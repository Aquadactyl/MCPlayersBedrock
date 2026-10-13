<?php

namespace Pterodactyl\BlueprintFramework\Extensions\bedrockplayers;

use Pterodactyl\Models\Server;

class ListPlayersRequest extends GetPlayersRequest
{
    public function authorize(): bool
    {
        $server = $this->route('server');
        return parent::authorize() && $server instanceof Server && (new QueryTarget)->supports($server);
    }
}
