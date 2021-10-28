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
 * Manage the import rules used for each import / update into GLPI.
 */
class PluginGlpiinventoryRulematchedlog extends CommonDBTM {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_glpiinventory_ruleimport';


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb = 0) {
      return '';
   }


   /**
    * Count number of elements
    *
    * @param object $item
    * @return integer
    */
   static function countForItem(CommonDBTM $item) {
      return countElementsInTable('glpi_plugin_glpiinventory_rulematchedlogs',
         [
            'itemtype' => $item->getType(),
            'items_id' => $item->getField('id'),
         ]);
   }


   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string|array name of the tab
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $array_ret = [];

      if ($item->getType() == 'PluginGlpiinventoryAgent') {
         if (Session::haveRight('plugin_glpiinventory_agent', READ)) {
             $array_ret[0] = self::createTabEntry(__('Import information', 'glpiinventory'));
         }
      } else {
         $continue = true;

         switch ($item->getType()) {
            case 'PluginGlpiinventoryAgent':
               if (Session::haveRight('plugin_glpiinventory_agent', READ)) {
                   $array_ret[0] = self::createTabEntry(__('Import information', 'glpiinventory'));
               }
               break;

            case 'PluginGlpiinventoryUnmanaged':
               $cnt = PluginGlpiinventoryRulematchedlog::countForItem($item);
               $array_ret[1] = self::createTabEntry(__('Import information', 'glpiinventory'), $cnt);
               break;

            case 'Computer':
            case 'Monitor':
            case 'NetworkEquipment':
            case 'Peripheral':
            case 'Phone':
            case 'Printer':
               $continue = PluginGlpiinventoryToolbox::isAnInventoryDevice($item);
               break;

            default:
               break;

         }
         if (!$continue) {
            return [];
         } else if (empty($array_ret)) {
            $cnt = PluginGlpiinventoryRulematchedlog::countForItem($item);
            $array_ret[1] = self::createTabEntry(__('Import information', 'glpiinventory'), $cnt);
         }
         return $array_ret;
      }
   }


   /**
    * Display the content of the tab
    *
    * @param object $item
    * @param integer $tabnum number of the tab to display
    * @param integer $withtemplate 1 if is a template form
    * @return boolean
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      $pfRulematchedlog = new self();
      if ($tabnum == '0') {
         if ($item->fields['id'] > 0) {
            $pfRulematchedlog->showFormAgent($item->fields['id']);
            return true;
         }
      } else if ($tabnum == '1') {
         if ($item->fields['id'] > 0) {
            $pfRulematchedlog->showMatchedLogForm($item->fields['id'], $item->getType());

            $itemtype = '';
            switch (get_class($item)) {

               case 'Computer':
                  $itemtype = new PluginGlpiinventoryInventoryComputerComputer();
                  break;

               case 'Printer':
                  $itemtype = new PluginGlpiinventoryPrinter();
                  break;

               case 'NetworkEquipment':
                  $itemtype = new PluginGlpiinventoryNetworkEquipment();
                  break;

            }

            return true;
         }
      }
      return false;
   }


   /**
    * Clean old data
    *
    * @global object $DB
    * @param integer $items_id
    * @param string $itemtype
    */
   function cleanOlddata($items_id, $itemtype) {
      global $DB;

      $query = "SELECT `id` FROM `glpi_plugin_glpiinventory_rulematchedlogs`
            WHERE `items_id` = '".$items_id."'
               AND `itemtype` = '".$itemtype."'
            ORDER BY `date` DESC
            LIMIT 30, 50000";
      $result = $DB->query($query);
      while ($data=$DB->fetchArray($result)) {
         $this->delete(['id'=>$data['id']]);
      }
   }


   /**
    * Display form
    *
    * @param integer $items_id
    * @param string $itemtype
    * @return true
    */
   function showMatchedLogForm($items_id, $itemtype) {
      global $DB;

      $rule    = new PluginGlpiinventoryInventoryRuleImport();
      $pfAgent = new PluginGlpiinventoryAgent();

      $class = PluginGlpiinventoryItem::getFIItemClassInstance($itemtype);
      if ($class) {
         $class->showDownloadInventoryFile($items_id);
      }
      if (isset($_GET["start"])) {
         $start = $_GET["start"];
      } else {
         $start = 0;
      }

      $params = ['FROM'  => 'glpi_plugin_glpiinventory_rulematchedlogs',
                 'WHERE' => ['itemtype' => $itemtype,
                             'items_id' => intval($items_id)],
                 'COUNT' => 'cpt'
                ];
      $iterator = $DB->request($params);
      $number   = $iterator->current()['cpt'];

      // Display the pager
      Html::printAjaxPager(self::getTypeName(2), $start, $number);

      echo "<table class='tab_cadre_fixe' cellpadding='1'>";

      echo "<tr>";
      echo "<th colspan='5'>";
      echo __('Rule import logs', 'glpiinventory');

      echo "</th>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>";
      echo _n('Date', 'Dates', 1);

      echo "</th>";
      echo "<th>";
      echo __('Rule name', 'glpiinventory');

      echo "</th>";
      echo "<th>";
      echo __('Agent', 'glpiinventory');

      echo "</th>";
      echo "<th>";
      echo __('Module', 'glpiinventory');

      echo "</th>";
      echo "<th>";
      echo _n('Criterion', 'Criteria', 2);

      echo "</th>";
      echo "</tr>";

      $params = ['FROM'  => 'glpi_plugin_glpiinventory_rulematchedlogs',
                 'WHERE' => ['itemtype' => $itemtype, 'items_id' => intval($items_id)],
                 'ORDER' => 'date DESC',
                 'START' => intval($start),
                 'LIMIT' => intval($_SESSION['glpilist_limit'])
                ];
      foreach ($DB->request($params) as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td align='center'>";
         echo Html::convDateTime($data['date']);
         echo "</td>";
         echo "<td align='center'>";
         if ($rule->getFromDB($data['rules_id'])) {
            echo $rule->getLink(1);
         }
         echo "</td>";
         echo "<td align='center'>";
         if ($pfAgent->getFromDB($data['plugin_glpiinventory_agents_id'])) {
            echo $pfAgent->getLink(1);
         }
         echo "</td>";
         echo "<td>";
         $a_methods = PluginGlpiinventoryStaticmisc::getmethods();
         foreach ($a_methods as $mdata) {
            if ($mdata['method'] == $data['method']) {
               echo $mdata['name'];
            }
         }
         echo "</td>";
         echo "<td>";
         $criteria = importArrayFromDB($data['criteria']);
         if (count($criteria) > 0) {
            echo "<ul style='list-style-type:disc'>";
            foreach ($criteria as $key=>$value) {
               echo "<li>".$key.": ";
               if (is_array($value)) {
                  echo implode("<br>", $value);
               } else {
                  echo $value;
               }
               echo "</li>";
            }
            echo "</ul><hr class='criteriarule'>";
         }
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";

      // Display the pager
      Html::printAjaxPager(self::getTypeName(2), $start, $number);

      return true;
   }


   /**
    * Display form for agent
    *
    * @param integer $agents_id
    */
   function showFormAgent($agents_id) {

      $rule = new PluginGlpiinventoryInventoryRuleImport();

      echo "<table class='tab_cadre_fixe' cellpadding='1'>";

      echo "<tr>";
      echo "<th colspan='5'>";
      echo __('Rule import logs', 'glpiinventory');

      echo "</th>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>";
      echo _n('Date', 'Dates', 1);

      echo "</th>";
      echo "<th>";
      echo __('Rule name', 'glpiinventory');

      echo "</th>";
      echo "<th>";
      echo __('Item type');

      echo "</th>";
      echo "<th>";
      echo _n('Item', 'Items', 1);

      echo "</th>";
      echo "<th>";
      echo __('Module', 'glpiinventory');

      echo "</th>";
      echo "</tr>";

      $allData = $this->find(['plugin_glpiinventory_agents_id' => $agents_id], ['date DESC']);
      foreach ($allData as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td align='center'>";
         echo Html::convDateTime($data['date']);
         echo "</td>";
         echo "<td align='center'>";
         if ($rule->getFromDB($data['rules_id'])) {
            echo $rule->getLink(1);
         }
         echo "</td>";
         echo "<td align='center'>";
         $itemtype = $data['itemtype'];
         $item = new $itemtype();
         echo $item->getTypeName();
         echo "</td>";
         echo "<td align='center'>";
         if ($item->getFromDB($data['items_id'])) {
            echo $item->getLink(1);
         }
         echo "</td>";
         echo "<td>";
         $a_methods = PluginGlpiinventoryStaticmisc::getmethods();
         foreach ($a_methods as $mdata) {
            if ($mdata['method'] == $data['method']) {
               echo $mdata['name'];
            }
         }
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";
   }
}
