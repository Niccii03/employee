<?php
include 'db.php';

// Функция для расчета средней зарплаты по должностям и отделам
function calculateAverageSalaryByDepartmentAndPosition()
{
    global $pdo;

    // Выполните SQL-запрос для расчета средней зарплаты
    $sql = "SELECT 
                departments.department_name,
                positions.position_name,
                AVG(employees.employee_salary) AS average_salary
            FROM employees
            JOIN positions ON employees.position_id = positions.position_id
            JOIN departments ON employees.department_id = departments.department_id
            GROUP BY departments.department_name, positions.position_name";

    $stmt = $pdo->query($sql);

    // Проверка наличия результата
    if ($stmt) {
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

// Обработка формы расчета
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["calculate_average_salary"])) {
    $result = calculateAverageSalaryByDepartmentAndPosition();
}
// Функция для получения сотрудников с фильтрами

function getFilteredEmployees($filter_department, $filter_name, $filter_position)
{
    global $pdo;

    // Преобразование значений в соответствующие типы данных
    $filter_department = ($filter_department !== '') ? (int) $filter_department : null;
    $filter_name = ($filter_name !== '') ? $filter_name : null;
    $filter_position = ($filter_position !== '') ? (int) $filter_position : null;

    $stmt = $pdo->prepare("SELECT * FROM get_filtered_employees(?, ?, ?)");
    $stmt->execute([$filter_department, $filter_name, $filter_position]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Обработка формы фильтрации
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filter_department"])) {
    $filter_department = $_POST["filter_department"];
    $filter_name = isset($_POST["filter_name"]) ? $_POST["filter_name"] : null;
    $filter_position = $_POST["filter_position"];

    // Используйте функцию getFilteredEmployees для получения сотрудников с учетом выбранных фильтров
    $filteredEmployees = getFilteredEmployees($filter_department, $filter_name, $filter_position);
}

// Обработка формы добавления сотрудника
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_employee"])) {
    $employee_name = $_POST["employee_name"];
    $employee_salary = $_POST["employee_salary"];
    $department_id = $_POST["department_id"];
    $position_id = $_POST["position_id"];

    // Добавление сотрудника
    $stmt = $pdo->prepare("INSERT INTO employees (employee_name, employee_salary, department_id, position_id, created_at) 
                           VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$employee_name, $employee_salary, $department_id, $position_id]);
}

// Получение списка отделов и должностей
$departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
$positions = $pdo->query("SELECT * FROM positions")->fetchAll(PDO::FETCH_ASSOC);

// Получение списка всех сотрудников
$employees = [];

try {
    $stmt = $pdo->query("SELECT * FROM employees");
    if ($stmt) {
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        throw new Exception("Ошибка выполнения запроса: " . implode(" ", $pdo->errorInfo()));
    }
} catch (Exception $e) {
    echo "Произошла ошибка: " . $e->getMessage();
}

