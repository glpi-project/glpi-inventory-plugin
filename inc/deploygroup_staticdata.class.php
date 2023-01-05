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
 * Manage the static groups (add manually computers in the group).
 */
class PluginGlpiinventoryDeployGroup_Staticdata extends CommonDBRelation
{
   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = "plugin_glpiinventory_group";

   /**
    * Itemtype for the first part of relation
    *
    * @var string
    */
    public static $itemtype_1 = 'PluginGlpiinventoryDeployGroup';

   /**
    * id field name for the first part of relation
    *
    * @var string
    */
    public static $items_id_1 = 'plugin_glpiinventory_deploygroups_id';

   /**
    * Itemtype for the second part of relation
    *
    * @var string
    */
    public static $itemtype_2 = 'itemtype';

   /**
    * id field name for the second part of relation
    *
    * @var string
    */
    public static $items_id_2 = 'items_id';


   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string|array name of the tab
    */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (
            !$withtemplate
            && ($item->getType() == 'PluginGlpiinventoryDeployGroup')
             && $item->fields['type'] == PluginGlpiinventoryDeployGroup::STATIC_GROUP
        ) {
            $tabs[1] = _n('Criterion', 'Criteria', 2);
            $count = countElementsInTable(
                getTableForItemType(__CLASS__),
                [
                'itemtype'                               => 'Computer',
                'plugin_glpiinventory_deploygroups_id' => $item->fields['id'],
                ]
            );
            if ($_SESSION['glpishow_count_on_tabs']) {
                $tabs[2] = self::createTabEntry(_n('Associated item', 'Associated items', $count), $count);
            } else {
                $tabs[2] = _n('Associated item', 'Associated items', $count);
            }
            $tabs[3] = __('CSV import', 'glpiinventory');
            return $tabs;
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
        switch ($tabnum) {
            case 1:
                self::showCriteriaAndSearch($item);
                return true;

            case 2:
                self::showResults();
                return true;

            case 3:
                self::csvImportForm($item);
                return true;
        }
        return false;
    }


   /**
    * Display criteria form + list of computers
    *
    * @param object $item PluginGlpiinventoryDeployGroup instance
    */
    public static function showCriteriaAndSearch(PluginGlpiinventoryDeployGroup $item)
    {

        echo "<div class='alert alert-primary d-flex align-items-center' role='alert'>";
        echo "<i class='fas fa-info-circle fa-xl'></i>";
        echo "<span class='ms-2'>";
        echo sprintf(
            __('Make a search to get desired computer, then use massive actions and use %s', 'glpiinventory'),
            '<strong>' . __('Add to static group', 'glpiinventory') . '</strong>'
        );
        echo "</span>";
        echo "</div>";

       // WITH checking post values
        $search_params = PluginGlpiinventoryDeployGroup::getSearchParamsAsAnArray($item, true);
       //If metacriteria array is empty, remove it as it displays the metacriteria form,
       //and it's is not we want !
        if (isset($search_params['metacriteria']) && empty($search_params['metacriteria'])) {
            unset($search_params['metacriteria']);
        }
        PluginGlpiinventoryDeployGroup::showCriteria($item, $search_params);

       //Add extra parameters for massive action display : only the Add action should be displayed
        $search_params['massiveactionparams']['extraparams']['id']                    = $item->getID();
        $search_params['massiveactionparams']['extraparams']['custom_action']         = 'add_to_group';
        $search_params['massiveactionparams']['extraparams']['massive_action_fields'] = ['action', 'id'];

        $data = Search::prepareDatasForSearch('Computer', $search_params);
        $data['itemtype'] = 'Computer';
        Search::constructSQL($data);

       // Use our specific constructDatas function rather than Glpi function
        PluginGlpiinventorySearch::constructDatas($data);
        $data['search']['target'] = PluginGlpiinventoryDeployGroup::getSearchEngineTargetURL($item->getID(), false);
        $data['itemtype'] = 'Computer';
        Search::displayData($data);
    }


