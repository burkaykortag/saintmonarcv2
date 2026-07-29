<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BrandRepository;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;
use Exception;

class BrandService {
    private BrandRepository $repository;
    private DatabaseInterface $db;
    private CacheInterface $cache;
    private const CACHE_KEY = 'active_brands';

    public function __construct(BrandRepository $repository, DatabaseInterface $db, CacheInterface $cache) {
        $this->repository = $repository;
        $this->db = $db;
        $this->cache = $cache;
    }

    public function getActiveCached(): array {
        if ($this->cache->has(self::CACHE_KEY)) {
            return $this->cache->get(self::CACHE_KEY);
        }

        $brands = $this->repository->getAll(['is_active' => 1]);
        $this->cache->set(self::CACHE_KEY, $brands, 86400); // 24 hours
        return $brands;
    }

    public function create(array $data): int {
        $name = trim($data['name'] ?? '');
        $shortDesc = trim($data['short_description'] ?? '');
        $desc = trim($data['description'] ?? '');
        $website = trim($data['website'] ?? '');
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $isActive = (int)($data['is_active'] ?? 1);
        $isFeatured = (int)($data['is_featured'] ?? 0);
        $showInHome = (int)($data['show_in_home'] ?? 0);

        $logoId = $data['logo_image_id'] ? (int)$data['logo_image_id'] : null;
        $coverId = $data['cover_image_id'] ? (int)$data['cover_image_id'] : null;
        $bannerId = $data['banner_image_id'] ? (int)$data['banner_image_id'] : null;

        if ($name === '') {
            throw new Exception("Marka adı boş bırakılamaz.");
        }

        $slug = $this->generateUniqueSlug($name);

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO brands (slug, logo_image_id, cover_image_id, banner_image_id, website, is_active, is_featured, show_in_home, sort_order) 
                 VALUES (:slug, :logo, :cover, :banner, :website, :active, :featured, :home, :sort)",
                [
                    ':slug' => $slug,
                    ':logo' => $logoId,
                    ':cover' => $coverId,
                    ':banner' => $bannerId,
                    ':website' => $website,
                    ':active' => $isActive,
                    ':featured' => $isFeatured,
                    ':home' => $showInHome,
                    ':sort' => $sortOrder
                ]
            );
            $brandId = (int)$this->db->lastInsertId();

            // Insert translation
            $this->db->execute(
                "INSERT INTO brand_translations (brand_id, language_id, name, short_description, description) 
                 VALUES (:brand_id, 1, :name, :short_desc, :desc)",
                [
                    ':brand_id' => $brandId,
                    ':name' => $name,
                    ':short_desc' => $shortDesc,
                    ':desc' => $desc
                ]
            );

            // Save SEO Meta
            if (!empty($data['seo'])) {
                $this->updateSeoMeta($brandId, $data['seo']);
            }

            $this->db->commit();
            $this->clearCache();

