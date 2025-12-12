<?php
session_start();

// Простейший вход для отладки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $demo_users = [
        'parent@example.com' => ['id' => 1, 'username' => 'Родитель', 'role' => 'parent', 'family_id' => 1],
        'child1@example.com' => ['id' => 2, 'username' => 'Анна', 'role' => 'child', 'family_id' => 1],
        'child2@example.com' => ['id' => 3, 'username' => 'Максим', 'role' => 'child', 'family_id' => 1]
    ];
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Простая проверка - любой пароль "123456" работает
    if ($password === '123456' && isset($demo_users[$email])) {
        $_SESSION['user_id'] = $demo_users[$email]['id'];
        $_SESSION['username'] = $demo_users[$email]['username'];
        $_SESSION['role'] = $demo_users[$email]['role'];
        $_SESSION['family_id'] = $demo_users[$email]['family_id'];
        
        // Перенаправляем на главную
        header('Location: index.php');
        exit;
    } else {
        echo "Ошибка входа. Используйте:<br>";
        echo "parent@example.com / 123456<br>";
        echo "child1@example.com / 123456<br>";
        echo "child2@example.com / 123456<br>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Login</title>
</head>
<body>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" value="parent@example.com">
        <input type="password" name="password" placeholder="Пароль" value="123456">
        <button type="submit">Войти</button>
    </form>
</body>
</html>