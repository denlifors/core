-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Фев 06 2026 г., 15:14
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `denlifors`
--

-- --------------------------------------------------------

--
-- Структура таблицы `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'published',
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `articles`
--

INSERT INTO `articles` (`id`, `title`, `slug`, `excerpt`, `short_description`, `content`, `image`, `status`, `view_count`, `created_at`, `updated_at`) VALUES
(1, 'Тестовая статья', '-', 'Описание тестовой статьи', NULL, 'Тестовая статья\r\nТестовая статья\r\nТестовая статья\r\nТестовая статьяТестовая статья\r\nТестовая статья\r\nТестовая статья\r\nТестовая статьямТестовая статья\r\nТестовая статья', 'article_6953cb075b53c.png', 'draft', 12, '2025-12-25 20:58:12', '2026-01-09 10:29:20'),
(3, 'фыsdfsdf', 'fsdedfgg', 'sdfsdfds', NULL, 'фыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыафыа', 'article_695c18ff04cc5.jpg', 'published', 2, '2026-01-05 20:03:15', '2026-01-08 09:59:46'),
(4, 'пвапавпк', '--1', NULL, 'вапвапавпва', NULL, 'article_695f8004c0fb5.jpg', 'published', 9, '2026-01-08 09:49:24', '2026-01-08 10:01:51');

-- --------------------------------------------------------

--
-- Структура таблицы `article_blocks`
--

CREATE TABLE `article_blocks` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `template_type` varchar(50) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `article_blocks`
--

INSERT INTO `article_blocks` (`id`, `article_id`, `template_type`, `sort_order`, `content`, `created_at`, `updated_at`) VALUES
(28, 4, 'title_text_image_right', 0, '{\"title\":\"\\u044b\\u0432\\u0430\\u043f\\u044b\\u0432\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\",\"text\":\"\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\",\"image\":\"article_695f7d788acf8.jpg\"}', '2026-01-08 10:01:33', '2026-01-08 10:01:33'),
(29, 4, 'text_only', 1, '{\"text\":\"\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\"}', '2026-01-08 10:01:33', '2026-01-08 10:01:33'),
(30, 4, 'title_text_button', 2, '{\"title\":\"\\u0432\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0430\\u0432\\u043f\\u0430\\u0432 \\u043f\\u044f\\u0432\\u0430\\u043f \\u0432\\u044f\\u0430 \\u043f\\u0432\\u044f\\u0430\\u043f \\u044f\\u0432\\u043f \\u043a \\u043f\\u0432\\u044f \\u043f\\u043a\\u0432\\u044f \\u043f\",\"text\":\"\\u044b\\u0432\\u0430\\u044b\\u0432\\u0430\\u0432\\u044b\\u0430\\u044b\\u0432\\u0430\\u0432\\u044b\\u0430\\u0432\",\"button_text\":\"\\u041f\\u0435\\u0440\\u0435\\u0439\\u0442\\u0438 \\u0432 \\u043a\\u0430\\u0442\\u0430\\u043b\\u043e\\u0433\",\"button_link\":\"http:\\/\\/localhost\\/DenLiFors\\/catalog.php\"}', '2026-01-08 10:01:33', '2026-01-08 10:01:33');

-- --------------------------------------------------------

--
-- Структура таблицы `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `page` varchar(50) DEFAULT 'home',
  `description` text DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `gradient_color1` varchar(7) DEFAULT '#667eea',
  `gradient_color2` varchar(7) DEFAULT '#764ba2',
  `gradient_angle` int(11) DEFAULT 135,
  `type` varchar(20) DEFAULT 'detailed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `banners`
--

INSERT INTO `banners` (`id`, `title`, `image`, `link`, `position`, `sort_order`, `status`, `created_at`, `page`, `description`, `subtitle`, `gradient_color1`, `gradient_color2`, `gradient_angle`, `type`) VALUES
(6, 'Первый баннер', 'banner_6953b30e861c99.96284128.png', '', 'hero', 1, 'active', '2025-12-25 21:01:04', 'home', '', 'Описание под баннером', '#b12f88', '#af4912', 32, 'detailed'),
(7, 'Баннер 2', 'banner_6953cb6bdea3b1.61019987.png', '', NULL, 2, 'active', '2025-12-30 12:54:43', 'home', '', 'Тестовый Баннер 2', '#31dd73', '#4f0303', 135, 'detailed');

-- --------------------------------------------------------

