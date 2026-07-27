<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A drop from `wire:sort` arrives as one moved row and its new index, not as a
 * whole list — one write per drag, never one per row.
 */
class ReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return self::rulesFor();
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(): array
    {
        return [
            'item' => ['required', 'integer', 'min:1'],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::attributeNames();
    }

    /**
     * @return array<string, string>
     */
    public static function attributeNames(): array
    {
        return [
            'item' => __('admin.actions.reorder'),
            'position' => __('admin.fields.sort'),
        ];
    }
}
