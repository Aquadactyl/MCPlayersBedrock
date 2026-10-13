import http from "@/api/http";
import getWebsocketToken from "@/api/server/getWebsocketToken";
import { Websocket } from "@/plugins/Websocket";
import { ConsoleRoster, RosterParser } from "./roster";

export function readConsoleRoster(uuid: string, signal?: AbortSignal): Promise<ConsoleRoster> {
  return new Promise((resolve, reject) => {
    // A dedicated connection receives fresh output only. Never request replayed console logs.
    const socket = new Websocket();
    const parser = new RosterParser();
    let settled = false;
    let armed = false;
    const finish = (result: ConsoleRoster | null, error?: unknown) => {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      signal?.removeEventListener("abort", abort);
      socket.removeAllListeners();
      socket.close();
      if (result) resolve(result);
      else reject(error ?? new Error("The console did not return a player list."));
    };
    const abort = () => finish(null, new DOMException("Roster request cancelled", "AbortError"));
    const timer = window.setTimeout(() => {
      const partial = parser.result();
      finish(
        partial
          ? { ...partial, roster_error: "The console returned an incomplete player list." }
          : null,
      );
    }, 8000);
    signal?.addEventListener("abort", abort, { once: true });
    if (signal?.aborted) {
      abort();
      return;
    }
    socket.on("console output", (line: string) => {
      if (!armed || typeof line !== "string") return;
      const result = parser.feed(line);
      if (result) finish(result);
    });
    socket.on("auth success", () => {
      if (settled || armed) return;
      armed = true;
      http
        .post(`/api/client/extensions/bedrockplayers/servers/${uuid}/players/list`, {}, { signal })
        .catch((error) => finish(null, error));
    });
    for (const event of [
      "jwt error",
      "daemon error",
      "SOCKET_CONNECT_ERROR",
      "SOCKET_CLOSE",
      "SOCKET_ERROR",
    ]) {
      socket.on(event, () =>
        finish(null, new Error("Could not read the authenticated server console.")),
      );
    }
    getWebsocketToken(uuid)
      .then(({ token, socket: url }) => {
        if (!settled) socket.setToken(token).connect(url);
      })
      .catch((error) => finish(null, error));
  });
}
