-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 24-04-2026 a las 16:18:45
-- Versión del servidor: 11.8.6-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u313936967_meiler`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actor_user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(150) NOT NULL,
  `entity_type` varchar(150) NOT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `details_json` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `actor_user_id`, `action`, `entity_type`, `entity_id`, `details_json`, `created_at`) VALUES
(1, 1, 'campaign.created', 'campaign', 11, '{\"name\":\"EMO\",\"description\":\"\",\"created_by\":1}', '2026-04-22 19:08:58'),
(2, 1, 'auth.login', 'user', 1, NULL, '2026-04-23 15:10:14'),
(3, 1, 'campaign.activated', 'campaign', 11, NULL, '2026-04-23 15:14:52'),
(4, 1, 'settings.updated', 'settings', NULL, NULL, '2026-04-23 15:26:05'),
(5, 1, 'auth.login', 'user', 1, NULL, '2026-04-23 23:07:09'),
(6, 1, 'settings.updated', 'settings', NULL, NULL, '2026-04-23 23:39:08'),
(7, 1, 'auth.login', 'user', 1, NULL, '2026-04-24 14:44:26'),
(8, 1, 'settings.updated', 'settings', NULL, NULL, '2026-04-24 14:45:18'),
(9, 1, 'settings.updated', 'settings', NULL, NULL, '2026-04-24 14:45:39'),
(10, 1, 'campaign.created', 'campaign', 12, '{\"name\":\"Ya que quede\",\"description\":\"\",\"created_by\":1}', '2026-04-24 15:31:37'),
(11, 1, 'campaign.message.saved', 'campaign', 12, '{\"content_mode\":\"text\"}', '2026-04-24 15:32:15'),
(12, 1, 'campaign.activated', 'campaign', 12, NULL, '2026-04-24 15:39:02'),
(13, 1, 'campaign.message.saved', 'campaign', 12, '{\"content_mode\":\"text\"}', '2026-04-24 15:56:19'),
(14, 1, 'auth.logout', 'user', 1, NULL, '2026-04-24 15:56:57'),
(15, 1, 'auth.login', 'user', 1, NULL, '2026-04-24 15:56:59'),
(16, 1, 'campaign.message.saved', 'campaign', 12, '{\"content_mode\":\"text\"}', '2026-04-24 15:57:12'),
(17, 1, 'campaign.message.saved', 'campaign', 12, '{\"content_mode\":\"text\"}', '2026-04-24 16:09:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `content_mode` enum('text','html') NOT NULL DEFAULT 'text',
  `sequence_start_at` datetime DEFAULT NULL,
  `sequence_interval_days` int(11) NOT NULL DEFAULT 2,
  `sequence_total_steps` int(11) NOT NULL DEFAULT 10,
  `status` enum('draft','active','inactive','processing','paused','completed','failed','cancelled') NOT NULL DEFAULT 'draft',
  `created_by` int(10) UNSIGNED NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `campaigns`
--

INSERT INTO `campaigns` (`id`, `name`, `description`, `content_mode`, `sequence_start_at`, `sequence_interval_days`, `sequence_total_steps`, `status`, `created_by`, `started_at`, `finished_at`, `created_at`, `updated_at`) VALUES
(11, 'EMO', NULL, 'text', NULL, 2, 10, 'active', 1, NULL, NULL, '2026-04-22 19:08:58', '2026-04-23 15:14:52'),
(12, 'Ya que quede', NULL, 'text', NULL, 2, 10, 'active', 1, NULL, NULL, '2026-04-24 15:31:37', '2026-04-24 15:39:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `campaign_deliveries`
--

CREATE TABLE `campaign_deliveries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_id` bigint(20) UNSIGNED NOT NULL,
  `send_number` tinyint(3) UNSIGNED NOT NULL,
  `scheduled_for` datetime NOT NULL,
  `status` enum('pending','sent','failed','skipped','cancelled') NOT NULL DEFAULT 'pending',
  `ses_message_id` varchar(255) DEFAULT NULL,
  `error_message` varchar(500) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `campaign_messages`
--

CREATE TABLE `campaign_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `campaign_id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `from_name` varchar(150) NOT NULL,
  `from_email` varchar(190) NOT NULL,
  `reply_to` varchar(190) DEFAULT NULL,
  `html_body` mediumtext NOT NULL,
  `text_body` mediumtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `campaign_messages`
--

INSERT INTO `campaign_messages` (`id`, `campaign_id`, `subject`, `from_name`, `from_email`, `reply_to`, `html_body`, `text_body`, `created_at`, `updated_at`) VALUES
(4, 12, 'ya que ojala', 'Equipo Umbrales', 'boletin@mailer.umbrales.org', 'yosefrochadl5@gmail.com', '<p>Hola,</p><p>Escribe aquí tu mensaje.</p><p>Saludos,<br>Equipo Umbrales</p>', 'Hola,\r\n\r\nEscribe aquí tu mensaje.\r\n\r\nSaludos,\r\nEquipo Umbrales', '2026-04-24 15:32:15', '2026-04-24 15:32:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `campaign_recipients`
--

CREATE TABLE `campaign_recipients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_id` bigint(20) UNSIGNED NOT NULL,
  `source` enum('manual','csv','segment') NOT NULL DEFAULT 'csv',
  `import_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('active','excluded') NOT NULL DEFAULT 'active',
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `campaign_recipients`
--

