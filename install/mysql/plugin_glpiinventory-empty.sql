--
-- ---------------------------------------------------------------------
-- GLPI Inventory Plugin
-- Copyright (C) 2021 Teclib' and contributors.
--
-- http://glpi-project.org
--
-- based on FusionInventory for GLPI
-- Copyright (C) 2010-2021 by the FusionInventory Development Team.
--
-- ---------------------------------------------------------------------
--
-- LICENSE
--
-- This file is part of GLPI Inventory Plugin.
--
-- GLPI Inventory Plugin is free software: you can redistribute it and/or modify
-- it under the terms of the GNU Affero General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
-- GNU Affero General Public License for more details.
--
-- You should have received a copy of the GNU Affero General Public License
-- along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
-- ---------------------------------------------------------------------
--

-- obsolete tables
DROP TABLE IF EXISTS `glpi_dropdown_plugin_glpiinventory_snmp_auth_auth_protocol`;
DROP TABLE IF EXISTS `glpi_dropdown_plugin_glpiinventory_snmp_auth_priv_protocol`;
DROP TABLE IF EXISTS `glpi_dropdown_plugin_glpiinventory_snmp_version`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_agents_errors`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_agents_inventory_state`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_agentprocesses`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_computers`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_config_modules`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_config_snmp_networking`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_connection_history`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_connection_stats`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_discovery`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_errors`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_unknown_mac`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_walks`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_lockable`;

-- renamed tables
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_config`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_config_modules`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_lock`;
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_task`;
-- DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_unknown_device`;

DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_configs`;

CREATE TABLE `glpi_plugin_glpiinventory_configs` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `type` varchar(255) DEFAULT NULL,
   `value` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `unicity` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_tasks`;

