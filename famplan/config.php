<?php
session_start();


// Базовые настройки
date_default_timezone_set('Europe/Moscow');

// Простая инициализация сессии
if (!isset($_SESSION['initialized'])) {
    $_SESSION['user_id'] = null;
    $_SESSION['username'] = null;
    $_SESSION['role'] = null;
    $_SESSION['family_id'] = null;
    $_SESSION['join_code'] = null;
    $_SESSION['events'] = [];
    $_SESSION['initialized'] = true;
}

// Функция для безопасного вывода
function safe_output($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>