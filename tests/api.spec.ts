import { beforeEach, describe, expect, it, vi } from "vitest";
import { getPlayers } from "@blueprint/extensions/bedrockplayers/api";
import { readConsoleRoster } from "@blueprint/extensions/bedrockplayers/consoleRoster";

const mocks = vi.hoisted(() => ({ get: vi.fn() }));
vi.mock("@/api/http", () => ({
  default: { get: mocks.get },
  httpErrorToHuman: (error: Error) => error.message,
}));
vi.mock("@blueprint/extensions/bedrockplayers/consoleRoster", () => ({
  readConsoleRoster: vi.fn(),
}));
const status = {
  status: "available",
  source: "bedrock_status",
  complete: false,
  online: 2,
  maximum: 20,
  players: [],
  software: "bds",
  checked_at: null,
};
const roster = {
  status: "available",
  source: "console",
  complete: true,
  online: 1,
  maximum: 20,
  players: [{ name: "Gamer Tag", uuid: null }],
} as const;
beforeEach(() => {
  vi.resetAllMocks();
  mocks.get.mockResolvedValue({ data: { attributes: status } });
  vi.mocked(readConsoleRoster).mockResolvedValue({ ...roster, players: [...roster.players] });
});

describe("Bedrock status and roster merging", () => {
  it("uses the authenticated list counts and names when available", async () => {
    expect(await getPlayers("server")).toMatchObject({
      source: "console",
      complete: true,
      online: 1,
      players: [{ name: "Gamer Tag" }],
    });
  });
  it("works through the console when UDP queries are unavailable", async () => {
    mocks.get.mockResolvedValue({
      data: { attributes: { ...status, status: "unavailable", online: null } },
    });
    expect((await getPlayers("server")).status).toBe("available");
  });
  it("keeps UDP counts with an explicit error when the console times out", async () => {
    vi.mocked(readConsoleRoster).mockRejectedValue(new Error("No console reply"));
    expect(await getPlayers("server")).toMatchObject({
      source: "bedrock_status",
      complete: false,
      players: [],
      roster_error: "No console reply",
    });
  });
  it("preserves permission errors and never connects unsupported games", async () => {
    const denied = { response: { status: 403 } };
    vi.mocked(readConsoleRoster).mockRejectedValue(denied);
    await expect(getPlayers("server")).rejects.toBe(denied);
    vi.mocked(readConsoleRoster).mockClear();
    mocks.get.mockResolvedValue({
      data: { attributes: { ...status, software: null, status: "unsupported" } },
    });
    expect((await getPlayers("server")).status).toBe("unsupported");
    expect(readConsoleRoster).not.toHaveBeenCalled();
  });
});
