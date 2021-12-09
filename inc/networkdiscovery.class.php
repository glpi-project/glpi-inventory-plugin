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
    die("Sorry. You can't access this file directly");
}

/**
 * Manage network discovery prepare the task and give the configuration to the
 * agent.
 */
class PluginGlpiinventoryNetworkdiscovery extends PluginGlpiinventoryCommunication
{


   /**
    * Prepare network discovery.
    * Get all devices and put in taskjobstat each task for each device for each
    * agent
    *
    * @param integer $taskjobs_id
    * @return string
    */
    public function prepareRun($taskjobs_id)
    {

        $pfTask = new PluginGlpiinventoryTask();
        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
        $pfIPRange = new PluginGlpiinventoryIPRange();
        $agent = new Agent();

        $uniqid = uniqid();

        $pfTaskjob->getFromDB($taskjobs_id);
        $pfTask->getFromDB($pfTaskjob->fields['plugin_glpiinventory_tasks_id']);

        $communication = $pfTask->fields['communication'];

       //list all iprange
        $a_iprange = importArrayFromDB($pfTaskjob->fields['definition']);
        $count_ip = 0;
        $a_iprangelist = [];
        $a_subnet_nbip = [];
        foreach ($a_iprange as $iprange) {
            $iprange_id = current($iprange);
            $a_iprangelist[] = $iprange_id;
            $pfIPRange->getFromDB($iprange_id);
            $s = $pfIPRange->getIp2long($pfIPRange->fields['ip_start']);
            $e = $pfIPRange->getIp2long($pfIPRange->fields['ip_end']);
            $a_subnet_nbip[$iprange_id] = $e - $s;
            $count_ip += $e - $s;
        }

       //list all agents
        $a_agent = importArrayFromDB($pfTaskjob->fields['action']);
        $dynagent = 0;
        $a_agentlist = [];
        foreach ($a_agent as $agent) {
            $agent_id = current($agent);
            if ($agent_id == '.1') {
                $dynagent = 1;
            } elseif ($agent_id == '.2') {
                $dynagent = 2;
            } else {
               // Detect if agent exists
                if ($agent->getFromDB($agent_id)) {
                    if ($pfTask->fields['communication'] == 'pull') {
                        $a_agentlist[$agent_id] = 1;
                    } else {
                        if ($pfTaskjob->isAgentAlive('1', $agent_id)) {
                            $a_agentlist[$agent_id] = 1;
                        }
                    }
                }
            }
        }
        if ($dynagent == '1') {
            $a_agents = $pfAgentmodule->getAgentsCanDo('NETWORKDISCOVERY');
            foreach ($a_agents as $data) {
                if (($count_ip / 10) >= count($a_agentlist)) {
                    $agent->getFromDB($data['id']);
                    $a_ip = PluginGlpiinventoryToolbox::getIPforDevice('Computer', $agent->fields['itemss_id']);
                    foreach ($a_ip as $ip) {
                        if ($pfTask->fields['communication'] == 'push') {
                            if ($pfTaskjob->isAgentAlive('1', $data['id'])) {
                                $a_agentlist[$data['id']] = 1;
                            }
                        } elseif ($pfTask->fields['communication'] == 'pull') {
                            $a_agentlist[$data['id']] = 1;
                        }
                    }
                }
            }
        }

        if ($dynagent == '2') {
           // Dynamic with subnet
            $pfSnmpinventory = new PluginGlpiinventoryNetworkinventory();
            $taskvalid = 0;
            foreach ($a_subnet_nbip as $iprange_id => $nbips) {
               //$maxagentpossible = $nbips/10;
                $pfIPRange->getFromDB($iprange_id);
                $a_agentListComplete = [];
                $a_agentList = $pfSnmpinventory->getAgentsSubnet(
                    $nbips,
                    "push",
                    "",
                    $pfIPRange->fields['ip_start'],
                    $pfIPRange->fields['ip_end']
                );
                if (isset($a_agentList)) {
                     $a_agentListComplete = array_merge($a_agentListComplete, $a_agentList);
                }

                if (!isset($a_agentListComplete) or empty($a_agentListComplete)) {
                    $a_input = [];
                    $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
                    $a_input['agents_id'] = 0;
                    $a_input['state']        = 1;
                    $a_input['itemtype']     = 'PluginGlpiinventoryIPRange';
                    $a_input['items_id']     = $iprange_id;
                    $a_input['uniqid']       = $uniqid;
                    $a_input['execution_id'] = $pfTask->fields['execution_id'];

                    $Taskjobstates_id = $pfTaskjobstate->add($a_input);
                    //Add log of taskjob
                    $a_input['plugin_glpiinventory_taskjobstates_id'] = $Taskjobstates_id;
                    $a_input['state'] = 7;
                    $a_input['date'] = date("Y-m-d H:i:s");
                    $pfTaskjoblog->add($a_input);

                    $pfTaskjobstate->changeStatusFinish(
                        $Taskjobstates_id,
                        0,
                        'PluginGlpiinventoryIPRange',
                        1,
                        "Unable to find agent to run this job"
                    );
                     $input_taskjob = [];
                     $input_taskjob['id'] = $pfTaskjob->fields['id'];
                     //$input_taskjob['status'] = 1;
                     $pfTaskjob->update($input_taskjob);
                } else {
                    $s = $pfIPRange->getIp2long($pfIPRange->fields['ip_start']);
                    $e = $pfIPRange->getIp2long($pfIPRange->fields['ip_end']);
                    $nbIpAgent = ceil(($e - $s) / count($a_agentListComplete));
                    $iptimes = 0;

                    foreach ($a_agentListComplete as $agent_id) {
                        $_SESSION['glpi_plugin_glpiinventory']['agents'][$agent_id] = 1;
                        //Add jobstate and put status (waiting on server = 0)
                        $a_input = [];
                        $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
                        $a_input['state'] = 0;
                        $a_input['agents_id'] = $agent_id;
                        $a_input['itemtype'] = 'PluginGlpiinventoryIPRange';
                        $a_input['uniqid'] = $uniqid;
                        $a_input['execution_id'] = $pfTask->fields['execution_id'];

                        $a_input['items_id'] = $iprange_id;
                        if (($iptimes + $nbIpAgent) > ($e - $s)) {
                            $a_input['specificity'] = $iptimes . "-" . ($e - $s);
                        } else {
                            $a_input['specificity'] = $iptimes . "-" . ($iptimes + $nbIpAgent);
                        }
                        $taskvalid++;
                        $Taskjobstates_id = $pfTaskjobstate->add($a_input);
                       //Add log of taskjob
                        $a_input['plugin_glpiinventory_taskjobstates_id'] = $Taskjobstates_id;
                        $a_input['state'] = 7;
                        $a_input['date'] = date("Y-m-d H:i:s");
                        $pfTaskjoblog->add($a_input);
                        unset($a_input['state']);
                        $iptimes += $nbIpAgent + 1;
                        if (($iptimes) >= ($e - $s + 1)) {
                             break;
                        }
                        $input_taskjob = [];
                        $input_taskjob['id'] = $pfTaskjob->fields['id'];
                        $input_taskjob['status'] = 1;
                        $pfTaskjob->update($input_taskjob);
                    }
                }
            }
            if ($taskvalid == "0") {
                $pfTaskjob->reinitializeTaskjobs($pfTaskjob->fields['plugin_glpiinventory_tasks_id']);
            }
           // *** Add jobstate
        } elseif (count($a_agentlist) == 0) {
            $a_input = [];
            $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
            $a_input['state'] = 1;
            $a_input['agents_id'] = 0;
            $a_input['itemtype'] = 'PluginGlpiinventoryIPRange';
            $a_input['items_id'] = 0;
            $a_input['uniqid'] = $uniqid;
            $a_input['execution_id'] = $pfTask->fields['execution_id'];

            $Taskjobstates_id = $pfTaskjobstate->add($a_input);
            //Add log of taskjob
            $a_input['plugin_glpiinventory_taskjobstates_id'] = $Taskjobstates_id;
            $a_input['state'] = 7;
            $a_input['date'] = date("Y-m-d H:i:s");
            $pfTaskjoblog->add($a_input);

            $pfTaskjobstate->changeStatusFinish(
                $Taskjobstates_id,
                0,
                'PluginGlpiinventoryIPRange',
                1,
                "Unable to find agent to run this job"
            );
            $input_taskjob = [];
            $input_taskjob['id'] = $pfTaskjob->fields['id'];
           //$input_taskjob['status'] = 1;
            $pfTaskjob->update($input_taskjob);
        } else {
            $iptimes = 0;
            $nbIpadded = 0;
            $break = 0;
            $numberIpByAgent = ceil($count_ip / (count($a_agentlist)));
            $a_iprangelistTmp = $a_iprangelist;
            $ip_id = array_shift($a_iprangelistTmp);
            foreach ($a_agentlist as $agent_id => $ip) {
               //Add jobstate and put status (waiting on server = 0)
                $a_input = [];
                $a_input['plugin_glpiinventory_taskjobs_id'] = $taskjobs_id;
                $a_input['state'] = 0;
                $a_input['agents_id'] = $agent_id;
                $a_input['itemtype'] = 'PluginGlpiinventoryIPRange';
                $a_input['uniqid'] = $uniqid;
                $a_input['execution_id'] = $pfTask->fields['execution_id'];

               //            $nbIpAgent = $numberIpByAgent;
                $nbIpadded = 0;
                foreach ($a_iprangelist as $iprange_id) {
                    if ($ip_id == $iprange_id) {
                        $pfIPRange->getFromDB($iprange_id);
                        $s = $pfIPRange->getIp2long($pfIPRange->fields['ip_start']);
                        $e = $pfIPRange->getIp2long($pfIPRange->fields['ip_end']);
                        if ($communication == "push") {
                             $_SESSION['glpi_plugin_glpiinventory']['agents'][$agent_id] = 1;
                        }

                        $a_input['items_id'] = $iprange_id;
                        $nbIpAgent = $numberIpByAgent - $nbIpadded;
                        if (($iptimes + $nbIpAgent) > ($e - $s)) {
                            $a_input['specificity'] = $iptimes . "-" . ($e - $s);
                            $nbIpadded = ($e - $s) - $iptimes;
                            $ip_id = array_shift($a_iprangelistTmp);
                            $iptimes = 0;
                        } else {
                            $a_input['specificity'] = $iptimes . "-" . ($iptimes + $nbIpAgent);
                            $iptimes += $nbIpAgent + 1;
                            $nbIpadded = 0;
                            $break = 1;
                        }
                        $Taskjobstates_id = $pfTaskjobstate->add($a_input);
                      //Add log of taskjob
                        $a_input['plugin_glpiinventory_taskjobstates_id'] = $Taskjobstates_id;
                        $a_input['state'] = 7;
                        $a_input['date'] = date("Y-m-d H:i:s");
                        $pfTaskjoblog->add($a_input);
                        unset($a_input['state']);
                    }
                }
                $input_taskjob = [];
                $input_taskjob['id'] = $pfTaskjob->fields['id'];
                $input_taskjob['status'] = 1;
                $pfTaskjob->update($input_taskjob);
            }
        }
        return $uniqid;
    }


