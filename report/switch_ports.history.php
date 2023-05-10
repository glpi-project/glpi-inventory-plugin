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

//Options for GLPI 0.71 and newer : need slave db to access the report
$USEDBREPLICATE = 1;
$DBCONNECTION_REQUIRED = 0;

define('GLPI_ROOT', '../../..');
include(GLPI_ROOT . "/inc/includes.php");

Html::header(__('GLPI Inventory', 'glpiinventory'), $_SERVER['PHP_SELF'], "utils", "report");

Session::checkRight('plugin_glpiinventory_reportnetworkequipment', READ);

$FK_port = filter_input(INPUT_GET, "networkports_id");

echo "<form action='" . $_SERVER['PHP_SELF'] . "' method='get'>";
echo "<table class='tab_cadre' cellpadding='5'>";
echo "<tr class='tab_bg_1' align='center'>";

echo "<td>";
echo _n('Network port', 'Network ports', 1) . " :&nbsp;";

$iterator = $DB->request([
    'SELECT' => [
        'glpi_networkequipments.name AS name',
        'glpi_networkports.name AS pname',
        'glpi_networkports.id AS id'
    ],
    'FROM'   => 'glpi_networkequipments',
    'LEFT JOIN'   => [
        'glpi_networkports' => [
            'FKEY' => [
                'glpi_networkequipments' => 'id',
                'glpi_networkports'      => 'items_id'
            ]
        ]
    ],
    'WHERE' => [
        'itemtype' => 'NetworkEquipment'
    ],
    'ORDER' => [
        'glpi_networkequipments.name',
        'glpi_networkports.logical_number'
    ]
]);

$selected = '';
foreach ($iterator as $data) {
    if (($data['id'] == $FK_port)) {
        $selected = $data['id'];
    }
    $ports[$data['id']] = $data['name'] . " - " . $data['pname'];
}

Dropdown::showFromArray(
    "networkports_id",
    $ports,
    ['value' => $selected]
);
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td align='center'>";
echo "<input type='submit' value='" . __('Validate')  . "' class='submit' />";
echo "</td>";
echo "</tr>";

echo "</table>";
Html::closeForm();

Html::footer();
