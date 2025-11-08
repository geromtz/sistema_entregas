-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-11-2025 a las 01:20:40
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_entregas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_info`
--

CREATE TABLE `company_info` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `company_info`
--

INSERT INTO `company_info` (`id`, `name`, `address`, `city`, `state`, `country`, `phone`, `email`, `website`, `tax_id`, `logo_url`, `created_at`, `updated_at`) VALUES
(1, 'ERA COMPUTACIONAL', 'VISTA ROSA 219', 'GUADALUPE', 'NUEVO LEON', 'MEXICO', '8188826848', 'contacto@eracomputacional.com.mx', 'www.eracomputacional.com.mx', 'ECO100211SZ1', NULL, '2025-09-26 17:45:27', '2025-09-26 17:53:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `business_name` varchar(50) NOT NULL,
  `company_name` varchar(50) NOT NULL,
  `created_by` int(10) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `customers`
--

INSERT INTO `customers` (`id`, `business_name`, `company_name`, `created_by`, `created_at`) VALUES
(1, 'PIASA', 'PROCESADORA INDUSTRIAL DE ACERO', 2, '2025-10-20 11:02:36'),
(2, 'MADISA', 'MAQUINAS DIESEL', 2, '2025-10-20 11:02:36'),
(3, 'LUMINA', 'LUMINA SOFTWARE', 2, '2025-10-20 11:02:36'),
(4, 'VINICOLA', 'VINICOLA SAN LORENZO', 3, '2025-10-20 11:02:36'),
(5, 'HAMMOND', 'HAMMOND POWER SOLUTION', 4, '2025-10-20 11:02:36'),
(6, 'DESARROLLO OPTIMA', '', 2, '2025-10-20 11:02:36'),
(7, 'NATURAL BAGS', '', 2, '2025-10-20 11:02:36'),
(8, 'QUIMICOS Y PAPELES', '', 2, '2025-10-20 11:02:36'),
(9, 'INDALUM', '', 2, '2025-10-20 11:02:36'),
(28, 'NIPPON SEIKI DE MEXICO', 'NIPPON', 4, '2025-10-21 11:02:17'),
(30, 'BONETERA', '', 2, '2025-10-21 12:11:35'),
(31, 'TRADELOSSA', '', 2, '2025-10-21 16:17:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `customers_address`
--

CREATE TABLE `customers_address` (
  `id` int(11) NOT NULL,
  `id_customer` int(10) NOT NULL,
  `google_maps` varchar(2083) NOT NULL,
  `street` varchar(255) DEFAULT NULL,
  `exterior_number` varchar(50) DEFAULT NULL,
  `interior_number` varchar(50) DEFAULT NULL,
  `colonia` varchar(150) DEFAULT NULL,
  `municipio` varchar(150) DEFAULT NULL,
  `ciudad` varchar(150) DEFAULT NULL,
  `estado` varchar(100) DEFAULT NULL,
  `pais` varchar(100) DEFAULT 'México',
  `codigo_postal` varchar(10) DEFAULT NULL,
  `referencias` text DEFAULT NULL,
  `branch` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `customers_address`
--

INSERT INTO `customers_address` (`id`, `id_customer`, `google_maps`, `street`, `exterior_number`, `interior_number`, `colonia`, `municipio`, `ciudad`, `estado`, `pais`, `codigo_postal`, `referencias`, `branch`) VALUES
(1, 28, 'https://maps.app.goo.gl/drbGCUcmVc3LTqzAA', 'Av. Finsa', '1501', NULL, 'Parque Industrial FINSA', 'Guadalupe', 'Guadalupe', 'Nuevo León', 'México', '67132', NULL, 'PLANTA 2'),
(2, 5, 'https://maps.app.goo.gl/BLZtZq2VyfG7k5RX9', 'Avante', '800', NULL, 'Industrial Park Avante', 'Guadalupe', 'Guadalupe', 'Nuevo León', 'México', '67190', NULL, 'PLANTA 1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `customer_delivery_comments`
--

CREATE TABLE `customer_delivery_comments` (
  `id` int(11) NOT NULL,
  `id_customer_order` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `customer_orders`
--

CREATE TABLE `customer_orders` (
  `id` int(10) NOT NULL,
  `id_system_order` int(10) NOT NULL,
  `order_seller_series` varchar(10) NOT NULL,
  `order_seller_number` int(10) NOT NULL,
  `id_customer` int(11) DEFAULT NULL,
  `purchase_order` varchar(255) DEFAULT NULL,
  `order_pdf_path` varchar(255) NOT NULL,
  `order_pdf_original` varchar(255) DEFAULT NULL,
  `order_pdf_uploaded_at` datetime DEFAULT NULL,
  `name_order` varchar(100) NOT NULL,
  `comments` text DEFAULT NULL,
  `attn` varchar(255) DEFAULT NULL,
  `id_status` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `customer_order_date` datetime NOT NULL,
  `co_last_update_date` datetime NOT NULL DEFAULT current_timestamp(),
  `id_uso_cfdi` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `customer_orders`
--

INSERT INTO `customer_orders` (`id`, `id_system_order`, `order_seller_series`, `order_seller_number`, `id_customer`, `purchase_order`, `order_pdf_path`, `order_pdf_original`, `order_pdf_uploaded_at`, `name_order`, `comments`, `attn`, `id_status`, `created_by`, `customer_order_date`, `co_last_update_date`, `id_uso_cfdi`) VALUES
(170, 388, 'VEN-001', 1, 6, '12345', '', NULL, NULL, 'PROYECTO 1', 'ENTREGAR ANTES DE LA 1', 'AMAIRANI', 1, 2, '2025-10-23 17:15:17', '2025-10-24 18:58:32', 3),
(171, 389, 'VEN-002', 1, 4, 'OC12345', '', NULL, NULL, 'MOUSE DELL', 'ANTES DE LAS 12', 'HECTOR', 1, 3, '2025-10-24 10:51:10', '2025-10-24 18:32:32', 3),
(172, 390, 'VEN-003', 1, 5, 'HAMMOND-1', '', NULL, NULL, 'CABLES DE RED', 'ENTREGAR ANTES DE LAS 5', 'RAUL VAZQUEZ', 1, 4, '2025-10-24 10:52:58', '2025-10-24 18:32:32', 3),
(173, 392, 'VEN-001', 2, 1, 'PV1234', 'storage/uploads/orders/user_2/2025/10/30/order_392/PO_392.pdf', 'MY895534.pdf', '2025-10-29 18:59:34', 'Discos para Jorge', '123', 'Jorge Perez', 1, 2, '2025-10-24 19:50:38', '2025-10-24 19:52:40', 3),
(174, 393, 'VEN-001', 3, 6, 'RAELI-123', '', NULL, NULL, 'MEMORIAS', 'ANTES DE LAS 12 EN METROPOLITAN', 'HECTOR', 1, 2, '2025-10-28 18:10:22', '2025-10-28 18:12:47', 3),
(193, 396, 'VEN-001', 4, 30, 'BON-10-11-12', 'storage/uploads/orders/user_2/2025/10/30/order_396/PO_396.pdf', 'FacturaAUSD_22581.pdf', '2025-10-29 18:59:06', 'BONETERA 1', 'ANTES DE LAS 12', 'HECTOR', 1, 2, '2025-10-29 17:30:45', '2025-10-29 18:43:24', 7),
(194, 397, 'VEN-001', 5, 3, 'LUM-1234', 'storage/uploads/orders/user_2/2025/10/30/order_397/PO_397.pdf', 'camaras.pdf', '2025-10-29 19:04:52', 'LUMINA DISCOS', 'ANTES DE LAS 12', 'HECTOR', 1, 2, '2025-10-29 19:01:10', '2025-10-29 19:07:10', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name_department` varchar(50) NOT NULL,
  `series` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `departments`
