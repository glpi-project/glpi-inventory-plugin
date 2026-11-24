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

// Runs when the file is loaded, right after the grid markup it binds to.
(function () {
   document.querySelectorAll('.timeslot-grid .timeslot-cell').forEach(function (cell) {
      cell.addEventListener('click', function () {
         var day_select = document.querySelector('select[name="beginday"]');
         if (day_select) {
            $(day_select).val(this.dataset.day).trigger('change');
         }
         var hour_select = document.querySelector('select[name="beginhours"]');
         if (hour_select) {
            $(hour_select).val(this.dataset.hour).trigger('change');
         }
      });
   });
})();
