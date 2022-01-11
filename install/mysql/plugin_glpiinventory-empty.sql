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
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `type` varchar(255) DEFAULT NULL,
   `value` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `unicity` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_tasks`;

CREATE TABLE `glpi_plugin_glpiinventory_tasks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_active` tinyint NOT NULL DEFAULT '0',
  `datetime_start` timestamp NULL DEFAULT NULL,
  `datetime_end` timestamp NULL DEFAULT NULL,
  `plugin_glpiinventory_timeslots_prep_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_glpiinventory_timeslots_exec_id` int unsigned NOT NULL DEFAULT '0',
  `last_agent_wakeup` timestamp NULL DEFAULT NULL,
  `wakeup_agent_counter` int NOT NULL DEFAULT '0',
  `wakeup_agent_time` int NOT NULL DEFAULT '0',
  `reprepare_if_successful` tinyint NOT NULL DEFAULT '1',
  `is_deploy_on_demand` tinyint NOT NULL DEFAULT '0',
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
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_tasks_id` int unsigned NOT NULL DEFAULT '0',
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `method` varchar(255) DEFAULT NULL,
  `targets` text DEFAULT NULL,
  `actors` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `rescheduled_taskjob_id` int unsigned NOT NULL DEFAULT '0',
  `statuscomments` text DEFAULT NULL,
  `enduser` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_tasks_id` (`plugin_glpiinventory_tasks_id`),
  KEY `entities_id` (`entities_id`),
  KEY `method` (`method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_taskjoblogs`;

CREATE TABLE `glpi_plugin_glpiinventory_taskjoblogs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_taskjobstates_id` int unsigned NOT NULL DEFAULT '0',
  `date` timestamp NULL DEFAULT NULL,
  `items_id` int unsigned NOT NULL DEFAULT '0',
  `itemtype` varchar(100) DEFAULT NULL,
  `state` int NOT NULL DEFAULT '0',
  `comment` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_taskjobstates_id` (`plugin_glpiinventory_taskjobstates_id`,`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_taskjobstates`;

CREATE TABLE `glpi_plugin_glpiinventory_taskjobstates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_taskjobs_id` int unsigned NOT NULL DEFAULT '0',
  `items_id` int unsigned NOT NULL DEFAULT '0',
  `itemtype` varchar(100) DEFAULT NULL,
  `state` int NOT NULL DEFAULT '0',
  `agents_id` int unsigned NOT NULL DEFAULT '0',
  `specificity` text DEFAULT NULL,
  `uniqid` varchar(255) DEFAULT NULL,
  `date_start` timestamp NULL DEFAULT NULL,
  `nb_retry` int NOT NULL DEFAULT '0',
  `max_retry` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_taskjobs_id` (`plugin_glpiinventory_taskjobs_id`),
  KEY `agents_id` (`agents_id`),
  KEY `uniqid` (`uniqid`,`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_agentmodules`;

CREATE TABLE `glpi_plugin_glpiinventory_agentmodules` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `modulename` varchar(255) DEFAULT NULL,
   `is_active` tinyint NOT NULL DEFAULT '0',
   `exceptions` text DEFAULT NULL COMMENT 'array(agent_id)',
   PRIMARY KEY (`id`),
   UNIQUE KEY `modulename` (`modulename`),
   KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_ipranges`;

CREATE TABLE `glpi_plugin_glpiinventory_ipranges` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `name` varchar(255) DEFAULT NULL,
   `entities_id` int unsigned NOT NULL DEFAULT '0',
   `ip_start` varchar(255) DEFAULT NULL,
   `ip_end` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_ipranges_snmpcredentials`;

CREATE TABLE `glpi_plugin_glpiinventory_ipranges_snmpcredentials` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_ipranges_id` int unsigned NOT NULL DEFAULT '0',
  `snmpcredentials_id` int unsigned NOT NULL DEFAULT '0',
  `rank` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `unicity` (`plugin_glpiinventory_ipranges_id`,`snmpcredentials_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_credentials`;

CREATE TABLE  `glpi_plugin_glpiinventory_credentials` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `entities_id` int unsigned NOT NULL DEFAULT '0',
   `is_recursive` tinyint NOT NULL DEFAULT '0',
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
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `entities_id` int unsigned NOT NULL DEFAULT '0',
   `plugin_glpiinventory_credentials_id` int unsigned NOT NULL DEFAULT '0',
   `name` varchar(255) NOT NULL DEFAULT '',
   `comment` text DEFAULT NULL,
   `ip` varchar(255) NOT NULL DEFAULT '',
   `date_mod` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_inventorycomputercomputers`;

CREATE TABLE `glpi_plugin_glpiinventory_inventorycomputercomputers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `operatingsystem_installationdate` timestamp NULL DEFAULT NULL,
  `winowner` varchar(255) DEFAULT NULL,
  `wincompany` varchar(255) DEFAULT NULL,
  `last_inventory_update` timestamp NULL DEFAULT NULL,
  `remote_addr` varchar(255) DEFAULT NULL,
  `serialized_inventory` longblob DEFAULT NULL,
  `is_entitylocked` tinyint NOT NULL DEFAULT '0',
  `oscomment` text DEFAULT NULL,
  `hostid` varchar(255) DEFAULT NULL,
  `last_boot` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `last_inventory_update` (`last_inventory_update`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_inventorycomputerstats`;

CREATE TABLE `glpi_plugin_glpiinventory_inventorycomputerstats` (
 `id` smallint unsigned NOT NULL AUTO_INCREMENT,
 `day` smallint NOT NULL DEFAULT '0',
 `hour` tinyint NOT NULL DEFAULT '0',
 `counter` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_statediscoveries`;

CREATE TABLE `glpi_plugin_glpiinventory_statediscoveries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_taskjob_id` int unsigned NOT NULL DEFAULT '0',
  `agents_id` int unsigned NOT NULL DEFAULT '0',
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `threads` int NOT NULL DEFAULT '0',
  `nb_ip` int NOT NULL DEFAULT '0',
  `nb_found` int NOT NULL DEFAULT '0',
  `nb_error` int NOT NULL DEFAULT '0',
  `nb_exists` int NOT NULL DEFAULT '0',
  `nb_import` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


--
-- BEGIN DEPLOY
--
DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploypackages`;

CREATE TABLE IF NOT EXISTS `glpi_plugin_glpiinventory_deploypackages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `comment` text DEFAULT NULL,
  `entities_id` int unsigned NOT NULL,
  `is_recursive` tinyint NOT NULL DEFAULT '0',
  `date_mod` timestamp NULL DEFAULT NULL,
  `uuid` varchar(255) DEFAULT NULL,
  `json` longtext DEFAULT NULL,
  `plugin_glpiinventory_deploygroups_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploypackages_entities`;

CREATE TABLE `glpi_plugin_glpiinventory_deploypackages_entities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_deploypackages_id` int unsigned NOT NULL DEFAULT '0',
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `is_recursive` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_deploypackages_id` (`plugin_glpiinventory_deploypackages_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploypackages_groups`;

CREATE TABLE `glpi_plugin_glpiinventory_deploypackages_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_deploypackages_id` int unsigned NOT NULL DEFAULT '0',
  `groups_id` int unsigned NOT NULL DEFAULT '0',
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `is_recursive` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_deploypackages_id` (`plugin_glpiinventory_deploypackages_id`),
  KEY `groups_id` (`groups_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploypackages_profiles`;

CREATE TABLE `glpi_plugin_glpiinventory_deploypackages_profiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_deploypackages_id` int unsigned NOT NULL DEFAULT '0',
  `profiles_id` int unsigned NOT NULL DEFAULT '0',
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `is_recursive` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_deploypackages_id` (`plugin_glpiinventory_deploypackages_id`),
  KEY `profiles_id` (`profiles_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploypackages_users`;

CREATE TABLE `glpi_plugin_glpiinventory_deploypackages_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_deploypackages_id` int unsigned NOT NULL DEFAULT '0',
  `users_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_deploypackages_id` (`plugin_glpiinventory_deploypackages_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deployfiles`;

CREATE TABLE IF NOT EXISTS `glpi_plugin_glpiinventory_deployfiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `mimetype` varchar(255) NOT NULL,
  `filesize` bigint NOT NULL,
  `comment` text DEFAULT NULL,
  `sha512` char(128) NOT NULL,
  `shortsha512` char(6) NOT NULL,
  `entities_id` int unsigned NOT NULL,
  `is_recursive` tinyint NOT NULL DEFAULT '0',
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shortsha512` (`shortsha512`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploymirrors`;

CREATE TABLE `glpi_plugin_glpiinventory_deploymirrors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int unsigned NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '0',
  `is_recursive` tinyint NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `locations_id` int unsigned NOT NULL,
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
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `comment` text DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploygroups_staticdatas`;

CREATE TABLE `glpi_plugin_glpiinventory_deploygroups_staticdatas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_deploygroups_id` int unsigned NOT NULL DEFAULT '0',
  `itemtype` varchar(100) DEFAULT NULL,
  `items_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (  `id` ),
  KEY `plugin_glpiinventory_deploygroups_id` (`plugin_glpiinventory_deploygroups_id`),
  KEY `items_id` (`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deploygroups_dynamicdatas`;

CREATE TABLE `glpi_plugin_glpiinventory_deploygroups_dynamicdatas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_deploygroups_id` int unsigned NOT NULL DEFAULT '0',
  `fields_array` text DEFAULT NULL,
  `can_update_group` tinyint NOT NULL DEFAULT '0',
  `computers_id_cache` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_deploygroups_id` (`plugin_glpiinventory_deploygroups_id`),
  KEY `can_update_group` (`can_update_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_deployuserinteractiontemplates`;

CREATE TABLE IF NOT EXISTS `glpi_plugin_glpiinventory_deployuserinteractiontemplates` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `name` varchar(255) DEFAULT NULL,
   `entities_id` int unsigned NOT NULL DEFAULT '0',
   `is_recursive` tinyint NOT NULL DEFAULT '0',
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
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `is_recursive` tinyint NOT NULL DEFAULT '0',
  `type` varchar(255) DEFAULT NULL,
  `is_active` tinyint NOT NULL DEFAULT '0',
  `comment` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects_registries`;

CREATE TABLE `glpi_plugin_glpiinventory_collects_registries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `plugin_glpiinventory_collects_id` int unsigned NOT NULL DEFAULT '0',
  `hive` varchar(255) DEFAULT NULL,
  `path` text DEFAULT NULL,
  `key` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects_registries_contents`;

CREATE TABLE `glpi_plugin_glpiinventory_collects_registries_contents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_glpiinventory_collects_registries_id` int unsigned NOT NULL DEFAULT '0',
  `key` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects_wmis`;

CREATE TABLE `glpi_plugin_glpiinventory_collects_wmis` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `plugin_glpiinventory_collects_id` int unsigned NOT NULL DEFAULT '0',
  `moniker` varchar(255) DEFAULT NULL,
  `class` varchar(255) DEFAULT NULL,
  `properties` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects_wmis_contents`;

CREATE TABLE `glpi_plugin_glpiinventory_collects_wmis_contents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_glpiinventory_collects_wmis_id` int unsigned NOT NULL DEFAULT '0',
  `property` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects_files`;

CREATE TABLE `glpi_plugin_glpiinventory_collects_files` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `plugin_glpiinventory_collects_id` int unsigned NOT NULL DEFAULT '0',
  `dir` varchar(255) DEFAULT NULL,
  `limit` int NOT NULL DEFAULT '50',
  `is_recursive` tinyint NOT NULL DEFAULT '0',
  `filter_regex` varchar(255) DEFAULT NULL,
  `filter_sizeequals` int NOT NULL DEFAULT '0',
  `filter_sizegreater` int NOT NULL DEFAULT '0',
  `filter_sizelower` int NOT NULL DEFAULT '0',
  `filter_checksumsha512` varchar(255) DEFAULT NULL,
  `filter_checksumsha2` varchar(255) DEFAULT NULL,
  `filter_name` varchar(255) DEFAULT NULL,
  `filter_iname` varchar(255) DEFAULT NULL,
  `filter_is_file` tinyint NOT NULL DEFAULT '1',
  `filter_is_dir` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_collects_files_contents`;

CREATE TABLE `glpi_plugin_glpiinventory_collects_files_contents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_glpiinventory_collects_files_id` int unsigned NOT NULL DEFAULT '0',
  `pathfile` text DEFAULT NULL,
  `size` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_timeslots`;

CREATE TABLE `glpi_plugin_glpiinventory_timeslots` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `is_recursive` tinyint NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_timeslotentries`;

CREATE TABLE `glpi_plugin_glpiinventory_timeslotentries` (
 `id` int unsigned NOT NULL AUTO_INCREMENT,
 `plugin_glpiinventory_timeslots_id` int unsigned NOT NULL DEFAULT '0',
 `entities_id` int unsigned NOT NULL DEFAULT '0',
 `is_recursive` tinyint NOT NULL DEFAULT '0',
 `day` tinyint NOT NULL DEFAULT '1',
 `begin` int DEFAULT NULL,
 `end` int DEFAULT NULL,
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
