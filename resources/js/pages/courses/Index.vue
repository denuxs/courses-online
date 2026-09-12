<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import CourseCard from '@/components/courses/CourseCard.vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/courses';
import type { Category, Course, Paginated } from '@/types';

type Props = {
    courses: Paginated<Course>;
    categories: Category[];
    filters: {
        category?: string;
    };
    can_create: boolean;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Courses', href: index() }],
    },
});

function onCategoryChange(event: Event) {
    const category = (event.target as HTMLSelectElement).value;

    router.get(
        index.url({ query: category ? { category } : {} }),
        {},
        { preserveState: true },
    );
}
</script>

<template>
    <Head title="Courses" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                title="Courses"
                description="Browse published courses from our instructors"
            />
            <Button v-if="can_create" as-child>
                <Link :href="create()">New course</Link>
            </Button>
        </div>

        <select
            :value="filters.category ?? ''"
            class="border-input dark:bg-input/30 h-9 rounded-md border bg-transparent px-3 text-sm shadow-xs"
            @change="onCategoryChange"
        >
            <option value="">All categories</option>
            <option
                v-for="category in categories"
                :key="category.id"
                :value="category.slug"
            >
                {{ category.name }}
            </option>
        </select>

        <div
            v-if="courses.data.length > 0"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <CourseCard
                v-for="course in courses.data"
                :key="course.id"
                :course="course"
            />
        </div>
        <div
            v-else
            class="text-muted-foreground rounded-lg border border-dashed p-12 text-center text-sm"
        >
            No courses found.
        </div>

        <Pagination :paginator="courses" />
    </div>
</template>
