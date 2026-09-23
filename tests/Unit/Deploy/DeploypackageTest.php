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
