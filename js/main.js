// js/main.js - Основные JavaScript функции для MigraSupport

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    initializeBurgerMenu();
    initializeProfileDropdown();
    initializeFadeInElements();
    initializeLanguageSelector();
    
    // Проверяем наличие сообщений об успехе/ошибке
    checkForNotifications();
    
    // Инициализация дополнительных функций
    initializeCitySelector();
    initializeTabs();
    initializeCurrencyConverter();
    initializeTranslator();
    setupEventListeners();
    
    // Загрузка данных, если пользователь авторизован
    if (typeof window.isLoggedIn !== 'undefined' && window.isLoggedIn) {
        loadAdminChatMessages();
        setupChatAutoRefresh();
    }
});

// Инициализация бургер-меню
function initializeBurgerMenu() {
    const burgerMenu = document.getElementById('burgerMenu');
    const mobileNav = document.getElementById('mobileNav');
    
    if (burgerMenu && mobileNav) {
        burgerMenu.addEventListener('click', function() {
            this.classList.toggle('active');
            mobileNav.classList.toggle('active');
        });
        
        document.addEventListener('click', function(event) {
            if (!burgerMenu.contains(event.target) && !mobileNav.contains(event.target)) {
                burgerMenu.classList.remove('active');
                mobileNav.classList.remove('active');
            }
        });
    }
}

// Инициализация выпадающего меню профиля
function initializeProfileDropdown() {
    const profileAvatar = document.getElementById('profileAvatar');
    const dropdownMenu = document.getElementById('profileDropdown');
    
    if (profileAvatar && dropdownMenu) {
        profileAvatar.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (!profileAvatar.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
        
        dropdownMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
}

// Инициализация элементов с анимацией появления при скролле
function initializeFadeInElements() {
    const fadeElements = document.querySelectorAll('.fade-in-element');
    
    if (fadeElements.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });
        
        fadeElements.forEach(element => {
            observer.observe(element);
        });
    }
}

// Инициализация выбора языка
function initializeLanguageSelector() {
    const langButtons = document.querySelectorAll('.lang-btn');
    
    langButtons.forEach(button => {
        button.addEventListener('click', function() {
            const lang = this.textContent.trim();
            changeLanguage(lang.toLowerCase());
        });
    });
}

// Функция смены языка
function changeLanguage(lang) {
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
}

// Проверка и отображение уведомлений
function checkForNotifications() {
    const notifications = document.querySelectorAll('.notification');
    
    if (notifications.length > 0) {
        notifications.forEach(notification => {
            // Автоматическое скрытие через 5 секунд
            if (!notification.classList.contains('error')) {
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 500);
                }, 5000);
            }
            
            // Добавляем кнопку закрытия
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '<i class="fas fa-times"></i>';
            closeBtn.style.cssText = `
                background: none;
                border: none;
                color: inherit;
                cursor: pointer;
                font-size: 0.9rem;
                margin-left: auto;
                padding: 5px;
                border-radius: 4px;
                transition: all 0.3s ease;
            `;
            
            closeBtn.addEventListener('mouseover', () => {
                closeBtn.style.background = 'rgba(255, 255, 255, 0.2)';
            });
            
            closeBtn.addEventListener('mouseout', () => {
                closeBtn.style.background = 'none';
            });
            
            closeBtn.addEventListener('click', () => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 500);
            });
            
            notification.appendChild(closeBtn);
        });
    }
}

// Инициализация выбора города
function initializeCitySelector() {
    document.querySelectorAll('.city-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const city = this.getAttribute('data-city');
            window.location.href = `index.php?city=${city}&lang=${getCurrentLanguage()}#home`;
        });
    });
}

// Получение текущего языка
function getCurrentLanguage() {
    return document.documentElement.lang || 'ru';
}

