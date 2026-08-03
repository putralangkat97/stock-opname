<script setup lang="ts">
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";

interface BreadcrumbEntryProps {
    label: string;
    href?: string;
    /** hide this crumb below the md breakpoint, like the original example */
    hideOnMobile?: boolean;
}

const props = defineProps<{
    breadcrumbs: BreadcrumbEntryProps[];
}>();
</script>

<template>
    <Breadcrumb>
        <BreadcrumbList>
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem
                    :class="item.hideOnMobile ? 'hidden md:block' : undefined"
                >
                    <BreadcrumbLink
                        v-if="item.href && index !== breadcrumbs.length - 1"
                        :href="item.href"
                    >
                        {{ item.label }}
                    </BreadcrumbLink>
                    <BreadcrumbPage v-else>{{ item.label }}</BreadcrumbPage>
                </BreadcrumbItem>

                <BreadcrumbSeparator
                    v-if="index !== breadcrumbs.length - 1"
                    :class="item.hideOnMobile ? 'hidden md:block' : undefined"
                />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
