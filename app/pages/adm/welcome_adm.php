<?php
session_start();
include("../../actions/cadastro_adm/session_check.php");
include_once '../../config/conexao.php';
$pdo = conectar();
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Rodadas</title>
    <link rel="stylesheet" href="/copadaspanelas2/public/css/adm/rodadas_adm.css">
    <link rel="stylesheet" href="/copadaspanelas2/public/css/cssfooter.css">
    <link rel="stylesheet" href="/copadaspanelas2/public/css/adm/header_adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'header_adm.php'; ?>

<h1 class="titulo-central fade-in">SEJA BEM VINDO ADMINSTRADOR(A)!</h1>
<h2 class="titulo-central fade-in">SEGUE ABAIXO AS OPÇÕES DE ADMINISTRADOR(A): </h2>

<?php include '../footer.php'; ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.fade-in').forEach(function(el, i) {
            setTimeout(() => el.classList.add('visible'), i * 20);
        });
    });
</script>
</body>
</html>