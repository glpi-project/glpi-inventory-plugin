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
 * GLPI Inventory Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use function Safe\json_decode;

Session::checkLoginUser();

$package = new PluginGlpiinventoryDeployPackage();
if (isset($_POST['update_json'])) {
    $json = json_decode($_POST['json'], true);
    $ret = PluginGlpiinventoryDeployPackage::updateOrderJson($_POST['packages_id'], $json);
    Html::back();
} elseif (isset($_POST['add_item'])) {
    PluginGlpiinventoryDeployPackage::alterJSON('add_item', $_POST);
    Html::back();
} elseif (isset($_POST['save_item'])) {
    PluginGlpiinventoryDeployPackage::alterJSON('save_item', $_POST);
    Html::back();
} elseif (isset($_POST['remove_item'])) {
    PluginGlpiinventoryDeployPackage::alterJSON('remove_item', $_POST);
    Html::back();
}

$data = $_POST;

//general form
if (isset($data["add"])) {
    Session::checkRight('plugin_glpiinventory_package', CREATE);
    $newID = $package->add($data);
    Html::redirect(Toolbox::getItemTypeFormURL('PluginGlpiinventoryDeployPackage') . "?id=" . $newID);
} elseif (isset($data["update"])) {
    Session::checkRight('plugin_glpiinventory_package', UPDATE);
    $package->update($data);
    Html::back();
} elseif (isset($data["purge"])) {
    Session::checkRight('plugin_glpiinventory_package', PURGE);
    $package->delete($data, true);
    $package->redirectToList();
} elseif (isset($_POST["addvisibility"])) {
    if (
        isset($_POST["_type"]) && !empty($_POST["_type"])
           && isset($_POST["plugin_glpiinventory_deploypackages_id"])
           && $_POST["plugin_glpiinventory_deploypackages_id"]
    ) {
        $item = null;
        switch ($_POST["_type"]) {
            case 'User':
                if (isset($_POST['users_id']) && $_POST['users_id']) {
                    $item = new PluginGlpiinventoryDeployPackage_User();
                }
                break;

            case 'Group':
                if (isset($_POST['groups_id']) && $_POST['groups_id']) {
                    $item = new PluginGlpiinventoryDeployPackage_Group();
                }
                break;

            case 'Profile':
                if (isset($_POST['profiles_id']) && $_POST['profiles_id']) {
                    $item = new PluginGlpiinventoryDeployPackage_Profile();
                }
                break;

            case 'Entity':
                $item = new PluginGlpiinventoryDeployPackage_Entity();
                break;
        }
        if (!is_null($item)) {
            $item->add($_POST);
        }
    }
    Html::back();
}

Html::header(
    __('GLPI Inventory DEPLOY'),
    $_SERVER["PHP_SELF"],
    "admin",
    "pluginglpiinventorymenu",
    "deploypackage"
);
PluginGlpiinventoryMenu::displayMenu("mini");
$id = "";
if (isset($_GET["id"])) {
    $id = $_GET["id"];
}
$package->display($_GET);
Html::footer();
