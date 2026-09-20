<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkProfile(['TECNICO', 'ADMINISTRADOR', 'DEV']);

$pdo = getConnection();
$id = $_GET['id'] ?? ($_POST['id'] ?? null);

if (!$id) {
    redirect('/chamados/');
}

// Verifica se chamado existe e está ABERTO
$stmt = $pdo->prepare("SELECT id, numero, status, tecnico_id FROM chamados WHERE id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chamado) {
    redirect('/chamados/');
}

if ($chamado['status'] !== 'ABERTO') {
    $_SESSION['msg_erro'] = "Este chamado não está aberto para ser assumido.";
    redirect("/chamados/ver.php?id=$id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_prazo = $_POST['tipo_prazo'] ?? '';
    $prazo = $_POST['prazo'] ?? null;
    $justificativa_prazo = trim($_POST['justificativa_prazo'] ?? '');
    
    if (empty($justificativa_prazo)) {
        $erro = "A justificativa é obrigatória.";
    } elseif ($tipo_prazo === 'definido' && empty($prazo)) {
        $erro = "Informe a data e hora do prazo.";
    } else {
        $prazo_indeterminado = ($tipo_prazo === 'indeterminado') ? 1 : 0;
        $prazo_val = ($tipo_prazo === 'definido') ? $prazo : null;
        
        try {
            $pdo->beginTransaction();
            
            // Atualizar chamado
            $stmt = $pdo->prepare("
                UPDATE chamados 
                SET tecnico_id = ?, status = 'EM_ANDAMENTO', prazo = ?, prazo_indeterminado = ?, justificativa_prazo = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['usuario_id'], $prazo_val, $prazo_indeterminado, $justificativa_prazo, $id]);
            
            // Histórico
            $desc = "Técnico assumiu o chamado. ";
            if ($prazo_indeterminado) {
                $desc .= "Prazo: Indeterminado. Justificativa: " . $justificativa_prazo;
            } else {
                $desc .= "Prazo definido para: " . date('d/m/Y H:i', strtotime($prazo_val)) . ". Justificativa: " . $justificativa_prazo;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_anterior, status_novo, descricao)
                VALUES (?, ?, 'ASSUMIR', 'ABERTO', 'EM_ANDAMENTO', ?)
            ");
            $stmt->execute([$id, $_SESSION['usuario_id'], $desc]);
            
            $pdo->commit();
            
            $_SESSION['msg'] = "Chamado assumido com sucesso!";
            redirect("/chamados/ver.php?id=$id");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao assumir chamado: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Assumir Chamado: <?= htmlspecialchars($chamado['numero']) ?></h1>
    <a href="/chamados/ver.php?id=<?= $id ?>" class="btn btn-secondary">Voltar</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="alert alert-info">
            Ao assumir este chamado, ele passará para o status <strong>Em Andamento</strong> e ficará sob sua responsabilidade.
        </div>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="id" value="<?= $id ?>">
            
            <div class="mb-3">
                <label class="form-label fw-bold">Tipo de Prazo</label>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="tipo_prazo" id="prazo_definido" value="definido" checked onchange="togglePrazo()">
                    <label class="form-check-label" for="prazo_definido">Data e hora definida</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="tipo_prazo" id="prazo_indeterminado" value="indeterminado" onchange="togglePrazo()">
                    <label class="form-check-label" for="prazo_indeterminado">Prazo indeterminado</label>
                </div>
            </div>
            
            <div class="mb-3" id="div_data_prazo">
                <label class="form-label">Data e Hora do Prazo</label>
                <input type="datetime-local" class="form-control" name="prazo" id="input_prazo">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Justificativa do Prazo <span class="text-danger">*</span></label>
                <textarea class="form-control" name="justificativa_prazo" rows="3" required placeholder="Justifique o prazo definido ou o motivo de ser indeterminado..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2-square"></i> Confirmar e Assumir</button>
        </form>
    </div>
</div>

<script>
function togglePrazo() {
    const isIndeterminado = document.getElementById('prazo_indeterminado').checked;
    const divPrazo = document.getElementById('div_data_prazo');
    const inputPrazo = document.getElementById('input_prazo');
    
    if (isIndeterminado) {
        divPrazo.style.display = 'none';
        inputPrazo.removeAttribute('required');
    } else {
        divPrazo.style.display = 'block';
        inputPrazo.setAttribute('required', 'required');
    }
}
// Init
togglePrazo();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
