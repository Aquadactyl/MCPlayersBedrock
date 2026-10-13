import assert from "node:assert/strict";
import { test } from "node:test";
import dgram from "node:dgram";
import { once } from "node:events";
import { queryPlayers } from "../private/query.mjs";

async function bedrockFixture(t, online = 2) {
  const socket = dgram.createSocket("udp4");
  socket.on("message", (ping, remote) => {
    const status = Buffer.from(
      `MCPE;Fixture;827;1.21.100;${online};30;123456789;world;Survival;1;19132;19133;`,
    );
    const size = Buffer.alloc(2);
    size.writeUInt16BE(status.length);
    socket.send(
      Buffer.concat([
        Buffer.from([0x1c]),
        ping.subarray(1, 9),
        Buffer.alloc(8),
        Buffer.from("00ffff00fefefefefdfdfdfd12345678", "hex"),
        size,
        status,
      ]),
      remote.port,
      remote.address,
    );
  });
  socket.bind(0, "127.0.0.1");
  await once(socket, "listening");
  t.after(() => new Promise((resolve) => socket.close(resolve)));
  return { host: "127.0.0.1", port: socket.address().port };
}
test("uses real RakNet UDP counts without inventing a roster", async (t) => {
  const result = await queryPlayers(await bedrockFixture(t));
  assert.equal(result.status, "available");
  assert.equal(result.source, "bedrock_status");
  assert.equal(result.online, 2);
  assert.equal(result.maximum, 30);
  assert.equal(result.complete, false);
  assert.deepEqual(result.players, []);
});
test("a zero-player UDP reply is still a status source", async (t) => {
  const result = await queryPlayers(await bedrockFixture(t, 0));
  assert.equal(result.online, 0);
  assert.equal(result.complete, false);
});
test("rejects unsafe targets before sending any packet", async () => {
  for (const target of [
    null,
    {},
    { host: "localhost/path", port: 19132 },
    { host: "host\nname", port: 19132 },
    { host: "localhost", port: 0 },
  ]) {
    assert.equal(
      (await queryPlayers(target, () => assert.fail("No query expected"))).status,
      "unavailable",
    );
  }
});
test("rejects non-Bedrock editions and malformed counts", async () => {
  for (const response of [
    { raw: { edition: "OTHER" }, numplayers: 1, maxplayers: 30 },
    { raw: { edition: "MCPE" }, numplayers: -1, maxplayers: 30 },
    { raw: { edition: "MCPE" }, numplayers: 1, maxplayers: Infinity },
  ]) {
    assert.equal(
      (await queryPlayers({ host: "localhost", port: 19132 }, async () => response)).status,
      "unavailable",
    );
  }
});
