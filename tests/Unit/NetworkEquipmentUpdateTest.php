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

use Glpi\Inventory\Converter;
use Glpi\Inventory\Inventory;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class NetworkEquipmentUpdateTest extends TestCase
{
    public $items_id = 0;

    public static function setUpBeforeClass(): void
    {
        // Delete all network equipments
        $networkEquipment = new NetworkEquipment();
        $items = $networkEquipment->find();
        foreach ($items as $item) {
            $networkEquipment->delete(['id' => $item['id']], true);
        }
    }

    public static function tearDownAfterClass(): void
    {
        global $DB, $GLPI_CACHE;
        $DB->update(
            NetworkPortType::getTable(),
            [
                'is_importable' => 0,
                'instantiation_type' => null,
            ],
            [
                'value_decimal' => [53, 54],
            ]
        );

        $GLPI_CACHE->set('glpi_inventory_ports_types', null);
    }

    public function testAddNetworkEquipment()
    {
        global $DB, $GLPI_CACHE;

        $this->assertTrue(
            $DB->update(
                NetworkPortType::getTable(),
                [
                    'is_importable' => 1,
                    'instantiation_type' => 'NetworkPortEthernet',
                ],
                [
                    'value_decimal' => [53, 54],
                ]
            )
        );

        $GLPI_CACHE->set('glpi_inventory_ports_types', null);

        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <DESCRIPTION>Cisco IOS Software, C2960 Software (C2960-LANBASEK9-M), Version 12.2(50)SE4, RELEASE SOFTWARE (fc1)
Technical Support: http://www.cisco.com/techsupport
Copyright (c) 1986-2010 by Cisco Systems, Inc.
Compiled Fri 26-Mar-10 09:14 by prod_rel_team</DESCRIPTION>
        <NAME>switchr2d2</NAME>
        <SERIAL>FOC147UJEU4</SERIAL>
        <UPTIME>157 days, 02:14:44.00</UPTIME>
        <MAC>6c:50:4d:39:59:80</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.30.67</IP>
          <IP>192.168.40.67</IP>
          <IP>192.168.50.67</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CONNECTION>
              <MAC>cc:f9:54:a1:03:35</MAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>FastEthernet0/1</IFDESCR>
          <IFNAME>Fa0/1</IFNAME>
          <IFNUMBER>10001</IFNUMBER>
          <IFSPEED>100000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFTYPE>6</IFTYPE>
          <MAC>6c:50:4d:39:59:81</MAC>
          <VLANS>
            <VLAN>
              <NAME>printer</NAME>
              <NUMBER>281</NUMBER>
              <TAGGED>1</TAGGED>
            </VLAN>
          </VLANS>
        </PORT>
        <PORT>
          <CONNECTIONS>
            <CONNECTION>
              <MAC>cc:f9:54:a1:03:36</MAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>FastEthernet0/2</IFDESCR>
          <IFNAME>Fa0/2</IFNAME>
          <IFNUMBER>10002</IFNUMBER>
          <IFSPEED>10000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFTYPE>6</IFTYPE>
          <MAC>6c:50:4d:39:59:82</MAC>
          <TRUNK>1</TRUNK>
          <VLANS>
            <VLAN>
              <NAME>printer</NAME>
              <NUMBER>281</NUMBER>
              <TAGGED>1</TAGGED>
            </VLAN>
            <VLAN>
              <NAME>admin</NAME>
              <NUMBER>280</NUMBER>
              <TAGGED>1</TAGGED>
            </VLAN>
          </VLANS>
        </PORT>
        <PORT>
          <IFDESCR>Port-channel10</IFDESCR>
          <IFNAME>Po10</IFNAME>
          <IFNUMBER>5005</IFNUMBER>
          <IFSPEED>4294967295</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFTYPE>53</IFTYPE>
          <MAC>6c:50:4d:39:59:88</MAC>
          <TRUNK>1</TRUNK>
          <AGGREGATE>
            <PORT>10001</PORT>
            <PORT>10002</PORT>
          </AGGREGATE>
        </PORT>
        <PORT>
          <IFDESCR>vlan0</IFDESCR>
          <IFNAME>vlan0</IFNAME>
          <IFNUMBER>5006</IFNUMBER>
          <IFSPEED>4294967295</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFTYPE>54</IFTYPE>
          <MAC>6c:50:4d:39:59:89</MAC>
          <TRUNK>1</TRUNK>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment = new NetworkEquipment();

        $this->items_id = $networkEquipment->add([
            'serial'      => 'FOC147UJEU4',
            'entities_id' => 0,
        ]);

        $this->assertGreaterThan(0, $this->items_id);

        $converter = new Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        new Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $this->assertEquals(1, count($networkEquipment->find()));
    }

    #[Depends('testAddNetworkEquipment')]
    public function testgetDeviceIPOfTaskjobID()
    {
        $pfTask    = new PluginGlpiinventoryTask();
        $pfTaskJob = new PluginGlpiinventoryTaskJob();
        $pfIpRange = new PluginGlpiinventoryIPRange();
        $networkEquipment = new NetworkEquipment();
        $pfNetworkinventory = new PluginGlpiinventoryNetworkinventory();

        $input = ['name' => 'MyTask', 'entities_id' => 0,
            'reprepare_if_successful' => 1, 'comment' => 'MyComments',
            'is_active' => 1,
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertGreaterThan(0, $tasks_id);

        $this->assertTrue($pfTask->getFromDB($tasks_id));
        $this->assertEquals('MyTask', $pfTask->fields['name']);
        $this->assertEquals(1, $pfTask->fields['is_active']);

        $input = ['ip_start' => '192.168.40.1',
            'ip_end' => '192.168.40.254',
        ];
        $iprange_id = $pfIpRange->add($input);
        $this->assertTrue($pfIpRange->getFromDB($iprange_id));
        $this->assertEquals('192.168.40.1', $pfIpRange->fields['ip_start']);
        $this->assertEquals('192.168.40.254', $pfIpRange->fields['ip_end']);

        $input = ['plugin_glpiinventory_tasks_id' => $tasks_id,
            'name'        => 'networkinventory',
            'method'      => 'networkinventory',
            'targets'     => '[{"PluginGlpiinventoryIPRange":"' . $iprange_id . '"}]',
        ];
        $taskjobs_id = $pfTaskJob->add($input);
        $this->assertGreaterThan(0, $taskjobs_id);
        $this->assertTrue($pfTaskJob->getFromDB($taskjobs_id));
        $this->assertEquals('networkinventory', $pfTaskJob->fields['name']);
        $this->assertEquals(
            '[{"PluginGlpiinventoryIPRange":"' . $iprange_id . '"}]',
            $pfTaskJob->fields['targets']
        );

        $networkEquipment->getFromDBByCrit(['name' => 'switchr2d2']);
        $ip = $pfNetworkinventory->getDeviceIPOfTaskID('NetworkEquipment', $networkEquipment->fields['id'], $tasks_id);
        $this->assertEquals('192.168.40.67', $ip);
    }
}
