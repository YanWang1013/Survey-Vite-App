<template>
    <PageComponent title="Dashboard">
        <div v-if="loading" class="flex justify-center">Loading...</div>
        <div
            v-else
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 text-gray-700"
        >
            <DashboardCard class="order-1 lg:order-2" style="animation-delay: 0.1s">
                <template v-slot:title>Total Surveys</template>
                <div
                    class="text-8xl pb-4 font-semibold flex-1 flex items-center justify-center"
                >
                    {{ data.totalSurveys }}
                </div>
            </DashboardCard>
            <DashboardCard class="order-2 lg:order-3" style="animation-delay: 0.2s">
                <template v-slot:title>Total Candidates</template>
                <div
                    class="text-8xl pb-4 font-semibold flex-1 flex items-center justify-center"
                >
                    {{ data.totalAnswerUsers }}
                </div>
            </DashboardCard>
            <DashboardCard class="order-3 lg:order-4" style="animation-delay: 0.2s">
                <template v-slot:title>Total Answers</template>
                <div
                    class="text-8xl pb-4 font-semibold flex-1 flex items-center justify-center"
                >
                    {{ data.totalAnswers }}
                </div>
            </DashboardCard>
            <DashboardCard class="order-4 lg:order-1 col-span-3" style="animation-delay: 0.2s">
                <template v-slot:title>Answered users per question</template>
                <div
                    class="text-8xl pb-4 font-semibold flex-1 flex items-center justify-center"
                >
                    <Bar
                        :data="userAnswerChartData"
                        :options="userAnswerChartOptions"
                    />
                </div>
            </DashboardCard>
            <DashboardCard class="order-5 lg:order-4 col-span-3" style="animation-delay: 0.2s">
                <template v-slot:title>Average answers per question</template>
                <div
                    class="text-8xl pb-4 font-semibold flex-1 flex items-center justify-center"
                >
                    <Bar
                        :data="avgAnswerChartData"
                        :options="avgAnswerChartOptions"
                    />
                </div>
            </DashboardCard>
            <DashboardCard
                v-if="current_user_role > Constants.GUEST_ROLE"
                class="order-6 lg:order-5 row-span-2"
                style="animation-delay: 0.2s"
            >
                <template v-slot:title>Latest Survey</template>
                <div v-if="data.latestSurvey">
                    <img
                        :src="data.latestSurvey.image_url"
                        class="w-[240px] mx-auto"
                        alt=""
                    />
                    <h3 class="font-bold text-xl mb-3">{{ data.latestSurvey.title }}</h3>
                    <div class="flex justify-between text-sm mb-1">
                        <div>Create Date:</div>
                        <div>{{ data.latestSurvey.created_at }}</div>
                    </div>
                    <div class="flex justify-between text-sm mb-1">
                        <div>Expire Date:</div>
                        <div>{{ data.latestSurvey.expire_date }}</div>
                    </div>
                    <div class="flex justify-between text-sm mb-1">
                        <div>Status:</div>
                        <div>{{ data.latestSurvey.status ? "Active" : "Draft" }}</div>
                    </div>
                    <div class="flex justify-between text-sm mb-1">
                        <div>Questions:</div>
                        <div>{{ data.latestSurvey.questions }}</div>
                    </div>
                    <div class="flex justify-between text-sm mb-3">
                        <div>Answers:</div>
                        <div>{{ data.latestSurvey.answers }}</div>
                    </div>
                    <div class="flex justify-between">
                        <TButton
                            v-if="current_user_role > Constants.GUEST_ROLE"
                            :to="{ name: 'SurveyView', params: { id: data.latestSurvey.id } }"
                            link
                        >
                            <PencilIcon class="w-5 h-5 mr-2" />
                            Edit Survey
                        </TButton>
                        <TButton :href="`/view/survey/${data.latestSurvey.slug}`" link>
                            <EyeIcon class="w-5 h-5 mr-2" />
                            View Answers
                        </TButton>
                    </div>
                </div>
                <div v-else class="text-gray-600 text-center py-16">
                    You don't have surveys yet
                </div>
            </DashboardCard>
            <DashboardCard class="order-7 lg:order-6 row-span-2" style="animation-delay: 0.3s">
                <template v-slot:title>
                    <div class="flex justify-between items-center mb-3 px-2">
                        <h3 class="text-2xl font-semibold">Latest Answers</h3>
