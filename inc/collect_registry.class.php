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

use function Safe\preg_match;

/**
 * Manage the windows registry to get in collect module.
 */
class PluginGlpiinventoryCollect_Registry extends PluginGlpiinventoryCollectCommon
{
    public string $collect_type = 'registry';

    /**
     * Get name of this type by language of the user connected
     *
     * @param int $nb number of elements
     * @return string name of this type
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Found entry', 'Found entries', $nb, 'glpiinventory');
    }

    public static function getIcon()
    {
        return "ti ti-settings-search";
    }

    /**
     * Get Hives of the registry
     *
     * @return array<string,string> list of hives
     */
    public static function getHives(): array
    {
        return [
            "HKEY_LOCAL_MACHINE"  => "HKEY_LOCAL_MACHINE",
        ];
    }

    public function getListHeaders(): array
    {
        return [
            'hive' => __('Hive', 'glpiinventory'),
            'path' => __("Path", "glpiinventory"),
            'key' => __("Key", "glpiinventory"),
        ];
    }

    public function displayOneRow(array $row = []): array
    {
        return [
            'hive' => $row['hive'],
            'path' => $row['path'],
            'key' => $row['key'],
        ];
    }

    public function prepareInputForAdd($input)
    {
        if (!preg_match('/^\/()/', $input['path'])) {
            $input['path'] = "/" . $input['path'];
        }
        if (!preg_match('/\/$/', $input['path'])) {
            $input['path'] .= "/";
        }

        return parent::prepareInputForAdd($input);
    }
}
