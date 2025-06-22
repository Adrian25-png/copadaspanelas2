<?php
function conectar() {
    $url = parse_url(getenv("DATABASE_URL"));

    $host = $url["host"];
    $port = $url["port"];
    $dbname = ltrim($url["path"], '/');
    $username = $url["user"];
    $password = $url["pass"];

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