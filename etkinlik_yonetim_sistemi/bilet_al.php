<?php
session_start();

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.html");
    exit;
}

if (!isset($_POST['etkinlik_id']) || !isset($_POST['adet'])) {
    die("Hatalı istek.");
}

$etkinlik_id = (int) $_POST['etkinlik_id'];
$adet = (int) $_POST['adet'];

if ($adet < 1 || $adet > 10) {
    die("Geçersiz bilet adedi.");
}

$conn = new mysqli('localhost', 'root', '', 'etkinlik_yönetim');
if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

// Etkinlik bilgilerini al ve stok kontrolü yap
$stmt = $conn->prepare("SELECT id, baslik, sehir, tarih, kategori, kontenjan FROM etkinlikler WHERE id = ?");
$stmt->bind_param("i", $etkinlik_id);
$stmt->execute();
$result = $stmt->get_result();
$etkinlik = $result->fetch_assoc();
$stmt->close();

if (!$etkinlik) {
    die("Etkinlik bulunamadı.");
}

if ($etkinlik['kontenjan'] < $adet) {
    die("Yeterli kontenjan yok.");
}


if (!isset($_SESSION['sepet'])) {
    $_SESSION['sepet'] = [];
}

// Sepette aynı etkinlik varsa adet güncelle, yoksa yeni ekle
$var_mi = false;
foreach ($_SESSION['sepet'] as &$item) {
    if ($item['etkinlik_id'] === $etkinlik_id) {
        // Yeni toplam adet stok sınırını aşmamalı
        if ($item['adet'] + $adet <= 10 && $etkinlik['kontenjan'] >= ($item['adet'] + $adet)) {
            $item['adet'] += $adet;
            $item['toplam'] = $item['adet'] * $item['fiyat'];
            $var_mi = true;
            break;
        } else {
            die("Sepette toplamda maksimum 10 adet ve mevcut stoktan fazla bilet olamaz.");
        }
    }
}
unset($item);

if (!$var_mi) {
    $_SESSION['sepet'][] = [
        'etkinlik_id' => $etkinlik_id,
        'baslik' => $etkinlik['baslik'],
        'sehir' => $etkinlik['sehir'],
        'tarih' => $etkinlik['tarih'],
        'kategori' => $etkinlik['kategori'],
        'adet' => $adet,
        'fiyat' => $fiyat,
        'toplam' => $toplam,
    ];
}

// Kontenjanı azalt sepete eklerken
$stmt = $conn->prepare("UPDATE etkinlikler SET kontenjan = kontenjan - ? WHERE id = ? AND kontenjan >= ?");
$stmt->bind_param("iii", $adet, $etkinlik_id, $adet);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    // Yetersiz kontenjan, geri alma işlemi (çünkü stok azalmadı)
    die("Sepete eklenirken stok problemi oluştu.");
}

$stmt->close();

header("Location: sepet.php");
exit;
