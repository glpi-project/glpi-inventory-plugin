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

class TaskCsvExportTest extends DbTestCase
{
    /** @return array<string, string> */
    private function exec(string $state, string $message): array
    {
        return ['last_log_date' => '2026-06-29 16:18:02', 'state' => $state, 'last_log' => $message];
    }

    // Builds a minimal $data
    /**
     * @param array<int, array<string, string>> $execs
     * @return array<string, mixed>
     */
    private function dataWith(array $execs): array
    {
        return ['tasks' => [1 => [
            'task_name' => 'Test task',
            'jobs'      => [10 => [
                'name'    => 'Test job',
                'method'  => 'inventory',
                'targets' => [99 => [
                    'name'   => 'Test target',
                    // agent_id = 0 → Agent::getFromDB() fails, so the name stays empty
                    'agents' => [0 => $execs],
                ]],
            ]],
        ]]];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, array<int|string, mixed>>
     */
    private function getRows(array $data, int $includeoldjobs = -1): array
    {
        $sheet = (new PluginGlpiinventoryTaskView())
            ->buildSpreadsheet($data, $includeoldjobs)
            ->getActiveSheet();
        return $sheet->toArray(null, true, false);
    }

    // The header row must be present and well formed
    public function testHeaders(): void
    {
        $rows = $this->getRows(['tasks' => []]);
        $this->assertSame(
            ['Task_name', 'Job_name', 'Method', 'Target', 'Agent', 'Computer name', 'Date', 'Status', 'Last Message'],
            $rows[0]
        );
    }

    // A single execution must fill the 9 expected columns
    public function testSingleExecution(): void
    {
        $rows = $this->getRows($this->dataWith([$this->exec('success', 'OK')]));

        // Row 0 = headers, row 1 = first execution.
        $this->assertSame('Test task', $rows[1][0]);
        $this->assertSame('Test job', $rows[1][1]);
        $this->assertSame('inventory', $rows[1][2]);
        $this->assertSame('Test target', $rows[1][3]);
        $this->assertSame('2026-06-29 16:18:02', $rows[1][6]);
        $this->assertSame('success', $rows[1][7]);
        $this->assertSame('OK', $rows[1][8]);
    }

    // With several executions, the parent columns must be repeated on every row
    public function testParentColumnsRepeated(): void
    {
        $rows = $this->getRows($this->dataWith([
            $this->exec('success', 'OK'),
            $this->exec('error', 'KO'),
        ]));

        $this->assertSame('Test task', $rows[2][0]);
        $this->assertSame('Test job', $rows[2][1]);
        $this->assertSame('Test target', $rows[2][3]);
        $this->assertSame('KO', $rows[2][8]);
    }

    // a message containing a semicolon,  double quotes or a newline must stay inside a single cell
    public function testMessageWithSpecialCharacters(): void
    {
        $multiline_message = "Line 1\nLine 2 with \"quotes\"\nLine 3;and;semicolons";

        $rows = $this->getRows($this->dataWith([
            $this->exec('error', 'Path not found; aborted'),
            $this->exec('success', $multiline_message),
        ]));

        // 1 header row + 2 execution rows = 3 rows
        $this->assertCount(3, $rows);
        $this->assertSame('Path not found; aborted', $rows[1][8]);
        $this->assertSame($multiline_message, $rows[2][8]);
    }

    // The $includeoldjobs parameter must cap the number of jobs per task
    public function testJobCountLimit(): void
    {
        $jobWithExec = fn(string $name) => [
            'name'    => $name,
            'method'  => 'inventory',
            'targets' => [99 => [
                'name'   => 'Target',
                'agents' => [0 => [$this->exec('success', $name)]],
            ]],
        ];

        $data = ['tasks' => [1 => [
            'task_name' => 'T',
            'jobs'      => [
                1 => $jobWithExec('first'),
                2 => $jobWithExec('second'),
                3 => $jobWithExec('third'), // must be skipped
            ],
        ]]];

        $rows = $this->getRows($data, 2);

        // 1 header + 2 kept jobs
        $this->assertCount(3, $rows);
        $this->assertSame('first', $rows[1][8]);
        $this->assertSame('second', $rows[2][8]);
    }

    // Computer  - agent / Columns are populated
    public function testAgentAndComputerColumnsArePopulated(): void
    {
        $computer_id = $this->createItem(Computer::class, [
            'name'        => 'pc test',
            'entities_id' => 0,
        ])->getID();

        // agent requires fk agentType
        $agenttype_id = $this->createItem(AgentType::class, [
            'name' => 'type test',
        ])->getID();

        $agent_id = $this->createItem(Agent::class, [
            'name'          => 'agent test',
            'entities_id'   => 0,
            'itemtype'      => Computer::class,
            'items_id'      => $computer_id,
            'deviceid'      => 'deviceid test',
            'agenttypes_id' => $agenttype_id,
        ])->getID();

        $data = ['tasks' => [1 => [
            'task_name' => 'test task',
            'jobs'      => [10 => [
                'name'    => 'test job',
                'method'  => 'inventory',
                'targets' => [99 => [
                    'name'   => 'test target',
                    'agents' => [$agent_id => [$this->exec('success', 'OK')]],
                ]],
            ]],
        ]]];

        $rows = $this->getRows($data);

        $this->assertSame('agent test', $rows[1][4]);
        $this->assertSame('pc test', $rows[1][5]);
    }
}
