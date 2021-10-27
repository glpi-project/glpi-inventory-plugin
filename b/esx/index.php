<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

//This call is to check that the ESX inventory service is up and running
$fi_status =filter_input(INPUT_GET, "status");
if (!empty($fi_status)) {
   return 'ok';
}
ob_start();
include ("../../../../inc/includes.php");
ob_end_clean();

$response = false;
//Agent communication using REST protocol
$fi_machineid = filter_input(INPUT_GET, "machineid");
if (!empty($fi_machineid)) {

   switch (filter_input(INPUT_GET, "action")) {

      case 'getJobs':
         $pfAgent        = new PluginGlpiinventoryAgent();
         $pfTask         = new PluginGlpiinventoryTask();
         $pfTaskjob      = new PluginGlpiinventoryTaskjob();
         $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();

         $agent = $pfAgent->infoByKey(Toolbox::addslashes_deep(filter_input(INPUT_GET, "machineid")));

         if (isset($agent['id'])) {
            $taskjobstates = $pfTask->getTaskjobstatesForAgent(
               $agent['id'],
               ['InventoryComputerESX']
            );

            ////start of json response
            $order = new stdClass;
            $order->jobs = [];

            $module = new PluginGlpiinventoryInventoryComputerESX();
            foreach ($taskjobstates as $taskjobstate) {
               $order->jobs[] = $module->run($taskjobstate);

               $taskjobstate->changeStatus(
                  $taskjobstate->fields['id'],
                  $taskjobstate::SERVER_HAS_SENT_DATA
               );
            }

            // return an empty dictionnary if there are no jobs.
            if (count($order->jobs) == 0) {
               $response = "{}";
            } else {
               $response = json_encode($order);
            }
         }

         break;

      case 'setLog':
         //Generic method to update logs
         PluginGlpiinventoryCommunicationRest::updateLog($_GET);
         break;
   }

   if ($response !== false) {
      echo $response;
   } else {
      echo json_encode((object)[]);
   }
}
