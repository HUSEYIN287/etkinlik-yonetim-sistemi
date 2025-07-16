<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'etkinlik_yönetim');
if ($conn->connect_error) die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);

$api_key        = 'rtUGAumYvLh6wfGBnkL83WmGT778gGkk';
$selected_city  = $_GET['city']  ?? '';
$active_tab     = $_GET['tab']   ?? 'tab0';

// Helper to set flash message and target tab
function set_flash($text, $type, $tab) {
    $_SESSION['flash']     = $text;
    $_SESSION['flash_type']= $type;
    $_SESSION['flash_tab'] = $tab;
}

include 'hava_durumu.php';

// POST işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Etkinlik ekle
    if (isset($_POST['save_event'])) {
      $kategori_terimleri = [
        "Music" => "Müzik",
        "Sports" => "Spor",
        "Arts & Theatre" => "Sanat ve Tiyatro",
        "Film" => "Film",
        "Miscellaneous" => "Çeşitli",
        "Other" => "Diğer"
      ];
      $api_kategori = $_POST['category'] ?? 'Other';
      $kategori = $kategori_terimleri[$api_kategori] ?? 'Diğer';
     
     if (
     $sicaklik < 30 || 
     strpos($condition, 'yağmur') !== false || 
     strpos($condition, 'kar') !== false
       ) {
         $durum = 0;
         
         } else {
         $durum = 1;
           }

     // Kategoriye göre fiyat belirleme
    switch ($kategori) {
        case 'Müzik':
            $fiyat = 400;
            break;
        case 'Sanat ve Tiyatro':
            $fiyat = 450;
            break;
        case 'Çeşitli':
            $fiyat = 500;
            break;
        default:
            $fiyat = 550;
    }

    // INSERT sorgusuna fiyat sütununu ekliyoruz
    $stmt = $conn->prepare(
        "INSERT IGNORE INTO etkinlikler
         (api_id, baslik, tarih, yer, sehir, detay_url, durum, kategori, fiyat)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "ssssssisi",
        $_POST['api_id'],
        $_POST['title'],
        $_POST['date'],
        $_POST['venue'],
        $_POST['city'],
        $_POST['url'],
        $durum,
        $kategori,
        $fiyat
    );

    $stmt->execute();
        if ($stmt->affected_rows>0) {
            set_flash("Etkinlik başarıyla eklendi.", 'success','tab2');
        } else {
            set_flash("Bu etkinliği zaten eklediniz.", 'error','tab2');
        }
        $active_tab='tab2';
    }
    // Eklenen etkinliği düzenle
    if (isset($_POST['duzenle_eklenen'])) {
        $kategori = $_POST['category'] ?? 'Diğer';
        $stmt = $conn->prepare(
        "UPDATE etkinlikler SET baslik=?, tarih=?, yer=?, sehir=?, kategori=? WHERE id=?"
        );
       $stmt->bind_param(
       "ssssssss",
            $_POST['title'],
            $_POST['date'],
            $_POST['venue'],
            $_POST['city'],
            $kategori,
            $_POST['etkinlik_id']
        );
        $stmt->execute();
        set_flash("Değişiklikler kaydedildi.",'success','tab2');
        $active_tab='tab2';


    }
    // Eklenen etkinlikten sil
    if (isset($_POST['eklenenden_cikar_id'])) {
        $id = intval($_POST['eklenenden_cikar_id']);
        $conn->query("DELETE FROM etkinlikler WHERE id=$id");
        set_flash("Etkinlik silindi.",'success','tab2');
        $active_tab='tab2';
    }
    // Yayına al
    if (isset($_POST['yayina_al_id'])) {
        $id = intval($_POST['yayina_al_id']);
        $conn->query("UPDATE etkinlikler SET yayinda=1 WHERE id=$id");
        set_flash("Etkinlik yayına alındı.",'success','tab3');
        $active_tab='tab3';
    }
    // Yayındaki etkinliği düzenle
    if (isset($_POST['duzenle_event'])) {
        $stmt = $conn->prepare(
          "UPDATE etkinlikler SET baslik=?, tarih=?, yer=?, sehir=? WHERE id=?"
        );
        $stmt->bind_param(
            "ssssi",
            $_POST['title'],
            $_POST['date'],
            $_POST['venue'],
            $_POST['city'],
            $_POST['etkinlik_id']
        );
        $stmt->execute();
        set_flash("Değişiklikler kaydedildi.",'success','tab3');
        $active_tab='tab3';
    }
    // Yayından kaldır
    if (isset($_POST['yayindan_kaldir'])) {
        $id = intval($_POST['etkinlik_id']);
        $conn->query("UPDATE etkinlikler SET yayinda=0 WHERE id=$id");
        set_flash("Etkinlik yayından kaldırıldı.",'success','tab2');
        $active_tab='tab2';
    }

    // Duyuru ekle
    if (isset($_POST['duyuru_ekle'])) {
        $stmt = $conn->prepare("INSERT INTO duyurular (baslik, icerik) VALUES (?, ?)");
        $stmt->bind_param("ss", $_POST['baslik'], $_POST['icerik']);
        $stmt->execute();
        set_flash("Duyuru eklendi.", 'success', 'tab_duyuru');
        $active_tab = 'tab_duyuru';
    }

    // Duyuru güncelle
    if (isset($_POST['duyuru_guncelle'])) {
       $stmt = $conn->prepare("UPDATE duyurular SET baslik=?, icerik=? WHERE id=?");
       $stmt->bind_param("ssi", $_POST['baslik'], $_POST['icerik'], $_POST['duyuru_id']);
       $stmt->execute();
       set_flash("Duyuru güncellendi.", 'success', 'tab_duyuru');
       $active_tab = 'tab_duyuru';
    }

    // Duyuru sil
    if (isset($_POST['duyuru_sil'])) {
       $stmt = $conn->prepare("DELETE FROM duyurular WHERE id=?");
       $stmt->bind_param("i", $_POST['duyuru_id']);
       $stmt->execute();
       set_flash("Duyuru silindi.", 'success', 'tab_duyuru');
       $active_tab = 'tab_duyuru';
    }

}

