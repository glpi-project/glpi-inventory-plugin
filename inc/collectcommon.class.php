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
 * Manage the windows registry to get in collect module.
 */
class PluginGlpiinventoryCollectCommon extends CommonDBTM
{
   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = 'plugin_glpiinventory_collect';

    public $type = '';

   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
    public static function getTypeName($nb = 0)
    {
        return '';
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
        if ($item->fields['id'] > 0) {
            if ($item->fields['type'] == $this->type) {
                return __('Collect configuration');
            }
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
        $class     = get_called_class();
        $pfCollect = new $class();
        $pfCollect->showForm($item->fields['id']);
        $pfCollect->showList($item->fields['id']);

        return true;
    }

   /**
   * Get headers to be displayed, as an array
   * @since 9.2+2.0
   *
   * @return array a list of header labels to be displayed
   */
    public function getListHeaders()
    {
        return [
         __('Name')
        ];
    }

   /**
   * Get values for a row to display in the list
   * @since 9.2+2.0
   *
   * @param array $row the row data to be displayed
   * @return array values to be display
   */
    public function displayOneRow($row = [])
    {
        return [
         $row['name']
        ];
    }

   /**
    * Display registries defined in collect
    *
    * @param integer $collects_id id of collect
    */
    public function showList($collects_id)
    {
        global $DB;
        $params = [
         'FROM'  => $this->getTable(),
         'WHERE' => ['plugin_glpiinventory_collects_id' => $collects_id]
        ];
        $iterator = $DB->request($params);

        $class = get_called_class();

        $headers = $this->getListHeaders();

        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr>";
        echo "<tr>";
        foreach ($headers as $label) {
            echo "<th>" . $label . "</th>";
        }
        echo "</tr>";
        foreach ($iterator as $data) {
            echo "<tr>";
            $row_data = $this->displayOneRow($data);
            foreach ($row_data as $value) {
                echo "<td align='center'>$value</td>";
            }
            echo "<td align='center'>";
            echo "<form name='form_bundle_item' action='" . $class::getFormURL() .
                   "' method='post'>";
            echo Html::hidden('id', ['value' => $data['id']]);
            echo "<input type='image' name='delete' src='" . Plugin::getWebDir('glpiinventory') . "/pics/drop.png'>";
            Html::closeForm();
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }


    public function displayNewSpecificities()
    {
    }

   /**
    * Display form to add registry
    *
    * @param integer $collects_id id of collect
    * @param array $options
    * @return true
    */
    public function showForm($collects_id, array $options = [])
    {
        $this->initForm(0, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Name');
        echo "</td>";
        echo "<td>";
        echo Html::hidden(
            'plugin_glpiinventory_collects_id',
            ['value' => $collects_id]
        );
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "</td>";
        $this->displayNewSpecificities();

        echo "</tr>\n";

        $this->showFormButtons($options);

        return true;
    }

    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
         'id'           => 'common',
         'name'         => __('Characteristics')
        ];

        $tab[] = [
         'id'           => '1',
         'table'        => $this->getTable(),
         'field'        => 'name',
         'name'         => __('Name'),
         'datatype'     => 'itemlink'
        ];

        return $tab;
    }
}
