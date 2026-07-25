<?php

namespace App\Http\Requests\Unit;

use App\Models\Unit;

class UpdateUnitRequest extends StoreUnitRequest
{
    public function authorize(): bool
    {
        $unit = $this->route('unit');

        return $unit instanceof Unit && $this->user()?->can('update', $unit) === true;
    }

    protected function nameExists(?int $ignoreId = null): bool
    {
        /** @var Unit $unit */
        $unit = $this->route('unit');

        return parent::nameExists((int) $unit->getKey());
    }
}
