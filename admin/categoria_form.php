<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkProfile(['ADMINISTRADOR', 'DEV']);

$pdo = getConnection();
$id = $_GET['id'] ?? null;
$categoria = ['nome' => ''];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$id]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$categoria) {
        redirect('/admin/categorias.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    
    if (empty($nome)) {
        $erro = "O nome da categoria é obrigatório.";
    } else {
        // Validação se já existe
        $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nome = ? AND id != ?");
        $stmt->execute([$nome, $id ?? 0]);
        if ($stmt->fetch()) {
            $erro = "Já existe uma categoria com este nome.";
        } else {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
                $stmt->execute([$nome, $id]);
                $_SESSION['msg'] = 'Categoria atualizada com sucesso.';
                logSystem($_SESSION['usuario_id'], 'EDICAO_CATEGORIA', "Editou categoria ID $id para '$nome'");
            } else {
                $stmt = $pdo->prepare("INSERT INTO categorias (nome, ativo) VALUES (?, 1)");
                $stmt->execute([$nome]);
                $_SESSION['msg'] = 'Categoria criada com sucesso.';
                logSystem($_SESSION['usuario_id'], 'CRIACAO_CATEGORIA', "Criou categoria '$nome'");
            }
            redirect('/admin/categorias.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?= $id ? 'Editar Categoria' : 'Nova Categoria' ?></h1>
    <a href="/admin/categorias.php" class="btn btn-secondary">Voltar</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nome da Categoria</label>
                <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($categoria['nome']) ?>" required>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Salvar</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
