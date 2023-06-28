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

use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Item as Dashboard_Item;
use Glpi\Dashboard\Right as Dashboard_Right;
use Ramsey\Uuid\Uuid;

include_once(PLUGIN_GLPI_INVENTORY_DIR . "/install/update.tasks.php");

/**
 * Get the current version of the plugin
 *
 * @global object $DB
 * @return string
 */
function pluginGlpiinventoryGetCurrentVersion()
{
    global $DB;

    require_once(PLUGIN_GLPI_INVENTORY_DIR . "/inc/module.class.php");

    if (
        (!$DB->tableExists("glpi_plugin_tracker_config")) &&
        (!$DB->tableExists("glpi_plugin_fusioninventory_config")) &&
        (!$DB->tableExists("glpi_plugin_fusioninventory_configs")) &&
        (!$DB->tableExists("glpi_plugin_glpiinventory_configs"))
    ) {
        return '0';
    } elseif (
        ($DB->tableExists("glpi_plugin_tracker_config")) ||
         ($DB->tableExists("glpi_plugin_glpiinventory_config"))
    ) {
        if ($DB->tableExists("glpi_plugin_glpiinventory_configs")) {
            $iterator = $DB->request([
            'FROM'   => 'glpi_plugin_glpiinventory_configs',
            'WHERE'  => ['type' => 'version'],
            'LIMIT'  => 1
            ]);

            $data = [];
            if (count($iterator)) {
                 $data = $iterator->current();
                 return $data['value'];
            }
        }

        if (
            (!$DB->tableExists("glpi_plugin_tracker_agents")) &&
            (!$DB->tableExists("glpi_plugin_fusioninventory_agents"))
        ) {
            return "1.1.0";
        }
        if (
            (!$DB->tableExists("glpi_plugin_tracker_config_discovery")) &&
            (!$DB->tableExists("glpi_plugin_fusioninventory_config"))
        ) {
            return "2.0.0";
        }
        if (
            (($DB->tableExists("glpi_plugin_tracker_agents")) &&
            (!$DB->fieldExists("glpi_plugin_tracker_config", "version"))) &&
            (!$DB->tableExists("glpi_plugin_fusioninventory_config"))
        ) {
            return "2.0.1";
        }
        if (
            (($DB->tableExists("glpi_plugin_tracker_agents")) &&
            ($DB->fieldExists("glpi_plugin_tracker_config", "version"))) ||
            ($DB->tableExists("glpi_plugin_fusioninventory_config"))
        ) {
            $querytable = 'glpi_plugin_fusioninventory_config';
            if ($DB->tableExists("glpi_plugin_tracker_agents")) {
                $querytable = 'glpi_plugin_tracker_config';
            }

            $iterator = $DB->request([
            'SELECT' => ['version'],
            'FROM'   => $querytable,
            'LIMIT'  => 1
            ]);

            $data = [];
            if (count($iterator)) {
                $data = $iterator->current();
            }

            if ($data['version'] == "0") {
                return "2.0.2";
            } else {
                return $data['version'];
            }
        }
    } elseif ($DB->tableExists("glpi_plugin_fusioninventory_configs")) {
        $iterator = $DB->request([
         'SELECT' => ['value'],
         'FROM'   => 'glpi_plugin_fusioninventory_configs',
         'WHERE'  => ['type' => 'version'],
         'LIMIT'  => 1
        ]);

        $data = [];
        if (count($iterator)) {
            $data = $iterator->current();
            return $data['value'];
        }
        if ($DB->fieldExists('glpi_plugin_fusioninventory_agentmodules', 'plugins_id')) {
            $iterator = $DB->request([
            'SELECT' => ['plugins_id'],
            'FROM'   => 'glpi_plugin_fusioninventory_agentmodules',
            'WHERE'  => ['modulename' => 'WAKEONLAN'],
            'LIMIT'  => 1
            ]);
            if (count($iterator)) {
                $ex_pluginid = $iterator->current();

                $DB->update(
                    'glpi_plugin_fusioninventory_taskjobs',
                    [
                    'plugins_id'   => PluginGlpiinventoryModule::getModuleId('fusioninventory')
                    ],
                    [
                    'plugins_id'   => $ex_pluginid['plugins_id']
                    ]
                );

                 $DB->update(
                     'glpi_plugin_fusioninventory_profiles',
                     [
                     'plugins_id'   => PluginGlpiinventoryModule::getModuleId('fusioninventory')
                     ],
                     [
                     'plugins_id'   => $ex_pluginid['plugins_id']
                     ]
                 );

                 $DB->update(
                     'glpi_plugin_fusioninventory_agentmodules',
                     [
                     'plugins_id'   => PluginGlpiinventoryModule::getModuleId('fusioninventory')
                     ],
                     [
                     'plugins_id'   => $ex_pluginid['plugins_id']
                     ]
                 );

                 $iterator = $DB->request([
                   'SELECT' => ['value'],
                   'FROM'   => 'glpi_plugin_fusioninventory_configs',
                   'WHERE'  => ['type' => 'version'],
                   'LIMIT'  => 1
                 ]);

                 $data = [];
                if (count($iterator)) {
                     $data = $iterator->current();
                     return $data['value'];
                }
            }
        }
    } elseif ($DB->tableExists("glpi_plugin_glpiinventory_configs")) {
        $iterator = $DB->request([
         'SELECT' => ['value'],
         'FROM'   => 'glpi_plugin_glpiinventory_configs',
         'WHERE'  => ['type' => 'version'],
         'LIMIT'  => 1
        ]);

        $data = [];
        if (count($iterator)) {
            $data = $iterator->current();
            return $data['value'];
        }
        if ($DB->fieldExists('glpi_plugin_glpiinventory_agentmodules', 'plugins_id')) {
            $iterator = $DB->request([
            'SELECT' => ['plugins_id'],
            'FROM'   => 'glpi_plugin_glpiinventory_agentmodules',
            'WHERE'  => ['modulename' => 'WAKEONLAN'],
            'LIMIT'  => 1
            ]);
            if (count($iterator)) {
                $ex_pluginid = $iterator->current();

                $DB->update(
                    'glpi_plugin_glpiinventory_taskjobs',
                    [
                        'plugins_id'   => PluginGlpiinventoryModule::getModuleId('glpiinventory')
                    ],
                    [
                        'plugins_id'   => $ex_pluginid['plugins_id']
                    ]
                );

                 $DB->update(
                     'glpi_plugin_glpiinventory_profiles',
                     [
                        'plugins_id'   => PluginGlpiinventoryModule::getModuleId('glpiinventory')
                     ],
                     [
                        'plugins_id'   => $ex_pluginid['plugins_id']
                     ]
                 );

                 $DB->update(
                     'glpi_plugin_glpiinventory_agentmodules',
                     [
                     'plugins_id'   => PluginGlpiinventoryModule::getModuleId('glpiinventory')
                     ],
                     [
                     'plugins_id'   => $ex_pluginid['plugins_id']
                     ]
                 );

                 $iterator = $DB->request([
                   'SELECT' => ['value'],
                   'FROM'   => 'glpi_plugin_glpiinventory_configs',
                   'WHERE'  => ['type' => 'version'],
                   'LIMIT'  => 1
                 ]);

                 $data = [];
                if (count($iterator)) {
                     $data = $iterator->current();
                     return $data['value'];
                }
            }
        }
    }
    return "1.1.0";
}

/**
 * The main function to update the plugin
 *
 * @global object $DB
 * @param string $current_version
 * @param string $migrationname
 */
function pluginGlpiinventoryUpdate($current_version, $migrationname = 'Migration')
{
    global $DB;

    $DB->disableTableCaching();

    ini_set("max_execution_time", "0");
    ini_set("memory_limit", "-1");

    $migration = new $migrationname($current_version);
    $prepare_task = [];
    $prepare_rangeip = [];
    $prepare_Config = [];

    $a_plugin = plugin_version_glpiinventory();
    $plugins_id = PluginGlpiinventoryModule::getModuleId($a_plugin['shortname']);

    $migration->displayMessage("Migration Classname : " . $migrationname);
    $migration->displayMessage("Update of plugin GLPI Inventory");

    $plugin_doc_dir = GLPI_PLUGIN_DOC_DIR . '/glpiinventory';
    $sub_directories = [
        'tmp'               => false,
        'files'             => false,
        'files/import'      => true,
        'files/export'      => true,
        'files/manifests'   => true,
        'files/repository'  => true,
        'upload'            => true,
    ];

    // ********* Ensure plugin directories are existing ********************** //
    if (!is_dir($plugin_doc_dir)) {
        mkdir($plugin_doc_dir);
    }
    foreach (array_keys($sub_directories) as $sub_directory) {
        $directory_full_path = $plugin_doc_dir . '/' . $sub_directory;
        if (!is_dir($directory_full_path)) {
            mkdir($directory_full_path);
        }
    }

    // ********* Copy files from FusionInventory ***************************** //
    foreach ($sub_directories as $sub_directory => $copy) {
        if (!$copy) {
            continue;
        }
        $directory_full_path = $plugin_doc_dir . '/' . $sub_directory;
        foreach (['fusinvdeploy', 'fusioninventory'] as $fi_plugin_directory) {
            $fi_directory_full_path = GLPI_PLUGIN_DOC_DIR . '/' . $fi_plugin_directory . '/' . $sub_directory;
            if (!is_dir($fi_directory_full_path)) {
                continue;
            }
            $files_iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fi_directory_full_path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($files_iterator as $file) {
                $dest_path = $directory_full_path . '/' . $files_iterator->getSubPathname();
                if (file_exists($dest_path)) {
                    continue; // Do not overwrite files (file has probably been copied by a previous migration)
                }
                if ($file->isDir()) {
                    mkdir($dest_path);
                } else {
                    copy($file->getRealPath(), $dest_path);
                }
            }
        }
    }

   // ********* Rename fileparts without .gz extension (cf #1999) *********** //
    if (is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files')) {
        $gzfiles = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files')
            ),
            '/\.gz$/'
        );

        foreach ($gzfiles as $gzfile) {
            $name = $gzfile->getRealPath();
            rename($name, str_replace('.' . $gzfile->getExtension(), '', $name));
        }
        unset($gzfiles);
    }

    // Drop unused views
    // Have to be done prior to renamePlugin() to prevent following warning:
    // "View 'glpi.glpi_plugin_fusinvdeploy_taskjobs' references invalid table(s) or column(s) or function(s) or definer/invoker of view lack rights to use them"
    $old_deploy_views = [
      'glpi_plugin_fusinvdeploy_taskjobs',
      'glpi_plugin_fusinvdeploy_tasks'
    ];
    foreach ($old_deploy_views as $view) {
        $DB->query("DROP VIEW IF EXISTS $view");
    }

    renamePlugin($migration);

   // conversion in very old version
    update213to220_ConvertField($migration);

   // ********* Migration internal / common ********************************* //

      // ********* Rename tables ******************************************** //
      $migration->renameTable(
          "glpi_plugin_glpiinventory_lock",
          "glpi_plugin_glpiinventory_locks"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_unknown_device",
          "glpi_plugin_glpiinventory_unknowndevices"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_config",
          "glpi_plugin_glpiinventory_configs"
      );

      // ********* Migration ************************************************ //
      $prepare_rangeip = do_agent_migration($migration);
      $prepare_Config  = do_config_migration($migration);
      do_entities_migration($migration);
      do_locks_migration($migration);
      do_profile_migration($migration);
      do_ignoredimport_migration($migration);
      do_rulematchedlog_migration($migration);
      do_unmanaged_migration($migration);

   // ********* Migration Computer inventory ******************************** //

      // ********* Rename tables ******************************************** //

      // ********* Migration ************************************************ //
      do_blacklist_migration($migration);
      do_antivirus_migration($migration);
      do_computercomputer_migration($migration);
      do_computerstat_migration($migration);
      do_computerlicense_migration($migration);
      do_computerremotemgmt_migration($migration);
      do_computerarch_migration($migration);
      do_computeroperatingsystem_migration($migration);
      do_dblocks_migration($migration);
      do_rule_migration($migration);
      do_task_migration($migration);

   // ********* Migration SNMP discovery and inventory ********************** //

      // ********* Rename tables ******************************************** //
      $migration->renameTable(
          "glpi_plugin_glpiinventory_rangeip",
          "glpi_plugin_glpiinventory_ipranges"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_networking_ports",
          "glpi_plugin_fusinvsnmp_networkports"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_construct_device",
          "glpi_plugin_fusinvsnmp_constructdevices"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_construct_mibs",
          "glpi_plugin_glpiinventory_snmpmodelconstructdevice_miboids"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_networking",
          "glpi_plugin_glpiinventory_networkequipments"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_networking_ifaddr",
          "glpi_plugin_fusinvsnmp_networkequipmentips"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_printers",
          "glpi_plugin_fusinvsnmp_printers"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_printers_cartridges",
          "glpi_plugin_fusinvsnmp_printercartridges"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_printers_history",
          "glpi_plugin_fusinvsnmp_printerlogs"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_model_infos",
          "glpi_plugin_glpiinventory_snmpmodels"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_mib_networking",
          "glpi_plugin_fusinvsnmp_modelmibs"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_snmp_connection",
          "glpi_plugin_fusinvsnmp_configsecurities"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_snmp_history",
          "glpi_plugin_fusinvsnmp_networkportlogs"
      );
      $migration->renameTable(
          "glpi_plugin_glpiinventory_snmp_history_connections",
          "glpi_plugin_fusinvsnmp_networkportconnectionlogs"
      );

      // ********* Migration ************************************************ //
      $prepare_task = do_iprange_migration($migration);
      do_iprangeconfigsecurity_migration($migration);
      do_configlogfield_migration($migration);
      do_networkport_migration($migration);
      do_printer_migration($migration);
      do_networkequipment_migration($migration);
      do_configsecurity_migration($migration);
      do_statediscovery_migration($migration);
      do_snmpmodel_migration($migration);

   // ********* Migration deploy ******************************************** //

      // ********* Rename tables ******************************************** //

      // ********* Migration ************************************************ //
      do_deployfile_migration($migration);
      do_deploypackage_migration($migration);
      do_deploymirror_migration($migration);
      do_deploygroup_migration($migration);
      do_deployuserinteraction_migration($migration);
      migrateTablesFromFusinvDeploy($migration);

   // ********* Migration ESX *********************************************** //

      // ********* Rename tables ******************************************** //

      // ********* Migration ************************************************ //
      do_credentialESX_migration($migration);

   // ********* Migration Collect ******************************************* //

      // ********* Rename tables ******************************************** //

      // ********* Migration ************************************************ //
      do_collect_migration($migration);

   // ********* Migration Tasks ********************************************* //

      // ********* Rename tables ******************************************** //

      // ********* Migration ************************************************ //
      pluginGlpiinventoryUpdateTasks($migration, $plugins_id);
      do_timeslot_migration($migration);

   // ********* Drop tables not used **************************************** //

    $a_droptable = ['glpi_plugin_glpiinventory_agents_inventory_state',
                        'glpi_plugin_glpiinventory_config_modules',
                        'glpi_plugin_glpiinventory_connection_stats',
                        'glpi_plugin_glpiinventory_discovery',
                        'glpi_plugin_glpiinventory_errors',
                        'glpi_plugin_glpiinventory_lockable',
                        'glpi_plugin_glpiinventory_connection_history',
                        'glpi_plugin_glpiinventory_walks',
                        'glpi_plugin_glpiinventory_config_snmp_history',
                        'glpi_plugin_glpiinventory_config_snmp_networking',
                        'glpi_plugin_glpiinventory_task',
                        'glpi_plugin_fusinvinventory_pcidevices',
                        'glpi_plugin_fusinvinventory_pcivendors',
                        'glpi_plugin_fusinvinventory_usbdevices',
                        'glpi_plugin_fusinvinventory_usbvendors',
                        'glpi_plugin_fusinvsnmp_constructdevicewalks',
                        'glpi_plugin_glpiinventory_snmpmodelmiblabels',
                        'glpi_plugin_glpiinventory_snmpmodelmibobjects',
                        'glpi_plugin_glpiinventory_snmpmodelmiboids',
                        'glpi_plugin_glpiinventory_snmpmodelconstructdevices',
                        'glpi_plugin_glpiinventory_snmpmodelconstructdevicewalks' .
                        'glpi_plugin_glpiinventory_snmpmodelconstructdevices_users',
                        'glpi_plugin_glpiinventory_snmpmodelconstructdevice_miboids',
                        'glpi_plugin_glpiinventory_snmpmodelmibs',
                        'glpi_plugin_glpiinventory_snmpmodels',
                        'glpi_plugin_glpiinventory_snmpmodeldevices',
                        'glpi_plugin_fusinvsnmp_constructdevice_miboids',
                        'glpi_plugin_fusinvsnmp_constructdevices',
                        'glpi_plugin_fusinvsnmp_constructdevices_users',
                        'glpi_plugin_fusinvsnmp_miblabels',
                        'glpi_plugin_fusinvsnmp_mibobjects',
                        'glpi_plugin_fusinvsnmp_miboids',
                        'glpi_plugin_fusinvsnmp_modeldevices',
                        'glpi_plugin_fusinvsnmp_modelmibs',
                        'glpi_plugin_fusinvsnmp_models',
                        'glpi_plugin_glpiinventory_construct_walks',
                        'glpi_plugin_glpiinventory_deployorders',
                        'glpi_plugin_tracker_computers',
                        'glpi_plugin_tracker_connection_history',
                        'glpi_plugin_tracker_agents_processes',
                        'glpi_plugin_tracker_config_snmp_history',
                        'glpi_plugin_tracker_config_snmp_networking',
                        'glpi_plugin_tracker_config_snmp_printer',
                        'glpi_plugin_tracker_config_snmp_script',
                        'glpi_plugin_tracker_connection_stats',
                        'glpi_plugin_tracker_discovery',
                        'glpi_plugin_tracker_errors',
                        'glpi_plugin_tracker_model_infos',
                        'glpi_plugin_tracker_processes',
                        'glpi_plugin_tracker_processes_values',
                        'glpi_dropdown_plugin_tracker_snmp_auth_auth_protocol',
                        'glpi_dropdown_plugin_tracker_snmp_auth_priv_protocol',
                        'glpi_dropdown_plugin_tracker_snmp_auth_sec_level',
                        'glpi_dropdown_plugin_tracker_snmp_version',
                        'glpi_plugin_tracker_computers',
                        'glpi_plugin_tracker_config',
                        'glpi_plugin_tracker_config_discovery',
                        'glpi_plugin_tracker_tmp_connections',
                        'glpi_plugin_tracker_tmp_netports',
                        'glpi_plugin_tracker_walks',
                        'glpi_plugin_glpiinventory_agents_errors',
                        'glpi_plugin_glpiinventory_agents_processes',
                        'glpi_plugin_glpiinventory_computers',
                        'glpi_plugin_glpiinventory_config_snmp_networking',
                        'glpi_plugin_glpiinventory_config_snmp_history',
                        'glpi_plugin_fusinvsnmp_agentconfigs',
                        'glpi_dropdown_plugin_glpiinventory_mib_label',
                        'glpi_dropdown_plugin_glpiinventory_mib_object',
                        'glpi_dropdown_plugin_glpiinventory_mib_oid',
                        'glpi_dropdown_plugin_glpiinventory_snmp_auth_auth_protocol',
                        'glpi_dropdown_plugin_glpiinventory_snmp_auth_priv_protocol',
                        'glpi_dropdown_plugin_glpiinventory_snmp_version',
                        'glpi_plugin_fusinvsnmp_temp_profiles',
                        'glpi_plugin_fusinvsnmp_tmp_agents',
                        'glpi_plugin_fusinvsnmp_tmp_configs',
                        'glpi_plugin_fusinvsnmp_tmp_tasks',
                        'glpi_plugin_glpiinventory_networkequipmentips',
                        'glpi_plugin_glpiinventory_inventorycomputerbatteries',
                        'glpi_plugin_glpiinventory_inventorycomputerchemistries'
       ];

    foreach ($a_droptable as $newTable) {
        $migration->dropTable($newTable);
    }

    /*
      $a_table = array();

      //table name
      $a_table['name'] = '';
      $a_table['oldname'] = array(
      );

      // fields : fields that are new, have changed type or just stay the same
      //    array(
      //        <fieldname> = array(
      //            'type' => <type>, 'value' => <value>)
      //    );
      $a_table['fields'] = array(

      );

      // oldfields = fields that need to be removed
      //    array( 'field0', 'field1', ...);
      $a_table['oldfields'] = array(
      );

      // renamefields = fields that need to be renamed
      //    array('oldname' = 'newname', ...)
      $a_table['renamefields'] = array(
      );

      // keys : new, changed or not
      //    array( 'field' => <fields>, 'name' => <keyname> , 'type' => <keytype>)
      // <fields> : fieldnames needed by the key
      //            ex : array('field0' , 'field1' ...)
      //            ex : 'fieldname'
      // <keyname> : the name of the key (if blank, the fieldname is used)
      // <type> : the type of key (ex: INDEX, ...)
      $a_table['keys'] = array(
      );

      // oldkeys : keys that need to be removed
      //    array( 'key0', 'key1', ... )
      $a_table['oldkeys'] = array(
      );
   */

   //Push task functionnality
    $migration->addField('glpi_plugin_glpiinventory_tasks', 'last_agent_wakeup', 'timestamp');
    $migration->addField('glpi_plugin_glpiinventory_tasks', 'wakeup_agent_counter', "int NOT NULL DEFAULT '0'");
    $migration->addField('glpi_plugin_glpiinventory_tasks', 'wakeup_agent_time', "int NOT NULL DEFAULT '0'");
    $migration->addField('glpi_plugin_glpiinventory_tasks', 'reprepare_if_successful', "tinyint NOT NULL DEFAULT '1'");
    $deploy_on_demand = $migration->addField('glpi_plugin_glpiinventory_tasks', 'is_deploy_on_demand', "tinyint NOT NULL DEFAULT '0'");
    $migration->addKey('glpi_plugin_glpiinventory_tasks', 'wakeup_agent_counter');
    $migration->addKey('glpi_plugin_glpiinventory_tasks', 'reprepare_if_successful');
    $migration->addKey('glpi_plugin_glpiinventory_tasks', 'is_deploy_on_demand');
    $migration->migrationOneTable('glpi_plugin_glpiinventory_tasks');

   //deploy on demand task migration :
   //the way to detect a deploy on demand task was by looking at it's name
   //we've now introduced a boolean to easily check for it
    if ($deploy_on_demand) {
        $task = new PluginGlpiinventoryTask();
        foreach (
            getAllDataFromTable(
                'glpi_plugin_glpiinventory_tasks',
                ['name' => ['LIKE', '%[self-deploy]%']]
            ) as $tsk
        ) {
            $task->update(['id' => $tsk['id'], 'is_deploy_on_demand' => 1]);
        }
    }

   /*
    * Clean display preferences not used
    */
    $DB->delete(
        'glpi_displaypreferences',
        [
         'itemtype' => [
            '5150',
            '5160',
            '5161',
            '5163',
            '5165',
            '5190'
         ]
        ]
    );

   // If no PluginGlpiinventoryTaskjoblog in preferences, add them
    $iterator = $DB->request([
      'FROM'   => 'glpi_displaypreferences',
      'WHERE'  => [
         'itemtype'  => 'PluginGlpiinventoryTaskjoblog',
         'users_id'  => 0
      ]
    ]);
    if (!count($iterator)) {
        $insert = $DB->buildInsert(
            'glpi_displaypreferences',
            [
            'itemtype'  => 'PluginGlpiinventoryTaskjoblog',
            'num'       => new \QueryParam(),
            'rank'      => new \QueryParam(),
            'users_id'  => 0
            ]
        );

        $stmt = $DB->prepare($insert);
        $insert_data = [
         [2, 1],
         [3, 2],
         [4, 3],
         [5, 4],
         [6, 5],
         [7, 6],
         [8, 7]
        ];
        foreach ($insert_data as $idata) {
            $stmt->bind_param(
                'ss',
                $idata[0],
                $idata[1]
            );
            $DB->executeStatement($stmt);
        }
        mysqli_stmt_close($stmt);
    }

   /*
    * Convert taskjob definition from PluginFusinvsnmpIPRange to PluginGlpiinventoryIPRange
    * onvert taskjob definition from PluginFusinvdeployPackage to PluginGlpiinventoryDeployPackage
    */
    $iterator = $DB->request([
      'FROM' => 'glpi_plugin_glpiinventory_taskjobs'
    ]);
    if (count($iterator)) {
        $update = $DB->buildUpdate(
            'glpi_plugin_glpiinventory_taskjobs',
            [
                'targets'   => new \QueryParam()
            ],
            [
                'id'        => new \QueryParam()
            ]
        );
        $stmt = $DB->prepare($update);

        foreach ($iterator as $data) {
            $a_defs = importArrayFromDB($data['targets']);
            foreach ($a_defs as $num => $a_def) {
                if (in_array(key($a_def), ['PluginFusinvsnmpIPRange', 'PluginFusioninventoryIPRange'])) {
                    $a_defs[$num] = ['PluginGlpiinventoryIPRange' => current($a_def)];
                } elseif (in_array(key($a_def), ['PluginFusinvdeployPackage', 'PluginFusioninventoryDeployPackage'])) {
                    $a_defs[$num] = ['PluginGlpiinventoryDeployPackage' => current($a_def)];
                }
            }

            $targets = exportArrayToDB($a_defs);
            $stmt->bind_param(
                'ss',
                $targets,
                $data['id']
            );
            $DB->executeStatement($stmt);
        }

        mysqli_stmt_close($stmt);
    }

   /*
    * Convert taskjoblogs itemtype from PluginFusinvdeployPackage to
    * PluginGlpiinventoryDeployPackage
    */
    $DB->update(
        'glpi_plugin_glpiinventory_taskjoblogs',
        [
         'itemtype' => 'PluginGlpiinventoryDeployPackage'
        ],
        [
         'itemtype' => 'PluginFusinvdeployPackage'
        ]
    );

   /*
    * Convert taskjobstates itemtype from PluginFusinvdeployPackage to
    * PluginGlpiinventoryDeployPackage
    */
    $DB->update(
        'glpi_plugin_glpiinventory_taskjobstates',
        [
         'itemtype' => 'PluginGlpiinventoryDeployPackage'
        ],
        [
         'itemtype' => 'PluginFusinvdeployPackage'
        ]
    );

   /*
    * Convert taskjob action from PluginFusinvdeployGroup to PluginGlpiinventoryDeployGroup
    */
    $iterator = $DB->request(['FROM' => 'glpi_plugin_glpiinventory_taskjobs']);
    if (count($iterator)) {
        $update = $DB->buildUpdate(
            'glpi_plugin_glpiinventory_taskjobs',
            [
            'actors' => new \QueryParam()
            ],
            [
            'id'     => new \QueryParam()
            ]
        );
        $stmt = $DB->prepare($update);

        foreach ($iterator as $data) {
            $a_defs = importArrayFromDB($data['actors']);
            foreach ($a_defs as $num => $a_def) {
                if (key($a_def) == 'PluginFusinvdeployGroup') {
                    $a_defs[$num] = ['PluginGlpiinventoryDeployGroup' => current($a_def)];
                }
            }

            $actors = exportArrayToDB($a_defs);
            $stmt->bind_param(
                'ss',
                $actors,
                $data['id']
            );
            $DB->executeStatement($stmt);
        }

        mysqli_stmt_close($stmt);
    }

   /*
    * Convert taskjob method deployuninstall in deployinstall
    */
    $DB->update(
        'glpi_plugin_glpiinventory_taskjobs',
        [
         'method' => 'deployinstall'
        ],
        [
         'method' => 'deployuninstall'
        ]
    );

   /*
    *  Manage configuration of plugin
    */
    $config = new PluginGlpiinventoryConfig();
    $pfSetup = new PluginGlpiinventorySetup();
    $users_id = $pfSetup->createGlpiInventoryUser();
    $a_input = [];
    $a_input['ssl_only'] = 0;
    $a_input['delete_task'] = 20;
    $a_input['agent_port'] = 62354;
    $a_input['extradebug'] = 0;
    $a_input['users_id'] = $users_id;
    $config->addValues($a_input, false);

    $a_input = [];
    $a_input['version'] = PLUGIN_GLPIINVENTORY_VERSION;
    $config->addValues($a_input, true);
    $a_input = [];
    $a_input['ssl_only'] = 0;
    if (isset($prepare_Config['ssl_only'])) {
        $a_input['ssl_only'] = $prepare_Config['ssl_only'];
    }
    $a_input['delete_task'] = 20;
    $a_input['agent_port'] = 62354;
    $a_input['extradebug'] = 0;
    $a_input['users_id'] = 0;

   //Deploy configuration options
    $a_input['server_upload_path'] =
         Toolbox::addslashes_deep(
             implode(
                 DIRECTORY_SEPARATOR,
                 [
                  GLPI_PLUGIN_DOC_DIR,
                  'glpiinventory',
                  'upload'
                 ]
             )
         );
    $a_input['alert_winpath']    = 1;
    $a_input['server_as_mirror'] = 1;
    $a_input['mirror_match']     = 0;
    $config->addValues($a_input, false);

    $pfSetup = new PluginGlpiinventorySetup();
    $users_id = $pfSetup->createGlpiInventoryUser();

    $DB->update(
        'glpi_plugin_glpiinventory_configs',
        [
         'value'  => $users_id
        ],
        [
         'type'   => 'users_id'
        ]
    );

   // Update fusinvinventory _config values to this plugin
    $input = [
      'import_software'                => 1,
      'import_volume'                  => 1,
      'import_antivirus'               => 1,
      'import_registry'                => 1,
      'import_process'                 => 1,
      'import_vm'                      => 1,
      'import_monitor_on_partial_sn'   => 0,
      'component_processor'            => 1,
      'component_memory'               => 1,
      'component_harddrive'            => 1,
      'component_networkcard'          => 1,
      'component_graphiccard'          => 1,
      'component_soundcard'            => 1,
      'component_drive'                => 1,
      'component_networkdrive'         => 1,
      'component_control'              => 1,
      'component_battery'              => 1,
      'component_powersupply'          => 1,
      'states_id_default'              => 0,
      'location'                       => 0,
      'group'                          => 0,
      'manage_osname'                  => 0,
      'component_networkcardvirtual'   => 1,
      'reprepare_job'                  => 0
    ];
    $config->addValues($input, false);

   // Add new config values if not added
    $input = $config->initConfigModule(true);
    foreach ($input as $name => $value) {
        $a_conf = $config->find(['type' => $name]);
        if (count($a_conf) == 0) {
            $config->add(['type' => $name, 'value' => $value]);
        }
    }

    $migration->displayMessage("Add Crontasks");
   /*
    * Add Crontask if not exist
    */
    $crontask = new CronTask();
    if ($crontask->getFromDBbyName('PluginGlpiinventoryTaskjob', 'taskscheduler')) {
        $crontask->fields['itemtype'] = 'PluginGlpiinventoryTask';
        $crontask->updateInDB(['itemtype']);
    }
    if (!$crontask->getFromDBbyName('PluginGlpiinventoryTask', 'taskscheduler')) {
        CronTask::Register(
            'PluginGlpiinventoryTask',
            'taskscheduler',
            '60',
            ['mode' => 2, 'allowmode' => 3, 'logs_lifetime' => 30]
        );
    }
    if (
        $crontask->getFromDBbyName('PluginGlpiinventoryTaskjobstate', 'cleantaskjob')
           and $crontask->getFromDBbyName('PluginGlpiinventoryTaskjobstatus', 'cleantaskjob')
    ) {
        $crontask->getFromDBbyName('PluginGlpiinventoryTaskjobstatus', 'cleantaskjob');
        $crontask->delete($crontask->fields);
    }

    if ($crontask->getFromDBbyName('PluginGlpiinventoryTaskjobstatus', 'cleantaskjob')) {
        $DB->update(
            'glpi_crontasks',
            [
            'itemtype'  => 'PluginGlpiinventoryTaskjobstate'
            ],
            [
            'itemtype'  => 'PluginGlpiinventoryTaskjobstatus'
            ]
        );
    }
    if (!$crontask->getFromDBbyName('PluginGlpiinventoryTaskjobstate', 'cleantaskjob')) {
        CronTask::Register(
            'PluginGlpiinventoryTaskjobstate',
            'cleantaskjob',
            (3600 * 24),
            ['mode' => 2, 'allowmode' => 3, 'logs_lifetime' => 30]
        );
    }
    if ($crontask->getFromDBbyName('PluginFusinvsnmpNetworkPortLog', 'cleannetworkportlogs')) {
        $crontask->delete($crontask->fields);
    }
    if ($crontask->getFromDBbyName('PluginGlpiinventoryConfigurationManagement', 'checkdevices')) {
        $crontask->delete($crontask->fields);
    }
    if ($crontask->getFromDBbyName('PluginGlpiinventoryTaskjob', 'updatedynamictasks')) {
        $crontask->delete($crontask->fields);
    }
    if (!$crontask->getFromDBbyName('PluginGlpiinventoryAgent', 'cleanoldagents')) {
        CronTask::Register(
            'PluginGlpiinventoryAgent',
            'cleanoldagents',
            86400,
            ['mode' => 2, 'allowmode' => 3, 'logs_lifetime' => 30,
                               'hourmin' => 22, 'hourmax' => 6,
            'comment' => Toolbox::addslashes_deep(__(
                'Delete agents that have not contacted the server since "xxx" days.',
                'glpiinventory'
            ))]
        );
    }
    if (!$crontask->getFromDBbyName('PluginGlpiinventoryTask', 'cleanondemand')) {
        CronTask::Register(
            'PluginGlpiinventoryTask',
            'cleanondemand',
            86400,
            ['mode' => 2, 'allowmode' => 3, 'logs_lifetime' => 30,
            'comment' => Toolbox::addslashes_deep(__('Clean on demand deployment tasks'))]
        );
    }

   /*
    * Update task's agents list from dynamic group periodically in order to automatically target new
    * computer.
    */
    if (!$crontask->getFromDBbyName('PluginGlpiinventoryAgentWakeup', 'wakeupAgents')) {
        CronTask::Register(
            'PluginGlpiinventoryAgentWakeup',
            'wakeupAgents',
            120,
            ['mode' => 2, 'allowmode' => 3, 'logs_lifetime' => 30,
            'comment' => Toolbox::addslashes_deep(__('Wake agents ups'))]
        );
    }

   // Fix software version in computers. see https://github.com/fusioninventory/fusioninventory-for-glpi/issues/1810
    $iterator = $DB->request([
      'FROM'   => 'glpi_computers',
      'WHERE'  => ['entities_id' => ['>', 0]]
    ]);
    if (count($iterator)) {
        $update = $DB->buildUpdate(
            'glpi_items_softwareversions',
            [
            'entities_id'  => new \QueryParam()
            ],
            [
            'itemtype'     => 'Computer',
            'items_id'     => new \QueryParam(),
            'is_dynamic'   => 1,
            'entities_id'  => 0
            ]
        );
        $stmt = $DB->prepare($update);
        foreach ($iterator as $data) {
            $stmt->bind_param(
                'ss',
                $data['entities_id'],
                $data['id']
            );
            $DB->executeStatement($stmt);
        }
        mysqli_stmt_close($stmt);
    }

    if ($DB->tableExists('glpi_plugin_glpiinventory_profiles')) {
       //Migrate rights to the new system introduction in GLPI 0.85
        PluginGlpiinventoryProfile::migrateProfiles();
       //Drop old table
        $migration->dropTable('glpi_plugin_glpiinventory_profiles');
    }

   //Antivirus stuff has been integrated in GLPI's core
    if ($DB->tableExists('glpi_plugin_glpiinventory_inventorycomputerantiviruses')) {
       //Antivirus migration from FI table to GLPi core table
        $antivirus = new ComputerAntivirus();
        foreach (getAllDataFromTable('glpi_plugin_glpiinventory_inventorycomputerantiviruses') as $ant) {
            unset($ant['id']);
            $ant['is_dynamic'] = 1;
            if (isset($ant['uptodate'])) {
                $ant['is_uptodate'] = $ant['uptodate'];
                unset($ant['uptodate']);
            } else {
                $ant['is_uptodate'] = 0;
            }
            if (isset($ant['version'])) {
                $ant['antivirus_version'] = $ant['version'];
                unset($ant['version']);
            } else {
                $ant['antivirus_version'] = '';
            }
            $antivirus->add($ant, [], false);
        }
        $migration->dropTable('glpi_plugin_glpiinventory_inventorycomputerantiviruses');
    }

   //Create first access to the current profile is needed
    if (isset($_SESSION['glpiactiveprofile'])) {
        PluginGlpiinventoryProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
    }

   // ********* Clean orphan data ********************************************** //

    // Clean timeslotentries
    $entries = [];
    $iterator = $DB->request([
        'SELECT' => 'glpi_plugin_glpiinventory_timeslotentries.id',
        'FROM'   => 'glpi_plugin_glpiinventory_timeslotentries',
        'LEFT JOIN' => [
            'glpi_plugin_glpiinventory_timeslots' => [
                'FKEY' => [
                    'glpi_plugin_glpiinventory_timeslotentries' => 'plugin_glpiinventory_timeslots_id',
                    'glpi_plugin_glpiinventory_timeslots' => 'id'
                ]
            ]
        ],
        'WHERE'  => ['glpi_plugin_glpiinventory_timeslots.id' => null]
    ]);

    if (count($iterator)) {
        foreach ($iterator as $data) {
            $entries[] = $data['id'];
        }
        $DB->delete('glpi_plugin_glpiinventory_timeslotentries', ['id' => $entries]);
    }

    // Clean entities
    $entities = [];
    $iterator = $DB->request([
        'SELECT' => 'glpi_plugin_glpiinventory_entities.id',
        'FROM'   => 'glpi_plugin_glpiinventory_entities',
        'LEFT JOIN' => [
            'glpi_entities' => [
                'FKEY' => [
                    'glpi_plugin_glpiinventory_entities' => 'entities_id',
                    'glpi_entities' => 'id'
                ]
            ]
        ],
        'WHERE'  => ['glpi_entities.id' => null]
    ]);

    if (count($iterator)) {
        foreach ($iterator as $data) {
            $entities[] = $data['id'];
        }
        $DB->delete('glpi_plugin_glpiinventory_entities', ['id' => $entities]);
    }

    // Clean packages
    $tables = [
      'glpi_plugin_glpiinventory_deploypackages_entities',
      'glpi_plugin_glpiinventory_deploypackages_groups',
      'glpi_plugin_glpiinventory_deploypackages_profiles',
      'glpi_plugin_glpiinventory_deploypackages_users'
    ];
    foreach ($tables as $table) {
        $entries = [];
        $iterator = $DB->request([
            'SELECT' => $table . '.id',
            'FROM'   => $table,
            'LEFT JOIN' => [
                'glpi_plugin_glpiinventory_deploypackages' => [
                    'FKEY' => [
                        $table => 'plugin_glpiinventory_deploypackages_id',
                        'glpi_plugin_glpiinventory_deploypackages' => 'id'
                    ]
                ]
            ],
            'WHERE'  => ['glpi_plugin_glpiinventory_deploypackages.id' => null]
        ]);

        if (count($iterator)) {
            foreach ($iterator as $data) {
                $entries[] = $data['id'];
            }
            $DB->delete($table, ['id' => $entries]);
        }
    }

    // Migrate search params for dynamic groups
    doDynamicDataSearchParamsMigration();

    installDashboard();

    // Add missing index on `glpi_plugin_glpiinventory_taskjoblogs`
    $migration = addTaskJobLogIndex($migration);

    $migration->executeMigration();
}

