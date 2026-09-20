<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkProfile(['ADMINISTRADOR', 'DEV']);

$pdo = getConnection();
$stmt = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && isset($_POST['id'])) {
    $id = $_POST['id'];
    $acao = $_POST['acao'];
    
    if ($acao === 'desativar') {
        $stmt = $pdo->prepare("UPDATE categorias SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['msg'] = 'Categoria desativada com sucesso.';
        logSystem($_SESSION['usuario_id'], 'DESATIVAR_CATEGORIA', "Desativou categoria ID $id");
    } elseif ($acao === 'reativar') {
        $stmt = $pdo->prepare("UPDATE categorias SET ativo = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['msg'] = 'Categoria reativada com sucesso.';
        logSystem($_SESSION['usuario_id'], 'REATIVAR_CATEGORIA', "Reativou categoria ID $id");
    }
    
    redirect('/admin/categorias.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Gerenciamento de Categorias</h1>
    <a href="/admin/categoria_form.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nova Categoria</a>
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
                    <th>Nome da Categoria</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $cat): ?>
                <tr>
                    <td><?= htmlspecialchars($cat['nome']) ?></td>
                    <td>
                        <?php if ($cat['ativo']): ?>
                            <span class="badge bg-success">Ativa</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inativa</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/admin/categoria_form.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <?php if ($cat['ativo']): ?>
                                <button type="submit" name="acao" value="desativar" class="btn btn-sm btn-danger" title="Desativar" onclick="return confirm('Deseja realmente desativar esta categoria?');">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            <?php else: ?>
                                <button type="submit" name="acao" value="reativar" class="btn btn-sm btn-success" title="Reativar" onclick="return confirm('Deseja realmente reativar esta categoria?');">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
