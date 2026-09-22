<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

$pdo = getConnection();
$cargo = $_SESSION['usuario_cargo'];
$usuario_id = $_SESSION['usuario_id'];

// Helpers para badges
function getBadgeStatus($status) {
    switch ($status) {
        case 'ABERTO': return '<span class="badge bg-primary">Aberto</span>';
        case 'EM_ANDAMENTO': return '<span class="badge bg-info text-dark">Em Andamento</span>';
        case 'PAUSADO': return '<span class="badge bg-warning text-dark">Pausado</span>';
        case 'FINALIZADO': return '<span class="badge bg-success">Finalizado</span>';
        case 'CANCELADO': return '<span class="badge bg-danger">Cancelado</span>';
        default: return '<span class="badge bg-secondary">Indefinido</span>';
    }
}

// badgeUrgencia() é global via config/helpers.php

function checkPrazo($chamado) {
    if ($chamado['prazo_indeterminado'] || !$chamado['prazo'] || in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])) {
        return '<span class="text-secondary">-</span>';
    }
    
    $prazo = strtotime($chamado['prazo']);
    $agora = time();
    $diferenca = $prazo - $agora;
    
    if ($diferenca < 0) {
        return '<span class="badge bg-danger" title="Prazo Vencido">🔴 Vencido</span>';
    } elseif ($diferenca < 86400) { // Menos de 24 horas
        return '<span class="badge bg-warning text-dark" title="Próximo do Vencimento">🟡 Próximo</span>';
    } else {
        return '<span class="badge bg-success" title="Dentro do Prazo">🟢 No Prazo</span>';
    }
}

// Lógica por Cargo
$indicadores = [];
$chamados_recentes = [];
$chamados_prioritarios = [];

