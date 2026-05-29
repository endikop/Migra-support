<?php
// РќРµ Р·Р°РїСѓСЃРєР°РµРј session_start() РїРѕРІС‚РѕСЂРЅРѕ
require_once '../src/config/config.php'
require_once '../src/components/header_nav.php';;
require_once '../src/components/include_avatar.php';

// РџРѕР»СѓС‡Р°РµРј РіРѕСЂРѕРґ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
$userCity = $_SESSION['city'] ?? 'minsk';

// Р¤СѓРЅРєС†РёСЏ РґР»СЏ РїРѕР»СѓС‡РµРЅРёСЏ РґР°РЅРЅС‹С… РіРѕСЂРѕРґР°
function getCityData($city) {
    $cities = [
        'minsk' => [
            'name' => 'РњРёРЅСЃРє',
            'image' => 'https://images.unsplash.com/photo-1596467888261-6a1e4c0b152a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
            'description' => 'РЎС‚РѕР»РёС†Р° Р‘РµР»Р°СЂСѓСЃРё, РєСЂСѓРїРЅРµР№С€РёР№ РїРѕР»РёС‚РёС‡РµСЃРєРёР№, СЌРєРѕРЅРѕРјРёС‡РµСЃРєРёР№ Рё РєСѓР»СЊС‚СѓСЂРЅС‹Р№ С†РµРЅС‚СЂ СЃС‚СЂР°РЅС‹. РЎРѕРІСЂРµРјРµРЅРЅС‹Р№ РіРѕСЂРѕРґ СЃ СЂР°Р·РІРёС‚РѕР№ РёРЅС„СЂР°СЃС‚СЂСѓРєС‚СѓСЂРѕР№.',
            'population' => '2 009 786 С‡РµР»РѕРІРµРє',
            'area' => '409,5 РєРјВІ',
            'services' => [
                [
                    'name' => 'Р“Р»Р°РІРЅРѕРµ СѓРїСЂР°РІР»РµРЅРёРµ РїРѕ РіСЂР°Р¶РґР°РЅСЃС‚РІСѓ Рё РјРёРіСЂР°С†РёРё',
                    'address' => 'СѓР». Р’РѕР»РѕРґР°СЂСЃРєРѕРіРѕ, 6',
                    'phone' => '+375 (17) 218-01-02',
                    'hours' => 'РџРЅ-РџС‚ 9:00-18:00, РѕР±РµРґ 13:00-14:00',
                    'email' => 'minsk@mvd.gov.by'
                ]
            ]
        ],
        'grodno' => [
            'name' => 'Р“СЂРѕРґРЅРѕ',
            'image' => 'https://images.unsplash.com/photo-1620053281310-7048673451e6?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
            'description' => 'Р“РѕСЂРѕРґ РЅР° Р·Р°РїР°РґРµ Р‘РµР»Р°СЂСѓСЃРё, РёР·РІРµСЃС‚РЅС‹Р№ СЃРІРѕРµР№ Р±РѕРіР°С‚РѕР№ РёСЃС‚РѕСЂРёРµР№ Рё Р°СЂС…РёС‚РµРєС‚СѓСЂРѕР№. РљСѓР»СЊС‚СѓСЂРЅР°СЏ СЃС‚РѕР»РёС†Р° Р‘РµР»Р°СЂСѓСЃРё.',
            'population' => '370 919 С‡РµР»РѕРІРµРє',
            'area' => '142,1 РєРјВІ',
            'services' => [
                [
                    'name' => 'РћС‚РґРµР» РїРѕ РіСЂР°Р¶РґР°РЅСЃС‚РІСѓ Рё РјРёРіСЂР°С†РёРё',
                    'address' => 'СѓР». РћР¶РµС€РєРѕ, 3',
                    'phone' => '+375 (152) 72-34-56',
                    'hours' => 'РџРЅ-РџС‚ 8:00-17:00',
                    'email' => 'grodno@mvd.gov.by'
                ]
            ]
        ],
        'brest' => [
            'name' => 'Р‘СЂРµСЃС‚',
            'image' => 'https://images.unsplash.com/photo-1599396170196-429b63e8c8a9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
            'description' => 'Р“РѕСЂРѕРґ-РіРµСЂРѕР№ РЅР° РіСЂР°РЅРёС†Рµ СЃ РџРѕР»СЊС€РµР№, РёР·РІРµСЃС‚РЅС‹Р№ Р‘СЂРµСЃС‚СЃРєРѕР№ РєСЂРµРїРѕСЃС‚СЊСЋ. РљСЂСѓРїРЅС‹Р№ С‚СЂР°РЅСЃРїРѕСЂС‚РЅС‹Р№ СѓР·РµР».',
            'population' => '350 616 С‡РµР»РѕРІРµРє',
            'area' => '146,1 РєРјВІ',
            'services' => [
                [
                    'name' => 'РЈРїСЂР°РІР»РµРЅРёРµ РїРѕ РіСЂР°Р¶РґР°РЅСЃС‚РІСѓ Рё РјРёРіСЂР°С†РёРё',
                    'address' => 'СѓР». Р›РµРЅРёРЅР°, 19',
                    'phone' => '+375 (162) 23-45-67',
                    'hours' => 'РџРЅ-РџС‚ 8:30-17:30',
                    'email' => 'brest@mvd.gov.by'
                ]
            ]
        ],
        'vitebsk' => [
            'name' => 'Р’РёС‚РµР±СЃРє',
            'image' => 'https://images.unsplash.com/photo-1601919051955-9a4d1f4e6a72?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
            'description' => 'Р“РѕСЂРѕРґ РЅР° СЃРµРІРµСЂРµ Р‘РµР»Р°СЂСѓСЃРё, РёР·РІРµСЃС‚РЅС‹Р№ С„РµСЃС‚РёРІР°Р»РµРј "РЎР»Р°РІСЏРЅСЃРєРёР№ Р±Р°Р·Р°СЂР°". РљСѓР»СЊС‚СѓСЂРЅР°СЏ Р¶РµРјС‡СѓР¶РёРЅР° СЂРµРіРёРѕРЅР°.',
            'population' => '378 459 С‡РµР»РѕРІРµРє',
            'area' => '124,5 РєРјВІ',
            'services' => [
                [
                    'name' => 'РћС‚РґРµР» РїРѕ РіСЂР°Р¶РґР°РЅСЃС‚РІСѓ Рё РјРёРіСЂР°С†РёРё',
                    'address' => 'СѓР». Р—Р°РјРєРѕРІР°СЏ, 5',
                    'phone' => '+375 (212) 23-45-67',
                    'hours' => 'РџРЅ-РџС‚ 8:30-17:30',
                    'email' => 'vitebsk@mvd.gov.by'
                ]
            ]
        ],
        'gomel' => [
            'name' => 'Р“РѕРјРµР»СЊ',
            'image' => 'https://images.unsplash.com/photo-1574362849222-7875e732f6f7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
            'description' => 'Р’С‚РѕСЂРѕР№ РїРѕ РІРµР»РёС‡РёРЅРµ РіРѕСЂРѕРґ Р‘РµР»Р°СЂСѓСЃРё, РІР°Р¶РЅС‹Р№ РїСЂРѕРјС‹С€Р»РµРЅРЅС‹Р№ Рё РєСѓР»СЊС‚СѓСЂРЅС‹Р№ С†РµРЅС‚СЂ РЅР° СЋРіРѕ-РІРѕСЃС‚РѕРєРµ СЃС‚СЂР°РЅС‹.',
            'population' => '535 693 С‡РµР»РѕРІРµРє',
            'area' => '139,8 РєРјВІ',
            'services' => [
                [
                    'name' => 'РЈРїСЂР°РІР»РµРЅРёРµ РїРѕ РіСЂР°Р¶РґР°РЅСЃС‚РІСѓ Рё РјРёРіСЂР°С†РёРё',
                    'address' => 'РїСЂ. Р›РµРЅРёРЅР°, 10',
                    'phone' => '+375 (232) 34-56-78',
                    'hours' => 'РџРЅ-РџС‚ 8:00-17:00',
                    'email' => 'gomel@mvd.gov.by'
                ]
            ]
        ],
        'mogilev' => [
            'name' => 'РњРѕРіРёР»С‘РІ',
            'image' => 'https://images.unsplash.com/photo-1548013146-72479768bada?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
            'description' => 'РљСЂСѓРїРЅС‹Р№ РїСЂРѕРјС‹С€Р»РµРЅРЅС‹Р№ Рё РєСѓР»СЊС‚СѓСЂРЅС‹Р№ С†РµРЅС‚СЂ РЅР° РІРѕСЃС‚РѕРєРµ Р‘РµР»Р°СЂСѓСЃРё. Р“РѕСЂРѕРґ СЃ Р±РѕРіР°С‚РѕР№ РёСЃС‚РѕСЂРёРµР№.',
            'population' => '380 440 С‡РµР»РѕРІРµРє',
            'area' => '118,5 РєРјВІ',
            'services' => [
                [
                    'name' => 'РћС‚РґРµР» РїРѕ РіСЂР°Р¶РґР°РЅСЃС‚РІСѓ Рё РјРёРіСЂР°С†РёРё',
                    'address' => 'СѓР». РџРµСЂРІРѕРјР°Р№СЃРєР°СЏ, 22',
                    'phone' => '+375 (222) 45-67-89',
                    'hours' => 'РџРЅ-РџС‚ 9:00-18:00',
                    'email' => 'mogilev@mvd.gov.by'
                ]
            ]
        ]
    ];

    return $cities[$city] ?? $cities['minsk'];
}

