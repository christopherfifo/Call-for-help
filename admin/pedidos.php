<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();
if (!in_array($_SESSION['usuario_cargo'], ['ADMINISTRADOR', 'DEV'])) {
    redirect('/errors/unauthorized.php');
}

$pdo = getConnection();

// Tratar ações
if (isset($_GET['acao']) && isset($_GET['id'])) {
    $acao = $_GET['acao']; // 'aprovar' ou 'recusar'
    $pedido_id = (int)$_GET['id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $pdo->prepare("SELECT * FROM pedidos_administrativos WHERE id = ? AND status = 'PENDENTE'");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pedido) {
        $novo_status = ($acao === 'aprovar') ? 'APROVADO' : 'RECUSADO';
        $agora = date('Y-m-d H:i:s');
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE pedidos_administrativos SET status = ?, atendido_por = ?, atendido_em = ?, atendente_ip = ? WHERE id = ?");
            $stmt->execute([$novo_status, $_SESSION['usuario_id'], $agora, $ip, $pedido_id]);
            
            $msg_usuario = "";
            if ($pedido['tipo'] === 'RESET_SENHA') {
                if ($novo_status === 'APROVADO') {
                    // Resetar senha para a matrícula
                    $stmt = $pdo->prepare("SELECT matricula FROM usuarios WHERE id = ?");
                    $stmt->execute([$pedido['usuario_id']]);
                    $matricula = $stmt->fetchColumn();
                    
                    $senha_hash = password_hash($matricula, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, primeiro_acesso = 1 WHERE id = ?");
                    $stmt->execute([$senha_hash, $pedido['usuario_id']]);
                    
                    $msg_usuario = "Seu pedido de reset de senha foi APROVADO. Sua nova senha provisória é sua matrícula.";
                } else {
                    $msg_usuario = "Seu pedido de reset de senha foi RECUSADO.";
                }
            } elseif ($pedido['tipo'] === 'CANCELAMENTO_CHAMADO') {
                if ($novo_status === 'APROVADO') {
                    $stmt = $pdo->prepare("UPDATE chamados SET status = 'CANCELADO', cancelado_em = ?, cancelado_por = ? WHERE id = ?");
                    $stmt->execute([$agora, $_SESSION['usuario_id'], $pedido['referencia_id']]);
                    
                    $desc = "Chamado cancelado via aprovação administrativa.";
                    $stmt = $pdo->prepare("
                        INSERT INTO historico_chamados (chamado_id, usuario_id, acao, status_novo, descricao, ip)
                        VALUES (?, ?, 'CANCELAMENTO_APROVADO', 'CANCELADO', ?, ?)
                    ");
                    $stmt->execute([$pedido['referencia_id'], $_SESSION['usuario_id'], $desc, $ip]);
                    
                    $msg_usuario = "Seu pedido de cancelamento do chamado ID {$pedido['referencia_id']} foi APROVADO.";
                } else {
                    $msg_usuario = "Seu pedido de cancelamento do chamado ID {$pedido['referencia_id']} foi RECUSADO.";
                }
            }
            
            // Notificar usuário original
            $stmt = $pdo->prepare("INSERT INTO notificacoes (usuario_id, tipo, mensagem, referencia_id, referencia_tipo) VALUES (?, 'DECISAO_PEDIDO', ?, ?, 'pedido')");
            $stmt->execute([$pedido['usuario_id'], $msg_usuario, $pedido_id]);
            
            // Auditoria
            $stmt = $pdo->prepare("INSERT INTO logs_sistema (usuario_id, acao, descricao, ip) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['usuario_id'], 'AVALIAR_PEDIDO', "Avaliando pedido $pedido_id para $novo_status", $ip]);
            
            $pdo->commit();
            $_SESSION['msg'] = "Pedido {$novo_status} com sucesso.";
            redirect('/admin/pedidos.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['msg_erro'] = "Erro: " . $e->getMessage();
            redirect('/admin/pedidos.php');
        }
    }
}

// Listar pedidos pendentes
$stmt = $pdo->query("
    SELECT p.*, u.nome, u.matricula, c.numero as chamado_numero
    FROM pedidos_administrativos p
    JOIN usuarios u ON p.usuario_id = u.id
    LEFT JOIN chamados c ON p.referencia_id = c.id
    WHERE p.status = 'PENDENTE'
    ORDER BY p.criado_em ASC
");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Central de Pedidos Administrativos</h1>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body table-responsive">
        <?php if(isset($_SESSION['msg'])): ?>
            <div class="alert alert-success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['msg_erro'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['msg_erro']; unset($_SESSION['msg_erro']); ?></div>
        <?php endif; ?>

        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Data/Hora</th>
                    <th>Solicitante</th>
                    <th>Tipo</th>
                    <th>Referência</th>
                    <th>Justificativa</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pedidos) === 0): ?>
                    <tr><td colspan="6" class="text-center">Nenhum pedido pendente no momento.</td></tr>
                <?php else: ?>
                    <?php foreach ($pedidos as $p): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td>
                            <td><?= htmlspecialchars($p['nome']) ?> (<?= htmlspecialchars($p['matricula']) ?>)</td>
                            <td><?= htmlspecialchars($p['tipo']) ?></td>
                            <td><?= $p['chamado_numero'] ? 'Chamado: ' . htmlspecialchars($p['chamado_numero']) : '-' ?></td>
                            <td class="text-wrap" style="max-width: 300px;"><?= htmlspecialchars($p['justificativa']) ?></td>
                            <td>
                                <a href="?acao=aprovar&id=<?= $p['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Confirmar aprovação?');"><i class="bi bi-check"></i> Aprovar</a>
                                <a href="?acao=recusar&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Confirmar recusa?');"><i class="bi bi-x"></i> Recusar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
