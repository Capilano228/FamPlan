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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <style>
        /* Темная бежевая цветовая схема */
        :root {
            --primary-beige: #E8DBC5;
            --secondary-beige: #D4C4A8;
            --dark-beige: #A8997E;
            --light-beige: #F5EFE0;
            --text-beige: #5D5342;
            
            --accent-coral: #C9A68E;
            --accent-blue: #A8C3CE;
            --accent-peach: #E0C9B1;
            --accent-lavender: #C7B8A6;
            --accent-mint: #B5C7B1;
            
            --text-dark: #3C3529;
            --text-medium: #6B6251;
            --text-light: #8A7F6D;
        }
        
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
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
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
                        <!-- Логотип - можно заменить на изображение -->
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
        
    <?php endif; ?>
    
    <script src="script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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