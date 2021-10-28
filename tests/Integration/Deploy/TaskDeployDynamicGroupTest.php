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

class TaskDeployDynamicGroupTest extends TestCase {

   public static function setUpBeforeClass(): void {

      // Delete all computers
      $computer = new Computer();
      $items = $computer->find();
      foreach ($items as $item) {
         $computer->delete(['id' => $item['id']], true);
      }

      // Delete all deploygroups
      $pfDeployGroup = new PluginGlpiinventoryDeployGroup();
      $items = $pfDeployGroup->find();
      foreach ($items as $item) {
         $pfDeployGroup->delete(['id' => $item['id']], true);
      }

      // Delete all tasks
      $pfTask = new PluginGlpiinventoryTask();
      $items = $pfTask->find();
      foreach ($items as $item) {
         $pfTask->delete(['id' => $item['id']], true);
      }

   }


   protected function setUp(): void {

      // Add some computers
      $computer = new Computer();
      $pfAgent  = new PluginGlpiinventoryAgent();

      $computerId = $computer->add(['name' => 'pc01', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'pc02', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'pc03', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'pc04', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'pc05', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'pc06', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'pc07', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'pc08', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'pc09', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'pc10', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'pc11', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'pc12', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'pc13', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'srv01', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'srv02', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'srv03', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'srv04', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
      $computerId = $computer->add(['name' => 'srv05', 'entities_id' => 0]);
      $pfAgent->add(['computers_id'=> $computerId, 'entities_id' => 0]);
   }


   /**
    * @test
    */
   public function TaskWithComputer() {
      $_SESSION['glpiactiveentities_string'] = 0;

      $pfDeployGroup             = new PluginGlpiinventoryDeployGroup();
      $pfDeployGroup_Dynamicdata = new PluginGlpiinventoryDeployGroup_Dynamicdata();
      $pfDeployPackage           = new PluginGlpiinventoryDeployPackage();
      $pfTask                    = new PluginGlpiinventoryTask();
      $pfTaskJob                 = new PluginGlpiinventoryTaskjob();
      $pfAgent                   = new PluginGlpiinventoryAgent();

      $input = [
         'name' => 'test',
         'type' => 'DYNAMIC'
      ];
      $groupId = $pfDeployGroup->add($input);
      $this->assertNotFalse($groupId);

      $input = [
         'plugin_glpiinventory_deploygroups_id' => $groupId,
         'fields_array' => 'a:2:{s:8:"criteria";a:1:{i:0;a:4:{s:4:"link";s:3:"AND";s:5:"field";s:1:"1";s:10:"searchtype";s:8:"contains";s:5:"value";s:3:"^pc";}}s:12:"metacriteria";N;}'
      ];
      $groupDynamicId = $pfDeployGroup_Dynamicdata->add($input);
      $this->assertNotFalse($groupDynamicId);

      $input = [
         'name'        => 'ls',
         'entities_id' => 0
      ];
      $packageId = $pfDeployPackage->add($input);
      $this->assertNotFalse($packageId);

      $input = [
         'name'           => 'deploy',
         'is_active'      => 1,
         'communication'  => 'pull'
      ];
      $taskId = $pfTask->add($input);
      $this->assertNotFalse($taskId);

      $a_plugins = current(getAllDataFromTable('glpi_plugins', ['directory' => 'glpiinventory']));

      $input = [
         'plugin_glpiinventory_tasks_id' => $taskId,
         'name'        => 'deploy',
         'plugins_id'  => $a_plugins['id'],
         'method'      => 'deployinstall',
         'actors'      => '[{"PluginGlpiinventoryDeployGroup":"'.$groupId.'"}]',
         'targets'     => '[{"PluginGlpiinventoryDeployPackage":"'.$packageId.'"}]'
      ];
      $taskjobId = $pfTaskJob->add($input);
      $this->assertNotFalse($taskjobId);

      // Force task prepation
      $pfTask->getFromDBByCrit(['name' => 'deploy']);
      $pfTask->forceRunning();

      $a_jobstates = getAllDataFromTable("glpi_plugin_glpiinventory_taskjobstates");
      $items = [];
      foreach ($a_jobstates as $data) {
         unset($data['uniqid']);
         unset($data['id']);
         $items[] = $data;
      }
      $agentsId = array_keys($pfAgent->find(['computers_id' => ['>', '0']]));

      $a_reference = [
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ],
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ],
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ],
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ],
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ],
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ],
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ],
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ],
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ],
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ],
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ],
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ],
         [
            'plugin_glpiinventory_taskjobs_id' => $taskjobId,
            'items_id'                           => $packageId,
            'itemtype'                           => "PluginGlpiinventoryDeployPackage",
            'state'                              => 0,
            'plugin_glpiinventory_agents_id'   => array_shift($agentsId),
            'specificity'                        => null,
            'date_start'                         => null,
            'nb_retry'                           => 0,
            'max_retry'                          => 1
         ]
      ];

      $this->assertEquals($a_reference, $items);
   }
}
