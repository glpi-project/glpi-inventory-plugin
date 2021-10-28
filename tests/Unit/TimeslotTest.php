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
 * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

class TimeslotTest extends TestCase {

   public static function setUpBeforeClass(): void {
      // Delete all timeslots
      $pfTimeslot = new PluginGlpiinventoryTimeslot();
      $items = $pfTimeslot->find();
      foreach ($items as $item) {
         $pfTimeslot->delete(['id' => $item['id']], true);
      }
   }


   /**
    * @test
    */
   public function addTimeslot() {
      $pfTimeslot = new PluginGlpiinventoryTimeslot();
      $input = [
          'entities_id'  => 0,
          'is_recursive' => 0,
          'name'         => 'unitdefault'
      ];
      $pfTimeslot->add($input);
      $cnt = countElementsInTable('glpi_plugin_glpiinventory_timeslots');
      $this->assertEquals(1, $cnt, "Timeslot may be added");
   }


   /**
    * @test
    */
   public function addSimpleEntrieslot() {
      $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
      $pfTimeslot = new PluginGlpiinventoryTimeslot();

      $pfTimeslot->getFromDBByCrit(['name' => 'unitdefault']);

      $input = [
          'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
          'entities_id'  => 0,
          'is_recursive' => 0,
          'day'          => 1,
          'begin'        => 7215,
          'end'          => 43200
      ];
      $pfTimeslotEntry->add($input);

      $input = [
          'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
          'entities_id'  => 0,
          'is_recursive' => 0,
          'day'          => 1,
          'begin'        => 72000,
          'end'          => 79200
      ];
      $pfTimeslotEntry->add($input);

      $input = [
          'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
          'entities_id'  => 0,
          'is_recursive' => 0,
          'day'          => 3,
          'begin'        => 39600,
          'end'          => 79200
      ];
      $pfTimeslotEntry->add($input);

      $references = [
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 7215,
            'end'          => 43200
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 72000,
            'end'          => 79200
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 3,
            'begin'        => 39600,
            'end'          => 79200
         ]
      ];
      $a_data = getAllDataFromTable('glpi_plugin_glpiinventory_timeslotentries');
      $items = [];
      foreach ($a_data as $data) {
         unset($data['id']);
         $items[] = $data;
      }

