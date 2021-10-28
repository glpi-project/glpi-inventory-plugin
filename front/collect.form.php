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
