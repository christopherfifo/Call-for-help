<?php
/**
 * config/rate_limit.php
 *
 * Rate limiting por IP + ação usando tabela no banco de dados.
 * Funciona independente de sessão ou cookie — resiste a brute force real.
 *
 * Uso:
 *   checkRateLimit('login', 5, 300);        // máx 5 tentativas em 5 min
 *   checkRateLimit('reset_senha', 3, 900);  // máx 3 tentativas em 15 min
 *   resetRateLimit('login');                // zera após sucesso
 */

/**
 * Verifica e incrementa o contador de tentativas para uma ação + IP.
 * Bloqueia com HTTP 429 se o limite for excedido.
 *
 * @param string $acao       Identificador da ação ('login', 'reset_senha', etc.)
 * @param int    $max        Máximo de tentativas na janela
 * @param int    $janela_seg Janela de tempo em segundos
 */
function checkRateLimit(string $acao, int $max, int $janela_seg): void
{
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $agora = new DateTime();

    try {
        $pdo = getConnection();

        // Limpa registros antigos da mesma linha se a janela já expirou
        // (reseta o contador automaticamente após a janela)
        $pdo->prepare("
            DELETE FROM rate_limit
            WHERE ip = ? AND acao = ?
              AND bloqueado_ate IS NULL
              AND primeira_em < DATE_SUB(NOW(), INTERVAL ? SECOND)
        ")->execute([$ip, $acao, $janela_seg]);

        // Busca registro atual
        $stmt = $pdo->prepare("
            SELECT tentativas, primeira_em, bloqueado_ate
            FROM rate_limit
            WHERE ip = ? AND acao = ?
        ");
        $stmt->execute([$ip, $acao]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        // ── Verifica se está em período de bloqueio ───────────────────────
        if ($registro && $registro['bloqueado_ate'] !== null) {
            $bloqueado_ate = new DateTime($registro['bloqueado_ate']);

            if ($agora < $bloqueado_ate) {
                // Ainda bloqueado
                $restante = $agora->diff($bloqueado_ate);
                $segundos_restantes = ($bloqueado_ate->getTimestamp() - $agora->getTimestamp());
                _rateLimit429($acao, $ip, $registro['tentativas'], $segundos_restantes);
            } else {
                // Bloqueio expirou — reseta
                $pdo->prepare("DELETE FROM rate_limit WHERE ip = ? AND acao = ?")
                    ->execute([$ip, $acao]);
                $registro = null;
            }
        }

        // ── Incrementa ou cria registro ───────────────────────────────────
        if (!$registro) {
            $pdo->prepare("
                INSERT INTO rate_limit (ip, acao, tentativas, primeira_em, ultima_em)
                VALUES (?, ?, 1, NOW(), NOW())
            ")->execute([$ip, $acao]);
            return; // Primeira tentativa — libera
        }

        $novas_tentativas = $registro['tentativas'] + 1;

        if ($novas_tentativas > $max) {
            // Excedeu — calcula até quando bloquear
            $bloqueio_ate = (new DateTime())->modify("+{$janela_seg} seconds");

            $pdo->prepare("
                UPDATE rate_limit
                SET tentativas = ?, ultima_em = NOW(), bloqueado_ate = ?
                WHERE ip = ? AND acao = ?
            ")->execute([
                $novas_tentativas,
                $bloqueio_ate->format('Y-m-d H:i:s'),
                $ip,
                $acao
            ]);

            // Registra no log de auditoria
            $pdo->prepare("
                INSERT INTO logs_sistema (usuario_id, acao, descricao, ip)
                VALUES (NULL, 'RATE_LIMIT_BLOQUEIO', ?, ?)
            ")->execute([
                "Bloqueio de rate limit: ação '{$acao}'. Tentativas: {$novas_tentativas}. Bloqueado até: "
                    . $bloqueio_ate->format('d/m/Y H:i:s') . ".",
                $ip
            ]);

            _rateLimit429($acao, $ip, $novas_tentativas, $janela_seg);
        }

        // Dentro do limite — apenas incrementa
        $pdo->prepare("
            UPDATE rate_limit
            SET tentativas = ?, ultima_em = NOW()
            WHERE ip = ? AND acao = ?
        ")->execute([$novas_tentativas, $ip, $acao]);

    } catch (Exception $e) {
        // Em caso de erro de banco, deixa passar (fail open).
        // Não queremos bloquear usuários legítimos por falha de infra.
        error_log("rate_limit error: " . $e->getMessage());
    }
}

/**
 * Reseta o contador após uma ação bem-sucedida.
 * Chame após login bem-sucedido, por exemplo.
 */
function resetRateLimit(string $acao): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    try {
        $pdo = getConnection();
        $pdo->prepare("DELETE FROM rate_limit WHERE ip = ? AND acao = ?")
            ->execute([$ip, $acao]);
    } catch (Exception $e) {
        error_log("rate_limit reset error: " . $e->getMessage());
    }
}

/**
 * Encerra a requisição com HTTP 429.
 * Retorna JSON para requisições de API, HTML para páginas normais.
 */
function _rateLimit429(string $acao, string $ip, int $tentativas, int $segundos_restantes): void
{
    $minutos = max(1, (int) ceil($segundos_restantes / 60));

    http_response_code(429);

    $is_api = (
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        || isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    );

    if ($is_api) {
        header('Content-Type: application/json');
        echo json_encode([
            'erro'     => "Muitas tentativas. Aguarde {$minutos} minuto(s).",
            'restante' => $segundos_restantes,
        ]);
    } else {
        echo '<!DOCTYPE html><html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Muitas tentativas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh">
    <div class="container text-center py-5">
        <div class="card shadow-sm border-0 d-inline-block p-5">
            <i class="bi bi-shield-lock text-danger" style="font-size:3rem"></i>
            <h2 class="mt-3">Muitas tentativas</h2>
            <p class="text-muted">Você excedeu o limite de tentativas para esta ação.</p>
            <p>Aguarde <strong>' . $minutos . ' minuto(s)</strong> e tente novamente.</p>
            <a href="/index.php" class="btn btn-primary mt-2">Voltar ao início</a>
        </div>
    </div>
</body></html>';
    }

    exit;
}