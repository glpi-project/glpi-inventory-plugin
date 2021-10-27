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

define ("PLUGIN_GLPI_INVENTORY_VERSION", "10.0.0+1.0");
// Minimal GLPI version, inclusive
define('PLUGIN_GLPI_INVENTORY_GLPI_MIN_VERSION', '9.5');
// Maximum GLPI version, exclusive
define('PLUGIN_GLPI_INVENTORY_GLPI_MAX_VERSION', '11.0');
// Used for use config values in 'cache'
$PF_CONFIG = [];
// used to know if computer inventory is in reallity a ESX task
$PF_ESXINVENTORY = false;

define('PLUGIN_GLPI_INVENTORY_DIR', __DIR__);

define ("PLUGIN_GLPI_INVENTORY_XML", '');

define ("PLUGIN_GLPI_INVENTORY_OFFICIAL_RELEASE", "0");
define ("PLUGIN_GLPI_INVENTORY_REALVERSION", PLUGIN_GLPI_INVENTORY_VERSION . " SNAPSHOT");

define("PLUGIN_GLPI_INVENTORY_REPOSITORY_DIR",
       GLPI_PLUGIN_DOC_DIR."/glpiinventory/files/repository/");
define("PLUGIN_GLPI_INVENTORY_MANIFESTS_DIR",
       GLPI_PLUGIN_DOC_DIR."/glpiinventory/files/manifests/");
define("PLUGIN_GLPI_INVENTORY_XML_DIR",
       GLPI_PLUGIN_DOC_DIR."/glpiinventory/xml/");

include_once GLPI_ROOT .'/plugins/glpiinventory/vendor/autoload.php';

/**
 * Check if the script name finish by
 *
 * @param string $scriptname
 * @return boolean
 */
function script_endswith($scriptname) {
   $script_name = filter_input(INPUT_SERVER, "SCRIPT_NAME");
   return substr($script_name, -strlen($scriptname))===$scriptname;
}


/**
 * Init hook
 *
 * @global array $PLUGIN_HOOKS
 * @global array $CFG_GLPI
 */
