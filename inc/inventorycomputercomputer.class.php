<?php
/**
 *  * ---------------------------------------------------------------------
 *  * GLPI Inventory Plugin
 *  * Copyright (C) 2021 Teclib' and contributors.
 *  *
 *  * http://glpi-project.org
 *  *
 *  * based on FusionInventory for GLPI
 *  * Copyright (C) 2010-2021 by the FusionInventory Development Team.
 *  *
 *  * ---------------------------------------------------------------------
 *  *
 *  * LICENSE
 *  *
 *  * This file is part of GLPI Inventory Plugin.
 *  *
 *  * GLPI Inventory Plugin is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as published by
 *  * the Free Software Foundation, either version 3 of the License, or
 *  * (at your option) any later version.
 *  *
 *  * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  * GNU Affero General Public License for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 *  * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Manage the extended information of a computer.
 */
class PluginGlpiinventoryInventoryComputerComputer extends PluginGlpiinventoryItem {


   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'computer';

   public $itemtype  = 'Computer';

   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb = 0) {
      return "";
   }


   /**
    * Display information about computer (bios, last contact...)
    *
    * @global array $CFG_GLPI
    * @param object $item
    * @return true
    */
   static function showAgentInfo($item) {
      global $CFG_GLPI;

      $pfInventoryComputerComputer = new PluginGlpiinventoryInventoryComputerComputer();
      $a_computerextend = current($pfInventoryComputerComputer->find(['computers_id' => $item->getID()], [], 1));
      if (empty($a_computerextend)) {
         return;
      }

      echo '<table class="tab_glpi" width="100%">';

      $pfAgent = new PluginGlpiinventoryAgent();
      $pfAgent->showInfoForComputer($item);

      if ($a_computerextend['bios_date'] != '') {
         echo '<tr class="tab_bg_1">';
         echo '<td>'.__('BIOS date', 'glpiinventory').'</td>';
         echo '<td>'.Html::convDate($a_computerextend['bios_date']).'</td>';
         echo '</tr>';
      }

      if ($a_computerextend['bios_version'] != '') {
         echo '<tr class="tab_bg_1">';
         echo '<td>'.__('BIOS version', 'glpiinventory').'</td>';
         echo '<td>'.$a_computerextend['bios_version'].'</td>';
         echo '</tr>';
      }

      if ($a_computerextend['bios_manufacturers_id'] > 0) {
         echo '<tr class="tab_bg_1">';
         echo '<td>'.Manufacturer::getTypeName(1).'&nbsp;:</td>';
         echo '<td>';
         echo Dropdown::getDropdownName("glpi_manufacturers",
                                        $a_computerextend['bios_manufacturers_id']);
         echo '</td>';
         echo '</tr>';
      }

      if ($a_computerextend['operatingsystem_installationdate'] != '') {
         echo '<tr class="tab_bg_1">';
         echo "<td>".OperatingSystem::getTypeName(1)." - ".__('Installation')." (".
                 strtolower(_n('Date', 'Dates', 1)).")</td>";
         echo '<td>'.Html::convDate($a_computerextend['operatingsystem_installationdate']).'</td>';
         echo '</tr>';
      }

      if ($a_computerextend['winowner'] != '') {
         echo '<tr class="tab_bg_1">';
         echo '<td>'.__('Owner', 'glpiinventory').'</td>';
         echo '<td>'.$a_computerextend['winowner'].'</td>';
         echo '</tr>';
      }

      if ($a_computerextend['wincompany'] != '') {
         echo '<tr class="tab_bg_1">';
         echo '<td>'.__('Company', 'glpiinventory').'</td>';
         echo '<td>'.$a_computerextend['wincompany'].'</td>';
         echo '</tr>';
      }
      return true;
   }


   /**
    * Display information about computer that is linked to an agent but
    * has no inventory
    *
    * @since 9.2
    * @param object $item
    * @return true
    */
   static function showFormForAgentWithNoInventory($item) {
      $id = $item->getID();
      $pfComputer  = new self();
      if ($item->isNewItem()
          || !empty($pfComputer->hasAutomaticInventory($id))) {
         return true;
      } else {
         $pfAgent = new PluginGlpiinventoryAgent();
         if ($pfAgent->getAgentWithComputerid($id)) {
            echo '<tr>';
            echo '<td colspan=\'4\'></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<th colspan="4">'.__('GLPI Inventory', 'glpiinventory').'</th>';
            echo '</tr>';
            $pfAgent->showInfoForComputer($item, 4);
         }
         return true;
      }
   }


   /**
    * Display information about a computer operating system
    * has no inventory
    *
    * @since 9.2
    * @param object $item
    * @return true
    */
   static function showFormOS($item) {
      $pfComputer = new self();
      $a_computerextend = current(
         $pfComputer->find(['computers_id' => $item->fields['items_id']], [], 1)
      );
      if (empty($a_computerextend)) {
         return;
      }

      echo '<tr class="tab_bg_1">';
      echo "<th colspan='4'></th>";
      echo "</tr>";

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Company', 'glpiinventory').'</td>';
      echo '<td>'.$a_computerextend['wincompany'].'</td>';

      echo '<td>'.__('Owner', 'glpiinventory').'</td>';
      echo '<td>'.$a_computerextend['winowner'].'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo "<td>".__('Comments')."</td>";
      echo '<td>'.$a_computerextend['oscomment'].'</td>';

      echo "<td>".__("Installation date")."</td>";
      echo '<td>'.Html::convDate($a_computerextend['operatingsystem_installationdate']).'</td>';

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('HostID', 'glpiinventory')."</td>";
      echo "<td>";
      echo $a_computerextend['hostid'];
      echo "</td><colspan='2'></td>";
      echo "</tr>";

      return true;
   }


   /**
    * Display information about computer (bios, last contact...)
    *
    * @global array $CFG_GLPI
    * @param object $item
    * @return true
    */
   static function showComputerInfo($item) {
      $fi_path = Plugin::getWebDir('glpiinventory');

      // Manage locks pictures
      PluginGlpiinventoryLock::showLockIcon('Computer');

      $pfInventoryComputerComputer = new PluginGlpiinventoryInventoryComputerComputer();
      $a_computerextend = $pfInventoryComputerComputer->hasAutomaticInventory($item->getID());
      if (empty($a_computerextend)) {
         return true;
      }

      echo '<table class="tab_glpi" width="100%">';

      echo '<tr>';
      echo '<th colspan="4">'.__('GLPI Inventory', 'glpiinventory').'</th>';
      echo '</tr>';

      $pfAgent = new PluginGlpiinventoryAgent();
      $pfAgent->showInfoForComputer($item, 4);

      echo '<tr class="tab_bg_1">';
      if ($a_computerextend['remote_addr'] != '') {
         echo '<td>'.__('Public contact address', 'glpiinventory').'</td>';
         echo '<td>'.$a_computerextend['remote_addr'].'</td>';
      } else {
         echo "<td colspan='2'></td>";
      }

      echo '<td>';
      echo __('Last inventory', 'glpiinventory');
      echo '</td>';
      echo '<td>';
      echo Html::convDateTime($a_computerextend['last_inventory_update']);
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      // Display automatic entity transfer
      if (Session::isMultiEntitiesMode()) {
         echo '<td>'.__('Automatic entity transfer', 'glpiinventory').'</td>';
         echo '<td>';
         $pfEntity = new PluginGlpiinventoryEntity();
         if ($pfEntity->getValue('transfers_id_auto', $item->fields['entities_id']) == 0) {
            echo __('No, locked (by entity configuration)', 'glpiinventory');
         } else {
            if ($a_computerextend['is_entitylocked'] == 1) {
               echo __('No, locked manually', 'glpiinventory');
               echo " [ <a href='".$fi_path."/front/computerentitylock.form.php?id=".
                     $a_computerextend['id']."&lock=0'>".__('Unlock it', 'glpiinventory')."</a> ]";
            } else {
               echo __('Yes');
               echo " [ <a href='".$fi_path."/front/computerentitylock.form.php?id=".
                     $a_computerextend['id']."&lock=1'>".__('Lock it', 'glpiinventory')."</a> ]";
            }
         }
         echo '</td>';
      } else {
         echo "<td colspan='2'></td>";
      }
      echo '<td>';
      echo __('Last boot', 'glpiinventory');
      echo '</td>';
      echo '<td>';
      echo Html::convDateTime($a_computerextend['last_boot']);
      echo '</td>';
      echo '</tr>';

      $pfRemoteManagement = new PluginGlpiinventoryComputerRemoteManagement();
      $pfRemoteManagement->showInformation($item->getID());
      echo '</table>';
      return true;
   }

   /**
    * Delete extended information of computer
    *
    * @param integer $computers_id
    */
   static function cleanComputer($computers_id) {
      $pfComputer = new self();
      $pfComputer->deleteByCriteria(['computers_id' => $computers_id], true, false);
   }


   /**
    * Get entity lock. If true, computer can't be transfered to another entity
    * by agent inventory (so in automatic)
    *
    * @param integer $computers_id
    * @return boolean
    */
   function getLock($computers_id) {

      $pfInventoryComputerComputer = new PluginGlpiinventoryInventoryComputerComputer();
      $a_computerextend = current($pfInventoryComputerComputer->find(
                                              ['computers_id' => $computers_id], [], 1));
      if (empty($a_computerextend)) {
         return false;
      }
      return $a_computerextend['is_entitylocked'];
   }
}
