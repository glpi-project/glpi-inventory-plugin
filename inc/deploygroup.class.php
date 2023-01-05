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
 * Manage the deploy groups.
 */
class PluginGlpiinventoryDeployGroup extends CommonDBTM
{
   /**
    * Define constant name of static group
    *
    * @var string
    */
    const STATIC_GROUP  = 'STATIC';

   /**
    * Define constant name of dynamic group
    *
    * @var string
    */
    const DYNAMIC_GROUP = 'DYNAMIC';

   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = "plugin_glpiinventory_group";

   /**
    * Define the array of itemtype allowed in static groups
    *
    * @var type
    */
    protected $static_group_types = ['Computer'];

   /**
    * We activate the history.
    *
    * @var boolean
    */
    public $dohistory = true;


   /**
    * __contruct function used to define the 2 types of groups
    */
    public function __construct()
    {
        $this->grouptypes = [
            self::STATIC_GROUP  => __('Static group', 'glpiinventory'),
            self::DYNAMIC_GROUP => __('Dynamic group', 'glpiinventory')
        ];
    }


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
    public static function getTypeName($nb = 0)
    {
        return __('Inventory group', 'glpiinventory');
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

        $count = self::getMatchingItemsCount("PluginGlpiinventoryTaskjob");
        $ong[$this->getType() . '$task'] = self::createTabEntry(_n('Associated task', 'Associated tasks', $count), $count);

        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }



