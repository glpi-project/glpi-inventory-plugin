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

use PHPUnit\Framework\TestCase;

class NetworkInventoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {

       // Delete all computers
        $computer = new Computer();
        $items = $computer->find(['NOT' => ['name' => ['LIKE', '_test_pc%']]]);
        foreach ($items as $item) {
            $computer->delete(['id' => $item['id']], true);
        }

       // Delete all agents
        $agent = new Agent();
        $items = $agent->find();
        foreach ($items as $item) {
            $agent->delete(['id' => $item['id']], true);
        }

       // Delete all ipranges
        $pfIPRange = new PluginGlpiinventoryIPRange();
        $items = $pfIPRange->find();
        foreach ($items as $item) {
            $pfIPRange->delete(['id' => $item['id']], true);
        }

       // Delete all tasks
        $pfTask = new PluginGlpiinventoryTask();
        $items = $pfTask->find();
        foreach ($items as $item) {
            $pfTask->delete(['id' => $item['id']], true);
        }

       // Delete all network equipments
        $networkEquipment = new NetworkEquipment();
        $items = $networkEquipment->find();
        foreach ($items as $item) {
            $networkEquipment->delete(['id' => $item['id']], true);
        }

       // Delete all printers
        $printer = new Printer();
        $items = $printer->find();
        foreach ($items as $item) {
            $printer->delete(['id' => $item['id']], true);
        }

