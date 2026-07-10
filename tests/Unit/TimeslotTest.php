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

class TimeslotTest extends DbTestCase
{
    public function testAddTimeslot()
    {
        $pfTimeslot = new PluginGlpiinventoryTimeslot();
        $input = [
            'entities_id'  => 0,
            'is_recursive' => 0,
            'name'         => 'unitdefault',
        ];
        $pfTimeslot->add($input);
        $cnt = countElementsInTable('glpi_plugin_glpiinventory_timeslots');
        $this->assertEquals(1, $cnt, "Timeslot may be added");
    }


    public function testAddSimpleEntrieslot()
    {
        $this->testAddTimeslot();
        $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
        $pfTimeslot = new PluginGlpiinventoryTimeslot();

        $pfTimeslot->getFromDBByCrit(['name' => 'unitdefault']);

        $input = [
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'entities_id'  => 0,
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 7215,
            'end'          => 43200,
        ];
        $pfTimeslotEntry->add($input);

        $input = [
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'entities_id'  => 0,
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 72000,
            'end'          => 79200,
        ];
        $pfTimeslotEntry->add($input);

        $input = [
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'entities_id'  => 0,
            'is_recursive' => 0,
            'day'          => 3,
            'begin'        => 39600,
            'end'          => 79200,
        ];
        $pfTimeslotEntry->add($input);

        $references = [
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 1,
                'begin'        => 7215,
                'end'          => 43200,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 1,
                'begin'        => 72000,
                'end'          => 79200,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 3,
                'begin'        => 39600,
                'end'          => 79200,
            ],
        ];
        $a_data = getAllDataFromTable('glpi_plugin_glpiinventory_timeslotentries');
        $items = [];
        foreach ($a_data as $data) {
            unset($data['id']);
            $items[] = $data;
        }