// Инициализация переключения вкладок
function initializeTabs() {
    const desktopTabs = document.querySelectorAll('.nav-tab');
    const mobileTabs = document.querySelectorAll('.mobile-nav-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    function activateTab(tabElement) {
        // Для десктопных вкладок
        desktopTabs.forEach(t => t.classList.remove('active'));
        if (tabElement.classList.contains('nav-tab')) {
            const desktopTab = Array.from(desktopTabs).find(t => 
                t.getAttribute('data-tab') === tabElement.getAttribute('data-tab')
            );
            if (desktopTab) desktopTab.classList.add('active');
        }
        
        // Для мобильных вкладок
        mobileTabs.forEach(t => t.classList.remove('active'));
        if (tabElement.classList.contains('mobile-nav-tab')) {
            const mobileTab = Array.from(mobileTabs).find(t => 
                t.getAttribute('data-tab') === tabElement.getAttribute('data-tab')
            );
            if (mobileTab) mobileTab.classList.add('active');
        }
        
        // Активация контента
        tabContents.forEach(c => c.classList.remove('active'));
        const tabId = tabElement.getAttribute('data-tab');
        const tabContent = document.getElementById(tabId);
        if (tabContent) {
            tabContent.classList.add('active');
            
            // Прокрутка к верху при переключении вкладок
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Закрытие dropdown при переключении вкладок
        const dropdownMenu = document.getElementById('profileDropdown');
        if (dropdownMenu) {
            dropdownMenu.classList.remove('show');
        }
        
        if (tabId === 'profile') {
            loadAdminChatMessages();
        }
        
        // Закрываем мобильную навигацию
        toggleMobileNav();
        
        // Инициализация специфичных для вкладки функций
        initializeTabSpecificFunctions(tabId);
    }
    
    desktopTabs.forEach(tab => {
        tab.addEventListener('click', () => activateTab(tab));
    });
    
    mobileTabs.forEach(tab => {
        tab.addEventListener('click', () => activateTab(tab));
    });
}

// Показать вкладку
function showTab(tabName) {
    const tab = document.querySelector(`.nav-tab[data-tab="${tabName}"]`) || 
               document.querySelector(`.mobile-nav-tab[data-tab="${tabName}"]`);
    if (tab) {
        tab.click();
    }
}

// Переключение мобильной навигации
function toggleMobileNav() {
    const burgerMenu = document.getElementById('burgerMenu');
    const mobileNav = document.getElementById('mobileNav');
    
    if (burgerMenu && mobileNav) {
        burgerMenu.classList.remove('active');
        mobileNav.classList.remove('active');
    }
}

// Инициализация конвертера валют
function initializeCurrencyConverter() {
    const fromCurrencySelect = document.getElementById('from-currency');
    const toCurrencySelect = document.getElementById('to-currency');
    const swapButton = document.getElementById('swap-currencies');
    const amountInput = document.getElementById('amount-input');
    const resultInput = document.getElementById('result-input');
    const clearButton = document.getElementById('clear-converter');
    const fromSymbol = document.getElementById('from-symbol');
    const toSymbol = document.getElementById('to-symbol');
    const exchangeRateInfo = document.getElementById('rate-value');
    const errorMessage = document.getElementById('converter-error');
    
    if (!fromCurrencySelect || !toCurrencySelect) return;
    
    // Обновление символов валют
    function updateCurrencySymbols() {
        const fromCurrency = fromCurrencySelect.value;
        const toCurrency = toCurrencySelect.value;
        
        if (window.currencies && window.currencies[fromCurrency]) {
            fromSymbol.innerHTML = `<i class="${window.currencies[fromCurrency].icon}"></i>`;
        }
        
        if (window.currencies && window.currencies[toCurrency]) {
            toSymbol.innerHTML = `<i class="${window.currencies[toCurrency].icon}"></i>`;
        }
    }
    
    // Функция конвертации
    function convertCurrency() {
        const amount = parseFloat(amountInput.value);
        const fromCurrency = fromCurrencySelect.value;
        const toCurrency = toCurrencySelect.value;
        
        if (isNaN(amount) || amount < 0) {
            showConverterError('Введите корректную сумму');
            resultInput.value = '';
            return;
        }
        
        if (amount === 0) {
            resultInput.value = '0';
            updateExchangeRateText(fromCurrency, toCurrency);
            hideConverterError();
            return;
        }
        
        hideConverterError();
        
        // Используем курсы из PHP
        if (!window.exchangeRates || !window.exchangeRates[fromCurrency] || !window.exchangeRates[toCurrency]) {
            showConverterError('Курс для выбранных валют не найден');
            return;
        }
        
        // Конвертируем через USD как базовую валюту
        const amountInUSD = amount / window.exchangeRates[fromCurrency];
        const result = amountInUSD * window.exchangeRates[toCurrency];
        
        // Обновляем результат
        resultInput.value = result.toFixed(2);
        
        // Обновляем информацию о курсе
        updateExchangeRateText(fromCurrency, toCurrency);
    }
    
    // Обновление текста курса
    function updateExchangeRateText(fromCurrency, toCurrency) {
        if (window.exchangeRates && window.exchangeRates[fromCurrency] && window.exchangeRates[toCurrency]) {
            const rate = (window.exchangeRates[toCurrency] / window.exchangeRates[fromCurrency]).toFixed(4);
            exchangeRateInfo.textContent = `1 ${fromCurrency} = ${rate} ${toCurrency}`;
        }
    }
    
    // Автоматическая конвертация при изменении значений
    let conversionTimeout;
    function scheduleConversion() {
        clearTimeout(conversionTimeout);
        conversionTimeout = setTimeout(convertCurrency, 300);
    }
    
    if (amountInput) {
        amountInput.addEventListener('input', scheduleConversion);
    }
    
    if (fromCurrencySelect) {
        fromCurrencySelect.addEventListener('change', function() {
            updateCurrencySymbols();
            scheduleConversion();
        });
    }
    
    if (toCurrencySelect) {
        toCurrencySelect.addEventListener('change', function() {
            updateCurrencySymbols();
            scheduleConversion();
        });
    }
    
    // Обработчик кнопки очистки
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            amountInput.value = '100';
            resultInput.value = '';
            fromCurrencySelect.value = 'USD';
            toCurrencySelect.value = 'BYN';
            updateCurrencySymbols();
            updateExchangeRateText('USD', 'BYN');
            hideConverterError();
            scheduleConversion();
        });
    }
    
    // Обработчик кнопки смены валют
    if (swapButton) {
        swapButton.addEventListener('click', function() {
            const tempCurrency = fromCurrencySelect.value;
            fromCurrencySelect.value = toCurrencySelect.value;
            toCurrencySelect.value = tempCurrency;
            
            updateCurrencySymbols();
            scheduleConversion();
        });
    }
    
    // Функции для работы с ошибками
    function showConverterError(message) {
        if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            
            setTimeout(hideConverterError, 5000);
        }
    }
    
    function hideConverterError() {
        if (errorMessage) {
            errorMessage.style.display = 'none';
        }
    }
    
    // Инициализация
    updateCurrencySymbols();
    convertCurrency();
    
    // Фокус на поле ввода суммы
    if (amountInput) {
        amountInput.focus();
        amountInput.select();
    }
}

