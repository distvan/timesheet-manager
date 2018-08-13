<template>
    <div id="working-time" class="container">
        <form @submit.prevent="onSubmit" action="#">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group form-group-sm">
                        <table class="project-inputs">
                            <tr>
                                <td>
                                    <label class="control-label">Project:</label>
                                </td>
                                <td colspan="5">
                                    <select class="project" v-model="project_id">
                                        <option value="0">Please select a project!</option>
                                        <option v-for="(project, index) in projects" :value="project.id">{{ project.name }}</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="control-label">From date:</label>
                                </td>
                                <td>
                                    <datepicker v-model="date_from" format="yyyy-MM-dd"></datepicker>
                                </td>
                                <td>
                                    <label for="hour_from" class="control-label">From hour:</label>
                                </td>
                                <td>
                                    <select id="hour_from" v-model="hour_from">
                                        <option v-for="idx in 24" :value="idx-1">{{ idx-1 }}</option>
                                    </select>
                                </td>
                                <td>
                                    <label for="min_from" class="control-label">From minutes:</label>
                                </td>
                                <td>
                                    <select id="min_from" v-model="min_from">
                                        <option v-for="idx in [0,15,30,45]" :value="idx">{{ idx }}</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="control-label">To date:</label>
                                </td>
                                <td>
                                    <datepicker v-model="date_to" format="yyyy-MM-dd"></datepicker>
                                </td>
                                <td>
                                    <label for="hour_to" class="control-label">To hour:</label>
                                </td>
                                <td>
                                    <select v-model="hour_to" id="hour_to">
                                        <option v-for="idx in 24" :value="idx-1">{{ idx-1 }}</option>
                                    </select>
                                </td>
                                <td>
                                    <label for="min_to" class="control-label">To minutes:</label>
                                </td>
                                <td>
                                    <select v-model="min_to" id="min_to">
                                        <option v-for="idx in [0,15,30,45]" :value="idx">{{ idx }}</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="form-group form-group-sm">
                        <label for="project_description" class="control-label">Work description</label>
                        <textarea v-model="description" id="project_description" class="form-control" placeholder="Details..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-default">Submit</button>
                    <button @click="onClear" type="reset" class="btn btn-default">Clear</button>
                </div>
                <div class="col-md-6">
                    <table class="today-workings">
                        <caption>Today workings</caption>
                        <tr>
                            <th class="th-name">Name</th>
                            <th class="th-date">From</th>
                            <th class="th-date">To</th>
                            <th class="th-hours">Hours</th>
                            <th class="th-action">Actions</th>
                        </tr>
                        <tr v-for="(wtime, index) in wtimes">
                            <td>{{ wtime.description }}</td>
                            <td>{{ wtime.from_hour }}:{{ wtime.from_min }}</td>
                            <td>{{ wtime.to_hour }}:{{ wtime.to_min }}</td>
                            <td>{{ wtime.hours }}</td>
                            <td class="td-action">
                                <a @click="onDelete(index)" href="#">Delete</a>
                            </td>
                        </tr>
                        <tr class="summary">
                            <td>Summary:</td>
                            <td></td>
                            <td></td>
                            <td>{{ todaySummaryHours }}</td>
                            <td></td>
                        </tr>
                    </table>
                </div>
            </div>
        </form>
    </div>
</template>

<script type="text/ecmascript-6">
    import axios from 'axios'
    import Datepicker from 'vuejs-datepicker'

    export default {
        data(){
            return {
                project_id: '',
                date_from: '',
                hour_from: '',
                min_from: '',
                date_to: '',
                hour_to: '',
                min_to: '',
                description: '',
                idx: '',
                projects: [],
                wtimes: [],
                todaySummaryHours: 0
            }
        },
        methods: {
            onSubmit(){
                var fromISO = '';
                var toISO = '';

                if(this.date_from != ''){
                    fromISO = this.date_from.toISOString().substring(0,10) + ' ' + this.hour_from + ':' + this.min_from + ':00'
                }
                if(this.date_to != ''){
                    toISO = this.date_to.toISOString().substring(0,10) + ' ' + this.hour_to + ':' + this.min_to + ':00'
                }

                if(this.idx == ''){
                    axios.post('/api/workingtime/add', {
                        projectid: this.project_id,
                        datefrom: fromISO,
                        dateto: toISO,
                        description: this.description
                    })
                            .then(res => {
                                this.onClear()
                                this.getTodayWTime()
                            })
                            .catch(error => {
                                console.log(error)
                            })
                }else{

                }
            },
            onDelete(idx){
                if(this.idx == ''){
                    axios.post('/api/workingtime/delete', {
                        id: this.wtimes[idx].id
                    })
                            .then(res => {
                                this.getTodayWTime()
                            })
                            .catch(error => {
                                console.log(error)
                            })
                }
            },
            onClear(){
                this.project_id = ''
                this.date_from = ''
                this.hour_from = ''
                this.min_from = ''
                this.date_to = ''
                this.hour_to = ''
                this.min_to = ''
                this.description = ''
            },
            getTodayWTime(){
                this.todaySummaryHours = 0;
                axios.get('/api/workingtime/getAllToday')
                        .then(res => {
                            this.wtimes = []
                            for(var i=0;i< res.data.result.length;i++){
                                this.wtimes.push(res.data.result[i])
                                this.todaySummaryHours += parseFloat(res.data.result[i].hours)
                            }
                        })
                        .catch(error => {
                            console.log(error)
                        })
            },
            getAllActiveProject(){
                axios.get('/api/project/getAll/' + this.$store.state.userId)
                        .then(res => {
                            for(var i=0;i< res.data.result.length;i++){
                                var active = res.data.result[i].active
                                if(active){
                                    this.projects.push(res.data.result[i])
                                }
                            }
                        })
                        .catch(error => {
                            console.log(error)
                        })
            }
        },
        mounted(){
            this.getAllActiveProject()
            this.getTodayWTime()
        },
        components: {
            Datepicker
        }
    }
</script>

<style>
#working-time .today-workings, #working-time .project{
    width: 100%;
}
#working-time .summary{
    border-top: 1px solid;
}
#working-time .project-inputs td{
    padding-bottom: 5px;
}
</style>