    public function getMatchingItemsCount($itemtype)
    {
        $count = 0;
        if (
            $itemtype == 'PluginGlpiinventoryTaskjob'
            && is_numeric($_GET['id'])
        ) {
            $pfTaskjob = new PluginGlpiinventoryTaskjob();
            $data = $pfTaskjob->find(['actors' => ['LIKE', '%"PluginGlpiinventoryDeployGroup":"' . $_GET['id'] . '"%']]);
            $count = count($data);
        }
        return $count;
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
        global $DB;

        if ($tabnum == 'task') {
            echo "<table width='950' class='tab_cadre_fixe'>";

            echo "<tr>";
            echo "<th>";
            echo __('Task');
            echo "</th>";
            echo "<th>";
            echo __('Active');
            echo "</th>";
            echo "<th>";
            echo __('Module method');
            echo "</th>";
            echo "</tr>";

            $modules_methods = PluginGlpiinventoryStaticmisc::getModulesMethods();
            $link = Toolbox::getItemTypeFormURL("PluginGlpiinventoryTask");

            $query = "SELECT
            glpi_plugin_glpiinventory_tasks.id as id,
            glpi_plugin_glpiinventory_tasks.name as tname,
            glpi_plugin_glpiinventory_tasks.is_active,
            glpi_plugin_glpiinventory_taskjobs.method
            FROM glpi_plugin_glpiinventory_taskjobs
            LEFT JOIN glpi_plugin_glpiinventory_tasks on plugin_glpiinventory_tasks_id=glpi_plugin_glpiinventory_tasks.id
            WHERE `actors` LIKE '%\"PluginGlpiinventoryDeployGroup\":\"" . $_GET['id'] . "\"%'
            ORDER BY glpi_plugin_glpiinventory_tasks.name";
            $res = $DB->query($query);

            while ($row = $DB->fetchAssoc($res)) {
                echo "<tr class='tab_bg_1'>";
                echo "<td>";
                echo "<a href='" . $link . "?id=" . $row['id'] . "'>" . $row['tname'] . "</a>";
                echo "</td>";
                echo "<td>";
                echo Dropdown::getYesNo($row['is_active']);
                echo "</td>";
                echo "<td>";
                echo $modules_methods[$row['method']];
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
            return true;
        }
        return false;
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
        $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'targettask'] = __('Target a task', 'glpiinventory');
        $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'duplicate']  = _sx('button', 'Duplicate');
        return $actions;
    }


   /**
    * Display form related to the massive action selected
    *
    * @param object $ma MassiveAction instance
    * @return boolean
    */
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'add_to_static_group':
                Dropdown::show(
                    'PluginGlpiinventoryDeployGroup',
                    ['condition' => ['type' => PluginGlpiinventoryDeployGroup::STATIC_GROUP]]
                );
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
            case 'duplicate':
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
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
        switch ($ma->getAction()) {
            case 'add_to_static_group':
                if ($item->getType() == 'Computer') {
                    $group_item = new PluginGlpiinventoryDeployGroup_Staticdata();
                    foreach ($ids as $id) {
                        if (
                            !countElementsInTable(
                                $group_item->getTable(),
                                [
                                'plugin_glpiinventory_deploygroups_id' => $_POST['plugin_glpiinventory_deploygroups_id'],
                                'itemtype'                               => 'Computer',
                                'items_id'                               => $id,
                                ]
                            )
                        ) {
                            $values = [
                            'plugin_glpiinventory_deploygroups_id' => $_POST['plugin_glpiinventory_deploygroups_id'],
                            'itemtype' => 'Computer',
                            'items_id' => $id];
                            $group_item->add($values);
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        }
                    }
                }
                break;
            case 'duplicate':
                $pfGroup = new self();
                foreach ($ids as $key) {
                    if ($pfGroup->getFromDB($key)) {
                        if ($pfGroup->duplicate($pfGroup->getID())) {
                            //set action massive ok for this item
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                        } else {
                          // KO
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        }
                    }
                }
                break;
            default:
                parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
                break;
        }
    }


    public function duplicate($deploygroups_id)
    {
        $result = true;
        if ($this->getFromDB($deploygroups_id)) {
            $input = $this->fields;
            unset($input['id']);
            $input['name'] = sprintf(__('Copy of %s'), $this->fields['name']);
            $new_deploygroups_id = $this->add($input);
            if ($new_deploygroups_id) {
                if ($this->fields['type'] == self::STATIC_GROUP) {
                    $result
                    = PluginGlpiinventoryDeployGroup_Staticdata::duplicate($deploygroups_id, $new_deploygroups_id);
                } else {
                    $result
                    = PluginGlpiinventoryDeployGroup_Dynamicdata::duplicate($deploygroups_id, $new_deploygroups_id);
                }
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
    }


   /**
    * Display title of the page
    *
    * @global array $CFG_GLPI
    */
    public function title()
    {
        global $CFG_GLPI;

        $buttons = [];
        $title   = self::getTypeName();

        if ($this->canCreate()) {
            $buttons["group.form.php?new=1"] = __('Add group', 'glpiinventory');
            $title = "";
        }
        Html::displayTitle(
            $CFG_GLPI['root_doc'] . "/plugins/fusinvdeploy/pics/menu_group.png",
            $title,
            $title,
            $buttons
        );
    }


   /**
    * Display form
    *
    * @param integer $ID
    * @param array $options
    * @return true
    */
    public function showForm($ID, array $options = [])
    {

        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "&nbsp;:</td>";
        echo "<td align='center'>";
        echo Html::input('name', ['size' => 40, 'value' => $this->fields['name']]);
        echo "</td>";

        echo "<td rowspan='2'>" . __('Comments') . "&nbsp;:</td>";
        echo "<td rowspan='2' align='center'>";
        echo "<textarea cols='40' class='form-control' name='comment' >" . $this->fields["comment"] . "</textarea>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Type') . "&nbsp;:</td>";
        echo "<td align='center'>";
        self::dropdownGroupType('type', $this->fields['type']);
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);
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
         'massiveaction' => false
        ];

        $tab[] = [
         'id'            => '2',
         'table'         => $this->getTable(),
         'field'         => 'type',
         'name'          => __('Type'),
         'datatype'      => 'specific',
         'massiveaction' => false,
         'searchtype'    => 'equals',
        ];

        return $tab;
    }


   /**
    * Check if this group is a dynamic group or not
    *
    * @return boolean
    */
    public function isDynamicGroup()
    {
        return ($this->fields['type'] == self::DYNAMIC_GROUP);
    }


   /**
    * Check if this group is a static group or not
    *
    * @return boolean
    */
    public function isStaticGroup()
    {
        return ($this->fields['type'] == self::STATIC_GROUP);
    }


   /**
    * Get a specific value to display
    *
    * @param string $field
    * @param array $values
    * @param array $options
    * @return string
    */
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        $group = new self();
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        if ($field == 'type') {
            return $group->grouptypes[$values[$field]];
        }
        return '';
    }


   /**
    * Display dropdown to select dynamic of static group
    *
    * @param string $name
    * @param string $value
    * @return string
    */
    public static function dropdownGroupType($name = 'type', $value = 'STATIC')
    {
        $group = new self();
        if ($name == 'type') {
            return Dropdown::showFromArray(
                $name,
                $group->grouptypes,
                ['value' => $value, 'display' => true]
            );
        } else {
            return Dropdown::showFromArray(
                $name,
                $group->grouptypes,
                ['value' => $value, 'display' => false]
            );
        }
    }


   /**
    * Get specific value to select
    *
    * @param string $field
    * @param string $name
    * @param string|array $values
    * @param array $options
    * @return string
    */
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        $options['display'] = false;
        if ($field == 'type') {
            return self::dropdownGroupType($name, $values[$field]);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


   /**
   * Get the URL to pass to the search engine
   * @since 9.2
   *
   * @param integer $deploygroup_id the ID of the group
   * @param boolean $is_dynamic is the group dynamic or static
   * @return string the target
   */
    public static function getSearchEngineTargetURL($deploygroup_id, $is_dynamic = false)
    {
        $target = PluginGlpiinventoryDeployGroup::getFormURLWithID($deploygroup_id);
        if ($is_dynamic) {
            $target .= "&_glpi_tab=PluginGlpiinventoryDeployGroup_Dynamicdata$1";
        } else {
            $target .= "&_glpi_tab=PluginGlpiinventoryDeployGroup_Staticdata$1";
        }
        $target .= "&plugin_glpiinventory_deploygroups_id=" . $deploygroup_id;
        return $target;
    }


   /**
    * Show criteria to search computers
    *
    * @param object $item PluginGlpiinventoryDeployGroup instance
    * @param array $p
    */
    public static function showCriteria(PluginGlpiinventoryDeployGroup $item, $p)
    {

        $is_dynamic = $item->isDynamicGroup();
        $itemtype   = "Computer";
        $can_update = $item->canEdit($item->getID());

        $p['target'] = self::getSearchEngineTargetURL($item->getID(), $is_dynamic);
        if ($can_update) {
            $p['addhidden'] = [
             'plugin_glpiinventory_deploygroups_id' => $item->getID(),
             'id'                                     => $item->getID(),
             'start'                                  => 0
            ];
        }
        if ($is_dynamic) {
            $p['actionname']   = 'save';
            $p['actionvalue']  = _sx('button', 'Save');
        } else {
            $p['actionname']   = 'preview';
            $p['actionvalue']  = __('Preview');
        }
        $p['showbookmark'] = false;

        $save_fold_status = $_SESSION['glpifold_search'];
        $_SESSION['glpifold_search'] = 0;
        Search::showGenericSearch($itemtype, $p);
        $_SESSION['glpifold_search'] = $save_fold_status;
    }


   /**
    * Get targets for the group
    *
    * @param integer $groups_id id of the group
    * @param bool    $use_cache retrieve agents from cache or not (only for dynamic groups)
    * @return array list of computers
    */
    public static function getTargetsForGroup($groups_id, $use_cache = false)
    {
        $group = new self();
        $group->getFromDB($groups_id);

        $results = [];
        if ($group->isStaticGroup()) {
            $staticgroup = new PluginGlpiinventoryDeployGroup_Staticdata();
            foreach (
                $staticgroup->find(
                    ['plugin_glpiinventory_deploygroups_id' => $groups_id,
                    'itemtype'                               => 'Computer']
                ) as $tmpgroup
            ) {
                $results[$tmpgroup['items_id']] = $tmpgroup['items_id'];
            }
        } else {
            $results = PluginGlpiinventoryDeployGroup_Dynamicdata::getTargetsByGroup(
                $group,
                $use_cache
            );
        }
        return $results;
    }


   /**
    * Get search parameters as an array
    *
    * @global object $DB
    * @param object $group PluginGlpiinventoryDeployGroup instance
    * @param boolean $check_post_values
    * @param boolean $getAll
    * @return array
    */
    public static function getSearchParamsAsAnArray(PluginGlpiinventoryDeployGroup $group, $check_post_values = false, $getAll = false)
    {
        global $DB;

        $computers_params = [];

       //Check criteria from DB
        if (!$check_post_values) {
            if ($group->fields['type'] == PluginGlpiinventoryDeployGroup::DYNAMIC_GROUP) {
                unset($_SESSION['glpisearch']['Computer']);
                $query = "SELECT `fields_array`
                     FROM `glpi_plugin_glpiinventory_deploygroups_dynamicdatas`
                     WHERE `plugin_glpiinventory_deploygroups_id`='" . $group->getID() . "'";
                $result = $DB->query($query);
                if ($DB->numrows($result) > 0) {
                    $fields_array     = $DB->result($result, 0, 'fields_array');
                    $computers_params = unserialize($fields_array);
                }
            }
        } else {
            if (
                $group->fields['type'] == PluginGlpiinventoryDeployGroup::STATIC_GROUP
                 && isset($_SESSION['glpisearch']['Computer'])
                 && !isset($_SESSION['glpisearch']['Computer']['show_results'])
            ) {
                $computers_params = $_SESSION['glpisearch']['Computer'];
            } else {
                unset($_SESSION['glpisearch']['Computer']);
                $computers_params = $_GET;
            }
        }
        if ($getAll) {
            $computers_params['export_all'] = true;
        }

        $computers_params["reset"] = true;
        return Search::manageParams('Computer', $computers_params, true, false);
    }


   /**
    * Clean when purge a deploy group
    */
    public function cleanDBOnPurge()
    {
        $dynamic_group = new PluginGlpiinventoryDeployGroup_Dynamicdata();
        $static_group  = new PluginGlpiinventoryDeployGroup_Staticdata();

        $params = ['plugin_glpiinventory_deploygroups_id' => $this->getID()];
        $dynamic_group->deleteByCriteria($params);
        $static_group->deleteByCriteria($params);
    }


   /**
    * Display for a computer the groups where it is
    *
    * @param integer $computers_id
    */
    public function showForComputer($computers_id)
    {
        global $DB;

        $canedit = PluginGlpiinventoryDeployGroup_Staticdata::canUpdate();
        if ($canedit) {
            $rand     = mt_rand();
            Html::openMassiveActionsForm('mass' . PluginGlpiinventoryDeployGroup_Staticdata::class . $rand);
            $massiveactionparams = [
                'num_displayed' => $_SESSION['glpilist_limit'],
                'container'     => 'mass' . PluginGlpiinventoryDeployGroup_Staticdata::class . $rand
            ];
            Html::showMassiveActions($massiveactionparams);
        }

        echo "<table width='950' class='tab_cadre_fixe'>";

        echo "<tr>";
        if ($canedit) {
            echo "<th width='10'>";
            echo Html::getCheckAllAsCheckbox('mass' . PluginGlpiinventoryDeployGroup_Staticdata::class . $rand);
            echo "</th>";
        }
        echo "<th>";
        echo __('Group');
        echo "</th>";
        echo "<th>";
        echo __('Type');
        echo "</th>";
        echo "</tr>";

        $link = Toolbox::getItemTypeFormURL("PluginGlpiinventoryDeployGroup");

        $iterator = $DB->request([
         'FROM'   => PluginGlpiinventoryDeployGroup_Staticdata::getTable(),
         'WHERE'  => [
            'items_id' => $computers_id,
            'itemtype' => 'Computer',
         ],
        ]);
        foreach ($iterator as $data) {
            $this->getFromDB($data['plugin_glpiinventory_deploygroups_id']);
            echo "<tr>";
            if ($canedit) {
                echo "<td width='10'>";
                Html::showMassiveActionCheckBox(PluginGlpiinventoryDeployGroup_Staticdata::class, $data["id"]);
                echo "</td>";
            }
            echo "<td>";
            echo "<a href='" . $link . "?id=" . $this->fields['id'] . "'>" . $this->fields['name'] . "</a>";
            echo "</td>";
            echo "<td>";
            echo __('Static group', 'glpiinventory');
            echo "</td>";
            echo "</tr>";
        }

        $iterator = $DB->request([
         'FROM'   => PluginGlpiinventoryDeployGroup_Dynamicdata::getTable(),
         'WHERE'  => [
            'computers_id_cache' => ["LIKE", '%"' . $computers_id . '"%'],
         ],
        ]);
        foreach ($iterator as $data) {
            $this->getFromDB($data['plugin_glpiinventory_deploygroups_id']);
            echo "<tr>";
            echo "<td>";
            echo "<a href='" . $link . "?id=" . $this->fields['id'] . "'>" . $this->fields['name'] . "</a>";
            echo "</td>";
            echo "<td>";
            echo __('Dynamic group', 'glpiinventory');
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
