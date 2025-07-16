<?php
session_start();

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.html");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'etkinlik_yönetim');
if ($conn->connect_error) die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);

$selected_city = $_GET['city'] ?? '';
$tab = $_GET['tab'] ?? 'etkinlikler';

$kullanici_id = $_SESSION['kullanici_id'];

$cityResult = $conn->query("SELECT DISTINCT sehir FROM etkinlikler WHERE yayinda=1");
$sehirler = [];
while($row = $cityResult->fetch_assoc()) {
    $sehirler[] = $row['sehir'];
}
if(class_exists('Collator')) {
    (new Collator('tr_TR'))->sort($sehirler);
} else {
    sort($sehirler);
}

$etkinlikler = null;
$duyurular = null;
$onerilen_etkinlikler = null;

$stmt = $conn->prepare("SELECT ilgi_alanlari FROM kullanicilar WHERE id=?");
$stmt->bind_param("i", $kullanici_id);
$stmt->execute();
$stmt->bind_result($mevcut_ilgiler);
$stmt->fetch();
$stmt->close();

$secili_ilgiler = array_filter(array_map('trim', explode(',', $mevcut_ilgiler)));

if ($tab === 'etkinlikler') {
    $stmt = $conn->prepare(
        "SELECT * FROM etkinlikler WHERE yayinda=1 AND (?='' OR sehir=?) ORDER BY tarih ASC"
    );
    $stmt->bind_param("ss", $selected_city, $selected_city);
    $stmt->execute();
    $etkinlikler = $stmt->get_result();

} else if ($tab === 'duyurular') {
    $duyurular = $conn->query("SELECT * FROM duyurular ORDER BY tarih DESC");

} else if ($tab === 'etkinlik_onerisi') {
    if (count($secili_ilgiler) > 0) {
        $placeholders = implode(',', array_fill(0, count($secili_ilgiler), '?'));
        $types = str_repeat('s', count($secili_ilgiler));

        $sql = "SELECT * FROM etkinlikler WHERE yayinda=1 AND kategori IN ($placeholders) ";
        if ($selected_city !== '') {
            $sql .= " AND sehir=? ";
            $types .= 's';
        }
        $sql .= " ORDER BY tarih ASC";

        $stmt = $conn->prepare($sql);

        if ($selected_city !== '') {
            $params = array_merge($secili_ilgiler, [$selected_city]);
        } else {
            $params = $secili_ilgiler;
        }

        $tmp = [];
        foreach ($params as $key => $value) {
            $tmp[$key] = &$params[$key];
        }

        array_unshift($tmp, $types);
        call_user_func_array([$stmt, 'bind_param'], $tmp);

        $stmt->execute();
        $onerilen_etkinlikler = $stmt->get_result();

    } else {
        $onerilen_etkinlikler = null;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Etkinlikler ve Duyurular</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
    .tabs { display: flex; margin-bottom: 20px; }
    .tab {
      padding: 10px 20px;
      background-color: #ddd;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;
      margin-right: 5px;
      cursor: pointer;
      text-decoration: none;
      color: #333;
      font-weight: bold;
    }
    .tab.active { background-color: #2196F3; color: white; }
    form { margin-bottom: 20px; }
    select {
      padding: 5px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
    ul { list-style: none; padding: 0; }
    li {
      padding: 12px;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      background-color: #fff;
    }
    .cancelled {
      color: #d32f2f;
      font-weight: bold;
      margin-top: 5px;
    }
    .message {
      padding: 12px;
      border-radius: 5px;
      font-weight: bold;
      background: #e0f7fa;
      border: 1px solid #00bcd4;
      color: #006064;
      width: fit-content;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<div style="position: fixed; top: 10px; right: 10px;">
  <a href="sepet.php" style="text-decoration: none;">
    <img src="https://cdn-icons-png.flaticon.com/512/1170/1170678.png" alt="Sepet" width="40" title="Sepetim">
  </a>
</div>

<div class="tabs">
  <a href="?tab=duyurular" class="tab <?= $tab === 'duyurular' ? 'active' : '' ?>">Duyurular</a>
  <a href="?tab=etkinlikler" class="tab <?= $tab === 'etkinlikler' ? 'active' : '' ?>">Etkinlikleri Göster</a>
  <a href="?tab=etkinlik_onerisi" class="tab <?= $tab === 'etkinlik_onerisi' ? 'active' : '' ?>">Etkinlik Önerisi</a>
  <a href="?tab=hesabim" class="tab <?= $tab === 'hesabim' ? 'active' : '' ?>">Hesabım</a>
  <a href="cikis.php" class="tab">Çıkış</a>
</div>

<?php if ($tab === 'duyurular'): ?>
  <ul>
  <?php if ($duyurular && $duyurular->num_rows > 0): ?>
    <?php while ($d = $duyurular->fetch_assoc()): ?>
      <li>
        <strong><?= htmlspecialchars($d['baslik']) ?></strong><br>
        <?= nl2br(htmlspecialchars($d['icerik'])) ?><br>
        <em><?= htmlspecialchars($d['tarih']) ?></em>
      </li>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="message">Henüz duyuru bulunmamaktadır.</div>
  <?php endif; ?>
  </ul>

<?php elseif ($tab === 'etkinlikler'): ?>
  <form method="get">
    <input type="hidden" name="tab" value="etkinlikler">
    <label for="city">Şehir:</label>
    <select name="city" id="city" onchange="this.form.submit()">
      <option value="">Tümü</option>
      <?php foreach ($sehirler as $s): ?>
        <option value="<?= htmlspecialchars($s) ?>" <?= $selected_city === $s ? 'selected' : '' ?>>
          <?= htmlspecialchars($s) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <ul>
  <?php if ($etkinlikler && $etkinlikler->num_rows > 0): ?>
    <?php while ($row = $etkinlikler->fetch_assoc()): ?>
      <li>
        <strong><?= htmlspecialchars($row['baslik']) ?></strong><br>
        Tarih: <?= htmlspecialchars($row['tarih']) ?><br>
        Yer: <?= htmlspecialchars($row['yer']) ?><br>
        Şehir: <?= htmlspecialchars($row['sehir']) ?><br>
        Kategori: <?= htmlspecialchars($row['kategori']) ?><br>
        Kontenjan: <?= (int)$row['kontenjan'] ?><br>

        <?php if ($row['durum'] != 0): ?>
        <form action="bilet_al.php" method="post" style="margin-top:10px;">
          <input type="hidden" name="etkinlik_id" value="<?= $row['id'] ?>">
          <label>Bilet Adedi:</label>
          <select name="adet">
          <?php 
            $maksimum_adet = min(10, (int)$row['kontenjan']);
            for ($i = 1; $i <= $maksimum_adet; $i++): 
          ?>
            <option value="<?= $i ?>"><?= $i ?></option>
          <?php endfor; ?>
          </select>
          <button type="submit" style="margin-left:10px;">Bilet Al</button>
        </form>
        <?php endif; ?>

        <?php if ($row['durum'] == 0): ?>
          <div class="cancelled">Etkinlik hava şartlarından dolayı iptal edildi</div>
        <?php endif; ?>
      </li>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="message">Etkinlik Bulunamadı.</div>
  <?php endif; ?>
  </ul>

<?php elseif ($tab === 'etkinlik_onerisi'): ?>
  <form method="get">
    <input type="hidden" name="tab" value="etkinlik_onerisi">
    <label for="city">Şehir:</label>
    <select name="city" id="city" onchange="this.form.submit()">
      <option value="">Tümü</option>
      <?php foreach ($sehirler as $s): ?>
        <option value="<?= htmlspecialchars($s) ?>" <?= $selected_city === $s ? 'selected' : '' ?>>
          <?= htmlspecialchars($s) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <ul>
  <?php if ($onerilen_etkinlikler && $onerilen_etkinlikler->num_rows > 0): ?>
    <?php while ($row = $onerilen_etkinlikler->fetch_assoc()): ?>
      <li>
        <strong><?= htmlspecialchars($row['baslik']) ?></strong><br>
        Tarih: <?= htmlspecialchars($row['tarih']) ?><br>
        Yer: <?= htmlspecialchars($row['yer']) ?><br>
        Şehir: <?= htmlspecialchars($row['sehir']) ?><br>
        Kategori: <?= htmlspecialchars($row['kategori']) ?><br>
        Kontenjan: <?= (int)$row['kontenjan'] ?><br>

        <?php if ($row['durum'] != 0): ?>
        <form action="bilet_al.php" method="post" style="margin-top:10px;">
          <input type="hidden" name="etkinlik_id" value="<?= $row['id'] ?>">
          <label>Bilet Adedi:</label>
          <select name="adet">
          <?php 
            $maksimum_adet = min(10, (int)$row['kontenjan']);
            for ($i = 1; $i <= $maksimum_adet; $i++): 
          ?>
            <option value="<?= $i ?>"><?= $i ?></option>
          <?php endfor; ?>
          </select>
          <button type="submit" style="margin-left:10px;">Bilet Al</button>
        </form>
        <?php endif; ?>

        <?php if ($row['durum'] == 0): ?>
          <div class="cancelled">Etkinlik hava şartlarından dolayı iptal edildi</div>
        <?php endif; ?>
      </li>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="message">İlgi alanlarınıza uygun etkinlik bulunamadı veya ilgi alanınız seçili değil.</div>
  <?php endif; ?>
  </ul>

<?php elseif ($tab === 'hesabim'): ?>
  <?php
    $mevcut_ilgiler = '';
    $mesaj = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $secimler = $_POST['ilgi_alanlari'] ?? [];
        $mevcut_ilgiler = implode(',', $secimler);

        $stmt = $conn->prepare("UPDATE kullanicilar SET ilgi_alanlari=? WHERE id=?");
        $stmt->bind_param("si", $mevcut_ilgiler, $kullanici_id);
        if ($stmt->execute()) {
            $mesaj = "İlgi alanları güncellendi.";
        } else {
            $mesaj = "Güncelleme başarısız.";
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("SELECT ilgi_alanlari FROM kullanicilar WHERE id=?");
        $stmt->bind_param("i", $kullanici_id);
        $stmt->execute();
        $stmt->bind_result($mevcut_ilgiler);
        $stmt->fetch();
        $stmt->close();
    }

    $tum_kategoriler = ["Müzik", "Sanat Ve Tiyatro", "Sinema", "Konferans", "Festival","Çeşitli"];
    $secili_ilgiler = array_filter(array_map('trim', explode(',', $mevcut_ilgiler)));
  ?>

  <h2>Hesap Bilgileri</h2>
  <?php if ($mesaj): ?>
    <div class="message"><?= htmlspecialchars($mesaj) ?></div>
  <?php endif; ?>

  <form method="post">
    <label>İlgi Alanları:</label><br>
    <?php foreach ($tum_kategoriler as $kategori): ?>
      <input type="checkbox" name="ilgi_alanlari[]" value="<?= htmlspecialchars($kategori) ?>"
      <?= in_array($kategori, $secili_ilgiler) ? 'checked' : '' ?>> <?= htmlspecialchars($kategori) ?><br>
    <?php endforeach; ?>
    <button type="submit" style="margin-top: 10px;">Güncelle</button>
  </form>

<?php else: ?>
  <div>Geçersiz sekme seçimi.</div>
<?php endif; ?>

</body>
</html>
