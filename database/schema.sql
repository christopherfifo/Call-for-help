-- ============================================================
-- Sistema de Chamados - Script de Instalação do Banco de Dados
-- ============================================================

CREATE DATABASE IF NOT EXISTS sistema_chamados CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_chamados;

-- ─────────────────────────────────────────
-- Tabela de Categorias
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categorias (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    ativo   BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_categorias_nome UNIQUE (nome)          -- garante idempotência nos INSERTs
);

-- ─────────────────────────────────────────
-- Tabela de Usuários
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL,
    matricula   VARCHAR(50)  NOT NULL,
    senha       VARCHAR(255) NOT NULL,
    cargo       ENUM('USUARIO','TECNICO','ADMINISTRADOR','DEV') NOT NULL DEFAULT 'USUARIO',
    ativo       BOOLEAN DEFAULT TRUE,
    primeiro_acesso BOOLEAN DEFAULT TRUE,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_usuarios_email     UNIQUE (email),
    CONSTRAINT uq_usuarios_matricula UNIQUE (matricula)
);

-- ─────────────────────────────────────────
-- Tabela de Chamados
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chamados (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    numero               VARCHAR(20)  NOT NULL,
    usuario_id           INT NOT NULL,
    tecnico_id           INT DEFAULT NULL,
    categoria_id         INT NOT NULL,
    assunto              VARCHAR(200) NOT NULL,
    descricao            TEXT NOT NULL,
    tipo                 ENUM('NORMAL','INFORMAL') NOT NULL DEFAULT 'NORMAL',
    status               ENUM('ABERTO','EM_ANDAMENTO','PAUSADO','FINALIZADO','CANCELADO') NOT NULL DEFAULT 'ABERTO',
    tag_repassado        BOOLEAN DEFAULT FALSE,
    tag_reaberto         BOOLEAN DEFAULT FALSE,
    -- urgencia armazena a urgência ORIGINAL definida pelo usuário na abertura.
    -- A urgência efetiva é calculada consultando a tabela urgencia_chamados.
    urgencia             ENUM('LEVE','MODERADA','ALTA') NOT NULL DEFAULT 'LEVE',
    justificativa_urgencia TEXT NOT NULL,
    prazo                DATETIME DEFAULT NULL,
    prazo_indeterminado  BOOLEAN DEFAULT FALSE,
    justificativa_prazo  TEXT DEFAULT NULL,
    relatorio_final      TEXT DEFAULT NULL,
    criado_em            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    finalizado_em        DATETIME DEFAULT NULL,
    cancelado_em         DATETIME DEFAULT NULL,
    cancelado_por        INT DEFAULT NULL,
    criado_por           INT DEFAULT NULL,
    CONSTRAINT uq_chamados_numero UNIQUE (numero),
    FOREIGN KEY (usuario_id)    REFERENCES usuarios(id),
    FOREIGN KEY (tecnico_id)    REFERENCES usuarios(id),
    FOREIGN KEY (categoria_id)  REFERENCES categorias(id),
    FOREIGN KEY (cancelado_por) REFERENCES usuarios(id),
    FOREIGN KEY (criado_por)    REFERENCES usuarios(id)
);

