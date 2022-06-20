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

use Glpi\Inventory\Inventory;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Manage the communication of network discovery feature with the agents.
 */
class PluginGlpiinventoryCommunicationNetworkDiscovery
{
   /**
    * Import data, so get data from agent to put in GLPI
    *
    * @param string $p_DEVICEID device_id of agent
    * @param object $a_CONTENT
    * @param Inventory $inventory
    * @return array
    */
    public function import($p_DEVICEID, $a_CONTENT, Inventory $inventory): array
    {
        $response = [];
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $agent = new Agent();

        PluginGlpiinventoryCommunication::addLog(
            'Function PluginGlpiinventoryCommunicationNetworkDiscovery->import().'
        );

        $agent->getFromDBByCrit(['deviceid' => $p_DEVICEID]);

        if (!isset($a_CONTENT->jobid)) {
            $a_CONTENT->jobid = $a_CONTENT->content->processnumber;
        }

        $_SESSION['glpi_plugin_glpiinventory_processnumber'] = $a_CONTENT->jobid;
        if ($pfTaskjobstate->getFromDB($a_CONTENT->jobid)) {
            if ($pfTaskjobstate->fields['state'] != PluginGlpiinventoryTaskjobstate::FINISHED) {
                $pfTaskjobstate->changeStatus($a_CONTENT->jobid, 2);
                if (
                    (!isset($a_CONTENT->content->agent->start))
                    && (!isset($a_CONTENT->content->agent->end))
                    && (!isset($a_CONTENT->content->agent->exit))
                ) {
                    $nb_devices = 1;
                    $_SESSION['plugin_glpiinventory_taskjoblog']['taskjobs_id'] = $a_CONTENT->jobid;
                    $_SESSION['plugin_glpiinventory_taskjoblog']['items_id'] = $agent->fields['id'];
                    $_SESSION['plugin_glpiinventory_taskjoblog']['itemtype'] = 'Agent';
                    $_SESSION['plugin_glpiinventory_taskjoblog']['state'] = PluginGlpiinventoryTaskjoblog::TASK_RUNNING;
                    $_SESSION['plugin_glpiinventory_taskjoblog']['comment'] = $nb_devices . ' ==devicesfound==';
                    $this->addtaskjoblog();
                }
            }
        }

        if ($pfTaskjobstate->getFromDB($a_CONTENT->jobid)) {
            if ($pfTaskjobstate->fields['state'] != PluginGlpiinventoryTaskjobstate::FINISHED) {
                if (isset($a_CONTENT->content->agent->exit)) {
                    $pfTaskjobstate->fail('Task aborted by agent');
                    $response['response'] = ['RESPONSE' => 'SEND'];
                } elseif (isset($a_CONTENT->content->device->error)) {
                    $pfTaskjobstate->fail($a_CONTENT->content->device->error);
                    $response['response'] = ['RESPONSE' => 'SEND'];
                } elseif (isset($a_CONTENT->content->agent->end)) {
                    $updated = countElementsInTable(
                        'glpi_plugin_glpiinventory_taskjoblogs',
                        [
                        'plugin_glpiinventory_taskjobstates_id' => $a_CONTENT->jobid,
                        'comment' => ['LIKE', '%==updatetheitem==%'],
                        ]
                    );
                     $created = countElementsInTable(
                         'glpi_plugin_glpiinventory_taskjoblogs',
                         [
                         'plugin_glpiinventory_taskjobstates_id' => $a_CONTENT->jobid,
                         'comment' => ['LIKE', '%==addtheitem==%'],
                         ]
                     );

                     $message = sprintf(
                         __('Processed: %1$s Created: %2$s Updated: %3$s', 'glpiinventory'),
                         $updated + $created,
                         $created,
                         $updated
                     );
                    $pfTaskjobstate->changeStatusFinish(
                        $a_CONTENT->jobid,
                        $agent->fields['id'],
                        'Agent',
                        '0',
                        $message
                    );
                    $response['response'] = ['RESPONSE' => 'SEND'];
                } elseif (!isset($a_CONTENT->content->agent->start) && !isset($a_CONTENT->content->agent->end) && !isset($a_CONTENT->content->agent->nbip)) {
                    $inventory->setDiscovery(true);
                    $inventory->doInventory();
                    if ($inventory->inError()) {
                        foreach ($inventory->getErrors() as $error) {
                            $response = ['response' => ['ERROR' => $error]];
                        }
                    } else {
                     //nothing to do.
                        $response = ['response' => ['RESPONSE' => 'SEND']];
                    }
                } else {
                    $response['response'] = ['RESPONSE' => 'SEND'];
                }
            } elseif (isset($a_CONTENT->content->agent->start) || isset($a_CONTENT->content->agent->end)) {
                $response['response'] = ['RESPONSE' => 'SEND'];
            } else {
                $response = ['response' => ['ERROR' => 'Task is already finished!']];
            }
        }
        return $response;
    }

   /**
    * Used to add log in the taskjob
    */
    public function addtaskjoblog()
    {

        $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
        $pfTaskjoblog->addTaskjoblog(
            $_SESSION['plugin_glpiinventory_taskjoblog']['taskjobs_id'],
            $_SESSION['plugin_glpiinventory_taskjoblog']['items_id'],
            $_SESSION['plugin_glpiinventory_taskjoblog']['itemtype'],
            $_SESSION['plugin_glpiinventory_taskjoblog']['state'],
            $_SESSION['plugin_glpiinventory_taskjoblog']['comment']
        );
    }


   /**
    * Get method name linked to this class
    *
    * @return string
    */
    public static function getMethod()
    {
        return 'networkdiscovery';
    }
}
