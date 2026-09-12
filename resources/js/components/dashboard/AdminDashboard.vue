<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { BookOpen, Clock, DollarSign, GraduationCap, Users } from "@lucide/vue";
import StatCard from "@/components/dashboard/StatCard.vue";
import Heading from "@/components/Heading.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { index as adminPaymentsIndex } from "@/routes/admin/payments";
import type { AdminDashboardProps } from "@/types";

defineProps<Pick<AdminDashboardProps, "stats" | "pending_payments">>();

const methodLabel: Record<string, string> = {
    cash: "Efectivo",
    bank_transfer: "Transferencia",
    other: "Otro",
};

const dateFormatter = new Intl.DateTimeFormat("es", { dateStyle: "medium" });

function formatDate(value: string): string {
    return dateFormatter.format(new Date(value));
}
</script>

<template>
    <div class="space-y-8">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <StatCard label="Usuarios" :value="stats.users" :icon="Users" />
            <StatCard label="Cursos" :value="stats.courses" :icon="BookOpen" />
            <StatCard
                label="Inscripciones activas"
                :value="stats.active_enrollments"
                :icon="GraduationCap"
            />
            <StatCard
                label="Ingresos confirmados"
                :value="stats.confirmed_revenue"
                :icon="DollarSign"
                format="currency"
            />
            <StatCard
                label="Pagos pendientes"
                :value="stats.pending_payments"
                :icon="Clock"
                :href="adminPaymentsIndex()"
            />
        </div>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <Heading title="Pagos pendientes" variant="small" />
                <Link :href="adminPaymentsIndex()">
                    <Button size="sm" variant="outline">Ver todos</Button>
                </Link>
            </div>

            <div v-if="pending_payments.length > 0" class="space-y-3">
                <Card v-for="payment in pending_payments" :key="payment.id">
                    <CardContent
                        class="flex items-center justify-between gap-4 pt-6"
                    >
                        <div class="min-w-0">
                            <p class="font-medium">{{ payment.user?.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ payment.course?.title }} ·
                                {{ formatDate(payment.created_at) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Badge variant="secondary">
                                {{ methodLabel[payment.method] }}
                            </Badge>
                            <span class="font-medium">
                                ${{ payment.amount }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                No hay pagos pendientes de revisión.
            </p>
        </section>
    </div>
</template>
