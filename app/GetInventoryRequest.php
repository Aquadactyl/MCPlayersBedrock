<?php

namespace Pterodactyl\BlueprintFramework\Extensions\bedrockplayers;

use Pterodactyl\Models\Permission;
use Pterodactyl\Models\Server;

class GetInventoryRequest extends GetPlayersRequest
{
    public function authorize(): bool
    {
        $server = $this->route('server');
        return parent::authorize() && $server instanceof Server && (new QueryTarget)->supports($server)
            && $this->user()->can(Permission::ACTION_FILE_READ, $server)
            && $this->user()->can(Permission::ACTION_FILE_READ_CONTENT, $server);
    }

    public function validationData(): array
    {
        return array_replace(parent::validationData(), ['player' => $this->route('player')]);
    }

    public function rules(): array
    {
        return ['player' => ['required', 'string', function ($attribute, $value, $fail) {
            if (!Gamertag::valid($value)) { $fail('Enter a valid Bedrock gamertag.'); }
        }]];
    }
}
