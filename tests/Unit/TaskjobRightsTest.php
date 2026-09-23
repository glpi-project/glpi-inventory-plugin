<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * @copyright 2021-2026 Teclib' and contributors.
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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Tests\DbTestCase;

class TaskjobRightsTest extends DbTestCase
{
    public function testForceEndRequiresUpdateRight(): void
    {
        $this->login('glpi', 'glpi');
        $taskjob = $this->createTaskjob(0);
        $taskjobstate = $this->createItem(PluginGlpiinventoryTaskjobstate::class, [
            'plugin_glpiinventory_taskjobs_id' => $taskjob->getID(),
            'items_id'                         => 1,
            'itemtype'                         => Computer::class,
            'state'                            => PluginGlpiinventoryTaskjobstate::PREPARED,
            'uniqid'                           => 'forceend_right',
        ]);
        $_SESSION['glpiactiveprofile'][PluginGlpiinventoryTask::$rightname] = READ;

        try {
            $taskjob->submitForm(['forceend' => 1, 'taskjobstates_id' => $taskjobstate->getID()]);
            $this->fail('Forcing the end of a job must require the update right');
        } catch (AccessDeniedHttpException $e) {
            $this->assertTrue($taskjobstate->getFromDB($taskjobstate->getID()));
            $this->assertEquals(PluginGlpiinventoryTaskjobstate::PREPARED, $taskjobstate->fields['state']);
        }
    }


    public function testDeleteTaskjobsRequiresPurgeRight(): void
    {
        $this->login('glpi', 'glpi');
        $taskjob = $this->createTaskjob(0);
        $_SESSION['glpiactiveprofile'][PluginGlpiinventoryTask::$rightname] = READ;

        try {
            $taskjob->submitForm(['delete_taskjobs' => 1, 'taskjobs' => [$taskjob->getID()]]);
            $this->fail('Deleting jobs must require the purge right');
        } catch (AccessDeniedHttpException $e) {
            $this->assertTrue($taskjob->getFromDB($taskjob->getID()));
        }
    }


    public function testDeleteTaskjobsRejectsJobOutsideActiveEntities(): void
    {
        $this->login('glpi', 'glpi');
        $other_entity = $this->createItem(Entity::class, ['name' => 'taskjob_other_entity', 'entities_id' => 0]);
        $taskjob = $this->createTaskjob($other_entity->getID());
        $this->setEntity(0, false);

        try {
            $taskjob->submitForm(['delete_taskjobs' => 1, 'taskjobs' => [$taskjob->getID()]]);
            $this->fail('Deleting a job of an inaccessible entity must be refused');
        } catch (AccessDeniedHttpException $e) {
            $this->assertTrue($taskjob->getFromDB($taskjob->getID()));
        }
    }


    public function testForceRunRejectsJobOutsideActiveEntities(): void
    {
        $this->login('glpi', 'glpi');
        $other_entity = $this->createItem(Entity::class, ['name' => 'taskjob_forcerun_entity', 'entities_id' => 0]);
        $taskjob = $this->createTaskjob($other_entity->getID());
        $this->setEntity(0, false);
        $taskjobstates_count = countElementsInTable(PluginGlpiinventoryTaskjobstate::getTable());

        try {
            $taskjob->submitForm(['taskjobstoforcerun' => [$taskjob->getID()]]);
            $this->fail('Forcing the run of a job of an inaccessible entity must be refused');
        } catch (AccessDeniedHttpException $e) {
            $this->assertSame($taskjobstates_count, countElementsInTable(PluginGlpiinventoryTaskjobstate::getTable()));
        }
    }


    public function testUpdateRejectsMovingJobToTaskOfInaccessibleEntity(): void
    {
        $this->login('glpi', 'glpi');
        $other_entity = $this->createItem(Entity::class, ['name' => 'taskjob_foreign_task', 'entities_id' => 0]);
        $foreign_task = $this->createItem(PluginGlpiinventoryTask::class, [
            'name'        => 'foreign task',
            'entities_id' => $other_entity->getID(),
        ]);
        $taskjob = $this->createTaskjob(0);
        $original_tasks_id = $taskjob->fields['plugin_glpiinventory_tasks_id'];
        $this->setEntity(0, false);

        try {
            $taskjob->submitForm([
                'update'                        => 1,
                'id'                            => $taskjob->getID(),
                'plugin_glpiinventory_tasks_id' => $foreign_task->getID(),
            ]);
            $this->fail('Moving a job to a task of an inaccessible entity must be refused');
        } catch (AccessDeniedHttpException $e) {
            $this->assertTrue($taskjob->getFromDB($taskjob->getID()));
            $this->assertEquals($original_tasks_id, $taskjob->fields['plugin_glpiinventory_tasks_id']);
        }
    }


