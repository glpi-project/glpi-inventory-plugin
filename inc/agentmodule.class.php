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

use function Safe\preg_match;

/**
 * Manage (enable or not) the modules in the agent.
 */
class PluginGlpiinventoryAgentmodule extends CommonDBTM
{
    /**
     * The right name for this class
     * Uses the same right as Agents in native GLPI Inventory
     *
     * @var string
     */
    public static $rightname = 'agent';


    /**
     * Get the tab name used for item
     *
     * @param CommonGLPI $item the item object
     * @param int $withtemplate 1 if is a template form
     * @return string name of the tab
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item instanceof PluginGlpiinventoryConfig || $item instanceof Agent) {
            return self::createTabEntry(__('Agents modules', 'glpiinventory'), 0, icon: Agent::getIcon());
        }
        return '';
    }


    /**
     * Display the content of the tab
     *
     * @param CommonGLPI $item
     * @param int $tabnum number of the tab to display
     * @param int $withtemplate 1 if is a template form
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item instanceof PluginGlpiinventoryConfig) {
            $pfAgentmodule = new self();
            $pfAgentmodule->showModuleForm();
            return true;
        } elseif ($item instanceof Agent) {
            $pfAgentmodule = new self();
            $pfAgentmodule->showFormAgentException($item->fields['id']);
            return true;
        }
        return false;
    }

    final public function getModulesList(?int $agents_id = null): array
    {
        $modules = $this->find();
        foreach ($modules as &$module) {
            $module['id'] = strtolower($module['modulename']);
            $module['exceptions'] = importArrayFromDB($module['exceptions']);

            if (in_array($agents_id, $module['exceptions'])) {
                $module ['is_active'] = !$module ['is_active'];
            }

            $methods = PluginGlpiinventoryStaticmisc::getmethods();
            $module["displayname"] = $module["modulename"];

            foreach ($methods as $method) {
                if (
                    (strtolower($module["modulename"]) == strtolower($method['method']))
                    || isset($method['task'])
                    && (strtolower($module["modulename"]) == strtolower($method['task']))
                ) {
                    if (isset($method['name'])) {
                        $module["displayname"] = $method['name'];
                    }
                    break;
                }
            }
            // Hack for snmpquery
            if ($module["modulename"] == 'SNMPQUERY') {
                $module["displayname"] = __('Network inventory (SNMP)', 'glpiinventory');
            }
            // Hack for deploy
            if ($module["modulename"] == 'DEPLOY') {
                $module["displayname"] = __('Package deployment', 'glpiinventory');
            }
        }

        return $modules;
    }

    /**
     * Display form to configure modules in agents
     */
    public function showModuleForm(): void
    {
        TemplateRenderer::getInstance()->display('@glpiinventory/forms/agentmodule.html.twig', [
            'canedit' => true,
            'modules' => $this->getModulesList(),
            'form_url' => PluginGlpiinventoryAgentmodule::getFormURL(),
        ]);
    }


    /**
     * Display form to configure activation of modules in agent form (in tab)
     *
     * @param int $agents_id id of the agent
     */
    public function showFormAgentException(int $agents_id): void
    {
        $agent = new Agent();
        $agent->getFromDB($agents_id);
        $canedit = $agent->can($agents_id, UPDATE);

        TemplateRenderer::getInstance()->display('@glpiinventory/forms/agentmodule.html.twig', [
            'canedit' => $canedit,
            'modules' => $this->getModulesList($agents_id),
            'form_url' => PluginGlpiinventoryAgentmodule::getFormURL(),
            'agents_id' => $agents_id,
        ]);
    }


    /**
     * Get global activation status of a module
     *
     * @param string $module_name name of module
     * @return array information of module activation
     */
    public function getActivationExceptions($module_name)
    {
        $a_modules = $this->find(['modulename' => $module_name], [], 1);
        return current($a_modules);
    }


    /**
     * Get list of agents have this module activated
     *
     * @param string $module_name name of the module
     * @return array id list of agents
     */
    public function getAgentsCanDo($module_name)
    {

        $agent = new Agent();

        if ($module_name == 'SNMPINVENTORY') {
            $module_name = 'SNMPQUERY';
        }
        $agentModule = $this->getActivationExceptions($module_name);

        $where = [];
        if ($agentModule['is_active'] == 0) {
            $a_agentList = importArrayFromDB($agentModule['exceptions']);
            if (count($a_agentList) > 0) {
                $ips = [];
                $i = 0;
                foreach ($a_agentList as $agent_id) {
                    if ($i > 0) {
                        $ips[] = $agent_id;
                    }
                    $i++;
                }
                if (count($ips) > 0) {
                    $where = ['id' => $ips];
                }
                if (isset($_SESSION['glpiactiveentities_string'])) {
                    $where += getEntitiesRestrictCriteria($agent->getTable());
                }
            } else {
                return [];
            }
        } else {
            $a_agentList = importArrayFromDB($agentModule['exceptions']);
            if (count($a_agentList) > 0) {
                $ips = [];
                $i = 0;
                foreach ($a_agentList as $agent_id) {
                    if ($i > 0) {
                        $ips[] = $agent_id;
                    }
                    $i++;
                }
                if (count($ips) > 0) {
                    $where = ['id' => ['NOT' => $ips]];
                }
                if (isset($_SESSION['glpiactiveentities_string'])) {
                    $where += getEntitiesRestrictCriteria($agent->getTable());
                }
            }
        }
        $a_agents = $agent->find($where);
        return $a_agents;
    }


