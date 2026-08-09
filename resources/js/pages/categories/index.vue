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
import type { PaginatedData, Category } from "@/types/models";

defineProps<{
    categories: PaginatedData<Category>;
}>();

const breadcrumbs = [{ label: "Categories", href: "/categories" }];

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
const editingCategory = ref<Category | null>(null);

const form = useForm({
    code: "",
    name: "",
    description: "",
});

function openCreateDialog() {
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEditDialog(category: Category) {
    editingCategory.value = category;
    form.clearErrors();
    form.code = category.code;
    form.name = category.name;
    form.description = category.description ?? "";
    dialogOpen.value = true;
}

function submit() {
    if (editingCategory.value) {
        form.put(`/categories/${editingCategory.value.id}`, {
            onSuccess: () => (dialogOpen.value = false),
        });
    } else {
        form.post("/categories", {
            onSuccess: () => (dialogOpen.value = false),
        });
    }
}

const deletingCategory = ref<Category | null>(null);

function confirmDelete() {
    if (!deletingCategory.value) return;
    router.delete(`/categories/${deletingCategory.value.id}`, {
        onSuccess: () => (deletingCategory.value = null),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Categories</h1>
                <Button @click="openCreateDialog">Add Category</Button>
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
                    <TableEmpty v-if="categories.data.length === 0" :colspan="4">
                        No categories yet.
                    </TableEmpty>
                    <TableRow v-for="category in categories.data" :key="category.id">
                        <TableCell class="font-mono text-sm">{{ category.code }}</TableCell>
                        <TableCell>{{ category.name }}</TableCell>
                        <TableCell>
                            <Badge variant="secondary">{{ category.products_count ?? 0 }}</Badge>
                        </TableCell>
                        <TableCell class="flex justify-end gap-2 text-right">
                            <Button variant="outline" size="sm" @click="openEditDialog(category)">
                                Edit
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deletingCategory = category"
                            >
                                Delete
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <div class="flex items-center justify-between text-sm text-muted-foreground">
                <span>Page {{ categories.current_page }} of {{ categories.last_page }}</span>
                <div class="flex gap-2">
                    <Button
                        v-for="link in categories.links"
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
                    <DialogTitle>{{ editingCategory ? "Edit Category" : "Add Category" }}</DialogTitle>
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

                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            {{ editingCategory ? "Save Changes" : "Create Category" }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog :open="!!deletingCategory" @update:open="(v) => !v && (deletingCategory = null)">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete this category?</AlertDialogTitle>
                    <AlertDialogDescription>
                        "{{ deletingCategory?.name }}" will be removed. This can't be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="deletingCategory = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="confirmDelete">Delete</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
