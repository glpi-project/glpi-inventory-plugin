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
 * Manage the display part of tasks.
 */
class PluginGlpiinventoryTaskView extends PluginGlpiinventoryCommonView
{
   /**
    * __contruct function where initialize base URLs
    */
    public function __construct()
    {
        parent::__construct();
        $this->base_urls = array_merge($this->base_urls, [
         'fi.job.logs' => $this->getBaseUrlFor('fi.ajax') . "/taskjob_logs.php",
        ]);
    }


   /**
    * Show job logs
    */
    public function showJobLogs()
    {
        $task_id = $this->fields['id'] ?? null;
        echo "<div class='card fusinv_panel'>";
        echo "<div class='row'>";

       // add a list limit for include old jobs
        $include_oldjobs_id = $this->showDropdownFromArray(
            __("Include old jobs", 'glpiinventory'),
            null,
            [
            1   => __('Last'),
            2   => 2,
            5   => 5,
            10  => 10,
            25  => 25,
            50  => 50,
            100 => 100,
            250 => 250,
            -1  => __('All')
            ],
            ['value' => $_SESSION['glpi_plugin_glpiinventory']['includeoldjobs']]
        );

       // add an auto-refresh control
        $refresh_randid = $this->showDropdownFromArray(
            __("refresh interval", "glpiinventory"),
            null,
            [
            "off"  => __('Off', 'glpiinventory'),
            "1"    => '1 ' . _n('second', 'seconds', 1),
            "5"    => '5 ' . _n('second', 'seconds', 5),
            "10"   => '10 ' . _n('second', 'seconds', 10),
            "60"   => '1 ' . _n('minute', 'minutes', 1),
            "120"  => '2 ' . _n('minute', 'minutes', 2),
            "300"  => '5 ' . _n('minute', 'minutes', 5),
            "600"  => '10 ' . _n('minute', 'minutes', 10),
            ],
            ['value' => $_SESSION['glpi_plugin_glpiinventory']['refresh']]
        );

       // display export button
        echo "<div class='col mt-auto'>";

        echo '<a class="openExportDialog pointer btn btn-icon btn-sm btn-secondary me-1 pe-2">';
        echo '<i class="ti ti-save"></i>';
        echo'<span class="d-none d-xxl-block">' .  __('Export task result', 'glpinventory') . '</span>';
        echo '</a>';

        // Add a manual refresh button
        echo "<div class='refresh_button submit'>";
        echo "<span></span>";
        echo "</div>"; // .refresh_button
        echo "</div>";

        echo "</div>"; // .fusinv_form
        echo "</div>"; // .fusinv_panel

         // Display Export modal
         echo "<div class='fusinv_panel' id='fiTaskExport_modalWindow'>";
         echo "<form method='POST' class='task_export_form center'
                     action='" . self::getFormURLWithID($task_id) . "'>";

         // states checkboxes
         echo "<label for='include_old_jobs'>" . __("Task execution states", 'glpiinventory') .
             "</label>";
         echo "<div class='state_checkboxes'>";
         // set options checked by default
         $agent_state_types = [
         'agents_prepared'  => false,
         'agents_running'   => true,
         'agents_cancelled' => false,
         'agents_success'   => true,
         'agents_error'     => true,
         'agents_postponed' => false,
         ];
         foreach ($agent_state_types as $agent_state_type => $agent_state_checked) {
             $agent_state_type = str_replace("agents_", "", $agent_state_type);
             $locale  = __(ucfirst($agent_state_type), 'glpiinventory');
             $checked = "";
             if ($agent_state_checked) {
                 $checked = "checked='checked'";
             }
             echo "<div class='agent_state_type_checkbox'>";
             echo "<input type='checkbox' $checked name='agent_state_types[]' " .
                 "value='$agent_state_type' id='agent_state_types_$agent_state_type' />";
             echo "<label for='agent_state_types_$agent_state_type'>&nbsp;$locale</label>";
             echo "</div>";
         }
         echo "</div>"; // .state_checkboxes

         echo "<div class='clear_states'></div>";

         echo Html::hidden('task_id', ['value' => $task_id]);
         echo Html::submit(_sx('button', 'Export'), ['name' => 'export_jobs', 'class' => 'btn btn-icon btn-sm btn-secondary me-1 pe-2']);
         Html::closeForm();
         echo "</div>"; // #fiTaskExport_modalWindow


         // Template structure for tasks' blocks
         echo "<script id='template_task' type='x-tmpl-mustache'>
             <div id='{{task_id}}' class='task_block {{expanded}}'>";
         if (!$task_id != null) {
             echo"<h3>" .  _n('Task', 'Tasks', 1, 'glpiinventory') . "
            <span class='task_name'>{{task_name}}</span></h3>
            <a href='" . PluginGlpiinventoryTask::getFormURL() . "?id={{task_id}}'  class='task_block_link'>
                <i class='fa fa-link pointer'></i>
            </a>";
         }

         echo "<div class='jobs_block'></div>
               </div>
            </script>";

       // Template structure for jobs' blocks
         echo "<script id='template_job' type='x-tmpl-mustache'>
               <div id='{{job_id}}' class='card job_block'>
                     <h3 class='job_name'>{{job_name}}</h3>
                  <div class='card targets_block'></div>
               </div>
            </script>";

       // Template structure for targets' blocks
         echo "<script id='template_target' type='x-tmpl-mustache'>
               <div id='{{target_id}}' class='card target_block'>
                  <div class='target_details'>
                  <div class='target_infos'>
                     <h4 class='target_name'>
                        <a target='_blank' href={{target_link}}>{{target_name}}</a>
                     </h4>
                     <div class='target_stats'>
                     </div>
                  </div>
                  <div class='progressbar'></div>
               </div>
               <div class='show_more'></div>
               <div class='agents_block'></div>
               <div class='show_more'></div>
            </script>";

       // Template structure for targets' statistics
         echo "<script id='template_target_stats' type='x-tmp-mustache'>
               <div class='{{stats_type}} stats_block'></div>
            </script>";

       // Template for counters' blocks
         echo "<script id='template_counter_block' type='x-tmpl-mustache'>
               <div class='counter_block {{counter_type}} {{#counter_empty}}empty{{/counter_empty}}'>
                  <a class='toggle_details_type'
                     data-counter_type='{{counter_type}}'
                     data-chart_id='{{chart_id}}'
                     title='" . __("Show/Hide details", "glpiinventory") . "'>
                     <div class='fold'></div>
                     <span class='counter_name'>{{counter_type_name}}</span>
                     <span class='counter_value'>{{counter_value}}</span>
                  </a>
               </div>
            </script>";

       // List of counter names
         echo Html::scriptBlock("$(document).ready(function() {
         taskjobs.statuses_order = {
            last_executions: [
               'agents_prepared',
               'agents_running',
               'agents_cancelled',
            ],
            last_finish_states: [
               'agents_notdone',
               'agents_success',
               'agents_error'
            ]
         };

         taskjobs.statuses_names = {
            'agents_notdone':   '" . __('Not done yet', 'glpiinventory') . "',
            'agents_error':     '" . __('In error', 'glpiinventory') . "',
            'agents_success':   '" . __('Successful', 'glpiinventory') . "',
            'agents_running':   '" . __('Running', 'glpiinventory') . "',
            'agents_prepared':  '" . __('Prepared', 'glpiinventory') . "',
            'agents_postponed':  '" . __('Postponed', 'glpiinventory') . "',
            'agents_cancelled': '" . __('Cancelled', 'glpiinventory') . "',
         };

         taskjobs.logstatuses_names = " .
            json_encode(PluginGlpiinventoryTaskjoblog::dropdownStateValues()) . ";
      });");