<!--                        <a-->
<!--                            href="javascript:void(0)"-->
<!--                            class="text-sm text-blue-500 hover:decoration-blue-500"-->
<!--                        >-->
<!--                            View all-->
<!--                        </a>-->
                    </div>
                </template>
                <div v-if="data.latestAnswers.length" class="text-left">
                    <a
                        :href="`/view/survey/${answer.survey.slug}`"
                        v-for="answer of data.latestAnswers"
                        :key="answer.id"
                        class="block p-2 hover:bg-gray-100/90"
                    >
                        <div class="font-semibold">{{ answer.survey.title }}</div>
                        <small>
                            Answer Made at:
                            <i class="font-semibold">{{ answer.end_date }}</i>
                        </small>
                    </a>
                </div>
                <div v-else class="text-gray-600 text-center py-16">
                    You don't have answers yet
                </div>
            </DashboardCard>
        </div>
    </PageComponent>
</template>

<script setup>
import {EyeIcon, PencilIcon} from "@heroicons/vue/solid"
import DashboardCard from "../components/core/DashboardCard.vue";
import TButton from "../components/core/TButton.vue";
import PageComponent from "../components/PageComponent.vue";
import {computed, ref} from "vue";
import { useStore } from "vuex";
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
import { Bar } from 'vue-chartjs'
import Constants from "../components/Constants.vue";

const store = useStore();
const current_user_role = store.state.user.data.role;
const loading = computed(() => store.state.dashboard.loading);
const data = computed(() => store.state.dashboard.data);
const avgAnswerChartData = computed(()=> {
    const avgAnswers = store.state.dashboard.data.avgAnswers;
    const avgAnswerChartDataLabels = [];
    const avgAnswerChartDataValues = [];
    avgAnswers.forEach((item)=>{
        avgAnswerChartDataLabels.push(item.question);
        avgAnswerChartDataValues.push(item.avg);
    })
    return {
        labels: avgAnswerChartDataLabels,
        datasets: [
            {
                label: "average value",
                data: avgAnswerChartDataValues,
                backgroundColor: ['#123E6B'],
            },
        ],
    };
});
const userAnswerChartData = computed(()=> {
    const avgAnswers = store.state.dashboard.data.avgAnswers;
    const avgAnswerChartDataLabels = [];
    const avgAnswerChartDataValues = [];
    avgAnswers.forEach((item)=>{
        avgAnswerChartDataLabels.push(item.question);
        avgAnswerChartDataValues.push(item.users);
    })
    return {
        labels: avgAnswerChartDataLabels,
        datasets: [
            {
                label: "number of users",
                data: avgAnswerChartDataValues,
                backgroundColor: ['#123E6B'],
            },
        ],
    };
});

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const avgAnswerChartOptions = ref({
    indexAxis: 'y',
    elements: {
        bar: {
            borderWidth: 2,
        }
    },
    responsive: true,
    plugins: {
        legend: {
            position: 'left',
        },
        title: {
            display: true,
            text: 'average value of answers',
        },
    },
});
const userAnswerChartOptions = ref({
    indexAxis: 'y',
    elements: {
        bar: {
            borderWidth: 2,
        }
    },
    responsive: true,
    plugins: {
        legend: {
            position: 'left',
        },
        title: {
            display: true,
            text: 'number of answered users',
        },
    },
});

store.dispatch("getDashboardData");
</script>

<style scoped></style>
