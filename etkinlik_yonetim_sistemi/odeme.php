<?php
session_start();

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.html");
    exit;
}

$sepet = $_SESSION['sepet'] ?? [];

if (empty($sepet)) {
    echo "<p style='color:red; font-weight:bold;'>Sepetiniz boş. Ödeme yapamazsınız.</p>";
    echo "<p><a href='kullanici_sayfa.php?tab=etkinlikler'>Ana Sayfaya Dön</a></p>";
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'etkinlik_yönetim');
if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

$toplam_tutar = 0;
foreach ($sepet as $item) {
    $toplam_tutar += $item['toplam'];
}

$odeme_basarili = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['kartno']) && !empty($_POST['sonkullanim']) && !empty($_POST['cvv'])) {
        $hata_var = false;

        // Kontenjanları tekrar kontrol et
        foreach ($sepet as $item) {
            $etkinlik_id = (int)$item['etkinlik_id'];
            $adet = (int)$item['adet'];

            $stmt = $conn->prepare("SELECT kontenjan FROM etkinlikler WHERE id = ?");
            $stmt->bind_param("i", $etkinlik_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            // Kontenjan zaten düşürülmüş olabilir, burası aslında bir önlem
            if ($row === null || $row['kontenjan'] < 0) {
                $hata_var = true;
                break;
            }
        }

        if (!$hata_var) {
            // Ödeme başarılı kabul, sepeti temizle
            $_SESSION['sepet'] = [];
            $odeme_basarili = true;

        } else {
            $hata_mesaji = "Bazı etkinliklerde kontenjan problemi var. Lütfen sepetinizi kontrol edin.";
        }
    } else {
        $hata_mesaji = "Lütfen tüm ödeme bilgilerini doldurun.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Ödeme Sayfası</title>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; }
  form { max-width: 400px; background: white; padding: 20px; border-radius: 8px; }
  label { display: block; margin-top: 15px; }
  input[type="text"], input[type="number"] {
    width: 100%; padding: 8px; margin-top: 5px; border-radius: 4px; border: 1px solid #ccc;
  }
  button {
    margin-top: 20px; padding: 10px 15px; background: #2196F3; border: none; color: white; border-radius: 5px;
    cursor: pointer;
  }
  .success-message {
    text-align: center; background: #e8f5e9; padding: 40px; border-radius: 10px;
  }
  .success-message h2 { color: #388e3c; }
  .error-message {
    background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 15px;
  }
</style>
</head>
<body>

<?php if ($odeme_basarili): ?>
  <div class="success-message">
    <h2>Teşekkürler! Ödemeniz başarıyla alındı.</h2>
    <p>Etkinlik biletleriniz e-posta adresinize gönderilecektir.</p>
    <p><a href="kullanici_sayfa.php?tab=etkinlikler">Ana Sayfaya Dön</a></p>
  </div>
<?php else: ?>
  <h2>Ödeme Sayfası</h2>
  <p>Toplam Tutar: <strong><?= number_format($toplam_tutar, 2) ?> TL</strong></p>

  <?php if (!empty($hata_mesaji)): ?>
    <div class="error-message"><?= htmlspecialchars($hata_mesaji) ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <label for="kartno">Kart Numarası:</label>
    <input type="text" id="kartno" name="kartno" placeholder="1111 2222 3333 4444" required pattern="\d{4} \d{4} \d{4} \d{4}">

    <label for="sonkullanim">Son Kullanma Tarihi (AA/YY):</label>
    <input type="text" id="sonkullanim" name="sonkullanim" placeholder="12/25" required pattern="\d{2}/\d{2}">

    <label for="cvv">CVV:</label>
    <input type="number" id="cvv" name="cvv" placeholder="123" required min="100" max="999">

    <button type="submit">Ödemeyi Tamamla</button>
  </form>

  <p><a href="sepet.php">Sepete Geri Dön</a></p>
<?php endif; ?>

</body>
</html>
