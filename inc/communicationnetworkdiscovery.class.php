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
 * Manage the communication of network discovery feature with the agents.
 */
class PluginGlpiinventoryCommunicationNetworkDiscovery {


   /**
    * Import data, so get data from agent to put in GLPI
    *
    * @param string $p_DEVICEID device_id of agent
    * @param array $a_CONTENT
    * @param array $arrayinventory
    * @return string errors or empty string
    */
   function import($p_DEVICEID, $a_CONTENT, $arrayinventory) {
      $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
      $pfAgent = new PluginGlpiinventoryAgent();

      PluginGlpiinventoryCommunication::addLog(
              'Function PluginGlpiinventoryCommunicationNetworkDiscovery->import().');

      $errors = '';
      $a_agent = $pfAgent->infoByKey($p_DEVICEID);
      if (isset($a_CONTENT['PROCESSNUMBER'])) {
         $_SESSION['glpi_plugin_glpiinventory_processnumber'] = $a_CONTENT['PROCESSNUMBER'];
         if ($pfTaskjobstate->getFromDB($a_CONTENT['PROCESSNUMBER'])) {
            if ($pfTaskjobstate->fields['state'] != PluginGlpiinventoryTaskjobstate::FINISHED) {
               $pfTaskjobstate->changeStatus($a_CONTENT['PROCESSNUMBER'], 2);
               if ((!isset($a_CONTENT['AGENT']['START']))
                       AND (!isset($a_CONTENT['AGENT']['END']))) {
                  $nb_devices = 0;
                  if (isset($a_CONTENT['DEVICE'])) {
                     if (is_int(key($a_CONTENT['DEVICE']))) {
                        $nb_devices = count($a_CONTENT['DEVICE']);
                     } else {
                        $nb_devices = 1;
                     }
                  }
                  $_SESSION['plugin_glpiinventory_taskjoblog']['taskjobs_id'] =
                                 $a_CONTENT['PROCESSNUMBER'];
                  $_SESSION['plugin_glpiinventory_taskjoblog']['items_id'] = $a_agent['id'];
                  $_SESSION['plugin_glpiinventory_taskjoblog']['itemtype'] =
                                 'PluginGlpiinventoryAgent';
                  $_SESSION['plugin_glpiinventory_taskjoblog']['state'] = PluginGlpiinventoryTaskjoblog::TASK_RUNNING;
                  $_SESSION['plugin_glpiinventory_taskjoblog']['comment'] =
                                 $nb_devices.' ==devicesfound==';
                  $this->addtaskjoblog();
               }
            }
         }
      }

      if ($pfTaskjobstate->getFromDB($a_CONTENT['PROCESSNUMBER'])) {
         if ($pfTaskjobstate->fields['state'] != PluginGlpiinventoryTaskjobstate::FINISHED) {
            $pfImportExport = new PluginGlpiinventorySnmpmodelImportExport();
            $errors .= $pfImportExport->import_netdiscovery($a_CONTENT, $p_DEVICEID);
            if (isset($a_CONTENT['AGENT']['END'])) {
               $messages = [
                   'Total Found' => 0,
                   'Created'     => 0,
                   'Updated'     => 0
               ];
               $messages['Updated'] = countElementsInTable('glpi_plugin_glpiinventory_taskjoblogs',
                  [
                     'plugin_glpiinventory_taskjobstates_id' => $a_CONTENT['PROCESSNUMBER'],
                     'comment'                                 => ['LIKE', '%==updatetheitem==%'],
                  ]);
               $messages['Created'] = countElementsInTable('glpi_plugin_glpiinventory_taskjoblogs',
                  [
                     'plugin_glpiinventory_taskjobstates_id' => $a_CONTENT['PROCESSNUMBER'],
                     'comment'                                 => ['LIKE', '%==addtheitem==%'],
                  ]);
               $messages['Total Found'] = $messages['Updated'] + $messages['Created'];

               $message = __('Processed:', 'glpiinventory').$messages['Total Found'].' ';
               $message.= __('Created:', 'glpiinventory').$messages['Created'].' ';
               $message.= __(' Updated:', 'glpiinventory').$messages['Updated'];
               $pfTaskjobstate->changeStatusFinish($a_CONTENT['PROCESSNUMBER'],
                                                   $a_agent['id'],
                                                   'PluginGlpiinventoryAgent',
                                                   '0',
                                                   $message);

            }
         }
      }
      return $errors;
   }


