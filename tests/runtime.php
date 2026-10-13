<?php
// Installed HTTP middleware checks. No real commands, game-file changes or API keys.
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;

$panel = $argv[1] ?? '/app'; require $panel . '/vendor/autoload.php';
$app = require $panel . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class); $kernel->bootstrap();
$server = Server::query()->with('egg')->get()->first(fn ($candidate) => !(new Pterodactyl\BlueprintFramework\Extensions\bedrockplayers\QueryTarget)->supports($candidate));
if (!$server) throw new RuntimeException('Requires an existing non-Bedrock server for safe rejection checks.');
$admin = User::query()->where('root_admin', true)->firstOrFail();
$outsider = new User; $outsider->forceFill(['id' => 0, 'root_admin' => false, 'use_totp' => true]);
$base = '/api/client/extensions/bedrockplayers/servers/' . $server->uuid . '/players';
$cases = [
    ['GET', $base, null, [], 401], ['POST', $base . '/list', null, [], 401],
    ['GET', $base . '/Gamer%20Tag/inventory', null, [], 401],
    ['GET', $base, $outsider, [], 404], ['POST', $base . '/list', $outsider, [], 404],
    ['GET', $base, $admin, [], 200], ['POST', $base . '/list', $admin, [], 403],
    ['POST', $base . '/Gamer%20Tag/actions', $admin, ['action' => 'op', 'confirmed' => true], 403],
    ['GET', $base . '/Gamer%20Tag/inventory', $admin, [], 403],
];
foreach ($cases as [$method, $path, $user, $parameters, $expected]) {
    Auth::forgetGuards();
    $request = Request::create($path, $method, $parameters, [], [], ['HTTP_ACCEPT' => 'application/json']);
    $app->instance('request', $request); if ($user) Auth::guard('sanctum')->setUser($user);
    $response = $kernel->handle($request);
    if ($response->getStatusCode() !== $expected) throw new RuntimeException($method . ' expected ' . $expected . ': ' . $response->getContent());
    echo $method . ' ' . basename($path) . ': HTTP ' . $expected . PHP_EOL;
    if ($expected === 200 && json_decode($response->getContent(), true)['attributes']['status'] !== 'unsupported') throw new RuntimeException('Non-Bedrock servers must remain unsupported.');
}
echo '9 installed Bedrock API auth and platform checks passed.' . PHP_EOL;
