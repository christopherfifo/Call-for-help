<?php
/**
 * chamados/urgencia.php
 *
 * Sistema de urgência com hierarquia:  Usuário → Técnico → Administrador/Dev
 *
 * - TECNICO    pode inserir/alterar/revogar SOMENTE seu próprio registro.
 * - ADMIN/DEV  podem inserir/alterar/revogar SOMENTE seu próprio registro.
 * - Ninguém pode alterar o registro de outro usuário.
 * - A urgência efetiva exibida no chamado é calculada por getUrgenciaEfetiva().
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

$cargo      = $_SESSION['usuario_cargo'];
$usuario_id = $_SESSION['usuario_id'];

// Somente Técnico, Administrador e Dev têm acesso
if (!in_array($cargo, ['TECNICO', 'ADMINISTRADOR', 'DEV'])) {
    $_SESSION['msg_erro'] = "Apenas Técnicos e Administradores podem intervir na urgência.";
    redirect('/chamados/');
}

$pdo = getConnection();
$id  = $_GET['id'] ?? ($_POST['id'] ?? null);

if (!$id) redirect('/chamados/');

// ── Carrega o chamado ──────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT id, numero, status, urgencia FROM chamados WHERE id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chamado || in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])) {
    $_SESSION['msg_erro'] = "Não é possível alterar a urgência de um chamado finalizado ou cancelado.";
    redirect("/chamados/ver.php?id=$id");
}

// ── Verifica se este usuário já tem um registro ativo neste chamado ────────
$stmt = $pdo->prepare("
    SELECT id, urgencia FROM urgencia_chamados
    WHERE chamado_id = ? AND usuario_id = ? AND ativo = 1
");
$stmt->execute([$id, $usuario_id]);
$registro_proprio = $stmt->fetch(PDO::FETCH_ASSOC);

// ── Ação: revogar registro próprio ────────────────────────────────────────
if (isset($_GET['revogar'])) {
    if (!$registro_proprio) {
        $_SESSION['msg_erro'] = "Você não possui intervenção ativa para revogar.";
        redirect("/chamados/ver.php?id=$id");
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE urgencia_chamados SET ativo = 0 WHERE id = ? AND usuario_id = ?
        ");
        $stmt->execute([$registro_proprio['id'], $usuario_id]);

        $urgencia_efetiva_pos = getUrgenciaEfetiva($pdo, $chamado);

        $desc = "Intervenção de urgência revogada por {$_SESSION['usuario_nome']} ($cargo). "
              . "Urgência efetiva passa a: {$urgencia_efetiva_pos}.";
        $stmt = $pdo->prepare("
            INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_anterior, status_novo, descricao, ip)
            VALUES (?, ?, 'REVOGAR_URGENCIA', ?, ?, ?, ?)
        ");
        $stmt->execute([$id, $usuario_id, $chamado['status'], $chamado['status'], $desc, $_SERVER['REMOTE_ADDR']]);

        $stmt = $pdo->prepare("
            INSERT INTO logs_sistema (usuario_id, acao, descricao, ip)
            VALUES (?, 'REVOGAR_URGENCIA', ?, ?)
        ");
        $stmt->execute([$usuario_id, "Revogou intervenção de urgência no chamado $id ({$chamado['numero']})", $_SERVER['REMOTE_ADDR']]);

        $pdo->commit();
        $_SESSION['msg'] = "Sua intervenção de urgência foi revogada.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['msg_erro'] = "Erro ao revogar: " . $e->getMessage();
    }

    redirect("/chamados/ver.php?id=$id");
}

// ── Ação: salvar nova intervenção / editar a própria ──────────────────────
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_urgencia    = $_POST['urgencia']      ?? '';
    $justificativa    = trim($_POST['justificativa'] ?? '');

    $urgencias_validas = ['LEVE', 'MODERADA', 'ALTA'];

    if (!in_array($nova_urgencia, $urgencias_validas)) {
        $erro = "Selecione uma urgência válida.";
    } elseif (empty($justificativa)) {
        $erro = "A justificativa é obrigatória.";
    } else {
        // Define hierarquia numérica do cargo atual
        $hierarquia_map = ['TECNICO' => 1, 'ADMINISTRADOR' => 2, 'DEV' => 3];
        $hierarquia     = $hierarquia_map[$cargo];

        $ip = $_SERVER['REMOTE_ADDR'];

        try {
            $pdo->beginTransaction();

            if ($registro_proprio) {
                // Edita o registro existente do próprio usuário
                $urgencia_anterior = $registro_proprio['urgencia'];

                $stmt = $pdo->prepare("
                    UPDATE urgencia_chamados
                    SET urgencia = ?, justificativa = ?, ip = ?, atualizado_em = NOW()
                    WHERE id = ? AND usuario_id = ?
                ");
                $stmt->execute([$nova_urgencia, $justificativa, $ip, $registro_proprio['id'], $usuario_id]);

                $acao = 'EDITAR_URGENCIA';
                $desc = "{$_SESSION['usuario_nome']} ($cargo) editou sua intervenção de urgência: "
                      . "{$urgencia_anterior} → {$nova_urgencia}. Justificativa: {$justificativa}";
            } else {
                // Cria novo registro de intervenção
                $urgencia_anterior = getUrgenciaEfetiva($pdo, $chamado);

                $stmt = $pdo->prepare("
                    INSERT INTO urgencia_chamados (chamado_id, usuario_id, cargo_autor, hierarquia, urgencia, justificativa, ativo, ip)
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?)
                ");
                $stmt->execute([$id, $usuario_id, $cargo, $hierarquia, $nova_urgencia, $justificativa, $ip]);

                $acao = 'ALTERAR_URGENCIA';
                $desc = "{$_SESSION['usuario_nome']} ($cargo) definiu urgência: "
                      . "{$urgencia_anterior} → {$nova_urgencia}. Justificativa: {$justificativa}";
            }

            // Histórico do chamado
            $stmt = $pdo->prepare("
                INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_anterior, status_novo, descricao, ip)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id, $usuario_id, $acao, $chamado['status'], $chamado['status'], $desc, $ip]);

            // Log de auditoria
            $stmt = $pdo->prepare("
                INSERT INTO logs_sistema (usuario_id, acao, descricao, ip)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$usuario_id, $acao, "Chamado $id ({$chamado['numero']}): $desc", $ip]);

            $pdo->commit();

            $_SESSION['msg'] = "Urgência atualizada com sucesso.";
            redirect("/chamados/ver.php?id=$id");

        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao salvar: " . $e->getMessage();
        }
    }
}

// ── Urgência efetiva atual ────────────────────────────────────────────────
$urgencia_efetiva = getUrgenciaEfetiva($pdo, $chamado);
$historico_urgencia = getUrgenciaHistorico($pdo, $id);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Urgência: <?= htmlspecialchars($chamado['numero']) ?></h1>
    <a href="/chamados/ver.php?id=<?= $id ?>" class="btn btn-secondary">Voltar</a>
</div>

<?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<!-- Painel de status atual -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Urgência original (usuário)</div>
                <div><?= badgeUrgencia($chamado['urgencia']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Urgência efetiva atual</div>
                <div><?= badgeUrgencia($urgencia_efetiva) ?></div>
                <?php if ($urgencia_efetiva !== $chamado['urgencia']): ?>
                    <div class="small text-warning mt-1"><i class="bi bi-exclamation-triangle"></i> Sobrescrita</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Sua intervenção ativa</div>
                <?php if ($registro_proprio): ?>
                    <div><?= badgeUrgencia($registro_proprio['urgencia']) ?></div>
                    <a href="?id=<?= $id ?>&revogar=1"
                       class="btn btn-sm btn-outline-danger mt-2"
                       onclick="return confirm('Revogar sua intervenção? A urgência efetiva será recalculada.')">
                        <i class="bi bi-x-circle"></i> Revogar
                    </a>
                <?php else: ?>
                    <span class="text-muted small">Nenhuma</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Formulário de intervenção -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white fw-bold">
        <i class="bi bi-exclamation-triangle"></i>
        <?= $registro_proprio ? 'Editar minha intervenção' : 'Registrar intervenção de urgência' ?>
    </div>
    <div class="card-body">
        <div class="alert alert-info small mb-3">
            <strong>Hierarquia:</strong> Técnico → Administrador → DEV.
            Cada nível só pode editar/revogar seu <em>próprio</em> registro.
            A urgência efetiva exibida no chamado é a de maior grau/hierarquia ativos.
        </div>

        <form method="POST">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="mb-3">
                <label class="form-label">Urgência <span class="text-danger">*</span></label>
                <select name="urgencia" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach (['LEVE','MODERADA','ALTA'] as $u): ?>
                        <option value="<?= $u ?>"
                            <?= (($registro_proprio['urgencia'] ?? ($_POST['urgencia'] ?? '')) === $u) ? 'selected' : '' ?>>
                            <?= ucfirst(strtolower($u)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Justificativa <span class="text-danger">*</span></label>
                <textarea name="justificativa" class="form-control" rows="3" required
                    placeholder="Justifique a alteração de urgência..."><?=
                    htmlspecialchars($registro_proprio['justificativa'] ?? ($_POST['justificativa'] ?? ''))
                ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i>
                <?= $registro_proprio ? 'Atualizar minha intervenção' : 'Registrar intervenção' ?>
            </button>
        </form>
    </div>
</div>

<!-- Histórico de intervenções -->
<?php if ($historico_urgencia): ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-bold">
        <i class="bi bi-clock-history"></i> Histórico de intervenções de urgência
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Autor</th>
                    <th>Perfil</th>
                    <th>Urgência</th>
                    <th>Status</th>
                    <th>Justificativa</th>
                    <th>IP</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historico_urgencia as $h): ?>
                <tr class="<?= $h['ativo'] ? '' : 'text-muted' ?>">
                    <td><?= htmlspecialchars($h['autor_nome']) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($h['cargo_autor']) ?></span></td>
                    <td><?= badgeUrgencia($h['urgencia'], true) ?></td>
                    <td>
                        <?php if ($h['ativo']): ?>
                            <span class="badge bg-success">Ativa</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Revogada</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-wrap" style="max-width:250px;">
                        <?= htmlspecialchars($h['justificativa']) ?>
                    </td>
                    <td><small><?= htmlspecialchars($h['ip'] ?? '-') ?></small></td>
                    <td><small><?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>