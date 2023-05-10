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

use Glpi\Toolbox\Sanitizer;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Manage the prepare task job and give the data to the agent when request what
 * to deploy.
 */
class PluginGlpiinventoryDeployCommon extends PluginGlpiinventoryCommunication
{
   /**
    * Check if definition_type is present in definitions_filter array.
    * This function returns true if the definition_type is not in
    * definitions_filter array.
    * If definitions_filter is NULL, this check is inhibited and return false.
    *
    * @param string $definition_type
    * @param null|array $definitions_filter
    * @return boolean
    */
    public function definitionFiltered($definition_type, $definitions_filter)
    {
        if (
            !is_null($definitions_filter)
              && is_array($definitions_filter)
              && count($definitions_filter) > 0
              && !in_array($definition_type, $definitions_filter)
        ) {
            return true;
        }
        return false;
    }


   /**
    * Prepare a takjob, get all devices and put in taskjobstate each task
    * for each device for each agent
    *
    * @global object $DB
    * @param integer $taskjob_id id of the taskjob
    * @param null|array $definitions_filter
    */
    public function prepareRun($taskjob_id, $definitions_filter = null)
    {
        global $DB;

        $task       = new PluginGlpiinventoryTask();
        $job        = new PluginGlpiinventoryTaskjob();
        $joblog     = new PluginGlpiinventoryTaskjoblog();
        $jobstate   = new PluginGlpiinventoryTaskjobstate();
        $agent      = new Agent();
        $agentmodule = new PluginGlpiinventoryAgentmodule();

        $job->getFromDB($taskjob_id);
        $task->getFromDB($job->fields['plugin_glpiinventory_tasks_id']);

        $communication = $task->fields['communication'];

        $actions     = importArrayFromDB($job->fields['action']);
        $definitions = importArrayFromDB($job->fields['definition']);
        $taskvalid   = 0;

        $computers = [];
        foreach ($actions as $action) {
            $itemtype = key($action);
            $items_id = current($action);

            switch ($itemtype) {
                case 'Computer':
                    if ($this->definitionFiltered("Computer", $definitions_filter)) {
                        break;
                    }
                    $computers[] = $items_id;
                    break;

                case 'Group':
                    if ($this->definitionFiltered("Group", $definitions_filter)) {
                        break;
                    }
                    $computer_object = new Computer();

                   //find computers by user associated with this group
                    $group_users   = new Group_User();
                    $group         = new Group();
                    $group->getFromDB($items_id);

                    $computers_a_1 = [];
                    $computers_a_2 = [];

                    $members = $group_users->getGroupUsers($items_id);

                    foreach ($members as $member) {
                        $computers = $computer_object->find(
                            ['users_id'    => $member['id'],
                            'is_deleted'  => 0,
                            'is_template' => 0]
                        );
                        foreach ($computers as $computer) {
                               $computers_a_1[] = $computer['id'];
                        }
                    }

                 //find computers directly associated with this group
                    $computers = $computer_object->find(
                        ['groups_id'   => $items_id,
                        'is_deleted'  => 0,
                        'is_template' => 0]
                    );
                    foreach ($computers as $computer) {
                         $computers_a_2[] = $computer['id'];
                    }

                   //merge two previous array and deduplicate entries
                    $computers = array_unique(array_merge($computers_a_1, $computers_a_2));
                    break;

                case 'PluginGlpiinventoryDeployGroup':
                    $group = new PluginGlpiinventoryDeployGroup();
                    $group->getFromDB($items_id);

                    switch ($group->getField('type')) {
                        case 'STATIC':
                            if ($this->definitionFiltered("PluginGlpiinventoryDeployGroupStatic", $definitions_filter)) {
                                break;
                            }
                            $iterator = $DB->request([
                                'SELECT' => 'items_id',
                                'FROM'   => 'glpi_plugin_glpiinventory_deploygroups_staticdatas',
                                'WHERE'  => [
                                    'groups_id' => $items_id,
                                    'itemtype'  => 'Computer'
                                ]
                            ]);
                            foreach ($iterator as $row) {
                                $computers[] = $row['items_id'];
                            }
                            break;
                        case 'DYNAMIC':
                            if ($this->definitionFiltered("PluginGlpiinventoryDeployGroupDynamic", $definitions_filter)) {
                                break;
                            }

                            //$definitions_filter is NULL = update by crontask !
                            $where = [];
                            if ($definitions_filter != null) {
                                $where['can_update_group'] = 1;
                            }

                            $iterator = $DB->request([
                                'SELECT' => 'fields_array',
                                'FROM'   => 'glpi_plugin_glpiinventory_deploygroups_dynamicdatas',
                                'WHERE'  => [
                                    'groups_id' => $items_id
                                ] + $where,
                                'LIMIT'  => 1
                            ]);

                            //No dynamic groups have been found : break
                            if (count($iterator) == 0) {
                                break;
                            }
                            $row = $iterator->current();

                            if (isset($_GET)) {
                                $get_tmp = $_GET;
                            }
                            if (isset($_SESSION["glpisearchcount"]['Computer'])) {
                                unset($_SESSION["glpisearchcount"]['Computer']);
                            }
                            if (isset($_SESSION["glpisearchcount2"]['Computer'])) {
                                unset($_SESSION["glpisearchcount2"]['Computer']);
                            }

                            $_GET = importArrayFromDB($row['fields_array']);

                            $_GET["glpisearchcount"] = count($_GET['field']);
                            if (isset($_GET['field2'])) {
                                $_GET["glpisearchcount2"] = count($_GET['field2']);
                            }

                            $pfSearch = new PluginGlpiinventorySearch();
                            Search::manageParams('Computer');
                            $glpilist_limit             = $_SESSION['glpilist_limit'];
                            $_SESSION['glpilist_limit'] = 999999999;
                            $result                     = $pfSearch->constructSQL('Computer', $_GET);
                            $_SESSION['glpilist_limit'] = $glpilist_limit;
                            while ($data = $DB->fetchArray($result)) {
                                $computers[] = $data['id'];
                            }
                            if (count($get_tmp) > 0) {
                                $_GET = $get_tmp;
                            }
                            break;
                    }
                    break;
            }
        }

       //Remove duplicatas from array
       //We are using isset for faster processing than array_unique because we might have many
       //entries in this list.
        $tmp_computers = [];
        foreach ($computers as $computer) {
            if (!isset($tmp_computers[$computer])) {
                $tmp_computers[$computer] = 1;
            }
        }
        $computers = array_keys($tmp_computers);

        $c_input = [];
        $c_input['plugin_glpiinventory_taskjobs_id'] = $job->fields['id'];
        $c_input['state']                              = 0;
        $c_input['agents_id']   = 0;
        $c_input['execution_id']                       = $task->fields['execution_id'];

        $package = new PluginGlpiinventoryDeployPackage();

        foreach ($computers as $computer_id) {
           //Unique Id match taskjobstatuses for an agent(computer)

            foreach ($definitions as $definition) {
                $uniqid = uniqid();
                $package->getFromDB($definition['PluginGlpiinventoryDeployPackage']);

                $c_input['state']    = 0;
                $c_input['itemtype'] = 'PluginGlpiinventoryDeployPackage';
                $c_input['items_id'] = $package->fields['id'];
                $c_input['date']     = date("Y-m-d H:i:s");
                $c_input['uniqid']   = $uniqid;

               //get agent for this computer
                $agent->getFromDBByCrit(['itemtype' => 'Computer', 'items_id' => $computer_id]);
                $agents_id = $agent->fields['id'] ?? false;
                if ($agents_id === false) {
                    $jobstates_id = $jobstate->add($c_input);
                    $jobstate->changeStatusFinish(
                        $jobstates_id,
                        0,
                        '',
                        1,
                        "No agent found for [[Computer::" . $computer_id . "]]"
                    );
                } else {
                    if ($agentmodule->isAgentCanDo('DEPLOY', $agents_id)) {
                        $c_input['agents_id'] = $agents_id;

                        $jobstates_running = $jobstate->find(
                            ['itemtype'                         => 'PluginGlpiinventoryDeployPackage',
                            'items_id'                         => $package->fields['id'],
                            'state'                            => ['!=', PluginGlpiinventoryTaskjobstate::FINISHED],
                            'agents_id' => $agents_id
                            ]
                        );

                        if (count($jobstates_running) == 0) {
                            // Push the agent, in the stack of agent to awake
                            if ($communication == "push") {
                                $_SESSION['glpi_plugin_glpiinventory']['agents'][$agents_id] = 1;
                            }

                            $jobstates_id = $jobstate->add($c_input);

                            //Add log of taskjob
                            $c_input['plugin_glpiinventory_taskjobstates_id'] = $jobstates_id;
                            $c_input['state'] = PluginGlpiinventoryTaskjoblog::TASK_PREPARED;
                            $taskvalid++;
                            $joblog->add($c_input);
                            unset($c_input['state']);
                            unset($c_input['agents_id']);
                        }
                    }
                }
            }
        }
        if ($taskvalid > 0) {
            $job->fields['status'] = 1;
            $job->update($job->fields);
        } else {
            $job->reinitializeTaskjobs($job->fields['plugin_glpiinventory_tasks_id']);
        }
    }


