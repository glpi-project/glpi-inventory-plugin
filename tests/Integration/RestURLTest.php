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

class RestURLTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {

       // Delete all entities except root entity
        $entity = new Entity();
        $items = $entity->find();
        foreach ($items as $item) {
            if ($item['id'] > 0) {
                $entity->delete(['id' => $item['id']], true);
            }
        }

       // Delete all agents
        $agent = new Agent();
        $items = $agent->find();
        foreach ($items as $item) {
            $agent->delete(['id' => $item['id']], true);
        }
    }


   /**
    * @test
    */
    public function prepareDb()
    {
        global $DB;

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $entity   = new Entity();
        $agent  = new Agent();
        $config   = new PluginGlpiinventoryConfig();

        $entityId = $entity->add([
         'name'        => 'ent1',
         'entities_id' => 0,
         'comment'     => '',
         'agent_base_url' => 'http://10.0.2.2/glpi085'
        ]);
        $this->assertNotFalse($entityId);

        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $input = [
         'name'        => 'toto',
         'entities_id' => $entityId,
         'deviceid'   => 'toto-device',
         'agenttypes_id' => $agenttype['id'],
         'itemtype' => '',
         'items_id' => 0
        ];
        $agents_id = $agent->add($input);
        $this->assertNotFalse($agents_id);

        $config->loadCache();

        $this->assertTrue($entity->getFromDBByCrit(['id' => 0]));
        $input = [
         'id'             => $entity->fields['id'],
         'agent_base_url' => 'http://127.0.0.1/glpi085'
        ];
        $ret = $entity->update($input);
        $this->assertTrue($ret);

       // active all modules
        $query = "UPDATE `glpi_plugin_glpiinventory_agentmodules`"
              . " SET `is_active`='1'";
        $DB->query($query);
    }


   /**
    * @test
    */
    public function getCollectUrlEnt1Entity()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $agent  = new Agent();

        $agent->getFromDBByCrit(['name' => 'toto']);
        $input = [
         'itemtype' => 'PluginGlpiinventoryCollect',
         'agents_id' => $agent->fields['id']
        ];
        $ret = $pfTaskjobstate->add($input);
        $this->assertNotFalse($ret);

       // Get answer
        $input = [
          'action'    => 'getConfig',
          'task'      => ['COLLECT' => '1.0.0'],
          'machineid' => 'toto-device'
        ];

        $response = PluginGlpiinventoryCommunicationRest::communicate($input);

        $this->assertEquals(
            'http://10.0.2.2/glpi085/plugins/glpiinventory/b/collect/',
            $response['schedule'][0]['remote'],
            'Wrong URL'
        );
    }


   /**
    * @test
    */
    public function getDeployUrlRootEntity()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $agent  = new Agent();

        $agent->getFromDBByCrit(['name' => 'toto']);
        $input = [
         'itemtype' => 'PluginGlpiinventoryDeployPackage',
         'agents_id' => $agent->fields['id']
        ];
        $pfTaskjobstate->add($input);

       // Get answer
        $input = [
          'action'    => 'getConfig',
          'task'      => ['Deploy' => '1.0.0'],
          'machineid' => 'toto-device'
        ];

        $response = PluginGlpiinventoryCommunicationRest::communicate($input);

        $this->assertEquals(
            'http://10.0.2.2/glpi085/plugins/glpiinventory/b/deploy/',
            $response['schedule'][0]['remote'],
            'Wrong URL'
        );
    }


   /**
    * @test
    */
    public function getEsxUrlRootEntity()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $agent  = new Agent();

        $agent->getFromDBByCrit(['name' => 'toto']);
        $input = [
         'itemtype' => 'PluginGlpiinventoryCredentialIp',
         'agents_id' => $agent->fields['id']
        ];
        $pfTaskjobstate->add($input);

       // Get answer
        $input = [
          'action'    => 'getConfig',
          'task'      => ['ESX' => '1.0.0'],
          'machineid' => 'toto-device'
        ];

        $response = PluginGlpiinventoryCommunicationRest::communicate($input);

        $this->assertEquals(
            'http://10.0.2.2/glpi085/plugins/glpiinventory/b/esx/',
            $response['schedule'][0]['remote'],
            'Wrong URL'
        );
    }


   /**
    * @test
    */
    public function getCollectUrlRootEntity()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $config = new PluginGlpiinventoryConfig();
        $config->loadCache();

        $entity = new Entity();
        $entity->getFromDBByCrit(['name' => 'ent1']);
        $this->assertArrayHasKey('id', $entity->fields);

        $this->assertTrue($entity->update(['id' => $entity->fields['id'], 'agent_base_url' => '']));

       // Get answer
        $input = [
          'action'    => 'getConfig',
          'task'      => ['COLLECT' => '1.0.0'],
          'machineid' => 'toto-device'
        ];

        $response = PluginGlpiinventoryCommunicationRest::communicate($input);
        $this->assertEquals(
            'http://127.0.0.1/glpi085/plugins/glpiinventory/b/collect/',
            $response['schedule'][0]['remote'],
            'Wrong URL'
        );
    }
}
