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
 * Manage the IP of VMWARE ESX and link to credentials to be able to inventory
 * these specific systems througth the webservice.
 */
class PluginGlpiinventoryCredentialIp extends CommonDropdown
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
    public $third_level_menu  = "credentialip";

   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = 'plugin_glpiinventory_credentialip';


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
    public static function getTypeName($nb = 0)
    {
        return __('Remote device inventory', 'glpiinventory');
    }


   /**
    * Add more fields
    *
    * @return array
    */
    public function getAdditionalFields()
    {
        return [['name'  => 'itemtype',
                         'label' => __('Type'),
                         'type'  => 'credentials'],
                   ['name'  => 'ip',
                         'label' => __('IP'),
                         'type'  => 'text']];
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
            case 'credentials':
                $field['id'] = $this->fields['plugin_glpiinventory_credentials_id'];
                PluginGlpiinventoryCredential::dropdownCredentials($field);
                break;
        }
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
         'field'         => 'name',
         'name'          => __('Authentication for remote devices (VMware)', 'glpiinventory'),
         'datatype'      => 'itemlink',
         'itemlink_type' => 'PluginGlpiinventoryCredential',
        ];

        $tab[] = [
         'id'       => '4',
         'table'    => $this->getTable(),
         'field'    => 'ip',
         'name'     => __('IP'),
         'datatype' => 'string',
        ];

        return $tab;
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
