/**
 * ОПТИМИЗИРОВАННЫЙ скрипт анимаций (БЕЗ ЛАГОВ)
 * Минимальная нагрузка на производительность
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Простая анимация загрузки страницы
    document.body.style.opacity = '1';
    
    // Плавная прокрутка к якорям
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '#!') {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    console.log('✨ Анимации загружены (оптимизированная версия)');
});
