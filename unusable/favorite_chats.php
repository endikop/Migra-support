<?php
session_start();
require_once '../src/config/config.php';

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

// РЇР·С‹РєРё
$supportedLanguages = ['ru', 'en', 'pt', 'fr', 'de'];
$lang = $_COOKIE['lang'] ?? 'ru';

function t($ru, $en, $pt = '', $fr = '', $de = '') {
    global $lang;
    switch ($lang) {
        case 'en': return $en;
        case 'pt': return !empty($pt) ? $pt : $en;
        case 'fr': return !empty($fr) ? $fr : $en;
        case 'de': return !empty($de) ? $de : $en;
        default: return $ru;
    }
}

$translations = [
    'favorite_chats' => t('РР·Р±СЂР°РЅРЅС‹Рµ С‡Р°С‚С‹', 'Favorite Chats', 'Chats Favoritos', 'Chats Favoris', 'Favorit-Chats'),
    'back_to_chats' => t('Р’РµСЂРЅСѓС‚СЊСЃСЏ Рє С‡Р°С‚Р°Рј', 'Back to Chats', 'Voltar aos Chats', 'Retour aux Chats', 'ZurГјck zu Chats'),
    'no_favorite_chats' => t('РЈ РІР°СЃ РЅРµС‚ РёР·Р±СЂР°РЅРЅС‹С… С‡Р°С‚РѕРІ', 'You have no favorite chats', 'VocГЄ nГЈo tem chats favoritos', 'Vous n\'avez pas de chats favoris', 'Sie haben keine Favorit-Chats'),
    'remove_from_favorites' => t('РЈРґР°Р»РёС‚СЊ РёР· РёР·Р±СЂР°РЅРЅРѕРіРѕ', 'Remove from favorites', 'Remover dos favoritos', 'Retirer des favoris', 'Aus Favoriten entfernen'),
    'favorite_since' => t('Р’ РёР·Р±СЂР°РЅРЅРѕРј СЃ', 'Favorite since', 'Favorito desde', 'Favori depuis', 'Favorit seit'),
    'remove_confirm' => t('Р’С‹ СѓРІРµСЂРµРЅС‹, С‡С‚Рѕ С…РѕС‚РёС‚Рµ СѓРґР°Р»РёС‚СЊ С‡Р°С‚ РёР· РёР·Р±СЂР°РЅРЅРѕРіРѕ?', 'Are you sure you want to remove this chat from favorites?', 'Tem certeza de que deseja remover este chat dos favoritos?', 'ГЉtes-vous sГ»r de vouloir retirer ce chat des favoris?', 'Sind Sie sicher, dass Sie diesen Chat aus den Favoriten entfernen mГ¶chten?'),
    'remove_success' => t('Р§Р°С‚ СѓРґР°Р»РµРЅ РёР· РёР·Р±СЂР°РЅРЅРѕРіРѕ', 'Chat removed from favorites', 'Chat removido dos favoritos', 'Chat retirГ© des favoris', 'Chat aus Favoriten entfernt')
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations['favorite_chats']; ?> - MigraSupport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <?php include_once 'include_animations.php'; ?>
    
    <style>
        :root {
            --primary: #3a86ff;
            --primary-dark: #2667cc;
            --primary-light: #5a9cff;
            --secondary: #8338ec;
            --accent: #ff006e;
            --success: #38b000;
            --dark: #1a1a2e;
            --dark-light: #2d2d44;
            --light: #f8f9fa;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --gradient-primary: linear-gradient(135deg, #3a86ff 0%, #8338ec 100%);
            --gradient-secondary: linear-gradient(135deg, #ff006e 0%, #ff9e00 100%);
            --gradient-dark: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 30px 60px rgba(0, 0, 0, 0.2);
            --radius: 16px;
            --radius-lg: 24px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gradient-dark);
            color: var(--light);
            line-height: 1.7;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(58, 134, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(131, 56, 236, 0.15) 0%, transparent 50%);
            z-index: -1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            text-decoration: none;
            transition: var(--transition);
            margin-bottom: 20px;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(-5px);
        }

        .page-header {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .page-header h1 {
            font-size: 2rem;
            color: white;
            margin-bottom: 10px;
        }

        .page-header p {
            color: var(--gray-light);
        }

        .favorite-chats-list {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chat-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            margin-bottom: 10px;
            transition: var(--transition);
            cursor: pointer;
        }

        .chat-card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }

        .chat-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--gradient-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            flex-shrink: 0;
            position: relative;
        }

        .chat-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .online-indicator {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 12px;
            height: 12px;
            background: var(--success);
            border: 2px solid var(--dark);
            border-radius: 50%;
        }

        .chat-info {
            flex: 1;
        }

        .chat-name {
            font-weight: 600;
            color: white;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .chat-details {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: var(--gray-light);
        }

        .chat-detail {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .chat-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-warning {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .btn-warning:hover {
            background: rgba(255, 193, 7, 0.3);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray-light);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: white;
        }

        @media (max-width: 768px) {
            .chat-card {
                flex-direction: column;
                text-align: center;
            }

            .chat-actions {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="personal_chats.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            <?php echo $translations['back_to_chats']; ?>
        </a>

        <div class="page-header">
            <h1><?php echo $translations['favorite_chats']; ?></h1>
            <p>Р’Р°С€Рё РёР·Р±СЂР°РЅРЅС‹Рµ С‡Р°С‚С‹</p>
        </div>

        <div class="favorite-chats-list" id="favoriteChatsList">
            <!-- РЎРїРёСЃРѕРє РёР·Р±СЂР°РЅРЅС‹С… С‡Р°С‚РѕРІ Р±СѓРґРµС‚ Р·Р°РіСЂСѓР¶РµРЅ С‡РµСЂРµР· JavaScript -->
            <div class="empty-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Р—Р°РіСЂСѓР·РєР°...</p>
            </div>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo $_SESSION['user_id']; ?>;

        // Р—Р°РіСЂСѓР·РєР° СЃРїРёСЃРєР° РёР·Р±СЂР°РЅРЅС‹С… С‡Р°С‚РѕРІ
        async function loadFavoriteChats() {
            try {
                const response = await fetch('favorite_chats_api.php?action=get_favorites');
                const chats = await response.json();
                
                const chatsList = document.getElementById('favoriteChatsList');
                
                if (!chats.length) {
                    chatsList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-star"></i>
                            <h3><?php echo $translations['no_favorite_chats']; ?></h3>
                        </div>
                    `;
                    return;
                }
                
                chatsList.innerHTML = chats.map(chat => `
                    <div class="chat-card" onclick="openChat(${chat.chat_id}, ${chat.other_user_id})">
                        <div class="chat-avatar">
                            ${chat.other_user_avatar ? 
                                `<img src="${chat.other_user_avatar}" alt="${chat.other_user_name}">` :
                                chat.other_user_first_name.charAt(0).toUpperCase()
                            }
                            ${chat.is_online ? '<div class="online-indicator"></div>' : ''}
                        </div>
                        <div class="chat-info">
                            <div class="chat-name">${chat.other_user_name}</div>
                            <div class="chat-details">
                                <div class="chat-detail">
                                    <i class="fas fa-comment"></i>
                                    <span>${chat.last_message_text || 'РќРµС‚ СЃРѕРѕР±С‰РµРЅРёР№'}</span>
                                </div>
                                <div class="chat-detail">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo $translations['favorite_since']; ?> ${formatDate(chat.favorite_since)}</span>
                                </div>
                            </div>
                        </div>
                        <div class="chat-actions">
                            <button class="btn btn-warning" onclick="removeFromFavorites(${chat.chat_id}, event)">
                                <i class="fas fa-star"></i>
                                <?php echo $translations['remove_from_favorites']; ?>
                            </button>
                        </div>
                    </div>
                `).join('');
                
            } catch (error) {
                console.error('Error loading favorite chats:', error);
                document.getElementById('favoriteChatsList').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>РћС€РёР±РєР° Р·Р°РіСЂСѓР·РєРё</h3>
                        <p>РџРѕРїСЂРѕР±СѓР№С‚Рµ РѕР±РЅРѕРІРёС‚СЊ СЃС‚СЂР°РЅРёС†Сѓ</p>
                    </div>
                `;
            }
        }

        // РЈРґР°Р»РµРЅРёРµ РёР· РёР·Р±СЂР°РЅРЅРѕРіРѕ
        async function removeFromFavorites(chatId, event) {
            event.stopPropagation();
            
            if (!confirm('<?php echo $translations['remove_confirm']; ?>')) {
                return;
            }
            
            try {
                const response = await fetch('personal_chats_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=toggle_favorite&chat_id=${chatId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('<?php echo $translations['remove_success']; ?>');
                    await loadFavoriteChats();
                } else {
                    alert(result.error || 'РћС€РёР±РєР°');
                }
            } catch (error) {
                console.error('Error removing from favorites:', error);
                alert('РћС€РёР±РєР°');
            }
        }

        // РћС‚РєСЂС‹С‚РёРµ С‡Р°С‚Р°
        function openChat(chatId, otherUserId) {
            window.location.href = `personal_chats.php?start_chat=${otherUserId}`;
        }

        // Р¤РѕСЂРјР°С‚РёСЂРѕРІР°РЅРёРµ РґР°С‚С‹
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // РРЅРёС†РёР°Р»РёР·Р°С†РёСЏ
        document.addEventListener('DOMContentLoaded', function() {
            loadFavoriteChats();
        });
    </script>
</body>
</html>
