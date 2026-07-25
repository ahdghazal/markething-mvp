@extends('layouts.dashboard')

@section('title', isset($isEditing) ? 'Edit Client - MARKETHING' : 'Create Client - MARKETHING')

@section('page-title', isset($isEditing) ? 'Edit Client Profile' : 'Create Client Profile')

@section(
    'page-subtitle',
    isset($isEditing)
        ? 'Update business context, business details, brand rules, and persona.'
        : 'Create a structured client profile for AI-powered campaign generation.'
)

@section('user-name', auth()->user()->name ?? 'Nova Marketing')
@section('user-role', 'Agency Account')

@section('dashboard-content')

@php
    $editing = isset($isEditing) && isset($client);

    $businessInfo = $client->business_info ?? [];
    $brandInfo = $client->brand_info ?? [];

    $primaryPersona = $editing
        ? $client->personas->first()
        : null;

    $personaAnswers = $primaryPersona?->answers ?? [];

    $businessContextMax = app(\App\Services\AppSettingService::class)
        ->int('business_context_character_limit', 5000);

    $businessContextValue = old('business_context', $client->business_context ?? '');

    $selectedCities = old('city', $businessInfo['city'] ?? []);
    $selectedBrandPositioning = old('brand_positioning', $businessInfo['brand_positioning'] ?? []);
    $selectedBrandAvoids = old('brand_avoids', $businessInfo['brand_avoids'] ?? []);
    $selectedConversionActions = old('conversion_actions', $brandInfo['conversion_actions'] ?? []);
    $selectedPersonaPriorities = old('persona_priorities', $personaAnswers['priorities'] ?? []);

    $conversion = $brandInfo['conversion'] ?? [];

    $aiDisabled = false;

    $steps = [
        'Business Context',
        'Business Fundamentals',
        'Market Positioning',
        'Brand Voice & Rules',
        'Marketing Mechanics',
        'Persona Basics',
        'Persona Motivations',
    ];

    $industries = [
        'Food & Beverage',
        'Retail',
        'E-commerce',
        'Beauty & Personal Care',
        'Health & Wellness',
        'Fitness',
        'Education',
        'Professional Services',
        'Creative Services',
        'Real Estate',
        'Hospitality',
        'Tech',
        'Other',
    ];

    $countries = [
        'Palestine',
        'Jordan',
        'Saudi Arabia',
        'UAE',
        'Egypt',
        'Lebanon',
        'Other',
    ];

    $cities = [
        'Ramallah',
        'Bethlehem',
        'Nablus',
        'Jerusalem',
        'Hebron',
        'Gaza',
        'Jericho',
        'Tulkarm',
        'Jenin',
        'Another Palestinian city',
        'West Bank-wide',
        'Online only',
    ];

    $brandPositioningOptions = [
        'Authentic & local',
        'Fast & convenient',
        'Specialist',
        'Friendly & approachable',
        'Trendy & modern',
        'Traditional & heritage',
        'Professional & trustworthy',
        'Innovative',
    ];

    $brandAvoidsOptions = [
        'Luxury',
        'Budget or discount',
        'Corporate or stiff',
        'Old-fashioned or traditional',
        'Trendy or trend-chasing',
        'Exclusive or elitist',
        'Pushy or salesy',
        'Casual or unserious',
        'Other',
    ];

    $conversionActions = [
        'Visit the store / location',
        'Order / inquire via WhatsApp',
        'Call us',
        'Order via delivery app',
        'Buy on our website',
        'Book an appointment',
        'Message us on Instagram / Facebook',
        'Subscribe / sign up',
    ];

    $personaPriorities = [
        'Price & offers',
        'Quality',
        'Speed & convenience',
        'Trust & safety',
        'Prestige & status',
        'New & trendy',
        'Personal service & care',
    ];
@endphp

