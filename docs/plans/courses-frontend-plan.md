# Plan: páginas Vue del dominio de cursos

## Contexto

El backend del dominio de cursos ya está implementado y verificado (ver `docs/plans/courses-backend-plan.md`): migraciones, modelos, policies, form requests, controladores y 23 tests en verde sobre MySQL. Los controladores devuelven `Inertia::render('courses/Index')`, `courses/Show`, `courses/Create`, `courses/Edit`, `categories/Index`, `categories/Show` y `enrollments/Index`, pero **ninguno de esos componentes Vue existe**, así que las rutas revientan en el navegador y los tests tuvieron que omitir la aserción `->component(...)`.

El objetivo es crear esas páginas siguiendo las convenciones del starter kit, dejando la aplicación navegable de punta a punta: catálogo público, detalle de curso con temario e inscripción, panel del instructor para gestionar cursos/módulos/lecciones, y listado de "mis cursos".

Decisiones tomadas con el usuario:
- **Catálogo**: rejilla de `Card`, no tabla.
- **courses/Edit**: incluye la gestión completa de módulos y lecciones.
- **Permisos**: flags `can` por curso añadidos al payload de los controladores (no se comparte el rol globalmente).
- **Primitivas UI**: no se añade nada a `components/ui/` ni se usa el CLI de shadcn-vue. Se compone solo con lo que ya existe.

## Convenciones del kit que hay que respetar

Verificadas leyendo el código; conviene tenerlas delante al escribir cada página:

- **El layout no se importa en la página.** [app.ts](resources/js/app.ts) lo resuelve por nombre: todo lo que no empiece por `auth/` o `settings/` recibe `AppLayout` automáticamente. La página solo declara props del layout: `defineOptions({ layout: { breadcrumbs: [{ title: 'Courses', href: index() }] } })`.
- **Formularios con `<Form>` de Inertia + Wayfinder, nunca `useForm`** (no se usa en ningún sitio del proyecto). Patrón de [settings/Profile.vue](resources/js/pages/settings/Profile.vue): `<Form v-bind="CourseController.update.form(course)" v-slot="{ errors, processing }">`, campos **no controlados** (`name="title"` + `:default-value`, sin `v-model`), errores con `<InputError :message="errors.title" />`.
- **Confirmaciones destructivas**: copiar la estructura `Dialog` + `<Form>` + `DialogFooter` de [DeleteUser.vue](resources/js/components/DeleteUser.vue).
- **Toasts ya funcionan solos**: `Inertia::flash('toast', ...)` lo consume [lib/flashToast.ts](resources/js/lib/flashToast.ts) y el `<Toaster />` vive en `AppSidebarLayout`. No hay que tocar nada en las páginas.
- **Rutas Wayfinder ya generadas** en `resources/js/routes/{courses,categories,enrollments}/` y `resources/js/actions/App/Http/Controllers/`. Como el binding es `{course:slug}`, los helpers aceptan el modelo directamente: `show(course)` funciona si `course` tiene `slug`. Se pasa el **objeto** de ruta a `:href`, no `.url()`.
- **Textos en inglés literal**: no hay capa de i18n en el frontend.
- **Formato**: 4 espacios, comillas simples, punto y coma, printWidth 80, imports ordenados (externos → `@/actions` → `@/components` → `@/components/ui/*` → `@/routes` → `@/types`). Lo impone `npm run check`.

## Implementación

### 1. Backend: exponer permisos y estado de inscripción

Cambios pequeños y localizados, cada uno con su test:

- `CourseController@show` — añadir al payload `'can' => ['update' => Gate::allows('update', $course), 'delete' => Gate::allows('delete', $course)]` y `'is_enrolled' => (bool) $request->user()?->enrollments()->where('course_id', $course->id)->whereNot('status', EnrollmentStatus::Cancelled)->exists()`. Hay que añadir `Request $request` a la firma.
- `CourseController@index` — añadir `'can_create' => Gate::allows('create', Course::class)`, que es lo que decide si se pinta el botón "New course".
- `CourseController@edit` — añadir `$course->load('modules.lessons')`, que la página necesita para el editor de temario.

