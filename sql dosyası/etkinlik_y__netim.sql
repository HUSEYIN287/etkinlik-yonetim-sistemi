-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 26 May 2025, 04:44:00
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `etkinlik_yönetim`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `biletler`
--

CREATE TABLE `biletler` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `etkinlik_id` int(11) DEFAULT NULL,
  `adet` int(11) DEFAULT NULL,
  `satin_alma_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `duyurular`
--

CREATE TABLE `duyurular` (
  `id` int(11) NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `icerik` text NOT NULL,
  `tarih` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `duyurular`
--

INSERT INTO `duyurular` (`id`, `baslik`, `icerik`, `tarih`) VALUES
(4, 'yeni', 'yeni', '2025-05-25 04:02:52'),
(5, 'daha yeni', 'daha yeni', '2025-05-25 04:03:22'),
(6, 'en yeni', 'en yeni', '2025-05-25 04:03:36');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `etkinlikler`
--

CREATE TABLE `etkinlikler` (
  `id` int(11) NOT NULL,
  `api_id` varchar(100) DEFAULT NULL,
  `baslik` varchar(255) DEFAULT NULL,
  `tarih` date DEFAULT NULL,
  `yer` varchar(255) DEFAULT NULL,
  `sehir` varchar(100) DEFAULT NULL,
  `detay_url` text DEFAULT NULL,
  `yayinda` tinyint(1) DEFAULT 0,
  `durum` tinyint(1) DEFAULT 1,
  `kategori` varchar(255) DEFAULT '',
  `fiyat` decimal(10,2) DEFAULT 0.00,
  `kontenjan` int(11) DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `etkinlikler`
--

INSERT INTO `etkinlikler` (`id`, `api_id`, `baslik`, `tarih`, `yer`, `sehir`, `detay_url`, `yayinda`, `durum`, `kategori`, `fiyat`, `kontenjan`) VALUES
(248, 'Z1HyzZyMZA8Lakkt', 'Sefa Doğanay', '2025-05-26', 'Avlu Kongre ve Kültür Merkezi - Ertuğrul Salonu', 'Balıkesir', 'https://www.biletix.com/performance/4FU25/001/TURKIYE/tr', 1, 0, 'Sanat ve Tiyatro', 450.00, 100),
(249, 'Z1HyzZyMZACQao4_', 'Murad Demir', '2025-05-26', 'Diyarbakır Sezai Karakoç Kültür ve Kongre Merkezi', 'Diyarbakır', 'https://www.biletix.com/performance/4DP71/001/TURKIYE/tr', 1, 0, 'Müzik', 400.00, 100);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `onayli` tinyint(1) DEFAULT 0,
  `sifre_degistir` int(11) DEFAULT 1,
  `ilgi_alanlari` varchar(500) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`id`, `mail`, `sifre`, `onayli`, `sifre_degistir`, `ilgi_alanlari`) VALUES
(1, 'admin@gmail.com', '123', 1, 0, 'müzik,sinema'),
(23, 'aaaaaa@gmail.com', '1234', 1, 0, ''),
(24, 'aaaaaa@gmail.com', '123', 1, 0, 'Müzik');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `biletler`
--
ALTER TABLE `biletler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kullanici_id` (`kullanici_id`),
  ADD KEY `etkinlik_id` (`etkinlik_id`);

--
-- Tablo için indeksler `duyurular`
--
ALTER TABLE `duyurular`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `etkinlikler`
--
ALTER TABLE `etkinlikler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_id` (`api_id`);

--
-- Tablo için indeksler `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `biletler`
--
ALTER TABLE `biletler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `duyurular`
--
ALTER TABLE `duyurular`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `etkinlikler`
--
ALTER TABLE `etkinlikler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=250;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicilar`
--
ALTER TABLE `kullanicilar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `biletler`
--
ALTER TABLE `biletler`
  ADD CONSTRAINT `biletler_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`),
  ADD CONSTRAINT `biletler_ibfk_2` FOREIGN KEY (`etkinlik_id`) REFERENCES `etkinlikler` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
