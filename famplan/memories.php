<?php
session_start();

// Если пользователь не авторизован, редиректим на главную
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Пользователь';
$role = $_SESSION['role'] ?? 'child';
$avatar_color = $_SESSION['avatar_color'] ?? '#A8D8EA';
$family_id = $_SESSION['family_id'] ?? null;

// Инициализируем массив воспоминаний в сессии
if (!isset($_SESSION['memories'])) {
    $_SESSION['memories'] = [];
}

// Демо-данные для воспоминаний
$demo_memories = [
    [
        'id' => 1,
        'title' => 'Семейный пикник',
        'description' => 'Замечательный день в парке с игрой в бадминтон!',
        'date' => date('Y-m-d', strtotime('-2 days')),
        'formatted_date' => date('d.m.Y', strtotime('-2 days')),
        'image' => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80',
        'author' => 'Родитель',
        'author_color' => '#FF9AA2',
        'likes' => 5,
        'comments' => 3
    ],
    [
        'id' => 2,
        'title' => 'Успех в школе',
        'description' => 'Анна получила пятерку по математике! Гордимся!',
        'date' => date('Y-m-d', strtotime('-1 day')),
        'formatted_date' => date('d.m.Y', strtotime('-1 day')),
        'image' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80',
        'author' => 'Анна',
        'author_color' => '#A8D8EA',
        'likes' => 8,
        'comments' => 2
    ],
    [
        'id' => 3,
        'title' => 'Первая победа',
        'description' => 'Максим выиграл турнир по шахматам!',
        'date' => date('Y-m-d', strtotime('-3 days')),
        'formatted_date' => date('d.m.Y', strtotime('-3 days')),
        'image' => 'https://images.unsplash.com/photo-1511988617509-a57c8a288659?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80',
        'author' => 'Максим',
        'author_color' => '#FFD3B6',
        'likes' => 12,
        'comments' => 5
    ]
];

// Если в сессии нет воспоминаний, инициализируем демо-данными
if (empty($_SESSION['memories'])) {
    $_SESSION['memories'] = $demo_memories;
    $_SESSION['next_memory_id'] = count($demo_memories) + 1;
}

// Обработка добавления нового воспоминания
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_memory'])) {
    $newMemory = [
        'id' => $_SESSION['next_memory_id'] ?? 1,
        'title' => htmlspecialchars($_POST['title'] ?? 'Новое воспоминание'),
        'description' => htmlspecialchars($_POST['description'] ?? ''),
        'date' => $_POST['memory_date'] ?? date('Y-m-d'),
        'formatted_date' => date('d.m.Y', strtotime($_POST['memory_date'] ?? 'now')),
        'image' => $_POST['image_url'] ?? 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80',
        'author' => $username,
        'author_color' => $avatar_color,
        'likes' => 0,
        'comments' => 0
    ];
    
    // Добавляем в начало массива (чтобы новые были первыми)
    array_unshift($_SESSION['memories'], $newMemory);
    
    // Увеличиваем ID для следующего воспоминания
    $_SESSION['next_memory_id'] = ($_SESSION['next_memory_id'] ?? 1) + 1;
    
    // Редирект на эту же страницу без POST данных
    header('Location: memories.php');
    exit;
}

