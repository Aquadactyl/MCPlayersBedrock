<?php

namespace Pterodactyl\BlueprintFramework\Extensions\bedrockplayers;

class Gamertag
{
    public const PATTERN = '/\A[\p{L}\p{M}\p{N}_ .#-]{1,32}\z/u';

    public static function valid(mixed $name): bool
    {
        return is_string($name) && $name === trim($name)
            && preg_match(self::PATTERN, $name) === 1
            && preg_match('/[\p{L}\p{N}_]/u', $name) === 1;
    }
}
