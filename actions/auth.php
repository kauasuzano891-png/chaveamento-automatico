<?php
/**
 * Ações de Autenticação e Usuários
 * Criptografia Bcrypt e Sessões Nativas
 */
require_once __DIR__ . '/../bd.php';

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

if ($acao === 'login') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        definirToast('erro', 'Campos Obrigatórios', 'Preencha o e-mail e a senha para acessar.');
        header('Location: ../login.php');
        exit;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Sucesso: inicializa sessão segura
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];

            registrarLog(null, 'LOGIN', "Usuário '{$usuario['nome']}' ({$usuario['email']}) realizou login.");
            definirToast('sucesso', 'Login Realizado', "Bem-vindo(a) de volta, {$usuario['nome']}!");
            header('Location: ../index.php');
            exit;
        } else {
            registrarLog(null, 'TENTATIVA_LOGIN_FALHA', "Tentativa frustrada de login para o e-mail: {$email}");
            definirToast('erro', 'Acesso Negado', 'E-mail ou senha incorretos. Verifique suas credenciais.');
            header('Location: ../login.php');
            exit;
        }
    } catch (Exception $e) {
        definirToast('erro', 'Erro Interno', 'Falha ao processar login: ' . $e->getMessage());
        header('Location: ../login.php');
        exit;
    }
}

if ($acao === 'cadastro') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senhaConf = $_POST['senha_confirmacao'] ?? '';

    if (empty($nome) || empty($email) || empty($senha)) {
        definirToast('erro', 'Dados Incompletos', 'Preencha todos os campos do formulário.');
        header('Location: ../cadastro.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        definirToast('erro', 'E-mail Inválido', 'Informe um endereço de e-mail válido.');
        header('Location: ../cadastro.php');
        exit;
    }

    if (strlen($senha) < 6) {
        definirToast('erro', 'Senha Fraca', 'A senha deve conter no mínimo 6 caracteres.');
        header('Location: ../cadastro.php');
        exit;
    }

    if ($senha !== $senhaConf) {
        definirToast('erro', 'Senhas Diferentes', 'A confirmação da senha não confere.');
        header('Location: ../cadastro.php');
        exit;
    }

    try {
        $db = getDB();

        // Verifica se e-mail já existe
        $check = $db->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch()) {
            definirToast('aviso', 'E-mail Já Cadastrado', 'Já existe uma conta cadastrada com este e-mail.');
            header('Location: ../cadastro.php');
            exit;
        }

        // Criptografia obrigatória com bcrypt
        $hash = password_hash($senha, PASSWORD_BCRYPT);
        $insert = $db->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'admin')");
        $insert->execute([$nome, $email, $hash]);

        $novoId = $db->lastInsertId();

        // Faz login automático
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $novoId;
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_email'] = $email;
        $_SESSION['usuario_tipo'] = 'admin';

        registrarLog(null, 'CADASTRO_USUARIO', "Novo usuário cadastrado: '{$nome}' ({$email})");
        definirToast('sucesso', 'Cadastro Concluído', "Conta de organizador criada com sucesso! Bem-vindo(a).");
        header('Location: ../index.php');
        exit;
    } catch (Exception $e) {
        definirToast('erro', 'Erro ao Cadastrar', 'Não foi possível cadastrar: ' . $e->getMessage());
        header('Location: ../cadastro.php');
        exit;
    }
}

if ($acao === 'logout') {
    $user = obterUsuarioLogado();
    if ($user) {
        registrarLog(null, 'LOGOUT', "Usuário '{$user['nome']}' desconectou-se do sistema.");
    }
    session_destroy();
    session_start();
    definirToast('info', 'Sessão Encerrada', 'Você saiu do sistema com segurança.');
    header('Location: ../login.php');
    exit;
}

header('Location: ../index.php');
exit;