-- ─────────────────────────────────────────
-- Tabela de Urgências (hierarquia de alterações)
-- ─────────────────────────────────────────
-- Cada linha representa uma intervenção de Técnico ou Admin/Dev sobre a urgência.
-- A urgência efetiva é o registro ativo (ativo=1) de mais alto grau / mais alta hierarquia.
-- Somente o autor do registro pode editá-lo ou desativá-lo (ativo=0).
CREATE TABLE IF NOT EXISTS urgencia_chamados (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    chamado_id   INT NOT NULL,
    usuario_id   INT NOT NULL,          -- quem fez a alteração
    cargo_autor  ENUM('TECNICO','ADMINISTRADOR','DEV') NOT NULL,
    hierarquia   TINYINT NOT NULL,      -- 1=TECNICO, 2=ADMINISTRADOR, 3=DEV
    urgencia     ENUM('LEVE','MODERADA','ALTA') NOT NULL,
    justificativa TEXT NOT NULL,
    ativo        BOOLEAN NOT NULL DEFAULT TRUE,  -- FALSE = revogado/substituído pelo próprio autor
    ip           VARCHAR(45) DEFAULT NULL,
    criado_em    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (chamado_id) REFERENCES chamados(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_urgencia_chamado_ativo (chamado_id, ativo, hierarquia)
);

-- ─────────────────────────────────────────
-- Tabela de Histórico de Chamados
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS historico_chamados (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    chamado_id      INT NOT NULL,
    usuario_id      INT NOT NULL,
    acao            VARCHAR(100) NOT NULL,
    status_anterior VARCHAR(50) DEFAULT NULL,
    status_novo     VARCHAR(50) DEFAULT NULL,
    descricao       TEXT NOT NULL,
    ip              VARCHAR(45) DEFAULT NULL,
    criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chamado_id) REFERENCES chamados(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ─────────────────────────────────────────
-- Tabela de Comentários / Atualizações
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comentarios_chamados (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    chamado_id  INT NOT NULL,
    usuario_id  INT NOT NULL,
    comentario  TEXT NOT NULL,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chamado_id) REFERENCES chamados(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ─────────────────────────────────────────
-- Tabela de Solicitações de Relatório
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS solicitacoes_relatorio (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    chamado_id    INT NOT NULL,
    solicitante_id INT NOT NULL,
    motivo        TEXT NOT NULL,
    observacao    TEXT DEFAULT NULL,
    status        ENUM('PENDENTE','ATENDIDO','RECUSADO') NOT NULL DEFAULT 'PENDENTE',
    atendido_por  INT DEFAULT NULL,
    atendido_em   DATETIME DEFAULT NULL,
    criado_em     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chamado_id)    REFERENCES chamados(id),
    FOREIGN KEY (solicitante_id) REFERENCES usuarios(id),
    FOREIGN KEY (atendido_por)  REFERENCES usuarios(id)
);

-- ─────────────────────────────────────────
-- Tabela de Anexos
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS anexos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    chamado_id     INT NOT NULL,
    usuario_id     INT NOT NULL,
    nome_original  VARCHAR(255) NOT NULL,
    caminho        VARCHAR(255) NOT NULL,
    tipo           VARCHAR(100) NOT NULL,
    tamanho        INT NOT NULL,
    criado_em      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chamado_id) REFERENCES chamados(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ─────────────────────────────────────────
-- Tabela de Logs do Sistema (Auditoria)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS logs_sistema (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT DEFAULT NULL,
    acao        VARCHAR(100) NOT NULL,
    descricao   TEXT NOT NULL,
    ip          VARCHAR(45) DEFAULT NULL,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ─────────────────────────────────────────
-- Tabela de Pedidos Administrativos
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pedidos_administrativos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tipo            ENUM('RESET_SENHA','CANCELAMENTO_CHAMADO','EXCLUSAO_CHAMADO') NOT NULL,
    usuario_id      INT NOT NULL,
    referencia_id   INT DEFAULT NULL,
    justificativa   TEXT NOT NULL,
    status          ENUM('PENDENTE','APROVADO','RECUSADO') NOT NULL DEFAULT 'PENDENTE',
    solicitante_ip  VARCHAR(45) DEFAULT NULL,
    atendido_por    INT DEFAULT NULL,
    atendido_em     DATETIME DEFAULT NULL,
    atendente_ip    VARCHAR(45) DEFAULT NULL,
    criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id)   REFERENCES usuarios(id),
    FOREIGN KEY (atendido_por) REFERENCES usuarios(id)
);

-- ─────────────────────────────────────────
-- Tabela de Notificações
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notificacoes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NOT NULL,
    tipo            VARCHAR(50) NOT NULL,
    mensagem        TEXT NOT NULL,
    lida            BOOLEAN DEFAULT FALSE,
    referencia_id   INT DEFAULT NULL,
    referencia_tipo VARCHAR(50) DEFAULT NULL,
    criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ─────────────────────────────────────────
-- Dados iniciais — categorias (idempotente via INSERT IGNORE)
-- ─────────────────────────────────────────
INSERT IGNORE INTO categorias (nome) VALUES
    ('Hardware'), ('Software'), ('Sistema'), ('Rede'),
    ('Impressora'), ('Acesso'), ('E-mail'), ('Telefonia'),
    ('Equipamento'), ('Outro');

