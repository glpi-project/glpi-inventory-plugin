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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GLPI Inventory Plugin. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

// Each marker is consumed once: removing it makes a second load of this file a no-op.
document.querySelectorAll('[data-glpiinventory-hide]').forEach((marker) => {
   Ext.get(marker.dataset.glpiinventoryHide).setDisplayed('none');
   marker.remove();
});

document.querySelectorAll('[data-glpiinventory-reload]').forEach((marker) => {
   const target = '#' + marker.dataset.glpiinventoryReload;
   try {
      $(target).find('.select2-hidden-accessible').select2('destroy');
   } catch (e) {}
   $(target).load(marker.dataset.glpiinventoryUrl, {
      taskjobs_id: marker.dataset.glpiinventoryTaskjobsId,
      typename: marker.dataset.glpiinventoryTypename
   });
   marker.remove();
});

document.querySelectorAll('[data-glpiinventory-register-method]').forEach((marker) => {
   taskjobs.register_update_method(marker.dataset.glpiinventoryRegisterMethod);
   marker.remove();
});
