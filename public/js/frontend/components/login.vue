<template>
    <div id="login">
        <div class="login-page">
            <div class="form">
                <span class="error">{{ error }}</span>
                <form @submit.prevent="loginOnSubmit" class="login-form">
                    <input type="email" placeholder="email" v-model="email" />
                    <input type="password" placeholder="password" v-model="password" autocomplete="off" />
                    <button>login</button>
                </form>
            </div>
        </div>
    </div>
</template>

<script type="text/ecmascript-6">
    export default{
        data(){
            return {
                email: '',
                password: ''
            }
        },
        computed: {
            user(){
                return this.$store.getters.isAuthenticated
            },
            error(){
                return this.$store.getters.getErrorMsg
            }
        },
        watch: {
            user(value){
                if(value){
                    this.$router.push({name: 'dashboard'})
                }
            }
        },
        methods: {
            loginOnSubmit(){
                const formData = {
                    email: this.email,
                    password: this.password
                }
                this.$store.dispatch('login', formData)
            }
        }
    }
</script>

<style>
    #login .login-page {
        width: 360px;
        padding: 8% 0 0;
        margin: auto;
    }
    #login .form {
        position: relative;
        z-index: 1;
        background: #FFFFFF;
        max-width: 360px;
        margin: 0 auto 100px;
        padding: 45px;
        text-align: center;
        box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
    }
    #login .form input {
        font-family: "Roboto", sans-serif;
        outline: 0;
        background: #f2f2f2;
        width: 100%;
        border: 0;
        margin: 0 0 15px;
        padding: 15px;
        box-sizing: border-box;
        font-size: 14px;
    }
    #login .form button {
        font-family: "Roboto", sans-serif;
        text-transform: uppercase;
        outline: 0;
        background: #4CAF50;
        width: 100%;
        border: 0;
        padding: 15px;
        color: #FFFFFF;
        font-size: 14px;
        -webkit-transition: all 0.3 ease;
        transition: all 0.3 ease;
        cursor: pointer;
    }
    #login .form button:hover,.form button:active,.form button:focus {
        background: #43A047;
    }
    #login .form .message {
        margin: 15px 0 0;
        color: #b3b3b3;
        font-size: 12px;
    }
    #login .form .message a {
        color: #4CAF50;
        text-decoration: none;
    }
    #login .form .register-form {
        display: none;
    }
    #login .container {
        position: relative;
        z-index: 1;
        max-width: 300px;
        margin: 0 auto;
    }
    #login .container:before, .container:after {
        content: "";
        display: block;
        clear: both;
    }
    #login .container .info {
        margin: 50px auto;
        text-align: center;
    }
    #login .container .info h1 {
        margin: 0 0 15px;
        padding: 0;
        font-size: 36px;
        font-weight: 300;
        color: #1a1a1a;
    }
    #login .container .info span {
        color: #4d4d4d;
        font-size: 12px;
    }
    #login .container .info span a {
        color: #000000;
        text-decoration: none;
    }
    #login .container .info span .fa {
        color: #EF3B3A;
    }
</style>