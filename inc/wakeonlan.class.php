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
 * Manage the wake on lan of computers by the agent.
 */
class PluginGlpiinventoryWakeonlan extends PluginGlpiinventoryCommunication
{
   /**
    * Prepare a taskjob
    * Get all devices and put in taskjobstate each task for each device for
    * each agent
    *
    * @global object $DB
    * @param integer $taskjobs_id
    * @return string
    */
    public function prepareRun($taskjobs_id)
    {
        global $DB;

        $pfTask = new PluginGlpiinventoryTask();
        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
        $agent = new Agent();

        $uniqid = uniqid();

        $pfTaskjob->getFromDB($taskjobs_id);
        $pfTask->getFromDB($pfTaskjob->fields['plugin_glpiinventory_tasks_id']);

        $communication = $pfTask->fields['communication'];
        $a_definitions = importArrayFromDB($pfTaskjob->fields['definition']);

        $a_computers_to_wake = [];
        foreach ($a_definitions as $definition) {
            $itemtype = key($definition);
            $items_id = current($definition);

            switch ($itemtype) {
                case 'Computer':
                    $a_computers_to_wake[] = $items_id;
                    break;

                case 'PluginGlpiinventoryDeployGroup':
                    $group = new PluginGlpiinventoryDeployGroup();
                    $group->getFromDB($items_id);

                    switch ($group->getField('type')) {
                        case 'STATIC':
                            $iterator = $DB->request([
                                'SELECT' => 'items_id',
                                'FROM'   => 'glpi_plugin_glpiinventory_deploygroups_staticdatas',
                                'WHERE'  => [
                                    'groups_id' => $items_id,
                                    'itemtype'  => 'Computer'
                                ]
                            ]);
                            foreach ($iterator as $row) {
                                $a_computers_to_wake[] = $row['items_id'];
                            }
                            break;

                        case 'DYNAMIC':
                            $iterator = $DB->request([
                                'SELECT' => 'fields_array',
                                'FROM'   => 'glpi_plugin_glpiinventory_deploygroups_dynamicdatas',
                                'WHERE'  => [
                                    'groups_id' => $items_id
                                ],
                                'LIMIT' => 1
                            ]);

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
                            Search::manageGetValues('Computer');
                            $glpilist_limit = $_SESSION['glpilist_limit'];
                            $_SESSION['glpilist_limit'] = 999999999;
                            $result = $pfSearch->constructSQL(
                                'Computer',
                                $_GET
                            );
                            $_SESSION['glpilist_limit'] = $glpilist_limit;
                            while ($data = $DB->fetchArray($result)) {
                                 $a_computers_to_wake[] = $data['id'];
                            }
                            if (count($get_tmp) > 0) {
                                $_GET = $get_tmp;
                            }
                            break;
                    }
            }
        }
        $a_actions = importArrayFromDB($pfTaskjob->fields['action']);

        $a_agentList = [];

        if (
            (!strstr($pfTaskjob->fields['action'], '".1"'))
            and (!strstr($pfTaskjob->fields['action'], '".2"'))
        ) {
            foreach ($a_actions as $a_action) {
                if (
                    (!in_array('.1', $a_action))
                    && (!in_array('.2', $a_action))
                ) {
                    $agent_id = current($a_action);
                    if ($agent->getFromDB($agent_id)) {
                        if ($communication == 'pull') {
                            $a_agentList[] = $agent_id;
                        } else {
                            if ($pfTaskjob->isAgentAlive('1', $agent_id)) {
                                $a_agentList[] = $agent_id;
                            }
                        }
                    }
                }
            }
        } elseif (strstr($pfTaskjob->fields['action'], '".1"')) {
           /*
            * Case 3 : dynamic agent
            */
            $a_agentList = $this->getAgentsSubnet(count($a_computers_to_wake), $communication);
        } elseif (in_array('.2', $a_actions)) {
           /*
            * Case 4 : dynamic agent same subnet
            */
            $subnet = '';
            foreach ($a_computers_to_wake as $items_id) {
                $iterator = $DB->request([
                    'FROM'   => 'glpi_networkports',
                    'WHERE'  => [
                        'items_id'  => $items_id,
                        'itemtype'  => 'Computer',
                        'mac'       => ['!=', '']
                    ]
                ]);

                foreach ($iterator as $data) {
                    $subnet = $data['subnet'];
                }
            }
            if ($subnet != '') {
                $a_agentList = $this->getAgentsSubnet(count($a_computers_to_wake), $communication, $subnet);
            }
        }

        if (count($a_agentList) == '0') {
            $a_input = [];
            $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
            $a_input['state'] = 1;
            $a_input['agents_id'] = 0;
            $a_input['itemtype'] = 'Computer';
            $a_input['items_id'] = 0;
            $a_input['uniqid'] = $uniqid;
            $Taskjobstates_id = $pfTaskjobstate->add($a_input);
            //Add log of taskjob
            $a_input['plugin_glpiinventory_taskjobstates_id'] = $Taskjobstates_id;
            $a_input['state'] = 7;
            $a_input['date'] = date("Y-m-d H:i:s");
            $pfTaskjoblog->add($a_input);

            $pfTaskjobstate->changeStatusFinish(
                $Taskjobstates_id,
                0,
                'Computer',
                1,
                "Unable to find agent to run this job"
            );
        } else {
            $nb_computers = ceil(count($a_computers_to_wake) / count($a_agentList));

            $a_input = [];
            $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
            $a_input['state'] = 0;
            $a_input['itemtype'] = 'Computer';
            $a_input['uniqid'] = $uniqid;
            while (count($a_computers_to_wake) != 0) {
                $agent_id = array_pop($a_agentList);
                $a_input['agents_id'] = $agent_id;
                for ($i = 0; $i < $nb_computers; $i++) {
                    //Add jobstate and put status
                    $a_input['items_id'] = array_pop($a_computers_to_wake);
                    $Taskjobstates_id = $pfTaskjobstate->add($a_input);
                    //Add log of taskjob
                    $a_input['plugin_glpiinventory_taskjobstates_id'] = $Taskjobstates_id;
                    $a_input['state'] = 7;
                    $a_input['date'] = date("Y-m-d H:i:s");
                    $pfTaskjoblog->add($a_input);
                    unset($a_input['state']);
                    if ($communication == "push") {
                        $_SESSION['glpi_plugin_glpiinventory']['agents'][$agent_id] = 1;
                    }
                }
            }
        }
        $pfTaskjob->fields['status'] = 1;
        $pfTaskjob->update($pfTaskjob->fields);

        return $uniqid;
    }


