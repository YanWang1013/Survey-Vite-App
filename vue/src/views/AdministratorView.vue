<template>
    <PageComponent>
        <template v-slot:header>
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ route.params.id ? admin.name : "Create a Administrator" }}
                </h1>

                <div class="flex">
                    <TButton v-if="route.params.id" color="red" @click="deleteAdministrator()">
                        <TrashIcon class="w-5 h-5 mr-2" />
                        Delete
                    </TButton>
                </div>
            </div>
        </template>
        <div v-if="administratorLoading" class="flex justify-center">Loading...</div>
        <form v-else @submit.prevent="saveAdministrator" class="animate-fade-in-down mt-8 col-md-6 mx-auto">
            <Alert
                v-if="Object.keys(errors).length"
                class="flex-col items-stretch text-sm"
            >
                <div v-for="(field, i) of Object.keys(errors)" :key="i">
                    <div v-for="(error, ind) of errors[field] || []" :key="ind">
                        * {{ error }}
                    </div>
                </div>
            </Alert>
            <div class="shadow sm:rounded-md sm:overflow-hidden">
                <!-- Administrator Fields -->
                <div class="px-4 py-5 bg-white space-y-6 sm:p-6">

                    <!-- Name -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">
                            Full Name
                        </label>
                        <input
                            type="text"
                            name="title"
                            id="title"
                            v-model="admin.name"
                            autocomplete="Administrator_name"
                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        />
                    </div>
                    <!--/ Name -->
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Email Address
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            v-model="admin.email"
                            autocomplete="Administrator_email"
                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        />
                    </div>
                    <!--/ Email -->
                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            v-model="admin.password"
                            autocomplete="Administrator_password"
                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        />
                    </div>
                    <!--/ Password -->
                    <!-- Password Confirmation -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            Confirm Password
                        </label>
                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            v-model="admin.password_confirmation"
                            autocomplete="Administrator_password_confirmation"
                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        />
                    </div>
                    <!--/ Password Confirmation -->

                </div>
                <!--/ Administrator Fields -->

                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                    <TButton>
                        <SaveIcon class="w-5 h-5 mr-2" />
                        Save
                    </TButton>
                </div>
            </div>
        </form>
    </PageComponent>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { SaveIcon, TrashIcon } from '@heroicons/vue/solid'
import store from "../store";
import PageComponent from "../components/PageComponent.vue";
import TButton from "../components/core/TButton.vue";
import Alert from "../components/Alert.vue";

const router = useRouter();

const route = useRoute();

// Get Administrator loading state, which only changes when we fetch Administrator from backend
const administratorLoading = computed(() => store.state.currentAdministrator.loading);
const errors = ref({});

// Create empty Administrator
let admin = ref({
    id: "",
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

// Watch to current Administrator data change and when this happens we update local model
watch(
    () => store.state.currentAdministrator.data,
        (newVal, oldVal) => {
        admin.value = {
            ...JSON.parse(JSON.stringify(newVal)),
            status: !!newVal.status,
        };
    }
);

// If the current component is rendered on Administrator update route we make a request to fetch Administrator
if (route.params.id) {
    store.dispatch("getAdministrator", route.params.id);
}

/**
 * Create or update Administrator
 */
function saveAdministrator() {
    let action = "created";
    if (admin.value.id) {
        action = "updated";
    }
    store.dispatch("saveAdministrator", { ...admin.value }).then(({ data }) => {
        store.commit("notify", {
            type: "success",
            message: "The Administrator was successfully " + action,
        });
        router.push({
            name: "Administrators"
        });
    }).catch((error) => {
        if (error.response.status === 422) {
            errors.value = error.response.data.errors;
        }
    });
}

function deleteAdministrator() {
    if (
        confirm(
            `Are you sure you want to delete this administrator? Operation can't be undone!!`
        )
    ) {
        store.dispatch("deleteAdministrator", admin.value.id).then(() => {
            router.push({
                name: "Administrators",
            });
        });
    }
}
</script>

<style></style>
