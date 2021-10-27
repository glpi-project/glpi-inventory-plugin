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
