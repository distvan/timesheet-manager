import Vue from 'vue'
import VueResource from 'vue-resource'
import Main from 'Main.vue'

//https://www.thepolyglotdeveloper.com/2018/04/simple-user-login-vuejs-web-application/

Vue.use(VueResource)

new Vue({
    el: '#container',
    render: h=> h(Main)
})