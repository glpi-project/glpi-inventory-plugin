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
 * Manage the visibility of package by group.
 */
class PluginGlpiinventoryDeployPackage_Group extends CommonDBRelation {

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
   static public $itemtype_2          = 'Group';

   /**
    * id field name for the second part of relation
    *
    * @var string
    */
   static public $items_id_2          = 'groups_id';

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
    * Get groups for a deploypackage
    *
    * @global object $DB
    * @param integer $deploypackages_id ID of the deploypackage
    * @return array list of groups linked to a deploypackage
   **/
   static function getGroups($deploypackages_id) {
      global $DB;

      $groups = [];
      $query  = "SELECT `glpi_plugin_glpiinventory_deploypackages_groups`.*
                 FROM `glpi_plugin_glpiinventory_deploypackages_groups`
                 WHERE plugin_glpiinventory_deploypackages_id = '$deploypackages_id'";

      foreach ($DB->request($query) as $data) {
         $groups[$data['groups_id']][] = $data;
      }
      return $groups;
   }
}
