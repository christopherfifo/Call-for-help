<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth(); // Usuários, Técnicos, Admins, DEVs podem acessar (mas geralmente Admin/Usuario abre)

$pdo = getConnection();

// Busca categorias ativas
$stmt = $pdo->query("SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome ASC");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Se for Admin/DEV, pode abrir em nome de outro usuário
$is_admin = in_array($_SESSION['usuario_cargo'], ['ADMINISTRADOR', 'DEV']);
$usuarios_combo = [];
if ($is_admin) {
    $stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome ASC");
    $usuarios_combo = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assunto = trim($_POST['assunto'] ?? '');
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $urgencia = $_POST['urgencia'] ?? 'LEVE';
    $justificativa_urgencia = trim($_POST['justificativa_urgencia'] ?? '');
    
    // Quem está solicitando o chamado?
    $usuario_solicitante_id = $_SESSION['usuario_id'];
    if ($is_admin && !empty($_POST['usuario_id'])) {
        $usuario_solicitante_id = (int)$_POST['usuario_id'];
    }

    if (empty($assunto) || empty($categoria_id) || empty($descricao) || empty($justificativa_urgencia)) {
        $erro = "Todos os campos são obrigatórios, incluindo a justificativa da urgência.";
    } else {
        // Gerar número automático do chamado (ex: ANO-MÊS-ID_SEQUENCIAL ou apenas um hash)
        // Para garantir o sequencial sem falhas, criamos um registro provisório ou usamos MAX(id) + 1
        // Forma simples: YYYYMMDD-xxxx
        $hoje = date('Ymd');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM chamados WHERE DATE(criado_em) = CURDATE()");
        $stmt->execute();
        $count = $stmt->fetchColumn() + 1;
        $numero_chamado = "CHM-" . $hoje . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);

        try {
            $pdo->beginTransaction();
            
            // Inserir chamado
            $stmt = $pdo->prepare("
                INSERT INTO chamados (numero, usuario_id, categoria_id, assunto, descricao, status, urgencia, justificativa_urgencia) 
                VALUES (?, ?, ?, ?, ?, 'ABERTO', ?, ?)
            ");
            $stmt->execute([$numero_chamado, $usuario_solicitante_id, $categoria_id, $assunto, $descricao, $urgencia, $justificativa_urgencia]);
            
            $chamado_id = $pdo->lastInsertId();
            
            // Inserir histórico
            $stmt = $pdo->prepare("
                INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_novo, descricao) 
                VALUES (?, ?, 'ABERTURA', 'ABERTO', ?)
            ");
            
            if ($usuario_solicitante_id !== $_SESSION['usuario_id']) {
                $desc_hist = "Chamado aberto por " . $_SESSION['usuario_nome'] . " em nome do usuário solicitante.";
            } else {
                $desc_hist = "Chamado aberto pelo usuário.";
            }
            
            $stmt->execute([$chamado_id, $_SESSION['usuario_id'], $desc_hist]);
            
            $pdo->commit();
            
            $_SESSION['msg'] = "Chamado aberto com sucesso! Número: $numero_chamado";
            redirect('/chamados/index.php'); // Redireciona para listagem (a ser criada na task 09)
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao abrir chamado: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Abrir Novo Chamado</h1>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?php if ($is_admin): ?>
            <div class="mb-3">
                <label class="form-label text-danger fw-bold">Abrir em nome do usuário (Apenas Admin/DEV)</label>
                <select class="form-select" name="usuario_id">
                    <option value="<?= $_SESSION['usuario_id'] ?>">-- Eu mesmo --</option>
                    <?php foreach ($usuarios_combo as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

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
                <input type="text" class="form-control" name="assunto" required placeholder="Ex: Problema de rede na recepção">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Descrição Detalhada</label>
                <textarea class="form-control" name="descricao" rows="4" required placeholder="Descreva o problema com o máximo de detalhes..."></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Justificativa da Urgência <span class="text-danger">*</span></label>
                <textarea class="form-control" name="justificativa_urgencia" rows="2" required placeholder="Por que essa urgência foi selecionada? Ex: Impede o atendimento ao público."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Abrir Chamado</button>
            <a href="/chamados/" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
