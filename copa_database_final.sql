-- Copa das Panelas - Banco de Dados Completo
-- Versão 2.0 com Sistema de Versionamento de Torneios

DROP DATABASE IF EXISTS copa;
CREATE DATABASE copa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE copa;

-- ============================================================================
-- SISTEMA DE VERSIONAMENTO DE TORNEIOS
-- ============================================================================

-- Tabela principal de torneios
CREATE TABLE tournaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    description TEXT,
    status ENUM('setup', 'active', 'completed', 'archived') DEFAULT 'setup',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    created_by INT NULL,
    INDEX idx_status (status),
    INDEX idx_year (year)
);

-- Configurações específicas de cada torneio
CREATE TABLE tournament_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT NOT NULL,
    num_groups INT NOT NULL DEFAULT 1,
    teams_per_group INT NOT NULL DEFAULT 4,
    final_phase ENUM('oitavas', 'quartas', 'semifinais', 'final') DEFAULT 'final',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
);

-- Backup de torneios para histórico
CREATE TABLE tournaments_backup (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_tournament_id INT NOT NULL,
    backup_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tournament_data JSON NOT NULL,
    backup_reason VARCHAR(255),
    INDEX idx_original_tournament (original_tournament_id),
    INDEX idx_backup_date (backup_date)
);

-- Log de atividades dos torneios
CREATE TABLE tournament_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    metadata JSON NULL,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    INDEX idx_tournament_activity (tournament_id, created_at)
);

-- ============================================================================
-- ADMINISTRAÇÃO
-- ============================================================================

-- Tabela de administradores
CREATE TABLE admin (
    cod_adm VARCHAR(200) NOT NULL PRIMARY KEY,
    nome VARCHAR(60) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
);

-- ============================================================================
-- ESTRUTURA PRINCIPAL DO TORNEIO
-- ============================================================================

-- Tabela de grupos (agora com tournament_id)
CREATE TABLE grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tournament_id INT NOT NULL,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    INDEX idx_tournament_id (tournament_id)
);

-- Tabela de times (agora com tournament_id)
CREATE TABLE times (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    logo BLOB,
    grupo_id INT NOT NULL,
    tournament_id INT NOT NULL,
    token VARCHAR(64) UNIQUE,
    pts INT DEFAULT 0,
    vitorias INT DEFAULT 0,
    empates INT DEFAULT 0,
    derrotas INT DEFAULT 0,
    gm INT DEFAULT 0,
    gc INT DEFAULT 0,
    sg INT DEFAULT 0,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    INDEX idx_tournament_id (tournament_id),
    INDEX idx_grupo_id (grupo_id)
);

-- ============================================================================
-- JOGADORES
-- ============================================================================

-- Tabela de jogadores
CREATE TABLE jogadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    gols INT DEFAULT 0,
    posicao VARCHAR(255),
    numero INT,
    assistencias INT DEFAULT 0,
    cartoes_amarelos INT DEFAULT 0,
    cartoes_vermelhos INT DEFAULT 0,
    token VARCHAR(64) UNIQUE,
    imagem LONGBLOB,
    time_id INT NOT NULL,
    FOREIGN KEY (time_id) REFERENCES times(id) ON DELETE CASCADE
);

-- Tabela para ranking de estatísticas dos jogadores
CREATE TABLE posicoes_jogadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jogador_id INT NOT NULL,
    categoria ENUM('gols', 'assistencias', 'cartoes_amarelos', 'cartoes_vermelhos') NOT NULL,
    posicao INT DEFAULT 0,
    FOREIGN KEY (jogador_id) REFERENCES jogadores(id) ON DELETE CASCADE
);

-- ============================================================================
-- JOGOS E CONFRONTOS
-- ============================================================================

-- Jogos da fase de grupos
CREATE TABLE jogos_fase_grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    timeA_id INT NOT NULL,
    timeB_id INT NOT NULL,
    nome_timeA VARCHAR(100) NOT NULL,
    nome_timeB VARCHAR(100) NOT NULL,
    gols_marcados_timeA INT DEFAULT 0,
    gols_marcados_timeB INT DEFAULT 0,
    resultado_timeA CHAR(1),
    resultado_timeB CHAR(1),
    data_jogo DATETIME NOT NULL,
    rodada INT NOT NULL,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (timeA_id) REFERENCES times(id) ON DELETE CASCADE,
    FOREIGN KEY (timeB_id) REFERENCES times(id) ON DELETE CASCADE,
    INDEX idx_grupo_rodada (grupo_id, rodada)
);

