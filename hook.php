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

/**
 * Add search options for GLPI objects
 *
 * @param string $itemtype
 * @return array
 */
function plugin_glpiinventory_getAddSearchOptions($itemtype) {

   $sopt = [];
   if ($itemtype == 'Computer' or $itemtype == 'PluginGlpiinventoryComputer') {

      $sopt[5150]['table']     = 'glpi_plugin_glpiinventory_inventorycomputercomputers';
      $sopt[5150]['field']     = 'last_fusioninventory_update';
      $sopt[5150]['name']      = __('FusInv', 'glpiinventory')." - ".
         __('Last inventory', 'glpiinventory');
      $sopt[5150]['datatype']  = 'datetime';
      $sopt[5150]['itemlink_type'] = 'PluginGlpiinventoryInventoryComputerLib';
      $sopt[5150]['massiveaction'] = false;

      $sopt[5157]['table']     = 'glpi_plugin_glpiinventory_inventorycomputercomputers';
      $sopt[5157]['field']     = 'operatingsystem_installationdate';
      $sopt[5157]['name']      = OperatingSystem::getTypeName(1)." - ".__('Installation')." (".
                                    strtolower(_n('Date', 'Dates', 1)).")";
      $sopt[5157]['joinparams']  = ['jointype' => 'child'];
      $sopt[5157]['datatype']  = 'date';
      $sopt[5157]['massiveaction'] = false;

      $sopt[5158]['table']     = 'glpi_plugin_glpiinventory_inventorycomputercomputers';
      $sopt[5158]['field']     = 'winowner';
      $sopt[5158]['joinparams']  = ['jointype' => 'child'];
      $sopt[5158]['name']      = __('Owner', 'glpiinventory');
      $sopt[5158]['massiveaction'] = false;

      $sopt[5159]['table']     = 'glpi_plugin_glpiinventory_inventorycomputercomputers';
      $sopt[5159]['field']     = 'wincompany';
      $sopt[5159]['name']      = __('Company', 'glpiinventory');
      $sopt[5159]['joinparams']  = ['jointype' => 'child'];
      $sopt[5159]['massiveaction'] = false;

      $sopt[5160]['table']     = 'glpi_plugin_glpiinventory_agents';
      $sopt[5160]['field']     = 'useragent';
      $sopt[5160]['name']      = __('Useragent', 'glpiinventory');
      $sopt[5160]['joinparams']  = ['jointype' => 'child'];
      $sopt[5160]['massiveaction'] = false;

      $sopt[5161]['table']     = 'glpi_plugin_glpiinventory_agents';
      $sopt[5161]['field']     = 'tag';
      $sopt[5161]['linkfield'] = '';
      $sopt[5161]['name']      = __('Inventory tag', 'glpiinventory');

      $sopt[5163]['table']     = 'glpi_plugin_glpiinventory_inventorycomputercomputers';
      $sopt[5163]['field']     = 'oscomment';
      $sopt[5163]['name']      = OperatingSystem::getTypeName(1).'/'.__('Comments');
      $sopt[5163]['joinparams']  = ['jointype' => 'child'];
      $sopt[5163]['massiveaction'] = false;

      $sopt[5164]['table']         = "glpi_plugin_glpiinventory_agentmodules";
      $sopt[5164]['field']         = "DEPLOY";
      $sopt[5164]['linkfield']     = "DEPLOY";
      $sopt[5164]['name']          = __('Module', 'glpiinventory')."-".__('Deploy', 'glpiinventory');
      $sopt[5164]['datatype']      = 'bool';
      $sopt[5164]['massiveaction'] = false;

      $sopt[5165]['table']         = "glpi_plugin_glpiinventory_agentmodules";
      $sopt[5165]['field']         = "WAKEONLAN";
      $sopt[5165]['linkfield']     = "WAKEONLAN";
      $sopt[5165]['name']          = __('Module', 'glpiinventory')."-".__('WakeOnLan', 'glpiinventory');
      $sopt[5165]['datatype']      = 'bool';
      $sopt[5165]['massiveaction'] = false;

      $sopt[5166]['table']         = "glpi_plugin_glpiinventory_agentmodules";
      $sopt[5166]['field']         = "INVENTORY";
      $sopt[5166]['linkfield']     = "INVENTORY";
      $sopt[5166]['name']          = __('Module', 'glpiinventory')."-".__('Local inventory', 'glpiinventory');
      $sopt[5166]['datatype']      = 'bool';
      $sopt[5166]['massiveaction'] = false;

      $sopt[5167]['table']         = "glpi_plugin_glpiinventory_agentmodules";
      $sopt[5167]['field']         = "InventoryComputerESX";
      $sopt[5167]['linkfield']     = "InventoryComputerESX";
      $sopt[5167]['name']          = __('Module', 'glpiinventory')."-".__('ESX/VMWare', 'glpiinventory');
      $sopt[5167]['datatype']      = 'bool';
      $sopt[5167]['massiveaction'] = false;

      $sopt[5168]['table']         = "glpi_plugin_glpiinventory_agentmodules";
      $sopt[5168]['field']         = "NETWORKINVENTORY";
      $sopt[5168]['linkfield']     = "NETWORKINVENTORY";
      $sopt[5168]['name']          = __('Module', 'glpiinventory')."-".__('Network inventory', 'glpiinventory');
      $sopt[5168]['datatype']      = 'bool';
      $sopt[5168]['massiveaction'] = false;

      $sopt[5169]['table']         = "glpi_plugin_glpiinventory_agentmodules";
      $sopt[5169]['field']         = "NETWORKDISCOVERY";
      $sopt[5169]['linkfield']     = "NETWORKDISCOVERY";
      $sopt[5169]['name']          = __('Module', 'glpiinventory')."-".__('Network discovery', 'glpiinventory');
      $sopt[5169]['datatype']      = 'bool';
      $sopt[5169]['massiveaction'] = false;

      $sopt[5170]['table']         = "glpi_plugin_glpiinventory_agentmodules";
      $sopt[5170]['field']         = "Collect";
      $sopt[5170]['linkfield']     = "Collect";
      $sopt[5170]['name']          = __('Module', 'glpiinventory')."-".__('Collect', 'glpiinventory');
      $sopt[5170]['datatype']      = 'bool';
      $sopt[5170]['massiveaction'] = false;

      $sopt[5171]['name']          = __('Static group', 'glpiinventory');
      $sopt[5171]['table']         = getTableForItemType('PluginGlpiinventoryDeployGroup');
      $sopt[5171]['massiveaction'] = false;
      $sopt[5171]['field']         = 'name';
      $sopt[5171]['forcegroupby']  = true;
      $sopt[5171]['usehaving']     = true;
      $sopt[5171]['datatype']      = 'dropdown';
      $sopt[5171]['joinparams']    = ['beforejoin'
                                       => ['table'      => 'glpi_plugin_glpiinventory_deploygroups_staticdatas',
                                                'joinparams' => ['jointype'          => 'itemtype_item',
                                                                        'specific_itemtype' => 'Computer']]];

      $sopt[5178]['table']     = 'glpi_plugin_glpiinventory_inventorycomputercomputers';
      $sopt[5178]['field']     = 'hostid';
      $sopt[5178]['name']      = __('HostID', 'glpiinventory');
      $sopt[5178]['joinparams']  = ['jointype' => 'child'];
      $sopt[5178]['massiveaction'] = false;

      $sopt[5179]['table']     = 'glpi_plugin_glpiinventory_computerlicenseinfos';
      $sopt[5179]['field']     = 'serial';
      $sopt[5179]['name']      = __('License serial number', 'glpiinventory');
      $sopt[5179]['joinparams']  = ['jointype' => 'child'];
      $sopt[5179]['massiveaction'] = false;

      $sopt[5180]['table']     = 'glpi_plugin_glpiinventory_computerlicenseinfos';
      $sopt[5180]['field']     = 'fullname';
      $sopt[5180]['name']      = __('License full name', 'glpiinventory');
      $sopt[5180]['joinparams']  = ['jointype' => 'child'];
      $sopt[5180]['massiveaction'] = false;

      $sopt[5181]['table']     = 'glpi_plugin_glpiinventory_computerlicenseinfos';
      $sopt[5181]['field']     = 'name';
      $sopt[5181]['name']      = _n('License', 'Licenses', 2);
      $sopt[5181]['joinparams']  = ['jointype' => 'child'];
      $sopt[5181]['massiveaction'] = false;

      $sopt[5182]['table']     = 'glpi_plugin_glpiinventory_inventorycomputercomputers';
      $sopt[5182]['field']     = 'last_boot';
      $sopt[5182]['name']      = __('FusInv', 'glpiinventory')." - ".__('Last boot', 'glpiinventory');
      $sopt[5182]['joinparams']  = ['jointype' => 'child'];
      $sopt[5182]['datatype']  = 'datetime';
      $sopt[5182]['massiveaction'] = false;

   }

   if ($itemtype == 'Computer') {
      // Switch
      $sopt[5192]['table'] = 'glpi_plugin_glpiinventory_networkequipments';
      $sopt[5192]['field'] = 'name';
      $sopt[5192]['linkfield'] = '';
      $sopt[5192]['name'] = __('FusInv', 'glpiinventory')." - ".__('Switch');
      $sopt[5192]['itemlink_type'] = 'NetworkEquipment';

      // Port of switch
      $sopt[5193]['table'] = 'glpi_plugin_glpiinventory_networkports';
      $sopt[5193]['field'] = 'id';
      $sopt[5193]['linkfield'] = '';
      $sopt[5193]['name'] = __('FusInv', 'glpiinventory')." - ".__('Switch ports');
      $sopt[5193]['forcegroupby'] = '1';

      $sopt += PluginGlpiinventoryCollect::getSearchOptionsToAdd();

      $sopt[5197]['table']         = 'glpi_plugin_glpiinventory_agents';
      $sopt[5197]['field']         = 'last_contact';
      $sopt[5197]['linkfield']     = '';
      $sopt[5197]['joinparams']    = ['jointype' => 'child'];
      $sopt[5197]['name']          = __('FusInv', 'glpiinventory')." - ".
                                     __('Last contact', 'glpiinventory');
   }

   if ($itemtype == 'Entity') {
      // Agent base URL
      $sopt[6192]['table'] = 'glpi_plugin_glpiinventory_entities';
      $sopt[6192]['field'] = 'agent_base_url';
      $sopt[6192]['linkfield'] = '';
      $sopt[6192]['name'] = __('Agent base URL', 'glpiinventory');
      $sopt[6192]['joinparams']  = ['jointype' => 'child'];
      $sopt[6192]['massiveaction'] = false;
   }

   if ($itemtype == 'Printer') {
      // Switch
      $sopt[5192]['table'] = 'glpi_plugin_glpiinventory_networkequipments';
      $sopt[5192]['field'] = 'name';
      $sopt[5192]['linkfield'] = '';
      $sopt[5192]['name'] = __('FusInv', 'glpiinventory')." - ".__('Switch');
      $sopt[5192]['itemlink_type'] = 'NetworkEquipment';

      // Port of switch
      $sopt[5193]['table'] = 'glpi_plugin_glpiinventory_networkports';
      $sopt[5193]['field'] = 'id';
      $sopt[5193]['linkfield'] = '';
      $sopt[5193]['name'] = __('FusInv', 'glpiinventory')." - ".__('Hardware ports');
      $sopt[5193]['forcegroupby'] = '1';

      $sopt[5191]['table'] = 'glpi_plugin_glpiinventory_configsecurities';
      $sopt[5191]['field'] = 'name';
      $sopt[5191]['linkfield'] = 'plugin_glpiinventory_configsecurities_id';
      $sopt[5191]['name'] = __('FusInv', 'glpiinventory')." - ".
                             __('SNMP credentials', 'glpiinventory');
      $sopt[5191]['datatype'] = 'itemlink';
      $sopt[5191]['itemlink_type'] = 'PluginGlpiinventoryConfigSecurity';
      $sopt[5191]['massiveaction'] = false;

      $sopt[5194]['table'] = 'glpi_plugin_glpiinventory_printers';
      $sopt[5194]['field'] = 'last_fusioninventory_update';
      $sopt[5194]['linkfield'] = '';
      $sopt[5194]['name'] = __('FusInv', 'glpiinventory')." - ".
         __('Last inventory', 'glpiinventory');
      $sopt[5194]['datatype'] = 'datetime';
      $sopt[5194]['massiveaction'] = false;

      $sopt[5196]['table']         = 'glpi_plugin_glpiinventory_printers';
      $sopt[5196]['field']         = 'sysdescr';
      $sopt[5196]['linkfield']     = '';
      $sopt[5196]['name']          = __('Sysdescr', 'glpiinventory');
      $sopt[5196]['datatype']      = 'text';
   }

   if ($itemtype == 'NetworkEquipment') {

      $sopt[5191]['table'] = 'glpi_plugin_glpiinventory_configsecurities';
      $sopt[5191]['field'] = 'name';
      $sopt[5191]['linkfield'] = 'plugin_glpiinventory_configsecurities_id';
      $sopt[5191]['name'] = __('FusInv', 'glpiinventory')." - ".
                             __('SNMP credentials', 'glpiinventory');
      $sopt[5191]['datatype'] = 'itemlink';
      $sopt[5191]['itemlink_type'] = 'PluginGlpiinventoryConfigSecurity';
      $sopt[5191]['massiveaction'] = false;

      $sopt[5194]['table'] = 'glpi_plugin_glpiinventory_networkequipments';
      $sopt[5194]['field'] = 'last_fusioninventory_update';
      $sopt[5194]['linkfield'] = '';
      $sopt[5194]['name'] = __('FusInv', 'glpiinventory')." - ".
         __('Last inventory', 'glpiinventory');
      $sopt[5194]['datatype'] = 'datetime';
      $sopt[5194]['massiveaction'] = false;

      $sopt[5195]['table'] = 'glpi_plugin_glpiinventory_networkequipments';
      $sopt[5195]['field'] = 'cpu';
      $sopt[5195]['linkfield'] = '';
      $sopt[5195]['name'] = __('FusInv', 'glpiinventory')." - ".
         __('CPU usage (in %)', 'glpiinventory');

      $sopt[5195]['datatype'] = 'number';

      $sopt[5196]['table']         = 'glpi_plugin_glpiinventory_networkequipments';
      $sopt[5196]['field']         = 'sysdescr';
      $sopt[5196]['linkfield']     = '';
      $sopt[5196]['name']          = __('Sysdescr', 'glpiinventory');
      $sopt[5196]['datatype']      = 'text';

   }
   return $sopt;
}


