--
-- ---------------------------------------------------------------------
-- GLPI - Gestionnaire Libre de Parc Informatique
-- Copyright (C) 2015-2021 Teclib' and contributors.
--
-- http://glpi-project.org
--
-- based on GLPI - Gestionnaire Libre de Parc Informatique
-- Copyright (C) 2003-2014 by the INDEPNET Development Team.
--
-- ---------------------------------------------------------------------
--
-- LICENSE
--
-- This file is part of GLPI.
--
-- GLPI is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- GLPI is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with GLPI. If not, see <http://www.gnu.org/licenses/>.
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

DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_agents`;

CREATE TABLE `glpi_plugin_glpiinventory_agents` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `entities_id` int(11) NOT NULL DEFAULT '0',
   `is_recursive` tinyint(1) NOT NULL DEFAULT '1',
   `name` varchar(255) DEFAULT NULL,
   `last_contact` timestamp NULL DEFAULT NULL,
   `version` varchar(255) DEFAULT NULL,
   `lock` tinyint(1) NOT NULL DEFAULT '0',
   `device_id` varchar(255) DEFAULT NULL COMMENT 'XML <DEVICE_ID> TAG VALUE',
   `computers_id` int(11) NOT NULL DEFAULT '0',
   `token` varchar(255) DEFAULT NULL,
   `useragent` varchar(255) DEFAULT NULL,
   `tag` varchar(255) DEFAULT NULL,
   `threads_networkdiscovery` int(4) NOT NULL DEFAULT '1' COMMENT 'array(xmltag=>value)',
   `threads_networkinventory` int(4) NOT NULL DEFAULT '1' COMMENT 'array(xmltag=>value)',
   `senddico` tinyint(1) NOT NULL DEFAULT '0',
   `timeout_networkdiscovery` int(4) NOT NULL DEFAULT '0' COMMENT 'Network Discovery task timeout (disabled by default)',
   `timeout_networkinventory` int(4) NOT NULL DEFAULT '0' COMMENT 'Network Inventory task timeout (disabled by default)',
   `agent_port` varchar(6) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `name` (`name`),
   KEY `device_id` (`device_id`),
   KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_configs`;

CREATE TABLE `glpi_plugin_glpiinventory_configs` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `type` varchar(255) DEFAULT NULL,
   `value` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `unicity` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_entities`;

CREATE TABLE `glpi_plugin_glpiinventory_entities` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `entities_id` int(11) NOT NULL DEFAULT '0',
   `transfers_id_auto` int(11) NOT NULL DEFAULT '0',
   `agent_base_url` varchar(255) NOT NULL DEFAULT '',
   PRIMARY KEY (`id`),
   KEY `entities_id` (`entities_id`,`transfers_id_auto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_locks`;

CREATE TABLE `glpi_plugin_glpiinventory_locks` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `tablename` varchar(64) NOT NULL DEFAULT '',
   `items_id` int(11) NOT NULL DEFAULT '0',
   `tablefields` text DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `tablename` (`tablename`),
   KEY `items_id` (`items_id`),
   UNIQUE KEY `unicity` (`tablename`,`items_id`)
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
  `plugin_glpiinventory_agents_id` int(11) NOT NULL DEFAULT '0',
  `specificity` text DEFAULT NULL,
  `uniqid` varchar(255) DEFAULT NULL,
  `date_start` timestamp NULL DEFAULT NULL,
  `nb_retry` int(11) NOT NULL DEFAULT '0',
  `max_retry` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `plugin_glpiinventory_taskjobs_id` (`plugin_glpiinventory_taskjobs_id`),
  KEY `plugin_glpiinventory_agents_id` (`plugin_glpiinventory_agents_id`),
  KEY `uniqid` (`uniqid`,`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_mappings`;

CREATE TABLE `glpi_plugin_glpiinventory_mappings` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `itemtype` varchar(100) DEFAULT NULL,
   `name` varchar(255) DEFAULT NULL,
   `table` varchar(255) DEFAULT NULL,
   `tablefield` varchar(255) DEFAULT NULL,
   `locale` int(4) NOT NULL DEFAULT '0',
   `shortlocale` int(4) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `name` (`name`),
   KEY `itemtype` (`itemtype`),
   KEY `table` (`table`),
   KEY `tablefield` (`tablefield`)
--   UNIQUE KEY `unicity` (`name`, `itemtype`) -- Specified key was too long; max key length is 1000 bytes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_unmanageds`;

