<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // ОБРАБОТКА ВХОДА
        if ($_POST['action'] === 'login') {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] === 'active') {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_type'] = $user['user_type'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['city'] = $user['city'] ?? 'minsk'; // Добавляем город в сессию
                        
                        if ($user['user_type'] === 'admin') {
                            header('Location: dashboard.php');
                        } else {
                            header('Location: index.php');
                        }
                        exit;
                    } else {
                        $_SESSION['error'] = 'Ваш аккаунт ожидает подтверждения администратора';
                        header('Location: index.php#login');
                        exit;
                    }
                } else {
                    $_SESSION['error'] = 'Неверное имя пользователя или пароль';
                    header('Location: index.php#login');
                    exit;
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Ошибка базы данных: ' . $e->getMessage();
                header('Location: index.php#login');
                exit;
            }
        }
        
        // ОБРАБОТКА РЕГИСТРАЦИИ
        if ($_POST['action'] === 'register') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $phone = trim($_POST['phone'] ?? '');
            $country_of_origin = trim($_POST['country_of_origin']);
            $passport_number = trim($_POST['passport_number']);
            $city = trim($_POST['city'] ?? 'minsk');
            
            try {
                // Проверяем, не занят ли username
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'Пользователь с таким именем уже существует';
                    header('Location: index.php#register');
                    exit;
                }
                
                // Проверяем, не занят ли email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'Пользователь с таким email уже существует';
                    header('Location: index.php#register');
                    exit;
                }
                
                // Проверяем, не занят ли номер паспорта
                $stmt = $pdo->prepare("SELECT id FROM users WHERE passport_number = ?");
                $stmt->execute([$passport_number]);
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'Пользователь с таким номером паспорта уже зарегистрирован';
                    header('Location: index.php#register');
                    exit;
                }
                
                // Создаем нового пользователя
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone, country_of_origin, passport_number, city, user_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'migrant', 'pending')");
                $stmt->execute([$username, $email, $password, $first_name, $last_name, $phone, $country_of_origin, $passport_number, $city]);
                
                $_SESSION['success'] = 'Регистрация успешна! Ваш аккаунт ожидает подтверждения администратора.';
                header('Location: index.php#login');
                exit;
                
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['error'] = 'Ошибка: такие данные уже существуют в системе';
                } else {
                    $_SESSION['error'] = 'Ошибка при регистрации: ' . $e->getMessage();
                }
                header('Location: index.php#register');
                exit;
            }
        }
        
        // Добавьте этот код в auth.php после других обработок POST
        if ($_POST['action'] === 'assign_chat') {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
                echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
                exit;
            }
            
            $chat_id = $_POST['chat_id'];
            
            try {
                $stmt = $pdo->prepare("UPDATE admin_chats SET admin_id = ?, status = 'open' WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $chat_id]);
                
                echo json_encode(['success' => true]);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()]);
            }
            exit;
        }
        
        // ОБРАБОТКА СООБЩЕНИЙ ГОРОДСКОГО ЧАТА
        if ($_POST['action'] === 'send_city_message') {
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
                exit;
            }
            
            $message = trim($_POST['message']);
            $city = trim($_POST['city']);
            $user_id = $_SESSION['user_id'];
            
            if (empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Сообщение не может быть пустым']);
                exit;
            }
            
            try {
                // Добавляем сообщение в городской чат
                $stmt = $pdo->prepare("INSERT INTO city_chat_messages (city, sender_id, message_text) VALUES (?, ?, ?)");
                $stmt->execute([$city, $user_id, $message]);
                
                echo json_encode(['success' => true]);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()]);
            }
            exit;
        }
        
        // ОБРАБОТКА СООБЩЕНИЙ ЧАТА С АДМИНИСТРАЦИЕЙ
        if ($_POST['action'] === 'send_admin_message') {
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
                exit;
            }
            
            $message = trim($_POST['message']);
            $user_id = $_SESSION['user_id'];
            
            if (empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Сообщение не может быть пустым']);
                exit;
            }
            
            try {
                // Ищем активный чат с администрацией
                $stmt = $pdo->prepare("SELECT id FROM admin_chats WHERE user_id = ? AND status = 'open' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$user_id]);
                $chat = $stmt->fetch();
                
                $chat_id = null;
                
                if ($chat) {
                    $chat_id = $chat['id'];
                } else {
                    // Создаем новый чат с администрацией
                    $subject = "Чат от " . $_SESSION['first_name'] . " " . $_SESSION['last_name'];
                    $stmt = $pdo->prepare("INSERT INTO admin_chats (user_id, subject) VALUES (?, ?)");
                    $stmt->execute([$user_id, $subject]);
                    $chat_id = $pdo->lastInsertId();
                }
                
                // Добавляем сообщение
                $stmt = $pdo->prepare("INSERT INTO admin_chat_messages (chat_id, sender_id, message_text) VALUES (?, ?, ?)");
                $stmt->execute([$chat_id, $user_id, $message]);
                
                echo json_encode(['success' => true]);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()]);
            }
            exit;
        }
    }
}

header('Location: index.php');
exit;
?>