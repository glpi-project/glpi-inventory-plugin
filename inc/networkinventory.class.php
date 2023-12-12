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
 * GLPI Inventory Plugin is distributed in the hope that it will be useful,
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
        $devices = [
            NetworkEquipment::getType() => [],
            Printer::getType() => []
        ];
        $a_definition = importArrayFromDB($pfTaskjob->fields['definition']);
        foreach ($a_definition as $datas) {
            $itemtype = key($datas);
            $items_id = current($datas);

            switch ($itemtype) {
                case 'PluginGlpiinventoryIPRange':
                    $a_iprange[] = $items_id;
                    break;

                case 'NetworkEquipment':
                case 'Printer':
                    $iterator = $DB->request([
                        'SELECT' => [
                            $itemtype::getTable() . '.id AS gID',
                            'glpi_ipaddresses.name AS gnifaddr',
                            'snmpcredentials_id'
                        ],
                        'FROM'   => $itemtype::getTable(),
                        'LEFT JOIN' => [
                            'glpi_networkports' => [
                                'ON' => [
                                    'glpi_networkports' => 'items_id',
                                    $itemtype::getTable() => 'id',[
                                        'AND' => [
                                            'glpi_networkports.itemtype' => $itemtype
                                        ]
                                    ]
                                ]
                            ],
                            'glpi_networknames' => [
                                'ON' => [
                                    'glpi_networknames' => 'items_id',
                                    'glpi_networkports' => 'id',[
                                        'AND' => [
                                            'glpi_networknames.itemtype' => 'NetworkPort'
                                        ]
                                    ]
                                ]
                            ],
                            'glpi_ipaddresses' => [
                                'ON' => [
                                    'glpi_ipaddresses' => 'items_id',
                                    'glpi_networknames' => 'id',[
                                        'AND' => [
                                            'glpi_ipaddresses.itemtype' => 'NetworkName'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'WHERE'  => [
                            $itemtype::getTable() . '.is_deleted' => 0,
                            'snmpcredentials_id' => ['!=', 0],
                            $itemtype::getTable() . '.id' => $items_id,
                            'glpi_ipaddresses.name' => ['!=', '']
                        ],
                        'LIMIT'  => 1
                    ]);

                    foreach ($iterator as $data) {
                        if (isset($a_snmpauth[$data['snmpcredentials_id']])) {
                              $input = [];
                              $input['TYPE'] = ($itemtype === NetworkEquipment::getType() ? 'NETWORKING' : 'PRINTER');
                              $input['ID'] = $data['gID'];
                              $input['IP'] = $data['gnifaddr'];
                              $input['AUTHSNMP_ID'] = $data['snmpcredentials_id'];
                              $a_specificity['DEVICE'][$itemtype . $data['gID']] = $input;
                              $devices[$itemtype][] = $items_id;
                        }
                    }
                    break;
            }
        }

        // Get all devices on each iprange
        foreach ($a_iprange as $items_id) {
            $pfIPRange->getFromDB($items_id);

            foreach ([NetworkEquipment::getType(), Printer::getType()] as $cur_itemtype) {
                // Search NetworkEquipment
                $criteria = [
                    'SELECT' => [
                        $cur_itemtype::getTable() . '.id AS gID',
                        'glpi_ipaddresses.name AS gnifaddr',
                        'snmpcredentials_id'
                    ],
                    'FROM' => $cur_itemtype::getTable(),
                    'LEFT JOIN' => [
                        'glpi_networkports' => [
                            'ON' => [
                                'glpi_networkports' => 'items_id',
                                $cur_itemtype::getTable() => 'id',[
                                    'AND' => [
                                        'glpi_networkports.itemtype' => $cur_itemtype
                                    ]
                                ]
                            ]
                        ],
                        'glpi_networknames' => [
                            'ON' => [
                                'glpi_networknames' => 'items_id',
                                'glpi_networkports' => 'id',[
                                    'AND' => [
                                        'glpi_networknames.itemtype' => 'NetworkPort'
                                    ]
                                ]
                            ]
                        ],
                        'glpi_ipaddresses' => [
                            'ON' => [
                                'glpi_ipaddresses' => 'items_id',
                                'glpi_networknames' => 'id',[
                                    'AND' => [
                                        'glpi_ipaddresses.itemtype' => 'NetworkName'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'WHERE' => [
                        $cur_itemtype::getTable() . '.is_deleted' => 0,
                        'snmpcredentials_id' => ['!=', 0],
                        new \QueryExpression('inet_aton(' . $DB->quoteName('glpi_ipaddresses.name') . ') BETWEEN inet_aton(' . $DB->quote($pfIPRange->fields['ip_start']) . ') AND inet_aton(' . $DB->quote($pfIPRange->fields['ip_end']) . ')'),
                    ],
                    'GROUPBY' => $cur_itemtype::getTable() . '.id'
                ];

                if ($pfIPRange->fields['entities_id'] != '-1') {
                    $criteria['WHERE'][$cur_itemtype::getTable() . '.entities_id'] = array_merge(
                        [$pfIPRange->fields['entities_id']],
                        getAncestorsOf("glpi_entities", $pfIPRange->fields['entities_id'])
                    );
                }

                $iterator = $DB->request($criteria);

                foreach ($iterator as $data) {
                    if (isset($a_snmpauth[$data['snmpcredentials_id']])) {
                        $input = [];
                        $input['TYPE'] = ($cur_itemtype === NetworkEquipment::getType() ? 'NETWORKING' : 'PRINTER');
                        $input['ID'] = $data['gID'];
                        $input['IP'] = $data['gnifaddr'];
                        $input['AUTHSNMP_ID'] = $data['snmpcredentials_id'];
                        $a_specificity['DEVICE'][$cur_itemtype . $data['gID']] = $input;
                        $devices[$cur_itemtype][] = $data['gID'];
                    }
                }
            }
        }

        // *** For dynamic agent same subnet, it's an another management ***
        if (strstr($pfTaskjob->fields['action'], '".2"')) {
            $a_subnet = [];
            $a_devicesubnet = [];
            foreach ($devices[NetworkEquipment::getType()] as $items_id) {
                $NetworkEquipment->getFromDB($items_id);
                $a_ip = explode(".", $NetworkEquipment->fields['ip']);
                $ip_subnet = $a_ip[0] . "." . $a_ip[1] . "." . $a_ip[2] . ".";
                if (!isset($a_subnet[$ip_subnet])) {
                    $a_subnet[$ip_subnet] = 0;
                }
                $a_subnet[$ip_subnet]++;
                $a_devicesubnet[$ip_subnet]['NetworkEquipment'][$items_id] = 1;
            }
            foreach ($devices[Printer::getType()] as $items_id) {
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

            $a_input = [];
            $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
            $a_input['state'] = 1;
            $a_input['agents_id'] = 0;
            $a_input['itemtype'] = '';
            $a_input['items_id'] = 0;
            $a_input['uniqid'] = $uniqid;
            $a_input['execution_id'] = $pfTask->fields['execution_id'];

            $taskvalid = 0;
            foreach (array_keys($a_subnet) as $subnet) {
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
            }
            if ($taskvalid == "0") {
                $pfTaskjob->reinitializeTaskjobs($pfTaskjob->fields['plugin_glpiinventory_tasks_id']);
            }
        } else {
            /**
             * Manage agents
             */
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
            // Return an empty list to avoid adding an option with no data in the joblist
            return [];
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
            // Only keep required snmp credentials
            $snmpauthlist = $credentials->find(['id' => $a_extended['snmpcredentials_id']]);
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
    * Get the devices have an IP in the IP range
    *
    * @global object $DB
    * @param integer $ipranges_id
    * @return array
    */
    public function getDevicesOfIPRange($ipranges_id, bool $restrict_entity = true)
    {
        global $DB;

        $devicesList = [];
        $pfIPRange = new PluginGlpiinventoryIPRange();

       // get all snmpauth
        $a_snmpauth = getAllDataFromTable(SNMPCredential::getTable());

        $pfIPRange->getFromDB($ipranges_id);

        foreach ([NetworkEquipment::getType(), Printer::getType()] as $itemtype) {
            $criteria = [
                'SELECT' => [
                    $itemtype::getTable() . '.id AS gID',
                    $itemtype::getTable() . '.name AS gNAME',
                    'glpi_ipaddresses.name AS gnifaddr',
                    'snmpcredentials_id'
                ],
                'FROM' => $itemtype::getTable(),
                'LEFT JOIN' => [
                    'glpi_networkports' => [
                        'ON' => [
                            'glpi_networkports' => 'items_id',
                            $itemtype::getTable() => 'id', [
                                'AND' => [
                                    'glpi_networkports.itemtype' => $itemtype
                                ]
                            ]
                        ]
                    ],
                    'glpi_networknames' => [
                        'ON' => [
                            'glpi_networknames' => 'items_id',
                            'glpi_networkports' => 'id', [
                                'AND' => [
                                    'glpi_networknames.itemtype' => 'NetworkPort'
                                ]
                            ]
                        ]
                    ],
                    'glpi_ipaddresses' => [
                        'ON' => [
                            'glpi_ipaddresses' => 'items_id',
                            'glpi_networknames' => 'id',[
                                'AND' => [
                                    'glpi_ipaddresses.itemtype' => 'NetworkName'
                                ]
                            ]
                        ]
                    ]
                ],
                'WHERE' => [
                    $itemtype::getTable() . '.is_deleted' => 0,
                    'snmpcredentials_id' => ['!=', '0'],
                    new \QueryExpression(
                        'inet_aton(' . $DB->quoteName('glpi_ipaddresses.name') . ') BETWEEN ' .
                        'inet_aton(' . $DB->quote($pfIPRange->fields['ip_start']) . ') AND inet_aton(' .
                        $DB->quote($pfIPRange->fields['ip_end']) . ')'
                    )
                ],
                'GROUPBY' => 'gID'
            ];

            if ($pfIPRange->fields['entities_id'] != '-1' && $restrict_entity) {
                $criteria['WHERE'][$itemtype::getTable() . '.entities_id'] = array_merge(
                    [$pfIPRange->fields['entities_id']],
                    getAncestorsOf("glpi_entities", $pfIPRange->fields['entities_id'])
                );
            }

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                if (isset($a_snmpauth[$data['snmpcredentials_id']])) {
                    $devicesList[] = [
                        $itemtype => $data['gID']
                    ];
                }
            }
        }

        return $devicesList;
    }
}
