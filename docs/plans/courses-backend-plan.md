# Plan: dominio de cursos online (backend)

## Contexto

El proyecto es el starter kit oficial de Laravel Vue (Laravel 13, Inertia v3, Fortify, Wayfinder, Pest 5) con un único commit: solo existe el andamiaje de auth y settings. El archivo `database.sql` (sin trackear) esboza en dialecto MySQL el dominio de una plataforma de cursos —`categories`, `courses`, `modules`, `lessons`, `enrollments`— pero no hay ninguna migración, modelo, controlador ni ruta detrás.

El objetivo es materializar ese esquema como backend Laravel completo: migraciones, modelos con relaciones, factories, seeders, resource controllers web (Inertia), rutas, policies y tests Pest. Sin páginas Vue en esta entrega.

Decisiones tomadas con el usuario:
- **Base de datos**: MySQL (el starter kit venía con SQLite; se migra el proyecto a MySQL).
- **Alcance**: solo backend.
- **Roles**: columna `role` en `users` (enum `student`, `instructor`, `admin`).
- **Controladores**: web, resource controllers que devuelven `Inertia::render`.
- **Inscripción**: simple y gratuita, sin tracking de lecciones completadas.

> Nota a tener en cuenta: los controladores devolverán `Inertia::render('courses/Index', ...)` etc. Los tests pasarán (Inertia no exige que exista el archivo `.vue` en tests), pero navegar a esas rutas en el navegador dará error hasta que se creen las páginas Vue en una entrega posterior.

## Adaptaciones al SQL original

`database.sql` ya está en dialecto MySQL, así que la traducción a migraciones es casi literal. Salvedades:

1. **ENUM**: se usa `$table->enum('status', [...])` (MySQL lo soporta de forma nativa) y además se castea a enums PHP respaldados (`App\Enums\CourseStatus`, `EnrollmentStatus`, `UserRole`) para tener tipos en el código.
2. **`UNIQUE KEY uq_module_position (course_id, position)`** se sustituye por un índice normal `(course_id, position)`. El unique rompe cualquier reordenación de módulos (intercambiar dos posiciones colisiona a mitad de la operación) sin aportar integridad real. Si lo prefieres literal, dímelo y lo dejo como unique.
3. **Timestamps**: se respeta el SQL — `courses` y `lessons` llevan `timestamps()`; `categories`, `modules` y `enrollments` no (en `enrollments` mandan `enrolled_at`/`completed_at`), con `public $timestamps = false;` en esos modelos.
4. `SMALLINT`/`TINYINT` → `unsignedSmallInteger` / `unsignedTinyInteger`.

## Implementación

### 0. Base de datos MySQL

El `.env` ya apunta a un servidor MySQL/MariaDB accesible (`172.19.0.3`) pero con una base de otro proyecto (`trepsi_new`). Pasos:

- `.env`: cambiar `DB_DATABASE` a `courses` (ya existe vacía en ese servidor, se reutiliza en vez de crear una nueva).
- `phpunit.xml`: en vez de SQLite en memoria, apuntar los tests a `courses_testing` (base ya creada) para ejercitar el mismo motor MySQL que producción.
- Verificar conexión con `php artisan db:show` antes de migrar.

Generar todo con `php artisan make:* --no-interaction`, nunca a mano.

### 1. Enums — `app/Enums/`

`UserRole`, `CourseStatus` (`Draft`/`Published`/`Archived`), `EnrollmentStatus` (`Active`/`Completed`/`Cancelled`). Enums `string` respaldados, con claves en TitleCase según las convenciones del proyecto. `CourseStatus` con un helper `isPublished(): bool`.

### 2. Migraciones — `database/migrations/`

En este orden (el orden importa por las claves foráneas):

- `add_role_to_users_table` — `enum('role', ['student', 'instructor', 'admin'])->default('student')->index()` después de `email`.
- `create_categories_table` — `id`, `name` (100), `slug` (120) unique. Sin timestamps.
- `create_courses_table` — `foreignId('instructor_id')->constrained('users')`, `foreignId('category_id')->nullable()->constrained()->nullOnDelete()`, `title` (180), `slug` (200) unique, `description` text nullable, `cover_path` nullable, `price` decimal(10,2) default 0, `enum('status', ['draft','published','archived'])` default `draft`, `published_at` nullable, `timestamps()`, índice `['status', 'published_at']`.
- `create_modules_table` — `foreignId('course_id')->constrained()->cascadeOnDelete()`, `title` (180), `unsignedSmallInteger('position')` default 0, índice `['course_id', 'position']`. Sin timestamps.
- `create_lessons_table` — `foreignId('module_id')->constrained()->cascadeOnDelete()`, `title` (180), `content` longText nullable, `video_url` nullable, `unsignedSmallInteger('duration_minutes')` nullable, `is_free_preview` bool default false, `position` default 0, `timestamps()`, índice `['module_id', 'position']`.
- `create_enrollments_table` — `foreignId('user_id')` y `foreignId('course_id')` ambos `cascadeOnDelete`, `enum('status', ['active','completed','cancelled'])` default `active`, `unsignedTinyInteger('progress_percent')` default 0, `enrolled_at`, `completed_at` nullable, unique `['user_id', 'course_id']`, índice `['user_id', 'status']`.

Ojo con el límite de 191 caracteres por índice en MySQL antiguo: `courses.slug` es VARCHAR(200) y va indexado como unique. Con MySQL 8 + utf8mb4 no hay problema (límite de 3072 bytes); si el servidor fuera MySQL 5.7 habría que acortar el slug a 191.

### 3. Modelos — `app/Models/`

