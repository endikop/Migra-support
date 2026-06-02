<?php
/**
 * ОПТИМИЗИРОВАННЫЙ файл для подключения анимаций
 * БЕЗ затемнения экрана - только анимация навигации
 */
?>

<!-- Оптимизированные анимации -->
<link rel="stylesheet" href="css/animations.css">
<script src="js/animations.js" defer></script>

<style>
/* УБРАНО затемнение body при загрузке */
/* Страница сразу видна */

/* Простые hover эффекты */
a {
    transition: color 0.2s ease;
}

/* Плавные переходы для форм */
input, select, textarea {
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: #4a90e2;
}

/* Оптимизация производительности */
* {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Отключение анимаций на слабых устройствах */
@media (prefers-reduced-motion: reduce) {
    * {
        animation: none !important;
        transition: none !important;
    }
}
</style>