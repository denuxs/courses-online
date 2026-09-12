# Plan: Dashboard por rol (`/dashboard`)

> **Estado: implementado tal cual está descrito** (`DashboardController`, tests en
> `tests/Feature/DashboardTest.php`, componentes en `resources/js/components/dashboard/` y el
> filtrado de `AppSidebar.vue` por rol). Ver también el resumen en el [README](../../README.md#dashboard).

## Contexto

`resources/js/pages/Dashboard.vue` sigue siendo el placeholder del starter kit (cuatro bloques `PlaceholderPattern`) y la ruta es `Route::inertia('dashboard', 'Dashboard')` sin controlador, así que no recibe datos. El dominio ya tiene todo lo necesario para un dashboard útil: `Enrollment` (con `progress_percent` almacenado), `Payment` (scopes `pending()` / `confirmed()`), `Course` (`published()`, `forInstructor()`), y `User` con `role` (`UserRole::Student|Instructor|Admin`, helpers `isInstructor()` / `isAdmin()`; **no existe `isStudent()`**, Student es el caso por defecto).

**Decisiones tomadas con el usuario**:

- **Un dashboard por rol**: un solo `Dashboard.vue`, el controlador devuelve datos distintos según `$user->role`.
- **Estudiante**: solo contadores (cursos activos, completados, pagos pendientes).
- **Instructor**: contadores (cursos publicados, borradores, alumnos) + lista de sus cursos recientes con nº de inscritos + últimas inscripciones en sus cursos.
- **Admin**: contador y lista de los últimos 5 pagos pendientes con enlace a `admin.payments.index` + métricas globales (usuarios, cursos, inscripciones activas, ingresos confirmados).
- **Textos en español** (como la landing; el resto de la app sigue en inglés).
- **Filtrar el sidebar por rol**: ocultar "Teaching" a estudiantes y "Payments" a no-admins.
- **Tests Pest** del controlador por rol.

Patrón a seguir: `app/Http/Controllers/HomeController.php` (invocable, array shapes en PHPDoc) y las páginas `enrollments/Index.vue`, `instructor/Courses.vue`, `admin/Payments.vue` (Card + Badge + `Heading`, breadcrumbs vía `defineOptions`).

## Backend

### 1. Controlador — `app/Http/Controllers/DashboardController.php`

`php artisan make:controller DashboardController --invokable --no-interaction`

```php
public function __invoke(Request $request): Response
{
    $user = $request->user();

    return Inertia::render('Dashboard', match ($user->role) {
        UserRole::Admin => $this->adminProps(),
        UserRole::Instructor => $this->instructorProps($user),
        default => $this->studentProps($user),
    });
}
```

Cada método privado devuelve `['role' => 'admin'|'instructor'|'student', 'stats' => [...], ...listas]` con array shapes documentados en PHPDoc:

**`studentProps(User $user)`**

```php
'role'  => 'student',
'stats' => [
    'active_courses'    => $user->enrollments()->where('status', EnrollmentStatus::Active)->count(),
    'completed_courses' => $user->enrollments()->where('status', EnrollmentStatus::Completed)->count(),
    'pending_payments'  => $user->payments()->pending()->count(),
],
```

**`instructorProps(User $user)`**

```php
$courseIds = $user->courses()->select('id');

'role'  => 'instructor',
'stats' => [
    'published_courses' => $user->courses()->where('status', CourseStatus::Published)->count(),
    'draft_courses'     => $user->courses()->where('status', CourseStatus::Draft)->count(),
    'students'          => Enrollment::query()->whereIn('course_id', $courseIds)
                               ->where('status', '!=', EnrollmentStatus::Cancelled)
                               ->distinct('user_id')->count('user_id'),
],
'courses' => $user->courses()->with('category')->withCount(['lessons', 'enrollments'])
                 ->latest()->limit(5)->get(),
'recent_enrollments' => Enrollment::query()->whereIn('course_id', $courseIds)
                 ->with(['user:id,name', 'course:id,title,slug'])
                 ->latest('enrolled_at')->limit(5)->get(),
```

**`adminProps()`**

```php
'role'  => 'admin',
'stats' => [
    'users'              => User::query()->count(),
    'courses'            => Course::query()->count(),
    'active_enrollments' => Enrollment::query()->where('status', EnrollmentStatus::Active)->count(),
    'confirmed_revenue'  => (float) Payment::query()->confirmed()->sum('amount'),
    'pending_payments'   => Payment::query()->pending()->count(),
],
'pending_payments' => Payment::query()->pending()
                          ->with(['user:id,name,email', 'course:id,title,slug'])
                          ->latest()->limit(5)->get(),
```

Sin caché: las métricas son baratas y el admin necesita ver los pagos pendientes al momento. Un admin ve **solo** el dashboard de admin (no el de instructor), coherente con `match` por rol.

### 2. Ruta — `routes/web.php`

Sustituir `Route::inertia('dashboard', 'Dashboard')` por `Route::get('dashboard', DashboardController::class)->name('dashboard')` dentro del grupo `auth, verified`. La URL y el nombre no cambian, así que `@/routes` `dashboard()` sigue válido (ejecutar `php artisan wayfinder:generate` igualmente para regenerar `@/actions`).

### 3. Tests — `tests/Feature/DashboardTest.php` (ampliar el existente)

Mantener los 2 tests actuales y añadir, con `assertInertia`:

1. Estudiante: `role === 'student'`, `stats.active_courses` / `completed_courses` / `pending_payments` correctos (usar `EnrollmentFactory` + `completed()`, `PaymentFactory` pendiente y `confirmed($admin)`), y `missing('pending_payments')` (no recibe datos de admin).
2. Instructor: `role === 'instructor'`, `stats.published_courses` / `draft_courses`, `stats.students` cuenta usuarios distintos y excluye cancelados, `courses` solo incluye los suyos (crear un curso de otro instructor y comprobar que no aparece), `recent_enrollments` solo de sus cursos.
3. Admin: `role === 'admin'`, `stats.pending_payments` y `has('pending_payments', n)`, `stats.confirmed_revenue` suma solo pagos confirmados.

Recordar: `Course::factory()->for($instructor, 'instructor')` (la relación se llama `instructor`, no `user`) y `Enrollment::factory()->for($user, 'user')->for($course, 'course')`; `CourseFactory` crea un instructor nuevo por defecto, así que fijar el instructor cuando el conteo importe.

## Frontend

### 4. Tipos

- `resources/js/types/auth.ts`: añadir `role: 'student' | 'instructor' | 'admin'` a `User` (hoy solo accesible por la index signature).
- Nuevo `resources/js/types/dashboard.ts` (reexportar desde `types/index.ts`):

```ts
export type StudentDashboardProps = {
    role: "student";
    stats: {
        active_courses: number;
        completed_courses: number;
        pending_payments: number;
    };
};
export type InstructorDashboardProps = {
    role: "instructor";
    stats: {
        published_courses: number;
        draft_courses: number;
        students: number;
    };
    courses: Course[]; // con enrollments_count y lessons_count
    recent_enrollments: Enrollment[]; // con user y course
};
export type AdminDashboardProps = {
    role: "admin";
    stats: {
        users: number;
        courses: number;
        active_enrollments: number;
        confirmed_revenue: number;
        pending_payments: number;
    };
    pending_payments: Payment[];
};
export type DashboardProps =
    StudentDashboardProps | InstructorDashboardProps | AdminDashboardProps;
```

Añadir `enrollments_count?: number` a `Course` y `user?: Pick<User, 'id' | 'name'>` a `Enrollment` en `types/courses.ts`.

### 5. Componentes — `resources/js/components/dashboard/`

| Componente                | Contenido                                                                                                                                                                                                                                                                                                                                                      | Props                                                                 |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------- |
| `StatCard.vue`            | `Card` con etiqueta, valor grande (`Intl.NumberFormat('es')`), ícono `@lucide/vue` opcional y `href` opcional (envuelve en `Link`). Reutilizable por los tres roles.                                                                                                                                                                                           | `label`, `value`, `icon?`, `href?`, `format?: 'number' \| 'currency'` |
| `StudentDashboard.vue`    | 3 `StatCard`: "Cursos activos" → `enrollments.index`, "Cursos completados" → `enrollments.index`, "Pagos pendientes". Debajo, botón "Explorar cursos" → `courses.index`.                                                                                                                                                                                       | `stats`                                                               |
| `InstructorDashboard.vue` | 3 `StatCard` (publicados/borradores → `instructor.courses.index`, alumnos). Sección "Mis cursos recientes": lista de `Card` con título (Link a `courses.show`), `Badge` de estado, `X lecciones · Y inscritos`; botón "Nuevo curso" → `courses.create`. Sección "Inscripciones recientes": lista con nombre de alumno, curso y fecha. Estados vacíos en ambas. | `stats`, `courses`, `recent_enrollments`                              |
| `AdminDashboard.vue`      | 5 `StatCard` (ingresos con `format: 'currency'`, pagos pendientes → `admin.payments.index`). Sección "Pagos pendientes": lista con alumno, curso, importe, método y fecha; botón "Ver todos" → `admin.payments.index`. Estado vacío.                                                                                                                           | `stats`, `pending_payments`                                           |

### 6. Página — `resources/js/pages/Dashboard.vue`

```vue
<script setup lang="ts">
const props = defineProps<DashboardProps>();
// breadcrumbs igual que ahora
</script>
<template>
    <Head title="Dashboard" />
    <div class="space-y-6">
        <Heading
            title="Dashboard"
            :description="`Hola, ${$page.props.auth.user.name}`"
        />
        <StudentDashboard v-if="props.role === 'student'" v-bind="props" />
        <InstructorDashboard
            v-else-if="props.role === 'instructor'"
            v-bind="props"
        />
        <AdminDashboard v-else v-bind="props" />
    </div>
</template>
```

Eliminar el import de `PlaceholderPattern` (el componente se queda porque no se usa en otro sitio… verificar con grep antes de borrarlo; si nadie más lo usa, dejarlo igualmente: fuera de alcance).

### 7. Sidebar — `resources/js/components/AppSidebar.vue`

Convertir `mainNavItems` en `computed` usando `usePage().props.auth.user.role`:

- Dashboard, Courses, Categories, My enrollments: todos.
- Teaching: `instructor` y `admin` (ambos pueden crear cursos según `CoursePolicy::create`).
- Payments: solo `admin`.

Limpiar los imports de íconos sin uso (`FolderGit2`, `BookOpen`) solo si el linter lo exige.

## Orden de ejecución

1. `DashboardController` + ruta + `php artisan wayfinder:generate`.
2. Ampliar `DashboardTest` → `php artisan test --compact tests/Feature/DashboardTest.php` en verde.
3. Tipos (`auth.ts`, `dashboard.ts`, `courses.ts`).
4. `StatCard` → `StudentDashboard` / `InstructorDashboard` / `AdminDashboard` → `Dashboard.vue`.
5. `AppSidebar.vue` filtrado por rol.
6. `vendor/bin/pint --dirty --format agent`; `npx prettier --write` **solo sobre los ficheros tocados** (el repo tiene deriva de formato preexistente, no reformatear todo); `npm run types:check`; `npm run build`.

## Verificación

- Tests: `php artisan test --compact tests/Feature/DashboardTest.php`, luego pedir al usuario que corra la suite completa.
- Manual (con `composer run dev`): iniciar sesión con un usuario de cada rol del `CourseSeeder`/`DatabaseSeeder` y comprobar `/dashboard`: contadores coherentes con `/my-enrollments`, `/instructor/courses` y `/admin/payments`; el sidebar oculta Teaching/Payments según rol; estados vacíos con un usuario nuevo; modo oscuro y ancho móvil (~400px).

## Fuera de alcance

- Widgets de estudiante más allá de contadores (continuar aprendiendo, pagos pendientes, recomendados) — descartados por el usuario.
- Calcular `progress_percent` desde `lesson_progress` (hoy es una columna almacenada que nadie actualiza).
- Middleware de rol en las rutas de instructor/admin (la autorización sigue en gates/controladores).
- Traducir el resto de la app al español.
