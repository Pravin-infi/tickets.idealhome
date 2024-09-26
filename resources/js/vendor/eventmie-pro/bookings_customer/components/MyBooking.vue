<template>
    <div class="container-fluid">
        <div class="py-5 py-lg-5">

            <div class="row">
                <div class="col-12 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <button type="button" class="btn btn-outline-primary btn-sm" @click="filter_toggle = !filter_toggle"><i class="fas fa-bars"></i></button> {{ trans('em.filters') }}
                        </h4>
                        <div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="reset()"><i class="fas fa-redo"></i> {{ trans('em.reset_filters') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Search -->
            <div class="row mt-3" v-show="filter_toggle" v-if="is_admin > 0">
                
                <!-- CUSTOM -->
                <div class="col-md-4 mb-3">
                    <div class="form-group"> 
                        <label class="form-label" for="exampleFormControlSelect1">{{ trans('em.events') }}</label>
                        <select class="form-control" name="state" v-model="event_id" >
                            <option  value=0>{{ trans('em.all_events') }} </option>
                            <option v-for="(event, index) in events" :key ="index" :value="event.event_id">{{event.event_title}} </option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="form-group">
                        <label class="form-label" for="exampleFormControlSelect1">{{ trans('em.booking_date') }} </label>
                        <date-picker  class="form-control p-2" :shortcuts="shortcuts" v-model="date_range" range :lang="$vue2_datepicker_lang" :placeholder="trans('em.booking_date')" format="YYYY-MM-DD "></date-picker>
                    </div>
                </div>    

                <div class="col-md-4 mb-3">
                    <div class="form-group">
                        <label class="form-label" for="exampleFormControlSelect1">{{ trans('em.search') }} {{ trans('em.any') }}</label>
                        <input class="form-control" type="text" v-model="search" :placeholder="trans('em.search')" @keyup="resetPagination()">
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="form-group">
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

                <div class="col-md-4 mb-3">
                    <div class="form-group">
                        <label class="form-label" for="exampleFormControlSelect1">{{ trans('em.event_date') }} </label>
                        <date-picker  class="form-control p-2" :shortcuts="shortcuts1" v-model="event_date_range" range :lang="$vue2_datepicker_lang" :placeholder="trans('em.event_date')" format="YYYY-MM-DD "></date-picker>
                    </div>
                </div>  
                <!-- CUSTOM -->

            </div>

            <!-- Booking List -->
            <div class="card shadow-sm border-0">
                <div class="card-header p-4 bg-white border-bottom-0"></div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table text-wrap table-hover">
                                <thead class="table-light text-nowrap">
                                    <tr>
                                    <!-- CUSTOM  -->
                                        <th  class="align-middle" v-for="column in columns" :key="column.name" @click="sortBy(column.name)"
                                            :class="[
                                                sortKey === column.name ? (sortOrders[column.name] > 0 ? 'sorting_asc' : 'sorting_desc') : 'sorting',
                                                column.hide ? 'hidden-xs' : '',
                                            ]"
                                            style="cursor:pointer;"
                                            v-if="column.name != 'order_number'"
                                        >
                                                {{ column.label }} <i class="fa fa-sort" aria-hidden="true"></i>
                                        </th>
                                        
                                        <th  class="align-middle" v-if="is_admin > 0">{{ trans('em.expired') }}</th>  
                                        <th class="align-middle hidden-xs">{{ trans('em.actions') }}</th>
                                        <!-- CUSTOM  --> 
                                        
                                    </tr>
                                </thead>
                                <tbody>
                                
                                    <!-- <tr v-for="(booking, index) in bookings" :key="index" > -->
                                            <!-- CUSTOM -->
                                    <tr v-for="(booking, index) in paginatedBookings" :key="index" >
                                    <!-- CUSTOM -->
                                        <td :data-title="trans('em.event')">
                                            <div class="d-flex align-items-center">
                                                <a :href="eventSlug(booking.event_slug)"> 
                                                    <img :src="'/storage/'+booking.event_thumbnail" :alt="booking.event_title" class="rounded img-4by3-md ">
                                                </a>
                                                <div class="ms-3 lh-1">
                                                    <h5 class="mb-1"> 
                                                        <a :href="eventSlug(booking.event_slug)" class="text-inherit text-wrap">{{ booking.event_title }}
                                                                <!-- <small>{{ '('+ booking.event_category +')'}}</small> -->
                                                        </a>
                                                    </h5>
                                                    <p class="text-mute" style="display: none;">
                                                        <small class="text-muted" v-if="booking.event_start_date != booking.event_end_date">
                                                            {{ moment(userTimezone(booking.event_start_date+' '+booking.event_start_time, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD')).format(date_format.vue_date_format) }}
                                                        </small>
                                                        <small class="text-muted" v-else>
                                                            {{ moment(userTimezone(booking.event_start_date+' '+booking.event_start_time,'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD')).format(date_format.vue_date_format) }}
                                                        </small>
                                                        
                                                        <small class="text-muted">
                                                            {{ userTimezone(booking.event_start_date+' '+booking.event_start_time, 'YYYY-MM-DD HH:mm:ss').format(date_format.vue_time_format) }}
                                                        </small>
                                                        <small class="text-muted"> 
                                                            {{ showTimezone() }}
                                                        </small>
                                                    </p>

                                                    <p>
                                                        <small class="text-success fw-bold">{{ trans('em.order_id') }}: #{{ booking.order_number }}</small>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- <td :data-title="trans('em.ticket')">{{ booking.ticket_title }} <strong>{{ ' x '+booking.quantity }}</strong></td> -->
                                        <td class="align-middle" :data-title="trans('em.ticket')"><i class="fas fa-ticket"></i> {{ booking.ticket_title }} <strong>{{ ' x '+booking.quantity }}</strong></td>
                                        <!-- <td :data-title="trans('em.order_total')">{{ booking.net_price+' '+ currency }} </td> -->
                                        <!-- CUSTOM -->
                                        <td class="align-middle" :data-title="trans('em.order_total')" v-if="is_admin > 0">{{ booking.net_price+' '+ booking.currency }} </td>
                                        <td class="align-middle" :data-title="trans('em.reward')" v-if="is_admin > 0">{{ (booking.promocode_reward != null ? booking.promocode_reward : 0 )+' '+ booking.currency }} </td>
                                        <!-- CUSTOM -->
                                        <td class="align-middle" :data-title="trans('em.booked_on')" v-if="is_admin > 0">{{ moment(userTimezone(booking.created_at, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD')).format(date_format.vue_date_format) }} {{ showTimezone()  }}</td>
                                        <td class="align-middle text-capitalize" :data-title="trans('em.payment')" v-if="is_admin > 0">
                                            <span class="badge bg-secondary text-white" v-if="booking.payment_type == 'offline'">
                                                {{ booking.payment_type }} 
                                                <hr class="small p-0 m-0">
                                                <small class="text-small text-white" v-if="booking.is_paid">{{ trans('em.paid') }}</small>
                                                <small class="text-small text-white" v-else>{{ trans('em.unpaid') }}</small>
                                            </span>
                                            <span class="badge bg-success text-white" v-else>{{ booking.payment_type }} <hr class="small p-0 m-0"><small class="text-small">{{ booking.is_paid ? trans('em.paid') : trans('em.unpaid') }}</small></span>
                                        </td>
                                        <td class="align-middle" :data-title="trans('em.checked_in')" v-if="is_admin > 0">
                                            <span class="badge bg-success text-white" v-if="booking.checked_in > 0">
                                                {{ trans('em.yes') }}
                                                <hr class="small p-0 m-0">
                                                <small class="text-small text-white">{{ booking.checked_in +'/'+ booking.quantity }}</small>
                                            </span>
                                            <span class="badge bg-secondary text-white" v-else>{{ trans('em.no') }}</span>
                                        </td>
                                        <td class="align-middle" :data-title="trans('em.status')" v-if="is_admin > 0">
                                            <span class="badge bg-success text-white" v-if="booking.status == 1">{{ trans('em.enabled') }}</span>
                                            <span class="badge bg-secondary text-white" v-else>{{ trans('em.disabled') }}</span>
                                        </td>
                                        <td class="align-middle" :data-title="trans('em.cancellation')" v-if="booking.booking_cancel == 0 && booking.status == 1 && booking.checked_in == 0 && is_admin > 0">
                                            <button type="button" class="btn btn-sm btn-danger" @click="bookingCancel(booking.id, booking.ticket_id, booking.event_id )" 
                                            v-if="disable_booking_cancellation == null"
                                            ><i class="fas fa-ban"></i> {{ trans('em.cancel') }}</button>
                                            <p v-else>{{ trans('em.n/a') }}</p>
                                        </td>
                                        <td class="align-middle" :data-title="trans('em.cancellation')" v-else v-if="is_admin > 0">
                                            <span class="badge bg-secondary text-white" v-if="booking.booking_cancel == 0">{{ trans('em.disabled') }}</span>
                                            <span class="badge bg-warning text-white" v-if="booking.booking_cancel == 1">{{ trans('em.pending') }}</span>
                                            <span class="badge bg-info text-white" v-if="booking.booking_cancel == 2">{{ trans('em.approved') }}</span>
                                            <span class="badge bg-secondary text-white" v-if="booking.booking_cancel == 3">{{ trans('em.refunded') }}</span>
                                        </td>
                                        
                                        <!-- check booking expired or not -->
                                        <td class="align-middle" :data-title="trans('em.expired')" v-if="(moment(booking.event_end_date+' '+booking.event_end_time, 'YYYY-MM-DD HH:mm:ss').tz(Intl.DateTimeFormat().resolvedOptions().timeZone) <= moment().tz(Intl.DateTimeFormat().resolvedOptions().timeZone)) && is_admin > 0">
                                            <span class="badge  bg-danger text-white"> {{trans('em.yes')}} </span>
                                        </td>

                                        <td class="align-middle" :data-title="trans('em.expired')" v-else v-if="is_admin > 0">
                                            <span class="badge  bg-primary text-white"> {{trans('em.no')}} </span>
                                        </td>
                                        <!-- check booking expired or not -->

                                        <td class="align-middle text-nowrap" :data-title="trans('em.actions')">
                                            <div v-if="hide_ticket_download == null">
                                                <a v-if="booking.is_paid == 1 && booking.status == 1" class="btn btn-sm btn-success mb-2" :href="downloadURL(booking.id, booking.order_number)"><i class="fas fa-download"></i> {{trans('em.ticket')}}</a>
                                                <!-- CUSTOM 
                                                <a v-if="booking.is_paid == 1 && booking.status == 1" class="btn btn-sm btn-success mb-2" :href="downloadInvoice(booking.id)"><i class="fas fa-download"></i> {{trans('em.invoice')}}</a>
                                                 CUSTOM -->
                                                <span class="badge bg-secondary text-white" v-else>
                                                    <small v-if="booking.is_paid == 0 && booking.status == 1" class="text-small text-white">{{ trans('em.unpaid') }}</small>
                                                    <small v-else class="text-small">{{ trans('em.disabled') }}</small>
                                                </span>
                                            </div>

                                            <!-- CUSTOM -->

                                            <div v-if="booking.is_paid == 1 && booking.status == 1"> 
                                                <button type="button" class="btn btn-sm btn-primary mb-2" @click="is_qrcode = 1;qrcode_booking_id = booking.id"><i class="fas fa-qrcode"></i> {{ trans('em.check_in')}}</button>
                                                <qr-code v-if="is_qrcode > 0 && qrcode_booking_id == booking.id" :is_qrcode="is_qrcode"  :qrcode_booking_id="qrcode_booking_id" :order_number="booking.order_number" :booking="booking"></qr-code>
                                            </div>
                                            
                                            <div v-if="hide_google_calendar == null" class="mb-2">
                                                <create-google-event :booking="booking" :date_format="date_format"></create-google-event>
                                            </div>

                                            <div v-if="booking.online_location != null && booking.is_paid == 1 && booking.status == 1"> 
                                                <button type="button" class="btn btn-sm btn-primary mb-2" @click="booking_id = booking.id"><i class="fas fa-tv"></i> {{ trans('em.online') +' '+ trans('em.event') }}</button>
                                                <online-event  v-if="booking_id == booking.id" :online_location="booking.online_location" :booking_id="booking.id" ></online-event>
                                            </div>
                                            <!-- CUSTOM -->
                                            <div v-if="booking.youtube_embed == null && booking.vimeo_embed == null">
                                                <div v-if="booking.online_location != null && booking.is_paid == 1 && booking.status == 1"> 
                                                    <button type="button" class="btn btn-sm btn-primary mb-2" @click="booking_id = booking.id"><i class="fas fa-tv"></i> {{ trans('em.online') +' '+ trans('em.event') }}</button>
                                                    <online-event  v-if="booking_id == booking.id" :online_location="booking.online_location" :booking_id="booking.id" ></online-event>
                                                </div>
                                            </div>
                                            <div v-else>
                                                <div v-if="booking.youtube_embed != null && booking.is_paid == 1 && booking.status == 1"> 
                                                    <button type="button" class="btn btn-sm btn-primary mb-2" @click="embed_code_modal=1;embed_code=booking.youtube_embed;online_location=booking.online_location"><i class="fab fa-youtube"></i> YouTube</button>
                                                </div>
                                                <div v-if="booking.vimeo_embed != null && booking.is_paid == 1 && booking.status == 1"> 
                                                    <button type="button" class="btn btn-sm btn-primary"  @click="embed_code_modal=1;embed_code=booking.vimeo_embed;online_location=booking.online_location"><i class="fab fa-vimeo"></i> Vimeo</button>
                                                </div>
                                            </div>
                                            <!-- CUSTOM -->
                                        </td>
                                    </tr>

                                    <tr v-if="bookings.length <= 0 && is_admin > 0">
                                        <td  colspan="10" class="text-center align-middle">{{ trans('em.no_bookings') }}</td>
                                    </tr>
                            
                                </tbody>
                            </table>
                        </div>    
                    
                        <!-- CUSTOM -->
                        <embed-code  v-if="embed_code_modal > 0" :embed_code="embed_code" :embed_code_modal="embed_code_modal" :online_location="online_location" ></embed-code>
                        <!-- CUSTOM -->

                        <div  class="px-4 pb-4" v-if="bookings.length > 0">
                            <pagination-component v-if="pagination.last_page > 1" :pagination="pagination" :offset="pagination.total" :path="'/mybookings'" @paginate="getMyBookings()">
                            </pagination-component>
                        </div>
                    </div>                   
                </div>
            </div>

        </div>    

    </div>
</template>


<script>

import PaginationComponent from '../../../../../../eventmie-pro/resources/js/common_components/Pagination';
import mixinsFilters from '../../../../mixins.js';
import OnlineEvent from '../../../../../../eventmie-pro/resources/js/bookings_customer/components/OnlineEvent';
import CreateGoogleEvent from './CreateGoogleEvent.vue';
//CUSTOM
import EmbedCode from '../../bookings_organiser/components/custom/EmbedCode';
import _ from 'lodash';
import DatePicker from 'vue2-datepicker';
import QrCode from '../../bookings_organiser/components/custom/QrCode';
//CUSTOM

export default {
    
    mixins:[
        mixinsFilters
    ],

    props: [
        // pagination query string
        'page',
        'is_success',
        'date_format',
        'disable_booking_cancellation',
        'hide_ticket_download',
        'hide_google_calendar',
        
    ],
    
    components: {
        PaginationComponent,
        OnlineEvent,
        CreateGoogleEvent,
         //CUSTOM
        EmbedCode,
        DatePicker,
        QrCode
        //CUSTOM
    },
    
    data() {
          //CUSTOM
        let sortOrders = {};
        let columns = [];

        if (this.is_admin > 0) {
            columns = [
                {label: trans('em.order_id'), name: 'order_number', hide: true },
                {label: trans('em.event'), name: 'event_slug', hide: true },
                {label: trans('em.ticket'), name: 'ticket_title', hide: true},
                {label: trans('em.order')+' '+trans('em.total'), name: 'net_price', hide: true},
                {label: trans('em.reward'), name: 'promocode_reward', hide: true },
                {label: trans('em.booked')+' '+trans('em.on') , name: 'created_at', hide: true},
                {label: trans('em.payment') , name: 'is_paid', hide: true},
                {label: trans('em.checked_in') , name: 'checked_in', hide: true},
                {label: trans('em.status') , name: 'status', hide: true},
                {label: trans('em.cancellation') , name: 'booking_cancel', hide: true},
            ];
        }else{
            columns = [
                {label: trans('em.event'), name: 'event_slug', hide: true },
                {label: trans('em.ticket'), name: 'ticket_title', hide: true},
            ];
        }
        
        columns.forEach((column) => {
            sortOrders[column.name] = -1;
        });
        //CUSTOM
        return {
            bookings : [],
            moment   : moment,
            pagination: {
                'current_page': 1
            },
            currency : null,
            booking_id : 0,
              //CUSTOM
            embed_code_modal : 0,
            embed_code       : null,
            online_location  : null,
             
            columns    : columns,
            sortKey    : 'created_at',
            sortOrders : sortOrders,
            length     : 10,
            search     : '',
            
            date_range : [],
            start_date : '',
            end_date   : '',
            
            events    : [],
            event_id  : 0,

            filter_toggle: false,

            // date shortucts like today, tommorrow
            shortcuts: [
                {
                    text: trans('em.today'),
                    onClick: () => {
                        this.date_range = [moment().toDate(), moment().toDate() ]
                    }
                },
                {
                    text: trans('em.tomorrow'),
                    onClick: () => {
                        this.date_range = [moment().add(1,'day').toDate(), moment().add(1,'day').toDate()]
                    }
                },
                {
                    text: trans('em.this')+' '+trans('em.weekend'),
                    onClick: () => {
                        this.date_range = [moment().endOf("week").toDate(), moment().endOf("week").toDate()]
                    }
                },
                {
                    text: trans('em.this')+' '+trans('em.week'),
                    onClick: () => {
                        this.date_range = [moment().startOf("week").toDate(), moment().endOf("week").toDate()]
                    }
                },
                {
                    text: trans('em.next')+' '+trans('em.week'),
                    onClick: () => {
                        this.date_range = [moment().add(1, 'weeks').startOf("week").toDate(), moment().add(1, 'weeks').endOf("week").toDate()]
                    }
                },
                {
                    text: trans('em.this')+' '+trans('em.month'),
                    onClick: () => {
                        this.date_range = [moment().startOf("month").toDate(), moment().endOf("month").toDate()]
                    }
                },
                {
                    text: trans('em.next')+' '+trans('em.month'),
                    onClick: () => {
                        this.date_range = [moment().add(1, 'months').startOf("month").toDate(), moment().add(1, 'months').endOf("month").toDate()]
                    }
                },
            ],


            // date shortucts like today, tommorrow
            shortcuts1: [
                {
                    text: trans('em.today'),
                    onClick: () => {
                        this.date_range = [moment().toDate(), moment().toDate() ]
                    }
                },
                {
                    text: trans('em.tomorrow'),
                    onClick: () => {
                        this.date_range = [moment().add(1,'day').toDate(), moment().add(1,'day').toDate()]
                    }
                },
                {
                    text: trans('em.this')+' '+trans('em.weekend'),
                    onClick: () => {
                        this.date_range = [moment().endOf("week").toDate(), moment().endOf("week").toDate()]
                    }
                },
                {
                    text: trans('em.this')+' '+trans('em.week'),
                    onClick: () => {
                        this.date_range = [moment().startOf("week").toDate(), moment().endOf("week").toDate()]
                    }
                },
                {
                    text: trans('em.next')+' '+trans('em.week'),
                    onClick: () => {
                        this.date_range = [moment().add(1, 'weeks').startOf("week").toDate(), moment().add(1, 'weeks').endOf("week").toDate()]
                    }
                },
                {
                    text: trans('em.this')+' '+trans('em.month'),
                    onClick: () => {
                        this.date_range = [moment().startOf("month").toDate(), moment().endOf("month").toDate()]
                    }
                },
                {
                    text: trans('em.next')+' '+trans('em.month'),
                    onClick: () => {
                        this.date_range = [moment().add(1, 'months').startOf("month").toDate(), moment().add(1, 'months').endOf("month").toDate()]
                    }
                },
            ],


            is_qrcode         : 0,
            qrcode_booking_id : 0,
            event_date_range : [],
            event_start_date : '',
            event_end_date   : '',
            //CUSTOM
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
        
        filteredBookings() {
            let bookings = this.bookings;
            if (this.search) {
                bookings = bookings.filter((row) => {
                    return Object.keys(row).some((key) => {
                        return String(row[key]).toLowerCase().indexOf(this.search.toLowerCase()) > -1;
                        })
                });
            }
            let sortKey = this.sortKey;

            let order = this.sortOrders[sortKey] || 1;
            if (sortKey) {
                bookings = bookings.slice().sort((a, b) => {
                let index = this.getIndex(this.columns, 'name', sortKey);
                if (index !== -1 && this.columns[index]) {
                        a = String(a[sortKey]).toLowerCase();
                        b = String(b[sortKey]).toLowerCase();
                        if (this.columns[index].type && this.columns[index].type === 'date') {
                            return (a === b ? 0 : new Date(a).getTime() > new Date(b).getTime() ? 1 : -1) * order;
                        } else if (this.columns[index].type && this.columns[index].type === 'number') {
                            return (+a === +b ? 0 : +a > +b ? 1 : -1) * order;
                        } else {
                            return (a === b ? 0 : a > b ? 1 : -1) * order;
                        }
                    } else {
            // Handle cases where the index is invalid or the column type is not defined
                        console.error(`Invalid column index or undefined column type for sortKey: ${sortKey}`);
                        return 0;
                    }
                });
            }
            return bookings;
        },

        paginatedBookings() {
            
            return this.filteredBookings;
        }
        //CUSTOM
    },

    methods:{
          // get all events
        getMyBookings() {
            
             //CUSTOM
            // axios.get(route('eventmie.mybookings')+'?page='+this.current_page)

            if(typeof this.start_date === "undefined") {
                this.start_date     = '';
            }
            if(typeof this.end_date === "undefined") {
                this.end_date     = '';
            }

            if(typeof this.event_start_date === "undefined") {
                this.event_start_date     = '';
            }
            if(typeof this.event_end_date === "undefined") {
                this.event_end_date     = '';
            }

            axios.get(route('eventmie.mybookings')+'?page='+this.current_page+'&event_id='+this.event_id+'&start_date='
                        +this.start_date+'&end_date='+this.end_date+'&length='+this.length+'&search='+this.search+'&event_start_date='+this.event_start_date+'&event_end_date='+this.event_end_date)
            //CUSTOM
            .then(res => {
                this.currency   = res.data.currency;
                this.bookings   = res.data.bookings.data;
                this.pagination = {
                    'total' : res.data.bookings.total,
                    'per_page' : res.data.bookings.per_page,
                    'current_page' : res.data.bookings.current_page,
                    'last_page' : res.data.bookings.last_page,
                    'from' : res.data.bookings.from,
                    'to' : res.data.bookings.to,
                    'links' : res.data.bookings.links,
                };
            })
            .catch(error => {
                
            });
        },

        // cancel my booking
        bookingCancel(booking_id, ticket_id, event_id) {
            this.showConfirm(trans('em.ask_cancel_booking')).then((res) => {
                if(res) {
                    axios.post(route('eventmie.mybookings_cancel'),{
                        booking_id : booking_id,
                        ticket_id  : ticket_id,
                        event_id   : event_id,
                    })
                    .then(res => {
                        if(res.data.status)
                        {
                            this.showNotification('success', trans('em.booking_cancel_success'));
                            this.getMyBookings();
                        }    
                    })
                    .catch(error => {});
                }
            })
        },

        // return route with event slug
        eventSlug(slug) {
            if(slug) {
                return route('eventmie.events_show',[slug]);
            }
        },

        // return route with download URL
        downloadURL(id, order_number) {
            if(id && order_number) {
                return route('eventmie.downloads_index',[id, order_number]);
            }
        },

         //CUSTOM
            
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


            this.getMyBookings();
        }, 1000),

        // get all events
        getMyEvents() {
            axios.get(route('customer_events'))
            .then(res => {
                this.events  = res.data.events;
            })
            .catch(error => {
                
            });
        },

                // searching by date 
        dateRange: function () {
            var is_date_null = 0;
            if(Object.keys(this.date_range).length > 0 )
            {
                // convert date range to server side date
                this.date_range.forEach(function(value, key) {
                    if(value != null) {
                        is_date_null = 1;

                        if(key == 0)
                            this.start_date   =  this.convert_date(value); // convert local start_date to server date then searching by date
                        
                        if(key == 1)
                            this.end_date     =  this.convert_date(value); // convert local end_date to server date then searching by date
                    }
                }.bind(this));
                
                // date reset
                if(is_date_null <= 0){
                    this.start_date  = '';
                    this.end_date    = '';
                }

                this.getMyBookings()
            }
            
        },

        
         // searching by date 
        eventDateRange: function () {
            var is_date_null = 0;
            if(Object.keys(this.event_date_range).length > 0 )
            {
                // convert date range to server side date
                this.event_date_range.forEach(function(value, key) {
                    if(value != null) {
                        is_date_null = 1;

                        if(key == 0)
                            this.event_start_date   =  this.convert_date(value); // convert local start_date to server date then searching by date
                        
                        if(key == 1)
                            this.event_end_date     =  this.convert_date(value); // convert local end_date to server date then searching by date
                    }
                }.bind(this));
                
                // date reset
                if(is_date_null <= 0){
                    this.event_start_date  = '';
                    this.event_end_date    = '';
                }

                this.getMyBookings()
            }
            
        },
       

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

        downloadInvoice(id = null){
            return route('invoice',[id]);
        },

        reset(){
            this.event_id       = 'All Events ';
            this.date_range     = '';
            this.search         = '';
            this.length         = '10';
            this.shortcuts1     = '';
        },

        
        // CUSTOM
    },
    mounted() {
        this.getMyBookings();
        
        // send email after successful bookings
        this.sendEmail();

        //CUSTOM
        this.getMyEvents();
        //CUSTOM
    },

     //CUSTOM
    watch : {
        date_range: function () {
            this.dateRange();
        },

        event_id: function () {
            this.getMyBookings();
        },

        event_date_range: function () {
            this.eventDateRange();
        },
        

    }
    //CUSTOM
}
</script>


