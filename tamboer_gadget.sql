/*
 Navicat Premium Data Transfer

 Source Server         : MySQL
 Source Server Type    : MySQL
 Source Server Version : 100432
 Source Host           : localhost:3306
 Source Schema         : tamboer_gadget

 Target Server Type    : MySQL
 Target Server Version : 100432
 File Encoding         : 65001

 Date: 20/10/2024 17:38:22
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for cache
-- ----------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache`  (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`key`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of cache
-- ----------------------------
INSERT INTO `cache` VALUES ('b79612f3a21d34ace4a9a2235bd3ba44df28dd3e', 'i:1;', 1728134653, NULL, NULL);
INSERT INTO `cache` VALUES ('b79612f3a21d34ace4a9a2235bd3ba44df28dd3e:timer', 'i:1728134653;', 1728134653, NULL, NULL);
INSERT INTO `cache` VALUES ('f55b367f75a7e4ad9ad7e3f5cf968fac7715c423', 'i:5;', 1728846140, NULL, NULL);
INSERT INTO `cache` VALUES ('f55b367f75a7e4ad9ad7e3f5cf968fac7715c423:timer', 'i:1728846140;', 1728846140, NULL, NULL);

-- ----------------------------
-- Table structure for cache_locks
-- ----------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks`  (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`key`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `failed_jobs_uuid_unique`(`uuid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for job_batches
-- ----------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches`  (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `cancelled_at` int(11) NULL DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED NULL DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `jobs_queue_index`(`queue`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for laporan_bulanan
-- ----------------------------
DROP TABLE IF EXISTS `laporan_bulanan`;
CREATE TABLE `laporan_bulanan`  (
  `id_laporan` int(255) NOT NULL,
  `bulan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tahun` year NULL DEFAULT NULL,
  `pemasukan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `pengeluaran` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `unit_masuk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `unit_keluar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id_laporan`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 18 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of migrations
-- ----------------------------
INSERT INTO `migrations` VALUES (5, '0001_01_01_000000_create_users_table', 1, NULL, NULL);
INSERT INTO `migrations` VALUES (6, '0001_01_01_000001_create_cache_table', 1, NULL, NULL);
INSERT INTO `migrations` VALUES (7, '0001_01_01_000002_create_jobs_table', 1, NULL, NULL);
INSERT INTO `migrations` VALUES (8, '2024_09_04_215151_create_personal_access_tokens_table', 1, NULL, NULL);
INSERT INTO `migrations` VALUES (9, '2024_09_05_030859_add_timestamps_to_cache_locks_table', 2, NULL, NULL);
INSERT INTO `migrations` VALUES (10, '2024_09_05_030859_add_timestamps_to_cache_table', 2, NULL, NULL);
INSERT INTO `migrations` VALUES (11, '2024_09_05_030859_add_timestamps_to_failed_jobs_table', 2, NULL, NULL);
INSERT INTO `migrations` VALUES (12, '2024_09_05_030859_add_timestamps_to_laporan_bulanan_table', 2, NULL, NULL);
INSERT INTO `migrations` VALUES (13, '2024_09_05_030859_add_timestamps_to_migrations_table', 2, NULL, NULL);
INSERT INTO `migrations` VALUES (14, '2024_09_05_030859_add_timestamps_to_penjualan_table', 2, NULL, NULL);
INSERT INTO `migrations` VALUES (15, '2024_09_05_030859_add_timestamps_to_sessions_table', 2, NULL, NULL);
INSERT INTO `migrations` VALUES (16, '2024_09_05_030859_add_timestamps_to_stok_hp_table', 2, NULL, NULL);
INSERT INTO `migrations` VALUES (17, '2024_09_05_030859_add_timestamps_to_user_table', 2, NULL, NULL);

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens`  (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`email`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for penjualan
-- ----------------------------
DROP TABLE IF EXISTS `penjualan`;
CREATE TABLE `penjualan`  (
  `id_penjualan` int(255) NOT NULL AUTO_INCREMENT,
  `tanggal` date NULL DEFAULT NULL,
  `id_stock` int(255) NOT NULL,
  `id_sales` int(255) NOT NULL,
  `harga_jual` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id_penjualan`) USING BTREE,
  INDEX `fk_stock`(`id_stock`) USING BTREE,
  INDEX `fk_sales`(`id_sales`) USING BTREE,
  CONSTRAINT `fk_sales` FOREIGN KEY (`id_sales`) REFERENCES `user` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_stock` FOREIGN KEY (`id_stock`) REFERENCES `stok_hp` (`id_stock`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of penjualan
-- ----------------------------
INSERT INTO `penjualan` VALUES (1, '2024-09-10', 1, 2, '10000000');
INSERT INTO `penjualan` VALUES (2, '2024-09-10', 3, 1, '6500000');
INSERT INTO `penjualan` VALUES (3, '2024-09-23', 16, 1, '3999000');
INSERT INTO `penjualan` VALUES (7, '2024-09-28', 6, 1, '14000000');
INSERT INTO `penjualan` VALUES (8, '2024-09-28', 11, 1, '10250000');
INSERT INTO `penjualan` VALUES (9, '2024-09-28', 10, 2, '7750000');
INSERT INTO `penjualan` VALUES (10, '2024-09-28', 9, 2, '12000000');

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `last_used_at` timestamp(0) NULL DEFAULT NULL,
  `expires_at` timestamp(0) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `personal_access_tokens_token_unique`(`token`) USING BTREE,
  INDEX `personal_access_tokens_tokenable_type_tokenable_id_index`(`tokenable_type`, `tokenable_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sessions
-- ----------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions`  (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `sessions_user_id_index`(`user_id`) USING BTREE,
  INDEX `sessions_last_activity_index`(`last_activity`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of sessions
-- ----------------------------
INSERT INTO `sessions` VALUES ('4xpSteisH3WrQLjVnT2wYbadRh8YqmuJSmYHHmPF', NULL, '192.168.117.197', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSmlsOG1JU05WQmdlTkZjYTNSeFA2ekI0OGRSa0o4dDRhemRCeUg5eiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly8xOTIuMTY4LjExNy4xOTc6ODAwMC9wYWdlcy9sYXBvcmFuIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1728846116, NULL, NULL);
INSERT INTO `sessions` VALUES ('ETQWQNXpra8PYBjgTO9fxPQqtB5ZVZPn4HD1LUCP', NULL, '192.168.97.197', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiS1RLdE5MUnV3NVhzRzJyTGMzRG9NRXJpSmsyUDI1clpHbzhPM1NkWCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xOTIuMTY4Ljk3LjE5Nzo4MDAwL3BhZ2VzL3N0b2siO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1727965999, NULL, NULL);
INSERT INTO `sessions` VALUES ('JAt2kRsycjHee0C6A67YPG08Ct8tXoEo4IjmEP5s', NULL, '192.168.97.197', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVktzeHdaYmltc0VZTDRibTNlbzVzSDdacllyWGc5OWlkSThUTjB6QyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xOTIuMTY4Ljk3LjE5Nzo4MDAwIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1728133823, NULL, NULL);
INSERT INTO `sessions` VALUES ('Z0V0oaXbqXPcmwcVj9y3CrS3lWRRFgRvw50PAFrr', NULL, '192.168.97.197', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSU83ZVZGWjl3YjI3MXhMWnVTdnRXZ3d0bHZrSkNiNDNyMXRZR0hnRiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xOTIuMTY4Ljk3LjE5Nzo4MDAwL2Rhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1727987214, NULL, NULL);

-- ----------------------------
-- Table structure for stok_hp
-- ----------------------------
DROP TABLE IF EXISTS `stok_hp`;
CREATE TABLE `stok_hp`  (
  `id_stock` int(255) NOT NULL AUTO_INCREMENT,
  `tanggal` date NULL DEFAULT NULL,
  `imei` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `brand` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `warna` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `harga_masuk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `harga_jual` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `id_sales` int(255) NULL DEFAULT NULL,
  `status` enum('ada','terjual') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id_stock`) USING BTREE,
  INDEX `sales_fk`(`id_sales`) USING BTREE,
  CONSTRAINT `sales_fk` FOREIGN KEY (`id_sales`) REFERENCES `user` (`id_user`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE = InnoDB AUTO_INCREMENT = 28 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of stok_hp
-- ----------------------------
INSERT INTO `stok_hp` VALUES (1, '2024-09-01', '359759101234567', 'Samsung', 'Galaxy S21', 'Phantom Gray', '8000000', '10000000', 1, 'terjual', NULL, NULL);
INSERT INTO `stok_hp` VALUES (3, '2024-09-03', '353755091234561', 'Xiaomi', 'Mi 11', 'Horizon Blue', '5000000', '6500000', 2, 'terjual', NULL, NULL);
INSERT INTO `stok_hp` VALUES (6, '2024-09-06', '357920107654321', 'Huawei', 'P50 Pro', 'Cocoa Gold', '10000000', '13000000', 2, 'terjual', NULL, NULL);
INSERT INTO `stok_hp` VALUES (7, '2024-09-07', '354445100987654', 'OnePlus', '9 Pro', 'Morning Mist', '7500000', '9000000', 2, 'ada', NULL, NULL);
INSERT INTO `stok_hp` VALUES (8, '2024-09-08', '358265100654321', 'Sony', 'Xperia 5 III', 'Green', '8000000', '10000000', 2, 'ada', NULL, NULL);
INSERT INTO `stok_hp` VALUES (9, '2024-09-09', '358730105432167', 'Google', 'Pixel 6', 'Stormy Black', '8500000', '11000000', 1, 'terjual', NULL, NULL);
INSERT INTO `stok_hp` VALUES (10, '2024-09-10', '354541098765432', 'Realme', 'GT 2 Pro', 'Paper White', '6000000', '7500000', 2, 'terjual', NULL, NULL);
INSERT INTO `stok_hp` VALUES (11, '2024-09-05', '123456789012345', 'Samsung', 'Samsung Galaxy S22', 'Black', '8888888', '9999999', 2, 'terjual', '2024-09-05 03:15:07', '2024-09-05 03:15:07');
INSERT INTO `stok_hp` VALUES (16, '2024-09-09', '869534070886351', 'Oppo', 'Oppo A3 Pro 5G 8/256', 'Moonlight Purple', '3200000', '3999000', 2, 'terjual', NULL, NULL);
INSERT INTO `stok_hp` VALUES (17, '2024-09-20', '169708678610377', 'Samsung', 'Mi 11', 'Morning Mist', '5803987', '10617277', 1, 'ada', NULL, NULL);
INSERT INTO `stok_hp` VALUES (18, '2024-09-30', '178593192201749', 'Realme', 'Pixel 6', 'Moonlight Purple', '10015371', '12496487', 2, 'ada', NULL, NULL);
INSERT INTO `stok_hp` VALUES (19, '2024-09-25', '776948014857959', 'Xiaomi', '9 Pro', 'Phantom Gray', '5311467', '7492805', 1, 'ada', NULL, NULL);
INSERT INTO `stok_hp` VALUES (20, '2024-09-17', '962655427070328', 'Google', '9 Pro', 'Green', '8102841', '10022974', 2, 'ada', NULL, NULL);
INSERT INTO `stok_hp` VALUES (21, '2024-09-11', '411422666897584', 'Samsung', 'Xperia 5 III', 'Horizon Blue', '4129619', '5893767', 1, 'ada', NULL, NULL);
INSERT INTO `stok_hp` VALUES (22, '2024-09-14', '971802334067535', 'Oppo', '9 Pro', 'Morning Mist', '9862855', '13346593', 2, 'ada', NULL, NULL);
INSERT INTO `stok_hp` VALUES (23, '2024-09-01', '668325055156112', 'Sony', 'GT 2 Pro', 'Green', '10122502', '11396651', 2, 'ada', NULL, NULL);
INSERT INTO `stok_hp` VALUES (24, '2024-09-13', '000875501348687', 'Realme', 'GT 2 Pro', 'Phantom Gray', '5402417', '7461014', 2, 'ada', NULL, NULL);
INSERT INTO `stok_hp` VALUES (25, '2024-09-05', '596055848654033', 'Google', 'GT 2 Pro', 'Moonlight Purple', '5689667', '10192480', 2, 'ada', NULL, NULL);
INSERT INTO `stok_hp` VALUES (27, '2024-10-03', '7283617256371829', 'Oppo', 'A60 8/258', 'Ungu Malam', '2900000', '3400000', 1, 'ada', NULL, NULL);

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`  (
  `id_user` int(16) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `level` enum('owner','sales') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id_user`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of user
-- ----------------------------
INSERT INTO `user` VALUES (1, 'revworks', '$2y$12$pPhdfGgHM4hLV51c6uOeP.a2Xptof1DdkWxdl.9TmfZxfXq.jAOy6', 'owner', 'rev');
INSERT INTO `user` VALUES (2, 'revwerks', '$2y$12$Rft6B3vyhYKpa3fMP/ALbum4bIBiHmLj5F7oV6ue.VXcxeILScQoC', 'sales', 'reva');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp(0) NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `users_email_unique`(`email`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
