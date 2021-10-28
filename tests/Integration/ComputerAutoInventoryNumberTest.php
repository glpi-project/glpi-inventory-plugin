<?php
/**
 *  * ---------------------------------------------------------------------
 *  * GLPI Inventory Plugin
 *  * Copyright (C) 2021 Teclib' and contributors.
 *  *
 *  * http://glpi-project.org
 *  *
 *  * based on FusionInventory for GLPI
 *  * Copyright (C) 2010-2021 by the FusionInventory Development Team.
 *  *
 *  * ---------------------------------------------------------------------
 *  *
 *  * LICENSE
 *  *
 *  * This file is part of GLPI Inventory Plugin.
 *  *
 *  * GLPI Inventory Plugin is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as published by
 *  * the Free Software Foundation, either version 3 of the License, or
 *  * (at your option) any later version.
 *  *
 *  * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  * GNU Affero General Public License for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 *  * ---------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

class ComputerAutoInventoryNumberTest extends TestCase {
   public $a_computer1 = [];

   public static function setUpBeforeClass(): void {

      // Delete all computers
      $computer = new Computer();
      $items = $computer->find();
      foreach ($items as $item) {
         $computer->delete(['id' => $item['id']], true);
      }
   }


   public static function tearDownAfterClass(): void {
      $pfConfig = new PluginGlpiinventoryConfig();

      // Update config
      $pfConfig->updateValue('auto_inventory_number_computer', '');
   }


   function __construct() {
      parent::__construct();
      $this->a_computer1 = [
          "Computer" => [
              "name"        => "pc001",
              "serial"      => "ggheb7ne7",
              "otherserial" => ""
          ],
          "inventorycomputer" => [
              'last_inventory_update' => date('Y-m-d H:i:s'),
              'serialized_inventory'        => 'something'
          ],
          'soundcard'      => [],
          'graphiccard'    => [],
          'controller'     => [],
          'processor'      => [],
          "computerdisk"   => [],
          'memory'         => [],
          'monitor'        => [],
          'printer'        => [],
          'peripheral'     => [],
          'networkport'    => [],
          'software'       => [],
          'harddrive'      => [],
          'virtualmachine' => [],
          'antivirus'      => [],
          'storage'        => [],
          'licenseinfo'    => [],
          'networkcard'    => [],
          'drive'          => [],
          'batteries'      => [],
          'remote_mgmt'    => [],
          'bios'           => [],
          'itemtype'       => 'Computer'
      ];
   }


   /**
    * @test
    */
   public function testInventoryNumberGenerationNewComputer() {

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      $pfiComputerLib   = new PluginGlpiinventoryInventoryComputerLib();
      $computer         = new Computer();
      $pfConfig         = new PluginGlpiinventoryConfig();

      // Update config
      $pfConfig->updateValue('auto_inventory_number_computer', '<PC#####\Y>');

      // Import computer
      $a_computerinventory = $this->a_computer1;
      $a_computer = $a_computerinventory['Computer'];
      $a_computer["entities_id"] = 0;
      $computers_id = $computer->add($a_computer);

      $pfiComputerLib->updateComputer(
         $a_computerinventory,
         $computers_id,
         false,
         1
      );
      $computer->getFromDBByCrit(['name' => 'pc001']);
      $this->assertEquals($computer->fields['otherserial'], 'PC00001'.date('Y'), 'Auto generation of inventory number not works');
   }


   /**
    * @test
    */
   public function testInventoryNumberGenerationUpdateComputer() {
      global $DB;

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      $pfiComputerLib   = new PluginGlpiinventoryInventoryComputerLib();
      $computer         = new Computer();
      $pfConfig         = new PluginGlpiinventoryConfig();

      $computer->getFromDBByCrit(['name' => 'pc001']);
      $computer->update([
         'id'          => $computer->fields['id'],
         'otherserial' => ''
      ]);

      // Because update, delete lock
      $pfLock = new PluginGlpiinventoryLock();
      $DB->query('DELETE FROM glpi_plugin_glpiinventory_locks');

      $computer = new Computer();
      $computer->getFromDBByCrit(['name' => 'pc001']);
      $this->assertEquals('', $computer->fields['otherserial']);

      $pfConfig->updateValue('auto_inventory_number_computer', '');

      $pfiComputerLib->updateComputer(
         $this->a_computer1,
         $computer->fields['id'],
         false,
         1
      );
      $computer = new Computer();
      $computer->getFromDBByCrit(['name' => 'pc001']);
      $this->assertEquals('', $computer->fields['otherserial'], 'Inventory number must be empty');

      // Update config
      $pfConfig->updateValue('auto_inventory_number_computer', '<PC#####\Y>');

      $pfiComputerLib->updateComputer(
         $this->a_computer1,
         $computer->fields['id'],
         false,
         1
      );
      $computer = new Computer();
      $computer->getFromDBByCrit(['name' => 'pc001']);
      $this->assertEquals($computer->fields['otherserial'], 'PC00001'.date('Y'), 'Auto generation of inventory number not works');
   }
}
