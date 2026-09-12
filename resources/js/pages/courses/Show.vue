<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Lock, Play } from '@lucide/vue';
import EnrollmentController from '@/actions/App/Http/Controllers/EnrollmentController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { edit, index, show } from '@/routes/courses';
import type { Course } from '@/types';

type Props = {
    course: Course;
    can: {
        update: boolean;
        delete: boolean;
    };
    is_enrolled: boolean;
};

defineProps<Props>();

defineOptions({
    layout: (props: Props) => ({
        breadcrumbs: [
            { title: 'Courses', href: index() },
            { title: props.course.title, href: show(props.course) },
        ],
    }),
});
</script>

<template>
    <Head :title="course.title" />

    <div class="mx-auto max-w-3xl space-y-8">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <Badge v-if="course.category" variant="secondary">
                    {{ course.category.name }}
                </Badge>
                <Badge
                    v-if="course.status !== 'published'"
                    variant="outline"
                    class="capitalize"
                >
                    {{ course.status }}
                </Badge>
            </div>

            <div class="flex flex-wrap items-start justify-between gap-4">
                <Heading :title="course.title" variant="small" />
                <Button v-if="can.update" as-child variant="outline">
                    <Link :href="edit(course)">Edit course</Link>
                </Button>
            </div>

            <p v-if="course.instructor" class="text-muted-foreground text-sm">
                By {{ course.instructor.name }}
            </p>

            <p v-if="course.description" class="text-sm leading-relaxed">
                {{ course.description }}
            </p>

            <div class="flex items-center gap-4">
                <span class="text-lg font-semibold">
                    {{ Number(course.price) > 0 ? `$${course.price}` : 'Free' }}
                </span>

                <Form
                    v-if="!is_enrolled"
                    v-bind="EnrollmentController.store.form(course)"
                    v-slot="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        Enroll
                    </Button>
                </Form>
                <Form
                    v-else
                    v-bind="EnrollmentController.destroy.form(course)"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                    >
                        Cancel enrollment
                    </Button>
                </Form>
            </div>
        </div>

        <div class="space-y-2">
            <h2 class="text-lg font-semibold">Curriculum</h2>

            <Collapsible
                v-for="module in course.modules"
                :key="module.id"
                class="rounded-lg border"
            >
                <CollapsibleTrigger class="w-full p-4 text-left font-medium">
                    {{ module.title }}
                    <span class="text-muted-foreground text-xs font-normal">
                        {{ module.lessons?.length ?? 0 }} lessons
                    </span>
                </CollapsibleTrigger>
                <CollapsibleContent class="space-y-1 border-t p-4">
                    <div
                        v-for="lesson in module.lessons"
                        :key="lesson.id"
                        class="flex items-center justify-between gap-2 py-1 text-sm"
                    >
                        <span class="flex items-center gap-2">
                            <Play
                                v-if="lesson.is_free_preview || is_enrolled"
                                class="text-muted-foreground size-4"
                            />
                            <Lock v-else class="text-muted-foreground size-4" />
                            {{ lesson.title }}
                        </span>
                        <span class="flex items-center gap-2">
                            <Badge
                                v-if="lesson.is_free_preview"
                                variant="outline"
                            >
                                Free preview
                            </Badge>
                            <span
                                v-if="lesson.duration_minutes"
                                class="text-muted-foreground text-xs"
                            >
                                {{ lesson.duration_minutes }} min
                            </span>
                        </span>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    </div>
</template>