   /**
    * Prepare data and send them to rule engine
    *
    * @param array $arrayinventory inventory array
    */
   function sendCriteria($arrayinventory) {

      PluginGlpiinventoryCommunication::addLog(
              'Function PluginGlpiinventoryCommunicationNetworkDiscovery->sendCriteria().');

      if ((isset($arrayinventory['MAC']))
              && ($arrayinventory['MAC'] == "00:00:00:00:00:00")) {
         unset($arrayinventory['MAC']);
      }

      $_SESSION['SOURCE_XMLDEVICE'] = $arrayinventory;

      $input = [];

      // Global criterias

      if ((isset($arrayinventory['SERIAL']))
              && (!empty($arrayinventory['SERIAL']))) {
         $input['serial'] = $arrayinventory['SERIAL'];
      }
      if ((isset($arrayinventory['MAC']))
              && (!empty($arrayinventory['MAC']))) {
         $input['mac'][] = $arrayinventory['MAC'];
      }
      if ((isset($arrayinventory['IP']))
              && (!empty($arrayinventory['IP']))) {
         $input['ip'][] = $arrayinventory['IP'];
      }
      if ((isset($arrayinventory['MODELSNMP']))
              && (!empty($arrayinventory['MODELSNMP']))) {
         $input['model'] = $arrayinventory['MODELSNMP'];
      }
      if ((isset($arrayinventory['SNMPHOSTNAME']))
              && (!empty($arrayinventory['SNMPHOSTNAME']))) {
         $input['name'] = $arrayinventory['SNMPHOSTNAME'];
      } else if ((isset($arrayinventory['NETBIOSNAME']))
              && (!empty($arrayinventory['NETBIOSNAME']))) {
         $input['name'] = $arrayinventory['NETBIOSNAME'];
      } else if ((isset($arrayinventory['DNSHOSTNAME']))
              && (!empty($arrayinventory['DNSHOSTNAME']))) {
         if (strpos($arrayinventory['DNSHOSTNAME'], '.') !== false) {
            $splitname = explode('.', $arrayinventory['DNSHOSTNAME']);
            $input['name'] = $splitname[0];
            if (!isset($arrayinventory['WORKGROUP'])) {
               unset($splitname[0]);
               $arrayinventory['WORKGROUP'] = implode('.', $splitname);
               $_SESSION['SOURCE_XMLDEVICE'] = $arrayinventory;
            }
         } else {
            $input['name'] = $arrayinventory['DNSHOSTNAME'];
         }
      }

      if (!isset($arrayinventory['ENTITY'])) {
         $arrayinventory['ENTITY'] = 0;
      }
      $input['entities_id'] = $arrayinventory['ENTITY'];
      if (isset($arrayinventory['TYPE'])) {
         switch ($arrayinventory['TYPE']) {

            case '1':
            case 'COMPUTER':
               $input['itemtype'] = "Computer";
               // Computer

                break;

            case '2':
            case 'NETWORKING':
            case 'STORAGE':
               $input['itemtype'] = "NetworkEquipment";
                break;

            case '3':
            case 'PRINTER':
               $input['itemtype'] = "Printer";
                break;

         }
      }

      $_SESSION['plugin_glpiinventory_datacriteria'] = serialize($input);
      $_SESSION['plugin_glpiinventory_classrulepassed'] =
                     "PluginGlpiinventoryCommunicationNetworkDiscovery";
      $rule = new PluginGlpiinventoryInventoryRuleImportCollection();
      $data = $rule->processAllRules($input, []);
      PluginGlpiinventoryConfig::logIfExtradebug("pluginGlpiinventory-rules",
                                                   $data);

      if (isset($data['action'])
             && ($data['action'] == PluginGlpiinventoryInventoryRuleImport::LINK_RESULT_DENIED)) {

         $a_text = [];
         foreach ($input as $key=>$data) {
            if (is_array($data)) {
               $a_text[] = "[".$key."]:".implode(", ", $data);
            } else {
               $a_text[] = "[".$key."]:".$data;
            }
         }
         $_SESSION['plugin_glpiinventory_taskjoblog']['comment'] = '==importdenied== '.
                                                                  implode(", ", $a_text);
         $this->addtaskjoblog();

         $pfIgnoredimport = new PluginGlpiinventoryIgnoredimportdevice();
         $inputdb = [];
         if (isset($input['name'])) {
            $inputdb['name'] = $input['name'];
         }
         $inputdb['date'] = date("Y-m-d H:i:s");
         if (isset($input['itemtype'])) {
            $inputdb['itemtype'] = $input['itemtype'];
         }
         if (isset($input['serial'])) {
            $inputdb['serial'] = $input['serial'];
         }
         if (isset($input['ip'])) {
            $inputdb['ip'] = exportArrayToDB($input['ip']);
         }
         if (isset($input['mac'])) {
            $inputdb['mac'] = exportArrayToDB($input['mac']);
         }
         if (isset($input['uuid'])) {
            $inputdb['uuid'] = $input['uuid'];
         }
         $inputdb['rules_id'] = $_SESSION['plugin_glpiinventory_rules_id'];
         $inputdb['method'] = 'networkdiscovery';
         $pfIgnoredimport->add($inputdb);
         unset($_SESSION['plugin_glpiinventory_rules_id']);
      }
      if (isset($data['_no_rule_matches']) AND ($data['_no_rule_matches'] == '1')) {
         if (!isset($_SESSION['glpiactiveentities_string'])) {
            $_SESSION['glpiactiveentities_string'] = "'".$input['entities_id']."'";
         }
         if (isset($input['itemtype'])
             && isset($data['action'])
             && ($data['action'] == PluginGlpiinventoryInventoryRuleImport::LINK_RESULT_CREATE)) {

            $this->rulepassed(0, $input['itemtype'], 0, $input['entities_id']);
         } else if (isset($input['itemtype'])
                AND !isset($data['action'])) {
            $this->rulepassed(0, $input['itemtype'], 0, $input['entities_id']);
         } else {
            $this->rulepassed(0, "PluginGlpiinventoryUnmanaged", 0, $input['entities_id']);
         }
      }
   }


