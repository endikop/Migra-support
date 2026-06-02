/* mobile-responsive.js - Единый JavaScript для мобильной адаптивности */

document.addEventListener('DOMContentLoaded', function() {
    // ==========================================================================
    // ИНИЦИАЛИЗАЦИЯ МОБИЛЬНОЙ НАВИГАЦИИ
    // ==========================================================================
    
    // Создаем мобильную навигацию, если она не существует
    function initMobileNavigation() {
        const header = document.querySelector('header');
        if (!header) return;
        
        // Проверяем, существует ли уже мобильная навигация
        let mobileNav = document.querySelector('.mobile-nav');
        if (!mobileNav) {
            mobileNav = document.createElement('div');
            mobileNav.className = 'mobile-nav';
            mobileNav.innerHTML = '<ul class="mobile-nav-tabs"></ul>';
            header.appendChild(mobileNav);
        }
        
        // Находим основную навигацию и копируем пункты меню
        const navTabs = document.querySelector('.nav-tabs');
        if (!navTabs) return;
        
        const mobileNavTabs = mobileNav.querySelector('.mobile-nav-tabs');
        mobileNavTabs.innerHTML = '';
        
        // Копируем каждый пункт меню
        navTabs.querySelectorAll('.nav-tab').forEach((tab, index) => {
            const mobileTab = document.createElement('li');
            mobileTab.className = 'mobile-nav-tab';
            
            // Копируем иконку и текст
            const icon = tab.querySelector('i');
            const text = tab.textContent.trim();
            
            mobileTab.innerHTML = `
                <a href="#" class="mobile-nav-link" data-index="${index}">
                    ${icon ? icon.outerHTML : '<i class="fas fa-circle"></i>'}
                    <span>${text}</span>
                </a>
            `;
            
            mobileNavTabs.appendChild(mobileTab);
        });
        
        // Добавляем ссылки навигации
        const navItems = [
            { icon: 'fas fa-home', text: 'Главная', url: 'index.php' },
            { icon: 'fas fa-info-circle', text: 'Информация', url: 'information.php' },
            { icon: 'fas fa-map-marker-alt', text: 'Карта служб', url: 'map.php' },
            { icon: 'fas fa-language', text: 'Переводчик', url: 'translator.php' },
            { icon: 'fas fa-exchange-alt', text: 'Конвертер валют', url: 'converter.php' },
            { icon: 'fas fa-comments', text: 'Чат города', url: 'chat.php' },
            { icon: 'fas fa-user-circle', text: 'Профиль', url: 'profile.php' },
            { icon: 'fas fa-newspaper', text: 'Новости', url: 'news_simple.php' }
        ];
        
        // Обновляем ссылки
        mobileNavTabs.querySelectorAll('.mobile-nav-link').forEach((link, index) => {
            const item = navItems[index];
            if (item) {
                link.href = item.url;
                if (link.querySelector('i')) {
                    link.querySelector('i').className = item.icon;
                }
            }
            
            // Пометка активной страницы
            const currentPage = window.location.pathname.split('/').pop();
            if (item && item.url === currentPage) {
                link.closest('.mobile-nav-tab').classList.add('active');
            }
        });
    }
    
    // ==========================================================================
    // БУРГЕР МЕНЮ - КЛИК
    // ==========================================================================
    
    function initBurgerMenu() {
        const burger = document.querySelector('.burger-menu');
        const mobileNav = document.querySelector('.mobile-nav');
        
        if (!burger || !mobileNav) return;
        
        burger.addEventListener('click', function(e) {
            e.stopPropagation();
            burger.classList.toggle('active');
            mobileNav.classList.toggle('active');
            
            // Блокируем скролл страницы при открытой мобильной навигации
            if (mobileNav.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
        
        // Закрытие мобильной навигации при клике вне ее
        document.addEventListener('click', function(e) {
            if (!mobileNav.contains(e.target) && !burger.contains(e.target)) {
                burger.classList.remove('active');
                mobileNav.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        // Закрытие при клике на ссылку
        mobileNav.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                setTimeout(() => {
                    burger.classList.remove('active');
                    mobileNav.classList.remove('active');
                    document.body.style.overflow = '';
                }, 300);
            }
        });
        
        // Фикс для IOS - обработка touch событий
        burger.addEventListener('touchstart', function(e) {
            e.preventDefault();
            burger.classList.toggle('active');
            mobileNav.classList.toggle('active');
            
            if (mobileNav.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
    }
    
    // ==========================================================================
    // ФИКСЫ ДЛЯ МОБИЛЬНЫХ УСТРОЙСТВ
    // ==========================================================================
    
    function applyMobileFixes() {
        // Фикс для IOS 100vh
        function fixVhOnMobile() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        
        fixVhOnMobile();
        window.addEventListener('resize', fixVhOnMobile);
        
        // Фикс для мобильного скролла
        document.body.addEventListener('touchmove', function(e) {
            if (document.querySelector('.mobile-nav.active')) {
                e.preventDefault();
            }
        }, { passive: false });
        
        // Фикс для масштабирования на мобильных
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(e) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    }
    
    // ==========================================================================
    // АДАПТИВНЫЕ ИЗОБРАЖЕНИЯ
    // ==========================================================================
    
    function optimizeImagesForMobile() {
        const images = document.querySelectorAll('img');
        const isMobile = window.innerWidth <= 768;
        
        images.forEach(img => {
            // Добавляем loading="lazy" для мобильных устройств
            if (isMobile && !img.hasAttribute('loading')) {
                img.setAttribute('loading', 'lazy');
            }
            
            // Добавляем альтернативный текст если его нет
            if (!img.alt && !img.hasAttribute('role')) {
                img.alt = '';
            }
        });
    }
    
    // ==========================================================================
    // АДАПТИВНЫЕ КНОПКИ И ИНПУТЫ
    // ==========================================================================
    
    function enhanceMobileInputs() {
        // Увеличиваем область клика для кнопок на мобильных
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.btn, .lang-btn, .mobile-nav-tab').forEach(el => {
                el.style.minHeight = '44px';
                el.style.minWidth = '44px';
                el.style.display = 'flex';
                el.style.alignItems = 'center';
                el.style.justifyContent = 'center';
            });
        }
        
        // Улучшаем поля ввода для мобильных
        document.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('focus', function() {
                if (window.innerWidth <= 768) {
                    // Прокручиваем к полю ввода при фокусе на мобильных
                    setTimeout(() => {
                        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }
            });
        });
    }
    
    // ==========================================================================
    // ПАНЕЛЬ ПЕРЕКЛЮЧЕНИЯ ЯЗЫКОВ - ЕДИНЫЙ ОБРАБОТЧИК
    // ==========================================================================
    
    function initLanguageSelector() {
        const langButtons = document.querySelectorAll('.lang-btn');
        
        langButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const lang = this.textContent.trim().toLowerCase();
                
                // Устанавливаем активный класс
                langButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Изменяем язык
                changeLanguage(lang);
            });
        });
    }
    
    function changeLanguage(lang) {
        const supportedLangs = ['ru', 'en', 'pt', 'fr', 'de'];
        if (!supportedLangs.includes(lang)) return;
        
        // Сохраняем язык в куки
        document.cookie = `lang=${lang}; path=/; max-age=${30 * 24 * 60 * 60}`;
        
        // Обновляем атрибут lang у html
        document.documentElement.lang = lang;
        
        // Обновляем текст кнопок языка
        updateLanguageButtons(lang);
        
        // Перезагружаем страницу для применения переводов
        const url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        window.location.href = url.toString();
    }
    
    function updateLanguageButtons(activeLang) {
        const langMap = {
            'ru': 'RU', 'en': 'EN', 'pt': 'PT', 'fr': 'FR', 'de': 'DE'
        };
        
        document.querySelectorAll('.lang-btn').forEach(btn => {
            const btnText = btn.textContent.trim().toLowerCase();
            const langKey = Object.keys(langMap).find(key => 
                btnText === langMap[key].toLowerCase()
            );
            
            if (langKey) {
                btn.textContent = langMap[langKey];
                btn.classList.toggle('active', langKey === activeLang);
            }
        });
    }
    
    // ==========================================================================
    // ОТСЛЕЖИВАНИЕ ОРИЕНТАЦИИ ЭКРАНА
    // ==========================================================================
    
    function handleOrientationChange() {
        const isPortrait = window.innerHeight > window.innerWidth;
        
        if (isPortrait) {
            // Вертикальная ориентация
            document.body.classList.add('portrait');
            document.body.classList.remove('landscape');
        } else {
            // Горизонтальная ориентация
            document.body.classList.add('landscape');
            document.body.classList.remove('portrait');
        }
        
        // Обновляем адаптивные стили
        updateResponsiveClasses();
    }
    
    // ==========================================================================
    // ОБНОВЛЕНИЕ АДАПТИВНЫХ КЛАССОВ
    // ==========================================================================
    
    function updateResponsiveClasses() {
        const width = window.innerWidth;
        const body = document.body;
        
        // Удаляем старые классы
        body.classList.remove('mobile-xs', 'mobile-sm', 'mobile-md', 'tablet', 'desktop');
        
        // Добавляем новые классы
        if (width < 400) {
            body.classList.add('mobile-xs');
        } else if (width < 576) {
            body.classList.add('mobile-sm');
        } else if (width < 768) {
            body.classList.add('mobile-md');
        } else if (width < 992) {
            body.classList.add('tablet');
        } else {
            body.classList.add('desktop');
        }
    }
    
    // ==========================================================================
    // ЛАЗИЛЬНАЯ ЗАГРУЗКА ИЗОБРАЖЕНИЙ
    // ==========================================================================
    
    function lazyLoadImages() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                        }
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }
    
    // ==========================================================================
    // ОБРАБОТЧИК СВАЙПОВ ДЛЯ МОБИЛЬНЫХ
    // ==========================================================================
    
    function initSwipeDetection() {
        let touchStartX = 0;
        let touchEndX = 0;
        let touchStartY = 0;
        let touchEndY = 0;
        
        document.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        });
        
        document.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            touchEndY = e.changedTouches[0].screenY;
            handleSwipe();
        });
        
        function handleSwipe() {
            const deltaX = touchEndX - touchStartX;
            const deltaY = touchEndY - touchStartY;
            
            // Определяем горизонтальный свайп
            if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 50) {
                if (deltaX > 0) {
                    // Свайп вправо - открываем мобильное меню
                    const burger = document.querySelector('.burger-menu');
                    if (burger && !burger.classList.contains('active')) {
                        burger.click();
                    }
                } else {
                    // Свайп влево - закрываем мобильное меню
                    const burger = document.querySelector('.burger-menu');
                    if (burger && burger.classList.contains('active')) {
                        burger.click();
                    }
                }
            }
        }
    }
    
    // ==========================================================================
    // АДАПТИВНЫЙ ТАЙМЕР ОБНОВЛЕНИЯ
    // ==========================================================================
    
    function initAdaptiveRefresh() {
        const isMobile = window.innerWidth <= 768;
        const refreshInterval = isMobile ? 30000 : 60000; // 30 сек на мобильных, 60 сек на десктопе
        
        // Пример: автообновление данных (можно адаптировать под конкретные страницы)
        if (window.location.pathname.includes('chat.php')) {
            setInterval(() => {
                if (!document.hidden) {
                    // Здесь код обновления чата
                    console.log('Автообновление для мобильных устройств');
                }
            }, refreshInterval);
        }
    }
    
    // ==========================================================================
    // ИНИЦИАЛИЗАЦИЯ ВСЕХ ФУНКЦИЙ
    // ==========================================================================
    
    function initAll() {
        initMobileNavigation();
        initBurgerMenu();
        applyMobileFixes();
        optimizeImagesForMobile();
        enhanceMobileInputs();
        initLanguageSelector();
        handleOrientationChange();
        updateResponsiveClasses();
        lazyLoadImages();
        initSwipeDetection();
        initAdaptiveRefresh();
        
        // Обновляем при изменении размера окна
        window.addEventListener('resize', function() {
            handleOrientationChange();
            updateResponsiveClasses();
            optimizeImagesForMobile();
            enhanceMobileInputs();
        });
        
        // Обновляем при изменении ориентации
        window.addEventListener('orientationchange', function() {
            setTimeout(() => {
                handleOrientationChange();
                updateResponsiveClasses();
            }, 100);
        });
        
        // Добавляем класс загрузки для плавного отображения
        setTimeout(() => {
            document.body.classList.add('mobile-ready');
        }, 100);
    }
    
    // Запускаем инициализацию
    initAll();
    
    // ==========================================================================
    // ГЛОБАЛЬНЫЕ ФУНКЦИИ ДЛЯ ДОСТУПА ИЗ ВНЕ
    // ==========================================================================
    
    window.MobileResponsive = {
        initMobileNavigation,
        initBurgerMenu,
        changeLanguage,
        updateResponsiveClasses,
        toggleMobileNav: function() {
            const burger = document.querySelector('.burger-menu');
            if (burger) burger.click();
        },
        isMobile: function() {
            return window.innerWidth <= 768;
        },
        isTablet: function() {
            return window.innerWidth <= 992 && window.innerWidth > 768;
        },
        getCurrentBreakpoint: function() {
            const width = window.innerWidth;
            if (width < 400) return 'xs';
            if (width < 576) return 'sm';
            if (width < 768) return 'md';
            if (width < 992) return 'tablet';
            return 'desktop';
        }
    };
    
    // Экспортируем для использования в консоли
    console.log('Mobile Responsive Module Loaded', window.MobileResponsive);
});

// Фоллбэк для старых браузеров
if (!('IntersectionObserver' in window)) {
    console.warn('IntersectionObserver не поддерживается, ленивая загрузка отключена');
    
    // Простой фоллбэк для ленивой загрузки
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img[data-src]');
        images.forEach(img => {
            img.src = img.dataset.src;
            if (img.dataset.srcset) {
                img.srcset = img.dataset.srcset;
            }
        });
    });
}