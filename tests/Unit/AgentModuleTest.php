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

class AgentModuleTest extends DbTestCase
{
    private array $modules = [
        'INVENTORY' => ['is_active' => true],
        'InventoryComputerESX' => ['is_active' => false],
        'NETWORKINVENTORY' => ['is_active' => false],
        'NETWORKDISCOVERY' => ['is_active' => false],
        'DEPLOY' => ['is_active' => true],
        'Collect' => ['is_active' => true],
    ];

    public function testGetModules(): void
    {
        $agent_modules = new PluginGlpiinventoryAgentmodule();
        $modules = $agent_modules->getModules();
        $this->assertSame(array_keys($this->modules), $modules);
    }

    public function testGetModulesList(): void
    {
        $agent_modules = new PluginGlpiinventoryAgentmodule();

        $list = $agent_modules->getModulesList();
        $this->assertCount(count($this->modules), $list);
        foreach ($list as $module) {
            $this->assertSame([], $module['exceptions']);
        }

        $this->testUpdateForAgent();
        $list = $agent_modules->getModulesList(); //add exceptions for "networkinventory" and "deploy" for current agent
        foreach ($list as $module) {
            if ($module['id'] == 'networkinventory' || $module['id'] == 'deploy') {
                $this->assertCount(1, $module['exceptions']);
            } else {
                $this->assertCount(0, $module['exceptions']);
            }
        }
    }

    public function testUpdateModules(): void
    {
        //an agent to add exception
        $agent = $this->createItem(
            Agent::class,
            [
                'name' => 'test agent',
                'deviceid' => 'foobar',
                'agenttypes_id' => 0,
                'itemtype' => Computer::class,
                'items_id' => 0,
            ]
        );

        $agent_modules = new PluginGlpiinventoryAgentmodule();
        $input = ['update' => true];
        foreach ($this->modules as $name => $conf) {
            $this->assertTrue($agent_modules->getFromDBByCrit(['modulename' => $name] + $conf));
            $this->assertSame(exportArrayToDB([]), $agent_modules->fields['exceptions']); // check no exceptions exist
            $input[strtolower($name) . '_is_active'] = $conf ['is_active'];
        }

        //add exception on 'DEPLOY'
        $input['deploy_exceptions'] = [$agent->getID()];

        $agent_modules->updateModules($input);

        foreach ($this->modules as $name => $conf) {
            $this->assertTrue($agent_modules->getFromDBByCrit(['modulename' => $name] + $conf));
            $exceptions = $name === 'DEPLOY' ? [$agent->getID()] : []; // check exception on DEPLOY only exist
            $this->assertSame(exportArrayToDB($exceptions), $agent_modules->fields['exceptions']);
        }
    }

    public function testUpdateForAgent(): void
    {
        //an agent to rule them all
        $agent = $this->createItem(
            Agent::class,
            [
                'name' => 'test agent',
                'deviceid' => 'foobar',
                'agenttypes_id' => 0,
                'itemtype' => Computer::class,
                'items_id' => 0,
            ]
        );

        $agent_modules = new PluginGlpiinventoryAgentmodule();
        $input = [
            'update_exceptions' => true,
            'agents_id' => $agent->getID(),
        ];
        foreach ($this->modules as $name => $conf) {
            $this->assertTrue($agent_modules->getFromDBByCrit(['modulename' => $name] + $conf));
            $this->assertSame(exportArrayToDB([]), $agent_modules->fields['exceptions']); // check no exceptions exist
            $input[strtolower($name) . '_is_active'] = $conf ['is_active'];
        }

        // agent active on active module => no change
        $input['inventory_is_active'] = 1;
        // agent inactive on inactive module => no change
        $input['inventorycomputeresx_is_active'] = 0;
        // agent active on inactive module => exception
        $input['networkinventory_is_active'] = 1;
        // agent inactive on active module => exception
        $input['deploy_is_active'] = 0;

        $agent_modules->updateForAgent($input);

        foreach ($this->modules as $name => $conf) {
            $this->assertTrue($agent_modules->getFromDBByCrit(['modulename' => $name] + $conf));

            $exceptions = [];
            switch ($name) {
                case 'NETWORKINVENTORY':
                case 'DEPLOY':
                    $exceptions = [$agent->getID()];
            }
            $this->assertSame(
                exportArrayToDB($exceptions),
                $agent_modules->fields['exceptions'],
                sprintf(
                    'Module "%s" does not have exceptions ("%s")',
                    $name,
                    implode(', ', $exceptions)
                )
            );

        }
    }
}
