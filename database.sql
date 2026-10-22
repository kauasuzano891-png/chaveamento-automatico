-- ========================================================
-- BANCO DE DADOS: chaveamento
-- Sistema de Chaveamento Esportivo Automático
-- Stack: PHP + MySQL (PDO) + Tailwind CSS
-- ========================================================

CREATE DATABASE IF NOT EXISTS `chaveamento` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `chaveamento`;

-- 1. TABELA DE USUÁRIOS (com senhas criptografadas em bcrypt)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `tipo` VARCHAR(20) NOT NULL DEFAULT 'admin',
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABELA DE TORNEIOS
CREATE TABLE IF NOT EXISTS `torneios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NULL,
  `nome` VARCHAR(150) NOT NULL,
  `modalidade` VARCHAR(60) NOT NULL,
  `categoria` VARCHAR(80) NULL,
  `formato` VARCHAR(50) NOT NULL DEFAULT 'mata-mata',
  `status` VARCHAR(30) NOT NULL DEFAULT 'em_andamento',
  `total_equipes` INT NOT NULL,
  `tem_terceiro_lugar` TINYINT(1) NOT NULL DEFAULT 0,
  `criterio_desempate` VARCHAR(80) NOT NULL DEFAULT 'penaltis',
  `campeao_id` INT NULL,
  `vice_id` INT NULL,
  `terceiro_id` INT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`usuario_id`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABELA DE EQUIPES
CREATE TABLE IF NOT EXISTS `equipes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `torneio_id` INT NOT NULL,
  `nome` VARCHAR(120) NOT NULL,
  `posicao_inicial` INT NOT NULL DEFAULT 0,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`torneio_id`),
  CONSTRAINT `fk_equipes_torneio` FOREIGN KEY (`torneio_id`) REFERENCES `torneios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABELA DE PARTIDAS (Confrontos do chaveamento)
CREATE TABLE IF NOT EXISTS `partidas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `torneio_id` INT NOT NULL,
  `fase` INT NOT NULL,
  `nome_fase` VARCHAR(60) NOT NULL,
  `numero_jogo` INT NOT NULL,
  `equipe1_id` INT NULL,
  `equipe2_id` INT NULL,
  `placar1` INT NULL,
  `placar2` INT NULL,
  `penaltis1` INT NULL,
  `penaltis2` INT NULL,
  `vencedor_id` INT NULL,
  `perdedor_id` INT NULL,
  `proxima_partida_id` INT NULL,
  `proxima_slot` INT NOT NULL DEFAULT 1,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pendente',
  `observacoes` TEXT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`torneio_id`),
  INDEX (`vencedor_id`),
  CONSTRAINT `fk_partidas_torneio` FOREIGN KEY (`torneio_id`) REFERENCES `torneios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABELA DE LOGS DE AUDITORIA (Rastreabilidade rigorosa contra manipulação)
CREATE TABLE IF NOT EXISTS `logs_auditoria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `torneio_id` INT NULL,
  `usuario_id` INT NULL,
  `usuario_nome` VARCHAR(120) NOT NULL,
  `acao` VARCHAR(80) NOT NULL,
  `descricao` TEXT NOT NULL,
  `detalhes` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`torneio_id`),
  INDEX (`usuario_id`),
  INDEX (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- USUÁRIO ADMINISTRADOR PADRÃO (Senha: admin123)
-- Hash gerado via PASSWORD_BCRYPT
INSERT INTO `usuarios` (`nome`, `email`, `senha`, `tipo`)
VALUES (
  'Administrador Geral',
  'admin@etec.sp.gov.br',
  '$2y$10$oE08s.rG8n/G659R2o3kO.Q6u4eT6I2d2k6t1N9N1yGqW877mUfU6',
  'admin'
) ON DUPLICATE KEY UPDATE `id`=`id`;
