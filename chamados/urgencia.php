<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

$pdo = getConnection();
$id = $_GET['id'] ?? ($_POST['id'] ?? null);

if (!$id) redirect('/chamados/');

$stmt = $pdo->prepare("SELECT id, numero, status, urgencia FROM chamados WHERE id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chamado || in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])) {
    $_SESSION['msg_erro'] = "Não é possível alterar a urgência de um chamado finalizado ou cancelado.";
    redirect("/chamados/ver.php?id=$id");
}

$cargo = $_SESSION['usuario_cargo'];
if (!in_array($cargo, ['ADMINISTRADOR', 'DEV'])) {
    $_SESSION['msg_erro'] = "Apenas Administradores podem alterar a urgência de chamados.";
    redirect("/chamados/ver.php?id=$id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_urgencia = $_POST['urgencia'] ?? '';
    $justificativa = trim($_POST['justificativa'] ?? '');
    
    if (empty($nova_urgencia) || !in_array($nova_urgencia, ['LEVE', 'MODERADA', 'ALTA'])) {
        $erro = "Selecione uma urgência válida.";
    } elseif (empty($justificativa)) {
        $erro = "A justificativa é obrigatória.";
    } elseif ($nova_urgencia === $chamado['urgencia']) {
        $erro = "A nova urgência deve ser diferente da atual.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                UPDATE chamados 
                SET urgencia = ?, justificativa_urgencia = CONCAT(justificativa_urgencia, '\n\nNova justificativa: ', ?)
                WHERE id = ?
            ");
            $stmt->execute([$nova_urgencia, $justificativa, $id]);
            
            $desc = "Urgência alterada de {$chamado['urgencia']} para {$nova_urgencia}. Justificativa: " . $justificativa;
            $stmt = $pdo->prepare("
                INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_anterior, status_novo, descricao)
                VALUES (?, ?, 'ALTERAR_URGENCIA', ?, ?, ?)
            ");
            $stmt->execute([$id, $_SESSION['usuario_id'], $chamado['status'], $chamado['status'], $desc]);
            
            $pdo->commit();
            
            $_SESSION['msg'] = "Urgência alterada com sucesso.";
            redirect("/chamados/ver.php?id=$id");
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao alterar urgência: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Alterar Urgência: <?= htmlspecialchars($chamado['numero']) ?></h1>
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
                <label class="form-label">Urgência Atual</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($chamado['urgencia']) ?>" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label">Nova Urgência <span class="text-danger">*</span></label>
                <select name="urgencia" class="form-select" required>
                    <option value="">Selecione...</option>
                    <option value="LEVE" <?= (isset($nova_urgencia) && $nova_urgencia === 'LEVE') ? 'selected' : '' ?>>Leve</option>
                    <option value="MODERADA" <?= (isset($nova_urgencia) && $nova_urgencia === 'MODERADA') ? 'selected' : '' ?>>Moderada</option>
                    <option value="ALTA" <?= (isset($nova_urgencia) && $nova_urgencia === 'ALTA') ? 'selected' : '' ?>>Alta</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Justificativa da Alteração <span class="text-danger">*</span></label>
                <textarea class="form-control" name="justificativa" rows="3" required placeholder="Por que a urgência está sendo alterada?"><?= isset($_POST['justificativa']) ? htmlspecialchars($_POST['justificativa']) : '' ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-info text-white"><i class="bi bi-exclamation-triangle"></i> Confirmar Alteração</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
