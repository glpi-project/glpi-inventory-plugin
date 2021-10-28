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

include ("../../../inc/includes.php");

$agent = new PluginGlpiinventoryAgent();

Session::checkRight('plugin_glpiinventory_agent', READ);

if (isset ($_POST["update"])) {
   Session::checkRight('plugin_glpiinventory_agent', UPDATE);
   if (isset($_POST['items_id'])) {
      if (($_POST['items_id'] != "0") AND ($_POST['items_id'] != "")) {
         $_POST['itemtype'] = '1';
      }
   }
   $agent->update($_POST);
   Html::back();
} else if (isset ($_POST["purge"])) {
   Session::checkRight('plugin_glpiinventory_agent', PURGE);
   $agent->delete($_POST, true);
   $agent->redirectToList();
} else if (isset ($_POST["disconnect"])) {
   Session::checkRight('plugin_glpiinventory_agent', UPDATE);
   $agent->disconnect($_POST);
   Html::back();
}


Html::header(__('GLPI Inventory', 'glpiinventory'), $_SERVER["PHP_SELF"], "admin",
             "pluginglpiinventorymenu", "agent");

PluginGlpiinventoryMenu::displayMenu("mini");

if (isset($_GET["id"])) {
   $agent->display(
      [
         "id" => $_GET["id"]
      ]
   );
} else {
   $agent->display(
      [
         "id" => 0
      ]
   );
}

Html::footer();
