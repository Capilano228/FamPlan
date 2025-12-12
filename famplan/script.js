// script.js - Оптимизирован для FamPlan
document.addEventListener('DOMContentLoaded', function() {
    initFamPlanApp();
});

function initFamPlanApp() {
    console.log('🏠 FamPlan инициализирован');
    
    // Инициализация всех модулей
    initNavigation();
    initCalendarInteractions();
    initChecklists();
    initChat();
    initModals();
    initForms();
    initNotifications();
    initTooltips();
    
    // Показываем приветствие
    showWelcomeGreeting();
    
    // Обновляем время
    updateDateTime();
    setInterval(updateDateTime, 60000);
    
    // Добавляем анимации
    initAnimations();
    
    // Прокручиваем чат вниз
    scrollChatToBottom();
}

// ==================== НАВИГАЦИЯ ====================
function initNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.content-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href')?.substring(1);
            if (!targetId) return;
            
            // Обновляем активные элементы
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            
            // Показываем секцию
            sections.forEach(section => {
                section.classList.remove('active');
                if (section.id === targetId) {
                    section.classList.add('active');
                    
                    // Обновляем заголовок страницы
                    const pageTitle = document.getElementById('pageTitle');
                    if (pageTitle) {
                        const navText = this.querySelector('span')?.textContent || 'FamPlan';
                        pageTitle.textContent = navText;
                    }
                    
                    // Специальные действия для секций
                    handleSectionChange(targetId);
                }
            });
            
            // Плавная прокрутка для мобильных устройств
            if (window.innerWidth < 768) {
                const mainContent = document.querySelector('.main-content');
                if (mainContent) {
                    mainContent.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
    
    // Автоматическое обновление активного элемента при загрузке
    const activeSection = document.querySelector('.content-section.active');
    if (activeSection) {
        const activeId = activeSection.id;
        const correspondingNav = document.querySelector(`.nav-item[href="#${activeId}"]`);
        if (correspondingNav) {
            correspondingNav.classList.add('active');
        }
    }
}

function handleSectionChange(sectionId) {
    switch(sectionId) {
        case 'dashboard':
            updateDashboardStats();
            highlightTodayInCalendar();
            break;
        case 'checklists':
            updateChecklistProgress();
            break;
        case 'family':
            updateFamilyMemberStats();
            break;
        case 'chat':
            scrollChatToBottom();
            focusChatInput();
            loadChatMessages();
            break;
    }
}

// ==================== КАЛЕНДАРЬ ====================
// ==================== КАЛЕНДАРЬ (УПРОЩЕННАЯ ВЕРСИЯ) ====================
function initCalendarInteractions() {
    const calendarDays = document.querySelectorAll('.calendar-day:not(.empty)');
    
    calendarDays.forEach(day => {
        // ТОЛЬКО визуальные эффекты при наведении
        day.addEventListener('mouseenter', function() {
            if (!this.classList.contains('today')) {
                this.style.transform = 'translateY(-2px) scale(1.05)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                this.style.zIndex = '1';
            }
        });
        
        day.addEventListener('mouseleave', function() {
            if (!this.classList.contains('today')) {
                this.style.transform = '';
                this.style.boxShadow = '';
                this.style.zIndex = '';
            }
        });
        
        // Показываем простую подсказку при клике (БЕЗ МОДАЛЬНОГО ОКНА)
        day.addEventListener('click', function() {
            const date = this.getAttribute('data-date');
            if (date) {
                const dateObj = new Date(date);
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const formattedDate = dateObj.toLocaleDateString('ru-RU', options);
                
                // Просто меняем цвет дня на 1 секунду
                const originalBg = this.style.backgroundColor;
                const originalBorder = this.style.borderColor;
                
                this.style.backgroundColor = '#E8F4FD';
                this.style.borderColor = '#4A90E2';
                
                // Показываем всплывающую подсказку
                const tooltip = document.createElement('div');
                tooltip.className = 'date-tooltip';
                tooltip.textContent = formattedDate;
                tooltip.style.cssText = `
                    position: absolute;
                    background: rgba(0,0,0,0.85);
                    color: white;
                    padding: 8px 12px;
                    border-radius: 6px;
                    font-size: 12px;
                    z-index: 1000;
                    white-space: nowrap;
                    top: -40px;
                    left: 50%;
                    transform: translateX(-50%);
                    animation: fadeIn 0.2s ease;
                `;
                
                this.appendChild(tooltip);
                
                // Убираем через 1.5 секунды
                setTimeout(() => {
                    this.style.backgroundColor = originalBg;
                    this.style.borderColor = originalBorder;
                    if (tooltip.parentNode) {
                        tooltip.remove();
                    }
                }, 1500);
            }
        });
    });
    
    // Подсветка сегодняшнего дня
    highlightTodayInCalendar();
}

function highlightTodayInCalendar() {
    const today = new Date().toISOString().split('T')[0];
    const todayElement = document.querySelector(`.calendar-day[data-date="${today}"]`);
    
    if (todayElement) {
        todayElement.classList.add('today');
        
        // Анимация пульсации при загрузке
        setTimeout(() => {
            todayElement.style.transform = 'scale(1.1)';
            todayElement.style.boxShadow = '0 0 20px rgba(168, 216, 234, 0.5)';
            
            setTimeout(() => {
                todayElement.style.transform = '';
                todayElement.style.boxShadow = '';
            }, 500);
        }, 1000);
    }
}

// ==================== ЧЕК-ЛИСТЫ ====================
function initChecklists() {
    // Обработка переключения состояния чекбоксов
    document.addEventListener('submit', function(e) {
        if (e.target.classList.contains('toggle-form')) {
            e.preventDefault();
            const form = e.target;
            const checkBtn = form.querySelector('.check-btn');
            const checklistItem = form.closest('.checklist-item');
            
            if (checkBtn && checklistItem) {
                toggleChecklistItemVisual(checkBtn, checklistItem);
                
                // Отправляем форму через 300мс для анимации
                setTimeout(() => {
                    form.submit();
                }, 300);
            }
        }
    });
    
    // Быстрое добавление пунктов по Enter
    const checklistForms = document.querySelectorAll('.add-checklist-item form');
    checklistForms.forEach(form => {
        const input = form.querySelector('input[type="text"]');
        if (input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (this.value.trim()) {
                        form.submit();
                    }
                }
            });
        }
    });
    
    // Инициализация прогресса чек-листов
    updateChecklistProgress();
}

