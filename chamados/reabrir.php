<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

$pdo = getConnection();
$id = $_GET['id'] ?? ($_POST['id'] ?? null);

if (!$id) redirect('/chamados/');

$stmt = $pdo->prepare("SELECT id, numero, status FROM chamados WHERE id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chamado || $chamado['status'] !== 'FINALIZADO') {
    $_SESSION['msg_erro'] = "Este chamado não pode ser reaberto no status atual.";
    redirect("/chamados/ver.php?id=$id");
}

$cargo = $_SESSION['usuario_cargo'];
if (!in_array($cargo, ['ADMINISTRADOR', 'DEV'])) {
    $_SESSION['msg_erro'] = "Apenas Administradores podem reabrir chamados.";
    redirect("/chamados/ver.php?id=$id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $justificativa = trim($_POST['justificativa'] ?? '');
    
    if (empty($justificativa)) {
        $erro = "A justificativa é obrigatória.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                UPDATE chamados 
                SET status = 'EM_ANDAMENTO', tag_reaberto = 1
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            $desc = "Chamado reaberto. Justificativa: " . $justificativa;
            $stmt = $pdo->prepare("
                INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_anterior, status_novo, descricao)
                VALUES (?, ?, 'REABRIR', 'FINALIZADO', 'EM_ANDAMENTO', ?)
            ");
            $stmt->execute([$id, $_SESSION['usuario_id'], $desc]);
            
            $pdo->commit();
            
            $_SESSION['msg'] = "Chamado reaberto com sucesso.";
            redirect("/chamados/ver.php?id=$id");
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao reabrir: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Reabrir Chamado: <?= htmlspecialchars($chamado['numero']) ?></h1>
    <a href="/chamados/ver.php?id=<?= $id ?>" class="btn btn-secondary">Voltar</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="alert alert-warning text-dark">
            <strong>Atenção:</strong> Esta ação retornará o chamado para a fila de atendimento (status EM ANDAMENTO) e a tag REABERTO será adicionada.
        </div>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="id" value="<?= $id ?>">
            
            <div class="mb-3">
                <label class="form-label">Justificativa da Reabertura <span class="text-danger">*</span></label>
                <textarea class="form-control" name="justificativa" rows="3" required placeholder="Por que o chamado está sendo reaberto? Exemplo: O problema voltou a ocorrer."></textarea>
            </div>
            
            <button type="submit" class="btn btn-warning text-dark"><i class="bi bi-arrow-counterclockwise"></i> Confirmar Reabertura</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
