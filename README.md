MARKETHING

MARKETHING is a Laravel-based web application for creating and managing Facebook and Instagram marketing campaigns with AI-assisted campaign and post generation.

The application allows agency users to manage clients, audience personas, campaigns, generated social media posts, prompts, and AI generation logs.

⸻

Technology Stack

* PHP
* Laravel 10
* Blade
* MySQL
* Composer
* Anthropic Claude API
* Laravel Authentication
* Eloquent ORM

⸻

Project Structure

Important directories:

app/
├── Http/
│   ├── Controllers/
│   │   ├── Agency/
│   │   └── Admin/
│   └── Requests/
│       └── Agency/
├── Models/
└── Services/
    └── AI/
config/
database/
├── migrations/
└── seeders/
resources/
└── views/
routes/
storage/
└── logs/

Important AI Services

The main AI-related services are located under:

app/Services/AI/

These services are responsible for:

* Preparing campaign context
* Compiling prompts
* Calling Anthropic Claude
* Parsing Claude responses
* Validating generated posts
* Persisting generated posts
* Logging AI requests and responses

⸻

Deployment

Requirements

Before deploying the application, install:

* PHP version compatible with Laravel 10
* Composer
* MySQL
* Git
* A web server such as Nginx or Apache
* HTTPS/SSL for production

The web server document root must point to:

public/

⸻

1. Clone the Repository

git clone <REPOSITORY_URL>
cd <PROJECT_DIRECTORY>

Use the production branch:

git checkout main

⸻

2. Install Dependencies

For production:

composer install --no-dev --prefer-dist --optimize-autoloader

For development:

composer install

⸻

3. Configure Environment

Create the environment file:

cp .env.example .env

Configure at least:

APP_NAME=MARKETHING
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=markething
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
ANTHROPIC_API_KEY=your_anthropic_api_key

Check config/ai.php for the exact Anthropic configuration used by the current application.

Additional AI configuration may include:

ANTHROPIC_MODEL=
ANTHROPIC_MAX_TOKENS=

Never commit .env or API keys to the repository.

⸻

4. Generate the Application Key

For a completely new installation:

php artisan key:generate

Important

Do not generate a new APP_KEY when moving an existing production database/environment unless an intentional key rotation is being performed.

Changing the key can invalidate encrypted application data.

⸻

Database Setup

5. Create the MySQL Database

Create an empty MySQL database and configure the corresponding DB_* variables in .env.

Then run:

php artisan migrate --force

Check migration status with:

php artisan migrate:status

⸻

Initial Database Seeding

A completely new installation requires the initial application data to be seeded.

The important seeders include:

database/seeders/
├── DatabaseSeeder.php
├── UserSeeder.php
├── PromptSeeder.php
└── AppSettingSeeder.php

The exact contents of DatabaseSeeder.php should be treated as authoritative for the current repository version.

For a fresh database:

php artisan db:seed --force

If the seeders are not registered through DatabaseSeeder.php, run them explicitly:

php artisan db:seed --class=AppSettingSeeder --force
php artisan db:seed --class=PromptSeeder --force
php artisan db:seed --class=UserSeeder --force

⸻

Seeder Responsibilities

AppSettingSeeder

Creates the default application settings required by the application.

These settings may control limits such as:

* Maximum campaign duration
* Other application-level configuration values

Do not assume that settings should be reseeded on an existing production database.

⸻

PromptSeeder

Creates the initial prompt/template data required for AI generation.

A clean deployment must have an active master prompt available before campaign generation can work.

After deployment, verify that:

* prompt records exist;
* prompt versions exist;
* the required master prompt exists;
* an active version can be resolved by the AI prompt services.

⸻

UserSeeder

Creates initial application users where required.

If the seeder contains default credentials:

1. Use them only for initial bootstrap.
2. Log in.
3. Immediately change the password.
4. Do not leave development/test credentials in production.

For an existing production database, do not blindly rerun UserSeeder.

⸻

Existing Production Database

When deploying a new version over an existing database:

git pull
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan view:cache

Do not automatically run all seeders.

Before running any seeder against an existing database, inspect whether it uses:

* create()
* updateOrCreate()
* hard-coded IDs
* destructive operations
* default credentials
* active prompt/version changes

