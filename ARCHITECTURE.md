# System Architecture & Design Patterns

## Overview

This News Aggregator system is built following SOLID principles and implements multiple design patterns to ensure maintainability, scalability, and testability.

## Design Patterns Implemented

### 1. Strategy Pattern

**Purpose:** Define a family of interchangeable algorithms (news sources)

**Implementation:**

```
NewsSourceInterface (Contract)
    ├── NewsApiSource
    ├── GuardianSource
    └── NYTimesSource
```

**Benefits:**

-   ✅ Add new sources without modifying existing code (Open/Closed Principle)
-   ✅ Sources are interchangeable at runtime
-   ✅ Each source encapsulates its own fetching logic

**Usage:**

```php
$source = NewsSourceFactory::make('newsapi');
$articles = $source->fetchArticles();
```

---

### 2. Adapter Pattern

**Purpose:** Convert different API response formats to a unified structure

**Implementation:**

```
Each NewsSource class has an adaptArticle() method:
- NewsAPI response → ArticleData
- Guardian response → ArticleData
- NY Times response → ArticleData
```

**Example:**

```php
// NewsAPI returns: { "title": "...", "urlToImage": "..." }
// Guardian returns: { "webTitle": "...", "fields": { "thumbnail": "..." } }
// NY Times returns: { "title": "...", "multimedia": [...] }

// All adapted to:
ArticleData {
    title: string
    url_to_image: string
    // ... other standardized fields
}
```

**Benefits:**

-   ✅ Decouples external API formats from internal data structure
-   ✅ Easy to handle API changes
-   ✅ Consistent data across all sources

---

### 3. Repository Pattern

**Purpose:** Abstract data access layer from business logic

**Implementation:**

```
ArticleRepositoryInterface (Contract)
    └── ArticleRepository (Implementation)
```

**Benefits:**

-   ✅ Business logic doesn't depend on database details
-   ✅ Easy to swap database or add caching
-   ✅ Simplifies testing with mock repositories

**Usage:**

```php
// Dependency Injection in Controller
public function __construct(
    private ArticleRepositoryInterface $repository
) {}

$articles = $this->repository->search($criteria);
```

---

### 4. Factory Pattern

**Purpose:** Centralized object creation for news sources

**Implementation:**

```php
NewsSourceFactory::make('newsapi')  → NewsApiSource
NewsSourceFactory::make('guardian') → GuardianSource
NewsSourceFactory::make('nytimes')  → NYTimesSource
```

**Benefits:**

-   ✅ Single point of control for object creation
-   ✅ Easy to add new sources
-   ✅ Type-safe instantiation

---

## SOLID Principles

### Single Responsibility Principle (SRP)

Each class has ONE reason to change:

-   `NewsApiSource` - Only changes if NewsAPI integration changes
-   `ArticleRepository` - Only changes if data access logic changes
-   `NewsAggregatorService` - Only changes if fetching orchestration changes

### Open/Closed Principle (OCP)

System is open for extension, closed for modification:

-   ✅ Add new news source: Create new class implementing `NewsSourceInterface`
-   ✅ Add new repository: Create new class implementing `ArticleRepositoryInterface`
-   ❌ No need to modify existing source classes

### Liskov Substitution Principle (LSP)

Any implementation can replace the interface:

```php
// These are interchangeable
NewsSourceInterface $source = new NewsApiSource();
NewsSourceInterface $source = new GuardianSource();
NewsSourceInterface $source = new NYTimesSource();

// All work the same way
$articles = $source->fetchArticles();
```

### Interface Segregation Principle (ISP)

Interfaces are focused and minimal:

-   `NewsSourceInterface` - Only fetch-related methods
-   `ArticleRepositoryInterface` - Only data access methods
-   No "fat" interfaces forcing unnecessary implementations

### Dependency Inversion Principle (DIP)

High-level modules depend on abstractions, not concrete classes:

