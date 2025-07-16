<?php
session_start();
$host = "localhost";
$db_adi = "etkinlik_yönetim";
$kullanici = "root";
$sifre = "";

$baglanti = new mysqli($host, $kullanici, $sifre, $db_adi);
if ($baglanti->connect_error) {
    die("Bağlantı hatası: " . $baglanti->connect_error);
}

// Şifre değiştirme işlemi
if (isset($_POST['yeni_sifre']) && isset($_SESSION['kullanici_id'])) {
    $yeni_sifre = $_POST['yeni_sifre'];
    $id = $_SESSION['kullanici_id'];

    $sql = "UPDATE kullanicilar SET sifre = ?, sifre_degistir = 0 WHERE id = ?";
    $stmt = $baglanti->prepare($sql);
    $stmt->bind_param("si", $yeni_sifre, $id);

    if ($stmt->execute()) {
        echo "<h2 style='color:green; text-align:center;'>Şifre başarıyla değiştirildi.</h2>";
        session_unset();
        session_destroy();
        echo "<p style='text-align:center;'><a href='giris.html'>Yeniden giriş yap</a></p>";
    } else {
        echo "<h2 style='color:red; text-align:center;'>Şifre değiştirilemedi.</h2>";
    }

    $stmt->close();
    $baglanti->close();
    exit;
}

// Giriş işlemi
if (isset($_POST['giris'])) {
    $mail = $_POST['mail'];
    $kullanici_sifre = $_POST['sifre'];

    $sql = "SELECT * FROM kullanicilar WHERE mail = ? AND sifre = ?";
    $stmt = $baglanti->prepare($sql);
    $stmt->bind_param("ss", $mail, $kullanici_sifre);
    $stmt->execute();
    $sonuc = $stmt->get_result();

    if ($sonuc->num_rows > 0) {
        $kullanici = $sonuc->fetch_assoc();

        if ($kullanici['id'] == 1) {
            $_SESSION['admin'] = true;
            header("Location: admin_panel.php");
            exit;
        } elseif ($kullanici['onayli'] == 1) {
            $_SESSION['kullanici_id'] = $kullanici['id'];

            if ($kullanici['sifre_degistir'] == 1) {
                // Şifre değiştirme formunu göster
                ?>
                <!DOCTYPE html>
                <html lang="tr">
                <head>
                    <meta charset="UTF-8">
                    <title>Şifre Değiştir</title>
                </head>
                <body>
                    <h2 style="text-align:center;">Lütfen yeni şifrenizi girin</h2>
                    <form method="POST" style="text-align:center;">
                        <input type="password" name="yeni_sifre" placeholder="Yeni Şifre" required><br><br>
                        <button type="submit">Şifreyi Güncelle</button>
                    </form>
                </body>
                </html>
                <?php
                exit;
            } else {
                 header("Location: kullanici_sayfa.php");
            }
        } else {
            echo "<h2 style='color:orange; text-align:center;'>Hesabınız henüz onaylanmamış.</h2>";
        }
    } else {
        echo "<h2 style='color:red; text-align:center;'>Mail veya şifre hatalı</h2>";
         echo "<p style='text-align:center;'><a href='giris.html'>Giriş Sayfasına Dön</a></p>";
    }

    $stmt->close();
}

// Kayıt işlemi
elseif (isset($_POST['kayit'])) {
    $mail = $_POST['mail'];
    $kullanici_sifre = $_POST['sifre'];

    $sql = "INSERT INTO kullanicilar (mail, sifre) VALUES (?, ?)";
    $stmt = $baglanti->prepare($sql);
    $stmt->bind_param("ss", $mail, $kullanici_sifre);

    if ($stmt->execute()) {
        echo "<h2 style='color:green; text-align:center;'>Kayıt başarıyla oluşturuldu.<br>Lütfen yöneticinin onaylamasını bekleyin.</h2>";
        echo "<p style='text-align:center;'><a href='giris.html'>Giriş Sayfasına Dön</a></p>";
    } else {
        echo "<h2 style='color:red; text-align:center;'>Kayıt başarısız: " . $stmt->error . "</h2>";
    }
    $stmt->close();
}

$baglanti->close();
?>
