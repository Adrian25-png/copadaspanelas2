<?php
#session_start(); // Inicie a sessão para verificar a autenticação
?>
<header>

    <!--Ícone da copa.-->
    <div id="Icon">
        <a href="HomePage.php"><img src="../../public/img/ESCUDO COPA DAS PANELAS.png" alt="Logo"></a>
    </div>

    <!--Título da copa.-->
    <!-- <div id="titulo-container">
        <div id="titulo">COPA DAS PANELAS</div>
    </div> -->

    <!--Login.-->
    <div class="cadastro">
                
                <?php if (isset($_SESSION['admin_id'])): ?>
                <a href="../adm/rodadas_adm.php" class="fas fa-user"> Entrar</a>
                <?php else: ?>
                <a href="../pages/adm/login.php" class="fas fa-user">Login</a>
                <?php endif; ?>
                
    
            </div>
    

</header>