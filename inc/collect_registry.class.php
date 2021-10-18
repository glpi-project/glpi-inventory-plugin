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
 * Manage the windows registry to get in collect module.
 */
class PluginFusioninventoryCollect_Registry extends PluginFusioninventoryCollectCommon {

   public $type = 'registry';

   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb = 0) {
      return _n('Found entry', 'Found entries', $nb, 'glpiinventory');
   }

   /**
    * Get Hives of the registry
    *
    * @return array list of hives
    */
   static function getHives() {
      return [
         "HKEY_LOCAL_MACHINE"  => "HKEY_LOCAL_MACHINE",
      ];
   }

   function getListHeaders() {
      return [
         __('Name'),
         __('Hive', 'glpiinventory'),
         __("Path", "fusioninventory"),
         __("Key", "fusioninventory"),
         __("Action")
      ];
   }

   function displayOneRow($row = []) {
      return [
         $row['name'],
         $row['hive'],
         $row['path'],
         $row['key']
      ];
   }

   function displayNewSpecificities() {
      echo "<td>".__('Hive', 'glpiinventory')."</td>";
      echo "<td>";
      Dropdown::showFromArray('hive',
                              PluginFusioninventoryCollect_Registry::getHives());
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Path', 'glpiinventory');
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='path' value='' size='80' />";
      echo "</td>";
      echo "<td>";
      echo __('Key', 'glpiinventory');
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='key' value='' />";
      echo "</td>";
   }


}

