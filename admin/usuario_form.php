<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkProfile(['ADMINISTRADOR', 'DEV']);

$pdo = getConnection();
$id = $_GET['id'] ?? null;
$usuario = ['nome' => '', 'email' => '', 'matricula' => '', 'cargo' => 'USUARIO'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        redirect('/admin/usuarios.php');
    }
    
    // Regra: Apenas DEV pode editar outro DEV/Admin (simplificado aqui para task 05, mas task 06 aprofunda)
    if (in_array($usuario['cargo'], ['ADMINISTRADOR', 'DEV']) && $_SESSION['usuario_cargo'] !== 'DEV') {
        die("Você não tem permissão para editar este perfil.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $matricula = $_POST['matricula'] ?? '';
    $cargo = $_POST['cargo'] ?? 'USUARIO';
    
    // Validação de permissão: Admin não pode criar DEV ou ADMINISTRADOR
    if ($_SESSION['usuario_cargo'] !== 'DEV' && in_array($cargo, ['ADMINISTRADOR', 'DEV'])) {
        die("Você não tem permissão para conceder este cargo.");
    }
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, matricula = ?, cargo = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $matricula, $cargo, $id]);
        $_SESSION['msg'] = 'Usuário atualizado com sucesso.';
        logSystem($_SESSION['usuario_id'], 'EDICAO_USUARIO', "Editou o usuário ID $id");
    } else {
        // Nova senha provisória é a própria matrícula
        $senhaHash = password_hash($matricula, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, matricula, senha, cargo, ativo, primeiro_acesso) VALUES (?, ?, ?, ?, ?, 1, 1)");
        $stmt->execute([$nome, $email, $matricula, $senhaHash, $cargo]);
        $_SESSION['msg'] = 'Usuário criado com sucesso. A senha provisória é a matrícula.';
        logSystem($_SESSION['usuario_id'], 'CRIACAO_USUARIO', "Criou o usuário matrícula $matricula");
    }
    
    redirect('/admin/usuarios.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?= $id ? 'Editar Usuário' : 'Novo Usuário' ?></h1>
    <a href="/admin/usuarios.php" class="btn btn-secondary">Voltar</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nome Completo</label>
                <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">E-mail</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Matrícula</label>
                <input type="text" class="form-control" name="matricula" value="<?= htmlspecialchars($usuario['matricula']) ?>" required>
                <?php if (!$id): ?>
                <div class="form-text">A matrícula será utilizada como senha no primeiro acesso.</div>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Cargo</label>
                <select class="form-select" name="cargo" required>
                    <option value="USUARIO" <?= $usuario['cargo'] === 'USUARIO' ? 'selected' : '' ?>>Usuário Comum</option>
                    <option value="TECNICO" <?= $usuario['cargo'] === 'TECNICO' ? 'selected' : '' ?>>Técnico</option>
                    <?php if ($_SESSION['usuario_cargo'] === 'DEV'): ?>
                        <option value="ADMINISTRADOR" <?= $usuario['cargo'] === 'ADMINISTRADOR' ? 'selected' : '' ?>>Administrador</option>
                        <option value="DEV" <?= $usuario['cargo'] === 'DEV' ? 'selected' : '' ?>>DEV / Superadmin</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Salvar</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
