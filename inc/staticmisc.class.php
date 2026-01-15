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
 * Manage the specifications of each module and for the task configuration.
 */
class PluginGlpiinventoryStaticmisc
{
    /**
     * Get task methods of this plugin
     *
     * @return array<array{
     *     module: string,
     *     method: string,
     *     classname?: string,
     *     selection_type?: string,
     *     hidetask?: int,
     *     name: string,
     *     task?: string,
     *     use_rest?: bool
     * }>
     */
    public static function task_methods()
    {
        return [
            [   'module'         => 'glpiinventory',
                'method'         => 'inventory',
                'selection_type' => 'devices',
                'hidetask'       => 1,
                'name'           => __('Computer Inventory', 'glpiinventory'),
                'use_rest'       => false,
            ],

            [   'module'         => 'glpiinventory',
                'classname'      => PluginGlpiinventoryInventoryComputerESX::class,
                'method'         => 'InventoryComputerESX',
                'selection_type' => 'devices',
                'name'           => __('VMware host remote inventory', 'glpiinventory'),
                'task'           => 'ESX',
                'use_rest'       => true,
            ],

            [   'module'         => 'glpiinventory',
                'classname'      => PluginGlpiinventoryNetworkdiscovery::class,
                'method'         => 'networkdiscovery',
                'name'           => __('Network discovery', 'glpiinventory'),
            ],

            [   'module'         => 'glpiinventory',
                'classname'      => PluginGlpiinventoryNetworkinventory::class,
                'method'         => 'networkinventory',
                'name'           => __('Network inventory (SNMP)', 'glpiinventory'),
            ],

            [   'module'         => 'glpiinventory',
                'classname'      => PluginGlpiinventoryDeployCommon::class,
                'method'         => 'deployinstall',
                'name'           => __('Package deploy', 'glpiinventory'),
                'task'           => "DEPLOY",
                'use_rest'       => true,
            ],

            [   'module'         => 'glpiinventory',
                'classname'      => PluginGlpiinventoryCollect::class,
                'method'         => 'collect',
                'name'           => __('Collect data', 'glpiinventory'),
                'task'           => "Collect",
                'use_rest'       => true,
            ],
        ];
    }