function toggleChecklistItemVisual(button, checklistItem) {
    const itemText = checklistItem.querySelector('.item-text');
    const itemStatus = checklistItem.querySelector('.item-status');
    
    if (!itemText || !itemStatus) return;
    
    // Анимация нажатия
    button.style.transform = 'scale(0.9)';
    
    setTimeout(() => {
        const isCurrentlyChecked = checklistItem.classList.contains('checked');
        
        if (isCurrentlyChecked) {
            // Отмечаем как невыполненное
            button.innerHTML = '<i class="far fa-square"></i>';
            checklistItem.classList.remove('checked');
            itemStatus.innerHTML = '<i class="fas fa-clock"></i> Нужно сделать';
            itemText.style.textDecoration = 'none';
            
            showNotification('Задача возобновлена', 'info');
        } else {
            // Отмечаем как выполненное
            button.innerHTML = '<i class="fas fa-check-square"></i>';
            checklistItem.classList.add('checked');
            itemStatus.innerHTML = '<i class="fas fa-check"></i> Готово';
            itemText.style.textDecoration = 'line-through';
            
            // Анимация успеха
            checklistItem.style.transform = 'scale(1.05)';
            setTimeout(() => {
                checklistItem.style.transform = '';
            }, 300);
            
            showNotification('Задача выполнена! 🎉', 'success');
            
            // Проверяем все ли задачи выполнены
            checkAllTasksCompleted(checklistItem.closest('.checklist-card'));
        }
        
        button.style.transform = 'scale(1)';
        
        // Обновляем прогресс
        updateChecklistProgress();
        
    }, 150);
}

function checkAllTasksCompleted(checklistCard) {
    if (!checklistCard) return;
    
    const allItems = checklistCard.querySelectorAll('.checklist-item');
    const completedItems = checklistCard.querySelectorAll('.checklist-item.checked');
    
    if (allItems.length > 0 && allItems.length === completedItems.length) {
        const header = checklistCard.querySelector('.checklist-header');
        if (header) {
            header.style.animation = 'pulse 1s 3';
            showNotification('🎊 Все задачи выполнены! Отличная работа!', 'success');
            
            setTimeout(() => {
                header.style.animation = '';
            }, 3000);
        }
    }
}

function updateChecklistProgress() {
    const checklistCards = document.querySelectorAll('.checklist-card');
    
    checklistCards.forEach(card => {
        const allItems = card.querySelectorAll('.checklist-item');
        const completedItems = card.querySelectorAll('.checklist-item.checked');
        
        if (allItems.length > 0) {
            const progress = Math.round((completedItems.length / allItems.length) * 100);
            
            // Обновляем или создаем прогресс-бар
            let progressBar = card.querySelector('.progress-bar');
            if (!progressBar) {
                progressBar = document.createElement('div');
                progressBar.className = 'progress-bar';
                progressBar.style.cssText = `
                    height: 4px;
                    background: #e0e0e0;
                    border-radius: 2px;
                    overflow: hidden;
                    margin-top: 10px;
                `;
                
                const progressFill = document.createElement('div');
                progressFill.className = 'progress-fill';
                progressFill.style.cssText = `
                    height: 100%;
                    width: ${progress}%;
                    background: linear-gradient(45deg, var(--accent-blue), var(--accent-mint));
                    transition: width 0.5s ease;
                    border-radius: 2px;
                `;
                
                progressBar.appendChild(progressFill);
                card.querySelector('.checklist-header')?.appendChild(progressBar);
            } else {
                const progressFill = progressBar.querySelector('.progress-fill');
                if (progressFill) {
                    progressFill.style.width = `${progress}%`;
                }
            }
            
            // Обновляем заголовок с процентом
            const headerTitle = card.querySelector('.checklist-header h3');
            if (headerTitle) {
                const originalText = headerTitle.textContent.replace(/\(\d+%\)/, '').trim();
                headerTitle.textContent = `${originalText} (${progress}%)`;
            }
        }
    });
}

