<?php
session_start();
require_once '../src/config/config.php';

// Р•СЃР»Рё РїРѕР»СЊР·РѕРІР°С‚РµР»СЊ СѓР¶Рµ Р°РІС‚РѕСЂРёР·РѕРІР°РЅ, РїРµСЂРµРЅР°РїСЂР°РІР»СЏРµРј РЅР° РїСЂРѕС„РёР»СЊ
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit();
}

$lang = $_COOKIE['lang'] ?? 'ru';

function t($ru, $en) {
    global $lang;
    return ($lang === 'en') ? $en : $ru;
}

$translations = [
    'title' => t('Р’РѕСЃСЃС‚Р°РЅРѕРІР»РµРЅРёРµ РїР°СЂРѕР»СЏ - MigraSupport', 'Password recovery - MigraSupport'),
    'recovery_title' => t('Р’РѕСЃСЃС‚Р°РЅРѕРІР»РµРЅРёРµ РїР°СЂРѕР»СЏ', 'Password recovery'),
    'recovery_desc' => t('Р’РІРµРґРёС‚Рµ РІР°С€ email, Рё РјС‹ РІС‹С€Р»РµРј РёРЅСЃС‚СЂСѓРєС†РёРё РїРѕ РІРѕСЃСЃС‚Р°РЅРѕРІР»РµРЅРёСЋ РїР°СЂРѕР»СЏ.', 'Enter your email and we will send you password recovery instructions.'),
    'email' => t('Email', 'Email'),
    'send_instructions' => t('РћС‚РїСЂР°РІРёС‚СЊ РёРЅСЃС‚СЂСѓРєС†РёРё', 'Send instructions'),
    'back_to_login' => t('Р’РµСЂРЅСѓС‚СЊСЃСЏ Рє РІС…РѕРґСѓ', 'Back to login'),
    'instructions_sent' => t('РРЅСЃС‚СЂСѓРєС†РёРё РѕС‚РїСЂР°РІР»РµРЅС‹', 'Instructions sent'),
    'check_email' => t('РџСЂРѕРІРµСЂСЊС‚Рµ РІР°С€ email РґР»СЏ РїРѕР»СѓС‡РµРЅРёСЏ РёРЅСЃС‚СЂСѓРєС†РёР№ РїРѕ РІРѕСЃСЃС‚Р°РЅРѕРІР»РµРЅРёСЋ РїР°СЂРѕР»СЏ.', 'Check your email for password recovery instructions.'),
    'email_not_found' => t('РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ СЃ С‚Р°РєРёРј email РЅРµ РЅР°Р№РґРµРЅ', 'User with this email not found'),
    'error_sending' => t('РћС€РёР±РєР° РѕС‚РїСЂР°РІРєРё email. РџРѕРїСЂРѕР±СѓР№С‚Рµ РїРѕР·Р¶Рµ.', 'Error sending email. Try again later.')
];

