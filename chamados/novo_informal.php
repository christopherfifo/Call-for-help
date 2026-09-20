<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();
if (!in_array($_SESSION['usuario_cargo'], ['TECNICO', 'ADMINISTRADOR', 'DEV'])) {
    redirect('/errors/unauthorized.php');
}

$pdo = getConnection();

$stmt = $pdo->query("SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome ASC");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, nome, matricula FROM usuarios WHERE ativo = 1 ORDER BY nome ASC");
$usuarios_combo = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assunto = trim($_POST['assunto'] ?? '');
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $urgencia = $_POST['urgencia'] ?? 'LEVE';
    $justificativa_urgencia = trim($_POST['justificativa_urgencia'] ?? '');
    $usuario_solicitante_id = (int)($_POST['usuario_id'] ?? 0);

    if (empty($assunto) || empty($categoria_id) || empty($descricao) || empty($justificativa_urgencia) || empty($usuario_solicitante_id)) {
        $erro = "Todos os campos são obrigatórios.";
    } else {
        $hoje = date('Ymd');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM chamados WHERE DATE(criado_em) = CURDATE()");
        $stmt->execute();
        $count = $stmt->fetchColumn() + 1;
        $numero_chamado = "INF-" . $hoje . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);

        try {
            $pdo->beginTransaction();
            $ip = $_SERVER['REMOTE_ADDR'];
            
            $stmt = $pdo->prepare("
                INSERT INTO chamados (numero, usuario_id, tecnico_id, categoria_id, assunto, descricao, status, urgencia, justificativa_urgencia, tipo, criado_por) 
                VALUES (?, ?, ?, ?, ?, ?, 'ABERTO', ?, ?, 'INFORMAL', ?)
            ");
            $stmt->execute([$numero_chamado, $usuario_solicitante_id, $_SESSION['usuario_id'], $categoria_id, $assunto, $descricao, $urgencia, $justificativa_urgencia, $_SESSION['usuario_id']]);
            $chamado_id = $pdo->lastInsertId();
            
            $desc_hist = "Chamado informal registrado pelo técnico " . $_SESSION['usuario_nome'] . " em nome do usuário.";
            $stmt = $pdo->prepare("
                INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_novo, descricao, ip) 
                VALUES (?, ?, 'ABERTURA_INFORMAL', 'ABERTO', ?, ?)
            ");
            $stmt->execute([$chamado_id, $_SESSION['usuario_id'], $desc_hist, $ip]);
            
            // Notificar usuário
            $stmt = $pdo->prepare("INSERT INTO notificacoes (usuario_id, tipo, mensagem, referencia_id, referencia_tipo) VALUES (?, 'CHAMADO_INFORMAL', ?, ?, 'chamado')");
            $msg = "Um chamado informal ($numero_chamado) foi registrado em seu nome por " . $_SESSION['usuario_nome'];
            $stmt->execute([$usuario_solicitante_id, $msg, $chamado_id]);
            
            // Log auditoria
            $stmt = $pdo->prepare("INSERT INTO logs_sistema (usuario_id, acao, descricao, ip) VALUES (?, 'CRIAR_CHAMADO_INFORMAL', ?, ?)");
            $stmt->execute([$_SESSION['usuario_id'], "Criou chamado informal ID $chamado_id ($numero_chamado) para usuário $usuario_solicitante_id", $ip]);
            
            $pdo->commit();
            $_SESSION['msg'] = "Chamado informal registrado com sucesso! Número: $numero_chamado";
            redirect('/chamados/index.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao registrar chamado informal: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Registrar Chamado Informal</h1>
</div>

<div class="card shadow-sm border-0 border-info">
    <div class="card-header bg-info text-dark fw-bold">
        <i class="bi bi-info-circle"></i> Registro de solicitação feita fora do sistema
    </div>
    <div class="card-body">
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Usuário Solicitante</label>
                <select class="form-select" name="usuario_id" required>
                    <option value="">Selecione quem fez a solicitação...</option>
                    <?php foreach ($usuarios_combo as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nome']) ?> (<?= htmlspecialchars($u['matricula']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" name="categoria_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Urgência</label>
                    <select class="form-select" name="urgencia" required>
                        <option value="LEVE">Leve</option>
                        <option value="MODERADA">Moderada</option>
                        <option value="ALTA">Alta</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Assunto / Motivo</label>
                <input type="text" class="form-control" name="assunto" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Descrição Detalhada</label>
                <textarea class="form-control" name="descricao" rows="4" required></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Justificativa da Urgência</label>
                <textarea class="form-control" name="justificativa_urgencia" rows="2" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-info"><i class="bi bi-save"></i> Registrar</button>
            <a href="/chamados/" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>