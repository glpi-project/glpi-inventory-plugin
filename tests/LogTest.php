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

class GLPIlogs extends TestCase
{

    public function testSQLlogs()
    {
        $filecontent = file_get_contents("../../files/_log/sql-errors.log");

        $this->assertEmpty($filecontent, 'sql-errors.log not empty: ' . $filecontent);
       // Reinitialize file
        file_put_contents("../../files/_log/sql-errors.log", '');
    }


    public function testPHPlogs()
    {
        $filecontent = file("../../files/_log/php-errors.log");
        $lines = [];
        foreach ($filecontent as $line) {
            if (
                !strstr($line, 'apc.')
                && !strstr($line, 'glpiphplog.DEBUG: Config::getCache()')
                && !strstr($line, 'Test logger')
            ) {
                $lines[] = $line;
            }
        }
        $this->assertEmpty(implode("", $lines), 'php-errors.log not empty: ' . implode("", $lines));
       // Reinitialize file
        file_put_contents("../../files/_log/php-errors.log", '');
    }
}
