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

use PHPUnit\Framework\TestCase;

class TaskjobRightsTest extends TestCase
{
    public function testConvertCommentEscapesAgentMessage(): void
    {
        $computer = new Computer();
        $this->assertNotFalse($computer->add(['name' => 'log_link_computer', 'entities_id' => 0]));

        $converted = PluginGlpiinventoryTaskjoblog::convertComment(
            '<img src=x onerror=alert(1)> [[Computer::' . $computer->getID() . ']]'
        );

        $this->assertStringStartsWith('&lt;img src=x onerror=alert(1)&gt; ', $converted);
        $this->assertStringContainsString($computer->getLink(), $converted);
    }


    public function testAddTaskjoblogStoresCommentAsIs(): void
    {
        $taskjoblog = new PluginGlpiinventoryTaskjoblog();

        $taskjoblog->addTaskjoblog(1, 1, Computer::class, PluginGlpiinventoryTaskjoblog::TASK_INFO, "agent's message");

        $this->assertTrue($taskjoblog->getFromDBByCrit(['plugin_glpiinventory_taskjobstates_id' => 1, 'itemtype' => Computer::class]));
        $this->assertSame("agent's message", $taskjoblog->fields['comment']);
    }


    public function testTaskNameSearchQuotesValues(): void
    {
        /** @var DBmysql $DB */
        global $DB;

        require_once dirname(__DIR__, 2) . '/hook.php';
        $injected_name = 'x") OR SLEEP(5) OR (\'';

        $where = plugin_glpiinventory_addWhere(
            'AND',
            '',
            PluginGlpiinventoryTaskjob::class,
            4,
            addslashes(json_encode([$injected_name]))
        );

        $this->assertSame(
            'AND `glpi_plugin_glpiinventory_tasks`.`name` IN (' . DBmysql::quoteValue($DB->escape($injected_name)) . ')',
            $where
        );
    }
}
