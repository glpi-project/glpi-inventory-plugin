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
 * Manage the remote management information of softwares installed on computer.
 */
class PluginGlpiinventoryComputerRemoteManagement extends CommonDBTM {


   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'computer';


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb = 0) {
      return __('Remote management', 'glpiinventory');
   }


   /**
    * Display remote management information
    *
    * @param integer $computers_id
    * @return true
    */
   function showInformation($computers_id) {

      $pfRemoteManagement = new self();
      $a_remotemanagement = $pfRemoteManagement->find(['computers_id' => $computers_id]);

      if (count($a_remotemanagement)) {

         echo '<tr>';
         echo '<th colspan="4">'.__('Remote management', 'glpiinventory').'</th>';
         echo '</tr>';

         foreach ($a_remotemanagement as $remotemanagement) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$remotemanagement['type']."</td>";
            if ($remotemanagement['type'] == "teamviewer") {
               echo "<td><a href='https://start.teamviewer.com/".$remotemanagement['number']."'>".$remotemanagement['number']."</a></td>";
            } else {
               echo "<td>".$remotemanagement['number']."</td>";
            }
            echo "<td colspan='2'>";
            echo "</tr>";
         }
      }
      return true;
   }


   /**
    * Delete all remote management information linked to the computer
    * (most cases when delete a computer)
    *
    * @param integer $computers_id
    */
   static function cleanComputer($computers_id) {
      $pfComputerRemoteManagement = new self();
      $pfComputerRemoteManagement->deleteByCriteria(['computers_id' => $computers_id]);
   }


}