/**
 * Manage search give items (display information in the search page)
 *
 * @global array $CFG_GLPI
 * @param string $type
 * @param integer $id
 * @param array $data
 * @param integer $num
 * @return string
 */
function plugin_glpiinventory_giveItem($type, $id, $data, $num) {
   global $CFG_GLPI, $DB;

   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$id]["table"];
   $field = $searchopt[$id]["field"];

   switch ($table.'.'.$field) {

      case "glpi_plugin_glpiinventory_taskjobs.status":
         $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
         return $pfTaskjobstate->stateTaskjob($data['raw']['id'], '200', 'htmlvar', 'simple');

      case "glpi_plugin_glpiinventory_agents.version":
         $array = importArrayFromDB($data['raw']['ITEM_'.$num]);
         $input = "";
         foreach ($array as $name => $version) {
            $input .= "<strong>".$name."</strong> : ".$version."<br/>";
         }
         $input .= "*";
         $input = str_replace("<br/>*", "", $input);
         return $input;

      case "glpi_plugin_glpiinventory_credentials.itemtype":
         if ($label == PluginGlpiinventoryCredential::getLabelByItemtype($data['raw']['ITEM_'.$num])) {
            return $label;
         } else {
            return '';
         }
        break;

      case 'glpi_plugin_glpiinventory_taskjoblogs.state':
         $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
        return $pfTaskjoblog->getDivState($data['raw']['ITEM_'.$num]);

      case 'glpi_plugin_glpiinventory_taskjoblogs.comment':
         $comment = $data['raw']['ITEM_'.$num];
         return PluginGlpiinventoryTaskjoblog::convertComment($comment);

      case 'glpi_plugin_glpiinventory_taskjobstates.plugin_glpiinventory_agents_id':
         $pfAgent = new PluginGlpiinventoryAgent();
         $pfAgent->getFromDB($data['raw']['ITEM_'.$num]);
         if (!isset($pfAgent->fields['name'])) {
            return NOT_AVAILABLE;
         }
         $itemtype = PluginGlpiinventoryTaskjoblog::getStateItemtype($data['raw']['ITEM_0']);
         if ($itemtype == 'PluginGlpiinventoryDeployPackage') {
            $computer = new Computer();
            $computer->getFromDB($pfAgent->fields['computers_id']);
            return $computer->getLink(1);
         }
         return $pfAgent->getLink(1);

      case 'glpi_plugin_glpiinventory_ignoredimportdevices.ip':
      case 'glpi_plugin_glpiinventory_ignoredimportdevices.mac':
         $array = importArrayFromDB($data['raw']['ITEM_'.$num]);
         return implode("<br/>", $array);

      case 'glpi_plugin_glpiinventory_ignoredimportdevices.method':
         $a_methods = PluginGlpiinventoryStaticmisc::getmethods();
         foreach ($a_methods as $mdata) {
            if ($mdata['method'] == $data['raw']['ITEM_'.$num]) {
               return $mdata['name'];
            }
         }
         break;
   }

   if ($table == "glpi_plugin_glpiinventory_agentmodules") {
      if ($type == 'Computer') {
         $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
         $a_modules = $pfAgentmodule->find(['modulename' => $field]);
         $data2 = current($a_modules);
         if ($table.".".$field == "glpi_plugin_glpiinventory_agentmodules.".$data2['modulename']) {
            if (strstr($data['raw']["ITEM_".$num."_0"], '"'.$data['raw']["ITEM_".$num."_1"].'"')) {
               if ($data['raw']['ITEM_'.$num] == '0') {
                  return Dropdown::getYesNo(true);
               } else {
                  return Dropdown::getYesNo(false);
               }

            }
            return Dropdown::getYesNo($data['raw']['ITEM_'.$num]);
         }
      } else {
         $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
         $a_modules = $pfAgentmodule->find(['modulename' => $field]);
         foreach ($a_modules as $data2) {
            if ($table.".".$field == "glpi_plugin_glpiinventory_agentmodules.".$data2['modulename']) {
               if (strstr($data['raw']["ITEM_".$num."_0"], '"'.$data['raw']['id'].'"')) {
                  if ($data['raw']['ITEM_'.$num] == 0) {
                     return Dropdown::getYesNo('1');
                  } else {
                     return Dropdown::getYesNo('0');
                  }
               }
               return Dropdown::getYesNo($data['raw']['ITEM_'.$num]);
            }
         }
      }
   }

   switch ($type) {

      case 'Computer':
         if ($table.'.'.$field == 'glpi_plugin_glpiinventory_networkports.id') {
            if (strstr($data['raw']["ITEM_$num"], "$")) {
               $split = explode("$$$$", $data['raw']["ITEM_$num"]);
               $ports = [];

               foreach ($split as $portconcat) {
                  $split2 = explode("....", $portconcat);
                  if (isset($split2[1])) {
                     $ports[] = $split2[1];
                  }
               }
               $out = implode("<br/>", $ports);
               return $out;
            }
         }
         break;

      case 'Printer':
         if ($table.'.'.$field == 'glpi_plugin_glpiinventory_networkequipments.name') {
            if (strstr($data['raw']["ITEM_$num"], "$")) {
               $split = explode("$$$$", $data['raw']["ITEM_$num"]);
               $out = implode("<br/>", $split);
               return $out;
            }
         }
         break;

      // * Authentification List (plugins/fusinvsnmp/front/configsecurity.php)
      case 'PluginGlpiinventoryConfigSecurity' :
         switch ($table.'.'.$field) {

            // ** Hidden auth passphrase (SNMP v3)
            case "glpi_plugin_glpiinventory_configsecurities.auth_passphrase" :
               $out = "";
               if (!empty($data['raw']["ITEM_$num"])) {
                  $out = "********";
               }
               return $out;

            // ** Hidden priv passphrase (SNMP v3)
            case "glpi_plugin_glpiinventory_configsecurities.priv_passphrase" :
               $out = "";
               if (!empty($data['raw']["ITEM_$num"])) {
                  $out = "********";
               }
               return $out;

         }
         break;

      // * Unknown mac addresses connectd on switch - report
      //   (plugins/fusinvsnmp/report/unknown_mac.php)
      case 'PluginGlpiinventoryUnmanaged' :
         switch ($table.'.'.$field) {

            // ** switch
            case "glpi_plugin_glpiinventory_networkequipments.id" :
               $out = '';
               $NetworkPort = new NetworkPort;
               $list = explode("$$$$", $data['raw']["ITEM_$num"]);
               foreach ($list as $numtmp => $vartmp) {
                  $NetworkPort->getDeviceData($vartmp, 'PluginGlpiinventoryUnmanaged');

                  $out .= "<a href=\"".Plugin::getWebDir('glpiinventory');
                  $out .= "/front/unmanaged.form.php?id=".$vartmp.
                             "\">";
                  $out .= $NetworkPort->device_name;
                  if ($CFG_GLPI["view_ID"]) {
                     $out .= " (".$vartmp.")";
                  }
                  $out .= "</a><br/>";
               }
               return "<center>".$out."</center>";

            // ** switch port
            case "glpi_plugin_glpiinventory_networkports.id" :
               $out = '';
               if (!empty($data['raw']["ITEM_$num"])) {
                  $list = explode("$$$$", $data['raw']["ITEM_$num"]);
                  $np = new NetworkPort;
                  foreach ($list as $numtmp => $vartmp) {
                     $np->getFromDB($vartmp);
                     $out .= "<a href='".$CFG_GLPI['root_doc']."/front/networkport.form.php?id=".
                                $vartmp."'>";
                     $out .= $np->fields["name"]."</a><br/>";
                  }
               }
               return "<center>".$out."</center>";

            case "glpi_plugin_fusinvsnmp_unmanageds.type" :
               $out = '<center> ';
               switch ($data['raw']["ITEM_$num"]) {
                  case COMPUTER_TYPE:
                     $out .= __('Computers');
                     break;

                  case NETWORKING_TYPE:
                     $out .= __('Networks');
                     break;

                  case PRINTER_TYPE:
                     $out .= __('Printers');
                     break;

                  case PERIPHERAL_TYPE:
                     $out .= __('Devices');
                     break;

                  case PHONE_TYPE:
                     $out .= __('Phones');
                     break;
               }
               $out .= '</center>';
               return $out;

         }
         break;

      // * Ports date connection - report (plugins/fusinvsnmp/report/ports_date_connections.php)
      case 'PluginGlpiinventoryNetworkPort' :
         switch ($table.'.'.$field) {

            // ** Name and link of networking device (switch)
            case "glpi_plugin_glpiinventory_networkports.id" :
               $data2 = $DB->request([
                  'SELECT'    => [
                     'glpi_networkequipments.name AS name',
                     'glpi_networkequipments.id AS id'
                  ],
                  'FROM'      => 'glpi_networkequipments',
                  'LEFT JOIN' => [
                     'glpi_networkports' => [
                        'FKEY' => [
                           'glpi_networkequipments'   => 'id',
                           'glpi_networkports'        => 'items_id'
                        ]
                     ],
                     'glpi_plugin_glpiinventory_networkports' => [
                        'FKEY' => [
                           'glpi_networkports'                          => 'id',
                           'glpi_plugin_glpiinventory_networkports'   => 'items_id'
                        ]
                     ]
                  ],
                  'WHERE'     => [
                     'glpi_plugin_glpiinventory_networkports.id' => $data['raw']["ITEM_$num"]
                  ],
                  'START'     => 0,
                  'LIMIT'     => 1
               ])->current();
               $out = "<a href='".$CFG_GLPI['root_doc']."/front/networking.form.php?id=".
                          $data2["id"]."'>";
               $out .= $data2["name"]."</a>";
               return "<center>".$out."</center>";

            // ** Name and link of port of networking device (port of switch)
            case "glpi_plugin_glpiinventory_networkports.networkports_id" :
               $NetworkPort = new NetworkPort;
               $NetworkPort->getFromDB($data['raw']["ITEM_$num"]);
               $name = "";
               if (isset($NetworkPort->fields["name"])) {
                  $name = $NetworkPort->fields["name"];
               }
               $out = "<a href='".$CFG_GLPI['root_doc']."/front/networkport.form.php?id=".
                          $data['raw']["ITEM_$num"];
               $out .= "'>".$name."</a>";
               return "<center>".$out."</center>";

            // ** Location of switch
            case "glpi_locations.id" :
               $out = Dropdown::getDropdownName("glpi_locations", $data['raw']["ITEM_$num"]);
               return "<center>".$out."</center>";

         }
         break;

      // * range IP list (plugins/fusinvsnmp/front/iprange.php)
      case 'PluginGlpiinventoryIPRange' :
         switch ($table.'.'.$field) {

            // ** Display entity name
            case "glpi_entities.name" :
               if ($data['raw']["ITEM_$num"] == '') {
                  $out = Dropdown::getDropdownName("glpi_entities", $data['raw']["ITEM_$num"]);
                  return "<center>".$out."</center>";
               }
               break;

         }
         break;

      case 'PluginGlpiinventoryNetworkPortLog' :
         switch ($table.'.'.$field) {

            // ** Display switch and Port
            case "glpi_networkports.id" :
               $Array_device = PluginGlpiinventoryNetworkPort::getUniqueObjectfieldsByportID(
                             $data['raw']["ITEM_$num"]
                          );
               $item = new $Array_device["itemtype"];
               $item->getFromDB($Array_device["items_id"]);
               $out = "<div align='center'>" . $item->getLink(1);

               $iterator = $DB->request([
                  'FROM'   => 'glpi_networkports',
                  'WHERE'  => ['id' => $data['raw']["ITEM_$num"]]
               ]);

               if (count($iterator)) {
                  $row = $iterator->current();
                  $out .= "<br/><a href='".$CFG_GLPI['root_doc']."/front/networkport.form.php?id=";
                  $out .= $data['raw']["ITEM_$num"]."'>".$row['name']."</a>";
               }
               $out .= "</td>";
               return $out;

            // ** Display GLPI field of device
            case "glpi_plugin_fusinvsnmp_networkportlogs.field" :
               //               $out = $GLPIINVENTORY_MAPPING[NETWORKING_TYPE][$data['raw']["ITEM_$num"]]['name'];
               $out = '';
               $map = new PluginGlpiinventoryMapping;
               $mapfields = $map->get('NetworkEquipment', $data['raw']["ITEM_$num"]);
               if ($mapfields != false) {
                  $out = _get('_LANG[\'plugin_fusinvsnmp\'][\'mapping\'][$mapfields["locale"]]');
               }
               return $out;

            // ** Display Old Value (before changement of value)
            case "glpi_plugin_fusinvsnmp_networkportlogs.old_value" :
               // TODO ADD LINK TO DEVICE
               if ((substr_count($data['raw']["ITEM_$num"], ":") == 5)
                       && (empty($data['raw']["ITEM_3"]))) {
                  return "<center><b>".$data['raw']["ITEM_$num"]."</b></center>";
               }
               break;

            // ** Display New Value (new value modified)
            case "glpi_plugin_fusinvsnmp_networkportlogs.new_value" :
               if ((substr_count($data['raw']["ITEM_$num"], ":") == 5)
                       && (empty($data['raw']["ITEM_3"]))) {
                  return "<center><b>".$data['raw']["ITEM_$num"]."</b></center>";
               }
               break;

         }
         break;

      case "PluginGlpiinventoryPrinterLog":
         switch ($table.'.'.$field) {

            case 'glpi_printers.name':

               // Search pages in printer history to limit SQL queries
               if (isset($_SESSION['glpi_plugin_glpiinventory_history_start'])) {
                  unset($_SESSION['glpi_plugin_glpiinventory_history_start']);
               }
               if (isset($_SESSION['glpi_plugin_glpiinventory_history_end'])) {
                  unset($_SESSION['glpi_plugin_glpiinventory_history_end']);
               }
               if ((isset($_SESSION['glpi_plugin_glpiinventory_date_start']))
                       AND (isset($_SESSION['glpi_plugin_glpiinventory_date_end']))) {

                  $query = "SELECT * FROM `glpi_plugin_glpiinventory_printerlogs`
                     WHERE `printers_id`='".$data['raw']['ITEM_0_2']."'
                        AND `date`>= '".$_SESSION['glpi_plugin_glpiinventory_date_start']."'
                        AND `date`<= '".$_SESSION['glpi_plugin_glpiinventory_date_end']." 23:59:59'
                     ORDER BY date asc
                     LIMIT 1";
                  $result = $DB->query($query);
                  while ($data2 = $DB->fetchArray($result)) {
                     $_SESSION['glpi_plugin_glpiinventory_history_start'] = $data2;
                  }
                  $query = "SELECT * FROM `glpi_plugin_glpiinventory_printerlogs`
                     WHERE `printers_id`='".$data['raw']['ITEM_0_2']."'
                        AND `date`>= '".$_SESSION['glpi_plugin_glpiinventory_date_start']."'
                        AND `date`<= '".$_SESSION['glpi_plugin_glpiinventory_date_end']." 23:59:59'
                     ORDER BY date desc
                     LIMIT 1";
                  $result = $DB->query($query);
                  while ($data2 = $DB->fetchArray($result)) {
                     $_SESSION['glpi_plugin_glpiinventory_history_end'] = $data2;
                  }
               }
               return "";

         }

         switch ($table) {

            case 'glpi_plugin_glpiinventory_printerlogs':
               if ((isset($_SESSION['glpi_plugin_glpiinventory_history_start'][$field]))
                      AND (isset($_SESSION['glpi_plugin_glpiinventory_history_end'][$field]))) {
                  $counter_start = $_SESSION['glpi_plugin_glpiinventory_history_start'][$field];
                  $counter_end = $_SESSION['glpi_plugin_glpiinventory_history_end'][$field];
                  if ($_SESSION['glpi_plugin_glpiinventory_date_start'] == "1970-01-01") {
                     $counter_start = 0;
                  }
                  $number = $counter_end - $counter_start;
                  if (($number == '0')) {
                      return '-';
                  } else {
                     return $number;
                  }

               } else {
                  return '-';
               }
               break;

         }
         break;

      case "PluginGlpiinventoryIgnoredimportdevice":
         switch ($table.'.'.$field) {

            case 'glpi_rules.id':
               $rule = new Rule();
               if ($rule->getFromDB($data['raw']["ITEM_$num"])) {
                  $out = "<a href='".Plugin::getWebDir('glpiinventory')."/front/inventoryruleimport.form.php?id=";
                  $out .= $data['raw']["ITEM_$num"]."'>".$rule->fields['name']."</a>";
                  return $out;
               }
         }
         break;
   }

   return "";
}