// ==================== ЧАТ ====================
function initChat() {
    const messageInput = document.querySelector('.message-input input[name="message"]');
    const sendButton = document.querySelector('.btn-send');
    const chatForm = document.querySelector('.message-input form');
    
    if (messageInput && sendButton && chatForm) {
        // Отправка по Enter
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim()) {
                    sendButton.click();
                }
            }
        });
        
        // Автофокус при заходе в чат
        setTimeout(() => {
            if (document.querySelector('#chat.content-section.active')) {
                messageInput.focus();
            }
        }, 500);
        
        // Автообновление чата каждые 30 секунд
        setInterval(loadChatMessages, 30000);
    }
    
    // Загружаем начальные сообщения
    loadChatMessages();
}

function loadChatMessages() {
    const messagesContainer = document.getElementById('messagesContainer');
    if (!messagesContainer) return;
    
    // В реальном приложении здесь был бы AJAX запрос
    // Для демо просто обновляем скролл
    scrollChatToBottom();
}

function scrollChatToBottom() {
    const container = document.querySelector('.messages-container');
    if (container) {
        setTimeout(() => {
            container.scrollTop = container.scrollHeight;
        }, 100);
    }
}

function focusChatInput() {
    const messageInput = document.querySelector('.message-input input[name="message"]');
    if (messageInput) {
        setTimeout(() => {
            messageInput.focus();
            messageInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 300);
    }
}

// ==================== МОДАЛЬНЫЕ ОКНА ====================
function initModals() {
    // Закрытие при клике вне окна
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    });
    
    // Закрытие по Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal[style*="display: flex"], .modal[style*="display:block"]');
            openModals.forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
    
    // Инициализация всех модальных окон
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        const closeBtn = modal.querySelector('.close-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                closeModal(modal.id);
            });
        }
    });
}

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        
        // Анимация появления
        modal.style.opacity = '0';
        const content = modal.querySelector('.modal-content');
        if (content) {
            content.style.transform = 'scale(0.9)';
        }
        
        setTimeout(() => {
            modal.style.opacity = '1';
            if (content) {
                content.style.transform = 'scale(1)';
            }
        }, 10);
        
        // Фокус на первом поле ввода
        setTimeout(() => {
            const firstInput = modal.querySelector('input, textarea, select');
            if (firstInput) {
                firstInput.focus();
                if (firstInput.type === 'date' || firstInput.type === 'time') {
                    firstInput.showPicker?.();
                }
            }
        }, 200);
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

// ==================== ФОРМЫ ====================
function initForms() {
    // Валидация всех форм
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            validateForm(this, e);
        });
    });
    
    // Переключение видимости пароля
    const showPasswordBtns = document.querySelectorAll('.show-password');
    showPasswordBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if (input && input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                
                // Автоматическое скрытие через 5 секунд
                setTimeout(() => {
                    if (input.type === 'text') {
                        input.type = 'password';
                        this.innerHTML = '<i class="fas fa-eye"></i>';
                    }
                }, 5000);
            } else if (input) {
                input.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
    
    // Автозаполнение даты в формах
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = new Date().toISOString().split('T')[0];
        }
    });
    
    // Автозаполнение времени
    const timeInputs = document.querySelectorAll('input[type="time"]');
    timeInputs.forEach(input => {
        if (!input.value) {
            const now = new Date();
            input.value = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
        }
    });
}

function validateForm(form, event) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            markFieldAsInvalid(field, 'Это поле обязательно для заполнения');
        } else {
            markFieldAsValid(field);
            
            // Дополнительные проверки
            if (field.type === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value)) {
                    isValid = false;
                    markFieldAsInvalid(field, 'Введите корректный email');
                }
            }
            
            if (field.type === 'password' && field.value.length < 6) {
                isValid = false;
                markFieldAsInvalid(field, 'Пароль должен быть не менее 6 символов');
            }
        }
    });
    
    if (!isValid) {
        event?.preventDefault();
        showNotification('Пожалуйста, исправьте ошибки в форме', 'error');
        return false;
    }
    
    return true;
}

function markFieldAsInvalid(field, message) {
    field.style.borderColor = 'var(--error)';
    field.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
    
    // Удаляем старую подсказку
    const oldTooltip = field.parentNode.querySelector('.field-error');
    if (oldTooltip) oldTooltip.remove();
    
    // Создаем новую подсказку
    const tooltip = document.createElement('div');
    tooltip.className = 'field-error';
    tooltip.textContent = message;
    tooltip.style.cssText = `
        color: var(--error);
        font-size: 12px;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    `;
    
    const icon = document.createElement('i');
    icon.className = 'fas fa-exclamation-circle';
    tooltip.prepend(icon);
    
    field.parentNode.appendChild(tooltip);
    
    // Анимация
    field.classList.add('shake');
    setTimeout(() => {
        field.classList.remove('shake');
    }, 500);
}

function markFieldAsValid(field) {
    field.style.borderColor = '';
    field.style.boxShadow = '';
    
    const errorTooltip = field.parentNode.querySelector('.field-error');
    if (errorTooltip) errorTooltip.remove();
}

