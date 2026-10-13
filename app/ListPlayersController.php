<?php

namespace Pterodactyl\BlueprintFramework\Extensions\bedrockplayers;

use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonCommandRepository;

class ListPlayersController extends ClientApiController
{
    public function __invoke(ListPlayersRequest $request, Server $server, DaemonCommandRepository $wings): array
    {
        $wings->setServer($server)->send('list');
        Activity::event('server:console.command')->property('command', 'list')
            ->property('addon', 'bedrockplayers')->log();
        return ['object' => 'bedrock_roster_request', 'attributes' => ['status' => 'submitted']];
    }
}
