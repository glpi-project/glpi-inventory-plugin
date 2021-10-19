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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Manage the mapping of network equipment and printer.
 */
class PluginGlpiinventoryMapping extends CommonDBTM {


   /**
    * Get mapping
    *
    * @param string $p_itemtype Mapping itemtype
    * @param string $p_name Mapping name
    * @return array|false mapping fields or FALSE
    */
   function get($p_itemtype, $p_name) {
      $data = $this->find(['itemtype' => $p_itemtype, 'name' => $p_name], [], 1);
      $mapping = current($data);
      if (isset($mapping['id'])) {
         return $mapping;
      }
      return false;
   }


   /**
    * Add new mapping
    *
    * @global object $DB
    * @param array $parm
    */
   function set($parm) {
      global $DB;

      $data = current(getAllDataFromTable("glpi_plugin_glpiinventory_mappings",
         ['itemtype' => $parm['itemtype'], 'name' => $parm['name']]));
      if (empty($data)) {
         // Insert
         $values = [
            'itemtype'     => $parm['itemtype'],
            'name'         => $parm['name'],
            'table'        => $parm['table'],
            'tablefield'   => $parm['tablefield'],
            'locale'       => $parm['locale']
         ];
         if (isset($parm['shortlocale'])) {
            $values['shortlocale'] = $parm['shortlocale'];
         }
         $DB->insert('glpi_plugin_glpiinventory_mappings', $values);
      } else if ($data['table'] != $parm['table']
                OR $data['tablefield'] != $parm['tablefield']
                OR $data['locale'] != $parm['locale']) {
         $data['table'] = $parm['table'];
         $data['tablefield'] = $parm['tablefield'];
         $data['locale'] = $parm['locale'];
         if (isset($parm['shortlocale'])) {
            $data['shortlocale'] = $parm['shortlocale'];
         }
         $this->update($data);
      }
   }


