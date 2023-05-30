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
    die("Sorry. You can't access this file directly");
}

/**
 * Manage the display of task jobs.
 */
class PluginGlpiinventoryTaskjobView extends PluginGlpiinventoryCommonView
{
   /**
    * __contruct function where initialize base URLs
    */
    public function __construct()
    {
        parent::__construct();
        $this->base_urls = array_merge($this->base_urls, [
         'fi.job.create' => $this->getBaseUrlFor('fi.ajax') . "/taskjob_form.php",
         'fi.job.edit' => $this->getBaseUrlFor('fi.ajax') . "/taskjob_form.php",
         'fi.job.moduletypes' => $this->getBaseUrlFor('fi.ajax') . "/taskjob_moduletypes.php",
         'fi.job.moduleitems' => $this->getBaseUrlFor('fi.ajax') . "/taskjob_moduleitems.php",
        ]);
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
        $tab_names = [];
        if ($item->fields['id'] > 0 and $this->can('task', READ)) {
            return __('Job configuration', 'glpiinventory');
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

        $pfTaskJob = new PluginGlpiinventoryTaskjob();

        if ($item->fields['id'] > 0) {
            if ($item->getType() == 'PluginGlpiinventoryTask') {
                //keep this code for multi task job if reintroduced
                //echo "<div id='taskjobs_form'>";
                //echo "</div>";
                //echo "<div id='taskjobs_list' class='tab_cadre_fixe'>";
                //$pfTaskJob->showListForTask($item->fields['id']);
                //echo "</div>";

                //display the unique job attached to task if needed
                $taskjobs = $pfTaskJob->getTaskjobs($item->fields['id']);
                $taskjob_id = 0;
                if (count($taskjobs)) {
                    $taskjob_id = reset($taskjobs)['id'];
                }
                $pfTaskJob->showForm($taskjob_id, ['task_id' => $item->fields['id']]);

                return true;
            }
        }
        return false;
    }


   /**
    * Ajax load item
    *
    * @param array $options
    * @return integer
    */
    public function ajaxLoadItem($options)
    {
       /*
        * The following has been borrowed from Html::display() and CommonGLPI::showTabsContent().
        *
        * TODO: maybe this can be shared through CommonView. -- Kevin Roy <kiniou@gmail.com>
        */

        if (
            isset($options['id'])
              and !$this->isNewID($options['id'])
        ) {
            if (!$this->getFromDB($options['id'])) {
                Html::displayNotFoundError();
            }
        }

       // for objects not in table like central
        $ID = 0;

        if (isset($this->fields['id'])) {
            $ID = $this->fields['id'];
        } else {
            if (isset($options['id'])) {
                $option_id = $options['id'];
               //Check for correct type of ID received from outside.
                if (
                    is_string($option_id)
                    and ctype_digit($option_id)
                ) {
                    $ID = (int)($options['id']);
                } elseif (is_int($option_id)) {
                    $ID = $option_id;
                } else {
                    trigger_error(
                        "Using default ID($ID) " .
                        "since we can't determine correctly the type of ID ('$option_id')"
                    );
                }
            }
        }
        return $ID;
    }


   /**
    * Get form in ajax
    *
    * @param array $options
    */
    public function ajaxGetForm($options)
    {
        $ID = $this->ajaxLoadItem($options);
        $this->showForm($ID, $options);
       // hide taskjobs_list after a job has been selected from the taskjobs_list
        echo Html::scriptBlock("$(document).ready(function() {
            $(\"#taskjobs_list\").hide();
         });");
    }


   /**
    * Get items list
    *
    * @param string $module_type
    * @return string
    */
    public function getItemsList($module_type)
    {
        $items = importArrayFromDB($this->fields[$module_type]);
        $result = [];
        foreach ($items as $item) {
            $itemtype = key($item);
            $itemid = $item[$itemtype];
            $result[] = $this->getItemDisplay($module_type, $itemtype, $itemid);
        }
        return implode("\n", $result);
    }


   /**
    * Get the html code for item to display
    *
    * @param string $module_type
    * @param string $itemtype
    * @param integer $items_id
    * @return string
    */
    public function getItemDisplay($module_type, $itemtype, $items_id)
    {
        $item = getItemForItemtype($itemtype);
        $item->getFromDB($items_id);
        $itemtype_name = $item->getTypeName();

        $item_fullid = $itemtype . '-' . $items_id;
        return "<div class='taskjob_item' id='$item_fullid'>
               " . Html::getCheckbox([]) . "
               <span class='" . $itemtype . "'></span>
               <label>
                  <span style='font-style:oblique'>" . $itemtype_name . "</span>
                  " . $item->getLink(['linkoption' => 'target="_blank"']) . "
               </label>
               <input type='hidden' name='" . $module_type . "[]' value='" . $item_fullid . "'>
               </input>
             </div>";
    }


   /**
    * Show jobs list for task
    *
    * @global array $CFG_GLPI
    * @param integer $task_id
    */
    public function showListForTask($task_id)
    {
        global $CFG_GLPI;

        $taskjobs = $this->getTaskjobs($task_id);

       // Check if cron GLPI running
        if (count($taskjobs) > 1) {
            $message = __('Several jobs in the same task is not anymore supported because of unexpected side-effects.
         Please consider modifying this task to avoid unexpected results.', 'glpiinventory');
            Html::displayTitle($CFG_GLPI['root_doc'] . "/pics/warning.png", $message, $message);
        }

       //Activate massive deletion if there are some.
        $addition_enabled = (count($taskjobs) == 0);

        echo "<form id='taskjobs_form' method='post' action='" . $this->getFormURL() . "'>";
        if ($addition_enabled) {
            echo "<div class='center'>";
            echo "<input type='button' class='submit taskjobs_create'" .
                " data-ajaxurl='" . $this->getBaseUrlFor('fi.job.create') . "'" .
                " data-task_id='$task_id' style='padding:5px;margin:0;right:0' " .
                " onclick='$(\"#taskjobs_list\").hide()'" .
                " value=' " . __('Add a job', 'glpiinventory') . " '/>";
            echo "</div>";
        } else {
            echo "<table class='tab_cadrehov package_item_list search-results table  card-table table-hover table-striped ' id='taskjobs_list'>\n";

           //Show list header only if a legacy task with more than one job was imported or not in editing mode
            $show_list = (count($taskjobs) > 1 || !isset($_REQUEST['edit_job']));
            if ($show_list) {
                echo "<thead><tr>";
                echo "<td>" .  Html::getCheckAllAsCheckbox("taskjobs_list", mt_rand()) . "</td>";
                echo "<td class='text-wrap'>" . __('Name') . "</td>";
                echo "<td class='text-wrap'>" . __('Comment') . "</td>";
                echo "<td></td>";
                echo "</tr></thead>";
            }

            foreach ($taskjobs as $taskjob_data) {
               //Keep row hidden when showing row is not required
                echo "<tr class='tab_bg_2'" . ($show_list ? "" : " hidden='true'") . ">\n";
                $this->showTaskjobSummary($taskjob_data);
                echo "</tr>\n";
            }

            echo "</table>\n";

           //Show the delete button for selected object when showing row is required
            if ($show_list) {
                echo "<div class='center' style='padding:5px'>";
                echo "<input type='submit' name='delete_taskjobs' value=\"" .
                __('Delete', 'glpiinventory') . "\" class='submit'>";
                echo "</div>";
            }
        }
        Html::closeForm();
    }


   /**
    * Get task jobs
    *
    * @param integer $task_id
    * @return array
    */
    public function getTaskjobs($task_id)
    {
       // Find taskjobs tied to the selected task
        $taskjobs = $this->find(
            ['plugin_glpiinventory_tasks_id' => $task_id,
             'rescheduled_taskjob_id'          => 0],
            ['id']
        );
        return $taskjobs;
    }


   /**
    * Show task job summary
    *
    * @param array $taskjob_data
    */
    public function showTaskjobSummary($taskjob_data)
    {
        $id = $taskjob_data['id'];
        $name = $taskjob_data['name'];
        $comment = $taskjob_data['comment'];
        if ($name == '') {
            $name = "($id)";
        }
        echo "<td class='control'>" .
               Html::getCheckbox(['name' => 'taskjobs[]', 'value' => $id]) . "
            </td>
            <td id='taskjob_" . $id . "' class='taskjob_block'>
               <a href='#taskjobs_form'
                  class='taskjobs_edit'
                  data-ajaxurl='" . $this->getBaseUrlFor('fi.job.edit') . "'
                  data-taskjob_id='$id'>
                  $name
               </a>
            </td>
            <td>" . $comment . "</td>
            <td class='rowhandler control'><div class='drag'/></td>";

        PluginGlpiinventoryToolbox::logIfExtradebug(
            "pluginGlpiinventory-tasks",
            "Task job edit : " . $this->getBaseUrlFor('fi.job.edit')
        );
        if (isset($_REQUEST['edit_job'])) {
            echo Html::scriptBlock("$(document).ready(function() {
            taskjobs.edit(
              '" . $this->getBaseUrlFor('fi.job.edit') . "',
              " . $_REQUEST['edit_job'] . "
            );
         });");
        }
    }


   /**
    * Display dropdown module types called in ajax
    *
    * @param array $options
    */
    public function ajaxModuleTypesDropdown($options)
    {

        switch ($options['moduletype']) {
            case 'actors':
                $title = __('Actor Type', 'glpiinventory');
                break;

            case 'targets':
                $title = __('Target Type', 'glpiinventory');
                break;
        }
       /**
        * get Itemtype choices dropdown
        */
        $module_types = array_merge(
            ['' => Dropdown::EMPTY_VALUE],
            $this->getTypesForModule($options['method'], $options['moduletype'])
        );
        $module_types_dropdown = $this->showDropdownFromArray(
            $title,
            null,
            $module_types
        );
        echo Html::scriptBlock("$(document).ready(function() {
         taskjobs.register_update_items(
            'dropdown_$module_types_dropdown',
            '" . $options['moduletype'] . "',
            '" . $this->getBaseUrlFor('fi.job.moduleitems') . "'
         );
      });");
    }


