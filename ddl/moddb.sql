
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `APIKeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `APIKeys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key` varchar(16) DEFAULT NULL,
  `public_key` text,
  `identifier` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `APIKeys` WRITE;
/*!40000 ALTER TABLE `APIKeys` DISABLE KEYS */;
/*!40000 ALTER TABLE `APIKeys` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `AccountRequests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AccountRequests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` text,
  `last_name` text,
  `organization` text,
  `title` text,
  `email_address` text,
  `field_of_science` text,
  `additional_information` text,
  `time_submitted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` varchar(100) DEFAULT NULL,
  `comments` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `AccountRequests` WRITE;
/*!40000 ALTER TABLE `AccountRequests` DISABLE KEYS */;
/*!40000 ALTER TABLE `AccountRequests` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ChartPool`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ChartPool` (
  `user_id` int(11) DEFAULT NULL,
  `chart_id` text,
  `insertion_rank` int(11) NOT NULL AUTO_INCREMENT,
  `chart_title` text,
  `chart_drill_details` text NOT NULL,
  `chart_date_description` text,
  `type` enum('image','datasheet') DEFAULT NULL,
  `active_role` varchar(30) DEFAULT NULL,
  `image_data` longblob,
  PRIMARY KEY (`insertion_rank`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ChartPool` WRITE;
