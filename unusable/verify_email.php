<?php
session_start();
require_once '../src/config/config.php';

$lang = $_COOKIE['lang'] ?? 'ru';

function t($ru, $en) {
    global $lang;
    return ($lang === 'en') ? $en : $ru;
}

$translations = [
    'title' => t('РџРѕРґС‚РІРµСЂР¶РґРµРЅРёРµ email - MigraSupport', 'Email verification - MigraSupport'),
    'verification_success' => t('Email СѓСЃРїРµС€РЅРѕ РїРѕРґС‚РІРµСЂР¶РґРµРЅ!', 'Email successfully verified!'),
    'verification_failed' => t('РћС€РёР±РєР° РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ email', 'Email verification failed'),
    'invalid_code' => t('РќРµРІРµСЂРЅС‹Р№ РёР»Рё СѓСЃС‚Р°СЂРµРІС€РёР№ РєРѕРґ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ', 'Invalid or expired verification code'),
    'go_to_profile' => t('РџРµСЂРµР№С‚Рё РІ РїСЂРѕС„РёР»СЊ', 'Go to profile'),
    'return_home' => t('Р’РµСЂРЅСѓС‚СЊСЃСЏ РЅР° РіР»Р°РІРЅСѓСЋ', 'Return to home')
];

$success = false;
$message = '';

if (isset($_GET['code'])) {
    $verification_code = $_GET['code'];
    
    try {
        // РџСЂРѕРІРµСЂСЏРµРј РєРѕРґ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ
        $stmt = $pdo->prepare("
            SELECT id, email, created_at 
            FROM users 
            WHERE email_verification_code = ? 
            AND status = 'pending'
        ");
        $stmt->execute([$verification_code]);
        $user = $stmt->fetch();
        
        if ($user) {
            // РџСЂРѕРІРµСЂСЏРµРј, РЅРµ РёСЃС‚РµРє Р»Рё СЃСЂРѕРє РґРµР№СЃС‚РІРёСЏ РєРѕРґР° (24 С‡Р°СЃР°)
            $code_age = time() - strtotime($user['created_at']);
            if ($code_age <= 86400) { // 24 С‡Р°СЃР° РІ СЃРµРєСѓРЅРґР°С…
                // РћР±РЅРѕРІР»СЏРµРј СЃС‚Р°С‚СѓСЃ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET status = 'active', 
                        email_verified_at = NOW(),
                        email_verification_code = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$user['id']]);
                
                $success = true;
                $message = t('Р’Р°С€ email СѓСЃРїРµС€РЅРѕ РїРѕРґС‚РІРµСЂР¶РґРµРЅ. РўРµРїРµСЂСЊ Сѓ РІР°СЃ РµСЃС‚СЊ РїРѕР»РЅС‹Р№ РґРѕСЃС‚СѓРї Рє СЃРёСЃС‚РµРјРµ.', 'Your email has been successfully verified. You now have full access to the system.');
                
                // РђРІС‚РѕРјР°С‚РёС‡РµСЃРєРё Р»РѕРіРёРЅРёРј РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ РµСЃР»Рё РЅРµ Р·Р°Р»РѕРіРёРЅРµРЅ
                if (!isset($_SESSION['user_id'])) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $user_data = $stmt->fetch();
                    
                    $_SESSION['user_id'] = $user_data['id'];
                    $_SESSION['username'] = $user_data['username'];
                    $_SESSION['first_name'] = $user_data['first_name'];
                    $_SESSION['last_name'] = $user_data['last_name'];
                    $_SESSION['email'] = $user_data['email'];
                    $_SESSION['city'] = $user_data['city'];
                    $_SESSION['user_type'] = $user_data['user_type'];
                    $_SESSION['status'] = 'active';
                }
            } else {
                $message = t('РЎСЂРѕРє РґРµР№СЃС‚РІРёСЏ РєРѕРґР° РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ РёСЃС‚РµРє. РџРѕР¶Р°Р»СѓР№СЃС‚Р°, Р·Р°РїСЂРѕСЃРёС‚Рµ РЅРѕРІС‹Р№ РєРѕРґ.', 'The verification code has expired. Please request a new one.');
            }
        } else {
            $message = t('РќРµРІРµСЂРЅС‹Р№ РєРѕРґ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ.', 'Invalid verification code.');
        }
    } catch (PDOException $e) {
        $message = t('РћС€РёР±РєР° Р±Р°Р·С‹ РґР°РЅРЅС‹С…. РџРѕР¶Р°Р»СѓР№СЃС‚Р°, РїРѕРїСЂРѕР±СѓР№С‚Рµ РїРѕР·Р¶Рµ.', 'Database error. Please try again later.');
    }
} else {
    $message = t('РљРѕРґ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ РЅРµ СѓРєР°Р·Р°РЅ.', 'Verification code not specified.');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations['title']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .verification-container {
            max-width: 600px;
            margin: 100px auto;
            animation: fadeInUp 0.6s ease;
        }

        .verification-card {
            background: rgba(26, 26, 46, 0.7);
            border-radius: var(--radius-lg);
            padding: 50px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: var(--shadow-xl);
        }

        .verification-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 2.5rem;
        }

        .success .verification-icon {
            background: rgba(56, 176, 0, 0.2);
            color: var(--success);
            border: 3px solid rgba(56, 176, 0, 0.3);
        }

        .error .verification-icon {
            background: rgba(255, 0, 84, 0.2);
            color: var(--danger);
            border: 3px solid rgba(255, 0, 84, 0.3);
        }

        .verification-title {
            color: white;
            font-size: 2rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .verification-message {
            color: var(--gray-light);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .verification-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }

        @media (max-width: 768px) {
            .verification-container {
                margin: 50px auto;
                padding: 0 20px;
            }

            .verification-card {
                padding: 30px;
            }

            .verification-title {
                font-size: 1.8rem;
            }

            .verification-message {
                font-size: 1rem;
            }

            .verification-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-card <?php echo $success ? 'success' : 'error'; ?>">
            <div class="verification-icon">
                <?php if ($success): ?>
                    <i class="fas fa-check-circle"></i>
                <?php else: ?>
                    <i class="fas fa-exclamation-circle"></i>
                <?php endif; ?>
            </div>
            
            <h1 class="verification-title">
                <?php echo $success ? $translations['verification_success'] : $translations['verification_failed']; ?>
            </h1>
            
            <div class="verification-message">
                <?php echo $message; ?>
            </div>
            
            <div class="verification-actions">
                <?php if ($success): ?>
                    <a href="profile.php" class="btn btn-primary">
                        <i class="fas fa-user"></i> <?php echo $translations['go_to_profile']; ?>
                    </a>
                <?php endif; ?>
                
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-home"></i> <?php echo $translations['return_home']; ?>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
