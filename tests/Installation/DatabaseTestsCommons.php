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

use PHPUnit\Framework\Assert;

class DatabaseTestsCommons extends Assert
{
    public function checkInstall($pluginname = '', $when = '')
    {
        global $DB;

        if ($pluginname == '') {
            return;
        }

        $comparaisonSQLFile = "plugin_" . $pluginname . "-empty.sql";
       // See http://joefreeman.co.uk/blog/2009/07/php-script-to-compare-mysql-database-schemas/

        $file_content = file_get_contents(GLPI_ROOT . "/plugins/" . $pluginname . "/install/mysql/" . $comparaisonSQLFile);
        $a_lines = explode("\n", $file_content);

        $a_tables_ref = [];
        $current_table = '';
        foreach ($a_lines as $line) {
            if (
                strstr($line, "CREATE TABLE ")
                 or strstr($line, "CREATE VIEW")
            ) {
                $matches = [];
                preg_match("/`(.*)`/", $line, $matches);
                $current_table = $matches[1];
            } else {
                if (preg_match("/^`/", trim($line))) {
                    $s_line = explode("`", $line);
                    $s_type = explode("COMMENT", $s_line[2]);
                    $s_type[0] = trim($s_type[0]);

                    $s_type[0] = preg_replace(
                        [
                            '/ COLLATE utf8(mb3|mb4)?_unicode_ci/',
                            '/ CHARACTER SET utf8(mb3|mb4)?/'
                        ],
                        [
                            '',
                            ''
                        ],
                        $s_type[0]
                    );

                    $column_replacements = [
                     // Remove comments
                        '/ COMMENT \'.+\'/i' => '',
                     // Remove integer display width
                        '/((tiny|small|medium|big)?int)\(\d+\)/i' => '$1',
                    ];
                    $s_type[0] = preg_replace(
                        array_keys($column_replacements),
                        array_values($column_replacements),
                        $s_type[0]
                    );

                    $a_tables_ref[$current_table][$s_line[1]] = str_replace(",", "", $s_type[0]);
                }
            }
        }

       // * Get tables from MySQL
        $a_tables_db = [];
        $a_tables = [];
       // SHOW TABLES;
        $query = "SHOW TABLES";
        $result = $DB->doQuery($query);
        while ($data = $DB->fetchArray($result)) {
            if (
                (strstr($data[0], "tracker")
                or strstr($data[0], 'glpiinventory')
                or strstr($data[0], 'fusioninventory')
                or strstr($data[0], 'fusinvinventory')
                or strstr($data[0], 'fusinvsnmp')
                or strstr($data[0], 'fusinvdeploy'))
                and (!strstr($data[0], "glpi_plugin_glpiinventory_pcidevices"))
                and (!strstr($data[0], "glpi_plugin_glpiinventory_pcivendors"))
                and (!strstr($data[0], "glpi_plugin_glpiinventory_ouis"))
                and (!strstr($data[0], "glpi_plugin_glpiinventory_usbdevices"))
                and (!strstr($data[0], "glpi_plugin_glpiinventory_usbvendors"))
            ) {
                $data[0] = preg_replace(
                    [
                        '/ COLLATE utf8(mb3|mb4)?_unicode_ci/',
                        '/ CHARACTER SET utf8(mb3|mb4)?/'
                    ],
                    [
                        '',
                        '',
                    ],
                    $data[0]
                );
                $data[0] = str_replace(
                    [
                        '( ',
                        ' )'
                    ],
                    [
                        '(',
                        ')'
                    ],
                    $data[0]
                );

                $a_tables[] = $data[0];
            }
        }

        foreach ($a_tables as $table) {
            $query = "SHOW CREATE TABLE " . $table;
            $result = $DB->doQuery($query);
            while ($data = $DB->fetchArray($result)) {
                $a_lines = explode("\n", $data['Create Table']);

                foreach ($a_lines as $line) {
                    if (
                        strstr($line, "CREATE TABLE ")
                        or strstr($line, "CREATE VIEW")
                    ) {
                        $matches = [];
                        preg_match("/`(.*)`/", $line, $matches);
                        $current_table = $matches[1];
                    } else {
                        if (preg_match("/^`/", trim($line))) {
                            $s_line = explode("`", $line);
                            $s_type = explode("COMMENT", $s_line[2]);
                            $s_type[0] = preg_replace(
                                [
                                    '/ COLLATE utf8(mb3|mb4)?_unicode_ci/',
                                    '/ CHARACTER SET utf8(mb3|mb4)?/'
                                ],
                                [
                                    '',
                                    ''
                                ],
                                trim($s_type[0])
                            );
                            $s_type[0] = str_replace(
                                ',',
                                '',
                                trim($s_type[0])
                            );
                        // Keeping DATETIME from old DB is considered as OK as DATETIME to TIMESTAMP
                        // migration should be done using dedicated GLPI command
                            $s_type[0] = preg_replace(
                                ['/^datetime DEFAULT NULL$/',   '/^datetime NOT NULL(.*)$/'],
                                ['timestamp NULL DEFAULT NULL', 'timestamp NOT NULL$1'],
                                $s_type[0]
                            );
                        //Mariadb 10.2 will return current_timestamp()
                        //while older returns CURRENT_TIMESTAMP...
                            $s_type[0] = preg_replace(
                                '/ CURRENT_TIMESTAMP$/',
                                ' CURRENT_TIMESTAMP()',
                                $s_type[0]
                            );
                        //Mariadb 10.2 allow default values on longblob
                        //while older returns CURRENT_TIMESTAMP...
                            $s_type[0] = preg_replace(
                                '/^longblob$/',
                                'longblob DEFAULT NULL',
                                $s_type[0]
                            );
                            if (
                                trim($s_type[0]) == 'text'
                                || trim($s_type[0]) == 'longtext'
                            ) {
                                  $s_type[0] .= ' DEFAULT NULL';
                            }

                            $column_replacements = [
                           // Remove comments
                                '/ COMMENT \'.+\'/i' => '',
                           // Remove integer display width
                                '/((tiny|small|medium|big)?int)\(\d+\)/i' => '$1',
                            ];
                            $s_type[0] = preg_replace(
                                array_keys($column_replacements),
                                array_values($column_replacements),
                                $s_type[0]
                            );

                            $s_type[0] = preg_replace("/(DEFAULT) ([-|+]?\d+)/", "$1 '$2'", $s_type[0]);
                            $a_tables_db[$current_table][$s_line[1]] = $s_type[0];
                        }
                    }
                }
            }
        }

        $a_tables_ref_tableonly = [];
        foreach ($a_tables_ref as $table => $data) {
            $a_tables_ref_tableonly[] = $table;
        }
        $a_tables_db_tableonly = [];
        foreach ($a_tables_db as $table => $data) {
            $a_tables_db_tableonly[] = $table;
        }

       // Compare
        $tables_toremove = array_diff($a_tables_db_tableonly, $a_tables_ref_tableonly);
        $tables_toadd = array_diff($a_tables_ref_tableonly, $a_tables_db_tableonly);

       // See tables missing or to delete
        $this->assertEquals(count($tables_toadd), 0, 'Tables missing ' . $when . ' ' . print_r($tables_toadd, true));
        $this->assertEquals(count($tables_toremove), 0, 'Tables to delete ' . $when . ' ' . print_r($tables_toremove, true));

       // See if fields are same
        foreach ($a_tables_db as $table => $data) {
            if (isset($a_tables_ref[$table])) {
                $fields_toremove = array_udiff_assoc($data, $a_tables_ref[$table], 'strcasecmp');
                $fields_toadd = array_udiff_assoc($a_tables_ref[$table], $data, 'strcasecmp');
                $diff = "======= DB ============== Ref =======> " . $table . "\n";
                $diff .= print_r($data, true);
                $diff .= print_r($a_tables_ref[$table], true);

               // See tables missing or to delete
                $this->assertEquals(count($fields_toadd), 0, 'Fields missing/not good in ' . $when . ' ' . $table . ' ' . print_r($fields_toadd, true) . " into " . $diff);
                $this->assertEquals(count($fields_toremove), 0, 'Fields to delete in ' . $when . ' ' . $table . ' ' . print_r($fields_toremove, true) . " into " . $diff);
            }
        }

        // Check if all modules registered
        $modules = [
            'INVENTORY',
            'InventoryComputerESX',
            'NETWORKINVENTORY',
            'NETWORKDISCOVERY',
            'DEPLOY',
            'Collect'
        ];
        foreach ($modules as $module) {
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM' => 'glpi_plugin_glpiinventory_agentmodules',
                'WHERE' => ['modulename' => $module]
            ]);
            $this->assertEquals(1, count($iterator), $module . ' module not registered');
        }

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM' => 'glpi_plugin_glpiinventory_agentmodules',
            'WHERE' => ['modulename' => 'ESX']
        ]);
        $this->assertEquals(0, count($iterator), 'ESX module may be renommed in InventoryComputerESX');

       /*
       * Verify in taskjob definition PluginFusinvsnmpIPRange not exist
       */
        $request = $DB->request([
            'FROM' => 'glpi_plugin_glpiinventory_taskjobs'
        ]);
        foreach ($request as $data) {
            $snmprangeip = 0;
            if (strstr($data['targets'], "PluginFusinvsnmpIPRange")) {
                $snmprangeip = 1;
            }
            $this->assertEquals($snmprangeip, 0, 'Have some "PluginFusinvsnmpIPRange" items in taskjob definition');
        }

       /*
       * Verify cron created
       */
        $crontask = new CronTask();
        $crons = [
            'taskscheduler' => 'PluginGlpiinventoryTask',
            'cleantaskjob' => 'PluginGlpiinventoryTaskjobstate',
            'wakeupAgents' => 'PluginGlpiinventoryAgentWakeup',
            'cleanondemand' => 'PluginGlpiinventoryTask'
        ];
        foreach ($crons as $cron => $class) {
            $this->assertTrue(
                $crontask->getFromDBbyName($class, $cron),
                'Cron ' . $cron . ' not created'
            );
        }

       /*
        * Verify config fields added
        */
        $plugin = new Plugin();
        $data = $plugin->find(['directory' => 'glpiinventory']);
        $plugins_id = 0;
        if (count($data)) {
            $fields = current($data);
            $plugins_id = $fields['id'];
        }

        $configs = [
            'ssl_only',
            'delete_task',
            'agent_port',
            'extradebug',
            'users_id',
            'version',
            'otherserial'
        ];
        foreach ($configs as $config) {
            $iterator = $DB->request([
                'SELECT' => ['id', 'value'],
                'FROM' => 'glpi_plugin_glpiinventory_configs',
                'WHERE' => ['type' => $config]
            ]);
            $this->assertEquals(1, count($iterator), 'type ' . $config . ' not added in config');

            if ($config === 'version') {
                $data = $iterator->current();
                $this->assertEquals(
                    PLUGIN_GLPIINVENTORY_VERSION,
                    $data['value'],
                    "Field 'version' not with right version"
                );
            }
        }

        // TODO : test glpi_displaypreferences, rules, SavedSearch...

        // Verify table `glpi_plugin_glpiinventory_inventorycomputerstats` filed with data
        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM' => 'glpi_plugin_glpiinventory_inventorycomputerstats'
        ]);
        $this->assertEquals(8760, count($iterator), 'Must have table `glpi_plugin_glpiinventory_inventorycomputerstats` not empty');
    }
}
