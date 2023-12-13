<?php
include 'db.php'; // Подключение к базе данных

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["employee_id_to_delete"])) {
    $employee_id_to_delete = $_GET["employee_id_to_delete"];

    // Удаление сотрудника
    $stmt = $pdo->prepare("DELETE FROM employees WHERE employee_id = ?");
    $stmt->execute([$employee_id_to_delete]);

    // Перенаправление на главную страницу или другую страницу после удаления
    header("Location: index.php");
    exit();
}
?>