```php
// ✅ Depends on interface
class ArticleController {
    public function __construct(ArticleRepositoryInterface $repo) {}
}

// ✅ Depends on interface
class NewsAggregatorService {
    public function fetchFrom(NewsSourceInterface $source) {}
}

// ❌ NOT depending on concrete classes like:
// public function __construct(ArticleRepository $repo) {}
```

---

## System Flow

### Article Fetching Flow

```
1. Command/Scheduler
   └── php artisan articles:fetch
       └── FetchArticlesCommand

2. Job Dispatch
   └── FetchArticlesFromSourceJob::dispatch($source)
       └── Queue System

3. Job Processing
   └── FetchArticlesFromSourceJob::handle()
       ├── NewsSourceFactory::make($slug)
       ├── NewsAggregatorService::fetchFrom($source)
       └── ArticleRepository::store()

4. Source Fetching (Strategy Pattern)
   └── $source->fetchArticles()
       ├── HTTP Request to API
       ├── adaptArticle() (Adapter Pattern)
       └── Return Collection<ArticleData>

5. Storage (Repository Pattern)
   └── ArticleRepository::store()
       ├── Check duplicates (findByUrl)
       └── Save to database
```

### API Request Flow

```
1. HTTP Request
   └── GET /api/v1/articles?search=tech&source=newsapi

2. Routing
   └── routes/api.php → ArticleController@index

3. Validation
   └── ArticleSearchRequest validates query parameters

4. Repository Query
   └── ArticleRepository::search($criteria)
       ├── Query Builder with filters
       ├── Eager load relationships
       └── Return paginated results

5. Resource Transformation
   └── ArticleCollection → ArticleResource
       └── Format response with metadata

6. JSON Response
   └── { data: [...], meta: {...}, links: {...} }
```

---

## Database Schema

### Entity Relationship Diagram

```
users
  └─── user_preferences (1:1)

sources (1:many)
  └─── articles (many)
      └─── categories (many:1)
```

### Key Indexes

```sql
-- Fast article lookups
articles.title (index)
articles.published_at (index)
articles.url (unique)
articles.[url, source_id] (composite unique)
articles.[source_id, published_at] (composite index)

-- Fast source/category filtering
sources.slug (unique)
categories.slug (unique)
```

---

## Service Layer Architecture

```
Controllers (HTTP Layer)
    ↓ (depends on)
Services (Business Logic)
    ↓ (depends on)
Repositories (Data Access)
    ↓ (depends on)
Models (Eloquent ORM)
    ↓
Database
```

**Benefits:**

-   Clean separation of concerns
-   Easy to test each layer independently
-   Business logic reusable across different entry points (API, CLI, etc.)

---

## Error Handling Strategy

### Levels of Error Handling

1. **Source Level** (NewsApiSource, etc.)

    - Catch API errors
    - Log with context
    - Throw exception up

2. **Service Level** (NewsAggregatorService)

    - Handle per-article errors gracefully
    - Continue processing other articles
    - Log warnings for individual failures

3. **Job Level** (FetchArticlesFromSourceJob)

    - Retry mechanism (3 attempts)
    - Exponential backoff (1min, 5min, 15min)
    - Log final failure
    - Store in failed_jobs table

4. **Controller Level** (ArticleController)
    - Return appropriate HTTP status codes
    - Format user-friendly error messages
    - Don't expose sensitive details

---

## Scalability Considerations

### Current Setup (Small to Medium Scale)

-   SQLite database (simple, portable)
-   Database queue driver
-   Single server deployment

### Scaling Up (Medium to Large Scale)

**Database:**

```
SQLite → PostgreSQL/MySQL
- Better concurrent write performance
- Advanced indexing options
- Full-text search capabilities
```

**Queue:**

```
Database Queue → Redis Queue
- Faster job processing
- Better for high-volume jobs
- Job prioritization
```

**Caching:**

```
Add Redis/Memcached
- Cache popular article searches
- Cache source/category lists
- Reduce database load
```

**Horizontal Scaling:**

```
Single Server → Load Balanced Servers
- Multiple queue workers
- Database read replicas
- CDN for static assets
```

