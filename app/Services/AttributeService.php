<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AttributeRepository;
use App\Services\AuditLogger;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;
use Core\Validation\Validator;
use Exception;

class AttributeService {
    private AttributeRepository $repository;
    private DatabaseInterface $db;
    private CacheInterface $cache;
    private AuditLogger $auditLogger;

    private const CACHE_KEY = 'attributes_list';
    private const SETS_CACHE_KEY = 'attribute_sets_list';

    public function __construct(
        AttributeRepository $repository,
        DatabaseInterface $db,
        CacheInterface $cache,
        AuditLogger $auditLogger
    ) {
        $this->repository = $repository;
        $this->db = $db;
        $this->cache = $cache;
        $this->auditLogger = $auditLogger;
    }

    public function getAllCached(array $filters = []): array {
        if (!empty($filters['q']) || !empty($filters['type'])) {
            return $this->repository->getAll($filters);
        }

        if ($this->cache->has(self::CACHE_KEY)) {
            return $this->cache->get(self::CACHE_KEY);
        }

        $attributes = $this->repository->getAll();
        $this->cache->set(self::CACHE_KEY, $attributes, 3600);
        return $attributes;
    }

    public function create(array $data): int {
        $validator = Validator::make($data, [
            'code' => 'required|unique:attributes,code',
            'type' => 'required',
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()));
            throw new Exception(implode(' ', $errors));
        }

