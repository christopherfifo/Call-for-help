<?php
/**
 * Script de instalação do Banco de Dados
 * Idempotente: pode ser executado quantas vezes quiser sem duplicar dados.
 * Execute via CLI: php install.php
 * ou via navegador (apenas em ambiente seguro).
 */

require_once __DIR__ . '/../config/config.php';

$host   = DB_HOST;
$dbname = DB_NAME;
$user   = DB_USER;
$pass   = DB_PASS;

try {
    // Conecta sem selecionar o banco para poder criá-lo se necessário
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✔ Conectado ao MySQL.\n";

    // Executa o schema completo
    $sqlFile = __DIR__ . '/schema.sql';
    if (!file_exists($sqlFile)) {
        die("✘ Arquivo schema.sql não encontrado.\n");
    }

    $sql = file_get_contents($sqlFile);

    // Executa statement por statement para compatibilidade com PDO
    // (PDO::exec não aceita múltiplos statements em todas as versões)
    $pdo->exec($sql);

    echo "✔ Estrutura do banco de dados criada/verificada com sucesso.\n";

    // Seleciona o banco para inserir o usuário DEV
    $pdo->exec("USE `$dbname`");

    // ── Usuário DEV (idempotente: INSERT IGNORE) ───────────────────────────
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE matricula = 'DEV001'");
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        $senhaHash = password_hash('DEV001', PASSWORD_DEFAULT);

        $insert = $pdo->prepare("
            INSERT INTO usuarios (nome, email, matricula, senha, cargo, ativo, primeiro_acesso)
            VALUES (?, ?, ?, ?, ?, 1, 1)
        ");
        $insert->execute([
            'Super Administrador',
            'dev@sistema.local',
            'DEV001',
            $senhaHash,
            'DEV',
        ]);

        echo "✔ Usuário DEV criado.\n";
        echo "  Matrícula : DEV001\n";
        echo "  Senha prov.: DEV001\n";
    } else {
        echo "✔ Usuário DEV já existe — nenhuma alteração.\n";
    }

    // ── Categorias já são inseridas via INSERT IGNORE no schema.sql ─────────
    echo "✔ Categorias verificadas (INSERT IGNORE aplicado no schema).\n";

    echo "\n✔ Instalação concluída com sucesso!\n";

} catch (PDOException $e) {
    die("✘ Erro: " . $e->getMessage() . "\n");
}