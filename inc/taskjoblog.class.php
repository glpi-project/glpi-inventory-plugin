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
    die("Sorry. You can't access this file directly");
}

/**
 * Manage the logs of task job.
 */
class PluginGlpiinventoryTaskjoblog extends CommonDBTM
{
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
    public static function dropdownStateValues()
    {

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
    public static function getStateName($state = -1)
    {
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
    public static function getStateItemtype($taskjoblogs_id)
    {
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
    public function rawSearchOptions()
    {

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
         'table'        => "glpi_agents",
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
    * Add a new line of log for a taskjob status
    *
    * @global object $DB
    * @param integer $taskjobstates_id id of the taskjobstate
    * @param integer $items_id id of the item associated with taskjob status
    * @param string $itemtype type name of the item associated with taskjob status
    * @param string $state state of this taskjobstate
    * @param string $comment the comment of this insertion
    */
    public function addTaskjoblog($taskjobstates_id, $items_id, $itemtype, $state, $comment)
    {
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
    public function graphFinish($taskjobs_id)
    {
        global $DB;

        $finishState = [2 => 0, 3 => 0, 4 => 0, 5 => 0];

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_plugin_glpiinventory_taskjoblogs.state'
            ],
            'FROM'   => 'glpi_plugin_glpiinventory_taskjobstates',
            'LEFT JOIN' => [
                'glpi_plugin_glpiinventory_taskjoblogs' => [
                    'ON' => [
                        'glpi_plugin_glpiinventory_taskjoblogs' => 'plugin_glpiinventory_taskjobstates_id',
                        'glpi_plugin_glpiinventory_taskjobstates' => 'id'
                    ]
                ]
            ],
            'WHERE'  => [
                'plugin_glpiinventory_taskjobs_id' => $taskjobs_id,
                'OR' => [
                    'glpi_plugin_glpiinventory_taskjoblogs.state' => [2, 3, 4, 5]
                ]
            ],
            'GROUPBY' => [
                'glpi_plugin_glpiinventory_taskjobstates.uniqid',
                'agents_id'
            ]
        ]);

        if (count($iterator)) {
            foreach ($iterator as $datajob) {
                $finishState[$datajob['state']]++;
            }
        }
        $input = [];
        $input[__('Started', 'glpiinventory')] = $finishState[2];
        $input[__('Ok', 'glpiinventory')]      = $finishState[3];
        $input[__('Error / rescheduled', 'glpiinventory')] = $finishState[4];
        $input[__('Error')] = $finishState[5];
        Stat::showGraph(
            ['status' => $input],
            ['title'     => '',
                         'unit'      => '',
                         'type'      => 'pie',
                         'height'    => 150,
                         'showtotal' => false
            ]
        );
    }


   /**
    * Get taskjobstate by uniqid
    *
    * @param string $uuid value uniqid
    * @return array with data of table glpi_plugin_glpiinventory_taskjobstates
    */
    public static function getByUniqID($uuid)
    {
        $a_datas = getAllDataFromTable(
            'glpi_plugin_glpiinventory_taskjobstates',
            ['uniqid' => $uuid],
            "1"
        );
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
    public function getDivState($state, $type = 'div')
    {

        $width = '50';

        switch ($state) {
            case self::TASK_PREPARED:
                return "<" . $type . " align='center' width='" . $width . "'>" .
                      __('Prepared', 'glpiinventory') . "</" . $type . ">";

            case self::TASK_STARTED:
                return "<" . $type . " align='center' width='" . $width . "'>" .
                       __('Started', 'glpiinventory') . "</" . $type . ">";

            case self::TASK_OK:
                return "<" . $type . " style='background-color: rgb(0, 255, 0);-moz-border-radius: 4px;" .
                     "-webkit-border-radius: 4px;-o-border-radius: 4px;padding: 2px;' " .
                     "align='center' width='" . $width . "'>" .
                     "<strong>" . __('OK') . "</strong></" . $type . ">";

            case self::TASK_ERROR:
                return "<" . $type . " style='background-color: rgb(255, 0, 0);-moz-border-radius: 4px;" .
                 "-webkit-border-radius: 4px;-o-border-radius: 4px;padding: 2px;' align='center' " .
                 "width='" . $width . "'>" .
                 "<strong>" . __('Error') . "</strong></" . $type . ">";

            case self::TASK_INFO:
                return "<" . $type . " style='background-color: rgb(255, 200, 0);-moz-border-radius: 4px;" .
                     "-webkit-border-radius: 4px;-o-border-radius: 4px;padding: 2px;' " .
                     "align='center' width='" . $width . "'>" .
                     "<strong>" . __('unknown', 'glpiinventory') . "</strong></" . $type . ">";

            case self::TASK_RUNNING:
                return "<" . $type . " style='background-color: rgb(255, 200, 0);-moz-border-radius: 4px;" .
                     "-webkit-border-radius: 4px;-o-border-radius: 4px;padding: 2px;' " .
                     "align='center' width='" . $width . "'>" .
                     "<strong>" . __('Running') . "</strong></" . $type . ">";
        }
    }


   /**
    * Convert comment by replace formatted message by translated message
    *
    * @param string $comment
    * @return string
    */
    public static function convertComment($comment)
    {
        $matches = [];
       // Search for replace [[itemtype::items_id]] by link
        preg_match_all("/\[\[(.*)\:\:(.*)\]\]/", $comment, $matches);
        foreach ($matches[0] as $num => $commentvalue) {
            $classname = $matches[1][$num];
            if ($classname != '' && class_exists($classname)) {
                $Class = new $classname();
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
            foreach ($matches[0] as $num => $commentvalue) {
                $comment = str_replace($commentvalue, $a_text[$matches[1][$num]], $comment);
            }
        }
        return str_replace(",[", "<br/>[", $comment);
    }
}
