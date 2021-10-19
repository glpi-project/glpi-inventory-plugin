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

Session::checkRight('plugin_fusioninventory_configsecurity', READ);

$pfConfigSecurity = new PluginFusioninventoryConfigSecurity();
$config = new PluginFusioninventoryConfig();

Html::header(__('GLPI Inventory', 'glpiinventory'), $_SERVER["PHP_SELF"], "admin",
         "pluginfusioninventorymenu", "configsecurity");

PluginFusioninventoryMenu::displayMenu("mini");


if (isset ($_POST["add"])) {
   Session::checkRight('plugin_fusioninventory_configsecurity', CREATE);
   $new_ID = 0;
   $new_ID = $pfConfigSecurity->add($_POST);
   Html::back();
} else if (isset ($_POST["update"])) {
   Session::checkRight('plugin_fusioninventory_configsecurity', UPDATE);
   $pfConfigSecurity->update($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   Session::checkRight('plugin_fusioninventory_configsecurity', PURGE);
   $pfConfigSecurity->delete($_POST);
   Html::redirect("configsecurity.php");
}

$id = "";
if (isset($_GET["id"])) {
   $id = $_GET["id"];
}

if (strstr($_SERVER['HTTP_REFERER'], "wizard.php")) {
   Html::redirect($_SERVER['HTTP_REFERER']."&id=".$id);
}

$pfConfigSecurity->showForm($id);

Html::footer();

