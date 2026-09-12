<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import CourseController from '@/actions/App/Http/Controllers/CourseController';
import CourseForm from '@/components/courses/CourseForm.vue';
import CurriculumEditor from '@/components/courses/CurriculumEditor.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { edit, index, show } from '@/routes/courses';
import type { Category, Course } from '@/types';

type Props = {
    course: Course;
    categories: Category[];
};

defineProps<Props>();

defineOptions({
    layout: (pageProps: Props) => ({
        breadcrumbs: [
            { title: 'Courses', href: index() },
            { title: pageProps.course.title, href: show(pageProps.course) },
            { title: 'Edit', href: edit(pageProps.course) },
        ],
    }),
});
</script>

<template>
    <Head :title="`Edit ${course.title}`" />

    <div class="mx-auto max-w-2xl space-y-10">
        <div class="space-y-6">
            <Heading title="Course details" variant="small" />

            <Form
                v-bind="CourseController.update.form(course)"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <CourseForm
                    :course="course"
                    :categories="categories"
                    :errors="errors"
                />

                <Button type="submit" :disabled="processing">
                    Save changes
                </Button>
            </Form>
        </div>

        <div class="space-y-4">
            <Heading title="Curriculum" variant="small" />
            <CurriculumEditor :course="course" />
        </div>

        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="text-red-600 dark:text-red-100">
                <p class="font-medium">Delete this course</p>
                <p class="text-sm">
                    This permanently removes the course, its modules and
                    lessons. This cannot be undone.
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive">Delete course</Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="CourseController.destroy.form(course)"
                        v-slot="{ processing }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>
                                Are you sure you want to delete "{{
                                    course.title
                                }}"?
                            </DialogTitle>
                        </DialogHeader>
                        <DialogFooter class="mt-4 gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary">Cancel</Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                            >
                                Delete course
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
