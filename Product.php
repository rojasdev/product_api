<?php
/**
 * Product.php
 * Data-access layer for the `products` table.
 */

class Product
{
    private PDO $db;
    private string $table = 'products';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Return all products, optionally filtered by category or active state. */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = :is_active";
            $params[':is_active'] = (int) $filters['is_active'];
        }

        $sql .= " ORDER BY id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Return a single product by id, or null if not found. */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Create a product. Returns the new row's id. */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table}
                    (sku, name, description, category, price, stock_quantity, is_active)
                VALUES
                    (:sku, :name, :description, :category, :price, :stock_quantity, :is_active)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':sku'            => $data['sku'],
            ':name'           => $data['name'],
            ':description'    => $data['description']    ?? null,
            ':category'       => $data['category'],
            ':price'          => $data['price'],
            ':stock_quantity' => $data['stock_quantity']  ?? 0,
            ':is_active'      => $data['is_active']       ?? 1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Update a product. Only provided fields are updated. Returns true if a row changed. */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['sku', 'name', 'description', 'category', 'price', 'stock_quantity', 'is_active'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false; // nothing to update
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    /** Delete a product. Returns true if a row was removed. */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** Check whether a SKU already exists (used for create/update validation). */
    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE sku = :sku";
        $params = [':sku' => $sku];

        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
