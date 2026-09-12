<script setup lang="ts">
import {
    Clock,
    Infinity as InfinityIcon,
    PlayCircle,
    Users,
} from "@lucide/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

const features = [
    {
        icon: Clock,
        title: "Aprende a tu ritmo",
        description:
            "Accede al contenido cuando quieras y avanza según tu propio horario.",
    },
    {
        icon: Users,
        title: "Instructores expertos",
        description:
            "Cursos creados por profesionales con experiencia real en la industria.",
    },
    {
        icon: PlayCircle,
        title: "Contenido en video",
        description:
            "Lecciones en video organizadas por módulos, fáciles de seguir.",
    },
    {
        icon: InfinityIcon,
        title: "Acceso de por vida",
        description:
            "Una vez inscrito, el contenido del curso queda disponible sin límite de tiempo.",
    },
];
</script>

<template>
    <section class="bg-background">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <Card v-for="feature in features" :key="feature.title">
                    <CardHeader>
                        <component
                            :is="feature.icon"
                            class="size-8 text-primary"
                        />
                        <CardTitle class="mt-2">{{ feature.title }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            {{ feature.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </section>
</template>
