<?php

namespace Pterodactyl\BlueprintFramework\Extensions\bedrockplayers;

use Illuminate\Validation\Rule;
use Pterodactyl\Models\Server;

class PlayerActionRequest extends GetPlayersRequest
{
    public function authorize(): bool
    {
        $server = $this->route('server');
        if (!parent::authorize() || !$server instanceof Server || !(new QueryTarget)->supports($server)) {
            return false;
        }
        return !in_array($this->input('action'), ['op', 'deop'], true)
            || $this->user()->root_admin || $this->user()->id === $server->owner_id;
    }

    public function validationData(): array
    {
        return array_replace(parent::validationData(), ['player' => $this->route('player')]);
    }

    public function rules(): array
    {
        $server = $this->route('server');
        $software = $server instanceof Server ? (new QueryTarget)->software($server) : null;
        return [
            'player' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!Gamertag::valid($value)) { $fail('Enter a valid Bedrock gamertag.'); }
            }],
            'action' => ['required', Rule::in((new PlayerCommand)->actions($software))],
            'reason' => ['nullable', 'string', 'max:120', 'not_regex:/[\p{C}\p{Zl}\p{Zp}]/u'],
            'confirmed' => ['required', 'accepted'],
        ];
    }
}
