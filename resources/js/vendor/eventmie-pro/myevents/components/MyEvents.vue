<template>
<div>

    <div class="card shadow-sm border-0">

        <div class="card-header d-flex justify-content-between flex-wrap p-4 bg-white border-bottom-0">
            <div>
                <h1 class="mb-0 fw-bold h2">{{ trans('em.myevents') }}</h1>
            </div>
            <div>

                <a class="btn btn-primary me-1 mb-1" :href="createEvent()"><span><i class="fas fa-calendar-plus"></i> {{ trans('em.create_event') }}</span></a>

                <!-- CUSTOM -->
                <button type="button" class="btn btn-secondary mb-1" @click="setOrganizerId()"><span><i class="fas fa-user-plus"></i> {{ trans('em.create') }} {{ trans('em.sub_organizer') }}</span></button>
                <create-users v-if="organizer_id > 0" :organizer_id="organizer_id" :organiser_id=" organizer != null ? organizer.id : null"></create-users>
                <!-- CUSTOM -->

            </div>
        </div>

        <div class="card-header  px-4 bg-white border-bottom-0">
            <!-- CUSTOM -->
            <div v-if="is_admin > 0">


                <!-- it is display in create case and when organiser_id is null -->
                <label class="form-label" >  {{ trans('em.organiser') }} {{ trans('em.events') }}</label>
                <v-select
                    label="name"
                    class="style-chooser"
                    :placeholder="trans('em.search_organiser')+' '+trans('em.email')+'/'+trans('em.name')"
                    v-model="organizer"
                    :required="organizer"
                    :filterable="false"
                    :options="organizers"
                    @search="onSearch"
                >
                <div slot="no-options">{{ trans('em.organiser_not_found') }} </div></v-select>
                <span v-show="errors.has('organizer')" class="help text-danger">{{ errors.first('organizer') }}</span>



            </div>

            <div class="row">

                <div class="col-md-3 mb-3">
                    <label class="form-label" for="exampleFormControlSelect1">{{ trans('em.search') }} {{ trans('em.any') }}</label>
                    <input class="form-control" type="text" v-model="search" :placeholder="trans('em.search')" @keyup="resetPagination()">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label" for="exampleFormControlSelect1">{{ trans('em.show') }}</label>
                    <select class="form-control" v-model="length" @change="resetPagination()">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                    </select>
                </div>

            </div>
             <!-- CUSTOM -->

        </div>

        <div class="table-responsive">

            <table class="table text-wrap">
                <thead class="table-light text-nowrap">
                    <tr>
                        <!-- CUSTOM  -->
                        <th v-for="column in columns" :key="column.name" @click="sortBy(column.name)"

                            :class="[
                                sortKey === column.name ? (sortOrders[column.name] > 0 ? 'sorting_asc' : 'sorting_desc') : 'sorting',
                                column.hide ? 'hidden-xs' : '',
                            ]"
                            style="cursor:pointer;">
                            {{column.label}} <i class="fa fa-sort" aria-hidden="true"></i>
                        </th>

                        <th class="hidden-xs">{{ trans('em.actions') }}</th>
                        <!-- CUSTOM -->
                    </tr>
                </thead>
                <tbody>
                    <!-- CUSTOM -->
                    <!-- <tr v-for="(event, index) in events" :key="index" > -->
                        <tr v-for="(event, index) in paginatedEvents" :key="index" >
                    <!-- CUSTOM -->
                        <td class="align-middle" :data-title="trans('em.event')">
                            <div class="d-flex align-items-center">
                                <a :href="eventSlug(event.slug)">
                                    <img :src="'/storage/'+event.poster" :alt="event.title" class=" rounded img-4by3-md ">
                                </a>
                                <div class="ms-3 lh-1">
                                    <h5 class="mb-1">
                                        <a class="text-inherit text-wrap" :href="eventSlug(event.slug)">{{ event.title }}</a>
                                    </h5>
                                    <small class="text-success strong" v-if="event.count_bookings > 0"><i class="fas fa-bolt"></i> {{ event.count_bookings }} {{ trans('em.bookings') }}</small>
                                    <small class="text-muted strong" v-else><i class="fas fa-hourglass"></i> {{ event.count_bookings }} {{ trans('em.bookings') }}</small>
                                    <small v-if="event.is_private > 0" class="text-primary strong" >&nbsp;&nbsp; <i class="fas fa-lock"></i> {{ trans('em.private_event') }}</small>

                                </div>
                            </div>
                        </td>
                        <td class="align-middle text-nowrap" :data-title="trans('em.start_date')">
                            <small class="text-muted">
                                {{ changeDateFormat(userTimezone(event.start_date+' '+event.start_time, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD'), 'YYYY-MM-DD') }}
                                {{ userTimezone(event.start_date+' '+event.start_time, 'YYYY-MM-DD HH:mm:ss').format(date_format.vue_time_format) }} {{ showTimezone()  }}
                            </small>
                            <br>
                            <small class="text-muted" v-if="userTimezone(event.start_date+' '+event.start_time, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD') <= userTimezone(event.end_date+' '+event.end_time, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD')" :data-title="trans('em.end_date')">
                                {{ changeDateFormat(userTimezone(event.end_date+' '+event.end_time, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD'), 'YYYY-MM-DD') }}
                                {{ userTimezone(event.end_date+' '+event.end_time, 'YYYY-MM-DD HH:mm:ss').format(date_format.vue_time_format) }} {{  showTimezone()  }}
                            </small>
                            <small class="text-muted" v-else :data-title="trans('em.end_date')">
                                {{ changeDateFormat(userTimezone(event.start_date+' '+event.start_time, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD'), 'YYYY-MM-DD') }}
                                {{ userTimezone(event.end_date+' '+event.end_time, 'YYYY-MM-DD HH:mm:ss').format(date_format.vue_time_format) }} {{  showTimezone()  }}
                            </small>
                        </td>
                        <td class="align-middle" :data-title="trans('em.repetitive')"><span class="badge" :class="event.repetitive ? 'bg-success' : 'bg-danger'">{{ event.repetitive ? trans('em.yes') : trans('em.no') }}</span></td>
                        <td class="align-middle" :data-title="trans('em.payment_frequency')"><span class="badge bg-primary">{{ event.merge_schedule ? trans('em.monthly_weekly') : trans('em.full_advance') }} </span></td>
                        <td class="align-middle" :data-title="trans('em.publish')"><span class="badge" :class="event.publish ? 'bg-success' : 'bg-danger'">{{ event.publish ? trans('em.published') : trans('em.unpublished') }}</span></td>
                        <td class="align-middle" :data-title="trans('em.status')"><span class="badge" :class="event.status ? 'bg-success' : 'bg-danger'">{{ event.status ? trans('em.enabled') : trans('em.disabled') }}</span></td>

                        <!-- CUSTOM -->
                        <td class="align-middle" :data-title="trans('em.action')">

                             <div class="custom-dropdown " v-on:click="isOpen = isOpen === index ? null : index">
                              <a class="btn btn-primary"  href="javascript:void(0)" role="button" id="courseDropdown2">
                                    <i class="fas fa-ellipsis-vertical" ></i>
                            </a>
                                    <ul  class="dropdownmenu shadow-lg" v-if="isOpen === index">
                                                <li>
                                                <a :class="{ 'disabled' : event.count_bookings < 1 }" class="btn  btn-dark btn-sm" :href="exportAttendies(event.slug, event.count_bookings)"><i class="fas fa-file-csv"></i> {{ trans('em.export_attendees') }}</a>
                                                </li>

                                                <li>
                                                <a class="btn btn-primary btn-sm" :href="eventEdit(event.slug)"><i class="fas fa-edit"></i> {{ trans('em.edit')+' '+trans('em.event') }}</a>
                                                </li>
                                                <li>
                                                    <a class="btn btn-sm btn-dark" :href="cloneEvent(event.slug)"><i class="fas fa-clone"></i> {{ trans('em.clone')+' '+trans('em.event') }}</a>
                                                </li>
                                                <li >
                                                <a class="btn btn-sm btn-primary" href="javascript:void(0)" @click="() => {event_id = event.id}"><i class="fas fa-lock"></i> {{ trans('em.private_event')}}</a>
                                                </li>
                                                <li>
                                                    <a class="btn btn-sm btn-dark" href="javascript:void(0)" @click="s_event_id = event.id"><i class="fas fa-paper-plane"></i> {{ trans('em.add')+' '+ trans('em.sub_organizers') }}</a>
                                                </li>
                                                <li>
                                                <a class="btn btn-sm btn-primary" href="javascript:void(0)" @click="g_event_id = event.id"><i class="fas fa-plus"></i> {{ trans('em.add')+' '+trans('em.to')+' '+trans('em.guestlist') }}</a>
                                                </li>
                                                <li>
                                                    <a class="btn btn-sm btn-dark" :href="route('voyager.export_sales_report',{'export_event_id' : event.id} )" ><i class="fas fa-plus"></i> {{ trans('em.export_sales_report') }}</a>
                                                </li>
                                    </ul>
                     </div>






                            <event-password v-if="event_id > 0 && event.id == event_id" :event_id="event_id" :event="event"></event-password>

                            <add-to-glist v-if="g_event_id > 0 && event.id == g_event_id" :g_event_id ="g_event_id"
                            :organiser_id="organizer != null ? organizer.id : null"
                            ></add-to-glist>

                            <sub-organizers
                                v-if="s_event_id == event.id"
                                :event_id="s_event_id"
                                :is_admin="is_admin"
                                :sub_organizers="event.sub_organizers"
                                :organiser_id=" organizer != null ? organizer.id : null"
                            ></sub-organizers>

                        </td>
                        <!-- CUSTOM -->

                    </tr>
                </tbody>
            </table>
        </div>
        <div class="px-4 pb-4" v-if="events.length > 0">
            <pagination-component v-if="pagination.last_page > 1" :pagination="pagination" :offset="pagination.total"  @paginate="getMyEvents()"></pagination-component>
        </div>
    </div>

</div>
</template>

<script>

import PaginationComponent from '../../../../../../eventmie-pro/resources/js/common_components/Pagination'
import mixinsFilters from '../../../../mixins.js';

//CUSTOM
import vSelect from "vue-select";
import _ from 'lodash';
import EventPassword from './custom/EventPassword';
import SubOrganizers from './custom/SubOrganizers.vue';
import CreateUsers   from './custom/CreateUsers.vue';
import AddToGlist   from  '../../../../myguests/components/glists/AddToGlist'
//CUSTOM


export default {
    props: [
        // pagination query string
        'page',
        'category',
        // CUSTOM
        'is_admin',
        'date_format'
        // CUSTOM
    ],

    components: {
        PaginationComponent,
        // CUSTOM
        vSelect,
        EventPassword,
        SubOrganizers,
        CreateUsers,
        AddToGlist,
        // CUSTOM
    },

    mixins:[
        mixinsFilters
    ],

    data() {
          //CUSTOM
        let sortOrders = {};
        let columns = [

            {label: trans('em.event'), name: 'title'  , hide: false},
            {label: trans('em.timings'), name: 'start_date' , hide: true},
            {label: trans('em.repetitive'), name: 'repetitive' , hide: true},
            {label: trans('em.payment_frequency') , name: 'merge_schedule' , hide: true},
            {label: trans('em.publish') , name: 'publish' , hide: true},
            {label: trans('em.status') , name: 'status' , hide: true},



        ];

        columns.forEach((column) => {
            sortOrders[column.name] = -1;
        });
        //CUSTOM
        return {
             isOpen: false,
            events           : [],
            pagination: {
                'current_page': 1
            },
            moment           : moment,
            // CUSTOM
            organizer_id  : 0,
            organizers    : [],
            organizer     : null,
            event_id      : 0,

            columns    : columns,
            sortKey    : 'created_at',
            sortOrders : sortOrders,
            length     : 10,
            search     : '',

            g_event_id    : 0,
            s_event_id    : 0,
            route : route

            // CUSTOM
        }
    },

    computed: {
        current_page() {
            // get page from route
            if(typeof this.page === "undefined")
                return 1;

            return this.page;
        },


         //CUSTOM


        filteredEvents() {
            let events = this.events;
            if (this.search) {
                events = events.filter((row) => {
                    return Object.keys(row).some((key) => {
                        return String(row[key]).toLowerCase().indexOf(this.search.toLowerCase()) > -1;
                        })
                });
            }
            let sortKey = this.sortKey;

            let order = this.sortOrders[sortKey] || 1;
            if (sortKey) {
                events = events.slice().sort((a, b) => {
                let index = this.getIndex(this.columns, 'name', sortKey);
                        a = String(a[sortKey]).toLowerCase();
                        b = String(b[sortKey]).toLowerCase();
                        if(this.columns[index] !== undefined){
                            if (this.columns[index].type && this.columns[index].type === 'date') {
                                return (a === b ? 0 : new Date(a).getTime() > new Date(b).getTime() ? 1 : -1) * order;
                            } else if (this.columns[index].type && this.columns[index].type === 'number') {
                                return (+a === +b ? 0 : +a > +b ? 1 : -1) * order;
                            } else {
                                return (a === b ? 0 : a > b ? 1 : -1) * order;
                            }
                        }
                });
            }
            return events;
        },

        paginatedEvents() {

            return this.filteredEvents;
        }
        //CUSTOM
    },
    methods: {

        // get all events
        getMyEvents() {
            // axios.get(route('eventmie.myevents')+'?page='+this.current_page)
            //CUSTOM
            var organiser_id = this.organizer != null ? this.organizer.id : null;

            // axios.get(route('eventmie.myevents')+'?page='+this.current_page)

            axios.get(route('eventmie.myevents')+'?page='+this.current_page+'&organiser_id='+organiser_id+'&length='+this.length+'&search='+this.search)
            //CUSTOM
            .then(res => {

                this.events  = res.data.myevents.data;

                this.pagination = {
                    'total' : res.data.myevents.total,
                    'per_page' : res.data.myevents.per_page,
                    'current_page' : res.data.myevents.current_page,
                    'last_page' : res.data.myevents.last_page,
                    'from' : res.data.myevents.from,
                    'to' : res.data.myevents.to,
                    'links' : res.data.myevents.links,
                };
            })
            .catch(error => {

            });
        },

        // edit myevents
        eventEdit(event_id) {
            return route('eventmie.myevents_form', {id: event_id});
        },

        // create newevents
        createEvent() {
            return route('eventmie.myevents_form');
        },

        // return route with event slug
        eventSlug(slug){
            return route('eventmie.events_show',[slug]);
        },

        // ExportAttendies
        exportAttendies(event_slug = null, event_bookings = 0){
            if(event_slug != null && event_bookings > 0)
                return route('eventmie.export_attendees', [event_slug]);

        },
        //CUSTOM
        // return route with event slug
        cloneEvent(slug){
            return route('clone_event',[slug]);
        },

        // SET ORGANIZER ID ON BUTTON CLICK
        setOrganizerId(){
            this.organizer_id = organizer_id;
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
                        _this.getMyEvents();
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

        sortBy(key) {
            this.sortKey = key;
            this.sortOrders[key] = this.sortOrders[key] * -1;
        },
        getIndex(array, key, value) {
            return array.findIndex(i => i[key] == value)
        },

        // reset pagination
        resetPagination: _.debounce(function(){
            this.my_page = 1;

            this.$router.push({ query: Object.assign({}, this.$route.query, {  page: 1 }) }).catch(()=>{});


            this.getMyEvents();
        }, 1000),

        expandTableRow: function (e) {
            var element = e.target;
            var expand_table = document.getElementById('table-expand-'+element.id);
            expand_table.classList.toggle('hidden');
            if(element.innerText == trans('em.show_more')) {
                // expand
                element.innerText = trans('em.show_less');
            } else {
                // collapse
                element.innerText = trans('em.show_more');
            }
        },

        //CUSTOM
    },
    mounted() {
        //CUSTOM
        // this.getMyEvents();
         if(this.is_admin > 0){
            this.getOrganizers();
        }else{
            this.getMyEvents();
        }

        //CUSTOM
    },


    //CUSTOM
    watch: {
        organizer : function (newValue = null, oldValue = null) {

            this.getOrganizers();

            if(this.organizer == null)
                this.organizer  = this.organizers[0];

        },
    }
    //CUSTOM
}
</script>
<style>

</style>