--

INSERT INTO `departments` (`id`, `name_department`, `series`) VALUES
(1, 'Dirección', 'DIR'),
(2, 'Recursos Humanos', 'RH'),
(3, 'Finanzas', 'FIN'),
(4, 'Administración', 'ADM'),
(5, 'Ventas', 'VEN'),
(6, 'Marketing', 'MAR'),
(7, 'Operaciones', 'OPE'),
(8, 'Producción', 'PRO'),
(9, 'Logística', 'LOG'),
(10, 'Servicio al Cliente', 'SC'),
(11, 'Tecnología de la Información', 'TI'),
(12, 'Investigación y Desarrollo', 'ID'),
(13, 'Legal', 'LEG'),
(14, 'Calidad', 'CAL'),
(15, 'Compras', 'COM');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `department_menus`
--

CREATE TABLE `department_menus` (
  `id_department` int(11) NOT NULL,
  `id_menu` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `department_menus`
--

INSERT INTO `department_menus` (`id_department`, `id_menu`) VALUES
(1, 10),
(1, 11),
(1, 12),
(4, 1006),
(4, 1007),
(4, 1008),
(4, 1010),
(5, 1),
(5, 2),
(5, 3),
(9, 13),
(9, 14),
(11, 7),
(11, 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `department_views`
--

CREATE TABLE `department_views` (
  `id_department` int(11) NOT NULL,
  `id_view` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `items_supplier_order`
--

CREATE TABLE `items_supplier_order` (
  `id` int(10) NOT NULL,
  `supplier_item_date` datetime NOT NULL,
  `id_status` int(11) DEFAULT 1,
  `id_supplier_order` int(10) NOT NULL,
  `quantity` int(10) NOT NULL,
  `item_number` int(11) NOT NULL,
  `description_product` varchar(200) NOT NULL,
  `created_by` int(10) NOT NULL,
  `items_supplier_order_date` datetime NOT NULL,
  `items_supplier_order_last_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `items_supplier_order`
--

INSERT INTO `items_supplier_order` (`id`, `supplier_item_date`, `id_status`, `id_supplier_order`, `quantity`, `item_number`, `description_product`, `created_by`, `items_supplier_order_date`, `items_supplier_order_last_update`) VALUES
(41, '2025-10-23 17:16:25', 4, 60, 5, 1, 'LAPTOP NEGRA 16GB', 2, '2025-10-23 17:16:25', '2025-10-24 18:32:32'),
(42, '2025-10-23 17:18:06', 4, 61, 5, 1, 'CAJA DE PAPEL', 2, '2025-10-23 17:18:06', '2025-10-24 18:32:32'),
(43, '2025-10-23 17:19:23', 4, 62, 1, 1, 'CARGADOR DE TABLET', 2, '2025-10-23 17:19:23', '2025-10-24 18:32:32'),
(44, '2025-10-23 17:19:23', 4, 62, 5, 2, 'TABLET SAMSUNG', 2, '2025-10-23 17:19:23', '2025-10-24 18:32:32'),
(45, '2025-10-23 17:20:58', 1, 63, 1, 1, 'BOBINA DE CABLE', 2, '2025-10-23 17:20:58', '2025-10-23 17:20:58'),
(46, '2025-10-23 17:20:58', 1, 63, 5, 2, 'CABLES APPLE', 2, '2025-10-23 17:20:58', '2025-10-23 17:20:58'),
(47, '2025-10-23 17:21:51', 1, 64, 100, 1, 'CAJA DE ROLLOS DE PAPEL 100 PIEZAS', 2, '2025-10-23 17:21:51', '2025-10-24 18:34:09'),
(48, '2025-10-23 17:27:16', 1, 65, 1, 1, '1 MOUSE AZUL', 2, '2025-10-23 17:27:16', '2025-10-23 17:27:16'),
(49, '2025-10-24 10:51:36', 1, 66, 1, 1, 'LAPTOP DELL AZUL', 3, '2025-10-24 10:51:36', '2025-10-24 10:52:33'),
(50, '2025-10-24 10:54:24', 1, 67, 1, 1, 'BOBINA DE CABLE', 4, '2025-10-24 10:54:24', '2025-10-24 10:54:24'),
(51, '2025-10-24 10:54:24', 1, 67, 5, 2, 'BOTES PLUG RJ45', 4, '2025-10-24 10:54:24', '2025-10-24 10:54:24'),
(52, '2025-10-24 10:54:24', 1, 67, 10, 3, 'PONCHADORAS', 4, '2025-10-24 10:54:24', '2025-10-24 10:54:24'),
(53, '2025-10-24 10:54:59', 1, 68, 1, 1, 'DELL FORANEA', 4, '2025-10-24 10:54:59', '2025-10-24 11:52:30'),
(54, '2025-10-24 11:37:40', 1, 69, 1, 1, 'CAMARA IP', 4, '2025-10-24 11:37:40', '2025-10-24 11:38:12'),
(55, '2025-10-24 11:37:40', 1, 69, 5, 2, 'DVR', 4, '2025-10-24 11:37:40', '2025-10-24 11:38:12'),
(56, '2025-10-24 11:38:53', 1, 70, 1, 1, 'DELL ', 4, '2025-10-24 11:38:53', '2025-10-24 11:38:53'),
(57, '2025-10-24 11:38:53', 1, 70, 1, 2, 'LAPTOP HP', 4, '2025-10-24 11:38:53', '2025-10-24 11:38:53'),
(58, '2025-10-24 11:39:22', 1, 71, 1, 1, 'CAJA ROLLO PAPEL TERMICO', 4, '2025-10-24 11:39:22', '2025-10-24 11:39:22'),
(59, '2025-10-24 16:37:03', 1, 72, 50, 1, 'LAPTOP NOTEBOOK NEGRAS CON BLANCO', 2, '2025-10-24 16:37:03', '2025-10-24 17:03:56'),
(60, '2025-10-24 16:40:07', 1, 73, 1, 1, 'BATERIA PARA LAPTOP HP', 2, '2025-10-24 16:40:07', '2025-10-24 18:58:32'),
(61, '2025-10-24 16:47:56', 4, 74, 1, 1, 'DISCO DURO', 2, '2025-10-24 16:47:56', '2025-10-24 18:32:32'),
(62, '2025-10-24 19:52:10', 1, 75, 2, 1, 'Monitores', 2, '2025-10-24 19:52:10', '2025-10-24 19:52:40'),
(63, '2025-10-28 18:12:47', 1, 76, 1, 1, 'TABLET AZUL', 2, '2025-10-28 18:12:47', '2025-10-28 18:12:47'),
(64, '2025-10-28 18:12:47', 1, 76, 2, 2, 'RELOJ CHECADOR', 2, '2025-10-28 18:12:47', '2025-10-28 18:12:47'),
(65, '2025-10-29 17:50:17', 1, 77, 1, 1, 'MOUSE AZUL CON ROJO', 2, '2025-10-29 17:50:17', '2025-10-29 17:50:17'),
(66, '2025-10-29 19:07:10', 1, 78, 1, 1, 'CELULAR 6GB', 2, '2025-10-29 19:07:10', '2025-10-30 10:44:05'),
(67, '2025-10-29 19:07:10', 1, 78, 5, 2, 'MOCHILA', 2, '2025-10-29 19:07:10', '2025-10-29 19:07:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logistic_assignments`
--

CREATE TABLE `logistic_assignments` (
  `id` int(11) NOT NULL,
  `id_tracking` int(11) NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `type` enum('pickup','delivery') NOT NULL DEFAULT 'delivery',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `scheduled_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `view_name` varchar(100) DEFAULT NULL,
  `type` enum('menu','dropdown') NOT NULL DEFAULT 'menu',
  `order_num` int(11) NOT NULL DEFAULT 1,
  `is_global` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `menus`
--

INSERT INTO `menus` (`id`, `name`, `parent_id`, `view_name`, `type`, `order_num`, `is_global`) VALUES
(1, 'Dashboard', NULL, 'dashboard', 'menu', 1, 1),
(2, 'Mis Pedidos', NULL, 'my_system_orders', 'menu', 2, 0),
(3, 'Pedidos Mayoristas', NULL, 'wholesale_orders', 'menu', 3, 0),
(7, 'Mis Tickets', NULL, 'my_tickets', 'menu', 2, 0),
(8, 'Gestión', NULL, 'management', 'menu', 3, 0),
(10, 'Mis Pedidos', NULL, 'my_system_orders', 'menu', 1, 0),
(11, 'Vendedores', NULL, 'sellers', 'menu', 2, 0),
(12, 'Reportes', NULL, 'reports', 'menu', 3, 0),
(13, 'Recolecciones', NULL, 'my_pickups', 'menu', 1, 0),
(14, 'Entregas', NULL, 'my_deliverys', 'menu', 2, 0),
(1000, 'Mi Perfil', NULL, '#', 'dropdown', 1, 1),
(1001, 'Soporte', 1000, 'support_tickets', 'menu', 2, 1),
(1002, 'Notificaciones', 1000, 'notifications', 'menu', 3, 1),
(1003, 'Configuración', 1000, 'settings', 'menu', 4, 1),
(1004, 'Salir', 1000, 'logout', 'menu', 5, 1),
(1005, 'Mis Datos', 1000, 'my_info', 'dropdown', 1, 1),
(1006, 'Logistica', NULL, '#', 'dropdown', 1, 0),
(1007, 'Recolecciones', 1006, 'logistic_pickups', 'menu', 2, 0),
(1008, 'Entregas', 1006, 'delivery', 'menu', 3, 0),
(1009, 'Actividades adicionales', NULL, 'other_trips', 'menu', 4, 1),
(1010, 'Centro Logistico', 1006, 'main_logistic', 'menu', 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `other_trips`
--

CREATE TABLE `other_trips` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `driver_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `other_trips`
--

INSERT INTO `other_trips` (`id`, `id_user`, `title`, `description`, `driver_comment`, `created_at`, `updated_at`) VALUES
(7, 9, 'IR AL CONTADOR', 'PASAR CON RUBEN', NULL, '2025-10-27 14:31:18', '2025-10-27 14:31:18'),
(8, 9, 'IR POR CAFE', 'TRAER 2 KILOS DE CAFE', NULL, '2025-10-27 14:31:34', '2025-10-27 15:33:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `name_position` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `dashboard_view` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `positions`
--

INSERT INTO `positions` (`id`, `name_position`, `department_id`, `dashboard_view`) VALUES
(1, 'Director General', 1, 'director_dashboard.php'),
(2, 'Analista de RRHH', 2, 'rrhh_analyst_dashboard.php'),
(3, 'Reclutador', 2, 'recruiter_dashboard.php'),
(4, 'Tesorero', 3, 'treasury_dashboard.php'),
(5, 'Ejecutivo de Ventas', 5, 'sales_dashboard.php'),
(6, 'Mensajero', 9, 'messenger_dashboard.php'),
(7, 'Soporte Técnico', 11, 'support_dashboard.php'),
(8, 'Asistente de ventas', 5, 'assistant_sales_dashboard.php'),
(9, 'Facturación', 4, 'billing_dashboard.php'),
(10, 'Diseño Gráfico', 6, 'design_dashboard.php');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `status_customer_order`
--

CREATE TABLE `status_customer_order` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `status_customer_order`
--

INSERT INTO `status_customer_order` (`id`, `name`) VALUES
(1, 'En proceso'),
(2, 'Parcial'),
(3, 'Completado'),
(4, 'Cancelado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `status_supplier_item`
--

CREATE TABLE `status_supplier_item` (
  `id` int(11) NOT NULL,
  `status_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `status_supplier_item`
--

INSERT INTO `status_supplier_item` (`id`, `status_name`) VALUES
(1, 'pendiente'),
(2, 'parcial'),
(3, 'completo'),
(4, 'cancelado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `status_supplier_order`
--

CREATE TABLE `status_supplier_order` (
  `id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `status_supplier_order`
--

INSERT INTO `status_supplier_order` (`id`, `status_name`, `description`) VALUES
(1, 'pendiente', 'Orden creada pero aún no procesada'),
(2, 'en proceso', 'El pedido esta por recolectarse o enviarse por el mayorista'),
(3, 'parcial', 'Solo parte de los productos fueron recibidos'),
(4, 'completo', 'El pedido fue recibido en su totalidad'),
(5, 'cancelado', 'El pedido fue cancelado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(10) NOT NULL,
  `company_name` varchar(50) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `business_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `suppliers`
--

INSERT INTO `suppliers` (`id`, `company_name`, `created_by`, `created_at`, `business_name`) VALUES
(1, 'GRUPO UNIDADES DE COMPUTO', 2, '2025-10-21 12:14:15', 'UNICOM'),
(2, 'EXEL DEL NORTE', 2, '2025-10-21 12:14:15', 'EXEL'),
(3, 'SISTEMAS Y SERVICIOS DE COMUNICACION', 2, '2025-10-21 12:14:15', 'SYSCOM'),
(4, '', 2, '2025-10-21 12:14:23', 'REDLEAF'),
(5, '', 2, '2025-10-21 18:07:17', 'INGRAM'),
(6, '', 2, '2025-10-22 10:24:07', 'AMERICAN STOCK'),
(7, '', 2, '2025-10-22 11:16:08', 'NEXSYS'),
(8, '', 2, '2025-10-23 13:26:13', 'CT INTERNACIONAL');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `supplier_adress`
--

CREATE TABLE `supplier_adress` (
  `id` int(11) NOT NULL,
  `id_supplier` int(10) NOT NULL,
  `branch` varchar(50) NOT NULL,
  `google_maps` varchar(2083) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `supplier_adress`
--

INSERT INTO `supplier_adress` (`id`, `id_supplier`, `branch`, `google_maps`) VALUES
(1, 1, 'No aplica', '#'),
(2, 3, 'SAN NICOLAS', 'https://maps.app.goo.gl/aUv84XLkV7n8e4RV8'),
(3, 3, 'CENTRO / MITRAS', ''),
(4, 1, 'MONTERREY', ''),
(5, 2, 'MONTERREY', ''),
(6, 6, 'MONTERREY', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `supplier_orders`
--

CREATE TABLE `supplier_orders` (
  `id` int(10) NOT NULL,
  `id_customer_order` int(10) NOT NULL,
  `id_status` int(10) NOT NULL,
  `id_supplier` int(10) NOT NULL,
  `id_branch` int(10) NOT NULL,
  `item_number` int(10) NOT NULL,
  `supplier_order` varchar(50) NOT NULL,
  `id_order_type` int(10) NOT NULL,
  `created_by` int(10) NOT NULL,
  `supplier_order_date` datetime DEFAULT NULL,
  `supplier_order_last_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `supplier_orders`
--

INSERT INTO `supplier_orders` (`id`, `id_customer_order`, `id_status`, `id_supplier`, `id_branch`, `item_number`, `supplier_order`, `id_order_type`, `created_by`, `supplier_order_date`, `supplier_order_last_update`) VALUES
(60, 170, 5, 1, 1, 1, 'EDITABLE-1', 1, 2, '2025-10-23 17:16:25', '2025-10-24 18:19:57'),
(61, 170, 5, 1, 1, 2, 'UNI-123', 1, 2, '2025-10-23 17:18:06', '2025-10-24 18:24:52'),
(62, 170, 5, 5, 1, 3, 'I-79465', 3, 2, '2025-10-23 17:19:23', '2025-10-24 18:31:07'),
(63, 170, 2, 3, 2, 4, 'FA24/12345', 4, 2, '2025-10-23 17:20:58', '2025-10-24 18:59:13'),
(64, 170, 2, 2, 1, 5, 'E-78454', 5, 2, '2025-10-23 17:21:51', '2025-10-24 18:34:09'),
(65, 170, 2, 1, 4, 6, 'PEDIDO 2', 1, 2, '2025-10-23 17:27:16', '2025-10-24 18:57:24'),
(66, 171, 2, 1, 4, 1, '12345', 1, 3, '2025-10-24 10:51:36', '2025-10-24 18:57:06'),
(67, 172, 2, 3, 3, 1, 'SYSCOM', 1, 4, '2025-10-24 10:54:24', '2025-10-24 18:56:11'),
(68, 172, 2, 2, 1, 2, 'FORANEO', 2, 4, '2025-10-24 10:54:59', '2025-10-24 18:32:32'),
(69, 172, 2, 3, 1, 3, 'FORANEO-SUC', 3, 4, '2025-10-24 11:37:40', '2025-10-24 18:32:32'),
(70, 172, 2, 5, 1, 4, 'ING-', 4, 4, '2025-10-24 11:38:53', '2025-10-24 18:32:32'),
(71, 172, 2, 2, 1, 5, '12345', 5, 4, '2025-10-24 11:39:22', '2025-10-24 18:32:32'),
(72, 170, 2, 3, 3, 7, 'MITRAS-1', 1, 2, '2025-10-24 16:37:03', '2025-10-24 18:32:32'),
(73, 170, 2, 6, 6, 8, 'PV12345', 1, 2, '2025-10-24 16:40:07', '2025-10-27 17:07:47'),
(74, 170, 5, 1, 4, 9, 'UNICOM-546454', 1, 2, '2025-10-24 16:47:56', '2025-10-24 18:22:55'),
(75, 173, 4, 2, 5, 1, '123', 2, 2, '2025-10-24 19:52:10', '2025-10-27 17:34:04'),
(76, 174, 2, 1, 4, 1, 'RAELI-PEDIDO-123', 1, 2, '2025-10-28 18:12:47', '2025-10-28 18:12:47'),
(77, 193, 2, 1, 4, 1, '112233-A', 1, 2, '2025-10-29 17:50:17', '2025-10-29 17:50:17'),
(78, 194, 2, 2, 5, 1, '121121211', 1, 2, '2025-10-29 19:07:10', '2025-10-29 19:07:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `supplier_order_types`
--

CREATE TABLE `supplier_order_types` (
  `id` int(11) NOT NULL,
  `order_type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `supplier_order_types`
--

INSERT INTO `supplier_order_types` (`id`, `order_type`) VALUES
(1, 'local'),
(2, 'Foráneo a ERA'),
(3, 'foraneo a sucursal'),
(4, 'varias sucursales'),
(5, 'proveedor a usuario final');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `supplier_pickup_comments`
--

CREATE TABLE `supplier_pickup_comments` (
  `id` int(11) NOT NULL,
  `id_supplier_order` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_orders`
--

CREATE TABLE `system_orders` (
  `id` int(11) NOT NULL,
  `system_number` int(10) NOT NULL,
  `system_number_date` datetime NOT NULL,
  `system_order_last_update` datetime DEFAULT NULL,
  `id_status` int(11) DEFAULT 1,
  `has_data` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(10) NOT NULL,
  `system_order_series` varchar(10) NOT NULL DEFAULT 'SO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `system_orders`
--

INSERT INTO `system_orders` (`id`, `system_number`, `system_number_date`, `system_order_last_update`, `id_status`, `has_data`, `created_by`, `system_order_series`) VALUES
(388, 1, '2025-10-23 17:15:12', '2025-10-24 18:58:32', 1, 1, 2, 'SO'),
(389, 2, '2025-10-24 10:51:06', '2025-10-24 18:32:32', 1, 1, 3, 'SO'),
(390, 3, '2025-10-24 10:52:53', '2025-10-24 18:32:32', 1, 1, 4, 'SO'),
(392, 4, '2025-10-24 19:50:27', '2025-10-24 19:52:40', 1, 1, 2, 'SO'),
(393, 5, '2025-10-28 18:10:16', '2025-10-28 18:12:47', 1, 1, 2, 'SO'),
(396, 6, '2025-10-29 17:16:14', '2025-10-29 18:43:24', 1, 1, 2, 'SO'),
(397, 7, '2025-10-29 19:01:04', '2025-10-29 19:07:10', 1, 1, 2, 'SO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_order_audit`
--

CREATE TABLE `system_order_audit` (
  `id_audit` int(11) NOT NULL,
  `id_system_order` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_order_status`
--

CREATE TABLE `system_order_status` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `system_order_status`
--

INSERT INTO `system_order_status` (`id`, `name`) VALUES
(1, 'Abierto'),
(2, 'Finalizado'),
(3, 'Incompleto'),
(4, 'Cancelado'),
(5, 'Temporal'),
(6, 'Por llenar'),
(7, 'Eliminado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tracking_supplier_orders`
--

CREATE TABLE `tracking_supplier_orders` (
  `id` int(10) NOT NULL,
  `id_item_supplier_order` int(10) NOT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `tracking_company` varchar(255) DEFAULT NULL,
  `origin_branch` varchar(255) DEFAULT NULL,
  `arrival_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tracking_supplier_orders`
--

INSERT INTO `tracking_supplier_orders` (`id`, `id_item_supplier_order`, `tracking_number`, `tracking_company`, `origin_branch`, `arrival_date`) VALUES
(5, 42, 'undefined', 'undefined', NULL, '0000-00-00'),
(6, 43, NULL, NULL, NULL, '2025-10-30'),
(7, 44, NULL, NULL, NULL, '2025-10-29'),
(8, 45, NULL, NULL, 'CDMX', '2025-10-30'),
(9, 46, NULL, NULL, 'GUADALAJARA', '2025-10-24'),
(10, 47, '789456123', 'PAQUETERIA EXEL', NULL, '2025-10-29'),
(11, 53, '123456', 'ESTAFETA', NULL, '2025-10-31'),
(12, 54, '', '', NULL, '2025-10-30'),
(13, 55, '', '', NULL, '2025-11-06'),
(14, 56, NULL, NULL, 'GUADALAJARA', '2025-10-31'),
(15, 57, NULL, NULL, 'YUCATAN', '2025-10-31'),
(16, 58, '45665464564', 'PAQUETE EXPRESS', NULL, '2025-10-30'),
(17, 62, '', '', NULL, '2025-10-23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(10) NOT NULL,
  `user` varchar(50) NOT NULL,
  `pass` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `apellidos` varchar(50) NOT NULL,
  `email` varchar(60) NOT NULL,
  `id_position` int(10) NOT NULL,
  `is_leader` tinyint(1) DEFAULT 0,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `user`, `pass`, `firstname`, `apellidos`, `email`, `id_position`, `is_leader`, `parent_id`) VALUES
(1, 'gmartinez', '0b365a54e821e1144537b8f90aa0707a', 'Gerónimo', 'Martínez', 'gmartinez@eracomputacional.com.mx', 7, 0, NULL),
(2, 'ventas1', '20a8c8e0c41ac50845e9f2d06ed5949f', 'Alicia', 'Alonso', 'alicia.alonso@eracomputacional.com.mx', 5, 0, NULL),
(3, 'ventas2', 'bb79e4f40d62ed88434f541152da170c', 'Alfredo ', 'Capetillo', 'alfredo.capetillo@eracomputacional.com.mx', 5, 0, NULL),
(4, 'ventas3', 'f6115b874b0a94fc340215e0ddc1a3af', 'Raul', 'Quintanilla', 'raul.quintanilla@eracomputacional.com.mx', 5, 0, NULL),
(5, 'facturas1', '0c04074287470a75d077ae4ad3b2a207', 'Luis Raeli', 'Flores', 'administracion@eracomputacional.com.mx', 9, 0, NULL),
(6, 'temo1', 'ecce598466200847380415b65063578c', 'Temo ', 'Charles', '', 6, 1, NULL),
(7, 'tavo1', 'a5be0161ec71b3a91b9b8a34367ef1cf', 'Gustavo', 'Arellano', '', 6, 0, NULL),
(8, 'aruiz', '66fdf2d9ffcae25a01c9bb648ebaa631', 'Alfredo', 'Ruiz Bustos', 'aruiz@eracomputacional.com.mx', 1, 0, NULL),
(9, 'albertob', '35df015f09148d9192a108a5fc5ea560', 'José Alberto', 'Bustos Martínez', 'alberto.bustos@eracomputacional.com.mx', 4, 0, NULL),
(10, 'ventas4', 'f15b7cbd6e3ba679e86b6c53e94fc3d3', 'Ithan Alejandro', 'Alonso', 'ventas1@eracomputacional.com.mx', 8, 0, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `ui_mode` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_series`
--

CREATE TABLE `user_series` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `series_code` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user_series`
--

INSERT INTO `user_series` (`id`, `id_user`, `series_code`) VALUES
(1, 9, 'FIN-001'),
(2, 8, 'DIR-001'),
(3, 5, 'ADM-001'),
(4, 1, 'TI-001'),
(5, 2, 'VEN-001'),
(6, 3, 'VEN-002'),
(7, 4, 'VEN-003'),
(8, 10, 'VEN-010');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usos_cfdi`
--

CREATE TABLE `usos_cfdi` (
  `id` int(11) NOT NULL,
  `uso_cfdi` varchar(10) NOT NULL,
  `descripcion` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usos_cfdi`
--

INSERT INTO `usos_cfdi` (`id`, `uso_cfdi`, `descripcion`) VALUES
(1, 'G01', 'Adquisición de mercancías.'),
(2, 'G02', 'Devoluciones, descuentos o bonificaciones.'),
(3, 'G03', 'Gastos en general.'),
(4, 'I01', 'Construcciones.'),
(5, 'I02', 'Mobiliario y equipo de oficina por inversiones.'),
(6, 'I03', 'Equipo de transporte.'),
(7, 'I04', 'Equipo de computo y accesorios.'),
(8, 'I05', 'Dados, troqueles, moldes, matrices y herramental.'),
(9, 'I06', 'Comunicaciones telefónicas.'),
(10, 'I07', 'Comunicaciones satelitales.'),
(11, 'I08', 'Otra maquinaria y equipo.'),
(12, 'D01', 'Honorarios médicos, dentales y gastos hospitalarios.'),
(13, 'D02', 'Gastos médicos por incapacidad o discapacidad.'),
(14, 'D03', 'Gastos funerales.'),
(15, 'D04', 'Donativos.'),
(16, 'D05', 'Intereses reales efectivamente pagados por créditos hipotecarios (casa habitación).'),
(17, 'D06', 'Aportaciones voluntarias al SAR.'),
(18, 'D07', 'Primas por seguros de gastos médicos.'),
(19, 'D08', 'Gastos de transportación escolar obligatoria.'),
(20, 'D09', 'Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones.'),
(21, 'D10', 'Pagos por servicios educativos (colegiaturas).'),
(22, 'S01', 'Sin efectos fiscales.'),
(23, 'CP01', 'Pagos.'),
(24, 'CN01', 'Nómina.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `views`
--

CREATE TABLE `views` (
  `id` int(11) NOT NULL,
  `view_name` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `type` enum('menu','dashboard','widget') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `views`
--

INSERT INTO `views` (`id`, `view_name`, `label`, `type`) VALUES
(1, 'dashboard_sales', 'Dashboard Ventas', 'dashboard'),
(2, 'dashboard_ti', 'Dashboard TI', 'dashboard'),
(3, 'menu_sales', 'Menú Ventas', 'menu'),
(4, 'menu_ti', 'Menú TI', 'menu');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `company_info`
--
ALTER TABLE `company_info`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `customers_address`
--
ALTER TABLE `customers_address`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_customer` (`id_customer`);

--
-- Indices de la tabla `customer_delivery_comments`
--
ALTER TABLE `customer_delivery_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_customer_order` (`id_customer_order`);

--
-- Indices de la tabla `customer_orders`
--
ALTER TABLE `customer_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_system_order` (`id_system_order`),
  ADD KEY `id_customer` (`id_customer`),
  ADD KEY `id_status` (`id_status`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `id_uso_cfdi` (`id_uso_cfdi`);

--
-- Indices de la tabla `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `department_menus`
--
ALTER TABLE `department_menus`
  ADD PRIMARY KEY (`id_department`,`id_menu`),
  ADD KEY `id_menu` (`id_menu`);

--
-- Indices de la tabla `department_views`
--
ALTER TABLE `department_views`
  ADD PRIMARY KEY (`id_department`,`id_view`),
  ADD KEY `id_view` (`id_view`);

--
-- Indices de la tabla `items_supplier_order`
--
ALTER TABLE `items_supplier_order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_supplier_order` (`id_supplier_order`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_itemsupplierorder_status` (`id_status`);

--
-- Indices de la tabla `logistic_assignments`
--
ALTER TABLE `logistic_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_tracking` (`id_tracking`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indices de la tabla `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indices de la tabla `other_trips`
--
ALTER TABLE `other_trips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_driver` (`id_user`);

--
-- Indices de la tabla `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indices de la tabla `status_customer_order`
--
ALTER TABLE `status_customer_order`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `status_supplier_item`
--
ALTER TABLE `status_supplier_item`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `status_supplier_order`
--
ALTER TABLE `status_supplier_order`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `supplier_adress`
--
ALTER TABLE `supplier_adress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_supplier` (`id_supplier`);

--
-- Indices de la tabla `supplier_orders`
--
ALTER TABLE `supplier_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_customer_order` (`id_customer_order`),
  ADD KEY `id_supplier` (`id_supplier`),
  ADD KEY `id_order_type` (`id_order_type`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `id_status` (`id_status`),
  ADD KEY `id_branch` (`id_branch`);

--
-- Indices de la tabla `supplier_order_types`
--
ALTER TABLE `supplier_order_types`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `supplier_pickup_comments`
--
ALTER TABLE `supplier_pickup_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_supplier_order` (`id_supplier_order`);

--
-- Indices de la tabla `system_orders`
--
ALTER TABLE `system_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_system_order_status` (`id_status`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `system_order_audit`
--
ALTER TABLE `system_order_audit`
  ADD PRIMARY KEY (`id_audit`),
  ADD KEY `fk_order_audit` (`id_system_order`),
  ADD KEY `fk_user_audit` (`changed_by`);

--
-- Indices de la tabla `system_order_status`
--
ALTER TABLE `system_order_status`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tracking_supplier_orders`
--
ALTER TABLE `tracking_supplier_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_item_supplier_order` (`id_item_supplier_order`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_position` (`id_position`),
  ADD KEY `fk_users_parent` (`parent_id`);

--
-- Indices de la tabla `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `user_series`
--
ALTER TABLE `user_series`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `series_code` (`series_code`);

--
-- Indices de la tabla `usos_cfdi`
--
ALTER TABLE `usos_cfdi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `c_UsoCFDI` (`uso_cfdi`);

--
-- Indices de la tabla `views`
--
ALTER TABLE `views`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `company_info`
--
ALTER TABLE `company_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `customers_address`
--
ALTER TABLE `customers_address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `customer_delivery_comments`
--
ALTER TABLE `customer_delivery_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `customer_orders`
--
ALTER TABLE `customer_orders`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=195;

--
-- AUTO_INCREMENT de la tabla `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `items_supplier_order`
--
ALTER TABLE `items_supplier_order`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT de la tabla `logistic_assignments`
--
ALTER TABLE `logistic_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1011;

--
-- AUTO_INCREMENT de la tabla `other_trips`
--
ALTER TABLE `other_trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `status_customer_order`
--
ALTER TABLE `status_customer_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `status_supplier_order`
--
ALTER TABLE `status_supplier_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `supplier_adress`
--
ALTER TABLE `supplier_adress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `supplier_orders`
--
ALTER TABLE `supplier_orders`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT de la tabla `supplier_order_types`
--
ALTER TABLE `supplier_order_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `supplier_pickup_comments`
--
ALTER TABLE `supplier_pickup_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `system_orders`
--
ALTER TABLE `system_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=398;

--
-- AUTO_INCREMENT de la tabla `system_order_audit`
--
ALTER TABLE `system_order_audit`
  MODIFY `id_audit` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `system_order_status`
--
ALTER TABLE `system_order_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tracking_supplier_orders`
--
ALTER TABLE `tracking_supplier_orders`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `user_series`
--
ALTER TABLE `user_series`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `usos_cfdi`
--
ALTER TABLE `usos_cfdi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `views`
--
ALTER TABLE `views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `customers_address`
--
ALTER TABLE `customers_address`
  ADD CONSTRAINT `customers_address_ibfk_1` FOREIGN KEY (`id_customer`) REFERENCES `customers` (`id`);

--
-- Filtros para la tabla `customer_delivery_comments`
--
ALTER TABLE `customer_delivery_comments`
  ADD CONSTRAINT `customer_delivery_comments_ibfk_1` FOREIGN KEY (`id_customer_order`) REFERENCES `customer_orders` (`id`);

--
-- Filtros para la tabla `customer_orders`
--
ALTER TABLE `customer_orders`
  ADD CONSTRAINT `customer_orders_ibfk_1` FOREIGN KEY (`id_system_order`) REFERENCES `system_orders` (`id`),
  ADD CONSTRAINT `customer_orders_ibfk_2` FOREIGN KEY (`id_customer`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `customer_orders_ibfk_3` FOREIGN KEY (`id_status`) REFERENCES `status_customer_order` (`id`),
  ADD CONSTRAINT `customer_orders_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `customer_orders_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `customer_orders_ibfk_6` FOREIGN KEY (`id_uso_cfdi`) REFERENCES `usos_cfdi` (`id`);

--
-- Filtros para la tabla `department_menus`
--
ALTER TABLE `department_menus`
  ADD CONSTRAINT `department_menus_ibfk_1` FOREIGN KEY (`id_department`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `department_menus_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `department_views`
--
ALTER TABLE `department_views`
  ADD CONSTRAINT `department_views_ibfk_1` FOREIGN KEY (`id_department`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `department_views_ibfk_2` FOREIGN KEY (`id_view`) REFERENCES `views` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `items_supplier_order`
--
ALTER TABLE `items_supplier_order`
  ADD CONSTRAINT `fk_itemsupplierorder_status` FOREIGN KEY (`id_status`) REFERENCES `status_supplier_item` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `items_supplier_order_ibfk_1` FOREIGN KEY (`id_supplier_order`) REFERENCES `supplier_orders` (`id`),
  ADD CONSTRAINT `items_supplier_order_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `logistic_assignments`
--
ALTER TABLE `logistic_assignments`
  ADD CONSTRAINT `logistic_assignments_ibfk_1` FOREIGN KEY (`id_tracking`) REFERENCES `tracking_supplier_orders` (`id`),
  ADD CONSTRAINT `logistic_assignments_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `menus_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `other_trips`
--
ALTER TABLE `other_trips`
  ADD CONSTRAINT `other_trips_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Filtros para la tabla `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `supplier_adress`
--
ALTER TABLE `supplier_adress`
  ADD CONSTRAINT `supplier_adress_ibfk_1` FOREIGN KEY (`id_supplier`) REFERENCES `suppliers` (`id`);

--
-- Filtros para la tabla `supplier_orders`
--
ALTER TABLE `supplier_orders`
  ADD CONSTRAINT `supplier_orders_ibfk_1` FOREIGN KEY (`id_customer_order`) REFERENCES `customer_orders` (`id`),
  ADD CONSTRAINT `supplier_orders_ibfk_3` FOREIGN KEY (`id_supplier`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `supplier_orders_ibfk_4` FOREIGN KEY (`id_order_type`) REFERENCES `supplier_order_types` (`id`),
  ADD CONSTRAINT `supplier_orders_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `supplier_orders_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `supplier_orders_ibfk_7` FOREIGN KEY (`id_status`) REFERENCES `status_supplier_order` (`id`),
  ADD CONSTRAINT `supplier_orders_ibfk_8` FOREIGN KEY (`id_branch`) REFERENCES `supplier_adress` (`id`);

--
-- Filtros para la tabla `supplier_pickup_comments`
--
ALTER TABLE `supplier_pickup_comments`
  ADD CONSTRAINT `supplier_pickup_comments_ibfk_1` FOREIGN KEY (`id_supplier_order`) REFERENCES `supplier_orders` (`id`);

--
-- Filtros para la tabla `system_orders`
--
ALTER TABLE `system_orders`
  ADD CONSTRAINT `fk_system_order_status` FOREIGN KEY (`id_status`) REFERENCES `system_order_status` (`id`),
  ADD CONSTRAINT `system_orders_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `system_order_audit`
--
ALTER TABLE `system_order_audit`
  ADD CONSTRAINT `fk_order_audit` FOREIGN KEY (`id_system_order`) REFERENCES `system_orders` (`id`),
  ADD CONSTRAINT `fk_user_audit` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `tracking_supplier_orders`
--
ALTER TABLE `tracking_supplier_orders`
  ADD CONSTRAINT `tracking_supplier_orders_ibfk_1` FOREIGN KEY (`id_item_supplier_order`) REFERENCES `items_supplier_order` (`id`);

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_parent` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_position`) REFERENCES `positions` (`id`);

--
-- Filtros para la tabla `user_series`
--
ALTER TABLE `user_series`
  ADD CONSTRAINT `user_series_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
