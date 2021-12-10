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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Manage the agent modules.
 */
class PluginGlpiinventoryModule extends CommonDBTM
{


   /**
    * Get all installed modules
    *
    * @param boolean $p_inactive Show inactive modules
    * @return array
    */
    public static function getAll($p_inactive = false)
    {
        $plugin = new Plugin();
        if ($p_inactive) {
            return $plugin->find(['state' => [1, 4], 'directory' => ['LIKE', 'fusinv%']]);
        } else {
            return $plugin->find(['state' => 1, 'directory' => ['LIKE', 'fusinv%']]);
        }
    }


   /**
    * Get module id or plugin id
    *
    * @param string $p_name the module name
    * @return integer|false plugin id or FALSE if module is not active or not a module
    */
    public static function getModuleId($p_name)
    {
        $index = false;
        if (!isset($_SESSION['glpi_plugins'])) {
            return $index;
        }
        if ($p_name == 'fusioninventory' || $p_name == 'glpiinventory') {
            $index = array_search($p_name, $_SESSION['glpi_plugins']);
            if (!$index) {
                $plugin = new Plugin();
                $data = $plugin->find(['directory' => $p_name]);
                if (count($data)) {
                    $fields = current($data);
                    $index = $fields['id'];
                }
            }
        }
        return $index;
    }


   /**
    * Get module name
    *
    * @param integer $p_id the module id
    * @return string|false false if module is not active or not a module
    */
    public static function getModuleName($p_id)
    {
        if (isset($_SESSION['glpi_plugins'][$p_id])) {
            if (
                (substr($_SESSION['glpi_plugins'][$p_id], 0, 6) == 'fusinv')
                or ($_SESSION['glpi_plugins'][$p_id] == 'glpiinventory')
            ) {
                return $_SESSION['glpi_plugins'][$p_id];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