// ==================== УВЕДОМЛЕНИЯ ====================
function initNotifications() {
    // Создаем контейнер для уведомлений если его нет
    let notificationContainer = document.querySelector('.notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        notificationContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
        `;
        document.body.appendChild(notificationContainer);
    }
}

function showNotification(message, type = 'info', duration = 5000) {
    const notificationContainer = document.querySelector('.notification-container');
    if (!notificationContainer) return;
    
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
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        background: white;
        border-left: 4px solid ${config.color};
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideInRight 0.3s ease-out;
        transform: translateX(100%);
        opacity: 0;
    `;
    
    notification.innerHTML = `
        <i class="${config.icon}" style="color: ${config.color}; font-size: 20px;"></i>
        <div class="notification-content">${message}</div>
        <button class="notification-close">&times;</button>
    `;
    
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.style.cssText = `
        margin-left: auto;
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #666;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.3s;
    `;
    
    closeBtn.addEventListener('mouseenter', () => {
        closeBtn.style.backgroundColor = 'rgba(0,0,0,0.1)';
    });
    
    closeBtn.addEventListener('mouseleave', () => {
        closeBtn.style.backgroundColor = '';
    });
    
    closeBtn.addEventListener('click', () => {
        hideNotification(notification);
    });
    
    notificationContainer.appendChild(notification);
    
    // Анимация появления
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
        notification.style.opacity = '1';
    }, 10);
    
    // Автоматическое скрытие
    const autoHide = setTimeout(() => {
        hideNotification(notification);
    }, duration);
    
    // Остановить автоскрытие при наведении
    notification.addEventListener('mouseenter', () => {
        clearTimeout(autoHide);
    });
    
    notification.addEventListener('mouseleave', () => {
        setTimeout(() => {
            hideNotification(notification);
        }, 2000);
    });
}