   /**
    * After rule engine passed, update task (log) and create item if required
    *
    * @param integer $items_id id of the item (0 = not exist in database)
    * @param string $itemtype
    * @param integer $entities_id
    */
   function rulepassed($items_id, $itemtype, $ports_id = 0, $entities_id = 0) {

      PluginGlpiinventoryLogger::logIfExtradebug(
         "pluginGlpiinventory-rules",
         "Rule passed : ".$items_id.", ".$itemtype."\n"
      );
      PluginGlpiinventoryLogger::logIfExtradebugAndDebugMode(
         'glpiinventorycommunication',
         'Function PluginFusinvsnmpCommunicationNetDiscovery->rulepassed().'
      );

      if (!isset($_SESSION['glpiactiveentities_string'])) {
         $_SESSION['glpiactiveentities_string'] = "'".$entities_id."'";
      }

      $_SESSION['glpiactive_entity'] = $entities_id;

      $item = new $itemtype();
      if ($items_id == "0") {
         $input = [];
         $input['date_mod'] = date("Y-m-d H:i:s");
         $input['entities_id'] = $entities_id;

         $items_id = $item->add($input);
         if (isset($_SESSION['plugin_glpiinventory_rules_id'])) {
            $pfRulematchedlog = new PluginGlpiinventoryRulematchedlog();
            $inputrulelog = [];
            $inputrulelog['date'] = date('Y-m-d H:i:s');
            $inputrulelog['rules_id'] = $_SESSION['plugin_glpiinventory_rules_id'];
            if (isset($_SESSION['plugin_glpiinventory_agents_id'])) {
               $inputrulelog['plugin_glpiinventory_agents_id'] =
                              $_SESSION['plugin_glpiinventory_agents_id'];
            }
            $inputrulelog['items_id'] = $items_id;
            $inputrulelog['itemtype'] = $itemtype;
            $inputrulelog['method'] = 'networkdiscovery';
            $pfRulematchedlog->add($inputrulelog);
            $pfRulematchedlog->cleanOlddata($items_id, $itemtype);
            unset($_SESSION['plugin_glpiinventory_rules_id']);
         }
         if (!isset($_SESSION['glpiactiveentities_string'])) {
            $_SESSION['glpiactiveentities_string'] = "'".$entities_id."'";
         }
         $_SESSION['plugin_glpiinventory_taskjoblog']['comment'] =
               '[==detail==] ==addtheitem== '.$item->getTypeName().
               ' [['.$itemtype.'::'.$items_id.']]';
         $this->addtaskjoblog();
      } else {

         $_SESSION['plugin_glpiinventory_taskjoblog']['comment'] =
               '[==detail==] ==updatetheitem== '.$item->getTypeName().
               ' [['.$itemtype.'::'.$items_id.']]';
         $this->addtaskjoblog();
      }
      $item->getFromDB($items_id);
      $this->importDevice($item);
   }