   /**
    * Get translation name of mapping
    *
    * @param array $mapping
    * @return string
    */
   function getTranslation ($mapping) {

      switch ($mapping['locale']) {

         case 1:
            return __('networking > location', 'glpiinventory');

         case 2:
            return __('networking > firmware', 'glpiinventory');

         case 3:
            return __('networking > uptime', 'glpiinventory');

         case 4:
            return __('networking > port > mtu', 'glpiinventory');

         case 5:
            return __('networking > port > speed', 'glpiinventory');

         case 6:
            return __('networking > port > internal status', 'glpiinventory');

         case 7:
            return __('networking > ports > last change', 'glpiinventory');

         case 8:
            return __('networking > port > number of bytes entered', 'glpiinventory');

         case 9:
            return __('networking > port > number of bytes out', 'glpiinventory');

         case 10:
            return __('networking > port > number of input errors', 'glpiinventory');

         case 11:
            return __('networking > port > number of errors output', 'glpiinventory');

         case 12:
            return __('networking > CPU usage', 'glpiinventory');

         case 13:
            return __('networking > serial number', 'glpiinventory');

         case 14:
            return __('networking > port > connection status', 'glpiinventory');

         case 15:
            return __('networking > port > MAC address', 'glpiinventory');

         case 16:
            return __('networking > port > name', 'glpiinventory');

         case 17:
            return __('networking > model', 'glpiinventory');

         case 18:
            return __('networking > port > type', 'glpiinventory');

         case 19:
            return __('networking > VLAN', 'glpiinventory');

         case 20:
            return __('networking > name', 'glpiinventory');

         case 21:
            return __('networking > total memory', 'glpiinventory');

         case 22:
            return __('networking > free memory', 'glpiinventory');

         case 23:
            return __('networking > port > port description', 'glpiinventory');

         case 24:
            return __('printer > name', 'glpiinventory');

         case 25:
            return __('printer > model', 'glpiinventory');

         case 26:
            return __('printer > total memory', 'glpiinventory');

         case 27:
            return __('printer > serial number', 'glpiinventory');

         case 28:
            return __('printer > meter > total number of printed pages', 'glpiinventory');

         case 29:
            return __('printer > meter > number of printed black and white pages', 'glpiinventory');

         case 30:
            return __('printer > meter > number of printed color pages', 'glpiinventory');

         case 31:
            return __('printer > meter > number of printed monochrome pages', 'glpiinventory');

         case 33:
            return __('networking > port > duplex type', 'glpiinventory');

         case 34:
            return __('printer > consumables > black cartridge (%)', 'glpiinventory');

         case 35:
            return __('printer > consumables > photo black cartridge (%)', 'glpiinventory');

         case 36:
            return __('printer > consumables > cyan cartridge (%)', 'glpiinventory');

         case 37:
            return __('printer > consumables > yellow cartridge (%)', 'glpiinventory');

         case 38:
            return __('printer > consumables > magenta cartridge (%)', 'glpiinventory');

         case 39:
            return __('printer > consumables > light cyan cartridge (%)', 'glpiinventory');

         case 40:
            return __('printer > consumables > light magenta cartridge (%)', 'glpiinventory');

         case 41:
            return __('printer > consumables > photoconductor (%)', 'glpiinventory');

         case 42:
            return __('printer > consumables > black photoconductor (%)', 'glpiinventory');

         case 43:
            return __('printer > consumables > color photoconductor (%)', 'glpiinventory');

         case 44:
            return __('printer > consumables > cyan photoconductor (%)', 'glpiinventory');

         case 45:
            return __('printer > consumables > yellow photoconductor (%)', 'glpiinventory');

         case 46:
            return __('printer > consumables > magenta photoconductor (%)', 'glpiinventory');

         case 47:
            return __('printer > consumables > black transfer unit (%)', 'glpiinventory');

         case 48:
            return __('printer > consumables > cyan transfer unit (%)', 'glpiinventory');

         case 49:
            return __('printer > consumables > yellow transfer unit (%)', 'glpiinventory');

         case 50:
            return __('printer > consumables > magenta transfer unit (%)', 'glpiinventory');

         case 51:
            return __('printer > consumables > waste bin (%)', 'glpiinventory');

         case 52:
            return __('printer > consumables > four (%)', 'glpiinventory');

         case 53:
            return __('printer > consumables > cleaning module (%)', 'glpiinventory');

         case 54:
            return __('printer > meter > number of printed duplex pages', 'glpiinventory');

         case 55:
            return __('printer > meter > nomber of scanned pages', 'glpiinventory');

         case 56:
            return __('printer > location', 'glpiinventory');

         case 57:
            return __('printer > port > name', 'glpiinventory');

         case 58:
            return __('printer > port > MAC address', 'glpiinventory');

         case 59:
            return __('printer > consumables > black cartridge (max ink)', 'glpiinventory');

         case 60:
            return __('printer > consumables > black cartridge (remaining ink )', 'glpiinventory');

         case 61:
            return __('printer > consumables > cyan cartridge (max ink)', 'glpiinventory');

         case 62:
            return __('printer > consumables > cyan cartridge (remaining ink)', 'glpiinventory');

         case 63:
            return __('printer > consumables > yellow cartridge (max ink)', 'glpiinventory');

         case 64:
            return __('printer > consumables > yellow cartridge (remaining ink)', 'glpiinventory');

         case 65:
            return __('printer > consumables > magenta cartridge (max ink)', 'glpiinventory');

         case 66:
            return __('printer > consumables > magenta cartridge (remaining ink)', 'glpiinventory');

         case 67:
            return __('printer > consumables > light cyan cartridge (max ink)', 'glpiinventory');

         case 68:
          return __('printer > consumables > light cyan cartridge (remaining ink)', 'glpiinventory');

         case 69:
            return __('printer > consumables > light magenta cartridge (max ink)', 'glpiinventory');

         case 70:
            return __('printer > consumables > light magenta cartridge (remaining ink)', 'glpiinventory');

         case 71:
            return __('printer > consumables > photoconductor (max ink)', 'glpiinventory');

         case 72:
            return __('printer > consumables > photoconductor (remaining ink)', 'glpiinventory');

         case 73:
            return __('printer > consumables > black photoconductor (max ink)', 'glpiinventory');

         case 74:
          return __('printer > consumables > black photoconductor (remaining ink)', 'glpiinventory');

         case 75:
            return __('printer > consumables > color photoconductor (max ink)', 'glpiinventory');

         case 76:
          return __('printer > consumables > color photoconductor (remaining ink)', 'glpiinventory');

         case 77:
            return __('printer > consumables > cyan photoconductor (max ink)', 'glpiinventory');

         case 78:
           return __('printer > consumables > cyan photoconductor (remaining ink)', 'glpiinventory');

         case 79:
            return __('printer > consumables > yellow photoconductor (max ink)', 'glpiinventory');

         case 80:
         return __('printer > consumables > yellow photoconductor (remaining ink)', 'glpiinventory');

         case 81:
            return __('printer > consumables > magenta photoconductor (max ink)', 'glpiinventory');

         case 82:
            return __('printer > consumables > magenta photoconductor (remaining ink)', 'glpiinventory');

         case 83:
            return __('printer > consumables > black transfer unit (max ink)', 'glpiinventory');

         case 84:
           return __('printer > consumables > black transfer unit (remaining ink)', 'glpiinventory');

         case 85:
            return __('printer > consumables > cyan transfer unit (max ink)', 'glpiinventory');

         case 86:
            return __('printer > consumables > cyan transfer unit (remaining ink)', 'glpiinventory');

         case 87:
            return __('printer > consumables > yellow transfer unit (max ink)', 'glpiinventory');

         case 88:
          return __('printer > consumables > yellow transfer unit (remaining ink)', 'glpiinventory');

         case 89:
            return __('printer > consumables > magenta transfer unit (max ink)', 'glpiinventory');

         case 90:
         return __('printer > consumables > magenta transfer unit (remaining ink)', 'glpiinventory');

         case 91:
            return __('printer > consumables > waste bin (max ink)', 'glpiinventory');

         case 92:
            return __('printer > consumables > waste bin (remaining ink)', 'glpiinventory');

         case 93:
            return __('printer > consumables > four (max ink)', 'glpiinventory');

         case 94:
            return __('printer > consumables > four (remaining ink)', 'glpiinventory');

         case 95:
            return __('printer > consumables > cleaning module (max ink)', 'glpiinventory');

         case 96:
            return __('printer > consumables > cleaning module (remaining ink)', 'glpiinventory');

         case 97:
            return __('printer > port > type', 'glpiinventory');

         case 98:
            return __('printer > consumables > maintenance kit (max)', 'glpiinventory');

         case 99:
            return __('printer > consumables > maintenance kit (remaining)', 'glpiinventory');

         case 400:
            return __('printer > consumables > maintenance kit (%)', 'glpiinventory');

         case 401:
            return __('networking > CPU user', 'glpiinventory');

         case 402:
            return __('networking > CPU system', 'glpiinventory');

         case 403:
            return __('networking > contact', 'glpiinventory');

         case 404:
            return __('networking > comments', 'glpiinventory');

         case 405:
            return __('printer > contact', 'glpiinventory');

         case 406:
            return __('printer > comments', 'glpiinventory');

         case 407:
            return __('printer > port > IP address', 'glpiinventory');

         case 408:
            return __('networking > port > index number', 'glpiinventory');

         case 409:
            return __('networking > Address CDP', 'glpiinventory');

         case 410:
            return __('networking > Port CDP', 'glpiinventory');

         case 411:
            return __('networking > port > trunk/tagged', 'glpiinventory');

         case 412:
            return __('networking > MAC address filters (dot1dTpFdbAddress)', 'glpiinventory');

         case 413:
            return __('networking > Physical addresses in memory (ipNetToMediaPhysAddress)', 'glpiinventory');

         case 414:
            return __('networking > instances de ports (dot1dTpFdbPort)', 'glpiinventory');

         case 415:
            return __('networking > numÃ©ro de ports associÃ© id du port (dot1dBasePortIfIndex)');

         case 416:
            return __('printer > port > index number', 'glpiinventory');

         case 417:
            return __('networking > MAC address', 'glpiinventory');

         case 418:
            return __('printer > Inventory number', 'glpiinventory');

         case 419:
            return __('networking > Inventory number', 'glpiinventory');

         case 420:
            return __('printer > manufacturer', 'glpiinventory');

         case 421:
            return __('networking > IP addresses', 'glpiinventory');

         case 422:
            return __('networking > PVID (port VLAN ID)', 'glpiinventory');

         case 423:
            return __('printer > meter > total number of printed pages (print)', 'glpiinventory');

         case 424:
            return __('printer > meter > number of printed black and white pages (print)', 'glpiinventory');

         case 425:
            return __('printer > meter > number of printed color pages (print)', 'glpiinventory');

         case 426:
            return __('printer > meter > total number of printed pages (copy)', 'glpiinventory');

         case 427:
            return __('printer > meter > number of printed black and white pages (copy)', 'glpiinventory');

         case 428:
            return __('printer > meter > number of printed color pages (copy)', 'glpiinventory');

         case 429:
            return __('printer > meter > total number of printed pages (fax)', 'glpiinventory');

         case 430:
            return __('networking > port > vlan', 'glpiinventory');

         case 435:
            return __('networking > CDP remote sysdescr', 'glpiinventory');

         case 436:
            return __('networking > CDP remote id', 'glpiinventory');

         case 437:
            return __('networking > CDP remote model device', 'glpiinventory');

         case 438:
            return __('networking > LLDP remote sysdescr', 'glpiinventory');

         case 439:
            return __('networking > LLDP remote id', 'glpiinventory');

         case 440:
            return __('networking > LLDP remote port description', 'glpiinventory');

         case 104:
            return __('MTU', 'glpiinventory');

         case 105:
            return __('Speed');

         case 106:
            return __('Internal status', 'glpiinventory');

         case 107:
            return __('Last Change', 'glpiinventory');

         case 108:
            return __('Number of received bytes', 'glpiinventory');

         case 109:
            return __('Number of outgoing bytes', 'glpiinventory');

         case 110:
            return __('Number of input errors', 'glpiinventory');

         case 111:
            return __('Number of output errors', 'glpiinventory');

         case 112:
            return __('CPU usage', 'glpiinventory');

         case 114:
            return __('Connection');

         case 115:
            return __('Internal MAC address', 'glpiinventory');

         case 116:
            return __('Name');

         case 117:
            return __('Model');

         case 118:
            return __('Type');

         case 119:
            return __('VLAN');

         case 120:
            return __('Alias', 'glpiinventory');

         case 128:
            return __('Total number of printed pages', 'glpiinventory');

         case 129:
            return __('Number of printed black and white pages', 'glpiinventory');

         case 130:
            return __('Number of printed color pages', 'glpiinventory');

         case 131:
            return __('Number of printed monochrome pages', 'glpiinventory');

         case 133:
            return __('Matte black cartridge', 'glpiinventory');

         case 134:
            return __('Black cartridge', 'glpiinventory');

         case 135:
            return __('Photo black cartridge', 'glpiinventory');

         case 136:
            return __('Cyan cartridge', 'glpiinventory');

         case 137:
            return __('Yellow cartridge', 'glpiinventory');

         case 138:
            return __('Magenta cartridge', 'glpiinventory');

         case 139:
            return __('Light cyan cartridge', 'glpiinventory');

         case 140:
            return __('Light magenta cartridge', 'glpiinventory');

         case 141:
            return __('Photoconductor', 'glpiinventory');

         case 142:
            return __('Black photoconductor', 'glpiinventory');

         case 143:
            return __('Color photoconductor', 'glpiinventory');

         case 144:
            return __('Cyan photoconductor', 'glpiinventory');

         case 145:
            return __('Yellow photoconductor', 'glpiinventory');

         case 146:
            return __('Magenta photoconductor', 'glpiinventory');

         case 147:
            return __('Black transfer unit', 'glpiinventory');

         case 148:
            return __('Cyan transfer unit', 'glpiinventory');

         case 149:
            return __('Yellow transfer unit', 'glpiinventory');

         case 150:
            return __('Magenta transfer unit', 'glpiinventory');

         case 151:
            return __('Waste bin', 'glpiinventory');

         case 152:
            return __('Four', 'glpiinventory');

         case 153:
            return __('Cleaning module', 'glpiinventory');

         case 154:
            return __('Number of pages printed duplex', 'glpiinventory');

         case 155:
            return __('Number of scanned pages', 'glpiinventory');

         case 156:
            return __('Maintenance kit', 'glpiinventory');

         case 157:
            return __('Black toner', 'glpiinventory');

         case 158:
            return __('Cyan toner', 'glpiinventory');

         case 159:
            return __('Magenta toner', 'glpiinventory');

         case 160:
            return __('Yellow toner', 'glpiinventory');

         case 161:
            return __('Black drum', 'glpiinventory');

         case 162:
            return __('Cyan drum', 'glpiinventory');

         case 163:
            return __('Magenta drum', 'glpiinventory');

         case 164:
            return __('Yellow drum', 'glpiinventory');

         case 165:
            return __('Many informations grouped', 'glpiinventory');

         case 166:
            return __('Black toner 2', 'glpiinventory');

         case 167:
            return __('Black toner Utilisé', 'glpiinventory');

         case 168:
            return __('Black toner Restant', 'glpiinventory');

         case 169:
            return __('Cyan toner Max', 'glpiinventory');

         case 170:
            return __('Cyan toner Utilisé', 'glpiinventory');

         case 171:
            return __('Cyan toner Restant', 'glpiinventory');

         case 172:
            return __('Magenta toner Max', 'glpiinventory');

         case 173:
            return __('Magenta toner Utilisé', 'glpiinventory');

         case 174:
            return __('Magenta toner Restant', 'glpiinventory');

         case 175:
            return __('Yellow toner Max', 'glpiinventory');

         case 176:
            return __('Yellow toner Utilisé', 'glpiinventory');

         case 177:
            return __('Yellow toner Restant', 'glpiinventory');

         case 178:
            return __('Black drum Max', 'glpiinventory');

         case 179:
            return __('Black drum Utilisé', 'glpiinventory');

         case 180:
            return __('Black drum Restant', 'glpiinventory');

         case 181:
            return __('Cyan drum Max', 'glpiinventory');

         case 182:
            return __('Cyan drum Utilisé', 'glpiinventory');

         case 183:
            return __('Cyan drumRestant', 'glpiinventory');

         case 184:
            return __('Magenta drum Max', 'glpiinventory');

         case 185:
            return __('Magenta drum Utilisé', 'glpiinventory');

         case 186:
            return __('Magenta drum Restant', 'glpiinventory');

         case 187:
            return __('Yellow drum Max', 'glpiinventory');

         case 188:
            return __('Yellow drum Utilisé', 'glpiinventory');

         case 189:
            return __('Yellow drum Restant', 'glpiinventory');

         case 190:
            return __('Waste bin Max', 'glpiinventory');

         case 191:
            return __('Waste bin Utilisé', 'glpiinventory');

         case 192:
            return __('Waste bin Restant', 'glpiinventory');

         case 193:
            return __('Maintenance kit Max', 'glpiinventory');

         case 194:
            return __('Maintenance kit Utilisé', 'glpiinventory');

         case 195:
            return __('Maintenance kit Restant', 'glpiinventory');

         case 196:
            return __('Grey ink cartridge', 'glpiinventory');

         case 197:
            return __('Paper roll in inches', 'glpiinventory');

         case 198:
            return __('Paper roll in centimeters', 'glpiinventory');

         case 199:
            return __('Transfer kit Max', 'glpiinventory');

         case 200:
            return __('Transfer kit used', 'glpiinventory');

         case 201:
            return __('Transfer kit remaining', 'glpiinventory');

         case 202:
            return __('Fuser kit', 'glpiinventory');

         case 203:
            return __('Fuser kit max', 'glpiinventory');

         case 204:
            return __('Fuser kit used', 'glpiinventory');

         case 205:
            return __('Fuser kit remaining', 'glpiinventory');

         case 206:
            return __('Gloss Enhancer ink cartridge', 'glpiinventory');

         case 207:
            return __('Blue ink cartridge', 'glpiinventory');

         case 208:
            return __('Green ink cartridge', 'glpiinventory');

         case 209:
            return __('Red ink cartridge', 'glpiinventory');

         case 210:
            return __('Chromatic Red ink cartridge', 'glpiinventory');

         case 211:
            return __('Light grey ink cartridge', 'glpiinventory');

         case 212:
            return __('Transfer kit', 'glpiinventory');

         case 1423:
            return __('Total number of printed pages (print)', 'glpiinventory');

         case 1424:
            return __('Number of printed black and white pages (print)', 'glpiinventory');

         case 1425:
            return __('Number of printed color pages (print)', 'glpiinventory');

         case 1426:
            return __('Total number of printed pages (copy)', 'glpiinventory');

         case 1427:
            return __('Number of printed black and white pages (copy)', 'glpiinventory');

         case 1428:
            return __('Number of printed color pages (copy)', 'glpiinventory');

         case 1429:
            return __('Total number of printed pages (fax)', 'glpiinventory');

         case 1434:
            return __('Total number of large printed pages', 'glpiinventory');

      }
      return $mapping['name'];
   }
}
