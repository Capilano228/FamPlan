<?php
session_start();

// Упрощенная версия без AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                // Простая демо-авторизация
                $demo_users = [
                    'parent@example.com' => ['id' => 1, 'username' => 'Родитель', 'role' => 'parent', 'family_id' => 1],
                    'child1@example.com' => ['id' => 2, 'username' => 'Анна', 'role' => 'child', 'family_id' => 1],
                    'child2@example.com' => ['id' => 3, 'username' => 'Максим', 'role' => 'child', 'family_id' => 1]
                ];
                
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                if ($password === '123456' && isset($demo_users[$email])) {
                    $_SESSION['user_id'] = $demo_users[$email]['id'];
                    $_SESSION['username'] = $demo_users[$email]['username'];
                    $_SESSION['role'] = $demo_users[$email]['role'];
                    $_SESSION['family_id'] = $demo_users[$email]['family_id'];
                }
                break;
                
            case 'add_event':
                if (isset($_SESSION['family_id'])) {
                    if (!isset($_SESSION['events'])) {
                        $_SESSION['events'] = [];
                    }
                    
                    $event_id = count($_SESSION['events']) + 1;
                    $_SESSION['events'][] = [
                        'id' => $event_id,
                        'family_id' => $_SESSION['family_id'],
                        'title' => htmlspecialchars($_POST['title'] ?? 'Новое событие'),
                        'description' => htmlspecialchars($_POST['description'] ?? ''),
                        'event_date' => $_POST['event_date'] ?? date('Y-m-d'),
                        'event_time' => $_POST['event_time'] ?? '12:00',
                        'color' => $_POST['color'] ?? '#A8D8EA',
                        'created_by' => $_SESSION['user_id']
                    ];
                }
                break;
                
            case 'add_checklist':
                if (isset($_SESSION['family_id'])) {
                    if (!isset($_SESSION['checklists'])) {
                        $_SESSION['checklists'] = [];
                    }
                    
                    $_SESSION['checklists'][] = [
                        'event_id' => intval($_POST['event_id']),
                        'item' => htmlspecialchars($_POST['item'] ?? ''),
                        'is_checked' => false
                    ];
                }
                break;
                
            case 'add_child':
                if ($_SESSION['role'] === 'parent') {
                    if (!isset($_SESSION['family_members'])) {
                        $_SESSION['family_members'] = [
                            ['id' => 1, 'username' => 'Родитель', 'role' => 'parent', 'family_id' => 1],
                            ['id' => 2, 'username' => 'Анна', 'role' => 'child', 'family_id' => 1],
                            ['id' => 3, 'username' => 'Максим', 'role' => 'child', 'family_id' => 1]
                        ];
                    }
                    
                    $new_id = count($_SESSION['family_members']) + 1;
                    $_SESSION['family_members'][] = [
                        'id' => $new_id,
                        'username' => htmlspecialchars($_POST['child_name'] ?? 'Новый ребенок'),
                        'role' => 'child',
                        'family_id' => $_SESSION['family_id']
                    ];
                }
                break;
                
            case 'logout':
                session_destroy();
                break;
        }
        
        // После обработки формы перезагружаем страницу
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Инициализация данных
if (!isset($_SESSION['events'])) {
    $_SESSION['events'] = [
        [
            'id' => 1,
            'family_id' => 1,
            'title' => 'День рождения мамы',
            'description' => 'Подготовить сюрприз!',
            'event_date' => date('Y-m-d', strtotime('+5 days')),
            'event_time' => '18:00',
            'color' => '#FF9AA2',
            'created_by' => 1
        ],
        [
            'id' => 2,
            'family_id' => 1,
            'title' => 'Поход в кино',
            'description' => 'Фильм "Семейные ценности"',
            'event_date' => date('Y-m-d', strtotime('+2 days')),
            'event_time' => '15:00',
            'color' => '#A8D8EA',
            'created_by' => 1
        ]
    ];
}

if (!isset($_SESSION['checklists'])) {
    $_SESSION['checklists'] = [
        ['event_id' => 1, 'item' => 'Купить торт', 'is_checked' => false],
        ['event_id' => 1, 'item' => 'Подготовить подарок', 'is_checked' => true],
        ['event_id' => 2, 'item' => 'Купить билеты', 'is_checked' => false]
    ];
}

if (!isset($_SESSION['family_members'])) {
    $_SESSION['family_members'] = [
        ['id' => 1, 'username' => 'Родитель', 'role' => 'parent', 'family_id' => 1],
        ['id' => 2, 'username' => 'Анна', 'role' => 'child', 'family_id' => 1],
        ['id' => 3, 'username' => 'Максим', 'role' => 'child', 'family_id' => 1]
    ];
}

// Данные для отображения
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;
$family_id = $_SESSION['family_id'] ?? null;
$events = $_SESSION['events'] ?? [];
$checklists = $_SESSION['checklists'] ?? [];
$family_members = $_SESSION['family_members'] ?? [];

