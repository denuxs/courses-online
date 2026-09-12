<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { register } from "@/routes";
import { index as coursesIndex } from "@/routes/courses";

const page = usePage();
</script>

<template>
    <section class="bg-background">
        <div class="mx-auto max-w-6xl px-4 py-20 text-center sm:px-6 sm:py-28">
            <h1
                class="text-4xl font-bold tracking-tight text-foreground sm:text-5xl"
            >
                Aprende a tu ritmo, con instructores expertos
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-muted-foreground">
                Cursos en video sobre desarrollo, diseño y negocios. Regístrate,
                elige un curso y empieza a aprender hoy mismo.
            </p>
            <div class="mt-8 flex items-center justify-center gap-4">
                <Link :href="coursesIndex()">
                    <Button size="lg">Explorar cursos</Button>
                </Link>
                <Link v-if="!page.props.auth.user" :href="register()">
                    <Button size="lg" variant="outline">Crear cuenta</Button>
                </Link>
            </div>
        </div>
    </section>
</template>
