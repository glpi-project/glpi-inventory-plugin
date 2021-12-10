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

include("../../../inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkCentralAccess();

$rand      = filter_input(INPUT_POST, "rand");
$mode      = filter_input(INPUT_POST, "mode");
$type      = filter_input(INPUT_POST, "type");
$classname = filter_input(INPUT_POST, "class");

if (empty($rand) && (empty($type))) {
    exit();
}
//Only process class that are related to software deployment
if (
    !class_exists($classname)
    || !in_array(
        $classname,
        ['PluginGlpiinventoryDeployCheck',
                'PluginGlpiinventoryDeployFile',
                'PluginGlpiinventoryDeployAction',
                'PluginGlpiinventoryDeployUserinteraction'
        ]
    )
) {
    exit();
}
$class        = new $classname();
$request_data = [
    'packages_id' => filter_input(INPUT_POST, "packages_id"),
    'orders_id'   => filter_input(INPUT_POST, "orders_id"),
    'value'       => filter_input(INPUT_POST, "value")
];
$class->displayAjaxValues(null, $request_data, $rand, $mode);