INSERT INTO `campaign_recipients` (`id`, `campaign_id`, `recipient_id`, `source`, `import_id`, `status`, `assigned_at`) VALUES
(3, 12, 13, 'csv', 7, 'active', '2026-04-24 15:38:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `campaign_steps`
--

CREATE TABLE `campaign_steps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `step_number` tinyint(3) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `text_body` mediumtext DEFAULT NULL,
  `html_body` mediumtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `campaign_steps`
--

INSERT INTO `campaign_steps` (`id`, `campaign_id`, `step_number`, `subject`, `text_body`, `html_body`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 11, 1, 'Mensaje 1', 'Hola,\n\nEscribe aquí el mensaje 1.\n\nSaludos,\nEquipo Umbrales', '<p>Hola,</p><p>Escribe aquí el mensaje 1.</p><p>Saludos,<br>Equipo Umbrales</p>', 1, '2026-04-22 19:09:01', '2026-04-22 19:09:01'),
(2, 11, 2, 'Mensaje 2', 'Hola,\n\nEscribe aquí el mensaje 2.\n\nSaludos,\nEquipo Umbrales', '<p>Hola,</p><p>Escribe aquí el mensaje 2.</p><p>Saludos,<br>Equipo Umbrales</p>', 1, '2026-04-22 19:09:01', '2026-04-22 19:09:01'),
(3, 11, 3, 'Mensaje 3', 'Hola,\n\nEscribe aquí el mensaje 3.\n\nSaludos,\nEquipo Umbrales', '<p>Hola,</p><p>Escribe aquí el mensaje 3.</p><p>Saludos,<br>Equipo Umbrales</p>', 1, '2026-04-22 19:09:01', '2026-04-22 19:09:01'),
(4, 11, 4, 'Mensaje 4', 'Hola,\n\nEscribe aquí el mensaje 4.\n\nSaludos,\nEquipo Umbrales', '<p>Hola,</p><p>Escribe aquí el mensaje 4.</p><p>Saludos,<br>Equipo Umbrales</p>', 1, '2026-04-22 19:09:01', '2026-04-22 19:09:01'),
(5, 11, 5, 'Mensaje 5', 'Hola,\n\nEscribe aquí el mensaje 5.\n\nSaludos,\nEquipo Umbrales', '<p>Hola,</p><p>Escribe aquí el mensaje 5.</p><p>Saludos,<br>Equipo Umbrales</p>', 1, '2026-04-22 19:09:01', '2026-04-22 19:09:01'),
(6, 11, 6, 'Mensaje 6', 'Hola,\n\nEscribe aquí el mensaje 6.\n\nSaludos,\nEquipo Umbrales', '<p>Hola,</p><p>Escribe aquí el mensaje 6.</p><p>Saludos,<br>Equipo Umbrales</p>', 1, '2026-04-22 19:09:01', '2026-04-22 19:09:01'),
(7, 11, 7, 'Mensaje 7', 'Hola,\n\nEscribe aquí el mensaje 7.\n\nSaludos,\nEquipo Umbrales', '<p>Hola,</p><p>Escribe aquí el mensaje 7.</p><p>Saludos,<br>Equipo Umbrales</p>', 1, '2026-04-22 19:09:01', '2026-04-22 19:09:01'),
(8, 11, 8, 'Mensaje 8', 'Hola,\n\nEscribe aquí el mensaje 8.\n\nSaludos,\nEquipo Umbrales', '<p>Hola,</p><p>Escribe aquí el mensaje 8.</p><p>Saludos,<br>Equipo Umbrales</p>', 1, '2026-04-22 19:09:01', '2026-04-22 19:09:01'),
(9, 11, 9, 'Mensaje 9', 'Hola,\n\nEscribe aquí el mensaje 9.\n\nSaludos,\nEquipo Umbrales', '<p>Hola,</p><p>Escribe aquí el mensaje 9.</p><p>Saludos,<br>Equipo Umbrales</p>', 1, '2026-04-22 19:09:01', '2026-04-22 19:09:01'),
(10, 11, 10, 'Mensaje 10', 'Hola,\n\nEscribe aquí el mensaje 10.\n\nSaludos,\nEquipo Umbrales', '<p>Hola,</p><p>Escribe aquí el mensaje 10.</p><p>Saludos,<br>Equipo Umbrales</p>', 1, '2026-04-22 19:09:01', '2026-04-22 19:09:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `temp_password_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recipients`
--

CREATE TABLE `recipients` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `first_name` varchar(120) DEFAULT NULL,
  `last_name` varchar(120) DEFAULT NULL,
  `institution` varchar(190) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `segment` varchar(120) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `consent_at` datetime DEFAULT NULL,
  `unsubscribed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `recipients`
--

INSERT INTO `recipients` (`id`, `email`, `first_name`, `last_name`, `institution`, `country`, `segment`, `status`, `consent_at`, `unsubscribed_at`, `created_at`, `updated_at`) VALUES
(13, 'yosefrochadl5@gmail.com', 'Yosef', 'Rocha', 'Instituto Umbrales', 'Mexico', 'Docentes', 'active', '0000-00-00 00:00:00', NULL, '2026-04-24 15:38:39', '2026-04-24 15:38:39'),
(14, 'diegoverdesv@gmail.com', 'Diego Antonio', 'S?nchez Verde', 'Umbrales Academy', 'M?xico', 'Administrativo', 'active', '0000-00-00 00:00:00', NULL, '2026-04-24 15:38:40', '2026-04-24 15:38:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recipient_imports`
--

CREATE TABLE `recipient_imports` (
  `id` int(10) UNSIGNED NOT NULL,
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `campaign_id` bigint(20) UNSIGNED DEFAULT NULL,
  `original_filename` varchar(255) NOT NULL,
  `total_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `imported_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `failed_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `recipient_imports`
--

INSERT INTO `recipient_imports` (`id`, `uploaded_by`, `campaign_id`, `original_filename`, `total_rows`, `imported_rows`, `failed_rows`, `created_at`) VALUES
(7, 1, 12, 'plantilla_destinatarios.csv', 2, 2, 0, '2026-04-24 15:38:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `send_sessions`
--

CREATE TABLE `send_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `campaign_id` int(10) UNSIGNED NOT NULL,
  `campaign_message_id` int(10) UNSIGNED NOT NULL,
  `status` enum('queued','processing','paused','completed','failed','cancelled') NOT NULL DEFAULT 'queued',
  `total_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `processed_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `success_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `failed_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `paused_at` datetime DEFAULT NULL,
  `resumed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `send_sessions`
--

INSERT INTO `send_sessions` (`id`, `campaign_id`, `campaign_message_id`, `status`, `total_count`, `processed_count`, `success_count`, `failed_count`, `started_at`, `finished_at`, `paused_at`, `resumed_at`, `created_at`, `updated_at`) VALUES
(1, 12, 4, 'queued', 0, 0, 0, 0, '2026-04-24 15:39:07', NULL, NULL, NULL, '2026-04-24 15:39:07', '2026-04-24 15:39:07'),
(2, 12, 4, 'queued', 0, 0, 0, 0, '2026-04-24 15:40:10', NULL, NULL, NULL, '2026-04-24 15:40:10', '2026-04-24 15:40:10'),
(3, 12, 4, 'queued', 0, 0, 0, 0, '2026-04-24 15:50:44', NULL, NULL, NULL, '2026-04-24 15:50:44', '2026-04-24 15:50:44'),
(4, 12, 4, 'queued', 0, 0, 0, 0, '2026-04-24 15:55:51', NULL, NULL, NULL, '2026-04-24 15:55:51', '2026-04-24 15:55:51'),
(5, 12, 4, 'queued', 0, 0, 0, 0, '2026-04-24 15:56:30', NULL, NULL, NULL, '2026-04-24 15:56:30', '2026-04-24 15:56:30'),
(6, 12, 4, 'queued', 0, 0, 0, 0, '2026-04-24 15:57:20', NULL, NULL, NULL, '2026-04-24 15:57:20', '2026-04-24 15:57:20'),
(7, 12, 4, 'queued', 0, 0, 0, 0, '2026-04-24 16:06:40', NULL, NULL, NULL, '2026-04-24 16:06:40', '2026-04-24 16:06:40'),
(8, 12, 4, 'queued', 0, 0, 0, 0, '2026-04-24 16:07:48', NULL, NULL, NULL, '2026-04-24 16:07:48', '2026-04-24 16:07:48'),
(9, 12, 4, 'queued', 0, 0, 0, 0, '2026-04-24 16:10:00', NULL, NULL, NULL, '2026-04-24 16:10:00', '2026-04-24 16:10:00'),
(10, 12, 4, 'queued', 0, 0, 0, 0, '2026-04-24 16:11:09', NULL, NULL, NULL, '2026-04-24 16:11:09', '2026-04-24 16:11:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `send_session_items`
--

CREATE TABLE `send_session_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `send_session_id` int(10) UNSIGNED NOT NULL,
  `recipient_id` int(10) UNSIGNED NOT NULL,
  `ses_message_id` varchar(190) DEFAULT NULL,
  `status` enum('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
  `error_message` varchar(255) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `send_session_items`
--

INSERT INTO `send_session_items` (`id`, `send_session_id`, `recipient_id`, `ses_message_id`, `status`, `error_message`, `processed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 13, NULL, 'pending', NULL, NULL, '2026-04-24 15:39:07', '2026-04-24 15:39:07'),
(2, 1, 14, NULL, 'pending', NULL, NULL, '2026-04-24 15:39:07', '2026-04-24 15:39:07'),
(3, 2, 13, NULL, 'pending', NULL, NULL, '2026-04-24 15:40:10', '2026-04-24 15:40:10'),
(4, 3, 13, NULL, 'pending', NULL, NULL, '2026-04-24 15:50:44', '2026-04-24 15:50:44'),
(5, 4, 13, NULL, 'pending', NULL, NULL, '2026-04-24 15:55:51', '2026-04-24 15:55:51'),
(6, 5, 13, NULL, 'pending', NULL, NULL, '2026-04-24 15:56:30', '2026-04-24 15:56:30'),
(7, 6, 13, NULL, 'pending', NULL, NULL, '2026-04-24 15:57:20', '2026-04-24 15:57:20'),
(8, 7, 13, NULL, 'pending', NULL, NULL, '2026-04-24 16:06:40', '2026-04-24 16:06:40'),
(9, 8, 13, NULL, 'pending', NULL, NULL, '2026-04-24 16:07:48', '2026-04-24 16:07:48'),
(10, 9, 13, NULL, 'pending', NULL, NULL, '2026-04-24 16:10:00', '2026-04-24 16:10:00'),
(11, 10, 13, NULL, 'pending', NULL, NULL, '2026-04-24 16:11:09', '2026-04-24 16:11:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(150) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `is_encrypted`, `created_at`, `updated_at`) VALUES
(1, 'ses_region', 'us-east-1', 0, '2026-04-23 15:26:05', '2026-04-23 15:26:05'),
(2, 'ses_key', 'Y9heZHEYDseyCJl/N1q6f+W3F0Qw5hnpZLYrIDOceYJLdmyqnugbRNuySi6POTer', 1, '2026-04-23 15:26:05', '2026-04-24 14:45:39'),
(3, 'ses_secret', 'o9Liy+Splgu+aSM8UJNqcatpq4Dv29PsAUQ7Te0emXnTQNPagDCCK98DCPoVj+mAmTvE+yhT9fmrHqZ21WhZvQ==', 1, '2026-04-23 15:26:05', '2026-04-24 14:45:39'),
(4, 'ses_from_email', 'boletin@mailer.umbrales.org', 0, '2026-04-23 15:26:05', '2026-04-24 14:45:18'),
(5, 'ses_from_name', 'Equipo Umbrales', 0, '2026-04-23 15:26:05', '2026-04-23 15:26:05'),
(6, 'ses_reply_to', 'yosefrochadl5@gmail.com', 0, '2026-04-23 15:26:05', '2026-04-24 14:45:39'),
(7, 'ses_configuration_set', NULL, 0, '2026-04-23 15:26:05', '2026-04-23 15:26:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unsubscribe_tokens`
--

CREATE TABLE `unsubscribe_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipient_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(128) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'admin@umbrales.local', '$2y$10$pJ1Yewhxof/uSzmiFEyOVeyAWSU7pcTGrwyHEqhpyHBz/nFUG5d6y', 'admin', 1, '2026-04-24 15:56:59', '2026-04-20 19:27:33', '2026-04-24 15:56:59'),
(2, 'Diego Verde', 'diego.verde@umbrales.local', '$2y$10$qTXqfpTjzjVAYZqXkjPlYO0KFbBPtl14KTbBhSCCb6deDazsqLwxS', 'admin', 1, '2026-04-21 20:50:27', '2026-04-21 20:47:32', '2026-04-21 20:50:27');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_actor` (`actor_user_id`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`);

--
-- Indices de la tabla `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_campaigns_user` (`created_by`);

--
-- Indices de la tabla `campaign_deliveries`
--
ALTER TABLE `campaign_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_campaign_recipient_send` (`campaign_id`,`recipient_id`,`send_number`),
  ADD KEY `idx_status_scheduled` (`status`,`scheduled_for`),
  ADD KEY `idx_campaign_id` (`campaign_id`),
  ADD KEY `idx_recipient_id` (`recipient_id`);

--
-- Indices de la tabla `campaign_messages`
--
ALTER TABLE `campaign_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_campaign_messages_campaign` (`campaign_id`);

--
-- Indices de la tabla `campaign_recipients`
--
ALTER TABLE `campaign_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_campaign_recipient` (`campaign_id`,`recipient_id`),
  ADD KEY `idx_campaign_id` (`campaign_id`),
  ADD KEY `idx_recipient_id` (`recipient_id`);

--
-- Indices de la tabla `campaign_steps`
--
ALTER TABLE `campaign_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_campaign_step` (`campaign_id`,`step_number`),
  ADD KEY `idx_campaign_id` (`campaign_id`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_password_resets_user` (`user_id`);

--
-- Indices de la tabla `recipients`
--
ALTER TABLE `recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `recipient_imports`
--
ALTER TABLE `recipient_imports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recipient_imports_user` (`uploaded_by`);

--
-- Indices de la tabla `send_sessions`
--
ALTER TABLE `send_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_send_sessions_campaign` (`campaign_id`),
  ADD KEY `fk_send_sessions_message` (`campaign_message_id`);

--
-- Indices de la tabla `send_session_items`
--
ALTER TABLE `send_session_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_session_recipient` (`send_session_id`,`recipient_id`),
  ADD KEY `fk_send_items_recipient` (`recipient_id`);

--
-- Indices de la tabla `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indices de la tabla `unsubscribe_tokens`
--
ALTER TABLE `unsubscribe_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_unsubscribe_recipient` (`recipient_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `campaign_deliveries`
--
ALTER TABLE `campaign_deliveries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `campaign_messages`
--
ALTER TABLE `campaign_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `campaign_recipients`
--
ALTER TABLE `campaign_recipients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `campaign_steps`
--
ALTER TABLE `campaign_steps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `recipients`
--
ALTER TABLE `recipients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `recipient_imports`
--
ALTER TABLE `recipient_imports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `send_sessions`
--
ALTER TABLE `send_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `send_session_items`
--
ALTER TABLE `send_session_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `unsubscribe_tokens`
--
ALTER TABLE `unsubscribe_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `campaigns`
--
ALTER TABLE `campaigns`
  ADD CONSTRAINT `fk_campaigns_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `campaign_messages`
--
ALTER TABLE `campaign_messages`
  ADD CONSTRAINT `fk_campaign_messages_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `recipient_imports`
--
ALTER TABLE `recipient_imports`
  ADD CONSTRAINT `fk_recipient_imports_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `send_sessions`
--
ALTER TABLE `send_sessions`
  ADD CONSTRAINT `fk_send_sessions_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`),
  ADD CONSTRAINT `fk_send_sessions_message` FOREIGN KEY (`campaign_message_id`) REFERENCES `campaign_messages` (`id`);

--
-- Filtros para la tabla `send_session_items`
--
ALTER TABLE `send_session_items`
  ADD CONSTRAINT `fk_send_items_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `recipients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_send_items_session` FOREIGN KEY (`send_session_id`) REFERENCES `send_sessions` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `unsubscribe_tokens`
--
ALTER TABLE `unsubscribe_tokens`
  ADD CONSTRAINT `fk_unsubscribe_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `recipients` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
