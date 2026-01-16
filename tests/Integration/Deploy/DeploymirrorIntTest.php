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

class DeploymirrorIntTest extends DbTestCase
{
    private string $serverUrl = 'http://localhost:8080/glpi/plugins/glpiinventory/b/deploy/?action=getFilePart&file=';

    private function defineConfiguration(): void
    {
        global $DB;

        //reset config
        PluginGlpiinventoryConfig::loadCache();

        $_SESSION['glpiactiveentities_string'] = 0;
        $entity   = new Entity();

        $result = $DB->request([
            'SELECT' => [
                new QueryExpression(QueryFunction::max('id') . '+1', 'newID'),
            ],
            'FROM'   => Entity::getTable(),
        ])->current();
        $entities_id = $result['newID'];

        $DB->insert(
            Entity::getTable(),
            [
                'id' => $entities_id,
                'name'        => 'entity A',
                'entities_id' => 0,
                'comment'     => '',
                'tag'         => 'entA',
                'agent_base_url' => 'http://localhost:8080/glpi',
                'transfers_id' => 0,
            ]
        );
        $entityAId = $DB->insertId();
        $this->assertTrue($entity->getFromDB($entities_id));
        $this->assertSame('entity A', $entity->fields['name'], 'Entity has not been created');

        ++$entities_id;
        $DB->insert(
            Entity::getTable(),
            [
                'id' => $entities_id,
                'name'        => 'entity B',
                'entities_id' => 0,
                'comment'     => '',
                'tag'         => 'entB',
                'agent_base_url' => 'http://localhost:8080/glpi',
                'transfers_id' => 0,
            ]
        );
        $entityBId = $DB->insertId();
        $this->assertTrue($entity->getFromDB($entities_id));
        $this->assertSame('entity B', $entity->fields['name'], 'Entity has not been created');

        $DB->update(
            Entity::getTable(),
            ['agent_base_url' => 'http://localhost:8080/glpi'],
            ['id' => 0]
        );

        $_SESSION['glpiactive_entity_recursive'] = 1;
        $_SESSION['glpishowallentities']         = 1;

        $location = new Location();
        $ret = $location->getFromDBByCrit(['name' => 'MyLocation']);
        if (!$ret) {
            $locations_id = $location->add([
                'name'         => 'MyLocation',
                'entities_id'  => 0,
                'is_recursive' => 1,
            ]);
            $this->assertNotFalse($locations_id);
        } else {
            $locations_id = $location->fields['id'];
        }

        $pfDeploymirror = new PluginGlpiinventoryDeployMirror();
        $input = [
            'name'         => 'Mirror Location',
            'comment'      => 'MyComment',
            'url'          => 'http://localhost:8085/mirror',
            'entities_id'  => 0,
            'locations_id' => $locations_id,
            'is_active'    => 0,
            'is_recursive' => 1,
        ];
        $mirrors_locations_id = $pfDeploymirror->add($input);
        $this->assertNotFalse($mirrors_locations_id);

        $pfDeploymirror = new PluginGlpiinventoryDeployMirror();
        $input = [
            'name'         => 'Mirror Entity A',
            'comment'      => 'MyComment',
            'url'          => 'http://localhost:8088/mirror',
            'entities_id'  => $entityAId,
            'locations_id' => $locations_id,
            'is_active'    => 1,
        ];
        $mirrors2_id = $pfDeploymirror->add($input);
        $this->assertNotFalse($mirrors2_id);

        $computer = new Computer();
        $agent    = new Agent();

        $computerRootId = $computer->add([
            'name'         => 'computer root',
            'serial'       => 'abcd',
            'entities_id'  => 0,
            'is_recursive' => 0,
        ]);
        $this->assertNotFalse($computerRootId);

        $agenttype = $DB->request(['FROM' => AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agentRootId = $agent->add([
            'name'         => 'computer-root',
            'itemtype' => Computer::class,
            'items_id' => $computerRootId,
            'entities_id'  => 0,
            'agenttypes_id' => $agenttype['id'],
            'deviceid' => 'computer-root',
        ]);
        $this->assertNotFalse($agentRootId);

        $computerEntAId = $computer->add([
            'name'         => 'computer EntityA',
            'serial'       => 'abce',
            'entities_id'  => $entityAId,
            'is_recursive' => 0,
            'locations_id' => $locations_id,
        ]);
        $this->assertNotFalse($computerEntAId);
        $agentEntAId = $agent->add([
            'name'         => 'computer-EntityA',
            'itemtype' => Computer::class,
            'items_id' => $computerEntAId,
            'entities_id'  => $entityAId,
            'agenttypes_id' => $agenttype['id'],
            'deviceid' => 'computer-EntityA',
        ]);
        $this->assertNotFalse($agentEntAId);

        $computerEntBId = $computer->add([
            'name'         => 'computer EntityB',
            'serial'       => 'abcf',
            'entities_id'  => $entityBId,
            'is_recursive' => 0,
            'locations_id' => $locations_id,
        ]);
        $this->assertNotFalse($computerEntBId);
        $agentEntBId = $agent->add([
            'name'         => 'computer-EntityB',
            'itemtype' => Computer::class,
            'items_id' => $computerEntBId,
            'entities_id'  => $entityBId,
            'agenttypes_id' => $agenttype['id'],
            'deviceid' => 'computer-EntityB',
        ]);
        $this->assertNotFalse($agentEntBId);
    }

    public function testEntitiesMirrorDisabled(): void
    {
        /** @var array<string, string|int> $PF_CONFIG */
        global $PF_CONFIG;

        $this->defineConfiguration();

        //Add the server's url at the end of the mirrors list
        $PF_CONFIG['server_as_mirror'] = true;
        $PF_CONFIG['mirror_match'] = PluginGlpiinventoryDeployMirror::MATCH_LOCATION;

        // The location mirror is disabled, so return the server's download url

        $agent = new Agent();
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer-root']));

        $mirrors = PluginGlpiinventoryDeployMirror::getList($agent->fields['id']);
        $result = [$this->serverUrl];
        $this->assertEquals($result, $mirrors);
    }

    public function testRootEntityMirrorNoLocation(): void
    {
        $this->defineConfiguration();

        //We enable the mirror
        $pfDeploymirror = new PluginGlpiinventoryDeployMirror();
        $pfDeploymirror->getFromDBByCrit(['name' => 'Mirror Location']);

        $input = [
            'id'        => $pfDeploymirror->fields['id'],
            'is_active' => 1,
        ];
        $this->assertTrue($pfDeploymirror->update($input));

        $agent = new Agent();
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer-root']));

        $mirrors = PluginGlpiinventoryDeployMirror::getList($agent->fields['id']);
        $result = [$this->serverUrl];
        $this->assertEquals($result, $mirrors);
    }

    public function testRootEntityMirrorWithLocation(): void
    {
        $this->defineConfiguration();

        $computer = new Computer();
        $location = new Location();

        $computer->getFromDBByCrit(['name' => 'computer root']);
        $location->getFromDBByCrit(['name' => 'MyLocation']);

        $ret = $computer->update([
            'id'           => $computer->fields['id'],
            'locations_id' => $location->fields['id'],
        ]);
        $this->assertNotFalse($ret);

        //In this case, the method must return the mirror location url
        $agent = new Agent();
        $agent->getFromDBByCrit(['name' => 'computer-root']);

        $mirrors = PluginGlpiinventoryDeployMirror::getList($agent->fields['id']);
        $result  = [
            "http://localhost:8088/mirror",
            $this->serverUrl,
        ];
        $this->assertEquals($result, $mirrors);
    }

    public function testEntityAMirrorWithLocation(): void
    {
        $this->defineConfiguration();
        $agent = new Agent();
        $agent->getFromDBByCrit(['name' => 'computer-EntityA']);

        $mirrors = PluginGlpiinventoryDeployMirror::getList($agent->fields['id']);
        $result  = [
            "http://localhost:8088/mirror",
            $this->serverUrl,
        ];
        $this->assertEquals($result, $mirrors);
    }

    public function testEntityBMirrorWithLocation(): void
    {
        $this->defineConfiguration();
        $agent = new Agent();
        $agent->getFromDBByCrit(['name' => 'computer-EntityB']);

        $mirrors = PluginGlpiinventoryDeployMirror::getList($agent->fields['id']);

        $result  = [
            "http://localhost:8088/mirror",
            $this->serverUrl,
        ];
        $this->assertEquals($result, $mirrors);
    }

    public function testRootEntityMirrorWithEntity(): void
    {
        global $PF_CONFIG;

        $this->defineConfiguration();

        $PF_CONFIG['mirror_match'] = PluginGlpiinventoryDeployMirror::MATCH_ENTITY;

        $agent = new Agent();
        $computer = new Computer();

        $this->assertTrue($computer->getFromDBByCrit(['name' => 'computer root']));
        $this->assertTrue(
            $computer->update([
                'id'           => $computer->fields['id'],
                'locations_id' => 0,
            ])
        );

        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer-root']));

        $mirrors = PluginGlpiinventoryDeployMirror::getList($agent->fields['id']);
        $result  = [
            'http://localhost:8088/mirror',
            $this->serverUrl,
        ];
        $this->assertEquals($result, $mirrors);
    }

    public function testEntityAMirrorWithEntity()
    {
        global $PF_CONFIG;

        $this->defineConfiguration();
        $PF_CONFIG['mirror_match'] = PluginGlpiinventoryDeployMirror::MATCH_ENTITY;

        $agent = new Agent();
        $computer = new Computer();

        $computer->getFromDBByCrit(['name' => 'computer EntityA']);
        $computer->update([
            'id'           => $computer->fields['id'],
            'locations_id' => 0,
        ]);

        $agent->getFromDBByCrit(['name' => 'computer-EntityA']);

        $mirrors = PluginGlpiinventoryDeployMirror::getList($agent->fields['id']);
        $result  = [
            "http://localhost:8088/mirror",
            $this->serverUrl,
        ];
        $this->assertEquals($result, $mirrors);
    }

    public function testEntityBMirrorWithEntity(): void
    {
        global $PF_CONFIG;

        $this->defineConfiguration();
        $PF_CONFIG['mirror_match'] = PluginGlpiinventoryDeployMirror::MATCH_ENTITY;

        $agent = new Agent();
        $computer = new Computer();

        $computer->getFromDBByCrit(['name' => 'computer EntityB']);
        $this->assertTrue(
            $computer->update([
                'id'           => $computer->fields['id'],
                'locations_id' => 0,
            ])
        );
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer-EntityB']));

        $mirrors = PluginGlpiinventoryDeployMirror::getList($agent->fields['id']);
        $result  = [
            'http://localhost:8088/mirror',
            $this->serverUrl,
        ];
        $this->assertEquals($result, $mirrors);
    }
}
