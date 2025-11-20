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

use Glpi\Http\Firewall;
use Glpi\Http\SessionManager;
use Glpi\Plugin\Hooks;

use function Safe\define;
use function Safe\parse_url;

define('PLUGIN_GLPIINVENTORY_VERSION', '1.6.2');
// Minimal GLPI version, inclusive
define('PLUGIN_GLPI_INVENTORY_GLPI_MIN_VERSION', '11.0.2');
// Maximum GLPI version, exclusive
define('PLUGIN_GLPI_INVENTORY_GLPI_MAX_VERSION', '11.0.99');
// Used for use config values in 'cache'
$PF_CONFIG = [];
// used to know if computer inventory is in reallity a ESX task
$PF_ESXINVENTORY = false;

define('PLUGIN_GLPI_INVENTORY_DIR', __DIR__);

define("PLUGIN_GLPI_INVENTORY_XML", '');

define("PLUGIN_GLPI_INVENTORY_OFFICIAL_RELEASE", "0");
define("PLUGIN_GLPI_INVENTORY_REALVERSION", PLUGIN_GLPIINVENTORY_VERSION . " SNAPSHOT");

define(
    "PLUGIN_GLPI_INVENTORY_REPOSITORY_DIR",
    GLPI_PLUGIN_DOC_DIR . "/glpiinventory/files/repository/"
);
define(
    "PLUGIN_GLPI_INVENTORY_MANIFESTS_DIR",
    GLPI_PLUGIN_DOC_DIR . "/glpiinventory/files/manifests/"
);

define(
    "PLUGIN_GLPI_INVENTORY_UPLOAD_DIR",
    GLPI_PLUGIN_DOC_DIR . "/glpiinventory/upload/"
);

/**
 * Check if the script name finish by
 *
 * @param string $scriptname
 * @return boolean
 */
function plugin_glpiinventory_script_endswith($scriptname)
{
    //append plugin directory to avoid dumb errors...
    $requested = 'glpiinventory/front/' . $scriptname;
    $current = parse_url($_SERVER['REQUEST_URI'] ?? '')['path'];

    return str_ends_with($current, $requested);
}


/**
 * Init hook
 *
 * @global array $PLUGIN_HOOKS
 * @global array $CFG_GLPI
 */
