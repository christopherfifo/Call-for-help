<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkProfile(['ADMINISTRADOR', 'DEV']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $acao = $_POST['acao'] ?? '';

    if ($id && $acao) {
        $pdo = getConnection();
        
        // Verifica dados atuais do usuário alvo
        $stmt = $pdo->prepare("SELECT matricula, cargo FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $alvo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($alvo) {
            // Previne Admin de alterar DEV/Admin
            if (in_array($alvo['cargo'], ['ADMINISTRADOR', 'DEV']) && $_SESSION['usuario_cargo'] !== 'DEV') {
                die("Permissão negada.");
            }
            
            if ($acao === 'desativar') {
                $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['msg'] = 'Usuário desativado com sucesso.';
                logSystem($_SESSION['usuario_id'], 'DESATIVAR_USUARIO', "Desativou usuário ID $id");
            } 
            elseif ($acao === 'reativar') {
                $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 1 WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['msg'] = 'Usuário reativado com sucesso.';
                logSystem($_SESSION['usuario_id'], 'REATIVAR_USUARIO', "Reativou usuário ID $id");
            } 
            elseif ($acao === 'reset_senha') {
                $senhaHash = password_hash($alvo['matricula'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, primeiro_acesso = 1 WHERE id = ?");
                $stmt->execute([$senhaHash, $id]);
                $_SESSION['msg'] = 'Senha resetada com sucesso para a matrícula original.';
                logSystem($_SESSION['usuario_id'], 'RESET_SENHA_USUARIO', "Resetou senha do usuário ID $id");
            }
        }
    }
    redirect('/admin/usuarios.php');
}
