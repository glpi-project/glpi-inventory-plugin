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
   die("Sorry. You can't access this file directly");
}

/**
 * Manage the configuration of logs of network inventory (network equipment
 * and printer).
 */
class PluginGlpiinventoryConfigLogField extends CommonDBTM {


   /**
    * Init config log fields : add default values in table
    *
    * @global object $DB
    */
   function initConfig() {
      global $DB;

      $NOLOG = '-1';
      $logs = [
          'NetworkEquipment' => [
              'ifdescr'          => $NOLOG,
              'ifIndex'          => $NOLOG,
              'ifinerrors'       => $NOLOG,
              'ifinoctets'       => $NOLOG,
              'ifinternalstatus' => $NOLOG,
              'iflastchange'     => $NOLOG,
              'ifmtu'            => $NOLOG,
              'ifName'           => $NOLOG,
              'ifouterrors'      => $NOLOG,
              'ifoutoctets'      => $NOLOG,
              'ifspeed'          => $NOLOG,
              'ifstatus'         => $NOLOG,
              'macaddr'          => $NOLOG,
              'portDuplex'       => $NOLOG,
              'trunk'            => $NOLOG
          ],
          'Printer' => [
              'ifIndex' => $NOLOG,
              'ifName'  => $NOLOG
          ]
      ];

      $mapping = new PluginGlpiinventoryMapping();
      foreach ($logs as $itemtype=>$fields) {
         foreach ($fields as $name=>$value) {
            $input = [];
            $mapfields = $mapping->get($itemtype, $name);
            if ($mapfields != false) {
               if (!$this->getValue($mapfields['id'])) {
                  $input['plugin_glpiinventory_mappings_id'] = $mapfields['id'];
                  $input['days']  = $value;
                  $this->add($input);
               } else {
                  // On old version, can have many times same value in DB
                  $query = "SELECT *  FROM `glpi_plugin_glpiinventory_configlogfields`
                     WHERE `plugin_glpiinventory_mappings_id` = '".$mapfields['id']."'
                     LIMIT 1,1000";
                  $result=$DB->query($query);

                  $delete = $DB->buildDelete(
                     'glpi_plugin_glpiinventory_configlogfields', [
                        'id' => new \QueryParam()
                     ]
                  );
                  $stmt = $DB->prepare($delete);
                  while ($data=$DB->fetchArray($result)) {
                     $stmt->bind_param('s', $data['id']);
                     $DB->executeStatement($stmt);
                  }
                  mysqli_stmt_close($stmt);
               }
            }
         }
      }
   }


   /**
    * Get the value of a field in configlog
    *
    * @global object $DB
    * @param string $field
    * @return string|false
    */
   function getValue($field) {
      global $DB;

      $query = "SELECT days
                FROM ".$this->getTable()."
                WHERE `plugin_glpiinventory_mappings_id`='".$field."'
                LIMIT 1;";
      $result = $DB->query($query);
      if ($result) {
         $fields = $DB->fetchRow($result);
         if ($fields) {
            $this->fields = $fields;
            return $this->fields['0'];
         }
      }
      return false;
   }


   /**
    * Display form
    *
    * @global object $DB
    * @param array $options
    * @return true
    */
   function showConfigForm($options = []) {
      global $DB;

      $mapping = new PluginGlpiinventoryMapping();

      echo "<form name='form' method='post' action='".$options['target']."'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr>";
      echo "<th colspan='2'>";
      echo __('History configuration', 'glpiinventory');

      echo "</th>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>";
      echo __('List of fields for which to keep history', 'glpiinventory');

      echo "</th>";
      echo "<th>";
      echo __('Retention in days', 'glpiinventory');

      echo "</th>";
      echo "</tr>";

      $days = [];
      $days[-1] = __('Never');
      $days[0]  = __('Always');
      for ($i = 1; $i < 366; $i++) {
         $days[$i]  = "$i";
      }

      $query = "SELECT `".$this->getTable()."`.`id`, `locale`, `days`, `itemtype`, `name`
                FROM `".$this->getTable()."`, `glpi_plugin_glpiinventory_mappings`
                WHERE `".$this->getTable()."`.`plugin_glpiinventory_mappings_id`=
                         `glpi_plugin_glpiinventory_mappings`.`id`
                ORDER BY `itemtype`, `name`;";
      $result=$DB->query($query);
      if ($result) {
         while ($data=$DB->fetchArray($result)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td align='left'>";
            echo $mapping->getTranslation($data);
            echo "</td>";

            echo "<td align='center'>";
            Dropdown::showFromArray('field-'.$data['id'], $days,
                                    ['value'=>$data['days']]);
            echo "</td>";
            echo "</tr>";
         }
      }

      if (Session::haveRight('plugin_glpiinventory_configuration', UPDATE)) {
         echo "<tr class='tab_bg_2'><td align='center' colspan='4'>
               <input type='hidden' name='tabs' value='history'/>
               <input class='btn btn-primary' type='submit' name='update'
                      value='" . __('Update') . "'></td></tr>";
      }
      echo "</table>";

      echo "<br/>";
      echo "<table class='tab_cadre_fixe' cellpadding='2'>";
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='1' class='center' height='30'>";
      if (Session::haveRight('plugin_glpiinventory_configuration', UPDATE)) {
         echo "<input type='submit' class='btn btn-secondary' name='Clean_history' ".
                 "value='"._x('button', 'Clean')."' >";
      }
      echo "</td>";
      echo "</tr>";
      echo "</table></div>";
      Html::closeForm();

      return true;
   }


   /**
    * Update data in database
    *
    * @param array $p_post
    */
   function putForm($p_post) {
      foreach ($p_post as $field=>$log) {
         if (substr($field, 0, 6) == 'field-') {
            $input = [];
            $input['id'] = substr($field, 6);
            $input['days'] = $log;
            $this->update($input);
         }
      }
   }
}
