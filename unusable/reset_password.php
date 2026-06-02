<?php
session_start();
require_once '../src/config/config.php';

$lang = $_COOKIE['lang'] ?? 'ru';

function t($ru, $en) {
    global $lang;
    return ($lang === 'en') ? $en : $ru;
}

$translations = [
    'title' => t('РЎР±СЂРѕСЃ РїР°СЂРѕР»СЏ - MigraSupport', 'Reset password - MigraSupport'),
    'reset_title' => t('РЎР±СЂРѕСЃ РїР°СЂРѕР»СЏ', 'Reset password'),
    'reset_desc' => t('Р’РІРµРґРёС‚Рµ РЅРѕРІС‹Р№ РїР°СЂРѕР»СЊ РґР»СЏ РІР°С€РµРіРѕ Р°РєРєР°СѓРЅС‚Р°.', 'Enter a new password for your account.'),
    'new_password' => t('РќРѕРІС‹Р№ РїР°СЂРѕР»СЊ', 'New password'),
    'confirm_password' => t('РџРѕРґС‚РІРµСЂРґРёС‚Рµ РїР°СЂРѕР»СЊ', 'Confirm password'),
    'reset_btn' => t('РЎР±СЂРѕСЃРёС‚СЊ РїР°СЂРѕР»СЊ', 'Reset password'),
    'back_to_login' => t('Р’РµСЂРЅСѓС‚СЊСЃСЏ Рє РІС…РѕРґСѓ', 'Back to login'),
    'password_reset_success' => t('РџР°СЂРѕР»СЊ СѓСЃРїРµС€РЅРѕ СЃР±СЂРѕС€РµРЅ!', 'Password successfully reset!'),
    'login_now' => t('Р’РѕР№С‚Рё СЃРµР№С‡Р°СЃ', 'Login now'),
    'invalid_token' => t('РќРµРІРµСЂРЅР°СЏ РёР»Рё СѓСЃС‚Р°СЂРµРІС€Р°СЏ СЃСЃС‹Р»РєР°', 'Invalid or expired link'),
    'passwords_not_match' => t('РџР°СЂРѕР»Рё РЅРµ СЃРѕРІРїР°РґР°СЋС‚', 'Passwords do not match'),
    'password_too_short' => t('РџР°СЂРѕР»СЊ РґРѕР»Р¶РµРЅ Р±С‹С‚СЊ РЅРµ РјРµРЅРµРµ 6 СЃРёРјРІРѕР»РѕРІ', 'Password must be at least 6 characters')
];

$token = $_GET['token'] ?? '';
$step = 1; // 1 - РІРІРѕРґ РїР°СЂРѕР»СЏ, 2 - СѓСЃРїРµС…, 3 - РѕС€РёР±РєР°
$error = '';
$user_id = null;

// РџСЂРѕРІРµСЂСЏРµРј С‚РѕРєРµРЅ
if ($token) {
    try {
        $stmt = $pdo->prepare("
            SELECT user_id FROM password_resets 
            WHERE token = ? AND expires_at > NOW() AND used = 0
        ");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if ($reset) {
            $user_id = $reset['user_id'];
        } else {
            $step = 3;
            $error = $translations['invalid_token'];
        }
    } catch (PDOException $e) {
        $step = 3;
        $error = t('РћС€РёР±РєР° Р±Р°Р·С‹ РґР°РЅРЅС‹С…', 'Database error');
    }
} else {
    $step = 3;
    $error = $translations['invalid_token'];
}

