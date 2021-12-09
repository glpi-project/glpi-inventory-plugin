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

ob_start();
include ("../../../../inc/includes.php");
ob_end_clean();

//Store deploy task version
//If task is lower than 2.2, there's no version sent by the agent
//we set it to 0
$deploy_task_version = 0;
if (isset($_GET['version'])) {
   $deploy_task_version = $_GET['version'];
}

$response = false;
//Agent communication using REST protocol
switch (filter_input(INPUT_GET, "action")) {

   case 'getJobs':
      $machineid = filter_input(INPUT_GET, "machineid");
      if (isset($machineid)) {
         $agent          = new Agent();
         $pfAgentModule  = new PluginGlpiinventoryAgentmodule();
         $pfTask         = new PluginGlpiinventoryTask();
         $pfTaskjob      = new PluginGlpiinventoryTaskjob();
         $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();

         if ($agent->getFromDBByCrit(['deviceid' => Toolbox::addslashes_deep($machineid)])) {

            $taskjobstates = $pfTask->getTaskjobstatesForAgent(
               $agent->fields['id'],
               ['deployinstall']
            );
            if (!$pfAgentModule->isAgentCanDo("DEPLOY", $agent->fields['id'])) {
               foreach ($taskjobstates as $taskjobstate) {
                  $taskjobstate->cancel(
                     __("Deploy module has been disabled for this agent", 'glpiinventory')
                  );
               }
               $response = "{}";
            } else {
               $package      = new PluginGlpiinventoryDeployPackage();
               $deploycommon = new PluginGlpiinventoryDeployCommon();

               //sort taskjobs by key id
               /**
                * TODO: sort taskjobs by 'index' field in the taskjob query since it can be
                * manipulated by drag and drop (cf. Task::getTaskjobsForAgent() ).
                */
               ////start of json response
               $order                  = new stdClass();
               $order->jobs            = [];
               $order->associatedFiles = new stdClass();

               ////aggregate json orders in a single json response
               foreach ($taskjobstates as $taskjobstate) {

                  // TODO: The run() method should be renamed as getData() and moved to the Package
                  // class since we want package configuration (Order class may be useless ... needs
                  // some thinking)

                  // Get taskjob json order
                  $jobstate_order = $deploycommon->run($taskjobstate);
                  if (!$jobstate_order) {
                     continue;
                  }

                  //Build the json to be sent
                  //check response depending on the
                  $jobstate_order = $package->buildJson($deploy_task_version,
                                                        $jobstate_order);

                  // Append order to the final json
                  $order->jobs[] = $jobstate_order['job'];

                  // Update associated files list
                  foreach ($jobstate_order['associatedFiles'] as $hash=>$associatedFiles) {
                     if (!property_exists($order->associatedFiles, $hash)) {
                        $order->associatedFiles->$hash = $associatedFiles;
                     }
                  }
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
         }
      }
      break;

   case 'getFilePart':
      $DB->close();
      PluginGlpiinventoryDeployFilepart::httpSendFile(filter_input(INPUT_GET, "file"));
      exit;
      break;

   case 'setStatus':

      $partjob_mapping = [
         "checking"    => __('Checks', 'glpiinventory'),
         "downloading" => __('Files download', 'glpiinventory'),
         "prepare"     => __('Files preparation', 'glpiinventory'),
         "processing"  => __('Actions', 'glpiinventory'),
      ];

      $error = false;

      $params = [
         'machineid' => filter_input(INPUT_GET, "machineid"),
         'uuid'      => filter_input(INPUT_GET, "uuid")
      ];

      if (filter_input(INPUT_GET, "status") == 'ko') {
         $params['code'] = 'ko';
         $fi_currentStep = filter_input(INPUT_GET, "currentStep");
         if (!empty($fi_currentStep)) {
            $params['msg'] = $partjob_mapping[filter_input(INPUT_GET, "currentStep")]
               . ":" . filter_input(INPUT_GET, "msg");
         } else {
            $params['msg'] = filter_input(INPUT_GET, "msg");
         }
         $error = true;
      }


      if ($error != true) {
         if (filter_input(INPUT_GET, "msg") === 'job successfully completed'
            || filter_input(INPUT_GET, "msg") === 'job skipped') {
            //Job has ended  or has been skipped and status should be ok
            $params['code'] = 'ok';
            $params['msg']  = filter_input(INPUT_GET, "msg");
         } else {
            $params['code'] = 'running';
            $fi_currentStep = filter_input(INPUT_GET, "currentStep");
            if (!empty($fi_currentStep)) {
               $params['msg'] = $partjob_mapping[filter_input(INPUT_GET, "currentStep")]
                  . ":" . filter_input(INPUT_GET, "msg");
            } else {
               $params['msg'] = filter_input(INPUT_GET, "msg");
            }
         }
      }
      if (is_array($params['msg'])) {
         $htmlspecialchars_flags = ENT_SUBSTITUTE | ENT_DISALLOWED;

         $tmp_msg = implode("\n", $params['msg']);
         $flags   = null;
         $tmp_msg =
            stripcslashes(
               htmlspecialchars(
                  $tmp_msg,
                  $htmlspecialchars_flags,
                  'UTF-8',
                  false
               )
            );
         $params['msg'] = nl2br($tmp_msg);
      }

      //Generic method to update logs
      PluginGlpiinventoryCommunicationRest::updateLog($params);
      break;

   case 'setUserEvent':
      $params = [
         'machineid' => filter_input(INPUT_GET, "machineid"),
         'uuid'      => filter_input(INPUT_GET, "uuid")
      ];

      //Action : postpone, cancel, continue
      $behavior = filter_input(INPUT_GET, "behavior");

      //before, after_download, after_download_failure,
      //after_failure, after
      $type    = filter_input(INPUT_GET, "type");

      //on_nouser, on_ok, on_cancel, on_abort, on_retry, on_ignore,
      //on_yes, on_no, on_tryagain, on_continue, on_timeout, on_async,
      //on_multiusers
      $event   = filter_input(INPUT_GET, "event");

      //The user who did the interaction
      $user    = filter_input(INPUT_GET, "user");

      //Process response if an agent provides a behavior, a type and an event
      //the user parameter is not mandatory
      if ($behavior !== false && $type !== false
         && $event !== false && $user !== false) {
         $interaction    = new PluginGlpiinventoryDeployUserinteraction();
         $cancel         = false;
         $postpone       = false;
         $params['msg']  = $interaction->getLogMessage($behavior, $type, $event,
                                                       $user);
         switch ($behavior) {
            case PluginGlpiinventoryDeployUserinteraction::RESPONSE_STOP:
               $params['code'] = 'ko';
               $cancel         = true;
               break;

            case PluginGlpiinventoryDeployUserinteraction::RESPONSE_CONTINUE:
               $params['code'] = 'running';
               break;

            case PluginGlpiinventoryDeployUserinteraction::RESPONSE_POSTPONE:
               $params['code'] = 'running';
               $postpone       = true;
               break;

            case PluginGlpiinventoryDeployUserinteraction::RESPONSE_BAD_EVENT:
               $params['code'] = 'ko';
               break;
         }

         //Generic method to update logs
         PluginGlpiinventoryCommunicationRest::updateLog($params);

         //If needed : cancel or postpone the job
         if ($cancel || $postpone) {
            $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
            $pfTaskjobstate->getFromDBByUniqID($params['uuid']);
            if ($cancel) {
               $pfTaskjobstate->cancel(__('User canceled the job', 'glpiinventory'));
            } else {
               $pfTaskjobstate->postpone($type, __('User postponed the job', 'glpiinventory'));
            }
         }
      }
}

if ($response !== false) {
   echo $response;
} else {
   echo json_encode((object)[]);
}