Always create a database backup before manually reseeding production data.

⸻

Laravel Cache / Optimization

After deployment:

php artisan optimize:clear
php artisan config:cache
php artisan view:cache

If compatible with the application’s route configuration:

php artisan route:cache

⸻

File Permissions

The web server must be able to write to:

storage/
bootstrap/cache/

Do not give the web server unnecessary write access to the entire project.

⸻

AI Campaign Generation

The campaign generation pipeline is approximately:

Campaign Form
     ↓
StoreCampaignRequest
     ↓
CampaignGenerationService
     ↓
Active Master Prompt
     ↓
Structured INPUT_JSON
     ↓
Claude API
     ↓
AIResponseParser
     ↓
Backend Validation
     ↓
campaign_posts
     ↓
LLM Logging

The backend remains responsible for validating the AI output.

Prompt instructions alone must not be treated as application validation.

⸻

Prompt Configuration

Prompt templates and versions are managed through the application’s prompt system.

The prompt contains two different types of information:

Safe to Edit

The following areas can generally be modified without changing application code:

* Brand voice
* Arabic dialect instructions
* Writing style
* Caption-writing guidance
* Cultural guidance
* Marketing strategy
* Campaign planning guidance
* Hook-writing guidance
* Creative direction guidance
* Examples
* Platform writing recommendations
* Boost-selection strategy
* Additional quality guidelines

These changes should preserve the existing input/output contract.

⸻

Sensitive Areas

The following areas must be edited carefully because they are connected to application code:

* JSON structure
* JSON property names
* Post field names
* Channel/platform values
* Media format values
* Campaign date rules
* Number of generated posts
* campaign.format_modes
* boost.recommended
* {{INPUT_JSON}}
* Parser-dependent fields

If any of these contracts change, review the corresponding PHP services before activating the prompt.

⸻

Prompt Elements That Must Not Be Removed

{{INPUT_JSON}}

The application replaces this placeholder with the structured campaign/client/persona information before sending the prompt to Claude.

Do not rename or remove it.

⸻

JSON-Only Requirement

Claude must return valid JSON.

Do not change the prompt so that Claude returns:

* explanations outside the JSON;
* Markdown around the JSON;
* code fences around the JSON;
* planning text;
* commentary before or after the JSON.

The response must remain compatible with AIResponseParser.

⸻

Campaign Post Count

The generated campaign must contain the requested number of posts.

The backend also validates the result.

⸻

Supported Platforms

Generated posts must resolve to:

facebook
instagram

Do not introduce arbitrary platform names without updating backend validation.

⸻

Supported Formats

Generated posts currently resolve to:

image
carousel
reel

Campaigns may allow multiple formats through campaign.format_modes.

Do not revert this to the previous single-format structure without updating the application.

⸻

Boost Recommendation

Each generated post contains a boost recommendation flag.

The AI response uses:

"boost": {
    "recommended": true
}

The parser maps this value to:

boost_recommended

in campaign_posts.

The value is a boolean:

true

or:

false

Do not remove or rename this field without updating the parser and persistence logic.

⸻

Changing Prompts Safely

Recommended procedure:

1. Create or edit a prompt version through the prompt management interface.
2. Keep the currently working version available.
3. Test the new version.
4. Generate a small campaign.
5. Inspect the raw Claude response.
6. Verify that the response is valid JSON.
7. Verify the number of generated posts.
8. Verify dates.
9. Verify channels.
10. Verify formats.
11. Verify captions and creative directions.
12. Verify boost.recommended.
13. Verify boost_recommended is persisted correctly.
14. Only activate the new prompt after successful testing.

If a prompt update breaks generation, reactivate the previous known-good prompt version.

⸻

AI Troubleshooting

When campaign generation fails, check in this order:

1. Browser/application error
2. Laravel log
3. LLM log entry
4. Compiled prompt
5. Raw Claude response
6. Parser error
7. Backend validation error
8. Database persistence

Laravel logs are normally located at:

storage/logs/laravel.log

Useful commands:

php artisan optimize:clear
php artisan migrate:status
php artisan route:list
composer dump-autoload

⸻

Common AI Failure Cases

No JSON array found in Claude response

Usually indicates that the Claude response was not in the format expected by the parser.