CREATE TABLE `glpi_plugin_glpiinventory_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `datetime_start` timestamp NULL DEFAULT NULL,
  `datetime_end` timestamp NULL DEFAULT NULL,
  `plugin_glpiinventory_timeslots_prep_id` int(11) NOT NULL DEFAULT '0',
  `plugin_glpiinventory_timeslots_exec_id` int(11) NOT NULL DEFAULT '0',
  `last_agent_wakeup` timestamp NULL DEFAULT NULL,
  `wakeup_agent_counter` int(11) NOT NULL DEFAULT '0',
  `wakeup_agent_time` int(11) NOT NULL DEFAULT '0',
  `reprepare_if_successful` tinyint(1) NOT NULL DEFAULT '1',
  `is_deploy_on_demand` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `plugin_glpiinventory_timeslots_prep_id` (`plugin_glpiinventory_timeslots_prep_id`),
  KEY `plugin_glpiinventory_timeslots_exec_id` (`plugin_glpiinventory_timeslots_exec_id`),
  KEY `is_active` (`is_active`),
  KEY `reprepare_if_successful` (`reprepare_if_successful`),
  KEY `is_deploy_on_demand` (`is_deploy_on_demand`),
  KEY `wakeup_agent_counter` (`wakeup_agent_counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_taskjobs`;

CREATE TABLE `glpi_plugin_glpiinventory_taskjobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_tasks_id` int(11) NOT NULL DEFAULT '0',
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `method` varchar(255) DEFAULT NULL,
  `targets` text DEFAULT NULL,
  `actors` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `rescheduled_taskjob_id` int(11) NOT NULL DEFAULT '0',
  `statuscomments` text DEFAULT NULL,
  `enduser` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_tasks_id` (`plugin_glpiinventory_tasks_id`),
  KEY `entities_id` (`entities_id`),
  KEY `method` (`method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_taskjoblogs`;

CREATE TABLE `glpi_plugin_glpiinventory_taskjoblogs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_taskjobstates_id` int(11) NOT NULL DEFAULT '0',
  `date` timestamp NULL DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT '0',
  `itemtype` varchar(100) DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT '0',
  `comment` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_taskjobstates_id` (`plugin_glpiinventory_taskjobstates_id`,`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_taskjobstates`;

CREATE TABLE `glpi_plugin_glpiinventory_taskjobstates` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_taskjobs_id` int(11) NOT NULL DEFAULT '0',
  `items_id` int(11) NOT NULL DEFAULT '0',
  `itemtype` varchar(100) DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT '0',
  `agents_id` int(11) NOT NULL DEFAULT '0',
  `specificity` text DEFAULT NULL,
  `uniqid` varchar(255) DEFAULT NULL,
  `date_start` timestamp NULL DEFAULT NULL,
  `nb_retry` int(11) NOT NULL DEFAULT '0',
  `max_retry` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_taskjobs_id` (`plugin_glpiinventory_taskjobs_id`),
  KEY `agents_id` (`agents_id`),
  KEY `uniqid` (`uniqid`,`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_agentmodules`;

CREATE TABLE `glpi_plugin_glpiinventory_agentmodules` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `modulename` varchar(255) DEFAULT NULL,
   `is_active` tinyint(1) NOT NULL DEFAULT '0',
   `exceptions` text DEFAULT NULL COMMENT 'array(agent_id)',
   PRIMARY KEY (`id`),
   UNIQUE KEY `modulename` (`modulename`),
   KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_ipranges`;

CREATE TABLE `glpi_plugin_glpiinventory_ipranges` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `name` varchar(255) DEFAULT NULL,
   `entities_id` int(11) NOT NULL DEFAULT '0',
   `ip_start` varchar(255) DEFAULT NULL,
   `ip_end` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_ipranges_snmpcredentials`;

CREATE TABLE `glpi_plugin_glpiinventory_ipranges_snmpcredentials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_ipranges_id` int(11) NOT NULL DEFAULT '0',
  `snmpcredentials_id` int(11) NOT NULL DEFAULT '0',
  `rank` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `unicity` (`plugin_glpiinventory_ipranges_id`,`snmpcredentials_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_credentials`;

CREATE TABLE  `glpi_plugin_glpiinventory_credentials` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `entities_id` int(11) NOT NULL DEFAULT '0',
   `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
   `name` varchar(255) NOT NULL DEFAULT '',
   `username` varchar(255) NOT NULL DEFAULT '',
   `password` varchar(255) NOT NULL DEFAULT '',
   `comment` text DEFAULT NULL,
   `date_mod` timestamp NULL DEFAULT NULL,
   `itemtype` varchar(255) NOT NULL DEFAULT '',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_credentialips`;

CREATE TABLE  `glpi_plugin_glpiinventory_credentialips` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `entities_id` int(11) NOT NULL DEFAULT '0',
   `plugin_glpiinventory_credentials_id` int(11) NOT NULL DEFAULT '0',
   `name` varchar(255) NOT NULL DEFAULT '',
   `comment` text DEFAULT NULL,
   `ip` varchar(255) NOT NULL DEFAULT '',
   `date_mod` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_inventorycomputercriterias`;

CREATE TABLE `glpi_plugin_glpiinventory_inventorycomputercriterias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_inventorycomputerblacklists`;

CREATE TABLE `glpi_plugin_glpiinventory_inventorycomputerblacklists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_criterium_id` int(11) NOT NULL DEFAULT '0',
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_criterium_id` (`plugin_glpiinventory_criterium_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;




DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_inventorycomputercomputers`;

CREATE TABLE `glpi_plugin_glpiinventory_inventorycomputercomputers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `operatingsystem_installationdate` timestamp NULL DEFAULT NULL,
  `winowner` varchar(255) DEFAULT NULL,
  `wincompany` varchar(255) DEFAULT NULL,
  `last_inventory_update` timestamp NULL DEFAULT NULL,
  `remote_addr` varchar(255) DEFAULT NULL,
  `serialized_inventory` longblob DEFAULT NULL,
  `is_entitylocked` tinyint(1) NOT NULL DEFAULT '0',
  `oscomment` text DEFAULT NULL,
  `hostid` varchar(255) DEFAULT NULL,
  `last_boot` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `last_inventory_update` (`last_inventory_update`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_inventorycomputerstats`;

CREATE TABLE `glpi_plugin_glpiinventory_inventorycomputerstats` (
 `id` smallint(3) NOT NULL AUTO_INCREMENT,
 `day` smallint(3) NOT NULL DEFAULT '0',
 `hour` tinyint(2) NOT NULL DEFAULT '0',
 `counter` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_statediscoveries`;

CREATE TABLE `glpi_plugin_glpiinventory_statediscoveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_taskjob_id` int(11) NOT NULL DEFAULT '0',
  `agents_id` int(11) NOT NULL DEFAULT '0',
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `threads` int(11) NOT NULL DEFAULT '0',
  `nb_ip` int(11) NOT NULL DEFAULT '0',
  `nb_found` int(11) NOT NULL DEFAULT '0',
  `nb_error` int(11) NOT NULL DEFAULT '0',
  `nb_exists` int(11) NOT NULL DEFAULT '0',
  `nb_import` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


--
-- BEGIN DEPLOY
--
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploypackages`;

CREATE TABLE IF NOT EXISTS `glpi_plugin_glpiinventory_deploypackages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `comment` text DEFAULT NULL,
  `entities_id` int(11) NOT NULL,
  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
  `date_mod` timestamp NULL DEFAULT NULL,
  `uuid` varchar(255) DEFAULT NULL,
  `json` longtext DEFAULT NULL,
  `plugin_glpiinventory_deploygroups_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploypackages_entities`;

CREATE TABLE `glpi_plugin_glpiinventory_deploypackages_entities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_deploypackages_id` int(11) NOT NULL DEFAULT '0',
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_deploypackages_id` (`plugin_glpiinventory_deploypackages_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploypackages_groups`;

CREATE TABLE `glpi_plugin_glpiinventory_deploypackages_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_deploypackages_id` int(11) NOT NULL DEFAULT '0',
  `groups_id` int(11) NOT NULL DEFAULT '0',
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_deploypackages_id` (`plugin_glpiinventory_deploypackages_id`),
  KEY `groups_id` (`groups_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploypackages_profiles`;

CREATE TABLE `glpi_plugin_glpiinventory_deploypackages_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_deploypackages_id` int(11) NOT NULL DEFAULT '0',
  `profiles_id` int(11) NOT NULL DEFAULT '0',
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_deploypackages_id` (`plugin_glpiinventory_deploypackages_id`),
  KEY `profiles_id` (`profiles_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploypackages_users`;

CREATE TABLE `glpi_plugin_glpiinventory_deploypackages_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_deploypackages_id` int(11) NOT NULL DEFAULT '0',
  `users_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_deploypackages_id` (`plugin_glpiinventory_deploypackages_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deployfiles`;

CREATE TABLE IF NOT EXISTS `glpi_plugin_glpiinventory_deployfiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `mimetype` varchar(255) NOT NULL,
  `filesize` bigint(20) NOT NULL,
  `comment` text DEFAULT NULL,
  `sha512` char(128) NOT NULL,
  `shortsha512` char(6) NOT NULL,
  `entities_id` int(11) NOT NULL,
  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shortsha512` (`shortsha512`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploymirrors`;

CREATE TABLE `glpi_plugin_glpiinventory_deploymirrors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `locations_id` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_active` (`is_active`),
  KEY `is_recursive` (`is_recursive`),
  KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploygroups`;

CREATE TABLE `glpi_plugin_glpiinventory_deploygroups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `comment` text DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploygroups_staticdatas`;

CREATE TABLE `glpi_plugin_glpiinventory_deploygroups_staticdatas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_deploygroups_id` int(11) NOT NULL DEFAULT '0',
  `itemtype` varchar(100) DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (  `id` ),
  KEY `plugin_glpiinventory_deploygroups_id` (`plugin_glpiinventory_deploygroups_id`),
  KEY `items_id` (`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploygroups_dynamicdatas`;

CREATE TABLE `glpi_plugin_glpiinventory_deploygroups_dynamicdatas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_deploygroups_id` int(11) NOT NULL DEFAULT '0',
  `fields_array` text DEFAULT NULL,
  `can_update_group` tinyint(1) NOT NULL DEFAULT '0',
  `computers_id_cache` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_deploygroups_id` (`plugin_glpiinventory_deploygroups_id`),
  KEY `can_update_group` (`can_update_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deployuserinteractiontemplates`;

CREATE TABLE IF NOT EXISTS `glpi_plugin_glpiinventory_deployuserinteractiontemplates` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `name` varchar(255) DEFAULT NULL,
   `entities_id` int(11) NOT NULL DEFAULT '0',
   `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
   `date_creation` timestamp NULL DEFAULT NULL,
   `date_mod` timestamp NULL DEFAULT NULL,
   `json` longtext DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `date_mod` (`date_mod`),
   KEY `date_creation` (`date_creation`),
   KEY `entities_id` (`entities_id`),
   KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- END DEPLOY
--


-- Collect tables
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects`;

CREATE TABLE `glpi_plugin_glpiinventory_collects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
  `type` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `comment` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects_registries`;

CREATE TABLE `glpi_plugin_glpiinventory_collects_registries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `plugin_glpiinventory_collects_id` int(11) NOT NULL DEFAULT '0',
  `hive` varchar(255) DEFAULT NULL,
  `path` text DEFAULT NULL,
  `key` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects_registries_contents`;

CREATE TABLE `glpi_plugin_glpiinventory_collects_registries_contents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `plugin_glpiinventory_collects_registries_id` int(11) NOT NULL DEFAULT '0',
  `key` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects_wmis`;

CREATE TABLE `glpi_plugin_glpiinventory_collects_wmis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `plugin_glpiinventory_collects_id` int(11) NOT NULL DEFAULT '0',
  `moniker` varchar(255) DEFAULT NULL,
  `class` varchar(255) DEFAULT NULL,
  `properties` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects_wmis_contents`;

CREATE TABLE `glpi_plugin_glpiinventory_collects_wmis_contents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `plugin_glpiinventory_collects_wmis_id` int(11) NOT NULL DEFAULT '0',
  `property` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects_files`;

CREATE TABLE `glpi_plugin_glpiinventory_collects_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `plugin_glpiinventory_collects_id` int(11) NOT NULL DEFAULT '0',
  `dir` varchar(255) DEFAULT NULL,
  `limit` int(4) NOT NULL DEFAULT '50',
  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
  `filter_regex` varchar(255) DEFAULT NULL,
  `filter_sizeequals` int(11) NOT NULL DEFAULT '0',
  `filter_sizegreater` int(11) NOT NULL DEFAULT '0',
  `filter_sizelower` int(11) NOT NULL DEFAULT '0',
  `filter_checksumsha512` varchar(255) DEFAULT NULL,
  `filter_checksumsha2` varchar(255) DEFAULT NULL,
  `filter_name` varchar(255) DEFAULT NULL,
  `filter_iname` varchar(255) DEFAULT NULL,
  `filter_is_file` tinyint(1) NOT NULL DEFAULT '1',
  `filter_is_dir` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects_files_contents`;

CREATE TABLE `glpi_plugin_glpiinventory_collects_files_contents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `plugin_glpiinventory_collects_files_id` int(11) NOT NULL DEFAULT '0',
  `pathfile` text DEFAULT NULL,
  `size` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_timeslots`;

CREATE TABLE `glpi_plugin_glpiinventory_timeslots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_timeslotentries`;

CREATE TABLE `glpi_plugin_glpiinventory_timeslotentries` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `plugin_glpiinventory_timeslots_id` int(11) NOT NULL DEFAULT '0',
 `entities_id` int(11) NOT NULL DEFAULT '0',
 `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
 `day` tinyint(1) NOT NULL DEFAULT '1',
 `begin` int(11) DEFAULT NULL,
 `end` int(11) DEFAULT NULL,
 PRIMARY KEY (`id`),
 KEY `plugin_glpiinventory_calendars_id` (`plugin_glpiinventory_timeslots_id`),
 KEY `day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


-- glpi_displaypreferences
INSERT INTO `glpi_displaypreferences` (`id`, `itemtype`, `num`, `rank`, `users_id`)
   VALUES
          (NULL, 'PluginGlpiinventoryTask', '2', '1', '0'),
          (NULL, 'PluginGlpiinventoryTask', '3', '2', '0'),
          (NULL, 'PluginGlpiinventoryTask', '4', '3', '0'),
          (NULL, 'PluginGlpiinventoryTask', '5', '4', '0'),
          (NULL, 'PluginGlpiinventoryTask', '6', '5', '0'),
          (NULL, 'PluginGlpiinventoryTask', '7', '6', '0'),
          (NULL, 'PluginGlpiinventoryTask', '30', '7', '0'),

          (NULL,'PluginGlpiinventoryIPRange', '2', '1', '0'),
          (NULL,'PluginGlpiinventoryIPRange', '3', '2', '0'),
          (NULL,'PluginGlpiinventoryIPRange', '4', '3', '0'),

          (NULL,'PluginGlpiinventoryTaskjob', '1', '1', '0'),
          (NULL,'PluginGlpiinventoryTaskjob', '2', '2', '0'),
          (NULL,'PluginGlpiinventoryTaskjob', '3', '3', '0'),
          (NULL,'PluginGlpiinventoryTaskjob', '4', '4', '0'),
          (NULL,'PluginGlpiinventoryTaskjob', '5', '5', '0'),

          (NULL,'PluginGlpiinventoryInventoryComputerBlacklist', '2', '1', '0'),

          (NULL,'PluginGlpiinventoryTaskjoblog', '2', '1', '0'),
          (NULL,'PluginGlpiinventoryTaskjoblog', '3', '2', '0'),
          (NULL,'PluginGlpiinventoryTaskjoblog', '4', '3', '0'),
          (NULL,'PluginGlpiinventoryTaskjoblog', '5', '4', '0'),
          (NULL,'PluginGlpiinventoryTaskjoblog', '6', '5', '0'),
          (NULL,'PluginGlpiinventoryTaskjoblog', '7', '6', '0'),
          (NULL,'PluginGlpiinventoryTaskjoblog', '8', '7', '0'),

          (NULL,'PluginGlpiinventoryStateDiscovery', '2', '1', '0'),
          (NULL,'PluginGlpiinventoryStateDiscovery', '4', '2', '0'),
          (NULL,'PluginGlpiinventoryStateDiscovery', '5', '3', '0'),
          (NULL,'PluginGlpiinventoryStateDiscovery', '6', '4', '0'),
          (NULL,'PluginGlpiinventoryStateDiscovery', '7', '5', '0'),
          (NULL,'PluginGlpiinventoryStateDiscovery', '8', '6', '0'),
          (NULL,'PluginGlpiinventoryStateDiscovery', '9', '7', '0'),
          (NULL,'PluginGlpiinventoryStateDiscovery', '10', '8', '0'),
          (NULL,'PluginGlpiinventoryStateDiscovery', '11', '9', '0'),
          (NULL,'PluginGlpiinventoryStateDiscovery', '12', '10', '0');



INSERT INTO `glpi_plugin_glpiinventory_inventorycomputercriterias`
(`id`, `name`, `comment`) VALUES
(1, 'Serial number', 'ssn'),
(2, 'uuid', 'uuid'),
(3, 'Mac address', 'macAddress'),
(4, 'Windows product key', 'winProdKey'),
(5, 'Model', 'smodel'),
(6, 'storage serial', 'storagesSerial'),
(7, 'drives serial', 'drivesSerial'),
(8, 'Asset Tag', 'assetTag'),
(9, 'Computer name', 'name'),
(10, 'Manufacturer', 'manufacturer'),
(11, 'IP', 'IP');

INSERT INTO `glpi_plugin_glpiinventory_inventorycomputerblacklists`
(`id`, `plugin_glpiinventory_criterium_id`, `value`) VALUES
(1, 3, '50:50:54:50:30:30'),
(2, 1, 'N/A'),
(3, 1, '(null string)'),
(4, 1, 'INVALID'),
(5, 1, 'SYS-1234567890'),
(6, 1, 'SYS-9876543210'),
(7, 1, 'SN-12345'),
(8, 1, 'SN-1234567890'),
(9, 1, '1111111111'),
(10, 1, '1111111'),
(11, 1, '1'),
(12, 1, '0123456789'),
(13, 1, '12345'),
(14, 1, '123456'),
(15, 1, '1234567'),
(16, 1, '12345678'),
(17, 1, '123456789'),
(18, 1, '1234567890'),
(19, 1, '123456789000'),
(20, 1, '12345678901234567'),
(21, 1, '0000000000'),
(22, 1, '000000000'),
(23, 1, '00000000'),
(24, 1, '0000000'),
(25, 1, '0000000'),
(26, 1, 'NNNNNNN'),
(27, 1, 'xxxxxxxxxxx'),
(28, 1, 'EVAL'),
(29, 1, 'IATPASS'),
(30, 1, 'none'),
(31, 1, 'To Be Filled By O.E.M.'),
(32, 1, 'Tulip Computers'),
(33, 1, 'Serial Number xxxxxx'),
(34, 1, 'SN-123456fvgv3i0b8o5n6n7k'),
(35, 1, 'Unknow'),
(36, 5, 'Unknow'),
(37, 1, 'System Serial Number'),
(38, 5, 'To Be Filled By O.E.M.'),
(39, 5, '*'),
(40, 5, 'System Product Name'),
(41, 5, 'Product Name'),
(42, 5, 'System Name'),
(43, 2, 'FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF'),
(44, 10, 'System manufacturer'),
(45, 2, '03000200-0400-0500-0006-000700080009'),
(46, 2, '6AB5B300-538D-1014-9FB5-B0684D007B53'),
(47, 2, '01010101-0101-0101-0101-010101010101'),
(48, 3, '20:41:53:59:4e:ff'),
(49, 3, '02:00:4e:43:50:49'),
(50, 3, 'e2:e6:16:20:0a:35'),
(51, 3, 'd2:0a:2d:a0:04:be'),
(52, 3, '00:a0:c6:00:00:00'),
(53, 3, 'd2:6b:25:2f:2c:e7'),
(54, 3, '33:50:6f:45:30:30'),
(55, 3, '0a:00:27:00:00:00'),
(56, 3, '00:50:56:C0:00:01'),
(57, 3, '00:50:56:C0:00:08'),
(58, 3, '02:80:37:EC:02:00'),
(59, 1, 'MB-1234567890'),
(60, 1, '0'),
(61, 1, 'empty'),
(62, 3, '24:b6:20:52:41:53'),
(63, 1, 'Not Specified'),
(64, 5, 'All Series');
