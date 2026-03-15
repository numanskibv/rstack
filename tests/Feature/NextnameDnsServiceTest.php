<?php

use App\Models\Project;
use App\Models\Server;
use App\Services\NextnameDnsService;
use Illuminate\Support\Facades\Http;

// ----------------------------------------------------------------------------
// Helpers
// ----------------------------------------------------------------------------

/**
 * Enable Nextname with working config and return service instance.
 */
function nextnameDnsService(): NextnameDnsService
{
    config([
        'rstack.nextname.enabled' => true,
        'rstack.nextname.key'     => 'test-api-key',
        'rstack.nextname.url'     => 'https://api.nextname.nl/v2',
        'rstack.nextname.domain'  => 'rstack.nl',
        'rstack.nextname.ttl'     => 300,
    ]);

    return new NextnameDnsService;
}

// ----------------------------------------------------------------------------
// enabled()
// ----------------------------------------------------------------------------

test('enabled() returns false when nextname is disabled', function () {
    config([
        'rstack.nextname.enabled' => false,
        'rstack.nextname.key'     => 'some-key',
    ]);

    expect((new NextnameDnsService)->enabled())->toBeFalse();
});

test('enabled() returns false when api key is empty', function () {
    config([
        'rstack.nextname.enabled' => true,
        'rstack.nextname.key'     => '',
    ]);

    expect((new NextnameDnsService)->enabled())->toBeFalse();
});

test('enabled() returns true when properly configured', function () {
    config([
        'rstack.nextname.enabled' => true,
        'rstack.nextname.key'     => 'real-key',
    ]);

    expect((new NextnameDnsService)->enabled())->toBeTrue();
});

// ----------------------------------------------------------------------------
// register()
// ----------------------------------------------------------------------------

test('register() makes no HTTP calls when disabled', function () {
    Http::fake();
    config([
        'rstack.nextname.enabled' => false,
        'rstack.nextname.key'     => '',
    ]);

    $project = Project::factory()->withSubdomain('myapp')->create();

    (new NextnameDnsService)->register($project);

    Http::assertNothingSent();
});

test('register() makes no HTTP calls when project has no subdomain', function () {
    Http::fake();
    $project = Project::factory()->create(['subdomain' => null]);

    nextnameDnsService()->register($project);

    Http::assertNothingSent();
});

test('register() creates A record and sets dns_status to pending', function () {
    $server  = Server::factory()->create(['ip_address' => '1.2.3.4']);
    $project = Project::factory()->withSubdomain('myapp')->create([
        'server_id'  => $server->id,
        'dns_status' => null,
    ]);

    Http::fake([
        // GET records → empty list (no existing record)
        'https://api.nextname.nl/v2/domains/rstack.nl/records' => Http::response([], 200),
        // POST create record → success
        'https://api.nextname.nl/v2/domains/rstack.nl/records' => Http::response(['id' => 42, 'name' => 'myapp'], 201),
    ]);

    nextnameDnsService()->register($project);

    Http::assertSentCount(2);

    Http::assertSent(function ($request) use ($server) {
        return $request->method() === 'POST'
            && str_contains($request->url(), '/domains/rstack.nl/records')
            && $request->data()['type'] === 'A'
            && $request->data()['name'] === 'myapp'
            && $request->data()['content'] === '1.2.3.4';
    });

    expect($project->fresh()->dns_status)->toBe('pending');
});

test('register() skips POST when record with same IP already exists', function () {
    $server  = Server::factory()->create(['ip_address' => '1.2.3.4']);
    $project = Project::factory()->withSubdomain('myapp')->create(['server_id' => $server->id]);

    Http::fake([
        'https://api.nextname.nl/v2/domains/rstack.nl/records' => Http::response([
            ['id' => 5, 'type' => 'A', 'name' => 'myapp', 'content' => '1.2.3.4'],
        ], 200),
    ]);

    nextnameDnsService()->register($project);

    // Only the GET, no POST or DELETE
    Http::assertSentCount(1);
    Http::assertSent(fn($r) => $r->method() === 'GET');
});

