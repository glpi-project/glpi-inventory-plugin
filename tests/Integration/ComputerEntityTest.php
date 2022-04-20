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

class ComputerEntityTest extends TestCase
{
    private static $entities_id_1;
    private static $entities_id_2;

    public static function setUpBeforeClass(): void
    {

       // Delete all entities except root entity
        $entity = new Entity();
        $items = $entity->find();
        foreach ($items as $item) {
            if ($item['id'] > 0) {
                $entity->delete(['id' => $item['id']], true);
            }
        }

       // Delete all computers
        $computer = new Computer();
        $items = $computer->find(['NOT' => ['name' => ['LIKE', '_test_pc%']]]);
        foreach ($items as $item) {
            $computer->delete(['id' => $item['id']], true);
        }

       // Delete all entity rules
        $rule = new Rule();
        $items = $rule->find(['sub_type' => RuleImportEntity::class]);
        foreach ($items as $item) {
            $rule->delete(['id' => $item['id']], true);
        }

        $agent = new Agent();
        $agents = $agent->find();
        foreach ($agents as $item) {
            $agent->delete(['id' => $item['id']], true);
        }
    }

   /**
    * Add computer in entity `ent1` (with rules)
    *
    * @test
    */
    public function AddComputer()
    {
        $entity = new Entity();

        self::$entities_id_1 = $entity->add([
         'name'        => 'ent1',
         'entities_id' => 0,
         'comment'     => '',
        ]);
        $this->assertNotFalse(self::$entities_id_1);

        self::$entities_id_2 = $entity->add([
         'name'        => 'ent2',
         'entities_id' => 0,
         'comment'     => ''
        ]);
        $this->assertNotFalse(self::$entities_id_2);

        $computer = new Computer();

       // * Add rule ignore
        $rule = new Rule();
        $ruleCriteria = new RuleCriteria();
        $ruleAction = new RuleAction();

        $input = [
         'sub_type'   => RuleImportEntity::class,
         'name'       => 'pc1',
         'match'      => 'AND',
         'is_active'  => 1
        ];
        $rules_id = $rule->add($input);
        $this->assertNotFalse($rules_id);

        $input = [
         'rules_id'   => $rules_id,
         'criteria'   => 'name',
         'condition'  => 0,
         'pattern'    => 'pc1'
        ];
        $this->assertNotFalse($ruleCriteria->add($input));

        $input = [
         'rules_id'      => $rules_id,
         'action_type'   => 'assign',
         'field'         => 'entities_id',
         'value'         => self::$entities_id_1
        ];
        $this->assertNotFalse($ruleAction->add($input));

       // ** Add
        $this->inventoryPc1();

        $nbComputers = countElementsInTable("glpi_computers", ['NOT' => ['name' => ['LIKE', '_test_pc%']]]);
        $this->assertEquals(1, $nbComputers, 'Nb computer for update computer');

        $computer->getFromDBByCrit(['name' => 'pc1']);
        $this->assertEquals(self::$entities_id_1, $computer->fields['entities_id'], 'Add computer');

        $this->agentEntity($computer->fields['id'], self::$entities_id_1, 'Add computer on entity 1');

       // ** Update
        $this->inventoryPc1();

        $computers = getAllDataFromTable("glpi_computers", ['NOT' => ['name' => ['LIKE', '_test_pc%']]]);
        $this->assertEquals(1, count($computers), 'Nb computer for update computer ' . print_r($computers, true));

        $computer->getFromDBByCrit(['name' => 'pc1']);
        $this->assertEquals(self::$entities_id_1, $computer->fields['entities_id'], 'Update computer');

        $this->agentEntity($computer->fields['id'], self::$entities_id_1, 'Update computer on entity 1 (not changed)');
    }


   /**
    * Update computer to change entity (transfer allowed)
    *
    * @test
    */
    public function updateComputerTransfer()
    {
        global $CFG_GLPI;

        $transfer       = new Transfer();
        $computer       = new Computer();
        $entity = new Entity();

       // Manual transfer computer to entity 2
        $transfer->getFromDB(1);
        $this->assertTrue($computer->getFromDBByCrit(['serial' => 'xxyyzz']));
        $item_to_transfer = ["Computer" => [1 => $computer->fields['id']]];
        $transfer->moveItems($item_to_transfer, 2, $transfer->fields);

        $computer->getFromDBByCrit(['serial' => 'xxyyzz']);
        $this->assertEquals(2, $computer->fields['entities_id'], 'Transfer move computer');

        $this->agentEntity($computer->fields['id'], 1, 'Transfer computer on entity 2');

        // Define entity 2 to allow to transfer
        $this->assertTrue($entity->getFromDB(self::$entities_id_2));
        $this->assertTrue(
            $entity->update([
                'id' => self::$entities_id_2,
                'transfers_id' => 1,
                'transfers_strategy' => 0
            ])
        );

        // Update computer and computer must be transferred
        $this->inventoryPc1();

        $nbComputers = countElementsInTable("glpi_computers", ['NOT' => ['name' => ['LIKE', '_test_pc%']]]);
        $this->assertEquals(1, $nbComputers, 'Nb computer for update computer');

        $computer->getFromDBByCrit(['serial' => 'xxyyzz']);
        $this->assertEquals(1, $computer->fields['entities_id'], 'Automatic transfer computer');

        $this->agentEntity($computer->fields['id'], 1, 'Automatic transfer computer on entity 1');
    }


