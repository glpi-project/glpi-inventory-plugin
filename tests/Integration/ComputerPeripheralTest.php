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

class ComputerPeripheralTest extends TestCase {
   public $a_computer1_XML = [];

   public static function setUpBeforeClass(): void {

      // Delete all computers
      $computer = new Computer();
      $items = $computer->find();
      foreach ($items as $item) {
         $computer->delete(['id' => $item['id']], true);
      }
      // Delete all peripherals
      $peripheral = new Peripheral();
      $items = $peripheral->find();
      foreach ($items as $item) {
         $peripheral->delete(['id' => $item['id']], true);
      }
   }


   function __construct() {
      parent::__construct();

      $this->a_computer1_XML =
      "<REQUEST>
   <CONTENT>
    <HARDWARE>
       <NAME>pc001</NAME>
    </HARDWARE>
    <BIOS>
       <SSN>ggheb7ne7</SSN>
    </BIOS>
    <USBDEVICES>
      <NAME>Intel(R) Centrino(R) Wireless Bluetooth(R) 4.0 + High Speed Adapter</NAME>
      <PRODUCTID>07DA</PRODUCTID>
      <VENDORID>8087</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>Generic USB Hub</NAME>
      <PRODUCTID>0024</PRODUCTID>
      <VENDORID>8087</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>Périphérique USB composite</NAME>
      <PRODUCTID>4302</PRODUCTID>
      <SERIAL>10075973</SERIAL>
      <VENDORID>17E9</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>Concentrateur USB générique</NAME>
      <PRODUCTID>3431</PRODUCTID>
      <VENDORID>2109</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>Périphérique USB composite</NAME>
      <PRODUCTID>B315</PRODUCTID>
      <VENDORID>04F2</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>Périphérique d’entrée USB</NAME>
      <PRODUCTID>3025</PRODUCTID>
      <VENDORID>04B3</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>Concentrateur USB générique</NAME>
      <PRODUCTID>3431</PRODUCTID>
      <VENDORID>2109</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>Périphérique d’entrée USB</NAME>
      <PRODUCTID>6019</PRODUCTID>
      <VENDORID>17EF</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>Périphérique USB composite</NAME>
      <PRODUCTID>8206</PRODUCTID>
      <VENDORID>03EB</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>USB-IF USB 3.0 Hub</NAME>
      <PRODUCTID>1111</PRODUCTID>
      <VENDORID>8086</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>Concentrateur USB SuperSpeed générique</NAME>
      <PRODUCTID>0811</PRODUCTID>
      <VENDORID>2109</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>Generic USB Hub</NAME>
      <PRODUCTID>0024</PRODUCTID>
      <VENDORID>8087</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>H5321 gw Mobile Broadband Device</NAME>
      <PRODUCTID>1926</PRODUCTID>
      <SERIAL>187A047919938CM0</SERIAL>
      <VENDORID>0BDB</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>Périphérique d’entrée USB</NAME>
      <PRODUCTID>91D1</PRODUCTID>
      <SERIAL>STM32_EMOTION2</SERIAL>
      <VENDORID>0483</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <NAME>Concentrateur USB SuperSpeed générique</NAME>
      <PRODUCTID>0811</PRODUCTID>
      <VENDORID>2109</VENDORID>
    </USBDEVICES>
</CONTENT>
</REQUEST>";

   }


