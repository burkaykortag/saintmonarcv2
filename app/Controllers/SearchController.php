<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\SearchService;
use App\Repositories\SearchRepository;
use Core\Security;
use Core\View\View;

class SearchController extends Controller {
    private SearchService $service;
    private SearchRepository $repository;
    private Security $security;

    public function __construct(View $view, SearchService $service, SearchRepository $repository, Security $security) {
        parent::__construct($view);
        $this->service = $service;
        $this->repository = $repository;
        $this->security = $security;
    }

    /**
     * Arama Motoru Genel Paneli.
     */
    public function index(Request $request, Response $response): string {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        
        $indexCount = $db->query("SELECT COUNT(*) as cnt FROM search_index")[0]['cnt'] ?? 0;
        $redirects = $this->repository->getRedirects();
        $rebuildLogs = $this->repository->getRebuildLogs(5);

        return $this->render('admin/search/index', [
            'indexCount' => $indexCount,
            'redirects' => $redirects,
            'rebuildLogs' => $rebuildLogs
        ]);
    }

    /**
     * Arama İstatistikleri Rapor Sayfası.
     */
    public function statistics(Request $request, Response $response): string {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);

        $totalQueries = $db->query("SELECT COUNT(*) as cnt FROM search_logs")[0]['cnt'] ?? 0;
        $failedQueries = $db->query("SELECT COUNT(*) as cnt FROM search_logs WHERE results_count = 0")[0]['cnt'] ?? 0;

        $popular = $this->repository->getPopular(20);
        $logs = $db->query("SELECT * FROM search_logs ORDER BY id DESC LIMIT 50");

