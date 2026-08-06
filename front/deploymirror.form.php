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
    __('Mirror servers'),
    '',
    "admin",
    "pluginglpiinventorymenu",
    "deploymirror"
);

PluginGlpiinventoryMenu::displayMenu("mini");

$mirror = new PluginGlpiinventoryDeployMirror();

if (isset($_POST["add"])) {
    Session::checkRight(PluginGlpiinventoryDeployMirror::$rightname, CREATE);
    $newID = $mirror->add($_POST);
    if ($_SESSION['glpibackcreated']) {
        Html::redirect($mirror->getLinkURL());
    } else {
        Html::back();
    }
} elseif (isset($_POST["update"])) {
    Session::checkRight(PluginGlpiinventoryDeployMirror::$rightname, UPDATE);
    $mirror->update($_POST);
    Html::back();
} elseif (isset($_POST["delete"])) {
    Session::checkRight(PluginGlpiinventoryDeployMirror::$rightname, PURGE);
    $mirror->delete($_POST);
    Html::redirect(Toolbox::getItemTypeFormURL(PluginGlpiinventoryDeployMirror::class));
}

$id = "";
if (isset($_GET["id"])) {
    $id = $_GET["id"];
}
$mirror->display(['id' => $id]);
//$mirror->showForm($id);
Html::footer();
