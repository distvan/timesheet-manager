<template>
    <div id="project" class="container">
        <form @submit.prevent="onSubmit" action="#">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group form-group-sm">
                        <label for="project_name" class="control-label">Project name</label>
                        <input type="text" class="form-control" id="project_name" placeholder="Name" v-model="name">
                    </div>
                    <div class="form-group form-group-sm">
                        <label for="project_description" class="control-label">Project description</label>
                        <textarea v-model="description" id="project_description" class="form-control" placeholder="Details..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-default">Submit</button>
                    <button @click="onClear" type="reset" class="btn btn-default">Clear</button>
                </div>
                <div class="col-md-6">
                    <table>
                        <tr>
                            <th class="th-name">Name</th>
                            <th class="th-active">Active</th>
                            <th class="th-action">Action</th>
                        </tr>
                        <tr v-for="(project, index) in projects">
                            <td>{{ project.name }}</td>
                            <td class="td-active">
                                <input @click="onActive(index)"
                                       type="checkbox"
                                       v-model="projects[index].active" :id="project.id" />
                            </td>
                            <td class="td-action"><a @click="onModify(index)" href="#">Modify</a></td>
                        </tr>
                    </table>
                </div>
            </div>
        </form>
    </div>
</template>

<script type="text/ecmascript-6">
    import axios from 'axios'

    export default {
        data(){
            return {
                id: '',
                name: '',
                description: '',
                projects: []
            }
        },
        methods: {
            onSubmit(){
                if(this.id == ''){
                    axios.post('/api/project/add', {name: this.name,
                        description: this.description, active: 1})
                            .then(res => {
                                this.onClear();
                                this.getAllProject();
                            })
                            .catch(error => {console.log(error)})
                }else{
                    axios.post('/api/project/modify/' + this.id, {name: this.name,
                        description: this.description})
                            .then(res => {
                                this.onClear();
                                this.getAllProject();
                            })
                            .catch(error => {console.log(error)})
                }
            },
            onActive(idx){
                var current = !this.projects[idx].active
                var val = current ? 1 : 0
                axios.post('/api/project/setStatus/' + this.projects[idx].id + '/' + val)
                        .then(res => {})
                        .catch(error => {console.log(error)})
            },
            onModify(idx){
                this.id = this.projects[idx].id
                this.name = this.projects[idx].name
                this.description = this.projects[idx].description
            },
            onClear(){
                this.id = ''
                this.name = ''
                this.description = ''
            },
            getAllProject(){
                axios.get('/api/project/getAll/' + this.$store.state.userId)
                        .then(res => {
                            this.projects = res.data.result
                        })
                        .catch(error => {
                            console.log(error)
                        })
            }
        },
        mounted(){
            this.getAllProject();
        }
    }

</script>

<style>
    #project table{
        width: 100%;
    }
    #project table th{
        border-bottom: 1px solid;
    }
    #project table .th-action, #project table .td-action, #project table .th-active, #project table .td-active {
        text-align: right;
    }
</style>