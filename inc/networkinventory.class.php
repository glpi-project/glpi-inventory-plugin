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
 * Manage network inventory task jobs.
 */
class PluginGlpiinventoryNetworkinventory extends PluginGlpiinventoryCommunication
{


   /**
    * Get all devices and put in taskjobstate each task for each device for
    * each agent
    *
    * @global object $DB
    * @param integer $taskjobs_id
    * @return string
    */
    public function prepareRun($taskjobs_id)
    {
        global $DB;

        $pfTask = new PluginGlpiinventoryTask();
        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $pfIPRange = new PluginGlpiinventoryIPRange();
        $agent = new Agent();
        $a_specificity = [];
        $a_specificity['DEVICE'] = [];

        $uniqid = uniqid();

        $pfTaskjob->getFromDB($taskjobs_id);
        $pfTask->getFromDB($pfTaskjob->fields['plugin_glpiinventory_tasks_id']);

        $NetworkEquipment = new NetworkEquipment();
        $NetworkPort = new NetworkPort();

       /*
        * * Different possibilities  :
        * IP RANGE
        * NetworkEquipment
        * Printer
        *
        * We will count total number of devices to query
        */
       // get all snmpauth
        $a_snmpauth = getAllDataFromTable(SNMPCredential::getTable());

       // get items_id by type
        $a_iprange = [];
        $a_NetworkEquipment = [];
        $a_Printer = [];
        $communication = $pfTask->fields['communication'];
        $a_definition = importArrayFromDB($pfTaskjob->fields['definition']);
        foreach ($a_definition as $datas) {
            $itemtype = key($datas);
            $items_id = current($datas);

            switch ($itemtype) {
                case 'PluginGlpiinventoryIPRange':
                    $a_iprange[] = $items_id;
                    break;

                case 'NetworkEquipment':
                    $query = "SELECT `glpi_networkequipments`.`id` AS `gID`,
                         `glpi_ipaddresses`.`name` AS `gnifaddr`,
                         `snmpcredentials_id`,
                  FROM `glpi_networkequipments`
                  LEFT JOIN `glpi_networkports`
                       ON `glpi_networkports`.`items_id`=`glpi_networkequipments`.`id`
                          AND `glpi_networkports`.`itemtype`='NetworkEquipment'
                  LEFT JOIN `glpi_networknames`
                       ON `glpi_networknames`.`items_id`=`glpi_networkports`.`id`
                          AND `glpi_networknames`.`itemtype`='NetworkPort'
                  LEFT JOIN `glpi_ipaddresses`
                       ON `glpi_ipaddresses`.`items_id`=`glpi_networknames`.`id`
                          AND `glpi_ipaddresses`.`itemtype`='NetworkName'
                  WHERE `glpi_networkequipments`.`is_deleted`='0'
                       AND `snmpcredentials_id`!='0'
                       AND `glpi_networkequipments`.`id` = '" . $items_id . "'
                       AND `glpi_ipaddresses`.`name`!=''
                  LIMIT 1";
                    $result = $DB->query($query);
                    while ($data = $DB->fetchArray($result)) {
                        if (isset($a_snmpauth[$data['snmpcredentials_id']])) {
                              $input = [];
                              $input['TYPE'] = 'NETWORKING';
                              $input['ID'] = $data['gID'];
                              $input['IP'] = $data['gnifaddr'];
                              $input['AUTHSNMP_ID'] = $data['snmpcredentials_id'];
                              $a_specificity['DEVICE']['NetworkEquipment' . $data['gID']] = $input;
                              $a_NetworkEquipment[] = $items_id;
                        }
                    }
                    break;

                case 'Printer':
                    $query = "SELECT `glpi_printers`.`id` AS `gID`,
                         `glpi_ipaddresses`.`name` AS `gnifaddr`,
                         `snmpcredentials_id`,
                  FROM `glpi_printers`
                  LEFT JOIN `glpi_networkports`
                       ON `glpi_networkports`.`items_id`=`glpi_printers`.`id`
                          AND `glpi_networkports`.`itemtype`='Printer'
                  LEFT JOIN `glpi_networknames`
                       ON `glpi_networknames`.`items_id`=`glpi_networkports`.`id`
                          AND `glpi_networknames`.`itemtype`='NetworkPort'
                  LEFT JOIN `glpi_ipaddresses`
                       ON `glpi_ipaddresses`.`items_id`=`glpi_networknames`.`id`
                          AND `glpi_ipaddresses`.`itemtype`='NetworkName'
                  WHERE `glpi_printers`.`is_deleted`=0
                        AND `snmpcredentials_id`!='0'
                        AND `glpi_printers`.`id` = '" . $items_id . "'
                        AND `glpi_ipaddresses`.`name`!=''
                  LIMIT 1";
                    $result = $DB->query($query);
                    while ($data = $DB->fetchArray($result)) {
                        if (isset($a_snmpauth[$data['snmpcredentials_id']])) {
                              $input = [];
                              $input['TYPE'] = 'PRINTER';
                              $input['ID'] = $data['gID'];
                              $input['IP'] = $data['gnifaddr'];
                              $input['AUTHSNMP_ID'] = $data['snmpcredentials_id'];
                              $a_specificity['DEVICE']['Printer' . $data['gID']] = $input;
                              $a_Printer[] = $items_id;
                        }
                    }
                    break;
            }
        }

       // Get all devices on each iprange
        foreach ($a_iprange as $items_id) {
            $pfIPRange->getFromDB($items_id);
           // Search NetworkEquipment
            $query = "SELECT `glpi_networkequipments`.`id` AS `gID`,
                            `glpi_ipaddresses`.`name` AS `gnifaddr`,
                            `snmpcredentials_id`,
                     FROM `glpi_networkequipments`
                     LEFT JOIN `glpi_networkports`
                          ON `glpi_networkports`.`items_id`=`glpi_networkequipments`.`id`
                             AND `glpi_networkports`.`itemtype`='NetworkEquipment'
                     LEFT JOIN `glpi_networknames`
                          ON `glpi_networknames`.`items_id`=`glpi_networkports`.`id`
                             AND `glpi_networknames`.`itemtype`='NetworkPort'
                     LEFT JOIN `glpi_ipaddresses`
                          ON `glpi_ipaddresses`.`items_id`=`glpi_networknames`.`id`
                             AND `glpi_ipaddresses`.`itemtype`='NetworkName'
                     WHERE `glpi_networkequipments`.`is_deleted`='0'
                          AND `snmpcredentials_id`!='0'";
            if ($pfIPRange->fields['entities_id'] != '-1') {
                $entities = "(" . $this->fields['entities_id'];
                foreach (getAncestorsOf("glpi_entities", $pfIPRange->fields['entities_id']) as $parent) {
                    $entities .= ",$parent";
                }
                $entities .= ")";
                $query .= " AND `glpi_networkequipments`.`entities_id` IN " .
                        $entities . " ";
            }
            $query .= " AND inet_aton(`glpi_ipaddresses`.`name`)
                         BETWEEN inet_aton('" . $pfIPRange->fields['ip_start'] . "')
                         AND inet_aton('" . $pfIPRange->fields['ip_end'] . "') ";
            $query .= " GROUP BY `glpi_networkequipments`.`id`";
            $result = $DB->query($query);
            while ($data = $DB->fetchArray($result)) {
                if (isset($a_snmpauth[$data['snmpcredentials_id']])) {
                    $input = [];
                    $input['TYPE'] = 'NETWORKING';
                    $input['ID'] = $data['gID'];
                    $input['IP'] = $data['gnifaddr'];
                    $input['AUTHSNMP_ID'] = $data['snmpcredentials_id'];
                    $a_specificity['DEVICE']['NetworkEquipment' . $data['gID']] = $input;
                    $a_NetworkEquipment[] = $data['gID'];
                }
            }
           // Search Printer
            $query = "SELECT `glpi_printers`.`id` AS `gID`,
                         `glpi_ipaddresses`.`name` AS `gnifaddr`,
                         `snmpcredentials_id`,
                  FROM `glpi_printers`
                  LEFT JOIN `glpi_networkports`
                       ON `glpi_networkports`.`items_id`=`glpi_printers`.`id`
                          AND `glpi_networkports`.`itemtype`='Printer'
                  LEFT JOIN `glpi_networknames`
                       ON `glpi_networknames`.`items_id`=`glpi_networkports`.`id`
                          AND `glpi_networknames`.`itemtype`='NetworkPort'
                  LEFT JOIN `glpi_ipaddresses`
                       ON `glpi_ipaddresses`.`items_id`=`glpi_networknames`.`id`
                          AND `glpi_ipaddresses`.`itemtype`='NetworkName'
                  WHERE `glpi_printers`.`is_deleted`=0
                        AND `snmpcredentials_id`!='0'";
            if ($pfIPRange->fields['entities_id'] != '-1') {
                $entities = "(" . $this->fields['entities_id'];
                foreach (getAncestorsOf("glpi_entities", $pfIPRange->fields['entities_id']) as $parent) {
                    $entities .= ",$parent";
                }
                $entities .= ")";
                $query .= "AND `glpi_printers`.`entities_id` IN " . $entities . " ";
            }
            $query .= " AND inet_aton(`glpi_ipaddresses`.`name`)
                      BETWEEN inet_aton('" . $pfIPRange->fields['ip_start'] . "')
                      AND inet_aton('" . $pfIPRange->fields['ip_end'] . "') ";
            $query .= " GROUP BY `glpi_printers`.`id`";
            $result = $DB->query($query);
            while ($data = $DB->fetchArray($result)) {
                if (isset($a_snmpauth[$data['snmpcredentials_id']])) {
                    $input = [];
                    $input['TYPE'] = 'PRINTER';
                    $input['ID'] = $data['gID'];
                    $input['IP'] = $data['gnifaddr'];
                    $input['AUTHSNMP_ID'] = $data['snmpcredentials_id'];
                    $a_specificity['DEVICE']['Printer' . $data['gID']] = $input;
                    $a_Printer[] = $data['gID'];
                }
            }
        }
        $count_device = count($a_NetworkEquipment) + count($a_Printer);

        $a_actions = importArrayFromDB($pfTaskjob->fields['action']);

       // *** For dynamic agent same subnet, it's an another management ***
        if (strstr($pfTaskjob->fields['action'], '".2"')) {
            $a_subnet = [];
            $a_agentList = [];
            $a_devicesubnet = [];
            foreach ($a_NetworkEquipment as $items_id) {
                $NetworkEquipment->getFromDB($items_id);
                $a_ip = explode(".", $NetworkEquipment->fields['ip']);
                $ip_subnet = $a_ip[0] . "." . $a_ip[1] . "." . $a_ip[2] . ".";
                if (!isset($a_subnet[$ip_subnet])) {
                    $a_subnet[$ip_subnet] = 0;
                }
                $a_subnet[$ip_subnet]++;
                $a_devicesubnet[$ip_subnet]['NetworkEquipment'][$items_id] = 1;
            }
            foreach ($a_Printer as $items_id) {
                $a_ports = $NetworkPort->find(
                    ['itemtype' => 'Printer',
                    'items_id' => $items_id,
                    ['ip']     => ['!=',
                    '127.0.0.1']]
                );
                foreach ($a_ports as $a_port) {
                     $a_ip = explode(".", $a_port['ip']);
                     $ip_subnet = $a_ip[0] . "." . $a_ip[1] . "." . $a_ip[2] . ".";
                    if (!isset($a_subnet[$ip_subnet])) {
                        $a_subnet[$ip_subnet] = 0;
                    }
                     $a_subnet[$ip_subnet]++;
                     $a_devicesubnet[$ip_subnet]['Printer'][$items_id] = 1;
                }
            }
            $a_agentsubnet = [];
            foreach ($a_subnet as $subnet => $num) {
                $a_agentList = $this->getAgentsSubnet($num, $communication, $subnet);
                if (!isset($a_agentList)) {
                    $a_agentsubnet[$subnet] = '';
                } else {
                    $a_agentsubnet[$subnet] = $a_agentList;
                }
            }
            $a_input = [];
            $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
            $a_input['state'] = 1;
            $a_input['agents_id'] = 0;
            $a_input['itemtype'] = '';
            $a_input['items_id'] = 0;
            $a_input['uniqid'] = $uniqid;
            $a_input['execution_id'] = $pfTask->fields['execution_id'];

            $taskvalid = 0;
            foreach ($a_agentsubnet as $subnet => $a_agentList) {
                if (
                    !isset($a_agentList)
                    or (isset($a_agentList)
                       && is_array($a_agentList)
                       && count($a_agentList) == '0')
                    or (isset($a_agentList)
                       && !is_array($a_agentList)
                       && $a_agentList == '')
                ) {
                   // No agent available for this subnet
                    for ($i = 0; $i < 2; $i++) {
                        $itemtype = 'Printer';
                        if ($i == '0') {
                             $itemtype = 'NetworkEquipment';
                        }
                        if (isset($a_devicesubnet[$subnet][$itemtype])) {
                            foreach ($a_devicesubnet[$subnet][$itemtype] as $items_id => $num) {
                                $a_input['itemtype'] = $itemtype;
                                $a_input['items_id'] = $items_id;
                                $a_input['specificity'] = exportArrayToDB(
                                    $a_specificity['DEVICE'][$itemtype . $items_id]
                                );
                                 $Taskjobstates_id = $pfTaskjobstate->add($a_input);
                                 //Add log of taskjob
                                 $a_input['plugin_glpiinventory_taskjobstates_id'] = $Taskjobstates_id;
                                 $a_input['state'] = 7;
                                 $a_input['date'] = date("Y-m-d H:i:s");
                                 $pfTaskjoblog->add($a_input);
                                 $pfTaskjobstate->changeStatusFinish(
                                     $Taskjobstates_id,
                                     0,
                                     '',
                                     1,
                                     "Unable to find agent to inventory " .
                                     "this " . $itemtype
                                 );
                                 $a_input['state'] = 1;
                            }
                        }
                    }
                } else {
                   // add taskjobstate
                    $count_device_subnet = 0;
                    if (isset($a_devicesubnet[$subnet]['NetworkEquipment'])) {
                        $count_device_subnet += count($a_devicesubnet[$subnet]['NetworkEquipment']);
                    }
                    if (isset($a_devicesubnet[$subnet]['Printer'])) {
                        $count_device_subnet += count($a_devicesubnet[$subnet]['Printer']);
                    }
                    $nb_devicebyagent = ceil($count_device_subnet / count($a_agentList));
                    $nbagent = 0;
                    $agent_id = array_pop($a_agentList);
                    $a_input['state'] = 0;

                    for ($i = 0; $i < 2; $i++) {
                        $itemtype = 'Printer';
                        if ($i == '0') {
                            $itemtype = 'NetworkEquipment';
                        }
                        if (isset($a_devicesubnet[$subnet][$itemtype])) {
                            foreach ($a_devicesubnet[$subnet][$itemtype] as $items_id => $num) {
                                $a_input['itemtype'] = $itemtype;
                                $a_input['items_id'] = $items_id;
                                $a_input['specificity'] = exportArrayToDB(
                                    $a_specificity['DEVICE'][$itemtype . $items_id]
                                );
                                if ($nbagent == $nb_devicebyagent) {
                                       $agent_id = array_pop($a_agentList);
                                       $nbagent = 0;
                                }
                                $a_input['agents_id'] = $agent_id;
                                $nbagent++;
                                $taskvalid++;
                                $Taskjobstates_id = $pfTaskjobstate->add($a_input);
                               //Add log of taskjob
                                $a_input['plugin_glpiinventory_taskjobstates_id'] = $Taskjobstates_id;
                                $a_input['state'] = 7;
                                $a_input['date'] = date("Y-m-d H:i:s");
                                $pfTaskjoblog->add($a_input);
                                unset($a_input['state']);
                                $a_input['agents_id'] = 0;
                                $a_input['state'] = 0;
                                if ($communication == "push") {
                                     $_SESSION['glpi_plugin_glpiinventory']['agents'][$agent_id] = 1;
                                }
                            }
                        }
                    }
                }
            }
            if ($taskvalid == "0") {
                $pfTaskjob->reinitializeTaskjobs($pfTaskjob->fields['plugin_glpiinventory_tasks_id']);
            }
        } else {
            $a_agentList = [];
           // *** Only agents not dynamic ***
            if (
                (!strstr($pfTaskjob->fields['action'], '".1"'))
                and (!strstr($pfTaskjob->fields['action'], '".2"'))
            ) {
                $agent_require_model = 0;
                foreach ($a_actions as $a_action) {
                    if (
                        (!in_array('.1', $a_action))
                        and (!in_array('.2', $a_action))
                    ) {
                        $agent_id = current($a_action);
                        if ($agent->getFromDB($agent_id)) {
                            $a_version = importArrayFromDB($this->fields['version']);
                            $agent_version = '0';
                            if (isset($a_version['INVENTORY'])) {
                                $agent_version = str_replace('v', '', $a_version['INVENTORY']);
                            }

                            if (strnatcmp($agent_version, '2.3.4') < 0) {
                                $agent_require_model = 1;
                            }
                            if ($communication == 'pull') {
                                $a_agentList[] = $agent_id;
                            } else {
                                if ($pfTaskjob->isAgentAlive('1', $agent_id)) {
                                    $a_agentList[] = $agent_id;
                                }
                            }
                        }
                    }
                }
            } elseif (strstr($pfTaskjob->fields['action'], '".1"')) {
               /*
                * Case : dynamic agent
                */
                $a_agentList = $this->getAgentsSubnet($count_device, $communication);
            }
           /*
           * Manage agents
           */
            if (count($a_agentList) == 0) {
                $a_input = [];
                $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
                $a_input['state'] = 1;
                $a_input['agents_id'] = 0;
                $a_input['itemtype'] = '';
                $a_input['items_id'] = 0;
                $a_input['uniqid'] = $uniqid;
                $a_input['execution_id'] = $pfTask->fields['execution_id'];

                $Taskjobstates_id = $pfTaskjobstate->add($a_input);
                //Add log of taskjob
                $a_input['plugin_glpiinventory_taskjobstates_id'] = $Taskjobstates_id;
                $a_input['state'] = 7;
                $a_input['date'] = date("Y-m-d H:i:s");
                $pfTaskjoblog->add($a_input);
                $pfTaskjobstate->changeStatusFinish(
                    $Taskjobstates_id,
                    0,
                    '',
                    1,
                    "Unable to find agent to run this job"
                );
                $input_taskjob = [];
                $input_taskjob['id'] = $pfTaskjob->fields['id'];
               //$input_taskjob['status'] = 0;
                $pfTaskjob->update($input_taskjob);
            } elseif ($count_device == 0) {
                $a_input = [];
                $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
                $a_input['state'] = 1;
                $a_input['agents_id'] = 0;
                $a_input['itemtype'] = '';
                $a_input['items_id'] = 0;
                $a_input['uniqid'] = $uniqid;
                $Taskjobstates_id = $pfTaskjobstate->add($a_input);
                //Add log of taskjob
                $a_input['plugin_glpiinventory_taskjobstates_id'] = $Taskjobstates_id;
                $a_input['state'] = 7;
                $a_input['date'] = date("Y-m-d H:i:s");
                $pfTaskjoblog->add($a_input);
                $pfTaskjobstate->changeStatusFinish(
                    $Taskjobstates_id,
                    0,
                    '',
                    0,
                    "No suitable devices to inventory"
                );
                $input_taskjob = [];
                $input_taskjob['id'] = $pfTaskjob->fields['id'];
               //$input_taskjob['status'] = 1;
                $pfTaskjob->update($input_taskjob);
            } else {
                foreach ($a_agentList as $agent_id) {
                   //Add jobstate and put status (waiting on server = 0)
                    $a_input = [];
                    $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
                    $a_input['state'] = 0;
                    $a_input['agents_id'] = $agent_id;
                    $a_input['uniqid'] = $uniqid;
                    $a_input['execution_id'] = $pfTask->fields['execution_id'];
                    $alternate = 0;
                    for ($d = 0; $d < ceil($count_device / count($a_agentList)); $d++) {
                        if ((count($a_NetworkEquipment) + count($a_Printer)) > 0) {
                            $getdevice = "NetworkEquipment";
                            if ($alternate == "1") {
                                 $getdevice = "Printer";
                                 $alternate = 0;
                            } else {
                                $getdevice = "NetworkEquipment";
                                $alternate++;
                            }
                            if (count($a_NetworkEquipment) == '0') {
                                $getdevice = "Printer";
                            } elseif (count($a_Printer) == '0') {
                                $getdevice = "NetworkEquipment";
                            }
                            $a_input['itemtype'] = $getdevice;

                            switch ($getdevice) {
                                case 'NetworkEquipment':
                                    $a_input['items_id'] = array_pop($a_NetworkEquipment);
                                    $a_input['specificity'] = exportArrayToDB(
                                        $a_specificity['DEVICE']['NetworkEquipment' . $a_input['items_id']]
                                    );
                                    break;

                                case 'Printer':
                                    $a_input['items_id'] = array_pop($a_Printer);
                                    $a_input['specificity'] = exportArrayToDB(
                                        $a_specificity['DEVICE']['Printer' . $a_input['items_id']]
                                    );
                                    break;
                            }
                            $Taskjobstates_id = $pfTaskjobstate->add($a_input);
                     //Add log of taskjob
                            $a_input['plugin_glpiinventory_taskjobstates_id'] = $Taskjobstates_id;
                            $a_input['state'] = 7;
                            $a_input['date'] = date("Y-m-d H:i:s");
                            $pfTaskjoblog->add($a_input);
                            unset($a_input['state']);
                            if ($communication == "push") {
                                $_SESSION['glpi_plugin_glpiinventory']['agents'][$agent_id] = 1;
                            }
                        }
                    }
                }
                $input_taskjob = [];
                $input_taskjob['id'] = $pfTaskjob->fields['id'];
                $input_taskjob['status'] = 1;
                $pfTaskjob->update($input_taskjob);
            }
        }
        return $uniqid;
    }


