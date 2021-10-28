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

class TaskTest extends TestCase {


   public static function setUpBeforeClass(): void {
      $pfTask = new PluginGlpiinventoryTask();
      $items = $pfTask->find();
      foreach ($items as $item) {
         $pfTask->delete(['id' => $item['id']], true);
      }
   }


   /**
    * @test
    */
   public function addTask() {
      $pfTask    = new PluginGlpiinventoryTask();
      $pfTaskJob = new PluginGlpiinventoryTaskJob();

      $input = ['name' => 'MyTask', 'entities_id' => 0,
                'reprepare_if_successful' => 1, 'comment' => 'MyComments',
                'is_active' => 1];
      $tasks_id = $pfTask->add($input);
      $this->assertGreaterThan(0, $tasks_id);

      $this->assertTrue($pfTask->getFromDB($tasks_id));
      $this->assertEquals('MyTask', $pfTask->fields['name']);
      $this->assertEquals(1, $pfTask->fields['is_active']);

      $input = ['plugin_glpiinventory_tasks_id' => $tasks_id,
                'name'        =>'deploy',
                'method'      => 'deploy',
                'actors'      => '[{"PluginGlpiinventoryDeployGroup":"1"}]'
               ];
      $taskjobs_id = $pfTaskJob->add($input);
      $this->assertGreaterThan(0, $taskjobs_id);
      $this->assertTrue($pfTaskJob->getFromDB($taskjobs_id));
      $this->assertEquals('deploy', $pfTaskJob->fields['name']);
      $this->assertEquals('[{"PluginGlpiinventoryDeployGroup":"1"}]',
                          $pfTaskJob->fields['actors']);
   }


   /**
    * @test
    */
   public function duplicateTask() {
      $pfTask    = new PluginGlpiinventoryTask();
      $pfTaskJob = new PluginGlpiinventoryTaskJob();

      $data = $pfTask->find(['name' => 'MyTask']);
      $this->assertEquals(1, count($data));
      $tmp = current($data);
      $source_tasks_id = $tmp['id'];

      $this->assertTrue($pfTask->duplicate($source_tasks_id));

      $data = $pfTask->find(['name' => 'Copy of MyTask']);
      $this->assertEquals(1, count($data));
      $tmp = current($data);
      $target_tasks_id = $tmp['id'];

      $this->assertTrue($pfTask->getFromDB($target_tasks_id));
      $this->assertEquals(0, $pfTask->fields['is_active']);

      $data = $pfTaskJob->find(['plugin_glpiinventory_tasks_id' => $target_tasks_id]);
      $this->assertEquals(1, count($data));
      $tmp = current($data);
      $target_taskjobs_id = $tmp['id'];
      $this->assertTrue($pfTaskJob->getFromDB($target_taskjobs_id));
      $this->assertEquals('deploy', $pfTaskJob->fields['method']);
      $this->assertEquals('[{"PluginGlpiinventoryDeployGroup":"1"}]',
                          $pfTaskJob->fields['actors']);
   }


   /**
    * @test
    */
   public function deleteTask() {
      $pfTask    = new PluginGlpiinventoryTask();
      $pfTaskJob = new PluginGlpiinventoryTaskJob();

      $data = $pfTask->find(['name' => 'Copy of MyTask']);
      $this->assertEquals(1, count($data));
      $tmp = current($data);
      $tasks_id = $tmp['id'];

      $data = $pfTaskJob->find(['plugin_glpiinventory_tasks_id' => $tasks_id]);
      $this->assertEquals(1, count($data));
      $tmp = current($data);
      $taskjobs_id = $tmp['id'];

      $this->assertTrue($pfTask->delete(['id' => $tasks_id]));
      $this->assertFalse($pfTask->getFromDB($tasks_id));
      $this->assertFalse($pfTaskJob->getFromDB($taskjobs_id));
   }
}