function hideNotification(notification) {
    notification.style.transform = 'translateX(100%)';
    notification.style.opacity = '0';
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// ==================== ПОДСКАЗКИ ====================
function initTooltips() {
    // Создаем стили для тултипов
    const style = document.createElement('style');
    style.textContent = `
        .custom-tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            max-width: 250px;
            white-space: normal;
            word-wrap: break-word;
            z-index: 100000;
            pointer-events: none;
            transform: translateY(-10px);
            opacity: 0;
            transition: opacity 0.2s, transform 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .custom-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 20px;
            border-width: 5px;
            border-style: solid;
            border-color: rgba(0, 0, 0, 0.9) transparent transparent transparent;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
    
    // Обработчики для элементов с атрибутом title
    const elementsWithTitle = document.querySelectorAll('[title]');
    elementsWithTitle.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
        element.addEventListener('focus', showTooltip);
        element.addEventListener('blur', hideTooltip);
    });
}

function showTooltip(event) {
    const element = event.target;
    const title = element.getAttribute('title');
    if (!title) return;
    
    // Удаляем существующий тултип
    hideTooltip();
    
    // Создаем новый тултип
    const tooltip = document.createElement('div');
    tooltip.className = 'custom-tooltip';
    tooltip.textContent = title;
    
    document.body.appendChild(tooltip);
    
    // Позиционируем тултип
    const rect = element.getBoundingClientRect();
    let x = rect.left + rect.width / 2 - tooltip.offsetWidth / 2;
    let y = rect.top - tooltip.offsetHeight - 10;
    
    // Если тултип выходит за пределы экрана, корректируем позицию
    if (x < 10) x = 10;
    if (x + tooltip.offsetWidth > window.innerWidth - 10) {
        x = window.innerWidth - tooltip.offsetWidth - 10;
    }
    if (y < 10) {
        y = rect.bottom + 10;
        tooltip.style.transform = 'translateY(10px)';
        tooltip.style.setProperty('--arrow-position', 'top: -10px; border-color: transparent transparent rgba(0,0,0,0.9) transparent;');
    }
    
    tooltip.style.left = x + 'px';
    tooltip.style.top = y + 'px';
    
    // Анимация появления
    setTimeout(() => {
        tooltip.style.opacity = '1';
        tooltip.style.transform = 'translateY(0)';
    }, 10);
    
    // Сохраняем ссылку на тултип
    element._tooltip = tooltip;
}

function hideTooltip(event) {
    const element = event?.target || document.querySelector('[title]:hover');
    if (element && element._tooltip) {
        element._tooltip.style.opacity = '0';
        element._tooltip.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            if (element._tooltip && element._tooltip.parentNode) {
                element._tooltip.parentNode.removeChild(element._tooltip);
                delete element._tooltip;
            }
        }, 200);
    }
}

// ==================== ДОПОЛНИТЕЛЬНЫЕ ФУНКЦИИ ====================
function showWelcomeGreeting() {
    setTimeout(() => {
        const hour = new Date().getHours();
        let greeting = 'Добро пожаловать в FamPlan!';
        
        if (hour < 12) greeting = 'Доброе утро! 🌞';
        else if (hour < 18) greeting = 'Добрый день! ☀️';
        else greeting = 'Добрый вечер! 🌙';
        
        const familyQuotes = [
            "Семья - это самое важное в жизни",
            "Вместе мы можем всё!",
            "Любовь семьи - величайшее сокровище",
            "Семья - это наша крепость",
            "Счастливая семья - счастливая жизнь"
        ];
        
        const randomQuote = familyQuotes[Math.floor(Math.random() * familyQuotes.length)];
        
        showNotification(`${greeting}<br><small><em>${randomQuote}</em></small>`, 'info', 3000);
    }, 1500);
}

function updateDateTime() {
    const now = new Date();
    
    // Форматируем время
    const timeString = now.toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const dateString = now.toLocaleDateString('ru-RU', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    // Обновляем все элементы с временем
    const timeElements = document.querySelectorAll('.date-display span');
    timeElements.forEach(element => {
        element.textContent = `${dateString}, ${timeString}`;
    });
    
    // Обновляем заголовок месяца в календаре
    const monthElement = document.getElementById('currentMonth');
    if (monthElement) {
        const monthNames = [
            'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
            'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
        ];
        const monthName = monthNames[now.getMonth()];
        const year = now.getFullYear();
        monthElement.textContent = `${monthName} ${year}`;
    }
}

function initAnimations() {
    // Анимация сердечек
    const hearts = document.querySelectorAll('.fa-heart, .fa-heartbeat');
    hearts.forEach(heart => {
        setInterval(() => {
            heart.style.transform = 'scale(1.2)';
            heart.style.color = '#FF9AA2';
            setTimeout(() => {
                heart.style.transform = 'scale(1)';
                heart.style.color = '';
            }, 300);
        }, 5000 + Math.random() * 5000);
    });
    
    // Анимация карточек при загрузке
    const cards = document.querySelectorAll('.card, .event-card, .checklist-card, .family-member-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s, transform 0.5s';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

function updateDashboardStats() {
    // Обновляем статистику на главной
    const eventCount = document.querySelectorAll('.event-card').length;
    const checklistCount = document.querySelectorAll('.checklist-item').length;
    const completedCount = document.querySelectorAll('.checklist-item.checked').length;
    
    // В реальном приложении здесь был бы AJAX запрос
    console.log(`Статистика: ${eventCount} событий, ${completedCount}/${checklistCount} задач выполнено`);
}

function updateFamilyMemberStats() {
    const members = document.querySelectorAll('.family-member-card');
    members.forEach(member => {
        const stats = member.querySelector('.member-stats');
        if (stats) {
            // Обновляем случайные данные для демо
            const completed = Math.floor(Math.random() * 15) + 3;
            const events = Math.floor(Math.random() * 8) + 1;
            
            stats.innerHTML = `
                <div class="stat">
                    <i class="fas fa-check-circle"></i>
                    <span>${completed} выполнено</span>
                </div>
                <div class="stat">
                    <i class="fas fa-calendar"></i>
                    <span>${events} событий</span>
                </div>
            `;
        }
    });
}

// ==================== ГЛОБАЛЬНЫЕ ФУНКЦИИ ====================
// Экспортируем функции для использования в HTML
window.showModal = showModal;
window.closeModal = closeModal;
window.showNotification = showNotification;
window.scrollChatToBottom = scrollChatToBottom;
window.updateDateTime = updateDateTime;

// Функция для переключения пароля (используется в login форме)
window.togglePassword = function(inputId) {
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
};

// Функция для быстрого добавления события
window.quickAddEvent = function() {
    const modal = document.getElementById('addEventModal');
    if (modal) {
        showModal('addEventModal');
        
        // Автозаполняем дату сегодняшним днем
        const dateInput = modal.querySelector('input[name="event_date"]');
        if (dateInput && !dateInput.value) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        
        // Фокус на названии события
        const titleInput = modal.querySelector('input[name="title"]');
        if (titleInput) {
            setTimeout(() => titleInput.focus(), 300);
        }
    }
};

// Функция для экспорта данных семьи
window.exportFamilyData = function() {
    const data = {
        exportDate: new Date().toISOString(),
        events: [],
        checklists: [],
        familyMembers: []
    };
    
    // Собираем события
    document.querySelectorAll('.event-card').forEach(card => {
        const title = card.querySelector('h4')?.textContent;
        const date = card.querySelector('.event-day')?.textContent + ' ' + card.querySelector('.event-month')?.textContent;
        if (title) {
            data.events.push({ title, date });
        }
    });
    
    // Собираем чек-листы
    document.querySelectorAll('.checklist-item').forEach(item => {
        const text = item.querySelector('.item-text')?.textContent;
        const completed = item.classList.contains('checked');
        if (text) {
            data.checklists.push({ text, completed });
        }
    });
    
    // Собираем членов семьи
    document.querySelectorAll('.family-member-card').forEach(member => {
        const name = member.querySelector('h3')?.textContent;
        const role = member.querySelector('.member-role')?.textContent;
        if (name) {
            data.familyMembers.push({ name, role });
        }
    });
    
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
    
    showNotification('Данные успешно экспортированы!', 'success');
};

// Функция для отправки сообщения в чат
window.sendChatMessage = function(event) {
    event?.preventDefault();
    
    const form = event?.target || document.querySelector('.message-input form');
    const input = form.querySelector('input[name="message"]');
    
    if (input && input.value.trim()) {
        // В реальном приложении здесь был бы AJAX запрос
        showNotification('Сообщение отправлено!', 'success');
        input.value = '';
        input.focus();
        
        // Обновляем чат
        setTimeout(scrollChatToBottom, 100);
    }
};

// Инициализация при полной загрузке страницы
window.addEventListener('load', function() {
    console.log('✅ FamPlan полностью загружен');
    
    // Добавляем обработчик для онлайн/офлайн статуса
    window.addEventListener('online', () => {
        showNotification('Соединение восстановлено ✓', 'success');
    });
    
    window.addEventListener('offline', () => {
        showNotification('Нет соединения с интернетом', 'warning');
    });
    
    // Проверяем, используется ли темная тема
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (prefersDark) {
        document.body.classList.add('dark-theme');
    }
});
// ==================== ВОСПОМИНАНИЯ ====================
let selectedCalendarDate = null;

function initMemories() {
    const calendarDays = document.querySelectorAll('.calendar-day:not(.empty)');
    
    calendarDays.forEach(day => {
        // Заменяем обычный клик на вызов модального окна
        day.addEventListener('click', function(e) {
            e.stopPropagation();
            const date = this.getAttribute('data-date');
            if (date) {
                showMemoriesActionModal(date);
            }
        });
    });
}

function showMemoriesActionModal(date) {
    selectedCalendarDate = date;
    
    const dateObj = new Date(date);
    const formattedDate = dateObj.toLocaleDateString('ru-RU', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    const modal = document.getElementById('dayActionModal');
    const title = document.getElementById('selectedDateTitle');
    
    if (modal && title) {
        title.textContent = formattedDate;
        showModal('dayActionModal');
    }
}

function viewMemories() {
    if (selectedCalendarDate) {
        closeModal('dayActionModal');
        
        // Перенаправляем на страницу воспоминаний
        window.location.href = `memories.php?date=${selectedCalendarDate}`;
    }
}

function showUploadMemoryModal() {
    closeModal('dayActionModal');
    showModal('uploadMemoryModal');
    
    // Инициализация drag and drop
    initMemoryUpload();
}

function initMemoryUpload() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('memoryFiles');
    
    if (!uploadArea || !fileInput) return;
    
    // Обработчики drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Подсветка при drag over
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.classList.add('dragover');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.classList.remove('dragover');
        }, false);
    });
    
    // Обработка drop
    uploadArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFileSelect({ target: { files } });
    }
    
    // Клик по области загрузки
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });
}

function handleFileSelect(event) {
    const files = event.target.files;
    const preview = document.getElementById('uploadPreview');
    
    if (!preview) return;
    
    preview.innerHTML = '';
    
    for (let file of files) {
        if (!file.type.match('image.*')) continue;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-preview';
            removeBtn.innerHTML = '×';
            removeBtn.onclick = function() {
                previewItem.remove();
            };
            
            previewItem.appendChild(img);
            previewItem.appendChild(removeBtn);
            preview.appendChild(previewItem);
        };
        reader.readAsDataURL(file);
    }
}

function saveMemories() {
    const caption = document.getElementById('memoryCaption')?.value;
    const preview = document.getElementById('uploadPreview');
    const fileCount = preview?.querySelectorAll('.preview-item').length || 0;
    
    if (fileCount === 0) {
        showNotification('Пожалуйста, добавьте хотя бы одну фотографию', 'error');
        return;
    }
    
    if (!caption?.trim()) {
        showNotification('Добавьте подпись к воспоминаниям', 'error');
        return;
    }
    
    // В реальном приложении здесь был бы AJAX запрос
    showNotification('🎉 Воспоминания успешно сохранены!', 'success');
    closeModal('uploadMemoryModal');
    
    // Очищаем форму
    document.getElementById('memoryCaption').value = '';
    document.getElementById('uploadPreview').innerHTML = '';
    document.getElementById('memoryFiles').value = '';
}

// Обновляем функцию initAllModules
function initAllModules() {
    initNavigation();
    initCalendarInteractions();
    initMemories(); // Добавляем инициализацию воспоминаний
    initChecklists();
    initChat();
    initModals();
    initForms();
    initNotifications();
    initTooltips();
}

// Добавляем глобальные функции
window.viewMemories = viewMemories;
window.showUploadMemoryModal = showUploadMemoryModal;
window.saveMemories = saveMemories;
// Функция для показа уведомлений (если еще нет)
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
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    
    // Создаем уведомление
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        background: white;
        border-left: 4px solid ${getNotificationColor(type)};
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideInRight 0.3s ease-out;
        transform: translateX(100%);
        opacity: 0;
    `;
    
    notification.innerHTML = `
        <i class="${icons[type] || icons.info}" style="color: ${getNotificationColor(type)};"></i>
        <div class="notification-content">${message}</div>
        <button class="notification-close">&times;</button>
    `;
    
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    });
    
    notificationContainer.appendChild(notification);
    
    // Анимация появления
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
        notification.style.opacity = '1';
    }, 10);
    
    // Автоматическое скрытие
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

