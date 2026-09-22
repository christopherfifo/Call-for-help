<?php
/**
 * Funções auxiliares globais reutilizáveis.
 * Incluído por config.php para que todo o sistema tenha acesso.
 */

/**
 * Mapa de hierarquia de cargo → nível numérico.
 * Quanto maior, mais autoridade.
 */
const HIERARQUIA_CARGO = [
    'USUARIO'       => 0,
    'TECNICO'       => 1,
    'ADMINISTRADOR' => 2,
    'DEV'           => 3,
];

/**
 * Mapa de nível numérico de urgência (para comparação).
 */
const NIVEL_URGENCIA = [
    'LEVE'     => 1,
    'MODERADA' => 2,
    'ALTA'     => 3,
];

/**
 * Retorna a urgência efetiva de um chamado.
 *
 * Algoritmo:
 *   1. Coleta todos os registros ativos em urgencia_chamados para o chamado.
 *   2. Entre eles, pega o de maior hierarquia de cargo.
 *   3. Se empate de hierarquia, pega o de maior urgência.
 *   4. Compara com a urgência original do chamado (cargo USUARIO, nível 0).
 *   5. Retorna a vencedora.
 *
 * @param PDO   $pdo
 * @param array $chamado  Linha da tabela chamados (deve conter 'id' e 'urgencia')
 * @return string  'LEVE' | 'MODERADA' | 'ALTA'
 */
function getUrgenciaEfetiva(PDO $pdo, array $chamado): string
{
    $stmt = $pdo->prepare("
        SELECT urgencia, hierarquia
        FROM urgencia_chamados
        WHERE chamado_id = ? AND ativo = 1
        ORDER BY hierarquia DESC, FIELD(urgencia,'ALTA','MODERADA','LEVE') ASC
        LIMIT 1
    ");
    $stmt->execute([$chamado['id']]);
    $override = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$override) {
        // Nenhuma intervenção ativa → retorna urgência original
        return $chamado['urgencia'];
    }

    // A intervenção vence se tiver hierarquia > 0 (qualquer técnico ou admin)
    // Ela sempre prevalece sobre a original, mas mostramos a maior urgência entre as duas
    $nivel_original  = NIVEL_URGENCIA[$chamado['urgencia']] ?? 1;
    $nivel_override  = NIVEL_URGENCIA[$override['urgencia']] ?? 1;

    return ($nivel_override >= $nivel_original)
        ? $override['urgencia']
        : $chamado['urgencia'];
}

/**
 * Retorna todos os registros ativos de urgência de um chamado,
 * ordenados por hierarquia decrescente (para exibição no histórico).
 */
function getUrgenciaHistorico(PDO $pdo, int $chamado_id): array
{
    $stmt = $pdo->prepare("
        SELECT uc.*, u.nome as autor_nome, u.cargo as autor_cargo
        FROM urgencia_chamados uc
        JOIN usuarios u ON uc.usuario_id = u.id
        WHERE uc.chamado_id = ?
        ORDER BY uc.criado_em DESC
    ");
    $stmt->execute([$chamado_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retorna o badge HTML para uma urgência.
 */
function badgeUrgencia(string $urgencia, bool $small = false): string
{
    $fs = $small ? '' : ' fs-6';
    switch ($urgencia) {
        case 'ALTA':     return "<span class=\"badge bg-danger{$fs}\">Alta</span>";
        case 'MODERADA': return "<span class=\"badge bg-warning text-dark{$fs}\">Moderada</span>";
        case 'LEVE':     return "<span class=\"badge bg-success{$fs}\">Leve</span>";
        default:         return "<span class=\"badge bg-secondary{$fs}\">Indefinida</span>";
    }
}