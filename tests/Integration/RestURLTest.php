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

class RestURLTest extends DbTestCase
{
    private int $entities_id;

    private function prepareDb()
    {
        global $DB;

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';
        $_SESSION['glpiactiveentities_string'] = "'0'";
        $_SESSION['glpiID'] = 2; // admin user

        $entity = new Entity();
        $agent  = new Agent();
        $config   = new PluginGlpiinventoryConfig();

        $result = $DB->request([
            'SELECT' => [
                new QueryExpression(QueryFunction::max('id') . '+1', 'newID'),
            ],
            'FROM'   => Entity::getTable(),
        ])->current();
        $this->entities_id = $result['newID'];

        $DB->insert(
            Entity::getTable(),
            [
                'id' => $this->entities_id,
                'name' => 'ent1',
                'entities_id' => 0,
                'comment' => '',
                'agent_base_url' => 'http://10.0.2.2/glpi085',
            ]
        );
        $this->assertTrue($entity->getFromDB($this->entities_id));
        $this->assertSame('http://10.0.2.2/glpi085', $entity->fields['agent_base_url'], 'Entity has not been created');

        $agenttype = $DB->request(['FROM' => AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $input = [
            'name'        => 'toto',
            'entities_id' => $this->entities_id,
            'deviceid'   => 'toto-device',
            'agenttypes_id' => $agenttype['id'],
            'itemtype' => '',
            'items_id' => 0,
            'use_module_collect_data' => 1,
            'use_module_package_deployment' => 1,
            'use_module_esx_remote_inventory' => 1,
        ];
        $agents_id = $agent->add($input);
        $this->assertNotFalse($agents_id);

        $config->loadCache();

        $this->assertTrue(
            $DB->update(
                Entity::getTable(),
                ['agent_base_url' => 'http://127.0.0.1/glpi085'],
                ['id' => 0]
            )
        );
        $this->assertTrue($entity->getFromDB(0));
        $this->assertSame('http://127.0.0.1/glpi085', $entity->fields['agent_base_url'], 'Entity has not been updated');

        // active all modules
        $this->assertTrue(
            $DB->update(
                'glpi_plugin_glpiinventory_agentmodules',
                ['is_active' => 1],
                [new QueryExpression("1=1")]
            )
        );
    }

    public function testGetCollectUrlEnt1Entity()
    {
        $this->prepareDb();
        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $agent  = new Agent();

        $this->assertTrue($agent->getFromDBByCrit(['name' => 'toto']));
        $input = [
            'itemtype' => PluginGlpiinventoryCollect::class,
            'agents_id' => $agent->fields['id'],
        ];
        $ret = $pfTaskjobstate->add($input);
        $this->assertNotFalse($ret);

        // Get answer
        $input = [
            'action'    => 'getConfig',
            'task'      => ['COLLECT' => '1.0.0'],
            'machineid' => 'toto-device',
        ];

        $response = PluginGlpiinventoryCommunicationRest::communicate($input);

        $this->assertEquals(
            'http://10.0.2.2/glpi085/plugins/glpiinventory/b/collect/',
            $response['schedule'][0]['remote'],
            'Wrong URL'
        );
    }

    public function testGetDeployUrlRootEntity()
    {
        $this->prepareDb();
        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $agent  = new Agent();

        $this->assertTrue($agent->getFromDBByCrit(['name' => 'toto']));
        $input = [
            'itemtype' => PluginGlpiinventoryDeployPackage::class,
            'agents_id' => $agent->fields['id'],
        ];
        $this->assertNotFalse($pfTaskjobstate->add($input));

        // Get answer
        $input = [
            'action'    => 'getConfig',
            'task'      => ['Deploy' => '1.0.0'],
            'machineid' => 'toto-device',
        ];

        $response = PluginGlpiinventoryCommunicationRest::communicate($input);

        $this->assertEquals(
            'http://10.0.2.2/glpi085/plugins/glpiinventory/b/deploy/',
            $response['schedule'][0]['remote'],
            'Wrong URL'
        );
    }

    public function testGetEsxUrlRootEntity()
    {
        $this->prepareDb();
        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $agent  = new Agent();

        $this->assertTrue($agent->getFromDBByCrit(['name' => 'toto']));
        $input = [
            'itemtype' => PluginGlpiinventoryCredentialIp::class,
            'agents_id' => $agent->fields['id'],
        ];
        $this->assertNotFalse($pfTaskjobstate->add($input));

        // Get answer
        $input = [
            'action'    => 'getConfig',
            'task'      => ['ESX' => '1.0.0'],
            'machineid' => 'toto-device',
        ];

        $response = PluginGlpiinventoryCommunicationRest::communicate($input);

        $this->assertEquals(
            'http://10.0.2.2/glpi085/plugins/glpiinventory/b/esx/',
            $response['schedule'][0]['remote'],
            'Wrong URL'
        );
    }

    public function testGetCollectUrlRootEntity()
    {
        global $DB;

        $this->prepareDb();
        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $config = new PluginGlpiinventoryConfig();
        $config->loadCache();

        $entity = new Entity();
        $this->assertTrue($entity->getFromDBByCrit(['name' => 'ent1']));
        $this->assertArrayHasKey('id', $entity->fields);

        $this->assertTrue(
            $DB->update(
                Entity::getTable(),
                ['agent_base_url' => ''],
                ['id' => $entity->fields['id']]
            )
        );

        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $agent  = new Agent();

        $this->assertTrue($agent->getFromDBByCrit(['name' => 'toto']));
        $input = [
            'itemtype' => PluginGlpiinventoryCollect::class,
            'agents_id' => $agent->fields['id'],
        ];
        $this->assertNotFalse($pfTaskjobstate->add($input));

        // Get answer
        $input = [
            'action'    => 'getConfig',
            'task'      => ['COLLECT' => '1.0.0'],
            'machineid' => 'toto-device',
        ];

        $response = PluginGlpiinventoryCommunicationRest::communicate($input);
        $this->assertEquals(
            'http://127.0.0.1/glpi085/plugins/glpiinventory/b/collect/',
            $response['schedule'][0]['remote'],
            'Wrong URL'
        );
    }
}
