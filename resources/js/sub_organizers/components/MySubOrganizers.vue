<template>
    <div class="card shadow-sm border-0">

        <div class="card-header d-flex justify-content-between p-4 pb-1 bg-white border-bottom-0">
            <div>
                <h1 class="fw-bold h2">{{ trans('em.manage') }} {{ trans('em.sub_organizers') }}</h1>
            </div>
        </div>  

        <div class="card-body p-0">
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table text-wrap table-hover">
                            <thead class="table-light text-nowrap">
                                <tr>
                                    <th>{{ trans('em.name') }}</th>
                                    <th>{{ trans('em.email') }}</th>
                                    <th>{{ trans('em.role') }}</th>
                                    <th>{{ trans('em.delete') }}</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(sub_organizer, index) in sub_organizers" :key="index" >
                                    
                                    <td>{{ sub_organizer.name }} </td>
                                    <td>{{ sub_organizer.email }} </td>
                                    <td>{{ sub_organizer.role_name }} </td>
                                
                                    <td class="text-nowrap">
                                        <a href="javascript:void(0)" class="btn btn-sm btn-primary" @click="editSubOrganizer(sub_organizer)"><i class="fas fa-edit"></i> {{ trans('em.edit') }}</a>
                                    </td>
                                </tr>
                            
                            </tbody>
                        </table>
                    </div>

                    <edit-sub-organizer v-if="edit_sub_organizer > 0" :sub_organizer="sub_organizer" :edit_sub_organizer="edit_sub_organizer"></edit-sub-organizer>

                    <div class="px-4 pb-4" v-if="sub_organizers.length > 0">
                        <pagination-component v-if="pagination.last_page > 1" :pagination="pagination" :offset="pagination.total"  @paginate="getMySubOrganizers()"></pagination-component>
                    </div>
                </div>
                
            </div>

        </div>
    </div>
</template>

<script>

import PaginationComponent from '../../../../eventmie-pro/resources/js/common_components/Pagination'

import mixinsFilters from '../../../../eventmie-pro/resources/js/mixins.js';

import { VueRouter } from 'vue-router';

// import component for vue routes

import EditSubOrganizer from './EditSubOrganizer.vue';


export default {
    props: [
        // pagination query string
        'page',
        'category'
    ],

    components: {
        PaginationComponent,
        EditSubOrganizer,
        
    },

          
    mixins:[
        mixinsFilters
    ],

    data() {
        return {
            sub_organizers           : [],
            sub_organizer            : [],

            pagination: {
                'current_page': 1
            },
            
            edit_sub_organizer        : 0,

            
        }
    },
    
    computed: {
        current_page() {
            // get page from route
            if(typeof this.page === "undefined")
                return 1;
            
            return this.page;
        },
    },
    methods: {
        
        // get all sub_organizers
        getMySubOrganizers() {
            axios.get(route('get_sub_organizers')+'?page='+this.current_page)
            .then(res => {
                
                this.sub_organizers  = res.data.sub_organizers.data;

                this.pagination = {
                    'total' : res.data.sub_organizers.total,
                    'per_page' : res.data.sub_organizers.per_page,
                    'current_page' : res.data.sub_organizers.current_page,
                    'last_page' : res.data.sub_organizers.last_page,
                    'from' : res.data.sub_organizers.from,
                    'to' : res.data.sub_organizers.to,
                    'links' : res.data.sub_organizers.links,
                };
            })
            .catch(error => {
                
            });
        },

        // // edit myevents
        editSubOrganizer(sub_organizer = []) {
            this.sub_organizer = sub_organizer; 
            this.edit_sub_organizer  = 1;    
        },

        
    },
    mounted() {
        this.getMySubOrganizers();
    }
}
</script>
<style>

</style>