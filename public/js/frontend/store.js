import Vue from 'vue'
import Vuex from 'vuex'
import router from './router'
import axios from 'axios'

axios.defaults.baseURL = ''
axios.defaults.headers.get['Accepts'] = 'application/json'

Vue.use(Vuex)

export default new Vuex.Store({
    state: {
        userId: null,
        idToken: null,
        firstName: null,
        errorMsg: null
    },
    mutations: {
        authUser(state, userData){
            state.userId = userData.userId
            state.idToken = userData.idToken
            state.firstName = userData.firstName
        },
        clearAuthData(state){
            state.userId = null
            state.idToken = null
            state.firstName = null
        },
        setErrorMsg(state, error){
            state.errorMsg = error
        }
    },
    actions: {
        login({commit, dispatch, state}, authData){
            axios.post('/api/login', {
                user: authData.email,
                password: authData.password
            }).then(res => {
                localStorage.setItem('token', res.data.token_id)
                localStorage.setItem('userId', res.data.user_id)
                commit('authUser', {
                    token: res.data.token_id,
                    userId: res.data.user_id,
                    firstName: res.data.first_name
                })
                router.push({name: 'dashboard'})
            }).catch(error => {
                if(error.response.status == 401){
                    commit('setErrorMsg', 'Bad login!')
                }
            })
        },
        logout({commit}){
            commit('clearAuthData')
            localStorage.removeItem('token')
            localStorage.removeItem('userId')
            router.replace({name: 'loginForm'})
        }
    },
    getters: {
        isAuthenticated(state){
            return state.idToken !== null
        },
        loggedAs(state){
            return state.firstName
        },
        getErrorMsg(state){
            return state.errorMsg
        }
    }
})