   /**
    * Import discovered device (add / update data in GLPI DB)
    *
    * @param object $item
    */
   function importDevice($item) {

      PluginGlpiinventoryLogger::logIfExtradebugAndDebugMode(
         'glpiinventorycommunication',
         'Function ' . __METHOD__
      );

      $arrayinventory = $_SESSION['SOURCE_XMLDEVICE'];
      $input = [];
      $input['id'] = $item->getID();

      $a_lockable = PluginGlpiinventoryLock::getLockFields(getTableForItemType($item->getType()),
                                                             $item->getID());

      if (!in_array('name', $a_lockable)) {
         if (isset($arrayinventory['SNMPHOSTNAME'])
                 && !empty($arrayinventory['SNMPHOSTNAME'])) {
            $input['name'] = $arrayinventory['SNMPHOSTNAME'];
         } else if (isset($arrayinventory['NETBIOSNAME'])
                 && !empty($arrayinventory['NETBIOSNAME'])) {
            $input['name'] = $arrayinventory['NETBIOSNAME'];
         } else if (isset($arrayinventory['DNSHOSTNAME'])
                 &&!empty($arrayinventory['DNSHOSTNAME'])) {
            $input['name'] = $arrayinventory['DNSHOSTNAME'];
         }
      }
      if (!in_array('serial', $a_lockable)) {
         if (isset($arrayinventory['SERIAL'])) {
            if (trim($arrayinventory['SERIAL']) != '') {
               $input['serial'] = trim($arrayinventory['SERIAL']);
            }
         }
      }
      if (isset($input['name'])
              && $input['name'] == '') {
         unset($input['name']);
      }
      if (isset($input['serial'])
              && $input['serial'] == '') {
         unset($input['serial']);
      }

      if (isset($arrayinventory['ENTITY']) AND !empty($arrayinventory['ENTITY'])) {
         $input['entities_id'] = $arrayinventory['ENTITY'];
         if (!isset($_SESSION['glpiactiveentities_string'])) {
            $_SESSION['glpiactiveentities_string'] = "'".$arrayinventory['ENTITY']."'";
         }
      }
      if (!isset($_SESSION['glpiactiveentities_string'])) {
         $_SESSION['glpiactiveentities_string'] = "'".$item->fields['entities_id']."'";
      }

      switch ($item->getType()) {

         case 'Computer':
            // don't update this computer, if it is already handled by
            // its own agent
            $autoupdatesystems_name = Dropdown::getDropdownName(
               "glpi_autoupdatesystems",
               $item->fields['autoupdatesystems_id']
            );
            if ($autoupdatesystems_name == 'FusionInventory' || $autoupdatesystems_name == 'GlpiInventory') {
               return;
            }

            if (isset($arrayinventory['WORKGROUP'])) {
               $ditem = new Domain_Item();
               if (!in_array('domains_id', $a_lockable)) {
                  $domain = new Domain();
                  $domains_id = $domain->import([
                     'name' => $arrayinventory['WORKGROUP'],
                     'entities_id' => $item->fields['entities_id']
                  ]);
                  $dinput = [
                     'itemtype'     => 'Computer',
                     'items_id'     => $item->fields['id'],
                     'domains_id'   => $domains_id
                  ];
                  if (!$ditem->getFromDBByCrit($dinput)) {
                     $ditem->add($dinput);
                  }
               }
            }
            $item->update($input);

            $this->updateNetworkInfo(
               $arrayinventory,
               'Computer',
               $item->getID(),
               'NetworkPortEthernet',
               1
            );
            break;

         case 'PluginGlpiinventoryUnmanaged':
            // Write XML file
            if (isset($_SESSION['SOURCE_XMLDEVICE'])) {
               PluginGlpiinventoryToolbox::writeXML(
                  $input['id'],
                  serialize($_SESSION['SOURCE_XMLDEVICE']),
                  'PluginGlpiinventoryUnmanaged'
               );
            }

            if (!in_array('contact', $a_lockable)
                    && isset($arrayinventory['USERSESSION'])) {
               $input['contact'] = $arrayinventory['USERSESSION'];
            }
            if (!in_array('domain', $a_lockable)) {
               if (isset($arrayinventory['WORKGROUP'])
                       && !empty($arrayinventory['WORKGROUP'])) {
                  $input['domain'] = Dropdown::importExternal("Domain",
                                       $arrayinventory['WORKGROUP'], $arrayinventory['ENTITY']);
               }
            }
            if (!empty($arrayinventory['TYPE'])) {
               switch ($arrayinventory['TYPE']) {

                  case '1':
                  case 'COMPUTER':
                     $input['item_type'] = 'Computer';
                     break;

                  case '2':
                  case 'NETWORKING':
                  case 'STORAGE':
                     $input['item_type'] = 'NetworkEquipment';
                     break;

                  case '3':
                  case 'PRINTER':
                     $input['item_type'] = 'Printer';
                     break;

               }
            }
            $input['plugin_glpiinventory_agents_id'] =
                           $_SESSION['glpi_plugin_glpiinventory_agentid'];

            $this->updateSNMPInfo($arrayinventory, $input, $item);

            $this->updateNetworkInfo(
               $arrayinventory,
               'PluginGlpiinventoryUnmanaged',
               $item->getID(),
               'NetworkPortEthernet',
               1
            );

            break;

         case 'NetworkEquipment':
            // Write XML file
            if (isset($_SESSION['SOURCE_XMLDEVICE'])) {
               PluginGlpiinventoryToolbox::writeXML(
                  $input['id'],
                  serialize($_SESSION['SOURCE_XMLDEVICE']),
                  'NetworkEquipment'
               );
            }

            $item->update($input);

            $this->updateNetworkInfo(
               $arrayinventory,
               'NetworkEquipment',
               $item->getID(),
               'NetworkPortAggregate',
               0
            );

            $pfNetworkEquipment = new PluginGlpiinventoryNetworkEquipment();
            $input = $this->initSpecificInfo(
               'networkequipments_id',
               $item->getID(),
               $pfNetworkEquipment
            );
            $this->updateSNMPInfo($arrayinventory, $input, $pfNetworkEquipment);

            break;

         case 'Printer':
            // Write XML file
            if (isset($_SESSION['SOURCE_XMLDEVICE'])) {
               PluginGlpiinventoryToolbox::writeXML(
                  $input['id'],
                  serialize($_SESSION['SOURCE_XMLDEVICE']),
                  'Printer'
               );
            }

            $input['have_ethernet'] = '1';
            $item->update($input);

            $this->updateNetworkInfo(
               $arrayinventory,
               'Printer',
               $item->getID(),
               'NetworkPortEthernet',
               1
            );

            $pfPrinter = new PluginGlpiinventoryPrinter();
            $input = $this->initSpecificInfo(
               'printers_id',
               $item->getID(),
               $pfPrinter
            );
            $this->updateSNMPInfo($arrayinventory, $input, $pfPrinter);

            break;

      }
   }


