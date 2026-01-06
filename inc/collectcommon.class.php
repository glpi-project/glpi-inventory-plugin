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

    public $collect_type = '';

    /**
     * Get name of this type by language of the user connected
     *
     * @param int $nb number of elements
     * @return string name of this type
     */
    public static function getTypeName($nb = 0)
    {
        return '';
    }



    /**
     * Get the tab name used for item
     *
     * @param CommonGLPI $item the item object
     * @param int $withtemplate 1 if is a template form
     * @return string name of the tab
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        /** @var CommonDBTM $item */
        if ($item->fields['id'] > 0) {
            if ($item->fields['type'] == $this->collect_type) {
                return  self::createTabEntry(__('Collect configuration'), 0, icon: 'ti ti-settings');
            }
        }
        return '';
    }


    /**
     * Display the content of the tab
     *
     * @param CommonGLPI $item
     * @param int $tabnum number of the tab to display
     * @param int $withtemplate 1 if is a template form
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof PluginGlpiinventoryCollect) {
            return false;
        }

        $class     = static::class;
        $pfCollect = new $class();
        $pfCollect->showAddForm($item);
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
            __('Name'),
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
            $row['name'],
        ];
    }

    /**
     * Display registries defined in collect
     *
     * @param int $collects_id id of collect
     *
     * @return void
     */
    public function showList($collects_id)
    {
        global $DB;
        $params = [
            'FROM'  => $this->getTable(),
            'WHERE' => ['plugin_glpiinventory_collects_id' => $collects_id],
        ];
        $iterator = $DB->request($params);

        $entries = [];
        foreach ($iterator as $row) {
            $entry = ['name' => $row['name']] + $this->displayOneRow($row);
            $entry['action'] = "<form action='" . static::getFormURL() . "' method='post'>"
                . Html::hidden('id', ['value' => $row['id']])
                . '<button type="submit" name="delete" class="btn btn-icon btn-ghost-danger"><i class="ti ti-trash"></i></button>'
                . Html::closeForm(false);
            $entries[] = $entry;
        }
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => ['name' => __('Name')] + $this->getListHeaders() + ['action' => __("Action")],
            'formatters' => [
                'action' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
        ]);
    }


    public function displayNewSpecificities() {}

    /**
     * Display add form
     *
     * @param PluginGlpiinventoryCollect $collect
     *
     * @return void
     */
    public function showAddForm(PluginGlpiinventoryCollect $collect)
    {
        TemplateRenderer::getInstance()->display('@glpiinventory/forms/collect/add.html.twig', [
            'item' => $this,
            'collect' => $collect,
            'collects_id' => $collect->getID(),
        ]);
    }

    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id'           => 'common',
            'name'         => __('Characteristics'),
        ];

        $tab[] = [
            'id'           => '1',
            'table'        => $this->getTable(),
            'field'        => 'name',
            'name'         => __('Name'),
            'datatype'     => 'itemlink',
        ];

        return $tab;
    }
}
