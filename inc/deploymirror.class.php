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

use Glpi\Application\View\TemplateRenderer;

/**
 * Manage the deploy mirror depend on location of computer.
 */
class PluginGlpiinventoryDeployMirror extends CommonDBTM
{
    const MATCH_LOCATION = 0;
    const MATCH_ENTITY   = 1;
    const MATCH_BOTH     = 2;

   /**
    * We activate the history.
    *
    * @var boolean
    */
    public $dohistory = true;

   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = 'plugin_glpiinventory_deploymirror';


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
    public static function getTypeName($nb = 0)
    {
        return __('Mirror servers', 'glpiinventory');
    }


   /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addStandardTab('Log', $ong, $options);

        return $ong;
    }


   /**
    * Get and filter mirrors list by computer agent and location.
    * Location is retrieved from the computer data.
    *
    * @global array $PF_CONFIG
    * @param integer $agents_id
    * @return array
    */
    public static function getList($agents_id)
    {
        global $PF_CONFIG, $DB;

        if (is_null($agents_id)) {
            return [];
        }

        $agent = new Agent();
        $agent->getFromDB($agents_id);
        $agent = $agent->fields;
        if (!isset($agent) || !isset($agent['items_id'])) {
            return [];
        }

        $computer = new Computer();
        $computer->getFromDB($agent['items_id']);

       //If no configuration has been done in the plugin's configuration
       //then use location for mirrors as default
       //!!this should not happen!!
        if (!isset($PF_CONFIG['mirror_match'])) {
            $mirror_match = self::MATCH_LOCATION;
        } else {
           //Get mirror matching from plugin's general configuration
            $mirror_match = $PF_CONFIG['mirror_match'];
        }

       //Get all mirrors for the agent's entity, or for entities above
       //sorted by entity level in a descending way (going to the closest,
       //deepest entity, to the highest)
        $query     = "SELECT `mirror`.*, `glpi_entities`.`level`
                    FROM `glpi_plugin_glpiinventory_deploymirrors` AS mirror
                    LEFT JOIN `glpi_entities`
                     ON (`mirror`.`entities_id`=`glpi_entities`.`id`)
                    WHERE `mirror`.`is_active`='1'";
        $query    .= getEntitiesRestrictRequest(
            ' AND ',
            'mirror',
            'entities_id',
            $agent['entities_id'],
            true
        );
        $query   .= " ORDER BY `glpi_entities`.`level` DESC";

       //The list of mirrors to return
        $mirrors  = [];

        foreach ($DB->request($query) as $result) {
           //First, check mirror by location
            if (
                in_array($mirror_match, [self::MATCH_LOCATION, self::MATCH_BOTH])
                && $computer->fields['locations_id'] > 0
                && $computer->fields['locations_id'] == $result['locations_id']
            ) {
                $mirrors[] = $result['url'];
            }

           //Second, check by entity
            if (in_array($mirror_match, [self::MATCH_ENTITY, self::MATCH_BOTH])) {
                $entities = $result['entities_id'];

               //If the mirror is visible in child entities then get all child entities
               //and check it the agent's entity is one of it
                if ($result['is_recursive']) {
                    $entities = getSonsOf('glpi_entities', $result['entities_id']);
                }

                $add_mirror = false;
                if (
                    is_array($entities)
                    && in_array($computer->fields['entities_id'], $entities)
                ) {
                    $add_mirror = true;
                } elseif ($computer->fields['entities_id'] == $result['entities_id']) {
                    $add_mirror = true;
                }
                if (!in_array($result['url'], $mirrors) && $add_mirror) {
                    $mirrors[] = $result['url'];
                }
            }
        }

       //add default mirror (this server) if enabled in config
        $entities_id = 0;
        if (isset($agent['entities_id'])) {
            $entities_id = $agent['entities_id'];
        }

       //If option is set to yes in general plugin configuration
       //Add the server's url as the last url in the list
        if (
            isset($PF_CONFIG['server_as_mirror'])
            && $PF_CONFIG['server_as_mirror'] == true
        ) {
            $mirrors[] = PluginGlpiinventoryAgentmodule::getUrlForModule('DEPLOY', $entities_id)
            . "?action=getFilePart&file=";
        }
        return $mirrors;
    }


   /**
    * Display form
    *
    * @global array $CFG_GLPI
    * @param integer $id
    * @param array $options
    * @return true
    */
    public function showForm($id, array $options = [])
    {
        $this->initForm($id, $options);
        TemplateRenderer::getInstance()->display('@glpiinventory/forms/deploymirror.html.twig', [
         'item'   => $this,
         'params' => $options,
        ]);

        return true;
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
         'name' => self::getTypeName(),
        ];

        $tab[] = [
         'id'            => '1',
         'table'         => $this->getTable(),
         'field'         => 'name',
         'name'          => __('Name'),
         'datatype'      => 'itemlink',
         'itemlink_type' => $this->getType()
        ];

        $tab[] = [
         'id'        => '19',
         'table'     => $this->getTable(),
         'field'     => 'date_mod',
         'name'      => __('Last update'),
         'datatype'  => 'datetime',
        ];

        $tab[] = [
         'id'           => '2',
         'table'        => $this->getTable(),
         'field'        => 'url',
         'name'         => __('Mirror server address', 'glpiinventory'),
         'datatype'     => 'string'
        ];

        $tab[] = [
         'id'        => '6',
         'table'     => $this->getTable(),
         'field'     => 'is_active',
         'name'      => __('Active'),
         'datatype'  => 'bool',
        ];

        $tab[] = [
         'id'        => '16',
         'table'     => $this->getTable(),
         'field'     => 'comment',
         'name'      => __('Comments'),
         'datatype'  => 'text',
        ];

        $tab[] = [
         'id'       => '80',
         'table'    => 'glpi_entities',
         'field'    => 'completename',
         'name'     => Entity::getTypeName(1),
         'datatype' => 'dropdown',
        ];

        $tab[] = [
         'id'        => '86',
         'table'     => $this->getTable(),
         'field'     => 'is_recursive',
         'name'      => __('Child entities'),
         'datatype'  => 'bool',
        ];

        $name = _n('Volume', 'Volumes', Session::getPluralNumber());
        $tab[] = [
          'id'                 => 'disk',
          'name'               => $name
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        return $tab;
    }


   /**
    * Get the massive actions for this object
    *
    * @param object|null $checkitem
    * @return array list of actions
    */
    public function getSpecificMassiveActions($checkitem = null)
    {
        return [__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'transfer'
               => __('Transfer')];
    }


   /**
    * Display form related to the massive action selected
    *
    * @param object $ma MassiveAction instance
    * @return boolean
    */
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        if ($ma->getAction() == 'transfer') {
            Dropdown::show('Entity');
            echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
            return true;
        }
        return false;
    }


   /**
    * Execution code for massive action
    *
    * @param object $ma MassiveAction instance
    * @param object $item item on which execute the code
    * @param array $ids list of ID on which execute the code
    */
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        $pfDeployMirror = new self();
        switch ($ma->getAction()) {
            case "transfer":
                foreach ($ids as $key) {
                    if ($pfDeployMirror->getFromDB($key)) {
                        $input = [];
                        $input['id'] = $key;
                        $input['entities_id'] = $_POST['entities_id'];
                        if ($pfDeployMirror->update($input)) {
                          //set action massive ok for this item
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                        } else {
                         // KO
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        }
                    }
                }
                break;
        }
    }
}