if ($cargo === 'USUARIO') {
    // Indicadores Usuário
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as qtd FROM chamados WHERE usuario_id = ? GROUP BY status");
    $stmt->execute([$usuario_id]);
    $res = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $indicadores = [
        'Abertos' => $res['ABERTO'] ?? 0,
        'Em Andamento' => $res['EM_ANDAMENTO'] ?? 0,
        'Pausados' => $res['PAUSADO'] ?? 0,
        'Finalizados' => $res['FINALIZADO'] ?? 0,
        'Cancelados' => $res['CANCELADO'] ?? 0
    ];
    
    // Lista recentes
    $stmt = $pdo->prepare("SELECT id, numero, assunto, status, urgencia, prazo, prazo_indeterminado, criado_em, tipo FROM chamados WHERE usuario_id = ? ORDER BY criado_em DESC LIMIT 10");
    $stmt->execute([$usuario_id]);
    $chamados_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($cargo === 'TECNICO') {
    // Indicadores Técnico
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as qtd FROM chamados WHERE tecnico_id = ? GROUP BY status");
    $stmt->execute([$usuario_id]);
    $res = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM chamados WHERE status = 'ABERTO' AND tecnico_id IS NULL");
    $stmt->execute();
    $abertos_fila = $stmt->fetchColumn();
    
    $indicadores = [
        'Fila (Sem Técnico)' => $abertos_fila,
        'Meus (Andamento)' => $res['EM_ANDAMENTO'] ?? 0,
        'Meus (Pausados)' => $res['PAUSADO'] ?? 0,
        'Meus (Finalizados)' => $res['FINALIZADO'] ?? 0
    ];
    
    // Lista prioritários ou em atraso para o técnico
    $stmt = $pdo->prepare("
        SELECT id, numero, assunto, status, urgencia, prazo, prazo_indeterminado, criado_em, tipo 
        FROM chamados 
        WHERE (tecnico_id = ? OR tecnico_id IS NULL) AND status NOT IN ('FINALIZADO', 'CANCELADO')
        ORDER BY 
            CASE COALESCE((SELECT uc.urgencia FROM urgencia_chamados uc WHERE uc.chamado_id = id AND uc.ativo = 1 ORDER BY uc.hierarquia DESC, FIELD(uc.urgencia,'ALTA','MODERADA','LEVE') ASC LIMIT 1), urgencia) WHEN 'ALTA' THEN 1 WHEN 'MODERADA' THEN 2 WHEN 'LEVE' THEN 3 END ASC,
            prazo ASC, 
            criado_em ASC
        LIMIT 10
    ");
    $stmt->execute([$usuario_id]);
    $chamados_prioritarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lista chamados informais registrados por este técnico
    $stmt = $pdo->prepare("SELECT id, numero, assunto, status, urgencia, prazo, prazo_indeterminado, criado_em, tipo FROM chamados WHERE criado_por = ? AND tipo = 'INFORMAL' ORDER BY criado_em DESC LIMIT 10");
    $stmt->execute([$usuario_id]);
    $chamados_informais = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else { // ADMINISTRADOR ou DEV
    // Indicadores Admin
    $stmt = $pdo->query("SELECT status, COUNT(*) as qtd FROM chamados GROUP BY status");
    $res = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM chamados WHERE tag_reaberto = 1 AND status NOT IN ('FINALIZADO', 'CANCELADO')");
    $reabertos = $stmt->fetchColumn();
    
    $indicadores = [
        'Abertos' => $res['ABERTO'] ?? 0,
        'Em Andamento' => $res['EM_ANDAMENTO'] ?? 0,
        'Pausados' => $res['PAUSADO'] ?? 0,
        'Finalizados' => $res['FINALIZADO'] ?? 0,
        'Cancelados' => $res['CANCELADO'] ?? 0,
        'Reabertos' => $reabertos
    ];
    
    // Lista prioritários globais
    $stmt = $pdo->query("
        SELECT id, numero, assunto, status, urgencia, prazo, prazo_indeterminado, criado_em, tipo 
        FROM chamados 
        WHERE status NOT IN ('FINALIZADO', 'CANCELADO')
        ORDER BY 
            CASE COALESCE((SELECT uc.urgencia FROM urgencia_chamados uc WHERE uc.chamado_id = id AND uc.ativo = 1 ORDER BY uc.hierarquia DESC, FIELD(uc.urgencia,'ALTA','MODERADA','LEVE') ASC LIMIT 1), urgencia) WHEN 'ALTA' THEN 1 WHEN 'MODERADA' THEN 2 WHEN 'LEVE' THEN 3 END ASC,
            prazo ASC,
            criado_em ASC
        LIMIT 10
    ");
    $chamados_prioritarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Dashboard - <?= htmlspecialchars($cargo) ?></h1>
</div>

<!-- Cards de Indicadores -->
<div class="row">
    <?php 
    $cores = ['bg-primary', 'bg-info text-dark', 'bg-warning text-dark', 'bg-success', 'bg-danger', 'bg-secondary'];
    $i = 0;
    foreach ($indicadores as $label => $valor): 
        $cor = $cores[$i % count($cores)];
    ?>
    <div class="col-md-3 mb-4">
        <div class="card <?= $cor ?> h-100 shadow-sm border-0">
            <div class="card-body py-4 text-center">
                <h6 class="card-title fw-bold text-uppercase mb-2"><?= htmlspecialchars($label) ?></h6>
                <h2 class="display-5 fw-bold mb-0"><?= (int)$valor ?></h2>
            </div>
        </div>
    </div>
    <?php 
        $i++;
    endforeach; 
    ?>
</div>

<!-- Listagem de Chamados -->
<?php if ($cargo === 'USUARIO'): ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history"></i> Meus Chamados Recentes</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nº</th>
                        <th>Assunto</th>
                        <th>Urgência</th>
                        <th>Status</th>
                        <th>Prazo SLA</th>
                        <th>Abertura</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($chamados_recentes) === 0): ?>
                        <tr><td colspan="7" class="text-center">Você ainda não abriu chamados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($chamados_recentes as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['numero']) ?> <?= (isset($c['tipo']) && $c['tipo'] === 'INFORMAL') ? '<span class="badge bg-info text-dark">Informal</span>' : '' ?></td>
                                <td><?= htmlspecialchars($c['assunto']) ?></td>
                                <td><?= badgeUrgencia(getUrgenciaEfetiva($pdo, $c), true) ?></td>
                                <td><?= getBadgeStatus($c['status']) ?></td>
                                <td><?= checkPrazo($c) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($c['criado_em'])) ?></td>
                                <td><a href="/chamados/ver.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary"><i class="bi bi-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-exclamation-octagon"></i> Chamados Prioritários / Na Fila</h5>
            <a href="/chamados/" class="btn btn-sm btn-outline-primary">Ver Todos</a>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nº</th>
                        <th>Assunto</th>
                        <th>Urgência</th>
                        <th>Status</th>
                        <th>Prazo SLA</th>
                        <th>Abertura</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($chamados_prioritarios) === 0): ?>
                        <tr><td colspan="7" class="text-center">Nenhum chamado pendente no momento.</td></tr>
                    <?php else: ?>
                        <?php foreach ($chamados_prioritarios as $c): ?>
                            <tr>
                                <td><a href="/chamados/ver.php?id=<?= $c['id'] ?>" class="fw-bold text-decoration-none"><?= htmlspecialchars($c['numero']) ?></a> <?= (isset($c['tipo']) && $c['tipo'] === 'INFORMAL') ? '<span class="badge bg-info text-dark">Informal</span>' : '' ?></td>
                                <td><?= htmlspecialchars($c['assunto']) ?></td>
                                <td><?= badgeUrgencia(getUrgenciaEfetiva($pdo, $c), true) ?></td>
                                <td><?= getBadgeStatus($c['status']) ?></td>
                                <td><?= checkPrazo($c) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($c['criado_em'])) ?></td>
                                <td><a href="/chamados/ver.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-arrow-right"></i> Acessar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($cargo === 'TECNICO'): ?>
    <div class="card shadow-sm border-0 border-info mb-4">
        <div class="card-header bg-info text-dark py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-headset"></i> Meus Chamados Informais Registrados</h5>
            <a href="/chamados/" class="btn btn-sm btn-outline-dark">Ver Todos</a>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nº</th>
                        <th>Assunto</th>
                        <th>Urgência</th>
                        <th>Status</th>
                        <th>Prazo SLA</th>
                        <th>Abertura</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($chamados_informais) === 0): ?>
                        <tr><td colspan="7" class="text-center">Nenhum chamado informal registrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($chamados_informais as $c): ?>
                            <tr>
                                <td><a href="/chamados/ver.php?id=<?= $c['id'] ?>" class="fw-bold text-decoration-none"><?= htmlspecialchars($c['numero']) ?></a> <span class="badge bg-info text-dark">Informal</span></td>
                                <td><?= htmlspecialchars($c['assunto']) ?></td>
                                <td><?= badgeUrgencia(getUrgenciaEfetiva($pdo, $c), true) ?></td>
                                <td><?= getBadgeStatus($c['status']) ?></td>
                                <td><?= checkPrazo($c) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($c['criado_em'])) ?></td>
                                <td><a href="/chamados/ver.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-arrow-right"></i> Acessar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>