            return $brandId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): void {
        $name = trim($data['name'] ?? '');
        $shortDesc = trim($data['short_description'] ?? '');
        $desc = trim($data['description'] ?? '');
        $website = trim($data['website'] ?? '');
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $isActive = (int)($data['is_active'] ?? 1);
        $isFeatured = (int)($data['is_featured'] ?? 0);
        $showInHome = (int)($data['show_in_home'] ?? 0);

        $logoId = $data['logo_image_id'] ? (int)$data['logo_image_id'] : null;
        $coverId = $data['cover_image_id'] ? (int)$data['cover_image_id'] : null;
        $bannerId = $data['banner_image_id'] ? (int)$data['banner_image_id'] : null;

        if ($name === '') {
            throw new Exception("Marka adı boş bırakılamaz.");
        }

        $current = $this->repository->getById($id);
        if (!$current) {
            throw new Exception("Marka bulunamadı.");
        }

        $slug = $current['slug'];
        if ($name !== $current['name']) {
            $slug = $this->generateUniqueSlug($name, $id);
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE brands 
                 SET slug = :slug, logo_image_id = :logo, cover_image_id = :cover, banner_image_id = :banner, 
                     website = :website, is_active = :active, is_featured = :featured, show_in_home = :home, sort_order = :sort 
                 WHERE id = :id",
                [
                    ':slug' => $slug,
                    ':logo' => $logoId,
                    ':cover' => $coverId,
                    ':banner' => $bannerId,
                    ':website' => $website,
                    ':active' => $isActive,
                    ':featured' => $isFeatured,
                    ':home' => $showInHome,
                    ':sort' => $sortOrder,
                    ':id' => $id
                ]
            );

            // Update translation
            $this->db->execute(
                "UPDATE brand_translations 
                 SET name = :name, short_description = :short_desc, description = :desc 
                 WHERE brand_id = :brand_id AND language_id = 1",
                [
                    ':name' => $name,
                    ':short_desc' => $shortDesc,
                    ':desc' => $desc,
                    ':brand_id' => $id
                ]
            );

            // Save SEO Meta
            if (!empty($data['seo'])) {
                $this->updateSeoMeta($id, $data['seo']);
            }

            $this->db->commit();
            $this->clearCache();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void {
        $this->db->execute("UPDATE brands SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);
        $this->clearCache();
    }

    public function getSeoMeta(int $brandId): array {
        $sql = "SELECT * FROM seo_meta WHERE model_type = 'Brand' AND model_id = :id LIMIT 1";
        $rows = $this->db->query($sql, [':id' => $brandId]);
        return $rows[0] ?? [
            'title' => '', 'description' => '', 'keywords' => '',
            'canonical_url' => '', 'og_title' => '', 'og_description' => '', 'og_image' => '', 'robots' => 'index, follow'
        ];
    }

    private function updateSeoMeta(int $brandId, array $seo): void {
        $exists = $this->db->query("SELECT id FROM seo_meta WHERE model_type = 'Brand' AND model_id = :id LIMIT 1", [':id' => $brandId]);
        $params = [
            ':title' => trim($seo['title'] ?? ''),
            ':desc' => trim($seo['description'] ?? ''),
            ':keywords' => trim($seo['keywords'] ?? ''),
            ':canonical' => trim($seo['canonical_url'] ?? ''),
            ':og_title' => trim($seo['og_title'] ?? ''),
            ':og_desc' => trim($seo['og_description'] ?? ''),
            ':og_image' => trim($seo['og_image'] ?? ''),
            ':robots' => trim($seo['robots'] ?? 'index, follow'),
            ':id' => $brandId
        ];

        if (!empty($exists)) {
            $this->db->execute(
                "UPDATE seo_meta 
                 SET title = :title, description = :desc, keywords = :keywords, canonical_url = :canonical, 
                     og_title = :og_title, og_description = :og_desc, og_image = :og_image, robots = :robots 
                 WHERE model_type = 'Brand' AND model_id = :id",
                $params
            );
        } else {
            $this->db->execute(
                "INSERT INTO seo_meta (model_type, model_id, title, description, keywords, canonical_url, og_title, og_description, og_image, robots) 
                 VALUES ('Brand', :id, :title, :desc, :keywords, :canonical, :og_title, :og_desc, :og_image, :robots)",
                $params
            );
        }
    }

    private function generateUniqueSlug(string $name, ?int $excludeId = null): string {
        $slug = $this->slugify($name);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $sql = "SELECT id FROM brands WHERE slug = :slug AND deleted_at IS NULL";
            $params = [':slug' => $slug];
            if ($excludeId) {
                $sql .= " AND id != :id";
                $params[':id'] = $excludeId;
            }
            $exists = $this->db->query($sql, $params);
            if (empty($exists)) {
                break;
            }
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugify(string $text): string {
        $find = ['Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö', 'ç', 'ş', 'ğ', 'ü', 'ı', 'ö'];
        $replace = ['c', 's', 'g', 'u', 'i', 'o', 'c', 's', 'g', 'u', 'i', 'o'];
        $text = str_replace($find, $replace, $text);
        $text = preg_replace('/[^A-Za-z0-9-]+/', '-', $text);
        $text = trim($text, '-');
        return strtolower($text);
    }

    public function clearCache(): void {
        $this->cache->delete(self::CACHE_KEY);
    }
}
