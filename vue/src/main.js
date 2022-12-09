import { createApp } from 'vue'
import store from './store'
import router from './router'
import "bootstrap/dist/css/bootstrap.min.css"
import "bootstrap"
import './index.css'
import App from './App.vue'
import "vue3-easy-data-table/dist/style.css";

const app = createApp(App)
    .use(store)
    .use(router)
    .mount('#app');
