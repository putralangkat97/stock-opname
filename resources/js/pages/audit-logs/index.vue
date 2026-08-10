<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/app-layout.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    TableEmpty,
} from "@/components/ui/table";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type { PaginatedData, AuditLog } from "@/types/models";

const props = defineProps<{
    auditLogs: PaginatedData<AuditLog>;
    filters: { module?: string; action?: string };
    modules: string[];
    actions: string[];
}>();

// No flash-toast watcher here — this page has no mutating actions, so
// nothing ever flashes back to it (unlike every other index page).

const moduleFilter = ref(props.filters.module ?? "");
const actionFilter = ref(props.filters.action ?? "");

function applyFilters() {
    router.get(
        "/audit-logs",
        {
            module: moduleFilter.value || undefined,
            action: actionFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function actionVariant(action: string) {
    if (
        action.includes("rejected") ||
        action.includes("cancelled") ||
        action === "deleted"
    ) {
        return "destructive";
    }
    if (action.includes("approved") || action === "created") {
        return "default";
    }
    return "secondary";
}

const viewingDetails = ref<AuditLog | null>(null);
</script>

<template>
    <AppLayout :breadcrumbs="[{ label: 'Audit Trail', href: '/audit-logs' }]">
        <div class="flex flex-col gap-4">
            <h1 class="text-xl font-semibold">Audit Trail</h1>

            <div class="flex flex-wrap items-center gap-2">
                <Select
                    v-model="moduleFilter"
                    @update:model-value="applyFilters"
                >
                    <SelectTrigger class="w-48">
                        <SelectValue placeholder="All modules" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All modules</SelectItem>
                        <SelectItem v-for="m in modules" :key="m" :value="m">
                            {{ m }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Select
                    v-model="actionFilter"
                    @update:model-value="applyFilters"
                >
                    <SelectTrigger class="w-48">
                        <SelectValue placeholder="All actions" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All actions</SelectItem>
                        <SelectItem v-for="a in actions" :key="a" :value="a">
                            {{ a }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>When</TableHead>
                        <TableHead>User</TableHead>
                        <TableHead>Role</TableHead>
                        <TableHead>Action</TableHead>
                        <TableHead>Module</TableHead>
                        <TableHead>Record</TableHead>
                        <TableHead>IP</TableHead>
                        <TableHead class="text-right">Details</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="auditLogs.data.length === 0" :colspan="8">
                        No audit log entries match this filter.
                    </TableEmpty>
                    <TableRow v-for="log in auditLogs.data" :key="log.id">
                        <TableCell class="text-sm">
                            {{ log.created_at }}
                        </TableCell>
                        <TableCell>{{ log.user?.name ?? "System" }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            {{ log.role_snapshot ?? "—" }}
                        </TableCell>
                        <TableCell>
                            <Badge :variant="actionVariant(log.action)">
                                {{ log.action }}
                            </Badge>
                        </TableCell>
                        <TableCell>{{ log.module }}</TableCell>
                        <TableCell class="font-mono text-sm">
                            #{{ log.auditable_id }}
                        </TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            {{ log.ip_address ?? "—" }}
                        </TableCell>
                        <TableCell class="text-right">
                            <Button
                                v-if="
                                    log.details &&
                                    Object.keys(log.details).length
                                "
                                variant="outline"
                                size="sm"
                                @click="viewingDetails = log"
                            >
                                View
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <div
                class="flex items-center justify-between text-sm text-muted-foreground"
            >
                <span>
                    Page {{ auditLogs.current_page }} of
                    {{ auditLogs.last_page }}
                </span>
                <div class="flex gap-2">
                    <Button
                        v-for="link in auditLogs.links"
                        :key="link.label"
                        variant="outline"
                        size="sm"
                        :disabled="!link.url"
                        :class="{ 'bg-muted': link.active }"
                        v-html="link.label"
                        @click="
                            link.url &&
                            router.get(link.url, {}, { preserveState: true })
                        "
                    />
                </div>
            </div>
        </div>

        <Dialog
            :open="!!viewingDetails"
            @update:open="(v) => !v && (viewingDetails = null)"
        >
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{ viewingDetails?.action }} —
                        {{ viewingDetails?.module }} #{{
                            viewingDetails?.auditable_id
                        }}
                    </DialogTitle>
                </DialogHeader>
                <pre
                    class="max-h-96 overflow-auto rounded-md bg-muted p-4 text-xs"
                >
                    {{ JSON.stringify(viewingDetails?.details, null, 2) }}
                </pre>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