function plugin_init_glpiinventory() {
   global $PLUGIN_HOOKS, $CFG_GLPI, $_UGET;

   $PLUGIN_HOOKS['csrf_compliant']['glpiinventory'] = true;

   $Plugin = new Plugin();
   $moduleId = 0;

   $debug_mode = false;
   if (isset($_SESSION['glpi_use_mode'])) {
      $debug_mode = ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE);
   }

   if ($Plugin->isActivated('glpiinventory')) { // check if plugin is active

      // Register classes into GLPI plugin factory

      $Plugin->registerClass('PluginGlpiinventoryAgent',
         [
            'addtabon' => [
               'Printer',
               'NetworkEquipment',
               'PluginGlpiinventoryCredentialIp'
            ]
         ]
      );
      $Plugin->registerClass('PluginGlpiinventoryAgentmodule');
      $Plugin->registerClass('PluginGlpiinventoryConfig');
      $Plugin->registerClass('PluginGlpiinventoryTask');

      $Plugin->registerClass('PluginGlpiinventoryTaskjob',
         [
            'addtabon' => [
               'PluginGlpiinventoryTask',
            ]
         ]
      );

      $Plugin->registerClass('PluginGlpiinventoryTaskjobstate',
         [
            'addtabon' => [
               'PluginGlpiinventoryTask',
               'Computer',
            ]
         ]
      );

      $Plugin->registerClass('PluginGlpiinventoryUnmanaged');
      $Plugin->registerClass('PluginGlpiinventoryModule');
      $Plugin->registerClass('PluginGlpiinventoryProfile',
              ['addtabon' => ['Profile']]);
      $Plugin->registerClass('PluginGlpiinventoryEntity',
              ['addtabon' => ['Entity']]);
      $Plugin->registerClass('PluginGlpiinventorySetup');
      $Plugin->registerClass('PluginGlpiinventoryIPRange');
      $Plugin->registerClass('PluginGlpiinventoryIPRange_ConfigSecurity',
              ['addtabon' => 'PluginGlpiinventoryIPRange']);
      $Plugin->registerClass('PluginGlpiinventoryCredential');
      $Plugin->registerClass('PluginGlpiinventoryTimeslot');
      $Plugin->registerClass('PluginGlpiinventoryLock',
              ['addtabon' => ['Computer', 'Printer', 'NetworkEquipment']]);

      $Plugin->registerClass('PluginGlpiinventoryInventoryComputerComputer',
              ['addtabon' => ['Computer']]);
      $Plugin->registerClass('PluginGlpiinventoryInventoryComputerInventory');
      $Plugin->registerClass('PluginGlpiinventoryCollect',
                              ['addtabon' => ['Computer']]);
      $Plugin->registerClass('PluginGlpiinventoryCollect_Registry',
              ['addtabon' => ['PluginGlpiinventoryCollect']]);
      $Plugin->registerClass('PluginGlpiinventoryCollect_Registry_Content',
              ['addtabon' => ['PluginGlpiinventoryCollect']]);
      $Plugin->registerClass('PluginGlpiinventoryCollect_Wmi',
              ['addtabon' => ['PluginGlpiinventoryCollect']]);
      $Plugin->registerClass('PluginGlpiinventoryCollect_Wmi_Content',
              ['addtabon' => ['PluginGlpiinventoryCollect']]);
      $Plugin->registerClass('PluginGlpiinventoryCollect_File',
              ['addtabon' => ['PluginGlpiinventoryCollect']]);
      $Plugin->registerClass('PluginGlpiinventoryCollect_File_Content',
              ['addtabon' => ['PluginGlpiinventoryCollect']]);
      $Plugin->registerClass('PluginGlpiinventoryComputerLicenseInfo',
              ['addtabon' => ['Computer']]);
      $Plugin->registerClass('PluginGlpiinventoryComputerRemoteManagement');

         //Classes for rulesengine
      $Plugin->registerClass('PluginGlpiinventoryInventoryRuleLocation');
      $Plugin->registerClass('PluginGlpiinventoryInventoryRuleLocationCollection',
              ['rulecollections_types'=>true]);
      $Plugin->registerClass('PluginGlpiinventoryInventoryRuleEntity');
      $Plugin->registerClass('PluginGlpiinventoryInventoryRuleEntityCollection',
              ['rulecollections_types'=>true]);
      $Plugin->registerClass('PluginGlpiinventoryRulematchedlog',
              ['addtabon' => ['Computer',
                              'Monitor',
                              'NetworkEquipment',
                              'Peripheral',
                              'Phone',
                              'PluginGlpiinventoryAgent',
                              'PluginGlpiinventoryUnmanaged',
                              'Printer']]);

      //Classes for rulesengine
      $Plugin->registerClass('PluginGlpiinventoryInventoryRuleImport');
      $Plugin->registerClass('PluginGlpiinventoryInventoryRuleImportCollection',
              ['rulecollections_types'=>true]);
      $Plugin->registerClass('PluginGlpiinventoryConstructDevice');

      // Networkinventory and networkdiscovery
      $Plugin->registerClass('PluginGlpiinventorySnmpmodel');
      $Plugin->registerClass('PluginGlpiinventoryNetworkEquipment',
              ['addtabon' => ['NetworkEquipment']]);
      $Plugin->registerClass('PluginGlpiinventoryPrinter',
              ['addtabon' => ['Printer']]);
      $Plugin->registerClass('PluginGlpiinventoryPrinterCartridge');
      $Plugin->registerClass('PluginGlpiinventoryConfigSecurity');
      $Plugin->registerClass('PluginGlpiinventoryNetworkPortLog',
              ['addtabon' => ['NetworkPort']]);
      $Plugin->registerClass('PluginFusinvsnmpAgentconfig');
      $Plugin->registerClass('PluginGlpiinventoryNetworkPort');
      $Plugin->registerClass('PluginGlpiinventoryStateDiscovery');
      $Plugin->registerClass('PluginGlpiinventoryPrinterLogReport');
      $Plugin->registerClass('PluginGlpiinventorySnmpmodelConstructdevice_User',
              ['addtabon' => ['User']]);
      $Plugin->registerClass('PluginGlpiinventoryDeployGroup');
      $Plugin->registerClass('PluginGlpiinventoryDeployGroup_Staticdata',
              ['addtabon' => ['PluginGlpiinventoryDeployGroup']]);
      $Plugin->registerClass('PluginGlpiinventoryDeployGroup_Dynamicdata',
              ['addtabon' => ['PluginGlpiinventoryDeployGroup']]);
      $Plugin->registerClass('PluginGlpiinventoryDeployPackage',
              ['addtabon' => ['Computer']]);

      $CFG_GLPI['glpitablesitemtype']["PluginGlpiinventoryPrinterLogReport"] =
                                                      "glpi_plugin_glpiinventory_printers";
      $CFG_GLPI['glpitablesitemtype']["PluginGlpiinventoryComputer"] =
                                                      "glpi_computers";

      // ##### 3. get informations of the plugin #####

      $Plugin->getFromDBbyDir('glpiinventory');
      $moduleId = $Plugin->fields['id'];

      // Load config
      PluginGlpiinventoryConfig::loadCache();

      // ##### 5. Set in session XMLtags of methods #####

      $_SESSION['glpi_plugin_glpiinventory']['xmltags']['WAKEONLAN'] = '';
      $_SESSION['glpi_plugin_glpiinventory']['xmltags']['INVENTORY']
                                             = 'PluginGlpiinventoryInventoryComputerInventory';
      $_SESSION['glpi_plugin_glpiinventory']['xmltags']['NETWORKDISCOVERY']
                                             = 'PluginGlpiinventoryCommunicationNetworkDiscovery';
      $_SESSION['glpi_plugin_glpiinventory']['xmltags']['NETWORKINVENTORY']
                                             = 'PluginGlpiinventoryCommunicationNetworkInventory';

      // set default values for task view
      if (!isset($_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'])) {
         $_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'] = 2;
      }
      if (!isset($_SESSION['glpi_plugin_glpiinventory']['refresh'])) {
         $_SESSION['glpi_plugin_glpiinventory']['refresh'] = 'off';
      }

      $PLUGIN_HOOKS['import_item']['glpiinventory'] = [
          'Computer' => ['Plugin']];

      $CFG_GLPI["specif_entities_tables"][] = 'glpi_plugin_glpiinventory_ipranges';

      $CFG_GLPI["networkport_types"][] = 'PluginGlpiinventoryUnmanaged';
      $CFG_GLPI["networkport_types"][] = 'PluginGlpiinventoryComputer';

      /**
       * Load the relevant javascript/css files only on pages that need them.
       */
      $PLUGIN_HOOKS['add_javascript']['glpiinventory'] = [];
      $PLUGIN_HOOKS['add_css']['glpiinventory'] = [];
      if (strpos(filter_input(INPUT_SERVER, "SCRIPT_NAME"), Plugin::getWebDir('glpiinventory', false)) != false
          || strpos(filter_input(INPUT_SERVER, "SCRIPT_NAME"), "front/printer.form.php") != false
          || strpos(filter_input(INPUT_SERVER, "SCRIPT_NAME"), "front/computer.form.php") != false) {
         $PLUGIN_HOOKS['add_css']['glpiinventory'][]="css/views.css";
         $PLUGIN_HOOKS['add_css']['glpiinventory'][]="css/deploy.css";

         array_push(
            $PLUGIN_HOOKS['add_javascript']['glpiinventory'],
            "lib/d3/d3".($debug_mode?"":".min").".js"
         );
      }
      if (script_endswith("timeslot.form.php")) {
         $PLUGIN_HOOKS['add_javascript']['glpiinventory'][] = "lib/timeslot".($debug_mode?"":".min").".js";
      }
      if (script_endswith("deploypackage.form.php")) {
         $PLUGIN_HOOKS['add_css']['glpiinventory'][]="lib/extjs/resources/css/ext-all.css";

         array_push(
            $PLUGIN_HOOKS['add_javascript']['glpiinventory'],
            "lib/extjs/adapter/ext/ext-base".($debug_mode?"-debug":"").".js",
            "lib/extjs/ext-all".($debug_mode?"-debug":"").".js",
            "lib/REDIPS_drag/redips-drag".($debug_mode?"-source":"-min").".js",
            "lib/REDIPS_drag/drag_table_rows.js",
            "lib/plusbutton".($debug_mode?"":".min").".js",
            "lib/deploy_editsubtype".($debug_mode?"":".min").".js"
         );
      }
      if (script_endswith("task.form.php")
         || script_endswith("taskjob.php")
         || script_endswith("iprange.form.php")) {
         array_push(
            $PLUGIN_HOOKS['add_javascript']['glpiinventory'],
            "lib/lazy.js-0.5.1/lazy".($debug_mode?"":".min").".js",
            "lib/mustache.js-2.3.0/mustache".($debug_mode?"":".min").".js",
            "js/taskjobs".($debug_mode || !file_exists('js/taskjobs.min.js')?"":".min").".js"
         );
      }
      if (script_endswith("menu.php")) {
         $PLUGIN_HOOKS['add_javascript']['glpiinventory'][] = "js/stats".($debug_mode || !file_exists('js/stats.min.js')?"":".min").".js";
      }

      if (Session::haveRight('plugin_glpiinventory_configuration', READ)
              || Session::haveRight('profile', UPDATE)) {// Config page
         $PLUGIN_HOOKS['config_page']['glpiinventory'] = 'front/config.form.php'.
                 '?itemtype=pluginfusioninventoryconfig&glpi_tab=1';
      }

      $PLUGIN_HOOKS['autoinventory_information']['glpiinventory'] = [
            'Computer' =>  ['PluginGlpiinventoryInventoryComputerComputer',
                                 'showComputerInfo'],
            'NetworkEquipment' => ['PluginGlpiinventoryNetworkEquipment',
                                        'showInfo'],
            'Printer' => ['PluginGlpiinventoryPrinter',
                                        'showInfo']];

      $PLUGIN_HOOKS['post_item_form']['glpiinventory']
         = 'plugin_glpiinventory_postItemForm';
      $PLUGIN_HOOKS['post_show_tab']['glpiinventory']
         = 'plugin_glpiinventory_postShowTab';
      $PLUGIN_HOOKS['pre_show_tab']['glpiinventory']
         = 'plugin_glpiinventory_preShowTab';

      $PLUGIN_HOOKS['use_massive_action']['glpiinventory'] = 1;

      $PLUGIN_HOOKS['item_add']['glpiinventory'] = [
            'NetworkPort_NetworkPort' => 'plugin_item_add_glpiinventory',
            'NetworkPort'             => 'plugin_item_add_glpiinventory'
          ];

      $PLUGIN_HOOKS['pre_item_update']['glpiinventory'] = [
            'Plugin' => 'plugin_pre_item_update_glpiinventory'
          ];
      $PLUGIN_HOOKS['item_update']['glpiinventory'] = [
         'Computer'         => 'plugin_item_update_glpiinventory',
         'NetworkEquipment' => 'plugin_item_update_glpiinventory',
         'Printer'          => 'plugin_item_update_glpiinventory',
         'Monitor'          => 'plugin_item_update_glpiinventory',
         'Peripheral'       => 'plugin_item_update_glpiinventory',
         'Phone'            => 'plugin_item_update_glpiinventory',
         'NetworkPort'      => 'plugin_item_update_glpiinventory',
         'PluginGlpiinventoryLock' => ['PluginGlpiinventoryLock', 'deleteLock']
      ];

      $PLUGIN_HOOKS['pre_item_purge']['glpiinventory'] = [
         'Computer'                 =>'plugin_pre_item_purge_glpiinventory',
         'NetworkPort_NetworkPort'  =>'plugin_pre_item_purge_glpiinventory',
         'PluginGlpiinventoryLock'=> ['PluginGlpiinventoryLock', 'deleteLock']
         ];
      $p = [
         'NetworkPort_NetworkPort'            => 'plugin_item_purge_glpiinventory',
         'PluginGlpiinventoryTask'          => ['PluginGlpiinventoryTask', 'purgeTask'],
         'PluginGlpiinventoryTaskjob'       => ['PluginGlpiinventoryTaskjob', 'purgeTaskjob'],
         'PluginGlpiinventoryUnmanaged'     => ['PluginGlpiinventoryUnmanaged', 'purgeUnmanagedDevice'],
         'NetworkEquipment'                   => 'plugin_item_purge_glpiinventory',
         'Printer'                            => 'plugin_item_purge_glpiinventory',
         'PluginGlpiinventoryTimeslot'      => 'plugin_item_purge_glpiinventory',
         'Entity'                             => 'plugin_item_purge_glpiinventory',
         'PluginGlpiinventoryDeployPackage' => 'plugin_item_purge_glpiinventory'
      ];
      $PLUGIN_HOOKS['item_purge']['glpiinventory'] = $p;

      $PLUGIN_HOOKS['item_transfer']['glpiinventory'] = 'plugin_item_transfer_glpiinventory';

      if (Session::haveRight('plugin_glpiinventory_unmanaged', READ)) {
         $PLUGIN_HOOKS["menu_toadd"]['glpiinventory']['assets'] = 'PluginGlpiinventoryUnmanaged';
      }
      if (Session::haveRight('plugin_glpiinventory_menu', READ)) {
         $PLUGIN_HOOKS["menu_toadd"]['glpiinventory']['admin'] = 'PluginGlpiinventoryMenu';
      }

      // For end users
      if (isset($_SESSION['glpiactiveprofile']['interface'])
              && $_SESSION['glpiactiveprofile']['interface'] == 'helpdesk') {
         $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
         if ($pfDeployPackage->canUserDeploySelf()) {
            $PLUGIN_HOOKS['helpdesk_menu_entry']['glpiinventory'] = '/front/deploypackage.public.php';
            $PLUGIN_HOOKS['add_css']['glpiinventory'][]="css/views.css";
         }
      }

      // load task view css for computer self deploy (tech)
      if (script_endswith("computer.form.php")) {
         $PLUGIN_HOOKS['add_css']['glpiinventory'][]="css/views.css";
      }

      if (isset($_SESSION["glpiname"])) {
         $report_list = [];
         if (Session::haveRight('plugin_glpiinventory_reportprinter', READ)) {
            $report_list["front/printerlogreport.php"] = __('Printed page counter', 'glpiinventory');

         }
         if (Session::haveRight('plugin_glpiinventory_reportnetworkequipment', READ)) {
            $report_list["report/switch_ports.history.php"] = __('Switch ports history', 'glpiinventory');

            $report_list["report/ports_date_connections.php"] = __('Unused switch ports', 'glpiinventory');

            $report_list["report/not_queried_recently.php"] = __('Number of days since last inventory', 'glpiinventory');

         }
         if (Session::haveRight('computer', READ)) {
            $report_list["report/computer_last_inventory.php"] = __('Computers not inventoried since xx days', 'glpiinventory');
         }
         $PLUGIN_HOOKS['reports']['glpiinventory'] = $report_list;

         /*
          * Deploy submenu entries
          */

         if (Session::haveRight('plugin_glpiinventory_configuration', READ)) {// Config page
            $PLUGIN_HOOKS['submenu_entry']['glpiinventory']['config'] = 'front/config.form.php';
         }

         $PLUGIN_HOOKS['webservices']['glpiinventory'] = 'plugin_glpiinventory_registerMethods';

         // Hack for NetworkEquipment display ports
         if (strstr(filter_input(INPUT_SERVER, "PHP_SELF"), '/ajax/common.tabs.php')) {
            if (strstr(filter_input(INPUT_GET, "_target"), '/front/networkequipment.form.php')
                    && filter_input(INPUT_GET, "_itemtype") == 'NetworkEquipment') {

               if (filter_input(INPUT_GET, "_glpi_tab") == 'NetworkPort$1') {
                  $_UGET['_glpi_tab'] = 'PluginGlpiinventoryNetworkEquipment$1';
               } else if (filter_input(INPUT_GET, "_glpi_tab") == 'PluginGlpiinventoryNetworkEquipment$1') {
                  $_GET['displaysnmpinfo'] = 1;
               }
            }
         }
         // Load nvd3 for printerpage counter graph
         if (strstr(filter_input(INPUT_SERVER, "PHP_SELF"), '/front/printer.form.php')
                 || strstr(filter_input(INPUT_SERVER, "PHP_SELF"), 'glpiinventory/front/menu.php')) {

            // Add graph javascript
            $PLUGIN_HOOKS['add_javascript']['glpiinventory'] = array_merge(
                  $PLUGIN_HOOKS['add_javascript']['glpiinventory'], [
                     "lib/nvd3/nv.d3.min.js"
                  ]
            );
            // Add graph css
            $PLUGIN_HOOKS['add_css']['glpiinventory'] = array_merge(
                  $PLUGIN_HOOKS['add_css']['glpiinventory'], [
                     "lib/nvd3/nv.d3.css"
                  ]
            );
         }
      }

      // Hack for use meta in group search engine
      $CFG_GLPI['PluginGlpiinventoryComputer_types'] = $CFG_GLPI['link_types'];

   } else { // plugin not active, need $moduleId for uninstall check
      include_once(PLUGIN_GLPI_INVENTORY_DIR.'/inc/module.class.php');
      $moduleId = PluginGlpiinventoryModule::getModuleId('glpiinventory');
   }

   // Check for uninstall
   $id = filter_input(INPUT_GET, "id");
   $action = filter_input(INPUT_GET, "action");
   if ($id == $moduleId
           && $action == 'uninstall'
           && (strstr(filter_input(INPUT_SERVER, "HTTP_REFERER"), "front/plugin.php"))) {

      if (PluginGlpiinventoryModule::getAll(true)) {
          Session::addMessageAfterRedirect(__('Other FusionInventory plugins (fusinv...) must be uninstalled before removing the FusionInventory plugin'));

         Html::redirect($CFG_GLPI["root_doc"]."/front/plugin.php");
         exit;
      }
   }

   // Add unmanaged devices in list of devices with networkport
   //$CFG_GLPI["netport_types"][] = "PluginGlpiinventoryUnmanaged";
   $CFG_GLPI["networkport_types"][] = "PluginGlpiinventoryUnmanaged";

   // exclude some pages from splitted layout
   if (isset($CFG_GLPI['layout_excluded_pages'])) { // to be compatible with glpi 0.85
      array_push($CFG_GLPI['layout_excluded_pages'], "timeslot.form.php");
   }
}


