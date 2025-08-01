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
 * Manage the visibility of package by entity.
 */
class PluginGlpiinventoryDeployPackage_Entity extends CommonDBRelation
{
    /**
     * Itemtype for the first part of relation
     *
     * @var string
     */
    public static $itemtype_1 = 'PluginGlpiinventoryDeployPackage';

    /**
     * id field name for the first part of relation
     *
     * @var string
     */
    public static $items_id_1 = 'plugin_glpiinventory_deploypackages_id';

    /**
     * Itemtype for the second part of relation
     *
     * @var string
     */
    public static $itemtype_2 = 'Entity';

    /**
     * id field name for the second part of relation
     *
     * @var string
     */
    public static $items_id_2 = 'entities_id';

    /**
     * Set we don't check parent right of the second item
     *
     * @var integer
     */
    public static $checkItem_2_Rights = self::DONT_CHECK_ITEM_RIGHTS;

    /**
     * Logs for the second item are disabled
     *
     * @var boolean
     */
    public static $logs_for_item_2 = false;


    /**
     * Get entities for a deploypackage
     *
     * @global object $DB
     * @param integer $deploypackages_id ID of the deploypackage
     * @return array list of of entities linked to a deploypackage
    **/
    public static function getEntities($deploypackages_id)
    {
        global $DB;

        $ent   = [];

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'plugin_glpiinventory_deploypackages_id' => $deploypackages_id,
            ],
        ]);

        foreach ($iterator as $data) {
            $ent[$data['entities_id']][] = $data;
        }
        return $ent;
    }
}
