<?php
#session_start(); // Inicie a sessão para verificar a autenticação
?>
<header>
    <!-- Ícone da copa centralizado -->
    <div id="Icon">
        <a href="HomePage2.php">
            <img src="../../public/img/ESCUDO COPA DAS PANELAS.png" alt="Logo">
        </a>
    </div>

    <!-- Login no canto direito -->
    <div class="cadastro">
        <?php if (isset($_SESSION['admin_id'])): ?>
            <a href="../adm/rodadas_adm.php" class="fas fa-user"> Entrar</a>
        <?php else: ?>
            <a href="../pages/adm/login.php" class="fas fa-user">Login</a>
        <?php endif; ?>
    </div>
</header>

<!-- Navegação abaixo do header -->
<nav id="nav-menu">
    <ul>
        <li><a href="../pages/HomePage.php">Home</a></li>
        <li>
            <a href="#">Tabelas de Jogos ▾</a>
            <ul class="dropdown">
                <li><a href="../pages/tabela_de_classificacao.php">Grupos</a></li>
                <li><a href="../pages/exibir_finais.php">Eliminatórias</a></li>
                <li><a href="../pages/rodadas.php">Rodadas</a></li>
            </ul>
        </li>
        <li>
            <a href="#">Dados da Copa ▾</a>
            <ul class="dropdown">
                <li><a href="../pages/publicacoes.php">Publicações</a></li>
                <li><a href="../pages/sobreNosHistory.php">História</a></li>
                <li><a href="#">Estatísticas</a></li>
            </ul>
        </li>
        <li><a href="Jogos Proximos.php">Transmissão</a></li>
        <li><a href="../pages/sobreNosTeam.php">Sobre nós</a></li>
    </ul>
</nav>
