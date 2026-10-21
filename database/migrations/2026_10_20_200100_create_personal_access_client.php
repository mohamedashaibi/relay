<?php

use Illuminate\Database\Migrations\Migration;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

// Personal tokens (used by Claude Code / Desktop) need a Passport "personal access" client to exist.
return new class extends Migration
{
    public function up(): void
    {
        $exists = Client::all()->contains(fn (Client $c) => $c->hasGrantType('personal_access'));

        if (! $exists) {
            app(ClientRepository::class)->createPersonalAccessGrantClient('Relay Personal Access', 'users');
        }
    }

    public function down(): void
    {
        Client::all()->filter(fn (Client $c) => $c->hasGrantType('personal_access'))->each->delete();
    }
};
