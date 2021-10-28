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
 * Manage the fields values to backend on computer inventory. If have serial
 * xxxxx, so delete it.
 */
class PluginGlpiinventoryInventoryComputerBlacklist extends CommonDBTM {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_glpiinventory_blacklist';


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb = 0) {
      return _n('Blacklist', 'Blacklists', $nb);
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
         'name' => __('BlackList')
      ];

      $tab[] = [
         'id'           => '1',
         'table'        => $this->getTable(),
         'field'        => 'value',
         'name'         => __('blacklisted value', 'glpiinventory')
      ];

      $tab[] = [
         'id'            => '2',
         'table'         => 'glpi_plugin_glpiinventory_inventorycomputercriterias',
         'field'         => 'name',
         'linkfield'     => 'plugin_glpiinventory_criterium_id',
         'name'          => __('Type'),
         'datatype'      => 'dropdown',
         'itemlink_type' => 'PluginGlpiinventoryInventoryComputerCriteria',
      ];

      return $tab;
   }


   /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
   function defineTabs($options = []) {

      $pfInventoryComputerCriteria = new PluginGlpiinventoryInventoryComputerCriteria();

      $ong = [];
      $i = 1;
      $fields = $pfInventoryComputerCriteria->find();
      foreach ($fields as $data) {
         $ong[$i] = $data['name'];
         $i++;
      }
      return $ong;
   }


   /**
    * Display form for blacklist
    *
    * @param integer $items_id
    * @param array $options
    * @return true
    */
   function showForm($items_id, array $options = []) {

      if ($items_id!='') {
         $this->getFromDB($items_id);
      } else {
         $this->getEmpty();
      }

      $this->showFormHeader();

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('blacklisted value', 'glpiinventory')."</td>";
      echo "<td>";
      echo Html::input('value', ['value' => $this->fields['value']]);
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      Dropdown::show('PluginGlpiinventoryInventoryComputerCriteria',
                     ['name' => 'plugin_glpiinventory_criterium_id',
                           'value' => $this->fields['plugin_glpiinventory_criterium_id']]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons();

      return true;
   }


   /**
    * Remove fields in inventory XML from agent which are blacklisted
    *
    * @param array $a_computerinventory
    * @return array
    */
   function cleanBlacklist($a_computerinventory) {

      $pfInventoryComputerCriteria = new PluginGlpiinventoryInventoryComputerCriteria();
      $fields = $pfInventoryComputerCriteria->find();
      foreach ($fields as $id=>$data) {

         switch ($data['comment']) {

            case 'ssn':
               $a_blacklist = $this->find(['plugin_glpiinventory_criterium_id' => $id]);

               foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
                  if ((isset($a_computerinventory['Computer']['serial']))
                      && (strtolower($a_computerinventory['Computer']['serial'])
                              == strtolower($blacklist_data['value']))) {
                     $a_computerinventory['Computer']['serial'] = "";
                  }
                  if (((!isset($a_computerinventory['Computer']['serial']))
                          || ($a_computerinventory['Computer']['serial'] == ""))
                         && isset($a_computerinventory['Computer']['mserial'])) {
                     $a_computerinventory['Computer']['serial'] = $a_computerinventory['Computer']['mserial'];
                     foreach ($a_blacklist as $blacklist_data2) {
                        if ($a_computerinventory['Computer']['serial'] == $blacklist_data2['value']) {
                           $a_computerinventory['Computer']['serial'] = "";
                        }
                     }
                  }
                  if (isset($a_computerinventory['monitor'])) {
                     foreach ($a_computerinventory['monitor'] as $num_m=>$data_m) {
                        if ((isset($data_m['serial']))
                            && (strtolower($data_m['serial'])
                                    == strtolower($blacklist_data['value']))) {
                           $a_computerinventory['monitor'][$num_m]['serial'] = "";
                        }
                     }
                  }
               }
               break;

            case 'uuid':
               $a_blacklist = $this->find(['plugin_glpiinventory_criterium_id' => $id]);

               foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
                  if ((isset($a_computerinventory['Computer']['uuid']))
                        && (strtolower($a_computerinventory['Computer']['uuid'])
                                == strtolower($blacklist_data['value']))) {
                     $a_computerinventory['Computer']['uuid'] = "";
                  }
               }
               break;

            case 'macAddress':
               $a_blacklist = $this->find(['plugin_glpiinventory_criterium_id' => $id]);

               foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
                  if (isset($a_computerinventory['networkport'])) {
                     foreach ($a_computerinventory['networkport'] as $key=>$network) {
                        if ((isset($network['mac']))
                                AND (strtolower($network['mac'])
                                        == strtolower($blacklist_data['value']))) {
                           $a_computerinventory['networkport'][$key]['mac'] = "";
                        }
                     }
                  }
               }
               break;

            case 'winProdKey':
               $a_blacklist = $this->find(['plugin_glpiinventory_criterium_id' => $id]);

               foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
                  if ((isset($a_computerinventory['inventorycomputer']['items_operatingsystems_id']['license_number']))
                          && (strtolower($a_computerinventory['inventorycomputer']['items_operatingsystems_id']['license_number'])
                                  == strtolower($blacklist_data['value']))) {
                     $a_computerinventory['inventorycomputer']['items_operatingsystems_id']['license_number'] = "";
                  }
               }
              break;

            case 'smodel':
               $a_blacklist = $this->find(['plugin_glpiinventory_criterium_id' => $id]);

               foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
                  if ((isset($a_computerinventory['Computer']['computermodels_id']))
                          && (strtolower($a_computerinventory['Computer']['computermodels_id'])
                                  == strtolower($blacklist_data['value']))) {
                     $a_computerinventory['Computer']['computermodels_id'] = "";
                  }
               }
               if (isset($a_computerinventory['Computer'])) {
                  if ($a_computerinventory['Computer']['computermodels_id'] == "") {
                     if (isset($a_computerinventory['Computer']['mmodel'])) {
                        $a_computerinventory['Computer']['computermodels_id'] =
                           $a_computerinventory['Computer']['mmodel'];

                        foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
                           if ((isset($a_computerinventory['Computer']['computermodels_id']))
                                   && (strtolower($a_computerinventory['Computer']['computermodels_id'])
                                           == strtolower($blacklist_data['value']))) {
                              $a_computerinventory['Computer']['computermodels_id'] = "";
                              break;
                           }
                        }
                     }
                  }
               }
               break;

            case 'storagesSerial':
               $a_blacklist = $this->find(['plugin_glpiinventory_criterium_id' => $id]);

               //               foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
               //                  if (isset($arrayinventory['CONTENT']['STORAGES'])) {
               //                     foreach ($arrayinventory['CONTENT']['STORAGES'] as $key=>$storage) {
               //                        if ((isset($storage['SERIALNUMBER']))
               //                                AND ($storage['SERIALNUMBER'] == $blacklist_data['value'])) {
               //                           $arrayinventory['CONTENT']['STORAGES'][$key]['SERIALNUMBER'] = "";
               //                        }
               //                     }
               //                  }
               //               }
              break;

            case 'drivesSerial':
               $a_blacklist = $this->find(['plugin_glpiinventory_criterium_id' => $id]);

               //               foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
               //                  if (isset($arrayinventory['CONTENT']['DRIVES'])) {
               //                     foreach ($arrayinventory['CONTENT']['DRIVES'] as $key=>$drive) {
               //                        if ((isset($drive['SERIAL']))
               //                                AND ($drive['SERIAL'] == $blacklist_data['value'])) {
               //                           $arrayinventory['CONTENT']['DRIVES'][$key]['SERIAL'] = "";
               //                        }
               //                     }
               //                  }
               //               }
              break;

            case 'assetTag':
               $a_blacklist = $this->find(['plugin_glpiinventory_criterium_id' => $id]);

               //               foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
               //                  if ((isset($arrayinventory['CONTENT']['BIOS']['ASSETTAG']))
               //                          AND ($arrayinventory['CONTENT']['BIOS']['ASSETTAG'] ==
               //                               $blacklist_data['value'])) {
               //                     $arrayinventory['CONTENT']['BIOS']['ASSETTAG'] = "";
               //                  }
               //               }
              break;

            case 'manufacturer':
               $a_blacklist = $this->find(['plugin_glpiinventory_criterium_id' => $id]);

               foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
                  if ((isset($a_computerinventory['Computer']['manufacturers_id']))
                          && (strtolower($a_computerinventory['Computer']['manufacturers_id'])
                                  == strtolower($blacklist_data['value']))) {
                     $a_computerinventory['Computer']['manufacturers_id'] = "";
                     break;
                  }
               }
               if (isset($a_computerinventory['Computer'])) {
                  if ($a_computerinventory['Computer']['manufacturers_id'] == "") {
                     if (isset($a_computerinventory['Computer']['mmanufacturer'])) {
                        $a_computerinventory['Computer']['manufacturers_id'] =
                           $a_computerinventory['Computer']['mmanufacturer'];

                        foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
                           if ((isset($a_computerinventory['Computer']['manufacturers_id']))
                                   && (strtolower($a_computerinventory['Computer']['manufacturers_id'])
                                           == strtolower($blacklist_data['value']))) {
                              $a_computerinventory['Computer']['manufacturers_id'] = "";
                              break;
                           }
                        }
                     }
                  }
                  if ($a_computerinventory['Computer']['manufacturers_id'] == "") {
                     if (isset($a_computerinventory['Computer']['bmanufacturer'])) {
                        $a_computerinventory['Computer']['manufacturers_id'] =
                              $a_computerinventory['Computer']['bmanufacturer'];

                        foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
                           if ((isset($a_computerinventory['Computer']['manufacturers_id']))
                                   && (strtolower($a_computerinventory['Computer']['manufacturers_id'])
                                           == strtolower($blacklist_data['value']))) {
                              $a_computerinventory['Computer']['manufacturers_id'] = "";
                              break;
                           }
                        }
                     }
                  }
               }
              break;

            case 'IP':
               $a_blacklist = $this->find(['plugin_glpiinventory_criterium_id' => $id]);

               foreach ($a_blacklist as $blacklist_id=>$blacklist_data) {
                  if (isset($a_computerinventory['networkport'])) {
                     foreach ($a_computerinventory['networkport'] as $key=>$netport_data) {
                        foreach ($netport_data['ipaddress'] as $num_ip=>$ip) {
                           if ($ip == $blacklist_data['value']) {
                              unset($a_computerinventory['networkport'][$key]['ipaddress'][$num_ip]);
                           }
                        }
                     }
                  }
               }
               break;

         }
      }
      // Blacklist mac of "miniport*" for windows because have same mac as principal network ports
      if (isset($a_computerinventory['networkport'])) {
         foreach ($a_computerinventory['networkport'] as $key=>$network) {
            if ((isset($network['name']))
                    AND (strtolower($network['name']) =="miniport d'ordonnancement de paquets")) {
               $a_computerinventory['networkport'][$key]['mac'] = "";
            }
         }
      }
      return $a_computerinventory;
   }
}
