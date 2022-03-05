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
 * Manage the communication of network inventory feature with the agents.
 */
class PluginGlpiinventoryCommunicationNetworkInventory
{
   /**
    * Define protected variables
    *
    * @var null
    */
    private $logFile;

   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = 'plugin_glpiinventory_networkequipment';


   /**
    * __contruct function where fill logFile if extradebug enabled
    */
    public function __construct()
    {
        if (PluginGlpiinventoryConfig::isExtradebugActive()) {
            $this->logFile = GLPI_LOG_DIR . '/glpiinventorycommunication.log';
        }
    }


   /**
    * Import data, so get data from agent to put in GLPI
    *
    * @param string $p_DEVICEID device_id of the agent
    * @param object $a_CONTENT
    * @param Inventory $arrayinventory
    */
    public function import($p_DEVICEID, $a_CONTENT, Inventory $inventory)
    {
        $response = [];

        PluginGlpiinventoryCommunication::addLog(
            'Function PluginGlpiinventoryCommunicationNetworkInventory->import().'
        );

        $agent = new Agent();
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();

        $agent->getFromDBByCrit(['deviceid' => $p_DEVICEID]);

        if (!isset($a_CONTENT->jobid)) {
            if (isset($a_CONTENT->content->processnumber)) {
                $a_CONTENT->jobid = $a_CONTENT->content->processnumber;
            } else {
                $a_CONTENT->jobid = 1;
            }
        }

        $_SESSION['glpi_plugin_glpiinventory_processnumber'] = $a_CONTENT->jobid;
        if ((!isset($a_CONTENT->content->agent->start)) && (!isset($a_CONTENT->content->agent->end)) && (!isset($a_CONTENT->content->agent->exit))) {
            $nb_devices = 1;
            $_SESSION['plugin_glpiinventory_taskjoblog']['taskjobs_id'] =
              $a_CONTENT->jobid;
            $_SESSION['plugin_glpiinventory_taskjoblog']['items_id'] = $agent->fields['id'];
            $_SESSION['plugin_glpiinventory_taskjoblog']['itemtype'] = 'Agent';
            $_SESSION['plugin_glpiinventory_taskjoblog']['state'] = '6';
            $_SESSION['plugin_glpiinventory_taskjoblog']['comment'] = $nb_devices .
              ' ==devicesqueried==';
            $this->addtaskjoblog();
        }

        if (isset($a_CONTENT->content->agent->exit)) {
            $pfTaskjobstate->fail('Task aborted by agent');
            $response['response'] = ['RESPONSE' => 'SEND'];
        } elseif (isset($a_CONTENT->content->agent->end)) {
            $cnt = countElementsInTable(
                'glpi_plugin_glpiinventory_taskjoblogs',
                [
                'plugin_glpiinventory_taskjobstates_id' => $a_CONTENT->jobid,
                'comment'                                 => ["LIKE", '%[==detail==] Update %'],
                ]
            );

            $pfTaskjobstate->changeStatusFinish(
                $a_CONTENT->jobid,
                $agent->fields['id'],
                'Agent',
                '0',
                'Total updated:' . $cnt
            );
            $response = ['response' => ['RESPONSE' => 'SEND']];
        } elseif (isset($a_CONTENT->content->agent->start)) {
            $_SESSION['plugin_glpiinventory_taskjoblog']['taskjobs_id'] = $a_CONTENT->jobid;
            $_SESSION['plugin_glpiinventory_taskjoblog']['items_id'] = $agent->fields['id'];
            $_SESSION['plugin_glpiinventory_taskjoblog']['itemtype'] = 'Agent';
            $_SESSION['plugin_glpiinventory_taskjoblog']['state'] = '6';
            $_SESSION['plugin_glpiinventory_taskjoblog']['comment'] = '==inventorystarted==';
            $this->addtaskjoblog();
            $response = ['response' => ['RESPONSE' => 'SEND']];
        } elseif (isset($a_CONTENT->content->device->error)) {
            $itemtype = "";
            if ($a_CONTENT->content->device->error->type == "NETWORKING" || $a_CONTENT->content->device->error->type == "STORAGE") {
                $itemtype = "NetworkEquipment";
            } elseif ($a_CONTENT->content->device->error->type == "PRINTER") {
                $itemtype = "Printer";
            }
            $_SESSION['plugin_glpiinventory_taskjoblog']['comment'] = '[==detail==] ' .
            $a_CONTENT->content->device->error->message . ' [[' . $itemtype . '::' .
            $a_CONTENT->content->device->error->id . ']]';
            $this->addtaskjoblog();
            $response = ['response' => ['RESPONSE' => 'SEND']];
        } else {
            $inventory->doInventory();
            if ($inventory->inError()) {
                foreach ($inventory->getErrors() as $error) {
                    $response = ['response' => ['ERROR' => $error]];
                }
            } else {
               //nothing to do.
                $response = ['response' => ['RESPONSE' => 'SEND']];
            }
        }

        return $response;
    }



   /**
    * Add log in the taskjob
    */
    public function addtaskjoblog()
    {

        if (!isset($_SESSION['plugin_glpiinventory_taskjoblog']['taskjobs_id'])) {
            return;
        }

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
        return 'networkinventory';
    }
}
