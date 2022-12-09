import { createStore } from "vuex";
import createPersistedState from 'vuex-persistedstate';
import axiosClient from "../axios";

const store = createStore({
    state: {
        user: {
            data: {},
            token: sessionStorage.getItem("TOKEN"),
        },
        administrators: {
            loading: false,
            data: {}
        },
        currentAdministrator: {
          data: {},
          loading: false,
        },
        guests: {
            loading: false,
            data: {}
        },
        dashboard: {
            loading: false,
            data: {}
        },
        surveys: {
            loading: false,
            links: [],
            data: []
        },
        currentSurvey: {
            data: {},
            loading: false,
        },
        questionTypes: ["text", "select", "radio", "checkbox", "textarea"],
        notification: {
            show: false,
            type: 'success',
            message: ''
        }
    },
    getters: {},
    actions: {
        register({commit}, user) {
            return axiosClient.post('/register', user)
                .then(({data}) => {
                    commit('setUser', data.user);
                    commit('setToken', data.token);
                    return data;
                })
        },
        login({commit}, user) {
            return axiosClient.post('/login', user)
                .then(({data}) => {
                    commit('setUser', data.user);
                    commit('setToken', data.token);
                    return data;
                })
        },
        logout({commit}) {
            return axiosClient.post('/logout')
                .then(response => {
                    commit('logout')
                    return response;
                })
        },
        getUser({commit}) {
            return axiosClient.get('/user')
            .then(res => {
                commit('setUser', res.data)
            })
        },
        getDashboardData({commit}) {
            commit('dashboardLoading', true)
            return axiosClient.get(`/dashboard`)
            .then((res) => {
                commit('dashboardLoading', false)
                commit('setDashboardData', res.data)
                return res;
            })
            .catch(error => {
                commit('dashboardLoading', false)
                return error;
            })
        },
        getSurveys({ commit }, {url = null} = {}) {
            commit('setSurveysLoading', true)
            url = url || "/survey";
            return axiosClient.get(url).then((res) => {
                commit('setSurveysLoading', false)
                commit("setSurveys", res.data);
                return res;
            });
        },
        getSurvey({ commit }, id) {
            commit("setCurrentSurveyLoading", true);
            return axiosClient
                .get(`/survey/${id}`)
                .then((res) => {
                    commit("setCurrentSurvey", res.data);
                    commit("setCurrentSurveyLoading", false);
                    return res;
                })
                .catch((err) => {
                    commit("setCurrentSurveyLoading", false);
                    throw err;
                });
        },
        getSurveyBySlug({ commit }, {slug, userId}) {
            commit("setCurrentSurveyLoading", true);
            const params = new URLSearchParams();
            params.append('user_id', userId);
            return axiosClient
                .get(`/survey-by-slug/${slug}`, { params: params})
                .then((res) => {
                    commit("setCurrentSurvey", res.data);
                    commit("setCurrentSurveyLoading", false);
                    return res;
                })
                .catch((err) => {
                    commit("setCurrentSurveyLoading", false);
                    throw err;
                });
        },
        saveSurvey({ commit, dispatch }, survey) {
            delete survey.image_url;

            let response;
            if (survey.id) {
                response = axiosClient
                    .put(`/survey/${survey.id}`, survey)
                    .then((res) => {
                        commit('setCurrentSurvey', res.data)
                        return res;
                    });
            } else {
                response = axiosClient.post("/survey", survey).then((res) => {
                    commit('setCurrentSurvey', res.data)
                    return res;
                });
            }

            return response;
        },
        deleteSurvey({ dispatch }, id) {
            return axiosClient.delete(`/survey/${id}`).then((res) => {
                dispatch('getSurveys')
                return res;
            });
        },
        saveSurveyAnswer({commit}, {surveyId, userId, answers}) {
            return axiosClient.post(`/survey/${surveyId}/answer`, {answers: answers, user_id: userId});
        },
        getAdministrators({ commit }, {url = null} = {}) {
            commit('setAdministratorsLoading', true)
            url = url || "/manager";
            return axiosClient.get(url).then((res) => {
                commit('setAdministratorsLoading', false)
                commit("setAdministrators", res.data);
                return res;
            });
        },
        getAdministrator({ commit }, id) {
            commit("setCurrentAdministratorLoading", true);
            return axiosClient
                .get(`/manager/${id}`)
                .then((res) => {
                    commit("setCurrentAdministrator", res.data);
                    commit("setCurrentAdministratorLoading", false);
                    return res;
                })
                .catch((err) => {
                    commit("setCurrentAdministratorLoading", false);
                    throw err;
                });
        },
        saveAdministrator({ commit, dispatch }, admin) {
            let response;
            if (admin.id) {
                response = axiosClient
                    .put(`/manager/${admin.id}`, admin)
                    .then((res) => {
                        commit('setCurrentAdministrator', res.data)
                        return res;
                    });
            } else {
                response = axiosClient.post("/manager", admin).then((res) => {
                    commit('setCurrentAdministrator', res.data)
                    return res;
                });
            }
            return response;
        },
        deleteAdministrator({ dispatch }, id) {
            return axiosClient.delete(`/manager/${id}`).then((res) => {
                dispatch('getAdministrators')
                return res;
            });
        },
        getGuests({ commit }, {url = null} = {}) {
            commit('setGuestsLoading', true)
            return axiosClient.get('guests').then((res) => {
                commit('setGuestsLoading', false)
                commit("setGuests", res.data);
                return res;
            });
        },
    },
    plugins: [createPersistedState()],
    mutations: {
        logout: (state) => {
            state.user.token = null;
            state.user.data = {};
            sessionStorage.removeItem("TOKEN");
        },
        setUser: (state, user) => {
            state.user.data = user;
        },
        setToken: (state, token) => {
            state.user.token = token;
            sessionStorage.setItem('TOKEN', token);
        },
        dashboardLoading: (state, loading) => {
            state.dashboard.loading = loading;
        },
        setDashboardData: (state, data) => {
            state.dashboard.data = data
        },
        setSurveysLoading: (state, loading) => {
            state.surveys.loading = loading;
        },
        setSurveys: (state, surveys) => {
            state.surveys.links = surveys.meta.links;
            state.surveys.data = surveys.data;
        },
        setCurrentSurveyLoading: (state, loading) => {
            state.currentSurvey.loading = loading;
        },
        setCurrentSurvey: (state, survey) => {
            state.currentSurvey.data = survey.data;
        },
        setAdministratorsLoading: (state, loading) => {
            state.administrators.loading = loading;
        },
        setAdministrators: (state, admins) => {
            state.administrators.links = admins.meta.links;
            state.administrators.data = admins.data;
        },
        setCurrentAdministratorLoading: (state, loading) => {
            state.currentAdministrator.loading = loading;
        },
        setCurrentAdministrator: (state, admin) => {
            state.currentAdministrator.data = admin.data;
        },
        setGuestsLoading: (state, loading) => {
            state.guests.loading = loading;
        },
        setGuests: (state, guests) => {
            state.guests.links = guests.meta.links;
            state.guests.data = guests.data;
        },
        notify: (state, {message, type}) => {
            state.notification.show = true;
            state.notification.type = type;
            state.notification.message = message;
            setTimeout(() => {
                state.notification.show = false;
            }, 3000)
        },
    },
    modules: {},
});

export default store;
