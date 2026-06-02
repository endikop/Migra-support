<?php
session_start();
require_once '../src/config/config.php';
require_once '../src/components/include_avatar.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// РџРѕРґРєР»СЋС‡Р°РµРј С„СѓРЅРєС†РёРё РґР»СЏ СЂР°Р±РѕС‚С‹ СЃ Р·Р°РїСЂРµС‰РµРЅРЅС‹РјРё СЃР»РѕРІР°РјРё
require_once '../src/admin/censorship/banned_words.php';

$pageTitle = 'РЈРїСЂР°РІР»РµРЅРёРµ Р·Р°РїСЂРµС‰РµРЅРЅС‹РјРё СЃР»РѕРІР°РјРё | РђРґРјРёРЅ-РїР°РЅРµР»СЊ';
require_once 'admin_template.php';
startAdminPage($pageTitle);

// РћР±СЂР°Р±РѕС‚РєР° РґРѕР±Р°РІР»РµРЅРёСЏ РЅРѕРІРѕРіРѕ СЃР»РѕРІР°
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_word'])) {
        $word = trim($_POST['word']);
        $replacement = trim($_POST['replacement']);
        $severity = $_POST['severity'];
        
        if (!empty($word)) {
            if (addBannedWord($word, $replacement, $severity, $_SESSION['user_id'], $pdo)) {
                $_SESSION['success'] = 'Р—Р°РїСЂРµС‰РµРЅРЅРѕРµ СЃР»РѕРІРѕ СѓСЃРїРµС€РЅРѕ РґРѕР±Р°РІР»РµРЅРѕ!';
            } else {
                $_SESSION['error'] = 'РћС€РёР±РєР° РїСЂРё РґРѕР±Р°РІР»РµРЅРёРё СЃР»РѕРІР°. Р’РѕР·РјРѕР¶РЅРѕ, С‚Р°РєРѕРµ СЃР»РѕРІРѕ СѓР¶Рµ СЃСѓС‰РµСЃС‚РІСѓРµС‚.';
            }
            header('Location: banned_words_admin.php');
            exit;
        }
    }
    
    if (isset($_POST['remove_word'])) {
        $wordId = $_POST['word_id'];
        if (removeBannedWord($wordId, $pdo)) {
            $_SESSION['success'] = 'Р—Р°РїСЂРµС‰РµРЅРЅРѕРµ СЃР»РѕРІРѕ СѓСЃРїРµС€РЅРѕ СѓРґР°Р»РµРЅРѕ!';
        } else {
            $_SESSION['error'] = 'РћС€РёР±РєР° РїСЂРё СѓРґР°Р»РµРЅРёРё СЃР»РѕРІР°.';
        }
        header('Location: banned_words_admin.php');
        exit;
    }
}

