<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/chamados/');
}

$pdo = getConnection();
$id = $_POST['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];
$cargo = $_SESSION['usuario_cargo'];

if (!$id || !isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['msg_erro'] = "Erro no upload do arquivo.";
    redirect("/chamados/ver.php?id=$id");
}

$stmt = $pdo->prepare("SELECT id, status, usuario_id, tecnico_id FROM chamados WHERE id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chamado || in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])) {
    $_SESSION['msg_erro'] = "Não é possível adicionar anexos a este chamado.";
    redirect("/chamados/ver.php?id=$id");
}

$pode_anexar = false;
if ($cargo === 'USUARIO' && $chamado['usuario_id'] == $usuario_id) {
    $pode_anexar = true;
} elseif ($cargo === 'TECNICO' && ($chamado['tecnico_id'] == $usuario_id || $chamado['tecnico_id'] == null)) {
    $pode_anexar = true;
} elseif (in_array($cargo, ['ADMINISTRADOR', 'DEV'])) {
    $pode_anexar = true;
}

if (!$pode_anexar) {
    $_SESSION['msg_erro'] = "Você não tem permissão para anexar arquivos neste chamado.";
    redirect("/chamados/ver.php?id=$id");
}

$arquivo = $_FILES['arquivo'];
$nome_original = $arquivo['name'];
$tamanho = $arquivo['size'];
$tmp_name = $arquivo['tmp_name'];

// Validações
$max_size = 5 * 1024 * 1024; // 5MB
if ($tamanho > $max_size) {
    $_SESSION['msg_erro'] = "O arquivo excede o limite de 5MB.";
    redirect("/chamados/ver.php?id=$id");
}

// Extensões e MIME types permitidos
$extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
$mimes_permitidos = [
    'image/jpeg', 'image/png', 'image/gif', 
    'application/pdf', 
    'application/msword', 
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain'
];

$extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $tmp_name);
finfo_close($finfo);

if (!in_array($extensao, $extensoes_permitidas) || !in_array($mime, $mimes_permitidos)) {
    $_SESSION['msg_erro'] = "Tipo de arquivo não permitido.";
    redirect("/chamados/ver.php?id=$id");
}

$diretorio_uploads = __DIR__ . '/../uploads/';
if (!is_dir($diretorio_uploads)) {
    mkdir($diretorio_uploads, 0755, true);
}

$novo_nome = uniqid('anexo_') . '_' . bin2hex(random_bytes(8)) . '.' . $extensao;
$caminho_destino = $diretorio_uploads . $novo_nome;

if (move_uploaded_file($tmp_name, $caminho_destino)) {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO anexos (chamado_id, usuario_id, nome_original, caminho, tipo, tamanho)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$id, $usuario_id, $nome_original, $novo_nome, $mime, $tamanho]);
        
        $stmt = $pdo->prepare("
            INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_anterior, status_novo, descricao)
            VALUES (?, ?, 'ANEXO', ?, ?, ?)
        ");
        $desc = "Adicionou um anexo: " . $nome_original;
        $stmt->execute([$id, $usuario_id, $chamado['status'], $chamado['status'], $desc]);
        
        $pdo->commit();
        
        $_SESSION['msg'] = "Anexo enviado com sucesso.";
    } catch (Exception $e) {
        $pdo->rollBack();
        unlink($caminho_destino); // Remove arquivo se falhar no banco
        $_SESSION['msg_erro'] = "Erro ao salvar anexo no banco de dados.";
    }
} else {
    $_SESSION['msg_erro'] = "Erro ao mover o arquivo para a pasta de uploads.";
}

redirect("/chamados/ver.php?id=$id");
