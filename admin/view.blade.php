<style>
    .mbp-admin {
        margin-bottom: 24px;
        border: 1px solid var(--aqua-border, #303942);
        border-radius: 8px;
        background: var(--aqua-surface, #1c232a);
        color: var(--aqua-text, #d7dce1);
        font-size: 13px;
        line-height: 1.65;
    }

    .mbp-admin .mbp-admin-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding: 26px 28px;
        border-bottom: 1px solid var(--aqua-border, #303942);
    }

    .mbp-admin h2,
    .mbp-admin h3 {
        color: var(--aqua-heading, #f5f6f7);
        font-weight: 600;
        line-height: 1.4;
    }

    .mbp-admin h2 {
        margin: 0 0 6px;
        font-size: 21px;
        letter-spacing: -0.02em;
    }

    .mbp-admin h3 {
        margin: 0 0 16px;
        font-size: 15px;
    }

    .mbp-admin p {
        margin: 0;
        color: var(--aqua-muted, #9ca5af);
    }

    .mbp-admin .mbp-admin-configure {
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 9px 14px;
        border: 1px solid var(--aqua-control-border, #39424b);
        border-radius: 5px;
        background: var(--aqua-raised, #272e35);
        color: var(--aqua-heading, #f5f6f7);
        font: inherit;
        font-weight: 600;
        cursor: pointer;
    }

    .mbp-admin .mbp-admin-configure:hover {
        border-color: var(--aqua-accent, #78d4cc);
        background: var(--aqua-hover, #29333d);
    }

    .mbp-admin .mbp-admin-configure:focus-visible,
    .mbp-admin summary:focus-visible {
        outline: 2px solid var(--aqua-accent, #78d4cc);
        outline-offset: 4px;
    }

    .mbp-admin .mbp-admin-columns {
        display: grid;
        grid-template-columns: minmax(0, 1.8fr) minmax(260px, 1fr);
    }

    .mbp-admin .mbp-admin-main,
    .mbp-admin .mbp-admin-aside {
        min-width: 0;
        padding: 26px 28px;
    }

    .mbp-admin .mbp-admin-aside {
        border-left: 1px solid var(--aqua-border, #303942);
        border-radius: 0 0 8px 0;
        background: var(--aqua-chrome, #11161b);
    }

    .mbp-admin .mbp-admin-steps {
        display: grid;
        gap: 17px;
        margin: 0 0 30px;
        padding: 0;
        list-style: none;
        counter-reset: mbp-setup;
    }

    .mbp-admin .mbp-admin-steps li {
        display: grid;
        grid-template-columns: 26px minmax(0, 1fr);
        gap: 12px;
        counter-increment: mbp-setup;
    }

    .mbp-admin .mbp-admin-steps li::before {
        content: counter(mbp-setup);
        display: grid;
        place-items: center;
        width: 26px;
        height: 26px;
        border: 1px solid var(--aqua-border, #303942);
        border-radius: 50%;
        color: var(--aqua-accent, #78d4cc);
        font-size: 12px;
        font-weight: 600;
    }

    .mbp-admin .mbp-admin-steps strong {
        display: block;
        margin-bottom: 2px;
        font-weight: 600;
    }

    .mbp-admin .mbp-admin-table {
        width: 100%;
        margin-bottom: 12px;
        border-collapse: collapse;
        font-size: 12px;
        text-align: left;
    }

    .mbp-admin .mbp-admin-table th,
    .mbp-admin .mbp-admin-table td {
        padding: 12px 10px;
        border-bottom: 1px solid var(--aqua-border, #303942);
        vertical-align: top;
        overflow-wrap: normal;
    }

    .mbp-admin .mbp-admin-table th:first-child,
    .mbp-admin .mbp-admin-table td:first-child {
        padding-left: 0;
    }

    .mbp-admin .mbp-admin-table th:last-child,
    .mbp-admin .mbp-admin-table td:last-child {
        padding-right: 0;
    }

    .mbp-admin .mbp-admin-table thead th {
        padding-top: 0;
        color: var(--aqua-muted, #9ca5af);
        font-weight: 400;
    }

    .mbp-admin .mbp-admin-table tbody th {
        font-weight: 500;
        overflow-wrap: anywhere;
    }

    .mbp-admin .mbp-admin-table td:last-child code {
        white-space: nowrap;
        overflow-wrap: normal;
    }

    .mbp-admin .mbp-admin-note {
        font-size: 12px;
    }

    .mbp-admin .mbp-admin-aside section + section {
        margin-top: 26px;
        padding-top: 24px;
        border-top: 1px solid var(--aqua-border, #303942);
    }

    .mbp-admin .mbp-admin-aside h3 {
        margin-bottom: 10px;
    }

    .mbp-admin .mbp-admin-aside p + p {
        margin-top: 12px;
    }

    .mbp-admin code {
        padding: 2px 5px;
        border-radius: 3px;
        background: var(--aqua-raised, #272e35);
        color: var(--aqua-text, #d7dce1);
        font-size: 0.92em;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .mbp-admin .mbp-admin-file {
        display: block;
        margin: 12px 0;
        padding: 10px 12px;
        border: 1px solid var(--aqua-border, #303942);
    }

    .mbp-admin .mbp-admin-permissions {
        margin: 0;
    }

    .mbp-admin .mbp-admin-permissions dt {
        margin-top: 14px;
        font-weight: 500;
    }

    .mbp-admin .mbp-admin-permissions dt:first-child {
        margin-top: 0;
    }

    .mbp-admin .mbp-admin-permissions dd {
        margin: 3px 0 0;
        color: var(--aqua-muted, #9ca5af);
        font-size: 12px;
    }

    .mbp-admin .mbp-admin-details {
        margin-top: 24px;
        padding-top: 18px;
        border-top: 1px solid var(--aqua-border, #303942);
        font-size: 12px;
    }

    .mbp-admin summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        color: var(--aqua-text, #d7dce1);
        list-style: none;
        cursor: pointer;
    }

    .mbp-admin summary::-webkit-details-marker {
        display: none;
    }

    .mbp-admin summary::after {
        content: "+";
        color: var(--aqua-accent, #78d4cc);
        font-size: 18px;
        line-height: 1;
    }

    .mbp-admin details[open] summary::after {
        content: "\2212";
    }

    .mbp-admin .mbp-admin-details p {
        margin-top: 12px;
    }

    @media (max-width: 1100px) {
        .mbp-admin .mbp-admin-columns {
            grid-template-columns: minmax(0, 1fr);
        }

        .mbp-admin .mbp-admin-aside {
            border-top: 1px solid var(--aqua-border, #303942);
            border-left: 0;
            border-radius: 0 0 8px 8px;
        }
    }

    @media (max-width: 600px) {
        .mbp-admin .mbp-admin-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 16px;
        }

        .mbp-admin .mbp-admin-header,
        .mbp-admin .mbp-admin-main,
        .mbp-admin .mbp-admin-aside {
            padding: 20px;
        }

        .mbp-admin .mbp-admin-table th,
        .mbp-admin .mbp-admin-table td {
            padding: 10px 6px;
        }
    }
</style>

<section class="mbp-admin" aria-labelledby="mbp-admin-title">
    <header class="mbp-admin-header">
        <div>
            <h2 id="mbp-admin-title">Setup &amp; server support</h2>
            <p>View players and manage access from each server's Bedrock Players tab.</p>
        </div>
        <button type="button" class="mbp-admin-configure" data-toggle="modal" data-target="#extensionConfigModal" aria-haspopup="dialog">
            <i class="fa fa-sliders" aria-hidden="true"></i>
            Configure servers
        </button>
    </header>

    <div class="mbp-admin-columns">
        <div class="mbp-admin-main">
            <section aria-labelledby="mbp-admin-setup-title">
                <h3 id="mbp-admin-setup-title">Before you start</h3>
                <ol class="mbp-admin-steps" role="list">
                    <li>
                        <div>
                            <strong>Choose your Bedrock eggs</strong>
                            <p>Open Configure servers and select your Bedrock eggs under <b>Route eggs</b> to show the tab on the right servers.</p>
                        </div>
                    </li>
                    <li>
                        <div>
                            <strong>Allow console access</strong>
                            <p>The server must be running, and users need console permission to read the player list and use moderation tools.</p>
                        </div>
                    </li>
                    <li>
                        <div>
                            <strong>Check the game port</strong>
                            <p>Allow UDP from the panel host to the assigned Bedrock port, usually <code>19132</code>, for player counts when names are unavailable.</p>
                        </div>
                    </li>
                </ol>
            </section>

            <section aria-labelledby="mbp-admin-support-title">
                <h3 id="mbp-admin-support-title">Supported server software</h3>
                <table class="mbp-admin-table" aria-labelledby="mbp-admin-support-title">
                    <thead>
                        <tr>
                            <th scope="col">Server software</th>
                            <th scope="col">Moderation</th>
                            <th scope="col">Access list</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Bedrock Dedicated Server</th>
                            <td>Kick</td>
                            <td><code>allowlist</code></td>
                        </tr>
                        <tr>
                            <th scope="row">Endstone</th>
                            <td>Kick, ban &amp; unban</td>
                            <td><code>allowlist</code></td>
                        </tr>
                        <tr>
                            <th scope="row">Nukkit / PowerNukkit</th>
                            <td>Kick, ban &amp; unban</td>
                            <td><code>whitelist</code></td>
                        </tr>
                        <tr>
                            <th scope="row">PocketMine-MP</th>
                            <td>Kick, ban &amp; unban</td>
                            <td><code>whitelist</code></td>
                        </tr>
                    </tbody>
                </table>
                <p class="mbp-admin-note">Removing a player from an access list does not ban them or turn the list on.</p>
            </section>

            <details class="mbp-admin-details">
                <summary>How player lists are read</summary>
                <p>The addon sends <code>list</code> and reads the fresh response over an authenticated Wings websocket. Standard English responses are supported; translated or plugin-customized output may not be recognized.</p>
                <p>Bedrock UDP status is a fallback for player counts only. It does not return player names.</p>
            </details>
        </div>

        <aside class="mbp-admin-aside" aria-label="Inventory setup and permissions">
            <section aria-labelledby="mbp-admin-inventory-title">
                <h3 id="mbp-admin-inventory-title">Inventory snapshots</h3>
                <p>Install a snapshot exporter on the game server to read inventory, hearts, hunger and XP. Player lists and moderation work without one.</p>
                <code class="mbp-admin-file">/bedrock-player-snapshots.json</code>
                <p class="mbp-admin-note">The exporter must write this file in the game server root. Users also need file listing and file content permissions.</p>
                <p class="mbp-admin-note">Snapshots are read-only. Bedrock gamertags do not use Java player saves or Java skins.</p>
            </section>

            <section aria-labelledby="mbp-admin-permissions-title">
                <h3 id="mbp-admin-permissions-title">Access &amp; permissions</h3>
                <dl class="mbp-admin-permissions">
                    <dt>Player lists &amp; moderation</dt>
                    <dd>Users with console permission. Moderation commands require confirmation.</dd>
                    <dt>OP &amp; de-OP</dt>
                    <dd>Server owners and panel admins only.</dd>
                    <dt>Command results</dt>
                    <dd>Check the server console to confirm the game accepted a command.</dd>
                </dl>
            </section>
        </aside>
    </div>
</section>
