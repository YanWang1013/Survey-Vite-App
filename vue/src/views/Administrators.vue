<!-- This example requires Tailwind CSS v2.0+ -->
<template>
    <PageComponent>
        <template v-slot:header>
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">Administrators</h1>
                <TButton color="green" :to="{ name: 'AdministratorCreate' }">
                    <PlusIcon class="w-5 h-5" />
                    Add new Administrator
                </TButton>
            </div>
        </template>
        <div v-if="administrators.loading" class="flex justify-center">Loading...</div>
        <div v-else-if="administrators.data.length">
            <div class="grid grid-cols-1 gap-5">
                <Vue3EasyDataTable
                    :headers="headers"
                    :items="administrators.data"
                    table-class-name="v3-easy-data-table"
                    buttons-pagination
                    show-index
                    border-cell
                    @click-row="showRow"
                />
            </div>
        </div>
        <div v-else class="text-gray-600 text-center py-16">
            No Administrator
        </div>
    </PageComponent>
</template>

<script setup>
import store from "../store";
import { computed } from "vue";
import {PlusIcon} from "@heroicons/vue/solid"
import TButton from '../components/core/TButton.vue'
import PageComponent from "../components/PageComponent.vue";
import Vue3EasyDataTable from 'vue3-easy-data-table';
import {useRouter} from "vue-router";

const router = useRouter();
const administrators = computed(() => store.state.administrators);

store.dispatch("getAdministrators");

const headers = [
    { text: "Full Name", value: "name", sortable: true},
    { text: "Email", value: "email", sortable: true},
    { text: "Created At", value: "created_at", sortable: true}
];

function showRow(item) {
    router.push({
        name: "AdministratorView", params: { id: item.id }
    });
}

function deleteAdministrator(Administrator) {
    if (
        confirm(
            `Are you sure you want to delete this Administrator? Operation can't be undone!!`
        )
    ) {
        store.dispatch("deleteAdministrator", Administrator.id).then(() => {
            store.dispatch("getAdministrators");
        });
    }
}

function getForPage(ev, link) {
    ev.preventDefault();
    if (!link.url || link.active) {
        return;
    }

    store.dispatch("getAdministrators", { url: link.url });
}
</script>
