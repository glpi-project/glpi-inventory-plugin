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

class CollectsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {

        // Delete all tasks
        $pfTask = new PluginGlpiinventoryTask();
        $items = $pfTask->find();
        foreach ($items as $item) {
            $pfTask->delete(['id' => $item['id']], true);
        }

        // Delete all computers
        $computer = new Computer();
        $items = $computer->find(['NOT' => ['name' => ['LIKE', '_test_pc%']]]);
        foreach ($items as $item) {
            $computer->delete(['id' => $item['id']], true);
        }

        // Delete all agents
        $agent = new Agent();
        $items = $agent->find();
        foreach ($items as $item) {
            $agent->delete(['id' => $item['id']], true);
        }

        // Delete all collects
        $pfCollect = new PluginGlpiinventoryCollect();
        $items = $pfCollect->find();
        foreach ($items as $item) {
            $pfCollect->delete(['id' => $item['id']], true);
        }
    }


    /**
     * @test
     */
    public function prepareDb()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollect = new PluginGlpiinventoryCollect();

        $input = [
         'name'         => 'Registry collect',
         'entities_id'  => 0,
         'is_recursive' => 0,
         'type'         => 'registry',
         'is_active'    => 1
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $input = [
         'name'                                 => 'Registry collection',
         'plugin_glpiinventory_collects_id'   => $collects_id,
         'hive'                                 => 'HKEY_LOCAL_MACHINE',
         'path'                                 => '/',
         'key'                                  => 'daKey'
        ];

        $pfCollect_Registry = new PluginGlpiinventoryCollect_Registry();
        $collectRegistryId = $pfCollect_Registry->add($input);
        $this->assertNotFalse($collectRegistryId);

        $input = [
          'name'                                => 'WMI',
          'plugin_glpiinventory_collects_id'  => $collects_id,
          'moniker'                             => 'DaWMI'
        ];

        $pfCollect_Wmi = new PluginGlpiinventoryCollect_Wmi();
        $collectWmiId = $pfCollect_Wmi->add($input);
        $this->assertNotFalse($collectWmiId);

        $input = [
         'name'                                 => 'PHP files',
         'plugin_glpiinventory_collects_id'   => $collects_id,
         'dir'                                  => '/var/www',
         'is_recursive'                         => 1,
         'filter_regex'                         => '*\.php',
         'filter_is_file'                       => 1,
         'filter_is_dir'                        => 0
        ];

        $pfCollect_File = new PluginGlpiinventoryCollect_File();
        $collectFileId = $pfCollect_File->add($input);
        $this->assertNotFalse($collectFileId);
    }


    /**
     * @test
     */
    public function getSearchOptionsToAdd()
    {

        $pfCollect = new PluginGlpiinventoryCollect();
        $pfCollect_Registry = new PluginGlpiinventoryCollect_Registry();
        $pfCollect_Wmi = new PluginGlpiinventoryCollect_Wmi();
        $pfCollect_File = new PluginGlpiinventoryCollect_File();

        $sopts = $pfCollect->getSearchOptionsToAdd();

        $this->assertEquals(4, count($sopts));

        $pfCollect_Registry->getFromDBByCrit(['name' => 'Registry collection']);
        $pfCollect_Wmi->getFromDBByCrit(['name' => 'WMI']);
        $pfCollect_File->getFromDBByCrit(['name' => 'PHP files']);

        $expected = [
         'table'            => 'glpi_plugin_glpiinventory_collects_registries_contents',
         'field'            => 'value',
         'linkfield'        => '',
         'name'             => __('Registry', 'glpiinventory') . " - Registry collection",
         'joinparams'       => ['jointype' => 'child'],
         'datatype'         => 'text',
         'forcegroupby'     => true,
         'massiveaction'    => false,
         'joinparams'       => [
            'condition' => "AND NEWTABLE.`plugin_glpiinventory_collects_registries_id` = " . $pfCollect_Registry->fields['id'],
            'jointype'  => 'child'
         ]
        ];
        $this->assertEquals($expected, $sopts[5200]);

        $expected = [
         'table'            => 'glpi_plugin_glpiinventory_collects_wmis_contents',
         'field'            => 'value',
         'linkfield'        => '',
         'name'             => __('WMI', 'glpiinventory') . " - WMI",
         'joinparams'       => ['jointype' => 'child'],
         'datatype'         => 'text',
         'forcegroupby'     => true,
         'massiveaction'    => false,
         'joinparams'       => [
            'condition' => "AND NEWTABLE.`plugin_glpiinventory_collects_wmis_id` = " . $pfCollect_Wmi->fields['id'],
            'jointype'  => 'child'
         ]
        ];
        $this->assertEquals($expected, $sopts[5201]);

        $expected = [
         'table'            => 'glpi_plugin_glpiinventory_collects_files_contents',
         'field'            => 'pathfile',
         'linkfield'        => '',
         'name'             => __('Find file', 'glpiinventory') . " - PHP files" .
            " - " . __('pathfile', 'glpiinventory'),
         'joinparams'       => ['jointype' => 'child'],
         'datatype'         => 'text',
         'forcegroupby'     => true,
         'massiveaction'    => false,
         'joinparams'       => [
            'condition' => "AND NEWTABLE.`plugin_glpiinventory_collects_files_id` = " . $pfCollect_File->fields['id'],
            'jointype'  => 'child'
         ]
        ];
        $this->assertEquals($expected, $sopts[5202]);

        $expected = [
         'table'            => 'glpi_plugin_glpiinventory_collects_files_contents',
         'field'            => 'size',
         'linkfield'        => '',
         'name'             => __('Find file', 'glpiinventory') . " - PHP files" .
                                    " - " . __('Size', 'glpiinventory'),
         'joinparams'       => ['jointype' => 'child'],
         'datatype'         => 'text',
         'forcegroupby'     => true,
         'massiveaction'    => false,
         'joinparams'       => [
            'condition' => "AND NEWTABLE.`plugin_glpiinventory_collects_files_id` = " . $pfCollect_File->fields['id'],
            'jointype'  => 'child'
         ]
        ];
        $this->assertEquals($expected, $sopts[5203]);
    }


    /**
     * @test
     */
    public function registryProcessWithAgent()
    {
        global $DB;

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $agent            = new Agent();
        $pfCollect          = new PluginGlpiinventoryCollect();
        $pfCollect_Registry = new PluginGlpiinventoryCollect_Registry();
        $pfTask             = new PluginGlpiinventoryTask();
        $pfTaskjob          = new PluginGlpiinventoryTaskjob();
        $pfTaskjobstate     = new PluginGlpiinventoryTaskjobstate();
        $computer           = new Computer();

        // Create a registry task with 2 paths to get
        $input = [
          'name'        => 'my registry keys',
          'entities_id' => 0,
          'type'        => 'registry',
          'is_active'   => 1
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $input = [
          'name' => 'Teamviewer',
          'plugin_glpiinventory_collects_id' => $collects_id,
          'hive' => 'HKEY_LOCAL_MACHINE',
          'path' => '/software/Wow6432Node/TeamViewer/',
          'key'  => '*',
        ];
        $registry_tm = $pfCollect_Registry->add($input);
        $this->assertNotFalse($registry_tm);

        $input = [
          'name' => 'GLPI Agent',
          'plugin_glpiinventory_collects_id' => $collects_id,
          'hive' => 'HKEY_LOCAL_MACHINE',
          'path' => '/software/GLPI-Agent/',
          'key'  => '*',
        ];
        $registry_fi = $pfCollect_Registry->add($input);
        $this->assertNotFalse($registry_fi);

        // Create computer
        $input = [
          'name'        => 'pc01',
          'entities_id' => 0
        ];
        $computers_id = $computer->add($input);
        $this->assertNotFalse($computers_id);

        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $input = [
          'name'         => 'pc01',
          'entities_id'  => 0,
          'itemtype'     => Computer::getType(),
          'items_id'     => $computers_id,
          'deviceid'     => 'pc01',
         'agenttypes_id' => $agenttype['id']
        ];
        $agents_id = $agent->add($input);
        $this->assertNotFalse($agents_id);

        // Create task
        $input = [
          'name'        => 'mycollect',
          'entities_id' => 0,
          'is_active'   => 1
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        $input = [
          'plugin_glpiinventory_tasks_id' => $tasks_id,
          'entities_id' => 0,
          'name'    => 'collectjob',
          'method'  => 'collect',
          'targets' => exportArrayToDB([['PluginGlpiinventoryCollect' => $collects_id]]),
          'actors'  => exportArrayToDB([['Computer' => $computers_id]]),
        ];
        $taskjobs_id = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobs_id);

        $methods = [];
        foreach (PluginGlpiinventoryStaticmisc::getmethods() as $method) {
            $methods[] = $method['method'];
        }
        $pfTask->prepareTaskjobs($methods);
        $jobstates = $pfTaskjobstate->find();
        $this->assertEquals(1, count($jobstates));
        $jobstate = current($jobstates);

        // Get jobs
        $resultObject = $pfCollect->communication('getJobs', 'pc01', null);
        $result = json_encode($resultObject);

        $matches = [];
        preg_match('/"token":"([a-z0-9]+)"/', $result, $matches);
        $this->assertEquals($result, '{"jobs":[{"function":"getFromRegistry","path":"HKEY_LOCAL_MACHINE\/software\/Wow6432Node\/TeamViewer\/*","uuid":"' . $jobstate['uniqid'] . '","_sid":' . $registry_tm . '},'
                                          . '{"function":"getFromRegistry","path":"HKEY_LOCAL_MACHINE\/software\/GLPI-Agent\/*","uuid":"' . $jobstate['uniqid'] . '","_sid":' . $registry_fi . '}],"postmethod":"POST","token":"' . $matches[1] . '"}');
        // answer 1
        $params = [
          'action'                => 'setAnswer',
          'InstallationDate'      => '2016-07-15',
          'Version'               => '11.0.62308',
          'UpdateVersion'         => '11.0.59518\0\0',
          'InstallationRev'       => '1110',
          '_cpt'                  => '1',
          'MIDInitiativeGUID'     => '{da2b3220-3d00-4f0f-93af-d38604c78405}',
          'ClientIC'              => '0x41A3B7BA',
          'uuid'                  => $jobstate['uniqid'],
          '_sid'                  => $registry_tm,
          'InstallationDirectory' => 'C:\\Program Files (x86)\\TeamViewer'
        ];
        $_GET = $params;
        $resultObject = $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);
        $result = json_encode($resultObject);

        $this->assertEquals($result, '{}');

        // answer 2
        $params = [
          'action'                  => 'setAnswer',
          'backend-collect-timeout' => 180,
          'httpd-port'              => '62354',
          'no-ssl-check'            => 1,
          'server'                  => 'http://10.0.2.2/glpi090/plugins/glpiinventory/',
          'logfile'                 => 'C:\\Program Files\\GLPI-Agent\\glpi-agent.log',
          'timeout'                 => 180,
          'httpd-trust'             => '127.0.0.1/32',
          'uuid'                    => $jobstate['uniqid'],
          '_sid'                    => $registry_tm,
          '_cpt'                    => '1',
          'httpd-ip'                => '0.0.0.0',
          'logger'                  => 'File',
          'debug'                   => '1',
          'delaytime'               => '3600',
          'logfile-maxsize'         => '16'
        ];

        $_GET = $params;
        $resultObject = $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);
        $result = json_encode($resultObject);

        $this->assertEquals($result, '{}');

        // jobsdone
        $params = [
          'action' => 'jobsDone',
          'uuid'   => $jobstate['uniqid'],
          ];

        $_GET = $params;
        $resultObject = $pfCollect->communication('jobsDone', null, $jobstate['uniqid']);
        $result = json_encode($resultObject);

        $this->assertEquals($result, '{}');
    }


    /**
     * @test
     */
    public function wmiProcessWithAgent()
    {

        // Delete all tasks
        $pfTask = new PluginGlpiinventoryTask();
        $items = $pfTask->find();
        foreach ($items as $item) {
            $pfTask->delete(['id' => $item['id']], true);
        }

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $agent = new Agent();
        $pfCollect = new PluginGlpiinventoryCollect();
        $pfCollect_Wmi = new PluginGlpiinventoryCollect_Wmi();
        $pfCollect_Wmi_Content = new PluginGlpiinventoryCollect_Wmi_Content();
        $pfTask = new PluginGlpiinventoryTask();
        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $computer = new Computer();

        // Create a registry task with 2 paths to get
        $input = [
          'name'        => 'my wmi keys',
          'entities_id' => 0,
          'type'        => 'wmi',
          'is_active'   => 1
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $input = [
          'name'       => 'keyboad name',
          'plugin_glpiinventory_collects_id' => $collects_id,
          'moniker'    => '',
          'class'      => 'Win32_Keyboard',
          'properties' => 'Name',
        ];
        $registry_kn = $pfCollect_Wmi->add($input);
        $this->assertNotFalse($registry_kn);

        $input = [
          'name'       => 'keyboad description',
          'plugin_glpiinventory_collects_id' => $collects_id,
          'moniker'    => '',
          'class'      => 'Win32_Keyboard',
          'properties' => 'Description',
        ];
        $registry_kd = $pfCollect_Wmi->add($input);
        $this->assertNotFalse($registry_kd);

        // get computer
        $computer->getFromDBByCrit(['name' => 'pc01']);
        $computers_id = $computer->fields['id'];
        $agent->getFromDBByCrit(['name' => 'pc01']);
        $agents_id = $agent->fields['id'];

        // Create task
        $input = [
          'name'        => 'mycollect',
          'entities_id' => 0,
          'is_active'   => 1
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        $input = [
          'plugin_glpiinventory_tasks_id' => $tasks_id,
          'entities_id' => 0,
          'name'    => 'collectjob',
          'method'  => 'collect',
          'targets' => exportArrayToDB([['PluginGlpiinventoryCollect' => $collects_id]]),
          'actors'  => exportArrayToDB([['Computer' => $computers_id]]),
        ];
        $taskjobs_id = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobs_id);

        $methods = [];
        foreach (PluginGlpiinventoryStaticmisc::getmethods() as $method) {
            $methods[] = $method['method'];
        }
        $pfTask->prepareTaskjobs($methods);
        $jobstates = $pfTaskjobstate->find();

        $this->assertEquals(1, count($jobstates));
        $jobstate = current($jobstates);

        // Get jobs
        $_GET = [];
        $resultObject = $pfCollect->communication('getJobs', 'pc01', null);
        $result = json_encode($resultObject);

        preg_match('/"token":"([a-z0-9]+)"/', $result, $matches);
        $this->assertEquals($result, '{"jobs":[{"function":"getFromWMI","class":"Win32_Keyboard","properties":["Name"],"uuid":"' . $jobstate['uniqid'] . '","_sid":' . $registry_kn . '},'
                                          . '{"function":"getFromWMI","class":"Win32_Keyboard","properties":["Description"],"uuid":"' . $jobstate['uniqid'] . '","_sid":' . $registry_kd . '}],"postmethod":"POST","token":"' . $matches[1] . '"}');

        // answer 1
        $params = [
          'action' => 'setAnswer',
          'uuid'   => $jobstate['uniqid'],
          '_sid'   => $registry_kn,
          '_cpt'   => '1',
          'Name'   => 'Enhanced (101- or 102-key)'
        ];
        $_GET = $params;
        $resultObject = $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);
        $result = json_encode($resultObject);

        $this->assertEquals($result, '{}');

        // answer 2
        $params = [
          'action'      => 'setAnswer',
          'uuid'        => $jobstate['uniqid'],
          '_sid'        => $registry_kd,
          '_cpt'        => '1',
          'Description' => 'Standard PS/2 Keyboard'
        ];
        $_GET = $params;
        $resultObject = $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);
        $result = json_encode($resultObject);

        $this->assertEquals($result, '{}');

        // jobsdone
        $params = [
         'action' => 'jobsDone',
         'uuid'   => $jobstate['uniqid'],
        ];
        $_GET = $params;
        $resultObject = $pfCollect->communication('jobsDone', null, $jobstate['uniqid']);
        $result = json_encode($resultObject);

        $this->assertEquals($result, '{}');

        // check data in db
        $content = $pfCollect_Wmi_Content->find();
        $items = [];
        foreach ($content as $data) {
            unset($data['id']);
            $items[] = $data;
        }

        $reference = [
         [
            'computers_id' => $computers_id,
            'plugin_glpiinventory_collects_wmis_id' => $registry_kn,
            'property'     => 'Name',
            'value'        => 'Enhanced (101- or 102-key)'
         ],
         [
            'computers_id' => $computers_id,
            'plugin_glpiinventory_collects_wmis_id' => $registry_kd,
            'property'     => 'Description',
            'value'        => 'Standard PS/2 Keyboard'
         ]
        ];
        $this->assertEquals($reference, $items);
    }


    /**
     * @test
     */
    public function filesProcessWithAgent()
    {

        // Delete all tasks
        $pfTask = new PluginGlpiinventoryTask();
        $items = $pfTask->find();
        foreach ($items as $item) {
            $pfTask->delete(['id' => $item['id']], true);
        }

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $agent = new Agent();
        $pfCollect = new PluginGlpiinventoryCollect();
        $pfCollect_File = new PluginGlpiinventoryCollect_File();
        $pfCollect_File_Content = new PluginGlpiinventoryCollect_File_Content();
        $pfTask = new PluginGlpiinventoryTask();
        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $computer = new Computer();

        // Create a registry task with 2 paths to get
        $input = [
          'name'        => 'my files search',
          'entities_id' => 0,
          'type'        => 'file',
          'is_active'   => 1
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $input = [
          'name'           => 'desktop',
          'plugin_glpiinventory_collects_id' => $collects_id,
          'dir'            => 'C:\Users\toto\Desktop',
          'limit'          => 10,
          'is_recursive'   => 1,
          'filter_is_file' => 1,
        ];
        $registry_desktop = $pfCollect_File->add($input);
        $this->assertNotFalse($registry_desktop);

        $input = [
          'name'           => 'downloads',
          'plugin_glpiinventory_collects_id' => $collects_id,
          'dir'            => 'C:\Users\toto\Downloads',
          'limit'          => 10,
          'is_recursive'   => 1,
          'filter_is_file' => 1,
        ];
        $registry_down = $pfCollect_File->add($input);
        $this->assertNotFalse($registry_down);

        // get computer
        $computer->getFromDBByCrit(['name' => 'pc01']);
        $computers_id = $computer->fields['id'];
        $agent->getFromDBByCrit(['name' => 'pc01']);
        $agents_id = $agent->fields['id'];

        // Create task
        $input = [
          'name'        => 'mycollect',
          'entities_id' => 0,
          'is_active'   => 1
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        $input = [
          'plugin_glpiinventory_tasks_id' => $tasks_id,
          'entities_id' => 0,
          'name'    => 'collectjob',
          'method'  => 'collect',
          'targets' => exportArrayToDB([['PluginGlpiinventoryCollect' => $collects_id]]),
          'actors'  => exportArrayToDB([['Computer' => $computers_id]]),
        ];
        $taskjobs_id = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobs_id);
        $methods = [];
        foreach (PluginGlpiinventoryStaticmisc::getmethods() as $method) {
            $methods[] = $method['method'];
        }
        $pfTask->prepareTaskjobs($methods);
        $jobstates = $pfTaskjobstate->find();

        $this->assertEquals(1, count($jobstates));
        $jobstate = current($jobstates);

        // Get jobs
        $_GET = [];
        $resultObject = $pfCollect->communication('getJobs', 'pc01', null);
        $result = json_encode($resultObject);

        preg_match('/"token":"([a-z0-9]+)"/', $result, $matches);
        $this->assertEquals($result, '{"jobs":[{"function":"findFile","dir":"C:Users\totoDesktop","limit":10,"recursive":1,"filter":{"is_file":1,"is_dir":0},"uuid":"' . $jobstate['uniqid'] . '","_sid":' . $registry_desktop . '},'
                                          . '{"function":"findFile","dir":"C:Users\totoDownloads","limit":10,"recursive":1,"filter":{"is_file":1,"is_dir":0},"uuid":"' . $jobstate['uniqid'] . '","_sid":' . $registry_down . '}],"postmethod":"POST","token":"' . $matches[1] . '"}');
        // answer 1
        $params = [
          'action' => 'setAnswer',
          'uuid'   => $jobstate['uniqid'],
          '_sid'   => $registry_desktop,
          '_cpt'   => '3',
          'path'   => 'C:\\Users\\toto\\Desktop/06_import_tickets.php',
          'size'   => 5053,
          'sendheaders' => false //for test
        ];
        $_GET = $params;
        $resultObject = $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);
        $result = json_encode($resultObject);

        $this->assertEquals($result, '{}');

        $params = [
          'action' => 'setAnswer',
          'uuid'   => $jobstate['uniqid'],
          '_sid'   => $registry_desktop,
          '_cpt'   => '2',
          'path'   => 'C:\\Users\\toto\\Desktop/glpiinventory.txt',
          'size'   => 28,
          'sendheaders' => false //for test
        ];
        $_GET = $params;
        $resultObject = $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);
        $result = json_encode($resultObject);

        $this->assertEquals($result, '{}');

        $params = [
          'action' => 'setAnswer',
          'uuid'   => $jobstate['uniqid'],
          '_sid'   => $registry_desktop,
          '_cpt'   => '1',
          'path'   => 'C:\\Users\\toto\\Desktop/desktop.ini',
          'size'   => 282,
          'sendheaders' => false //for test
        ];
        $_GET = $params;
        $resultObject = $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);
        $result = json_encode($resultObject);

        $this->assertEquals($result, '{}');

        // answer 2
        $params = [
          'action' => 'setAnswer',
          'uuid'   => $jobstate['uniqid'],
          '_sid'   => $registry_down,
          '_cpt'   => '2',
          'path'   => 'C:\\Users\\toto\\Downloads/jxpiinstall.exe',
          'size'   => 738368,
          'sendheaders' => false //for test
        ];
        $_GET = $params;
        $resultObject = $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);
        $result = json_encode($resultObject);

        $this->assertEquals($result, '{}');

        $params = [
          'action' => 'setAnswer',
          'uuid'   => $jobstate['uniqid'],
          '_sid'   => $registry_down,
          '_cpt'   => '1',
          'path'   => 'C:\\Users\\toto\\Downloads/npp.6.9.2.Installer.exe',
          'size'   => 4211112,
          'sendheaders' => false //for test
        ];
        $_GET = $params;
        $resultObject = $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);
        $result = json_encode($resultObject);

        $this->assertEquals($result, '{}');

        // jobsdone
        $params = [
          'action' => 'jobsDone',
          'uuid'   => $jobstate['uniqid'],
          ];

        $_GET = $params;
        $resultObject = $pfCollect->communication('jobsDone', null, $jobstate['uniqid']);
        $result = json_encode($resultObject);

        $this->assertEquals($result, '{}');

        // check data in db
        $content = $pfCollect_File_Content->find();
        $items = [];
        foreach ($content as $data) {
            unset($data['id']);
            $items[] = $data;
        }

        $reference = [
         [
            'computers_id' => "$computers_id",
            'plugin_glpiinventory_collects_files_id' => "$registry_desktop",
            'pathfile'     => 'C:/Users/toto/Desktop/06_import_tickets.php',
            'size'         => '5053'
         ],
         [
            'computers_id' => "$computers_id",
            'plugin_glpiinventory_collects_files_id' => "$registry_desktop",
            'pathfile'     => 'C:/Users/toto/Desktop/glpiinventory.txt',
            'size'         => '28'
         ],
         [
            'computers_id' => "$computers_id",
            'plugin_glpiinventory_collects_files_id' => "$registry_desktop",
            'pathfile'     => 'C:/Users/toto/Desktop/desktop.ini',
            'size'         => '282'
         ],
         [
            'computers_id' => "$computers_id",
            'plugin_glpiinventory_collects_files_id' => "$registry_down",
            'pathfile'     => 'C:/Users/toto/Downloads/jxpiinstall.exe',
            'size'         => '738368'
         ],
         [
            'computers_id' => "$computers_id",
            'plugin_glpiinventory_collects_files_id' => "$registry_down",
            'pathfile'     => 'C:/Users/toto/Downloads/npp.6.9.2.Installer.exe',
            'size'         => '4211112'
         ]
        ];
        $this->assertEquals($reference, $items);
    }


    /**
     * @test
     */
    public function testFilesCleanComputer()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollect = new PluginGlpiinventoryCollect_File();
        $computer = new Computer();

        $input = [
         'name'        => 'pc02',
         'entities_id' => 0
        ];

        $computerId = $computer->add($input);
        $this->assertNotFalse($computerId);

        $input = [
         'name'         => 'Files collect to clean',
         'entities_id'  => $_SESSION['glpiactive_entity'],
         'is_recursive' => '0',
         'type'         => 'registry',
         'is_active'    => 1
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $pfCollect_File = new PluginGlpiinventoryCollect_File();
        $pfCollect_File->getFromDBByCrit(['name' => 'PHP files']);
        $file_id = $pfCollect_File->fields['id'];

        $input = [
         'computers_id'                                     => $computerId,
         'plugin_glpiinventory_collects_registries_id'    => $file_id,
         'key'                                              => 'test_key',
         'value'                                            => 'test_value'
        ];
        $pfCollect_File_Contents = new PluginGlpiinventoryCollect_File_Content();
        $collectFileContentId = $pfCollect_File_Contents->add($input);
        $this->assertNotFalse($collectFileContentId);

        //First, check if file contents does exist
        $pfCollect_File_Contents = new PluginGlpiinventoryCollect_File_Content();
        $pfCollect_File_Contents->getFromDB($collectFileContentId);

        $this->assertEquals(5, count($pfCollect_File_Contents->fields));

        //Second, clean and check if it has been removed
        $pfCollect_File_Contents = new PluginGlpiinventoryCollect_File_Content();
        $pfCollect_File_Contents->cleanComputer($computerId);

        $pfCollect_File_Contents->getFromDB($collectFileContentId);
        $this->assertEquals(0, count($pfCollect_File_Contents->fields));
    }


    /**
     * @test
     */
    public function testRegistryCleanComputer()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollect = new PluginGlpiinventoryCollect();
        $pfCollect_Registry = new PluginGlpiinventoryCollect_Registry();
        $computer = new Computer();

        $pfCollect_Registry->getFromDBByCrit(['name' => 'Registry collection']);
        $computer->getFromDBByCrit(['name' => 'pc01']);

        $input = [
         'computers_id'                                     => $computer->fields['id'],
         'plugin_glpiinventory_collects_registries_id'    => $pfCollect_Registry->fields['id'],
         'key'                                              => 'test_key',
         'value'                                            => 'test_value'
        ];
        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $collectRegistryContentId = $pfCollect_Registry_Contents->add($input);
        $this->assertNotFalse($collectRegistryContentId);

        //First, check if registry contents does exist
        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $pfCollect_Registry_Contents->getFromDB($collectRegistryContentId);

        $this->assertEquals(5, count($pfCollect_Registry_Contents->fields));

        //Second, clean and check if it has been removed
        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $pfCollect_Registry_Contents->cleanComputer($computer->fields['id']);

        $pfCollect_Registry_Contents->getFromDB($collectRegistryContentId);
        $this->assertEquals(0, count($pfCollect_Registry_Contents->fields));
    }


    /**
     * @test
     */
    public function testWmiCleanComputer()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollect = new PluginGlpiinventoryCollect();
        $pfCollect_Wmi = new PluginGlpiinventoryCollect_Wmi();
        $computer = new Computer();

        $pfCollect_Wmi->getFromDBByCrit(['name' => 'WMI']);
        $computer->getFromDBByCrit(['name' => 'pc01']);

        $input = [
         'computers_id'                                     => $computer->fields['id'],
         'plugin_glpiinventory_collects_registries_id'    => $pfCollect_Wmi->fields['id'],
         'key'                                              => 'test_key',
         'value'                                            => 'test_value'
        ];
        $pfCollect_Wmi_Contents = new PluginGlpiinventoryCollect_Wmi_Content();
        $collectWmiContentId = $pfCollect_Wmi_Contents->add($input);
        $this->assertNotFalse($collectWmiContentId);

        //First, check if wmi contents does exist
        $pfCollect_Wmi_Contents = new PluginGlpiinventoryCollect_Wmi_Content();
        $pfCollect_Wmi_Contents->getFromDB($collectWmiContentId);

        $this->assertEquals(5, count($pfCollect_Wmi_Contents->fields));

        //Second, clean and check if it has been removed
        $pfCollect_Wmi_Contents = new PluginGlpiinventoryCollect_Wmi_Content();
        $pfCollect_Wmi_Contents->cleanComputer($computer->fields['id']);

        $pfCollect_Wmi_Contents->getFromDB($collectWmiContentId);
        $this->assertEquals(0, count($pfCollect_Wmi_Contents->fields));
    }


    /**
     * @test
     */
    public function testDeleteComputer()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        // Create computer

        $computer = new Computer();
        $computer->getFromDBByCrit(['name' => 'pc01']);
        $computers_id = $computer->fields['id'];

        $pfCollect = new PluginGlpiinventoryCollect();

        //populate wmi data
        $input = [
         'name'         => 'WMI collect',
         'entities_id'  => $_SESSION['glpiactive_entity'],
         'is_recursive' => '0',
         'type'         => 'registry',
         'is_active'    => 1
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $input = [
          'name'                                => 'WMI',
          'plugin_glpiinventory_collects_id'  => $collects_id,
          'moniker'                             => 'DaWMI'
        ];
        $pfCollect_Wmi = new PluginGlpiinventoryCollect_Wmi();
        $wmi_id = $pfCollect_Wmi->add($input);
        $this->assertNotFalse($wmi_id);

        $input = [
         'computers_id'                                     => $computers_id,
         'plugin_glpiinventory_collects_registries_id'    => $wmi_id,
         'key'                                              => 'test_key',
         'value'                                            => 'test_value'
        ];
        $pfCollect_Wmi_Contents = new PluginGlpiinventoryCollect_Wmi_Content();
        $collectWmiContectId = $pfCollect_Wmi_Contents->add($input);
        $this->assertNotFalse($collectWmiContectId);

        //check if wmi contents does exist
        $pfCollect_Wmi_Contents = new PluginGlpiinventoryCollect_Wmi_Content();
        $pfCollect_Wmi_Contents->getFromDB($collectWmiContectId);

        $this->assertEquals(5, count($pfCollect_Wmi_Contents->fields));

        //populate files data
        $input = [
         'name'         => 'Files collect',
         'entities_id'  => $_SESSION['glpiactive_entity'],
         'is_recursive' => '0',
         'type'         => 'registry',
         'is_active'    => 1
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $pfCollect_File = new PluginGlpiinventoryCollect_File();
        $pfCollect_File->getFromDBByCrit(['name' => 'PHP files']);
        $file_id = $pfCollect_File->fields['id'];

        $input = [
         'computers_id'                                     => $computers_id,
         'plugin_glpiinventory_collects_registries_id'    => $file_id,
         'key'                                              => 'test_key',
         'value'                                            => 'test_value'
        ];
        $pfCollect_File_Contents = new PluginGlpiinventoryCollect_File_Content();
        $collectFileContentId = $pfCollect_File_Contents->add($input);
        $this->assertNotFalse($collectFileContentId);

        //check if file contents does exist
        $pfCollect_File_Contents = new PluginGlpiinventoryCollect_File_Content();
        $pfCollect_File_Contents->getFromDB($collectFileContentId);

        $this->assertEquals(5, count($pfCollect_File_Contents->fields));

        //populate registry data
        $input = [
         'name'         => 'Registry collect',
         'entities_id'  => $_SESSION['glpiactive_entity'],
         'is_recursive' => '0',
         'type'         => 'registry',
         'is_active'    => 1
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $input = [
         'name'                                 => 'Registry collection',
         'plugin_glpiinventory_collects_id'   => $collects_id,
         'hive'                                 => 'HKEY_LOCAL_MACHINE',
         'path'                                 => '/',
         'key'                                  => 'daKey'
        ];
        $pfCollect_Registry = new PluginGlpiinventoryCollect_Registry();
        $registry_id = $pfCollect_Registry->add($input);
        $this->assertNotFalse($registry_id);

        $input = [
         'computers_id'                                     => $computers_id,
         'plugin_glpiinventory_collects_registries_id'    => $registry_id,
         'key'                                              => 'test_key',
         'value'                                            => 'test_value'
        ];
        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $collectRegistryContentId = $pfCollect_Registry_Contents->add($input);
        $this->assertNotFalse($collectRegistryContentId);

        // check if registry contents does exist
        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $pfCollect_Registry_Contents->getFromDB($collectRegistryContentId);

        $this->assertEquals(5, count($pfCollect_Registry_Contents->fields));

        // delete computer and check if it has been put in trash
        $computer->delete(['id' => $computers_id]);
        $this->assertTrue($computer->getFromDB($computers_id));

        $pfCollect_Wmi_Contents = new PluginGlpiinventoryCollect_Wmi_Content();
        $pfCollect_Wmi_Contents->getFromDB($collectWmiContectId);
        $this->assertEquals(5, count($pfCollect_Wmi_Contents->fields));

        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $pfCollect_Registry_Contents->getFromDB($collectRegistryContentId);
        $this->assertEquals(5, count($pfCollect_Registry_Contents->fields));

        $pfCollect_File_Contents = new PluginGlpiinventoryCollect_File_Content();
        $pfCollect_File_Contents->getFromDB($collectFileContentId);
        $this->assertEquals(5, count($pfCollect_File_Contents->fields));

        // purge computer and check if it has been removed
        $computer->delete(['id' => $computers_id], true);
        $this->assertFalse($computer->getFromDB($computers_id));

        $pfCollect_Wmi_Contents = new PluginGlpiinventoryCollect_Wmi_Content();
        $pfCollect_Wmi_Contents->getFromDB($collectWmiContectId);
        $this->assertEquals(0, count($pfCollect_Wmi_Contents->fields));

        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $pfCollect_Registry_Contents->getFromDB($collectRegistryContentId);
        $this->assertEquals(0, count($pfCollect_Registry_Contents->fields));

        $pfCollect_File_Contents = new PluginGlpiinventoryCollect_File_Content();
        $pfCollect_File_Contents->getFromDB($collectFileContentId);
        $this->assertEquals(0, count($pfCollect_File_Contents->fields));
    }

    public function testTaskWithDeletedActor()
    {
        // Delete all tasks
        $pfTask = new PluginGlpiinventoryTask();
        $items = $pfTask->find();
        foreach ($items as $item) {
            $pfTask->delete(['id' => $item['id']], true);
        }

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfTask = new PluginGlpiinventoryTask();
        $pfTaskjob = new PluginGlpiinventoryTaskjob();

        // Create task
        $input = [
            'name'        => 'mycollect',
            'entities_id' => 0,
            'is_active'   => 1
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        $input = [
            'plugin_glpiinventory_tasks_id' => $tasks_id,
            'entities_id' => 0,
            'name'    => 'collectjob',
            'method'  => 'collect',
            'targets' => exportArrayToDB([['PluginGlpiinventoryCollect' => 0]]),
            'actors'  => exportArrayToDB([['Agent' => 0]]),
        ];
        $taskjobs_id = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobs_id);
        $methods = [];
        foreach (PluginGlpiinventoryStaticmisc::getmethods() as $method) {
            $methods[] = $method['method'];
        }
        $pfTask->prepareTaskjobs($methods);

        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfTaskjob->getFromDB($taskjobs_id);
        // Check actors
        $this->assertEquals('[]', $pfTaskjob->fields['actors']);
    }
}
