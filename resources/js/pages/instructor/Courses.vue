<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CourseCard from '@/components/courses/CourseCard.vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { create } from '@/routes/courses';
import { index } from '@/routes/instructor/courses';
import type { Course, Paginated } from '@/types';

type Props = {
    courses: Paginated<Course>;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My courses', href: index() }],
    },
});
</script>

<template>
    <Head title="My courses" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                title="My courses"
                description="Manage the courses you teach"
            />
            <Button as-child>
                <Link :href="create()">New course</Link>
            </Button>
        </div>

        <div
            v-if="courses.data.length > 0"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <CourseCard
                v-for="course in courses.data"
                :key="course.id"
                :course="course"
                show-status
            />
        </div>
        <div
            v-else
            class="text-muted-foreground rounded-lg border border-dashed p-12 text-center text-sm"
        >
            You haven't created any courses yet.
        </div>

        <Pagination :paginator="courses" />
    </div>
</template>