function addTaskJobLogIndex(Migration $migration)
{
    $migration->addKey(
        "glpi_plugin_glpiinventory_taskjoblogs",
        ['itemtype', 'items_id'],
        'item'
    );

    return $migration;
}

function installDashboard()
{
    $dashboard = new Dashboard();

    if ($dashboard->getFromDB('plugin_glpiinventory_dashboard') !== false) {
       // The dashboard already exists, nothing to create
        return;
    }

    $dashboard->add([
       'key'     => 'plugin_glpiinventory_dashboard',
       'name'    => 'Glpi inventory reports',
       'context' => 'core',
    ]);

    if ($dashboard->isNewItem()) {
       // Failed to create the dashboard
        return;
    };

    $commonOptions = [
        'widgettype'   => 'bigNumber',
        'use_gradient' => '0',
        'point_labels' => '0',
     ];
    $cards = [
        'plugin_glpiinventory_nb_agent'    => [
            'color' => '#606f91'
        ],
        'plugin_glpiinventory_nb_task'    => [
            'color' => '#606f91'
        ],
        'plugin_glpiinventory_nb_printer'      => [
           'color' => '#606f91'
        ],
        'plugin_glpiinventory_nb_networkequipement' => [
           'color' => '#606f91'
        ],
        'plugin_glpiinventory_nb_phone' => [
           'color' => '#606f91'
        ],
        'plugin_glpiinventory_nb_computer'  => [
           'color' => '#606f91'
        ],
        'plugin_glpiinventory_nb_unmanaged'   => [
            'color' => '#e69138'
        ]
    ];

    // With counters
    $x = 0;
    $w = 3; // Width
    $h = 2; // Height
    $y = 0;
    foreach ($cards as $key => $options) {
        $item = new Dashboard_Item();
        $item->addForDashboard($dashboard->fields['id'], [[
            'card_id' => $key,
            'gridstack_id' => $key . '_' . Uuid::uuid4(),
            'x'       => $x,
            'y'       => $y,
            'width'   => $w,
            'height'  => $h,
            'card_options' => array_merge($commonOptions, $options),
        ]]);
        $x =  $x + $w;
    }
}


/**
 * Manage the agent part migration
 *
 * @global object $DB
 * @param object $migration
 * @return array
 */
function do_agent_migration($migration)
{
    global $DB;

   /*
    *  Table glpi_plugin_glpiinventory_agents
    */
    $newTable = "glpi_plugin_glpiinventory_agents";
    $prepare_rangeip = [];
    $prepare_agentConfig = [];
    if (
        $DB->tableExists("glpi_plugin_tracker_agents")
              && $DB->fieldExists(
                  "glpi_plugin_tracker_agents",
                  "ifaddr_start"
              )
    ) {
        $iterator = $DB->request(['FROM' => 'glpi_plugin_tracker_agents']);
        foreach ($iterator as $data) {
            $prepare_rangeip[] = [
            "ip_start" => $data['ifaddr_start'],
            "ip_end"  => $data['ifaddr_end'],
            "name"    => $data['name']
            ];
            $prepare_agentConfig[] = [
            "name" => $data["name"],
            "lock" => $data['lock'],
            "threads_networkinventory" => $data['nb_process_query'],
            "threads_networkdiscovery" => $data['nb_process_discovery']
            ];
        }
    } elseif (
        $DB->tableExists("glpi_plugin_tracker_agents")
                  and $DB->fieldExists(
                      "glpi_plugin_tracker_agents",
                      "core_discovery"
                  )
    ) {
        $iterator = $DB->request(['FROM' => 'glpi_plugin_tracker_agents']);
        foreach ($iterator as $data) {
            $prepare_agentConfig[] = [
            "name" => $data["name"],
            "lock" => $data['lock'],
            "threads_networkinventory" => $data['threads_query'],
            "threads_networkdiscovery" => $data['threads_discovery']
            ];
        }
    } elseif ($DB->tableExists("glpi_plugin_glpiinventory_agents")) {
        if ($DB->fieldExists($newTable, "module_snmpquery")) {
            $iterator = $DB->request(['FROM' => 'glpi_plugin_tracker_agents']);
            foreach ($iterator as $data) {
                $prepare_agentConfig[] = [
                 "id" => $data["ID"],
                 "threads_networkinventory" => $data['threads_query'],
                 "threads_networkdiscovery" => $data['threads_discovery'],
                 "NETORKINVENTORY" => $data['module_snmpquery'],
                 "NETWORKDISCOVERY" => $data['module_netdiscovery'],
                 "INVENTORY" => $data['module_inventory'],
                 "WAKEONLAN" => $data['module_wakeonlan']
                ];
            }
        }
    }

    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_agents';
    $a_table['oldname'] = ['glpi_plugin_tracker_agents'];

    $a_table['fields']  = [];
    $a_table['fields']['id']            = ['type'    => 'autoincrement',
                                                'value'   => ''];
    $a_table['fields']['entities_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                'value'   => null];
    $a_table['fields']['is_recursive']  = ['type'    => 'bool',
                                                'value'   => '1'];
    $a_table['fields']['name']          = ['type'    => 'string',
                                                'value'   => null];
    $a_table['fields']['last_contact']  = ['type'    => 'timestamp',
                                                'value'   => null];
    $a_table['fields']['version']       = ['type'    => 'string',
                                                'value'   => null];
    $a_table['fields']['lock']          = ['type'    => 'bool',
                                                'value'   => null];
    $a_table['fields']['device_id']     = ['type'    => 'string',
                                                'value'   => null];
    $a_table['fields']['computers_id']  = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                'value'   => null];
    $a_table['fields']['token']         = ['type'    => 'string',
                                                'value'   => null];
    $a_table['fields']['useragent']     = ['type'    => 'string',
                                                'value'   => null];
    $a_table['fields']['tag']           = ['type'    => 'string',
                                                'value'   => null];
    $a_table['fields']['threads_networkdiscovery'] = [
      'type' => "int NOT NULL DEFAULT '1' COMMENT 'array(xmltag=>value)'",
      'value'   => null];

    $a_table['fields']['threads_networkinventory'] = [
      'type' => "int NOT NULL DEFAULT '1' COMMENT 'array(xmltag=>value)'",
      'value'   => null];

    $a_table['fields']['senddico']      = [
      'type'    => 'bool',
      'value'   => null
    ];

    $a_table['fields']['timeout_networkdiscovery'] = [
      'type' => "int NOT NULL DEFAULT '0' COMMENT 'Network Discovery task timeout'",
      'value'   => null
    ];
    $a_table['fields']['timeout_networkinventory'] = [
      'type' => "int NOT NULL DEFAULT '0' COMMENT 'Network Inventory task timeout'",
      'value'   => null
    ];
    $a_table['fields']['agent_port']    = ['type'    => 'varchar(6)',
                                                'value'   => null];

    $a_table['oldfields']  = [
      'module_snmpquery',
      'module_netdiscovery',
      'module_inventory',
      'module_wakeonlan',
      'core_discovery',
      'threads_discovery',
      'core_query',
      'threads_query',
      'tracker_agent_version',
      'logs',
      'fragment',
      'itemtype',
      'device_type'];

    $a_table['renamefields'] = [];
    $a_table['renamefields']['ID'] = 'id';
    $a_table['renamefields']['last_agent_update'] = 'last_contact';
    $a_table['renamefields']['fusioninventory_agent_version'] = 'version';
    $a_table['renamefields']['key'] = 'device_id';
    $a_table['renamefields']['on_device'] = 'computers_id';
    $a_table['renamefields']['items_id'] = 'computers_id';

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'name', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'device_id', 'name' => '', 'type' => 'INDEX'];
   //$a_table['keys'][] = ['field' => 'computers_id', 'name' => '', 'type' => 'INDEX'];

    $a_table['oldkeys'] = ['key'];

    migratePluginTables($migration, $a_table);

   /*
   * Table glpi_plugin_glpiinventory_agentmodules
   */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_agentmodules';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => 'autoincrement',
                                             'value'   => ''];
    $a_table['fields']['modulename'] = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['is_active']  = ['type'    => 'bool',
                                             'value'   => null];
    $a_table['fields']['exceptions'] = ['type'    => 'text',
                                             'value'   => null];

    $a_table['oldfields']  = [];
    $a_table['oldfields'][] = 'plugins_id';
    $a_table['oldfields'][] = 'entities_id';
    $a_table['oldfields'][] = 'url';

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'modulename', 'name' => '', 'type' => 'UNIQUE'];

    $a_table['oldkeys'] = ['unicity', 'entities_id'];

    migratePluginTables($migration, $a_table);

   /*
    * Add Deploy module
    */
    $iterator = $DB->request([
      'FROM'   => 'glpi_plugin_glpiinventory_agentmodules',
      'WHERE'  => ['modulename' => 'DEPLOY'],
      'LIMIT'  => 1
    ]);
    if (!count($iterator)) {
        $DB->insert(
            'glpi_plugin_glpiinventory_agentmodules',
            [
            'modulename'   => 'DEPLOY',
            'is_active'    => 0,
            'exceptions'   => exportArrayToDB([])
            ]
        );
    }

   /*
    * Add WakeOnLan module appear in version 2.3.0
    */
    $iterator = $DB->request([
      'FROM'   => 'glpi_plugin_glpiinventory_agentmodules',
      'WHERE'  => ['modulename' => 'WAKEONLAN'],
      'LIMIT'  => 1
    ]);
    if (!count($iterator)) {
        $agentmodule = new PluginGlpiinventoryAgentmodule();
        $input = [
         'modulename'   => "WAKEONLAN",
         'is_active'  => 0,
         'exceptions' => exportArrayToDB([])
        ];
        $agentmodule->add($input);
    }

   /*
    * Add SNMPQUERY module if not present
    */
    $DB->update(
        'glpi_plugin_glpiinventory_agentmodules',
        [
         'modulename'   => 'NETWORKINVENTORY'
        ],
        [
         'modulename'   => 'SNMPQUERY'
        ]
    );

    $iterator = $DB->request([
      'FROM'   => 'glpi_plugin_glpiinventory_agentmodules',
      'WHERE'  => ['modulename' => 'NETWORKINVENTORY'],
      'LIMIT'  => 1
    ]);
    if (!count($iterator)) {
        $agentmodule = new PluginGlpiinventoryAgentmodule();
        $input = [];
        $input['modulename'] = "NETWORKINVENTORY";
        $input['is_active']  = 0;
        $input['exceptions'] = exportArrayToDB([]);
        $agentmodule->add($input);
    }

   /*
    * Add NETDISCOVERY module if not present
    */
    $DB->update(
        'glpi_plugin_glpiinventory_agentmodules',
        [
         'modulename'   => 'NETWORKDISCOVERY'
        ],
        [
         'modulename'   => 'NETDISCOVERY'
        ]
    );

    $iterator = $DB->request([
      'SELECT' => ['id'],
      'FROM'   => 'glpi_plugin_glpiinventory_agentmodules',
      'WHERE'  => ['modulename' => 'NETWORKDISCOVERY'],
      'LIMIT'  => 1
    ]);

    if (!count($iterator)) {
        $agentmodule = new PluginGlpiinventoryAgentmodule();
        $input = [
         'modulename'   => "NETWORKDISCOVERY",
         'is_active'    => 0,
         'exceptions'   => exportArrayToDB([])
        ];
        $agentmodule->add($input);
    }

   /*
    * Add INVENTORY module if not present
    */
    $iterator = $DB->request([
      'SELECT' => ['id'],
      'FROM'   => 'glpi_plugin_glpiinventory_agentmodules',
      'WHERE'  => ['modulename' => 'INVENTORY'],
      'LIMIT'  => 1
    ]);
    if (!count($iterator)) {
        $agentmodule = new PluginGlpiinventoryAgentmodule();
        $input = [
         'modulename'   => "INVENTORY",
         'is_active'    => 1,
         'exceptions'   => exportArrayToDB([])
        ];
        $agentmodule->add($input);
    }

   /*
    * Add ESX module appear in version 2.4.0(0.80+1.0)
    */
    $DB->update(
        'glpi_plugin_glpiinventory_agentmodules',
        [
         'modulename'   => 'InventoryComputerESX'
        ],
        [
         'modulename'   => 'ESX'
        ]
    );

    $agentmodule = new PluginGlpiinventoryAgentmodule();
    $iterator = $DB->request([
      'SELECT' => ['id'],
      'FROM'   => 'glpi_plugin_glpiinventory_agentmodules',
      'WHERE'  => ['modulename' => 'InventoryComputerESX'],
      'LIMIT'  => 1
    ]);
    if (!count($iterator)) {
        $input = [
         'modulename'   => "InventoryComputerESX",
         'is_active'    => 0,
         'exceptions'   => exportArrayToDB([])
        ];
        $url = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $url = $_SERVER['HTTP_REFERER'];
        }
        $agentmodule->add($input);
    }

   /*
    * Add Collect module appear in version 0.84+2.0
    */
    $agentmodule = new PluginGlpiinventoryAgentmodule();
    $iterator = $DB->request([
      'SELECT' => ['id'],
      'FROM'   => 'glpi_plugin_glpiinventory_agentmodules',
      'WHERE'  => ['modulename' => 'Collect'],
      'LIMIT'  => 1
    ]);
    if (!count($iterator)) {
        $input = [
         'modulename'   => "Collect",
         'is_active'    => 1,
         'exceptions'   => exportArrayToDB([])
        ];
        $agentmodule->add($input);
    }

   /*
    * Migrate data of table glpi_plugin_fusinvsnmp_agentconfigs into
    * glpi_plugin_glpiinventory_agents
    */
    if ($DB->tableExists("glpi_plugin_fusinvsnmp_agentconfigs")) {
        $iterator = $DB->request(['FROM' => 'glpi_plugin_fusinvsnmp_agentconfigs']);
        if (count($iterator)) {
            $update = $DB->buildUpdate(
                'glpi_plugin_glpiinventory_agents',
                [
                'threads_networkdiscovery' => new \QueryParam(),
                'threads_networkinventory' => new \QueryParam(),
                'senddico'                 => new \QueryParam()
                ],
                [
                'id'                       => new \QueryParam()
                ]
            );
            $stmt = $DB->prepare($update);
            foreach ($iterator as $data) {
                 $stmt->bind_param(
                     'ssss',
                     $data['threads_netdiscovery'],
                     $data['threads_snmpquery'],
                     $data['senddico'],
                     $data['plugin_glpiinventory_agents_id']
                 );
                 $DB->executeStatement($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }

    changeDisplayPreference("5158", "PluginGlpiinventoryAgent");

   // Delete data in glpi_logs(agent problem => ticket http://forge.fusioninventory.org/issues/1546)
   // ** Token
    $DB->delete(
        'glpi_logs',
        [
         'itemtype'           => 'PluginGlpiinventoryAgent',
         'id_search_option'   => 9
        ]
    );

   // ** Last contact
    $DB->delete(
        'glpi_logs',
        [
         'itemtype'           => 'PluginGlpiinventoryAgent',
         'id_search_option'   => 4
        ]
    );

   // ** Version
    $DB->delete(
        'glpi_logs',
        [
         'itemtype'           => 'PluginGlpiinventoryAgent',
         'id_search_option'   => 8,
         'old_value'          => new \QueryExpression($DB->quoteName('new_value'))
        ]
    );

    return $prepare_rangeip;
}


/**
 * Manage the configuration part migration
 *
 * @global object $DB
 * @param object $migration
 * @return array
 */
function do_config_migration($migration)
{
    global $DB;

    $prepare_Config = [];
   /*
    * Table glpi_plugin_glpiinventory_configs
    */
    if ($DB->tableExists('glpi_plugin_tracker_config')) {
        if ($DB->fieldExists('glpi_plugin_tracker_config', 'ssl_only')) {
            $iterator = $DB->request([
            'FROM'   => 'glpi_plugin_tracker_config',
            'LIMIT'  => 1
            ]);
            if (count($iterator)) {
                 $data = $iterator->current();
                 $prepare_Config['ssl_only'] = $data['ssl_only'];
            }
        }
    }
    if ($DB->tableExists('glpi_plugin_glpiinventory_configs')) {
        $id = 'id';
        if ($DB->fieldExists('glpi_plugin_glpiinventory_configs', 'ID')) {
            $id = 'ID';
        }

        $iterator = $DB->request([
         'FROM'   => 'glpi_plugin_glpiinventory_configs',
         'WHERE'  => ['type' => 'version'],
         'START'  => 1,
         'LIMIT'  => 10
        ]);
        if (count($iterator)) {
            $delete = $DB->buildDelete(
                'glpi_plugin_glpiinventory_configs',
                [
                $id => new \QueryParam()
                ]
            );
            $stmt = $DB->prepare($delete);
            foreach ($iterator as $data) {
                 $stmt->bind_param('s', $data['id']);
                 $DB->executeStatement($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }

    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_configs';
    $a_table['oldname'] = ['glpi_plugin_tracker_config'];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => 'autoincrement',
                                            'value'   => ''];
    $a_table['fields']['type']       = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['value']      = ['type'    => 'string',
                                            'value'   => null];

    $a_table['oldfields']  = [];
    $a_table['oldfields'][] = 'version';
    $a_table['oldfields'][] = 'URL_agent_conf';
    $a_table['oldfields'][] = 'ssl_only';
    $a_table['oldfields'][] = 'authsnmp';
    $a_table['oldfields'][] = 'criteria1_ip';
    $a_table['oldfields'][] = 'criteria1_name';
    $a_table['oldfields'][] = 'criteria1_serial';
    $a_table['oldfields'][] = 'criteria1_macaddr';
    $a_table['oldfields'][] = 'criteria2_ip';
    $a_table['oldfields'][] = 'criteria2_name';
    $a_table['oldfields'][] = 'criteria2_serial';
    $a_table['oldfields'][] = 'criteria2_macaddr';
    $a_table['oldfields'][] = 'delete_agent_process';
    $a_table['oldfields'][] = 'activation_history';
    $a_table['oldfields'][] = 'activation_connection';
    $a_table['oldfields'][] = 'activation_snmp_computer';
    $a_table['oldfields'][] = 'activation_snmp_networking';
    $a_table['oldfields'][] = 'activation_snmp_peripheral';
    $a_table['oldfields'][] = 'activation_snmp_phone';
    $a_table['oldfields'][] = 'activation_snmp_printer';
    $a_table['oldfields'][] = 'plugins_id';
    $a_table['oldfields'][] = 'module';

    $a_table['renamefields'] = [];
    $a_table['renamefields']['ID'] = 'id';

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => ["type"],
                              'name' => 'unicity',
                              'type' => 'UNIQUE'];

    $a_table['oldkeys'] = ['unicity'];

    migratePluginTables($migration, $a_table);

    return $prepare_Config;
}


/**
 * Manage the entities part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_entities_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_entities
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_entities';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => 'autoincrement',
                                            'value'   => ''];
    $a_table['fields']['entities_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['fields']['transfers_id_auto'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                              'value'   => null];
    $a_table['fields']['agent_base_url'] = ['type'    => 'string',
                                              'value'   => ''];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => ['entities_id', 'transfers_id_auto'],
                              'name' => 'entities_id',
                              'type' => 'INDEX'];

    $a_table['oldkeys'] = [];

    // Fix -1 values in `transfers_id_auto`
    if ($DB->tableExists('glpi_plugin_glpiinventory_entities') && $DB->fieldExists('glpi_plugin_glpiinventory_entities', 'transfers_id_auto')) {
        $DB->update(
            'glpi_plugin_glpiinventory_entities',
            ['transfers_id_auto' => '0'],
            ['transfers_id_auto' => '-1'],
        );
    }

    migratePluginTables($migration, $a_table);
    if (countElementsInTable($a_table['name']) == 0) {
        $a_configs = getAllDataFromTable(
            'glpi_plugin_glpiinventory_configs',
            ['type' => 'transfers_id_auto']
        );
        $transfers_id_auto = 0;
        if (count($a_configs) > 0) {
            $a_config = current($a_configs);
            $transfers_id_auto = $a_config['value'];
        }

        $a_configs = getAllDataFromTable(
            'glpi_plugin_glpiinventory_configs',
            ['type' => 'agent_base_url']
        );
        $agent_base_url = '';
        if (count($a_configs) > 0) {
            $a_config = current($a_configs);
            $agent_base_url = $a_config['value'];
        }

        $DB->insert(
            'glpi_plugin_glpiinventory_entities',
            [
            'entities_id'        => 0,
            'transfers_id_auto'  => $transfers_id_auto,
            'agent_base_url'     => $agent_base_url
            ]
        );
    } elseif (countElementsInTable($a_table['name']) > 0) {
        $a_configs = getAllDataFromTable(
            'glpi_plugin_glpiinventory_configs',
            ['type' => 'agent_base_url']
        );
        $agent_base_url = '';
        if (count($a_configs) > 0) {
            $a_config = current($a_configs);
            $agent_base_url = $a_config['value'];

            $DB->update(
                'glpi_plugin_glpiinventory_entities',
                [
                'agent_base_url' => $agent_base_url
                ],
                [true]
            );
        }
    }
}


/**
 * Manage the IP range part migration
 *
 * @global object $DB
 * @param object $migration
 * @return array
 */
function do_iprange_migration($migration)
{
    global $DB;

    $prepare_task = [];

   /*
    * Table glpi_plugin_glpiinventory_ipranges
    */
    if ($DB->tableExists("glpi_plugin_tracker_rangeip")) {
       // Get all data to create task
        $iterator = $DB->request(['FROM' => 'glpi_plugin_tracker_rangeip']);
        foreach ($iterator as $data) {
            if ($data['discover'] == '1') {
                $prepare_task[] = ["agents_id" => $data['FK_tracker_agents'],
                                    "ipranges_id" => $data['ID'],
                                    "netdiscovery" => "1"];
            }
            if ($data['query'] == '1') {
                $prepare_task[] = ["agents_id" => $data['FK_tracker_agents'],
                                    "ipranges_id" => $data['ID'],
                                    "snmpquery" => "1"];
            }
        }
    }
    if (
        $DB->tableExists("glpi_plugin_glpiinventory_rangeip")
           && $DB->fieldExists(
               "glpi_plugin_glpiinventory_rangeip",
               "FK_fusioninventory_agents_discover"
           )
    ) {
       // Get all data to create task
        $iterator = $DB->request(['FROM' => 'glpi_plugin_glpiinventory_rangeip']);
        foreach ($iterator as $data) {
            if ($data['discover'] == '1') {
                $prepare_task[] = ["agents_id" => $data['FK_fusioninventory_agents_discover'],
                                    "ipranges_id" => $data['ID'],
                                    "netdiscovery" => "1"];
            }
            if ($data['query'] == '1') {
                $prepare_task[] = ["agents_id" => $data['FK_fusioninventory_agents_query'],
                                    "ipranges_id" => $data['ID'],
                                    "snmpquery" => "1"];
            }
        }
    }
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_ipranges';
    $a_table['oldname'] = ['glpi_plugin_tracker_rangeip', 'glpi_plugin_fusinvsnmp_ipranges'];

    $a_table['fields']  = [
      'id'         => ['type'    => 'autoincrement',    'value'   => ''],
      'name'       => ['type'    => 'string',           'value'   => null],
      'entities_id' => ['type'    => 'int unsigned NOT NULL DEFAULT 0',          'value'   => null],
      'ip_start'   => ['type'    => 'string',           'value'   => null],
      'ip_end'     => ['type'    => 'string',           'value'   => null]
    ];

    $a_table['oldfields']  = [
      'FK_tracker_agents',
      'discover',
      'query',
      'FK_fusioninventory_agents_discover',
      'FK_fusioninventory_agents_query',
      'construct_device_id',
      'log',
      'comment'
    ];

    $a_table['renamefields'] = [
      'ID'           => 'id',
      'ifaddr_start' => 'ip_start',
      'ifaddr_end'   => 'ip_end',
      'FK_entities'  => 'entities_id'
    ];

    $a_table['keys']   = [
      ['field' => 'entities_id', 'name' => '', 'type' => 'INDEX']
    ];

    $a_table['oldkeys'] = [
      'FK_tracker_agents',
      'FK_tracker_agents_2'
    ];

    migratePluginTables($migration, $a_table);

    changeDisplayPreference("5159", "PluginFusinvsnmpIPRange");

    return $prepare_task;
}


/**
 * Manage the locks part migration
 *
 * @param object $migration
 */
function do_locks_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_locks
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_locks';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => 'autoincrement',
                                            'value'   => ''];
    $a_table['fields']['tablename']  = [
                     'type'    => "varchar(64) NOT NULL DEFAULT ''",
                     'value'   => null];
    $a_table['fields']['items_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['fields']['tablefields'] = ['type'    => 'text',
                                            'value'   => null];

    $a_table['oldfields']  = ['itemtype'];

    $a_table['renamefields'] = [];
    $a_table['renamefields']['fields'] = 'tablefields';

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'tablename', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'items_id' , 'name' => '', 'type' => 'INDEX'];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   // Deduplicate entries

    $iterator = $DB->request([
      'SELECT'  => [
         'tablename',
         'COUNT' => ['tablename as cpt'],
         'items_id'
      ],
      'FROM'    => 'glpi_plugin_glpiinventory_locks',
      'GROUPBY' => [
         'tablename',
         'items_id'
      ],
      'HAVING' => [
         'cpt' => ['>', 1]
      ]
    ]);
    foreach ($iterator as $data) {
        $DB->delete(
            'glpi_plugin_glpiinventory_locks',
            [
               'tablename' => $data['tablename'],
               'items_id'  => $data['items_id']
            ],
            ['ORDER' => 'ID desc', 'LIMIT' => ($data['cpt'] - 1)]
        );
    }

   // add unique key
    $a_table['keys'][] = ['field' => ["tablename", "items_id"],
                         'name' => 'unicity', 'type' => 'UNIQUE'];
    migratePluginTables($migration, $a_table);
}


