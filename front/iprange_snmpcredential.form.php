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

$pfIPRange_ConfigSecurity = new PluginGlpiinventoryIPRange_SNMPCredential();

if (isset($_POST["add"])) {
    $a_data = current(
        getAllDataFromTable(
            PluginGlpiinventoryIPRange_SNMPCredential::getTable(),
            [
            'WHERE' => [
               'plugin_glpiinventory_ipranges_id' => $_POST['plugin_glpiinventory_ipranges_id']
            ],
            'ORDER' => 'rank DESC'
            ]
        )
    );
    $_POST['rank'] = 1;
    if (isset($a_data['rank'])) {
        $_POST['rank'] = $a_data['rank'] + 1;
    }
    $pfIPRange_ConfigSecurity->add($_POST);
    Html::back();
}
