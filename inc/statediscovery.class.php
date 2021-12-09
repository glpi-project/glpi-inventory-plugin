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
 * Manage the network discovery state.
 */
class PluginGlpiinventoryStateDiscovery extends CommonDBTM
{

   /**
    * The right name for this class
    *
    * @var string
    */
    static $rightname = 'plugin_glpiinventory_task';


   /**
    * Update state of discovery
    *
    * @param integer $p_number
    * @param array $a_input
    * @param integer $agent_id
    */
    public function updateState($p_number, $a_input, $agent_id)
    {
        $data = $this->find(
            ['plugin_glpiinventory_taskjob_id' => $p_number,
            'agents_id'  => $agent_id]
        );
        if (count($data) == "0") {
            $input = [];
            $input['plugin_glpiinventory_taskjob_id'] = $p_number;
            $input['agents_id'] = $agent_id;
            $id = $this->add($input);
            $this->getFromDB($id);
            $data[$id] = $this->fields;
        }

        foreach ($data as $process_id => $input) {
            foreach ($a_input as $field => $value) {
                if (
                    $field == 'nb_ip'
                    || $field == 'nb_found'
                    || $field == 'nb_error'
                    || $field == 'nb_exists'
                    || $field == 'nb_import'
                ) {
                    $input[$field] = $data[$process_id][$field] + $value;
                } else {
                    $input[$field] = $value;
                }
            }
            $this->update($input);
        }
       // If discovery and query are finished, we will end Process
        $this->getFromDB($process_id);
        $doEnd = 1;
        if (
            ($this->fields['threads'] != '0')
              && ($this->fields['end_time'] == '')
        ) {
            $doEnd = 0;
        }

        if ($doEnd == '1') {
            $this->endState($p_number, date("Y-m-d H:i:s"), $agent_id);
        }
    }


   /**
    * End the state process
    *
    * @param integer $p_number
    * @param string $date_end
    * @param integer $agent_id
    */
    public function endState($p_number, $date_end, $agent_id)
    {
        $data = $this->find(
            ['plugin_glpiinventory_taskjob_id' => $p_number,
            'agents_id'  => $agent_id]
        );
        foreach ($data as $input) {
            $input['end_time'] = $date_end;
            $this->update($input);
        }
    }


   /**
    * Display the discovery state
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
        $pfStateInventory = new PluginGlpiinventoryStateInventory();
        $pfTaskjob = new PluginGlpiinventoryTaskjob();

        $start = 0;
        if (isset($_REQUEST["start"])) {
            $start = $_REQUEST["start"];
        }

       // Total Number of events
        $querycount = "SELECT count(*) AS cpt FROM `glpi_plugin_glpiinventory_taskjobstates`
         LEFT JOIN `glpi_plugin_glpiinventory_taskjobs`
            ON `plugin_glpiinventory_taskjobs_id` = `glpi_plugin_glpiinventory_taskjobs`.`id`
         WHERE `method` = 'networkdiscovery'
         GROUP BY `uniqid`
         ORDER BY `uniqid` DESC ";

        $resultcount = $DB->query($querycount);
        $number = $DB->numrows($resultcount);

       // Display the pager
        Html::printPager($start, $number, Plugin::getWebDir('glpiinventory') . "/front/statediscovery.php", '');

        echo "<div class='card'>";
        echo "<table class='table table-hover card-table'>";

        echo "<thead>";
        echo "<tr class='tab_bg_1'>";
        echo "<th>" . __('Unique id', 'glpiinventory') . "</th>";
        echo "<th>" . __('Task job', 'glpiinventory') . "</th>";
        echo "<th>" . __('Agent', 'glpiinventory') . "</th>";
        echo "<th>" . __('Status') . "</th>";
        echo "<th>" . __('Starting date', 'glpiinventory') . "</th>";
        echo "<th>" . __('Ending date', 'glpiinventory') . "</th>";
        echo "<th>" . __('Total duration') . "</th>";
        echo "<th>" . __('Threads number', 'glpiinventory') . "</th>";
        echo "<th>" . __('Total discovery devices', 'glpiinventory') . "</th>";
        echo "<th>" . __('Devices not imported', 'glpiinventory') . "</th>";
        echo "<th>" . __('Devices linked', 'glpiinventory') . "</th>";
        echo "<th>" . __('Devices imported', 'glpiinventory') . "</th>";
        echo "</tr>";
        echo "</thead>";

        $sql = "SELECT `glpi_plugin_glpiinventory_taskjobstates`.*
            FROM `glpi_plugin_glpiinventory_taskjobstates`
         LEFT JOIN `glpi_plugin_glpiinventory_taskjobs`
            ON `plugin_glpiinventory_taskjobs_id` = `glpi_plugin_glpiinventory_taskjobs`.`id`
         WHERE `method` = 'networkdiscovery'
         GROUP BY `uniqid`
         ORDER BY `uniqid` DESC
         LIMIT " . intval($start) . ", " . intval($_SESSION['glpilist_limit']);

        $result = $DB->query($sql);
        while ($data = $DB->fetchArray($result)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . $data['uniqid'] . "</td>";
            $pfTaskjob->getFromDB($data['plugin_glpiinventory_taskjobs_id']);
            echo "<td>";
            $link = $pfTaskjob->getLink();
            $link = str_replace('.form', '', $link);
            echo $link;
            echo "</td>";
            $agent->getFromDB($data['agents_id']);
            echo "<td>" . $agent->getLink(1) . "</td>";
            $nb_found = 0;
            $nb_threads = 0;
            $start_date = "";
            $end_date = "";
            $notimporteddevices = 0;
            $updateddevices = 0;
            $createddevices = 0;
            $a_taskjobstates = $pfTaskjobstate->find(['uniqid' => $data['uniqid']]);
            foreach ($a_taskjobstates as $datastate) {
                $a_taskjoblog = $pfTaskjoblog->find(['plugin_glpiinventory_taskjobstates_id' => $datastate['id']]);
                foreach ($a_taskjoblog as $taskjoblog) {
                    if (strstr($taskjoblog['comment'], " ==devicesfound==")) {
                        $nb_found += str_replace(" ==devicesfound==", "", $taskjoblog['comment']);
                    } elseif (strstr($taskjoblog['comment'], "==importdenied==")) {
                        $notimporteddevices++;
                    } elseif (strstr($taskjoblog['comment'], "==updatetheitem==")) {
                        $updateddevices++;
                    } elseif (strstr($taskjoblog['comment'], "==addtheitem==")) {
                        $createddevices++;
                    } elseif ($taskjoblog['state'] == "1") {
                        $nb_threads = str_replace(" threads", "", $taskjoblog['comment']);
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
            } else {
                $interval = '';
                if (phpversion() >= 5.3) {
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
                } else {
                    $interval = $pfStateInventory->dateDiff($start_date, $end_date);
                }
            }
            echo "<td>" . $nb_threads . "</td>";
            echo "<td>" . $nb_found . "</td>";
            echo "<td>" . $notimporteddevices . "</td>";
            echo "<td>" . $updateddevices . "</td>";
            echo "<td>" . $createddevices . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
}
