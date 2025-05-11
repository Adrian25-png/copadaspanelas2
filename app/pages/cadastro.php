<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar</title>
    <link rel="stylesheet" type="text/css" href="../../public/css/cadastrocss.css">
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
</head>
<body>
    <?php include 'header_geral.php'; ?>

    <div class="container">
        <h1>Cadastro</h1>
        <form class="register-form" method="POST" action="">
            <div class="input-field">
              <input type="text" name="nome" required spellcheck="false">
              <label>Nome</label>
            </div>
            <div class="input-field">
              <input type="text" name="sobrenome" required spellcheck="false">
              <label>Sobrenome</label>
            </div>
            <div class="input-field">
              <input type="text" name="usuario" required spellcheck="false">
              <label>Nome de Usuário</label>
            </div>
            <div class="input-field">
              <input type="email" name="email" required spellcheck="false">
              <label>Email</label>
            </div>
            <div class="input-field">
              <input type="password" name="senha" required>
              <label>Senha</label>
            </div>
            <div class="input-field">
              <input type="password" name="confirmar_senha" required>
              <label>Confirmação de Senha</label>
            </div>
            <button type="submit">REGISTRAR</button>
          </form>
    </div>

    <?php include 'footer.php'; ?>

</body>

</html>
