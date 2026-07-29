<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\MediaService;
use App\Repositories\MediaRepository;
use Core\Contracts\DatabaseInterface;
use Core\View\View;
use Exception;

class MediaController extends Controller {
    private MediaService $mediaService;
    private MediaRepository $mediaRepository;
    private DatabaseInterface $db;

    public function __construct(View $view, MediaService $mediaService, MediaRepository $mediaRepository, DatabaseInterface $db) {
        parent::__construct($view);
        $this->mediaService = $mediaService;
        $this->mediaRepository = $mediaRepository;
        $this->db = $db;
    }

    public function index(Request $request, Response $response): string {
        $allTags = $this->db->query("SELECT * FROM media_tags ORDER BY name ASC");
        return $this->render('admin/media/index', [
            'all_tags' => $allTags
        ]);
    }

    public function listAjax(Request $request, Response $response): void {
        $folderId = $request->get('folder_id') ? (int)$request->get('folder_id') : null;
        $search = $request->get('q') ?? '';
        $extFilter = $request->get('extension') ?? '';
        $dateFilter = $request->get('date') ?? '';
        $sizeFilter = $request->get('size') ?? '';
        $tagId = $request->get('tag_id') ? (int)$request->get('tag_id') : null;
        $sortBy = $request->get('sort_by') ?? 'date';
        $sortOrder = $request->get('sort_order') ?? 'desc';
        
        $limit = $request->get('limit') ? (int)$request->get('limit') : 24;
        $page = $request->get('page') ? (int)$request->get('page') : 1;
        $offset = ($page - 1) * $limit;

        $filters = [
            'folder_id' => $folderId,
            'q' => $search,
            'extension' => $extFilter,
            'date' => $dateFilter,
            'size' => $sizeFilter,
            'tag_id' => $tagId,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ];

        // Fetch folders for navigation
        $folderSql = "SELECT * FROM media_folders WHERE parent_id " . ($folderId ? "= :parent_id" : "IS NULL");
        $folderParams = $folderId ? [':parent_id' => $folderId] : [];
        $folders = $this->db->query($folderSql, $folderParams);

        // Fetch files using paginated repository query
        $files = $this->mediaRepository->getAll($filters, $limit, $offset);

        // Map usages details and tags array
        foreach ($files as &$file) {
            $file['tags'] = $file['tags_list'] ? explode(',', $file['tags_list']) : [];
            $file['usages'] = $this->getMediaUsage((int)$file['id'], $file['filename']);
            
            // Format size for display
            $file['formatted_size'] = $this->formatBytes((int)$file['file_size']);
        }

        // Folder breadcrumbs path
        $breadcrumbs = [];
        $tempFolderId = $folderId;
        while ($tempFolderId) {
            $f = $this->db->query("SELECT id, parent_id, name FROM media_folders WHERE id = :id LIMIT 1", [':id' => $tempFolderId]);
            if (!empty($f)) {
                $breadcrumbs[] = $f[0];
                $tempFolderId = $f[0]['parent_id'] ? (int)$f[0]['parent_id'] : null;
            } else {
                break;
            }
        }
        $breadcrumbs = array_reverse($breadcrumbs);

        $response->json([
            'success' => true,
            'folders' => $folders,
            'files' => $files,
            'breadcrumbs' => $breadcrumbs,
            'page' => $page,
            'limit' => $limit,
            'has_more' => count($files) >= $limit
        ]);
    }

    public function uploadAjax(Request $request, Response $response): void {
        $folderId = $request->post('folder_id') ? (int)$request->post('folder_id') : null;
        $adminId = $_SESSION['admin_id'] ?? null;

        if (empty($_FILES['files'])) {
            $response->json(['success' => false, 'message' => 'Yüklenecek dosya bulunamadı.'], 400);
            return;
        }

        $uploadedFiles = $_FILES['files'];
        $results = [];
        $errors = [];

        if (is_array($uploadedFiles['name'])) {
            $total = count($uploadedFiles['name']);
            for ($i = 0; $i < $total; $i++) {
                $single = [
                    'name' => $uploadedFiles['name'][$i],
                    'type' => $uploadedFiles['type'][$i],
                    'tmp_name' => $uploadedFiles['tmp_name'][$i],
                    'error' => $uploadedFiles['error'][$i],
                    'size' => $uploadedFiles['size'][$i]
                ];
                try {
                    $results[] = $this->mediaService->upload($single, $folderId, (int)$adminId);
                } catch (Exception $e) {
                    $errors[] = $single['name'] . ': ' . $e->getMessage();
                }
            }
        } else {
            try {
                $results[] = $this->mediaService->upload($uploadedFiles, $folderId, (int)$adminId);
            } catch (Exception $e) {
                $errors[] = $uploadedFiles['name'] . ': ' . $e->getMessage();
            }
        }

        if (count($errors) > 0) {
            $response->json([
                'success' => count($results) > 0,
                'message' => 'Bazı dosya yüklemeleri başarısız oldu.',
                'errors' => $errors,
                'uploaded' => $results
            ], 400);
        } else {
            $response->json([
                'success' => true,
                'message' => 'Tüm dosyalar başarıyla yüklendi.',
                'uploaded' => $results
            ]);
        }
    }

