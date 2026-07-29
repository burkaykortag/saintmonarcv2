<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategoryRepository;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;
use Exception;

class CategoryService {
    private CategoryRepository $repository;
    private DatabaseInterface $db;
    private CacheInterface $cache;
    private const CACHE_KEY = 'category_tree';

    public function __construct(CategoryRepository $repository, DatabaseInterface $db, CacheInterface $cache) {
        $this->repository = $repository;
        $this->db = $db;
        $this->cache = $cache;
    }

    public function getTreeCached(array $filters = []): array {
        // If query or filters are active, bypass cache for real results
        if (!empty($filters['q']) || isset($filters['is_active'])) {
            $categories = $this->repository->getAll($filters);
            return $this->repository->buildTree($categories);
        }

        if ($this->cache->has(self::CACHE_KEY)) {
            return $this->cache->get(self::CACHE_KEY);
        }

        $categories = $this->repository->getAll();
        $tree = $this->repository->buildTree($categories);
        $this->cache->set(self::CACHE_KEY, $tree, 86400); // Cache for 24h

        return $tree;
    }

    public function create(array $data): int {
        $name = trim($data['name'] ?? '');
        $parentId = $data['parent_id'] ? (int)$data['parent_id'] : null;
        $desc = trim($data['description'] ?? '');
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $isActive = (int)($data['is_active'] ?? 1);
        $showInMenu = (int)($data['show_in_menu'] ?? 0);
        $showInHome = (int)($data['show_in_home'] ?? 0);
        $isFeatured = (int)($data['is_featured'] ?? 0);

        $coverId = $data['cover_image_id'] ? (int)$data['cover_image_id'] : null;
        $bannerId = $data['banner_image_id'] ? (int)$data['banner_image_id'] : null;
        $iconId = $data['icon_image_id'] ? (int)$data['icon_image_id'] : null;

        if ($name === '') {
            throw new Exception("Kategori adı boş bırakılamaz.");
        }

        // Generate unique slug
        $slug = $this->generateUniqueSlug($name);

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO categories (parent_id, cover_image_id, banner_image_id, icon_image_id, slug, is_active, show_in_menu, show_in_home, is_featured, sort_order) 
                 VALUES (:parent, :cover, :banner, :icon, :slug, :active, :menu, :home, :featured, :sort)",
                [
                    ':parent' => $parentId,
                    ':cover' => $coverId,
                    ':banner' => $bannerId,
                    ':icon' => $iconId,
                    ':slug' => $slug,
                    ':active' => $isActive,
                    ':menu' => $showInMenu,
                    ':home' => $showInHome,
                    ':featured' => $isFeatured,
                    ':sort' => $sortOrder
                ]
            );
            $categoryId = (int)$this->db->lastInsertId();

            // Insert translation
            $this->db->execute(
                "INSERT INTO category_translations (category_id, language_id, name, description) 
                 VALUES (:category_id, 1, :name, :desc)",
                [
                    ':category_id' => $categoryId,
                    ':name' => $name,
                    ':desc' => $desc
                ]
            );

            // Handle SEO Meta mapping
            if (!empty($data['seo'])) {
                $this->updateSeoMeta($categoryId, $data['seo']);
            }

            $this->db->commit();
            $this->clearCache();

