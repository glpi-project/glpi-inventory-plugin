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

use Glpi\Tests\DbTestCase;

class NetworkDiscoveryTest extends DbTestCase
{
    /**
     * Create a test computer and associated agent
     *
     * @return int computer id
     */
    private function createComputer(string $name): int
    {
        // create computer
        $computer = new Computer();
        $computers_id = $computer->add([
            'entities_id' => 0,
            'name'        => $name,
        ]);
        $this->assertNotFalse($computers_id);

        // create related agent
        $agent = new Agent();
        $this->assertNotFalse(
            $agent->add(
                [
                    'entities_id' => 0,
                    'name' => $name,
                    'version' => '{"INVENTORY":"v1.0.0"}',
                    'deviceid' => $name,
                    'useragent' => 'GLPI-Agent_v1.0.0',
                    'itemtype' => Computer::class,
                    'items_id' => $computers_id,
                    'agenttypes_id' => getItemByTypeName(AgentType::class, 'Core', true),
                ]
            )
        );

        return $computers_id;
    }

    private function prepareDb(): void
    {
        $pfTask          = new PluginGlpiinventoryTask();
        $pfTaskjob       = new PluginGlpiinventoryTaskjob();
        $pfIPRange       = new PluginGlpiinventoryIPRange();

        // Create computers + agents
        $this->createComputer('computer1');
        $this->createComputer('computer2');
        $this->createComputer('computer3');

        // Add IPRange
        $input = [
            'entities_id' => 0,
            'name'        => 'Office',
            'ip_start'    => '10.0.0.1',
            'ip_end'      => '10.0.0.254',
        ];
        $ipranges_id = $pfIPRange->add($input);
        $this->assertNotFalse($ipranges_id);

        $input = [
            'entities_id' => 0,
            'name'        => 'Office2',
            'ip_start'    => '10.0.2.1',
            'ip_end'      => '10.0.2.254',
        ];
        $ipranges_id2 = $pfIPRange->add($input);
        $this->assertNotFalse($ipranges_id2);

        // Allow all agents to do network discovery
        $module = new PluginGlpiinventoryAgentmodule();
        $this->assertTrue($module->getFromDBByCrit(['modulename' => 'NETWORKDISCOVERY']));
        $this->assertTrue(
            $module->update([
                'id'        => $module->fields['id'],
                'is_active' => 1,
            ])
        );

        // create task
        $input = [
            'entities_id' => 0,
            'name'        => 'network discovery',
            'is_active'   => 1,
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        // create taskjob
        $input = [
            'plugin_glpiinventory_tasks_id' => $tasks_id,
            'entities_id' => 0,
            'name' => 'discovery',
            'method' => 'networkdiscovery',
            'targets' => '[{"PluginGlpiinventoryIPRange":"' . $ipranges_id . '"}]',
            'actors' => '[{"Agent":"' . getItemByTypeName(Agent::class, 'computer2', true) . '"}]',
        ];
        $taskjobId = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobId);

        // create task
        $input = [
            'entities_id' => 0,
            'name' => 'network discovery2',
            'is_active' => 1,
        ];
        $tasks2_id = $pfTask->add($input);
        $this->assertNotFalse($tasks2_id);

        // create taskjob
        $input = [
            'plugin_glpiinventory_tasks_id' => $tasks2_id,
            'entities_id' => 0,
            'name' => 'discovery',
            'method' => 'networkdiscovery',
            'targets' => '[{"PluginGlpiinventoryIPRange":"' . $ipranges_id2 . '"}]',
            'actors' => '[{"Agent":"' . getItemByTypeName(Agent::class, 'computer3', true) . '"}]',
        ];
        $taskjobId = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobId);

        PluginGlpiinventoryTask::cronTaskscheduler();
    }


    public function testPrepareTask()
    {
        $this->prepareDb();
        $pfTask  = new PluginGlpiinventoryTask();
        $agent = new Agent();

        $this->assertTrue($pfTask->getFromDBByCrit(['name' => 'network discovery']));
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer2']));

        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $ref = [$agent->fields['id'] => 'computer2',];
        $this->assertEquals($ref, $data['agents']);
    }


    public function testPrepareTask2()
    {
        $this->prepareDb();
        $pfTask = new PluginGlpiinventoryTask();
        $agent = new Agent();

        $this->assertTrue($pfTask->getFromDBByCrit(['name' => 'network discovery2']));
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer3']));

        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $ref = [$agent->fields['id'] => 'computer3'];
        $this->assertEquals($ref, $data['agents']);
    }
}
