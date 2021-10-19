<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

class RuleEntityTest extends TestCase {


   public static function setUpBeforeClass(): void {

      // Delete all entities exept root entity
      $entity = new Entity();
      $items = $entity->find();
      foreach ($items as $item) {
         if ($item['id'] > 0) {
            $entity->delete(['id' => $item['id']], true);
         }
      }

      // Delete all entityrules
      $rule = new Rule();
      $items = $rule->find(['sub_type' => 'PluginGlpiinventoryInventoryRuleEntity']);
      foreach ($items as $item) {
         $rule->delete(['id' => $item['id']], true);
      }

      // Delete all computers
      $computer = new Computer();
      $items = $computer->find();
      foreach ($items as $item) {
         $computer->delete(['id' => $item['id']], true);
      }
   }

   public static function tearDownAfterClass(): void {
      // Delete all entity rules
      $rule = new Rule();
      $items = $rule->find(['sub_type' => "PluginGlpiinventoryInventoryRuleEntity"]);
      foreach ($items as $item) {
         $rule->delete(['id' => $item['id']], true);
      }

   }

   /**
    * @test
    */
   public function TwoRegexpEntitiesTest() {
      $entity = new Entity();

      $entityAId = $entity->add([
         'name'        => 'entity A',
         'entities_id' => 0,
         'comment'     => '',
         'tag'         => 'entA'
      ]);
      $this->assertNotFalse($entityAId);

      $entityBId = $entity->add([
         'name'        => 'entity B',
         'entities_id' => 0,
         'comment'     => '',
         'tag'         => 'entB'
      ]);
      $this->assertNotFalse($entityBId);

      $entityCId = $entity->add([
         'name'        => 'entity C',
         'entities_id' => 0,
         'comment'     => '',
         'tag'         => 'entC'
      ]);
      $this->assertNotFalse($entityBId);

      // Add a rule for get entity tag (1)
      $rule = new Rule();
      $input = [
         'is_active' => 1,
         'name'      => 'entity rule 1',
         'match'     => 'AND',
         'sub_type'  => 'PluginGlpiinventoryInventoryRuleEntity',
         'ranking'   => 1
      ];
      $rule1_id = $rule->add($input);

      // Add criteria
      $rulecriteria = new RuleCriteria();
      $input = [
         'rules_id'  => $rule1_id,
         'criteria'  => "name",
         'pattern'   => "/^([A-Za-z0-9]*) - ([A-Za-z0-9]*) - (.*)$/",
         'condition' => PluginGlpiinventoryInventoryRuleEntity::REGEX_MATCH
      ];
      $rulecriteria->add($input);

      // Add action
      $ruleaction = new RuleAction();
      $input = [
         'rules_id'    => $rule1_id,
         'action_type' => 'regex_result',
         'field'       => '_affect_entity_by_tag',
         'value'       => '#2'
      ];
      $ruleaction->add($input);

      // Add a rule for get entity tag (2)
      $rule = new Rule();
      $input = [
      'is_active' => 1,
      'name'      => 'entity rule 2',
      'match'     => 'AND',
      'sub_type'  => 'PluginGlpiinventoryInventoryRuleEntity',
      'ranking'   => 2
      ];
      $rule2_id = $rule->add($input);

      // Add criteria
      $rulecriteria = new RuleCriteria();
      $input = [
         'rules_id'  => $rule2_id,
         'criteria'  => "name",
         'pattern'   => "/^([A-Za-z0-9]*) - (.*)$/",
         'condition' => PluginGlpiinventoryInventoryRuleEntity::REGEX_MATCH
      ];
      $rulecriteria->add($input);

      // Add action
      $ruleaction = new RuleAction();
      $input = [
         'rules_id'    => $rule2_id,
         'action_type' => 'regex_result',
         'field'       => '_affect_entity_by_tag',
         'value'       => '#1'
      ];
      $ruleaction->add($input);

      $input = [
      'name' => 'computer01 - entC'
      ];

      $ruleEntity = new PluginGlpiinventoryInventoryRuleEntityCollection();
      $ruleEntity->getCollectionPart();
      $ent = $ruleEntity->processAllRules($input, []);

      $a_references = [
      'entities_id' => $entityCId,
      '_ruleid'     => $rule2_id
      ];

      $this->assertEquals($a_references, $ent, 'Entity C');

      $input = [
      'name' => 'computer01 - blabla - entB'
      ];

      $ruleEntity = new PluginGlpiinventoryInventoryRuleEntityCollection();
      $ruleEntity->getCollectionPart();
      $ent = $ruleEntity->processAllRules($input, []);

      $a_references = [
      'entities_id' => $entityBId,
      '_ruleid'     => $rule1_id
      ];

      $this->assertEquals($a_references, $ent, 'Entity B');
   }
}