   /**
    * Update networkport information
    *
    * @param array $arrayinventory
    * @param string $itemtype
    * @param integer $items_id
    * @param string $instanciation_type type of port (ethernet, wifi...)
    * @param boolean $check_addresses
    */
   function updateNetworkInfo($arrayinventory, $itemtype, $items_id, $instanciation_type, $check_addresses) {
      $NetworkPort = new NetworkPort();
      $port = current($NetworkPort->find(
           ['itemtype'           => $itemtype,
            'items_id'           => $items_id,
            'instantiation_type' => $instanciation_type],
           [], 1));
      $port_id = 0;
      if (isset($port['id'])) {
         if (isset($arrayinventory['MAC']) AND !empty($arrayinventory['MAC'])) {
            $input = [];
            $input['id']  = $port['id'];
            $input['mac'] = $arrayinventory['MAC'];
            $NetworkPort->update($input);
         }
         $port_id = $port['id'];
      } else {
         $item = new $itemtype;
         $item->getFromDB($items_id);
         $input = [];
         $input['itemtype']           = $itemtype;
         $input['items_id']           = $items_id;
         $input['instantiation_type'] = $instanciation_type;
         $input['name']               = "management";
         $input['entities_id']        = $item->fields['entities_id'];
         if (isset($arrayinventory['MAC'])
                 && !empty($arrayinventory['MAC'])) {
            $input['mac'] = $arrayinventory['MAC'];
         }
         $port_id = $NetworkPort->add($input);
      }

      $NetworkName = new NetworkName();
      $name = current($NetworkName->find(
        ['itemtype' => 'NetworkPort', 'items_id' => $port_id], [], 1));
      $name_id = 0;

      if (isset($name['id'])) {
         $name_id = $name['id'];
      } else {
         $input = [];
         $input['itemtype'] = 'NetworkPort';
         $input['items_id'] = $port_id;
         $name_id = $NetworkName->add($input);
      }

      if (isset($arrayinventory['IP'])) {
         $IPAddress = new IPAddress();

         if ($check_addresses) {
            $addresses = $IPAddress->find(
                  ['itemtype' => 'NetworkName',
                   'items_id' => $name_id],
                  [], 1);
         } else {
            // Case of NetworkEquipment
            $a_ips = $IPAddress->find(
                  ['itemtype' => 'NetworkName',
                   'items_id' => $name_id,
                   'name'     => $arrayinventory['IP']],
                  [], 1);
            if (count($a_ips) > 0) {
               $addresses = $a_ips;
            } else {
               $addresses = [];
            }
         }

         if (count($addresses) == 0) {
            $input = [];
            $input['itemtype'] = 'NetworkName';
            $input['items_id'] = $name_id;
            $input['name']     = $arrayinventory['IP'];
            $IPAddress->add($input);
         } else {
            $address = current($addresses);
            if ($address['name'] != $arrayinventory['IP']) {
               $input = [];
               $input['id']   = $address['id'];
               $input['name'] = $arrayinventory['IP'];
               $IPAddress->update($input);
            }
         }
      }
   }


