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

class CronTaskTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {

       // Delete all computers
        $computer = new Computer();
        $items = $computer->find(['NOT' => ['name' => ['LIKE', '_test_pc%']]]);
        foreach ($items as $item) {
            $computer->delete(['id' => $item['id']], true);
        }

       // Delete all agents (force)
        $agent = new Agent();
        $items = $agent->find();
        foreach ($items as $item) {
            $agent->delete(['id' => $item['id']], true);
        }

       // Delete all tasks
        $pfTask = new PluginGlpiinventoryTask();
        $items = $pfTask->find();
        foreach ($items as $item) {
            $pfTask->delete(['id' => $item['id']], true);
        }

       // Delete al deploygroups
        $pfDeployGroup   = new PluginGlpiinventoryDeployGroup();
        $items = $pfDeployGroup->find();
        foreach ($items as $item) {
            $pfDeployGroup->delete(['id' => $item['id']], true);
        }

       // Delete al deploypackages
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $items = $pfDeployPackage->find();
        foreach ($items as $item) {
            $pfDeployPackage->delete(['id' => $item['id']], true);
        }

        $module = new PluginGlpiinventoryAgentmodule();
        $module->getFromDBByCrit(['modulename' => 'DEPLOY']);
        $module->update([
         'id'        => $module->fields['id'],
         'is_active' => 1
        ]);
    }


   /**
    * @test
    */
    public function prepareDb()
    {
        global $DB;

        $computer        = new Computer();
        $agent         = new Agent();
        $entity = new Entity();
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $pfDeployGroup   = new PluginGlpiinventoryDeployGroup();
        $pfTask          = new PluginGlpiinventoryTask();
        $pfTaskjob       = new PluginGlpiinventoryTaskjob();
        $pfDeployGroup_Dynamicdata = new PluginGlpiinventoryDeployGroup_Dynamicdata();

        $this->assertTrue($entity->getFromDBByCrit(['id' => 0]));
        $this->assertTrue(
            $entity->update([
            'id'                => $entity->fields['id'],
            'agent_base_url' => 'http://127.0.0.1/glpi'
            ])
        );

       // Create package
        $input = [
          'entities_id' => 0,
          'name'        => 'package'
        ];
        $packages_id = $pfDeployPackage->add($input);
        $this->assertNotFalse($packages_id);

       // Create dynamic group
        $input = [
          'name' => 'all computers have name computer',
          'type' => 'DYNAMIC'
        ];
        $groups_id = $pfDeployGroup->add($input);
        $this->assertNotFalse($groups_id);

        $input = [
          'plugin_glpiinventory_deploygroups_id' => $groups_id,
          'fields_array' => 'a:2:{s:8:"criteria";a:1:{i:0;a:3:{s:5:"field";s:1:"1";s:10:"searchtype";s:8:"contains";s:5:"value";s:8:"computer";}}s:12:"metacriteria";s:0:"";}'
        ];
        $groupDynamicId = $pfDeployGroup_Dynamicdata->add($input);
        $this->assertNotFalse($groupDynamicId);

       // create task
        $input = [
          'entities_id' => 0,
          'name'        => 'deploy',
          'is_active'   => 1
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

       // create takjob
        $input = [
          'plugin_glpiinventory_tasks_id' => $tasks_id,
          'entities_id'                     => 0,
          'name'                            => 'deploy',
          'method'                          => 'deployinstall',
          'targets'                         => '[{"PluginGlpiinventoryDeployPackage":"' . $packages_id . '"}]',
          'actors'                          => '[{"PluginGlpiinventoryDeployGroup":"' . $groups_id . '"}]'
        ];
        $taskjobId = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobId);

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
          'deviceid'    => 'computer1',
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
          'deviceid'   => 'computer2',
          'useragent'   => 'FusionInventory-Agent_v2.3.11',
          'itemtype' => Computer::getType(),
          'items_id' => $computers_id,
          'agenttypes_id' => $agenttype['id']
        ];
        $agentId = $agent->add($input);
        $this->assertNotFalse($agentId);

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
          'deviceid'    => 'computer3',
          'useragent'   => 'FusionInventory-Agent_v2.3.11',
          'itemtype' => Computer::getType(),
          'items_id' => $computers_id,
         'agenttypes_id' => $agenttype['id']
        ];
        $agentId = $agent->add($input);
        $this->assertNotFalse($agentId);

       // Create package
        $input = [
          'entities_id' => 0,
          'name'        => 'on demand package',
          'is_recursive' => 0,
          'plugin_glpiinventory_deploygroups_id' => $groups_id,
          'json' => '{"jobs":{"checks":[],"associatedFiles":[],"actions":[]},"associatedFiles":[]}'
        ];
        $packages_id_2 = $pfDeployPackage->add($input);
        $this->assertNotFalse($packages_id_2);

       // create task
        $input = [
          'entities_id'             => 0,
          'name'                    => 'ondemand',
          'is_active'               => 1,
          'is_deploy_on_demand'     => 1,
          'reprepare_if_successful' => 0
        ];
        $tasks_id_2 = $pfTask->add($input);
        $this->assertNotFalse($tasks_id_2);

       // create takjob
        $input = [
          'plugin_glpiinventory_tasks_id' => $tasks_id_2,
          'entities_id'                     => 0,
          'name'                            => 'deploy',
          'method'                          => 'deployinstall',
          'targets'                         => '[{"PluginGlpiinventoryDeployPackage":"' . $packages_id_2 . '"}]',
          'actors'                          => '[{"PluginGlpiinventoryDeployGroup":"' . $groups_id . '"}]'
        ];
        $taskjobId = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobId);
    }


   /**
    * @test
    */
    public function prepareTask()
    {
        global $DB;

        PluginGlpiinventoryTask::cronTaskscheduler();

        $pfTask = new PluginGlpiinventoryTask();

        $pfTask->getFromDBByCrit(['name' => 'deploy']);
        $this->assertArrayHasKey('id', $pfTask->fields);
        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $agent = new Agent();
        $reference = [];
        $agent->getFromDBByCrit(['name' => 'computer1']);
        $reference[$agent->fields['id']] = 'computer1';
        $agent->getFromDBByCrit(['name' => 'computer2']);
        $reference[$agent->fields['id']] = 'computer2';
        $agent->getFromDBByCrit(['name' => 'computer3']);
        $reference[$agent->fields['id']] = 'computer3';

        $this->assertEquals($reference, $data['agents']);
        foreach ($data['tasks'] as $task_id => &$task) {
            foreach ($task['jobs'] as $job_id => &$job) {
                foreach ($job['targets'] as $target_id => &$target) {
                    foreach ($target['agents'] as $agent_id => &$agent) {
                        $logs = $data['tasks'][$task_id]['jobs'][$job_id]['targets'][$target_id]['agents'][$agent_id];
                        $this->assertEquals(1, count($logs));
                        /* We get something like:
                        [agent_id] => 1
                        [link] => ./vendor/bin/phpunit/front/computer.form.php?id=1
                        [numstate] => 0
                        [state] => prepared
                        [jobstate_id] => 1
                        [last_log_id] => 1
                        [last_log_date] => 2018-01-20 12:44:06
                        [timestamp] => 1516448646
                        [last_log] =>
                        */
                        foreach ($logs as &$log) {
                            $this->assertEquals($log['agent_id'], $agent_id);
                            $this->assertEquals($log['state'], "prepared");
                            $this->assertEquals($log['last_log'], "");
                        }
                    }
                }
            }
        }
    }


   /**
    * @test
    */
    public function prepareTaskWithNewComputer()
    {
        global $DB;

        $computer = new Computer();
        $agent  = new Agent();

        $input = [
          'entities_id' => 0,
          'name'        => 'computer4'
        ];
        $computers_id = $computer->add($input);
        $this->assertNotFalse($computers_id);

        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $input = [
          'entities_id' => 0,
          'name'        => 'computer4',
          'version'     => '{"INVENTORY":"v2.3.11"}',
          'deviceid'    => 'computer4',
          'useragent'   => 'FusionInventory-Agent_v2.3.11',
          'itemtype' => Computer::getType(),
          'items_id' => $computers_id,
          'agenttypes_id' => $agenttype['id']
        ];
        $agentId = $agent->add($input);
        $this->assertNotFalse($agentId);

        PluginGlpiinventoryTask::cronTaskscheduler();

        $pfTask = new PluginGlpiinventoryTask();

       // All tasks (active or not) and get logs
        $pfTask->getFromDBByCrit(['name' => 'deploy']);
        $this->assertArrayHasKey('id', $pfTask->fields);
        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $agent = new Agent();
        $reference = [];
        $agent->getFromDBByCrit(['name' => 'computer1']);
        $reference[$agent->fields['id']] = 'computer1';
        $agent->getFromDBByCrit(['name' => 'computer2']);
        $reference[$agent->fields['id']] = 'computer2';
        $agent->getFromDBByCrit(['name' => 'computer3']);
        $reference[$agent->fields['id']] = 'computer3';
        $agent->getFromDBByCrit(['name' => 'computer4']);
        $reference[$agent->fields['id']] = 'computer4';

        $this->assertEquals($reference, $data['agents']);
    }


   /**
    * @test
    */
    public function prepareTaskWithdynamicgroupchanged()
    {

        $computer = new Computer();
        $computer->getFromDBByCrit(['name' => 'computer2']);
        $computer->update([
          'id'   => $computer->fields['id'],
          'name' => 'koin']);

        PluginGlpiinventoryTask::cronTaskscheduler();

        $pfTask = new PluginGlpiinventoryTask();

        $pfTask->getFromDBByCrit(['name' => 'deploy']);
        $this->assertArrayHasKey('id', $pfTask->fields);
        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $agent = new Agent();
        $reference = [];
        $ref_prepared = [];
        $agent->getFromDBByCrit(['name' => 'computer1']);
        $reference[$agent->fields['id']] = 'computer1';
        $agentId1 = $agent->fields['id'];

        $agent->getFromDBByCrit(['name' => 'computer2']);
        $reference[$agent->fields['id']] = 'computer2';

        $agent->getFromDBByCrit(['name' => 'computer3']);
        $reference[$agent->fields['id']] = 'computer3';
        $agentId2 = $agent->fields['id'];

        $agent->getFromDBByCrit(['name' => 'computer4']);
        $reference[$agent->fields['id']] = 'computer4';
        $ref_prepared[] = $agent->fields['id'];
        $ref_prepared[] = $agentId2;
        $ref_prepared[] = $agentId1;

        $this->assertEquals($reference, $data['agents']);

        $pfTaskjob       = new PluginGlpiinventoryTaskjob();
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();

        $pfTaskjob->getFromDBByCrit(['plugin_glpiinventory_tasks_id' => $pfTask->fields['id']]);
        $pfDeployPackage->getFromDBByCrit(['name' => 'package']);

        $this->assertEquals($ref_prepared, array_keys($data['tasks'][$pfTask->fields['id']]['jobs'][$pfTaskjob->fields['id']]['targets']['PluginGlpiinventoryDeployPackage_' . $pfDeployPackage->fields['id']]['counters']['agents_prepared']));
    }


   /**
    * @test
    */
    public function prepareTaskDisabled()
    {

        $pfTask = new PluginGlpiinventoryTask();

        $pfTask->getFromDBByCrit(['name' => 'deploy']);
        $this->assertArrayHasKey('id', $pfTask->fields);
        $pfTask->update([
         'id'        => $pfTask->fields['id'],
         'is_active' => 0
        ]);

        PluginGlpiinventoryTask::cronTaskscheduler();

       // Only for active tasks and with logs
        $data = $pfTask->getJoblogs([$pfTask->fields['id']], true, true);

        $ref = [];

        $this->assertEquals($ref, $data['agents'], 'Task inactive, so no agent prepared');

        $ref_prepared = [];

        $this->assertEquals($ref_prepared, $data['tasks']);
    }

   /**
    * @test
    */
    public function prepareTaskNoLogs()
    {
        global $DB;

        $pfTask = new PluginGlpiinventoryTask();

        $pfTask->getFromDBByCrit(['name' => 'deploy']);
        $this->assertArrayHasKey('id', $pfTask->fields);
        $pfTask->update([
         'id'        => $pfTask->fields['id'],
         'is_active' => 1
        ]);

        PluginGlpiinventoryTask::cronTaskscheduler();

        $data = $pfTask->getJoblogs([$pfTask->fields['id']], false, false);

        $agent = new Agent();
        $reference = [];
        $agent->getFromDBByCrit(['name' => 'computer1']);
        $reference[$agent->fields['id']] = 'computer1';
        $agent->getFromDBByCrit(['name' => 'computer2']);
        $reference[$agent->fields['id']] = 'computer2';
        $agent->getFromDBByCrit(['name' => 'computer3']);
        $reference[$agent->fields['id']] = 'computer3';
        $agent->getFromDBByCrit(['name' => 'computer4']);
        $reference[$agent->fields['id']] = 'computer4';

        $this->assertEquals($reference, $data['agents']);

        foreach ($data['tasks'] as $task_id => &$task) {
            foreach ($task['jobs'] as $job_id => &$job) {
                foreach ($job['targets'] as $target_id => &$target) {
                    foreach ($target['agents'] as $agent_id => &$agent) {
                        $logs = $data['tasks'][$task_id]['jobs'][$job_id]['targets'][$target_id]['agents'][$agent_id];
                        // No logs
                        $this->assertEquals(0, count($logs), print_r($logs, true));
                    }
                }
            }
        }
    }


   /**
    * @test
    */
    public function prepareTaskNotRePrepareIfSuccessful()
    {
        global $DB;

        $_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'] = 2;

        $agent        = new Agent();
        $pfTask       = new PluginGlpiinventoryTask();
        $deploycommon = new PluginGlpiinventoryDeployCommon();

        $DB->query("TRUNCATE TABLE `glpi_plugin_glpiinventory_taskjoblogs`");
        $DB->query("TRUNCATE TABLE `glpi_plugin_glpiinventory_taskjobstates`");

        $pfTask->getFromDBByCrit(['name' => 'deploy']);
        $this->assertArrayHasKey('id', $pfTask->fields);
        $pfTask->update([
         'id'                      => $pfTask->fields['id'],
         'reprepare_if_successful' => 0,
         'is_active'               => 1
        ]);

       // prepare
        PluginGlpiinventoryTask::cronTaskscheduler();

        $pfTaskjob       = new PluginGlpiinventoryTaskjob();
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();

        $pfTaskjob->getFromDBByCrit(['plugin_glpiinventory_tasks_id' => $pfTask->fields['id']]);
        $pfDeployPackage->getFromDBByCrit(['name' => 'package']);

        $agent->getFromDBByCrit(['name' => 'computer1']);
        $agentComputer1Id = $agent->fields['id'];
        $agent->getFromDBByCrit(['name' => 'computer2']);
        $agentComputer2Id = $agent->fields['id'];
        $agent->getFromDBByCrit(['name' => 'computer3']);
        $agentComputer3Id = $agent->fields['id'];
        $agent->getFromDBByCrit(['name' => 'computer4']);
        $agentComputer4Id = $agent->fields['id'];

        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $reference = [
         'agents_prepared' => [
            $agentComputer1Id => 1,
            $agentComputer3Id => 2,
            $agentComputer4Id => 3
         ],
         'agents_cancelled' => [],
         'agents_running' => [],
         'agents_success' => [],
         'agents_error' => [],
         'agents_notdone' => [
            $agentComputer4Id => 3,
            $agentComputer3Id => 2,
            $agentComputer1Id => 1
         ]
        ];

        $counters = $data['tasks'][$pfTask->fields['id']]['jobs'][$pfTaskjob->fields['id']]['targets']['PluginGlpiinventoryDeployPackage_' . $pfDeployPackage->fields['id']]['counters'];
        $this->assertEquals($reference, $counters);

       // 1 computer deploy successfully
        $this->assertTrue($agent->getFromDBByCrit(['deviceid' => 'computer1']));
        $taskjobstates = $pfTask->getTaskjobstatesForAgent(
            $agent->fields['id'],
            ['deployinstall']
        );
        foreach ($taskjobstates as $taskjobstate) {
            $jobstate_order = $deploycommon->run($taskjobstate);
            $params = [
            'machineid' => 'computer1',
            'uuid'      => $jobstate_order['job']['uuid'],
            'code'      => 'ok',
            'msg'       => 'seems ok',
            'sendheaders' => false
            ];
            PluginGlpiinventoryCommunicationRest::updateLog($params);
        }

       // 1 computer in error
        $agent->getFromDBByCrit(['deviceid' => 'computer3']);
        $taskjobstates = $pfTask->getTaskjobstatesForAgent(
            $agent->fields['id'],
            ['deployinstall']
        );
        foreach ($taskjobstates as $taskjobstate) {
            $jobstate_order = $deploycommon->run($taskjobstate);
            $params = [
            'machineid' => 'computer3',
            'uuid'      => $jobstate_order['job']['uuid'],
            'code'      => 'running',
            'msg'       => 'gogogo',
            'sendheaders' => false
            ];
            PluginGlpiinventoryCommunicationRest::updateLog($params);
            $params = [
            'machineid' => 'computer3',
            'uuid'      => $jobstate_order['job']['uuid'],
            'code'      => 'ko',
            'msg'       => 'failure of check #1 (error)',
            'sendheaders' => false
            ];
            PluginGlpiinventoryCommunicationRest::updateLog($params);
        }

       // re-prepare and will have only the computer in error be in prepared mode
        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $reference = [
         'agents_prepared' => [
            $agentComputer4Id => 3,
         ],
         'agents_cancelled' => [],
         'agents_running' => [],
         'agents_success' => [
            $agentComputer1Id => 1
         ],
         'agents_error' => [
            $agentComputer3Id => 2
         ],
         'agents_notdone' => [
            $agentComputer4Id => 3
         ]
        ];

        $counters = $data['tasks'][$pfTask->fields['id']]['jobs'][$pfTaskjob->fields['id']]['targets']['PluginGlpiinventoryDeployPackage_' . $pfDeployPackage->fields['id']]['counters'];
        $this->assertEquals($reference, $counters);

        PluginGlpiinventoryTask::cronTaskscheduler();
        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);
        $reference = [
         'agents_prepared' => [
            $agentComputer3Id => 7,
            $agentComputer4Id => 3
            ],
         'agents_cancelled' => [],
         'agents_running' => [],
         'agents_success' => [
            $agentComputer1Id => 1
         ],
         'agents_error' => [
            $agentComputer3Id => 2
         ],
         'agents_notdone' => [
            $agentComputer4Id => 3
         ]
        ];
        $counters = $data['tasks'][$pfTask->fields['id']]['jobs'][$pfTaskjob->fields['id']]['targets']['PluginGlpiinventoryDeployPackage_' . $pfDeployPackage->fields['id']]['counters'];
        $this->assertEquals($reference, $counters);

        $pfTask->update([
         'id'                      => $pfTask->fields['id'],
         'reprepare_if_successful' => 1,
        ]);
        PluginGlpiinventoryTask::cronTaskscheduler();
        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);
        $reference = [
         'agents_prepared' => [
         $agentComputer1Id => 9,
         $agentComputer3Id => 7,
         $agentComputer4Id => 3
            ],
         'agents_cancelled' => [],
         'agents_running' => [],
         'agents_success' => [
         $agentComputer1Id => 1,
         ],
         'agents_error' => [
            $agentComputer3Id => 2
         ],
         'agents_notdone' => [
            $agentComputer4Id => 3
         ]
        ];
        $counters = $data['tasks'][$pfTask->fields['id']]['jobs'][$pfTaskjob->fields['id']]['targets']['PluginGlpiinventoryDeployPackage_' . $pfDeployPackage->fields['id']]['counters'];
        $this->assertEquals($reference, $counters);
    }


   /**
    * @test
    */
    public function cleanTasksAndJobs()
    {
        global $DB;

        $pfTask         = new PluginGlpiinventoryTask();
        $pfTaskJob      = new PluginGlpiinventoryTaskJob();
        $pfTaskJobstate = new PluginGlpiinventoryTaskjobstate();

       //We only work on 1 task
        $pfTask->getFromDBByCrit(['name' => 'deploy']);
        $pfTask->delete(['id' => $pfTask->fields['id']], true);

       //Clean all taskjoblogs & states
        $DB->query("TRUNCATE TABLE `glpi_plugin_glpiinventory_taskjoblogs`");
        $DB->query("TRUNCATE TABLE `glpi_plugin_glpiinventory_taskjobstates`");

       //Find the on demand task
        $tasks = $pfTask->find(['name' => 'ondemand']);
        $this->assertEquals(1, count($tasks));

        $task     = current($tasks);
        $tasks_id = $task['id'];

       //Prepare the task
        PluginGlpiinventoryTask::cronTaskscheduler();

       //Set the first job as successfull
        $query = "SELECT DISTINCT `plugin_glpiinventory_taskjobstates_id`
                FROM glpi_plugin_glpiinventory_taskjoblogs LIMIT 1";
        foreach ($DB->request($query) as $data) {
            $pfTaskJobstate->changeStatusFinish($data['plugin_glpiinventory_taskjobstates_id'], 0, '');
        }

       //No task & jobtates should be removed because ask for cleaning 5 days from now
        $index = $pfTask->cleanTasksAndJobs(5);

        $this->assertEquals(0, $index);

       //Set the joblogs date at 2 days ago
        $datetime = new Datetime($_SESSION['glpi_currenttime']);
        $datetime->modify('-4 days');

        $query = "UPDATE `glpi_plugin_glpiinventory_taskjoblogs`
                SET `date`='" . $datetime->format('Y-m-d') . " 00:00:00'";
        $DB->query($query);

       //No task & jobs should be removed because ask for cleaning 5 days from now
        $index = $pfTask->cleanTasksAndJobs(5);
        $this->assertEquals(0, $index);

        $this->assertEquals(true, $pfTask->getFromDB($tasks_id));

        $computer = new Computer();
        $agent  = new Agent();

       //Add a new computer into the dynamic group
        $input = [
          'entities_id' => 0,
          'name'        => 'computer5'
        ];
        $computers_id = $computer->add($input);
        $this->assertNotFalse($computers_id);

        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $input = [
          'entities_id'  => 0,
          'name'         => 'computer5',
          'version'      => '{"INVENTORY":"v2.3.21"}',
          'deviceid'     => 'computer5',
          'useragent'    => 'FusionInventory-Agent_v2.3.21',
          'itemtype' => Computer::getType(),
          'items_id' => $computers_id,
          'agenttypes_id' => $agenttype['id']
        ];
        $agent->add($input);

       //Reprepare the task
        PluginGlpiinventoryTask::cronTaskscheduler();

       //One taskjob is finished and should be cleaned
        $index = $pfTask->cleanTasksAndJobs(3);

        $this->assertGreaterThan(0, $index);

       //The task is still in DB because one job is not done
        $this->assertEquals(1, countElementsInTable(
            'glpi_plugin_glpiinventory_tasks',
            ['id' => $tasks_id]
        ));

       //Set the first job as successfull
        $query = "SELECT DISTINCT `plugin_glpiinventory_taskjobstates_id`
                FROM glpi_plugin_glpiinventory_taskjoblogs";
        foreach ($DB->request($query) as $data) {
            $pfTaskJobstate->changeStatusFinish($data['plugin_glpiinventory_taskjobstates_id'], 0, '');
        }

        $query = "UPDATE `glpi_plugin_glpiinventory_taskjoblogs`
                SET `date`='" . $datetime->format('Y-m-d') . " 00:00:00'";
        $DB->query($query);

       //One taskjob is finished and should be cleaned
        $index = $pfTask->cleanTasksAndJobs(2);

        $this->assertGreaterThan(0, $index);

       //The task is still in DB because one job is not done
        $this->assertEquals(0, countElementsInTable(
            'glpi_plugin_glpiinventory_tasks',
            ['id' => $tasks_id]
        ));
    }
}