/**
 * Manage the version information of the plugin
 *
 * @return array
 */
function plugin_version_glpiinventory() {
   return ['name'           => 'GLPI Inventory',
           'shortname'      => 'glpiinventory',
           'version'        => PLUGIN_GLPI_INVENTORY_VERSION,
           'license'        => 'AGPLv3+',
           'oldname'        => 'tracker',
           'author'         => 'Teclib\'',
           'homepage'       => 'https://github.com/glpi-project/glpi-inventory-plugin',
           'requirements'   => [
              'glpi' => [
                  'min' => PLUGIN_GLPI_INVENTORY_GLPI_MIN_VERSION,
                  'max' => PLUGIN_GLPI_INVENTORY_GLPI_MAX_VERSION,
                  'dev' => PLUGIN_GLPI_INVENTORY_OFFICIAL_RELEASE == 0
               ],
               'php' => [
                  'exts'   => [
                     'fileinfo'  => [
                        'required'  => true,
                        'class'     => 'finfo'
                     ]
                  ]
               ]
            ]
         ];
}


/**
 * Manage / check the prerequisites of the plugin
 *
 * @global object $DB
 * @return boolean
 */
function plugin_glpiinventory_check_prerequisites() {
   global $DB;

   if (!method_exists('Plugin', 'checkVersions')) {
      $version = rtrim(GLPI_VERSION, '-dev');
      if (version_compare($version, PLUGIN_GLPI_INVENTORY_GLPI_MIN_VERSION, 'lt')) {
         echo "This plugin requires GLPI " . PLUGIN_GLPI_INVENTORY_GLPI_MIN_VERSION;
         return false;
      }

      if (!isset($_SESSION['glpi_plugins'])) {
         $_SESSION['glpi_plugins'] = [];
      }

      if (version_compare(GLPI_VERSION, PLUGIN_GLPI_INVENTORY_GLPI_MIN_VERSION.'-dev', '!=')
         && version_compare(GLPI_VERSION, PLUGIN_GLPI_INVENTORY_GLPI_MIN_VERSION, 'lt')
         || version_compare(GLPI_VERSION, PLUGIN_GLPI_INVENTORY_GLPI_MAX_VERSION, 'ge')) {
         if (method_exists('Plugin', 'messageIncompatible')) {
            echo Plugin::messageIncompatible('core', PLUGIN_GLPI_INVENTORY_GLPI_MIN_VERSION, PLUGIN_GLPI_INVENTORY_GLPI_MAX_VERSION);
         } else {
            // TRANS: %1$s is the minimum GLPI version inclusive, %2$s the maximum version exclusive
            echo sprintf(__('Your GLPI version not compatible, require >= %1$s and < %2$s', 'glpiinventory'),
            PLUGIN_GLPI_INVENTORY_GLPI_MIN_VERSION,
            PLUGIN_GLPI_INVENTORY_GLPI_MAX_VERSION);
         }
         return false;
      }

      if (!function_exists('finfo_open')) {
         echo __('fileinfo extension (PHP) is required...', 'glpiinventory');
         return false;
      }
   }

   $a_plugins = ['fusinvinventory', 'fusinvsnmp', 'fusinvdeploy'];
   foreach ($a_plugins as $pluginname) {
      if (file_exists(GLPI_ROOT.'/plugins/'.$pluginname)) {
         printf(__('Please remove folder %s in glpi/plugins/', 'glpiinventory'), $pluginname);
         return false;
      }
   }

   return true;
}


/**
 * Check if the config is ok
 *
 * @return boolean
 */
function plugin_glpiinventory_check_config() {
   return true;
}


/**
 * Check the rights
 *
 * @param string $type
 * @param string $right
 * @return boolean
 */
function plugin_glpiinventory_haveTypeRight($type, $right) {
   return true;
}
