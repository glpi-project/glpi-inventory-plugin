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

class CollectRuleTest extends TestCase
{
    public $rules_id = 0;
    public $ruleactions_id = 0;

    public static function setUpBeforeClass(): void
    {

       // Delete all computermodels
        $computerModel = new ComputerModel();
        $items = $computerModel->find();
        foreach ($items as $item) {
            $computerModel->delete(['id' => $item['id']], true);
        }

       // Delete all collectrules
        $rule = new Rule();
        $items = $rule->find(['sub_type' => "PluginGlpiinventoryCollectRule"]);
        foreach ($items as $item) {
            $rule->delete(['id' => $item['id']], true);
        }
    }


   /**
    * @test
    */
    public function prepareDatabase()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $rule = new Rule();
        $ruleCriteria = new RuleCriteria();
        $ruleAction = new RuleAction();

        $computerModel = new ComputerModel();
        $items = $computerModel->find();
        foreach ($items as $item) {
            $computerModel->delete(['id' => $item['id']], true);
        }

        $input = [
          'name' => '6430u'
        ];
        $computerModelId = $computerModel->add($input);
        $this->assertNotFalse($computerModelId);

       // * computer model assign
        $input = [
          'entities_id' => 0,
          'sub_type'    => 'PluginGlpiinventoryCollectRule',
          'name'        => 'computer model',
          'match'       => 'AND'
        ];
        $rules_id = $rule->add($input);

        $input = [
          'rules_id'  => $rules_id,
          'criteria'  => 'filename',
          'condition' => 6,
          'pattern'   => "/latitude(.*)/"
        ];
        $ruleCriteria->add($input);

        $input = [
          'rules_id'    => $rules_id,
          'action_type' => 'assign',
          'field'       => 'computermodels_id',
          'value'       => $computerModelId
        ];
        $this->ruleactions_id = $ruleAction->add($input);

       // * computer model regex
        $input = [
          'entities_id' => 0,
          'sub_type'    => 'PluginGlpiinventoryCollectRule',
          'name'        => 'computer model 2',
          'match'       => 'AND'
        ];
        $rules_id = $rule->add($input);

        $input = [
          'rules_id'  => $rules_id,
          'criteria'  => 'filename',
          'condition' => 6,
          'pattern'   => "/longitude(.*)/"
        ];
        $ruleCriteria->add($input);

        $input = [
          'rules_id'    => $rules_id,
          'action_type' => 'regex_result',
          'field'       => 'computermodels_id',
          'value'       => '#0'
        ];
        $this->ruleactions_id = $ruleAction->add($input);

       // * user regex
        $input = [
          'entities_id' => 0,
          'sub_type'    => 'PluginGlpiinventoryCollectRule',
          'name'        => 'user',
          'match'       => 'AND'
        ];
        $rules_id = $rule->add($input);

        $input = [
          'rules_id'  => $rules_id,
          'criteria'  => 'filename',
          'condition' => 6,
          'pattern'   => "/user (.*)/"
        ];
        $ruleCriteria->add($input);

        $input = [
          'rules_id'    => $rules_id,
          'action_type' => 'regex_result',
          'field'       => 'user',
          'value'       => '#0'
        ];
        $this->ruleactions_id = $ruleAction->add($input);

       // * softwareversion regex
        $input = [
          'entities_id' => 0,
          'sub_type'    => 'PluginGlpiinventoryCollectRule',
          'name'        => 'softwareversion 3.0',
          'match'       => 'AND'
        ];
        $rules_id = $rule->add($input);

        $input = [
          'rules_id'  => $rules_id,
          'criteria'  => 'filename',
          'condition' => 6,
          'pattern'   => "/version (.*)/"
        ];
        $ruleCriteria->add($input);

        $input = [
          'rules_id'    => $rules_id,
          'action_type' => 'regex_result',
          'field'       => 'softwareversion',
          'value'       => '#0'
        ];
        $this->ruleactions_id = $ruleAction->add($input);

       // * otherserial regex
        $input = [
          'entities_id' => 0,
          'sub_type'    => 'PluginGlpiinventoryCollectRule',
          'name'        => 'otherserial',
          'match'       => 'AND'
        ];
        $rules_id = $rule->add($input);

        $input = [
          'rules_id'  => $rules_id,
          'criteria'  => 'filename',
          'condition' => 6,
          'pattern'   => "/other (.*)/"
        ];
        $ruleCriteria->add($input);

        $input = [
          'rules_id'    => $rules_id,
          'action_type' => 'regex_result',
          'field'       => 'otherserial',
          'value'       => '#0'
        ];
        $this->ruleactions_id = $ruleAction->add($input);

       // * otherserial regex
        $input = [
          'entities_id' => 0,
          'sub_type'    => 'PluginGlpiinventoryCollectRule',
          'name'        => 'otherserial assign',
          'match'       => 'AND'
        ];
        $rules_id = $rule->add($input);

