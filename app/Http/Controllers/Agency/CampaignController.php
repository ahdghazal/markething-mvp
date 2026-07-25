<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\StoreCampaignRequest;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Persona;
use App\Services\AI\CampaignGenerationService;
use App\Services\AI\PromptTemplateService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Jobs\GenerateCampaignJob;

class CampaignController extends Controller
{
    public function create(Request $request): View
    {
        $clients = $request->user()
            ->clients()
            ->where('status', 'active')
            ->with([
                'personas' => function ($query) {
                    $query->where('status', 'active');
                },
            ])
            ->latest()
            ->get();

        return view('agency.campaigns.create', [
            'clients' => $clients,
        ]);
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $user = $request->user();

        $client = Client::query()
            ->where('id', $request->client_id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $persona = Persona::query()
            ->where('id', $request->persona_id)
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->firstOrFail();

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->startOfDay();

        $durationDays = $startDate->diffInDays($endDate) + 1;

        if ($durationDays > 30) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum campaign date range is 30 days.',
                'errors' => [
                    'end_date' => [
                        'Maximum campaign date range is 30 days.'
                    ]
                ]
            ], 422);
        }

        $channels = array_values(array_unique($request->channels ?? []));

        $maxPostsAllowed = $durationDays * count($channels);

        if ((int) $request->requested_posts_count > $maxPostsAllowed) {
            return response()->json([
                'success' => false,
                'message' =>
                    "Too many materials. Maximum allowed for this date range and channels is {$maxPostsAllowed}.",
                'errors' => [
                    'requested_posts_count' => [
                        "Too many materials. Maximum allowed for this date range and channels is {$maxPostsAllowed}."
                    ]
                ]
            ], 422);
        }

        $savedConversionMethods =
            $client->brand_info['conversion_actions'] ?? [];

        $conversionMethods =
            array_values(array_unique($request->conversion_methods ?? []));

        foreach ($conversionMethods as $method) {
            if (! in_array($method, $savedConversionMethods, true)) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Please select only conversion methods saved in this client profile.',
                    'errors' => [
                        'conversion_methods' => [
                            'Please select only conversion methods saved in this client profile.'
                        ]
                    ]
                ], 422);
            }
        }

        $offer = [
            'type' => $request->offer_type,
            'value' => $request->offer_value,
            'conditions' => $request->offer_conditions,
            'deadline' => $request->offer_deadline,
            'code' => $request->offer_code,
        ];

        $promptVersion = app(PromptTemplateService::class)
            ->getActiveMasterPrompt();

        $campaign = Campaign::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'persona_id' => $persona->id,

            'name' => $request->name,
            'objective' => $request->objective,
            'description' => $request->description,

            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),

            'format_modes' => $request->format_modes,
            'mood' => $request->mood,

            'channels' => $channels,

            'requested_posts_count' =>
                (int) $request->requested_posts_count,

            'status' => 'generating',

            'prompt_version_id' => $promptVersion?->id,

            'snapshot' => [
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'industry' => $client->industry,
                    'business_context' => $client->business_context,
                    'business_info' => $client->business_info,
                    'brand_info' => $client->brand_info,
                ],

                'persona' => [
                    'id' => $persona->id,
                    'name' => $persona->name,
                    'age_range' => $persona->age_range,
                    'answers' => $persona->answers,
                ],

                'campaign' => [
                    'name' => $request->name,
                    'topic' => $request->name,
                    'objective' => $request->objective,
                    'description' => $request->description,

                    'offer' => $offer,

                    'conversion_methods' => $conversionMethods,

                    'format_modes' => $request->format_modes,
                    'mood' => $request->mood,

                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),

                    'channels' => $channels,

                    'requested_posts_count' =>
                        (int) $request->requested_posts_count,
                ],
            ],
        ]);

        try {
            GenerateCampaignJob::dispatch($campaign);

            return response()->json([
                'success' => true,
                'campaign_id' => $campaign->id,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Campaign generation failed.',
            ], 500);
        }
    }

    public function show(Request $request, Campaign $campaign): View
    {
        abort_if($campaign->user_id !== $request->user()->id, 403);

        $campaign->load([
            'posts',
            'client',
            'persona',
        ]);

        return view('agency.campaigns.show', [
            'campaign' => $campaign,
        ]);
    }

    public function index(): View
    {
        $campaigns = Campaign::query()
            ->where('user_id', auth()->id())
            ->with(['client', 'persona'])
            ->withCount('posts')
            ->latest()
            ->paginate(10);

        return view('agency.campaigns.index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function status(
    Campaign $campaign
    ) {
        abort_if(
            $campaign->user_id !== auth()->id(),
            403
        );

        return response()->json([
            'status' => $campaign->status,
        ]);
    }
}