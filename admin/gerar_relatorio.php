<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

checkAuth();

$cargo = $_SESSION['usuario_cargo'];
if (!in_array($cargo, ['ADMINISTRADOR', 'DEV'])) {
    die("Acesso negado.");
}

$pdo = getConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    die("Chamado não informado.");
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
    die("Chamado não encontrado.");
}

// Histórico
$stmt = $pdo->prepare("
    SELECT h.*, u.nome as usuario_nome 
    FROM historico_chamados h
    JOIN usuarios u ON h.usuario_id = u.id
    WHERE h.chamado_id = ?
    ORDER BY h.criado_em ASC
");
$stmt->execute([$id]);
$historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Atualizações
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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório do Chamado <?= htmlspecialchars($chamado['numero']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #555; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 16px; font-weight: bold; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 10px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f5f5f5; width: 25%; }
        .timeline-item { margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #eee; }
        .timeline-item:last-child { border-bottom: none; }
        .timeline-date { font-weight: bold; color: #666; }
        .badge { padding: 3px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .badge-alta { background: #dc3545; color: #fff; }
        .badge-moderada { background: #ffc107; color: #000; }
        .badge-leve { background: #28a745; color: #fff; }
        .badge-aberto { background: #0d6efd; color: #fff; }
        .badge-andamento { background: #17a2b8; color: #fff; }
        .badge-pausado { background: #ffc107; color: #000; }
        .badge-finalizado { background: #28a745; color: #fff; }
        .badge-cancelado { background: #dc3545; color: #fff; }
        @media print {
            body { padding: 0; }
            button { display: none; }
        }
    </style>
    <!-- html2pdf library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <button onclick="downloadPDF()" style="margin-bottom: 20px; padding: 10px 15px; background: #000; color: #fff; border: none; cursor: pointer; border-radius: 4px;">
        📥 Baixar PDF
    </button>
    <button onclick="window.print()" style="margin-bottom: 20px; margin-left: 10px; padding: 10px 15px; background: #6c757d; color: #fff; border: none; cursor: pointer; border-radius: 4px;">
        🖨️ Imprimir
    </button>

    <div id="relatorio-conteudo">
        <div class="header">
            <h1>Relatório de Chamado TI</h1>
            <p>Gerado em <?= date('d/m/Y H:i:s') ?> por <?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>
        </div>

        <div class="section">
            <div class="section-title">Identificação do Chamado</div>
            <table>
                <tr>
                    <th>Número</th>
                    <td><strong><?= htmlspecialchars($chamado['numero']) ?></strong></td>
                    <th>Status</th>
                    <td><?= htmlspecialchars($chamado['status']) ?></td>
                </tr>
                <tr>
                    <th>Solicitante</th>
                    <td><?= htmlspecialchars($chamado['solicitante']) ?></td>
                    <th>E-mail</th>
                    <td><?= htmlspecialchars($chamado['solicitante_email']) ?></td>
                </tr>
                <tr>
                    <th>Categoria</th>
                    <td><?= htmlspecialchars($chamado['categoria']) ?></td>
                    <th>Data Abertura</th>
                    <td><?= date('d/m/Y H:i', strtotime($chamado['criado_em'])) ?></td>
                </tr>
                <tr>
                    <th>Técnico Responsável</th>
                    <td><?= htmlspecialchars($chamado['tecnico'] ?? 'Nenhum') ?></td>
                    <th>Data Finalização</th>
                    <td><?= $chamado['finalizado_em'] ? date('d/m/Y H:i', strtotime($chamado['finalizado_em'])) : '-' ?></td>
                </tr>
                <tr>
                    <th>Prazo Definido</th>
                    <td colspan="3">
                        <?php 
                        if ($chamado['prazo']) echo date('d/m/Y H:i', strtotime($chamado['prazo']));
                        elseif ($chamado['prazo_indeterminado']) echo 'Indeterminado';
                        else echo '-';
                        ?>
                    </td>
                </tr>
                <?php if ($chamado['justificativa_prazo']): ?>
                <tr>
                    <th>Justificativa do Prazo</th>
                    <td colspan="3"><?= nl2br(htmlspecialchars($chamado['justificativa_prazo'])) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Urgência</th>
                    <td><?= htmlspecialchars($chamado['urgencia']) ?></td>
                    <th>Justificativa Urgência</th>
                    <td><?= nl2br(htmlspecialchars($chamado['justificativa_urgencia'])) ?></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Detalhes da Solicitação</div>
            <table>
                <tr>
                    <th>Assunto</th>
                    <td><?= htmlspecialchars($chamado['assunto']) ?></td>
                </tr>
                <tr>
                    <th>Descrição</th>
                    <td><?= nl2br(htmlspecialchars($chamado['descricao'])) ?></td>
                </tr>
            </table>
        </div>

        <?php if ($chamado['relatorio_final']): ?>
        <div class="section">
            <div class="section-title">Relatório Final Técnico</div>
            <div style="background: #fdfdfd; border: 1px solid #ccc; padding: 10px; margin-bottom: 15px;">
                <?= nl2br(htmlspecialchars($chamado['relatorio_final'])) ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($chamado['cancelado_em']): ?>
        <div class="section">
            <div class="section-title text-danger">Informações de Cancelamento</div>
            <table>
                <tr>
                    <th>Cancelado em</th>
                    <td><?= date('d/m/Y H:i', strtotime($chamado['cancelado_em'])) ?></td>
                </tr>
                <tr>
                    <th>Cancelado por (ID)</th>
                    <td><?= htmlspecialchars($chamado['cancelado_por']) ?></td>
                </tr>
            </table>
        </div>
        <?php endif; ?>

        <?php if (count($atualizacoes) > 0): ?>
        <div class="section">
            <div class="section-title">Atualizações / Comentários</div>
            <?php foreach ($atualizacoes as $a): ?>
                <div class="timeline-item">
                    <span class="timeline-date"><?= date('d/m/Y H:i', strtotime($a['criado_em'])) ?></span> - 
                    <strong><?= htmlspecialchars($a['usuario_nome']) ?> (<?= htmlspecialchars($a['cargo']) ?>)</strong>
                    <div style="margin-top: 5px;"><?= nl2br(htmlspecialchars($a['comentario'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="section">
            <div class="section-title">Histórico de Auditoria</div>
            <?php foreach ($historico as $h): ?>
                <div class="timeline-item">
                    <span class="timeline-date"><?= date('d/m/Y H:i:s', strtotime($h['criado_em'])) ?></span> - 
                    <strong><?= htmlspecialchars($h['usuario_nome']) ?></strong> realizou a ação <strong><?= htmlspecialchars($h['acao']) ?></strong>
                    <div style="margin-top: 5px; color: #555;"><?= nl2br(htmlspecialchars($h['descricao'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
    </div>

    <script>
        function downloadPDF() {
            var element = document.getElementById('relatorio-conteudo');
            var opt = {
                margin:       10,
                filename:     'relatorio_chamado_<?= htmlspecialchars($chamado['numero']) ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
