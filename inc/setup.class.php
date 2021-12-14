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
class PluginGlpiinventorySetup
{
   /**
    * Uninstall process when uninstall the plugin
    *
    * @global object $DB
    * @return true
    */
    public static function uninstall()
    {
        global $DB;

        CronTask::Unregister('glpiinventory');
        PluginGlpiinventoryProfile::uninstallProfile();

        $pfSetup  = new PluginGlpiinventorySetup();
        $user     = new User();

        if (class_exists('PluginGlpiinventoryConfig')) {
            $inventory_config      = new PluginGlpiinventoryConfig();
            $users_id = $inventory_config->getValue('users_id');
            $user->delete(['id' => $users_id], 1);
        }

        if (file_exists(GLPI_PLUGIN_DOC_DIR . '/glpiinventory')) {
            $pfSetup->rrmdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory');
        }

        $result = $DB->query("SHOW TABLES;");
        while ($data = $DB->fetchArray($result)) {
            if (
                (strstr($data[0], "glpi_plugin_glpiinventory_"))
                 or (strstr($data[0], "glpi_plugin_fusinvsnmp_"))
                 or (strstr($data[0], "glpi_plugin_fusinvinventory_"))
                or (strstr($data[0], "glpi_dropdown_plugin_fusioninventory"))
                or (strstr($data[0], "glpi_plugin_tracker"))
                or (strstr($data[0], "glpi_dropdown_plugin_tracker"))
            ) {
                $query_delete = "DROP TABLE `" . $data[0] . "`;";
                $DB->query($query_delete) or die($DB->error());
            }
        }

        $DB->deleteOrDie(
            'glpi_displaypreferences',
            [
            'itemtype' => ['LIKE', 'PluginGlpiinventory%']
            ]
        );

       //Remove informations related to profiles from the session (to clean menu and breadcrumb)
        PluginGlpiinventoryProfile::removeRightsFromSession();
        return true;
    }


   /**
    * Remove a directory and sub-directory
    *
    * @param string $dir name of the directory
    */
    public function rrmdir($dir)
    {
        $pfSetup = new PluginGlpiinventorySetup();

        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        $pfSetup->rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }


   /**
    * Creation of user
    *
    * @return integer id of the user "Plugin GLPI Inventory"
    */
    public function createGlpiInventoryUser()
    {
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
