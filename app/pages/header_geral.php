<?php
#session_start(); // Inicie a sessão para verificar a autenticação
?>
<header>
    <!--Icone da copa-->
    <div id="Icon">
        <a href="HomePage2.php">
            <img src="/copadaspanelas2/public/img/ESCUDO COPA DAS PANELAS.png" alt="Logo">
        </a>
    </div>
    <!--Cadastro-->
    <div class="cadastro">
        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
            <a href="adm/dashboard_simple.php" style="background: #27ae60; padding: 8px 12px; border-radius: 5px; margin-right: 10px; text-decoration: none; color: white;">
                <i class="fas fa-cogs"></i> Admin
            </a>
            <a href="adm/login_simple.php?logout=1">Sair</a>
        <?php else: ?>
            <a href="adm/login_simple.php">Login Admin</a>
        <?php endif; ?>
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

</header>