/**
 * Manage search options values
 *
 * @global object $DB
 * @param object $item
 * @return boolean
 */
function plugin_glpiinventory_searchOptionsValues($item) {
   global $DB;

   if ($item['searchoption']['table'] == 'glpi_plugin_glpiinventory_taskjoblogs'
           AND $item['searchoption']['field'] == 'state') {
      $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
      $elements = $pfTaskjoblog->dropdownStateValues();
      Dropdown::showFromArray($item['name'], $elements, ['value' => $item['value']]);
      return true;
   } else if ($item['searchoption']['table'] == 'glpi_plugin_glpiinventory_taskjobstates'
           AND $item['searchoption']['field'] == 'uniqid') {
      $elements = [];
      $iterator = $DB->request([
         'FROM'      => $item['searchoption']['table'],
         'GROUPBY'   => 'uniqid',
         'ORDER'     => 'uniqid'
      ]);
      foreach ($iterator as $data) {
         $elements[$data['uniqid']] = $data['uniqid'];
      }
      Dropdown::showFromArray($item['name'], $elements, ['value' => $item['value']]);
      return true;
   }
}


/**
 * Define Dropdown tables to be manage in GLPI
 *
 * @return array
 */
function plugin_glpiinventory_getDropdown() {
   return [];
}


/**
 * Manage GLPI cron
 *
 * @return integer
 */
function cron_plugin_glpiinventory() {
   //   TODO :Disable for the moment (may be check if functions is good or not
   //   $ptud = new PluginGlpiinventoryUnmanaged;
   //   $ptud->cleanOrphelinsConnections();
   //   $ptud->FusionUnknownKnownDevice();
   //   TODO : regarder les 2 lignes juste en dessous !!!!!
   //   #Clean server script processes history
   //   $pfisnmph = new PluginGlpiinventoryNetworkPortLog;
   //   $pfisnmph->cronCleanHistory();

   return 1;
}


/**
 * Manage the installation process
 *
 * @return boolean
 */
function plugin_glpiinventory_install() {
   ini_set("max_execution_time", "0");

   if (basename(filter_input(INPUT_SERVER, "SCRIPT_NAME")) != "cli_install.php") {
      if (!isCommandLine()) {
         Html::header(__('Setup', 'glpiinventory'), filter_input(INPUT_SERVER, "PHP_SELF"), "config", "plugins");
      }
      $migrationname = 'Migration';
   } else {
      $migrationname = 'CliMigration';
   }

   require_once (PLUGIN_GLPI_INVENTORY_DIR . "/install/update.php");
   $version_detected = pluginGlpiinventoryGetCurrentVersion();

   if (!defined('FORCE_INSTALL')
      &&
      isset($version_detected)
      && (
         defined('FORCE_UPGRADE')
         || (
            $version_detected != '0'
         )
      )) {
      // note: if version detected = version found can have problem, so need
      //       pass in upgrade to be sure all OK
      pluginGlpiinventoryUpdate($version_detected, $migrationname);
   } else {
      require_once (PLUGIN_GLPI_INVENTORY_DIR . "/install/install.php");
      pluginGlpiinventoryInstall(PLUGIN_GLPI_INVENTORY_VERSION, $migrationname);
   }
   return true;
}


/**
 * Manage the uninstallation of the plugin
 *
 * @return boolean
 */
function plugin_glpiinventory_uninstall() {
   require_once(PLUGIN_GLPI_INVENTORY_DIR . "/inc/setup.class.php");
   require_once(PLUGIN_GLPI_INVENTORY_DIR . "/inc/profile.class.php");
   require_once(PLUGIN_GLPI_INVENTORY_DIR . "/inc/inventoryruleimport.class.php");
   return PluginGlpiinventorySetup::uninstall();
}


/**
 * Add massive actions to GLPI itemtypes
 *
 * @param string $type
 * @return array
 */