// Получаем все воспоминания
$memories = $_SESSION['memories'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Воспоминания • FamPlan</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <style>
        .memories-header {
            background: linear-gradient(135deg, #FF9AA2, #FFD3B6);
            padding: 60px 20px;
            text-align: center;
            color: white;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .memories-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="white" opacity="0.1"><path d="M50,20 C65,20 77,32 77,47 C77,62 65,74 50,74 C35,74 23,62 23,47 C23,32 35,20 50,20 Z M50,30 C59,30 67,38 67,47 C67,56 59,64 50,64 C41,64 33,56 33,47 C33,38 41,30 50,30 Z"/></svg>');
        }
        
        .memories-title {
            font-family: 'Pacifico', cursive;
            font-size: 48px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .memories-subtitle {
            font-size: 18px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .memories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            padding: 0 20px;
            max-width: 1200px;
            margin: 0 auto 40px;
        }
        
        .memory-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        
        .memory-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .memory-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .memory-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .memory-card:hover .memory-image img {
            transform: scale(1.1);
        }
        
        .memory-date-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .memory-content {
            padding: 20px;
        }
        
        .memory-title {
            font-size: 20px;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        
        .memory-description {
            color: var(--text-medium);
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .memory-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: var(--text-light);
        }
        
        .memory-author {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .author-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .memory-stats {
            display: flex;
            gap: 15px;
        }
        
        .stat {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .stat:hover {
            color: var(--accent-coral);
        }
        
        .add-memory-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(45deg, #FF9AA2, #FFD3B6);
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(255, 154, 162, 0.4);
            transition: all 0.3s ease;
            z-index: 100;
        }
        
        .add-memory-btn:hover {
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 15px 40px rgba(255, 154, 162, 0.6);
        }
        
        .empty-memories {
            text-align: center;
            padding: 60px 20px;
            grid-column: 1 / -1;
        }
        
        .empty-memories i {
            font-size: 72px;
            color: var(--accent-blue);
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            z-index: 10;
        }
        
        .back-button:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
        }
        
        /* Модальное окно для добавления воспоминания */
        .memory-modal {
            max-width: 600px;
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
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--dark-beige);
            border-radius: 10px;
            font-size: 16px;
            font-family: 'Nunito', sans-serif;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-blue);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .image-preview {
            margin-top: 10px;
            display: none;
        }
        
        .image-preview img {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #FF9AA2, #FFD3B6);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 154, 162, 0.3);
        }
        
        @media (max-width: 768px) {
            .memories-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                padding: 0 15px;
            }
            
            .memories-title {
                font-size: 36px;
            }
            
            .add-memory-btn {
                bottom: 20px;
                right: 20px;
                width: 60px;
                height: 60px;
            }
        }
        
        @media (max-width: 480px) {
            .memories-grid {
                grid-template-columns: 1fr;
            }
            
            .memories-title {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Кнопка назад -->
    <a href="index.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Назад
    </a>
    
    <!-- Хедер -->
    <div class="memories-header">
        <h1 class="memories-title">Семейные воспоминания</h1>
        <p class="memories-subtitle">Здесь хранятся самые теплые моменты вашей семьи</p>
    </div>
    
    <!-- Галерея воспоминаний -->
    <div class="memories-grid" id="memoriesContainer">
        <?php if (!empty($memories)): ?>
            <?php foreach ($memories as $memory): ?>
                <div class="memory-card">
                    <div class="memory-image">
                        <img src="<?php echo $memory['image']; ?>" alt="<?php echo htmlspecialchars($memory['title']); ?>" loading="lazy">
                        <div class="memory-date-badge">
                            <i class="far fa-calendar"></i>
                            <?php echo $memory['formatted_date']; ?>
                        </div>
                    </div>
                    <div class="memory-content">
                        <h3 class="memory-title"><?php echo htmlspecialchars($memory['title']); ?></h3>
                        <p class="memory-description"><?php echo htmlspecialchars($memory['description']); ?></p>
                        <div class="memory-meta">
                            <div class="memory-author">
                                <div class="author-avatar" style="background: <?php echo $memory['author_color']; ?>;">
                                    <?php echo substr($memory['author'], 0, 1); ?>
                                </div>
                                <span><?php echo $memory['author']; ?></span>
                            </div>
                            <div class="memory-stats">
                                <div class="stat" onclick="likeMemory(<?php echo $memory['id']; ?>, this)">
                                    <i class="far fa-heart"></i>
                                    <span class="like-count"><?php echo $memory['likes']; ?></span>
                                </div>
                                <div class="stat">
                                    <i class="far fa-comment"></i>
                                    <span><?php echo $memory['comments']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-memories">
                <i class="fas fa-images"></i>
                <h2>Пока нет воспоминаний</h2>
                <p>Добавьте первое семейное воспоминание!</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Кнопка добавления воспоминания -->
    <button class="add-memory-btn" onclick="showAddMemoryModal()">
        <i class="fas fa-plus"></i>
    </button>
    
    <!-- Модальное окно добавления воспоминания -->
    <div id="addMemoryModal" class="modal memory-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Добавить воспоминание</h2>
                <button class="close-modal" onclick="closeModal('addMemoryModal')">&times;</button>
            </div>
            <form method="POST" id="memoryForm">
                <input type="hidden" name="add_memory" value="1">
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
                        <label><i class="fas fa-image"></i> Ссылка на фотографию (URL)</label>
                        <input type="url" name="image_url" id="imageUrl" 
                               placeholder="https://example.com/photo.jpg" 
                               oninput="updateImagePreview(this.value)">
                        <small style="color: var(--text-light); display: block; margin-top: 5px;">
                            Можно использовать ссылки с Unsplash: https://images.unsplash.com/photo-...
                        </small>
                        <div class="image-preview" id="imagePreview">
                            <img src="" alt="Предпросмотр" id="previewImage">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Сохранить воспоминание
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let memories = <?php echo json_encode($memories); ?>;
        
        function showAddMemoryModal() {
            const modal = document.getElementById('addMemoryModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.style.opacity = '0';
                
                setTimeout(() => {
                    modal.style.opacity = '1';
                    const content = modal.querySelector('.modal-content');
                    if (content) {
                        content.style.transform = 'scale(1)';
                    }
                    
                    // Фокус на первом поле
                    const firstInput = modal.querySelector('input[name="title"]');
                    if (firstInput) {
                        setTimeout(() => firstInput.focus(), 200);
                    }
                }, 10);
            }
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.opacity = '0';
                const content = modal.querySelector('.modal-content');
                if (content) {
                    content.style.transform = 'scale(0.9)';
                }
                
                setTimeout(() => {
                    modal.style.display = 'none';
                    if (content) {
                        content.style.transform = '';
                    }
                }, 300);
            }
        }
        
        function updateImagePreview(url) {
            const preview = document.getElementById('imagePreview');
            const previewImage = document.getElementById('previewImage');
            
            if (url && url.startsWith('http')) {
                previewImage.src = url;
                preview.style.display = 'block';
                
                // Проверяем загрузку изображения
                previewImage.onload = function() {
                    previewImage.style.opacity = '1';
                };
                
                previewImage.onerror = function() {
                    previewImage.src = 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80';
                    previewImage.alt = 'Стандартное изображение';
                };
            } else {
                preview.style.display = 'none';
            }
        }
        
        function likeMemory(memoryId, element) {
            const heartIcon = element.querySelector('i');
            const likeCount = element.querySelector('.like-count');
            
            if (heartIcon.classList.contains('far')) {
                // Ставим лайк
                heartIcon.className = 'fas fa-heart';
                likeCount.textContent = parseInt(likeCount.textContent) + 1;
                element.style.color = '#FF9AA2';
                
                // Анимация
                element.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    element.style.transform = '';
                }, 300);
                
                showNotification('❤️ Вам понравилось это воспоминание!');
            } else {
                // Убираем лайк
                heartIcon.className = 'far fa-heart';
                likeCount.textContent = parseInt(likeCount.textContent) - 1;
                element.style.color = '';
                
                showNotification('💔 Лайк убран', 'info');
            }
        }
        
        function showNotification(message, type = 'success') {
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
                background: ${type === 'success' ? 'rgba(40, 167, 69, 0.95)' : 'rgba(23, 162, 184, 0.95)'};
            `;
            
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" style="margin-left: auto; background: none; border: none; color: white; cursor: pointer;">×</button>
            `;
            
            document.body.appendChild(notification);
            
            // Автоматическое скрытие
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 3000);
        }
        
        // Обработка формы добавления воспоминания
        document.getElementById('memoryForm')?.addEventListener('submit', function(e) {
            const title = this.querySelector('input[name="title"]').value;
            const description = this.querySelector('textarea[name="description"]').value;
            
            if (!title.trim() || !description.trim()) {
                e.preventDefault();
                showNotification('Заполните все обязательные поля', 'info');
                return false;
            }
            
            // Показываем уведомление о сохранении
            showNotification('💾 Сохраняем воспоминание...', 'info');
            
            return true;
        });
        
        // Анимация появления карточек
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.memory-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s, transform 0.5s';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Если есть новые воспоминания (из URL параметра)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('new')) {
                showNotification('🎉 Новое воспоминание успешно добавлено!', 'success');
                // Убираем параметр из URL без перезагрузки
                history.replaceState({}, document.title, window.location.pathname);
            }
        });
        
        // Закрытие модальных окон при клике вне контента
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id);
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