        $this->assertEquals($references, $items, "May have 3 entries");
    }


    public function testAddEntriesTimeslotYetAdded()
    {
        $this->testAddSimpleEntrieslot();
        $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
        $pfTimeslot = new PluginGlpiinventoryTimeslot();

        $pfTimeslot->getFromDBByCrit(['name' => 'unitdefault']);

        $input = [
            'timeslots_id' => $pfTimeslot->fields['id'],
            'beginday'     => 1,
            'lastday'      => 1,
            'beginhours'   => 7230,
            'lasthours'    => 43140,
        ];
        $pfTimeslotEntry->addEntry($input);

        $input = [
            'timeslots_id' => $pfTimeslot->fields['id'],
            'beginday'     => 1,
            'lastday'      => 1,
            'beginhours'   => 72000,
            'lasthours'    => 79140,
        ];
        $pfTimeslotEntry->addEntry($input);

        $references = [
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 1,
                'begin'        => 7215,
                'end'          => 43200,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 1,
                'begin'        => 72000,
                'end'          => 79200,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 3,
                'begin'        => 39600,
                'end'          => 79200,
            ],
        ];
        $a_data = getAllDataFromTable('glpi_plugin_glpiinventory_timeslotentries', ['ORDER' => 'id']);
        $items = [];
        foreach ($a_data as $data) {
            unset($data['id']);
            $items[] = $data;
        }
        $this->assertEquals($references, $items, "May have 2 entries " . print_r($items, true));
    }


    public function testAddEntriesTimeslotNotInRanges()
    {
        $this->testAddSimpleEntrieslot();
        $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
        $pfTimeslot = new PluginGlpiinventoryTimeslot();

        $pfTimeslot->getFromDBByCrit(['name' => 'unitdefault']);

        $input = [
            'timeslots_id' => $pfTimeslot->fields['id'],
            'beginday'     => 1,
            'lastday'      => 1,
            'beginhours'   => 15,
            'lasthours'    => 30,
        ];
        $pfTimeslotEntry->addEntry($input);

        $references = [
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 1,
                'begin'        => 7215,
                'end'          => 43200,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 1,
                'begin'        => 72000,
                'end'          => 79200,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 3,
                'begin'        => 39600,
                'end'          => 79200,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 1,
                'begin'        => 15,
                'end'          => 30,
            ],
        ];
        $a_data = getAllDataFromTable('glpi_plugin_glpiinventory_timeslotentries');
        $items = [];
        foreach ($a_data as $data) {
            unset($data['id']);
            $items[] = $data;
        }
        $this->assertEquals($references, $items, "May have 3 entries " . print_r($items, true));
    }


    public function testAddEntryIn3Ranges()
    {
        $this->testAddSimpleEntrieslot();
        $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
        $pfTimeslot = new PluginGlpiinventoryTimeslot();

        $pfTimeslot->getFromDBByCrit(['name' => 'unitdefault']);

        $input = [
            'timeslots_id' => $pfTimeslot->fields['id'],
            'beginday'     => 1,
            'lastday'      => 1,
            'beginhours'   => 0,
            'lasthours'    => 79215,
        ];
        $pfTimeslotEntry->addEntry($input);

        $references = [
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 3,
                'begin'        => 39600,
                'end'          => 79200,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 1,
                'begin'        => 0,
                'end'          => 79215,
            ],
        ];
        $a_data = getAllDataFromTable('glpi_plugin_glpiinventory_timeslotentries');
        $items = [];
        foreach ($a_data as $data) {
            unset($data['id']);
            $items[] = $data;
        }
        $this->assertEquals($references, $items, "May have 2 entries " . print_r($items, true));
    }


    public function testAddEntryForTwoDays()
    {
        $this->testAddEntryIn3Ranges();
        $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
        $pfTimeslot = new PluginGlpiinventoryTimeslot();

        $pfTimeslot->getFromDBByCrit(['name' => 'unitdefault']);

        $input = [
            'timeslots_id' => $pfTimeslot->fields['id'],
            'beginday'     => 1,
            'lastday'      => 4,
            'beginhours'   => 79230,
            'lasthours'    => 36000,
        ];
        $pfTimeslotEntry->addEntry($input);

        $references = [
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 1,
                'begin'        => 0,
                'end'          => 79215,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 1,
                'begin'        => 79230,
                'end'          => 86400,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 2,
                'begin'        => 0,
                'end'          => 86400,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 3,
                'begin'        => 0,
                'end'          => 86400,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 4,
                'begin'        => 0,
                'end'          => 36000,
            ],
        ];
        $a_data = getAllDataFromTable('glpi_plugin_glpiinventory_timeslotentries');
        $items = [];
        foreach ($a_data as $data) {
            unset($data['id']);
            $items[] = $data;
        }
        $this->assertEquals($references, $items, "May have 4 entries " . print_r($items, true));
    }


    public function testAddEntryForTwoDaysYetAdded()
    {
        $this->testAddEntryForTwoDays();
        $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
        $pfTimeslot = new PluginGlpiinventoryTimeslot();

        $pfTimeslot->getFromDBByCrit(['name' => 'unitdefault']);

        $input = [
            'timeslots_id' => $pfTimeslot->fields['id'],
            'beginday'     => 2,
            'lastday'      => 3,
            'beginhours'   => 60,
            'lasthours'    => 36015,
        ];
        $pfTimeslotEntry->addEntry($input);

        $references = [
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 1,
                'begin'        => 0,
                'end'          => 79215,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 1,
                'begin'        => 79230,
                'end'          => 86400,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 2,
                'begin'        => 0,
                'end'          => 86400,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 3,
                'begin'        => 0,
                'end'          => 86400,
            ],
            [
                'entities_id'  => 0,
                'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
                'is_recursive' => 0,
                'day'          => 4,
                'begin'        => 0,
                'end'          => 36000,
            ],
        ];
        $a_data = getAllDataFromTable('glpi_plugin_glpiinventory_timeslotentries');
        $items = [];
        foreach ($a_data as $data) {
            unset($data['id']);
            $items[] = $data;
        }
        $this->assertEquals($references, $items, "May have 4 entries " . print_r($items, true));
    }


    /**
     * Invoke the private getWeekGrid() method.
     *
     * @param array<int,string> $days
     * @param array<int,array<array<string,mixed>>> $entries_by_day
     * @return array<array{day:int,label:string,cells:array<bool>}>
     */
    private function callGetWeekGrid(array $days, array $entries_by_day): array
    {
        $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
        $method = new ReflectionMethod($pfTimeslotEntry, 'getWeekGrid');
        return $method->invoke($pfTimeslotEntry, $days, $entries_by_day);
    }


    public function testWeekGridFullDay()
    {
        // A single entry covering the whole day, up to the 86400 upper boundary.
        $days = [1 => 'Monday'];
        $entries_by_day = [
            1 => [
                ['begin' => 0, 'end' => 24 * 3600],
            ],
        ];

        $grid = $this->callGetWeekGrid($days, $entries_by_day);

        $this->assertCount(1, $grid);
        $this->assertSame(1, $grid[0]['day']);
        $this->assertSame('Monday', $grid[0]['label']);
        // 96 quarter-hour cells, all active: the end == 86400 boundary must not
        // leave the last (23:45 -> 24:00) cell inactive.
        $this->assertCount(96, $grid[0]['cells']);
        $this->assertSame(
            array_fill(0, 96, true),
            $grid[0]['cells'],
            "A full-day entry must activate every cell, including the last one"
        );
    }


    public function testWeekGridMiddayBoundaries()
    {
        // Noon splits the day exactly on cell 48 (43200 / 900 = 48).
        $days = [1 => 'Monday', 2 => 'Tuesday'];
        $entries_by_day = [
            // Morning only: [00:00, 12:00[
            1 => [
                ['begin' => 0, 'end' => 12 * 3600],
            ],
            // Afternoon only: [12:00, 24:00[
            2 => [
                ['begin' => 12 * 3600, 'end' => 24 * 3600],
            ],
        ];

        $grid = $this->callGetWeekGrid($days, $entries_by_day);

        $this->assertCount(2, $grid);

        // Morning: cells 0..47 active, 48..95 inactive.
        $morning = $grid[0]['cells'];
        $this->assertCount(96, $morning);
        for ($i = 0; $i < 96; $i++) {
            $this->assertSame(
                $i < 48,
                $morning[$i],
                "Morning cell $i should be " . ($i < 48 ? 'active' : 'inactive')
            );
        }
        // The 11:45 -> 12:00 cell is active, the 12:00 -> 12:15 cell is not.
        $this->assertTrue($morning[47]);
        $this->assertFalse($morning[48]);

        // Afternoon: cells 0..47 inactive, 48..95 active.
        $afternoon = $grid[1]['cells'];
        $this->assertCount(96, $afternoon);
        for ($i = 0; $i < 96; $i++) {
            $this->assertSame(
                $i >= 48,
                $afternoon[$i],
                "Afternoon cell $i should be " . ($i >= 48 ? 'active' : 'inactive')
            );
        }
        // The noon boundary belongs to the afternoon entry.
        $this->assertFalse($afternoon[47]);
        $this->assertTrue($afternoon[48]);
    }


    public function testWeekGridEmptyDay()
    {
        // A day with no entry must produce 96 inactive cells
        $days = [1 => 'Monday'];

        $grid = $this->callGetWeekGrid($days, []);

        $this->assertCount(1, $grid);
        $this->assertCount(96, $grid[0]['cells']);
        $this->assertSame(array_fill(0, 96, false), $grid[0]['cells']);
    }
}
