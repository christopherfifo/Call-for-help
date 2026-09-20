<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkProfile(['DEV']);

$pdo = getConnection();
// Busca os logs do sistema
$stmt = $pdo->query("
    SELECT l.id, l.acao, l.descricao, l.ip, l.criado_em, u.nome, u.matricula
    FROM logs_sistema l
    LEFT JOIN usuarios u ON l.usuario_id = u.id
    ORDER BY l.criado_em DESC
    LIMIT 500
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Logs do Sistema (Auditoria)</h1>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body table-responsive">
        <table class="table table-striped table-hover align-middle table-sm">
            <thead class="table-dark">
                <tr>
                    <th>Data/Hora</th>
                    <th>Usuário</th>
                    <th>IP</th>
                    <th>Ação</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= date('d/m/Y H:i:s', strtotime($log['criado_em'])) ?></td>
                    <td>
                        <?php if ($log['nome']): ?>
                            <?= htmlspecialchars($log['nome']) ?> <small class="text-muted">(<?= htmlspecialchars($log['matricula']) ?>)</small>
                        <?php else: ?>
                            <em>Sistema</em>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['ip']) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($log['acao']) ?></span></td>
                    <td><?= htmlspecialchars($log['descricao']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
