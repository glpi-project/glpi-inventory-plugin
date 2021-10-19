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
 * Manage the wmi to get in collect module.
 */
class PluginGlpiinventoryCollect_Wmi extends PluginGlpiinventoryCollectCommon {

   public $type = 'wmi';

   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb = 0) {
      return _n('Found WMI', 'Found WMIs', $nb, 'glpiinventory');
   }

   function getListHeaders() {
      return [
         __("Name"),
         __("Moniker", "glpiinventory"),
         __("Class", "glpiinventory"),
         __("Properties", "glpiinventory"),
         __("Action")
      ];
   }

   function displayOneRow($row = []) {
      return [
         $row['name'],
         $row['moniker'],
         $row['class'],
         $row['properties']
      ];
   }

   function displayNewSpecificities() {
      echo "<td>".__('moniker', 'glpiinventory')."</td>";
      echo "<td>";
      echo "<input type='text' name='moniker' value='' size='50' />";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Class', 'glpiinventory');
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='class' value='' />";
      echo "</td>";
      echo "<td>";
      echo __('Properties', 'glpiinventory');
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='properties' value='' size='50' />";
      echo "</td>";
   }
}
