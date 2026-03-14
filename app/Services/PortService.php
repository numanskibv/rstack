<?php

namespace App\Services;

use App\Models\Project;

class PortService
{
    public const START_PORT = 8001;

    public function allocateNext(): int
    {
        $highest = Project::max('port');

        return $highest ? $highest + 1 : self::START_PORT;
    }

    public function isAvailable(int $port): bool
    {
        return ! Project::where('port', $port)->exists();
    }
}
