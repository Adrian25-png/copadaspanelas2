<?php
session_start(); // Inicie a sessão para verificar a autenticação
?>
<header>
    <!--Icone da copa-->
    <div id="Icon">
        <a href="HomePage2.php">
            <img src="/copadaspanelas2/public/img/ESCUDO COPA DAS PANELAS.png" alt="Logo">
        </a>
    </div>

    <!-- Barra de navegação -->
    <nav id="nav-menu">
        <ul>
            <li><a href="../pages/HomePage2.php">Home</a></li>
            <li>
                <a href="">Tabelas de Jogos ▾</a>
                <ul class="dropdown">
                   <li><a href="../pages/tabela_de_classificacao.php">Grupos</a></li>
                   <li><a href="../pages/exibir_finais.php">Eliminatórias</a></li>
                   <li><a href="../pages/rodadas.php">Rodadas</a></li>
                </ul>     
            </li>
            <li>
                <a href="">Dados da Copa ▾</a>
                <ul class="dropdown">
                    <li><a href="../pages/publicacoes.php">Publicações</a></li>
                    <li><a href="../pages/sobreNosHistory.php">História</a></li>
                    <li><a href="../pages/estatistica.php">Estatísticas</a></li> <!--Criar um dropdown para os outros arquivos de estatistica de jogador-->
                </ul>
            </li>
            <li><a href="JogosProximos.php">Transmissão</a></li>
            <li><a href="../pages/sobreNosTeam.php">Sobre nós</a></li>
        </ul>
    </nav>

    <!--Login Admin-->
    <div class="admin-login">
        <?php
        // Detectar se estamos em uma subpasta
        $currentPath = $_SERVER['REQUEST_URI'];
        $isInAdmFolder = strpos($currentPath, '/adm/') !== false;
        $adminPath = $isInAdmFolder ? '' : 'adm/';
        ?>
        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
            <a href="<?= $adminPath ?>dashboard_simple.php" class="admin-btn">
                <i class="fas fa-cogs"></i> Dashboard
            </a>
            <a href="<?= $adminPath ?>login_simple.php?logout=1" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        <?php else: ?>
            <a href="<?= $adminPath ?>login_simple.php" class="login-btn">
                <i class="fas fa-user-shield"></i> Admin
            </a>
        <?php endif; ?>
    </div>

</header>

<!-- Script de áudio global -->
<script src="/copadaspanelas2/public/js/global-audio.js"></script>