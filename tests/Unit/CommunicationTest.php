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

class CommunicationTest extends TestCase {

   private $output = '<?xml version="1.0"?>
<foo>
   <bar/>
</foo>
';

   public static function tearDownAfterClass(): void {
      // ob_end_clean();
   }


   /**
    * @test
    */
   public function testNew() {
      $communication = new PluginGlpiinventoryCommunication();
      $this->assertInstanceOf(
         'PluginGlpiinventoryCommunication', $communication
      );
      return $communication;
   }


   /**
    * @test
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
   public function testGetMessage() {
      $communication = new PluginGlpiinventoryCommunication();
      $communication->setMessage('<foo><bar/></foo>');
      $message = $communication->getMessage();
      $this->assertInstanceOf('SimpleXMLElement', $message);
      $this->assertXMLStringEqualsXMLString('<foo><bar/></foo>', $message->asXML());
   }


   /**
    * @test
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
   public function testSendMessage() {
      $communication = new PluginGlpiinventoryCommunication();
      $communication->setMessage('<foo><bar/></foo>');

      $this->expectOutputString($this->output);
      $communication->sendMessage();
      $this->assertContains(
         'Content-Type: application/xml', xdebug_get_headers()
      );
   }


   /**
    * @test
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
   public function testSendMessageNoCompression() {
      $communication = new PluginGlpiinventoryCommunication();
      $communication->setMessage('<foo><bar/></foo>');

      $this->expectOutputString($this->output);
      $communication->sendMessage('none');
      $this->assertContains(
         'Content-Type: application/xml', xdebug_get_headers()
      );
   }


   /**
    * @test
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
   public function testSendMessageZlibCompression() {
      $communication = new PluginGlpiinventoryCommunication();
      $communication->setMessage('<foo><bar/></foo>');

      $this->expectOutputString(gzcompress($this->output));
      $communication->sendMessage('zlib');
      $this->assertContains(
         'Content-Type: application/x-compress-zlib', xdebug_get_headers()
      );
   }


   /**
    * @test
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
   public function testSendMessageDeflate() {
      $communication = new PluginGlpiinventoryCommunication();
      $communication->setMessage('<foo><bar/></foo>');

      $this->expectOutputString(gzdeflate($this->output));
      $communication->sendMessage('deflate');
      $this->assertContains(
         'Content-Type: application/x-compress-deflate', xdebug_get_headers()
      );
   }


   /**
    * @test
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
   public function testSendMessageGzipCompression() {
      $communication = new PluginGlpiinventoryCommunication();
      $communication->setMessage('<foo><bar/></foo>');
      $this->expectOutputString(gzencode($this->output));
      $communication->sendMessage('gzip');
      $this->assertContains(
         'Content-Type: application/x-compress-gzip', xdebug_get_headers()
      );
   }
}