   /**
    * When agent contact server, this function send datas to agent
    *
    * @param object $jobstate
    * @return string
    */
    public function run($jobstate)
    {

        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
        $NetworkPort                        = new NetworkPort();

        $sxml_option = $this->message->addChild('OPTION');
        $sxml_option->addChild('NAME', 'WAKEONLAN');

        $changestate = 0;
       //      foreach ($taskjobstates as $jobstate) {
         $data = $jobstate->fields;
         $a_networkPort = $NetworkPort->find(['itemtype' => 'Computer', 'items_id' => $data['items_id']]);
         $computerip = 0;
        foreach ($a_networkPort as $datanetwork) {
           //if ($datanetwork['ip'] != "127.0.0.1") {
            if ($datanetwork['mac'] != '') {
                $computerip++;
                $sxml_param = $sxml_option->addChild('PARAM');
                $sxml_param->addAttribute('MAC', $datanetwork['mac']);
                //$sxml_param->addAttribute('IP', $datanetwork['ip']);

                if ($changestate == '0') {
                    $pfTaskjobstate->changeStatus($data['id'], 1);
                    $pfTaskjoblog->addTaskjoblog(
                        $data['id'],
                        '0',
                        'Computer',
                        '1',
                        ''
                    );
                    $changestate = $pfTaskjobstate->fields['id'];
                } else {
                    $pfTaskjobstate->changeStatusFinish(
                        $data['id'],
                        $data['items_id'],
                        $data['itemtype'],
                        0,
                        "Merged with " . $changestate
                    );
                }

               // Update taskjobstate (state = 3 : finish); Because we haven't return of agent on this action
                $pfTaskjobstate->changeStatusFinish(
                    $data['id'],
                    $data['items_id'],
                    $data['itemtype'],
                    0,
                    'WakeOnLan have not return state'
                );
            }
           //}
        }
        if ($computerip == '0') {
            $pfTaskjobstate->changeStatusFinish(
                $data['id'],
                $data['items_id'],
                $data['itemtype'],
                1,
                "No IP found on the computer"
            );
        }
       //}
        return $this->message;
    }


