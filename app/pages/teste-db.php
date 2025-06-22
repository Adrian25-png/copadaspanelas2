<?php
$url = getenv("DATABASE_URL");
if (!$url) {
    die("Variável DATABASE_URL não está definida.\n");
}
echo "DATABASE_URL: $url\n";

$parsed = parse_url($url);
print_r($parsed);

$host = $parsed["host"] ?? null;
$port = $parsed["port"] ?? null;
$dbname = ltrim($parsed["path"] ?? '', '/');
$username = $parsed["user"] ?? null;
$password = $parsed["pass"] ?? null;

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão OK!";
} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}