// Инициализация переводчика
function initializeTranslator() {
    const sourceLanguageSelect = document.getElementById('source-language');
    const targetLanguageSelect = document.getElementById('target-language');
    const swapButton = document.getElementById('swap-languages');
    const sourceText = document.getElementById('source-text');
    const translatedText = document.getElementById('translated-text');
    const translateButton = document.getElementById('translate-button');
    const clearButton = document.getElementById('clear-translator');
    const buttonText = translateButton ? translateButton.querySelector('.button-text') : null;
    const sourceCharCount = document.getElementById('source-char-count');
    const translatedCharCount = document.getElementById('translated-char-count');
    const errorMessage = document.getElementById('error-message');
    const detectedLanguageBadge = document.getElementById('detected-language');
    const autoDetectIndicator = document.getElementById('auto-detect-indicator');

    if (!sourceLanguageSelect || !targetLanguageSelect) return;
    
    // Обновление счетчика символов
    function updateCharCount() {
        if (sourceCharCount && sourceText) {
            sourceCharCount.textContent = `${sourceText.value.length} символов`;
        }
        if (translatedCharCount && translatedText) {
            translatedCharCount.textContent = `${translatedText.value.length} символов`;
        }
    }
    
    if (sourceText) {
        updateCharCount();
        sourceText.addEventListener('input', updateCharCount);
    }

    // Функция для определения языка текста
    async function detectLanguage(text) {
        if (!text.trim()) return 'en';
        
        // Показываем индикатор определения языка
        if (autoDetectIndicator) {
            autoDetectIndicator.style.display = 'flex';
        }
        
        try {
            const response = await fetch(`https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=en&dt=t&q=${encodeURIComponent(text.substring(0, 100))}`);
            
            if (!response.ok) {
                throw new Error('Language detection failed');
            }
            
            const data = await response.json();
            let detectedLang = data[2] || 'en';
            
            // Преобразуем код языка
            const langMap = {
                'ru': 'ru',
                'en': 'en',
                'es': 'es',
                'fr': 'fr',
                'de': 'de',
                'it': 'it',
                'ja': 'ja',
                'zh-CN': 'zh',
                'zh-TW': 'zh',
                'ar': 'ar',
                'pl': 'pl',
                'tr': 'tr'
            };
            
            detectedLang = langMap[detectedLang] || 'en';
            
            // Обновляем бейдж с определенным языком
            const langNames = {
                'ru': 'Русский',
                'en': 'English',
                'es': 'Español',
                'fr': 'Français',
                'de': 'Deutsch',
                'it': 'Italiano',
                'ja': '日本語',
                'zh': '中文',
                'ar': 'العربية',
                'pl': 'Polski',
                'tr': 'Türkçe'
            };
            
            if (detectedLanguageBadge) {
                detectedLanguageBadge.textContent = langNames[detectedLang] || detectedLang;
                detectedLanguageBadge.style.display = 'inline-block';
            }
            
            return detectedLang;
        } catch (error) {
            console.error('Language detection error:', error);
            
            // Fallback: простое определение по символам
            if (text.match(/[а-яА-ЯёЁ]/)) {
                if (detectedLanguageBadge) {
                    detectedLanguageBadge.textContent = 'Русский';
                    detectedLanguageBadge.style.display = 'inline-block';
                }
                return 'ru';
            } else if (text.match(/[a-zA-Z]/)) {
                if (detectedLanguageBadge) {
                    detectedLanguageBadge.textContent = 'English';
                    detectedLanguageBadge.style.display = 'inline-block';
                }
                return 'en';
            } else {
                if (detectedLanguageBadge) {
                    detectedLanguageBadge.textContent = 'Auto';
                    detectedLanguageBadge.style.display = 'inline-block';
                }
                return 'en';
            }
        } finally {
            if (autoDetectIndicator) {
                autoDetectIndicator.style.display = 'none';
            }
        }
    }

    // Основная функция перевода
    async function translateText(text, sourceLang, targetLang) {
        if (!text.trim()) {
            if (translatedText) {
                translatedText.value = '';
                updateCharCount();
            }
            return '';
        }
        
        try {
            // Показываем индикатор загрузки
            if (translateButton && buttonText) {
                translateButton.disabled = true;
                buttonText.innerHTML = '<div class="loading"></div>Перевод...';
            }
            
            // Определяем язык, если выбран автоопределение
            let actualSourceLang = sourceLang;
            if (sourceLang === 'auto') {
                actualSourceLang = await detectLanguage(text);
            }
            
            // Используем Google Translate API
            const response = await fetch(
                `https://translate.googleapis.com/translate_a/single?client=gtx&sl=${actualSourceLang}&tl=${targetLang}&dt=t&q=${encodeURIComponent(text)}`
            );
            
            if (!response.ok) {
                throw new Error(`Translation failed: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Извлекаем переведенный текст
            let translated = '';
            if (data[0]) {
                data[0].forEach(item => {
                    if (item[0]) {
                        translated += item[0];
                    }
                });
            }
            
            return translated || text;
        } catch (error) {
            console.error('Translation error:', error);
            
            // Fallback 1: Попробуем MyMemory API
            try {
                const response = await fetch(
                    `https://api.mymemory.translated.net/get?q=${encodeURIComponent(text)}&langpair=${actualSourceLang || 'auto'}|${targetLang}`
                );
                
                if (!response.ok) {
                    throw new Error('MyMemory API failed');
                }
                
                const data = await response.json();
                if (data.responseData && data.responseData.translatedText) {
                    return data.responseData.translatedText;
                }
            } catch (memoryError) {
                console.error('MyMemory error:', memoryError);
            }
            
            // Fallback 2: Если все API не сработали, используем простую подстановку
            throw new Error('Все сервисы перевода недоступны');
        } finally {
            if (translateButton && buttonText) {
                translateButton.disabled = false;
                buttonText.textContent = 'Перевести';
            }
        }
    }

    // Обработчик кнопки перевода
    if (translateButton) {
        translateButton.addEventListener('click', async function() {
            const textToTranslate = sourceText.value.trim();
            
            if (!textToTranslate) {
                showTranslatorError('Пожалуйста, введите текст для перевода');
                return;
            }
            
            if (textToTranslate.length > 5000) {
                showTranslatorError('Текст слишком длинный (максимум 5000 символов)');
                return;
            }
            
            hideTranslatorError();
            
            try {
                const sourceLang = sourceLanguageSelect.value;
                const targetLang = targetLanguageSelect.value;
                
                const translated = await translateText(textToTranslate, sourceLang, targetLang);
                if (translatedText) {
                    translatedText.value = translated;
                }
                
                updateCharCount();
                
            } catch (error) {
                console.error('Translation failed:', error);
                
                // Демо-перевод при ошибке
                if (sourceText && sourceText.value.trim()) {
                    const demoTranslations = {
                        'ru-en': 'This is a demo translation. For full functionality, please try again later.',
                        'en-ru': 'Это демо-перевод. Для полной функциональности попробуйте позже.',
                        'default': '[Demo] ' + sourceText.value
                    };
                    
                    let demoText = demoTranslations.default;
                    if (sourceLanguageSelect.value === 'ru' && targetLanguageSelect.value === 'en') {
                        demoText = demoTranslations['ru-en'];
                    } else if (sourceLanguageSelect.value === 'en' && targetLanguageSelect.value === 'ru') {
                        demoText = demoTranslations['en-ru'];
                    }
                    
                    if (translatedText) {
                        translatedText.value = demoText;
                        updateCharCount();
                    }
                    
                    showTranslatorError('Сервисы перевода временно недоступны. Используется демо-режим.');
                }
            }
        });
    }
    
    // Обработчик кнопки очистки
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            if (sourceText) sourceText.value = '';
            if (translatedText) translatedText.value = '';
            if (detectedLanguageBadge) detectedLanguageBadge.style.display = 'none';
            updateCharCount();
            hideTranslatorError();
            if (sourceText) sourceText.focus();
        });
    }
    
    // Обработчик кнопки смены языков
    if (swapButton) {
        swapButton.addEventListener('click', async function() {
            const sourceLang = sourceLanguageSelect.value;
            const targetLang = targetLanguageSelect.value;
            
            if (targetLang === 'auto') {
                targetLanguageSelect.value = sourceLang === 'auto' ? 'ru' : sourceLang;
                sourceLanguageSelect.value = 'auto';
            } else {
                sourceLanguageSelect.value = targetLang;
                targetLanguageSelect.value = sourceLang === 'auto' ? 'ru' : sourceLang;
            }
            
            if (sourceText && sourceText.value && translatedText && translatedText.value) {
                const tempText = sourceText.value;
                sourceText.value = translatedText.value;
                translatedText.value = tempText;
                
                updateCharCount();
                
                // Автоматически определяем язык после смены
                if (sourceLanguageSelect.value === 'auto' && sourceText.value.trim()) {
                    const detectedLang = await detectLanguage(sourceText.value);
                    if (detectedLanguageBadge) {
                        detectedLanguageBadge.textContent = detectedLang === 'ru' ? 'Русский' : 
                                                           detectedLang === 'en' ? 'English' : detectedLang;
                        detectedLanguageBadge.style.display = 'inline-block';
                    }
                }
            }
        });
    }
    
    // Автоматический перевод при вводе (с задержкой)
    let translationTimeout;
    if (sourceText) {
        sourceText.addEventListener('input', function() {
            clearTimeout(translationTimeout);
            const text = sourceText.value.trim();
            
            if (text && text.length > 2 && text.length <= 500) {
                translationTimeout = setTimeout(async () => {
                    if (text === sourceText.value.trim()) {
                        // Определяем язык для автоопределения
                        if (sourceLanguageSelect.value === 'auto' && text.length > 10) {
                            await detectLanguage(text);
                        }
                        // Автоперевод для коротких текстов
                        if (text.length <= 200) {
                            if (translateButton) translateButton.click();
                        }
                    }
                }, 1500);
            }
        });
    }
    
    // Функции для работы с ошибками переводчика
    function showTranslatorError(message) {
        if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            
            setTimeout(hideTranslatorError, 5000);
        }
    }
    
    function hideTranslatorError() {
        if (errorMessage) {
            errorMessage.style.display = 'none';
        }
    }
    
    // Обработчик клавиши Enter для перевода (Ctrl+Enter)
    if (sourceText) {
        sourceText.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                if (translateButton) translateButton.click();
            }
        });
    }
    
    // Фокус на поле ввода текста
    if (sourceText) {
        sourceText.focus();
    }
}

