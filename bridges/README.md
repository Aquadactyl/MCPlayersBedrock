# Optional inventory snapshot exporters

The panel addon works without these plugins for names, counts and moderation.
To inspect inventory/HUD, install the matching exporter in the game server.
They read player APIs and write only `bedrock-player-snapshots.json` (and a temporary
file) in the server root, every 100 ticks. They execute no commands, change no
items and open no network listener. Each export is bounded to 2 MiB / 1,000 records.
Last-known offline snapshots retain the player's original timestamp.

| Platform | Ready-to-install artifact | Target |
| --- | --- | --- |
| Nukkit | `dist/bedrock-snapshots-nukkit-1.0.0.jar` | Compiled against Cloudburst Nukkit 1.0 snapshot `1.0-20260919.123832-1250`, Java 8 bytecode |
| Endstone | `dist/endstone_bedrock_snapshots-1.0.0-py3-none-any.whl` | Endstone API 0.11, Python 3.10+ |
| PocketMine | `dist/bedrock-snapshots-pocketmine-1.0.0.phar` | PocketMine API 5.0 |

Copy the matching artifact into that server's `plugins` directory and restart
it when appropriate for your players. Endstone's wheel uses its plugin loader;
it does not install, upgrade or downgrade the Endstone runtime. Check the console
for successful plugin loading, then open Saved inventory in the panel. File-read
permissions are required there. The exporters are optional and have not been
installed on your existing game servers.

Stock official BDS has no plugin loader, so these plugins cannot be installed
there. Its inventory requires a separate compatible export producer; automated
stock-BDS inventory extraction is not included. Endstone's 0.11 Python API may
not expose hunger, in which case Food is correctly labelled Unknown. Missing
server API fields and unrecognized item images should not be interpreted as zero.

Nukkit forks can have API differences: rebuild against your fork if its loader
rejects the compiled artifact. The source is in `nukkit/`. Compile the Java source
with a Nukkit server JAR on the classpath, then package the classes and plugin.yml
at the archive root. Endstone's wheel can be rebuilt with
`python tools/package_bridges.py`. PocketMine's PHAR can be built with:

```bash
php -d phar.readonly=0 bedrock/bridges/pocketmine/build.php bedrock/bridges/pocketmine OUTPUT.phar
```

Source APIs:
[Endstone Python API](https://github.com/EndstoneMC/endstone/tree/main/endstone),
[Nukkit Player](https://github.com/CloudburstMC/Nukkit/blob/master/src/main/java/cn/nukkit/Player.java),
[PocketMine Human](https://github.com/pmmp/PocketMine-MP/blob/stable/src/entity/Human.php).
