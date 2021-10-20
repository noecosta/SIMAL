-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Erstellungszeit: 20. Okt 2021 um 15:01
-- Server-Version: 10.5.5-MariaDB
-- PHP-Version: 7.4.4

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `simal`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `alert`
--

CREATE TABLE `alert` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `state` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `title` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(350) COLLATE utf8mb4_unicode_ci NOT NULL,
  `informations` varchar(350) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publish_from` date NOT NULL DEFAULT current_timestamp(),
  `publish_to` date DEFAULT NULL,
  `creator` bigint(20) UNSIGNED NOT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `alert`
--

INSERT INTO `alert` (`id`, `state`, `title`, `description`, `informations`, `publish_from`, `publish_to`, `creator`, `isDeleted`) VALUES
(1, 1, 'Willkommen', 'Herzlich Willkommen auf der SIMAL Applikation. Hier stehen relevante Informationen, welche die Bevölkerung auf diverse Gefahren und Warnungen hinweisen sollen.', 'Hier würden zusätzliche Informationen an die Bevölkerung stehen. 👻', '2021-10-20', NULL, 1, 0),
(2, 3, 'Sars-CoV-2 Verbreitungsgefahr', 'Die neusten Mutationen des grassierenden Sars-CoV-2 Viruses verbreiten sich erheblich schneller als bisherige Varianten.', 'Bitte isolieren Sie sich bei auftretenden Symptomen umgehend und kontaktieren Sie ihren Hausarzt oder rufen Sie bei der lokalen Gesundheitsbehörde an. Es wird generell empfohlen auf alle nicht-notwendigen sozialen Interaktionen zu verzichten.', '2021-10-20', '2022-12-31', 2, 0),
(3, 2, 'Pestizide im Grundwasser', 'Im Kanton Aargau wurden am Donnerstagmorgen bei einer regulären Kontrolle Pestizide im Grundwasser entdeckt.', 'Bitte informieren Sie sich bei Ihrer zuständigen Behörde über eventuelle Massnahmen und Sicherheitsvorkehrungen. Es sollte bis auf weiteres kein Trinkwasser aus Grundquellen bezogen werden; zur Körperhygiene kann bedenkenlos Grundwasser verwendet werden.', '2021-10-20', NULL, 2, 0),
(4, 1, 'Nächste Abstimmungen', 'Die Unterlagen zu den nächsten bundesweiten Abstimmungen sind bei ihrem lokalen Amt verfügbar.\r\nWir würden uns freuen wenn Sie sich daran beteiligen sollten.', NULL, '2021-10-20', '2021-10-31', 2, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `alert_region`
--

CREATE TABLE `alert_region` (
  `alert_id` bigint(20) UNSIGNED NOT NULL,
  `region_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `alert_region`
--

INSERT INTO `alert_region` (`alert_id`, `region_id`) VALUES
(1, 27),
(2, 8),
(2, 10),
(2, 26),
(3, 1),
(4, 27);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `alert_state`
--

CREATE TABLE `alert_state` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shortform` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `longform` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `alert_state`
--

INSERT INTO `alert_state` (`id`, `shortform`, `longform`) VALUES
(1, 'INFO', 'Information'),
(2, 'WARN', 'Warnung'),
(3, 'DANGER', 'Gefahr');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `region`
--

CREATE TABLE `region` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shortform` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `longform` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `region`
--

