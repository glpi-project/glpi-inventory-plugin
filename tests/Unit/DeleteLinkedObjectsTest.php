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

use PHPUnit\Framework\TestCase;

class DeleteLinkedObjectsTest extends TestCase
{
   /**
    * @test
    */
    public function IpRangeDeleteConfigSecurity()
    {

        $iprange = new PluginGlpiinventoryIPRange();
        $iprange_credentials = new PluginGlpiinventoryIPRange_SNMPCredential();

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
          'snmpcredentials_id' => 1,
          'rank' => 1
        ];
        $iprange_credentials->add($input);

        $list_security = $iprange_credentials->find();
        $this->assertEquals(1, count($list_security), "SNMP community not added to iprange");

        $iprange->delete(['id' => $ipranges_id]);

        $list_iprange = $iprange->find();
        $this->assertEquals(0, count($list_iprange), "IP Range not right deleted");

        $list_security = $iprange_credentials->find();
        $this->assertEquals(0, count($list_security), "SNMP community not deleted with iprange");
    }
}
