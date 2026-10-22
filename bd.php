<?php
/**
 * Conexão com o Banco de Dados via PDO
 * Sistema de Chaveamento Esportivo Automático
 * 
 * Regras:
 * - Sempre usar PDO com Prepared Statements
 * - Sessões nativas estruturadas
 * - Tratamento de logs e auditoria
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações do MySQL (Padrão XAMPP/WampServer)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'chaveamento');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna a instância única de conexão PDO
 * @return PDO
 */
function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $mysqlDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 1,
    ];

    try {
        // Tenta conexão direta com a base MySQL
        $pdo = new PDO($mysqlDsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Se a base 'chaveamento' ainda não existir no MySQL, tenta criá-la automaticamente
        try {
            $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
            $tempPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            $pdo = new PDO($mysqlDsn, DB_USER, DB_PASS, $options);
            inicializarTabelas($pdo, 'mysql');
            return $pdo;
        } catch (PDOException $e2) {
            // Caso o serviço MySQL não esteja ativo no momento local,
            // utiliza fallback transparente em SQLite para garantir funcionamento contínuo
            $sqliteFile = __DIR__ . '/chaveamento.sqlite';
            $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec("PRAGMA foreign_keys = ON;");
            inicializarTabelas($pdo, 'sqlite');
            return $pdo;
        }
    }
}

/**
 * Cria as tabelas do sistema caso ainda não existam
 */
function inicializarTabelas(PDO $pdo, string $driver) {
    if ($driver === 'sqlite') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                senha TEXT NOT NULL,
                tipo TEXT NOT NULL DEFAULT 'admin',
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS torneios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER,
                nome TEXT NOT NULL,
                modalidade TEXT NOT NULL,
                categoria TEXT,
                formato TEXT NOT NULL DEFAULT 'mata-mata',
                status TEXT NOT NULL DEFAULT 'em_andamento',
                total_equipes INTEGER NOT NULL,
                tem_terceiro_lugar INTEGER NOT NULL DEFAULT 0,
                criterio_desempate TEXT NOT NULL DEFAULT 'penaltis',
                campeao_id INTEGER,
                vice_id INTEGER,
                terceiro_id INTEGER,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS equipes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                torneio_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                posicao_inicial INTEGER DEFAULT 0,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (torneio_id) REFERENCES torneios (id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS partidas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                torneio_id INTEGER NOT NULL,
                fase INTEGER NOT NULL,
                nome_fase TEXT NOT NULL,
                numero_jogo INTEGER NOT NULL,
                equipe1_id INTEGER,
                equipe2_id INTEGER,
                placar1 INTEGER,
                placar2 INTEGER,
                penaltis1 INTEGER,
                penaltis2 INTEGER,
                vencedor_id INTEGER,
                perdedor_id INTEGER,
                proxima_partida_id INTEGER,
                proxima_slot INTEGER NOT NULL DEFAULT 1,
                status TEXT NOT NULL DEFAULT 'pendente',
                observacoes TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (torneio_id) REFERENCES torneios (id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS logs_auditoria (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                torneio_id INTEGER,
                usuario_id INTEGER,
                usuario_nome TEXT NOT NULL,
                acao TEXT NOT NULL,
                descricao TEXT NOT NULL,
                detalhes TEXT,
                ip TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } else {
        // Driver MySQL
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `usuarios` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `nome` VARCHAR(120) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `senha` VARCHAR(255) NOT NULL,
                `tipo` VARCHAR(20) NOT NULL DEFAULT 'admin',
                `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
                `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `equipes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `torneio_id` INT NOT NULL,
                `nome` VARCHAR(120) NOT NULL,
                `posicao_inicial` INT NOT NULL DEFAULT 0,
                `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (`torneio_id`),
                CONSTRAINT `fk_equipes_torneio` FOREIGN KEY (`torneio_id`) REFERENCES `torneios` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
                CONSTRAINT `fk_partidas_torneio` FOREIGN KEY (`torneio_id`) REFERENCES `torneios` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
                INDEX (`usuario_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    // Cria usuário administrador inicial se tabela estiver vazia
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total = $stmt->fetch()['total'] ?? 0;
    if ($total == 0) {
        $hashPadrao = password_hash('admin123', PASSWORD_BCRYPT);
        $insert = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
        $insert->execute(['Administrador Geral', 'admin@etec.sp.gov.br', $hashPadrao, 'admin']);
    }
}

/**
 * Registra log para auditoria de qualquer alteração no sistema
 * Essencial para transparência e regras contra manipulação
 */
function registrarLog(?int $torneio_id, string $acao, string $descricao, ?string $detalhes = null): bool {
    try {
        $db = getDB();
        $user = obterUsuarioLogado();
        $userId = $user ? $user['id'] : null;
        $userNome = $user ? $user['nome'] : 'Sistema / Visitante';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $db->prepare("
            INSERT INTO logs_auditoria (torneio_id, usuario_id, usuario_nome, acao, descricao, detalhes, ip)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$torneio_id, $userId, $userNome, $acao, $descricao, $detalhes, $ip]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Retorna os dados do usuário atualmente logado na sessão
 */
function obterUsuarioLogado(): ?array {
    if (!empty($_SESSION['usuario_id']) && !empty($_SESSION['usuario_nome'])) {
        return [
            'id' => $_SESSION['usuario_id'],
            'nome' => $_SESSION['usuario_nome'],
            'email' => $_SESSION['usuario_email'] ?? '',
            'tipo' => $_SESSION['usuario_tipo'] ?? 'admin'
        ];
    }
    return null;
}

/**
 * Exige que o usuário esteja logado para prosseguir
 */
function exigirLogin(): void {
    if (!obterUsuarioLogado()) {
        definirToast('aviso', 'Acesso Restrito', 'Faça login para realizar alterações ou gerenciar campeonatos.');
        header('Location: login.php');
        exit;
    }
}

/**
 * Define uma notificação TOAST para ser exibida na próxima requisição
 */
function definirToast(string $tipo, string $titulo, string $mensagem): void {
    $_SESSION['toast'] = [
        'tipo' => $tipo, // 'sucesso', 'erro', 'aviso', 'info'
        'titulo' => $titulo,
        'mensagem' => $mensagem
    ];
}

/**
 * Recupera e consome a notificação TOAST pendente
 */
function obterToast(): ?array {
    if (isset($_SESSION['toast'])) {
        $toast = $_SESSION['toast'];
        unset($_SESSION['toast']);
        return $toast;
    }
    return null;
}

/**
 * Sanitiza saída de strings contra XSS
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
