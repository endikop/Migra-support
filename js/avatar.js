// JavaScript решение для отображения аватара на всех страницах
// Просто добавьте этот скрипт в конец каждой страницы

document.addEventListener('DOMContentLoaded', function() {
    // Находим все элементы с классом user-avatar
    const avatarElements = document.querySelectorAll('.user-avatar');
    
    if (avatarElements.length > 0) {
        // Отправляем запрос для получения аватара пользователя
        fetch('get_user_avatar.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.avatar) {
                    // Обновляем все аватары на странице
                    avatarElements.forEach(avatarElement => {
                        // Проверяем, не содержит ли уже аватар изображение
                        if (!avatarElement.querySelector('img')) {
                            const userName = avatarElement.getAttribute('data-user-name') || 
                                           avatarElement.title || 
                                           avatarElement.textContent.trim();
                            
                            const img = document.createElement('img');
                            img.src = data.avatar;
                            img.alt = userName;
                            img.style.width = '100%';
                            img.style.height = '100%';
                            img.style.borderRadius = '50%';
                            img.style.objectFit = 'cover';
                            
                            // Очищаем содержимое и добавляем изображение
                            avatarElement.innerHTML = '';
                            avatarElement.appendChild(img);
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Ошибка при получении аватара:', error);
            });
    }
});