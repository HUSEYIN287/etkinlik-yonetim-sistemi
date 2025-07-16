<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "etkinlik_yönetim";
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) die("Veritabanı bağlantı hatası: " . $conn->connect_error);

// Onay veya reddetme işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['onayla_kullanici'])) {
        $id = intval($_POST['kullanici_id']);
        $conn->query("UPDATE kullanicilar SET onayli = 1 WHERE id = $id");
        $message = "Kullanıcı onaylandı.";
        $message_type = "success";
    }

    if (isset($_POST['reddet_kullanici'])) {
        $id = intval($_POST['kullanici_id']);
        $conn->query("DELETE FROM kullanicilar WHERE id = $id");
        $message = "Kullanıcı reddedildi ve silindi.";
        $message_type = "error";
    }
}

// Listele
$kullanicilar = $conn->query("SELECT * FROM kullanicilar WHERE onayli = 0 ORDER BY id ASC");
?>

<style>
    #kayit-onay table {
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        table-layout: fixed;
    }
    #kayit-onay th, #kayit-onay td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
        word-wrap: break-word;
    }
    #kayit-onay thead {
        background-color: #007BFF;
        color: white;
    }
    #kayit-onay tbody tr:hover {
        background-color: #f1f1f1;
    }
    #kayit-onay form {
        display: flex;
        gap: 5px;
        flex-wrap: nowrap;
    }
    #kayit-onay button {
    cursor: pointer;
    border: none;
    padding: 10px 16px; /* Yükseklik ve genişlik arttı */
    font-size: 15px;     /* Yazı büyüdü */
    border-radius: 6px;  /* Daha yumuşak köşeler */
    transition: background-color 0.3s ease;
    flex-shrink: 0;
    }
    #kayit-onay .btn-green {
        background-color: #28a745;
        color: white;
        margin-right: 20px;
    }
    #kayit-onay .btn-green:hover {
        background-color: #218838;
    }
    #kayit-onay .btn-red {
        background-color: #dc3545;
        color: white;
    }
    #kayit-onay .btn-red:hover {
        background-color: #c82333;
    }
    #kayit-onay th:nth-child(1), #kayit-onay td:nth-child(1) {
        width: 40%;
    }
    #kayit-onay th:nth-child(2), #kayit-onay td:nth-child(2) {
        width: 30%;
    }
    #kayit-onay th:nth-child(3), #kayit-onay td:nth-child(3) {
        width: 30%;
    }
</style>

<div id="kayit-onay">
    <h3>🕒 Onay Bekleyen Kullanıcılar</h3>
    <?php if (!empty($message)): ?>
        <div class="message <?= $message_type ?>"><?= $message ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Mail</th>
                <th>Şifre</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($kullanici = $kullanicilar->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($kullanici['mail']) ?></td>
                    <td><?= str_repeat('*', strlen($kullanici['sifre'])) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="kullanici_id" value="<?= $kullanici['id'] ?>">
                            <button type="submit" name="onayla_kullanici" class="btn-green">✅ Onayla</button>
                            <button type="submit" name="reddet_kullanici" class="btn-red">❌ Reddet</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>





