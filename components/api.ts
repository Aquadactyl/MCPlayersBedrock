import http, { httpErrorToHuman } from "@/api/http";
import { readConsoleRoster } from "./consoleRoster";

export interface Player {
  name: string;
  uuid: null;
}
export type Software = "bds" | "endstone" | "nukkit" | "pocketmine";
export type PlayerAction =
  | "kick"
  | "ban"
  | "pardon"
  | "op"
  | "deop"
  | "whitelist_add"
  | "whitelist_remove";
export interface PlayerCapabilities {
  moderate: boolean;
  operator: boolean;
  inventory: boolean;
  actions: PlayerAction[];
}
export interface PlayerList {
  status: "available" | "unavailable" | "unsupported" | "pending";
  source: "console" | "bedrock_status" | null;
  complete: boolean;
  online: number | null;
  maximum: number | null;
  players: Player[];
  checked_at: string | null;
  software: Software | null;
  capabilities?: PlayerCapabilities;
  roster_error?: string;
}
export interface InventoryItem {
  slot: number;
  id: string;
  count: number;
  metadata: unknown;
  metadata_truncated: boolean;
}
export interface InventorySnapshot {
  source: "snapshot_export";
  player: string;
  uuid: null;
  xuid: string | null;
  saved_at: string | null;
  read_at: string;
  inventory: InventoryItem[];
  ender_chest: InventoryItem[];
  health: number | null;
  food: number | null;
  xp_level: number | null;
  xp_progress?: number | null;
  selected_slot: number | null;
  dimension: string | null;
  position: number[] | null;
  data_version: number | null;
  state: { operator: boolean | null; banned: boolean | null; whitelisted: boolean | null };
}

export async function getPlayers(uuid: string, signal?: AbortSignal): Promise<PlayerList> {
  const { data } = await http.get<{ attributes: PlayerList }>(
    `/api/client/extensions/bedrockplayers/servers/${uuid}/players`,
    { signal },
  );
  const status = data.attributes;
  if (!status.software || status.status === "unsupported") return status;
  try {
    const roster = await readConsoleRoster(uuid, signal);
    return { ...status, ...roster, checked_at: new Date().toISOString() };
  } catch (error) {
    const code = (error as { response?: { status?: number } }).response?.status;
    if (signal?.aborted || (code && [401, 403, 404, 429].includes(code))) throw error;
    return { ...status, roster_error: httpErrorToHuman(error) };
  }
}
export async function sendPlayerAction(
  uuid: string,
  name: string,
  action: PlayerAction,
  reason = "",
) {
  await http.post(
    `/api/client/extensions/bedrockplayers/servers/${uuid}/players/${encodeURIComponent(name)}/actions`,
    { action, reason: reason || null, confirmed: true },
  );
}
export async function getInventory(
  uuid: string,
  player: Player,
  signal?: AbortSignal,
): Promise<InventorySnapshot> {
  const { data } = await http.get<{ attributes: InventorySnapshot }>(
    `/api/client/extensions/bedrockplayers/servers/${uuid}/players/${encodeURIComponent(player.name)}/inventory`,
    { signal },
  );
  return data.attributes;
}
