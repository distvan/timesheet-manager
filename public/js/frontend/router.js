import Vue from 'vue'
import VueRouter from 'vue-router'
import AuthGuard from './auth-guard'
import Login from './components/Login.vue'
import Dashboard from './components/Dashboard.vue'
import WorkingTime from './components/WorkingTime.vue'
import Project from './components/Project.vue'
import Summary from './components/Summary.vue'

Vue.use(VueRouter)

const routes = [
    {
        path: '/',
        name: 'loginForm',
        component: Login
    },
    {
        path: '/login',
        name: 'loginForm',
        component: Login
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: Dashboard,
        beforeEnter: AuthGuard
    },
    {
        path: '/working-time',
        name: 'working-time',
        component: WorkingTime,
        beforeEnter: AuthGuard
    },
    {
        path: '/project',
        name: 'project',
        component: Project,
        beforeEnter: AuthGuard
    },
    {
        path: '/summary',
        name: 'summary',
        component: Summary,
        beforeEnter: AuthGuard
    },
    {
        path: '/logout',
        name: 'logout',
        component: Login,
        beforeEnter: AuthGuard
    }
]

export default new VueRouter({mode: '', routes})