Seguir exactamente el estilo de [User.php](app/Models/User.php): atributos `#[Fillable([...])]` (no propiedad `$fillable`), bloque `@property` completo, método `casts()`, tipos de retorno explícitos en las relaciones.

- **`Category`** — `hasMany(Course::class)`.
- **`Course`** — `belongsTo(User::class, 'instructor_id')` como `instructor()`, `belongsTo(Category::class)`, `hasMany(Module::class)`, `hasManyThrough(Lesson::class, Module::class)`, `hasMany(Enrollment::class)`, `belongsToMany(User::class, 'enrollments')` como `students()`. Casts: `status` → `CourseStatus`, `price` → `decimal:2`, `published_at` → datetime. `getRouteKeyName(): string => 'slug'` para binding por slug. Scopes `published()` y `forInstructor(User $user)`.
- **`Module`** — `belongsTo(Course::class)`, `hasMany(Lesson::class)`, `$timestamps = false`.
- **`Lesson`** — `belongsTo(Module::class)`, cast `is_free_preview` → bool.
- **`Enrollment`** — `belongsTo(User::class)`, `belongsTo(Course::class)`, casts de `status`/`enrolled_at`/`completed_at`, `$timestamps = false`.
- **`User`** (editar) — añadir `'role'` al `#[Fillable]`, `@property UserRole $role`, cast a `UserRole`, y las relaciones `courses()` (hasMany por `instructor_id`), `enrollments()`, `enrolledCourses()` (belongsToMany). Helper `isInstructor(): bool`.

### 4. Factories y seeders

Una factory por modelo (`--factory` al crear el modelo). Estados útiles: `CourseFactory::published()`, `draft()`, `free()`; `UserFactory::instructor()`; `LessonFactory::freePreview()`; `EnrollmentFactory::completed()`. Slugs con `Str::slug(...)` + sufijo único para no colisionar con el índice unique.

Aprovechar para arreglar `UserFactory::withTwoFactor()` en [UserFactory.php:49](database/factories/UserFactory.php#L49), que tiene el cuerpo vacío y revienta con error de tipo si se llama.

`CourseSeeder` que crea unas categorías fijas, instructores, cursos publicados con módulos y lecciones anidadas, y algunas inscripciones; registrarlo desde `DatabaseSeeder`.

### 5. Policies — `app/Policies/`

`CoursePolicy` (más `ModulePolicy` y `LessonPolicy` que delegan en el curso padre):
- `viewAny`/`view`: público si el curso está publicado; el instructor dueño y los admin ven también borradores.
- `create`: `$user->isInstructor()` o admin.
- `update`/`delete`: `$user->id === $course->instructor_id`, o admin.

Laravel 13 autodescubre las policies por convención de nombres; no hace falta registrarlas.

### 6. Controladores y rutas

Todos con `php artisan make:controller X --resource --model=Y`, con `authorizeResource` o `Gate::authorize` explícito, y form requests dedicados (`php artisan make:request`) siguiendo el patrón de `app/Http/Requests/Settings/`.

Nuevo archivo `routes/courses.php`, requerido desde [web.php](routes/web.php) igual que `settings.php`:

| Controlador | Rutas | Acceso |
|---|---|---|
| `CourseController` | `index`, `show` públicas (solo publicados); `create`/`store`/`edit`/`update`/`destroy` | auth + policy |
| `Instructor\CourseModuleController` | `index`…`destroy` anidado en course | instructor dueño |
| `Instructor\ModuleLessonController` | anidado en module | instructor dueño |
| `CategoryController` | `index`, `show` | público |
| `EnrollmentController` | `index` (mis cursos), `store` (inscribirse), `destroy` (cancelar) | auth |

`EnrollmentController@store`: exige curso publicado, usa `firstOrCreate` sobre `user_id`+`course_id` para respetar el unique, fija `enrolled_at = now()`, y devuelve `to_route(...)` con `Inertia::flash('toast', ...)` como hace [ProfileController.php:41](app/Http/Controllers/Settings/ProfileController.php#L41).

`CourseController@index` carga con `with(['instructor:id,name', 'category'])` y `withCount('lessons')` paginado, para evitar N+1.

Se generan los helpers de Wayfinder al final con `php artisan wayfinder:generate` para que las futuras páginas Vue tengan los tipos listos.

### 7. Tests — `tests/Feature/`

Pest, creados con `php artisan make:test --pest`. `RefreshDatabase` ya se aplica globalmente desde `tests/Pest.php`.

- `CourseIndexTest` — el listado muestra solo publicados; un borrador no aparece.
- `CourseManagementTest` — un instructor crea/edita/borra su curso; otro instructor recibe 403; un estudiante recibe 403 al crear.
- `ModuleLessonTest` — CRUD anidado y aislamiento por dueño; borrar un curso arrastra módulos y lecciones (cascade).
- `EnrollmentTest` — inscribirse funciona; inscribirse dos veces no duplica; no se puede inscribir en un curso borrador; cancelar funciona.
- `tests/Unit/CourseTest.php` — el scope `published()` y los casts de enum.

## Verificación

```bash
php artisan db:show                       # confirmar que conecta a MySQL
php artisan migrate:fresh --seed          # esquema + datos de prueba
php artisan test --compact tests/Feature/CourseIndexTest.php   # y el resto, uno a uno
php artisan route:list --except-vendor    # revisar las rutas generadas
vendor/bin/pint --dirty --format agent    # formato
vendor/bin/phpstan analyse                 # larastan
```

Inspeccionar el esquema resultante con la herramienta `database-schema` de Boost y un par de consultas con `database-query` sobre los datos del seeder. Al final, pedir al usuario que ejecute la suite completa: `php artisan test --compact`.
