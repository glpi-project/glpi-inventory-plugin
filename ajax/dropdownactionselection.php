<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * @basedon   FusionInventory for GLPI
 * @copyright 2021-2026 Teclib' and contributors.
 * @copyright 2010-2021 by the FusionInventory Development Team.
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

if (plugin_glpiinventory_script_endswith("dropdownactionselection.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

global $CFG_GLPI;

Session::checkCentralAccess();

echo "<span hidden data-glpiinventory-selection='action'"
    . " data-glpiinventory-obj='" . htmlescape((string) filter_input(INPUT_POST, "actionselectadd")) . "'"
    . " data-glpiinventory-type='" . htmlescape((string) filter_input(INPUT_POST, "actiontypeid")) . "'"
    . " data-glpiinventory-root-doc='" . htmlescape($CFG_GLPI['root_doc']) . "'></span>";
echo Html::script('/plugins/glpiinventory/js/dropdown_selection.js', ['version' => PLUGIN_GLPIINVENTORY_VERSION], false);
