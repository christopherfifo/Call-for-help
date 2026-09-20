<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit;
}

$pdo = getConnection();
$usuario_id = $_SESSION['usuario_id'];

if (isset($_GET['ler'])) {
    $id = (int)$_GET['ler'];
    $stmt = $pdo->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    echo json_encode(['status' => 'ok']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM notificacoes WHERE usuario_id = ? ORDER BY criado_em DESC LIMIT 20");
$stmt->execute([$usuario_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));