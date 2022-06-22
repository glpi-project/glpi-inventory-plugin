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
 * Manage SNMP credentials associated with IP ranges.
 */
class PluginGlpiinventoryIPRange_SNMPCredential extends CommonDBRelation
{
   /**
    * Itemtype for the first part of relation
    *
    * @var string
    */
    public static $itemtype_1    = 'PluginGlpiinventoryIPRange';

   /**
    * id field name for the first part of relation
    *
    * @var string
    */
    public static $items_id_1    = 'plugin_glpiinventory_ipranges_id';

   /**
    * Restrict the first item to the current entity
    *
    * @var string
    */
    public static $take_entity_1 = true;

   /**
    * Itemtype for the second part of relation
    *
    * @var string
    */
    public static $itemtype_2    = 'SNMPCredential';

   /**
    * id field name for the second part of relation
    *
    * @var string
    */
    public static $items_id_2    = 'snmpcredentials_id';

   /**
    * Not restrict the second item to the current entity
    *
    * @var string
    */
    public static $take_entity_2 = false;


   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string name of the tab
    */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->fields['id'] > 0) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    PluginGlpiinventoryIPRange_SNMPCredential::getTable(),
                    [
                        'plugin_glpiinventory_ipranges_id' => $item->getID()
                    ]
                );
            }
            return self::createTabEntry(__('Associated SNMP credentials', 'glpiinventory'), $nb);
        }
        return '';
    }


   /**
    * Display the content of the tab
    *
    * @param object $item
    * @param integer $tabnum number of the tab to display
    * @param integer $withtemplate 1 if is a template form
    * @return true
    */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $pfIPRange_credentials = new self();
        $pfIPRange_credentials->showItemForm($item);
        return true;
    }


   /**
    * Get standard massive action forbidden (hide in massive action list)
    *
    * @return array
    */
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


   /**
    * Display form
    *
    * @param object $item
    * @param array $options
    * @return boolean
    */
    public function showItemForm(CommonDBTM $item, array $options = [])
    {

        $ID = $item->getField('id');

        if ($item->isNewID($ID)) {
            return false;
        }

        if (!$item->can($item->fields['id'], READ)) {
            return false;
        }
        $rand = mt_rand();

        $a_data = getAllDataFromTable(
            self::getTable(),
            [
            'WHERE' => [
               'plugin_glpiinventory_ipranges_id' => $item->getID()
            ],
            'ORDER' => 'rank'
            ]
        );
        $a_used = [];
        foreach ($a_data as $data) {
            $a_used[] = $data['snmpcredentials_id'];
        }
        echo "<div class='firstbloc'>";
        echo "<form name='iprange_snmpcredential_form$rand' id='iprange_snmpcredential_form$rand' method='post'
             action='" . self::getFormURL() . "' >";

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_2'>";
        echo "<th colspan='2'>" . __('Add SNMP credentials') . "</th>";
        echo "</tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        Dropdown::show(SNMPCredential::getType(), ['used' => $a_used]);
        echo "</td>";
        echo "<td>";
        echo Html::hidden(
            'plugin_glpiinventory_ipranges_id',
            ['value' => $item->getID()]
        );
        echo "<input type='submit' name='add' value=\"" .
          _sx('button', 'Associate') . "\" class='submit'>";
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        Html::closeForm();
        echo "</div>";

       // Display list of auth associated with IP range
        $rand = mt_rand();

        echo "<div class='spaced'>";
        Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
        $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
        Html::showMassiveActions($massiveactionparams);

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_2'>";
        echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
        echo "<th>";
        echo __('SNMP credentials', 'glpiinventory');
        echo "</th>";
        echo "<th>";
        echo __('Version', 'glpiinventory');
        echo "</th>";
        echo "<th>";
        echo __('By order of priority', 'glpiinventory');
        echo "</th>";
        echo "</tr>";

        $credentials = new SNMPCredential();
        foreach ($a_data as $data) {
            echo "<tr class='tab_bg_2'>";
            echo "<td>";
            Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
            echo "</td>";
            echo "<td>";
            $credentials->getFromDB($data['snmpcredentials_id']);
            echo $credentials->getLink();
            echo "</td>";
            echo "<td>";
            echo $credentials->getRealVersion();
            echo "</td>";
            echo "<td>";
            echo $data['rank'];
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        $massiveactionparams['ontop'] = false;
        Html::showMassiveActions($massiveactionparams);
        echo "</div>";
        return true;
    }
}
