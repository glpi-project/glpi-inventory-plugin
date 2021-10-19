<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Manage the visibility of package by user.
 */
class PluginFusioninventoryDeployPackage_User extends CommonDBRelation {

   /**
    * Itemtype for the first part of relation
    *
    * @var string
    */
   static public $itemtype_1          = 'PluginFusioninventoryDeployPackage';

   /**
    * id field name for the first part of relation
    *
    * @var string
    */
   static public $items_id_1          = 'plugin_fusioninventory_deploypackages_id';

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
      $query = "SELECT `glpi_plugin_fusioninventory_deploypackages_users`.*
                FROM `glpi_plugin_fusioninventory_deploypackages_users`
                WHERE `plugin_fusioninventory_deploypackages_id` = '$deploypackages_id'";

      foreach ($DB->request($query) as $data) {
         $users[$data['users_id']][] = $data;
      }
      return $users;
   }
}
