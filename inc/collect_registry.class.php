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

    public const MODE_DEFAULT = 0;

    public const MODE_PATH_EXISTS = 1;

    public const MODE_KEY_DEFINED = 2;

    /**
     * Case 3: read all the values found under the path, recursively down to the
     * configured depth (depth 0 = only the values at the path, depth 1 = also the
     * sub-keys of first level, etc.).
     */
    public const MODE_DEPTH = 3;

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

    /**
     * Get the available collect modes.
     *
     * @return array<int,string> list of modes [value => label]
     */
    public static function getModes(): array
    {
        return [
            self::MODE_DEFAULT     => __('Default', 'glpiinventory'),
            self::MODE_PATH_EXISTS => __('Check path existence', 'glpiinventory'),
            self::MODE_KEY_DEFINED => __('Check if a key is defined', 'glpiinventory'),
            self::MODE_DEPTH       => __('All values', 'glpiinventory'),
        ];
    }

    public function getListHeaders(): array
    {
        return [
            'hive' => __('Hive', 'glpiinventory'),
            'path' => __("Path", "glpiinventory"),
            'key' => __("Key", "glpiinventory"),
            'mode' => __("Mode", "glpiinventory"),
            'depth' => __("Recursion depth", "glpiinventory"),
        ];
    }

    public function displayOneRow(array $row = []): array
    {
        $modes = self::getModes();
        $mode  = (int) ($row['mode'] ?? self::MODE_DEFAULT);
        return [
            'hive' => $row['hive'],
            'path' => $row['path'],
            // the key is not relevant when checking the path existence or reading recursively
            'key'  => in_array($mode, [self::MODE_PATH_EXISTS, self::MODE_DEPTH], true) ? '' : $row['key'],
            'mode' => $modes[$mode] ?? $modes[self::MODE_DEFAULT],
            // the All value only applies to the depth mode
            'depth' => ($mode === self::MODE_DEPTH) ? (int) ($row['depth'] ?? 0) : '',
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

        return parent::prepareInputForAdd($this->normalizeModeInput($input));
    }

    /**
     * Normalize the "defined" flag and recursion "depth" according to the selected mode.
     *
     * - "defined" is a boolean flag driven by the MODE_KEY_DEFINED mode (case 2).
     * - "depth" (All value) only applies to the MODE_DEPTH mode (case 3); it is
     *   forced to 0 for the other modes.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private function normalizeModeInput(array $input): array
    {
        $mode = (int) ($input['mode'] ?? self::MODE_DEFAULT);
        $input['mode'] = $mode;

        // case 2: the "key is defined" check is requested through the mode
        $input['defined'] = ($mode === self::MODE_KEY_DEFINED) ? 1 : 0;

        // All value is only meaningful in the depth mode (case 3)
        $input['depth'] = ($mode === self::MODE_DEPTH) ? max(0, (int) ($input['depth'] ?? 0)) : 0;

        return $input;
    }
}
