# Courses Online

Plataforma de cursos online: los instructores publican cursos organizados en módulos y
lecciones, y los estudiantes se inscriben en ellos.

## Stack

| Capa              | Tecnología                                                                    |
| ----------------- | ----------------------------------------------------------------------------- |
| Backend           | Laravel 13, PHP 8.4 (requiere `^8.3`)                                         |
| Frontend          | Inertia v3 + Vue 3, TypeScript, Tailwind v4, shadcn-vue (reka-ui)             |
| Auth              | Laravel Fortify (login, registro, reset de contraseña, verificación de email) |
| Rutas tipadas     | Laravel Wayfinder                                                             |
| Build             | Vite 8 vía [vite-plus](https://viteplus.dev) (binario `vp`)                   |
| Tests             | Pest 5                                                                        |
| Análisis estático | Larastan (PHPStan), Pint, `vue-tsc`                                           |

Está construido sobre el [starter kit oficial de Laravel + Vue](https://github.com/laravel/vue-starter-kit).

## Requisitos

- PHP 8.3+ con las extensiones habituales de Laravel (`pdo`, `mbstring`, `intl`, `gd`, `zip`)
- Composer 2
- Node 22+ y npm
- MySQL 8 (o MariaDB) para desarrollo local

## Puesta en marcha

```bash
git clone git@github.com:denuxs/courses-online.git
cd courses-online

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configura la conexión a base de datos en `.env`. **`.env.example` viene con `sqlite` por ser
el valor por defecto del starter kit, pero el proyecto se desarrolla sobre MySQL** (las
migraciones usan columnas `ENUM` nativas):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=courses
DB_USERNAME=root
DB_PASSWORD=
```

Crea la base de datos, migra y siembra datos de prueba:

```bash
mysql -u root -p -e 'CREATE DATABASE courses CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
php artisan migrate --seed
```

El seeder crea 4 categorías, 3 instructores, 10 estudiantes, un admin
(`admin@example.com`) y 10 cursos con sus módulos y lecciones. Además siembra el flujo de
pagos: la mayoría de estudiantes recibe un pago confirmado (con su inscripción activa y algo
de progreso en `lesson_progress`), 4 quedan con un pago `pending` y 2 con uno `rejected`.
Todos los usuarios generados usan la contraseña `password`.

Levanta el entorno de desarrollo (servidor, cola, logs y Vite a la vez):

```bash
composer run dev
```

## Comandos habituales

```bash
composer run dev          # servidor + queue + logs + vite
composer run test         # config:clear + Pint + PHPStan + Pest
composer run lint         # Pint (formato PHP)
composer run types:check  # PHPStan / Larastan
composer run ci:check     # lo anterior + checks de frontend

php artisan test --compact                       # toda la suite
php artisan test --filter=CourseIndexTest        # un test concreto

npm run dev          # solo Vite
npm run build        # build de producción
npm run check        # lint + formato del frontend
npm run check:fix    # corrige lint y formato
npm run types:check  # vue-tsc --noEmit
```

### Tests

La suite **no usa SQLite en memoria**: `phpunit.xml` apunta a una base MySQL dedicada, que
debe existir antes de correr los tests.

```bash
mysql -u root -p -e 'CREATE DATABASE courses_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
```

## Modelo de dominio

```
User ─┬─< Course >── Category
      │      │
      │      └──< Module ──< Lesson
      │
      ├──< Payment >───── Course
      │
      └──< Enrollment >── Course
             │
             └──< LessonProgress >── Lesson
```

- **User** tiene un `role`: `student`, `instructor` o `admin` (enum `App\Enums\UserRole`).
- **Course** pertenece a un instructor y opcionalmente a una categoría. Su `status` es
  `draft`, `published` o `archived`; solo los publicados aparecen en el catálogo público.
  Se resuelve por `slug` en las rutas.
- **Module** agrupa lecciones dentro de un curso, ordenadas por `position`.
- **Lesson** puede marcarse como `is_free_preview` para mostrarse sin inscripción.
- **Payment** es la solicitud de acceso de un estudiante a un curso: `status`
  (`pending`/`confirmed`/`rejected`) y `method` (`cash`/`bank_transfer`/`other`). Incluye los
  cursos gratuitos (`amount = 0`); el flujo es el mismo para todos.
- **Enrollment** une usuario y curso (único por pareja), con `status`
  (`active`/`completed`/`cancelled`) y un `progress_percent` desnormalizado. Solo se crea (o
  reactiva) cuando un admin confirma el `Payment` correspondiente.
- **LessonProgress** registra, por inscripción y lección, `completed_at` y `seconds_watched`
  (único por pareja). Hoy solo lo escribe el seeder; ningún controlador lo actualiza todavía.

Borrar un curso arrastra en cascada sus módulos, lecciones, pagos e inscripciones.

### Flujo de pagos e inscripción

El estudiante nunca se inscribe directamente: **solicita acceso** al curso, y el admin
decide si otorgarlo.

1. `POST /courses/{slug}/payments` (`PaymentController@store`) crea un `Payment` en estado
   `pending`, siempre que el estudiante no esté ya inscrito (con estado distinto de
   `cancelled`) ni tenga otra solicitud pendiente para ese mismo curso.
2. `GET /admin/payments` (`Admin\PaymentController@index`) lista los pagos `pending`, solo
   para admins (`PaymentPolicy`).
3. `PATCH /admin/payments/{payment}` los confirma o rechaza. Al confirmar, crea o reactiva
   (`updateOrCreate`) el `Enrollment` como `active`, dentro de una transacción junto con el
   propio `Payment`.
4. El estudiante cancela su acceso desde `DELETE /courses/{slug}/enroll`
   (`EnrollmentController@destroy`), que solo marca el `Enrollment` como `cancelled` — no
   toca el `Payment` histórico, así que puede volver a solicitar acceso más adelante.

El esquema original del que parte todo esto está en [`database.sql`](database.sql), y las
decisiones de diseño (y dónde nos desviamos de ese SQL) están documentadas en
[`docs/plans/`](docs/plans/).

### Autorización

Las policies (`CoursePolicy`, `ModulePolicy`, `LessonPolicy`) aplican una regla simple: solo
el instructor propietario del curso —o un admin— puede editarlo, junto con sus módulos y
lecciones. Los cursos en borrador solo son visibles para su propietario. `PaymentPolicy`
restringe `admin/payments` a usuarios con `role = admin`; no hay middleware de rol en las
rutas, la autorización vive en gates dentro de los controladores.

El frontend no conoce roles: los controladores envían flags por recurso
(`can.update`, `can.delete`, `is_enrolled`, `can_create`) en el payload de Inertia.

## Rutas

| Ruta                                                | Descripción                                            | Acceso      |
| --------------------------------------------------- | ------------------------------------------------------ | ----------- |
| `GET /`                                             | Landing con cursos destacados y estadísticas           | Público     |
| `GET /courses`                                      | Catálogo de cursos publicados, filtrable por categoría | Público     |
| `GET /courses/{slug}`                               | Detalle del curso con su temario                       | Público     |
| `GET /categories`, `GET /categories/{slug}`         | Categorías y sus cursos                                | Público     |
| `GET /courses/create`, `GET /courses/{slug}/edit`   | Alta y edición de curso                                | Instructor  |
| `POST/PUT/DELETE /courses/{slug}/modules/...`       | Gestión de módulos y lecciones                         | Propietario |
| `GET /instructor/courses`                           | Cursos propios, incluidos borradores                   | Instructor  |
| `GET /my-enrollments`                               | Cursos en los que estás inscrito                       | Autenticado |
| `POST /courses/{slug}/payments`                     | Solicitar acceso a un curso (crea un `Payment`)        | Autenticado |
| `DELETE /courses/{slug}/enroll`                     | Cancelar la inscripción activa                         | Autenticado |
| `GET /admin/payments`, `PATCH /admin/payments/{id}` | Revisar y confirmar/rechazar solicitudes de acceso     | Admin       |

Listado completo con `php artisan route:list --except-vendor`.

## Estructura del frontend

```
resources/js/
├── pages/            # páginas Inertia (courses/, categories/, enrollments/, instructor/, admin/)
├── components/       # componentes de aplicación
│   ├── courses/      # CourseCard, CourseForm, CurriculumEditor
│   ├── landing/      # secciones de la home pública (Hero, Stats, FeaturedCourses...)
│   └── ui/           # primitivas shadcn-vue (no editar a mano)
├── layouts/          # AppLayout, AuthLayout, settings/Layout
├── types/            # tipos compartidos, incluido el dominio en courses.ts
├── actions/ routes/  # generados por Wayfinder (git-ignored)
└── app.ts            # arranque de Inertia y resolución de layouts
```

### Convenciones

Conviene conocerlas antes de tocar el frontend, porque no son las de una app Vue cualquiera:

- **Los layouts no se importan en las páginas.** `app.ts` resuelve el layout a partir del
  nombre de la página. Una página solo declara _props_ del layout:
  `defineOptions({ layout: { breadcrumbs: [...] } })`, o una función que recibe las props de
  la página cuando el breadcrumb es dinámico.
- **Formularios con `<Form>` de Inertia + Wayfinder, nunca `useForm`.** Los campos son no
  controlados (`name` + `:default-value`, sin `v-model`) y los errores llegan por el slot:
  `v-slot="{ errors, processing }"`.
- **Los toasts ya están conectados.** `Inertia::flash('toast', [...])` desde el backend se
  renderiza solo; no hay que hacer nada en la página.
- Las cadenas de texto van en inglés literal: no hay capa de i18n en el frontend.

## Puntos que suelen dar problemas

- **Wayfinder y `.form()`**: `php artisan wayfinder:generate` **no** genera los helpers
  `.form()`; hay que pasar `--with-form`. El plugin de Vite sí los genera automáticamente en
  `npm run dev` y `npm run build`, así que normalmente no hace falta lanzarlo a mano.
- **Orden de rutas**: `routes/courses.php` registra las rutas estáticas (`courses/create`)
  _antes_ que las dinámicas (`courses/{course:slug}`). Si se invierte, `create` se interpreta
  como un slug. El archivo lo advierte con un comentario.
- **Páginas nuevas y el manifest de Vite**: `app.blade.php` hace
  `@vite("resources/js/pages/{$page['component']}.vue")`, así que al crear una página hay que
  ejecutar `npm run build` (o tener Vite corriendo) o los tests fallarán con
  `Unable to locate file in Vite manifest`.
- **`components/ui/` está excluido del lint y del formato**; son archivos generados por
  shadcn-vue y se dejan tal cual.
- **2FA viene esbozado pero no está habilitado**: el modelo `User` documenta campos
  `two_factor_*` que ninguna migración crea, y `UserFactory::withTwoFactor()` tiene el cuerpo
  vacío (PHPStan lo reporta). Viene así del starter kit; habilitarlo requiere activar la
  feature en `config/fortify.php` y publicar su migración.

## Docker

El [`Dockerfile`](Dockerfile) produce una imagen de producción en dos etapas: una que instala
dependencias y compila los assets (necesita PHP _y_ Node, porque el plugin de Wayfinder
ejecuta `php artisan` durante el build de Vite), y la imagen final con Apache + PHP.

```bash
docker build -t courses-online .

docker run -p 8080:80 \
  -e APP_KEY="base64:$(openssl rand -base64 32)" \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=... -e DB_DATABASE=courses \
  -e DB_USERNAME=... -e DB_PASSWORD=... \
  courses-online
```

Dos cosas a tener en cuenta:

- **La imagen está compilada contra PostgreSQL** (`pdo_pgsql`), mientras que el desarrollo
  local usa MySQL. Si despliegas contra MySQL, cambia las extensiones del `Dockerfile`.
- La imagen se instala con `--no-dev`, así que **los seeders no funcionan dentro del
  contenedor**: dependen de Faker, que es una dependencia de desarrollo.

Las cachés de configuración, rutas y vistas se generan al arrancar el contenedor, no al
construir la imagen, porque dependen de variables de entorno de ejecución.
