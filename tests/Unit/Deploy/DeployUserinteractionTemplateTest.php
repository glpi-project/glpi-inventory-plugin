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

class DeployUserinteractionTemplateTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {

       // Delete all Interactions
        $interaction = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $items = $interaction->find();
        foreach ($items as $item) {
            $interaction->delete(['id' => $item['id']], true);
        }
    }


   /**
    * @test
    */
    public function testDefineTabs()
    {
        $expected = [
                   'PluginGlpiinventoryDeployUserinteractionTemplate$1' => 'General',
                   'PluginGlpiinventoryDeployUserinteractionTemplate$2' => 'Behaviors',
                   'Log$1' => 'Historical'
                  ];
        $template = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $this->assertEquals($expected, $template->defineTabs());
    }


   /**
    * @test
    */
    public function testGetTabNameForItem()
    {
        $expected = [  1 => 'General', 2 => 'Behaviors'];
        $template = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $this->assertEquals($expected, $template->getTabNameForItem($template));
    }


   /**
    * @test
    */
    public function testGetTypeName()
    {
        $this->assertEquals(
            'User interaction templates',
            PluginGlpiinventoryDeployUserinteractionTemplate::getTypeName()
        );
        $this->assertEquals(
            'User interaction template',
            PluginGlpiinventoryDeployUserinteractionTemplate::getTypeName(1)
        );
        $this->assertEquals(
            'User interaction templates',
            PluginGlpiinventoryDeployUserinteractionTemplate::getTypeName(2)
        );
    }


   /**
    * @test
    */
    public function testGetTypes()
    {
        $types = PluginGlpiinventoryDeployUserinteractionTemplate::getTypes();
        $this->assertEquals(
            $types,
            [PluginGlpiinventoryDeployUserinteractionTemplate::ALERT_WTS => __("Windows system alert (WTS)", 'glpiinventory')]
        );
    }


   /**
    * @test
    */
    public function testGetButtons()
    {
        $buttons  = PluginGlpiinventoryDeployUserinteractionTemplate::getButtons(PluginGlpiinventoryDeployUserinteractionTemplate::ALERT_WTS);
        $this->assertEquals(8, count($buttons));

        $buttons = PluginGlpiinventoryDeployUserinteractionTemplate::getButtons('foo');
        $this->assertFalse($buttons);

        $buttons = PluginGlpiinventoryDeployUserinteractionTemplate::getButtons();
        $this->assertFalse($buttons);
    }


   /**
    * @test
    */
    public function testAddJsonFieldsToArray()
    {
        $template = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $template->fields['json'] = '{"platform":"wts","timeout":4,"buttons":"ok","retry_after":4,"nb_max_retry":4,"on_timeout":"continue","on_nouser":"continue","on_multiusers":"cancel"}';
        $result = ['name' => 'foo'];
        $result = $template->addJsonFieldsToArray($result);

        $expected = ['name'          => 'foo',
                   'platform'      => 'wts',
                   'timeout'      => 4,
                   'buttons'       => 'ok',
                   'retry_after'   => 4,
                   'nb_max_retry'  => 4,
                   'on_timeout'    => 'continue',
                   'on_nouser'     => 'continue',
                   'on_multiusers' => 'cancel',
                   'wait'          => 'yes'];
        $this->assertEquals($expected, $result);

        $template->fields['json'] = '{"platform":"wts","timeout":4,"buttons":"ok_async","retry_after":4,"nb_max_retry":4,"on_timeout":"continue","on_nouser":"continue","on_multiusers":"cancel"}';
        $result = ['name' => 'foo'];
        $result = $template->addJsonFieldsToArray($result);

        $expected = ['name'          => 'foo',
                   'platform'      => 'wts',
                   'timeout'      => 4,
                   'buttons'       => 'ok',
                   'retry_after'   => 4,
                   'nb_max_retry'  => 4,
                   'on_timeout'    => 'continue',
                   'on_nouser'     => 'continue',
                   'on_multiusers' => 'cancel',
                   'wait'          => 'no'];
        $this->assertEquals($expected, $result);
    }


   /**
    * @test
    */
    public function testGetIcons()
    {
        $icons = PluginGlpiinventoryDeployUserinteractionTemplate::getIcons(PluginGlpiinventoryDeployUserinteractionTemplate::ALERT_WTS);
        $this->assertEquals(5, count($icons));
        $this->assertEquals($icons, [ PluginGlpiinventoryDeployUserinteractionTemplate::WTS_ICON_NONE     => __('None'),
                                    PluginGlpiinventoryDeployUserinteractionTemplate::WTS_ICON_WARNING  => __('Warning'),
                                    PluginGlpiinventoryDeployUserinteractionTemplate::WTS_ICON_INFO     => _n('Information', 'Informations', 1),
                                    PluginGlpiinventoryDeployUserinteractionTemplate::WTS_ICON_ERROR    => __('Error'),
                                    PluginGlpiinventoryDeployUserinteractionTemplate::WTS_ICON_QUESTION => __('Question', 'glpiinventory')
                                   ]);

        $icons = PluginGlpiinventoryDeployUserinteractionTemplate::getIcons('foo');
        $this->assertFalse($icons);

        $icons = PluginGlpiinventoryDeployUserinteractionTemplate::getIcons();
        $this->assertEquals($icons, [ PluginGlpiinventoryDeployUserinteractionTemplate::WTS_ICON_NONE     => __('None'),
                                    PluginGlpiinventoryDeployUserinteractionTemplate::WTS_ICON_WARNING  => __('Warning'),
                                    PluginGlpiinventoryDeployUserinteractionTemplate::WTS_ICON_INFO     => _n('Information', 'Informations', 1),
                                    PluginGlpiinventoryDeployUserinteractionTemplate::WTS_ICON_ERROR    => __('Error'),
                                    PluginGlpiinventoryDeployUserinteractionTemplate::WTS_ICON_QUESTION => __('Question', 'glpiinventory')
                                   ]);
    }


   /**
    * @test
    */
    public function testGetBehaviors()
    {
        $behaviors = PluginGlpiinventoryDeployUserinteractionTemplate::getBehaviors();
        $expected  = [PluginGlpiinventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY => __('Continue job with no user interaction'),
                    PluginGlpiinventoryDeployUserinteractionTemplate::BEHAVIOR_POSTPONE_DEPLOY => __('Retry job later', 'glpiinventory'),
                    PluginGlpiinventoryDeployUserinteractionTemplate::BEHAVIOR_STOP_DEPLOY   => __('Cancel job')
                   ];
        $this->assertEquals($expected, $behaviors);
    }


   /**
    * @test
    */
    public function testAdd()
    {
        $interaction = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $tmp = ['name'         => 'test',
              'entities_id'  => 0,
              'is_recursive' => 0,
              'json'         => ''
             ];
        $this->assertNotNull($interaction->add($tmp));
        $interaction->getFromDB(1);
        $this->assertEquals('[]', $interaction->fields['json']);

        $tmp = ['name'         => 'test2',
              'entities_id'  => 0,
              'is_recursive' => 0,
              'platform'     => PluginGlpiinventoryDeployUserinteractionTemplate::ALERT_WTS,
              'timeout'      => 4,
              'buttons'      => PluginGlpiinventoryDeployUserinteractionTemplate::WTS_BUTTON_OK_SYNC,
              'icon'         => 'warning',
              'retry_after'  => 4,
              'nb_max_retry' => 4,
              'on_timeout'   => PluginGlpiinventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY,
              'on_nouser'    => PluginGlpiinventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY,
              'on_multiusers' => PluginGlpiinventoryDeployUserinteractionTemplate::BEHAVIOR_STOP_DEPLOY
             ];
        $this->assertNotNull($interaction->add($tmp));
        $expected = '{"platform":"win32","timeout":4,"buttons":"ok","icon":"warning","retry_after":4,"nb_max_retry":4,"on_timeout":"continue:continue","on_nouser":"continue:continue","on_multiusers":"stop:stop"}';
        $this->assertEquals($expected, $interaction->fields['json']);
    }


   /**
    * @test
    */
    public function testUpdate()
    {
        $interaction = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $interaction->getFromDBByCrit(['name' => 'test']);
        $tmp = [
         'id'   => $interaction->fields['id'],
         'name' => 'test_update',
         'json' => ''
        ];
        $this->assertTrue($interaction->update($tmp));
        $this->assertEquals('test_update', $interaction->fields['name']);
    }


   /**
    * @test
    */
    public function testSaveToJson()
    {
        $values = ['name'          => 'interaction',
                 'platform'      => PluginGlpiinventoryDeployUserinteractionTemplate::ALERT_WTS,
                 'timeout'       => 4,
                 'buttons'       => PluginGlpiinventoryDeployUserinteractionTemplate::WTS_BUTTON_OK_SYNC,
                 'icon'          => 'warning',
                 'retry_after'   => 4,
                 'nb_max_retry'  => 4,
                 'on_timeout'    => PluginGlpiinventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY,
                 'on_nouser'     => PluginGlpiinventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY,
                 'on_multiusers' => PluginGlpiinventoryDeployUserinteractionTemplate::BEHAVIOR_STOP_DEPLOY
                ];
        $interaction = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $result      = $interaction->saveToJson($values);
        $expected    = '{"platform":"win32","timeout":4,"buttons":"ok","icon":"warning","retry_after":4,"nb_max_retry":4,"on_timeout":"continue:continue","on_nouser":"continue:continue","on_multiusers":"stop:stop"}';
        $this->assertEquals($expected, $result);

        $result      = $interaction->saveToJson([]);
        $this->assertEquals($result, "[]");
    }


   /**
    * @test
    */
    public function testGestMainFormFields()
    {
        $template = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $expected = ['platform', 'timeout', 'buttons', 'icon',
                   'retry_after', 'nb_max_retry'];
        $this->assertEquals($expected, $template->getMainFormFields());
    }


   /**
    * @test
    */
    public function testGetBehaviorsFields()
    {
        $template = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $expected = ['on_timeout', 'on_nouser', 'on_multiusers', 'on_ok', 'on_no',
                   'on_yes', 'on_cancel', 'on_abort', 'on_retry', 'on_tryagain',
                   'on_ignore', 'on_continue', 'on_async'];
        $this->assertEquals($expected, $template->getBehaviorsFields());
    }


   /**
    * @test
    */
    public function testGetJsonFields()
    {
        $template = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $expected = ['platform', 'timeout', 'buttons', 'icon',
                   'retry_after', 'nb_max_retry',
                   'on_timeout', 'on_nouser', 'on_multiusers', 'on_ok', 'on_no',
                   'on_yes', 'on_cancel', 'on_abort', 'on_retry', 'on_tryagain',
                   'on_ignore', 'on_continue', 'on_async'];
        $this->assertEquals($expected, $template->getJsonFields());
    }


   /**
    * @test
    */
    public function testInitializeJsonFields()
    {
        $template = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $this->assertEquals(19, count($template->initializeJsonFields([])));
    }


   /**
    * @test
    */
    public function testGetEvents()
    {
        $template = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $this->assertEquals(12, count($template->getEvents()));
    }


   /**
    * @test
    */
    public function testGetBehaviorsToDisplay()
    {
        $template = new PluginGlpiinventoryDeployUserinteractionTemplate();

        $this->assertEquals(
            ['on_timeout', 'on_nouser', 'on_multiusers', 'on_ok'],
            $template->getBehaviorsToDisplay('ok')
        );

        $this->assertEquals(
            ['on_timeout', 'on_nouser', 'on_multiusers', 'on_ok'],
            $template->getBehaviorsToDisplay('ok_async')
        );

        $this->assertEquals(
            ['on_timeout', 'on_nouser', 'on_multiusers',
                            'on_ok', 'on_cancel'],
            $template->getBehaviorsToDisplay('okcancel')
        );

        $this->assertEquals(
            ['on_timeout', 'on_nouser', 'on_multiusers',
                            'on_yes', 'on_no'],
            $template->getBehaviorsToDisplay('yesno')
        );

        $this->assertEquals(
            ['on_timeout', 'on_nouser', 'on_multiusers',
                            'on_yes', 'on_no', 'on_cancel'],
            $template->getBehaviorsToDisplay('yesnocancel')
        );

        $this->assertEquals(
            ['on_timeout', 'on_nouser', 'on_multiusers',
                            'on_abort', 'on_retry', 'on_ignore'],
            $template->getBehaviorsToDisplay('abortretryignore')
        );

        $this->assertEquals(
            ['on_timeout', 'on_nouser', 'on_multiusers',
                           'on_retry', 'on_cancel'],
            $template->getBehaviorsToDisplay('retrycancel')
        );

        $this->assertEquals(
            ['on_timeout', 'on_nouser', 'on_multiusers',
                           'on_tryagain', 'on_cancel', 'on_continue'],
            $template->getBehaviorsToDisplay('canceltrycontinue')
        );
    }


   /**
    * @test
    */
    public function testPrepareInputForAdd()
    {
        $template = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $input = ['name'       => 'foo',
                'button'     => PluginGlpiinventoryDeployUserinteractionTemplate::WTS_BUTTON_CANCEL_TRY_CONTINUE,
                'icon'       => PluginGlpiinventoryDeployUserinteractionTemplate::WTS_ICON_QUESTION,
                'on_timeout' => PluginGlpiinventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY
               ];
        $expected = '{"icon":"question","on_timeout":"continue:continue"}';
        $modified = $template->prepareInputForAdd($input);
        $this->assertEquals($expected, $modified['json']);
    }


   /**
    * @test
    */
    public function testGetDefaultBehaviorForAButton()
    {
        $template = new PluginGlpiinventoryDeployUserinteractionTemplate();
        $this->assertEquals('continue:continue', $template->getDefaultBehaviorForAButton('on_ok'));
        $this->assertEquals('continue:continue', $template->getDefaultBehaviorForAButton('on_yes'));
        $this->assertEquals('continue:continue', $template->getDefaultBehaviorForAButton('on_multiusers'));
        $this->assertEquals('continue:continue', $template->getDefaultBehaviorForAButton('on_timeout'));

        $this->assertEquals('stop:stop', $template->getDefaultBehaviorForAButton('on_no'));
        $this->assertEquals('stop:stop', $template->getDefaultBehaviorForAButton('on_cancel'));
        $this->assertEquals('stop:stop', $template->getDefaultBehaviorForAButton('on_abort'));

        $this->assertEquals('stop:postpone', $template->getDefaultBehaviorForAButton('on_retry'));
        $this->assertEquals('stop:postpone', $template->getDefaultBehaviorForAButton('on_ignore'));
    }
}
