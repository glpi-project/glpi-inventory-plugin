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

include ("../../../inc/includes.php");

Session::checkLoginUser();

if (isset($_GET["popup"])) {
   $_SESSION["glpipopup"]["name"] = $_GET["popup"];
}

if (isset($_SESSION["glpipopup"]["name"])) {
   switch ($_SESSION["glpipopup"]["name"]) {
      case "test_rule" :
         Html::popHeader(__('Test'), $_SERVER['PHP_SELF']);
         include "../../../front/rule.test.php";
         break;

      case "test_all_rules" :
         Html::popHeader(__('Test rules engine'), $_SERVER['PHP_SELF']);
         include "../../../front/rulesengine.test.php";
         break;

      case "show_cache" :
         Html::popHeader(__('Cache informations', 'glpiinventory'), $_SERVER['PHP_SELF']);
         include "../../../front/rule.cache.php";
         break;

      case "pluginfusioninventory_networkport_display_options" :
         Html::popHeader(__('Network ports display options', 'glpiinventory'), $_SERVER['PHP_SELF']);
         include "networkport.display.php";
         break;

   }
   echo "<div class='center'><br><a href='javascript:window.close()'>".__('Back')."</a>";
   echo "</div>";
   Html::popFooter();
}

