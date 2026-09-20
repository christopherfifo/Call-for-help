<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/chamados/');
}

$pdo = getConnection();
$id = $_POST['id'] ?? null;
$motivo = trim($_POST['motivo'] ?? '');
$observacao = trim($_POST['observacao'] ?? '');

$usuario_id = $_SESSION['usuario_id'];
$cargo = $_SESSION['usuario_cargo'];

if (!$id || empty($motivo)) {
    $_SESSION['msg_erro'] = "O motivo é obrigatório para solicitar o relatório.";
    redirect("/chamados/ver.php?id=$id");
}

$stmt = $pdo->prepare("SELECT id, status, usuario_id, tecnico_id FROM chamados WHERE id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chamado) {
    redirect('/chamados/');
}

$pode_solicitar = false;
if ($cargo === 'USUARIO' && $chamado['usuario_id'] == $usuario_id) {
    $pode_solicitar = true;
} elseif ($cargo === 'TECNICO' && ($chamado['tecnico_id'] == $usuario_id || $chamado['tecnico_id'] == null)) {
    $pode_solicitar = true;
} elseif (in_array($cargo, ['ADMINISTRADOR', 'DEV'])) {
    $pode_solicitar = true;
}

if (!$pode_solicitar) {
    $_SESSION['msg_erro'] = "Você não tem permissão para solicitar relatório deste chamado.";
    redirect("/chamados/ver.php?id=$id");
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO solicitacoes_relatorio (chamado_id, solicitante_id, motivo, observacao, status)
        VALUES (?, ?, ?, ?, 'PENDENTE')
    ");
    $stmt->execute([$id, $usuario_id, $motivo, $observacao]);
    
    $stmt = $pdo->prepare("
        INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_anterior, status_novo, descricao)
        VALUES (?, ?, 'SOLICITAR_RELATORIO', ?, ?, ?)
    ");
    $desc = "Solicitou um relatório. Motivo: " . $motivo;
    $stmt->execute([$id, $usuario_id, $chamado['status'], $chamado['status'], $desc]);
    
    $pdo->commit();
    
    $_SESSION['msg'] = "Solicitação de relatório enviada com sucesso.";
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['msg_erro'] = "Erro ao solicitar relatório: " . $e->getMessage();
}

redirect("/chamados/ver.php?id=$id");