   /**
    * Display result, so list of computers
    */
    public static function showResults()
    {
        if (
            isset($_SESSION['glpisearch']['Computer'])
              && isset($_SESSION['glpisearch']['Computer']['show_results'])
        ) {
            $computers_params = $_SESSION['glpisearch']['Computer'];
        }
        $computers_params['metacriteria'] = [];
        $computers_params['criteria'][]   = ['searchtype' => 'equals',
                                                'value' => $_GET['id'],
                                                'field' => 5171];

        $search_params = Search::manageParams('Computer', $computers_params);

       //Add extra parameters for massive action display : only the Delete action should be displayed
        $search_params['massiveactionparams']['extraparams']['id'] = $_GET['id'];
        $search_params['massiveactionparams']['extraparams']['custom_action'] = 'delete_from_group';
        $search_params['massiveactionparams']['extraparams']['massive_action_fields'] = ['action', 'id'];
        $data = Search::prepareDatasForSearch('Computer', $search_params);

        $data['itemtype'] = 'Computer';
        Search::constructSQL($data);

       // Use our specific constructDatas function rather than Glpi function
        PluginGlpiinventorySearch::constructDatas($data);
        $data['search']['target'] = PluginGlpiinventoryDeployGroup::getSearchEngineTargetURL($_GET['id'], false);
        $data['itemtype'] = 'Computer';
        Search::displayData($data);
    }


   /**
   * Duplicate entries from one group to another
   * @param $source_deploygroups_id the source group ID
   * @param $target_deploygroups_id the target group ID
   * @return the duplication status, as a boolean
   */
    public static function duplicate($source_deploygroups_id, $target_deploygroups_id)
    {
        $result        = true;
        $pfStaticGroup = new self();

        $groups = $pfStaticGroup->find(['plugin_glpiinventory_deploygroups_id' => $source_deploygroups_id]);
        foreach ($groups as $group) {
            unset($group['id']);
            $group['plugin_glpiinventory_deploygroups_id']
            = $target_deploygroups_id;
            if (!$pfStaticGroup->add($group)) {
                $result |= false;
            }
        }
        return $result;
    }


   /**
    * Form to import computers ID in CSV file
    *
    * @since 9.2+2.0
    *
    * @param object $item it's an instance of PluginGlpiinventoryDeployGroup class
    *
    * @return boolean
    */
    public static function csvImportForm(PluginGlpiinventoryDeployGroup $item)
    {

        echo "<form action='' method='post' enctype='multipart/form-data'>";

        echo "<br>";
        echo "<table class='tab_cadre_fixe' cellpadding='1' width='600'>";
        echo "<tr>";
        echo "<th>";
        echo __('Import a list of computers from a CSV file (the first column must contain the computer ID)', 'glpiinventory') . " :";
        echo "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td align='center'>";
        echo Html::hidden('groups_id', ['value' => $item->getID()]);
        echo "<input type='file' name='importcsvfile' value=''/>";
        echo "&nbsp;" . Html::submit(__('Import'));
        ;
        echo "</td>";
        echo "</tr>";

        echo "</table>";

        Html::closeForm();
        return true;
    }


   /**
    * Import into DB the computers ID
    *
    * @since 9.2+2.0
    *
    * @param array $post_data
    * @param array $files_data array with information of $_FILE
    *
    * @return boolean
    */
    public static function csvImport($post_data, $files_data)
    {
        $pfDeployGroup_static = new self();
        $computer = new Computer();
        $input = [
         'plugin_glpiinventory_deploygroups_id' => $post_data['groups_id'],
         'itemtype' => 'Computer'
        ];
        if (isset($files_data['importcsvfile']['tmp_name'])) {
            if (($handle = fopen($files_data['importcsvfile']['tmp_name'], "r")) !== false) {
                while (($data = fgetcsv($handle, 1000, $_SESSION["glpicsv_delimiter"])) !== false) {
                    $input['items_id'] = str_replace(' ', '', $data[0]);
                    if ($computer->getFromDB($input['items_id'])) {
                        $pfDeployGroup_static->add($input);
                    }
                }
                Session::addMessageAfterRedirect(__('Computers imported successfully from CSV file', 'glpiinventory'), false, INFO);
                fclose($handle);
            } else {
                Session::addMessageAfterRedirect(__('Impossible to read the CSV file', 'glpiinventory'), false, ERROR);
                return false;
            }
        } else {
            Session::addMessageAfterRedirect(sprintf(__('%1$s %2$s'), "File not found", $files_data['importcsvfile']['tmp_name']), false, ERROR);
            return false;
        }
        return true;
    }
}
