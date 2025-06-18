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
    <title>Painel do Administrador</title>
    <link rel="stylesheet" href="/copadaspanelas2/public/css/cssfooter.css">
    <link rel="stylesheet" href="/copadaspanelas2/public/css/adm/header_adm.css">
    <link rel="stylesheet" href="/copadaspanelas2/public/css/adm/welcome_adm.css"> <!-- Novo CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'header_adm.php'; ?>

<h1 class="titulo-central fade-in">SEJA BEM VINDO ADMINISTRADOR(A)!</h1>
<h2 class="titulo-central fade-in">ESCOLHA UMA DAS OPÇÕES ABAIXO PARA GERENCIAR:</h2>

<div class="admin-panel">

    <a href="../adm/rodadas_adm.php" class="admin-card">
        <div class="card-image">
            <img src="/copadaspanelas2/public/img/adm_icons/rodadas.png" alt="RODADAS">
        </div>
        <h3>Administrar Rodadas</h3>
        <p>Visualize e edite as rodadas do campeonato.</p>
    </a>

    <a href="../adm/adicionar_grupo.php" class="admin-card">
        <div class="card-image">
            <img src="/copadaspanelas2/public/img/adm_icons/campeonato.png" alt="CAMPEONATO">
        </div>
        <h3>Criar Novo Campeonato</h3>
        <p>Inicie um novo torneio e configure seus grupos.</p>
    </a>

    <a href="../adm/adicionar_times.php" class="admin-card">
        <div class="card-image">
            <img src="" alt="TIMES">
        </div>
        <h3>Adicionar Times</h3>
        <p>Inclua novos times manualmente no campeonato.</p>
    </a>

    <a href="../adm/editar_time.php" class="admin-card">
        <div class="card-image">
            <img src="" alt="EDITAR TIMES">
        </div>
        <h3>Editar Times</h3>
        <p>Modifique informações dos times cadastrados.</p>
    </a>

    <a href="../adm/adicionar_times_de_forma_aleatoria.php" class="admin-card">
        <div class="card-image">
            <img src="" alt="TIMES ALEATÓRIOS">
        </div>
        <h3>Adicionar Times Aleatoriamente</h3>
        <p>Preencha os grupos automaticamente com times.</p>
    </a>

    <a href="../classificar.php" class="admin-card">
        <div class="card-image">
            <img src="" alt="CLASSIFICAR TIMES">
        </div>
        <h3>Classificar Times</h3>
        <p>Defina quais times avançam para a próxima fase.</p>
    </a>

    <a href="../adm/adicionar_dados_finais.php" class="admin-card">
        <div class="card-image">
            <img src="" alt="ADMINISTRAR FINAIS">
        </div>
        <h3>Administrar Finais</h3>
        <p>Gerencie os dados das partidas finais.</p>
    </a>

    <a href="../adm/crud_jogador.php" class="admin-card">
        <div class="card-image">
            <img src="" alt="ADMINISTRAR JOGADORES">
        </div>
        <h3>Administrar Jogadores</h3>
        <p>Adicione, edite ou remova jogadores e suas estatísticas.</p>
    </a>

</div>

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