<?php

use App\Models\Project;
use Illuminate\Support\Facades\Validator;

// Regex from the Livewire #[Validate] rule on the create form
const SUBDOMAIN_REGEX = '/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/';

function validateSubdomain(string $subdomain, ?int $existingId = null): \Illuminate\Validation\Validator
{
    return Validator::make(
        ['subdomain' => $subdomain],
        ['subdomain' => [
            'nullable',
            'regex:' . SUBDOMAIN_REGEX,
            'max:63',
            \Illuminate\Validation\Rule::unique('projects', 'subdomain')->ignore($existingId),
        ]]
    );
}

// ----------------------------------------------------------------------------
// Format validation
// ----------------------------------------------------------------------------

test('subdomain passes with lowercase alphanumeric value', function () {
    expect(validateSubdomain('myapp')->passes())->toBeTrue();
});

test('subdomain passes with hyphens in the middle', function () {
    expect(validateSubdomain('my-cool-app')->passes())->toBeTrue();
});

test('subdomain passes with numbers', function () {
    expect(validateSubdomain('app2')->passes())->toBeTrue();
});

test('subdomain fails when it starts with a hyphen', function () {
    expect(validateSubdomain('-myapp')->fails())->toBeTrue();
});

test('subdomain fails when it ends with a hyphen', function () {
    expect(validateSubdomain('myapp-')->fails())->toBeTrue();
});

test('subdomain fails with uppercase letters', function () {
    expect(validateSubdomain('MyApp')->fails())->toBeTrue();
});

test('subdomain fails with spaces', function () {
    expect(validateSubdomain('my app')->fails())->toBeTrue();
});

test('subdomain fails with dots', function () {
    expect(validateSubdomain('my.app')->fails())->toBeTrue();
});

test('subdomain fails when longer than 63 characters', function () {
    expect(validateSubdomain(str_repeat('a', 64))->fails())->toBeTrue();
});

test('subdomain passes when exactly 63 characters', function () {
    expect(validateSubdomain(str_repeat('a', 63))->passes())->toBeTrue();
});

test('empty subdomain is treated as null and passes (optional field)', function () {
    $v = Validator::make(
        ['subdomain' => ''],
        ['subdomain' => 'nullable|regex:' . SUBDOMAIN_REGEX . '|max:63']
    );
    expect($v->passes())->toBeTrue();
});

// ----------------------------------------------------------------------------
// Uniqueness validation
// ----------------------------------------------------------------------------

test('subdomain fails when already taken by another project', function () {
    Project::factory()->withSubdomain('taken')->create();

    expect(validateSubdomain('taken')->fails())->toBeTrue();
});

test('subdomain passes when taken by the same project (update scenario)', function () {
    $project = Project::factory()->withSubdomain('myapp')->create();

    // Ignore the project's own id → should pass
    expect(validateSubdomain('myapp', $project->id)->passes())->toBeTrue();
});

test('subdomain passes when different from all existing ones', function () {
    Project::factory()->withSubdomain('existing')->create();

    expect(validateSubdomain('new-project')->passes())->toBeTrue();
});
