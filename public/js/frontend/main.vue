<template>
    <div id="main-page">
        <div v-if="loggedAs!=null">
            <div class="logged-as">Logged as: <strong>{{ loggedAs }}</strong></div>
            <app-menu></app-menu>
        </div>
        <router-view></router-view>
    </div>
</template>

<script type="text/ecmascript-6">
    import Menu from './components/Menu.vue'
    import Dashboard from './components/Dashboard.vue'

    export default {
        name: 'main-page',
        computed: {
            loggedAs(){
                return this.$store.getters.loggedAs
            }
        },
        components:{
            'app-menu': Menu
        }
    }
</script>

<style>
#main-page .logged-as{
    text-align: right;
    margin-right: 1%;
}
</style>