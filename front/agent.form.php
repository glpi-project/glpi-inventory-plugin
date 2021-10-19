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

include ("../../../inc/includes.php");

$agent = new PluginFusioninventoryAgent();

Session::checkRight('plugin_fusioninventory_agent', READ);

if (isset ($_POST["update"])) {
   Session::checkRight('plugin_fusioninventory_agent', UPDATE);
   if (isset($_POST['items_id'])) {
      if (($_POST['items_id'] != "0") AND ($_POST['items_id'] != "")) {
         $_POST['itemtype'] = '1';
      }
   }
   $agent->update($_POST);
   Html::back();
} else if (isset ($_POST["purge"])) {
   Session::checkRight('plugin_fusioninventory_agent', PURGE);
   $agent->delete($_POST, true);
   $agent->redirectToList();
} else if (isset ($_POST["disconnect"])) {
   Session::checkRight('plugin_fusioninventory_agent', UPDATE);
   $agent->disconnect($_POST);
   Html::back();
}


Html::header(__('GLPI Inventory', 'glpiinventory'), $_SERVER["PHP_SELF"], "admin",
             "pluginfusioninventorymenu", "agent");

PluginFusioninventoryMenu::displayMenu("mini");

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