        $input = [
          'rules_id'  => $rules_id,
          'criteria'  => 'filename',
          'condition' => 6,
          'pattern'   => "/serial (.*)/"
        ];
        $ruleCriteria->add($input);

        $input = [
          'rules_id'    => $rules_id,
          'action_type' => 'assign',
          'field'       => 'otherserial',
          'value'       => 'ttuujj'
        ];
        $this->ruleactions_id = $ruleAction->add($input);

       // * computer comment assign
        $input = [
          'entities_id' => 0,
          'sub_type'    => 'PluginGlpiinventoryCollectRule',
          'name'        => 'comment assign rule',
          'match'       => 'AND'
        ];
        $rules_id = $rule->add($input);

        $input = [
          'rules_id'  => $rules_id,
          'criteria'  => 'filename',
          'condition' => 6,
          'pattern'   => "/dell (.*)/"
        ];
        $ruleCriteria->add($input);

        $input = [
          'rules_id'    => $rules_id,
          'action_type' => 'assign',
          'field'       => 'comment',
          'value'       => 'mycomment'
        ];
        $this->ruleactions_id = $ruleAction->add($input);
    }


   /**
    * @test
    */
    public function getComputerCommentAssign()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollectRuleCollection = new PluginGlpiinventoryCollectRuleCollection();

        $res_rule = $pfCollectRuleCollection->processAllRules(
            [
            'filename'  => 'dell d630',
            'filepath'  => '/tmp',
            'size'      => 1000
            ]
        );
        $this->assertEquals('mycomment', $res_rule['comment']);
    }

   /**
    * @test
    */
    public function getComputerModelAssign()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollectRuleCollection = new PluginGlpiinventoryCollectRuleCollection();

        $res_rule = $pfCollectRuleCollection->processAllRules(
            [
            'filename'  => 'latitude 6430u',
            'filepath'  => '/tmp',
            'size'      => 1000
            ]
        );
        $computerModel = new ComputerModel();
        $computerModel->getFromDBByCrit(['name' => '6430u']);
        $this->assertEquals($computerModel->fields['id'], $res_rule['computermodels_id']);
    }


   /**
    * @test
    */
    public function getComputerModelRegexCreate()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollectRuleCollection = new PluginGlpiinventoryCollectRuleCollection();

        $res_rule = $pfCollectRuleCollection->processAllRules(
            [
            'filename'  => 'longitude 6431u',
            'filepath'  => '/tmp',
            'size'      => 1000
            ]
        );
        $computerModel = new ComputerModel();
        $computerModel->getFromDBByCrit(['name' => '6431u']);

        $this->assertEquals($computerModel->fields['id'], $res_rule['computermodels_id']);
    }


   /**
    * @test
    */
    public function getComputerModelRegex()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollectRuleCollection = new PluginGlpiinventoryCollectRuleCollection();

        $res_rule = $pfCollectRuleCollection->processAllRules(
            [
            'filename'  => 'longitude 6430u',
            'filepath'  => '/tmp',
            'size'      => 1000
            ]
        );
        $computerModel = new ComputerModel();
        $computerModel->getFromDBByCrit(['name' => '6430u']);

        $this->assertEquals($computerModel->fields['id'], $res_rule['computermodels_id']);
    }


   /**
    * @test
    */
    public function getUserRegex()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollectRuleCollection = new PluginGlpiinventoryCollectRuleCollection();

        $res_rule = $pfCollectRuleCollection->processAllRules(
            [
            'filename'  => 'user david',
            'filepath'  => '/tmp',
            'size'      => 1000
            ]
        );

        $this->assertEquals('david', $res_rule['user']);
    }


   /**
    * @test
    */
    public function getSoftwareVersionRegex()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollectRuleCollection = new PluginGlpiinventoryCollectRuleCollection();

        $res_rule = $pfCollectRuleCollection->processAllRules(
            [
            'filename'  => 'version 3.2.0',
            'filepath'  => '/tmp',
            'size'      => 1000
            ]
        );

        $this->assertEquals('3.2.0', $res_rule['softwareversion']);
    }


   /**
    * @test
    */
    public function getOtherserialRegex()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollectRuleCollection = new PluginGlpiinventoryCollectRuleCollection();

        $res_rule = $pfCollectRuleCollection->processAllRules(
            [
            'filename'  => 'other xxyyzz',
            'filepath'  => '/tmp',
            'size'      => 1000
            ]
        );

        $this->assertEquals('xxyyzz', $res_rule['otherserial']);
    }


   /**
    * @test
    */
    public function getOtherserialAssign()
    {

        $_SESSION["plugin_glpiinventory_entity"] = 0;
        $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

        $pfCollectRuleCollection = new PluginGlpiinventoryCollectRuleCollection();

        $res_rule = $pfCollectRuleCollection->processAllRules(
            [
            'filename'  => 'serial clic',
            'filepath'  => '/tmp',
            'size'      => 1000
            ]
        );

        $this->assertEquals('ttuujj', $res_rule['otherserial']);
    }
}