   /**
    * Display dropdown module items called in ajax
    *
    * @param array $options
    */
    public function ajaxModuleItemsDropdown($options)
    {
        global $DB;

        $moduletype = $options['moduletype'];
        $itemtype   = $options['itemtype'];
        $method     = $options['method'];
        if ($itemtype === "") {
            return;
        }
        switch ($moduletype) {
            case 'actors':
                $title = __('Actor Item', 'glpiinventory');
                break;

            case 'targets':
                $title = __('Target Item', 'glpiinventory');
                break;
        }

        if (!preg_match("/^[a-zA-Z]+$/", $method)) {
            $method = '';
        }

       // filter actor list with active agent and with current module active
        $condition = [];
        if (
            $moduletype == "actors"
            && in_array($itemtype, ["Computer", "Agent"])
        ) {
           // remove install suffix from deploy
            $modulename = str_replace('DEPLOYINSTALL', 'DEPLOY', strtoupper($method));

            // prepare a query to retrieve agent's & computer's id
            $iterator = $DB->request([
                'SELECT' => [
                    'agents.id AS agents_id',
                    'agents.items_id'
                ],
                'FROM' => 'glpi_agents AS agents',
                'LEFT JOIN' => [
                    'glpi_computers AS computers' => [
                        'ON' => [
                            'computers' => 'id',
                            'agents' => 'items_id', [
                                'AND' => [
                                    'agents.itemtype' => 'Computer'
                                ]
                            ]
                        ]
                    ],
                    'glpi_plugin_glpiinventory_agentmodules AS modules' => [
                        'OR' => [
                            'exceptions' => ['LIKE', new QueryExpression("CONCAT('%\"', agents.`id`, '\"%')")],
                            'modules.is_active' => 1
                        ]
                    ]
                ],
                'WHERE' => [
                    'RAW' => [
                        'UPPER(modules.modulename)' => $modulename,
                    ],
                    'computers.is_deleted' => 0,
                    'computers.is_template' => 0
                ],
                'GROUP' => [
                    'agents.id',
                    'agents.items_id'
                ]
            ]);
            $filter_id = [];
            foreach ($iterator as $data_filter) {
                if ($itemtype == 'Computer') {
                    $filter_id[] =  $data_filter['items_id'];
                } else {
                    $filter_id[] =  $data_filter['agents_id'];
                }
            }

           // if we found prepare condition for dropdown
           // else prepare a false condition for dropdown
            if (count($filter_id)) {
                $condition = ['id' => $filter_id];
            }
        }

       /**
        * get Itemtype choices dropdown
        */
        $dropdown_rand = $this->showDropdownForItemtype(
            $title,
            $itemtype,
            [
            'width'     => "95%",
            'condition' => $condition
            ]
        );
        $item = getItemForItemtype($itemtype);
        $itemtype_name = $item->getTypeName();
        $item_key_id = $item->getForeignKeyField();
        $dropdown_rand_id = "dropdown_" . $item_key_id . $dropdown_rand;
        echo "<div class='center'
                 id='add_fusinv_job_item_button'
                 data-moduletype='$moduletype'
                 data-itemtype='$itemtype'
                 data-itemtype_name='$itemtype_name'
                 data-dropdown_rand_id='$dropdown_rand_id'>
               <button type='button' class='btn btn-secondary'>" . __('Add') . " $title</button>
            </div>";
    }


