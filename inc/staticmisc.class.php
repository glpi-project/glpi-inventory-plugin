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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Manage the specifications of each module and for the task configuration.
 */
class PluginGlpiinventoryStaticmisc
{
   /**
    * Get task methods of this plugin
    *
    * @return array('module'=>'value', 'method'=>'value')
    *   module value name of plugin
    *   method value name of method
    */
    public static function task_methods()
    {

        $a_tasks = [
            [   'module'         => 'glpiinventory',
                     'classname'      => 'PluginGlpiinventoryWakeonlan',
                     'method'         => 'wakeonlan',
                     'name'           => __('Wake On LAN', 'glpiinventory'),
                     'use_rest'       => false
            ],

            [   'module'         => 'glpiinventory',
                     'method'         => 'inventory',
                     'selection_type' => 'devices',
                     'hidetask'       => 1,
                     'name'           => __('Computer Inventory', 'glpiinventory'),
                     'use_rest'       => false
            ],

            [   'module'         => 'glpiinventory',
                     'classname'      => 'PluginGlpiinventoryInventoryComputerESX',
                     'method'         => 'InventoryComputerESX',
                     'selection_type' => 'devices',
                     'name'           => __('VMware host remote inventory', 'glpiinventory'),
                     'task'           => 'ESX',
                     'use_rest'       => true
            ],

            [   'module'         => 'glpiinventory',
                     'classname'      => 'PluginGlpiinventoryNetworkDiscovery',
                     'method'         => 'networkdiscovery',
                     'name'           => __('Network discovery', 'glpiinventory')
            ],

            [   'module'         => 'glpiinventory',
                     'classname'      => 'PluginGlpiinventoryNetworkInventory',
                     'method'         => 'networkinventory',
                     'name'           => __('Network inventory (SNMP)', 'glpiinventory')
            ],

            [   'module'         => 'glpiinventory',
                     'classname'      => 'PluginGlpiinventoryDeployCommon',
                     'method'         => 'deployinstall',
                     'name'           => __('Package deploy', 'glpiinventory'),
                     'task'           => "DEPLOY",
                     'use_rest'       => true
            ],

            [   'module'         => 'glpiinventory',
                     'classname'      => 'PluginGlpiinventoryCollect',
                     'method'         => 'collect',
                     'name'           => __('Collect data', 'glpiinventory'),
                     'task'           => "Collect",
                     'use_rest'       => true
            ]
        ];
        return $a_tasks;
    }


   /**
    * Display methods availables
    *
    * @return array
    */
    public static function getModulesMethods()
    {

        $methods = PluginGlpiinventoryStaticmisc::getmethods();

        $modules_methods = [];
        $modules_methods[''] = "------";
        foreach ($methods as $method) {
            if (!((isset($method['hidetask']) and $method['hidetask'] == '1'))) {
                if (isset($method['name'])) {
                    $modules_methods[$method['method']] = $method['name'];
                } else {
                    $modules_methods[$method['method']] = $method['method'];
                }
            }
        }
        return $modules_methods;
    }


   /**
    * Get types of datas available to select for taskjob definition for WakeOnLan method
    *
    * @param array $a_itemtype types yet added for definitions
    * @return array('itemtype'=>'value', 'itemtype'=>'value'...)
    *   itemtype itemtype of object
    *   value name of the itemtype
    */
    public static function task_definitiontype_wakeonlan($a_itemtype)
    {

        $a_itemtype['Computer'] = Computer::getTypeName();
        $a_itemtype['PluginGlpiinventoryDeployGroup']
                              = PluginGlpiinventoryDeployGroup::getTypeName();
        return $a_itemtype;
    }


   /**
    * Get all devices of definition type 'Computer' defined in
    * task_definitiontype_wakeonlan
    *
    * @param string $title (not used)
    * @return string unique html element id
    */
    public static function task_definitionselection_Computer_wakeonlan($title)
    {

        $options = [];
        $options['entity'] = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name'] = 'definitionselectiontoadd';
        $rand = Dropdown::show("Computer", $options);
        return $rand;
    }


