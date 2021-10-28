<?php
/**
 *  * ---------------------------------------------------------------------
 *  * GLPI Inventory Plugin
 *  * Copyright (C) 2021 Teclib' and contributors.
 *  *
 *  * http://glpi-project.org
 *  *
 *  * based on FusionInventory for GLPI
 *  * Copyright (C) 2010-2021 by the FusionInventory Development Team.
 *  *
 *  * ---------------------------------------------------------------------
 *  *
 *  * LICENSE
 *  *
 *  * This file is part of GLPI Inventory Plugin.
 *  *
 *  * GLPI Inventory Plugin is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as published by
 *  * the Free Software Foundation, either version 3 of the License, or
 *  * (at your option) any later version.
 *  *
 *  * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  * GNU Affero General Public License for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 *  * ---------------------------------------------------------------------
 */

$USEDBREPLICATE=1;
$DBCONNECTION_REQUIRED=0;

include ("../../../inc/includes.php");

Html::header(__('GLPI Inventory', 'glpiinventory'),
             $_SERVER["PHP_SELF"],
             "admin",
             "pluginglpiinventorymenu",
             "printerlogreport");

Session::checkRight('plugin_glpiinventory_reportprinter', READ);

if (isset($_POST['glpi_plugin_glpiinventory_date_start'])) {
   $_SESSION['glpi_plugin_glpiinventory_date_start'] =
                                 $_POST['glpi_plugin_glpiinventory_date_start'];
}
if (isset($_POST['glpi_plugin_glpiinventory_date_end'])) {
   $_SESSION['glpi_plugin_glpiinventory_date_end'] =
                                 $_POST['glpi_plugin_glpiinventory_date_end'];
}

if (isset($_POST['reset'])) {
   unset($_SESSION['glpi_plugin_glpiinventory_date_start']);
   unset($_SESSION['glpi_plugin_glpiinventory_date_end']);
}

if ((!isset($_SESSION['glpi_plugin_glpiinventory_date_start']))
       OR (empty($_SESSION['glpi_plugin_glpiinventory_date_start']))) {
   $_SESSION['glpi_plugin_glpiinventory_date_start'] = "2000-01-01";
}
if (!isset($_SESSION['glpi_plugin_glpiinventory_date_end'])) {
   $_SESSION['glpi_plugin_glpiinventory_date_end'] = date("Y-m-d");
}

displaySearchForm();

$_GET['target']="printerlogreport.php";

Search::show('PluginGlpiinventoryPrinterLogReport');


/**
 * Display special search form
 *
 * @global array $_SERVER
 */
function displaySearchForm() {
   global $_SERVER;

   echo "<form action='".$_SERVER["PHP_SELF"]."' method='post'>";
   echo "<table class='tab_cadre' cellpadding='5'>";
   echo "<tr class='tab_bg_1' align='center'>";
   echo "<td>";
   echo __('Starting date', 'glpiinventory')." :";
   echo "</td>";
   echo "<td width='120'>";
   Html::showDateField("glpi_plugin_glpiinventory_date_start",
                       ['value' => $_SESSION['glpi_plugin_glpiinventory_date_start']]);
   echo "</td>";

   echo "<td>";
   echo __('Ending date', 'glpiinventory')." :";
   echo "</td>";
   echo "<td width='120'>";
   Html::showDateField("glpi_plugin_glpiinventory_date_end",
                       ['value' => $_SESSION['glpi_plugin_glpiinventory_date_end']]);
   echo "</td>";

   echo "<td>";
   echo "<input type='submit' name='reset' value='reset' class='submit' />";
   echo "</td>";

   echo "<td>";
   echo "<input type='submit' value='". __('Validate') . "' class='submit' />";
   echo "</td>";

   echo "</tr>";
   echo "</table>";
   Html::closeForm();
}


Html::footer();