/**
 * Manage the SNMP communities linked to IP range part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_iprangeconfigsecurity_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_ipranges_configsecurities
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_ipranges_configsecurities';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => 'autoincrement',
                                            'value'   => ''];
    $a_table['fields']['plugin_glpiinventory_ipranges_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['fields']['plugin_glpiinventory_configsecurities_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['fields']['rank']       = ['type'    => 'integer',
                                            'value'   => '1'];

    $a_table['oldfields']    = [];

    $a_table['renamefields'] = [];

    $a_table['keys']         = [];

    $a_table['oldkeys']      = [];

    migratePluginTables($migration, $a_table);

   /*
    *  Clean SNMP communities orphelin associated to deleted ipranges
    */
    $iterator = $DB->request([
      'SELECT'    => 'glpi_plugin_glpiinventory_ipranges_configsecurities.id',
      'FROM'      => 'glpi_plugin_glpiinventory_ipranges_configsecurities',
      'LEFT JOIN' => [
         'glpi_plugin_glpiinventory_ipranges' => [
            'FKEY'   => [
               'glpi_plugin_glpiinventory_ipranges_configsecurities'  => 'plugin_glpiinventory_ipranges_id',
               'glpi_plugin_glpiinventory_ipranges'                   => 'id'
            ]
         ]
      ],
      'WHERE'     => ['glpi_plugin_glpiinventory_ipranges_configsecurities.id' => null]
    ]);
    if (count($iterator)) {
        $delete = $DB->buildDelete(
            'glpi_plugin_glpiinventory_ipranges_configsecurities',
            [
            'id' => new \QueryParam()
            ]
        );
        $stmt = $DB->prepare($delete);
        foreach ($iterator as $data) {
            $stmt->bind_param('s', $data['id']);
            $DB->executeStatement($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}


/**
 * Manage the profile part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_profile_migration($migration)
{
    global $DB;

    if ($DB->tableExists('glpi_plugin_glpiinventory_profiles')) {
       /*
        * Table glpi_plugin_glpiinventory_profiles
        */
        $a_table = [];
        $a_table['name'] = 'glpi_plugin_glpiinventory_profiles';
        $a_table['oldname'] = [];

        $a_table['fields']  = [];
        $a_table['fields']['id']         = ['type'    => 'autoincrement',
                                               'value'   => ''];
        $a_table['fields']['type']       = ['type'    => 'string',
                                               'value'   => ''];
        $a_table['fields']['right']      = ['type'    => 'char',
                                               'value'   => null];
        $a_table['fields']['plugins_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                               'value'   => null];
        $a_table['fields']['profiles_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                               'value'   => null];

        $a_table['oldfields']  = [
          'name',
          'interface',
          'is_default',
          'snmp_networking',
          'snmp_printers',
          'snmp_models',
          'snmp_authentification',
          'rangeip',
          'agents',
          'remotecontrol',
          'agentsprocesses',
          'unknowndevices',
          'reports',
          'deviceinventory',
          'netdiscovery',
          'snmp_query',
          'wol',
          'configuration'];

        $a_table['renamefields'] = [];
        $a_table['renamefields']['ID'] = 'id';

        $a_table['keys']   = [];

        $a_table['oldkeys'] = [];

        migratePluginTables($migration, $a_table);

       // Remove multiple lines can have problem with unicity
        $query = "SELECT * , count(`id`) AS cnt
         FROM `glpi_plugin_glpiinventory_profiles`
         GROUP BY `type`,`plugins_id`,`profiles_id`
         HAVING cnt >1
         ORDER BY cnt";
        $result = $DB->query($query);
        while ($data = $DB->fetchArray($result)) {
            //DB::delete() not yet supports limit nor order
            $queryd = "DELETE FROM `glpi_plugin_glpiinventory_profiles`
               WHERE `type`='" . $data['type'] . "'
                  AND `plugins_id`='" . $data['plugins_id'] . "'
                  AND `profiles_id`='" . $data['profiles_id'] . "'
               ORDER BY `id` DESC
               LIMIT " . ($data['cnt'] - 1) . " ";
            $DB->query($queryd);
        }

        $a_table = [];
        $a_table['name'] = 'glpi_plugin_glpiinventory_profiles';
        $a_table['oldname'] = [];

        $a_table['fields']  = [];

        $a_table['oldfields']  = [];

        $a_table['renamefields'] = [];

        $a_table['keys']   = [];
        $a_table['keys'][] = ['field' => ["type", "plugins_id", "profiles_id"],
                                 'name' => 'unicity', 'type' => 'UNIQUE'];

        $a_table['oldkeys'] = [];

        migratePluginTables($migration, $a_table);
    }
}


/**
 * Manage the timeslot (of task) part migration
 *
 * @param object $migration
 */
function do_timeslot_migration($migration)
{
   /*
    * Table glpi_plugin_glpiinventory_timeslots
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_timeslots';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']           = ['type'    => 'autoincrement',
                                              'value'   => ''];
    $a_table['fields']['entities_id']  = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                              'value'   => null];
    $a_table['fields']['is_recursive'] = ['type'    => 'bool',
                                              'value'   => '0'];
    $a_table['fields']['name']         = ['type'    => 'string',
                                              'value'   => null];
    $a_table['fields']['comment']      = ['type'    => 'text',
                                              'value'   => null];
    $a_table['fields']['date_mod']     = ['type'    => 'timestamp',
                                              'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   /*
    * Table glpi_plugin_glpiinventory_timeslotentries
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_timeslotentries';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']           = ['type'    => 'autoincrement',
                                              'value'   => ''];
    $a_table['fields']['plugin_glpiinventory_timeslots_id']  = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                              'value'   => null];
    $a_table['fields']['entities_id']  = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                              'value'   => null];
    $a_table['fields']['is_recursive'] = ['type'    => 'bool',
                                              'value'   => '0'];
    $a_table['fields']['day']          = ['type'    => 'bool',
                                              'value'   => 1];
    $a_table['fields']['begin']        = ['type'    => 'int DEFAULT NULL',
                                              'value'   => null];
    $a_table['fields']['end']          = ['type'    => 'int DEFAULT NULL',
                                              'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);
}


/**
 * Manage the unmanaged devices part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_unmanaged_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_unmanageds
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_unmanageds';
    $a_table['oldname'] = [
       'glpi_plugin_glpiinventory_unknowndevices',
       'glpi_plugin_tracker_unknown_device'];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => 'autoincrement',
                                            'value'   => ''];
    $a_table['fields']['name']       = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['date_mod']   = ['type'    => 'timestamp',
                                            'value'   => null];
    $a_table['fields']['entities_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['fields']['locations_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['fields']['is_deleted'] = ['type'    => 'bool',
                                            'value'   => null];
    $a_table['fields']['users_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['fields']['serial']     = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['otherserial'] = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['contact']    = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['domain']     = ['type'    => 'integer',
                                            'value'   => null];
    $a_table['fields']['comment']    = ['type'    => 'text',
                                            'value'   => null];
    $a_table['fields']['item_type']  = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['accepted']   = ['type'    => 'bool',
                                            'value'   => null];
    $a_table['fields']['agents_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['fields']['ip']         = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['hub']        = ['type'    => 'bool',
                                            'value'   => null];
    $a_table['fields']['states_id']  = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['fields']['sysdescr']   = ['type'    => 'text',
                                            'value'   => null];
    $a_table['fields']['plugin_glpiinventory_configsecurities_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['fields']['is_dynamic'] = ['type'    => 'bool',
                                            'value'   => null];
    $a_table['fields']['serialized_inventory'] = ['type'    => 'longblob',
                                            'value'   => null];

    $a_table['oldfields']  = [
      'dnsname',
      'snmp',
      'FK_model_infos',
      'FK_snmp_connection',
      'FK_agent',
      'mac',
      'ifmac',
      'plugin_fusinvsnmp_models_id',
      'plugin_glpiinventory_snmpmodels_id',
       'is_template'
      ];

    $a_table['renamefields'] = [
      'ID'           => 'id',
      'comments'     => 'comment',
      'type'         => 'item_type',
      'ifaddr'       => 'ip',
      'FK_entities'  => 'entities_id',
      'location'     => 'locations_id',
      'deleted'      => 'is_deleted',
      'plugin_fusinvsnmp_configsecurities_id' => 'plugin_glpiinventory_configsecurities_id',
      'plugin_glpiinventory_agents_id' => 'agents_id',
       ];

    $a_table['keys']   = [
      ['field' => 'entities_id', 'name' => '', 'type' => 'INDEX'],
      ['field' => 'agents_id', 'name' => '', 'type' => 'INDEX'],
      ['field' => 'is_deleted', 'name' => '', 'type' => 'INDEX'],
      ['field' => 'date_mod', 'name' => '', 'type' => 'INDEX']
    ];

    $a_table['oldkeys'] = [
        'plugin_glpiinventory_agents_id',
    ];

    migratePluginTables($migration, $a_table);

    if ($DB->tableExists('glpi_plugin_fusinvsnmp_unknowndevices')) {
        $iterator = $DB->request(['FROM' => 'glpi_plugin_fusinvsnmp_unknowndevices']);
        if (count($iterator)) {
            $update = $DB->buildUpdate(
                'glpi_plugin_glpiinventory_unmanageds',
                [
                'sysdescr'                                   => new \QueryParam(),
                'plugin_glpiinventory_configsecurities_id' => new \QueryParam()
                ],
                [
                'id'                                         => new \QueryParam()
                ]
            );
            $stmt = $DB->prepare($update);
            foreach ($iterator as $data) {
                 $stmt->bind_param(
                     'sss',
                     $data['sysdescr'],
                     $data['plugin_fusinvsnmp_configsecurities_id'],
                     $data['plugin_glpiinventory_unknowndevices_id']
                 );
            }
            mysqli_stmt_close($stmt);
        }
        $migration->dropTable('glpi_plugin_fusinvsnmp_unknowndevices');
    }

    changeDisplayPreference("5153", "PluginGlpiinventoryUnmanaged");
    changeDisplayPreference(
        "PluginGlpiinventoryUnknownDevice",
        "PluginGlpiinventoryUnmanaged"
    );

   /*
    * Delete IP and MAC of PluginGlpiinventoryUnmanaged in displaypreference
    */
    $DB->delete(
        'glpi_displaypreferences',
        [
         'itemtype'  => 'PluginGlpiinventoryUnmanaged',
         'OR'        => [
            'num' => [11, 12, 16]
         ]
        ]
    );

   /*
    * Convert itemtype from glpi_plugin_glpiinventory_unknowndevices to
    * PluginGlpiinventoryUnmanaged
    */
    $tables = ['glpi_networkports', 'glpi_logs',
      'glpi_plugin_glpiinventory_ignoredimportdevices'];
    foreach ($tables as $table) {
        $DB->update(
            $table,
            [
            'itemtype'  => 'PluginGlpiinventoryUnmanaged'
            ],
            [
            'itemtype'  => 'PluginGlpiinventoryUnknowndevice'
            ]
        );
    }

    $DB->update(
        'glpi_ipaddresses',
        [
         'mainitemtype' => 'PluginGlpiinventoryUnmanaged'
        ],
        [
         'mainitemtype' => 'PluginGlpiinventoryUnknowndevice'
        ]
    );
}


/**
 * Manage the ignored import rules part migration
 *
 * @param object $migration
 */
function do_ignoredimport_migration($migration)
{
   /*
    * Table glpi_plugin_glpiinventory_ignoredimportdevices
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_ignoredimportdevices';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => 'autoincrement',
                                            'value'   => ''];
    $a_table['fields']['name']       = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['date']       = ['type'    => 'timestamp',
                                            'value'   => null];
    $a_table['fields']['itemtype']   = [
                     'type'    => "varchar(100) DEFAULT NULL",
                     'value'   => null];
    $a_table['fields']['entities_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['fields']['ip']         = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['mac']        = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['rules_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['fields']['method']     = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['serial']     = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['uuid']       = ['type'    => 'string',
                                            'value'   => null];
    $a_table['fields']['agents_id']
                                    = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [
        'plugin_glpiinventory_agents_id' => 'agents_id',
    ];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'agents_id',
                              'name' => '',
                              'type' => 'INDEX'];

    $a_table['oldkeys'] = [
        'plugin_glpiinventory_agents_id',
    ];

    migratePluginTables($migration, $a_table);
}


/**
 * Manage the computer blacklist part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_blacklist_migration($migration)
{
    global $DB;
   /*
    * Table glpi_plugin_glpiinventory_inventorycomputercriterias
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_inventorycomputercriterias';
    $a_table['oldname'] = ['glpi_plugin_fusinvinventory_criterias'];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => 'autoincrement',
                                             'value'   => ''];
    $a_table['fields']['name']       = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['comment']    = ['type'    => 'text',
                                             'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'name', 'name' => '', 'type' => 'INDEX'];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   /*
   * Table glpi_plugin_glpiinventory_inventorycomputerblacklists
   */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_inventorycomputerblacklists';
    $a_table['oldname'] = ['glpi_plugin_fusinvinventory_blacklists'];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => 'autoincrement',
                                             'value'   => ''];
    $a_table['fields']['plugin_glpiinventory_criterium_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                                     'value'   => null];
    $a_table['fields']['value']  = ['type'    => 'string',
                                          'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'plugin_glpiinventory_criterium_id',
                              'name' => '',
                              'type' => 'KEY'];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);
    $DB->listFields($a_table['name'], false);

   /*
   *  Udpate criteria for blacklist
   */
    $a_criteria = [
         'Serial number'       => 'ssn',
         'uuid'                => 'uuid',
         'Mac address'         => 'macAddress',
         'Windows product key' => 'winProdKey',
         'Model'               => 'smodel',
         'storage serial'      => 'storagesSerial',
         'drives serial'       => 'drivesSerial',
         'Asset Tag'           => 'assetTag',
         'Computer name'       => 'name',
         'Manufacturer'        => 'manufacturer'
    ];

    foreach ($a_criteria as $name => $comment) {
        $iterator = $DB->request([
         'FROM'   => 'glpi_plugin_glpiinventory_inventorycomputercriterias',
         'WHERE'  => ['name' => $name]
        ]);
        if (!count($iterator)) {
            $DB->insert(
                'glpi_plugin_glpiinventory_inventorycomputercriterias',
                [
                'name'      => $name,
                'comment'   => $comment
                ]
            );
        }
    }
    $a_criteria = [];
    $iterator = $DB->request(['FROM' => 'glpi_plugin_glpiinventory_inventorycomputercriterias']);
    foreach ($iterator as $data) {
        $a_criteria[$data['comment']] = $data['id'];
    }

   /*
   * Update blacklist
   */
    $newTable = "glpi_plugin_glpiinventory_inventorycomputerblacklists";
   // * ssn
    $a_input = [
      'N/A',
      '(null string)',
      'INVALID',
      'SYS-1234567890',
      'SYS-9876543210',
      'SN-12345',
      'SN-1234567890',
      '1111111111',
      '1111111',
      '1',
      '0123456789',
      '12345',
      '123456',
      '1234567',
      '12345678',
      '123456789',
      '1234567890',
      '123456789000',
      '12345678901234567',
      '0000000000',
      '000000000',
      '00000000',
      '0000000',
      '0000000',
      'NNNNNNN',
      'xxxxxxxxxxx',
      'EVAL',
      'IATPASS',
      'none',
      'To Be Filled By O.E.M.',
      'Tulip Computers',
      'Serial Number xxxxxx',
      'SN-123456fvgv3i0b8o5n6n7k',
      'Unknow',
      'System Serial Number',
      'MB-1234567890',
      '0',
      'empty',
      'Not Specified',
      'OEM_Serial',
      'SystemSerialNumb'];

    foreach ($a_input as $value) {
        $iterator = $DB->request([
         'FROM'   => $newTable,
         'WHERE'  => [
            'plugin_glpiinventory_criterium_id'  => $a_criteria['ssn'],
            'value'                                => $value
         ]
        ]);
        if (!count($iterator)) {
            $DB->insert(
                $newTable,
                [
                'plugin_glpiinventory_criterium_id'  => $a_criteria['ssn'],
                'value'                                => $value
                ]
            );
        }
    }

   // * uuid
    $a_input = [
      'FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF',
      '03000200-0400-0500-0006-000700080009',
      '6AB5B300-538D-1014-9FB5-B0684D007B53',
      '01010101-0101-0101-0101-010101010101',
      '2'];

    foreach ($a_input as $value) {
        $iterator = $DB->request([
         'FROM'   => $newTable,
         'WHERE'  => [
            'plugin_glpiinventory_criterium_id'  => $a_criteria['uuid'],
            'value'                                => $value
         ]
        ]);
        if (!count($iterator)) {
            $DB->insert(
                $newTable,
                [
                'plugin_glpiinventory_criterium_id'  => $a_criteria['uuid'],
                'value'                                => $value
                ]
            );
        }
    }

   // * macAddress
    $a_input = [
      '20:41:53:59:4e:ff',
      '02:00:4e:43:50:49',
      'e2:e6:16:20:0a:35',
      'd2:0a:2d:a0:04:be',
      '00:a0:c6:00:00:00',
      'd2:6b:25:2f:2c:e7',
      '33:50:6f:45:30:30',
      '0a:00:27:00:00:00',
      '00:50:56:C0:00:01',
      '00:50:56:C0:00:08',
      '02:80:37:EC:02:00',
      '50:50:54:50:30:30',
      '24:b6:20:52:41:53',
      '00:50:56:C0:00:02',
      '00:50:56:C0:00:03',
      '00:50:56:C0:00:04',
      'FE:FF:FF:FF:FF:FF',
      '00:00:00:00:00:00',
      '00:0b:ca:fe:00:00'];
    foreach ($a_input as $value) {
        $iterator = $DB->request([
         'FROM'   => $newTable,
         'WHERE'  => [
            'plugin_glpiinventory_criterium_id'  => $a_criteria['macAddress'],
            'value'                                => $value
         ]
        ]);
        if (!count($iterator)) {
            $DB->insert(
                $newTable,
                [
                'plugin_glpiinventory_criterium_id'  => $a_criteria['macAddress'],
                'value'                                => $value
                ]
            );
        }
    }

   // * smodel
    $a_input = [
      'Unknow',
      'To Be Filled By O.E.M.',
      '*',
      'System Product Name',
      'Product Name',
      'System Name',
      'All Series'];
    foreach ($a_input as $value) {
        $iterator = $DB->request([
         'FROM'   => $newTable,
         'WHERE'  => [
            'plugin_glpiinventory_criterium_id'  => $a_criteria['smodel'],
            'value'                                => $value
         ]
        ]);
        if (!count($iterator)) {
            $DB->insert(
                $newTable,
                [
                'plugin_glpiinventory_criterium_id'  => $a_criteria['smodel'],
                'value'                                => $value
                ]
            );
        }
    }

   // * manufacturer
    $a_input = ['System manufacturer'];
    foreach ($a_input as $value) {
        $iterator = $DB->request([
         'FROM'   => $newTable,
         'WHERE'  => [
            'plugin_glpiinventory_criterium_id'  => $a_criteria['manufacturer'],
            'value'                                => $value
         ]
        ]);
        if (!count($iterator)) {
            $DB->insert(
                $newTable,
                [
                'plugin_glpiinventory_criterium_id'  => $a_criteria['manufacturer'],
                'value'                                => $value
                ]
            );
        }
    }

   // * ip
    $iterator = $DB->request([
      'FROM'   => 'glpi_plugin_glpiinventory_inventorycomputercriterias',
      'WHERE'  => ['name' => 'IP']
    ]);
    if (!count($iterator)) {
        $DB->insert(
            'glpi_plugin_glpiinventory_inventorycomputercriterias',
            [
            'id'        => 11,
            'name'      => 'IP',
            'comment'   => 'IP'
            ]
        );
    }

    $a_criteria = [];
    $iterator = $DB->request(['FROM' => 'glpi_plugin_glpiinventory_inventorycomputercriterias']);
    foreach ($iterator as $data) {
        $a_criteria[$data['comment']] = $data['id'];
    }

    $a_input = ['0.0.0.0'];
    foreach ($a_input as $value) {
        $iterator = $DB->request([
         'FROM'   => $newTable,
         'WHERE'  => [
            'plugin_glpiinventory_criterium_id'  => $a_criteria['IP'],
            'value'                                => $value
         ]
        ]);
        if (!count($iterator)) {
            $DB->insert(
                $newTable,
                [
                'plugin_glpiinventory_criterium_id'  => $a_criteria['IP'],
                'value'                                => $value
                ]
            );
        }
    }

    changeDisplayPreference(
        "PluginFusinvinventoryBlacklist",
        "PluginGlpiinventoryInventoryComputerBlacklist"
    );
}


/**
 * Manage the rules matched log part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_rulematchedlog_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_rulematchedlogs
    */
    $newTable = "glpi_plugin_glpiinventory_rulematchedlogs";
    if (!$DB->tableExists($newTable)) {
        $query = "CREATE TABLE `" . $newTable . "` (
                  `id` int unsigned NOT NULL AUTO_INCREMENT,
                   PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->query($query);
    }
    $migration->changeField(
        $newTable,
        "id",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );

    $migration->migrationOneTable($newTable);

    $migration->addField(
        $newTable,
        "date",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "items_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "itemtype",
        "varchar(100) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "rules_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    if ($DB->fieldExists($newTable, "plugin_glpiinventory_agents_id")) {
        $migration->changeField(
            $newTable,
            "plugin_glpiinventory_agents_id",
            "agents_id",
            "int unsigned NOT NULL DEFAULT '0'"
        );
    } else {
        $migration->addField(
            $newTable,
            "agents_id",
            "int unsigned NOT NULL DEFAULT '0'"
        );
    }
    $migration->addField(
        $newTable,
        "method",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "criteria",
        'text DEFAULT NULL'
    );
    $migration->migrationOneTable($newTable);
    $DB->listFields($newTable, false);
}


/**
 * Manage the antivirus part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_antivirus_migration($migration)
{
   /*
    * Table glpi_plugin_glpiinventory_inventorycomputerantiviruses
    */
    $newTable = "glpi_plugin_glpiinventory_inventorycomputerantiviruses";
    $migration->renameTable("glpi_plugin_fusinvinventory_antivirus", $newTable);
}


