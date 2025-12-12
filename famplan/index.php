<?php
session_start();

// Функция для безопасного доступа к массивам
function safeArray(&$array, $key, $default = []) {
    if (!isset($array[$key]) || !is_array($array[$key])) {
        $array[$key] = $default;
    }
    return $array[$key];
}

// Функция для безопасного вывода
function safeOutput($string, $default = '') {
    return htmlspecialchars($string ?? $default, ENT_QUOTES, 'UTF-8');
}

// Инициализация данных сессии
function initSessionData() {
    if (!isset($_SESSION['famplan'])) {
        $_SESSION['famplan'] = [];
    }
    
    $data = &$_SESSION['famplan'];
    
    // Основные массивы с демо-данными
    $defaultData = [
        'users' => [
            1 => [
                'id' => 1,
                'username' => 'Родитель',
                'email' => 'parent@example.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'role' => 'parent',
                'family_id' => 1,
                'avatar_color' => '#C9A68E'
            ],
            2 => [
                'id' => 2,
                'username' => 'Анна',
                'email' => 'child1@example.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'role' => 'child',
                'family_id' => 1,
                'avatar_color' => '#A8C3CE'
            ],
            3 => [
                'id' => 3,
                'username' => 'Максим',
                'email' => 'child2@example.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'role' => 'child',
                'family_id' => 1,
                'avatar_color' => '#E0C9B1'
            ]
        ],
        'families' => [
            1 => [
                'id' => 1,
                'name' => 'Наша семья',
                'join_code' => 'FAM' . rand(100, 999),
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ],
        'events' => [],
        'checklists' => [],
        'messages' => [],
        'family_members' => [
            1 => ['family_id' => 1, 'user_id' => 1, 'role' => 'parent'],
            2 => ['family_id' => 1, 'user_id' => 2, 'role' => 'child'],
            3 => ['family_id' => 1, 'user_id' => 3, 'role' => 'child']
        ],
        'memories' => [],
        'next_id' => 100
    ];
    
    // Инициализируем все массивы
    foreach ($defaultData as $key => $value) {
        if (!isset($data[$key]) || empty($data[$key])) {
            $data[$key] = $value;
        }
    }
}

// Инициализируем сессию
initSessionData();

$data = &$_SESSION['famplan'];

// Гарантируем существование всех массивов
$requiredArrays = ['events', 'checklists', 'messages', 'family_members', 'users', 'families', 'memories'];
foreach ($requiredArrays as $arrayName) {
    safeArray($data, $arrayName);
}

// Календарь
if (!isset($_SESSION['calendar_date'])) {
    $_SESSION['calendar_date'] = date('Y-m');
}

// Генерация ID
function getNextId() {
    global $data;
    if (!isset($data['next_id'])) {
        $data['next_id'] = 100;
    }
    $id = $data['next_id'];
    $data['next_id']++;
    return $id;
}

// Обработка POST запросов
$notification = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                $demo_users = [
                    'parent@example.com' => ['id' => 1, 'username' => 'Родитель', 'role' => 'parent', 'family_id' => 1, 'avatar_color' => '#C9A68E'],
                    'child1@example.com' => ['id' => 2, 'username' => 'Анна', 'role' => 'child', 'family_id' => 1, 'avatar_color' => '#A8C3CE'],
                    'child2@example.com' => ['id' => 3, 'username' => 'Максим', 'role' => 'child', 'family_id' => 1, 'avatar_color' => '#E0C9B1']
                ];
                
                if (isset($demo_users[$email]) && $password === '123456') {
                    $user = $demo_users[$email];
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['family_id'] = $user['family_id'];
                    $_SESSION['avatar_color'] = $user['avatar_color'];
                    
                    $notification['message'] = 'Добро пожаловать, ' . $user['username'] . '!';
                    $notification['type'] = 'success';
                } else {
                    $notification['message'] = 'Неверный email или пароль. Используйте демо-данные.';
                    $notification['type'] = 'error';
                }
                break;
                
            case 'add_event':
                if (isset($_SESSION['family_id']) && isset($_SESSION['user_id'])) {
                    $eventId = getNextId();
                    $newEvent = [
                        'id' => $eventId,
                        'family_id' => $_SESSION['family_id'],
                        'title' => safeOutput($_POST['title'] ?? 'Новое событие'),
                        'description' => safeOutput($_POST['description'] ?? ''),
                        'event_date' => $_POST['event_date'] ?? date('Y-m-d'),
                        'event_time' => $_POST['event_time'] ?? '12:00',
                        'created_by' => $_SESSION['user_id'],
                        'color' => $_POST['color'] ?? '#A8C3CE'
                    ];
                    
                    $data['events'][] = $newEvent;
                    $notification['message'] = 'Событие "' . safeOutput($_POST['title']) . '" добавлено!';
                    $notification['type'] = 'success';
                }
                break;
                
            case 'create_checklist':
                if (isset($_SESSION['family_id']) && isset($_SESSION['user_id'])) {
                    $checklistId = getNextId();
                    $newChecklist = [
                        'id' => $checklistId,
                        'family_id' => $_SESSION['family_id'],
                        'title' => safeOutput($_POST['title'] ?? 'Новый чек-лист'),
                        'created_by' => $_SESSION['user_id'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'items' => []
                    ];
                    
                    $data['checklists'][] = $newChecklist;
                    $notification['message'] = 'Чек-лист создан!';
                    $notification['type'] = 'success';
                }
                break;
                
            case 'add_checklist_item':
                $checklistId = intval($_POST['checklist_id'] ?? 0);
                $itemText = safeOutput($_POST['item'] ?? '');
                
                foreach ($data['checklists'] as &$checklist) {
                    if ($checklist['id'] == $checklistId) {
                        $itemId = getNextId();
                        $checklist['items'][] = [
                            'id' => $itemId,
                            'text' => $itemText,
                            'is_checked' => false,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        $notification['message'] = 'Пункт добавлен в чек-лист!';
                        $notification['type'] = 'success';
                        break;
                    }
                }
                break;
                
            case 'toggle_checklist_item':
                $checklistId = intval($_POST['checklist_id'] ?? 0);
                $itemId = intval($_POST['item_id'] ?? 0);
                
                foreach ($data['checklists'] as &$checklist) {
                    if ($checklist['id'] == $checklistId) {
                        foreach ($checklist['items'] as &$item) {
                            if ($item['id'] == $itemId) {
                                $item['is_checked'] = !$item['is_checked'];
                                $notification['message'] = $item['is_checked'] ? 'Задача выполнена! 🎉' : 'Задача возобновлена';
                                $notification['type'] = 'success';
                                break 2;
                            }
                        }
                    }
                }
                break;
                
            case 'delete_checklist':
                $checklistId = intval($_POST['checklist_id'] ?? 0);
                $data['checklists'] = array_filter($data['checklists'], function($checklist) use ($checklistId) {
                    return $checklist['id'] != $checklistId;
                });
                $notification['message'] = 'Чек-лист удален!';
                $notification['type'] = 'success';
                break;
                
            case 'add_memory':
                if (isset($_SESSION['family_id']) && isset($_SESSION['user_id'])) {
                    $memoryId = getNextId();
                    $newMemory = [
                        'id' => $memoryId,
                        'family_id' => $_SESSION['family_id'],
                        'user_id' => $_SESSION['user_id'],
                        'title' => safeOutput($_POST['title'] ?? 'Воспоминание'),
                        'description' => safeOutput($_POST['description'] ?? ''),
                        'date' => $_POST['memory_date'] ?? date('Y-m-d'),
                        'image' => $_POST['image_url'] ?? '',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $data['memories'][] = $newMemory;
                    $notification['message'] = 'Воспоминание добавлено!';
                    $notification['type'] = 'success';
                }
                break;
                
            case 'send_message':
                if (isset($_SESSION['family_id']) && isset($_SESSION['user_id'])) {
                    $messageId = getNextId();
                    $newMessage = [
                        'id' => $messageId,
                        'family_id' => $_SESSION['family_id'],
                        'user_id' => $_SESSION['user_id'],
                        'message' => safeOutput($_POST['message'] ?? ''),
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $data['messages'][] = $newMessage;
                    $notification['message'] = 'Сообщение отправлено!';
                    $notification['type'] = 'success';
                }
                break;
                
            case 'change_calendar_month':
                $direction = intval($_POST['direction'] ?? 0);
                $current = strtotime($_SESSION['calendar_date'] . '-01');
                $_SESSION['calendar_date'] = date('Y-m', strtotime($direction . ' months', $current));
                break;
                
            case 'logout':
                session_destroy();
                header("Location: index.php");
                exit;
        }
        
        // Сохраняем уведомление в сессии
        if ($notification['message']) {
            $_SESSION['notification'] = $notification;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Получаем уведомление из сессии
if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    unset($_SESSION['notification']);
}

// Данные текущего пользователя
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;
$family_id = $_SESSION['family_id'] ?? null;
$avatar_color = $_SESSION['avatar_color'] ?? '#A8C3CE';

// Функция генерации календаря
function generateCalendar($year, $month) {
    global $data;
    
    $firstDay = date('N', strtotime("$year-$month-01"));
    $daysInMonth = date('t', strtotime("$year-$month-01"));
    $today = date('Y-m-d');
    
    $monthNames = [
        1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 
        4 => 'Апрель', 5 => 'Май', 6 => 'Июнь',
        7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь',
        10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
    ];
    
    $days = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
    
    $calendar = '<div class="calendar-grid">';
    
    // Дни недели
    foreach ($days as $day) {
        $calendar .= '<div class="calendar-day-header">' . $day . '</div>';
    }
    
    // Пустые ячейки
    for ($i = 1; $i < $firstDay; $i++) {
        $calendar .= '<div class="calendar-day empty"></div>';
    }
    
    // Дни месяца
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $isToday = ($date == $today);
        
        // Проверяем есть ли воспоминания на эту дату
        $hasMemories = false;
        $memories = [];
        if (isset($data['memories']) && $family_id = $_SESSION['family_id'] ?? null) {
            foreach ($data['memories'] as $memory) {
                if (isset($memory['date']) && $memory['date'] == $date && 
                    isset($memory['family_id']) && $memory['family_id'] == $family_id) {
                    $hasMemories = true;
                    $memories[] = $memory;
                }
            }
        }
        
        $class = 'calendar-day';
        if ($isToday) $class .= ' today';
        if ($hasMemories) $class .= ' has-memories';
        
        $calendar .= '<div class="' . $class . '" data-date="' . $date . '" data-memories=\'' . json_encode($memories) . '\'>';
        $calendar .= '<div class="day-number">' . $day . '</div>';
        
        if ($hasMemories) {
            $calendar .= '<div class="memory-indicator" title="Есть воспоминания">';
            $calendar .= '<i class="fas fa-camera"></i>';
            $calendar .= '</div>';
        }
        
        $calendar .= '</div>';
    }
    
    $calendar .= '</div>';
    return $calendar;
}

// Генерация календаря
list($cal_year, $cal_month) = explode('-', $_SESSION['calendar_date']);
$calendar_html = generateCalendar($cal_year, $cal_month);

// Получение данных для текущей семьи
$family_events = [];
$family_checklists = [];
$family_members = [];
$family_messages = [];
$family_memories = [];

if ($family_id) {
    // События семьи
    foreach (safeArray($data, 'events') as $event) {
        if (isset($event['family_id']) && $event['family_id'] == $family_id) {
            $family_events[] = $event;
        }
    }
    
    // Чек-листы семьи
    foreach (safeArray($data, 'checklists') as $checklist) {
        if (isset($checklist['family_id']) && $checklist['family_id'] == $family_id) {
            $family_checklists[] = $checklist;
        }
    }
    
    // Члены семьи
    foreach (safeArray($data, 'family_members') as $member) {
        if (isset($member['family_id']) && $member['family_id'] == $family_id) {
            $userId = $member['user_id'] ?? null;
            if ($userId && isset($data['users'][$userId])) {
                $user = $data['users'][$userId];
                $family_members[] = [
                    'id' => $userId,
                    'username' => $user['username'] ?? 'Неизвестный',
                    'role' => $user['role'] ?? 'child',
                    'email' => $user['email'] ?? 'нет@email.com',
                    'avatar_color' => $user['avatar_color'] ?? '#A8C3CE'
                ];
            }
        }
    }
    
    // Сообщения
    foreach (safeArray($data, 'messages') as $msg) {
        if (isset($msg['family_id']) && $msg['family_id'] == $family_id) {
            $userId = $msg['user_id'] ?? null;
            if ($userId && isset($data['users'][$userId])) {
                $user = $data['users'][$userId];
                $msg['username'] = $user['username'] ?? 'Неизвестный';
                $msg['user_role'] = $user['role'] ?? 'child';
                $msg['avatar_color'] = $user['avatar_color'] ?? '#A8C3CE';
                $family_messages[] = $msg;
            }
        }
    }
    
    // Воспоминания
    foreach (safeArray($data, 'memories') as $memory) {
        if (isset($memory['family_id']) && $memory['family_id'] == $family_id) {
            $userId = $memory['user_id'] ?? null;
            if ($userId && isset($data['users'][$userId])) {
                $user = $data['users'][$userId];
                $memory['username'] = $user['username'] ?? 'Неизвестный';
                $memory['avatar_color'] = $user['avatar_color'] ?? '#A8C3CE';
                $family_memories[] = $memory;
            }
        }
    }
}

// Сортировка
usort($family_events, function($a, $b) {
    return strtotime($a['event_date'] ?? '') <=> strtotime($b['event_date'] ?? '');
});

usort($family_messages, function($a, $b) {
    return strtotime($a['created_at'] ?? '') <=> strtotime($b['created_at'] ?? '');
});

usort($family_memories, function($a, $b) {
    return strtotime($b['date'] ?? '') <=> strtotime($a['date'] ?? '');
});
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FamPlan • Семейный организатор</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        /* ==================== CSS СТИЛИ ==================== */
        
        /* Темная бежевая цветовая схема */
        :root {
            --primary-beige: white;
            --secondary-beige: #D4C4A8;
            --dark-beige: #A8997E;
            --light-beige: #F5EFE0;
            --text-beige: #5D5342;
            
            --accent-coral: #C9A68E;
            --accent-blue: #7E7C81;
            --accent-peach: #E0C9B1;
            --accent-lavender: #C7B8A6;
            --accent-mint: #B5C7B1;
            
            --text-dark: #3C3529;
            --text-medium: #6B6251;
            --text-light: #8A7F6D;
            
            --shadow: 0 8px 32px rgba(92, 83, 66, 0.15);
            --radius-lg: 24px;
            --radius-md: 16px;
            --radius-sm: 12px;
            
            --success: #28a745;
            --error: #dc3545;
            --info: grey;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, var(--primary-beige) 0%, var(--secondary-beige) 100%);
            color: var(--text-dark);
            min-height: 100vh;
            line-height: 1.6;
        }
        
        /* Анимации */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.02); opacity: 0.9; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        /* Экран авторизации */
        .auth-screen {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-container {
            background: var(--light-beige);
            border-radius: var(--radius-lg);
            padding: 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: var(--shadow);
            animation: fadeIn 0.8s ease;
            border: 2px solid var(--dark-beige);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-main {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .logo-image {
            width: 60px;
            height: 60px;
            background: var(--accent-coral);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            cursor: pointer;
            overflow: hidden;
            border: 3px solid var(--dark-beige);
        }
        
        .logo-main h1 {
            font-family: 'Pacifico', cursive;
            font-size: 36px;
            color: var(--text-dark);
        }
        
        .tagline {
            color: var(--text-medium);
            font-size: 16px;
        }
        
        .auth-form {
            margin-top: 20px;
        }
        
        .auth-form h2 {
            color: var(--text-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .demo-info {
            background: var(--accent-mint);
            padding: 12px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 13px;
            color: var(--text-dark);
            border-left: 4px solid var(--accent-blue);
        }
        
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            z-index: 1;
        }
        
        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid var(--dark-beige);
            border-radius: var(--radius-md);
            font-size: 16px;
            background: var(--primary-beige);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(168, 195, 206, 0.2);
        }
        
        .show-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
        }
        
        .btn-auth {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, var(--accent-coral), var(--accent-peach));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(201, 166, 142, 0.3);
        }
        
        .notification {
            position: relative;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.3s ease;
        }
        
        .notification.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success);
        }
        
        .notification.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--error);
        }
        
        .notification.info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid var(--info);
        }
        
        /* Основной интерфейс */
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Боковая панель */
        .sidebar {
            width: 260px;
            background: var(--light-beige);
            border-right: 2px solid var(--dark-beige);
            display: flex;
            flex-direction: column;
            padding: 25px 0;
        }
        
        .sidebar-header {
            padding: 0 20px 25px;
            border-bottom: 2px solid var(--secondary-beige);
        }
        
        .logo-sidebar {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .logo-sidebar h2 {
            font-family: 'Pacifico', cursive;
            font-size: 24px;
            color: var(--text-dark);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .user-details h3 {
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        .user-role {
            font-size: 12px;
            color: var(--text-medium);
            background: var(--secondary-beige);
            padding: 3px 8px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 25px 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            text-decoration: none;
            color: var(--text-medium);
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            cursor: pointer;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
        }
        
        .nav-item:hover {
            background: var(--secondary-beige);
            color: var(--text-dark);
            border-left-color: var(--accent-coral);
        }
        
        .nav-item.active {
            background: var(--primary-beige);
            color: var(--accent-coral);
            border-left-color: var(--accent-coral);
        }
        
        .nav-item i {
            font-size: 18px;
            width: 20px;
        }
        
        .sidebar-footer {
            padding: 0 20px;
        }
        
        .btn-logout {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, var(--text-light), var(--text-medium));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Основной контент */
        .main-content {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
        }
        
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--secondary-beige);
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header-left h1 {
            font-size: 32px;
            color: var(--text-dark);
            margin-bottom: 5px;
            font-family: 'Pacifico', cursive;
        }
        
        .greeting {
            font-size: 16px;
            color: var(--text-medium);
        }
        
        .date-display {
            background: white;
            padding: 12px 20px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 2px solid var(--dark-beige);
        }
        
        /* Контентные секции */
        .content-sections {
            margin-top: 15px;
        }
        
        .content-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .content-section.active {
            display: block;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-header h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
            color: var(--text-dark);
        }
        
        .btn-add {
            padding: 10px 20px;
            background: linear-gradient(45deg, var(--accent-blue), var(--accent-mint));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(168, 195, 206, 0.3);
        }
        
        /* Календарь */
        .calendar-widget {
            background: var(--light-beige);
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border: 2px solid var(--dark-beige);
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
        }
        
        .calendar-nav {
            background: none;
            border: none;
            color: var(--text-medium);
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .calendar-nav:hover {
            background: var(--secondary-beige);
            color: var(--text-dark);
        }
        
        #currentMonth {
            font-size: 20px;
            margin: 0 20px;
            color: var(--text-dark);
            min-width: 200px;
            text-align: center;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }
        
        .calendar-day-header {
            text-align: center;
            padding: 12px 5px;
            font-weight: 700;
            color: var(--text-medium);
            background: var(--secondary-beige);
            border-radius: 8px;
            font-size: 14px;
        }
        
        .calendar-day {
            aspect-ratio: 1;
            background: var(--primary-beige);
            border-radius: 8px;
            padding: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            border: 2px solid transparent;
        }
        
        .calendar-day:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: var(--accent-blue);
        }
        
        .calendar-day.today {
            background: var(--accent-mint);
            border-color: var(--accent-blue);
            font-weight: bold;
        }
        
        .calendar-day.has-memories {
            background: #FFF9E6;
        }
        
        .day-number {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
        }
        
        .memory-indicator {
            position: absolute;
            bottom: 5px;
            right: 5px;
            font-size: 10px;
            color: var(--accent-coral);
        }
        
        .calendar-day.empty {
            background: transparent;
            cursor: default;
        }
        
        .calendar-day.empty:hover {
            transform: none;
            box-shadow: none;
            border-color: transparent;
        }
        
        /* Чек-листы */
        .checklists-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .checklist-card {
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 2px solid var(--dark-beige);
        }
        
        .checklist-header {
            padding: 20px;
            background: var(--accent-mint);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .checklist-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            color: var(--text-dark);
            flex: 1;
        }
        
        .checklist-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-delete-checklist {
            background: var(--error);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-delete-checklist:hover {
            transform: scale(1.1);
            background: #c82333;
        }
        
        .checklist-items {
            padding: 20px;
            min-height: 100px;
        }
        
        .checklist-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: var(--primary-beige);
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
            transition: all 0.3s ease;
            gap: 12px;
        }
        
        .checklist-item:hover {
            background: var(--secondary-beige);
        }
        
        .checklist-item.checked {
            background: #F0FFF4;
        }
        
        .checklist-item.checked .item-text {
            text-decoration: line-through;
            color: var(--text-light);
        }
        
        .toggle-form {
            margin: 0;
            padding: 0;
            display: inline;
        }
        
        .check-btn {
            background: none;
            border: none;
            color: var(--text-medium);
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.3s ease;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .check-btn:hover {
            background: var(--accent-blue);
            color: white;
        }
        
        .item-text {
            font-size: 15px;
            color: var(--text-dark);
            flex: 1;
        }
        
        .item-status {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            min-width: 100px;
            text-align: center;
        }
        
        .checklist-item:not(.checked) .item-status {
            background: #FFF3CD;
            color: #856404;
        }
        
        .checklist-item.checked .item-status {
            background: #D4EDDA;
            color: #155724;
        }
        
        .add-checklist-item {
            padding: 15px;
            border-top: 2px solid var(--secondary-beige);
        }
        
        .add-checklist-item form {
            display: flex;
            gap: 10px;
        }
        
        .add-checklist-item input[type="text"] {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--dark-beige);
            border-radius: var(--radius-sm);
            font-size: 14px;
            background: var(--primary-beige);
        }
        
        .btn-add-item {
            width: 40px;
            background: var(--accent-blue);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-add-item:hover {
            background: var(--accent-coral);
            transform: scale(1.05);
        }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: var(--radius-lg);
            border: 2px dashed var(--dark-beige);
        }
        
        .empty-state i {
            font-size: 48px;
            color: var(--accent-blue);
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: var(--text-medium);
        }
        
        /* Воспоминания */
        .memories-container {
            margin-top: 20px;
        }
        
        .memories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .memory-card {
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 2px solid var(--dark-beige);
            transition: all 0.3s ease;
        }
        
        .memory-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .memory-image {
            height: 180px;
            overflow: hidden;
        }
        
        .memory-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .memory-card:hover .memory-image img {
            transform: scale(1.05);
        }
        
        .memory-content {
            padding: 20px;
        }
        
        .memory-content h3 {
            font-size: 18px;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        
        .memory-content p {
            color: var(--text-medium);
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .memory-meta {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: var(--text-light);
        }
        
        .memory-date, .memory-author {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Семья */
        .family-members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .family-member-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            box-shadow: var(--shadow);
            border: 2px solid var(--dark-beige);
            transition: all 0.3s ease;
        }
        
        .family-member-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .member-info {
            flex: 1;
        }
        
        .member-info h3 {
            font-size: 18px;
            margin-bottom: 5px;
            color: var(--text-dark);
        }
        
        .member-role, .member-email {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-medium);
            margin-bottom: 5px;
        }
        
        /* Чат */
        .chat-container {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border: 2px solid var(--dark-beige);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 500px;
        }
        
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .empty-chat {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }
        
        .empty-chat i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .empty-chat h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        
        .empty-chat p {
            color: var(--text-medium);
        }
        
        .message {
            max-width: 75%;
            padding: 12px;
            border-radius: var(--radius-md);
            position: relative;
            animation: fadeIn 0.3s ease;
        }
        
        .message.sent {
            align-self: flex-end;
            background: var(--accent-blue);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.received {
            align-self: flex-start;
            background: var(--primary-beige);
            color: var(--text-dark);
            border-bottom-left-radius: 4px;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .message-sender {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .sender-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
            flex-shrink: 0;
        }
        
        .sender-info {
            display: flex;
            flex-direction: column;
        }
        
        .sender-info strong {
            font-size: 13px;
            line-height: 1;
        }
        
        .message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 2px;
        }
        
        .message-content {
            line-height: 1.4;
            word-break: break-word;
        }
        
        .message-input {
            padding: 15px;
            border-top: 2px solid var(--secondary-beige);
            background: var(--light-beige);
        }
        
        .message-input form {
            display: flex;
            gap: 10px;
        }
        
        .message-input input[type="text"] {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--dark-beige);
            border-radius: var(--radius-sm);
            font-size: 14px;
            background: white;
        }
        
        .btn-send {
            width: 45px;
            background: var(--accent-coral);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-send:hover {
            background: var(--accent-peach);
            transform: scale(1.05);
        }
        
        /* ==================== СТИЛИ ДЛЯ ВКЛАДКИ ДАННЫХ ==================== */
        
        /* Блок данных */
        .data-dashboard {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        /* Карточки статистики */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow);
            border: 2px solid var(--dark-beige);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .stat-card.pulse-card {
            animation: pulse 2s infinite;
            background: linear-gradient(135deg, #FF9AA2, #FFD3B6);
            color: white;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            flex-shrink: 0;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-content h3 {
            font-size: 16px;
            margin-bottom: 8px;
            color: var(--text-medium);
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-light);
        }
        
        /* Круг прогресса */
        .progress-circle {
            position: relative;
            width: 70px;
            height: 70px;
            margin: 10px 0;
        }
        
        .circle-progress {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(var(--accent-blue) 0% 85%, var(--secondary-beige) 85% 100%);
        }
        
        .progress-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 18px;
            font-weight: bold;
            color: var(--text-dark);
        }
        
        /* Спарклайн */
        .sparkline-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .sparkline {
            flex: 1;
            height: 40px;
            display: flex;
            align-items: flex-end;
            gap: 2px;
        }
        
        .sparkline-value {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
        }
        
        /* Карточка приглашения */
        .family-invite-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow);
            border: 2px solid var(--dark-beige);
        }
        
        .invite-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .invite-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            color: var(--text-dark);
        }
        
        .invite-content {
            background: var(--light-beige);
            border-radius: var(--radius-md);
            padding: 20px;
        }
        
        .invite-code-display, .invite-link-display {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .invite-code-display code {
            flex: 1;
            background: white;
            padding: 12px;
            border-radius: var(--radius-sm);
            font-family: monospace;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 2px;
            color: var(--accent-coral);
            border: 2px dashed var(--accent-coral);
        }
        
        .invite-link-display input {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--dark-beige);
            border-radius: var(--radius-sm);
            font-size: 14px;
            background: white;
        }
        
        .btn-copy, .btn-copy-small {
            background: var(--accent-blue);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-copy:hover {
            background: var(--accent-coral);
            transform: translateY(-2px);
        }
        
        .btn-copy-small {
            padding: 8px 12px;
            font-size: 14px;
        }
        
        .invite-hint {
            text-align: center;
            color: var(--text-medium);
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .invite-share-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .share-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: var(--radius-sm);
            color: white;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .share-btn.whatsapp { background: #25D366; }
        .share-btn.telegram { background: #0088cc; }
        .share-btn.email { background: var(--accent-coral); }
        
        .share-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .invite-tabs {
            display: flex;
            gap: 10px;
            border-top: 2px solid var(--secondary-beige);
            padding-top: 15px;
        }
        
        .invite-tab {
            flex: 1;
            padding: 12px;
            background: white;
            border: 2px solid var(--dark-beige);
            border-radius: var(--radius-sm);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
            color: var(--text-medium);
            transition: all 0.3s ease;
        }
        
        .invite-tab.active {
            background: var(--accent-blue);
            color: white;
            border-color: var(--accent-blue);
        }
        
        /* Креативные фишки */
        .fun-features {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow);
            border: 2px solid var(--dark-beige);
        }
        
        .fun-features h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: var(--text-dark);
            font-size: 20px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .feature-btn {
            background: var(--light-beige);
            border: 2px solid var(--dark-beige);
            border-radius: var(--radius-md);
            padding: 20px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
        }
        
        .feature-btn:hover {
            background: var(--accent-peach);
            border-color: var(--accent-coral);
            transform: translateY(-5px);
        }
        
        .feature-btn i {
            font-size: 32px;
            color: var(--accent-coral);
        }
        
        .feature-btn span {
            font-weight: 600;
            color: var(--text-dark);
            text-align: center;
        }
        
        /* Таймлайн семьи */
        .family-timeline-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow);
            border: 2px solid var(--dark-beige);
        }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .timeline-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            color: var(--text-dark);
        }
        
        .timeline-content {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-content::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--accent-blue);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding: 15px;
            background: var(--light-beige);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--accent-coral);
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -28px;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--accent-coral);
            border: 3px solid white;
            box-shadow: 0 0 0 3px var(--accent-blue);
        }
        
        .timeline-date {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .timeline-content-text {
            color: var(--text-dark);
        }
        
        /* Модальные окна */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(232, 219, 197, 0.95);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 500px;
            box-shadow: var(--shadow);
            border: 2px solid var(--dark-beige);
            animation: fadeIn 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 2px solid var(--secondary-beige);
            position: sticky;
            top: 0;
            background: white;
            z-index: 1;
        }
        
        .modal-header h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-dark);
            font-size: 20px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-light);
            cursor: pointer;
            transition: color 0.3s ease;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .close-modal:hover {
            background: var(--secondary-beige);
            color: var(--accent-coral);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--dark-beige);
            border-radius: var(--radius-sm);
            font-size: 14px;
            background: var(--primary-beige);
            color: var(--text-dark);
            transition: all 0.3s ease;
            font-family: 'Nunito', sans-serif;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(168, 195, 206, 0.2);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .color-picker {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        
        .color-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--radius-sm);
            transition: all 0.3s ease;
        }
        
        .color-option:hover {
            background: var(--secondary-beige);
        }
        
        .color-option input[type="radio"] {
            display: none;
        }
        
        .color-option input[type="radio"]:checked + .color-dot {
            transform: scale(1.2);
            box-shadow: 0 0 0 3px white, 0 0 0 5px var(--text-dark);
        }
        
        .color-dot {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            transition: all 0.3s ease;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .color-label {
            font-size: 11px;
            color: var(--text-medium);
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, var(--accent-coral), var(--accent-peach));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(201, 166, 142, 0.3);
        }
        
        /* Настройки данных */
        .settings-group {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--secondary-beige);
        }
        
        .settings-group:last-child {
            border-bottom: none;
        }
        
        .settings-group h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: var(--text-dark);
        }
        
        .setting-item {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            flex: 1;
        }
        
        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .range-slider {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .range-slider input[type="range"] {
            flex: 1;
            height: 6px;
            border-radius: 3px;
            background: var(--secondary-beige);
            outline: none;
        }
        
        .range-slider input[type="range"]::-webkit-slider-thumb {
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--accent-blue);
            cursor: pointer;
        }
        
        .theme-options {
            display: flex;
            gap: 15px;
        }
        
        .theme-option {
            flex: 1;
            background: none;
            border: 2px solid var(--dark-beige);
            border-radius: var(--radius-md);
            padding: 15px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .theme-option:hover {
            border-color: var(--accent-coral);
        }
        
        .theme-preview {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--accent-coral);
        }
        
        .theme-preview.warm-theme {
            background: linear-gradient(45deg, #FF9AA2, #FFD3B6);
        }
        
        .theme-preview.cool-theme {
            background: linear-gradient(45deg, #A8D8EA, #C7CEEA);
        }
        
        .theme-preview.vibrant-theme {
            background: linear-gradient(45deg, #FF9AA2, #B5EAD7, #FFD3B6);
        }
        
        /* Окно приглашения */
        .invite-options {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .invite-option {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: var(--light-beige);
            border-radius: var(--radius-md);
            border: 2px solid var(--dark-beige);
            transition: all 0.3s ease;
        }
        
        .invite-option:hover {
            border-color: var(--accent-blue);
            transform: translateX(5px);
        }
        
        .option-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--accent-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            flex-shrink: 0;
        }
        
        .option-content {
            flex: 1;
        }
        
        .option-content h4 {
            margin-bottom: 5px;
            color: var(--text-dark);
        }
        
        .option-content p {
            color: var(--text-medium);
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .option-code, .option-link {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .option-code strong {
            flex: 1;
            background: white;
            padding: 10px;
            border-radius: var(--radius-sm);
            font-family: monospace;
            font-size: 18px;
            text-align: center;
            color: var(--accent-coral);
            border: 2px dashed var(--accent-coral);
        }
        
        .option-link input {
            flex: 1;
            padding: 10px;
            border: 2px solid var(--dark-beige);
            border-radius: var(--radius-sm);
            background: white;
            font-size: 14px;
        }
        
        .qrcode-container {
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        
        .invite-instructions {
            background: var(--light-beige);
            border-radius: var(--radius-md);
            padding: 20px;
            border-left: 4px solid var(--accent-mint);
        }
        
        .invite-instructions h4 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: var(--text-dark);
        }
        
        .invite-instructions ol {
            padding-left: 20px;
            color: var(--text-medium);
        }
        
        .invite-instructions li {
            margin-bottom: 8px;
        }
        
        /* Кнопки действий */
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-secondary {
            padding: 10px 20px;
            background: linear-gradient(45deg, var(--accent-peach), var(--accent-lavender));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(224, 201, 177, 0.3);
        }
        
        .btn-small {
            padding: 8px 16px;
            background: var(--accent-mint);
            color: var(--text-dark);
            border: none;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }
        
        .btn-small:hover {
            background: var(--accent-blue);
            color: white;
        }
        
        /* Уведомления */
        .notification-global {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 10000;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            max-width: 400px;
            animation: slideIn 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .notification-global.success {
            background: rgba(40, 167, 69, 0.95);
            color: white;
        }
        
        .notification-global.error {
            background: rgba(220, 53, 69, 0.95);
            color: white;
        }
        
        .notification-global.info {
            background: rgba(23, 162, 184, 0.95);
            color: white;
        }
        
        /* Адаптивность */
        @media (max-width: 1024px) {
            .app-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                padding: 15px;
            }
            
            .sidebar-nav {
                display: flex;
                overflow-x: auto;
                padding: 15px 0;
                gap: 10px;
            }
            
            .nav-item {
                flex-direction: column;
                padding: 12px;
                min-width: 90px;
                border-left: none;
                border-bottom: 3px solid transparent;
                text-align: center;
            }
            
            .nav-item.active {
                border-left: none;
                border-bottom: 3px solid var(--accent-coral);
            }
            
            .main-content {
                padding: 15px;
            }
            
            .checklists-container {
                grid-template-columns: 1fr;
            }
            
            .memories-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .auth-container {
                padding: 25px 15px;
            }
            
            .logo-main h1 {
                font-size: 28px;
            }
            
            .main-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .calendar-grid {
                gap: 5px;
            }
            
            .calendar-day {
                padding: 5px;
            }
            
            .day-number {
                font-size: 14px;
            }
            
            .family-members-grid {
                grid-template-columns: 1fr;
            }
            
            .chat-container {
                height: 400px;
            }
            
            .message {
                max-width: 85%;
            }
            
            .modal-content {
                margin: 10px;
                max-height: 85vh;
            }
            
            .invite-share-buttons {
                flex-direction: column;
            }
            
            .theme-options {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .calendar-day-header {
                font-size: 12px;
                padding: 8px 2px;
            }
            
            .calendar-day {
                min-height: 45px;
            }
            
            .day-number {
                font-size: 13px;
            }
            
            .checklist-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .item-status {
                align-self: flex-end;
            }
            
            .color-picker {
                justify-content: center;
            }
            
            .color-option {
                flex: 0 0 calc(33.333% - 8px);
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .invite-option {
                flex-direction: column;
                text-align: center;
            }
            
            .invite-tabs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php if (!$user_id): ?>
        <!-- Экран авторизации -->
        <div class="auth-screen">
            <div class="auth-container">
                <div class="auth-header">
                    <div class="logo-main">
                        <div class="logo-image">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h1>FamPlan</h1>
                    </div>
                    <p class="tagline">Организуйте семейную жизнь с любовью</p>
                </div>
                
                <div class="auth-form">
                    <h2><i class="fas fa-sign-in-alt"></i> Вход в систему</h2>
                    <div class="demo-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Демо доступ:</strong><br>
                        • Родитель: parent@example.com / 123456<br>
                        • Ребенок 1: child1@example.com / 123456<br>
                        • Ребенок 2: child2@example.com / 123456
                    </div>
                    
                    <?php if ($notification['message']): ?>
                        <div class="notification <?php echo $notification['type']; ?>">
                            <i class="fas fa-<?php echo $notification['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                            <?php echo $notification['message']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" placeholder="Ваш email" required value="parent@example.com">
                        </div>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" placeholder="Пароль" required value="123456" id="loginPassword">
                            <button type="button" class="show-password" onclick="togglePassword('loginPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <button type="submit" class="btn-auth">
                            <i class="fas fa-sign-in-alt"></i> Войти в семью
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Основной интерфейс -->
        <div class="app-container">
            <!-- Боковая панель -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <div class="logo-sidebar">
                        <div class="logo-image">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h2>FamPlan</h2>
                    </div>
                    <div class="user-info">
                        <div class="user-avatar" style="background: <?php echo $avatar_color; ?>">
                            <?php echo $role === 'parent' ? '👨‍👩‍👧‍👦' : '👶'; ?>
                        </div>
                        <div class="user-details">
                            <h3><?php echo safeOutput($username); ?></h3>
                            <span class="user-role"><?php echo $role === 'parent' ? '👑 Родитель' : '🌟 Ребенок'; ?></span>
                        </div>
                    </div>
                </div>
                
                <nav class="sidebar-nav">
                    <a href="#calendar" class="nav-item active" data-section="calendar">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Календарь</span>
                    </a>
                    <a href="#checklists" class="nav-item" data-section="checklists">
                        <i class="fas fa-tasks"></i>
                        <span>Чек-листы</span>
                    </a>
                    <a href="#memories" class="nav-item" data-section="memories">
                        <i class="fas fa-images"></i>
                        <span>Воспоминания</span>
                    </a>
                    <a href="#family" class="nav-item" data-section="family">
                        <i class="fas fa-users"></i>
                        <span>Семья</span>
                    </a>
                    <a href="#chat" class="nav-item" data-section="chat">
                        <i class="fas fa-comments"></i>
                        <span>Чат</span>
                    </a>
                    <a href="#data" class="nav-item" data-section="data">
                        <i class="fas fa-chart-network"></i>
                        <span>Данные</span>
                    </a>
                </nav>
                
                <div class="sidebar-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn-logout">
                            <i class="fas fa-sign-out-alt"></i> Выйти
                        </button>
                    </form>
                </div>
            </aside>
            
            <!-- Основной контент -->
            <main class="main-content">
                <!-- Хедер -->
                <header class="main-header">
                    <div class="header-left">
                        <h1 id="pageTitle">Календарь</h1>
                        <p class="greeting">Добро пожаловать, <?php echo safeOutput($username); ?>! <?php echo $role === 'parent' ? '👑' : '🌟'; ?></p>
                    </div>
                    <div class="header-right">
                        <div class="date-display">
                            <i class="fas fa-calendar-day"></i>
                            <span id="currentDateTime"><?php echo date('d.m.Y H:i'); ?></span>
                        </div>
                    </div>
                </header>
                
                <!-- Уведомление -->
                <?php if ($notification['message']): ?>
                    <div class="notification-global <?php echo $notification['type']; ?>">
                        <i class="fas fa-<?php echo $notification['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $notification['message']; ?>
                        <button onclick="this.parentElement.remove()">&times;</button>
                    </div>
                <?php endif; ?>
                
                <!-- Контентные секции -->
                <div class="content-sections">
                    <!-- Секция: Календарь -->
                    <section id="calendar" class="content-section active">
                        <div class="section-header">
                            <h2><i class="fas fa-calendar-heart"></i> Семейный календарь</h2>
                            <button class="btn-add" onclick="showModal('addEventModal')">
                                <i class="fas fa-plus-circle"></i> Добавить событие
                            </button>
                        </div>
                        
                        <div class="calendar-widget">
                            <div class="calendar-header">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="change_calendar_month">
                                    <input type="hidden" name="direction" value="-1">
                                    <button type="submit" class="calendar-nav">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                </form>
                                <h3 id="currentMonth">
                                    <?php 
                                    $monthNum = date('n', strtotime($_SESSION['calendar_date'] . '-01'));
                                    $monthNames = [
                                        1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 
                                        4 => 'Апрель', 5 => 'Май', 6 => 'Июнь',
                                        7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь',
                                        10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
                                    ];
                                    echo $monthNames[$monthNum] . ' ' . date('Y', strtotime($_SESSION['calendar_date'] . '-01'));
                                    ?>
                                </h3>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="change_calendar_month">
                                    <input type="hidden" name="direction" value="1">
                                    <button type="submit" class="calendar-nav">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </form>
                            </div>
                            <?php echo $calendar_html; ?>
                        </div>
                    </section>
                    
                    <!-- Секция: Чек-листы -->
                    <section id="checklists" class="content-section">
                        <div class="section-header">
                            <h2><i class="fas fa-clipboard-check"></i> Чек-листы</h2>
                            <button class="btn-add" onclick="showModal('createChecklistModal')">
                                <i class="fas fa-plus-circle"></i> Создать чек-лист
                            </button>
                        </div>
                        
                        <div class="checklists-container">
                            <?php if (!empty($family_checklists)): ?>
                                <?php foreach ($family_checklists as $checklist): ?>
                                    <div class="checklist-card" data-checklist-id="<?php echo $checklist['id']; ?>">
                                        <div class="checklist-header">
                                            <h3>
                                                <i class="fas fa-list"></i>
                                                <?php echo safeOutput($checklist['title']); ?>
                                            </h3>
                                            <div class="checklist-actions">
                                                <form method="POST" class="delete-checklist-form">
                                                    <input type="hidden" name="action" value="delete_checklist">
                                                    <input type="hidden" name="checklist_id" value="<?php echo $checklist['id']; ?>">
                                                    <button type="submit" class="btn-delete-checklist" onclick="return confirm('Удалить этот чек-лист?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <div class="checklist-items">
                                            <?php if (!empty($checklist['items'])): ?>
                                                <?php foreach ($checklist['items'] as $item): ?>
                                                    <div class="checklist-item <?php echo $item['is_checked'] ? 'checked' : ''; ?>">
                                                        <form method="POST" class="toggle-form">
                                                            <input type="hidden" name="action" value="toggle_checklist_item">
                                                            <input type="hidden" name="checklist_id" value="<?php echo $checklist['id']; ?>">
                                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                            <button type="submit" class="check-btn">
                                                                <?php if ($item['is_checked']): ?>
                                                                    <i class="fas fa-check-square"></i>
                                                                <?php else: ?>
                                                                    <i class="far fa-square"></i>
                                                                <?php endif; ?>
                                                            </button>
                                                        </form>
                                                        <span class="item-text"><?php echo safeOutput($item['text']); ?></span>
                                                        <span class="item-status">
                                                            <?php echo $item['is_checked'] ? '<i class="fas fa-check"></i> Готово' : '<i class="fas fa-clock"></i> Нужно сделать'; ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="add-checklist-item">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="add_checklist_item">
                                                <input type="hidden" name="checklist_id" value="<?php echo $checklist['id']; ?>">
                                                <input type="text" name="item" placeholder="Добавить новый пункт..." required>
                                                <button type="submit" class="btn-add-item">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-clipboard-list"></i>
                                    <h3>Чек-листов пока нет</h3>
                                    <p>Создайте свой первый чек-лист!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    
                    <!-- Секция: Воспоминания -->
                    <section id="memories" class="content-section">
                        <div class="section-header">
                            <h2><i class="fas fa-images"></i> Воспоминания</h2>
                            <button class="btn-add" onclick="showModal('addMemoryModal')">
                                <i class="fas fa-plus-circle"></i> Добавить воспоминание
                            </button>
                        </div>
                        
                        <div class="memories-container">
                            <?php if (!empty($family_memories)): ?>
                                <div class="memories-grid">
                                    <?php foreach ($family_memories as $memory): ?>
                                        <div class="memory-card">
                                            <?php if (!empty($memory['image'])): ?>
                                                <div class="memory-image">
                                                    <img src="<?php echo safeOutput($memory['image']); ?>" alt="<?php echo safeOutput($memory['title']); ?>" loading="lazy">
                                                </div>
                                            <?php endif; ?>
                                            <div class="memory-content">
                                                <h3><?php echo safeOutput($memory['title']); ?></h3>
                                                <p><?php echo safeOutput($memory['description']); ?></p>
                                                <div class="memory-meta">
                                                    <span class="memory-date">
                                                        <i class="far fa-calendar"></i>
                                                        <?php echo date('d.m.Y', strtotime($memory['date'])); ?>
                                                    </span>
                                                    <span class="memory-author">
                                                        <i class="fas fa-user"></i>
                                                        <?php echo safeOutput($memory['username']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-images"></i>
                                    <h3>Пока нет воспоминаний</h3>
                                    <p>Добавьте первое семейное воспоминание!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    
                    <!-- Секция: Семья -->
                    <section id="family" class="content-section">
                        <div class="section-header">
                            <h2><i class="fas fa-users"></i> Наша семья</h2>
                        </div>
                        
                        <div class="family-members-grid">
                            <?php foreach ($family_members as $member): ?>
                                <div class="family-member-card">
                                    <div class="member-avatar" style="background: <?php echo $member['avatar_color']; ?>">
                                        <?php echo $member['role'] === 'parent' ? '👑' : '👶'; ?>
                                    </div>
                                    <div class="member-info">
                                        <h3><?php echo safeOutput($member['username']); ?></h3>
                                        <p class="member-role">
                                            <?php if ($member['role'] === 'parent'): ?>
                                                <i class="fas fa-crown"></i> Родитель
                                            <?php else: ?>
                                                <i class="fas fa-child"></i> Ребенок
                                            <?php endif; ?>
                                        </p>
                                        <p class="member-email">
                                            <i class="fas fa-envelope"></i> 
                                            <?php echo safeOutput($member['email']); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    
                    <!-- Секция: Чат -->
                    <section id="chat" class="content-section">
                        <div class="section-header">
                            <h2><i class="fas fa-comments"></i> Семейный чат</h2>
                        </div>
                        
                        <div class="chat-container">
                            <div class="messages-container" id="messagesContainer">
                                <?php if (!empty($family_messages)): ?>
                                    <?php foreach ($family_messages as $msg): ?>
                                        <div class="message <?php echo ($msg['user_id'] == $user_id) ? 'sent' : 'received'; ?>">
                                            <div class="message-header">
                                                <div class="message-sender">
                                                    <div class="sender-avatar" style="background: <?php echo $msg['avatar_color'] ?? '#A8C3CE'; ?>">
                                                        <?php echo $msg['user_role'] === 'parent' ? '👑' : '👶'; ?>
                                                    </div>
                                                    <div class="sender-info">
                                                        <strong><?php echo safeOutput($msg['username']); ?></strong>
                                                        <span class="message-time">
                                                            <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="message-content">
                                                <?php echo safeOutput($msg['message']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-chat">
                                        <i class="fas fa-comment-slash"></i>
                                        <h3>Нет сообщений</h3>
                                        <p>Начните общение с семьей!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="message-input">
                                <form method="POST">
                                    <input type="hidden" name="action" value="send_message">
                                    <input type="text" name="message" placeholder="Напишите сообщение для семьи..." required>
                                    <button type="submit" class="btn-send">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Секция: Данные -->
                    <section id="data" class="content-section">
                        <div class="section-header">
                            <h2><i class="fas fa-chart-network"></i> Данные семьи</h2>
                            <div class="header-actions">
                                <button class="btn-secondary" onclick="showFamilyInviteModal()">
                                    <i class="fas fa-user-plus"></i> Пригласить
                                </button>
                                <button class="btn-add" onclick="showStatsSettings()">
                                    <i class="fas fa-cog"></i> Настройки
                                </button>
                            </div>
                        </div>
                        
                        <div class="data-dashboard">
                            <!-- Карточки статистики -->
                            <div class="stats-grid">
                                <div class="stat-card pulse-card">
                                    <div class="stat-icon" style="background: var(--accent-coral);">
                                        <i class="fas fa-heartbeat"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3>Пульс семьи</h3>
                                        <div class="stat-value" id="familyPulse">85</div>
                                        <div class="stat-label">единиц счастья</div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon" style="background: var(--accent-blue);">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3>Настроение</h3>
                                        <div class="progress-circle">
                                            <div class="circle-progress" id="moodFill"></div>
                                            <span class="progress-value" id="moodValue">85%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon" style="background: var(--accent-peach);">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3>Достижения</h3>
                                        <div class="stat-value" id="achievementsCount">24</div>
                                        <div class="stat-label">за месяц</div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon" style="background: var(--accent-lavender);">
                                        <i class="fas fa-bolt"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3>Продуктивность</h3>
                                        <div class="sparkline-container">
                                            <div class="sparkline" id="productivitySparkline"></div>
                                            <span class="sparkline-value" id="productivityScore">92%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Приглашение в семью -->
                            <div class="family-invite-card">
                                <div class="invite-header">
                                    <h3><i class="fas fa-user-friends"></i> Пригласить в семью</h3>
                                    <button class="btn-small" onclick="showFamilyInviteModal()">
                                        <i class="fas fa-link"></i> Создать приглашение
                                    </button>
                                </div>
                                <div class="invite-content">
                                    <div class="invite-method active" id="inviteMethodCode">
                                        <div class="invite-code-display">
                                            <code id="familyJoinCode"><?php echo isset($_SESSION['join_code']) ? $_SESSION['join_code'] : 'FAM' . rand(1000, 9999); ?></code>
                                            <button class="btn-copy" onclick="copyJoinCode()">
                                                <i class="far fa-copy"></i> Копировать
                                            </button>
                                        </div>
                                        <p class="invite-hint">Поделитесь этим кодом с членами семьи</p>
                                    </div>
                                    
                                    <div class="invite-method" id="inviteMethodLink" style="display: none;">
                                        <div class="invite-link-display">
                                            <input type="text" id="familyInviteLink" readonly 
                                                   value="<?php echo 'https://famplan.com/join/' . (isset($_SESSION['join_code']) ? $_SESSION['join_code'] : 'FAM' . rand(1000, 9999)); ?>">
                                            <button class="btn-copy" onclick="copyInviteLink()">
                                                <i class="far fa-copy"></i> Копировать
                                            </button>
                                        </div>
                                        <div class="invite-share-buttons">
                                            <button class="share-btn whatsapp" onclick="shareViaWhatsApp()">
                                                <i class="fab fa-whatsapp"></i> WhatsApp
                                            </button>
                                            <button class="share-btn telegram" onclick="shareViaTelegram()">
                                                <i class="fab fa-telegram"></i> Telegram
                                            </button>
                                            <button class="share-btn email" onclick="shareViaEmail()">
                                                <i class="far fa-envelope"></i> Email
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="invite-tabs">
                                        <button class="invite-tab active" onclick="switchInviteMethod('code')">
                                            <i class="fas fa-key"></i> Код
                                        </button>
                                        <button class="invite-tab" onclick="switchInviteMethod('link')">
                                            <i class="fas fa-link"></i> Ссылка
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Креативные фишки -->
                            <div class="fun-features">
                                <h3><i class="fas fa-magic"></i> Креативные фишки</h3>
                                <div class="features-grid">
                                    <button class="feature-btn" onclick="generateFamilyQuote()">
                                        <i class="fas fa-quote-right"></i>
                                        <span>Цитата дня</span>
                                    </button>
                                    
                                    <button class="feature-btn" onclick="showMemoryOfTheDay()">
                                        <i class="fas fa-history"></i>
                                        <span>Воспоминание дня</span>
                                    </button>
                                    
                                    <button class="feature-btn" onclick="showFamilyChallenge()">
                                        <i class="fas fa-medal"></i>
                                        <span>Семейный челлендж</span>
                                    </button>
                                    
                                    <button class="feature-btn" onclick="generateFamilyRecipe()">
                                        <i class="fas fa-utensils"></i>
                                        <span>Семейный рецепт</span>
                                    </button>
                                    
                                    <button class="feature-btn" onclick="showCompatibilityTest()">
                                        <i class="fas fa-heart"></i>
                                        <span>Тест совместимости</span>
                                    </button>
                                    
                                    <button class="feature-btn" onclick="showFuturePrediction()">
                                        <i class="fas fa-crystal-ball"></i>
                                        <span>Прогноз на неделю</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Таймлайн семьи -->
                            <div class="family-timeline-card">
                                <div class="timeline-header">
                                    <h3><i class="fas fa-stream"></i> Хроника семьи</h3>
                                    <button class="btn-small" onclick="exportFamilyData()">
                                        <i class="fas fa-download"></i> Экспорт
                                    </button>
                                </div>
                                <div class="timeline-content" id="familyTimeline">
                                    <!-- Заполнится через JavaScript -->
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
        
        <!-- Модальное окно добавления события -->
        <div id="addEventModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-calendar-plus"></i> Новое событие</h2>
                    <button class="close-modal" onclick="closeModal('addEventModal')">&times;</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_event">
                    <div class="modal-body">
                        <div class="form-group">
                            <label><i class="fas fa-heading"></i> Название события</label>
                            <input type="text" name="title" placeholder="Например: День рождения мамы" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Описание</label>
                            <textarea name="description" placeholder="Детали события..." rows="3"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-day"></i> Дата</label>
                                <input type="date" name="event_date" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Время</label>
                                <input type="time" name="event_time" value="18:00">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-palette"></i> Цвет события</label>
                            <div class="color-picker">
                                <label class="color-option">
                                    <input type="radio" name="color" value="#C9A68E" checked>
                                    <span class="color-dot" style="background: #C9A68E;"></span>
                                    <span class="color-label">Бежевый</span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" name="color" value="#A8C3CE">
                                    <span class="color-dot" style="background: #A8C3CE;"></span>
                                    <span class="color-label">Голубой</span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" name="color" value="#E0C9B1">
                                    <span class="color-dot" style="background: #E0C9B1;"></span>
                                    <span class="color-label">Персиковый</span>
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-plus-circle"></i> Добавить событие
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Модальное окно создания чек-листа -->
        <div id="createChecklistModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-list"></i> Создать чек-лист</h2>
                    <button class="close-modal" onclick="closeModal('createChecklistModal')">&times;</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_checklist">
                    <div class="modal-body">
                        <div class="form-group">
                            <label><i class="fas fa-heading"></i> Название чек-листа</label>
                            <input type="text" name="title" placeholder="Например: Подготовка к празднику" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Описание (необязательно)</label>
                            <textarea name="description" placeholder="Описание чек-листа..." rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-plus-circle"></i> Создать чек-лист
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Модальное окно добавления воспоминания -->
        <div id="addMemoryModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-images"></i> Добавить воспоминание</h2>
                    <button class="close-modal" onclick="closeModal('addMemoryModal')">&times;</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_memory">
                    <div class="modal-body">
                        <div class="form-group">
                            <label><i class="fas fa-heading"></i> Название воспоминания</label>
                            <input type="text" name="title" placeholder="Например: Наш семейный пикник" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Описание</label>
                            <textarea name="description" placeholder="Опишите это воспоминание..." rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <label><i class="far fa-calendar"></i> Дата воспоминания</label>
                            <input type="date" name="memory_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-image"></i> Ссылка на фотографию (URL, необязательно)</label>
                            <input type="url" name="image_url" placeholder="https://example.com/photo.jpg">
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Сохранить воспоминание
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Модальное окно просмотра воспоминаний по дате -->
        <div id="dateMemoriesModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-camera"></i> Воспоминания за <span id="memoriesDate"></span></h2>
                    <button class="close-modal" onclick="closeModal('dateMemoriesModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="dateMemoriesContainer" class="memories-list">
                        <!-- Воспоминания будут загружены через JavaScript -->
                    </div>
                    <div class="modal-footer">
                        <button class="btn-add" onclick="showModal('addMemoryModal')">
                            <i class="fas fa-plus"></i> Добавить воспоминание на эту дату
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Модальное окно приглашения -->
        <div id="familyInviteModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-user-plus"></i> Пригласить в семью</h2>
                    <button class="close-modal" onclick="closeModal('familyInviteModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="invite-options">
                        <div class="invite-option">
                            <div class="option-icon">
                                <i class="fas fa-key"></i>
                            </div>
                            <div class="option-content">
                                <h4>Через код</h4>
                                <p>Поделитесь кодом с членами семьи</p>
                                <div class="option-code">
                                    <strong id="modalJoinCode">FAM<?php echo rand(1000, 9999); ?></strong>
                                    <button class="btn-copy-small" onclick="copyCodeFromModal()">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="invite-option">
                            <div class="option-icon">
                                <i class="fas fa-link"></i>
                            </div>
                            <div class="option-content">
                                <h4>Через ссылку</h4>
                                <p>Отправьте ссылку для быстрого присоединения</p>
                                <div class="option-link">
                                    <input type="text" id="modalInviteLink" readonly 
                                           value="<?php echo 'https://famplan.com/join/FAM' . rand(1000, 9999); ?>">
                                    <button class="btn-copy-small" onclick="copyLinkFromModal()">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="invite-option">
                            <div class="option-icon">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="option-content">
                                <h4>QR-код</h4>
                                <p>Отсканируйте для присоединения</p>
                                <div class="qrcode-container">
                                    <canvas id="qrCodeCanvas"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="invite-instructions">
                        <h4><i class="fas fa-info-circle"></i> Как это работает:</h4>
                        <ol>
                            <li>Поделитесь кодом или ссылкой с членами семьи</li>
                            <li>Они вводят код на экране приветствия</li>
                            <li>Или переходят по ссылке для автоматического присоединения</li>
                            <li>После подтверждения родителя, они становятся частью семьи!</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Модальное окно настроек статистики -->
        <div id="statsSettingsModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-chart-bar"></i> Настройки данных</h2>
                    <button class="close-modal" onclick="closeModal('statsSettingsModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="settings-group">
                        <h3><i class="fas fa-eye"></i> Отображение</h3>
                        <div class="setting-item">
                            <label class="checkbox-label">
                                <input type="checkbox" id="showActivityChart" checked>
                                <span>Показывать график активности</span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <label class="checkbox-label">
                                <input type="checkbox" id="showBalanceWheel" checked>
                                <span>Колесо баланса семьи</span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <label class="checkbox-label">
                                <input type="checkbox" id="showTimeline" checked>
                                <span>Хроника семьи</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="settings-group">
                        <h3><i class="fas fa-bell"></i> Уведомления</h3>
                        <div class="setting-item">
                            <label class="checkbox-label">
                                <input type="checkbox" id="dailyStats" checked>
                                <span>Ежедневная статистика</span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <label class="checkbox-label">
                                <input type="checkbox" id="weeklyReport" checked>
                                <span>Еженедельный отчет</span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <label class="checkbox-label">
                                <input type="checkbox" id="achievementAlerts" checked>
                                <span>Оповещения о достижениях</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="settings-group">
                        <h3><i class="fas fa-sync"></i> Обновление</h3>
                        <div class="setting-item">
                            <label>Автообновление данных:</label>
                            <div class="range-slider">
                                <input type="range" id="updateInterval" min="5" max="60" value="30">
                                <span id="intervalValue">30</span> минут
                            </div>
                        </div>
                    </div>
                    
                    <div class="settings-group">
                        <h3><i class="fas fa-palette"></i> Внешний вид</h3>
                        <div class="theme-options">
                            <button class="theme-option" onclick="changeDataTheme('warm')">
                                <div class="theme-preview warm-theme"></div>
                                <span>Теплая</span>
                            </button>
                            <button class="theme-option" onclick="changeDataTheme('cool')">
                                <div class="theme-preview cool-theme"></div>
                                <span>Холодная</span>
                            </button>
                            <button class="theme-option" onclick="changeDataTheme('vibrant')">
                                <div class="theme-preview vibrant-theme"></div>
                                <span>Яркая</span>
                            </button>
                        </div>
                    </div>
                    
                    <button class="btn-submit" onclick="saveStatsSettings()">
                        <i class="fas fa-save"></i> Сохранить настройки
                    </button>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
    
    <script>
        // ==================== JAVASCRIPT ====================
        
        // Основные функции приложения
        document.addEventListener('DOMContentLoaded', function() {
            console.log('FamPlan initialized');
            
            initNavigation();
            initCalendar();
            updateCurrentTime();
            setInterval(updateCurrentTime, 60000);
            
            // Автоскрытие уведомлений
            const notifications = document.querySelectorAll('.notification-global');
            notifications.forEach(notification => {
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.style.opacity = '0';
                        setTimeout(() => notification.remove(), 300);
                    }
                }, 5000);
            });
            
            // Инициализация смены логотипа
            initLogoUpload();
            
            // Инициализация настроек
            loadStatsSettings();
        });
        
        function initNavigation() {
            const navItems = document.querySelectorAll('.nav-item[data-section]');
            const sections = document.querySelectorAll('.content-section');
            
            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const sectionId = this.getAttribute('data-section');
                    
                    // Обновляем активные элементы
                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Показываем нужную секцию
                    sections.forEach(section => {
                        section.classList.remove('active');
                        if (section.id === sectionId) {
                            section.classList.add('active');
                            document.getElementById('pageTitle').textContent = 
                                this.querySelector('span').textContent;
                            
                            if (sectionId === 'chat') {
                                scrollChatToBottom();
                            } else if (sectionId === 'data') {
                                initDataSection();
                            }
                        }
                    });
                });
            });
        }
        
        function initCalendar() {
            const calendarDays = document.querySelectorAll('.calendar-day:not(.empty)');
            calendarDays.forEach(day => {
                // Визуальные эффекты при наведении
                day.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('today')) {
                        this.style.transform = 'translateY(-2px) scale(1.05)';
                        this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                    }
                });
                
                day.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('today')) {
                        this.style.transform = '';
                        this.style.boxShadow = '';
                    }
                });
                
                // Обработка клика по дню
                day.addEventListener('click', function() {
                    const date = this.getAttribute('data-date');
                    const memories = JSON.parse(this.getAttribute('data-memories') || '[]');
                    
                    if (date) {
                        const dateObj = new Date(date);
                        const formattedDate = dateObj.toLocaleDateString('ru-RU', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        
                        // Показываем кнопку просмотра воспоминаний
                        showDateMemoriesButton(this, date, memories, formattedDate);
                    }
                });
            });
        }
        
        function showDateMemoriesButton(dayElement, date, memories, formattedDate) {
            // Удаляем старую кнопку, если есть
            const oldButton = dayElement.querySelector('.view-memories-btn');
            if (oldButton) oldButton.remove();
            
            // Создаем кнопку
            const button = document.createElement('button');
            button.className = 'view-memories-btn';
            button.innerHTML = '<i class="fas fa-camera"></i> Воспоминания';
            button.style.cssText = `
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: var(--accent-coral);
                color: white;
                border: none;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                cursor: pointer;
                z-index: 10;
                display: flex;
                align-items: center;
                gap: 5px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                animation: fadeIn 0.3s ease;
            `;
            
            button.onclick = function(e) {
                e.stopPropagation();
                showDateMemoriesModal(date, memories, formattedDate);
            };
            
            dayElement.appendChild(button);
            
            // Автоматически скрываем кнопку через 3 секунды
            setTimeout(() => {
                if (button.parentNode) {
                    button.style.opacity = '0';
                    setTimeout(() => button.remove(), 300);
                }
            }, 3000);
        }
        
        function showDateMemoriesModal(date, memories, formattedDate) {
            document.getElementById('memoriesDate').textContent = formattedDate;
            const container = document.getElementById('dateMemoriesContainer');
            
            if (memories.length > 0) {
                let html = '<div class="memories-grid-modal">';
                memories.forEach(memory => {
                    html += `
                        <div class="memory-card-modal">
                            ${memory.image ? `<img src="${memory.image}" alt="${memory.title}" class="memory-image-modal">` : ''}
                            <div class="memory-content-modal">
                                <h4>${memory.title}</h4>
                                <p>${memory.description}</p>
                                <div class="memory-meta-modal">
                                    <span><i class="fas fa-user"></i> ${memory.username || 'Неизвестно'}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="empty-memories">
                        <i class="fas fa-images" style="font-size: 48px; color: var(--text-light); margin-bottom: 20px;"></i>
                        <h3>Нет воспоминаний на эту дату</h3>
                        <p>Добавьте первое воспоминание!</p>
                    </div>
                `;
            }
            
            showModal('dateMemoriesModal');
        }
        
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.style.opacity = '1';
                }, 10);
            }
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            }
        }
        
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const eyeIcon = document.querySelector(`#${inputId} + .show-password i`);
            
            if (input && eyeIcon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    eyeIcon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    eyeIcon.className = 'fas fa-eye';
                }
            }
        }
        
        function scrollChatToBottom() {
            const container = document.querySelector('.messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }
        
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('ru-RU', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            const dateString = now.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
            
            const timeElement = document.getElementById('currentDateTime');
            if (timeElement) {
                timeElement.textContent = `${dateString}, ${timeString}`;
            }
        }
        
        function initLogoUpload() {
            const logoImage = document.querySelector('.logo-image');
            if (logoImage) {
                logoImage.addEventListener('click', function() {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    input.onchange = function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                logoImage.innerHTML = `<img src="${e.target.result}" alt="Логотип FamPlan" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                                localStorage.setItem('famplan_logo', e.target.result);
                            };
                            reader.readAsDataURL(file);
                        }
                    };
                    input.click();
                });
                
                // Загружаем сохраненный логотип
                const savedLogo = localStorage.getItem('famplan_logo');
                if (savedLogo) {
                    logoImage.innerHTML = `<img src="${savedLogo}" alt="Логотип FamPlan" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                }
            }
        }
        
        // ==================== ДАННЫЕ И СТАТИСТИКА ====================
        
        function initDataSection() {
            // Обновление данных
            updateFamilyStats();
            
            // Загрузка таймлайна
            loadFamilyTimeline();
            
            // Инициализация креативных фишек
            initFunFeatures();
            
            // Автообновление
            startDataAutoUpdate();
        }
        
        function updateFamilyStats() {
            // Обновляем пульс (случайное значение 60-100)
            const pulse = Math.floor(Math.random() * 40) + 60;
            document.getElementById('familyPulse').textContent = pulse;
            
            // Обновляем настроение (случайное значение 70-95)
            const mood = Math.floor(Math.random() * 25) + 70;
            document.getElementById('moodFill').style.width = `${mood}%`;
            document.getElementById('moodValue').textContent = `${mood}%`;
            
            // Обновляем достижения
            const achievements = Math.floor(Math.random() * 10) + 15;
            document.getElementById('achievementsCount').textContent = achievements;
            
            // Обновляем продуктивность
            const productivity = Math.floor(Math.random() * 10) + 90;
            document.getElementById('productivityScore').textContent = `${productivity}%`;
            
            // Обновляем sparkline
            drawProductivitySparkline();
        }
        
        function drawProductivitySparkline() {
            const container = document.getElementById('productivitySparkline');
            if (!container) return;
            
            // Генерируем случайные данные
            const data = Array.from({length: 10}, () => Math.floor(Math.random() * 100));
            
            // Создаем простой sparkline с помощью div
            container.innerHTML = '';
            const max = Math.max(...data);
            
            data.forEach(value => {
                const bar = document.createElement('div');
                bar.style.height = `${(value / max) * 100}%`;
                bar.style.width = '8px';
                bar.style.backgroundColor = 'var(--accent-blue)';
                bar.style.margin = '0 2px';
                bar.style.borderRadius = '2px';
                container.appendChild(bar);
            });
        }
        
        function loadFamilyTimeline() {
            const timeline = document.getElementById('familyTimeline');
            if (!timeline) return;
            
            const events = [
                { date: 'Сегодня', text: 'Завершили все задачи в чек-листе' },
                { date: 'Вчера', text: 'Добавили новое воспоминание' },
                { date: '2 дня назад', text: 'Сходили всей семьей в кино' },
                { date: 'Неделю назад', text: 'Установили рекорд продуктивности' },
                { date: 'Месяц назад', text: 'Присоединился новый член семьи' }
            ];
            
            timeline.innerHTML = events.map(event => `
                <div class="timeline-item">
                    <div class="timeline-date">${event.date}</div>
                    <div class="timeline-content-text">${event.text}</div>
                </div>
            `).join('');
        }
        
        function initFunFeatures() {
            // Добавляем обработчики для креативных фишек
            console.log('Инициализация креативных фишек');
        }
        
        // ==================== ПРИГЛАШЕНИЯ ====================
        
        function showFamilyInviteModal() {
            // Генерируем новый код если его нет
            if (!document.getElementById('modalJoinCode').textContent) {
                const code = 'FAM' + Math.floor(1000 + Math.random() * 9000);
                document.getElementById('modalJoinCode').textContent = code;
                document.getElementById('modalInviteLink').value = `https://famplan.com/join/${code}`;
                
                // Генерируем QR-код
                generateQRCode(code);
            }
            
            showModal('familyInviteModal');
        }
        
        function switchInviteMethod(method) {
            const codeTab = document.getElementById('inviteMethodCode');
            const linkTab = document.getElementById('inviteMethodLink');
            const tabs = document.querySelectorAll('.invite-tab');
            
            if (method === 'code') {
                codeTab.style.display = 'block';
                linkTab.style.display = 'none';
                tabs[0].classList.add('active');
                tabs[1].classList.remove('active');
            } else {
                codeTab.style.display = 'none';
                linkTab.style.display = 'block';
                tabs[0].classList.remove('active');
                tabs[1].classList.add('active');
            }
        }
        
        function copyJoinCode() {
            const code = document.getElementById('familyJoinCode').textContent;
            navigator.clipboard.writeText(code).then(() => {
                showNotification('Код скопирован! 📋', 'success');
            });
        }
        
        function copyInviteLink() {
            const link = document.getElementById('familyInviteLink');
            link.select();
            navigator.clipboard.writeText(link.value).then(() => {
                showNotification('Ссылка скопирована! 🔗', 'success');
            });
        }
        
        function copyCodeFromModal() {
            const code = document.getElementById('modalJoinCode').textContent;
            navigator.clipboard.writeText(code).then(() => {
                showNotification('Код скопирован! 📋', 'success');
            });
        }
        
        function copyLinkFromModal() {
            const link = document.getElementById('modalInviteLink');
            link.select();
            navigator.clipboard.writeText(link.value).then(() => {
                showNotification('Ссылка скопирована! 🔗', 'success');
            });
        }
        
        function generateQRCode(code) {
            const canvas = document.getElementById('qrCodeCanvas');
            if (!canvas || !window.QRCode) return;
            
            // Устанавливаем размеры канваса
            canvas.width = 150;
            canvas.height = 150;
            
            // Генерируем QR-код
            QRCode.toCanvas(canvas, `FAMPLAN_JOIN:${code}`, {
                width: 150,
                margin: 2,
                color: {
                    dark: '#3C3529',
                    light: '#F5EFE0'
                }
            }, function(error) {
                if (error) console.error(error);
            });
        }
        
        function shareViaWhatsApp() {
            const link = document.getElementById('familyInviteLink').value;
            const text = `Присоединяйся к нашей семье в FamPlan! 🏡\nКод: ${document.getElementById('familyJoinCode').textContent}\n${link}`;
            window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
        }
        
        function shareViaTelegram() {
            const link = document.getElementById('familyInviteLink').value;
            const text = `Присоединяйся к нашей семье в FamPlan! 🏡\nКод: ${document.getElementById('familyJoinCode').textContent}\n${link}`;
            window.open(`https://t.me/share/url?url=${encodeURIComponent(link)}&text=${encodeURIComponent(text)}`, '_blank');
        }
        
        function shareViaEmail() {
            const link = document.getElementById('familyInviteLink').value;
            const code = document.getElementById('familyJoinCode').textContent;
            const subject = 'Приглашение в семью FamPlan 🏡';
            const body = `Привет!\n\nПрисоединяйся к нашей семье в FamPlan!\n\nКод для присоединения: ${code}\nИли перейди по ссылке: ${link}\n\nС нетерпением ждем тебя! ❤️`;
            window.location.href = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        }
        
        // ==================== КРЕАТИВНЫЕ ФИШКИ ====================
        
        function generateFamilyQuote() {
            const quotes = [
                "Семья – это команда. Вместе мы можем всё! 💪",
                "Лучшее наследство детям – счастливые воспоминания. ✨",
                "Дом там, где тебя любят и ждут. ❤️",
                "Семейное счастье – это не пункт назначения, а путешествие. 🚀",
                "Вместе мы – сила, любовь и поддержка. 🌟"
            ];
            
            const randomQuote = quotes[Math.floor(Math.random() * quotes.length)];
            showNotification(randomQuote, 'info');
        }
        
        function showMemoryOfTheDay() {
            const memories = [
                "Помните наш первый совместный поход? Вот та старая фотография у костра! 🔥",
                "Как смеялись, когда готовили тот невероятный торт на день рождения! 🎂",
                "Тот дождливый день, когда играли в настолки целый день – было так здорово! 🎲"
            ];
            
            const randomMemory = memories[Math.floor(Math.random() * memories.length)];
            showNotification(`🎞️ Воспоминание дня: ${randomMemory}`, 'info', 5000);
        }
        
        function showFamilyChallenge() {
            const challenges = [
                "СЕГОДНЯШНИЙ ЧЕЛЛЕНД: Устройте семейный ужин без гаджетов! 📵",
                "ЧЕЛЛЕНД: Сделайте друг другу комплименты за завтраком! 💬",
                "ЧЕЛЛЕНД: Вместе приготовьте новое блюдо! 👨‍🍳",
                "ЧЕЛЛЕНД: Прогуляйтесь вместе после ужина! 🚶‍♂️🚶‍♀️"
            ];
            
            const randomChallenge = challenges[Math.floor(Math.random() * challenges.length)];
            showNotification(`🏆 ${randomChallenge}`, 'success', 6000);
        }
        
        function generateFamilyRecipe() {
            const recipes = [
                "Семейная пицца 🍕",
                "Домашние пельмени 🥟", 
                "Шоколадные маффины 🧁",
                "Суп-пюре из тыквы 🎃"
            ];
            
            const recipe = recipes[Math.floor(Math.random() * recipes.length)];
            showNotification(`🍽️ Рецепт недели: ${recipe}`, 'info', 5000);
        }
        
        function showCompatibilityTest() {
            const compatibility = Math.floor(Math.random() * 40) + 60; // 60-100%
            showNotification(`❤️ Тест совместимости: ${compatibility}%! ${compatibility > 80 ? 'Идеально! 💖' : 'Хорошо! 👍'}`, 'success');
        }
        
        function showFuturePrediction() {
            const predictions = [
                "На этой неделе вас ждет приятный сюрприз! 🎁",
                "Выходные будут полны веселья и смеха! 😄",
                "Кто-то из семьи достигнет успеха в учебе! 📚",
                "Вас ждет вкусный семейный ужин! 🍕",
                "Получите неожиданный подарок! 🎉"
            ];
            
            const prediction = predictions[Math.floor(Math.random() * predictions.length)];
            showNotification(`🔮 Прогноз на неделю: ${prediction}`, 'info');
        }
        
        // ==================== НАСТРОЙКИ ДАННЫХ ====================
        
        function showStatsSettings() {
            showModal('statsSettingsModal');
        }
        
        function changeDataTheme(theme) {
            const root = document.documentElement;
            
            switch(theme) {
                case 'warm':
                    root.style.setProperty('--accent-coral', '#FF9AA2');
                    root.style.setProperty('--accent-blue', '#FFD3B6');
                    root.style.setProperty('--accent-peach', '#FF9AA2');
                    break;
                case 'cool':
                    root.style.setProperty('--accent-coral', '#A8D8EA');
                    root.style.setProperty('--accent-blue', '#C7CEEA');
                    root.style.setProperty('--accent-peach', '#A8D8EA');
                    break;
                case 'vibrant':
                    root.style.setProperty('--accent-coral', '#FF9AA2');
                    root.style.setProperty('--accent-blue', '#B5EAD7');
                    root.style.setProperty('--accent-peach', '#FFD3B6');
                    break;
            }
            
            showNotification('Тема изменена!', 'success');
        }
        
        function saveStatsSettings() {
            const settings = {
                showActivityChart: document.getElementById('showActivityChart').checked,
                showBalanceWheel: document.getElementById('showBalanceWheel').checked,
                showTimeline: document.getElementById('showTimeline').checked,
                dailyStats: document.getElementById('dailyStats').checked,
                weeklyReport: document.getElementById('weeklyReport').checked,
                achievementAlerts: document.getElementById('achievementAlerts').checked,
                updateInterval: document.getElementById('updateInterval').value
            };
            
            localStorage.setItem('famplanStatsSettings', JSON.stringify(settings));
            showNotification('Настройки сохранены!', 'success');
            closeModal('statsSettingsModal');
        }
        
        function loadStatsSettings() {
            const saved = localStorage.getItem('famplanStatsSettings');
            if (saved) {
                const settings = JSON.parse(saved);
                
                document.getElementById('showActivityChart').checked = settings.showActivityChart;
                document.getElementById('showBalanceWheel').checked = settings.showBalanceWheel;
                document.getElementById('showTimeline').checked = settings.showTimeline;
                document.getElementById('dailyStats').checked = settings.dailyStats;
                document.getElementById('weeklyReport').checked = settings.weeklyReport;
                document.getElementById('achievementAlerts').checked = settings.achievementAlerts;
                document.getElementById('updateInterval').value = settings.updateInterval;
                document.getElementById('intervalValue').textContent = settings.updateInterval;
            }
        }
        
        function startDataAutoUpdate() {
            const interval = localStorage.getItem('famplanStatsSettings') 
                ? JSON.parse(localStorage.getItem('famplanStatsSettings')).updateInterval * 60000 
                : 30 * 60000;
            
            setInterval(() => {
                if (document.querySelector('#data.content-section.active')) {
                    updateFamilyStats();
                    showNotification('📊 Статистика обновлена!', 'info', 2000);
                }
            }, interval);
        }
        
        // ==================== ЭКСПОРТ ДАННЫХ ====================
        
        function exportFamilyData() {
            const data = {
                exportDate: new Date().toISOString(),
                events: [],
                checklists: [],
                familyMembers: [],
                memories: []
            };
            
            // Собираем данные из DOM (в реальном приложении - с сервера)
            // Это демо-реализация
            showNotification('📥 Экспорт данных начат...', 'info');
            
            setTimeout(() => {
                // Создаем JSON файл
                const json = JSON.stringify(data, null, 2);
                const blob = new Blob([json], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                
                // Создаем ссылку для скачивания
                const a = document.createElement('a');
                a.href = url;
                a.download = `famplan-backup-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                
                // Освобождаем память
                URL.revokeObjectURL(url);
                
                showNotification('✅ Данные успешно экспортированы!', 'success');
            }, 1000);
        }
        
        // ==================== УВЕДОМЛЕНИЯ ====================
        
        function showNotification(message, type = 'info', duration = 3000) {
            // Создаем контейнер для уведомлений если его нет
            let notificationContainer = document.querySelector('.notification-container');
            if (!notificationContainer) {
                notificationContainer = document.createElement('div');
                notificationContainer.className = 'notification-container';
                notificationContainer.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    max-width: 350px;
                `;
                document.body.appendChild(notificationContainer);
            }
            
            // Иконки для разных типов уведомлений
            const icons = {
                success: { icon: 'fas fa-check-circle', color: '#28a745' },
                error: { icon: 'fas fa-exclamation-circle', color: '#dc3545' },
                warning: { icon: 'fas fa-exclamation-triangle', color: '#ffc107' },
                info: { icon: 'fas fa-info-circle', color: '#17a2b8' }
            };
            
            const config = icons[type] || icons.info;
            
            // Создаем уведомление
            const notification = document.createElement('div');
            notification.className = `notification-global ${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                gap: 12px;
                z-index: 10000;
                box-shadow: 0 6px 20px rgba(0,0,0,0.15);
                max-width: 400px;
                animation: slideIn 0.3s ease;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255,255,255,0.2);
                color: white;
                background: ${type === 'success' ? 'rgba(40, 167, 69, 0.95)' : 
                          type === 'error' ? 'rgba(220, 53, 69, 0.95)' : 
                          'rgba(23, 162, 184, 0.95)'};
            `;
            
            notification.innerHTML = `
                <i class="${config.icon}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" style="margin-left: auto; background: none; border: none; color: white; cursor: pointer; font-size: 20px;">&times;</button>
            `;
            
            document.body.appendChild(notification);
            
            // Автоматическое скрытие
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => notification.remove(), 300);
                }
            }, duration);
            
            // Остановить автоскрытие при наведении
            notification.addEventListener('mouseenter', () => {
                clearTimeout(autoHide);
            });
            
            const autoHide = setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, duration);
        }
        
        // Закрытие модальных окон при клике вне контента
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.opacity = '0';
                setTimeout(() => {
                    event.target.style.display = 'none';
                }, 300);
            }
        };
        
        // Закрытие по Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const openModals = document.querySelectorAll('.modal[style*="display: flex"]');
                openModals.forEach(modal => {
                    closeModal(modal.id);
                });
            }
        });
    </script>
</body>
</html>