INSERT INTO `region` (`id`, `shortform`, `longform`) VALUES
(1, 'AG', 'Aargau'),
(2, 'AR', 'Appenzell Ausserrhoden'),
(3, 'AI', 'Appenzell Innerrhoden'),
(4, 'BL', 'Basel-Landschaft'),
(5, 'BS', 'Basel-Stadt'),
(6, 'BE', 'Bern'),
(7, 'FR', 'Fribourg / Freiburg'),
(8, 'GE', 'Genève / Genf'),
(9, 'GL', 'Glarus'),
(10, 'GR', 'Graubünden'),
(11, 'JU', 'Jura'),
(12, 'LU', 'Luzern'),
(13, 'NE', 'Neuchâtel / Neuenburg'),
(14, 'NW', 'Nidwalden'),
(15, 'OW', 'Obwalden'),
(16, 'SG', 'St. Gallen'),
(17, 'SH', 'Schaffhausen'),
(18, 'SZ', 'Schwyz'),
(19, 'SO', 'Solothurn'),
(20, 'TG', 'Thurgau'),
(21, 'TI', 'Ticino / Tessin'),
(22, 'UR', 'Uri'),
(23, 'VS', 'Valais / Wallis'),
(24, 'VD', 'Vaud / Waadt'),
(25, 'ZG', 'Zug'),
(26, 'ZH', 'Zürich'),
(27, 'NA', 'National');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `role`
--

CREATE TABLE `role` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shortform` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `role`
--

INSERT INTO `role` (`id`, `name`, `shortform`) VALUES
(1, 'Administrator', 'ADMIN'),
(2, 'Author', 'AUTHOR'),
(3, 'User', 'USER');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `role_management`
--

CREATE TABLE `role_management` (
  `role_id` bigint(20) UNSIGNED NOT NULL DEFAULT 3,
  `user_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `role_management`
--

INSERT INTO `role_management` (`role_id`, `user_id`) VALUES
(1, 1),
(2, 2),
(3, 1),
(3, 2);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE `user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `firstname` varchar(65) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(65) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mail` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `user`
--

INSERT INTO `user` (`id`, `firstname`, `lastname`, `mail`, `username`, `password`) VALUES
(1, 'Admin', 'Istrator', 'admin.istrator@admin.ch', 'admin', '$2y$10$eva8S1wPLfewOAg4zb84SurT6AmQ6W96WZ5JwFxWfGqgQ5ZgcSeIa'),
(2, 'Au', 'Thor', 'au.thor@admin.ch', 'author', '$2y$10$9aQdr56wgNS7IRPuRGG9NemXFuLEO9qbnFzsRbEs3cyBUfqmyxSF6');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `alert`
--
ALTER TABLE `alert`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creator_fk` (`creator`),
  ADD KEY `state_fk` (`state`);

--
-- Indizes für die Tabelle `alert_region`
--
ALTER TABLE `alert_region`
  ADD PRIMARY KEY (`alert_id`,`region_id`),
  ADD KEY `region_fk` (`region_id`);

--
-- Indizes für die Tabelle `alert_state`
--
ALTER TABLE `alert_state`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `region`
--
ALTER TABLE `region`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shortform` (`shortform`);

--
-- Indizes für die Tabelle `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `role_management`
--
ALTER TABLE `role_management`
  ADD PRIMARY KEY (`role_id`,`user_id`),
  ADD KEY `user_fk` (`user_id`);

--
-- Indizes für die Tabelle `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `alert`
--
ALTER TABLE `alert`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `alert_state`
--
ALTER TABLE `alert_state`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `region`
--
ALTER TABLE `region`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT für Tabelle `role`
--
ALTER TABLE `role`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `user`
--
ALTER TABLE `user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `alert`
--
ALTER TABLE `alert`
  ADD CONSTRAINT `creator_fk` FOREIGN KEY (`creator`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `state_fk` FOREIGN KEY (`state`) REFERENCES `alert_state` (`id`);

--
-- Constraints der Tabelle `alert_region`
--
ALTER TABLE `alert_region`
  ADD CONSTRAINT `alert_fk` FOREIGN KEY (`alert_id`) REFERENCES `alert` (`id`),
  ADD CONSTRAINT `region_fk` FOREIGN KEY (`region_id`) REFERENCES `region` (`id`);

--
-- Constraints der Tabelle `role_management`
--
ALTER TABLE `role_management`
  ADD CONSTRAINT `role_fk` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`),
  ADD CONSTRAINT `user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
