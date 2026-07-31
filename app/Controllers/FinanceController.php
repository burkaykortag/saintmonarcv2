<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\FinanceService;
use App\Repositories\FinanceRepository;
use Core\Security;
use Core\View\View;

class FinanceController extends Controller {
    private FinanceService $service;
    private FinanceRepository $repository;
    private Security $security;

    public function __construct(View $view, FinanceService $service, FinanceRepository $repository, Security $security) {
        parent::__construct($view);
        $this->service = $service;
        $this->repository = $repository;
        $this->security = $security;
    }

    /**
     * Finans Paneli (Dashboard).
     */
    public function index(Request $request, Response $response): string {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);

        $totalRevenue = $db->query("SELECT SUM(amount) as total FROM revenues")[0]['total'] ?? 0.0;
        $totalExpense = $db->query("SELECT SUM(amount) as total FROM expenses")[0]['total'] ?? 0.0;
        $invoiceCount = $db->query("SELECT COUNT(*) as cnt FROM invoices")[0]['cnt'] ?? 0;

        $invoices = $this->repository->listInvoices([], 1, 10);
        $accounts = $this->repository->listFinancialAccounts();

        return $this->render('admin/finance/index', [
            'totalRevenue' => (float)$totalRevenue,
            'totalExpense' => (float)$totalExpense,
            'invoiceCount' => (int)$invoiceCount,
            'invoices' => $invoices,
            'accounts' => $accounts
        ]);
    }

    /**
     * Hesaplar Sayfası (Cari Hesaplar, Banka & Kasa).
     */
    public function accounts(Request $request, Response $response): string {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);

        $financialAccounts = $this->repository->listFinancialAccounts();
        $bankAccounts = $db->query("SELECT * FROM bank_accounts WHERE deleted_at IS NULL");
        $cashAccounts = $db->query("SELECT * FROM cash_accounts WHERE deleted_at IS NULL");
        $customerAccounts = $db->query(
            "SELECT ca.*, c.first_name, c.last_name 
             FROM customer_accounts ca 
             JOIN customers c ON ca.customer_id = c.id 
             WHERE ca.deleted_at IS NULL"
        );

        return $this->render('admin/finance/accounts', [
            'financialAccounts' => $financialAccounts,
            'bankAccounts' => $bankAccounts,
            'cashAccounts' => $cashAccounts,
            'customerAccounts' => $customerAccounts
        ]);
    }

    /**
     * Faturalar Listesi.
     */
    public function invoices(Request $request, Response $response): string {
        $invoices = $this->repository->listInvoices();
        return $this->render('admin/finance/invoices', [
            'invoices' => $invoices
        ]);
    }

    /**
     * Giderler Yönetimi.
     */
    public function expenses(Request $request, Response $response): string {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);

        $expenses = $this->repository->listExpenses();
        $categories = $db->query("SELECT * FROM expense_categories WHERE deleted_at IS NULL");

        return $this->render('admin/finance/expenses', [
            'expenses' => $expenses,
            'categories' => $categories
        ]);
    }

    /**
     * Gelirler Yönetimi.
     */
    public function revenues(Request $request, Response $response): string {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);

        $revenues = $this->repository->listRevenues();
        $categories = $db->query("SELECT * FROM revenue_categories WHERE deleted_at IS NULL");

        return $this->render('admin/finance/revenues', [
            'revenues' => $revenues,
            'categories' => $categories
        ]);
    }

    /**
     * Finansal Raporlar Sayfası (Kâr-Zarar, Bilanço, Mizan).
     */
    public function reports(Request $request, Response $response): string {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);

        $profit = $db->query("SELECT * FROM profit_loss_reports ORDER BY id DESC LIMIT 5");
        $balance = $db->query("SELECT * FROM balance_sheet_reports ORDER BY id DESC LIMIT 5");
        $mizan = $db->query("SELECT * FROM trial_balance ORDER BY id DESC LIMIT 20");

        return $this->render('admin/finance/reports', [
            'profit' => $profit,
            'balance' => $balance,
            'mizan' => $mizan
        ]);
    }

    /**
     * Masraf Ekleme.
     */
    public function storeExpense(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/expenses?error=CSRF+Hatası.'));
            return;
        }

        $this->repository->createExpense([
            'category_id' => (int)$request->get('category_id'),
            'amount' => (float)$request->get('amount'),
            'tax_amount' => (float)$request->get('tax_amount', 0.0),
            'description' => $request->get('description'),
            'expense_date' => $request->get('expense_date', date('Y-m-d'))
        ]);

        $response->redirect(url('/admin/expenses?success=Gider+başarıyla+kaydedildi.'));
    }

    /**
     * Gelir Ekleme.
     */
    public function storeRevenue(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/revenues?error=CSRF+Hatası.'));
            return;
        }

        $this->repository->createRevenue([
            'category_id' => (int)$request->get('category_id'),
            'amount' => (float)$request->get('amount'),
            'tax_amount' => (float)$request->get('tax_amount', 0.0),
            'description' => $request->get('description'),
            'revenue_date' => $request->get('revenue_date', date('Y-m-d'))
        ]);

        $response->redirect(url('/admin/revenues?success=Gelir+başarıyla+kaydedildi.'));
    }

    // --- REST API Metotları ---

    public function apiFinance(Request $request, Response $response): void {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $totalRevenue = $db->query("SELECT SUM(amount) as total FROM revenues")[0]['total'] ?? 0.0;
        $totalExpense = $db->query("SELECT SUM(amount) as total FROM expenses")[0]['total'] ?? 0.0;

        $response->json([
            'success' => true,
            'summary' => [
                'total_revenue' => (float)$totalRevenue,
                'total_expense' => (float)$totalExpense,
                'net_profit' => (float)$totalRevenue - (float)$totalExpense
            ]
        ]);
    }

    public function apiAccounts(Request $request, Response $response): void {
        $accounts = $this->repository->listFinancialAccounts();
        $response->json(['success' => true, 'data' => $accounts]);
    }

    public function apiInvoices(Request $request, Response $response): void {
        $invoices = $this->repository->listInvoices();
        $response->json(['success' => true, 'data' => $invoices]);
    }

    public function apiPayments(Request $request, Response $response): void {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $tx = $db->query("SELECT * FROM payment_transactions ORDER BY id DESC LIMIT 50");
        $response->json(['success' => true, 'data' => $tx]);
    }

    public function apiExpenses(Request $request, Response $response): void {
        $expenses = $this->repository->listExpenses();
        $response->json(['success' => true, 'data' => $expenses]);
    }

    public function apiRevenues(Request $request, Response $response): void {
        $revenues = $this->repository->listRevenues();
        $response->json(['success' => true, 'data' => $revenues]);
    }

    public function apiReports(Request $request, Response $response): void {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $mizan = $db->query("SELECT * FROM trial_balance ORDER BY id DESC");
        $response->json(['success' => true, 'trial_balance' => $mizan]);
    }
}
