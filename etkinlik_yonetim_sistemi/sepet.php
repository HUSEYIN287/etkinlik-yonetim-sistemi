<?php
session_start();

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.html");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'etkinlik_yönetim');
if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

$sepet = $_SESSION['sepet'] ?? [];
$uyari = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $index = isset($_POST['index']) ? (int)$_POST['index'] : null;

    if (isset($_POST['sil']) && isset($sepet[$index])) {
        $etkinlik_id = $sepet[$index]['etkinlik_id'];
        $adet = $sepet[$index]['adet'];

        $stmt = $conn->prepare("UPDATE etkinlikler SET kontenjan = kontenjan + ? WHERE id = ?");
        $stmt->bind_param("ii", $adet, $etkinlik_id);
        $stmt->execute();
        $stmt->close();

        unset($sepet[$index]);
        $_SESSION['sepet'] = array_values($sepet);
        $sepet = $_SESSION['sepet'];

    } elseif (isset($_POST['adet_artir']) && isset($sepet[$index])) {
        $etkinlik_id = $sepet[$index]['etkinlik_id'];
        $adet = $sepet[$index]['adet'];

        $stmt = $conn->prepare("SELECT kontenjan FROM etkinlikler WHERE id = ?");
        $stmt->bind_param("i", $etkinlik_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $kontenjan = $row['kontenjan'] ?? 0;
        $stmt->close();

        if ($adet < 10 && $kontenjan > 0) {
            $stmt = $conn->prepare("UPDATE etkinlikler SET kontenjan = kontenjan - 1 WHERE id = ?");
            $stmt->bind_param("i", $etkinlik_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $sepet[$index]['adet']++;
                $sepet[$index]['toplam'] = $sepet[$index]['adet'] * $sepet[$index]['fiyat'];
                $_SESSION['sepet'] = $sepet;
            }
            $stmt->close();
        } else {
            $uyari = "Kontenjan sınırı aşıldı.";
        }

    } elseif (isset($_POST['adet_azalt']) && isset($sepet[$index])) {
        $etkinlik_id = $sepet[$index]['etkinlik_id'];

        if ($sepet[$index]['adet'] > 1) {
            $sepet[$index]['adet']--;
            $sepet[$index]['toplam'] = $sepet[$index]['adet'] * $sepet[$index]['fiyat'];
            $_SESSION['sepet'] = $sepet;

            $stmt = $conn->prepare("UPDATE etkinlikler SET kontenjan = kontenjan + 1 WHERE id = ?");
            $stmt->bind_param("i", $etkinlik_id);
            $stmt->execute();
            $stmt->close();
        }

    } elseif (isset($_POST['bosalt'])) {
        foreach ($sepet as $item) {
            $stmt = $conn->prepare("UPDATE etkinlikler SET kontenjan = kontenjan + ? WHERE id = ?");
            $stmt->bind_param("ii", $item['adet'], $item['etkinlik_id']);
            $stmt->execute();
            $stmt->close();
        }
        $_SESSION['sepet'] = [];
        $sepet = [];

    } elseif (isset($_POST['odeme_yap'])) {
        if (empty($sepet)) {
            $uyari = "Sepet boş. Ödeme yapılamaz.";
        } else {
            header("Location: odeme.php");
            exit;
        }
    }
}

// Güncel kontenjan ve fiyatları sepete tekrar çek
foreach ($sepet as $i => $item) {
    $etkinlik_id = (int)$item['etkinlik_id'];
    $stmt = $conn->prepare("SELECT kontenjan, fiyat FROM etkinlikler WHERE id = ?");
    $stmt->bind_param("i", $etkinlik_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row) {
        $sepet[$i]['kontenjan'] = $row['kontenjan'];
        $sepet[$i]['fiyat'] = $row['fiyat'];
        $sepet[$i]['toplam'] = $sepet[$i]['adet'] * $row['fiyat'];
    }
    $stmt->close();
}
$_SESSION['sepet'] = $sepet;

$genel_toplam = array_sum(array_column($sepet, 'toplam'));
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Sepetim</title>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
  th { background: #2196F3; color: white; }
  button { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; }
  .btn-danger { background: #d32f2f; color: white; }
  .btn-primary { background: #2196F3; color: white; }
  .btn-secondary { background: #757575; color: white; }
  .btn-group button { margin: 0 3px; }
  .uyari { color: red; margin-bottom: 10px; font-weight: bold; }
</style>
</head>
<body>

<h2>Sepetiniz</h2>

<?php if (!empty($uyari)): ?>
    <div class="uyari"><?= htmlspecialchars($uyari) ?></div>
<?php endif; ?>

<?php if (empty($sepet)): ?>
    <p>Sepetiniz boş.</p>
<?php else: ?>
<table>
    <thead>
    <tr>
        <th>Etkinlik</th>
        <th>Şehir</th>
        <th>Tarih</th>
        <th>Kategori</th>
        <th>Bilet Adedi</th>
        <th>Birim Fiyat (TL)</th>
        <th>Toplam (TL)</th>
        <th>Kalan Kontenjan</th>
        <th>İşlem</th>
    </tr>
    </thead>
    <tbody>
<?php foreach ($sepet as $index => $item): ?>
    <tr>
        <td><?= htmlspecialchars($item['baslik']) ?></td>
        <td><?= htmlspecialchars($item['sehir']) ?></td>
        <td><?= htmlspecialchars($item['tarih']) ?></td>
        <td><?= htmlspecialchars($item['kategori']) ?></td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="index" value="<?= $index ?>">
                <div class="btn-group">
                    <button type="submit" name="adet_azalt" <?= $item['adet'] <= 1 ? 'disabled' : '' ?>>-</button>
                </div>
            </form>

            <?= $item['adet'] ?>

            <form method="post" style="display:inline;">
                <input type="hidden" name="index" value="<?= $index ?>">
                <div class="btn-group">
                    <button type="submit" name="adet_artir" <?= $item['adet'] >= 10 || $item['kontenjan'] <= 0 ? 'disabled' : '' ?>>+</button>
                </div>
            </form>
        </td>
        <td><?= number_format($item['fiyat'], 2) ?></td>
        <td><?= number_format($item['toplam'], 2) ?></td>
        <td><?= $item['kontenjan'] ?></td>
        <td>
            <form method="post">
                <input type="hidden" name="index" value="<?= $index ?>">
                <button type="submit" name="sil" class="btn-danger">Sil</button>
            </form>
        </td>
    </tr>
<?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="6" style="text-align:right;">Genel Toplam:</th>
            <th><?= number_format($genel_toplam, 2) ?> TL</th>
            <th colspan="2"></th>
        </tr>
    </tfoot>
</table>

<form method="post" style="display:inline;">
    <button type="submit" name="bosalt" class="btn-secondary" onclick="return confirm('Sepeti boşaltmak istiyor musunuz?')">Sepeti Boşalt</button>
</form>

<form method="post" style="display:inline; float:right;">
    <button type="submit" name="odeme_yap" class="btn-primary">Ödemeye Geç</button>
</form>
<?php endif; ?>

<p><a href="kullanici_sayfa.php?tab=etkinlikler">Ana Sayfaya Dön</a></p>

</body>
</html>
