# Plan: Landing page (`/`)

## Contexto

`resources/js/pages/Welcome.vue` es hoy el esqueleto de Laravel: solo un `<nav>` con Login/Register/Dashboard. La ruta `/` está declarada como `Route::inertia('/', 'Welcome')` en `routes/web.php`, sin controlador, por lo que no puede recibir datos. `app.ts` ya resuelve `Welcome` con `layout: null`, así que la landing controla su propio header/footer y no hereda el sidebar de `AppLayout`.

**Decisiones tomadas con el usuario**:

- Idioma **español**. Reutilizar shadcn-vue (`components/ui/`), Tailwind y el soporte de modo oscuro existente.
- Contenido dinámico: **últimos 6 cursos publicados** y **3 contadores** (cursos publicados, estudiantes inscritos, instructores) cacheados 1 h.
- Secciones estáticas: hero + CTA, beneficios, cómo funciona, testimonios (hardcoded por ahora) y footer.
- Usuarios autenticados **ven la landing** y el header muestra `Dashboard` en vez de Login/Register (comportamiento actual).
- Secciones extraídas a `resources/js/components/landing/`; `Welcome.vue` solo las compone.
- Feature test con Pest para el controlador.

> Nota: el resto de la UI (`CourseCard`, `courses/Index`) está en inglés ("By", "lessons", "Free"). La landing irá en español según lo acordado; unificar idioma del resto de la app queda fuera de alcance.

## Backend

### 1. Controlador — `app/Http/Controllers/HomeController.php`

`php artisan make:controller HomeController --invokable --no-interaction`

```php
public function __invoke(): Response
{
    return Inertia::render('Welcome', [
        'featured_courses' => Course::query()
            ->published()
            ->with(['instructor:id,name', 'category'])
            ->withCount('lessons')
            ->latest('published_at')
            ->limit(6)
            ->get(),
        'stats' => Cache::remember('landing.stats', now()->addHour(), fn (): array => [
            'courses' => Course::query()->published()->count(),
            'students' => Enrollment::query()->where('status', EnrollmentStatus::Active)->distinct('user_id')->count('user_id'),
            'instructors' => User::query()->where('role', UserRole::Instructor)->count(),
        ]),
    ]);
}
```

- Misma cadena de query que `CourseController@index` (scope `published()`, eager load de `instructor:id,name` y `category`, `withCount('lessons')`) para que `CourseCard` funcione sin cambios.
- `stats` como array shape documentado en PHPDoc: `array{courses: int, students: int, instructors: int}`.
- `students` cuenta usuarios distintos con al menos una inscripción activa (no filas de `enrollments`).

### 2. Ruta — `routes/web.php`

Sustituir `Route::inertia('/', 'Welcome')->name('home')` por `Route::get('/', HomeController::class)->name('home')`. Después ejecutar `php artisan wayfinder:generate` (o dejar que el plugin de Vite lo haga) para que `@/routes` siga exportando `home()`.

### 3. Test — `tests/Feature/HomePageTest.php`

`php artisan make:test --pest HomePageTest`. Casos (estilo de `CourseIndexTest.php`, con `assertInertia`):

1. La home responde 200 y renderiza `Welcome` con `featured_courses` y `stats`.
2. Solo aparecen cursos publicados (crear 1 draft + 1 published → `has('featured_courses', 1)`).
3. Se limita a 6 aunque haya 8 publicados, ordenados por `published_at` desc (el primero es el más reciente).
4. `stats` refleja cursos publicados, estudiantes distintos con inscripción activa e instructores (usar `UserFactory::instructor()` / estados existentes y `EnrollmentFactory`).
5. Un usuario autenticado sigue viendo la landing (no redirección).

Usar `Cache::flush()` o el driver `array` (ya configurado en `phpunit.xml`) para que la caché de `stats` no contamine entre tests.

## Frontend

### 4. Tipos — `resources/js/types/`

Añadir en `courses.ts` (o nuevo `landing.ts` reexportado desde `index.ts`):

