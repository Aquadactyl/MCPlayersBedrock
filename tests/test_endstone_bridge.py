import importlib.util
import json
from pathlib import Path
import sys
import tempfile
import types
import unittest
from unittest.mock import patch

plugin_module = types.ModuleType("endstone.plugin")
plugin_module.Plugin = type("Plugin", (), {})
sys.modules["endstone.plugin"] = plugin_module
path = Path(__file__).resolve().parents[1] / "bridges/endstone/endstone_bedrock_snapshots/plugin.py"
spec = importlib.util.spec_from_file_location("snapshot_bridge", path)
bridge = importlib.util.module_from_spec(spec)
spec.loader.exec_module(bridge)
obj = types.SimpleNamespace


def player(name="Gamer Tag"):
    sword = obj(amount=1, type=obj(id="minecraft:diamond_sword"), data=4, nbt="{Damage:4}")
    inventory = obj(contents=[sword] + [None] * 35, helmet=sword, chestplate=None,
                    leggings=None, boots=None, item_in_off_hand=sword, held_item_slot=0)
    return obj(name=name, xuid="1234567890", inventory=inventory,
               ender_chest=obj(contents=[sword]), health=18, exp_level=12,
               exp_progress=0.375, is_op=True, location=obj(x=12, y=64, z=-10),
               dimension=obj(name="Overworld"))


class ExporterTests(unittest.TestCase):
    def test_read_only_inventory_and_hud_fields(self):
        record = bridge.capture(player())
        self.assertEqual([entry["slot"] for entry in record["inventory"]], [0, 103, 150])
        self.assertEqual(record["xp_progress"], 0.375)
        self.assertEqual(record["health"], 18)
        self.assertIsNone(record["food"])
        self.assertEqual(record["xuid"], "1234567890")

    def test_missing_food_is_not_guessed(self):
        native = player()
        native.food_level = 19
        self.assertEqual(bridge.capture(native)["food"], 19)
        native.food_level = float("nan")
        self.assertIsNone(bridge.capture(native)["food"])

    def test_atomic_file_and_retained_offline_timestamp(self):
        with tempfile.TemporaryDirectory() as directory, patch.object(bridge.Path, "cwd", return_value=Path(directory)):
            instance = bridge.BedrockSnapshots()
            instance.logger = obj(warning=lambda text: self.fail(text))
            scheduled = []
            instance.server = obj(online_players=[player()], scheduler=obj(run_task=lambda *args, **kwargs: scheduled.append(kwargs)))
            instance.on_enable()
            self.assertEqual(scheduled[0]["period"], 100)
            instance.publish()
            data = json.loads(instance.output.read_text(encoding="utf-8"))
            self.assertEqual(data["schema"], "bedrockplayers.v1")
            saved_at = data["players"][0]["saved_at"]
            instance.server.online_players = []
            instance.publish()
            data = json.loads(instance.output.read_text(encoding="utf-8"))
            self.assertEqual(data["players"][0]["saved_at"], saved_at)
            self.assertFalse(instance.output.with_suffix(".json.tmp").exists())


if __name__ == "__main__":
    unittest.main()
