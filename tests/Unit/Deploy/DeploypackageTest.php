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

class DeploypackageTest extends TestCase
{
    /** @var mixed */
    private $package_right;


    protected function setUp(): void
    {
        $this->package_right = $_SESSION['glpiactiveprofile'][PluginGlpiinventoryDeployPackage::$rightname];
    }


    protected function tearDown(): void
    {
        $_SESSION['glpiactiveprofile'][PluginGlpiinventoryDeployPackage::$rightname] = $this->package_right;
        Session::changeActiveEntities(0, true);
    }


    /**
     * @test
     */
    public function testGetTypeName()
    {
        $this->assertEquals('Package', PluginGlpiinventoryDeployPackage::getTypeName());
        $this->assertEquals('Package', PluginGlpiinventoryDeployPackage::getTypeName(1));
        $this->assertEquals('Package', PluginGlpiinventoryDeployPackage::getTypeName(3));
    }


    /**
     * @test
     */
    public function testIsDeployEnabled()
    {
        global $DB;

        $computer = new Computer();
        $agent  = new Agent();
        $module   = new PluginGlpiinventoryAgentmodule();
        $package  = new PluginGlpiinventoryDeployPackage();

        //Enable deploy feature for all agents
        $module->getFromDBByCrit(['modulename' => 'DEPLOY']);
        $module->update(['id' => $module->fields['id'], 'is_active' => 1]);

        // Create a computer
        $input = [
            'entities_id' => 0,
            'name'        => 'computer1',
        ];
        $computers_id = $computer->add($input);

        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $input = [
            'entities_id' => 0,
            'name'        => 'computer',
            'version'     => '{"INVENTORY":"v2.3.21"}',
            'deviceid'   => Computer::getType() . $computers_id,
            'useragent'   => 'FusionInventory-Agent_v2.3.21',
            'itemtype' => Computer::getType(),
            'items_id' => $computers_id,
            'agenttypes_id' => $agenttype['id'],
            'use_module_package_deployment' => 1,
        ];
        $this->assertNotFalse($agent->add($input));

        $this->assertTrue($package->isDeployEnabled($computers_id));

        //Disable deploy feature for all agents
        $module->update(['id' => $module->fields['id'], 'is_active' => 0]);

        $this->assertFalse($package->isDeployEnabled($computers_id));
    }


    public function testCanUpdateContentRequiresUpdateRight(): void
    {
        $package = $this->createPackage('package_content_right');

        $this->assertTrue($package->canUpdateContent());

        $_SESSION['glpiactiveprofile'][PluginGlpiinventoryDeployPackage::$rightname] = READ;
        $this->assertFalse($package->canUpdateContent());
    }


    public function testCanUpdateContentRejectsPackageOutsideActiveEntities(): void
    {
        $package = $this->createPackageInSubEntity('package_content_foreign');
        Session::changeActiveEntities(0, false);

        $this->assertTrue($package->getFromDB($package->getID()));
        $this->assertFalse($package->canUpdateContent());
    }


    public function testCanReadRequiresPackageRight(): void
    {
        $package = $this->createPackage('package_read_right');

        $this->assertTrue($package->can($package->getID(), READ));

        $_SESSION['glpiactiveprofile'][PluginGlpiinventoryDeployPackage::$rightname] = 0;
        $this->assertFalse($package->can($package->getID(), READ));
    }


    public function testCanReadRejectsPackageOutsideActiveEntities(): void
    {
        $package = $this->createPackageInSubEntity('package_foreign');
        Session::changeActiveEntities(0, false);

        $this->assertFalse($package->can($package->getID(), READ));
    }


    public function testCanReadRejectsUnknownPackage(): void
    {
        $this->assertFalse((new PluginGlpiinventoryDeployPackage())->can(999999, READ));
    }


