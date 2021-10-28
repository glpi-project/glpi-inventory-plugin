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
   die("Sorry. You can't access directly to this file");
}

/**
 * Manage the installation and uninstallation of the plugin.
 */
class PluginGlpiinventorySetup {


   /**
    * Uninstall process when uninstall the plugin
    *
    * @global object $DB
    * @return true
    */
   static function uninstall() {
      global $DB;

      CronTask::Unregister('glpiinventory');
      PluginGlpiinventoryProfile::uninstallProfile();

      $pfSetup  = new PluginGlpiinventorySetup();
      $user     = new User();

      if (class_exists('PluginGlpiinventoryConfig')) {
         $inventory_config      = new PluginGlpiinventoryConfig();
         $users_id = $inventory_config->getValue('users_id');
         $user->delete(['id'=>$users_id], 1);
      }

      if (file_exists(GLPI_PLUGIN_DOC_DIR.'/glpiinventory')) {
         $pfSetup->rrmdir(GLPI_PLUGIN_DOC_DIR.'/glpiinventory');
      }

      $result = $DB->query("SHOW TABLES;");
      while ($data = $DB->fetchArray($result)) {
         if ((strstr($data[0], "glpi_plugin_glpiinventory_"))
                 OR (strstr($data[0], "glpi_plugin_fusinvsnmp_"))
                 OR (strstr($data[0], "glpi_plugin_fusinvinventory_"))
                OR (strstr($data[0], "glpi_dropdown_plugin_fusioninventory"))
                OR (strstr($data[0], "glpi_plugin_tracker"))
                OR (strstr($data[0], "glpi_dropdown_plugin_tracker"))) {

            $query_delete = "DROP TABLE `".$data[0]."`;";
            $DB->query($query_delete) or die($DB->error());
         }
      }

      $DB->deleteOrDie(
         'glpi_displaypreferences', [
            'itemtype' => ['LIKE', 'PluginGlpiinventory%']
         ]
      );

      // Delete rules
      $Rule = new Rule();
      $Rule->deleteByCriteria(['sub_type' => 'PluginGlpiinventoryInventoryRuleImport']);

      //Remove informations related to profiles from the session (to clean menu and breadcrumb)
      PluginGlpiinventoryProfile::removeRightsFromSession();
      return true;
   }


