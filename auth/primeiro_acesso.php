<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

if (!isset($_SESSION['usuario_id'])) {
    redirect('/auth/login.php');
}

if (!$_SESSION['primeiro_acesso']) {
    redirect('/dashboard/index.php');
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    if (strlen($nova_senha) < 6) {
        $erro = 'A nova senha deve ter no mínimo 6 caracteres.';
    } elseif ($nova_senha !== $confirma_senha) {
        $erro = 'As senhas não conferem.';
    } else {
        if (changePasswordFirstAccess($_SESSION['usuario_id'], $nova_senha)) {
            $sucesso = 'Senha alterada com sucesso! Redirecionando...';
            header("refresh:2;url=/dashboard/index.php");
        } else {
            $erro = 'Erro ao alterar a senha. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Primeiro Acesso - Troca de Senha</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center py-4" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                
                <div class="text-center mb-4">
                    <div class="bg-warning text-dark rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-shield-lock fs-2"></i>
                    </div>
                    <h2 class="h4 fw-bold text-dark">Ação Obrigatória</h2>
                    <p class="text-muted small">Por questões de segurança, você deve alterar sua senha provisória para acessar o sistema.</p>
                </div>

                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        
                        <?php if ($erro): ?>
                            <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($erro) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem;"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($sucesso): ?>
                            <div class="alert alert-success py-4 text-center border-0" role="alert">
                                <i class="bi bi-check-circle-fill fs-1 text-success d-block mb-3"></i>
                                <h5 class="fw-bold mb-1">Tudo certo!</h5>
                                <span class="small"><?= htmlspecialchars($sucesso) ?></span>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Nova Senha</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                        <input type="password" name="nova_senha" class="form-control" placeholder="Mínimo de 6 caracteres" required minlength="6" autofocus>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-semibold small">Confirme a Nova Senha</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-key-fill"></i></span>
                                        <input type="password" name="confirma_senha" class="form-control" placeholder="Repita a nova senha" required minlength="6">
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
                                        <i class="bi bi-save me-1"></i> Salvar Nova Senha
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="/auth/logout.php" class="text-danger text-decoration-none small fw-semibold">
                        <i class="bi bi-box-arrow-left me-1"></i> Sair do sistema
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>