<?php
require_once __DIR__ . '/../config/database.php';

function login($matricula, $senha) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE matricula = ? AND ativo = 1");
    $stmt->execute([$matricula]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['senha'])) {
        session_regenerate_id(true); // Prevenção de Session Hijacking
        
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        $_SESSION['usuario_cargo'] = $user['cargo'];
        $_SESSION['primeiro_acesso'] = $user['primeiro_acesso'];
        
        logSystem($user['id'], 'LOGIN', 'Usuário realizou login no sistema.');
        return true;
    }
    return false;
}

function checkAuth() {
    if (!isset($_SESSION['usuario_id'])) {
        redirect('/auth/login.php');
    }
    
    // Bloqueia acesso a outras páginas se for o primeiro acesso
    if ($_SESSION['primeiro_acesso'] && basename($_SERVER['PHP_SELF']) !== 'primeiro_acesso.php' && basename($_SERVER['PHP_SELF']) !== 'logout.php') {
        redirect('/auth/primeiro_acesso.php');
    }
}

function checkProfile($allowedProfiles) {
    checkAuth();
    if (!in_array($_SESSION['usuario_cargo'], $allowedProfiles)) {
        logSystem($_SESSION['usuario_id'], 'ACESSO_NEGADO', 'Tentativa de acesso não autorizado.');
        redirect('/errors/unauthorized.php');
    }
}

function logout() {
    if (isset($_SESSION['usuario_id'])) {
        logSystem($_SESSION['usuario_id'], 'LOGOUT', 'Usuário saiu do sistema.');
    }
    session_destroy();
    redirect('/auth/login.php');
}

function changePasswordFirstAccess($usuario_id, $novaSenha) {
    $pdo = getConnection();
    $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, primeiro_acesso = 0 WHERE id = ?");
    if ($stmt->execute([$hash, $usuario_id])) {
        $_SESSION['primeiro_acesso'] = 0;
        logSystem($usuario_id, 'TROCA_SENHA', 'Usuário alterou a senha no primeiro acesso.');
        return true;
    }
    return false;
}

function logSystem($usuario_id, $acao, $descricao) {
    $pdo = getConnection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("INSERT INTO logs_sistema (usuario_id, acao, descricao, ip) VALUES (?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $acao, $descricao, $ip]);
}