    public function testItemAddActionRejectsItemOutsideActiveEntities(): void
    {
        $this->login('glpi', 'glpi');
        $other_entity = $this->createItem(Entity::class, ['name' => 'taskjob_foreign_item', 'entities_id' => 0]);
        $foreign_computer = $this->createItem(Computer::class, [
            'name'        => 'foreign_computer',
            'entities_id' => $other_entity->getID(),
        ]);
        $this->setEntity(0, false);
        $tasks_count = countElementsInTable(PluginGlpiinventoryTask::getTable());

        try {
            (new PluginGlpiinventoryTaskjob())->submitForm([
                'itemaddaction' => 1,
                'itemtype'      => Computer::class,
                'items_id'      => $foreign_computer->getID(),
                'methodaction'  => 'glpiinventory||inventory',
            ]);
            $this->fail('Adding an action on an item of an inaccessible entity must be refused');
        } catch (AccessDeniedHttpException $e) {
            $this->assertSame($tasks_count, countElementsInTable(PluginGlpiinventoryTask::getTable()));
        }
    }


    public function testItemAddActionRejectsInvalidItemtype(): void
    {
        $this->login('glpi', 'glpi');

        $this->expectException(BadRequestHttpException::class);
        (new PluginGlpiinventoryTaskjob())->submitForm([
            'itemaddaction' => 1,
            'itemtype'      => 'NotAnItemtype',
            'items_id'      => 1,
            'methodaction'  => 'glpiinventory||inventory',
        ]);
    }


    public function testAddJobRequiresUpdateRightOnTask(): void
    {
        $this->login('glpi', 'glpi');
        $task = $this->createItem(PluginGlpiinventoryTask::class, ['name' => 'task_without_update', 'entities_id' => 0]);
        $_SESSION['glpiactiveprofile'][PluginGlpiinventoryTask::$rightname] = READ | CREATE;
        $taskjobs_count = countElementsInTable(PluginGlpiinventoryTaskjob::getTable());

        try {
            (new PluginGlpiinventoryTaskjob())->submitForm([
                'add'                           => 1,
                'name'                          => 'job_without_update',
                'plugin_glpiinventory_tasks_id' => $task->getID(),
                'entities_id'                   => 0,
            ]);
            $this->fail('Adding a job to a task must require the update right on this task');
        } catch (AccessDeniedHttpException $e) {
            $this->assertSame($taskjobs_count, countElementsInTable(PluginGlpiinventoryTaskjob::getTable()));
        }
    }


    public function testItemAddActionRejectsMissingItemtype(): void
    {
        $this->login('glpi', 'glpi');

        $this->expectException(BadRequestHttpException::class);
        (new PluginGlpiinventoryTaskjob())->submitForm([
            'itemaddaction' => 1,
            'methodaction'  => 'glpiinventory||inventory',
        ]);
    }


    public function testConvertCommentEscapesAgentMessage(): void
    {
        $this->login('glpi', 'glpi');
        $computer = $this->createItem(Computer::class, ['name' => 'log_link_computer', 'entities_id' => 0]);

        $converted = PluginGlpiinventoryTaskjoblog::convertComment(
            '<img src=x onerror=alert(1)> [[Computer::' . $computer->getID() . ']]'
        );

        $this->assertStringStartsWith('&lt;img src=x onerror=alert(1)&gt; ', $converted);
        $this->assertStringContainsString($computer->getLink(), $converted);
    }


    public function testAddTaskjoblogStoresCommentAsIs(): void
    {
        $this->login('glpi', 'glpi');
        $taskjoblog = new PluginGlpiinventoryTaskjoblog();

        $taskjoblog->addTaskjoblog(1, 1, Computer::class, PluginGlpiinventoryTaskjoblog::TASK_INFO, "agent's message");

        $this->assertTrue($taskjoblog->getFromDBByCrit(['plugin_glpiinventory_taskjobstates_id' => 1, 'itemtype' => Computer::class]));
        $this->assertSame("agent's message", $taskjoblog->fields['comment']);
    }


    public function testTaskNameSearchQuotesValues(): void
    {
        require_once dirname(__DIR__, 2) . '/hook.php';
        $injected_name = 'x") OR SLEEP(5) OR ("';

        $where = plugin_glpiinventory_addWhere(
            'AND',
            '',
            PluginGlpiinventoryTaskjob::class,
            4,
            json_encode([$injected_name])
        );

        $this->assertSame(
            'AND `glpi_plugin_glpiinventory_tasks`.`name` IN (' . DBmysql::quoteValue($injected_name) . ')',
            $where
        );
    }


    private function createTaskjob(int $entities_id): PluginGlpiinventoryTaskjob
    {
        $task = $this->createItem(PluginGlpiinventoryTask::class, [
            'name'        => 'task_' . $entities_id,
            'entities_id' => $entities_id,
        ]);

        return $this->createItem(PluginGlpiinventoryTaskjob::class, [
            'name'                          => 'job_' . $entities_id,
            'plugin_glpiinventory_tasks_id' => $task->getID(),
            'entities_id'                   => $entities_id,
            'method'                        => 'deployinstall',
        ]);
    }
}
