<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

$pdo = getConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    redirect('/chamados/');
}

$stmt = $pdo->prepare("
    SELECT c.*, u.nome as solicitante, u.email as solicitante_email, t.nome as tecnico, cat.nome as categoria 
    FROM chamados c
    JOIN usuarios u ON c.usuario_id = u.id
    LEFT JOIN usuarios t ON c.tecnico_id = t.id
    JOIN categorias cat ON c.categoria_id = cat.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chamado) {
    redirect('/chamados/');
}

// Controle de acesso: Usuário só vê o seu; Técnico vê o atribuído a ele OU na fila (tecnico_id NULL); Admin/DEV vê tudo.
$cargo = $_SESSION['usuario_cargo'];

if ($cargo === 'USUARIO' && $chamado['usuario_id'] != $_SESSION['usuario_id']) {
    die("Você não tem permissão para visualizar este chamado.");
}

if ($cargo === 'TECNICO') {
    $eh_da_fila      = ($chamado['tecnico_id'] === null);
    $eh_atribuido    = ($chamado['tecnico_id'] == $_SESSION['usuario_id']);
    if (!$eh_da_fila && !$eh_atribuido) {
        die("Você não tem permissão para visualizar este chamado.");
    }
}

// Busca o histórico (Timeline)
$stmt = $pdo->prepare("
    SELECT h.*, u.nome as usuario_nome 
    FROM historico_chamados h
    JOIN usuarios u ON h.usuario_id = u.id
    WHERE h.chamado_id = ?
    ORDER BY h.criado_em DESC
");
$stmt->execute([$id]);
$historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca anexos
$stmt = $pdo->prepare("
    SELECT a.*, u.nome as usuario_nome 
    FROM anexos a
    JOIN usuarios u ON a.usuario_id = u.id
    WHERE a.chamado_id = ?
    ORDER BY a.criado_em ASC
");
$stmt->execute([$id]);
$anexos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Urgência efetiva e histórico de intervenções ──────────────────────────
$urgencia_efetiva   = getUrgenciaEfetiva($pdo, $chamado);
$urgencia_sobrescrita = ($urgencia_efetiva !== $chamado['urgencia']);
$historico_urgencia = getUrgenciaHistorico($pdo, $id);

// Intervenção ativa do usuário logado (para o botão de ação)
$stmt = $pdo->prepare("
    SELECT id, urgencia FROM urgencia_chamados
    WHERE chamado_id = ? AND usuario_id = ? AND ativo = 1
");
$stmt->execute([$id, $_SESSION['usuario_id']]);
$minha_intervencao = $stmt->fetch(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';

function badgeStatus($status) {
    switch ($status) {
        case 'ABERTO': return '<span class="badge bg-primary fs-6">Aberto</span>';
        case 'EM_ANDAMENTO': return '<span class="badge bg-info text-dark fs-6">Em Andamento</span>';
        case 'PAUSADO': return '<span class="badge bg-warning text-dark fs-6">Pausado</span>';
        case 'FINALIZADO': return '<span class="badge bg-success fs-6">Finalizado</span>';
        case 'CANCELADO': return '<span class="badge bg-danger fs-6">Cancelado</span>';
        default: return '<span class="badge bg-secondary fs-6">Indefinido</span>';
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Chamado: <?= htmlspecialchars($chamado['numero']) ?> <?= (isset($chamado['tipo']) && $chamado['tipo'] === 'INFORMAL') ? '<span class="badge bg-info text-dark fs-6 align-middle">Informal</span>' : '' ?></h1>
    <a href="/chamados/" class="btn btn-secondary">Voltar</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><?= htmlspecialchars($chamado['assunto']) ?></h5>
                <div>
                    <?php if ($chamado['tag_repassado']): ?>
                        <span class="badge bg-secondary fs-6 me-1">Repassado</span>
                    <?php endif; ?>
                    <?php if ($chamado['tag_reaberto']): ?>
                        <span class="badge bg-warning text-dark fs-6 me-1">Reaberto</span>
                    <?php endif; ?>
                    <?= badgeStatus($chamado['status']) ?>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="fw-bold text-muted border-bottom pb-2">Descrição</h6>
                    <p class="mt-3"><?= nl2br(htmlspecialchars($chamado['descricao'])) ?></p>
                </div>
                
                <?php if ($chamado['relatorio_final']): ?>
                <div class="alert alert-success mt-4">
                    <h6 class="alert-heading fw-bold">Relatório Final</h6>
                    <hr>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($chamado['relatorio_final'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history"></i> Histórico do Chamado</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($historico as $h): ?>
                    <div class="mb-3 border-start border-3 border-secondary ps-3 py-1">
                        <div class="d-flex justify-content-between">
                            <strong><?= htmlspecialchars($h['usuario_nome']) ?></strong>
                            <small class="text-muted"><?= date('d/m/Y H:i:s', strtotime($h['criado_em'])) ?></small>
                        </div>
                        <p class="mb-0 mt-1"><?= htmlspecialchars($h['descricao']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Anexos -->
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-paperclip"></i> Anexos</h5>
                <?php if (!in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAnexo">
                        <i class="bi bi-upload"></i> Novo Anexo
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (count($anexos) === 0): ?>
                    <p class="text-muted mb-0">Nenhum anexo encontrado.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Arquivo</th>
                                    <th>Tamanho</th>
                                    <th>Enviado por</th>
                                    <th>Data</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($anexos as $anexo): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($anexo['nome_original']) ?></td>
                                        <td><?= round($anexo['tamanho'] / 1024, 2) ?> KB</td>
                                        <td><?= htmlspecialchars($anexo['usuario_nome']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($anexo['criado_em'])) ?></td>
                                        <td>
                                            <a href="/chamados/download.php?id=<?= $anexo['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">
                                                <i class="bi bi-download"></i> Baixar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Atualizações (Comentários) -->
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-chat-dots"></i> Atualizações do Chamado</h5>
            </div>
            <div class="card-body">
                <?php
                // Fetch atualizações
                $stmt = $pdo->prepare("
                    SELECT c.*, u.nome as usuario_nome, u.cargo
                    FROM comentarios_chamados c
                    JOIN usuarios u ON c.usuario_id = u.id
                    WHERE c.chamado_id = ?
                    ORDER BY c.criado_em ASC
                ");
                $stmt->execute([$id]);
                $atualizacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if (count($atualizacoes) === 0): ?>
                    <p class="text-muted mb-4">Nenhuma atualização registrada.</p>
                <?php else: ?>
                    <div class="mb-4">
                        <?php foreach ($atualizacoes as $a): ?>
                            <div class="card mb-3 <?= $a['usuario_id'] == $chamado['usuario_id'] ? 'border-primary' : 'border-info' ?>">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                    <span class="fw-bold"><?= htmlspecialchars($a['usuario_nome']) ?> <small class="text-muted fw-normal">(<?= htmlspecialchars($a['cargo']) ?>)</small></span>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($a['criado_em'])) ?></small>
                                </div>
                                <div class="card-body py-2">
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($a['comentario'])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])): ?>
                    <form method="POST" action="/chamados/atualizar.php">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Adicionar Atualização</label>
                            <textarea name="comentario" class="form-control" rows="3" required placeholder="Digite sua atualização ou comentário aqui..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Enviar Atualização</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h6 class="fw-bold border-bottom pb-2">Detalhes</h6>
                <ul class="list-unstyled mt-3">
                    <li class="mb-2"><strong>Solicitante:</strong> <?= htmlspecialchars($chamado['solicitante']) ?></li>
                    <li class="mb-2"><strong>Categoria:</strong> <?= htmlspecialchars($chamado['categoria']) ?></li>
                    <li class="mb-2"><strong>Técnico:</strong> <?= htmlspecialchars($chamado['tecnico'] ?? 'Não atribuído') ?></li>
                    <li class="mb-2">
                        <strong>Urgência:</strong>
                        <?= badgeUrgencia($urgencia_efetiva) ?>
                        <?php if ($urgencia_sobrescrita): ?>
                            <span class="badge bg-warning text-dark ms-1" title="Urgência original: <?= htmlspecialchars($chamado['urgencia']) ?>">
                                <i class="bi bi-exclamation-triangle"></i> Sobrescrita
                            </span>
                        <?php endif; ?>
                    </li>
                    <?php if ($urgencia_sobrescrita): ?>
                    <li class="mb-2">
                        <strong>Urgência original (usuário):</strong>
                        <?= badgeUrgencia($chamado['urgencia'], true) ?>
                    </li>
                    <?php endif; ?>
                    <li class="mb-2"><strong>Justificativa (Urgência):</strong><br><small class="text-muted"><?= htmlspecialchars($chamado['justificativa_urgencia']) ?></small></li>
                    <li class="mb-2"><strong>Abertura:</strong> <?= date('d/m/Y H:i', strtotime($chamado['criado_em'])) ?></li>
                    <?php if ($chamado['prazo']): ?>
                        <li class="mb-2">
                            <strong>Prazo:</strong> <?= date('d/m/Y H:i', strtotime($chamado['prazo'])) ?>
                            <?php 
                            if (!in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])) {
                                $prazo_ts = strtotime($chamado['prazo']);
                                $agora = time();
                                $diferenca = $prazo_ts - $agora;
                                if ($diferenca < 0) echo '<span class="badge bg-danger ms-1">🔴 Vencido</span>';
                                elseif ($diferenca < 86400) echo '<span class="badge bg-warning text-dark ms-1">🟡 Próximo</span>';
                                else echo '<span class="badge bg-success ms-1">🟢 No Prazo</span>';
                            }
                            ?>
                        </li>
                    <?php elseif ($chamado['prazo_indeterminado']): ?>
                        <li class="mb-2"><strong>Prazo:</strong> <span class="text-warning fw-bold">Indeterminado</span></li>
                    <?php endif; ?>
                </ul>
                
                <hr>
                
                <!-- Ações do Chamado -->
                <div class="d-grid gap-2 mt-4">
                    <?php if (isset($_SESSION['msg_erro'])): ?>
                        <div class="alert alert-danger p-2 mb-2"><?= htmlspecialchars($_SESSION['msg_erro']); unset($_SESSION['msg_erro']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (in_array($_SESSION['usuario_cargo'], ['TECNICO', 'ADMINISTRADOR', 'DEV'])): ?>
                        
                        <?php if ($chamado['status'] === 'ABERTO'): ?>
                            <a href="/chamados/assumir.php?id=<?= $id ?>" class="btn btn-success"><i class="bi bi-play-circle"></i> Assumir Chamado</a>
                        <?php endif; ?>
                        
                        <?php if ($chamado['status'] === 'EM_ANDAMENTO'): ?>
                            <a href="/chamados/pausar.php?id=<?= $id ?>" class="btn btn-warning text-dark"><i class="bi bi-pause-circle"></i> Pausar</a>
                            <a href="/chamados/repassar.php?id=<?= $id ?>" class="btn btn-secondary"><i class="bi bi-arrow-right-circle"></i> Repassar</a>
                            <a href="/chamados/finalizar.php?id=<?= $id ?>" class="btn btn-primary"><i class="bi bi-check-circle"></i> Finalizar</a>
                        <?php endif; ?>

                        <?php if ($chamado['status'] === 'PAUSADO'): ?>
                            <form method="POST" action="/chamados/retomar.php" class="d-grid">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-play-fill"></i> Retomar Atendimento</button>
                            </form>
                        <?php endif; ?>
                        
                    <?php endif; ?>

                    <?php 
                    // Regra de Cancelamento: Dono ou Admin, desde que não esteja Cancelado/Finalizado
                    $pode_cancelar = false;
                    if (!in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])) {
                        if ($_SESSION['usuario_id'] == $chamado['usuario_id'] || in_array($_SESSION['usuario_cargo'], ['ADMINISTRADOR', 'DEV'])) {
                            $pode_cancelar = true;
                        }
                    }
                    ?>
                    <?php if ($pode_cancelar): ?>
                        <a href="/chamados/cancelar.php?id=<?= $id ?>" class="btn btn-danger mt-2"><i class="bi bi-x-octagon"></i> Cancelar Chamado</a>
                    <?php endif; ?>

                    <?php 
                    // Regra de Reabertura: Somente Admin/DEV, apenas para chamados FINALIZADO
                    if ($chamado['status'] === 'FINALIZADO' && in_array($_SESSION['usuario_cargo'], ['ADMINISTRADOR', 'DEV'])): 
                    ?>
                        <a href="/chamados/reabrir.php?id=<?= $id ?>" class="btn btn-warning text-dark mt-2"><i class="bi bi-arrow-counterclockwise"></i> Reabrir Chamado</a>
                    <?php endif; ?>

                    <?php 
                    // Técnico, Admin e DEV podem intervir na urgência
                    if (!in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])
                        && in_array($_SESSION['usuario_cargo'], ['TECNICO', 'ADMINISTRADOR', 'DEV'])): 
                    ?>
                        <a href="/chamados/urgencia.php?id=<?= $id ?>" class="btn btn-info text-white mt-2">
                            <i class="bi bi-exclamation-triangle"></i>
                            <?= $minha_intervencao ? 'Editar minha urgência' : 'Intervir na Urgência' ?>
                        </a>
                        <?php if ($minha_intervencao): ?>
                            <a href="/chamados/urgencia.php?id=<?= $id ?>&revogar=1"
                               class="btn btn-outline-danger mt-2"
                               onclick="return confirm('Revogar sua intervenção de urgência?')">
                                <i class="bi bi-x-circle"></i> Revogar urgência
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <button type="button" class="btn btn-dark mt-2" data-bs-toggle="modal" data-bs-target="#modalSolicitarRelatorio">
                        <i class="bi bi-file-earmark-pdf"></i> Solicitar Relatório
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!in_array($chamado['status'], ['FINALIZADO', 'CANCELADO'])): ?>
<!-- Modal Novo Anexo -->
<div class="modal fade" id="modalAnexo" tabindex="-1" aria-labelledby="modalAnexoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/chamados/anexar.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="modalAnexoLabel">Novo Anexo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Selecione o arquivo</label>
                <input type="file" name="arquivo" class="form-control" required>
                <div class="form-text">Extensões permitidas: jpg, jpeg, png, gif, pdf, doc, docx, txt. Tamanho máximo: 5MB.</div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Fazer Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Modal Solicitar Relatório -->
<div class="modal fade" id="modalSolicitarRelatorio" tabindex="-1" aria-labelledby="modalSolicitarRelatorioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/chamados/solicitar_relatorio.php" method="POST">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="modalSolicitarRelatorioLabel">Solicitar Relatório</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info">
                Um administrador avaliará sua solicitação e, se aprovada, gerará o PDF do relatório.
            </div>
            <div class="mb-3">
                <label class="form-label">Motivo da Solicitação <span class="text-danger">*</span></label>
                <textarea name="motivo" class="form-control" rows="3" required placeholder="Por que você precisa deste relatório?"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Observação (Opcional)</label>
                <textarea name="observacao" class="form-control" rows="2" placeholder="Detalhes adicionais..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-dark">Enviar Solicitação</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if ($historico_urgencia): ?>
<!-- Histórico de intervenções de urgência -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-warning">
            <div class="card-header bg-warning bg-opacity-10 fw-bold py-3">
                <i class="bi bi-exclamation-triangle text-warning"></i> Intervenções de Urgência
                <span class="badge bg-secondary ms-2"><?= count($historico_urgencia) ?></span>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Autor</th>
                            <th>Perfil</th>
                            <th>Urgência</th>
                            <th>Status</th>
                            <th>Justificativa</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico_urgencia as $h): ?>
                        <tr class="<?= $h['ativo'] ? '' : 'text-muted text-decoration-line-through' ?>">
                            <td><?= htmlspecialchars($h['autor_nome']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($h['cargo_autor']) ?></span></td>
                            <td><?= badgeUrgencia($h['urgencia'], true) ?></td>
                            <td>
                                <?= $h['ativo']
                                    ? '<span class="badge bg-success">Ativa</span>'
                                    : '<span class="badge bg-secondary">Revogada</span>' ?>
                            </td>
                            <td class="text-wrap" style="max-width:300px;">
                                <?= htmlspecialchars($h['justificativa']) ?>
                            </td>
                            <td><small><?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>