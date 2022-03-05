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

       // Add icon for documentation
        $img = Html::image(
            $fi_full_path . "/pics/books.png",
            ['alt' => __('Import', 'glpiinventory')]
        );
        $options['menu']['links'][$img] = '/' . $fi_rel_path . '/front/documentation.php';

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
        * General
        */
        $general_menu = [];
        if (Session::haveRight('plugin_glpiinventory_agent', READ)) {
            $general_menu[0]['name'] = __('Agents management', 'glpiinventory');
            $general_menu[0]['pic']  = $fi_path . "/pics/menu_agents.png";
            $general_menu[0]['link'] = Agent::getSearchURL();
        }

        if (Session::haveRight('plugin_glpiinventory_group', READ)) {
            $general_menu[2]['name'] = __('Groups of computers', 'glpiinventory');
            $general_menu[2]['pic']  = $fi_path . "/pics/menu_group.png";
            $general_menu[2]['link'] = $fi_path . "/front/deploygroup.php";
        }

        if (Session::haveRight('config', UPDATE) || Session::haveRight('plugin_glpiinventory_configuration', UPDATE)) {
            $general_menu[3]['name'] = __('General configuration', 'glpiinventory');
            $general_menu[3]['pic']  = $fi_path . "/pics/menu_agents.png";
            $general_menu[3]['link'] = $fi_path . "/front/config.form.php";
        }

        if (!empty($general_menu)) {
            $menu['general'] = [
            'name'     => __('General', 'glpiinventory'),
            'children' => $general_menu,
            ];
        }

       /*
       * Tasks
       */
        $tasks_menu = [];
        if (Session::haveRight('plugin_glpiinventory_task', READ)) {
            $tasks_menu[2]['name'] = __('Task management', 'glpiinventory');
            $tasks_menu[2]['pic']  = $fi_path . "/pics/menu_task.png";
            $tasks_menu[2]['link'] = Toolbox::getItemTypeSearchURL('PluginGlpiinventoryTask');

            $tasks_menu[3]['name'] = __('Monitoring / Logs', 'glpiinventory');
            $tasks_menu[3]['pic']  = $fi_path . "/pics/menu_runningjob.png";
            $tasks_menu[3]['link'] = Toolbox::getItemTypeSearchURL('PluginGlpiinventoryTaskJob');
        }

        if (Session::haveRight('plugin_glpiinventory_importxml', CREATE)) {
            $tasks_menu[0]['name'] = __('Import agent XML file', 'glpiinventory');
            $tasks_menu[0]['pic']  = $fi_path . "/pics/menu_importxml.png";
            $tasks_menu[0]['link'] = $CFG_GLPI['root_doc'] . "/front/inventory.conf.php?forcetab=Conf\$2";
        }

        if (Session::haveRight("plugin_glpiinventory_collect", READ)) {
            $tasks_menu[11]['name'] = __('Computer information', 'glpiinventory');
            $tasks_menu[11]['pic']  = $fi_path . "/pics/menu_task.png";
            $tasks_menu[11]['link'] = Toolbox::getItemTypeSearchURL('PluginGlpiinventoryCollect');
        }

        if (Session::haveRight('plugin_glpiinventory_task', READ)) {
            $tasks_menu[12]['name'] = __('Time slot', 'glpiinventory');
            $tasks_menu[12]['pic']  = $fi_path . "/pics/menu_timeslot.png";
            $tasks_menu[12]['link'] = Toolbox::getItemTypeSearchURL('PluginGlpiinventoryTimeslot');
        }

        if (!empty($tasks_menu)) {
            $menu['tasks'] = [
            'name'     => __('Tasks', 'glpiinventory'),
            'children' => $tasks_menu,
            ];
        }

       /*
       * Rules
       */
        $rules_menu = [];
        if (Session::haveRight('plugin_glpiinventory_ruleimport', READ)) {
            $rules_menu[1]['name'] = __('Equipment import and link rules', 'glpiinventory');
            $rules_menu[1]['pic']  = $fi_path . "/pics/menu_rules.png";
            $rules_menu[1]['link'] = Toolbox::getItemTypeSearchURL(
                RuleImportAsset::class
            );
        }

        if (Session::haveRight('plugin_glpiinventory_ignoredimportdevice', READ)) {
            $rules_menu[2]['name'] = __('Asset skipped during import', 'glpiinventory');
            $rules_menu[2]['pic']  = $fi_path . "/pics/menu_rules.png";
            $rules_menu[2]['link'] = RefusedEquipment::getSearchURL();
        }

        if (Session::haveRight('plugin_glpiinventory_ruleentity', READ)) {
            $rules_menu[3]['name'] = __('Computer entity rules', 'glpiinventory');
            $rules_menu[3]['pic']  = $fi_path . "/pics/menu_rules.png";
            $rules_menu[3]['link'] = RuleImportEntity::getSearchURL();
           //$rules_menu[3]['link'] = $fi_path."/front/inventoryruleentity.php";
        }

       /*if (Session::haveRight('plugin_glpiinventory_rulelocation', READ)) {
         $rules_menu[4]['name'] = __('Location rules', 'glpiinventory');
         $rules_menu[4]['pic']  = $fi_path."/pics/menu_rules.png";
         $rules_menu[4]['link'] = $fi_path."/front/inventoryrulelocation.php";
       }*/

        if (Session::haveRight("plugin_glpiinventory_rulecollect", READ)) {
            $rules_menu[5]['name'] = __('Computer information rules', 'glpiinventory');
            $rules_menu[5]['pic']  = $fi_path . "/pics/menu_rules.png";
            $rules_menu[5]['link'] = $fi_path . "/front/collectrule.php";
        }

        if (Session::haveRight('plugin_glpiinventory_blacklist', READ)) {
            $rules_menu[6]['name'] = Blacklist::getTypeName(1);
            $rules_menu[6]['pic']  = $fi_path . "/pics/menu_blacklist.png";
            $rules_menu[6]['link'] = Blacklist::getSearchURL();
        }

        if (!empty($rules_menu)) {
            $menu['rules'] = [
            'name'     => __('Rules', 'glpiinventory'),
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
            'pic'  => $fi_path . "/pics/menu_rangeip.png",
            'link' => Toolbox::getItemTypeSearchURL('PluginGlpiinventoryIPRange')
            ];
        }

        if (Session::haveRight('plugin_glpiinventory_credentialip', READ)) {
            $network_menu[] = [
            'name' => __('Remote devices to inventory (VMware)', 'glpiinventory'),
            'pic'  => $fi_path . "/pics/menu_credentialips.png",
            'link' => Toolbox::getItemTypeSearchURL('PluginGlpiinventoryCredentialip')
            ];
        }

        if (Session::haveRight('plugin_glpiinventory_configsecurity', READ)) {
            $network_menu[] = [
            'name' => __('SNMP credentials', 'glpiinventory'),
            'pic'  => $fi_path . "/pics/menu_authentification.png",
            'link' => SNMPCredential::getSearchURL()
            ];
        }

        if (Session::haveRight('plugin_glpiinventory_credential', READ)) {
            $network_menu[] = [
            'name' => __('Authentication for remote devices (VMware)', 'glpiinventory'),
            'pic'  => $fi_path . "/pics/menu_authentification.png",
            'link' => Toolbox::getItemTypeSearchURL('PluginGlpiinventoryCredential')
            ];
        }

        if (Session::haveRight('plugin_glpiinventory_task', READ)) {
            $network_menu[] = [
            'name' => __('Discovery status', 'glpiinventory'),
            'pic'  =>   $fi_path . "/pics/menu_discovery_status.png",
            'link' =>   $fi_path . "/front/statediscovery.php"
            ];

            $network_menu[] = [
               'name' => __('Network inventory status', 'glpiinventory'),
               'pic' =>    $fi_path . "/pics/menu_inventory_status.png",
               'link' =>   $fi_path . "/front/stateinventory.php",
            ];
        }

        if (Session::haveRight('plugin_glpiinventory_model', READ)) {
            $network_menu[] = [
            'name' => __('SNMP models creation', 'glpiinventory'),
            'pic'  => $fi_path . "/pics/menu_constructmodel.png",
            'link' => $fi_path . "/front/constructmodel.php"
            ];
        }

        if (!empty($network_menu)) {
            $menu['network'] = [
            'name'     => __('Networking', 'glpiinventory'),
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
            'pic'  => $fi_path . "/pics/menu_package.png",
            'link' => $fi_path . "/front/deploypackage.php"
            ];
        }

        if (Session::haveRight('plugin_glpiinventory_deploymirror', READ)) {
            $deploy_menu[1]['name'] = __('Mirror servers', 'glpiinventory');
            $deploy_menu[1]['pic']  = $fi_path . "/pics/menu_files.png";
            $deploy_menu[1]['link'] = $fi_path . "/front/deploymirror.php";
        }

        if (Session::haveRight('plugin_glpiinventory_userinteractiontemplate', READ)) {
            $deploy_menu[2]['name'] = _n(
                'User interaction template',
                'User interaction templates',
                2,
                'glpiinventory'
            );
            $deploy_menu[2]['pic']  = $fi_path . "/pics/menu_files.png";
            $deploy_menu[2]['link'] = $fi_path . "/front/deployuserinteractiontemplate.php";
        }

        if (!empty($deploy_menu)) {
            $menu['deploy'] = [
            'name'     => __('Deploy', 'glpiinventory'),
            'children' => $deploy_menu,
            ];
        }

       /*
       * Guide
       */
        $guide_menu = [];

        $guide_menu[] = [
         'name' => __('SNMP inventory', 'glpiinventory'),
         'pic'  => "",
         'link' => $fi_path . "/front/menu_snmpinventory.php"
        ];

        if (!empty($guide_menu)) {
            $menu['guide'] = [
            'name'     => __('Guide', 'glpiinventory'),
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
    * Display the board (graph / stats on plugin page)
    *
    * @global object $DB
    */
    public static function board()
    {
        return;
       /*
       global $DB;

       // FI Computers
       $fusionComputers    = 0;
       $restrict_entity    = getEntitiesRestrictRequest(" AND", 'comp');
       $query_fi_computers = "SELECT COUNT(comp.`id`) as nb_computers
                             FROM glpi_computers comp
                             LEFT JOIN glpi_plugin_glpiinventory_inventorycomputercomputers fi_comp
                               ON fi_comp.`computers_id` = comp.`id`
                             WHERE comp.`is_deleted`  = '0'
                               AND comp.`is_template` = '0'
                               AND fi_comp.`id` IS NOT NULL
                               $restrict_entity";
       $res_fi_computers = $DB->query($query_fi_computers);
       if ($data_fi_computers = $DB->fetchAssoc($res_fi_computers)) {
         $fusionComputers = $data_fi_computers['nb_computers'];
       }

       // All Computers
       $allComputers = countElementsInTableForMyEntities('glpi_computers',
         ['is_deleted' => '0', 'is_template' => '0']);

       $dataComputer = [];
       $dataComputer[] = [
          'key' => __('GLPI Inventory computers', 'glpiinventory').' : '.$fusionComputers,
          'y'   => $fusionComputers,
          'color' => '#3dff7d'
       ];
       $dataComputer[] = [
          'key' => __('Other computers', 'glpiinventory').' : '.($allComputers - $fusionComputers),
          'y'   => ($allComputers - $fusionComputers),
          'color' => "#dedede"
       ];

       // SNMP
       $networkequipment = 0;
       $restrict_entity  = getEntitiesRestrictRequest(" AND", 'net');
       $query_fi_net = "SELECT COUNT(net.`id`) as nb_net
                             FROM glpi_networkequipments net
                             LEFT JOIN glpi_plugin_glpiinventory_networkequipments fi_net
                               ON fi_net.`networkequipments_id` = net.`id`
                             WHERE net.`is_deleted`  = '0'
                               AND net.`is_template` = '0'
                               AND fi_net.`id` IS NOT NULL
                               $restrict_entity";
       $res_fi_net = $DB->query($query_fi_net);
       if ($data_fi_net = $DB->fetchAssoc($res_fi_net)) {
         $networkequipment = $data_fi_net['nb_net'];
       }

       $printer         = 0;
       $restrict_entity = getEntitiesRestrictRequest(" AND", 'printers');
       $query_fi_printers = "SELECT COUNT(printers.`id`) as nb_printers
                             FROM glpi_printers printers
                             LEFT JOIN glpi_plugin_glpiinventory_printers fi_printer
                               ON fi_printer.`printers_id` = printers.`id`
                             WHERE printers.`is_deleted`  = '0'
                               AND printers.`is_template` = '0'
                               AND fi_printer.`id` IS NOT NULL
                               $restrict_entity";
       $res_fi_printers = $DB->query($query_fi_printers);
       if ($data_fi_printers = $DB->fetchAssoc($res_fi_printers)) {
         $printer = $data_fi_printers['nb_printers'];
       }

       $dataSNMP = [];
       $dataSNMP[] = [
          'key' => __('Network equipments', 'glpiinventory').' : '.$networkequipment,
          'y'   => $networkequipment,
          'color' => '#3d94ff'
       ];
       $dataSNMP[] = [
          'key' => __('Printers', 'glpiinventory').' : '.$printer,
          'y'   => $printer,
          'color' => '#3dff7d'
       ];

       // switches ports
       $allSwitchesPortSNMP = 0;
       $restrict_entity     = getEntitiesRestrictRequest(" AND", 'networkports');
       $query_fi_networkports = "SELECT COUNT(networkports.`id`) as nb_networkports
                             FROM glpi_networkports networkports
                             LEFT JOIN glpi_plugin_glpiinventory_networkports fi_networkports
                               ON fi_networkports.`networkports_id` = networkports.`id`
                             WHERE networkports.`is_deleted`  = '0'
                               AND fi_networkports.`id` IS NOT NULL
                               $restrict_entity";
       $res_fi_networkports = $DB->query($query_fi_networkports);
       if ($data_fi_networkports = $DB->fetchAssoc($res_fi_networkports)) {
         $allSwitchesPortSNMP = $data_fi_networkports['nb_networkports'];
       }

       $query = "SELECT networkports.`id` FROM `glpi_networkports` networkports
              LEFT JOIN `glpi_plugin_glpiinventory_networkports`
                 ON `glpi_plugin_glpiinventory_networkports`.`networkports_id` = networkports.`id`
              LEFT JOIN glpi_networkports_networkports
                  ON (`networkports_id_1`=networkports.`id`
                     OR `networkports_id_2`=networkports.`id`)
              WHERE `glpi_plugin_glpiinventory_networkports`.`id` IS NOT NULL
                  AND `glpi_networkports_networkports`.`id` IS NOT NULL
                  $restrict_entity";
       $result = $DB->query($query);
       $networkPortsLinked = $DB->numrows($result);

       $dataPortL = [];
       $dataPortL[] = [
          'key' => __('Linked with a device', 'glpiinventory').' : '.$networkPortsLinked,
          'y'   => $networkPortsLinked,
          'color' => '#3dff7d'
       ];
       $dataPortL[] = [
          'key' => __('SNMP switch network ports not linked', 'glpiinventory').' : '.($allSwitchesPortSNMP - $networkPortsLinked),
          'y'   => ($allSwitchesPortSNMP - $networkPortsLinked),
          'color' => '#dedede'
       ];

       // Ports connected at last SNMP inventory
       $networkPortsConnected = 0;
       $restrict_entity     = getEntitiesRestrictRequest(" AND", 'networkports');
       $query_fi_networkports = "SELECT COUNT(networkports.`id`) as nb_networkports
                             FROM glpi_networkports networkports
                             LEFT JOIN glpi_plugin_glpiinventory_networkports fi_networkports
                               ON fi_networkports.`networkports_id` = networkports.`id`
                             WHERE networkports.`is_deleted`  = '0'
                               AND (fi_networkports.`ifstatus`='1'
                                    OR fi_networkports.`ifstatus`='up')
                               and fi_networkports.`id` IS NOT NULL
                               $restrict_entity";
       $res_fi_networkports = $DB->query($query_fi_networkports);
       if ($data_fi_networkports = $DB->fetchAssoc($res_fi_networkports)) {
         $networkPortsConnected = $data_fi_networkports['nb_networkports'];
       }

       $dataPortC = [];
       $dataPortC[] = [
          'key' => __('Linked with a device', 'glpiinventory').' : '.$networkPortsConnected,
          'y'   => $networkPortsConnected,
          'color' => '#3dff7d'
       ];
       $dataPortC[] = [
          'key' => __('Not linked', 'glpiinventory').' : '.($allSwitchesPortSNMP - $networkPortsConnected),
          'y'   => ($allSwitchesPortSNMP - $networkPortsConnected),
          'color' => '#dedede'
       ];

       // Number of computer inventories in last hour, 6 hours, 24 hours
       $dataInventory = PluginGlpiinventoryInventoryComputerStat::getLastHours();

       // Deploy
       $restrict_entity = getEntitiesRestrictRequest(" AND", 'glpi_plugin_glpiinventory_taskjobs');
       $query = "SELECT `plugin_glpiinventory_tasks_id`
                FROM glpi_plugin_glpiinventory_taskjobs
                WHERE method LIKE '%deploy%'
                  $restrict_entity
                GROUP BY `plugin_glpiinventory_tasks_id`";
       $result = $DB->query($query);
       $a_tasks = [];
       while ($data=$DB->fetchArray($result)) {
         $a_tasks[] = $data['plugin_glpiinventory_tasks_id'];
       }
       $pfTask = new PluginGlpiinventoryTask();
       // Do not get logs with the jobs states, this to avoid long request time
       // and this is not useful on the plugin home page
       $data = $pfTask->getJoblogs($a_tasks, $with_logs = false);

       $dataDeploy = [];
       $dataDeploy[0] = [
          'key' => __('Prepared and waiting', 'glpiinventory'),
          'y'   => 0,
          'color' => '#efefef'
       ];
       $dataDeploy[1] = [
          'key' => __('Running', 'glpiinventory'),
          'y'   => 0,
          'color' => '#aaaaff'
       ];
       $dataDeploy[2] = [
          'key' => __('Successful', 'glpiinventory'),
          'y'   => 0,
          'color' => '#aaffaa'
       ];
       $dataDeploy[3] = [
          'key' => __('In error', 'glpiinventory'),
          'y'   => 0,
          'color' => '#ff0000'
       ];
       foreach ($data['tasks'] as $lev1) {
         foreach ($lev1['jobs'] as $lev2) {
            foreach ($lev2['targets'] as $lev3) {
               $dataDeploy[2]['y'] += count($lev3['counters']['agents_success']);
               $dataDeploy[3]['y'] += count($lev3['counters']['agents_error']);
               $dataDeploy[0]['y'] += count($lev3['counters']['agents_prepared']);
               $dataDeploy[1]['y'] += count($lev3['counters']['agents_running']);
            }
         }
       }
       for ($k=0; $k<4; $k++) {
         $dataDeploy[$k]['key'] .= " : ".$dataDeploy[$k]['y'];
       }

       echo "<div class='fi_board'>";
       self::showChart('computers', $dataComputer, __('Automatic inventory vs manually added', 'glpiinventory'));
       self::showChartBar('nbinventory', $dataInventory,
                         __('Computer inventories in the last hours', 'glpiinventory'));
       self::showChart('deploy', $dataDeploy, __('Deployment', 'glpiinventory'));
       self::showChart('snmp', $dataSNMP, __('Network inventory by SNMP', 'glpiinventory'));
       self::showChart('ports', $dataPortL, __('Ports on network equipments (inventoried by SNMP)', 'glpiinventory'));
       self::showChart('portsconnected', $dataPortC, __('Ports on all network equipments', 'glpiinventory'));
       echo "</div>";
       */
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
}
