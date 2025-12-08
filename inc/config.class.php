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
    public const ACTION_CLEAN = 0;

    /**
     * Define number to the action 'change status' of agents
     *
     * @var integer
     */
    public const ACTION_STATUS = 1;


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
        $input['agent_port']             = '62354';
        $input['extradebug']             = '0';
        $input['users_id']               = $users_id;
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
        $this->addStandardTab(PluginGlpiinventoryConfig::class, $ong, $options);
        $this->addStandardTab(PluginGlpiinventoryAgentmodule::class, $ong, $options);

        if (isset($_SESSION['glpi_plugin_glpiinventory']['configuration']['moduletabforms'])) {
            $plugin_tabs = $ong;
            $moduleTabForms
                  = $_SESSION['glpi_plugin_glpiinventory']['configuration']['moduletabforms'];
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
     * @param CommonGLPI $item the item object
     * @param integer $withtemplate 1 if is a template form
     * @return string|array name of the tab
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item instanceof self) {
            return [
                self::createTabEntry(__('General setup'), 0, icon: 'ti ti-settings'),
                self::createTabEntry(__('Network Inventory', 'glpiinventory'), 0, icon: 'ti ti-network'),
                self::createTabEntry(__('Package management', 'glpiinventory'), 0, icon: 'ti ti-package'),
            ];
        }
        return '';
    }


    /**
     * Display the content of the tab
     *
     * @param CommonGLPI $item
     * @param integer $tabnum number of the tab to display
     * @param integer $withtemplate 1 if is a template form
     * @return boolean
     */
    public static function displayTabContentForItem($item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var PluginGlpiinventoryConfig $item */
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
     * @param string $name name in configuration
     * @return null|string|integer
     */
    public function getValue($name)
    {
        /** @var array $PF_CONFIG */
        global $PF_CONFIG;

        if (isset($PF_CONFIG[$name])) {
            return $PF_CONFIG[$name];
        }

        $config = current($this->find(['type' => $name]));
        return $config['value'] ?? null;
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
     * @return void
     */
    public function showConfigForm()
    {
        TemplateRenderer::getInstance()->display('@glpiinventory/forms/config/main.html.twig', [
            'canedit' => true,
            'item' => $this,
        ]);
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

        return '';
    }


    /**
     * Display form for tab 'Network inventory'
     *
     * @return void
     */
    public function showFormNetworkInventory()
    {
        TemplateRenderer::getInstance()->display('@glpiinventory/forms/config/netinv.html.twig', [
            'canedit' => true,
            'item' => $this,
        ]);
    }


    /**
     * Display form for tab 'Deploy'
     *
     * @return void
     */
    public function showFormDeploy()
    {
        TemplateRenderer::getInstance()->display('@glpiinventory/forms/config/deploy.html.twig', [
            'canedit' => true,
            'item' => $this,
            'mirror_values' => [
                PluginGlpiinventoryDeployMirror::MATCH_LOCATION => __('with location', 'glpiinventory'),
                PluginGlpiinventoryDeployMirror::MATCH_ENTITY   => __('with entity', 'glpiinventory'),
                PluginGlpiinventoryDeployMirror::MATCH_BOTH     => __('with both', 'glpiinventory'),
            ],
        ]);
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
                'value' => $value,
            ]);
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
        /** @var array $PF_CONFIG */
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
     * Check if extradebug mode is active
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
     * @param string|string[] $message the message to put in log file
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
     * The only case where table doesn't exist is when you click on
     * uninstall the plugin and it's already uninstalled
     */
    public static function loadCache()
    {
        /** @var DBmysql $DB */
        /** @var array $PF_CONFIG */
        global $DB, $PF_CONFIG;

        if ($DB->tableExists('glpi_plugin_glpiinventory_configs')) {
            $PF_CONFIG = [];
            $configs = $DB->request(['FROM' => 'glpi_plugin_glpiinventory_configs']);
            foreach ($configs as $data) {
                $PF_CONFIG[$data['type']] = $data['value'];
            }
        }
    }

    public static function getIcon()
    {
        return "ti ti-settings";
    }
}