   /**
    * @test
    */
   public function PeripheralUniqueSerialimport() {
      global $DB;

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      $computer     = new Computer();
      $manufacturer = new Manufacturer();

      $pxml = @simplexml_load_string($this->a_computer1_XML, 'SimpleXMLElement', LIBXML_NOCDATA);

      $arrayinventory = PluginGlpiinventoryFormatconvert::XMLtoArray($pxml);

      $agent = new PluginGlpiinventoryAgent();
      $agents_id = $agent->importToken($arrayinventory);
      $_SESSION['plugin_glpiinventory_agents_id'] = $agents_id;

      $pfInventoryComputerInventory = new PluginGlpiinventoryInventoryComputerInventory();
      $pfInventoryComputerInventory->import('deviceid',
                                            $arrayinventory['CONTENT'],
                                            $arrayinventory);

      $computer->getFromDBByCrit(['name' => 'pc001']);
      $this->assertEquals('ggheb7ne7', $computer->fields['serial'], 'Computer not updated correctly');

      $this->assertTrue($manufacturer->getFromDBByCrit(['name' => 'DisplayLink']), 'Cannot find manufacturer DisplayLink');
      $manufacturerFirst = $manufacturer->fields['id'];
      $this->assertTrue($manufacturer->getFromDBByCrit(['name' => 'Ericsson Business Mobile Networks BV']), 'Cannot find manufacturer Ericsson Business Mobile Networks BV');
      $manufacturerSecond = $manufacturer->fields['id'];
      $this->assertTrue($manufacturer->getFromDBByCrit(['name' => 'STMicroelectronics']), 'Cannot find manufacturer STMicroelectronics');
      $manufacturerThird = $manufacturer->fields['id'];

      $reference = [
         [
            'name' => 'Périphérique USB composite',
            'serial'              => '10075973',
            'peripheraltypes_id'  => 0,
            'peripheralmodels_id' => 0,
            'manufacturers_id'    => $manufacturerFirst,
            'is_global'           => 0,
            'is_deleted'          => 0,
            'is_template'         => 0,
            'is_dynamic'          => 1,
            'entities_id'         => 0,
            'contact'             => null,
            'contact_num'         => null,
            'users_id_tech'       => 0,
            'groups_id_tech'      => 0,
            'comment'             => null,
            'otherserial'         => '',
            'locations_id'        => 0,
            'brand'               => null,
            'template_name'       => null,
            'users_id'            => 0,
            'groups_id'           => 0,
            'states_id'           => 0,
            'ticket_tco'          => '0.0000',
            'is_recursive'        => 0,
            'uuid'                => null
         ],
         [
            'name' => 'H5321 gw Mobile Broadband Module',
            'serial'              => '187A047919938CM0',
            'peripheraltypes_id'  => 0,
            'peripheralmodels_id' => 0,
            'manufacturers_id'    => $manufacturerSecond,
            'is_global'           => 0,
            'is_deleted'          => 0,
            'is_template'         => 0,
            'is_dynamic'          => 1,
            'entities_id'         => 0,
            'contact'             => null,
            'contact_num'         => null,
            'users_id_tech'       => 0,
            'groups_id_tech'      => 0,
            'comment'             => null,
            'otherserial'         => '',
            'locations_id'        => 0,
            'brand'               => null,
            'template_name'       => null,
            'users_id'            => 0,
            'groups_id'           => 0,
            'states_id'           => 0,
            'ticket_tco'          => '0.0000',
            'is_recursive'        => 0,
            'uuid'                => null
         ],
         [
            'name'                => 'Sensor Hub',
            'serial'              => 'STM32_EMOTION2',
            'peripheraltypes_id'  => 0,
            'peripheralmodels_id' => 0,
            'manufacturers_id'    => $manufacturerThird,
            'is_global'           => 0,
            'is_deleted'          => 0,
            'is_template'         => 0,
            'is_dynamic'          => 1,
            'entities_id'         => 0,
            'contact'             => null,
            'contact_num'         => null,
            'users_id_tech'       => 0,
            'groups_id_tech'      => 0,
            'comment'             => null,
            'otherserial'         => '',
            'locations_id'        => 0,
            'brand'               => null,
            'template_name'       => null,
            'users_id'            => 0,
            'groups_id'           => 0,
            'states_id'           => 0,
            'ticket_tco'          => '0.0000',
            'is_recursive'        => 0,
            'uuid'                => null
         ]
      ];

      $a_db_peripherals = getAllDataFromTable('glpi_peripherals');

      $items = [];
      foreach ($a_db_peripherals as $data) {
         unset($data['id']);
         unset($data['date_creation']);
         unset($data['date_mod']);
         $items[] = $data;
      }

      $this->assertEquals($reference, $items, 'List of peripherals');

      // Update computer and may not have new values in glpi_logs
      $query = "SELECT * FROM `glpi_logs`
         ORDER BY `id` DESC LIMIT 1";

      $result = $DB->query($query);
      $data = $DB->fetchAssoc($result);
      $last_id = $data['id'];
      $pfInventoryComputerInventory->import('deviceid',
                                            $arrayinventory['CONTENT'],
                                            $arrayinventory);

      $data = getAllDataFromTable('glpi_logs', ['id' => ['>', $last_id]]);
      $this->assertEquals([], $data, 'On update peripherals, may not have new lines in glpi_logs');
   }
}
