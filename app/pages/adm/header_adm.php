<?php
// Garante que a sessão foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado como administrador
$usuarioLogado = isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
?>

<header>
    <!-- Ícone da Copa Centralizado -->
    <div id="Icon">
        <a href="../HomePage2.php">
            <img src="/copadaspanelas2/public/img/ESCUDO COPA DAS PANELAS.png" alt="Logo">
        </a>
    </div>

    <!-- Botão Login/Deslogar -->
    <div class="deslogar">
        <?php if ($usuarioLogado): ?>
            <a href="../adm/logout.php">Deslogar</a>
        <?php else: ?>
            <a href="../adm/login.php">Entrar</a>
            
        <?php endif; ?>
    </div>

    <!-- Menu de navegação -->
    <nav id="nav-menu">
        <ul>
            <li><a href="../HomePage2.php">Home</a></li>

            <li>
                <a href="#">Rodadas ▾</a>
                <ul class="dropdown">
                    <li><a href="../rodadas.php">Rodadas</a></li>
                    <?php if ($usuarioLogado): ?>
                        <li><a href="../adm/rodadas_adm.php">Administrar Rodadas</a></li>
                        <li><a href="../adm/adicionar_grupo.php">Criar novo campeonato</a></li>
                        <li><a href="../adm/adicionar_times.php">Adicionar times</a></li>
                        <li><a href="../adm/editar_time.php">Editar times</a></li>
                        <li><a href="../adm/adicionar_times_de_forma_aleatoria.php">Adicionar times aleatoriamente</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <li>
                <a href="#">Classificação ▾</a>
                <ul class="dropdown">
                    <li><a href="../tabela_de_classificacao.php">Grupos</a></li>
                    <?php if ($usuarioLogado): ?>
                        <li><a href="../classificar.php">Classificados</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <li>
                <a href="#">Finais ▾</a>
                <ul class="dropdown">
                    <li><a href="../exibir_finais.php">Finais</a></li>
                    <?php if ($usuarioLogado): ?>
                        <li><a href="../adm/adicionar_dados_finais.php">Administrar finais</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <li>
                <a href="#">Estatísticas ▾</a>
                <ul class="dropdown">
                    <li><a href="../estatistica.php">Ver estatísticas</a></li>
                    <?php if ($usuarioLogado): ?>
                        <li><a href="../adm/crud_jogador.php">Administrar jogadores</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        </ul>
    </nav>
</header>