<?php
/**
 *  * ---------------------------------------------------------------------
 *  * GLPI Inventory Plugin
 *  * Copyright (C) 2021 Teclib' and contributors.
 *  *
 *  * http://glpi-project.org
 *  *
 *  * based on FusionInventory for GLPI
 *  * Copyright (C) 2010-2021 by the FusionInventory Development Team.
 *  *
 *  * ---------------------------------------------------------------------
 *  *
 *  * LICENSE
 *  *
 *  * This file is part of GLPI Inventory Plugin.
 *  *
 *  * GLPI Inventory Plugin is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as published by
 *  * the Free Software Foundation, either version 3 of the License, or
 *  * (at your option) any later version.
 *  *
 *  * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  * GNU Affero General Public License for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 *  * ---------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

class DeploymirrorIntTest extends TestCase {

   private $serverUrl = 'http://localhost:8080/glpi/plugins/glpiinventory/b/deploy/?action=getFilePart&file=';

   public static function setUpBeforeClass(): void {

      // Delete all entities exept root entity
      $entity = new Entity();
      $items = $entity->find();
      foreach ($items as $item) {
         if ($item['id'] > 0) {
            $entity->delete(['id' => $item['id']], true);
         }
      }

      // Delete all deploymirrors
      $pfDeploymirror = new PluginGlpiinventoryDeployMirror();
      $items = $pfDeploymirror->find();
      foreach ($items as $item) {
         $pfDeploymirror->delete(['id' => $item['id']], true);
      }

      // Delete all computers
      $computer = new Computer();
      $items = $computer->find();
      foreach ($items as $item) {
         $computer->delete(['id' => $item['id']], true);
      }
   }

   /**
    * @test
    */
   public function testDefineEntitiesConfiguration() {

      $entity   = new Entity();
      $pfEntity = new PluginGlpiinventoryEntity();

      $entityAId = $entity->add([
         'name'        => 'entity A',
         'entities_id' => 0,
         'comment'     => '',
         'tag'         => 'entA'
      ]);
      $this->assertNotFalse($entityAId);

      $entityBId = $entity->add([
         'name'        => 'entity B',
         'entities_id' => 0,
         'comment'     => '',
         'tag'         => 'entB'
      ]);
      $this->assertNotFalse($entityBId);

      $ret = $pfEntity->getFromDBByCrit(['entities_id' => 0]);
      if (!$ret) {
         $input = [
            'entities_id'       => 0,
            'agent_base_url'    => 'http://localhost:8080/glpi',
            'transfers_id_auto' => 0
         ];
         $ret = $pfEntity->add($input);
         $this->assertNotFalse($ret);
      } else {
         $input = [
            'id'                => $pfEntity->fields['id'],
            'agent_base_url'    => 'http://localhost:8080/glpi',
            'transfers_id_auto' => 0
         ];
         $ret = $pfEntity->update($input);
         $this->assertNotFalse($ret);
      }

      $input = [
         'entities_id'       => $entityAId,
         'agent_base_url'    => 'http://localhost:8080/glpi',
         'transfers_id_auto' => 0
      ];
      $ret = $pfEntity->add($input);
      $this->assertNotFalse($ret);

      $input = [
         'entities_id'       => $entityBId,
         'agent_base_url'    => 'http://localhost:8080/glpi',
         'transfers_id_auto' => 0
      ];
      $ret = $pfEntity->add($input);
      $this->assertNotFalse($ret);

      $_SESSION['glpiactive_entity_recursive'] = 1;
      $_SESSION['glpishowallentities']         = 1;

      $location = new Location();
      $locations_id = 0;
      $ret = $location->getFromDBByCrit(['name' => 'MyLocation']);
      if (!$ret) {
         $locations_id = $location->add([
            'name'         => 'MyLocation',
            'entities_id'  => 0,
            'is_recursive' => 1
         ]);
         $this->assertNotFalse($locations_id);
      } else {
         $locations_id = $location->fields['id'];
      }

      $entity = new Entity();

      $pfDeploymirror = new PluginGlpiinventoryDeployMirror();
      $input = [
         'name'         => 'Mirror Location',
         'comment'      => 'MyComment',
         'url'          => 'http://localhost:8085/mirror',
         'entities_id'  => 0,
         'locations_id' => $locations_id,
         'is_active'    => 0,
         'is_recursive' => 1
      ];
      $mirrors_locations_id = $pfDeploymirror->add($input);
      $this->assertNotFalse($mirrors_locations_id);

      $pfDeploymirror = new PluginGlpiinventoryDeployMirror();
      $entity->getFromDBByCrit(['name' => 'entity A']);
      $input = [
         'name'         => 'Mirror Entity A',
         'comment'      => 'MyComment',
         'url'          => 'http://localhost:8088/mirror',
         'entities_id'  => $entity->fields['id'],
         'locations_id' => $locations_id,
         'is_active'    => 1
      ];
      $mirrors2_id = $pfDeploymirror->add($input);
      $this->assertNotFalse($mirrors2_id);

      $computer = new Computer();
      $agent    = new PluginGlpiinventoryAgent();

      $computerRootId = $computer->add([
         'name'         => 'computer root',
         'serial'       => 'abcd',
         'entities_id'  => 0,
         'is_recursive' => 0,
      ]);
      $this->assertNotFalse($computerRootId);
      $agentRootId = $agent->add([
         'name'         => 'computer-root',
         'computers_id' => $computerRootId,
         'entities_id'  => 0
      ]);
      $this->assertNotFalse($agentRootId);

      $computerEntAId = $computer->add([
         'name'         => 'computer EntityA',
         'serial'       => 'abce',
         'entities_id'  => $entityAId,
         'is_recursive' => 0,
         'locations_id' => $locations_id
      ]);
      $this->assertNotFalse($computerEntAId);
      $agentEntAId = $agent->add([
         'name'         => 'computer-EntityA',
         'computers_id' => $computerEntAId,
         'entities_id'  => $entityAId
      ]);
      $this->assertNotFalse($agentEntAId);

      $computerEntBId = $computer->add([
         'name'         => 'computer EntityB',
         'serial'       => 'abcf',
         'entities_id'  => $entityBId,
         'is_recursive' => 0,
         'locations_id' => $locations_id
      ]);
      $this->assertNotFalse($computerEntBId);
      $agentEntBId = $agent->add([
         'name'         => 'computer-EntityB',
         'computers_id' => $computerEntBId,
         'entities_id'  => $entityBId
      ]);
      $this->assertNotFalse($agentEntBId);

   }


   /**
    * @test
    */
   public function testEntitiesMirorDisabled() {

      //Add the server's url at the end of the mirrors list
      $PF_CONFIG['server_as_mirror'] = true;
      $PF_CONFIG['mirror_match'] = PluginGlpiinventoryDeployMirror::MATCH_LOCATION;

      // The location mirror is disabled, so return the server's download url

      $pfAgent = new PluginGlpiinventoryAgent();
      $pfAgent->getFromDBByCrit(['name' => 'computer-root']);

      $mirrors = PluginGlpiinventoryDeployMirror::getList($pfAgent->fields['id']);
      $result = [ 0 => $this->serverUrl ];
      $this->assertEquals($result, $mirrors);

   }


   /**
    * @test
    */
   public function testRootEntityMirrorNoLocation() {

      //We enable the mirror

      $pfDeploymirror = new PluginGlpiinventoryDeployMirror();
      $pfDeploymirror->getFromDBByCrit(['name' => 'Mirror Location']);

      $input = [
         'id'        => $pfDeploymirror->fields['id'],
         'is_active' => 1
      ];
      $ret = $pfDeploymirror->update($input);
      $this->assertNotFalse($ret);

      $pfAgent = new PluginGlpiinventoryAgent();
      $pfAgent->getFromDBByCrit(['name' => 'computer-root']);

      $mirrors = PluginGlpiinventoryDeployMirror::getList($pfAgent->fields['id']);
      $result = [
         0 => $this->serverUrl
      ];
      $this->assertEquals($result, $mirrors);
   }

   /**
    * @test
    */
   public function testRootEntityMirrorWithLocation() {

      $computer = new Computer();
      $location = new Location();

      $computer->getFromDBByCrit(['name' => 'computer root']);
      $location->getFromDBByCrit(['name' => 'MyLocation']);

      $ret = $computer->update([
         'id'           => $computer->fields['id'],
         'locations_id' => $location->fields['id']
      ]);
      $this->assertNotFalse($ret);

      //In this case, the method must return the mirror location url
      $pfAgent = new PluginGlpiinventoryAgent();
      $pfAgent->getFromDBByCrit(['name' => 'computer-root']);

      $mirrors = PluginGlpiinventoryDeployMirror::getList($pfAgent->fields['id']);
      $result  = [
         0 => "http://localhost:8085/mirror",
         1 => $this->serverUrl
      ];
      $this->assertEquals($result, $mirrors);

   }

   /**
    * @test
    */
   public function testEntityAMirrorWithLocation() {

      $pfAgent = new PluginGlpiinventoryAgent();
      $pfAgent->getFromDBByCrit(['name' => 'computer-EntityA']);

      $mirrors = PluginGlpiinventoryDeployMirror::getList($pfAgent->fields['id']);
      $result  = [
         0 => "http://localhost:8088/mirror",
         1 => "http://localhost:8085/mirror",
         2 => $this->serverUrl
      ];
      $this->assertEquals($result, $mirrors);
   }


   /**
    * @test
    */
   public function testEntityBMirrorWithLocation() {

      $pfAgent = new PluginGlpiinventoryAgent();
      $pfAgent->getFromDBByCrit(['name' => 'computer-EntityB']);

      $mirrors = PluginGlpiinventoryDeployMirror::getList($pfAgent->fields['id']);

      $result  = [
         0 => "http://localhost:8085/mirror",
         1 => $this->serverUrl
      ];
      $this->assertEquals($result, $mirrors);
   }


   /**
    * @test
    */
   public function testRootEntityMirrorWithEntity() {
      global $PF_CONFIG;

      $PF_CONFIG['mirror_match'] = PluginGlpiinventoryDeployMirror::MATCH_ENTITY;

      $pfAgent = new PluginGlpiinventoryAgent();
      $computer = new Computer();

      $computer->getFromDBByCrit(['name' => 'computer root']);
      $computer->update([
         'id'           => $computer->fields['id'],
         'locations_id' => 0
      ]);

      $pfAgent->getFromDBByCrit(['name' => 'computer-root']);

      $mirrors = PluginGlpiinventoryDeployMirror::getList($pfAgent->fields['id']);
      $result  = [
         0 => 'http://localhost:8085/mirror',
         1 => $this->serverUrl
      ];
      $this->assertEquals($result, $mirrors);
   }


   /**
    * @test
    */
   public function testEntityAMirrorWithEntity() {
      global $PF_CONFIG;

      $PF_CONFIG['mirror_match'] = PluginGlpiinventoryDeployMirror::MATCH_ENTITY;

      $pfAgent = new PluginGlpiinventoryAgent();
      $computer = new Computer();

      $computer->getFromDBByCrit(['name' => 'computer EntityA']);
      $computer->update([
         'id'           => $computer->fields['id'],
         'locations_id' => 0
      ]);

      $pfAgent->getFromDBByCrit(['name' => 'computer-EntityA']);

      $mirrors = PluginGlpiinventoryDeployMirror::getList($pfAgent->fields['id']);
      $result  = [
         0 => "http://localhost:8088/mirror",
         1 => "http://localhost:8085/mirror",
         2 => $this->serverUrl
      ];
      $this->assertEquals($result, $mirrors);
   }


   /**
    * @test
    */
   public function testEntityBMirrorWithEntity() {
      global $PF_CONFIG;

      $PF_CONFIG['mirror_match'] = PluginGlpiinventoryDeployMirror::MATCH_ENTITY;

      $pfAgent = new PluginGlpiinventoryAgent();
      $computer = new Computer();

      $computer->getFromDBByCrit(['name' => 'computer EntityB']);
      $computer->update([
         'id'           => $computer->fields['id'],
         'locations_id' => 0
      ]);

      $pfAgent->getFromDBByCrit(['name' => 'computer-EntityB']);

      $mirrors = PluginGlpiinventoryDeployMirror::getList($pfAgent->fields['id']);
      $result  = [
         0 => "http://localhost:8085/mirror",
         1 => $this->serverUrl
      ];
      $this->assertEquals($result, $mirrors);
   }
}