// Таблица сотрудников
if (!empty($employees)) {
    echo "<h2>Все сотрудники</h2>";
    echo "<table border='1'>";
    echo "<thead><tr><th>ID</th><th>Имя</th><th>Зарплата</th><th>Отдел</th><th>Должность</th><th>Дата создания</th><th>Действия</th></tr></thead>";
    echo "<tbody>";
    foreach ($employees as $employee) {
        echo "<tr>";
        echo "<td>{$employee['employee_id']}</td>";
        echo "<td>{$employee['employee_name']}</td>";
        echo "<td>{$employee['employee_salary']}</td>";
        echo "<td>{$employee['department_id']}</td>";
        echo "<td>{$employee['position_id']}</td>";
        echo "<td>{$employee['created_at']}</td>";
        echo "<td>";
        echo "<button onclick='deleteEmployee({$employee['employee_id']})'>Удалить</button>";
        // echo "<button onclick='openPopup({$employee['employee_id']})'>Изменить</button>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<h2>Нет сотрудников в базе данных</h2>";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление сотрудника</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1>Фильтрация сотрудников</h1>

    <!-- Форма фильтрации -->
    <form method="post">
        <label for="filter_department">Фильтр по отделу:</label>
        <select name="filter_department">
            <option value="">Все отделы</option>
            <?php
            $departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($departments as $department): ?>
                <option value="<?= $department['department_id'] ?>" <?= (isset($filter_department) && $filter_department == $department['department_id']) ? 'selected' : '' ?>>
                    <?= $department['department_name'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="filter_name">Фильтр по имени:</label>
        <input type="text" name="filter_name" value="<?= isset($filter_name) ? $filter_name : '' ?>">

        <label for="filter_position">Фильтр по должности:</label>
        <select name="filter_position">
            <option value="">Все должности</option>
            <?php
            $positions = $pdo->query("SELECT * FROM positions")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($positions as $position): ?>
                <option value="<?= $position['position_id'] ?>" <?= (isset($filter_position) && $filter_position == $position['position_id']) ? 'selected' : '' ?>>
                    <?= $position['position_name'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Применить фильтр</button>
    </form>
    <hr>
    <!-- Таблица сотрудников -->
    <?php if (isset($filteredEmployees) && !empty($filteredEmployees)): ?>
        <h2>Отфильтрованные сотрудники</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Зарплата</th>
                    <th>Отдел</th>
                    <th>Должность</th>
                    <th>Дата создания</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filteredEmployees as $employee): ?>
                    <tr>
                        <td>
                            <?= $employee['employee_id'] ?>
                        </td>
                        <td>
                            <?= $employee['employee_name'] ?>
                        </td>
                        <td>
                            <?= $employee['employee_salary'] ?>
                        </td>
                        <td>
                            <?= $employee['department_id'] ?>
                        </td>
                        <td>
                            <?= $employee['position_id'] ?>
                        </td>
                        <td>
                            <?= $employee['created_at'] ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif (isset($filteredEmployees) && empty($filteredEmployees)): ?>
        <h2>Нет отфильтрованных сотрудников</h2>
    <?php endif; ?>
    <h1>Расчет средней зарплаты</h1>

    <!-- Форма расчета -->
    <form method="post">
        <label for="calculation_type">Выберите тип расчета:</label>
        <select name="calculation_type" id="calculation_type">
            <option value="by_department">По отделам</option>
            <option value="by_position">По должностям</option>
        </select>

        <button type="submit" name="calculate_average_salary">Рассчитать среднюю зарплату</button>
    </form>

    <?php if (isset($result) && $result !== false): ?>
        <!-- Результаты расчета -->
        <h2>Средняя зарплата по отделам и должностям</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>Отдел</th>
                    <th>Должность</th>
                    <th>Средняя зарплата</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result as $row): ?>
                    <tr>
                        <td>
                            <?= $row['department_name'] ?>
                        </td>
                        <td>
                            <?= $row['position_name'] ?>
                        </td>
                        <td>
                            <?= $row['average_salary'] ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif (isset($result) && $result === false): ?>
        <p>Ошибка при расчете средней зарплаты по отделам и должностям.</p>
    <?php endif; ?>


    <!-- Форма добавления сотрудника -->
    <form method="post">
        <h2>Добавление сотрудника</h2>

        <label for="employee_name">Имя нового сотрудника:</label>
        <input type="text" name="employee_name" required>

        <label for="employee_salary">Зарплата нового сотрудника:</label>
        <input type="number" name="employee_salary" required>

        <!-- Выбор отдела нового сотрудника -->
        <label for="department_id">Отдел нового сотрудника:</label>
        <select name="department_id" required>
            <?php
            $departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($departments as $department): ?>
                <option value="<?= $department['department_id'] ?>">
                    <?= $department['department_name'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Выбор должности нового сотрудника -->
        <label for="position_id">Должность нового сотрудника:</label>
        <select name="position_id" required>
            <?php
            $positions = $pdo->query("SELECT * FROM positions")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($positions as $position): ?>
                <option value="<?= $position['position_id'] ?>">
                    <?= $position['position_name'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="add_employee">Добавить сотрудника</button>
    </form>

    <hr>
    <div id="popup" class="popup">
        <h2>Изменение данных сотрудника</h2>
        <form method="post" id="updateForm">
            <input type="hidden" name="employee_id_to_update" id="employee_id_to_update">
            <label for="new_name">Новое имя:</label>
            <input type="text" name="new_name" required>

            <label for="new_salary">Новая зарплата:</label>
            <input type="number" name="new_salary" required>

            <label for="new_department">Новый отдел:</label>
            <select name="new_department" required>
                <option value="">Все отделы</option>
                <?php
                $departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($departments as $department): ?>
                    <option value="<?= $department['department_id'] ?>" <?= (isset($filter_department) && $filter_department == $department['department_id']) ? 'selected' : '' ?>>
                        <?= $department['department_name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="new_position">Новая должность:</label>
            <select name="new_position" required>
                <option value="">Все должности</option>
                <?php
                $positions = $pdo->query("SELECT * FROM positions")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($positions as $position): ?>
                    <option value="<?= $position['position_id'] ?>" <?= (isset($filter_position) && $filter_position == $position['position_id']) ? 'selected' : '' ?>>
                        <?= $position['position_name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="update_employee">Сохранить изменения</button>
        </form>
        <button onclick="closePopup()">Закрыть</button>
    </div>
    <script>
        // JavaScript функция для открытия pop-up окна с данными сотрудника
        // JavaScript функция для открытия pop-up окна с данными сотрудника
        // JavaScript функция для открытия pop-up окна с данными сотрудника
        // JavaScript функция для открытия pop-up окна с данными сотрудника
        function openPopup(employeeId, employeeName, employeeSalary, departmentId, positionId) {
            var popup = $('#popup');
            var form = popup.find('form');

            var nameInput = form.find('input[name="new_name"]');
            var salaryInput = form.find('input[name="new_salary"]');
            var departmentSelect = form.find('select[name="new_department"]');
            var positionSelect = form.find('select[name="new_position"]');
            var updateButton = form.find('button[name="update_employee"]');

            nameInput.val(employeeName);
            salaryInput.val(employeeSalary);
            departmentSelect.val(departmentId);
            positionSelect.val(positionId);

            updateButton.on('click', function (event) {
                // Отправка данных для обновления сотрудника
                event.preventDefault();

                var formData = form.serializeArray();
                formData.push({ name: 'employee_id_to_update', value: employeeId });

                $.ajax({
                    type: 'POST',
                    url: 'update_employee.php',
                    data: formData,
                    success: function (response) {
                        if (response.success) {
                            // Обновление содержимого таблицы после успешного обновления
                            location.reload();
                        } else {
                            console.error('Ошибка обновления сотрудника:', response.error);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Ошибка при отправке запроса:', error);
                        alert('Ошибка при отправке запроса: ' + error);
                    }
                });

                closePopup();
            });

            popup.show();
        }




        function closePopup() {
            document.getElementById('popup').style.display = 'none';
        }

        // JavaScript функция для удаления сотрудника
        function deleteEmployee(employeeId) {
            if (confirm('Вы уверены, что хотите удалить сотрудника?')) {
                // Отправьте запрос на удаление сотрудника (например, используя AJAX)
                // Или можете просто перенаправить пользователя на страницу удаления
                fetch('delete_employee.php?employee_id_to_delete=' + employeeId)
                    .then(response => {
                        if (response.ok) {
                            // Обновление содержимого таблицы после успешного удаления
                            location.reload();
                        } else {
                            console.error('Ошибка удаления сотрудника');
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка удаления сотрудника:', error);
                    });
            }
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

</body>

</html>