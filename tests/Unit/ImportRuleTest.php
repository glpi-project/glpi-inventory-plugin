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

class ImportRuleTest extends TestCase {

   private $items_id = 0;
   private $itemtype = '';
   private $ports_id = 0;

   public static function setUpBeforeClass(): void {
      // Reinit rules
      $setup = new PluginGlpiinventorySetup();
      $setup->initRules(true, true);
   }

   public static function tearDownAfterClass(): void {
      // Reinit rules
      $setup = new PluginGlpiinventorySetup();
      $setup->initRules(true, true);
   }

   function setUp(): void {
      $this->items_id = 0;
      $this->itemtype = '';
      $this->ports_id = 0;

      // Delete all computers
      $computer = new Computer();
      $computers = $computer->find();
      foreach ($computers as $item) {
         $computer->delete(['id' => $item['id']], true);
      }

      // Delete all network equipments
      $networkEquipment = new NetworkEquipment();
      $items = $networkEquipment->find();
      foreach ($items as $item) {
         $networkEquipment->delete(['id' => $item['id']], true);
      }

      // Delete all printers
      $printer = new Printer();
      $items = $printer->find();
      foreach ($items as $item) {
         $printer->delete(['id' => $item['id']], true);
      }

      // Delete all unmanaged items
      $pfUnmanaged = new PluginGlpiinventoryUnmanaged();
      $items = $pfUnmanaged->find();
      foreach ($items as $item) {
         $pfUnmanaged->delete(['id' => $item['id']], true);
      }
   }

   function rulepassed($items_id, $itemtype, $ports_id = 0) {
      $this->items_id = $items_id;
      $this->itemtype = $itemtype;
      $this->ports_id = $ports_id;
   }

   static function getMethod() {
      return 'testRules';
   }

   function activeRule($name) {
      $pfRule = new PluginGlpiinventoryInventoryRuleImportCollection();
      $rules = $pfRule->find([
         'name'      => ['LIKE', '%'.$name.'%'],
         'is_active' => 0
      ]);
      foreach ($rules as $rule) {
         $pfRule->update([
            'id'        => $rule['id'],
            'is_active' => 1,
         ]);
      }
   }

   function activateAllRules() {
      $pfRule = new PluginGlpiinventoryInventoryRuleImportCollection();
      $rules = $pfRule->find();
      foreach ($rules as $rule) {
         $pfRule->update([
            'id'        => $rule['id'],
            'is_active' => 1,
         ]);
      }
   }

   function addRule($name, $criteria = [], $action = [], $afterRuleName = '') {
      global $DB;

      $rule = new PluginGlpiinventoryInventoryRuleImport();
      $rulecriteria = new RuleCriteria();
      $rulecollection = new PluginGlpiinventoryInventoryRuleImportCollection();

      $input = [
         'is_active' => 1,
         'name'      => $name,
         'match'     => 'AND',
         'sub_type'  => 'PluginGlpiinventoryInventoryRuleImport'
      ];
      if ($afterRuleName != '') {
         $ruleARN = $rule->find(['name' => $afterRuleName], [], 1);
         if (count($ruleARN) > 0) {
            $r = current($ruleARN);
            $DB->query("UPDATE glpi_rules "
                  . "SET ranking = ranking + 1 "
                  . "WHERE ranking > '".$r['ranking']."' "
                  . "   AND `sub_type`='PluginGlpiinventoryInventoryRuleImport'");

            $input['ranking'] = ($r['ranking'] + 1);
         }
      }
      $rules_id = $rulecollection->add($input);
      $DB->query("UPDATE glpi_rules SET `ranking`=1"
            . " WHERE `id`=".$rules_id);

      // Add criteria
      foreach ($criteria as $crit) {
         $input = [
            'rules_id'  => $rules_id,
            'criteria'  => $crit['criteria'],
            'pattern'   => $crit['pattern'],
            'condition' => $crit['condition'],
         ];
         $rulecriteria->add($input);
      }

      // Add action
      $ruleaction = new RuleAction();
      $input = [
         'rules_id'    => $rules_id,
         'action_type' => $action['action_type'],
         'field'       => $action['field'],
         'value'       => $action['value'],
      ];
      $ruleaction->add($input);
   }

   /**
    * @test
    */
   public function rulepassedNetworkEquipment_nodevice() {

      $pf = new PluginGlpiinventoryInventoryNetworkEquipmentLib();
      $pfUnmanaged = new PluginGlpiinventoryUnmanaged();
      $dbu = new DbUtils();
      $items = $pfUnmanaged->find();
      foreach ($items as $item) {
         $pfUnmanaged->delete(['id' => $item['id']], true);
      }
      $pf->data_device = [
         "name" => "test03"
      ];
      $_SESSION['plugin_glpiinventory_datacriteria'] = serialize(["name" => "test01"]);
      $pf->rulepassed(0, '', 0);
      $this->assertEquals(0, $dbu->countElementsInTable('glpi_networkports'));

      // Check if the device is right an unmanaged device
      $items = $pfUnmanaged->find();
      $this->assertEquals(1, count($items));
      $item = current($items);
      $this->assertEquals("test03", $item['name']);
   }

   /**
    * @test
    */
   public function rulepassedNetworkEquipment_device_noport() {

      $pf = new PluginGlpiinventoryInventoryNetworkEquipmentLib();
      $networkequipment = new NetworkEquipment();
      $networkport = new NetworkPort();
      $dbu = new DbUtils();

      $device_id = $networkequipment->add([
         'name'        => 'sw001',
         'entities_id' => 0,
      ]);

      $pf->data_device = [
         "ifdescr" => "Fa0/12",
         "name"    => "sw001",
      ];
      $pf->rulepassed($device_id, "NetworkEquipment", 0);

      $this->assertEquals(1, $dbu->countElementsInTable('glpi_networkports'));
      // Check if the device is right a networkequipment
      $ports = $networkport->find();
      $port = current($ports);
      $this->assertEquals("NetworkEquipment", $port['itemtype']);
      $this->assertEquals("Fa0/12", $port['name']);
   }

   /**
    * @test
    */
   public function rulepassedNetworkEquipment_device_port() {

      $pf = new PluginGlpiinventoryInventoryNetworkEquipmentLib();
      $networkequipment = new NetworkEquipment();
      $networkport = new NetworkPort();
      $dbu = new DbUtils();

      $device_id = $networkequipment->add([
         'name'        => 'sw001',
         'entities_id' => 0,
      ]);
      $ports_id = $networkport->add([
         'name'     => 'Fa0/12',
         'items_id' => $device_id,
         'itemtype' => 'NetworkEquipment',
      ]);

      $pf->data_device = [
         "ifdescr" => "Fa0/12",
         "name"    => "sw001",
      ];
      $pf->rulepassed($device_id, "NetworkEquipment", $ports_id);

      $this->assertEquals(1, $dbu->countElementsInTable('glpi_networkports'));
   }
}
