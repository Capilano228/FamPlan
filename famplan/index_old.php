<?php
require_once 'config.php';

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'register':
                handleRegistration($conn);
                break;
            case 'login':
                handleLogin($conn);
                break;
            case 'create_family':
                handleCreateFamily($conn);
                break;
            case 'join_family':
                handleJoinFamily($conn);
                break;
            case 'add_event':
                handleAddEvent($conn);
                break;
            case 'add_checklist_item':
                handleAddChecklistItem($conn);
                break;
            case 'send_message':
                handleSendMessage($conn);
                break;
        }
    }
}

function handleRegistration($conn) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'] ?? 'child';
    
    $sql = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', '$role')";
    
    if ($conn->query($sql)) {
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        echo "<script>alert('Регистрация успешна!'); window.location.href = window.location.href;</script>";
    }
}

function handleLogin($conn) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['family_id'] = $user['family_id'];
        }
    }
}

function handleCreateFamily($conn) {
    if (!isset($_SESSION['user_id'])) return;
    
    $familyName = $conn->real_escape_string($_POST['family_name']);
    $joinCode = substr(md5(uniqid()), 0, 8);
    
    $sql = "INSERT INTO families (name, join_code, created_by) VALUES ('$familyName', '$joinCode', {$_SESSION['user_id']})";
    
    if ($conn->query($sql)) {
        $familyId = $conn->insert_id;
        $conn->query("UPDATE users SET family_id = $familyId WHERE id = {$_SESSION['user_id']}");
        $_SESSION['family_id'] = $familyId;
        $_SESSION['join_code'] = $joinCode;
    }
}

// ... остальные функции обработки

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FamPlan - Семейный организатор</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Навигация -->
        <nav class="navbar">
            <div class="logo">
                <i class="fas fa-heart"></i>
                <span>FamPlan</span>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <span>Привет, <?php echo $_SESSION['username']; ?>!</span>
                    <a href="#dashboard" class="nav-link"><i class="fas fa-home"></i> Главная</a>
                    <a href="#calendar" class="nav-link"><i class="fas fa-calendar"></i> Календарь</a>
                    <a href="#chat" class="nav-link"><i class="fas fa-comments"></i> Чат</a>
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                </div>
            <?php endif; ?>
        </nav>

        <!-- Контент -->
        <div class="content">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <!-- Регистрация/Вход -->
                <div class="auth-container">
                    <div class="auth-tabs">
                        <button class="tab-btn active" onclick="showTab('login')">Вход</button>
                        <button class="tab-btn" onclick="showTab('register')">Регистрация</button>
                    </div>
                    
                    <div id="login" class="tab-content active">
                        <h2>Вход в FamPlan</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="login">
                            <input type="email" name="email" placeholder="Email" required>
                            <input type="password" name="password" placeholder="Пароль" required>
                            <button type="submit" class="btn-primary">Войти</button>
                        </form>
                    </div>
                    
                    <div id="register" class="tab-content">
                        <h2>Регистрация</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="register">
                            <input type="text" name="username" placeholder="Имя" required>
                            <input type="email" name="email" placeholder="Email" required>
                            <input type="password" name="password" placeholder="Пароль" required>
                            <select name="role">
                                <option value="parent">Родитель</option>
                                <option value="child">Ребенок</option>
                            </select>
                            <button type="submit" class="btn-primary">Зарегистрироваться</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Основной интерфейс -->
                <div class="dashboard">
                    <?php if (!isset($_SESSION['family_id'])): ?>
                        <!-- Создание/присоединение к семье -->
                        <div class="family-setup">
                            <h2>Добро пожаловать в FamPlan!</h2>
                            <div class="setup-options">
                                <div class="setup-card">
                                    <h3><i class="fas fa-plus-circle"></i> Создать семью</h3>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="create_family">
                                        <input type="text" name="family_name" placeholder="Название семьи" required>
                                        <button type="submit" class="btn-primary">Создать</button>
                                    </form>
                                </div>
                                
                                <div class="setup-card">
                                    <h3><i class="fas fa-user-plus"></i> Присоединиться к семье</h3>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="join_family">
                                        <input type="text" name="join_code" placeholder="Код семьи" required>
                                        <button type="submit" class="btn-secondary">Присоединиться</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Календарь -->
                        <section id="calendar-section">
                            <h2><i class="fas fa-calendar-alt"></i> Семейный календарь</h2>
                            <div class="calendar-container">
                                <div class="calendar-header">
                                    <button onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                                    <h3 id="current-month">Январь 2024</h3>
                                    <button onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                                </div>
                                <div class="calendar" id="calendar"></div>
                            </div>
                            
                            <!-- Добавление события -->
                            <div class="add-event-form">
                                <h3>Добавить событие</h3>
                                <form method="POST">
                                    <input type="hidden" name="action" value="add_event">
                                    <input type="text" name="title" placeholder="Название события" required>
                                    <textarea name="description" placeholder="Описание"></textarea>
                                    <input type="date" name="event_date" required>
                                    <input type="time" name="event_time">
                                    <button type="submit" class="btn-primary">Добавить</button>
                                </form>
                            </div>
                            
                            <!-- Список событий -->
                            <div class="events-list">
                                <h3>Ближайшие события</h3>
                                <div id="events-container">
                                    <!-- События будут загружены через JS -->
                                </div>
                            </div>
                        </section>
                        
                        <!-- Чек-листы -->
                        <section id="checklists-section">
                            <h2><i class="fas fa-tasks"></i> Чек-листы</h2>
                            <div class="checklists-container">
                                <!-- Чек-листы будут загружены через JS -->
                            </div>
                        </section>
                        
                        <!-- Семейный чат -->
                        <section id="chat-section">
                            <h2><i class="fas fa-comments"></i> Семейный чат</h2>
                            <div class="chat-container">
                                <div class="messages" id="messages-container">
                                    <!-- Сообщения будут загружены через JS -->
                                </div>
                                <div class="message-input">
                                    <form method="POST" onsubmit="sendMessage(event)">
                                        <input type="hidden" name="action" value="send_message">
                                        <input type="text" id="message-input" name="message" placeholder="Напишите сообщение..." required>
                                        <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i></button>
                                    </form>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>