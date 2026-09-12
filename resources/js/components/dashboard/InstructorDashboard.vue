<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { BookOpen, FileText, Users } from "@lucide/vue";
import StatCard from "@/components/dashboard/StatCard.vue";
import Heading from "@/components/Heading.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { create, show } from "@/routes/courses";
import { index as instructorCoursesIndex } from "@/routes/instructor/courses";
import type { InstructorDashboardProps } from "@/types";

defineProps<
    Pick<InstructorDashboardProps, "stats" | "courses" | "recent_enrollments">
>();

const statusVariant: Record<string, "default" | "secondary" | "outline"> = {
    draft: "outline",
    published: "default",
    archived: "secondary",
};

const dateFormatter = new Intl.DateTimeFormat("es", { dateStyle: "medium" });

function formatDate(value: string): string {
    return dateFormatter.format(new Date(value));
}
</script>

<template>
    <div class="space-y-8">
        <div class="grid gap-4 sm:grid-cols-3">
            <StatCard
                label="Cursos publicados"
                :value="stats.published_courses"
                :icon="BookOpen"
                :href="instructorCoursesIndex()"
            />
            <StatCard
                label="Borradores"
                :value="stats.draft_courses"
                :icon="FileText"
                :href="instructorCoursesIndex()"
            />
            <StatCard label="Alumnos" :value="stats.students" :icon="Users" />
        </div>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <Heading title="Mis cursos recientes" variant="small" />
                <Link :href="create()">
                    <Button size="sm">Nuevo curso</Button>
                </Link>
            </div>

            <div v-if="courses.length > 0" class="space-y-3">
                <Card v-for="course in courses" :key="course.id">
                    <CardContent
                        class="flex items-center justify-between gap-4 pt-6"
                    >
                        <div class="min-w-0">
                            <Link
                                :href="show(course)"
                                class="font-medium hover:underline"
                            >
                                {{ course.title }}
                            </Link>
                            <p class="text-sm text-muted-foreground">
                                {{ course.lessons_count ?? 0 }} lecciones ·
                                {{ course.enrollments_count ?? 0 }} inscritos
                            </p>
                        </div>
                        <Badge
                            :variant="statusVariant[course.status]"
                            class="capitalize"
                        >
                            {{ course.status }}
                        </Badge>
                    </CardContent>
                </Card>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                Todavía no has creado ningún curso.
            </p>
        </section>

        <section class="space-y-4">
            <Heading title="Inscripciones recientes" variant="small" />

            <div v-if="recent_enrollments.length > 0" class="space-y-3">
                <Card
                    v-for="enrollment in recent_enrollments"
                    :key="enrollment.id"
                >
                    <CardContent
                        class="flex items-center justify-between gap-4 pt-6"
                    >
                        <div class="min-w-0">
                            <p class="font-medium">
                                {{ enrollment.user?.name }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ enrollment.course?.title }}
                            </p>
                        </div>
                        <span class="text-sm text-muted-foreground">
                            {{ formatDate(enrollment.enrolled_at) }}
                        </span>
                    </CardContent>
                </Card>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                Aún no hay alumnos inscritos en tus cursos.
            </p>
        </section>
    </div>
</template>
