<?php
session_start();
require_once 'config.php';

// Подключаем файл с данными аватара
require_once 'include_avatar.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $image = '';

        if (empty($title) || empty($content)) {
            $error = 'Заголовок и содержание обязательны.';
        } else {
            // Загрузка изображения
            if (!empty($_FILES['image']['name'])) {
                $uploadDir = 'img/news/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp','gif'];
                if (!in_array($ext, $allowed)) {
                    $error = 'Недопустимый формат изображения.';
                } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                    $error = 'Файл слишком большой (макс. 5 МБ).';
                } else {
                    $filename = uniqid('news_') . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
                    $image = $uploadDir . $filename;
                }
            }

            if (empty($error)) {
                try {
                    if ($action === 'create') {
                        $stmt = $pdo->prepare("INSERT INTO news (title, content, image, author_id, is_active) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $content, $image ?: null, $_SESSION['user_id'], $is_active]);
                        $success = 'Новость успешно создана.';
                    } else {
                        $id = (int)$_POST['id'];
                        if (!empty($image)) {
                            $stmt = $pdo->prepare("UPDATE news SET title=?, content=?, image=?, is_active=? WHERE id=?");
                            $stmt->execute([$title, $content, $image, $is_active, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE news SET title=?, content=?, is_active=? WHERE id=?");
                            $stmt->execute([$title, $content, $is_active, $id]);
                        }
                        $success = 'Новость успешно обновлена.';
                    }
                } catch (PDOException $e) {
                    $error = 'Ошибка БД: ' . $e->getMessage();
                }
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        try {
            // Удаляем изображение если есть
            $stmt = $pdo->prepare("SELECT image FROM news WHERE id=?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && !empty($row['image']) && file_exists($row['image'])) {
                unlink($row['image']);
            }
            $pdo->prepare("DELETE FROM news WHERE id=?")->execute([$id]);
            $success = 'Новость удалена.';
        } catch (PDOException $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        try {
            $pdo->prepare("UPDATE news SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
            $success = 'Статус новости изменён.';
        } catch (PDOException $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}

// Получаем новость для редактирования
$editNews = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editNews = $stmt->fetch();
}

// Список всех новостей
try {
    $stmt = $pdo->query("SELECT n.*, u.first_name, u.last_name FROM news n LEFT JOIN users u ON n.author_id = u.id ORDER BY n.created_at DESC");
    $newsList = $stmt->fetchAll();
} catch (PDOException $e) {
    $newsList = [];
    $error = 'Ошибка загрузки новостей: ' . $e->getMessage();
}

// Получаем имя и аватар пользователя
$userName = isset($userName) ? $userName : 'Администратор';
$userAvatar = isset($userAvatar) ? $userAvatar : null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление новостями | Админ-панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Универсальные анимации -->
    <?php include_once 'include_animations.php'; ?>
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --danger-color: #7209b7;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --border-color: #dee2e6;
            --sidebar-width: 250px;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
        body { background:#f5f7fb; color:var(--dark-color); display:flex; min-height:100vh; }

        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
            z-index: 100;
        }
        .logo { padding: 0 20px 30px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .logo h2 { font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .logo h2 i { color: #4cc9f0; }
        .nav-menu { list-style: none; padding: 0; }
        .nav-menu li { margin-bottom: 5px; }
        .nav-menu a { display:flex; align-items:center; gap:12px; padding:15px 20px; color:rgba(255,255,255,0.9); text-decoration:none; transition:all 0.3s; font-weight:500; }
        .nav-menu a:hover, .nav-menu a.active { background:rgba(255,255,255,0.1); color:white; border-left:4px solid #4cc9f0; }
        .nav-menu a i { width:20px; text-align:center; }

        .main-content { flex:1; margin-left:var(--sidebar-width); padding:20px; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; padding-bottom:20px; border-bottom:1px solid var(--border-color); }
        .header h1 { color:var(--primary-color); font-size:1.8rem; display:flex; align-items:center; gap:10px; }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card { background:white; border-radius:12px; padding:30px; box-shadow:0 4px 15px rgba(0,0,0,0.05); margin-bottom:25px; }
        .card h2 { color:var(--primary-color); margin-bottom:20px; font-size:1.3rem; display:flex; align-items:center; gap:10px; }

        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-weight:600; margin-bottom:6px; color:var(--dark-color); }
        .form-control { width:100%; padding:10px 14px; border:1px solid var(--border-color); border-radius:8px; font-size:0.95rem; transition:border 0.2s; }
        .form-control:focus { outline:none; border-color:var(--primary-color); box-shadow:0 0 0 3px rgba(67,97,238,0.1); }
        textarea.form-control { min-height:140px; resize:vertical; }

        .form-check { display:flex; align-items:center; gap:8px; }
        .form-check input { width:18px; height:18px; cursor:pointer; }

        .btn { padding:10px 22px; border:none; border-radius:8px; cursor:pointer; font-weight:600; display:inline-flex; align-items:center; gap:8px; transition:all 0.3s; text-decoration:none; font-size:0.9rem; }
        .btn-primary { background:var(--primary-color); color:white; }
        .btn-primary:hover { background:var(--secondary-color); transform:translateY(-2px); }
        .btn-warning { background:#ff9e00; color:white; }
        .btn-warning:hover { background:#cc7e00; }
        .btn-danger { background:var(--danger-color); color:white; }
        .btn-danger:hover { background:#5a088f; }
        .btn-success { background:#38b000; color:white; }
        .btn-success:hover { background:#2d8a00; }
        .btn-secondary { background:var(--gray-color); color:white; }
        .btn-sm { padding:6px 12px; font-size:0.8rem; }

        .alert { padding:14px 18px; border-radius:8px; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:rgba(76,201,240,0.15); color:#0a8ea8; border-left:4px solid #4cc9f0; }
        .alert-danger { background:rgba(114,9,183,0.15); color:var(--danger-color); border-left:4px solid var(--danger-color); }

        .data-table { width:100%; border-collapse:collapse; }
        .data-table th { background:#f8f9fa; padding:13px 15px; text-align:left; font-weight:600; border-bottom:2px solid var(--border-color); }
        .data-table td { padding:13px 15px; border-bottom:1px solid var(--border-color); vertical-align:middle; }
        .data-table tbody tr:hover { background:#f8f9fa; }

        .badge { padding:4px 10px; border-radius:20px; font-size:0.8rem; font-weight:600; }
        .badge-active { background:rgba(56,176,0,0.15); color:#2d8a00; }
        .badge-inactive { background:rgba(114,9,183,0.15); color:var(--danger-color); }

        .news-thumb { width:60px; height:45px; object-fit:cover; border-radius:6px; }
        .actions { display:flex; gap:6px; flex-wrap:wrap; }

        .preview-text { max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--gray-color); font-size:0.88rem; }

        @media(max-width:1200px) {
            .sidebar { width:70px; }
            .sidebar .logo h2 span, .sidebar .nav-menu a span { display:none; }
            .sidebar .logo h2 { justify-content:center; }
            .nav-menu a { justify-content:center; padding:15px 10px; }
            .main-content { margin-left:70px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar - единая панель навигации -->
    <!-- Подключаем единую навигацию -->
    <?php include_once 'admin_navigation.php'; ?>

    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-comments"></i> Управление чатами</h1>
            <?php 
            // Получаем данные администратора аналогично dashboard и migrants
            $userAvatar = function_exists('getAdminUserAvatar') ? getAdminUserAvatar() : (isset($userAvatar) ? $userAvatar : null);
            $userName = function_exists('getAdminUserName') ? getAdminUserName() : (isset($userName) ? $userName : 'Администратор');
            ?>
            <div class="user-info">
                <div class="user-avatar">
                    <?php if ($userAvatar): ?>
                        <img src="<?php echo htmlspecialchars($userAvatar); ?>" 
                             alt="<?php echo htmlspecialchars($userName); ?>"
                             style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    <?php else: 
                        echo strtoupper(substr($userName, 0, 1));
                    endif; ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($userName); ?></div>
                    <div style="font-size: 0.9rem; color: var(--gray-color);"><?php echo date('d.m.Y H:i'); ?></div>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Форма создания / редактирования -->
        <div class="card">
            <h2>
                <i class="fas fa-<?php echo $editNews ? 'edit' : 'plus-circle'; ?>"></i>
                <?php echo $editNews ? 'Редактировать новость' : 'Добавить новость'; ?>
            </h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editNews ? 'edit' : 'create'; ?>">
                <?php if ($editNews): ?>
                    <input type="hidden" name="id" value="<?php echo $editNews['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Заголовок *</label>
                    <input type="text" name="title" class="form-control" required
                           value="<?php echo $editNews ? htmlspecialchars($editNews['title']) : ''; ?>"
                           placeholder="Введите заголовок новости">
                </div>

                <div class="form-group">
                    <label>Содержание *</label>
                    <textarea name="content" class="form-control" required
                              placeholder="Введите текст новости"><?php echo $editNews ? htmlspecialchars($editNews['content']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Изображение <?php echo $editNews && $editNews['image'] ? '(оставьте пустым, чтобы не менять)' : '(необязательно)'; ?></label>
                    <?php if ($editNews && $editNews['image']): ?>
                        <div style="margin-bottom:8px;">
                            <img src="<?php echo htmlspecialchars($editNews['image']); ?>" style="height:80px;border-radius:6px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               <?php echo (!$editNews || $editNews['is_active']) ? 'checked' : ''; ?>>
                        <label for="is_active">Опубликовать (показывать на сайте)</label>
                    </div>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editNews ? 'Сохранить изменения' : 'Создать новость'; ?>
                    </button>
                    <?php if ($editNews): ?>
                        <a href="news_admin.php" class="btn btn-secondary"><i class="fas fa-times"></i> Отмена</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Список новостей -->
        <div class="card">
            <h2><i class="fas fa-list"></i> Все новости (<?php echo count($newsList); ?>)</h2>
            <?php if (empty($newsList)): ?>
                <p style="color:var(--gray-color); text-align:center; padding:30px;">Новостей пока нет. Создайте первую!</p>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Фото</th>
                            <th>Заголовок</th>
                            <th>Превью</th>
                            <th>Автор</th>
                            <th>Статус</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($newsList as $item): ?>
                        <tr>
                            <td><?php echo $item['id']; ?></td>
                            <td>
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" class="news-thumb" alt="">
                                <?php else: ?>
                                    <span style="color:#ccc;"><i class="fas fa-image fa-2x"></i></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                            <td><div class="preview-text"><?php echo htmlspecialchars(mb_substr($item['content'], 0, 80)); ?>...</div></td>
                            <td><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></td>
                            <td>
                                <span class="badge <?php echo $item['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $item['is_active'] ? 'Активна' : 'Скрыта'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($item['created_at'])); ?></td>
                            <td>
                                <div class="actions">
                                    <a href="news_admin.php?edit=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Изменить статус?')">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $item['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" title="<?php echo $item['is_active'] ? 'Скрыть' : 'Опубликовать'; ?>">
                                            <i class="fas fa-<?php echo $item['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить новость безвозвратно?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>