// Функции для чата с администрацией
function loadAdminChatMessages() {
    if (typeof window.isLoggedIn === 'undefined' || !window.isLoggedIn) return;
    
    fetch('get_admin_chat.php')
        .then(response => response.json())
        .then(messages => {
            displayAdminChatMessages(messages);
        })
        .catch(error => {
            console.error('Error loading admin chat:', error);
        });
}

function displayAdminChatMessages(messages) {
    const container = document.getElementById('admin-chat-messages');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (messages.length === 0) {
        container.innerHTML = '<div class="notification info"><i class="fas fa-info-circle"></i> Задайте вопрос администрации!</div>';
        return;
    }
    
    messages.forEach(msg => {
        const messageDiv = document.createElement('div');
        const isOwnMessage = msg.sender_id == window.userId;
        
        messageDiv.className = `message ${isOwnMessage ? 'own' : 'other'}`;
        messageDiv.innerHTML = `
            <div class="message-header">
                <span class="message-sender">${msg.sender_name}</span>
                <span class="message-time">${msg.created_at}</span>
            </div>
            <div class="message-text">${msg.message_text}</div>
        `;
        
        container.appendChild(messageDiv);
    });
    
    container.scrollTop = container.scrollHeight;
}

function sendAdminMessage() {
    const messageInput = document.getElementById('admin-message-text');
    const message = messageInput ? messageInput.value.trim() : '';
    
    if (!message) return;
    
    const formData = new FormData();
    formData.append('message', message);
    formData.append('action', 'send_admin_message');
    
    fetch('auth.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (messageInput) messageInput.value = '';
            loadAdminChatMessages();
        } else {
            showToast('Ошибка: ' + result.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Ошибка отправки', 'error');
    });
}