    public function saveSeo(Request $request, Response $response): void {
        $id = (int)($request->post('id') ?? 0);
        $title = trim($request->post('title') ?? '');
        $altText = trim($request->post('alt_text') ?? '');
        $caption = trim($request->post('caption') ?? '');
        $description = trim($request->post('description') ?? '');

        try {
            $this->db->execute(
                "UPDATE media_library 
                 SET title = :title, alt_text = :alt_text, caption = :caption, description = :description 
                 WHERE id = :id",
                [
                    ':title' => $title !== '' ? $title : null,
                    ':alt_text' => $altText !== '' ? $altText : null,
                    ':caption' => $caption !== '' ? $caption : null,
                    ':description' => $description !== '' ? $description : null,
                    ':id' => $id
                ]
            );

            // Tags update
            $tags = $request->post('tags') ?? [];
            $this->db->execute("DELETE FROM media_tag_relations WHERE media_id = :id", [':id' => $id]);
            foreach ($tags as $tagId) {
                $this->db->execute(
                    "INSERT INTO media_tag_relations (media_id, tag_id) VALUES (:mid, :tid)",
                    [':mid' => $id, ':tid' => (int)$tagId]
                );
            }

            $response->json(['success' => true, 'message' => 'Meta verileri başarıyla kaydedildi.']);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkActionAjax(Request $request, Response $response): void {
        $action = $request->post('action') ?? '';
        $mediaIds = $request->post('media_ids') ?? [];
        
        if (empty($mediaIds)) {
            $response->json(['success' => false, 'message' => 'Seçili medya dosyası bulunamadı.'], 400);
            return;
        }

        try {
            if ($action === 'delete') {
                $this->mediaService->bulkDelete($mediaIds);
                $response->json(['success' => true, 'message' => 'Seçilen dosyalar kalıcı olarak silindi.']);
            } elseif ($action === 'move') {
                $targetFolderId = $request->post('target_folder_id') ? (int)$request->post('target_folder_id') : null;
                $this->mediaService->bulkMove($mediaIds, $targetFolderId);
                $response->json(['success' => true, 'message' => 'Seçilen dosyalar yeni klasöre taşındı.']);
            } elseif ($action === 'tag') {
                $tagIds = $request->post('tag_ids') ?? [];
                $this->mediaService->bulkTag($mediaIds, $tagIds);
                $response->json(['success' => true, 'message' => 'Seçilen dosyalara etiketler başarıyla eklendi.']);
            } elseif ($action === 'download') {
                $zipUrl = $this->mediaService->bulkDownload($mediaIds);
                $response->json(['success' => true, 'zip_url' => $zipUrl]);
            } elseif ($action === 'copy') {
                $this->mediaService->bulkCopy($mediaIds);
                $response->json(['success' => true, 'message' => 'Seçilen dosyalar başarıyla kopyalandı.']);
            } elseif ($action === 'webp') {
                $this->mediaService->bulkWebp($mediaIds);
                $response->json(['success' => true, 'message' => 'Seçilen görseller WebP formatına dönüştürüldü.']);
            } elseif ($action === 'regenerate') {
                $this->mediaService->bulkRegenerateThumbnails($mediaIds);
                $response->json(['success' => true, 'message' => 'Görsellerin boyutları ve önizlemeleri yeniden üretildi.']);
            } else {
                $response->json(['success' => false, 'message' => 'Bilinmeyen toplu işlem.'], 400);
            }
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function createFolderAjax(Request $request, Response $response): void {
        $name = trim($request->post('name') ?? '');
        $parentId = $request->post('parent_id') ? (int)$request->post('parent_id') : null;
        $adminId = $_SESSION['admin_id'] ?? null;

        if ($name === '') {
            $response->json(['success' => false, 'message' => 'Klasör adı boş bırakılamaz.'], 400);
            return;
        }

        try {
            $this->db->execute(
                "INSERT INTO media_folders (name, parent_id, created_by_admin) VALUES (:name, :parent, :admin)",
                [':name' => $name, ':parent' => $parentId, ':admin' => $adminId]
            );
            $response->json(['success' => true, 'message' => 'Klasör başarıyla oluşturuldu.']);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteFolderAjax(Request $request, Response $response): void {
        $folderId = (int)($request->post('folder_id') ?? 0);
        try {
            $this->cleanupFolderFiles($folderId);
            $this->db->execute("DELETE FROM media_folders WHERE id = :id", [':id' => $folderId]);
            $response->json(['success' => true, 'message' => 'Klasör ve tüm içeriği başarıyla silindi.']);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function getMediaUsage(int $mediaId, string $filename): array {
        $usages = [];
        $path = '%' . $filename . '%';

        $brandCount = $this->db->query("SELECT COUNT(*) as count FROM brands WHERE logo_image_id = :id", [':id' => $mediaId])[0]['count'] ?? 0;
        if ($brandCount > 0) $usages[] = "Marka Logosu ({$brandCount})";

        $productImgCount = $this->db->query("SELECT COUNT(*) as count FROM product_images WHERE image_id = :id", [':id' => $mediaId])[0]['count'] ?? 0;
        if ($productImgCount > 0) $usages[] = "Ürün Resmi ({$productImgCount})";

        $productCoverCount = $this->db->query("SELECT COUNT(*) as count FROM products WHERE cover_image_id = :id", [':id' => $mediaId])[0]['count'] ?? 0;
        if ($productCoverCount > 0) $usages[] = "Ürün Kapak Görseli ({$productCoverCount})";

        return $usages;
    }

    private function cleanupFolderFiles(int $folderId): void {
        $files = $this->db->query("SELECT id FROM media_library WHERE folder_id = :fid", [':fid' => $folderId]);
        $ids = array_map(fn($f) => (int)$f['id'], $files);
        if (!empty($ids)) {
            $this->mediaService->bulkDelete($ids);
        }
        
        $subfolders = $this->db->query("SELECT id FROM media_folders WHERE parent_id = :pid", [':pid' => $folderId]);
        foreach ($subfolders as $sub) {
            $this->cleanupFolderFiles((int)$sub['id']);
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
