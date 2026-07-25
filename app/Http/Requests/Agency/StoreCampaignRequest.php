<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Services\AppSettingService;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAgency() === true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'persona_id' => ['required', 'integer', 'exists:personas,id'],

            'name' => ['required', 'string', 'max:255'],
            'objective' => [
                'required',
                'string',
                Rule::in([
                    'Awareness — get the business noticed',
                    'Engagement — start conversations and comments',
                    'Offer / promotion — push a specific deal',
                    'Link clicks — send people to a link',
                    'Brand — share story, values, connection',
                ]),
            ],
            
            'offer_type' => ['nullable', 'string', 'max:255'],
            'offer_value' => ['nullable', 'string', 'max:40'],
            'offer_conditions' => ['nullable', 'string', 'max:150'],
            'offer_deadline' => ['nullable', 'date'],
            'offer_code' => ['nullable', 'string', 'max:30'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],

            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [
                'required',
                'string',
                Rule::in(['instagram', 'facebook']),
            ],

            'requested_posts_count' => ['required', 'integer', 'min:1'],

            'description' => ['nullable', 'string', 'max:3000'],
            'format_modes' => ['required', 'array', 'min:1'],

            'format_modes.*' => [
                'required',
                'string',
                Rule::in([
                    'image',
                    'carousel',
                    'reel',
                    'system_decide',
                ]),
            ],
            
            'mood' => [
                'nullable',
                'string',
                Rule::in([
                    'Celebratory / festive',
                    'Urgent / limited-time',
                    'Warm / heartfelt',
                    'Exciting / hype',
                    'Informative / helpful',
                    'Inspiring / motivational',
                ]),
            ],
            'conversion_methods' => ['required', 'array', 'min:1'],
            'conversion_methods.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'channels.required' => 'Please select at least one channel.',
            'channels.min' => 'Please select at least one channel.',
        
            'format_modes.required' =>
                'Please select at least one content format.',
        
            'format_modes.min' =>
                'Please select at least one content format.',
        
            'requested_posts_count.required' =>
                'Please enter the number of posts.',
        
            'conversion_methods.required' =>
                'Please select at least one conversion method.',
        
            'conversion_methods.min' =>
                'Please select at least one conversion method.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                $formatModes = $this->input('format_modes', []);

                if (
                    in_array('system_decide', $formatModes, true) &&
                    count($formatModes) > 1
                ) {
                    $validator->errors()->add(
                        'format_modes',
                        '"Let the system decide" cannot be selected together with other content formats.'
                    );
                }

                if (
                    ! $this->filled('start_date') ||
                    ! $this->filled('end_date')
                ) {
                    return;
                }

                $limit = app(AppSettingService::class)
                    ->int('max_campaign_days', 30);

                $start = Carbon::parse($this->start_date);
                $end = Carbon::parse($this->end_date);

                if ($start->diffInDays($end) + 1 > $limit) {
                    $validator->errors()->add(
                        'end_date',
                        "Campaign date range cannot exceed {$limit} days."
                    );
                }
                $durationDays = $start->diffInDays($end) + 1;

                $channels = $this->input('channels', []);
                
                $requestedPostsCount =
                    (int) $this->input('requested_posts_count', 0);
                
                $maxPostsAllowed =
                    $durationDays * count($channels);
                
                if (
                    $requestedPostsCount > 0 &&
                    count($channels) > 0 &&
                    $requestedPostsCount > $maxPostsAllowed
                ) {
                    $validator->errors()->add(
                        'requested_posts_count',
                        "Too many materials. Maximum allowed for this date range and selected channels is {$maxPostsAllowed}."
                    );
                }
                if (
                    $this->input('objective') === 'Offer / promotion — push a specific deal'
                    && blank($this->input('offer_type'))
                ) {
                    $validator->errors()->add(
                        'offer_type',
                        'Please select an offer type.'
                    );
                }
            },
        ];
    }
}