function setupChatAutoRefresh() {
    setInterval(() => {
        if (document.querySelector('.nav-tab.active')?.getAttribute('data-tab') === 'profile' ||
            document.querySelector('.mobile-nav-tab.active')?.getAttribute('data-tab') === 'profile') {
            loadAdminChatMessages();
        }
    }, 5000);
}

// Функция для копирования текста в буфер обмена
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Текст скопирован в буфер обмена', 'success');
    }).catch(err => {
        console.error('Ошибка копирования: ', err);
        showToast('Не удалось скопировать текст', 'error');
    });
}

// Показать всплывающее сообщение
function showToast(message, type = 'info') {
    // Удаляем старые тосты
    const oldToasts = document.querySelectorAll('.custom-toast');
    oldToasts.forEach(toast => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    });
    
    const toast = document.createElement('div');
    toast.className = `custom-toast ${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    // Стили для тоста
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? 'rgba(56, 176, 0, 0.9)' : 
                     type === 'error' ? 'rgba(255, 0, 84, 0.9)' : 
                     'rgba(58, 134, 255, 0.9)'};
        color: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        backdrop-filter: blur(10px);
        box-shadow: var(--shadow-lg);
        max-width: 400px;
    `;
    
    // Автоматическое скрытие через 5 секунд
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 500);
    }, 5000);
}

