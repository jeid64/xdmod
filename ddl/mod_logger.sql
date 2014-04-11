
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
DROP TABLE IF EXISTS `log_id_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_id_seq` (
  `sequence` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`sequence`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `log_id_seq` WRITE;
/*!40000 ALTER TABLE `log_id_seq` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_id_seq` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `log_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_level` (
  `log_level_id` smallint(6) NOT NULL,
  `level` varchar(16) NOT NULL,
  `description` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`log_level_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `log_level` WRITE;
/*!40000 ALTER TABLE `log_level` DISABLE KEYS */;
INSERT INTO `log_level` VALUES (0,'EMERG','System is unusable'),(1,'ALERT','Immediate action required'),(2,'CRIT','Critical conditions'),(3,'ERR','Error conditions'),(4,'WARNING','Warning conditions'),(5,'NOTICE','Normal but significant'),(6,'INFO','Informational'),(7,'DEBUG','Debug-level message');
/*!40000 ALTER TABLE `log_level` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `log_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_table` (
  `id` int(11) DEFAULT NULL,
  `logtime` datetime DEFAULT NULL,
  `ident` text,
  `priority` text,
  `message` longtext,
  KEY `unique_id_idx` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `log_table` WRITE;
/*!40000 ALTER TABLE `log_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_table` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

