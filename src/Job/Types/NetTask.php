<?php


/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * Copyright (C) 2021 Teclib' and contributors.
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

declare(strict_types=1);

namespace GlpiPlugin\Glpiinventory\Job\Types;

use GlpiPlugin\Glpiinventory\Enums\NetTaskTypes;
use GlpiPlugin\Glpiinventory\Enums\TaskJobLogsTypes;

class NetTask extends Generic
{
    public function __construct(
        private readonly NetTaskTypes $task_type,
        private readonly int $threads,
        private readonly int $timeout
    ) {
        parent::__construct(TaskJobLogsTypes::NET_TASK);
    }

    public function getTaskType(): NetTaskTypes
    {
        return $this->task_type;
    }

    public function getThreads(): int
    {
        return $this->threads;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
