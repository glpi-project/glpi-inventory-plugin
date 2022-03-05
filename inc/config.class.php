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
 * Manage the configuration of the plugin.
 */
class PluginGlpiinventoryConfig extends CommonDBTM
{
   /**
    * Initialize the displaylist public variable
    *
    * @var boolean
    */
    public $displaylist = false;

   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = 'plugin_glpiinventory_configuration';

   /**
    * Define number to the action 'clean' of agents
    *
    * @var integer
    */
    const ACTION_CLEAN = 0;

   /**
    * Define number to the action 'change status' of agents
    *
    * @var integer
    */
    const ACTION_STATUS = 1;


   /**
    * Initialize config values of  plugin
    *
    * @param boolean $getOnly
    * @return array
    */
    public function initConfigModule($getOnly = false)
    {

        $pfSetup  = new PluginGlpiinventorySetup();
        $users_id = $pfSetup->createGlpiInventoryUser();
        $input    = [];

        $input['version']                = PLUGIN_GLPIINVENTORY_VERSION;
        $input['ssl_only']               = '0';
        $input['delete_task']            = '20';
        $input['inventory_frequence']    = '24';
        $input['agent_port']             = '62354';
        $input['extradebug']             = '0';
        $input['users_id']               = $users_id;
        $input['agents_old_days']        = '0';
        $input['agents_action']          = 0;
        $input['agents_status']          = 0;
        $input['wakeup_agent_max']       = '10';

        $input['import_software']        = 1;
        $input['import_volume']          = 1;
        $input['import_antivirus']       = 1;
        $input['import_registry']        = 1;
        $input['import_process']         = 1;
        $input['import_vm']              = 1;
        $input['import_monitor_on_partial_sn'] = 0;
        $input['component_processor']    = 1;
        $input['component_memory']       = 1;
        $input['component_harddrive']    = 1;
        $input['component_networkcard']  = 1;
        $input['component_graphiccard']  = 1;
        $input['component_soundcard']    = 1;
        $input['component_drive']        = 1;
        $input['component_networkdrive'] = 1;
        $input['component_control']      = 1;
        $input['component_removablemedia'] = 0;
        $input['component_simcard']      = 1;
        $input['component_powersupply']  = 1;
        $input['states_id_default']      = 0;
        $input['states_id_snmp_default'] = 0;
        $input['location']               = 0;
        $input['group']                  = 0;
        $input['create_vm']              = 0;
        $input['component_networkcardvirtual'] = 1;
        $input['otherserial']            = 0;
        $input['component_battery']      = 1;

        $input['threads_networkdiscovery'] = 20;
        $input['threads_networkinventory'] = 10;
        $input['timeout_networkdiscovery'] = 1;
        $input['timeout_networkinventory'] = 15;

       //deploy config variables
        $input['server_upload_path'] =
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
        $input['alert_winpath']         = 1;
        $input['server_as_mirror']      = 1;
        $input['manage_osname']         = 1;
        $input['clean_on_demand_tasks'] = -1;

        $input['reprepare_job']         = 0;

        if (!$getOnly) {
            $this->addValues($input);
        }
        return $input;
    }


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
    public static function getTypeName($nb = 0)
    {

        return __('General setup');
    }


   /**
    * Add multiple configuration values
    *
    * @param array $values configuration values, indexed by name
    * @param boolean $update say if add or update in database
    */
    public function addValues($values, $update = true)
    {

        foreach ($values as $type => $value) {
            if ($this->getValue($type) === null) {
                $this->addValue($type, $value);
            } elseif ($update == true) {
                $this->updateValue($type, $value);
            }
        }
    }


