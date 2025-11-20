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

use Glpi\DBAL\QueryParam;

/**
 * Manage the computer inventory stats (number of inventories arrived in
 * the plugin and regroued by hour).
 */
class PluginGlpiinventoryInventoryComputerStat extends CommonDBTM
{
    /**
     * The right name for this class
     *
     * @var string
     */
    public static $rightname = 'agent';


    /**
     * Get name of this type by language of the user connected
     *
     * @param integer $nb number of elements
     * @return string name of this type
     */
    public static function getTypeName($nb = 0)
    {
        return "Stat";
    }


    /**
     * Init stats
     */
    public static function init()
    {
        /** @var DBmysql $DB */
        global $DB;

        $insert = $DB->buildInsert(
            'glpi_plugin_glpiinventory_inventorycomputerstats',
            [
                'day'    => new QueryParam(),
                'hour'   => new QueryParam(),
            ]
        );
        $stmt = $DB->prepare($insert);

        for ($d = 1; $d <= 365; $d++) {
            for ($h = 0; $h < 24; $h++) {
                $stmt->bind_param(
                    'ss',
                    $d,
                    $h
                );
                $DB->executeStatement($stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }
}
