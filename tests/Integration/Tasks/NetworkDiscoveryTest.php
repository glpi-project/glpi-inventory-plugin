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

use PHPUnit\Framework\TestCase;

class NetworkDiscoveryTest extends TestCase
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
    }

   /**
    * @test
    */
    public function prepareDb()
    {
        global $DB;

        $computer        = new Computer();
        $agent           = new Agent();
        $pfTask          = new PluginGlpiinventoryTask();
        $pfTaskjob       = new PluginGlpiinventoryTaskjob();
        $pfIPRange       = new PluginGlpiinventoryIPRange();

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
          'deviceid'   => 'computer1',
          'useragent'   => 'FusionInventory-Agent_v2.3.11',
          'itemtype' => Computer::getType(),
          'items_id' => $computers_id,
         'agenttypes_id' => $agenttype['id']
        ];
        $agentId = $agent->add($input);
        $this->assertNotFalse($agentId);

        $input = [
          'entities_id' => 0,
          'name'        => 'computer2'
        ];
        $computers_id = $computer->add($input);
        $this->assertNotFalse($computers_id);

        $input = [
          'entities_id' => 0,
          'name'        => 'computer2',
          'version'     => '{"INVENTORY":"v2.3.11"}',
          'deviceid'    => 'computer2',
          'useragent'   => 'FusionInventory-Agent_v2.3.11',
          'itemtype' => Computer::getType(),
          'items_id' => $computers_id,
          'agenttypes_id' => $agenttype['id']
        ];
        $agent2Id = $agent->add($input);
        $this->assertNotFalse($agent2Id);

        $input = [
          'entities_id' => 0,
          'name'        => 'computer3'
        ];
        $computers_id = $computer->add($input);
        $this->assertNotFalse($computers_id);

        $input = [
          'entities_id' => 0,
          'name'        => 'computer3',
          'version'     => '{"INVENTORY":"v2.3.11"}',
          'deviceid'   => 'computer3',
          'useragent'   => 'FusionInventory-Agent_v2.3.11',
          'itemtype' => Computer::getType(),
          'items_id' => $computers_id,
          'agenttypes_id' => $agenttype['id']
        ];
        $agent3Id = $agent->add($input);
        $this->assertNotFalse($agent3Id);

       // Add IPRange
        $input = [
          'entities_id' => 0,
          'name'        => 'Office',
          'ip_start'    => '10.0.0.1',
          'ip_end'      => '10.0.0.254'
        ];
        $ipranges_id = $pfIPRange->add($input);
        $this->assertNotFalse($ipranges_id);

        $input = [
          'entities_id' => 0,
          'name'        => 'Office2',
          'ip_start'    => '10.0.2.1',
          'ip_end'      => '10.0.2.254'
        ];
        $ipranges_id2 = $pfIPRange->add($input);
        $this->assertNotFalse($ipranges_id2);

       // Allow all agents to do network discovery
        $module = new PluginGlpiinventoryAgentmodule();
        $module->getFromDBByCrit(['modulename' => 'NETWORKDISCOVERY']);
        $module->update([
         'id'        => $module->fields['id'],
         'is_active' => 1
        ]);

       // create task
        $input = [
          'entities_id' => 0,
          'name'        => 'network discovery',
          'is_active'   => 1
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

       // create taskjob
        $input = [
          'plugin_glpiinventory_tasks_id' => $tasks_id,
          'entities_id'                     => 0,
          'name'                            => 'discovery',
          'method'                          => 'networkdiscovery',
          'targets'                         => '[{"PluginGlpiinventoryIPRange":"' . $ipranges_id . '"}]',
          'actors'                          => '[{"Agent":"' . $agent2Id . '"}]'
        ];
        $taskjobId = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobId);

       // create task
        $input = [
          'entities_id' => 0,
          'name'        => 'network discovery2',
          'is_active'   => 1
        ];
        $tasks2_id = $pfTask->add($input);
        $this->assertNotFalse($tasks2_id);

       // create taskjob
        $input = [
          'plugin_glpiinventory_tasks_id' => $tasks2_id,
          'entities_id'                     => 0,
          'name'                            => 'discovery',
          'method'                          => 'networkdiscovery',
          'targets'                         => '[{"PluginGlpiinventoryIPRange":"' . $ipranges_id2 . '"}]',
          'actors'                          => '[{"Agent":"' . $agent3Id . '"}]'
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

        $pfTask->getFromDBByCrit(['name' => 'network discovery']);
        $agent->getFromDBByCrit(['name' => 'computer2']);

        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $ref = [
         $agent->fields['id'] => 'computer2',
        ];

        $this->assertEquals($ref, $data['agents']);
    }


   /**
    * @test
    */
    public function prepareTask2()
    {
        $pfTask = new PluginGlpiinventoryTask();
        $agent = new Agent();

        $pfTask->getFromDBByCrit(['name' => 'network discovery2']);
        $agent->getFromDBByCrit(['name' => 'computer3']);

        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $ref = [
         $agent->fields['id'] => 'computer3',
        ];

        $this->assertEquals($ref, $data['agents']);
    }
}
