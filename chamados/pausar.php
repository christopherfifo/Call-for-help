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

if (!$chamado || $chamado['status'] !== 'EM_ANDAMENTO') {
    $_SESSION['msg_erro'] = "Apenas chamados em andamento podem ser pausados.";
    redirect("/chamados/ver.php?id=$id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo = trim($_POST['motivo'] ?? '');
    
    if (empty($motivo)) {
        $erro = "O motivo da pausa é obrigatório.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE chamados SET status = 'PAUSADO' WHERE id = ?");
            $stmt->execute([$id]);
            
            $stmt = $pdo->prepare("
                INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_anterior, status_novo, descricao)
                VALUES (?, ?, 'PAUSAR', 'EM_ANDAMENTO', 'PAUSADO', ?)
            ");
            $stmt->execute([$id, $_SESSION['usuario_id'], "Pausa: " . $motivo]);
            
            $pdo->commit();
            
            $_SESSION['msg'] = "Chamado pausado com sucesso.";
            redirect("/chamados/ver.php?id=$id");
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao pausar: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Pausar Chamado: <?= htmlspecialchars($chamado['numero']) ?></h1>
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
                <label class="form-label">Motivo da Pausa <span class="text-danger">*</span></label>
                <textarea class="form-control" name="motivo" rows="3" required placeholder="Ex: Aguardando peça para substituição..."></textarea>
            </div>
            <button type="submit" class="btn btn-warning text-dark"><i class="bi bi-pause-circle"></i> Confirmar Pausa</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
