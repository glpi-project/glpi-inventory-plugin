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

include("../../../inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkCentralAccess();

$fi_move_item = filter_input(INPUT_POST, "move_item");
if (!empty($fi_move_item)) { //ajax request
    $json_response = ["success" => true, "reason"  => ''];

    if (Session::haveRight('plugin_glpiinventory_package', UPDATE)) {
        $params = [
                  'old_index' => filter_input(INPUT_POST, "old_index"),
                  'new_index' => filter_input(INPUT_POST, "new_index"),
                  'id'        => filter_input(INPUT_POST, "id")
               ];
        $itemtype = filter_input(INPUT_POST, "itemtype");
        if (class_exists($itemtype)) {
            $item = new $itemtype();
            $item->move_item($params);
        } else {
            Toolbox::logDebug("package subtype not found : " . $params['itemtype']);
            Html::displayErrorAndDie("package subtype not found");
        }
    } else {
        $json_response['success'] = false;
        $json_response['reason']  = __('Package modification is forbidden by your profile.');
    }

    echo json_encode($json_response);
   //exit;
} else {
    $packages_id = filter_input(INPUT_POST, "packages_id");
    $rand       = filter_input(INPUT_POST, "rand");
    $mode       = filter_input(INPUT_POST, "mode");
    $fi_subtype = filter_input(INPUT_POST, "subtype");
    if (
        empty($packages_id) && empty($rand)
           && empty($fi_subtype)
    ) {
        exit;
    }

    if (!is_numeric($packages_id)) {
        Toolbox::logDebug("Error: orders_id in request is not an integer");
        Toolbox::logDebug(print_r($packages_id, true));
        exit;
    }

    $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
    $pfDeployPackage->getFromDB($packages_id);

   //TODO: In the displayForm function, $_REQUEST is somewhat too much for the '$datas' parameter
   // I think we could use only $order -- Kevin 'kiniou' Roy
    $input = [
             'index'       => filter_input(INPUT_POST, "index"),
             'value'       => filter_input(INPUT_POST, "value"),
             'packages_id' => filter_input(INPUT_POST, "packages_id"),
             'orders_id'   => filter_input(INPUT_POST, "orders_id"),
            ];
    $itemtype = filter_input(INPUT_POST, "subtype");
    switch (filter_input(INPUT_POST, "subtype")) {
        case 'package_json_debug':
            if (isset($order->fields['json'])) {
                $pfDeployPackage->displayJSONDebug();
            } else {
                echo "{}";
            }
            break;
        default:
            $classname = 'PluginGlpiinventoryDeploy' . ucfirst($itemtype);
            $class     = new $classname();
            $class->displayForm($pfDeployPackage, $input, $rand, $mode);
            break;
    }
}
