<script setup lang="ts">
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { LoaderIcon } from "@lucide/vue";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

function handleLogin() {
    form.post("/login");
}
</script>

<template>
    <div
        class="bg-gray-100 flex items-center justify-center h-screen px-4 sm:px-0 w-full"
    >
        <div
            class="bg-white rounded-lg shadow border border-gray-200 p-6 sm:max-w-lg mx-auto w-full"
        >
            <div class="flex flex-col gap-4">
                <div class="text-2xl font-medium text-gray-700 text-center">
                    Welcome!
                </div>
                <form
                    @submit.prevent="handleLogin"
                    class="flex flex-col items-start gap-4"
                >
                    <div class="flex flex-col gap-0.5 w-full">
                        <label for="email" class="text-sm text-gray-600">
                            Email
                        </label>
                        <input
                            type="email"
                            id="email"
                            v-model="form.email"
                            placeholder="test@example.com"
                            class="w-full px-3 py-2 border border-gray-200 rounded-md"
                        />
                    </div>
                    <div class="flex flex-col gap-0.5 w-full">
                        <label for="password" class="text-sm text-gray-600">
                            Password
                        </label>
                        <input
                            type="password"
                            id="password"
                            v-model="form.password"
                            placeholder="your password"
                            class="w-full px-3 py-2 border border-gray-200 rounded-md"
                        />
                    </div>
                    <div class="flex items-center">
                        <label for="remember" class="flex gap-1.5 items-center">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                v-model="form.remember"
                                class="h-4 w-4 rounded-md"
                            />
                            <span class="text-sm">Remember Me</span>
                        </label>
                    </div>
                    <button
                        type="submit"
                        class="w-full px-4 py-2 bg-gray-800 text-white rounded-md inline-flex justify-center items-center transition-all active:translate-y-px"
                        :class="{ 'opacity-50': form.processing }"
                        :disabled="form.processing"
                    >
                        <LoaderIcon
                            v-if="form.processing"
                            class="animate-spin"
                        />
                        <template v-else>Login</template>
                    </button>
                </form>
                <div></div>
            </div>
        </div>
    </div>
</template>
