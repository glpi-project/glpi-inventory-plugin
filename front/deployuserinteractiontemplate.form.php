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

include ("../../../inc/includes.php");
Session::checkLoginUser();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$template = new PluginGlpiinventoryDeployUserinteractionTemplate();
//general form
if (isset ($_POST["add"])) {
   Session::checkRight('plugin_glpiinventory_userinteractiontemplate', CREATE);
   $newID = $template->add($_POST);
   Html::redirect($template->getFormURLWithID($newID));
} else if (isset ($_POST["update"])) {
   Session::checkRight('plugin_glpiinventory_userinteractiontemplate', UPDATE);
   $template->update($_POST);
   Html::back();
} else if (isset ($_POST["purge"])) {
   Session::checkRight('plugin_glpiinventory_userinteractiontemplate', PURGE);
   $template->delete($_POST, 1);
   $template->redirectToList();
}

if (isset($_GET['_in_modal']) && $_GET['_in_modal']) {
   Html::nullHeader(__('GLPI Inventory DEPLOY'), $_SERVER["PHP_SELF"]);
} else {
   Html::header(__('GLPI Inventory DEPLOY'), $_SERVER["PHP_SELF"], "admin",
      "pluginglpiinventorymenu", "deployuserinteractiontemplate");
   PluginGlpiinventoryMenu::displayMenu("mini");
}
$template->display($_GET);
if (isset($_GET['_in_modal']) && $_GET['_in_modal']) {
   Html::nullFooter();
} else {
   Html::footer();
}