---

## Testing Strategy (Future Implementation)

### Unit Tests

```php
// Test individual components in isolation
NewsApiSourceTest
ArticleRepositoryTest
NewsAggregatorServiceTest
```

### Feature Tests

```php
// Test complete features
ArticleSearchTest
ArticleFetchTest
UserPreferenceTest
```

### Integration Tests

```php
// Test with mocked external APIs
use Http::fake();

test('fetches from NewsAPI', function() {
    Http::fake([
        'newsapi.org/*' => Http::response(['articles' => [...]]),
    ]);

    $this->artisan('articles:fetch --source=newsapi --sync')
        ->assertSuccessful();
});
```

---

## Security Considerations

### Implemented

-   ✅ API keys stored in environment variables
-   ✅ Database query parameterization (SQL injection prevention)
-   ✅ Input validation via Form Requests
-   ✅ Rate limiting ready (Laravel built-in)

### Recommended for Production

-   🔒 API authentication (Laravel Sanctum)
-   🔒 Rate limiting on public endpoints
-   🔒 HTTPS only in production
-   🔒 CORS configuration
-   🔒 Input sanitization for XSS prevention
-   🔒 Regular security updates

---

## Performance Optimizations

### Current

-   ✅ Database indexing on frequently queried fields
-   ✅ Eager loading relationships (N+1 prevention)
-   ✅ Pagination for large result sets
-   ✅ Queue for non-blocking operations

### Future Enhancements

-   📈 Query result caching
-   📈 API response caching
-   📈 Database query optimization
-   📈 Lazy loading for large content fields
-   📈 CDN for images

---

## Monitoring & Logging

### Current Logging

```
storage/logs/laravel.log - All application logs
storage/logs/fetch-articles.log - Scheduled fetch logs
```

### Log Levels

-   `INFO` - Normal operations (fetch started, completed)
-   `WARNING` - Recoverable errors (individual article failed)
-   `ERROR` - Critical errors (entire source fetch failed)

### Recommended Monitoring (Production)

-   Application Performance Monitoring (APM)
-   Error tracking (Sentry, Bugsnag)
-   Queue monitoring (Laravel Horizon)
-   Database performance monitoring
-   API response time tracking

---

## Adding New Features

### Add New News Source

1. Create new source class:

```php
class BBCSource implements NewsSourceInterface {
    public function fetchArticles(array $filters = []): Collection {}
    public function getName(): string { return 'BBC'; }
    public function getSlug(): string { return 'bbc'; }
    private function adaptArticle(array $rawData): ArticleData {}
}
```

2. Update factory:

```php
// NewsSourceFactory.php
'bbc' => new BBCSource(),
```

3. Add to seeder:

```php
// SourceSeeder.php
['name' => 'BBC', 'slug' => 'bbc', ...]
```

4. Add API key to config:

```php
// config/services.php
'bbc' => ['key' => env('BBC_KEY')],
```

Done! ✅

---

## Code Quality Metrics

### Adherence to Principles

-   ✅ SOLID Principles: All 5 implemented
-   ✅ DRY (Don't Repeat Yourself): Common logic extracted
-   ✅ KISS (Keep It Simple): Simple, understandable code
-   ✅ Separation of Concerns: Clear layer boundaries

### Code Organization

-   ✅ PSR-4 autoloading
-   ✅ Laravel conventions followed
-   ✅ Type hints everywhere (PHP 8+)
-   ✅ Meaningful naming
-   ✅ Single Responsibility per class

---

## Conclusion

This architecture provides:

-   ✅ **Maintainability** - Easy to understand and modify
-   ✅ **Scalability** - Can grow with requirements
-   ✅ **Testability** - Each component can be tested in isolation
-   ✅ **Extensibility** - New features add without breaking existing code
-   ✅ **Reliability** - Error handling and retry mechanisms
-   ✅ **Performance** - Optimized queries and queue processing

The system is production-ready for small to medium traffic and can be scaled up as needed.
