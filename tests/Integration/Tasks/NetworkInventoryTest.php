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
use Glpi\DBAL\QueryFunction;
use Glpi\Tests\DbTestCase;

class NetworkInventoryTest extends DbTestCase
{
    private function prepareDb(): void
    {
        global $DB;

        $_SESSION['glpiactiveentities_string'] = "'0'";

        $computer        = new Computer();
        $agent           = new Agent();
        $pfTask          = new PluginGlpiinventoryTask();
        $pfTaskjob       = new PluginGlpiinventoryTaskjob();
        $pfIPRange       = new PluginGlpiinventoryIPRange();
        $networkEquipment = new NetworkEquipment();
        $networkPort     = new NetworkPort();
        $networkName     = new NetworkName();
        $iPAddress       = new IPAddress();
        $printer         = new Printer();

        $result = $DB->request([
            'SELECT' => [
                new QueryExpression(QueryFunction::max('id') . '+1', 'newID'),
            ],
            'FROM'   => Entity::getTable(),
        ])->current();
        $entities_id = $result['newID'];

        // Create entities
        $this->assertNotFalse(
            $DB->insert(
                Entity::getTable(),
                [
                    'id' => $entities_id,
                    'name' => 'ent1',
                    'entities_id' => 0,
                    'comment' => '',
                ]
            )
        );
        $entity1Id = $DB->insertId();

        $this->assertNotFalse(
            $DB->insert(
                Entity::getTable(),
                [
                    'id' => ++$entities_id,
                    'name' => 'ent2',
                    'entities_id' => 0,
                    'comment' => '',
                ]
            )
        );
        $entity2Id = $DB->insertId();

        $this->assertNotFalse(
            $DB->insert(
                Entity::getTable(),
                [
                    'id' => ++$entities_id,
                    'name' => 'ent1.1',
                    'entities_id' => $entity1Id,
                    'comment' => '',
                ]
            )
        );
        $entity11Id = $DB->insertId();

        // Create computers + agents
        $input = [
            'entities_id' => 0,
            'name'        => 'computer1',
        ];
        $computers_id = $computer->add($input);
        $this->assertNotFalse($computers_id);

        $agenttype = $DB->request(['FROM' => AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $input = [
            'entities_id' => 0,
            'name'        => 'computer1',
            'version'     => '{"INVENTORY":"v2.3.11"}',
            'deviceid'    => 'computer1',
            'useragent'   => 'FusionInventory-Agent_v2.3.11',
            'itemtype' => Computer::class,
            'items_id' => $computers_id,
            'agenttypes_id' => $agenttype['id'],
        ];
        $agent1Id = $agent->add($input);
        $this->assertNotFalse($agent1Id);

        // Create Network Equipments
        $input = [
            'name'        => 'sw0',
            'entities_id' => 0,
            'snmpcredentials_id' => 2,
        ];
        $netequipId = $networkEquipment->add($input);
        $this->assertNotFalse($netequipId);

        $input = [
            'entities_id'        => 0,
            'name'               => 'management',
            'items_id'           => $netequipId,
            'itemtype'           => NetworkEquipment::class,
            'instantiation_type' => NetworkPortAggregate::class,
            'NetworkName__ipaddresses' => ['-1' => '10.0.0.10'],
        ];
        $networkPort->splitInputForElements($input);
        $netportId = $networkPort->add($input);
        $this->assertNotFalse($netportId);

        $networkPort->updateDependencies(true);

        $input = [
            'name'        => 'sw1',
            'entities_id' => $entity1Id,
            'snmpcredentials_id' => 2,
        ];
        $netEquipId = $networkEquipment->add($input);
        $this->assertNotFalse($netEquipId);

        $input = [
            'entities_id'        => $entity1Id,
            'name'               => 'management',
            'items_id'           => $netequipId,
            'itemtype'           => NetworkEquipment::class,
            'instantiation_type' => NetworkPortAggregate::class,
            'NetworkName__ipaddresses' => ['-1' => '10.0.0.11'],
        ];
        $networkPort->splitInputForElements($input);
        $netportId = $networkPort->add($input);
        $this->assertNotFalse($netportId);

        $networkPort->updateDependencies(true);

        $input = [
            'name'        => 'sw2',
            'entities_id' => $entity2Id,
            'snmpcredentials_id' => 2,
        ];
        $netequipId = $networkEquipment->add($input);
        $this->assertNotFalse($netequipId);

        $input = [
            'entities_id'        => $entity2Id,
            'name'               => 'management',
            'items_id'           => $netequipId,
            'itemtype'           => NetworkEquipment::class,
            'instantiation_type' => NetworkPortAggregate::class,
            'NetworkName__ipaddresses' => ['-1' => '10.0.0.12'],
        ];
        $networkPort->splitInputForElements($input);
        $netportId = $networkPort->add($input);
        $this->assertNotFalse($netportId);

        $networkPort->updateDependencies(true);

        $input = [
            'name'        => 'sw3/1.1',
            'entities_id' => $entity11Id,
            'snmpcredentials_id' => 2,
        ];
        $netequipId = $networkEquipment->add($input);
        $this->assertNotFalse($netequipId);

        $input = [
            'entities_id'        => $entity11Id,
            'name'               => 'management',
            'items_id'           => $netequipId,
            'itemtype'           => NetworkEquipment::class,
            'instantiation_type' => NetworkPortAggregate::class,
            'NetworkName__ipaddresses' => ['-1' => '10.0.0.21'],
        ];
        $networkPort->splitInputForElements($input);
        $netportId = $networkPort->add($input);
        $this->assertNotFalse($netportId);

        $networkPort->updateDependencies(true);

        // Create Printers
        $input = [
            'name'        => 'printer 001',
            'entities_id' => 0,
            'snmpcredentials_id' => 2,
        ];
        $printers_id = $printer->add($input);
        $this->assertNotFalse($printers_id);

        $networkports_id = $networkPort->add([
            'itemtype'           => Printer::class,
            'instantiation_type' => NetworkPortEthernet::class,
            'items_id'           => $printers_id,
            'entities_id'        => 0,
        ]);
        $this->assertNotFalse($networkports_id);

        $networknames_id = $networkName->add([
            'entities_id' => 0,
            'itemtype'    => NetworkPort::class,
            'items_id'    => $networkports_id,
        ]);
        $this->assertNotFalse($networknames_id);

        $ipId = $iPAddress->add([
            'entities_id' => 0,
            'itemtype'    => NetworkName::class,
            'items_id'    => $networknames_id,
            'name'        => '192.168.200.124',
        ]);
        $this->assertNotFalse($ipId);

        // Add IPRange
        $input = [
            'entities_id' => 1,
            'name'        => 'Office',
            'ip_start'    => '10.0.0.1',
            'ip_end'      => '10.0.0.254',
        ];
        $ipranges_id = $pfIPRange->add($input);
        $this->assertNotFalse($ipranges_id);

        // Allow all agents to do network inventory
        $module = new PluginGlpiinventoryAgentmodule();
        $this->assertTrue($module->getFromDBByCrit(['modulename' => 'NETWORKINVENTORY']));
        $this->assertTrue(
            $module->update([
                'id'        => $module->fields['id'],
                'is_active' => 1,
            ])
        );

        // create task
        $input = [
            'entities_id' => 0,
            'name'        => 'network inventory',
            'is_active'   => 1,
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        // create taskjob
        $input = [
            'plugin_glpiinventory_tasks_id' => $tasks_id,
            'entities_id'                     => 0,
            'name'                            => 'inventory',
            'method'                          => 'networkinventory',
            'targets'                         => '[{"PluginGlpiinventoryIPRange":"' . $ipranges_id . '"}]',
            'actors'                          => '[{"Agent":"' . $agent1Id . '"}]',
        ];
        $taskjobId = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobId);

        PluginGlpiinventoryTask::cronTaskscheduler();
    }

    public function testPrepareTask(): void
    {
        $this->prepareDb();
        $pfTask  = new PluginGlpiinventoryTask();
        $agent = new Agent();

        $this->assertTrue(
            $pfTask->getFromDBByCrit(['name' => 'network inventory']),
            'Task not found'
        );
        $this->assertTrue(
            $agent->getFromDBByCrit(['name' => 'computer1']),
            'Agent not found'
        );

        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $ref = [$agent->fields['id'] => 'computer1'];

        $this->assertEquals($ref, $data['agents']);
    }

    public function testGetDevicesToInventory(): void
    {
        $this->prepareDb();
        $networkequipments_id = getItemByTypeName(NetworkEquipment::class, 'sw0', true);
        $pfNetworkinventory = new PluginGlpiinventoryNetworkinventory();
        $jobstate = new PluginGlpiinventoryTaskjobstate();

        $this->assertTrue(
            $jobstate->getFromDBByCrit(['itemtype' => NetworkEquipment::class, 'items_id' => $networkequipments_id]),
            'TaskJobState not found'
        );
        $data = $pfNetworkinventory->run($jobstate);

        $this->assertEquals('NETWORKING', $data['OPTION']['DEVICE']['attributes']['TYPE']);
        $this->assertEquals('10.0.0.10', $data['OPTION']['DEVICE']['attributes']['IP']);
        $this->assertEquals('2', $data['OPTION']['DEVICE']['attributes']['AUTHSNMP_ID']);
        $this->assertGreaterThan(0, intval($data['OPTION']['DEVICE']['attributes']['ID']));
    }

    public function testPrinterToInventoryWithIp(): void
    {
        global $DB;

        $this->prepareDb();
        $computer = new Computer();
        $printer       = new Printer();
        $pfTask        = new PluginGlpiinventoryTask();
        $pfTaskjob     = new PluginGlpiinventoryTaskjob();
        $agent         = new Agent();
        $communication = new PluginGlpiinventoryCommunication();
        $jobstate      = new PluginGlpiinventoryTaskjobstate();

        $this->assertTrue($printer->getFromDBByCrit(['name' => 'printer 001']));

        $computers_id = $computer->add([
            'entities_id' => 0,
            'name' => 'computer_testPrinterToInventoryWithIp',
        ]);
        $this->assertNotFalse($computers_id);

        $agents_id = $agent->add([
            'entities_id' => 0,
            'name' => 'computer_testPrinterToInventoryWithIp',
            'version' => '{"INVENTORY":"v1.0.0"}',
            'deviceid' => 'computer_testPrinterToInventoryWithIp',
            'useragent' => 'GLPI-Agent_v1.0.0',
            'itemtype' => Computer::class,
            'items_id' => $computers_id,
            'agenttypes_id' => getItemByTypeName(AgentType::class, 'Core', true),
        ]);
        $this->assertNotFalse($agents_id);

        // Add task
        // create task
        $input = [
            'entities_id' => 0,
            'name' => 'printer inventory',
            'is_active'   => 1,
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        // create taskjob
        $input = [
            'plugin_glpiinventory_tasks_id' => $tasks_id,
            'entities_id'                     => 0,
            'name'                            => 'printer inventory',
            'method'                          => 'networkinventory',
            'targets'                         => '[{"Printer":"' . $printer->fields['id'] . '"}]',
            'actors'                          => '[{"Agent":"' . $agent->fields['id'] . '"}]',
        ];
        $taskjobId = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobId);

        PluginGlpiinventoryTask::cronTaskscheduler();

        // Task is prepared
        // Agent will get data

        $message = $communication->getTaskAgent($agent->fields['id']);
        $jobstate->getFromDBByCrit(['itemtype' => Printer::class]);

        $ref = [
            'OPTION' => [
                'NAME' => 'SNMPQUERY',
                'PARAM' => [
                    'content' => '',
                    'attributes' => [
                        'THREADS_QUERY' => 10,
                        'TIMEOUT'       => '15',
                        'PID'           => $jobstate->fields['id'],
                    ],
                ],
                'DEVICE' => [
                    'content' => '',
                    'attributes' => [
                        'TYPE'        => 'PRINTER',
                        'ID'          => $printer->fields['id'],
                        'IP'          => '192.168.200.124',
                        'AUTHSNMP_ID' => 2,
                    ],
                ], [
                    'AUTHENTICATION' => [
                        'ID'        => 2,
                        'VERSION'   => '2c',
                        'COMMUNITY' => 'public',
                    ],
                ],
            ],
        ];

        $this->assertEquals([$ref], $message, 'XML of SNMP inventory task');
    }

    public function testPrinterToInventoryWithoutIp(): void
    {
        $this->prepareDb();
        $printer       = new Printer();
        $pfTaskjob     = new PluginGlpiinventoryTaskjob();
        $agent         = new Agent();
        $communication = new PluginGlpiinventoryCommunication();
        $iPAddress     = new IPAddress();

        // Delete all tasks
        $pfTask = new PluginGlpiinventoryTask();
        $items = $pfTask->find();
        foreach ($items as $item) {
            $this->assertTrue($pfTask->delete(['id' => $item['id']], true));
        }

        // Delete ipaddress of the printer
        if ($iPAddress->getFromDBByCrit(['name' => '192.168.200.124'])) {
            $this->assertTrue($iPAddress->delete(['id' => $iPAddress->fields['id']]));
        }

        $this->assertTrue($printer->getFromDBByCrit(['name' => 'printer 001']));
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer1']));

        // Add task
        // create task
        $input = [
            'entities_id' => 0,
            'name'        => 'network inventory',
            'is_active'   => 1,
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        // create taskjob
        $input = [
            'plugin_glpiinventory_tasks_id' => $tasks_id,
            'entities_id'                     => 0,
            'name'                            => 'inventory',
            'method'                          => 'networkinventory',
            'targets'                         => '[{"Printer":"' . $printer->fields['id'] . '"}]',
            'actors'                          => '[{"Agent":"' . $agent->fields['id'] . '"}]',
        ];
        $this->assertNotFalse($pfTaskjob->add($input));

        PluginGlpiinventoryTask::cronTaskscheduler();

        // Task is prepared
        // Agent will get data

        $communication->getTaskAgent($agent->fields['id']);
        $message = $communication->getMessage();
        $json = json_encode($message);
        $array = json_decode($json, true);

        $ref = [];

        $this->assertEquals($ref, $array, 'XML of SNMP inventory task');
    }
}