function plugin_glpiinventory_MassiveActions($type) {

   $sep = MassiveAction::CLASS_ACTION_SEPARATOR;
   $ma = [];

   switch ($type) {

      case "Computer":
         if (Session::haveRight('plugin_glpiinventory_lock', UPDATE)) {
            $ma["PluginGlpiinventoryLock".$sep."manage_locks"]
               = _n('Lock', 'Locks', Session::getPluralNumber(), 'glpiinventory')." (".strtolower(_n('Field', 'Fields', Session::getPluralNumber())).")";
         }
         if (Session::haveRight('plugin_glpiinventory_task', UPDATE)) {
            $ma["PluginGlpiinventoryTask".$sep."target_task"]
               = __('Target a task', 'glpiinventory');
         }
         if (Session::haveRight('plugin_glpiinventory_group', UPDATE)) {
            $ma["PluginGlpiinventoryDeployGroup".$sep."add_to_static_group"]
               = __('Add to static group', 'glpiinventory');
         }
         break;

      case "NetworkEquipment":
      case "Printer":
         if (Session::haveRight('plugin_glpiinventory_lock', UPDATE)) {
            $ma["PluginGlpiinventoryLock".$sep."manage_locks"]
               = _n('Lock', 'Locks', Session::getPluralNumber(), 'glpiinventory')." (".strtolower(_n('Field', 'Fields', Session::getPluralNumber())).")";
         }
         if (Session::haveRight('plugin_glpiinventory_configsecurity', UPDATE)) {
            $ma["PluginGlpiinventoryConfigSecurity".$sep."assign_auth"]
               = __('Assign SNMP credentials', 'glpiinventory');
         }

         break;
   }
   return $ma;
}


/**
 * Manage massice actions fields display
 *
 * @param array $options
 * @return boolean
 */
function plugin_glpiinventory_MassiveActionsFieldsDisplay($options = []) {

   $table = $options['options']['table'];
   $field = $options['options']['field'];
   $linkfield = $options['options']['linkfield'];

   switch ($table.".".$field) {

      case "glpi_plugin_glpiinventory_unmanageds.item_type":
         $type_list = [];
         $type_list[] = 'Computer';
         $type_list[] = 'NetworkEquipment';
         $type_list[] = 'Printer';
         $type_list[] = 'Peripheral';
         $type_list[] = 'Phone';
         Dropdown::showItemTypes($linkfield, $type_list,
                                          ['value' => 0]);
         return true;

      case 'glpi_plugin_glpiinventory_configsecurities.name':
         Dropdown::show("PluginGlpiinventoryConfigSecurity",
                        ['name' => $linkfield]);
         return true;

      case 'glpi_plugin_glpiinventory_agents.id' :
         Dropdown::show("PluginFusinvsnmpAgent",
                        ['name' => $linkfield,
                              'comment' => false]);
         return true;

      case 'glpi_plugin_glpiinventory_agents.threads_networkdiscovery' :
         Dropdown::showNumber("threads_networkdiscovery", [
             'value' => $linkfield,
             'min' => 1,
             'max' => 400]
         );
         return true;

      case 'glpi_plugin_glpiinventory_agents.threads_networkinventory' :
         Dropdown::showNumber("threads_networkinventory", [
             'value' => $linkfield,
             'min' => 1,
             'max' => 400]
         );
         return true;

      case 'glpi_entities.name' :
         if (Session::isMultiEntitiesMode()) {
            Dropdown::show("Entities",
                           ['name' => "entities_id",
                           'value' => $_SESSION["glpiactive_entity"]]);
         }
         return true;

   }
   return false;
}


/**
 * Manage Add select to search query
 *
 * @param string $type
 * @param integer $id
 * @param integer $num
 * @return string
 */
function plugin_glpiinventory_addSelect($type, $id, $num) {

   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$id]["table"];
   $field = $searchopt[$id]["field"];

   switch ($type) {

      case 'PluginGlpiinventoryAgent':

         $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
         $a_modules = $pfAgentmodule->find();
         foreach ($a_modules as $data) {
            if ($table.".".$field == "glpi_plugin_glpiinventory_agentmodules.".$data['modulename']) {
               return " `FUSION_".$data['modulename']."`.`is_active` AS ITEM_$num, ".
                          "`FUSION_".$data['modulename']."`.`exceptions`  AS ITEM_".$num."_0, ";
            }
         }
         break;

      case 'Computer':

         switch ($table.".".$field) {

            // ** switch
            case "glpi_plugin_glpiinventory_networkequipments.name" :
               return "GROUP_CONCAT(DISTINCT glpi_networkequipments.name SEPARATOR '$$$$') AS ITEM_$num, ";

            // ** switch port
            case "glpi_plugin_glpiinventory_networkports.id" :
               return "GROUP_CONCAT( DISTINCT
                     CONCAT_WS('....', GLPIINVENTORY_22.items_id, GLPIINVENTORY_22.name)
                  SEPARATOR '$$$$') AS ITEM_$num, ";

         }
         $a_agent_modules = PluginGlpiinventoryAgentmodule::getModules();
         foreach ($a_agent_modules as $module) {
            if ($table.".".$field == 'glpi_plugin_glpiinventory_agentmodules.'.$module) {

               return " `FUSION_".$module."`.`is_active` AS ITEM_$num, ".
                          "`FUSION_".$module."`.`exceptions`  AS ITEM_".$num."_0, ".
                          "`agent".strtolower($module)."`.`id`  AS ITEM_".$num."_1, ";
            }
         }
         break;

      // * PRINTER List (front/printer.php)
      case 'Printer':
         switch ($table.".".$field) {

            // ** switch
            case "glpi_plugin_glpiinventory_networkequipments.name" :
               return "GROUP_CONCAT(glpi_networkequipments.name SEPARATOR '$$$$') AS ITEM_$num, ";

            // ** switch port
            case "glpi_plugin_glpiinventory_networkports.id" :
               return "GROUP_CONCAT( GLPIINVENTORY_22.name SEPARATOR '$$$$') AS ITEM_$num, ";

         }
         break;

      case 'PluginGlpiinventoryUnmanaged' :
         switch ($table.".".$field) {

            case "glpi_networkequipments.device" :
               return "GROUP_CONCAT( DISTINCT GLPIINVENTORY_12.items_id SEPARATOR '$$$$') ".
                          "AS ITEM_$num, ";

            case "glpi_networkports.NetworkPort" :
               return "GROUP_CONCAT( DISTINCT GLPIINVENTORY_22.".$field." SEPARATOR '$$$$') ".
                          "AS ITEM_$num, ";

         }
         break;

      case 'PluginGlpiinventoryIPRange' :
         switch ($table.".".$searchopt[$id]["linkfield"]) {

            case "glpi_plugin_glpiinventory_agents.plugin_fusinvsnmp_agents_id_query" :
               return "GROUP_CONCAT( DISTINCT CONCAT(gpta.name, '$$', gpta.id) SEPARATOR '$$$$') ".
                          "AS ITEM_$num, ";

         }
         break;

      case "PluginGlpiinventoryPrinterLog":
         if ($table.".".$field == "glpi_users.name") {
            return " `glpi_users`.`name` AS ITEM_$num, `glpi_users`.`realname` ".
                        "AS ITEM_".$num."_2, `glpi_users`.`id` AS ITEM_".$num."_3, ".
                        "`glpi_users`.`firstname` AS ITEM_".$num."_4, ";
         }
         break;

      case 'PluginGlpiinventoryPrinterLogReport':

         if ($table == 'glpi_plugin_glpiinventory_printerlogs') {
            if (strstr($field, 'pages_') OR $field == 'scanned') {
               return " (
                  (SELECT ".$field." FROM glpi_plugin_glpiinventory_printerlogs
                     WHERE printers_id = glpi_printers.id
                        AND date <= '".$_SESSION['glpi_plugin_glpiinventory_date_end']." 23:59:59'
                     ORDER BY date DESC LIMIT 1)
                  -
                  (SELECT ".$field." FROM glpi_plugin_glpiinventory_printerlogs
                     WHERE printers_id = glpi_printers.id
                        AND date >= '".$_SESSION['glpi_plugin_glpiinventory_date_start'].
                           " 00:00:00'  ORDER BY date  LIMIT 1)
                  )  AS ITEM_$num, ";
            }
         }
         break;

   }
   return "";
}


/**
 * Manage group by in search query
 *
 * @param string $type
 * @return boolean
 */
function plugin_glpiinventory_forceGroupBy($type) {
    return false;
}


/**
 * Manage left join in search query
 *
 * @param string $itemtype
 * @param string $ref_table
 * @param string $new_table
 * @param string $linkfield
 * @param string $already_link_tables
 * @return string
 */
