/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.10-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: qxpanuto_mid
-- ------------------------------------------------------
-- Server version	10.11.10-MariaDB-cll-lve

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `anonymous_notes`
--

DROP TABLE IF EXISTS `anonymous_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `anonymous_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `color` varchar(10) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `anonymous_notes`
--

LOCK TABLES `anonymous_notes` WRITE;
/*!40000 ALTER TABLE `anonymous_notes` DISABLE KEYS */;
INSERT INTO `anonymous_notes` (`id`, `content`, `color`, `created_at`) VALUES (1,'halo','#ffc6ff','2025-03-06 00:47:02'),
(2,'hey','#a0c4ff','2025-03-06 00:47:29'),
(3,'halo','#a0c4ff','2025-03-06 00:51:04'),
(4,'halo','#ffd6a5','2025-03-06 01:04:03'),
(5,'eyy','#ffadad','2025-03-06 01:04:23'),
(6,'halo','#bdb2ff','2025-03-06 01:14:15'),
(7,'good night','#fdffb6','2025-03-06 01:17:12'),
(8,'good morning','#ffadad','2025-03-06 01:22:22'),
(9,'good morning','#bdb2ff','2025-03-06 01:22:22');
/*!40000 ALTER TABLE `anonymous_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comments_user_id` (`user_id`),
  KEY `idx_comments_post_id` (`post_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
INSERT INTO `comments` (`id`, `user_id`, `post_id`, `content`, `created_at`) VALUES (1,1,1,'halo','2025-03-04 22:44:26'),
(2,3,15,'halo','2025-03-05 19:16:20'),
(3,3,2,'halo','2025-03-05 19:16:43'),
(4,6,16,'nice','2025-03-05 20:01:38'),
(5,8,16,'ngueng ngueng~~~','2025-03-05 22:30:00'),
(6,2,4,'good','2025-03-05 22:43:19'),
(7,2,19,'guys..','2025-03-05 23:01:14'),
(8,1,6,'nice info','2025-03-05 23:26:22'),
(9,1,18,'yes','2025-03-05 23:34:07'),
(10,2,21,'gws','2025-03-06 00:13:47'),
(11,2,15,'hai','2025-03-06 00:15:42'),
(12,1,12,'halo','2025-03-06 02:20:09'),
(13,2,21,'HALO','2025-03-06 09:08:29'),
(14,2,21,'HALO','2025-03-06 09:08:29'),
(15,2,20,'halo','2025-03-06 09:23:35'),
(16,2,20,'halo','2025-03-06 09:23:35'),
(17,2,20,'halo','2025-03-06 09:23:35'),
(18,9,17,'aowkwowk','2025-03-06 09:25:07'),
(19,9,17,'aowkwowk','2025-03-06 09:25:07'),
(20,9,17,'aowkwowk','2025-03-06 09:25:07'),
(21,2,21,'cek','2025-03-06 09:31:14'),
(22,7,21,'Cek masuk','2025-03-06 09:31:34'),
(23,2,21,'halo','2025-03-06 09:38:34');
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ignored_posts`
--

DROP TABLE IF EXISTS `ignored_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ignored_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ignore` (`user_id`,`post_id`),
  KEY `post_id` (`post_id`),
  KEY `idx_ignored_posts_user_id` (`user_id`),
  CONSTRAINT `ignored_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ignored_posts_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ignored_posts`
--

LOCK TABLES `ignored_posts` WRITE;
/*!40000 ALTER TABLE `ignored_posts` DISABLE KEYS */;
INSERT INTO `ignored_posts` (`id`, `user_id`, `post_id`, `created_at`) VALUES (1,1,2,'2025-03-05 14:34:57'),
(2,3,16,'2025-03-05 18:36:34'),
(3,3,1,'2025-03-05 18:38:20'),
(4,2,17,'2025-03-05 23:06:13'),
(5,1,16,'2025-03-05 23:27:39'),
(6,1,15,'2025-03-05 23:28:15'),
(7,2,21,'2025-03-06 10:10:58'),
(10,9,21,'2025-03-06 12:44:20'),
(11,6,23,'2025-03-06 13:09:09'),
(12,10,23,'2025-03-07 01:49:28'),
(13,10,21,'2025-03-07 01:49:44'),
(14,2,22,'2025-03-07 02:10:09'),
(15,11,24,'2025-03-07 03:14:37'),
(16,11,23,'0000-00-00 00:00:00'),
(17,11,22,'0000-00-00 00:00:00'),
(18,1,6,'2025-03-07 04:08:46'),
(19,1,25,'2025-03-07 04:23:49'),
(20,2,25,'0000-00-00 00:00:00'),
(21,2,27,'0000-00-00 00:00:00'),
(22,2,35,'0000-00-00 00:00:00');
/*!40000 ALTER TABLE `ignored_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `likes`
--

DROP TABLE IF EXISTS `likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`user_id`,`post_id`),
  KEY `idx_likes_user_id` (`user_id`),
  KEY `idx_likes_post_id` (`post_id`),
  KEY `idx_likes_user_post` (`user_id`,`post_id`),
  CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=216 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `likes`
--

LOCK TABLES `likes` WRITE;
/*!40000 ALTER TABLE `likes` DISABLE KEYS */;
INSERT INTO `likes` (`id`, `user_id`, `post_id`, `created_at`) VALUES (3,1,1,'2025-03-04 22:44:42'),
(4,1,2,'2025-03-05 14:34:35'),
(5,3,4,'2025-03-05 16:58:51'),
(9,5,16,'2025-03-05 17:13:28'),
(11,3,11,'2025-03-05 17:56:11'),
(13,3,14,'2025-03-05 17:56:41'),
(17,3,12,'2025-03-05 19:09:48'),
(21,3,15,'2025-03-05 19:16:13'),
(23,7,16,'2025-03-05 20:10:02'),
(24,7,13,'2025-03-05 20:10:30'),
(25,1,15,'2025-03-05 21:07:52'),
(34,1,17,'2025-03-05 23:25:29'),
(37,1,13,'2025-03-05 23:31:51'),
(38,1,19,'2025-03-05 23:33:46'),
(45,6,13,'2025-03-06 01:05:42'),
(47,6,20,'2025-03-06 01:21:38'),
(49,1,20,'2025-03-06 02:15:58'),
(50,1,21,'2025-03-06 02:16:03'),
(51,1,16,'2025-03-06 02:16:08'),
(54,1,10,'2025-03-06 02:20:18'),
(58,1,5,'2025-03-06 02:23:49'),
(141,9,18,'2025-03-06 09:24:57'),
(191,2,20,'2025-03-06 10:03:36'),
(195,2,8,'2025-03-06 12:08:52'),
(202,7,21,'2025-03-06 12:30:49'),
(204,2,21,'2025-03-06 12:38:11'),
(208,2,22,'2025-03-06 12:45:21'),
(210,8,22,'2025-03-06 12:47:30'),
(213,1,7,'2025-03-07 04:17:03'),
(214,1,6,'2025-03-07 04:20:49');
/*!40000 ALTER TABLE `likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset`
--

DROP TABLE IF EXISTS `password_reset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `email` (`email`),
  KEY `token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset`
--

LOCK TABLES `password_reset` WRITE;
/*!40000 ALTER TABLE `password_reset` DISABLE KEYS */;
INSERT INTO `password_reset` (`id`, `user_id`, `email`, `token`, `expires`, `expires_at`, `created_at`) VALUES (1,2,'salwanettayumna@gmail.com','9effaa53b828238f7aaca4182751aba2e12947df7bec4c91cf9cfbc50ac4e0a1','2025-03-07 05:09:44','0000-00-00 00:00:00','2025-03-06 19:37:04'),
(2,2,'salwanettayumna@gmail.com','9effaa53b828238f7aaca4182751aba2e12947df7bec4c91cf9cfbc50ac4e0a1','2025-03-07 05:09:44','0000-00-00 00:00:00','2025-03-06 19:43:04'),
(3,2,'salwanettayumna@gmail.com','9effaa53b828238f7aaca4182751aba2e12947df7bec4c91cf9cfbc50ac4e0a1','2025-03-07 05:09:44','0000-00-00 00:00:00','2025-03-06 19:43:11'),
(5,13,'urushibaraurukako@gmail.com','1fe32283f15a52f038f1fa611d0bb4cc0ec99f76d5407947c17094a1b4bdc055','2025-03-10 22:54:00','0000-00-00 00:00:00','2025-03-10 14:54:00');
/*!40000 ALTER TABLE `password_reset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `media_type` enum('image','video','none') DEFAULT 'none',
  `media_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_posts_user_id` (`user_id`),
  KEY `idx_posts_user_content` (`user_id`,`content`(255)),
  FULLTEXT KEY `content_search` (`content`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` (`id`, `user_id`, `content`, `media_type`, `media_path`, `created_at`) VALUES (1,1,'halo','none',NULL,'2025-03-04 22:44:09'),
(2,1,'hai','none',NULL,'2025-03-05 14:34:34'),
(3,2,'Politics: A Reflection of Societyâ€™s Values Politics is not just about laws or policies; it reflects the values and priorities of a society. From social justice to economic policies, political decisions shape the everyday lives of millions. Whatâ€™s happening in politics today often reveals what people care about, what theyâ€™re fighting for, and what they hope to see in the future. Understanding this helps us engage more thoughtfully and advocate for a world that aligns with our collective values.','none',NULL,'2025-03-05 16:39:38'),
(4,2,'Travel: A Gateway to New Perspectives Travel isnâ€™t just about visiting new places; itâ€™s about experiencing different cultures, meeting new people, and gaining a broader understanding of the world. Whether itâ€™s a weekend getaway or a year-long adventure, each journey helps shape who we are, opening our minds to new perspectives. The more we travel, the more we realize how much there is to learn and explore','none',NULL,'2025-03-05 16:39:45'),
(5,2,'The Magic of Comfort Food Sometimes, the best way to lift your spirits is with a hearty plate of comfort food. Whether itâ€™s a bowl of creamy mac and cheese, a warm slice of pizza, or a bowl of homemade soup, comfort food has a way of making us feel cozy and content. Itâ€™s not just about the tasteâ€”itâ€™s about the memories and the feeling of being cared for. Food can be a little hug on a plate.','none',NULL,'2025-03-05 16:39:54'),
(6,2,'The Power of Lifelong Learning Education doesnâ€™t stop at graduation. In todayâ€™s fast-paced world, learning is a lifelong journey. Whether itâ€™s picking up a new skill, reading a book, or attending a workshop, continuing to educate ourselves opens up endless possibilities. Lifelong learning keeps our minds sharp, our curiosity alive, and helps us adapt to the ever-changing world around us.','none',NULL,'2025-03-05 16:40:02'),
(7,3,'Global Politics: The Interconnected World In our increasingly globalized world, politics is no longer confined to national borders. Decisions made in one country can have ripple effects across the globe. From trade agreements to climate change policies, international politics has the power to impact millions of lives. Understanding the interconnectedness of todayâ€™s political landscape is essential for grasping how global events shape local realities.','none',NULL,'2025-03-05 16:40:49'),
(8,3,'The Future of Travel: Sustainable Adventures As we look toward the future, sustainable travel is becoming more important than ever. Whether itâ€™s choosing eco-friendly accommodations, supporting local businesses, or reducing our carbon footprint, every traveler can play a part in protecting the planet. Sustainable travel isnâ€™t just about reducing harm; itâ€™s about making sure that the places we love to visit can continue to thrive for generations to come.','none',NULL,'2025-03-05 16:40:56'),
(9,3,'Sweet Cravings: The Irresistible Allure of Desserts Sometimes, the best part of a food is the sweet ending. Whether youâ€™re a fan of chocolate cake, a scoop of gelato, or a delicate pastry, desserts have a way of satisfying that sweet craving in the most delightful way. For some, itâ€™s the rich, decadent flavors; for others, itâ€™s the nostalgic taste that takes them back to childhood. No matter the reason, dessert is a universal joy.','none',NULL,'2025-03-05 16:41:04'),
(10,3,'The Importance of Critical Thinking in Education Education isnâ€™t just about memorizing factsâ€”itâ€™s about developing the ability to think critically. Critical thinking helps us analyze information, question assumptions, and make informed decisions. In a world full of noise and misinformation, the ability to think clearly and independently is one of the most valuable skills we can acquire through education.','none',NULL,'2025-03-05 16:41:11'),
(11,4,'Traveling is one of lifeâ€™s greatest adventures, offering the opportunity to explore new cultures, taste exotic foods, and create unforgettable memories. Whether you\'re hiking through breathtaking mountains, wandering through bustling city streets, or relaxing on a serene beach, each destination holds something unique. Travel broadens our perspectives, fosters personal growth, and helps us appreciate the beauty of the world. Every trip, no matter how big or small, leaves us with stories to tell and experiences that shape who we are.','none',NULL,'2025-03-05 17:02:18'),
(12,4,'One of the most enriching aspects of travel is the chance to immerse yourself in different cultures. You get to learn new languages, try diverse cuisines, and understand how people from all walks of life live. It\'s a reminder that the world is vast, and thereâ€™s so much more to discover beyond our own borders. Cultural exchange is a beautiful way to build connections and bridge gaps between people from different parts of the world.','none',NULL,'2025-03-05 17:02:30'),
(13,4,'Travel is about more than just visiting new places â€“ it\'s about creating memories that will last a lifetime. The laughter shared with friends on a road trip, the awe of seeing a landmark in person for the first time, or the serenity of watching the sunset on a quiet beach â€“ these moments are treasures. Travel helps us collect experiences that shape our stories, our identity, and how we see the world','none',NULL,'2025-03-05 17:02:39'),
(14,4,'Sometimes, the best part of travel is the escape it offers from daily routines and responsibilities. Itâ€™s a chance to recharge, reflect, and experience life from a different perspective. Whether you\'re escaping to a quiet cabin in the woods or a luxury resort on an island, travel provides a much-needed break from the hustle and bustle of everyday life, giving you the time and space to relax and refresh your mind.','none',NULL,'2025-03-05 17:02:47'),
(15,4,'Thereâ€™s something incredibly humbling about being surrounded by natureâ€™s beauty. From towering mountains to lush forests, clear lakes to endless deserts, the natural world offers awe-inspiring landscapes that leave us speechless. Traveling to places where nature thrives is not only a way to witness stunning scenery but also a reminder of the importance of preserving the environment for future generations.','none',NULL,'2025-03-05 17:02:58'),
(16,5,'nguenggg...','image','assets/uploads/images/1741169560_IMG_3487.JPG','2025-03-05 17:12:40'),
(17,8,'aw','image','assets/uploads/images/1741188565_Screenshot 2024-11-26 103924.png','2025-03-05 22:29:25'),
(18,8,'Like a fever \r\nLike I\'m burning alive \r\nLike a sign \r\nDid I cross the line?\r\n','none',NULL,'2025-03-05 22:32:24'),
(19,2,'help guys','image','assets/uploads/images/1741189466_Screenshot 2025-03-03 061113.png','2025-03-05 22:44:26'),
(20,1,'good night','none',NULL,'2025-03-05 23:20:18'),
(21,7,'putri mabok statistik','none',NULL,'2025-03-05 23:21:49'),
(22,9,'','image','assets/uploads/images/1741239889_IMG_3530 copy.JPG','2025-03-06 12:44:49'),
(23,8,'a','none',NULL,'2025-03-06 12:47:49'),
(24,6,'hi\r\n','none',NULL,'2025-03-06 13:20:47'),
(25,2,'halo','image','assets/uploads/images/1741242480_download.jpeg','2025-03-06 13:28:00'),
(26,6,'Hi','image','assets/uploads/images/1741400114_Screenshot 2024-10-28 190607.png','2025-03-08 09:15:14'),
(27,6,'Anime','image','assets/uploads/images/1741400884_Screenshot 2024-10-11 160519.png','2025-03-08 09:28:04'),
(28,12,'selling account \r\ndummy gmail\r\ndine âœ…\r\npm if interesting','image','assets/uploads/images/1741578117_Screenshot 2025-02-17 164925.png','2025-03-10 10:41:57'),
(30,15,'','image','assets/uploads/images/1741618681_Screenshot 2024-10-15 215026.png','2025-03-10 21:58:01'),
(31,15,'','image','assets/uploads/images/1741618968_Screenshot 2024-12-17 195039.png','2025-03-10 22:02:48'),
(32,13,'Finally arrived at Amphoreus','image','assets/uploads/images/1741619004_Picture1.png','2025-03-10 22:03:24'),
(34,15,'','image','assets/uploads/images/1741619032_Screenshot 2025-03-03 154406.png','2025-03-10 22:03:52'),
(35,13,'This fellow decided to tag along #Mem','image','assets/uploads/images/1741619367_Picture2.png','2025-03-10 22:09:27');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_interests`
--

DROP TABLE IF EXISTS `user_interests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_interests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `interest` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`interest`),
  KEY `idx_user_interests_user_id` (`user_id`),
  CONSTRAINT `user_interests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_interests`
--

LOCK TABLES `user_interests` WRITE;
/*!40000 ALTER TABLE `user_interests` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_interests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_preferences`
--

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `theme` enum('light','dark') DEFAULT 'light',
  `notification` enum('on','off') DEFAULT 'on',
  `language` varchar(10) DEFAULT 'en',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_preferences`
--

LOCK TABLES `user_preferences` WRITE;
/*!40000 ALTER TABLE `user_preferences` DISABLE KEYS */;
INSERT INTO `user_preferences` (`id`, `user_id`, `theme`, `notification`, `language`, `created_at`, `updated_at`) VALUES (1,6,'light','on','en','2025-03-08 09:07:34','2025-03-08 09:08:03'),
(2,2,'light','on','en','2025-03-08 13:42:06','2025-03-08 13:42:36'),
(3,14,'light','on','en','2025-03-10 22:06:05','0000-00-00 00:00:00');
/*!40000 ALTER TABLE `user_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT 'assets/images/default_profile.png',
  `bio` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_picture`, `bio`, `created_at`, `last_login`, `location`, `website`) VALUES (1,'mingyu','mingyuganteng@gmail.com','$2y$10$e8GEUMNIgSZ8Rpp9MfsIUu7UvXX8w8lgCqBIQAC2PKx8y8o3ITYwi','assets/images/default_profile.png',NULL,'2025-03-04 22:42:16','2025-03-07 04:51:30',NULL,NULL),
(2,'sana','salwanettayumna@gmail.com','$2y$10$7mW5xjavWY3LcuVIof5YqO0KfGjfgs32GzMLUxbD1R4rm7DBrjV5S','assets/uploads/images/1741195206_download.jpeg','(â—\'â—¡\'â—)','2025-03-05 16:38:42','2025-03-10 22:29:11',NULL,NULL),
(3,'linyi','linyi@gmail.com','$2y$10$tZpGESDsT7.lmnUcPW8P8uyC0tS8ZUTeYxFqBiaEP2TOhmnikLpz6','assets/images/default_profile.png',NULL,'2025-03-05 16:40:31','2025-03-05 17:03:19',NULL,NULL),
(4,'nana','nana@gmail.com','$2y$10$dQa1uKXJnGc0ThnyoezhCe77udeciDN0/QKvDiYRTFW7jt5LYxLgi','assets/images/default_profile.png',NULL,'2025-03-05 17:00:14','2025-03-05 17:02:03',NULL,NULL),
(5,'1234','aa@gmail.com','$2y$10$a2QM5QV4lzA4iYAx1wl4G.p2vq2dSYfou20c20dP16kQkzN0vfMW6','assets/images/default_profile.png',NULL,'2025-03-05 17:10:56','2025-03-05 17:11:26',NULL,NULL),
(6,'Onlynx','stevenelia101@gmail.com','$2y$10$13Rx0bUEbFwWrfxrzNcHC.XLfPwCl/hBVIIttpkFysclOsDop2lT6','assets/uploads/images/1741230506_Screenshot 2025-02-17 165049.png','Weeb','2025-03-05 18:12:42','2025-03-08 09:05:10','',''),
(7,'kamisama','kami@gmail.com','$2y$10$XPUtcv8F/QH5h5n4Ixohk.XSHw9/.m1aMp.0VSrj3iSFlnQDc6tLu','assets/images/default_profile.png',NULL,'2025-03-05 20:09:06','2025-03-06 12:59:38',NULL,NULL),
(8,'canablu','yaudah@gmail.com','$2y$10$kC3rz66y50eQMVnXuUHdwedd4J970X6KBvRFZsVRsFSCTQDBc4E0S','assets/uploads/images/1741201826_Screenshot 2024-11-17 134133.png','','2025-03-05 22:28:23','2025-03-06 12:47:26',NULL,NULL),
(9,'12344','aaa@gmail.com','$2y$10$phv45ZmPVGrYJNhWqkR/reBA5upUqh2pF.dv5pkwQd4AiZOJvQZAy','assets/images/default_profile.png',NULL,'2025-03-06 09:24:00','2025-03-06 12:44:04',NULL,NULL),
(10,'halo','halo@gmail.com','$2y$10$B2oSXsOgkQV0bHcbbfWkz.aHOBHP4a796vut8z80T811BOtGNHxYC','assets/images/default_profile.png',NULL,'2025-03-07 01:48:44','2025-03-07 01:49:15',NULL,NULL),
(11,'sasa','salwanetta.yumna@student.president.ac.id','$2y$10$2F942F8ob.CJNWAKrEKyq.DbkokUyB95.CevnoDPX.MO5mfxA9tze','assets/images/default_profile.png',NULL,'2025-03-07 02:52:59','2025-03-07 03:07:00',NULL,NULL),
(12,'epep','adventurecreature99@gmail.com','$2y$10$Rx.gikj2F7u4LUWPS6nBa.PpJxbGlMbIhpsBAcUtK3Ekw174j/T/G','assets/uploads/images/1741577838_Screenshot 2025-02-17 164925.png','black desert mobile player','2025-03-10 10:35:46','2025-03-10 10:35:56',NULL,NULL),
(13,'GalacticBaseballer','urushibaraurukako@gmail.com','$2y$10$/IHHbLHmPTEu9YaeTrCSBeWR8pPnYlZHzv410FneZEezML25UOkm6','assets/uploads/images/1741618820_trailblazer-destruction.png','Your trailblaizing companion','2025-03-10 21:53:31','2025-03-10 21:55:58',NULL,NULL),
(14,'Link','stephaniequte@gmail.com','$2y$10$2LSslUXGqfSX9GOLiVaFhuotjPknCTJlCJNWM6wT2H8nSVf9Nhim.','assets/images/default_profile.png',NULL,'2025-03-10 21:53:58','2025-03-10 21:54:24',NULL,NULL),
(15,'stipi','stipi123@gmail.com','$2y$10$g0AIZunuc5uEVnin2RLWXemgStYoyypCosy1XkMVqT9uB9E1PmBTe','assets/uploads/images/1741618737_Screenshot 2024-11-06 212515.png','','2025-03-10 21:54:17','2025-03-10 21:54:50',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'qxpanuto_mid'
--

--
-- Dumping routines for database 'qxpanuto_mid'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `InsertSampleData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertSampleData`()
BEGIN
    -- Insert sample users
    INSERT INTO users (username, email, password_hash) VALUES 
    ('john_doe', 'john@example.com', '$2y$10$randomhashhere'),
    ('jane_smith', 'jane@example.com', '$2y$10$anotherhashhere');

    -- Insert sample posts
    INSERT INTO posts (user_id, content, tags) VALUES 
    (1, 'First post about technology and innovation', 'tech,innovation'),
    (1, 'Exploring the world of artificial intelligence', 'ai,machine-learning'),
    (2, 'My thoughts on sustainable development', 'environment,sustainability');

    -- Insert sample likes
    INSERT INTO likes (user_id, post_id) VALUES 
    (1, 3),
    (2, 1),
    (2, 2);

    -- Insert sample comments
    INSERT INTO comments (user_id, post_id, content) VALUES 
    (2, 1, 'Great post about technology!'),
    (1, 3, 'Interesting perspective on sustainability');
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-03-10 23:23:16
