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

class DeployactionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {

       // Delete all tasks
        $pfTask = new PluginGlpiinventoryTask();
        $items = $pfTask->find();
        foreach ($items as $item) {
            $pfTask->delete(['id' => $item['id']], true);
        }
    }




   /**
    * @test
    */
    public function testGetReturnActionNames()
    {
        $action = new PluginGlpiinventoryDeployAction();
        $this->assertEquals(5, count($action->getReturnActionNames()));
    }


   /**
    * @test
    */
    public function getGetTypes()
    {
        $action = new PluginGlpiinventoryDeployAction();
        $this->assertEquals(5, count($action->getTypes()));
    }


   /**
    * @test
    */
    public function testGetLabelForAType()
    {
        $action = new PluginGlpiinventoryDeployAction();
        $this->assertEquals(
            __('Command', 'glpiinventory'),
            $action->getLabelForAType('cmd')
        );
        $this->assertEquals(
            __('Move', 'glpiinventory'),
            $action->getLabelForAType('move')
        );
        $this->assertEquals(
            __('Copy', 'glpiinventory'),
            $action->getLabelForAType('copy')
        );
        $this->assertEquals(
            __('Delete directory', 'glpiinventory'),
            $action->getLabelForAType('delete')
        );
        $this->assertEquals(
            __('Create directory', 'glpiinventory'),
            $action->getLabelForAType('mkdir')
        );
        $this->assertEquals(
            'foo',
            $action->getLabelForAType('foo')
        );
    }


   /**
   * @test
   */
    public function testAdd_item()
    {
        $_SESSION['glpiactiveentities_string'] = 0;

        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $input = ['name'        => 'test1',
                'entities_id' => 0];
        $packages_id = $pfDeployPackage->add($input);

        $action = new PluginGlpiinventoryDeployAction();
        $params = ['id'                => $packages_id,
                 'actionstype'       => 'cmd',
                 'name'              => 'Command ls',
                 'exec'              => 'ls -lah',
                 'logLineLimit'      => '100',
                 'retChecks'         => ['type' => 'okCode', 'values' => [127]],
                ];
        $action->add_item($params);
        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"ls -lah","name":"Command ls","logLineLimit":"100"}}],"userinteractions":[]},"associatedFiles":[]}';
        $json     = Toolbox::stripslashes_deep($action->getJson($packages_id));
        $this->assertEquals($expected, $json);

        $params = ['id'                => $packages_id,
                 'actionstype'       => 'move',
                 'name'              => 'Move to /tmp',
                 'from'              => '*',
                 'to'                => '/tmp/'
                ];
        $action->add_item($params);

        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"ls -lah","name":"Command ls","logLineLimit":"100"}},{"move":{"from":"*","to":"/tmp/","name":"Move to /tmp"}}],"userinteractions":[]},"associatedFiles":[]}';
        $json     = Toolbox::stripslashes_deep($action->getJson($packages_id));
        $this->assertEquals($expected, $json);

        $params = ['id'                => $packages_id,
                 'actionstype'       => 'copy',
                 'name'              => 'Copy to /tmp',
                 'from'              => '*',
                 'to'                => '/tmp/'
                ];
        $action->add_item($params);

        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"ls -lah","name":"Command ls","logLineLimit":"100"}},{"move":{"from":"*","to":"/tmp/","name":"Move to /tmp"}},{"copy":{"from":"*","to":"/tmp/","name":"Copy to /tmp"}}],"userinteractions":[]},"associatedFiles":[]}';
        $json     = Toolbox::stripslashes_deep($action->getJson($packages_id));
        $this->assertEquals($expected, $json);

        $params = ['id'                => $packages_id,
                 'actionstype'       => 'mkdir',
                 'name'              => 'Create directory /tmp/foo',
                 'to'                => '/tmp/foo'
                ];
        $action->add_item($params);

        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"ls -lah","name":"Command ls","logLineLimit":"100"}},{"move":{"from":"*","to":"/tmp/","name":"Move to /tmp"}},{"copy":{"from":"*","to":"/tmp/","name":"Copy to /tmp"}},{"mkdir":{"to":"/tmp/foo","name":"Create directory /tmp/foo"}}],"userinteractions":[]},"associatedFiles":[]}';
        $json     = Toolbox::stripslashes_deep($action->getJson($packages_id));
        $this->assertEquals($expected, $json);
        $params = ['id'                => $packages_id,
                 'actionstype'       => 'delete',
                 'name'              => 'Delete directory /tmp/foo',
                 'to'                => '/tmp/foo'
                ];
        $action->add_item($params);

        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"ls -lah","name":"Command ls","logLineLimit":"100"}},{"move":{"from":"*","to":"/tmp/","name":"Move to /tmp"}},{"copy":{"from":"*","to":"/tmp/","name":"Copy to /tmp"}},{"mkdir":{"to":"/tmp/foo","name":"Create directory /tmp/foo"}},{"delete":{"to":"/tmp/foo","name":"Delete directory /tmp/foo"}}],"userinteractions":[]},"associatedFiles":[]}';
        $json     = Toolbox::stripslashes_deep($action->getJson($packages_id));
        $this->assertEquals($expected, $json);
    }


   /**
   * @test
   */
    public function testSave_item()
    {
        $json = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"ls -lah","name":"Command ls","logLineLimit":"100"}},{"move":{"from":"*","to":"/tmp/","name":"Move to /tmp"}},{"copy":{"from":"*","to":"/tmp/","name":"Copy to /tmp"}},{"mkdir":{"to":"/tmp/foo","name":"Create directory /tmp/foo"}},{"delete":{"to":"/tmp/foo","name":"Delete directory /tmp/foo"}}]},"associatedFiles":[]}';

        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $input = ['name'        => 'test1',
                'entities_id' => 0,
                'json'        => $json];
        $packages_id = $pfDeployPackage->add($input);

        $action = new PluginGlpiinventoryDeployAction();
        $params = ['id'                => $packages_id,
                 'index'             => 0,
                 'actionstype'       => 'cmd',
                 'name'              => 'Command ls -la \'s',
                 'exec'              => 'ls -la',
                 'logLineLimit'      => '100',
                 'retChecks'         => ['type' => 'okCode', 'values' => [127]],
                ];
        $action->save_item($params);
        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"ls -la","name":"Command ls -la \'s","logLineLimit":"100"}},{"move":{"from":"*","to":"/tmp/","name":"Move to /tmp"}},{"copy":{"from":"*","to":"/tmp/","name":"Copy to /tmp"}},{"mkdir":{"to":"/tmp/foo","name":"Create directory /tmp/foo"}},{"delete":{"to":"/tmp/foo","name":"Delete directory /tmp/foo"}}]},"associatedFiles":[]}';
        $json     = Toolbox::stripslashes_deep($action->getJson($packages_id));
        $this->assertEquals($expected, $json);
    }


   /**
   * @test
   */
    public function testRemove_item()
    {
        $json = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"ls -lah","name":"Command ls","logLineLimit":"100"}},{"move":{"from":"*","to":"/tmp/","name":"Move to /tmp"}},{"copy":{"from":"*","to":"/tmp/","name":"Copy to /tmp"}},{"mkdir":{"to":"/tmp/foo","name":"Create directory /tmp/foo"}},{"delete":{"to":"/tmp/foo","name":"Delete directory /tmp/foo"}}]},"associatedFiles":[]}';

        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $input = ['name'        => 'test1',
                'entities_id' => 0,
                'json'        => $json
               ];
        $packages_id = $pfDeployPackage->add($input);

        $action = new PluginGlpiinventoryDeployAction();
        $action->remove_item(['packages_id'    => $packages_id,
                            'action_entries' => [0 => 'on']]);
        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"ls -lah","name":"Command ls","logLineLimit":"100"}},{"move":{"from":"*","to":"/tmp/","name":"Move to /tmp"}},{"copy":{"from":"*","to":"/tmp/","name":"Copy to /tmp"}},{"mkdir":{"to":"/tmp/foo","name":"Create directory /tmp/foo"}},{"delete":{"to":"/tmp/foo","name":"Delete directory /tmp/foo"}}]},"associatedFiles":[]}';
        $json     = $action->getJson($packages_id);
        $this->assertEquals($expected, $json);

        $action->remove_item(['packages_id'    => $packages_id,
                            'action_entries' => [0 => 'on', 1 => 'on']]);
        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"ls -lah","name":"Command ls","logLineLimit":"100"}},{"move":{"from":"*","to":"/tmp/","name":"Move to /tmp"}},{"copy":{"from":"*","to":"/tmp/","name":"Copy to /tmp"}},{"mkdir":{"to":"/tmp/foo","name":"Create directory /tmp/foo"}},{"delete":{"to":"/tmp/foo","name":"Delete directory /tmp/foo"}}]},"associatedFiles":[]}';
        $json     = $action->getJson($packages_id);
        $this->assertEquals($expected, $json);
    }


   /**
   * @test
   */
    public function testMove_item()
    {
        $json = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"ls -lah","name":"Command ls","logLineLimit":"100"}},{"move":{"from":"*","to":"/tmp/","name":"Move to /tmp"}},{"copy":{"from":"*","to":"/tmp/","name":"Copy to /tmp"}},{"mkdir":{"to":"/tmp/foo","name":"Create directory /tmp/foo"}},{"delete":{"to":"/tmp/foo","name":"Delete directory /tmp/foo"}}]},"associatedFiles":[]}';

        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $input = ['name'        => 'test1',
                'entities_id' => 0,
                'json'        => $json
               ];
        $packages_id = $pfDeployPackage->add($input);
        $action      = new PluginGlpiinventoryDeployAction();

        $action->move_item(['id'        => $packages_id,
                          'old_index' => 0,
                          'new_index' => 1]);
        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"move":{"from":"*","to":"/tmp/","name":"Move to /tmp"}},{"cmd":{"exec":"ls -lah","name":"Command ls","logLineLimit":"100"}},{"copy":{"from":"*","to":"/tmp/","name":"Copy to /tmp"}},{"mkdir":{"to":"/tmp/foo","name":"Create directory /tmp/foo"}},{"delete":{"to":"/tmp/foo","name":"Delete directory /tmp/foo"}}]},"associatedFiles":[]}';
        $json     = $action->getJson($packages_id);
        $this->assertEquals($expected, $json);
    }


   /**
    * @test
    */
    public function testRunCommand()
    {
        global $DB;

        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $pfDeployCommon = new PluginGlpiinventoryDeployCommon();
        $pfTask = new PluginGlpiinventoryTask();
        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $computer = new Computer();
        $agent = new Agent();
        $entity = new Entity();
        $action = new PluginGlpiinventoryDeployAction();

        $this->assertTrue($entity->getFromDBByCrit(['id' => 0]));
        $input = [
         'id'             => $entity->fields['id'],
         'agent_base_url' => 'http://127.0.0.1/glpi'
        ];
        $this->assertTrue($entity->update($input));

        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $json = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"ls","name":"echo","logLineLimit":"10"}}],"userinteractions":[]},"associatedFiles":[]}';
        $input = [
         'name'        => 'cmd test',
         'entities_id' => 0,
         'json'        => $json
        ];
        $packages_id = $pfDeployPackage->add($input);
        $this->assertNotFalse($packages_id);

        $params = [
         'id'           => $packages_id,
         'index'        => 0,
         'actionstype'  => 'exec',
         'name'         => 'echo',
         'exec'         => 'echo "test de l\'echo" >> /tmp/echo',
         'logLineLimit' => '10',
         'retChecks'    => ['type' => 'okCode', 'values' => [0]],
        ];
        $action->save_item($params);

       // create task
        $input = [
         'entities_id' => 0,
         'name'        => 'deploy',
         'is_active'   => 1
        ];
        $tasks_id = $pfTask->add($input);
        $this->assertNotFalse($tasks_id);

       // Create computers + agents
        $input = [
         'entities_id' => 0,
         'name'        => 'computer1'
        ];
        $computers_id = $computer->add($input);
        $this->assertNotFalse($computers_id);

        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $input = [
         'entities_id' => 0,
         'name'        => 'portdavid',
         'version'     => '{"INVENTORY":"v2.4"}',
         'deviceid'    => 'portdavid',
         'useragent'   => 'FusionInventory-Agent_v2.4',
         'items_id'    => $computers_id,
         'itemtype'    => 'Computer',
         'agenttypes_id' => $agenttype['id']
        ];
        $agents_id = $agent->add($input);
        $this->assertNotFalse($agents_id);

       // create takjob
        $input = [
         'plugin_glpiinventory_tasks_id' => $tasks_id,
         'entities_id'                     => 0,
         'name'                            => 'deploy',
         'method'                          => 'deployinstall',
         'targets'                         => '[{"PluginGlpiinventoryDeployPackage":"' . $packages_id . '"}]',
         'actors'                          => '[{"Computer":"' . $computers_id . '"}]'
        ];
        $taskjobId = $pfTaskjob->add($input);
        $this->assertNotFalse($taskjobId);

       // prepare task
        PluginGlpiinventoryTask::cronTaskscheduler();

        $taskjobstates = $pfTask->getTaskjobstatesForAgent(
            $agents_id,
            ['deployinstall']
        );
        $this->assertEquals(1, count($taskjobstates));

        foreach ($taskjobstates as $taskjobstate) {
            $data = $pfDeployCommon->run($taskjobstate);

            $this->assertStringNotContainsString($data['job']['actions'][0]['exec']['exec'], "&gt;", "&gt; found instead >");
            $this->assertStringContainsString(">>", $data['job']['actions'][0]['exec']['exec'], "We may have >>");
        }
    }
}
