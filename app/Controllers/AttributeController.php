<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\AttributeService;
use App\Repositories\AttributeRepository;
use Core\View\View;
use Exception;

class AttributeController extends Controller {
    private AttributeService $service;
    private AttributeRepository $repository;

    public function __construct(View $view, AttributeService $service, AttributeRepository $repository) {
        parent::__construct($view);
        $this->service = $service;
        $this->repository = $repository;
    }

    public function index(Request $request, Response $response): string {
        $filters = [];
        if ($request->get('q')) {
            $filters['q'] = trim((string)$request->get('q'));
        }
        if ($request->get('type')) {
            $filters['type'] = trim((string)$request->get('type'));
        }

        $attributes = $this->repository->getAll($filters);

        return $this->render('admin/attributes/index', [
            'attributes' => $attributes,
            'q' => $filters['q'] ?? '',
            'type' => $filters['type'] ?? ''
        ]);
    }

    public function showCreate(Request $request, Response $response): string {
        return $this->render('admin/attributes/create');
    }

    public function store(Request $request, Response $response): void {
        try {
            $data = $request->post();
            $this->service->create($data);
            $response->redirect('/admin/attributes?success=' . urlencode('Özellik başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/attributes/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function showEdit(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $attribute = $this->repository->getById($id);
        if (!$attribute) {
            $response->redirect('/admin/attributes?error=' . urlencode('Özellik bulunamadı.'));
            exit;
        }

        return $this->render('admin/attributes/edit', [
            'attribute' => $attribute
        ]);
    }

    public function update(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $data = $request->post();
            $this->service->update($id, $data);
            $response->redirect('/admin/attributes?success=' . urlencode('Özellik başarıyla güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/attributes/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->service->delete($id);
            $response->redirect('/admin/attributes?success=' . urlencode('Özellik başarıyla silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/attributes?error=' . urlencode($e->getMessage()));
        }
    }

    // --- Sets ---
    public function indexSets(Request $request, Response $response): string {
        $filters = [];
        if ($request->get('q')) {
            $filters['q'] = trim((string)$request->get('q'));
        }

        $sets = $this->repository->getSets($filters);

        return $this->render('admin/attributes/sets/index', [
            'sets' => $sets,
            'q' => $filters['q'] ?? ''
        ]);
    }

    public function showCreateSet(Request $request, Response $response): string {
        $attributes = $this->repository->getAll();
        return $this->render('admin/attributes/sets/create', [
            'attributes' => $attributes
        ]);
    }

    public function storeSet(Request $request, Response $response): void {
        try {
            $data = $request->post();
            $this->service->createSet($data);
            $response->redirect('/admin/attributes/sets?success=' . urlencode('Özellik grubu başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/attributes/sets/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function showEditSet(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $set = $this->repository->getSetById($id);
        if (!$set) {
            $response->redirect('/admin/attributes/sets?error=' . urlencode('Özellik grubu bulunamadı.'));
            exit;
        }

        $attributes = $this->repository->getAll();

        return $this->render('admin/attributes/sets/edit', [
            'set' => $set,
            'attributes' => $attributes
        ]);
    }

    public function updateSet(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $data = $request->post();
            $this->service->updateSet($id, $data);
            $response->redirect('/admin/attributes/sets?success=' . urlencode('Özellik grubu başarıyla güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/attributes/sets/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function deleteSet(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->service->deleteSet($id);
            $response->redirect('/admin/attributes/sets?success=' . urlencode('Özellik grubu başarıyla silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/attributes/sets?error=' . urlencode($e->getMessage()));
        }
    }

    // --- REST API Endpoints ---
    public function apiIndex(Request $request, Response $response): void {
        $filters = $request->get();
        $attributes = $this->repository->getAll($filters);
        $this->json($attributes);
    }

    public function apiShow(Request $request, Response $response): void {
        // Extract ID from URL path or parameter
        $pathParts = explode('/', trim($request->getPath(), '/'));
        $id = (int)end($pathParts);
        $attribute = $this->repository->getById($id);
        if (!$attribute) {
            $this->json(['error' => 'Özellik bulunamadı.'], 404);
            return;
        }
        $this->json($attribute);
    }

    public function apiStore(Request $request, Response $response): void {
        try {
            $data = json_decode($request->getRawBody(), true) ?? $request->post();
            $id = $this->service->create($data);
            $this->json(['success' => true, 'id' => $id], 201);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function apiUpdate(Request $request, Response $response): void {
        $pathParts = explode('/', trim($request->getPath(), '/'));
        $id = (int)end($pathParts);
        try {
            $data = json_decode($request->getRawBody(), true) ?? $request->post();
            $this->service->update($id, $data);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function apiDelete(Request $request, Response $response): void {
        $pathParts = explode('/', trim($request->getPath(), '/'));
        $id = (int)end($pathParts);
        try {
            $this->service->delete($id);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
