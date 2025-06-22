<?php
function conectar() {
    if ($_SERVER['SERVER_NAME'] === 'localhost') {
        // Ambiente local (XAMPP)
        $host = '127.0.0.1';
        $port = '3306';
        $dbname = 'copa';
        $username = 'root';
        $password = ''; // senha local do XAMPP
    } else {
        // Ambiente Railway
        $host = 'switchback.proxy.rlwy.net';
        $port = '3306';
        $dbname = 'railway'; // troque aqui pelo nome real do banco no Railway
        $username = 'root';
        $password = 'awesBgGcSgDAJOgEEMIEVBvjuAvaXRJW'; // senha real do Railway
    }

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