function plugin_glpiinventory_addLeftJoin($itemtype, $ref_table, $new_table, $linkfield,
                                            &$already_link_tables) {

   switch ($itemtype) {

      case 'PluginGlpiinventoryAgent':
         $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
         $a_modules = $pfAgentmodule->find();
         foreach ($a_modules as $data) {
            if ($new_table.".".$linkfield == "glpi_plugin_glpiinventory_agentmodules.".$data['modulename']) {
               return " LEFT JOIN `glpi_plugin_glpiinventory_agentmodules` AS FUSION_".
                       $data['modulename']."
                          ON FUSION_".$data['modulename'].".`modulename`=".
                             "'".$data['modulename']."' ";
            }
         }
         break;

      case 'PluginGlpiinventoryTaskjoblog':
         //         echo $new_table.".".$linkfield."<br/>";
         $taskjob = 0;
         $already_link_tables_tmp = $already_link_tables;
         array_pop($already_link_tables_tmp);
         foreach ($already_link_tables_tmp AS $tmp_table) {
            if ($tmp_table == "glpi_plugin_glpiinventory_tasks"
                    OR $tmp_table == "glpi_plugin_glpiinventory_taskjobs"
                    OR $tmp_table == "glpi_plugin_glpiinventory_taskjobstates") {
               $taskjob = 1;
            }
         }

         switch ($new_table.".".$linkfield) {

            case 'glpi_plugin_glpiinventory_tasks.plugin_glpiinventory_tasks_id':
               $ret = '';
               if ($taskjob == '0') {
                  $ret = ' LEFT JOIN `glpi_plugin_glpiinventory_taskjobstates` ON
                     (`plugin_glpiinventory_taskjobstates_id` = '.
                          '`glpi_plugin_glpiinventory_taskjobstates`.`id` )
                  LEFT JOIN `glpi_plugin_glpiinventory_taskjobs` ON
                     (`plugin_glpiinventory_taskjobs_id` = '.
                          '`glpi_plugin_glpiinventory_taskjobs`.`id` ) ';
               }
               $ret .= ' LEFT JOIN `glpi_plugin_glpiinventory_tasks` ON
                  (`plugin_glpiinventory_tasks_id` = `glpi_plugin_glpiinventory_tasks`.`id`) ';
               return $ret;

            case 'glpi_plugin_glpiinventory_taskjobs.plugin_glpiinventory_taskjobs_id':
            case 'glpi_plugin_glpiinventory_taskjobstates.'.
                    'plugin_glpiinventory_taskjobstates_id':
               if ($taskjob == '0') {
                  return ' LEFT JOIN `glpi_plugin_glpiinventory_taskjobstates` ON
                     (`plugin_glpiinventory_taskjobstates_id` = '.
                          '`glpi_plugin_glpiinventory_taskjobstates`.`id` )
                  LEFT JOIN `glpi_plugin_glpiinventory_taskjobs` ON
                     (`plugin_glpiinventory_taskjobs_id` = '.
                          '`glpi_plugin_glpiinventory_taskjobs`.`id` ) ';
               }
               return ' ';

         }
         break;

      case 'PluginGlpiinventoryTask':
         if ($new_table.".".$linkfield == 'glpi_plugin_glpiinventory_taskjoblogs.'.
                 'plugin_glpiinventory_taskjoblogs_id') {
            return "LEFT JOIN `glpi_plugin_glpiinventory_taskjobs` AS taskjobs
                     ON `plugin_glpiinventory_tasks_id` = `glpi_plugin_glpiinventory_tasks`.`id`
               LEFT JOIN `glpi_plugin_glpiinventory_taskjobstates` AS taskjobstates
                     ON taskjobstates.`id` =
                  (SELECT MAX(`id`)
                     FROM glpi_plugin_glpiinventory_taskjobstates
                   WHERE plugin_glpiinventory_taskjobs_id = taskjobs.`id`
                   ORDER BY id DESC
                   LIMIT 1
                  )
               LEFT JOIN `glpi_plugin_glpiinventory_taskjoblogs`
                  ON `glpi_plugin_glpiinventory_taskjoblogs`.`id` =
                  (SELECT MAX(`id`)
                     FROM `glpi_plugin_glpiinventory_taskjoblogs`
                   WHERE `plugin_glpiinventory_taskjobstates_id`= taskjobstates.`id`
                   ORDER BY id DESC LIMIT 1
                  ) ";
         }
         break;

      case 'Computer':
      case 'PluginGlpiinventoryComputer':
         switch ($new_table.".".$linkfield) {

            case 'glpi_plugin_glpiinventory_agents.plugin_glpiinventory_agents_id':
                return " LEFT JOIN `glpi_plugin_glpiinventory_agents`
                  ON (`glpi_computers`.`id`=`glpi_plugin_glpiinventory_agents`.`computers_id`) ";
                break;

            case 'glpi_plugin_glpiinventory_inventorycomputercomputers.plugin_glpiinventory_inventorycomputercomputers_id':
               return " LEFT JOIN `glpi_plugin_glpiinventory_inventorycomputercomputers`
                    AS glpi_plugin_glpiinventory_inventorycomputercomputers
                    ON (`glpi_computers`.`id` = ".
                    "`glpi_plugin_glpiinventory_inventorycomputercomputers`.".
                    "`computers_id` ) ";

            // ** switch
            case "glpi_plugin_glpiinventory_networkequipments.plugin_glpiinventory_networkequipments_id" :
               $table_networking_ports = 0;
               foreach ($already_link_tables AS $num => $tmp_table) {
                  if ($tmp_table == "glpi_networkports.") {
                     $table_networking_ports = 1;
                  }
               }
               if ($table_networking_ports == "1") {
                  return " LEFT JOIN glpi_networkports_networkports AS GLPIINVENTORY_11 ON glpi_networkports.id = GLPIINVENTORY_11.networkports_id_1 OR glpi_networkports.id = GLPIINVENTORY_11.networkports_id_2 ".
                     " LEFT JOIN glpi_networkports AS GLPIINVENTORY_12 ON GLPIINVENTORY_12.id = CASE WHEN GLPIINVENTORY_11.networkports_id_1 = glpi_networkports.id THEN GLPIINVENTORY_11.networkports_id_2 ELSE GLPIINVENTORY_11.networkports_id_1 END
                     LEFT JOIN glpi_networkequipments ON GLPIINVENTORY_12.items_id=glpi_networkequipments.id ";

               } else {
                  return " LEFT JOIN glpi_networkports AS GLPIINVENTORY_10 ON (GLPIINVENTORY_10.items_id = glpi_computers.id AND GLPIINVENTORY_10.itemtype='Computer') ".
                     " LEFT JOIN glpi_networkports_networkports AS GLPIINVENTORY_11 ON GLPIINVENTORY_10.id = GLPIINVENTORY_11.networkports_id_1 OR GLPIINVENTORY_10.id = GLPIINVENTORY_11.networkports_id_2 ".
                     " LEFT JOIN glpi_networkports AS GLPIINVENTORY_12 ON GLPIINVENTORY_12.id = CASE WHEN GLPIINVENTORY_11.networkports_id_1 = GLPIINVENTORY_10.id THEN GLPIINVENTORY_11.networkports_id_2 ELSE GLPIINVENTORY_11.networkports_id_1 END
                     LEFT JOIN glpi_networkequipments ON GLPIINVENTORY_12.items_id=glpi_networkequipments.id ";
               }
               break;

            // ** switch port
            case "glpi_plugin_glpiinventory_networkports.plugin_glpiinventory_networkports_id" :
               $table_networking_ports = 0;
               $table_fusinvsnmp_networking = 0;
               foreach ($already_link_tables AS $num => $tmp_table) {
                  if ($tmp_table == "glpi_networkports.") {
                     $table_networking_ports = 1;
                  }
                  if ($tmp_table == "glpi_plugin_fusinvsnmp_networkequipments.id") {
                     $table_fusinvsnmp_networking = 1;
                  }
               }
               if ($table_fusinvsnmp_networking == "1") {
                  return " LEFT JOIN glpi_networkports AS GLPIINVENTORY_22 ON GLPIINVENTORY_22.id=GLPIINVENTORY_12.id ";
               } else if ($table_networking_ports == "1") {
                  return " LEFT JOIN glpi_networkports_networkports AS GLPIINVENTORY_21 ON glpi_networkports.id = GLPIINVENTORY_21.networkports_id_1 OR glpi_networkports.id = GLPIINVENTORY_21.networkports_id_2 ".
                     " LEFT JOIN glpi_networkports AS GLPIINVENTORY_22 ON GLPIINVENTORY_22.id = CASE WHEN GLPIINVENTORY_21.networkports_id_1 = glpi_networkports.id THEN GLPIINVENTORY_21.networkports_id_2 ELSE GLPIINVENTORY_21.networkports_id_1 END ";
               } else {
                  return " LEFT JOIN glpi_networkports AS GLPIINVENTORY_20 ON (GLPIINVENTORY_20.items_id = glpi_computers.id AND GLPIINVENTORY_20.itemtype='Computer') ".
                     " LEFT JOIN glpi_networkports_networkports AS GLPIINVENTORY_21 ON GLPIINVENTORY_20.id = GLPIINVENTORY_21.networkports_id_1 OR GLPIINVENTORY_20.id = GLPIINVENTORY_21.networkports_id_2 ".
                     " LEFT JOIN glpi_networkports AS GLPIINVENTORY_22 ON GLPIINVENTORY_22.id = CASE WHEN GLPIINVENTORY_21.networkports_id_1 = GLPIINVENTORY_20.id THEN GLPIINVENTORY_21.networkports_id_2 ELSE GLPIINVENTORY_21.networkports_id_1 END ";
               }
               break;

         }
         $a_agent_modules = PluginGlpiinventoryAgentmodule::getModules();
         foreach ($a_agent_modules as $module) {
            if ($new_table.".".$linkfield == 'glpi_plugin_glpiinventory_agentmodules.'.$module) {
               return " LEFT JOIN `glpi_plugin_glpiinventory_agentmodules` AS FUSION_".$module."
                             ON FUSION_".$module.".`modulename`='".$module."'
                          LEFT JOIN `glpi_plugin_glpiinventory_agents` as agent".strtolower($module)."
                             ON (`glpi_computers`.`id`=`agent".strtolower($module)."`.`computers_id`) ";
            }
         }
         break;

      case 'NetworkEquipment':
         $already_link_tables_tmp = $already_link_tables;
         array_pop($already_link_tables_tmp);

         $leftjoin_fusioninventory_networkequipments = 1;
         if ((in_array('glpi_plugin_glpiinventory_networkequipments', $already_link_tables_tmp))
            OR (in_array('glpi_plugin_glpiinventory_configsecurities',
                         $already_link_tables_tmp))) {

            $leftjoin_fusioninventory_networkequipments = 0;
         }
         switch ($new_table.".".$linkfield) {

            // ** last inventory
            case "glpi_plugin_glpiinventory_networkequipments." :
               if ($leftjoin_fusioninventory_networkequipments == "1") {
                  return " LEFT JOIN glpi_plugin_glpiinventory_networkequipments
                     ON (glpi_networkequipments.id = ".
                          "glpi_plugin_glpiinventory_networkequipments.networkequipments_id) ";
               }
               return " ";

            // ** cpu
            case "glpi_plugin_glpiinventory_networkequipments.".
                    "plugin_glpiinventory_networkequipments_id" :
               if ($leftjoin_fusioninventory_networkequipments == "1") {
                     return " LEFT JOIN glpi_plugin_glpiinventory_networkequipments
                        ON (glpi_networkequipments.id = ".
                             "glpi_plugin_glpiinventory_networkequipments.networkequipments_id) ";
               }
               return " ";

            // ** SNMP authentification
            case "glpi_plugin_glpiinventory_configsecurities.".
                    "plugin_glpiinventory_configsecurities_id":
               $return = "";
               if ($leftjoin_fusioninventory_networkequipments == "1") {
                  $return = " LEFT JOIN glpi_plugin_glpiinventory_networkequipments
                     ON glpi_networkequipments.id = ".
                          "glpi_plugin_glpiinventory_networkequipments.networkequipments_id ";
               }
               return $return." LEFT JOIN glpi_plugin_glpiinventory_configsecurities
                  ON glpi_plugin_glpiinventory_networkequipments.".
                       "plugin_glpiinventory_configsecurities_id = ".
                          "glpi_plugin_glpiinventory_configsecurities.id ";

            case "glpi_plugin_glpiinventory_networkequipments.sysdescr":
               $return = " ";
               if ($leftjoin_fusioninventory_networkequipments == "1") {
                  $return = " LEFT JOIN glpi_plugin_glpiinventory_networkequipments
                     ON glpi_networkequipments.id = ".
                          "glpi_plugin_glpiinventory_networkequipments.networkequipments_id ";
               }
               return $return;

         }
         break;

      case 'Printer':
         $already_link_tables_tmp = $already_link_tables;
         array_pop($already_link_tables_tmp);

         $leftjoin_fusioninventory_printers = 1;
         if ((in_array('glpi_plugin_glpiinventory_printers', $already_link_tables_tmp))
            OR (in_array('glpi_plugin_glpiinventory_configsecurities',
                         $already_link_tables_tmp))) {

            $leftjoin_fusioninventory_printers = 0;
         }
         switch ($new_table.".".$linkfield) {

            // ** last inventory
            case "glpi_plugin_glpiinventory_printers.plugin_glpiinventory_printers_id" :
               if ($leftjoin_fusioninventory_printers == 1) {
                  return " LEFT JOIN glpi_plugin_glpiinventory_printers
                     ON (glpi_printers.id = glpi_plugin_glpiinventory_printers.printers_id) ";
               }
               return " ";

            // ** SNMP authentification
            case "glpi_plugin_glpiinventory_configsecurities.".
                    "plugin_glpiinventory_configsecurities_id":
               $return = "";
               if ($leftjoin_fusioninventory_printers == "1") {
                  $return = " LEFT JOIN glpi_plugin_glpiinventory_printers
                     ON glpi_printers.id = glpi_plugin_glpiinventory_printers.printers_id ";
               }
               return $return." LEFT JOIN glpi_plugin_glpiinventory_configsecurities
                  ON glpi_plugin_glpiinventory_printers.plugin_glpiinventory_configsecurities_id
                        = glpi_plugin_glpiinventory_configsecurities.id ";

            // ** switch
            case "glpi_plugin_glpiinventory_networkequipments.plugin_glpiinventory_networkequipments_id" :
               $table_networking_ports = 0;
               foreach ($already_link_tables AS $num => $tmp_table) {
                  if ($tmp_table == "glpi_networkports.") {
                     $table_networking_ports = 1;
                  }
               }
               if ($table_networking_ports == "1") {
                  return " LEFT JOIN glpi_networkports_networkports AS GLPIINVENTORY_11 ON glpi_networkports.id = GLPIINVENTORY_11.networkports_id_1 OR glpi_networkports.id = GLPIINVENTORY_11.networkports_id_2 ".
                     " LEFT JOIN glpi_networkports AS GLPIINVENTORY_12 ON GLPIINVENTORY_12.id = CASE WHEN GLPIINVENTORY_11.networkports_id_1 = glpi_networkports.id THEN GLPIINVENTORY_11.networkports_id_2 ELSE GLPIINVENTORY_11.networkports_id_1 END
                     LEFT JOIN glpi_networkequipments ON GLPIINVENTORY_12.items_id=glpi_networkequipments.id ";

               } else {
                  return " LEFT JOIN glpi_networkports AS GLPIINVENTORY_10 ON (GLPIINVENTORY_10.items_id = glpi_printers.id AND GLPIINVENTORY_10.itemtype='Printer') ".
                     " LEFT JOIN glpi_networkports_networkports AS GLPIINVENTORY_11 ON GLPIINVENTORY_10.id = GLPIINVENTORY_11.networkports_id_1 OR GLPIINVENTORY_10.id = GLPIINVENTORY_11.networkports_id_2 ".
                     " LEFT JOIN glpi_networkports AS GLPIINVENTORY_12 ON GLPIINVENTORY_12.id = CASE WHEN GLPIINVENTORY_11.networkports_id_1 = GLPIINVENTORY_10.id THEN GLPIINVENTORY_11.networkports_id_2 ELSE GLPIINVENTORY_11.networkports_id_1 END
                     LEFT JOIN glpi_networkequipments ON GLPIINVENTORY_12.items_id=glpi_networkequipments.id ";
               }
               break;

            // ** switch port
            case "glpi_plugin_glpiinventory_networkports.plugin_glpiinventory_networkports_id" :
               $table_networking_ports = 0;
               $table_fusinvsnmp_networking = 0;
               foreach ($already_link_tables AS $num => $tmp_table) {
                  if ($tmp_table == "glpi_networkports.") {
                     $table_networking_ports = 1;
                  }
                  if ($tmp_table == "glpi_plugin_fusinvsnmp_networkequipments.id") {
                     $table_fusinvsnmp_networking = 1;
                  }
               }
               if ($table_fusinvsnmp_networking == "1") {
                  return " LEFT JOIN glpi_networkports AS GLPIINVENTORY_22 ON GLPIINVENTORY_22.id=GLPIINVENTORY_12.id ";
               } else if ($table_networking_ports == "1") {
                  return " LEFT JOIN glpi_networkports_networkports AS GLPIINVENTORY_21 ON glpi_networkports.id = GLPIINVENTORY_21.networkports_id_1 OR glpi_networkports.id = GLPIINVENTORY_21.networkports_id_2 ".
                     " LEFT JOIN glpi_networkports AS GLPIINVENTORY_22 ON GLPIINVENTORY_22.id = CASE WHEN GLPIINVENTORY_21.networkports_id_1 = glpi_networkports.id THEN GLPIINVENTORY_21.networkports_id_2 ELSE GLPIINVENTORY_21.networkports_id_1 END ";
               } else {
                  return " LEFT JOIN glpi_networkports AS GLPIINVENTORY_20 ON (GLPIINVENTORY_20.items_id = glpi_printers.id AND GLPIINVENTORY_20.itemtype='Printer') ".
                     " LEFT JOIN glpi_networkports_networkports AS GLPIINVENTORY_21 ON GLPIINVENTORY_20.id = GLPIINVENTORY_21.networkports_id_1 OR GLPIINVENTORY_20.id = GLPIINVENTORY_21.networkports_id_2 ".
                     " LEFT JOIN glpi_networkports AS GLPIINVENTORY_22 ON GLPIINVENTORY_22.id = CASE WHEN GLPIINVENTORY_21.networkports_id_1 = GLPIINVENTORY_20.id THEN GLPIINVENTORY_21.networkports_id_2 ELSE GLPIINVENTORY_21.networkports_id_1 END ";
               }
               break;

         }
         break;

      case 'PluginGlpiinventoryPrinterLogReport':
         switch ($new_table.".".$linkfield) {

            case 'glpi_locations.locations_id':
               return " LEFT JOIN `glpi_locations`
                  ON (`glpi_printers`.`locations_id` = `glpi_locations`.`id`) ";

            case 'glpi_printertypes.printertypes_id':
               return " LEFT JOIN `glpi_printertypes`
                  ON (`glpi_printers`.`printertypes_id` = `glpi_printertypes`.`id`) ";

            case 'glpi_states.states_id':
               return " LEFT JOIN `glpi_states`
                  ON (`glpi_printers`.`states_id` = `glpi_states`.`id`) ";

            case 'glpi_users.users_id':
               return " LEFT JOIN `glpi_users` AS glpi_users
                  ON (`glpi_printers`.`users_id` = `glpi_users`.`id`) ";

            case 'glpi_manufacturers.manufacturers_id':
               return " LEFT JOIN `glpi_manufacturers`
                  ON (`glpi_printers`.`manufacturers_id` = `glpi_manufacturers`.`id`) ";

            case 'glpi_networkports.printers_id':
               return " LEFT JOIN `glpi_networkports`
                  ON (`glpi_printers`.`id` = `glpi_networkports`.`items_id` AND `glpi_networkports`.`itemtype` = 'Printer') ";

            case 'glpi_plugin_glpiinventory_printerlogs.plugin_glpiinventory_printerlogs_id':
               return " LEFT JOIN `glpi_plugin_glpiinventory_printerlogs`
                  ON (`glpi_plugin_glpiinventory_printerlogs`.`printers_id` = `glpi_printers`.`id`) ";

            case 'glpi_networkports.networkports_id':
               return " LEFT JOIN `glpi_networkports` ON (`glpi_printers`.`id` = `glpi_networkports`.`items_id` AND `glpi_networkports`.`itemtype` = 'Printer' ) ";

            case 'glpi_printers.printers_id':
               return " LEFT JOIN `glpi_printers` ON (`glpi_plugin_glpiinventory_printers`.`printers_id` = `glpi_printers`.`id` AND `glpi_printers`.`id` IS NOT NULL ) ";

         }
         break;

   }
   return "";
}


/**
 * Manage order in search query
 *
 * @param string $type
 * @param integer $id
 * @param string $order
 * @param integer $key
 * @return string
 */
function plugin_glpiinventory_addOrderBy($type, $id, $order, $key = 0) {
   return "";
}


/**
 * Add where in search query
 *
 * @param string $type
 * @return string
 */
function plugin_glpiinventory_addDefaultWhere($type) {
   if ($type == 'PluginGlpiinventoryTaskjob' && !isAPI()) {
      return " ( select count(*) FROM `glpi_plugin_glpiinventory_taskjobstates`
         WHERE plugin_glpiinventory_taskjobs_id= `glpi_plugin_glpiinventory_taskjobs`.`id`
         AND `state`!='3' )";
   }
}


/**
 * Manage where in search query
 *
 * @param string $link
 * @param string $nott
 * @param string $type
 * @param integer $id
 * @param string $val
 * @return string
 */
function plugin_glpiinventory_addWhere($link, $nott, $type, $id, $val) {

   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$id]["table"];
   $field = $searchopt[$id]["field"];

   switch ($type) {

      case 'PluginGlpiinventoryTaskjob' :
         /*
          * WARNING: The following is some minor hack in order to select a range of ids.
          *
          * More precisely, when using the ID filter, you can now put IDs separated by commas.
          * This is used by the DeployPackage class when it comes to check running tasks on some
          * packages.
          */
         if ($table == 'glpi_plugin_glpiinventory_tasks') {
            if ($field == 'id') {
               //check if this range is numeric
               $ids = explode(',', $val);
               foreach ($ids as $k => $i) {
                  if (!is_numeric($i)) {
                     unset($ids[$k]);
                  }
               }

               if (count($ids) >= 1) {
                  return $link." `$table`.`id` IN (".implode(',', $ids).")";
               } else {
                  return "";
               }
            } else if ($field == 'name') {
               $val = stripslashes($val);
               //decode a json query to match task names in taskjobs list
               $names = json_decode($val);
               if ($names !== null && is_array($names)) {
                  $names = array_map(
                     function ($a) {
                        return "\"".$a."\"";
                     },
                     $names
                  );
                  return $link." `$table`.`name` IN (".implode(',', $names) . ")";
               } else {
                  return "";
               }
            }
         }
         break;

      case 'PluginGlpiinventoryAgent':
         $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
         $a_modules = $pfAgentmodule->find();
         foreach ($a_modules as $data) {
            if ($table.".".$field == "glpi_plugin_glpiinventory_agentmodules.".
                    $data['modulename']) {
               if (($data['exceptions'] != "[]") AND ($data['exceptions'] != "")) {
                  $a_exceptions = importArrayFromDB($data['exceptions']);
                  $current_id = current($a_exceptions);
                  $in = "(";
                  foreach ($a_exceptions as $agent_id) {
                     $in .= $agent_id.", ";
                  }
                  $in .= ")";
                  $in = str_replace(", )", ")", $in);

                  if ($val != $data['is_active']) {
                     return $link." (FUSION_".$data['modulename'].".`exceptions` LIKE '%\"".
                             $current_id."\"%' ) AND `glpi_plugin_glpiinventory_agents`.`id` IN ".
                             $in." ";
                  } else {
                     return $link." `glpi_plugin_glpiinventory_agents`.`id` NOT IN ".$in." ";
                  }
               } else {
                  if ($val != $data['is_active']) {
                     return $link." (FUSION_".$data['modulename'].".`is_active`!='".
                              $data['is_active']."') ";
                  } else {
                     return $link." (FUSION_".$data['modulename'].".`is_active`='".
                              $data['is_active']."') ";
                  }
               }
            }
         }
         break;

      case 'PluginGlpiinventoryTaskjoblog':
         if ($field == 'uniqid') {
            return $link." (`".$table."`.`uniqid`='".$val."') ";
         }
         break;

      // * Computer List (front/computer.php)
      case 'Computer':
         switch ($table.".".$field) {

            // ** switch
            case "glpi_plugin_glpiinventory_networkequipments.name" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR glpi_networkequipments.id IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR glpi_networkequipments.id IS NOT NULL";
               }
               return $link." (glpi_networkequipments.id  LIKE '%".$val."%' $ADD ) ";

            // ** switch port
            case "glpi_plugin_glpiinventory_networkports.id" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR GLPIINVENTORY_22.name IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR GLPIINVENTORY_22.name IS NOT NULL";
               }
               return $link." (GLPIINVENTORY_22.name  LIKE '%".$val."%' $ADD ) ";
               break;

         }

         $a_agent_modules = PluginGlpiinventoryAgentmodule::getModules();
         foreach ($a_agent_modules as $module) {
            if ($table.".".$field == 'glpi_plugin_glpiinventory_agentmodules.'.$module) {
               $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
               $a_modules = $pfAgentmodule->find(['modulename' => $module]);
               $data = current($a_modules);
               if (($data['exceptions'] != "[]") AND ($data['exceptions'] != "")) {
                  $a_exceptions = importArrayFromDB($data['exceptions']);
                  $current_id = current($a_exceptions);
                  $in = "(";
                  foreach ($a_exceptions as $agent_id) {
                     $in .= $agent_id.", ";
                  }
                  $in .= ")";
                  $in = str_replace(", )", ")", $in);

                  if ($val != $data['is_active']) {
                     return $link." (FUSION_".$module.".`exceptions` LIKE '%\"".
                             $current_id."\"%' ) AND `agent".strtolower($module)."`.`id` IN ".
                             $in." ";
                  } else {
                     return $link." `agent".strtolower($module)."`.`id` NOT IN ".$in." ";
                  }
               } else {
                  if ($val != $data['is_active']) {
                     return $link." (FUSION_".$module.".`is_active`!='".
                              $data['is_active']."') ";
                  } else {
                     return $link." (FUSION_".$module.".`is_active`='".
                              $data['is_active']."') ";
                  }
               }
            }
         }
         break;

      // * Networking List (front/networking.php)
      case 'NetworkEquipment':
         switch ($table.".".$field) {

            // ** last inventory
            case "glpi_plugin_glpiinventory_networkequipments.networkequipments_id" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR $table.last_fusinvsnmp_update IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR $table.last_fusinvsnmp_update IS NOT NULL";
               }
               return $link." ($table.last_fusinvsnmp_update  LIKE '%".$val."%' $ADD ) ";

            // ** SNMP authentification
            case "glpi_plugin_glpiinventory_networkequipments.plugin_fusinvsnmp_snmpauths_id" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR glpi_plugin_glpiinventory_configsecurities.name IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR glpi_plugin_glpiinventory_configsecurities.name IS NOT NULL";
               }
               return $link." (glpi_plugin_glpiinventory_configsecurities.name  LIKE '%".$val.
                       "%' $ADD ) ";

            // ** CPU
            case "glpi_plugin_glpiinventory_networkequipments.cpu":
               break;

         }
         break;

      // * Printer List (front/printer.php)
      case 'Printer':
         switch ($table.".".$field) {

            // ** last inventory
            case "glpi_plugin_glpiinventory_printers.printers_id" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR $table.last_fusinvsnmp_update IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR $table.last_fusinvsnmp_update IS NOT NULL";
               }
               return $link." ($table.last_fusinvsnmp_update  LIKE '%".$val."%' $ADD ) ";

            // ** SNMP authentification
            case "glpi_plugin_glpiinventory_networkequipments.plugin_fusinvsnmp_snmpauths_id" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR $table.name IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR $table.name IS NOT NULL";
               }
               return $link." ($table.name  LIKE '%".$val."%' $ADD ) ";

            // ** switch
            case "glpi_plugin_glpiinventory_networkequipments.name" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR glpi_networkequipments.id IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR glpi_networkequipments.id IS NOT NULL";
               }
               return $link." (glpi_networkequipments.id  LIKE '%".$val."%' $ADD ) ";

            // ** switch port
            case "glpi_plugin_glpiinventory_networkports.id" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR GLPIINVENTORY_22.name IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR GLPIINVENTORY_22.name IS NOT NULL";
               }
               return $link." (GLPIINVENTORY_22.name  LIKE '%".$val."%' $ADD ) ";

         }
         break;

      // * Unknown mac addresses connectd on switch - report
      // (plugins/fusinvsnmp/report/unknown_mac.php)
      case 'PluginGlpiinventoryUnmanaged' :
         switch ($table.".".$field) {

            // ** switch
            case "glpi_plugin_glpiinventory_networkequipments.id" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR GLPIINVENTORY_12.items_id IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR GLPIINVENTORY_12.items_id IS NOT NULL";
               }
               return $link." (GLPIINVENTORY_13.name  LIKE '%".$val."%' $ADD ) ";

            // ** switch port
            case "glpi_plugin_glpiinventory_networkports.id" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR GLPIINVENTORY_22.name IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR GLPIINVENTORY_22.name IS NOT NULL";
               }
               return $link." (GLPIINVENTORY_22.name  LIKE '%".$val."%' $ADD ) ";

         }
         break;

      // * Ports date connection - report (plugins/fusinvsnmp/report/ports_date_connections.php)
      case 'PluginGlpiinventoryNetworkPort' :
         switch ($table.".".$field) {

            // ** Name and link of networking device (switch)
            case "glpi_plugin_glpiinventory_networkports.id" :
               break;

            // ** Name and link of port of networking device (port of switch)
            case "glpi_plugin_glpiinventory_networkports.networkports_id" :
               break;

            // ** Location of switch
            case "glpi_locations.id" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR glpi_networkequipments.location IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR glpi_networkequipments.location IS NOT NULL";
               }
               if ($val == "0") {
                  return $link." (glpi_networkequipments.location >= -1 ) ";
               }
               return $link." (glpi_networkequipments.location = '".$val."' $ADD ) ";

            case "glpi_plugin_glpiinventory_networkports.lastup" :
               $ADD = "";
               //$val = str_replace("&lt;", ">", $val);
               //$val = str_replace("\\", "", $val);
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR $table.$field IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR $table.$field IS NOT NULL";
               }
               return $link." ($table.$field $val $ADD ) ";

         }
         break;

      // * range IP list (plugins/fusinvsnmp/front/iprange.php)
      case 'PluginGlpiinventoryIPRange' :
         switch ($table.".".$field) {

            // ** Name of range IP and link to form
            case "glpi_plugin_glpiinventory_ipranges.name" :
               break;

            // ** Agent name associed to IP range and link to agent form
            case "glpi_plugin_fusinvsnmp_agents.id" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR $table.name IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR $table.name IS NOT NULL";
               }
               return $link." ($table.name  LIKE '%".$val."%' $ADD ) ";

         }
         switch ($table.".".$field) {

            case "glpi_plugin_fusinvsnmp_agents.plugin_fusinvsnmp_agents_id_query" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR $table.name IS NULL";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR $table.name IS NOT NULL";
               }
               return $link." (gpta.name  LIKE '%".$val."%' $ADD ) ";

         }
         break;

      case 'PluginGlpiinventoryNetworkPortLog' :
         switch ($table.".".$field) {

            // ** Display switch and Port
            case "glpi_networkports.id" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR $table.id IS NULL ";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR $table.id IS NOT NULL ";
               }
               return $link." ($table.id = '".$val."' $ADD ) ";

            // ** Display GLPI field of device
            case "glpi_plugin_fusinvsnmp_networkportlogs.field" :
               $ADD = "";
               if ($nott == "0" && $val == "NULL") {
                  $ADD = " OR $table.$field IS NULL ";
               } else if ($nott == "1" && $val == "NULL") {
                  $ADD = " OR $table.$field IS NOT NULL ";
               }
               if (!empty($val)) {
                  $map = new PluginGlpiinventoryMapping;
                  $mapfields = $map->get('NetworkEquipment', $val);
                  if ($mapfields != false) {
                     $val = $mapfields['tablefields'];
                  } else {
                     $val = '';
                  }
               }
               return $link." ($table.$field = '".addslashes($val)."' $ADD ) ";

         }
         break;

   }
   return "";
}


