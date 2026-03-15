<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SSH Private Key Path
    |--------------------------------------------------------------------------
    |
    | Absolute path to the private key RStack uses when connecting to managed
    | servers. The corresponding public key must be present in the
    | ~/.ssh/authorized_keys file on each server.
    |
    */

    'ssh_key_path' => env('RSTACK_SSH_KEY_PATH', storage_path('app/ssh/id_rsa')),

    /*
    |--------------------------------------------------------------------------
    | Remote Project Root
    |--------------------------------------------------------------------------
    |
    | The base directory on remote servers where RStack stores project files.
    | Each project gets its own sub-directory: {root}/{project->slug}
    |
    */

    'remote_project_root' => env('RSTACK_REMOTE_PROJECT_ROOT', '/srv/rstack/projects'),

    /*
    |--------------------------------------------------------------------------
    | SSH Connection Timeout (seconds)
    |--------------------------------------------------------------------------
    */

    'ssh_timeout' => (int) env('RSTACK_SSH_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Nginx Proxy Manager
    |--------------------------------------------------------------------------
    |
    | RStack automatically creates proxy hosts in NPM after a successful
    | deployment. Set NPM_URL to the base URL of your NPM instance and
    | provide admin credentials via NPM_EMAIL and NPM_PASSWORD.
    |
    */

    'npm' => [
        'url'      => env('NPM_URL'),
        'email'    => env('NPM_EMAIL'),
        'password' => env('NPM_PASSWORD'),
        'enabled'  => env('NPM_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nextname DNS
    |--------------------------------------------------------------------------
    |
    | RStack registers subdomain A-records via the Nextname.nl JSON REST API.
    | Set NEXTNAME_API_KEY to your API key and NEXTNAME_DOMAIN to the base
    | domain (e.g. rstack.nl). Set NEXTNAME_ENABLED=false to skip DNS
    | registration entirely.
    |
    */

    'nextname' => [
        'url'     => env('NEXTNAME_URL', 'https://api.nextname.nl/v2'),
        'key'     => env('NEXTNAME_API_KEY'),
        'domain'  => env('NEXTNAME_DOMAIN', 'rstack.nl'),
        'enabled' => env('NEXTNAME_ENABLED', false),
        'ttl'     => (int) env('NEXTNAME_TTL', 300),
    ],

];
