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

if (!$chamado || ($chamado['status'] !== 'EM_ANDAMENTO' && $chamado['status'] !== 'PAUSADO')) {
    $_SESSION['msg_erro'] = "Apenas chamados em andamento ou pausados podem ser repassados.";
    redirect("/chamados/ver.php?id=$id");
}

// Busca outros técnicos
$stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE cargo IN ('TECNICO', 'ADMINISTRADOR', 'DEV') AND ativo = 1 AND id != ? ORDER BY nome ASC");
$stmt->execute([$_SESSION['usuario_id']]);
$tecnicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destino = $_POST['destino'] ?? ''; // 'fila' ou id do tecnico
    $justificativa = trim($_POST['justificativa'] ?? '');
    
    if (empty($destino) || empty($justificativa)) {
        $erro = "Selecione o destino e informe a justificativa.";
    } else {
        try {
            $pdo->beginTransaction();
            
            if ($destino === 'fila') {
                $novo_status = 'ABERTO';
                $tecnico_id = null;
                $tag_repassado = 1;
                $desc = "Devolvido para a fila. Justificativa: $justificativa";
            } else {
                $novo_status = 'EM_ANDAMENTO';
                $tecnico_id = (int)$destino;
                $tag_repassado = 0;
                
                // Busca nome do técnico para log
                $stmtNome = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
                $stmtNome->execute([$tecnico_id]);
                $novo_tec = $stmtNome->fetchColumn();
                
                $desc = "Repassado para $novo_tec. Justificativa: $justificativa";
            }
            
            $stmt = $pdo->prepare("
                UPDATE chamados 
                SET tecnico_id = ?, status = ?, tag_repassado = ? 
                WHERE id = ?
            ");
            $stmt->execute([$tecnico_id, $novo_status, $tag_repassado, $id]);
            
            $stmt = $pdo->prepare("
                INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_anterior, status_novo, descricao)
                VALUES (?, ?, 'REPASSAR', ?, ?, ?)
            ");
            $stmt->execute([$id, $_SESSION['usuario_id'], $chamado['status'], $novo_status, $desc]);
            
            $pdo->commit();
            
            $_SESSION['msg'] = "Chamado repassado com sucesso.";
            redirect("/chamados/ver.php?id=$id");
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao repassar: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Repassar Chamado: <?= htmlspecialchars($chamado['numero']) ?></h1>
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
                <label class="form-label fw-bold">Repassar para</label>
                <select class="form-select" name="destino" required>
                    <option value="">Selecione...</option>
                    <option value="fila">-- Devolver para a Fila (Ficará Aberto) --</option>
                    <?php foreach ($tecnicos as $tec): ?>
                        <option value="<?= $tec['id'] ?>"><?= htmlspecialchars($tec['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Justificativa do Repasse <span class="text-danger">*</span></label>
                <textarea class="form-control" name="justificativa" rows="3" required placeholder="Por que você está repassando este chamado?"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right-circle"></i> Confirmar Repasse</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