// РџРѕР»СѓС‡Р°РµРј СЃРїРёСЃРѕРє Р·Р°РїСЂРµС‰РµРЅРЅС‹С… СЃР»РѕРІ
$bannedWords = getBannedWords($pdo);
?>

        <!-- Р—Р°РіРѕР»РѕРІРѕРє -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-ban"></i> РЈРїСЂР°РІР»РµРЅРёРµ Р·Р°РїСЂРµС‰РµРЅРЅС‹РјРё СЃР»РѕРІР°РјРё</h2>
                <p>Р”РѕР±Р°РІР»СЏР№С‚Рµ СЃР»РѕРІР°, РєРѕС‚РѕСЂС‹Рµ Р±СѓРґСѓС‚ Р°РІС‚РѕРјР°С‚РёС‡РµСЃРєРё С†РµРЅР·СѓСЂРёСЂРѕРІР°С‚СЊСЃСЏ РІ С‡Р°С‚Р°С…</p>
            </div>
        </div>

        <!-- Р¤РѕСЂРјР° РґРѕР±Р°РІР»РµРЅРёСЏ РЅРѕРІРѕРіРѕ СЃР»РѕРІР° -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> Р”РѕР±Р°РІРёС‚СЊ РЅРѕРІРѕРµ Р·Р°РїСЂРµС‰РµРЅРЅРѕРµ СЃР»РѕРІРѕ</h3>
            </div>
            <form method="POST" class="form-grid">
                <div class="form-group">
                    <label class="form-label">Р—Р°РїСЂРµС‰РµРЅРЅРѕРµ СЃР»РѕРІРѕ *</label>
                    <input type="text" name="word" class="form-control" required 
                           placeholder="РќР°РїСЂРёРјРµСЂ: РѕСЃРєРѕСЂР±Р»РµРЅРёРµ" maxlength="100">
                    <small class="form-text">РЎР»РѕРІРѕ РёР»Рё С„СЂР°Р·Р° РґР»СЏ С†РµРЅР·СѓСЂС‹</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Р—Р°РјРµРЅР° *</label>
                    <input type="text" name="replacement" class="form-control" required 
                           placeholder="РќР°РїСЂРёРјРµСЂ: ***" value="***" maxlength="100">
                    <small class="form-text">РќР° С‡С‚Рѕ Р·Р°РјРµРЅРёС‚СЊ Р·Р°РїСЂРµС‰РµРЅРЅРѕРµ СЃР»РѕРІРѕ</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">РЎРµСЂСЊРµР·РЅРѕСЃС‚СЊ *</label>
                    <select name="severity" class="form-control" required>
                        <option value="low">РќРёР·РєР°СЏ</option>
                        <option value="medium" selected>РЎСЂРµРґРЅСЏСЏ</option>
                        <option value="high">Р’С‹СЃРѕРєР°СЏ</option>
                    </select>
                    <small class="form-text">Р’С‹СЃРѕРєР°СЏ СЃРµСЂСЊРµР·РЅРѕСЃС‚СЊ РґР»СЏ СЃР°РјС‹С… РіСЂСѓР±С‹С… РЅР°СЂСѓС€РµРЅРёР№</small>
                </div>
                
                <div class="form-group full-width">
                    <button type="submit" name="add_word" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Р”РѕР±Р°РІРёС‚СЊ СЃР»РѕРІРѕ
                    </button>
                </div>
            </form>
        </div>

        <!-- РЎРїРёСЃРѕРє Р·Р°РїСЂРµС‰РµРЅРЅС‹С… СЃР»РѕРІ -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> РЎРїРёСЃРѕРє Р·Р°РїСЂРµС‰РµРЅРЅС‹С… СЃР»РѕРІ (<?php echo count($bannedWords); ?>)</h3>
            </div>
            
            <?php if (empty($bannedWords)): ?>
                <div class="no-data">
                    <i class="fas fa-ban"></i>
                    <div>РќРµС‚ Р·Р°РїСЂРµС‰РµРЅРЅС‹С… СЃР»РѕРІ</div>
                    <p>Р”РѕР±Р°РІСЊС‚Рµ РїРµСЂРІРѕРµ СЃР»РѕРІРѕ, РёСЃРїРѕР»СЊР·СѓСЏ С„РѕСЂРјСѓ РІС‹С€Рµ</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>РЎР»РѕРІРѕ</th>
                            <th>Р—Р°РјРµРЅР°</th>
                            <th>РЎРµСЂСЊРµР·РЅРѕСЃС‚СЊ</th>
                            <th>РЎС‚Р°С‚СѓСЃ</th>
                            <th>Р”РµР№СЃС‚РІРёСЏ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bannedWords as $word): ?>
                        <tr>
                            <td><?php echo $word['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($word['word']); ?></strong></td>
                            <td><?php echo htmlspecialchars($word['replacement']); ?></td>
                            <td>
                                <?php 
                                $severityText = '';
                                $severityClass = '';
                                switch ($word['severity']) {
                                    case 'low': 
                                        $severityText = 'РќРёР·РєР°СЏ'; 
                                        $severityClass = 'status active';
                                        break;
                                    case 'medium': 
                                        $severityText = 'РЎСЂРµРґРЅСЏСЏ'; 
                                        $severityClass = 'status pending';
                                        break;
                                    case 'high': 
                                        $severityText = 'Р’С‹СЃРѕРєР°СЏ'; 
                                        $severityClass = 'status inactive';
                                        break;
                                }
                                ?>
                                <span class="<?php echo $severityClass; ?>"><?php echo $severityText; ?></span>
                            </td>
                            <td>
                                <span class="status active">РђРєС‚РёРІРЅРѕ</span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="word_id" value="<?php echo $word['id']; ?>">
                                    <button type="submit" name="remove_word" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Р’С‹ СѓРІРµСЂРµРЅС‹, С‡С‚Рѕ С…РѕС‚РёС‚Рµ СѓРґР°Р»РёС‚СЊ СЌС‚Рѕ Р·Р°РїСЂРµС‰РµРЅРЅРѕРµ СЃР»РѕРІРѕ?')">
                                        <i class="fas fa-trash"></i> РЈРґР°Р»РёС‚СЊ
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- РўРµСЃС‚ С†РµРЅР·СѓСЂС‹ -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-vial"></i> РўРµСЃС‚ С†РµРЅР·СѓСЂС‹</h3>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">РўРµСЃС‚РѕРІС‹Р№ С‚РµРєСЃС‚</label>
                    <textarea id="testText" class="form-control" rows="4" 
                              placeholder="Р’РІРµРґРёС‚Рµ С‚РµРєСЃС‚ РґР»СЏ РїСЂРѕРІРµСЂРєРё С†РµРЅР·СѓСЂС‹..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Р РµР·СѓР»СЊС‚Р°С‚</label>
                    <div id="testResult" class="form-control" style="min-height: 100px; background-color: #f8f9fa; padding: 12px; border-radius: 8px;">
                        Р РµР·СѓР»СЊС‚Р°С‚ РїРѕСЏРІРёС‚СЃСЏ Р·РґРµСЃСЊ...
                    </div>
                </div>
                <div class="form-group full-width">
                    <button type="button" onclick="testCensorship()" class="btn btn-success">
                        <i class="fas fa-vial"></i> РџСЂРѕС‚РµСЃС‚РёСЂРѕРІР°С‚СЊ
                    </button>
                </div>
            </div>
        </div>

        <script>
        function testCensorship() {
            const text = document.getElementById('testText').value;
            if (!text.trim()) {
                alert('Р’РІРµРґРёС‚Рµ С‚РµРєСЃС‚ РґР»СЏ С‚РµСЃС‚РёСЂРѕРІР°РЅРёСЏ');
                return;
            }
            
            fetch('test_censorship.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'text=' + encodeURIComponent(text)
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('testResult');
                if (data.success) {
                    let html = `<strong>РћСЂРёРіРёРЅР°Р»СЊРЅС‹Р№ С‚РµРєСЃС‚:</strong><br>`;
                    html += `<div style="background: #fff; padding: 8px; border-radius: 4px; margin-bottom: 10px; border-left: 3px solid #ccc;">`;
                    html += `${data.original_text}</div>`;
                    
                    html += `<strong>РџРѕСЃР»Рµ С†РµРЅР·СѓСЂС‹:</strong><br>`;
                    html += `<div style="background: #fff; padding: 8px; border-radius: 4px; margin-bottom: 10px; border-left: 3px solid ${data.censored ? '#f72585' : '#4cc9f0'};">`;
                    html += `${data.censored_text}</div>`;
                    
                    if (data.censored) {
                        html += `<strong>РќР°Р№РґРµРЅС‹ Р·Р°РїСЂРµС‰РµРЅРЅС‹Рµ СЃР»РѕРІР°:</strong><br>`;
                        html += `<ul style="margin-top: 5px;">`;
                        data.found_words.forEach(word => {
                            html += `<li><strong>${word.word}</strong> в†’ ${word.replacement} (${word.severity})</li>`;
                        });
                        html += `</ul>`;
                    } else {
                        html += `<div style="color: #4cc9f0;"><i class="fas fa-check-circle"></i> Р—Р°РїСЂРµС‰РµРЅРЅС‹С… СЃР»РѕРІ РЅРµ РЅР°Р№РґРµРЅРѕ</div>`;
                    }
                    
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `<div style="color: #f72585;"><i class="fas fa-exclamation-circle"></i> РћС€РёР±РєР°: ${data.error}</div>`;
                }
            })
            .catch(error => {
                document.getElementById('testResult').innerHTML = 
                    `<div style="color: #f72585;"><i class="fas fa-exclamation-circle"></i> РћС€РёР±РєР° СЃРµС‚Рё</div>`;
            });
        }
        </script>

<?php
endAdminPage();
?>