        return $this->render('admin/search/statistics', [
            'totalQueries' => $totalQueries,
            'failedQueries' => $failedQueries,
            'popular' => $popular,
            'logs' => $logs
        ]);
    }

    /**
     * Eş Anlamlı & Stop Words Yönetim Sayfası.
     */
    public function synonyms(Request $request, Response $response): string {
        $synonyms = $this->repository->getSynonyms();
        $stopWords = $this->repository->getStopWords();

        return $this->render('admin/search/synonyms', [
            'synonyms' => $synonyms,
            'stopWords' => $stopWords
        ]);
    }

    /**
     * Boost (Arama Puanı) Yönetim Sayfası.
     */
    public function boost(Request $request, Response $response): string {
        $rules = $this->repository->getBoostRules();
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $products = $db->query("SELECT id, sku FROM products WHERE deleted_at IS NULL LIMIT 100");

        return $this->render('admin/search/boost', [
            'rules' => $rules,
            'products' => $products
        ]);
    }

    /**
     * İndeksi Yeniden Oluştur (Rebuild Index).
     */
    public function rebuild(Request $request, Response $response): void {
        try {
            $total = $this->service->rebuildIndex();
            $response->redirect(url('/admin/search?success=' . $total . '+içerik+başarıyla+yeniden+indekslendi.'));
        } catch (\Throwable $e) {
            $response->redirect(url('/admin/search?error=İndeksleme+hatası:+' . urlencode($e->getMessage())));
        }
    }

    /**
     * Arama Önbelleğini Temizler.
     */
    public function clearCache(Request $request, Response $response): void {
        $this->repository->clearCache();
        $response->redirect(url('/admin/search?success=Arama+önbelleği+başarıyla+temizlendi.'));
    }

    // --- CRUD Yöntemleri ---

    public function storeSynonym(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/search/synonyms?error=CSRF+hatası.'));
            return;
        }

        $source = $request->get('source_word');
        $targets = $request->get('target_words');

        if ($source && $targets) {
            $this->repository->saveSynonym($source, $targets);
            $response->redirect(url('/admin/search/synonyms?success=Eş+anlamlı+kaydedildi.'));
        } else {
            $response->redirect(url('/admin/search/synonyms?error=Lütfen+tüm+alanları+doldurun.'));
        }
    }

    public function deleteSynonym(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/search/synonyms?error=CSRF+hatası.'));
            return;
        }

        $id = (int)$request->get('id');
        $this->repository->deleteSynonym($id);
        $response->redirect(url('/admin/search/synonyms?success=Eş+anlamlı+silindi.'));
    }

    public function storeStopWord(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/search/synonyms?error=CSRF+hatası.'));
            return;
        }

        $word = $request->get('word');
        if ($word) {
            $this->repository->saveStopWord($word);
            $response->redirect(url('/admin/search/synonyms?success=Stop+word+kaydedildi.'));
        } else {
            $response->redirect(url('/admin/search/synonyms?error=Kelime+boş+olamaz.'));
        }
    }

    public function deleteStopWord(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/search/synonyms?error=CSRF+hatası.'));
            return;
        }

        $id = (int)$request->get('id');
        $this->repository->deleteStopWord($id);
        $response->redirect(url('/admin/search/synonyms?success=Stop+word+silindi.'));
    }

    public function storeRedirect(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/search?error=CSRF+hatası.'));
            return;
        }

        $keyword = $request->get('keyword');
        $url = $request->get('redirect_url');
        $code = (int)$request->get('redirect_code', 301);

        if ($keyword && $url) {
            $this->repository->saveRedirect($keyword, $url, $code);
            $response->redirect(url('/admin/search?success=Yönlendirme+kaydedildi.'));
        } else {
            $response->redirect(url('/admin/search?error=Tüm+alanlar+gereklidir.'));
        }
    }

    public function deleteRedirect(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/search?error=CSRF+hatası.'));
            return;
        }

        $id = (int)$request->get('id');
        $this->repository->deleteRedirect($id);
        $response->redirect(url('/admin/search?success=Yönlendirme+silindi.'));
    }

    public function storeBoost(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/search/boost?error=CSRF+hatası.'));
            return;
        }

        $targetType = $request->get('target_type');
        $targetId = $request->get('target_id') ? (int)$request->get('target_id') : null;
        $keyword = $request->get('keyword');
        $value = (float)$request->get('boost_value', 1.0);

        $this->repository->saveBoostRule($targetType, $targetId, $keyword, $value);
        $response->redirect(url('/admin/search/boost?success=Boost+kuralı+eklendi.'));
    }

    public function deleteBoost(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/search/boost?error=CSRF+hatası.'));
            return;
        }

        $id = (int)$request->get('id');
        $this->repository->deleteBoostRule($id);
        $response->redirect(url('/admin/search/boost?success=Boost+kuralı+silindi.'));
    }

    // --- REST API Endpoints ---

    public function apiSearch(Request $request, Response $response): void {
        $q = $request->get('q') ?? '';
        $filters = [
            'price_min' => $request->get('price_min'),
            'price_max' => $request->get('price_max'),
            'stock_status' => $request->get('stock_status')
        ];
        $page = (int)$request->get('page', 1);

        $res = $this->service->search($q, $filters, $page);
        $response->json(['success' => true, 'data' => $res]);
    }

    public function apiSuggest(Request $request, Response $response): void {
        $q = $request->get('q') ?? '';
        $res = $this->service->getSuggestions($q);
        $response->json(['success' => true, 'data' => $res]);
    }

    public function apiAutocomplete(Request $request, Response $response): void {
        $q = $request->get('q') ?? '';
        $res = $this->service->autocomplete($q);
        $response->json(['success' => true, 'data' => $res]);
    }

    public function apiPopular(Request $request, Response $response): void {
        $res = $this->repository->getPopular(10);
        $response->json(['success' => true, 'data' => $res]);
    }

    public function apiHistory(Request $request, Response $response): void {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $res = $db->query("SELECT DISTINCT query FROM search_logs ORDER BY id DESC LIMIT 10");
        $response->json(['success' => true, 'data' => $res]);
    }

    public function apiStatistics(Request $request, Response $response): void {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $total = $db->query("SELECT COUNT(*) as cnt FROM search_logs")[0]['cnt'] ?? 0;
        $failed = $db->query("SELECT COUNT(*) as cnt FROM search_logs WHERE results_count = 0")[0]['cnt'] ?? 0;
        $response->json(['success' => true, 'data' => ['total_searches' => $total, 'failed_searches' => $failed]]);
    }

    public function apiRebuild(Request $request, Response $response): void {
        try {
            $total = $this->service->rebuildIndex();
            $response->json(['success' => true, 'total_indexed' => $total]);
        } catch (\Throwable $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiAi(Request $request, Response $response): void {
        $q = $request->get('q') ?? '';
        $res = $this->service->aiSearch($q);
        $response->json(['success' => true, 'data' => $res]);
    }
}
