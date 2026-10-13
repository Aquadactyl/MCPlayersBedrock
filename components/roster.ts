import { validGamertag } from "./gamertag";

export interface ConsoleRoster {
  status: "available";
  source: "console";
  complete: boolean;
  online: number;
  maximum: number;
  players: { name: string; uuid: null }[];
  roster_error?: string;
}

function cleanLine(line: string) {
  return (
    line
      // Console output intentionally contains ANSI colour/control sequences.
      // eslint-disable-next-line no-control-regex
      .replace(/\x1b\[[0-?]*[ -/]*[@-~]/gu, "")
      .replace(/§[0-9a-z]/giu, "")
      .replace(
        /^(?:\[\d{4}-\d{2}-\d{2} [\d:.]+ INFO\]\s*|\[[\d:.]+(?: INFO)?\]\s*|[\d:.]+ \[INFO\]\s*)/u,
        "",
      )
      .replace(/^\[(?:INFO|CONSOLE|Server thread\/INFO)\]:?\s*/u, "")
      .trim()
  );
}

export class RosterParser {
  private header: { online: number; maximum: number } | null = null;
  private names = new Map<string, string>();
  private bytes = 0;

  feed(output: string): ConsoleRoster | null {
    this.bytes += output.length;
    if (this.bytes > 262144 || output.length > 65536) return null;
    for (const raw of output.split(/\r?\n/u)) {
      let line = cleanLine(raw);
      const header =
        /^There are (\d+)\/(\d+) players online:\s*(.*)$/iu.exec(line) ??
        /^There are (\d+) (?:of a max of|out of a maximum of) (\d+) players online:\s*(.*)$/iu.exec(
          line,
        );
      if (header) {
        const online = Number(header[1]),
          maximum = Number(header[2]);
        if (online > 10000 || maximum > 1000000) continue;
        this.header = { online, maximum };
        this.names.clear();
        if (online === 0) return this.result();
        line = header[3];
      }
      if (!this.header || !line) continue;
      const names = line
        .replace(/^Players:\s*/iu, "")
        .split(",")
        .map((name) => {
          const value = name.trim();
          return value.startsWith('"') && value.endsWith('"') ? value.slice(1, -1) : value;
        });
      if (!names.length || !names.every(validGamertag)) continue;
      for (const name of names) this.names.set(name.toLowerCase(), name);
      if (this.names.size === this.header.online) return this.result();
    }
    return null;
  }

  result(): ConsoleRoster | null {
    if (!this.header || this.names.size > this.header.online) return null;
    return {
      status: "available",
      source: "console",
      complete: this.names.size === this.header.online,
      ...this.header,
      players: [...this.names.values()]
        .sort((a, b) => a.localeCompare(b))
        .map((name) => ({ name, uuid: null })),
    };
  }
}
