-- ============================================================
-- PROJETO ECOCICLO - BANCO DE DADOS (versão corrigida)
-- Requer MySQL 8.0.16+ (para os CHECK funcionarem)
-- Senha de TODOS os usuários de exemplo: Eco@1234
-- ============================================================

CREATE DATABASE IF NOT EXISTS ecociclo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecociclo;

-- ------------------------------------------------------------
-- 1. USUÁRIO (autenticação) - RN02, RN03, RN05, RN16
-- ------------------------------------------------------------
CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('PARTICIPANTE', 'ADMIN', 'COLETOR') NOT NULL DEFAULT 'PARTICIPANTE',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. PARTICIPANTE - RN05, RN11, RN17
-- ------------------------------------------------------------
CREATE TABLE participante (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    telefone VARCHAR(20) NOT NULL,
    endereco VARCHAR(150) NOT NULL,
    bairro VARCHAR(80) NOT NULL,
    saldo_ecopontos INT NOT NULL DEFAULT 0 CHECK (saldo_ecopontos >= 0),
    CONSTRAINT fk_participante_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuario(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. MATERIAL (catálogo de recicláveis) - RN04
-- ------------------------------------------------------------
CREATE TABLE material (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(60) NOT NULL UNIQUE,
    descricao VARCHAR(200) NOT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. AGENDAMENTO - RN06, RN07, RN17
-- ------------------------------------------------------------
CREATE TABLE agendamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL,
    material_id INT NOT NULL,
    endereco VARCHAR(150) NOT NULL,
    bairro VARCHAR(80) NOT NULL,
    data_agendada DATE NOT NULL,
    faixa_horario VARCHAR(50) NOT NULL,
    status ENUM('Solicitada', 'Agendada', 'Concluida', 'Cancelada') NOT NULL DEFAULT 'Solicitada',
    observacoes TEXT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_agendamento_participante FOREIGN KEY (participante_id)
        REFERENCES participante(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_agendamento_material FOREIGN KEY (material_id)
        REFERENCES material(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. COLETA (confirmação e peso) - RN08, RN09, RN10, RN17
-- ------------------------------------------------------------
CREATE TABLE coleta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agendamento_id INT NOT NULL UNIQUE,      -- 1 coleta por agendamento
    responsavel_id INT NOT NULL,             -- usuário que confirmou
    data_efetiva DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    peso_kg DECIMAL(8,2) NOT NULL CHECK (peso_kg > 0),
    pontos_gerados INT NOT NULL CHECK (pontos_gerados >= 0),
    observacoes TEXT NULL,
    CONSTRAINT fk_coleta_agendamento FOREIGN KEY (agendamento_id)
        REFERENCES agendamento(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_coleta_responsavel FOREIGN KEY (responsavel_id)
        REFERENCES usuario(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. COMÉRCIO - RN12
-- ------------------------------------------------------------
CREATE TABLE comercio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    bairro VARCHAR(80) NOT NULL,
    contato VARCHAR(50) NOT NULL,
    situacao_cadastral ENUM('Ativo', 'Inativo') NOT NULL DEFAULT 'Ativo'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. CUPOM - RN12
-- ------------------------------------------------------------
CREATE TABLE cupom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comercio_id INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    custo_ecopontos INT NOT NULL CHECK (custo_ecopontos > 0),
    validade DATE NOT NULL,
    quantidade_disponivel INT NOT NULL CHECK (quantidade_disponivel >= 0),
    CONSTRAINT fk_cupom_comercio FOREIGN KEY (comercio_id)
        REFERENCES comercio(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. TROCA DE CUPOM - RN13, RN14, RN17
-- ------------------------------------------------------------
CREATE TABLE troca_cupom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL,
    cupom_id INT NOT NULL,
    pontos_debitados INT NOT NULL CHECK (pontos_debitados > 0),
    codigo_resgatado VARCHAR(20) NOT NULL UNIQUE,
    data_troca DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_troca_participante FOREIGN KEY (participante_id)
        REFERENCES participante(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_troca_cupom FOREIGN KEY (cupom_id)
        REFERENCES cupom(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 9. MOVIMENTAÇÃO DE ECOPONTOS (extrato) - RN09, RN10, RN11, RN17
-- coleta_id UNIQUE impede crédito duplicado da mesma coleta
-- ------------------------------------------------------------
CREATE TABLE movimentacao_ecopontos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL,
    tipo ENUM('CREDITO', 'DEBITO') NOT NULL,
    quantidade INT NOT NULL CHECK (quantidade > 0),
    data_movimentacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    descricao VARCHAR(255) NOT NULL,
    coleta_id INT NULL UNIQUE,
    troca_id INT NULL UNIQUE,
    CONSTRAINT fk_mov_participante FOREIGN KEY (participante_id)
        REFERENCES participante(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mov_coleta FOREIGN KEY (coleta_id)
        REFERENCES coleta(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mov_troca FOREIGN KEY (troca_id)
        REFERENCES troca_cupom(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- POPULAÇÃO (mín. 3 registros por tabela)
-- Senha de todos: Eco@1234
-- ============================================================

INSERT INTO usuario (nome, email, senha_hash, perfil) VALUES
('Ana Silva',       'ana.silva@email.com',          '$2y$10$DycbL9piW4iA6y8wx8B7mOqB1JlNJlYAYV3E7Tl3iu.GRuJxQh0aO', 'PARTICIPANTE'),
('Carlos Oliveira', 'carlos.oliveira@email.com',    '$2y$10$DycbL9piW4iA6y8wx8B7mOqB1JlNJlYAYV3E7Tl3iu.GRuJxQh0aO', 'PARTICIPANTE'),
('Mariana Costa',   'mariana.costa@email.com',      '$2y$10$DycbL9piW4iA6y8wx8B7mOqB1JlNJlYAYV3E7Tl3iu.GRuJxQh0aO', 'PARTICIPANTE'),
('Roberto Coletor', 'roberto.coletor@ecociclo.com', '$2y$10$DycbL9piW4iA6y8wx8B7mOqB1JlNJlYAYV3E7Tl3iu.GRuJxQh0aO', 'COLETOR'),
('Administrador',   'admin@ecociclo.com',           '$2y$10$DycbL9piW4iA6y8wx8B7mOqB1JlNJlYAYV3E7Tl3iu.GRuJxQh0aO', 'ADMIN');

-- Saldos já refletem o extrato abaixo:
-- Ana 120-50=70 | Carlos 80-30=50 | Mariana 155-60=95
INSERT INTO participante (usuario_id, telefone, endereco, bairro, saldo_ecopontos) VALUES
(1, '(81) 98765-4321', 'Rua Quatorze, 123',        'Curado 4', 70),
(2, '(81) 97654-3210', 'Av. Dolores Duran, 1500',  'Curado 2', 50),
(3, '(81) 96543-2109', 'Rua São José, 45',         'Curado 3', 95);

INSERT INTO material (nome, descricao) VALUES
('Plástico e PET',    'Garrafas, embalagens e potes plásticos limpos'),
('Papel e Papelão',   'Caixas, jornais, revistas e folhas secas'),
('Vidro',             'Garrafas e potes de vidro sem tampa'),
('Metal e Alumínio',  'Latinhas, tampas e sucata de metal leve');

INSERT INTO agendamento (participante_id, material_id, endereco, bairro, data_agendada, faixa_horario, status, observacoes) VALUES
(1, 1, 'Rua Quatorze, 123',       'Curado 4', '2026-10-05', 'Manhã (08h - 12h)', 'Concluida', 'Separado plástico e PET'),
(2, 3, 'Av. Dolores Duran, 1500', 'Curado 2', '2026-10-08', 'Tarde (13h - 17h)', 'Concluida', 'Garrafas de vidro'),
(3, 2, 'Rua São José, 45',        'Curado 3', '2026-10-12', 'Manhã (08h - 12h)', 'Concluida', 'Deixar na portaria'),
(1, 4, 'Rua Quatorze, 123',       'Curado 4', '2026-10-28', 'Tarde (13h - 17h)', 'Solicitada', 'Latinhas'),
(2, 2, 'Av. Dolores Duran, 1500', 'Curado 2', '2026-10-30', 'Manhã (08h - 12h)', 'Agendada',  NULL),
(3, 1, 'Rua São José, 45',        'Curado 3', '2026-10-20', 'Tarde (13h - 17h)', 'Cancelada', 'Participante desistiu');

-- RN09: pontos = peso x 10
INSERT INTO coleta (agendamento_id, responsavel_id, data_efetiva, peso_kg, pontos_gerados, observacoes) VALUES
(1, 4, '2026-10-05 10:30:00', 12.00, 120, 'Material bem separado'),
(2, 4, '2026-10-08 14:15:00',  8.00,  80, NULL),
(3, 4, '2026-10-12 09:40:00', 15.50, 155, 'Papelão levemente úmido');

INSERT INTO comercio (nome, bairro, contato, situacao_cadastral) VALUES
('Padaria Pedro Pães',    'Curado 3', '(81) 3222-1100', 'Ativo'),
('Hortifruti Vida Verde', 'Curado 2', '(81) 3333-2211', 'Ativo'),
('Docegeo',               'Curado 4', '(81) 3444-5566', 'Ativo');

-- Quantidades já descontam as trocas realizadas (20→19, 30→29, 50→49)
INSERT INTO cupom (comercio_id, titulo, descricao, custo_ecopontos, validade, quantidade_disponivel) VALUES
(1, 'Cupom R$ 10,00 de Desconto', 'Válido para compras acima de R$ 30,00 na padaria.',     50, '2026-12-31', 19),
(2, 'Desconto de 15% em Orgânicos', 'Desconto em todo o setor de hortifruti orgânico.',      60, '2026-11-30', 29),
(3, 'Espresso Grátis',            'Um café espresso na compra de qualquer fatia de bolo.',   30, '2026-12-15', 49);

INSERT INTO troca_cupom (participante_id, cupom_id, pontos_debitados, codigo_resgatado, data_troca) VALUES
(1, 1, 50, 'ECO-PAD-9831', '2026-10-14 16:00:00'),
(2, 3, 30, 'ECO-DOC-4410', '2026-10-15 11:20:00'),
(3, 2, 60, 'ECO-HOR-7752', '2026-10-15 17:45:00');

INSERT INTO movimentacao_ecopontos (participante_id, tipo, quantidade, data_movimentacao, descricao, coleta_id, troca_id) VALUES
(1, 'CREDITO', 120, '2026-10-05 10:30:00', 'Crédito por coleta concluída (12.00 kg)', 1, NULL),
(2, 'CREDITO',  80, '2026-10-08 14:15:00', 'Crédito por coleta concluída (8.00 kg)',  2, NULL),
(3, 'CREDITO', 155, '2026-10-12 09:40:00', 'Crédito por coleta concluída (15.50 kg)', 3, NULL),
(1, 'DEBITO',   50, '2026-10-14 16:00:00', 'Troca pelo cupom: Cupom R$ 10,00 de Desconto', NULL, 1),
(2, 'DEBITO',   30, '2026-10-15 11:20:00', 'Troca pelo cupom: Espresso Grátis',            NULL, 2),
(3, 'DEBITO',   60, '2026-10-15 17:45:00', 'Troca pelo cupom: Desconto de 15% em Orgânicos', NULL, 3);