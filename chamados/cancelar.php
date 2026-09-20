<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

$pdo = getConnection();
$id = $_GET['id'] ?? ($_POST['id'] ?? null);

if (!$id) redirect('/chamados/');

$stmt = $pdo->prepare("SELECT id, numero, status, usuario_id FROM chamados WHERE id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chamado || in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])) {
    $_SESSION['msg_erro'] = "Este chamado não pode ser cancelado no status atual.";
    redirect("/chamados/ver.php?id=$id");
}

$cargo = $_SESSION['usuario_cargo'];
$dono = ($chamado['usuario_id'] == $_SESSION['usuario_id']);
$admin = in_array($cargo, ['ADMINISTRADOR', 'DEV']);

if (!$dono && !$admin) {
    $_SESSION['msg_erro'] = "Apenas o criador do chamado ou um Administrador podem solicitar o cancelamento.";
    redirect("/chamados/ver.php?id=$id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $justificativa = trim($_POST['justificativa'] ?? '');
    
    if (empty($justificativa)) {
        $erro = "A justificativa é obrigatória.";
    } else {
        try {
            $pdo->beginTransaction();
            $ip = $_SERVER['REMOTE_ADDR'];
            
            // Criar pedido administrativo
            $stmt = $pdo->prepare("
                INSERT INTO pedidos_administrativos (tipo, usuario_id, referencia_id, justificativa, solicitante_ip)
                VALUES ('CANCELAMENTO_CHAMADO', ?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['usuario_id'], $id, $justificativa, $ip]);
            
            // Notificar admins
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cargo IN ('ADMINISTRADOR', 'DEV')");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($admins as $admin_id) {
                $stmt = $pdo->prepare("INSERT INTO notificacoes (usuario_id, tipo, mensagem, referencia_id, referencia_tipo) VALUES (?, 'PEDIDO_CANCELAMENTO', ?, ?, 'chamado')");
                $msg = "Usuário {$_SESSION['usuario_nome']} solicitou o cancelamento do chamado {$chamado['numero']}.";
                $stmt->execute([$admin_id, $msg, $id]);
            }
            
            // Log auditoria
            $stmt = $pdo->prepare("INSERT INTO logs_sistema (usuario_id, acao, descricao, ip) VALUES (?, 'SOLICITAR_CANCELAMENTO', ?, ?)");
            $stmt->execute([$_SESSION['usuario_id'], "Solicitou cancelamento do chamado $id ({$chamado['numero']})", $ip]);
            
            $pdo->commit();
            
            $_SESSION['msg'] = "Solicitação de cancelamento enviada aos administradores.";
            redirect("/chamados/ver.php?id=$id");
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao solicitar cancelamento: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Solicitar Cancelamento: <?= htmlspecialchars($chamado['numero']) ?></h1>
    <a href="/chamados/ver.php?id=<?= $id ?>" class="btn btn-secondary">Voltar</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="alert alert-warning">
            <strong>Atenção:</strong> O cancelamento requer aprovação de um administrador. Justifique detalhadamente abaixo.
        </div>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="id" value="<?= $id ?>">
            
            <div class="mb-3">
                <label class="form-label">Justificativa do Cancelamento <span class="text-danger">*</span></label>
                <textarea class="form-control" name="justificativa" rows="3" required placeholder="Por que o chamado deve ser cancelado?"></textarea>
            </div>
            
            <button type="submit" class="btn btn-warning"><i class="bi bi-shield-lock"></i> Solicitar Cancelamento</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>