// РћР±СЂР°Р±РѕС‚РєР° СЃР±СЂРѕСЃР° РїР°СЂРѕР»СЏ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = $translations['password_too_short'];
    } elseif ($password !== $confirm_password) {
        $error = $translations['passwords_not_match'];
    } else {
        try {
            // РҐРµС€РёСЂСѓРµРј РЅРѕРІС‹Р№ РїР°СЂРѕР»СЊ
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // РћР±РЅРѕРІР»СЏРµРј РїР°СЂРѕР»СЊ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$password_hash, $user_id]);
            
            // РџРѕРјРµС‡Р°РµРј С‚РѕРєРµРЅ РєР°Рє РёСЃРїРѕР»СЊР·РѕРІР°РЅРЅС‹Р№
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            $step = 2;
        } catch (PDOException $e) {
            $error = t('РћС€РёР±РєР° Р±Р°Р·С‹ РґР°РЅРЅС‹С…', 'Database error');
        }
    }
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
        .reset-container {
            max-width: 500px;
            margin: 100px auto;
            animation: fadeInUp 0.6s ease;
        }

        .reset-card {
            background: rgba(26, 26, 46, 0.7);
            border-radius: var(--radius-lg);
            padding: 50px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: var(--shadow-xl);
        }

        .reset-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 2.5rem;
        }

        .reset-icon.success {
            background: rgba(56, 176, 0, 0.2);
            color: var(--success);
            border: 3px solid rgba(56, 176, 0, 0.3);
        }

        .reset-icon.error {
            background: rgba(255, 0, 84, 0.2);
            color: var(--danger);
            border: 3px solid rgba(255, 0, 84, 0.3);
        }

        .reset-icon.default {
            background: rgba(58, 134, 255, 0.2);
            color: var(--primary);
            border: 3px solid rgba(58, 134, 255, 0.3);
        }

        .reset-title {
            color: white;
            font-size: 2rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .reset-desc {
            color: var(--gray-light);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .reset-form {
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: white;
            font-size: 1rem;
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
        }

        .reset-button {
            width: 100%;
            padding: 16px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .reset-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(58, 134, 255, 0.3);
        }

        .back-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.95rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-link:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        .success-message {
            background: rgba(56, 176, 0, 0.1);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--success);
            text-align: left;
        }

        .success-message i {
            color: var(--success);
            margin-right: 10px;
        }

        .error-message {
            background: rgba(255, 0, 84, 0.1);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--danger);
            text-align: left;
        }

        .error-message i {
            color: var(--danger);
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .reset-container {
                margin: 50px auto;
                padding: 0 20px;
            }

            .reset-card {
                padding: 30px;
            }

            .reset-title {
                font-size: 1.8rem;
            }

            .reset-desc {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <?php if ($step === 1): ?>
                <div class="reset-icon default">
                    <i class="fas fa-key"></i>
                </div>
                
                <h1 class="reset-title"><?php echo $translations['reset_title']; ?></h1>
                <p class="reset-desc"><?php echo $translations['reset_desc']; ?></p>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="reset-form">
                    <div class="form-group">
                        <label class="form-label"><?php echo $translations['new_password']; ?></label>
                        <input type="password" name="password" class="form-input" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?php echo $translations['confirm_password']; ?></label>
                        <input type="password" name="confirm_password" class="form-input" required minlength="6">
                    </div>
                    
                    <button type="submit" class="reset-button">
                        <i class="fas fa-redo"></i>
                        <?php echo $translations['reset_btn']; ?>
                    </button>
                </form>
                
                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <?php echo $translations['back_to_login']; ?>
                </a>
                
            <?php elseif ($step === 2): ?>
                <div class="reset-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h1 class="reset-title"><?php echo $translations['password_reset_success']; ?></h1>
                
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <p><?php echo t('РўРµРїРµСЂСЊ РІС‹ РјРѕР¶РµС‚Рµ РІРѕР№С‚Рё СЃ РЅРѕРІС‹Рј РїР°СЂРѕР»РµРј.', 'You can now login with your new password.'); ?></p>
                </div>
                
                <a href="login.php" class="reset-button" style="text-decoration: none; width: auto; display: inline-block;">
                    <i class="fas fa-sign-in-alt"></i>
                    <?php echo $translations['login_now']; ?>
                </a>
                
            <?php elseif ($step === 3): ?>
                <div class="reset-icon error">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                
                <h1 class="reset-title"><?php echo t('РћС€РёР±РєР°', 'Error'); ?></h1>
                
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <p><?php echo $error; ?></p>
                </div>
                
                <a href="forgot_password.php" class="back-link">
                    <i class="fas fa-redo"></i>
                    <?php echo t('Р—Р°РїСЂРѕСЃРёС‚СЊ РЅРѕРІСѓСЋ СЃСЃС‹Р»РєСѓ', 'Request new link'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
