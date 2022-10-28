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
 * Manage the task system.
 */
class PluginGlpiinventoryTask extends PluginGlpiinventoryTaskView
{
   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = 'plugin_glpiinventory_task';

   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
    public static function getTypeName($nb = 0)
    {
        return __('Task management', 'glpiinventory');
    }



   /**
    * Check if user can create a task
    *
    * @return boolean
    */
    public static function canCreate()
    {
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
         'id' => 'common',
         'name' => __('Task')
        ];

        $tab[] = [
         'id'        => '1',
         'table'     => $this->getTable(),
         'field'     => 'name',
         'name'      => __('Name'),
         'datatype'  => 'itemlink',
        ];

        $tab[] = [
         'id'        => '2',
         'table'     => $this->getTable(),
         'field'     => 'datetime_start',
         'name'      => __('Schedule start', 'glpiinventory'),
         'datatype'  => 'datetime',
        ];

        $tab[] = [
         'id'        => '8',
         'table'     => $this->getTable(),
         'field'     => 'datetime_end',
         'name'      => __('Schedule end', 'glpiinventory'),
         'datatype'  => 'datetime',
        ];

        $tab[] = [
         'id'        => '3',
         'table'     => 'glpi_entities',
         'field'     => 'completename',
         'linkfield' => 'entities_id',
         'name'      => Entity::getTypeName(1),
         'datatype'  => 'dropdown',
        ];

        $tab[] = [
         'id'        => '4',
         'table'     => $this->getTable(),
         'field'     => 'comment',
         'name'      => __('Comments'),
        ];

        $tab[] = [
         'id'        => '5',
         'table'     => $this->getTable(),
         'field'     => 'is_active',
         'name'      => __('Active'),
         'datatype'  => 'bool',
        ];

        $tab[] = [
         'id'        => '6',
         'table'     => $this->getTable(),
         'field'     => 'reprepare_if_successful',
         'name'      => __(
             'Permit to re-prepare task after run',
             'glpiinventory'
         ),
         'datatype'  => 'bool',
        ];

        $tab[] = [
         'id'       => '7',
         'table'    => $this->getTable(),
         'field'    => 'is_deploy_on_demand',
         'name'     => __('deploy on demand task', 'glpiinventory'),
         'datatype' => 'bool',
        ];

        $tab[] = [
         'id'        => '30',
         'table'     => $this->getTable(),
         'field'     => 'id',
         'name'      => __('ID'),
         'datatype'  => 'number',
        ];

