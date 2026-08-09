<script setup lang="ts">
import { ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import AppLayout from "@/layouts/app-layout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
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
    DialogFooter,
} from "@/components/ui/dialog";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogCancel,
    AlertDialogAction,
} from "@/components/ui/alert-dialog";
import { Field, FieldLabel, FieldError } from "@/components/ui/field";
import type { PaginatedData, Unit } from "@/types/models";

defineProps<{
    units: PaginatedData<Unit>;
}>();

const breadcrumbs = [{ label: "Units", href: "/units" }];

const page = usePage();
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    },
    { immediate: true },
);

const dialogOpen = ref(false);
const editingUnit = ref<Unit | null>(null);

const form = useForm({
    code: "",
    name: "",
    symbol: "",
});

function openCreateDialog() {
    editingUnit.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEditDialog(unit: Unit) {
    editingUnit.value = unit;
    form.clearErrors();
    form.code = unit.code;
    form.name = unit.name;
    form.symbol = unit.symbol;
    dialogOpen.value = true;
}

function submit() {
    if (editingUnit.value) {
        form.put(`/units/${editingUnit.value.id}`, {
            onSuccess: () => (dialogOpen.value = false),
        });
    } else {
        form.post("/units", {
            onSuccess: () => (dialogOpen.value = false),
        });
    }
}

const deletingUnit = ref<Unit | null>(null);

function confirmDelete() {
    if (!deletingUnit.value) return;
    router.delete(`/units/${deletingUnit.value.id}`, {
        onSuccess: () => (deletingUnit.value = null),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Units</h1>
                <Button @click="openCreateDialog">Add Unit</Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Symbol</TableHead>
                        <TableHead>Products</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="units.data.length === 0" :colspan="5">
                        No units yet.
                    </TableEmpty>
                    <TableRow v-for="unit in units.data" :key="unit.id">
                        <TableCell class="font-mono text-sm">{{ unit.code }}</TableCell>
                        <TableCell>{{ unit.name }}</TableCell>
                        <TableCell>{{ unit.symbol }}</TableCell>
                        <TableCell>
                            <Badge variant="secondary">{{ unit.products_count ?? 0 }}</Badge>
                        </TableCell>
                        <TableCell class="flex justify-end gap-2 text-right">
                            <Button variant="outline" size="sm" @click="openEditDialog(unit)">
                                Edit
                            </Button>
                            <Button variant="destructive" size="sm" @click="deletingUnit = unit">
                                Delete
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <div class="flex items-center justify-between text-sm text-muted-foreground">
                <span>Page {{ units.current_page }} of {{ units.last_page }}</span>
                <div class="flex gap-2">
                    <Button
                        v-for="link in units.links"
                        :key="link.label"
                        variant="outline"
                        size="sm"
                        :disabled="!link.url"
                        :class="{ 'bg-muted': link.active }"
                        v-html="link.label"
                        @click="link.url && router.get(link.url, {}, { preserveState: true })"
                    />
                </div>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editingUnit ? "Edit Unit" : "Add Unit" }}</DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <Field>
                        <FieldLabel>Code</FieldLabel>
                        <Input v-model="form.code" />
                        <FieldError :errors="[form.errors.code]" />
                    </Field>
                    <Field>
                        <FieldLabel>Name</FieldLabel>
                        <Input v-model="form.name" />
                        <FieldError :errors="[form.errors.name]" />
                    </Field>
                    <Field>
                        <FieldLabel>Symbol</FieldLabel>
                        <Input v-model="form.symbol" placeholder="e.g. pcs, kg, box" />
                        <FieldError :errors="[form.errors.symbol]" />
                    </Field>

                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            {{ editingUnit ? "Save Changes" : "Create Unit" }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog :open="!!deletingUnit" @update:open="(v) => !v && (deletingUnit = null)">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete this unit?</AlertDialogTitle>
                    <AlertDialogDescription>
                        "{{ deletingUnit?.name }}" will be removed. This can't be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="deletingUnit = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="confirmDelete">Delete</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
