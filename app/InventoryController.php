<?php

namespace Pterodactyl\BlueprintFramework\Extensions\bedrockplayers;

use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InventoryController extends ClientApiController
{
    public function __invoke(GetInventoryRequest $request, Server $server, PlayerFiles $files, ExportSnapshot $snapshots): array
    {
        try { $json = $files->read($server, '/bedrock-player-snapshots.json', 2097152); }
        catch (DaemonConnectionException $exception) {
            if (!in_array($exception->getStatusCode(), [404, 500], true)) throw $exception;
            throw new HttpException(404, 'No Bedrock inventory export was found. Install a snapshot exporter that writes bedrock-player-snapshots.json; stock Bedrock worlds cannot be inspected with the Java save reader.', $exception);
        }
        return ['object' => 'bedrock_inventory_snapshot', 'attributes' => $snapshots->parse($json, $request->validated('player'))];
    }
}
