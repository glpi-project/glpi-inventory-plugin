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

Session::checkRight('plugin_glpiinventory_configuration', READ);

Html::header(
    __('Features', 'glpiinventory'),
    '',
    "admin",
    "pluginglpiinventorymenu",
    "menu"
);


PluginGlpiinventoryMenu::displayMenu("mini");

$pfConfig = new PluginGlpiinventoryConfig();

if (isset($_POST['update'])) {
    Session::checkRight(PluginGlpiinventoryConfig::$rightname, UPDATE);
    $data = $_POST;
    unset($data['update']);
    unset($data['id']);
    foreach ($data as $key => $value) {
        $pfConfig->updateValue($key, $value);
    }
    Html::back();
}

$a_config = current($pfConfig->find([], [], 1));
$pfConfig->getFromDB($a_config['id']);
$pfConfig->display();

Html::footer();
