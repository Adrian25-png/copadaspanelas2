<?php
session_start(); // Inicia sessão
require_once("../../config/conexao.php"); // Função conectar()
$pdo = conectar(); // Conecta ao banco

// Ativar exibição de erros (para depuração)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Mensagem para usuário (sucesso/erro)
$msg = "";

// Lógica para excluir admin via GET 'excluir_cod'
if (isset($_GET['excluir_cod'])) {
    $cod_adm = $_GET['excluir_cod'];

    // Preparar e executar exclusão segura
    $stmt = $pdo->prepare("DELETE FROM admin WHERE cod_adm = ?");
    if ($stmt->execute([$cod_adm])) {
        $msg = "Administrador $cod_adm excluído com sucesso.";
    } else {
        $msg = "Erro ao excluir administrador $cod_adm.";
    }
}

// Buscar todos os administradores
$stmt = $pdo->query("SELECT cod_adm, nome, email FROM admin ORDER BY cod_adm ASC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Administradores</title>
    <link rel="stylesheet" href="/copadaspanelas2/public/css/adm/listar_adms.css">
    <link rel="stylesheet" href="/copadaspanelas2/public/css/cssfooter.css">
    <link rel="stylesheet" href="/copadaspanelas2/public/css/adm/header_adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>

<?php include 'header_adm.php'; ?>

<div class="form-container">
    <h2>ADMINISTRADORES CADASTRADOS</h2>

    <?php if ($msg): ?>
        <div class="msg <?= strpos($msg, 'sucesso') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <?php if (count($admins) === 0): ?>
        <p>Nenhum administrador cadastrado.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?= htmlspecialchars($admin['cod_adm']) ?></td>
                        <td><?= htmlspecialchars($admin['nome']) ?></td>
                        <td><?= htmlspecialchars($admin['email']) ?></td>
                        <td>
                            <a class="btn-excluir" href="?excluir_cod=<?= urlencode($admin['cod_adm']) ?>" 
                               onclick="return confirm('Tem certeza que deseja excluir o administrador <?= htmlspecialchars($admin['nome']) ?>?');">
                               Excluir
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>

</body>
</html>