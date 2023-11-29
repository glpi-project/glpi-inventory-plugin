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
 * Manage the taskjob for VMWARE ESX / VCENTER remote inventory.
 */
class PluginGlpiinventoryInventoryComputerESX extends PluginGlpiinventoryCommunication
{
   /**
    * Get all devices and put in taskjobstate each task for
    * each device for each agent
    *
    * @param integer $taskjobs_id id of taskjob esx
    * @return string uniqid value
    */
    public function prepareRun($taskjobs_id)
    {

        $task       = new PluginGlpiinventoryTask();
        $job        = new PluginGlpiinventoryTaskjob();
        $joblog     = new PluginGlpiinventoryTaskjoblog();
        $jobstate  = new PluginGlpiinventoryTaskjobstate();

        $uniqid = uniqid();

        $job->getFromDB($taskjobs_id);
        $task->getFromDB($job->fields['plugin_glpiinventory_tasks_id']);

       //list all agents
        $agent_actions     = importArrayFromDB($job->fields['action']);
        $task_definitions  = importArrayFromDB($job->fields['definition']);
        $agent_actionslist = [];
        foreach ($agent_actions as $targets) {
            foreach ($targets as $itemtype => $items_id) {
                $item = new $itemtype();
                // Detect if agent exists
                if ($item->getFromDB($items_id)) {
                    $agent_actionslist[$items_id] = 1;
                }
            }
        }

       // *** Add jobstate
        if (empty($agent_actionslist)) {
            $a_input = [];
            $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
            $a_input['state']                              = 0;
            $a_input['agents_id'] = 0;
            $a_input['uniqid']                             = $uniqid;
            $a_input['execution_id']                       = $task->fields['execution_id'];

            foreach ($task_definitions as $task_definition) {
                foreach ($task_definition as $task_itemtype => $task_items_id) {
                    $a_input['itemtype'] = $task_itemtype;
                    $a_input['items_id'] = $task_items_id;
                    $jobstates_id = $jobstate->add($a_input);
                    //Add log of taskjob
                    $a_input['plugin_glpiinventory_taskjobstates_id'] = $jobstates_id;
                    $a_input['state'] = PluginGlpiinventoryTaskjoblog::TASK_PREPARED;
                    $a_input['date']  = date("Y-m-d H:i:s");
                    $joblog->add($a_input);

                    $jobstate->changeStatusFinish(
                        $jobstates_id,
                        0,
                        'PluginGlpiinventoryInventoryComputerESX',
                        1,
                        "Unable to find agent to run this job"
                    );
                }
            }
            $job->update($job->fields);
        } else {
            foreach ($agent_actions as $targets) {
                foreach ($targets as $items_id) {
                    foreach ($task_definitions as $task_definition) {
                        foreach ($task_definition as $task_itemtype => $task_items_id) {
                            $a_input = [];
                            $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
                            $a_input['state']                              = 0;
                            $a_input['agents_id']   = $items_id;
                            $a_input['itemtype']                           = $task_itemtype;
                            $a_input['items_id']                           = $task_items_id;
                            $a_input['uniqid']                             = $uniqid;
                            $a_input['date']                               = date("Y-m-d H:i:s");
                            $a_input['execution_id']                       = $task->fields['execution_id'];

                            $jobstates_id = $jobstate->add($a_input);
                          //Add log of taskjob
                            $a_input['plugin_glpiinventory_taskjobstates_id'] = $jobstates_id;
                            $a_input['state'] = PluginGlpiinventoryTaskjoblog::TASK_PREPARED;

                            $joblog->add($a_input);
                            unset($a_input['state']);
                        }
                    }
                }
            }

            $job->fields['status'] = 1;
            $job->update($job->fields);
        }
        return $uniqid;
    }


   /**
    * Get ESX jobs for this agent
    *
    * @param object $taskjobstate
    * @return array
    */
    public function run($taskjobstate)
    {
        $credential     = new PluginGlpiinventoryCredential();
        $credentialip   = new PluginGlpiinventoryCredentialIp();

        $credentialip->getFromDB($taskjobstate->fields['items_id']);
        $credential->getFromDB($credentialip->fields['plugin_glpiinventory_credentials_id']);

        $order['uuid'] = $taskjobstate->fields['uniqid'];
        $order['host'] = $credentialip->fields['ip'];
        $order['user'] = $credential->fields['username'];
        $order['password'] = $credential->fields['password'];
        return $order;
    }
}
