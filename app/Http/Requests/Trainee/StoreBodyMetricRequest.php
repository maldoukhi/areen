<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainee;

use App\Models\BodyMetric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * One weigh-in. `(user_id, measured_on)` is unique in the schema, so recording
 * the same day twice corrects the earlier figure rather than failing — a trainee
 * who mistyped their weight should be able to simply type it again.
 */
class StoreBodyMetricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BodyMetric::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'measured_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.Carbon::today()->addDay()->toDateString()],

            // The columns are decimal(5,2) and decimal(4,1); the bounds below are
            // tighter than the columns on purpose, because a human body is.
            'weight' => ['nullable', 'required_without:body_fat', 'numeric', 'min:20', 'max:400'],
            'body_fat' => ['nullable', 'required_without:weight', 'numeric', 'min:1', 'max:70'],

            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'measured_on' => __('trainee.metrics.measured_on'),
            'weight' => __('trainee.metrics.weight'),
            'body_fat' => __('trainee.metrics.body_fat'),
            'notes' => __('trainee.metrics.notes'),
        ];
    }
}
