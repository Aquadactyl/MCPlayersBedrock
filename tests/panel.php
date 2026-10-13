<?php

// Run against an installed addon: php /path/to/backend.php /app
// Uses only in-memory models and cache; no database writes or persistent credentials.
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;

$panel = $argv[1] ?? '/app';
require $panel . '/vendor/autoload.php';
$app = require $panel . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Cache::swap(new Repository(new ArrayStore));

$checks = 0;
function check(bool $passed, string $description): void
{
    global $checks;
    if (!$passed) {
        throw new RuntimeException('FAIL: ' . $description);
    }
    $checks++;
    echo 'PASS: ' . $description . PHP_EOL;
}

function fixture(): Server
{
    $server = new Server;
    $server->forceFill(['id' => 901, 'node_id' => 902, 'owner_id' => 903, 'uuid' => '11111111-1111-4111-8111-111111111111', 'game_query_type' => 'auto', 'status' => null]);
    $node = new Node;
    $node->forceFill(['id' => 902, 'fqdn' => 'wings.example.test', 'query_address' => 'query.example.test', 'maintenance_mode' => false]);
    $egg = new Egg;
    $egg->forceFill(['name' => 'Paper']);
    $primary = new Allocation;
    $primary->forceFill(['id' => 904, 'node_id' => 902, 'server_id' => 901, 'ip' => '192.0.2.1', 'port' => 25565]);
    $query = new Allocation;
    $query->forceFill(['id' => 905, 'node_id' => 902, 'server_id' => 901, 'ip' => '192.0.2.2', 'port' => 25566]);
    $server->setRelation('node', $node)->setRelation('egg', $egg)->setRelation('allocation', $primary)->setRelation('gameQueryAllocation', $query)->setRelation('subusers', collect())->setRelation('transfer', null);

    return $server;
}