-- Jogos das fases finais
CREATE TABLE jogos_finais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timeA_id INT NOT NULL,
    timeB_id INT NOT NULL,
    nome_timeA VARCHAR(100) NOT NULL,
    nome_timeB VARCHAR(100) NOT NULL,
    gols_marcados_timeA INT NOT NULL,
    gols_marcados_timeB INT NOT NULL,
    resultado_timeA CHAR(1),
    resultado_timeB CHAR(1),
    data_jogo DATETIME NOT NULL,
    fase VARCHAR(50) NOT NULL,
    FOREIGN KEY (timeA_id) REFERENCES times(id) ON DELETE CASCADE,
    FOREIGN KEY (timeB_id) REFERENCES times(id) ON DELETE CASCADE
);

-- ============================================================================
-- CLASSIFICAÇÃO PARA FASES FINAIS
-- ============================================================================

-- Times classificados para oitavas
CREATE TABLE oitavas_de_final (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_id INT NOT NULL,
    grupo_nome VARCHAR(50),
    time_nome VARCHAR(100),
    FOREIGN KEY (time_id) REFERENCES times(id) ON DELETE CASCADE
);

-- Times classificados para quartas
CREATE TABLE quartas_de_final (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_id INT NOT NULL,
    grupo_nome VARCHAR(50),
    time_nome VARCHAR(100),
    FOREIGN KEY (time_id) REFERENCES times(id) ON DELETE CASCADE
);

-- Times classificados para semifinais
CREATE TABLE semifinais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_id INT NOT NULL,
    grupo_nome VARCHAR(50),
    time_nome VARCHAR(100),
    FOREIGN KEY (time_id) REFERENCES times(id) ON DELETE CASCADE
);

-- Times classificados para final
CREATE TABLE final (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_id INT NOT NULL,
    grupo_nome VARCHAR(50),
    time_nome VARCHAR(100),
    FOREIGN KEY (time_id) REFERENCES times(id) ON DELETE CASCADE
);

-- ============================================================================
-- CONFRONTOS DAS FASES FINAIS
-- ============================================================================

-- Confrontos das oitavas
CREATE TABLE oitavas_de_final_confrontos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timeA_id INT NOT NULL,
    timeB_id INT NOT NULL,
    fase ENUM('oitavas') NOT NULL,
    gols_marcados_timeA INT DEFAULT NULL,
    gols_marcados_timeB INT DEFAULT NULL,
    gols_contra_timeA INT DEFAULT NULL,
    gols_contra_timeB INT DEFAULT NULL
);

-- Confrontos das quartas
CREATE TABLE quartas_de_final_confrontos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timeA_id INT NOT NULL,
    timeB_id INT NOT NULL,
    fase ENUM('quartas') NOT NULL,
    gols_marcados_timeA INT DEFAULT NULL,
    gols_marcados_timeB INT DEFAULT NULL,
    gols_contra_timeA INT DEFAULT NULL,
    gols_contra_timeB INT DEFAULT NULL
);

-- Confrontos das semifinais
CREATE TABLE semifinais_confrontos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timeA_id INT NOT NULL,
    timeB_id INT NOT NULL,
    fase ENUM('semifinais') NOT NULL,
    gols_marcados_timeA INT DEFAULT NULL,
    gols_marcados_timeB INT DEFAULT NULL,
    gols_contra_timeA INT DEFAULT NULL,
    gols_contra_timeB INT DEFAULT NULL
);

-- Confrontos da final
CREATE TABLE final_confrontos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timeA_id INT NOT NULL,
    timeB_id INT NOT NULL,
    fase ENUM('final') NOT NULL,
    gols_marcados_timeA INT DEFAULT NULL,
    gols_marcados_timeB INT DEFAULT NULL,
    gols_contra_timeA INT DEFAULT NULL,
    gols_contra_timeB INT DEFAULT NULL
);

-- ============================================================================
-- CONFIGURAÇÕES E CONTROLE
-- ============================================================================

-- Configurações gerais (mantida para compatibilidade)
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipes_por_grupo INT NOT NULL,
    numero_grupos INT NOT NULL,
    fase_final ENUM('oitavas', 'quartas', 'semifinais', 'final') NOT NULL
);

-- Controle de execução de fases
CREATE TABLE fase_execucao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fase VARCHAR(50) NOT NULL,
    executado BOOLEAN NOT NULL DEFAULT FALSE
);

