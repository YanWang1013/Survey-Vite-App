<!-- This example requires Tailwind CSS v2.0+ -->
<template>
    <PageComponent>
        <template v-slot:header>
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">Guests</h1>
            </div>
        </template>
        <div v-if="guests.loading" class="flex justify-center">Loading...</div>
        <div v-else-if="guests.data.length">
            <div class="grid grid-cols-1 gap-5">
                <Vue3EasyDataTable
                    :headers="headers"
                    :items="guests.data"
                    table-class-name="v3-easy-data-table"
                    buttons-pagination
                    show-index
                    border-cell
                />
            </div>
        </div>
        <div v-else class="text-gray-600 text-center py-16">
            No Guests
        </div>
    </PageComponent>
</template>

<script setup>
import store from "../store";
import { computed } from "vue";
import PageComponent from "../components/PageComponent.vue";
import Vue3EasyDataTable from 'vue3-easy-data-table';
import {useRouter} from "vue-router";

const router = useRouter();
const guests = computed(() => store.state.guests);

store.dispatch("getGuests").then(() => {
    const guestData = store.state.guests.data;
    guestData.forEach((item) => {
        item['answer_count'] = item.answers.length;
    })
});

const headers = [
    { text: "Full Name", value: "name", sortable: true},
    { text: "Email", value: "email", sortable: true},
    { text: "Answers", value: "answer_count", align: "center", sortable: true},
    { text: "Created At", value: "created_at", sortable: true}
];

</script>