CREATE TABLE IF NOT EXISTS `glpi_plugin_glpiinventory_unmanageds` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `name` varchar(255) DEFAULT NULL,
   `date_mod` timestamp NULL DEFAULT NULL,
   `entities_id` int(11) NOT NULL DEFAULT '0',
   `locations_id` int(11) NOT NULL DEFAULT '0',
   `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
   `users_id` int(11) NOT NULL DEFAULT '0',
   `serial` varchar(255) DEFAULT NULL,
   `otherserial` varchar(255) DEFAULT NULL,
   `contact` varchar(255) DEFAULT NULL,
   `domain` int(11) NOT NULL DEFAULT '0',
   `comment` text DEFAULT NULL,
   `item_type` varchar(255) DEFAULT NULL,
   `accepted` tinyint(1) NOT NULL DEFAULT '0',
   `plugin_glpiinventory_agents_id` int(11) NOT NULL DEFAULT '0',
   `ip` varchar(255) DEFAULT NULL,
   `hub` tinyint(1) NOT NULL DEFAULT '0',
   `states_id` int(11) NOT NULL DEFAULT '0',
   `sysdescr` text DEFAULT NULL,
   `plugin_glpiinventory_configsecurities_id` int(11) NOT NULL DEFAULT '0',
   `is_dynamic` tinyint(1) NOT NULL DEFAULT '0',
   `serialized_inventory` longblob DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `entities_id` (`entities_id`),
   KEY `plugin_glpiinventory_agents_id` (`plugin_glpiinventory_agents_id`),
   KEY `is_deleted` (`is_deleted`),
   KEY `date_mod` (`date_mod`)
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



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_ipranges_configsecurities`;

CREATE TABLE `glpi_plugin_glpiinventory_ipranges_configsecurities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_ipranges_id` int(11) NOT NULL DEFAULT '0',
  `plugin_glpiinventory_configsecurities_id` int(11) NOT NULL DEFAULT '0',
  `rank` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `unicity` (`plugin_glpiinventory_ipranges_id`,`plugin_glpiinventory_configsecurities_id`)
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



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_ignoredimportdevices`;

CREATE TABLE `glpi_plugin_glpiinventory_ignoredimportdevices` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `name` varchar(255) DEFAULT NULL,
   `date` timestamp NULL DEFAULT NULL,
   `itemtype` varchar(100) DEFAULT NULL,
   `entities_id` int(11) NOT NULL DEFAULT '0',
   `ip` varchar(255) DEFAULT NULL,
   `mac` varchar(255) DEFAULT NULL,
   `rules_id` int(11) NOT NULL DEFAULT '0',
   `method` varchar(255) DEFAULT NULL,
   `serial` varchar(255) DEFAULT NULL,
   `uuid` varchar(255) DEFAULT NULL,
   `plugin_glpiinventory_agents_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_rulematchedlogs`;

CREATE TABLE `glpi_plugin_glpiinventory_rulematchedlogs` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `date` timestamp NULL DEFAULT NULL,
   `items_id` int(11) NOT NULL DEFAULT '0',
   `itemtype` varchar(100) DEFAULT NULL,
   `rules_id` int(11) NOT NULL DEFAULT '0',
   `plugin_glpiinventory_agents_id` int(11) NOT NULL DEFAULT '0',
   `method` varchar(255) DEFAULT NULL,
   `criteria` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`itemtype`,`items_id`)
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
  `last_fusioninventory_update` timestamp NULL DEFAULT NULL,
  `remote_addr` varchar(255) DEFAULT NULL,
  `serialized_inventory` longblob DEFAULT NULL,
  `is_entitylocked` tinyint(1) NOT NULL DEFAULT '0',
  `oscomment` text DEFAULT NULL,
  `hostid` varchar(255) DEFAULT NULL,
  `last_boot` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `last_fusioninventory_update` (`last_fusioninventory_update`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_inventorycomputerstats`;

