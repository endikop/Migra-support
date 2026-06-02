<?php
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
    'blocked_users' => t('Р—Р°Р±Р»РѕРєРёСЂРѕРІР°РЅРЅС‹Рµ РїРѕР»СЊР·РѕРІР°С‚РµР»Рё', 'Blocked Users', 'UsuГЎrios Bloqueados', 'Utilisateurs BloquГ©s', 'Blockierte Benutzer'),
    'back_to_chats' => t('Р’РµСЂРЅСѓС‚СЊСЃСЏ Рє С‡Р°С‚Р°Рј', 'Back to Chats', 'Voltar aos Chats', 'Retour aux Chats', 'ZurГјck zu Chats'),
    'no_blocked_users' => t('РЈ РІР°СЃ РЅРµС‚ Р·Р°Р±Р»РѕРєРёСЂРѕРІР°РЅРЅС‹С… РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№', 'You have no blocked users', 'VocГЄ nГЈo tem usuГЎrios bloqueados', 'Vous n\'avez pas d\'utilisateurs bloquГ©s', 'Sie haben keine blockierten Benutzer'),
    'unblock' => t('Р Р°Р·Р±Р»РѕРєРёСЂРѕРІР°С‚СЊ', 'Unblock', 'Desbloquear', 'DГ©bloquer', 'Entsperren'),
    'blocked_since' => t('Р—Р°Р±Р»РѕРєРёСЂРѕРІР°РЅ СЃ', 'Blocked since', 'Bloqueado desde', 'BloquГ© depuis', 'Gesperrt seit'),
    'unblock_confirm' => t('Р’С‹ СѓРІРµСЂРµРЅС‹, С‡С‚Рѕ С…РѕС‚РёС‚Рµ СЂР°Р·Р±Р»РѕРєРёСЂРѕРІР°С‚СЊ СЌС‚РѕРіРѕ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ?', 'Are you sure you want to unblock this user?', 'Tem certeza de que deseja desbloquear este usuГЎrio?', 'ГЉtes-vous sГ»r de vouloir dГ©bloquer cet utilisateur?', 'Sind Sie sicher, dass Sie diesen Benutzer entsperren mГ¶chten?'),
    'unblock_success' => t('РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ СЂР°Р·Р±Р»РѕРєРёСЂРѕРІР°РЅ', 'User unblocked', 'UsuГЎrio desbloqueado', 'Utilisateur dГ©bloquГ©', 'Benutzer entsperrt')
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations['blocked_users']; ?> - MigraSupport</title>
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

        .blocked-users-list {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            margin-bottom: 10px;
            transition: var(--transition);
        }

        .user-card:hover {
            background: rgba(255, 255, 255, 0.08);
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
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
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

        .user-actions {
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

        .btn-success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .btn-success:hover {
            background: rgba(40, 167, 69, 0.3);
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
            .user-card {
                flex-direction: column;
                text-align: center;
            }

            .user-actions {
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
            <h1><?php echo $translations['blocked_users']; ?></h1>
            <p>РЈРїСЂР°РІР»РµРЅРёРµ Р·Р°Р±Р»РѕРєРёСЂРѕРІР°РЅРЅС‹РјРё РїРѕР»СЊР·РѕРІР°С‚РµР»СЏРјРё</p>
        </div>

        <div class="blocked-users-list" id="blockedUsersList">
            <!-- РЎРїРёСЃРѕРє Р·Р°Р±Р»РѕРєРёСЂРѕРІР°РЅРЅС‹С… РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№ Р±СѓРґРµС‚ Р·Р°РіСЂСѓР¶РµРЅ С‡РµСЂРµР· JavaScript -->
            <div class="empty-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Р—Р°РіСЂСѓР·РєР°...</p>
            </div>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo $_SESSION['user_id']; ?>;

        // Р—Р°РіСЂСѓР·РєР° СЃРїРёСЃРєР° Р·Р°Р±Р»РѕРєРёСЂРѕРІР°РЅРЅС‹С… РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№
        async function loadBlockedUsers() {
            try {
                const response = await fetch('blocked_users_api.php?action=get_blocked');
                const users = await response.json();
                
                const usersList = document.getElementById('blockedUsersList');
                
                if (!users.length) {
                    usersList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-ban"></i>
                            <h3><?php echo $translations['no_blocked_users']; ?></h3>
                        </div>
                    `;
                    return;
                }
                
                usersList.innerHTML = users.map(user => `
                    <div class="user-card" data-user-id="${user.id}">
                        <div class="user-avatar">
                            ${user.avatar ? 
                                `<img src="${user.avatar}" alt="${user.name}">` :
                                user.first_name.charAt(0).toUpperCase()
                            }
                        </div>
                        <div class="user-info">
                            <div class="user-name">${user.name}</div>
                            <div class="user-details">
                                <div class="user-detail">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo $translations['blocked_since']; ?> ${formatDate(user.blocked_since)}</span>
                                </div>
                            </div>
                        </div>
                        <div class="user-actions">
                            <button class="btn btn-success" onclick="unblockUser(${user.id})">
                                <i class="fas fa-unlock"></i>
                                <?php echo $translations['unblock']; ?>
                            </button>
                        </div>
                    </div>
                `).join('');
                
            } catch (error) {
                console.error('Error loading blocked users:', error);
                document.getElementById('blockedUsersList').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>РћС€РёР±РєР° Р·Р°РіСЂСѓР·РєРё</h3>
                        <p>РџРѕРїСЂРѕР±СѓР№С‚Рµ РѕР±РЅРѕРІРёС‚СЊ СЃС‚СЂР°РЅРёС†Сѓ</p>
                    </div>
                `;
            }
        }

        // Р Р°Р·Р±Р»РѕРєРёСЂРѕРІРєР° РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
        async function unblockUser(userId) {
            if (!confirm('<?php echo $translations['unblock_confirm']; ?>')) {
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
                    alert('<?php echo $translations['unblock_success']; ?>');
                    await loadBlockedUsers();
                } else {
                    alert(result.error || 'РћС€РёР±РєР°');
                }
            } catch (error) {
                console.error('Error unblocking user:', error);
                alert('РћС€РёР±РєР°');
            }
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
            loadBlockedUsers();
        });
    </script>
</body>
</html>
