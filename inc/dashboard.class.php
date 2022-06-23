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
* Manage the checks before deploy a package.
*/
class PluginGlpiinventoryDashboard
{
    public static function nbItems(array $params): array
    {
        $default_params = [
         'label'                 => "",
         'itemtype'              => Agent::getType(),
         'icon'                  => Agent::getIcon(),
         'apply_filters'         => [],
        ];

        $params = array_merge($default_params, $params);

        $searchCriteria =  ['criteria' => [$params['apply_filters']],
         'reset'    => 'reset'];

        $searchWaiting = Search::getDatas(
            $params['itemtype'],
            $searchCriteria
        );

        $count = 0;
        if (isset($searchWaiting['data']['totalcount'])) {
            $count = $searchWaiting['data']['totalcount'];
        }

        $url =  $params['itemtype']::getSearchURL();
        $url .= '?' . Toolbox::append_params($searchCriteria);
        return [
         'number'     => $count,
         'url'        => $url,
         'label'      => $params['label'],
         'icon'       => $params['icon'],
         's_criteria' => $searchCriteria,
         'itemtype'   => $params['itemtype'],
        ];
    }
}
