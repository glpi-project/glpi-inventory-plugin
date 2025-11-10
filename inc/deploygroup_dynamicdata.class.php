<?php

use Safe\Exceptions\JsonException;

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

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * Manage the dynamic groups (based on search engine of GLPI).
 */
class PluginGlpiinventoryDeployGroup_Dynamicdata extends CommonDBChild
{
    /**
     * The right name for this class
     *
     * @var string
     */
    public static $rightname = "plugin_glpiinventory_group";

    /**
     * Itemtype of the item linked
     *
     * @var string
     */
    public static $itemtype = PluginGlpiinventoryDeployGroup::class;

    /**
     * id field of the item linked
     *
     * @var string
     */
    public static $items_id = 'plugin_glpiinventory_deploygroups_id';


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        /** @var PluginGlpiinventoryDeployGroup $item */
        if (
            !$withtemplate
            && $item->fields['type'] == PluginGlpiinventoryDeployGroup::DYNAMIC_GROUP
        ) {
            $tabs[1] = self::createTabEntry(_n('Criterion', 'Criteria', Session::getPluralNumber()), 0, icon: 'ti ti-file-search');
            $count = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                // Get the count of matching items
                $count = self::getMatchingItemsCount($item);
            }
            $tabs[2] = self::createTabEntry(_n('Associated item', 'Associated items', $count), $count, icon: 'ti ti-list');