/**
 * Manage pre-item update an item
 *
 * @param object $parm
 */
function plugin_pre_item_update_glpiinventory($parm) {
   if ($parm->fields['directory'] == 'glpiinventory') {
      $plugin = new Plugin();

      $a_plugins = PluginGlpiinventoryModule::getAll();
      foreach ($a_plugins as $datas) {
         $plugin->unactivate($datas['id']);
      }
   }
}


/**
 * Manage pre-item purge an item
 *
 * @param object $parm
 * @return object
 */
function plugin_pre_item_purge_glpiinventory($parm) {
   $itemtype = get_class($parm);
   $items_id = $parm->getID();

   switch ($itemtype) {
      case 'Computer':
         $pfAgent        = new PluginGlpiinventoryAgent;
         $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate;
         if ($agent_id = $pfAgent->getAgentWithComputerid($items_id)) {
            // count associated tasks to the agent
            $states = $pfTaskjobstate->find(['plugin_glpiinventory_agents_id' => $agent_id], [], 1);
            if (count($states) > 0) {
               // Delete link between computer and agent fusion
               $pfAgent->update([
                  'id'           => $agent_id,
                  'computers_id' => 0],
               true);
            } else {
               // no task associated, purge also agent
               $pfAgent->delete(['id' => $agent_id], true);
            }
         }

         $clean = [
            'PluginGlpiinventoryInventoryComputerComputer',
            'PluginGlpiinventoryComputerLicenseInfo',
            'PluginGlpiinventoryCollect_File_Content',
            'PluginGlpiinventoryCollect_Registry_Content',
            'PluginGlpiinventoryCollect_Wmi_Content'
         ];
         foreach ($clean as $obj) {
            $obj::cleanComputer($items_id);
         }
         break;

      case 'NetworkPort_NetworkPort':
         $networkPort = new NetworkPort();
         if ($networkPort->getFromDB($parm->fields['networkports_id_1'])) {
            if (($networkPort->fields['itemtype']) == 'NetworkEquipment') {
               PluginGlpiinventoryNetworkPortLog::addLogConnection("remove",
                                                                $parm->fields['networkports_id_1']);
            } else {
               $networkPort->getFromDB($parm->fields['networkports_id_2']);
               if (($networkPort->fields['itemtype']) == 'NetworkEquipment') {
                  PluginGlpiinventoryNetworkPortLog::addLogConnection("remove",
                                                                $parm->fields['networkports_id_2']);
               }
            }
         }
      break;

   }

   $rule = new PluginGlpiinventoryRulematchedlog();
   $rule->deleteByCriteria(['itemtype' => $itemtype, 'items_id' => $items_id]);

   PluginGlpiinventoryLock::cleanForAsset($itemtype, $items_id);
   return $parm;
}


