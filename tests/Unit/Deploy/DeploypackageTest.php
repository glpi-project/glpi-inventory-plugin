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

class DeploypackageTest extends TestCase {


   /**
    * @test
    */
   public function testGetTypeName() {
      $this->assertEquals('Package', PluginFusioninventoryDeployPackage::getTypeName());
      $this->assertEquals('Package', PluginFusioninventoryDeployPackage::getTypeName(1));
      $this->assertEquals('Package', PluginFusioninventoryDeployPackage::getTypeName(3));
   }


   /**
    * @test
    */
   public function testIsDeployEnabled() {

      $computer = new Computer();
      $pfAgent  = new PluginFusioninventoryAgent();
      $module   = new PluginFusioninventoryAgentmodule();
      $package  = new PluginFusioninventoryDeployPackage();

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
