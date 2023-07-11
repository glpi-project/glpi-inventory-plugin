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
 * Manage the state of task jobs.
 */
class PluginGlpiinventoryTaskjobstate extends CommonDBTM
{
   /**
    * Define constant state prepared.
    * The job is just prepared and waiting for agent request
    *
    * @var integer
    */
    const PREPARED = 0;

   /**
    * Define constant state has sent data to agent and not have the answer.
    * The job is running and the server sent the job config
    *
    * @var integer
    */
    const SERVER_HAS_SENT_DATA = 1;

   /**
    * Define constant state agent has sent data.
    * The job is running and the agent sent reply to the server
    *
    * @var integer
    */
    const AGENT_HAS_SENT_DATA = 2;

   /**
    * Define constant state finished.
    * The agent completed successfully the job
    *
    * @var integer
    */
    const FINISHED = 3;

   /**
    * Define constant state in error.
    * The agent failed to complete the job
    *
    * @var integer
    */
    const IN_ERROR = 4;

   /**
    * Define constant state cancelled
    * The job has been cancelled either by a user or the agent himself (eg. if
    * it has been forbidden to run this taskjob)
    *
    * @var integer
    */
    const CANCELLED = 5;

   /**
    * Define constant state in error.
    * The agent failed to complete the job
    *
    * @var integer
    */
    const POSTPONED = 6;

   /**
    * Initialize the public method
    *
    * @var string
    */
    public $method = '';


    public static $rightname = 'plugin_glpiinventory_task';

   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string name of the tab
    */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case 'Computer':
                return __("Tasks / Groups", "glpiinventory");
            break;

