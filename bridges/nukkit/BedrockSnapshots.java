package uk.nitrocraft.bedrocksnapshots;

import cn.nukkit.Player;
import cn.nukkit.item.Item;
import cn.nukkit.inventory.Inventory;
import cn.nukkit.plugin.PluginBase;
import com.google.gson.*;
import java.nio.charset.StandardCharsets;
import java.nio.file.*;
import java.time.Instant;
import java.util.*;

/** Read-only snapshot publisher. Does not execute commands or change items. */
public final class BedrockSnapshots extends PluginBase {
    private final Gson gson = new GsonBuilder().serializeNulls().create();
    private final LinkedHashMap<String, JsonObject> records = new LinkedHashMap<>();
    private final Path output = Paths.get("bedrock-player-snapshots.json").toAbsolutePath();

    @Override public void onEnable() {
        try {
            if (Files.exists(output) && Files.size(output) <= 2097152) {
                JsonObject root = JsonParser.parseString(new String(Files.readAllBytes(output), StandardCharsets.UTF_8)).getAsJsonObject();
                if ("bedrockplayers.v1".equals(root.get("schema").getAsString())) {
                    for (JsonElement element : root.getAsJsonArray("players")) {
                        JsonObject record = element.getAsJsonObject();
                        if (record.has("name")) records.put(record.get("name").getAsString().toLowerCase(Locale.ROOT), record);
                    }
                }
            }
        } catch (Exception ignored) { getLogger().warning("Previous snapshot export could not be read."); }
        getServer().getScheduler().scheduleRepeatingTask(this, this::publish, 100);
    }

    private Map<String, Object> stack(Item item, int slot) {
        if (item == null || item.getId() == 0 || item.getCount() <= 0) return null;
        Map<String, Object> result = new LinkedHashMap<>();
        String identifier;
        try { identifier = item.getRuntimeEntry().getIdentifier(); }
        catch (Exception unsupported) { identifier = "nukkit:" + item.getId(); }
        result.put("slot", slot); result.put("id", identifier); result.put("count", item.getCount());
        Map<String, Object> metadata = new LinkedHashMap<>();
        metadata.put("damage", item.getDamage()); metadata.put("name", item.getName());
        String nbt = String.valueOf(item.getNamedTag());
        metadata.put("nbt", nbt.substring(0, Math.min(17000, nbt.length())));
        result.put("metadata", metadata); return result;
    }

    private List<Map<String, Object>> contents(Inventory inventory, int limit) {
        List<Map<String, Object>> result = new ArrayList<>();
        for (int slot = 0; slot < limit; slot++) {
            Map<String, Object> item = stack(inventory.getItem(slot), slot);
            if (item != null) result.add(item);
        }
        return result;
    }

    private JsonObject capture(Player player) {
        Map<String, Object> result = new LinkedHashMap<>();
        List<Map<String, Object>> items = contents(player.getInventory(), 36);
        Item[] equipment = {player.getInventory().getBoots(), player.getInventory().getLeggings(), player.getInventory().getChestplate(), player.getInventory().getHelmet(), player.getOffhandInventory().getItem(0)};
        int[] slots = {100, 101, 102, 103, 150};
        for (int index = 0; index < equipment.length; index++) { Map<String, Object> item = stack(equipment[index], slots[index]); if (item != null) items.add(item); }
        int level = player.getExperienceLevel();
        int required = level >= 30 ? 112 + (level - 30) * 9 : level >= 15 ? 37 + (level - 15) * 5 : 7 + level * 2;
        result.put("name", player.getName()); result.put("xuid", player.getLoginChainData().getXUID());
        result.put("saved_at", Instant.now().toString()); result.put("inventory", items);
        result.put("ender_chest", contents(player.getEnderChestInventory(), 27));
        result.put("health", player.getHealth()); result.put("food", player.getFoodData().getLevel());
        result.put("xp_level", level); result.put("xp_progress", Math.max(0, Math.min(1, (double) player.getExperience() / required)));
        result.put("selected_slot", player.getInventory().getHeldItemIndex());
        result.put("dimension", player.getLevel().getName()); result.put("position", Arrays.asList(player.getX(), player.getY(), player.getZ()));
        Map<String, Object> state = new LinkedHashMap<>();
        state.put("operator", player.isOp()); state.put("banned", player.isBanned()); state.put("whitelisted", player.isWhitelisted());
        result.put("state", state); return gson.toJsonTree(result).getAsJsonObject();
    }

    public void publish() {
        try {
            for (Player player : getServer().getOnlinePlayers().values()) {
                String key = player.getName().toLowerCase(Locale.ROOT); records.remove(key); records.put(key, capture(player));
            }
            byte[] bytes;
            while (true) {
                JsonObject root = new JsonObject(); root.addProperty("schema", "bedrockplayers.v1"); root.addProperty("saved_at", Instant.now().toString());
                root.add("players", gson.toJsonTree(records.values())); bytes = gson.toJson(root).getBytes(StandardCharsets.UTF_8);
                if (records.size() <= 1000 && bytes.length <= 2097152) break;
                records.remove(records.keySet().iterator().next());
            }
            Path temporary = output.resolveSibling("bedrock-player-snapshots.json.tmp"); Files.write(temporary, bytes);
            try { Files.move(temporary, output, StandardCopyOption.REPLACE_EXISTING, StandardCopyOption.ATOMIC_MOVE); }
            catch (AtomicMoveNotSupportedException fallback) { Files.move(temporary, output, StandardCopyOption.REPLACE_EXISTING); }
        } catch (Exception error) { getLogger().warning("Snapshot export failed: " + error.getMessage()); }
    }
    @Override public void onDisable() { publish(); }
}