       // Delete all entities exept root entity
        $entity = new Entity();
        $items = $entity->find();
        foreach ($items as $item) {
            if ($item['id'] > 0) {
                $entity->delete(['id' => $item['id']], true);
            }
        }
    }


   /**
    * @test
    */
    public function prepareDb()
    {
        global $DB;

        $DB->connect();

        $entity          = new Entity();
        $computer        = new Computer();
        $agent           = new Agent();
        $pfTask          = new PluginGlpiinventoryTask();
        $pfTaskjob       = new PluginGlpiinventoryTaskjob();
        $pfIPRange       = new PluginGlpiinventoryIPRange();
        $networkEquipment = new NetworkEquipment();
        $networkPort     = new NetworkPort();
        $networkName     = new NetworkName();
        $printer       = new Printer();
        $iPAddress       = new IPAddress();
        $printer         = new Printer();

       // Create entities
        $entity1Id = $entity->add([
         'name'        => 'ent1',
         'entities_id' => 0,
         'comment'     => ''
        ]);
        $this->assertNotFalse($entity1Id);

        $entity2Id = $entity->add([
         'name'        => 'ent2',
         'entities_id' => 0,
         'comment'     => ''
        ]);
        $this->assertNotFalse($entity2Id);

        $entity11Id = $entity->add([
         'name'        => 'ent1.1',
         'entities_id' => $entity1Id,
         'comment'     => ''
        ]);
        $this->assertNotFalse($entity11Id);

       // Create computers + agents
        $input = [
          'entities_id' => 0,
          'name'        => 'computer1'
        ];
        $computers_id = $computer->add($input);
        $this->assertNotFalse($computers_id);

        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $input = [
          'entities_id' => 0,
          'name'        => 'computer1',
          'version'     => '{"INVENTORY":"v2.3.11"}',
          'deviceid'    => 'computer1',
          'useragent'   => 'FusionInventory-Agent_v2.3.11',
          'itemtype' => Computer::getType(),
          'items_id' => $computers_id,
          'agenttypes_id' => $agenttype['id']
        ];
        $agent1Id = $agent->add($input);
        $this->assertNotFalse($agent1Id);

       // Create Network Equipments
        $input = [
          'name'        => 'sw0',
          'entities_id' => 0,
          'snmpcredentials_id' => 2
        ];
        $netequipId = $networkEquipment->add($input);
        $this->assertNotFalse($netequipId);

        $input = [
          'entities_id'        => 0,
          'name'               => 'management',
          'items_id'           => $netequipId,
          'itemtype'           => 'NetworkEquipment',
          'instantiation_type' => 'NetworkPortAggregate',
          'NetworkName__ipaddresses' => ['-1' => '10.0.0.10']
        ];
        $networkPort->splitInputForElements($input);
        $netportId = $networkPort->add($input);
        $this->assertNotFalse($netportId);

        $networkPort->updateDependencies(true);

        $input = [
          'name'        => 'sw1',
          'entities_id' => $entity1Id,
          'snmpcredentials_id' => 2
        ];
        $netEquipId = $networkEquipment->add($input);
        $this->assertNotFalse($netEquipId);

        $input = [
          'entities_id'        => $entity1Id,
          'name'               => 'management',
          'items_id'           => $netequipId,
          'itemtype'           => 'NetworkEquipment',
          'instantiation_type' => 'NetworkPortAggregate',
          'NetworkName__ipaddresses' => ['-1' => '10.0.0.11']
        ];
        $networkPort->splitInputForElements($input);
        $netportId = $networkPort->add($input);
        $this->assertNotFalse($netportId);

        $networkPort->updateDependencies(true);

        $input = [
          'name'        => 'sw2',
          'entities_id' => $entity2Id,
          'snmpcredentials_id' => 2
        ];
        $netequipId = $networkEquipment->add($input);
        $this->assertNotFalse($netequipId);

        $input = [
          'entities_id'        => $entity2Id,
          'name'               => 'management',
          'items_id'           => $netequipId,
          'itemtype'           => 'NetworkEquipment',
          'instantiation_type' => 'NetworkPortAggregate',
          'NetworkName__ipaddresses' => ['-1' => '10.0.0.12']
        ];
        $networkPort->splitInputForElements($input);
        $netportId = $networkPort->add($input);
        $this->assertNotFalse($netportId);

        $networkPort->updateDependencies(true);

        $input = [
          'name'        => 'sw3/1.1',
          'entities_id' => $entity11Id,
          'snmpcredentials_id' => 2
        ];
        $netequipId = $networkEquipment->add($input);
        $this->assertNotFalse($netequipId);

        $input = [
          'entities_id'        => $entity11Id,
          'name'               => 'management',
          'items_id'           => $netequipId,
          'itemtype'           => 'NetworkEquipment',
          'instantiation_type' => 'NetworkPortAggregate',
          'NetworkName__ipaddresses' => ['-1' => '10.0.0.21']
        ];
        $networkPort->splitInputForElements($input);
        $netportId = $networkPort->add($input);
        $this->assertNotFalse($netportId);

        $networkPort->updateDependencies(true);

       // Create Printers

        $input = [
         'name'        => 'printer 001',
         'entities_id' => 0,
         'snmpcredentials_id' => 2
        ];
        $printers_id = $printer->add($input);
        $this->assertNotFalse($printers_id);

        $networkports_id = $networkPort->add([
          'itemtype'           => 'Printer',
          'instantiation_type' => 'NetworkPortEthernet',
          'items_id'           => $printers_id,
          'entities_id'        => 0
        ]);
        $this->assertNotFalse($networkports_id);

        $networknames_id = $networkName->add([
          'entities_id' => 0,
          'itemtype'    => 'NetworkPort',
          'items_id'    => $networkports_id
        ]);
        $this->assertNotFalse($networknames_id);

        $ipId = $iPAddress->add([
          'entities_id' => 0,
          'itemtype'    => 'NetworkName',
          'items_id'    => $networknames_id,
          'name'        => '192.168.200.124'
        ]);
        $this->assertNotFalse($ipId);

       // Add IPRange
        $input = [
          'entities_id' => 1,
          'name'        => 'Office',
          'ip_start'    => '10.0.0.1',
          'ip_end'      => '10.0.0.254'
        ];
        $ipranges_id = $pfIPRange->add($input);
        $this->assertNotFalse($ipranges_id);

       // Allow all agents to do network discovery
        $module = new PluginGlpiinventoryAgentmodule();
        $module->getFromDBByCrit(['modulename' => 'NETWORKINVENTORY']);
        $module->update([
         'id'        => $module->fields['id'],
         'is_active' => 1
        ]);

       // create task
        $input = [
          'entities_id' => 0,
          'name'        => 'network inventory',
          'is_active'   => 1
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
          'actors'                          => '[{"Agent":"' . $agent1Id . '"}]'
        ];
        $taskjobId = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobId);

        PluginGlpiinventoryTask::cronTaskscheduler();
    }


   /**
    * @test
    */
    public function prepareTask()
    {

        $pfTask  = new PluginGlpiinventoryTask();
        $agent = new Agent();

        $pfTask->getFromDBByCrit(['name' => 'network inventory']);
        $agent->getFromDBByCrit(['name' => 'computer1']);

        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $ref = [
          $agent->fields['id'] => 'computer1',
        ];

        $this->assertEquals($ref, $data['agents']);
    }


   /**
    * @test
    */
    public function getDevicesToInventory()
    {

        $pfNetworkinventory = new PluginGlpiinventoryNetworkinventory();
        $jobstate           = new PluginGlpiinventoryTaskjobstate();
        $jobstate->getFromDBByCrit(['itemtype' => 'NetworkEquipment']);
        $data = $pfNetworkinventory->run($jobstate);

        $this->assertEquals('NETWORKING', $data['OPTION']['DEVICE']['attributes']['TYPE']);
        $this->assertEquals('10.0.0.10', $data['OPTION']['DEVICE']['attributes']['IP']);
        $this->assertEquals('2', $data['OPTION']['DEVICE']['attributes']['AUTHSNMP_ID']);
        $this->assertGreaterThan(0, intval($data['OPTION']['DEVICE']['attributes']['ID']));
    }


   /**
    * @test
    */
    public function PrinterToInventoryWithIp()
    {

        $printer       = new Printer();
        $pfTask        = new PluginGlpiinventoryTask();
        $pfTaskjob     = new PluginGlpiinventoryTaskjob();
        $agent         = new Agent();
        $communication = new PluginGlpiinventoryCommunication();
        $jobstate      = new PluginGlpiinventoryTaskjobstate();

        $this->assertTrue($printer->getFromDBByCrit(['name' => 'printer 001']));
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer1']));
        $this->assertTrue($agent->update(['id' => $agent->fields['id'], 'threads_networkinventory' => 10]));

       // Add task
       // create task
        $input = [
          'entities_id' => 0,
          'name'        => 'printer inventory',
          'is_active'   => 1
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
          'actors'                          => '[{"Agent":"' . $agent->fields['id'] . '"}]'
        ];
        $taskjobId = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobId);

        PluginGlpiinventoryTask::cronTaskscheduler();

       // Task is prepared
       // Agent will get data

        $message = $communication->getTaskAgent($agent->fields['id']);
        $jobstate->getFromDBByCrit(['itemtype' => 'Printer']);

        $ref = [
         'OPTION' => [
            'NAME' => 'SNMPQUERY',
            'PARAM' => [
               'content' => '',
               'attributes' => [
                  'THREADS_QUERY' => '10',
                  'TIMEOUT'       => '15',
                  'PID'           => $jobstate->fields['id']
               ]
            ],
            'DEVICE' => [
               'content' => '',
               'attributes' => [
                  'TYPE'        => 'PRINTER',
                  'ID'          => $printer->fields['id'],
                  'IP'          => '192.168.200.124',
                  'AUTHSNMP_ID' => 2
               ]
            ], [
               'AUTHENTICATION' => [
                  'ID'        => 1,
                  'VERSION'   => 1,
                  'COMMUNITY' => 'public'
               ]
            ], [
               'AUTHENTICATION' => [
                  'ID'        => 2,
                  'VERSION'   => '2c',
                  'COMMUNITY' => 'public'
               ]
            ]
         ]
        ];

        $this->assertEquals([$ref], $message, 'XML of SNMP inventory task');
    }


   /**
    * @test
    */
    public function PrinterToInventoryWithoutIp()
    {

        $printer       = new Printer();
        $pfTask        = new PluginGlpiinventoryTask();
        $pfTaskjob     = new PluginGlpiinventoryTaskjob();
        $agent         = new Agent();
        $communication = new PluginGlpiinventoryCommunication();
        $iPAddress     = new IPAddress();

       // Delete all tasks
        $pfTask = new PluginGlpiinventoryTask();
        $items = $pfTask->find();
        foreach ($items as $item) {
            $pfTask->delete(['id' => $item['id']], true);
        }

       // Delete ipaddress of the printer
        $iPAddress->getFromDBByCrit(['name' => '192.168.200.124']);
        $iPAddress->delete(['id' => $iPAddress->fields['id']]);

        $printer->getFromDBByCrit(['name' => 'printer 001']);
        $agent->getFromDBByCrit(['name' => 'computer1']);

       // Add task
       // create task
        $input = [
          'entities_id' => 0,
          'name'        => 'network inventory',
          'is_active'   => 1
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
          'actors'                          => '[{"Agent":"' . $agent->fields['id'] . '"}]'
        ];
        $pfTaskjob->add($input);

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