**Hueco a cubrir**: `index` solo lista cursos publicados, así que hoy un instructor no tiene forma de llegar a sus borradores ni, por tanto, a `courses/Edit`. Añadir una acción y ruta nuevas para el panel del instructor:

- `app/Http/Controllers/Instructor/CourseController.php` con un único `index(Request $request)` que devuelve `Inertia::render('instructor/Courses', ['courses' => $request->user()->courses()->with('category')->withCount('lessons')->latest()->paginate(12)])`.
- Ruta `GET instructor/courses` → `instructor.courses.index`, dentro del grupo `auth`+`verified` de [routes/courses.php](routes/courses.php).

### 2. Tipos — `resources/js/types/courses.ts`

No existe ningún tipo de dominio ni de paginación. Crear `courses.ts` con `Category`, `Course`, `Module`, `Lesson`, `Enrollment` y un helper `Paginated<T>` (la forma del `LengthAwarePaginator` serializado: `data`, `current_page`, `last_page`, `links[]`, `total`, `prev_page_url`, `next_page_url`), y re-exportarlo desde [types/index.ts](resources/js/types/index.ts) junto a los demás.

Las formas de los modelos están documentadas en los bloques `@property` de [app/Models/Course.php](app/Models/Course.php) y hermanos. Ojo: `price` llega como **string** (cast `decimal:2`) y `status` como string del enum.

### 3. Componentes compartidos — `resources/js/components/`

Ninguno toca `components/ui/`: son composiciones de las primitivas existentes (`Card`, `Button`, `Badge`, `Dialog`, `Collapsible`, `Input`, `Label`, `Separator`), en la línea de `DeleteUser.vue` o `Heading.vue`.

- `Pagination.vue` — `Link`s envueltos en `Button as-child` a partir de `links[]` del paginador. Se usa en tres páginas, así que se extrae en vez de repetirlo; si prefieres inline en cada página, dilo y lo cambio.
- `courses/CourseCard.vue` — tarjeta de curso (título, instructor, categoría, nº de lecciones, precio o "Free"), con prop `showStatus` para pintar un `Badge` de estado en el panel del instructor. La usan `courses/Index`, `categories/Show` e `instructor/Courses`.
- `courses/CourseForm.vue` — campos compartidos por Create y Edit; recibe `course?` y `categories`, y un slot para el botón de envío. El campo `status` solo se pinta cuando hay `course` (Create no lo manda).
- `courses/CurriculumEditor.vue` — el editor de módulos y lecciones de la página Edit.

**Sin primitivas nuevas**, dos consecuencias prácticas:
- `description` y `content` usan un `<textarea>` nativo con las clases Tailwind copiadas de `components/ui/input/Input.vue`, para que se vea igual.
- `category_id` y `status` usan un `<select>` nativo estilizado, no el `Select` de reka-ui: el `<Form>` del kit trabaja con campos no controlados y el `Select` de reka-ui no envía nada de forma nativa (haría falta un input oculto). El `<select>` nativo con `name` y `:selected` encaja con el patrón sin trucos.

### 4. Páginas — `resources/js/pages/`

