<?php
function conectar() {
    $host = 'localhost'; // ou '127.0.0.1'
    $port = '3306';
    $dbname = 'copa';
    $username = 'root';
    $password = ''; // sua senha local do MySQL, se tiver

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }
}
?>