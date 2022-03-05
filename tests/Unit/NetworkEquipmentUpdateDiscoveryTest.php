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

use PHPUnit\Framework\TestCase;

class NetworkEquipmentUpdateDiscoveryTest extends TestCase
{
    public $item_id = 0;

    public $networkports_reference = [
      [
         'items_id'            => 1,
         'itemtype'            => 'NetworkEquipment',
         'entities_id'         => 0,
         'is_recursive'        => 0,
         'logical_number'      => 0,
         'name'                => 'Management',
         'instantiation_type'  => 'NetworkPortAggregate',
         'mac'                 => '38:22:d6:3c:da:e7',
         'comment'             => null,
         'is_deleted'          => 0,
         'is_dynamic'          => 1,
         'ifmtu' => 0,
         'ifspeed' => 0,
         'ifinternalstatus' => null,
         'ifconnectionstatus' => 0,
         'iflastchange' => null,
         'ifinbytes' => 0,
         'ifinerrors' => 0,
         'ifoutbytes' => 0,
         'ifouterrors' => 0,
         'ifstatus' => null,
         'ifdescr' => null,
         'ifalias' => null,
         'portduplex' => null,
         'trunk' => 0,
         'lastup' => null
      ]
    ];

    public $ipaddresses_reference = [
      [
         'entities_id'   => 0,
         'items_id'      => 1,
         'itemtype'      => 'NetworkName',
         'version'       => 4,
         'name'          => '99.99.10.10',
         'binary_0'      => 0,
         'binary_1'      => 0,
         'binary_2'      => 65535,
         'binary_3'      => 1667435018,
         'is_deleted'    => 0,
         'is_dynamic'    => 1,
         'mainitems_id'  => 1,
         'mainitemtype'  => 'NetworkEquipment'

      ]
    ];

    protected $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <SNMPHOSTNAME>switch H3C</SNMPHOSTNAME>
      <DESCRIPTION>H3C Comware Platform Software, Software Version 5.20 Release 2208</DESCRIPTION>
      <AUTHSNMP>1</AUTHSNMP>
      <SERIAL>042ff</SERIAL>
      <IP>99.99.10.10</IP>
      <MAC>38:22:d6:3c:da:e7</MAC>
      <MANUFACTURER>H3C</MANUFACTURER>
      <TYPE>NETWORKING</TYPE>
    </DEVICE>
    <MODULEVERSION>4.2</MODULEVERSION>
    <PROCESSNUMBER>85</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>NETDISCOVERY</QUERY>
</REQUEST>
";

    public static function setUpBeforeClass(): void
    {
       // Delete all network equipments
        $networkEquipment = new NetworkEquipment();
        $items = $networkEquipment->find();
        foreach ($items as $item) {
            $networkEquipment->delete(['id' => $item['id']], true);
        }

       // Delete all printers
        $printer = new Printer();
        $items = $printer->find();
        foreach ($items as $item) {
            $printer->delete(['id' => $item['id']], true);
        }

       // Delete all computer
        $computer = new Computer();
        $items = $computer->find(['NOT' => ['name' => ['LIKE', '_test_pc%']]]);
        foreach ($items as $item) {
            $computer->delete(['id' => $item['id']], true);
        }

       // Delete all ipaddresses
        $ipAddress = new IPAddress();
        $items = $ipAddress->find();
        foreach ($items as $item) {
            $ipAddress->delete(['id' => $item['id']], true);
        }

       // Delete all networknames
        $networkName = new NetworkName();
        $items = $networkName->find();
        foreach ($items as $item) {
            $networkName->delete(['id' => $item['id']], true);
        }

        \RuleImportAsset::initRules();
    }


   /**
    * @test
    */
    public function AddNetworkEquipment()
    {
       // Load session rights
        $_SESSION['glpidefault_entity'] = 0;
        Session::initEntityProfiles(2);
        Session::changeProfile(4);
        plugin_init_glpiinventory();

        $networkEquipment = new NetworkEquipment();

        $input = [
          'name' => 'switch H3C',
          'serial' => '042ff',
          'entities_id' => '0'
        ];
        $this->item_id = $networkEquipment->add($input);
        $this->assertNotFalse($this->item_id, "Add network equipment failed");
        $networkEquipment->getFromDB($this->item_id);

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($this->xml_source);
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $this->assertEquals(1, count($networkEquipment->find()));

        $this->assertGreaterThan(0, $networkEquipment->getFromDBByCrit(['serial' => '042ff']));
        $this->assertEquals('switch H3C', $networkEquipment->fields['name'], 'Name must be updated');
    }