    public function testUpdateRejectsJsonWhileTaskRunsPackage(): void
    {
        $package = $this->createPackage('package_running_task');
        $original_json = $package->fields['json'];
        $task = new PluginGlpiinventoryTask();
        $this->assertNotFalse($task->add([
            'name'        => 'task_running_package',
            'entities_id' => 0,
            'is_active'   => 1,
        ]));
        $taskjob = new PluginGlpiinventoryTaskjob();
        $this->assertNotFalse($taskjob->add([
            'name'                          => 'job_running_package',
            'plugin_glpiinventory_tasks_id' => $task->getID(),
            'entities_id'                   => 0,
            'method'                        => 'deployinstall',
            'targets'                       => '[{"PluginGlpiinventoryDeployPackage":"' . $package->getID() . '"}]',
        ]));

        $this->assertFalse($package->update([
            'id'   => $package->getID(),
            'json' => '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"whoami"}}],"userinteractions":[]},"associatedFiles":[]}',
        ]));
        $this->assertSessionError('Package content cannot be modified while a task is running with it');
        $this->assertTrue($package->getFromDB($package->getID()));
        $this->assertSame($original_json, $package->fields['json']);
    }


    public function testUpdateRejectsJsonReferencingInvalidFileHash(): void
    {
        $package = $this->createPackage('package_update_json_hash');

        $this->assertFalse($package->update([
            'id'   => $package->getID(),
            'json' => '{"jobs":{"checks":[],"associatedFiles":["../../../config/config_db.php"],"actions":[],"userinteractions":[]},"associatedFiles":{"../../../config/config_db.php":{"name":"x"}}}',
        ]));
        $this->assertSessionError('Invalid package content');
    }


    public function testUpdateAcceptsEscapedJsonWithValidFileHash(): void
    {
        $package = $this->createPackage('package_update_escaped_json');
        $hash = str_repeat('f', 128);
        $json = json_encode([
            'jobs'            => ['checks' => [], 'associatedFiles' => [$hash], 'actions' => [['cmd' => ['exec' => 'echo "ok"']]], 'userinteractions' => []],
            'associatedFiles' => [$hash => ['name' => 'file']],
        ]);

        $this->assertTrue($package->update(['id' => $package->getID(), 'json' => Toolbox::addslashes_deep($json)]));
        $this->assertTrue($package->getFromDB($package->getID()));
        $this->assertSame($json, $package->fields['json']);
    }


    public function testUpdateRejectsInvalidUuid(): void
    {
        $package = $this->createPackage('package_update_uuid');

        $this->assertFalse($package->update(['id' => $package->getID(), 'uuid' => '../../evil']));
        $this->assertSessionError('Invalid package uuid');
    }


    public function testRemoveFileInRepoIgnoresPathsOutsideRepository(): void
    {
        $victim = GLPI_TMP_DIR . '/glpiinventory_victim.txt';
        file_put_contents($victim, 'keep me');
        $manifest = str_repeat('c', 128);
        file_put_contents(PLUGIN_GLPI_INVENTORY_MANIFESTS_DIR . $manifest, $victim . "\n");

        $deploy_file = new PluginGlpiinventoryDeployFile();
        $this->assertFalse($deploy_file->removeFileInRepo('../../../_tmp/' . basename($victim)));
        $this->assertTrue($deploy_file->removeFileInRepo($manifest));

        $this->assertFileExists($victim);
        $this->assertFileDoesNotExist(PLUGIN_GLPI_INVENTORY_MANIFESTS_DIR . $manifest);
        unlink($victim);
    }


