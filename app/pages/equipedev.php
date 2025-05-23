<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipe dev</title>
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="../../public/css/devs.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'header_geral.php' ?>

	<div class="team-wrapper fade-in"> <!-- Adicionando um wrapper para controlar melhor o espaçamento -->
        <section class="team">
            <div class="center">
                <h1>Nosso Time</h1>
            </div>

			<div class="team-content">
				<div class="box">
					<img src="../../public/img/Dev_s/thwatt.jpg">
					<h3>Thwaverton</h3>
					<h5>Tec. Informatica</h5>
					<div class="icons">
						<a href="#"><i class="ri-twitter-fill"></i></a>
						<a href="https://github.com/"><i class="fa-brands fa-square-github"></i></i></a>
						<a href="https://www.instagram.com/thwaverton/"><i class="ri-instagram-fill"></i></a>
					</div>
				</div>

				<div class="box">
					<img src="../../public/img/Dev_s/luizatt.jpg">
					<h3>Luiz</h3>
					<h5>Tec. Informatica</h5>
					<div class="icons">
						<a href="#"><i class="ri-twitter-fill"></i></a>
						<a href="https://github.com/Kizzo-liso"><i class="fa-brands fa-square-github"></i></i></a>
						<a href="https://www.instagram.com/luiz_bjl/"><i class="ri-instagram-fill"></i></a>
					</div>
				</div>

				<div class="box">
					<img src="../../public/img/Dev_s/Eduardo.jfif">
					<h3>Eduardo</h3>
					<h5>Tec. Informatica</h5>
					<div class="icons">
						<a href="#"><i class="ri-twitter-fill"></i></a>
						<a href="https://github.com/Napoleoni-TheCreator"><i class="fa-brands fa-square-github"></i></a>
						<a href="https://www.instagram.com/"><i class="ri-instagram-fill"></i></a>
					</div>
				</div>

				<div class="box">
					<img src="../../public/img/Dev_s/sharaia.jpg">
					<h3>Shaiara</h3>
					<h5>Tec. Informatica</h5>
					<div class="icons">
						<a href="#"><i class="ri-twitter-fill"></i></a>
						<a href="https://github.com/"><i class="fa-brands fa-square-github"></i></i></a>
						<a href="https://www.instagram.com/shayara_mary/"><i class="ri-instagram-fill"></i></a>
					</div>
				</div>

				<div class="box">
					<img src="../../public/img/Dev_s/riqqueleat.png">
					<h3>Riquele</h3>
					<h5>Tec. Informatica</h5>
					<div class="icons">
						<a href="#"><i class="ri-twitter-fill"></i></a>
						<a href="https://github.com/"><i class="fa-brands fa-square-github"></i></i></a>
						<a href="https://www.instagram.com/riquelecostaa/"><i class="ri-instagram-fill"></i></a>
					</div>
				</div>

				<div class="box">
					<img src="../../public/img/Dev_s/rafaelatt.jpg">
					<h3>Rafael</h3>
					<h5>Tec. Informatica</h5>
					<div class="icons">
						<a href="#"><i class="ri-twitter-fill"></i></a>
						<a href="https://github.com/"><i class="fa-brands fa-square-github"></i></i></a>
						<a href="https://www.instagram.com/offx.moreira/"><i class="ri-instagram-fill"></i></a>
					</div>
				</div>
			</div>
		</section>
	</div> <!-- Fim do wrapper -->

	<script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.fade-in').forEach(function(el, i) {
                setTimeout(() => el.classList.add('visible'), i * 20);
            });
        });
    </script>
	
    <?php include 'footer.php' ?>
</body>

</html>