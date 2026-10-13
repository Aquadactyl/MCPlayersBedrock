<?php

namespace Pterodactyl\BlueprintFramework\Extensions\bedrockplayers;

use Symfony\Component\Process\Process;

class QueryRunner
{
    public function query(array $target): array
    {
        try {
            $process = new Process([config('game-query.node_binary', 'node'), '--max-old-space-size=96',
                base_path('.blueprint/extensions/bedrockplayers/private/query.mjs')], base_path());
            $process->setInput(json_encode($target, JSON_THROW_ON_ERROR));
            $process->setTimeout(6)->run();
            $result = json_decode($process->getOutput(), true, 10, JSON_THROW_ON_ERROR);
            if ($process->isSuccessful() && is_array($result) && ($result['status'] ?? null) === 'available'
                && ($result['source'] ?? null) === 'bedrock_status' && ($result['complete'] ?? null) === false
                && ($result['players'] ?? null) === [] && is_int($result['online'] ?? null)
                && $result['online'] >= 0 && $result['online'] <= 1000000 && is_int($result['maximum'] ?? null)
                && $result['maximum'] >= 0 && $result['maximum'] <= 1000000) {
                return $result;
            }
        } catch (\Throwable) {}
        return ['status' => 'unavailable', 'source' => null, 'complete' => false,
            'online' => null, 'maximum' => null, 'players' => []];
    }
}
