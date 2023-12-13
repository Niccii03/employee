<?php
include 'db.php'; // Подключение к базе данных

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_employee"])) {
    $employee_id_to_update = $_POST["employee_id_to_update"];
    $new_name = $_POST["new_name"];
    $new_salary = $_POST["new_salary"];
    $new_department = $_POST["new_department"];
    $new_position = $_POST["new_position"];

    // Обновление данных сотрудника
    $stmt = $pdo->prepare("UPDATE employees SET employee_name = ?, employee_salary = ?, department_id = ?, position_id = ? WHERE employee_id = ?");
    $result = $stmt->execute([$new_name, $new_salary, $new_department, $new_position, $employee_id_to_update]);

    // Отправка JSON-ответа
    header('Content-Type: application/json');
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error updating employee']);
        var_dump($_POST); // Добавьте эту строку для отладки
        var_dump($stmt->errorInfo()); // Добавьте эту строку для вывода информации об ошибке в запросе
    }
    exit();
} else {
    echo json_encode(['error' => 'Invalid request']);
    var_dump($_POST); // Добавьте эту строку для отладки
    exit();
}
?>