    /**
     * Display methods available
     *
     * @return array<string,string>
     */
    public static function getModulesMethods()
    {

        $methods = PluginGlpiinventoryStaticmisc::getmethods();

        $modules_methods = [];
        $modules_methods[''] = "------";
        foreach ($methods as $method) {
            if (!((isset($method['hidetask']) && $method['hidetask'] == '1'))) {
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
     * Get all methods of this plugin
     *
     * @return array an array of the form('module'=>'value', 'method'=>'value') //@phpstan-ignore missingType.iterableValue
     */
    public static function getmethods()
    {
        $a_methods = call_user_func([PluginGlpiinventoryStaticmisc::class, 'task_methods']);
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
     * Get all devices of definition type 'PluginGlpiinventoryCredentialIp'
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownAction()
     */
    public static function task_definitionselection_PluginGlpiinventoryCredentialIp_InventoryComputerESX()
    {
        /** @var DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => 'glpi_plugin_glpiinventory_credentialips',
            'LEFT JOIN' => [
                'glpi_plugin_glpiinventory_credentials' => [
                    'ON' => [
                        'glpi_plugin_glpiinventory_credentials' => 'id',
                        'glpi_plugin_glpiinventory_credentialips' => 'plugin_glpiinventory_credentials_id',
                    ],
                ],
            ],
            'WHERE'  => [
                'glpi_plugin_glpiinventory_credentials.itemtype' => PluginGlpiinventoryInventoryComputerESX::class,
            ] + getEntitiesRestrictCriteria('glpi_plugin_glpiinventory_credentialips'),
        ]);

        $agents = [];
        //$agents['.1'] = __('All');

        foreach ($iterator as $data) {
            $agents[$data['id']] = $data['name'];
        }
        if ($agents !== []) {
            return Dropdown::showFromArray('definitionselectiontoadd', $agents);
        }

        return '';
    }


    //------------------------------------------ Actions-------------------------------------//


    /**
     * Get action types for InventoryComputerESX
     *
     * @param array<string,mixed> $a_itemtype
     * @return array<string,string>
     * @used-by PluginGlpiinventoryTaskjob::dropdownActionType()
     */
    public static function task_actiontype_InventoryComputerESX($a_itemtype)
    {
        return ['' => Dropdown::EMPTY_VALUE,
            Agent::class => Agent::getTypeName(Session::getPluralNumber()),
        ];
    }


    /**
     * Get all devices of action type 'PluginGlpiinventoryCredentialIp'
     * defined in task_actiontype_InventoryComputerESX
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownActionType()
     */
    public static function task_actionselection_PluginGlpiinventoryCredentialIp_InventoryComputerESX()
    {
        /** @var DBmysql $DB */
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
                        'glpi_plugin_glpiinventory_credentialips' => 'plugin_glpiinventory_credentials_id',
                    ],
                ],
            ],
            'WHERE'  => [
                'glpi_plugin_glpiinventory_credentials.itemtype' => PluginGlpiinventoryInventoryComputerESX::class,
            ] + getEntitiesRestrictCriteria('glpi_plugin_glpiinventory_credentialips'),
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
     * @used-by PluginGlpiinventoryTaskjob::dropdownActionType()
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
     * @param int $entities_id id of the entity
     * @return array<string,mixed>
     * @used-by PluginGlpiinventoryCommunicationRest::getConfigByAgent() via PluginGlpiinventoryCommunicationRest::getMethodForParameters()
     */
    public static function task_ESX_getParameters($entities_id)
    {
        return ['periodicity' => 3600, 'delayStartup' => 3600, 'task' => 'ESX',
            "remote" => PluginGlpiinventoryAgentmodule::getUrlForModule('ESX', $entities_id),
        ];
    }


    //------------------------------- Network tools ------------------------------------//

    // *** NETWORKDISCOVERY ***

    /**
     * Get all ip ranges of definition type 'PluginGlpiinventoryIPRange'
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownAction()
     */
    public static function task_definitionselection_PluginGlpiinventoryIPRange_networkdiscovery()
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
     * Get all ip ranges of definition type 'PluginGlpiinventoryIPRange'
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownAction()
     */
    public static function task_definitionselection_PluginGlpiinventoryIPRange_networkinventory()
    {
        $rand = PluginGlpiinventoryStaticmisc::task_definitionselection_PluginGlpiinventoryIPRange_networkdiscovery();
        return $rand;
    }


    /**
     * Get all devices of definition type 'NetworkEquipment'
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownAction()
     */
    public static function task_definitionselection_NetworkEquipment_networkinventory()
    {
        $options = [];
        $options['entity'] = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name'] = 'definitionselectiontoadd';
        $rand = Dropdown::show(NetworkEquipment::class, $options);
        return $rand;
    }


    /**
     * Get all devices of definition type 'Printer'
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownAction()
     */
    public static function task_definitionselection_Printer_networkinventory()
    {

        $options = [];
        $options['entity'] = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name'] = 'definitionselectiontoadd';
        $rand = Dropdown::show(Printer::class, $options);
        return $rand;
    }


    /**
     * Get selection type for network inventory
     *
     * @param string $itemtype
     * @return string
     * @used-by PluginGlpiinventoryTaskjobView::submitForm()
     */
    public static function task_selection_type_networkinventory($itemtype)
    {
        $selection_type = '';
        switch ($itemtype) {
            case PluginGlpiinventoryIPRange::class:
                $selection_type = 'iprange';
                break;

            case Printer::class:
            case NetworkEquipment::class:
                $selection_type = 'devices';
                break;
        }
        return $selection_type;
    }


    /**
     * Get selection type for network discovery
     *
     * @param string $itemtype
     * @return string
     * @used-by PluginGlpiinventoryTaskjobView::submitForm()
     */
    public static function task_selection_type_networkdiscovery($itemtype)
    {
        $selection_type = '';
        if ($itemtype == PluginGlpiinventoryIPRange::class) {
            $selection_type = 'iprange';
        }
        return $selection_type;
    }


    /* Deploy definitions */

    /**
     * Get all packages of definition type 'PluginGlpiinventoryDeployPackage'
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownAction()
     */
    public static function task_definitionselection_PluginGlpiinventoryDeployPackage_deployinstall()
    {
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'definitionselectiontoadd';
        return Dropdown::show(PluginGlpiinventoryDeployPackage::class, $options);
    }


    /* Deploy Actions */


    /**
     * Get types of action for deployinstall
     *
     * @param array<string,mixed> $a_itemtype
     * @return array<string,string>
     * @used-by PluginGlpiinventoryTaskjob::dropdownActionType()
     */
    public static function task_actiontype_deployinstall($a_itemtype)
    {
        return ['' => Dropdown::EMPTY_VALUE,
            Computer::class  => Computer::getTypeName(),
            PluginGlpiinventoryDeployGroup::class => PluginGlpiinventoryDeployGroup::getTypeName(),
            Group::class => Group::getTypeName(),
        ];
    }


    /**
     * Get all computers of action type 'Computer'
     * defined in task_actiontype_deployinstall
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownActionType()
     */
    public static function task_actionselection_Computer_deployinstall()
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'actionselectiontoadd';
        $options['condition']
         = implode(
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
                 ')',
             ]
         );
        return Dropdown::show(Computer::class, $options);
    }


    /**
     * Get all computers of action type 'Group'
     * defined in task_actiontype_deployinstall
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownActionType()
     */
    public static function task_actionselection_Group_deployinstall()
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'actionselectiontoadd';
        return Dropdown::show(Group::class, $options);
    }


