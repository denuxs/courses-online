<script setup lang="ts">
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Card, CardContent } from "@/components/ui/card";

const testimonials = [
    {
        name: "María González",
        role: "Estudiante de Desarrollo Web",
        quote: "Pude aprender a mi ritmo mientras trabajaba. Los instructores explican todo de forma muy clara.",
    },
    {
        name: "Carlos Ramírez",
        role: "Estudiante de Diseño",
        quote: "La calidad de los cursos es excelente y el acceso de por vida me permitió repasar cuando lo necesité.",
    },
    {
        name: "Lucía Fernández",
        role: "Estudiante de Negocios",
        quote: "El proceso de inscripción fue muy sencillo y el soporte respondió rápido a mis dudas.",
    },
];

function initials(name: string): string {
    return name
        .split(" ")
        .map((part) => part[0])
        .join("")
        .slice(0, 2)
        .toUpperCase();
}
</script>

<template>
    <section class="bg-muted/40">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2
                class="text-center text-2xl font-bold text-foreground sm:text-3xl"
            >
                Lo que dicen nuestros estudiantes
            </h2>

            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
                <Card
                    v-for="testimonial in testimonials"
                    :key="testimonial.name"
                >
                    <CardContent class="pt-6">
                        <p class="text-sm text-muted-foreground">
                            &ldquo;{{ testimonial.quote }}&rdquo;
                        </p>
                        <div class="mt-4 flex items-center gap-3">
                            <Avatar>
                                <AvatarFallback>
                                    {{ initials(testimonial.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <p class="text-sm font-medium text-foreground">
                                    {{ testimonial.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ testimonial.role }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </section>
</template>
