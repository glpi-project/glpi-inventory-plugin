<?php

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

use PHPUnit\Framework\TestCase;

class CollectContentCommonTest extends TestCase
{
    public function testEachTableKeepsItsOwnStart(): void
    {
        $request = ['start' => '40', 'definitions_id' => '12', 'starts' => ['15' => '20']];

        $this->assertSame(
            [12 => 40, 15 => 20, 18 => 0],
            PluginGlpiinventoryCollectContentCommon::getRequestedStarts($request, [12, 15, 18])
        );
    }

    public function testStartWithoutPagedTableIsIgnored(): void
    {
        $this->assertSame(
            [12 => 0, 15 => 0],
            PluginGlpiinventoryCollectContentCommon::getRequestedStarts(['start' => '40'], [12, 15])
        );
    }

    public function testInvalidStartsFallBackToZero(): void
    {
        $request = ['start' => '-20', 'definitions_id' => '12', 'starts' => 'not_an_array'];

        $this->assertSame(
            [12 => 0, 15 => 0],
            PluginGlpiinventoryCollectContentCommon::getRequestedStarts($request, [12, 15])
        );
    }

    public function testPagerParamsCarryOtherTablesStarts(): void
    {
        parse_str(
            ltrim(PluginGlpiinventoryCollectContentCommon::getPagerParams(12, [12 => 40, 15 => 20, 18 => 0]), '&'),
            $params
        );

        $this->assertEquals(['definitions_id' => '12', 'starts' => ['15' => '20', '18' => '0']], $params);
    }

    public function testPagerParamsRoundTrip(): void
    {
        $starts = [12 => 40, 15 => 20, 18 => 60];
        parse_str(ltrim(PluginGlpiinventoryCollectContentCommon::getPagerParams(15, $starts), '&'), $request);
        $request['start'] = '80';

        $this->assertSame(
            [12 => 40, 15 => 80, 18 => 60],
            PluginGlpiinventoryCollectContentCommon::getRequestedStarts($request, [12, 15, 18])
        );
    }
}