    /**
     * Get all computers of action type 'PluginGlpiinventoryDeployGroup'
     * defined in task_actiontype_deployinstall
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownActionType()
     */
    public static function task_actionselection_PluginGlpiinventoryDeployGroup_deployinstall()
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'actionselectiontoadd';
        return Dropdown::show(PluginGlpiinventoryDeployGroup::class, $options);
    }


    /**
     * Get Deploy paramaters: url for communication with server
     *
     * @param int $entities_id
     * @return array<string,string>
     * @used-by PluginGlpiinventoryCommunicationRest::getConfigByAgent() via PluginGlpiinventoryCommunicationRest::getMethodForParameters()
     */
    public static function task_deploy_getParameters($entities_id)
    {
        return [
            "task" => "Deploy",
            "remote" => PluginGlpiinventoryAgentmodule::getUrlForModule('Deploy', $entities_id),
        ];
    }


    /* Collect */

    /**
     * Get all collects of definition type 'PluginGlpiinventoryCollect'
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownAction()
     */
    public static function task_definitionselection_PluginGlpiinventoryCollect_collect()
    {
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'definitionselectiontoadd';
        return Dropdown::show(PluginGlpiinventoryCollect::class, $options);
    }


    /**
     * Get action types for collect
     *
     * @param array<string,mixed> $a_itemtype
     * @return array<string,string>
     * @used-by PluginGlpiinventoryTaskjob::dropdownActionType()
     */
    public static function task_actiontype_collect($a_itemtype)
    {
        return ['' => Dropdown::EMPTY_VALUE,
            Computer::class => Computer::getTypeName(),
            PluginGlpiinventoryDeployGroup::class => PluginGlpiinventoryDeployGroup::getTypeName(),
            Group::class => Group::getTypeName(),
        ];
    }


    /**
     * Get all computers of action type 'Computer'
     * defined in task_actiontype_collect
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownActionType()
     */
    public static function task_actionselection_Computer_collect()
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'actionselectiontoadd';
        $options['condition']
         = implode(
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
                 ')',
             ]
         );
        return Dropdown::show("Computer", $options);
    }


    /**
     * Get all computers of action type 'Group'
     * defined in task_actiontype_collect
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownActionType()
     */
    public static function task_actionselection_Group_collect()
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'actionselectiontoadd';
        return Dropdown::show(Group::class, $options);
    }


    /**
     * Get all computers of action type 'PluginGlpiinventoryDeployGroup'
     * defined in task_actiontype_collect
     *
     * @return string unique html element id
     * @used-by PluginGlpiinventoryTaskjob::dropdownActionType()
     */
    public static function task_actionselection_PluginGlpiinventoryDeployGroup_collect()
    {
        $options = [];
        $options['entity']      = $_SESSION['glpiactive_entity'];
        $options['entity_sons'] = 1;
        $options['name']        = 'actionselectiontoadd';
        return Dropdown::show(PluginGlpiinventoryDeployGroup::class, $options);
    }


    /**
     *
     * Get collect parameters (URL to dialog with server)
     *
     * @param int $entities_id
     * @return array<string,string>
     * @used-by PluginGlpiinventoryCommunicationRest::getConfigByAgent() via PluginGlpiinventoryCommunicationRest::getMethodForParameters()
     */
    public static function task_collect_getParameters(int $entities_id): array
    {
        return [
            "task" => "Collect",
            "remote" => PluginGlpiinventoryAgentmodule::getUrlForModule('Collect', $entities_id),
        ];
    }
}