// Группировка чек-листов по событиям
$event_checklists = [];
foreach ($events as $event) {
    if ($event['family_id'] == $family_id) {
        $event_checklists[$event['id']] = [
            'event' => $event,
            'items' => []
        ];
        
        foreach ($checklists as $item) {
            if ($item['event_id'] == $event['id']) {
                $event_checklists[$event['id']]['items'][] = $item;
            }
        }
    }
}

// Фильтрация членов семьи
$family_members = array_filter($family_members, function($member) use ($family_id) {
    return $member['family_id'] == $family_id;
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
</head>
<body>
    <?php if (!$user_id): ?>
        <!-- Экран авторизации (оставить как в предыдущей версии) -->
        <!-- ... тот же код авторизации ... -->
    <?php else: ?>
        <!-- Основной интерфейс с обычными формами (без AJAX) -->
        <div class="app-container">
            <!-- Боковая панель (оставить как было) -->
            <!-- ... -->
            
            <main class="main-content">
                <!-- Хедер -->
                <header class="main-header">
                    <div class="header-left">
                        <h1>FamPlan</h1>
                        <p class="greeting">Добро пожаловать, <?php echo htmlspecialchars($username); ?>!</p>
                    </div>
                </header>
                
                <!-- Секция календаря -->
                <section class="content-section active">
                    <div class="section-header">
                        <h2><i class="fas fa-calendar-heart"></i> Семейный календарь</h2>
                        <button class="btn-add" onclick="showAddEventModal()">
                            <i class="fas fa-plus-circle"></i> Добавить событие
                        </button>
                    </div>
                    
                    <!-- Форма добавления события (обычная, не AJAX) -->
                    <div id="addEventForm" class="modal-form" style="display: none;">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2><i class="fas fa-calendar-plus"></i> Новое событие</h2>
                                <button class="close-modal" onclick="closeModal('addEventForm')">&times;</button>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="add_event">
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
                                        <label>
                                            <input type="radio" name="color" value="#FF9AA2" checked>
                                            <span class="color-option" style="background: #FF9AA2;"></span>
                                        </label>
                                        <label>
                                            <input type="radio" name="color" value="#A8D8EA">
                                            <span class="color-option" style="background: #A8D8EA;"></span>
                                        </label>
                                        <label>
                                            <input type="radio" name="color" value="#FFD3B6">
                                            <span class="color-option" style="background: #FFD3B6;"></span>
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn-submit">
                                    <i class="fas fa-plus-circle"></i> Добавить событие
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Календарь и события -->
                    <div class="calendar-widget">
                        <!-- ... календарь ... -->
                    </div>
                    
                    <!-- Чек-листы с обычными формами -->
                    <div class="checklists-container">
                        <?php foreach ($event_checklists as $event_id => $data): ?>
                            <div class="checklist-card">
                                <div class="checklist-header">
                                    <h3><?php echo htmlspecialchars($data['event']['title']); ?></h3>
                                </div>
                                
                                <div class="checklist-items">
                                    <?php foreach ($data['items'] as $item): ?>
                                        <div class="checklist-item <?php echo $item['is_checked'] ? 'checked' : ''; ?>">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_checklist">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="check-btn">
                                                    <?php echo $item['is_checked'] ? '✓' : '○'; ?>
                                                </button>
                                                <?php echo htmlspecialchars($item['item']); ?>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Форма добавления пункта -->
                                    <form method="POST" class="add-checklist-form">
                                        <input type="hidden" name="action" value="add_checklist">
                                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                        <input type="text" name="item" placeholder="Добавить новый пункт..." required>
                                        <button type="submit" class="btn-add-item">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Форма добавления ребенка -->
                    <div id="addChildForm" class="modal-form" style="display: none;">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2><i class="fas fa-child"></i> Добавить ребенка</h2>
                                <button class="close-modal" onclick="closeModal('addChildForm')">&times;</button>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="add_child">
                                <div class="form-group">
                                    <label>Имя ребенка</label>
                                    <input type="text" name="child_name" required>
                                </div>
                                <button type="submit" class="btn-submit">Добавить</button>
                            </form>
                        </div>
                    </div>
                </section>
            </main>
        </div>
        
        <script>
            // Простые функции для модальных окон
            function showAddEventModal() {
                document.getElementById('addEventForm').style.display = 'block';
            }
            
            function showAddChildModal() {
                document.getElementById('addChildForm').style.display = 'block';
            }
            
            function closeModal(modalId) {
                document.getElementById(modalId).style.display = 'none';
            }
            
            // Закрытие по клику вне модального окна
            window.onclick = function(event) {
                if (event.target.classList.contains('modal-form')) {
                    event.target.style.display = 'none';
                }
            };
        </script>
    <?php endif; ?>
</body>
</html>