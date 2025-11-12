-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.32-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for web_shop
-- DROP DATABASE IF EXISTS `web_shop`;
-- CREATE DATABASE IF NOT EXISTS `web_shop` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
-- USE `web_shop`;

-- Dumping structure for table web_shop.cart_items
DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE IF NOT EXISTS `cart_items` (
  `cart_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `unique_product_in_cart` (`user_id`,`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.cart_items: ~0 rows (approximately)
DELETE FROM `cart_items`;

-- Dumping structure for table web_shop.coupons
DROP TABLE IF EXISTS `coupons`;
CREATE TABLE IF NOT EXISTS `coupons` (
  `coupon_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `coupon_code` varchar(20) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL COMMENT 'เปอร์เซ็นต์ส่วนลด 0.00 - 100.00',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `usage_limit` int(11) NOT NULL DEFAULT 100,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `is_giveaway` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`coupon_id`),
  UNIQUE KEY `coupon_code` (`coupon_code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.coupons: ~5 rows (approximately)
DELETE FROM `coupons`;
INSERT INTO `coupons` (`coupon_id`, `coupon_code`, `discount_percent`, `is_active`, `created_by`, `created_at`, `usage_limit`, `used_count`, `is_giveaway`) VALUES
	(1, 'CPN2QM1RXM', 10.00, 1, 2, '2025-11-12 09:55:39', 5, 0, 0),
	(2, 'SALE2024', 50.00, 1, 2, '2025-11-12 09:55:49', 100, 0, 0),
	(3, 'CPNJSHRA94', 100.00, 0, 2, '2025-11-12 10:30:05', 1, 0, 0),
	(4, 'SALE20202020', 35.00, 1, 2, '2025-11-12 11:22:24', 100, 0, 0),
	(5, 'SALE2025', 10.00, 1, 2, '2025-11-12 11:32:20', 100, 1, 1),
	(6, 'NEWUSER', 35.00, 1, 2, '2025-11-12 13:40:54', 10, 1, 1);

-- Dumping structure for table web_shop.download_history
DROP TABLE IF EXISTS `download_history`;
CREATE TABLE IF NOT EXISTS `download_history` (
  `download_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`download_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.download_history: ~4 rows (approximately)
DELETE FROM `download_history`;
INSERT INTO `download_history` (`download_id`, `user_id`, `product_id`, `timestamp`) VALUES
	(1, 7, 5, '2025-11-12 10:47:18'),
	(2, 7, 5, '2025-11-12 10:55:05'),
	(3, 7, 9, '2025-11-12 10:55:54'),
	(4, 7, 4, '2025-11-12 10:55:57'),
	(5, 25, 9, '2025-11-12 13:41:50');

-- Dumping structure for table web_shop.download_limits
DROP TABLE IF EXISTS `download_limits`;
CREATE TABLE IF NOT EXISTS `download_limits` (
  `limit_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `download_date` date NOT NULL COMMENT 'วันที่ดาวน์โหลด (YYYY-MM-DD)',
  `download_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`limit_id`),
  UNIQUE KEY `user_product_daily_limit` (`user_id`,`product_id`,`download_date`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.download_limits: ~3 rows (approximately)
DELETE FROM `download_limits`;
INSERT INTO `download_limits` (`limit_id`, `user_id`, `product_id`, `download_date`, `download_count`) VALUES
	(1, 7, 5, '2025-11-12', 1),
	(2, 7, 9, '2025-11-12', 1),
	(3, 7, 4, '2025-11-12', 1),
	(4, 25, 9, '2025-11-12', 1);

-- Dumping structure for table web_shop.live_chat
DROP TABLE IF EXISTS `live_chat`;
CREATE TABLE IF NOT EXISTS `live_chat` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `sender_username` varchar(50) NOT NULL,
  `sender_role` enum('user','admin') NOT NULL,
  `message_text` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.live_chat: ~0 rows (approximately)
DELETE FROM `live_chat`;
INSERT INTO `live_chat` (`message_id`, `sender_id`, `sender_username`, `sender_role`, `message_text`, `image_path`, `timestamp`) VALUES
	(23, 25, 'nice', 'user', 'สวัสดีครับแอดมิน', NULL, '2025-11-12 20:42:36'),
	(24, 2, 'eskimo1579', 'admin', 'เป็นไรอะ', NULL, '2025-11-12 20:42:43');

-- Dumping structure for table web_shop.products
DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `avg_rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.products: ~6 rows (approximately)
DELETE FROM `products`;
INSERT INTO `products` (`product_id`, `name`, `price`, `stock`, `description`, `file_path`, `image_url`, `avg_rating`, `review_count`) VALUES
	(3, 'คอร์สสร้างเว็บไซต์โดย PHP', 250.00, 93, 'เริ่มต้นเขียนเว็บแอปพลิเคชันด้วย PHP ภาษายอดนิยมที่ใช้สร้างเว็บไซต์มานับล้านทั่วโลก. เรียนรู้การเชื่อมต่อฐานข้อมูล การจัดการฟอร์ม และการประมวลผลข้อมูล. เหมาะสำหรับผู้ที่ต้องการเข้าใจการทำงานของเว็บฝั่งเซิร์ฟเวอร์', 'downloadable_works/file_6913083b1e6a30.10600915.pdf', 'https://img2.pic.in.th/pic/php.png', 0.00, 0),
	(4, 'ปูพื้นฐานการเขียนโปรแกรมด้วย Python', 200.00, 94, 'ปูพื้นฐานการเขียนโปรแกรมด้วย Python ภาษาใช้งานง่ายและทรงพลัง. เรียนรู้แนวคิดพื้นฐาน เช่น ตัวแปร เงื่อนไข ลูป และฟังก์ชัน. เหมาะสำหรับผู้เริ่มต้นที่ต้องการต่อยอดสู่สายงาน Data, AI หรือ Web Development', 'downloadable_works/file_691308447a37f9.75841636.pdf', 'https://img2.pic.in.th/pic/pythona430f86a25864b77.png', 0.00, 0),
	(5, 'คอร์ส Frontend ประกอบด้วย JS , CSS , HTML', 280.00, 98, 'เรียนรู้การพัฒนาเว็บไซต์ฝั่งผู้ใช้ด้วย HTML, CSS และ JavaScript. เข้าใจการออกแบบหน้าตาเว็บไซต์ให้สวยงามและตอบสนองต่อผู้ใช้. เหมาะสำหรับผู้ที่ต้องการสร้างเว็บเพจที่มีชีวิตชีวาและใช้งานได้จริง', 'downloadable_works/file_6913084a1092a5.01539263.pdf', 'https://img5.pic.in.th/file/secure-sv1/frontend.png', 0.00, 0),
	(6, 'คอร์สการเก็บข้อมูลโดย Json', 50.00, 89, 'ทำความเข้าใจรูปแบบข้อมูล JSON (JavaScript Object Notation) ที่ใช้แลกเปลี่ยนข้อมูลระหว่างระบบ. เรียนรู้วิธีการอ่าน เขียน และประยุกต์ใช้งาน JSON กับ API ต่าง ๆ. เหมาะสำหรับนักพัฒนาเว็บและแอปพลิเคชันทุกระดับ', 'downloadable_works/file_6913084f12a649.26465364.pdf', 'https://img5.pic.in.th/file/secure-sv1/json.png', 0.00, 0),
	(7, 'คอร์สภาษา C ', 400.00, 100, 'เรียนรู้ภาษา C ซึ่งเป็นรากฐานของหลายภาษาสมัยใหม่ เช่น C++ และ Java. เข้าใจโครงสร้างโปรแกรม การจัดการหน่วยความจำ และตรรกะของการเขียนโปรแกรมระดับล่าง. เหมาะสำหรับผู้ที่ต้องการเข้าใจพื้นฐานเชิงลึกของการทำงานของคอมพิวเตอร์', 'downloadable_works/file_69130854c474d8.37731204.pdf', 'https://img5.pic.in.th/file/secure-sv1/cb9ec84ccd3d2e137.png', 0.00, 0),
	(9, 'คอร์สหลังบ้าน โดยใช้ NodeJs', 380.00, 9, 'เรียนรู้การสร้างเว็บแอปพลิเคชันฝั่งเซิร์ฟเวอร์ด้วย JavaScript โดยใช้ Node.js ที่ได้รับความนิยมทั่วโลก. เข้าใจหลักการทำงานแบบ asynchronous และการจัดการ API อย่างมีประสิทธิภาพ. ปูพื้นฐานการพัฒนา backend สำหรับผู้ที่ต้องการต่อยอดสู่ระดับมืออาชีพ', 'downloadable_works/file_69130859b85a24.69596038.pdf', 'https://img2.pic.in.th/pic/nodejs.png', 0.00, 0);

-- Dumping structure for table web_shop.redeem_codes
DROP TABLE IF EXISTS `redeem_codes`;
CREATE TABLE IF NOT EXISTS `redeem_codes` (
  `code_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `redeem_code` varchar(30) NOT NULL,
  `point_amount` decimal(10,2) NOT NULL,
  `max_uses` int(11) NOT NULL DEFAULT 1,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_giveaway` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`code_id`),
  UNIQUE KEY `redeem_code` (`redeem_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.redeem_codes: ~1 rows (approximately)
DELETE FROM `redeem_codes`;
INSERT INTO `redeem_codes` (`code_id`, `redeem_code`, `point_amount`, `max_uses`, `used_count`, `is_active`, `created_at`, `is_giveaway`) VALUES
	(1, 'FREWE1', 1000.00, 1, 1, 1, '2025-11-12 12:08:49', 0),
	(2, 'FREWE11', 1.00, 1, 0, 1, '2025-11-12 12:20:31', 0),
	(3, 'FREWE1FREWE1', 12.00, 1, 1, 1, '2025-11-12 12:25:21', 0),
	(4, 'FREWE1232', 1.00, 1, 0, 1, '2025-11-12 12:31:19', 0),
	(5, 'FFF', 50.00, 1, 1, 1, '2025-11-12 13:41:05', 1);

-- Dumping structure for table web_shop.reviews
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL COMMENT 'คะแนน 1-5',
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `unique_review` (`user_id`,`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.reviews: ~2 rows (approximately)
DELETE FROM `reviews`;
INSERT INTO `reviews` (`review_id`, `user_id`, `product_id`, `rating`, `review_text`, `created_at`) VALUES
	(10, 7, 5, 3, '0ดำฟหก', '2025-11-12 10:38:21'),
	(12, 2, 3, 5, 'โง่มาก', '2025-11-12 11:06:23');

-- Dumping structure for table web_shop.support_messages
DROP TABLE IF EXISTS `support_messages`;
CREATE TABLE IF NOT EXISTS `support_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender_role` enum('user','admin') NOT NULL,
  `message_text` text NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.support_messages: ~0 rows (approximately)
DELETE FROM `support_messages`;
INSERT INTO `support_messages` (`message_id`, `ticket_id`, `user_id`, `sender_role`, `message_text`, `timestamp`) VALUES
	(101, 18, 25, 'user', 'แอดมินช่วยด้วย', '2025-11-12 20:42:08'),
	(102, 18, 2, 'admin', 'เป็นไรมากป่าว', '2025-11-12 20:42:24');

-- Dumping structure for table web_shop.support_tickets
DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('open','closed','answered') DEFAULT 'open',
  `created_at` datetime DEFAULT current_timestamp(),
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ticket_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.support_tickets: ~0 rows (approximately)
DELETE FROM `support_tickets`;
INSERT INTO `support_tickets` (`ticket_id`, `user_id`, `subject`, `status`, `created_at`, `last_updated`) VALUES
	(18, 25, 'โหลดไฟล์ไม่ได้', 'closed', '2025-11-12 20:42:08', '2025-11-12 20:42:28');

-- Dumping structure for table web_shop.transactions
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `tx_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `tmweasy_ref` varchar(255) DEFAULT NULL,
  `slip_path` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`tx_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.transactions: ~8 rows (approximately)
DELETE FROM `transactions`;
INSERT INTO `transactions` (`tx_id`, `user_id`, `amount`, `status`, `tmweasy_ref`, `slip_path`, `timestamp`) VALUES
	(1, 2, 100.00, 'failed', 'BANK17628494462', NULL, '2025-11-11 15:24:06'),
	(2, 2, 1.00, 'success', 'BANK17628495112', NULL, '2025-11-11 15:25:11'),
	(3, 4, 100.00, 'failed', 'BANK17628511854', 'transactions_slips/slip_6912f9711bd9b1.88316881.jpg', '2025-11-11 15:53:05'),
	(4, 2, 1000.00, 'success', 'BANK17628522722', 'transactions_slips/slip_6912fdb0ae30a2.83179250.jpg', '2025-11-11 16:11:12'),
	(5, 5, 100.00, 'success', 'BANK17628560095', 'transactions_slips/slip_69130c49edc061.38161885.png', '2025-11-11 17:13:29'),
	(6, 6, 1000.00, 'success', 'BANK17628561086', 'transactions_slips/slip_69130cac7fa478.92515713.jpg', '2025-11-11 17:15:08'),
	(7, 7, 500.00, 'success', 'BANK17628640107', 'transactions_slips/slip_69132b8a479e85.93358441.png', '2025-11-11 19:26:50'),
	(9, 7, 1000.00, 'pending', 'BANK17629472127', 'transactions_slips/slip_6914708c42e733.77852611.png', '2025-11-12 18:33:32'),
	(10, 25, 500.00, 'success', 'BANK176295481625', 'transactions_slips/slip_69148e40b98231.27177697.png', '2025-11-12 20:40:16');

-- Dumping structure for table web_shop.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_role` enum('user','admin') DEFAULT 'user',
  `wallet_point` decimal(10,2) DEFAULT 0.00,
  `tmweasy_con_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.users: ~2 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `user_role`, `wallet_point`, `tmweasy_con_id`) VALUES
	(1, 'admin', '$2y$10$wNnU.8sV9hWlWpXjXqYwO.L.bB.c0F.c.Nq/3j9uGzRj8P7r7v7t6', 'admin@shop.com', 'user', 0.00, NULL),
	(2, 'eskimo1579', '$2y$10$U1y4C3atWhBkfYmtWuZ/de/JF9FeKhjaE7/SHtPkcAlhTAuiSaDG2', 'bangmo16691@gmail.com', 'admin', 953.00, NULL),
	(24, 'Shit13', '$2y$10$y//Im3/WML34A75SNOyBxeQhhqFrSqdJ4FmkXeUBLGcSxqVIWXR5W', 'pea@gmail.com', 'user', 0.00, NULL),
	(25, 'nice', '$2y$10$PNe5dKBUDGSM7mm/tjfQaebPreKtPAr62xyM7ZWaeQg3gbWMZEQuG', 'pea@gmail.com', 'user', 303.00, NULL);

-- Dumping structure for table web_shop.user_purchases
DROP TABLE IF EXISTS `user_purchases`;
CREATE TABLE IF NOT EXISTS `user_purchases` (
  `purchase_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `price_paid` decimal(10,2) NOT NULL,
  `purchase_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`purchase_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `user_purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `user_purchases_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table web_shop.user_purchases: ~0 rows (approximately)
DELETE FROM `user_purchases`;
INSERT INTO `user_purchases` (`purchase_id`, `user_id`, `product_id`, `price_paid`, `purchase_date`) VALUES
	(24, 25, 9, 380.00, '2025-11-12 20:41:45');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