// API'den veri çek
$api_url = "https://app.ticketmaster.com/discovery/v2/events.json?countryCode=TR&apikey=$api_key&size=200";
$response = file_get_contents($api_url);
$data     = json_decode($response,true);
$etkinlikler = $data['_embedded']['events'] ?? [];

// Filtreli liste
$filtreliEtkinlikler = array_filter($etkinlikler, function($e) use($selected_city){
    if ($selected_city==='') return true;
    foreach ($e['_embedded']['venues'] ?? [] as $v) {
        if (isset($v['city']['name']) && stripos($v['city']['name'],$selected_city)!==false){
            return true;
        }
    }
    return false;
});

// 🔽 Buraya ekleyin: Tarihe göre küçükten büyüğe sıralama
usort($filtreliEtkinlikler, function($a, $b) {
    $dateA = $a['dates']['start']['localDate'] ?? '';
    $dateB = $b['dates']['start']['localDate'] ?? '';
    // Tarih formatı YYYY-MM-DD olduğu için strcmp de çalışır:
    return strcmp($dateA, $dateB);
});

// Şehirler
$sehirler=[];
foreach($etkinlikler as $e){
    foreach($e['_embedded']['venues'] ?? [] as $v){
        if (isset($v['city']['name'])) $sehirler[]=$v['city']['name'];
    }
}
$sehirler=array_unique($sehirler);
if(class_exists('Collator')) {
    (new Collator('tr_TR'))->sort($sehirler);
} else {
    sort($sehirler);
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Etkinlik Yönetimi</title>
  <style>
    body{font-family:Arial;margin:20px;}
    .tab{display:none;}
    .tab.active{display:block;}
    .tab-buttons button{padding:10px 20px;margin-right:5px;border:none;cursor:pointer;background:#ddd;font-weight:bold;border-radius:4px;}
    .tab-buttons button.active{background:#4285f4;color:#fff;}
    ul{list-style:none;padding:0;}
    li{padding:10px;border:1px solid #ccc;border-radius:8px;margin-bottom:15px;}
    form.inline{display:inline;}
    .btn{padding:6px 12px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;margin-left:5px;}
    .btn-green{background:#4CAF50;color:#fff;}
    .btn-blue{background:#2196F3;color:#fff;}
    .btn-orange{background:#ff9800;color:#fff;}
    .btn-red{background:#f44336;color:#fff;}
    .message{padding:10px;margin-bottom:20px;border-radius:5px;font-weight:bold;}
    .success{background:#e0ffe0;border:1px solid #90ee90;color:#006400;}
    .error{background:#ffe0e0;border:1px solid #f08080;color:#8b0000;}
    #confirm-modal,#modal-overlay{display:none;position:fixed;}
    #modal-overlay{top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;}
    #confirm-modal{top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:20px;border:2px solid #444;border-radius:10px;z-index:1000;}
  
    .btn-gray {
       background: #ddd;
       color: #000;
    }

    .btn-gray:hover, .btn-gray:active {
       background: #2196F3;
       color: #fff;
    }

  </style>
  <script>
    function showTab(id){
      document.querySelectorAll('.tab').forEach(el=>el.classList.remove('active'));
      document.querySelectorAll('.tab-buttons button').forEach(el=>el.classList.remove('active'));
      document.getElementById(id).classList.add('active');
      document.querySelector(`button[data-tab="${id}"]`).classList.add('active');
    }
    function confirmDelete(f){
      document.getElementById('modal-overlay').style.display='block';
      document.getElementById('confirm-modal').style.display='block';
      document.getElementById('confirm-yes').onclick=()=>f.submit();
    }
    function closeModal(){
      document.getElementById('modal-overlay').style.display='none';
      document.getElementById('confirm-modal').style.display='none';
    }
    window.onload=()=>{
      showTab("<?= $active_tab ?>");
      // Flash mesaj
      <?php if(isset($_SESSION['flash'], $_SESSION['flash_tab'])): ?>
        if("<?= $_SESSION['flash_tab'] ?>"==="<?= $active_tab ?>"){
          const msg=document.createElement('div');
          msg.id='message-box';
          msg.className='message <?= $_SESSION['flash_type'] ?>';
          msg.textContent="<?= $_SESSION['flash'] ?>";
          document.body.insertBefore(msg, document.querySelector('.tab-buttons'));
          setTimeout(()=>msg.remove(),3000);
        }
        <?php unset($_SESSION['flash'],$_SESSION['flash_type'],$_SESSION['flash_tab']); ?>
      <?php endif; ?>
    };
  </script>
</head>
<body>

  <div id="modal-overlay" onclick="closeModal()"></div>
  <div id="confirm-modal">
    <p>Etkinliği silmek istediğinize emin misiniz?</p>
    <button id="confirm-yes" class="btn btn-red">Evet</button>
    <button onclick="closeModal()" class="btn btn-blue">Vazgeç</button>
  </div>

  <h2>Etkinlik Yönetimi</h2>
  <div class="tab-buttons">
    <button data-tab="tab0" onclick="showTab('tab0')">🕒 Onay Bekleyen</button>
    <button data-tab="tab1" onclick="showTab('tab1')">📅 Göster</button>
    <button data-tab="tab2" onclick="showTab('tab2')">📝 Eklenen</button>
    <button data-tab="tab3" onclick="showTab('tab3')">✅ Yayındaki</button>
    <button data-tab="tab_duyuru" onclick="showTab('tab_duyuru')">📢 Duyuru Ekle</button>
    <button data-tab="logout" onclick="window.location.href='cikis.php'" class="btn btn-gray">🚪 Çıkış</button>
  </div>

  <div id="tab0" class="tab">
    <?php include 'kayit_onay.php'; ?>
  </div>

  <div id="tab1" class="tab">
    <form method="get">
      <input type="hidden" name="tab" value="tab1">
      <label>Şehir:</label>
      <select name="city" onchange="this.form.submit()">
        <option value="">Tümü</option>
        <?php foreach($sehirler as $s): ?>
          <option value="<?= htmlspecialchars($s) ?>" <?= $selected_city===$s?'selected':'' ?>>
            <?= htmlspecialchars($s) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <ul>
      <?php foreach($filtreliEtkinlikler as $e): ?>
      <li>
        <strong><?= htmlspecialchars($e['name']) ?></strong><br>
        Tarih: <?= htmlspecialchars($e['dates']['start']['localDate']??'') ?><br>
        Yer: <?= htmlspecialchars($e['_embedded']['venues'][0]['name']??'') ?><br>
        Şehir: <?= htmlspecialchars($e['_embedded']['venues'][0]['city']['name']??'') ?>
        <form method="post">
          <input type="hidden" name="api_id"  value="<?= htmlspecialchars($e['id']) ?>">
          <input type="hidden" name="title"   value="<?= htmlspecialchars($e['name']) ?>">
          <input type="hidden" name="date"    value="<?= htmlspecialchars($e['dates']['start']['localDate']??'') ?>">
          <input type="hidden" name="venue"   value="<?= htmlspecialchars($e['_embedded']['venues'][0]['name']??'') ?>">
          <input type="hidden" name="city"    value="<?= htmlspecialchars($e['_embedded']['venues'][0]['city']['name']??'') ?>">
          <input type="hidden" name="url"     value="<?= htmlspecialchars($e['url']) ?>">
          <input type="hidden" name="category" value="<?= htmlspecialchars($e['classifications'][0]['segment']['name'] ?? 'Diğer') ?>">
          <button name="save_event" class="btn btn-green">Ekle</button>
        </form>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div id="tab2" class="tab">
    <h3>Eklenen Etkinlikler</h3>
    <ul>
    <?php
      $r = $conn->query("SELECT * FROM etkinlikler WHERE yayinda=0 ORDER BY tarih ASC");
      while($row=$r->fetch_assoc()):
    ?>
      <li>
        <form method="post" class="inline">
          <input type="hidden" name="etkinlik_id" value="<?= $row['id'] ?>">
          İsim: <input type="text" name="title" value="<?= htmlspecialchars($row['baslik']) ?>" required>
          Tarih:<input type="text" name="date"  value="<?= htmlspecialchars($row['tarih']) ?>" required>
          Yer:<input type="text" name="venue" value="<?= htmlspecialchars($row['yer']) ?>" required>
          Şehir:<input type="text" name="city"  value="<?= htmlspecialchars($row['sehir']) ?>" required>
          <button name="duzenle_eklenen" class="btn btn-blue">Değişiklikleri Kaydet</button>
        </form>
        <form method="post" class="inline" onsubmit="event.preventDefault(); confirmDelete(this);">
          <input type="hidden" name="eklenenden_cikar_id" value="<?= $row['id'] ?>">
          <button class="btn btn-red">Sil</button>
        </form>
        <form method="post" class="inline">
          <input type="hidden" name="yayina_al_id" value="<?= $row['id'] ?>">
          <button class="btn btn-green">Yayına Al</button>
        </form>
      </li>
    <?php endwhile; ?>
    </ul>
  </div>

  <div class="tab" id="tab3">
    <h3>Yayındaki Etkinlikler</h3>
    <ul>
    <?php 
    $result = $conn->query("SELECT * FROM etkinlikler WHERE yayinda = 1 ORDER BY tarih ASC");
    while ($row = $result->fetch_assoc()): ?>
        <li>
            <form method="post" class="inline">
                <input type="hidden" name="etkinlik_id" value="<?= $row['id'] ?>">
                İsim: <input type="text" name="title" value="<?= htmlspecialchars($row['baslik']) ?>" required>
                Tarih:<input type="text" name="date"  value="<?= htmlspecialchars($row['tarih']) ?>" required>
                Yer:<input type="text" name="venue" value="<?= htmlspecialchars($row['yer']) ?>" required>
                Şehir:<input type="text" name="city"  value="<?= htmlspecialchars($row['sehir']) ?>" required>
                <button name="duzenle_event" class="btn btn-blue">Değişiklikleri Kaydet</button>
            </form>

            <form method="post" class="inline">
                <input type="hidden" name="etkinlik_id" value="<?= $row['id'] ?>">
                <button name="yayindan_kaldir" class="btn btn-orange">Yayından Kaldır</button>
            </form>

            <?php if ((int)$row['durum'] === 0): ?>
                <div class="message error" style="margin-top:10px;">
                    Etkinlik hava şartlarından dolayı iptal edildi
                </div>
            <?php endif; ?>
        </li>
    <?php endwhile; ?>
    </ul>
</div>

<div id="tab_duyuru" class="tab">
  <h3>Duyuru Ekle / Düzenle</h3>

  <form method="post" style="margin-bottom:20px;">
    <input type="text" name="baslik" placeholder="Duyuru Başlığı" required style="width:100%;padding:8px;margin-bottom:10px;">
    <textarea name="icerik" placeholder="Duyuru İçeriği" required style="width:100%;height:100px;padding:8px;"></textarea>
    <button name="duyuru_ekle" class="btn btn-green">Duyuru Ekle</button>
  </form>

  <h4>Yayınlanmış Duyurular</h4>
  <ul>
  <?php 
    $duyuru_sorgu = $conn->query("SELECT * FROM duyurular ORDER BY tarih DESC");
    while ($d = $duyuru_sorgu->fetch_assoc()):
  ?>
    <li>
      <form method="post" class="inline">
        <input type="hidden" name="duyuru_id" value="<?= $d['id'] ?>">
        Başlık: <input type="text" name="baslik" value="<?= htmlspecialchars($d['baslik']) ?>" required>
        <br>
        İçerik: <textarea name="icerik" required style="width:100%;height:60px;"><?= htmlspecialchars($d['icerik']) ?></textarea>
        <br>
        <button name="duyuru_guncelle" class="btn btn-blue">Güncelle</button>
      </form>

      <form method="post" class="inline" style="margin-top:5px;">
        <input type="hidden" name="duyuru_id" value="<?= $d['id'] ?>">
        <button name="duyuru_sil" class="btn btn-red">Sil</button>
      </form>
    </li>
  <?php endwhile; ?>
  </ul>
</div>


</body>
</html>
