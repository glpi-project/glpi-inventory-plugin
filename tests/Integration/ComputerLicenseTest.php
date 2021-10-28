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

class ComputerLicenseTest extends TestCase {
   public $a_computer1 = [];
   public $a_computer1_beforeformat = [];

   public static function setUpBeforeClass(): void {

      // Delete all computers
      $computer = new Computer();
      $items = $computer->find();
      foreach ($items as $item) {
         $computer->delete(['id' => $item['id']], true);
      }
   }


   /*
    * Why do you define a constructor here while you can set this 2 variables up ahead ???
    */
   function __construct() {
      parent::__construct();
      $this->a_computer1 = [
          "Computer" => [
              "name"   => "pc001",
              "serial" => "ggheb7ne7"
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
          'licenseinfo'    => [
              [
                  'name'     => 'Microsoft Office 2003',
                  'fullname' => 'Microsoft Office Professional Edition 2003',
                  'serial'   => 'xxxxx-xxxxx-P6RC4-xxxxx-xxxxx'
              ]
          ],
          'networkcard'    => [],
          'drive'          => [],
          'batteries'      => [],
          'remote_mgmt'    => [],
          'bios'           => [],
          'powersupply'    => [],
          'itemtype'       => 'Computer'
      ];

      $this->a_computer1_beforeformat = [
          "CONTENT" => [
              "HARDWARE" => [
                  "NAME"   => "pc001"
              ],
              "BIOS" => [
                  "SSN" => "ggheb7ne7"
              ],
              'LICENSEINFOS' => [
                  [
                      'COMPONENTS' => 'Word/Excel/Access/Outlook/PowerPoint/Publisher/InfoPath',
                      'FULLNAME'   => 'Microsoft Office Professional Edition 2003',
                      'KEY'        => 'xxxxx-xxxxx-P6RC4-xxxxx-xxxxx',
                      'NAME'       => 'Microsoft Office 2003',
                      'PRODUCTID'  => 'xxxxx-640-0000xxx-xxxxx'
                  ]
              ]
          ]
      ];
   }


   /**
    * @test
    */
   public function testAddLicensesWhenInventory() {

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      $pfiComputerLib   = new PluginGlpiinventoryInventoryComputerLib();
      $computer         = new Computer();

      $a_computerinventory = $this->a_computer1;
      $a_computer = $a_computerinventory['Computer'];
      $a_computer["entities_id"] = 0;
      $computers_id = $computer->add($a_computer);

      $pfiComputerLib->updateComputer($a_computerinventory,
                                      $computers_id,
                                      false,
                                      1);

      $computer->getFromDBByCrit(['name' => 'pc001']);
      $this->assertEquals('ggheb7ne7', $computer->fields['serial'], 'Computer not updated correctly');

      $this->assertEquals(1,
                          countElementsInTable('glpi_plugin_glpiinventory_computerlicenseinfos'),
                          'License may be added in table');

      $pfComputerLicenseInfo = new PluginGlpiinventoryComputerLicenseInfo();
      $pfComputerLicenseInfo->getFromDBByCrit(['name' => 'Microsoft Office 2003']);
      $a_ref = [
          'id'                   => $pfComputerLicenseInfo->fields['id'],
          'computers_id'         => $computer->fields['id'],
          'softwarelicenses_id'  => 0,
          'name'                 => 'Microsoft Office 2003',
          'fullname'             => 'Microsoft Office Professional Edition 2003',
          'serial'               => 'xxxxx-xxxxx-P6RC4-xxxxx-xxxxx',
          'is_trial'             => 0,
          'is_update'            => 0,
          'is_oem'               => 0,
          'activation_date'      => null
      ];

      $this->assertEquals($a_ref,
                          $pfComputerLicenseInfo->fields,
                          'License data');
   }


   /**
    * @test
    */
   public function testCleanComputer() {

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      //First, check if license does exist
      $pfComputerLicenseInfo = new PluginGlpiinventoryComputerLicenseInfo();
      $computer = new Computer();
      $pfComputerLicenseInfo->getFromDBByCrit(['name' => 'Microsoft Office 2003']);
      $computer->getFromDBByCrit(['name' => 'pc001']);

      $a_ref = [
          'id'                   => $pfComputerLicenseInfo->fields['id'],
          'computers_id'         => $computer->fields['id'],
          'softwarelicenses_id'  => 0,
          'name'                 => 'Microsoft Office 2003',
          'fullname'             => 'Microsoft Office Professional Edition 2003',
          'serial'               => 'xxxxx-xxxxx-P6RC4-xxxxx-xxxxx',
          'is_trial'             => 0,
          'is_update'            => 0,
          'is_oem'               => 0,
          'activation_date'      => null
      ];

      $this->assertEquals($a_ref,
                          $pfComputerLicenseInfo->fields,
                          'License data');

      //Second, clean and check if it has been removed
      $pfComputerLicenseInfo = new PluginGlpiinventoryComputerLicenseInfo();
      $pfComputerLicenseInfo->cleanComputer($computer->fields['id']);

      $ret = $pfComputerLicenseInfo->getFromDBByCrit(['name' => 'Microsoft Office 2003']);
      $this->assertFalse($ret);
   }


   /**
    * @test
    */
   public function testDeleteComputer() {

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      $pfiComputerLib   = new PluginGlpiinventoryInventoryComputerLib();
      $computer         = new Computer();

      $a_computerinventory = $this->a_computer1;
      $computer->getFromDBByCrit(['name' => 'pc001']);
      $pfiComputerLib->updateComputer($a_computerinventory,
                                      $computer->fields['id'],
                                      false,
                                      1);

      $computer->getFromDB(1);

      //First, check if license does exist
      $pfComputerLicenseInfo = new PluginGlpiinventoryComputerLicenseInfo();
      $pfComputerLicenseInfo->getFromDBByCrit(['name' => 'Microsoft Office 2003']);;

      $a_ref = [
          'id'                   => $pfComputerLicenseInfo->fields['id'],
          'computers_id'         => $computer->fields['id'],
          'softwarelicenses_id'  => 0,
          'name'                 => 'Microsoft Office 2003',
          'fullname'             => 'Microsoft Office Professional Edition 2003',
          'serial'               => 'xxxxx-xxxxx-P6RC4-xxxxx-xxxxx',
          'is_trial'             => 0,
          'is_update'            => 0,
          'is_oem'               => 0,
          'activation_date'      => null
      ];

      $this->assertEquals(
         $a_ref,
         $pfComputerLicenseInfo->fields,
         'License data'
      );

      //delete computer and check if it has been removed
      $computer->delete(['id' => $computer->fields['id']]);
      $this->assertTrue($computer->getFromDB($computer->fields['id']));

      $ret = $pfComputerLicenseInfo->getFromDBByCrit(['name' => 'Microsoft Office 2003']);
      $this->assertNotFalse($ret);

      //purge computer and check if it has been removed
      $computer->delete(['id' => $computer->fields['id']], 1);
      $this->assertFalse($computer->getFromDB($computer->fields['id']));

      $ret = $pfComputerLicenseInfo->getFromDBByCrit(['name' => 'Microsoft Office 2003']);
      $this->assertFalse($ret);
   }
}
