<script setup lang="ts">
import AppLayout from "@/layouts/app-layout.vue";
import { usePage, useForm } from "@inertiajs/vue3";

const page = usePage();
const user = page.props.auth?.user;

const breadcrumbs = [{ label: "Dashboard", href: "/dashboard" }];

const form = useForm();

function handleLogout() {
    form.post("/logout");
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs"> </AppLayout>
</template>