Inspect the raw response in the LLM logs before changing the parser.

⸻

Invalid JSON

Check:

* prompt JSON requirements;
* Claude response;
* unescaped quotation marks;
* unexpected Markdown;
* missing commas/brackets;
* prompt changes affecting the output schema.

⸻

Incorrect Post Count

Check:

* campaign requested post count;
* campaign duration;
* selected channels;
* CampaignGenerationService;
* prompt instructions;
* parser output;
* backend post-count validation.

⸻

Incorrect Format

Check:

* campaign.format_modes;
* prompt format instructions;
* parser normalization;
* backend format validation.

⸻

boost_recommended Always False

Compare:

"boost": {
    "recommended": true
}

in the raw Claude response against the value stored in:

campaign_posts.boost_recommended

If Claude returns true but the database contains 0, inspect:

* AIResponseParser;
* CampaignGenerationService;
* CampaignPost $fillable;
* model casts;
* database migration;
* persistence mapping.

⸻

Production Security

Never commit:

.env
API keys
database passwords
production credentials
session secrets

Use environment variables or the hosting provider’s secret-management system.

Production should use:

APP_ENV=production
APP_DEBUG=false

Always use HTTPS.

⸻

Backups

The production MySQL database should have:

* scheduled backups;
* retained backup copies;
* a documented restore process;
* periodic restore testing.

A backup that has never been restored should not be considered a verified recovery mechanism.

⸻

Third-Party Services

MARKETHING currently depends on external infrastructure/services such as:

* Anthropic Claude API
* MySQL hosting
* Web hosting/server
* DNS/domain provider
* HTTPS/TLS certificate provider
* Git repository hosting
* Error monitoring, if configured

Record the actual production provider and access information separately using a secure credential-management system.

Do not place credentials in this README.

⸻

Production Smoke Test

After deploying a clean environment, verify:

* Application loads over HTTPS
* Authentication works
* Administrative routes work
* Agency routes work
* Users can access their permitted resources
* Clients can be created
* Personas can be created
* Campaign can be created
* Campaign generation successfully calls Claude
* Generated posts are persisted
* Multiple content formats work
* Facebook and Instagram generation works
* boost_recommended is persisted correctly
* Post regeneration works
* Prompt management works
* LLM logs are created
* Laravel logs contain no unexpected production errors
* Database backups are configured
* Error monitoring is operational

⸻

Important Maintenance Rule

When changing MARKETHING, distinguish between:

Prompt-only changes

Changes to:

* wording;
* tone;
* examples;
* strategy;
* dialect;
* creative guidance;
* marketing rules.

These normally require only prompt testing.

Application-contract changes

Changes to:

* JSON structure;
* field names;
* database fields;
* enum values;
* campaign format representation;
* post count rules;
* channel representation;
* parser behavior.

These require coordinated changes to the relevant Laravel services, parser, validation, models, migrations, and/or frontend.

Do not change an application contract through the prompt alone.

⸻

Final Deployment Checklist

Before handing a deployment to the next technical team:

* Code is deployed from the intended branch/tag
* Composer dependencies are installed
* .env is configured
* APP_KEY is configured correctly
* Database connection works
* All migrations are applied
* Required initial settings are seeded
* Required prompt records are seeded
* Initial privileged user exists
* Default credentials have been secured
* Active master prompt exists
* Anthropic API credentials work
* Campaign generation works
* Post regeneration works
* Prompt editing/versioning works
* LLM logging works
* Backups are configured
* HTTPS is enabled
* APP_DEBUG=false
* Production logs are accessible
* Error monitoring is configured
* Production smoke test passes

⸻

Source of Truth

For future maintenance, use the repository itself as the technical source of truth.

In particular:

* database/migrations/ defines the database schema.
* database/seeders/ defines initial seeded data.
* app/Services/AI/ defines the AI integration behavior.
* app/Http/Requests/ defines server-side validation.
* app/Models/ defines persistence behavior and casts.
* The active prompt/version defines the current AI writing and campaign strategy.
* AIResponseParser and CampaignGenerationService define the boundary between Claude output and persisted campaign posts.

When documentation and implementation differ, verify the current code, migrations, and active configuration before making production changes.
