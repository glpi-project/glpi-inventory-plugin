<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

class SoftwareVersionUpdateTest extends TestCase {

   public static function setUpBeforeClass(): void {

      // Delete all computers
      $computer = new Computer();
      $items = $computer->find();
      foreach ($items as $item) {
         $computer->delete(['id' => $item['id']], true);
      }
   }


   /**
    * @test
    */
   public function updateVersionWithOs() {
      global $DB;

      $_SESSION["plugin_fusioninventory_entity"] = 0;
      $_SESSION["glpiname"]                      = 'Plugin_FusionInventory';

      $computer        = new Computer();
      $pfiComputerInv  = new PluginFusioninventoryInventoryComputerInventory();
      $pfAgent         = new PluginFusioninventoryAgent();
      $software        = new Software();
      $version         = new SoftwareVersion();
      $installation    = new Item_SoftwareVersion();

      //Add a computer
      $computers_id = $computer->add(['name'        => 'computer1',
                                      'entities_id' => 0
                                     ]);
      $this->assertGreaterThan(0, $computers_id);

      //Add a software
      $softwares_id = $software->add(['name' => 'FusionInventory-Agent', 'entities_id' => 0]);
      $this->assertGreaterThan(0, $softwares_id);

      //Add a version for the software
      $versions_id = $version->add(['name' => '2.4',
                                    'entities_id' => 0,
                                    'softwares_id' => $softwares_id
                                   ]);
      $this->assertGreaterThan(0, $versions_id);

      //Install the software on the computer
      $installations_id = $installation->add([
         'itemtype'           => 'Computer',
         'items_id'           => $computers_id,
         'softwareversions_id' => $versions_id,
         'is_dynamic'          => 1
      ]);
      $this->assertGreaterThan(0, $installations_id);

      //Add agent for this computer
      $a_agents_id = $pfAgent->add(['name'      => 'computer1-2018-01-01',
                                    'device_id' => 'computer1-2018-01-01']);
      $_SESSION['plugin_fusioninventory_agents_id'] = $a_agents_id;

      //Import a simple inventory WITHOUT OS infos
      $a_inventory = [];
      $a_inventory['CONTENT']['HARDWARE'] = [
          'NAME' => 'computer1'
      ];
      $a_inventory['CONTENT']['SOFTWARES'][] = [
          'COMMENTS' => "Comment",
          'NAME'     => "FusionInventory-Agent",
          'VERSION'  => "2.4"
      ];

      $pfiComputerInv->import("computer1-2018-01-01", "", $a_inventory);

      //There must be only one version 2.4 for FusionInventory-Agent software
      $this->assertEquals(1, countElementsInTable('glpi_softwareversions',
         ['softwares_id' => $softwares_id, 'name' => '2.4']));
      //The computer is linked to the existing software version
      $this->assertEquals(
         1,
         countElementsInTable(
            'glpi_items_softwareversions', [
               'softwareversions_id'   => $versions_id,
               'items_id'              => $computers_id,
               'itemtype'              => 'Computer'
            ]
         )
      );

      //We add OS infos to the inventory
      $a_inventory['CONTENT']['OPERATINGSYSTEM'] = [
         'ARCH'           => 'x86_64',
         'FULL_NAME'      => 'Ubuntu 17.10',
         'NAME'           => 'Ubuntu',
         'KERNEL_NAME'    => 'linux',
         'KERNEL_VERSION' => '4.13.0-19-generic',
         'VERSION'        => '17.10'
      ];

      //Second import
      $pfiComputerInv->import("computer1-2018-01-01", "", $a_inventory);

      //There must be a second version 2.4 for FusionInventory-Agent software, with OS infos
      $this->assertEquals(2, countElementsInTable('glpi_softwareversions',
         ['softwares_id' => $softwares_id, 'name' => '2.4']));
      //The computer is no longer linked to the existing software version
      $this->assertEquals(
         0,
         countElementsInTable(
            'glpi_items_softwareversions', [
               'softwareversions_id'   => $versions_id,
               'items_id'              => $computers_id,
               'itemtype'              => 'Computer'
            ]
         )
      );

      //Load the software version
      $iterator = $DB->request('glpi_items_softwareversions', ['items_id' => $computers_id, 'itemtype' => 'Computer']);
      $this->assertEquals(1, $iterator->numrows());
      $data = $iterator->current();
      //Check that it's not the same version as the one we have manually created
      $this->assertNotEquals($data['softwareversions_id'], $versions_id);

      //Check that there's an OS for this version
      $this->assertTrue($version->getFromDB($data['softwareversions_id']));
      $this->assertGreaterThan(0, $version->fields['operatingsystems_id']);

      //Check that there's no installation for the old version
      $this->assertEquals(0, countElementsInTable('glpi_items_softwareversions',
         ['softwareversions_id' => $versions_id, 'items_id' => $computers_id, 'itemtype' => 'Computer']));

      //Third import: a second one with an OS
      $pfiComputerInv->import("computer1-2018-01-01", "", $a_inventory);
      //There must be a second version 2.4 for FusionInventory-Agent software, with OS infos
      $this->assertEquals(2, countElementsInTable('glpi_softwareversions',
         ['softwares_id' => $softwares_id, 'name' => '2.4']));
   }
}