```ts
export type LandingStats = {
    courses: number;
    students: number;
    instructors: number;
};
```

### 5. Componentes — `resources/js/components/landing/`

| Componente                | Contenido                                                                                                                                                                                 | Props                 |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------- |
| `LandingHeader.vue`       | Logo (`AppLogo`) + nav: `Cursos` (`courses.index`), y `Dashboard` o `Iniciar sesión` / `Registrarse` según `$page.props.auth.user`. Toggle de tema opcional reutilizando `useAppearance`. | —                     |
| `HeroSection.vue`         | Título, subtítulo, botones `Explorar cursos` (→ `courses.index`) y `Crear cuenta` (→ `register`, oculto si hay sesión).                                                                   | —                     |
| `StatsSection.vue`        | 3 contadores con `Intl.NumberFormat('es')`.                                                                                                                                               | `stats: LandingStats` |
| `FeaturesSection.vue`     | 4 tarjetas (`Card`) con íconos `lucide-vue-next`: aprende a tu ritmo, instructores expertos, contenido en video, acceso de por vida. Datos en un array local.                             | —                     |
| `FeaturedCourses.vue`     | Grid responsive (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-3`) reutilizando `components/courses/CourseCard.vue`; enlace "Ver todos los cursos". Estado vacío si no hay cursos.             | `courses: Course[]`   |
| `HowItWorksSection.vue`   | 4 pasos numerados: regístrate → elige un curso → solicita acceso/paga → aprende.                                                                                                          | —                     |
| `TestimonialsSection.vue` | 3 testimonios hardcoded con `Avatar` + `Card`.                                                                                                                                            | —                     |
| `LandingFooter.vue`       | Enlaces (Cursos, Categorías, Login), nombre de la app desde `import.meta.env.VITE_APP_NAME` y año actual.                                                                                 | —                     |

Todas las rutas vía Wayfinder (`@/routes`, `@/routes/courses`, `@/routes/categories`), nunca URLs hardcoded. Los textos en español van directamente en los componentes (la app no usa i18n).

### 6. Página — `resources/js/pages/Welcome.vue`

```vue
<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
// imports de las secciones...
defineProps<{ featured_courses: Course[]; stats: LandingStats }>();
</script>

<template>
    <Head title="Inicio" />
    <div class="min-h-screen bg-background text-foreground">
        <LandingHeader />
        <main>
            <HeroSection />
            <StatsSection :stats="stats" />
            <FeaturesSection />
            <FeaturedCourses :courses="featured_courses" />
            <HowItWorksSection />
            <TestimonialsSection />
        </main>
        <LandingFooter />
    </div>
</template>
```

- Eliminar el `<link>` a `rsms.me/inter` del `<Head>` (la app ya carga su fuente vía `app.css`; verificar antes de quitarlo).
- Ancho de contenido con un contenedor común `mx-auto max-w-6xl px-4 sm:px-6` en cada sección; secciones alternando `bg-background` / `bg-muted/40` para ritmo visual.
- Tokens de color de shadcn (`bg-background`, `text-muted-foreground`, `bg-primary`) en lugar de los hex `#1b1b18` actuales, para que el modo oscuro funcione sin variantes `dark:` manuales.

## Orden de ejecución

1. `HomeController` + ruta + `wayfinder:generate`.
2. `HomePageTest` → `php artisan test --compact tests/Feature/HomePageTest.php` en verde.
3. Tipos + componentes `landing/` + `Welcome.vue`.
4. `vendor/bin/pint --dirty --format agent`, `npm run lint` / `npm run format` si existen, `npm run build` (o `composer run dev`) para verificar que compila.
5. Revisión visual en `/` en claro y oscuro, con y sin sesión, y en móvil (~400px).

## Fuera de alcance (posibles siguientes pasos)

- Testimonios y estadísticas de "horas de contenido" desde la base de datos.
- Campo `is_featured` para curar destacados manualmente.
- Sección de categorías en la landing.
- Unificar idioma (español) en el resto de la aplicación.
