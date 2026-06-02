<?php
// Включаем буферизацию вывода ДО любого кода
if (ob_get_level() == 0) {
    ob_start();
}

session_start();
require_once 'config.php';

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
    'find_users' => t('РќР°Р№С‚Рё РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№', 'Find Users', 'Encontrar UsuГЎrios', 'Trouver des Utilisateurs', 'Benutzer finden'),
    'search_users' => t('РџРѕРёСЃРє РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№...', 'Search users...', 'Pesquisar usuГЎrios...', 'Rechercher des utilisateurs...', 'Benutzer suchen...'),
    'start_chat' => t('РќР°С‡Р°С‚СЊ С‡Р°С‚', 'Start Chat', 'Iniciar Chat', 'DГ©marrer le Chat', 'Chat starten'),
    'back_to_chats' => t('Р’РµСЂРЅСѓС‚СЊСЃСЏ Рє С‡Р°С‚Р°Рј', 'Back to Chats', 'Voltar aos Chats', 'Retour aux Chats', 'ZurГјck zu Chats'),
    'no_users_found' => t('РџРѕР»СЊР·РѕРІР°С‚РµР»Рё РЅРµ РЅР°Р№РґРµРЅС‹', 'No users found', 'Nenhum usuГЎrio encontrado', 'Aucun utilisateur trouvГ©', 'Keine Benutzer gefunden'),
    'online' => t('Р’ СЃРµС‚Рё', 'Online', 'Online', 'En ligne', 'Online'),
    'offline' => t('РќРµ РІ СЃРµС‚Рё', 'Offline', 'Offline', 'Hors ligne', 'Offline'),
    'city' => t('Р“РѕСЂРѕРґ', 'City', 'Cidade', 'Ville', 'Stadt')
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations['find_users']; ?> - MigraSupport</title>
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
            max-width: 800px;
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

        .search-container {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .search-container h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: white;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
        }

        .search-box i {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-light);
            font-size: 1.2rem;
        }

        .users-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
            animation: fadeInUp 0.3s ease;
        }

        .user-card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .user-avatar {
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

        .user-avatar img {
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

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            color: white;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .user-details {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: var(--gray-light);
        }

        .user-detail {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 6px 15px rgba(58, 134, 255, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(58, 134, 255, 0.4);
        }

        .btn-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .btn-danger:hover {
            background: rgba(220, 53, 69, 0.3);
            transform: translateY(-2px);
        }

        .btn-success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .btn-success:hover {
            background: rgba(40, 167, 69, 0.3);
            transform: translateY(-2px);
        }

        .user-actions {
            display: flex;
            gap: 10px;
        }

        .user-actions .btn {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray-light);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 10px;
            }

            .search-container {
                padding: 20px;
            }

            .user-card {
                flex-direction: column;
                text-align: center;
            }

            .user-details {
                flex-direction: column;
                gap: 5px;
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

        <div class="search-container">
            <h2><?php echo $translations['find_users']; ?></h2>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="<?php echo $translations['search_users']; ?>">
                <i class="fas fa-search"></i>
            </div>
            <div class="users-list" id="usersList">
                <!-- РџРѕР»СЊР·РѕРІР°С‚РµР»Рё Р±СѓРґСѓС‚ Р·Р°РіСЂСѓР¶РµРЅС‹ С‡РµСЂРµР· JavaScript -->
            </div>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo $_SESSION['user_id']; ?>;
        let searchTimeout = null;

        // РџРѕРёСЃРє РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№
        async function searchUsers(query = '') {
            try {
                const response = await fetch(`find_users_api.php?query=${encodeURIComponent(query)}`);
                const users = await response.json();
                
                const usersList = document.getElementById('usersList');
                
                if (users.length === 0) {
                    usersList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p><?php echo $translations['no_users_found']; ?></p>
                        </div>
                    `;
                    return;
                }
                
                usersList.innerHTML = users.map(user => `
                    <div class="user-card">
                        <div class="user-avatar">
                            ${user.avatar ? 
                                `<img src="${user.avatar}" alt="${user.name}">` :
                                user.first_name.charAt(0).toUpperCase()
                            }
                            ${user.is_online ? '<div class="online-indicator"></div>' : ''}
                        </div>
                        <div class="user-info">
                            <div class="user-name">
                                ${user.name}
                                ${user.is_blocked_by_me ? '<i class="fas fa-ban" style="color: #dc3545; font-size: 0.8rem; margin-left: 5px;"></i>' : ''}
                                ${user.is_blocked_by_other ? '<span style="color: #dc3545; font-size: 0.8rem; margin-left: 5px;">Р—Р°Р±Р»РѕРєРёСЂРѕРІР°РЅ</span>' : ''}
                            </div>
                            <div class="user-details">
                                <div class="user-detail">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>${user.city_name || user.city}</span>
                                </div>
                                <div class="user-detail">
                                    <i class="fas fa-circle" style="color: ${user.is_online ? 'var(--success)' : 'var(--gray)'}"></i>
                                    <span>${user.is_online ? '<?php echo $translations['online']; ?>' : '<?php echo $translations['offline']; ?>'}</span>
                                </div>
                            </div>
                        </div>
                        <div class="user-actions">
                            <button class="btn btn-primary" onclick="startChat(${user.id})" ${user.is_blocked_by_me || user.is_blocked_by_other ? 'disabled' : ''}>
                                <i class="fas fa-paper-plane"></i>
                                <?php echo $translations['start_chat']; ?>
                            </button>
                            <button class="btn ${user.is_blocked_by_me ? 'btn-success' : 'btn-danger'}" onclick="toggleBlockInSearch(${user.id}, this)">
                                <i class="fas ${user.is_blocked_by_me ? 'fa-unlock' : 'fa-ban'}"></i>
                                ${user.is_blocked_by_me ? 'Р Р°Р·Р±Р»РѕРєРёСЂРѕРІР°С‚СЊ' : 'Р—Р°Р±Р»РѕРєРёСЂРѕРІР°С‚СЊ'}
                            </button>
                        </div>
                    </div>
                `).join('');
                
            } catch (error) {
                console.error('Error searching users:', error);
            }
        }

        // РќР°С‡Р°С‚СЊ С‡Р°С‚ СЃ РїРѕР»СЊР·РѕРІР°С‚РµР»РµРј
        async function startChat(otherUserId) {
            try {
                // РЎРЅР°С‡Р°Р»Р° РїСЂРѕРІРµСЂСЏРµРј СЃС‚Р°С‚СѓСЃ Р±Р»РѕРєРёСЂРѕРІРєРё
                const blockResponse = await fetch(`personal_chats_api.php?action=check_block_status&user_id=${otherUserId}`);
                const blockStatus = await blockResponse.json();
                
                if (blockStatus.success && blockStatus.status?.is_blocked_by_me) {
                    alert('Р’С‹ Р·Р°Р±Р»РѕРєРёСЂРѕРІР°Р»Рё СЌС‚РѕРіРѕ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ. РЎРЅР°С‡Р°Р»Р° СЂР°Р·Р±Р»РѕРєРёСЂСѓР№С‚Рµ РµРіРѕ.');
                    return;
                }
                
                if (blockStatus.success && blockStatus.status?.is_blocked_by_other) {
                    alert('Р’С‹ Р·Р°Р±Р»РѕРєРёСЂРѕРІР°РЅС‹ СЌС‚РёРј РїРѕР»СЊР·РѕРІР°С‚РµР»РµРј.');
                    return;
                }
                
                const response = await fetch('personal_chats_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=create_chat&other_user_id=${otherUserId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // РџРµСЂРµРЅР°РїСЂР°РІР»СЏРµРј РІ Р»РёС‡РЅС‹Рµ С‡Р°С‚С‹ СЃ РѕС‚РєСЂС‹С‚С‹Рј С‡Р°С‚РѕРј
                    window.location.href = `personal_chats.php?start_chat=${otherUserId}`;
                } else {
                    alert(result.error || 'РћС€РёР±РєР° СЃРѕР·РґР°РЅРёСЏ С‡Р°С‚Р°');
                }
                
            } catch (error) {
                console.error('Error starting chat:', error);
                alert('РћС€РёР±РєР° СЃРѕР·РґР°РЅРёСЏ С‡Р°С‚Р°');
            }
        }

        // Р‘Р»РѕРєРёСЂРѕРІРєР°/СЂР°Р·Р±Р»РѕРєРёСЂРѕРІРєР° РІ РїРѕРёСЃРєРµ
        async function toggleBlockInSearch(userId, button) {
            const isBlocked = button.classList.contains('btn-success');
            const message = isBlocked ? 'Р’С‹ СѓРІРµСЂРµРЅС‹, С‡С‚Рѕ С…РѕС‚РёС‚Рµ СЂР°Р·Р±Р»РѕРєРёСЂРѕРІР°С‚СЊ СЌС‚РѕРіРѕ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ?' : 'Р’С‹ СѓРІРµСЂРµРЅС‹, С‡С‚Рѕ С…РѕС‚РёС‚Рµ Р·Р°Р±Р»РѕРєРёСЂРѕРІР°С‚СЊ СЌС‚РѕРіРѕ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ?';
            
            if (!confirm(message)) {
                return;
            }
            
            try {
                const response = await fetch('personal_chats_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=toggle_block&user_id=${userId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const isBlockedNow = result.is_blocked;
                    
                    // РћР±РЅРѕРІР»СЏРµРј РєРЅРѕРїРєСѓ
                    button.className = `btn ${isBlockedNow ? 'btn-success' : 'btn-danger'}`;
                    button.innerHTML = `
                        <i class="fas ${isBlockedNow ? 'fa-unlock' : 'fa-ban'}"></i>
                        ${isBlockedNow ? 'Р Р°Р·Р±Р»РѕРєРёСЂРѕРІР°С‚СЊ' : 'Р—Р°Р±Р»РѕРєРёСЂРѕРІР°С‚СЊ'}
                    `;
                    
                    // РћР±РЅРѕРІР»СЏРµРј РєРЅРѕРїРєСѓ С‡Р°С‚Р°
                    const chatBtn = button.parentElement.querySelector('.btn-primary');
                    if (chatBtn) {
                        chatBtn.disabled = isBlockedNow;
                    }
                    
                    // РћР±РЅРѕРІР»СЏРµРј РёРєРѕРЅРєСѓ Р±Р»РѕРєРёСЂРѕРІРєРё РІ РёРјРµРЅРё
                    const userName = button.closest('.user-card').querySelector('.user-name');
                    if (userName) {
                        const banIcon = userName.querySelector('.fa-ban');
                        if (isBlockedNow && !banIcon) {
                            userName.innerHTML += '<i class="fas fa-ban" style="color: #dc3545; font-size: 0.8rem; margin-left: 5px;"></i>';
                        } else if (!isBlockedNow && banIcon) {
                            banIcon.remove();
                        }
                    }
                    
                    alert(isBlockedNow ? 'РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ Р·Р°Р±Р»РѕРєРёСЂРѕРІР°РЅ' : 'РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ СЂР°Р·Р±Р»РѕРєРёСЂРѕРІР°РЅ');
                    
                    // РћР±РЅРѕРІР»СЏРµРј РїРѕРёСЃРє С‡РµСЂРµР· 1 СЃРµРєСѓРЅРґСѓ
                    setTimeout(() => {
                        const searchInput = document.getElementById('searchInput');
                        searchUsers(searchInput.value);
                    }, 1000);
                } else {
                    alert(result.error || 'РћС€РёР±РєР°');
                }
            } catch (error) {
                console.error('Error toggling block:', error);
                alert('РћС€РёР±РєР°');
            }
        }

        // РћР±СЂР°Р±РѕС‚РєР° РІРІРѕРґР° РїРѕРёСЃРєР°
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const query = e.target.value.trim();
            
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            searchTimeout = setTimeout(() => {
                searchUsers(query);
            }, 300);
        });

        // РРЅРёС†РёР°Р»РёР·Р°С†РёСЏ
        document.addEventListener('DOMContentLoaded', function() {
            searchUsers(); // Р—Р°РіСЂСѓР¶Р°РµРј РІСЃРµС… РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№ РїСЂРё Р·Р°РіСЂСѓР·РєРµ
        });
    </script>
</body>
</html>