        return $tab;
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
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = count(PluginGlpiinventoryTaskjob::getTaskfromIPRange($item));
            }
            return self::createTabEntry(__('Associated tasks', 'glpiinventory'), $nb);
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
        $pf_Task = new self();
        $pf_Task->showItemForm($item);
        return true;
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

        if ($item->isNewID($ID) || !$item->can($item->fields['id'], READ)) {
            return false;
        }

        $rand = mt_rand();
        $a_data = PluginGlpiinventoryTaskjob::getTaskfromIPRange($item);

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_2'>";
        echo "<th>";
        echo __('Tasks', 'glpiinventory');
        echo "</th>";
        echo "</tr>";

        $credentials = new PluginGlpiinventoryTask();
        foreach ($a_data as $data) {
            echo "<tr class='tab_bg_2'>";
            echo "<td>";
            $credentials->getFromDB($data['id']);
            echo $credentials->getLink();
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        return true;
    }




   /**
    * Purge elements linked to task when delete it
    *
    * @global object $DB
    * @param object $param
    */
    public static function purgeTask($param)
    {
        global $DB;

        $tasks_id = $param->fields['id'];

       //clean jobslogs
       //DB::delete() does not supports subqueries
        $DB->query("DELETE FROM glpi_plugin_glpiinventory_taskjoblogs
                  WHERE plugin_glpiinventory_taskjobstates_id IN (
                     SELECT states.id
                     FROM glpi_plugin_glpiinventory_taskjobstates AS states
                     INNER JOIN glpi_plugin_glpiinventory_taskjobs AS jobs
                        ON jobs.id = states.plugin_glpiinventory_taskjobs_id
                        AND jobs.plugin_glpiinventory_tasks_id = '$tasks_id'
                  ) ");

       //clean states
       //DB::delete() does not supports subqueries
        $DB->query("DELETE FROM glpi_plugin_glpiinventory_taskjobstates
                  WHERE plugin_glpiinventory_taskjobs_id IN (
                     SELECT jobs.id
                     FROM glpi_plugin_glpiinventory_taskjobs AS jobs
                     WHERE jobs.plugin_glpiinventory_tasks_id = '$tasks_id'
                  )");

       //clean jobs
        $DB->delete(
            'glpi_plugin_glpiinventory_taskjobs',
            [
            'plugin_glpiinventory_tasks_id' => $tasks_id
            ]
        );
    }



   /**
    * Purge all tasks AND taskjob related with method
    *
    * @param string $method
    */
    public static function cleanTasksbyMethod($method)
    {
        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfTask = new PluginGlpiinventoryTask();

        $a_taskjobs = $pfTaskjob->find(['method' => $method]);
        $task_id = 0;
        foreach ($a_taskjobs as $a_taskjob) {
            $pfTaskjob->delete($a_taskjob, 1);
            if (
                ($task_id != $a_taskjob['plugin_glpiinventory_tasks_id'])
                and ($task_id != '0')
            ) {
                // Search if this task have other taskjobs, if not, we will delete it
                $findtaskjobs = $pfTaskjob->find(['plugin_glpiinventory_tasks_id' => $task_id]);
                if (count($findtaskjobs) == '0') {
                    $pfTask->delete(['id' => $task_id], 1);
                }
            }
            $task_id = $a_taskjob['plugin_glpiinventory_tasks_id'];
        }
        if ($task_id != '0') {
           // Search if this task have other taskjobs, if not, we will delete it
            $findtaskjobs = $pfTaskjob->find(['plugin_glpiinventory_tasks_id' => $task_id]);
            if (count($findtaskjobs) == '0') {
                $pfTask->delete(['id' => $task_id], 1);
            }
        }
    }



   /**
    * Get the list of taskjobstate for the agent
    *
    * @global object $DB
    * @param integer $agent_id
    * @param string $methods
    * @param array $options
    * @return array
    */
    public function getTaskjobstatesForAgent($agent_id, $methods = [], $options = [])
    {
        global $DB;

        $pfTimeslot = new PluginGlpiinventoryTimeslot();

        $jobstates = [];

       //Get the datetime of agent request
        $now = new Datetime();

       // list of jobstates not allowed to run (ie. filtered by schedule AND timeslots)
        $jobstates_to_cancel = [];

        $query = implode(" \n", [
         "SELECT",
         "     task.`id`, task.`name`, task.`is_active`,",
         "     task.`datetime_start`, task.`datetime_end`,",
         "     task.`plugin_glpiinventory_timeslots_exec_id` as timeslot_id,",
         "     job.`id`, job.`name`, job.`method`, job.`actors`,",
         "     run.`itemtype`, run.`items_id`, run.`state`,",
         "     run.`id`, run.`agents_id`",
         "FROM `glpi_plugin_glpiinventory_taskjobstates` run",
         "LEFT JOIN `glpi_plugin_glpiinventory_taskjobs` job",
         "  ON job.`id` = run.`plugin_glpiinventory_taskjobs_id`",
         "LEFT JOIN `glpi_plugin_glpiinventory_tasks` task",
         "  ON task.`id` = job.`plugin_glpiinventory_tasks_id`",
         "WHERE",
         "  job.`method` IN ('" . implode("','", $methods) . "')",
         "  AND run.`state` IN ('" . implode("','", [
            PluginGlpiinventoryTaskjobstate::PREPARED,
            PluginGlpiinventoryTaskjobstate::SERVER_HAS_SENT_DATA,
            PluginGlpiinventoryTaskjobstate::AGENT_HAS_SENT_DATA,
         ]) . "')",
         "  AND run.`agents_id` = " . $agent_id,
         // order the result by job.id
         // TODO: the result should be ordered by the future job.index field when drag AND drop
         // feature will be properly activated in the taskjobs list.
         "ORDER BY job.`id`",
        ]);

        $query_result = $DB->query($query);
        $results = [];
        if ($query_result) {
            $results = PluginGlpiinventoryToolbox::fetchAssocByTable($query_result);
        }

       // Fetch a list of unique actors since the same actor can be assigned to many jobs.
        $actors = [];
        foreach ($results as $result) {
            $actors_from_job = importArrayFromDB($result['job']['actors']);
            foreach ($actors_from_job as $actor) {
                $actor_key = "" . key($actor) . "_" . $actor[key($actor)];
                if (!isset($actors[$actor_key])) {
                    $actors[$actor_key] = [];
                    foreach ($this->getAgentsFromActors([$actor], true) as $agent) {
                        $actors[$actor_key][$agent] = true;
                    }
                }
            }
        }

       // Merge agents into one list
        $agents = [];
        foreach ($actors as $agents_list) {
            foreach ($agents_list as $id => $val) {
                if (!isset($agents[$id])) {
                    $agents[$id] = true;
                }
            }
        }
        $agents = array_keys($agents);

       // Get timeslot's entries from this list at the time of the request (ie. get entries according
       // to the day of the week)
        $day_of_week = $now->format("N");

        $timeslot_ids = [];
        foreach ($results as $result) {
            $timeslot_ids[$result['task']['timeslot_id']] = 1;
        }
        $timeslot_entries = $pfTimeslot->getTimeslotEntries(array_keys($timeslot_ids), $day_of_week);

        $timeslot_cursor = $pfTimeslot->getTimeslotCursor($now);

       /**
        * Ensure the agent's jobstates are allowed to run at the time of the agent's request.
        * The following checks if:
        * - The tasks associated with those taskjobs are not disabled.
        * - The task's schedule AND timeslots still match the time those jobstates have been
        * requested.
        * - The agent is still present in the dynamic actors (eg. Dynamic groups)
        */
        foreach ($results as $result) {
            $jobstate = new PluginGlpiinventoryTaskjobstate();
            $jobstate->getFromDB($result['run']['id']);

           // Cancel the job if has already been sent to the agent but the agent did not replied
            if (
                $result['run']['state'] == PluginGlpiinventoryTaskjobstate::SERVER_HAS_SENT_DATA
                 or $result['run']['state'] == PluginGlpiinventoryTaskjobstate::AGENT_HAS_SENT_DATA
            ) {
                $jobstates_to_cancel[$jobstate->fields['id']] = [
                'jobstate' => $jobstate,
                'reason'   => __(
                    "The agent is requesting a configuration that has already been sent to him by the server. It is more likely that the agent is subject to a critical error.",
                    'glpiinventory'
                ),
                 'code'     => $jobstate::IN_ERROR
                ];
                continue;
            }

           // Cancel the jobstate if the related tasks has been deactivated
            if ($result['task']['is_active'] == 0) {
                $jobstates_to_cancel[$jobstate->fields['id']] = [
                'jobstate' => $jobstate,
                'reason'   => __(
                    'The task has been deactivated after preparation of this job.',
                    'glpiinventory'
                )
                ];
                continue;
            };

           // Cancel the jobstate if it the schedule doesn't match.
            if (!is_null($result['task']['datetime_start'])) {
                $schedule_start = new DateTime($result['task']['datetime_start']);

                if (!is_null($result['task']['datetime_end'])) {
                    $schedule_end = new DateTime($result['task']['datetime_end']);
                } else {
                    $schedule_end = $now;
                }

                if (!($schedule_start <= $now and $now <= $schedule_end)) {
                    $jobstates_to_cancel[$jobstate->fields['id']] = [
                    'jobstate' => $jobstate,
                    'reason'   => __(
                        "This job can not be executed anymore due to the task's schedule.",
                        'glpiinventory'
                    )
                    ];
                    continue;
                }
            }

           // Cancel the jobstate if it is requested outside of any timeslot.
            $timeslot_id = $result['task']['timeslot_id'];

           // Do nothing if there are no defined timeslots for this jobstate.
            if ($timeslot_id > 0) {
                $timeslot_matched = false;

               // We do nothing if there are no timeslot_entries, meaning this jobstate is not allowed
               // to be executed at the day of request.
                if (array_key_exists($timeslot_id, $timeslot_entries)) {
                    foreach ($timeslot_entries[$timeslot_id] as $timeslot_entry) {
                        if (
                            $timeslot_entry['begin'] <= $timeslot_cursor
                            and $timeslot_cursor <= $timeslot_entry['end']
                        ) {
                          //The timeslot cursor (ie. time of request) matched a timeslot entry so we can
                          //break the loop here.
                            $timeslot_matched = true;
                            break;
                        }
                    }
                }
               // If no timeslot matched, cancel this jobstate.
                if (!$timeslot_matched) {
                    $jobstates_to_cancel[$jobstate->fields['id']] = [
                    'jobstate' => $jobstate,
                    'reason'   => __(
                        "This job can not be executed anymore due to the task's timeslot.",
                        'glpiinventory'
                    )
                    ];
                    continue;
                }
            }

           // Make sure the agent is still present in the list of actors that generated
           // this jobstate.
           // TODO: If this jobstate needs to be cancelled, it would be worth to point out which actor
           // is the source of this execution. To do this, we need to track the 'actor_source' in the
           // jobstate when it's generated by prepareTaskjobs().

           //$job_actors = importArrayFromDB($result['job']['actors']);
            if (!in_array($agent_id, $agents)) {
                $jobstates_to_cancel[$jobstate->fields['id']] = [
                'jobstate' => $jobstate,
                'reason'   => __(
                    'This agent does not belong anymore in the actors defined in the job.',
                    'glpiinventory'
                )
                ];
                continue;
            }

           //TODO: The following method (actually defined as member of taskjob) needs to be
           //initialized when getting the jobstate from DB (with a getfromDB hook for example)
            $jobstate->method = $result['job']['method'];

           //Add the jobstate to the list since previous checks are good.
            $jobstates[$jobstate->fields['id']] = $jobstate;
        }

       //Remove the list of jobstates previously filtered for removal.
        foreach ($jobstates_to_cancel as $jobstate) {
            if (!isset($jobstate['code'])) {
                $jobstate['code'] = PluginGlpiinventoryTaskjobstate::CANCELLED;
            }
            switch ($jobstate['code']) {
                case PluginGlpiinventoryTaskjobstate::IN_ERROR:
                    $jobstate['jobstate']->fail($jobstate['reason']);
                    break;

                default:
                    $jobstate['jobstate']->cancel($jobstate['reason']);
                    break;
            }
        }
        return $jobstates;
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
            $query_timeslot = '';
        } else {
            $query_timeslot = "OR (`plugin_glpiinventory_timeslots_prep_id` IN (" . implode(',', $timeslots) . "))";
        }

       //transform methods array into string for database query
        $methods = "'" . implode("','", $methods) . "'";

       // limit preparation to a specific tasks_id
        $sql_task_id = "";
        if ($tasks_id) {
            $sql_task_id = "AND `task`.`id` = $tasks_id";
        }

        $query = implode(" \n", [
         "SELECT",
         "     task.`id`, task.`name`, task.`reprepare_if_successful`, ",
         "     job.`id`, job.`name`, job.`method`, ",
         "     job.`targets`, job.`actors`,job.`restrict_to_task_entity`",
         "FROM `glpi_plugin_glpiinventory_taskjobs` job",
         "LEFT JOIN `glpi_plugin_glpiinventory_tasks` task",
         "  ON task.`id` = job.`plugin_glpiinventory_tasks_id`",
         "WHERE task.`is_active` = 1",
         $sql_task_id,
         "AND (",
         /**
          * Filter jobs by the schedule AND timeslots
          */
         // check only if now() >= datetime_start if datetime_end is null
         "        (   task.`datetime_start` IS NOT NULL AND task.`datetime_end` IS NULL",
         "              AND '" . $now->format("Y-m-d H:i:s") . "' >= task.`datetime_start` )",
         "     OR",
         // check if now() is between datetime_start AND datetime_end
         "        (   task.`datetime_start` IS NOT NULL AND task.`datetime_end` IS NOT NULL",
         "              AND '" . $now->format("Y-m-d H:i:s") . "' ",
         "                    between task.`datetime_start` AND task.`datetime_end` )",
         "     OR",
         // finally, check if this task can be run at any time ( datetime_start and datetime_end are
         // both null)
         "        ( task.`datetime_start` IS NULL AND task.`datetime_end` IS NULL )",
         ")",
         "AND job.`method` IN (" . $methods . ")
         AND (`plugin_glpiinventory_timeslots_prep_id`='0'
              $query_timeslot)",
         // order the result by job.id
         // TODO: the result should be ordered by the future job.index field when drag and drop
         // feature will be properly activated in the taskjobs list.
         "ORDER BY job.`id`",
        ]);

        $query_result = $DB->query($query);
        $results      = [];
        if ($query_result) {
            $results = PluginGlpiinventoryToolbox::fetchAssocByTable($query_result);
        }

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



   /**
    * Cron task: prepare taskjobs
    *
    * @return true
    */
    public static function cronTaskscheduler()
    {

        ini_set("max_execution_time", "0");

        $task    = new self();
        $methods = [];
        foreach (PluginGlpiinventoryStaticmisc::getmethods() as $method) {
            $methods[] = $method['method'];
        }

        $task->prepareTaskjobs($methods);
        return true;
    }

   /**
    * Cron task: prepare taskjobs
    *
    * @return true
    */
    public static function cronCleanOnDemand($task = null)
    {
        global $DB;

        $config   = new PluginGlpiinventoryConfig();
        $interval = $config->getValue('clean_on_demand_tasks');

       //If crontask is disabled, quit method
        if (!$interval < 0) {
            return true;
        }

        $pfTask = new self();
        $index  = $pfTask->cleanTasksAndJobs($interval);
        $task->addVolume($index);
        return true;
    }

   /**
   * Get all on demand tasks to clean
   * @param $interval number of days to look for successful tasks
   * @return an array of tasks ID to clean
   */
    public function cleanTasksAndJobs($interval)
    {
        global $DB;

        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $pfTask         = new PluginGlpiinventoryTask();

        $index = 0;

       //Delete taskstates that are too old
        $date  = "SELECT DISTINCT state.`id` as 'id'
                FROM `glpi_plugin_glpiinventory_taskjoblogs` AS log
                LEFT JOIN `glpi_plugin_glpiinventory_taskjobstates` AS state
                  ON (state.`id` = log.`plugin_glpiinventory_taskjobstates_id`)
               LEFT JOIN `glpi_plugin_glpiinventory_taskjobs` AS job
                 ON (job.`id` = state.`plugin_glpiinventory_taskjobs_id`)
                LEFT JOIN `glpi_plugin_glpiinventory_tasks` AS task
                  ON (task.`id` = job.`plugin_glpiinventory_tasks_id`)
                WHERE task.`is_deploy_on_demand`='1'
                   AND DATEDIFF(ADDDATE(log.`date`,
                                INTERVAL " . $interval . " DAY),
                                CURDATE()) < '0'
                   AND `state`.`state` IN (3, 4, 5)";

        foreach ($DB->request($date) as $data) {
            $pfTaskjobstate->delete($data, true);
            $index++;
        }

       //Check if a task has jobstates. In case not, delete the task
        foreach (
            $DB->request(
                'glpi_plugin_glpiinventory_tasks',
                ['is_deploy_on_demand' => 1]
            ) as $task
        ) {
            $query = "SELECT COUNT(*) as cpt
                   FROM `glpi_plugin_glpiinventory_taskjobstates` as state
                   LEFT JOIN `glpi_plugin_glpiinventory_taskjobs` AS job
                      ON (job.`id` = state.`plugin_glpiinventory_taskjobs_id`)
                   WHERE job.`plugin_glpiinventory_tasks_id`='" . $task['id'] . "'";
            $result = $DB->query($query);

            if ($DB->result($result, 0, "cpt") == 0) {
                $index++;
                $pfTask->delete(['id' => $task['id']], true);
            }
        }

        return $index;
    }

   /**
    * Give cron information
    *
    * @param $name : task's name
    *
    * @return arrray of information
   **/
    public static function cronInfo($name)
    {

        switch ($name) {
            case 'taskScheduler':
                return ['description' => __('Inventory task scheduler')];

            case 'cleanOnDemand':
                return ['description' => __('Clean on demand deployment tasks')];
        }
        return [];
    }


   /**
    * Format chrono (interval) in hours, minutes, seconds, microseconds string
    *
    * @param array $chrono
    * @return string
    */
    public static function formatChrono($chrono)
    {
        $interval = abs($chrono['end'] - $chrono['start']);
        $micro    = intval($interval * 100);
        $seconds  = fmod($interval, 60);
        $minutes  = intval($interval / 60);
        $hours    = intval($interval / 60 / 60);
        return "${hours}h ${minutes}m ${seconds}s ${micro}µs";
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
    * Get logs of job
    *
    * Returns a map array containing: ['tasks' => $logs, 'agents' => $agents]
    * - tasks: is a map containing the objects of a task
    * - agents: is a list of the agents involved in the tasks jobs
    *
    * @global object $DB
    * @param array $task_ids list of tasks id
    * @param bool $with_logs default to true to get jobs execution logs with the jobs states
    * @param bool $only_active, set to true to include only active tasks
    * @return array
    */
    public function getJoblogs($task_ids = [], $with_logs = true, $only_active = false)
    {
        global $DB;

       // Results grouped by tasks > jobs > jobstates
        $logs = [];
       // Agents concerned by the logs
        $agents = [];

       // The concerned tasks list
        if (is_array($task_ids) && count($task_ids) > 0) {
            $tasks_list = "AND task.`id` IN ('" . implode("', '", $task_ids) . "')";
        } else {
            // Not task identifiers provided
            $tasks_list = "";
        }

       // Restrict by IP to prevent display tasks in another entity use not have right
        $entity_restrict_task = '';
        if (isset($_SESSION['glpiactiveentities_string'])) {
            $entity_restrict_task = getEntitiesRestrictRequest("AND", 'task');
        }

        PluginGlpiinventoryToolbox::logIfExtradebug(
            "pluginGlpiinventory-tasks",
            "Get tasks jobs log, tasks list: " . implode(",", $task_ids)
        );

        $prepare_chrono = [
         "start" => microtime(true),
         "end"   => 0
        ];

       // We get list of taskjobs
        $active_task = $only_active ? "AND task.`is_active`='1'" : "";

        $data_structure = [
         'query' => "SELECT
            `job`.`id` as 'job.id',
            `job`.`name` as 'job.name',
            `job`.`method` as 'job.method',
            `job`.`targets` as 'job.targets',
            `task`.`id` as 'task.id',
            `task`.`name` as 'task.name',
            `job`.`restrict_to_task_entity` as 'job.restrict_to_task_entity'
            FROM `glpi_plugin_glpiinventory_taskjobs` as job
            LEFT JOIN `glpi_plugin_glpiinventory_tasks` as task
              ON job.`plugin_glpiinventory_tasks_id` = task.`id` $active_task $tasks_list $entity_restrict_task
            WHERE task.`id` IS NOT NULL ",
         'result' => null,
         "start" => microtime(true),
         "end"   => 0
        ];

        $data_structure['result'] = $DB->query($data_structure['query']);

        PluginGlpiinventoryToolbox::logIfExtradebug(
            "pluginGlpiinventory-tasks",
            "Preparing query: " . print_r($data_structure, true)
        );

        if ($data_structure['result']->num_rows <= 0) {
            // Not useful to go further, we will not have any result to send!
            // Perharps the required tasks are not even active ;)
            return ['tasks' => $logs, 'agents' => $agents];
        }

       // Target cache (used to speed up data formatting)
        $expanded = [];
        if (isset($_SESSION['plugin_glpiinventory_tasks_expanded'])) {
            $expanded = $_SESSION['plugin_glpiinventory_tasks_expanded'];
        }

        $agent_state_types = [
         'agents_prepared',
         'agents_cancelled',
         'agents_running',
         'agents_success',
         'agents_error',
         'agents_notdone'
        ];
        while ($result = $data_structure['result']->fetch_assoc()) {
           // ***** Begin loop for each taskjob ***** //

            PluginGlpiinventoryToolbox::logIfExtradebug(
                "pluginGlpiinventory-tasks",
                "Job: " . print_r($result, true)
            );

            $task_id = $result['task.id'];
            if (!array_key_exists($task_id, $logs)) {
                $logs[$task_id] = [
                 'task_name' => $result['task.name'],
                 'task_id'   => $result['task.id'],
                 'expanded'  => false,
                 'jobs'      => []
                ];
            }

            if (isset($expanded[$task_id])) {
                $logs[$task_id]['expanded'] = $expanded[$task_id];
            }

            $job_id = $result['job.id'];
            $jobs_handle = &$logs[$task_id]['jobs'];
            if (!array_key_exists($job_id, $jobs_handle)) {
                $jobs_handle[$job_id] = [
                'name'    => $result['job.name'],
                'id'      => $result['job.id'],
                'method'  => $result['job.method'],
                'targets' => []
                ];
            }
            $targets = importArrayFromDB($result['job.targets']);
            $targets_handle = &$jobs_handle[$job_id]['targets'];

           // ***** special case for IPRanges of networkinventory ***** //

            if ($result['job.method'] == 'networkinventory') {
                $newtargets = [];
                $pfNetworkinventory = new PluginGlpiinventoryNetworkinventory();
                foreach ($targets as $keyt => $target) {
                    $item_type = key($target);
                    $items_id  = current($target);
                    if ($item_type == 'PluginGlpiinventoryIPRange') {
                        unset($targets[$keyt]);
                        // In this case get devices of this iprange
                        $deviceList = $pfNetworkinventory->getDevicesOfIPRange($items_id, $result['job.restrict_to_task_entity']);
                        $newtargets = array_merge($newtargets, $deviceList);
                    }
                }
                $targets = array_merge($targets, $newtargets);
            }

           // ***** loop on each target of the job ***** //

            foreach ($targets as $target) {
                PluginGlpiinventoryToolbox::logIfExtradebug(
                    "pluginGlpiinventory-tasks",
                    "- target: " . print_r($target, true)
                );

                $item_type = key($target);
                $item_id   = current($target);
                $item_name = "";
                if (strpos($item_id, '$#$') !== false) {
                     list($item_id, $item_name) = explode('$#$', $item_id);
                }

                $target_id = $item_type . "_" . $item_id;
                if ($item_name == "") {
                    $item = new $item_type();
                    if ($item->getFromDB($item_id)) {
                        $item_name = $item->fields['name'];
                    }
                }
                $targets_handle[$target_id] = [
                'id'        => $item_id,
                'name'      => $item_name,
                'type_name' => $item_type::getTypeName(),
                'item_link' => $item_type::getFormURLWithID($item_id, true),
                'counters'  => [],
                'agents'    => []
                ];
               // create agent states counter lists
                foreach ($agent_state_types as $type) {
                    $targets_handle[$target_id]['counters'][$type] = [];
                }
            }
        }

        $prepare_chrono['end'] = microtime(true);
        $prepare_chrono['duration'] = self::formatChrono($prepare_chrono);

        PluginGlpiinventoryToolbox::logIfExtradebug(
            "pluginGlpiinventory-tasks",
            "Prepared: " . print_r($logs, true)
        );

        PluginGlpiinventoryToolbox::logIfExtradebug(
            "pluginGlpiinventory-tasks",
            $prepare_chrono
        );

       // How many run log must we provide ?
        $max_runs = 1;
        if (isset($_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'])) {
            if ($_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'] >= 1) {
                $max_runs = $_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'];
            }
        }

       /*
        * The query is a template to get the log of a specific job execution. This query is run for each job
        * state to get the execution log.
        */
        $q_job_state_last_log = [
         'query' => "SELECT
             `log`.`id` as 'log.last_id',
             `log`.`date` as 'log.last_date',
             `log`.`comment` as 'log.last_comment',
              log.`plugin_glpiinventory_taskjobstates_id` as run_id,
             UNIX_TIMESTAMP(log.`date`) as 'log.last_timestamp'
             FROM `glpi_plugin_glpiinventory_taskjoblogs` AS log
             WHERE log.`plugin_glpiinventory_taskjobstates_id` in ('RUN.ID')
             ORDER BY log.`id` DESC",
         'result' => null,
         "start" => microtime(true),
         "end"   => 0
        ];

       // Get all jobs id of this tasks_id
        $pftaskjob = new PluginGlpiinventoryTaskjob();

       // Parse the query result to update the data to return
        $tasks_list1 = [];
        if (is_array($task_ids) && count($task_ids) > 0) {
            $tasks_list1 += ['plugin_glpiinventory_tasks_id' => $task_ids];
        }
        $taskjobs = $pftaskjob->find($tasks_list1);
        $counter_agents = [];
        foreach ($taskjobs as $taskjob) {
           // get taskjobstates
            $query_job_state = "SELECT `glpi_plugin_glpiinventory_taskjobstates`.id, state,
             glpi_plugin_glpiinventory_taskjobstates.itemtype, glpi_plugin_glpiinventory_taskjobstates.items_id,
             `agents_id` as 'agent.id',
             `agent`.`name` as 'agent.name',
             `agent`.`items_id` as 'agent.computers_id'
             FROM `glpi_plugin_glpiinventory_taskjobstates`
             LEFT JOIN `glpi_agents` AS agent
                ON agent.`id` = `agents_id`
             WHERE `glpi_plugin_glpiinventory_taskjobstates`.`plugin_glpiinventory_taskjobs_id` = '" . $taskjob['id'] . "'
             AND agent.itemtype = 'Computer'
             ORDER BY glpi_plugin_glpiinventory_taskjobstates.id desc";

           // Execute query to get all jobs states - log the query result
            $q_task_job_state['start'] = microtime(true);
            $q_task_job_state['result'] = $DB->query($query_job_state);
            $q_task_job_state['end'] = microtime(true);
            $q_task_job_state['duration'] = self::formatChrono($q_task_job_state);

            PluginGlpiinventoryToolbox::logIfExtradebug(
                "pluginGlpiinventory-tasks",
                $q_task_job_state
            );

            $runs_id = [];

           // Parse the query result to update the data to return
            $count_results = 0;
            while ($result = $q_task_job_state['result']->fetch_assoc()) {
                PluginGlpiinventoryToolbox::logIfExtradebug(
                    "pluginGlpiinventory-tasks",
                    "Result: " . print_r($result, true)
                );

                 // ***** create a unique key ***** //

                 $key_runs = $result['agent.id'] . "+" . $result['items_id'] . "+" . $result['itemtype'];
                if (!isset($counter_agents[$key_runs])) {
                     $counter_agents[$key_runs] = 0;
                }
                 $counter_agents[$key_runs]++;
                if ($counter_agents[$key_runs] > $max_runs) {
                    continue;
                }

                 // We need to check if the results are consistent with the view's structure gathered
                 // by the first query
                 $task_id = $taskjob['plugin_glpiinventory_tasks_id'];
                if (!isset($logs[$task_id])) {
                    continue;
                }
                $job_id = $taskjob['id'];
                $jobs   = &$logs[$task_id]['jobs'];
                if (!isset($jobs[$job_id])) {
                    continue;
                }
                $target_id = $result['itemtype'] . '_' . $result['items_id'];
                $targets   = &$jobs[$job_id]['targets'];
                if (!isset($targets[$target_id])) {
                     continue;
                }

                $count_results += 1;

                $counters = &$targets[$target_id]['counters'];
                $agent_id = $result['agent.id'];
               // This to be updated if needed!
                $agents[$agent_id] = $result['agent.name'];

                if (!isset($targets[$target_id]['agents'][$agent_id])) {
                    $targets[$target_id]['agents'][$agent_id] = [];
                }
                $agent_state = '';
                $run_id = $result['id'];

              // Update counters

                switch ($result['state']) {
                    case PluginGlpiinventoryTaskjobstate::CANCELLED:
                      // We put this agent in the cancelled counter
                      // if it does not have any other job states.
                        if (
                            !isset($counters['agents_prepared'][$agent_id])
                            && !isset($counters['agents_running'][$agent_id])
                        ) {
                            $counters['agents_cancelled'][$agent_id] = $run_id;
                            $agent_state = 'cancelled';
                        }
                        break;

                    case PluginGlpiinventoryTaskjobstate::PREPARED:
                     // We put this agent in the prepared counter
                     // if it has not yet completed any job.
                        $counters['agents_prepared'][$agent_id] = $run_id;
                        $agent_state = 'prepared';

                     // drop running counter for agent if preparation more recent
                        if (
                            isset($counters['agents_running'][$agent_id])
                            && $counters['agents_running'][$agent_id] < $run_id
                        ) {
                            unset($counters['agents_running'][$agent_id]);
                        }

                     // drop cancelled counter for agent if preparation more recent
                        if (
                            isset($counters['agents_cancelled'][$agent_id])
                            && $counters['agents_cancelled'][$agent_id] < $run_id
                        ) {
                            unset($counters['agents_cancelled'][$agent_id]);
                        }
                        break;

                    case PluginGlpiinventoryTaskjobstate::SERVER_HAS_SENT_DATA:
                    case PluginGlpiinventoryTaskjobstate::AGENT_HAS_SENT_DATA:
                     // This agent is running so it must not be in any other counter
                     // remove older counters
                        foreach ($agent_state_types as $type) {
                            if (
                                isset($counters[$type][$agent_id])
                                && $counters[$type][$agent_id] < $run_id
                            ) {
                                unset($counters[$type][$agent_id]);
                            }
                        }
                        $counters['agents_running'][$agent_id] = $run_id;
                        $agent_state = 'running';
                        break;

                    case PluginGlpiinventoryTaskjobstate::IN_ERROR:
                        // drop older success
                        if (
                            isset($counters['agents_success'][$agent_id])
                            && $counters['agents_success'][$agent_id] < $run_id
                        ) {
                            unset($counters['agents_success'][$agent_id]);
                        }

                        // if we don't have success run (more recent due to previous test)
                        // so we are really in error
                        if (!isset($counters['agents_success'][$agent_id])) {
                            $counters['agents_error'][$agent_id] = $run_id;
                            unset($counters['agents_notdone'][$agent_id]);
                        }

                        $agent_state = 'error';
                        break;

                    case PluginGlpiinventoryTaskjobstate::FINISHED:
                      // drop older error
                        if (
                            isset($counters['agents_error'][$agent_id])
                            && $counters['agents_error'][$agent_id] < $run_id
                        ) {
                            unset($counters['agents_error'][$agent_id]);
                        }

                      // if we don't have error run (more recent due to previous test)
                      // so we are really in success
                        if (!isset($counters['agents_error'][$agent_id])) {
                            $counters['agents_success'][$agent_id] = $run_id;
                            unset($counters['agents_notdone'][$agent_id]);
                        }

                        $agent_state = 'success';
                        break;
                }
                if (
                    !isset($counters['agents_error'][$agent_id])
                    && !isset($counters['agents_success'][$agent_id])
                ) {
                    $counters['agents_notdone'][$agent_id] = $run_id;
                }
                if (
                    isset($counters['agents_running'][$agent_id])
                    || isset($counters['agents_prepared'][$agent_id])
                ) {
                    unset($counters['agents_cancelled'][$agent_id]);
                }
                if ($with_logs) {
                    $runs_id[$run_id] = [
                    'agent_id' => $agent_id,
                    'link'     => Computer::getFormURLWithID($result['agent.computers_id']),
                    'numstate' => $result['state'],
                    'state'    => $agent_state,
                    'jobs_id'  => $job_id,
                    'task_id'  => $task_id,
                    'target_id' => $target_id
                    ];
                }
            }
            if ($with_logs && count($runs_id) > 0) {
                $query = $q_job_state_last_log['query'];
                $query = str_replace('RUN.ID', implode("', '", array_keys($runs_id)), $query);
                $q_job_state_last_log['real_query'] = $query;
                $q_job_state_last_log['result'] = $DB->query($query);

                $q_job_state_last_log['end'] = microtime(true);
                $q_job_state_last_log['duration'] = self::formatChrono($q_job_state_last_log);

                PluginGlpiinventoryToolbox::logIfExtradebug(
                    "pluginGlpiinventory-tasks",
                    "Log query: " . print_r($q_job_state_last_log, true)
                );

                while ($log_result = $q_job_state_last_log['result']->fetch_assoc()) {
                     PluginGlpiinventoryToolbox::logIfExtradebug(
                         "pluginGlpiinventory-tasks",
                         "Log: " . print_r($log_result, true)
                     );

                     $run_id = $log_result['run_id'];
                     $run_data = $runs_id[$run_id];

                     $jobs    = &$logs[$run_data['task_id']]['jobs'];
                     $targets = &$jobs[$run_data['jobs_id']]['targets'];

                     $targets[$run_data['target_id']]['agents'][$run_data['agent_id']][] = [
                        'agent_id'      => $run_data['agent_id'],
                        'link'          => $run_data['link'],
                        'numstate'      => $run_data['numstate'],
                        'state'         => $run_data['state'],
                        'jobstate_id'   => $run_id,
                        'last_log_id'   => $log_result['log.last_id'],
                        'last_log_date' => $log_result['log.last_date'],
                        'timestamp'     => $log_result['log.last_timestamp'],
                        'last_log'      => PluginGlpiinventoryTaskjoblog::convertComment($log_result['log.last_comment'])
                     ];
                }
            }
        }

        PluginGlpiinventoryToolbox::logIfExtradebug(
            "pluginGlpiinventory-tasks",
            "Got $count_results results"
        );

        PluginGlpiinventoryToolbox::logIfExtradebug(
            "pluginGlpiinventory-tasks",
            ['tasks' => $logs, 'agents' => $agents]
        );

        return ['tasks' => $logs, 'agents' => $agents];
    }

   /**
    * Ajax called to get job logs
    *
    * @param  array  $options these possible entries
    *                          - task_id (mandatory), the current task id
    *                          - includeoldjobs: the value of "include old jobs" list
    *                          - refresh: the value of "refresh interval" list
    *                          - display: true for direct display of JSON result else returns a JSON encoded string
    *
    * @return depends on @param $options['display'].
    * @return string, empty if JSON results are displayed
    */
    public function ajaxGetJobLogs($options = [])
    {
        if (!empty($options['task_id'])) {
            if (is_array($options['task_id'])) {
                $task_ids = $options['task_id'];
            } else {
                $task_ids = [$options['task_id']];
            }
        } else {
            $task_ids = [];
        }

        if (isset($options['includeoldjobs'])) {
            $_SESSION['glpi_plugin_glpiinventory']['includeoldjobs'] = $options['includeoldjobs'];
        }

        if (isset($options['refresh'])) {
            $_SESSION['glpi_plugin_glpiinventory']['refresh'] = $options['refresh'];
        }

       //unlock session since access checks have been done (to avoid lock another page)
        session_write_close();

        $logs = $this->getJoblogs($task_ids, true, false);
        PluginGlpiinventoryToolbox::logIfExtradebug(
            "pluginGlpiinventory-tasks",
            "ajaxGetJobLogs, agents: " . count($logs['agents'])
        );
        PluginGlpiinventoryToolbox::logIfExtradebug(
            "pluginGlpiinventory-tasks",
            "ajaxGetJobLogs, tasks: " . count($logs['tasks'])
        );

        PluginGlpiinventoryToolbox::logIfExtradebug(
            "pluginGlpiinventory-tasks",
            "ajaxGetJobLogs: " . print_r($logs, true)
        );
        $out = json_encode($logs);
        if (
            isset($options['display'])
            and !$options['display']
        ) {
            return $out;
        } else {
            echo $out;
            return '';
        }
    }



   /**
    * Get tasks planned
    *
    * @global object $DB
    * @param integer $tasks_id if 0, no restriction so get all
    * @param bool $only_active, set to true to include only active tasks
    * @return object
    */
    public function getTasksPlanned($tasks_id = 0, $only_active = true)
    {
        global $DB;

        $where = '';
        $where .= getEntitiesRestrictRequest("AND", 'task');
        if ($tasks_id > 0) {
            $where = " AND task.`id`='" . $tasks_id . "'
            LIMIT 1 ";
        }

       // Include tasks that are not active
        $active_task = $only_active ? "AND `is_active`='1'" : "";
        $query = "SELECT * FROM `glpi_plugin_glpiinventory_tasks` as task
         WHERE execution_id =
            (SELECT execution_id FROM glpi_plugin_glpiinventory_taskjobs as taskjob
               WHERE taskjob.`plugin_glpiinventory_tasks_id`=task.`id`
               ORDER BY execution_id DESC
               LIMIT 1
            )
            $active_task
            AND `periodicity_count` > 0
            AND `periodicity_type` != '0' " . $where;
        return $DB->query($query);
    }



   /**
    * Get tasks filtered by relevant criteria
    *
    * @global object $DB
    * @param array $filter criteria to filter in the request
    * @return array
    */
    public static function getItemsFromDB($filter)
    {
        global $DB;

        $select = ["tasks" => "task.*"];
        $where = [];
        $leftjoin = [];

       // Filter active tasks
        if (
            isset($filter['is_active'])
              and is_bool($filter['is_active'])
        ) {
            $where[] = "task.`is_active` = " . $filter['is_active'];
        }

       //Filter by running taskjobs
        if (
            isset($filter['is_running'])
              and is_bool($filter['is_running'])
        ) {
           // add taskjobs table JOIN statement if not already set
            if (!isset($leftjoin['taskjobs'])) {
                $leftjoin_bak = $leftjoin;
                $leftjoin_tmp = PluginGlpiinventoryTaskjob::getJoinQuery();
                $leftjoin = array_merge($leftjoin_bak, $leftjoin_tmp);
                if (!isset($select["taskjobs"])) {
                    $select['taskjobs'] = "taskjob.*";
                }
            }
            $where[] = "`taskjob`.`id` IS NOT NULL";
        }

       //Filter by targets classes
        if (
            isset($filter['targets'])
              and is_array($filter['targets'])
        ) {
            $where_tmp = [];
           //check classes existence AND append them to the query filter
            foreach ($filter['targets'] as $itemclass => $itemid) {
                if (class_exists($itemclass)) {
                    $cond = "taskjob.`targets` LIKE '%\"" . $itemclass . "\"";
                    //adding itemid if not empty
                    if (!empty($itemid)) {
                        $cond .= ":\"" . $itemid . "\"";
                    }
                    //closing LIKE statement
                    $cond .= "%'";
                    $where_tmp[] = $cond;
                }
            }
           //join every filtered conditions
            if (count($where_tmp) > 0) {
               // add taskjobs table JOIN statement if not already set
                if (!isset($leftjoin['taskjobs'])) {
                    $leftjoin_bak = $leftjoin;
                    $leftjoin_tmp = PluginGlpiinventoryTaskjob::getJoinQuery();
                    $leftjoin = array_merge($leftjoin_bak, $leftjoin_tmp);
                }
                if (!isset($select["taskjobs"])) {
                    $select['taskjobs'] = "taskjob.*";
                }
                $where[] = "( " . implode("OR", $where_tmp) . " )";
            }
        }

       // Filter by actors classes
        if (
            isset($filter['actors'])
            and is_array($filter['actors'])
        ) {
            $where_tmp = [];
           //check classes existence AND append them to the query filter
            foreach ($filter['actors'] as $itemclass => $itemid) {
                if (class_exists($itemclass)) {
                    $cond = "taskjob.`actors` LIKE '%\"" . $itemclass . "\"";

                    //adding itemid if not empty
                    if (!empty($itemid)) {
                        $cond .= ":\"" . $itemid . "\"";
                    }
                    //closing LIKE statement
                    $cond .= "%'";
                    $where_tmp[] = $cond;
                }
            }
           //join every filtered conditions
            if (count($where_tmp) > 0) {
               // add taskjobs table JOIN statement if not already set
                if (!isset($leftjoin['taskjobs'])) {
                    $leftjoin_bak = $leftjoin;
                    $leftjoin_tmp = PluginGlpiinventoryTaskjob::getJoinQuery();
                    $leftjoin = array_merge($leftjoin_bak, $leftjoin_tmp);
                }
                if (!isset($select["taskjobs"])) {
                    $select['taskjobs'] = "taskjob.*";
                }
                $where[] = "( " . implode("OR", $where_tmp) . " )";
            }
        }

       // Filter by entity
        if (
            isset($filter['by_entities'])
            and (bool)$filter['by_entities']
        ) {
            $where[] = getEntitiesRestrictRequest("", 'task');
        }

        $query =
         implode(
             "\n",
             [
               "SELECT " . implode(',', $select),
               "FROM `glpi_plugin_glpiinventory_tasks` as task",
               implode("\n", $leftjoin),
               "WHERE\n    " . implode("\nAND ", $where)
             ]
         );

        $results = [];
        $r = $DB->query($query);
        if ($r) {
            $results = PluginGlpiinventoryToolbox::fetchAssocByTable($r);
        }
        return $results;
    }



   /**
    * Get tasks in error
    *
    * @global object $DB
    * @return object
    */
    public function getTasksInerror()
    {
        global $DB;

        $where = '';
        $where .= getEntitiesRestrictRequest("AND", 'glpi_plugin_glpiinventory_tasks');

        $query = "SELECT `glpi_plugin_glpiinventory_tasks`.*
         FROM `glpi_plugin_glpiinventory_tasks`
         LEFT JOIN `glpi_plugin_glpiinventory_taskjobs` AS taskjobs
            ON `plugin_glpiinventory_tasks_id` = `glpi_plugin_glpiinventory_tasks`.`id`
         LEFT JOIN `glpi_plugin_glpiinventory_taskjobstates` AS taskjobstates
            ON taskjobstates.`id` =
            (SELECT MAX(`id`)
             FROM glpi_plugin_glpiinventory_taskjobstates
             WHERE plugin_glpiinventory_taskjobs_id = taskjobs.`id`
             ORDER BY id DESC
             LIMIT 1
            )
         LEFT JOIN `glpi_plugin_glpiinventory_taskjoblogs`
            ON `glpi_plugin_glpiinventory_taskjoblogs`.`id` =
            (SELECT MAX(`id`)
            FROM `glpi_plugin_glpiinventory_taskjoblogs`
            WHERE `plugin_glpiinventory_taskjobstates_id`= taskjobstates.`id`
            ORDER BY id DESC LIMIT 1 )
         WHERE `glpi_plugin_glpiinventory_taskjoblogs`.`state`='4'
         " . $where . "
         GROUP BY plugin_glpiinventory_tasks_id
         ORDER BY `glpi_plugin_glpiinventory_taskjoblogs`.`date` DESC";

        return $DB->query($query);
    }



   /**
    * Do actions after updated the item
    *
    * @global object $DB
    * @param integer $history
    */
    public function post_updateItem($history = 1)
    {
        global $DB;

        if (
            isset($this->oldvalues['is_active'])
              and $this->oldvalues['is_active'] == 1
        ) {
           // If disable task, must end all taskjobstates prepared
            $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
            $query = implode(" \n", [
            "SELECT",
            "     task.`id`, task.`name`, task.`is_active`,",
            "     task.`datetime_start`, task.`datetime_end`,",
            "     task.`plugin_glpiinventory_timeslots_prep_id` as timeslot_id,",
            "     job.`id`, job.`name`, job.`method`, job.`actors`,",
            "     run.`itemtype`, run.`items_id`, run.`state`,",
            "     run.`id`, run.`agents_id`",
            "FROM `glpi_plugin_glpiinventory_taskjobstates` run",
            "LEFT JOIN `glpi_plugin_glpiinventory_taskjobs` job",
            "  ON job.`id` = run.`plugin_glpiinventory_taskjobs_id`",
            "LEFT JOIN `glpi_plugin_glpiinventory_tasks` task",
            "  ON task.`id` = job.`plugin_glpiinventory_tasks_id`",
            "WHERE",
            "  run.`state` IN ('" . implode("','", [
               PluginGlpiinventoryTaskjobstate::PREPARED,
            ]) . "')",
            "  AND task.`id` = " . $this->fields['id'],
            // order the result by job.id
            // TODO: the result should be ordered by the future job.index field when drag AND drop
            // feature will be properly activated in the taskjobs list.
            "ORDER BY job.`id`",
            ]);
            $query_result = $DB->query($query);
            $results = [];
            if ($query_result) {
                $results = PluginGlpiinventoryToolbox::fetchAssocByTable($query_result);
            }
            foreach ($results as $data) {
                $pfTaskjobstate->getFromDB($data['run']['id']);
                $pfTaskjobstate->cancel(__('Task has been disabled', 'glpiinventory'));
            }
        }
        parent::post_updateItem($history);
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
    * Get the massive actions for this object
    *
    * @param object|null $checkitem
    * @return array list of actions
    */
    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions = [];
        $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'transfert'] = __('Transfer');
        $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'duplicate'] = _sx('button', 'Duplicate');
        return $actions;
    }



   /**
    * Display form related to the massive action selected
    *
    * @global array $CFG_GLPI
    * @param object $ma MassiveAction instance
    * @return boolean
    */
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        global $CFG_GLPI;

        switch ($ma->getAction()) {
            case "transfert":
                Dropdown::show('Entity');
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;

            case "duplicate":
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;

            case 'target_task':
                echo "<table class='tab_cadre' width='600'>";
                echo "<tr>";
                echo "<td>";
                echo _n('Task', 'Tasks', 1, 'glpiinventory') . "&nbsp;:";
                echo "</td>";
                echo "<td>";
                $rand = mt_rand();
                Dropdown::show('PluginGlpiinventoryTask', [
                  'name'      => "tasks_id",
                  'condition' => ['is_active' => 0],
                  'toupdate'  => [
                        'value_fieldname' => "id",
                        'to_update'       => "dropdown_packages_id$rand",
                        'url'             => Plugin::getWebDir('glpiinventory') . "/ajax/dropdown_taskjob.php"
                  ]
                ]);
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td>";
                echo __('Package', 'glpiinventory') . "&nbsp;:";
                echo "</td>";
                echo "<td>";
                Dropdown::show('PluginGlpiinventoryDeployPackage', [
                     'name' => "packages_id",
                     'rand' => $rand
                ]);
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td colspan='2' align='center'>";
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                echo "</td>";
                echo "</tr>";
                echo "</table>";
                return true;

            case 'addtojob_target':
                echo "<table class='tab_cadre' width='600'>";
                echo "<tr>";
                echo "<td>";
                echo _n('Task', 'Tasks', 1, 'glpiinventory') . "&nbsp;:";
                echo "</td>";
                echo "<td>";
                $rand = mt_rand();
                Dropdown::show('PluginGlpiinventoryTask', [
                  'name'      => "tasks_id",
                  'toupdate'  => [
                        'value_fieldname' => "id",
                        'to_update'       => "taskjob$rand",
                        'url'             => Plugin::getWebDir('glpiinventory') . "/ajax/dropdown_taskjob.php"
                  ]
                ]);
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td>";
                echo __('Job', 'glpiinventory') . "&nbsp;:";
                echo "</td>";
                echo "<td>";
                echo "<div id='taskjob$rand'>";
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td colspan='2' align='center'>";
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                echo "</td>";
                echo "</tr>";
                echo "</table>";
                return true;
        }
        return false;
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

        $pfTask    = new self();
        $pfTaskjob = new PluginGlpiinventoryTaskjob();

        switch ($ma->getAction()) {
            case "duplicate":
                foreach ($ids as $key) {
                    if ($pfTask->getFromDB($key)) {
                        if ($pfTask->duplicate($pfTask->getID())) {
                          //set action massive ok for this item
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                        } else {
                         // KO
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        }
                    }
                }
                break;

            case "transfert":
                foreach ($ids as $computer_id) {
                    if ($pfTask->getFromDB($computer_id)) {
                        $a_taskjobs = $pfTaskjob->find(['plugin_glpiinventory_tasks_id' => $computer_id]);
                        foreach ($a_taskjobs as $data1) {
                            $input = [];
                            $input['id'] = $data1['id'];
                            $input['entities_id'] = $_POST['entities_id'];
                            $pfTaskjob->update($input);
                        }

                        $input = [];
                        $input['id'] = $computer_id;
                        $input['entities_id'] = $_POST['entities_id'];

                        if ($pfTask->update($input)) {
                          //set action massive ok for this item
                            $ma->itemDone($item->getType(), $computer_id, MassiveAction::ACTION_OK);
                        } else {
                         // KO
                            $ma->itemDone($item->getType(), $computer_id, MassiveAction::ACTION_KO);
                        }
                    }
                }
                break;

            case 'target_task':
                $computer = new Computer();
                $pfDeployPackage = new PluginGlpiinventoryDeployPackage();

               // Get the task and the package
                $got_task = $pfTask->getFromDB($ma->POST['tasks_id']);
                $got_package = $pfDeployPackage->getFromDB($ma->POST['packages_id']);
                if (! $got_package or ! $got_task) {
                   // No task or package provided
                    foreach ($ids as $computer_id) {
                        $computer->getFromDB($computer_id);
                        $ma->itemDone($computer->getType(), $computer_id, MassiveAction::ACTION_KO);
                    }
                    Session::addMessageAfterRedirect(sprintf(
                        __('%1$s: %2$s'),
                        $pfTask->getLink(),
                        __(
                            'You must choose a task and a package to target a task with a deployment package.',
                            'glpiinventory'
                        )
                    ), false, ERROR);
                    PluginGlpiinventoryToolbox::logIfExtradebug(
                        "pluginGlpiinventory-tasks",
                        "Missing task and/or package for targeting a task"
                    );
                    return;
                }

                PluginGlpiinventoryToolbox::logIfExtradebug(
                    "pluginGlpiinventory-tasks",
                    "Target a task: " . $pfTask->getName() .
                    ", id: " . $pfTask->getId()
                );

                $job_name = __('Deployment job, package: ', 'glpiinventory') . $pfDeployPackage->getName();

               // Prepare base data
                $input = [
                 'plugin_glpiinventory_tasks_id' => $pfTask->getId(),
                 'entities_id'                     => 0,
                 'name'                            => $job_name,
                 'method'                          => 'deployinstall',
                 'targets'                         => '[{"PluginGlpiinventoryDeployPackage":"' . $ma->POST['packages_id'] . '"}]',
                 'actor'                           => []
                ];

                if ($pfTaskjob->getFromDBByCrit(['plugin_glpiinventory_tasks_id' => $ma->POST['tasks_id'], 'name' => $job_name])) {
                   // The task already has a job with the same name - update the job actors
                    $message = sprintf(
                        __('%1$s: %2$s'),
                        $pfTask->getLink(),
                        __('Updated a deployment job, package: ', 'glpiinventory') . $pfDeployPackage->getName() .
                        __(', actors: ', 'glpiinventory')
                    );
                    foreach ($ids as $computer_id) {
                           $computer->getFromDB($computer_id);
                           $message .= $computer->getName() . ",";
                           $input['actors'][] = ['Computer' => $computer_id];
                           $ma->itemDone($computer->getType(), $computer_id, MassiveAction::ACTION_OK);
                    }
                   //               $ma->addMessage($message);
                    Session::addMessageAfterRedirect($message, false, INFO);
                    $input['id'] = $pfTaskjob->getID();
                    $input['actors'] = json_encode($input['actors']);
                    PluginGlpiinventoryToolbox::logIfExtradebug(
                        "pluginGlpiinventory-tasks",
                        "Update the task job: " . serialize($input)
                    );
                    $pfTaskjob->update($input);
                } else {
                    if ($pfTaskjob->getFromDBByCrit(['plugin_glpiinventory_tasks_id' => $pfTask->getID()])) {
                       // The task already has a job - do not replace!
                        foreach ($ids as $computer_id) {
                             $computer->getFromDB($computer_id);
                             $ma->itemDone($computer->getType(), $computer_id, MassiveAction::ACTION_KO);
                        }
                        Session::addMessageAfterRedirect(
                            sprintf(
                                __('%1$s: %2$s'),
                                $pfTask->getLink(),
                                __('The selected task already has a deployment job for another package: ' . $pfTaskjob->getName(), 'glpiinventory')
                            ),
                            false,
                            ERROR
                        );
                        PluginGlpiinventoryToolbox::logIfExtradebug(
                            "pluginGlpiinventory-tasks",
                            "Not allowed to update the task job"
                        );
                    } else {
                       // The task do not have a job - create a new one
                        $message = sprintf(
                            __('%1$s: %2$s'),
                            $pfTask->getLink(),
                            __('Created a deployment job, package: ', 'glpiinventory') . $pfDeployPackage->getName() .
                            __(', actors: ', 'glpiinventory')
                        );
                        foreach ($ids as $computer_id) {
                            $computer->getFromDB($computer_id);
                            $message .= $computer->getName() . ",";
                            $input['actors'][] = ['Computer' => $computer_id];
                            $ma->itemDone($computer->getType(), $computer_id, MassiveAction::ACTION_OK);
                        }
                        $input['actors'] = json_encode($input['actors']);
                       //                  $ma->addMessage($message);
                        Session::addMessageAfterRedirect($message, false, INFO);
                        PluginGlpiinventoryToolbox::logIfExtradebug(
                            "pluginGlpiinventory-tasks",
                            "Create the task job: " . serialize($input)
                        );
                        $pfTaskjob->add($input);
                    }
                }
                break;

            case 'addtojob_target':
                $taskjob = new PluginGlpiinventoryTaskjob();
                foreach ($ids as $items_id) {
                    $taskjob->additemtodefatc('targets', $item->getType(), $items_id, $ma->POST['taskjobs_id']);
                    $ma->itemDone($item->getType(), $items_id, MassiveAction::ACTION_OK);
                }
                break;
        }
    }

   /**
   * Duplicate a task
   * @param $source_tasks_id the ID of the task to duplicate
   * @return void
   */
    public function duplicate($source_tasks_id)
    {
        $result = true;
        if ($this->getFromDB($source_tasks_id)) {
            $input              = $this->fields;
            $input['name']      = sprintf(
                __('Copy of %s'),
                $this->fields['name']
            );
            $input['is_active'] = 0;
            unset($input['id']);
            $input              = Toolbox::addslashes_deep($input);
            if ($target_task_id = $this->add($input)) {
                 //Clone taskjobs
                 $result
                  = PluginGlpiinventoryTaskjob::duplicate($source_tasks_id, $target_task_id);
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
    }

    public static function getIcon()
    {
        return "ti ti-list-check";
    }
}