   /**
    * When agent contact server, this function send job data to agent
    *
    * @param object $jobstate PluginGlpiinventoryTaskjobstate instance
    * @return array
    */
    public function run($jobstate)
    {
        $agent = new Agent();
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
        $pfIPRange = new PluginGlpiinventoryIPRange();
        $pfToolbox = new PluginGlpiinventoryToolbox();
        $pfConfig = new PluginGlpiinventoryConfig();

        $agent->getFromDB($jobstate->fields['agents_id']);

        $param_attrs = [];

       // Use general config when threads number is set to 0 on the agent
        $param_attrs['THREADS_DISCOVERY'] = $agent->fields["threads_networkdiscovery"] == 0 ?
         $pfConfig->getValue('threads_networkdiscovery') :
         $agent->fields["threads_networkdiscovery"];

       // Use general config when timeout is set to 0 on the agent
        $param_attrs['TIMEOUT'] = $agent->fields["timeout_networkdiscovery"] == 0 ?
         $pfConfig->getValue('timeout_networkdiscovery') :
         $agent->fields["timeout_networkdiscovery"];

        $param_attrs['PID'] = $jobstate->fields['id'];

        $iprange_attrs = [];
        $changestate = 0;
        $taskjobstatedatas = $jobstate->fields;
        $pfTaskjob->getFromDB($taskjobstatedatas['plugin_glpiinventory_taskjobs_id']);
        $pfTaskjobstate->getFromDB($taskjobstatedatas['id']);
        $pfIPRange->getFromDB($taskjobstatedatas['items_id']);

        $iprange_attrs['ID'] = $pfIPRange->fields['id'];

        if (!is_null($pfTaskjobstate->fields['specificity'])) {
            $a_split = explode("-", $pfTaskjobstate->fields['specificity']);

            $first_ip = $pfIPRange->getIp2long($pfIPRange->fields["ip_start"]);

            $last_ip = long2ip($first_ip + $a_split[1]);
            $first_ip = long2ip($first_ip + $a_split[0]);
            if ($first_ip != '0.0.0.0' && $last_ip != '0.0.0.0') {
                $iprange_attrs['IPSTART'] = $first_ip;
                $iprange_attrs['IPEND'] = $last_ip;
            }
        } else {
            $iprange_attrs['IPSTART'] = $pfIPRange->fields["ip_start"];
            $iprange_attrs['IPEND'] = $pfIPRange->fields["ip_end"];
        }

        $iprange_attrs['ENTITY'] = $pfIPRange->fields["entities_id"];

        if ($changestate == '0') {
            $pfTaskjobstate->changeStatus($pfTaskjobstate->fields['id'], 1);
            $pfTaskjoblog->addTaskjoblog(
                $pfTaskjobstate->fields['id'],
                '0',
                'Agent',
                '1',
                $agent->fields["threads_networkdiscovery"] . ' threads ' .
                                 $agent->fields["timeout_networkdiscovery"] . ' timeout'
            );
            $changestate = $pfTaskjobstate->fields['id'];
        } else {
            $pfTaskjobstate->changeStatusFinish(
                $pfTaskjobstate->fields['id'],
                $taskjobstatedatas['items_id'],
                $taskjobstatedatas['itemtype'],
                0,
                "Merged with " . $changestate
            );
        }
        $iprange_credentials = new PluginGlpiinventoryIPRange_SNMPCredential();
        $a_auths = $iprange_credentials->find(
            ['plugin_glpiinventory_ipranges_id' => $pfIPRange->fields['id']],
            ['rank']
        );
        $auth_nodes = [];
        foreach ($a_auths as $dataAuth) {
            $auth_node = $pfToolbox->addAuth($dataAuth['snmpcredentials_id']);
            if (count($auth_node)) {
                $auth_nodes[] = $auth_node;
            }
        }

        return [
         'OPTION' => [
            'NAME' => 'NETDISCOVERY',
            'PARAM' => [
               'content' => '',
               'attributes' => $param_attrs
            ],
            'RANGEIP' => [
               'content'   => '',
               'attributes' => $iprange_attrs
            ]
         ] + $auth_nodes
        ];
    }
}
