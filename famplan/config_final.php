<?php
// Простейшая конфигурация без базы данных
session_start();

// Инициализация данных при первом запуске
if (!isset($_SESSION['famplan_data'])) {
    $_SESSION['famplan_data'] = [
        'users' => [],
        'families' => [],
        'events' => [],
        'checklists' => [],
        'messages' => [],
        'last_id' => 0
    ];
}

// Утилитарные функции
function get_next_id() {
    global $_SESSION;
    return ++$_SESSION['famplan_data']['last_id'];
}

function add_user($username, $email, $password, $role = 'child') {
    global $_SESSION;
    
    $id = get_next_id();
    $_SESSION['famplan_data']['users'][$id] = [
        'id' => $id,
        'username' => $username,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'family_id' => null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return $id;
}

function find_user($email) {
    global $_SESSION;
    foreach ($_SESSION['famplan_data']['users'] as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }
    return null;
}

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'register':
                $user_id = add_user(
                    $_POST['username'],
                    $_POST['email'],
                    $_POST['password'],
                    $_POST['role'] ?? 'child'
                );
                
                if ($user_id) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $_POST['username'];
                    $_SESSION['role'] = $_POST['role'] ?? 'child';
                }
                break;
                
            case 'login':
                $user = find_user($_POST['email']);
                if ($user && password_verify($_POST['password'], $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['family_id'] = $user['family_id'];
                } else {
                    $error = "Неверный email или пароль";
                }
                break;
                
            case 'logout':
                session_destroy();
                header("Location: index.php");
                exit;
        }
    }
}

// Получение текущих данных
$current_user = isset($_SESSION['user_id']) ? 
    $_SESSION['famplan_data']['users'][$_SESSION['user_id']] : null;
?>