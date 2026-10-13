<?php
declare(strict_types=1);

namespace RepGraphics\BedrockSnapshots;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\format\io\GlobalItemDataHandlers;

final class Main extends PluginBase
{
    private array $records = [];
    private string $output;

    protected function onEnable(): void
    {
        $this->output = getcwd() . DIRECTORY_SEPARATOR . 'bedrock-player-snapshots.json';
        if (is_file($this->output) && filesize($this->output) <= 2097152) {
            $previous = json_decode(file_get_contents($this->output), true);
            if (($previous['schema'] ?? null) === 'bedrockplayers.v1') {
                foreach (array_slice($previous['players'] ?? [], -1000) as $record) {
                    if (is_array($record) && is_string($record['name'] ?? null)) $this->records[strtolower($record['name'])] = $record;
                }
            }
        }
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(fn () => $this->publish()), 100);
    }

    private function stamp(): string { return gmdate('Y-m-d\TH:i:s\Z'); }

    private function stack(Item $item, int $slot): ?array
    {
        if ($item->isNull()) return null;
        $metadata = ['name' => $item->getCustomName(), 'lore' => $item->getLore()];
        $tag = $item->getNamedTag();
        if (is_callable([$tag, 'toString'])) $metadata['nbt'] = substr($tag->toString(), 0, 17000);
        return ['slot' => $slot, 'id' => GlobalItemDataHandlers::getSerializer()->serializeType($item)->getName(),
            'count' => $item->getCount(), 'metadata' => $metadata];
    }

    private function contents(Inventory $inventory, int $limit): array
    {
        $result = [];
        for ($slot = 0; $slot < $limit; $slot++) if (($item = $this->stack($inventory->getItem($slot), $slot)) !== null) $result[] = $item;
        return $result;
    }

    private function capture(Player $player): array
    {
        $inventory = $player->getInventory(); $armor = $player->getArmorInventory();
        $items = $this->contents($inventory, 36);
        foreach ([103 => $armor->getHelmet(), 102 => $armor->getChestplate(), 101 => $armor->getLeggings(), 100 => $armor->getBoots(), 150 => $player->getOffHandInventory()->getItem(0)] as $slot => $stack) {
            if (($item = $this->stack($stack, $slot)) !== null) $items[] = $item;
        }
        $position = $player->getPosition(); $name = $player->getName();
        return ['name' => $name, 'xuid' => $player->getXuid(), 'saved_at' => $this->stamp(),
            'inventory' => $items, 'ender_chest' => $this->contents($player->getEnderInventory(), 27),
            'health' => $player->getHealth(), 'food' => $player->getHungerManager()->getFood(),
            'xp_level' => $player->getXpManager()->getXpLevel(), 'xp_progress' => $player->getXpManager()->getXpProgress(),
            'selected_slot' => $inventory->getHeldItemIndex(), 'dimension' => $player->getWorld()->getFolderName(),
            'position' => [$position->x, $position->y, $position->z],
            'state' => ['operator' => $this->getServer()->isOp($name), 'banned' => $this->getServer()->getNameBans()->isBanned($name), 'whitelisted' => $this->getServer()->isWhitelisted($name)]];
    }

    public function publish(): void
    {
        try {
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                $key = strtolower($player->getName()); unset($this->records[$key]); $this->records[$key] = $this->capture($player);
            }
            do {
                $payload = json_encode(['schema' => 'bedrockplayers.v1', 'saved_at' => $this->stamp(), 'players' => array_values($this->records)], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                if (count($this->records) <= 1000 && strlen($payload) <= 2097152) break;
                array_shift($this->records);
            } while (true);
            $temporary = $this->output . '.tmp';
            if (file_put_contents($temporary, $payload, LOCK_EX) === false || !rename($temporary, $this->output)) throw new \RuntimeException('Could not write the snapshot export.');
        } catch (\Throwable $error) { $this->getLogger()->warning('Snapshot export failed: ' . $error->getMessage()); }
    }

    protected function onDisable(): void { if (isset($this->output)) $this->publish(); }
}
