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
 * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
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
 * Manage the wake up the agents remotely.
 */
class PluginGlpiinventoryAgentWakeup extends CommonDBTM
{


   /**
    * The right name for this class
    *
    * @var string
    */
    static $rightname = 'plugin_glpiinventory_taskjob';


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
    static function getTypeName($nb = 0)
    {
        return __('Job', 'glpiinventory');
    }


   /**
    * Check if can wake up an agent
    *
    * @return true
    */
    static function canCreate()
    {
        return true;
    }


   /*
    * @function cronWakeupAgents
    * This function update already running tasks with dynamic groups
    */


   /**
    * Cron task: wake up agents. Configuration is in each tasks
    *
    * @global object $DB
    * @param object $crontask
    * @return boolean true if successfully, otherwise false
    */
    static function cronWakeupAgents($crontask)
    {
        global $DB;

        $wakeupArray = [];
        $tasks       = [];
       //Get the maximum number of agent to wakeup,
       //as allowed in the general configuration
        $config = new PluginGlpiinventoryConfig();
        $agent  = new Agent();

        $maxWakeUp   = $config->getValue('wakeup_agent_max');

       //Get all active timeslots
        $timeslot = new PluginGlpiinventoryTimeslot();
        $timeslots = $timeslot->getCurrentActiveTimeslots();
        $query_timeslots = [
         'plugin_glpiinventory_timeslots_exec_id'   => 0
        ];
        if (!empty($timeslots)) {
            array_push($query_timeslots, [
            'plugin_glpiinventory_timeslots_exec_id' => $timeslots
            ]);
        }
       //Get all active task requiring an agent wakeup
       //Check all tasks without timeslot or task with a current active timeslot
        $iterator = $DB->request([
         'SELECT' => ['id', 'wakeup_agent_counter', 'wakeup_agent_time', 'last_agent_wakeup'],
         'FROM'   => 'glpi_plugin_glpiinventory_tasks',
         'WHERE'  => [
            'wakeup_agent_counter'  => ['>', 0],
            'wakeup_agent_time'     => ['>', 0],
            'is_active'             => 1,
            [
               'OR'   => $query_timeslots
            ]
         ]
        ]);

        foreach ($iterator as $task) {
            if (!is_null($task['wakeup_agent_time'])) {
                //Do not wake up is last wake up in inferior to the minimum wake up interval
                $interval   = time() - strtotime($task['last_agent_wakeup']);
                if ($interval < ($task['wakeup_agent_time'] * MINUTE_TIMESTAMP)) {
                    continue;
                }
            }
            $maxWakeUpTask = $task['wakeup_agent_counter'];
            if ($maxWakeUp < $maxWakeUpTask) {
                $maxWakeUpTask = $maxWakeUp;
            }

           //Store task ID
            if (!in_array($task['id'], $tasks)) {
                $tasks[] = $task['id'];
            }

           //For each task, get a number of taskjobs at the PREPARED state
           //(the maximum is defined in wakeup_agent_counter)
            $iterator2 = $DB->request([
            'SELECT'    => [
               'glpi_plugin_glpiinventory_taskjobstates.agents_id',
            ],
            'FROM'      => [
               'glpi_plugin_glpiinventory_taskjobstates'
            ],
            'LEFT JOIN' => [
               'glpi_plugin_glpiinventory_taskjobs' => [
                  'FKEY' => [
                     'glpi_plugin_glpiinventory_taskjobs'    => 'id',
                     'glpi_plugin_glpiinventory_taskjobstates' => 'plugin_glpiinventory_taskjobs_id'
                  ]
               ]
            ],
            'WHERE'     => [
               'glpi_plugin_glpiinventory_taskjobs.plugin_glpiinventory_tasks_id' => $task['id'],
               'glpi_plugin_glpiinventory_taskjobstates.state'  => PluginGlpiinventoryTaskjobstate::PREPARED
            ],
            'ORDER'     => 'glpi_plugin_glpiinventory_taskjobstates.id',
            'START'     => 0,
            ]);
            $counter = 0;

            foreach ($iterator2 as $state) {
                 $agents_id = $state['agents_id'];
                if (isset($wakeupArray[$agents_id])) {
                    $counter++;
                } else {
                    $agent->getFromDB($agents_id);
                    $statusAgent = $agent->requestStatus();
                    if ($statusAgent['answer'] == 'waiting') {
                        $wakeupArray[$agents_id] = $agents_id;
                        $counter++;
                    }
                }

               // check if max number of agent reached for this task
                if ($counter >= $maxWakeUpTask) {
                     break;
                }
            }
        }

       //Number of agents successfully woken up
        $wokeup = 0;
        if (!empty($tasks)) {
           //Update last wake up time each task
            $DB->update(
                'glpi_plugin_glpiinventory_tasks',
                [
                'last_agent_wakeup' => $_SESSION['glpi_currenttime']
                ],
                [
                'id' => $tasks
                ]
            );

           //Try to wake up agents one by one
            foreach ($wakeupArray as $ID) {
                $agent->getFromDB($ID);
                if (self::wakeUp($agent)) {
                    $wokeup++;
                }
            }
        }

        $crontask->addVolume($wokeup);
        return true;
    }

   /**
    * Send a request to the remotely agent to run now
    *
    * @return boolean true if send successfully, otherwise false
    */
    static function wakeUp(Agent $agent)
    {
        $ret = false;

        PluginGlpiinventoryDisplay::disableDebug();
        $urls = $agent->getAgentURLs();

        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        foreach ($urls as $url) {
            if (!$ret) {
                if (@file_get_contents($url, 0, $ctx) !== false) {
                    $ret = true;
                    break;
                }
            }
        }
        PluginGlpiinventoryDisplay::reenableusemode();

        return $ret;
    }
}
