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

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Glpiinventory\Enums\TaskJobLogsTypes;
use GlpiPlugin\Glpiinventory\Job\Types\Generic;

class DeleteTaskTest extends DbTestCase
{
    private int $taskId = 0;
    private int $taskjobId = 0;
    private int $taskjobstateId = 0;
    private int $taskjoblogId = 0;


    public function prepareDb(): void
    {
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $pfDeployGroup   = new PluginGlpiinventoryDeployGroup();
        $pfTask          = new PluginGlpiinventoryTask();
        $pfTaskjob       = new PluginGlpiinventoryTaskjob();
        $pfTaskjobState  = new PluginGlpiinventoryTaskjobstate();
        $pfTaskjoblog    = new PluginGlpiinventoryTaskjoblog();
        $pfDeployGrDyndata = new PluginGlpiinventoryDeployGroup_Dynamicdata();

        // Create package
        $input = [
            'entities_id' => 0,
            'name'        => 'package',
        ];
        $packageId = $pfDeployPackage->add($input);

        // Create dynamic group
        $input = [
            'name' => 'all computers have name computer',
            'type' => 'DYNAMIC',
        ];
        $groupId = $pfDeployGroup->add($input);

        $input = [
            'plugin_glpiinventory_deploygroups_id' => $groupId,
            'fields_array' => '{"criteria":[{"field":"1","searchtype":"contains","value":"computer"}],"metacriteria":""}',
        ];
        $pfDeployGrDyndata->add($input);

        // create task
        $input = [
            'entities_id' => 0,
            'name' => 'deploy',
            'is_active' => 1,
        ];
        $this->taskId = $pfTask->add($input);

        // create taskjob
        $input = [
            'plugin_glpiinventory_tasks_id' => $this->taskId,
            'entities_id' => 0,
            'name' => 'deploy',
            'method' => 'deployinstall',
            'targets' => '[{"PluginGlpiinventoryDeployPackage":"' . $packageId . '"}]',
            'actors' => '[{"PluginGlpiinventoryDeployGroup":"' . $this->taskId . '"}]',
        ];
        $this->taskjobId = $pfTaskjob->add($input);

        //create taskjobstate
        $input = [
            'plugin_glpiinventory_taskjobs_id' => $this->taskjobId,
            'items_id' => 0,
            'itemtype' => Computer::class,
            'state' => PluginGlpiinventoryTaskjobstate::FINISHED,
            'agents_id' => 0,
            'specificity' => 0,
            'uniqid' => 0,

        ];
        $this->taskjobstateId = $pfTaskjobState->add($input);

        //create taskjoblog
        $this->taskjoblogId = $pfTaskjoblog->addJobLog(
            taskjobs_id: $this->taskjobstateId,
            items_id: 0,
            itemtype: Computer::class,
            state: PluginGlpiinventoryTaskjoblog::TASK_RUNNING,
            comment: new Generic(type: TaskJobLogsTypes::DEVICES_FOUND),
        );
    }

    public function testDeleteTask(): void
    {
        $this->prepareDb();
        $pfTask         = new PluginGlpiinventoryTask();
        $pfTaskjob      = new PluginGlpiinventoryTaskjob();
        $pfTaskjobState = new PluginGlpiinventoryTaskjobstate();
        $pfTaskjoblog   = new PluginGlpiinventoryTaskjoblog();

        //delete task
        $return = $pfTask->delete(['id' => $this->taskId]);
        $this->assertEquals(true, $return);

        //check deletion of job
        $jobsFound = $pfTaskjob->find(['id' => $this->taskjobId]);
        $this->assertEquals([], $jobsFound);

        //check deletion of state
        $statesFound = $pfTaskjobState->find(['id' => $this->taskjobstateId]);
        $this->assertEquals([], $statesFound);

        //check deletion of log
        $logsFound = $pfTaskjoblog->find(['id' => $this->taskjoblogId]);
        $this->assertEquals([], $logsFound);
    }
}
