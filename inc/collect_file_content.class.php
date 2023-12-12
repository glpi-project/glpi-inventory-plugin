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
    die("Sorry. You can't access directly to this file");
}

/**
 * Manage the files found by the collect module of agent.
 */
class PluginGlpiinventoryCollect_File_Content extends PluginGlpiinventoryCollectContentCommon
{
    public $collect_itemtype = 'PluginGlpiinventoryCollect_File';
    public $collect_table    = 'glpi_plugin_glpiinventory_collects_files';
    public $type             = 'file';

   /**
    * Update computer files (add and update files) related to this
    * collect file id
    *
    * @global object $DB
    * @param integer $computers_id id of the computer
    * @param array $file_data
    * @param integer $collects_files_id id of collect_file
    */
    public function updateComputer($computers_id, $file_data, $collects_files_id)
    {
        foreach ($file_data as $key => $value) {
            $input = [
            'computers_id' => $computers_id,
            'plugin_glpiinventory_collects_files_id' => $collects_files_id,
            'pathfile'     => str_replace(['\\', '//'], ['/', '/'], $value['path']),
            'size'         => $value['size']
            ];
            $this->add($input);
        }
    }


   /**
    * Display files found on the computer
    *
    * @param integer $computers_id id of the computer
    */
    public function showForComputer($computers_id)
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
    * @param integer $collects_files_id id of collect_file
    */
    public function showContent($collects_files_id)
    {
        $pfCollect_File = new PluginGlpiinventoryCollect_File();
        $computer = new Computer();

        $pfCollect_File->getFromDB($collects_files_id);

        echo "<table class='tab_cadre_fixe'>";

        echo "<tr>";
        echo "<th colspan='3'>";
        echo $pfCollect_File->fields['name'];
        echo "</th>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>" . __('Computer') . "</th>";
        echo "<th>" . __('pathfile', 'glpiinventory') . "</th>";
        echo "<th>" . __('Size', 'glpiinventory') . "</th>";
        echo "</tr>";

        $a_data = $this->find(
            ['plugin_glpiinventory_collects_files_id' => $collects_files_id],
            ['pathfile']
        );
        foreach ($a_data as $data) {
            echo "<tr class='tab_bg_1'>";
            echo '<td>';
            $computer->getFromDB($data['computers_id']);
            echo $computer->getLink(1);
            echo '</td>';
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
}
