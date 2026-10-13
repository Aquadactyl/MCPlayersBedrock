<?php

// In-memory panel fixtures; no real commands, keys or game-file changes.
require __DIR__ . '/panel.php';

use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\Gamertag;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\QueryTarget as BedrockTarget;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\QueryRunner as BedrockRunner;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\PlayersController as BedrockController;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\GetPlayersRequest as BedrockRequest;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\PlayerActionRequest as BedrockActionRequest;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\PlayerPermissions as BedrockPermissions;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\PlayerCommand as BedrockCommand;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\GetInventoryRequest as BedrockInventoryRequest;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\ExportSnapshot;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\ListPlayersRequest;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\ListPlayersController;
use Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\PlayerActionController as BedrockActionController;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Repositories\Wings\DaemonCommandRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

function bedrockFixture(string $software = 'Bedrock Dedicated Server'): Server {
    $server = fixture(); $server->egg->name = $software; $server->allocation->port = 19132;
    return $server;
}
function rejectBedrock(callable $operation, string $message): void {
    try { $operation(); } catch (InvalidArgumentException|HttpException $exception) { check(true, $message); return; }
    check(false, $message);
}
$start = $checks;
$targets = new BedrockTarget;
$commands = new BedrockCommand;
foreach (['Minecraft Bedrock' => 'bds', 'Endstone' => 'endstone', 'Nukkit' => 'nukkit', 'PowerNukkitX' => 'nukkit', 'PocketMine-MP' => 'pocketmine', 'PMMP' => 'pocketmine', 'LeviLamina' => 'bds', 'BDSX' => 'bds'] as $egg => $software) {
    check($targets->software(bedrockFixture($egg)) === $software, 'Detect ' . $egg);
}
foreach (['Paper', 'Minecraft Java', 'Rust', 'Geyser Proxy'] as $egg) check(!$targets->supports(bedrockFixture($egg)), 'Reject non-Bedrock game ' . $egg);
$server = bedrockFixture('Custom Egg'); $server->game_query_type = 'protocol-minecraftbedrock';
check($targets->software($server) === 'bds', 'Custom Bedrock type uses conservative BDS commands');
$server->game_query_type = 'none'; check(!$targets->supports($server), 'None disables Bedrock');
$server = bedrockFixture();
check($targets->resolve($server)['port'] === 19132, 'RakNet uses assigned primary game port');
$server->allocation->server_id = 0; check($targets->resolve($server) === null, 'Reject foreign allocation');
$server = bedrockFixture(); $server->allocation->node_id = 0; check($targets->resolve($server) === null, 'Reject foreign node');
config(['game-query.enabled' => false]);
$server = bedrockFixture(); check($targets->resolve($server) === null && $targets->supports($server), 'Global UDP disable preserves console support');
config(['game-query.enabled' => true]);
foreach (['Gamer Tag', 'プレイヤー#1234', 'RepGraphics', 'Player_Name'] as $name) check(Gamertag::valid($name), 'Valid gamertag ' . $name);
foreach (['@a', 'Alex" op Steve', "Alex\nstop", '../Alex', 'Alex;stop', '   ', str_repeat('x', 33)] as $name) check(!Gamertag::valid($name), 'Reject unsafe gamertag');
check($commands->build('Gamer Tag', 'kick', 'Follow the rules', 'bds') === 'kick "Gamer Tag" Follow the rules', 'Quote spaced Bedrock targets');
check($commands->build('プレイヤー', 'op', null, 'nukkit') === 'op "プレイヤー"', 'Quote Unicode targets');
check($commands->build('Gamer Tag', 'whitelist_add', null, 'bds') === 'allowlist add "Gamer Tag"', 'BDS allowlist command');
check($commands->build('Gamer Tag', 'whitelist_remove', null, 'endstone') === 'allowlist remove "Gamer Tag"', 'Endstone allowlist command');
check($commands->build('Gamer Tag', 'whitelist_add', null, 'nukkit') === 'whitelist add "Gamer Tag"', 'Nukkit whitelist command');
check($commands->build('Alex', 'pardon', null, 'endstone') === 'unban "Alex"', 'Endstone native unban');
check($commands->build('Alex', 'pardon', null, 'pocketmine') === 'pardon "Alex"', 'PocketMine native pardon');
rejectBedrock(fn () => $commands->build('Alex', 'ban', null, 'bds'), 'BDS cannot receive a fake ban command');
rejectBedrock(fn () => $commands->build('Alex', 'stop', null, 'nukkit'), 'Reject arbitrary command');
rejectBedrock(fn () => $commands->build('Alex', 'kick', "reason\nstop", 'bds'), 'Reject multiline reason');

