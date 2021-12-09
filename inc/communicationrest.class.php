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
 * Manage the communication in REST with the agents.
 */
class PluginGlpiinventoryCommunicationRest
{


   /**
    * Manage communication between agent and server
    *
    * @param array $params
    * @return array|false array return jobs ready for the agent
    */
    publicstatic  function communicate($params = [])
    {
        $response = [];
        if (isset($params['action']) && isset($params['machineid'])) {
            $agent = new Agent();
            if ($agent->getFromDBByCrit(['deviceid' => $params['machineid']])) {
                $params['agent'] = $agent;
                switch ($params['action']) {
                    case 'getConfig':
                        $response = self::getConfigByAgent($params);
                        break;

                    case 'getJobs':
                        $response = self::getJobsByAgent($params);
                        break;

                    case 'wait':
                        break;
                }
            } else {
                $response = false;
            }
        } else {
            $response = false;
        }
        return $response;
    }


   /**
    * Get configuration for an agent and for modules requested
    *
    * @param array $params
    * @return array
    */
    publicstatic  function getConfigByAgent($params = [])
    {
        $schedule = [];

        if (isset($params['task'])) {
            $pfAgentModule = new PluginGlpiinventoryAgentmodule();
            $a_agent       = $params['agent']->fields;

            foreach (array_keys($params['task']) as $task) {
                foreach (PluginGlpiinventoryStaticmisc::getmethods() as $method) {
                    switch (strtolower($task)) {
                        case 'deploy':
                            $classname = 'PluginGlpiinventoryDeployPackage';
                            break;
                        case 'esx':
                            $classname = 'PluginGlpiinventoryCredentialIp';
                            break;
                        case 'collect':
                             $classname = 'PluginGlpiinventoryCollect';
                            break;
                        default:
                             $classname = '';
                    }

                    $taskname = $method['method'];
                    if (strstr($taskname, 'deploy')) {
                        $taskname = $method['task'];
                    }
                    $class = PluginGlpiinventoryStaticmisc::getStaticMiscClass($method['module']);
                    if (
                        (isset($method['task']) && strtolower($method['task']) == strtolower($task))
                        && (isset($method['use_rest']) && $method['use_rest'])
                        && method_exists($class, self::getMethodForParameters($task))
                        && $pfAgentModule->isAgentCanDo($taskname, $a_agent['id'])
                        && countElementsInTable(
                            'glpi_plugin_glpiinventory_taskjobstates',
                            [
                            'agents_id' => $a_agent['id'],
                            'itemtype' => $classname,
                            'state' => 0,
                            ]
                        ) > 0
                    ) {
                     /*
                     * Since migration, there is only one plugin in one directory
                     * It's maybe time to redo this function -- kiniou
                     */
                        $schedule[]
                        = call_user_func(
                            [$class, self::getMethodForParameters($task)],
                            $a_agent['entities_id']
                        );
                        break; //Stop the loop since we found the module corresponding to the asked task
                    }
                }
            }
        }
        return ['configValidityPeriod' => 600, 'schedule' => $schedule];
    }


   /**
    * Get jobs for an agent
    * TODO: This methods must be used inplace of other methods in order to mutualize code and
    * to fully support agent REST API for every task's types
    *       -- kiniou
    *
    * @param array $params
    * @return false
    */
    publicstatic  function getJobsByAgent($params = [])
    {
       //      $jobs = [];
       //      $methods = PluginGlpiinventoryStaticmisc::getmethods();
       //      if (isset($params['task'])) {
       //         foreach (array_keys($params['task']) as $task) {
       //
       //         }
       //      }
        return false;
    }


   /**
    * Send to the agent an OK code
    */
    publicstatic  function sendOk()
    {
        header("HTTP/1.1 200", true, 200);
    }


   /**
    * Send to the agent an error code
    * when the request sent by the agent is invalid
    */
    publicstatic  function sendError()
    {
        header("HTTP/1.1 400", true, 400);
    }


   /**
    * Generate the function name related to the module to get parameters
    *
    * @param string $task
    * @return string
    */
    publicstatic  function getMethodForParameters($task)
    {
        return "task_" . strtolower($task) . "_getParameters";
    }


   /**
    * Update agent status for a taskjob
    *
    * @global object $DB
    * @param array $params
    */
    publicstatic  function updateLog($params = [])
    {
        global $DB;

        $p              = [];
        $p['machineid'] = ''; //DeviceId
        $p['uuid']      = ''; //Task uuid
        $p['msg']       = 'ok'; //status of the task
        $p['code']      = ''; //current step of processing
        $p['sendheaders'] = true;

        foreach ($params as $key => $value) {
            $p[$key] = $value;
        }

       //Get the agent ID by its deviceid
        $agent = new Agent();

       //No need to continue since the requested agent doesn't exist in database
        if ($agent->getFromDBByCrit(['deviceid' => $p['machineid']]) === false) {
            if ($p['sendheaders']) {
                self::sendError();
            }
            return;
        }

        $taskjobstate = new PluginGlpiinventoryTaskjobstate();

       //Get task job status : identifier is the uuid given by the agent
        $params = ['FROM' => getTableForItemType("PluginGlpiinventoryTaskjobstate"),
                 'FIELDS' => 'id',
                 'WHERE' => ['uniqid' => $p['uuid']]
                ];
        foreach ($DB->request($params) as $jobstate) {
            $taskjobstate->getFromDB($jobstate['id']);

           //Get taskjoblog associated
            $taskjoblog = new PluginGlpiinventoryTaskjoblog();
            switch ($p['code']) {
                case 'running':
                    $taskjoblog->addTaskjoblog(
                        $taskjobstate->fields['id'],
                        $taskjobstate->fields['items_id'],
                        $taskjobstate->fields['itemtype'],
                        PluginGlpiinventoryTaskjoblog::TASK_RUNNING,
                        $p['msg']
                    );
                    break;

                case 'ok':
                case 'ko':
                    $taskjobstate->changeStatusFinish(
                        $taskjobstate->fields['id'],
                        $taskjobstate->fields['items_id'],
                        $taskjobstate->fields['itemtype'],
                        ($p['code'] == 'ok' ? 0 : 1),
                        $p['msg']
                    );
                    break;
            }
        }
        if ($p['sendheaders']) {
            self::sendOk();
        }
    }


   /**
    * Test a given url
    *
    * @param string $url
    * @return boolean
    */
    publicstatic  function testRestURL($url)
    {

       //If fopen is not allowed, we cannot check and then return true...
        if (!ini_get('allow_url_fopen')) {
            return true;
        }

        $handle = fopen($url, 'rb');
        if (!$handle) {
            return false;
        } else {
            fclose($handle);
            return true;
        }
    }


   /**
    * Manage REST parameters
    **/
    publicstatic  function handleFusionCommunication()
    {
        $response = PluginGlpiinventoryCommunicationRest::communicate($_GET);
        if ($response) {
            echo json_encode($response);
        } else {
            PluginGlpiinventoryCommunicationRest::sendError();
        }
    }
}
