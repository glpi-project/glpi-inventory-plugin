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

class PrinterUpdateTest extends TestCase
{
    public $items_id = 0;
    public $update_time = '';

    public static function setUpBeforeClass(): void
    {
        global $DB;

       // Delete all printers
        $printer = new Printer();
        $items = $printer->find();
        foreach ($items as $item) {
            $printer->delete(['id' => $item['id']], true);
        }
        $_SESSION["glpiID"] = 0;
    }

    public static function tearDownAfterClass(): void
    {
        $_SESSION["glpiID"] = 2;
    }

   /**
    * @test
    */
    public function AddPrinter()
    {
        $this->update_time = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $this->update_time;

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST><CONTENT><DEVICE>
      <CARTRIDGES>
        <TONERBLACK>60</TONERBLACK>
        <TONERCYAN>40</TONERCYAN>
        <TONERYELLOW>80</TONERYELLOW>
        <TONERMAGENTA>100</TONERMAGENTA>
      </CARTRIDGES>
      <INFO>
        <DESCRIPTION>HP ETHERNET MULTI-ENVIRONMENT</DESCRIPTION>
        <ID>54</ID>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>HP Unknown</MODEL>
        <NAME>ARC12-B09-N</NAME>
        <RAM>64</RAM>
        <SERIAL>VRG5XUT5</SERIAL>
        <TYPE>PRINTER</TYPE>
      </INFO>
      <PAGECOUNTERS>
        <TOTAL>15134</TOTAL>
        <BLACK>10007</BLACK>
        <COLOR>5127</COLOR>
      </PAGECOUNTERS>
    </DEVICE></CONTENT><QUERY>SNMP</QUERY><DEVICEID>foo</DEVICEID></REQUEST>";

        $printer = new Printer();

        $this->items_id = $printer->add([
         'serial'      => 'VRG5XUT5',
         'entities_id' => 0
        ]);

        $this->assertGreaterThan(0, $this->items_id);

        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));

        $CFG_GLPI["is_contact_autoupdate"] = 0;
        new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        // To be sure not have 2 times the same information
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default
    }


   /**
    * @test
    */
    public function PrinterGeneral()
    {
        $printer = new Printer();
        $printer->getFromDBByCrit(['name' => 'ARC12-B09-N']);
        $printerId = $printer->fields['id'];
        unset($printer->fields['id']);
        unset($printer->fields['date_mod']);
        unset($printer->fields['date_creation']);

        $manufacturer = new Manufacturer();
        $manufacturer->getFromDBByCrit(['name' => 'Hewlett-Packard']);

        $model = new PrinterModel();
        $model->getFromDBByCrit(['name' => 'HP Unknown']);

        $type = new PrinterType();
        $type->getFromDBByCrit(['name' => 'Printer']);

        $autoupdate = new AutoUpdateSystem();
        $autoupdate->getFromDBByCrit(['name' => 'GLPI Native Inventory']);

        $a_reference = [
         'name'                 => 'ARC12-B09-N',
         'serial'               => 'VRG5XUT5',
         'otherserial'          => null,
         'manufacturers_id'     => $manufacturer->fields['id'],
         'locations_id'         => 0,
         'printermodels_id'     => $model->fields['id'],
         'memory_size'          => '64',
         'entities_id'          => 0,
         'is_recursive'         => 0,
         'contact'              => null,
         'contact_num'          => null,
         'users_id_tech'        => 0,
         'groups_id_tech'       => 0,
         'have_serial'          => 0,
         'have_parallel'        => 0,
         'have_usb'             => 0,
         'have_wifi'            => 0,
         'have_ethernet'        => 1,
         'comment'              => null,
         'networks_id'          => 0,
         'printertypes_id'      => $type->fields['id'],
         'is_global'            => 0,
         'is_deleted'           => 0,
         'is_template'          => 0,
         'template_name'        => null,
         'init_pages_counter'   => 0,
         'last_pages_counter'   => 15134,
         'users_id'             => 0,
         'groups_id'            => 0,
         'states_id'            => 0,
         'ticket_tco'           => '0.0000',
         'is_dynamic'           => 1,
         'uuid'                 => null,
         'sysdescr'             => 'HP ETHERNET MULTI-ENVIRONMENT',
         'last_inventory_update' => $_SESSION['glpi_currenttime'],
         'snmpcredentials_id' => 0,
         'autoupdatesystems_id' => $autoupdate->fields['id']
        ];

        $this->assertEquals($a_reference, $printer->fields);

       //Check if no log has been added for the counter's update
       /*$nb = countElementsInTable('glpi_logs',
                                 ['itemtype'         => 'Printer',
                                  'items_id'         => $printerId,
                                  'linked_action'    => 0,
                                  'id_search_option' => 12
                                  ]);
       $logs = new Log();
       var_dump($logs->find(['itemtype'         => 'Printer',
         'items_id'         => $printerId,
         'linked_action'    => 0,
         'id_search_option' => 12
       ]));
       $this->assertEquals($nb, 0);*/
    }


   /**
    * @test
    */
    public function PrinterSnmpExtension()
    {

        $printer = new Printer();
        $printer->getFromDBByCrit(['name' => 'ARC12-B09-N']);
        $this->assertEquals($printer->fields['sysdescr'], 'HP ETHERNET MULTI-ENVIRONMENT');
    }


   /**
    * @test
    */
    public function PrinterPageCounter()
    {

        $printerlog = new PrinterLog();
        $printer = new Printer();
        $printer->getFromDBByCrit(['name' => 'ARC12-B09-N']);
        $a_pages = $printerlog->find(['printers_id' => $printer->fields['id']]);

        $this->assertEquals(1, count($a_pages), print_r($a_pages, true));
    }


   /**
    * @test
    */
    public function PrinterCartridgeBlack()
    {
        $cartridge_info = new Printer_CartridgeInfo();
        $printer = new Printer();
        $printer->getFromDBByCrit(['name' => 'ARC12-B09-N']);

        $a_cartridge = $cartridge_info->find([
         'printers_id' => $printer->fields['id'],
         'property' => 'tonerblack',
         'value' => 60
        ]);
        $this->assertEquals(1, count($a_cartridge));
    }


   /**
    * @test
    */
    public function PrinterCartridgeCyan()
    {
        $cartridge_info = new Printer_CartridgeInfo();
        $printer = new Printer();
        $printer->getFromDBByCrit(['name' => 'ARC12-B09-N']);

        $a_cartridge = $cartridge_info->find([
         'printers_id' => $printer->fields['id'],
         'property' => 'tonercyan',
         'value' => 40
        ]);
        $this->assertEquals(1, count($a_cartridge));
    }


   /**
    * @test
    */
    public function PrinterCartridgeYellow()
    {
        $cartridge_info = new Printer_CartridgeInfo();
        $printer = new Printer();
        $printer->getFromDBByCrit(['name' => 'ARC12-B09-N']);

        $a_cartridge = $cartridge_info->find([
         'printers_id' => $printer->fields['id'],
         'property' => 'toneryellow',
         'value' => 80
        ]);
        $this->assertEquals(1, count($a_cartridge));
    }


   /**
    * @test
    */
    public function PrinterCartridgeMagenta()
    {
        $cartridge_info = new Printer_CartridgeInfo();
        $printer = new Printer();
        $printer->getFromDBByCrit(['name' => 'ARC12-B09-N']);
        $a_cartridge = $cartridge_info->find([
         'printers_id' => $printer->fields['id'],
         'property' => 'tonermagenta',
         'value' => 100
        ]);
        $this->assertEquals(1, count($a_cartridge));
    }


   /**
    * @test
    */
    public function PrinterAllCartridges()
    {
        $cartridge_info = new Printer_CartridgeInfo();
        $a_cartridge = $cartridge_info->find();
        $this->assertEquals(4, count($a_cartridge));
    }


   /**
    * @test
    */
    public function NewPrinterFromNetdiscovery()
    {

        $networkName = new NetworkName();
        $iPAddress = new IPAddress();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <AUTHSNMP>1</AUTHSNMP>
      <DESCRIPTION>Photosmart D7200 series</DESCRIPTION>
      <SNMPHOSTNAME>HP0BBBC4</SNMPHOSTNAME>
      <NETBIOSNAME>HP00215A0BBBC4</NETBIOSNAME>
      <ENTITY>0</ENTITY>
      <IP>192.168.20.100</IP>
      <MAC>00:21:5a:0b:bb:c4</MAC>
      <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
      <MODEL>Printer0093</MODEL>
      <SERIAL>MY89AQG0V9050N</SERIAL>
      <TYPE>PRINTER</TYPE>
    </DEVICE>
    <MODULEVERSION>4.2</MODULEVERSION>
    <PROCESSNUMBER>85</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>NETDISCOVERY</QUERY>
</REQUEST>
";

        $printer = new Printer();
        $printers_id = $printer->add([
         'serial' => 'MY89AQG0V9050N',
         'entities_id' => 0
        ]);
        $this->assertNotFalse($printers_id);
        $this->assertTrue($printer->getFromDB($printers_id));

        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $printer->getFromDB($printers_id);
        $this->assertEquals('HP0BBBC4', $printer->fields['name'], 'Name must be updated');

        $this->assertEquals(
            '1',
            $printer->fields['snmpcredentials_id'],
            'SNMPauth may be with id 1'
        );
        $this->assertEquals(
            'Photosmart D7200 series',
            $printer->fields['sysdescr'],
            'Sysdescr not updated correctly'
        );

       // Check mac
        $networkPort = new NetworkPort();
        $a_ports = $networkPort->find(['itemtype' => 'Printer', 'items_id' => $printers_id]);
        $this->assertEquals(
            '1',
            count($a_ports),
            'May have one network port'
        );
        $a_port = current($a_ports);
        $this->assertEquals(
            '00:21:5a:0b:bb:c4',
            $a_port['mac'],
            'Mac address'
        );

       // check ip
        $a_networknames = $networkName->find(
            ['itemtype' => 'NetworkPort',
            'items_id' => $a_port['id']]
        );
        $this->assertEquals(
            '1',
            count($a_networknames),
            'May have one networkname'
        );
        $a_networkname = current($a_networknames);
        $a_ipaddresses = $iPAddress->find(
            ['itemtype' => 'NetworkName',
            'items_id' => $a_networkname['id']]
        );
        $this->assertEquals(
            '1',
            count($a_ipaddresses),
            'May have one IP address'
        );
        $a_ipaddress = current($a_ipaddresses);
        $this->assertEquals(
            '192.168.20.100',
            $a_ipaddress['name'],
            'IP address'
        );
    }


   /**
    * @test
    * @depends NewPrinterFromNetdiscovery
    */
    public function updatePrinterFromNetdiscovery()
    {
        global $DB;

        $networkName = new NetworkName();
        $iPAddress = new IPAddress();

        $_SESSION["glpiID"] = 0;

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <AUTHSNMP>1</AUTHSNMP>
      <DESCRIPTION>Photosmart D7200 series</DESCRIPTION>
      <SNMPHOSTNAME>HP0BBBC4new</SNMPHOSTNAME>
      <NETBIOSNAME>HP00215A0BBBC4</NETBIOSNAME>
      <ENTITY>0</ENTITY>
      <IP>192.168.20.102</IP>
      <MAC>00:21:5a:0b:bb:c4</MAC>
      <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
      <MODEL>Printer0093</MODEL>
      <SERIAL>MY89AQG0V9050N</SERIAL>
      <TYPE>PRINTER</TYPE>
    </DEVICE>
    <MODULEVERSION>4.2</MODULEVERSION>
    <PROCESSNUMBER>85</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>NETDISCOVERY</QUERY>
</REQUEST>
";

        $printer = new Printer();
        $printer->getFromDBByCrit(['serial' => 'MY89AQG0V9050N']);
        $this->assertArrayHasKey('id', $printer->fields);

        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $printer = new Printer();
        $printer->getFromDBByCrit(['serial' => 'MY89AQG0V9050N']);
        $this->assertArrayHasKey('id', $printer->fields);

        $this->assertEquals('HP0BBBC4new', $printer->fields['name'], 'Name must be updated');

        $this->assertEquals(
            '1',
            $printer->fields['snmpcredentials_id'],
            'SNMPauth may be with id 1'
        );
        $this->assertEquals(
            'Photosmart D7200 series',
            $printer->fields['sysdescr'],
            'Sysdescr not updated correctly'
        );

       // Check mac
        $networkPort = new NetworkPort();
        $a_ports = $networkPort->find(['itemtype' => 'Printer', 'items_id' => $printer->fields['id']]);
        $this->assertEquals(
            '1',
            count($a_ports),
            'May have one network port'
        );
        $a_port = current($a_ports);
        $this->assertEquals(
            '00:21:5a:0b:bb:c4',
            $a_port['mac'],
            'Mac address'
        );

       // check ip
        $a_networknames = $networkName->find(
            ['itemtype' => 'NetworkPort',
            'items_id' => $a_port['id']]
        );
        $this->assertEquals(
            '1',
            count($a_networknames),
            'May have one networkname'
        );
        $a_networkname = current($a_networknames);
        $a_ipaddresses = $iPAddress->find(
            ['itemtype' => 'NetworkName',
            'items_id' => $a_networkname['id']]
        );
        $this->assertEquals(
            '1',
            count($a_ipaddresses),
            'May have one IP address'
        );
        $a_ipaddress = current($a_ipaddresses);
        $this->assertEquals(
            '192.168.20.102',
            $a_ipaddress['name'],
            'IP address'
        );
    }

   /**
    * @test
    * @depends NewPrinterFromNetdiscovery
    */
    public function updatePrinterFromNetdiscoveryToInventory()
    {
        $_SESSION["plugin_glpiinventory_entity"] = 0;

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <AUTHSNMP>1</AUTHSNMP>
      <DESCRIPTION>SHARP MX-5140N</DESCRIPTION>
      <SNMPHOSTNAME>SHARP MX-5140N</SNMPHOSTNAME>
      <NETBIOSNAME>SHARP MX-5140N</NETBIOSNAME>
      <ENTITY>0</ENTITY>
      <IP>10.120.80.61</IP>
      <MAC>24:26:42:1e:5a:90</MAC>
      <MANUFACTURER>Sharp</MANUFACTURER>
      <SERIAL>8512418234</SERIAL>
      <TYPE>PRINTER</TYPE>
    </DEVICE>
    <MODULEVERSION>4.2</MODULEVERSION>
    <PROCESSNUMBER>85</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>NETDISCOVERY</QUERY>
</REQUEST>
";

       //First: discover the device
        $printer     = new Printer();
        $printers_id = $printer->add([
         'serial'      => '8512418234',
         'entities_id' => 0
        ]);
        $printer->getFromDB($printers_id);

        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $this->assertGreaterThan(0, $printer->getFromDBByCrit(['serial' => '8512418234']));
        $this->assertEquals('SHARP MX-5140N', $printer->fields['name'], 'Name must be updated');

       // Check mac
        $networkPort = new NetworkPort();
        $a_ports = $networkPort->find(['itemtype' => 'Printer', 'items_id' => $printers_id]);
        $this->assertEquals(
            '1',
            count($a_ports),
            'May have one network port'
        );
        $a_port = current($a_ports);
        $this->assertEquals('24:26:42:1e:5a:90', $a_port['mac'], 'Mac address');

       //Logical number should be 0
        $this->assertEquals(0, $a_port['logical_number'], 'Logical number equals 0');

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <COMMENTS>SHARP MX-5140N</COMMENTS>
        <ID>" . $printers_id . "</ID>
        <MANUFACTURER>Sharp</MANUFACTURER>
        <MEMORY>32</MEMORY>
        <MODEL>SHARP MX series</MODEL>
        <NAME>SHARP MX-5140N</NAME>
        <RAM>64</RAM>
        <SERIAL>8512418234</SERIAL>
        <TYPE>PRINTER</TYPE>
        <UPTIME>14 days, 22:48:33.30</UPTIME>
        <IPS><IP>10.120.80.61</IP></IPS>
        <MAC>24:26:42:1e:5a:90</MAC>
      </INFO>
      <PORTS>
        <PORT>
          <IFDESCR>Ethernet/1</IFDESCR>
          <IFINERRORS>0</IFINERRORS>
          <IFINOCTETS>1446413508</IFINOCTETS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFLASTCHANGE>23.85 seconds</IFLASTCHANGE>
          <IFMTU>1500</IFMTU>
          <IFNAME>NC-6800h</IFNAME>
          <IFNUMBER>1</IFNUMBER>
          <IFOUTERRORS>0</IFOUTERRORS>
          <IFOUTOCTETS>53073183</IFOUTOCTETS>
          <IFSPEED>100000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFTYPE>7</IFTYPE>
          <IP>10.120.80.61</IP>
          <IPS>
            <IP>10.120.80.61</IP>
          </IPS>
          <MAC>24:26:42:1e:5a:90</MAC>
        </PORT>
      </PORTS>
    </DEVICE>
  </CONTENT>
  <QUERY>SNMP</QUERY>
  <DEVICEID>foo</DEVICEID>
</REQUEST>";

        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $a_ports = $networkPort->find(['itemtype' => 'Printer', 'items_id' => $printers_id]);
        $expected = 1 + 1; //1 port + 1 management port
        $this->assertEquals($expected, count($a_ports), 'Should have ' . $expected . 'ports');

        $a_port = array_pop($a_ports);
        $mgmt_port = array_pop($a_ports);
        $this->assertEquals('Management', $mgmt_port['name']);
        $this->assertEquals('Management', $mgmt_port['name']);

        //Logical number should be 1
        $this->assertEquals(1, $a_port['logical_number'], 'Logical number changed to 1');
        $this->assertEquals('NC-6800h', $a_port['name'], 'Name has changed');
    }
}
