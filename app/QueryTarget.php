<?php

namespace Pterodactyl\BlueprintFramework\Extensions\bedrockplayers;

use Pterodactyl\Models\Server;

class QueryTarget
{
    public function software(Server $server): ?string
    {
        $type = $server->game_query_type ?? 'auto';
        if (!in_array($type, ['auto', 'protocol-minecraftbedrock'], true)) {
            return null;
        }
        $name = $server->egg->name;
        return match (true) {
            (bool) preg_match('/endstone/i', $name) => 'endstone',
            (bool) preg_match('/nukkit/i', $name) => 'nukkit',
            (bool) preg_match('/pocketmine|pmmp/i', $name) => 'pocketmine',
            $type === 'protocol-minecraftbedrock' || (bool) preg_match('/bedrock|bdsx|levilamina/i', $name) => 'bds',
            default => null,
        };
    }

    public function supports(Server $server): bool
    {
        return $this->software($server) !== null;
    }

    public function resolve(Server $server): ?array
    {
        $allocation = $server->allocation;
        if (!config('game-query.enabled', true) || !$this->supports($server) || !$allocation
            || $allocation->server_id !== $server->id || $allocation->node_id !== $server->node_id) {
            return null;
        }
        $host = $server->node->query_address ?: $allocation->ip;
        if (in_array($host, ['127.0.0.1', '::1', '0.0.0.0', '::'], true)) {
            $host = config('game-query.local_container_queries') && $server->node->fqdn === 'wings.localhost'
                ? $server->uuid : $server->node->fqdn;
        }
        return ['host' => $host, 'port' => (int) $allocation->port];
    }
}