/**
 * Manage the computer extended part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_computercomputer_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_inventorycomputercomputers
    */
    if (
        $DB->tableExists("glpi_plugin_fusinvinventory_computers")
           && $DB->fieldExists("glpi_plugin_fusinvinventory_computers", "uuid")
    ) {
        $Computer = new Computer();
        $iterator = $DB->request(['FROM' => 'glpi_plugin_fusinvinventory_computers']);
        foreach ($iterator as $data) {
            if ($Computer->getFromDB($data['items_id'])) {
                $input = [
                 'id'     => $data['items_id'],
                 'uuid'   => $data['uuid']
                ];
                $Computer->update($input);
            }
        }
        $sql = "DROP TABLE `glpi_plugin_fusinvinventory_computers`";
        $DB->query($sql);
    }
    if ($DB->tableExists("glpi_plugin_fusinvinventory_tmp_agents")) {
        $sql = "DROP TABLE `glpi_plugin_fusinvinventory_tmp_agents`";
        $DB->query($sql);
    }
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_inventorycomputercomputers';
    $a_table['oldname'] = ['glpi_plugin_fusinvinventory_computers'];

    $a_table['fields']  = [];
    $a_table['fields']['id']                     = ['type'    => 'autoincrement',
                                                   'value'   => ''];
    $a_table['fields']['computers_id']           = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                   'value'   => null];
    $a_table['fields']['operatingsystem_installationdate'] = ['type'    => 'timestamp',
                                                             'value'   => null];
    $a_table['fields']['winowner']               = ['type'    => 'string',
                                                   'value'   => null];
    $a_table['fields']['wincompany']             = ['type'    => 'string',
                                                   'value'   => null];
    $a_table['fields']['last_inventory_update']     = ['type'    => 'timestamp',
                                                            'value'   => null];
    $a_table['fields']['remote_addr']            = ['type'    => 'string',
                                                   'value'   => null];
    $a_table['fields']['serialized_inventory']   = ['type'    => 'longblob',
                                                   'value'   => null];
    $a_table['fields']['is_entitylocked']        = ['type'    => 'bool',
                                                   'value'   => "0"];
    $a_table['fields']['oscomment']              = ['type'    => 'text',
                                                   'value'   => null];
    $a_table['fields']['last_boot']              = ['type'    => 'timestamp',
                                                   'value'   => null];

    $a_table['oldfields']  = [
      'plugin_glpiinventory_computerarchs_id',
      'bios_assettag',
      'bios_date',
      'bios_version',
      'bios_manufacturers_id'
    ];

    $a_table['renamefields']['last_fusioninventory_update'] = 'last_inventory_update';

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'computers_id', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'last_inventory_update', 'name' => '', 'type' => 'INDEX'];

    $a_table['oldkeys'] = [];

    do_biosascomponentmigration();

    migratePluginTables($migration, $a_table);

    $migration->dropTable('glpi_plugin_fusinvinventory_libserialization');

   /*
    * Manage devices with is_dynamic
    */
    $iterator = $DB->request(['FROM' => 'glpi_plugin_glpiinventory_inventorycomputercomputers']);
    if (count($iterator)) {
        $update = $DB->buildUpdate(
            'glpi_computers',
            [
            'is_dynamic'   => 1
            ],
            [
            'id'           => new \QueryParam()
            ]
        );
        $stmt = $DB->prepare($update);
        foreach ($iterator as $data) {
            $stmt->bind_param('s', $data['computers_id']);
            $DB->executeStatement($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}


/**
 * A firmware component with a BIOS type has been added in GLPI 9.2
 *
 * @return void
 */
function do_biosascomponentmigration()
{
    global $DB;

   //BIOS as a component
    if (
        $DB->tableExists('glpi_plugin_glpiinventory_inventorycomputercomputers') &&
        ($DB->fieldExists('glpi_plugin_glpiinventory_inventorycomputercomputers', 'bios_date') ||
        $DB->fieldExists('glpi_plugin_glpiinventory_inventorycomputercomputers', 'bios_version') ||
        $DB->fieldExists('glpi_plugin_glpiinventory_inventorycomputercomputers', 'bios_manufacturers_id'))
    ) {
        $bioses = [];
        //retrieve exiting
        $bios_iterator = $DB->request([
            'SELECT' => [
                'computers_id',
                'bios_date',
                'bios_version',
                'bios_manufacturers_id',
                'glpi_manufacturers.name AS mname'
            ],
            'FROM' => 'glpi_plugin_glpiinventory_inventorycomputercomputers',
            'LEFT JOIN' => [
                'glpi_manufacturers' => [
                    'FKEY' => [
                        'glpi_plugin_glpiinventory_inventorycomputercomputers' => 'bios_manufacturers_id',
                        'glpi_manufacturers' => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                [
                    'OR' => [
                        'NOT' => ['bios_date' => null],
                        'AND' => [
                            'NOT' => ['bios_version' => null],
                            'bios_version' => ['!=', '']
                        ],
                        'bios_manufacturers_id' => ['!=', 0]
                    ]
                ]
            ]
        ]);

        $deviceBios = new DeviceFirmware();
        $item_DeviceBios  = new Item_DeviceFirmware();
        foreach ($bios_iterator as $data) {
            if (empty($data['bios_date'])) {
                continue; // Ignore invalid dates
            }

            $key = md5($data['bios_date'] . $data['bios_version'] . $data['bios_manufacturers_id']);
            if (!isset($bioses[$key])) {
                //look for an existing BIOS in the database
                $iterator = $DB->request([
                 'SELECT' => 'id',
                 'FROM'   => 'glpi_devicefirmwares',
                 'WHERE'  => [
                  'date'               => $data['bios_date'],
                  'version'            => $data['bios_version'],
                  'manufacturers_id'   => $data['bios_manufacturers_id']
                 ],
                 'START'  => 0,
                 'LIMIT'  => 1
                ]);
                if (count($iterator)) {
                     $existing = $iterator->current();
                     $bioses[$key] = $existing['id'];
                } else {
                    $designation = sprintf(
                        __('%1$s BIOS'),
                        $data['mname']
                    );

                   //not found in database, create it
                    $deviceBios->add(
                        [
                        'designation'        => $designation,
                        'date'               => $data['bios_date'],
                        'version'            => $data['bios_version'],
                        'manufacturers_id'   => $data['bios_manufacturers_id']
                        ]
                    );
                    $bioses[$key] = $deviceBios->getID();
                }
            }

           //attach found/created component to computer
            $item_DeviceBios->add(
                [
                'items_id'           => $data['computers_id'],
                'itemtype'           => 'Computer',
                'devicefirmwares_id' => $bioses[$key],
                'is_dynamic'         => 1
                ]
            );
        }
    }
}


/**
 * Manage the computer inventory staistics part migration
 *
 * @param object $migration
 */
function do_computerstat_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_inventorycomputerstats
    */

    if (!$DB->tableExists("glpi_plugin_glpiinventory_inventorycomputerstats")) {
        $a_table = [];
        $a_table['name'] = 'glpi_plugin_glpiinventory_inventorycomputerstats';
        $a_table['oldname'] = [];

        $a_table['fields']  = [];
        $a_table['fields']['id']      = ['type'    => "smallint unsigned NOT NULL AUTO_INCREMENT",
                                                         'value'   => ''];
        $a_table['fields']['day']     = ['type'    => "smallint NOT NULL DEFAULT '0'",
                                                         'value'   => ''];
        $a_table['fields']['hour']    = ['type'    => "tinyint NOT NULL DEFAULT '0'",
                                                         'value'   => ''];
        $a_table['fields']['counter'] = ['type'    => 'integer',
                                                         'value'   => null];

        $a_table['oldfields']  = [];

        $a_table['renamefields'] = [];

        $a_table['keys']   = [];

        $a_table['oldkeys'] = [];

        migratePluginTables($migration, $a_table);

        require_once(PLUGIN_GLPI_INVENTORY_DIR . "/inc/inventorycomputerstat.class.php");
        PluginGlpiinventoryInventoryComputerStat::init();
    }
}


/**
 * Manage the configuration log fields (for network equipment and printer)
 * part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_configlogfield_migration($migration)
{
    global $DB;

   /*
    * glpi_plugin_glpiinventory_configlogfields
    */
    $newTable = "glpi_plugin_glpiinventory_configlogfields";
    $migration->renameTable(
        "glpi_plugin_glpiinventory_config_snmp_history",
        $newTable
    );
    $migration->renameTable(
        "glpi_plugin_fusinvsnmp_configlogfields",
        $newTable
    );
    renamePluginFields($migration, $newTable);
    if (!$DB->tableExists($newTable)) {
        $query = "CREATE TABLE `" . $newTable . "` (
                  `id` int unsigned NOT NULL AUTO_INCREMENT,
                   PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->query($query);
    }
    $migration->changeField(
        $newTable,
        "ID",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "id",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "plugin_glpiinventory_mappings_id",
        "plugin_glpiinventory_mappings_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "days",
        "days",
        "int NOT NULL DEFAULT '-1'"
    );
    $migration->migrationOneTable($newTable);
    $migration->changeField(
        $newTable,
        "ID",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "field",
        "plugin_glpiinventory_mappings_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->migrationOneTable($newTable);
    $migration->addField(
        $newTable,
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->addField(
        $newTable,
        "plugin_glpiinventory_mappings_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "days",
        "int NOT NULL DEFAULT '-1'"
    );
    $migration->addKey(
        $newTable,
        "plugin_glpiinventory_mappings_id"
    );
    $migration->migrationOneTable($newTable);
    $DB->listFields($newTable, false);
}


/**
 * Manage the network port part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_networkport_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_networkportconnectionlogs
    */
    $newTable = "glpi_plugin_glpiinventory_networkportconnectionlogs";
    $migration->renameTable(
        "glpi_plugin_fusinvsnmp_networkportconnectionlogs",
        $newTable
    );
    renamePluginFields($migration, $newTable);

    if (!$DB->tableExists($newTable)) {
        $DB->query('CREATE TABLE `' . $newTable . '` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC');
    }
    $migration->changeField(
        $newTable,
        "ID",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "id",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "date",
        "date_mod",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "date_mod",
        "date_mod",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "creation",
        "creation",
        "tinyint NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "FK_port_source",
        "networkports_id_source",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "networkports_id_source",
        "networkports_id_source",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "FK_port_destination",
        "networkports_id_destination",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "networkports_id_destination",
        "networkports_id_destination",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "plugin_glpiinventory_agentprocesses_id",
        "plugin_glpiinventory_agentprocesses_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->dropField($newTable, "process_number");
    $migration->migrationOneTable($newTable);
    $migration->addField(
        $newTable,
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->addField(
        $newTable,
        "date_mod",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "creation",
        "tinyint NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "networkports_id_source",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "networkports_id_destination",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "plugin_glpiinventory_agentprocesses_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addKey(
        $newTable,
        ["networkports_id_source",
                              "networkports_id_destination",
                              "plugin_glpiinventory_agentprocesses_id"],
        "networkports_id_source"
    );
    $migration->addKey(
        $newTable,
        "date_mod"
    );
    $migration->migrationOneTable($newTable);
    $DB->listFields($newTable, false);

   /*
    * Table glpi_plugin_glpiinventory_networkporttypes
    */
    $newTable = "glpi_plugin_glpiinventory_networkporttypes";
    $migration->renameTable(
        "glpi_plugin_fusinvsnmp_networkporttypes",
        $newTable
    );
    renamePluginFields($migration, $newTable);

    if (!$DB->tableExists($newTable)) {
        $query = "CREATE TABLE `" . $newTable . "` (
                     `id` int unsigned NOT NULL AUTO_INCREMENT,
                      PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->query($query);
    }
    $migration->changeField(
        $newTable,
        "id",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "name",
        "name",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "number",
        "number",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "othername",
        "othername",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "import",
        "import",
        "tinyint NOT NULL DEFAULT '0'"
    );
    $migration->migrationOneTable($newTable);
    $migration->addField(
        $newTable,
        "name",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "number",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "othername",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "import",
        "tinyint NOT NULL DEFAULT '0'"
    );
    $migration->migrationOneTable($newTable);
    $DB->listFields($newTable, false);

   /*
    * glpi_plugin_glpiinventory_networkports
    */
    $newTable = "glpi_plugin_glpiinventory_networkports";
    $migration->renameTable(
        "glpi_plugin_fusinvsnmp_networkports",
        $newTable
    );

    $migration->renameTable(
        "glpi_plugin_tracker_networking_ports",
        $newTable
    );
    renamePluginFields($migration, $newTable);

    if (!$DB->tableExists($newTable)) {
        $DB->query('CREATE TABLE `' . $newTable . '` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`id`)
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC');
    }
    $migration->changeField(
        $newTable,
        "id",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "networkports_id",
        "networkports_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "ifmtu",
        "ifmtu",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "ifspeed",
        "ifspeed",
        "bigint NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "ifinternalstatus",
        "ifinternalstatus",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "ifconnectionstatus",
        "ifconnectionstatus",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "iflastchange",
        "iflastchange",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "ifinoctets",
        "ifinoctets",
        "bigint NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "ifinerrors",
        "ifinerrors",
        "bigint NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "ifoutoctets",
        "ifoutoctets",
        "bigint NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "ifouterrors",
        "ifouterrors",
        "bigint NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "ifstatus",
        "ifstatus",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "mac",
        "mac",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "ifdescr",
        "ifdescr",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "portduplex",
        "portduplex",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "trunk",
        "trunk",
        "tinyint NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "lastup",
        "lastup",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->migrationOneTable($newTable);
    $migration->changeField(
        $newTable,
        "ID",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "FK_networking_ports",
        "networkports_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "ifmac",
        "mac",
        "varchar(255) DEFAULT NULL"
    );
    $migration->dropKey(
        $newTable,
        "FK_networking_ports"
    );
    $migration->migrationOneTable($newTable);
    $migration->addField(
        $newTable,
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->addField(
        $newTable,
        "networkports_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "ifmtu",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "ifspeed",
        "bigint NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "ifinternalstatus",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "ifconnectionstatus",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "iflastchange",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "ifinoctets",
        "bigint NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "ifinerrors",
        "bigint NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "ifoutoctets",
        "bigint NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "ifouterrors",
        "bigint NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "ifstatus",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "mac",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "ifdescr",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "ifalias",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "portduplex",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "trunk",
        "tinyint NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "lastup",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->addKey(
        $newTable,
        "networkports_id"
    );
    $migration->migrationOneTable($newTable);
    $DB->listFields($newTable, false);

   /*
    * Table glpi_plugin_glpiinventory_networkportlogs
    */
    $newTable = "glpi_plugin_glpiinventory_networkportlogs";
    if ($DB->tableExists("glpi_plugin_tracker_snmp_history")) {
       // **** Update history
        update213to220_ConvertField($migration);

       // **** Migration network history connections
        $iterator = $DB->request([
         'FROM'   => 'glpi_plugin_tracker_snmp_history',
         'COUNT'  => 'cpt',
         'WHERE'  => ['Field' => 0]
        ]);
        $datas = $iterator->current();
        $nb = $datas['cpt'];

       //echo "Move Connections history to another table...";

        for ($i = 0; $i < $nb; $i = $i + 500) {
            $migration->displayMessage("$i / $nb");
            $iterator = $DB->request([
              'FROM'   => 'glpi_plugin_tracker_snmp_history',
              'WHERE'  => ['Field' => 0],
              'ORDER'  => ['FK_process DESC', 'date_mod DESC'],
              'LIMIT'  => 500
            ]);
            foreach ($iterator as $thread_connection) {
                 $input = [];
                 $input['process_number'] = $thread_connection['FK_process'];
                 $input['date'] = $thread_connection['date_mod'];
                if (
                    ($thread_connection["old_device_ID"] != "0")
                    or ($thread_connection["new_device_ID"] != "0")
                ) {
                    if ($thread_connection["old_device_ID"] != "0") {
                      // disconnection
                        $input['creation'] = '0';
                    } elseif ($thread_connection["new_device_ID"] != "0") {
                    // connection
                        $input['creation'] = '1';
                    }
                    $input['FK_port_source'] = $thread_connection["FK_ports"];
                    $dataPort = [];
                    $portvalue = null;
                    if ($thread_connection["old_device_ID"] != "0") {
                           $portvalue = $thread_connection['old_value'];
                    } elseif ($thread_connection["new_device_ID"] != "0") {
                        $portvalue = $thread_connection['new_value'];
                    }
                    if ($portvalue != null) {
                            $port_iterator = $DB->request([
                               'FROM'   => 'glpi_networkports',
                               'WHERE'  => ['mac' => $thread_connection['old_value']],
                               'LIMIT'  => 1
                            ]);
                            $dataPort = $port_iterator->current();
                    }
                    if (isset($dataPort['id'])) {
                           $input['FK_port_destination'] = $dataPort['id'];
                    } else {
                          $input['FK_port_destination'] = 0;
                    }

                    $DB->insert(
                        'glpi_plugin_fusinvsnmp_networkportconnectionlogs',
                        [
                        'date_mod'                    => $input['date'],
                        'creation'                    => $input['creation'],
                        'networkports_id_source'      => $input['FK_port_source'],
                        'networkports_id_destination' => $input['FK_port_destination']
                        ]
                    );
                }
            }
        }

        $DB->delete(
            'glpi_plugin_tracker_snmp_history',
            [
            'Field'  => 0,
            'OR'     => [
               'old_device_ID'   => ['!=', 0],
               'new_device_ID'   => ['!=', 0],
            ]
            ]
        );
        $migration->displayMessage("$nb / $nb");
    }

    $migration->renameTable(
        "glpi_plugin_fusinvsnmp_networkportlogs",
        $newTable
    );

    $migration->renameTable(
        "glpi_plugin_tracker_snmp_history",
        $newTable
    );
    renamePluginFields($migration, $newTable);

    if (!$DB->tableExists($newTable)) {
        $query = "CREATE TABLE `" . $newTable . "` (
                     `id` int unsigned NOT NULL AUTO_INCREMENT,
                      PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->query($query);
    }
    $migration->changeField(
        $newTable,
        "id",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "networkports_id",
        "networkports_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "plugin_glpiinventory_mappings_id",
        "plugin_glpiinventory_mappings_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "date_mod",
        "date_mod",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "value_old",
        "value_old",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "value_new",
        "value_new",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "plugin_glpiinventory_agentprocesses_id",
        "plugin_glpiinventory_agentprocesses_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->migrationOneTable($newTable);
    $migration->changeField(
        $newTable,
        "ID",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "FK_ports",
        "networkports_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "plugin_glpiinventory_mappings_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->migrationOneTable($newTable);

   // Update with mapping
    if ($DB->fieldExists($newTable, "Field")) {
        $pfMapping = new PluginGlpiinventoryMapping();
        $iterator = $DB->request([
         'FROM'      => $newTable,
         'GROUPBY'   => 'Field'
        ]);
        foreach ($iterator as $data) {
            $mapping = 0;
            if ($mapping = $pfMapping->get("NetworkEquipment", $data['Field'])) {
                $DB->update(
                    $newTable,
                    [
                    'plugin_glpiinventory_mappings_id'   => $mapping['id']
                    ],
                    [
                    'Field'                                => $data['Field'],
                    'plugin_glpiinventory_mappings_id'   => ['!=', $mapping['id']]
                    ]
                );
            }
        }
    }
    $migration->dropField(
        $newTable,
        "Field"
    );
    $migration->changeField(
        $newTable,
        "old_value",
        "value_old",
        "varchar(255) DEFAULT NULL"
    );
    $migration->dropField(
        $newTable,
        "old_device_type"
    );
    $migration->dropField(
        $newTable,
        "old_device_ID"
    );
    $migration->changeField(
        $newTable,
        "new_value",
        "value_new",
        "varchar(255) DEFAULT NULL"
    );
    $migration->dropField(
        $newTable,
        "new_device_type"
    );
    $migration->dropField(
        $newTable,
        "new_device_ID"
    );
    $migration->dropField($newTable, "FK_process");
    $migration->dropKey($newTable, "FK_process");
    $migration->dropKey(
        $newTable,
        "FK_ports"
    );
    $migration->migrationOneTable($newTable);
    $migration->addField(
        $newTable,
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->addField(
        $newTable,
        "networkports_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "plugin_glpiinventory_mappings_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "date_mod",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "value_old",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "value_new",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "plugin_glpiinventory_agentprocesses_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addKey(
        $newTable,
        ["networkports_id", "date_mod"],
        "networkports_id"
    );
    $migration->addKey(
        $newTable,
        "plugin_glpiinventory_mappings_id"
    );
    $migration->addKey(
        $newTable,
        "plugin_glpiinventory_agentprocesses_id"
    );
    $migration->addKey(
        $newTable,
        "date_mod"
    );
    $migration->migrationOneTable($newTable);
    $DB->listFields($newTable, false);

   /*
    * Update networports to convert itemtype 5153 to PluginGlpiinventoryUnknownDevice
    */
    $DB->update(
        'glpi_networkports',
        [
         'itemtype'  => 'PluginGlpiinventoryUnmanaged'
        ],
        [
         'itemtype'  => [5153, 'PluginGlpiinventoryUnknownDevice']
        ]
    );

    $DB->update(
        'glpi_networkports',
        [
         'itemtype'  => 'PluginGlpiinventoryTask'
        ],
        [
         'itemtype'  => 5166
        ]
    );

   /*
    * Clean for port orphelin
    */
   //networkports with item_type = 0
    $NetworkPort = new NetworkPort();
    $NetworkPort_Vlan = new NetworkPort_Vlan();
    $NetworkPort_NetworkPort = new NetworkPort_NetworkPort();
    $a_networkports = $NetworkPort->find(['itemtype' => '']);
    foreach ($a_networkports as $data) {
        if ($NetworkPort_NetworkPort->getFromDBForNetworkPort($data['id'])) {
            $NetworkPort_NetworkPort->delete($NetworkPort_NetworkPort->fields);
        }
        $a_vlans = $NetworkPort_Vlan->find(['networkports_id' => $data['id']]);
        foreach ($a_vlans as $a_vlan) {
            $NetworkPort_Vlan->delete($a_vlan);
        }
        $NetworkPort->delete($data, 1);
    }

   /*
    *  Clean old ports deleted but have some information in SNMP tables
    */
    $iterator = $DB->request([
        'SELECT' => 'glpi_plugin_glpiinventory_networkports.id',
        'FROM' => 'glpi_plugin_glpiinventory_networkports',
        'LEFT JOIN' => [
            'glpi_networkports' => [
                'FKEY' => [
                    'glpi_plugin_glpiinventory_networkports' => 'networkports_id',
                    'glpi_networkports' => 'id'
                ]
            ],
            'glpi_networkequipments' => [
                'FKEY' => [
                    'glpi_networkports' => 'items_id',
                    'glpi_networkequipments' => 'id'
                ]
            ]
        ],
        'WHERE' => [
            'glpi_networkequipments.id' => null
        ]
    ]);
    foreach ($iterator as $data) {
        $DB->delete(
            'glpi_plugin_glpiinventory_networkports',
            [
                'id'  => $data['id']
            ]
        );
    }

    changeDisplayPreference("5162", "PluginFusinvsnmpNetworkPortLog");
}


/**
 * Manage the printer part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_printer_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_printers
    */
    $newTable = "glpi_plugin_glpiinventory_printers";
    $migration->renameTable(
        "glpi_plugin_fusinvsnmp_printers",
        $newTable
    );

    $migration->renameTable(
        "glpi_plugin_tracker_printers",
        $newTable
    );
    renamePluginFields($migration, $newTable);

    if (!$DB->tableExists($newTable)) {
        $DB->query('CREATE TABLE `' . $newTable . '` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`id`)
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC');
    }
    $migration->changeField(
        $newTable,
        "id",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "printers_id",
        "printers_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "sysdescr",
        "sysdescr",
        "text DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "plugin_fusinvsnmp_configsecurities_id",
        "plugin_glpiinventory_configsecurities_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "plugin_glpiinventory_configsecurities_id",
        "plugin_glpiinventory_configsecurities_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "frequence_days",
        "frequence_days",
        "int NOT NULL DEFAULT '1'"
    );
    $migration->changeField(
        $newTable,
        "last_fusioninventory_update",
        "last_inventory_update",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->migrationOneTable($newTable);
    $migration->changeField(
        $newTable,
        "ID",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "FK_printers",
        "printers_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "FK_snmp_connection",
        "plugin_glpiinventory_configsecurities_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "last_tracker_update",
        "last_inventory_update",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->dropKey(
        $newTable,
        "FK_printers"
    );
    $migration->dropKey(
        $newTable,
        "FK_snmp_connection"
    );
    $migration->dropKey(
        $newTable,
        "plugin_glpiinventory_snmpmodels_id"
    );
    $migration->migrationOneTable($newTable);
    $migration->dropField(
        $newTable,
        "plugin_fusinvsnmp_models_id"
    );
    $migration->dropField(
        $newTable,
        "plugin_glpiinventory_snmpmodels_id"
    );
    $migration->dropField(
        $newTable,
        "FK_model_infos"
    );
    $migration->addField(
        $newTable,
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->addField(
        $newTable,
        "printers_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "sysdescr",
        "text DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "plugin_glpiinventory_configsecurities_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "frequence_days",
        "int NOT NULL DEFAULT '1'"
    );
    $migration->addField(
        $newTable,
        "last_inventory_update",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "serialized_inventory",
        "longblob"
    );
   /*$migration->addKey($newTable,
      "plugin_glpiinventory_configsecurities_id");*/
    $migration->addKey(
        $newTable,
        "printers_id"
    );
    $migration->migrationOneTable($newTable);
    $DB->listFields($newTable, false);

   /*
    * Table glpi_plugin_glpiinventory_printerlogs
    */
    $newTable = "glpi_plugin_glpiinventory_printerlogs";
    $migration->renameTable(
        "glpi_plugin_fusinvsnmp_printerlogs",
        $newTable
    );
    $migration->renameTable(
        "glpi_plugin_tracker_printers_history",
        $newTable
    );
    renamePluginFields($migration, $newTable);

    if (!$DB->tableExists($newTable)) {
        $DB->query('CREATE TABLE `' . $newTable . '` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`id`)
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC');
    }
    $migration->changeField(
        $newTable,
        "id",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "printers_id",
        "printers_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "date",
        "date",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "pages_total",
        "pages_total",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "pages_n_b",
        "pages_n_b",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "pages_color",
        "pages_color",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "pages_recto_verso",
        "pages_recto_verso",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "scanned",
        "scanned",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "pages_total_print",
        "pages_total_print",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "pages_n_b_print",
        "pages_n_b_print",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "pages_color_print",
        "pages_color_print",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "pages_total_copy",
        "pages_total_copy",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "pages_n_b_copy",
        "pages_n_b_copy",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "pages_color_copy",
        "pages_color_copy",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "pages_total_fax",
        "pages_total_fax",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->migrationOneTable($newTable);
    $migration->changeField(
        $newTable,
        "ID",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "FK_printers",
        "printers_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->migrationOneTable($newTable);
    $migration->addField(
        $newTable,
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->addField(
        $newTable,
        "printers_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "date",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "pages_total",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "pages_n_b",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "pages_color",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "pages_recto_verso",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "scanned",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "pages_total_print",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "pages_n_b_print",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "pages_color_print",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "pages_total_copy",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "pages_n_b_copy",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "pages_color_copy",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "pages_total_fax",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addKey(
        $newTable,
        ["printers_id", "date"],
        "printers_id"
    );
    $migration->migrationOneTable($newTable);
    $DB->listFields($newTable, false);

   /*
    *  glpi_plugin_glpiinventory_printercartridges
    */
    $newTable = "glpi_plugin_glpiinventory_printercartridges";
    $migration->renameTable(
        "glpi_plugin_fusinvsnmp_printercartridges",
        $newTable
    );
    $migration->renameTable(
        "glpi_plugin_tracker_printers_cartridges",
        $newTable
    );
    renamePluginFields($migration, $newTable);

    if (!$DB->tableExists($newTable)) {
        $DB->query('CREATE TABLE `' . $newTable . '` (
                        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`id`)
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC');
    }
    $migration->changeField(
        $newTable,
        "id",
        "id",
        "bigint unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "printers_id",
        "printers_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "plugin_glpiinventory_mappings_id",
        "plugin_glpiinventory_mappings_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "cartridges_id",
        "cartridges_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "state",
        "state",
        "int NOT NULL DEFAULT '100'"
    );
    $migration->migrationOneTable($newTable);
    $migration->changeField(
        $newTable,
        "ID",
        "id",
        "bigint unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "FK_printers",
        "printers_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "FK_cartridges",
        "cartridges_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "plugin_glpiinventory_mappings_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->migrationOneTable($newTable);

   // Update with mapping
    if ($DB->fieldExists($newTable, "object_name")) {
        $iterator = $DB->request([
         'FROM'   => $newTable,
         'GROUP'  => 'object_name'
        ]);
        foreach ($iterator as $data) {
            $pfMapping = new PluginGlpiinventoryMapping();
            $mapping = 0;
            if (($mapping = $pfMapping->get("Printer", $data['object_name']))) {
                $DB->update(
                    $newTable,
                    [
                    'plugin_glpiinventory_mappings_id'   => $mapping['id']
                    ],
                    [
                    'object_name'                          => $data['object_name']
                    ]
                );
            }
        }
    }
    $migration->dropField(
        $newTable,
        "object_name"
    );
    $migration->migrationOneTable($newTable);
    $migration->addField(
        $newTable,
        "id",
        "bigint unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->addField(
        $newTable,
        "printers_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "plugin_glpiinventory_mappings_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "cartridges_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "state",
        "int NOT NULL DEFAULT '100'"
    );
    $migration->addKey(
        $newTable,
        "printers_id"
    );
    $migration->addKey(
        $newTable,
        "plugin_glpiinventory_mappings_id"
    );
    $migration->addKey(
        $newTable,
        "cartridges_id"
    );
    $migration->migrationOneTable($newTable);
    $DB->listFields($newTable, false);

   /*
    * Clean for printer more information again in DB when printer is purged
    */
   //echo "Clean for printer more information again in DB when printer is purged\n";
    $iterator = $DB->request([
      'SELECT'    => 'glpi_plugin_glpiinventory_printers.id',
      'FROM'      => 'glpi_plugin_glpiinventory_printers',
      'LEFT JOIN' => [
         'glpi_printers'   => [
            'FKEY'   => [
               'glpi_plugin_glpiinventory_printers' => 'printers_id',
               'glpi_printers'                        => 'id'
            ]
         ]
      ],
      'WHERE'     => ['glpi_printers.id' => null]
    ]);
    if (count($iterator)) {
        $delete = $DB->buildDelete(
            'glpi_plugin_glpiinventory_printers',
            [
            'id' => new \QueryParam()
            ]
        );
        $stmt = $DB->prepare($delete);
        foreach ($iterator as $data) {
            $stmt->bind_param('s', $data['id']);
            $DB->executeStatement($stmt);
        }
        mysqli_stmt_close($stmt);
    }

   /*
    *  Clean printer cartridge not deleted with the printer associated
    */
   //echo "Clean printer cartridge not deleted with the printer associated\n";
    $iterator = $DB->request([
      'SELECT'    => 'glpi_plugin_glpiinventory_printercartridges.id',
      'FROM'      => 'glpi_plugin_glpiinventory_printercartridges',
      'LEFT JOIN' => [
         'glpi_printers'   => [
            'FKEY'   => [
               'glpi_plugin_glpiinventory_printercartridges' => 'printers_id',
               'glpi_printers'                                 => 'id'
            ]
         ]
      ],
      'WHERE'     => ['glpi_printers.id' => null]
    ]);

    $stmt = null;
    foreach ($iterator as $data) {
        $DB->delete(
            'glpi_plugin_glpiinventory_printercartridges',
            [
            'id' => $data['id']
            ]
        );

        changeDisplayPreference("5168", "PluginGlpiinventoryPrinterLogReport");
        changeDisplayPreference(
            "PluginFusinvsnmpPrinterLogReport",
            "PluginGlpiinventoryPrinterLogReport"
        );
        changeDisplayPreference("5156", "PluginFusinvsnmpPrinterCartridge");
    }

   /*
    * Manage devices with is_dynamic
    */
    $iterator = $DB->request(['FROM' => 'glpi_plugin_glpiinventory_printers']);
    if (count($iterator)) {
        $update = $DB->buildUpdate(
            'glpi_printers',
            [
            'is_dynamic'   => 1
            ],
            [
            'id'           => new \QueryParam()
            ]
        );
        $stmt = $DB->prepare($update);
        foreach ($iterator as $data) {
            $stmt->bind_param('s', $data['printers_id']);
            $DB->executeStatement($stmt);
        }
        mysqli_stmt_close($stmt);
    }

    $migration->displayMessage("Clean printers");
   /*
    * Remove / at the end of printers (bugs in older versions of agents.
    */
    $printer = new Printer();
    $iterator = $DB->request([
      'FROM'   => 'glpi_printers',
      'WHERE'  => ['serial' => ['LIKE', '%/']]
    ]);
    foreach ($iterator as $data) {
        $cleanSerial = preg_replace('/\/$/', '', $data['serial']);
        $iterator2 = $DB->request([
         'FROM'   => 'glpi_printers',
         'WHERE'  => ['serial' => $cleanSerial],
         'LIMIT'  => 1
        ]);
        if (!count($iterator)) {
            $input = [
              'id'     => $data['id'],
              'serial' => $cleanSerial
            ];
            $printer->update($input);
        }
    }
}


/**
 * Manage the network equipment part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_networkequipment_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_networkequipments
    */
    $newTable = "glpi_plugin_glpiinventory_networkequipments";
    $migration->renameTable(
        "glpi_plugin_fusinvsnmp_networkequipments",
        $newTable
    );
    $migration->renameTable(
        "glpi_plugin_tracker_networking",
        $newTable
    );
    renamePluginFields($migration, $newTable);

    if (!$DB->tableExists($newTable)) {
        $DB->query('CREATE TABLE `' . $newTable . '` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`id`)
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC');
    }
    $migration->changeField(
        $newTable,
        "id",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "networkequipments_id",
        "networkequipments_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "sysdescr",
        "sysdescr",
        "text DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "plugin_glpiinventory_configsecurities_id",
        "plugin_glpiinventory_configsecurities_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "uptime",
        "uptime",
        "varchar(255) NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "cpu",
        "cpu",
        "int NOT NULL DEFAULT '0' COMMENT '%'"
    );
    $migration->changeField(
        $newTable,
        "memory",
        "memory",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "last_fusioninventory_update",
        "last_inventory_update",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "last_PID_update",
        "last_PID_update",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->migrationOneTable($newTable);
    $migration->changeField(
        $newTable,
        "ID",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "FK_networking",
        "networkequipments_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "FK_snmp_connection",
        "plugin_glpiinventory_configsecurities_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "last_tracker_update",
        "last_inventory_update",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "plugin_fusinvsnmp_configsecurities_id",
        "plugin_glpiinventory_configsecurities_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->dropKey(
        $newTable,
        "FK_networking"
    );
    $migration->dropKey(
        $newTable,
        "FK_model_infos"
    );
    $migration->dropKey(
        $newTable,
        "plugin_glpiinventory_snmpmodels_id"
    );
    $migration->migrationOneTable($newTable);
    $migration->dropField(
        $newTable,
        "plugin_glpiinventory_snmpmodels_id"
    );
    $migration->dropField(
        $newTable,
        "plugin_fusinvsnmp_models_id"
    );
    $migration->dropField(
        $newTable,
        "FK_model_infos"
    );
    $migration->addField(
        $newTable,
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->addField(
        $newTable,
        "networkequipments_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "sysdescr",
        "text DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "plugin_glpiinventory_configsecurities_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "uptime",
        "varchar(255) NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "cpu",
        "int NOT NULL DEFAULT '0' COMMENT '%'"
    );
    $migration->addField(
        $newTable,
        "memory",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "last_inventory_update",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "last_PID_update",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "serialized_inventory",
        "longblob"
    );
    $migration->addKey(
        $newTable,
        "networkequipments_id"
    );
    $migration->migrationOneTable($newTable);
    $DB->listFields($newTable, false);

   /*
    * glpi_plugin_glpiinventory_networkequipmentips
    * Removed in 0.84, but required here for update, we drop in edn of this function
    */
    if (
        $DB->tableExists("glpi_plugin_glpiinventory_networkequipmentips")
           || $DB->tableExists("glpi_plugin_fusinvsnmp_networkequipmentips")
           || $DB->tableExists("glpi_plugin_tracker_networking_ifaddr")
    ) {
        $newTable = "glpi_plugin_glpiinventory_networkequipmentips";
        $migration->renameTable(
            "glpi_plugin_fusinvsnmp_networkequipmentips",
            $newTable
        );
        $migration->renameTable(
            "glpi_plugin_tracker_networking_ifaddr",
            $newTable
        );
        renamePluginFields($migration, $newTable);

        if (!$DB->tableExists($newTable)) {
            $DB->query('CREATE TABLE `' . $newTable . '` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`id`)
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC');
        }
        $migration->changeField(
            $newTable,
            "id",
            "id",
            "int unsigned NOT NULL AUTO_INCREMENT"
        );
        $migration->changeField(
            $newTable,
            "networkequipments_id",
            "networkequipments_id",
            "int unsigned NOT NULL DEFAULT '0'"
        );
        $migration->changeField(
            $newTable,
            "ip",
            "ip",
            "varchar(255) DEFAULT NULL"
        );
        $migration->migrationOneTable($newTable);
        $migration->changeField(
            $newTable,
            "ID",
            "id",
            "int unsigned NOT NULL AUTO_INCREMENT"
        );
        $migration->changeField(
            $newTable,
            "FK_networking",
            "networkequipments_id",
            "int unsigned NOT NULL DEFAULT '0'"
        );
        $migration->changeField(
            $newTable,
            "ifaddr",
            "ip",
            "varchar(255) DEFAULT NULL"
        );
        $migration->dropKey(
            $newTable,
            "ifaddr"
        );
        $migration->migrationOneTable($newTable);
        $migration->addField(
            $newTable,
            "id",
            "int unsigned NOT NULL AUTO_INCREMENT"
        );
        $migration->addField(
            $newTable,
            "networkequipments_id",
            "int unsigned NOT NULL DEFAULT '0'"
        );
        $migration->addField(
            $newTable,
            "ip",
            "varchar(255) DEFAULT NULL"
        );
        $migration->addKey(
            $newTable,
            "ip"
        );
        $migration->addKey(
            $newTable,
            "networkequipments_id"
        );
        $migration->migrationOneTable($newTable);
        $DB->listFields($newTable, false);
    }

   /*
    * Move networkequipment IPs to net system
    */
    if ($DB->tableExists("glpi_plugin_glpiinventory_networkequipmentips")) {
        $networkPort = new NetworkPort();
        $networkName = new NetworkName();
        $ipAddress = new IPAddress();
        $networkEquipment = new NetworkEquipment();

        $iterator = $DB->request(['FROM' => 'glpi_plugin_glpiinventory_networkequipments']);
        foreach ($iterator as $data) {
            if ($networkEquipment->getFromDB($data['networkequipments_id'])) {
                $oldtableip = [];
                $iterator2 = $DB->request([
                 'FROM'   => 'glpi_plugin_glpiinventory_networkequipmentips',
                 'WHERE'  => ['networkequipments_id' => $data['networkequipments_id']]
                ]);
                foreach ($iterator2 as $dataIP) {
                     $oldtableip[$dataIP['ip']] = $dataIP['ip'];
                }

                // Get actual IP defined
                $networknames_id = 0;
                $a_ports = $networkPort->find(
                    ['itemtype'           => 'NetworkEquipment',
                    'items_id'           => $data['networkequipments_id'],
                    'instantiation_type' => 'NetworkPortAggregate',
                    'name'               => 'management'],
                    [],
                    1
                );

                foreach ($a_ports as $a_port) {
                     $a_networknames = $networkName->find(
                         ['itemtype' => 'NetworkPort',
                         'items_id' => $a_port['id']]
                     );
                    foreach ($a_networknames as $a_networkname) {
                         $networknames_id = $a_networkname['id'];
                         $a_ipaddresses = $ipAddress->find(
                             ['itemtype' => 'NetworkName',
                              'items_id' => $a_networkname['id']]
                         );
                        foreach ($a_ipaddresses as $a_ipaddress) {
                            if (isset($oldtableip[$a_ipaddress['name']])) {
                                 unset($oldtableip[$a_ipaddress['name']]);
                            } else {
                                 $ipAddress->delete($a_ipaddress, 1);
                            }
                        }
                    }
                }

                // Update
                foreach ($oldtableip as $ip) {
                    $input = [];
                    $input['itemtype']   = "NetworkName";
                    $input['items_id']   = $networknames_id;
                    $input['name']       = $ip;
                    $input['is_dynamic'] = 1;
                    $ipAddress->add($input);
                }
            }
        }
    }

   /*
    * Clean for switch more informations again in DB when switch is purged
    */
   //echo "Clean for switch more informations again in DB when switch is purged\n";
    $iterator = $DB->request([
      'SELECT'    => 'glpi_plugin_glpiinventory_networkequipments.id',
      'FROM'      => 'glpi_plugin_glpiinventory_networkequipments',
      'LEFT JOIN' => [
         'glpi_networkequipments'   => [
            'FKEY'   => [
               'glpi_networkequipments'                        => 'id',
               'glpi_plugin_glpiinventory_networkequipments' => 'networkequipments_id'
            ]
         ]
      ],
      'WHERE'     => [
         'glpi_networkequipments.id' => null
      ]
    ]);
    if (count($iterator)) {
        $delete = $DB->buildDelete(
            'glpi_plugin_glpiinventory_networkequipments',
            [
            'id'  => new \QueryParam()
            ]
        );
        $stmt = $DB->prepare($delete);
        foreach ($iterator as $data) {
            $stmt->bind_param('s', $data['id']);
            $DB->executeStatement($stmt);
        }
    }

    changeDisplayPreference("5157", "PluginGlpiinventoryNetworkEquipment");
    changeDisplayPreference(
        "PluginFusinvsnmpNetworkEquipment",
        "PluginGlpiinventoryNetworkEquipment"
    );

   /*
    * Manage devices with is_dynamic
    */
    $iterator = $DB->request(['FROM' => 'glpi_plugin_glpiinventory_networkequipments']);
    if (count($iterator)) {
        $update = $DB->buildUpdate(
            'glpi_networkequipments',
            [
            'is_dynamic'   => 1
            ],
            [
            'id'           => new \QueryParam()
            ]
        );
        $stmt = $DB->prepare($update);
        foreach ($iterator as $data) {
            $stmt->bind_param('s', $data['networkequipments_id']);
            $DB->executeStatement($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}


/**
 * Manage the Config security (SNMP anthentication) part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_configsecurity_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_configsecurities
    */
   // TODO get info to create SNMP authentification with old values of Tracker plugin
    $newTable = "glpi_plugin_glpiinventory_configsecurities";
    $migration->renameTable(
        "glpi_plugin_fusinvsnmp_configsecurities",
        $newTable
    );
    $migration->renameTable(
        "glpi_plugin_tracker_snmp_connection",
        $newTable
    );
    renamePluginFields($migration, $newTable);

    if (!$DB->tableExists($newTable)) {
        $DB->query('CREATE TABLE `' . $newTable . '` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`id`)
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC');
    }
    $migration->changeField(
        $newTable,
        "id",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
      $migration->changeField(
          $newTable,
          "name",
          "name",
          "varchar(64) DEFAULT NULL"
      );
    $migration->changeField(
        $newTable,
        "snmpversion",
        "snmpversion",
        "varchar(8) NOT NULL DEFAULT '1'"
    );
    $migration->changeField(
        $newTable,
        "community",
        "community",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "username",
        "username",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "authentication",
        "authentication",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "auth_passphrase",
        "auth_passphrase",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "encryption",
        "encryption",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "priv_passphrase",
        "priv_passphrase",
        "varchar(255) DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "is_deleted",
        "is_deleted",
        "tinyint NOT NULL DEFAULT '0'"
    );
    $migration->migrationOneTable($newTable);
    $migration->changeField(
        $newTable,
        "ID",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "FK_snmp_version",
        "snmpversion",
        "varchar(8) NOT NULL DEFAULT '1'"
    );
    $migration->changeField(
        $newTable,
        "sec_name",
        "username",
        "varchar(255) DEFAULT NULL"
    );
    $migration->dropField(
        $newTable,
        "sec_level"
    );
    $migration->dropField(
        $newTable,
        "auth_protocol"
    );
    $migration->dropField(
        $newTable,
        "priv_protocol"
    );
    $migration->dropField(
        $newTable,
        "deleted"
    );
    $migration->migrationOneTable($newTable);
    $migration->addField(
        $newTable,
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->addField(
        $newTable,
        "name",
        "varchar(64) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "snmpversion",
        "varchar(8) NOT NULL DEFAULT '1'"
    );
    $migration->addField(
        $newTable,
        "community",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "username",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "authentication",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "auth_passphrase",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "encryption",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "priv_passphrase",
        "varchar(255) DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "is_deleted",
        "tinyint NOT NULL DEFAULT '0'"
    );
    $migration->addKey(
        $newTable,
        "snmpversion"
    );
    $migration->addKey(
        $newTable,
        "is_deleted"
    );
    $migration->migrationOneTable($newTable);
    $DB->listFields($newTable, false);

    changeDisplayPreference("5152", "PluginGlpiinventoryConfigSecurity");

   // Remove the protocols AES192 and AES256 because not managed in the agent
   // with the perl module NET-SNMP
    $DB->update(
        'glpi_plugin_glpiinventory_configsecurities',
        ['encryption' => 'AES128'],
        ['encryption' => ['AES192', 'AES256']]
    );
}


/**
 * Manage the discovery state part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_statediscovery_migration($migration)
{
    global $DB;

   /*
    *  glpi_plugin_glpiinventory_statediscoveries
    */
    $newTable = "glpi_plugin_glpiinventory_statediscoveries";
    $migration->renameTable(
        "glpi_plugin_fusinvsnmp_statediscoveries",
        $newTable
    );
    renamePluginFields($migration, $newTable);

    if (!$DB->tableExists($newTable)) {
        $DB->query("CREATE TABLE `" . $newTable . "` (
                     `id` int unsigned NOT NULL AUTO_INCREMENT,
                     PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
    }
    $migration->changeField(
        $newTable,
        "id",
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->changeField(
        $newTable,
        "plugin_glpiinventory_taskjob_id",
        "plugin_glpiinventory_taskjob_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "plugin_glpiinventory_agents_id",
        "agents_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "start_time",
        "start_time",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "end_time",
        "end_time",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "date_mod",
        "date_mod",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->changeField(
        $newTable,
        "threads",
        "threads",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "nb_ip",
        "nb_ip",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "nb_found",
        "nb_found",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "nb_error",
        "nb_error",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "nb_exists",
        "nb_exists",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->changeField(
        $newTable,
        "nb_import",
        "nb_import",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->migrationOneTable($newTable);
    $migration->addField(
        $newTable,
        "id",
        "int unsigned NOT NULL AUTO_INCREMENT"
    );
    $migration->addField(
        $newTable,
        "plugin_glpiinventory_taskjob_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "agents_id",
        "int unsigned NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "start_time",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "end_time",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "date_mod",
        "timestamp NULL DEFAULT NULL"
    );
    $migration->addField(
        $newTable,
        "threads",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "nb_ip",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "nb_found",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "nb_error",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "nb_exists",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->addField(
        $newTable,
        "nb_import",
        "int NOT NULL DEFAULT '0'"
    );
    $migration->migrationOneTable($newTable);
    $DB->listFields($newTable, false);
}


/**
 * Manage the computer license part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_computerlicense_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_computerlicenseinfos
    */
    if ($DB->tableExists("glpi_plugin_fusinvinventory_licenseinfos")) {
        $DB->update(
            'glpi_plugin_fusinvinventory_licenseinfos',
            [
            'softwarelicenses_id'   => 0
            ],
            [
            'softwarelicenses_id'   => null
            ]
        );
    }
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_computerlicenseinfos';
    $a_table['oldname'] = ['glpi_plugin_fusinvinventory_licenseinfos'];

    $a_table['fields']  = [];
    $a_table['fields']['id']                  = ['type'    => 'autoincrement',
                                                     'value'   => ''];
    $a_table['fields']['computers_id']        = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                     'value'   => null];
    $a_table['fields']['softwarelicenses_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                     'value'   => null];
    $a_table['fields']['name']                = ['type'    => 'string',
                                                     'value'   => null];
    $a_table['fields']['fullname']            = ['type'    => 'string',
                                                     'value'   => null];
    $a_table['fields']['serial']              = ['type'    => 'string',
                                                     'value'   => null];
    $a_table['fields']['is_trial']            = ['type'    => 'bool',
                                                     'value'   => null];
    $a_table['fields']['is_update']           = ['type'    => 'bool',
                                                     'value'   => null];
    $a_table['fields']['is_oem']              = ['type'    => 'bool',
                                                     'value'   => null];
    $a_table['fields']['activation_date']     = ['type'    => 'timestamp',
                                                     'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'name', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'fullname', 'name' => '', 'type' => 'INDEX'];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);
}


/**
 * Manage the computer remote management part migration
 *
 * @param object $migration
 */
function do_computerremotemgmt_migration($migration)
{

   /*
    * Table PluginGlpiinventoryComputerRemoteManagement
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_computerremotemanagements';
    $a_table['oldname'] = ['glpi_plugin_glpiinventory_computerremotemanagements'];

    $a_table['fields']  = [];
    $a_table['fields']['id']                  = ['type'    => 'autoincrement',
                                                     'value'   => ''];
    $a_table['fields']['computers_id']        = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                     'value'   => null];
    $a_table['fields']['number']              = ['type'    => 'string',
                                                     'value'   => null];
    $a_table['fields']['type']                = ['type'    => 'string',
                                                     'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'computers_id', 'name' => '', 'type' => 'INDEX'];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);
}


/**
 * Manage the computer architecture part migration
 *
 * @param object $migration
 */
function do_computerarch_migration($migration)
{
    global $DB;

    if ($DB->tableExists('glpi_plugin_glpiinventory_computerarches')) {
       //Rename field in coputeroperatingsystems table
        $a_table = [
         'name'     => 'glpi_plugin_glpiinventory_computeroperatingsystems',
         'renamefields' => [
            'plugin_glpiinventory_computerarches_id' => 'operatingsystemarchitectures_id'
         ]
        ];
        migratePluginTables($migration, $a_table);

       //Arches migration from FI table to GLPi core table
        $arches = new OperatingSystemArchitecture();
        foreach (getAllDataFromTable('glpi_plugin_glpiinventory_computerarches') as $arch) {
           //check if arch already exists in core
            if ($arches->getFromDBByCrit(['name' => $DB->escape($arch['name'])])) {
                $new_id = $arches->fields['id'];
            } else {
                unset($arch['id']);
                $new_id = $arches->add($arch, [], false);
            }

            $DB->update(
                'glpi_plugin_glpiinventory_computeroperatingsystems AS pf_os',
                [
                    'pf_os.operatingsystemarchitectures_id' => $new_id
                ],
                [
                    'os_arch.name' => $arch['name']
                ],
                [
                    'LEFT JOIN' => [
                        'operatingsystemarchitectures AS os_arch' => [
                            'ON' => [
                                'pf_os' => 'operatingsystemarchitectures_id',
                                'os_arch' => 'id',
                            ]
                        ]
                    ]
                ]
            );
        }

        $migration->dropTable('glpi_plugin_glpiinventory_computerarches');

       //Update dictionnary to match the new operating system
        $DB->update(
            'glpi_rules',
            [
                'sub_type'  => 'RuleDictionnaryOperatingSystemArchitectureCollection'
            ],
            [
                'sub_type'  => 'PluginGlpiinventoryRuleDictionnaryComputerArchCollection'
            ]
        );
    }
}


/**
 * Manage the operating system edition part migration
 *
 * @param object $migration
 */
function do_operatingsystemedition_migration($migration)
{
    global $DB;

    if ($DB->tableExists('glpi_plugin_glpiinventory_computeroperatingsystemeditions')) {
       //OS editions migration from FI table to GLPi core table
        $ose = new OperatingSystemEdition();
        foreach (getAllDataFromTable('glpi_plugin_glpiinventory_computeroperatingsystemeditions') as $edition) {
           //check if arch already exists in core
            if ($ose->getFromDBByCrit(['name' => $DB->escape($edition['name'])])) {
                $new_id = $ose->fields['id'];
            } else {
                unset($edition['id']);
                $new_id = $ose->add($edition, [], false);
            }

            $DB->update(
                'glpi_plugin_glpiinventory_computeroperatingsystems AS pf_os',
                [
                    'pf_os.plugin_glpiinventory_computeroperatingsystemeditions_id' => $new_id
                ],
                [
                    'os_edition.name' => $edition['name']
                ],
                [
                    'LEFT JOIN' => [
                        'glpi_plugin_glpiinventory_computeroperatingsystemeditions AS os_edition' => [
                            'ON' => [
                                'pf_os' => 'plugin_glpiinventory_computeroperatingsystemeditions_id',
                                'os_edition' => 'id'
                            ]
                        ]
                    ]
                ]
            );
        }
        $migration->dropTable('glpi_plugin_glpiinventory_computeroperatingsystemeditions');
    }
}


/**
 * Manage the kernel names and kernel versions part migration
 *
 * @param object $migration
 *
 * @return array
 */
function do_operatingsystemkernel_migration($migration)
{
    global $DB;

    if ($DB->tableExists('glpi_plugin_glpiinventory_computeroskernelnames')) {
       //Find wich version on which kernel
        $kmapping = []; // [orig_osid|orig_osversionid => newid]
        $mapping  = []; // [orig_computerosid => new_osversionid]

        $kernels = new OperatingSystemKernel();
        $kversions = new OperatingSystemKernelVersion();

       //DB::update() does not handle joins for now
        $query = "SELECT fi_cos.id,
            fi_kname.id AS kid, fi_kname.name AS kname,
            fi_kversion.id AS kvid, fi_kversion.name AS kversion
         FROM glpi_plugin_glpiinventory_computeroperatingsystems AS fi_cos
         INNER JOIN glpi_plugin_glpiinventory_computeroskernelnames AS fi_kname
            ON fi_kname.id = fi_cos.plugin_glpiinventory_computeroskernelnames_id
         INNER JOIN glpi_plugin_glpiinventory_computeroskernelversions AS fi_kversion
            ON fi_kversion.id = fi_cos.plugin_glpiinventory_computeroskernelversions_id
      ";
        $iterator = $DB->request($query);

        foreach ($iterator as $row) {
            $key = "{$row['kid']}|{$row['kvid']}";
            if (!isset($mapping[$key])) {
                //find in db for an existing kernel name
                if (!$kernels->getFromDBByCrit(['name' => $DB->escape($row['kname'])])) {
                    $kernels->add(['name' => $row['kname']]);
                }
                if (!$kversions->getFromDBByCrit(['name' => $DB->escape($row['kversion']), 'operatingsystemkernels_id' => $kernels->getID()])) {
                    $kversions->add([
                    'name'                        => $row['kversion'],
                    'operatingsystemkernels_id'  => $kernels->getID()
                    ]);
                }
                $kmapping[$key] = $kversions->getID();
            }
            $mapping[$row['id']] = $kmapping[$key];
        }

        $migration->dropTable('glpi_plugin_glpiinventory_computeroskernelnames');
        $migration->dropTable('glpi_plugin_glpiinventory_computeroskernelversions');

        return $mapping;
    }
}


/**
 * Manage the computer operating system part migration
 *
 * @param object $migration
 */
function do_computeroperatingsystem_migration($migration)
{
    global $DB;

    do_operatingsystemedition_migration($migration);
    $kversions_mapping = do_operatingsystemkernel_migration($migration);

    if ($DB->tableExists("glpi_plugin_glpiinventory_computeroperatingsystems")) {
        $ios = new Item_OperatingSystem();
        $query = "SELECT DISTINCT(fi_computer.computers_id) AS cid, fi_computer.computers_id, fi_cos.*
         FROM glpi_plugin_glpiinventory_inventorycomputercomputers AS fi_computer
         INNER JOIN glpi_plugin_glpiinventory_computeroperatingsystems AS fi_cos
            ON fi_computer.plugin_glpiinventory_computeroperatingsystems_id = fi_cos.id
         ";
        $iterator = $DB->request($query);

        foreach ($iterator as $row) {
            $search = [
            'itemtype'                          => 'Computer',
            'items_id'                          => $row['cid'],
            'operatingsystems_id'               => $row['operatingsystems_id'],
            'operatingsystemarchitectures_id'   => $row['operatingsystemarchitectures_id']
            ];

            $computer = new Computer();
            $computer->getFromDB($row['cid']);

            $input = $search + [
            'operatingsystemversions_id'        => $row['operatingsystemversions_id'],
            'operatingsystemservicepacks_id'    => $row['operatingsystemservicepacks_id'],
            'operatingsystemkernelversions_id'  => isset($kversions_mapping[$row['id']])
                                                      ? $kversions_mapping[$row['id']]
                                                      : 0,
            'operatingsystemeditions_id'        => $row['plugin_glpiinventory_computeroperatingsystemeditions_id'],
            'is_dynamic'                        => 1,
            'entities_id'                       => $computer->fields['entities_id']
            ];

            if (!$ios->getFromDBByCrit($search)) {
                $ios->add($input);
            } else {
                $ios->update(
                    ['id' => $ios->getID()] + $input
                );
            }
        }

        $migration->dropTable('glpi_plugin_glpiinventory_computeroperatingsystems');
        $migration->dropField(
            'glpi_plugin_glpiinventory_inventorycomputercomputers',
            'plugin_glpiinventory_computeroperatingsystems_id'
        );

       //handle display preferences
       //[oldid => newid]
        $sopts = [
         5172 => 45, //OS name
         5173 => 46, //OS version
         5174 => 64, //Kernel name
         5175 => 48, //Kernel version
         5176 => 41, //Service pack
         5177 => 63, //OS edition
         5150 => 9   //Last Update
        ];
        foreach ($sopts as $oldid => $newid) {
            $iterator = $DB->request(
                "SELECT * FROM `glpi_displaypreferences`
               WHERE
                  `itemtype`='Computer' AND (
                     `num`='$oldid' OR `num`='$newid'
                  )"
            );
            $users = [];
            foreach ($iterator as $row) {
                if (!in_array($row['users_id'], $users)) {
                    $users[] = $row['users_id'];
                    $DB->update(
                        'glpi_displaypreferences',
                        [
                        'num' => $newid
                        ],
                        [
                        'id'  => $row['id']
                        ]
                    );
                } elseif ($row['num'] == $oldid) {
                    $DB->delete(
                        'glpi_displaypreferences',
                        [
                        'id' => $row['id']
                        ]
                    );
                }
            }
        }

       //handle bookmarks
        $iterator = $DB->request([
         'FROM'   => 'glpi_savedsearches',
         'WHERE'  => [
            'itemtype' => 'Computer'
         ]
        ]);
        foreach ($iterator as $row) {
            parse_str($row["query"], $options);
            $changed = false;
            foreach ($options['criteria'] as &$criterion) {
                if (isset($sopts[$criterion['field']])) {
                    $criterion['field'] = $sopts[$criterion['field']];
                    $changed = true;
                }
            }

            if ($changed === true) {
                $querystr = Toolbox::append_params($options);
                $ssearch = new SavedSearch();
                $ssearch->update([
                'id'     => $row['id'],
                'query'  => $querystr
                ]);
            }
        }

       //handle dynamic groups
        $iterator = $DB->request([
         'FROM'   => 'glpi_plugin_glpiinventory_deploygroups_dynamicdatas'
        ]);
        foreach ($iterator as $row) {
            $fields = unserialize($row['fields_array']);
            $changed = false;
            foreach ($fields as &$type) {
                foreach ($type as &$criterion) {
                    if (isset($sopts[$criterion['field']])) {
                        $criterion['field'] = $sopts[$criterion['field']];
                        $changed = true;
                    }
                }
            }

            if ($changed === true) {
                $dyndata = new PluginGlpiinventoryDeployGroup_Dynamicdata();
                $dyndata->update([
                'id'  => $row['id'],
                'fields_array' => serialize($fields)
                ]);
            }
        }
    }

    $migration->addField(
        'glpi_plugin_glpiinventory_inventorycomputercomputers',
        "hostid",
        "string",
        ['after' => 'oscomment']
    );
    $migration->migrationOneTable('glpi_plugin_glpiinventory_inventorycomputercomputers');
}


/**
 * Manage the deploy user interaction migration process
 *
 * @since 9.2
 * @global object $DB
 * @param object $migration
 */
function do_deployuserinteraction_migration($migration)
{
    global $DB;

    if (!$DB->tableExists('glpi_plugin_glpiinventory_deployuserinteractions')) {
        $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_glpiinventory_deployuserinteractiontemplates` (
         `id` int unsigned NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         `entities_id` int unsigned NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         `json` longtext DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->query($query);
    }
}


/**
 * Manage the deploy files part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_deployfile_migration($migration)
{
    global $DB;

   /*
    * glpi_plugin_glpiinventory_deployfiles
    */
    $a_table = [];

    $a_table['name'] = 'glpi_plugin_glpiinventory_deployfiles';

    $a_table['oldname'] = [
    ];

    $a_table['fields'] = [
      'id' =>  [
               'type'   => 'autoincrement',
               'value'  => null
      ],
      'name' => [
               'type'   => 'varchar(255) NOT NULL',
               'value'  => null
      ],
      'mimetype' => [
               'type'   => 'varchar(255) NOT NULL',
               'value'  => null
      ],
      'filesize' => [
               'type' => 'bigint NOT NULL',
               'value' => null
      ],
      'comment' => [
               'type'   => 'text DEFAULT NULL',
               'value'  => null
      ],
      'sha512' => [
               'type'   => 'char(128) NOT NULL',
               'value'  => null
      ],
      'shortsha512' => [
               'type'   => 'char(6) NOT NULL',
               'value'  => null
      ],
      'entities_id' => [
               'type'   => 'int unsigned NOT NULL',
               'value'  => null
      ],
      'is_recursive' => [
               'type'   => 'tinyint NOT NULL DEFAULT \'0\'',
               'value'  => 0
      ],
      'date_mod' => [
               'type'   => 'timestamp NULL DEFAULT NULL',
               'value'  => null
      ],

    ];

    $a_table['oldfields'] = [
    ];

    $a_table['renamefields'] = [
    ];

    $a_table['keys'] = [
      [
         'field' => 'id',
         'name' => '',
         'type' => 'KEY'
      ],
      [
         'field' => 'shortsha512',
         'name' => '',
         'type' => 'KEY'
      ],
      [
         'field' => 'entities_id',
         'name' => '',
         'type' => 'KEY'
      ],
      [
         'field' => 'date_mod',
         'name' => '',
         'type' => 'KEY'
      ],
    ];

    $a_table['oldkeys'] = [
    ];

    migratePluginTables($migration, $a_table);

    if ($DB->tableExists("glpi_plugin_fusinvdeploy_files")) {
        if (!$DB->fieldExists("glpi_plugin_fusinvdeploy_files", "entities_id")) {
            $migration->addField(
                'glpi_plugin_fusinvdeploy_files',
                'entities_id',
                'int unsigned NOT NULL DEFAULT 0',
                ['value' => 0]
            );
            $migration->addField(
                'glpi_plugin_fusinvdeploy_files',
                'is_recursive',
                'bool',
                ['value' => 0]
            );
            $migration->migrationOneTable('glpi_plugin_fusinvdeploy_files');
            if ($DB->fieldExists("glpi_plugin_fusinvdeploy_files", "filesize")) {
                $iterator = $DB->request([
                    'SELECT' => [
                        'files.id',
                        'files.name',
                        'files.filesize',
                        'files.mimetype',
                        'files.sha512',
                        'files.shortsha512',
                        'files.create_date',
                        'pkgs.entities_id',
                        'pkgs.is_recursive'
                    ],
                    'FROM'   => 'glpi_plugin_fusinvdeploy_files AS files',
                    'LEFT JOIN' => [
                        'glpi_plugin_fusinvdeploy_orders AS orders' => [
                            'FKEY' => [
                                'orders' => 'id',
                                'files' => 'plugin_fusinvdeploy_orders_id'
                            ]
                        ],
                        'glpi_plugin_fusinvdeploy_packages AS pkgs' => [
                            'FKEY' => [
                                'pkgs' => 'id',
                                'orders' => 'plugin_fusinvdeploy_packages_id'
                            ]
                        ]
                    ],
                    'WHERE'  => [
                        'files.shortsha512' => ['!=', '']
                    ]
                ]);

                if (count($iterator) > 0) {
                    $update = $DB->buildUpdate(
                        'glpi_plugin_fusinvdeploy_files',
                        [
                            'entities_id'  => new \QueryParam(),
                            'is_recursive' => new \QueryParam(),
                        ],
                        [
                            'id'           => new \QueryParam()
                        ]
                    );
                    $stmt = $DB->prepare($update);
                    foreach ($iterator as $data) {
                            $stmt->bind_param(
                                'sss',
                                $data['entities_id'],
                                $data['is_recursive'],
                                $data['id']
                            );
                            $DB->executeStatement($stmt);
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}


/**
 * Manage the deploy package part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_deploypackage_migration($migration)
{
    global $DB;

   /*
    * glpi_plugin_glpiinventory_deploypackages
    */

    $a_table = [];

   //table name
    $a_table['name'] = 'glpi_plugin_glpiinventory_deploypackages';
    $a_table['oldname'] = [
      'glpi_plugin_fusinvdeploy_packages'
    ];

    $a_table['fields'] = [
      'id' =>  [
               'type' => 'autoincrement',
               'value' => null
      ],
      'name' =>  [
               'type' => 'varchar(255) NOT NULL',
               'value' => null
      ],
      'comment' =>  [
               'type' => "text",
               'value' => null
      ],
      'entities_id' =>  [
               'type' => 'int unsigned NOT NULL',
               'value' => null
      ],
      'is_recursive' =>  [
               'type' => 'tinyint NOT NULL DEFAULT \'0\'',
               'value' => null
      ],
      'date_mod' =>  [
               'type' => 'timestamp NULL DEFAULT NULL',
               'value' => null
      ],
      'uuid' =>  [
               'type' => 'string',
               'value' => null
      ],
      'json' =>  [
               'type' => 'longtext DEFAULT NULL',
               'value' => null
      ],
      'plugin_glpiinventory_deploygroups_id' => [
               'type'    => 'int unsigned NOT NULL DEFAULT 0',
               'value'   => null
      ],

    ];

    $a_table['oldfields'] = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = [
         'field' => 'entities_id',
         'name' => '',
         'type' => 'INDEX'
      ];
    $a_table['keys'][] = [
         'field' => 'date_mod',
         'name' => '',
         'type' => 'INDEX'
      ];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   // Before update, manage old Installation and Uninstallation
   // see https://github.com/fusioninventory/fusioninventory-for-glpi/issues/1823
    $order_table = 'glpi_plugin_glpiinventory_deployorders';
    $migration->renameTable('glpi_plugin_fusinvdeploy_orders', $order_table);

    if (
        $DB->tableExists($order_table)
           and $DB->fieldExists($order_table, 'type', false)
    ) {
        require_once(PLUGIN_GLPI_INVENTORY_DIR . "/inc/deploypackage.class.php");
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();

        $installs = getAllDataFromTable($order_table, ['type' => '0']);
        foreach ($installs as $install) {
            $pfDeployPackage->getFromDB($install['plugin_glpiinventory_deploypackages_id']);
            $input = [
             'id'   => $pfDeployPackage->fields['id'],
             'json' => addslashes($install['json']),
            ];
            $pfDeployPackage->update($input);
        }

        $uninstalls = getAllDataFromTable($order_table, ['type' => '1']);
        foreach ($uninstalls as $uninstall) {
            if (
                countElementsInTable($order_table, [
                'type'                                     => '0',
                'plugin_glpiinventory_deploypackages_id' => $uninstall['plugin_glpiinventory_deploypackages_id'],
                'json'                                     => ['<>', ''],
                ]) > 0
            ) {
               // have install and uninstall, so duplicate package
                $pfDeployPackage->getFromDB($uninstall['plugin_glpiinventory_deploypackages_id']);
                $input = $pfDeployPackage->fields;
                unset($input['id']);
                $input['json'] = $uninstall['json'];
                $input['name'] .= " (uninstall)";
                $deploypackage_id = $pfDeployPackage->add($input);
                $DB->update(
                    $order_table,
                    [
                    'plugin_glpiinventory_deploypackages_id'   => $deploypackage_id
                    ],
                    [
                    'id'                                         => $uninstall['id']
                    ]
                );
            }
        }
    }
    if ($DB->tableExists($order_table)) {
        $migration->dropTable($order_table);
    }

   /*
    * Table glpi_plugin_glpiinventory_deploypackages_entities
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_deploypackages_entities';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']      = ['type'    => 'autoincrement',
                                         'value'   => ''];
    $a_table['fields']['plugin_glpiinventory_deploypackages_id'] = ['type' => 'int unsigned NOT NULL DEFAULT 0',
                                                                          'value' => null];
    $a_table['fields']['entities_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                               'value'   => null];
    $a_table['fields']['is_recursive']  = ['type'    => 'bool',
                                               'value'   => '0'];
    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'plugin_glpiinventory_deploypackages_id', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'entities_id', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'is_recursive', 'name' => '', 'type' => 'INDEX'];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   /*
    * Table glpi_plugin_glpiinventory_deploypackages_groups
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_deploypackages_groups';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']      = ['type'    => 'autoincrement',
                                         'value'   => ''];
    $a_table['fields']['plugin_glpiinventory_deploypackages_id'] = ['type' => 'int unsigned NOT NULL DEFAULT 0',
                                                                          'value' => null];
    $a_table['fields']['groups_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                             'value'   => null];
    $a_table['fields']['entities_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                               'value'   => null];
    $a_table['fields']['is_recursive']  = ['type'    => 'bool',
                                               'value'   => '0'];
    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'plugin_glpiinventory_deploypackages_id', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'groups_id', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'entities_id', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'is_recursive', 'name' => '', 'type' => 'INDEX'];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   /*
    * Table glpi_plugin_glpiinventory_deploypackages_profiles
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_deploypackages_profiles';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']      = ['type'    => 'autoincrement',
                                         'value'   => ''];
    $a_table['fields']['plugin_glpiinventory_deploypackages_id'] = ['type' => 'int unsigned NOT NULL DEFAULT 0',
                                                                          'value' => null];
    $a_table['fields']['profiles_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                               'value'   => null];
    $a_table['fields']['entities_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                               'value'   => null];
    $a_table['fields']['is_recursive']  = ['type'    => 'bool',
                                               'value'   => '0'];
    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'plugin_glpiinventory_deploypackages_id', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'profiles_id', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'entities_id', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'is_recursive', 'name' => '', 'type' => 'INDEX'];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   /*
    * Table glpi_plugin_glpiinventory_deploypackages_users
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_deploypackages_users';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']      = ['type'    => 'autoincrement',
                                         'value'   => ''];
    $a_table['fields']['plugin_glpiinventory_deploypackages_id'] = ['type' => 'int unsigned NOT NULL DEFAULT 0',
                                                                          'value' => null];
    $a_table['fields']['users_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                            'value'   => null];
    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'plugin_glpiinventory_deploypackages_id', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'users_id', 'name' => '', 'type' => 'INDEX'];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);
}


/**
 * Manage the deploy mirror part migration
 *
 * @param object $migration
 */
function do_deploymirror_migration($migration)
{
    global $DB;

   /*
    * glpi_plugin_glpiinventory_deploymirrors
    */

    $a_table = [];

   //If table doesn't exists, then we're sure the is_active field is not present
    if (!$DB->tableExists('glpi_plugin_glpiinventory_deploymirrors')) {
        $is_active_exists = false;
    } else {
        $is_active_exists = ($DB->fieldExists(
            'glpi_plugin_glpiinventory_deploymirrors',
            'is_active'
        ));
    }

   //table name
    $a_table['name'] = 'glpi_plugin_glpiinventory_deploymirrors';
    $a_table['oldname'] = [
      'glpi_plugin_fusinvdeploy_mirrors'
    ];

    $a_table['fields'] = [
      'id' =>  [
         'type' => 'autoincrement',
         'value' => null
      ],
      'entities_id' =>  [
         'type' => 'int unsigned NOT NULL',
         'value' => null
      ],
      'is_active' =>  [
         'type' => 'tinyint NOT NULL DEFAULT \'0\'',
         'value' => null
      ],
      'is_recursive' =>  [
         'type' => 'tinyint NOT NULL DEFAULT \'0\'',
         'value' => null
      ],
      'name' =>  [
         'type' => 'varchar(255) NOT NULL',
         'value' => null
      ],
      'url' =>  [
         'type' => "varchar(255)" .
                   " NOT NULL DEFAULT ''",
         'value' => null
      ],
      'locations_id' => [
         'type' => 'int unsigned NOT NULL',
         'value' => 0
      ],
      'comment' =>  [
         'type' => "text",
         'value' => null
      ],
      'date_mod' =>  [
         'type' => 'timestamp NULL DEFAULT NULL',
         'value' => null
      ],
    ];

    $a_table['oldfields'] = [
    ];

    $a_table['renamefields'] = [
    ];

    $a_table['keys'] = [
      [
         'field' => 'entities_id',
         'name' => '',
         'type' => 'KEY'
      ],
      [
         'field' => 'is_active',
         'name' => '',
         'type' => 'KEY'
      ],
      [
         'field' => 'is_recursive',
         'name' => '',
         'type' => 'KEY'
      ],
      [
         'field' => 'date_mod',
         'name' => '',
         'type' => 'KEY'
      ],
    ];

    $a_table['oldkeys'] = [
    ];

    migratePluginTables($migration, $a_table);

   //During migration, once the is_active field is added,
   //all mirrors must be active to keep compatibility
    if (!$is_active_exists) {
        $DB->update(
            'glpi_plugin_glpiinventory_deploymirrors',
            [
            'is_active' => 1
            ],
            [1 => 1]
        );
    }
}


/**
 * Manage the deploy group part migration
 *
 * @param object $migration
 */
function do_deploygroup_migration($migration)
{

   /*
    * glpi_plugin_glpiinventory_deploygroups
    */

    $a_table = [];

   //table name
    $a_table['name'] = 'glpi_plugin_glpiinventory_deploygroups';
    $a_table['oldname'] = [
      'glpi_plugin_fusinvdeploy_groups'
    ];

    $a_table['fields'] = [
      'id' =>  [
         'type' => 'autoincrement',
         'value' => null
      ],
      'name' =>  [
         'type' => 'varchar(255) NOT NULL',
         'value' => null
      ],
      'comment' =>  [
         'type' => "text",
         'value' => null
      ],
      'type' =>  [
         'type' => 'varchar(255) NOT NULL',
         'value' => null
      ],
    ];

    $a_table['oldfields'] = [
    ];

    $a_table['renamefields'] = [
    ];

    $a_table['keys'] = [
    ];

    $a_table['oldkeys'] = [
    ];

    migratePluginTables($migration, $a_table);

   /*
    * glpi_plugin_glpiinventory_deploygroups_staticdatas
    */

    $a_table = [];

   //table name
    $a_table['name'] = 'glpi_plugin_glpiinventory_deploygroups_staticdatas';
    $a_table['oldname'] = [
      'glpi_plugin_fusinvdeploy_groups_staticdatas'
    ];

    $a_table['fields'] = [
      'id' =>  [
         'type' => 'autoincrement',
         'value' => null
      ],
      'plugin_glpiinventory_deploygroups_id' =>  [
         'type' => 'int unsigned NOT NULL DEFAULT 0',
         'value' => null
      ],
      'itemtype' =>  [
         'type' => 'varchar(100) DEFAULT NULL',
         'value' => null
      ],
      'items_id' =>  [
         'type' => 'int unsigned NOT NULL DEFAULT 0',
         'value' => null
      ],
    ];

    $a_table['oldfields'] = [
    ];

    $a_table['renamefields'] = [
      'groups_id' => 'plugin_glpiinventory_deploygroups_id',
    ];

    $a_table['keys'] = [
      /*[
         'field' => 'plugin_glpiinventory_deploygroups_id',
         'name' => '',
         'type' => 'KEY'
      ],*/
      [
         'field' => 'items_id',
         'name' => '',
         'type' => 'KEY'
      ],
    ];

    $a_table['oldkeys'] = [
    ];

    migratePluginTables($migration, $a_table);

   /*
    * glpi_plugin_glpiinventory_deploygroups_dynamicdatas
    */

    $a_table = [];

   //table name
    $a_table['name'] = 'glpi_plugin_glpiinventory_deploygroups_dynamicdatas';
    $a_table['oldname'] = [
      'glpi_plugin_fusinvdeploy_groups_dynamicdatas'
    ];

    $a_table['fields'] = [
      'id' =>  [
         'type' => 'autoincrement',
         'value' => null
      ],
      'plugin_glpiinventory_deploygroups_id' =>  [
         'type' => 'int unsigned NOT NULL DEFAULT 0',
         'value' => null
      ],
      'fields_array' =>  [
         'type' => 'text',
         'value' => null
      ],
      'can_update_group' =>  [
         'type' => 'bool',
         'value' => 0
      ],
      'computers_id_cache' =>  [
         'type' => 'longtext',
         'value' => null
      ],
    ];

    $a_table['oldfields'] = [
    ];

    $a_table['renamefields'] = [
      'groups_id' => 'plugin_glpiinventory_deploygroups_id',
    ];

    $a_table['keys'] = [
      /*[
         'field' => 'plugin_glpiinventory_deploygroups_id',
         'name' => '',
         'type' => 'KEY'
      ],*/
      [
         'field' => 'can_update_group',
         'name' => '',
         'type' => 'KEY'
      ],
    ];

    $a_table['oldkeys'] = [
    ];

    migratePluginTables($migration, $a_table);
}


/**
 * Manage the database locks part migration
 *
 * @param object $migration
 */
function do_dblocks_migration($migration)
{

   /*
    * Table glpi_plugin_glpiinventory_dblockinventorynames
    */
      $a_table = [];
      $a_table['name'] = 'glpi_plugin_glpiinventory_dblockinventorynames';
      $a_table['oldname'] = [];

      $a_table['fields']  = [];
      $a_table['fields']['value']      = ['type'    => "varchar(100) NOT NULL DEFAULT ''",
                                               'value'   => null];
      $a_table['fields']['date']       = ['type'    => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP()',
                                               'value'   => null];

      $a_table['oldfields']  = [];

      $a_table['renamefields'] = [];

      $a_table['keys']   = [];
      $a_table['keys'][] = ['field' => 'value', 'name' => '', 'type' => 'UNIQUE'];

      $a_table['oldkeys'] = [];

      migratePluginTables($migration, $a_table);

      /*
      * Table glpi_plugin_glpiinventory_dblockinventories
      */
      $a_table = [];
      $a_table['name'] = 'glpi_plugin_glpiinventory_dblockinventories';
      $a_table['oldname'] = [];

      $a_table['fields']  = [];
      $a_table['fields']['value']      = ['type'    => 'integer',
                                               'value'   => null];
      $a_table['fields']['date']       = ['type'    => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP()',
                                               'value'   => null];

      $a_table['oldfields']  = [];

      $a_table['renamefields'] = [];

      $a_table['keys']   = [];
      $a_table['keys'][] = ['field' => 'value', 'name' => '', 'type' => 'UNIQUE'];

      $a_table['oldkeys'] = [];

      migratePluginTables($migration, $a_table);

      /*
      * Table glpi_plugin_glpiinventory_dblocksoftwares
      */
      $a_table = [];
      $a_table['name'] = 'glpi_plugin_glpiinventory_dblocksoftwares';
      $a_table['oldname'] = [];

      $a_table['fields']  = [];
      $a_table['fields']['value']      = ['type'    => 'bool',
                                               'value'   => null];
      $a_table['fields']['date']       = ['type'    => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP()',
                                               'value'   => null];

      $a_table['oldfields']  = [];

      $a_table['renamefields'] = [];

      $a_table['keys']   = [];
      $a_table['keys'][] = ['field' => 'value', 'name' => '', 'type' => 'UNIQUE'];

      $a_table['oldkeys'] = [];

      migratePluginTables($migration, $a_table);

      /*
      * Table glpi_plugin_glpiinventory_dblocksoftwareversions
      */
      $a_table = [];
      $a_table['name'] = 'glpi_plugin_glpiinventory_dblocksoftwareversions';
      $a_table['oldname'] = [];

      $a_table['fields']  = [];
      $a_table['fields']['value']      = ['type'    => 'bool',
                                               'value'   => null];
      $a_table['fields']['date']       = ['type'    => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP()',
                                               'value'   => null];

      $a_table['oldfields']  = [];

      $a_table['renamefields'] = [];

      $a_table['keys']   = [];
      $a_table['keys'][] = ['field' => 'value', 'name' => '', 'type' => 'UNIQUE'];

      $a_table['oldkeys'] = [];

      migratePluginTables($migration, $a_table);
}


/**
 * Manage the ESX credentials part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_credentialESX_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_credentials
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_credentials';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => 'autoincrement',
                                             'value'   => ''];
    $a_table['fields']['entities_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                             'value'   => null];
    $a_table['fields']['is_recursive'] = ['type'    => 'bool',
                                             'value'   => null];
    $a_table['fields']['name']       = ['type'    => 'string',
                                             'value'   => ""];
    $a_table['fields']['username']   = ['type'    => 'string',
                                             'value'   => ""];
    $a_table['fields']['password']   = ['type'    => 'string',
                                             'value'   => ""];
    $a_table['fields']['comment']    = ['type'    => 'text',
                                             'value'   => null];
    $a_table['fields']['date_mod']   = ['type'    => 'timestamp',
                                             'value'   => null];
    $a_table['fields']['itemtype']   = ['type'    => 'string',
                                             'value'   => ""];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   // Fix itemtype changed in 0.84
    $DB->update(
        'glpi_plugin_glpiinventory_credentials',
        [
         'itemtype'  => 'PluginGlpiinventoryInventoryComputerESX'
        ],
        [
         'itemtype'  => 'PluginFusinvinventoryVmwareESX'
        ]
    );

   /*
    * Table glpi_plugin_glpiinventory_credentialips
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_credentialips';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => 'autoincrement',
                                             'value'   => ''];
    $a_table['fields']['entities_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                             'value'   => null];
    $a_table['fields']['plugin_glpiinventory_credentials_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                             'value'   => null];
    $a_table['fields']['name']       = ['type'    => 'string',
                                             'value'   => ""];
    $a_table['fields']['comment']    = ['type'    => 'text',
                                             'value'   => null];
    $a_table['fields']['ip']         = ['type'    => 'string',
                                             'value'   => ""];
    $a_table['fields']['date_mod']   = ['type'    => 'timestamp',
                                             'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);
}


/**
 * Manage the collect part migration
 *
 * @param object $migration
 */
function do_collect_migration($migration)
{

   /*
    * Table glpi_plugin_glpiinventory_collects
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_collects';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => "autoincrement",
                                             'value'   => ''];
    $a_table['fields']['name']       = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['entities_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                'value'   => null];
    $a_table['fields']['is_recursive']  = ['type'    => 'bool',
                                                'value'   => null];
    $a_table['fields']['type']       = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['is_active']  = ['type'    => 'bool',
                                             'value'   => null];
    $a_table['fields']['comment']    = ['type'    => 'text',
                                             'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   /*
   * Table glpi_plugin_glpiinventory_collects_registries
   */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_collects_registries';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => "autoincrement",
                                             'value'   => ''];
    $a_table['fields']['name']       = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['plugin_glpiinventory_collects_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                'value'   => null];
    $a_table['fields']['hive']       = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['path']       = ['type'    => 'text',
                                             'value'   => null];
    $a_table['fields']['key']        = ['type'    => 'string',
                                             'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   /*
   * Table glpi_plugin_glpiinventory_collects_registries_contents
   */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_collects_registries_contents';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => "autoincrement",
                                             'value'   => ''];
    $a_table['fields']['computers_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                             'value'   => null];
    $a_table['fields']['plugin_glpiinventory_collects_registries_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                'value'   => null];
    $a_table['fields']['key']       = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['value']     = ['type'    => 'string',
                                             'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'computers_id', 'name' => '', 'type' => 'INDEX'];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   /*
   * Table glpi_plugin_glpiinventory_collects_wmis
   */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_collects_wmis';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => "autoincrement",
                                             'value'   => ''];
    $a_table['fields']['name']       = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['plugin_glpiinventory_collects_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                'value'   => null];
    $a_table['fields']['moniker']    = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['class']      = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['properties'] = ['type'    => 'string',
                                             'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   /*
   * Table glpi_plugin_glpiinventory_collects_wmis_contents
   */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_collects_wmis_contents';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => "autoincrement",
                                             'value'   => ''];
    $a_table['fields']['computers_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                             'value'   => null];
    $a_table['fields']['plugin_glpiinventory_collects_wmis_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                'value'   => null];
    $a_table['fields']['property']   = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['value']      = ['type'    => 'string',
                                             'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   /*
   * Table glpi_plugin_glpiinventory_collects_files
   */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_collects_files';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => "autoincrement",
                                             'value'   => ''];
    $a_table['fields']['name']       = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['plugin_glpiinventory_collects_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                'value'   => null];
    $a_table['fields']['dir']        = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['limit']      = ['type'    => "int NOT NULL DEFAULT '50'",
                                             'value'   => null];
    $a_table['fields']['is_recursive'] = ['type'    => 'bool',
                                             'value'   => null];
    $a_table['fields']['filter_regex'] = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['filter_sizeequals'] = ['type'    => 'integer',
                                             'value'   => null];
    $a_table['fields']['filter_sizegreater'] = ['type'    => 'integer',
                                             'value'   => null];
    $a_table['fields']['filter_sizelower'] = ['type'    => 'integer',
                                             'value'   => null];
    $a_table['fields']['filter_checksumsha512'] = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['filter_checksumsha2'] = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['filter_name'] = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['filter_iname'] = ['type'    => 'string',
                                             'value'   => null];
    $a_table['fields']['filter_is_file'] = ['type'    => 'bool',
                                             'value'   => '1'];
    $a_table['fields']['filter_is_dir'] = ['type'    => 'bool',
                                             'value'   => '0'];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);

   /*
   * Table glpi_plugin_glpiinventory_collects_files_contents
   */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_collects_files_contents';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']         = ['type'    => "autoincrement",
                                             'value'   => ''];
    $a_table['fields']['computers_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                             'value'   => null];
    $a_table['fields']['plugin_glpiinventory_collects_files_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                'value'   => null];
    $a_table['fields']['pathfile']   = ['type'    => 'text',
                                             'value'   => null];
    $a_table['fields']['size']       = ['type'    => 'integer',
                                             'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);
}


/**
 * Manage the SNMP models part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_snmpmodel_migration($migration)
{
    global $DB;

    $elements = ['5151', 'PluginFusinvsnmpModel',
       'PluginGlpiinventorySnmpmodel', 'PluginFusinvsnmpConstructDevice',
       'PluginGlpiinventorySnmpmodelConstructDevice', '5167'];
    foreach ($elements as $element) {
        $DB->delete(
            'glpi_displaypreferences',
            [
            'itemtype' => $element
            ]
        );
    }
}


/**
 * Manage the rules part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_rule_migration($migration)
{
    global $DB;

   /*
    * Update rules
    */
    $DB->update(
        'glpi_rules',
        [
         'sub_type'  => 'PluginGlpiinventoryInventoryRuleImport'
        ],
        [
         'sub_type'  => 'PluginGlpiinventoryRuleImportEquipment'
        ]
    );

    $iterator = $DB->request([
      'FROM'   => 'glpi_rules',
      'WHERE'  => ['sub_type' => 'PluginGlpiinventoryInventoryRuleImport']
    ]);
    if (count($iterator)) {
        $update = $DB->buildUpdate(
            'glpi_ruleactions',
            [
            'value'  => 1
            ],
            [
            'rules_id'  => new \QueryParam(),
            'value'     => 0,
            'field'     => '_fusion'
            ]
        );
        $stmt = $DB->prepare($update);
        foreach ($iterator as $data) {
            $stmt->bind_param('s', $data['id']);
            $DB->executeStatement($stmt);
        }
        mysqli_stmt_close($stmt);
    }

   /*
   *  Manage configuration of plugin
   */
    $config = new PluginGlpiinventoryConfig();
    $pfSetup = new PluginGlpiinventorySetup();
    $users_id = $pfSetup->createGlpiInventoryUser();
    $a_input = [];
    $a_input['ssl_only'] = 0;
    $a_input['delete_task'] = 20;
    $a_input['agent_port'] = 62354;
    $a_input['extradebug'] = 0;
    $a_input['users_id'] = $users_id;
    $config->addValues($a_input, false);

    $a_input = [];
    $a_input['version'] = PLUGIN_GLPIINVENTORY_VERSION;
    $config->addValues($a_input, true);
    $a_input = [];
    $a_input['ssl_only'] = 0;
    if (isset($prepare_Config['ssl_only'])) {
        $a_input['ssl_only'] = $prepare_Config['ssl_only'];
    }
    $a_input['delete_task'] = 20;
    $a_input['agent_port'] = 62354;
    $a_input['extradebug'] = 0;
    $a_input['users_id'] = 0;

   //Deploy configuration options
    $a_input['server_upload_path'] =
         Toolbox::addslashes_deep(
             implode(
                 DIRECTORY_SEPARATOR,
                 [
                  GLPI_PLUGIN_DOC_DIR,
                  'glpiinventory',
                  'upload'
                 ]
             )
         );
    $a_input['alert_winpath']    = 1;
    $a_input['server_as_mirror'] = 1;
    $a_input['mirror_match']     = 0;
    $config->addValues($a_input, false);

    $pfSetup = new PluginGlpiinventorySetup();
    $users_id = $pfSetup->createGlpiInventoryUser();
    $DB->update(
        'glpi_plugin_glpiinventory_configs',
        [
         'value'  => $users_id
        ],
        [
         'type'   => 'users_id'
        ]
    );

   // Delete old configs
    $DB->delete(
        'glpi_plugin_glpiinventory_configs',
        [
         'type' => 'import_printer'
        ]
    );
    $DB->delete(
        'glpi_plugin_glpiinventory_configs',
        [
         'type'   => 'import_peripheral'
        ]
    );
    $DB->delete(
        'glpi_plugin_glpiinventory_configs',
        [
         'type'   => 'import_printer'
        ]
    );
}


/**
 * Manage the task part migration
 *
 * @global object $DB
 * @param object $migration
 */
function do_task_migration($migration)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_taskjobs
    */
    $a_table = [];
    $a_table['name'] = 'glpi_plugin_glpiinventory_taskjobs';
    $a_table['oldname'] = [];

    $a_table['fields']  = [];
    $a_table['fields']['id']                     = ['type'    => 'autoincrement',
                                                        'value'   => ''];
    $a_table['fields']['plugin_glpiinventory_tasks_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                                 'value'   => null];
    $a_table['fields']['entities_id']   = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                               'value'   => null];
    $a_table['fields']['name']          = ['type'    => 'string',
                                               'value'   => null];
    $a_table['fields']['date_creation'] = ['type'    => 'timestamp',
                                               'value'   => null];
    $a_table['fields']['method']        = ['type'    => 'string',
                                               'value'   => null];
    $a_table['fields']['targets']       = ['type'    => 'text',
                                               'value'   => null];
    $a_table['fields']['actors']        = ['type'    => 'text',
                                               'value'   => null];
    $a_table['fields']['comment']       = ['type'    => 'text',
                                               'value'   => null];
    $a_table['fields']['rescheduled_taskjob_id'] = ['type'    => 'int unsigned NOT NULL DEFAULT 0',
                                                        'value'   => null];
    $a_table['fields']['statuscomments'] = ['type'    => 'text',
                                                'value'   => null];
    $a_table['fields']['enduser']       = ['type'    => 'text',
                                               'value'   => null];

    $a_table['oldfields']  = [];

    $a_table['renamefields'] = [];

    $a_table['keys']   = [];
    $a_table['keys'][] = ['field' => 'plugin_glpiinventory_tasks_id', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'entities_id', 'name' => '', 'type' => 'INDEX'];
    $a_table['keys'][] = ['field' => 'method', 'name' => '', 'type' => 'INDEX'];

    $a_table['oldkeys'] = [];

    migratePluginTables($migration, $a_table);
}


/**
 * Migrate search params from the old system to the new one
 * As search engine integration has been improved with GLPI 0.85
 *
 * @since 0.85+1.0
 *
 * @global object $DB
 */
function doDynamicDataSearchParamsMigration()
{
    global $DB;

    $iterator = $DB->request([
      'SELECT' => ['id', 'fields_array'],
      'FROM'   => 'glpi_plugin_glpiinventory_deploygroups_dynamicdatas'
    ]);

    if (count($iterator)) {
        $update = $DB->buildUpdate(
            'glpi_plugin_glpiinventory_deploygroups_dynamicdatas',
            [
            'fields_array' => new \QueryParam()
            ],
            [
            'id'           => new \QueryParam()
            ]
        );
        $stmt = $DB->prepare($update);
        foreach ($iterator as $dynamic_data) {
            $new_values   = migrationDynamicGroupFields($dynamic_data['fields_array']);
            $stmt->bind_param(
                'ss',
                $new_values,
                $dynamic_data['id']
            );
        }
        mysqli_stmt_close($stmt);
    }
}


/**
 * Migration of one dynamic group
 *
 * @since 0.85+1.0
 *
 * @param array $fields search paramas in old format (serialized)
 * @return string search paramas in new format (serialized)
 */
function migrationDynamicGroupFields($fields)
{
    $data       = json_decode($fields, true);
    $new_fields = [];
    if (!is_array($data)) {
        $data   = unserialize($fields);
    }

   //We're still in 0.85 or higher,
   //no need for migration !
    if (isset($data['criteria'])) {
        return $fields;
    }

   //Upgrade from 0.84
    if (isset($data['field'])) {
        $count_fields = count($data['field']);
        for ($i = 0; $i < $count_fields; $i++) {
            $new_value = [];
            $new_value['value']       = $data['contains'][$i];
            $new_value['field']       = $data['field'][$i];
            $new_value['searchtype']  = $data['searchtype'][$i];
            $new_fields['criteria'][] = $new_value;
        }

        if (isset($data['field2'])) {
            $count_fields = count($data['field2']);
            for ($i = 0; $i < $count_fields; $i++) {
                $new_value = [];
                $new_value['value']           = $data['contains2'][$i];
                $new_value['field']           = $data['field2'][$i];
                $new_value['itemtype']        = $data['itemtype2'][$i];
                $new_value['searchtype']      = $data['searchtype2'][$i];
                $new_fields['metacriteria'][] = $new_value;
            }
        }
    } elseif (isset($data['itemtype']) && isset($data['name'])) {
       //Ugrapde from 0.83, where the number of fields to search was fixed
        $oldfields = ['name'                => 2,
                         'serial'              => 5,
                         'otherserial'         => 6,
                         'locations_id'        => 3,
                         'operatingsystems_id' => 45,
                         'room'                => 92,
                         'building'            => 91];
        foreach ($oldfields as $name => $id) {
            $new_value = [];
            if (isset($data[$name]) && $data[$name] != '') {
                $new_value['field']       = $id;
                $new_value['value']       = $data[$name];
                $new_value['searchtype']  = 'equals';
            }
            if (!empty($new_value)) {
                $new_fields['criteria'][] = $new_value;
            }
        }
    }
    return serialize($new_fields);
}


/**
 * Manage the display preference part migration
 *
 * @global object $DB
 * @param string $olditemtype
 * @param string $newitemtype
 */
function changeDisplayPreference($olditemtype, $newitemtype)
{
    global $DB;

    $query = "SELECT `users_id`, `num`, count(*) as `cnt`, GROUP_CONCAT( id SEPARATOR ' ') as id
      FROM `glpi_displaypreferences`
      WHERE (`itemtype` = '" . $newitemtype . "'
      OR `itemtype` = '" . $olditemtype . "')
      group by `users_id`, `num`";
    $result = $DB->query($query);
    while ($data = $DB->fetchArray($result)) {
        if ($data['cnt'] > 1) {
            $ids = explode(' ', $data['id']);
            array_shift($ids);
            $DB->delete(
                'glpi_displaypreferences',
                [
                'id' => $ids
                ]
            );
        }
    }

    $DB->update(
        'glpi_displaypreferences',
        [
         'itemtype'  => $newitemtype
        ],
        [
         'itemtype'  => $olditemtype
        ]
    );
}


/**
 * Manage the update from 2.13 to 2.20 version (very old) part migration
 *
 * @global object $DB
 * @param object $migration
 */
function update213to220_ConvertField($migration)
{
    global $DB;

   // ----------------------------------------------------------------------
   //NETWORK MAPPING MAPPING
   // ----------------------------------------------------------------------
    $constantsfield = [];

    $constantsfield['reseaux > lieu'] = 'location';
    $constantsfield['networking > location'] = 'location';
    $constantsfield['Netzwerk > Standort'] = 'location';

    $constantsfield['réseaux > firmware'] = 'firmware';
    $constantsfield['networking > firmware'] = 'firmware';
    $constantsfield['Netzwerk > Firmware'] = 'firmware';

    $constantsfield['réseaux > firmware'] = 'firmware1';
    $constantsfield['networking > firmware'] = 'firmware1';
    $constantsfield['Netzwerk > Firmware'] = 'firmware1';

    $constantsfield['réseaux > firmware'] = 'firmware2';
    $constantsfield['networking > firmware'] = 'firmware2';
    $constantsfield['Netzwerk > Firmware'] = 'firmware2';

    $constantsfield['réseaux > contact'] = 'contact';
    $constantsfield['networking > contact'] = 'contact';
    $constantsfield['Netzwerk > Kontakt'] = 'contact';

    $constantsfield['réseaux > description'] = 'comments';
    $constantsfield['networking > comments'] = 'comments';
    $constantsfield['Netzwerk > Kommentar'] = 'comments';

    $constantsfield['réseaux > uptime'] = 'uptime';
    $constantsfield['networking > uptime'] = 'uptime';
    $constantsfield['Netzwerk > Uptime'] = 'uptime';

    $constantsfield['réseaux > utilisation du CPU'] = 'cpu';
    $constantsfield['networking > CPU usage'] = 'cpu';
    $constantsfield['Netzwerk > CPU Auslastung'] = 'cpu';

    $constantsfield['réseaux > CPU user'] = 'cpuuser';
    $constantsfield['networking > CPU usage (user)'] = 'cpuuser';
    $constantsfield['Netzwerk > CPU Benutzer'] = 'cpuuser';

    $constantsfield['réseaux > CPU système'] = 'cpusystem';
    $constantsfield['networking > CPU usage (system)'] = 'cpusystem';
    $constantsfield['Netzwerk > CPU System'] = 'cpusystem';

    $constantsfield['réseaux > numéro de série'] = 'serial';
    $constantsfield['networking > serial number'] = 'serial';
    $constantsfield['Netzwerk > Seriennummer'] = 'serial';

    $constantsfield['réseaux > numéro d\'inventaire'] = 'otherserial';
    $constantsfield['networking > Inventory number'] = 'otherserial';
    $constantsfield['Netzwerk > Inventarnummer'] = 'otherserial';

    $constantsfield['réseaux > nom'] = 'name';
    $constantsfield['networking > name'] = 'name';
    $constantsfield['Netzwerk > Name'] = 'name';

    $constantsfield['réseaux > mémoire totale'] = 'ram';
    $constantsfield['networking > total memory'] = 'ram';
    $constantsfield['Netzwerk > Gesamter Speicher'] = 'ram';

    $constantsfield['réseaux > mémoire libre'] = 'memory';
    $constantsfield['networking > free memory'] = 'memory';
    $constantsfield['Netzwerk > Freier Speicher'] = 'memory';

    $constantsfield['réseaux > VLAN'] = 'vtpVlanName';
    $constantsfield['networking > VLAN'] = 'vtpVlanName';
    $constantsfield['Netzwerk > VLAN'] = 'vtpVlanName';

    $constantsfield['réseaux > port > vlan'] = 'vmvlan';
    $constantsfield['networking > port > vlan'] = 'vmvlan';

    $constantsfield['réseaux > modèle'] = 'entPhysicalModelName';
    $constantsfield['networking > model'] = 'entPhysicalModelName';
    $constantsfield['Netzwerk > Modell'] = 'entPhysicalModelName';

    $constantsfield['réseaux > adresse MAC'] = 'macaddr';
    $constantsfield['networking > MAC address'] = 'macaddr';
    $constantsfield['Netzwerk > MAC Adresse'] = 'macaddr';

    $constantsfield['réseaux > Adresse CDP'] = 'cdpCacheAddress';
    $constantsfield['networking > CDP address'] = 'cdpCacheAddress';
    $constantsfield['Netzwerk > Adresse CDP'] = 'cdpCacheAddress';

    $constantsfield['réseaux > port CDP'] = 'cdpCacheDevicePort';
    $constantsfield['networking > CDP port'] = 'cdpCacheDevicePort';
    $constantsfield['Netzwerk > Port CDP'] = 'cdpCacheDevicePort';

    $constantsfield['réseaux > chassis id distant LLDP'] = 'lldpRemChassisId';
    $constantsfield['networking > remote chassis id LLDP'] = 'lldpRemChassisId';

    $constantsfield['réseaux > port distant LLDP'] = 'lldpRemPortId';
    $constantsfield['networking > remote port LLDP'] = 'lldpRemPortId';

    $constantsfield['réseaux > chassis id local LLDP'] = 'lldpLocChassisId';
    $constantsfield['networking > localchassis id LLDP'] = 'lldpLocChassisId';

    $constantsfield['réseaux > port > trunk/tagged'] = 'vlanTrunkPortDynamicStatus';
    $constantsfield['networking > port > trunk/tagged'] = 'vlanTrunkPortDynamicStatus';
    $constantsfield['Netzwerk > Port > trunk/tagged'] = 'vlanTrunkPortDynamicStatus';

    $constantsfield['trunk'] = 'vlanTrunkPortDynamicStatus';

    $constantsfield['réseaux > Adresses mac filtrées (dot1dTpFdbAddress)'] = 'dot1dTpFdbAddress';
    $constantsfield['networking > MAC address filters (dot1dTpFdbAddress)'] = 'dot1dTpFdbAddress';
    $constantsfield['Netzwerk > MAC Adressen Filter (dot1dTpFdbAddress)'] = 'dot1dTpFdbAddress';

    $constantsfield['réseaux > adresses physiques mémorisées (ipNetToMediaPhysAddress)'] =
                  'ipNetToMediaPhysAddress';
    $constantsfield['networking > Physical addresses in memory (ipNetToMediaPhysAddress)'] =
                  'ipNetToMediaPhysAddress';
    $constantsfield['Netzwerk > Physikalische Adressen im Speicher (ipNetToMediaPhysAddress)'] =
                  'ipNetToMediaPhysAddress';

    $constantsfield['réseaux > instances de ports (dot1dTpFdbPort)'] = 'dot1dTpFdbPort';
    $constantsfield['networking > Port instances (dot1dTpFdbPort)'] = 'dot1dTpFdbPort';
    $constantsfield['Netzwerk > Instanzen des Ports (dot1dTpFdbPort)'] = 'dot1dTpFdbPort';

    $constantsfield['réseaux > numéro de ports associé ID du port (dot1dBasePortIfIndex)'] =
                  'dot1dBasePortIfIndex';
    $constantsfield['networking > Port number associated with port ID (dot1dBasePortIfIndex)'] =
                  'dot1dBasePortIfIndex';
    $constantsfield['Netzwerk > Verkn&uuml;pfung der Portnummerierung mit der ID des Ports (dot1dBasePortIfIndex)'] = 'dot1dBasePortIfIndex';

    $constantsfield['réseaux > addresses IP'] = 'ipAdEntAddr';
    $constantsfield['networking > IP addresses'] = 'ipAdEntAddr';
    $constantsfield['Netzwerk > IP Adressen'] = 'ipAdEntAddr';

    $constantsfield['réseaux > portVlanIndex'] = 'PortVlanIndex';
    $constantsfield['networking > portVlanIndex'] = 'PortVlanIndex';
    $constantsfield['Netzwerk > portVlanIndex'] = 'PortVlanIndex';

    $constantsfield['réseaux > port > numéro index'] = 'ifIndex';
    $constantsfield['networking > port > index number'] = 'ifIndex';
    $constantsfield['Netzwerk > Port > Nummerischer Index'] = 'ifIndex';

    $constantsfield['réseaux > port > mtu'] = 'ifmtu';
    $constantsfield['networking > port > mtu'] = 'ifmtu';
    $constantsfield['Netzwerk > Port > MTU'] = 'ifmtu';

    $constantsfield['réseaux > port > vitesse'] = 'ifspeed';
    $constantsfield['networking > port > speed'] = 'ifspeed';
    $constantsfield['Netzwerk > Port > Geschwindigkeit'] = 'ifspeed';

    $constantsfield['réseaux > port > statut interne'] = 'ifinternalstatus';
    $constantsfield['networking > port > internal status'] = 'ifinternalstatus';
    $constantsfield['Netzwerk > Port > Interner Zustand'] = 'ifinternalstatus';

    $constantsfield['réseaux > port > Dernier changement'] = 'iflastchange';
    $constantsfield['networking > ports > Last change'] = 'iflastchange';
    $constantsfield['Netzwerk > Ports > Letzte &Auml;nderung'] = 'iflastchange';

    $constantsfield['réseaux > port > nombre d\'octets entrés'] = 'ifinoctets';
    $constantsfield['networking > port > number of bytes in'] = 'ifinoctets';
    $constantsfield['Netzwerk > Port > Anzahl eingegangene Bytes'] = 'ifinoctets';

    $constantsfield['réseaux > port > nombre d\'octets sortis'] = 'ifoutoctets';
    $constantsfield['networking > port > number of bytes out'] = 'ifoutoctets';
    $constantsfield['Netzwerk > Port > Anzahl ausgehende Bytes'] = 'ifoutoctets';

    $constantsfield['réseaux > port > nombre d\'erreurs entrées'] = 'ifinerrors';
    $constantsfield['networking > port > number of input errors'] = 'ifinerrors';
    $constantsfield['Netzwerk > Port > Anzahl Input Fehler'] = 'ifinerrors';

    $constantsfield['réseaux > port > nombre d\'erreurs sorties'] = 'ifouterrors';
    $constantsfield['networking > port > number of output errors'] = 'ifouterrors';
    $constantsfield['Netzwerk > Port > Anzahl Fehler Ausgehend'] = 'ifouterrors';

    $constantsfield['réseaux > port > statut de la connexion'] = 'ifstatus';
    $constantsfield['networking > port > connection status'] = 'ifstatus';
    $constantsfield['Netzwerk > Port > Verbingungszustand'] = 'ifstatus';

    $constantsfield['réseaux > port > adresse MAC'] = 'ifPhysAddress';
    $constantsfield['networking > port > MAC address'] = 'ifPhysAddress';
    $constantsfield['Netzwerk > Port > MAC Adresse'] = 'ifPhysAddress';

    $constantsfield['réseaux > port > nom'] = 'ifName';
    $constantsfield['networking > port > name'] = 'ifName';
    $constantsfield['Netzwerk > Port > Name'] = 'ifName';

    $constantsfield['réseaux > port > type'] = 'ifType';
    $constantsfield['networking > ports > type'] = 'ifType';
    $constantsfield['Netzwerk > Ports > Typ'] = 'ifType';

    $constantsfield['réseaux > port > description du port'] = 'ifdescr';
    $constantsfield['networking > port > port description'] = 'ifdescr';
    $constantsfield['Netzwerk > Port > Port Bezeichnung'] = 'ifdescr';

    $constantsfield['réseaux > port > type de duplex'] = 'portDuplex';
    $constantsfield['networking > port > duplex type'] = 'portDuplex';
    $constantsfield['Netzwerk > Port > Duplex Typ'] = 'portDuplex';

    $constantsfield['imprimante > modèle'] = 'model';
    $constantsfield['printer > model'] = 'model';
    $constantsfield['Drucker > Modell'] = 'model';

    $constantsfield['imprimante > fabricant'] = 'enterprise';
    $constantsfield['printer > manufacturer'] = 'enterprise';
    $constantsfield['Drucker > Hersteller'] = 'enterprise';

    $constantsfield['imprimante > numéro de série'] = 'serial';
    $constantsfield['printer > serial number'] = 'serial';
    $constantsfield['Drucker > Seriennummer'] = 'serial';

    $constantsfield['imprimante > contact'] = 'contact';
    $constantsfield['printer > contact'] = 'contact';
    $constantsfield['Drucker > Kontakt'] = 'contact';

    $constantsfield['imprimante > description'] = 'comments';
    $constantsfield['printer > comments'] = 'comments';
    $constantsfield['Drucker > Kommentar'] = 'comments';

    $constantsfield['imprimante > nom'] = 'name';
    $constantsfield['printer > name'] = 'name';
    $constantsfield['Drucker > Name'] = 'name';

    $constantsfield['imprimante > numéro d\'inventaire'] = 'otherserial';
    $constantsfield['printer > Inventory number'] = 'otherserial';
    $constantsfield['Drucker > Inventarnummer'] = 'otherserial';

    $constantsfield['imprimante > mémoire totale'] = 'memory';
    $constantsfield['printer > total memory'] = 'memory';
    $constantsfield['Drucker > Gesamter Speicher'] = 'memory';

    $constantsfield['imprimante > lieu'] = 'location';
    $constantsfield['printer > location'] = 'location';
    $constantsfield['Drucker > Standort'] = 'location';

    $constantsfield['Informations diverses regroupées'] = 'informations';
    $constantsfield['Many informations grouped'] = 'informations';
    $constantsfield['Many informations grouped'] = 'informations';

    $constantsfield['Toner Noir'] = 'tonerblack';
    $constantsfield['Black toner'] = 'tonerblack';

    $constantsfield['Toner Noir Max'] = 'tonerblackmax';
    $constantsfield['Black toner Max'] = 'tonerblackmax';

    $constantsfield['Toner Noir Utilisé'] = 'tonerblackused';

    $constantsfield['Toner Noir Restant'] = 'tonerblackremaining';

    $constantsfield['Toner Noir'] = 'tonerblack2';
    $constantsfield['Black toner'] = 'tonerblack2';

    $constantsfield['Toner Noir Max'] = 'tonerblack2max';
    $constantsfield['Black toner Max'] = 'tonerblack2max';

    $constantsfield['Toner Noir Utilisé'] = 'tonerblack2used';

    $constantsfield['Toner Noir Restant'] = 'tonerblack2remaining';

    $constantsfield['Toner Cyan'] = 'tonercyan';
    $constantsfield['Cyan toner'] = 'tonercyan';

    $constantsfield['Toner Cyan Max'] = 'tonercyanmax';
    $constantsfield['Cyan toner Max'] = 'tonercyanmax';

    $constantsfield['Toner Cyan Utilisé'] = 'tonercyanused';

    $constantsfield['Toner Cyan Restant'] = 'tonercyanremaining';

    $constantsfield['Toner Magenta'] = 'tonermagenta';
    $constantsfield['Magenta toner'] = 'tonermagenta';

    $constantsfield['Toner Magenta Max'] = 'tonermagentamax';
    $constantsfield['Magenta toner Max'] = 'tonermagentamax';

    $constantsfield['Toner Magenta Utilisé'] = 'tonermagentaused';
    $constantsfield['Magenta toner Utilisé'] = 'tonermagentaused';

    $constantsfield['Toner Magenta Restant'] = 'tonermagentaremaining';
    $constantsfield['Magenta toner Restant'] = 'tonermagentaremaining';

    $constantsfield['Toner Jaune'] = 'toneryellow';
    $constantsfield['Yellow toner'] = 'toneryellow';

    $constantsfield['Toner Jaune Max'] = 'toneryellowmax';
    $constantsfield['Yellow toner Max'] = 'toneryellowmax';

    $constantsfield['Toner Jaune Utilisé'] = 'toneryellowused';
    $constantsfield['Yellow toner Utilisé'] = 'toneryellowused';

    $constantsfield['Toner Jaune Restant'] = 'toneryellowremaining';
    $constantsfield['Yellow toner Restant'] = 'toneryellowremaining';

    $constantsfield['Bac récupérateur de déchet'] = 'wastetoner';
    $constantsfield['Waste bin'] = 'wastetoner';
    $constantsfield['Abfalleimer'] = 'wastetoner';

    $constantsfield['Bac récupérateur de déchet Max'] = 'wastetonermax';
    $constantsfield['Waste bin Max'] = 'wastetonermax';

    $constantsfield['Bac récupérateur de déchet Utilisé'] = 'wastetonerused';
    $constantsfield['Waste bin Utilisé'] = 'wastetonerused';

    $constantsfield['Bac récupérateur de déchet Restant'] = 'wastetonerremaining';
    $constantsfield['Waste bin Restant'] = 'wastetonerremaining';

    $constantsfield['Cartouche noir'] = 'cartridgeblack';
    $constantsfield['Black ink cartridge'] = 'cartridgeblack';
    $constantsfield['Schwarze Kartusche'] = 'cartridgeblack';

    $constantsfield['Cartouche noir'] = 'cartridgeblackmatte';
    $constantsfield['Black ink cartridge'] = 'cartridgeblackmatte';
    $constantsfield['Schwarze Kartusche'] = 'cartridgeblackmatte';

    $constantsfield['Cartouche noir photo'] = 'cartridgeblackphoto';
    $constantsfield['Photo black ink cartridge'] = 'cartridgeblackphoto';
    $constantsfield['Photoschwarz Kartusche'] = 'cartridgeblackphoto';

    $constantsfield['Cartouche cyan'] = 'cartridgecyan';
    $constantsfield['Cyan ink cartridge'] = 'cartridgecyan';
    $constantsfield['Cyan Kartusche'] = 'cartridgecyan';

    $constantsfield['Cartouche cyan clair'] = 'cartridgecyanlight';
    $constantsfield['Light cyan ink cartridge'] = 'cartridgecyanlight';
    $constantsfield['Leichtes Cyan Kartusche'] = 'cartridgecyanlight';

    $constantsfield['Cartouche magenta'] = 'cartridgemagenta';
    $constantsfield['Magenta ink cartridge'] = 'cartridgemagenta';
    $constantsfield['Magenta Kartusche'] = 'cartridgemagenta';

    $constantsfield['Cartouche magenta clair'] = 'cartridgemagentalight';
    $constantsfield['Light ink magenta cartridge'] = 'cartridgemagentalight';
    $constantsfield['Leichtes Magenta Kartusche'] = 'cartridgemagentalight';

    $constantsfield['Cartouche jaune'] = 'cartridgeyellow';
    $constantsfield['Yellow ink cartridge'] = 'cartridgeyellow';
    $constantsfield['Gelbe Kartusche'] = 'cartridgeyellow';

    $constantsfield['Cartouche grise'] = 'cartridgegrey';
    $constantsfield['Grey ink cartridge'] = 'cartridgegrey';
    $constantsfield['Grau Kartusche'] = 'cartridgegrey';

    $constantsfield['Cartouche grise clair'] = 'cartridgegreylight';
    $constantsfield['Light Grey ink cartridge'] = 'cartridgegreylight';
    $constantsfield['Leichtes Grau Kartusche'] = 'cartridgegreylight';

    $constantsfield['Cartouche le amplificateur de brillance'] = 'cartridgeglossenhancer';
    $constantsfield['Gloss Enhancer ink cartridge'] = 'cartridgeglossenhancer';
    $constantsfield['Gloss Enhancer Kartusche'] = 'cartridgeglossenhancer';

    $constantsfield['Cartouche bleu'] = 'cartridgeblue';
    $constantsfield['Blue ink cartridge'] = 'cartridgeblue';
    $constantsfield['Kartusche blau'] = 'cartridgeblue';

    $constantsfield['Cartouche vert'] = 'cartridgegreen';
    $constantsfield['green ink cartridge'] = 'cartridgegreen';
    $constantsfield['Kartusche grün'] = 'cartridgegreen';

    $constantsfield['Cartouche rouge'] = 'cartridgered';
    $constantsfield['Red ink cartridge'] = 'cartridgered';
    $constantsfield['Kartusche rot'] = 'cartridgered';

    $constantsfield['Cartouche rouge chromatique'] = 'cartridgechromaticred';
    $constantsfield['Chromatic red ink cartridge'] = 'cartridgechromaticred';
    $constantsfield['Kartusche chromatische rot'] = 'cartridgechromaticred';

    $constantsfield['Kit de maintenance'] = 'maintenancekit';
    $constantsfield['Maintenance kit'] = 'maintenancekit';
    $constantsfield['Wartungsmodul'] = 'maintenancekit';

    $constantsfield['Kit de maintenance Max'] = 'maintenancekitmax';
    $constantsfield['Maintenance kit Max'] = 'maintenancekitmax';

    $constantsfield['Kit de maintenance Utilisé'] = 'maintenancekitused';
    $constantsfield['Maintenance kit Used'] = 'maintenancekitused';

    $constantsfield['Kit de maintenance Restant'] = 'maintenancekitremaining';
    $constantsfield['Maintenance kit Remaining'] = 'maintenancekitremaining';

    $constantsfield['Kit de transfert'] = 'transferkit';
    $constantsfield['Transfer kit'] = 'transferkit';
    $constantsfield['Transfermodul'] = 'transferkit';

    $constantsfield['Kit de transfert Max'] = 'transferkitmax';
    $constantsfield['Transfer kit Max'] = 'transferkitmax';

    $constantsfield['Kit de transfert Utilisé'] = 'transferkitused';
    $constantsfield['Transfer kit Used'] = 'transferkitused';

    $constantsfield['Kit de transfert Restant'] = 'transferkitremaining';
    $constantsfield['Transfer kit Remaining'] = 'transferkitremaining';

    $constantsfield['Kit de fusion'] = 'fuserkit';
    $constantsfield['Fuser kit'] = 'fuserkit';
    $constantsfield['fixiereinheitmodul'] = 'fuserkit';

    $constantsfield['Kit de fusion Max'] = 'fuserkitmax';
    $constantsfield['fusion kit Max'] = 'fuserkitmax';

    $constantsfield['Kit de fusion Utilisé'] = 'fuserkitused';
    $constantsfield['Fuser kit used'] = 'fuserkitused';

    $constantsfield['Kit de fusion Restant'] = 'fuserkitremaining';
    $constantsfield['Fuser kit remaining'] = 'fuserkitremaining';

    $constantsfield['Tambour Noir'] = 'drumblack';
    $constantsfield['Black drum'] = 'drumblack';

    $constantsfield['Tambour Noir Max'] = 'drumblackmax';
    $constantsfield['Black drum Max'] = 'drumblackmax';

    $constantsfield['Tambour Noir Utilisé'] = 'drumblackused';
    $constantsfield['Black drum Utilisé'] = 'drumblackused';

    $constantsfield['Tambour Noir Restant'] = 'drumblackremaining';
    $constantsfield['Black drum Restant'] = 'drumblackremaining';

    $constantsfield['Tambour Cyan'] = 'drumcyan';
    $constantsfield['Cyan drum'] = 'drumcyan';

    $constantsfield['Tambour Cyan Max'] = 'drumcyanmax';
    $constantsfield['Cyan drum Max'] = 'drumcyanmax';

    $constantsfield['Tambour Cyan Utilisé'] = 'drumcyanused';
    $constantsfield['Cyan drum Utilisé'] = 'drumcyanused';

    $constantsfield['Tambour Cyan Restant'] = 'drumcyanremaining';
    $constantsfield['Cyan drumRestant'] = 'drumcyanremaining';

    $constantsfield['Tambour Magenta'] = 'drummagenta';
    $constantsfield['Magenta drum'] = 'drummagenta';

    $constantsfield['Tambour Magenta Max'] = 'drummagentamax';
    $constantsfield['Magenta drum Max'] = 'drummagentamax';

    $constantsfield['Tambour Magenta Utilisé'] = 'drummagentaused';
    $constantsfield['Magenta drum Utilisé'] = 'drummagentaused';

    $constantsfield['Tambour Magenta Restant'] = 'drummagentaremaining';
    $constantsfield['Magenta drum Restant'] = 'drummagentaremaining';

    $constantsfield['Tambour Jaune'] = 'drumyellow';
    $constantsfield['Yellow drum'] = 'drumyellow';

    $constantsfield['Tambour Jaune Max'] = 'drumyellowmax';
    $constantsfield['Yellow drum Max'] = 'drumyellowmax';

    $constantsfield['Tambour Jaune Utilisé'] = 'drumyellowused';
    $constantsfield['Yellow drum Utilisé'] = 'drumyellowused';

    $constantsfield['Tambour Jaune Restant'] = 'drumyellowremaining';
    $constantsfield['Yellow drum Restant'] = 'drumyellowremaining';

    $constantsfield['imprimante > compteur > nombre total de pages imprimées'] =
                  'pagecountertotalpages';
    $constantsfield['printer > meter > total number of printed pages'] = 'pagecountertotalpages';
    $constantsfield['Drucker > Messung > Gesamtanzahl gedruckter Seiten'] = 'pagecountertotalpages';

    $constantsfield['imprimante > compteur > nombre de pages noir et blanc imprimées'] =
                  'pagecounterblackpages';
    $constantsfield['printer > meter > number of printed black and white pages'] =
                  'pagecounterblackpages';
    $constantsfield['Drucker > Messung > Gesamtanzahl gedrucker Schwarz/Wei&szlig; Seiten'] =
                  'pagecounterblackpages';

    $constantsfield['imprimante > compteur > nombre de pages couleur imprimées'] =
                  'pagecountercolorpages';
    $constantsfield['printer > meter > number of printed color pages'] = 'pagecountercolorpages';
    $constantsfield['Drucker > Messung > Gesamtanzahl gedruckter Farbseiten'] =
                  'pagecountercolorpages';

    $constantsfield['imprimante > compteur > nombre de pages recto/verso imprimées'] =
                  'pagecounterrectoversopages';
    $constantsfield['printer > meter > number of printed duplex pages'] =
                  'pagecounterrectoversopages';
    $constantsfield['Drucker > Messung > Anzahl der gedruckten Duplex Seiten'] =
                  'pagecounterrectoversopages';

    $constantsfield['imprimante > compteur > nombre de pages scannées'] = 'pagecounterscannedpages';
    $constantsfield['printer > meter > nomber of scanned pages'] = 'pagecounterscannedpages';
    $constantsfield['Drucker > Messung > Anzahl der gescannten Seiten'] = 'pagecounterscannedpages';

    $constantsfield['imprimante > compteur > nombre total de pages imprimées (impression)'] =
                  'pagecountertotalpages_print';
    $constantsfield['printer > meter > total number of printed pages (print mode)'] =
                  'pagecountertotalpages_print';
    $constantsfield['Drucker > Messung > Gesamtanzahl gedruckter Seiten (Druck)'] =
                  'pagecountertotalpages_print';

    $constantsfield['imprimante > compteur > nombre de pages noir et blanc imprimées (impression)'] =
                  'pagecounterblackpages_print';
    $constantsfield['printer > meter > number of printed black and white pages (print mode)'] =
                  'pagecounterblackpages_print';
    $constantsfield['Drucker > Messung > Gesamtanzahl gedruckter Schwarz/Wei&szlig; Seiten (Druck)'] =
                  'pagecounterblackpages_print';

    $constantsfield['imprimante > compteur > nombre de pages couleur imprimées (impression)'] =
                  'pagecountercolorpages_print';
    $constantsfield['printer > meter > number of printed color pages (print mode)'] =
                  'pagecountercolorpages_print';
    $constantsfield['Drucker > Messung > Gesamtanzahl farbig gedruckter Seiten (Druck)'] =
                  'pagecountercolorpages_print';

    $constantsfield['imprimante > compteur > nombre total de pages imprimées (copie)'] =
                  'pagecountertotalpages_copy';
    $constantsfield['printer > meter > total number of printed pages (copy mode)'] =
                  'pagecountertotalpages_copy';
    $constantsfield['Drucker > Messung > Gesamtanzahl gedruckter Seiten (Kopie)'] =
                  'pagecountertotalpages_copy';

    $constantsfield['imprimante > compteur > nombre de pages noir et blanc imprimées (copie)'] =
                  'pagecounterblackpages_copy';
    $constantsfield['printer > meter > number of printed black and white pages (copy mode)'] =
                  'pagecounterblackpages_copy';
    $constantsfield['Drucker > Messung > Gesamtanzahl gedruckter Schwarz/Wei&szlig; Seite (Kopie)'] =
                  'pagecounterblackpages_copy';

    $constantsfield['imprimante > compteur > nombre de pages couleur imprimées (copie)'] =
                  'pagecountercolorpages_copy';
    $constantsfield['printer > meter > number of printed color pages (copy mode)'] =
                  'pagecountercolorpages_copy';
    $constantsfield['Drucker > Messung > Gesamtanzahl farbig gedruckter Seiten (Kopie)'] =
                  'pagecountercolorpages_copy';

    $constantsfield['imprimante > compteur > nombre total de pages imprimées (fax)'] =
                  'pagecountertotalpages_fax';
    $constantsfield['printer > meter > total number of printed pages (fax mode)'] =
                  'pagecountertotalpages_fax';
    $constantsfield['Drucker > Messung > Gesamtanzahl gedruckter Seiten (Fax)'] =
                  'pagecountertotalpages_fax';

    $constantsfield['imprimante > compteur > nombre total de pages larges imprimées'] =
                  'pagecounterlargepages';
    $constantsfield['printer > meter > total number of large printed pages'] =
                  'pagecounterlargepages';

    $constantsfield['imprimante > port > adresse MAC'] = 'ifPhysAddress';
    $constantsfield['printer > port > MAC address'] = 'ifPhysAddress';
    $constantsfield['Drucker > Port > MAC Adresse'] = 'ifPhysAddress';

    $constantsfield['imprimante > port > nom'] = 'ifName';
    $constantsfield['printer > port > name'] = 'ifName';
    $constantsfield['Drucker > Port > Name'] = 'ifName';

    $constantsfield['imprimante > port > adresse IP'] = 'ifaddr';
    $constantsfield['printer > port > IP address'] = 'ifaddr';
    $constantsfield['Drucker > Port > IP Adresse'] = 'ifaddr';

    $constantsfield['imprimante > port > type'] = 'ifType';
    $constantsfield['printer > port > type'] = 'ifType';
    $constantsfield['Drucker > port > Typ'] = 'ifType';

    $constantsfield['imprimante > port > numéro index'] = 'ifIndex';
    $constantsfield['printer > port > index number'] = 'ifIndex';
    $constantsfield['Drucker > Port > Indexnummer'] = 'ifIndex';

    if ($DB->tableExists("glpi_plugin_tracker_snmp_history")) {
       //echo "Converting history port ...\n";
        $i = 0;
        $nb = count($constantsfield);
         $migration->addKey(
             "glpi_plugin_tracker_snmp_history",
             "Field"
         );
        $migration->addKey(
            "glpi_plugin_tracker_snmp_history",
            ["Field", "old_value"],
            "Field_2"
        );
        $migration->addKey(
            "glpi_plugin_tracker_snmp_history",
            ["Field", "new_value"],
            "Field_3"
        );
        $migration->migrationOneTable("glpi_plugin_tracker_snmp_history");

        $update = $DB->buildUpdate(
            'glpi_plugin_tracker_snmp_history',
            [
            'Field'  => new \QueryParam()
            ],
            [
            'Field'  => new \QueryParam()
            ]
        );
        $stmt = $DB->prepare($update);
        foreach ($constantsfield as $langvalue => $mappingvalue) {
            $i++;
            $stmt->bind_param(
                'ss',
                $mappingvalue,
                $langvalue
            );
            $DB->executeStatement($stmt);
            $migration->displayMessage("$i / $nb");
        }
        mysqli_stmt_close($stmt);
        $migration->displayMessage("$i / $nb");

        // Move connections from glpi_plugin_glpiinventory_snmp_history to
        // glpi_plugin_glpiinventory_snmp_history_connections
        //echo "Moving creation connections history\n";
        $hist_iterator = $DB->request([
            'FROM'   => 'glpi_plugin_tracker_snmp_history',
            'WHERE'  => [
                'Field' => 0,
                'OR' => [
                    'NOT' => ['old_value' => ['LIKE', '%:%']],
                    'old_value' => null,
                ],
            ]
        ]);
        $stmt = null;
        if (count($hist_iterator)) {
            $nb = count($hist_iterator);
            $i = 0;
            $migration->displayMessage("$i / $nb");
            foreach ($hist_iterator as $data) {
                $i++;

                // Search port from mac address
                $iterator = $DB->request([
                 'FROM'   => 'glpi_networkports',
                 'WHERE'  => ['mac' => $data['new_value']]
                ]);
                if (count($iterator) == 1) {
                     $input = [];
                     $data_port = $iterator->current();
                     $input['FK_port_source'] = $data_port['id'];

                     $port_iterator = $DB->request([
                      'FROM'   => 'glpi_networkports',
                      'WHERE'  => [
                         'items_id'  => $data['new_device_ID'],
                         'itemtype'  => $data['new_device_type']
                      ]
                     ]);
                    if (count($port_iterator) == 1) {
                        if ($stmt == null) {
                            $insert = $DB->buildInsert(
                                'glpi_plugin_fusinvsnmp_networkportconnectionlogs',
                                [
                                'date_mod'                    => new \QueryParam(),
                                'creation'                    => new \QueryParam(),
                                'networkports_id_source'      => new \QueryParam(),
                                'networkports_id_destination' => new \QueryParam()
                                ]
                            );
                            $stmt = $DB->prepare($insert);
                        }
                         $data_port2 = $port_iterator->current();
                         $input['FK_port_destination'] = $data_port2['id'];

                         $input['date'] = $data['date_mod'];
                         $input['creation'] = 1;
                         $input['process_number'] = $data['FK_process'];

                         $stmt->bind_param(
                             'ssss',
                             $input['date'],
                             $input['creation'],
                             $input['FK_port_source'],
                             $input['FK_port_destination']
                         );
                         $DB->executeStatement($stmt);
                    }
                }

                $DB->delete(
                    'glpi_plugin_tracker_snmp_history',
                    [
                    'id'  => $data['ID']
                    ]
                );
                if (preg_match("/000$/", $i)) {
                     $migration->displayMessage("$i / $nb");
                }
            }
            $migration->displayMessage("$i / $nb");
        }
        if ($stmt !== null) {
            mysqli_stmt_close($stmt);
        }

        //echo "Moving deleted connections history\n";
        $hist_iterator = $DB->request([
            'FROM'   => 'glpi_plugin_tracker_snmp_history',
            'WHERE'  => [
                'Field' => 0,
                'OR' => [
                    'NOT' => ['new_value' => ['LIKE', '%:%']],
                    ['new_value' => null]
                ]
            ]
        ]);

        $stmt = null;
        if (count($hist_iterator)) {
            $nb = count($hist_iterator);
            $i = 0;
            $migration->displayMessage("$i / $nb");
            foreach ($hist_iterator as $data) {
                $i++;

                // Search port from mac address
                $iterator = $DB->request([
                    'FROM'   => 'glpi_networkports',
                    'WHERE'  => ['mac' => $data['old_value']]
                ]);
                if (count($iterator) == 1) {
                     $input = [];
                     $data_port = $iterator->current();
                     $input['FK_port_source'] = $data_port['id'];

                     $port_iterator = $DB->request([
                        'FROM'   => 'glpi_networkports',
                        'WHERE'  => [
                            'items_id'  => $data['old_device_ID'],
                            'itemtype'  => $data['old_device_type']
                        ]
                     ]);
                    if (count($port_iterator) == 1) {
                         $data_port2 = $port_iterator->current();
                         $input['FK_port_destination'] = $data_port2['id'];

                         $input['date'] = $data['date_mod'];
                         $input['creation'] = 1;
                         $input['process_number'] = $data['FK_process'];
                        if ($input['FK_port_source'] != $input['FK_port_destination']) {
                            if ($stmt == null) {
                                $insert = $DB->buildInsert(
                                    'glpi_plugin_fusinvsnmp_networkportconnectionlogs',
                                    [
                                    'date_mod'                    => new \QueryParam(),
                                    'creation'                    => new \QueryParam(),
                                    'networkports_id_source'      => new \QueryParam(),
                                    'networkports_id_destination' => new \QueryParam()
                                    ]
                                );
                                    $stmt = $DB->prepare($insert);
                            }

                            $stmt->bind_param(
                                'ssss',
                                $input['date'],
                                $input['creation'],
                                $input['FK_port_source'],
                                $input['FK_port_destination']
                            );
                            $DB->executeStatement($stmt);
                        }
                    }
                }

                $DB->delete(
                    'glpi_plugin_tracker_snmp_history',
                    [
                    'ID' => $data['ID']
                    ]
                );
                if (preg_match("/000$/", $i)) {
                     $migration->displayMessage("$i / $nb");
                }
            }
            $migration->displayMessage("$i / $nb");
        }
        if ($stmt !== null) {
            mysqli_stmt_close($stmt);
        }
    }
}


/**
 * Manage the migration of MySQL tables / fields
 *
 * @global object $DB
 * @param object $migration
 * @param array $a_table
 */
function migratePluginTables($migration, $a_table)
{
    global $DB;

    foreach ($a_table['oldname'] as $oldtable) {
        $migration->renameTable($oldtable, $a_table['name']);
        renamePluginFields($migration, $a_table['name']);
    }

    if (!$DB->tableExists($a_table['name'])) {
        if (strstr($a_table['name'], 'glpi_plugin_glpiinventory_dblock')) {
            $query = "CREATE TABLE `" . $a_table['name'] . "` (
                        `value` int NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`value`)
                     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        } else {
            $query = "CREATE TABLE `" . $a_table['name'] . "` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`id`)
                     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        }

        $DB->query($query);
    }

    foreach ($a_table['renamefields'] as $old => $new) {
        $migration->changeField(
            $a_table['name'],
            $old,
            $new,
            $a_table['fields'][$new]['type'],
            ['value' => $a_table['fields'][$new]['value'],
            'update' => true]
        );
    }

    foreach ($a_table['oldkeys'] as $field) {
        $migration->dropKey(
            $a_table['name'],
            $field
        );
    }
    $migration->migrationOneTable($a_table['name']);

    foreach ($a_table['oldfields'] as $field) {
        $migration->dropField(
            $a_table['name'],
            $field
        );
    }
    $migration->migrationOneTable($a_table['name']);

    foreach ($a_table['fields'] as $field => $data) {
        $migration->changeField(
            $a_table['name'],
            $field,
            $field,
            $data['type'],
            ['value' => $data['value']]
        );
    }
    $migration->migrationOneTable($a_table['name']);

    foreach ($a_table['fields'] as $field => $data) {
        $migration->addField(
            $a_table['name'],
            $field,
            $data['type'],
            ['value' => $data['value']]
        );
    }
    $migration->migrationOneTable($a_table['name']);

    foreach ($a_table['keys'] as $data) {
        $migration->addKey(
            $a_table['name'],
            $data['field'],
            $data['name'],
            $data['type']
        );
    }
    $migration->migrationOneTable($a_table['name']);

    $DB->listFields($a_table['name'], false);
}


/**
 * Migrate tables from plugin fusinvdeploy
 *    all datas in exploded tables are merged and stored in json in order table
 *
 * @global object $DB
 * @param  object $migration
 */
function migrateTablesFromFusinvDeploy($migration)
{
    global $DB;

    if (
        $DB->tableExists("glpi_plugin_glpiinventory_deployorders")
         && $DB->tableExists("glpi_plugin_fusinvdeploy_checks")
         && $DB->tableExists("glpi_plugin_fusinvdeploy_files")
         && $DB->tableExists("glpi_plugin_fusinvdeploy_actions")
    ) {
       //add json field in deploy order table to store datas from old misc tables
        $field_created = $migration->addField(
            "glpi_plugin_glpiinventory_deployorders",
            "json",
            "longtext DEFAULT NULL"
        );
        $migration->migrationOneTable("glpi_plugin_glpiinventory_deployorders");

        $final_datas = [];

       //== glpi_plugin_glpiinventory_deployorders ==
        $o_iterator = $DB->request(['FROM' => 'glpi_plugin_glpiinventory_deployorders']);
        foreach ($o_iterator as $o_datas) {
            $order_id = $o_datas['id'];

            $o_line = [];
            $of_line = [];

            $o_line['checks'] = [];
            $o_line['actions'] = [];
            $o_line['associatedFiles'] = [];

            //=== Checks ===

            if ($DB->tableExists("glpi_plugin_fusinvdeploy_checks")) {
                $iterator = $DB->request([
                    'SELECT' => [
                        'type',
                        'path',
                        'value',
                        'error AS return'
                    ],
                    'FROM'   => 'glpi_plugin_fusinvdeploy_checks',
                    'WHERE'  => [
                        'plugin_fusinvdeploy_orders_id' => $order_id
                    ],
                    'ORDER'  => [
                        'ranking ASC'
                    ]
                ]);

                $c_i = 0;
                foreach ($iterator as $c_datas) {
                    foreach ($c_datas as $c_key => $c_value) {
                       //specific case for filesytem sizes, convert to bytes
                        if (
                            !empty($c_value)
                            && is_numeric($c_value)
                            && $c_datas['type'] !== 'freespaceGreater'
                        ) {
                            $c_value = $c_value * 1024 * 1024;
                        }

                       //construct job check entry
                        $o_line['checks'][$c_i][$c_key] = $c_value;
                    }
                    $c_i++;
                }
            }

            $files_list = [];
            //=== Files ===
            if ($DB->tableExists("glpi_plugin_fusinvdeploy_files")) {
                $f_iterator = $DB->request([
                    'SELECT' => [
                        'id',
                        'name',
                        'is_p2p AS p2p',
                        'filesize',
                        'mimetype',
                        'p2p_retention_days AS p2p-retention-duration',
                        'uncompress',
                        'sha512'
                    ],
                    'FROM' => 'glpi_plugin_fusinvdeploy_files',
                    'WHERE' => [
                        'plugin_fusinvdeploy_orders_id' => $order_id
                    ]
                ]);
                foreach ($f_iterator as $f_datas) {
                  //jump to next entry if sha512 is empty
                  // This kind of entries could happen sometimes on upload errors
                    if (empty($f_datas['sha512'])) {
                        continue;
                    }

                  //construct job file entry
                    $o_line['associatedFiles'][] = $f_datas['sha512'];

                    foreach ($f_datas as $f_key => $f_value) {
                         //we don't store the sha512 field in json
                        if (
                            $f_key == "sha512"
                             || $f_key == "id"
                             || $f_key == "filesize"
                             || $f_key == "mimetype"
                        ) {
                            continue;
                        }

                       //construct order file entry
                        $of_line[$f_datas['sha512']][$f_key] = $f_value;
                    }

                    if (!in_array($f_datas['sha512'], $files_list)) {
                        $files_list[] = $f_datas['sha512'];
                    }
                }
            }

            //=== Actions ===
            $cmdStatus['RETURNCODE_OK'] = 'okCode';
            $cmdStatus['RETURNCODE_KO'] = 'errorCode';
            $cmdStatus['REGEX_OK'] = 'okPattern';
            $cmdStatus['REGEX_KO'] = 'errorPattern';

            if ($DB->tableExists("glpi_plugin_fusinvdeploy_actions")) {
                $a_iterator = $DB->request([
                    'FROM' => 'glpi_plugin_fusinvdeploy_actions',
                    'WHERE' => [
                        'plugin_fusinvdeploy_orders_id' => $order_id
                    ],
                    'ORDER' => [
                        'ranking ASC'
                    ]
                ]);

                $a_i = 0;
                foreach ($a_iterator as $a_datas) {
                    //get type
                    $type = strtolower(str_replace("PluginFusinvdeployAction_", "", $a_datas['itemtype']));

                    //specific case for command type
                    $type = str_replace("command", "cmd", $type);

                    //table for action itemtype
                    $a_table = getTableForItemType($a_datas['itemtype']);

                    //get table fields
                    $at_iterator = $DB->request([
                        'FROM' => $a_table,
                        'WHERE' => [
                            'id' => $a_datas['items_id']
                        ]
                    ]);
                    foreach ($at_iterator as $at_datas) {
                        foreach ($at_datas as $at_key => $at_value) {
                            //we don't store the id field of action itemtype table in json
                            if ($at_key == "id") {
                                continue;
                            }

                            //specific case for 'path' field
                            if ($at_key == "path") {
                                $o_line['actions'][$a_i][$type]['list'][] = $at_value;
                            } else {
                                //construct job actions entry
                                $o_line['actions'][$a_i][$type][$at_key] = $at_value;
                            }
                        }

                        //specific case for commands : we must add status and env vars
                        if ($a_datas['itemtype'] === "PluginFusinvdeployAction_Command") {
                            $ret_cmd_iterator = $DB->request([
                                'SELECT' => [
                                    'type',
                                    'value'
                                ],
                                'FROM' => 'glpi_plugin_fusinvdeploy_actions_commandstatus',
                                'WHERE' => [
                                    'plugin_fusinvdeploy_commands_id' => $at_datas['id']
                                ]
                            ]);
                            foreach ($ret_cmd_iterator as $res_cmd_datas) {
                                // Skip empty retchecks type:
                                // This surely means they have been drop at some point but entry has not been
                                // removed from database.
                                if (!empty($res_cmd_datas['type'])) {
                                      //construct command status array entry
                                      $o_line['actions'][$a_i][$type]['retChecks'][] = [
                                      'type'  => $cmdStatus[$res_cmd_datas['type']],
                                      'values' => [$res_cmd_datas['value']]
                                      ];
                                }
                            }
                        }
                    }
                    $a_i++;
                }
            }
            $final_datas[$order_id]['jobs'] = $o_line;
            $final_datas[$order_id]['associatedFiles'] = $of_line;
            unset($o_line);
            unset($of_line);
        }
        $options = 0;
        $options = $options | JSON_UNESCAPED_SLASHES;

       //store json in order table
        if (count($final_datas)) {
            $update = $DB->buildUpdate(
                'glpi_plugin_glpiinventory_deployorders',
                [
                'json'   => new \QueryParam()
                ],
                [
                'id'     => new \QueryParam()
                ]
            );
            $stmt = $DB->prepare($update);
            foreach ($final_datas as $order_id => $data) {
                 $json = $DB->escape(json_encode($data, $options));

                 $stmt->bind_param(
                     'ss',
                     $json,
                     $order_id
                 );
            }
        }
    }

   //=== Fileparts ===
    if (
        $DB->tableExists('glpi_plugin_fusinvdeploy_fileparts')
           && $DB->tableExists('glpi_plugin_fusinvdeploy_files')
    ) {
        $files_list = $DB->request('glpi_plugin_fusinvdeploy_files');
       // multipart file datas
        foreach ($files_list as $file) {
            $sha = $file['sha512'];
            if (empty($sha)) {
                continue;
            }
            $shortsha = substr($sha, 0, 6);
            $fp_iterator = $DB->request([
                'SELECT' => [
                    'fp.sha512 AS filepart_hash',
                    'f.sha512  AS file_hash'
                ],
                'FROM' => 'glpi_plugin_fusinvdeploy_files AS f',
                'INNER JOIN' => [
                    'glpi_plugin_fusinvdeploy_fileparts AS fp' => [
                        'ON' => [
                            'f' => 'id',
                            'fp' => 'plugin_fusinvdeploy_files_id', [
                                'AND' => ['f.shortsha512' => $shortsha]
                            ]
                        ]
                    ]
                ],
                'GROUP BY' => 'fp.sha512',
                'ORDER' => 'fp.id'
            ]);
            if (count($fp_iterator) > 0) {
                $fhandle = fopen(
                    GLPI_PLUGIN_DOC_DIR . "/glpiinventory/files/manifests/{$sha}",
                    'w+'
                );
                foreach ($fp_iterator as $fp_datas) {
                    if ($fp_datas['file_hash'] === $sha) {
                        fwrite($fhandle, $fp_datas['filepart_hash'] . "\n");
                    }
                }
                fclose($fhandle);
            }
        }
    }

   //migrate fusinvdeploy_files
    if ($DB->tableExists("glpi_plugin_fusinvdeploy_files")) {
        $DB->query("TRUNCATE TABLE `glpi_plugin_glpiinventory_deployfiles`");
        if ($DB->fieldExists("glpi_plugin_fusinvdeploy_files", "filesize")) {
            $f_iterator = $DB->request([
                'SELECT' => [
                    'files.id',
                    'files.name',
                    'files.filesize',
                    'files.mimetype',
                    'files.sha512',
                    'files.shortsha512',
                    'files.create_date',
                    'files.entities_id',
                    'files.is_recursive'
                ],
                'FROM' => 'glpi_plugin_fusinvdeploy_files AS files',
                'WHERE' => [
                    'files.shortsha512' => [
                        '!=' => ''
                    ]
                ]
            ]);
            foreach ($f_iterator as $f_datas) {
                 $entry = [
                  "id"        => $f_datas["id"],
                  "name"      => $f_datas["name"],
                  "filesize"  => $f_datas["filesize"],
                  "mimetype"  => $f_datas["mimetype"],
                  "shortsha512"  => $f_datas["shortsha512"],
                  "sha512"  => $f_datas["sha512"],
                  "comments"  => "",
                  "date_mod"  => $f_datas["create_date"],
                  "entities_id"  => $f_datas["entities_id"],
                  "is_recursive"  => $f_datas["is_recursive"],
                 ];
                 $migration->displayMessage("\n");
                 // Check if file exists
                 $i_DeployFile = new PluginGlpiinventoryDeployFile();
                 $migration->displayMessage(
                     "migrating file " . $entry['name'] .
                     " sha:" . $entry['sha512'] .
                     "\n"
                 );
                if ($i_DeployFile->checkPresenceManifest($entry['sha512'])) {
                     $migration->displayMessage(
                         "manifest exists" .
                          "\n"
                     );
                      $migration->insertInTable(
                          "glpi_plugin_glpiinventory_deployfiles",
                          $entry
                      );
                }
            }
        }
    }

   /**
    * JSON orders fixer:
    *    This piece of code makes sure that JSON orders in database are valid and will fix it
    *    otherwise.
    */

    $packages = $DB->request('glpi_plugin_glpiinventory_deploypackages');
    foreach ($packages as $order_config) {
        $json_order = json_decode($order_config['json']);
       //print("deployorders fixer : actual order structure for ID ".$order_config['id']."\n" . print_r($json_order,true) ."\n");

       // Checks for /jobs json property
        if (!isset($json_order->jobs) || !is_object($json_order->jobs)) {
           //print("deployorders fixer : create missing required 'jobs' property\n");
            $json_order->jobs = new stdClass();
        }

        if (!isset($json_order->jobs->checks)) {
           //print("deployorders fixer : create missing required '/jobs/checks' array property\n");
            $json_order->jobs->checks = [];
        }
        if (!isset($json_order->jobs->actions)) {
           //print("deployorders fixer : create missing required '/jobs/actions' array property\n");
            $json_order->jobs->actions = [];
        }
        if (!isset($json_order->jobs->associatedFiles)) {
           //print("deployorders fixer : create missing required '/jobs/associatedFiles' array property\n");
            $json_order->jobs->associatedFiles = [];
        }

       // Checks for /associatedFiles json property
        if (!isset($json_order->associatedFiles) || !is_object($json_order->associatedFiles)) {
           //print("deployorders fixer : create missing required 'associatedFiles' property\n");
            $json_order->associatedFiles = new stdClass();
        }
       //print(
         //"deployorders fixer : final order structure for ID ".$order_config['id']."\n" .
       //   json_encode($json_order,JSON_PRETTY_PRINT) ."\n"
       //);
        $pfDeployPackageItem = new PluginGlpiinventoryDeployPackageItem();
        $pfDeployPackageItem->updateOrderJson($order_config['id'], $json_order);
    }

   /**
    * Drop unused tables
    */
    $old_deploy_tables = [
      'glpi_plugin_fusinvdeploy_actions',
      'glpi_plugin_fusinvdeploy_actions_commandenvvariables',
      'glpi_plugin_fusinvdeploy_actions_commands',
      'glpi_plugin_fusinvdeploy_actions_commandstatus',
      'glpi_plugin_fusinvdeploy_actions_copies',
      'glpi_plugin_fusinvdeploy_actions_deletes',
      'glpi_plugin_fusinvdeploy_actions_messages',
      'glpi_plugin_fusinvdeploy_actions_mkdirs',
      'glpi_plugin_fusinvdeploy_actions_moves',
      'glpi_plugin_fusinvdeploy_checks',
      'glpi_plugin_fusinvdeploy_fileparts',
      'glpi_plugin_fusinvdeploy_files',
      'glpi_plugin_fusinvdeploy_files_mirrors',
      'glpi_plugin_glpiinventory_inventorycomputerstorages',
      'glpi_plugin_glpiinventory_inventorycomputerstoragetypes',
      'glpi_plugin_glpiinventory_inventorycomputerstorages_storages'
    ];
    foreach ($old_deploy_tables as $table) {
        $migration->dropTable($table);
    }
}

function renamePlugin(Migration $migration)
{
    global $DB;

    $tables = $DB->listTables('glpi_plugin_fusioninventory%');
    if (count($tables)) {
       //plugin has not yet been renamed; we should not have any tables with new name.
        $new_tables = $DB->listTables('glpi_plugin_glpiinventory%');
        foreach ($new_tables as $new_table) {
            $migration->dropTable($new_table);
        }
    }
    foreach ($tables as $table) {
        $old_table = $table['TABLE_NAME'];
        $new_table = str_replace('fusioninventory', 'glpiinventory', $old_table);
        $migration->renameTable($old_table, $new_table);
        renamePluginFields($migration, $new_table);
    }

    // Rename itemtypes
    $itemtypes_iterator = $DB->request(
        [
            'SELECT' => [
                'information_schema.columns.table_name AS TABLE_NAME',
                'information_schema.columns.column_name AS COLUMN_NAME',
            ],
            'FROM'   => 'information_schema.columns',
            'INNER JOIN'   => [
                'information_schema.tables' => [
                    'FKEY' => [
                        'information_schema.tables'  => 'table_name',
                        'information_schema.columns' => 'table_name',
                        [
                            'AND' => [
                                'information_schema.tables.table_schema' => new QueryExpression(
                                    $DB->quoteName('information_schema.columns.table_schema')
                                ),
                            ]
                        ],
                    ]
                ]
            ],
            'WHERE'  => [
                'information_schema.tables.table_type'    => 'BASE TABLE',
                'information_schema.columns.table_schema' => $DB->dbdefault,
                'information_schema.columns.table_name'   => ['LIKE', 'glpi\_%'],
                'OR' => [
                    ['information_schema.columns.column_name'  => 'itemtype'],
                    ['information_schema.columns.column_name'  => ['LIKE', 'itemtype_%']],
                ],
            ],
            'ORDER'  => 'information_schema.columns.table_name',
        ]
    );

    foreach ($itemtypes_iterator as $itemtype) {
        $table_name   = $itemtype['TABLE_NAME'];
        $itemtype_col = $itemtype['COLUMN_NAME'];

        $DB->update(
            $table_name,
            [
                $itemtype_col => new \QueryExpression(
                    'REPLACE(' . $DB->quoteName($itemtype_col) . ', "PluginFusioninventory", "PluginGlpiinventory")'
                )
            ],
            [
                $itemtype_col => ['LIKE', 'PluginFusioninventory%']
            ]
        );
    }
}

function renamePluginFields(Migration $migration, string $table)
{
    global $DB;

    if (!$DB->tableExists($table)) {
        return;
    }

    $has_changes = false;
    $fields = $DB->listFields($table, false);
    foreach ($fields as $field) {
        $old_field = $field['Field'];
        if (preg_match('/plugin_fusioninventory.*_id/', $old_field)) {
            $new_field = str_replace('fusion', 'glpi', $old_field);
            $migration->changeField(
                $table,
                $old_field,
                $new_field,
                'int unsigned NOT NULL DEFAULT 0'
            );
            $migration->dropKey($table, $old_field);
            $migration->addKey($table, $new_field);
            $has_changes = true;
        }
    }
    if ($has_changes) {
        $migration->migrationOneTable($table);
    }
}
