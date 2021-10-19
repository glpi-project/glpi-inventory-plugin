<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

ob_start();
ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");
ini_set('display_errors', 1);

if (session_id()=="") {
   session_start();
}

if (!defined('GLPI_ROOT')) {
   include_once("../../../inc/includes.php");
}
$_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
if (!isset($_SESSION['glpilanguage'])) {
   $_SESSION['glpilanguage'] = 'fr_FR';
}
$_SESSION['glpi_fusionionventory_nolock'] = true;
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
$_SESSION['glpi_use_mode'] = 0;
$_SESSION['glpiparententities'] = '';
$_SESSION['glpishowallentities'] = true;

ob_end_clean();
header("server-type: glpi/fusioninventory ".PLUGIN_GLPI_INVENTORY_VERSION);

if (!class_exists("PluginGlpiinventoryConfig")) {
   header("Content-Type: application/xml");
   echo "<?xml version='1.0' encoding='UTF-8'?>
<REPLY>
   <ERROR>Plugin GLPI Inventory not installed!</ERROR>
</REPLY>";
   session_destroy();
   exit();
}

$pfCommunication  = new PluginGlpiinventoryCommunication();

if (!isset($rawdata)) {
   $rawdata = file_get_contents("php://input");
}
if (isset($_GET['action']) && isset($_GET['machineid'])) {
   PluginGlpiinventoryCommunicationRest::handleFusionCommunication();
} else if (!empty($rawdata)) {
   $pfCommunication->handleOCSCommunication($rawdata);
}

session_destroy();