   /**
    * Get info from database
    *
    * @param string $key_field
    * @param integer $id
    * @param object $item
    * @return array
    */
   function initSpecificInfo($key_field, $id, $item) {
      $instances = $item->find([$key_field => $id]);
      $input = [];
      if (count($instances) > 0) {
         $input = Toolbox::addslashes_deep(current($instances));
      } else {
         $input[$key_field] = $id;
         $id = $item->add($input);
         $item->getFromDB($id);
         $input = $item->fields;
      }
      return $input;
   }


   /**
    * Update SNMP information of a device (sysdescr, SNMP credentials...)
    *
    * @param array $arrayinventory
    * @param array $input
    * @param object $item
    */
   function updateSNMPInfo($arrayinventory, $input, $item) {
      if (isset($arrayinventory['DESCRIPTION'])
              && !empty($arrayinventory['DESCRIPTION'])) {
         $input['sysdescr']  = $arrayinventory['DESCRIPTION'];
      }
      if (isset($arrayinventory['AUTHSNMP'])
              && !empty($arrayinventory['AUTHSNMP'])) {
         $input['plugin_glpiinventory_configsecurities_id'] = $arrayinventory['AUTHSNMP'];
      }
      $item->update($input);
   }


   /**
    * Used to add log in the taskjob
    */
   function addtaskjoblog() {

      $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
      $pfTaskjoblog->addTaskjoblog(
                     $_SESSION['plugin_glpiinventory_taskjoblog']['taskjobs_id'],
                     $_SESSION['plugin_glpiinventory_taskjoblog']['items_id'],
                     $_SESSION['plugin_glpiinventory_taskjoblog']['itemtype'],
                     $_SESSION['plugin_glpiinventory_taskjoblog']['state'],
                     $_SESSION['plugin_glpiinventory_taskjoblog']['comment']);
   }


   /**
    * Get method name linked to this class
    *
    * @return string
    */
   static function getMethod() {
      return 'networkdiscovery';
   }


}
