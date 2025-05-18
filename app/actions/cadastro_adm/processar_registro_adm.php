<?php
session_start();
require_once("../../config/conexao.php");

// Ativar exibição de erros (para depuração)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pdo = conectar(); // Conectar com PDO

// Verificar CSRF token
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('Location: ../../pages/adm/cadastro_adm.php?error=token');
    exit();
}

// Validar campos do formulário
$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';

// Função para validar e-mail
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && preg_match('/@.*\.com$/', $email);
}

// Função para validar domínio
function validarDominioEmail($email) {
    $dominios_validos = ['gmail.com', 'hotmail.com'];
    $dominio = substr(strrchr($email, "@"), 1);
    return in_array($dominio, $dominios_validos);
}

// Validar e-mail
if (!validarEmail($email)) {
    header('Location: ../../pages/adm/cadastro_adm.php?error=email');
    exit();
}

if (!validarDominioEmail($email)) {
    header('Location: ../../pages/adm/cadastro_adm.php?error=dominio');
    exit();
}

// Verificar se nome já existe
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE nome = ?");
$stmt->execute([$nome]);
if ($stmt->fetchColumn() > 0) {
    header('Location: ../../pages/adm/cadastro_adm.php?error=nome_existente');
    exit();
}

// Verificar se email já existe
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetchColumn() > 0) {
    header('Location: ../../pages/adm/cadastro_adm.php?error=email_existente');
    exit();
}

// Hash da senha
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// Função para gerar código único
function gerarCodigoAdm($pdo) {
    $ano_atual = date("Y");
    $prefixo = "cpTelsr";
    $ano_prefixo = $ano_atual . $prefixo;

    $stmt = $pdo->prepare("SELECT cod_adm FROM admin WHERE cod_adm LIKE ?");
    $stmt->execute([$ano_prefixo . '%']);

    $ids_existentes = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id_atual = (int)substr($row['cod_adm'], strlen($ano_prefixo));
        $ids_existentes[] = $id_atual;
    }

    $proximo_id = 1;
    while (in_array($proximo_id, $ids_existentes)) {
        $proximo_id++;
    }

    return $ano_prefixo . $proximo_id;
}

$cod_adm = gerarCodigoAdm($pdo);

// Inserir no banco
$stmt = $pdo->prepare("INSERT INTO admin (cod_adm, nome, email, senha) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$cod_adm, $nome, $email, $senha_hash])) {
    header('Location: ../../pages/adm/cadastro_adm.php?success=1');
    exit();
} else {
    header('Location: ../../pages/adm/cadastro_adm.php?error=db');
    exit();
}