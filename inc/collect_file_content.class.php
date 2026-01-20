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
 * Manage the files found by the collect module of agent.
 */
class PluginGlpiinventoryCollect_File_Content extends PluginGlpiinventoryCollectContentCommon
{
    public string $collect_itemtype = PluginGlpiinventoryCollect_File::class;
    public string $collect_table    = 'glpi_plugin_glpiinventory_collects_files';
    public string $collect_type     = 'file';

    /**
     * Update computer files (add and update files) related to this
     * collect file id
     *
     * @param int $computers_id id of the computer
     * @param array<string,mixed> $file_data
     * @param int $collects_files_id id of collect_file
     */
    public function updateComputer($computers_id, $file_data, $collects_files_id): void
    {
        foreach ($file_data as $key => $value) {
            $input = [
                'computers_id' => $computers_id,
                'plugin_glpiinventory_collects_files_id' => $collects_files_id,
                'pathfile'     => str_replace(['\\', '//'], ['/', '/'], $value['path']),
                'size'         => $value['size'],
            ];
            $this->add($input);
        }
    }


    /**
     * Display files found on the computer
     *
     * @param int $computers_id id of the computer
     */
    public function showForComputer(int $computers_id): void
    {
        $pfCollect_File = new PluginGlpiinventoryCollect_File();

        echo "<table class='tab_cadre_fixe'>";

        $a_data = $this->find(
            ['computers_id' => $computers_id],
            ['plugin_glpiinventory_collects_files_id', 'pathfile']
        );
        $previous_key = 0;
        foreach ($a_data as $data) {
            $pfCollect_File->getFromDB($data['plugin_glpiinventory_collects_files_id']);
            if ($previous_key != $data['plugin_glpiinventory_collects_files_id']) {
                echo "<tr class='tab_bg_1'>";
                echo '<th colspan="3">';
                echo $pfCollect_File->fields['name'] . ": " . $pfCollect_File->fields['dir'];
                echo '</th>';
                echo '</tr>';

                echo "<tr>";
                echo "<th>" . __('Path/file', 'glpiinventory') . "</th>";
                echo "<th>" . __('Size', 'glpiinventory') . "</th>";
                echo "</tr>";

                $previous_key = $data['plugin_glpiinventory_collects_files_id'];
            }

            echo "<tr class='tab_bg_1'>";
            echo '<td>';
            echo $data['pathfile'];
            echo '</td>';
            echo '<td>';
            echo Toolbox::getSize($data['size']);
            echo '</td>';
            echo "</tr>";
        }
        echo '</table>';
    }

    /**
     * Display all files found on all computers related to the collect file
     *
     * @param int $id id of collect_file
     *
     * @return void
     */
    public function showContent(int $id): void
    {
        $collect_file = new PluginGlpiinventoryCollect_File();
        $computer = new Computer();
        $collect_file->getFromDB($id);

        $data = $this->find(
            ['plugin_glpiinventory_collects_files_id' => $id],
            ['pathfile']
        );
        $entries = [];
        foreach ($data as $row) {
            $computer->getFromDB($row['computers_id']);
            $entry = [
                'computer' => $computer->getLink(),
                'pathfile' => $row['pathfile'],
                'size'     => $row['size'],
            ];
            $entries[] = $entry;
        }

        echo '<div class="card">
            <div class="card-body">
                <h3 class="card-title">' . $collect_file->fields['name'] . '</h3>';
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'computer' => Computer::getTypeName(1),
                'pathfile' => __('Path/file', 'glpiinventory'),
                'size' => __('Size', 'glpiinventory'),
            ],
            'formatters' => [
                'computer' => 'raw_html',
                'size' => 'bytesize',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
        ]);
        echo '</div></div>';
    }
}
