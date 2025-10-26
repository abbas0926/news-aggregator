# News Aggregator Backend

A Laravel backend that fetches news articles from multiple sources (NewsAPI, The Guardian, NY Times) and provides a REST API for accessing them.

## What's Included

-   REST API with filtering, search, and pagination
-   Fetches from 3 news sources automatically
-   User authentication with Laravel Sanctum
-   User preferences for personalized feeds
-   Queue-based article fetching
-   Scheduled fetching

## Quick Start

### Requirements

-   PHP 8.2+
-   Composer

### Installation

1. Clone and install dependencies:

```bash
cd news-aggregator
composer install
```

2. Create environment file:

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure database in `.env`:

For MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=news_aggregator
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

4. Add your API keys to `.env`:

```env
NEWSAPI_KEY=your_key_here
GUARDIAN_KEY=your_key_here
NYTIMES_KEY=your_key_here
```

See "Getting API Keys" section below for details.

5. Create database:

MySQL:

```bash
mysql -u root -p
CREATE DATABASE news_aggregator;
exit
```

PostgreSQL:

```bash
psql -U postgres
CREATE DATABASE news_aggregator;
\q
```

6. Run migrations:

```bash
php artisan migrate
```

This creates the tables and seeds 3 sources and 10 categories automatically.

7. Fetch some articles:

```bash
php artisan articles:fetch --sync
```

8. Start the server:

```bash
php artisan serve
```

That's it. The API is now running at `http://localhost:8000`

## Getting API Keys

### NewsAPI

1. Go to https://newsapi.org/register
2. Register and copy your API key
3. Add to `.env`: `NEWSAPI_KEY=your_key`

### The Guardian

1. Go to https://open-platform.theguardian.com/access/
2. Register and check your email for the key
3. Add to `.env`: `GUARDIAN_KEY=your_key`

### NY Times

1. Go to https://developer.nytimes.com/get-started
2. Create an account and app
3. Enable "Top Stories API"
4. Add to `.env`: `NYTIMES_KEY=your_key`

## API Endpoints

### Public Endpoints

```
GET  /api/v1/articles                 - Get articles (with filters)
GET  /api/v1/articles/{id}            - Get single article
GET  /api/v1/sources                  - Get available sources
GET  /api/v1/categories               - Get categories

POST /api/v1/register                 - Register new user
POST /api/v1/login                    - Login
```

### Protected Endpoints (requires authentication)

```
GET  /api/v1/me                       - Get current user
POST /api/v1/logout                   - Logout
GET  /api/v1/user/preferences         - Get user preferences
PUT  /api/v1/user/preferences         - Update preferences
```

## Example Requests

### Get articles

```bash
curl http://localhost:8000/api/v1/articles
```

### Search and filter

```bash
curl "http://localhost:8000/api/v1/articles?search=technology&source=newsapi"
```

### Login

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

### Access protected endpoint

```bash
curl http://localhost:8000/api/v1/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Available Filters

When fetching articles, you can use these parameters:

-   `search` - Search in title and description
-   `source` - Filter by source: newsapi, guardian, nytimes
-   `category` - Filter by category slug
-   `author` - Filter by author name
-   `date_from` - Start date (YYYY-MM-DD)
-   `date_to` - End date (YYYY-MM-DD)
-   `page` - Page number
-   `per_page` - Items per page (max 100)

## Fetching Articles

### Manual fetch (synchronous)

```bash
php artisan articles:fetch --sync
```

### Using queue (recommended)

```bash
# Dispatch jobs
php artisan articles:fetch

# Run worker in another terminal
php artisan queue:work
```

### Automated fetching

Articles are fetched automatically every hour via Laravel's scheduler.

On Linux/Mac, add to crontab:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

On Windows, use Task Scheduler to run `php artisan schedule:run` every minute.

Test it manually:

```bash
php artisan schedule:run
```

## Configuration

Queue configuration is in `config/queue.php`. Default is database driver (no Redis needed).

API keys are in `config/services.php`, reading from environment variables.

## Testing with Seeded Data

A test user is created during seeding:

-   Email: test@example.com
-   Password: password

Use these credentials to test authentication endpoints.

## Adding New News Sources

1. Create new class implementing `NewsSourceInterface`:

```php
class BBCSource implements NewsSourceInterface {
    public function fetchArticles(array $filters = []): Collection {}
    public function getName(): string { return 'BBC'; }
    public function getSlug(): string { return 'bbc'; }
}
```

2. Add to factory in `NewsSourceFactory.php`
3. Add source to database via seeder
4. Add API key to config
