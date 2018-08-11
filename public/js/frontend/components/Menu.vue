<template>
    <div id="menu">
        <nav class="navbar navbar-default style-9 navbar-landing" data-spy="affix" data-offset-top="60">
            <div>
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse" aria-expanded="false">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <router-link :to="{ name: 'dashboard'}"><img width="50px" src="/img/clock.png" /></router-link>
                </div>

                <div class="collapse navbar-collapse" id="navbar-collapse">
                    <ul class="nav navbar-nav navbar-right onepage-nav">
                        <li><router-link :to="{ name: 'working-time'}" active-class="active">WorkingTime</router-link></li>
                        <li><router-link :to="{ name: 'project'}" active-class="active">Project</router-link></li>
                        <li><router-link :to="{ name: 'summary'}" active-class="active">Summary</router-link></li>
                        <li><a @click="onLogout" href="#">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</template>

<script type="text/ecmascript-6">
    export default {
        methods: {
            onLogout(){
                return this.$store.dispatch('logout')
            }
        }
    }
</script>

<style>

</style>