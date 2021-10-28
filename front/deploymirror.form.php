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

Html::header(__('Mirror servers'), $_SERVER["PHP_SELF"], "admin",
   "pluginglpiinventorymenu", "deploymirror");

PluginGlpiinventoryMenu::displayMenu("mini");

$mirror = new PluginGlpiinventoryDeployMirror();

if (isset ($_POST["add"])) {
   $newID = $mirror->add($_POST);
   Html::back();
} else if (isset ($_POST["update"])) {
   $mirror->update($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   $mirror->delete($_POST);
   Html::redirect(Toolbox::getItemTypeFormURL('PluginGlpiinventoryDeployMirror'));
}

$id = "";
if (isset($_GET["id"])) {
   $id = $_GET["id"];
}
$mirror->display(['id' => $id]);
//$mirror->showForm($id);
Html::footer();
