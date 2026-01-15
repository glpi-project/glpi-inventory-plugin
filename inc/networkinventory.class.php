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

use Glpi\DBAL\QueryExpression;

/**
 * Manage network inventory task jobs.
 */
class PluginGlpiinventoryNetworkinventory extends PluginGlpiinventoryCommunication
{
    /**
     * When agent contact server, this function send datas to agent
     *
     * @param PluginGlpiinventoryTaskjobstate $jobstate PluginGlpiinventoryTaskjobstate instance
     * @return array<string, mixed>
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

        $taskjob = new PluginGlpiinventoryTaskjob();
        $taskjob->getFromDB($jobstate->fields['plugin_glpiinventory_taskjobs_id']);

        $ip = $this->getDeviceIPOfTaskID($jobstate->fields['itemtype'], $jobstate->fields['items_id'], $taskjob->fields['plugin_glpiinventory_tasks_id']);

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
            $param_attrs['THREADS_QUERY'] = $agent->fields["threads_networkinventory"] == 0
            ? $pfConfig->getValue('threads_networkinventory')
            : $agent->fields["threads_networkinventory"];

            // Use general config when timeout is set to 0 on the agent
            $param_attrs['TIMEOUT'] = $agent->fields["timeout_networkinventory"] == 0
            ? $pfConfig->getValue('timeout_networkinventory')
            : $agent->fields["timeout_networkinventory"];

            $param_attrs['PID'] = $current->fields['id'];

            $taskjobstatedatas = $jobstate->fields;

            $a_extended = ['snmpcredentials_id' => 0];
            if ($jobstate->fields['itemtype'] == Printer::class) {
                $device_attrs['TYPE'] = 'PRINTER';
                $printer = new Printer();
                $a_extended = current($printer->find(['id' => $jobstate->fields['items_id']], [], 1));
            } elseif ($jobstate->fields['itemtype'] == NetworkEquipment::class) {
                $device_attrs['TYPE'] = 'NETWORKING';
                $neteq = new NetworkEquipment();
                $a_extended = current($neteq->find(['id' => $jobstate->fields['items_id']], [], 1));
            }

            $device_attrs['ID'] = $jobstate->fields['items_id'];
            $device_attrs['IP'] = $ip;
            $device_attrs['AUTHSNMP_ID'] = $a_extended['snmpcredentials_id'];

            $pfTaskjobstate->changeStatus($taskjobstatedatas['id'], 1);
            /*$pfTaskjoblog->addTaskjoblog(
                $taskjobstatedatas['id'],
                0,
                Agent::class,
                '1',
                $param_attrs['THREADS_QUERY'] . ' threads '
                . $param_attrs['TIMEOUT'] . ' timeout'
            );*/
            $pfTaskjoblog->addJobLog(
                taskjobs_id: $pfTaskjobstate->fields['id'],
                items_id: 0,
                itemtype: Agent::class,
                state: PluginGlpiinventoryTaskjoblog::TASK_STARTED,
                comment: new \GlpiPlugin\Glpiinventory\Job\Types\Info( //FIXME: probably use a specific type to store those information
                    $param_attrs['THREADS_QUERY'] . ' threads '
                    . $param_attrs['TIMEOUT'] . ' timeout'
                )
            );


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
                    'attributes' => $param_attrs,
                ],
                'DEVICE' => [
                    'content' => '',
                    'attributes' => $device_attrs,
                ],
            ] + $auth_nodes,
        ];
    }


    /**
     * Get the devices have an IP in the IP range
     *
     * @param int $ipranges_id
     * @return array<array<string, int>>
     */
    public function getDevicesOfIPRange($ipranges_id, bool $restrict_entity = true)
    {
        /** @var DBmysql $DB */
        global $DB;

        $devicesList = [];
        $pfIPRange = new PluginGlpiinventoryIPRange();

        // get all snmpauth
        $a_snmpauth = getAllDataFromTable(SNMPCredential::getTable());

        $pfIPRange->getFromDB($ipranges_id);

        foreach ([NetworkEquipment::class, Printer::class] as $itemtype) {
            $criteria = [
                'SELECT' => [
                    $itemtype::getTable() . '.id AS gID',
                    $itemtype::getTable() . '.name AS gNAME',
                    'glpi_ipaddresses.name AS gnifaddr',
                    'snmpcredentials_id',
                ],
                'FROM' => $itemtype::getTable(),
                'LEFT JOIN' => [
                    'glpi_networkports' => [
                        'ON' => [
                            'glpi_networkports' => 'items_id',
                            $itemtype::getTable() => 'id', [
                                'AND' => [
                                    'glpi_networkports.itemtype' => $itemtype,
                                ],
                            ],
                        ],
                    ],
                    'glpi_networknames' => [
                        'ON' => [
                            'glpi_networknames' => 'items_id',
                            'glpi_networkports' => 'id', [
                                'AND' => [
                                    'glpi_networknames.itemtype' => NetworkPort::class,
                                ],
                            ],
                        ],
                    ],
                    'glpi_ipaddresses' => [
                        'ON' => [
                            'glpi_ipaddresses' => 'items_id',
                            'glpi_networknames' => 'id',[
                                'AND' => [
                                    'glpi_ipaddresses.itemtype' => NetworkName::class,
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE' => [
                    $itemtype::getTable() . '.is_deleted' => 0,
                    'snmpcredentials_id' => ['!=', '0'],
                    'glpi_ipaddresses.version' => 4,
                    new QueryExpression(
                        'inet_aton(' . $DB->quoteName('glpi_ipaddresses.name') . ') BETWEEN '
                        . 'inet_aton(' . $DB->quote($pfIPRange->fields['ip_start']) . ') AND inet_aton('
                        . $DB->quote($pfIPRange->fields['ip_end']) . ')'
                    ),
                ],
                'GROUPBY' => 'gID',
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
                        $itemtype => $data['gID'],
                    ];
                }
            }
        }

        return $devicesList;
    }

    /**
    * Get the device IP in the IP range
    *
    * @param string $job_itemtype
    * @param int $job_items_id
    * @param int $tasks_id
    * @return string|false
    */
    public function getDeviceIPOfTaskID($job_itemtype, $job_items_id, $tasks_id)
    {
        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfIPRange = new PluginGlpiinventoryIPRange();
        $iPAddress = new IPAddress();

        $device_ips = PluginGlpiinventoryToolbox::getIPforDevice(
            $job_itemtype,
            $job_items_id
        );
        $ip = current($device_ips);
        $a_taskjobs = $pfTaskjob->find(['plugin_glpiinventory_tasks_id' => $tasks_id]);
        foreach ($a_taskjobs as $a_taskjob) {
            $a_definition = importArrayFromDB($a_taskjob['targets']);
            foreach ($a_definition as $datas) {
                $itemtype = key($datas);
                $items_id = current($datas);

                switch ($itemtype) {
                    case PluginGlpiinventoryIPRange::class:
                        $pfIPRange->getFromDB($items_id);
                        foreach ($device_ips as $device_ip) {
                            if ($pfIPRange->getIp2long($device_ip) <= $pfIPRange->getIp2long($pfIPRange->fields['ip_end']) && $pfIPRange->getIp2long($pfIPRange->fields['ip_start']) <= $pfIPRange->getIp2long($device_ip)) {
                                // in range, assign this device IP
                                $ip = $device_ip;

                                $a_ipaddresses = $iPAddress->find(
                                    ['name' => $device_ip]
                                );

                                if (count($a_ipaddresses) > 1) {
                                    // continue loop if IP is non unique
                                    continue;
                                } else {
                                    // exit loop if IP is unique
                                    break;
                                }
                            }
                        }
                        break;
                }
            }
        }

        return $ip;
    }
}
