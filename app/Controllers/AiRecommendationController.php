<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\AiRecommendationServiceInterface;
use Core\Security;
use Core\View\View;

class AiRecommendationController extends Controller {
    private AiRecommendationServiceInterface $service;
    private Security $security;

    public function __construct(View $view, AiRecommendationServiceInterface $service, Security $security) {
        parent::__construct($view);
        $this->service = $service;
        $this->security = $security;
    }

    /**
     * Yapay Zeka Öneri Motoru Paneli.
     */
    public function index(Request $request, Response $response): string {
        // CSRF yetkilendirmesi check'leri base framework'ten gelmektedir.
        // Mevcut önerileri listele (Eğer yoksa otomatik üretir)
        $recommendations = $this->service->generateRecommendations();

        return $this->render('admin/recommendations/index', [
            'recommendations' => $recommendations
        ]);
    }

    /**
     * Önerileri yeniden analiz edip günceller.
     */
    public function generate(Request $request, Response $response): void {
        $this->service->generateRecommendations();
        $response->redirect(url('/admin/recommendations?success=Analiz+ve+öneriler+güncellendi.'));
    }

    /**
     * Öneriyi otomatik kampanyaya çevirerek uygular.
     */
    public function apply(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/recommendations?error=CSRF+doğrulama+hatası.'));
            return;
        }

        $id = (int)$request->get('id');
        $success = $this->service->applyRecommendation($id);

        if ($success) {
            $response->redirect(url('/admin/recommendations?success=Öneri+başarıyla+kampanyaya+dönüştürüldü.'));
        } else {
            $response->redirect(url('/admin/recommendations?error=Öneri+uygulanırken+bir+hata+oluştu.'));
        }
    }

    /**
     * Öneriyi yoksayar.
     */
    public function dismiss(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/recommendations?error=CSRF+doğrulama+hatası.'));
            return;
        }

        $id = (int)$request->get('id');
        $success = $this->service->dismissRecommendation($id);

        if ($success) {
            $response->redirect(url('/admin/recommendations?success=Öneri+yoksayıldı.'));
        } else {
            $response->redirect(url('/admin/recommendations?error=Öneri+güncellenirken+hata+oluştu.'));
        }
    }
}
