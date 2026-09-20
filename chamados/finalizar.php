<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkProfile(['TECNICO', 'ADMINISTRADOR', 'DEV']);

$pdo = getConnection();
$id = $_GET['id'] ?? ($_POST['id'] ?? null);

if (!$id) redirect('/chamados/');

$stmt = $pdo->prepare("SELECT id, numero, status FROM chamados WHERE id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chamado || $chamado['status'] !== 'EM_ANDAMENTO') {
    $_SESSION['msg_erro'] = "Apenas chamados em andamento podem ser finalizados.";
    redirect("/chamados/ver.php?id=$id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $relatorio = trim($_POST['relatorio'] ?? '');
    
    if (empty($relatorio)) {
        $erro = "O relatório final é obrigatório.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $agora = date('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("
                UPDATE chamados 
                SET status = 'FINALIZADO', relatorio_final = ?, finalizado_em = ?
                WHERE id = ?
            ");
            $stmt->execute([$relatorio, $agora, $id]);
            
            $stmt = $pdo->prepare("
                INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_anterior, status_novo, descricao)
                VALUES (?, ?, 'FINALIZAR', 'EM_ANDAMENTO', 'FINALIZADO', 'Chamado finalizado pelo técnico.')
            ");
            $stmt->execute([$id, $_SESSION['usuario_id']]);
            
            $pdo->commit();
            
            $_SESSION['msg'] = "Chamado finalizado com sucesso.";
            redirect("/chamados/ver.php?id=$id");
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao finalizar: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Finalizar Chamado: <?= htmlspecialchars($chamado['numero']) ?></h1>
    <a href="/chamados/ver.php?id=<?= $id ?>" class="btn btn-secondary">Voltar</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="id" value="<?= $id ?>">
            
            <div class="mb-3">
                <label class="form-label">Relatório Final <span class="text-danger">*</span></label>
                <textarea class="form-control" name="relatorio" rows="6" required placeholder="Descreva detalhadamente a solução aplicada ao problema..."></textarea>
                <div class="form-text">O relatório é obrigatório para encerrar o chamado.</div>
            </div>
            
            <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Confirmar Finalização</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
