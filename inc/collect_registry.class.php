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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Manage the windows registry to get in collect module.
 */
class PluginGlpiinventoryCollect_Registry extends PluginGlpiinventoryCollectCommon
{
    public $type = 'registry';

   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
    public static function getTypeName($nb = 0)
    {
        return _n('Found entry', 'Found entries', $nb, 'glpiinventory');
    }

   /**
    * Get Hives of the registry
    *
    * @return array list of hives
    */
    public static function getHives()
    {
        return [
         "HKEY_LOCAL_MACHINE"  => "HKEY_LOCAL_MACHINE",
        ];
    }

    public function getListHeaders()
    {
        return [
         __('Name'),
         __('Hive', 'glpiinventory'),
         __("Path", "glpiinventory"),
         __("Key", "glpiinventory"),
         __("Action")
        ];
    }

    public function displayOneRow($row = [])
    {
        return [
         $row['name'],
         $row['hive'],
         $row['path'],
         $row['key']
        ];
    }

    public function displayNewSpecificities()
    {
        echo "<td>" . __('Hive', 'glpiinventory') . "</td>";
        echo "<td>";
        Dropdown::showFromArray(
            'hive',
            PluginGlpiinventoryCollect_Registry::getHives()
        );
        echo "</td>";
        echo "</tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Path', 'glpiinventory');
        echo "</td>";
        echo "<td>";
        echo "<input type='text' name='path' value='' size='80' />";
        echo "</td>";
        echo "<td>";
        echo __('Key', 'glpiinventory');
        echo "</td>";
        echo "<td>";
        echo "<input type='text' name='key' value='' />";
        echo "</td>";
    }
}
