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

/**
 * Escape a dropdown label before it is written into innerHTML or an attribute.
 *
 * @param {string} text
 * @returns {string}
 */
function glpiinventory_escape(text) {
   return String(text).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
   });
}

/**
 * Add the item picked in the dropdowns to the task selection.
 *
 * @param {HTMLElement} marker  Carries the kind and the ids of the dropdowns.
 */
function glpiinventory_add_selection(marker) {
   const kinds = {
      action: { select: 'actionlist', list: 'actionselection', remove: 'glpiinventory-delaction' },
      definition: { select: 'definitionlist', list: 'definitionselection', remove: 'glpiinventory-deldef' }
   };
   const kind = kinds[marker.dataset.glpiinventorySelection];
   const select = document.getElementById(kind.select);
   const obj = document.getElementById(marker.dataset.glpiinventoryObj);
   const type = document.getElementById(marker.dataset.glpiinventoryType);
   const list = document.getElementById(kind.list).innerHTML;

   const type_text = type.options[type.selectedIndex].text;
   const type_value = type.options[type.selectedIndex].value;
   const obj_text = obj.options[obj.selectedIndex].text;
   const obj_value = obj.options[obj.selectedIndex].value;

   const pattern1 = new RegExp(type_value + '->' + obj_value + ',');
   const pattern2 = new RegExp(type_value + '->' + obj_value + '$');
   if (select.value.match(pattern1) || select.value.match(pattern2)) {
      return;
   }

   document.getElementById(kind.list).innerHTML = list + '<br>' + glpiinventory_escape(type_text) + ' -> ' + glpiinventory_escape(obj_text) +
      ' <img src="' + glpiinventory_escape(marker.dataset.glpiinventoryRootDoc) + '/pics/delete.png" class="' + kind.remove +
      '" data-glpiinventory-args="' + glpiinventory_escape(type_text + '->' + obj_text + '->' + type_value + '->' + obj_value) + '">';
   if (type.value !== '0') {
      select.value = select.value + ',' + type_value + '->' + obj_value;
   } else {
      select.value = select.value + ',' + obj_value;
   }
}

document.querySelectorAll('[data-glpiinventory-selection]').forEach((marker) => {
   glpiinventory_add_selection(marker);
   marker.remove();
});
