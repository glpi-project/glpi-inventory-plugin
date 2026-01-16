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
use Glpi\Tests\DbTestCase;

class CronTaskTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        //enable DEPLOY module
        $module = new PluginGlpiinventoryAgentmodule();
        $this->assertTrue($module->getFromDBByCrit(['modulename' => 'DEPLOY']));
        $this->assertTrue(
            $module->update([
                'id'        => $module->fields['id'],
                'is_active' => 1,
            ])
        );
    }

    private function prepareDb(): void
    {
        global $DB;

        $entity = new Entity();
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $pfDeployGroup   = new PluginGlpiinventoryDeployGroup();
        $pfTask          = new PluginGlpiinventoryTask();
        $pfTaskjob       = new PluginGlpiinventoryTaskjob();
        $pfDeployGroup_Dynamicdata = new PluginGlpiinventoryDeployGroup_Dynamicdata();

        $this->assertTrue(
            $DB->update(
                Entity::getTable(),
                ['agent_base_url' => 'http://127.0.0.1/glpi'],
                ['id' => 0]
            )
        );
        $this->assertTrue($entity->getFromDB(0));
        $this->assertSame('http://127.0.0.1/glpi', $entity->fields['agent_base_url'], 'Entity has not been updated');

        // Create package
        $input = [
            'entities_id' => 0,
            'name' => 'package',
        ];
        $packages_id = $pfDeployPackage->add($input);
        $this->assertNotFalse($packages_id);

        // Create dynamic group
        $input = [
            'name' => 'all computers have name computer',
            'type' => 'DYNAMIC',
        ];
        $groups_id = $pfDeployGroup->add($input);
        $this->assertNotFalse($groups_id);

        $input = [
            'plugin_glpiinventory_deploygroups_id' => $groups_id,
            'fields_array' => '{"criteria":[{"field":"1","searchtype":"contains","value":"computer"}],"metacriteria":""}',
        ];
        $groupDynamicId = $pfDeployGroup_Dynamicdata->add($input);
        $this->assertNotFalse($groupDynamicId);

        // create task
        $input = [
            'entities_id' => 0,
            'name' => 'deploy',
            'is_active' => 1,
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        // create takjob
        $input = [
            'plugin_glpiinventory_tasks_id' => $tasks_id,
            'entities_id' => 0,
            'name' => 'deploy',
            'method' => 'deployinstall',
            'targets' => '[{"PluginGlpiinventoryDeployPackage":"' . $packages_id . '"}]',
            'actors' => '[{"PluginGlpiinventoryDeployGroup":"' . $groups_id . '"}]',
        ];
        $taskjobId = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobId);

        // Create computers + agents
        $this->createComputer('computer1');
        $this->createComputer('computer2');
        $this->createComputer('computer3');

        // Create package
        $input = [
            'entities_id' => 0,
            'name' => 'on demand package',
            'is_recursive' => 0,
            'plugin_glpiinventory_deploygroups_id' => $groups_id,
            'json' => '{"jobs":{"checks":[],"associatedFiles":[],"actions":[]},"associatedFiles":[]}',
        ];
        $packages_id_2 = $pfDeployPackage->add($input);
        $this->assertNotFalse($packages_id_2);

        // create task
        $input = [
            'entities_id' => 0,
            'name' => 'ondemand',
            'is_active' => 1,
            'is_deploy_on_demand' => 1,
            'reprepare_if_successful' => 0,
        ];
        $tasks_id_2 = $pfTask->add($input);
        $this->assertNotFalse($tasks_id_2);

        // create takjob
        $input = [
            'plugin_glpiinventory_tasks_id' => $tasks_id_2,
            'entities_id' => 0,
            'name' => 'deploy',
            'method' => 'deployinstall',
            'targets' => '[{"PluginGlpiinventoryDeployPackage":"' . $packages_id_2 . '"}]',
            'actors' => '[{"PluginGlpiinventoryDeployGroup":"' . $groups_id . '"}]',
        ];
        $this->assertNotFalse($pfTaskjob->add($input));
    }

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

    public function testPrepareTaskStandard(): void
    {
        $this->prepareDb();
        PluginGlpiinventoryTask::cronTaskscheduler();

        $pfTask = new PluginGlpiinventoryTask();

        $this->assertTrue($pfTask->getFromDBByCrit(['name' => 'deploy']), 'deploy task not found');
        $this->assertArrayHasKey('id', $pfTask->fields);
        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $agent = new Agent();
        $reference = [];
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer1']), 'computer1 not found');
        $reference[$agent->fields['id']] = 'computer1';
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer2']), 'computer2 not found');
        $reference[$agent->fields['id']] = 'computer2';
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer3']), 'computer3 not found');
        $reference[$agent->fields['id']] = 'computer3';

        $this->assertEquals($reference, $data['agents']);
        foreach ($data['tasks'] as $task_id => &$task) {
            foreach ($task['jobs'] as $job_id => &$job) {
                foreach ($job['targets'] as $target_id => &$target) {
                    foreach ($target['agents'] as $agent_id => &$agent) {
                        $logs = $data['tasks'][$task_id]['jobs'][$job_id]['targets'][$target_id]['agents'][$agent_id];
                        $this->assertEquals(1, count($logs));
                        /* We get something like:
                        [
                            agent_id => 1,
                            link => ./vendor/bin/phpunit/front/computer.form.php?id=1,
                            numstate => 0,
                            state => prepared,
                            jobstate_id => 1,
                            last_log_id => 1,
                            last_log_date => 2018-01-20 12:44:06,
                            timestamp => 1516448646,
                            last_log =>
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

    public function testPrepareTaskWithNewComputer(): void
    {
        $this->prepareDb();
        $this->createComputer('computer4');

        PluginGlpiinventoryTask::cronTaskscheduler();

        $pfTask = new PluginGlpiinventoryTask();

        // All tasks (active or not) and get logs
        $this->assertTrue($pfTask->getFromDBByCrit(['name' => 'deploy']), 'deploy task not found');
        $this->assertArrayHasKey('id', $pfTask->fields);
        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $agent = new Agent();
        $reference = [];
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer1']), 'computer1 not found');
        $reference[$agent->fields['id']] = 'computer1';
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer2']), 'computer2 not found');
        $reference[$agent->fields['id']] = 'computer2';
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer3']), 'computer3 not found');
        $reference[$agent->fields['id']] = 'computer3';
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer4']), 'computer4 not found');
        $reference[$agent->fields['id']] = 'computer4';

        $this->assertEquals($reference, $data['agents']);
    }

    public function testPrepareTaskWithdynamicgroupchanged(): void
    {
        $this->prepareDb();
        $this->createComputer('computer4');

        $computer = new Computer();
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'computer2']));
        $this->assertTrue(
            $computer->update([
                'id'   => $computer->fields['id'],
                'name' => 'koin',
            ])
        );

        PluginGlpiinventoryTask::cronTaskscheduler();

        $pfTask = new PluginGlpiinventoryTask();

        $pfTask->getFromDBByCrit(['name' => 'deploy']);
        $this->assertArrayHasKey('id', $pfTask->fields);
        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $agent = new Agent();
        $reference = [];
        $ref_prepared = [];
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer1']), 'computer1 not found');
        $reference[$agent->fields['id']] = 'computer1';
        $agentId1 = $agent->fields['id'];

        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer3']), 'computer3 not found');
        $reference[$agent->fields['id']] = 'computer3';
        $agentId2 = $agent->fields['id'];

        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer4']), 'computer4 not found');
        $reference[$agent->fields['id']] = 'computer4';
        $ref_prepared[] = $agent->fields['id'];
        $ref_prepared[] = $agentId2;
        $ref_prepared[] = $agentId1;

        $this->assertEquals($reference, $data['agents']);

        $pfTaskjob       = new PluginGlpiinventoryTaskjob();
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();

        $pfTaskjob->getFromDBByCrit(['plugin_glpiinventory_tasks_id' => $pfTask->fields['id']]);
        $pfDeployPackage->getFromDBByCrit(['name' => 'package']);

        $this->assertEquals(
            $ref_prepared,
            array_keys($data['tasks'][$pfTask->fields['id']]['jobs'][$pfTaskjob->fields['id']]['targets']['PluginGlpiinventoryDeployPackage_' . $pfDeployPackage->fields['id']]['counters']['agents_prepared'])
        );
    }

    public function testPrepareTaskDisabled(): void
    {
        $this->prepareDb();
        $pfTask = new PluginGlpiinventoryTask();

        $this->assertTrue($pfTask->getFromDBByCrit(['name' => 'deploy']));
        $this->assertTrue(
            $pfTask->update([
                'id'        => $pfTask->fields['id'],
                'is_active' => 0,
            ])
        );

        PluginGlpiinventoryTask::cronTaskscheduler();

        // Only for active tasks and with logs
        $data = $pfTask->getJoblogs([$pfTask->fields['id']], true, true);

        $this->assertEquals([], $data['agents'], 'Task inactive, so no agent prepared');
        $this->assertEquals([], $data['tasks']);
    }

    public function testPrepareTaskNoLogs(): void
    {
        $this->prepareDb();
        $pfTask = new PluginGlpiinventoryTask();

        $this->assertTrue($pfTask->getFromDBByCrit(['name' => 'deploy']), 'deploy task not found');

        PluginGlpiinventoryTask::cronTaskscheduler();

        $data = $pfTask->getJoblogs([$pfTask->fields['id']], false, false);

        $agent = new Agent();
        $reference = [];
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer1']));
        $reference[$agent->fields['id']] = 'computer1';
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer2']));
        $reference[$agent->fields['id']] = 'computer2';
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer3']));
        $reference[$agent->fields['id']] = 'computer3';

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

    public function testPrepareTaskNotRePrepareIfSuccessful(): void
    {
        global $DB;
        $this->prepareDb();
        $_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'] = 2;

        $agent        = new Agent();
        $pfTask       = new PluginGlpiinventoryTask();
        $deploycommon = new PluginGlpiinventoryDeployCommon();

        $this->assertTrue($pfTask->getFromDBByCrit(['name' => 'deploy']));

        //update directly in DB to prevent reset jobstate
        //see PluginGlpiinventoryTask->post_updateItem
        $this->assertTrue(
            $DB->update(
                $pfTask->getTable(),
                ['reprepare_if_successful' => 0],
                ['id' => $pfTask->fields['id']]
            )
        );
        $this->assertTrue($pfTask->getFromDBByCrit(['name' => 'deploy']));

        // prepare
        PluginGlpiinventoryTask::cronTaskscheduler();

        $pfTaskjob       = new PluginGlpiinventoryTaskjob();
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();

        $pfTaskjob->getFromDBByCrit(['plugin_glpiinventory_tasks_id' => $pfTask->fields['id']]);
        $pfDeployPackage->getFromDBByCrit(['name' => 'package']);

        $this->createComputer('computer4');

        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer1']), 'computer1 not found');
        $agentComputer1Id = $agent->fields['id'];
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer2']), 'computer2 not found');
        $agentComputer2Id = $agent->fields['id'];
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer3']), 'computer3 not found');
        $agentComputer3Id = $agent->fields['id'];
        $this->assertTrue($agent->getFromDBByCrit(['name' => 'computer4']), 'computer4 not found');
        $agentComputer4Id = $agent->fields['id'];

        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $counters = $data['tasks'][$pfTask->fields['id']]['jobs'][$pfTaskjob->fields['id']]['targets']['PluginGlpiinventoryDeployPackage_' . $pfDeployPackage->fields['id']]['counters'];
        $expected = [
            $agentComputer1Id,
            $agentComputer2Id,
            $agentComputer3Id,
        ];
        sort($expected);
        $result = array_keys($counters['agents_prepared']);
        sort($result);
        $this->assertSame($expected, $result);
        $this->assertSame([], $counters['agents_cancelled']);
        $this->assertSame([], $counters['agents_running']);
        $this->assertSame([], $counters['agents_success']);
        $this->assertSame([], $counters['agents_error']);

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
                'sendheaders' => false,
            ];
            PluginGlpiinventoryCommunicationRest::updateLog($params);
        }

        // 1 computer in error
        $this->assertTrue($agent->getFromDBByCrit(['deviceid' => 'computer3']), 'agent for computer3 not found');
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
                'sendheaders' => false,
            ];
            PluginGlpiinventoryCommunicationRest::updateLog($params);
            $params = [
                'machineid' => 'computer3',
                'uuid'      => $jobstate_order['job']['uuid'],
                'code'      => 'ko',
                'msg'       => 'failure of check #1 (error)',
                'sendheaders' => false,
            ];
            PluginGlpiinventoryCommunicationRest::updateLog($params);
        }

        // re-prepare and will have only the computer in error be in prepared mode
        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);

        $counters = $data['tasks'][$pfTask->fields['id']]['jobs'][$pfTaskjob->fields['id']]['targets']['PluginGlpiinventoryDeployPackage_' . $pfDeployPackage->fields['id']]['counters'];
        $this->assertSame([$agentComputer2Id], array_keys($counters['agents_prepared']));
        $this->assertSame([], $counters['agents_cancelled']);
        $this->assertSame([], $counters['agents_running']);
        $this->assertSame([$agentComputer1Id], array_keys($counters['agents_success']));
        $this->assertSame([$agentComputer3Id], array_keys($counters['agents_error']));


        PluginGlpiinventoryTask::cronTaskscheduler();
        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);
        $reference = [
            'agents_prepared' => [
                $agentComputer3Id => 7,
                $agentComputer4Id => 3,
            ],
            'agents_cancelled' => [],
            'agents_running' => [],
            'agents_success' => [
                $agentComputer1Id => 1,
            ],
            'agents_error' => [
                $agentComputer3Id => 2,
            ],
        ];
        $counters = $data['tasks'][$pfTask->fields['id']]['jobs'][$pfTaskjob->fields['id']]['targets']['PluginGlpiinventoryDeployPackage_' . $pfDeployPackage->fields['id']]['counters'];
        $this->assertSame([$agentComputer4Id, $agentComputer3Id, $agentComputer2Id], array_keys($counters['agents_prepared']));
        $this->assertSame([], $counters['agents_cancelled']);
        $this->assertSame([], $counters['agents_running']);
        $this->assertSame([$agentComputer1Id], array_keys($counters['agents_success']));
        $this->assertSame([$agentComputer3Id], array_keys($counters['agents_error']));


        //update directly in DB to prevent reset jobstate
        //see PluginGlpiinventoryTask->post_updateItem
        $this->assertTrue(
            $DB->update(
                $pfTask->getTable(),
                ['reprepare_if_successful' => 1],
                ['id' => $pfTask->fields['id']]
            )
        );
        $this->assertTrue($pfTask->getFromDB($pfTask->fields['id']));

        PluginGlpiinventoryTask::cronTaskscheduler();
        $data = $pfTask->getJoblogs([$pfTask->fields['id']]);
        $counters = $data['tasks'][$pfTask->fields['id']]['jobs'][$pfTaskjob->fields['id']]['targets']['PluginGlpiinventoryDeployPackage_' . $pfDeployPackage->fields['id']]['counters'];
        $this->assertSame([$agentComputer1Id, $agentComputer4Id, $agentComputer3Id, $agentComputer2Id], array_keys($counters['agents_prepared']));
        $this->assertSame([], $counters['agents_cancelled']);
        $this->assertSame([], $counters['agents_running']);
        $this->assertSame([$agentComputer1Id], array_keys($counters['agents_success']));
        $this->assertSame([$agentComputer3Id], array_keys($counters['agents_error']));
    }

    public function testCleanTasksAndJobs(): void
    {
        global $DB;
        $this->prepareDb();
        $pfTask         = new PluginGlpiinventoryTask();
        $pfTaskJobstate = new PluginGlpiinventoryTaskjobstate();

        //We only work on 1 task
        $this->assertTrue($pfTask->getFromDBByCrit(['name' => 'deploy']), 'deploy task not found');
        $this->assertTrue($pfTask->delete(['id' => $pfTask->fields['id']], true));

        //Find the on demand task
        $tasks = $pfTask->find(['name' => 'ondemand']);
        $this->assertEquals(1, count($tasks));

        $task     = current($tasks);
        $tasks_id = $task['id'];

        //Prepare the task
        PluginGlpiinventoryTask::cronTaskscheduler();

        //Set the first job as successfull
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_glpiinventory_taskjoblogs',
            'LIMIT' => 1,
        ]);
        foreach ($iterator as $data) {
            $pfTaskJobstate->changeStatusFinish($data['plugin_glpiinventory_taskjobstates_id'], 0, '');
        }

        //No task & jobtates should be removed because ask for cleaning 5 days from now
        $index = $pfTask->cleanTasksAndJobs(5);

        $this->assertEquals(0, $index);

        //Set the joblogs date at 2 days ago
        $datetime = new Datetime($_SESSION['glpi_currenttime']);
        $datetime->modify('-4 days');

        $this->assertTrue(
            $DB->update(
                'glpi_plugin_glpiinventory_taskjoblogs',
                ['date' => $datetime->format('Y-m-d') . " 00:00:00"],
                [new QueryExpression("1=1")]
            )
        );

        //No task & jobs should be removed because ask for cleaning 5 days from now
        $index = $pfTask->cleanTasksAndJobs(5);
        $this->assertEquals(0, $index);

        $this->assertTrue($pfTask->getFromDB($tasks_id));

        $this->createComputer('computer5');

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
        $request = $DB->request([
            'DISTINCT' => 'plugin_glpiinventory_taskjobstates_id',
            'FROM' => 'glpi_plugin_glpiinventory_taskjoblogs',
        ]);
        foreach ($request as $data) {
            $pfTaskJobstate->changeStatusFinish($data['plugin_glpiinventory_taskjobstates_id'], 0, '');
        }

        $this->assertTrue(
            $DB->update(
                'glpi_plugin_glpiinventory_taskjoblogs',
                ['date' => $datetime->format('Y-m-d') . " 00:00:00"],
                [new QueryExpression("1=1")]
            )
        );

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
