<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * @basedon   FusionInventory for GLPI
 * @copyright 2021-2026 Teclib' and contributors.
 * @copyright 2010-2021 by the FusionInventory Development Team.
 *
 * http://glpi-project.org
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

use Safe\Exceptions\FilesystemException;

use function Safe\fclose;
use function Safe\fgetcsv;
use function Safe\fopen;

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
    public static $itemtype_1 = PluginGlpiinventoryDeployGroup::class;

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
     * @param CommonGLPI $item the item object
     * @param int $withtemplate 1 if is a template form
     * @return string|array<string> name of the tab
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (
            !$withtemplate
            && ($item instanceof PluginGlpiinventoryDeployGroup)
             && $item->fields['type'] == PluginGlpiinventoryDeployGroup::STATIC_GROUP
        ) {
            $tabs[1] = self::createTabEntry(_n('Criterion', 'Criteria', 2), 0, icon: 'ti ti-file-search');
            $count = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $count = countElementsInTable(
                    getTableForItemType(self::class),
                    [
                        'plugin_glpiinventory_deploygroups_id' => $item->fields['id'],
                    ]
                );
            }
            $tabs[2] = self::createTabEntry(_n('Associated item', 'Associated items', $count), $count, icon: 'ti ti-list');
            $tabs[3] = self::createTabEntry(__('CSV import', 'glpiinventory'), 0, icon: 'ti ti-csv');
            return $tabs;
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
        /** @var PluginGlpiinventoryDeployGroup $item */
        switch ($tabnum) {
            case 1:
                self::showCriteriaAndSearch($item);
                return true;

            case 2:
                self::showResults($item);
                return true;

            case 3:
                self::csvImportForm($item);
                return true;
        }
        return false;
    }


    /**
     * Display criteria form + list of items
     *
     * @param PluginGlpiinventoryDeployGroup $item PluginGlpiinventoryDeployGroup instance
     */
    public static function showCriteriaAndSearch(PluginGlpiinventoryDeployGroup $item): void
    {
        $itemtype = $item->getGroupItemtype();

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
        $search_params['massiveactionparams']['extraparams']['specific_actions'][self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'add'] = __('Add to static group', 'glpiinventory');
        $search_params['massiveactionparams']['extraparams']['massive_action_fields'] = ['action', 'id'];

        $limit_backup = $_SESSION['glpilist_limit'];
        $_SESSION['glpilist_limit'] = 200;
        $data = Search::prepareDatasForSearch($itemtype, $search_params);
        Search::constructSQL($data);
        Search::constructData($data);
        $data['search']['target'] = PluginGlpiinventoryDeployGroup::getSearchEngineTargetURL($item->getID(), false);
        Search::displayData($data);
        $_SESSION['glpilist_limit'] = $limit_backup;

        //remove trashbin switch
        echo Html::scriptBlock("
            $(document).ready(
                function() {
                    $('label.form-switch').hide();
                    $('#dropdown-export').hide();
                    $('button.show_displaypreference_modal').hide();
                    $('form[id^=\"massform" . str_replace('\\', '', $itemtype) . "\"]').find('table:first').removeClass('search-results');
                    $('span.search-limit').html('');
                }
            );
        ");
    }


    /**
     * Display result, so list of computers
     */
    public static function showResults(PluginGlpiinventoryDeployGroup $item): void
    {
        /** @var DBmysql $DB */
        global $DB;
        $rand = random_int(0, mt_getrandmax());

        $params = [
            'SELECT' => '*',
            'FROM'   => self::getTable(),
            'WHERE'  => ['plugin_glpiinventory_deploygroups_id' => $item->getID()],
        ];

        $datas = [];
        $iterator = $DB->request($params);
        foreach ($iterator as $data) {
            $datas[] = $data;
        }
        $number = count($datas);

        echo "<div class='spaced'>";
        echo "<div class='spaced'>";

        $mass_class = str_replace('\\', '', self::class);
        Html::openMassiveActionsForm('mass' . $mass_class . $rand);
        $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $number),
            'item' => $item,
            'specific_actions' => [self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'deleteitem' => _x('button', __('Remove from static group', 'glpiinventory'))],
            'container' => 'mass' . $mass_class . $rand,
            'massive_action_fields' => ['action', 'id'],
        ];
        Html::showMassiveActions($massiveactionparams);

        echo "<table class='tab_cadre_fixehov'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';

        $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . $mass_class . $rand);
        $header_top    .= "</th>";
        $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . $mass_class . $rand);
        $header_bottom .=  "</th>";

        $header_end .= "<th>" . __('Name') . "</th>";
        $header_end .= "<th>" . _n('Item type', 'Item types', 1) . "</th>";
        $header_end .= "<th>" . __('Automatic inventory') . "</th>";
        $header_end .= "<th>" . Entity::getTypeName(1) . "</th>";
        $header_end .= "<th>" . __('Serial number') . "</th>";
        $header_end .= "<th>" . __('Inventory number') . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        foreach ($datas as $data) {
            $target = getItemForItemtype($data["itemtype"]);
            if ($target === false || !$target->getFromDB($data["items_id"])) {
                continue;
            }
            $linkname = $target->fields["name"];
            if ($_SESSION["glpiis_ids_visible"] || empty($target->fields["name"])) {
                $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $target->fields["id"]);
            }
            $link = $target::getFormURLWithID($target->fields["id"]);
            $name = "<a href=\"" . $link . "\">" . $linkname . "</a>";
            echo "<tr class='tab_bg_1'>";

            echo "<td width='10'>";
            Html::showMassiveActionCheckBox($data["itemtype"], $data["items_id"]);
            echo "</td>";

            echo "<td "
                . ((isset($target->fields['is_deleted']) && $target->fields['is_deleted']) ? "class='tab_bg_2_2'" : "")
                . ">" . $name . "</td>";
            echo "<td>" . $target::getTypeName(1) . "</td>";
            echo "<td>" . Dropdown::getYesNo($target->fields['is_dynamic']) . "</td>";
            echo "<td>" . Dropdown::getDropdownName(
                "glpi_entities",
                $target->fields['entities_id']
            );
            echo "</td>";
            echo "<td>"
                    . (isset($target->fields["serial"]) ? "" . $target->fields["serial"] . "" : "-") . "</td>";
            echo "<td>"
                    . (isset($target->fields["otherserial"]) ? "" . $target->fields["otherserial"] . "" : "-") . "</td>";
            echo "</tr>";
        }
        echo $header_begin . $header_bottom . $header_end;

        echo "</table>";
        if ($number) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
    }


    /**
    * Duplicate entries from one group to another
    * @param int $source_deploygroups_id the source group ID
    * @param int $target_deploygroups_id the target group ID
    * @return bool the duplication status
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
     * @param PluginGlpiinventoryDeployGroup $item it's an instance of PluginGlpiinventoryDeployGroup class
     *
     * @return bool
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
     * @param array<string,mixed> $post_data
     * @param array<string,mixed> $files_data array with information of $_FILE
     *
     * @return bool
     */
    public static function csvImport($post_data, $files_data)
    {
        $pfDeployGroup_static = new self();
        $itemtype = PluginGlpiinventoryDeployGroup::getItemtypeForGroup((int) $post_data['groups_id']);
        $item     = getItemForItemtype($itemtype);
        if ($item === false) {
            return false;
        }
        $input = [
            'plugin_glpiinventory_deploygroups_id' => $post_data['groups_id'],
            'itemtype' => $itemtype,
        ];
        if (isset($files_data['importcsvfile']['tmp_name'])) {
            try {
                $handle = fopen($files_data['importcsvfile']['tmp_name'], "r");
                while (($data = fgetcsv($handle, 1000, $_SESSION["glpicsv_delimiter"], '"', '')) !== false) {
                    $input['items_id'] = (int) str_replace(' ', '', $data[0]);
                    if ($item->getFromDB($input['items_id'])) {
                        $pfDeployGroup_static->add($input);
                    }
                }
                Session::addMessageAfterRedirect(__('Computers imported successfully from CSV file', 'glpiinventory'), false, INFO);
                fclose($handle);
            } catch (FilesystemException $e) {
                Session::addMessageAfterRedirect(__('Impossible to read the CSV file', 'glpiinventory'), false, ERROR);
                return false;
            }
        } else {
            Session::addMessageAfterRedirect(sprintf(__('%1$s %2$s'), "File not found", $files_data['importcsvfile']['tmp_name']), false, ERROR);
            return false;
        }
        return true;
    }


    /**
     * Execution code for massive action
     *
     * @param MassiveAction $ma MassiveAction instance
     * @param CommonDBTM $item item on which execute the code
     * @param array<int> $ids list of ID on which execute the code
     *
     * @return void
     */
    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {

        $group_item = new self();
        switch ($ma->getAction()) {
            case 'add':
                foreach ($ids as $key) {
                    if (!$item->can($key, UPDATE)) {
                        $ma->itemDone($item::class, $key, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        continue;
                    }
                    $values = [
                        'plugin_glpiinventory_deploygroups_id' => $_POST['id'],
                        'itemtype'                             => $item::class,
                        'items_id'                             => $key,
                    ];
                    if (!countElementsInTable($group_item->getTable(), $values)) {
                        $group_item->add($values);
                        $ma->itemDone($item::class, $key, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item::class, $key, MassiveAction::ACTION_KO);
                    }
                }
                return;

            case 'deleteitem':
                foreach ($ids as $key) {
                    if (
                        $group_item->deleteByCriteria([
                            'items_id' => $key,
                            'itemtype' => $item::class,
                            'plugin_glpiinventory_deploygroups_id' => $_POST['item_items_id'],
                        ])
                    ) {
                        $ma->itemDone($item::class, $key, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item::class, $key, MassiveAction::ACTION_KO);
                    }
                }
        }
    }


    /**
     * Display form related to the massive action selected
     *
     * @param MassiveAction $ma MassiveAction instance
     * @return bool
     */
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        if ($ma->getAction() == 'add') {
            echo "<br><br>" . Html::submit(
                _x('button', 'Add'),
                ['name' => 'massiveaction']
            );
            return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }
}
