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

use Glpi\Application\View\TemplateRenderer;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Manage plugin menu
 */
class PluginGlpiinventoryMenu extends CommonGLPI
{
   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
    public static function getTypeName($nb = 0)
    {
        return __('GLPI Inventory', 'glpiinventory');
    }


   /**
    * Check if can view item
    *
    * @return boolean
    */
    public static function canView()
    {
        $can_display = false;
        $profile = new PluginGlpiinventoryProfile();

        foreach ($profile->getAllRights() as $right) {
            if (Session::haveRight($right['field'], READ)) {
                $can_display = true;
                break;
            }
        }
        return $can_display;
    }


   /**
    * Check if can create an item
    *
    * @return boolean
    */
    public static function canCreate()
    {
        return false;
    }


   /**
    * Get the menu name
    *
    * @return string
    */
    public static function getMenuName()
    {
        return self::getTypeName();
    }


   /**
    * Get additional menu options and breadcrumb
    *
    * @global array $CFG_GLPI
    * @return array
    */
    public static function getAdditionalMenuOptions()
    {
        $fi_full_path = Plugin::getWebDir('glpiinventory');
        $fi_rel_path  = Plugin::getWebDir('glpiinventory', false);

        $elements = [
          'iprange'                    => 'PluginGlpiinventoryIPRange',
          'config'                     => 'PluginGlpiinventoryConfig',
          'task'                       => 'PluginGlpiinventoryTask',
          'timeslot'                   => 'PluginGlpiinventoryTimeslot',
          'unmanaged'                  => 'Unmanaged',
          'collectrule'                => 'PluginGlpiinventoryCollectRule',
          'configsecurity'             => 'SNMPCredential',
          'credential'                 => 'PluginGlpiinventoryCredential',
          'credentialip'               => 'PluginGlpiinventoryCredentialIp',
          'collect'                    => 'PluginGlpiinventoryCollect',
          'deploypackage'              => 'PluginGlpiinventoryDeployPackage',
          'deploymirror'               => 'PluginGlpiinventoryDeployMirror',
          'deploygroup'                => 'PluginGlpiinventoryDeployGroup',
          'deployuserinteractiontemplate' => 'PluginGlpiinventoryDeployUserinteractionTemplate',
          'ignoredimportdevice'        => 'RefusedEquipment'
        ];
        $options = [];

        $options['menu']['title'] = self::getTypeName();
        $options['menu']['page']  = self::getSearchURL(false);
        if (Session::haveRight('plugin_glpiinventory_configuration', READ)) {
            $options['menu']['links']['config']  = PluginGlpiinventoryConfig::getFormURL(false);
        }
        foreach ($elements as $type => $itemtype) {
            $options[$type] = [
              'title' => $itemtype::getTypeName(),
              'page'  => $itemtype::getSearchURL(false)];
            $options[$type]['links']['search'] = $itemtype::getSearchURL(false);
            if ($itemtype::canCreate()) {
                if ($type != 'ignoredimportdevice') {
                    $options[$type]['links']['add'] = $itemtype::getFormURL(false);
                }
            }
            if (Session::haveRight('plugin_glpiinventory_configuration', READ)) {
                $options[$type]['links']['config']  = PluginGlpiinventoryConfig::getFormURL(false);
            }
        }
       // hack for config
        $options['config']['page'] = PluginGlpiinventoryConfig::getFormURL(false);

       // Add icon for import package
        $img = Html::image(
            $fi_full_path . "/pics/menu_import.png",
            ['alt' => __('Import', 'glpiinventory')]
        );
        $options['deploypackage']['links'][$img] = '/' . $fi_rel_path . '/front/deploypackage.import.php';
       // Add icon for clean unused deploy files
        $img = Html::image(
            $fi_full_path . "/pics/menu_cleanfiles.png",
            ['alt' => __('Clean unused files', 'glpiinventory')]
        );
        $options['deploypackage']['links'][$img] = '/' . $fi_rel_path . '/front/deployfile.clean.php';

        $options['agent'] = [
           'title' => Agent::getTypeName(),
           'page'  => Agent::getSearchURL(false),
           'links' => [
               'search' => Agent::getSearchURL(false)
           ]];
        if (Session::haveRight('plugin_glpiinventory_configuration', READ)) {
            $options['agent']['links']['config']  = PluginGlpiinventoryConfig::getFormURL(false);
        }
        return $options;
    }


