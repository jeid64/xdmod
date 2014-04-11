
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
DROP TABLE IF EXISTS `shredded_job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shredded_job` (
  `shredded_job_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_format` enum('pbs','sge','slurm') NOT NULL,
  `date_key` date NOT NULL,
  `job_id` int(10) unsigned NOT NULL,
  `job_array_index` int(10) unsigned DEFAULT NULL,
  `job_name` varchar(255) DEFAULT NULL,
  `cluster_name` varchar(255) DEFAULT NULL,
  `queue_name` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL DEFAULT 'Unknown',
  `project_name` varchar(255) NOT NULL DEFAULT 'Unknown',
  `start_time` int(10) unsigned NOT NULL,
  `end_time` int(10) unsigned NOT NULL,
  `submission_time` int(10) unsigned NOT NULL,
  `wallt` bigint(20) unsigned NOT NULL,
  `cput` bigint(20) unsigned DEFAULT NULL,
  `mem` bigint(20) unsigned DEFAULT NULL,
  `vmem` bigint(20) unsigned DEFAULT NULL,
  `wait` bigint(20) unsigned NOT NULL,
  `exect` bigint(20) unsigned NOT NULL,
  `nodes` int(10) unsigned NOT NULL,
  `cpus` int(10) unsigned NOT NULL,
  PRIMARY KEY (`shredded_job_id`),
  KEY `source` (`source_format`,`cluster_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shredded_job_pbs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shredded_job_pbs` (
  `shredded_job_pbs_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `job_array_index` int(10) NOT NULL DEFAULT '-1',
  `host` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `user` varchar(255) NOT NULL,
  `groupname` varchar(255) NOT NULL,
  `ctime` int(11) NOT NULL,
  `qtime` int(11) NOT NULL,
  `start` int(11) NOT NULL,
  `end` int(11) NOT NULL,
  `etime` int(11) NOT NULL,
  `exit_status` int(11) DEFAULT NULL,
  `session` int(10) unsigned DEFAULT NULL,
  `requestor` varchar(255) DEFAULT NULL,
  `jobname` varchar(255) DEFAULT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `account` varchar(255) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `error_path` varchar(255) DEFAULT NULL,
  `output_path` varchar(255) DEFAULT NULL,
  `exec_host` text,
  `resources_used_vmem` bigint(20) unsigned DEFAULT NULL,
  `resources_used_mem` bigint(20) unsigned DEFAULT NULL,
  `resources_used_walltime` bigint(20) unsigned DEFAULT NULL,
  `resources_used_nodes` int(10) unsigned DEFAULT NULL,
  `resources_used_cpus` int(10) unsigned DEFAULT NULL,
  `resources_used_cput` bigint(20) unsigned DEFAULT NULL,
  `resource_list_nodes` text,
  `resource_list_procs` text,
  `resource_list_neednodes` text,
  `resource_list_pcput` bigint(20) unsigned DEFAULT NULL,
  `resource_list_cput` bigint(20) unsigned DEFAULT NULL,
  `resource_list_walltime` bigint(20) unsigned DEFAULT NULL,
  `resource_list_ncpus` tinyint(3) unsigned DEFAULT NULL,
  `resource_list_nodect` int(10) unsigned DEFAULT NULL,
  `resource_list_mem` bigint(20) unsigned DEFAULT NULL,
  `resource_list_pmem` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`shredded_job_pbs_id`),
  KEY `job` (`host`,`job_id`,`job_array_index`,`ctime`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shredded_job_sge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shredded_job_sge` (
  `shredded_job_sge_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clustername` varchar(255) DEFAULT NULL,
  `qname` varchar(255) DEFAULT NULL,
  `hostname` varchar(255) NOT NULL,
  `groupname` varchar(255) DEFAULT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `job_name` varchar(255) DEFAULT NULL,
  `job_number` int(10) unsigned NOT NULL,
  `account` varchar(255) DEFAULT NULL,
  `priority` tinyint(4) DEFAULT NULL,
  `submission_time` int(11) unsigned DEFAULT NULL,
  `start_time` int(11) unsigned DEFAULT NULL,
  `end_time` int(11) unsigned DEFAULT NULL,
  `failed` int(11) DEFAULT NULL,
  `exit_status` int(11) DEFAULT NULL,
  `ru_wallclock` int(11) DEFAULT NULL,
  `ru_utime` decimal(32,6) DEFAULT NULL,
  `ru_stime` decimal(32,6) DEFAULT NULL,
  `ru_maxrss` int(11) DEFAULT NULL,
  `ru_ixrss` int(11) DEFAULT NULL,
  `ru_ismrss` int(11) DEFAULT NULL,
  `ru_idrss` int(11) DEFAULT NULL,
  `ru_isrss` int(11) DEFAULT NULL,
  `ru_minflt` int(11) DEFAULT NULL,
  `ru_majflt` int(11) DEFAULT NULL,
  `ru_nswap` int(11) DEFAULT NULL,
  `ru_inblock` int(11) DEFAULT NULL,
  `ru_oublock` int(11) DEFAULT NULL,
  `ru_msgsnd` int(11) DEFAULT NULL,
  `ru_msgrcv` int(11) DEFAULT NULL,
  `ru_nsignals` int(11) DEFAULT NULL,
  `ru_nvcsw` int(11) DEFAULT NULL,
  `ru_nivcsw` int(11) DEFAULT NULL,
  `project` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `granted_pe` varchar(255) DEFAULT NULL,
  `slots` int(11) DEFAULT NULL,
  `task_number` int(11) DEFAULT NULL,
  `cpu` decimal(32,6) DEFAULT NULL,
  `mem` decimal(32,6) DEFAULT NULL,
  `io` decimal(32,6) DEFAULT NULL,
  `category` text,
  `iow` decimal(32,6) DEFAULT NULL,
  `pe_taskid` int(11) DEFAULT NULL,
  `maxvmem` bigint(20) DEFAULT NULL,
  `arid` int(11) DEFAULT NULL,
  `ar_submission_time` int(11) unsigned DEFAULT NULL,
  `resource_list_arch` varchar(255) DEFAULT NULL,
  `resource_list_qname` varchar(255) DEFAULT NULL,
  `resource_list_hostname` varchar(255) DEFAULT NULL,
  `resource_list_notify` int(11) DEFAULT NULL,
  `resource_list_calendar` varchar(255) DEFAULT NULL,
  `resource_list_min_cpu_interval` int(11) DEFAULT NULL,
  `resource_list_tmpdir` varchar(255) DEFAULT NULL,
  `resource_list_seq_no` int(11) DEFAULT NULL,
  `resource_list_s_rt` bigint(20) DEFAULT NULL,
  `resource_list_h_rt` bigint(20) DEFAULT NULL,
  `resource_list_s_cpu` bigint(20) DEFAULT NULL,
  `resource_list_h_cpu` bigint(20) DEFAULT NULL,
  `resource_list_s_data` bigint(20) DEFAULT NULL,
  `resource_list_h_data` bigint(20) DEFAULT NULL,
  `resource_list_s_stack` bigint(20) DEFAULT NULL,
  `resource_list_h_stack` bigint(20) DEFAULT NULL,
  `resource_list_s_core` bigint(20) DEFAULT NULL,
  `resource_list_h_core` bigint(20) DEFAULT NULL,
  `resource_list_s_rss` bigint(20) DEFAULT NULL,
  `resource_list_h_rss` bigint(20) DEFAULT NULL,
  `resource_list_slots` varchar(255) DEFAULT NULL,
  `resource_list_s_vmem` bigint(20) DEFAULT NULL,
  `resource_list_h_vmem` bigint(20) DEFAULT NULL,
  `resource_list_s_fsize` bigint(20) DEFAULT NULL,
  `resource_list_h_fsize` bigint(20) DEFAULT NULL,
  `resource_list_num_proc` int(11) DEFAULT NULL,
  `resource_list_mem_free` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`shredded_job_sge_id`),
  UNIQUE KEY `job` (`hostname`,`job_number`,`task_number`,`failed`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shredded_job_slurm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shredded_job_slurm` (
  `shredded_job_slurm_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `job_name` tinytext NOT NULL,
  `cluster_name` tinytext NOT NULL,
  `partition_name` tinytext NOT NULL,
  `user_name` tinytext NOT NULL,
  `group_name` tinytext NOT NULL,
  `account_name` tinytext NOT NULL,
  `submit_time` int(10) unsigned NOT NULL,
  `eligible_time` int(10) unsigned NOT NULL,
  `start_time` int(10) unsigned NOT NULL,
  `end_time` int(10) unsigned NOT NULL,
  `elapsed` int(10) unsigned NOT NULL,
  `exit_code` tinytext NOT NULL,
  `nnodes` int(10) unsigned NOT NULL,
  `ncpus` int(10) unsigned NOT NULL,
  `node_list` text NOT NULL,
  PRIMARY KEY (`shredded_job_slurm_id`),
  UNIQUE KEY `job` (`cluster_name`(20),`job_id`,`submit_time`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staging_cluster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staging_cluster` (
  `cluster_id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_name` varchar(255) NOT NULL,
  PRIMARY KEY (`cluster_id`),
  UNIQUE KEY `cluster_name` (`cluster_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staging_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staging_group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) NOT NULL,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `group_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staging_group_cluster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staging_group_cluster` (
  `group_cluster_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) NOT NULL,
  `cluster_name` varchar(255) NOT NULL,
  PRIMARY KEY (`group_cluster_id`),
  UNIQUE KEY `group_cluster_name` (`group_name`,`cluster_name`),
  KEY `group_name` (`group_name`),
  KEY `cluster_name` (`cluster_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staging_job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staging_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `job_array_index` int(10) unsigned DEFAULT NULL,
  `job_name` varchar(255) DEFAULT NULL,
  `cluster_name` varchar(255) NOT NULL,
  `queue_name` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `start_time` int(10) unsigned NOT NULL,
  `end_time` int(10) unsigned NOT NULL,
  `submission_time` int(10) unsigned NOT NULL,
  `wallt` bigint(20) unsigned NOT NULL,
  `cput` bigint(20) unsigned NOT NULL,
  `mem` bigint(20) unsigned NOT NULL,
  `vmem` bigint(20) unsigned NOT NULL,
  `wait` bigint(20) unsigned NOT NULL,
  `exect` bigint(20) unsigned NOT NULL,
  `nodes` int(10) unsigned NOT NULL,
  `cpus` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staging_union_user_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staging_union_user_group` (
  `union_user_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `union_user_group_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`union_user_group_id`),
  UNIQUE KEY `union_user_group_name` (`union_user_group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staging_union_user_group_cluster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staging_union_user_group_cluster` (
  `union_user_group_cluster_id` int(11) NOT NULL AUTO_INCREMENT,
  `union_user_group_name` varchar(255) NOT NULL,
  `cluster_name` varchar(255) NOT NULL,
  PRIMARY KEY (`union_user_group_cluster_id`),
  UNIQUE KEY `union_user_group_cluster_name` (`union_user_group_name`,`cluster_name`),
  KEY `union_user_group_name` (`union_user_group_name`),
  KEY `cluster_name` (`cluster_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staging_user_group_cluster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staging_user_group_cluster` (
  `user_group_cluster_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) NOT NULL DEFAULT '',
  `group_name` varchar(255) NOT NULL DEFAULT '',
  `cluster_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_group_cluster_id`),
  UNIQUE KEY `user_group_cluster` (`user_name`,`group_name`,`cluster_name`),
  KEY `user_name` (`user_name`),
  KEY `group_name` (`group_name`),
  KEY `cluster_name` (`cluster_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