      $this->assertEquals($references, $items, "May have 3 entries");
   }


   /**
    * @test
    */
   public function addEntriesTimeslotYetAdded() {

      $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
      $pfTimeslot = new PluginGlpiinventoryTimeslot();

      $pfTimeslot->getFromDBByCrit(['name' => 'unitdefault']);

      $input = [
          'timeslots_id' => $pfTimeslot->fields['id'],
          'beginday'     => 1,
          'lastday'      => 1,
          'beginhours'   => 7230,
          'lasthours'    => 43140
      ];
      $pfTimeslotEntry->addEntry($input);

      $input = [
          'timeslots_id' => $pfTimeslot->fields['id'],
          'beginday'     => 1,
          'lastday'      => 1,
          'beginhours'   => 72000,
          'lasthours'    => 79140
      ];
      $pfTimeslotEntry->addEntry($input);

      $references = [
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 7215,
            'end'          => 43200
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 72000,
            'end'          => 79200
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 3,
            'begin'        => 39600,
            'end'          => 79200
         ]
      ];
      $a_data = getAllDataFromTable('glpi_plugin_glpiinventory_timeslotentries', ['ORDER' => 'id']);
      $items = [];
      foreach ($a_data as $data) {
         unset($data['id']);
         $items[] = $data;
      }
      $this->assertEquals($references, $items, "May have 2 entries ".print_r($items, true));
   }


   /**
    * @test
    */
   public function addEntriesTimeslotNotInRanges() {

      $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
      $pfTimeslot = new PluginGlpiinventoryTimeslot();

      $pfTimeslot->getFromDBByCrit(['name' => 'unitdefault']);

      $input = [
          'timeslots_id' => $pfTimeslot->fields['id'],
          'beginday'     => 1,
          'lastday'      => 1,
          'beginhours'   => 15,
          'lasthours'    => 30
      ];
      $pfTimeslotEntry->addEntry($input);

      $references = [
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 7215,
            'end'          => 43200
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 72000,
            'end'          => 79200
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 3,
            'begin'        => 39600,
            'end'          => 79200
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 15,
            'end'          => 30
         ]
      ];
      $a_data = getAllDataFromTable('glpi_plugin_glpiinventory_timeslotentries');
      $items = [];
      foreach ($a_data as $data) {
         unset($data['id']);
         $items[] = $data;
      }
      $this->assertEquals($references, $items, "May have 3 entries ".print_r($items, true));
   }


   /**
    * @test
    */
   public function addEntryIn3Ranges() {

      $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
      $pfTimeslot = new PluginGlpiinventoryTimeslot();

      $pfTimeslot->getFromDBByCrit(['name' => 'unitdefault']);

      $input = [
          'timeslots_id' => $pfTimeslot->fields['id'],
          'beginday'     => 1,
          'lastday'      => 1,
          'beginhours'   => 0,
          'lasthours'    => 79215
      ];
      $pfTimeslotEntry->addEntry($input);

      $references = [
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 3,
            'begin'        => 39600,
            'end'          => 79200
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 0,
            'end'          => 79215
         ]
      ];
      $a_data = getAllDataFromTable('glpi_plugin_glpiinventory_timeslotentries');
      $items = [];
      foreach ($a_data as $data) {
         unset($data['id']);
         $items[] = $data;
      }
      $this->assertEquals($references, $items, "May have 2 entries ".print_r($items, true));
   }


   /**
    * @test
    */
   public function addEntryForTwoDays() {

      $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
      $pfTimeslot = new PluginGlpiinventoryTimeslot();

      $pfTimeslot->getFromDBByCrit(['name' => 'unitdefault']);

      $input = [
          'timeslots_id' => $pfTimeslot->fields['id'],
          'beginday'     => 1,
          'lastday'      => 4,
          'beginhours'   => 79230,
          'lasthours'    => 36000
      ];
      $pfTimeslotEntry->addEntry($input);

      $references = [
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 0,
            'end'          => 79215
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 79230,
            'end'          => 86400
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 2,
            'begin'        => 0,
            'end'          => 86400
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 3,
            'begin'        => 0,
            'end'          => 86400
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 4,
            'begin'        => 0,
            'end'          => 36000
         ],
      ];
      $a_data = getAllDataFromTable('glpi_plugin_glpiinventory_timeslotentries');
      $items = [];
      foreach ($a_data as $data) {
         unset($data['id']);
         $items[] = $data;
      }
      $this->assertEquals($references, $items, "May have 4 entries ".print_r($items, true));
   }


   /**
    * @test
    */
   public function addEntryForTwoDaysYetAdded() {

      $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
      $pfTimeslot = new PluginGlpiinventoryTimeslot();

      $pfTimeslot->getFromDBByCrit(['name' => 'unitdefault']);

      $input = [
          'timeslots_id' => $pfTimeslot->fields['id'],
          'beginday'     => 2,
          'lastday'      => 3,
          'beginhours'   => 60,
          'lasthours'    => 36015
      ];
      $pfTimeslotEntry->addEntry($input);

      $references = [
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 0,
            'end'          => 79215
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 1,
            'begin'        => 79230,
            'end'          => 86400
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 2,
            'begin'        => 0,
            'end'          => 86400
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 3,
            'begin'        => 0,
            'end'          => 86400
         ],
         [
            'entities_id'  => 0,
            'plugin_glpiinventory_timeslots_id' => $pfTimeslot->fields['id'],
            'is_recursive' => 0,
            'day'          => 4,
            'begin'        => 0,
            'end'          => 36000
         ],
      ];
      $a_data = getAllDataFromTable('glpi_plugin_glpiinventory_timeslotentries');
      $items = [];
      foreach ($a_data as $data) {
         unset($data['id']);
         $items[] = $data;
      }
      $this->assertEquals($references, $items, "May have 4 entries ".print_r($items, true));
   }
}
