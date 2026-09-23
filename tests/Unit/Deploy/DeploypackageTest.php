<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * @basedon   FusionInventory for GLPI
 * @copyright 2021-2026 Teclib' and contributors.
 * @copyright 2010-2021 by the FusionInventory Development Team.
 *
 * http://glpi-project.org
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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Tests\DbTestCase;

class DeploypackageTest extends DbTestCase
{
    public function testGetTypeName()
    {
        $this->assertEquals('Package', PluginGlpiinventoryDeployPackage::getTypeName());
        $this->assertEquals('Package', PluginGlpiinventoryDeployPackage::getTypeName(1));
        $this->assertEquals('Package', PluginGlpiinventoryDeployPackage::getTypeName(3));
    }


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

        $agenttype = $DB->request(['FROM' => AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $input = [
            'entities_id' => 0,
            'name'        => 'computer',
            'version'     => '{"INVENTORY":"v2.3.21"}',
            'deviceid'   => Computer::class . $computers_id,
            'useragent'   => 'FusionInventory-Agent_v2.3.21',
            'itemtype' => Computer::class,
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
        $this->login('glpi', 'glpi');
        $package = $this->createPackage('package_content_right');

        $this->assertTrue($package->canUpdateContent());

        $_SESSION['glpiactiveprofile'][PluginGlpiinventoryDeployPackage::$rightname] = READ;
        $this->assertFalse($package->canUpdateContent());
    }


    public function testUpdateRejectsJsonWhileTaskRunsPackage(): void
    {
        $this->login('glpi', 'glpi');
        $package = $this->createPackage('package_running_task');
        $original_json = $package->fields['json'];
        $task = $this->createItem(PluginGlpiinventoryTask::class, [
            'name'        => 'task_running_package',
            'entities_id' => 0,
            'is_active'   => 1,
        ]);
        $this->createItem(PluginGlpiinventoryTaskjob::class, [
            'name'                          => 'job_running_package',
            'plugin_glpiinventory_tasks_id' => $task->getID(),
            'entities_id'                   => 0,
            'method'                        => 'deployinstall',
            'targets'                       => '[{"PluginGlpiinventoryDeployPackage":"' . $package->getID() . '"}]',
        ], ['targets']);

        $this->assertFalse($package->update([
            'id'   => $package->getID(),
            'json' => '{"jobs":{"checks":[],"associatedFiles":[],"actions":[{"cmd":{"exec":"whoami"}}],"userinteractions":[]},"associatedFiles":[]}',
        ]));
        $this->hasSessionMessages(ERROR, ['Package content cannot be modified while a task is running with it']);
        $this->assertTrue($package->getFromDB($package->getID()));
        $this->assertSame($original_json, $package->fields['json']);
    }


    public function testAlterJsonRejectsUserWithoutUpdateRight(): void
    {
        $this->login('glpi', 'glpi');
        $package = $this->createPackage('package_alter_json_right');
        $_SESSION['glpiactiveprofile'][PluginGlpiinventoryDeployPackage::$rightname] = READ;

        $this->expectException(AccessDeniedHttpException::class);
        PluginGlpiinventoryDeployPackage::alterJSON('add_item', [
            'id'                => $package->getID(),
            'itemtype'          => PluginGlpiinventoryDeployAction::class,
            'deploy_actiontype' => 'cmd',
            'name'              => 'cmd',
            'exec'              => 'whoami',
        ]);
    }


    public function testUploadFileFromServerRejectsPathOutsideUploadDir(): void
    {
        $this->login('glpi', 'glpi');
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
        $this->login('glpi', 'glpi');

        $this->expectException(BadRequestHttpException::class);
        (new PluginGlpiinventoryDeployPackage())->importPackage('../../../../files/_tmp/unknown.zip');
    }


    public function testImportPackageRejectsInvalidManifestEntry(): void
    {
        $this->login('glpi', 'glpi');
        $archive = $this->createImportArchive([
            'package'    => ['name' => 'evil', 'uuid' => 'evil.manifest', 'json' => '{"jobs":{}}'],
            'files'      => [],
            'manifests'  => ['../../../evil'],
            'repository' => [],
        ]);

        try {
            (new PluginGlpiinventoryDeployPackage())->importPackage(basename($archive));
            $this->fail('Archive with invalid manifest entry must be rejected');
        } catch (BadRequestHttpException $e) {
            $this->assertDirectoryDoesNotExist($archive . '.extract');
            $this->assertCount(0, (new PluginGlpiinventoryDeployPackage())->find(['uuid' => 'evil.manifest']));
        } finally {
            unlink($archive);
        }
    }


    public function testImportPackageIgnoresEntityFromArchive(): void
    {
        $this->login('glpi', 'glpi');
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
        $this->login('glpi', 'glpi');
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
        } catch (BadRequestHttpException $e) {
            $this->assertFileDoesNotExist(PLUGIN_GLPI_INVENTORY_REPOSITORY_DIR . $part_path);
            $this->assertCount(0, (new PluginGlpiinventoryDeployPackage())->find(['uuid' => 'tampered.part']));
        } finally {
            unlink($archive);
        }
    }


    public function testImportPackageKeepsExistingManifest(): void
    {
        $this->login('glpi', 'glpi');
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


    public function testUpdateRejectsJsonReferencingInvalidFileHash(): void
    {
        $this->login('glpi', 'glpi');
        $package = $this->createPackage('package_update_json_hash');

        $this->assertFalse($package->update([
            'id'   => $package->getID(),
            'json' => '{"jobs":{"checks":[],"associatedFiles":["../../../config/config_db.php"],"actions":[],"userinteractions":[]},"associatedFiles":{"../../../config/config_db.php":{"name":"x"}}}',
        ]));
        $this->hasSessionMessages(ERROR, ['Invalid package content']);
    }


    public function testUpdateRejectsInvalidUuid(): void
    {
        $this->login('glpi', 'glpi');
        $package = $this->createPackage('package_update_uuid');

        $this->assertFalse($package->update(['id' => $package->getID(), 'uuid' => '../../evil']));
        $this->hasSessionMessages(ERROR, ['Invalid package uuid']);
    }


    public function testRemoveFileInRepoIgnoresPathsOutsideRepository(): void
    {
        $this->login('glpi', 'glpi');
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
        $this->login('glpi', 'glpi');
        [$manifest, $part] = $this->createManifestWithTraversalLine();
        $package = $this->createPackageWithFiles('package_export_hashes', $manifest);
        $export_dir = GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/export/';
        if (!is_dir($export_dir)) {
            mkdir($export_dir, 0o777, true);
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
        $this->login('glpi', 'glpi');
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


    public function testImportPackageRejectsInvalidFileHash(): void
    {
        $this->login('glpi', 'glpi');
        $archive = $this->createImportArchive([
            'package'    => ['name' => 'evil_file', 'uuid' => 'evil.file', 'json' => '{"jobs":{}}'],
            'files'      => [['name' => 'evil', 'sha512' => '../../evil', 'shortsha512' => '../../']],
            'manifests'  => [],
            'repository' => [],
        ]);

        try {
            $this->expectException(BadRequestHttpException::class);
            (new PluginGlpiinventoryDeployPackage())->importPackage(basename($archive));
        } finally {
            unlink($archive);
        }
    }


    public function testMassiveActionsRejectUserWithReadRightOnly(): void
    {
        $this->login('glpi', 'glpi');
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
        $this->login('glpi', 'glpi');
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
        $this->login('glpi', 'glpi');
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
        $this->login('glpi', 'glpi');
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


    private function createPackage(string $name): PluginGlpiinventoryDeployPackage
    {
        $package = new PluginGlpiinventoryDeployPackage();
        $this->assertNotFalse($package->add(['name' => $name, 'entities_id' => 0]));

        return $package;
    }


    private function createPackageWithFiles(string $name, string $manifest): PluginGlpiinventoryDeployPackage
    {
        /** @var DBmysql $DB */
        global $DB;

        $package = $this->createPackage($name);
        $DB->update(PluginGlpiinventoryDeployPackage::getTable(), [
            'uuid' => Rule::getUuid(),
            'json' => json_encode([
                'jobs'            => ['checks' => [], 'associatedFiles' => [$manifest], 'actions' => []],
                'associatedFiles' => [$manifest => ['name' => 'file'], '../evil' => ['name' => 'evil']],
            ]),
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
            mkdir($part_dir, 0o777, true);
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


    /**
     * @param array<string,mixed> $information
     * @param array<string,string> $entries extra archive entries, keyed by path
     */
    private function createImportArchive(array $information, array $entries = []): string
    {
        $import_dir = GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/import/';
        if (!is_dir($import_dir)) {
            mkdir($import_dir, 0o777, true);
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