// Валидация email
function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

// Валидация телефона (белорусский формат)
function isValidPhone(phone) {
    const re = /^\+375\s?\((29|33|44|25)\)\s?\d{3}-\d{2}-\d{2}$/;
    return re.test(phone);
}

// Форматирование номера телефона
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.length > 0) {
        value = '+375' + value;
    }
    
    if (value.length > 4) {
        value = value.slice(0, 4) + ' (' + value.slice(4);
    }
    if (value.length > 8) {
        value = value.slice(0, 8) + ') ' + value.slice(8);
    }
    if (value.length > 13) {
        value = value.slice(0, 13) + '-' + value.slice(13);
    }
    if (value.length > 16) {
        value = value.slice(0, 16) + '-' + value.slice(16);
    }
    if (value.length > 19) {
        value = value.slice(0, 19);
    }
    
    input.value = value;
}

// Инициализация специфичных для вкладки функций
function initializeTabSpecificFunctions(tabId) {
    switch(tabId) {
        case 'converter':
            initializeCurrencyConverter();
            break;
        case 'translator':
            initializeTranslator();
            break;
        case 'map-services':
            // Инициализация карты, если она существует
            if (typeof window.initializeMigrationMap === 'function') {
                window.initializeMigrationMap();
            }
            break;
    }
}