function plugin_init_glpiinventory()
{
    /** @var array $PF_CONFIG */
    global $PLUGIN_HOOKS, $CFG_GLPI, $PF_CONFIG;

    $current_url = parse_url($_SERVER['REQUEST_URI'] ?? '')['path'];

    $Plugin = new Plugin();

    $debug_mode = false;
    if (isset($_SESSION['glpi_use_mode'])) {
        $debug_mode = ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE);
    }

    if ($Plugin->isActivated('glpiinventory')) { // check if plugin is active
        // Disable firewall checks for machine to machine endpoints
        Firewall::addPluginStrategyForLegacyScripts('glpiinventory', '#^/index\.php#', Firewall::STRATEGY_NO_CHECK);
        Firewall::addPluginStrategyForLegacyScripts('glpiinventory', '#^/b/#', Firewall::STRATEGY_NO_CHECK);
        Firewall::addPluginStrategyForLegacyScripts('glpiinventory', '#^/front/communication.php#', Firewall::STRATEGY_NO_CHECK);

        //for dashboard
        $CFG_GLPI['javascript']['admin']['pluginglpiinventorymenu'] = [
            'dashboard', 'gridstack',
            'charts', 'clipboard', 'sortable',
        ];

        $PLUGIN_HOOKS[Hooks::DASHBOARD_CARDS]['glpiinventory'] = 'plugin_glpiinventory_hook_dashboard_cards';

        // Register classes into GLPI plugin factory
        $Plugin->registerClass(
            PluginGlpiinventoryAgentmodule::class,
            [
                'addtabon' => [
                    'Agent',
                ],
            ]
        );
        $Plugin->registerClass(PluginGlpiinventoryConfig::class);
        $Plugin->registerClass(PluginGlpiinventoryTask::class, ['addtabon' => PluginGlpiinventoryIPRange::class]);

        $Plugin->registerClass(
            PluginGlpiinventoryTaskjob::class,
            [
                'addtabon' => [
                    PluginGlpiinventoryTask::class,
                ],
            ]
        );

        $Plugin->registerClass(
            PluginGlpiinventoryTaskjobstate::class,
            [
                'addtabon' => [
                    PluginGlpiinventoryTask::class,
                    Computer::class,
                ],
            ]
        );

        $Plugin->registerClass(PluginGlpiinventoryModule::class);
        $Plugin->registerClass(
            PluginGlpiinventoryProfile::class,
            ['addtabon' => [Profile::class]]
        );
        $Plugin->registerClass(PluginGlpiinventorySetup::class);
        $Plugin->registerClass(PluginGlpiinventoryIPRange::class);
        $Plugin->registerClass(
            PluginGlpiinventoryIPRange_SNMPCredential::class,
            ['addtabon' => PluginGlpiinventoryIPRange::class]
        );
        $Plugin->registerClass(PluginGlpiinventoryCredential::class);
        $Plugin->registerClass(PluginGlpiinventoryTimeslot::class);

        $Plugin->registerClass(
            PluginGlpiinventoryCollect::class,
            ['addtabon' => [Computer::class]]
        );
        $Plugin->registerClass(
            PluginGlpiinventoryCollect_Registry::class,
            ['addtabon' => [PluginGlpiinventoryCollect::class]]
        );
        $Plugin->registerClass(
            PluginGlpiinventoryCollect_Registry_Content::class,
            ['addtabon' => [PluginGlpiinventoryCollect::class]]
        );
        $Plugin->registerClass(
            PluginGlpiinventoryCollect_Wmi::class,
            ['addtabon' => [PluginGlpiinventoryCollect::class]]
        );
        $Plugin->registerClass(
            PluginGlpiinventoryCollect_Wmi_Content::class,
            ['addtabon' => [PluginGlpiinventoryCollect::class]]
        );
        $Plugin->registerClass(
            PluginGlpiinventoryCollect_File::class,
            ['addtabon' => [PluginGlpiinventoryCollect::class]]
        );
        $Plugin->registerClass(
            PluginGlpiinventoryCollect_File_Content::class,
            ['addtabon' => [PluginGlpiinventoryCollect::class]]
        );

        // Networkinventory and networkdiscovery
        $Plugin->registerClass(PluginGlpiinventoryStateDiscovery::class);
        $Plugin->registerClass(PluginGlpiinventoryDeployGroup::class);
        $Plugin->registerClass(
            PluginGlpiinventoryDeployGroup_Staticdata::class,
            ['addtabon' => [PluginGlpiinventoryDeployGroup::class]]
        );
        $Plugin->registerClass(
            PluginGlpiinventoryDeployGroup_Dynamicdata::class,
            ['addtabon' => [PluginGlpiinventoryDeployGroup::class]]
        );
        $Plugin->registerClass(
            PluginGlpiinventoryDeployPackage::class,
            ['addtabon' => [Computer::class]]
        );

        // ##### 3. get informations of the plugin #####

        $Plugin->getFromDBbyDir('glpiinventory');

        // Load config
        PluginGlpiinventoryConfig::loadCache();

        // ##### 5. Set in session XMLtags of methods #####
        $_SESSION['glpi_plugin_glpiinventory']['xmltags']['NETWORKDISCOVERY']
                                             = PluginGlpiinventoryCommunicationNetworkDiscovery::class;
        $_SESSION['glpi_plugin_glpiinventory']['xmltags']['NETWORKINVENTORY']
                                             = PluginGlpiinventoryCommunicationNetworkInventory::class;

        // set default values for task view
        if (!isset($_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'])) {
            $_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'] = 2;
        }
        if (!isset($_SESSION['glpi_plugin_glpiinventory']['refresh'])) {
            $_SESSION['glpi_plugin_glpiinventory']['refresh'] = 'off';
        }

        $PLUGIN_HOOKS['import_item']['glpiinventory'] = [
            'Computer' => ['Plugin'],
        ];

        $CFG_GLPI["specif_entities_tables"][] = 'glpi_plugin_glpiinventory_ipranges';

        $CFG_GLPI['threads_networkdiscovery'] = $PF_CONFIG['threads_networkdiscovery'];
        $CFG_GLPI['threads_networkinventory'] = $PF_CONFIG['threads_networkinventory'];
        $CFG_GLPI['timeout_networkdiscovery'] = $PF_CONFIG['timeout_networkdiscovery'];
        $CFG_GLPI['timeout_networkinventory'] = $PF_CONFIG['timeout_networkinventory'];

        /**
         * Load the relevant javascript/css files only on pages that need them.
         */
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'] = [];
        $PLUGIN_HOOKS[Hooks::ADD_CSS]['glpiinventory'] = [];
        if (
            str_contains($current_url, $CFG_GLPI['root_doc'] . '/plugins/glpiinventory/')
            || str_ends_with($current_url, "front/printer.form.php")
            || str_ends_with($current_url, "front/computer.form.php")
        ) {
            $PLUGIN_HOOKS[Hooks::ADD_CSS]['glpiinventory'][] = "css/views" . ($debug_mode || !file_exists(__DIR__ . '/public/css/views.min.css') ? "" : ".min") . ".css";
            $PLUGIN_HOOKS[Hooks::ADD_CSS]['glpiinventory'][] = "css/deploy" . ($debug_mode || !file_exists(__DIR__ . '/public/css/deploy.min.css') ? "" : ".min") . ".css";
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'][] = "lib/d3/d3" . ($debug_mode ? "" : ".min") . ".js";
        }
        if (plugin_glpiinventory_script_endswith("timeslot.form.php")) {
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'][] = "lib/timeslot" . ($debug_mode || !file_exists(__DIR__ . '/public/lib/timeslot.min.js') ? "" : ".min") . ".js";
        }
        if (plugin_glpiinventory_script_endswith("deploypackage.form.php")) {
            $PLUGIN_HOOKS[Hooks::ADD_CSS]['glpiinventory'][] = "lib/extjs/resources/css/ext-all.css";
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'][] = "lib/extjs/adapter/ext/ext-base" . ($debug_mode ? "-debug" : "") . ".js";
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'][] = "lib/extjs/ext-all" . ($debug_mode ? "-debug" : "") . ".js";
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'][] = "lib/REDIPS_drag/redips-drag" . ($debug_mode ? "-source" : "-min") . ".js";
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'][] = "lib/REDIPS_drag/drag_table_rows.js";
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'][] = "lib/plusbutton" . ($debug_mode ? "" : ".min") . ".js";
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'][] = "lib/deploy_editsubtype" . ($debug_mode || !file_exists(__DIR__ . '/public/lib/deploy_editsubtype.min.js') ? "" : ".min") . ".js";
        }
        if (plugin_glpiinventory_script_endswith("task.form.php")
        || plugin_glpiinventory_script_endswith("taskjob.php")
        || plugin_glpiinventory_script_endswith("iprange.form.php")) {
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'][] = "lib/lazy.js-0.5.1/lazy" . ($debug_mode ? "" : ".min") . ".js";
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'][] = "lib/mustache.js-2.3.0/mustache" . ($debug_mode ? "" : ".min") . ".js";
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'][] = "js/taskjobs" . ($debug_mode || !file_exists(__DIR__ . '/public/js/taskjobs.min.js') ? "" : ".min") . ".js";
        }
        if (plugin_glpiinventory_script_endswith("menu.php")) {
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'][] = "js/stats" . ($debug_mode || !file_exists(__DIR__ . '/public/js/stats.min.js') ? "" : ".min") . ".js";
        }

        if (
            Session::haveRight('plugin_glpiinventory_configuration', READ)
              || Session::haveRight('profile', UPDATE)
        ) {// Config page
            $PLUGIN_HOOKS['config_page']['glpiinventory'] = 'front/config.form.php'
                 . '?itemtype=pluginfusioninventoryconfig&glpi_tab=1';
        }

        $PLUGIN_HOOKS['use_massive_action']['glpiinventory'] = 1;

        $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['glpiinventory'] = [
            'Plugin' => 'plugin_pre_item_update_glpiinventory',
        ];

        $PLUGIN_HOOKS[Hooks::PRE_ITEM_PURGE]['glpiinventory'] = [
            Computer::class => 'plugin_pre_item_purge_glpiinventory',
            NetworkPort_NetworkPort::class => 'plugin_pre_item_purge_glpiinventory',
        ];
        $p = [
            NetworkPort_NetworkPort::class => 'plugin_item_purge_glpiinventory',
            PluginGlpiinventoryTask::class => ['PluginGlpiinventoryTask', 'purgeTask'],
            PluginGlpiinventoryTaskjob::class => ['PluginGlpiinventoryTaskjob', 'purgeTaskjob'],
            PluginGlpiinventoryTimeslot::class => 'plugin_item_purge_glpiinventory',
            Entity::class => 'plugin_item_purge_glpiinventory',
            PluginGlpiinventoryDeployPackage::class => 'plugin_item_purge_glpiinventory',
        ];
        $PLUGIN_HOOKS[Hooks::ITEM_PURGE]['glpiinventory'] = $p;

        if (Session::haveRight('plugin_glpiinventory_menu', READ)) {
            $PLUGIN_HOOKS["menu_toadd"]['glpiinventory']['admin'] = PluginGlpiinventoryMenu::class;
        }

        // For end users
        if (
            isset($_SESSION['glpiactiveprofile']['interface'])
              && $_SESSION['glpiactiveprofile']['interface'] == 'helpdesk'
        ) {
            $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
            if ($pfDeployPackage->canUserDeploySelf()) {
                $PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY]['glpiinventory'] = '/front/deploypackage.public.php';
                $PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY_ICON]['glpiinventory'] = 'ti ti-package';
                $PLUGIN_HOOKS[Hooks::ADD_CSS]['glpiinventory'][] = "css/views" . ($debug_mode || !file_exists(__DIR__ . '/public/css/views.min.css') ? "" : ".min") . ".css";
            }
        }

        // load task view css for computer self deploy (tech)
        if (str_ends_with($current_url, "front/computer.form.php")) {
            $PLUGIN_HOOKS[Hooks::ADD_CSS]['glpiinventory'][] = "css/views" . ($debug_mode || !file_exists(__DIR__ . '/public/css/views.min.css') ? "" : ".min") . ".css";
        }

        if (isset($_SESSION["glpiname"])) {
            /*
             * Deploy submenu entries
             */

            // Load nvd3 for printerpage counter graph
            if (
                str_ends_with($current_url, '/front/printer.form.php')
                 || str_ends_with($current_url, 'glpiinventory/front/menu.php')
            ) {
                // Add graph javascript
                $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'] = array_merge(
                    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['glpiinventory'],
                    [
                        "lib/nvd3/nv.d3.min.js",
                    ]
                );
                // Add graph css
                $PLUGIN_HOOKS[Hooks::ADD_CSS]['glpiinventory'] = array_merge(
                    $PLUGIN_HOOKS[Hooks::ADD_CSS]['glpiinventory'],
                    [
                        "lib/nvd3/nv.d3.min.css",
                    ]
                );
            }
        }
    } else { // plugin not active, need $moduleId for uninstall check
        include_once(PLUGIN_GLPI_INVENTORY_DIR . '/inc/module.class.php');
    }

    // exclude some pages from splitted layout
    if (isset($CFG_GLPI['layout_excluded_pages'])) {
        // to be compatible with glpi 0.85
        $CFG_GLPI['layout_excluded_pages'][] = "timeslot.form.php";
    }

    $PLUGIN_HOOKS[Hooks::PROLOG_RESPONSE]['glpiinventory'] = 'plugin_glpiinventory_prolog_response';
    $PLUGIN_HOOKS[Hooks::NETWORK_DISCOVERY]['glpiinventory'] = 'plugin_glpiinventory_network_discovery';
    $PLUGIN_HOOKS[Hooks::NETWORK_INVENTORY]['glpiinventory'] = 'plugin_glpiinventory_network_inventory';

    // Support JSON protocol CONTACT requests from agents
    $PLUGIN_HOOKS[Hooks::HANDLE_NETDISCOVERY_TASK]['glpiinventory'] = 'plugin_glpiinventory_handle_netdiscovery_task';
    $PLUGIN_HOOKS[Hooks::HANDLE_NETINVENTORY_TASK]['glpiinventory'] = 'plugin_glpiinventory_handle_netinventory_task';
    $PLUGIN_HOOKS[Hooks::HANDLE_ESX_TASK]['glpiinventory'] = 'plugin_glpiinventory_handle_esx_task';
    $PLUGIN_HOOKS[Hooks::HANDLE_COLLECT_TASK]['glpiinventory'] = 'plugin_glpiinventory_handle_collect_task';
    $PLUGIN_HOOKS[Hooks::HANDLE_DEPLOY_TASK]['glpiinventory'] = 'plugin_glpiinventory_handle_deploy_task';
}