            case 'PluginGlpiinventoryTask':
                return __("Job executions", "glpiinventory");
            break;
        }
    }


   /**
    * Get all states name
    *
    * @return array
    */
    public static function getStateNames()
    {
        return [
         self::PREPARED             => __('Prepared', 'glpiinventory'),
         self::SERVER_HAS_SENT_DATA => __('Server has sent data to the agent', 'glpiinventory'),
         self::AGENT_HAS_SENT_DATA  => __('Agent replied with data to the server', 'glpiinventory'),
         self::FINISHED             => __('Finished', 'glpiinventory'),
         self::IN_ERROR             => __('Error', 'glpiinventory'),
         self::CANCELLED            => __('Cancelled', 'glpiinventory'),
         self::POSTPONED            => __('Postponed', 'glpiinventory')
        ];
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
        if ($item->getType() == 'PluginGlpiinventoryTask') {
            $item->showJobLogs();
            return true;
        } elseif ($item->getType() == 'Computer') {
            $pfTaskJobState = new PluginGlpiinventoryTaskjobstate();
            $pfTaskJobState->showStatesForComputer($item->fields['id']);
            echo "<br>";
            $pfDeployGroup = new PluginGlpiinventoryDeployGroup();
            $pfDeployGroup->showForComputer($item->fields['id']);
        }
        return false;
    }


   /**
   * Display state of taskjob
   *
   * @param integer $taskjobs_id id of the taskjob
   * @param integer $width how large in pixel display array
   * @param string $return display or return in var (html or htmlvar or other value
   *        to have state number in %)
   * @param string $style '' = normal or 'simple' for very simple display
   *
   * @return string
   *
   **/
    public function stateTaskjob($taskjobs_id, $width = 930, $return = 'html', $style = '')
    {
        global $DB;

        $state = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        $total = 0;
        $iterator = $DB->request(['FROM'  => 'glpi_plugin_glpiinventory_taskjobstates',
                                'WHERE' => ['plugin_glpiinventory_taskjobs_id' => $taskjobs_id,
                                            'state' => ['NOT', self::FINISHED]]
                               ]);
        if ($iterator->numrows() > 0) {
            foreach ($iterator as $data) {
                $total++;
                $state[$data['state']]++;
            }
            if ($total == '0') {
                $globalState = 0;
            } else {
                $first       = 25;
                $second      = ((($state[1] + $state[2] + $state[3]) * 100) / $total) / 4;
                $third       = ((($state[2] + $state[3]) * 100) / $total) / 4;
                $fourth      = (($state[3] * 100) / $total) / 4;
                $globalState = $first + $second + $third + $fourth;
            }
            if ($return == 'html') {
                if ($style == 'simple') {
                    Html::displayProgressBar($width, ceil($globalState), ['simple' => 1]);
                } else {
                    Html::displayProgressBar($width, ceil($globalState));
                }
            } elseif ($return == 'htmlvar') {
                if ($style == 'simple') {
                    return PluginGlpiinventoryDisplay::getProgressBar(
                        $width,
                        ceil($globalState),
                        ['simple' => 1]
                    );
                } else {
                    return PluginGlpiinventoryDisplay::getProgressBar(
                        $width,
                        ceil($globalState)
                    );
                }
            } else {
                return ceil($globalState);
            }
        }
        return '';
    }


   /**
    * Change the state
    *
    * @todo There is no need to pass $id since we should use this method with
    *       an instantiated object
    *
    * @param integer $id id of the taskjobstate
    * @param integer $state state to set
    */
    public function changeStatus($id, $state)
    {
        $this->update(['id' => $id, 'state' => $state]);
    }


   /**
    * Get taskjobs of an agent
    *
    * @param integer $agent_id id of the agent
    */
    public function getTaskjobsAgent($agent_id)
    {
        global $DB;

        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $moduleRun = [];
        $params = ['FROM'   => 'glpi_plugin_glpiinventory_taskjobstates',
                 'FIELDS' => 'plugin_glpiinventory_taskjobs_id',
                 'WHERE'  => ['agents_id' => $agent_id,
                              'state' => self::PREPARED],
                  'ORDER' => 'id'
                ];
        foreach ($DB->request($params) as $data) {
           // Get job and data to send to agent
            if ($pfTaskjob->getFromDB($data['plugin_glpiinventory_taskjobs_id'])) {
                $moduleName = PluginGlpiinventoryModule::getModuleName($pfTaskjob->fields['plugins_id']);
                if ($moduleName) {
                    $className = "Plugin" . ucfirst($moduleName) . ucfirst($pfTaskjob->fields['method']);
                    $moduleRun[$className][] = $data;
                }
            }
        }
        return $moduleRun;
    }


   /**
    * Process ajax parameters for getLogs() methods
    *
    * since 0.85+1.0
    * @param array $params list of ajax expected 'id' and 'last_date' parameters
    * @return string in json format, encoded list of logs grouped by jobstates
    */
    public function ajaxGetLogs($params)
    {
        $id        = null;
        $last_date = null;

        if (isset($params['id']) and $params['id'] > 0) {
            $id = $params['id'];
        }
        if (isset($params['last_date'])) {
            $last_date = $params['last_date'];
        }
        if (!preg_match("/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/", $last_date)) {
            $last_date = null;
        }
        if (!is_null($id) && !is_null($last_date)) {
            echo json_encode($this->getLogs($id, $last_date));
        }
    }


   /**
    * Get logs associated to a jobstate.
    *
    * @global object $DB
    * @param integer $id
    * @param string $last_date
    * @return array
    */
    public function getLogs($id, $last_date)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'log.id',
                'log.date',
                'log.comment',
                'log.state',
                'run.uniqid AS runid'
            ],
            'FROM'   => 'glpi_plugin_glpiinventory_taskjoblogs AS log',
            'LEFT JOIN' => [
                'glpi_plugin_glpiinventory_taskjobstates AS run' => [
                    'ON' => [
                        'run' => 'id',
                        'log' => 'plugin_glpiinventory_taskjobstates_id'
                    ]
                ]
            ],
            'WHERE'  => [
                'run.id' => $id,
                'log.date' => ['<=', $last_date]
            ],
            'ORDER'  => 'log.id DESC'
        ]);

        $logs = [];
        foreach ($iterator as $result) {
            $run_id = $result['runid'];
            $logs['run']    = $run_id;
            $logs['logs'][] = [
            'log.id'      => $result['id'],
            'log.comment' => PluginGlpiinventoryTaskjoblog::convertComment($result['comment']),
            'log.date'    => $result['date'],
            'log.f_date'  => Html::convDateTime($result['date']),
            'log.state'   => $result['state']
            ];
        }

        return $logs;
    }


   /**
    * Change the status to finish
    *
    * @param integer $taskjobstates_id id of the taskjobstates
    * @param integer $items_id id of the item
    * @param string $itemtype type of the item
    * @param integer $error error
    * @param string $message message for the status
    */
    public function changeStatusFinish($taskjobstates_id, $items_id, $itemtype, $error = 0, $message = '')
    {

        $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
        $pfTaskjob    = new PluginGlpiinventoryTaskjob();

        $this->getFromDB($taskjobstates_id);
        $input          = [];
        $input['id']    = $this->fields['id'];
        $input['state'] = self::FINISHED;

        $log_input = [];
        if ($error == "1") {
            $log_input['state'] = PluginGlpiinventoryTaskjoblog::TASK_ERROR;
            $input['state']     = self::IN_ERROR;
        } else {
            $log_input['state'] = PluginGlpiinventoryTaskjoblog::TASK_OK;
            $input['state']     = self::FINISHED;
        }

        $this->update($input);
        $log_input['plugin_glpiinventory_taskjobstates_id'] = $taskjobstates_id;
        $log_input['items_id'] = $items_id;
        $log_input['itemtype'] = $itemtype;
        $log_input['date']     = $_SESSION['glpi_currenttime'];
        $log_input['comment']  = $message;
        $log_input             = Toolbox::addslashes_deep($log_input);
        $pfTaskjoblog->add($log_input);

        $pfTaskjob->getFromDB($this->fields['plugin_glpiinventory_taskjobs_id']);
    }


   /**
    * Update taskjob(log) in error
    *
    * @param string $reason
    */
    public function fail($reason = '')
    {
        $this->updateState(
            PluginGlpiinventoryTaskjoblog::TASK_ERROR,
            self::IN_ERROR,
            $reason
        );
    }


   /*
    * Postpone a job
    * @param string $type the type of interaction (before download, etc)
    * @param string $reason the text to be displayed
    */
    public function postpone($type, $reason = '')
    {
        $this->updateState(
            PluginGlpiinventoryTaskjoblog::TASK_INFO,
            self::POSTPONED,
            $reason
        );
        $this->processPostonedJob($type);
    }


   /**
    * Cancel a taskjob
    *
    * @param string $reason
    */
    public function cancel($reason = '')
    {
        $this->updateState(
            PluginGlpiinventoryTaskjoblog::TASK_INFO,
            self::CANCELLED,
            $reason
        );
    }


   /**
    * Update the state of a jobstate
    * @since 9.2
    *
    * @param string $joblog_state the state of the joblog to set
    * @param string $jobstate_state the state of the jobstate to set
    * @param string $reason
    */
    public function updateState($joblog_state, $jobstate_state, $reason = '')
    {

        $log       = new PluginGlpiinventoryTaskjoblog();
        $log_input = [
         'plugin_glpiinventory_taskjobstates_id' => $this->fields['id'],
         'items_id' => $this->fields['items_id'],
         'itemtype' => $this->fields['itemtype'],
         'date'     => $_SESSION['glpi_currenttime'],
         'state'    => $joblog_state,
         'comment'  => Toolbox::addslashes_deep($reason)
        ];

        $log->add($log_input);
        $this->update([
         'id'    => $this->fields['id'],
         'state' => $jobstate_state
        ]);
    }


    private function processPostonedJob($type)
    {

        $pfDeployUserInteraction = new PluginGlpiinventoryDeployUserinteraction();
       //Let's browse all user interactions
        foreach ($pfDeployUserInteraction->getItemValues($this->fields['items_id']) as $interaction) {
           //Look for the user interaction that matches our event
            if ($interaction['type'] == $type && $interaction['template']) {
                $params = $this->fields;

                //Found, let's load the template
                $template  = new PluginGlpiinventoryDeployUserinteractionTemplate();
                if ($template->getFromDB($interaction['template'])) {
                   //Get the template values
                    $template_values = $template->getValues();
                   //Compute the next run date for the job. Retry_after value is in seconds
                    $date = new \DateTime('+' . $template_values['retry_after'] . ' seconds');
                    $params['date_start'] = $date->format('Y-m-d H:i');
                   //Set the max number or retry
                   //(we set it each time a job is postponed because the value
                   //can change in the template)
                    $params['max_retry'] = $template_values['nb_max_retry'];
                    $params['nb_retry']  = $params['nb_retry'] + 1;
                    $params['state']     = self::PREPARED;
                    $states_id           = $params['id'];
                    $this->update($params);

                    $reason    = '-----------------------------------------------------';
                    $log       = new PluginGlpiinventoryTaskjoblog();
                    $log_input = [
                    'plugin_glpiinventory_taskjobstates_id' => $states_id,
                    'items_id' => $this->fields['items_id'],
                    'itemtype' => $this->fields['itemtype'],
                    'date'     => $_SESSION['glpi_currenttime'],
                    'state'    => PluginGlpiinventoryTaskjoblog::TASK_INFO,
                    'comment'  => Toolbox::addslashes_deep($reason)
                    ];
                    $log->add($log_input);

                    $reason = sprintf(
                        __('Job available for next execution at %s', 'glpiinventory'),
                        Html::convDateTime($params['date_start'], 'glpiinventory')
                    );

                    $log_input = [
                     'plugin_glpiinventory_taskjobstates_id' => $states_id,
                     'items_id' => $this->fields['items_id'],
                     'itemtype' => $this->fields['itemtype'],
                     'date'     => $_SESSION['glpi_currenttime'],
                     'state'    => PluginGlpiinventoryTaskjoblog::TASK_STARTED,
                     'comment'  => Toolbox::addslashes_deep($reason)
                    ];
                    $log->add($log_input);

                    if ($params['nb_retry'] <= $params['max_retry']) {
                         $reason = ' ' . sprintf(__('Retry #%d', 'glpiinventory'), $params['nb_retry']);
                    } else {
                        $reason = ' ' . sprintf(__('Maximum number of retry reached: force deployment', 'glpiinventory'));
                    }
                    $log_input = [
                    'plugin_glpiinventory_taskjobstates_id' => $states_id,
                    'items_id' => $this->fields['items_id'],
                    'itemtype' => $this->fields['itemtype'],
                    'date'     => $_SESSION['glpi_currenttime'],
                    'state'    => PluginGlpiinventoryTaskjoblog::TASK_INFO,
                    'comment'  => Toolbox::addslashes_deep($reason)
                    ];
                    $log->add($log_input);
                }
            }
        }
    }


   /**
    * Cron task: clean taskjob (retention time)
    *
    * @global object $DB
    */
    public static function cronCleantaskjob()
    {
        global $DB;

        $config         = new PluginGlpiinventoryConfig();
        $retentiontime  = $config->getValue('delete_task');
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();

        $iterator = $DB->request([
            'FROM'   => 'glpi_plugin_glpiinventory_taskjoblogs',
            'WHERE'  => [
                'date'  => ['<', new \QueryExpression('DATE_ADD(NOW(), INTERVAL -' . $retentiontime . ' DAY)')]
            ],
            'GROUPBY' => 'plugin_glpiinventory_taskjobstates_id'
        ]);

        if (count($iterator)) {
            $delete = $DB->buildDelete(
                'glpi_plugin_glpiinventory_taskjoblogs',
                [
                'plugin_glpiinventory_taskjobstates_id' => new \QueryParam()
                ]
            );
            $stmt = $DB->prepare($delete);
            foreach ($iterator as $data) {
                 $pfTaskjobstate->getFromDB($data['plugin_glpiinventory_taskjobstates_id']);
                 $pfTaskjobstate->delete($pfTaskjobstate->fields, 1);

                 $stmt->bind_param('s', $data['plugin_glpiinventory_taskjobstates_id']);
                 $DB->executeStatement($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }


   /**
   * Fill a taskjobstate by it's uuid
   * @since 9.2
   * @param uniqid taskjobstate's uniqid
   */
    public function getFromDBByUniqID($uniqid)
    {
        $result = $this->find(['uniqid' => $uniqid], [], 1);
        if (!empty($result)) {
            $this->fields = array_pop($result);
        }
    }


   /**
    * Display the tasks where the computer is associated
    *
    * @param integer $computers_id
    */
    public function showStatesForComputer($computers_id)
    {
        global $DB;

        $agent      = new Agent();
        $pfTask       = new PluginGlpiinventoryTask();
        $pfTaskjob    = new PluginGlpiinventoryTaskjob();
        $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();

       // Get the agent of the computer
        $agent->getFromDBByCrit(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $agents_id = $agent->fields['id'];

        $tasks_id = [];

       // Get tasks ids
        $iterator = $DB->request([
         'FROM'   => $this->getTable(),
         'WHERE'  => [
            'agents_id' => $agents_id,
         ],
         'ORDER' => 'id DESC',
        ]);
        foreach ($iterator as $data) {
            $pfTaskjob->getFromDB($data['plugin_glpiinventory_taskjobs_id']);
            $pfTask->getFromDB($pfTaskjob->fields['plugin_glpiinventory_tasks_id']);
            if (!isset($tasks_id[$pfTask->fields['id']])) {
                $tasks_id[$pfTask->fields['id']] = [
                 'is_active' => $pfTask->fields['is_active'],
                 'jobstates' => [],
                 'method'    => $pfTaskjob->fields['method'],
                 'name'      => $pfTask->fields['name'],
                ];
            }
            // Limit to 5 last runs
            if (count($tasks_id[$pfTask->fields['id']]['jobstates']) < 5) {
                $tasks_id[$pfTask->fields['id']]['jobstates'][] = $data['id'];
            }
        }
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
        echo "<th>";
        echo _n('Date', 'Dates', 1);
        echo "</th>";
        echo "<th>";
        echo __('Status');
        echo "</th>";
        echo "</tr>";

        $modules_methods = PluginGlpiinventoryStaticmisc::getModulesMethods();
        $link = Toolbox::getItemTypeFormURL("PluginGlpiinventoryTask");
        $stateColors = [
         PluginGlpiinventoryTaskjoblog::TASK_PREPARED => '#efefef',
         PluginGlpiinventoryTaskjoblog::TASK_RUNNING  => '#aaaaff',
         PluginGlpiinventoryTaskjoblog::TASK_STARTED  => '#aaaaff',
         PluginGlpiinventoryTaskjoblog::TASK_OK       => '#aaffaa',
         PluginGlpiinventoryTaskjoblog::TASK_ERROR    => '#ff0000',
        ];

        foreach ($tasks_id as $id => $data) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<a href='" . $link . "?id=" . $id . "'>" . $data['name'] . "</a>";
            echo "</td>";
            echo "<td>";
            echo Dropdown::getYesNo($data['is_active']);
            echo "</td>";
            echo "<td>";
            echo $modules_methods[$data['method']];
            echo "</td>";
            echo "<td colspan='2'>";
            echo "</td>";
            echo "</tr>";

           // Each taskjobstate
            foreach ($data['jobstates'] as $jobstates_id) {
                $logs = $pfTaskjoblog->find(['plugin_glpiinventory_taskjobstates_id' => $jobstates_id], ['id DESC'], 1);
                if (count($logs) > 0) {
                    $log = current($logs);
                    echo "<tr class='tab_bg_1'>";
                    echo "<td colspan='3'>";
                    echo "</td>";
                    echo "</td>";
                    echo "<td style='background-color: " . ($stateColors[$log['state']] ?? '#ffffff') . "'>";
                    echo Html::convDateTime($log['date']);
                    echo "</td>";
                    echo "<td style='background-color: " . ($stateColors[$log['state']] ?? '#ffffff') . "'>";
                    echo $pfTaskjoblog->getStateName($log['state']);
                    // status
                    echo "</td>";
                    echo "</tr>";
                }
            }
        }
        echo "</table>";
    }
}
