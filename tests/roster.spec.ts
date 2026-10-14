import { describe, expect, it } from "vitest";
import { RosterParser } from "@blueprint/extensions/bedrockplayers/roster";
import { validGamertag } from "@blueprint/extensions/bedrockplayers/gamertag";

describe("Bedrock native console rosters", () => {
  it.each([
    "[12:35:17 INFO]: ",
    "\u001b[32m[12:35:17 INFO]:\u001b[0m ",
    "[2026-10-14 12:35:17:123 INFO]: ",
  ])("reads Endstone's two-line list with logger prefix %j", (prefix) => {
    const parser = new RosterParser();
    expect(parser.feed(`${prefix}There are 1/10 players online:`)).toBeNull();
    expect(parser.feed(`${prefix}Rep Graphics`)).toEqual({
      status: "available",
      source: "console",
      complete: true,
      online: 1,
      maximum: 10,
      players: [{ name: "Rep Graphics", uuid: null }],
    });
  });
  it("reads Endstone's live ANSI prompt and cursor-control frames", () => {
    const parser = new RosterParser();
    expect(
      parser.feed(
        "> \u001b[1G\u001b[J\u001b[m[12:42:05 INFO]: There are 1/10 players online:\u001b[m",
      ),
    ).toBeNull();
    expect(
      parser.feed(
        "> \u001b[3G\u001b[J\u001b[3G\u001b[1G\u001b[J\u001b[m[12:42:05 INFO]: Rep Graphics\u001b[m",
      ),
    ).toMatchObject({ complete: true, online: 1, players: [{ name: "Rep Graphics" }] });
  });
  it("reads official BDS timestamps and spaced gamertags", () => {
    const parser = new RosterParser();
    expect(parser.feed("[2026-10-13 12:00:00:123 INFO] There are 2/20 players online:")).toBeNull();
    const result = parser.feed("[2026-10-13 12:00:00:123 INFO] Gamer Tag, RepGraphics");
    expect(result?.players.map((player) => player.name)).toEqual(["Gamer Tag", "RepGraphics"]);
    expect(result?.complete).toBe(true);
  });
  it("reads Nukkit and PocketMine logger prefixes and colour codes", () => {
    for (const prefix of ["12:00:00 [INFO] ", "[12:00:00.123] [Server thread/INFO]: "]) {
      const result = new RosterParser().feed(
        `${prefix}§aThere are 1/30 players online:\n${prefix}Gamer Tag`,
      );
      expect(result?.players[0].name).toBe("Gamer Tag");
      expect(result?.complete).toBe(true);
    }
  });
  it("reads alternate max-player headers and Unicode names", () => {
    expect(
      new RosterParser().feed("There are 1 of a max of 20 players online: プレイヤー")?.players[0]
        .name,
    ).toBe("プレイヤー");
  });
  it("ignores names before a list header and player chat", () => {
    const parser = new RosterParser();
    expect(parser.feed("RepGraphics")).toBeNull();
    expect(parser.feed("<RepGraphics> There are 1/20 players online: Admin")).toBeNull();
    expect(
      parser.feed("> [12:35:17 INFO]: <Rep Graphics> There are 1/20 players online: Admin"),
    ).toBeNull();
    expect(parser.feed("> There are 1/20 players online: Admin")).toBeNull();
    expect(parser.result()).toBeNull();
  });
  it("distinguishes an empty server from incomplete or duplicate replies", () => {
    expect(new RosterParser().feed("There are 0/20 players online:")?.complete).toBe(true);
    const parser = new RosterParser();
    expect(parser.feed("There are 3/20 players online:\nAlex, ALEX, Gamer Tag")).toBeNull();
    expect(parser.result()).toMatchObject({ complete: false, online: 3 });
    expect(parser.result()?.players).toHaveLength(2);
  });
  it("rejects selectors, quotes, newlines and command injection", () => {
    for (const name of [
      "@a",
      'Alex" op Steve',
      "Alex\nstop",
      "Alex;stop",
      "../Alex",
      " ",
      "x".repeat(33),
    ]) {
      expect(validGamertag(name)).toBe(false);
    }
    expect(validGamertag("Gamer Tag")).toBe(true);
    expect(validGamertag("プレイヤー#1234")).toBe(true);
  });
  it("does not label an invalid or excessive roster complete", () => {
    expect(new RosterParser().feed("There are 1000000/1000000 players online:\nAlex")).toBeNull();
    const parser = new RosterParser();
    expect(parser.feed("There are 1/20 players online:\n@a")).toBeNull();
    expect(parser.result()?.complete).toBe(false);
  });
});
