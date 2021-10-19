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
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkCentralAccess();

$rand      = filter_input(INPUT_POST, "rand");
$mode      = filter_input(INPUT_POST, "mode");
$type      = filter_input(INPUT_POST, "type");
$classname = filter_input(INPUT_POST, "class");

if (empty($rand) && (empty($type))) {
   exit();
}
//Only process class that are related to software deployment
if (!class_exists($classname)
   || !in_array($classname,
               ['PluginGlpiinventoryDeployCheck',
                'PluginGlpiinventoryDeployFile',
                'PluginGlpiinventoryDeployAction',
                'PluginGlpiinventoryDeployUserinteraction'
               ])) {
   exit();
}
$class        = new $classname();
$request_data = [
    'packages_id' => filter_input(INPUT_POST, "packages_id"),
    'orders_id'   => filter_input(INPUT_POST, "orders_id"),
    'value'       => filter_input(INPUT_POST, "value")
];
$class->displayAjaxValues(null, $request_data, $rand, $mode);
