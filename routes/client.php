<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\PlayersController;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\ListPlayersController;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\PlayerActionController;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\InventoryController;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;
use Pterodactyl\Http\Middleware\Activity\ServerSubject;

Route::get('/servers/{server}/players', PlayersController::class)
    ->middleware([AuthenticateServerAccess::class, 'throttle:20,1,bedrockplayers:']);
Route::post('/servers/{server}/players/list', ListPlayersController::class)
    ->middleware([AuthenticateServerAccess::class, ServerSubject::class, 'throttle:12,1,bedrockplayers-list:']);
Route::post('/servers/{server}/players/{player}/actions', PlayerActionController::class)
    ->where('player', '[^/]{1,128}')
    ->middleware([AuthenticateServerAccess::class, ServerSubject::class, 'throttle:10,1,bedrockplayers-actions:']);
Route::get('/servers/{server}/players/{player}/inventory', InventoryController::class)
    ->where('player', '[^/]{1,128}')
    ->middleware([AuthenticateServerAccess::class, 'throttle:12,1,bedrockplayers-inventory:']);