   /**
    * When agent contact server, this function send datas to agent
    *
    * @param object $jobstate PluginGlpiinventoryTaskjobstate instance
    * @return array
    */
    public function run($jobstate)
    {
        $agent = new Agent();
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
        $credentials = new SNMPCredential();
        $pfToolbox = new PluginGlpiinventoryToolbox();
        $pfConfig = new PluginGlpiinventoryConfig();

        $current = $jobstate;
        $agent->getFromDB($current->fields['agents_id']);

        $ip = current(PluginGlpiinventoryToolbox::getIPforDevice(
            $jobstate->fields['itemtype'],
            $jobstate->fields['items_id']
        ));

        $param_attrs = [];
        $device_attrs = [];
        $auth_nodes = [];

        if ($ip == '') {
            $pfTaskjobstate->changeStatusFinish(
                $jobstate->fields['id'],
                $jobstate->fields['items_id'],
                $jobstate->fields['itemtype'],
                1,
                "Device have no ip"
            );
        } else {
           // Use general config when threads number is set to 0 on the agent
            $param_attrs['THREADS_QUERY'] = $agent->fields["threads_networkinventory"] == 0 ?
            $pfConfig->getValue('threads_networkinventory') :
            $agent->fields["threads_networkinventory"];

           // Use general config when timeout is set to 0 on the agent
            $param_attrs['TIMEOUT'] = $agent->fields["timeout_networkinventory"] == 0 ?
            $pfConfig->getValue('timeout_networkinventory') :
            $agent->fields["timeout_networkinventory"];

            $param_attrs['PID'] = $current->fields['id'];

            $changestate = 0;
            $taskjobstatedatas = $jobstate->fields;

            $a_extended = ['snmpcredentials_id' => 0];
            if ($jobstate->fields['itemtype'] == 'Printer') {
                $device_attrs['TYPE'] = 'PRINTER';
                $printer = new Printer();
                $a_extended = current($printer->find(['id' => $jobstate->fields['items_id']], [], 1));
            } elseif ($jobstate->fields['itemtype'] == 'NetworkEquipment') {
                $device_attrs['TYPE'] = 'NETWORKING';
                $neteq = new NetworkEquipment();
                $a_extended = current($neteq->find(['id' => $jobstate->fields['items_id']], [], 1));
            }

            $device_attrs['ID'] = $jobstate->fields['items_id'];
            $device_attrs['IP'] = $ip;
            $device_attrs['AUTHSNMP_ID'] = $a_extended['snmpcredentials_id'];

            if ($changestate == '0') {
                $pfTaskjobstate->changeStatus($taskjobstatedatas['id'], 1);
                $pfTaskjoblog->addTaskjoblog(
                    $taskjobstatedatas['id'],
                    '0',
                    'Agent',
                    '1',
                    $param_attrs['THREADS_QUERY'] . ' threads ' .
                    $param_attrs['TIMEOUT'] . ' timeout'
                );
                $changestate = $pfTaskjobstate->fields['id'];
            } else {
                $pfTaskjobstate->changeStatusFinish(
                    $taskjobstatedatas['id'],
                    $taskjobstatedatas['items_id'],
                    $taskjobstatedatas['itemtype'],
                    0,
                    "Merged with " . $changestate
                );
            }
            $snmpauthlist = $credentials->find();
            foreach ($snmpauthlist as $snmpauth) {
                $auth_node = $pfToolbox->addAuth($snmpauth['id']);
                if (count($auth_node)) {
                    $auth_nodes[] = $auth_node;
                }
            }
        }

        return [
         'OPTION' => [
            'NAME' => 'SNMPQUERY',
            'PARAM' => [
               'content' => '',
               'attributes' => $param_attrs
            ],
            'DEVICE' => [
               'content' => '',
               'attributes' => $device_attrs
            ]
         ] + $auth_nodes
        ];
    }


