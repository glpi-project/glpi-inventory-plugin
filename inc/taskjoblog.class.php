<?php
/**
 *  * ---------------------------------------------------------------------
 *  * GLPI Inventory Plugin
 *  * Copyright (C) 2021 Teclib' and contributors.
 *  *
 *  * http://glpi-project.org
 *  *
 *  * based on FusionInventory for GLPI
 *  * Copyright (C) 2010-2021 by the FusionInventory Development Team.
 *  *
 *  * ---------------------------------------------------------------------
 *  *
 *  * LICENSE
 *  *
 *  * This file is part of GLPI Inventory Plugin.
 *  *
 *  * GLPI Inventory Plugin is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as published by
 *  * the Free Software Foundation, either version 3 of the License, or
 *  * (at your option) any later version.
 *  *
 *  * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  * GNU Affero General Public License for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 *  * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Manage the logs of task job.
 */
class PluginGlpiinventoryTaskjoblog extends CommonDBTM {

   /**
    * Define state task started
    *
    * @var integer
    */
   const TASK_STARTED = 1;

   /**
    * Define state task OK / successful
    *
    * @var integer
    */
   const TASK_OK = 2;

   /**
    * Define state task in error
    *
    * @var integer
    */
   const TASK_ERROR = 4;

   /**
    * Define state task information
    *
    * @var integer
    */
   const TASK_INFO = 5;

   /**
    * Define state task running
    *
    * @var integer
    */
   const TASK_RUNNING = 6;

   /**
    * Define state task prepared, so wait agent contact the server to get
    * this task
    *
    * @var integer
    */
   const TASK_PREPARED = 7;


   /**
    * return array with state mapping name
    *
    * @return array with all elements
    */
   static function dropdownStateValues() {

      $elements = [
         self::TASK_PREPARED           => __('Prepared', 'glpiinventory'),
         self::TASK_STARTED            => __('Started', 'glpiinventory'),
         self::TASK_RUNNING            => __('Running'),
         self::TASK_OK                 => __('Ok', 'glpiinventory'),
         self::TASK_ERROR              => __('Error'),
         self::TASK_INFO               => __('Info', 'glpiinventory'),
      ];

      return $elements;
   }


   /**
    * Get state name
    *
    * @param integer $state
    * @return string
    */
   static function getStateName($state = -1) {
      $state_names = self::dropdownStateValues();
      if (isset($state_names[$state])) {
         return $state_names[$state];
      } else {
         return NOT_AVAILABLE;
      }
   }

   /**
    * Get itemtype of task job state
    *
    * @global object $DB
    * @param integer $taskjoblogs_id
    * @return string
    */
   static function getStateItemtype($taskjoblogs_id) {
      global $DB;

      $params = ['FROM'   => 'glpi_plugin_glpiinventory_taskjobstates',
                 'LEFT JOIN' => ['glpi_plugin_glpiinventory_taskjoblogs',
                     ['FKEY' => ['glpi_plugin_glpiinventory_taskjoblogs'   => 'plugin_glpiinventory_taskjobstates_id',
                                 'glpi_plugin_glpiinventory_taskjobstates' => 'id']]
                     ],
                 'FIELDS' => ['itemtype'],
                 'WHERE'  => ['glpi_plugin_glpiinventory_taskjobstates.id' => $taskjoblogs_id],
                 'LIMIT'  => 1
                ];
      $iterator = $DB->request($params);
      if ($iterator->numrows()) {
         $data = $iterator->current();
         return $data['itemtype'];
      } else {
         return '';
      }
   }