   /**
    * @test
    * @depends AddNetworkEquipment
    */
    public function NewNetworkEquipmentHasPorts()
    {
        $networkports = getAllDataFromTable('glpi_networkports');

        $networkEquipment = new NetworkEquipment();
        $this->assertTrue($networkEquipment->getFromDBByCrit(['serial' => '042ff']));
        $this->networkports_reference[0]['items_id'] = $networkEquipment->fields['id'];

        $reference = [];
        foreach ($networkports as $data) {
            unset($data['id']);
            unset($data['date_mod']);
            unset($data['date_creation']);
            $reference[] = $data;
        }

        $this->assertEquals(
            $this->networkports_reference,
            $reference,
            "Network ports does not match reference on first update"
        );
    }


   /**
    * @test
    * @depends AddNetworkEquipment
    */
    public function NewNetworkEquipmentHasIpAdresses()
    {
        $ipaddresses = getAllDataFromTable('glpi_ipaddresses');

        $items = [];
        foreach ($ipaddresses as $data) {
            unset($data['id']);
            unset($data['date_mod']);
            unset($data['date_creation']);
            $items[] = $data;
        }

        $networkName = new NetworkName();
        $netnames = $networkName->find([], [], 1);
        $this->assertEquals(1, count($netnames), 'No network name created');
        $item = current($netnames);
        $this->ipaddresses_reference[0]['items_id'] = $item['id'];

        $networkEquipment = new NetworkEquipment();
        $item = current($networkEquipment->find([], [], 1));
        $this->ipaddresses_reference[0]['mainitems_id'] = $item['id'];

        $this->assertEquals(
            $this->ipaddresses_reference,
            $items,
            "IP addresses does not match reference on first update"
        );
    }


   /**
    * @test
    * @depends AddNetworkEquipment
    */
    public function UpdateNetworkEquipment()
    {

       // Load session rights
        $_SESSION['glpidefault_entity'] = 0;
        Session::initEntityProfiles(2);
        Session::changeProfile(4);
        plugin_init_glpiinventory();

       // Update 2nd time
        $networkEquipment = new NetworkEquipment();
        $item = current($networkEquipment->find([], [], 1));

        $networkEquipment->getFromDB($item['id']);

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($this->xml_source);
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $this->assertEquals(1, count($networkEquipment->find()));
    }

   /**
    * @test
    * @depends UpdateNetworkEquipment
    */
    public function UpdateNetworkEquipmentOnlyOneNetworkName()
    {
        $networkNames = getAllDataFromTable('glpi_networknames');
        $this->assertEquals(1, count($networkNames));
    }


   /**
    * @test
    * @depends UpdateNetworkEquipment
    */
    public function UpdateNetworkEquipmentOnlyOneIpaddress()
    {
        $Ips = getAllDataFromTable('glpi_ipaddresses');
        $this->assertEquals(1, count($Ips));
    }


   /**
    * @test
    * @depends UpdateNetworkEquipment
    */
    public function UpdatedNetworkEquipmentHasPorts()
    {
        $networkports = getAllDataFromTable('glpi_networkports');

        $this->assertEquals(1, count($networkports), "Must have only 1 network port");

        $networkEquipment = new NetworkEquipment();
        $item = current($networkEquipment->find([], [], 1));
        $this->networkports_reference[0]['items_id'] = $item['id'];

        $reference = [];
        foreach ($networkports as $data) {
            unset($data['id']);
            unset($data['date_mod']);
            unset($data['date_creation']);
            $reference[] = $data;
        }

        $this->assertEquals(
            $this->networkports_reference,
            $reference,
            "network ports does not match reference on second update"
        );
    }


   /**
    * @test
    * @depends UpdateNetworkEquipment
    */
    public function UpdateNetworkEquipmentHasIpAdresses()
    {
        $ipaddresses = getAllDataFromTable('glpi_ipaddresses');

        $items = [];
        foreach ($ipaddresses as $data) {
            unset($data['id']);
            unset($data['date_mod']);
            unset($data['date_creation']);
            $items[] = $data;
        }

        $networkName = new NetworkName();
        $item = current($networkName->find([], [], 1));
        $this->ipaddresses_reference[0]['items_id'] = $item['id'];

        $networkEquipment = new NetworkEquipment();
        $item = current($networkEquipment->find([], [], 1));
        $this->ipaddresses_reference[0]['mainitems_id'] = $item['id'];

        $this->assertEquals(
            $this->ipaddresses_reference,
            $items,
            "IP addresses does not match reference on second update:\n" .
            print_r($this->ipaddresses_reference, true) . "\n" .
            print_r($ipaddresses, true) . "\n"
        );
    }
}