   /**
    * Get html code for itemtype plus button
    *
    * @param string $title
    * @param string $itemtype
    * @param string $method
    * @return string
    */
    public function getAddItemtypeButton($title, $itemtype, $method)
    {
        return"<a class='addbutton show_moduletypes'
                data-ajaxurl='" . $this->getBaseUrlFor('fi.job.moduletypes') . "'
                data-itemtype='$itemtype'
                data-method='$method'>
            $title
            <img src='" . $this->getBaseUrlFor('glpi.pics') . "/add_dropdown.png' />
            </a>";
    }


   /**
    * Display form for taskjob
    *
    * @param integer $id id of the taskjob
    * @param array $options
    * @return true
    */
    public function showForm($id, $options = [])
    {
        global $CFG_GLPI;

        $new_item = false;
        if ($id > 0) {
            if ($this->getFromDB($id)) {
                $this->checkConfiguration($id);
                $this->getFromDB($id);
            } else {
                $id = 0;
                $this->getEmpty();
                $this->fields['plugin_glpiinventory_tasks_id'] = $options['task_id'];
                $new_item = true;
            }
        } else {
            if (!array_key_exists('task_id', $options)) {
                echo $this->getMessage(
                    __('A job can not be created outside a task form'),
                    self::MSG_ERROR
                );
                return;
            }
            $this->getEmpty();
            $this->fields['plugin_glpiinventory_tasks_id'] = $options['task_id'];
            $new_item = true;
        }
        $pfTask = $this->getTask();

        echo "<form method='post' name='form_taskjob' action='" .
            Plugin::getWebDir('glpiinventory') . "/front/taskjob.form.php''>";

        if (!$new_item) {
            echo "<input type='hidden' name='id' value='" . $id . "' />";
        }
        echo
         "<input type='hidden' name='plugin_glpiinventory_tasks_id' " .
         "value='" . $pfTask->fields['id'] . "' />";
        echo "<table class='tab_cadre_fixe'>";

       // Optional line
        $ismultientities = Session::isMultiEntitiesMode();
        echo '<tr>';
        echo '<th colspan="4">';

        if (!$new_item) {
            echo $this->getTypeName() . " - " . __('ID') . " $id ";
            if ($ismultientities) {
                echo "(" . Dropdown::getDropdownName('glpi_entities', $this->fields['entities_id']) . ")";
            }
        } else {
            if ($ismultientities) {
                echo __('New action', 'glpiinventory') . "&nbsp;:&nbsp;" .
                 Dropdown::getDropdownName("glpi_entities", $this->fields['entities_id']);
            } else {
                echo __('New action', 'glpiinventory');
            }
        }
        echo '</th>';
        echo '</tr>';

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='4'>";
        echo "<div class='fusinv_form'>";

        echo "<div class='mb-2 row col-12 col-sm-10'>";
        echo "<label class='form-label col-sm-4 col-form-label'>" . __('Name') . "&nbsp;</label>";
        echo "<div class='col-sm-8'>";
        echo Html::input("name", ['value' => $this->fields["name"]]);
        echo "</div>";
        echo "</div>";

        echo "<div class='mb-2 row col-20 col-sm-10'>";
        echo "<label class='form-label col-sm-4 col-form-label'>" . __('Comments') . "&nbsp;</label>";
        echo "<div class='col-sm-8'>";
        echo
         "<textarea class='autogrow form-control' name='comment' >" .
         $this->fields["comment"] .
         "</textarea>";
        echo "</div>";
        echo "</div>";

        $modules_methods = PluginGlpiinventoryStaticmisc::getModulesMethods();
        if (
            !Session::haveRight('plugin_glpiinventory_networkequipment', CREATE)
              and !Session::haveRight('plugin_glpiinventory_printer', CREATE)
        ) {
            if (isset($modules_methods['networkdiscovery'])) {
                unset($modules_methods['networkdiscovery']);
            }
            if (isset($modules_methods['networkinventory'])) {
                unset($modules_methods['networkinventory']);
            }
        }
        if (!Session::haveRight('plugin_glpiinventory_wol', READ)) {
            if (isset($modules_methods['wakeonlan'])) {
                unset($modules_methods['wakeonlan']);
            }
        }

        echo "<div class='mb-2 row col-20 col-sm-10'>";
        echo "<label>" . __('Module method', 'glpiinventory') . "&nbsp;</label>";

        if (!isset($options['width'])) {
            $options['width'] = '40%';
        }

        if (!is_null("method")) {
            $options['value'] = $this->fields["method"];
        }

        $options["on_change"] = "task_method_change(this.value)";

        $modules_methods_rand = Dropdown::showFromArray(
            "method",
            $modules_methods,
            $options
        );
        echo "</div>";

        echo Html::scriptBlock("
        function task_method_change(val) {
           var display = (val != 'networkinventory') ? 'none' : '';
           document.getElementById('entity_restrict').style.display = display;
        }
     ");

        if (!$new_item) {
            echo "<script type='text/javascript'>";
            echo "   taskjobs.register_update_method( 'dropdown_method" . $modules_methods_rand . "');";
            echo "</script>";

            echo "<div style='display:none' id='method_selected'>" . $this->fields['method'] . "</div>";
        }

        $style = "style='display:none'";
        if (!$new_item && $this->fields['method'] == "networkinventory") {
            $style = "";
        }

        echo "<div " . $style . "id='entity_restrict' class='mb-2 col-20 col-sm-10'>";
        echo "<label>" . __('Restrict scope to task entity', 'glpiinventory') . "&nbsp;</label>";

        if (!isset($options['width'])) {
            $options['width'] = '40%';
        }
        $options['name'] = 'restrict_to_task_entity';
        if (!is_null("restrict_to_task_entity")) {
            $options['value'] = 1;
            $options['checked'] = $this->fields["restrict_to_task_entity"];
        }
        echo Html::getCheckbox($options);
        echo Html::showToolTip(__('Only for IPRange, restrict target to task entity. Unchecked if assets are not in the same entity as the task'), ['display' => true]);
        echo "</div>";

        echo "</div>"; // end of first inputs column wrapper

       // Display Definition choices
        if (!$new_item) {
           //Start second column of the form
            echo "<div class='fusinv_form'>";

            echo "<div class='input_wrap split_column tab_bg_4'>";
            echo $this->getAddItemtypeButton(
                __('Targets', 'glpiinventory'),
                'targets',
                $this->fields['method']
            );
           //echo "<br/><span class='description' style='font-size:50%;font-style:italic'>";
            echo "<br/><span class='description'>";
            echo __('The items that should be applied for this job.', 'glpiinventory');
            echo "</span>";
            echo "</div>";

            echo "<div class='input_wrap split_column tab_bg_4'>";
            echo $this->getAddItemtypeButton(
                __('Actors', 'glpiinventory'),
                'actors',
                $this->fields['method']
            );
            echo "<br/><span class='description'>";
            echo __('The items that should carry out those targets.', 'glpiinventory');
            echo "</span>";
            echo "</div>";

            echo "<div class='dropdown-divider'></div>";
            echo "<div id='taskjob_moduletypes_dropdown'></div>";
            echo "<div id='taskjob_moduleitems_dropdown'></div>";
            echo "</div>";
        }

        if (!$new_item) {
            $targets_display_list = $this->getItemsList('targets');
           // Display targets and actors lists
            echo "<hr/>
               <div>
                  <div class='taskjob_list_header'>
                     <label>" . __('Targets', 'glpiinventory') . "&nbsp;:</label>
                  </div>
                  <div id='taskjob_targets_list'>
                     $targets_display_list
                  </div>
                  <div>
                     <a class='clear_list button'
                        data-clear-param='targets'>" .
                        __('Clear list', 'glpiinventory') . "
                     </a>
                      /
                     <a class='delete_items_selected'
                        data-delete-param='targets'>" .
                        __('Delete selected items', 'glpiinventory') . "
                     </a>
                  </div>
               </div>";

            $actors_display_list = $this->getItemsList('actors');
            echo "<hr/>
               <div>
                  <div class='taskjob_list_header'>
                     <label>" . __('Actors', 'glpiinventory') . "&nbsp;:</label>
                  </div>
                  <div id='taskjob_actors_list'>
                     $actors_display_list
                  </div>
                  <div>
                     <a class='clear_list'
                        data-clear-param='actors'>" .
                        __('Clear list', 'glpiinventory') . "
                     </a>
                       /
                     <a class='delete_items_selected'
                        data-delete-param='actors'>" .
                        __('Delete selected items', 'glpiinventory') . "
                     </a>
                  </div>
               </div>";
        }

        if ($new_item) {
            echo "<tr>";
            echo "<td colspan='4' valign='top' align='center'>";
            echo Html::submit(__('Add'), ['name' => 'add', 'class' => 'btn btn-primary']);
            echo "</td>";
            echo '</tr>';
        } else {
            echo "<tr>";
            echo "<td class='center'>";
            echo Html::submit(__('Update'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</td>";

            echo "<td class='center' colspan='2'>
                  <div id='cancel_job_changes_button' style='display:none'>
                     <button type='button' class='btn btn-secondary'
                            onclick='taskjobs.edit(\"" . $this->getBaseUrlFor('fi.job.edit') . "\", $id)'>" .
                            __('Cancel modifications', 'glpiinventory') . "</button>
                  </div>
               </td>";

            echo "<td class='center'>";
            echo "<input type='submit'
                      name='delete'
                      value=\"" . __('Purge', 'glpiinventory') . "\"
                      class='btn btn-danger' " .
                      Html::addConfirmationOnAction(__(
                          'Confirm the final deletion ?',
                          'glpiinventory'
                      )) . ">";
            echo "</td>";
            echo '</tr>';
        }

        echo "</table>";
        Html::closeForm();

        echo Html::scriptBlock("$(document).ready(function() {
         taskjobs.register_form_changed();
      });");

        echo "<br/>";

        return true;
    }


   /**
    * Manage actions when submit a form (add, update, purge...)
    *
    * @param array $postvars
    */
    public function submitForm($postvars)
    {
        global $CFG_GLPI;

        $jobs_id = 0;

        $mytaskjob = new PluginGlpiinventoryTaskjob();
        if (isset($postvars['definition_add'])) {
           // * Add a definition
            $mytaskjob->getFromDB($postvars['id']);
            $a_listdef = importArrayFromDB($mytaskjob->fields['definition']);
            $add = 1;
            foreach ($a_listdef as $dataDB) {
                if (
                    isset($dataDB[$postvars['DefinitionType']])
                    and $dataDB[$postvars['DefinitionType']] == $postvars['definitionselectiontoadd']
                ) {
                    $add = 0;
                    break;
                }
            }
            if ($add == '1') {
                if (
                    isset($postvars['DefinitionType'])
                    and $postvars['DefinitionType'] != ''
                ) {
                    $a_listdef[] = [$postvars['DefinitionType'] => $postvars['definitionselectiontoadd']];
                }
            }
            $input = [];
            $input['id'] = $postvars['id'];
            $input['definition'] = exportArrayToDB($a_listdef);
            $mytaskjob->update($input);
            Html::back();
        } elseif (isset($postvars['action_add'])) {
           // * Add an action
            $mytaskjob->getFromDB($postvars['id']);
            $a_listact = importArrayFromDB($mytaskjob->fields['action']);
            $add = 1;
            foreach ($a_listact as $dataDB) {
                if (
                    isset($dataDB[$postvars['ActionType']])
                    and $dataDB[$postvars['ActionType']] == $postvars['actionselectiontoadd']
                ) {
                    $add = 0;
                    break;
                }
            }
            if ($add == '1') {
                if (
                    isset($postvars['ActionType'])
                    and $postvars['ActionType'] != ''
                ) {
                    $a_listact[] = [$postvars['ActionType'] => $postvars['actionselectiontoadd']];
                }
            }
            $input = [];
            $input['id'] = $postvars['id'];
            $input['action'] = exportArrayToDB($a_listact);
            $mytaskjob->update($input);
            Html::back();
        } elseif (isset($postvars['definition_delete'])) {
           // * Delete definition
            $mytaskjob->getFromDB($postvars['id']);
            $a_listdef = importArrayFromDB($mytaskjob->fields['definition']);

            foreach ($postvars['definition_to_delete'] as $itemdelete) {
                $datadel = explode('-', $itemdelete);
                foreach ($a_listdef as $num => $dataDB) {
                    if (isset($dataDB[$datadel[0]]) and $dataDB[$datadel[0]] == $datadel[1]) {
                        unset($a_listdef[$num]);
                    }
                }
            }
            $input = [];
            $input['id'] = $postvars['id'];
            $input['definition'] = exportArrayToDB($a_listdef);
            $mytaskjob->update($input);
            Html::back();
        } elseif (isset($postvars['action_delete'])) {
           // * Delete action
            $mytaskjob->getFromDB($postvars['id']);
            $a_listact = importArrayFromDB($mytaskjob->fields['action']);

            foreach ($postvars['action_to_delete'] as $itemdelete) {
                $datadel = explode('-', $itemdelete);
                foreach ($a_listact as $num => $dataDB) {
                    if (isset($dataDB[$datadel[0]]) and $dataDB[$datadel[0]] == $datadel[1]) {
                        unset($a_listact[$num]);
                    }
                }
            }
            $input = [];
            $input['id'] = $postvars['id'];
            $input['action'] = exportArrayToDB($a_listact);
            $mytaskjob->update($input);
            Html::back();
        } elseif (isset($postvars['taskjobstoforcerun'])) {
           // * Force running many tasks (wizard)
            Session::checkRight('plugin_glpiinventory_task', UPDATE);
            $pfTask = new PluginGlpiinventoryTask();
            $pfTaskjob = new PluginGlpiinventoryTaskjob();
            $_SESSION["plugin_glpiinventory_forcerun"] = [];
            foreach ($postvars['taskjobstoforcerun'] as $taskjobs_id) {
                $pfTask->getFromDB($pfTaskjob->fields['plugin_glpiinventory_tasks_id']);
                $pfTask->forceRunning();
            }
        } elseif (isset($postvars['add']) || isset($postvars['update'])) {
           // * Add and update taskjob
            Session::checkRight('plugin_glpiinventory_task', CREATE);
            if (isset($postvars['add'])) {
                if (!isset($postvars['entities_id'])) {
                    $postvars['entities_id'] = $_SESSION['glpidefault_entity'];
                }
               // Get entity of task
                $pfTask = new PluginGlpiinventoryTask();
                $pfTask->getFromDB($postvars['plugin_glpiinventory_tasks_id']);
                $entities_list = getSonsOf('glpi_entities', $pfTask->fields['entities_id']);
                if (!in_array($postvars['entities_id'], $entities_list)) {
                    $postvars['entities_id'] = $pfTask->fields['entities_id'];
                }
                $jobs_id = $this->add($postvars);
            } else {
                if (isset($postvars['method_id'])) {
                    $postvars['method']  = $postvars['method_id'];
                }

                $targets = [];
                if (
                    array_key_exists('targets', $postvars)
                    and is_array($postvars['targets'])
                    and count($postvars['targets']) > 0
                ) {
                    foreach ($postvars['targets'] as $target) {
                        list($itemtype, $itemid) = explode('-', $target);
                        $targets[] = [$itemtype => $itemid];
                    }
                }

                $postvars['targets'] = exportArrayToDB($targets);

                $actors = [];
                if (
                    array_key_exists('actors', $postvars)
                    and is_array($postvars['actors'])
                    and count($postvars['actors']) > 0
                ) {
                    foreach ($postvars['actors'] as $actor) {
                        list($itemtype, $itemid) = explode('-', $actor);
                        $actors[] = [$itemtype => $itemid];
                    }
                }

                $postvars['actors'] = exportArrayToDB($actors);

               //TODO: get rid of plugins_id and just use method
                $this->update($postvars);
            }

            $add_redirect = "";
            if ($jobs_id) {
                $add_redirect = "&edit_job=$jobs_id#taskjobs_form";
            }

            Html::redirect(Plugin::getWebDir('glpiinventory') . "/front/task.form.php?id=" .
                                 $postvars['plugin_glpiinventory_tasks_id'] . $add_redirect);
        } elseif (isset($postvars["delete"])) {
           // * delete taskjob
            Session::checkRight('plugin_glpiinventory_task', PURGE);

            $this->delete($postvars);
        } elseif (isset($postvars['itemaddaction'])) {
            $array                     = explode("||", $postvars['methodaction']);
            $module                    = $array[0];
            $method                    = $array[1];
           // Add task
            $mytask = new PluginGlpiinventoryTask();
            $input                     = [];
            $input['name']             = $method;

            $task_id = $mytask->add($input);

           // Add job with this device
            $input = [];
            $input['plugin_glpiinventory_tasks_id'] = $task_id;
            $input['name']                            = $method;
            $input['datetime_start']                  = $postvars['datetime_start'];

            $input['plugins_id']                      = PluginGlpiinventoryModule::getModuleId($module);
            $input['method']                          = $method;
            $a_selectionDB                            = [];
            $a_selectionDB[][$postvars['itemtype']]      = $postvars['items_id'];
            $input['definition']                      = exportArrayToDB($a_selectionDB);

            $taskname = "plugin_" . $module . "_task_selection_type_" . $method;
            if (is_callable($taskname)) {
                $input['selection_type'] = call_user_func($taskname, $postvars['itemtype']);
            }
            $mytaskjob->add($input);
           // Upsate task to activate it
            $mytask->getFromDB($task_id);
            $mytask->fields['is_active'] = "1";
            $mytask->update($mytask->fields);
           // force running this job (?)
        } elseif (isset($postvars['forceend'])) {
            $mytaskjobstate = new PluginGlpiinventoryTaskjobstate();
            $pfTaskjob = new PluginGlpiinventoryTaskjob();
            $mytaskjobstate->getFromDB($postvars['taskjobstates_id']);
            $jobstate = $mytaskjobstate->fields;
            $a_taskjobstates = $mytaskjobstate->find(['uniqid' => $mytaskjobstate->fields['uniqid']]);
            foreach ($a_taskjobstates as $data) {
                if ($data['state'] != PluginGlpiinventoryTaskjobstate::FINISHED) {
                    $mytaskjobstate->changeStatusFinish(
                        $data['id'],
                        0,
                        '',
                        1,
                        "Action cancelled by user"
                    );
                }
            }

            $pfTaskjob->getFromDB($jobstate['plugin_glpiinventory_taskjobs_id']);
            $pfTaskjob->reinitializeTaskjobs($pfTaskjob->fields['plugin_glpiinventory_tasks_id']);
        } elseif (isset($postvars['delete_taskjobs'])) {
            foreach ($postvars['taskjobs'] as $taskjob_id) {
                $input = ['id' => $taskjob_id];
                $this->delete($input, true);
            }
        }
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
