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

class ComputerDynamicTest extends TestCase {


   /**
    * @test
    */
   public function UpdateComputerManuallyAdded() {

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      $pfiComputerLib  = new PluginGlpiinventoryInventoryComputerLib();
      $computer = new Computer();
      $itemDisk = new Item_Disk();

      $a_computerinventory = [
          "Computer" => [
              "name"   => "pc002",
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
          "computerdisk"   => [
              [
                 "freesize"   => 259327,
                 "totalsize"  => 290143,
                 "device"     => '',
                 "name"       => "C:",
                 "mountpoint" => "C:"
              ]
          ],
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
          'powersupply'    => [],
          'itemtype'       => 'Computer'
      ];

      $a_computer = $a_computerinventory['Computer'];
      $a_computer["entities_id"] = 0;

      $computers_id = $computer->add($a_computer);
      $a_cdisk = [
          "items_id"     => $computers_id,
          "itemtype"     => 'Computer',
          "name"         => "D:",
          "mountpoint"   => "D:",
          "entities_id"  => 0
      ];
      $itemDisk->add($a_cdisk);

      $a_computerdisk = $itemDisk->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
      $this->assertEquals(1, count($a_computerdisk), 'Right no dynamic added');

      $pfiComputerLib->updateComputer($a_computerinventory,
                                      $computers_id,
                                      false,
                                      1);

      $a_computerdisk = $itemDisk->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
      $this->assertEquals(1, count($a_computerdisk), 'May have only 1 computerdisk');

      $a_computerdisk = $itemDisk->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
      $this->assertEquals(1, count($a_computerdisk), 'May have only 1 computerdisk and is dynamic');
   }


   /**
    * @test
    */
   public function UpdateComputerInventoryAdded() {

      // Add manually a computerdisk

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      $pfiComputerLib  = new PluginGlpiinventoryInventoryComputerLib();
      $computer = new Computer();
      $itemDisk = new Item_Disk();

      $a_computerinventory = [
          "Computer" => [
              "name"   => "pc002",
              "serial" => "ggheb7ne72"
          ],
          "inventorycomputer" => [
              'last_inventory_update' => date('Y-m-d H:i:s'),
              'serialized_inventory'        => 'something'
          ],
          'soundcard'      => [],
          'graphiccard'    => [],
          'controller'     => [],
          'processor'      => [],
          "computerdisk" => [
              [
                 "freesize"   => 259327,
                 "totalsize"  => 290143,
                 "device"     => '',
                 "name"       => "C:",
                 "mountpoint" => "C:"
              ]
          ],
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
          'powersupply'    => [],
          'itemtype'       => 'Computer'
      ];

      $a_computer = $a_computerinventory['Computer'];
      $a_computer["entities_id"] = 0;

      $computers_id = $computer->add($a_computer);

      $pfiComputerLib->updateComputer($a_computerinventory,
                                      $computers_id,
                                      false,
                                      0);

      $a_cdisk = [
          "items_id"     => $computers_id,
          "itemtype"     => "Computer",
          "name"         => "D:",
          "mountpoint"   => "D:",
          "entities_id"  => 0
      ];
      $itemDisk->add($a_cdisk);

      $a_computerdisk = $itemDisk->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
      $this->assertEquals(2, count($a_computerdisk), 'May have dynamic + no dynamic computerdisk');

      $a_computerdisk = $itemDisk->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
      $this->assertEquals(1, count($a_computerdisk), '(1)Not dynamic');

      $a_computerdisk = $itemDisk->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
      $this->assertEquals(1, count($a_computerdisk), '(2)Dynamic');

      $pfiComputerLib->updateComputer($a_computerinventory,
                                      $computers_id,
                                      false,
                                      1);

      $a_computerdisk = $itemDisk->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
      $this->assertEquals(2, count($a_computerdisk), 'May ALWAYS have dynamic '.
                                                     '+ no dynamic computerdisk');

      $a_computerdisk = $itemDisk->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
      $this->assertEquals(1, count($a_computerdisk), '(3)Not dynamic');

      $a_computerdisk = $itemDisk->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
      $this->assertEquals(1, count($a_computerdisk), '(4)Dynamic');
   }


   /**
    * @test
    */
   public function UpdateComputerRemoveProcessor() {

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      $a_inventory = [
          'Computer' => [
             'name'                             => 'pcxxx1',
             'users_id'                         => 0,
             'operatingsystems_id'              => 0,
             'operatingsystemversions_id'       => 0,
             'uuid'                             => '68405E00-E5BE-11DF-801C-B05981261220',
             'manufacturers_id'                 => 0,
             'computermodels_id'                => 0,
             'serial'                           => 'XB63J7DH',
             'computertypes_id'                 => 0,
             'is_dynamic'                       => 1,
             'contact'                          => 'username'
          ],
          'inventorycomputer' => [
              'winowner'                        => 'test',
              'wincompany'                      => 'siprossii',
              'operatingsystem_installationdate'=> '2012-10-16 08:12:56',
              'last_inventory_update'     => date('Y-m-d H:i:s'),
              'last_boot'                       => '2018-06-11 08:03:32',
          ],
          'soundcard'      => [],
          'graphiccard'    => [],
          'controller'     => [],
          'processor'      => [
            [
                    'manufacturers_id'  => 0,
                    'designation'       => 'Core i3',
                    'frequence'         => 2400,
                    'serial'            => '',
                    'frequency'         => 2400,
                    'frequence'         => 2400,
                    'frequency_default' => 2400
                ],
            [
                    'manufacturers_id'  => 0,
                    'designation'       => 'Core i3',
                    'frequence'         => 2400,
                    'serial'            => '',
                    'frequency'         => 2400,
                    'frequence'         => 2400,
                    'frequency_default' => 2400
                ]
            ],
          'computerdisk'   => [],
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
          'powersupply'    => [],
          'itemtype'       => 'Computer'
          ];

      $computer         = new Computer();
      $pfiComputerLib   = new PluginGlpiinventoryInventoryComputerLib();
      $item_DeviceProcessor = new Item_DeviceProcessor();

      $computers_id = $computer->add(['serial'      => 'XB63J7DH',
                                           'entities_id' => 0]);

      $pfiComputerLib->updateComputer($a_inventory, $computers_id, false);

      $a_processors = $item_DeviceProcessor->find(['items_id' => $computers_id, 'itemtype' => 'Computer']);
      $this->assertEquals(2, count($a_processors), 'May have the 2 Processors');

      // Remove one processor from inventory
      unset($a_inventory['processor'][1]);
      $pfiComputerLib->updateComputer($a_inventory, $computers_id, false);

      $a_processors = $item_DeviceProcessor->find(['items_id' => $computers_id, 'itemtype' => 'Computer']);
      $this->assertEquals(1, count($a_processors), 'May have the only 1 processor after
                           deleted a processor from inventory');
   }
}