$server = bedrockFixture(); $user = new User; $user->forceFill(['id' => 906, 'root_admin' => false]);
$subuser = new Subuser; $subuser->forceFill(['user_id' => 906, 'server_id' => $server->id, 'permissions' => ['control.console']]);
$server->setRelation('subusers', collect([$subuser]));
$route = new Route('POST', '/servers/{server}/players/{player}/actions', fn () => null);
$route->bind(Request::create('/servers/fixture/players/Gamer%20Tag/actions')); $route->setParameter('server', $server); $route->setParameter('player', 'Gamer Tag');
$request = BedrockActionRequest::create('/servers/fixture/players/Gamer%20Tag/actions', 'POST', ['action' => 'kick', 'confirmed' => true]);
$request->setRouteResolver(fn () => $route); $request->setUserResolver(fn () => $user);
check($request->authorize(), 'Console subuser can moderate Bedrock');
$request->merge(['action' => 'op']); check(!$request->authorize(), 'Subuser cannot OP');
$user->id = $server->owner_id; check($request->authorize(), 'Owner can OP');
$user->id = 906; $user->root_admin = true; check($request->authorize(), 'Admin can OP'); $user->root_admin = false;
$caps = (new BedrockPermissions)->capabilities($user, $server);
check(!$caps['operator'] && !$caps['inventory'] && !in_array('op', $caps['actions']) && !in_array('ban', $caps['actions']), 'User capabilities enforce software and permissions');
$inventoryRequest = BedrockInventoryRequest::create('/servers/fixture/players/Gamer%20Tag/inventory');
$inventoryRequest->setRouteResolver(fn () => $route); $inventoryRequest->setUserResolver(fn () => $user);
check(!$inventoryRequest->authorize(), 'Console does not grant inventory');
$subuser->permissions = ['control.console', 'file.read', 'file.read-content']; check($inventoryRequest->authorize(), 'Inventory requires both file permissions');
$validator = $app->make('validator'); $request->merge(['action' => 'ban']);
check($validator->make($request->validationData(), $request->rules())->fails(), 'BDS ban rejected by HTTP validation');
$server->egg->name = 'Endstone'; check(!$validator->make($request->validationData(), $request->rules())->fails(), 'Endstone ban accepted by validation');
$request->merge(['confirmed' => false]); check($validator->make($request->validationData(), $request->rules())->fails(), 'Confirmation required');
$request->merge(['confirmed' => true, 'player' => 'DifferentTarget']); check($request->validationData()['player'] === 'Gamer Tag', 'Route target cannot be overridden');