/**
 * Manage the version information of the plugin
 *
 * @return array
 */
function plugin_version_glpiinventory()
{
    return [
        'name'           => 'GLPI Inventory',
        'shortname'      => 'glpiinventory',
        'version'        => PLUGIN_GLPIINVENTORY_VERSION,
        'license'        => 'AGPLv3+',
        'oldname'        => 'fusioninventory',
        'author'         => 'Teclib\'',
        'homepage'       => 'https://github.com/glpi-project/glpi-inventory-plugin',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_GLPI_INVENTORY_GLPI_MIN_VERSION,
                'max' => PLUGIN_GLPI_INVENTORY_GLPI_MAX_VERSION,
                'dev' => PLUGIN_GLPI_INVENTORY_OFFICIAL_RELEASE == 0, //@phpstan-ignore equal.alwaysTrue
            ],
            'php' => [
                'exts'   => [
                    'fileinfo'  => [
                        'required'  => true,
                        'class'     => 'finfo',
                    ],
                ],
            ],
        ],
    ];
}


/**
 * Manage / check the prerequisites of the plugin
 *
 * @global DBMysql $DB
 * @return boolean
 */
function plugin_glpiinventory_check_prerequisites()
{
    return true;
}


/**
 * Check the rights
 *
 * @param string $type
 * @param string $right
 * @return boolean
 */
function plugin_glpiinventory_haveTypeRight($type, $right)
{
    return true;
}

function plugin_glpiinventory_options()
{
    return [
        'autoinstall_disabled' => true,
    ];
}


function plugin_glpiinventory_boot()
{
    SessionManager::registerPluginStatelessPath('glpiinventory', '#^/$#');
    SessionManager::registerPluginStatelessPath('glpiinventory', '#^/Communication$#');
    SessionManager::registerPluginStatelessPath('glpiinventory', '#^/front/communication.php$#');
}
