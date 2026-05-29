/**
 * СОВРЕМЕННЫЙ UI СКРИПТ
 * Универсальная интерактивность для всех страниц
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // === ИНИЦИАЛИЗАЦИЯ ===
    initDropdowns();
    initMobileMenu();
    initTabs();
    initAccordion();
    initToasts();
    initModals();
    initSmoothScroll();
    initAnimations();
    
    console.log('✨ Современный UI загружен');
    
    // === DROPDOWN МЕНЮ ===
    function initDropdowns() {
        document.querySelectorAll('[data-dropdown-toggle]').forEach(toggle => {
            const targetId = toggle.getAttribute('data-dropdown-toggle');
            const dropdown = document.getElementById(targetId);
            
            if (!dropdown) return;
            
            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                
                // Закрыть другие dropdown
                document.querySelectorAll('.lang-dropdown, .user-dropdown').forEach(d => {
                    if (d !== dropdown) d.classList.remove('show');
                });
                
                dropdown.classList.toggle('show');
            });
        });
        
        // Закрытие при клике вне
        document.addEventListener('click', () => {
            document.querySelectorAll('.lang-dropdown, .user-dropdown').forEach(d => {
                d.classList.remove('show');
            });
        });
    }
    
    // === МОБИЛЬНОЕ МЕНЮ ===
    function initMobileMenu() {
        const toggle = document.querySelector('.mobile-menu-toggle');
        const menu = document.querySelector('.mobile-menu');
        
        if (!toggle || !menu) return;
        
        toggle.addEventListener('click', () => {
            toggle.classList.toggle('active');
            menu.classList.toggle('show');
            document.body.style.overflow = menu.classList.contains('show') ? 'hidden' : '';
        });
        
        // Закрытие при клике на ссылку
        menu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                toggle.classList.remove('active');
                menu.classList.remove('show');
                document.body.style.overflow = '';
            });
        });
    }
    
    // === ТАБЫ ===
    function initTabs() {
        document.querySelectorAll('[data-tab]').forEach(tab => {
            tab.addEventListener('click', () => {
                const targetId = tab.getAttribute('data-tab');
                const container = tab.closest('[data-tabs-container]');
                
                if (!container) return;
                
                // Убрать активные классы
                container.querySelectorAll('[data-tab]').forEach(t => t.classList.remove('active'));
                container.querySelectorAll('[data-tab-content]').forEach(c => c.classList.remove('active'));
                
                // Добавить активные классы
                tab.classList.add('active');
                const content = container.querySelector(`[data-tab-content="${targetId}"]`);
                if (content) content.classList.add('active');
            });
        });
    }
    
    // === АККОРДЕОН ===
    function initAccordion() {
        document.querySelectorAll('.accordion-header').forEach(header => {
            header.addEventListener('click', () => {
                const item = header.closest('.accordion-item');
                const accordion = item.closest('.accordion');
                
                // Закрыть другие элементы (если нужно)
                if (!accordion.hasAttribute('data-multiple')) {
                    accordion.querySelectorAll('.accordion-item').forEach(i => {
                        if (i !== item) i.classList.remove('active');
                    });
                }
                
                item.classList.toggle('active');
            });
        });
    }
    
    // === ТОСТЫ ===
    function initToasts() {
        // Автоматическое закрытие тостов
        document.querySelectorAll('.toast').forEach(toast => {
            const duration = parseInt(toast.getAttribute('data-duration')) || 5000;
            
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, duration);
            
            // Закрытие по клику
            const closeBtn = toast.querySelector('.toast-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    toast.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => toast.remove(), 300);
                });
            }
        });
    }
    
    // === МОДАЛЬНЫЕ ОКНА ===
    function initModals() {
        // Открытие модальных окон
        document.querySelectorAll('[data-modal-open]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.getAttribute('data-modal-open');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('show');
                    document.body.style.overflow = 'hidden';
                }
            });
        });
        
        // Закрытие модальных окон
        document.querySelectorAll('[data-modal-close]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.modal-overlay');
                if (modal) {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Закрытие при клике на overlay
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Закрытие по ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.show').forEach(modal => {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                });
            }
        });
    }
    
    // === ПЛАВНАЯ ПРОКРУТКА ===
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '#!') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    const headerHeight = document.querySelector('.modern-header')?.offsetHeight || 70;
                    const targetPosition = target.offsetTop - headerHeight - 20;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }
    
    // === АНИМАЦИИ ПРИ СКРОЛЛЕ ===
    function initAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });
    }
});

// === УТИЛИТЫ ===

/**
 * Показать тост уведомление
 * @param {string} message - Текст сообщения
 * @param {string} type - Тип: success, error, warning, info
 * @param {number} duration - Длительность в мс
 */
function showToast(message, type = 'info', duration = 5000) {
    const container = document.querySelector('.toast-container') || createToastContainer();
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.setAttribute('data-duration', duration);
    toast.innerHTML = `
        <i class="fas ${icons[type]} toast-icon"></i>
        <div class="toast-content">
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close"><i class="fas fa-times"></i></button>
    `;
    
    container.appendChild(toast);
    
    // Автоматическое удаление
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
    
    // Закрытие по клику
    toast.querySelector('.toast-close').addEventListener('click', () => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

/**
 * Показать загрузчик
 */
function showLoader() {
    const loader = document.createElement('div');
    loader.className = 'loader-overlay';
    loader.id = 'global-loader';
    loader.innerHTML = '<div class="loader"></div>';
    document.body.appendChild(loader);
    document.body.style.overflow = 'hidden';
}

/**
 * Скрыть загрузчик
 */
function hideLoader() {
    const loader = document.getElementById('global-loader');
    if (loader) {
        loader.remove();
        document.body.style.overflow = '';
    }
}

/**
 * Подтверждение действия
 * @param {string} message - Текст подтверждения
 * @returns {Promise<boolean>}
 */
function confirmAction(message) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay show';
        overlay.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Подтверждение</h3>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-ghost" data-action="cancel">Отмена</button>
                    <button class="btn btn-primary" data-action="confirm">Подтвердить</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
        
        overlay.querySelector('[data-action="confirm"]').addEventListener('click', () => {
            overlay.remove();
            document.body.style.overflow = '';
            resolve(true);
        });
        
        overlay.querySelector('[data-action="cancel"]').addEventListener('click', () => {
            overlay.remove();
            document.body.style.overflow = '';
            resolve(false);
        });
        
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
                document.body.style.overflow = '';
                resolve(false);
            }
        });
    });
}

// === АНИМАЦИЯ SLIDEOUTRIGHT ===
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
`;
document.head.appendChild(style);
