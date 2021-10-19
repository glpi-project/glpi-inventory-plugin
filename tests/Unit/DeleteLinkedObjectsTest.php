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

use PHPUnit\Framework\TestCase;

class DeleteLinkedObjectsTest extends TestCase {

   /**
    * @test
    */
   public function IpRangeDeleteConfigSecurity() {

      $iprange = new PluginGlpiinventoryIPRange();
      $iprange_ConfigSecurity = new PluginGlpiinventoryIPRange_ConfigSecurity();

      // Delete all IPRanges
      $items = $iprange->find();
      foreach ($items as $item) {
         $iprange->delete(['id' => $item['id']], true);
      }

      $input = [
          'name'        => 'Office',
          'ip_start'    => '192.168.0.1',
          'ip_end'      => '192.168.0.254',
          'entities_id' => 0
      ];
      $ipranges_id = $iprange->add($input);

      $list_iprange = $iprange->find();
      $this->assertEquals(1, count($list_iprange), "IP Range not right added");

      $input = [
          'plugin_glpiinventory_ipranges_id' => $ipranges_id,
          'plugin_glpiinventory_configsecurities_id' => 1,
          'rank' => 1
      ];
      $iprange_ConfigSecurity->add($input);

      $list_security = $iprange_ConfigSecurity->find();
      $this->assertEquals(1, count($list_security), "SNMP community not added to iprange");

      $iprange->delete(['id' => $ipranges_id]);

      $list_iprange = $iprange->find();
      $this->assertEquals(0, count($list_iprange), "IP Range not right deleted");

      $list_security = $iprange_ConfigSecurity->find();
      $this->assertEquals(0, count($list_security), "SNMP community not deleted with iprange");
   }
}
