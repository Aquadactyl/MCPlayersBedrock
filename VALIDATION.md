# Bedrock version validation

Initially verified on 13 October 2026 against the local Aquadactyl/Blueprint beta-2026-08
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

The initial validation used protocol fixtures and recorded standard console
responses for BDS, Nukkit/PocketMine and Endstone, without a live Bedrock server;
this does not substitute for live testing of every server distribution/version.
Inventory/HUD requires the documented snapshot export rather than a Java save file.
During that initial validation, no game server settings were changed, no exporters
were installed in a live game server, and no punitive or privilege-changing
commands were sent. The Java addon
remains separately installed. Browser screenshots/manual visual review were not
available; interface behaviour was verified in the panel's DOM test environment.

## 14 October 2026: Endstone roster fix, version 1.0.1

Endstone's `[12:35:17 INFO]:` prefix and interactive `>` prompt previously
prevented the console parser from recognizing `list` replies. The parser now
accepts the colon and strips prompts immediately before a recognized logger
prefix. It continues to ignore command echoes and player chat, and preserves
gamertags containing spaces.

All seven addon frontend test files passed (57 tests). Regression tests cover the
reported two-line `Rep Graphics` roster, colored logger prefixes, actual Endstone
ANSI cursor-control frames, and separate authenticated websocket events.

The installed parser was also checked against fresh, authenticated Wings output
on the local Docker deployment: official BDS 1.26.52.3 returned a complete empty
roster (0/10), and Endstone 0.11.14 returned a complete roster (1/10), containing
the connected player `Rep Graphics`. Only the fixed `list` command was submitted;
game servers were not restarted. The temporary validation API key was revoked.
