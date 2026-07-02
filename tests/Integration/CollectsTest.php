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

use Glpi\Tests\DbTestCase;

class CollectsTest extends DbTestCase
{
    private function prepareDb(): void
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollect = new PluginGlpiinventoryCollect();

        $input = [
            'name'         => 'Registry collect',
            'entities_id'  => 0,
            'is_recursive' => 0,
            'type'         => 'registry',
            'is_active'    => 1,
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $input = [
            'name'                                 => 'Registry collection',
            'plugin_glpiinventory_collects_id'   => $collects_id,
            'hive'                                 => 'HKEY_LOCAL_MACHINE',
            'path'                                 => '/',
            'key'                                  => 'daKey',
        ];

        $pfCollect_Registry = new PluginGlpiinventoryCollect_Registry();
        $collectRegistryId = $pfCollect_Registry->add($input);
        $this->assertNotFalse($collectRegistryId);

        $input = [
            'name'                                => 'WMI',
            'plugin_glpiinventory_collects_id'  => $collects_id,
            'moniker'                             => 'DaWMI',
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
            'filter_is_dir'                        => 0,
        ];

        $pfCollect_File = new PluginGlpiinventoryCollect_File();
        $collectFileId = $pfCollect_File->add($input);
        $this->assertNotFalse($collectFileId);
    }

    private function createComputer(string $name = 'pc01'): int
    {
        global $DB;

        $computers_id = $this->createItem(Computer::class, [
            'name'        => $name,
            'entities_id' => 0,
        ])->getID();

        $agenttype = $DB->request(['FROM' => AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $this->createItem(Agent::class, [
            'name'                    => $name,
            'entities_id'             => 0,
            'itemtype'                => Computer::class,
            'items_id'                => $computers_id,
            'deviceid'                => $name,
            'agenttypes_id'           => $agenttype['id'],
            'use_module_collect_data' => 1,
        ]);
        return $computers_id;
    }

    /**
     * Delete every existing collect task (tests share the DB).
     */
    private function deleteAllTasks(): void
    {
        $pfTask = new PluginGlpiinventoryTask();
        foreach ($pfTask->find() as $item) {
            $pfTask->delete(['id' => $item['id']], true);
        }
    }

    /**
     * Create a collect task targeting a collect for a computer, prepare the task
     * jobs and return the (single) created task job state.
     *
     * @return array<string,mixed> the task job state
     */
    private function prepareCollectJob(int $collects_id, int $computers_id): array
    {
        $pfTask         = new PluginGlpiinventoryTask();
        $pfTaskjob      = new PluginGlpiinventoryTaskjob();
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();

        $tasks_id = $pfTask->add([
            'name'        => 'mycollect',
            'entities_id' => 0,
            'is_active'   => 1,
        ]);
        $this->assertNotFalse($tasks_id);

        $taskjobs_id = $pfTaskjob->add([
            'plugin_glpiinventory_tasks_id' => $tasks_id,
            'entities_id' => 0,
            'name'    => 'collectjob',
            'method'  => 'collect',
            'targets' => exportArrayToDB([[PluginGlpiinventoryCollect::class => $collects_id]]),
            'actors'  => exportArrayToDB([[Computer::class => $computers_id]]),
        ]);
        $this->assertNotFalse($taskjobs_id);

        $methods = [];
        foreach (PluginGlpiinventoryStaticmisc::getmethods() as $method) {
            $methods[] = $method['method'];
        }
        $pfTask->prepareTaskjobs($methods);

        $jobstates = $pfTaskjobstate->find();
        $this->assertEquals(1, count($jobstates));
        return current($jobstates);
    }

    public function testGetSearchOptionsToAdd(): void
    {
        $this->prepareDb();
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
                'jointype'  => 'child',
            ],
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
                'jointype'  => 'child',
            ],
        ];
        $this->assertEquals($expected, $sopts[5201]);