/**
 * Manage pre-item delete an item
 *
 * @global object $DB
 * @param object $parm
 * @return object
 */
function plugin_pre_item_delete_glpiinventory($parm) {
   global $DB;

   if (isset($parm["_item_type_"])) {
      switch ($parm["_item_type_"]) {

         case 'NetworkPort':
            $DB->delete(
               'glpi_plugin_glpiinventory_networkports', [
                  'networkports_id' => $param->getField('id')
               ]
            );
            break;

      }
   }
   return $parm;
}


/**
 * Manage when update an item
 *
 * @param object $parm
 */
function plugin_item_update_glpiinventory($parm) {
   if (isset($_SESSION["glpiID"])
           && $_SESSION["glpiID"] != ''
           && !isset($_SESSION['glpi_glpiinventory_nolock'])) { // manual task
      $plugin = new Plugin;
      if ($plugin->isActivated('glpiinventory')) {
         // lock fields which have been updated
         $type = get_class($parm);
         $id = $parm->getField('id');
         $fieldsToLock = $parm->updates;
         PluginGlpiinventoryLock::addLocks($type, $id, $fieldsToLock);
      }
   }
}


/**
 * Manage when add an item
 *
 * @param object $parm
 * @return object
 */
function plugin_item_add_glpiinventory($parm) {

   switch (get_class($parm)) {

      case 'NetworkPort_NetworkPort':
         $networkPort = new NetworkPort();
         $networkPort->getFromDB($parm->fields['networkports_id_1']);
         if ($networkPort->fields['itemtype'] == 'NetworkEquipment') {
            PluginGlpiinventoryNetworkPortLog::addLogConnection("make",
                                                                  $parm->fields['networkports_id_1']);
         } else {
            $networkPort->getFromDB($parm->fields['networkports_id_2']);
            if ($networkPort->fields['itemtype'] == 'NetworkEquipment') {
               PluginGlpiinventoryNetworkPortLog::addLogConnection("make",
                                                                  $parm->fields['networkports_id_2']);
            }
         }
         break;

      case 'NetworkPort':
         if ($parm->fields['itemtype'] == 'NetworkEquipment') {
            $pfNetworkPort = new PluginGlpiinventoryNetworkPort();
            $pfNetworkPort->add(['networkports_id' => $parm->fields['id']]);
         }
         break;

   }
   return $parm;
}


/**
 * Manage when purge an item
 *
 * @param object $parm
 * @return object
 */
