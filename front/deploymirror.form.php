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
Session::checkLoginUser();

Html::header(__('Mirror servers'), $_SERVER["PHP_SELF"], "admin",
   "pluginfusioninventorymenu", "deploymirror");

//PluginFusioninventoryProfile::checkRight("Fusioninventory", "agents", "r");

PluginFusioninventoryMenu::displayMenu("mini");

$mirror = new PluginFusioninventoryDeployMirror();

if (isset ($_POST["add"])) {
   // PluginFusioninventoryProfile::checkRight("Fusinvdeloy", "package", "w");
   $newID = $mirror->add($_POST);
   Html::back();
} else if (isset ($_POST["update"])) {
   // PluginFusioninventoryProfile::checkRight("Fusinvdeloy", "package", "w");
   $mirror->update($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   // PluginFusioninventoryProfile::checkRight("Fusinvdeloy", "package", "w");
   $mirror->delete($_POST);
   Html::redirect(Toolbox::getItemTypeFormURL('PluginFusioninventoryDeployMirror'));
}

$id = "";
if (isset($_GET["id"])) {
   $id = $_GET["id"];
}
$mirror->display(['id' => $id]);
//$mirror->showForm($id);
Html::footer();

