<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * Copyright (C) 2021 Teclib' and contributors.
 *
 * http://glpi-project.org
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

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (!empty($_POST['type']) && isset($_POST['items_id']) && ($_POST['items_id'] > 0)) {
    $prefix = '';
    $suffix = '';
    if (!empty($_POST['prefix'])) {
        $prefix = $_POST['prefix'] . '[';
        $suffix = ']';
    }

    switch ($_POST['type']) {
        case Group::class:
        case Profile::class:
            $params = ['value' => $_SESSION['glpiactive_entity'],
                'name'  => $prefix . 'entities_id' . $suffix,
            ];
            echo "<table class='tab_format'><tr><td>";
            echo htmlescape(Entity::getTypeName(1));
            echo "</td><td>";
            Entity::dropdown($params);
            echo "</td><td>";
            echo __s('Child entities');
            echo "</td><td>";
            Dropdown::showYesNo($prefix . 'is_recursive' . $suffix);
            echo "</td></tr></table>";
            break;
    }
}
