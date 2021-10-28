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
 * Manage the list of devices ignored on import.
 * Mean when device go in import rules, rules say "ignore import this device
 * because I don't want it"
 */
class PluginGlpiinventoryIgnoredimportdevice extends CommonDBTM {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_glpiinventory_ignoredimportdevice';


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb = 0) {
      return __('Equipment ignored on import', 'glpiinventory');
   }


   /**
    * Get search function for the class
    *
    * @return array
    */
   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id' => 'common',
         'name' => __('Agent', 'glpiinventory')
      ];

      $tab[] = [
         'id'            => '1',
         'table'         => $this->getTable(),
         'field'         => 'name',
         'name'          => __('Name'),
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '2',
         'table'         => 'glpi_rules',
         'field'         => 'id',
         'name'          => __('Rule name', 'glpiinventory'),
         'datatype'      => 'itemlink',
         'itemlink_type' => 'PluginGlpiinventoryInventoryRuleImport',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '3',
         'table'         => $this->getTable(),
         'field'         => 'date',
         'name'          => _n('Date', 'Dates', 1),
         'datatype'      => 'datetime',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '4',
         'table'         => $this->getTable(),
         'field'         => 'itemtype',
         'name'          => __('Item type'),
         'massiveaction' => false,
         'datatype'      => 'itemtypename',
      ];

      $tab[] = [
         'id'            => '5',
         'table'         => 'glpi_entities',
         'field'         => 'completename',
         'name'          => Entity::getTypeName(1),
         'massiveaction' => false,
         'datatype'      => 'dropdown',
      ];

      $tab[] = [
         'id'            => '6',
         'table'         => $this->getTable(),
         'field'         => 'serial',
         'name'          => __('Serial number'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '7',
         'table'         => $this->getTable(),
         'field'         => 'uuid',
         'name'          => __('UUID'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '8',
         'table'         => $this->getTable(),
         'field'         => 'ip',
         'name'          => __('IP'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '9',
         'table'         => $this->getTable(),
         'field'         => 'mac',
         'name'          => __('MAC'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '10',
         'table'         => $this->getTable(),
         'field'         => 'method',
         'name'          => __('Module', 'glpiinventory'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '11',
         'table'         => 'glpi_plugin_glpiinventory_agents',
         'field'         => 'name',
         'name'          => __('Agent', 'glpiinventory'),
         'datatype'      => 'itemlink',
         'massiveaction' => false,
         'itemlink_type' => 'PluginGlpiinventoryAgent',
      ];

      return $tab;
   }


   /**
    * Get search parameters for default search / display list
    *
    * @return array
    */
   static function getDefaultSearchRequest() {
      return ['sort'  => 3,
                   'order' => 'DESC'];
   }
}
