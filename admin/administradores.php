<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkProfile(['DEV']);

$pdo = getConnection();
$stmt = $pdo->query("SELECT id, nome, matricula, email, cargo, ativo, criado_em FROM usuarios WHERE cargo IN ('ADMINISTRADOR', 'DEV') ORDER BY nome ASC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Gerenciamento de Administradores</h1>
    <a href="/admin/usuario_form.php?context=admin" class="btn btn-primary"><i class="bi bi-shield-plus"></i> Novo Administrador</a>
</div>

<div class="alert alert-info">
    Esta área é exclusiva para o DEV / Superadministrador. Aqui você gerencia os níveis de acesso superiores.
</div>

<div class="card shadow-sm border-0">
    <div class="card-body table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Nome</th>
                    <th>Matrícula</th>
                    <th>Nível</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nome']) ?></td>
                    <td><?= htmlspecialchars($u['matricula']) ?></td>
                    <td>
                        <?php if ($u['cargo'] === 'DEV'): ?>
                            <span class="badge bg-dark">DEV / Superadmin</span>
                        <?php else: ?>
                            <span class="badge bg-primary">Administrador</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['ativo']): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/admin/usuario_form.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="/admin/usuario_acao.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <?php if ($u['id'] !== $_SESSION['usuario_id']): // Não pode desativar a si mesmo ?>
                                <?php if ($u['ativo']): ?>
                                    <button type="submit" name="acao" value="desativar" class="btn btn-sm btn-danger" title="Desativar" onclick="return confirm('Deseja realmente desativar este administrador?');">
                                        <i class="bi bi-person-x"></i>
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="acao" value="reativar" class="btn btn-sm btn-success" title="Reativar">
                                        <i class="bi bi-person-check"></i>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <button type="submit" name="acao" value="reset_senha" class="btn btn-sm btn-secondary" title="Resetar Senha" onclick="return confirm('Resetar a senha deste administrador?');">
                                <i class="bi bi-key"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
