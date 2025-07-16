<?php
// Seçili şehri al, yoksa 'Van'
$weather_city = isset($selected_city) && $selected_city !== '' ? $selected_city : 'Van';

$apiKey = "28af9dfb3e4ccd862d34fb778e542a5c";
$city_encoded = urlencode($weather_city);
$url = "https://api.openweathermap.org/data/2.5/weather?q={$city_encoded},tr&appid={$apiKey}&units=metric&lang=tr";

$response = @file_get_contents($url);
if ($response !== false) {
    $data = json_decode($response, true);

    if (isset($data['main'])) {
        $sicaklik    = round($data['main']['temp']);
        // Hava koşulu küçük harfe dönüştürülmüş: karar mantığında kullanacağız
        $condition   = strtolower($data['weather'][0]['description']);
        // Ekranda göstereceğimiz başlıklı hali:
        $condition_display = ucfirst($condition);
        $icon        = $data['weather'][0]['icon'];

        echo "
        <div style='position:absolute; top:10px; right:10px; background:#f1f1f1; padding:10px 15px; border-radius:8px; font-family:sans-serif; font-size:14px; box-shadow:0 0 10px rgba(0,0,0,0.1); display:flex; align-items:center; gap:8px; z-index:999;'>
            <img src='https://openweathermap.org/img/wn/{$icon}@2x.png' alt='icon' style='width:32px; height:32px;'>
            <div>
                <strong>" . htmlspecialchars($weather_city) . "</strong><br>
                {$condition_display} {$sicaklik}°C
            </div>
        </div>
        ";
    } else {
        echo "<div style='position:absolute; top:10px; right:10px; background:#fdd; padding:10px; border-radius:8px;'>Hava durumu alınamadı.</div>";
    }
} else {
    echo "<div style='position:absolute; top:10px; right:10px; background:#fdd; padding:10px; border-radius:8px;'>Bağlantı hatası.</div>";
}
?>