   /**
    * Get all devices of definition type 'PluginGlpiinventoryDeployGroup'
    * defined in task_definitiontype_wakeonlan
    *
    * @param string $title (not used)
    * @return string unique html element id
    */
    public static function task_definitionselection_PluginGlpiinventoryDeployGroup_wakeonlan($title)
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'definitionselectiontoadd';
        return Dropdown::show("PluginGlpiinventoryDeployGroup", $options);
    }


   /**
    * Get all methods of this plugin
    *
    * @return array('module'=>'value', 'method'=>'value')
    *   module value name of plugin
    *   method value name of method
    *
    */
    public static function getmethods()
    {
        $a_methods = call_user_func(['PluginGlpiinventoryStaticmisc', 'task_methods']);
        $a_modules = PluginGlpiinventoryModule::getAll();
        foreach ($a_modules as $data) {
            $class = $class = PluginGlpiinventoryStaticmisc::getStaticMiscClass($data['directory']);
            if (is_callable([$class, 'task_methods'])) {
                $a_methods = array_merge(
                    $a_methods,
                    call_user_func([$class, 'task_methods'])
                );
            }
        }
        return $a_methods;
    }


   /**
    * Get name of the staticmisc class for a module
    *
    * @param string $module the module name
    * @return string the name of the staticmisc class associated with it
    */
    public static function getStaticMiscClass($module)
    {
        return "Plugin" . ucfirst($module) . "Staticmisc";
    }


   /**
    * Get types of datas available to select for taskjob definition for ESX method
    *
    * @param array $a_itemtype array types yet added for definitions
    * @return array('itemtype'=>'value', 'itemtype'=>'value'...)
    *   itemtype itemtype of object
    *   value name of the itemtype
    */
    public static function task_definitiontype_InventoryComputerESX($a_itemtype)
    {
        $a_itemtype['PluginGlpiinventoryCredentialIp'] =
                       PluginGlpiinventoryCredentialIp::getTypeName();
        return $a_itemtype;
    }


   /**
    * Get all devices of definition type 'PluginGlpiinventoryCredentialIp'
    * defined in task_definitiontype_InventoryComputerESX
    *
    * @global object $DB
    * @param string (not used)
    * @return string unique html element id
    */
    public static function task_definitionselection_PluginGlpiinventoryCredentialIp_InventoryComputerESX($title)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => 'glpi_plugin_glpiinventory_credentialips',
            'LEFT JOIN' => [
                'glpi_plugin_glpiinventory_credentials' => [
                    'ON' => [
                        'glpi_plugin_glpiinventory_credentials' => 'id',
                        'glpi_plugin_glpiinventory_credentialips' => 'plugin_glpiinventory_credentials_id'
                    ]
                ]
            ],
            'WHERE'  => [
                'glpi_plugin_glpiinventory_credentials.itemtype' => 'PluginGlpiinventoryInventoryComputerESX'
            ] + getEntitiesRestrictCriteria('glpi_plugin_glpiinventory_credentialips')
        ]);

        $agents = [];
        //$agents['.1'] = __('All');

        foreach ($iterator as $data) {
            $agents[$data['id']] = $data['name'];
        }
        if (!empty($agents)) {
            return Dropdown::showFromArray('definitionselectiontoadd', $agents);
        }
    }


   //------------------------------------------ Actions-------------------------------------//


   /**
    * Get action types for InventoryComputerESX
    *
    * @param array $a_itemtype
    * @return array
    */
    public static function task_actiontype_InventoryComputerESX($a_itemtype)
    {
        return ['' => Dropdown::EMPTY_VALUE ,
              Agent::class => Agent::getTypeName(Session::getPluralNumber())];
    }


   /**
    * Get all devices of action type 'PluginGlpiinventoryCredentialIp'
    * defined in task_actiontype_InventoryComputerESX
    *
    * @global object $DB
    * @return string unique html element id
    */
    public static function task_actionselection_PluginGlpiinventoryCredentialIp_InventoryComputerESX()
    {
        global $DB;

        $options = [];
        $options['name'] = 'definitionactiontoadd';

        $iterator = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => 'glpi_plugin_glpiinventory_credentialips',
            'LEFT JOIN' => [
                'glpi_plugin_glpiinventory_credentials' => [
                    'ON' => [
                        'glpi_plugin_glpiinventory_credentials' => 'id',
                        'glpi_plugin_glpiinventory_credentialips' => 'plugin_glpiinventory_credentials_id'
                    ]
                ]
            ],
            'WHERE'  => [
                'glpi_plugin_glpiinventory_credentials.itemtype' => 'PluginGlpiinventoryInventoryComputerESX'
            ] + getEntitiesRestrictCriteria('glpi_plugin_glpiinventory_credentialips')
        ]);

        $credentialips = [];
        foreach ($iterator as $data) {
            $credentialips[$data['id']] = $data['name'];
        }
        return Dropdown::showFromArray('actionselectiontoadd', $credentialips);
    }


   /**
    * Get all devices of action type 'Agent'
    * defined in task_actiontype_InventoryComputerESX
    *
    * @return string unique html element id
    */
    public static function task_actionselection_Agent_InventoryComputerESX()
    {

        $array = [];
        $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
        $array1 = $pfAgentmodule->getAgentsCanDo(strtoupper("InventoryComputerESX"));
        foreach ($array1 as $id => $data) {
            $array[$id] = $data['name'];
        }
        asort($array);
        return Dropdown::showFromArray('actionselectiontoadd', $array);
    }


   //------------------------------------------ ---------------------------------------------//
   //------------------------------------------ REST PARAMS---------------------------------//
   //------------------------------------------ -------------------------------------------//


   /**
    * Get ESX task parameters to send to the agent
    * For the moment it's hardcoded, but in a future release it may be in DB
    *
    * @param integer $entities_id id of the entity
    * @return array
    */
    public static function task_ESX_getParameters($entities_id)
    {
        return ['periodicity' => 3600, 'delayStartup' => 3600, 'task' => 'ESX',
                    "remote" => PluginGlpiinventoryAgentmodule::getUrlForModule('ESX', $entities_id)];
    }


   //------------------------------- Network tools ------------------------------------//

   // *** NETWORKDISCOVERY ***


   /**
    * Definition types for network discovery
    *
    * @param array $a_itemtype
    * @return array
    */
    public static function task_definitiontype_networkdiscovery($a_itemtype)
    {
        $a_itemtype['PluginGlpiinventoryIPRange'] = __('IP Ranges', 'glpiinventory');
        return $a_itemtype;
    }


   /**
    * Get all ip ranges of definition type 'PluginGlpiinventoryIPRange'
    * defined in task_definitiontype_networkdiscovery
    *
    * @param string $title (not used)
    * @return string unique html element id
    */
    public static function task_definitionselection_PluginGlpiinventoryIPRange_networkdiscovery($title)
    {
        $options = [];
        $options['entity'] = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name'] = 'definitionselectiontoadd';
        $rand = Dropdown::show("PluginGlpiinventoryIPRange", $options);
        return $rand;
    }


   // *** NETWORKINVENTORY ***


   /**
    * Definition types for network inventory
    *
    * @param array $a_itemtype
    * @return array
    */
    public static function task_definitiontype_networkinventory($a_itemtype)
    {
        $a_itemtype['PluginGlpiinventoryIPRange'] = __('IP Ranges', 'glpiinventory');

        $a_itemtype['NetworkEquipment'] = NetworkEquipment::getTypeName();
        $a_itemtype['Printer'] = Printer::getTypeName();

        return $a_itemtype;
    }


   /**
    * Get all ip ranges of definition type 'PluginGlpiinventoryIPRange'
    * defined in task_definitiontype_networkinventory
    *
    * @param string $title (not used)
    * @return string unique html element id
    */
    public static function task_definitionselection_PluginGlpiinventoryIPRange_networkinventory($title)
    {
        $rand = PluginGlpiinventoryStaticmisc::task_definitionselection_PluginGlpiinventoryIPRange_networkdiscovery($title);
        return $rand;
    }


   /**
    * Get all devices of definition type 'NetworkEquipment'
    * defined in task_definitiontype_networkinventory
    *
    * @param string $title (not used)
    * @return string unique html element id
    */
    public static function task_definitionselection_NetworkEquipment_networkinventory($title)
    {
        $options = [];
        $options['entity'] = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name'] = 'definitionselectiontoadd';
        $rand = Dropdown::show("NetworkEquipment", $options);
        return $rand;
    }


   /**
    * Get all devices of definition type 'Printer'
    * defined in task_definitiontype_networkinventory
    *
    * @param string $title (not used)
    * @return string unique html element id
    */
    public static function task_definitionselection_Printer_networkinventory($title)
    {

        $options = [];
        $options['entity'] = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name'] = 'definitionselectiontoadd';
        $rand = Dropdown::show("Printer", $options);
        return $rand;
    }


   /**
    * Get agents allowed to do network discovery
    *
    * @return array
    */
    public static function task_networkdiscovery_agents()
    {

        $array = [];
        $array["-.1"] = __('Auto managenement dynamic of agents', 'glpiinventory');

        $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
        $array1 = $pfAgentmodule->getAgentsCanDo('NETWORKDISCOVERY');
        foreach ($array1 as $id => $data) {
            $array["PluginGlpiinventoryAgent-" . $id] =
                 __('Auto managenement dynamic of agents', 'glpiinventory') . " - " . $data['name'];
        }
        return $array;
    }


   /**
    * Get types of actions for network inventory
    *
    * @return array
    */
    public static function task_action_networkinventory()
    {
        $a_itemtype = [];
        $a_itemtype[] = "Printer";
        $a_itemtype[] = "NetworkEquipment";
        $a_itemtype[] = 'PluginGlpiinventoryIPRange';

        return $a_itemtype;
    }


   /**
    * Get selection type for network inventory
    *
    * @param string $itemtype
    * @return string
    */
    public static function task_selection_type_networkinventory($itemtype)
    {
        $selection_type = '';
        switch ($itemtype) {
            case 'PluginGlpiinventoryIPRange':
                $selection_type = 'iprange';
                break;

            case "Printer":
            case "NetworkEquipment":
                $selection_type = 'devices';
                break;
        }
        return $selection_type;
    }


   /**
    * Get selection type for network discovery
    *
    * @param string $itemtype
    * @return array
    */
    public static function task_selection_type_networkdiscovery($itemtype)
    {
        $selection_type = '';
        switch ($itemtype) {
            case 'PluginGlpiinventoryIPRange':
                $selection_type = 'iprange';
                break;
        }
        return $selection_type;
    }


   /* Deploy definitions */


   /**
    * Get definition types for deploy install
    *
    * @param string $a_itemtype
    * @return array
    */
    public static function task_definitiontype_deployinstall($a_itemtype)
    {
        return ['' => Dropdown::EMPTY_VALUE,
                   'PluginGlpiinventoryDeployPackage' => __('Package')];
    }


   /**
    * Get all packages of definition type 'PluginGlpiinventoryDeployPackage'
    * defined in task_definitiontype_deployinstall
    *
    * @param string $title (not used)
    * @return string unique html element id
    */
    public static function task_definitionselection_PluginGlpiinventoryDeployPackage_deployinstall()
    {
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'definitionselectiontoadd';
        return Dropdown::show("PluginGlpiinventoryDeployPackage", $options);
    }


   /* Deploy Actions */


   /**
    * Get types of action for deployinstall
    *
    * @param array $a_itemtype
    * @return array
    */
    public static function task_actiontype_deployinstall($a_itemtype)
    {
        return ['' => Dropdown::EMPTY_VALUE,
                   'Computer'                         => __('Computers'),
                   'PluginGlpiinventoryDeployGroup' => PluginGlpiinventoryDeployGroup::getTypeName(),
                   'Group'                            => __('Group')
                  ];
    }


   /**
    * Get all computers of action type 'Computer'
    * defined in task_actiontype_deployinstall
    *
    * @return string unique html element id
    */
    public static function task_actionselection_Computer_deployinstall()
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'actionselectiontoadd';
        $options['condition']   =
         implode(
             " ",
             [
               '`id` IN ( ',
               '  SELECT agents.`items_id`',
               '  FROM `glpi_agents` as agents',
               '  LEFT JOIN `glpi_plugin_glpiinventory_agentmodules` as module',
               '  ON module.modulename = "DEPLOY"',
               '  WHERE',
               '     agents.`itemtype` = \'Computer\'',
               '     AND (',
               '           (  module.is_active=1',
               '              AND module.exceptions NOT LIKE CONCAT(\'%"\',agents.`id`,\'"%\') )',
               '        OR (  module.is_active=0',
               '              AND module.exceptions LIKE CONCAT(\'%"\',agents.`id`,\'"%\') )',
               '     )',
               ')'
              ]
         );
        return Dropdown::show("Computer", $options);
    }


   /**
    * Get all computers of action type 'Group'
    * defined in task_actiontype_deployinstall
    *
    * @return string unique html element id
    */
    public static function task_actionselection_Group_deployinstall()
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'actionselectiontoadd';
        return Dropdown::show("Group", $options);
    }


   /**
    * Get all computers of action type 'PluginGlpiinventoryDeployGroup'
    * defined in task_actiontype_deployinstall
    *
    * @return string unique html element id
    */
    public static function task_actionselection_PluginGlpiinventoryDeployGroup_deployinstall()
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'actionselectiontoadd';
        return Dropdown::show("PluginGlpiinventoryDeployGroup", $options);
    }


   /**
    * Get Deploy paramaters: url for communication with server
    *
    * @param integer $entities_id
    * @return array
    */
    public static function task_deploy_getParameters($entities_id)
    {
        return [
         "task" => "Deploy",
         "remote" => PluginGlpiinventoryAgentmodule::getUrlForModule('Deploy', $entities_id)
        ];
    }


   /* Collect */


   /**
    * Get definition types of collect
    *
    * @param array $a_itemtype
    * @return array
    */
    public static function task_definitiontype_collect($a_itemtype)
    {
        return ['' => Dropdown::EMPTY_VALUE,
                   'PluginGlpiinventoryCollect' => __('Collect information', 'glpiinventory')];
    }


   /**
    * Get all collects of definition type 'PluginGlpiinventoryCollect'
    * defined in task_definitiontype_collect
    *
    * @param string (not used)
    * @return string unique html element id
    */
    public static function task_definitionselection_PluginGlpiinventoryCollect_collect()
    {
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'definitionselectiontoadd';
        return Dropdown::show("PluginGlpiinventoryCollect", $options);
    }


   /**
    * Get action types for collect
    *
    * @param array $a_itemtype
    * @return array
    */
    public static function task_actiontype_collect($a_itemtype)
    {
        return ['' => Dropdown::EMPTY_VALUE,
                   'Computer'                         => __('Computers'),
                   'PluginGlpiinventoryDeployGroup' => PluginGlpiinventoryDeployGroup::getTypeName(),
                   'Group'                            => __('Group')
                  ];
    }


   /**
    * Get all computers of action type 'Computer'
    * defined in task_actiontype_collect
    *
    * @return string unique html element id
    */
    public static function task_actionselection_Computer_collect()
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'actionselectiontoadd';
        $options['condition']   =
         implode(
             " ",
             [
               '`id` IN ( ',
               '  SELECT agents.`items_id`',
               '  FROM `glpi_agents` as agents',
               '  LEFT JOIN `glpi_plugin_glpiinventory_agentmodules` as module',
               '  ON module.modulename = "Collect"',
               '  WHERE',
               '     agents.`itemtype` = \'Computer\'',
               '     AND (',
               '           (  module.is_active=1',
               '              AND module.exceptions NOT LIKE CONCAT(\'%"\',agents.`id`,\'"%\') )',
               '        OR (  module.is_active=0',
               '              AND module.exceptions LIKE CONCAT(\'%"\',agents.`id`,\'"%\') )',
               '     )',
               ')'
              ]
         );
        return Dropdown::show("Computer", $options);
    }


   /**
    * Get all computers of action type 'Group'
    * defined in task_actiontype_collect
    *
    * @return string unique html element id
    */
    public static function task_actionselection_Group_collect()
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'actionselectiontoadd';
        return Dropdown::show("Group", $options);
    }


   /**
    * Get all computers of action type 'PluginGlpiinventoryDeployGroup'
    * defined in task_actiontype_collect
    *
    * @return string unique html element id
    */
    public static function task_actionselection_PluginGlpiinventoryDeployGroup_collect()
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'actionselectiontoadd';
        return Dropdown::show("PluginGlpiinventoryDeployGroup", $options);
    }


   /**
    *
    * Get collect parameters (URL to dialog with server)
    *
    * @param integer $entities_id
    * @return array
    */
    public static function task_collect_getParameters($entities_id)
    {
        return [
         "task" => "Collect",
         "remote" => PluginGlpiinventoryAgentmodule::getUrlForModule('Collect', $entities_id)
        ];
    }
}
