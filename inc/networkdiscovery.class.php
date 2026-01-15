<?php

use GlpiPlugin\Glpiinventory\Enums\NetTaskTypes;
use GlpiPlugin\Glpiinventory\Job\Types\NetTask;

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

/**
 * Manage network discovery prepare the task and give the configuration to the
 * agent.
 */
class PluginGlpiinventoryNetworkdiscovery extends PluginGlpiinventoryCommunication
{
    /**
     * When agent contact server, this function send job data to agent
     *
     * @param PluginGlpiinventoryTaskjobstate $jobstate PluginGlpiinventoryTaskjobstate instance
     * @return array<string, mixed>
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
        $param_attrs['THREADS_DISCOVERY'] = $agent->fields["threads_networkdiscovery"] == 0
        ? $pfConfig->getValue('threads_networkdiscovery')
        : $agent->fields["threads_networkdiscovery"];

        // Use general config when timeout is set to 0 on the agent
        $param_attrs['TIMEOUT'] = $agent->fields["timeout_networkdiscovery"] == 0
        ? $pfConfig->getValue('timeout_networkdiscovery')
        : $agent->fields["timeout_networkdiscovery"];

        $param_attrs['PID'] = $jobstate->fields['id'];

        $iprange_attrs = [];
        $taskjobstatedatas = $jobstate->fields;
        $pfTaskjob->getFromDB($taskjobstatedatas['plugin_glpiinventory_taskjobs_id']);
        $pfTaskjobstate->getFromDB($taskjobstatedatas['id']);
        $pfIPRange->getFromDB($taskjobstatedatas['items_id']);

        $iprange_attrs['ID'] = $pfIPRange->fields['id'];

        if (!is_null($pfTaskjobstate->fields['specificity'])) {
            $a_split = explode("-", $pfTaskjobstate->fields['specificity']);

            $first_ip = $pfIPRange->getIp2long($pfIPRange->fields["ip_start"]);

            $last_ip = long2ip($first_ip + (int) $a_split[1]);
            $first_ip = long2ip($first_ip + (int) $a_split[0]);
            if ($first_ip != '0.0.0.0' && $last_ip != '0.0.0.0') {
                $iprange_attrs['IPSTART'] = $first_ip;
                $iprange_attrs['IPEND'] = $last_ip;
            }
        } else {
            $iprange_attrs['IPSTART'] = $pfIPRange->fields["ip_start"];
            $iprange_attrs['IPEND'] = $pfIPRange->fields["ip_end"];
        }

        $iprange_attrs['ENTITY'] = $pfIPRange->fields["entities_id"];

        $pfTaskjobstate->changeStatus($pfTaskjobstate->fields['id'], 1);
        /*$pfTaskjoblog->addTaskjoblog(
            $pfTaskjobstate->fields['id'],
            0,
            Agent::class,
            1,
            $agent->fields["threads_networkdiscovery"] . ' threads '
                             . $agent->fields["timeout_networkdiscovery"] . ' timeout'
        );*/
        $pfTaskjoblog->addJobLog(
            taskjobs_id: $pfTaskjobstate->fields['id'],
            items_id: 0,
            itemtype: Agent::class,
            state: PluginGlpiinventoryTaskjoblog::TASK_STARTED,
            comment: new NetTask(
                task_type: NetTaskTypes::TASk_DISCOVERY,
                threads: $agent->fields["threads_networkdiscovery"],
                timeout: $agent->fields["timeout_networkdiscovery"]
            )
        );

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
                    'attributes' => $param_attrs,
                ],
                'RANGEIP' => [
                    'content'   => '',
                    'attributes' => $iprange_attrs,
                ],
            ] + $auth_nodes,
        ];
    }
}
