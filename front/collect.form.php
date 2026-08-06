<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * @basedon   FusionInventory for GLPI
 * @copyright 2021-2026 Teclib' and contributors.
 * @copyright 2010-2021 by the FusionInventory Development Team.
 *
 * http://glpi-project.org
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
 * GLPI Inventory Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

Session::checkLoginUser();

Html::header(
    __('Collect management', 'glpiinventory'),
    '',
    "admin",
    "pluginglpiinventorymenu",
    "collect"
);

$pfCollect = new PluginGlpiinventoryCollect();

if (isset($_POST["add"])) {
    Session::checkRight(PluginGlpiinventoryCollect::$rightname, CREATE);
    $collects_id = $pfCollect->add($_POST);
    Html::redirect(Toolbox::getItemTypeFormURL(PluginGlpiinventoryCollect::class)
           . "?id=" . $collects_id);
} elseif (isset($_POST["update"])) {
    Session::checkRight(PluginGlpiinventoryCollect::$rightname, UPDATE);
    $pfCollect->update($_POST);
    Html::back();
} elseif (isset($_REQUEST["purge"])) {
    Session::checkRight(PluginGlpiinventoryCollect::$rightname, PURGE);
    $pfCollect->delete($_POST);
    $pfCollect->redirectToList();
}

PluginGlpiinventoryMenu::displayMenu("mini");

if (!isset($_GET["id"])) {
    $_GET['id'] = '';
}
$pfCollect->display($_GET);

Html::footer();