   /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
    public function defineTabs($options = [])
    {

        $plugin = new Plugin();

        $ong        = [];
        $moduleTabs = [];
        $this->addStandardTab("PluginGlpiinventoryConfig", $ong, $options);
        $this->addStandardTab("PluginGlpiinventoryAgentmodule", $ong, $options);

        if (isset($_SESSION['glpi_plugin_glpiinventory']['configuration']['moduletabforms'])) {
            $plugin_tabs = $ong;
            $moduleTabForms =
                  $_SESSION['glpi_plugin_glpiinventory']['configuration']['moduletabforms'];
            if (count($moduleTabForms)) {
                foreach ($moduleTabForms as $module => $form) {
                    if ($plugin->isActivated($module)) {
                        $this->addStandardTab($form[key($form)]['class'], $ong, $options);
                    }
                }
                $moduleTabs = array_diff($ong, $plugin_tabs);
            }
            $_SESSION['glpi_plugin_glpiinventory']['configuration']['moduletabs'] = $moduleTabs;
        }
        return $ong;
    }


   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string|array name of the tab
    */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
            return [
             __('General setup'),
             __('Network Inventory', 'glpiinventory'),
             __('Package management', 'glpiinventory')
            ];
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
    public static function displayTabContentForItem($item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($tabnum) {
            case 0:
                $item->showConfigForm();
                return true;

            case 1:
                $item->showFormNetworkInventory();
                return true;

            case 2:
                $item->showFormDeploy();
                return true;
        }
        return false;
    }


   /**
    * Get configuration value with name
    *
    * @global array $PF_CONFIG
    * @param string $name name in configuration
    * @return null|string|integer
    */
    public function getValue($name)
    {
        global $PF_CONFIG;

        if (isset($PF_CONFIG[$name])) {
            return $PF_CONFIG[$name];
        }

        $config = current($this->find(['type' => $name]));
        if (isset($config['value'])) {
            return $config['value'];
        }
        return null;
    }


   /**
    * Give state of a config field for plugin
    *
    * @param string $name name in configuration
    * @return boolean
    */
    public function isFieldActive($name)
    {
        if (!($this->getValue($name))) {
            return false;
        } else {
            return true;
        }
    }


