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


/**
 * Manage the history of network port changes.
 */
class PluginGlpiinventoryNetworkPortLog extends CommonDBTM {


   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string name of the tab
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->fields['id'] > 0) {
         return __('GLPI Inventory historical', 'glpiinventory');
      }
      return '';
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
      $pfNetworkPortLog = new self();
      echo $pfNetworkPortLog->showHistory($item->fields['id']);
      return true;
   }


   /**
    * Insert port history with connection and disconnection
    *
    * @global object $DB
    * @param string $status status of port ('make' or 'remove')
    * @param array $array with values : $array["networkports_id"], $array["value"], $array["itemtype"]
    *                     and $array["device_ID"]
    */
   function insertConnection($status, $array) {
      global $DB;

      $input = [];
      $input['date'] = date("Y-m-d H:i:s");
      $input['networkports_id'] = $array['networkports_id'];

      if ($status == "field") {
         $DB->insert(
            'glpi_plugin_glpiinventory_networkportlogs', [
               'networkports_id'                      => $array['networkports_id'],
               'plugin_glpiinventory_mappings_id'   => $array['plugin_glpiinventory_mappings_id'],
               'value_old'                            => $array('value_old'),
               'value_new'                            => $array['value_new'],
               'date_mod'                             => date("Y-m-d H:i:s")
            ]
         );
      }
   }


    /**
     * Display form
     *
     * @global object $DB
     * @param integer $id
     * @param array $options
     * @return true
     */
   function showForm($id, array $options = []) {
      global $DB;

      $this->initForm($id, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='3'>";
      echo __('List of fields to history', 'glpiinventory')." :";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";

      $options = [];

      $mapping = new PluginGlpiinventoryMapping;
      $maps = $mapping->find();
      $listName = [];
      foreach ($maps as $mapfields) {
         // TODO: untested
         $listName[$mapfields['itemtype']."-".$mapfields['name']]=
            $mapping->getTranslation($mapfields);
      }

      if (!empty($listName)) {
         asort($listName);
      }

      // Get list of fields configured for history
      $iterator = $DB->request([
         'FROM'   => 'glpi_plugin_glpiinventory_configlogfields'
      ]);

      $stmt = null;
      if (count($iterator)) {
         $delete = $DB->buildDelete(
            'glpi_plugin_glpiinventory_configlogfields', [
               'id' => new \QueryParam()
            ]
         );
         $stmt = $DB->prepare($delete);
      }

      foreach ($iterator as $data) {
         $type = '';
         $name= '';
         list($type, $name) = explode("-", $data['field']);
         if (!isset($listName[$type."-".$name])) {
            $stmt->bind_param('s', $data['id']);
            $DB->executeStatement($stmt);
         } else {
            $options[$data['field']]=$listName[$type."-".$name];
         }
         unset($listName[$data['field']]);
      }

      if ($stmt != null) {
         mysqli_stmt_close($stmt);
      }

      if (!empty($options)) {
         asort($options);
      }
      echo "<td class='right' width='350'>";
      if (count($listName)) {
         echo "<select name='plugin_glpiinventory_extraction_to_add[]' multiple size='15'>";
         foreach ($listName as $key => $val) {
            //list ($item_type, $item) = explode("_", $key);
            echo "<option value='$key'>" . $val . "</option>\n";
         }
         echo "</select>";
      }

      echo "</td><td class='center'>";

      if (count($listName)) {
         if (Session::haveRight('plugin_glpiinventory_configuration', UPDATE)) {
            echo "<input type='submit'  class=\"submit\" ".
                    "name='plugin_glpiinventory_extraction_add' value='" . __('Add') . " >>'>";
         }
      }
      echo "<br /><br />";
      if (!empty($options)) {
         if (Session::haveRight('plugin_glpiinventory_configuration', UPDATE)) {
            echo "<input type='submit'  class=\"submit\" ".
                    "name='plugin_glpiinventory_extraction_delete' value='<< ".
                    __('Delete', 'glpiinventory') . "'>";
         }
      }
      echo "</td><td class='left'>";
      if (!empty($options)) {
         echo "<select name='plugin_glpiinventory_extraction_to_delete[]' multiple size='15'>";
         foreach ($options as $key => $val) {
            //list ($item_type, $item) = explode("_", $key);
            echo "<option value='$key'>" . $val . "</option>\n";
         }
         echo "</select>";
      } else {
         echo "&nbsp;";
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th colspan='3'>";
      echo __('Clean history', 'glpiinventory')." :";
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='3' class='center'>";
      if (Session::haveRight('plugin_glpiinventory_configuration', UPDATE)) {
         echo "<input type='submit' class=\"submit\" name='Clean_history' ".
                 "value='".__('Clean')."' >";
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th colspan='3'>";
      echo "&nbsp;";
      echo "</th>";
      echo "</tr>";

      $this->showFormButtons($options);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }


   /**
    * Cron task: clean networkport logs too old
    *
    * @global object $DB
    */
   static function cronCleannetworkportlogs() {
      global $DB;

      $pfConfigLogField = new PluginGlpiinventoryConfigLogField();
      $pfNetworkPortLog = new PluginGlpiinventoryNetworkPortLog();

      $a_list = $pfConfigLogField->find();
      if (count($a_list)) {
         foreach ($a_list as $data) {
            switch ($data['days']) {
               case '-1':
                  $DB->delete(
                     $pfNetworkPortLog->getTable(), [
                        'plugin_glpiinventory_mappings_id' => $data['plugin_glpiinventory_mappings_id']
                     ]
                  );
                  break;

               case '0': // never delete
                  break;

               default:
                  $DB->delete(
                     $pfNetworkPortLog->getTable(), [
                        'plugin_glpiinventory_mappings_id'   => $data['plugin_glpiinventory_mappings_id'],
                        'date_mod'                             => new \QueryExpression(
                           "date_add(now(), interval - {$data['days']} day)"
                        )
                     ]
                  );
                  break;

            }
         }
      }
   }


   /**
    * Add log of networkport
    *
    * @param integer $port_id
    * @param string $value_new
    * @param string $field
    */
   static function networkport_addLog($port_id, $value_new, $field) {
      $pfNetworkPort = new PluginGlpiinventoryNetworkPort();
      $pfNetworkPortLog = new PluginGlpiinventoryNetworkPortLog();
      $pfConfigLogField = new PluginGlpiinventoryConfigLogField();
      $pfMapping = new PluginGlpiinventoryMapping();

      $db_field = $field;
      switch ($field) {

         case 'ifname':
            $db_field = 'name';
            $field = 'ifName';
            break;

         case 'mac':
            $db_field = 'mac';
            $field = 'macaddr';
            break;

         case 'ifnumber':
            $db_field = 'logical_number';
            $field = 'ifIndex';
            break;

         case 'trunk':
            $field = 'vlanTrunkPortDynamicStatus';
            break;

         case 'iftype':
            $field = 'ifType';
            break;

         case 'duplex':
            $field = 'portDuplex';
            break;

      }

      $pfNetworkPort->loadNetworkport($port_id);
      //echo $ptp->getValue($db_field);
      if ($pfNetworkPort->getValue($db_field) != $value_new) {
         $a_mapping = $pfMapping->get('NetworkEquipment', $field);

         $days = $pfConfigLogField->getValue($a_mapping['id']);

         if ((isset($days)) AND ($days != '-1')) {
            $array = [];
            $array["networkports_id"] = $port_id;
            $array["plugin_glpiinventory_mappings_id"] = $a_mapping['id'];
            $array["value_old"] = $pfNetworkPort->getValue($db_field);
            $array["value_new"] = $value_new;
            $pfNetworkPortLog->insertConnection("field", $array);
         }
      }
   }


   // $status = connection or disconnection


   /**
    * Add log when connect or disconnect
    *
    * @param string $status values possible: make|remove
    * @param integer $ports_id
    */
   static function addLogConnection($status, $ports_id) {

      $pfNetworkPortConnectionLog = new PluginGlpiinventoryNetworkPortConnectionLog();
      $NetworkPort_NetworkPort=new NetworkPort_NetworkPort();

      $input = [];

      // Récupérer le port de la machine associé au port du switch

      // Récupérer le type de matériel
      $input["networkports_id_source"] = $ports_id;
      $opposite_port = $NetworkPort_NetworkPort->getOppositeContact($ports_id);
      if (!$opposite_port) {
         return;
      }
      $input['networkports_id_destination'] = $opposite_port;

      $input['date_mod'] = date("Y-m-d H:i:s");

      if ($status == 'remove') {
         $input['creation'] = 0;
      } else if ($status == 'make') {
         $input['creation'] = 1;
      }

      $pfNetworkPortConnectionLog->add($input);
   }


   /**
    * Get the history list of the port
    *
    * @global object $DB
    * @global array $CFG_GLPI
    * @param integer $ID_port
    * @return string the html prepared to display
    */
   static function showHistory($ID_port) {
      global $DB, $CFG_GLPI;

      $np = new NetworkPort();

      $query = "
         SELECT * FROM(
            SELECT * FROM (
               SELECT `id`, `date_mod`, `plugin_glpiinventory_agentprocesses_id`,
                  `networkports_id_source`, `networkports_id_destination`,
                  `creation` as `field`, NULL as `value_old`, NULL as `value_new`
               FROM `glpi_plugin_glpiinventory_networkportconnectionlogs`
               WHERE `networkports_id_source`='".$ID_port."'
                  OR `networkports_id_destination`='".$ID_port."'
               ORDER BY `date_mod` DESC
               )
            AS `DerivedTable1`
            UNION ALL
            SELECT * FROM (
               SELECT `glpi_plugin_glpiinventory_networkportlogs`.`id`,
                  `date_mod` as `date_mod`, `plugin_glpiinventory_agentprocesses_id`,
                  `networkports_id` AS `networkports_id_source`,
                  NULL as `networkports_id_destination`,
                  `name` AS `field`, `value_old`, `value_new`
               FROM `glpi_plugin_glpiinventory_networkportlogs`
               LEFT JOIN `glpi_plugin_glpiinventory_mappings`
                  ON `glpi_plugin_glpiinventory_networkportlogs`.".
                        "`plugin_glpiinventory_mappings_id` =
                     `glpi_plugin_glpiinventory_mappings`.`id`
               WHERE `networkports_id`='".$ID_port."'
               ORDER BY `date_mod` DESC
               )
            AS `DerivedTable2`)
         AS `MainTable`
         ORDER BY `date_mod` DESC, `id` DESC";

      $text = "<table class='tab_cadre' cellpadding='5' width='950'>";

      $text .= "<tr class='tab_bg_1'>";
      $text .= "<th colspan='8'>";
      $text .= "Historique";
      $text .= "</th>";
      $text .= "</tr>";

      $text .= "<tr class='tab_bg_1'>";
      $text .= "<th>".__('Connection')."</th>";
      $text .= "<th>"._n('Item', 'Items', 1)."</th>";
      $text .= "<th>".__('Field')."</th>";
      $text .= "<th></th>";
      $text .= "<th></th>";
      $text .= "<th></th>";
      $text .= "<th>"._n('Date', 'Dates', 1)."</th>";
      $text .= "</tr>";

      $result=$DB->query($query);
      if ($result) {
         while ($data=$DB->fetchArray($result)) {
            $text .= "<tr class='tab_bg_1'>";
            if (!empty($data["networkports_id_destination"])) {
               // Connections and disconnections
               $imgfolder = Plugin::getWebDir('glpiinventory')."/pics";
               if ($data['field'] == '1') {
                  $text .= "<td align='center'><img src='".$imgfolder."/connection_ok.png'/></td>";
               } else {
                  $text .= "<td align='center'><img src='".$imgfolder.
                              "/connection_notok.png'/></td>";
               }
               if ($ID_port == $data["networkports_id_source"]) {
                  if ($np->getFromDB($data["networkports_id_destination"])) {
                     //if (isset($np->fields["items_id"])) {
                     $item = new $np->fields["itemtype"];
                     $item->getFromDB($np->fields["items_id"]);
                     $link1 = $item->getLink(1);
                     $link = "<a href=\"" . $CFG_GLPI["root_doc"].
                                 "/front/networkport.form.php?id=" . $np->fields["id"] . "\">";
                     if (rtrim($np->fields["name"]) != "") {
                        $link .= $np->fields["name"];
                     } else {
                        $link .= __('Without name');
                     }
                     $link .= "</a>";
                     $text .= "<td align='center'>".$link." ".__('on', 'glpiinventory')." ".
                                 $link1."</td>";
                  } else {
                     $text .= "<td align='center'><font color='#ff0000'>".__('Deleted').
                                 "</font></td>";
                  }

               } else if ($ID_port == $data["networkports_id_destination"]) {
                  $np->getFromDB($data["networkports_id_source"]);
                  if (isset($np->fields["items_id"])) {
                     $item = new $np->fields["itemtype"];
                     $item->getFromDB($np->fields["items_id"]);
                     $link1 = $item->getLink(1);
                     $link = "<a href=\"" . $CFG_GLPI["root_doc"]."/front/networkport.form.php?id=".
                                 $np->fields["id"] . "\">";
                     if (rtrim($np->fields["name"]) != "") {
                        $link .= $np->fields["name"];
                     } else {
                        $link .= __('Without name');
                     }
                     $link .= "</a>";
                     $text .= "<td align='center'>".$link." ".__('on', 'glpiinventory')." ".
                                 $link1."</td>";
                  } else {
                     $text .= "<td align='center'><font color='#ff0000'>".__('Deleted').
                                 "</font></td>";
                  }
               }
               $text .= "<td align='center' colspan='4'></td>";
               $text .= "<td align='center'>".Html::convDateTime($data["date_mod"])."</td>";

            } else {
               // Changes values
               $text .= "<td align='center' colspan='2'></td>";
               $mapping = new PluginGlpiinventoryMapping();
               $mapfields = $mapping->get('NetworkEquipment', $data["field"]);
               if ($mapfields != false) {
                  $text .= "<td align='center'>".
                     $mapping->getTranslation($mapfields)."</td>";
               } else {
                  $text .= "<td align='center'></td>";
               }
               $text .= "<td align='center'>".$data["value_old"]."</td>";
               $text .= "<td align='center'>-></td>";
               $text .= "<td align='center'>".$data["value_new"]."</td>";
               $text .= "<td align='center'>".Html::convDateTime($data["date_mod"])."</td>";
            }
            $text .= "</tr>";
         }
      }
      $text .= "</table>";
      return $text;
   }


}

