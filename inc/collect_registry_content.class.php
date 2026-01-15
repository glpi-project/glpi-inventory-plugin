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

use function Safe\preg_match;

/**
 * Manage the registry keys found by the collect module of agent.
 */
class PluginGlpiinventoryCollect_Registry_Content extends PluginGlpiinventoryCollectContentCommon
{
    public string $collect_itemtype = PluginGlpiinventoryCollect_Registry::class;
    public string $collect_table    = 'glpi_plugin_glpiinventory_collects_registries';

    public string $collect_type = 'registry';

    /**
     * Get the tab name used for item
     *
     * @param CommonGLPI $item the item object
     * @param int $withtemplate 1 if is a template form
     * @return string name of the tab
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        /** @var CommonDBTM $item */
        if ($item->fields['id'] > 0) {
            if (get_class($item) == PluginGlpiinventoryCollect::class) {
                if ($item->fields['type'] == 'registry') {
                    $a_colregs = getAllDataFromTable(
                        'glpi_plugin_glpiinventory_collects_registries',
                        ['plugin_glpiinventory_collects_id' => $item->fields['id']]
                    );
                    if (count($a_colregs) == 0) {
                        return '';
                    }
                    $in = array_keys($a_colregs);
                    if (
                        countElementsInTable(
                            'glpi_plugin_glpiinventory_collects_registries_contents',
                            ['plugin_glpiinventory_collects_registries_id' => $in]
                        ) > 0
                    ) {
                        return __('Windows registry content', 'glpiinventory');
                    }
                }
            }
        }
        return '';
    }


    /**
     * Update computer registry values (add and update) related to this
     * collect registry id
     *
     * @param int $computers_id id of the computer
     * @param array<string,mixed> $registry_data registry info sent by agent
     * @param int $collects_registries_id id of collect_registry
     */
    public function updateComputer($computers_id, $registry_data, $collects_registries_id): void
    {
        /** @var DBmysql $DB */
        global $DB;

        $db_registries = [];

        $iterator = $DB->request([
            'SELECT' => ['id', 'key', 'value'],
            'FROM'   => 'glpi_plugin_glpiinventory_collects_registries_contents',
            'WHERE'  => [
                'computers_id' => $computers_id,
                'plugin_glpiinventory_collects_registries_id' => $collects_registries_id,
            ],
        ]);

        foreach ($iterator as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $db_registries[$idtmp] = $data;
        }

        unset($registry_data['_sid']);
        foreach ($registry_data as $key => $value) {
            foreach ($db_registries as $keydb => $arraydb) {
                if ($arraydb['key'] == $key) {
                    $input = ['key'   => $arraydb['key'],
                        'id'    => $keydb,
                        'value' => $value,
                    ];
                    $this->update($input);
                    unset($registry_data[$key]);
                    unset($db_registries[$keydb]);
                    break;
                }
            }
        }

        foreach (array_keys($db_registries) as $id) {
            $this->delete(['id' => $id], true);
        }
        foreach ($registry_data as $key => $value) {
            if (preg_match("/^0x[0-9a-fA-F]{1,}$/", $value)) {
                $value = hexdec($value);
            }
            $input = [
                'computers_id' => $computers_id,
                'plugin_glpiinventory_collects_registries_id' => $collects_registries_id,
                'key'          => $key,
                'value'        => $value,
            ];
            $this->add($input);
        }
    }

    /**
     * Show registries keys of the computer
     *
     * @param int $computers_id id of the computer
     */
    public function showForComputer(int $computers_id): void
    {
        $pfCollect_Registry = new PluginGlpiinventoryCollect_Registry();
        echo "<table class='tab_cadre_fixe'>";
        $a_data = $this->find(
            ['computers_id' => $computers_id],
            ['plugin_glpiinventory_collects_registries_id', 'key']
        );
        $previous_key = 0;
        foreach ($a_data as $data) {
            $pfCollect_Registry->getFromDB($data['plugin_glpiinventory_collects_registries_id']);
            if ($previous_key != $data['plugin_glpiinventory_collects_registries_id']) {
                echo "<tr class='tab_bg_1'>";
                echo '<th colspan="3">';
                echo $pfCollect_Registry->fields['name'];
                echo '</th>';
                echo '</tr>';

                echo "<tr>";
                echo "<th>" . __('Path', 'glpiinventory') . "</th>";
                echo "<th>" . __('Value', 'glpiinventory') . "</th>";
                echo "<th>" . __('Data', 'glpiinventory') . "</th>";
                echo "</tr>";

                $previous_key = $data['plugin_glpiinventory_collects_registries_id'];
            }

            echo "<tr class='tab_bg_1'>";
            echo '<td>';
            echo $pfCollect_Registry->fields['hive']
              . $pfCollect_Registry->fields['path'];
            echo '</td>';
            echo '<td>';
            echo $data['key'];
            echo '</td>';
            echo '<td>';
            echo $data['value'];
            echo '</td>';
            echo "</tr>";
        }
        echo '</table>';
    }


    /**
     * Display registry keys / values of collect_registry id
     *
     * @param int $id
     *
     * @return void
     */
    public function showContent(int $id): void
    {
        $collect_registry = new PluginGlpiinventoryCollect_Registry();
        $collect_registry->getFromDB($id);
        $computer = new Computer();

        $data = $this->find(
            ['plugin_glpiinventory_collects_registries_id' => $id],
            ['key']
        );
        $entries = [];
        foreach ($data as $row) {
            $computer->getFromDB($row['computers_id']);
            $entry = [
                'computer' => $computer->getLink(),
                'value' => $row['key'],
                'data'     => $row['value'],
            ];
            $entries[] = $entry;
        }

        echo '<div class="card">
            <div class="card-body">
                <h3 class="card-title">' . $collect_registry->fields['name'] . ' - ' . $collect_registry->fields['hive']
            . $collect_registry->fields['path'] . '</h3>';
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'computer' => __('Computer'),
                'value' => __('Value', 'glpiinventory'),
                'data' => __('Data', 'glpiinventory'),
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