CREATE TABLE `glpi_plugin_glpiinventory_inventorycomputerstats` (
 `id` smallint(3) NOT NULL AUTO_INCREMENT,
 `day` smallint(3) NOT NULL DEFAULT '0',
 `hour` tinyint(2) NOT NULL DEFAULT '0',
 `counter` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_configlogfields`;

CREATE TABLE `glpi_plugin_glpiinventory_configlogfields` (
   `id` int(8) NOT NULL AUTO_INCREMENT,
   `plugin_glpiinventory_mappings_id` int(11) NOT NULL DEFAULT '0',
   `days` int(255) NOT NULL DEFAULT '-1',
   PRIMARY KEY ( `id` ) ,
   KEY `plugin_glpiinventory_mappings_id` ( `plugin_glpiinventory_mappings_id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_networkportconnectionlogs`;

CREATE TABLE `glpi_plugin_glpiinventory_networkportconnectionlogs` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `date_mod` timestamp NULL DEFAULT NULL,
   `creation` tinyint(1) NOT NULL DEFAULT '0',
   `networkports_id_source` int(11) NOT NULL DEFAULT '0',
   `networkports_id_destination` int(11) NOT NULL DEFAULT '0',
   `plugin_glpiinventory_agentprocesses_id` int(11) NOT NULL DEFAULT '0',
   PRIMARY KEY (`id`),
   KEY `networkports_id_source` ( `networkports_id_source`, `networkports_id_destination`, `plugin_glpiinventory_agentprocesses_id` ),
   KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_networkequipments`;

CREATE TABLE `glpi_plugin_glpiinventory_networkequipments` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `networkequipments_id` int(11) NOT NULL DEFAULT '0',
   `sysdescr` text DEFAULT NULL,
   `plugin_glpiinventory_configsecurities_id` int(11) NOT NULL DEFAULT '0',
   `uptime` varchar(255) NOT NULL DEFAULT '0',
   `cpu` int(3) NOT NULL DEFAULT '0' COMMENT '%',
   `memory` int(11) NOT NULL DEFAULT '0',
   `last_fusioninventory_update` timestamp NULL DEFAULT NULL,
   `last_PID_update` int(11) NOT NULL DEFAULT '0',
   `serialized_inventory` longblob DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `networkequipments_id` (`networkequipments_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_networkporttypes`;

CREATE TABLE `glpi_plugin_glpiinventory_networkporttypes` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `name` varchar(255) DEFAULT NULL,
   `number` int(4) NOT NULL DEFAULT '0',
   `othername` varchar(255) DEFAULT NULL,
   `import` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_printers`;

CREATE TABLE `glpi_plugin_glpiinventory_printers` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `printers_id` int(11) NOT NULL DEFAULT '0',
   `sysdescr` text DEFAULT NULL,
   `plugin_glpiinventory_configsecurities_id` int(11) NOT NULL DEFAULT '0',
   `frequence_days` int(5) NOT NULL DEFAULT '1',
   `last_fusioninventory_update` timestamp NULL DEFAULT NULL,
   `serialized_inventory` longblob DEFAULT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `unicity` (`printers_id`),
   KEY `plugin_glpiinventory_configsecurities_id` (`plugin_glpiinventory_configsecurities_id`),
   KEY `printers_id` (`printers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_printerlogs`;

CREATE TABLE `glpi_plugin_glpiinventory_printerlogs` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `printers_id` int(11) NOT NULL DEFAULT '0',
   `date` timestamp NULL DEFAULT NULL,
   `pages_total` int(11) NOT NULL DEFAULT '0',
   `pages_n_b` int(11) NOT NULL DEFAULT '0',
   `pages_color` int(11) NOT NULL DEFAULT '0',
   `pages_recto_verso` int(11) NOT NULL DEFAULT '0',
   `scanned` int(11) NOT NULL DEFAULT '0',
   `pages_total_print` int(11) NOT NULL DEFAULT '0',
   `pages_n_b_print` int(11) NOT NULL DEFAULT '0',
   `pages_color_print` int(11) NOT NULL DEFAULT '0',
   `pages_total_copy` int(11) NOT NULL DEFAULT '0',
   `pages_n_b_copy` int(11) NOT NULL DEFAULT '0',
   `pages_color_copy` int(11) NOT NULL DEFAULT '0',
   `pages_total_fax` int(11) NOT NULL DEFAULT '0',
   PRIMARY KEY (`id`),
   KEY `printers_id` (`printers_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_printercartridges`;

CREATE TABLE `glpi_plugin_glpiinventory_printercartridges` (
   `id` bigint(100) NOT NULL AUTO_INCREMENT,
   `printers_id` int(11) NOT NULL DEFAULT '0',
   `plugin_glpiinventory_mappings_id` int(11) NOT NULL DEFAULT '0',
   `cartridges_id` int(11) NOT NULL DEFAULT '0',
   `state` int(3) NOT NULL DEFAULT '100',
   PRIMARY KEY (`id`),
   KEY `printers_id` (`printers_id`),
   KEY `plugin_glpiinventory_mappings_id` (`plugin_glpiinventory_mappings_id`),
   KEY `cartridges_id` (`cartridges_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_networkports`;

CREATE TABLE `glpi_plugin_glpiinventory_networkports` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `networkports_id` int(11) NOT NULL DEFAULT '0',
   `ifmtu` int(8) NOT NULL DEFAULT '0',
   `ifspeed` bigint(50) NOT NULL DEFAULT '0',
   `ifinternalstatus` varchar(255) DEFAULT NULL,
   `ifconnectionstatus` int(8) NOT NULL DEFAULT '0',
   `iflastchange` varchar(255) DEFAULT NULL,
   `ifinoctets` bigint(50) NOT NULL DEFAULT '0',
   `ifinerrors` bigint(50) NOT NULL DEFAULT '0',
   `ifoutoctets` bigint(50) NOT NULL DEFAULT '0',
   `ifouterrors` bigint(50) NOT NULL DEFAULT '0',
   `ifstatus` varchar(255) DEFAULT NULL,
   `mac` varchar(255) DEFAULT NULL,
   `ifdescr` varchar(255) DEFAULT NULL,
   `ifalias` varchar(255) DEFAULT NULL,
   `portduplex` varchar(255) DEFAULT NULL,
   `trunk` tinyint(1) NOT NULL DEFAULT '0',
   `lastup` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `networkports_id` (`networkports_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_configsecurities`;

CREATE TABLE `glpi_plugin_glpiinventory_configsecurities` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `name` varchar(64) DEFAULT NULL,
   `snmpversion` varchar(8) NOT NULL DEFAULT '1',
   `community` varchar(255) DEFAULT NULL,
   `username` varchar(255) DEFAULT NULL,
   `authentication` varchar(255) DEFAULT NULL,
   `auth_passphrase` varchar(255) DEFAULT NULL,
   `encryption` varchar(255) DEFAULT NULL,
   `priv_passphrase` varchar(255) DEFAULT NULL,
   `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY (`id`),
   KEY `snmpversion` (`snmpversion`),
   KEY `is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_networkportlogs`;

CREATE TABLE `glpi_plugin_glpiinventory_networkportlogs` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `networkports_id` int(11) NOT NULL DEFAULT '0',
   `plugin_glpiinventory_mappings_id` int(11) NOT NULL DEFAULT '0',
   `date_mod` timestamp NULL DEFAULT NULL,
   `value_old` varchar(255) DEFAULT NULL,
   `value_new` varchar(255) DEFAULT NULL,
   `plugin_glpiinventory_agentprocesses_id` int(11) NOT NULL DEFAULT '0',
   PRIMARY KEY (`id`),
   KEY `networkports_id` (`networkports_id`,`date_mod`),
   KEY `plugin_glpiinventory_mappings_id` (`plugin_glpiinventory_mappings_id`),
   KEY `plugin_glpiinventory_agentprocesses_id` (`plugin_glpiinventory_agentprocesses_id`),
   KEY `date_mod` (`date_mod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_statediscoveries`;

CREATE TABLE `glpi_plugin_glpiinventory_statediscoveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_glpiinventory_taskjob_id` int(11) NOT NULL DEFAULT '0',
  `plugin_glpiinventory_agents_id` int(11) NOT NULL DEFAULT '0',
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



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_computerlicenseinfos`;

CREATE TABLE `glpi_plugin_glpiinventory_computerlicenseinfos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `softwarelicenses_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `serial` varchar(255) DEFAULT NULL,
  `is_trial` tinyint(1) NOT NULL DEFAULT '0',
  `is_update` tinyint(1) NOT NULL DEFAULT '0',
  `is_oem` tinyint(1) NOT NULL DEFAULT '0',
  `activation_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `fullname` (`fullname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_computerremotemanagements`;

CREATE TABLE `glpi_plugin_glpiinventory_computerremotemanagements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `number` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
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



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_dblockinventorynames`;

CREATE TABLE `glpi_plugin_glpiinventory_dblockinventorynames` (
  `value` varchar(100) NOT NULL DEFAULT '',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
   PRIMARY KEY (`value`),
   UNIQUE KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_dblockinventories`;

CREATE TABLE `glpi_plugin_glpiinventory_dblockinventories` (
  `value` int(11) NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
   PRIMARY KEY (`value`),
   UNIQUE KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_dblocksoftwares`;

CREATE TABLE `glpi_plugin_glpiinventory_dblocksoftwares` (
  `value` tinyint(1) NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
   PRIMARY KEY (`value`),
   UNIQUE KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



DROP TABLE IF EXISTS `glpi_plugin_glpiinventory_dblocksoftwareversions`;

CREATE TABLE `glpi_plugin_glpiinventory_dblocksoftwareversions` (
  `value` tinyint(1) NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
   PRIMARY KEY (`value`),
   UNIQUE KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



-- INSERT
-- glpi_plugin_glpiinventory_configsecurities
INSERT INTO `glpi_plugin_glpiinventory_configsecurities`
      (`id`, `name`, `snmpversion`, `community`, `username`, `authentication`, `auth_passphrase`, `encryption`, `priv_passphrase`, `is_deleted`)
   VALUES (1, 'Public community v1', '1', 'public', '', '0', '', '0', '', '0'),
          (2, 'Public community v2c', '2', 'public', '', '0', '', '0', '', '0');


-- glpi_plugin_glpiinventory_entities
INSERT INTO `glpi_plugin_glpiinventory_entities`
      (`entities_id`, `transfers_id_auto`)
   VALUES ('0', '0');


-- glpi_displaypreferences
INSERT INTO `glpi_displaypreferences` (`id`, `itemtype`, `num`, `rank`, `users_id`)
   VALUES (NULL,'PluginGlpiinventoryAgent', '2', '1', '0'),
          (NULL,'PluginGlpiinventoryAgent', '4', '2', '0'),
          (NULL,'PluginGlpiinventoryAgent', '5', '3', '0'),
          (NULL,'PluginGlpiinventoryAgent', '6', '4', '0'),
          (NULL,'PluginGlpiinventoryAgent', '7', '5', '0'),
          (NULL,'PluginGlpiinventoryAgent', '8', '6', '0'),
          (NULL,'PluginGlpiinventoryAgent', '9', '7', '0'),

          (NULL, 'PluginGlpiinventoryUnmanaged', '2', '1', '0'),
          (NULL, 'PluginGlpiinventoryUnmanaged', '4', '2', '0'),
          (NULL, 'PluginGlpiinventoryUnmanaged', '3', '3', '0'),
          (NULL, 'PluginGlpiinventoryUnmanaged', '5', '4', '0'),
          (NULL, 'PluginGlpiinventoryUnmanaged', '7', '5', '0'),
          (NULL, 'PluginGlpiinventoryUnmanaged', '10', '6', '0'),
          (NULL, 'PluginGlpiinventoryUnmanaged', '18', '8', '0'),
          (NULL, 'PluginGlpiinventoryUnmanaged', '14', '9', '0'),
          (NULL, 'PluginGlpiinventoryUnmanaged', '15', '10', '0'),
          (NULL, 'PluginGlpiinventoryUnmanaged', '9', '11', '0'),

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

          (NULL, 'PluginGlpiinventoryConfigSecurity', '3', '1', '0'),
          (NULL, 'PluginGlpiinventoryConfigSecurity', '4', '2', '0'),
          (NULL, 'PluginGlpiinventoryConfigSecurity', '5', '3', '0'),
          (NULL, 'PluginGlpiinventoryConfigSecurity', '7', '4', '0'),
          (NULL, 'PluginGlpiinventoryConfigSecurity', '8', '5', '0'),
          (NULL, 'PluginGlpiinventoryConfigSecurity', '9', '6', '0'),
          (NULL, 'PluginGlpiinventoryConfigSecurity', '10', '7', '0'),

          (NULL,'PluginGlpiinventoryNetworkEquipment', '2', '1', '0'),
          (NULL,'PluginGlpiinventoryNetworkEquipment', '3', '2', '0'),
          (NULL,'PluginGlpiinventoryNetworkEquipment', '4', '3', '0'),
          (NULL,'PluginGlpiinventoryNetworkEquipment', '5', '4', '0'),
          (NULL,'PluginGlpiinventoryNetworkEquipment', '6', '5', '0'),
          (NULL,'PluginGlpiinventoryNetworkEquipment', '7', '6', '0'),
          (NULL,'PluginGlpiinventoryNetworkEquipment', '8', '7', '0'),
          (NULL,'PluginGlpiinventoryNetworkEquipment', '9', '8', '0'),
          (NULL,'PluginGlpiinventoryNetworkEquipment', '10', '9', '0'),
          (NULL,'PluginGlpiinventoryNetworkEquipment', '11', '10', '0'),
          (NULL,'PluginGlpiinventoryNetworkEquipment', '14', '11', '0'),
          (NULL,'PluginGlpiinventoryNetworkEquipment', '12', '12', '0'),
          (NULL,'PluginGlpiinventoryNetworkEquipment', '13', '13', '0'),

          (NULL,'PluginGlpiinventoryNetworkPortLog', '2', '1', '0'),
          (NULL,'PluginGlpiinventoryNetworkPortLog', '3', '2', '0'),
          (NULL,'PluginGlpiinventoryNetworkPortLog', '4', '3', '0'),
          (NULL,'PluginGlpiinventoryNetworkPortLog', '5', '4', '0'),
          (NULL,'PluginGlpiinventoryNetworkPortLog', '6', '5', '0'),

          (NULL,'PluginGlpiinventoryNetworkPort', '3', '1', '0'),
          (NULL,'PluginGlpiinventoryNetworkPort', '5', '2', '0'),
          (NULL,'PluginGlpiinventoryNetworkPort', '6', '3', '0'),
          (NULL,'PluginGlpiinventoryNetworkPort', '7', '4', '0'),
          (NULL,'PluginGlpiinventoryNetworkPort', '8', '5', '0'),
          (NULL,'PluginGlpiinventoryNetworkPort', '9', '6', '0'),
          (NULL,'PluginGlpiinventoryNetworkPort', '10', '7', '0'),
          (NULL,'PluginGlpiinventoryNetworkPort', '11', '8', '0'),
          (NULL,'PluginGlpiinventoryNetworkPort', '12', '9', '0'),
          (NULL,'PluginGlpiinventoryNetworkPort', '13', '10', '0'),
          (NULL,'PluginGlpiinventoryNetworkPort', '14', '11', '0'),

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



-- glpi_plugin_glpiinventory_mappings
INSERT INTO `glpi_plugin_glpiinventory_mappings`
      (`itemtype`, `name`, `table`, `tablefield`, `locale`, `shortlocale`)
   VALUES ('NetworkEquipment','location','glpi_networkequipments','locations_id',1,NULL),
          ('NetworkEquipment','firmware','glpi_networkequipments',
             'networkequipmentfirmwares_id',2,NULL),
          ('NetworkEquipment','firmware1','','',2,NULL),
          ('NetworkEquipment','firmware2','','',2,NULL),
          ('NetworkEquipment','contact','glpi_networkequipments','contact',403,NULL),
          ('NetworkEquipment','comments','glpi_networkequipments','comment',404,NULL),
          ('NetworkEquipment','uptime','glpi_plugin_glpiinventory_networkequipments',
             'uptime',3,NULL),
          ('NetworkEquipment','cpu','glpi_plugin_glpiinventory_networkequipments',
             'cpu',12,NULL),
          ('NetworkEquipment','cpuuser','glpi_plugin_glpiinventory_networkequipments',
             'cpu',401,NULL),
          ('NetworkEquipment','cpusystem','glpi_plugin_glpiinventory_networkequipments',
             'cpu',402,NULL),
          ('NetworkEquipment','serial','glpi_networkequipments','serial',13,NULL),
          ('NetworkEquipment','otherserial','glpi_networkequipments','otherserial',419,NULL),
          ('NetworkEquipment','name','glpi_networkequipments','name',20,NULL),
          ('NetworkEquipment','ram','glpi_networkequipments','ram',21,NULL),
          ('NetworkEquipment','memory','glpi_plugin_glpiinventory_networkequipments',
             'memory',22,NULL),
          ('NetworkEquipment','vtpVlanName','','',19,NULL),
          ('NetworkEquipment','vmvlan','','',430,NULL),
          ('NetworkEquipment','entPhysicalModelName','glpi_networkequipments',
             'networkequipmentmodels_id',17,NULL),
          ('NetworkEquipment','macaddr','glpi_networkequipments','ip',417,NULL),
-- Network CDP (Walk)
          ('NetworkEquipment','cdpCacheAddress','','',409,NULL),
          ('NetworkEquipment','cdpCacheDevicePort','','',410,NULL),
          ('NetworkEquipment','cdpCacheVersion','','',435,NULL),
          ('NetworkEquipment','cdpCacheDeviceId','','',436,NULL),
          ('NetworkEquipment','cdpCachePlatform','','',437,NULL),
          ('NetworkEquipment','lldpRemChassisId','','',431,NULL),
          ('NetworkEquipment','lldpRemPortId','','',432,NULL),
          ('NetworkEquipment','lldpLocChassisId','','',432,NULL),
          ('NetworkEquipment','lldpRemSysDesc','','',438,NULL),
          ('NetworkEquipment','lldpRemSysName','','',439,NULL),
          ('NetworkEquipment','lldpRemPortDesc','','',440,NULL),
          ('NetworkEquipment','vlanTrunkPortDynamicStatus','','',411,NULL),
          ('NetworkEquipment','dot1dTpFdbAddress','','',412,NULL),
          ('NetworkEquipment','ipNetToMediaPhysAddress','','',413,NULL),
          ('NetworkEquipment','dot1dTpFdbPort','','',414,NULL),
          ('NetworkEquipment','dot1dBasePortIfIndex','','',415,NULL),
          ('NetworkEquipment','ipAdEntAddr','','',421,NULL),
          ('NetworkEquipment','PortVlanIndex','','',422,NULL),
-- NetworkPorts
          ('NetworkEquipment','ifIndex','','',408,NULL),
          ('NetworkEquipment','ifmtu','glpi_plugin_glpiinventory_networkports',
             'ifmtu',4,NULL),
          ('NetworkEquipment','ifspeed','glpi_plugin_glpiinventory_networkports',
             'ifspeed',5,NULL),
          ('NetworkEquipment','ifinternalstatus','glpi_plugin_glpiinventory_networkports',
             'ifinternalstatus',6,NULL),
          ('NetworkEquipment','iflastchange','glpi_plugin_glpiinventory_networkports',
             'iflastchange',7,NULL),
          ('NetworkEquipment','ifinoctets','glpi_plugin_glpiinventory_networkports',
             'ifinoctets',8,NULL),
          ('NetworkEquipment','ifoutoctets','glpi_plugin_glpiinventory_networkports',
             'ifoutoctets',9,NULL),
          ('NetworkEquipment','ifinerrors','glpi_plugin_glpiinventory_networkports',
             'ifinerrors',10,NULL),
          ('NetworkEquipment','ifouterrors','glpi_plugin_glpiinventory_networkports',
             'ifouterrors',11,NULL),
          ('NetworkEquipment','ifstatus','glpi_plugin_glpiinventory_networkports',
             'ifstatus',14,NULL),
          ('NetworkEquipment','ifPhysAddress','glpi_networkports','mac',15,NULL),
          ('NetworkEquipment','ifName','glpi_networkports','name',16,NULL),
          ('NetworkEquipment','ifType','','',18,NULL),
          ('NetworkEquipment','ifdescr','glpi_plugin_glpiinventory_networkports',
             'ifdescr',23,NULL),
          ('NetworkEquipment','portDuplex','glpi_plugin_glpiinventory_networkports',
             'portduplex',33,NULL),
          ('NetworkEquipment','ifalias','glpi_plugin_glpiinventory_networkports',
             'ifalias',120,NULL),
-- Printers
          ('Printer','model','glpi_printers','printermodels_id',25,NULL),
          ('Printer','enterprise','glpi_printers','manufacturers_id',420,NULL),
          ('Printer','serial','glpi_printers','serial',27,NULL),
          ('Printer','contact','glpi_printers','contact',405,NULL),
          ('Printer','comments','glpi_printers','comment',406,NULL),
          ('Printer','name','glpi_printers','comment',24,NULL),
          ('Printer','otherserial','glpi_printers','otherserial',418,NULL),
          ('Printer','memory','glpi_printers','memory_size',26,NULL),
          ('Printer','location','glpi_printers','locations_id',56,NULL),
          ('Printer','informations','','',165,165),
-- Cartridges
          ('Printer','tonerblack','','',157,157),
          ('Printer','tonerblackmax','','',166,166),
          ('Printer','tonerblackused','','',167,167),
          ('Printer','tonerblackremaining','','',168,168),
          ('Printer','tonerblack2','','',157,157),
          ('Printer','tonerblack2max','','',166,166),
          ('Printer','tonerblack2used','','',167,167),
          ('Printer','tonerblack2remaining','','',168,168),
          ('Printer','tonercyan','','',158,158),
          ('Printer','tonercyanmax','','',169,169),
          ('Printer','tonercyanused','','',170,170),
          ('Printer','tonercyanremaining','','',171,171),
          ('Printer','tonermagenta','','',159,159),
          ('Printer','tonermagentamax','','',172,172),
          ('Printer','tonermagentaused','','',173,173),
          ('Printer','tonermagentaremaining','','',174,174),
          ('Printer','toneryellow','','',160,160),
          ('Printer','toneryellowmax','','',175,175),
          ('Printer','toneryellowused','','',176,176),
          ('Printer','toneryellowremaining','','',177,177),
          ('Printer','wastetoner','','',151,151),
          ('Printer','wastetonermax','','',190,190),
          ('Printer','wastetonerused','','',191,191),
          ('Printer','wastetonerremaining','','',192,192),
          ('Printer','cartridgeblackmatte','','',133,133),
          ('Printer','cartridgematteblack','','',133,133),
          ('Printer','cartridgeblack','','',134,134),
          ('Printer','cartridgeblackphoto','','',135,135),
          ('Printer','cartridgephotoblack','','',135,135),
          ('Printer','cartridgecyan','','',136,136),
          ('Printer','cartridgecyanlight','','',139,139),
          ('Printer','cartridgelightcyan','','',139,139),
          ('Printer','cartridgemagenta','','',138,138),
          ('Printer','cartridgemagentalight','','',140,140),
          ('Printer','cartridgelightmagenta','','',140,140),
          ('Printer','cartridgeyellow','','',137,137),
          ('Printer','cartridgegrey','','',196,196),
          ('Printer','cartridgegray','','',196,196),
          ('Printer','cartridgegreylight','','',211,211),
          ('Printer','cartridgegraylight','','',211,211),
          ('Printer','cartridgelightgrey','','',211,211),
          ('Printer','cartridgelightgray','','',211,211),
          ('Printer','cartridgeglossenhancer','','',206,206),
          ('Printer','cartridgeblue','','',207,207),
          ('Printer','cartridgegreen','','',208,208),
          ('Printer','cartridgered','','',209,209),
          ('Printer','cartridgechromaticred','','',210,210),
          ('Printer','maintenancekit','','',156,156),
          ('Printer','maintenancekitmax','','',193,193),
          ('Printer','maintenancekitused','','',194,194),
          ('Printer','maintenancekitremaining','','',195,195),
          ('Printer','transferkit','','',212,212),
          ('Printer','transferkitmax','','',199,199),
          ('Printer','transferkitused','','',200,200),
          ('Printer','transferkitremaining','','',201,201),
          ('Printer','fuserkit','','',202,202),
          ('Printer','fuserkitmax','','',203,203),
          ('Printer','fuserkitused','','',204,204),
          ('Printer','fuserkitremaining','','',205,205),
          ('Printer','drumblack','','',161,161),
          ('Printer','drumblackmax','','',178,178),
          ('Printer','drumblackused','','',179,179),
          ('Printer','drumblackremaining','','',180,180),
          ('Printer','drumcyan','','',162,162),
          ('Printer','drumcyanmax','','',181,181),
          ('Printer','drumcyanused','','',182,182),
          ('Printer','drumcyanremaining','','',183,183),
          ('Printer','drummagenta','','',163,163),
          ('Printer','drummagentamax','','',184,184),
          ('Printer','drummagentaused','','',185,185),
          ('Printer','drummagentaremaining','','',186,186),
          ('Printer','drumyellow','','',164,164),
          ('Printer','drumyellowmax','','',187,187),
          ('Printer','drumyellowused','','',188,188),
          ('Printer','drumyellowremaining','','',189,189),
          ('Printer','paperrollinches','','',197,197),
          ('Printer','paperrollcentimeters','','',198,198),
-- Printers : Counter pages
          ('Printer','pagecountertotalpages','glpi_plugin_glpiinventory_printerlogs',
             'pages_total',28,128),
          ('Printer','pagecounterblackpages','glpi_plugin_glpiinventory_printerlogs',
             'pages_n_b',29,129),
          ('Printer','pagecountercolorpages','glpi_plugin_glpiinventory_printerlogs',
             'pages_color',30,130),
          ('Printer','pagecounterrectoversopages','glpi_plugin_glpiinventory_printerlogs',
             'pages_recto_verso',54,154),
          ('Printer','pagecounterscannedpages','glpi_plugin_glpiinventory_printerlogs',
             'scanned',55,155),
          ('Printer','pagecountertotalpages_print','glpi_plugin_glpiinventory_printerlogs',
             'pages_total_print',423,1423),
          ('Printer','pagecounterblackpages_print','glpi_plugin_glpiinventory_printerlogs',
             'pages_n_b_print',424,1424),
          ('Printer','pagecountercolorpages_print','glpi_plugin_glpiinventory_printerlogs',
             'pages_color_print',425,1425),
          ('Printer','pagecountertotalpages_copy','glpi_plugin_glpiinventory_printerlogs',
             'pages_total_copy',426,1426),
          ('Printer','pagecounterblackpages_copy','glpi_plugin_glpiinventory_printerlogs',
             'pages_n_b_copy',427,1427),
          ('Printer','pagecountercolorpages_copy','glpi_plugin_glpiinventory_printerlogs',
             'pages_color_copy',428,1428),
          ('Printer','pagecountertotalpages_fax','glpi_plugin_glpiinventory_printerlogs',
             'pages_total_fax',429,1429),
          ('Printer','pagecounterlargepages','glpi_plugin_glpiinventory_printerlogs',
             'pages_total_large',434,1434),
-- Printers : NetworkPort
          ('Printer','ifPhysAddress','glpi_networkports','mac',58,NULL),
          ('Printer','ifName','glpi_networkports','name',57,NULL),
          ('Printer','ifaddr','glpi_networkports','ip',407,NULL),
          ('Printer','ifType','','',97,NULL),
          ('Printer','ifIndex','','',416,NULL),
-- Computer
          ('Computer','serial','','serial',13,NULL),
          ('Computer','ifPhysAddress','','mac',15,NULL),
          ('Computer','ifaddr','','ip',407,NULL);