<div class="client-create-page">

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
        method="POST"
        action="{{ $editing
            ? route('agency.clients.update', $client)
            : route('agency.clients.store') }}"
        class="client-form-layout staged-client-form"
    >
        @csrf

        @if ($editing)
            @method('PATCH')
        @endif

        <div class="form-main-column">

            <div class="table-card">

                <div class="section-header">
                    <div>
                        <span class="hero-badge">
                            Step-by-step profile
                        </span>

                        <h2 class="section-title" data-current-step-title>
                            Business Context
                        </h2>

                        <p class="section-description" data-current-step-description>
                            Paste the main business context AI Assist will use later.
                        </p>
                    </div>
                </div>

                <div class="step-progress-bar">
                    <div
                        class="step-progress-fill"
                        data-step-progress-fill
                    ></div>
                </div>

                {{-- STEP 1 --}}
                <div
                    class="client-step-panel active"
                    data-client-step="0"
                    data-step-title="Business Context"
                    data-step-description="Paste the main business context AI Assist will use later."
                >
                    <div class="form-group">
                        <label class="form-label required">
                            Business Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-input"
                            placeholder="Bloom Café"
                            value="{{ old('name', $client->name ?? '') }}"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Business Context
                        </label>

                        <textarea
                            name="business_context"
                            class="form-textarea"
                            maxlength="{{ $businessContextMax }}"
                            data-business-context-source
                            placeholder="Paste a business bio, about-us text, previous captions, or your own description..."
                            >{{ $businessContextValue }}</textarea>

                        <div class="ai-field-footer">
                            <p>
                                This is the source context AI Assist uses for supported fields.
                            </p>

                            <span data-business-context-counter>
                                {{ mb_strlen($businessContextValue) }}/{{ $businessContextMax }}
                            </span>
                        </div>

                        <details class="business-context-examples">
                            <summary>
                                Examples of what to paste
                            </summary>

                            <div class="examples-box">
                                <p>
                                    “We are a local coffee shop serving specialty coffee, pastries, and workspace seating for students and remote workers.”
                                </p>

                                <p>
                                    “Our brand sells handmade gold jewelry for women who want elegant gifts, bridal sets, and everyday luxury pieces.”
                                </p>

                                <p>
                                    “We are a burger restaurant focused on juicy chicken and beef burgers, fresh toppings, and fast delivery.”
                                </p>
                            </div>
                        </details>
                    </div>
                </div>

                {{-- STEP 2 --}}
                <div
                    class="client-step-panel"
                    data-client-step="1"
                    data-step-title="Business Fundamentals"
                    data-step-description="Define the business category, location, price tier, and differentiator."
                >
                    <div class="form-grid">

                        <div class="form-group">
                            <label class="form-label required">Industry</label>
                            <p class="input-helper">What kind of business is this?</p>

                            <select
                                name="industry"
                                class="form-select"
                                data-industry-select
                                required
                            >
                                <option value="">Select industry</option>

                                @foreach ($industries as $industry)
                                    <option
                                        value="{{ $industry }}"
                                        @selected(old('industry', $businessInfo['industry'] ?? $client->industry ?? '') === $industry)
                                    >
                                        {{ $industry }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div
                            class="form-group conditional-field"
                            data-industry-other-field
                        >
                            <label class="form-label">Other Industry</label>

                            <input
                                type="text"
                                name="industry_other"
                                class="form-input"
                                maxlength="50"
                                value="{{ old('industry_other', $businessInfo['industry_other'] ?? '') }}"
                                placeholder="Write the industry"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Business Type</label>
                            <p class="input-helper">Pick the closest match.</p>

                            <select
                            name="business_type"
                            class="form-select"
                            data-business-type-select
                            data-current-value="{{ old('business_type', $businessInfo['business_type'] ?? '') }}"
                            required
                        >
                                <option value="">Select business type</option>

                                @php
                                    $businessTypeValue = old('business_type', $businessInfo['business_type'] ?? '');
                                @endphp

                                @foreach ([
                                    'Restaurant',
                                    'Cafe',
                                    'Bakery',
                                    'Jewelry',
                                    'Clothing',
                                    'Salon',
                                    'Cosmetics brand',
                                    'Clinic',
                                    'Gym',
                                    'Training center',
                                    'Marketing agency',
                                    'Photography',
                                    'Real estate agency',
                                    'Hotel',
                                    'Software',
                                    'SaaS',
                                    'Other',
                                ] as $type)
                                    <option
                                        value="{{ $type }}"
                                        @selected($businessTypeValue === $type)
                                    >
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div
                            class="form-group conditional-field"
                            data-business-type-other-field
                        >
                            <label class="form-label">Other Business Type</label>

                            <input
                                type="text"
                                name="business_type_other"
                                class="form-input"
                                maxlength="50"
                                value="{{ old('business_type_other', $businessInfo['business_type_other'] ?? '') }}"
                                placeholder="Write the business type"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Country</label>
                            <p class="input-helper">Where is the business based?</p>
                        
                            <select
                                name="country"
                                class="form-select"
                                data-country-select
                                required
                            >
                                <option value="">Select country</option>
                        
                                @foreach ($countries as $country)
                                    <option
                                        value="{{ $country }}"
                                        @selected(old('country', $businessInfo['country'] ?? '') === $country)
                                    >
                                        {{ $country }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">City</label>
                            <p class="input-helper">Which cities does the business serve? Pick all that apply.</p>
                        
                            <div
                                class="city-grid"
                                data-palestine-cities
                            >
                                @foreach ($cities as $city)
                                    <label class="city-option">
                                        <input
                                            type="checkbox"
                                            name="city[]"
                                            value="{{ $city }}"
                                            @checked(in_array($city, $selectedCities ?? [], true))
                                        >
                        
                                        <span>{{ $city }}</span>
                                    </label>
                                @endforeach
                            </div>
                        
                            <div
                                class="empty-selection-card hidden"
                                data-city-placeholder
                            >
                                <span>📍</span>
                        
                                <div>
                                    <strong>City selection unavailable</strong>
                        
                                    <p>
                                        City presets are currently available only for Palestine.
                                        Additional countries will be added in future versions.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Price Tier</label>
                            <p class="input-helper">How do prices compare to others in the same category?</p>

                            <input
                                type="range"
                                name="price_tier"
                                min="1"
                                max="5"
                                class="form-range"
                                value="{{ old('price_tier', $businessInfo['price_tier'] ?? 3) }}"
                                data-price-tier-range
                                required
                            >

                            <p class="input-helper">
                                Selected:
                                <strong data-price-tier-label>
                                    {{ old('price_tier', $businessInfo['price_tier'] ?? 3) }}
                                </strong>
                                / 5
                            </p>
                        </div>

                    </div>

                    <x-ai-assist-field
                        label="What sets this business apart?"
                        name="differentiator"
                        :value="old('differentiator', $businessInfo['differentiator'] ?? '')"
                        question-key="differentiator"
                        :client-id="$editing ? $client->id : null"
                        :max="200"
                        :disabled="$aiDisabled"
                        placeholder="e.g., The only roastery in Ramallah sourcing beans from small Palestinian farms"
                        footer="In a line or two, what makes this business different from similar ones?"
                        required
                    />
                </div>

                {{-- STEP 3 --}}
                <div
                    class="client-step-panel"
                    data-client-step="2"
                    data-step-title="Market Positioning"
                    data-step-description="Define how the brand should and should not come across."
                >
                    <div class="form-group">
                        <label class="form-label required">Brand Positioning</label>
                        <p class="input-helper">How should the brand come across? Pick up to 2.</p>

                        <div class="checkbox-grid other-option" data-max-checks="2">
                            @foreach ($brandPositioningOptions as $option)
                                <label class="checkbox-row">
                                    <input
                                        type="checkbox"
                                        name="brand_positioning[]"
                                        value="{{ $option }}"
                                        @checked(in_array($option, $selectedBrandPositioning ?? [], true))
                                    >

                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">What this brand avoids being</label>
                        <p class="input-helper">Anything the brand should never come across as? Pick up to 3 or skip.</p>

                        <div class="checkbox-grid other-option" data-max-checks="3">
                            @foreach ($brandAvoidsOptions as $option)
                                <label class="checkbox-row">
                                    <input
                                        type="checkbox"
                                        name="brand_avoids[]"
                                        value="{{ $option }}"
                                        @checked(in_array($option, $selectedBrandAvoids ?? [], true))
                                        data-brand-avoids-option
                                    >

                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div
                        class="form-group conditional-field"
                        data-brand-avoids-other-field
                    >
                        <label class="form-label">Other Avoided Trait</label>

                        <input
                            type="text"
                            name="brand_avoids_other"
                            class="form-input"
                            maxlength="60"
                            value="{{ old('brand_avoids_other', $businessInfo['brand_avoids_other'] ?? '') }}"
                            placeholder="Write what the brand avoids"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Business Age</label>
                        <p class="input-helper">How long has the business been running?</p>

                        <select
                            name="business_age"
                            class="form-select"
                            required
                        >
                            <option value="">Select business age</option>

                            @foreach ([
                                'Just launched (under 6 months)',
                                'Growing (6 months – 2 years)',
                                'Established (2–10 years)',
                                'Mature (10+ years)',
                            ] as $option)
                                <option
                                    value="{{ $option }}"
                                    @selected(old('business_age', $businessInfo['business_age'] ?? '') === $option)
                                >
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- STEP 4 --}}
                <div
                    class="client-step-panel"
                    data-client-step="3"
                    data-step-title="Brand Voice & Rules"
                    data-step-description="Set Arabic style, emoji usage, English usage, and content rules."
                >
                    <div class="form-grid">

                        <div class="form-group">
                            <label class="form-label required">Arabic Variety / Dialect</label>
                            <p class="input-helper">Which kind of Arabic should the content be written in?</p>

                            <select
                                name="arabic_dialect"
                                class="form-select"
                                required
                            >
                                <option value="">Select Arabic style</option>

                                @foreach ([
                                    'Modern Standard Arabic — فصحى',
                                    'Palestinian / Levantine colloquial — عامية',
                                    'Gulf colloquial — خليجي',
                                    'Egyptian colloquial — مصري',
                                    'White / neutral spoken Arabic — محايدة لهجة',
                                    'Mix of MSA + colloquial',
                                ] as $option)
                                    <option
                                        value="{{ $option }}"
                                        @selected(old('arabic_dialect', $brandInfo['arabic_dialect'] ?? '') === $option)
                                    >
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Emoji Usage</label>
                            <p class="input-helper">How often should posts use emojis?</p>

                            <select
                                name="emoji_usage"
                                class="form-select"
                                required
                            >
                                <option value="">Select emoji usage</option>

                                @foreach (['None', 'Minimal', 'Moderate', 'Liberal'] as $option)
                                    <option
                                        value="{{ $option }}"
                                        @selected(old('emoji_usage', $brandInfo['emoji_usage'] ?? '') === $option)
                                    >
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">English Usage</label>
                            <p class="input-helper">Should posts include English, or stay fully Arabic?</p>

                            <select
                                name="english_usage"
                                class="form-select"
                                required
                            >
                                <option value="">Select English usage</option>

                                @foreach ([
                                    'Arabic only',
                                    'Arabic, common English terms allowed',
                                    'Arabic + brand/product names kept in English',
                                ] as $option)
                                    <option
                                        value="{{ $option }}"
                                        @selected(old('english_usage', $brandInfo['english_usage'] ?? '') === $option)
                                    >
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <div class="form-group">
                        <label class="form-label">Words & phrases to avoid</label>
                        <p class="input-helper">Any words or phrases the content should never use? Leave blank if none.</p>

                        <textarea
                            name="words_to_avoid"
                            class="form-textarea"
                            maxlength="5000"
                            placeholder="e.g., never say ‘cheap’; avoid heavy ‘offer / sale’ language"
                        >{{ old('words_to_avoid', $brandInfo['words_to_avoid'] ?? '') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Caption Samples</label>
                        <p class="input-helper">Paste a couple of real posts that already sound the way you want. Optional.</p>

                        <textarea
                            name="caption_samples"
                            class="form-textarea"
                            maxlength="5000"
                            placeholder="Paste 1–2 existing posts that sound the way you want."
                        >{{ old('caption_samples', $brandInfo['caption_samples'] ?? '') }}</textarea>
                    </div>
                </div>

                {{-- STEP 5 --}}
                <div
                    class="client-step-panel"
                    data-client-step="4"
                    data-step-title="Marketing Mechanics"
                    data-step-description="Define how customers take action with this business."
                >
                    <div class="form-group">
                        <label class="form-label required">Conversion Actions</label>
                        <p class="input-helper">How do customers take action? Pick all that apply, then fill in the details.</p>

                        <div class="checkbox-grid other-option">
                            @foreach ($conversionActions as $action)
                                <label class="checkbox-row">
                                    <input
                                        type="checkbox"
                                        name="conversion_actions[]"
                                        value="{{ $action }}"
                                        @checked(in_array($action, $selectedConversionActions ?? [], true))
                                        data-conversion-action
                                    >

                                    <span>{{ $action }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-grid">

                        <div class="form-group conditional-field" data-conversion-detail="Visit the store / location">
                            <label class="form-label">Address or Area</label>
                            <input
                                type="text"
                                name="conversion_location"
                                class="form-input"
                                value="{{ old('conversion_location', $conversion['location'] ?? '') }}"
                                placeholder="e.g., Rafidia, Nablus"
                            >
                        </div>

                        <div class="form-group conditional-field" data-conversion-detail="Order / inquire via WhatsApp">
                            <label class="form-label">WhatsApp Number</label>
                            <input
                                type="text"
                                name="conversion_whatsapp"
                                class="form-input"
                                value="{{ old('conversion_whatsapp', $conversion['whatsapp'] ?? '') }}"
                                placeholder="+970..."
                            >
                        </div>

                        <div class="form-group conditional-field" data-conversion-detail="Call us">
                            <label class="form-label">Phone Number</label>
                            <input
                                type="text"
                                name="conversion_phone"
                                class="form-input"
                                value="{{ old('conversion_phone', $conversion['phone'] ?? '') }}"
                                placeholder="+970..."
                            >
                        </div>

                        <div class="form-group conditional-field" data-conversion-detail="Order via delivery app">
                            <label class="form-label">Delivery App Name(s)</label>
                            <input
                                type="text"
                                name="conversion_delivery_app"
                                class="form-input"
                                value="{{ old('conversion_delivery_app', $conversion['delivery_app'] ?? '') }}"
                                placeholder="e.g., Careem, local delivery app"
                            >
                        </div>

                        <div class="form-group conditional-field" data-conversion-detail="Buy on our website">
                            <label class="form-label">Website URL</label>
                            <input
                                type="text"
                                name="conversion_website"
                                class="form-input"
                                value="{{ old('conversion_website', $conversion['website'] ?? '') }}"
                                placeholder="https://..."
                            >
                        </div>

                        <div class="form-group conditional-field" data-conversion-detail="Book an appointment">
                            <label class="form-label">Booking Link or Method</label>
                            <input
                                type="text"
                                name="conversion_booking"
                                class="form-input"
                                value="{{ old('conversion_booking', $conversion['booking'] ?? '') }}"
                                placeholder="Booking link or booking instructions"
                            >
                        </div>

                        <div class="form-group conditional-field" data-conversion-detail="Message us on Instagram / Facebook">
                            <label class="form-label">Instagram / Facebook Handle</label>
                            <input
                                type="text"
                                name="conversion_social_dm"
                                class="form-input"
                                value="{{ old('conversion_social_dm', $conversion['social_dm'] ?? '') }}"
                                placeholder="@brandname"
                            >
                        </div>

                        <div class="form-group conditional-field" data-conversion-detail="Subscribe / sign up">
                            <label class="form-label">Signup Link</label>
                            <input
                                type="text"
                                name="conversion_signup"
                                class="form-input"
                                value="{{ old('conversion_signup', $conversion['signup'] ?? '') }}"
                                placeholder="https://..."
                            >
                        </div>

                    </div>
                </div>

                {{-- STEP 6 --}}
                <div
                    class="client-step-panel"
                    data-client-step="5"
                    data-step-title="Persona Basics"
                    data-step-description="Create the first target audience persona."
                >
                    <div class="form-grid">

                        <div class="form-group">
                            <label class="form-label required">Persona Name</label>
                            <p class="input-helper">A short name so you can recognize this audience later.</p>

                            <input
                                type="text"
                                name="persona_name"
                                class="form-input"
                                maxlength="50"
                                placeholder="e.g., Young mothers"
                                value="{{ old('persona_name', $primaryPersona->name ?? '') }}"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Gender</label>
                            <p class="input-helper">Who is this content speaking to?</p>

                            <select
                                name="persona_gender"
                                class="form-select"
                                required
                            >
                                <option value="">Select gender</option>

                                @foreach ([
                                    'Women — feminine address',
                                    'Men — masculine address',
                                    'Mixed (everyone) — inclusive forms',
                                ] as $option)
                                    <option
                                        value="{{ $option }}"
                                        @selected(old('persona_gender', $personaAnswers['gender'] ?? '') === $option)
                                    >
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Age Range</label>
                            <p class="input-helper">Roughly how old is this audience?</p>

                            <select
                                name="persona_age_range"
                                class="form-select"
                                required
                            >
                                <option value="">Select age range</option>

                                @foreach ([
                                    '13–17',
                                    '18–24',
                                    '25–34',
                                    '35–44',
                                    '45–60',
                                    '60+',
                                    'All ages',
                                ] as $option)
                                    <option
                                        value="{{ $option }}"
                                        @selected(old('persona_age_range', $primaryPersona->age_range ?? '') === $option)
                                    >
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Who is this audience, in one line?</label>
                            <p class="input-helper">The type of person or their situation.</p>

                            <input
                                type="text"
                                name="persona_who"
                                class="form-input"
                                maxlength="80"
                                placeholder="e.g., New mothers buying for their first baby"
                                value="{{ old('persona_who', $personaAnswers['who'] ?? '') }}"
                                required
                            >
                        </div>

                    </div>
                </div>

                {{-- STEP 7 --}}
                <div
                    class="client-step-panel"
                    data-client-step="6"
                    data-step-title="Persona Motivations"
                    data-step-description="Define buyer relationship, priorities, and objections."
                >
                    
                    <div class="form-group">
                        <label class="form-label required">What matters most to them?</label>
                        <p class="input-helper">Pick up to 2. What does this audience care about most?</p>

                        <div class="checkbox-grid other-option" data-max-checks="2">
                            @foreach ($personaPriorities as $priority)
                                <label class="checkbox-row">
                                    <input
                                        type="checkbox"
                                        name="persona_priorities[]"
                                        value="{{ $priority }}"
                                        @checked(in_array($priority, $selectedPersonaPriorities ?? [], true))
                                    >

                                    <span>{{ $priority }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <x-ai-assist-field
                        label="What makes them hesitate?"
                        name="persona_objection"
                        :value="old('persona_objection', $personaAnswers['objection'] ?? '')"
                        question-key="persona_objection"
                        :client-id="$editing ? $client->id : null"
                        :max="150"
                        :disabled="$aiDisabled"
                        placeholder="e.g., They worry the quality won’t match the price, or they’ve been let down before."
                        footer="What stops them from buying — price worries, trust, habit? Optional."
                    />

                    <div class="form-group">
                        <label class="form-label required">Is the buyer the same as the person who uses the product?</label>
                        <p class="input-helper">Who pays and who actually uses it — are they the same person?</p>

                        <select
                            name="persona_buyer_is_user"
                            class="form-select"
                            data-persona-buyer-select
                            required
                        >
                            <option value="">Select answer</option>

                            @foreach ([
                                'Yes — they buy it and use it themselves',
                                'No — they buy it for someone else',
                                'No — someone else buys it for them',
                            ] as $option)
                                <option
                                    value="{{ $option }}"
                                    @selected(old('persona_buyer_is_user', $personaAnswers['buyer_is_user'] ?? '') === $option)
                                >
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div
                        class="form-group conditional-field"
                        data-persona-decider-field
                    >
                        <label class="form-label">Who actually decides or pays?</label>
                        <p class="input-helper">Required when the buyer and user are not the same.</p>

                        <input
                            type="text"
                            name="persona_decider"
                            class="form-input"
                            maxlength="60"
                            placeholder="e.g., the mother, the company’s HR manager, the husband"
                            value="{{ old('persona_decider', $personaAnswers['decider'] ?? '') }}"
                        >
                    </div>

                </div>

                <div class="client-step-actions">
                    <a
                    href="{{ route('agency.clients.index') }}"
                    class="btn btn-secondary"
                    data-unsaved-leave-link
                >
                    Cancel
                </a>


                    <div class="client-step-actions-right">


                        <button
                        class="btn btn-secondary"
                        type="button"
                        data-prev-step
                    >
                        Back
                    </button>
                    
                        <button
                            class="btn btn-primary"
                            type="button"
                            data-next-step
                        >
                            Next
                        </button>

                        <button
                            class="btn btn-primary"
                            type="submit"
                            data-submit-client
                        >
                            @if ($editing)
                                Save Client Changes
                            @else
                                Create Client Profile
                            @endif
                        </button>

                    </div>

                </div>

            </div>

        </div>

        <div class="form-side-column">

            <div class="table-card sticky-card">

                <h2 class="section-title">Profile Completion</h2>

                <div class="step-mini-progress">
                    <span data-step-counter>
                        Step 1 of {{ count($steps) }}
                    </span>
                </div>

                <div class="completion-list">

                    @foreach ($steps as $index => $step)
                        <div
                            class="completion-item {{ $index === 0 ? 'active' : '' }}"
                            data-step-progress-item
                        >
                            <span>
                                {{ $index === 0 ? '•' : $index + 1 }}
                            </span>

                            {{ $step }}
                        </div>
                    @endforeach

                </div>

            </div>

        </div>

    </form>

</div>

<x-modal
    id="aiAssistModal"
    title="AI Assist"
    subtitle="MARKETHING will use the Business Context and information already entered in this profile to draft an answer for:"
>
    <div class="form-group">
        <label class="form-label" id="aiAssistLabel">
            Field
        </label>

        <p class="input-helper" id="aiAssistHelper">
            Add extra details if needed.
        </p>
    </div>

    <div class="modal-actions">
        <button class="btn btn-primary" type="button" id="runAiAssistBtn">
            ✦ Generate Draft
        </button>

        <button class="btn btn-secondary" type="button" data-close-modal>
            Cancel
        </button>
    </div>
</x-modal>

<x-modal
    id="replaceConfirmationModal"
    title="Replace Existing Text"
    subtitle="This action will overwrite the current field content."
>
    <div class="replace-confirmation-warning">
        <span class="replace-confirmation-warning-icon">
            !
        </span>

        <div>
            <p class="replace-confirmation-text">
                MARKETHING will generate a new draft and replace the text currently written in this field.
            </p>

            <p class="input-helper">
                You can still edit the generated answer manually after it appears.
            </p>
        </div>
    </div>

    <label class="checkbox-row replace-checkbox">
        <input
            type="checkbox"
            id="replaceDontAskAgain"
        >

        <span>
            Don't ask me again
        </span>
    </label>

    <div class="modal-actions">
        <button
            class="btn btn-secondary"
            type="button"
            id="cancelReplaceBtn"
        >
            Cancel
        </button>

        <button
            class="btn btn-primary"
            type="button"
            id="confirmReplaceBtn"
        >
            Continue
        </button>
    </div>
</x-modal>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const businessContext = document.querySelector('[data-business-context-source]');
        const businessCounter = document.querySelector('[data-business-context-counter]');
        const aiButtons = document.querySelectorAll('[data-open-ai-assist]');
        const form = document.querySelector('.staged-client-form');
    
        let formChanged = false;
        let formSubmitted = false;
        let dailyLimitReached = false;
    
        const businessTypesByIndustry = {
            'Food & Beverage': ['Restaurant', 'Cafe', 'Bakery', 'Specialty food product', 'Catering', 'Bar', 'Juice & smoothie bar', 'Sweets & desserts', 'Food truck', 'Other'],
            'Retail': ['Clothing', 'Accessories', 'Home goods', 'Specialty retail', 'Electronics', 'Books & stationery', 'Toys & gifts', 'Jewelry', 'Furniture', 'Other'],
            'E-commerce': ['Online clothing store', 'Online specialty products', 'Handmade & crafts', 'Multi-category online store', 'Other'],
            'Beauty & Personal Care': ['Salon', 'Spa', 'Cosmetics brand', 'Skincare brand', 'Barbershop', 'Nail salon', 'Perfume brand', 'Beauty clinic', 'Other'],
            'Health & Wellness': ['Clinic', 'Dental practice', 'Medical center', 'Pharmacy', 'Nutrition & diet', 'Physiotherapy', 'Alternative medicine', 'Mental health practice', 'Other'],
            'Fitness': ['Gym', 'Personal training', 'Yoga studio', 'CrossFit box', 'Sports club', 'Fitness brand', 'Martial arts studio', 'Other'],
            'Education': ['School', 'Nursery & kindergarten', 'Training center', 'Language institute', 'Online courses', 'Tutoring', 'University & college', 'Educational content', 'Other'],
            'Professional Services': ['Law firm', 'Accounting', 'Consulting', 'Marketing agency', 'Engineering', 'Architecture', 'IT services', 'Translation services', 'HR & recruitment', 'Other'],
            'Creative Services': ['Photography', 'Videography', 'Graphic design', 'Branding studio', 'Content production', 'Printing services', 'Event planning', 'Interior design', 'Other'],
            'Real Estate': ['Real estate agency', 'Property developer', 'Property management', 'Brokerage', 'Vacation rentals', 'Other'],
            'Hospitality': ['Hotel', 'Guesthouse', 'Resort', 'Event venue', 'Travel agency', 'Tourism services', 'Co-working space', 'Other'],
            'Tech': ['Software', 'SaaS', 'Mobile app', 'Tech startup', 'IT solutions', 'E-commerce platform', 'Fintech', 'EdTech', 'Other'],
            'Other': ['Other'],
        };
    
        form?.querySelectorAll('input, textarea, select').forEach((field) => {
            field.addEventListener('input', () => formChanged = true);
            field.addEventListener('change', () => formChanged = true);
        });
    
        form?.addEventListener('submit', () => formSubmitted = true);
    
        window.addEventListener('beforeunload', (event) => {
            if (formChanged && !formSubmitted) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    
        function refreshBusinessTypeOptions() {
            const industrySelect = document.querySelector('[data-industry-select]');
            const businessTypeSelect = document.querySelector('[data-business-type-select]');
    
            if (!industrySelect || !businessTypeSelect) return;
    
            const currentValue = businessTypeSelect.dataset.currentValue || businessTypeSelect.value;
            const options = businessTypesByIndustry[industrySelect.value] || [];
    
            businessTypeSelect.innerHTML = '<option value="">Select business type</option>';
    
            options.forEach((type) => {
                const option = document.createElement('option');
                option.value = type;
                option.textContent = type;
                option.selected = currentValue === type;
                businessTypeSelect.appendChild(option);
            });
    
            businessTypeSelect.dataset.currentValue = businessTypeSelect.value;
        }
    
        function toggleField(field, shouldShow) {
            if (!field) return;
    
            field.style.display = shouldShow ? 'block' : 'none';
    
            field.querySelectorAll('input, select, textarea').forEach((input) => {
                if (!shouldShow) {
                    input.value = '';
                    input.required = false;
                }
            });
        }
    
        function refreshConditionals() {
            const industrySelect = document.querySelector('[data-industry-select]');
            const businessTypeSelect = document.querySelector('[data-business-type-select]');
            const brandAvoidsOther = document.querySelector('[data-brand-avoids-other-field]');
            const personaBuyerSelect = document.querySelector('[data-persona-buyer-select]');
    
            toggleField(document.querySelector('[data-industry-other-field]'), industrySelect?.value === 'Other');
            toggleField(document.querySelector('[data-business-type-other-field]'), businessTypeSelect?.value === 'Other');
    
            const brandAvoidsOtherChecked = Array
                .from(document.querySelectorAll('[data-brand-avoids-option]'))
                .some((checkbox) => checkbox.value === 'Other' && checkbox.checked);
    
            toggleField(brandAvoidsOther, brandAvoidsOtherChecked);
    
            const personaNeedsDecider =
                personaBuyerSelect &&
                personaBuyerSelect.value &&
                personaBuyerSelect.value !== 'Yes — they buy it and use it themselves';
    
            const personaDeciderField = document.querySelector('[data-persona-decider-field]');
            toggleField(personaDeciderField, personaNeedsDecider);
    
            personaDeciderField
                ?.querySelector('input')
                ?.toggleAttribute('required', Boolean(personaNeedsDecider));
    
            document.querySelectorAll('[data-conversion-detail]').forEach((detailField) => {
                const action = detailField.dataset.conversionDetail;
    
                const isChecked = document.querySelector(
                    `[data-conversion-action][value="${CSS.escape(action)}"]`
                )?.checked;
    
                toggleField(detailField, Boolean(isChecked));
    
                const input = detailField.querySelector('input');
    
                if (input) {
                    input.required = Boolean(isChecked) && action !== 'Message us on Instagram / Facebook';
                }
            });
        }
    
        document.querySelector('[data-industry-select]')?.addEventListener('change', () => {
            const businessTypeSelect = document.querySelector('[data-business-type-select]');
    
            if (businessTypeSelect) {
                businessTypeSelect.dataset.currentValue = '';
            }
    
            refreshBusinessTypeOptions();
            refreshConditionals();
        });
    
        document
            .querySelectorAll('[data-business-type-select], [data-persona-buyer-select], [data-brand-avoids-option], [data-conversion-action]')
            .forEach((field) => field.addEventListener('change', refreshConditionals));
    
        refreshBusinessTypeOptions();
        refreshConditionals();
    
        const countrySelect = document.querySelector('[data-country-select]');
        const cityGrid = document.querySelector('[data-palestine-cities]');
        const cityPlaceholder = document.querySelector('[data-city-placeholder]');
    
        function refreshCountryCities() {
            if (!countrySelect) return;
    
            const isPalestine = countrySelect.value === 'Palestine';
    
            cityGrid?.classList.toggle('hidden', !isPalestine);
            cityPlaceholder?.classList.toggle('hidden', isPalestine);
    
            cityGrid?.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                checkbox.required = false;
    
                if (!isPalestine) {
                    checkbox.checked = false;
                }
            });
        }
    
        countrySelect?.addEventListener('change', refreshCountryCities);
        refreshCountryCities();
    
        document.querySelectorAll('[data-max-checks]').forEach((group) => {
            const max = Number(group.dataset.maxChecks || 0);
    
            group.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    const checked = group.querySelectorAll('input[type="checkbox"]:checked');
    
                    if (checked.length > max) {
                        checkbox.checked = false;
                        alert('You can select up to ' + max + ' options only.');
                    }
    
                    refreshConditionals();
                });
            });
        });
    
        const priceRange = document.querySelector('[data-price-tier-range]');
        const priceLabel = document.querySelector('[data-price-tier-label]');
    
        function updatePriceLabel() {
            if (!priceRange || !priceLabel) return;
    
            const labels = {
                1: '1 — Budget',
                2: '2 — Affordable',
                3: '3 — Mid-range',
                4: '4 — Premium',
                5: '5 — Luxury',
            };
    
            priceLabel.textContent = labels[priceRange.value] || priceRange.value;
        }
    
        priceRange?.addEventListener('input', updatePriceLabel);
        updatePriceLabel();
    
        function refreshAiButtons() {
            aiButtons.forEach((button) => {
                button.disabled = dailyLimitReached;
    
                if (dailyLimitReached) {
                    button.classList.add('disabled-ai');
                    button.title = 'Daily AI assist limit reached. Resets at midnight.';
                } else {
                    button.classList.remove('disabled-ai');
                    button.title = '';
                }
            });
        }
    
        if (businessContext && businessCounter) {
            businessContext.addEventListener('input', () => {
                businessCounter.textContent =
                    businessContext.value.length + '/' + businessContext.getAttribute('maxlength');
    
                refreshAiButtons();
            });
        }
    
        refreshAiButtons();
    
        const steps = document.querySelectorAll('[data-client-step]');
        const nextBtn = document.querySelector('[data-next-step]');
        const prevBtn = document.querySelector('[data-prev-step]');
        const submitBtn = document.querySelector('[data-submit-client]');
        const progressFill = document.querySelector('[data-step-progress-fill]');
        const progressItems = document.querySelectorAll('[data-step-progress-item]');
        const stepCounter = document.querySelector('[data-step-counter]');
        const currentStepTitle = document.querySelector('[data-current-step-title]');
        const currentStepDescription = document.querySelector('[data-current-step-description]');
    
        let currentStep = 0;
    
        function showStep(index) {
            steps.forEach((step, i) => step.classList.toggle('active', i === index));
    
            progressItems.forEach((item, i) => {
                const icon = item.querySelector('span');
    
                item.classList.toggle('done', i < index);
                item.classList.toggle('active', i === index);
    
                if (icon) {
                    icon.textContent = i < index ? '✓' : (i === index ? '•' : i + 1);
                }
            });
    
            const activePanel = steps[index];
    
            if (currentStepTitle && activePanel) {
                currentStepTitle.textContent = activePanel.dataset.stepTitle || '';
            }
    
            if (currentStepDescription && activePanel) {
                currentStepDescription.textContent = activePanel.dataset.stepDescription || '';
            }
    
            if (progressFill) {
                progressFill.style.width = (((index + 1) / steps.length) * 100) + '%';
            }
    
            if (stepCounter) {
                stepCounter.textContent = 'Step ' + (index + 1) + ' of ' + steps.length;
            }
    
            if (prevBtn) {
                prevBtn.style.display = index === 0 ? 'none' : 'inline-flex';
            }
    
            if (nextBtn) {
                nextBtn.style.display = index === steps.length - 1 ? 'none' : 'inline-flex';
            }
    
            if (submitBtn) {
                submitBtn.style.display = index === steps.length - 1 ? 'inline-flex' : 'none';
            }
    
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    
        function isHiddenByConditional(field) {
            const wrapper = field.closest('.conditional-field');
            return wrapper && wrapper.style.display === 'none';
        }
    
        function validateCurrentStep() {
            const activeStep = steps[currentStep];
    
            if (!activeStep) return true;
    
            const requiredFields = activeStep.querySelectorAll(
                'input[required], select[required], textarea[required]'
            );
    
            for (const field of requiredFields) {
                if (isHiddenByConditional(field)) continue;
                if (field.type === 'checkbox') continue;
    
                const value = String(field.value || '').trim();
    
                if (!value) {
                    field.focus();
                    alert('Please fill all required fields before continuing.');
                    return false;
                }
            }
    
            if (activeStep.querySelector('[name="conversion_actions[]"]')) {
                if (activeStep.querySelectorAll('[name="conversion_actions[]"]:checked').length === 0) {
                    alert('Please select at least one conversion action before continuing.');
                    return false;
                }
            }
    
            if (activeStep.querySelector('[name="brand_positioning[]"]')) {
                if (activeStep.querySelectorAll('[name="brand_positioning[]"]:checked').length === 0) {
                    alert('Please select at least one brand positioning option before continuing.');
                    return false;
                }
            }
    
            if (activeStep.querySelector('[name="city[]"]') && countrySelect?.value === 'Palestine') {
                if (activeStep.querySelectorAll('[name="city[]"]:checked').length === 0) {
                    alert('Please select at least one city before continuing.');
                    return false;
                }
            }
    
            if (activeStep.querySelector('[name="persona_priorities[]"]')) {
                if (activeStep.querySelectorAll('[name="persona_priorities[]"]:checked').length === 0) {
                    alert('Please select at least one persona priority before continuing.');
                    return false;
                }
            }
    
            return true;
        }
    
        nextBtn?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopImmediatePropagation();
    
            refreshConditionals();
    
            if (!validateCurrentStep()) {
                return false;
            }
    
            if (currentStep < steps.length - 1) {
                currentStep++;
                showStep(currentStep);
            }
    
            return false;
        });
    
        prevBtn?.addEventListener('click', (event) => {
            event.preventDefault();
    
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        });
    
        form?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
    
            const tag = event.target.tagName.toLowerCase();
    
            if (tag === 'textarea') return;
    
            event.preventDefault();
            event.stopImmediatePropagation();
    
            if (currentStep < steps.length - 1) {
                nextBtn?.click();
            } else {
                submitBtn?.click();
            }
        });
    
        showStep(currentStep);
    
        let activeFieldWrapper = null;
        let activeTextarea = null;
    
        const modal = document.getElementById('aiAssistModal');
        const labelEl = document.getElementById('aiAssistLabel');
        const helperEl = document.getElementById('aiAssistHelper');
        const extraInput = null;
        const runBtn = document.getElementById('runAiAssistBtn');
    
        aiButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopImmediatePropagation();
    
                const hasContext = businessContext && businessContext.value.trim().length > 0;
    
                if (!hasContext) {
                    alert("Fill in the business context field at the beginning to activate ‘Help me answer this’ feature.");
                    return false;
                }
    
                if (dailyLimitReached) {
                    alert('Daily AI assist limit reached. Resets at midnight.');
                    return false;
                }
    
                activeFieldWrapper = button.closest('[data-ai-field]');
                activeTextarea = activeFieldWrapper?.querySelector('[data-ai-target-field]');
    
                if (!activeFieldWrapper || !activeTextarea) return false;
    
                labelEl.textContent = button.dataset.aiLabel || 'AI Assist';
                helperEl.textContent = button.dataset.aiHelper || 'Add extra details if needed.';
    
                modal.classList.add('active', 'show');
    
                return false;
            });
        });
    
        function shouldSkipReplaceConfirmation() {
            return localStorage.getItem('markething_ai_assist_skip_replace_confirmation') === 'true';
        }
    
        function askBeforeReplacing() {
            return new Promise((resolve) => {
                const replaceModal = document.getElementById('replaceConfirmationModal');
                const confirmBtn = document.getElementById('confirmReplaceBtn');
                const cancelBtn = document.getElementById('cancelReplaceBtn');
                const checkbox = document.getElementById('replaceDontAskAgain');
    
                if (!replaceModal || !confirmBtn || !cancelBtn) {
                    resolve(window.confirm('This will replace your current text. Continue?'));
                    return;
                }
    
                if (checkbox) checkbox.checked = false;
    
                replaceModal.classList.add('show', 'active');
    
                const cleanup = () => {
                    replaceModal.classList.remove('show', 'active');
                    confirmBtn.removeEventListener('click', onConfirm);
                    cancelBtn.removeEventListener('click', onCancel);
                };
    
                const onConfirm = () => {
                    if (checkbox?.checked) {
                        localStorage.setItem('markething_ai_assist_skip_replace_confirmation', 'true');
                    }
    
                    cleanup();
                    resolve(true);
                };
    
                const onCancel = () => {
                    cleanup();
                    resolve(false);
                };
    
                confirmBtn.addEventListener('click', onConfirm);
                cancelBtn.addEventListener('click', onCancel);
            });
        }
    
        runBtn?.addEventListener('click', async (event) => {
            event.preventDefault();
    
            if (!activeFieldWrapper || !activeTextarea) return;
    
            const existingValue = activeTextarea.value.trim();
    
            if (existingValue.length > 0 && !shouldSkipReplaceConfirmation()) {
                const confirmed = await askBeforeReplacing();
                if (!confirmed) return;
            }
    
            const button = activeFieldWrapper.querySelector('[data-open-ai-assist]');
            const warning = activeFieldWrapper.querySelector('[data-ai-soft-warning]');
            const clicks = Number(activeTextarea.dataset.aiCurrentClicks || 0) + 1;
    
            activeTextarea.dataset.aiCurrentClicks = clicks;
    
            if (clicks >= 3 && warning) {
                warning.classList.remove('hidden');
            }
    
            button.disabled = true;
            runBtn.disabled = true;
            runBtn.textContent = 'Generating...';
            activeTextarea.readOnly = true;
    
            try {
                modal.classList.remove('active', 'show');
    
                showAiLoading(
                    'Drafting Answer...',
                    'MARKETHING is generating a response using your Business Context.'
                );
    
                const csrfToken =
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    
                const response = await fetch('{{ route('agency.ai-assist') }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        ...(button.dataset.clientId ? { client_id: button.dataset.clientId } : {}),
                        question_key: button.dataset.questionKey,
                        question_label: button.dataset.aiLabel,
                        input: '',
                        character_limit: button.dataset.characterLimit,
                        extra_instructions: '',
                        business_context: businessContext?.value || '',
                        business_info: {
                        name: document.querySelector('[name="name"]')?.value || '',

                        industry: document.querySelector('[name="industry"]')?.value || '',
                        industry_other: document.querySelector('[name="industry_other"]')?.value || '',

                        business_type: document.querySelector('[name="business_type"]')?.value || '',
                        business_type_other: document.querySelector('[name="business_type_other"]')?.value || '',

                        country: document.querySelector('[name="country"]')?.value || '',

                        city: Array.from(
                            document.querySelectorAll('[name="city[]"]:checked')
                        ).map((input) => input.value),

                        price_tier: document.querySelector('[name="price_tier"]')?.value || '',

                        differentiator: document.querySelector('[name="differentiator"]')?.value || '',

                        brand_positioning: Array.from(
                            document.querySelectorAll('[name="brand_positioning[]"]:checked')
                        ).map((input) => input.value),

                        brand_avoids: Array.from(
                            document.querySelectorAll('[name="brand_avoids[]"]:checked')
                        ).map((input) => input.value),

                        brand_avoids_other: document.querySelector('[name="brand_avoids_other"]')?.value || '',

                        business_age: document.querySelector('[name="business_age"]')?.value || '',
                    },

                    brand_info: {
                        arabic_dialect: document.querySelector('[name="arabic_dialect"]')?.value || '',
                        emoji_usage: document.querySelector('[name="emoji_usage"]')?.value || '',
                        english_usage: document.querySelector('[name="english_usage"]')?.value || '',
                        words_to_avoid: document.querySelector('[name="words_to_avoid"]')?.value || '',
                        caption_samples: document.querySelector('[name="caption_samples"]')?.value || '',

                        conversion_actions: Array.from(
                            document.querySelectorAll('[name="conversion_actions[]"]:checked')
                        ).map((input) => input.value),

                        conversion: {
                            location: document.querySelector('[name="conversion_location"]')?.value || '',
                            whatsapp: document.querySelector('[name="conversion_whatsapp"]')?.value || '',
                            phone: document.querySelector('[name="conversion_phone"]')?.value || '',
                            delivery_app: document.querySelector('[name="conversion_delivery_app"]')?.value || '',
                            website: document.querySelector('[name="conversion_website"]')?.value || '',
                            booking: document.querySelector('[name="conversion_booking"]')?.value || '',
                            social_dm: document.querySelector('[name="conversion_social_dm"]')?.value || '',
                            signup: document.querySelector('[name="conversion_signup"]')?.value || '',
                        },
                    },
                    }),
                });
    
                const data = await response.json();
    
                if (response.status === 429) {
                    dailyLimitReached = true;
                    refreshAiButtons();
                    alert(data.message || 'Daily AI assist limit reached. Resets at midnight.');
                    return;
                }
    
                if (!response.ok || !data.success) {
                    alert(data.message || 'Couldn’t draft an answer. Try again in a moment.');
                    return;
                }
    
                activeTextarea.value = data.text;
                formChanged = true;
    
                const counter = activeFieldWrapper.querySelector('[data-character-counter]');
    
                if (counter) {
                    counter.textContent =
                        activeTextarea.value.length + '/' + button.dataset.characterLimit;
                }
    
            } catch (error) {
                alert('Couldn’t draft an answer. Try again in a moment.');
            } finally {
                hideAiLoading();
    
                runBtn.disabled = false;
                runBtn.textContent = '✦ Generate Draft';
                activeTextarea.readOnly = false;
    
                refreshAiButtons();
            }
        });
    
        document.querySelectorAll('[data-ai-target-field]').forEach((textarea) => {
            const wrapper = textarea.closest('[data-ai-field]');
            const counter = wrapper?.querySelector('[data-character-counter]');
            const max = textarea.getAttribute('maxlength');
    
            textarea.addEventListener('input', () => {
                if (counter) {
                    counter.textContent = textarea.value.length + '/' + max;
                }
            });
        });
    });
    </script>
    
    @endsection