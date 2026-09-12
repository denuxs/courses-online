<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import CourseController from '@/actions/App/Http/Controllers/CourseController';
import CourseForm from '@/components/courses/CourseForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/courses';
import type { Category } from '@/types';

type Props = {
    categories: Category[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Courses', href: index() },
            { title: 'New course', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New course" />

    <div class="mx-auto max-w-2xl space-y-6">
        <Heading
            title="New course"
            description="Set up the basics — you can add modules and lessons afterwards"
        />

        <Form
            v-bind="CourseController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <CourseForm :categories="categories" :errors="errors" />

            <Button type="submit" :disabled="processing">
                Create course
            </Button>
        </Form>
    </div>
</template>