    public function testExportPackageSkipsInvalidHashes(): void
    {
        [$manifest, $part] = $this->createManifestWithTraversalLine();
        $package = $this->createPackageWithFiles('package_export_hashes', $manifest);
        $export_dir = GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/export/';
        if (!is_dir($export_dir)) {
            mkdir($export_dir, 0777, true);
        }
        $archive = $export_dir . $package->fields['uuid'] . '.packageexporthashes.zip';

        try {
            $package->exportPackage($package->getID());

            $zip = new ZipArchive();
            $this->assertTrue($zip->open($archive));
            $information = json_decode($zip->getFromName('information.json'), true);
            $entries = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entries[] = $zip->getNameIndex($i);
            }
            $zip->close();

            $this->assertSame([$manifest], $information['manifests']);
            $this->assertSame([(new PluginGlpiinventoryDeployFile())->getDirBySha512($part) . '/' . $part], $information['repository']);
            $this->assertEmpty(array_filter($entries, fn(string $entry): bool => str_contains($entry, '..')));
        } finally {
            $this->removeManifestWithTraversalLine($manifest, $part);
            if (file_exists($archive)) {
                unlink($archive);
            }
        }
    }


    public function testDeployOrderSkipsInvalidHashes(): void
    {
        [$manifest, $part] = $this->createManifestWithTraversalLine();
        $package = $this->createPackageWithFiles('package_order_hashes', $manifest);
        $taskjobstate = new PluginGlpiinventoryTaskjobstate();
        $taskjobstate->fields = [
            'date_start' => null,
            'items_id'   => $package->getID(),
            'itemtype'   => PluginGlpiinventoryDeployPackage::class,
            'uniqid'     => 'order_hashes',
            'agents_id'  => null,
        ];

        try {
            $order = (new PluginGlpiinventoryDeployCommon())->run($taskjobstate);

            $this->assertSame([$part], $order['associatedFiles'][$manifest]['multiparts']);
            $this->assertSame([], $order['associatedFiles']['../evil']['multiparts']);
        } finally {
            $this->removeManifestWithTraversalLine($manifest, $part);
        }
    }


    public function testUploadFileFromServerRejectsPathOutsideUploadDir(): void
    {
        $package = $this->createPackage('package_server_file');
        $outside_file = GLPI_TMP_DIR . '/glpiinventory_outside_upload.txt';
        file_put_contents($outside_file, 'secret');

        $deploy_file = new PluginGlpiinventoryDeployFile();
        $this->assertFalse($deploy_file->uploadFileFromServer([
            'id'       => $package->getID(),
            'filename' => $outside_file,
        ]));

        unlink($outside_file);
    }


    public function testImportPackageRejectsUnlistedArchive(): void
    {

        $this->expectException(RuntimeException::class);
        (new PluginGlpiinventoryDeployPackage())->importPackage('../../../../files/_tmp/unknown.zip');
    }


    public function testImportPackageRejectsInvalidManifestEntry(): void
    {
        $archive = $this->createImportArchive([
            'package'    => ['name' => 'evil', 'uuid' => 'evil.manifest', 'json' => '{"jobs":{}}'],
            'files'      => [],
            'manifests'  => ['../../../evil'],
            'repository' => [],
        ]);

        try {
            (new PluginGlpiinventoryDeployPackage())->importPackage(basename($archive));
            $this->fail('Archive with invalid manifest entry must be rejected');
        } catch (RuntimeException $e) {
            $this->assertDirectoryDoesNotExist($archive . '.extract');
            $this->assertCount(0, (new PluginGlpiinventoryDeployPackage())->find(['uuid' => 'evil.manifest']));
        } finally {
            unlink($archive);
        }
    }


    public function testImportPackageIgnoresEntityFromArchive(): void
    {
        $archive = $this->createImportArchive([
            'package'    => [
                'name'         => 'imported',
                'uuid'         => 'imported.entity',
                'json'         => '{"jobs":{"checks":[],"associatedFiles":[],"actions":[],"userinteractions":[]},"associatedFiles":[]}',
                'entities_id'  => 999,
                'is_recursive' => 1,
            ],
            'files'      => [],
            'manifests'  => [],
            'repository' => [],
        ]);

        $package = new PluginGlpiinventoryDeployPackage();
        $package->importPackage(basename($archive));
        unlink($archive);

        $this->assertTrue($package->getFromDBByCrit(['uuid' => 'imported.entity']));
        $this->assertEquals(Session::getActiveEntity(), $package->fields['entities_id']);
        $this->assertEquals(0, $package->fields['is_recursive']);
        $this->assertDirectoryDoesNotExist($archive . '.extract');
    }


    public function testImportPackageRejectsPartNotMatchingItsHash(): void
    {
        $part_path = 'a/aa/' . str_repeat('a', 128);
        $archive = $this->createImportArchive(
            [
                'package'    => ['name' => 'tampered', 'uuid' => 'tampered.part', 'json' => '{"jobs":{}}'],
                'files'      => [],
                'manifests'  => [],
                'repository' => [$part_path],
            ],
            ['files/repository/' . $part_path => 'not the expected content']
        );

        try {
            (new PluginGlpiinventoryDeployPackage())->importPackage(basename($archive));
            $this->fail('Archive with a part not matching its hash must be rejected');
        } catch (RuntimeException $e) {
            $this->assertFileDoesNotExist(PLUGIN_GLPI_INVENTORY_REPOSITORY_DIR . $part_path);
            $this->assertCount(0, (new PluginGlpiinventoryDeployPackage())->find(['uuid' => 'tampered.part']));
        } finally {
            unlink($archive);
        }
    }


    public function testImportPackageKeepsExistingManifest(): void
    {
        $manifest = str_repeat('b', 128);
        $existing_manifest = PLUGIN_GLPI_INVENTORY_MANIFESTS_DIR . $manifest;
        file_put_contents($existing_manifest, 'original');
        $archive = $this->createImportArchive(
            [
                'package'    => ['name' => 'overwrite', 'uuid' => 'overwrite.manifest', 'json' => '{"jobs":{}}'],
                'files'      => [],
                'manifests'  => [$manifest],
                'repository' => [],
            ],
            ['files/manifests/' . $manifest => 'replaced']
        );

        (new PluginGlpiinventoryDeployPackage())->importPackage(basename($archive));
        unlink($archive);

        $this->assertStringEqualsFile($existing_manifest, 'original');
        unlink($existing_manifest);
    }


    public function testMassiveActionsRejectUserWithReadRightOnly(): void
    {
        $package = $this->createPackage('package_massive_read_only');
        $_SESSION['glpiactiveprofile'][PluginGlpiinventoryDeployPackage::$rightname] = READ;

        foreach (['transfert', 'import', 'duplicate'] as $action) {
            $this->assertSame(
                [MassiveAction::ACTION_NORIGHT],
                $this->runMassiveAction($action, [$package->getID()], ['entities_id' => 0]),
                "Action '$action' must be refused"
            );
        }
        $this->assertCount(1, $package->find(['name' => 'package_massive_read_only']));
    }


    public function testMassiveTransferRejectsInaccessibleEntity(): void
    {
        $package = $this->createPackage('package_massive_transfer');

        $this->assertSame(
            [MassiveAction::ACTION_NORIGHT],
            $this->runMassiveAction('transfert', [$package->getID()], ['entities_id' => 999999])
        );
        $this->assertTrue($package->getFromDB($package->getID()));
        $this->assertEquals(0, $package->fields['entities_id']);
    }


    public function testMassiveImportContinuesAfterInvalidArchive(): void
    {
        $valid_archive = $this->createImportArchive([
            'package'    => ['name' => 'massive_valid', 'uuid' => 'massive.valid', 'json' => '{"jobs":{}}'],
            'files'      => [],
            'manifests'  => [],
            'repository' => [],
        ]);

        $this->assertSame(
            [MassiveAction::ACTION_KO, MassiveAction::ACTION_OK],
            $this->runMassiveAction('import', ['unknown.zip', basename($valid_archive)])
        );
        unlink($valid_archive);
        $this->assertCount(1, (new PluginGlpiinventoryDeployPackage())->find(['uuid' => 'massive.valid']));
    }


    public function testMassiveImportContinuesAfterArchiveWithoutInformation(): void
    {
        $archive_without_information = GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/import/no_information.test.zip';
        $zip = new ZipArchive();
        $zip->open($archive_without_information, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'no information.json');
        $zip->close();
        $valid_archive = $this->createImportArchive([
            'package'    => ['name' => 'massive_after_missing', 'uuid' => 'massive.after.missing', 'json' => '{"jobs":{}}'],
            'files'      => [],
            'manifests'  => [],
            'repository' => [],
        ]);

        try {
            $this->assertSame(
                [MassiveAction::ACTION_KO, MassiveAction::ACTION_OK],
                $this->runMassiveAction('import', [basename($archive_without_information), basename($valid_archive)])
            );
            $this->assertDirectoryDoesNotExist($archive_without_information . '.extract');
            $this->assertCount(1, (new PluginGlpiinventoryDeployPackage())->find(['uuid' => 'massive.after.missing']));
        } finally {
            unlink($archive_without_information);
            unlink($valid_archive);
        }
    }


    /**
     * @param array<int|string> $ids
     * @param array<string,mixed> $input
     * @return array<int>
     */
    private function runMassiveAction(string $action, array $ids, array $input = []): array
    {
        $results = [];
        $ma = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAction', 'addMessage', 'itemDone'])
            ->getMock();
        $ma->POST = $input;
        $ma->method('getAction')->willReturn($action);
        $ma->method('itemDone')->willReturnCallback(
            function (string $itemtype, $id, int $result) use (&$results): void {
                $results[] = $result;
            }
        );

        PluginGlpiinventoryDeployPackage::processMassiveActionsForOneItemtype(
            $ma,
            new PluginGlpiinventoryDeployPackage(),
            $ids
        );

        return $results;
    }


    private function assertSessionError(string $message): void
    {
        $this->assertContains($message, $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? []);
        unset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR]);
    }


    private function createPackageWithFiles(string $name, string $manifest): PluginGlpiinventoryDeployPackage
    {
        /** @var DBmysql $DB */
        global $DB;

        $package = $this->createPackage($name);
        $DB->update(PluginGlpiinventoryDeployPackage::getTable(), [
            'uuid' => Rule::getUuid(),
            'json' => $DB->escape(json_encode([
                'jobs'            => ['checks' => [], 'associatedFiles' => [$manifest], 'actions' => []],
                'associatedFiles' => [$manifest => ['name' => 'file'], '../evil' => ['name' => 'evil']],
            ])),
        ], ['id' => $package->getID()]);
        $this->assertTrue($package->getFromDB($package->getID()));

        return $package;
    }


    /**
     * @return array{string, string} manifest hash and its only valid part hash
     */
    private function createManifestWithTraversalLine(): array
    {
        $manifest = str_repeat('d', 128);
        $part = str_repeat('e', 128);
        $part_dir = PLUGIN_GLPI_INVENTORY_REPOSITORY_DIR . (new PluginGlpiinventoryDeployFile())->getDirBySha512($part);
        if (!is_dir($part_dir)) {
            mkdir($part_dir, 0777, true);
        }
        file_put_contents($part_dir . '/' . $part, 'part');
        file_put_contents(PLUGIN_GLPI_INVENTORY_MANIFESTS_DIR . $manifest, "../../../_tmp/evil\n" . $part . "\n");

        return [$manifest, $part];
    }


    private function removeManifestWithTraversalLine(string $manifest, string $part): void
    {
        unlink(PLUGIN_GLPI_INVENTORY_MANIFESTS_DIR . $manifest);
        unlink(PLUGIN_GLPI_INVENTORY_REPOSITORY_DIR . (new PluginGlpiinventoryDeployFile())->getDirBySha512($part) . '/' . $part);
    }


    private function createPackageInSubEntity(string $name): PluginGlpiinventoryDeployPackage
    {
        $entity = new Entity();
        $entities_id = $entity->add(['name' => $name . '_entity', 'entities_id' => 0]);
        $this->assertNotFalse($entities_id);
        $package = new PluginGlpiinventoryDeployPackage();
        $this->assertNotFalse($package->add(['name' => $name, 'entities_id' => $entities_id]));

        return $package;
    }


    private function createPackage(string $name): PluginGlpiinventoryDeployPackage
    {
        $package = new PluginGlpiinventoryDeployPackage();
        $this->assertNotFalse($package->add(['name' => $name, 'entities_id' => 0]));

        return $package;
    }


    /**
     * @param array<string,mixed> $information
     * @param array<string,string> $entries extra archive entries, keyed by path
     */
    private function createImportArchive(array $information, array $entries = []): string
    {
        $import_dir = GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/import/';
        if (!is_dir($import_dir)) {
            mkdir($import_dir, 0777, true);
        }

        $archive = $import_dir . $information['package']['uuid'] . '.test.zip';
        $zip = new ZipArchive();
        $zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('information.json', json_encode($information));
        foreach ($entries as $path => $content) {
            $zip->addFromString($path, $content);
        }
        $zip->close();

        return $archive;
    }
}
