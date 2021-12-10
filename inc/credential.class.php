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
 * Manage the credentials for inventory VMWARE ESX.
 */
class PluginGlpiinventoryCredential extends CommonDropdown
{

   /**
    * Define first level menu name
    *
    * @var string
    */
    public $first_level_menu  = "admin";

   /**
    * Define second level menu name
    *
    * @var string
    */
    public $second_level_menu = "pluginglpiinventorymenu";

   /**
    * Define third level menu name
    *
    * @var string
    */
    public $third_level_menu  = "credential";

   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = 'plugin_glpiinventory_credential';


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
    public static function getTypeName($nb = 0)
    {
        return __('Authentication for remote devices (VMware)', 'glpiinventory');
    }


   /**
    * Fields added to this class
    *
    * @return array
    */
    public function getAdditionalFields()
    {

        return [['name'  => 'itemtype',
                         'label' => __('Type'),
                         'type'  => 'credential_itemtype'],
                   ['name'  => 'username',
                         'label' => __('Login'),
                         'type'  => 'text'],
                   ['name'  => 'password',
                         'label' => __('Password'),
                         'type'  => 'password']];
    }


   /**
    * Display specific fields
    *
    * @param integer $ID
    * @param array $field
    */
    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {

        switch ($field['type']) {
            case 'credential_itemtype':
                $this->showItemtype($ID);
                break;
        }
    }


   /**
    * DIsplay the credential itemtype
    *
    * @param integer $ID
    */
    public function showItemtype($ID)
    {

       //Criteria already added : only display the selected itemtype
        if ($ID > 0) {
            $label = self::getLabelByItemtype($this->fields['itemtype']);
            if ($label) {
                echo $label;
                echo "<input type='hidden' name='itemtype' value='" . $this->fields['itemtype'] . "'";
            }
        } else {
           //Add criteria : display dropdown
            $options = self::getCredentialsItemTypes();
            $options[''] = Dropdown::EMPTY_VALUE;
            asort($options);
            Dropdown::showFromArray('itemtype', $options);
        }
    }


   /**
    * Define more tabs to display
    *
    * @param array $options
    * @return array
    */
    public function defineMoreTabs($options = [])
    {
        return [];
    }


   /**
    * Display more tabs
    *
    * @param array $tab
    */
    public function displayMoreTabs($tab)
    {
    }