$step = 1; // 1 - РІРІРѕРґ email, 2 - РёРЅСЃС‚СЂСѓРєС†РёРё РѕС‚РїСЂР°РІР»РµРЅС‹, 3 - СЃР±СЂРѕСЃ РїР°СЂРѕР»СЏ
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    try {
        $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Р“РµРЅРµСЂРёСЂСѓРµРј С‚РѕРєРµРЅ СЃР±СЂРѕСЃР° РїР°СЂРѕР»СЏ
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 С‡Р°СЃ
            
            $stmt = $pdo->prepare("
                INSERT INTO password_resets (user_id, token, expires_at, created_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    token = VALUES(token),
                    expires_at = VALUES(expires_at),
                    created_at = NOW()
            ");
            $stmt->execute([$user['id'], $token, $expires]);
            
            // РћС‚РїСЂР°РІР»СЏРµРј email
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
            
            $subject = t('Р’РѕСЃСЃС‚Р°РЅРѕРІР»РµРЅРёРµ РїР°СЂРѕР»СЏ - MigraSupport', 'Password recovery - MigraSupport');
            
            $message = "
            <html>
            <head>
                <title>" . t('Р’РѕСЃСЃС‚Р°РЅРѕРІР»РµРЅРёРµ РїР°СЂРѕР»СЏ', 'Password recovery') . "</title>
            </head>
            <body>
                <h2>" . t('Р—РґСЂР°РІСЃС‚РІСѓР№С‚Рµ', 'Hello') . ", " . $user['first_name'] . "!</h2>
                <p>" . t('РњС‹ РїРѕР»СѓС‡РёР»Рё Р·Р°РїСЂРѕСЃ РЅР° СЃР±СЂРѕСЃ РїР°СЂРѕР»СЏ РґР»СЏ РІР°С€РµРіРѕ Р°РєРєР°СѓРЅС‚Р°.', 'We received a password reset request for your account.') . "</p>
                <p>" . t('Р”Р»СЏ СЃР±СЂРѕСЃР° РїР°СЂРѕР»СЏ РїРµСЂРµР№РґРёС‚Рµ РїРѕ СЃСЃС‹Р»РєРµ:', 'To reset your password, click the link:') . "</p>
                <p><a href='" . $reset_link . "'>" . $reset_link . "</a></p>
                <p>" . t('РЎСЃС‹Р»РєР° РґРµР№СЃС‚РІРёС‚РµР»СЊРЅР° РІ С‚РµС‡РµРЅРёРµ 1 С‡Р°СЃР°.', 'The link is valid for 1 hour.') . "</p>
                <p>" . t('Р•СЃР»Рё РІС‹ РЅРµ Р·Р°РїСЂР°С€РёРІР°Р»Рё СЃР±СЂРѕСЃ РїР°СЂРѕР»СЏ, РїСЂРѕРёРіРЅРѕСЂРёСЂСѓР№С‚Рµ СЌС‚Рѕ РїРёСЃСЊРјРѕ.', 'If you did not request a password reset, please ignore this email.') . "</p>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: no-reply@migrasupport.by" . "\r\n";
            
            if (mail($email, $subject, $message, $headers)) {
                $step = 2;
            } else {
                $error = $translations['error_sending'];
            }
        } else {
            $error = $translations['email_not_found'];
        }
    } catch (PDOException $e) {
        $error = t('РћС€РёР±РєР° Р±Р°Р·С‹ РґР°РЅРЅС‹С…', 'Database error');
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
        .recovery-container {
            max-width: 500px;
            margin: 100px auto;
            animation: fadeInUp 0.6s ease;
        }

        .recovery-card {
            background: rgba(26, 26, 46, 0.7);
            border-radius: var(--radius-lg);
            padding: 50px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: var(--shadow-xl);
        }

        .recovery-icon {
            width: 80px;
            height: 80px;
            background: rgba(58, 134, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 2.5rem;
            color: var(--primary);
            border: 3px solid rgba(58, 134, 255, 0.3);
        }

        .recovery-title {
            color: white;
            font-size: 2rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .recovery-desc {
            color: var(--gray-light);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .recovery-form {
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: white;
            font-size: 1rem;
            text-align: left;
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

        .recovery-button {
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

        .recovery-button:hover {
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
            .recovery-container {
                margin: 50px auto;
                padding: 0 20px;
            }

            .recovery-card {
                padding: 30px;
            }

            .recovery-title {
                font-size: 1.8rem;
            }

            .recovery-desc {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <div class="recovery-card">
            <div class="recovery-icon">
                <i class="fas fa-key"></i>
            </div>
            
            <h1 class="recovery-title"><?php echo $translations['recovery_title']; ?></h1>
            
            <?php if ($step === 1): ?>
                <p class="recovery-desc"><?php echo $translations['recovery_desc']; ?></p>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="recovery-form">
                    <div class="form-group">
                        <label class="form-label"><?php echo $translations['email']; ?></label>
                        <input type="email" name="email" class="form-input" required autofocus>
                    </div>
                    
                    <button type="submit" class="recovery-button">
                        <i class="fas fa-paper-plane"></i>
                        <?php echo $translations['send_instructions']; ?>
                    </button>
                </form>
                
                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <?php echo $translations['back_to_login']; ?>
                </a>
                
            <?php elseif ($step === 2): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $translations['instructions_sent']; ?></h3>
                    <p><?php echo $translations['check_email']; ?></p>
                </div>
                
                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <?php echo $translations['back_to_login']; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
