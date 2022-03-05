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

class GetTaskByFiltersTest extends TestCase
{
   /**
    * @test
    */
    public function GetTaskWithoutJobs()
    {

        $pfTask = new PluginGlpiinventoryTask();

       // create task
        $input = [
          'entities_id' => 0,
          'name'        => 'deploy',
          'is_active'   => 1
        ];
        $pfTask->add($input);

        $running_tasks = $pfTask->getItemsFromDB(
            [
            'is_running'  => true,
            'is_active'   => true
            ]
        );
        $this->assertEquals([], $running_tasks, 'Not find task because not have job');
    }
}
