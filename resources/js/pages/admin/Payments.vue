<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import PaymentController from '@/actions/App/Http/Controllers/Admin/PaymentController';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { index } from '@/routes/admin/payments';
import type { Paginated, Payment } from '@/types';

type Props = {
    payments: Paginated<Payment>;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Payments', href: index() }],
    },
});

const textareaClass = cn(
    'border-input placeholder:text-muted-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
);
</script>

<template>
    <Head title="Payments" />

    <div class="space-y-6">
        <Heading
            title="Payments"
            description="Review pending access requests"
        />

        <div v-if="payments.data.length > 0" class="space-y-4">
            <Card v-for="payment in payments.data" :key="payment.id">
                <CardHeader class="flex-row items-start justify-between gap-2">
                    <div>
                        <CardTitle>{{ payment.course?.title }}</CardTitle>
                        <p class="text-muted-foreground text-sm">
                            {{ payment.user?.name }} ({{ payment.user?.email }})
                        </p>
                    </div>
                    <Badge variant="outline" class="capitalize">
                        {{ payment.method.replace('_', ' ') }}
                    </Badge>
                </CardHeader>
                <CardContent class="text-sm">
                    <span class="font-semibold">
                        {{
                            Number(payment.amount) > 0
                                ? `$${payment.amount}`
                                : 'Free'
                        }}
                    </span>
                    <span class="text-muted-foreground">
                        · requested {{ payment.created_at }}
                    </span>
                </CardContent>
                <CardFooter class="gap-2">
                    <Form
                        v-bind="PaymentController.update.form(payment)"
                        v-slot="{ processing }"
                    >
                        <input type="hidden" name="status" value="confirmed" />
                        <Button type="submit" size="sm" :disabled="processing">
                            Confirm
                        </Button>
                    </Form>

                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="outline" size="sm">
                                Reject
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                v-bind="PaymentController.update.form(payment)"
                                v-slot="{ processing }"
                                class="space-y-4"
                            >
                                <input
                                    type="hidden"
                                    name="status"
                                    value="rejected"
                                />
                                <DialogHeader>
                                    <DialogTitle
                                        >Reject this payment?</DialogTitle
                                    >
                                    <DialogDescription>
                                        The student will not be enrolled in "{{
                                            payment.course?.title
                                        }}".
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="grid gap-2">
                                    <Label for="notes">Notes (optional)</Label>
                                    <textarea
                                        id="notes"
                                        name="notes"
                                        :class="textareaClass"
                                        placeholder="Reason for rejection"
                                    ></textarea>
                                </div>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button variant="secondary">
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        :disabled="processing"
                                    >
                                        Reject payment
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </CardFooter>
            </Card>
        </div>
        <div
            v-else
            class="text-muted-foreground rounded-lg border border-dashed p-12 text-center text-sm"
        >
            No pending payments to review.
        </div>

        <Pagination :paginator="payments" />
    </div>
</template>
