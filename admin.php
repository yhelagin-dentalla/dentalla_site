<?php
session_start();

// bcrypt-хеш пароля "dentalla2026" (формат $2a$, совместим со старым PHP crypt())
$ADMIN_PASSWORD_HASH = '$2a$10$n.AlUesW411rNHyxMPyL2OxyTAeZBAlcCK8OhJEZlUwBYW1AU2GmG';
$DATA_FILE = __DIR__ . '/services-data.json';

// Проверка пароля, совместимая с PHP 5.3+ (без password_verify, которой нет в PHP < 5.5)
function dentalla_check_password($plain, $hash) {
    return crypt($plain, $hash) === $hash;
}

// Аналог http_response_code() для PHP < 5.4
function dentalla_status($code) {
    $messages = array(
        200 => 'OK', 400 => 'Bad Request', 403 => 'Forbidden', 500 => 'Internal Server Error'
    );
    $msg = isset($messages[$code]) ? $messages[$code] : '';
    header('HTTP/1.1 ' . $code . ' ' . $msg);
}

header('X-Robots-Tag: noindex, nofollow');

// ── Выход ──
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ── Вход ──
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $pass = isset($_POST['password']) ? $_POST['password'] : '';
    if (dentalla_check_password($pass, $ADMIN_PASSWORD_HASH)) {
        $_SESSION['dentalla_admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $loginError = 'Неверный пароль';
    }
}

$isAuthed = !empty($_SESSION['dentalla_admin']);

// ── API: сохранение данных (только для авторизованных) ──
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($contentType, 'application/json') !== false) {
    header('Content-Type: application/json; charset=utf-8');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['action']) || $data['action'] !== 'save') {
        dentalla_status(400);
        echo json_encode(array('ok' => false, 'error' => 'Некорректный запрос'));
        exit;
    }
    if (!$isAuthed) {
        dentalla_status(403);
        echo json_encode(array('ok' => false, 'error' => 'Не авторизован'));
        exit;
    }
    if (!isset($data['blocks']) || !is_array($data['blocks'])) {
        dentalla_status(400);
        echo json_encode(array('ok' => false, 'error' => 'Некорректные данные'));
        exit;
    }
    // Базовая валидация структуры
    $structOk = true;
    foreach ($data['blocks'] as $b) {
        if (!isset($b['id']) || !isset($b['title']) || !isset($b['items']) || !is_array($b['items'])) {
            $structOk = false;
            break;
        }
    }
    if (!$structOk) {
        dentalla_status(400);
        echo json_encode(array('ok' => false, 'error' => 'Некорректная структура блока'));
        exit;
    }
    $toSave = array('blocks' => $data['blocks']);
    $json = json_encode($toSave);
    if (@file_put_contents($DATA_FILE, $json) === false) {
        dentalla_status(500);
        echo json_encode(array('ok' => false, 'error' => 'Не удалось записать файл. Проверьте права доступа.'));
        exit;
    }
    echo json_encode(array('ok' => true));
    exit;
}