   /**
    * Remove a directory and sub-directory
    *
    * @param string $dir name of the directory
    */
   function rrmdir($dir) {
      $pfSetup = new PluginGlpiinventorySetup();

      if (is_dir($dir)) {
         $objects = scandir($dir);
         foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
               if (filetype($dir."/".$object) == "dir") {
                  $pfSetup->rrmdir($dir."/".$object);
               } else {
                  unlink($dir."/".$object);
               }
            }
         }
         reset($objects);
         rmdir($dir);
      }
   }


   /**
    * Create rules (initialisation)
    *
    * @param integer $reset
    * @return boolean
    */
   function initRules($reset = 0, $onlyActive = false) {
      global $DB;

      if ($reset == 1) {
         $grule = new Rule();
         $a_rules = $grule->find(['sub_type' => 'PluginGlpiinventoryInventoryRuleImport']);
         foreach ($a_rules as $data) {
            $grule->delete($data);
         }
      }

      $rules = [];

      $rules[] = [
         'name'      => 'Device update (by mac+ifnumber restricted port)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 9,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ifnumber',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ifnumber',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'link_criteria_port',
               'condition' => 203,
               'pattern'   => 1
            ],
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Device update (by mac+ifnumber not restricted port)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 9,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ifnumber',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ifnumber',
               'condition' => 8,
               'pattern'   => 1
            ],
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Device update (by ip+ifdescr restricted port)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 9,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ip',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ip',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ifdescr',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ifdescr',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'link_criteria_port',
               'condition' => 203,
               'pattern'   => 1
            ],
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Device update (by ip+ifdescr not restricted port)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 9,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ip',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ip',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ifdescr',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ifdescr',
               'condition' => 8,
               'pattern'   => 1
            ],
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Device import (by mac+ifnumber)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 9,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ifnumber',
               'condition' => 8,
               'pattern'   => 1
            ],
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Device import (by ip+ifdescr)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 9,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ip',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'ifdescr',
               'condition' => 8,
               'pattern'   => 1
            ],
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Update only mac address (mac on switch port)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 9,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'only_these_criteria',
               'condition' => 204,
               'pattern'   => 1
            ],
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Import only mac address (mac on switch port)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 9,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'only_these_criteria',
               'condition' => 204,
               'pattern'   => 1
            ],
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Computer constraint (name)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Computer'
            ],
            [
               'criteria'  => 'name',
               'condition' => 9,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion2'
      ];

      $rules[] = [
         'name'      => 'Computer update (by serial + uuid)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Computer'
            ],
            [
               'criteria'  => 'serial',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'uuid',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'uuid',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];
      $rules[] = [
         'name'      => 'Computer update (by serial)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Computer'
            ],
            [
               'criteria'  => 'serial',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Computer update (by uuid)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Computer'
            ],
            [
               'criteria'  => 'uuid',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'uuid',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Computer update (by mac)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Computer'
            ],
            [
               'criteria'  => 'mac',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Computer update (by name)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Computer'
            ],
            [
               'criteria'  => 'name',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'name',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Computer import (by serial + uuid)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Computer'
            ],
            [
               'criteria'  => 'uuid',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Computer import (by serial)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Computer'
            ],
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Computer import (by uuid)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Computer'
            ],
            [
               'criteria'  => 'uuid',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Computer import (by mac)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Computer'
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Computer import (by name)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Computer'
            ],
            [
               'criteria'  => 'name',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Computer import denied',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Computer'
            ]
         ],
         'action'    => '_fusion2'
      ];

      $rules[] = [
         'name'      => 'Printer constraint (name)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Printer'
            ],
            [
               'criteria'  => 'name',
               'condition' => 9,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion2'
      ];

      $rules[] = [
         'name'      => 'Printer update (by serial)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Printer'
            ],
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'serial',
               'condition' => 10,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Printer update (by mac)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Printer'
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 10,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Printer import (by serial)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Printer'
            ],
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Printer import (by mac)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Printer'
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Printer import denied',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Printer'
            ]
         ],
         'action'    => '_fusion2'
      ];

      $rules[] = [
         'name'      => 'NetworkEquipment constraint (name)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'NetworkEquipment'
            ],
            [
               'criteria'  => 'name',
               'condition' => 9,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion2'
      ];

      $rules[] = [
         'name'      => 'NetworkEquipment update (by serial)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'NetworkEquipment'
            ],
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'serial',
               'condition' => 10,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'NetworkEquipment update (by mac)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'NetworkEquipment'
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 10,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'NetworkEquipment import (by serial)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'NetworkEquipment'
            ],
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'NetworkEquipment import (by mac)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'NetworkEquipment'
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'NetworkEquipment import denied',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'NetworkEquipment'
            ]
         ],
         'action'    => '_fusion2'
      ];

      $rules[] = [
         'name'      => 'Peripheral update (by serial)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Peripheral'
            ],
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'serial',
               'condition' => 10,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Peripheral import (by serial)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Peripheral'
            ],
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Peripheral import denied',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Peripheral'
            ]
         ],
         'action'    => '_fusion2'
      ];

      $rules[] = [
         'name'      => 'Monitor update (by serial)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Monitor'
            ],
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'serial',
               'condition' => 10,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Monitor import (by serial)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Monitor'
            ],
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Monitor import denied',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Monitor'
            ]
         ],
         'action'    => '_fusion2'
      ];

      $rules[] = [
         'name'      => 'Phone constraint (name)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Phone'
            ],
            [
               'criteria'  => 'name',
               'condition' => 9,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion2'
      ];

      $rules[] = [
         'name'      => 'Phone update (by mac)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Phone'
            ],
            [
               'criteria'  => 'mac',
               'condition' => 10,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Phone import (by mac)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Phone'
            ],
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Phone import denied',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => 'Phone'
            ]
         ],
         'action'    => '_fusion2'
      ];

      $rules[] = [
         'name'      => 'Global constraint (name)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'name',
               'condition' => 9,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion2'
      ];

      $rules[] = [
         'name'      => 'Global update (by serial)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'serial',
               'condition' => 10,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Global update (by mac)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ],
            [
               'criteria'  => 'mac',
               'condition' => 10,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Global import (by serial)',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'serial',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Global import (by mac)',
         'match'     => 'AND',
         'is_active' => 0,
         'criteria'  => [
            [
               'criteria'  => 'mac',
               'condition' => 8,
               'pattern'   => 1
            ]
         ],
         'action'    => '_fusion1'
      ];

      $rules[] = [
         'name'      => 'Global import denied',
         'match'     => 'AND',
         'is_active' => 1,
         'criteria'  => [
            [
               'criteria'  => 'itemtype',
               'condition' => 0,
               'pattern'   => ''
            ]
         ],
         'action'    => '_fusion2'
      ];

      $ranking = 0;
      foreach ($rules as $rule) {
         if ($onlyActive && $rule['is_active'] == 0) {
            continue;
         }

         $rulecollection = new PluginGlpiinventoryInventoryRuleImportCollection();
         $input = [];
         $input['is_active'] = $rule['is_active'];
         $input['name']      = $rule['name'];
         $input['match']     = $rule['match'];
         $input['sub_type']  = 'PluginGlpiinventoryInventoryRuleImport';
         $input['ranking']   = $ranking;
         $rule_id = $rulecollection->add($input);

         // Add criteria
         $rulefi = $rulecollection->getRuleClass();
         foreach ($rule['criteria'] as $criteria) {
            $rulecriteria = new RuleCriteria(get_class($rulefi));
            $criteria['rules_id'] = $rule_id;
            $rulecriteria->add($criteria);
         }

         // Add action
         $ruleaction = new RuleAction(get_class($rulefi));
         $input = [];
         $input['rules_id'] = $rule_id;
         $input['action_type'] = 'assign';
         if ($rule['action'] == '_fusion1') {
            $input['field'] = '_fusion';
            $input['value'] = '1';
         } else if ($rule['action'] == '_fusion2') {
            $input['field'] = '_fusion';
            $input['value'] = '2';
         } else if ($rule['action'] == '_ignore_import') {
            $input['field'] = '_ignore_import';
            $input['value'] = '1';
         }
         $ruleaction->add($input);

         $ranking++;
      }
      return true;
   }


   /**
    * Creation of user
    *
    * @return integer id of the user "Plugin GLPI Inventory"
    */
   function createGlpiInventoryUser() {
      $user = new User();
      $a_users = $user->find(['name' => 'Plugin_GLPI_Inventory']);
      if (count($a_users) == '0') {
         $input = [];
         $input['name'] = 'Plugin_GLPI_Inventory';
         $input['password'] = mt_rand(30, 39);
         $input['firstname'] = "Plugin GLPI Inventory";
         return $user->add($input);
      } else {
         $user = current($a_users);
         return $user['id'];
      }
   }
}
