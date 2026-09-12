<script setup lang="ts">
const steps = [
    {
        number: "1",
        title: "Regístrate",
        description: "Crea tu cuenta gratis en menos de un minuto.",
    },
    {
        number: "2",
        title: "Elige un curso",
        description:
            "Explora el catálogo y encuentra el curso que te interesa.",
    },
    {
        number: "3",
        title: "Solicita acceso",
        description:
            "Completa el pago o solicita el curso; el equipo confirma tu inscripción.",
    },
    {
        number: "4",
        title: "Aprende",
        description: "Accede a las lecciones en video a tu propio ritmo.",
    },
];
</script>

<template>
    <section class="bg-background">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2
                class="text-center text-2xl font-bold text-foreground sm:text-3xl"
            >
                Cómo funciona
            </h2>

            <div
                class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div
                    v-for="step in steps"
                    :key="step.number"
                    class="text-center"
                >
                    <div
                        class="mx-auto flex size-10 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground"
                    >
                        {{ step.number }}
                    </div>
                    <h3 class="mt-4 font-semibold text-foreground">
                        {{ step.title }}
                    </h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ step.description }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
