<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import CourseCard from "@/components/courses/CourseCard.vue";
import { Button } from "@/components/ui/button";
import { index as coursesIndex } from "@/routes/courses";
import type { Course } from "@/types";

defineProps<{
    courses: Course[];
}>();
</script>

<template>
    <section class="bg-muted/40">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-2xl font-bold text-foreground sm:text-3xl">
                    Cursos destacados
                </h2>
                <Link :href="coursesIndex()">
                    <Button variant="outline">Ver todos</Button>
                </Link>
            </div>

            <div
                v-if="courses.length > 0"
                class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <CourseCard
                    v-for="course in courses"
                    :key="course.id"
                    :course="course"
                />
            </div>
            <p v-else class="mt-8 text-muted-foreground">
                Todavía no hay cursos publicados.
            </p>
        </div>
    </section>
</template>
