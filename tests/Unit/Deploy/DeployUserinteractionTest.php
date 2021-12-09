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

class DeployUserinteractionTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {

       // Delete all packages
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $items = $pfDeployPackage->find();
        foreach ($items as $item) {
            $pfDeployPackage->delete(['id' => $item['id']], true);
        }
    }



   /**
    * @test
    */
    public function testGetTypeName()
    {
        $this->assertEquals(
            'User interactions',
            PluginGlpiinventoryDeployUserinteraction::getTypeName()
        );
        $this->assertEquals(
            'User interaction',
            PluginGlpiinventoryDeployUserinteraction::getTypeName(1)
        );
        $this->assertEquals(
            'User interactions',
            PluginGlpiinventoryDeployUserinteraction::getTypeName(2)
        );
    }


   /**
    * @test
    */
    public function testGetTypes()
    {
        $interaction = new PluginGlpiinventoryDeployUserinteraction();
        $events      = $interaction->getTypes();
        $this->assertEquals(5, count($events));
    }


   /**
    * @test
    */
    public function testGetLabelForAType()
    {
        $interaction = new PluginGlpiinventoryDeployUserinteraction();
        $this->assertEquals(
            "Before download",
            $interaction->getLabelForAType('before')
        );
        $this->assertEquals(
            "After download",
            $interaction->getLabelForAType('after_download')
        );
        $this->assertEquals(
            "After actions",
            $interaction->getLabelForAType('after')
        );
        $this->assertEquals(
            "On download failure",
            $interaction->getLabelForAType('after_download_failure')
        );
        $this->assertEquals(
            "On actions failure",
            $interaction->getLabelForAType('after_failure')
        );
    }


   /**
    * @test
    */
    public function testGetValues()
    {
        $interaction = new PluginGlpiinventoryDeployUserinteraction();
        $data        = ['name' => 'foo', 'title' => 'title', 'text' => 'text', 'template' => 1];
        $values      = $interaction->getValues([], $data, 'edit');
        $expected    = ['name_value'        => 'foo',
                      'name_label'        => 'Interaction label',
                      'name_type'         => 'input',
                      'title_label'       => 'Title&nbsp;<span class=\'red\'>*</span>',
                      'title_value'       => 'title',
                      'title_type'        => 'input',
                      'description_label' => 'Message',
                      'description_type'  => 'text',
                      'description_value' => 'text',
                      'template_label'    => 'User interaction template&nbsp;<span class=\'red\'>*</span>',
                      'template_value'    => 1,
                      'template_type'     => 'dropdown'
                  ];
        $this->assertEquals($expected, $values);

        $values      = $interaction->getValues([], $data, 'create');
        $expected    = ['name_value'        => '',
                      'name_label'        => 'Interaction label',
                      'name_type'         => 'input',
                      'title_label'       => 'Title&nbsp;<span class=\'red\'>*</span>',
                      'title_value'       => '',
                      'title_type'        => 'input',
                      'description_label' => 'Message',
                      'description_type'  => 'text',
                      'description_value' => '',
                      'template_label'    => 'User interaction template&nbsp;<span class=\'red\'>*</span>',
                      'template_value'    => '',
                      'template_type'     => 'dropdown'
        ];
        $this->assertEquals($expected, $values);
    }


   /**
    * @test
    */
    public function testGetInteractionDescription()
    {
        $template = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $tmp['name']         = 'My Template';
        $tmp['json']         = '{"platform":"wts","timeout":4,"buttons":"ok","retry_after":4,"nb_max_retry":4,"on_timeout":"continue","on_nouser":"continue","on_multiusers":"cancel"}';
        $tmp['entities_id']  = 0;
        $tmp['is_recursive'] = 0;
        $templates_id = $template->add($tmp);

        $tmp = [];

        $interaction     = new PluginGlpiinventoryDeployUserinteraction();
        $tmp['name']     = 'inter';
        $tmp['type']     = 'after';
        $tmp['template'] = $templates_id;

        $expected = 'inter - After actions (My Template)';
        $this->assertEquals($expected, $interaction->getInteractionDescription($tmp));

        $tmp['type'] = 'after_download';
        $expected    = 'inter - After download (My Template)';
        $this->assertEquals($expected, $interaction->getInteractionDescription($tmp));

        $tmp['label'] = 'Interaction download failure';
        $tmp['type']  = 'after_download_failure';
        $expected     = 'Interaction download failure - On download failure (My Template)';
        $this->assertEquals($expected, $interaction->getInteractionDescription($tmp));
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

        $interaction     = new PluginGlpiinventoryDeployUserinteraction();
        $params = ['id'                   => $packages_id,
                 'userinteractionstype' => 'before',
                 'name'                 => 'My interaction',
                 'name'                 => 'interaction 1',
                 'title'                => 'My title',
                 'text'                 => 'my text',
                 'template'             => 0,
                ];
        $interaction->add_item($params);
        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[],"userinteractions":[{"name":"interaction 1","title":"My title","text":"my text","type":"before","template":0}]},"associatedFiles":[]}';
        $json     = Toolbox::stripslashes_deep($interaction->getJson($packages_id));
        $this->assertEquals($expected, $json);

        $params = ['id'                   => $packages_id,
                 'userinteractionstype' => 'after',
                 'name'                 => 'My interaction 2',
                 'name'                 => 'interaction 2',
                 'title'                => 'My title',
                 'text'                 => 'my text',
                 'template'             => 0,
                ];
        $interaction->add_item($params);
        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[],"userinteractions":[{"name":"interaction 1","title":"My title","text":"my text","type":"before","template":0},{"name":"interaction 2","title":"My title","text":"my text","type":"after","template":0}]},"associatedFiles":[]}';
        $json     = Toolbox::stripslashes_deep($interaction->getJson($packages_id));
        $this->assertEquals($expected, $json);
    }


   /**
    * @test
    * @depends testAdd_item
    */
    public function testSave_item()
    {
        $_SESSION['glpiactiveentities_string'] = 0;

        $interaction = new PluginGlpiinventoryDeployUserinteraction();
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $pfDeployPackage->getFromDBByCrit(['name' => 'test1']);
        $params = ['id'                   => $pfDeployPackage->fields['id'],
                 'index'                => 0,
                 'userinteractionstype' => 'after',
                 'name'                 => 'My interaction',
                 'name'                 => 'interaction 1',
                 'title'                => 'My title',
                 'text'                 => 'my text',
                 'template'             => 1,
                ];
        $interaction->save_item($params);
        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[],"userinteractions":[{"name":"interaction 1","title":"My title","text":"my text","type":"after","template":1},{"name":"interaction 2","title":"My title","text":"my text","type":"after","template":0}]},"associatedFiles":[]}';
        $json     = Toolbox::stripslashes_deep($interaction->getJson($pfDeployPackage->fields['id']));
        $this->assertEquals($expected, $json);
    }


   /**
    * @test
    * @depends testAdd_item
    */
    public function testMove_item()
    {
        $_SESSION['glpiactiveentities_string'] = 0;

        $interaction     = new PluginGlpiinventoryDeployUserinteraction();
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $pfDeployPackage->getFromDBByCrit(['name' => 'test1']);
        $interaction->move_item(['id'        => $pfDeployPackage->fields['id'],
                               'old_index' => 0,
                               'new_index' => 1]);
        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[],"userinteractions":[{"name":"interaction 2","title":"My title","text":"my text","type":"after","template":0},{"name":"interaction 1","title":"My title","text":"my text","type":"after","template":1}]},"associatedFiles":[]}';
        $json     = $interaction->getJson($pfDeployPackage->fields['id']);
        $this->assertEquals($expected, $json);
    }


   /**
    * @test
    * @depends testAdd_item
    */
    public function testRemove_item()
    {
        $_SESSION['glpiactiveentities_string'] = 0;

        $interaction     = new PluginGlpiinventoryDeployUserinteraction();
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $pfDeployPackage->getFromDBByCrit(['name' => 'test1']);
        $interaction->remove_item(['packages_id'              => $pfDeployPackage->fields['id'],
                                 'userinteractions_entries' => [0 => 'on']]);
        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[],"userinteractions":[{"name":"interaction 1","title":"My title","text":"my text","type":"after","template":1}]},"associatedFiles":[]}';
        $json     = $interaction->getJson($pfDeployPackage->fields['id']);
        $this->assertEquals($expected, $json);

        $interaction->remove_item(['packages_id'              => $pfDeployPackage->fields['id'],
                                 'userinteractions_entries' => [0 => 'on']]);
        $expected = '{"jobs":{"checks":[],"associatedFiles":[],"actions":[],"userinteractions":[]},"associatedFiles":[]}';
        $json     = $interaction->getJson($pfDeployPackage->fields['id']);
        $this->assertEquals($expected, $json);
    }
}
