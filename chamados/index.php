<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

$pdo = getConnection();
$cargo = $_SESSION['usuario_cargo'];
$usuario_id = $_SESSION['usuario_id'];

// Filtros básicos
$status_filter = $_GET['status'] ?? '';
$pesquisa = $_GET['q'] ?? '';

// Montar query baseada no perfil
$where = [];
$params = [];

if ($cargo === 'USUARIO') {
    $where[] = "c.usuario_id = ?";
    $params[] = $usuario_id;
} elseif ($cargo === 'TECNICO') {
    // Para simplificar na view geral, o técnico vê os que não têm técnico (fila) ou os dele.
    // A Task 10 vai aprimorar a fila, mas aqui limitamos a visão básica.
    $where[] = "(c.tecnico_id = ? OR c.tecnico_id IS NULL)";
    $params[] = $usuario_id;
} else {
    // Administrador/DEV vê tudo.
}

if ($status_filter) {
    $where[] = "c.status = ?";
    $params[] = $status_filter;
}

if ($pesquisa) {
    $where[] = "(c.assunto LIKE ? OR c.numero LIKE ? OR u.nome LIKE ?)";
    $termo = "%$pesquisa%";
    array_push($params, $termo, $termo, $termo);
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Ordenação: 1. Urgência (Alta, Moderada, Leve), 2. Antiguidade (criado_em ASC)
$sql = "
    SELECT c.*, u.nome as solicitante, t.nome as tecnico, cat.nome as categoria 
    FROM chamados c
    JOIN usuarios u ON c.usuario_id = u.id
    LEFT JOIN usuarios t ON c.tecnico_id = t.id
    JOIN categorias cat ON c.categoria_id = cat.id
    $whereClause
    ORDER BY 
        CASE c.urgencia WHEN 'ALTA' THEN 1 WHEN 'MODERADA' THEN 2 WHEN 'LEVE' THEN 3 END ASC,
        c.criado_em ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$chamados = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';

function getBadgeUrgencia($urgencia) {
    switch ($urgencia) {
        case 'ALTA': return '<span class="badge bg-danger">Alta</span>';
        case 'MODERADA': return '<span class="badge bg-warning text-dark">Moderada</span>';
        case 'LEVE': return '<span class="badge bg-success">Leve</span>';
        default: return '<span class="badge bg-secondary">Indefinido</span>';
    }
}

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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Lista de Chamados</h1>
    <a href="/chamados/novo.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Abrir Chamado</a>
</div>

<?php if (isset($_SESSION['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['msg']); ?>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Pesquisar (Nº, Assunto ou Usuário)</label>
                <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($pesquisa) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="ABERTO" <?= $status_filter === 'ABERTO' ? 'selected' : '' ?>>Aberto</option>
                    <option value="EM_ANDAMENTO" <?= $status_filter === 'EM_ANDAMENTO' ? 'selected' : '' ?>>Em Andamento</option>
                    <option value="PAUSADO" <?= $status_filter === 'PAUSADO' ? 'selected' : '' ?>>Pausado</option>
                    <option value="FINALIZADO" <?= $status_filter === 'FINALIZADO' ? 'selected' : '' ?>>Finalizado</option>
                    <option value="CANCELADO" <?= $status_filter === 'CANCELADO' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i> Filtrar</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="/chamados/" class="btn btn-light w-100">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Nº</th>
                    <th>Assunto</th>
                    <th>Solicitante</th>
                    <th>Categoria</th>
                    <th>Técnico</th>
                    <th>Urgência</th>
                    <th>Status</th>
                    <th>Prazo SLA</th>
                    <th>Abertura</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($chamados) === 0): ?>
                    <tr><td colspan="10" class="text-center">Nenhum chamado encontrado.</td></tr>
                <?php else: ?>
                    <?php 
                    function checkPrazo($chamado) {
                        if ($chamado['prazo_indeterminado'] || !$chamado['prazo'] || in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])) {
                            return '<span class="text-secondary">-</span>';
                        }
                        $prazo = strtotime($chamado['prazo']);
                        $agora = time();
                        $diferenca = $prazo - $agora;
                        if ($diferenca < 0) return '<span class="badge bg-danger" title="Vencido">🔴 Vencido</span>';
                        elseif ($diferenca < 86400) return '<span class="badge bg-warning text-dark" title="Próximo do Vencimento">🟡 Próximo</span>';
                        else return '<span class="badge bg-success" title="No Prazo">🟢 No Prazo</span>';
                    }
                    foreach ($chamados as $c): 
                    ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($c['numero']) ?> <?= (isset($c['tipo']) && $c['tipo'] === 'INFORMAL') ? '<span class="badge bg-info text-dark">Informal</span>' : '' ?></td>
                        <td><?= htmlspecialchars($c['assunto']) ?></td>
                        <td><?= htmlspecialchars($c['solicitante']) ?></td>
                        <td><?= htmlspecialchars($c['categoria']) ?></td>
                        <td><?= htmlspecialchars($c['tecnico'] ?? 'Não atribuído') ?></td>
                        <td><?= getBadgeUrgencia($c['urgencia']) ?></td>
                        <td>
                            <?php if ($c['tag_repassado']): ?>
                                <span class="badge bg-secondary me-1" title="Repassado">Rep.</span>
                            <?php endif; ?>
                            <?php if ($c['tag_reaberto']): ?>
                                <span class="badge bg-warning text-dark me-1" title="Reaberto">Reab.</span>
                            <?php endif; ?>
                            <?= getBadgeStatus($c['status']) ?>
                        </td>
                        <td><?= checkPrazo($c) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($c['criado_em'])) ?></td>
                        <td>
                            <a href="/chamados/ver.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-info text-white" title="Visualizar Detalhes">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Placeholder Paginação Simples para Task 09 -->
        <nav>
          <ul class="pagination justify-content-end mb-0">
            <li class="page-item disabled"><a class="page-link" href="#">Anterior</a></li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item disabled"><a class="page-link" href="#">Próximo</a></li>
          </ul>
        </nav>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
