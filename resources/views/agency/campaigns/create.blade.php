@extends('layouts.dashboard')

@section('title', 'Create Campaign - MARKETHING')

@section('page-title', 'Create Campaign')

@section(
    'page-subtitle',
    'Generate AI-powered campaigns using real client profiles and personas.'
)

@section('user-name', auth()->user()->name ?? 'Agency User')
@section('user-role', 'Agency Account')

@section('dashboard-content')

<div class="campaign-builder-page">

    @if (session('success'))
        <div class="validation-box success-box">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="validation-box">
            {{ $errors->first() }}
        </div>
    @endif

    <form
        id="campaignCreateForm"
        method="POST"
        action="{{ route('agency.campaigns.store') }}"
        class="campaign-builder-layout"
        novalidate
    >
        @csrf

        <div class="campaign-main-column">

            <div class="table-card">

                <div class="section-header">
                    <div>
                        <h2 class="section-title">Client & Persona</h2>
                        <p class="section-description">
                            Select the business and target audience persona.
                        </p>
                    </div>
                </div>

                <div class="form-grid">

                    <div class="form-group">
                        <label class="form-label required">Client Profile</label>

                        <select
                            name="client_id"
                            id="clientSelect"
                            class="form-input"
                            required
                        >
                            <option value="">Select client</option>

                            @foreach ($clients as $client)
                                <option
                                    value="{{ $client->id }}"
                                    data-personas='@json($client->personas)'
                                    data-conversion-actions='@json($client->brand_info["conversion_actions"] ?? [])'
                                    {{ old('client_id') == $client->id ? 'selected' : '' }}
                                >
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Persona</label>

                        <select
                            name="persona_id"
                            id="personaSelect"
                            class="form-input"
                            required
                        >
                            <option value="">Select persona</option>
                        </select>
                    </div>

                </div>

            </div>
            
            <div class="table-card">

                <div class="section-header">
                    <div>
                        <h2 class="section-title">Campaign Information</h2>
                        <p class="section-description">
                            Configure the core details of the generated campaign.
                        </p>
                    </div>
                </div>

                <div class="form-grid">

                    <div class="form-group">
                        <label class="form-label required">Campaign Name</label>

                        <input
                            type="text"
                            name="name"
                            class="form-input"
                            placeholder="Enter your campaign name"
                            maxlength="300"
                            value="{{ old('name') }}"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Campaign Objective</label>

                        <select name="objective" id="objectiveSelect" class="form-input" required>
                            <option value="">Select objective</option>

                            @foreach ([
                                'Awareness — get the business noticed',
                                'Engagement — start conversations and comments',
                                'Offer / promotion — push a specific deal',
                                'Link clicks — send people to a link',
                                'Brand — share story, values, connection',
                            ] as $option)
                                <option value="{{ $option }}" @selected(old('objective') === $option)>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="form-grid conditional-field hidden" id="offerDetailsGroup">

                    <div class="form-group">
                        <label class="form-label required">Offer Type</label>

                        <select name="offer_type" class="form-input" id="offerTypeSelect">
                            <option value="">Select offer type</option>

                            @foreach ([
                                'Percentage discount',
                                'Amount discount',
                                'Free delivery',
                                'Buy X get Y',
                                'Gift with purchase',
                                'Bundle price',
                                'Other',
                            ] as $option)
                                <option value="{{ $option }}" @selected(old('offer_type') === $option)>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Offer Value</label>

                        <input
                            type="text"
                            name="offer_value"
                            id="offerValueInput"
                            class="form-input"
                            maxlength="40"
                            placeholder="e.g., 20% off"
                            value="{{ old('offer_value') }}"
                        >
                    </div>
                    <div class="form-group full-span campaign-conditions">
                        <label class="form-label">Conditions</label>

                        <textarea
                            name="offer_conditions"
                            class="form-textarea"
                            maxlength="150"
                            placeholder="e.g., Valid on selected items only."
                        >{{ old('offer_conditions') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Offer Deadline</label>

                        <input
                            type="date"
                            name="offer_deadline"
                            class="form-input"
                            value="{{ old('offer_deadline') }}"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Promo Code</label>

                        <input
                            type="text"
                            name="offer_code"
                            class="form-input"
                            maxlength="30"
                            placeholder="e.g., WINTER20"
                            value="{{ old('offer_code') }}"
                        >
                    </div>

                </div>

                <div class="form-group">
                    <label class="form-label">Campaign Description</label>

                    <textarea
                        name="description"
                        class="form-textarea campaign-description"
                        maxlength="3000"
                        placeholder="Add any extra campaign context, theme, or important notes..."
                    >{{ old('description') }}</textarea>
                </div>

            </div>


            <div class="table-card">

                <div class="section-header">
                    <div>
                        <h2 class="section-title">Conversion Methods</h2>
                        <p class="section-description">
                            Pick from the conversion methods saved in the selected client profile.
                        </p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label required">Conversion Method(s)</label>

                    <div
                        id="conversionMethodsGrid"
                        class="checkbox-grid"
                        data-old='@json(old("conversion_methods", []))'
                    >
                        <p class="input-helper">
                            Select a client first to load saved conversion methods.
                        </p>
                    </div>
                </div>

            </div>

            {{-- <div class="table-card">

                <div class="section-header">
                    <div>
                        <h2 class="section-title">Content Format</h2>
                        <p class="section-description">
                            Choose what kind of materials to generate.
                        </p>
                    </div>
                </div>

                <div class="form-grid">



                    <div class="form-group">
                        <label class="form-label">Campaign Mood</label>

                        <select name="mood" class="form-input">
                            <option value="">Select mood optional</option>

                            @foreach ([
                                'Celebratory / festive',
                                'Urgent / limited-time',
                                'Warm / heartfelt',
                                'Exciting / hype',
                                'Informative / helpful',
                                'Inspiring / motivational',
                            ] as $option)
                                <option value="{{ $option }}" @selected(old('mood') === $option)>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div> 

            </div> --}}

            <div class="table-card">

                <div class="section-header">
                    <div>
                        <h2 class="section-title">Content Settings</h2>
                        <p class="section-description">
                            Configure format mode, campaign duration, channels, and material count.
                        </p>
                    </div>
                </div>


            @php
                $oldFormatModes = old('format_modes', ['system_decide']);
            
                if (! is_array($oldFormatModes)) {
                    $oldFormatModes = [$oldFormatModes];
                }
            @endphp
            
            <div class="form-group">
                <label class="form-label required">Content Formats</label>
            
                <p class="input-helper">
                    Select one or more content formats, or let the system choose the best mix.
                </p>
            
                <div class="checkbox-grid" id="formatModesGrid">
            
                    <label class="channel-checkbox">
                        <input
                            type="checkbox"
                            name="format_modes[]"
                            value="image"
                            data-specific-format
                            {{ in_array('image', $oldFormatModes, true) ? 'checked' : '' }}
                        >
            
                        <span>Images</span>
                    </label>
            
                    <label class="channel-checkbox">
                        <input
                            type="checkbox"
                            name="format_modes[]"
                            value="carousel"
                            data-specific-format
                            {{ in_array('carousel', $oldFormatModes, true) ? 'checked' : '' }}
                        >
            
                        <span>Carousels</span>
                    </label>
            
                    <label class="channel-checkbox">
                        <input
                            type="checkbox"
                            name="format_modes[]"
                            value="reel"
                            data-specific-format
                            {{ in_array('reel', $oldFormatModes, true) ? 'checked' : '' }}
                        >
            
                        <span>Reels</span>
                    </label>
            
                    <label class="channel-checkbox">
                        <input
                            type="checkbox"
                            name="format_modes[]"
                            value="system_decide"
                            id="systemDecideFormat"
                            {{ in_array('system_decide', $oldFormatModes, true) ? 'checked' : '' }}
                        >
            
                        <span>Let the system decide</span>
                    </label>
            
                </div>
            </div>

                <div class="form-grid">

                    <div class="form-group">
                        <label class="form-label required">Start Date</label>

                        <input
                            type="date"
                            name="start_date"
                            class="form-input"
                            value="{{ old('start_date') }}"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label required">End Date</label>

                        <input
                            type="date"
                            name="end_date"
                            class="form-input"
                            value="{{ old('end_date') }}"
                            required
                        >
                    </div>

                </div>

                <div class="form-group">
                    <label class="form-label required">Channels</label>

                    <div class="checkbox-grid">

                        <label class="channel-checkbox">
                            <input
                                type="checkbox"
                                name="channels[]"
                                value="instagram"
                                {{ in_array('instagram', old('channels', [])) ? 'checked' : '' }}
                            >

                            <span>Instagram</span>
                        </label>

                        <label class="channel-checkbox">
                            <input
                                type="checkbox"
                                name="channels[]"
                                value="facebook"
                                {{ in_array('facebook', old('channels', [])) ? 'checked' : '' }}
                            >

                            <span>Facebook</span>
                        </label>

                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label required">Number of Materials</label>

                    <input
                        type="number"
                        name="requested_posts_count"
                        class="form-input"
                        min="1"
                        placeholder="12"
                        value="{{ old('requested_posts_count') }}"
                        required
                    >

                    <p class="input-helper" id="postLimitHint">
                        Select dates and channels to calculate the maximum allowed materials.
                    </p>
                </div>

            </div>

            <div class="save-actions">
                <button
                    id="generateCampaignBtn"
                    class="btn btn-primary"
                    type="submit"
                >
                    Generate Campaign
                </button>

                <a
                    href="{{ route('agency.dashboard') }}"
                    class="btn btn-secondary full-btn"
                >
                    Cancel
                </a>
            </div>

        </div>

        <div class="campaign-side-column">

            <div class="table-card sticky-card">

                <h2 class="section-title">
                    AI Campaign Generation
                </h2>

                <div class="completion-list">
                    <div class="completion-item done">
                        <span>✓</span>
                        Business Context
                    </div>

                    <div class="completion-item done">
                        <span>✓</span>
                        Persona Selection
                    </div>

                    <div class="completion-item active">
                        <span>•</span>
                        Campaign Generation
                    </div>
                </div>

                <div class="profile-side-divider"></div>

                <div class="campaign-note">
                    <h3>Generation Notes</h3>

                    <p>MARKETHING will assemble:</p>

                    <ul>
                        <li>Business context</li>
                        <li>Brand information</li>
                        <li>Audience persona</li>
                        <li>Campaign objective and offer details</li>
                        <li>Conversion methods</li>
                        <li>Selected content formats</li>
                        <li>Selected channels</li>
                    </ul>
                </div>

            </div>

        </div>

    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('campaignCreateForm');
    const generateButton = document.getElementById('generateCampaignBtn');

    const clientSelect = document.getElementById('clientSelect');
    const personaSelect = document.getElementById('personaSelect');

    const objectiveSelect = document.getElementById('objectiveSelect');
    const offerTypeSelect = document.getElementById('offerTypeSelect');
    const offerValueInput = document.getElementById('offerValueInput');
    const offerDetailsGroup = document.getElementById('offerDetailsGroup');
    const conversionMethodsGrid = document.getElementById('conversionMethodsGrid');
    const postLimitHint = document.getElementById('postLimitHint');
    const formatModesGrid =
        document.getElementById('formatModesGrid');

    const systemDecideFormat =
        document.getElementById('systemDecideFormat');

    const specificFormatInputs =
        Array.from(
            document.querySelectorAll('[data-specific-format]')
        );
    let formChanged = false;
    let formSubmitted = false;

    if (generateButton) {
        generateButton.disabled = false;
        generateButton.textContent = 'Generate Campaign';
    }

    function refreshOfferDetails() {
        const shouldShow =
            objectiveSelect?.value === 'Offer / promotion — push a specific deal';

        offerDetailsGroup?.classList.toggle('hidden', !shouldShow);

        offerDetailsGroup
            ?.querySelector('[name="offer_type"]')
            ?.toggleAttribute('required', shouldShow);
    }

    function refreshFormatModes() {
        if (!systemDecideFormat) {
            return;
        }

        const systemDecides =
            systemDecideFormat.checked;

        specificFormatInputs.forEach((input) => {
            if (systemDecides) {
                input.checked = false;
            }

            input.disabled = systemDecides;
        });
    }
    function updateOfferValuePlaceholder() {
        if (!offerTypeSelect || !offerValueInput) {
            return;
        }

        const placeholders = {
            'Percentage discount':
                'e.g., 20%',

            'Amount discount':
                'e.g., $15',

            'Free delivery':
                'e.g., Orders over $50',

            'Buy X get Y':
                'e.g., Buy 2 Get 1 Free',

            'Gift with purchase':
                'e.g., Free makeup bag',

            'Bundle price':
                'e.g., 3 products for $99',

            'Other':
                'Describe the offer value',
        };

        offerValueInput.placeholder =
            placeholders[offerTypeSelect.value]
            ?? 'Enter offer value';
    }

    objectiveSelect?.addEventListener('change', refreshOfferDetails);
    refreshOfferDetails();
        offerTypeSelect?.addEventListener(
        'change',
        updateOfferValuePlaceholder
    );

    updateOfferValuePlaceholder();

    function populatePersonas() {
        personaSelect.innerHTML = `
            <option value="">
                Select persona
            </option>
        `;

        const selectedOption =
            clientSelect.options[clientSelect.selectedIndex];

        if (!selectedOption || !selectedOption.dataset.personas) {
            return;
        }

        const personas =
            JSON.parse(selectedOption.dataset.personas || '[]');

        personas.forEach((persona) => {
            const option = document.createElement('option');

            option.value = persona.id;
            option.textContent = persona.name;

            if ("{{ old('persona_id') }}" == persona.id) {
                option.selected = true;
            }

            personaSelect.appendChild(option);
        });
    }

    function populateConversionMethods() {
        if (!conversionMethodsGrid) {
            return;
        }

        conversionMethodsGrid.innerHTML = '';

        const selectedOption =
            clientSelect.options[clientSelect.selectedIndex];

        if (!selectedOption || !selectedOption.dataset.conversionActions) {
            conversionMethodsGrid.innerHTML =
                '<p class="input-helper">Select a client first to load saved conversion methods.</p>';

            return;
        }

        const methods =
            JSON.parse(selectedOption.dataset.conversionActions || '[]');

        const oldValues =
            JSON.parse(conversionMethodsGrid.dataset.old || '[]');

        if (!methods.length) {
            conversionMethodsGrid.innerHTML =
                '<p class="input-helper">This client has no saved conversion methods. Edit the client profile first.</p>';

            return;
        }

        methods.forEach((method) => {
            const label = document.createElement('label');
            label.className = 'channel-checkbox';

            const input = document.createElement('input');
            input.type = 'checkbox';
            input.name = 'conversion_methods[]';
            input.value = method;

            if (oldValues.includes(method)) {
                input.checked = true;
            }

            const span = document.createElement('span');
            span.textContent = method;

            label.appendChild(input);
            label.appendChild(span);

            conversionMethodsGrid.appendChild(label);
        });
    }

    function getSelectedChannels() {
        return Array.from(
            form.querySelectorAll('[name="channels[]"]:checked')
        ).map((input) => input.value);
    }

    function getCampaignDurationDays() {
        const startDate = form.querySelector('[name="start_date"]')?.value;
        const endDate = form.querySelector('[name="end_date"]')?.value;

        if (!startDate || !endDate) {
            return 0;
        }

        if (endDate < startDate) {
            return -1;
        }

        const start = new Date(startDate + 'T00:00:00');
        const end = new Date(endDate + 'T00:00:00');

        return Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
    }

    function updatePostLimitHint() {
        const postsInput = form.querySelector('[name="requested_posts_count"]');
        const durationDays = getCampaignDurationDays();
        const channelCount = getSelectedChannels().length;

        if (!postLimitHint || !postsInput) {
            return;
        }

        if (durationDays === -1) {
            postLimitHint.textContent =
                'End date cannot be before the start date.';
            postsInput.removeAttribute('max');
            return;
        }

        if (durationDays === 0 || channelCount === 0) {
            postLimitHint.textContent =
                'Select dates and channels to calculate the maximum allowed materials.';
            postsInput.removeAttribute('max');
            return;
        }

        const maxPosts =
            durationDays * channelCount;

        postsInput.setAttribute('max', String(maxPosts));

        postLimitHint.textContent =
            `Maximum allowed materials for this setup: ${maxPosts}.`;
    }

    populatePersonas();
    populateConversionMethods();
    updatePostLimitHint();

    clientSelect?.addEventListener('change', () => {
        populatePersonas();
        populateConversionMethods();
    });

    form?.querySelectorAll('input, textarea, select').forEach((field) => {
        field.addEventListener('input', () => {
            formChanged = true;
            updatePostLimitHint();
        });

        field.addEventListener('change', () => {
            formChanged = true;
            updatePostLimitHint();
        });
    });

    function validateCampaignForm() {
        const name =
            form.querySelector('[name="name"]')?.value.trim();

        const objective =
            form.querySelector('[name="objective"]')?.value.trim();

        const offerType =
            form.querySelector('[name="offer_type"]')?.value.trim();

        const clientId =
            form.querySelector('[name="client_id"]')?.value;

        const personaId =
            form.querySelector('[name="persona_id"]')?.value;

        const conversionMethods =
            form.querySelectorAll('[name="conversion_methods[]"]:checked');

        const formatModes =
            form.querySelectorAll(
                '[name="format_modes[]"]:checked'
            );

        const startDate =
            form.querySelector('[name="start_date"]')?.value;

        const endDate =
            form.querySelector('[name="end_date"]')?.value;

        const postsCount =
            form.querySelector('[name="requested_posts_count"]')?.value;

        const channels =
            form.querySelectorAll('[name="channels[]"]:checked');

        const durationDays =
            getCampaignDurationDays();

        const maxPosts =
            durationDays > 0
                ? durationDays * channels.length
                : 0;

        if (!name) {
            return 'Please enter a campaign name.';
        }

        if (!objective) {
            return 'Please select a campaign objective.';
        }

        if (
            objective === 'Offer / promotion — push a specific deal' &&
            !offerType
        ) {
            return 'Please select an offer type.';
        }

        if (!clientId) {
            return 'Please select a client profile.';
        }

        if (!personaId) {
            return 'Please select a persona.';
        }

        if (conversionMethods.length === 0) {
            return 'Please select at least one conversion method.';
        }

        if (formatModes.length === 0) {
            return 'Please select at least one format.';
        }

        if (!startDate) {
            return 'Please select a start date.';
        }

        if (!endDate) {
            return 'Please select an end date.';
        }

        if (endDate < startDate) {
            return 'End date cannot be before the start date.';
        }

        if (durationDays > 30) {
            return 'Campaign date range cannot exceed 30 days.';
        }

        if (channels.length === 0) {
            return 'Please select at least one channel.';
        }

        if (!postsCount || Number(postsCount) < 1) {
            return 'Please enter the number of materials.';
        }

        if (maxPosts > 0 && Number(postsCount) > maxPosts) {
            return `Too many materials. Maximum allowed for this date range and channels is ${maxPosts}.`;
        }

        return null;
    }

    form?.addEventListener('submit', (event) => {
        event.preventDefault();

        const error =
            validateCampaignForm();

        if (error) {
            alert(error);
            return;
        }

        formSubmitted = true;

        if (generateButton) {
            generateButton.disabled = true;
            generateButton.textContent = 'Generating...';
        }

        showAiLoading(
            'Generating Your Campaign...',
            'Analyzing your business, brand, audience, and campaign strategy.'
        );

        const loadingSteps = [
            'Reading business context...',
            'Understanding brand rules...',
            'Analyzing the selected persona...',
            'Planning campaign structure...',
            'Checking schedule and channels...',
            'Preparing the AI generation request...',
        ];

        let loadingStepIndex = 0;

        const loadingInterval = setInterval(() => {
            const description =
                document.querySelector('[data-ai-loading-description]') ||
                document.getElementById('aiLoadingTitle');

            if (!description) {
                return;
            }

            description.textContent =
                loadingSteps[loadingStepIndex] ||
                'Finalizing campaign setup...';

            loadingStepIndex =
                Math.min(loadingStepIndex + 1, loadingSteps.length - 1);
        }, 5000);

        submitCampaignAsync(form, generateButton);

    });

    window.addEventListener('beforeunload', (event) => {
        if (formChanged && !formSubmitted) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    document.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const href = link.getAttribute('href');

            if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
                return;
            }

            if (formChanged && !formSubmitted) {
                const confirmed = confirm(
                    'You have unsaved campaign changes. Leave this page?'
                );

                if (!confirmed) {
                    event.preventDefault();
                }
            }
        });
    });

    systemDecideFormat?.addEventListener('change', () => {
    refreshFormatModes();
    formChanged = true;
});

specificFormatInputs.forEach((input) => {
    input.addEventListener('change', () => {

        if (input.checked) {
            systemDecideFormat.checked = false;
        }

        refreshFormatModes();
        formChanged = true;
    });
});

refreshFormatModes();

});
</script>

@endsection