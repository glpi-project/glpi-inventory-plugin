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

ini_set("log_errors", true);

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
set_error_handler(null);

include_once(__DIR__ . "/../../../inc/toolbox.class.php");

class Logging {
   public static $LOG_CRITICAL = ['level'=>50, 'name'=>'CRITICAL '];
   public static $LOG_ERROR    = ['level'=>40, 'name'=>'ERROR    '];
   public static $LOG_QUIET    = ['level'=>35,  'name'=>'QUIET    '];
   public static $LOG_WARNING  = ['level'=>30, 'name'=>'WARNING  '];
   public static $LOG_INFO     = ['level'=>20, 'name'=>'INFO     '];
   public static $LOG_DEBUG    = ['level'=>10, 'name'=>'DEBUG    '];

   public $loglevel;


   public function __construct($loglevel = null) {

      if (is_null($loglevel)) {
         $this->loglevel = self::$LOG_INFO;
      } else {
         $this->loglevel = $loglevel;
      }
   }


   public function formatlog($messages, $loglevel) {
      $msg = [];
      foreach ($messages as $message) {
         if (is_array($message) || is_object($message)) {
            //$msg[] = print_r($message, true);
            $msg[] = PluginFusioninventoryToolbox::formatJson(json_encode($message));
         } else if (is_null($message)) {
            $msg[] = ' NULL';
         } else if (is_bool($message)) {
            $msg[] = ($message ? 'true' : 'false');
         } else {
            $msg[] = $message;
         }
      }
      return $loglevel['name'] . ': '. implode("\n", $msg);
   }


   function printlog($msg = "", $loglevel = null) {

      if (is_null($loglevel)) {
         $loglevel = self::$LOG_INFO;
      }

      /*
         print(
            var_export($this->loglevel['level'],true) . " >= " .
            var_export($loglevel['level'],true) . "\n"
         );
       */
      if ($this->loglevel['level'] <= $loglevel['level']) {
         print( $this->formatlog($msg, $loglevel) . PHP_EOL );
      }
   }


   function info() {
      $msg = func_get_args();
      $this->printlog($msg, self::$LOG_INFO);
   }


   function error() {
      $msg = func_get_args();
      $this->printlog($msg, self::$LOG_ERROR);
   }


   function debug() {
      $msg = func_get_args();
      $this->printlog($msg, self::$LOG_DEBUG);
   }


   function setLevelFromArgs($quiet = false, $debug = false) {
      $this->loglevel = self::$LOG_INFO;
      if ($quiet) {
         $this->loglevel = self::$LOG_QUIET;
      } else if ($debug) {
         $this->loglevel = self::$LOG_DEBUG;
      }
   }


}