// Настройка обработчиков событий
function setupEventListeners() {
    // Обработка Enter в чате с администрацией
    const adminInput = document.getElementById('admin-message-text');
    if (adminInput) {
        adminInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendAdminMessage();
            }
        });
    }
    
    // Обработка отправки форм
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Дополнительная валидация перед отправкой
            const username = this.querySelector('input[name="username"]').value.trim();
            const password = this.querySelector('input[name="password"]').value.trim();
            
            if (!username || !password) {
                showToast('Заполните все поля', 'error');
                return;
            }
            
            this.submit();
        });
    }
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Дополнительная валидация перед отправкой
            const email = this.querySelector('input[name="email"]').value.trim();
            const password = this.querySelector('input[name="password"]').value.trim();
            const firstName = this.querySelector('input[name="first_name"]').value.trim();
            const lastName = this.querySelector('input[name="last_name"]').value.trim();
            
            if (!email || !password || !firstName || !lastName) {
                showToast('Заполните все обязательные поля', 'error');
                return;
            }
            
            if (!isValidEmail(email)) {
                showToast('Введите корректный email', 'error');
                return;
            }
            
            this.submit();
        });
    }
    
    // Обработка форматирования телефона
    const phoneInputs = document.querySelectorAll('input[name="phone"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    });
}

// Функция для центрирования карты на городе
function centerMapOnCity(cityKey) {
    if (typeof window.centerMapOnCity === 'function') {
        window.centerMapOnCity(cityKey);
    } else {
        showTab('map-services');
        sessionStorage.setItem('centerMapOnCity', cityKey);
    }
}

// Глобальная функция для переключения между вкладками (доступна из HTML)
window.showTab = showTab;
window.centerMapOnCity = centerMapOnCity;