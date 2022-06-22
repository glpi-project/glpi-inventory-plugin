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

use Glpi\Application\View\TemplateRenderer;

/**
 * Manage the IP ranges for network discovery and network inventory.
 */
class PluginGlpiinventoryIPRange extends CommonDBTM
{
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
    public static $rightname = 'plugin_glpiinventory_iprange';


   /**
    * Check if can create an IP range
    *
    * @return true
    */
    public static function canCreate()
    {
        return true;
    }


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
    public static function getTypeName($nb = 0)
    {

        if (isset($_SERVER['HTTP_REFERER']) and strstr($_SERVER['HTTP_REFERER'], 'iprange')) {
            if ((isset($_POST['glpi_tab'])) and ($_POST['glpi_tab'] == 1)) {
                // Permanent task discovery
                return __('Communication mode', 'glpiinventory');
            } elseif ((isset($_POST['glpi_tab'])) and ($_POST['glpi_tab'] == 2)) {
               // Permanent task inventory
                return __('See all informations of task', 'glpiinventory');
            } else {
                return __('IP Ranges', 'glpiinventory');
            }
        }
        return __('IP Ranges', 'glpiinventory');
    }


   /**
    * Get comments of the object
    *
    * @return string comments in HTML format
    */
    public function getComments()
    {
        $comment = $this->fields['ip_start'] . " -> " . $this->fields['ip_end'];
        return Html::showToolTip($comment, ['display' => false]);
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
         'id' => 'common',
         'name' => __('IP range configuration', 'glpiinventory')
        ];

        $tab[] = [
         'id'           => '1',
         'table'        => $this->getTable(),
         'field'        => 'name',
         'name'         => __('Name'),
         'datatype'     => 'itemlink'
        ];

        $tab[] = [
         'id'        => '2',
         'table'     => 'glpi_entities',
         'field'     => 'completename',
         'linkfield' => 'entities_id',
         'name'      => Entity::getTypeName(1),
         'datatype'  => 'dropdown',
        ];

        $tab[] = [
         'id'        => '3',
         'table'     => $this->getTable(),
         'field'     => 'ip_start',
         'name'      => __('Start of IP range', 'glpiinventory'),
        ];

        $tab[] = [
         'id'        => '4',
         'table'     => $this->getTable(),
         'field'     => 'ip_end',
         'name'      => __('End of IP range', 'glpiinventory'),
        ];

        $tab[] = [
         'id'            => '5',
         'table'         => SNMPCredential::getTable(),
         'field'         => 'name',
         'datatype'      => 'dropdown',
         'right'         => 'all',
         'name'          => SNMPCredential::getTypeName(1),
         'forcegroupby'  => true,
         'massiveaction' => false,
         'joinparams'    => [
            'beforejoin' => [
               'table'      => PluginGlpiinventoryIPRange_SNMPCredential::getTable(),
               'joinparams' => [
                  'jointype' => 'child',
               ],
            ],
         ],
        ];

        return $tab;
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
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }


   /**
    * Display form
    *
    * @param integer $id
    * @param array $options
    * @return true
    */
    public function showForm($id, array $options = [])
    {
        $this->initForm($id, $options);
        TemplateRenderer::getInstance()->display('@glpiinventory/forms/iprange.html.twig', [
         'item'   => $this,
         'params' => $options,
        ]);

        return true;
    }


   /**
    * Check if IP is valid
    *
    * @param array $a_input array of IPs
    * @return boolean
    */
    public function checkip($a_input)
    {

        $count = 0;
        foreach ($a_input as $num => $value) {
            if (strstr($num, "ip_")) {
                if (($value > 255) or (!is_numeric($value)) or strstr($value, ".")) {
                    $count++;
                    $a_input[$num] = "<font color='#ff0000'>" . $a_input[$num] . "</font>";
                }
            }
        }

        if ($count == '0') {
            return true;
        } else {
            Session::addMessageAfterRedirect("<font color='#ff0000'>" . __('Bad IP', 'glpiinventory') .
            "</font><br/>" .
            __('Start of IP range', 'glpiinventory') . " : " .
            $a_input['ip_start0'] . "." . $a_input['ip_start1'] . "." .
            $a_input['ip_start2'] . "." . $a_input['ip_start3'] . "<br/>" .
            __('End of IP range', 'glpiinventory') . " : " .
            $a_input['ip_end0'] . "." . $a_input['ip_end1'] . "." .
            $a_input['ip_end2'] . "." . $a_input['ip_end3']);
            return false;
        }
    }


   /**
    * Get ip in long format
    *
    * @param string $ip IP in format IPv4
    * @return integer $int
    */
    public function getIp2long($ip)
    {
        $int = ip2long($ip);
        if ($int < 0) {
            $int = sprintf("%u\n", ip2long($ip));
        }
        return $int;
    }


   /**
    * After purge item, delete SNMP credentials linked to this ip range
    */
    public function post_purgeItem()
    {
        $pfIPRange_credentials = new PluginGlpiinventoryIPRange_SNMPCredential();
        $a_data = getAllDataFromTable(
            PluginGlpiinventoryIPRange_SNMPCredential::getTable(),
            ['plugin_glpiinventory_ipranges_id' => $this->fields['id']]
        );
        foreach ($a_data as $data) {
            $pfIPRange_credentials->delete($data);
        }
        parent::post_deleteItem();
    }


   /**
    * Get the massive actions for this object
    *
    * @param object|null $checkitem
    * @return array list of actions
    */
    public function getSpecificMassiveActions($checkitem = null)
    {

        $actions = [];
        if (Session::haveRight("plugin_glpiinventory_task", UPDATE)) {
            $actions['PluginGlpiinventoryTask' . MassiveAction::CLASS_ACTION_SEPARATOR . 'addtojob_target'] = __('Target a task', 'glpiinventory');
        }
        return $actions;
    }
}
