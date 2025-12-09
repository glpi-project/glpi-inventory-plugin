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

/**
 * Manage the wmi to get in collect module.
 */
class PluginGlpiinventoryCollect_Wmi extends PluginGlpiinventoryCollectCommon
{
    public $collect_type = 'wmi';

    /**
     * Get name of this type by language of the user connected
     *
     * @param integer $nb number of elements
     * @return string name of this type
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Found WMI', 'Found WMIs', $nb, 'glpiinventory');
    }

    public function getListHeaders()
    {
        return [
            'moniker' => "Moniker",
            'class' => __("Class", "glpiinventory"),
            'properties' => __("Properties", "glpiinventory"),
        ];
    }

    public function displayOneRow($row = [])
    {
        return [
            'moniker' => $row['moniker'],
            'class' => $row['class'],
            'properties' => $row['properties'],
        ];
    }
}