   /**
    * Get agents by the subnet given
    *
    * @global object $DB
    * @param integer $nb_computers
    * @param string $communication
    * @param string $subnet
    * @param string $ipstart
    * @param string $ipend
    * @return array
    */
    public function getAgentsSubnet($nb_computers, $communication, $subnet = '', $ipstart = '', $ipend = '')
    {
        global $DB;

        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfAgentmodule = new PluginGlpiinventoryAgentmodule();

       // Number of computers min by agent
        $nb_computerByAgentMin = 20;
        $nb_agentsMax = ceil($nb_computers / $nb_computerByAgentMin);

        $a_agentList = [];

        if ($subnet != '') {
            $subnet = " AND `glpi_ipaddresses`.`name` LIKE '" . $subnet . "%' ";
        } elseif ($ipstart != '' and $ipend != '') {
            $subnet = " AND ( INET_ATON(`glpi_ipaddresses`.`name`) > INET_ATON('" . $ipstart . "')
            AND  INET_ATON(`glpi_ipaddresses`.`name`) < INET_ATON('" . $ipend . "') ) ";
        }
        $a_agents = $pfAgentmodule->getAgentsCanDo('NETWORKINVENTORY');
        $a_agentsid = [];
        foreach ($a_agents as $a_agent) {
            $a_agentsid[] = $a_agent['id'];
        }
        if (count($a_agentsid) == '0') {
            return $a_agentList;
        }