        $code = $this->slugify($data['code']);
        $type = trim($data['type']);
        $name = trim($data['name']);

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO attributes (code, type) VALUES (:code, :type)",
                [':code' => $code, ':type' => $type]
            );
            $attributeId = (int)$this->db->lastInsertId();

            // Insert default language translation (language_id = 1)
            $this->db->execute(
                "INSERT INTO attribute_translations (attribute_id, language_id, name) VALUES (:aid, 1, :name)",
                [':aid' => $attributeId, ':name' => $name]
            );

            // Multilingual translations if provided E.g. translations[2][name] = "Color"
            if (!empty($data['translations'])) {
                foreach ($data['translations'] as $langId => $trans) {
                    if ((int)$langId === 1 || empty($trans['name'])) continue;
                    $this->db->execute(
                        "INSERT INTO attribute_translations (attribute_id, language_id, name) VALUES (:aid, :lang, :name)
                         ON DUPLICATE KEY UPDATE name = VALUES(name)",
                        [':aid' => $attributeId, ':lang' => (int)$langId, ':name' => trim($trans['name'])]
                    );
                }
            }

            // Option values (e.g. Color values, select options, etc.)
            if (!empty($data['values'])) {
                foreach ($data['values'] as $valData) {
                    if (empty($valData['name'])) continue;
                    $valName = trim($valData['name']);
                    $valCode = !empty($valData['code']) ? $this->slugify($valData['code']) : $this->slugify($valName);

                    $this->db->execute(
                        "INSERT INTO attribute_values (attribute_id, code) VALUES (:aid, :code)",
                        [':aid' => $attributeId, ':code' => $valCode]
                    );
                    $valId = (int)$this->db->lastInsertId();

                    $this->db->execute(
                        "INSERT INTO attribute_value_translations (attribute_value_id, language_id, name) VALUES (:vid, 1, :name)",
                        [':vid' => $valId, ':name' => $valName]
                    );

                    if (!empty($valData['translations'])) {
                        foreach ($valData['translations'] as $langId => $vTrans) {
                            if ((int)$langId === 1 || empty($vTrans['name'])) continue;
                            $this->db->execute(
                                "INSERT INTO attribute_value_translations (attribute_value_id, language_id, name) 
                                 VALUES (:vid, :lang, :name) ON DUPLICATE KEY UPDATE name = VALUES(name)",
                                [':vid' => $valId, ':lang' => (int)$langId, ':name' => trim($vTrans['name'])]
                            );
                        }
                    }
                }
            }

            // Logs
            $this->auditLogger->logActivity('attribute_create', "Yeni özellik oluşturuldu: {$name} (Kod: {$code})");
            $this->auditLogger->logAudit('create', 'Attribute', $attributeId, null, $data);

            $this->db->commit();
            $this->clearCache();
            return $attributeId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): void {
        $validator = Validator::make($data, [
            'code' => "required|unique:attributes,code,{$id},id",
            'type' => 'required',
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()));
            throw new Exception(implode(' ', $errors));
        }

        $current = $this->repository->getById($id);
        if (!$current) {
            throw new Exception("Özellik bulunamadı.");
        }

        $code = $this->slugify($data['code']);
        $type = trim($data['type']);
        $name = trim($data['name']);

        $this->db->beginTransaction();
        try {
            // Update attributes main table
            $this->db->execute(
                "UPDATE attributes SET code = :code, type = :type, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                [':code' => $code, ':type' => $type, ':id' => $id]
            );

            // Update main translation
            $this->db->execute(
                "INSERT INTO attribute_translations (attribute_id, language_id, name) VALUES (:aid, 1, :name)
                 ON DUPLICATE KEY UPDATE name = VALUES(name)",
                [':aid' => $id, ':name' => $name]
            );

            // Multilingual translations
            if (!empty($data['translations'])) {
                foreach ($data['translations'] as $langId => $trans) {
                    if ((int)$langId === 1 || empty($trans['name'])) continue;
                    $this->db->execute(
                        "INSERT INTO attribute_translations (attribute_id, language_id, name) VALUES (:aid, :lang, :name)
                         ON DUPLICATE KEY UPDATE name = VALUES(name)",
                        [':aid' => $id, ':lang' => (int)$langId, ':name' => trim($trans['name'])]
                    );
                }
            }

            // Sync option values: Update existing, insert new, delete missing
            $keepValueIds = [];
            if (!empty($data['values'])) {
                foreach ($data['values'] as $valData) {
                    $valName = trim($valData['name'] ?? '');
                    if ($valName === '') continue;

                    $valId = !empty($valData['id']) ? (int)$valData['id'] : null;
                    $valCode = !empty($valData['code']) ? $this->slugify($valData['code']) : $this->slugify($valName);

                    if ($valId) {
                        // Update existing value
                        $this->db->execute(
                            "UPDATE attribute_values SET code = :code, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                            [':code' => $valCode, ':id' => $valId]
                        );

                        $this->db->execute(
                            "INSERT INTO attribute_value_translations (attribute_value_id, language_id, name) VALUES (:vid, 1, :name)
                             ON DUPLICATE KEY UPDATE name = VALUES(name)",
                            [':vid' => $valId, ':name' => $valName]
                        );
                        $keepValueIds[] = $valId;
                    } else {
                        // Insert new value
                        $this->db->execute(
                            "INSERT INTO attribute_values (attribute_id, code) VALUES (:aid, :code)",
                            [':aid' => $id, ':code' => $valCode]
                        );
                        $valId = (int)$this->db->lastInsertId();

                        $this->db->execute(
                            "INSERT INTO attribute_value_translations (attribute_value_id, language_id, name) VALUES (:vid, 1, :name)",
                            [':vid' => $valId, ':name' => $valName]
                        );
                        $keepValueIds[] = $valId;
                    }

                    // Value translations
                    if (!empty($valData['translations'])) {
                        foreach ($valData['translations'] as $langId => $vTrans) {
                            if ((int)$langId === 1 || empty($vTrans['name'])) continue;
                            $this->db->execute(
                                "INSERT INTO attribute_value_translations (attribute_value_id, language_id, name) 
                                 VALUES (:vid, :lang, :name) ON DUPLICATE KEY UPDATE name = VALUES(name)",
                                [':vid' => $valId, ':lang' => (int)$langId, ':name' => trim($vTrans['name'])]
                            );
                        }
                    }
                }
            }

            // Delete missing values
            if (!empty($keepValueIds)) {
                $inQuery = implode(',', $keepValueIds);
                $this->db->execute(
                    "DELETE FROM attribute_values WHERE attribute_id = :aid AND id NOT IN ({$inQuery})",
                    [':aid' => $id]
                );
            } else {
                $this->db->execute("DELETE FROM attribute_values WHERE attribute_id = :aid", [':aid' => $id]);
            }

            // Logs
            $this->auditLogger->logActivity('attribute_update', "Özellik güncellendi: {$name} (Kod: {$code})");
            $this->auditLogger->logAudit('update', 'Attribute', $id, $current, $data);

            $this->db->commit();
            $this->clearCache();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void {
        $current = $this->repository->getById($id);
        if (!$current) {
            throw new Exception("Özellik bulunamadı.");
        }

        $this->db->beginTransaction();
        try {
            // Soft delete attributes
            $this->db->execute(
                "UPDATE attributes SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id",
                [':id' => $id]
            );

            // Logs
            $this->auditLogger->logActivity('attribute_delete', "Özellik silindi: {$current['name']} (Kod: {$current['code']})");
            $this->auditLogger->logAudit('delete', 'Attribute', $id, $current, null);

            $this->db->commit();
            $this->clearCache();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // --- Attribute Sets Services ---
    public function createSet(array $data): int {
        $validator = Validator::make($data, [
            'code' => 'required|unique:product_attribute_sets,code',
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()));
            throw new Exception(implode(' ', $errors));
        }

        $code = $this->slugify($data['code']);
        $name = trim($data['name']);

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO product_attribute_sets (code) VALUES (:code)",
                [':code' => $code]
            );
            $setId = (int)$this->db->lastInsertId();

            $this->db->execute(
                "INSERT INTO product_attribute_set_translations (set_id, language_id, name) VALUES (:sid, 1, :name)",
                [':sid' => $setId, ':name' => $name]
            );

            if (!empty($data['translations'])) {
                foreach ($data['translations'] as $langId => $trans) {
                    if ((int)$langId === 1 || empty($trans['name'])) continue;
                    $this->db->execute(
                        "INSERT INTO product_attribute_set_translations (set_id, language_id, name) 
                         VALUES (:sid, :lang, :name) ON DUPLICATE KEY UPDATE name = VALUES(name)",
                        [':sid' => $setId, ':lang' => (int)$langId, ':name' => trim($trans['name'])]
                    );
                }
            }

            if (!empty($data['attribute_ids'])) {
                foreach ($data['attribute_ids'] as $attrId) {
                    $this->db->execute(
                        "INSERT INTO product_attribute_set_items (set_id, attribute_id) VALUES (:sid, :aid)",
                        [':sid' => $setId, ':aid' => (int)$attrId]
                    );
                }
            }

            $this->auditLogger->logActivity('attribute_set_create', "Yeni özellik grubu oluşturuldu: {$name}");
            $this->auditLogger->logAudit('create', 'AttributeSet', $setId, null, $data);

            $this->db->commit();
            $this->clearCache();
            return $setId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateSet(int $id, array $data): void {
        $validator = Validator::make($data, [
            'code' => "required|unique:product_attribute_sets,code,{$id},id",
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()));
            throw new Exception(implode(' ', $errors));
        }

        $current = $this->repository->getSetById($id);
        if (!$current) {
            throw new Exception("Özellik grubu bulunamadı.");
        }

        $code = $this->slugify($data['code']);
        $name = trim($data['name']);

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE product_attribute_sets SET code = :code, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                [':code' => $code, ':id' => $id]
            );

            $this->db->execute(
                "INSERT INTO product_attribute_set_translations (set_id, language_id, name) VALUES (:sid, 1, :name)
                 ON DUPLICATE KEY UPDATE name = VALUES(name)",
                [':sid' => $id, ':name' => $name]
            );

            if (!empty($data['translations'])) {
                foreach ($data['translations'] as $langId => $trans) {
                    if ((int)$langId === 1 || empty($trans['name'])) continue;
                    $this->db->execute(
                        "INSERT INTO product_attribute_set_translations (set_id, language_id, name) 
                         VALUES (:sid, :lang, :name) ON DUPLICATE KEY UPDATE name = VALUES(name)",
                        [':sid' => $id, ':lang' => (int)$langId, ':name' => trim($trans['name'])]
                    );
                }
            }

            // Sync set attributes
            $this->db->execute("DELETE FROM product_attribute_set_items WHERE set_id = :sid", [':sid' => $id]);
            if (!empty($data['attribute_ids'])) {
                foreach ($data['attribute_ids'] as $attrId) {
                    $this->db->execute(
                        "INSERT INTO product_attribute_set_items (set_id, attribute_id) VALUES (:sid, :aid)",
                        [':sid' => $id, ':aid' => (int)$attrId]
                    );
                }
            }

            $this->auditLogger->logActivity('attribute_set_update', "Özellik grubu güncellendi: {$name}");
            $this->auditLogger->logAudit('update', 'AttributeSet', $id, $current, $data);

            $this->db->commit();
            $this->clearCache();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteSet(int $id): void {
        $current = $this->repository->getSetById($id);
        if (!$current) {
            throw new Exception("Özellik grubu bulunamadı.");
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE product_attribute_sets SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id",
                [':id' => $id]
            );

            $this->auditLogger->logActivity('attribute_set_delete', "Özellik grubu silindi: {$current['name']}");
            $this->auditLogger->logAudit('delete', 'AttributeSet', $id, $current, null);

            $this->db->commit();
            $this->clearCache();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // --- Helpers ---
    private function slugify(string $text): string {
        $find = ['ç', 'ğ', 'ı', 'İ', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'Ö', 'Ş', 'Ü'];
        $replace = ['c', 'g', 'i', 'i', 'o', 's', 'u', 'c', 'g', 'o', 's', 'u'];
        $text = str_replace($find, $replace, $text);
        $text = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return strtolower(trim($text, '-'));
    }

    private function clearCache(): void {
        $this->cache->delete(self::CACHE_KEY);
        $this->cache->delete(self::SETS_CACHE_KEY);
    }
}
