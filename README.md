# Laravel API Boilerplate

This project is a reusable Laravel API starter focused on authentication, validation, service-based business logic, and clean module scaffolding. It is intentionally generic and does not include sample domain features such as posts, products, or an app-specific CRUD module.

## What this starter includes

- Laravel API structure with versioned routes under `/api/v1`
- Sanctum authentication
- Reusable API response helper
- Form request validation
- User profile update flow
- Password reset flow
- Generic service layer pattern for future modules
- Feature tests covering auth and account behavior

## Architecture overview

### Model

Models live in `app/Models`.

Example:

- `app/Models/User.php`

Responsibilities:

- Eloquent model definition
- Casts, fillable attributes, relationships
- Database access logic only at the model layer

Notes:

- Keep business rules out of the model when they become complex.
- For feature-specific logic, move it into a service class.
- This starter uses the default `User` model for authentication and account actions.

### Request validation

Form requests live in `app/Http/Requests`.

Examples:

- `RegisterUserRequest`
- `LoginUserRequest`
- `UpdateProfileRequest`
- `ResetPasswordRequest`

Responsibilities:

- Validate incoming payloads
- Enforce authorization rules with `authorize()`
- Keep controller methods thin and focused on orchestration

### Controller

Controllers live in `app/Http/Controllers`.

Example:

- `app/Http/Controllers/Api/V1/AuthController.php`

Responsibilities:

- Accept HTTP input
- Validate request data
- Call a service layer
- Return a consistent JSON response

Important rule:

- The controller should not contain business logic. It should orchestrate workflows and delegate logic to services.

### Service layer

Services live in `app/Services`.

Examples:

- `AuthService`
- `BaseCrudService`

Responsibilities:

- Encapsulate reusable auth and business logic
- Keep controllers readable
- Make logic easier to test and reuse across modules

The `BaseCrudService` is meant to be the default scaffold for new modules:

- `list()`
- `find()`
- `create()`
- `update()`
- `delete()`

This pattern gives each new module a consistent structure without repeating CRUD code.

### Resource layer

Resources live in `app/Http/Resources`.

Example:

- `app/Http/Resources/UserResource.php`

Responsibilities:

- Shape JSON payloads to return to API consumers
- Hide internal attribute details when needed
- Keep API output consistent

### View layer

This starter is API-first, so there are no Blade view files by default.

The application responds with JSON using:

- API controllers
- Resource classes
- `ApiResponse` helper

If you want frontend pages later, you can add them separately without changing this API-focused structure.

## Local setup

Follow these steps to run the project locally.

### 1. Copy environment variables

Copy `.env.example` to `.env` if it is not already present:

```bash
cp .env.example .env
```

### 2. Set the API protection key

Open `.env` and set a local secret:

```env
API_AUTH_KEY=your-local-secret-key
```

This key is required on protected API requests through the `X-API-Key` header.

### 3. Start the Docker environment

From the project root:

```bash
docker compose up -d --build
```

### 4. Install PHP dependencies

```bash
docker compose exec app composer install
```

### 5. Generate the Laravel app key

```bash
docker compose exec app php artisan key:generate
```

### 6. Run database migrations

```bash
docker compose exec app php artisan migrate
```

### 7. Run the app tests

```bash
docker compose exec app php artisan test --filter=AuthApiTest --compact
```

### 8. Start using the API

Protected endpoints require the header:

```http
X-API-Key: your-local-secret-key
```

## API routes

Routes are defined in `routes/api.php`.

### Public auth routes

```http
POST /api/v1/auth/register
POST /api/v1/auth/login
GET /api/v1/health
```

### Authenticated routes

```http
GET /api/v1/auth/me
PUT /api/v1/auth/profile
POST /api/v1/auth/password/reset
POST /api/v1/auth/logout
```

## Example requests

### Register user

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'
```

### Login user

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "jane@example.com",
    "password": "Password123!"
  }'
```

### Get authenticated profile

```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Update profile

```bash
curl -X PUT http://localhost:8000/api/v1/auth/profile \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Smith",
    "email": "jane.smith@example.com"
  }'
```

### Reset password

```bash
curl -X POST http://localhost:8000/api/v1/auth/password/reset \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "current_password": "Password123!",
    "password": "NewPassword456!",
    "password_confirmation": "NewPassword456!"
  }'
```

## Reusable pattern for new modules

Use this structure whenever you add a new feature:

1. Create or update the model in `app/Models`
2. Add validation in `app/Http/Requests`
3. Add a controller in `app/Http/Controllers/Api/V1`
4. Put business logic in `app/Services`
5. Add a resource in `app/Http/Resources`
6. Register route in `routes/api.php`
7. Add tests in `tests/Feature`

Template pattern:

```php
class ExampleController extends Controller
{
    public function __construct(
        protected ExampleService $exampleService,
    ) {}

    public function index()
    {
        $items = $this->exampleService->list();

        return ApiResponse::success([
            'items' => ExampleResource::collection($items),
        ]);
    }
}
```

And in the service:

```php
class ExampleService extends BaseCrudService
{
    protected function modelClass(): string
    {
        return Example::class;
    }
}
```

This keeps controller logic consistent and makes future modules easy to build.

## Suggested conventions

- Controllers should be thin and route-focused.
- Validation belongs in form requests.
- Shared logic belongs in services.
- Use `ApiResponse` for consistent JSON responses.
- Use resources for serialized output.
- Put tests next to behavior and validate the API contract.

## Authorization conventions

This starter uses a consistent naming pattern for Laravel authorization so it stays predictable across policies, gates, and route middleware.

### Policy methods

Policy methods should use camelCase and match the action name:

```php
public function manageUsers(User $user): bool
{
    return $user->hasRole('admin');
}
```

### Gate and ability names

Ability names passed to `Gate::define()`, `can:...`, and `$user->can(...)` should use snake_case:

```php
Gate::define('manage_users', fn (User $user): bool => $user->hasRole('admin'));
```

This keeps the route middleware and the authorization API aligned:

```php
Route::middleware('can:manage_users')->group(function () {
    // protected routes
});
```

### Why the two names differ

- `manageUsers` is the method name inside the policy class.
- `manage_users` is the ability identifier used by Laravel authorization checks.

The mapping is intentional and should stay consistent. Avoid mixing the two styles in the same feature unless there is a clear reason.

## Testing

Use the Laravel test suite for validation.

Example:

```bash
php artisan test
```

Or a focused auth test:

```bash
php artisan test --filter=AuthApiTest
```

This starter is designed to be a clean base for building API-first Laravel applications quickly and consistently.
