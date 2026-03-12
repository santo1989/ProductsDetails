<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$scope = isset($_GET['scope']) ? strtolower(trim($_GET['scope'])) : 'all';

$query = "SELECT COALESCE(UNIX_TIMESTAMP(MAX(COALESCE(Updated_At, Created_At))), 0) AS last_update, COUNT(*) AS total FROM products";
$params = [];
$types = '';

if ($scope === 'own') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'ok' => false,
            'error' => 'Unauthorized',
        ]);
        $conn->close();
        exit;
    }

    $query .= " WHERE Created_By = ?";
    $types = 'i';
    $params[] = (int) $_SESSION['user_id'];
}

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode([
        'ok' => false,
        'error' => 'Query prepare failed',
    ]);
    $conn->close();
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;

echo json_encode([
    'ok' => true,
    'last_update' => isset($row['last_update']) ? (int) $row['last_update'] : 0,
    'total' => isset($row['total']) ? (int) $row['total'] : 0,
    'scope' => $scope,
]);

$stmt->close();
$conn->close();
