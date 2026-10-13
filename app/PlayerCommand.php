<?php

namespace Pterodactyl\BlueprintFramework\Extensions\bedrockplayers;

class PlayerCommand
{
    public function actions(?string $software): array
    {
        if (!in_array($software, ['bds', 'endstone', 'nukkit', 'pocketmine'], true)) {
            return [];
        }
        $actions = ['kick', 'op', 'deop', 'whitelist_add', 'whitelist_remove'];
        return $software === 'bds' ? $actions : array_merge($actions, ['ban', 'pardon']);
    }

    public function build(string $name, string $action, ?string $reason, string $software): string
    {
        if (!Gamertag::valid($name) || !in_array($action, $this->actions($software), true)
            || ($reason !== null && (mb_strlen($reason) > 120 || preg_match('/[\p{C}\p{Zl}\p{Zp}]/u', $reason)))) {
            throw new \InvalidArgumentException('Invalid gamertag, reason or unsupported action.');
        }
        // Quotes and backslashes are excluded by Gamertag::valid. Never accept selectors.
        $target = '"' . $name . '"';
        $allowlist = in_array($software, ['bds', 'endstone'], true) ? 'allowlist' : 'whitelist';
        $command = match ($action) {
            'kick', 'ban', 'op', 'deop' => $action . ' ' . $target,
            'pardon' => ($software === 'endstone' ? 'unban' : 'pardon') . ' ' . $target,
            'whitelist_add' => $allowlist . ' add ' . $target,
            'whitelist_remove' => $allowlist . ' remove ' . $target,
        };
        if (in_array($action, ['kick', 'ban'], true) && trim($reason ?? '') !== '') {
            $command .= ' ' . trim($reason);
        }
        return $command;
    }
}
