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
 * Manage the task jobs.
 */
class PluginGlpiinventoryTaskjob extends PluginGlpiinventoryTaskjobView
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
        return __('Job', 'glpiinventory');
    }


   /**
    * Check if can create an item
    *
    * @return boolean
    */
    public static function canCreate()
    {
        return true;
    }


   /**
    * Get join query in SQL
    *
    * @return array
    */
    public static function getJoinQuery()
    {
        //FIXME: used only for queries using PluginGlpiinventoryToolbox::fetchAssocByTable()
        return [
          'taskjobs' =>
               "LEFT JOIN `glpi_plugin_glpiinventory_taskjobs` as taskjob\n" .
               "ON taskjob.`plugin_glpiinventory_tasks_id` = task.`id`"];
    }

    public static function getJoinCriteria()
    {
        //FIXME: used only for queries using PluginGlpiinventoryToolbox::fetchAssocByTable()
        return [
            'glpi_plugin_glpiinventory_taskjobs AS taskjob' => [
                'ON' => [
                    'task' => 'id',
                    'taskjob' => 'plugin_glpiinventory_tasks_id'
                ]
            ]
        ];
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
         'table'     => 'glpi_entities',
         'field'     => 'completename',
         'linkfield' => 'entities_id',
         'name'      => Entity::getTypeName(1),
        ];

        $tab[] = [
         'id'            => '4',
         'table'         => 'glpi_plugin_glpiinventory_tasks',
         'field'         => 'name',
         'linkfield'     => 'plugin_glpiinventory_tasks_id',
         'name'          => __('Task'),
         'datatype'      => 'itemlink',
         'itemlink_type' => 'PluginGlpiinventoryTask',
        ];

        $tab[] = [
         'id'        => '5',
         'table'     => $this->getTable(),
         'field'     => 'status',
         'name'      => __('Status'),
        ];

        $tab[] = [
         'id'        => '6',
         'table'     => $this->getTable(),
         'field'     => 'id',
         'name'      => __('ID'),
        ];

        return $tab;
    }


   /**
    * get task of this task job
    *
    * @return object PluginGlpiinventoryTask instance
    */
    public function getTask()
    {
        $pfTask = new PluginGlpiinventoryTask();
        $pfTask->getFromDB($this->fields['plugin_glpiinventory_tasks_id']);
        return $pfTask;
    }

    /**
    * get task with job using IPRange
    *
    * @return array
    */
    public static function getTaskfromIPRange(PluginGlpiinventoryIPRange $item)
    {
        global $DB;

        $ID = $item->getField('id');

        //get all task with job using IPRange
        $iterator = $DB->request([
            'SELECT' => 'task.*',
            'FROM' => 'glpi_plugin_glpiinventory_tasks AS task',
            'LEFT JOIN' => [
                'glpi_plugin_glpiinventory_taskjobs AS job' => [
                    'ON' => [
                        'task' => 'id',
                        'job' => 'plugin_glpiinventory_tasks_id'
                    ]
                ]
            ],
            'WHERE' => [
                'job.targets' => ['LIKE', '%{"PluginGlpiinventoryIPRange":"' . $ID . '"}%']
            ]
        ]);

        $a_data = [];
        foreach ($iterator as $data) {
            $a_data[$data['id']] = $data;
        }

        return $a_data;
    }


   /**
    * Display definitions type dropdown
    *
    * @global array $CFG_GLPI
    * @param string $myname
    * @param string $method
    * @param integer $value
    * @param integer $taskjobs_id
    * @param string $entity_restrict
    * @return string unique id of html element
    */
    public function dropdownType($myname, $method, $value = 0, $taskjobs_id = 0, $entity_restrict = '')
    {
        global $CFG_GLPI;

        $a_methods = PluginGlpiinventoryStaticmisc::getmethods();
        $a_type = [];
        $a_type[''] = Dropdown::EMPTY_VALUE;
        if ($myname == 'action') {
            $a_type['Agent'] = Agent::getTypeName();
        }
        foreach ($a_methods as $datas) {
            if ($method == $datas['method']) {
                $module = $datas['module'];
                $class = PluginGlpiinventoryStaticmisc::getStaticMiscClass($module);
                if (is_callable([$class, "task_" . $myname . "type_" . $method])) {
                    $a_type = call_user_func([$class, "task_" . $myname . "type_" . $method], $a_type);
                }
            }
        }

        $rand = Dropdown::showFromArray(ucfirst($myname) . "Type", $a_type);

        $params = [ucfirst($myname) . 'Type' => '__VALUE__',
            'entity_restrict' => $entity_restrict,
            'rand'            => $rand,
            'myname'          => ucfirst($myname) . "Type",
            'name'            => $myname,
            'method'          => $method,
            $myname . 'typeid'  => 'dropdown_' . ucfirst($myname) . 'Type' . $rand,
            'taskjobs_id'     => $taskjobs_id];

        Ajax::updateItemOnEvent(
            'dropdown_' . ucfirst($myname) . 'Type' . $rand,
            "show_" . ucfirst($myname) . "List" . $taskjobs_id,
            Plugin::getWebDir('glpiinventory') . "/ajax/dropdowntypelist.php",
            $params
        );

        return $rand;
    }


   /**
    * Get Itemtypes list for the selected method
    *
    * @param string $method
    * @param string $moduletype
    * @return array
    */
    public function getTypesForModule($method, $moduletype)
    {

        $available_methods = PluginGlpiinventoryStaticmisc::getmethods();
        $types = [];
        if ($moduletype === 'actors') {
            $types['Agent'] = Agent::getTypeName();
        }

       /**
        * TODO: move staticmisc actors and targets related methods to the relevant Module classes
        * ( I don't have time for this yet and this is why i can live with a simple mapping string
        * table)
        */
        switch ($moduletype) {
            case 'actors':
                $moduletype_tmp = 'action';
                break;

            case 'targets':
                $moduletype_tmp = 'definition';
                break;
        }

        foreach ($available_methods as $available_method) {
            if ($method == $available_method['method']) {
                $module = $available_method['module'];
                $class = PluginGlpiinventoryStaticmisc::getStaticMiscClass($module);
                $class_method = [$class, "task_" . $moduletype_tmp . "type_" . $method];
                if (is_callable($class_method)) {
                    $types = call_user_func($class_method, $types);
                }
            }
        }
        return $types;
    }


   /**
    * Display definitions value with preselection of definition type
    *
    * @global array $CFG_GLPI
    * @param string $myname name of dropdown
    * @param string $definitiontype name of the definition type selected
    * @param string $method name of the method selected
    * @param string $deftypeid dropdown name of definition type
    * @param integer $taskjobs_id
    * @param integer $value name of the definition (used for edit taskjob)
    * @param string $entity_restrict restriction of entity if required
    * @param integer $title
    * @return string unique id of html element
    */
    public function dropdownvalue(
        $myname,
        $definitiontype,
        $method,
        $deftypeid,
        $taskjobs_id,
        $value = 0,
        $entity_restrict = '',
        $title = 0
    ) {
        global $CFG_GLPI;

        $a_methods = PluginGlpiinventoryStaticmisc::getmethods();
        $module = '';
        foreach ($a_methods as $datas) {
            if ($method == $datas['method']) {
                $module = $datas['module'];
            }
        }

        $rand = '';
        $class = PluginGlpiinventoryStaticmisc::getStaticMiscClass($module);
        if (
            is_callable([$class, "task_" . $_POST['name'] . "selection_" .
            $definitiontype . "_" . $method])
        ) {
            $rand = call_user_func(
                [$class,
                                      "task_" . $_POST['name'] . "selection_" . $definitiontype . "_" .
                                          $method],
                $title
            );

            $iddropdown = "dropdown_" . $_POST['name'] . "selectiontoadd";
        } else {
            $a_data = $this->getAgents($method);

            $rand = Dropdown::showFromArray($_POST['name'] . 'selectiontoadd', $a_data);
            $iddropdown = "dropdown_" . $_POST['name'] . "selectiontoadd";
        }

        echo "<br/><center><input type='button' id='add_button_" . $_POST['name'] . $taskjobs_id . "' " .
              "name='add_button_" . $_POST['name'] . "' value=\"" . __('Add') .
              "\" class='submit'></center>";
        $params = ['items_id'  => '__VALUE0__',
                      'add_button_' . $_POST['name'] . $taskjobs_id => '__VALUE1__',
                      'itemtype'  => $definitiontype,
                      'rand'      => $rand,
                      'myname'    => 'items_id',
                      'type'      => $_POST['name'],
                      'taskjobs_id' => $taskjobs_id];
        Ajax::updateItemOnEvent(
            [$iddropdown . $rand , "add_button_" . $_POST['name'] . $taskjobs_id],
            "Additem_$rand",
            Plugin::getWebDir('glpiinventory') . "/ajax/taskjobaddtype.php",
            $params,
            ["click"],
            "-1",
            "-1",
            [__('Add')]
        );

        echo "<span id='Additem_$rand'></span>";
    }


   /**
    * Display actions type (itemtypes)
    *
    * @global array $CFG_GLPI
    * @param string $myname name of dropdown
    * @param string $method name of the method selected
    * @param integer $value name of the definition type (used for edit taskjob)
    * @param string $entity_restrict restriction of entity if required
    * @return string unique id of html element
    */
    public function dropdownActionType($myname, $method, $value = 0, $entity_restrict = '')
    {
        global $CFG_GLPI;

        $a_methods               = PluginGlpiinventoryStaticmisc::getmethods();
        $a_actioninitiontype     = [];
        $a_actioninitiontype[''] = Dropdown::EMPTY_VALUE;
        $a_actioninitiontype['Agent'] = Agent::getTypeName();
        foreach ($a_methods as $datas) {
            if ($method == $datas['method']) {
                $module = ucfirst($datas['module']);
                $class = PluginGlpiinventoryStaticmisc::getStaticMiscClass($module);

                if (is_callable([$class, "task_actiontype_" . $method])) {
                    $a_actioninitiontype = call_user_func(
                        [$class, "task_actiontype_" . $method],
                        $a_actioninitiontype
                    );
                }
            }
        }

        $rand = Dropdown::showFromArray($myname, $a_actioninitiontype);

        $params = ['ActionType' => '__VALUE__',
            'entity_restrict' => $entity_restrict,
            'rand' => $rand,
            'myname' => $myname,
            'method' => $method,
            'actiontypeid' => 'dropdown_' . $myname . $rand
            ];
        Ajax::updateItemOnSelectEvent(
            'dropdown_ActionType' . $rand,
            "show_ActionList",
            Plugin::getWebDir('glpiinventory') . "/ajax/dropdownactionlist.php",
            $params
        );

        return $rand;
    }


   /**
    * Display actions value with preselection of action type
    *
    * @global array $CFG_GLPI
    * @param string $myname name of dropdown
    * @param string $actiontype name of the action type selected
    * @param string $method name of the method selected
    * @param string $actiontypeid dropdown name of action type
    * @param integer $value name of the definition (used for edit taskjob)
    * @param string $entity_restrict restriction of entity if required
    * @return string unique id of html element
    */
    public function dropdownAction(
        $myname,
        $actiontype,
        $method,
        $actiontypeid,
        $value = 0,
        $entity_restrict = ''
    ) {
        global $CFG_GLPI;

        $a_methods = PluginGlpiinventoryStaticmisc::getmethods();
        $module = '';
        foreach ($a_methods as $datas) {
            if ($method == $datas['method']) {
                $module = $datas['module'];
            }
        }

        $rand = '';

        $class = PluginGlpiinventoryStaticmisc::getStaticMiscClass($module);
        if ($actiontype == "Agent") {
            $actionselection_method = "task_actionselection_Agent_" . $method;
            if (is_callable([$class, $actionselection_method])) {
                $rand = call_user_func([$class, $actionselection_method]);
            } else {
                $a_data = $this->getAgents($method);

                $rand = Dropdown::showFromArray('actionselectiontoadd', $a_data);
            }
        } else {
            $definitionselection_method = "task_definitionselection_" . $actiontype . "_" . $method;
            if (is_callable([$class, $definitionselection_method])) {
                $rand = call_user_func([$class, $definitionselection_method]);
            }
        }

        $params = ['selection'        => '__VALUE__',
                    'entity_restrict'  => $entity_restrict,
                    'myname'           => $myname,
                    'actionselectadd'  => 'dropdown_actionselectiontoadd' . $rand,
                    'actiontypeid'     => $actiontypeid];

        Ajax::updateItemOnEvent(
            'addAObject',
            'show_ActionListEmpty',
            Plugin::getWebDir('glpiinventory') . "/ajax/dropdownactionselection.php",
            $params,
            ["click"]
        );
    }


   /**
    * Get all agents allowed to a module (task method)
    *
    * @param string $module name of dropdown
    * @return array [id integed agent id] => $name value agent name
    */
    public function getAgents($module)
    {
       //Array to store agents that are allowed to use this module
        $allowed_agents = [];
        $pfAgentmodule  = new PluginGlpiinventoryAgentmodule();

       //Get all agents that can run the module
        $array_agents   = $pfAgentmodule->getAgentsCanDo(strtoupper($module));
        foreach ($array_agents as $id => $data) {
            $allowed_agents[$id] = $data['name'];
        }
       //Sort the array
        asort($allowed_agents);
        return $allowed_agents;
    }


   /**
    * re initialize all taskjob of a taskjob
    *
    * @global object $DB
    * @param integer $tasks_id id of the task
    * @param integer $disableTimeVerification
    * @return boolean true if all taskjob are ready (so finished from old runnning job)
    */
    public function reinitializeTaskjobs($tasks_id, $disableTimeVerification = 0)
    {
        global $DB;

        $pfTask         = new PluginGlpiinventoryTask();
        $pfTaskjob      = new PluginGlpiinventoryTaskjob();
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $pfTaskjoblog   = new PluginGlpiinventoryTaskjoblog();
        $iterator = $DB->request([
            'SELECT' => [
                '*',
                new QueryExpression('UNIX_TIMESTAMP(datetime_start) AS date_scheduled_timestamp')
            ],
            'FROM'   => $pfTaskjob->getTable(),
            'WHERE'  => ['id' => $tasks_id],
            'LIMIT'  => 1
        ]);
        $data   = $iterator->current();
        $period = $pfTaskjob->periodicityToTimestamp(
            $data['periodicity_type'],
            $data['periodicity_count']
        );

        // Calculate next execution from last
        $iterator = $DB->request([
            'FROM'   => $pfTaskjob->getTable(),
            'WHERE'  => [
                'plugin_glpiinventory_tasks_id' => $tasks_id
            ],
            'ORDER'  => 'id DESC'
        ]);
        $nb_taskjobs = count($iterator);
        // get only with execution_id (same +1) as task
        $iterator = $DB->request([
            'FROM'   => $pfTaskjob->getTable(),
            'WHERE'  => [
                'plugin_glpiinventory_tasks_id' => $tasks_id,
                'execution_id'                 => $data['execution_id'] + 1
            ],
            'ORDER'  => 'id DESC'
        ]);
        $finished    = 2;

        $nb_finished = 0;
        foreach ($iterator as $dataJob) {
            $a_taskjobstateuniqs = $pfTaskjobstate->find(
                ['plugin_glpiinventory_taskjobs_id' => $dataJob['id']],
                ['id DESC'],
                1
            );
            $a_taskjobstateuniq = current($a_taskjobstateuniqs);
            $a_taskjobstate = $pfTaskjobstate->find(
                ['plugin_glpiinventory_taskjobs_id' => $dataJob['id'],
                'uniqid'                             => $a_taskjobstateuniq['uniqid']]
            );
            $taskjobstatefinished = 0;

            foreach ($a_taskjobstate as $statedata) {
                 $a_joblog = $pfTaskjoblog->find(
                     ['plugin_glpiinventory_taskjobstates_id' => $statedata['id'],
                     'state'                                   => [2,
                     4,
                     5]]
                 );
                if (count($a_joblog) > 0) {
                     $taskjobstatefinished++;
                }
            }
            if (
                (count($a_taskjobstate) == $taskjobstatefinished)
                 and (count($a_taskjobstate) > 0 )
            ) {
                if ($finished == '2') {
                    $finished = 1;
                }
                $nb_finished++;
            } else {
                $finished = 0;
            }
        }
        if ($nb_finished != $nb_taskjobs) {
            if ($disableTimeVerification == '1') { // Forcerun
                $iterator = $DB->request([
                    'FROM' => $pfTaskjob->getTable(),
                    'WHERE' => [
                        'plugin_glpiinventory_tasks_id' => $tasks_id,
                        'execution_id'                 => $data['execution_id']
                    ],
                    'ORDER' => 'id DESC'
                ]);
                if (count($iterator) == $nb_taskjobs) {
                    $finished = 1;
                    return true;
                } else {
                    $finished = 0;
                }
            } else {
                $finished = 0;
            }
        }
       // if all jobs are finished, we calculate if we reinitialize all jobs
        if ($finished == "1") {
            $exe = $data['execution_id'];
            unset($data['execution_id']);

            $DB->update(
                $pfTaskjob->getTable(),
                [
                'status' => 0
                ],
                [
                'plugin_glpiinventory_tasks_id' => $data['id']
                ]
            );

            if ($period != '0') {
                if (is_null($data['date_scheduled_timestamp'])) {
                    $data['date_scheduled_timestamp'] = date('U');
                }
                if (
                    ($data['date_scheduled_timestamp'] + $period) <= date('U')
                    and $period = ! '0'
                ) {
                    $periodtotal = $period;
                    for ($i = 2; ($data['date_scheduled_timestamp'] + $periodtotal) <= date('U'); $i++) {
                        $periodtotal = $period * $i;
                    }
                    $data['datetime_start'] = date(
                        "Y-m-d H:i:s",
                        $data['date_scheduled_timestamp'] + $periodtotal
                    );
                } elseif ($data['date_scheduled_timestamp'] <= date('U')) {
                    $data['datetime_start'] = date(
                        "Y-m-d H:i:s",
                        $data['date_scheduled_timestamp'] + $period
                    );
                }
            }
            $data['execution_id'] = $exe + 1;
            unset($data['comment']);
            $pfTask->update($data);
            return true;
        } else {
            return false;
        }
    }


   /**
    * Get period in secondes by type and count time
    *
    * @param string $periodicity_type type of time (minutes, hours...)
    * @param integer $periodicity_count number of type time
    * @return integer in seconds
    */
    public function periodicityToTimestamp($periodicity_type, $periodicity_count)
    {
        $period = 0;
        switch ($periodicity_type) {
            case 'minutes':
                $period = $periodicity_count * 60;
                break;

            case 'hours':
                $period = $periodicity_count * 60 * 60;
                break;

            case 'days':
                $period = $periodicity_count * 60 * 60 * 24;
                break;

            case 'months':
                $period = $periodicity_count * 60 * 60 * 24 * 30; //month
                break;
        }
        return $period;
    }

   /**
    * Cron task: finish task if have some problem or started for so long time
    *
    * @global object $DB
    */
    public function CronCheckRunnningJobs()
    {
        global $DB;

       // Get all taskjobstate running
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $pfTaskjoblog   = new PluginGlpiinventoryTaskjoblog();

        $a_taskjobstate = $DB->request([
         'FROM'    => 'glpi_plugin_glpiinventory_taskjobstates',
         'WHERE'   => ['state' => [0, 1, 2]],
         'GROUPBY' => ['uniqid', 'agents_id']]);
        foreach ($a_taskjobstate as $data) {
            $iterator = $DB->request([
                'FROM' => 'glpi_plugin_glpiinventory_tasks',
                'LEFT JOIN' => [
                    'glpi_plugin_glpiinventory_taskjobs' => [
                        'ON' => [
                            'glpi_plugin_glpiinventory_tasks' => 'id',
                            'glpi_plugin_glpiinventory_taskjobs' => 'plugin_glpiinventory_tasks_id'
                        ]
                    ]
                ],
                'WHERE' => [
                    'glpi_plugin_glpiinventory_taskjobs.id' => $data['plugin_glpiinventory_taskjobs_id']
                ],
                'LIMIT' => 1
            ]);

            if (count($iterator) != 0) {
                $task = $iterator->current();
                if ($task['communication'] == 'pull') {
                    $has_recent_log_entries = $pfTaskjoblog->find(
                        ['plugin_glpiinventory_taskjobstates_id' => $data['id']],
                        ['id DESC'],
                        1
                    );
                    $finish = false;
                    if (count($has_recent_log_entries) == 1) {
                          $data2 = current($has_recent_log_entries);
                          $date = strtotime($data2['date']);
                          $date += (4 * 3600);
                        if ($date < date('U')) {
                            $finish = true;
                        }
                    } else {
                        $finish = true;
                    }

                    // No news from the agent since 4 hour. The agent is probably crached.
                    //Let's cancel the task
                    if ($finish) {
                        $a_statustmp = $pfTaskjobstate->find(
                            ['uniqid' => $data['uniqid'],
                            'agents_id' => $data['agents_id'],
                            'state'  => [1,
                            2]]
                        );
                        foreach ($a_statustmp as $datatmp) {
                            $pfTaskjobstate->changeStatusFinish(
                                $datatmp['id'],
                                0,
                                '',
                                1,
                                "==agentcrashed=="
                            );
                        }
                    }
                } elseif ($task['communication'] == 'push') {
                    $a_valid = $pfTaskjoblog->find(
                        ['plugin_glpiinventory_taskjobstates_id' => $data['id'],
                        'ADDTIME(date, "00:10:00")' => ['<',  'NOW()']],
                        ['id DESC'],
                        1
                    );

                    if (count($a_valid) == '1') {
                         // Get agent status
                         $agentreturn = $this->getRealStateAgent(
                             $data['agents_id']
                         );

                        switch ($agentreturn) {
                            case 'waiting':
                              // token is bad and must force cancel task in server
                                $a_statetmp = $pfTaskjobstate->find(
                                    ['uniqid' => $data['uniqid'],
                                    'agents_id' => $data['agents_id'],
                                    'state'  => [0,
                                    1,
                                    2]]
                                );
                                foreach ($a_statetmp as $datatmp) {
                                           $pfTaskjobstate->changeStatusFinish(
                                               $datatmp['id'],
                                               0,
                                               '',
                                               1,
                                               "==badtoken=="
                                           );
                                }
                                break;

                            case 'running':
                          // just wait and do nothing
                                break;

                            case 'noanswer':
                                   // agent crash or computer is shutdown and force cancel task in server
                                   $a_statetmp = $pfTaskjobstate->find(
                                       ['uniqid' => $data['uniqid'],
                                       'agents_id' => $data['agents_id'],
                                       'state'  => [1,
                                       2]]
                                   );
                                foreach ($a_statetmp as $datatmp) {
                                        $pfTaskjobstate->changeStatusFinish(
                                            $datatmp['id'],
                                            0,
                                            '',
                                            1,
                                            "==agentcrashed=="
                                        );
                                }
                                  $a_valid4h = $pfTaskjoblog->find(
                                      ['plugin_glpiinventory_taskjobstates_id' => $data['id']],
                                      ['id DESC'],
                                      1
                                  );
                                  $finish = false;
                                if (count($a_valid4h) == 1) {
                                        $datajs = current($a_valid4h);
                                        $date = strtotime($datajs['date']);
                                        $date += (4 * 3600);
                                    if ($date < date('U')) {
                                        $finish = true;
                                    }
                                } else {
                                    $finish = true;
                                }

                                if ($finish) {
                                    $a_statetmp = $pfTaskjobstate->find(
                                        ['uniqid' => $data['uniqid'],
                                        'agents_id' => $data['agents_id'],
                                        'state' => 0]
                                    );
                                    foreach ($a_statetmp as $datatmp) {
                                         $pfTaskjobstate->changeStatusFinish(
                                             $datatmp['id'],
                                             0,
                                             '',
                                             1,
                                             "==agentcrashed=="
                                         );
                                    }
                                }
                                break;

                            case 'noip':
                                 // just wait and do nothing
                                break;
                        }
                    }
                }
            }
        }

       // If taskjob.status = 1 and all taskjobstates are finished, so reinitializeTaskjobs()
        $sub_query = new \QuerySubQuery([
            'COUNT' => 'cpt',
            'FROM' => 'glpi_plugin_glpiinventory_taskjobstates',
            'WHERE' => [
                new \QueryExpression('plugin_glpiinventory_taskjobs_id = glpi_plugin_glpiinventory_taskjobs.id'),
                'state' => ['<', 3]
            ]
        ]);
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_glpiinventory_taskjobs',
            'WHERE' => [
                'status' => 1,
                new \QueryExpression($sub_query->getSQL() . ' = 0')
            ]
        ]);

        foreach ($iterator as $data) {
            $this->reinitializeTaskjobs($data['plugin_glpiinventory_tasks_id'], '1');
        }
    }


   /**
    * Check for configuration consistency.
    * Remove items targets or actors that have been deleted.
    *
    * @return boolean ( What does this return value mean ? -- Kevin Roy <kiniou@gmail.com> )
    */
    public function checkConfiguration()
    {

        $return = true;
        $input = [];
        $input['id'] = $this->fields['id'];
        $targets = importArrayFromDB($this->fields['targets']);
        foreach ($targets as $num => $data) {
            $classname = key($data);
            if ($classname == '') {
                unset($targets[$num]);
            } else {
                $Class = new $classname();
                if (!$Class->getFromDB(current($data))) {
                    unset($targets[$num]);
                }
            }
        }
        if (count($targets) == '0') {
            $input['targets'] = '';
            $return = false;
        } else {
            $input['targets'] = exportArrayToDB($targets);
        }
        $actors = importArrayFromDB($this->fields['actors']);
        foreach ($actors as $num => $data) {
            $classname = key($data);
            $Class = new $classname();
            if (
                !$Class->getFromDB(current($data))
                 and (current($data) != ".1")
                 and (current($data) != ".2")
            ) {
                unset($actors[$num]);
            }
        }
        if (count($actors) == '0') {
            $input['actors'] = '';
            $return = false;
        } else {
            $input['actors'] = exportArrayToDB($actors);
        }
        $this->update($input);
        return $return;
    }


   /**
    * Purge taskjoblog/state when delete taskjob
    *
    * @param object $parm PluginGlpiinventoryTaskjob instance
    */
    public static function purgeTaskjob($parm)
    {
       // $parm["id"]
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $pfTaskjoblog   = new PluginGlpiinventoryTaskjoblog();

       // all taskjobs
        $a_taskjobstates = $pfTaskjobstate->find(
            ['plugin_glpiinventory_taskjobs_id' => $parm->fields["id"]]
        );
        foreach ($a_taskjobstates as $a_taskjobstate) {
            $a_taskjoblogs = $pfTaskjoblog->find(
                ['plugin_glpiinventory_taskjobstates_id' => $a_taskjobstate['id']]
            );
            foreach ($a_taskjoblogs as $a_taskjoblog) {
                 $pfTaskjoblog->delete($a_taskjoblog, 1);
            }
            $pfTaskjobstate->delete($a_taskjobstate, 1);
        }
    }


   /**
    * Force end task
    */
    public function forceEnd()
    {
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();

        $a_taskjobstates =
         $pfTaskjobstate->find(['plugin_glpiinventory_taskjobs_id' => $this->fields["id"]]);

       //TODO: in order to avoid too many atomic operations on DB, convert the
       //following into a massive prepared operation (ie. ids in one massive action)
        foreach ($a_taskjobstates as $a_taskjobstate) {
            $pfTaskjobstate->getFromDB($a_taskjobstate['id']);
            if ($a_taskjobstate['state'] != PluginGlpiinventoryTaskjobstate::FINISHED) {
                 $pfTaskjobstate->changeStatusFinish(
                     $a_taskjobstate['id'],
                     0,
                     '',
                     1,
                     "Action cancelled by user"
                 );
            }
        }
        $this->reinitializeTaskjobs($this->fields['plugin_glpiinventory_tasks_id']);
    }


   /**
    * Display static list of taskjob
    *
    * @param string $method method name of taskjob to display
    */
    public static function quickList($method)
    {

        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfTask = new PluginGlpiinventoryTask();

        $a_list = $pfTaskjob->find(['method' => $method]);

        echo "<table class='tab_cadrehov' style='width:950px'>";
        echo "<tr class='tab_bg_1'>";
        echo "<th>" . __('Name') . "</th>";
        echo "<th>" . __('Active') . "</th>";
        echo "<th>" . __('Scheduled date', 'glpiinventory') . "</th>";
        echo "<th>" . __('Periodicity', 'glpiinventory') . "</th>";
        echo "<th>" . __('Definition', 'glpiinventory') . "</td>";
        echo "<th>" . __('Action', 'glpiinventory') . "</th>";
        echo "</tr>";

        foreach ($a_list as $data) {
            $pfTaskjob->getFromDB($data['id']);
            $pfTask->getFromDB($data['plugin_glpiinventory_tasks_id']);
            echo "<tr class='tab_bg_1'>";
            $link_item = $pfTaskjob->getFormURL();
            $link  = $link_item;
            $link .= (strpos($link, '?') ? '&amp;' : '?') . 'id=' . $pfTaskjob->fields['id'];
            echo "<td><a href='" . $link . "'>" . $pfTaskjob->getNameID(1) . "</a></td>";
            echo "<td>" . Dropdown::getYesNo($pfTask->fields['is_active']) . "</td>";
            echo "<td>" . $pfTask->fields['datetime_start'] . "</td>";
            $a_time = '';
            switch ($pfTask->fields['periodicity_type']) {
                case 'minutes':
                    $a_time = $pfTask->fields['periodicity_count'] . " " .
                    strtolower(__('Minute(s)', 'glpiinventory'));
                    break;

                case 'hours':
                    $a_time = $pfTask->fields['periodicity_count'] . " " .
                    strtolower(__('hour(s)', 'glpiinventory'));
                    break;

                case 'days':
                    $a_time = $pfTask->fields['periodicity_count'] . " " .
                    __('day(s)', 'glpiinventory');
                    break;

                case 'months':
                    $a_time = $pfTask->fields['periodicity_count'] . " " .
                    __('months');
                    break;
            }
            echo "<td>" . $a_time . "</td>";
            $a_defs = importArrayFromDB($data['definition']);
            echo "<td>";
            foreach ($a_defs as $datadef) {
                foreach ($datadef as $itemtype => $items_id) {
                    $class = new $itemtype();
                    $class->getFromDB($items_id);
                    echo $class->getLink(1) . " (" . $class->getTypeName() . ")<br/>";
                }
            }
            echo "</td>";
            echo "<td>";
            $a_acts = importArrayFromDB($data['action']);
            foreach ($a_acts as $dataact) {
                foreach ($dataact as $itemtype => $items_id) {
                    $class = new $itemtype();
                    $itemname = $class->getTypeName();
                    $class->getFromDB($items_id);
                    if ($items_id == '.1') {
                        $name = __('Auto managenement dynamic of agents', 'glpiinventory');
                    } elseif ($items_id == '.2') {
                        $name =  __('Auto managenement dynamic of agents (same subnet)', 'glpiinventory');
                    } else {
                        $name = $class->getLink(1);
                    }
                    echo $name . ' (' . $itemname . ')<br/>';
                }
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }


   /**
    * Function used to add item in definition or action of a taskjob
    *    and hide add form
    *    and refresh type list
    *
    * @global array $CFG_GLPI
    * @param string $type
    * @param string $itemtype
    * @param integer $items_id
    * @param integer $taskjobs_id
    */
    public function additemtodefatc($type, $itemtype, $items_id, $taskjobs_id)
    {
        global $CFG_GLPI;

        $this->getFromDB($taskjobs_id);
        $a_type = importArrayFromDB($this->fields[$type]);
        $add = 1;
        foreach ($a_type as $data) {
            foreach ($data as $key => $val) {
                if ($itemtype == $key and $items_id == $val) {
                    $add = 0;
                }
            }
        }
        if ($add == '1') {
            $a_type[] = [$itemtype => $items_id];
            $input = [];
            $input['id'] = $this->fields['id'];
            $input[$type] = exportArrayToDB($a_type);
            $this->update($input);
        }

       //TODO: Clean add form
        echo "<script type='text/javascript'>
      //document.getElementById('show_" . ucfirst($type) . "List').innerHTML='&nbsp';

      Ext.get('" . $type . $taskjobs_id . "').setDisplayed('none');
      </script>";
       // reload item list
        $params = [];
        $params['taskjobs_id'] = $taskjobs_id;
        $params['typename'] = $type;
        echo "<script type='text/javascript'>";
        Ajax::updateItemJsCode(
            "show" . $type . "list" . $taskjobs_id . "_",
            Plugin::getWebDir('glpiinventory') . "/ajax/dropdownlist.php",
            $params
        );
        echo "</script>";
    }


   /**
    * Function used to delete item in definition or action of a taskjob
    *    and hide add form
    *    and refresh type list
    *
    * @global array $CFG_GLPI
    * @param string $type
    * @param string $items_id
    * @param integer $taskjobs_id
    */
    public function deleteitemtodefatc($type, $a_items_id, $taskjobs_id)
    {
        global $CFG_GLPI;

        $this->getFromDB($taskjobs_id);
        $a_type = importArrayFromDB($this->fields[$type]);
        $split = explode("-", $a_items_id);
        foreach ($split as $key) {
            unset($a_type[$key]);
        }
        $input = [];
        $input['id'] = $this->fields['id'];
        $input[$type] = exportArrayToDB($a_type);
        $this->update($input);

       // reload item list
        $params = [];
        $params['taskjobs_id'] = $taskjobs_id;
        $params['typename'] = $type;
        echo "<script type='text/javascript'>";
        Ajax::updateItemJsCode(
            "show" . $type . "list" . $taskjobs_id . "_",
            Plugin::getWebDir('glpiinventory') . "/ajax/dropdownlist.php",
            $params
        );
        echo "</script>";
    }


   /**
    * Display + button to add definition or action
    *
    * @global array $CFG_GLPI
    * @param string $name name of the action (here definition or action)
    */
    public function plusButton($name)
    {
        global $CFG_GLPI;

        if ($this->canUpdate()) {
            echo "&nbsp;";
            echo "<img onClick=\"Ext.get('" . $name . "').setDisplayed('block')\"
                    title=\"" . __('Add') . "\" alt=\"" . __('Add') . "\"
                    class='pointer'  src='" . $CFG_GLPI["root_doc"] . "/pics/add_dropdown.png'>";
        }
    }


   /**
    * Prepare task job
    *
    * @param array $a_taskjob
    * @return string uniqid
    */
    public function prepareRunTaskjob($a_taskjob)
    {

        $itemtype = "PluginGlpiinventory" . ucfirst($a_taskjob['method']);
        $item = new $itemtype();

        if (
            $a_taskjob['method'] == 'deployinstall'
              && isset($a_taskjob['definitions_filter'])
        ) {
            $uniqid = $item->prepareRun($a_taskjob['id'], $a_taskjob['definitions_filter']);
        } else {
            $uniqid = $item->prepareRun($a_taskjob['id']);
        }
        return $uniqid;
    }


    public static function restartJob($params)
    {
        $task     = new PluginGlpiinventoryTask();
        $job      = new PluginGlpiinventoryTaskjob();
        $jobstate = new PluginGlpiinventoryTaskjobstate();
        $joblog   = new PluginGlpiinventoryTaskjoblog();
        $agent    = new Agent();

       // get old state
        $jobstate->getFromDB($params['jobstate_id']);

       // prepare new state (copy from old)
        $run = $jobstate->fields;
        unset($run['id']);
        $run['state']  = PluginGlpiinventoryTaskjobstate::PREPARED;
        $run['uniqid'] = uniqid();
        if ($run['specificity'] == "") {
            $run['specificity'] = "NULL";
        }

       // add this new state and first log
        if ($run_id = $jobstate->add($run)) {
            $log = [
            'date'    => date("Y-m-d H:i:s"),
            'state'   => PluginGlpiinventoryTaskjoblog::TASK_PREPARED,
            'plugin_glpiinventory_taskjobstates_id' => $run_id,
            'comment' => ''
            ];
            if ($joblog->add($log)) {
               //wake up agent (only if task support wakeup)
                $job->getFromDB($jobstate->fields['plugin_glpiinventory_taskjobs_id']);
                $task->getFromDB($job->fields['plugin_glpiinventory_tasks_id']);

                if (
                    $task->fields['wakeup_agent_counter'] > 0
                    && $task->fields['wakeup_agent_time'] > 0
                ) {
                    $agent->getFromDB($params['agent_id']);
                    PluginGlpiinventoryAgentWakeup::wakeUp($agent);
                }
            }
        }
    }


   /**
    * Update method
    *
    * @param string $method
    * @param integer $taskjobs_id
    */
    public function updateMethod($method, $taskjobs_id)
    {

        $a_methods = PluginGlpiinventoryStaticmisc::getmethods();
        foreach ($a_methods as $datas) {
            if ($method == $datas['method']) {
                $input = [];
                $input['id'] = $taskjobs_id;
                $input['method'] = $method;
                $input['plugins_id'] = PluginGlpiinventoryModule::getModuleId($datas['module']);
                $this->update($input);
            }
        }
    }


   /**
    * Update list of definition and actions
    *
    * @global array $CFG_GLPI
    * @param integer $tasks_id
    */
    public function displayList($tasks_id)
    {
        global $CFG_GLPI;

        $rand = mt_rand();

        echo "<script type=\"text/javascript\">
function edit_subtype(id,el) {

   //remove all border to previous selected item (remove classes)
//   Ext.select('#table_taskjob_'+ _rand +' tr').removeClass('selected');


   var row = null;
   if (el) {
      // get parent row of the selected element
      row = jQuery(el).parents('tr:first')
   }

   if (row) {
      //add border to selected index (add class)
      row.addClass('selected');
//      params['index'] = row.index();
      // change mode to edit
//      params['mode'] = 'edit';
      var arg = 'taskjobs_id=' + id;
   } else {
      var arg = 'tasks_id=' + id;
   }

   //scroll to edit form
//   document.getElementById('th_title_taskjob_' + _rand).scrollIntoView();

   //show and load form
//   $('taskjobs_block' + _rand).setDisplayed('block');
   $('#taskjobs_block').load('../ajax/taskjob_form.php?' + arg);
}

/*
 * Create a new subtype element.
 * This method just override *edit_subtype* with a null element.
 */
function new_subtype(id) {
   edit_subtype(id, null);
}
</script>";

        echo "<table class='tab_cadre_fixe' id='package_order_" . $tasks_id . "'>";

        echo "<tr>";
        echo "<th id='th_title_taskjob_$rand'>";
        echo "&nbsp;" . $this->getTypeName();

        echo "&nbsp;";
        echo "<img id='plus_taskjobs_block{$rand}'";
        echo " onclick=\"new_subtype({$tasks_id})\" ";
        echo  " title='" . __('Add') . "' alt='" . __('Add') . "' ";
        echo  " class='pointer' src='" .
            $CFG_GLPI["root_doc"] . "/pics/add_dropdown.png' /> ";

        echo "</th>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='vertical-align:top'>";

       /**
        * Display subtype form
        **/
        echo "<form name='additiontaskjob' method='post' " .
         " action='taskjob.form.php'>";
        echo "<input type='hidden' name='orders_id' value='$tasks_id' />";
        echo "<input type='hidden' name='itemtype' value='PluginGlpiinventoryDeploy" .
         ucfirst('taskjob') . "' />";

        echo "<div id='taskjobs_block'></div>";
        Html::closeForm();

        $a_taskjobs = getAllDataFromTable(
            $this->getTable(),
            ['plugin_glpiinventory_tasks_id' => $tasks_id],
            false,
            '`ranking`'
        );
        echo  "<div id='drag_taskjob_taskjobs'>";
        echo "<table class='tab_cadrehov package_item_list' id='table_taskjob_$rand' style='width: 950px'>";
        $i = 0;
        foreach ($a_taskjobs as $data) {
            echo Search::showNewLine(Search::HTML_OUTPUT, ($i % 2));
            echo "<td class='control'>";
            Html::showCheckbox(['name'    => 'taskjob_entries[]',
                                  'value'   => $i]);
            echo "</td>";
            echo "<td>";
            echo "<a class='edit' " .
                 "onclick=\"edit_subtype({$data['id']}, this)\">";
            echo $data['name'];
            echo "</a><br />";

            echo "<b>";
            echo __('Definition', 'glpiinventory');
            echo "</b>";
            echo "<ul class='retChecks'>";
            $a_definitions = importArrayFromDB($data['definition']);
            foreach ($a_definitions as $a_definition) {
                foreach ($a_definition as $itemtype => $items_id) {
                    echo "<li>";
                    $item = new $itemtype();
                    $item->getFromDB($items_id);
                    echo $item->getTypeName() . " > ";
                    echo $item->getLink();
                    echo "</li>";
                }
            }
            echo "</ul>";

            echo "<b>";
            echo __('Action', 'glpiinventory');
            echo "</b>";
            echo "<ul class='retChecks'>";
            $a_actions = importArrayFromDB($data['action']);
            foreach ($a_actions as $a_action) {
                foreach ($a_action as $itemtype => $items_id) {
                    echo "<li>";
                    $item = new $itemtype();
                    $item->getFromDB($items_id);
                    echo $item->getTypeName() . " > ";
                    echo $item->getLink();
                    echo "</li>";
                }
            }
            echo "</ul>";

            echo "</td>";
            echo "</td>";
            echo "<td class='rowhandler control' title='" . __('drag', 'glpiinventory') .
            "'><div class='drag row'></div></td>";
            echo "</tr>";
            $i++;
        }
        echo "<tr><th>";
        echo Html::getCheckAllAsCheckbox("taskjobsList$rand", mt_rand());
        echo "</th><th colspan='3' class='mark'></th></tr>";
        echo "</table>";
        echo "</div>";
        echo "&nbsp;&nbsp;<img src='" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png' alt=''>";
        echo "<input type='submit' name='delete' value=\"" .
         __('Delete', 'glpiinventory') . "\" class='submit'>";

       /**
        * Initialize drag and drop on subtype lists
        **/
        echo "<script type=\"text/javascript\">
      redipsInit('taskjob', 'taskjob', $tasks_id);
</script>";

        echo "</table>";
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
        $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'task_forceend'] = __('Force the end', 'glpiinventory');
        return $actions;
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

        $pfTaskjob = new PluginGlpiinventoryTaskjob();

        switch ($ma->getAction()) {
            case "plugin_glpiinventory_transfert":
                foreach ($ids as $key) {
                    $pfTaskjob->getFromDB($key);
                    $pfTaskjob->forceEnd();

                   //set action massive ok for this item
                    $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                }
                break;
        }
    }


   /**
   * Duplicate all taskjobs for a task to another one
   * @param $source_tasks_id the ID of the task to clone
   * @param $target_task_id the ID of the cloned task
   * @return void
   */
    public static function duplicate($source_tasks_id, $target_tasks_id)
    {
        $pfTaskJob = new self();
        $result    = true;
        $taskjobs  = $pfTaskJob->find(['plugin_glpiinventory_tasks_id' => $source_tasks_id]);
        foreach ($taskjobs as $taskjob) {
            $taskjob['plugin_glpiinventory_tasks_id'] = $target_tasks_id;
            unset($taskjob['id']);
            if (!$pfTaskJob->add($taskjob)) {
                $result = false;
            }
        }
        return $result;
    }
}