        $expected = [
            'table'            => 'glpi_plugin_glpiinventory_collects_files_contents',
            'field'            => 'pathfile',
            'linkfield'        => '',
            'name'             => __('Find file', 'glpiinventory') . " - PHP files"
            . " - " . __('pathfile', 'glpiinventory'),
            'joinparams'       => ['jointype' => 'child'],
            'datatype'         => 'text',
            'forcegroupby'     => true,
            'massiveaction'    => false,
            'joinparams'       => [
                'condition' => "AND NEWTABLE.`plugin_glpiinventory_collects_files_id` = " . $pfCollect_File->fields['id'],
                'jointype'  => 'child',
            ],
        ];
        $this->assertEquals($expected, $sopts[5202]);

        $expected = [
            'table'            => 'glpi_plugin_glpiinventory_collects_files_contents',
            'field'            => 'size',
            'linkfield'        => '',
            'name'             => __('Find file', 'glpiinventory') . " - PHP files"
                                    . " - " . __('Size', 'glpiinventory'),
            'joinparams'       => ['jointype' => 'child'],
            'datatype'         => 'text',
            'forcegroupby'     => true,
            'massiveaction'    => false,
            'joinparams'       => [
                'condition' => "AND NEWTABLE.`plugin_glpiinventory_collects_files_id` = " . $pfCollect_File->fields['id'],
                'jointype'  => 'child',
            ],
        ];
        $this->assertEquals($expected, $sopts[5203]);
    }

    public function testRegistryProcessWithAgent(): void
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
            'is_active'   => 1,
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

        $computers_id = $this->createComputer();

        // Create task
        $input = [
            'name'        => 'mycollect',
            'entities_id' => 0,
            'is_active'   => 1,
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        $input = [
            'plugin_glpiinventory_tasks_id' => $tasks_id,
            'entities_id' => 0,
            'name'    => 'collectjob',
            'method'  => 'collect',
            'targets' => exportArrayToDB([[PluginGlpiinventoryCollect::class => $collects_id]]),
            'actors'  => exportArrayToDB([[Computer::class => $computers_id]]),
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
            'InstallationDirectory' => 'C:\\Program Files (x86)\\TeamViewer',
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
            'logfile-maxsize'         => '16',
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

    public function testWmiProcessWithAgent(): void
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
            'is_active'   => 1,
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
        $computers_id = $this->createComputer();

        // Create task
        $input = [
            'name'        => 'mycollect',
            'entities_id' => 0,
            'is_active'   => 1,
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        $input = [
            'plugin_glpiinventory_tasks_id' => $tasks_id,
            'entities_id' => 0,
            'name'    => 'collectjob',
            'method'  => 'collect',
            'targets' => exportArrayToDB([[PluginGlpiinventoryCollect::class => $collects_id]]),
            'actors'  => exportArrayToDB([[Computer::class => $computers_id]]),
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
            'Name'   => 'Enhanced (101- or 102-key)',
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
            'Description' => 'Standard PS/2 Keyboard',
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
                'value'        => 'Enhanced (101- or 102-key)',
            ],
            [
                'computers_id' => $computers_id,
                'plugin_glpiinventory_collects_wmis_id' => $registry_kd,
                'property'     => 'Description',
                'value'        => 'Standard PS/2 Keyboard',
            ],
        ];
        $this->assertEquals($reference, $items);
    }

    public function testFilesProcessWithAgent(): void
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
            'is_active'   => 1,
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
        $computers_id = $this->createComputer();

        // Create task
        $input = [
            'name'        => 'mycollect',
            'entities_id' => 0,
            'is_active'   => 1,
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        $input = [
            'plugin_glpiinventory_tasks_id' => $tasks_id,
            'entities_id' => 0,
            'name'    => 'collectjob',
            'method'  => 'collect',
            'targets' => exportArrayToDB([[PluginGlpiinventoryCollect::class => $collects_id]]),
            'actors'  => exportArrayToDB([[Computer::class => $computers_id]]),
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
        $this->assertEquals($result, '{"jobs":[{"function":"findFile","dir":"C:\\\Users\\\toto\\\Desktop","limit":10,"recursive":1,"filter":{"is_file":1,"is_dir":0},"uuid":"' . $jobstate['uniqid'] . '","_sid":' . $registry_desktop . '},'
                                          . '{"function":"findFile","dir":"C:\\\Users\\\toto\\\Downloads","limit":10,"recursive":1,"filter":{"is_file":1,"is_dir":0},"uuid":"' . $jobstate['uniqid'] . '","_sid":' . $registry_down . '}],"postmethod":"POST","token":"' . $matches[1] . '"}');
        // answer 1
        $params = [
            'action' => 'setAnswer',
            'uuid'   => $jobstate['uniqid'],
            '_sid'   => $registry_desktop,
            '_cpt'   => '3',
            'path'   => 'C:\Users\toto\Desktop/06_import_tickets.php',
            'size'   => 5053,
            'sendheaders' => false, //for test
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
            'path'   => 'C:\Users\toto\Desktop/glpiinventory.txt',
            'size'   => 28,
            'sendheaders' => false, //for test
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
            'path'   => 'C:\Users\toto\Desktop/desktop.ini',
            'size'   => 282,
            'sendheaders' => false, //for test
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
            'path'   => 'C:\Users\toto\Downloads/jxpiinstall.exe',
            'size'   => 738368,
            'sendheaders' => false, //for test
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
            'path'   => 'C:\Users\toto\Downloads/npp.6.9.2.Installer.exe',
            'size'   => 4211112,
            'sendheaders' => false, //for test
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
                'size'         => '5053',
            ],
            [
                'computers_id' => "$computers_id",
                'plugin_glpiinventory_collects_files_id' => "$registry_desktop",
                'pathfile'     => 'C:/Users/toto/Desktop/glpiinventory.txt',
                'size'         => '28',
            ],
            [
                'computers_id' => "$computers_id",
                'plugin_glpiinventory_collects_files_id' => "$registry_desktop",
                'pathfile'     => 'C:/Users/toto/Desktop/desktop.ini',
                'size'         => '282',
            ],
            [
                'computers_id' => "$computers_id",
                'plugin_glpiinventory_collects_files_id' => "$registry_down",
                'pathfile'     => 'C:/Users/toto/Downloads/jxpiinstall.exe',
                'size'         => '738368',
            ],
            [
                'computers_id' => "$computers_id",
                'plugin_glpiinventory_collects_files_id' => "$registry_down",
                'pathfile'     => 'C:/Users/toto/Downloads/npp.6.9.2.Installer.exe',
                'size'         => '4211112',
            ],
        ];
        $this->assertEquals($reference, $items);
    }

    public function testFilesCleanComputer(): void
    {
        $this->prepareDb();
        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollect = new PluginGlpiinventoryCollect_File();
        $computer = new Computer();

        $input = [
            'name'        => 'pc02',
            'entities_id' => 0,
        ];

        $computerId = $computer->add($input);
        $this->assertNotFalse($computerId);

        $input = [
            'name'         => 'Files collect to clean',
            'entities_id'  => $_SESSION['glpiactive_entity'],
            'is_recursive' => '0',
            'type'         => 'registry',
            'is_active'    => 1,
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $pfCollect_File = new PluginGlpiinventoryCollect_File();
        $this->assertNotFalse($pfCollect_File->getFromDBByCrit(['name' => 'PHP files']));
        $file_id = $pfCollect_File->fields['id'];

        $input = [
            'computers_id'                                     => $computerId,
            'plugin_glpiinventory_collects_registries_id'    => $file_id,
            'key'                                              => 'test_key',
            'value'                                            => 'test_value',
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

    public function testRegistryCleanComputer(): void
    {
        $this->prepareDb();
        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollect_Registry = new PluginGlpiinventoryCollect_Registry();

        $this->assertNotFalse($pfCollect_Registry->getFromDBByCrit(['name' => 'Registry collection']));
        $computers_id = $this->createComputer();

        $input = [
            'computers_id'                                     => $computers_id,
            'plugin_glpiinventory_collects_registries_id'    => $pfCollect_Registry->fields['id'],
            'key'                                              => 'test_key',
            'value'                                            => 'test_value',
        ];
        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $collectRegistryContentId = $pfCollect_Registry_Contents->add($input);
        $this->assertNotFalse($collectRegistryContentId);

        //First, check if registry contents does exist
        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $pfCollect_Registry_Contents->getFromDB($collectRegistryContentId);

        $this->assertEquals(6, count($pfCollect_Registry_Contents->fields));

        //Second, clean and check if it has been removed
        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $pfCollect_Registry_Contents->cleanComputer($computers_id);

        $pfCollect_Registry_Contents->getFromDB($collectRegistryContentId);
        $this->assertEquals(0, count($pfCollect_Registry_Contents->fields));
    }

    public function testWmiCleanComputer(): void
    {
        $this->prepareDb();
        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollect_Wmi = new PluginGlpiinventoryCollect_Wmi();

        $this->assertNotFalse($pfCollect_Wmi->getFromDBByCrit(['name' => 'WMI']));
        $computers_id = $this->createComputer();

        $input = [
            'computers_id'                                     => $computers_id,
            'plugin_glpiinventory_collects_registries_id'    => $pfCollect_Wmi->fields['id'],
            'key'                                              => 'test_key',
            'value'                                            => 'test_value',
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
        $pfCollect_Wmi_Contents->cleanComputer($computers_id);

        $pfCollect_Wmi_Contents->getFromDB($collectWmiContentId);
        $this->assertEquals(0, count($pfCollect_Wmi_Contents->fields));
    }

    public function testDeleteComputer(): void
    {
        $this->prepareDb();
        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        // Create computer
        $computer = new Computer();
        $computers_id = $this->createComputer();

        $pfCollect = new PluginGlpiinventoryCollect();

        //populate wmi data
        $input = [
            'name'         => 'WMI collect',
            'entities_id'  => $_SESSION['glpiactive_entity'],
            'is_recursive' => '0',
            'type'         => 'registry',
            'is_active'    => 1,
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $input = [
            'name'                                => 'WMI',
            'plugin_glpiinventory_collects_id'  => $collects_id,
            'moniker'                             => 'DaWMI',
        ];
        $pfCollect_Wmi = new PluginGlpiinventoryCollect_Wmi();
        $wmi_id = $pfCollect_Wmi->add($input);
        $this->assertNotFalse($wmi_id);

        $input = [
            'computers_id'                                     => $computers_id,
            'plugin_glpiinventory_collects_registries_id'    => $wmi_id,
            'key'                                              => 'test_key',
            'value'                                            => 'test_value',
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
            'is_active'    => 1,
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $pfCollect_File = new PluginGlpiinventoryCollect_File();
        $this->assertNotFalse($pfCollect_File->getFromDBByCrit(['name' => 'PHP files']));
        $file_id = $pfCollect_File->fields['id'];

        $input = [
            'computers_id'                                     => $computers_id,
            'plugin_glpiinventory_collects_registries_id'    => $file_id,
            'key'                                              => 'test_key',
            'value'                                            => 'test_value',
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
            'is_active'    => 1,
        ];
        $collects_id = $pfCollect->add($input);
        $this->assertNotFalse($collects_id);

        $input = [
            'name'                                 => 'Registry collection',
            'plugin_glpiinventory_collects_id'   => $collects_id,
            'hive'                                 => 'HKEY_LOCAL_MACHINE',
            'path'                                 => '/',
            'key'                                  => 'daKey',
        ];
        $pfCollect_Registry = new PluginGlpiinventoryCollect_Registry();
        $registry_id = $pfCollect_Registry->add($input);
        $this->assertNotFalse($registry_id);

        $input = [
            'computers_id'                                     => $computers_id,
            'plugin_glpiinventory_collects_registries_id'    => $registry_id,
            'key'                                              => 'test_key',
            'value'                                            => 'test_value',
        ];
        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $collectRegistryContentId = $pfCollect_Registry_Contents->add($input);
        $this->assertNotFalse($collectRegistryContentId);

        // check if registry contents does exist
        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $pfCollect_Registry_Contents->getFromDB($collectRegistryContentId);

        $this->assertEquals(6, count($pfCollect_Registry_Contents->fields));

        // delete computer and check if it has been put in trash
        $computer->delete(['id' => $computers_id]);
        $this->assertTrue($computer->getFromDB($computers_id));

        $pfCollect_Wmi_Contents = new PluginGlpiinventoryCollect_Wmi_Content();
        $pfCollect_Wmi_Contents->getFromDB($collectWmiContectId);
        $this->assertEquals(5, count($pfCollect_Wmi_Contents->fields));

        $pfCollect_Registry_Contents = new PluginGlpiinventoryCollect_Registry_Content();
        $pfCollect_Registry_Contents->getFromDB($collectRegistryContentId);
        $this->assertEquals(6, count($pfCollect_Registry_Contents->fields));

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

    public function testTaskWithDeletedActor(): void
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
            'is_active'   => 1,
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

        $input = [
            'plugin_glpiinventory_tasks_id' => $tasks_id,
            'entities_id' => 0,
            'name'    => 'collectjob',
            'method'  => 'collect',
            'targets' => exportArrayToDB([[PluginGlpiinventoryCollect::class => 0]]),
            'actors'  => exportArrayToDB([[Agent::class => 0]]),
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

    public function testRegistryModeNormalization(): void
    {
        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $collects_id = $this->createItem(PluginGlpiinventoryCollect::class, [
            'name' => 'registry modes', 'entities_id' => 0, 'type' => 'registry', 'is_active' => 1,
        ])->getID();

        $this->assertCount(4, PluginGlpiinventoryCollect_Registry::getModes());

        // Default mode: no "defined", depth forced to 0 (depth belongs to MODE_DEPTH now)
        $reg = $this->createItem(PluginGlpiinventoryCollect_Registry::class, [
            'name' => 'default', 'plugin_glpiinventory_collects_id' => $collects_id,
            'hive' => 'HKEY_LOCAL_MACHINE', 'path' => '/a/', 'key' => '*',
            'mode' => PluginGlpiinventoryCollect_Registry::MODE_DEFAULT, 'depth' => 3,
        ], ['depth']);
        $this->assertEquals(0, (int) $reg->fields['defined']);
        $this->assertEquals(0, (int) $reg->fields['depth']);

        // Key defined mode: "defined" forced to 1, depth 0
        $reg = $this->createItem(PluginGlpiinventoryCollect_Registry::class, [
            'name' => 'defined', 'plugin_glpiinventory_collects_id' => $collects_id,
            'hive' => 'HKEY_LOCAL_MACHINE', 'path' => '/a/', 'key' => 'k',
            'mode' => PluginGlpiinventoryCollect_Registry::MODE_KEY_DEFINED, 'depth' => 5,
        ], ['depth']);
        $this->assertEquals(1, (int) $reg->fields['defined']);
        $this->assertEquals(0, (int) $reg->fields['depth']);

        // Path exists mode: "defined" 0, depth 0
        $reg = $this->createItem(PluginGlpiinventoryCollect_Registry::class, [
            'name' => 'exists', 'plugin_glpiinventory_collects_id' => $collects_id,
            'hive' => 'HKEY_LOCAL_MACHINE', 'path' => '/a/', 'key' => 'k',
            'mode' => PluginGlpiinventoryCollect_Registry::MODE_PATH_EXISTS, 'depth' => 5,
        ], ['depth']);
        $this->assertEquals(0, (int) $reg->fields['defined']);
        $this->assertEquals(0, (int) $reg->fields['depth']);

        // Depth mode: "defined" 0, depth kept
        $reg = $this->createItem(PluginGlpiinventoryCollect_Registry::class, [
            'name' => 'depth', 'plugin_glpiinventory_collects_id' => $collects_id,
            'hive' => 'HKEY_LOCAL_MACHINE', 'path' => '/a/', 'key' => 'k',
            'mode' => PluginGlpiinventoryCollect_Registry::MODE_DEPTH, 'depth' => 2,
        ]);
        $this->assertEquals(0, (int) $reg->fields['defined']);
        $this->assertEquals(2, (int) $reg->fields['depth']);
    }

    public function testRegistryModeExistsProcessWithAgent(): void
    {
        $this->deleteAllTasks();
        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollect                  = new PluginGlpiinventoryCollect();
        $pfCollect_Registry_Content = new PluginGlpiinventoryCollect_Registry_Content();

        $collects_id = $this->createItem(PluginGlpiinventoryCollect::class, [
            'name' => 'registry existence', 'entities_id' => 0, 'type' => 'registry', 'is_active' => 1,
        ])->getID();
        $registry_id = $this->createItem(PluginGlpiinventoryCollect_Registry::class, [
            'name' => 'TeamViewer path',
            'plugin_glpiinventory_collects_id' => $collects_id,
            'hive' => 'HKEY_LOCAL_MACHINE',
            'path' => '/software/Wow6432Node/TeamViewer/',
            'key'  => 'Version',
            'mode' => PluginGlpiinventoryCollect_Registry::MODE_PATH_EXISTS,
        ])->getID();

        $computers_id = $this->createComputer('pc-exists');
        $jobstate     = $this->prepareCollectJob($collects_id, $computers_id);

        // getJobs: existence check, no key in the path, "exists" flag set
        $resultObject = $pfCollect->communication('getJobs', 'pc-exists', null);
        $this->assertCount(1, $resultObject->jobs);
        $job = $resultObject->jobs[0];
        $this->assertEquals('getFromRegistry', $job['function']);
        $this->assertEquals('HKEY_LOCAL_MACHINE/software/Wow6432Node/TeamViewer/', $job['path']);
        $this->assertSame(1, $job['exists']);
        $this->assertArrayNotHasKey('defined', $job);
        $this->assertArrayNotHasKey('depth', $job);

        // setAnswer: path present
        $_GET = [
            'action' => 'setAnswer', 'uuid' => $jobstate['uniqid'],
            '_sid' => $registry_id, '_cpt' => '1', '_exists' => '1',
        ];
        $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);

        $contents = $pfCollect_Registry_Content->find(['plugin_glpiinventory_collects_registries_id' => $registry_id]);
        $this->assertCount(1, $contents);
        $this->assertEquals('1', current($contents)['value']);

        // fallback: no _exists but the agent returned a value => present
        $_GET = [
            'action' => 'setAnswer', 'uuid' => $jobstate['uniqid'],
            '_sid' => $registry_id, '_cpt' => '1', 'Version' => '15.0',
        ];
        $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);
        $contents = $pfCollect_Registry_Content->find(['plugin_glpiinventory_collects_registries_id' => $registry_id]);
        $this->assertCount(1, $contents);
        $this->assertEquals('1', current($contents)['value']);
    }

    public function testRegistryModeDefinedProcessWithAgent(): void
    {
        $this->deleteAllTasks();
        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollect                  = new PluginGlpiinventoryCollect();
        $pfCollect_Registry_Content = new PluginGlpiinventoryCollect_Registry_Content();

        $collects_id = $this->createItem(PluginGlpiinventoryCollect::class, [
            'name' => 'registry defined', 'entities_id' => 0, 'type' => 'registry', 'is_active' => 1,
        ])->getID();
        $registry = $this->createItem(PluginGlpiinventoryCollect_Registry::class, [
            'name' => 'GLPI-Agent server',
            'plugin_glpiinventory_collects_id' => $collects_id,
            'hive' => 'HKEY_LOCAL_MACHINE',
            'path' => '/software/GLPI-Agent/',
            'key'  => 'server',
            'mode' => PluginGlpiinventoryCollect_Registry::MODE_KEY_DEFINED,
        ]);
        $registry_id = $registry->getID();

        // mode 2 forces the "defined" flag on the config
        $this->assertEquals(1, (int) $registry->fields['defined']);

        $computers_id = $this->createComputer('pc-defined');
        $jobstate     = $this->prepareCollectJob($collects_id, $computers_id);

        // getJobs: path includes the key, "defined" flag set
        $resultObject = $pfCollect->communication('getJobs', 'pc-defined', null);
        $job = $resultObject->jobs[0];
        $this->assertEquals('HKEY_LOCAL_MACHINE/software/GLPI-Agent/server', $job['path']);
        $this->assertSame(1, $job['defined']);
        $this->assertArrayNotHasKey('exists', $job);
        $this->assertArrayNotHasKey('depth', $job);

        // setAnswer: key defined
        $_GET = [
            'action' => 'setAnswer', 'uuid' => $jobstate['uniqid'],
            '_sid' => $registry_id, '_cpt' => '1', '_defined' => '1',
        ];
        $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);

        $contents = $pfCollect_Registry_Content->find(['plugin_glpiinventory_collects_registries_id' => $registry_id]);
        $this->assertCount(1, $contents);
        $content = current($contents);
        $this->assertEquals('server', $content['key']);
        $this->assertEquals('1', $content['value']);
    }

    public function testRegistryModeDepthProcessWithAgent(): void
    {
        $this->deleteAllTasks();
        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollect                  = new PluginGlpiinventoryCollect();
        $pfCollect_Registry_Content = new PluginGlpiinventoryCollect_Registry_Content();

        $collects_id = $this->createItem(PluginGlpiinventoryCollect::class, [
            'name' => 'registry recursion', 'entities_id' => 0, 'type' => 'registry', 'is_active' => 1,
        ])->getID();
        $registry_id = $this->createItem(PluginGlpiinventoryCollect_Registry::class, [
            'name' => 'GLPI-Agent tree',
            'plugin_glpiinventory_collects_id' => $collects_id,
            'hive' => 'HKEY_LOCAL_MACHINE',
            'path' => '/software/GLPI-Agent/',
            'key'  => '*',
            'mode' => PluginGlpiinventoryCollect_Registry::MODE_DEPTH,
            'depth' => 2,
        ])->getID();

        $computers_id = $this->createComputer('pc-depth');

        // a stale content row that must be cleaned when the job is dispatched
        $this->createItem(PluginGlpiinventoryCollect_Registry_Content::class, [
            'computers_id' => $computers_id,
            'plugin_glpiinventory_collects_registries_id' => $registry_id,
            'key' => 'old', 'value' => 'old', 'depth' => 0,
        ]);

        $jobstate = $this->prepareCollectJob($collects_id, $computers_id);

        // getJobs: recursion depth sent, path without key, stale content cleaned
        $resultObject = $pfCollect->communication('getJobs', 'pc-depth', null);
        $job = $resultObject->jobs[0];
        $this->assertEquals('HKEY_LOCAL_MACHINE/software/GLPI-Agent/', $job['path']);
        $this->assertSame(2, $job['depth']);
        $this->assertArrayNotHasKey('exists', $job);
        $this->assertArrayNotHasKey('defined', $job);
        $this->assertCount(0, $pfCollect_Registry_Content->find([
            'plugin_glpiinventory_collects_registries_id' => $registry_id,
            'computers_id' => $computers_id,
        ]));

        // answer 1: explicit depth
        $_GET = [
            'action' => 'setAnswer', 'uuid' => $jobstate['uniqid'], '_sid' => $registry_id,
            '_cpt' => '2', '_path' => 'httpd-port', '_value' => '65354', '_depth' => '0',
        ];
        $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);

        // answer 2: depth deduced from the relative path
        $_GET = [
            'action' => 'setAnswer', 'uuid' => $jobstate['uniqid'], '_sid' => $registry_id,
            '_cpt' => '1', '_path' => 'Subkey/debug', '_value' => '2',
        ];
        $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);

        // answer 3: SAME path as answer 1 => must upsert, not duplicate
        $_GET = [
            'action' => 'setAnswer', 'uuid' => $jobstate['uniqid'], '_sid' => $registry_id,
            '_cpt' => '1', '_path' => 'httpd-port', '_value' => '62354', '_depth' => '0',
        ];
        $pfCollect->communication('setAnswer', null, $jobstate['uniqid']);

        $contents = $pfCollect_Registry_Content->find(['plugin_glpiinventory_collects_registries_id' => $registry_id]);
        $this->assertCount(2, $contents);

        $byKey = [];
        foreach ($contents as $row) {
            $byKey[$row['key']] = $row;
        }
        $this->assertArrayHasKey('httpd-port', $byKey);
        $this->assertEquals('62354', $byKey['httpd-port']['value']); // updated by the upsert
        $this->assertEquals(0, (int) $byKey['httpd-port']['depth']);

        $this->assertArrayHasKey('Subkey/debug', $byKey);
        $this->assertEquals('2', $byKey['Subkey/debug']['value']);
        $this->assertEquals(1, (int) $byKey['Subkey/debug']['depth']);
    }
}
