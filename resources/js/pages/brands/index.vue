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
    DialogDescription,
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
import type { PaginatedData, Brand } from "@/types/models";
import { LoaderIcon } from "@lucide/vue";

defineProps<{
    brands: PaginatedData<Brand>;
}>();

const breadcrumbs = [{ label: "Brands", href: "/brands" }];

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
const editingBrand = ref<Brand | null>(null);

const form = useForm({
    code: "",
    name: "",
    description: "",
    logo_url: "",
});

function openCreateDialog() {
    editingBrand.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEditDialog(brand: Brand) {
    editingBrand.value = brand;
    form.clearErrors();
    form.code = brand.code;
    form.name = brand.name;
    form.description = brand.description ?? "";
    form.logo_url = brand.logo_url ?? "";
    dialogOpen.value = true;
}

function submit() {
    if (editingBrand.value) {
        form.put(`/brands/${editingBrand.value.id}`, {
            onSuccess: () => (dialogOpen.value = false),
        });
    } else {
        form.post("/brands", {
            onSuccess: () => (dialogOpen.value = false),
        });
    }
}

const deletingBrand = ref<Brand | null>(null);

function confirmDelete() {
    if (!deletingBrand.value) return;
    router.delete(`/brands/${deletingBrand.value.id}`, {
        onSuccess: () => (deletingBrand.value = null),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Brands</h1>
                <Button @click="openCreateDialog">Add Brand</Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Products</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="brands.data.length === 0" :colspan="4">
                        No brands yet.
                    </TableEmpty>
                    <TableRow v-for="brand in brands.data" :key="brand.id">
                        <TableCell class="font-mono text-sm">{{
                            brand.code
                        }}</TableCell>
                        <TableCell>{{ brand.name }}</TableCell>
                        <TableCell>
                            <Badge variant="secondary">{{
                                brand.products_count ?? 0
                            }}</Badge>
                        </TableCell>
                        <TableCell class="flex justify-end gap-2 text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openEditDialog(brand)"
                            >
                                Edit
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deletingBrand = brand"
                            >
                                Delete
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <div
                class="flex items-center justify-between text-sm text-muted-foreground"
            >
                <span
                    >Page {{ brands.current_page }} of
                    {{ brands.last_page }}</span
                >
                <div class="flex gap-2">
                    <Button
                        v-for="link in brands.links"
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

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{
                        editingBrand ? "Edit Brand" : "Add Brand"
                    }}</DialogTitle>
                    <DialogDescription>Form brand</DialogDescription>
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
                        <FieldLabel>Description (optional)</FieldLabel>
                        <Input v-model="form.description" />
                        <FieldError :errors="[form.errors.description]" />
                    </Field>
                    <Field>
                        <FieldLabel>Logo URL (optional)</FieldLabel>
                        <Input
                            v-model="form.logo_url"
                            placeholder="https://..."
                        />
                        <FieldError :errors="[form.errors.logo_url]" />
                    </Field>

                    <DialogFooter>
                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            <LoaderIcon
                                v-if="form.processing"
                                class="animate-spin"
                            />
                            <template v-else>
                                {{
                                    editingBrand
                                        ? "Save Changes"
                                        : "Create Brand"
                                }}
                            </template>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog
            :open="!!deletingBrand"
            @update:open="(v) => !v && (deletingBrand = null)"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete this brand?</AlertDialogTitle>
                    <AlertDialogDescription>
                        "{{ deletingBrand?.name }}" will be removed. This can't
                        be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="deletingBrand = null"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction @click="confirmDelete"
                        >Delete</AlertDialogAction
                    >
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
