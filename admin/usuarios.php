<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkProfile(['ADMINISTRADOR', 'DEV']);

$pdo = getConnection();
$stmt = $pdo->query("SELECT id, nome, matricula, email, cargo, ativo, criado_em FROM usuarios ORDER BY nome ASC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Gerenciamento de Usuários</h1>
    <a href="/admin/usuario_form.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Novo Usuário</a>
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
                    <th>Nome</th>
                    <th>Matrícula</th>
                    <th>E-mail</th>
                    <th>Cargo</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nome']) ?></td>
                    <td><?= htmlspecialchars($u['matricula']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="badge bg-info text-dark"><?= htmlspecialchars($u['cargo']) ?></span>
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
                            <?php if ($u['ativo']): ?>
                                <button type="submit" name="acao" value="desativar" class="btn btn-sm btn-danger" title="Desativar" onclick="return confirm('Deseja realmente desativar este usuário?');">
                                    <i class="bi bi-person-x"></i>
                                </button>
                            <?php else: ?>
                                <button type="submit" name="acao" value="reativar" class="btn btn-sm btn-success" title="Reativar" onclick="return confirm('Deseja realmente reativar este usuário?');">
                                    <i class="bi bi-person-check"></i>
                                </button>
                            <?php endif; ?>
                            <button type="submit" name="acao" value="reset_senha" class="btn btn-sm btn-secondary" title="Resetar Senha" onclick="return confirm('Deseja resetar a senha deste usuário para a matrícula (senha provisória)?');">
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
