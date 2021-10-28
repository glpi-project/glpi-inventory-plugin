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
 * Manage db locks during inventory.
 */
class PluginGlpiinventoryDBLock {

   //Number of milliseconds to wait each time we check if the lock is set
   const MILLISECOND_LOCK_WAIT        = 100000;

   //Number of second to wait before forcing release of the lock
   const SECONDS_TO_RELEASE_LOCK      = 600;

   //Number of second that is supposed to be the maximum to send to an agent
   //an error message explaining that a lock is still in place
   const SECONDS_BEFORE_SENDING_ERROR = 5;

   /**
   * Put a lock to prevent import of software/software version
   * @since 9.2+2.0
   *
   * @param string $type type of lock ('software' or 'softwareversion')
   * @param string callback_method the name of the method to call when checking the lock
   * @return boolean the result of the lock
   */
   function setLock($type, $value = '1', $callback_method = false) {
      global $DB, $CFG_GLPI;

      $result = true;
      $table  = "glpi_plugin_glpiinventory_dblock".$type;
      if ($DB->tableExists($table)) {

         $start_time = date('U');
         //Set a lock for softwares to prevent new software import
         $CFG_GLPI["use_log_in_files"] = false;
         $query = $DB->buildInsert(
            $table, [
               'value' => $value
            ]
         );

         while (!$DB->query($query)) {
            usleep(self::MILLISECOND_LOCK_WAIT);
            if ($callback_method) {
               $result = $this->checkLockForAgents($start_time);
               if (!$result) {
                  break;
               }
            }
         }
         $CFG_GLPI["use_log_in_files"] = true;
      }
      return $result;
   }

   /**
   * Check if a lock error message should be sent an agent
   * @since 9.2+2.0
   *
   * @param string $start_time the time where we start trying to set the lock
   * @return boolean true if no error message should
   */
   function checkLockForAgents($start_time) {
      if ((date('U') - $start_time) > self::SECONDS_BEFORE_SENDING_ERROR) {
         $communication = new PluginGlpiinventoryCommunication();
         $communication->setMessage("<?xml version='1.0' encoding='UTF-8'?>
      <REPLY>
      <ERROR>ERROR: Timeout for DB lock based on name</ERROR>
      </REPLY>");
         $communication->sendMessage($_SESSION['plugin_glpiinventory_compressmode']);
         return false;
      }
      return true;
   }

   /**
   * Put a lock to prevent import of software/software version
   * @since 9.2+2.0
   *
   * @param string $type type of lock ('software' or 'softwareversion')
   * @param string $where the SQL where clause to use to release lock (default '')
   */
   function releaseLock($type, $where = []) {
      global $DB;

      if (empty($where)) {
         $where = ['value' => '1'];
      }
      $table = "glpi_plugin_glpiinventory_dblock".$type;
      if ($DB->tableExists($table)) {
         //Release the lock
         $DB->delete(
            $table,
            $where
         );
      }
   }

   /**
   * Clean all locks lasting more than 10 minutes
   * @since 9.2+2.0
   *
   */
   function releaseAllLocks() {
      $where = ['date' => ['<', new \QueryExpression(sprintf('CURRENT_TIMESTAMP() - INTERVAL %s second', self::SECONDS_TO_RELEASE_LOCK))]];
      $tables = [
         'inventorynames',
         'inventories',
         'softwares',
         'softwareversions'
      ];

      foreach ($tables as $table) {
         $this->releaseLock($table, $where);
      }
   }
}
