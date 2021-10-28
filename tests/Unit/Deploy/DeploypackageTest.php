<?php
/**
 *  * ---------------------------------------------------------------------
 *  * GLPI Inventory Plugin
 *  * Copyright (C) 2021 Teclib' and contributors.
 *  *
 *  * http://glpi-project.org
 *  *
 *  * based on FusionInventory for GLPI
 *  * Copyright (C) 2010-2021 by the FusionInventory Development Team.
 *  *
 *  * ---------------------------------------------------------------------
 *  *
 *  * LICENSE
 *  *
 *  * This file is part of GLPI Inventory Plugin.
 *  *
 *  * GLPI Inventory Plugin is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as published by
 *  * the Free Software Foundation, either version 3 of the License, or
 *  * (at your option) any later version.
 *  *
 *  * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  * GNU Affero General Public License for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 *  * ---------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

class DeploypackageTest extends TestCase {


   /**
    * @test
    */
   public function testGetTypeName() {
      $this->assertEquals('Package', PluginGlpiinventoryDeployPackage::getTypeName());
      $this->assertEquals('Package', PluginGlpiinventoryDeployPackage::getTypeName(1));
      $this->assertEquals('Package', PluginGlpiinventoryDeployPackage::getTypeName(3));
   }


   /**
    * @test
    */
   public function testIsDeployEnabled() {

      $computer = new Computer();
      $pfAgent  = new PluginGlpiinventoryAgent();
      $module   = new PluginGlpiinventoryAgentmodule();
      $package  = new PluginGlpiinventoryDeployPackage();

      //Enable deploy feature for all agents
      $module->getFromDBByCrit(['modulename' => 'DEPLOY']);
      $module->update(['id' => $module->fields['id'], 'is_active' => 1]);

      // Create a computer
      $input = [
          'entities_id' => 0,
          'name'        => 'computer1'
      ];
      $computers_id = $computer->add($input);

      $input = [
          'entities_id' => 0,
          'name'        => 'computer',
          'version'     => '{"INVENTORY":"v2.3.21"}',
          'device_id'   => 'portdavid',
          'useragent'   => 'FusionInventory-Agent_v2.3.21',
          'computers_id'=> $computers_id
      ];
      $pfAgent->add($input);

      $this->assertTrue($package->isDeployEnabled($computers_id));

      //Disable deploy feature for all agents
      $module->update(['id' => $module->fields['id'], 'is_active' => 0]);

      $this->assertFalse($package->isDeployEnabled($computers_id));
   }
}
