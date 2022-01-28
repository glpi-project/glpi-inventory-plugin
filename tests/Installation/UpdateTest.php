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

require_once("DatabaseTestsCommons.php");

use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
       // clean log files
        file_put_contents("../../files/_log/php-errors.log", '');
        file_put_contents("../../files/_log/sql-errors.log", '');
    }


    public static function tearDownAfterClass(): void
    {
       // Creation of folders if not created in tests
        if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory')) {
            mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory');
        }
        if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/tmp')) {
            mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/tmp');
        }
        if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/xml')) {
            mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/xml');
        }
        if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/xml/computer')) {
            mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/xml/computer');
        }
        if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/xml/printer')) {
            mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/xml/printer');
        }
        if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/xml/networkequipment')) {
            mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/xml/networkequipment');
        }
        if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/upload')) {
            mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/upload');
        }
        if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files')) {
            mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files');
        }
        if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/repository')) {
            mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/repository');
        }
        if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/manifests')) {
            mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/manifests');
        }
        if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/import')) {
            mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/import');
        }
        if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/export')) {
            mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/export');
        }
    }




   /**
    * @dataProvider provider
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    * @test
    */
    public function update($version = '', $verify = false, $nbrules = 0)
    {
        global $DB;

       // uninstall the plugin
        $plugin = new Plugin();
        $plugin->getFromDBByCrit(['directory' => 'glpiinventory']);
        $plugin->uninstall($plugin->fields['id']);

        $query = "SHOW TABLES";
        $result = $DB->query($query);
        while ($data = $DB->fetchArray($result)) {
            if (
                strstr($data[0], "tracker")
                || strstr($data[0], "fusi")
                || strstr($data[0], "glpiinventory")
            ) {
                $DB->query("DROP TABLE " . $data[0]);
            }
        }
        $query = "DELETE FROM `glpi_displaypreferences`
         WHERE `itemtype` LIKE 'PluginFus%' OR `itemtype` LIKE 'PluginGlpiinventory%'";
        $DB->queryOrDie($query);

       // Delete all plugin rules
        $query = "DELETE FROM " . Rule::getTable() . " WHERE sub_type LIKE 'Plugin%'";
        $DB->queryOrDie($query);

        $DB->query('DELETE FROM glpi_ruleactions WHERE id > 105');
        $DB->query('DELETE FROM glpi_rulecriterias WHERE id > 108');
        $DB->query('DELETE FROM glpi_rules WHERE id > 105');

        if ($version != '') {
            $sqlfile = "tests/Installation/mysql/i-" . $version . ".sql";
           // Load specific plugin version in database
            $result = $this->load_mysql_file(
                $DB->dbuser,
                $DB->dbhost,
                $DB->dbdefault,
                $DB->dbpassword,
                $sqlfile
            );
            $this->assertEquals(
                0,
                $result['returncode'],
                "Failed to install plugin " . $sqlfile . ":\n" .
                implode("\n", $result['output'])
            );

            $commandMy = "cd ../../ && php bin/console glpi:migration:myisam_to_innodb -n -q --config-dir=tests/config";
            $outputMy = [];
            $returncodeMy = 0;
            exec($commandMy, $outputMy, $returncodeMy);
            $this->assertEquals(
                0,
                $returncodeMy,
                sprintf("Result code from glpi:migration:myisam_to_innodb was '%s'.\n%s", $returncodeMy, implode("\n", $outputMy))
            );
        }
        $outputInstall = [];
        $returncodeInstall = 0;
        $commandInstall = "cd ../../ && php bin/console glpi:plugin:install -n -q --config-dir=tests/config --username=glpi glpiinventory";
        exec($commandInstall, $outputInstall, $returncodeInstall);
        $this->assertEquals(
            0,
            $returncodeInstall,
            sprintf("Result code from glpi:plugin:install was '%s'.\n%s", $returncodeInstall, implode("\n", $outputInstall))
        );

        $outputActivate     = [];
        $returncodeActivate = 0;
        $commandActivate = "cd ../../ && php bin/console glpi:plugin:activate -n -q --config-dir=tests/config glpiinventory";
        exec($commandActivate, $outputActivate, $returncodeActivate);
        $this->assertEquals(
            0,
            $returncodeActivate,
            sprintf("Result code from glpi:plugin:activate was '%s'.\n%s", $returncodeActivate, implode("\n", $outputActivate))
        );

        $GLPIlog = new GLPIlogs();
        $GLPIlog->testSQLlogs();
        $GLPIlog->testPHPlogs();

        $DatabaseTestsCommons = new DatabaseTestsCommons();
        $DatabaseTestsCommons->checkInstall("glpiinventory", "upgrade from " . $version);

        $this->verifyEntityRules($nbrules);
        $this->checkDeployMirrors();

        if ($verify) {
            $this->verifyConfig();
        }
    }

    public function load_mysql_file($dbuser = '', $dbhost = '', $dbdefault = '', $dbpassword = '', $file = null)
    {
        if (!file_exists($file)) {
            return [
            'returncode' => 1,
            'output' => ["ERROR: File '{$file}' does not exist !"]
            ];
        }

        $result = $this->construct_mysql_options($dbuser, $dbhost, $dbpassword, 'mysql');

        if (is_array($result)) {
            return $result;
        }

        $cmd = $result . " " . $dbdefault . " < " . $file . " 2>&1";

        $returncode = 0;
        $output = [];
        exec(
            $cmd,
            $output,
            $returncode
        );
        array_unshift($output, "Output of '{$cmd}'");
        return [
         'returncode' => $returncode,
         'output' => $output
        ];
    }


    public function construct_mysql_options($dbuser = '', $dbhost = '', $dbpassword = '', $cmd_base = 'mysql')
    {
        $cmd = [];

        if (empty($dbuser) || empty($dbhost)) {
            return [
            'returncode' => 2,
            'output' => ["ERROR: missing mysql parameters (user='{$dbuser}', host='{$dbhost}')"]
            ];
        }
        $cmd = [$cmd_base];

        if (strpos($dbhost, ':') !== false) {
            $dbhost = explode(':', $dbhost);
            if (!empty($dbhost[0])) {
                $cmd[] = "--host " . $dbhost[0];
            }
            if (is_numeric($dbhost[1])) {
                $cmd[] = "--port " . $dbhost[1];
            } else {
               // The dbhost's second part is assumed to be a socket file if it is not numeric.
                $cmd[] = "--socket " . $dbhost[1];
            }
        } else {
            $cmd[] = "--host " . $dbhost;
        }

        $cmd[] = "--user " . $dbuser;

        if (!empty($dbpassword)) {
            $cmd[] = "-p'" . urldecode($dbpassword) . "'";
        }
        return implode(' ', $cmd);
    }


    public function provider()
    {
       // version, verifyConfig, nb entity rules
        return [
         '0.83+2.1'     => ["0.83+2.1", true, 1],
         /*'9.5+3.0'     => ["9.5+3.0", true, 1],*/
         'empty tables' => ["", false, 0],
        ];
    }


    private function verifyEntityRules($nbrules = 0)
    {
        global $DB;

        $DB->connect();

        if ($nbrules == 0) {
            return;
        }

        $cnt_old = countElementsInTable(
            "glpi_rules",
            ['sub_type' => 'PluginFusinvinventoryRuleEntity']
        );

        $this->assertEquals(0, $cnt_old, "May not have entity rules with old itemtype name");

        $cnt_new = countElementsInTable(
            "glpi_rules",
            ['sub_type' => 'PluginGlpiinventoryInventoryRuleEntity']
        );

        $this->assertEquals($nbrules, $cnt_new, "May have " . $nbrules . " entity rules");
    }


    private function verifyConfig()
    {
        global $DB;
        $DB->connect();

        $a_configs = getAllDataFromTable(
            'glpi_plugin_glpiinventory_configs',
            ['type' => 'states_id_default']
        );

        $this->assertEquals(1, count($a_configs), "May have conf states_id_default");

        $a_config = current($a_configs);
        $this->assertEquals(1, $a_config['value'], "May keep states_id_default to 1");
    }


    private function checkDeployMirrors()
    {
        global $DB;

       //check is the field is_active has correctly been added to mirror servers
        $this->assertTrue($DB->fieldExists(
            'glpi_plugin_glpiinventory_deploymirrors',
            'is_active'
        ));
    }
}