test('register() replaces record when server IP changed', function () {
    $server  = Server::factory()->create(['ip_address' => '9.9.9.9']);
    $project = Project::factory()->withSubdomain('myapp')->create(['server_id' => $server->id]);

    Http::fake([
        // GET → stale record with old IP
        'https://api.nextname.nl/v2/domains/rstack.nl/records' => function ($request) {
            if ($request->method() === 'GET') {
                return Http::response([
                    ['id' => 7, 'type' => 'A', 'name' => 'myapp', 'content' => '1.2.3.4'],
                ], 200);
            }
            // POST new record
            return Http::response(['id' => 8], 201);
        },
        // DELETE stale record
        'https://api.nextname.nl/v2/domains/rstack.nl/records/7' => Http::response(null, 200),
    ]);

    nextnameDnsService()->register($project);

    Http::assertSent(fn($r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/records/7'));
    Http::assertSent(fn($r) => $r->method() === 'POST' && $r->data()['content'] === '9.9.9.9');

    expect($project->fresh()->dns_status)->toBe('pending');
});

test('register() throws RuntimeException when server IP is missing', function () {
    $server  = Server::factory()->create(['ip_address' => '']);
    $project = Project::factory()->withSubdomain('myapp')->create(['server_id' => $server->id]);

    Http::fake();

    expect(fn() => nextnameDnsService()->register($project))
        ->toThrow(\RuntimeException::class);
});

// ----------------------------------------------------------------------------
// remove()
// ----------------------------------------------------------------------------

test('remove() makes no HTTP calls when disabled', function () {
    Http::fake();
    config(['rstack.nextname.enabled' => false, 'rstack.nextname.key' => '']);

    $project = Project::factory()->withSubdomain('myapp')->create();

    (new NextnameDnsService)->remove($project);

    Http::assertNothingSent();
});

test('remove() makes no HTTP calls when project has no subdomain', function () {
    Http::fake();
    $project = Project::factory()->create(['subdomain' => null]);

    nextnameDnsService()->remove($project);

    Http::assertNothingSent();
});

test('remove() deletes existing record and clears dns_status', function () {
    $project = Project::factory()->withSubdomain('myapp', 'active')->create();

    Http::fake([
        'https://api.nextname.nl/v2/domains/rstack.nl/records'   => Http::response([
            ['id' => 12, 'type' => 'A', 'name' => 'myapp', 'content' => '1.2.3.4'],
        ], 200),
        'https://api.nextname.nl/v2/domains/rstack.nl/records/12' => Http::response(null, 200),
    ]);

    nextnameDnsService()->remove($project);

    Http::assertSent(fn($r) => $r->method() === 'DELETE');
    expect($project->fresh()->dns_status)->toBeNull();
});

test('remove() is no-op when no record exists at Nextname', function () {
    $project = Project::factory()->withSubdomain('myapp')->create();

    Http::fake([
        'https://api.nextname.nl/v2/domains/rstack.nl/records' => Http::response([], 200),
    ]);

    nextnameDnsService()->remove($project);

    Http::assertNotSent(fn($r) => $r->method() === 'DELETE');
    expect($project->fresh()->dns_status)->toBeNull();
});

// ----------------------------------------------------------------------------
// checkPropagation()
// ----------------------------------------------------------------------------

test('checkPropagation() returns false when project has no subdomain', function () {
    $project = Project::factory()->create(['subdomain' => null]);

    $result = nextnameDnsService()->checkPropagation($project);

    expect($result)->toBeFalse();
});

test('checkPropagation() sets dns_status to pending when subdomain does not resolve', function () {
    // Non-existent subdomain will not resolve in any environment – dns_get_record returns []
    $project = Project::factory()
        ->withSubdomain('thissubdomaindoesnotexist99999xrstack')
        ->for(Server::factory()->create(['ip_address' => '1.2.3.4']))
        ->create(['dns_status' => null]);

    $result = nextnameDnsService()->checkPropagation($project);

    expect($result)->toBeFalse();
    expect($project->fresh()->dns_status)->toBe('pending');
});

// ----------------------------------------------------------------------------
// fqdn()
// ----------------------------------------------------------------------------

test('fqdn() returns correct fully qualified domain name', function () {
    $project = Project::factory()->withSubdomain('myapp')->create();

    $fqdn = nextnameDnsService()->fqdn($project);

    expect($fqdn)->toBe('myapp.rstack.nl');
});
