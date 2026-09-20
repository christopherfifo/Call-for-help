<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

// Se já estiver logado, redireciona para o dashboard
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['primeiro_acesso']) {
        redirect('/auth/primeiro_acesso.php');
    } else {
        redirect('/dashboard/index.php');
    }
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = $_POST['matricula'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (login($matricula, $senha)) {
        if ($_SESSION['primeiro_acesso']) {
            redirect('/auth/primeiro_acesso.php');
        } else {
            redirect('/dashboard/index.php');
        }
    } else {
        $erro = 'Matrícula ou senha inválidos, ou usuário inativo.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Chamados</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center py-4" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                
                <div class="text-center mb-4">
                    <div class="bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-headset fs-2"></i>
                    </div>
                    <h2 class="h3 fw-bold text-dark">Sistema de Chamados</h2>
                    <p class="text-muted small">Faça login para acessar o painel</p>
                </div>

                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        
                        <?php if ($erro): ?>
                            <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($erro) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem;"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Matrícula</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" name="matricula" class="form-control" placeholder="Digite sua matrícula" required autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold small">Senha</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                    <input type="password" name="senha" class="form-control" placeholder="Digite sua senha" required>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                                </button>
                            </div>
                            <div class="text-center mt-3">
                                <a href="esqueci_senha.php" class="text-decoration-none small">Esqueci minha senha</a>
                            </div>
                        </form>

                    </div>
                </div>
                
                <div class="text-center mt-4 text-muted small">
                    &copy; <?= date('Y') ?> — Gerenciamento de Chamados de TI
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>