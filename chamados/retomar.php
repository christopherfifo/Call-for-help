<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkProfile(['TECNICO', 'ADMINISTRADOR', 'DEV']);

$pdo = getConnection();
$id = $_GET['id'] ?? ($_POST['id'] ?? null);

if (!$id) redirect('/chamados/');

$stmt = $pdo->prepare("SELECT id, numero, status, tecnico_id FROM chamados WHERE id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chamado || $chamado['status'] !== 'PAUSADO') {
    $_SESSION['msg_erro'] = "Apenas chamados pausados podem ser retomados.";
    redirect("/chamados/ver.php?id=$id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE chamados SET status = 'EM_ANDAMENTO' WHERE id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("
            INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_anterior, status_novo, descricao)
            VALUES (?, ?, 'RETOMAR', 'PAUSADO', 'EM_ANDAMENTO', 'Atendimento retomado pelo técnico.')
        ");
        $stmt->execute([$id, $_SESSION['usuario_id']]);
        
        $pdo->commit();
        
        $_SESSION['msg'] = "Chamado retomado com sucesso.";
        redirect("/chamados/ver.php?id=$id");
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['msg_erro'] = "Erro ao retomar: " . $e->getMessage();
        redirect("/chamados/ver.php?id=$id");
    }
}
// Se não for POST, exibe confirmação rápida ou processa diretamente?
// Melhor exigir POST (através de um formulário ou form oculto) para segurança CSRF,
// mas para simplicidade na view ver.php criaremos um form inline.
?>