/*!40000 ALTER TABLE `ChartPool` DISABLE KEYS */;
/*!40000 ALTER TABLE `ChartPool` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `Colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Colors` (
  `color` char(8) NOT NULL,
  `description` varchar(45) DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  PRIMARY KEY (`color`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
/*!50100 PARTITION BY KEY (color)
PARTITIONS 2 */;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `Colors` WRITE;
/*!40000 ALTER TABLE `Colors` DISABLE KEYS */;
INSERT INTO `Colors` VALUES ('0x33abab','',-2),('0x4e665D',NULL,2),('0x69bbed','blue',-400),('0x99abab','powder blue',-196),('0xDB4230',NULL,3),('0x00CCFF','',-7),('0x1199FF','blueish',4),('0x339966','green',-187),('0x33FF33','green',-200),('0x3aaaaa','green',-192),('0x6666FF','purple',-199),('0x669999','ligh blue',-189),('0x66FF00',NULL,-1),('0x789abc','',-5),('0x8D4DFF','purple',-10),('0x990099','purple',-186),('0x993333','red-brown',-188),('0x999900','yellow-green',-191),('0x9999FF',NULL,-184),('0x99FF99',NULL,-185),('0xA57E81','',-9),('0xa88d95','yello',-4),('0xab6565','organish-pink',-193),('0xab8722','yellowish-creamish',-195),('0xCC3300','orange',-190),('0xCC6600',NULL,-183),('0xCC99FF',NULL,-182),('0xF4A221',NULL,1),('0xFF6666',NULL,-181),('0xFF66FF','pinkish-purple',-197),('0xFF9966','orange',-185),('0xFF99CC','',-6),('0xFFBC71','',-8);
/*!40000 ALTER TABLE `Colors` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ExceptionEmailAddresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ExceptionEmailAddresses` (
  `email_address` varchar(200) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ExceptionEmailAddresses` WRITE;
/*!40000 ALTER TABLE `ExceptionEmailAddresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `ExceptionEmailAddresses` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `Glossary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Glossary` (
  `term` text,
  `definition` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `Glossary` WRITE;
/*!40000 ALTER TABLE `Glossary` DISABLE KEYS */;
/*!40000 ALTER TABLE `Glossary` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `GlossaryAliases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GlossaryAliases` (
  `term` text,
  `alias` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `GlossaryAliases` WRITE;
/*!40000 ALTER TABLE `GlossaryAliases` DISABLE KEYS */;
/*!40000 ALTER TABLE `GlossaryAliases` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `RESTx509`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RESTx509` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `distinguished_name` text,
  `api_key` varchar(100) DEFAULT NULL,
  `description` text,
  `time_cert_signed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `RESTx509` WRITE;
/*!40000 ALTER TABLE `RESTx509` DISABLE KEYS */;
/*!40000 ALTER TABLE `RESTx509` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ReportCharts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ReportCharts` (
  `report_id` varchar(100) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `chart_type` text,
  `type` enum('image','datasheet') DEFAULT NULL,
  `selected` tinyint(1) DEFAULT '0',
  `chart_id` text,
  `ordering` int(11) NOT NULL DEFAULT '0',
  `chart_date_description` text,
  `chart_title` varchar(100) DEFAULT NULL,
  `chart_drill_details` text NOT NULL,
  `timeframe_type` text,
  `image_data` longblob
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ReportCharts` WRITE;
/*!40000 ALTER TABLE `ReportCharts` DISABLE KEYS */;
/*!40000 ALTER TABLE `ReportCharts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ReportTemplateACL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ReportTemplateACL` (
  `template_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ReportTemplateACL` WRITE;
/*!40000 ALTER TABLE `ReportTemplateACL` DISABLE KEYS */;
/*!40000 ALTER TABLE `ReportTemplateACL` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ReportTemplateCharts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ReportTemplateCharts` (
  `template_id` int(11) DEFAULT NULL,
  `chart_id` text,
  `ordering` int(11) DEFAULT NULL,
  `chart_date_description` text,
  `chart_title` varchar(100) DEFAULT NULL,
  `chart_drill_details` text,
  `timeframe_type` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ReportTemplateCharts` WRITE;
/*!40000 ALTER TABLE `ReportTemplateCharts` DISABLE KEYS */;
/*!40000 ALTER TABLE `ReportTemplateCharts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ReportTemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ReportTemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(1000) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `template` varchar(30) DEFAULT NULL,
  `title` varchar(1000) DEFAULT NULL,
  `header` varchar(1000) DEFAULT NULL,
  `footer` varchar(1000) DEFAULT NULL,
  `format` enum('Pdf','Pptx','Doc','Xls','Html') DEFAULT NULL,
  `font` enum('Times','Arial') DEFAULT NULL,
  `schedule` enum('Once','Daily','Weekly','Monthly','Quarterly','Semi-annually','Annually') DEFAULT NULL,
  `delivery` enum('Download','E-mail') DEFAULT NULL,
  `charts_per_page` int(1) DEFAULT NULL,
  `use_submenu` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ReportTemplates` WRITE;
/*!40000 ALTER TABLE `ReportTemplates` DISABLE KEYS */;
/*!40000 ALTER TABLE `ReportTemplates` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `Reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Reports` (
  `report_id` varchar(100) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(1000) DEFAULT 'TAS Report',
  `derived_from` varchar(1000) DEFAULT NULL,
  `title` varchar(1000) DEFAULT 'TAS Report',
  `header` varchar(1000) DEFAULT NULL,
  `footer` varchar(1000) DEFAULT NULL,
  `format` enum('Pdf','Pptx','Doc','Xls','Html') NOT NULL DEFAULT 'Pdf',
  `font` enum('Times','Arial') NOT NULL DEFAULT 'Arial',
  `schedule` enum('Once','Daily','Weekly','Monthly','Quarterly','Semi-annually','Annually') NOT NULL DEFAULT 'Once',
  `delivery` enum('Download','E-mail') NOT NULL DEFAULT 'E-mail',
  `selected` tinyint(1) NOT NULL DEFAULT '0',
  `charts_per_page` int(1) DEFAULT NULL,
  `active_role` varchar(30) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `Reports` WRITE;
/*!40000 ALTER TABLE `Reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `Reports` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `Roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Roles` (
  `role_id` int(4) NOT NULL DEFAULT '0',
  `abbrev` varchar(20) NOT NULL,
  `description` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `Roles` WRITE;
/*!40000 ALTER TABLE `Roles` DISABLE KEYS */;
INSERT INTO `Roles` VALUES (0,'mgr','Manager'),(1,'cd','Center Director'),(3,'usr','User'),(4,'pi','Principal Investigator'),(5,'cs','Center Staff'),(7,'dev','Developer');
/*!40000 ALTER TABLE `Roles` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `SessionManager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SessionManager` (
  `session_token` varchar(40) NOT NULL,
  `session_id` text NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `ip_address` varchar(40) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `init_time` varchar(100) NOT NULL,
  `last_active` varchar(100) NOT NULL,
  `used_logout` tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`session_token`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `SessionManager` WRITE;
/*!40000 ALTER TABLE `SessionManager` DISABLE KEYS */;
/*!40000 ALTER TABLE `SessionManager` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `UserProfiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserProfiles` (
  `user_id` int(11) NOT NULL DEFAULT '0',
  `serialized_profile_data` longblob,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `UserProfiles` WRITE;
/*!40000 ALTER TABLE `UserProfiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserProfiles` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `UserRoleParameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserRoleParameters` (
  `user_id` int(11) DEFAULT NULL,
  `role_id` int(4) DEFAULT NULL,
  `param_name` varchar(255) DEFAULT NULL,
  `param_op` enum('>','>=','=','<','<=','IS','IS NOT','IN') DEFAULT NULL,
  `param_value` varchar(1024) DEFAULT NULL,
  `is_primary` int(11) DEFAULT NULL,
  `is_active` int(11) DEFAULT NULL,
  `promoter` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `UserRoleParameters` WRITE;
/*!40000 ALTER TABLE `UserRoleParameters` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserRoleParameters` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `UserRoles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserRoles` (
  `user_id` int(11) NOT NULL DEFAULT '0',
  `role_id` int(4) NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `UserRoles__ibfk_2` (`role_id`),
  CONSTRAINT `UserRoles__ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `UserRoles__ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `Roles` (`role_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `UserRoles` WRITE;
/*!40000 ALTER TABLE `UserRoles` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserRoles` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `UserTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserTypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  `color` char(7) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `UserTypes` WRITE;
/*!40000 ALTER TABLE `UserTypes` DISABLE KEYS */;
INSERT INTO `UserTypes` VALUES (1,'External','#000000'),(2,'Internal','#0000ff'),(3,'Testing','#008800');
/*!40000 ALTER TABLE `UserTypes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `Users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(200) DEFAULT NULL,
  `password` char(32) DEFAULT NULL,
  `email_address` varchar(200) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT '',
  `last_name` varchar(50) DEFAULT NULL,
  `time_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `time_last_updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `password_last_updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `account_is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `person_id` int(11) DEFAULT NULL COMMENT 'references TGcDB.people.person_id',
  `organization_id` int(11) DEFAULT NULL COMMENT 'references TGcDB.organizations.organization_id',
  `field_of_science` int(11) DEFAULT NULL,
  `token` varchar(32) DEFAULT NULL,
  `token_expiration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `parent_user_id` int(11) NOT NULL,
  `user_type` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `person_id_idx` (`person_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `Users` WRITE;
/*!40000 ALTER TABLE `Users` DISABLE KEYS */;
/*!40000 ALTER TABLE `Users` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `users_insert_timestamp` BEFORE INSERT ON `Users`
 FOR EACH ROW begin

	SET NEW.time_created = now();

	SET NEW.time_last_updated = now();

end */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `users_update_timestamp` BEFORE UPDATE ON `Users`
 FOR EACH ROW begin 

	if NEW.password <> OLD.password then 

		SET NEW.password_last_updated = now(); 

	end if; 

	SET NEW.time_last_updated = now();

end */;;
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