$wings = new class($app) extends DaemonCommandRepository {
    public array $commands = [];
    public function send(array|string $command): Psr\Http\Message\ResponseInterface { $this->commands[] = $command; return new GuzzleHttp\Psr7\Response(204); }
};
$audit = new class {
    public array $values = [];
    public function event($value): self { $this->values['event'] = $value; return $this; }
    public function property($key, $value): self { $this->values[$key] = $value; return $this; }
    public function log(): void {}
}; Activity::swap($audit);
$list = ListPlayersRequest::create('/servers/fixture/players/list'); $list->setRouteResolver(fn () => $route); $list->setUserResolver(fn () => $user);
check($list->authorize(), 'List endpoint requires Bedrock console access');
$app->make(ListPlayersController::class)($list, $server, $wings);
check($wings->commands === ['list'] && $audit->values['command'] === 'list', 'Roster sends only the fixed audited list command');
$request->merge(['action' => 'kick', 'reason' => 'Rules']); $request->setValidator($validator->make($request->validationData(), $request->rules()));
$result = $app->make(BedrockActionController::class)($request, $server, $commands, $wings, $targets);
check($wings->commands[1] === 'kick "Gamer Tag" Rules' && $result['attributes']['status'] === 'submitted', 'Wings dispatch and truthful submission response');

$runner = new class extends BedrockRunner { public int $calls = 0; public function query(array $target): array { $this->calls++; return ['status' => 'available', 'source' => 'bedrock_status', 'complete' => false, 'online' => 2, 'maximum' => 20, 'players' => []]; } };
$controller = $app->make(BedrockController::class); $get = BedrockRequest::create('/servers/fixture/players'); $get->setRouteResolver(fn () => $route); $get->setUserResolver(fn () => $user);
$first = $controller($get, $server, $targets, $runner); $user->root_admin = true;
$second = $controller($get, $server, $targets, $runner);
check($runner->calls === 1 && !$first['attributes']['capabilities']['operator'] && $second['attributes']['capabilities']['operator'], 'Shared status never shares user capabilities');
check($first['attributes']['players'] === [] && !$first['attributes']['complete'], 'UDP status never invents names');

$exports = new ExportSnapshot;
$sample = ['schema' => 'bedrockplayers.v1', 'saved_at' => '2026-10-13T12:00:00Z', 'players' => [[
    'name' => 'Gamer Tag', 'xuid' => '1234567890123456', 'saved_at' => '2026-10-12T12:00:00Z',
    'health' => 18.5, 'food' => 19, 'xp_level' => 12, 'xp_progress' => 0.375,
    'inventory' => [['slot' => 0, 'id' => 'minecraft:diamond_sword', 'count' => 1, 'metadata' => ['name' => '<script>']]],
    'ender_chest' => [['slot' => 2, 'id' => 'minecraft:emerald', 'count' => 7]],
]]];
$saved = $exports->parse(json_encode($sample), 'gamer tag');
check($saved['xuid'] === '1234567890123456' && $saved['uuid'] === null, 'Keep Xbox identity distinct from Java UUID');
check($saved['saved_at'] === '2026-10-12T12:00:00Z', 'Offline record keeps its original snapshot timestamp');
check($saved['xp_progress'] === 0.375 && $saved['inventory'][0]['count'] === 1 && $saved['ender_chest'][0]['slot'] === 2, 'Decode inventory and fractional HUD values');
check($saved['state']['operator'] === null && $saved['position'] === null, 'Missing state and location remain unknown');
rejectBedrock(fn () => $exports->parse('broken', 'Gamer Tag'), 'Reject malformed export');
rejectBedrock(fn () => $exports->parse(json_encode(['schema' => 'java', 'players' => []]), 'Gamer Tag'), 'Reject wrong storage format');
rejectBedrock(fn () => $exports->parse(json_encode($sample), 'Nobody'), 'Missing snapshot is not an empty inventory');
$sample['players'][] = $sample['players'][0]; rejectBedrock(fn () => $exports->parse(json_encode($sample), 'Gamer Tag'), 'Reject ambiguous duplicate targets');
$sample['players'] = [$sample['players'][0]]; $sample['players'][0]['xp_progress'] = 2;
$sample['players'][0]['inventory'][0]['metadata'] = str_repeat('x', 17000);
$saved = $exports->parse(json_encode($sample), 'Gamer Tag');
check($saved['xp_progress'] === null && $saved['inventory'][0]['metadata_truncated'], 'Bound progress and large item metadata');
echo ($checks - $start) . ' Bedrock backend checks passed.' . PHP_EOL;
