import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { readConsoleRoster } from "@blueprint/extensions/bedrockplayers/consoleRoster";

const mocks = vi.hoisted(() => ({ sockets: [] as any[], token: vi.fn(), post: vi.fn() }));
vi.mock("@/api/http", () => ({ default: { post: mocks.post } }));
vi.mock("@/api/server/getWebsocketToken", () => ({ default: mocks.token }));
vi.mock("@/plugins/Websocket", () => ({
  Websocket: class {
    listeners = new Map<string, (...args: any[]) => void>();
    close = vi.fn();
    connect = vi.fn(() => this);
    setToken = vi.fn(() => this);
    constructor() {
      mocks.sockets.push(this);
    }
    on(event: string, callback: (...args: any[]) => void) {
      this.listeners.set(event, callback);
      return this;
    }
    emit(event: string, value?: string) {
      this.listeners.get(event)?.(value);
    }
    removeAllListeners() {
      this.listeners.clear();
    }
  },
}));
const start = async (signal?: AbortSignal) => {
  const promise = readConsoleRoster("bedrock-server", signal);
  await Promise.resolve();
  return { promise, socket: mocks.sockets[0] };
};
beforeEach(() => {
  vi.useFakeTimers();
  mocks.sockets.length = 0;
  mocks.token.mockResolvedValue({
    token: "private-test-token",
    socket: "wss://wings.example.test/socket",
  });
  mocks.post.mockResolvedValue({ data: { attributes: { status: "submitted" } } });
});
afterEach(() => {
  vi.useRealTimers();
  vi.clearAllMocks();
});

describe("authenticated Bedrock console reader", () => {
  it("waits for authentication and submits only the fixed list request", async () => {
    const { promise, socket } = await start();
    socket.emit("console output", "There are 1/20 players online: OldName");
    expect(mocks.post).not.toHaveBeenCalled();
    socket.emit("auth success");
    expect(mocks.post).toHaveBeenCalledExactlyOnceWith(
      "/api/client/extensions/bedrockplayers/servers/bedrock-server/players/list",
      {},
      { signal: undefined },
    );
    socket.emit("console output", "There are 1/20 players online:\nGamer Tag");
    expect((await promise).players[0].name).toBe("Gamer Tag");
    expect(socket.close).toHaveBeenCalledTimes(1);
    expect(socket.listeners.size).toBe(0);
  });
  it("closes and rejects when permission checks fail", async () => {
    const denied = { response: { status: 403 }, message: "Denied" };
    mocks.post.mockRejectedValue(denied);
    const { promise, socket } = await start();
    socket.emit("auth success");
    await expect(promise).rejects.toBe(denied);
    expect(socket.close).toHaveBeenCalledTimes(1);
  });
  it("aborts and closes a waiting request", async () => {
    const controller = new AbortController();
    const { promise, socket } = await start(controller.signal);
    controller.abort();
    await expect(promise).rejects.toMatchObject({ name: "AbortError" });
    expect(socket.close).toHaveBeenCalledTimes(1);
    expect(mocks.post).not.toHaveBeenCalled();
  });
  it("does not connect after cancellation while a token request is pending", async () => {
    let token!: (data: { token: string; socket: string }) => void;
    mocks.token.mockReturnValue(
      new Promise((resolve) => {
        token = resolve;
      }),
    );
    const controller = new AbortController();
    const { promise, socket } = await start(controller.signal);
    controller.abort();
    await expect(promise).rejects.toMatchObject({ name: "AbortError" });
    token({ token: "unused", socket: "wss://wings.example.test/socket" });
    await Promise.resolve();
    expect(socket.connect).not.toHaveBeenCalled();
  });
  it("labels partial results after the bounded response timeout", async () => {
    const { promise, socket } = await start();
    socket.emit("auth success");
    socket.emit("console output", "There are 2/20 players online:\nGamer Tag");
    await vi.advanceTimersByTimeAsync(8001);
    expect(await promise).toMatchObject({
      complete: false,
      online: 2,
      roster_error: "The console returned an incomplete player list.",
    });
    expect(socket.close).toHaveBeenCalledTimes(1);
  });
  it("handles websocket failure without leaving a connection running", async () => {
    const { promise, socket } = await start();
    socket.emit("SOCKET_ERROR");
    await expect(promise).rejects.toThrow("authenticated server console");
    expect(socket.close).toHaveBeenCalledTimes(1);
  });
});
