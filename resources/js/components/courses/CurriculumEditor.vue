<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ChevronDown, Pencil, Trash } from '@lucide/vue';
import CourseModuleController from '@/actions/App/Http/Controllers/Instructor/CourseModuleController';
import ModuleLessonController from '@/actions/App/Http/Controllers/Instructor/ModuleLessonController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Course } from '@/types';

type Props = {
    course: Course;
};

defineProps<Props>();
</script>

<template>
    <div class="space-y-4">
        <Collapsible
            v-for="module in course.modules"
            :key="module.id"
            class="rounded-lg border"
            default-open
        >
            <div class="flex items-center justify-between gap-2 p-4">
                <CollapsibleTrigger
                    class="flex flex-1 items-center gap-2 text-left font-medium"
                >
                    <ChevronDown class="size-4" />
                    {{ module.title }}
                    <span class="text-muted-foreground text-xs font-normal">
                        {{ module.lessons?.length ?? 0 }} lessons
                    </span>
                </CollapsibleTrigger>

                <div class="flex items-center gap-1">
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="ghost" size="icon-sm">
                                <Pencil class="size-4" />
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                v-bind="
                                    CourseModuleController.update.form({
                                        course,
                                        module,
                                    })
                                "
                                class="space-y-4"
                                v-slot="{ errors, processing }"
                            >
                                <DialogHeader>
                                    <DialogTitle>Edit module</DialogTitle>
                                </DialogHeader>
                                <div class="grid gap-2">
                                    <Label :for="`module-title-${module.id}`"
                                        >Title</Label
                                    >
                                    <Input
                                        :id="`module-title-${module.id}`"
                                        name="title"
                                        :default-value="module.title"
                                        required
                                    />
                                    <InputError :message="errors.title" />
                                </div>
                                <DialogFooter>
                                    <DialogClose as-child>
                                        <Button variant="secondary"
                                            >Cancel</Button
                                        >
                                    </DialogClose>
                                    <Button type="submit" :disabled="processing"
                                        >Save</Button
                                    >
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>

                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="ghost" size="icon-sm">
                                <Trash class="size-4" />
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                v-bind="
                                    CourseModuleController.destroy.form({
                                        course,
                                        module,
                                    })
                                "
                                class="space-y-4"
                                v-slot="{ processing }"
                            >
                                <DialogHeader>
                                    <DialogTitle
                                        >Delete "{{
                                            module.title
                                        }}"?</DialogTitle
                                    >
                                </DialogHeader>
                                <p class="text-muted-foreground text-sm">
                                    This also deletes every lesson inside this
                                    module. This cannot be undone.
                                </p>
                                <DialogFooter>
                                    <DialogClose as-child>
                                        <Button variant="secondary"
                                            >Cancel</Button
                                        >
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        :disabled="processing"
                                    >
                                        Delete module
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <CollapsibleContent class="space-y-2 border-t p-4">
                <div
                    v-for="lesson in module.lessons"
                    :key="lesson.id"
                    class="flex items-center justify-between gap-2 rounded-md border p-3"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-sm">{{ lesson.title }}</span>
                        <Badge v-if="lesson.is_free_preview" variant="outline">
                            Free preview
                        </Badge>
                        <span
                            v-if="lesson.duration_minutes"
                            class="text-muted-foreground text-xs"
                        >
                            {{ lesson.duration_minutes }} min
                        </span>
                    </div>

                    <div class="flex items-center gap-1">
                        <Dialog>
                            <DialogTrigger as-child>
                                <Button variant="ghost" size="icon-sm">
                                    <Pencil class="size-4" />
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    v-bind="
                                        ModuleLessonController.update.form({
                                            course,
                                            module,
                                            lesson,
                                        })
                                    "
                                    class="space-y-4"
                                    v-slot="{ errors, processing }"
                                >
                                    <DialogHeader>
                                        <DialogTitle>Edit lesson</DialogTitle>
                                    </DialogHeader>
                                    <div class="grid gap-2">
                                        <Label
                                            :for="`lesson-title-${lesson.id}`"
                                            >Title</Label
                                        >
                                        <Input
                                            :id="`lesson-title-${lesson.id}`"
                                            name="title"
                                            :default-value="lesson.title"
                                            required
                                        />
                                        <InputError :message="errors.title" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label
                                            :for="`lesson-duration-${lesson.id}`"
                                            >Duration (minutes)</Label
                                        >
                                        <Input
                                            :id="`lesson-duration-${lesson.id}`"
                                            name="duration_minutes"
                                            type="number"
                                            min="0"
                                            :default-value="
                                                lesson.duration_minutes ?? ''
                                            "
                                        />
                                        <InputError
                                            :message="errors.duration_minutes"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label
                                            :for="`lesson-video-${lesson.id}`"
                                            >Video URL</Label
                                        >
                                        <Input
                                            :id="`lesson-video-${lesson.id}`"
                                            name="video_url"
                                            :default-value="
                                                lesson.video_url ?? ''
                                            "
                                        />
                                        <InputError
                                            :message="errors.video_url"
                                        />
                                    </div>
                                    <label
                                        class="flex items-center gap-2 text-sm"
                                    >
                                        <input
                                            type="checkbox"
                                            name="is_free_preview"
                                            value="1"
                                            :checked="lesson.is_free_preview"
                                        />
                                        Free preview
                                    </label>
                                    <DialogFooter>
                                        <DialogClose as-child>
                                            <Button variant="secondary"
                                                >Cancel</Button
                                            >
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            :disabled="processing"
                                            >Save</Button
                                        >
                                    </DialogFooter>
                                </Form>
                            </DialogContent>
                        </Dialog>

                        <Dialog>
                            <DialogTrigger as-child>
                                <Button variant="ghost" size="icon-sm">
                                    <Trash class="size-4" />
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    v-bind="
                                        ModuleLessonController.destroy.form({
                                            course,
                                            module,
                                            lesson,
                                        })
                                    "
                                    class="space-y-4"
                                    v-slot="{ processing }"
                                >
                                    <DialogHeader>
                                        <DialogTitle
                                            >Delete "{{
                                                lesson.title
                                            }}"?</DialogTitle
                                        >
                                    </DialogHeader>
                                    <DialogFooter>
                                        <DialogClose as-child>
                                            <Button variant="secondary"
                                                >Cancel</Button
                                            >
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            :disabled="processing"
                                        >
                                            Delete lesson
                                        </Button>
                                    </DialogFooter>
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <Form
                    v-bind="
                        ModuleLessonController.store.form({ course, module })
                    "
                    reset-on-success
                    class="flex items-end gap-2 pt-2"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid flex-1 gap-2">
                        <Label :for="`new-lesson-${module.id}`"
                            >Add lesson</Label
                        >
                        <Input
                            :id="`new-lesson-${module.id}`"
                            name="title"
                            placeholder="Lesson title"
                            required
                        />
                        <InputError :message="errors.title" />
                    </div>
                    <Button type="submit" :disabled="processing">Add</Button>
                </Form>
            </CollapsibleContent>
        </Collapsible>

        <Form
            v-bind="CourseModuleController.store.form(course)"
            reset-on-success
            class="flex items-end gap-2 rounded-lg border border-dashed p-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid flex-1 gap-2">
                <Label for="new-module">Add module</Label>
                <Input
                    id="new-module"
                    name="title"
                    placeholder="Module title"
                    required
                />
                <InputError :message="errors.title" />
            </div>
            <Button type="submit" :disabled="processing">Add</Button>
        </Form>
    </div>
</template>
