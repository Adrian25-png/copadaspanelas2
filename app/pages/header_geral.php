<?php
#session_start(); // Inicie a sessão para verificar a autenticação
?>
<header>
    <!--Icone da copa-->
    <div id="Icon">
        <a href="HomePage2.php">
            <img src="../../public/img/ESCUDO COPA DAS PANELAS.png" alt="Logo">
        </a>
    </div>
    <!--Cadastro-->
    <div class="cadastro">
        <?php if (isset($_SESSION['admin_id'])): ?>
            <a href="../adm/rodadas_adm.php">Entrar</a>
        <?php else: ?>
            <a href="../pages/adm/login.php">Login</a>
        <?php endif; ?>
    </div>
    
</header>