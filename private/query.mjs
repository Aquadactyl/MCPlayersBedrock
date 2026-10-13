import { GameDig } from "gamedig";
import { pathToFileURL } from "node:url";

export const unavailable = () => ({
  status: "unavailable",
  source: null,
  complete: false,
  online: null,
  maximum: null,
  players: [],
});
export const validTarget = (target) =>
  target &&
  typeof target.host === "string" &&
  target.host.length > 0 &&
  target.host.length <= 253 &&
  !/[\s/\\\p{C}]/u.test(target.host) &&
  Number.isInteger(target.port) &&
  target.port > 0 &&
  target.port <= 65535;

export async function queryPlayers(target, query = (options) => GameDig.query(options)) {
  if (!validTarget(target)) return unavailable();
  try {
    const result = await query({
      type: "protocol-minecraftbedrock",
      host: target.host,
      port: target.port,
      givenPortOnly: true,
      maxRetries: 0,
      socketTimeout: 900,
      attemptTimeout: 1800,
      portCache: false,
    });
    if (
      !["MCPE", "MCEE"].includes(result.raw?.edition) ||
      ![result.numplayers, result.maxplayers].every(
        (value) => Number.isInteger(value) && value >= 0 && value <= 1000000,
      )
    )
      throw new Error("Invalid Bedrock reply");
    return {
      status: "available",
      source: "bedrock_status",
      complete: false,
      online: result.numplayers,
      maximum: result.maxplayers,
      players: [],
    };
  } catch {
    return unavailable();
  }
}

async function main() {
  const finish = (result) => {
    process.stdout.write(JSON.stringify(result));
    process.exit(0);
  };
  const timeout = setTimeout(() => finish(unavailable()), 4500);
  let input = "";
  process.stdin.setEncoding("utf8");
  for await (const chunk of process.stdin) {
    input += chunk;
    if (input.length > 4096) return finish(unavailable());
  }
  try {
    const result = await queryPlayers(JSON.parse(input));
    clearTimeout(timeout);
    finish(result);
  } catch {
    finish(unavailable());
  }
}
if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) await main();
