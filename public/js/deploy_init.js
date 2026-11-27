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

// Each marker is consumed once: removing it makes a second load of this file a no-op.
document.querySelectorAll('[data-glpiinventory-redips]').forEach((marker) => {
   redipsInit('deploypackage', marker.dataset.glpiinventoryRedips, marker.dataset.glpiinventoryPackagesId);
   marker.remove();
});

document.querySelectorAll('[data-glpiinventory-server-tree]').forEach((marker) => {
   const rand = marker.dataset.glpiinventoryServerTree;

   window['Tree_Category_Loader' + rand] = new Ext.tree.TreeLoader({
      dataUrl: marker.dataset.glpiinventoryDataUrl
   });

   window['Tree_Category' + rand] = new Ext.tree.TreePanel({
      collapsible      : false,
      animCollapse     : false,
      border           : false,
      id               : 'tree_projectcategory' + rand,
      el               : 'tree_projectcategory' + rand,
      autoScroll       : true,
      animate          : false,
      enableDD         : true,
      containerScroll  : true,
      height           : 320,
      width            : 670,
      loader           : window['Tree_Category_Loader' + rand],
      rootVisible      : false,
      listeners: {
         click: function(node, event) {
            if (node.leaf == true) {
               Ext.get('server_filename' + rand).dom.value = node.id;
               window['fileModal' + rand].hide();
            }
         }
      }
   });

   // SET the root node.
   window['Tree_Category_Root' + rand] = new Ext.tree.AsyncTreeNode({
      text     : '',
      draggable   : false,
      id    : '-1'                  // this IS the id of the startnode
   });
   window['Tree_Category' + rand].setRootNode(window['Tree_Category_Root' + rand]);

   // Render the tree.
   window['Tree_Category' + rand].render();
   window['Tree_Category_Root' + rand].expand();

   marker.remove();
});
