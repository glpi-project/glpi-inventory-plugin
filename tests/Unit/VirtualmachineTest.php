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

class VirtualmachineTest extends TestCase {

   public $items_id = 0;
   public $datelatupdate = '';
   public $computer_inventory = [];

   public static function setUpBeforeClass(): void {

      // Delete all computers
      $computer = new Computer();
      $computers = $computer->find();
      foreach ($computers as $item) {
         $computer->delete(['id' => $item['id']], true);
      }
   }

   function __construct() {
      parent::__construct();
      $a_inventory = [
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
          'processor'      => [],
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
      $a_inventory['Computer'] = [
          'name'                             => 'pc-host-VM',
          'users_id'                         => 0,
          'operatingsystems_id'              => 'freebsd',
          'operatingsystemversions_id'       => '9.1-RELEASE',
          'uuid'                             => '68405E00-E5BE-11DF-801C-B05981201220',
          'os_licenseid'                     => '',
          'os_license_number'                => '',
          'operatingsystemservicepacks_id'   => 'GENERIC ()root@farrell.cse.buffalo.edu',
          'manufacturers_id'                 => '',
          'computermodels_id'                => '',
          'serial'                           => 'XB63J7D',
          'computertypes_id'                 => 'Notebook',
          'is_dynamic'                       => 1,
          'contact'                          => 'username'
      ];
      $a_inventory['inventorycomputer'] = [
          'last_inventory_update' => date('Y-m-d H:i:s'),
          'serialized_inventory'        => 'something'
      ];

      $a_inventory['virtualmachine'][] = [
          'ram'                      => 1024,
          'name'                     => 'Windows 7',
          'comment'                  => 'comment',
          'virtualmachinestates_id'  => 'up',
          'virtualmachinesystems_id' => 'vbox',
          'uuid'                     => '2961ecf6-7e94-488d-ae0d-e427123078b3',
          'vcpu'                     => 1,
          'virtualmachinetypes_id'   => 'virtualbox',
          'is_dynamic'               => 1
      ];

      $this->computer_inventory = $a_inventory;
   }


   /**
    * @test
    */
   public function AddComputer() {

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION['glpishowallentities'] = 1;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      $a_inventory = $this->computer_inventory;

      $pfiComputerLib   = new PluginGlpiinventoryInventoryComputerLib();
      $computer         = new Computer();
      $pfFormatconvert  = new PluginGlpiinventoryFormatconvert();

      $a_inventory = $pfFormatconvert->replaceids($a_inventory, 'Computer', 0);

      $serialized = base64_encode(gzcompress(serialize($a_inventory)));
      $a_inventory['inventorycomputer']['serialized_inventory'] =
               Toolbox::addslashes_deep($serialized);

      $this->items_id = $computer->add(['serial' => 'XB63J7D',
                                        'entities_id' => 0]);

      $this->assertGreaterThan(0, $this->items_id, false);
      $pfiComputerLib->updateComputer($a_inventory, $this->items_id, true);

      // To be sure not have 2 same informations
      $pfiComputerLib->updateComputer($a_inventory, $this->items_id, false);
   }


   /**
    * Create VirtualMachine in computer
    *
    * @test
    */
   public function ComputerVirtualmachineCreate() {

      $a_data = getAllDataFromTable("glpi_computervirtualmachines");
      $items = [];
      foreach ($a_data as $data) {
         unset($data['id']);
         unset($data['date_mod']);
         unset($data['date_creation']);
         $items[] = $data;
      }

      $computer = new Computer();
      $computer->getFromDBByCrit(['serial' => 'XB63J7D']);

      $a_reference = [
         [
            'entities_id'              => 0,
            'computers_id'             => $computer->fields['id'],
            'name'                     => 'Windows 7',
            'comment'                  => 'comment',
            'virtualmachinestates_id'  => 1,
            'virtualmachinesystems_id' => 1,
            'virtualmachinetypes_id'   => 1,
            'uuid'                     => '2961ecf6-7e94-488d-ae0d-e427123078b3',
            'vcpu'                     => 1,
            'ram'                      => 1024,
            'is_deleted'               => 0,
            'is_dynamic'               => 1,
         ],
      ];
      $this->assertEquals($a_reference, $items);
   }


   /**
    * Update VirtualMachine in computer
    *
    * @test
    */
   public function ComputerVirtualmachineUpdateMemory() {

      $a_inventory = $this->computer_inventory;

      $a_inventory['virtualmachine'][0]['ram'] = '2048';

      $pfiComputerLib   = new PluginGlpiinventoryInventoryComputerLib();
      $pfFormatconvert  = new PluginGlpiinventoryFormatconvert();

      $a_inventory = $pfFormatconvert->replaceids($a_inventory, 'Computer', 1);

      $serialized = base64_encode(gzcompress(serialize($a_inventory)));
      $a_inventory['inventorycomputer']['serialized_inventory'] =
               Toolbox::addslashes_deep($serialized);

      $computer = new Computer();
      $computer->getFromDBByCrit(['serial' => 'XB63J7D']);

      $pfiComputerLib->updateComputer($a_inventory, $computer->fields['id'], false);

      $nbvm = countElementsInTable("glpi_computervirtualmachines");

      $this->assertEquals(1, $nbvm, 'May have only 1 VM');

      $a_data = getAllDataFromTable("glpi_computervirtualmachines");

      $items = [];
      foreach ($a_data as $data) {
         unset($data['id']);
         unset($data['date_mod']);
         unset($data['date_creation']);
         $items[] = $data;
      }

      $computer = new Computer();
      $computer->getFromDBByCrit(['serial' => 'XB63J7D']);

      $a_reference = [
         [
            'entities_id'              => 0,
            'computers_id'             => $computer->fields['id'],
            'name'                     => 'Windows 7',
            'comment'                  => 'comment',
            'virtualmachinestates_id'  => 1,
            'virtualmachinesystems_id' => 1,
            'virtualmachinetypes_id'   => 1,
            'uuid'                     => '2961ecf6-7e94-488d-ae0d-e427123078b3',
            'vcpu'                     => 1,
            'ram'                      => 2048,
            'is_deleted'               => 0,
            'is_dynamic'               => 1,
         ],
      ];
      $this->assertEquals($a_reference, $items);
   }
}
