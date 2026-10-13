<?php

namespace Pterodactyl\BlueprintFramework\Extensions\bedrockplayers;

use Pterodactyl\Models\Permission;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;

class PlayerPermissions
{
    public function capabilities(User $user, Server $server): array
    {
        $software = (new QueryTarget)->software($server);
        $console = $software !== null && $user->can(Permission::ACTION_CONTROL_CONSOLE, $server);
        $operator = $console && ($user->root_admin || $user->id === $server->owner_id);
        return [
            'moderate' => $console, 'operator' => $operator,
            'inventory' => $console && $user->can(Permission::ACTION_FILE_READ, $server)
                && $user->can(Permission::ACTION_FILE_READ_CONTENT, $server),
            'actions' => $console ? array_values(array_filter((new PlayerCommand)->actions($software),
                fn ($action) => $operator || !in_array($action, ['op', 'deop'], true))) : [],
        ];
    }
}