   /**
    * Get search function for the class
    *
    * @return array
    */
   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id' => 'common',
         'name' => __('Logs')
      ];

      $tab[] = [
         'id'            => '1',
         'table'         => $this->getTable(),
         'field'         => 'id',
         'name'          => __('ID'),
         'massiveaction' => false, // implicit field is i,
      ];

      $tab[] = [
         'id'            => '2',
         'table'         => 'glpi_plugin_glpiinventory_tasks',
         'field'         => 'name',
         'name'          => _n('Task', 'Tasks', 2),
         'datatype'      => 'itemlink',
         'itemlink_type' => "PluginGlpiinventoryTask",
      ];

      $tab[] = [
         'id'            => '3',
         'table'         => 'glpi_plugin_glpiinventory_taskjobs',
         'field'         => 'name',
         'name'          => __('Job', 'glpiinventory'),
         'datatype'      => 'itemlink',
         'itemlink_type' => "PluginGlpiinventoryTaskjob",
      ];

      $tab[] = [
         'id'         => '4',
         'table'      => $this->getTable(),
         'field'      => 'state',
         'name'       => __('Status'),
         'searchtype' => 'equals',
      ];

      $tab[] = [
         'id'            => '5',
         'table'         => $this->getTable(),
         'field'         => 'date',
         'name'          => _n('Date', 'Dates', 1),
         'datatype'      => 'datetime',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'       => '6',
         'table'    => 'glpi_plugin_glpiinventory_taskjobstates',
         'field'    => 'uniqid',
         'name'     => __('Unique id', 'glpiinventory'),
         'datatype' => 'string',
      ];

      $tab[] = [
         'id'       => '7',
         'table'    => $this->getTable(),
         'field'    => 'comment',
         'name'     => __('Comments'),
         'datatype' => 'string',
      ];

      $tab[] = [
         'id'           => '8',
         'table'        => "glpi_plugin_glpiinventory_agents",
         'field'        => 'name',
         'name'         => __('Agent', 'glpiinventory'),
         'datatype'     => 'itemlink',
         'forcegroupby' => true,
         'joinparams'   => [
            'beforejoin' => [
               'table'      => 'glpi_plugin_glpiinventory_taskjobstates',
               'joinparams' => ['jointype' => 'child'],
            ],
         ],
      ];
      return $tab;
   }


   /**
    * Display javascript functions for history
    *
    * @global array $CFG_GLPI
    */
   function javascriptHistory() {
      $fi_path = Plugin::getWebDir('glpiinventory');

            echo "<script  type='text/javascript'>
function close_array(id) {
   document.getElementById('plusmoins'+id).innerHTML = '<img src=\'".$fi_path."/pics/collapse.png\''+
      'onClick=\'document.getElementById(\"viewfollowup'+id+'\").hide();appear_array('+id+');\' />".
         "&nbsp;<img src=\'".$fi_path."/pics/refresh.png\' />';
   document.getElementById('plusmoins'+id).style.backgroundColor = '#e4e4e2';
}
function appear_array(id) {
   document.getElementById('plusmoins'+id).innerHTML = '<img src=\'".$fi_path."/pics/expand.png\''+
      'onClick=\'document.getElementById(\"viewfollowup'+id+'\").show();close_array('+id+');\' />';
   document.getElementById('plusmoins'+id).style.backgroundColor = '#f2f2f2';

}

      </script>";

      echo "<script type='text/javascript' src='".$fi_path."/prototype.js'></script>";
      echo "<script type='text/javascript' src='".$fi_path."/effects.js'></script>";
   }


   /**
    * Display each history line
    *
    * @global array $CFG_GLPI
    * @param integer $taskjobstates_id
    * @param integer $displayprocess
    * @param integer $displaytaskjob
    * @param integer $nb_td
    */
   function showHistoryLines($taskjobstates_id, $displayprocess = 1, $displaytaskjob = 0,
                             $nb_td = 5) {
      global $CFG_GLPI;

      $fi_path = Plugin::getWebDir('glpiinventory');

      $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
      $pfAgent        = new PluginGlpiinventoryAgent();

      $pfTaskjobstate->getFromDB($taskjobstates_id);

      $displayforceend = 0;
      $a_history = $this->find(
            ['plugin_glpiinventory_taskjobstates_id' => $pfTaskjobstate->fields['id']],
            ['id DESC'], 1);

      echo "<tr class='tab_bg_1'>";
      echo "<td width='40' id='plusmoins".$pfTaskjobstate->fields["id"]."'><img src='".
               $fi_path."/pics/expand.png' ".
               "onClick='document.getElementById(\"viewfollowup".$pfTaskjobstate->fields["id"].
               "\").show();close_array(".$pfTaskjobstate->fields["id"].");' /></td>";

      echo "<td>";
      echo $pfTaskjobstate->fields['uniqid'];
      echo "</td>";
      if ($displayprocess == '1') {
         echo "<td>";
         echo $pfTaskjobstate->fields['id'];
         echo "</td>";
      }
      if ($displaytaskjob == '1') {
         $pfTaskjob = new PluginGlpiinventoryTaskjob();
         $pfTask    = new PluginGlpiinventoryTask();

         $pfTaskjob->getFromDB($pfTaskjobstate->fields['plugin_glpiinventory_taskjobs_id']);
         $pfTask->getFromDB($pfTaskjob->fields['plugin_glpiinventory_tasks_id']);
         echo "<td>";
         echo $pfTaskjob->getLink(1)." (".$pfTask->getLink().")";
         echo "</td>";
      }
      echo "<td>";
      $pfAgent->getFromDB($pfTaskjobstate->fields['plugin_glpiinventory_agents_id']);
      echo $pfAgent->getLink(1);

      Ajax::updateItemOnEvent('plusmoins'.$pfTaskjobstate->fields["id"],
                      'viewfollowup'.$pfTaskjobstate->fields["id"],
                      $fi_path."/ajax/showtaskjoblogdetail.php",
                      ['agents_id' =>
                                 $pfTaskjobstate->fields['plugin_glpiinventory_agents_id'],
                          'uniqid' => $pfTaskjobstate->fields['uniqid']],
                      ["click"]);

      echo "</td>";
      $a_return = $this->displayHistoryDetail(array_pop($a_history), 0);
      $count = $a_return[0];
      $displayforceend += $count;
      echo $a_return[1];

      if ($displayforceend == "0") {
         echo "<td align='center'>";
         echo "<form name='form' method='post' action='".
                 $fi_path."/front/taskjob.form.php'>";
         echo "<input type='hidden' name='taskjobstates_id' value='".
                 $pfTaskjobstate->fields['id']."' />";
         echo "<input type='hidden' name='taskjobs_id' value='".
                 $pfTaskjobstate->fields['plugin_glpiinventory_taskjobs_id']."' />";
         echo '<input name="forceend" value="'.__('Force the end', 'glpiinventory').'"
             class="submit" type="submit">';
         Html::closeForm();
         echo "</td>";
      }
      echo "</tr>";

      echo "<tr>";
      echo "<td colspan='".$nb_td."' style='display: none;' id='viewfollowup".
              $pfTaskjobstate->fields["id"]."' class='tab_bg_4'>";
      echo "</td>";
      echo "</tr>";
   }


   /**
    * Get the html display detail of each history line
    *
    * @param integer $agents_id id of the agent
    * @param integer $uniqid uniq id of each taskjobs runing
    * @param integer $width how large in pixel display array
    * @return string
    */
   function showHistoryInDetail($agents_id, $uniqid, $width = 950) {
      global $CFG_GLPI, $DB;

      $pfAgent          = new PluginGlpiinventoryAgent();
      $a_devices_merged = [];

      $text = "<center><table class='tab_cadrehov' style='width: ".$width."px'>";

      $params = ['FROM'  => 'glpi_plugin_glpiinventory_taskjobstates',
                 'WHERE' => ['plugin_glpiinventory_agents_id' => $agents_id, 'uniqid' => $uniqid],
                 'ORDER' => 'id DESC'
                ];
      foreach ($DB->request($params) as $data) {

         $displayforceend = 0;
         $a_history = $this->find(['plugin_glpiinventory_taskjobstates_id' => $data['id']], ['id']);

         if (strstr(exportArrayToDB($a_history), "Merged with ")) {
            $classname = $data['itemtype'];
            $Class     = new $classname();
            $Class->getFromDB($data['items_id']);
            $a_devices_merged[] = $Class->getLink(1)."&nbsp;(".$Class->getTypeName().")";
         } else {
            $text .= "<tr>";
            $text .= "<th colspan='2'><img src='".$CFG_GLPI['root_doc']."/pics/puce.gif' />".
                         __('Process number', 'glpiinventory')."&nbsp;: ".$data['id']."</th>";
            $text .= "<th>";
            $text .= _n('Date', 'Dates', 1);

            $text .= "</th>";
            $text .= "<th>";
            $text .= __('Status');

            $text .= "</th>";
            $text .= "<th>";
            $text .= __('Comments');

            $text .= "</th>";
            $text .= "</tr>";
            $text .= "<tr class='tab_bg_1'>";
            $text .= "<th colspan='2'>";
            $text .= __('Agent', 'glpiinventory');

            $text .= "</th>";
            $a_return = $this->displayHistoryDetail(array_shift($a_history));
            $count = $a_return[0];
            $text .= $a_return[1];
            $displayforceend += $count;
            $text .= "</tr>";

            $text .= "<tr class='tab_bg_1'>";
            $text .= "<td colspan='2'>";
            $pfAgent->getFromDB($data['plugin_glpiinventory_agents_id']);
            $text .= $pfAgent->getLink(1);
            $text .= "</td>";
            $a_return = $this->displayHistoryDetail(array_shift($a_history));
            $count = $a_return[0];
            $text .= $a_return[1];
            $displayforceend += $count;
            $text .= "</tr>";

            $text .= "<tr class='tab_bg_1'>";
            $text .= "<th colspan='2'>";
            $text .= __('Definition', 'glpiinventory');

            $text .= "<sup>(".(count($a_devices_merged) + 1).")</sup>";
            $text .= "</th>";
            $a_return = $this->displayHistoryDetail(array_shift($a_history));
            $count = $a_return[0];
            $text .= $a_return[1];
            $displayforceend += $count;
            $text .= "</tr>";

            $text .= "<tr class='tab_bg_1'>";
            $text .= "<td colspan='2'>";
            if (!empty($data["itemtype"])) {
               $device = new $data["itemtype"]();
               $device->getFromDB($data["items_id"]);
               $text .= $device->getLink(1);
               $text .= "&nbsp;";
               $text .= "(".$device->getTypeName().")";
            }
            $text .= "</td>";
            $a_return = $this->displayHistoryDetail(array_shift($a_history));
            $count = $a_return[0];
            $text .= $a_return[1];
            $displayforceend += $count;
            $text .= "</tr>";

            while (count($a_history) != 0) {
               if (count($a_devices_merged) > 0) {
                  $text .= "<tr class='tab_bg_1'>";
                  $text .= "<td colspan='2'>";
                  $text .= array_pop($a_devices_merged);
                  $text .= "</td>";
                  $a_return = $this->displayHistoryDetail(array_shift($a_history));
                  $count = $a_return[0];
                  $text .= $a_return[1];
                  $displayforceend += $count;
                  $text .= "</tr>";
               } else {
                  $text .= "<tr class='tab_bg_1'>";
                  $text .= "<td colspan='2' rowspan='".count($a_history)."'>";
                  $text .= "</td>";
                  $a_return = $this->displayHistoryDetail(array_shift($a_history));
                  $count = $a_return[0];
                  $text .= $a_return[1];
                  $displayforceend += $count;
                  $text .= "</tr>";

                  while (count($a_history) != 0) {
                     $text .= "<tr class='tab_bg_1'>";
                     $a_return = $this->displayHistoryDetail(array_shift($a_history));
                     $count = $a_return[0];
                     $text .= $a_return[1];
                     $displayforceend += $count;
                     $text .= "</tr>";
                  }
               }
            }
            $display = 1;
            while (count($a_devices_merged) != 0) {
               $text .= "<tr class='tab_bg_1'>";
               $text .= "<td colspan='2'>";
               $text .= array_pop($a_devices_merged);
               $text .= "</td>";
               if ($display == "1") {
                  $text .= "<td colspan='3' rowspan='".(count($a_devices_merged) + 1)."'></td>";
                  $display = 0;
               }
               $text .= "</tr>";
            }

            $text .= "<tr class='tab_bg_4'>";
            $text .= "<td colspan='5' height='4'>";
            $text .= "</td>";
            $text .= "</tr>";
         }
      }
      $text .= "</table></center>";
      return $text;
   }


   /**
    * Display high detail of each history line
    *
    * @param array $datas data of history
    * @param integer $comment 0/1 display comment or not
    * @return array
    *               - boolean 0/1 if this log = finish
    *               - text to display
    */
   function displayHistoryDetail($datas, $comment = 1) {

      $text = "<td align='center'>";
      $text .= Html::convDateTime($datas['date']);
      $text .= "</td>";
      $finish = 0;

      switch ($datas['state']) {

         case self::TASK_PREPARED :
            $text .= "<td align='center'>";
            $text .= __('Prepared', 'glpiinventory');

            break;

         case self::TASK_STARTED :
            $text .= "<td align='center'>";
            $text .= __('Started', 'glpiinventory');

            break;

         case self::TASK_OK :
            $text .= "<td style='background-color: rgb(0, 255, 0);-moz-border-radius:".
                 " 4px;-webkit-border-radius: 4px;-o-border-radius: 4px;padding: 2px;' ".
                 "align='center'>";
            $text .= "<strong>".__('Ok', 'glpiinventory')."</strong>";
            $finish++;
            break;

         case self::TASK_ERROR :
            $text .= "<td style='background-color: rgb(255, 0, 0);-moz-border-radius: ".
                 "4px;-webkit-border-radius: 4px;-o-border-radius: 4px;padding: 2px;' ".
                 "align='center'>";
            $text .= "<strong>".__('Error')."</strong>";
            $finish++;
            break;

         case self::TASK_INFO :
            $text .= "<td style='background-color: rgb(255, 200, 0);-moz-border-radius: ".
                 "4px;-webkit-border-radius: 4px;-o-border-radius: 4px;padding: 2px;' ".
                 "align='center'>";
            $text .= "<strong>".__('Info', 'glpiinventory')."</strong>";
            $finish++;
            break;

         case self::TASK_RUNNING :
            $text .= "<td style='background-color: rgb(255, 200, 0);-moz-border-radius: ".
                 "4px;-webkit-border-radius: 4px;-o-border-radius: 4px;padding: 2px;' ".
                 "align='center'>";
            $text .= "<strong>".__('Running')."</strong>";
            break;

         default:
            $text .= "<td>";
            break;
      }

      $text .= "</td>";
      if ($comment == '1') {
         $text .= "<td class='fusinv_task_comment'>";
         $datas['comment'] = PluginGlpiinventoryTaskjoblog::convertComment($datas['comment']);
         $text .= $datas['comment'];
         $text .= "</td>";
      }
      return [$finish, $text];
   }


   /**
    * Add a new line of log for a taskjob status
    *
    * @global object $DB
    * @param integer $taskjobstates_id id of the taskjobstate
    * @param integer $items_id id of the item associated with taskjob status
    * @param string $itemtype type name of the item associated with taskjob status
    * @param string $state state of this taskjobstate
    * @param string $comment the comment of this insertion
    */
   function addTaskjoblog($taskjobstates_id, $items_id, $itemtype, $state, $comment) {
      global $DB;
      $this->getEmpty();
      unset($this->fields['id']);
      $this->fields['plugin_glpiinventory_taskjobstates_id'] = $taskjobstates_id;
      $this->fields['date']      = $_SESSION['glpi_currenttime'];
      $this->fields['items_id']  = $items_id;
      $this->fields['itemtype']  = $itemtype;
      $this->fields['state']     = $state;
      $this->fields['comment']   = $DB->escape($comment);

      $this->addToDB();
   }


   /**
    * Display the graph of finished tasks
    *
    * @global object $DB
    * @param integer $taskjobs_id id of the taskjob
    */
   function graphFinish($taskjobs_id) {
      global $DB;

      $finishState = [2 => 0, 3 => 0, 4 => 0, 5 => 0];

      $query = "SELECT `glpi_plugin_glpiinventory_taskjoblogs`.`state`
         FROM glpi_plugin_glpiinventory_taskjobstates
         LEFT JOIN `glpi_plugin_glpiinventory_taskjoblogs`
            ON plugin_glpiinventory_taskjobstates_id=".
               "`glpi_plugin_glpiinventory_taskjobstates`.`id`
         WHERE `plugin_glpiinventory_taskjobs_id`='".$taskjobs_id."'
         AND (`glpi_plugin_glpiinventory_taskjoblogs`.`state` = '2'
            OR `glpi_plugin_glpiinventory_taskjoblogs`.`state` = '3'
            OR `glpi_plugin_glpiinventory_taskjoblogs`.`state` = '4'
            OR `glpi_plugin_glpiinventory_taskjoblogs`.`state` = '5')
         GROUP BY glpi_plugin_glpiinventory_taskjobstates.uniqid, ".
              "plugin_glpiinventory_agents_id";
      $result=$DB->query($query);
      if ($result) {
         while ($datajob=$DB->fetchArray($result)) {
            $finishState[$datajob['state']]++;
         }
      }
      $input = [];
      $input[__('Started', 'glpiinventory')] = $finishState[2];
      $input[__('Ok', 'glpiinventory')]      = $finishState[3];
      $input[__('Error / rescheduled', 'glpiinventory')] = $finishState[4];
      $input[__('Error')] = $finishState[5];
      Stat::showGraph(['status' => $input],
                        ['title'     => '',
                         'unit'      => '',
                         'type'      => 'pie',
                         'height'    => 150,
                         'showtotal' => false
                        ]);

   }


   /**
    * Get taskjobstate by uniqid
    *
    * @param string $uuid value uniqid
    * @return array with data of table glpi_plugin_glpiinventory_taskjobstates
    */
   static function getByUniqID($uuid) {
      $a_datas = getAllDataFromTable('glpi_plugin_glpiinventory_taskjobstates',
                                      ['uniqid' => $uuid],
                                      "1");
      foreach ($a_datas as $a_data) {
         return $a_data;
      }
      return [];
   }

   /**
    * Get div with text/color depend on state
    *
    * @param integer $state state number
    * @param string $type div / td
    * @return string complete node (openned and closed)
    */
   function getDivState($state, $type = 'div') {

      $width = '50';

      switch ($state) {

         case self::TASK_PREPARED:
            return "<".$type." align='center' width='".$width."'>".
                      __('Prepared', 'glpiinventory')."</".$type.">";

         case self::TASK_STARTED:
            return "<".$type." align='center' width='".$width."'>".
                       __('Started', 'glpiinventory')."</".$type.">";

         case self::TASK_OK:
            return "<".$type." style='background-color: rgb(0, 255, 0);-moz-border-radius: 4px;".
                     "-webkit-border-radius: 4px;-o-border-radius: 4px;padding: 2px;' ".
                     "align='center' width='".$width."'>".
                     "<strong>".__('OK')."</strong></".$type.">";

         case self::TASK_ERROR:
            return "<".$type." style='background-color: rgb(255, 0, 0);-moz-border-radius: 4px;".
                 "-webkit-border-radius: 4px;-o-border-radius: 4px;padding: 2px;' align='center' ".
                 "width='".$width."'>".
                 "<strong>".__('Error')."</strong></".$type.">";

         case self::TASK_INFO:
            return "<".$type." style='background-color: rgb(255, 200, 0);-moz-border-radius: 4px;".
                     "-webkit-border-radius: 4px;-o-border-radius: 4px;padding: 2px;' ".
                     "align='center' width='".$width."'>".
                     "<strong>".__('unknown', 'glpiinventory')."</strong></".$type.">";

         case self::TASK_RUNNING:
            return "<".$type." style='background-color: rgb(255, 200, 0);-moz-border-radius: 4px;".
                     "-webkit-border-radius: 4px;-o-border-radius: 4px;padding: 2px;' ".
                     "align='center' width='".$width."'>".
                     "<strong>".__('Running')."</strong></".$type.">";

      }
   }


   /**
    * Convert comment by replace formated message by translated message
    *
    * @param string $comment
    * @return string
    */
   static function convertComment($comment) {
      $matches = [];
      // Search for replace [[itemtype::items_id]] by link
      preg_match_all("/\[\[(.*)\:\:(.*)\]\]/", $comment, $matches);
      foreach ($matches[0] as $num=>$commentvalue) {
         $classname = $matches[1][$num];
         if ($classname != '') {
            $Class = new $classname;
            $Class->getFromDB($matches[2][$num]);
            $comment = str_replace($commentvalue, $Class->getLink(), $comment);
         }
      }
      if (strstr($comment, "==")) {
         preg_match_all("/==([\w\d]+)==/", $comment, $matches);
         $a_text = [
            'devicesqueried'  => __('devices queried', 'glpiinventory'),
            'devicesfound'    => __('devices found', 'glpiinventory'),
            'addtheitem'      => __('Add the item', 'glpiinventory'),
            'updatetheitem'   => __('Update the item', 'glpiinventory'),
            'inventorystarted' => __('Inventory started', 'glpiinventory'),
            'detail'          => __('Detail', 'glpiinventory'),
            'badtoken'        => __('Agent communication error, impossible to start agent', 'glpiinventory'),
            'agentcrashed'    => __('Agent stopped/crashed', 'glpiinventory'),
            'importdenied'    => __('Import denied', 'glpiinventory')
         ];
         foreach ($matches[0] as $num=>$commentvalue) {
            $comment = str_replace($commentvalue, $a_text[$matches[1][$num]], $comment);
         }
      }
      return str_replace(",[", "<br/>[", $comment);
   }
}
