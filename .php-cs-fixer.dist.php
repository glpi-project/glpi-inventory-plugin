<?php


use PhpCsFixer\Finder;
use PhpCsFixer\Config;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

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
 * GLPI Inventory Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

$finder = (new Finder())
    ->in(__DIR__)
    ->exclude([
        '.git/',
        'tests/config/',
        'tests/files/',
        'vendor/',
    ])
;

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setCacheFile(sys_get_temp_dir() . '/php-cs-fixer.glpi-inventory-plugin.cache')
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP84Migration' => true,
        'no_unused_imports' => true,
        'heredoc_indentation' => false, // This rule is mandatory due to a bug in `xgettext`, see https://savannah.gnu.org/bugs/?func=detailitem&item_id=62158
    ])
    ->setFinder($finder)
;

