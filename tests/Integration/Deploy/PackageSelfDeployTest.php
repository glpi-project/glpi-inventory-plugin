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
use PHPUnit\Framework\Attributes\Depends;

class PackageSelfDeployTest extends DbTestCase
{
    private function prepare(): void
    {
        global $DB;

        $computer      = new Computer();
        $agent         = new Agent();
        $pfDeployGroup = new PluginGlpiinventoryDeployGroup();

        $users_id = getItemByTypeName(User::class, 'glpi', true);
        $computers_id = $computer->add([
            'name'        => 'pc01',
            'entities_id' => 0,
            'users_id'    => $users_id,
        ]);
        $this->assertNotFalse($computers_id);
        $agenttype = $DB->request(['FROM' => AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $this->assertNotFalse(
            $agent->add([
                'itemtype' => Computer::class,
                'items_id' => $computers_id,
                'entities_id' => 0,
                'agenttypes_id' => $agenttype['id'],
                'deviceid' => "Computer$computers_id",
                'use_module_package_deployment' => 1,
            ])
        );
        $this->assertNotFalse(
            $pfDeployGroup->add([
                'name' => 'all',
                'type' => 'DYNAMIC',
            ])
        );
    }

    public function testPackageNoTarget()
    {
        $this->login();
        $this->prepare();
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $input = [
            'name'        => 'test1',
            'entities_id' => 0,
            'plugin_glpiinventory_deploygroups_id' => 0,
        ];
        $packagesId = $pfDeployPackage->add($input);
        $this->assertNotFalse($packagesId);
        $packages = $pfDeployPackage->canUserDeploySelf();
        $this->assertFalse($packages, 'May have no packages');
    }


    public function testPackageTargetEntity()
    {
        $this->testPackageNoTarget();
        $pfDeployPackage        = new PluginGlpiinventoryDeployPackage();
        $pfDeployPackage_Entity = new PluginGlpiinventoryDeployPackage_Entity();
        $pfDeployGroup          = new PluginGlpiinventoryDeployGroup();

        $_SESSION['glpiactiveprofile']['plugin_glpiinventory_selfpackage'] = READ;

        $pfDeployGroup->getFromDBByCrit(['name' => 'all']);

        $pfDeployPackage->getFromDBByCrit(['name' => 'test1']);
        $pfDeployPackage->update([
            'id' => $pfDeployPackage->fields['id'],
            'plugin_glpiinventory_deploygroups_id' => $pfDeployGroup->fields['id'],
        ]);
        $this->assertArrayHasKey('id', $pfDeployPackage->fields);

        $pfDeployPackage_Entity->add([
            'plugin_glpiinventory_deploypackages_id' => $pfDeployPackage->fields['id'],
        ]);

        $packages = $pfDeployPackage->canUserDeploySelf();
        $reference = [$pfDeployPackage->fields['id'] => $pfDeployPackage->fields];
        $this->assertEquals($reference, $packages, 'May have 1 package');
    }


    public function testPackageTargetgroup()
    {
        $this->testPackageNoTarget();
        $pfDeployPackage       = new PluginGlpiinventoryDeployPackage();
        $pfDeployPackage_Group = new PluginGlpiinventoryDeployPackage_Group();
        $group                 = new Group();
        $pfDeployGroup         = new PluginGlpiinventoryDeployGroup();

        $_SESSION['glpiactiveprofile']['plugin_glpiinventory_selfpackage'] = READ;

        $pfDeployGroup->getFromDBByCrit(['name' => 'all']);

        $groupId = $group->add([
            'name' => 'self-deploy',
            'entities_id' => 0,
        ]);
        $this->assertNotFalse($groupId);

        $pfDeployPackage->getFromDBByCrit(['name' => 'test1']);
        $pfDeployPackage->update([
            'id' => $pfDeployPackage->fields['id'],
            'plugin_glpiinventory_deploygroups_id' => $pfDeployGroup->fields['id'],
        ]);

        $pfDeployPackage_Group->add([
            'plugin_glpiinventory_deploypackages_id' => $pfDeployPackage->fields['id'],
            'groups_id'   => $groupId,
            'entities_id' => 0,
        ]);
        $packages = $pfDeployPackage->canUserDeploySelf();
        $this->assertFalse($packages, 'May have no packages');

        $_SESSION['glpigroups'] = [0 => $groupId];

        $packages = $pfDeployPackage->canUserDeploySelf();
        $reference = [$pfDeployPackage->fields['id'] => $pfDeployPackage->fields];
        $this->assertEquals($reference, $packages, 'May have 1 package');
    }


    public function testPackageTargetUser()
    {
        $this->testPackageNoTarget();
        $pfDeployPackage      = new PluginGlpiinventoryDeployPackage();
        $pfDeployPackage_User = new PluginGlpiinventoryDeployPackage_User();
        $pfDeployGroup         = new PluginGlpiinventoryDeployGroup();

        $_SESSION['glpiactiveprofile']['plugin_glpiinventory_selfpackage'] = READ;

        $pfDeployGroup->getFromDBByCrit(['name' => 'all']);

        $pfDeployPackage->getFromDBByCrit(['name' => 'test1']);
        $pfDeployPackage->update([
            'id' => $pfDeployPackage->fields['id'],
            'plugin_glpiinventory_deploygroups_id' => $pfDeployGroup->fields['id'],
        ]);

        $pfDeployPackage_User->add([
            'plugin_glpiinventory_deploypackages_id' => $pfDeployPackage->fields['id'],
            'users_id' => $_SESSION['glpiID'] + 1,
        ]);
        $packages = $pfDeployPackage->canUserDeploySelf();
        $this->assertFalse($packages, 'May have no packages');

        $pfDeployPackage_User->add([
            'plugin_glpiinventory_deploypackages_id' => $pfDeployPackage->fields['id'],
            'users_id' => $_SESSION['glpiID'],
        ]);

        $packages = $pfDeployPackage->canUserDeploySelf();
        $reference = [$pfDeployPackage->fields['id'] => $pfDeployPackage->fields];
        $this->assertEquals($reference, $packages, 'May have 1 package');
    }


    public function testPackageTargetProfile()
    {
        $this->testPackageNoTarget();
        $pfDeployPackage         = new PluginGlpiinventoryDeployPackage();
        $pfDeployPackage_Profile = new PluginGlpiinventoryDeployPackage_Profile();
        $pfDeployGroup         = new PluginGlpiinventoryDeployGroup();

        $pfDeployGroup->getFromDBByCrit(['name' => 'all']);

        $pfDeployPackage->getFromDBByCrit(['name' => 'test1']);
        $pfDeployPackage->update([
            'id' => $pfDeployPackage->fields['id'],
            'plugin_glpiinventory_deploygroups_id' => $pfDeployGroup->fields['id'],
        ]);

        $pfDeployPackage_Profile->add([
            'plugin_glpiinventory_deploypackages_id' => $pfDeployPackage->fields['id'],
            'profiles_id' => $_SESSION['glpiactiveprofile']['id'] - 1,
        ]);
        $packages = $pfDeployPackage->canUserDeploySelf();
        $this->assertFalse($packages, 'May have no packages');

        $pfDeployPackage_Profile->add([
            'plugin_glpiinventory_deploypackages_id' => $pfDeployPackage->fields['id'],
            'profiles_id' => $_SESSION['glpiactiveprofile']['id'],
        ]);

        $packages = $pfDeployPackage->canUserDeploySelf();
        $reference = [
            $pfDeployPackage->fields['id'] => $pfDeployPackage->fields,
        ];
        $this->assertEquals($reference, $packages, 'May have 1 package');
    }

    public function testReportMyPackage()
    {
        global $DB;
        $this->testPackageNoTarget();
        $this->logOut();
        // Enable deploy feature for all agents
        $module = new PluginGlpiinventoryAgentmodule();
        $this->assertTrue($module->getFromDBByCrit(['modulename' => 'DEPLOY']));
        $this->assertTrue(
            $module->update([
                'id'        => $module->fields['id'],
                'is_active' => 1,
            ])
        );

        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $computer        = new Computer();
        $agent           = new Agent();
        $pfDeployPackage_Entity = new PluginGlpiinventoryDeployPackage_Entity();
        $pfDeployGroup         = new PluginGlpiinventoryDeployGroup();

        $this->assertTrue($pfDeployGroup->getFromDBByCrit(['name' => 'all']));

        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc01']));
        $computerId1 = $computer->fields['id'];

        $computerId2 = $computer->add([
            'name'        => 'pc02',
            'entities_id' => 1,
            'users_id'    => getItemByTypeName(User::class, 'glpi', true),
        ]);
        $this->assertNotFalse($computerId2);

        $agenttype = $DB->request(['FROM' => AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agentId = $agent->add([
            'itemtype' => Computer::class,
            'items_id' => $computerId2,
            'entities_id' => 0,
            'agenttypes_id' => $agenttype['id'],
            'deviceid' => "Computer$computerId2",
            'use_module_package_deployment' => 1,
        ]);
        $this->assertNotFalse($agentId);

        $this->assertTrue($pfDeployPackage->getFromDBByCrit(['name' => 'test1']));
        $this->assertTrue(
            $pfDeployPackage->update([
                'id' => $pfDeployPackage->fields['id'],
                'plugin_glpiinventory_deploygroups_id' => $pfDeployGroup->fields['id'],
            ])
        );
        $packages_id_1 = $pfDeployPackage->fields['id'];
        $packageEntityId = $pfDeployPackage_Entity->add([
            'plugin_glpiinventory_deploypackages_id' => $packages_id_1,
            'entities_id' => 0,
        ]);
        $this->assertNotFalse($packageEntityId);

        // The second package, test2, is not in the same entity, and is not recursive
        // It should not be visible when requesting the list of packages the user
        // can deploy
        $input = [
            'name'        => 'test2',
            'entities_id' => 1,
            'plugin_glpiinventory_deploygroups_id' => $pfDeployGroup->fields['id'],
        ];
        $packages_id_2 = $pfDeployPackage->add($input);
        $this->assertNotFalse($packages_id_2);
        $this->assertNotFalse(
            $pfDeployPackage_Entity->add([
                'plugin_glpiinventory_deploypackages_id' => $packages_id_2,
                'entities_id' => 1,
            ])
        );

        // Create task
        $pfDeployPackage->deployToComputer($computerId1, $packages_id_1, $_SESSION['glpiID']);
        $pfDeployPackage->deployToComputer($computerId2, $packages_id_1, $_SESSION['glpiID']);
        //$_SESSION['glpiID'] = $userId;
        // Prepare task
        PluginGlpiinventoryTask::cronTaskscheduler();
        /*$task    = new PluginGlpiinventoryTask();
        $methods = [];
        foreach (PluginGlpiinventoryStaticmisc::getmethods() as $method) {
            $methods[] = $method['method'];
        }
        $task->prepareTaskjobs($methods);*/

        $reference = [
            'agents_prepared',
        ];

        $_SERVER['REQUEST_URI'] = 'front/deploypackage.php'; // URL is used to fix addDefaultWhere
        $packages = $pfDeployPackage->getPackageForMe($_SESSION['glpiID']);
        $packages_deploy = [];
        foreach ($packages as $data) {
            foreach ($data as $package_info) {
                if (isset($package_info['taskjobs_id'])) {
                    $packages_deploy[] = $package_info['last_taskjobstate']['state'];
                }
            }
        }
        $this->assertEquals($reference, $packages_deploy);

        $_SERVER['REQUEST_URI'] = 'front/deploypackage.public.php'; // URL is used to fix addDefaultWhere
        $packages = $pfDeployPackage->getPackageForMe($_SESSION['glpiID']);
        $packages_deploy = [];
        foreach ($packages as $data) {
            foreach ($data as $package_info) {
                if (isset($package_info['taskjobs_id'])) {
                    $packages_deploy[] = $package_info['last_taskjobstate']['state'];
                }
            }
        }
        $this->assertEquals($reference, $packages_deploy);
    }

    public function testReportComputerPackages()
    {
        global $DB;
        $this->testPackageNoTarget();
        $pfDeployPackage        = new PluginGlpiinventoryDeployPackage();
        $computer               = new Computer();
        $agent                  = new Agent();
        $pfDeployPackage_Entity = new PluginGlpiinventoryDeployPackage_Entity();
        $pfDeployGroup         = new PluginGlpiinventoryDeployGroup();

        $pfDeployGroup->getFromDBByCrit(['name' => 'all']);

        $computer->getFromDBByCrit(['name' => 'pc01']);
        $computerId1 = $computer->fields['id'];

        $computerId3 = $computer->add([
            'name' => 'pc03',
            'entities_id' => 0,
        ]);
        $this->assertNotFalse($computerId3);
        $agenttype = $DB->request(['FROM' => AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agent->add([
            'itemtype' => Computer::class,
            'items_id' => $computerId3,
            'entities_id' => 0,
            'agenttypes_id' => $agenttype['id'],
            'deviceid' => "Computer$computerId3",
            'use_module_package_deployment' => 1,
        ]);

        $pfDeployPackage->getFromDBByCrit(['name' => 'test1']);
        $pfDeployPackage->update([
            'id'                                     => $pfDeployPackage->fields['id'],
            'entities_id'                            => 0,
            'plugin_glpiinventory_deploygroups_id' => $pfDeployGroup->fields['id'],
        ]);
        $packages_id = $pfDeployPackage->fields['id'];
        $pfDeployPackage_Entity->add([
            'plugin_glpiinventory_deploypackages_id' => $packages_id,
        ]);

        $pfDeployPackage->getFromDBByCrit(['name' => 'test2']);
        $pfDeployPackage->update([
            'id'                                     => $pfDeployPackage->fields['id'],
            'entities_id'                            => 0,
            'plugin_glpiinventory_deploygroups_id' => $pfDeployGroup->fields['id'],
        ]);

        $pfDeployPackage_Entity->add([
            'plugin_glpiinventory_deploypackages_id' => $pfDeployPackage->fields['id'],
        ]);

        $input = [
            'name'                                   => 'test3',
            'entities_id'                            => 0,
            'plugin_glpiinventory_deploygroups_id' => 0,
        ];
        $packages_id = $pfDeployPackage->add($input);
        $this->assertNotFalse($packages_id);

        $packages = $pfDeployPackage->getPackageForMe(false, $computerId1);
        $names    = [];

        foreach ($packages as $data) {
            foreach ($data as $package_info) {
                $names[] = $package_info['name'];
            }
        }

        $expected = ['test1', 'test2'];
        $this->assertEquals($expected, $names);
    }

    public function testReportComputerPackagesDeployDisabled()
    {
        $this->testReportComputerPackages();
        // Disable deploy feature for all agents
        $module = new PluginGlpiinventoryAgentmodule();
        $module->getFromDBByCrit(['modulename' => 'DEPLOY']);
        $module->update([
            'id'        => $module->fields['id'],
            'is_active' => 0,
        ]);

        $pfDeployPackage        = new PluginGlpiinventoryDeployPackage();
        $computer               = new Computer();
        $pfDeployPackage_Entity = new PluginGlpiinventoryDeployPackage_Entity();
        $pfDeployGroup         = new PluginGlpiinventoryDeployGroup();

        $pfDeployGroup->getFromDBByCrit(['name' => 'all']);

        $pfDeployPackage->getFromDBByCrit(['name' => 'test1']);
        $pfDeployPackage_Entity->add([
            'plugin_glpiinventory_deploypackages_id' => $pfDeployPackage->fields['id'],
        ]);

        $pfDeployPackage->getFromDBByCrit(['name' => 'test2']);
        $pfDeployPackage_Entity->add([
            'plugin_glpiinventory_deploypackages_id' => $pfDeployPackage->fields['id'],
        ]);

        $computer->getFromDBByCrit(['name' => 'pc03']);

        $packages = $pfDeployPackage->getPackageForMe(false, $computer->fields['id']);
        $names    = [];

        foreach ($packages as $data) {
            foreach ($data as $package_info) {
                $names[] = $package_info['name'];
            }
        }

        $expected = [];
        $this->assertEquals($names, $expected);
    }
}
