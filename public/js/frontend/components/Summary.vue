<template>
    <div id="summary" class="container">

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
                    <button type="submit" class="btn btn-default">Filter</button>
                    <button @click="onExport" type="button" class="btn btn-default">Export</button>
                    <select v-model="language">
                        <option value="en_US">English</option>
                        <option value="hu_HU">Hungarian</option>
                        <option value="de_DE">German</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <table class="today-workings">
                        <caption>Working times</caption>
                        <tr>
                            <th class="th-name">Description</th>
                            <th class="th-date">From</th>
                            <th class="th-date">To</th>
                            <th class="th-hours">Hours</th>
                            <th class="th-action">Project</th>
                            <th class="th-invoice">Invoice</th>
                        </tr>
                        <tr v-for="(wtime, index) in wtimes">
                            <td>{{ wtime.description }}</td>
                            <td>{{ wtime.from_hour }}:{{ wtime.from_min }}</td>
                            <td>{{ wtime.to_hour }}:{{ wtime.to_min }}</td>
                            <td>{{ wtime.hours }}</td>
                            <td class="td-action">{{ wtime.name }}</td>
                            <td>{{ wtime.invoice_no }}</td>
                        </tr>
                        <tr class="summary">
                            <td>Summary:</td>
                            <td></td>
                            <td></td>
                            <td>{{ summaryHours }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </table>
                    <div v-if="showInvoicing">
                        <input placeholder="Invoice number" type="text" v-model="invoice_number" />
                        <button @click="onInvoicing" type="button">Invoicing</button>
                    </div>
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
                projects: [],
                wtimes: [],
                summaryHours: 0,
                language: 'en_US',
                invoice_number: ''
            }
        },
        computed:{
            showInvoicing(){
                var show = true
                for(var i=0;i<this.wtimes.length ;i++){
                    if(this.wtimes[i].invoice_no != ''){
                        show = false
                    }
                }

                return this.wtimes.length > 0 && show
            }
        },
        methods:{
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
            },
            getWorkingTimes(){
                var dates = this.getDates()

                if(dates.from != '' && dates.to != ''){
                    axios.get('/api/workingtime/getFiltered/' + dates.from + '/' + dates.to + '/' + this.project_id)
                            .then(res => {
                                this.wtimes = []
                                this.summaryHours = 0
                                for(var i=0;i< res.data.result.length;i++){
                                    this.summaryHours += parseFloat(res.data.result[i].hours)
                                    this.wtimes.push(res.data.result[i])
                                }
                            })
                            .catch(error => {
                                console.log(error)
                            })
                }
            },
            onSubmit(){
                this.getWorkingTimes();
            },
            onExport(){
                console.log('Export')
                var dates = this.getDates()

                if(dates.from != '' && dates.to != '') {
                    axios('/api/workingtime/export/' + dates.from + '/' + dates.to + '/' + this.project_id + '/' +
                            this.language + '/pdf', {
                        method: 'GET', responseType: 'blob'
                    })
                            .then(response => {
                                /*
                                const file = new Blob([response.data], {type: 'application/pdf'})
                                const fileUrl = URL.createObjectURL(file)
                                window.open(fileUrl)
                                */
                                const url = window.URL.createObjectURL(new Blob([response.data]))
                                const link = document.createElement('a');
                                link.href = url;
                                link.setAttribute('download', 'summary.pdf');
                                document.body.appendChild(link);
                                link.click();
                            })
                            .catch(error => {
                                console.log(error)
                            })
                }
            },
            onInvoicing(){
                var ids = []
                for(var i=0;i<this.wtimes.length;i++){
                    ids.push(this.wtimes[i].id)
                }
                axios.post('/api/workingtime/attachInvoice', {
                    wt_ids: ids,
                    invoice_no: this.invoice_number
                }).then(response => {
                    this.getWorkingTimes();
                }).catch(error => {
                    console.log(error)
                })
            },
            getDates(){
                var fromISO = '';
                var toISO = '';
                if(this.date_from != ''){
                    fromISO = this.date_from.toISOString().substring(0,10) + ' ' + this.hour_from + ':' + this.min_from + ':00'
                }
                if(this.date_to != ''){
                    toISO = this.date_to.toISOString().substring(0,10) + ' ' + this.hour_to + ':' + this.min_to + ':00'
                }
                return {from: fromISO, to: toISO}
            }
        },
        mounted(){
            this.getAllActiveProject()
        },
        components: {
            Datepicker
        }
    }
</script>

<style>
    #summary .today-workings, #summary .project{
        width: 100%;
    }
    #summary .summary{
        border-top: 1px solid;
    }
    #summary .project-inputs td{
        padding-bottom: 5px;
    }
</style>