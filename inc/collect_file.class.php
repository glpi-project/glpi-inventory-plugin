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

/**
 * Manage the files to search in collect module.
 */
class PluginGlpiinventoryCollect_File extends PluginGlpiinventoryCollectCommon
{
    public string $collect_type = 'file';

    /**
     * Get name of this type by language of the user connected
     *
     * @param int $nb number of elements
     * @return string name of this type
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Found file', 'Found files', $nb, 'glpiinventory');
    }

    public static function getIcon()
    {
        return "ti ti-file-search";
    }

    public function getListHeaders(): array
    {
        return [
            'limit' => __("Limit", "glpiinventory"),
            'dir' => __("Folder", "glpiinventory"),
            'is_recursive' => __("Recursive", "glpiinventory"),
            'filter_regex' => __("Regex", "glpiinventory"),
            'filter_size' => __("Size", "glpiinventory"),
            'filter_checksumsha512' => __("Checksum SHA512", "glpiinventory"),
            'filter_checksumsha2' => __("Checksum SHA2", "glpiinventory"),
            'filter_name' => __("Name", "glpiinventory"),
            'filter_iname' => __("Iname", "glpiinventory"),
            'filter_type' => __("Type", "glpiinventory"),
        ];
    }

    public function displayOneRow(array $row = []): array
    {
        $filter = $type = '';
        if (!empty($row['filter_sizeequals'])) {
            $filter = '= ' . $row['filter_sizeequals'];
        } elseif (!empty($row['filter_sizegreater'])) {
            $filter = '> ' . $row['filter_sizegreater'];
        } elseif (!empty($row['filter_sizelower'])) {
            $filter = '< ' . $row['filter_sizelower'];
        }
        if ($row['filter_is_file'] == 1) {
            $type = __('File', 'glpiinventory');
        } else {
            $type = __('Folder', 'glpiinventory');
        }

        return [
            'limit' => $row['limit'],
            'dir' => $row['dir'],
            'is_recursive' => $row['is_recursive'],
            'filter_regex' => $row['filter_regex'],
            'filter_size' => $filter,
            'filter_checksumsha512' => $row['filter_checksumsha512'],
            'filter_checksumsha2' => $row['filter_checksumsha2'],
            'filter_name' => $row['filter_name'],
            'filter_iname' => $row['filter_iname'],
            'filter_type' => $type,
        ];
    }

    /**
     * After purge item, delete collect files
     */
    public function post_purgeItem()
    {
        // Delete all File
        $pfCollectFileContent = new PluginGlpiinventoryCollect_File_Content();
        $items = $pfCollectFileContent->find(['plugin_glpiinventory_collects_files_id' => $this->fields['id']]);
        foreach ($items as $item) {
            $pfCollectFileContent->delete(['id' => $item['id']], true);
        }
        parent::post_deleteItem();
    }

    public function prepareInputForAdd($input)
    {
        // conversions
        if (!empty($input['sizetype']) && $input['sizetype'] != 'none') {
            $input['filter_size' . $input['sizetype']] = $input['size'];
        }
        if (!empty($input['filter_name']) && $input['filter_nametype'] != 'none') {
            $input['filter_' . $input['filter_nametype']] = $input['filter_name'];

            //set null if needed
            if ($input['filter_nametype'] == 'iname') {
                $input['filter_name'] = null;
            } else {
                $input['filter_iname'] = null;
            }
        } else {
            //if 'none' , name and iname need to be null
            $input['filter_iname'] = null;
            $input['filter_name'] = null;
        }
        if (!empty($input['type']) && $input['type'] == 'file') {
            $input['filter_is_file'] = 1;
            $input['filter_is_dir'] = 0;
        } elseif (!empty($input['type'])) {
            $input['filter_is_file'] = 0;
            $input['filter_is_dir'] = 1;
        }

        return parent::prepareInputForAdd($input);
    }
}