function getNotificationColor(type) {
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    return colors[type] || colors.info;
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
    document.querySelector('.mood-value').textContent = `${mood}%`;
    
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
        bar.style.backgroundColor = 'rgba(255,255,255,0.8)';
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
            <div class="timeline-content">${event.text}</div>
        </div>
    `).join('');
}

function initFunFeatures() {
    // Добавляем обработчики для креативных фишек
    console.log('Инициализация креативных фишек');
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
    
    // Создаем модальное окно с воспоминанием
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = 'memoryModal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fas fa-history"></i> Воспоминание дня</h2>
                <button class="close-modal" onclick="closeModal('memoryModal')">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center; padding: 40px;">
                <div style="font-size: 72px; color: var(--accent-coral); margin-bottom: 20px;">
                    <i class="fas fa-memory"></i>
                </div>
                <h3 style="color: var(--text-dark); margin-bottom: 20px;">О! Помните?</h3>
                <p style="font-size: 18px; color: var(--text-medium); line-height: 1.6;">
                    ${randomMemory}
                </p>
                <button class="btn-submit" onclick="closeModal('memoryModal')" style="margin-top: 30px;">
                    <i class="fas fa-heart"></i> Спасибо за воспоминание!
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function showFamilyChallenge() {
    const challenges = [
        "СЕГОДНЯШНИЙ ЧЕЛЛЕНД: Устройте семейный ужин без гаджетов! 📵",
        "ЧЕЛЛЕНД: Сделайте друг другу комплименты за завтраком! 💬",
        "ЧЕЛЛЕНД: Вместе приготовьте новое блюдо! 👨‍🍳",
        "ЧЕЛЛЕНД: Прогуляйтесь вместе после ужина! 🚶‍♂️🚶‍♀️"
    ];
    
    const randomChallenge = challenges[Math.floor(Math.random() * challenges.length)];
    
    // Показываем в виде уведомления с кнопкой принятия
    const notification = document.createElement('div');
    notification.className = 'notification-global info';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 20px;
        z-index: 10000;
        max-width: 400px;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="font-size: 32px; color: white;">
                <i class="fas fa-medal"></i>
            </div>
            <div style="flex: 1;">
                <h4 style="margin: 0 0 10px 0; color: white;">Семейный челлендж дня! 🏆</h4>
                <p style="margin: 0; color: white; opacity: 0.9;">${randomChallenge}</p>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button onclick="acceptChallenge(this)" style="padding: 8px 20px; background: white; color: var(--accent-blue); border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">
                        Принимаю!
                    </button>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" style="padding: 8px 20px; background: transparent; color: white; border: 1px solid white; border-radius: 8px; cursor: pointer;">
                        Позже
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
}

function acceptChallenge(button) {
    button.innerHTML = '<i class="fas fa-check"></i> Принято!';
    button.style.background = '#4CAF50';
    button.style.color = 'white';
    button.disabled = true;
    
    setTimeout(() => {
        button.parentElement.parentElement.parentElement.remove();
        showNotification('🎉 Челлендж принят! Удачи в выполнении!', 'success');
    }, 2000);
}

function generateFamilyRecipe() {
    const recipes = [
        { name: "Семейная пицца 🍕", desc: "Каждый делает свою часть!" },
        { name: "Домашние пельмени 🥟", desc: "Лепим вместе всей семьей" },
        { name: "Шоколадные маффины 🧁", desc: "Секретный рецепт бабушки" },
        { name: "Суп-пюре из тыквы 🎃", desc: "Согревающий осенний суп" }
    ];
    
    const recipe = recipes[Math.floor(Math.random() * recipes.length)];
    
    showNotification(`<strong>${recipe.name}</strong><br>${recipe.desc}`, 'info', 5000);
}

function showCompatibilityTest() {
    // Простой тест совместимости
    const questions = [
        "Любите ли вы вместе смотреть фильмы?",
        "Часто ли вы смеетесь вместе?",
        "Поддерживаете ли вы друг друга в трудную минуту?"
    ];
    
    let score = 0;
    questions.forEach(() => {
        if (Math.random() > 0.3) score++;
    });
    
    const compatibility = Math.floor((score / questions.length) * 100);
    
    // Создаем результат
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = 'compatibilityModal';
    modal.style.display = 'flex';
    
    let resultText = '';
    let resultEmoji = '';
    
    if (compatibility >= 80) {
        resultText = 'Идеальная совместимость! Вы отлично подходите друг другу!';
        resultEmoji = '💖';
    } else if (compatibility >= 60) {
        resultText = 'Хорошая совместимость! Есть над чем работать, но в целом отлично!';
        resultEmoji = '👍';
    } else {
        resultText = 'Есть куда расти! Попробуйте больше времени проводить вместе.';
        resultEmoji = '🤝';
    }
    
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fas fa-heart"></i> Тест совместимости</h2>
                <button class="close-modal" onclick="closeModal('compatibilityModal')">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center; padding: 40px;">
                <div style="font-size: 72px; margin-bottom: 20px;">${resultEmoji}</div>
                <div style="font-size: 48px; font-weight: 800; color: var(--accent-coral); margin-bottom: 20px;">
                    ${compatibility}%
                </div>
                <h3 style="color: var(--text-dark); margin-bottom: 20px;">${resultText}</h3>
                <div style="background: var(--primary-beige); padding: 20px; border-radius: 12px; margin-top: 20px;">
                    <p style="color: var(--text-medium); margin: 0;">
                        <i class="fas fa-lightbulb"></i> Совет: Попробуйте совместное хобби!
                    </p>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function showFuturePrediction() {
    const predictions = [
        { emoji: '🌟', text: 'На этой неделе вас ждет приятный сюрприз!' },
        { emoji: '🎉', text: 'Выходные будут полны веселья и смеха!' },
        { emoji: '📚', text: 'Кто-то из семьи достигнет успеха в учебе!' },
        { emoji: '🍕', text: 'Вас ждет вкусный семейный ужин!' },
        { emoji: '🎁', text: 'Получите неожиданный подарок!' }
    ];
    
    const prediction = predictions[Math.floor(Math.random() * predictions.length)];
    
    // Создаем красивую карточку предсказания
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = 'predictionModal';
    modal.style.display = 'flex';
    
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 500px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="modal-header" style="border-bottom-color: rgba(255,255,255,0.2);">
                <h2 style="color: white;"><i class="fas fa-crystal-ball"></i> Прогноз на неделю</h2>
                <button class="close-modal" style="color: white;" onclick="closeModal('predictionModal')">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center; padding: 40px;">
                <div style="font-size: 72px; margin-bottom: 20px; animation: pulse 2s infinite;">
                    ${prediction.emoji}
                </div>
                <h3 style="margin-bottom: 20px; font-size: 24px;">Магический шар говорит...</h3>
                <div style="font-size: 20px; line-height: 1.6; margin-bottom: 30px; opacity: 0.9;">
                    "${prediction.text}"
                </div>
                <div style="display: flex; justify-content: center; gap: 20px; margin-top: 30px;">
                    <button onclick="closeModal('predictionModal')" style="padding: 12px 30px; background: white; color: #667eea; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">
                        Спасибо!
                    </button>
                    <button onclick="showFuturePrediction()" style="padding: 12px 30px; background: transparent; color: white; border: 1px solid white; border-radius: 8px; cursor: pointer;">
                        Еще предсказание
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
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

// Обновляем функцию handleSectionChange
function handleSectionChange(sectionId) {
    switch(sectionId) {
        case 'calendar':
            highlightTodayInCalendar();
            break;
        case 'checklists':
            updateChecklistProgress();
            break;
        case 'family':
            updateFamilyMemberStats();
            break;
        case 'data':
            initDataSection();
            break;
        case 'chat':
            scrollChatToBottom();
            focusChatInput();
            loadChatMessages();
            break;
    }
}

// Обновляем функцию initAllModules
function initAllModules() {
    initNavigation();
    initCalendarInteractions();
    initChecklists();
    initChat();
    initModals();
    initForms();
    initNotifications();
    initTooltips();
    // Загружаем настройки статистики
    loadStatsSettings();
}
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
        day.addEventListener('click', function(e) {
            e.stopPropagation();
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
    const logoImages = document.querySelectorAll('.logo-image');
    logoImages.forEach(logoImage => {
        logoImage.addEventListener('click', function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Обновляем все логотипы на странице
                        logoImages.forEach(logo => {
                            logo.innerHTML = `<img src="${e.target.result}" alt="Логотип FamPlan" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                        });
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
    });
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

