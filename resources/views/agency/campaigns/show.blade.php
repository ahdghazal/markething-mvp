@extends('layouts.dashboard')

@section('title', $campaign->name . ' - MARKETHING')

@section('page-title', $campaign->name)

@section(
    'page-subtitle',
    'Review generated campaign posts, captions, and creative directions.'
)

@section('user-name', auth()->user()->name ?? 'Agency User')
@section('user-role', 'Agency Account')

@section('dashboard-content')

@php
    $snapshot = $campaign->snapshot ?? [];
    $campaignSnapshot = $snapshot['campaign'] ?? [];
    $offer = $campaignSnapshot['offer'] ?? [];
    $conversionMethods = $campaignSnapshot['conversion_methods'] ?? [];
    $formatModes = $campaignSnapshot['format_modes'] ?? $campaign->format_modes ?? null;
    $mood = $campaignSnapshot['mood'] ?? $campaign->mood ?? null;

    $channelCounts = $campaign->posts
        ->groupBy('channel')
        ->map(fn ($posts) => $posts->count());
@endphp

<div class="campaign-output-page">

    @if (session('success'))
        <div class="validation-box success-box">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="validation-box">
            {{ $errors->first() }}

            @if ($campaign->status === 'failed')
                <div style="margin-top: 10px;">
                    Possible reasons: Claude timeout, invalid JSON, network issue, or distribution rule violation.
                </div>
            @endif
        </div>
    @endif

    <div class="campaign-output-hero">

        <div>
            @if ($campaign->status === 'generated')
                <span class="hero-badge">
                    Campaign Generated
                </span>
            @elseif ($campaign->status === 'failed')
                <span class="hero-badge suspended-badge">
                    Generation Failed
                </span>
            @else
                <span class="hero-badge inactive-badge">
                    Generating
                </span>
            @endif

            <h2>{{ $campaign->name }}</h2>

            <p>
                {{ $campaign->objective }}
                · {{ $campaign->posts->count() }} generated posts
                · {{ $campaign->start_date->format('M d') }}
                —
                {{ $campaign->end_date->format('M d, Y') }}
            </p>
        </div>

        <div class="hero-actions">
            <a
                href="{{ route('agency.campaigns.create') }}"
                class="btn btn-secondary"
            >
                + New Campaign
            </a>

            <button
                class="btn btn-primary"
                type="button"
                onclick="window.print()"
            >
                Export / Print
            </button>
        </div>

    </div>

    <div class="stats-grid">

        <x-stats-card
            label="Posts"
            value="{{ $campaign->posts->count() }}"
            hint="Generated outputs"
        />

        <x-stats-card
            label="Channels"
            value="{{ count($campaign->channels) }}"
            hint="Selected platforms"
        />

        <x-stats-card
            label="Client"
            value="{{ $campaign->client?->name ?? 'Deleted client' }}"
            hint="Business profile"
        />

        <x-stats-card
            label="Persona"
            value="{{ $campaign->persona?->name ?? 'Deleted persona' }}"
            hint="Audience target"
        />

    </div>

    <div class="campaign-output-grid">

        <div class="campaign-posts-column">

            @if ($campaign->status === 'failed')
                <div class="table-card">
                    <h2 class="section-title">Generation Failed</h2>

                    <p class="section-description">
                        MARKETHING could not generate a valid campaign. Please try again with shorter inputs,
                        fewer materials, or a smaller date range.
                    </p>
                </div>
            @endif

            @forelse ($campaign->posts as $post)

                <div class="campaign-post-card">

                    <div class="campaign-post-top">



                        <div class="campaign-post-actions">
                          
                            <button
                                class="mini-btn"
                                type="button"
                                data-open-modal="postModal{{ $post->id }}"
                            >
                                View / Edit
                            </button>

                            <button
                            class="mini-btn"
                            type="button"
                            data-card-copy-post
                            data-caption="{{ e($post->caption) }}"
                            data-hashtags="{{ e($post->hashtags) }}"
                        >
                            Copy
                        </button>

                            @if ($post->is_edited)
                                <span class="edited-pill">
                                    Edited
                                </span>
                            @endif

                            @if ($post->is_regenerated)
                                <span class="edited-pill regenerated-pill">
                                    Regenerated
                                </span>
                            @endif



                        </div>

                        <div class="post-header">
                            @if($post->boost_recommended)
                                <span class="edited-pill boost">
                                    Recommended for Boosting
                                </span>
                            @endif
                            <span class="post-number">
                                Post #{{ $post->sequence_number }}
                            </span>

                            <h3 class="text-rtl">
                                {{ $post->summary ?: 'Generated post' }}
                            </h3>

                            <p>
                                <strong>{{ ucfirst($post->channel) }}</strong>
                                ·
                                {{ ucfirst($post->media_type) }}
                                ·
                                {{ $post->scheduled_date?->format('M d, Y') }}
                            </p>
                        </div>

                    </div>

                    <div class="post-preview text-rtl">
                        <p>
                            {{ \Illuminate\Support\Str::limit($post->caption, 260) }}
                        </p>
                    </div>

                    <div class="post-meta-row">

                        <div>
                            <span>Media Type</span>
                            <strong>{{ ucfirst($post->media_type) }}</strong>
                        </div>

                        <div class="text-rtl">
                            <span>Hashtags</span>
                            <strong>{{ \Illuminate\Support\Str::limit($post->hashtags, 50) }}</strong>
                        </div>

                    </div>

                </div>

                <x-modal
                    id="postModal{{ $post->id }}"
                    title="Generated Campaign Post"
                    subtitle="Review, edit, copy, or regenerate this post."
                >

                    <div class="generated-post-detail">

                        <div class="info-grid">

                            <div class="info-item">
                                <span>Channel</span>
                                <strong>{{ ucfirst($post->channel) }}</strong>
                            </div>

                            <div class="info-item">
                                <span>Media Type</span>
                                <strong>{{ ucfirst($post->media_type) }}</strong>
                            </div>

                            <div class="info-item">
                                <span>Scheduled Date</span>
                                <strong>{{ $post->scheduled_date?->format('M d, Y') }}</strong>
                            </div>

                            <div class="info-item">
                                <span>Status</span>
                                <strong>
                                    @if ($post->is_regenerated)
                                        Regenerated
                                    @elseif ($post->is_edited)
                                        Edited
                                    @else
                                        Original
                                    @endif
                                </strong>
                            </div>

                        </div>

                        <div class="generated-detail-block">
                            <span>Summary</span>
                            <p>{{ $post->summary ?: 'No summary provided.' }}</p>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('agency.campaign-posts.update', $post) }}"
                        >
                            @csrf
                            @method('PATCH')

                            <div class="generated-detail-block">
                                <span>Caption</span>

                                <textarea
                                    class="generated-output-textarea text-rtl"
                                    rows="8"
                                    name="caption"
                                >{{ $post->caption }}</textarea>
                            </div>

                            <div class="generated-detail-block">
                                <span>Creative Direction</span>

                                <textarea
                                    class="generated-output-textarea text-rtl"
                                    rows="5"
                                    name="creative_direction"
                                >{{ $post->creative_direction }}</textarea>
                            </div>

                            <div class="generated-detail-block">
                                <span>Hashtags</span>

                                <textarea
                                    class="generated-output-textarea text-rtl"
                                    rows="4"
                                    name="hashtags"
                                >{{ $post->hashtags }}</textarea>
                            </div>

                            <div class="modal-actions">
                                <button
                                    class="btn btn-regenerate"
                                    type="submit"
                                >
                                    Save Changes
                                </button>

                                <button
                                    class="btn btn-secondary"
                                    type="button"
                                    data-copy-post
                                >
                                    Copy Content
                                </button>

                                <button
                                    class="btn btn-secondary"
                                    type="button"
                                    data-close-modal
                                >
                                    Close
                                </button>
                            </div>

                        </form>

                        <div class="profile-side-divider"></div>

                        <form
                            method="POST"
                            action="{{ route('agency.campaign-posts.regenerate', $post) }}"
                        >
                            @csrf

                            <div class="modal-actions">
                                <button
                                    class="btn btn-regenerate"
                                    type="submit"
                                    @disabled($post->regeneration_count >= 1)
                                >
                                    ✦
                                    {{ $post->regeneration_count >= 1 ? 'Regeneration Used' : 'Regenerate Post' }}
                                </button>
                            </div>
                        </form>

                    </div>

                </x-modal>

            @empty

                <x-empty-state
                    title="No generated posts yet"
                    description="This campaign does not contain generated output yet."
                />

            @endforelse

        </div>

        <div class="campaign-side-column">

            <div class="table-card sticky-card">

                <h2 class="section-title">Campaign Summary</h2>

                <div class="summary-list">

                    <div class="summary-row">
                        <span>Client</span>
                        <strong>{{ $campaign->client?->name ?? 'Deleted client' }}</strong>
                    </div>

                    <div class="summary-row">
                        <span>Persona</span>
                        <strong>{{ $campaign->persona?->name ?? 'Deleted persona' }}</strong>
                    </div>

                    <div class="summary-row">
                        <span>Status</span>
                        <strong>{{ ucfirst($campaign->status) }}</strong>
                    </div>

                    <div class="summary-row">
                        <span>Objective</span>
                        <strong>{{ $campaign->objective }}</strong>
                    </div>

                    @php
                        $formatLabels = [
                            'image' => 'Image',
                            'carousel' => 'Carousel',
                            'reel' => 'Reel',
                            'system_decide' => 'Let the system decide',
                        ];
                    @endphp

                    <strong>
                        {{
                            !empty($formatModes)
                                ? implode(', ', array_map(fn ($mode) => $formatLabels[$mode] ?? $mode, $formatModes))
                                : 'Not specified'
                        }}
                    </strong>

                    <div class="summary-row">
                        <span>Mood</span>
                        <strong>{{ $mood ?: 'Not specified' }}</strong>
                    </div>

                    <div class="summary-row">
                        <span>Posts Requested</span>
                        <strong>{{ $campaign->requested_posts_count }}</strong>
                    </div>

                    <div class="summary-row">
                        <span>Channels</span>
                        <strong>{{ implode(', ', array_map('ucfirst', $campaign->channels ?? [])) }}</strong>
                    </div>

                    <div class="summary-row">
                        <span>Conversion</span>
                        <strong>
                            {{ count($conversionMethods) ? implode(', ', $conversionMethods) : 'Not provided' }}
                        </strong>
                    </div>

                    @if (!empty($offer['type']))
                        <div class="summary-row">
                            <span>Offer</span>
                            <strong>{{ $offer['type'] }}</strong>
                        </div>
                    @endif

                    @if (!empty($offer['value']))
                        <div class="summary-row">
                            <span>Offer Value</span>
                            <strong>{{ $offer['value'] }}</strong>
                        </div>
                    @endif

                    @if (!empty($offer['deadline']))
                        <div class="summary-row">
                            <span>Offer Deadline</span>
                            <strong>{{ $offer['deadline'] }}</strong>
                        </div>
                    @endif

                    @if (!empty($offer['code']))
                        <div class="summary-row">
                            <span>Promo Code</span>
                            <strong>{{ $offer['code'] }}</strong>
                        </div>
                    @endif

                </div>

                <div class="profile-side-divider"></div>

                <div class="campaign-objective-box">
                    <h3>Campaign Description</h3>

                    <p>
                        {{ $campaign->description ?: 'No additional description provided.' }}
                    </p>
                </div>

                <div class="profile-side-divider"></div>

                <div class="campaign-objective-box">
                    <h3>Distribution</h3>

                    @forelse ($channelCounts as $channel => $count)
                        <p>
                            <strong>{{ ucfirst($channel) }}:</strong>
                            {{ $count }} posts
                        </p>
                    @empty
                        <p>No posts generated yet.</p>
                    @endforelse
                </div>

                <div class="save-actions">

                    <a
                        href="{{ route('agency.dashboard') }}"
                        class="btn btn-secondary full-btn"
                    >
                        Back To Dashboard
                    </a>

                    @if ($campaign->client && ! $campaign->client->trashed())
                        <a
                            href="{{ route('agency.clients.show', $campaign->client) }}"
                            class="btn btn-primary full-btn"
                        >
                            View Client
                        </a>
                    @else
                        <button class="btn btn-danger full-btn" type="button" disabled>
                            Client Deleted
                        </button>
                    @endif

                </div>

            </div>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    document
        .querySelectorAll('[data-card-copy-post]')
        .forEach((button) => {
            button.addEventListener('click', async () => {
                const caption = button.dataset.caption || '';
                const hashtags = button.dataset.hashtags || '';

                try {
                    await navigator.clipboard.writeText(
                        `${caption}\n\n${hashtags}`.trim()
                    );

                    const original = button.textContent;
                    button.textContent = 'Copied!';

                    setTimeout(() => {
                        button.textContent = original;
                    }, 1500);
                } catch (error) {
                    alert('Copy failed. Please copy manually.');
                }
            });
        });

    document
        .querySelectorAll('[data-copy-post]')
        .forEach((button) => {
            button.addEventListener('click', async () => {
                const form = button.closest('form');

                if (!form) {
                    return;
                }

                const textareas = form.querySelectorAll('textarea');

                let combined = '';

                textareas.forEach((textarea) => {
                    combined += textarea.value + '\n\n';
                });

                try {
                    await navigator.clipboard.writeText(combined.trim());

                    button.textContent = 'Copied!';

                    setTimeout(() => {
                        button.textContent = 'Copy Content';
                    }, 1800);
                } catch (error) {
                    alert('Copy failed. Please copy manually.');
                }
            });
        });

    document
        .querySelectorAll('[id^="postModal"]')
        .forEach((modal) => {
            const editForm =
                modal.querySelector(
                    'form[action*="campaign-posts"][method="POST"]:not([action*="regenerate"])'
                );

            if (!editForm) {
                return;
            }

            let isDirty = false;
            let isSubmitted = false;

            editForm.dataset.dirty = 'false';

            editForm
                .querySelectorAll('textarea')
                .forEach((textarea) => {
                    textarea.addEventListener('input', () => {
                        isDirty = true;
                        isSubmitted = false;
                        editForm.dataset.dirty = 'true';
                    });
                });

            editForm.addEventListener('submit', () => {
                isSubmitted = true;
                isDirty = false;
                editForm.dataset.dirty = 'false';
            });

            modal
                .querySelectorAll('[data-close-modal]')
                .forEach((closeButton) => {
                    closeButton.addEventListener('click', (event) => {
                        if (!isDirty || isSubmitted) {
                            return;
                        }

                        const confirmed = confirm(
                            'You have unsaved changes in this post. Close anyway?'
                        );

                        if (!confirmed) {
                            event.preventDefault();
                            event.stopImmediatePropagation();
                        }
                    });
                });

            const regenerateForm =
                modal.querySelector('form[action*="regenerate"]');

            regenerateForm?.addEventListener('submit', (event) => {
                if (isDirty && !isSubmitted) {
                    const confirmed = confirm(
                        'This will permanently overwrite this post and your unsaved edits will be lost. Continue?'
                    );

                    if (!confirmed) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        return;
                    }
                }

                editForm.dataset.dirty = 'false';

                showAiLoading(
                    'Regenerating Post...',
                    'Creating a new version of this post.'
                );
            });
        });

    window.addEventListener('beforeunload', (event) => {
        const hasDirtyPost =
            Array
                .from(
                    document.querySelectorAll(
                        '[id^="postModal"] form[data-dirty="true"]'
                    )
                )
                .length > 0;

        if (hasDirtyPost) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
});
</script>

@endsection