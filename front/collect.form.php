<?php

/**
 * FusionInventory
 *
 * Copyright (C) 2010-2016 by the FusionInventory Development Team.
 *
 * http://www.fusioninventory.org/
 * https://github.com/fusioninventory/fusioninventory-for-glpi
 * http://forge.fusioninventory.org/
 *
 * ------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of FusionInventory project.
 *
 * FusionInventory is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * FusionInventory is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with FusionInventory. If not, see <http://www.gnu.org/licenses/>.
 *
 * ------------------------------------------------------------------------
 *
 * This file is used to manage the collect form.
 *
 * ------------------------------------------------------------------------
 *
 * @package   FusionInventory
 * @author    David Durieux
 * @copyright Copyright (c) 2010-2016 FusionInventory team
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      http://www.fusioninventory.org/
 * @link      https://github.com/fusioninventory/fusioninventory-for-glpi
 *
 */

include ("../../../inc/includes.php");

Html::header(__('Collect management', 'glpiinventory'),
             $_SERVER["PHP_SELF"],
             "admin",
             "pluginfusioninventorymenu",
             "collect");

$pfCollect = new PluginFusioninventoryCollect();

if (isset($_POST["add"])) {
   $collects_id = $pfCollect->add($_POST);
   Html::redirect(Toolbox::getItemTypeFormURL('PluginFusioninventoryCollect').
           "?id=".$collects_id);
} else if (isset($_POST["update"])) {
   $pfCollect->update($_POST);
   Html::back();
} else if (isset($_REQUEST["purge"])) {
   $pfCollect->delete($_POST);
   $pfCollect->redirectToList();
}

PluginFusioninventoryMenu::displayMenu("mini");

if (!isset($_GET["id"])) {
   $_GET['id'] = '';
}
$pfCollect->display($_GET);

Html::footer();