// Функция для показа уведомлений
function showNotification(message, type = 'info', duration = 3000) {
    let notificationContainer = document.querySelector('.notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        notificationContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
        `;
        document.body.appendChild(notificationContainer);
    }
    
    const icons = {
        success: { icon: 'fas fa-check-circle', color: '#28a745' },
        error: { icon: 'fas fa-exclamation-circle', color: '#dc3545' },
        warning: { icon: 'fas fa-exclamation-triangle', color: '#ffc107' },
        info: { icon: 'fas fa-info-circle', color: '#17a2b8' }
    };
    
    const config = icons[type] || icons.info;
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        background: white;
        border-left: 4px solid ${config.color};
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.3s ease-out;
        transform: translateX(100%);
        opacity: 0;
    `;
    
    notification.innerHTML = `
        <i class="${config.icon}" style="color: ${config.color}; font-size: 20px;"></i>
        <div class="notification-content">${message}</div>
        <button class="notification-close">&times;</button>
    `;
    
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.style.cssText = `
        margin-left: auto;
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #666;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.3s;
    `;
    
    closeBtn.addEventListener('click', () => {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    });
    
    notificationContainer.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
        notification.style.opacity = '1';
    }, 10);
    
    const autoHide = setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, duration);
    
    notification.addEventListener('mouseenter', () => {
        clearTimeout(autoHide);
    });
    
    notification.addEventListener('mouseleave', () => {
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 2000);
    });
}// ==================== ДАННЫЕ И СТАТИСТИКА ====================

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
    document.querySelector('.mood-value').textContent = `${mood}%`;
    
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
        bar.style.backgroundColor = 'rgba(255,255,255,0.8)';
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
            <div class="timeline-content">${event.text}</div>
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
    
    // Очищаем канвас
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
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

// ==================== ИНИЦИАЛИЗАЦИЯ ====================

// Обновляем функцию handleSectionChange
function handleSectionChange(sectionId) {
    switch(sectionId) {
        case 'calendar':
            highlightTodayInCalendar();
            break;
        case 'checklists':
            updateChecklistProgress();
            break;
        case 'data':
            initDataSection();
            break;
        case 'chat':
            scrollChatToBottom();
            focusChatInput();
            loadChatMessages();
            break;
    }
}

// Обновляем функцию initAllModules
function initAllModules() {
    initNavigation();
    initCalendarInteractions();
    initChecklists();
    initChat();
    initModals();
    initForms();
    initNotifications();
    initTooltips();
    // Загружаем настройки статистики
    loadStatsSettings();
}