| Página | Contenido |
|---|---|
| `courses/Index.vue` | Rejilla de `CourseCard` (`sm:grid-cols-2 lg:grid-cols-3`), filtro por categoría con `<select>` nativo que navega a `index.url({ query: { category } })`, botón "New course" si `can_create`, `Pagination`, y un estado vacío (texto centrado + borde discontinuo) cuando no hay cursos. |
| `courses/Show.vue` | Cabecera con título, instructor, categoría, precio y `Badge` de estado si no está publicado. Descripción. Temario: un `Collapsible` por módulo con sus lecciones, duración y `Badge` "Free preview". Botón Enroll (`<Form v-bind="EnrollmentController.store.form(course)">`) o Cancel enrollment según `is_enrolled`; botón Edit si `can.update`. |
| `courses/Create.vue` | `Heading` + `CourseForm` dentro de `<Form v-bind="CourseController.store.form()">`. |
| `courses/Edit.vue` | `CourseForm` en un `<Form ... .update.form(course)>`, luego `CurriculumEditor`, y abajo una zona destructiva con el diálogo de borrar curso al estilo `DeleteUser.vue`. |
| `instructor/Courses.vue` | Rejilla de `CourseCard` con `showStatus`, enlazando a Edit; botón "New course". |
| `categories/Index.vue` | Lista de categorías con su `courses_count`, enlazando a `categories.show`. |
| `categories/Show.vue` | `Heading` con el nombre + rejilla de `CourseCard` + `Pagination`. |
| `enrollments/Index.vue` | Tarjetas de los cursos inscritos con `Badge` de estado, barra de progreso (un `div` con `:style="{ width: progress_percent + '%' }"`, no hace falta primitiva) y acción de cancelar. |

En `CurriculumEditor.vue`: cada módulo es un `Collapsible` con su título, acciones de editar (Dialog + `<Form v-bind="CourseModuleController.update.form({ course, module })">`) y borrar (Dialog de confirmación); dentro, la lista de lecciones con las mismas acciones sobre `ModuleLessonController`, más formularios de "Add lesson" y "Add module". Todas esas rutas redirigen a `courses.edit`, así que el estado se refresca solo.

### 5. Navegación — `AppSidebar.vue`

No hay archivo central de navegación: el menú es el const `mainNavItems` dentro de [AppSidebar.vue](resources/js/components/AppSidebar.vue). Añadir ahí "Courses" (`BookMarked`), "Categories" (`FolderTree`), "My courses" (`GraduationCap`, → `enrollments.index`) y "Teaching" (`PencilRuler`, → `instructor.courses.index`), con iconos de `@lucide/vue`.

Detalle: `NavMain.vue` marca el activo con `isCurrentUrl` (coincidencia exacta), así que `/courses/mi-curso` no dejaría "Courses" resaltado. Cambiar `NavMain.vue` a `isCurrentOrParentUrl` —el mismo helper de `useCurrentUrl` que ya usa [settings/Layout.vue](resources/js/layouts/settings/Layout.vue)— para que el resaltado sea por prefijo.

### 6. Tests

- Restaurar en los tests de feature las aserciones `->component('courses/Index')` etc. que se omitieron porque el archivo Vue no existía (`config('inertia.testing.ensure_pages_exist')` está en `true`, así que ahora sí verifican que la página existe). Afecta a [tests/Feature/CourseIndexTest.php](tests/Feature/CourseIndexTest.php).
- Tests nuevos para lo añadido en el paso 1: que `show` expone `can.update` en `true` para el dueño y `false` para un extraño, que `is_enrolled` refleja la inscripción, y que `instructor.courses.index` lista los borradores propios y no los de otro instructor.

## Verificación

```bash
php artisan test --compact                 # suite completa, incluidas las aserciones de componente
npm run check:fix                          # lint + formato (vite-plus)
npm run types:check                        # vue-tsc --noEmit
npm run build                              # el build debe pasar limpio
composer run dev                           # servidor + vite para revisar en el navegador
```

Con `php artisan migrate:fresh --seed` ya hay datos (4 categorías, 3 instructores, 10 cursos con módulos y lecciones, 10 inscripciones). Recorrer a mano: `/courses` → filtrar por categoría → abrir un curso → inscribirse (toast) → `/my-enrollments` → cancelar; y como instructor: `/instructor/courses` → editar un curso → añadir módulo → añadir lección → borrar lección. Usar `browser-logs` de Boost para comprobar que no hay errores de consola, y `get-absolute-url` para las URLs.
