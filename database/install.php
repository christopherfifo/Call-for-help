<?php
/**
 * Script de instalação do Banco de Dados
 * Executar via CLI ou navegador (apenas uma vez)
 */

require_once __DIR__ . '/../config/config.php';

$host = DB_HOST;
$dbname = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;

try {
    // Conecta sem selecionar o banco para poder criá-lo
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conectado ao MySQL com sucesso.\n";

    // Lê o arquivo SQL
    $sqlFile = __DIR__ . '/schema.sql';
    if (!file_exists($sqlFile)) {
        die("Arquivo schema.sql não encontrado.\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Separa os comandos SQL por ';'
    // Opcional: Para evitar problemas, podemos apenas executar o script completo 
    // mas se a versão do pdo/mysql reclamar de múltiplos statements, devemos quebrar
    $pdo->exec($sql);
    echo "Estrutura do banco de dados criada com sucesso.\n";
    
    // Seleciona o banco criado para inserir o usuário admin
    $pdo->exec("USE `$dbname`");

    // Verifica se o usuário DEV já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE matricula = ?");
    $stmt->execute(['DEV001']);
    
    if ($stmt->rowCount() == 0) {
        $senhaProvisoria = 'DEV001';
        $senhaHash = password_hash($senhaProvisoria, PASSWORD_DEFAULT);
        
        $insert = $pdo->prepare("
            INSERT INTO usuarios (nome, email, matricula, senha, cargo, ativo, primeiro_acesso) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insert->execute([
            'Super Administrador',
            'dev@sistema.local',
            'DEV001',
            $senhaHash,
            'DEV',
            1,
            1
        ]);
        
        echo "Usuário DEV criado com sucesso!\n";
        echo "Matrícula: DEV001\n";
        echo "Senha Provisória: DEV001\n";
    } else {
        echo "Usuário DEV já existe no banco.\n";
    }

} catch (PDOException $e) {
    die("Erro ao executar script: " . $e->getMessage() . "\n");
}
