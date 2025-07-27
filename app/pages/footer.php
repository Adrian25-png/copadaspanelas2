<?php
// Verifica se o usuário está logado como administrador
$usuarioLogado = isset($_SESSION['admin_id']);
?>

<footer>
    <div class="footerContain">
        <div class="socialIcons">
            <a href="https://www.youtube.com/@copadaspanelasifbjl4623" target="_blank"><i class="fa-brands fa-youtube"></i></a>
            <a href="https://www.instagram.com/pan_cup/" target="_blank"><i class="fa-brands fa-instagram"></i></a>
        </div>

        <div class="footerNav">
            <ul>
                <li><a href="../pages/HomePage2.php">Página Inicial</a></li>
                <li><a href="../pages/sobreNosTeam.php">Sobre Nós</a></li>
                <li><a href="../pages/equipedev.php">Equipe Dev</a></li>
            </ul>
        </div>

        <div class="footerButton">
            <p>Copyright &copy; 2024; Desenvolvido por <span class="dev">PANELASCUP</span></p>
            <p>Instituto Federal de Educação, Ciência e Tecnologia Baiano – Campus Bom Jesus da Lapa
                BR 349, Km 14 - Zona Rural, Bom Jesus da Lapa - Bahia, CEP: 47600-000</p>
        </div>
    </div>
</footer>
