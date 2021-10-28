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
 * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
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

use Glpi\Inventory\FilesToJSON;

/**
 * Used to get the name of PCIID, USBID and PCIID.
 */
class PluginGlpiinventoryInventoryExternalDB extends CommonDBTM {


   /**
    * Get manufacturer from pciid
    *
    * @global object $DB
    * @param string $pciid
    * @return array
    */
   static function getDataFromPCIID($pciid) {
      $pcivendor = new \PCIVendor();
      $exploded = explode(":", $pciid);

      //manufacturer
      $pci_manufacturer = $pcivendor->getManufacturer($exploded[0]);
      //product name
      $pci_product = $pcivendor->getProductName($exploded[0], $exploded[1]);

      if ($pci_manufacturer || $pcivendor) {
         return [
            'name' => $pci_product,
            'manufacturer' => $pci_manufacturer
         ];
      }

      return [];
   }


    /**
     * Get data from vendorid and productid USB
     *
     * @global object $DB
     * @param integer $vendorId
     * @param integer $productId
     * @return array
     */
   static function getDataFromUSBID($vendorId, $productId) {
      $usbvendor = new \USBVendor();

      //manufacturer
      $vendors_name = $usbvendor->getManufacturer($vendorId);
      //product name
      $devices_name = $usbvendor->getProductName($vendorId, $productId);

      return [$vendors_name, $devices_name];
   }


   /**
    * Get manufaturer linked to 6 first number of MAC address
    *
    * @global object $DB
    * @param string $mac
    * @return string
    */
   static function getManufacturerWithMAC($mac) {
      global $GLPI_CACHE;

      $exploded = explode(':', $mac);

      if (isset($exploded[2])) {
         if (!$GLPI_CACHE->has('glpiinventory_ouis')) {
            $jsonfile = new FilesToJSON();
            $ouis = json_decode(file_get_contents($jsonfile->getJsonFilePath('ouis')), true);
            $GLPI_CACHE->set('glpiinventory_ouis', $ouis);
         }
         $ouis = $ouis ?? $GLPI_CACHE->get('glpiinventory_ouis');

         $mac = sprintf('%s:%s:%s', $exploded[0], $exploded[1], $exploded[2]);
         return $ouis[strtoupper($mac)] ?? false;
      }

      return '';
   }
}
