<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

// Apenas Administrador ou DEV
$cargo = $_SESSION['usuario_cargo'];
if (!in_array($cargo, ['ADMINISTRADOR', 'DEV'])) {
    die("Acesso negado.");
}

$pdo = getConnection();

// Ação de Atender a solicitação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $solicitacao_id = $_POST['id'];
    
    $stmt = $pdo->prepare("UPDATE solicitacoes_relatorio SET status = 'ATENDIDO', atendido_por = ?, atendido_em = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id'], $solicitacao_id]);
    
    $_SESSION['msg'] = "Solicitação marcada como atendida.";
    redirect('/admin/solicitacoes_relatorio.php');
}

// Busca todas as solicitações
$stmt = $pdo->query("
    SELECT s.*, c.numero as chamado_numero, u.nome as solicitante, u.cargo as solicitante_cargo, t.nome as atendente
    FROM solicitacoes_relatorio s
    JOIN chamados c ON s.chamado_id = c.id
    JOIN usuarios u ON s.solicitante_id = u.id
    LEFT JOIN usuarios t ON s.atendido_por = t.id
    ORDER BY s.status = 'PENDENTE' DESC, s.criado_em DESC
");
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Solicitações de Relatório</h1>
</div>

<?php if (isset($_SESSION['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['msg']); ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Chamado</th>
                    <th>Solicitante</th>
                    <th>Perfil</th>
                    <th>Data Solicitação</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($solicitacoes) === 0): ?>
                    <tr><td colspan="6" class="text-center">Nenhuma solicitação encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($solicitacoes as $s): ?>
                        <tr>
                            <td><a href="/chamados/ver.php?id=<?= $s['chamado_id'] ?>" class="fw-bold text-decoration-none"><?= htmlspecialchars($s['chamado_numero']) ?></a></td>
                            <td><?= htmlspecialchars($s['solicitante']) ?></td>
                            <td><?= htmlspecialchars($s['solicitante_cargo']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($s['criado_em'])) ?></td>
                            <td>
                                <?php if ($s['status'] === 'PENDENTE'): ?>
                                    <span class="badge bg-warning text-dark">Pendente</span>
                                <?php elseif ($s['status'] === 'ATENDIDO'): ?>
                                    <span class="badge bg-success">Atendido</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($s['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalVer<?= $s['id'] ?>">
                                    <i class="bi bi-eye"></i> Visualizar
                                </button>
                                <?php if ($s['status'] === 'PENDENTE'): ?>
                                    <!-- Formulário para marcar como atendido -->
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Marcar como Atendido"><i class="bi bi-check-circle"></i> Atender</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Modal Visualizar Solicitação -->
                        <div class="modal fade" id="modalVer<?= $s['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Detalhes da Solicitação</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Chamado:</strong> <?= htmlspecialchars($s['chamado_numero']) ?></p>
                                        <p><strong>Solicitante:</strong> <?= htmlspecialchars($s['solicitante']) ?> (<?= htmlspecialchars($s['solicitante_cargo']) ?>)</p>
                                        <p><strong>Data:</strong> <?= date('d/m/Y H:i:s', strtotime($s['criado_em'])) ?></p>
                                        <hr>
                                        <p><strong>Motivo:</strong><br><?= nl2br(htmlspecialchars($s['motivo'])) ?></p>
                                        <?php if ($s['observacao']): ?>
                                            <p><strong>Observação:</strong><br><?= nl2br(htmlspecialchars($s['observacao'])) ?></p>
                                        <?php endif; ?>
                                        <hr>
                                        <p><strong>Status:</strong> <?= $s['status'] ?></p>
                                        <?php if ($s['status'] === 'ATENDIDO'): ?>
                                            <p><strong>Atendido por:</strong> <?= htmlspecialchars($s['atendente']) ?></p>
                                            <p><strong>Em:</strong> <?= date('d/m/Y H:i:s', strtotime($s['atendido_em'])) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer d-flex justify-content-between">
                                        <!-- Botão provisório para Task 20 -->
                                        <a href="/admin/gerar_relatorio.php?id=<?= $s['chamado_id'] ?>" class="btn btn-dark"><i class="bi bi-file-pdf"></i> Gerar PDF</a>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
