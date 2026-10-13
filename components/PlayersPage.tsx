import React, { useEffect, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import {
  Check,
  Copy,
  Info,
  LoaderCircle,
  RefreshCw,
  Search,
  Server,
  ShieldCheck,
  Users,
  WifiOff,
  UserRoundCog,
} from "lucide-react";
import { ServerContext } from "@/state/server";
import ServerContentBlock from "@/components/elements/ServerContentBlock";
import Button from "@/components/elements/Button";
import Input from "@/components/elements/Input";
import { httpErrorToHuman } from "@/api/http";
import { getPlayers, Player } from "./api";
import PlayerAvatar from "./PlayerAvatar";
import PlayerDialog from "./PlayerDialog";
import { validGamertag } from "./gamertag";
import "./players.css";

export default function PlayersPage() {
  const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
  const [search, setSearch] = useState("");
  const [automatic, setAutomatic] = useState(true);
  const [notice, setNotice] = useState("");
  const [lookup, setLookup] = useState("");
  const [selectedPlayer, setSelectedPlayer] = useState<Player | null>(null);
  useEffect(() => {
    setSelectedPlayer(null);
  }, [uuid]);
  const query = useQuery({
    queryKey: ["bedrockplayers", uuid],
    queryFn: ({ signal }) => getPlayers(uuid, signal),
    staleTime: 10000,
    retry: false,
    refetchInterval: (current) => {
      if (!automatic || current.state.data?.status === "unsupported") return false;
      const status = (current.state.error as { response?: { status?: number } } | null)?.response
        ?.status;
      if (status && [401, 403, 404, 409, 429].includes(status)) return false;
      return current.state.data?.status === "pending" ? 2000 : 15000;
    },
    refetchIntervalInBackground: false,
    refetchOnWindowFocus: true,
  });

  useEffect(() => {
    if (!notice) return;
    const timeout = window.setTimeout(() => setNotice(""), 3000);
    return () => window.clearTimeout(timeout);
  }, [notice]);

  const data = query.data;
  const capabilities = data?.capabilities ?? {
    moderate: false,
    operator: false,
    inventory: false,
    actions: [],
  };
  const available = data?.status === "available";
  const unsupported = data?.status === "unsupported";
  const busy = query.isFetching;
  const players = data?.players ?? [];
  const filtered = players.filter((player) =>
    player.name.toLowerCase().includes(search.trim().toLowerCase()),
  );
  const copy = async (value: string, description: string) => {
    try {
      await navigator.clipboard.writeText(value);
      setNotice(description);
    } catch {
      setNotice("Copy failed. Select the name and copy it manually.");
    }
  };
  const updated = data?.checked_at
    ? new Date(data.checked_at).toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
      })
    : null;
  const health = query.isError
    ? "Update failed"
    : available
      ? "Online"
      : unsupported
        ? "Unsupported"
        : query.isPending || data?.status === "pending"
          ? "Checking"
          : "Unreachable";

  return (
    <ServerContentBlock title="Players">
      <div className="mbp-page">
        <header className="mbp-header">
          <div className="mbp-heading">
            <span className="mbp-heading-icon">
              <Users size={25} aria-hidden />
            </span>
            <div>
              <p className="mbp-eyebrow">Minecraft Bedrock</p>
              <h1>Bedrock Players</h1>
              <p className="mbp-subtitle">
                Player rosters, server actions and exported inventories.
              </p>
            </div>
          </div>
          <div className="mbp-header-actions">
            <span
              className={`mbp-status ${available && !query.isError ? "mbp-status-online" : ""}`}
            >
              <span aria-hidden />
              {health}
            </span>
            <Button
              color="grey"
              disabled={busy}
              onClick={() => void query.refetch()}
              aria-label="Refresh player list"
            >
              <span className="mbp-button-content">
                <RefreshCw size={16} className={busy ? "mbp-spin" : ""} aria-hidden />
                Refresh
              </span>
            </Button>
          </div>
        </header>

        {query.isError && (
          <div className="mbp-alert mbp-alert-warning" role="alert">
            <WifiOff size={19} aria-hidden />
            <div>
              <strong>Could not update the player list.</strong>
              <p>
                {httpErrorToHuman(query.error)}{" "}
                {data && "The last result is shown below; it may be out of date."}
              </p>
            </div>
          </div>
        )}

        {!unsupported && (
          <div className="mbp-stats">
            <div className="mbp-stat">
              <Users size={19} aria-hidden />
              <span>{query.isError ? "Last reported online" : "Players online"}</span>
              <strong>
                {available ? data.online : "—"}
                <small> / {available ? data.maximum : "—"}</small>
              </strong>
            </div>
            <div className="mbp-stat">
              <ShieldCheck size={19} aria-hidden />
              <span>Names returned</span>
              <strong>
                {available ? players.length : "—"}
                <small>{available && (data.complete ? "full list" : "partial list")}</small>
              </strong>
            </div>
            <div className="mbp-stat">
              <Server size={19} aria-hidden />
              <span>List source</span>
              <strong className="mbp-stat-source">
                {available ? (data.source === "console" ? "Console list" : "Bedrock status") : "—"}
                <small>
                  {available
                    ? data.source === "console"
                      ? "Authenticated console"
                      : "UDP counts only"
                    : "Waiting for response"}
                </small>
              </strong>
            </div>
          </div>
        )}

        {available && !data.complete && (
          <div className="mbp-alert" role="status">
            <Info size={19} aria-hidden />
            <div>
              <strong>
                {data.source === "bedrock_status"
                  ? "Bedrock status does not include player names."
                  : "The server returned a partial player list."}
              </strong>
              <p>
                {data.source === "bedrock_status"
                  ? "Counts are available, but the console roster could not be read. Start the server, check console connectivity and use an English list response."
                  : "Some names were missing or could not be displayed. The reported online count is shown above."}
              </p>
            </div>
          </div>
        )}

        {data?.roster_error && (
          <p className="mbp-tool-error" role="alert">
            {data.roster_error}
          </p>
        )}

        {(capabilities.moderate || capabilities.inventory) && (
          <form
            className="mbp-lookup"
            onSubmit={(event) => {
              event.preventDefault();
              const name = lookup.trim();
              if (validGamertag(name))
                setSelectedPlayer({
                  name,
                  uuid:
                    players.find((player) => player.name.toLowerCase() === name.toLowerCase())
                      ?.uuid ?? null,
                });
            }}
          >
            <label htmlFor="mbp-lookup">Open any player, including offline players</label>
            <div>
              <Input
                id="mbp-lookup"
                value={lookup}
                onChange={(event) => setLookup(event.target.value)}
                placeholder="Bedrock gamertag"
                maxLength={32}
                required
              />
              <Button type="submit" color="grey">
                <span className="mbp-button-content">
                  <UserRoundCog size={16} aria-hidden />
                  Open player
                </span>
              </Button>
            </div>
          </form>
        )}

        <section className="mbp-panel" aria-label="Player list" aria-busy={busy}>
          <div className="mbp-toolbar">
            <div>
              <h2>{query.isError && data ? "Last reported players" : "Player list"}</h2>
              <p>
                {available
                  ? `${players.length} ${players.length === 1 ? "name" : "names"} returned · Sorted A–Z`
                  : "Live names from your Minecraft server"}
              </p>
            </div>
            <div className="mbp-toolbar-actions">
              <label className="mbp-search">
                <Search size={17} aria-hidden />
                <Input
                  type="search"
                  aria-label="Search players"
                  placeholder="Search players…"
                  value={search}
                  onChange={(event) => setSearch(event.target.value)}
                  disabled={!players.length}
                />
              </label>
              <Button
                isSecondary
                disabled={!players.length}
                onClick={() =>
                  void copy(players.map((player) => player.name).join("\n"), "Player names copied.")
                }
              >
                <span className="mbp-button-content">
                  <Copy size={15} aria-hidden />
                  Copy names
                </span>
              </Button>
            </div>
          </div>

          {query.isPending || data?.status === "pending" ? (
            <div className="mbp-empty">
              <LoaderCircle size={30} className="mbp-spin" aria-hidden />
              <h3>Checking your server</h3>
              <p>Retrieving the latest player list…</p>
            </div>
          ) : unsupported ? (
            <div className="mbp-empty">
              <Server size={30} aria-hidden />
              <h3>Minecraft Bedrock servers only</h3>
              <p>
                Choose a Bedrock, Endstone, Nukkit or PocketMine egg, or select Minecraft Bedrock as
                the game query type in the server's admin settings. A query type of None disables
                this addon.
              </p>
            </div>
          ) : !available ? (
            <div className="mbp-empty">
              <WifiOff size={30} aria-hidden />
              <h3>
                {query.isError ? "Player list unavailable" : "Your server could not be reached"}
              </h3>
              <p>
                {query.isError
                  ? "Try refreshing once access or connectivity is restored."
                  : "The server may be offline, status may be disabled, or a firewall may be blocking the panel. Start the server and check the setup below."}
              </p>
            </div>
          ) : !players.length ? (
            <div className="mbp-empty">
              <Users size={30} aria-hidden />
              <h3>{data.online === 0 ? "No players online" : "Player names are hidden"}</h3>
              <p>
                {data.online === 0
                  ? "Players will appear here when they join your server."
                  : "The server reports players online but no console roster is available. Check the connection and console list output."}
              </p>
            </div>
          ) : !filtered.length ? (
            <div className="mbp-empty">
              <Search size={30} aria-hidden />
              <h3>No matching players</h3>
              <p>Try a different player name.</p>
            </div>
          ) : (
            <ul className="mbp-player-grid">
              {filtered.map((player) => (
                <li className="mbp-player" key={player.name.toLowerCase()}>
                  <PlayerAvatar name={player.name} />
                  <div className="mbp-player-info">
                    <strong>{player.name}</strong>
                    <span>{query.isError ? "Last reported" : "Reported online"}</span>
                  </div>
                  <button
                    type="button"
                    className="mbp-copy"
                    onClick={() => void copy(player.name, `${player.name} copied.`)}
                    aria-label={`Copy ${player.name}`}
                    title="Copy username"
                  >
                    <Copy size={16} aria-hidden />
                  </button>
                  <button
                    type="button"
                    className="mbp-copy"
                    aria-label={`Manage ${player.name}`}
                    title="Player profile and actions"
                    onClick={() => setSelectedPlayer(player)}
                  >
                    <UserRoundCog size={17} aria-hidden />
                  </button>
                </li>
              ))}
            </ul>
          )}

          <footer className="mbp-footer">
            <span>
              {updated ? `Last checked at ${updated}` : "No result yet"}
              {available && search.trim()
                ? ` · Showing ${filtered.length} of ${players.length}`
                : ""}
            </span>
            <label>
              <Input
                type="checkbox"
                checked={automatic}
                onChange={(event) => setAutomatic(event.target.checked)}
              />
              <span>Auto-refresh every 15s</span>
            </label>
          </footer>
        </section>

        <p className="mbp-notice" role="status" aria-live="polite">
          {notice && (
            <>
              <Check size={15} aria-hidden />
              {notice}
            </>
          )}
        </p>

        {selectedPlayer && (
          <PlayerDialog
            key={`${uuid}:${selectedPlayer.name}`}
            uuid={uuid}
            player={selectedPlayer}
            capabilities={capabilities}
            software={data?.software ?? "bds"}
            onClose={() => setSelectedPlayer(null)}
            onNotice={setNotice}
          />
        )}

        <details className="mbp-setup">
          <summary>
            <Info size={17} aria-hidden />
            Bedrock server setup
          </summary>
          <div>
            <p>
              The addon reads the server's native <code>list</code> command through an authenticated
              console connection. It also uses Bedrock UDP status for counts when the roster is
              unavailable.
            </p>
            <p>
              Allow UDP on your assigned Bedrock game port (usually <code>19132</code>) from the
              panel host. Server commands require a running server. OP and de-OP require the owner
              or a panel admin.
            </p>
            <p>
              Inventory and HUD inspection require an exported Bedrock snapshot file and file read
              permissions. Java player saves and Java account skins are not used for Bedrock
              gamertags.
            </p>
          </div>
        </details>
      </div>
    </ServerContentBlock>
  );
}
