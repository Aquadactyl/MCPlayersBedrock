# Bedrock Players for Aquadactyl

Independent Blueprint addon for official Bedrock Dedicated Server, Endstone,
Nukkit/PowerNukkit and PocketMine-MP. Identifier: `bedrockplayers`, version 1.0.0,
Blueprint beta-2026-08. Install alongside the Java `mcplayers` addon.

## Features

- Authenticated native console `list` rosters, search, copy, gamertags with spaces
  and Unicode, manual offline lookup and visible-page refresh every 15 seconds.
- Bedrock RakNet UDP counts when the console roster cannot be read. Counts are
  never presented as a player-name list.
- Confirmed, audited kick, OP/de-OP and access-list controls. Ban/unban on server
  software with native support. Owners/admins alone can OP or de-OP.
- Read-only exported inventory, equipment, Ender Chest, metadata, hearts, hunger
  and XP HUD, using the common snapshot format below.
- Separate routes, cache keys and CSS from the Java addon. No panel dependency
  installs or core source patches. Decorative avatars; Java skins are not matched
  to Xbox gamertags.

| Software | Ban / unban | Access-list command |
| --- | --- | --- |
| Official BDS, BDSX, LeviLamina | Not exposed; no native BDS ban | `allowlist` |
| Endstone | `ban` / `unban` | `allowlist` |
| Nukkit / PowerNukkit | `ban` / `pardon` | `whitelist` |
| PocketMine-MP | `ban` / `pardon` | `whitelist` |

Commands report submission; read the console for the game's actual result. An
access-list removal changes the list, and does not enable it or constitute a ban.
Older versions or plugins overriding native commands can behave differently.

## Install

Copy `dist/bedrockplayers.blueprint` into the Aquadactyl panel root, then run:

```bash
cd /var/www/aquadactyl
sudo blueprint -i bedrockplayers
```

For the existing local test panel, from this repository:

```bash
docker cp dist/bedrockplayers.blueprint aquadactyl-test-panel-1:/app/bedrockplayers.blueprint
docker exec aquadactyl-test-panel-1 blueprint -i bedrockplayers
```

Open the server's **Bedrock Players** tab. Under Admin > Extensions > Bedrock
Players, use Route eggs to restrict navigation to your Bedrock eggs. Software is
detected from the egg name: Endstone, Nukkit/PowerNukkit, PocketMine/PMMP, Bedrock,
BDSX or LeviLamina. Custom eggs selected as Minecraft Bedrock in the server's game
query settings use conservative official-BDS commands. A type of None disables
this addon. Other games and standalone proxies are outside this release's scope.

Allow UDP on the assigned Bedrock game port, usually 19132, from the panel host.
The addon uses the primary allocation for RakNet; it does not send Java TCP status
or Java Query requests. Console rosters still work when global game queries are
disabled. The server must run, and your browser must reach its Wings websocket.
Roster parsing accepts standard English `list` output with native logger prefixes.
Localized or plugin-customized output may require an exporter or manual lookup.
Nukkit's native list can use display names; enter the actual gamertag when a plugin
changes those names.

## Permissions

Console permission (`control.console`) is required for the page and native actions.
The roster sends only the fixed `list` command, through an authenticated endpoint.
OP/de-OP additionally require server ownership or panel admin access. Inventory
also requires `file.read` and `file.read-content`. Every endpoint enforces server
membership and its permissions. User capabilities are never shared in the cache.

The roster uses a separate authenticated websocket and requests no old log replay.
It closes on result, timeout, errors, cancellation or page exit. Duplicate names,
invalid targets, incomplete replies and status-only results are labelled accurately.

## Inventory snapshot exports

Bedrock world storage is not Java `playerdata/*.dat`. Inventory/HUD inspection
requires a server-side exporter writing `/bedrock-player-snapshots.json` in the
game server root. The panel never opens or changes a live Bedrock LevelDB world.
The core listing and moderation functions require no game plugin.

Ready-to-install Nukkit, Endstone and PocketMine exporters are provided separately
in `dist/`. See [bridges/README.md](bridges/README.md) for installation, API targets
and limitations. They are not automatically installed on your game servers.

The file is UTF-8 JSON, at most 2 MiB, with at most 1,000 player records:

```json
{
  "schema": "bedrockplayers.v1",
  "saved_at": "2026-10-13T12:00:00Z",
  "players": [{
    "name": "Gamer Tag",
    "xuid": "1234567890123456",
    "saved_at": "2026-10-13T12:00:00Z",
    "health": 18,
    "food": 19,
    "xp_level": 12,
    "xp_progress": 0.375,
    "selected_slot": 0,
    "dimension": "minecraft:overworld",
    "position": [12, 64, -10],
    "inventory": [{"slot": 0, "id": "minecraft:diamond_sword", "count": 1, "metadata": {}}],
    "ender_chest": [],
    "state": {"operator": false, "banned": false, "whitelisted": true}
  }]
}
```

Use slot 0–8 for the hotbar, 9–35 for the main inventory, 100/101/102/103 for
feet/legs/chest/head and 150 for offhand. Ender Chest slots are 0–26. Item IDs must
be namespaced identifiers. `xp_progress` is a fraction from 0 to 1, independently
of `xp_level`. Missing vitals and player state remain unknown. Publishers should
write atomically and keep each player's original `saved_at` when retaining offline
records. Exports are snapshots, and do not offer item editing.

Item images and HUD textures use the same credited assets as the Java release;
the default inventory model is shown. Custom pack models and Bedrock-specific
items without an image use fallback icons. HUD sprites are bundled; item images
use a pinned mc-assets collection through jsDelivr.

## Build and checks

```bash
npm ci --ignore-scripts
node --test bedrock/tests/query.test.mjs
python tools/package.py bedrock
python tools/verify_package.py bedrock
python tools/package_bridges.py
python bedrock/tests/test_endstone_bridge.py
```

UI checks use the installed panel's Vitest runner. PHP fixtures use in-memory
models, cache and fake Wings repositories. Punitive/privilege-changing commands
are never tested on a real player. Validation is recorded in VALIDATION.md.

Copy `bedrock/tests/*.spec.ts*` into an installed panel's
`resources/scripts/bedrockplayers-tests/`, then run
`pnpm exec vitest run resources/scripts/bedrockplayers-tests/`. Run the PHP
fixtures with `php bedrock/tests/backend.php /app`; when copied into a container,
also copy the original `tests/backend.php` to `/tmp/mcplayers-tests/backend.php`
so the shared fixtures are available.

Update with `blueprint -i bedrockplayers`; remove with
`blueprint -r bedrockplayers`. There are no database migrations.

## License and references

Addon code uses the BSD 2-Clause licence in LICENSE. Game graphics keep their
separate source credits in components/hud/README.md. Command/protocol references:
[Microsoft list](https://learn.microsoft.com/en-us/minecraft/creator/commands/commands/list?view=minecraft-bedrock-stable),
[Microsoft kick](https://learn.microsoft.com/en-us/minecraft/creator/commands/commands/kick?view=minecraft-bedrock-stable),
[Nukkit commands](https://github.com/CloudburstMC/Nukkit/tree/master/src/main/java/cn/nukkit/command/defaults),
[Endstone changelog](https://github.com/EndstoneMC/endstone/blob/main/CHANGELOG.md),
[PocketMine commands](https://github.com/pmmp/PocketMine-MP/tree/stable/src/command/defaults).
