<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\VendorRepository;
use Core\Contracts\CacheInterface;
use Exception;

class VendorService
{
    private VendorRepository $repository;
    private CacheInterface $cache;

    public function __construct(VendorRepository $repository, CacheInterface $cache)
    {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    // --- VENDOR MANAGEMENT ---

    public function createVendor(array $data): int
    {
        if (empty($data['name'])) {
            throw new Exception("Satıcı adı boş olamaz.");
        }
        if (empty($data['email'])) {
            throw new Exception("Satıcı e-posta adresi boş olamaz.");
        }
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        return $this->repository->createVendor($data);
    }

    public function getVendor(int $id): ?array
    {
        $cacheKey = "vendor_{$id}";
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $vendor = $this->repository->getVendor($id);
        if ($vendor) {
            $this->cache->set($cacheKey, $vendor, 3600); // 1 hour
        }
        return $vendor;
    }

    public function updateVendor(int $id, array $data): bool
    {
        $success = $this->repository->updateVendor($id, $data);
        if ($success) {
            $this->clearCache($id);
        }
        return $success;
    }

    public function deleteVendor(int $id): bool
    {
        $success = $this->repository->deleteVendor($id);
        if ($success) {
            $this->clearCache($id);
        }
        return $success;
    }

    public function listVendors(array $filters = []): array
    {
        return $this->repository->listVendors($filters);
    }

    // --- VENDOR USERS ---

    public function registerVendorUser(array $data): int
    {
        if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
            throw new Exception("Tüm kullanıcı bilgileri eksiksiz girilmelidir.");
        }
        return $this->repository->createVendorUser($data);
    }

    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->repository->getVendorUserByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }

    // --- PRODUCTS ASSOCIATION ---

    public function linkProduct(int $vendorId, int $productId): bool
    {
        $success = $this->repository->associateProduct($vendorId, $productId);
        if ($success) {
            $this->cache->delete("vendor_products_{$vendorId}");
        }
        return $success;
    }

    public function getVendorProducts(int $vendorId): array
    {
        $cacheKey = "vendor_products_{$vendorId}";
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $products = $this->repository->listVendorProducts($vendorId);
        $this->cache->set($cacheKey, $products, 1800); // 30 mins
        return $products;
    }

    // --- COMMISSION SYSTEM ---

    public function calculateCommission(int $vendorId, float $itemPrice, float $rateOverride = null): float
    {
        $vendor = $this->getVendor($vendorId);
        if (!$vendor) {
            throw new Exception("Satıcı bulunamadı.");
        }

        $rate = $rateOverride !== null ? $rateOverride : (float)$vendor['commission_rate'];
        $commissionType = $vendor['commission_type'];

        if ($commissionType === 'flat') {
            return $rate;
        }

        // Percentage calculations
        return round(($itemPrice * $rate) / 100, 2);
    }

    public function recordCommission(array $data): int
    {
        return $this->repository->createCommission($data);
    }

    // --- WALLET & PAYMENTS ---

    public function getWallet(int $vendorId): ?array
    {
        $cacheKey = "vendor_wallet_{$vendorId}";
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $wallet = $this->repository->getWallet($vendorId);
        if ($wallet) {
            $this->cache->set($cacheKey, $wallet, 900); // 15 mins
        }
        return $wallet;
    }

    public function deposit(int $vendorId, float $amount, string $refType, int $refId, string $description = ''): int
    {
        $this->cache->delete("vendor_wallet_{$vendorId}");
        $this->cache->delete("vendor_stats_{$vendorId}");
        $this->cache->delete("vendor_dashboard_{$vendorId}");

        return $this->repository->addWalletTransaction([
            'vendor_id' => $vendorId,
            'type' => 'credit',
            'amount' => $amount,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'description' => $description
        ]);
    }

    public function withdraw(int $vendorId, float $amount, string $refType, int $refId, string $description = ''): int
    {
        $wallet = $this->getWallet($vendorId);
        if (!$wallet || (float)$wallet['balance'] < $amount) {
            throw new Exception("Cüzdanda yetersiz bakiye.");
        }

        $this->cache->delete("vendor_wallet_{$vendorId}");
        $this->cache->delete("vendor_stats_{$vendorId}");
        $this->cache->delete("vendor_dashboard_{$vendorId}");

        return $this->repository->addWalletTransaction([
            'vendor_id' => $vendorId,
            'type' => 'debit',
            'amount' => $amount,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'description' => $description
        ]);
    }

    public function requestPayout(int $vendorId, int $bankAccountId, float $amount): int
    {
        $wallet = $this->getWallet($vendorId);
        if (!$wallet || (float)$wallet['balance'] < $amount) {
            throw new Exception("Yetersiz cüzdan bakiyesi sebebiyle ödeme talep edilemez.");
        }

        // Debit the amount first as pending withdrawal
        $this->withdraw($vendorId, $amount, 'payment', 0, 'Talep edilen hak ediş ödemesi');

        return $this->repository->createPayment([
            'vendor_id' => $vendorId,
            'bank_account_id' => $bankAccountId,
            'amount' => $amount,
            'status' => 'pending'
        ]);
    }

    // --- STATISTICS & REPORTS ---

    public function getStatistics(int $vendorId): array
    {
        $cacheKey = "vendor_stats_{$vendorId}";
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $stats = $this->repository->getStatistics($vendorId);
        if (!$stats) {
            $stats = [
                'total_sales' => 0.00,
                'total_orders' => 0,
                'total_earnings' => 0.00,
                'total_commission' => 0.00,
                'active_products' => 0
            ];
        }
        $this->cache->set($cacheKey, $stats, 1800);
        return $stats;
    }

    // --- INTERNAL HELPERS ---

    private function generateSlug(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $find = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', ' ', '&', '_', '-'];
        $replace = ['c', 'g', 'i', 'o', 's', 'u', '-', '-', '-', '-'];
        $text = str_replace($find, $replace, $text);
        $text = preg_replace('/[^a-z0-9\-]/', '', $text);
        return trim(preg_replace('/-+/', '-', $text), '-');
    }

    private function clearCache(int $vendorId): void
    {
        $this->cache->delete("vendor_{$vendorId}");
        $this->cache->delete("vendor_products_{$vendorId}");
        $this->cache->delete("vendor_stats_{$vendorId}");
        $this->cache->delete("vendor_wallet_{$vendorId}");
        $this->cache->delete("vendor_dashboard_{$vendorId}");
    }
}
