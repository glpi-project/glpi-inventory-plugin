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

Session::checkRight('plugin_glpiinventory_configuration', READ);

Html::header(
    __('Features', 'glpiinventory'),
    $_SERVER["PHP_SELF"],
    "admin",
    "pluginglpiinventorymenu",
    "config"
);


PluginGlpiinventoryMenu::displayMenu("mini");

$pfConfig = new PluginGlpiinventoryConfig();

if (isset($_POST['update'])) {
    $data = $_POST;
    unset($data['update']);
    unset($data['id']);
    unset($data['_glpi_csrf_token']);
    foreach ($data as $key => $value) {
        $pfConfig->updateValue($key, $value);
    }
    Html::back();
}

$a_config = current($pfConfig->find([], [], 1));
$pfConfig->getFromDB($a_config['id']);
$pfConfig->display();

Html::footer();
