<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

class NetworkDiscoveryTest extends TestCase {

   public static function setUpBeforeClass(): void {

      // Delete all computers
      $computer = new Computer();
      $items = $computer->find();
      foreach ($items as $item) {
         $computer->delete(['id' => $item['id']], true);
      }

      // Delete all agents
      $pfAgent = new PluginGlpiinventoryAgent();
      $items = $pfAgent->find();
      foreach ($items as $item) {
         $pfAgent->delete(['id' => $item['id']], true);
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
   public function prepareDb() {

      $computer        = new Computer();
      $pfAgent         = new PluginGlpiinventoryAgent();
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

      $input = [
          'entities_id' => 0,
          'name'        => 'computer1',
          'version'     => '{"INVENTORY":"v2.3.11"}',
          'device_id'   => 'computer1',
          'useragent'   => 'FusionInventory-Agent_v2.3.11',
          'computers_id'=> $computers_id
      ];
      $agentId = $pfAgent->add($input);
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
          'device_id'   => 'computer2',
          'useragent'   => 'FusionInventory-Agent_v2.3.11',
          'computers_id'=> $computers_id
      ];
      $agent2Id = $pfAgent->add($input);
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
          'device_id'   => 'computer3',
          'useragent'   => 'FusionInventory-Agent_v2.3.11',
          'computers_id'=> $computers_id
      ];
      $agent3Id = $pfAgent->add($input);
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
          'targets'                         => '[{"PluginGlpiinventoryIPRange":"'.$ipranges_id.'"}]',
          'actors'                          => '[{"PluginGlpiinventoryAgent":"'.$agent2Id.'"}]'
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
          'targets'                         => '[{"PluginGlpiinventoryIPRange":"'.$ipranges_id2.'"}]',
          'actors'                          => '[{"PluginGlpiinventoryAgent":"'.$agent3Id.'"}]'
      ];
      $taskjobId = $pfTaskjob->add($input);
      $this->assertNotFalse($taskjobId);

      PluginGlpiinventoryTask::cronTaskscheduler();
   }


   /**
    * @test
    */
   public function prepareTask() {
      $pfTask  = new PluginGlpiinventoryTask();
      $pfAgent = new PluginGlpiinventoryAgent();

      $pfTask->getFromDBByCrit(['name' => 'network discovery']);
      $pfAgent->getFromDBByCrit(['name' => 'computer2']);

      $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

      $ref = [
         $pfAgent->fields['id'] => 'computer2',
      ];

      $this->assertEquals($ref, $data['agents']);
   }


   /**
    * @test
    */
   public function prepareTask2() {
      $pfTask = new PluginGlpiinventoryTask();
      $pfAgent = new PluginGlpiinventoryAgent();

      $pfTask->getFromDBByCrit(['name' => 'network discovery2']);
      $pfAgent->getFromDBByCrit(['name' => 'computer3']);

      $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

      $ref = [
         $pfAgent->fields['id'] => 'computer3',
      ];

      $this->assertEquals($ref, $data['agents']);
   }
}
