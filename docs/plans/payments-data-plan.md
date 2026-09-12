# Plan: tablas `lesson_progress` y `payments` (migraciones + modelos)

## Contexto

El dominio de cursos ya está completo (ver `docs/plans/courses-backend-plan.md` y `courses-frontend-plan.md`). `database.sql` añade dos tablas que todavía no existen en el proyecto:

- **`lesson_progress`** — progreso por lección de una inscripción (`completed_at`, `seconds_watched`), unique por `(enrollment_id, lesson_id)`. Es la base para dejar de tener `enrollments.progress_percent` como número muerto.
- **`payments`** — solicitud de acceso a un curso. El nuevo flujo de negocio es: el estudiante _solicita_ el curso → se crea un `payment` con `status = pending` → un **admin** lo marca `confirmed` o `rejected` → solo al confirmarse se crea (o se reactiva, si estaba `cancelled`) el `enrollment`.

**Alcance de esta entrega (decidido con el usuario)**: solo la capa de datos — enums, migraciones, modelos con relaciones, factories, seeder y tests. El cambio de flujo en `EnrollmentController@store`, el controlador de confirmación, la policy y las páginas Vue quedan para la siguiente entrega (ver sección final). Mientras tanto, `EnrollmentController@store` sigue creando el enrollment directamente y sus tests siguen en verde.

Decisiones adicionales:

- Los cursos gratuitos (`price = 0`) **también** pasan por un `payment` (con `amount = 0`); el flujo es uniforme.
- Se respeta el SQL tal cual: **no** se añaden `reference_code` ni `proof_path`.
- Solo los admin confirman/rechazan (afecta a la siguiente entrega; aquí solo condiciona que `confirmed_by` apunte a `users` y que la factory use `UserFactory::admin()`).

## Adaptaciones al SQL

1. **Nombre de tabla `lesson_progress`** (singular): Eloquent pluralizaría `LessonProgress` a `lesson_progresses`, así que el modelo declara `protected $table = 'lesson_progress'`.
2. **ENUMs** → `$table->enum(...)` + enums PHP respaldados (`PaymentStatus`, `PaymentMethod`), igual que `CourseStatus`/`EnrollmentStatus`.
3. **`currency CHAR(3)`** → `$table->char('currency', 3)->default('USD')`.
4. **`seconds_watched INT`** → `unsignedInteger`.
5. **Timestamps**: `payments` los lleva; `lesson_progress` no (`$timestamps = false`), como `enrollments`.
6. **Sin unique en `payments (user_id, course_id)`** a propósito: un estudiante puede tener un pago `rejected` y luego otro `pending` para el mismo curso. La regla "solo un `pending` a la vez" se aplicará en la capa de aplicación en la siguiente entrega, no en el esquema.

> Nota: el Dockerfile ya apunta a PostgreSQL pero `.env`/`phpunit.xml` siguen en MySQL. `enum()` funciona en ambos (en Postgres genera un `CHECK`), así que no hay que hacer nada especial.

## Implementación

Todo se genera con `php artisan make:* --no-interaction`; seguir el estilo de los ficheros hermanos (atributo `#[Fillable]`, bloque `@property`, `casts()`, tipos de retorno explícitos).

### 1. Enums — `app/Enums/`

- `PaymentStatus`: `Pending = 'pending'`, `Confirmed = 'confirmed'`, `Rejected = 'rejected'`. Helper `isConfirmed(): bool` (mismo patrón que `CourseStatus::isPublished()`).
- `PaymentMethod`: `Cash = 'cash'`, `BankTransfer = 'bank_transfer'`, `Other = 'other'`.

### 2. Migraciones — `database/migrations/`

Dos migraciones, en este orden (la de `lesson_progress` depende de `enrollments` y `lessons`, la de `payments` de `users` y `courses`; ambas ya existen):

**`create_lesson_progress_table`**

```php
$table->id();
$table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
$table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
$table->timestamp('completed_at')->nullable();
$table->unsignedInteger('seconds_watched')->default(0);
$table->unique(['enrollment_id', 'lesson_id']);
```

**`create_payments_table`**

```php
$table->id();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->foreignId('course_id')->constrained()->cascadeOnDelete();
$table->decimal('amount', 10, 2);
$table->char('currency', 3)->default('USD');
$table->enum('method', ['cash', 'bank_transfer', 'other'])->default('bank_transfer');
$table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
$table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('confirmed_at')->nullable();
$table->text('notes')->nullable();
$table->timestamps();
$table->index(['status', 'created_at']);
```

### 3. Modelos — `app/Models/`

**`LessonProgress`** (nuevo, `--factory`)

- `$table = 'lesson_progress'`, `$timestamps = false`.
- Fillable: `enrollment_id`, `lesson_id`, `completed_at`, `seconds_watched`.
- Casts: `completed_at` → datetime, `seconds_watched` → integer.
- Relaciones: `enrollment(): BelongsTo`, `lesson(): BelongsTo`.
- Helper `isCompleted(): bool` (`completed_at !== null`).

**`Payment`** (nuevo, `--factory`)

