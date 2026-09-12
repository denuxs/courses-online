<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { login } from "@/routes";
import { index as categoriesIndex } from "@/routes/categories";
import { index as coursesIndex } from "@/routes/courses";

const appName = import.meta.env.VITE_APP_NAME || "Cursos";
const year = new Date().getFullYear();
</script>

<template>
    <footer class="border-t bg-background">
        <div
            class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-muted-foreground sm:flex-row sm:px-6"
        >
            <p>
                &copy; {{ year }} {{ appName }}. Todos los derechos reservados.
            </p>

            <nav class="flex items-center gap-6">
                <Link :href="coursesIndex()" class="hover:text-foreground">
                    Cursos
                </Link>
                <Link :href="categoriesIndex()" class="hover:text-foreground">
                    Categorías
                </Link>
                <Link :href="login()" class="hover:text-foreground">
                    Iniciar sesión
                </Link>
            </nav>
        </div>
    </footer>
</template>
