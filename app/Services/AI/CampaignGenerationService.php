<?php

namespace App\Services\AI;

use App\Models\Campaign;
use App\Models\CampaignPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CampaignGenerationService
{
    public function generate(Campaign $campaign): void
    {
        $compiledPrompt = null;
        $promptVersion = null;
        $result = null;
        $rawResponse = null;
        $latency = 0;

        try {
            DB::beginTransaction();

            $campaign->load([
                'client',
                'persona',
            ]);

            $snapshot = $campaign->snapshot ?? [];

            $snapshotClient = $snapshot['client'] ?? [];
            $snapshotPersona = $snapshot['persona'] ?? [];
            $snapshotCampaign = $snapshot['campaign'] ?? [];

            $promptVersion =
                app(PromptTemplateService::class)
                    ->getActiveMasterPrompt();

            if (! $promptVersion) {
                throw new \Exception('No active master prompt found.');
            }

            /*
            |--------------------------------------------------------------------------
            | Resolve campaign values
            |--------------------------------------------------------------------------
            */

            $businessInfo =
                $snapshotClient['business_info'] ?? [];

            $brandInfo =
                $snapshotClient['brand_info'] ?? [];

            $personaAnswers =
                $snapshotPersona['answers'] ?? [];

            $campaignOffer =
                $snapshotCampaign['offer'] ?? null;

            $campaignConversionMethods =
                $snapshotCampaign['conversion_methods'] ?? [];

            if (! is_array($campaignConversionMethods)) {
                $campaignConversionMethods = [
                    $campaignConversionMethods,
                ];
            }

            $campaignConversionMethods =
                array_values(
                    array_unique(
                        array_filter(
                            $campaignConversionMethods
                        )
                    )
                );

        /*
        |--------------------------------------------------------------------------
        | Resolve selected content formats
        |--------------------------------------------------------------------------
        */

        $campaignFormatModes =
            $snapshotCampaign['format_modes']
            ?? $campaign->format_modes
            ?? ['system_decide'];

        if (! is_array($campaignFormatModes)) {
            $campaignFormatModes = [
                $campaignFormatModes,
            ];
        }

        $campaignFormatModes =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn (mixed $format): string =>
                                strtolower(trim((string) $format)),
                            $campaignFormatModes
                        )
                    )
                )
            );

        if ($campaignFormatModes === []) {
            $campaignFormatModes = [
                'system_decide',
            ];
        }

        $allowedRequestedFormats = [
            'image',
            'carousel',
            'reel',
            'system_decide',
        ];

        foreach ($campaignFormatModes as $formatMode) {
            if (! in_array($formatMode, $allowedRequestedFormats, true)) {
                throw new \Exception(
                    'Campaign contains an invalid selected content format: ' .
                    $formatMode
                );
            }
        }

        if (
            in_array('system_decide', $campaignFormatModes, true) &&
            count($campaignFormatModes) > 1
        ) {
            throw new \Exception(
                '"Let the system decide" cannot be combined with specific content formats.'
            );
        }

            $campaignChannels =
                $snapshotCampaign['channels']
                ?? $campaign->channels
                ?? [];

            if (! is_array($campaignChannels)) {
                $campaignChannels = [
                    $campaignChannels,
                ];
            }

            $campaignChannels =
                array_values(
                    array_unique(
                        array_filter(
                            $campaignChannels
                        )
                    )
                );

            $requestedPostsCount =
                (int) (
                    $snapshotCampaign['requested_posts_count']
                    ?? $campaign->requested_posts_count
                );

            $campaignStartDate =
                Carbon::parse(
                    $snapshotCampaign['start_date']
                    ?? $campaign->start_date
                )->toDateString();

            $campaignEndDate =
                Carbon::parse(
                    $snapshotCampaign['end_date']
                    ?? $campaign->end_date
                )->toDateString();

            /*
            |--------------------------------------------------------------------------
            | Normalize values for the owner's documented input schema
            |--------------------------------------------------------------------------
            */

            $rawObjective =
                $snapshotCampaign['objective']
                ?? $campaign->objective;

            $normalizedObjective =
                match ($rawObjective) {
                    'Awareness — get the business noticed',
                    'awareness' =>
                        'awareness',

                    'Engagement — start conversations and comments',
                    'engagement' =>
                        'engagement',

                    'Offer / promotion — push a specific deal',
                    'offer' =>
                        'offer',

                    'Link clicks — send people to a link',
                    'link_clicks' =>
                        'link_clicks',

                    'Brand — share story, values, connection',
                    'brand' =>
                        'brand',

                    default =>
                        $rawObjective,
                };

            /*
            |--------------------------------------------------------------------------
            | Normalize optional arrays
            |--------------------------------------------------------------------------
            */

            $cities =
                $businessInfo['city'] ?? [];

            if (! is_array($cities)) {
                $cities = [$cities];
            }

            $brandPositioning =
                $businessInfo['brand_positioning'] ?? [];

            if (! is_array($brandPositioning)) {
                $brandPositioning = [
                    $brandPositioning,
                ];
            }

            $brandAvoids =
                $businessInfo['brand_avoids'] ?? [];

            if (! is_array($brandAvoids)) {
                $brandAvoids = [$brandAvoids];
            }

            if (
                ! empty(
                    $businessInfo['brand_avoids_other']
                    ?? null
                )
            ) {
                $brandAvoids[] =
                    $businessInfo['brand_avoids_other'];
            }

            $personaPriorities =
                $personaAnswers['priorities'] ?? [];

            if (! is_array($personaPriorities)) {
                $personaPriorities = [
                    $personaPriorities,
                ];
            }

            $availableConversionMethods =
                $brandInfo['conversion_actions'] ?? [];

            if (! is_array($availableConversionMethods)) {
                $availableConversionMethods = [
                    $availableConversionMethods,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Normalize offer
            |--------------------------------------------------------------------------
            */

            $normalizedOffer = null;

            if (
                $normalizedObjective === 'offer' &&
                is_array($campaignOffer)
            ) {
                $rawOfferType =
                    $campaignOffer['type'] ?? null;

                $normalizedOfferType =
                    match ($rawOfferType) {
                        'Percentage discount',
                        'percentage' =>
                            'percentage',

                        'Amount discount',
                        'Fixed amount discount',
                        'amount' =>
                            'amount',

                        'Free delivery',
                        'free_delivery' =>
                            'free_delivery',

                        'Buy X get Y',
                        'buy_x_get_y' =>
                            'buy_x_get_y',

                        'Gift with purchase',
                        'Free gift',
                        'gift' =>
                            'gift',
                        
                        'Bundle price',
                        'Bundle',
                        'bundle' =>
                            'bundle',

                        'Other',
                        'other' =>
                            'other',

                        default =>
                            $rawOfferType,
                    };

                $normalizedOffer = [
                    'type' =>
                        $normalizedOfferType,

                    'value' =>
                        $campaignOffer['value'] ?? null,

                    'conditions' =>
                        $campaignOffer['conditions'] ?? null,

                    'deadline' =>
                        $campaignOffer['deadline'] ?? null,

                    'code' =>
                        $campaignOffer['code'] ?? null,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Build conversion inventory
            |--------------------------------------------------------------------------
            */

            $conversionActions =
                $brandInfo['conversion'] ?? [];

            if (! is_array($conversionActions)) {
                $conversionActions = [];
            }

            /*
            |--------------------------------------------------------------------------
            | Keep only methods available in this client profile
            |--------------------------------------------------------------------------
            */

            foreach ($conversionActions as $method => $details) {
                if (
                    $details === null ||
                    $details === '' ||
                    $details === []
                ) {
                    unset($conversionActions[$method]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Build the owner's INPUT_JSON object
            |--------------------------------------------------------------------------
            */

            $inputJson = [
                'brand' => [
                    'business_context' =>
                        $snapshotClient['business_context']
                        ?? null,

                    'industry' =>
                        $snapshotClient['industry']
                        ?? $businessInfo['industry']
                        ?? null,

                    'business_type' =>
                        $businessInfo['business_type']
                        ?? null,

                    'country' =>
                        $businessInfo['country']
                        ?? null,

                    'city' =>
                        array_values(
                            array_filter($cities)
                        ),

                    'price_tier' =>
                        $businessInfo['price_tier']
                        ?? null,

                    'differentiator' =>
                        $businessInfo['differentiator']
                        ?? null,

                    'brand_positioning' =>
                        array_values(
                            array_filter(
                                $brandPositioning
                            )
                        ),

                    'brand_avoids' =>
                        array_values(
                            array_unique(
                                array_filter(
                                    $brandAvoids
                                )
                            )
                        ),

                    'business_age' =>
                        $businessInfo['business_age']
                        ?? null,

                    'arabic_dialect' =>
                        $brandInfo['arabic_dialect']
                        ?? null,

                    'emoji_usage' =>
                        $brandInfo['emoji_usage']
                        ?? null,

                    'english_usage' =>
                        $brandInfo['english_usage']
                        ?? null,

                    'words_to_avoid' =>
                        $brandInfo['words_to_avoid']
                        ?? null,

                    'caption_samples' =>
                        $brandInfo['caption_samples']
                        ?? null,

                    'conversion_actions' =>
                        $conversionActions,
                ],

                'persona' => [
                    'name' =>
                        $snapshotPersona['name']
                        ?? null,

                    'gender' =>
                        $personaAnswers['gender']
                        ?? null,

                    'age_range' =>
                        $snapshotPersona['age_range']
                        ?? null,

                    'who' =>
                        $personaAnswers['who']
                        ?? $personaAnswers['description']
                        ?? null,

                    'buyer_is_user' =>
                        $personaAnswers['buyer_is_user']
                        ?? null,

                    'decider' =>
                        $personaAnswers['decider']
                        ?? null,

                    'priorities' =>
                        array_values(
                            array_filter(
                                $personaPriorities
                            )
                        ),

                    'objection' =>
                        $personaAnswers['objection']
                        ?? null,
                ],

                'campaign' => [

                    'objective' =>
                        $normalizedObjective,

                    'description' =>
                        $snapshotCampaign['description']
                        ?? $campaign->description
                        ?? null,

                    'offer' =>
                        $normalizedOffer,

                    'conversion_methods' =>
                        $campaignConversionMethods,

                    'channels' =>
                        $campaignChannels,

                    'format_modes' => 
                        $campaignFormatModes,

                    'material_count' =>
                        $requestedPostsCount,

                    'start_date' =>
                        $campaignStartDate,

                    'end_date' =>
                        $campaignEndDate,
                ],

                /*
                |--------------------------------------------------------------------------
                | Full campaign generation
                |--------------------------------------------------------------------------
                */

                'regenerate_index' => null,
            ];

            /*
            |--------------------------------------------------------------------------
            | Compile the owner's active master prompt
            |--------------------------------------------------------------------------
            */

            $compiledPrompt =
                app(PromptCompilerService::class)
                    ->compile(
                        $promptVersion->content,
                        [
                            'INPUT_JSON' =>
                                json_encode(
                                    $inputJson,
                                    JSON_PRETTY_PRINT
                                    | JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                    | JSON_THROW_ON_ERROR
                                ),

                            'regenerate_index' =>
                                'null',
                        ]
                    );

            $startedAt = microtime(true);

            $result =
                app(ClaudeService::class)
                    ->generate(
                        $compiledPrompt
                    );

            $latency =
                (int) ((microtime(true) - $startedAt) * 1000);
                
            $rawResponse =
                $result['content'] ?? null;

            if (
                ! is_string($rawResponse) ||
                trim($rawResponse) === ''
            ) {
                throw new \Exception(
                    'Claude returned an empty response.'
                );
            }

            \Log::info('=== CLAUDE RAW RESPONSE ===');
            \Log::info($rawResponse);

            $posts =
                app(AIResponseParser::class)
                    ->parseCampaignPosts(
                        $rawResponse
                    );

                $allowedChannels =
                    array_values(
                        array_intersect(
                            [
                                'instagram',
                                'facebook',
                            ],
                            array_map(
                                fn (mixed $channel): string =>
                                    strtolower(
                                        trim((string) $channel)
                                    ),
                                $campaignChannels
                            )
                        )
                    );
    
                if ($allowedChannels === []) {
                    throw new \Exception(
                        'Campaign does not contain any valid selected channels.'
                    );
                }


            $allowedMediaTypes = [
                'image',
                'reel',
                'carousel',
            ];

            if (count($posts) !== $requestedPostsCount) {
                throw new \Exception(
                    'Claude returned ' . count($posts) . " posts, but {$requestedPostsCount} were requested."
                );
            }

            $seenScheduleSlots = [];

            foreach ($posts as $index => $post) {
                $channel =
                    strtolower($post['channel'] ?? '');

                if (! in_array($channel, $allowedChannels, true)) {
                    throw new \Exception(
                        'Generated post #' . ($index + 1) . ' has an invalid channel.'
                    );
                }

                if (empty($post['scheduled_date'])) {
                    throw new \Exception(
                        'Generated post #' . ($index + 1) . ' is missing scheduled_date.'
                    );
                }

                $scheduledDate =
                    Carbon::parse(
                        $post['scheduled_date']
                    )->toDateString();

                if (
                    $scheduledDate < $campaignStartDate ||
                    $scheduledDate > $campaignEndDate
                ) {
                    throw new \Exception(
                        'Generated post #' . ($index + 1) . ' has a scheduled date outside the campaign date range.'
                    );
                }

                $slotKey =
                    $channel . '|' . $scheduledDate;

                if (isset($seenScheduleSlots[$slotKey])) {
                    throw new \Exception(
                        "Claude returned more than one {$channel} post on {$scheduledDate}."
                    );
                }

                $seenScheduleSlots[$slotKey] = true;

                $mediaType =
                    strtolower($post['media_type'] ?? 'image');

                if (! in_array($mediaType, $allowedMediaTypes, true)) {
                    throw new \Exception(
                        'Generated post #' . ($index + 1) . ' has an invalid media type.'
                    );
                }
                $systemDecidesFormat =
                in_array(
                    'system_decide',
                    $campaignFormatModes,
                    true
                );
            
                if (
                    ! $systemDecidesFormat &&
                    ! in_array(
                        $mediaType,
                        $campaignFormatModes,
                        true
                    )
                ) {
                    throw new \Exception(
                        'Generated post #' .
                        ($index + 1) .
                        ' uses the "' .
                        $mediaType .
                        '" format, which was not selected.'
                    );
                }

            }

            $posts = collect($posts)
                ->unique('caption')
                ->values()
                ->toArray();

            if (count($posts) !== $requestedPostsCount) {
                throw new \Exception(
                    'Claude returned duplicate captions, causing the generated post count to be lower than requested.'
                );
            }

            foreach ($posts as $index => $post) {
                CampaignPost::create([
                    'campaign_id' =>
                        $campaign->id,

                    'sequence_number' =>
                        $post['sequence_number'] ?? ($index + 1),

                    'scheduled_date' =>
                        Carbon::parse($post['scheduled_date']),

                    'channel' =>
                        strtolower($post['channel']),

                    'media_type' =>
                        strtolower($post['media_type'] ?? 'image'),

                    'summary' =>
                        $post['summary'] ?? null,

                    'caption' =>
                        $post['caption'] ?? '',

                    'hashtags' =>
                        is_array($post['hashtags'] ?? null)
                            ? implode(' ', $post['hashtags'])
                            : ($post['hashtags'] ?? ''),

                    'creative_direction' =>
                        $post['creative_direction'] ?? '',

                    'boost_recommended' =>
                        $post['boost_recommended'] ?? false,

                    'is_edited' => false,
                ]);
            }

            $campaign->update([
                'status' => 'generated',
            ]);

            app(LlmLogService::class)
                ->create([
                    'user_id' =>
                        $campaign->user_id,

                    'client_id' =>
                        $campaign->client_id,

                    'campaign_id' =>
                        $campaign->id,

                    'call_type' =>
                        'campaign_generation',

                    'provider' =>
                        'anthropic',

                    'model' =>
                        config('ai.anthropic.model'),

                    'prompt_version_id' =>
                        $promptVersion->id,

                    'assembled_prompt' =>
                        $compiledPrompt,

                    'response' =>
                        $rawResponse,

                    'input_tokens' =>
                        $result['input_tokens'] ?? 0,

                    'output_tokens' =>
                        $result['output_tokens'] ?? 0,

                    'latency_ms' =>
                        $latency,

                    'retry_count' => 0,

                    'status' =>
                        'success',
                ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();

            $campaign->update([
                'status' => 'failed',
            ]);

            app(LlmLogService::class)
                ->create([
                    'user_id' =>
                        $campaign->user_id,

                    'client_id' =>
                        $campaign->client_id,

                    'campaign_id' =>
                        $campaign->id,

                    'call_type' =>
                        'campaign_generation',

                    'provider' =>
                        'anthropic',

                    'model' =>
                        config('ai.anthropic.model'),

                    'prompt_version_id' =>
                        $promptVersion?->id,

                    'assembled_prompt' =>
                        $compiledPrompt,

                    'response' =>
                        $rawResponse,

                    'input_tokens' =>
                        $result['input_tokens'] ?? 0,

                    'output_tokens' =>
                        $result['output_tokens'] ?? 0,

                    'latency_ms' =>
                        $latency,

                    'retry_count' => 0,

                    'status' =>
                        'failed',

                    'error_message' =>
                        $e->getMessage(),
                ]);

            throw $e;
        }
    }
}