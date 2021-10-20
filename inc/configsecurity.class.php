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
   die("Sorry. You can't access this file directly");
}

use Glpi\Application\View\TemplateRenderer;

/**
 * Manage SNMP credentials: v1, v2c and v3 support.
 */
class PluginGlpiinventoryConfigSecurity extends CommonDBTM {

   /**
    * We activate the history.
    *
    * @var boolean
    */
   public $dohistory = true;

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_glpiinventory_configsecurity';


   /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;
   }


   /**
    * Display form
    *
    * @param integer $id
    * @param array $options
    * @return true
    */
   function showForm($id, array $options = []) {
      Session::checkRight('plugin_glpiinventory_configsecurity', READ);

      $this->initForm($id, $options);
      TemplateRenderer::getInstance()->display('@glpiinventory/forms/configsecurity.html.twig', [
         'item'   => $this,
         'params' => $options,
      ]);

      return true;
   }


   /**
    * Display SNMP version (dropdown)
    *
    * @param null|string $p_value
    */
   function showDropdownSNMPVersion($p_value = null) {
      $snmpVersions = [0 => '-----', '1', '2c', '3'];
      $options = [];
      if (!is_null($p_value)) {
         $options = ['value' => $p_value];
      }
      Dropdown::showFromArray("snmpversion", $snmpVersions, $options);
   }


   /**
    * Get real version of SNMP
    *
    * @param integer $id
    * @return string
    */
   function getSNMPVersion($id) {
      switch ($id) {

         case '1':
            return '1';

         case '2':
            return '2c';

         case '3':
            return '3';

      }
      return '';
   }


   /**
    * Display SNMP encryption protocols dropdown
    *
    * @param null|string $p_value
    */
   function showDropdownSNMPAuth($p_value = null) {
      $authentications = [0=>'-----', 'MD5', 'SHA'];
      $options = [];
      if (!is_null($p_value)) {
         $options = ['value'=>$p_value];
      }
      Dropdown::showFromArray("authentication", $authentications, $options);
   }


   /**
    * Get SNMP authentication protocol
    *
    * @param integer $id
    * @return string
    */
   function getSNMPAuthProtocol($id) {
      switch ($id) {

         case '1':
            return 'MD5';

         case '2':
            return 'SHA';

      }
      return '';
   }


   /**
    * Display SNMP encryption protocols dropdown
    *
    * @param string $p_value
    */
   function showDropdownSNMPEncryption($p_value = null) {
      $encryptions = [0 => Dropdown::EMPTY_VALUE, 'DES', 'AES128', 'Triple-DES'];
      $options     = [];
      if (!is_null($p_value)) {
         $options = ['value' => $p_value];
      }
      Dropdown::showFromArray("encryption", $encryptions, $options);
   }


   /**
    * Get SNMP encryption protocol
    *
    * @param integer $id
    * @return string
    */
   function getSNMPEncryption($id) {
      switch ($id) {

         case '1':
            return 'DES';

         case '2':
            return 'AES';

         case '5':
            return '3DES';

      }
      return '';
   }


   /**
    * Display SNMP credentials dropdown
    *
    * @param string $selected
    */
   static function authDropdown($selected = "") {

      Dropdown::show("PluginGlpiinventoryConfigSecurity",
                      ['name' => "plugin_glpiinventory_configsecurities_id",
                           'value' => $selected,
                           'comment' => false]);
   }


   /**
    * Display form related to the massive action selected
    *
    * @param object $ma MassiveAction instance
    * @return boolean
    */
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      if ($ma->getAction() == 'assign_auth') {
         PluginGlpiinventoryConfigSecurity::authDropdown();
         echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
         return true;
      }
      return false;
   }


   /**
    * Execution code for massive action
    *
    * @param object $ma MassiveAction instance
    * @param object $item item on which execute the code
    * @param array $ids list of ID on which execute the code
    */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      $itemtype = $item->getType();

      switch ($ma->getAction()) {

         case "assign_auth" :
            switch ($itemtype) {

               case 'NetworkEquipment':
                  $equipement = new PluginGlpiinventoryNetworkEquipment();
                  break;

               case 'Printer':
                  $equipement = new PluginGlpiinventoryPrinter();
                  break;

               case 'PluginGlpiinventoryUnmanaged':
                  $equipement = new PluginGlpiinventoryUnmanaged();
                  break;

            }
            $fk = getForeignKeyFieldForItemType($itemtype);
            foreach ($ids as $key) {
               $found = $equipement->find([$fk => $key]);
               $input = [];
               if (count($found) > 0) {
                  $current = current($found);
                  $equipement->getFromDB($current['id']);
                  $input['id'] = $equipement->fields['id'];
                  $input['plugin_glpiinventory_configsecurities_id'] =
                              $_POST['plugin_glpiinventory_configsecurities_id'];
                  $return = $equipement->update($input);
               } else {
                  $input[$fk] = $key;
                  $input['plugin_glpiinventory_configsecurities_id'] =
                              $_POST['plugin_glpiinventory_configsecurities_id'];
                  $return = $equipement->add($input);
               }

               if ($return) {
                  //set action massive ok for this item
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
               } else {
                  // KO
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
               }
            }
         break;

      }
   }


   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'            => 'common',
         'name'          => __('Characteristics')
      ];

      $tab[] = [
         'id'            => '1',
         'table'         => $this->getTable(),
         'field'         => 'name',
         'name'          => __('Name'),
         'datatype'      => 'itemlink'
      ];

      $tab[] = [
         'id'            => '2',
         'table'         => $this->getTable(),
         'field'         => 'community',
         'name'          => __('Community', 'glpiinventory'),
         'datatype'      => 'string',
         'massiveaction' => false
      ];

      return $tab;
   }
}