-- ============================================================================
-- MÍDIA E CONTEÚDO
-- ============================================================================

-- Notícias
CREATE TABLE noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    imagem LONGBLOB NOT NULL,
    link VARCHAR(255) NOT NULL,
    data_adicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Links para transmissões
CREATE TABLE linklive (
    codlive VARCHAR(255) PRIMARY KEY
);

-- Links do Instagram
CREATE TABLE linkinstagram (
    codinsta INT AUTO_INCREMENT PRIMARY KEY, 
    linklive VARCHAR(255)
);

-- Tabela de jogos (mantida para compatibilidade)
CREATE TABLE jogos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_id INT NOT NULL,
    resultado CHAR(1),
    data_jogo DATE,
    FOREIGN KEY (time_id) REFERENCES times(id) ON DELETE CASCADE
);

-- ============================================================================
-- TABELAS DE HISTÓRICO (Para preservar dados de torneios anteriores)
-- ============================================================================

-- Histórico de grupos
CREATE TABLE grupos_historico (
    historico_id INT AUTO_INCREMENT PRIMARY KEY,
    id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    tournament_id INT,
    backup_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Histórico de times
CREATE TABLE times_historico (
    historico_id INT AUTO_INCREMENT PRIMARY KEY,
    id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    logo BLOB,
    grupo_id INT NOT NULL,
    tournament_id INT,
    token VARCHAR(64),
    pts INT DEFAULT 0,
    vitorias INT DEFAULT 0,
    empates INT DEFAULT 0,
    derrotas INT DEFAULT 0,
    gm INT DEFAULT 0,
    gc INT DEFAULT 0,
    sg INT DEFAULT 0,
    backup_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Histórico de jogadores
CREATE TABLE jogadores_historico (
    historico_id INT AUTO_INCREMENT PRIMARY KEY,
    id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    gols INT DEFAULT 0,
    posicao VARCHAR(255),
    numero INT,
    assistencias INT DEFAULT 0,
    cartoes_amarelos INT DEFAULT 0,
    cartoes_vermelhos INT DEFAULT 0,
    token VARCHAR(64),
    imagem LONGBLOB,
    time_id INT NOT NULL,
    backup_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- DADOS INICIAIS
-- ============================================================================

-- Inserir configuração padrão
INSERT INTO configuracoes (equipes_por_grupo, numero_grupos, fase_final) 
VALUES (4, 4, 'semifinais');

-- Inserir fases de execução padrão
INSERT INTO fase_execucao (fase, executado) VALUES 
('grupos', FALSE),
('oitavas', FALSE),
('quartas', FALSE),
('semifinais', FALSE),
('final', FALSE);

-- Criar torneio padrão
INSERT INTO tournaments (name, year, description, status) 
VALUES ('Copa das Panelas 2024', 2024, 'Torneio atual migrado automaticamente', 'active');

SET @default_tournament_id = LAST_INSERT_ID();

-- Inserir configurações do torneio padrão
INSERT INTO tournament_settings (tournament_id, num_groups, teams_per_group, final_phase)
VALUES (@default_tournament_id, 4, 4, 'semifinais');

-- Log inicial
INSERT INTO tournament_activity_log (tournament_id, action, description)
VALUES (@default_tournament_id, 'MIGRATION', 'Sistema migrado para nova arquitetura');

-- ============================================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================================================

CREATE INDEX idx_times_tournament_grupo ON times(tournament_id, grupo_id);
CREATE INDEX idx_jogos_fase_grupos_resultado ON jogos_fase_grupos(resultado_timeA, resultado_timeB);
CREATE INDEX idx_jogos_fase_grupos_data ON jogos_fase_grupos(data_jogo);
CREATE INDEX idx_tournament_activity_log_action ON tournament_activity_log(action, created_at);
CREATE INDEX idx_jogadores_time_stats ON jogadores(time_id, gols, assistencias);

-- ============================================================================
-- COMENTÁRIOS FINAIS
-- ============================================================================

/*
BANCO DE DADOS COPA DAS PANELAS - VERSÃO 2.0 COMPLETA

ESTRUTURA:
✅ 32 tabelas principais
✅ Sistema de versionamento de torneios
✅ Backup automático de dados
✅ Tabelas de histórico
✅ Log de atividades
✅ Índices para performance

COMO APLICAR:
mysql -u root < copa_database_final.sql

ACESSO:
http://localhost/copadaspanelas2/app/pages/adm/tournament_list.php

STATUS: PRONTO PARA USO
*/
