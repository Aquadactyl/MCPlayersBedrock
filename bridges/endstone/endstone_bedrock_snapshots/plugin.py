"""Read-only player exporter for Endstone 0.11. No network or game commands."""
import json
import math
import os
from collections import OrderedDict
from datetime import datetime, timezone
from pathlib import Path

from endstone.plugin import Plugin


def stamp():
    return datetime.now(timezone.utc).isoformat()


def finite(value):
    return value if isinstance(value, (int, float)) and not isinstance(value, bool) and math.isfinite(value) else None


def stack(item, slot):
    if item is None or item.amount <= 0 or item.type.id == "minecraft:air":
        return None
    metadata = str(item.nbt)
    return {"slot": slot, "id": item.type.id, "count": item.amount,
            "metadata": {"data": item.data, "nbt": metadata[:17000]}}


def contents(inventory, limit):
    return [entry for slot, item in enumerate(inventory.contents[:limit]) if (entry := stack(item, slot))]


def capture(player):
    inventory = player.inventory
    items = contents(inventory, 36)
    for attribute, slot in (("helmet", 103), ("chestplate", 102), ("leggings", 101), ("boots", 100), ("item_in_off_hand", 150)):
        item = stack(getattr(inventory, attribute, None), slot)
        if item:
            items.append(item)
    location = player.location
    # Some Endstone APIs do not expose hunger; an absent getter stays unknown.
    food = finite(getattr(player, "food_level", None))
    return {"name": player.name, "xuid": player.xuid, "saved_at": stamp(),
            "inventory": items, "ender_chest": contents(player.ender_chest, 27),
            "health": finite(player.health), "food": food,
            "xp_level": player.exp_level, "xp_progress": finite(player.exp_progress),
            "selected_slot": inventory.held_item_slot, "dimension": player.dimension.name,
            "position": [location.x, location.y, location.z],
            "state": {"operator": player.is_op, "banned": None, "whitelisted": None}}


class BedrockSnapshots(Plugin):
    api_version = "0.11"

    def on_enable(self):
        self.output = Path.cwd() / "bedrock-player-snapshots.json"
        self.records = OrderedDict()
        if self.output.is_file() and self.output.stat().st_size <= 2097152:
            try:
                previous = json.loads(self.output.read_text(encoding="utf-8"))
                if previous.get("schema") == "bedrockplayers.v1":
                    for record in previous.get("players", [])[-1000:]:
                        if isinstance(record, dict) and isinstance(record.get("name"), str):
                            self.records[record["name"].casefold()] = record
            except (ValueError, OSError):
                self.logger.warning("Previous snapshot export could not be read.")
        self.server.scheduler.run_task(self, self.publish, delay=20, period=100)

    def publish(self):
        try:
            for player in self.server.online_players:
                record = capture(player)
                self.records.pop(player.name.casefold(), None)
                self.records[player.name.casefold()] = record
            while len(self.records) > 1000:
                self.records.popitem(last=False)
            while True:
                payload = json.dumps({"schema": "bedrockplayers.v1", "saved_at": stamp(),
                                      "players": list(self.records.values())}, ensure_ascii=False, allow_nan=False).encode("utf-8")
                if len(payload) <= 2097152 or not self.records:
                    break
                self.records.popitem(last=False)
            temporary = self.output.with_suffix(".json.tmp")
            temporary.write_bytes(payload)
            os.replace(temporary, self.output)
        except (ValueError, OSError, RuntimeError) as error:
            self.logger.warning(f"Snapshot export failed: {error}")

    def on_disable(self):
        self.publish()
