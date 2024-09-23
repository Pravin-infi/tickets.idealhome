<template>
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between p-4 pb-1 bg-white border-bottom-0">
            <div>
                <h1 class="fw-bold h2">{{ trans('em.manage') }} {{ trans('em.review') }}</h1>
            </div>
        </div>  

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6" >
                    <div class="form-group">
                        <label class="form-label" for="exampleFormControlSelect1">{{ trans('em.events') }}</label>
                        <select class="form-control" v-model="event_id" @change="getReviews()">
                            
                            <option :value="event.id" v-for="(event, index) in events" :key="index" >{{event.title}}</option>
                            
                        </select>
                    </div>
                </div>

                <div class="col-md-6" v-if="is_admin > 0">
                    <label class="form-label">  {{ trans('em.organiser') }} {{ trans('em.events') }}</label>
                    <div>
                        <v-select 
                            label="name" 
                            class="style-chooser" 
                            :placeholder="trans('em.search_organiser')+' '+trans('em.email')+'/'+trans('em.name')"
                            v-model="organizer" 
                            :required="organizer" 
                            :filterable="false" 
                            :options="organizers" 
                            @search="onSearch" 
                        ><div slot="no-options">{{ trans('em.organiser_not_found') }} </div></v-select>
                    </div>

                    <span v-show="errors.has('organizer')" class="help text-danger">{{ errors.first('organizer') }}</span>
                </div>
                
            </div>
        </div>

        <div class="table-responsive">
            <table class="table text-wrap table-hover">
                <thead class="table-light text-nowrap">
                    <tr>
                        <th>{{ trans('em.title') }}</th>
                        <th>{{ trans('em.rating') }}</th>
                        <th>{{ trans('em.review') }}</th>
                        <th>{{ trans('em.status') }}</th>
                        <th>{{ trans('em.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(review, index) in reviews" :key="index" >
                        
                        <td class="align-middle" >{{ review.event.title }}</td>
                        <td class="align-middle" >{{ review.rating }}</td>
                        <td class="align-middle" >{{ review.review }}</td>
                        <td class="align-middle" ><span class="badge bg-success text-white">{{ review.status > 0 ? trans('em.publish')  : trans('em.unpublish')}}
                            </span>
                        </td>
                        <td class="align-middle text-nowrap">
                        <button type="button" @click="updateStatus(review.id)" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> {{ trans('em.status') +' '+ trans('em.update') }}</button>
                        </td>
                    </tr>
                    
                </tbody>
            </table>
        </div>

        <div class="px-4 pb-4" v-if="reviews.length > 0">
            <pagination-component v-if="pagination.last_page > 1" :pagination="pagination" :offset="pagination.total" :path="'manage_reviews'" @paginate="getReviews()"></pagination-component>
        </div>
        
    </div>        

</template>

<script>
import PaginationComponent from '../../../../eventmie-pro/resources/js/common_components/Pagination'
import mixinsFilters from '../../mixins.js';
import _ from 'lodash';
import vSelect from "vue-select";

export default {
    props:[
        'events',   
        'page',
        'is_admin'
    ],

    components: {
        PaginationComponent,
        vSelect,
    },
     mixins:[
        mixinsFilters
    ],

    data() {
        return {

            // for edit glist
            reviews            : [],
            event_id           : null,
            pagination: {
                'current_page': 1
            },
            organizers    : [],
            organizer     : null,
            
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
        
        // get all glist with pagination
        getReviews() {
            axios.get(route('manage_reviews.index')+'?page='+this.current_page+'&event_id='+this.event_id+'&organizer_id='+this.organizer.id)
            .then(res => {
                
                if(res.data.status){
                    this.reviews  = res.data.manage_reviews.data;

                    this.pagination = {
                        'total' : res.data.manage_reviews.total,
                        'per_page' : res.data.manage_reviews.per_page,
                        'current_page' : res.data.manage_reviews.current_page,
                        'last_page' : res.data.manage_reviews.last_page,
                        'from' : res.data.manage_reviews.from,
                        'to' : res.data.manage_reviews.to,
                        'links' : res.data.manage_reviews.links,
                    };
                }else{
                    this.reviews  = [];
                }
            })
            .catch(error => {
                
            });
        },

        // get organizers

        getOrganizers(loading, search = null){
            
            this.$parent.page = 1;
            this.$router.push({ query: Object.assign({}, this.$route.query, { page: 1   }) }).catch(()=>{});

            if(this.is_admin <= 0)
                return true;

            var postUrl     = route('eventmie.get_organizers');
            var _this       = this;
            axios.post(postUrl,{
                'search' :search,
            }).then(res => {
                
                var promise = new Promise(function(resolve, reject) { 
                    
                    if(res.data.organizers.length > 0)
                        _this.organizers = res.data.organizers
                    
                    if(_this.organizer == null)
                        _this.organizer  = res.data.organizers[0];

                    resolve(true);
                }) 
                
                promise 
                    .then(function(successMessage)  { 
                        // CUSTOM
                        _this.getReviews();
                        // CUSTOM
                    }, function(errorMessage) { 
                    //error handler function is invoked 
                        console.log(errorMessage); 
                    }) 
                    
            })
            .catch(error => {
                let serrors = Vue.helpers.axiosErrors(error);
                if (serrors.length) {
                    this.serverValidate(serrors);
                }
            });

            
        },

        // v-select methods
        onSearch(search, loading) {
            loading(true);
            this.searchOrganizers(loading, search, this);
        },

        // v-select methods
        searchOrganizers: _.debounce((loading, search, vm) => {
            
            if(search.length > 0){
                vm.getOrganizers(loading, search);
                loading(false);
            }    
            else
                loading(false);    
            
        }, 150),


        //set data
        setData(){
            if(this.events.length > 0 ){
            
                this.event_id  = this.events[0].id;

                this.organizer = this.events[0].user; 

                this.getReviews();
            } 
        },

        // update status route
        updateStatus(id = null){
            axios.post(route('manage_reviews.update'),{
                review_id    : id,
                event_id     : this.event_id,
                organizer_id : this.organizer.id 
            
            })
            .then(res => {
                
                if(res.data.status)
                {
                    this.showNotification('success', trans('em.review')+' '+trans('em.saved')+' '+trans('em.successfully'));
                    // reload page   
                    setTimeout(function() {
                        location.reload(true);
                    }, 1000);
                }
            })
            .catch(error => {
                let serrors = Vue.helpers.axiosErrors(error);
                if (serrors.length) {
                    this.serverValidate(serrors);
                }
            });
            
        },

        // show server validation errors
        serverValidate(serrors) {
            this.$validator.validateAll().then((result) => {
                this.$validator.errors.add(serrors);
            });
        },
    },

    mounted(){
        this.setData();
    },

    watch : {
        event_id: function () {
            this.$router.push({ query: Object.assign({}, this.$route.query, {  page: 1 }) }).catch(()=>{});

        },

        organizer : function (newValue = null, oldValue = null) {
            
            this.getOrganizers();     

            if(this.organizer == null)
                this.organizer  = this.organizers[0];

        },
    }    
}
</script>