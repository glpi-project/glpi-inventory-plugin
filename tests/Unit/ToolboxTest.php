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

class ToolboxTest extends TestCase {

   public $formatJson_input = [
      'test_text' => 'Lorem Ipsum',
      'test_number' => 1234,
      'test_float' => 1234.5678,
      'test_array' => [ 1,2,3,4, 'lorem_ipsum' ],
      'test_hash' => ['lorem' => 'ipsum', 'ipsum' => 'lorem']
   ];

   public $formatJson_expected = <<<JSON
{
    "test_text": "Lorem Ipsum",
    "test_number": 1234,
    "test_float": 1234.5678,
    "test_array": [
        1,
        2,
        3,
        4,
        "lorem_ipsum"
    ],
    "test_hash": {
        "lorem": "ipsum",
        "ipsum": "lorem"
    }
}
JSON;


   /**
    * @test
    */
   public function formatJson() {

      $this->assertEquals(
         $this->formatJson_expected,
         PluginGlpiinventoryToolbox::formatJson(json_encode($this->formatJson_input))
      );
   }


   /**
    * @test
    */
   public function isAnInventoryDevice() {
      $computer = new Computer();

      $this->assertFalse(PluginGlpiinventoryToolbox::isAnInventoryDevice($computer));

      $values = ['name'         => 'comp',
                 'is_dynamic'   => 1,
                 'entities_id'  => 0,
                 'is_recursive' => 0];
      $computers_id = $computer->add($values);
      $computer->getFromDB($computers_id);

      $this->assertFalse(PluginGlpiinventoryToolbox::isAnInventoryDevice($computer));

      $pfComputer = new PluginGlpiinventoryInventoryComputerComputer();
      $pfComputer->add(['computers_id' => $computers_id]);
      $this->assertTrue(PluginGlpiinventoryToolbox::isAnInventoryDevice($computer));

      $printer = new Printer();
      $values  = ['name'         => 'printer',
                  'is_dynamic'   => 1,
                  'entities_id'  => 0,
                  'is_recursive' => 0];
      $printers_id = $printer->add($values);
      $printer->getFromDB($printers_id);
      $this->assertFalse(PluginGlpiinventoryToolbox::isAnInventoryDevice($printer));

      $pfPrinter = new PluginGlpiinventoryPrinter();
      $pfPrinter->add(['printers_id' => $printers_id]);
      $this->assertTrue(PluginGlpiinventoryToolbox::isAnInventoryDevice($printer));

      $values  = ['name'         => 'printer2',
                  'is_dynamic'   => 0,
                  'entities_id'  => 0,
                  'is_recursive' => 0];
      $printers_id_2 = $printer->add($values);
      $printer->getFromDB($printers_id_2);
      $pfPrinter->add(['printers_id' => $printers_id_2]);
      $this->assertFalse(PluginGlpiinventoryToolbox::isAnInventoryDevice($printer));

   }


   /**
    * @test
    */
   public function addDefaultStateIfNeeded() {
      $input = [];

      $state = new State();
      $states_id_computer = $state->importExternal('state_computer');
      $states_id_snmp = $state->importExternal('state_snmp');

      $config = new PluginGlpiinventoryConfig();
      $config->updateValue('states_id_snmp_default', $states_id_snmp);
      $config->updateValue('states_id_default', $states_id_computer);

      $result = PluginGlpiinventoryToolbox::addDefaultStateIfNeeded('computer', $input);
      $this->assertEquals(['states_id' => $states_id_computer], $result);

      $result = PluginGlpiinventoryToolbox::addDefaultStateIfNeeded('snmp', $input);
      $this->assertEquals(['states_id' => $states_id_snmp], $result);

      $result = PluginGlpiinventoryToolbox::addDefaultStateIfNeeded('foo', $input);
      $this->assertEquals([], $result);

      // Redo default
      $config->updateValue('states_id_snmp_default', 0);
      $config->updateValue('states_id_default', 0);
   }
}
