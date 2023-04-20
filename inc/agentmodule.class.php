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
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string name of the tab
    */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == 'PluginGlpiinventoryConfig') {
            return __('Agents modules', 'glpiinventory');
        } elseif ($item->getType() == 'Agent') {
            return __('Agents modules', 'glpiinventory');
        }
        return '';
    }


   /**
    * Display the content of the tab
    *
    * @param object $item
    * @param integer $tabnum number of the tab to display
    * @param integer $withtemplate 1 if is a template form
    * @return boolean
    */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'PluginGlpiinventoryConfig') {
            $pfAgentmodule = new self();
            $pfAgentmodule->showModuleForm();
            return true;
        } elseif ($item->getType() == 'Agent') {
            $pfAgentmodule = new self();
            $pfAgentmodule->showFormAgentException($item->fields['id']);
            return true;
        }
        return false;
    }


   /**
    * Display form to configure modules in agents
    *
    * @return boolean true if no problem
    */
    public function showModuleForm()
    {

        $agent = new Agent();

        $a_modules = $this->find();
        foreach ($a_modules as $data) {
            echo "<form name='form_ic' method='post' action='" .
                 Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th width='130'>" . __('Module', 'glpiinventory') . "</th>";
            echo "<th width='180'>" . __('Activation (by default)', 'glpiinventory') . "</th>";
            echo "<th>" . __('Exceptions', 'glpiinventory') . "</th>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            $a_methods = PluginGlpiinventoryStaticmisc::getmethods();
            $modulename = $data["modulename"];

            foreach ($a_methods as $datamod) {
                if (
                    (strtolower($data["modulename"]) == strtolower($datamod['method'])) ||
                    isset($datamod['task']) &&
                    (strtolower($data["modulename"]) == strtolower($datamod['task']))
                ) {
                    if (isset($datamod['name'])) {
                        $modulename = $datamod['name'];
                    }
                    break;
                }
            }
           // Hack for snmpquery
            if ($data["modulename"] == 'SNMPQUERY') {
                $modulename = __('Network inventory (SNMP)', 'glpiinventory');
            }
           // Hack for deploy
            if ($data["modulename"] == 'DEPLOY') {
                $modulename = __('Package deployment', 'glpiinventory');
            }

            echo "<td align='center'><strong>" . $modulename . "</strong></td>";
            echo "<td align='center'>";
            $checked = $data['is_active'];

            Html::showCheckbox(['name'    => 'activation',
                                  'value'   => '1',
                                  'checked' => $checked]);
            echo "</td>";
            echo "<td>";
             echo "<table>";
             echo "<tr>";
             echo "<td width='45%'>";
             $a_agentList = importArrayFromDB($data['exceptions']);
             $a_used = [];
            foreach ($a_agentList as $agent_id) {
                 $a_used[] = $agent_id;
            }
            Dropdown::show("Agent", ["name" => "agent_to_add[]",
                                                               "used" => $a_used]);
             echo "</td>";
             echo "<td align='center'>";
             echo "<input type='submit' class='btn btn-secondary' name='agent_add' value='" .
               __s('Add') . " >>'>";
             echo "<br><br>";
             echo "<input type='submit' class='btn btn-secondary' name='agent_delete' value='<< " .
               __s('Delete') . "'>";
             echo "</td>";
             echo "<td width='45%'>";

             echo "<select class='form-select' size='6' name='agent_to_delete[]'>";
            foreach ($a_agentList as $agent_id) {
                 $agent->getFromDB($agent_id);
                 echo "<option value='" . $agent_id . "'>" . $agent->getName() . "</option>";
            }
            echo "</select>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            echo "</td>";

            echo "<tr>";
            echo "<td class='tab_bg_2 center' colspan='3'>";
            echo "<input type='submit' name='update' value=\"" . __s('Update') . "\" class='btn btn-primary'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            echo Html::hidden('id', ['value' => $data['id']]);
            Html::closeForm();
            echo "<br/>";
        }
        return true;
    }


   /**
    * Display form to configure activation of modules in agent form (in tab)
    *
    * @global array $CFG_GLPI
    * @param integer $agents_id id of the agent
    */
    public function showFormAgentException($agents_id)
    {
        $agent = new Agent();
        $agent->getFromDB($agents_id);
        $canedit = $agent->can($agents_id, UPDATE);

        echo "<br/>";
        if ($canedit) {
            echo "<form name='form_ic' method='post' action='" . Plugin::getWebDir('glpiinventory') .
               "/front/agentmodule.form.php'>";
        }
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr>";
        echo "<th>" . __('Module', 'glpiinventory') . "</th>";
        echo "<th>Activation</th>";
        echo "<th>" . __('Module', 'glpiinventory') . "</th>";
        echo "<th>Activation</th>";
        echo "</tr>";

        $a_modules = $this->find();
        $i = 0;
        $a_methods = PluginGlpiinventoryStaticmisc::getmethods();
        foreach ($a_modules as $data) {
            if ($i == 0) {
                echo "<tr class='tab_bg_1'>";
            }
            $modulename = $data["modulename"];
            foreach ($a_methods as $datamod) {
                if (
                    (strtolower($data["modulename"]) == strtolower($datamod['method'])) ||
                    isset($datamod['task']) &&
                    (strtolower($data["modulename"]) == strtolower($datamod['task']))
                ) {
                    if (isset($datamod['name'])) {
                        $modulename = $datamod['name'];
                    }
                    break;
                }
            }
           // Hack for snmpquery
            if ($data["modulename"] == 'SNMPQUERY') {
                $modulename = __('Network inventory (SNMP)', 'glpiinventory');
            }
           // Hack for deploy
            if ($data["modulename"] == 'DEPLOY') {
                $modulename = __('Package deployment', 'glpiinventory');
            }

            echo "<td width='50%'>" . $modulename . " :</td>";
            echo "<td align='center'>";

            $checked = $data['is_active'];
            $a_agentList = importArrayFromDB($data['exceptions']);
            if (in_array($agents_id, $a_agentList)) {
                if ($checked == 1) {
                    $checked = 0;
                } else {
                    $checked = 1;
                }
            }
            Html::showCheckbox(['name'    => "activation-" . $data["modulename"],
                                  'value'   => '1',
                                  'checked' => $checked]);
            echo "</td>";
            if ($i == 1) {
                echo "</tr>";
                $i = -1;
            }
            $i++;
        }
        if ($i == 1) {
            echo "<td></td>";
            echo "<td></td>";
            echo "</tr>";
        }
        if ($canedit) {
            echo "<tr>";
            echo "<td class='tab_bg_2 card-body mx-n2 mb-4  border-top' colspan='4'>";
            echo Html::hidden('id', ['value' => $agents_id]);
            echo "<input type='submit' name='updateexceptions' " .
                 "value=\"" . __('Update') . "\" class='btn btn-primary'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            Html::closeForm();
        } else {
            echo "</table>";
        }
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
    * @param integer $agents_id id of the agent
    * @return boolean true if enabled, otherwise false
    */
    public function isAgentCanDo($module_name, $agents_id)
    {

        $agentModule = $this->getActivationExceptions($module_name);

        if ($agentModule['is_active'] == 0) {
            $a_agentList = importArrayFromDB($agentModule['exceptions']);
            if (in_array($agents_id, $a_agentList)) {
                return true;
            } else {
                return false;
            }
        } else {
            $a_agentList = importArrayFromDB($agentModule['exceptions']);
            if (in_array($agents_id, $a_agentList)) {
                return false;
            } else {
                return true;
            }
        }
    }


   /**
    * Generate the server module URL to send to agent
    *
    * @param string $modulename name of the module
    * @param integer $entities_id id of the entity
    * @return string the URL generated
    */
    public static function getUrlForModule($modulename, $entities_id = -1)
    {
        $plugin_dir = '/' . Plugin::getWebDir('glpiinventory', false);

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
}