            return $tabs;
        }
        return '';
    }


    /**
     * Get the count of items matching the dynamic search criteria
     *
     * This function saves and restores the pagination parameters to avoid breaking the pagination in the
     * query results.
     *
     * @param PluginGlpiinventoryDeployGroup $item the item object
     * @return int
     */
    public function getMatchingItemsCount(PluginGlpiinventoryDeployGroup $item)
    {
        // It's necessary to do a backup of $_SESSION['glpisearch']['Computer']
        // to isolate the search performed in the dynamic group,
        // otherwise the search will be reused by GLPI in the computer list (cf.$_SESSION['glpisearch']['Computer'])
        $backup_criteria = [];
        if (isset($_SESSION['glpisearch'][Computer::class])) {
            $backup_criteria = $_SESSION['glpisearch'][Computer::class];
        }

        $params = PluginGlpiinventoryDeployGroup::getSearchParamsAsAnArray($item, false);
        $params['massiveactionparams']['extraparams']['id'] = $_GET['id'];
        if (isset($params['metacriteria']) && !is_array($params['metacriteria'])) {
            $params['metacriteria'] = [];
        }
        $params['target'] = PluginGlpiinventoryDeployGroup::getSearchEngineTargetURL($_GET['id'], true);

        $data = Search::prepareDatasForSearch(Computer::class, $params);
        Search::constructSQL($data);
        Search::constructData($data);

        $_SESSION['glpisearch'][Computer::class] = $backup_criteria;

        return $data['data']['totalcount'];
    }



    /**
     * Display the content of the tab
     *
     * @param CommonGLPI $item
     * @param integer $tabnum number of the tab to display
     * @param integer $withtemplate 1 if is a template form
     * @return boolean
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        // It's necessary to do a backup of $_SESSION['glpisearch']['Computer']
        // to isolate the search performed in the dynamic group,
        // otherwise the search will be reused by GLPI in the computer list (cf.$_SESSION['glpisearch']['Computer'])
        $backup_criteria = [];
        if (isset($_SESSION['glpisearch'][Computer::class])) {
            $backup_criteria = $_SESSION['glpisearch'][Computer::class];
        }

        /** @var PluginGlpiinventoryDeployGroup $item */
        switch ($tabnum) {
            case 1:
                self::showCriteriaAndSearch($item);
                //restore session data
                $_SESSION['glpisearch'][Computer::class] = $backup_criteria;
                return true;

            case 2:
                $pagination_params = [];
                foreach (['sort', 'order', 'start'] as $field) {
                    if (isset($_SESSION['glpisearch'][Computer::class][$field])) {
                        $pagination_params[$field] = $_SESSION['glpisearch'][Computer::class][$field];
                    }
                }
                $params = PluginGlpiinventoryDeployGroup::getSearchParamsAsAnArray($item, false);
                $params['massiveactionparams']['extraparams']['id'] = $_GET['id'];
                // Include pagination parameters in the provided parameters
                foreach ($pagination_params as $key => $value) {
                    $params[$key] = $value;
                }
                if (isset($params['metacriteria']) && !is_array($params['metacriteria'])) {
                    $params['metacriteria'] = [];
                }
                $params['target'] = PluginGlpiinventoryDeployGroup::getSearchEngineTargetURL($_GET['id'], true);
                self::showList(Computer::class, $params, []);
                //restore session data
                $_SESSION['glpisearch'][Computer::class] = $backup_criteria;
                return true;
        }
        return false;
    }


    /**
     * Display criteria form + list of computers
     *
     * @param PluginGlpiinventoryDeployGroup $item PluginGlpiinventoryDeployGroup instance
     *
     * @return void
     */
    public static function showCriteriaAndSearch(PluginGlpiinventoryDeployGroup $item)
    {
        // Save pagination parameters
        $pagination_params = [];
        foreach (['sort', 'order', 'start'] as $field) {
            if (isset($_SESSION['glpisearch'][Computer::class][$field])) {
                $pagination_params[$field] = $_SESSION['glpisearch'][Computer::class][$field];
            }
        }
        // WITHOUT checking post values
        $search_params = PluginGlpiinventoryDeployGroup::getSearchParamsAsAnArray($item, false);
        //If metacriteria array is empty, remove it as it displays the metacriteria form,
        //and it is not we want !
        unset($search_params['reset']);

        $_SESSION['glpisearch'][Computer::class]['criteria'] = $search_params['criteria'];

        if (isset($search_params['metacriteria']) && empty($search_params['metacriteria'])) {
            unset($search_params['metacriteria']);
        }

        echo '<div class="search_page row">';
        echo '<div class="col search-container">';
        PluginGlpiinventoryDeployGroup::showCriteria($item, $search_params);
        echo '</div>';
        echo '</div>';
    }


    /**
     * Display list of computers in the group
     *
     * @param string $itemtype
     * @param array $params
     * @param array $forcedisplay
     */
    public static function showList($itemtype, $params, $forcedisplay)
    {
        $data = Search::prepareDatasForSearch(Computer::class, $params, $forcedisplay);
        Search::constructSQL($data);
        Search::constructData($data);

        echo "<div class='search_page row'>";
        echo "<div class='search-container w-100 disable-overflow-y' counter='" . (int) $data['data']['count'] . "'>";
        Search::displayData($data);
        echo "</div></div>";
    }


    /**
     * Get data, so computer list
     *
     * @param string $itemtype
     * @param array $params
     * @param array $forcedisplay
     * @return array
     */
    public static function getDatas($itemtype, $params, array $forcedisplay = [])
    {
        $data = Search::prepareDatasForSearch(Computer::class, $params, $forcedisplay);
        Search::constructSQL($data);
        Search::constructData($data);

        return $data;
    }


    /**
     * Get computers belonging to a dynamic group
     *
     * @since 0.85+1.0
     *
     * @param PluginGlpiinventoryDeployGroup $group the group object
     * @param boolean $use_cache retrieve computers_id from cache (computers_id_cache field)
     * @return array of computer ids
     */
    public static function getTargetsByGroup(PluginGlpiinventoryDeployGroup $group, $use_cache = false)
    {
        $ids = [];

        if (!$use_cache || !$ids = self::retrieveCache($group)) {
            $ids = [];

            $search_params = PluginGlpiinventoryDeployGroup::getSearchParamsAsAnArray($group, false, true);
            if (isset($search_params['metacriteria']) && empty($search_params['metacriteria'])) {
                unset($search_params['metacriteria']);
            }

            //force no sort (Search engine will sort by id) for better performance
            $search_params['sort'] = '';

            //Only retrieve computers IDs
            $results = self::getDatas(
                Computer::class,
                $search_params,
                ['2']
            );


            foreach ($results['data']['rows'] as $id => $row) {
                $ids[$row['id']] = $row['id'];
            }

            //store results in cache (for reusing on agent communication)
            self::storeCache($group, $ids);
        }

        return $ids;
    }


    /**
     * Store a set of computers id in db
     * @param  PluginGlpiinventoryDeployGroup $group the instance of fi group
     * @param  array                            $ids   the list of id to store
     * @return bool
     */
    public static function storeCache(PluginGlpiinventoryDeployGroup $group, $ids = [])
    {
        /** @var DBmysql $DB */
        global $DB;

        $result = $DB->update(
            self::getTable(),
            [
                'computers_id_cache' => $DB->escape(json_encode($ids)),
            ],
            [
                'plugin_glpiinventory_deploygroups_id' => $group->getID(),
            ]
        );
        return $result;
    }


    /**
     * Retrieve the id of computer stored in db for a group
     * @param  PluginGlpiinventoryDeployGroup $group the instance of the group
     * @return array                            the list of compuers id
     */
    public static function retrieveCache(PluginGlpiinventoryDeployGroup $group)
    {
        $ids  = false;
        $data = getAllDataFromTable(
            self::getTable(),
            ['plugin_glpiinventory_deploygroups_id' => $group->getID()]
        );
        if (count($data)) {
            $first = array_shift($data);
            try {
                $ids = json_decode($first['computers_id_cache'], true);
            } catch (JsonException $e) {
                //empty catch
            }
        }

        return $ids;
    }


    /**
    * Duplicate entries from one group to another
    * @param integer $source_deploygroups_id the source group ID
    * @param integer $target_deploygroups_id the target group ID
    * @return boolean the duplication status
    */
    public static function duplicate($source_deploygroups_id, $target_deploygroups_id)
    {
        $result         = true;
        $pfDynamicGroup = new self();

        $groups = $pfDynamicGroup->find(['plugin_glpiinventory_deploygroups_id' => $source_deploygroups_id]);
        foreach ($groups as $group) {
            unset($group['id']);
            $group['plugin_glpiinventory_deploygroups_id']
            = $target_deploygroups_id;
            if (!$pfDynamicGroup->add($group)) {
                $result = false;
            }
        }
        return $result;
    }
}
