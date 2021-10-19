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

Html::header(__('GLPI Inventory', 'glpiinventory'),
             $_SERVER["PHP_SELF"],
             "admin",
             "pluginfusioninventorymenu",
             "inventorycomputerblacklist");

Session::checkRight('plugin_fusioninventory_blacklist', READ);

PluginFusioninventoryMenu::displayMenu("mini");

$pfInventoryComputerBlacklist = new PluginFusioninventoryInventoryComputerBlacklist();

if (isset ($_POST["add"])) {
   Session::checkRight('plugin_fusioninventory_blacklist', CREATE);
   if (!empty($_POST['value'])) {
      $pfInventoryComputerBlacklist->add($_POST);
   }
   Html::back();
} else if (isset ($_POST["update"])) {
   Session::checkRight('plugin_fusioninventory_blacklist', UPDATE);
   $pfInventoryComputerBlacklist->update($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   Session::checkRight('plugin_fusioninventory_blacklist', PURGE);
   $pfInventoryComputerBlacklist->delete($_POST);
   Html::redirect("blacklist.php");
}

if (isset($_GET["id"])) {
   $pfInventoryComputerBlacklist->showForm($_GET["id"]);
} else {
   $pfInventoryComputerBlacklist->showForm("");
}

Html::footer();

