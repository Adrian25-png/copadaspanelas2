<?php
// conexao.php – conexão segura usando PDO

function conectar() {
    $host = 'localhost';       // Servidor do banco
    $dbname = 'copa';          // Nome do banco
    $username = 'root';        // Usuário
    $password = '';            // Senha

    try {
        // Criar conexão PDO
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        // Habilitar erros como exceções
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo; // Retornar conexão
    } catch (PDOException $e) {
        // Exibir erro e encerrar
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }
}
?>