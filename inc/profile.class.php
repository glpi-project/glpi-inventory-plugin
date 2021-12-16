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
 * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
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
 * Manage the profiles in plugin.
 */
class PluginGlpiinventoryProfile extends Profile
{
   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = "config";

      /*
       * Old profile names:
       *
       *    agent
       *    remotecontrol
       *    configuration
       *    wol
       *    unmanaged
       *    task
       *    iprange
       *    credential
       *    credentialip
       *    existantrule
       *    importxml
       *    blacklist
       *    ESX
       *    configsecurity
       *    networkequipment
       *    printer
       *    model
       *    reportprinter
       *    reportnetworkequipment
       *    packages
       *    status
       */


   /**
    * Get the mapping old rights => new rights. Require it for upgrade from old
    * version of plugin
    *
    * @return array
    */
    public static function getOldRightsMappings()
    {
        $types = ['agent'                  => 'plugin_glpiinventory_agent',
                     'remotecontrol'          => 'plugin_glpiinventory_remotecontrol',
                     'configuration'          => 'plugin_glpiinventory_configuration',
                     'wol'                    => 'plugin_glpiinventory_wol',
                     'unmanaged'              => 'plugin_glpiinventory_unmanaged',
                     'task'                   => 'plugin_glpiinventory_task',
                     'credential'             => 'plugin_glpiinventory_credential',
                     'credentialip'           => 'plugin_glpiinventory_credentialip',
                     'existantrule'           => ['plugin_glpiinventory_ruleimport',
                                                        'plugin_glpiinventory_ruleentity',
                                                        'plugin_glpiinventory_rulelocation'],
                     'importxml'              => 'plugin_glpiinventory_importxml',
                     'blacklist'              => 'plugin_glpiinventory_blacklist',
                     'ESX'                    => 'plugin_glpiinventory_esx',
                     'configsecurity'         => 'plugin_glpiinventory_configsecurity',
                     'networkequipment'       => 'plugin_glpiinventory_networkequipment',
                     'printer'                => 'plugin_glpiinventory_printer',
                     'reportprinter'          => 'plugin_glpiinventory_reportprinter',
                     'reportnetworkequipment' => 'plugin_glpiinventory_reportnetworkequipment',
                     'packages'               => 'plugin_glpiinventory_package',
                     'status'                 => 'plugin_glpiinventory_status',
                     'collect'                => ['plugin_glpiinventory_collect',
                                                       'plugin_glpiinventory_rulecollect']];

        return $types;
    }


   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string name of the tab
    */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return self::createTabEntry('GLPI Inventory');
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
        $pfProfile = new self();
        if ($item->fields['interface'] == 'central') {
            $pfProfile->showForm($item->fields['id']);
        } else {
            $pfProfile->showFormSelf($item->fields['id']);
        }
        return true;
    }


   /**
    * Display form
    *
    * @param integer $profiles_id
    * @param array $options
    * @return boolean
    */
    public function showForm($profiles_id, $options = [])
    {

        $openform = true;
        $closeform = true;

        echo "<div class='firstbloc'>";
        if (
            ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
            && $openform
        ) {
            $profile = new Profile();
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        }

        $profile = new Profile();
        $profile->getFromDB($profiles_id);

        $rights = $this->getRightsGeneral();
        $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                      'default_class' => 'tab_bg_2',
                                                      'title'         => __('General', 'glpiinventory')]);

        $rights = $this->getRightsRules();
        $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                      'default_class' => 'tab_bg_2',
                                                      'title'         => _n('Rule', 'Rules', 2)]);

        $rights = $this->getRightsInventory();
        $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                      'default_class' => 'tab_bg_2',
                                                      'title'         => __('Inventory', 'glpiinventory')]);

        $rights = $this->getRightsDeploy();
        $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                      'default_class' => 'tab_bg_2',
                                                      'title'         => __('Software deployment', 'glpiinventory')]);
        if (
            $canedit
            && $closeform
        ) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            echo "</div>\n";
            Html::closeForm();
        }
        echo "</div>";

        $this->showLegend();
        return true;
    }


   /**
    * Display profile form for helpdesk interface
    *
    * @param integer $profiles_id
    * @param boolean $openform
    * @param boolean $closeform
    */
    public function showFormSelf($profiles_id = 0, $openform = true, $closeform = true)
    {

        echo "<div class='firstbloc'>";
        if (
            ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
            && $openform
        ) {
            $profile = new Profile();
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        }

        $profile = new Profile();
        $profile->getFromDB($profiles_id);

        $rights = [
          ['rights'    => [READ => __('Read')],
                'label'     => __('Deploy packages on demand', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_selfpackage']
        ];
        $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                      'default_class' => 'tab_bg_2',
                                                      'title'         => __('Software deployment', 'glpiinventory')]);
        if (
            $canedit
            && $closeform
        ) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            echo "</div>\n";
            Html::closeForm();
        }
        echo "</div>";

        $this->showLegend();
    }


   /**
    * Delete profiles
    */
    public static function uninstallProfile()
    {
        $pfProfile = new self();
        $a_rights = $pfProfile->getAllRights();
        foreach ($a_rights as $data) {
            ProfileRight::deleteProfileRights([$data['field']]);
        }
    }


   /**
    * Get all rights
    *
    * @return array
    */
    public function getAllRights()
    {
        $a_rights = [];
        $a_rights = array_merge($a_rights, $this->getRightsGeneral());
        $a_rights = array_merge($a_rights, $this->getRightsInventory());
        $a_rights = array_merge($a_rights, $this->getRightsRules());
        $a_rights = array_merge($a_rights, $this->getRightsDeploy());
        return $a_rights;
    }


   /**
    * Get rights for rules part
    *
    * @return array
    */
    public function getRightsRules()
    {
        $rights = [
          /*['itemtype'  => 'PluginGlpiinventoryInventoryRuleImport',
                'label'     => __('Rules for import and link computers'),
                'field'     => 'plugin_glpiinventory_ruleimport'
          ],*/
          /*['itemtype'  => 'PluginGlpiinventoryInventoryRuleEntity',
                'label'     => __('Entity rules', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_ruleentity'
          ],*/
          /*['itemtype'  => 'PluginGlpiinventoryInventoryRuleImport',
                'label'     => __('Rules for import and link computers'),
                'field'     => 'plugin_glpiinventory_rulelocation'
          ],*/
          /*['itemtype'  => 'PluginGlpiinventoryInventoryComputerBlacklist',
                'label'     => __('Fields blacklist', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_blacklist'
          ],*/
          ['itemtype'  => 'PluginGlpiinventoryCollectRule',
                'label'     => __('Computer information rules', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_rulecollect'
          ],
          /*['itemtype'  => 'PluginGlpiinventoryIgnoredimportdevice',
                'label'     =>  __('Equipment ignored on import', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_ignoredimportdevice'
          ],*/
        ];
        return $rights;
    }


   /**
    * Get rights for deploy part
    *
    * @return array
    */
    public function getRightsDeploy()
    {
        $rights = [
          ['itemtype'  => 'PluginGlpiinventoryDeployPackage',
                'label'     => __('Manage packages', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_package'],
          ['itemtype'  => 'PluginGlpiinventoryDeployPackage',
               'label'     => __('User interaction template', 'glpiinventory'),
               'field'     => 'plugin_glpiinventory_userinteractiontemplate'],
          ['itemtype'  => 'PluginGlpiinventoryDeployMirror',
                'label'     => __('Mirror servers', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_deploymirror'],
          ['itemtype'  => 'PluginGlpiinventoryDeployPackage',
                'label'     => __('Deploy packages on demand', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_selfpackage',
                'rights'    => [READ => __('Read')]]
        ];
        return $rights;
    }


   /**
    * Get rights for inventory part
    *
    * @return array
    */
    public function getRightsInventory()
    {
        $rights = [
          ['itemtype'  => 'PluginGlpiinventoryIprange',
                'label'     => __('IP range configuration', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_iprange'],
          ['itemtype'  => 'PluginGlpiinventoryCredential',
                'label'     => __('Authentication for remote devices (VMware)', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_credential'],
          ['itemtype'  => 'PluginGlpiinventoryCredentialip',
                'label'     => __('Remote devices to inventory (VMware)', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_credentialip'],
          ['itemtype'  => 'PluginGlpiinventoryCredential',
                'label'     => __('VMware host', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_esx'],
          /*['itemtype'  => 'PluginGlpiinventoryConfigSecurity',
                'label'     => __('SNMP credentials', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_configsecurity'],*/
          ['rights'    => [CREATE => __('Create')],
                'label'     => __('Network equipment SNMP', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_networkequipment'],
          ['rights'    => [CREATE => __('Create')],
                'label'     => __('Printer SNMP', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_printer'],
          /*['itemtype'  => 'PluginGlpiinventoryUnmanaged',
                'label'     => __('Unmanaged devices', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_unmanaged'],*/
          /*['itemtype'  => 'PluginGlpiinventoryInventoryComputerImportXML',
                'label'     => __('computer XML manual import', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_importxml'],*/
          ['rights'    => [READ => __('Read')],
                'label'     => __('Printers report', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_reportprinter'],
          ['rights'    => [READ => __('Read')],
                'label'     => __('Network report'),
                'field'     => 'plugin_glpiinventory_reportnetworkequipment']
        ];
        return $rights;
    }


   /**
    * Get general rights
    *
    * @return array
    */
    public function getRightsGeneral()
    {
        $rights = [
          ['rights'    => [READ => __('Read')],
                'label'     => __('Menu', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_menu'],
          ['itemtype'  => 'Agent',
                'label'     => __('Agents', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_agent'],
          ['rights'    => [READ => __('Read')],
                'label'     => __('Agent remote control', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_remotecontrol'],
          ['rights'    => [READ => __('Read'), UPDATE => __('Update')],
                'itemtype'  => 'PluginGlpiinventoryConfig',
                'label'     => __('Configuration', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_configuration'],
          ['itemtype'  => 'PluginGlpiinventoryTask',
                'label'     => _n('Task', 'Tasks', 2, 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_task'],
          ['rights'    => [READ => __('Read')],
                'label'     => __('Wake On LAN', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_wol'],
          ['itemtype'  => 'PluginGlpiinventoryDeployGroup',
                'label'     => __('Groups of computers', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_group'],
          ['itemtype'  => 'PluginGlpiinventoryCollect',
                'label'     => __('Computer information', 'glpiinventory'),
                'field'     => 'plugin_glpiinventory_collect']
        ];

        return $rights;
    }


   /**
    * Add the default profile
    *
    * @param integer $profiles_id
    * @param array $rights
    */
    public static function addDefaultProfileInfos($profiles_id, $rights)
    {
        $profileRight = new ProfileRight();
        foreach ($rights as $right => $value) {
            if (
                !countElementsInTable(
                    'glpi_profilerights',
                    ['profiles_id' => $profiles_id, 'name' => $right]
                )
            ) {
                $myright['profiles_id'] = $profiles_id;
                $myright['name']        = $right;
                $myright['rights']      = $value;
                $profileRight->add($myright);

                //Add right to the current session
                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }


   /**
    * Create first access (so default profile)
    *
    * @param integer $profiles_id id of profile
    */
    public static function createFirstAccess($profiles_id)
    {
        include_once(PLUGIN_GLPI_INVENTORY_DIR . "/inc/profile.class.php");
        $profile = new self();
        foreach ($profile->getAllRights() as $right) {
            self::addDefaultProfileInfos(
                $profiles_id,
                [$right['field'] => ALLSTANDARDRIGHT]
            );
        }
    }


   /**
    * Delete rights stored in session
    */
    public static function removeRightsFromSession()
    {
        $profile = new self();
        foreach ($profile->getAllRights() as $right) {
            if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
                unset($_SESSION['glpiactiveprofile'][$right['field']]);
            }
        }
        ProfileRight::deleteProfileRights([$right['field']]);

        if (isset($_SESSION['glpimenu']['plugins']['types']['PluginGlpiinventoryMenu'])) {
            unset($_SESSION['glpimenu']['plugins']['types']['PluginGlpiinventoryMenu']);
        }
        if (isset($_SESSION['glpimenu']['plugins']['content']['pluginglpiinventorymenu'])) {
            unset($_SESSION['glpimenu']['plugins']['content']['pluginglpiinventorymenu']);
        }
        if (isset($_SESSION['glpimenu']['assets']['types']['PluginGlpiinventoryUnmanaged'])) {
            unset($_SESSION['glpimenu']['plugins']['types']['PluginGlpiinventoryUnmanaged']);
        }
        if (isset($_SESSION['glpimenu']['assets']['content']['pluginglpiinventoryunmanaged'])) {
            unset($_SESSION['glpimenu']['assets']['content']['pluginglpiinventoryunmanaged']);
        }
    }


   /**
    * Migration script for old rights from old version of plugin
    */
    public static function migrateProfiles()
    {
       //Get all rights from the old table
        $profiles = getAllDataFromTable(getTableForItemType(__CLASS__));

       //Load mapping of old rights to their new equivalent
        $oldrights = self::getOldRightsMappings();

       //For each old profile : translate old right the new one
        foreach ($profiles as $profile) {
            switch ($profile['right']) {
                case 'r':
                    $value = READ;
                    break;
                case 'w':
                    $value = ALLSTANDARDRIGHT;
                    break;
                case 0:
                default:
                    $value = 0;
                    break;
            }
           //Write in glpi_profilerights the new right
            if (isset($oldrights[$profile['type']])) {
               //There's one new right corresponding to the old one
                if (!is_array($oldrights[$profile['type']])) {
                    self::addDefaultProfileInfos(
                        $profile['profiles_id'],
                        [$oldrights[$profile['type']] => $value]
                    );
                } else {
                   //One old right has been splitted into serveral new ones
                    foreach ($oldrights[$profile['type']] as $newtype) {
                        self::addDefaultProfileInfos(
                            $profile['profiles_id'],
                            [$newtype => $value]
                        );
                    }
                }
            }
        }
    }


   /**
    * Init profiles during installation:
    * - add rights in profile table for the current user's profile
    * - current profile has all rights on the plugin
    */
    public static function initProfile()
    {
        $pfProfile = new self();
        $profile   = new Profile();
        $a_rights  = $pfProfile->getAllRights();
        foreach ($a_rights as $data) {
            if (
                countElementsInTable(
                    "glpi_profilerights",
                    ['name' => $data['field']]
                ) == 0
            ) {
                ProfileRight::addProfileRights([$data['field']]);
                $_SESSION['glpiactiveprofile'][$data['field']] = 0;
            }
        }

       // Add all rights to current profile of the user
        if (isset($_SESSION['glpiactiveprofile'])) {
            $dataprofile       = [];
            $dataprofile['id'] = $_SESSION['glpiactiveprofile']['id'];
            $profile->getFromDB($_SESSION['glpiactiveprofile']['id']);
            foreach ($a_rights as $info) {
                if (
                    is_array($info)
                    && ((!empty($info['itemtype'])) || (!empty($info['rights'])))
                    && (!empty($info['label'])) && (!empty($info['field']))
                ) {
                    if (isset($info['rights'])) {
                        $rights = $info['rights'];
                    } else {
                        $rights = $profile->getRightsFor($info['itemtype']);
                    }
                    foreach ($rights as $right => $label) {
                         $dataprofile['_' . $info['field']][$right] = 1;
                         $_SESSION['glpiactiveprofile'][$data['field']] = $right;
                    }
                }
            }
            $profile->update($dataprofile);
        }
    }
}
