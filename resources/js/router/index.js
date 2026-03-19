import { createRouter, createWebHistory } from "vue-router";
import LoginView from "@/views/auth/LoginView.vue";

const routes = [
    {
        path: "/",
        name: "login",
        component: LoginView,
    },
];

export default createRouter({
    history: createWebHistory(),
    routes,
});