- Fillable: `user_id`, `course_id`, `amount`, `currency`, `method`, `status`, `confirmed_by`, `confirmed_at`, `notes`.
- Casts: `amount` → `decimal:2`, `method` → `PaymentMethod`, `status` → `PaymentStatus`, `confirmed_at` → datetime.
- Relaciones: `user(): BelongsTo`, `course(): BelongsTo`, `confirmer(): BelongsTo` (`User`, FK `confirmed_by`).
- Scopes: `scopePending()`, `scopeConfirmed()` (mismo estilo que `Course::scopePublished()` en [Course.php:126](app/Models/Course.php#L126)).
- Helper `isConfirmed(): bool` delegando en el enum.

**Modelos existentes (añadir relaciones)**

- `Enrollment` → `lessonProgress(): HasMany<LessonProgress>`.
- `Lesson` → `progress(): HasMany<LessonProgress>`.
- `Course` → `payments(): HasMany<Payment>`.
- `User` → `payments(): HasMany<Payment>` y `confirmedPayments(): HasMany<Payment>` (FK `confirmed_by`).

Actualizar los bloques `@property`/`@property-read` de cada uno.

### 4. Factories — `database/factories/`

**`LessonProgressFactory`**

- `enrollment_id => Enrollment::factory()`, `lesson_id => Lesson::factory()`, `seconds_watched => fake()->numberBetween(0, 1800)`, `completed_at => null`.
- Estado `completed()`: `completed_at => now()`.

**`PaymentFactory`**

- `user_id => User::factory()`, `course_id => Course::factory()->published()`, `amount` como closure que lee el precio del curso ya creado (`fn (array $attributes) => Course::find($attributes['course_id'])->price`) para que el pago sea coherente con el curso, `currency => 'USD'`, `method => PaymentMethod::BankTransfer`, `status => PaymentStatus::Pending`, resto `null`.
- Estados: `confirmed(?User $by = null)` (status Confirmed, `confirmed_at => now()`, `confirmed_by => $by ?? User::factory()->admin()`), `rejected()` (status Rejected, `confirmed_at`/`confirmed_by` rellenos igual, `notes` con un motivo), `cash()`.

### 5. Seeder — `database/seeders/CourseSeeder.php`

Ampliar el seeder actual (no crear uno nuevo) para que los datos cuenten la historia completa:

- Crear un admin (`User::factory()->admin()->create(['email' => 'admin@example.com'])`) que será el `confirmed_by`.
- Para cada `Enrollment` que ya crea el bucle final ([CourseSeeder.php:61](database/seeders/CourseSeeder.php#L61)), crear antes un `Payment::factory()->confirmed($admin)` para el mismo usuario y curso, y después 2–3 `LessonProgress` sobre lecciones del curso (alguna `completed()`).
- Añadir unos cuantos `Payment` `pending` y uno `rejected` de otros estudiantes, para que el futuro panel de admin tenga algo que mostrar.

### 6. Tests — `tests/Feature/`

Pest, `php artisan make:test --pest`. Seguir el estilo de [CourseScopesTest.php](tests/Feature/CourseScopesTest.php) (el proyecto pone los tests de modelo en `Feature/` porque necesitan DB).

**`PaymentModelTest`**

- Los casts devuelven `PaymentStatus`/`PaymentMethod` y `amount` como string `decimal:2`.
- `Payment::pending()` y `Payment::confirmed()` filtran correctamente.
- `confirmer()` devuelve el admin; al borrar ese admin, `confirmed_by` pasa a `null` (nullOnDelete) y el pago sigue existiendo.
- Borrar el curso arrastra sus pagos (cascade).
- La factory `confirmed()` hereda el `amount` del precio del curso.

**`LessonProgressModelTest`**

- Relaciones `enrollment()`/`lesson()` y `isCompleted()`.
- Insertar dos filas con el mismo `(enrollment_id, lesson_id)` lanza `QueryException` (unique).
- Borrar la inscripción arrastra su progreso (cascade).

Tras los tests, `php artisan wayfinder:generate` no hace falta (no hay rutas nuevas).

## Verificación

```bash
php artisan migrate:fresh --seed                              # esquema + datos coherentes
php artisan test --compact tests/Feature/PaymentModelTest.php
php artisan test --compact tests/Feature/LessonProgressModelTest.php
php artisan test --compact                                    # la suite completa debe seguir en verde (66 → ~76)
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
```

Comprobar con `database-schema` de Boost que `lesson_progress` tiene el unique y `payments` el índice `(status, created_at)` y la FK `confirmed_by` con `SET NULL`; con `database-query`, que cada `enrollment` del seeder tiene un `payment` confirmado del mismo `user_id`/`course_id`.

Opcional: guardar este plan como `docs/plans/payments-data-plan.md` junto a los otros dos.

## Siguiente entrega (fuera de alcance, para dejar el camino marcado)

- `EnrollmentController@store` pasa a crear un `Payment` `pending` (rechazando si ya hay uno `pending` para ese usuario/curso) en vez del `Enrollment`; `courses/Show` muestra "Request access" / "Pending approval".
- `Admin\PaymentController` con `index` (pendientes) y `update` (confirm/reject) + `PaymentPolicy` (solo `isAdmin()`), en una transacción: al confirmar, `Enrollment::updateOrCreate(['user_id','course_id'], ['status' => Active, 'enrolled_at' => now(), 'completed_at' => null])` para crear o reactivar la inscripción cancelada.
- Página de lección que escriba en `lesson_progress` y recalcule `enrollments.progress_percent`.