   /**
    * Get search function for the class
    *
    * @return array
    */
    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
         'id'   => 'common',
         'name' => __('Authentication for remote devices (VMware)', 'glpiinventory'),
        ];

        $tab[] = [
         'id'       => '1',
         'table'    => $this->getTable(),
         'field'    => 'name',
         'name'     => __('Name'),
         'datatype' => 'itemlink',
        ];

        $tab[] = [
         'id'       => '2',
         'table'    => 'glpi_entities',
         'field'    => 'completename',
         'name'     => Entity::getTypeName(1),
         'datatype' => 'dropdown',
        ];

        $tab[] = [
         'id'            => '3',
         'table'         => $this->getTable(),
         'field'         => 'itemtype',
         'name'          => __('Type'),
         'massiveaction' => false,
        ];

        $tab[] = [
         'id'    => '4',
         'table' => $this->getTable(),
         'field' => 'username',
         'name'  => __('Login'),
        ];

        return $tab;
    }


   /**
    * Perform checks to be sure that an itemtype and at least a field are
    * selected
    *
    * @param array $input the values to insert in DB
    * @return array
    */
    public static function checkBeforeInsert($input)
    {

        if ($input['password'] == '') {
            unset($input['password']);
        }

        if (!$input['itemtype']) {
            Session::addMessageAfterRedirect(
                __('It\'s mandatory to select a type and at least one field'),
                true,
                ERROR
            );
            $input = [];
        }
        return $input;
    }


   /**
    * Prepare data before add to database
    *
    * @param array $input
    * @return array
    */
    public function prepareInputForAdd($input)
    {
        return self::checkBeforeInsert($input);
    }


   /**
    * Prepare data before update in database
    *
    * @param array $input
    * @return array
    */
    public function prepareInputForUpdate($input)
    {
        return $input;
    }


   /**
    * Get an itemtype label by the credential itemtype
    *
    * @param string $credential_itemtype for example PluginGlpiinventoryInventoryComputerESX
    * @return string|false
    */
    public static function getLabelByItemtype($credential_itemtype)
    {
        $credentialtypes = self::findItemtypeType($credential_itemtype);
        if (!empty($credentialtypes)) {
            return $credentialtypes['name'];
        }
        return false;
    }


   /**
    * Find a credential by his itemtype
    *
    * @param string $credential_itemtype for example PluginGlpiinventoryInventoryComputerESX
    * @return array
    */
    public static function findItemtypeType($credential_itemtype)
    {

        $credential = ['itemtype' => 'PluginGlpiinventoryInventoryComputerESX', //Credential itemtype
                           'name'    => __('VMware host', 'glpiinventory'), //Label
                           'targets' => ['Computer']];
        if ($credential['itemtype'] == $credential_itemtype) {
            return $credential;
        }
        return [];
    }


   /**
    * Get all credentials itemtypes
    *
    * @return array
    */
    public static function getCredentialsItemTypes()
    {
        return ['PluginGlpiinventoryInventoryComputerESX' =>
                           __('VMware host', 'glpiinventory')];
    }


   /**
    * Get credential types
    *
    * @param string $itemtype
    * @return array
    */
    public static function getForItemtype($itemtype)
    {
        $itemtypes = [];
        foreach (PluginGlpiinventoryModule::getAll() as $data) {
            $class = PluginGlpiinventoryStaticmisc::getStaticMiscClass($data['directory']);
            if (is_callable([$class, 'credential_types'])) {
                foreach (call_user_func([$class, 'credential_types']) as $credential) {
                    if (in_array($itemtype, $credential['targets'])) {
                        $itemtypes[$credential['itemtype']] = $credential['name'];
                    }
                }
            }
        }
        return $itemtypes;
    }


   /**
    * Display dropdown with credentials
    *
    * @global array $CFG_GLPI
    * @param array $params
    */
    public static function dropdownCredentials($params = [])
    {
        global $CFG_GLPI;

        $p = [];
        if ($params['id'] == -1) {
            $p['value']    = '';
            $p['itemtype'] = '';
            $p['id']       = 0;
        } else {
            $credential = new PluginGlpiinventoryCredential();
            $credential->getFromDB($params['id']);
            if ($credential->getFromDB($params['id'])) {
                $p = $credential->fields;
            } else {
                $p['value']    = '';
                $p['itemtype'] = '';
                $p['id']       = 0;
            }
        }

        $types     = self::getCredentialsItemTypes();
        $types[''] = Dropdown::EMPTY_VALUE;
        $rand      = Dropdown::showFromArray(
            'plugin_glpiinventory_credentials_id',
            $types,
            ['value' => $p['itemtype']]
        );
        $ajparams = ['itemtype' => '__VALUE__',
                        'id'       => $p['id']];
        $url       = Plugin::getWebDir('glpiinventory') . "/ajax/dropdownCredentials.php";
        Ajax::updateItemOnSelectEvent(
            "dropdown_plugin_glpiinventory_credentials_id$rand",
            "span_credentials",
            $url,
            $ajparams
        );

        echo "&nbsp;<span name='span_credentials' id='span_credentials'>";
        if ($p['id']) {
            self::dropdownCredentialsForItemtype($p);
        }
        echo "</span>";
    }


   /**
    * Display dropdown of credentials for itemtype
    *
    * @param array $params
    */
    public static function dropdownCredentialsForItemtype($params = [])
    {

        if (empty($params['itemtype'])) {
            return;
        }

       // params
       // Array([itemtype] => PluginGlpiinventoryInventoryComputerESX [id] => 0)
        if ($params['itemtype'] == 'PluginGlpiinventoryInventoryComputerESX') {
            $params['itemtype'] = 'PluginGlpiinventoryCredential';
        }
        $value = 0;
        if (isset($params['id'])) {
            $value = $params['id'];
        }
        Dropdown::show($params['itemtype'], ['entity_sons' => true,
                                                'value'       => $value]);
    }


   /**
    * Check if there's at least one credential itemetype
    *
    * @return boolean
    */
    public static function hasAlLeastOneType()
    {
        $types = self::getCredentialsItemTypes();
        return (!empty($types));
    }


   /**
    * Display a specific header
    */
    public function displayHeader()
    {
       //Common dropdown header
        parent::displayHeader();

        PluginGlpiinventoryMenu::displayMenu("mini");
    }
}