       // Template for agents' blocks
        echo "<script id='template_agent' type='x-tmpl-mustache'>
               <div class='agent_block' id='{{agent_id}}'>
                  <div class='status {{status.last_exec}}'></span>
                  <div class='status {{status.last_finish}}'></span>
               </div>
            </script>";

       // Display empty block for each jobs display
       // which will be rendered later by mustache.js
        echo "<div class='tasks_block'></div>";

        $agent = new Agent();
        $Computer = new Computer();

        echo Html::scriptBlock("$(document).ready(function() {
         taskjobs.task_id        = '" . $task_id . "';
         taskjobs.ajax_url       = '" . $this->getBaseUrlFor('fi.job.logs') . "';
         taskjobs.agents_url     = '" . $agent->getFormUrl() . "';
         taskjobs.includeoldjobs = '" . $_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'] . "';
         taskjobs.refresh        = '" . $_SESSION['glpi_plugin_glpiinventory']['refresh'] . "';
         taskjobs.computers_url  = '" . $Computer->getFormUrl() . "';
         taskjobs.init_templates();
         taskjobs.init_refresh_form(
            '" . $this->getBaseUrlFor('fi.job.logs') . "',
            '" . $task_id . "',
            'dropdown_" . $refresh_randid . "'
         );
         taskjobs.init_include_old_jobs_buttons(
            '" . $this->getBaseUrlFor('fi.job.logs') . "',
            '" . $task_id . "',
            'dropdown_" . $include_oldjobs_id . "'
         );
         taskjobs.update_logs_timeout(
            '" . $this->getBaseUrlFor('fi.job.logs') . "',
            '" . $task_id . "',
            'dropdown_" . $refresh_randid . "'
         );
      });");
    }


   /**
    * Display form for task configuration
    *
    * @param integer $id ID of the task
    * @param $options array
    * @return boolean TRUE if form is ok
    *
    **/
    public function showForm($id, $options = [])
    {
        $pfTaskjob = new PluginGlpiinventoryTaskjob();

        $taskjobs = [];
        $new_item = false;

        if ($id > 0) {
            $this->getFromDB($id);
            $taskjobs = $pfTaskjob->find(['plugin_glpiinventory_tasks_id' => $id], ['id']);
        } else {
            $this->getEmpty();
            $new_item = true;
        }

        $options['colspan'] = 2;
        $this->initForm($id, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='4'>";
        echo "<div class='row'>";

        $this->showTextField(__('Name'), "name");
        $this->showTextArea(__('Comments'), "comment");
        $this->showCheckboxField(
            __('Permit to re-prepare task after run', 'glpiinventory'),
            "reprepare_if_successful"
        );
        if ($this->fields['is_deploy_on_demand']) {
            echo "<div class='mb-2 row col-12 col-sm-6'>";
            echo __("This is an on demand deployment task", "glpiinventory");
            echo "</div>";
        }
        if (!$new_item) {
            $this->showCheckboxField(__('Active'), "is_active");

            $datetime_field_options = [
            'timestep'   => 1,
            'maybeempty' => true,
            ];
            $this->showDateTimeField(
                __('Schedule start', 'glpiinventory'),
                "datetime_start",
                $datetime_field_options
            );

            $this->showDateTimeField(
                __('Schedule end', 'glpiinventory'),
                "datetime_end",
                $datetime_field_options
            );

            $this->showDropdownForItemtype(
                __('Preparation timeslot', 'glpiinventory'),
                "PluginGlpiinventoryTimeslot",
                ['name'  => 'plugin_glpiinventory_timeslots_prep_id',
                                        'value' => $this->fields['plugin_glpiinventory_timeslots_prep_id']
                                       ]
            );

            $this->showDropdownForItemtype(
                __('Execution timeslot', 'glpiinventory'),
                "PluginGlpiinventoryTimeslot",
                ['name'  => 'plugin_glpiinventory_timeslots_exec_id',
                  'value' => $this->fields['plugin_glpiinventory_timeslots_exec_id']]
            );

            $this->showIntegerField(
                __('Agent wakeup interval (in minutes)', 'glpiinventory'),
                "wakeup_agent_time",
                ['value' => $this->fields['wakeup_agent_time'],
                                  'toadd' => ['0' => __('Never')],
                                  'min'   => 1,
                                  'step'  => 1
                ]
            );

            $this->showIntegerField(
                __('Number of agents to wake up', 'glpiinventory'),
                "wakeup_agent_counter",
                ['value' => $this->fields['wakeup_agent_counter'],
                                  'toadd' => ['0' => __('None')],
                                  'min'   => 0,
                                  'step'  => 1
                ]
            );
        }

        echo "</div>";
        echo "</td>";
        echo "</tr>";
        $this->showFormButtons($options);

        return true;
    }


    public function showFormButtons($options = [])
    {
        $ID = 0;
        if (isset($this->fields['id'])) {
            $ID = $this->fields['id'];
        }

        echo "<tr>";
        echo "<td colspan='2'>";
        if ($this->isNewID($ID)) {
            echo Html::submit(_x('button', 'Add'), [
                'name' => 'add',
                'class' => 'btn btn-primary'
            ]);
        } else {
            echo Html::hidden('id', ['value' => $ID]);
            echo Html::submit("<i class='fas fa-save me-1'></i>" . _x('button', 'Save'), [
            'name'  => 'update',
            'class' => 'btn btn-primary'
            ]);
        }
        echo "</td>";

        if ($this->fields['is_active']) {
            echo "<td>";
            echo Html::submit("<i class='fas fa-bolt me-1'></i>" . __('Force start', 'glpiinventory'), [
            'name' => 'forcestart',
            'class' => 'btn btn-warning',
            ]);
            echo "</td>";
        }

        echo "<td>";
        if (!$this->isNewID($ID) && $this->can($ID, PURGE)) {
            echo Html::submit("<i class='fas fa-trash me-1'></i>" . _x('button', 'Delete permanently'), [
            'name'    => 'purge',
            'confirm' => __('Confirm the final deletion?'),
            'class '  => 'btn btn-danger',
            ]);
        }
        echo "</td>";
        echo "</tr>";

       // Close for Form
        echo "</table></div>";
        Html::closeForm();
    }


   /**
    * Manage the different actions in when submit form (add, update,purge...)
    *
    * @param array $postvars
    */
    public function submitForm($postvars)
    {

        if (isset($postvars['forcestart'])) {
            Session::checkRight('plugin_glpiinventory_task', UPDATE);

            $this->getFromDB($postvars['id']);
            $this->forceRunning();

            Html::back();
        } elseif (isset($postvars["add"])) {
            Session::checkRight('plugin_glpiinventory_task', CREATE);
            $items_id = $this->add($postvars);
            Html::redirect(str_replace("add=1", "", $_SERVER['HTTP_REFERER']) . "?id=" . $items_id);
        } elseif (isset($postvars["purge"])) {
            Session::checkRight('plugin_glpiinventory_task', PURGE);
            $pfTaskJob = new PluginGlpiinventoryTaskjob();
            $taskjobs = $pfTaskJob->find(['plugin_glpiinventory_tasks_id' => $postvars['id']]);
            foreach ($taskjobs as $taskjob) {
                $pfTaskJob->delete($taskjob);
            }
            $this->delete($postvars);
            Html::redirect(Toolbox::getItemTypeSearchURL(get_class($this)));
        } elseif (isset($_POST["update"])) {
            Session::checkRight('plugin_glpiinventory_task', UPDATE);
            $this->getFromDB($postvars['id']);
           //Ensure empty value are set to NULL for datetime fields
            if (isset($postvars['datetime_start']) && $postvars['datetime_start'] === '') {
                $postvars['datetime_start'] = 'NULL';
            }
            if (isset($postvars['datetime_end']) && $postvars['datetime_end'] === '') {
                $postvars['datetime_end'] = 'NULL';
            }
            $this->update($postvars);
            Html::back();
        } elseif (isset($postvars['export_jobs'])) {
            Session::checkRight('plugin_glpiinventory_task', READ);
            $this->csvExport($postvars);
        }
    }


   /**
    * Define reprepare_if_successful field when get empty item
    *
    * @return bool
    */
    public function getEmpty()
    {
        parent::getEmpty();
        $pfConfig = new PluginGlpiinventoryConfig();
        $this->fields['reprepare_if_successful'] = $pfConfig->getValue('reprepare_job');

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

    /**
     * Export a list of jobs in CSV format
     *
     * @param  array  $params these possible entries:
     *                        - agent_state_types: array of agent states to filter output
     *                          (prepared, cancelled, running, success, error)
     *                        - debug_csv, possible values:
     *                           - 0 : no debug (really export to csv,
     *                           - 1 : display params AND html table,
     *                           - 2: like 1 + display also json of jobs logs
     *
     * @return nothing (force a download of csv)
     */
    public function csvExport($params = [])
    {
        global $CFG_GLPI;

        $default_params = [
            'agent_state_types' => [],
            'debug_csv'         => 0
        ];
        $params = array_merge($default_params, $params);

        $includeoldjobs    = $_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'];
        $agent_state_types = ['prepared', 'cancelled', 'running', 'success', 'error' ];
        if (isset($params['agent_state_types'])) {
            $agent_state_types = $params['agent_state_types'];
        }

        if (!$params['debug_csv']) {
            header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
            header('Pragma: private'); /// IE BUG + SSL
            header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
            header("Content-disposition: attachment; filename=export.csv");
            header("Content-type: text/csv");
        } else {
            Html::printCleanArray($params);
            Html::printCleanArray($agent_state_types);
        }

        $params['display'] = false;
        $pfTask            = new PluginGlpiinventoryTask();
        $data              = json_decode($pfTask->ajaxGetJobLogs($params), true);

        //clean line with state_types with unwanted states
        foreach ($data['tasks'] as $task_id => &$task) {
            foreach ($task['jobs'] as $job_id => &$job) {
                foreach ($job['targets'] as $target_id => &$target) {
                    foreach ($target['agents'] as $agent_id => &$agent) {
                        foreach ($agent as $exec_id => $exec) {
                            if (!in_array($exec['state'], $agent_state_types)) {
                                unset($agent[$exec_id]);
                                if (count($agent) === 0) {
                                    unset($target['agents'][$agent_id]);
                                }
                            }
                        }
                    }
                }
            }
        }

        // clean old temporary variables
        unset($task, $job, $target, $agent);

        if (!$params['debug_csv']) {
            define('SEP', $CFG_GLPI['csv_delimiter']);
            define('NL', "\r\n");
        } else {
            define('SEP', '</td><td>');
            define('NL', '</tr><tr><td>');
            echo "<table border=1><tr><td>";
        }

        // cols titles
        echo "Task_name" . SEP;
        echo "Job_name" . SEP;
        echo "Method" . SEP;
        echo "Target" . SEP;
        echo "Agent" . SEP;
        echo "Computer name" . SEP;
        echo "Date" . SEP;
        echo "Status" . SEP;
        echo "Last Message" . NL;

        $agent_obj = new Agent();
        $computer  = new Computer();

        // prepare an anonymous (and temporory) function
        // for test if an element is the last of an array
        $last = function (&$array, $key) {
            end($array);
            return $key === key($array);
        };

        // display lines
        $csv_array = [];
        $tab = 0;
        foreach ($data['tasks'] as $task_id => $task) {
            echo $task['task_name'] . SEP;

            if (count($task['jobs']) == 0) {
                echo NL;
            } else {
                foreach ($task['jobs'] as $job_id => $job) {
                    echo $job['name'] . SEP;
                    echo $job['method'] . SEP;
                    if (count($job['targets']) == 0) {
                        echo NL;
                    } else {
                        foreach ($job['targets'] as $target_id => $target) {
                            echo $target['name'] . SEP;

                            if (count($target['agents']) == 0) {
                                echo NL;
                            } else {
                                foreach ($target['agents'] as $agent_id => $agent) {
                                    $agent_obj->getFromDB($agent_id);
                                    echo $agent_obj->getName() . SEP;
                                    $computer->getFromDB($agent_obj->fields['items_id']);
                                    echo $computer->getname() . SEP;

                                    $log_cpt = 0;
                                    if (count($agent) == 0) {
                                        echo NL;
                                    } else {
                                        foreach ($agent as $exec_id => $exec) {
                                            echo $exec['last_log_date'] . SEP;
                                            echo $exec['state'] . SEP;
                                            echo $exec['last_log'] . NL;
                                            $log_cpt++;

                                            if ($includeoldjobs != -1 and $log_cpt >= $includeoldjobs) {
                                                break;
                                            }

                                            if (!$last($agent, $exec_id)) {
                                                echo SEP . SEP . SEP . SEP . SEP . SEP;
                                            }
                                        }
                                    }

                                    if (!$last($target['agents'], $agent_id)) {
                                        echo SEP . SEP . SEP . SEP;
                                    }
                                }
                            }

                            if (!$last($job['targets'], $target_id)) {
                                echo SEP . SEP . SEP;
                            }
                        }
                    }

                    if (!$last($task['jobs'], $job_id)) {
                        echo SEP;
                    }
                }
            }
        }
        if ($params['debug_csv'] === 2) {
            echo "</td></tr></table>";

            //echo original datas
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        }

        // force exit to prevent further display
        exit;
    }


    /**
     * Force running the current task
     **/
    public function forceRunning()
    {
        $methods = [];
        foreach (PluginGlpiinventoryStaticmisc::getmethods() as $method) {
            $methods[] = $method['method'];
        }
        $this->prepareTaskjobs($methods, $this->getID());
    }


    /**
     * Prepare task jobs
     *
     * @global object $DB
     * @param array $methods
     * @param string $task_id; the concerned task
     * @return true
     */
    public function prepareTaskjobs($methods = [], $tasks_id = false)
    {
        global $DB;

        $now = new DateTime();

        PluginGlpiinventoryToolbox::logIfExtradebug(
            "pluginGlpiinventory-jobs",
            "Preparing tasks jobs, task id: " . $tasks_id
        );

        //Get all active timeslots
        $timeslot  = new PluginGlpiinventoryTimeslot();
        $timeslots = $timeslot->getCurrentActiveTimeslots();
        if (empty($timeslots)) {
            $it_timeslot = ['plugin_glpiinventory_timeslots_prep_id' => 0];
        } else {
            $it_timeslot = [
                'OR' => [
                    [
                        'plugin_glpiinventory_timeslots_prep_id' => 0,
                    ],
                    [
                        'plugin_glpiinventory_timeslots_prep_id' => $timeslots,
                    ]
                ]
            ];
        }

        // limit preparation to a specific tasks_id
        $it_task_id = [];
        if ($tasks_id) {
            $it_task_id = ['task.id' => $tasks_id];
        }

        $iterator = $DB->request([
            'SELECT' => [
                'task.id',
                'task.name',
                'task.reprepare_if_successful',
                'job.id AS jobid',
                'job.name AS jobname',
                'job.method',
                'job.targets',
                'job.actors',
                'job.restrict_to_task_entity'
            ],
            'FROM' => 'glpi_plugin_glpiinventory_taskjobs AS job',
            'LEFT JOIN' => [
                'glpi_plugin_glpiinventory_tasks AS task' => [
                    'FKEY' => [
                        'task' => 'id',
                        'job' => 'plugin_glpiinventory_tasks_id'
                    ]
                ]
            ],
            'WHERE' => array_merge([
                'task.is_active' => 1,
                [
                    'OR' => [
                        [
                            [
                                'NOT' => [
                                    'task.datetime_start' => null,
                                ]
                            ],
                            'task.datetime_end' => null,
                            'task.datetime_start' => ['<', $now->format("Y-m-d H:i:s")],
                        ],
                        [
                            ['NOT' => ['task.datetime_start' => null]],
                            ['NOT' => ['task.datetime_end' => null]],
                            new QueryExpression(
                                $DB->quoteValue($now->format("Y-m-d H:i:s")) . ' BETWEEN ' .
                                $DB->quoteName('task.datetime_start') . ' AND ' .
                                $DB->quoteName('task.datetime_end')
                            ),
                        ],
                        [
                            'task.datetime_start' => null,
                            'task.datetime_end' => null
                        ]
                    ]
                ],
                'job.method' => $methods,
            ], $it_timeslot, $it_task_id),
            'ORDER' => [
                'job.id'
            ]
        ]);
        $results = PluginGlpiinventoryToolbox::fetchAssocByTableIterator($iterator);

        // Fetch a list of actors to be prepared. We may have the same actors for each job so this
        // part can speed up the process.
        //$actors = [];

        // Set basic elements of jobstates
        $run_base = [
            'state' => PluginGlpiinventoryTaskjobstate::PREPARED,
        ];
        $log_base = [
            'date'    => $_SESSION['glpi_currenttime'],
            'state'   => PluginGlpiinventoryTaskjoblog::TASK_PREPARED,
            'comment' => ''
        ];

        $jobstate = new PluginGlpiinventoryTaskjobstate();
        $joblog   = new PluginGlpiinventoryTaskjoblog();

        foreach ($results as $result) {
            $actors = importArrayFromDB($result['job']['actors']);
            // Get agents linked to the actors
            $agent_ids = [];
            foreach ($this->getAgentsFromActors($actors) as $agent_id) {
                $agent_ids[$agent_id] = true;
            }
            //Continue with next job if there are no agents found from actors.
            //TODO: This may be good to report this kind of information. We just need to do a list of
            //agent's ids generated by actors like array('actors_type-id' => array( 'agent_0',...).
            //Then the following could be put in the targets foreach loop before looping through
            //agents.
            if (count($agent_ids) == 0) {
                continue;
            }
            $saved_agent_ids = $agent_ids;
            $targets = importArrayFromDB($result['job']['targets']);
            if ($result['job']['method'] == 'networkinventory') {
                $newtargets = [];
                $pfNetworkinventory = new PluginGlpiinventoryNetworkinventory();
                foreach ($targets as $keyt => $target) {
                    $item_type = key($target);
                    $items_id = current($target);
                    if ($item_type == 'PluginGlpiinventoryIPRange') {
                        unset($targets[$keyt]);
                        // In this case get devices of this iprange
                        $deviceList = $pfNetworkinventory->getDevicesOfIPRange($items_id, $result['job']['restrict_to_task_entity']);
                        $newtargets = array_merge($newtargets, $deviceList);
                    }
                }
                $targets = array_merge($targets, $newtargets);
            }

            $limit = 0;
            foreach ($targets as $target) {
                $agent_ids = $saved_agent_ids;
                $item_type = key($target);
                $item_id   = current($target);
                $job_id    = $result['job']['id'];
                // Filter out agents that are already running the targets.
                $jobstates_running = $jobstate->find(
                    ['itemtype' => $item_type,
                        'items_id' => $item_id,
                        'plugin_glpiinventory_taskjobs_id' => $job_id,
                        'NOT'      => ['state' => [
                            PluginGlpiinventoryTaskjobstate::FINISHED,
                            PluginGlpiinventoryTaskjobstate::IN_ERROR,
                            PluginGlpiinventoryTaskjobstate::POSTPONED,
                            PluginGlpiinventoryTaskjobstate::CANCELLED]],
                        'agents_id' => array_keys($agent_ids)]
                );
                foreach ($jobstates_running as $jobstate_running) {
                    $jobstate_agent_id = $jobstate_running['agents_id'];
                    if (isset($agent_ids[$jobstate_agent_id])) {
                        $agent_ids[$jobstate_agent_id] = false;
                    }
                }

                // If task have not reprepare_if_successful, do not reprerare
                // successful taskjobstate
                if (!$result['task']['reprepare_if_successful']) {
                    $jobstates_running = $jobstate->find(
                        ['itemtype' => $item_type,
                            'items_id' => $item_id,
                            'plugin_glpiinventory_taskjobs_id' => $job_id,
                            'state'    => PluginGlpiinventoryTaskjobstate::FINISHED,
                            'agents_id'   => array_keys($agent_ids)]
                    );

                    foreach ($jobstates_running as $jobstate_running) {
                        $jobstate_agent_id = $jobstate_running['agents_id'];
                        if (isset($agent_ids[$jobstate_agent_id])) {
                            $agent_ids[$jobstate_agent_id] = false;
                        }
                    }
                }

                // Cancel agents prepared but not in $agent_ids (like computer
                // not in dynamic group)
                $jobstates_tocancel = $jobstate->find([
                    'itemtype' => $item_type,
                    'items_id' => $item_id,
                    'plugin_glpiinventory_taskjobs_id' => $job_id,
                    'NOT' => [
                        'OR' => [
                            'state' => [
                                PluginGlpiinventoryTaskjobstate::FINISHED,
                                PluginGlpiinventoryTaskjobstate::IN_ERROR,
                                PluginGlpiinventoryTaskjobstate::CANCELLED,
                            ],
                            'agents_id' => array_keys($agent_ids)]
                    ]
                ]);

                foreach ($jobstates_tocancel as $jobstate_tocancel) {
                    $jobstate->getFromDB($jobstate_tocancel['id']);
                    $jobstate->cancel(__('Device no longer defined in definition of job', 'glpiinventory'));
                }

                foreach ($agent_ids as $agent_id => $agent_not_running) {
                    if ($agent_not_running) {
                        $limit += 1;
                        if ($limit > 500) {
                            $limit = 0;
                            break;
                        }
                        $run = array_merge(
                            $run_base,
                            [
                                'itemtype'                           => $item_type,
                                'items_id'                           => $item_id,
                                'plugin_glpiinventory_taskjobs_id' => $job_id,
                                'agents_id'   => $agent_id,
                                'uniqid'                             => uniqid(),
                            ]
                        );

                        $run_id = $jobstate->add($run);
                        PluginGlpiinventoryToolbox::logIfExtradebug(
                            "pluginGlpiinventory-jobs",
                            "- prepared a job execution: " . print_r($run, true)
                        );
                        if ($run_id !== false) {
                            $log = array_merge(
                                $log_base,
                                [
                                    'plugin_glpiinventory_taskjobstates_id' => $run_id
                                ]
                            );
                            $joblog->add($log);
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * Get agents of Computers from Actors defined in taskjobs
     * TODO: this method should be rewritten to call directly a getAgents() method in the
     * corresponding itemtype classes.
     *
     * @param array $actors
     * @param bool  $use_cache retrieve agents from cache or not
     * @return array list of agents
     */
    public function getAgentsFromActors($actors = [], $use_cache = false)
    {
        $agents    = [];
        $computers = [];
        $computer  = new Computer();
        $agent     = new Agent();
        $pfToolbox = new PluginGlpiinventoryToolbox();
        foreach ($actors as $actor) {
            $itemtype = key($actor);
            $itemid   = $actor[$itemtype];
            $item     = getItemForItemtype($itemtype);

            // If this item doesn't exists, we continue to the next actor item.
            // TODO: remove this faulty actor from the list of job actor.
            if ($item === false) {
                trigger_error(
                    sprintf('Invalid itemtype "%s".', $itemtype),
                    E_USER_WARNING
                );
                continue;
            }
            $dbresult = $item->getFromDB($itemid);
            if ($dbresult === false) {
                trigger_error(
                    sprintf('Invalid item "%s" (%s).', $itemtype, $itemid),
                    E_USER_WARNING
                );
                continue;
            }

            switch ($itemtype) {
                case 'Computer':
                    $computers[$itemid] = 1;
                    break;

                case 'PluginGlpiinventoryDeployGroup':
                    $group_targets = $pfToolbox->executeAsGlpiinventoryUser(
                        'PluginGlpiinventoryDeployGroup::getTargetsForGroup',
                        [$itemid, $use_cache]
                    );
                    foreach ($group_targets as $computerid) {
                        $computers[$computerid] = 1;
                    }
                    break;

                case 'Group':
                    //find computers by user associated with this group
                    $group_users   = new Group_User();
                    $members       = [];
                    $members       = $group_users->getGroupUsers($itemid);

                    foreach ($members as $member) {
                        $computers_from_user = $computer->find(['users_id' => $member['id']]);
                        foreach ($computers_from_user as $computer_entry) {
                            $computers[$computer_entry['id']] = 1;
                        }
                    }

                    //find computers directly associated with this group
                    $computer_from_group = $computer->find(['groups_id' => $itemid]);
                    foreach ($computer_from_group as $computer_entry) {
                        $computers[$computer_entry['id']] = 1;
                    }
                    break;

                /**
                 * TODO: The following should be replaced with Dynamic groups
                 */
                case Agent::class:
                    $agents[$itemid] = 1;
                    break;
            }
        }

        //Get agents from the computer's ids list
        if (count($computers)) {
            $agents_entries = $agent->find(['itemtype' => 'Computer', 'items_id' => array_keys($computers)]);
            foreach ($agents_entries as $agent_entry) {
                $agents[$agent_entry['id']] = 1;
            }
        }

        // Return the list of agent's ids.
        // (We used hash keys to avoid duplicates in the list)
        return array_keys($agents);
    }
}