   /**
    * Get agents on the subnet
    *
    * @global object $DB
    * @param integer $nb_computers
    * @param string $communication
    * @param string $subnet
    * @return array
    */
    public function getAgentsSubnet($nb_computers, $communication, $subnet = '')
    {
        global $DB;

        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
        $OperatingSystem = new OperatingSystem();

        // Number of computers min by agent
        $nb_computerByAgentMin = 20;
        $nb_agentsMax = ceil($nb_computers / $nb_computerByAgentMin);

        // Get ids of operating systems which can make real wakeonlan
        $a_os = $OperatingSystem->find(['name' => ['LIKE', '%Linux%']]);
        $os_where = [];
        $pass_count = 1;

        if (count($a_os)) {
            $pass_count++;
            $os_where = ['operatingsystems_id' => array_keys($a_os)];
        }

        $a_agentList = [];
        for ($pass = 0; $pass < $pass_count; $pass++) {
            if ($pass == "1") {
                // It's not linux
                $os_where = ['NOT' => $os_where];
            }

            $subnet_where = [];
            if ($subnet != '') {
                $subnet_where = ['subnet' => $subnet];
            }

            $a_agents = $pfAgentmodule->getAgentsCanDo('WAKEONLAN');
            $a_agentsid = [];
            foreach ($a_agents as $a_agent) {
                $a_agentsid[] = $a_agent['id'];
            }
            if (count($a_agentsid) == '0') {
                return $a_agentList;
            }

            $where = [
                'glpi_agents.id' => $a_agentsid,
                'ip' => ['!=', '127.0.0.1']
            ];

            $iterator = $DB->request([
                'SELECT' => [
                    'glpi_agents.id as a_id',
                    'ip',
                    'subnet',
                    'token'
                ],
                'FROM' => 'glpi_agents',
                'LEFT JOIN' => [
                    'glpi_networkports' => [
                        'FKEY' => [
                            'glpi_networkports' => 'items_id',
                            'glpi_agents' => 'items_id'
                        ],
                    ],
                    'glpi_computers' => [
                        'FKEY' => [
                            'glpi_computers' => 'id',
                            'glpi_agents' => 'items_id'
                        ],
                    ]
                ],
                'WHERE' => array_merge(
                    [
                        'glpi_agents.itemtype' => 'Computer',
                        'glpi_networkports.itemtype' => 'Computer',
                    ],
                    $os_where,
                    $subnet_where,
                    $where
                )
            ]);

            if (count($iterator)) {
                foreach ($iterator as $data) {
                    if ($communication == 'push') {
                        if ($pfTaskjob->isAgentAlive(1, $data['a_id'])) {
                            if (!in_array($a_agentList, $data['a_id'])) {
                                $a_agentList[] = $data['a_id'];
                                if (count($a_agentList) >= $nb_agentsMax) {
                                    return $a_agentList;
                                }
                            }
                        }
                    } elseif ($communication == 'pull') {
                        if (!in_array($a_agentList, $data['a_id'])) {
                            $a_agentList[] = $data['a_id'];
                            if (count($a_agentList) > $nb_agentsMax) {
                                return $a_agentList;
                            }
                        }
                    }
                }
            }
        }
        return $a_agentList;
    }
}