   /**
    * Display form
    *
    * @param array $options
    * @return true
    */
    public function showConfigForm($options = [])
    {

        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('SSL-only for agent', 'glpiinventory') . "</td>";
        echo "<td width='20%'>";
        Dropdown::showYesNo("ssl_only", $this->isFieldActive('ssl_only'));
        echo "</td>";
        echo "<td>" . __('Inventory frequency (in hours)', 'glpiinventory') . "</td>";
        echo "<td width='20%'>";
        Dropdown::showNumber(
            "inventory_frequence",
            [
                            'value' => $this->getValue('inventory_frequence'),
                            'min' => 1,
                            'max' => 240
                           ]
        );
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Delete tasks logs after', 'glpiinventory') . "</td>";
        echo "<td>";
        Dropdown::showNumber(
            "delete_task",
            [
                            'value' => $this->getValue('delete_task'),
                            'min'   => 1,
                            'max'   => 240,
                            'unit'  => 'day'
                           ]
        );
        echo "</td>";

        echo "<td>" . __('Agent port', 'glpiinventory') . "</td>";
        echo "<td>";
        echo "<input type='text' class='form-control' name='agent_port' value='" . $this->getValue('agent_port') . "'/>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Extra-debug', 'glpiinventory') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("extradebug", $this->isFieldActive('extradebug'));
        echo "</td>";

        echo "<td>" . __('Re-prepare successful jobs', 'glpiinventory') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("reprepare_job", $this->isFieldActive('reprepare_job'));
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan =2></td>";
        echo "<td>" . __('Maximum number of agents to wake up in a task', 'glpiinventory') . "</td>";
        echo "<td width='20%'>";
        Dropdown::showNumber(
            "wakeup_agent_max",
            [
                            'value' => $this->getValue('wakeup_agent_max'),
                            'min' => 1,
                            'max' => 100
                           ]
        );
        echo "</td>";

        echo "</tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<th colspan=4 >" . __('Update agents', 'glpiinventory') . "</th></tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Update agents not have contacted server since (in days)', 'glpiinventory') . "</td>";
        echo "<td width='20%'>";
        Dropdown::showNumber("agents_old_days", [
         'value' => $this->getValue('agents_old_days'),
         'min'   => 1,
         'max'   => 1000,
         'toadd' => ['0' => __('Disabled')]]);
        echo "</td>";
        echo "<td>" . __('Action') . "</td>";
        echo "<td width='20%'>";
       //action
        $rand = Dropdown::showFromArray(
            'agents_action',
            [self::getActions(self::ACTION_CLEAN), self::getActions(self::ACTION_STATUS)],
            ['value' => $this->getValue('agents_action'), 'on_change' => 'changestatus();']
        );
       //if action == action_status => show blocation else hide blocaction
        echo Html::scriptBlock("
         function changestatus() {
            if ($('#dropdown_agents_action$rand').val() != 0) {
               $('#blocaction1').show();
               $('#blocaction2').show();
            } else {
               $('#blocaction1').hide();
               $('#blocaction2').hide();
            }
         }
         changestatus();

      ");
        echo "</td>";
        echo "</tr>";
       //blocaction with status
        echo "<tr class='tab_bg_1'><td colspan=2></td>";
        echo "<td>";
        echo "<span id='blocaction1' style='display:none'>";
        echo __('Change the status', 'glpiinventory');
        echo "</span>";
        echo "</td>";
        echo "<td width='20%'>";
        echo "<span id='blocaction2' style='display:none'>";
        State::dropdown(['name'   => 'agents_status',
         'value'  => $this->getValue('agents_status'),
         'entity' => $_SESSION['glpiactive_entity']]);
        echo "</span>";
        echo "</td>";
        echo "</tr>";

        $options['candel'] = false;
        $this->showFormButtons($options);

        return true;
    }


   /**
    * Get the action for agent action
    *
    * @param integer $action
    * @return string
    */
    public static function getActions($action)
    {
        switch ($action) {
            case self::ACTION_STATUS:
                return __('Change the status', 'glpiinventory');

            case self::ACTION_CLEAN:
                return __('Clean agents', 'glpiinventory');
        }
    }


   /**
    * Display form for tab 'Network inventory'
    *
    * @param array $options
    * @return true
    */
    public static function showFormNetworkInventory($options = [])
    {
        global $CFG_GLPI;

        $pfConfig     = new PluginGlpiinventoryConfig();
        $pfsnmpConfig = new self();

        $pfsnmpConfig->fields['id'] = 1;
        $pfsnmpConfig->showFormHeader($options);

        echo "<tr>";
        echo "<th colspan='4'>";
        echo __('Network options', 'glpiinventory');
        echo "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Default status', 'glpiinventory') . "</td>";
        echo "<td>";
        Dropdown::show(
            'State',
            ['name'   => 'states_id_snmp_default',
            'value'  => $pfConfig->getValue('states_id_snmp_default')]
        );
        echo "</td><td colspan='2'></td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Threads number', 'glpiinventory') . "&nbsp;" .
              "(" . strtolower(__('Network discovery', 'glpiinventory')) . ")</td>";
        echo "<td align='center'>";
        Dropdown::showNumber("threads_networkdiscovery", [
             'value' => $pfConfig->getValue('threads_networkdiscovery'),
             'min'   => 1,
             'max'   => 400]);
        echo "</td>";

        echo "<td>" . __('Threads number', 'glpiinventory') . "&nbsp;" .
              "(" . strtolower(__('Network inventory (SNMP)', 'glpiinventory')) . ")</td>";
        echo "<td align='center'>";
        Dropdown::showNumber("threads_networkinventory", [
             'value' => $pfConfig->getValue('threads_networkinventory'),
             'min'   => 1,
             'max'   => 400]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('SNMP timeout', 'glpiinventory') . "&nbsp;" .
              "(" . strtolower(__('Network discovery', 'glpiinventory')) . ")</td>";
        echo "<td align='center'>";
        Dropdown::showNumber("timeout_networkdiscovery", [
             'value' => $pfConfig->getValue('timeout_networkdiscovery'),
             'min'   => 1,
             'max'   => 60]);
        echo "</td>";
        echo "<td>" . __('SNMP timeout', 'glpiinventory') . "&nbsp;" .
              "(" . strtolower(__('Network inventory (SNMP)', 'glpiinventory')) . ")</td>";
        echo "<td align='center'>";
        Dropdown::showNumber("timeout_networkinventory", [
             'value' => $pfConfig->getValue('timeout_networkinventory'),
             'min'   => 1,
             'max'   => 60]);
        echo "</td>";
        echo "</tr>";

        $options['candel'] = false;
        $pfsnmpConfig->showFormButtons($options);

        return true;
    }


   /**
    * Display form for tab 'Deploy'
    *
    * @param array $options
    * @return true
    */
    public static function showFormDeploy($options = [])
    {

        $pfConfig = new PluginGlpiinventoryConfig();
        $pfConfig->fields['id'] = 1;
        $options['colspan'] = 1;
        $pfConfig->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Root folder for sending files from server', 'glpiinventory') . "</td>";
        echo "<td>";
        echo "<input type='text' class='form-control' name='server_upload_path' value='" .
         $pfConfig->getValue('server_upload_path') . "' size='60' />";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>" . __('Use this GLPI server as a mirror server', 'glpiinventory') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("server_as_mirror", $pfConfig->getValue('server_as_mirror'));
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>" . __('Match mirrors to agents', 'glpiinventory') . "</td>";
        echo "<td>";
        $mirror_options = [
         PluginGlpiinventoryDeployMirror::MATCH_LOCATION => __('with location', 'glpiinventory'),
         PluginGlpiinventoryDeployMirror::MATCH_ENTITY   => __('with entity', 'glpiinventory'),
         PluginGlpiinventoryDeployMirror::MATCH_BOTH     => __('with both', 'glpiinventory')
        ];
        Dropdown::showFromArray(
            'mirror_match',
            $mirror_options,
            ['value' => $pfConfig->getValue('mirror_match')]
        );
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>" . __('Delete successful on demand tasks after (in days)', 'glpiinventory') . "</td>";
        echo "<td width='20%'>";
        $toadd = [-1 => __('Never')];
        Dropdown::showNumber("clean_on_demand_tasks", [
         'value' => $pfConfig->getValue('clean_on_demand_tasks'),
         'min'   => 1,
         'max'   => 1000,
         'toadd' => $toadd]);
        echo "</td>";
        echo "</tr>";

        $options['candel'] = false;
        $pfConfig->showFormButtons($options);

        return true;
    }


   /**
    * Add name + value in configuration if not exist
    *
    * @param string $name
    * @param string $value
    * @return integer|false integer is the id of this configuration name
    */
    public function addValue($name, $value)
    {
        $existing_value = $this->getValue($name);
        if (!is_null($existing_value)) {
            return $existing_value;
        } else {
            return $this->add(['type'  => $name,
                                 'value' => $value]);
        }
    }


   /**
    * Update configuration value
    *
    * @param string $name name of configuration
    * @param string $value
    * @return boolean
    */
    public function updateValue($name, $value)
    {
        global $PF_CONFIG;

       // retrieve current config
        $config = current($this->find(['type' => $name]));

       // set in db
        if (isset($config['id'])) {
            $result = $this->update(['id' => $config['id'], 'value' => $value]);
        } else {
            $result = $this->add(['type' => $name, 'value' => $value]);
        }

       // set cache
        if ($result) {
            $PF_CONFIG[$name] = $value;
        }

        return $result;
    }


   /**
    * Check if extradebug mode is activate
    *
    * @return null|integer the integer is 1 or 0 (it's like boolean)
    */
    public static function isExtradebugActive()
    {
        $fConfig = new self();
        return $fConfig->getValue('extradebug');
    }


   /**
    * Log when extra-debug is activated
    *
    * @param string $file name of log file to update
    * @param string $message the message to put in log file
    */
    public static function logIfExtradebug($file, $message)
    {
        if (self::isExtradebugActive()) {
            if (is_array($message)) {
                $message = print_r($message, true);
            }
            Toolbox::logInFile($file, $message);
        }
    }


   /**
    * Load all configuration in global variable $PF_CONFIG
    *
    * Test if table exists before loading cache
    * The only case where table doesn't exists is when you click on
    * uninstall the plugin and it's already uninstalled
    *
    * @global object $DB
    * @global array $PF_CONFIG
    */
    public static function loadCache()
    {
        global $DB, $PF_CONFIG;

        if ($DB->tableExists('glpi_plugin_glpiinventory_configs')) {
            $PF_CONFIG = [];
            foreach ($DB->request('glpi_plugin_glpiinventory_configs') as $data) {
                $PF_CONFIG[$data['type']] = $data['value'];
            }
        }
    }
}
