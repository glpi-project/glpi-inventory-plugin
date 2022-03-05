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

class RuleImportTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
       // Reinit rules
        \RuleImportAsset::initRules();
    }

    public static function tearDownAfterClass(): void
    {
       // Reinit rules
        \RuleImportAsset::initRules();
    }

    public function setUp(): void
    {
       // Delete all printers
        $printer = new Printer();
        $items = $printer->find();
        foreach ($items as $item) {
            $printer->delete(['id' => $item['id']], true);
        }
    }


   /**
    * @test
    */
    public function changeRulesForPrinterRules()
    {

        $rule = new Rule();
       // Add a rule test check model
        $input = [
         'is_active' => 1,
         'name'      => 'Printer model',
         'match'     => 'AND',
         'sub_type'  => \RuleImportAsset::class,
         'ranking'   => 1,
        ];
        $rule_id = $rule->add($input);
        $this->assertNotFalse($rule_id);

       // Add criteria
        $rulecriteria = new RuleCriteria();
        $input = [
         'rules_id'  => $rule_id,
         'criteria'  => 'serial',
         'pattern'   => '1',
         'condition' => \RuleImportAsset::PATTERN_FIND
        ];
        $ret = $rulecriteria->add($input);
        $this->assertNotFalse($ret);

       // Add action
        $ruleaction = new RuleAction();
        $input = [
         'rules_id'    => $rule_id,
         'action_type' => 'assign',
         'field'       => '_inventory',
         'value'       => \RuleImportAsset::RULE_ACTION_LINK_OR_IMPORT
        ];
        $ret = $ruleaction->add($input);
        $this->assertNotFalse($ret);

       // Denied import
        $input = [
         'is_active' => 1,
         'name'      => 'Deny printer import',
         'match'     => 'AND',
         'sub_type'  => \RuleImportAsset::class,
         'ranking'   => 3,
        ];
        $rule_id = $rule->add($input);
        $this->assertNotFalse($rule_id);

       // Add criteria
        $input = [
         'rules_id'  => $rule_id,
         'criteria'  => 'name',
         'pattern'   => '0',
         'condition' => \RuleImportAsset::PATTERN_EXISTS
        ];
        $ret = $rulecriteria->add($input);
        $this->assertNotFalse($ret);

       // Add action
        $input = [
         'rules_id'    => $rule_id,
         'action_type' => 'assign',
         'field'       => '_inventory',
         'value'       => \RuleImportAsset::RULE_ACTION_DENIED
        ];
        $ret = $ruleaction->add($input);
        $this->assertNotFalse($ret);
    }


   /**
    * @test
    */
    public function PrinterDiscoveryImport()
    {
        $this->changeRulesForPrinterRules();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <AUTHSNMP>1</AUTHSNMP>
      <DESCRIPTION>Brother NC-6400h, Firmware Ver.1.11  (06.12.20),MID 84UZ92</DESCRIPTION>
      <SNMPHOSTNAME>UH4DLPT01</SNMPHOSTNAME>
      <NETBIOSNAME>UH4DLPT01</NETBIOSNAME>
      <ENTITY>0</ENTITY>
      <IP>10.36.4.29</IP>
      <MAC>00:80:77:d9:51:c3</MAC>
      <MANUFACTURER>Brother</MANUFACTURER>
      <SERIAL>E8J596100</SERIAL>
      <TYPE>PRINTER</TYPE>
    </DEVICE>
    <MODULEVERSION>4.2</MODULEVERSION>
    <PROCESSNUMBER>85</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>NETDISCOVERY</QUERY>
</REQUEST>
";

        $printer = new Printer();
        $this->assertNotFalse(
            $printer->add([
            'entities_id' => '0',
            'serial'      => 'E8J596100'
            ])
        );

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml_source);
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $_SESSION['plugin_glpiinventory_taskjoblog']['taskjobs_id'] = 1;
        $_SESSION['plugin_glpiinventory_taskjoblog']['items_id']    = '1';
        $_SESSION['plugin_glpiinventory_taskjoblog']['itemtype']    = 'Printer';
        $_SESSION['plugin_glpiinventory_taskjoblog']['state']       = 0;
        $_SESSION['plugin_glpiinventory_taskjoblog']['comment']     = '';

       /*$pfCommunicationNetworkDiscovery->sendCriteria($a_inventory);*/

        $a_printers = $printer->find();
        $this->assertEquals(1, count($a_printers), 'May have only one Printer');

        $a_printer = current($a_printers);
        $this->assertEquals('UH4DLPT01', $a_printer['name'], 'Hostname of printer may be updated');
    }


   /**
    * @test
    */
    public function PrinterDiscoveryImportDenied()
    {
        $this->changeRulesForPrinterRules();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <AUTHSNMP>1</AUTHSNMP>
      <DESCRIPTION>Brother NC-6400h, Firmware Ver.1.11  (06.12.20),MID 84UZ92</DESCRIPTION>
      <SNMPHOSTNAME>UH4DLPT01</SNMPHOSTNAME>
      <NETBIOSNAME>UH4DLPT01</NETBIOSNAME>
      <ENTITY>0</ENTITY>
      <IP>10.36.4.29</IP>
      <MAC>00:80:77:d9:51:c3</MAC>
      <MANUFACTURER>Brother</MANUFACTURER>
      <SERIAL>E8J596100A</SERIAL>
      <TYPE>PRINTER</TYPE>
    </DEVICE>
    <MODULEVERSION>4.2</MODULEVERSION>
    <PROCESSNUMBER>85</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>NETDISCOVERY</QUERY>
</REQUEST>
";
        $printer = new Printer();
        $a_printers = $printer->find();
        $this->assertEquals(0, count($a_printers), 'There should be no printer');

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml_source);
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $_SESSION['plugin_glpiinventory_taskjoblog']['taskjobs_id'] = 1;
        $_SESSION['plugin_glpiinventory_taskjoblog']['items_id']    = '1';
        $_SESSION['plugin_glpiinventory_taskjoblog']['itemtype']    = 'Printer';
        $_SESSION['plugin_glpiinventory_taskjoblog']['state']       = 0;
        $_SESSION['plugin_glpiinventory_taskjoblog']['comment']     = '';
        $a_printers = $printer->find();
        $this->assertEquals(0, count($a_printers), 'May have no Printer');

       /* task is squeezed :/
       $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
       $a_logs = $pfTaskjoblog->find(['comment' => ['LIKE', '%importdenied%']], ['id DESC'], 1);
       $a_log = current($a_logs);
       $this->assertEquals('==importdenied== [serial]:E8J596100A, '.
              '[mac]:00:80:77:d9:51:c3, [ip]:10.36.4.29, [model]:Printer0442, '.
              '[name]:UH4DLPT01, [entities_id]:0, [itemtype]:Printer',
              $a_log['comment'], 'Import denied message');*/
    }
}