    /**
     * Get if agent has this module enabled
     *
     * @param string $module_name module name
     * @param int $agents_id id of the agent
     * @return bool true if enabled, otherwise false
     */
    public function isAgentCanDo($module_name, $agents_id)
    {

        switch (strtoupper($module_name)) {
            case "INVENTORYCOMPUTERESX":
                $module_active = "use_module_esx_remote_inventory";
                break;
            case "NETWORKDISCOVERY":
                $module_active = "use_module_network_discovery";
                break;
            case "NETWORKINVENTORY":
                $module_active = "use_module_network_inventory";
                break;
            case "DEPLOY":
                $module_active = "use_module_package_deployment";
                break;
            case "COLLECT":
                $module_active = "use_module_collect_data";
                break;
        }

        $a_agentModList = [];
        if (isset($module_active)) {
            $agent = new Agent();
            $a_agentModList = $agent->find(['id' => $agents_id, $module_active => 1]);
        }

        $agentModule = $this->getActivationExceptions($module_name);
        $a_agentExceptList = importArrayFromDB($agentModule['exceptions']);

        if ($agentModule['is_active'] == 0) {
            if (in_array($agents_id, $a_agentExceptList)) {
                if (isset($module_active) && count($a_agentModList) == 0) {
                    return false;
                }
                return true;
            } else {
                return false;
            }
        } else {
            if (in_array($agents_id, $a_agentExceptList)) {
                return false;
            } else {
                if (isset($module_active) && count($a_agentModList) == 0) {
                    return false;
                }
                return true;
            }
        }
    }


    /**
     * Generate the server module URL to send to agent
     *
     * @param string $modulename name of the module
     * @param int $entities_id id of the entity
     * @return string the URL generated
     */
    public static function getUrlForModule($modulename, $entities_id = -1)
    {
        $plugin_dir = '/plugins/glpiinventory';

        $entity = new Entity();
        $base_url = $entity->getUsedConfig('agent_base_url', $entities_id, 'agent_base_url', '');

        //trim the ending / if needed
        $base_url = rtrim($base_url, '/');

        if (!empty($base_url)) {
            PluginGlpiinventoryToolbox::logIfExtradebug(
                "pluginGlpiinventory-agent-url",
                "Entity " . $entities_id . ", agent base URL: " . $base_url
            );
        } else {
            // ... else use global GLPI configuration parameter.
            /** @var array $CFG_GLPI */
            global $CFG_GLPI;
            $base_url = $CFG_GLPI['url_base'];

            PluginGlpiinventoryToolbox::logIfExtradebug(
                "pluginGlpiinventory-agent-url",
                "Global configuration URL: " . $base_url
            );
        }

        // Add plugin_dir only if still not set in agent_base_url
        if (!preg_match('/(plugins|marketplace)/', $base_url)) {
            $base_url .= $plugin_dir;
        }

        // Construct the path to the JSON back from the agent_base_url.
        // agent_base_url is the initial URL used by the agent
        return $base_url . '/b/' . strtolower($modulename) . '/';
    }


    /**
     * Get list of all modules
     *
     * @return array list of name of modules
     */
    public static function getModules()
    {
        $a_modules = [];
        $a_data = getAllDataFromTable(PluginGlpiinventoryAgentmodule::getTable());
        foreach ($a_data as $data) {
            $a_modules[] = $data['modulename'];
        }
        return $a_modules;
    }

    public function updateModules(array $data): void
    {
        $modules = $this->find();
        foreach ($modules as $module_data) {
            $moduleid = strtolower($module_data['modulename']);

            $exceptions = $data[$moduleid . '_exceptions'] ?? [];
            if (empty($exceptions)) {
                $exceptions = [];
            }
            $input = [
                'id' => $module_data['id'],
                'is_active' => $data[$moduleid . '_is_active'],
                'exceptions' => exportArrayToDB($exceptions),
            ];

            $module = new PluginGlpiinventoryAgentmodule();
            $module->update($input);
        }
    }

    public function updateForAgent(array $data): void
    {
        $agents_id = $data['agents_id'];
        $modules = $this->find();
        foreach ($modules as $module_data) {
            $moduleid = strtolower($module_data['modulename']);
            $exceptions = importArrayFromDB($module_data['exceptions']);

            $is_exception = in_array($agents_id, $exceptions);

            $is_active = $module_data['is_active'];
            $post_activation = $data[$moduleid . '_is_active'];

            if ($is_active != $post_activation && !$is_exception) {
                $exceptions[] = $agents_id;
            } else {
                unset($exceptions[array_search($agents_id, $exceptions)]);
            }

            $input = [
                'id' => $module_data['id'],
                'exceptions' => exportArrayToDB($exceptions),
            ];

            $module = new PluginGlpiinventoryAgentmodule();
            $module->update($input);
        }
    }
}
