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

Html::header(__('Collect management', 'glpiinventory'),
             $_SERVER["PHP_SELF"],
             "admin",
             "pluginglpiinventorymenu",
             "collect");

$pfCollect = new PluginGlpiinventoryCollect();

if (isset($_POST["add"])) {
   $collects_id = $pfCollect->add($_POST);
   Html::redirect(Toolbox::getItemTypeFormURL('PluginGlpiinventoryCollect').
           "?id=".$collects_id);
} else if (isset($_POST["update"])) {
   $pfCollect->update($_POST);
   Html::back();
} else if (isset($_REQUEST["purge"])) {
   $pfCollect->delete($_POST);
   $pfCollect->redirectToList();
}

PluginGlpiinventoryMenu::displayMenu("mini");

if (!isset($_GET["id"])) {
   $_GET['id'] = '';
}
$pfCollect->display($_GET);

Html::footer();

