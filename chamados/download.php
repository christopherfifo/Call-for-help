<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

$pdo = getConnection();
$anexo_id = $_GET['id'] ?? null;

if (!$anexo_id) {
    die("Anexo não encontrado.");
}

$stmt = $pdo->prepare("SELECT a.*, c.usuario_id as chamado_usuario_id, c.tecnico_id as chamado_tecnico_id 
                       FROM anexos a
                       JOIN chamados c ON a.chamado_id = c.id
                       WHERE a.id = ?");
$stmt->execute([$anexo_id]);
$anexo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anexo) {
    die("Anexo não encontrado.");
}

// Controle de acesso igual ao ver.php
$cargo = $_SESSION['usuario_cargo'];
$usuario_id = $_SESSION['usuario_id'];

$pode_baixar = false;
if ($cargo === 'USUARIO' && $anexo['chamado_usuario_id'] == $usuario_id) {
    $pode_baixar = true;
} elseif ($cargo === 'TECNICO' && ($anexo['chamado_tecnico_id'] == $usuario_id || $anexo['chamado_tecnico_id'] == null)) {
    $pode_baixar = true;
} elseif (in_array($cargo, ['ADMINISTRADOR', 'DEV'])) {
    $pode_baixar = true;
}

if (!$pode_baixar) {
    die("Você não tem permissão para baixar este anexo.");
}

$caminho_arquivo = __DIR__ . '/../uploads/' . $anexo['caminho'];

if (!file_exists($caminho_arquivo)) {
    die("O arquivo físico não foi encontrado no servidor.");
}

// Força o download
header('Content-Description: File Transfer');
header('Content-Type: ' . $anexo['tipo']);
header('Content-Disposition: attachment; filename="' . basename($anexo['nome_original']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($caminho_arquivo));

readfile($caminho_arquivo);
exit;