   /**
    * run function, so return data to send to the agent for deploy
    *
    * @param object $taskjobstate PluginGlpiinventoryTaskjobstate instance
    * @return array
    */
    public function run($taskjobstate)
    {

       //Check if the job has been postponed
        if (
            !is_null($taskjobstate->fields['date_start'])
            && $taskjobstate->fields['date_start'] > $_SESSION['glpi_currenttime']
        ) {
           //If the job is postponed and the execution date is in the future,
           //skip the job for now
            return false;
        }

       //get order by type and package id
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $pfDeployPackage->getFromDB($taskjobstate->fields['items_id']);
       //decode order data
        $order_data = json_decode($pfDeployPackage->fields['json'], true);

       /* TODO:
       * This has to be done properly in each corresponding classes.
       * Meanwhile, I just split the data to rebuild a proper and compliant JSON
       */
        $order_job = $order_data['jobs'];
       //add uniqid to response data
        $order_job['uuid'] = $taskjobstate->fields['uniqid'];

       /* TODO:
       * Orders should only contain job data and associatedFiles should be retrieved from the
       * list inside Orders data like the following :
       *
       * $order_files = []
       * foreach ($order_job["associatedFiles"] as $hash) {
       *    if (!isset($order_files[$hash]) {
       *       $order_files[$hash] = PluginGlpiinventoryDeployFile::getByHash($hash);
       *       $order_files[$hash]['mirrors'] = $mirrors
       *    }
       * }
       */
        $order_files = $order_data['associatedFiles'];

       //Add mirrors to associatedFiles
        $mirrors = PluginGlpiinventoryDeployMirror::getList(
            $taskjobstate->fields['agents_id']
        );
        foreach ($order_files as $hash => $params) {
            $order_files[$hash]['mirrors'] = $mirrors;
            $manifest = PLUGIN_GLPI_INVENTORY_MANIFESTS_DIR . $hash;
            $order_files[$hash]['multiparts'] = [];
            if (file_exists($manifest)) {
                $handle = fopen($manifest, "r");
                if ($handle) {
                    while (($buffer = fgets($handle)) !== false) {
                        $order_files[$hash]['multiparts'][] = trim($buffer);
                    }
                    fclose($handle);
                }
            }
        }
       //Send an empty json dict instead of empty json list
        if (count($order_files) == 0) {
            $order_files = (object)[];
        }

       // Fix some command like : echo "write in file" >> c:\TEMP\HELLO.txt
        if (isset($order_job['actions'])) {
            foreach ($order_job['actions'] as $key => $value) {
                if (isset($value['cmd']) && isset($value['cmd']['exec'])) {
                    $order_job['actions'][$key]['cmd']['exec'] = Sanitizer::unsanitize($value['cmd']['exec']);
                }
            }
        }

        $order = [
         "job"             => $order_job,
         "associatedFiles" => $order_files
        ];
        return $order;
    }
}