--
-- Структура таблицы `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `session_id`, `product_id`, `quantity`, `created_at`) VALUES
(3, NULL, 'r07aot9nt7fdb1861i4vm834d6', 7, 2, '2026-01-05 19:21:58'),
(5, 1, NULL, 7, 1, '2026-02-05 13:24:48'),
(6, 3, NULL, 7, 1, '2026-02-06 13:44:16');

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `sort_order`, `created_at`) VALUES
(1, 'Витамины и минералы', 'vitamins', 'Витаминные комплексы и минеральные добавки', NULL, NULL, 0, '2025-12-25 15:06:34'),
(2, 'Для иммунитета', 'immunity', 'Средства для укрепления иммунной системы', NULL, NULL, 0, '2025-12-25 15:06:34'),
(3, 'Для пищеварения', 'digestion', 'Продукты для улучшения пищеварения', NULL, NULL, 0, '2025-12-25 15:06:34'),
(4, 'Для энергии', 'energy', 'Продукты для повышения энергии и тонуса', NULL, NULL, 0, '2025-12-25 15:06:34'),
(5, 'Для красоты', 'beauty', 'Добавки для красоты кожи, волос и ногтей', NULL, NULL, 0, '2025-12-25 15:06:34'),
(6, 'Для суставов', 'joints', 'Средства для здоровья суставов', NULL, NULL, 0, '2025-12-25 15:06:34');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `total` decimal(10,2) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_sku` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `full_description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT 0,
  `weight` decimal(8,2) DEFAULT NULL,
  `volume` varchar(50) DEFAULT NULL,
  `composition` text DEFAULT NULL,
  `usage_method` text DEFAULT NULL,
  `contraindications` text DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `release_form` text DEFAULT NULL,
  `active_substances` text DEFAULT NULL,
  `duration` text DEFAULT NULL,
  `nutritional_value` text DEFAULT NULL,
  `storage_conditions` text DEFAULT NULL,
  `shelf_life` varchar(255) DEFAULT NULL,
  `manufacturer` text DEFAULT NULL,
  `packaging` text DEFAULT NULL,
  `documentation` text DEFAULT NULL,
  `documentation_file` varchar(255) DEFAULT NULL,
  `what_is_it` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `sku`, `description`, `full_description`, `price`, `old_price`, `stock`, `category_id`, `image`, `images`, `status`, `is_featured`, `weight`, `volume`, `composition`, `usage_method`, `contraindications`, `view_count`, `created_at`, `updated_at`, `release_form`, `active_substances`, `duration`, `nutritional_value`, `storage_conditions`, `shelf_life`, `manufacturer`, `packaging`, `documentation`, `documentation_file`, `what_is_it`) VALUES
(1, 'Витаминный комплекс \"Энергия\"', 'vitamin-energy', 'DL-001', 'Комплекс витаминов для повышения энергии и общего тонуса организма', NULL, 1290.00, 1590.00, 50, 1, NULL, NULL, 'active', 1, NULL, NULL, 'Витамины группы B, витамин C, магний, цинк', 'По 1 капсуле в день во время еды', NULL, 3, '2025-12-25 15:06:34', '2025-12-25 19:52:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Иммуно-форт', 'immuno-fort', 'DL-002', 'Укрепление иммунной системы и защита от вирусов', NULL, 1890.00, NULL, 30, 2, NULL, NULL, 'active', 1, NULL, NULL, 'Эхинацея, витамин C, цинк, прополис', 'По 2 капсулы в день', NULL, 0, '2025-12-25 15:06:34', '2025-12-25 15:06:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Детокс-комплекс', 'detox-complex', 'DL-003', 'Очищение организма и улучшение пищеварения', NULL, 1490.00, 1790.00, 25, 3, NULL, NULL, 'active', 1, NULL, NULL, 'Расторопша, артишок, клетчатка', 'По 1 капсуле утром и вечером', NULL, 0, '2025-12-25 15:06:34', '2025-12-25 15:06:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Энергия-плюс', 'energy-plus', 'DL-004', 'Повышение энергии и работоспособности', NULL, 2190.00, NULL, 40, 4, NULL, NULL, 'active', 1, NULL, NULL, 'Коэнзим Q10, женьшень, витамины группы B', 'По 1 капсуле утром', NULL, 0, '2025-12-25 15:06:34', '2025-12-25 15:06:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Красота и молодость', 'beauty-youth', 'DL-005', 'Комплекс для здоровья кожи, волос и ногтей', NULL, 2490.00, 2890.00, 35, 5, NULL, NULL, 'active', 1, NULL, NULL, 'Коллаген, гиалуроновая кислота, биотин', 'По 2 капсулы в день', NULL, 1, '2025-12-25 15:06:34', '2025-12-30 11:11:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Суставы-про', 'joints-pro', 'DL-006', 'Поддержка здоровья суставов и хрящей', NULL, 1690.00, NULL, 45, 6, NULL, NULL, 'active', 1, NULL, NULL, 'Глюкозамин, хондроитин, MSM', 'По 3 капсулы в день', NULL, 0, '2025-12-25 15:06:34', '2025-12-25 15:06:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Тестовый товар', '-', 'dl-482950389', 'Тестовый товар', 'ываыва ываываываываываываываываыва ыв аыв аыв\r\nываываыва\r\nываываыв', 99.00, 1000.00, 20, 1, 'img_694da644774072.92385967.png', '[]', 'active', 1, 20.00, '1', 'фывыф', 'Тестовый товар', 'фывфы', 58, '2025-12-25 21:02:29', '2026-02-06 13:42:06', 'Тестовый товар', 'Тестовый товар', 'Тестовый товар', 'Тестовый товар', 'Тестовый товар', '', 'Тестовый товар', 'Тестовый товар', '[{\"name\":\"doc_6953b7e6487919.02211742.docx\",\"file\":\"doc_6953b7e6487919.02211742.docx\"}]', '', '{\"description\":\"\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\",\"consists_of\":[\"\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\",\"\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\"],\"release_form\":[\"\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\",\"\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\"],\"how_to_take\":\"\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\",\"recommendation\":\"\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\",\"nutritional_value\":[\"\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\",\"\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\"],\"duration\":\"\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\",\"contraindications\":\"\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\",\"precautions\":\"\\u0432\\u044b\\u0430\\u044b\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u0432\\u0430\\u043f\\u044f\\u0432\\u0430\\u043f\",\"advantages\":[]}'),
(10, 'Тестовый товар222', '-222', '234234423', '', '', 0.00, NULL, 0, NULL, '', '[]', 'inactive', 0, NULL, '', '', '', '', 0, '2026-01-07 10:38:28', '2026-01-07 10:47:42', '', '', '', '', '', '', '', '', '[{\"name\":\"\\u0422\\u0435\\u0441\\u0442\\u043e\\u0432\\u044b\\u0439 \\u0434\\u043e\\u043a\\u0443\\u043c\\u0435\\u043d\\u0442\",\"description\":\"\\u0421\\u043a\\u0430\\u0447\\u0430\\u0439\\u0442\\u0435 \\u0422\\u0435\\u0441\\u0442\\u043e\\u0432\\u044b\\u0439 \\u0434\\u043e\\u043a\\u0443\\u043c\\u0435\\u043d\\u0442 1\",\"file\":\"doc_695e378b680ed5.41033886.docx\"}]', '', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `product_attributes`
--

CREATE TABLE `product_attributes` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` enum('text','number','select') DEFAULT 'text'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `product_attribute_values`
--

