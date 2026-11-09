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

class CleanTaskjobCronTest extends DbTestCase
{
    public function testRepeatedRunsDoNotSetErrorState(): void
    {
        for ($i = 0; $i < CronTask::MAX_ERROR_COUNT; $i++) {
            $this->assertSame('cleantaskjob', CronTask::launch(-CronTask::MODE_EXTERNAL, 1, 'cleantaskjob'));
        }

        $crontask = new CronTask();
        $this->assertTrue($crontask->getFromDBbyName(PluginGlpiinventoryTaskjobstate::class, 'cleantaskjob'));
        $this->assertSame(0, $crontask->fields['error_count']);
        $this->assertNotSame(CronTask::STATE_ERROR, $crontask->fields['state']);
    }

    public function testReturnValue(): void
    {
        global $DB;

        $this->assertSame(0, PluginGlpiinventoryTaskjobstate::cronCleantaskjob());

        $this->assertTrue($DB->insert(PluginGlpiinventoryTaskjobstate::getTable(), ['uniqid' => 'cleantaskjob-test']));
        $states_id = $DB->insertId();
        $this->assertTrue($DB->insert(PluginGlpiinventoryTaskjoblog::getTable(), [
            'plugin_glpiinventory_taskjobstates_id' => $states_id,
            'date' => '2000-01-01 00:00:00',
        ]));

        $this->assertSame(1, PluginGlpiinventoryTaskjobstate::cronCleantaskjob());
        $this->assertSame(0, countElementsInTable(PluginGlpiinventoryTaskjoblog::getTable(), ['plugin_glpiinventory_taskjobstates_id' => $states_id]));
        $this->assertSame(0, countElementsInTable(PluginGlpiinventoryTaskjobstate::getTable(), ['id' => $states_id]));
    }
}
