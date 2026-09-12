<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import AppLogo from "@/components/AppLogo.vue";
import { Button } from "@/components/ui/button";
import { dashboard, login, register } from "@/routes";
import { index as coursesIndex } from "@/routes/courses";

const page = usePage();
</script>

<template>
    <header class="border-b bg-background">
        <div
            class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6"
        >
            <Link :href="'/'" class="flex items-center gap-2">
                <AppLogo />
            </Link>

            <nav class="flex items-center gap-4">
                <Link
                    :href="coursesIndex()"
                    class="hidden text-sm font-medium text-muted-foreground hover:text-foreground sm:inline-block"
                >
                    Cursos
                </Link>

                <Link v-if="page.props.auth.user" :href="dashboard()">
                    <Button variant="outline" size="sm">Dashboard</Button>
                </Link>
                <template v-else>
                    <Link :href="login()">
                        <Button variant="ghost" size="sm"
                            >Iniciar sesión</Button
                        >
                    </Link>
                    <Link :href="register()">
                        <Button size="sm">Registrarse</Button>
                    </Link>
                </template>
            </nav>
        </div>
    </header>
</template>
