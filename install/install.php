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

/**
 * This function manage the installation of the plugin.
 *
 * @global object $DB
 * @param string $version
 * @param string $migrationname class name related to Migration class of GLPI
 */
function pluginGlpiinventoryInstall($version, $migrationname = 'Migration')
{
    global $CFG_GLPI, $DB;

    ini_set("memory_limit", "-1");
    ini_set("max_execution_time", "0");

    $migration = new $migrationname($version);

   /*
    * Load classes
    */
    require_once(PLUGIN_GLPI_INVENTORY_DIR . '/inc/commonview.class.php');
    require_once(PLUGIN_GLPI_INVENTORY_DIR . '/inc/taskjobview.class.php');
    require_once(PLUGIN_GLPI_INVENTORY_DIR . '/inc/taskview.class.php');
    require_once(PLUGIN_GLPI_INVENTORY_DIR . '/inc/deploypackageitem.class.php');
    require_once(PLUGIN_GLPI_INVENTORY_DIR . '/inc/collectcommon.class.php');
    require_once(PLUGIN_GLPI_INVENTORY_DIR . '/inc/collectcontentcommon.class.php');
    foreach (glob(PLUGIN_GLPI_INVENTORY_DIR . '/inc/*.php') as $file) {
        require_once($file);
    }

    $migration->displayMessage("GLPI Inventory plugin installation");

   // Get information of plugin

   /*
    * Clean if plugin has been installed and uninstalled (not clean correctly)
    */
    $migration->displayMessage("Clean data from old installation of the plugin");

    $DB->delete(
        'glpi_displaypreferences',
        [
         'itemtype'  => [
            '5150',
            '5151',
            '5152',
            '5153',
            '5156',
            '5157',
            '5158',
            '5159',
            '5161',
            '5165',
            '5166',
            '5167',
            '5168',
         ]
        ]
    );

    $DB->delete(
        'glpi_displaypreferences',
        [
         'itemtype' => ['LIKE', 'PluginGlpiinventory%']
        ]
    );
    $DB->delete(
        'glpi_displaypreferences',
        [
         'itemtype' => ['LIKE', 'PluginFusioninventory%']
        ]
    );
    $DB->delete(
        'glpi_displaypreferences',
        [
         'itemtype' => ['LIKE', 'PluginFusinvinventory%']
        ]
    );
    $DB->delete(
        'glpi_displaypreferences',
        [
         'itemtype' => ['LIKE', 'PluginFusinvsnmp%']
        ]
    );

   // Purge network ports have itemtype tp 5153
    $networkPort = new NetworkPort();
    $iterator = $DB->request([
      'FROM'   => 'glpi_networkports',
      'WHERE'  => ['itemtype' => '5153']
    ]);
    foreach ($iterator as $data) {
        $networkPort->delete(['id' => $data['id']], 1);
    }

   /*
    * Remove old rules
    */
    $migration->displayMessage("Clean rules from old installation of the plugin");
    $Rule = new Rule();
    $a_rules = $Rule->find(['sub_type' => 'PluginGlpiinventoryInventoryRuleImport']);
    foreach ($a_rules as $data) {
        $Rule->delete($data);
    }
    $a_rules = $Rule->find(['sub_type' => 'PluginFusinvinventoryRuleEntity']);
    foreach ($a_rules as $data) {
        $Rule->delete($data);
    }

    $a_rules = $Rule->find(['sub_type' => 'PluginFusinvinventoryRuleLocation']);
    foreach ($a_rules as $data) {
        $Rule->delete($data);
    }

   /*
    * Create DB structure
    */
    $migration->displayMessage("Creation tables in database");
    $DB_file = PLUGIN_GLPI_INVENTORY_DIR . "/install/mysql/plugin_glpiinventory-empty.sql";
    if (!$DB->runFile($DB_file)) {
        $migration->displayMessage("Error on creation tables in database");
    }

   /*
    * Creation of folders
    */
    $migration->displayMessage("Creation of folders");
    if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory')) {
        mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory');
    }
    if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/tmp')) {
        mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/tmp');
    }
    if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/upload')) {
        mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/upload');
    }

   /*
    * Deploy folders
    */
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

   /*
    * Manage profiles
    */
    $migration->displayMessage("Initialize profiles");
    PluginGlpiinventoryProfile::initProfile();

   /*
    * Add config
    */
    $migration->displayMessage("Initialize configuration");
    $pfConfig = new PluginGlpiinventoryConfig();
    $pfConfig->initConfigModule();

   /*
    * Register Agent TASKS
    */
    $migration->displayMessage("Initialize agent TASKS");
    $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
    $input = [];
    $input['modulename'] = "WAKEONLAN";
    $input['is_active']  = 0;
    $input['exceptions'] = exportArrayToDB([]);
    $pfAgentmodule->add($input);

    $input = [];
    $input['modulename'] = "INVENTORY";
    $input['is_active']  = 1;
    $input['exceptions'] = exportArrayToDB([]);
    $pfAgentmodule->add($input);

    $input = [];
    $input['modulename'] = "InventoryComputerESX";
    $input['is_active']  = 0;
    $input['exceptions'] = exportArrayToDB([]);
    $pfAgentmodule->add($input);

    $input = [];
    $input['modulename'] = "NETWORKINVENTORY";
    $input['is_active']  = 0;
    $input['exceptions'] = exportArrayToDB([]);
    $pfAgentmodule->add($input);

    $input = [];
    $input['modulename'] = "NETWORKDISCOVERY";
    $input['is_active']  = 0;
    $input['exceptions'] = exportArrayToDB([]);
    $pfAgentmodule->add($input);

    $input = [];
    $input['modulename'] = "DEPLOY";
    $input['is_active']  = 1;
    $input['exceptions'] = exportArrayToDB([]);
    $pfAgentmodule->add($input);

    $input = [];
    $input['modulename'] = "Collect";
    $input['is_active']  = 1;
    $input['exceptions'] = exportArrayToDB([]);
    $pfAgentmodule->add($input);

   /*
    * Add cron task
    */
    $migration->displayMessage("Initialize cron task");
    CronTask::Register(
        'PluginGlpiinventoryTask',
        'taskscheduler',
        '60',
        ['mode' => 2, 'allowmode' => 3, 'logs_lifetime' => 30]
    );
    CronTask::Register(
        'PluginGlpiinventoryTaskjobstate',
        'cleantaskjob',
        (3600 * 24),
        ['mode' => 2, 'allowmode' => 3, 'logs_lifetime' => 30]
    );
    CronTask::Register(
        'PluginGlpiinventoryAgentWakeup',
        'wakeupAgents',
        120,
        ['mode' => 2, 'allowmode' => 3, 'logs_lifetime' => 30,
        'comment' => Toolbox::addslashes_deep(__(
            'Wake agents ups',
            'glpiinventory'
        ))]
    );
    CronTask::Register(
        'PluginGlpiinventoryTask',
        'cleanondemand',
        86400,
        ['mode' => 2, 'allowmode' => 3, 'logs_lifetime' => 30,
        'comment' => Toolbox::addslashes_deep(__('Clean on demand deployment tasks'))]
    );

   /*
    * Add notification for configuration management
    */

    CronTask::Register(
        'PluginGlpiinventoryTaskjobstate',
        'cleantaskjob',
        (3600 * 24),
        ['mode' => 2, 'allowmode' => 3, 'logs_lifetime' => 30]
    );

    require_once(PLUGIN_GLPI_INVENTORY_DIR . "/inc/inventorycomputerstat.class.php");
    PluginGlpiinventoryInventoryComputerStat::init();

    /*
     * Define when install agent_base_url in entity, unless:
     *  - it is already defined,
     *  - it matches the GLPI base URL.
     */
    $agent_base_url = Entity::getUsedConfig('agent_base_url', 0, 'agent_base_url', '');

    if (empty($agent_base_url)) {
        $full_url = filter_input(INPUT_SERVER, "PHP_SELF");
        $https = filter_input(INPUT_SERVER, "HTTPS");
        $http_host = filter_input(INPUT_SERVER, "HTTP_HOST");

        if ($full_url && (strpos($full_url, '/ajax/marketplace.php') !== false || strpos($full_url, '/front/plugin.form.php') !== false)) {
            $agent_base_url = str_replace(
                ['/ajax/marketplace.php', '/front/plugin.form.php'],
                '',
                (!empty($https) ? 'https://' : 'http://') . $http_host . $full_url
            );
            if ($agent_base_url !== $CFG_GLPI['url_base']) {
                $DB->update(
                    'glpi_entities',
                    [
                        'agent_base_url' => $agent_base_url
                    ],
                    [
                        'id'             => 0
                    ]
                );
            }
        }
    }
}