        $where = " AND `glpi_agents`.`ID` IN (";
        $where .= implode(', ', $a_agentsid);
        $where .= ")
         AND `glpi_ipaddresses`.`name` != '127.0.0.1' ";

        $query = "SELECT `glpi_agents`.`id` as `a_id`,
         `glpi_ipaddresses`.`name` as ip, token
         FROM `glpi_agents`
         LEFT JOIN `glpi_networkports`
            ON `glpi_networkports`.`items_id` = `glpi_agents`.`items_id`
         LEFT JOIN `glpi_networknames`
              ON `glpi_networknames`.`items_id`=`glpi_networkports`.`id`
                 AND `glpi_networknames`.`itemtype`='NetworkPort'
         LEFT JOIN `glpi_ipaddresses`
              ON `glpi_ipaddresses`.`items_id`=`glpi_networknames`.`id`
                 AND `glpi_ipaddresses`.`itemtype`='NetworkName'
         LEFT JOIN `glpi_computers`
            ON `glpi_computers`.`id` = `glpi_agents`.`items_id`
         WHERE `glpi_agents`.`itemtype`='Computer'
            AND `glpi_networkports`.`itemtype`='Computer'
            " . $subnet . "
            " . $where . " ";
        Toolbox::logInFile('NET', $query);
        $result = $DB->query($query);
        if ($result) {
            while ($data = $DB->fetchArray($result)) {
                if ($communication == 'push') {
                    if ($pfTaskjob->isAgentAlive("1", $data['a_id'])) {
                        if (!in_array($a_agentList, $data['a_id'])) {
                             $a_agentList[] = $data['a_id'];
                            if (count($a_agentList) >= $nb_agentsMax) {
                                return $a_agentList;
                            }
                        }
                    }
                } elseif ($communication == 'pull') {
                    if (!in_array($data['a_id'], $a_agentList)) {
                        $a_agentList[] = $data['a_id'];
                        if (count($a_agentList) > $nb_agentsMax) {
                            return $a_agentList;
                        }
                    }
                }
            }
        }
        return $a_agentList;
    }


   /**
    * Get the devices have an IP in the IP range
    *
    * @global object $DB
    * @param integer $ipranges_id
    * @return array
    */
    public function getDevicesOfIPRange($ipranges_id)
    {
        global $DB;

        $devicesList = [];
        $pfIPRange = new PluginGlpiinventoryIPRange();

       // get all snmpauth
        $a_snmpauth = getAllDataFromTable(SNMPCredential::getTable());

        $pfIPRange->getFromDB($ipranges_id);
       // Search NetworkEquipment
        $query = "SELECT `glpi_networkequipments`.`id` AS `gID`,
                       `glpi_networkequipments`.`name` AS `gNAME`,
                       `glpi_ipaddresses`.`name` AS `gnifaddr`,
                       `snmpcredentials_id`
                  FROM `glpi_networkequipments`
                  LEFT JOIN `glpi_networkports`
                       ON `glpi_networkports`.`items_id`=`glpi_networkequipments`.`id`
                          AND `glpi_networkports`.`itemtype`='NetworkEquipment'
                  LEFT JOIN `glpi_networknames`
                       ON `glpi_networknames`.`items_id`=`glpi_networkports`.`id`
                          AND `glpi_networknames`.`itemtype`='NetworkPort'
                  LEFT JOIN `glpi_ipaddresses`
                       ON `glpi_ipaddresses`.`items_id`=`glpi_networknames`.`id`
                          AND `glpi_ipaddresses`.`itemtype`='NetworkName'
                  WHERE `glpi_networkequipments`.`is_deleted`='0'
                       AND `snmpcredentials_id`!='0'";
        if ($pfIPRange->fields['entities_id'] != '-1') {
            $entities = "(" . $pfIPRange->fields['entities_id'];
            foreach (getAncestorsOf("glpi_entities", $pfIPRange->fields['entities_id']) as $parent) {
                $entities .= ",$parent";
            }
            $entities .= ")";
            $query .= " AND `glpi_networkequipments`.`entities_id` IN " .
                     $entities . " ";
        }
        $query .= " AND inet_aton(`glpi_ipaddresses`.`name`)
                      BETWEEN inet_aton('" . $pfIPRange->fields['ip_start'] . "')
                      AND inet_aton('" . $pfIPRange->fields['ip_end'] . "') ";
        $query .= " GROUP BY `glpi_networkequipments`.`id`";
        $result = $DB->query($query);
        while ($data = $DB->fetchArray($result)) {
            if (isset($a_snmpauth[$data['snmpcredentials_id']])) {
                $devicesList[] = [
                'NetworkEquipment' => $data['gID']
                ];
            }
        }
       // Search Printer
        $query = "SELECT `glpi_printers`.`id` AS `gID`,
                      `glpi_printers`.`name` AS `gNAME`,
                      `glpi_ipaddresses`.`name` AS `gnifaddr`,
                      `snmpcredentials_id`
               FROM `glpi_printers`
               LEFT JOIN `glpi_networkports`
                    ON `glpi_networkports`.`items_id`=`glpi_printers`.`id`
                       AND `glpi_networkports`.`itemtype`='Printer'
               LEFT JOIN `glpi_networknames`
                    ON `glpi_networknames`.`items_id`=`glpi_networkports`.`id`
                       AND `glpi_networknames`.`itemtype`='NetworkPort'
               LEFT JOIN `glpi_ipaddresses`
                    ON `glpi_ipaddresses`.`items_id`=`glpi_networknames`.`id`
                       AND `glpi_ipaddresses`.`itemtype`='NetworkName'
               WHERE `glpi_printers`.`is_deleted`=0
                     AND `snmpcredentials_id`!='0'";
        if ($pfIPRange->fields['entities_id'] != '-1') {
            $entities = "(" . $pfIPRange->fields['entities_id'];
            foreach (getAncestorsOf("glpi_entities", $pfIPRange->fields['entities_id']) as $parent) {
                $entities .= ",$parent";
            }
            $entities .= ")";
            $query .= "AND `glpi_printers`.`entities_id` IN " . $entities . " ";
        }
        $query .= " AND inet_aton(`glpi_ipaddresses`.`name`)
                   BETWEEN inet_aton('" . $pfIPRange->fields['ip_start'] . "')
                   AND inet_aton('" . $pfIPRange->fields['ip_end'] . "') ";
        $query .= " GROUP BY `glpi_printers`.`id`";
        $result = $DB->query($query);
        while ($data = $DB->fetchArray($result)) {
            if (isset($a_snmpauth[$data['snmpcredentials_id']])) {
                $devicesList[] = [
                'Printer' => $data['gID']
                ];
            }
        }
        return $devicesList;
    }
}
