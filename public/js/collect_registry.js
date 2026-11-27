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

$(function () {
    // Registry collect mode values (see PluginGlpiinventoryCollect_Registry):
    //   0 = MODE_DEFAULT, 1 = MODE_PATH_EXISTS, 2 = MODE_KEY_DEFINED, 3 = MODE_DEPTH
    document.querySelectorAll('[data-glpiinventory-registry]').forEach((marker) => {
        const rand = marker.dataset.glpiinventoryRegistry;
        marker.remove();

        const keyField    = $('#collect_registry_key_' + rand);
        const depthField  = $('#collect_registry_depth_' + rand);
        const modeSelect  = keyField.closest('form').find('select[name="mode"]');

        const toggleRegistryFields = function () {
            const mode = parseInt(modeSelect.val(), 10) || 0;
            // The key is useless when checking the path existence or reading recursively
            keyField.toggle(mode !== 1 && mode !== 3);
            // The recursion depth field only applies to the "All values" mode
            depthField.toggle(mode === 3);
        };

        modeSelect.on('change', toggleRegistryFields);
        toggleRegistryFields();
    });
});
