<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Manage the files to search in collect module.
 */
class PluginFusioninventoryCollect_File extends PluginFusioninventoryCollectCommon {

   public $type = 'file';

   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb = 0) {
      return _n('Found file', 'Found files', $nb, 'glpiinventory');
   }


   function getListHeaders() {
      return [
         __("Name"),
         __("Limit", "fusioninventory"),
         __("Folder", "fusioninventory"),
         __("Recursive", "fusioninventory"),
         __("Regex", "fusioninventory"),
         __("Size", "fusioninventory"),
         __("Checksum SHA512", "fusioninventory"),
         __("Checksum SHA2", "fusioninventory"),
         __("Name", "fusioninventory"),
         __("Iname", "fusioninventory"),
         __("Type", "fusioninventory"),
         __("Action")
      ];
   }

   function displayOneRow($row = []) {
      $filter = $type = '';
      if (!empty($row['filter_sizeequals'])) {
         $filter = '= '.$row['filter_sizeequals'];
      } else if (!empty($row['filter_sizegreater'])) {
         $filer = '> '.$row['filter_sizegreater'];
      } else if (!empty($row['filter_sizelower'])) {
         $filter = '< '.$row['filter_sizelower'];
      }
      if ($row['filter_is_file'] == 1) {
         $type = __('File', 'glpiinventory');
      } else {
         $type = __('Folder', 'glpiinventory');
      }

      return [
         $row['name'],
         $row['limit'],
         $row['dir'],
         $row['is_recursive'],
         $row['filter_regex'],
         $filter,
         $row['filter_checksumsha512'],
         $row['filter_checksumsha2'],
         $row['filter_name'],
         $row['filter_iname'],
         $type
      ];
   }

   function displayNewSpecificities() {
      echo "<td>".__('Limit', 'glpiinventory')."</td>";
      echo "<td>";
      Dropdown::showNumber('limit', [
                           'min'   => 1,
                           'max'   => 100,
                           'value' => 5
                           ]
      );
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='4'>";
      echo _n('Filter', 'Filters', 2, 'glpiinventory');
      echo "</th>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Base folder', 'glpiinventory');
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='dir' value='/' size='50' />";
      echo "</td>";
      echo "<td>";
      echo __('Folder recursive', 'glpiinventory');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('is_recursive', 1);
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Regex', 'glpiinventory');
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='filter_regex' value='' size='50' />";
      echo "</td>";
      echo "<td>";
      echo __('Size', 'glpiinventory');
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray('sizetype', [
          'none'    => __('Disabled', 'glpiinventory'),
          'equals'  => '=',
          'greater' => '>',
          'lower'   => '<'
         ]
      );
      echo "<input type='text' name='size' value='' />";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Checksum SHA512', 'glpiinventory');
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='filter_checksumsha512' value='' />";
      echo "</td>";
      echo "<td>";
      echo __('Checksum SHA2', 'glpiinventory');
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='filter_checksumsha2' value='' />";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Filename', 'glpiinventory');
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray('filter_nametype', [
          'none'  => __('Disabled', 'glpiinventory'),
          'name'  => __('Non sentitive case', 'glpiinventory'),
          'iname' => __('Sentitive case', 'glpiinventory')
         ]
      );
      echo "<input type='text' name='filter_name' value='' />";
      echo "</td>";
      echo "<td>";
      echo __('Type', 'glpiinventory');
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray('type', [
            'file' => __('File', 'glpiinventory'),
            'dir'  => __('Folder', 'glpiinventory')
         ]
      );
      echo "</td>";
   }


   /**
    * After purge item, delete collect files
    */
   function post_purgeItem() {
      // Delete all File
      $pfCollectFileContent = new PluginFusioninventoryCollect_File_Content();
      $items = $pfCollectFileContent->find(['plugin_fusioninventory_collects_files_id' => $this->fields['id']]);
      foreach ($items as $item) {
         $pfCollectFileContent->delete(['id' => $item['id']], true);
      }
      parent::post_deleteItem();
   }
}