   /**
    * Display the menu of plugin
    *
    * @global array $CFG_GLPI
    * @param string $type
    */
    public static function displayMenu($type = "big")
    {
        global $CFG_GLPI;

        $fi_path = Plugin::getWebDir('glpiinventory');

        $menu = [];

       /*
        * Dashboard
        */
        if (Session::haveRight('dashboard', READ)) {
            $dashboard_menu = [];
            $dashboard_menu[0]['name'] = __('Inventory');
            $dashboard_menu[0]['pic']  = "ti ti-dashboard";
            $dashboard_menu[0]['link'] = $fi_path . "/front/menu.php";

            $menu['dashboard'] = [
                'name'     => __('Dashboard'),
                'pic'      => "ti ti-dashboard",
                'children' => $dashboard_menu
            ];
        }

       /*
        * General
        */
        $general_menu = [];
        if (Session::haveRight('agent', READ)) {
            $general_menu[0]['name'] = __('Agents management', 'glpiinventory');
            $general_menu[0]['pic']  = "ti ti-robot";
            $general_menu[0]['link'] = Agent::getSearchURL();
        }

        if (Session::haveRight('plugin_glpiinventory_group', READ)) {
            $general_menu[2]['name'] = __('Groups of computers', 'glpiinventory');
            $general_menu[2]['pic']  = "ti ti-devices-pc";
            $general_menu[2]['link'] = $fi_path . "/front/deploygroup.php";
        }

        if (Session::haveRight('config', UPDATE) || Session::haveRight('plugin_glpiinventory_configuration', UPDATE)) {
            $general_menu[3]['name'] = __('General configuration', 'glpiinventory');
            $general_menu[3]['pic']  = "ti ti-settings";
            $general_menu[3]['link'] = $fi_path . "/front/config.form.php";
        }

        if (!empty($general_menu)) {
            $menu['general'] = [
            'name'     => __('General', 'glpiinventory'),
            'pic'     => "ti ti-settings",
            'children' => $general_menu,
            ];
        }

       /*
       * Tasks
       */
        $tasks_menu = [];
        if (Session::haveRight('plugin_glpiinventory_task', READ)) {
            $tasks_menu[2]['name'] = __('Task management', 'glpiinventory');
            $tasks_menu[2]['pic']  = "ti ti-list-check";
            $tasks_menu[2]['link'] = Toolbox::getItemTypeSearchURL('PluginGlpiinventoryTask');

            $tasks_menu[3]['name'] = __('Monitoring / Logs', 'glpiinventory');
            $tasks_menu[3]['pic']  = "ti ti-activity";
            $tasks_menu[3]['link'] = Toolbox::getItemTypeSearchURL('PluginGlpiinventoryTaskJob');
        }

        if (Session::haveRight('config', READ)) {
            $tasks_menu[0]['name'] = __('Import agent XML file', 'glpiinventory');
            $tasks_menu[0]['pic']  = "ti ti-file-import";
            $tasks_menu[0]['link'] = $CFG_GLPI['root_doc'] . "/front/inventory.conf.php?forcetab=Glpi\Inventory\Conf$2";
        }

        if (Session::haveRight("plugin_glpiinventory_collect", READ)) {
            $tasks_menu[11]['name'] = __('Computer information', 'glpiinventory');
            $tasks_menu[11]['pic']  = "ti ti-devices-pc";
            $tasks_menu[11]['link'] = Toolbox::getItemTypeSearchURL('PluginGlpiinventoryCollect');
        }

        if (Session::haveRight('plugin_glpiinventory_task', READ)) {
            $tasks_menu[12]['name'] = __('Time slot', 'glpiinventory');
            $tasks_menu[12]['pic']  = "ti ti-calendar-time";
            $tasks_menu[12]['link'] = Toolbox::getItemTypeSearchURL('PluginGlpiinventoryTimeslot');
        }

        if (!empty($tasks_menu)) {
            $menu['tasks'] = [
            'name'     => __('Tasks', 'glpiinventory'),
            'pic'     => "ti ti-list-check",
            'children' => $tasks_menu,
            ];
        }

       /*
       * Rules
       */
        $rules_menu = [];
        if (Session::haveRight('plugin_glpiinventory_ruleimport', READ)) {
            $rules_menu[1]['name'] = __('Equipment import and link rules', 'glpiinventory');
            $rules_menu[1]['pic']  = "ti ti-book";
            $rules_menu[1]['link'] = Toolbox::getItemTypeSearchURL(
                RuleImportAsset::class
            );
        }

        if (Session::haveRight('config', READ)) {
            $rules_menu[2]['name'] = __('Asset skipped during import', 'glpiinventory');
            $rules_menu[2]['pic']  = "ti ti-device-desktop-off";
            $rules_menu[2]['link'] = RefusedEquipment::getSearchURL();
        }

        if (Session::haveRight('rule_import', READ)) {
            $rules_menu[3]['name'] = __('Computer entity rules', 'glpiinventory');
            $rules_menu[3]['pic']  = "ti ti-book";
            $rules_menu[3]['link'] = RuleImportEntity::getSearchURL();
           //$rules_menu[3]['link'] = $fi_path."/front/inventoryruleentity.php";
        }

       /*if (Session::haveRight('plugin_glpiinventory_rulelocation', READ)) {
         $rules_menu[4]['name'] = __('Location rules', 'glpiinventory');
         $rules_menu[4]['pic']  = "ti ti-map-2";
         $rules_menu[4]['link'] = $fi_path."/front/inventoryrulelocation.php";
       }*/

        if (Session::haveRight("plugin_glpiinventory_rulecollect", READ)) {
            $rules_menu[5]['name'] = __('Computer collect rules', 'glpiinventory');
            $rules_menu[5]['pic']  = "ti ti-book";
            $rules_menu[5]['link'] = $fi_path . "/front/collectrule.php";
        }

        if (Session::haveRight('config', READ)) {
            $rules_menu[6]['name'] = Blacklist::getTypeName(1);
            $rules_menu[6]['pic']  = "ti ti-ban";
            $rules_menu[6]['link'] = Blacklist::getSearchURL();
        }

        if (!empty($rules_menu)) {
            $menu['rules'] = [
            'name'     => __('Rules', 'glpiinventory'),
            'pic'     => "ti ti-book",
            'children' => $rules_menu,
            ];
        }

       /*
       * Network
       */
        $network_menu = [];

        if (Session::haveRight('plugin_glpiinventory_iprange', READ)) {
            $network_menu[] = [
            'name' => __('IP Ranges', 'glpiinventory'),
            'pic'  => "ti ti-viewfinder",
            'link' => Toolbox::getItemTypeSearchURL('PluginGlpiinventoryIPRange')
            ];
        }

        if (Session::haveRight('plugin_glpiinventory_credentialip', READ)) {
            $network_menu[] = [
            'name' => __('Remote devices to inventory (VMware)', 'glpiinventory'),
            'pic'  => "ti ti-devices-pc",
            'link' => Toolbox::getItemTypeSearchURL('PluginGlpiinventoryCredentialip')
            ];
        }

        if (Session::haveRight('plugin_glpiinventory_configsecurity', READ)) {
            $network_menu[] = [
            'name' => __('SNMP credentials', 'glpiinventory'),
            'pic'  => "ti ti-lock",
            'link' => SNMPCredential::getSearchURL()
            ];
        }

        if (Session::haveRight('plugin_glpiinventory_credential', READ)) {
            $network_menu[] = [
            'name' => __('Authentication for remote devices (VMware)', 'glpiinventory'),
            'pic'  => "ti ti-lock",
            'link' => Toolbox::getItemTypeSearchURL('PluginGlpiinventoryCredential')
            ];
        }

        if (Session::haveRight('plugin_glpiinventory_task', READ)) {
            $network_menu[] = [
            'name' => __('Discovery status', 'glpiinventory'),
            'pic'  =>   "ti ti-activity",
            'link' =>   $fi_path . "/front/statediscovery.php"
            ];

            $network_menu[] = [
               'name' => __('Network inventory status', 'glpiinventory'),
               'pic' =>    "ti ti-activity",
               'link' =>   $fi_path . "/front/stateinventory.php",
            ];
        }

        if (Session::haveRight('plugin_glpiinventory_model', READ)) {
            $network_menu[] = [
            'name' => __('SNMP models creation', 'glpiinventory'),
            'pic'  => "ti ti-model",
            'link' => $fi_path . "/front/constructmodel.php"
            ];
        }

        if (!empty($network_menu)) {
            $menu['network'] = [
            'name'     => __('Networking', 'glpiinventory'),
            'pic'     => "ti ti-network",
            'children' => $network_menu,
            ];
        }

       /*
       * Deploy
       */
        $deploy_menu = [];

        if (Session::haveRight('plugin_glpiinventory_package', READ)) {
            $deploy_menu[] = [
            'name' => __('Package management', 'glpiinventory'),
            'pic'  => "ti ti-package",
            'link' => $fi_path . "/front/deploypackage.php"
            ];
        }

        if (Session::haveRight('plugin_glpiinventory_deploymirror', READ)) {
            $deploy_menu[1]['name'] = __('Mirror servers', 'glpiinventory');
            $deploy_menu[1]['pic']  = "ti ti-server-2";
            $deploy_menu[1]['link'] = $fi_path . "/front/deploymirror.php";
        }

        if (Session::haveRight('plugin_glpiinventory_userinteractiontemplate', READ)) {
            $deploy_menu[2]['name'] = _n(
                'User interaction template',
                'User interaction templates',
                2,
                'glpiinventory'
            );
            $deploy_menu[2]['pic']  = "ti ti-template";
            $deploy_menu[2]['link'] = $fi_path . "/front/deployuserinteractiontemplate.php";
        }

        if (!empty($deploy_menu)) {
            $menu['deploy'] = [
            'name'     => __('Deploy', 'glpiinventory'),
            'pic'     => "ti ti-share",
            'children' => $deploy_menu,
            ];
        }

       /*
       * Guide
       */
        $guide_menu = [];

        $guide_menu[] = [
         'name' => __('SNMP inventory', 'glpiinventory'),
         'pic'  => "ti ti-book",
         'link' => $fi_path . "/front/menu_snmpinventory.php"
        ];

        if (!empty($guide_menu)) {
            $menu['guide'] = [
            'name'     => __('Guide', 'glpiinventory'),
            'pic'      => "ti ti-book-2",
            'children' => $guide_menu,
            ];
        }

        TemplateRenderer::getInstance()->display('@glpiinventory/submenu.html.twig', [
         'menu' => $menu,
        ]);
    }


