<?php

namespace App\Services;

use App\Models\Stack;
use Illuminate\Database\Eloquent\Collection;

class StackService
{
    public function all(): Collection
    {
        return Stack::withCount('projects')->latest()->get();
    }

    public function create(array $data): Stack
    {
        return Stack::create($data);
    }

    public function templateExists(Stack $stack): bool
    {
        return is_dir(base_path($stack->template_path));
    }

    public function loadEnvTemplate(Stack $stack): array
    {
        $path = base_path($stack->template_path . '/.env.template');

        if (! file_exists($path)) {
            return [];
        }

        $vars = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            [$key] = explode('=', $line, 2);
            $vars[trim($key)] = '';
        }

        return $vars;
    }
}
