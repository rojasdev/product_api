<?php
/**
 * index.php
 * Simple REST API for the `products` resource.
 *
 * Routes (assuming this file is served at /api/index.php,
 * or rewritten to /api/ via .htaccess):
 *
 *   GET    /api/products            -> list products (supports ?category=&is_active=)
 *   GET    /api/products/?id={id}       -> get one product
 *   POST   /api/products            -> create a product   (JSON body)
 *   PUT    /api/products/?id={id}       -> update a product   (JSON body, partial allowed)
 *   DELETE /api/products/?id={id}       -> delete a product
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Product.php';

$db = getDbConnection();
$product = new Product($db);

function sendJson($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJson(['error' => 'Invalid JSON body'], 400);
    }
    return $data ?? [];
}

/** Basic validation for create requests. */
function validateCreate(array $data): array
{
    $errors = [];
    foreach (['sku', 'name', 'category', 'price'] as $required) {
        if (empty($data[$required]) && $data[$required] !== '0') {
            $errors[] = "Field '{$required}' is required.";
        }
    }
    if (isset($data['price']) && !is_numeric($data['price'])) {
        $errors[] = "Field 'price' must be numeric.";
    }
    if (isset($data['stock_quantity']) && !is_numeric($data['stock_quantity'])) {
        $errors[] = "Field 'stock_quantity' must be numeric.";
    }
    return $errors;
}

// --------------------------------------------------------------
// Routing: parse path like /products or /products/5
// --------------------------------------------------------------
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path   = trim(str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $path), '/');
$parts  = $path === '' ? [] : explode('/', $path);

// Expect first segment to be "products"
if (($parts[0] ?? '') !== 'products') {
    sendJson(['error' => 'Not found. Try /products or /products/{id}'], 404);
}

$id     = isset($parts[1]) ? (int) $parts[1] : null;
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        if ($id !== null) {
            $row = $product->getById($id);
            if (!$row) {
                sendJson(['error' => "Product {$id} not found"], 404);
            }
            sendJson($row);
        } else {
            $filters = [];
            if (!empty($_GET['category'])) {
                $filters['category'] = $_GET['category'];
            }
            if (isset($_GET['is_active'])) {
                $filters['is_active'] = $_GET['is_active'];
            }
            sendJson($product->getAll($filters));
        }
        break;

    case 'POST':
        $data = readJsonBody();
        $errors = validateCreate($data);
        if ($errors) {
            sendJson(['error' => 'Validation failed', 'details' => $errors], 422);
        }
        if ($product->skuExists($data['sku'])) {
            sendJson(['error' => "SKU '{$data['sku']}' already exists"], 409);
        }
        $newId = $product->create($data);
        sendJson($product->getById($newId), 201);
        break;

    case 'PUT':
        if ($id === null) {
            sendJson(['error' => 'Product id is required for update'], 400);
        }
        if (!$product->getById($id)) {
            sendJson(['error' => "Product {$id} not found"], 404);
        }
        $data = readJsonBody();
        if (isset($data['sku']) && $product->skuExists($data['sku'], $id)) {
            sendJson(['error' => "SKU '{$data['sku']}' already exists"], 409);
        }
        $product->update($id, $data);
        sendJson($product->getById($id));
        break;

    case 'DELETE':
        if ($id === null) {
            sendJson(['error' => 'Product id is required for delete'], 400);
        }
        $deleted = $product->delete($id);
        if (!$deleted) {
            sendJson(['error' => "Product {$id} not found"], 404);
        }
        sendJson(['message' => "Product {$id} deleted"]);
        break;

    default:
        sendJson(['error' => 'Method not allowed'], 405);
}
