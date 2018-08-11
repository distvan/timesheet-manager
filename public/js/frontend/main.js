import Vue from 'vue'
import Main from './Main.vue'
import router from './router'
import store from './store'

new Vue({
    el: '#container',
    router: router,
    store: store,
    render: h => h(Main)
})