            return $categoryId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): void {
        $name = trim($data['name'] ?? '');
        $parentId = $data['parent_id'] ? (int)$data['parent_id'] : null;
        $desc = trim($data['description'] ?? '');
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $isActive = (int)($data['is_active'] ?? 1);
        $showInMenu = (int)($data['show_in_menu'] ?? 0);
        $showInHome = (int)($data['show_in_home'] ?? 0);
        $isFeatured = (int)($data['is_featured'] ?? 0);

        $coverId = $data['cover_image_id'] ? (int)$data['cover_image_id'] : null;
        $bannerId = $data['banner_image_id'] ? (int)$data['banner_image_id'] : null;
        $iconId = $data['icon_image_id'] ? (int)$data['icon_image_id'] : null;

        if ($name === '') {
            throw new Exception("Kategori adı boş bırakılamaz.");
        }

        // Prevent setting parent to itself
        if ($parentId === $id) {
            throw new Exception("Bir kategori kendisinin üst kategorisi olamaz.");
        }

        // Check current slug
        $current = $this->repository->getById($id);
        if (!$current) {
            throw new Exception("Kategori bulunamadı.");
        }

        // Generate slug if name changes
        $slug = $current['slug'];
        if ($name !== $current['name']) {
            $slug = $this->generateUniqueSlug($name, $id);
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE categories 
                 SET parent_id = :parent, cover_image_id = :cover, banner_image_id = :banner, icon_image_id = :icon, 
                     slug = :slug, is_active = :active, show_in_menu = :menu, show_in_home = :home, is_featured = :featured, sort_order = :sort 
                 WHERE id = :id",
                [
                    ':parent' => $parentId,
                    ':cover' => $coverId,
                    ':banner' => $bannerId,
                    ':icon' => $iconId,
                    ':slug' => $slug,
                    ':active' => $isActive,
                    ':menu' => $showInMenu,
                    ':home' => $showInHome,
                    ':featured' => $isFeatured,
                    ':sort' => $sortOrder,
                    ':id' => $id
                ]
            );

            // Update translation
            $this->db->execute(
                "UPDATE category_translations 
                 SET name = :name, description = :desc 
                 WHERE category_id = :category_id AND language_id = 1",
                [
                    ':name' => $name,
                    ':desc' => $desc,
                    ':category_id' => $id
                ]
            );

            // Update SEO Meta
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
        $this->db->beginTransaction();
        try {
            // Soft delete recursively all subcategories
            $this->recursiveSoftDelete($id);
            $this->db->commit();
            $this->clearCache();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function recursiveSoftDelete(int $id): void {
        $this->db->execute("UPDATE categories SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);
        
        // Find subcategories and delete
        $subs = $this->db->query("SELECT id FROM categories WHERE parent_id = :id AND deleted_at IS NULL", [':id' => $id]);
        foreach ($subs as $sub) {
            $this->recursiveSoftDelete((int)$sub['id']);
        }
    }

    public function getSeoMeta(int $categoryId): array {
        $sql = "SELECT * FROM seo_meta WHERE model_type = 'Category' AND model_id = :id LIMIT 1";
        $rows = $this->db->query($sql, [':id' => $categoryId]);
        return $rows[0] ?? [
            'title' => '', 'description' => '', 'keywords' => '',
            'canonical_url' => '', 'og_title' => '', 'og_description' => '', 'og_image' => '', 'robots' => 'index, follow'
        ];
    }

    private function updateSeoMeta(int $categoryId, array $seo): void {
        $exists = $this->db->query("SELECT id FROM seo_meta WHERE model_type = 'Category' AND model_id = :id LIMIT 1", [':id' => $categoryId]);
        $params = [
            ':title' => trim($seo['title'] ?? ''),
            ':desc' => trim($seo['description'] ?? ''),
            ':keywords' => trim($seo['keywords'] ?? ''),
            ':canonical' => trim($seo['canonical_url'] ?? ''),
            ':og_title' => trim($seo['og_title'] ?? ''),
            ':og_desc' => trim($seo['og_description'] ?? ''),
            ':og_image' => trim($seo['og_image'] ?? ''),
            ':robots' => trim($seo['robots'] ?? 'index, follow'),
            ':id' => $categoryId
        ];

        if (!empty($exists)) {
            $this->db->execute(
                "UPDATE seo_meta 
                 SET title = :title, description = :desc, keywords = :keywords, canonical_url = :canonical, 
                     og_title = :og_title, og_description = :og_desc, og_image = :og_image, robots = :robots 
                 WHERE model_type = 'Category' AND model_id = :id",
                $params
            );
        } else {
            $this->db->execute(
                "INSERT INTO seo_meta (model_type, model_id, title, description, keywords, canonical_url, og_title, og_description, og_image, robots) 
                 VALUES ('Category', :id, :title, :desc, :keywords, :canonical, :og_title, :og_desc, :og_image, :robots)",
                $params
            );
        }
    }

    private function generateUniqueSlug(string $name, ?int $excludeId = null): string {
        $slug = $this->slugify($name);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $sql = "SELECT id FROM categories WHERE slug = :slug AND deleted_at IS NULL";
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