// ── Загрузка текущих данных для редактора ──
$currentData = array('blocks' => array());
if (file_exists($DATA_FILE)) {
    $decoded = json_decode(file_get_contents($DATA_FILE), true);
    if (is_array($decoded)) $currentData = $decoded;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Управление услугами — ДЕНТАЛЛА</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600&family=Manrope:wght@400;500;600;700&display=swap">
<style>
:root{--ink:#2B2418;--muted:#756B5D;--gold:#B09B3A;--gold-dark:#92811E;--cream:#F7F3EA;--cream-2:#FCFBF7;--line:#EAE2D5;--danger:#B24D3A;--danger-bg:#FBF1EC;}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Manrope',sans-serif;color:var(--ink);background:var(--cream-2);line-height:1.5;font-size:14px;}
a{color:inherit}
button{cursor:pointer;font-family:inherit}

/* ── ЛОГИН ── */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:linear-gradient(135deg,#F5EFE3,#FCFBF7);}
.login-box{background:#fff;border-radius:22px;box-shadow:0 20px 60px rgba(64,49,25,.12);padding:44px 40px;width:100%;max-width:380px;text-align:center;}
.login-box h1{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:600;margin-bottom:6px;}
.login-box p{color:var(--muted);font-size:13px;margin-bottom:24px;}
.login-box input{width:100%;border:1px solid var(--line);border-radius:10px;padding:13px 16px;font-size:15px;font-family:inherit;outline:none;margin-bottom:14px;transition:border-color .2s;}
.login-box input:focus{border-color:var(--gold);}
.login-box button{width:100%;background:var(--gold);color:#fff;border:none;border-radius:10px;padding:13px;font-size:14.5px;font-weight:600;transition:background .2s;}
.login-box button:hover{background:var(--gold-dark);}
.login-error{background:var(--danger-bg);color:var(--danger);border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;}

/* ── ШАПКА АДМИНКИ ── */
.admin-header{background:var(--ink);color:#EFE8DA;padding:18px clamp(16px,3vw,40px);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.admin-header h1{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;}
.admin-header .sub{font-size:11.5px;color:#A99B8C;margin-top:2px;}
.admin-actions{display:flex;gap:10px;align-items:center;}
.btn{border:none;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:600;transition:all .2s;}
.btn-gold{background:var(--gold);color:#fff;}
.btn-gold:hover{background:var(--gold-dark);}
.btn-gold:disabled{background:#C8C0A8;cursor:not-allowed;}
.btn-ghost{background:rgba(255,255,255,.08);color:#EFE8DA;}
.btn-ghost:hover{background:rgba(255,255,255,.15);}
.save-status{font-size:12.5px;color:#A99B8C;}
.save-status.ok{color:#8FBF7A;}
.save-status.err{color:#E08A6E;}

/* ── ОСНОВНОЙ КОНТЕЙНЕР ── */
.admin-wrap{max-width:900px;margin:0 auto;padding:28px 16px 80px;}
.hint{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px 20px;font-size:13px;color:var(--muted);margin-bottom:24px;line-height:1.6;}

/* ── БЛОК УСЛУГ ── */
.block{background:#fff;border:1px solid var(--line);border-radius:18px;margin-bottom:20px;overflow:hidden;box-shadow:0 4px 16px rgba(64,49,25,.04);}
.block-head{display:flex;align-items:center;gap:10px;padding:16px 18px;background:var(--cream);border-bottom:1px solid var(--line);}
.block-head input.block-title{flex:1;border:1px solid transparent;background:transparent;font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:var(--ink);padding:6px 8px;border-radius:6px;outline:none;}
.block-head input.block-title:focus{border-color:var(--gold);background:#fff;}
.block-order{display:flex;flex-direction:column;gap:2px;}
.icon-btn{background:none;border:1px solid var(--line);border-radius:6px;width:26px;height:24px;display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--muted);transition:.2s;}
.icon-btn:hover{border-color:var(--gold);color:var(--gold-dark);}
.icon-btn.danger:hover{border-color:var(--danger);color:var(--danger);}

.items{padding:6px 18px 14px;}
.item-row{display:grid;grid-template-columns:1fr 130px auto;gap:8px;align-items:start;padding:8px 0;border-bottom:1px dashed var(--line);}
.item-row:last-child{border-bottom:none;}
.item-row input{border:1px solid var(--line);border-radius:8px;padding:9px 11px;font-size:13.5px;font-family:inherit;outline:none;width:100%;transition:border-color .2s;}
.item-row input:focus{border-color:var(--gold);}
.item-row .item-name-wrap{display:flex;flex-direction:column;gap:5px;}
.item-row .item-comment{font-size:12px;color:var(--muted);}
.item-row .item-actions{display:flex;gap:4px;align-items:center;padding-top:2px;}

.add-item-btn{margin-top:10px;background:none;border:1px dashed var(--line);border-radius:8px;padding:9px 14px;font-size:12.5px;color:var(--gold-dark);width:100%;transition:.2s;}
.add-item-btn:hover{border-color:var(--gold);background:#FBF8EF;}

.add-block-btn{background:#fff;border:1px dashed #C8B898;border-radius:18px;padding:18px;width:100%;text-align:center;font-size:14px;color:var(--gold-dark);font-weight:600;transition:.2s;}
.add-block-btn:hover{border-color:var(--gold);background:#FBF8EF;}

@media(max-width:560px){
  .item-row{grid-template-columns:1fr;}
  .item-row .item-actions{justify-content:flex-end;padding-top:0;}
  .admin-header{flex-direction:column;align-items:flex-start;gap:10px;}
  .admin-actions{width:100%;justify-content:space-between;}
}
</style>
</head>
<body>

<?php if (!$isAuthed): ?>

<div class="login-wrap">
  <div class="login-box">
    <h1>ДЕНТАЛЛА</h1>
    <p>Управление услугами и ценами</p>
    <?php if (!empty($loginError)): ?><div class="login-error"><?php echo htmlspecialchars($loginError); ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="action" value="login">
      <input type="password" name="password" placeholder="Пароль" autofocus required>
      <button type="submit">Войти</button>
    </form>
  </div>
</div>

<?php else: ?>

<div class="admin-header">
  <div>
    <h1>Управление услугами</h1>
    <div class="sub">ДЕНТАЛЛА · панель администратора</div>
  </div>
  <div class="admin-actions">
    <span class="save-status" id="save-status"></span>
    <button class="btn btn-gold" id="save-btn" onclick="saveData()">Сохранить изменения</button>
    <a class="btn btn-ghost" href="admin.php?logout=1">Выйти</a>
  </div>
</div>

<div class="admin-wrap">
  <div class="hint">Здесь можно менять цены, добавлять и удалять услуги, объединять их в блоки, оставлять внутренние комментарии к позиции (посетителям сайта они не показываются — видна только цена и название). После изменений обязательно нажмите <strong>«Сохранить изменения»</strong> — правки сразу появятся на странице «Услуги» сайта.</div>

  <div id="blocks-container"></div>

  <button class="add-block-btn" onclick="addBlock()">+ Добавить новый блок услуг</button>
</div>

<script>
var DATA = <?php echo json_encode($currentData); ?>;

function transliterate(str) {
  var map = {'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'e','ж':'zh','з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'c','ч':'ch','ш':'sh','щ':'sch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya',' ':'-'};
  return str.toLowerCase().split('').map(function(c){ return map[c] !== undefined ? map[c] : c; }).join('').replace(/[^a-z0-9\-]/g,'').replace(/-+/g,'-');
}

function render() {
  var container = document.getElementById('blocks-container');
  container.innerHTML = '';
  DATA.blocks.forEach(function(block, bi) {
    var div = document.createElement('div');
    div.className = 'block';

    var head = document.createElement('div');
    head.className = 'block-head';

    var order = document.createElement('div');
    order.className = 'block-order';
    var upBtn = document.createElement('button');
    upBtn.className = 'icon-btn'; upBtn.textContent = '▲'; upBtn.title = 'Переместить вверх';
    upBtn.onclick = function(){ moveBlock(bi, -1); };
    var downBtn = document.createElement('button');
    downBtn.className = 'icon-btn'; downBtn.textContent = '▼'; downBtn.title = 'Переместить вниз';
    downBtn.onclick = function(){ moveBlock(bi, 1); };
    order.appendChild(upBtn); order.appendChild(downBtn);

    var titleInput = document.createElement('input');
    titleInput.className = 'block-title';
    titleInput.value = block.title;
    titleInput.oninput = function(){ block.title = this.value; };

    var delBlockBtn = document.createElement('button');
    delBlockBtn.className = 'icon-btn danger';
    delBlockBtn.textContent = '✕';
    delBlockBtn.title = 'Удалить блок целиком';
    delBlockBtn.onclick = function(){
      if (confirm('Удалить блок «' + block.title + '» вместе со всеми услугами в нём?')) {
        DATA.blocks.splice(bi, 1);
        render();
      }
    };

    head.appendChild(order);
    head.appendChild(titleInput);
    head.appendChild(delBlockBtn);
    div.appendChild(head);

    var itemsWrap = document.createElement('div');
    itemsWrap.className = 'items';

    block.items.forEach(function(item, ii) {
      var row = document.createElement('div');
      row.className = 'item-row';

      var nameWrap = document.createElement('div');
      nameWrap.className = 'item-name-wrap';
      var nameInput = document.createElement('input');
      nameInput.placeholder = 'Название услуги';
      nameInput.value = item.name;
      nameInput.oninput = function(){ item.name = this.value; };
      var commentInput = document.createElement('input');
      commentInput.className = 'item-comment';
      commentInput.placeholder = 'Комментарий (необязательно, виден на сайте под названием)';
      commentInput.value = item.comment || '';
      commentInput.oninput = function(){ item.comment = this.value; };
      nameWrap.appendChild(nameInput);
      nameWrap.appendChild(commentInput);

      var priceInput = document.createElement('input');
      priceInput.placeholder = 'от 5 000 ₽';
      priceInput.value = item.price;
      priceInput.oninput = function(){ item.price = this.value; };

      var actions = document.createElement('div');
      actions.className = 'item-actions';
      var upI = document.createElement('button'); upI.className='icon-btn'; upI.textContent='▲'; upI.title='Вверх';
      upI.onclick = function(){ moveItem(bi, ii, -1); };
      var downI = document.createElement('button'); downI.className='icon-btn'; downI.textContent='▼'; downI.title='Вниз';
      downI.onclick = function(){ moveItem(bi, ii, 1); };
      var delI = document.createElement('button'); delI.className='icon-btn danger'; delI.textContent='✕'; delI.title='Удалить услугу';
      delI.onclick = function(){ block.items.splice(ii, 1); render(); };
      actions.appendChild(upI); actions.appendChild(downI); actions.appendChild(delI);

      row.appendChild(nameWrap);
      row.appendChild(priceInput);
      row.appendChild(actions);
      itemsWrap.appendChild(row);
    });

    var addItemBtn = document.createElement('button');
    addItemBtn.className = 'add-item-btn';
    addItemBtn.textContent = '+ Добавить услугу в этот блок';
    addItemBtn.onclick = function(){
      block.items.push({name:'', price:'', comment:''});
      render();
    };
    itemsWrap.appendChild(addItemBtn);

    div.appendChild(itemsWrap);
    container.appendChild(div);
  });
}

function moveBlock(index, dir) {
  var newIndex = index + dir;
  if (newIndex < 0 || newIndex >= DATA.blocks.length) return;
  var tmp = DATA.blocks[index];
  DATA.blocks[index] = DATA.blocks[newIndex];
  DATA.blocks[newIndex] = tmp;
  render();
}

function moveItem(bi, ii, dir) {
  var items = DATA.blocks[bi].items;
  var newIndex = ii + dir;
  if (newIndex < 0 || newIndex >= items.length) return;
  var tmp = items[ii];
  items[ii] = items[newIndex];
  items[newIndex] = tmp;
  render();
}

function addBlock() {
  var title = prompt('Название нового блока услуг:');
  if (!title) return;
  var id = transliterate(title) || ('block-' + Date.now());
  var existingIds = DATA.blocks.map(function(b){ return b.id; });
  var finalId = id, n = 2;
  while (existingIds.indexOf(finalId) !== -1) { finalId = id + '-' + n; n++; }
  DATA.blocks.push({id: finalId, title: title, items: []});
  render();
}

function saveData() {
  var btn = document.getElementById('save-btn');
  var status = document.getElementById('save-status');
  btn.disabled = true;
  status.className = 'save-status';
  status.textContent = 'Сохраняем…';

  fetch('admin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action: 'save', blocks: DATA.blocks})
  })
  .then(function(r){ return r.json(); })
  .then(function(res){
    btn.disabled = false;
    if (res.ok) {
      status.className = 'save-status ok';
      status.textContent = 'Сохранено ✓';
      setTimeout(function(){ status.textContent = ''; }, 3000);
    } else {
      status.className = 'save-status err';
      status.textContent = 'Ошибка: ' + (res.error || 'неизвестная');
    }
  })
  .catch(function(){
    btn.disabled = false;
    status.className = 'save-status err';
    status.textContent = 'Ошибка соединения';
  });
}
</script>

<?php endif; ?>
</body>
</html>