   /**
    * Update computer to not change entity (transfer not allowed)
    *
    * @test
    */
    public function updateComputerNoTransfer()
    {

        $transfer = new Transfer();
        $computer = new Computer();
        $entity = new Entity();

       // Manual transfer computer to entity 2
        $transfer->getFromDB(1);
        $computer->getFromDBByCrit(['serial' => 'xxyyzz']);
        $item_to_transfer = ["Computer" => [1 => $computer->fields['id']]];
        $transfer->moveItems($item_to_transfer, 2, $transfer->fields);

        $computer->getFromDBByCrit(['serial' => 'xxyyzz']);
        $this->assertEquals(2, $computer->fields['entities_id'], 'Transfer move computer');

        $this->agentEntity($computer->fields['id'], 1, 'Transfer computer on entity 2');

       // Define entity 2 to disallowed transfer
        $this->assertTrue($entity->getFromDB(self::$entities_id_2));
        $this->assertTrue(
            $entity->update([
                'id' => self::$entities_id_2,
                'transfers_id' => 0,
                'transfers_strategy' => 0
            ])
        );

       // Update computer and computer must not be transferred (keep in entity 2)
        $this->inventoryPc1();

        $this->assertEquals(1, countElementsInTable('glpi_computers', ['NOT' => ['name' => ['LIKE', '_test_pc%']]]), 'Must have only 1 computer');

        $computer->getFromDBByCrit(['serial' => 'xxyyzz']);
        $this->assertEquals(2, $computer->fields['entities_id'], 'Computer must not be transferred');

        $this->agentEntity($computer->fields['id'], 1, 'Agent must stay with entity 1');
    }


   /**
    * Update computer with restrict entity (in this case computer added)
    *
    * @test
    */
    public function updateaddComputerRestrictEntity()
    {
        global $DB;

        $computer = new Computer();

       // Disable all rules
        $DB->update(
            Rule::getTable(),
            [
            'is_active' => 0
            ],
            [
            'sub_type' => RuleImportAsset::class
            ]
        );

       // Add rule name + restrict entity search
        $rulecollection = new RuleImportAssetCollection();
        $input = [
         'is_active' => 1,
         'name'      => 'Computer name + restrict',
         'match'     => 'AND',
         'sub_type'  => RuleImportAsset::class,
         'ranking'   => 1
        ];
        $rule_id = $rulecollection->add($input);
        $this->assertNotFalse($rule_id);

       // Add criteria
        $rule = $rulecollection->getRuleClass();
        $rulecriteria = new RuleCriteria(get_class($rule));
        $input = [
         'rules_id'  => $rule_id,
         'criteria'  => 'name',
         'pattern'   => 1,
         'condition' => RuleImportAsset::PATTERN_FIND,
        ];
        $this->assertNotFalse($rulecriteria->add($input));

        $input = [
         'rules_id'  => $rule_id,
         'criteria'  => 'name',
         'pattern'   => 1,
         'condition' => RuleImportAsset::PATTERN_EXISTS,
        ];
        $this->assertNotFalse($rulecriteria->add($input));

        $input = [
         'rules_id'  => $rule_id,
         'criteria'  => 'entityrestrict',
         'pattern'   => '',
         'condition' => RuleImportAsset::PATTERN_ENTITY_RESTRICT,
        ];
        $this->assertNotFalse($rulecriteria->add($input));

        $input = [
         'rules_id'  => $rule_id,
         'criteria'  => 'itemtype',
         'pattern'   => 'Computer',
         'condition' => RuleImportAsset::PATTERN_IS,
        ];
        $this->assertNotFalse($rulecriteria->add($input));

       // Add action
        $ruleaction = new RuleAction(get_class($rule));
        $input = [
         'rules_id'    => $rule_id,
         'action_type' => 'assign',
         'field'       => '_fusion',
         'value'       => '1',
        ];
        $this->assertNotFalse($ruleaction->add($input));

        $this->inventoryPc1();

        $this->assertEquals(2, countElementsInTable('glpi_computers', ['NOT' => ['name' => ['LIKE', '_test_pc%']]]), 'Must have only 2 computer');

        $item = current($computer->find(['serial' => 'xxyyzz'], ['id DESC'], 1));
        $this->assertEquals(1, $item['entities_id'], 'Second computer added');
    }


    protected function agentEntity($computers_id = 0, $entities_id = 0, $text = '')
    {

        if ($computers_id == 0) {
            return;
        }

        $agent = new Agent();
        $this->assertTrue($agent->getFromDBByCrit(['itemtype' => 'Computer', 'items_id' => $computers_id]));
        $a_agents_id = $agent->fields['id'];
        $agent->getFromDB($a_agents_id);
        $this->assertEquals($entities_id, $agent->fields['entities_id'], $text);
    }

    protected function inventoryPc1()
    {
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <NAME>pc1</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>xxyyzz</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>pc-2013-02-13</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $converter = new \Glpi\Inventory\Converter();
        $source = json_decode($converter->convert($xml_source));

        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($source);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertEquals([], $inventory->getErrors());
    }
}
