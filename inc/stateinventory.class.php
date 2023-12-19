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
 * Manage the network inventory state.
 */
class PluginGlpiinventoryStateInventory extends CommonDBTM
{
   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = 'plugin_glpiinventory_task';


   /**
    * __contruct function where add variable in $CFG_GLPI
    *
    * @global array $CFG_GLPI
    */
    public function __construct()
    {
        global $CFG_GLPI;

        $CFG_GLPI['glpitablesitemtype']['PluginGlpiinventoryStateInventory'] =
          'glpi_plugin_glpiinventory_taskjobstates';
    }


   /**
    * Display network inventory state
    *
    * @global object $DB
    * @global array $CFG_GLPI
    * @param array $options
    */
    public function display($options = [])
    {
        global $DB, $CFG_GLPI;

        $agent = new Agent();
        $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
        $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
        $pfTaskjob = new PluginGlpiinventoryTaskjob();
        $pfTask = new PluginGlpiinventoryTask();

        $start = 0;
        if (isset($_REQUEST["start"])) {
            $start = $_REQUEST["start"];
        }

        // Total Number of events
        $iterator = $DB->request([
            'COUNT' => 'cpt',
            'FROM'   => 'glpi_plugin_glpiinventory_taskjobstates',
            'LEFT JOIN' => [
                'glpi_plugin_glpiinventory_taskjobs' => [
                    'ON' => [
                        'glpi_plugin_glpiinventory_taskjobs' => 'id',
                        'glpi_plugin_glpiinventory_taskjobstates' => 'plugin_glpiinventory_taskjobs_id'
                    ]
                ]
            ],
            'WHERE'  => [
                'method' => 'networkinventory'
            ],
            'GROUPBY' => 'uniqid',
            'ORDERBY' => 'uniqid DESC'
        ]);

        $number = count($iterator);

       // Display the pager
        Html::printPager($start, $number, Plugin::getWebDir('glpiinventory') . "/front/stateinventory.php", '');

        echo "<div class='card'>";
        echo "<table class='table table-hover card-table'>";

        echo "<thead>";
        echo "<tr class='tab_bg_1'>";
        echo "<th>" . __('Unique id', 'glpiinventory') . "</th>";
        echo "<th>" . _n('Task', 'Tasks', 1, 'glpiinventory') . "</th>";
        echo "<th>" . __('Agent', 'glpiinventory') . "</th>";
        echo "<th>" . __('Status') . "</th>";
        echo "<th>" . __('Starting date', 'glpiinventory') . "</th>";
        echo "<th>" . __('Ending date', 'glpiinventory') . "</th>";
        echo "<th>" . __('Total duration') . "</th>";
        echo "<th>" . __('Number per second', 'glpiinventory') . "</th>";
        echo "<th>" . __('Threads / Timeout configuration', 'glpiinventory') . "</th>";
        echo "<th>" . __('To inventory', 'glpiinventory') . "</th>";
        echo "<th>" . __('Error(s)', 'glpiinventory') . "</th>";
        echo "</tr>";
        echo "</thead>";

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_plugin_glpiinventory_taskjobstates.*'
            ],
            'FROM'   => 'glpi_plugin_glpiinventory_taskjobstates',
            'LEFT JOIN' => [
                'glpi_plugin_glpiinventory_taskjobs' => [
                    'ON' => [
                        'glpi_plugin_glpiinventory_taskjobs' => 'id',
                        'glpi_plugin_glpiinventory_taskjobstates' => 'plugin_glpiinventory_taskjobs_id'
                    ]
                ]
            ],
            'WHERE'  => [
                'method' => 'networkinventory'
            ],
            'GROUPBY' => 'uniqid',
            'ORDERBY' => 'uniqid DESC',
            'LIMIT'  => (int)$_SESSION['glpilist_limit'],
            'START'  => (int)$start
        ]);

        foreach ($iterator as $data) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . $data['uniqid'] . "</td>";
            $pfTaskjob->getFromDB($data['plugin_glpiinventory_taskjobs_id']);
            $pfTask->getFromDB($pfTaskjob->fields['plugin_glpiinventory_tasks_id']);
            echo "<td>";
            $link = $pfTask->getLinkURL() . '&forcetab=PluginGlpiinventoryTaskjobstate$1';
            $label = $pfTask->getNameID([]);
            echo "<a href='$link'>$label</a>";
            echo "</td>";
            $agent->getFromDB($data['agents_id']);
            echo "<td>" . $agent->getLink(1) . "</td>";
            $nb_query = 0;
            $nb_threads = 0;
            $start_date = "";
            $end_date = "";
            $nb_errors = 0;
            $a_taskjobstates = $pfTaskjobstate->find(['uniqid' => $data['uniqid']]);
            foreach ($a_taskjobstates as $datastate) {
                $a_taskjoblog = $pfTaskjoblog->find(['plugin_glpiinventory_taskjobstates_id' => $datastate['id']]);
                foreach ($a_taskjoblog as $taskjoblog) {
                    if (strstr($taskjoblog['comment'], " ==devicesqueried==")) {
                        $nb_query += str_replace(" ==devicesqueried==", "", $taskjoblog['comment']);
                    } elseif (strstr($taskjoblog['comment'], " No response from remote host")) {
                        $nb_errors++;
                    } elseif ($taskjoblog['state'] == "1") {
                        $nb_threads = $taskjoblog['comment'];
                        $start_date = $taskjoblog['date'];
                    }

                    if (
                        ($taskjoblog['state'] == "2")
                        or ($taskjoblog['state'] == "3")
                        or ($taskjoblog['state'] == "4")
                        or ($taskjoblog['state'] == "5")
                    ) {
                        if (!strstr($taskjoblog['comment'], 'Merged with ')) {
                            $end_date = $taskjoblog['date'];
                        }
                    }
                }
            }
           // State
            echo "<td>";
            switch ($data['state']) {
                case 0:
                    echo __('Prepared', 'glpiinventory');
                    break;

                case 1:
                case 2:
                    echo __('Started', 'glpiinventory');
                    break;

                case 3:
                    echo __('Finished tasks', 'glpiinventory');
                    break;
            }
            echo "</td>";

            echo "<td>" . Html::convDateTime($start_date) . "</td>";
            echo "<td>" . Html::convDateTime($end_date) . "</td>";

            if ($end_date == '') {
                $end_date = date("Y-m-d H:i:s");
            }
            if ($start_date == '') {
                echo "<td>-</td>";
                echo "<td>-</td>";
            } else {
                $date1 = new DateTime($start_date);
                $date2 = new DateTime($end_date);
                $interval = $date1->diff($date2);
                $display_date = '';
                if ($interval->h > 0) {
                    $display_date .= $interval->h . "h ";
                } elseif ($interval->i > 0) {
                    $display_date .= $interval->i . "min ";
                }
                echo "<td>" . $display_date . $interval->s . "s</td>";

                $nb_per_second = 0;
                if (strtotime($end_date) - strtotime($start_date) > 0) {
                    $nb_per_second = round(($nb_query - $nb_errors) /
                    (strtotime($end_date) - strtotime($start_date)), 2);
                }
                echo "<td>" . $nb_per_second . "</td>";
            }
            echo "<td>" . $nb_threads . "</td>";
            echo "<td>" . $nb_query . "</td>";
            echo "<td>" . $nb_errors . "</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "</div>";
    }


   /**
    * Display diff between 2 dates, so the time elapsed of execution
    *
    * @param string $date1
    * @param string $date2
    */
    public function dateDiff($date1, $date2)
    {
        $timestamp1 = strtotime($date1);
        $timestamp2 = strtotime($date2);

        $interval = [];
        $timestamp = $timestamp2 - $timestamp1;
        $nb_min = floor($timestamp / 60);
        $interval['s'] = $timestamp - ($nb_min * 60);
        $nb_hour = floor($nb_min / 60);
        $interval['i'] = $nb_min - ($nb_hour * 60);

        $display_date = '';
        if ($nb_hour > 0) {
            $display_date .= $nb_hour . "h ";
        } elseif ($interval['i'] > 0) {
            $display_date .= $interval['i'] . "min ";
        }

        echo "<td>" . $display_date . $interval['s'] . "s</td>";
    }
}
