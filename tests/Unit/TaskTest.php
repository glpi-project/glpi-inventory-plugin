<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * @basedon   FusionInventory for GLPI
 * @copyright 2021-2026 Teclib' and contributors.
 * @copyright 2010-2021 by the FusionInventory Development Team.
 *
 * http://glpi-project.org
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
use PHPUnit\Framework\Attributes\DataProvider;

class TaskTest extends DbTestCase
{
    public function tearDown(): void
    {
        unset($_SESSION['glpi_plugin_glpiinventory']['includeoldjobs']);
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function includeOldJobsProvider(): iterable
    {
        yield 'last run only' => [1, 1];
        yield 'two last runs' => [2, 2];
        yield 'more runs requested than available' => [5, 3];
    }

    #[DataProvider('includeOldJobsProvider')]
    public function testJoblogsKeepLastRunsOfEachJobSharingAgentAndTarget(int $includeoldjobs, int $expected_runs_per_job): void
    {
        $computer  = $this->createItem(Computer::class, ['name' => 'joblogs_computer', 'entities_id' => 0]);
        $agenttype = $this->createItem(AgentType::class, ['name' => 'joblogs_agenttype']);
        $agent     = $this->createItem(Agent::class, [
            'name'          => 'joblogs_agent',
            'entities_id'   => 0,
            'itemtype'      => Computer::class,
            'items_id'      => $computer->getID(),
            'deviceid'      => 'joblogs_agent',
            'agenttypes_id' => $agenttype->getID(),
        ]);
        $task = $this->createItem(PluginGlpiinventoryTask::class, ['name' => 'joblogs_task', 'entities_id' => 0]);

        $expected_states_ids_by_job = [];
        foreach (['first_job', 'second_job'] as $job_name) {
            $taskjob = $this->createItem(PluginGlpiinventoryTaskjob::class, [
                'plugin_glpiinventory_tasks_id' => $task->getID(),
                'name'                          => $job_name,
                'method'                        => 'inventory',
                'targets'                       => '[{"Computer":"' . $computer->getID() . '"}]',
            ]);
            $states_ids = [];
            foreach (['2026-01-01 10:00:00', '2026-01-02 10:00:00', '2026-01-03 10:00:00'] as $run_date) {
                $state = $this->createItem(PluginGlpiinventoryTaskjobstate::class, [
                    'plugin_glpiinventory_taskjobs_id' => $taskjob->getID(),
                    'itemtype'                         => Computer::class,
                    'items_id'                         => $computer->getID(),
                    'agents_id'                        => $agent->getID(),
                    'state'                            => PluginGlpiinventoryTaskjobstate::FINISHED,
                    'uniqid'                           => $job_name . $run_date,
                ]);
                $this->createItem(PluginGlpiinventoryTaskjoblog::class, [
                    'plugin_glpiinventory_taskjobstates_id' => $state->getID(),
                    'date'                                  => $run_date,
                    'itemtype'                              => Computer::class,
                    'items_id'                              => $computer->getID(),
                    'state'                                 => PluginGlpiinventoryTaskjoblog::TASK_OK,
                    'comment'                               => $job_name,
                ]);
                $states_ids[] = $state->getID();
            }
            $expected_states_ids_by_job[$taskjob->getID()] = array_slice(array_reverse($states_ids), 0, $expected_runs_per_job);
        }

        $_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'] = $includeoldjobs;
        $joblogs = (new PluginGlpiinventoryTask())->getJoblogs([$task->getID()]);

        $target_key = Computer::class . '_' . $computer->getID();
        foreach ($expected_states_ids_by_job as $taskjobs_id => $expected_states_ids) {
            $runs = $joblogs['tasks'][$task->getID()]['jobs'][$taskjobs_id]['targets'][$target_key]['agents'][$agent->getID()] ?? [];
            $this->assertSame($expected_states_ids, array_map('intval', array_column($runs, 'jobstate_id')));
        }
    }

    public function testAddTask()
    {
        $pfTask    = new PluginGlpiinventoryTask();
        $pfTaskJob = new PluginGlpiinventoryTaskJob();

        $input = ['name' => 'MyTask', 'entities_id' => 0,
            'reprepare_if_successful' => 1, 'comment' => 'MyComments',
            'is_active' => 1,
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertGreaterThan(0, $tasks_id);

        $this->assertTrue($pfTask->getFromDB($tasks_id));
        $this->assertEquals('MyTask', $pfTask->fields['name']);
        $this->assertEquals(1, $pfTask->fields['is_active']);

        $input = ['plugin_glpiinventory_tasks_id' => $tasks_id,
            'name'        => 'deploy',
            'method'      => 'deploy',
            'actors'      => '[{"PluginGlpiinventoryDeployGroup":"1"}]',
        ];
        $taskjobs_id = $pfTaskJob->add($input);
        $this->assertGreaterThan(0, $taskjobs_id);
        $this->assertTrue($pfTaskJob->getFromDB($taskjobs_id));
        $this->assertEquals('deploy', $pfTaskJob->fields['name']);
        $this->assertEquals(
            '[{"PluginGlpiinventoryDeployGroup":"1"}]',
            $pfTaskJob->fields['actors']
        );
    }


    public function testDuplicateTask()
    {
        $this->testAddTask();
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
        $this->assertEquals(
            '[{"PluginGlpiinventoryDeployGroup":"1"}]',
            $pfTaskJob->fields['actors']
        );
    }


    public function testDeleteTask()
    {
        $this->testAddTask();
        $pfTask    = new PluginGlpiinventoryTask();
        $pfTaskJob = new PluginGlpiinventoryTaskJob();

        $data = $pfTask->find(['name' => 'MyTask']);
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
