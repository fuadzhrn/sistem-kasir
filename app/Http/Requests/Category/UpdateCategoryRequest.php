<?php

namespace App\Http\Requests\Category;

use App\Models\Category;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category && $this->user()?->can('update', $category) === true;
    }

    protected function nameExists(?int $ignoreId = null): bool
    {
        /** @var Category $category */
        $category = $this->route('category');

        return parent::nameExists((int) $category->getKey());
    }
}
