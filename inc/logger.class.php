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
 * Manage extra debug in files.
 */
class PluginFusioninventoryLogger {


   /**
    * Log when extra-debug is activated
    *
    * @param string $file
    * @param string $message
    */
   static function logIfExtradebug($file, $message) {
      if (!PluginFusioninventoryConfig::isExtradebugActive()) {
         return;
      }
      Toolbox::logInFile($file, $message);
   }


   /**
    * log when extra-debug and debug mode is activated
    *
    * @param string $file
    * @param string $message
    */
   static function logIfExtradebugAndDebugMode($file, $message) {
      if ($_SESSION['glpi_use_mode'] != Session::DEBUG_MODE) {
         return;
      }
      self::logIfExtradebug($file, $message);
   }
}