$cityData = getCityData($userCity);
?>

<div class="hero">
    <h1>Р§Р°С‚ РіРѕСЂРѕРґР° <?php echo $cityData['name']; ?></h1>
    <p>РћР±С‰Р°Р№С‚РµСЃСЊ СЃ РґСЂСѓРіРёРјРё РјРёРіСЂР°РЅС‚Р°РјРё РІ РІР°С€РµРј РіРѕСЂРѕРґРµ, РґРµР»РёС‚РµСЃСЊ РѕРїС‹С‚РѕРј Рё РЅР°С…РѕРґРёС‚Рµ РїРѕРґРґРµСЂР¶РєСѓ.</p>
</div>

<?php if (!$isLoggedIn || !canUserPerformActions()): ?>
    <div class="card">
        <div class="notification info">
            <i class="fas fa-info-circle"></i>
            Р”Р»СЏ РґРѕСЃС‚СѓРїР° Рє С‡Р°С‚Сѓ РЅРµРѕР±С…РѕРґРёРјРѕ <a href="#" onclick="showTab('login')" style="color: var(--primary); font-weight: bold;">РІРѕР№С‚Рё РІ СЃРёСЃС‚РµРјСѓ</a>.
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="chat-container">
            <div class="chat-messages" id="city-chat-messages">
                <div class="notification info">
                    <i class="fas fa-info-circle"></i>
                    Р—Р°РіСЂСѓР·РєР° СЃРѕРѕР±С‰РµРЅРёР№...
                </div>
            </div>
            
            <div class="chat-input-area">
                <textarea class="chat-input" id="city-message-text" placeholder="РќР°РїРёС€РёС‚Рµ СЃРѕРѕР±С‰РµРЅРёРµ РґР»СЏ Р¶РёС‚РµР»РµР№ <?php echo $cityData['name']; ?>..." rows="3"></textarea>
                <button onclick="sendCityMessage(event)" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
        
        <div style="display: flex; gap: 15px; margin-top: 20px; align-items: center;">
            <button onclick="refreshChat()" class="btn btn-outline">
                <i class="fas fa-sync-alt"></i> РћР±РЅРѕРІРёС‚СЊ
            </button>
            <span style="color: var(--gray-light); font-size: 0.9rem;">
                Р§Р°С‚ РѕР±РЅРѕРІР»СЏРµС‚СЃСЏ Р°РІС‚РѕРјР°С‚РёС‡РµСЃРєРё РєР°Р¶РґС‹Рµ 10 СЃРµРєСѓРЅРґ
            </span>
        </div>
    </div>

    <script>
        // Р“Р»РѕР±Р°Р»СЊРЅС‹Рµ РїРµСЂРµРјРµРЅРЅС‹Рµ РґР»СЏ СѓРїСЂР°РІР»РµРЅРёСЏ Р°РІС‚РѕРѕР±РЅРѕРІР»РµРЅРёРµРј
        let chatAutoRefresh = true;
        let chatRefreshInterval;
        let lastMessageCount = 0;
        
        // Р¤СѓРЅРєС†РёРё РґР»СЏ РіРѕСЂРѕРґСЃРєРѕРіРѕ С‡Р°С‚Р°
        function loadCityChatMessages(showLoader = true) {
            if (!<?php echo ($isLoggedIn && canUserPerformActions()) ? 'true' : 'false'; ?>) return;
            
            // РџРѕРєР°Р·С‹РІР°РµРј РёРЅРґРёРєР°С‚РѕСЂ Р·Р°РіСЂСѓР·РєРё С‚РѕР»СЊРєРѕ РµСЃР»Рё СЏРІРЅРѕ СѓРєР°Р·Р°РЅРѕ
            if (showLoader) {
                const container = document.getElementById('city-chat-messages');
                if (container) {
                    container.innerHTML = '<div class="notification info"><i class="fas fa-spinner fa-spin"></i> Р—Р°РіСЂСѓР·РєР° СЃРѕРѕР±С‰РµРЅРёР№...</div>';
                }
            }
            
            fetch('get_city_chat.php?city=<?php echo $userCity; ?>')
                .then(response => response.json())
                .then(messages => {
                    // РћР±РЅРѕРІР»СЏРµРј С‚РѕР»СЊРєРѕ РµСЃР»Рё РєРѕР»РёС‡РµСЃС‚РІРѕ СЃРѕРѕР±С‰РµРЅРёР№ РёР·РјРµРЅРёР»РѕСЃСЊ
                    if (messages.length !== lastMessageCount) {
                        displayCityChatMessages(messages);
                        lastMessageCount = messages.length;
                    }
                })
                .catch(error => {
                    console.error('Error loading city chat:', error);
                });
        }
        
        function displayCityChatMessages(messages) {
            const container = document.getElementById('city-chat-messages');
            if (!container) return;
            
            container.innerHTML = '';
            
            if (messages.length === 0) {
                container.innerHTML = '<div class="notification info"><i class="fas fa-info-circle"></i> Р§Р°С‚ РїСѓСЃС‚. Р‘СѓРґСЊС‚Рµ РїРµСЂРІС‹Рј!</div>';
                return;
            }
            
            messages.forEach(msg => {
                const messageDiv = document.createElement('div');
                const isOwnMessage = msg.sender_id == <?php echo ($isLoggedIn && canUserPerformActions()) ? $_SESSION['user_id'] : '0'; ?>;
                
                // РЎРѕР·РґР°РµРј Р°РІР°С‚Р°СЂ
                let avatarHtml = '';
                if (msg.avatar) {
                    avatarHtml = `<img src="${msg.avatar}" alt="${msg.sender_name}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; cursor: pointer;" onclick="window.location.href='view_profile.php?id=${msg.sender_id}'">`;
                } else {
                    const initial = msg.sender_name.charAt(0).toUpperCase();
                    avatarHtml = `<div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #ff006e 0%, #ff9e00 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem; cursor: pointer;" onclick="window.location.href='view_profile.php?id=${msg.sender_id}'">${initial}</div>`;
                }
                
                messageDiv.className = `message ${isOwnMessage ? 'own' : 'other'}`;
                messageDiv.style.display = 'flex';
                messageDiv.style.gap = '10px';
                messageDiv.style.alignItems = 'flex-start';
                
                if (isOwnMessage) {
                    messageDiv.innerHTML = `
                        <div style="flex: 1;">
                            <div class="message-header">
                                <span class="message-sender" style="cursor: pointer;" onclick="window.location.href='view_profile.php?id=${msg.sender_id}'">${msg.sender_name}</span>
                                <span class="message-time">${msg.created_at}</span>
                            </div>
                            <div class="message-text">${msg.message_text}</div>
                        </div>
                        ${avatarHtml}
                    `;
                } else {
                    messageDiv.innerHTML = `
                        ${avatarHtml}
                        <div style="flex: 1;">
                            <div class="message-header">
                                <span class="message-sender" style="cursor: pointer;" onclick="window.location.href='view_profile.php?id=${msg.sender_id}'">${msg.sender_name}</span>
                                <span class="message-time">${msg.created_at}</span>
                            </div>
                            <div class="message-text">${msg.message_text}</div>
                        </div>
                    `;
                }
                
                container.appendChild(messageDiv);
            });
            
            // РџР»Р°РІРЅР°СЏ РїСЂРѕРєСЂСѓС‚РєР° Рє РїРѕСЃР»РµРґРЅРµРјСѓ СЃРѕРѕР±С‰РµРЅРёСЋ
            container.scrollTo({
                top: container.scrollHeight,
                behavior: 'smooth'
            });
        }
        
        function sendCityMessage(event) {
            const messageInput = document.getElementById('city-message-text');
            const message = messageInput.value.trim();
            
            if (!message) return;
            
            // Р‘Р»РѕРєРёСЂСѓРµРј РєРЅРѕРїРєСѓ РѕС‚РїСЂР°РІРєРё
            const sendButton = event.target;
            const originalHtml = sendButton.innerHTML;
            sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            sendButton.disabled = true;
            
            const formData = new FormData();
            formData.append('message', message);
            formData.append('city', '<?php echo $userCity; ?>');
            formData.append('action', 'send_city_message');
            
            fetch('auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    messageInput.value = '';
                    // Р—Р°РіСЂСѓР¶Р°РµРј СЃРѕРѕР±С‰РµРЅРёСЏ Р±РµР· РїРѕРєР°Р·Р° РёРЅРґРёРєР°С‚РѕСЂР°
                    loadCityChatMessages(false);
                } else {
                    alert('РћС€РёР±РєР°: ' + result.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('РћС€РёР±РєР° РѕС‚РїСЂР°РІРєРё');
            })
            .finally(() => {
                // Р’РѕСЃСЃС‚Р°РЅР°РІР»РёРІР°РµРј РєРЅРѕРїРєСѓ
                sendButton.innerHTML = originalHtml;
                sendButton.disabled = false;
                messageInput.focus();
            });
        }
        
        // Р¤СѓРЅРєС†РёСЏ РґР»СЏ РєРЅРѕРїРєРё РѕР±РЅРѕРІР»РµРЅРёСЏ
        function refreshChat() {
            // РЎР±СЂР°СЃС‹РІР°РµРј СЃС‡РµС‚С‡РёРє СЃРѕРѕР±С‰РµРЅРёР№, С‡С‚РѕР±С‹ РїСЂРёРЅСѓРґРёС‚РµР»СЊРЅРѕ РѕР±РЅРѕРІРёС‚СЊ
            lastMessageCount = 0;
            loadCityChatMessages(true);
        }
        
        // РќР°СЃС‚СЂРѕР№РєР° Р°РІС‚РѕРѕР±РЅРѕРІР»РµРЅРёСЏ С‡Р°С‚Р°
        function setupChatAutoRefresh() {
            // РћС‡РёС‰Р°РµРј РїСЂРµРґС‹РґСѓС‰РёР№ РёРЅС‚РµСЂРІР°Р», РµСЃР»Рё РµСЃС‚СЊ
            if (chatRefreshInterval) {
                clearInterval(chatRefreshInterval);
            }
            
            // Р—Р°РїСѓСЃРєР°РµРј Р°РІС‚РѕРѕР±РЅРѕРІР»РµРЅРёРµ С‚РѕР»СЊРєРѕ РµСЃР»Рё Р°РєС‚РёРІРЅРѕ
            if (chatAutoRefresh) {
                chatRefreshInterval = setInterval(() => {
                    // РџСЂРѕРІРµСЂСЏРµРј, Р°РєС‚РёРІРЅР° Р»Рё РІРєР»Р°РґРєР° С‡Р°С‚Р°
                    const activeTab = document.querySelector('.nav-tab.active');
                    if (activeTab && activeTab.getAttribute('data-tab') === 'city-chat') {
                        // Р—Р°РіСЂСѓР¶Р°РµРј Р±РµР· РїРѕРєР°Р·Р° РёРЅРґРёРєР°С‚РѕСЂР° Р·Р°РіСЂСѓР·РєРё
                        loadCityChatMessages(false);
                    }
                }, 10000); // РЈРІРµР»РёС‡РёРІР°РµРј РёРЅС‚РµСЂРІР°Р» РґРѕ 10 СЃРµРєСѓРЅРґ
            }
        }
        
        // РћР±СЂР°Р±РѕС‚РєР° Enter РІ С‡Р°С‚Рµ
        function setupChatEnterHandler() {
            const cityInput = document.getElementById('city-message-text');
            
            if (cityInput) {
                cityInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendCityMessage(e);
                    }
                });
            }
        }
        
        // РРЅРёС†РёР°Р»РёР·Р°С†РёСЏ С‡Р°С‚Р° РїСЂРё Р·Р°РіСЂСѓР·РєРµ СЃС‚СЂР°РЅРёС†С‹
        document.addEventListener('DOMContentLoaded', function() {
            setupChatEnterHandler();
            
            // Р—Р°РіСЂСѓР¶Р°РµРј СЃРѕРѕР±С‰РµРЅРёСЏ РїСЂРё РѕС‚РєСЂС‹С‚РёРё РІРєР»Р°РґРєРё
            const activeTab = document.querySelector('.nav-tab.active');
            if (activeTab && activeTab.getAttribute('data-tab') === 'city-chat') {
                loadCityChatMessages();
            }
            
            // РќР°СЃС‚СЂР°РёРІР°РµРј Р°РІС‚РѕРѕР±РЅРѕРІР»РµРЅРёРµ
            setupChatAutoRefresh();
            
            // РћР±СЂР°Р±РѕС‚С‡РёРє РґР»СЏ РІРєР»Р°РґРѕРє - РѕСЃС‚Р°РЅР°РІР»РёРІР°РµРј Р°РІС‚РѕРѕР±РЅРѕРІР»РµРЅРёРµ РїСЂРё СѓС…РѕРґРµ СЃ С‡Р°С‚Р°
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabId = tab.getAttribute('data-tab');
                    if (tabId !== 'city-chat' && chatRefreshInterval) {
                        clearInterval(chatRefreshInterval);
                    } else if (tabId === 'city-chat') {
                        loadCityChatMessages();
                        setupChatAutoRefresh();
                    }
                });
            });
            
            // РћСЃС‚Р°РЅРѕРІРєР° Р°РІС‚РѕРѕР±РЅРѕРІР»РµРЅРёСЏ РїСЂРё С„РѕРєСѓСЃРµ РЅР° РїРѕР»Рµ РІРІРѕРґР°
            const cityInput = document.getElementById('city-message-text');
            if (cityInput) {
                cityInput.addEventListener('focus', () => {
                    chatAutoRefresh = false;
                    if (chatRefreshInterval) {
                        clearInterval(chatRefreshInterval);
                    }
                });
                
                cityInput.addEventListener('blur', () => {
                    chatAutoRefresh = true;
                    setupChatAutoRefresh();
                });
            }
        });
        
        // Р­РєСЃРїРѕСЂС‚РёСЂСѓРµРј С„СѓРЅРєС†РёРё РґР»СЏ РёСЃРїРѕР»СЊР·РѕРІР°РЅРёСЏ РІ РѕСЃРЅРѕРІРЅРѕРј С„Р°Р№Р»Рµ
        window.loadCityChatMessages = loadCityChatMessages;
        window.sendCityMessage = sendCityMessage;
        window.refreshChat = refreshChat;
    </script>
<?php endif; ?>

