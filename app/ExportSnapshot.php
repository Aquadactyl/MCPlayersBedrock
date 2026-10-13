<?php

namespace Pterodactyl\BlueprintFramework\Extensions\bedrockplayers;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ExportSnapshot
{
    public function parse(string $json, string $name): array
    {
        if (!Gamertag::valid($name) || strlen($json) > 2097152) {
            throw new HttpException(422, 'Invalid gamertag or oversized snapshot export.');
        }
        try { $data = json_decode($json, true, 32, JSON_THROW_ON_ERROR); }
        catch (\JsonException $exception) { throw new HttpException(422, 'The Bedrock snapshot export is invalid JSON.', $exception); }
        if (!is_array($data) || ($data['schema'] ?? null) !== 'bedrockplayers.v1'
            || !is_array($data['players'] ?? null) || !array_is_list($data['players']) || count($data['players']) > 1000
            || !is_string($data['saved_at'] ?? null) || strlen($data['saved_at']) > 64
            || !preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,9})?(?:Z|[+-]\d{2}:\d{2})\z/', $data['saved_at'])
            || strtotime($data['saved_at']) === false) {
            throw new HttpException(422, 'The file does not match the Bedrock player snapshot format.');
        }
        $matches = array_values(array_filter($data['players'], fn ($player) => is_array($player)
            && Gamertag::valid($player['name'] ?? null) && mb_strtolower($player['name']) === mb_strtolower($name)));
        if (count($matches) !== 1) {
            throw new HttpException(count($matches) ? 422 : 404, count($matches)
                ? 'The snapshot contains duplicate gamertags.' : 'This player has no exported inventory snapshot.');
        }
        $player = $matches[0];
        $savedAt = $player['saved_at'] ?? $data['saved_at'];
        if (!is_string($savedAt) || strlen($savedAt) > 64 || strtotime($savedAt) === false
            || !preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,9})?(?:Z|[+-]\d{2}:\d{2})\z/', $savedAt)) {
            throw new HttpException(422, 'The player snapshot has an invalid save time.');
        }
        $progress = $this->number($player['xp_progress'] ?? null);
        $food = $this->number($player['food'] ?? null);
        $level = $player['xp_level'] ?? null;
        $position = $player['position'] ?? null;
        return [
            'source' => 'snapshot_export', 'player' => $player['name'], 'uuid' => null,
            'xuid' => is_string($player['xuid'] ?? null) && preg_match('/\A\d{1,20}\z/', $player['xuid']) ? $player['xuid'] : null,
            'saved_at' => $savedAt, 'read_at' => now()->toIso8601String(),
            'inventory' => $this->items($player['inventory'] ?? [], false),
            'ender_chest' => $this->items($player['ender_chest'] ?? [], true),
            'health' => $this->number($player['health'] ?? null),
            'food' => $food !== null && $food <= 20 ? $food : null,
            'xp_level' => is_int($level) && $level >= 0 ? $level : null,
            'xp_progress' => $progress !== null && $progress <= 1 ? $progress : null,
            'selected_slot' => is_int($player['selected_slot'] ?? null) && $player['selected_slot'] >= 0 && $player['selected_slot'] <= 8 ? $player['selected_slot'] : null,
            'dimension' => is_string($player['dimension'] ?? null) ? mb_substr($player['dimension'], 0, 128) : null,
            'position' => is_array($position) && array_is_list($position) && count($position) === 3
                && array_all($position, fn ($value) => (is_int($value) || is_float($value)) && is_finite((float) $value)) ? $position : null,
            'data_version' => null,
            'state' => array_combine(['operator', 'banned', 'whitelisted'], array_map(
                fn ($key) => is_bool($player['state'][$key] ?? null) ? $player['state'][$key] : null,
                ['operator', 'banned', 'whitelisted'])),
        ];
    }

    private function number(mixed $value): int|float|null
    {
        return (is_int($value) || is_float($value)) && is_finite((float) $value) && $value >= 0 ? $value : null;
    }

    private function items(mixed $items, bool $ender): array
    {
        if (!is_array($items)) return [];
        $result = [];
        foreach (array_slice($items, 0, 128) as $item) {
            if (!is_array($item) || !is_int($slot = $item['slot'] ?? null) || !is_string($id = $item['id'] ?? null)
                || strlen($id) > 160 || !preg_match('~\A[a-z0-9_.-]+:[a-z0-9_./-]+\z~', $id)
                || $id === 'minecraft:air' || !is_int($count = $item['count'] ?? null) || $count < 1 || $count > 1000000
                || ($ender ? $slot < 0 || $slot > 26 : !($slot >= 0 && $slot <= 35 || $slot >= 100 && $slot <= 103 || $slot === 150))) {
                continue;
            }
            $metadata = json_encode($item['metadata'] ?? [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            $truncated = strlen($metadata) > 16384;
            $result[$slot] = ['slot' => $slot, 'id' => $id, 'count' => $count,
                'metadata' => $truncated ? null : json_decode($metadata, true), 'metadata_truncated' => $truncated];
        }
        ksort($result);
        return array_values($result);
    }
}