function plugin_item_purge_glpiinventory($parm) {
   global $DB;

   switch (get_class($parm)) {

      case 'NetworkPort_NetworkPort':
         // If remove connection of a hub port (unknown device), we must delete this port too
         $NetworkPort = new NetworkPort();
         $NetworkPort_Vlan = new NetworkPort_Vlan();
         $pfUnmanaged = new PluginGlpiinventoryUnmanaged();
         $networkPort_NetworkPort = new NetworkPort_NetworkPort();

         $a_hubs = [];

         $port_id = $NetworkPort->getContact($parm->getField('networkports_id_1'));
         $NetworkPort->getFromDB($parm->getField('networkports_id_1'));
         if ($NetworkPort->fields['itemtype'] == 'PluginGlpiinventoryUnmanaged') {
            $pfUnmanaged->getFromDB($NetworkPort->fields['items_id']);
            if ($pfUnmanaged->fields['hub'] == '1') {
               $a_hubs[$NetworkPort->fields['items_id']] = 1;
               $NetworkPort->delete($NetworkPort->fields);
            }
         }
         $NetworkPort->getFromDB($port_id);
         if ($port_id) {
            if ($NetworkPort->fields['itemtype'] == 'PluginGlpiinventoryUnmanaged') {
               $pfUnmanaged->getFromDB($NetworkPort->fields['items_id']);
               if ($pfUnmanaged->fields['hub'] == '1') {
                  $a_hubs[$NetworkPort->fields['items_id']] = 1;
               }
            }
         }
         $port_id = $NetworkPort->getContact($parm->getField('networkports_id_2'));
         $NetworkPort->getFromDB($parm->getField('networkports_id_2'));
         if ($NetworkPort->fields['itemtype'] == 'PluginGlpiinventoryUnmanaged') {
            if ($pfUnmanaged->getFromDB($NetworkPort->fields['items_id'])) {
               if ($pfUnmanaged->fields['hub'] == '1') {
                  $a_vlans = $NetworkPort_Vlan->getVlansForNetworkPort($NetworkPort->fields['id']);
                  foreach ($a_vlans as $vlan_id) {
                     $NetworkPort_Vlan->unassignVlan($NetworkPort->fields['id'], $vlan_id);
                  }
                  $a_hubs[$NetworkPort->fields['items_id']] = 1;
                  $NetworkPort->delete($NetworkPort->fields);
               }
            }
         }
         if ($port_id) {
            $NetworkPort->getFromDB($port_id);
            if ($NetworkPort->fields['itemtype'] == 'PluginGlpiinventoryUnmanaged') {
               $pfUnmanaged->getFromDB($NetworkPort->fields['items_id']);
               if ($pfUnmanaged->fields['hub'] == '1') {
                  $a_hubs[$NetworkPort->fields['items_id']] = 1;
               }
            }
         }

         // If hub have no port, delete it
         foreach (array_keys($a_hubs) as $unknowndevice_id) {
            $a_networkports = $NetworkPort->find(
                  ['itemtype' => 'PluginGlpiinventoryUnmanaged',
                   'items_id' => $unknowndevice_id]);
            if (count($a_networkports) < 2) {
               $pfUnmanaged->delete(['id' => $unknowndevice_id], 1);
            } else if (count($a_networkports) == '2') {
               $switchPorts_id = 0;
               $otherPorts_id  = 0;
               foreach ($a_networkports as $data) {
                  if ($data['name'] == 'Link') {
                     $switchPorts_id = $NetworkPort->getContact($data['id']);
                  } else if ($otherPorts_id == '0') {
                     $otherPorts_id = $NetworkPort->getContact($data['id']);
                  } else {
                     $switchPorts_id = $NetworkPort->getContact($data['id']);
                  }
               }

               $pfUnmanaged->disconnectDB($switchPorts_id); // disconnect this port
               $pfUnmanaged->disconnectDB($otherPorts_id);     // disconnect destination port

               $networkPort_NetworkPort->add(['networkports_id_1' => $switchPorts_id,
                                                   'networkports_id_2' => $otherPorts_id]);
            }
         }
         break;

      case 'NetworkEquipment':
         // Delete all ports
         $DB->delete(
            'glpi_plugin_glpiinventory_networkequipments', [
               'networkequipments_id' => $parm->fields['id']
            ]
         );

         $iterator = $DB->request([
            'SELECT'    => [
               'glpi_plugin_glpiinventory_networkports.id',
               'glpi_networkports.id AS nid'
            ],
            'FROM'      => 'glpi_plugin_glpiinventory_networkports',
            'LEFT JOIN' => [
               'glpi_networkports' => [
                  'FKEY' => [
                     'glpi_networkports'                          => 'id',
                     'glpi_plugin_glpiinventory_networkports'   => 'networkports_id'
                  ]
               ]
            ],
            'WHERE'     => [
               'items_id'  => $parm->fields['id'],
               'itemtype'  => 'NetworkEquipment'
            ]
         ]);
         foreach ($iterator as $data) {
            $DB->delete(
               'glpi_plugin_glpiinventory_networkports', [
                  'id' => $data['id']
               ]
            );
            $DB->delete(
               'glpi_plugin_fusinvsnmp_networkportlogs', [
                  'networkports_id' => $data['nid']
               ]
            );
         }
         break;

      case "Printer":
         $DB->delete(
            'glpi_plugin_glpiinventory_printers', [
               'printers_id' => $parm->fields['id']
            ]
         );
         $DB->delete(
            'glpi_plugin_glpiinventory_printercartridges', [
               'printers_id' => $parm->fields['id']
            ]
         );
         $DB->delete(
            'glpi_plugin_glpiinventory_printerlogs', [
               'printers_id' => $parm->fields['id']
            ]
         );
         break;

      case 'PluginGlpiinventoryTimeslot';
         $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
         $dbentries = getAllDataFromTable(
            'glpi_plugin_glpiinventory_timeslotentries', [
               'WHERE'  => [
                  'plugin_glpiinventory_timeslots_id' => $parm->fields['id']
               ]
            ]
         );
         foreach ($dbentries as $data) {
            $pfTimeslotEntry->delete(['id' => $data['id']], true);
         }
         break;

      case 'Entity':
         $DB->delete(
            'glpi_plugin_glpiinventory_entities', [
               'entities_id' => $parm->fields['id']
            ]
         );
         break;

      case 'PluginGlpiinventoryDeployPackage':
         // Delete all linked items
         $DB->delete(
            'glpi_plugin_glpiinventory_deploypackages_entities', [
               'plugin_glpiinventory_deploypackages_id' => $parm->fields['id']
            ]
         );
         $DB->delete(
            'glpi_plugin_glpiinventory_deploypackages_groups', [
               'plugin_glpiinventory_deploypackages_id' => $parm->fields['id']
            ]
         );
         $DB->delete(
            'glpi_plugin_glpiinventory_deploypackages_profiles', [
               'plugin_glpiinventory_deploypackages_id' => $parm->fields['id']
            ]
         );
         $DB->delete(
            'glpi_plugin_glpiinventory_deploypackages_users', [
               'plugin_glpiinventory_deploypackages_id' => $parm->fields['id']
            ]
         );
         break;

   }
   return $parm;
}


/**
 * Manage when transfer an item
 *
 * @param object $parm
 * @return boolean
 */
function plugin_item_transfer_glpiinventory($parm) {

   switch ($parm['type']) {

      case 'Computer':
         $pfAgent = new PluginGlpiinventoryAgent();
         $agent_id = $pfAgent->getAgentWithComputerid($parm['id']);
         if ($agent_id) {
            $input = [];
            $input['id'] = $agent_id;
            $computer = new Computer();
            $computer->getFromDB($parm['newID']);
            $input['entities_id'] = $computer->fields['entities_id'];
            $pfAgent->update($input);
         }

         break;
   }
   return false;
}


/**
 * Register the webservices methods
 *
 * @global array $WEBSERVICES_METHOD
 */
function plugin_glpiinventory_registerMethods() {
   global $WEBSERVICES_METHOD;

   $WEBSERVICES_METHOD['fusioninventory.test'] = [
                     'PluginGlpiinventoryInventoryComputerWebservice',
                     'methodTest'];
   $WEBSERVICES_METHOD['fusioninventory.computerextendedinfo'] = [
       'PluginGlpiinventoryInventoryComputerWebservice',
         'methodExtendedInfo'];
}


/**
 * Define dropdown relations
 *
 * @return array
 */
function plugin_glpiinventory_getDatabaseRelations() {

   $plugin = new Plugin();

   if ($plugin->isActivated("glpiinventory")) {
      return ["glpi_locations"
                        => ['glpi_plugin_glpiinventory_deploymirrors' => 'locations_id'],
                   "glpi_entities"
                        => ["glpi_plugin_glpiinventory_agents"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_collects"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_credentialips"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_credentials"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_deployfiles"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_deploymirrors"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_deploypackages"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_ignoredimportdevices"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_ipranges"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_tasks"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_timeslotentries"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_timeslots"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_deployuserinteractiontemplates"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_unmanageds"
                                    => "entities_id"]];
   }
   return [];
}


/**
 * Display a form if no automatic inventory has yet been
 * performed
 * @since 9.2
 *
 * @param array $params Hook parameters
 *
 * @return void
 */
function plugin_glpiinventory_postItemForm($params) {
   if (isset($params['item']) && $params['item'] instanceof CommonDBTM) {
      switch (get_class($params['item'])) {
         case 'Computer':
            PluginGlpiinventoryInventoryComputerComputer::showFormForAgentWithNoInventory($params['item']);
            break;
         case 'Item_OperatingSystem':
            if ($params['item']->fields['itemtype'] == 'Computer') {
               PluginGlpiinventoryInventoryComputerComputer::showFormOS($params['item']);
            }
      }
   }
}


/**
 * FusinInventory infos after showing a tab
 * @since 9.2
 *
 * @param array $params Hook parameters
 *
 * @return void
 */
function plugin_glpiinventory_postshowtab($params) {
   if (isset($params['item']) && is_object($params['item'])) {
      $item = $params['item'];

      switch ($item->getType()) {
         case 'Computer':
            switch (Session::getActiveTab('Computer')) {
               case 'Lock$1':
                  PluginGlpiinventoryLock::showLocksForAnItem($item);
                  break;
            }

            break;

         case 'NetworkEquipment':
            switch (Session::getActiveTab('NetworkEquipment')) {
               case 'Lock$1':
                  PluginGlpiinventoryLock::showLocksForAnItem($item);
                  break;
            }
            break;

         case 'Printer':
            switch (Session::getActiveTab('Printer')) {
               case 'Lock$1':
                  PluginGlpiinventoryLock::showLocksForAnItem($item);
                  break;
            }

            break;
      }
   }
}


 /**
  * FusinInventory infos after before a tab
  * @since 9.2
  *
  * @param array $params Hook parameters
  *
  * @return void
  */
function plugin_glpiinventory_preshowtab($params) {
   global $CFG_GLPI;

   if (isset($params['item']) && is_object($params['item'])) {
      $item = $params['item'];
      if (in_array($item->getType(), $CFG_GLPI['software_types'])) {
         switch (Session::getActiveTab($item->getType())) {
            case 'Item_SoftwareVersion$1':
               $license = new PluginGlpiinventoryComputerLicenseInfo();
               $license->showForm($item->getID());
               break;
         }
      }
   }
}

/**
 * Code for export dynamic groups in CSV / PDF with all columns defined in view (global / personal)
 *
 * @param array $params
 * @return boolean
 */
function plugin_glpiinventory_dynamicReport($params) {
   switch ($params['item_type']) {
      case "PluginGlpiinventoryComputer";
         if ($url = parse_url($_SERVER['HTTP_REFERER'])) {
            $params = Search::manageParams($_GET["item_type"], $_GET);

            $data = Search::prepareDatasForSearch($params["item_type"], $params);
            $data['itemtype'] = 'Computer';
            Search::constructSQL($data);
            Search::constructData($data);
            Search::displayData($data);

            return true;
         }
         break;
   }
   return false;
}