CREATE TABLE `product_attribute_values` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('user','partner') DEFAULT 'user',
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `birth_date` date DEFAULT NULL,
  `consultant_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `role`, `is_admin`, `created_at`, `updated_at`, `birth_date`, `consultant_id`) VALUES
(1, 'admin@denlifors.ru', '$2y$10$tg9furO/ShYA6tuY56KnjuNZnIuO7MAHj5yJEpJLpZE9HvVyMVpZ.', 'Admin', 'Admin', NULL, 'user', 1, '2025-12-25 15:06:34', '2026-02-06 14:12:11', NULL, NULL),
(2, 'admin@example.com', '$2y$10$K07X58TTHN91CghSLoBh/.6We9eR6HSaji/vmE9seTqQB7v0e7KfG', 'ываываыа', 'Петров', '87476740792', 'user', 0, '2026-01-05 19:09:35', '2026-01-05 19:09:35', NULL, NULL),
(3, 'ckpydg@gmail.com', '$2y$10$GHVIYJcYYwdp2t3gM2Y7z.hW6QoQoxn8LQb4FqvcNHtwMDCLMnvum', 'Игорь', 'Петров', '+8 (747) 674-07-92', 'partner', 0, '2026-02-06 13:43:54', '2026-02-06 13:44:54', NULL, NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Индексы таблицы `article_blocks`
--
ALTER TABLE `article_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_article` (`article_id`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Индексы таблицы `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_session` (`session_id`);

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_number` (`order_number`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_slug` (`slug`);

--
-- Индексы таблицы `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Индексы таблицы `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_attribute` (`attribute_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `article_blocks`
--
ALTER TABLE `article_blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT для таблицы `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `article_blocks`
--
ALTER TABLE `article_blocks`
  ADD CONSTRAINT `fk_article_blocks_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Ограничения внешнего ключа таблицы `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD CONSTRAINT `product_attribute_values_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_attribute_values_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
