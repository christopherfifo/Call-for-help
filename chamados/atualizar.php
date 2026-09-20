<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/chamados/');
}

$pdo = getConnection();
$id = $_POST['id'] ?? null;
$comentario = trim($_POST['comentario'] ?? '');

if (!$id || empty($comentario)) {
    redirect('/chamados/');
}

// Verifica se o chamado existe e não está finalizado ou cancelado
$stmt = $pdo->prepare("SELECT id, status, usuario_id, tecnico_id FROM chamados WHERE id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chamado || in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])) {
    $_SESSION['msg_erro'] = "Não é possível adicionar atualizações a este chamado.";
    redirect("/chamados/ver.php?id=$id");
}

// Verifica permissões (Dono, Técnico responsável ou Admin/DEV)
$cargo = $_SESSION['usuario_cargo'];
$usuario_id = $_SESSION['usuario_id'];

$pode_atualizar = false;
if ($cargo === 'USUARIO' && $chamado['usuario_id'] == $usuario_id) {
    $pode_atualizar = true;
} elseif ($cargo === 'TECNICO' && ($chamado['tecnico_id'] == $usuario_id || $chamado['tecnico_id'] == null)) {
    // Um técnico pode atualizar se for o responsável ou se estiver na fila (embora idealmente devesse assumir primeiro)
    $pode_atualizar = true;
} elseif (in_array($cargo, ['ADMINISTRADOR', 'DEV'])) {
    $pode_atualizar = true;
}

if (!$pode_atualizar) {
    $_SESSION['msg_erro'] = "Você não tem permissão para atualizar este chamado.";
    redirect("/chamados/ver.php?id=$id");
}

try {
    $pdo->beginTransaction();
    
    // Insere o comentário
    $stmt = $pdo->prepare("
        INSERT INTO comentarios_chamados (chamado_id, usuario_id, comentario)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$id, $usuario_id, $comentario]);
    
    // Atualiza a data de última atualização do chamado (opcional, mas recomendado)
    $stmt = $pdo->prepare("UPDATE chamados SET atualizado_em = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    // Insere no histórico
    $stmt = $pdo->prepare("
        INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_anterior, status_novo, descricao)
        VALUES (?, ?, 'ATUALIZAR', ?, ?, ?)
    ");
    $desc = "Adicionou uma nova atualização/comentário.";
    $stmt->execute([$id, $usuario_id, $chamado['status'], $chamado['status'], $desc]);
    
    $pdo->commit();
    
    $_SESSION['msg'] = "Atualização adicionada com sucesso.";
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['msg_erro'] = "Erro ao adicionar atualização: " . $e->getMessage();
}

redirect("/chamados/ver.php?id=$id");
