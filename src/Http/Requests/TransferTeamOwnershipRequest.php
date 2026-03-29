<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest as Request;

class TransferTeamOwnershipRequest extends Request
{
    public function authorize(): bool
    {
        return auth()->check()
            && $this->team
            && (int) $this->team->governor_owned_by === (int) auth()->id();
    }

    public function rules(): array
    {
        return [
            "new_owner_id" => "required|integer",
        ];
    }

    public function process(): void
    {
        $authClass = config("genealabs-laravel-governor.models.auth");
        $newOwner = (new $authClass)->findOrFail($this->input("new_owner_id"));

        $this->team->transferOwnership($newOwner);
    }
}
