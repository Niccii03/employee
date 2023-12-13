<?php
$host = "localhost";
$dbname = "Employee-2";
$user = "postgres";
$password = "W23062010w";

$dsn = "pgsql:host=$host;dbname=$dbname;user=$user;password=$password";

try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>
