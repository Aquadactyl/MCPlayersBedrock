# Bedrock version validation

Verified on 13 October 2026 against the local Aquadactyl/Blueprint beta-2026-08
panel, PHP 8.5, Node 24, React 19 and its installed Vitest/TypeScript tooling.

| Check | Result |
| --- | --- |
| Real UDP RakNet protocol fixtures | 4 passed |
| Bedrock backend software, commands, permissions, cache and exports | 64 passed |
| Roster/parser, websocket lifecycle, API, page, dialog, images and HUD | 52 passed |
| Installed HTTP auth/membership/platform checks | 9 passed |
| Endstone read-only exporter and atomic/offline retention checks | 3 passed |
| Frontend TypeScript | Passed |
| Addon frontend lint | Passed; no warnings |
| Blueprint installation and update / production build | Passed |
| Nukkit exporter | Compiled against actual Cloudburst Nukkit SDK, Java 8 target |
| PocketMine exporter | PHP syntax and PHAR build passed |
| Endstone exporter | Pure-Python wheel built; API 0.11 interfaces checked against upstream stubs |
| Package integrity | ZIP, original HUD PNG bytes, PNG chunks and SHA256 verified |

Backend fixtures also run the existing Java addon's 31 baseline checks. The
native `list` and moderation dispatch tests use a fake Wings repository. HTTP
smoke tests target a non-Bedrock server to prove platform rejection, without
sending commands. Endstone exporter tests use public-API-shaped fixtures rather
than a running native server. Nukkit/PocketMine plugin loading and inventory
capture have not been tested live; compilation/syntax alone cannot prove runtime
compatibility with every fork or version.

There is no running Bedrock server in the current local stack. Protocol fixtures
and recorded standard console responses cover BDS, Nukkit/PocketMine and Endstone;
this does not substitute for live testing of every server distribution/version.
Inventory/HUD requires the documented snapshot export rather than a Java save file.
No game server settings were changed, no exporters were installed in a live game
server, and no punitive or privilege-changing commands were sent. The Java addon
remains separately installed. Browser screenshots/manual visual review were not
available; interface behaviour was verified in the panel's DOM test environment.
