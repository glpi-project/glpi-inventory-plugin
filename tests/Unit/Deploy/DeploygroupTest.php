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

class DeploygroupTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        global $DB;

       // Delete all groups
        $pfDeploygroup = new PluginGlpiinventoryDeployGroup();
        $items = $pfDeploygroup->find();
        foreach ($items as $item) {
            $pfDeploygroup->delete(['id' => $item['id']], true);
        }

       // Delete all computers
        $computer = new Computer();
        $items = $computer->find(['NOT' => ['name' => ['LIKE', '_test_pc%']]]);
        foreach ($items as $item) {
            $computer->delete(['id' => $item['id']], true);
        }

        $DB->query("truncate table glpi_computers");
    }


   /**
    * @test
    */
    public function AddGroup()
    {
        $pfDeploygroup = new PluginGlpiinventoryDeployGroup();
        $input = ['name'    => 'MyGroup',
                'type'    => PluginGlpiinventoryDeployGroup::STATIC_GROUP,
                'comment' => 'MyComment'
               ];
        $groups_id = $pfDeploygroup->add($input);
        $this->assertGreaterThan(0, $groups_id);

        $result = ['id'      => $groups_id,
                 'name'    => 'MyGroup',
                 'type'    => PluginGlpiinventoryDeployGroup::STATIC_GROUP,
                 'comment' => 'MyComment'
                ];
        $pfDeploygroup->getFromDB($groups_id);
        $this->assertEquals($pfDeploygroup->fields, $result);
    }


   /**
    * @test
    * @depends AddGroup
    */
    public function cloneStaticGroup()
    {
        $computer      = new Computer();
        $pfDeploygroup = new PluginGlpiinventoryDeployGroup();
        $pfStaticgroup = new PluginGlpiinventoryDeployGroup_Staticdata();

        $groups        = $pfDeploygroup->find(['name' => 'MyGroup']);
        $this->assertEquals(1, count($groups));

        $group     = current($groups);
        $groups_id = $group['id'];

        $computers_id_1 = $computer->add(['name' => 'MyComputer1', 'entities_id' => 1]);
        $computers_id_2 = $computer->add(['name' => 'MyComputer2', 'entities_id' => 1]);

        $pfStaticgroup->add(['plugin_glpiinventory_deploygroups_id' => $groups_id,
                           'itemtype' => 'Computer', 'items_id' => $computers_id_1]);
        $pfStaticgroup->add(['plugin_glpiinventory_deploygroups_id' => $groups_id,
                           'itemtype' => 'Computer', 'items_id' => $computers_id_2]);

        $this->assertTrue($pfDeploygroup->duplicate($groups_id));
        $this->assertFalse($pfDeploygroup->duplicate(100000000));

        $data = $pfDeploygroup->find(['name' => 'Copy of MyGroup']);
        $this->assertEquals(1, count($data));
        $tmp = current($data);

       //Store the group's id
        $new_groups_id = $tmp['id'];

        $data = $pfStaticgroup->find(
            ['plugin_glpiinventory_deploygroups_id' => $new_groups_id],
            ['items_id ASC']
        );
        $this->assertEquals(2, count($data));
        $tmp = current($data);
        $this->assertEquals('Computer', $tmp['itemtype']);
        $this->assertEquals($computers_id_1, $tmp['items_id']);

        $tmp = next($data);
        $this->assertEquals('Computer', $tmp['itemtype']);
        $this->assertEquals($computers_id_2, $tmp['items_id']);
    }


   /**
    * @test
    * @depends cloneStaticGroup
    */
    public function cloneDynamicGroup()
    {
        $pfDeploygroup = new PluginGlpiinventoryDeployGroup();
        $input = ['name'    => 'Dynamic group',
                'type'    => PluginGlpiinventoryDeployGroup::DYNAMIC_GROUP,
                'comment' => 'My dynamic group'
               ];
        $groups_id = $pfDeploygroup->add($input);
        $this->assertGreaterThan(0, $groups_id);

        $json = "a:2:{s:8:\"criteria\";a:1:{i:0;a:3:{s:5:\"field\";s:2:\"45\";s:10:\"searchtype\";s:8:\"contains\";s:5:\"value\";s:7:\"windows\";}}s:12:\"metacriteria\";N;}";
        $pfDynamicGroup = new PluginGlpiinventoryDeployGroup_Dynamicdata();
        $input = ['plugin_glpiinventory_deploygroups_id' => $groups_id,
                'fields_array'     => $json,
                'can_update_group' => 0
               ];
        $dynamicgroups_id = $pfDynamicGroup->add($input);
        $this->assertGreaterThan(0, $dynamicgroups_id);

        $this->assertTrue($pfDeploygroup->duplicate($groups_id));

        $data = $pfDeploygroup->find(['name' => 'Copy of Dynamic group']);
        $this->assertEquals(1, count($data));
        $tmp = current($data);
        $new_groups_id = $tmp['id'];
        $this->assertFalse($pfDeploygroup->duplicate(100));

        $data = $pfDynamicGroup->find(['plugin_glpiinventory_deploygroups_id' => $new_groups_id]);
        $this->assertEquals(1, count($data));
        $tmp = current($data);
        $this->assertEquals($json, $tmp['fields_array']);
    }


   /**
    * @test
    * @depends cloneDynamicGroup
    */
    public function updateGroup()
    {
       //Get the group have the name "Windows computers"
        $pfDeploygroup = new PluginGlpiinventoryDeployGroup();
        $data = $pfDeploygroup->find(['name' => 'Copy of Dynamic group']);
        $this->assertEquals(1, count($data));
        $tmp = current($data);
       //Store the group's id
        $groups_id = $tmp['id'];

        $input = ['name' => 'Second Dynamic group', 'id' => $groups_id];
        $this->assertTrue($pfDeploygroup->update($input));

        $data = $pfDeploygroup->find(['name' => 'Copy of Dynamic group']);
        $this->assertEquals(0, count($data));

        $data = $pfDeploygroup->find(['name' => 'Second Dynamic group']);
        $this->assertEquals(1, count($data));
    }


   /**
    * @test
    * @depends updateGroup
    */
    public function switchDynamicToStaticGroup()
    {
       //Get the group have the name "Windows computers"
        $pfDeploygroup = new PluginGlpiinventoryDeployGroup();
        $data = $pfDeploygroup->find(['name' => 'Dynamic group']);
        $this->assertEquals(1, count($data));
        $tmp = current($data);
       //Store the group's id
        $groups_id = $tmp['id'];

        $input = ['id'   => $groups_id,
                'type' => PluginGlpiinventoryDeployGroup::STATIC_GROUP];
        $this->assertTrue($pfDeploygroup->update($input));

        $pfDeploygroup->getFromDB($groups_id);
        $this->assertEquals(
            PluginGlpiinventoryDeployGroup::STATIC_GROUP,
            $pfDeploygroup->fields['type']
        );

        $pfStaticgroup = new PluginGlpiinventoryDeployGroup_Staticdata();
        $data = $pfStaticgroup->find(['plugin_glpiinventory_deploygroups_id' => $groups_id]);
        $this->assertEquals(0, count($data));
    }


   /**
    * @test
    * @depends cloneDynamicGroup
    */
    public function deleteDynamicGroup()
    {

        $pfDeploygroup  = new PluginGlpiinventoryDeployGroup();
        $pfDynamicgroup = new PluginGlpiinventoryDeployGroup_Dynamicdata();

        $data = $pfDeploygroup->find(['name' => 'Second Dynamic group']);
        $this->assertEquals(1, count($data));
        $tmp = current($data);
       //Store the group's id
        $groups_id = $tmp['id'];

       //Get group datas
        $data = $pfDynamicgroup->find(['plugin_glpiinventory_deploygroups_id' => $groups_id]);
        $this->assertEquals(1, count($data));
       //Store group data id
        $tmp = current($data);
        $dynamicgroups_id = $tmp['id'];

       //Delete the group
        $this->assertTrue($pfDeploygroup->delete(['id' => $groups_id]));
        $this->assertFalse($pfDeploygroup->getFromDB($groups_id));
        $this->assertFalse($pfDynamicgroup->getFromDB($dynamicgroups_id));
    }


   /**
    * @test
    * @depends cloneStaticGroup
    */
    public function deleteStaticGroup()
    {

       //Get the group have the name "Windows computers"
        $pfDeploygroup = new PluginGlpiinventoryDeployGroup();
        $data = $pfDeploygroup->find(['name' => 'MyGroup']);
        $this->assertEquals(1, count($data));
        $tmp = current($data);
       //Store the group's id
        $groups_id = $tmp['id'];

       //Get group datas
        $pfStaticGroup = new PluginGlpiinventoryDeployGroup_Staticdata();
        $data = $pfStaticGroup->find(['plugin_glpiinventory_deploygroups_id' => $groups_id]);
        $this->assertEquals(2, count($data));

       //Delete the group
        $this->assertTrue($pfDeploygroup->delete(['id' => $groups_id]));
        $this->assertFalse($pfDeploygroup->getFromDB($groups_id));
        foreach ($data as $staticgroup) {
            $this->assertFalse($pfStaticGroup->getFromDB($staticgroup['id']));
        }
    }


   /**
    * @test
    */
    public function ImportCsvStaticGroup()
    {
        global $DB;

       // Add some computers, with the ID
        $computer = new Computer();

        $DB->query("ALTER TABLE glpi_computers AUTO_INCREMENT = 12345;");

        $input = [
          'entities_id' => 0,
          'name' => 'computer1'
        ];
        $ret = $computer->add($input);
        $this->assertEquals(12345, $ret);

        $input = [
          'entities_id' => 0,
          'name' => 'computer2'
        ];
        $ret = $computer->add($input);
        $this->assertEquals(12346, $ret);

        $pfDeploygroup = new PluginGlpiinventoryDeployGroup();
        $pfDeploygroup_static = new PluginGlpiinventoryDeployGroup_Staticdata();

        $input = ['name'    => 'MyGroup',
                'type'    => PluginGlpiinventoryDeployGroup::STATIC_GROUP,
                'comment' => 'MyComment'
               ];
        $groups_id = $pfDeploygroup->add($input);
        $this->assertGreaterThan(0, $groups_id);

        $input_post = [
          'groups_id' => $groups_id
        ];
        $input_files = [
          'importcsvfile' => [
              'tmp_name' => realpath(dirname(__FILE__)) . '/computers.csv'
          ]
        ];
        $ret = $pfDeploygroup_static->csvImport($input_post, $input_files);
        $this->assertTrue($ret);

        $computer_list = $pfDeploygroup_static->find(['plugin_glpiinventory_deploygroups_id' => $groups_id], ['items_id']);
        $computer_list_db = [];
        foreach ($computer_list as $comp_data) {
            $computer_list_db[] = $comp_data['items_id'];
        }
        $this->assertEquals(['12345', '12346'], $computer_list_db);
    }
}
