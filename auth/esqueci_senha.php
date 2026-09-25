<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
checkRateLimit("reset_senha", 3, 900); // máx 3 pedidos/15min por IP

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = $_POST['matricula'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $justificativa = $_POST['justificativa'] ?? '';
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE matricula = ? AND nome = ?");
    $stmt->execute([$matricula, $nome]);
    $user = $stmt->fetch();
    
    if ($user) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $pdo->prepare("INSERT INTO pedidos_administrativos (tipo, usuario_id, justificativa, solicitante_ip) VALUES ('RESET_SENHA', ?, ?, ?)");
        $stmt->execute([$user['id'], $justificativa, $ip]);
        
        // Notificar admins
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cargo IN ('ADMINISTRADOR', 'DEV')");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($admins as $admin_id) {
            $stmt = $pdo->prepare("INSERT INTO notificacoes (usuario_id, tipo, mensagem) VALUES (?, 'NOVO_PEDIDO', 'Novo pedido de reset de senha para a matrícula ' . ?)");
            $stmt->execute([$admin_id, $matricula]);
        }
        
        $sucesso = 'Pedido de reset de senha enviado aos administradores. Aguarde a aprovação.';
    } else {
        $erro = 'Dados não conferem ou usuário não encontrado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci minha senha - Sistema de Chamados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center py-4" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <h4 class="mb-4">Solicitar Reset de Senha</h4>
                        <?php if ($erro): ?>
                            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($erro) ?></div>
                        <?php endif; ?>
                        <?php if ($sucesso): ?>
                            <div class="alert alert-success py-2 small"><?= htmlspecialchars($sucesso) ?></div>
                            <a href="login.php" class="btn btn-primary w-100">Voltar ao Login</a>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label small">Matrícula</label>
                                    <input type="text" name="matricula" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Nome Completo</label>
                                    <input type="text" name="nome" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Justificativa</label>
                                    <textarea name="justificativa" class="form-control" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Solicitar Reset</button>
                                <div class="text-center mt-3">
                                    <a href="login.php" class="text-decoration-none small">Voltar</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>