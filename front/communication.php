<?php
/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * Copyright (C) 2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on FusionInventory for GLPI
 * Copyright (C) 2010-2021 by the FusionInventory Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI Inventory Plugin.
 *
 * GLPI Inventory Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   include_once("../../../inc/includes.php");
}

if (!class_exists("PluginGlpiinventoryConfig")) {
   header("Content-Type: application/xml");
   echo "<?xml version='1.0' encoding='UTF-8'?>
<REPLY>
   <ERROR>Plugin GLPI Inventory not installed!</ERROR>
</REPLY>";
   session_destroy();
   exit();
}

if (isset($_GET['action']) && isset($_GET['machineid'])) {
   ini_set("memory_limit", "-1");
   ini_set("max_execution_time", "0");
   ini_set('display_errors', 1);

   if (session_id()=="") {
      session_start();
   }

   $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
   if (!isset($_SESSION['glpilanguage'])) {
      $_SESSION['glpilanguage'] = 'fr_FR';
   }
   $_SESSION['glpi_glpiinventory_nolock'] = true;
   ini_set('display_errors', 'On');
   error_reporting(E_ALL | E_STRICT);
   $_SESSION['glpi_use_mode'] = 0;
   $_SESSION['glpiparententities'] = '';
   $_SESSION['glpishowallentities'] = true;

   header("server-type: glpi/glpiinventory ".PLUGIN_GLPI_INVENTORY_VERSION);

   PluginGlpiinventoryCommunicationRest::handleFusionCommunication();
} else {
   include_once  GLPI_ROOT . '/front/inventory.php';
}

session_destroy();
