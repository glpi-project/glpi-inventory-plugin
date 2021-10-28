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
 * Manage the visibility of package by user.
 */
class PluginGlpiinventoryDeployPackage_User extends CommonDBRelation {

   /**
    * Itemtype for the first part of relation
    *
    * @var string
    */
   static public $itemtype_1          = 'PluginGlpiinventoryDeployPackage';

   /**
    * id field name for the first part of relation
    *
    * @var string
    */
   static public $items_id_1          = 'plugin_glpiinventory_deploypackages_id';

   /**
    * Itemtype for the second part of relation
    *
    * @var string
    */
   static public $itemtype_2          = 'User';

   /**
    * id field name for the second part of relation
    *
    * @var string
    */
   static public $items_id_2          = 'users_id';

   /**
    * Set we don't check parent right of the second item
    *
    * @var integer
    */
   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;

   /**
    * Logs for the second item are disabled
    *
    * @var type
    */
   static public $logs_for_item_2     = false;


   /**
    * Get users for a deploypackage
    *
    * @global object $DB
    * @param integer $deploypackages_id ID of the deploypackage
    * @return array list of users linked to a deploypackage
   **/
   static function getUsers($deploypackages_id) {
      global $DB;

      $users = [];
      $query = "SELECT `glpi_plugin_glpiinventory_deploypackages_users`.*
                FROM `glpi_plugin_glpiinventory_deploypackages_users`
                WHERE `plugin_glpiinventory_deploypackages_id` = '$deploypackages_id'";

      foreach ($DB->request($query) as $data) {
         $users[$data['users_id']][] = $data;
      }
      return $users;
   }
}
