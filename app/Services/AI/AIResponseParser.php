<?php

namespace App\Services\AI;

use Exception;

class AIResponseParser
{
    public function parseCampaignPosts(string $response): array
    {
        $cleaned = $this->extractJson($response);

        $decoded = json_decode($cleaned, true);

        if (! is_array($decoded)) {
            throw new Exception(
                'Claude returned invalid JSON: ' . json_last_error_msg()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Owner's schema
        |--------------------------------------------------------------------------
        |
        | {
        |     "campaign_meta": {...},
        |     "posts": [...]
        | }
        |
        */

        if (
            array_key_exists('posts', $decoded) &&
            is_array($decoded['posts'])
        ) {
            return $this->normalizeOwnerPosts(
                $decoded['posts']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Existing legacy flat-array schema
        |--------------------------------------------------------------------------
        |
        | Keep this fallback so older prompt versions and saved prompt versions
        | continue to work.
        |
        */

        if (array_is_list($decoded)) {
            return array_values(
                array_filter(
                    $decoded,
                    fn (mixed $post): bool => is_array($post)
                )
            );
        }

        throw new Exception(
            'Claude response did not contain a valid posts array.'
        );
    }

    protected function normalizeOwnerPosts(array $posts): array
    {
        return collect($posts)
            ->filter(
                fn (mixed $post): bool => is_array($post)
            )
            ->values()
            ->map(function (array $post, int $index): array {
                $caption = is_array($post['caption'] ?? null)
                    ? $post['caption']
                    : [];

                $creativeBrief = is_array(
                    $post['creative_brief'] ?? null
                )
                    ? $post['creative_brief']
                    : [];

                return [
                    /*
                    |--------------------------------------------------------------------------
                    | Owner field → Existing application field
                    |--------------------------------------------------------------------------
                    */

                    'sequence_number' =>
                        $post['post_index']
                        ?? $post['sequence_number']
                        ?? ($index + 1),

                    'channel' =>
                        $this->normalizeChannel(
                            $post['platform']
                            ?? $post['channel']
                            ?? null
                        ),

                    'media_type' =>
                        $this->normalizeMediaType(
                            $post['format']
                            ?? $post['media_type']
                            ?? null
                        ),

                    'summary' =>
                        $this->buildSummary($post),

                    'scheduled_date' =>
                        $post['post_date']
                        ?? $post['scheduled_date']
                        ?? null,

                    'caption' =>
                        $this->buildCaption(
                            $caption,
                            $post
                        ),

                    'hashtags' =>
                        $this->buildHashtags(
                            $caption['hashtags']
                            ?? $post['hashtags']
                            ?? []
                        ),

                    'creative_direction' =>
                        $this->buildCreativeDirection(
                            $post,
                            $creativeBrief
                        ),

                    'boost_recommended' =>
                        (bool) data_get(
                            $post,
                            'boost.recommended',
                            false
                        ),
                ];
            })
            ->all();
    }

    protected function buildCaption(
        array $caption,
        array $post
    ): string {
        /*
        |--------------------------------------------------------------------------
        | Prefer the owner's ready-to-display assembled caption
        |--------------------------------------------------------------------------
        */

        $assembled = trim(
            (string) ($caption['assembled'] ?? '')
        );

        if ($assembled !== '') {
            return $assembled;
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback: assemble the caption ourselves
        |--------------------------------------------------------------------------
        */

        $parts = [
            $caption['hook'] ?? null,
            $caption['body'] ?? null,
            $caption['cta'] ?? null,
        ];

        $captionText = collect($parts)
            ->map(
                fn (mixed $value): string =>
                    trim((string) $value)
            )
            ->filter()
            ->implode("\n\n");

        if ($captionText !== '') {
            return $captionText;
        }

        return trim(
            (string) ($post['caption'] ?? '')
        );
    }

    protected function buildHashtags(mixed $hashtags): string
    {
        if (is_array($hashtags)) {
            return collect($hashtags)
                ->map(
                    fn (mixed $hashtag): string =>
                        trim((string) $hashtag)
                )
                ->filter()
                ->map(function (string $hashtag): string {
                    return str_starts_with($hashtag, '#')
                        ? $hashtag
                        : '#' . ltrim($hashtag, '#');
                })
                ->unique()
                ->implode(' ');
        }

        return trim((string) $hashtags);
    }

    protected function buildSummary(array $post): ?string
    {
        $summary = trim(
            (string) ($post['summary'] ?? '')
        );

        if ($summary !== '') {
            return $summary;
        }

        $role = trim(
            (string) ($post['role'] ?? '')
        );

        $oneMessage = trim(
            (string) data_get(
                $post,
                'creative_brief.one_message',
                ''
            )
        );

        if ($oneMessage !== '') {
            return $oneMessage;
        }

        if ($role !== '') {
            return 'Campaign post role: '
                . str_replace('_', ' ', $role) . '.';
        }

        return null;
    }

    protected function buildCreativeDirection(
        array $post,
        array $creativeBrief
    ): string {
        $sections = [];

        /*
        |--------------------------------------------------------------------------
        | Preserve the owner's complete creative brief in one database field
        |--------------------------------------------------------------------------
        */

        $labels = [
            'format_dimensions' =>
                'Format and dimensions',

            'one_message' =>
                'One message',

            'headline_text_exact' =>
                'Headline text',

            'visual_concept' =>
                'Visual concept',

            'hierarchy' =>
                'Visual hierarchy',

            'cta_element' =>
                'CTA element',

            'color_theme' =>
                'Color theme',

            'typography_note' =>
                'Typography',

            'logo_branding' =>
                'Logo and branding',

            'whitespace_density' =>
                'Whitespace and density',

            'boost_note' =>
                'Boost note',
        ];

        foreach ($labels as $key => $label) {
            $value = $creativeBrief[$key] ?? null;

            if (is_array($value)) {
                $value = collect($value)
                    ->filter()
                    ->implode(' → ');
            }

            $value = trim((string) $value);

            if ($value !== '') {
                $sections[] = "{$label}: {$value}";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Preserve carousel planning inside creative_direction
        |--------------------------------------------------------------------------
        */

        $carouselSlides =
            $post['carousel_slides'] ?? [];

        if (is_array($carouselSlides) && $carouselSlides !== []) {
            $slides = collect($carouselSlides)
                ->filter(
                    fn (mixed $slide): bool =>
                        is_array($slide)
                )
                ->map(function (array $slide): string {
                    $number =
                        $slide['slide'] ?? '?';

                    $headline = trim(
                        (string) ($slide['headline'] ?? '')
                    );

                    $support = trim(
                        (string) ($slide['support'] ?? '')
                    );

                    $dominant = trim(
                        (string) (
                            $slide['dominant_element'] ?? ''
                        )
                    );

                    $details = collect([
                        $headline,
                        $support,
                        $dominant !== ''
                            ? "Dominant element: {$dominant}"
                            : null,
                    ])
                        ->filter()
                        ->implode(' — ');

                    return "Slide {$number}: {$details}";
                })
                ->filter()
                ->implode("\n");

            if ($slides !== '') {
                $sections[] = "Carousel plan:\n{$slides}";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Preserve Reel planning inside creative_direction
        |--------------------------------------------------------------------------
        */

        $reel = $post['reel'] ?? null;

        if (is_array($reel)) {
            $reelSections = [];

            $script = $reel['script'] ?? [];

            if (is_array($script) && $script !== []) {
                $scriptText = collect($script)
                    ->filter(
                        fn (mixed $scene): bool =>
                            is_array($scene)
                    )
                    ->map(function (array $scene): string {
                        $time = trim(
                            (string) ($scene['time'] ?? '')
                        );

                        $onScreen = trim(
                            (string) (
                                $scene['on_screen_text'] ?? ''
                            )
                        );

                        $spoken = trim(
                            (string) ($scene['spoken'] ?? '')
                        );

                        $shot = trim(
                            (string) ($scene['shot'] ?? '')
                        );

                        return collect([
                            $time,
                            $onScreen !== ''
                                ? "On-screen: {$onScreen}"
                                : null,
                            $spoken !== ''
                                ? "Spoken: {$spoken}"
                                : null,
                            $shot !== ''
                                ? "Shot: {$shot}"
                                : null,
                        ])
                            ->filter()
                            ->implode(' — ');
                    })
                    ->filter()
                    ->implode("\n");

                if ($scriptText !== '') {
                    $reelSections[] =
                        "Script:\n{$scriptText}";
                }
            }

            $direction = trim(
                (string) ($reel['direction'] ?? '')
            );

            if ($direction !== '') {
                $reelSections[] =
                    "Direction: {$direction}";
            }

            if ($reelSections !== []) {
                $sections[] =
                    "Reel plan:\n"
                    . implode("\n", $reelSections);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Legacy fallback
        |--------------------------------------------------------------------------
        */

        if ($sections === []) {
            return trim(
                (string) (
                    $post['creative_direction'] ?? ''
                )
            );
        }

        return implode("\n\n", $sections);
    }

    protected function normalizeChannel(
        mixed $channel
    ): string {
        return match (strtolower(trim((string) $channel))) {
            'facebook' => 'facebook',
            'instagram' => 'instagram',
            default => strtolower(
                trim((string) $channel)
            ),
        };
    }

    protected function normalizeMediaType(
        mixed $format
    ): string {
        return match (
            strtolower(trim((string) $format))
        ) {
            'single',
            'single image',
            'single_image',
            'static',
            'image' => 'image',

            'carousel',
            'carousels' => 'carousel',

            'reel',
            'reels',
            'video' => 'reel',

            default => 'image',
        };
    }

    protected function extractJson(string $response): string
    {
        $response = trim($response);

        $response = preg_replace(
            '/```(?:json)?|```/i',
            '',
            $response
        );

        $response = trim($response);

        /*
        |--------------------------------------------------------------------------
        | Owner schema: find the outer JSON object
        |--------------------------------------------------------------------------
        */

        $firstObject = strpos($response, '{');
        $lastObject = strrpos($response, '}');

        if (
            $firstObject !== false &&
            $lastObject !== false &&
            $lastObject > $firstObject
        ) {
            return substr(
                $response,
                $firstObject,
                $lastObject - $firstObject + 1
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Legacy schema: find the outer JSON array
        |--------------------------------------------------------------------------
        */

        $firstArray = strpos($response, '[');
        $lastArray = strrpos($response, ']');

        if (
            $firstArray !== false &&
            $lastArray !== false &&
            $lastArray > $firstArray
        ) {
            return substr(
                $response,
                $firstArray,
                $lastArray - $firstArray + 1
            );
        }

        throw new Exception(
            'Claude response was incomplete or did not contain valid JSON.'
        );
    }
}