   /**
    * Menu for SNMP inventory
    *
    * @global array $CFG_GLPI
    */
    public static function displayMenuSNMPInventory()
    {
        $fi_path = Plugin::getWebDir('glpiinventory');

        echo "<table class='tab_cadre_fixe'>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2'>";
        echo __('This is the steps to configure plugin for SNMP inventory (swicth, router, network printer)', 'glpiinventory');
        echo "</th>";
        echo "</tr>";

        $a_steps = [
          [
              'text' => __('Configure SNMP credentials', 'glpiinventory'),
              'url'  => SNMPCredential::getFormURL(),
          ],
          [
              'text' => __('Define rules for import : merge and create new devices (CAUTION: same rules for computer inventory)', 'glpiinventory'),
              'url'  => RuleImportAsset::getFormURL(),
          ],
          [
              'text' => __('`Network Discovery`, used to discover the devices on the network', 'glpiinventory'),
              'url'  => "",
              'title' => true
          ],
          [
              'text' => __('Define IP Ranges of your network + related SNMP credentials', 'glpiinventory'),
              'url'  => $fi_path . "/front/iprange.php"
          ],
          [
              'text' => __('Define an agent allowed to discover the network', 'glpiinventory'),
              'url'  => $fi_path . "/front/config.form.php?forcetab=PluginGlpiinventoryAgentmodule$1"
          ],
          [
              'text' => __('Create a new Task with discovery module and the agent defined previously', 'glpiinventory'),
              'url'  => $fi_path . "/front/task.php"
          ],
          [
              'text' => __('If you have devices not typed, import them from unmanaged devices', 'glpiinventory'),
              'url'  => Unmanaged::getSearchURL()
          ],
          [
              'text' => __('`Network Inventory`, used to complete inventory the discovered devices', 'glpiinventory'),
              'url'  => "",
              'title' => true
          ],
          [
              'text' => __('Define an agent allowed to inventory the network by SNMP', 'glpiinventory'),
              'url'  => $fi_path . "/front/config.form.php?forcetab=PluginGlpiinventoryAgentmodule$1"
          ],
          [
              'text' => __('Create a new Task with network inventory module and the agent defined previously', 'glpiinventory'),
              'url'  => $fi_path . "/front/task.php"
          ],
        ];

        $i = 1;
        foreach ($a_steps as $data) {
            echo "<tr class='tab_bg_1'>";
            if (
                isset($data['title'])
                 && $data['title']
            ) {
                echo "<th colspan='2'>";
                echo $data['text'];
                echo "</th>";
            } else {
                echo "<th width='25'>";
                echo $i . ".";
                echo "</th>";
                echo "<td>";
                if ($data['url'] == '') {
                    echo $data['text'];
                } else {
                    echo '<a href="' . $data['url'] . '" target="_blank">' . $data['text'] . '</a>';
                }
                echo "</td>";
                $i++;
            }
            echo "</tr>";
        }
        echo "</table>";
    }


   /**
    * Display chart
    *
    * @param string $name
    * @param array $data list of data for the chart
    * @param string $title
    */
    public static function showChart($name, $data, $title = '&nbsp;')
    {
        echo "<div class='fi_chart donut'>";
        echo "<h2 class='fi_chart_title'>$title</h2>";
        echo '<svg id="' . $name . '"></svg>';
        echo Html::scriptBlock("$(function() {
         statHalfDonut('" . $name . "', '" . json_encode($data) . "');
      });");
        echo "</div>";
    }


   /**
    * Display chart bar
    *
    * @param string $name
    * @param array $data list of data for the chart
    * @param string $title
    * @param integer $width
    */
    public static function showChartBar($name, $data, $title = '', $width = 370)
    {
        echo "<div class='fi_chart bar'>";
        echo "<h2 class='fi_chart_title'>$title</h2>";
        echo '<svg id="' . $name . '"></svg>';
        echo Html::scriptBlock("$(function() {
         statBar('" . $name . "', '" . json_encode($data) . "');
      });");
        echo "</div>";
    }

    public static function getIcon()
    {
        return "ti ti-settings";
    }
}
