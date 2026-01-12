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

use Glpi\Application\View\TemplateRenderer;

/**
 * Manage the wmi information found by the collect module of agent.
 */

class PluginGlpiinventoryCollect_Wmi_Content extends PluginGlpiinventoryCollectContentCommon
{
    public string $collect_itemtype = PluginGlpiinventoryCollect_Wmi::class;
    public string $collect_table    = 'glpi_plugin_glpiinventory_collects_wmis';

    public string $collect_type = 'wmi';

    /**
     * update wmi data to compute (add and update) with data sent by the agent
     *
     * @param int $computers_id id of the computer
     * @param array<string,mixed> $wmi_data
     * @param int $collects_wmis_id
     */
    public function updateComputer($computers_id, $wmi_data, $collects_wmis_id): void
    {
        /** @var DBmysql $DB */
        global $DB;

        $db_wmis = [];

        $iterator = $DB->request([
            'SELECT' => ['id', 'property', 'value'],
            'FROM'   => 'glpi_plugin_glpiinventory_collects_wmis_contents',
            'WHERE'  => [
                'computers_id' => $computers_id,
                'plugin_glpiinventory_collects_wmis_id' => $collects_wmis_id,
            ],
        ]);

        foreach ($iterator as $data) {
            $wmi_id = $data['id'];
            unset($data['id']);
            $db_wmis[$wmi_id] = $data;
        }

        unset($wmi_data['_sid']);
        foreach ($wmi_data as $key => $value) {
            foreach ($db_wmis as $keydb => $arraydb) {
                if ($arraydb['property'] == $key) {
                    $input = ['property' => $arraydb['property'],
                        'id'       => $keydb,
                        'value'    => $value,
                    ];
                    $this->update($input);
                    unset($wmi_data[$key]);
                    unset($db_wmis[$keydb]);
                    break;
                }
            }
        }

        foreach (array_keys($db_wmis) as $id) {
            $this->delete(['id' => $id], true);
        }
        foreach ($wmi_data as $key => $value) {
            $input = [
                'computers_id' => $computers_id,
                'plugin_glpiinventory_collects_wmis_id' => $collects_wmis_id,
                'property'     => $key,
                'value'        => $value,
            ];
            $this->add($input);
        }
    }

    /**
     * Display wmi information of computer
     *
     * @param int $computers_id id of computer
     */
    public function showForComputer(int $computers_id): void
    {

        $pfCollect_Wmi = new PluginGlpiinventoryCollect_Wmi();
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr>";
        echo "<th>Moniker</th>";
        echo "<th>" . __('Class', 'glpiinventory') . "</th>";
        echo "<th>" . __('Property', 'glpiinventory') . "</th>";
        echo "<th>" . __('Value', 'glpiinventory') . "</th>";
        echo "</tr>";

        $a_data = $this->find(
            ['computers_id' => $computers_id],
            ['plugin_glpiinventory_collects_wmis_id', 'property']
        );
        foreach ($a_data as $data) {
            echo "<tr class='tab_bg_1'>";
            echo '<td>';
            $pfCollect_Wmi->getFromDB($data['plugin_glpiinventory_collects_wmis_id']);
            echo $pfCollect_Wmi->fields['moniker'];
            echo '</td>';
            echo '<td>';
            echo $pfCollect_Wmi->fields['class'];
            echo '</td>';
            echo '<td>';
            echo $data['property'];
            echo '</td>';
            echo '<td>';
            echo $data['value'];
            echo '</td>';
            echo "</tr>";
        }
        echo '</table>';
    }


    /**
     * Display wmi information of collect_wmi_id
     *
     * @param int $id
     *
     * @return void
     */
    public function showContent(int $id): void
    {
        $collect_wmi = new PluginGlpiinventoryCollect_Wmi();
        $computer = new Computer();
        $collect_wmi->getFromDB($id);

        $data = $this->find(
            ['plugin_glpiinventory_collects_wmis_id' => $id],
            ['property']
        );
        $entries = [];
        foreach ($data as $row) {
            $computer->getFromDB($row['computers_id']);
            $entry = [
                'computer' => $computer->getLink(),
                'property' => $row['property'],
                'value'     => $row['value'],
            ];
            $entries[] = $entry;
        }

        echo '<div class="card">
            <div class="card-body">
                <h3 class="card-title">' . $collect_wmi->fields['name'] . ' - ' . $collect_wmi->fields['class'] . '</h3>';
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'computer' => __('Computer'),
                'property' => __('Property', 'glpiinventory'),
                'value' => __('Value', 'glpiinventory'),
            ],
            'formatters' => [
                'computer' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
        ]);
        echo '</div></div>';
    }
}
