<template>
    <div>
        <div v-if="Object.keys(booking).length > 0">

            <table class="table table-striped table-hover ">
                <tbody>
                <tr>
                    <th>{{ trans('em.event') }}</th>
                    <td>{{ booking.event_title }} ( {{booking.event_category}} )</td>
                </tr>

                <tr>
                <th>{{ trans('em.repetitive') }}</th>
                <td>{{ booking.event_repetitive > 0 ? trans('em.yes') : trans('em.no')}}</td>
                </tr>   

                <tr>
                <th>{{ trans('em.timings') }}</th>
                <td>
                    <p class="text-bold text-small">{{ userTimezone(booking.event_start_date+' '+booking.event_start_time, 'YYYY-MM-DD HH:mm:ss').format(date_format.vue_date_format) }} - {{
                        (userTimezone(booking.event_start_date+' '+booking.event_start_time, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD') <= userTimezone(booking.event_end_date+' '+booking.event_end_time, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD')) ? userTimezone(booking.event_end_date+' '+booking.event_end_time, 'YYYY-MM-DD HH:mm:ss').format(date_format.vue_date_format) : userTimezone(booking.event_start_date+' '+booking.event_start_time, 'YYYY-MM-DD HH:mm:ss').format(date_format.vue_date_format)
                    }} 

                    {{  showTimezone()  }}
                    
                    </p>
                    <p class="text-bold text-small">{{ userTimezone(booking.event_start_date+' '+booking.event_start_time, 'YYYY-MM-DD HH:mm:ss').format(date_format.vue_time_format)+' - '+userTimezone(booking.event_end_date+' '+booking.event_end_time, 'YYYY-MM-DD HH:mm:ss').format(date_format.vue_time_format) +  showTimezone() }}</p>                </td>
                </tr>

                <tr>
                <th>{{ trans('em.customer') }}</th>
                <td>{{ booking.customer_name }} ( {{booking.customer_email}} )</td>
                </tr>

                <tr>
                <th>{{ trans('em.booked_on') }}</th>
                <td>{{ moment(convert_date_to_local(booking.created_at)).format(date_format.vue_date_format) }}</td>
                </tr>   

                <tr>
                <th>{{ trans('em.order') }}</th>
                <td>{{ booking.order_number }}</td>
                </tr>

                <tr>
                <th>{{ trans('em.ticket') }}</th>
                <td>{{ booking.ticket_title }} <strong>{{ ' x '+booking.quantity }}</strong></td>
                </tr>

                <tr>
                <th>{{ trans('em.price') }}</th>
                <td>{{ booking.price+' '+booking.currency }}</td>
                </tr>
                
                <tr>
                <th>{{ trans('em.payment') }}</th>
                <td class="text-capitalize">
                    <span class="badge bg-secondary" v-if="booking.payment_type == 'offline'">
                        {{ booking.payment_type }} 
                        <hr class="small p-0 m-0">
                        <small class="text-small text-white" v-if="booking.is_paid">{{ trans('em.paid') }}</small>
                        <small class="text-small text-white" v-else>{{ trans('em.unpaid') }}</small>
                    </span>
                    <span class="badge bg-success" v-else>{{ booking.payment_type }} <hr class="small"><small class="text-small">{{ booking.is_paid ? trans('em.paid') : trans('em.unpaid') }}</small></span>
                </td>
                </tr>

                <tr>
                <th>{{ trans('em.booking_cancellation') }}</th>
                <td>
                    <span class="badge  bg-success" v-if="booking.booking_cancel == 0">{{ trans('em.no') }}</span>
                    <span class="badge  bg-warning" v-if="booking.booking_cancel == 1">{{ trans('em.pending') }}</span>
                    <span class="badge bg-info" v-if="booking.booking_cancel == 2">{{ trans('em.approved') }}</span>
                    <span class="badge bg-secondary" v-if="booking.booking_cancel == 3">{{ trans('em.refunded') }}</span>
                </td>
                </tr>
                
                <tr>
                <th>{{ trans('em.checked_in') }}</th>
                <td>
                    <span class="badge bg-success" v-if="booking.checked_in > 0">{{ trans('em.yes') }}</span>
                    <span class="badge bg-secondary" v-else>{{ trans('em.no') }}</span>
                </td>
                </tr>

                <tr>
                <th>{{ trans('em.booking') }}  {{ trans('em.status') }}</th>
                <td>
                    <span class="badge bg-success" v-if="booking.status == 1">{{ trans('em.enabled') }}</span>
                    <span class="badge bg-secondary" v-else>{{ trans('em.disabled') }}</span>
                </td>
                </tr>
                </tbody>
            </table>

        </div>

        <div v-else>
            <div class="alert alert-info"><h5><i class="fas fa-qrcode"></i> {{ trans('em.scan_ticket') }}</h5></div>
        </div>

        <div class="alert alert-danger"  v-if="errorMessage != '' "><i class="fas fa-camera"></i> {{ errorMessage }}</div>

        <qrcode-stream :camera="camera" v-if="hide_scanner <= 0" @decode="onDecode" @init="onInit"></qrcode-stream>
        
        
           
    </div>
    
</template>

<script>

import mixinsFilters from '../../../../mixins.js';

export default {

    mixins:[
        mixinsFilters
    ],

    props: ['date_format'],
    
    data() {
        return {
            moment     : moment,
            decodedContent  : '',
            errorMessage    : '',
            hide_scanner    : 0,
            booking         : [],
            camera: 'auto',
        }
    },

    methods: {
        onDecode(content) {
            this.decodedContent = JSON.parse(content);  
            if(Object.keys(this.decodedContent).length > 0)
                this.getBooking();
        },

        getBooking() {
            let post_url = route('eventmie.get_booking') ;
            let post_data = {
                'id'            : this.decodedContent.id,
                'order_number'  : this.decodedContent.order_number,
            };
            axios.post(post_url, post_data)
            .then(res => {
                document.getElementById("dropdown").style.display = "block";
                this.booking   = res.data.booking;
                
                this.VerifyTicket();
            })
            .catch(error => {
                document.getElementById("dropdown").style.display = "none";
                this.camera = 'off'
                let serrors = Vue.helpers.axiosErrors(error);
                if (serrors.length) {
                    this.serverValidate(serrors);
                }
                let _this = this;
                setTimeout(()=> {
                    _this.camera = 'auto';
                },2000);
            });
        },

        onInit(promise) {
            promise.then(() => {
                console.log('Successfully initilized! Ready for scanning now!')
            })
            .catch(error => {
                if (error.name === 'NotAllowedError') {
                    this.errorMessage = trans('em.camera_access_required');
                } else if (error.name === 'NotFoundError') {
                    this.errorMessage = trans('em.camera_not_detected');
                } else if (error.name === 'NotSupportedError') {
                    this.errorMessage = trans('em.camera_https_required');
                } else if (error.name === 'NotReadableError') {
                    this.errorMessage = trans('em.camera_not_detected');
                } else if (error.name === 'OverconstrainedError') {
                    this.errorMessage = trans('em.camera_not_detected');
                } else {
                    this.errorMessage = trans('em.camera_not_detected');
                }
            })
        },

        // verify ticket after qr code scanner
        VerifyTicket() {
            document.getElementById("booking_id").value                 = this.booking.id; 
            document.getElementById("order_number").value               = this.booking.order_number; 
            document.getElementById('check_in_button').style.display    = 'block';

            this.hide_scanner = 1;

            //CUSTOM
            this.booking.attendees.forEach((value, index) => {
                var option      = document.createElement("option");
                option.text     = value.name;
                option.value    = value.id;
                option.disabled = value.checked_in > 0 ? true : false;
                option.selected = true;
                var attendee_id = document.getElementById("attendee_id");
                console.log(attendee_id);
                attendee_id.appendChild(option);
            });
            setTimeout(function() {
                document.getElementById('form').submit();
            },100);
            
            //CUSTOM
        },

        // getBookingTesting() {
        //     let post_url = route('eventmie.get_booking') ;
        //     let post_data = {
        //         'id'            : '202',
        //         'order_number'  : '16368910844711',
        //     };
        //     axios.post(post_url, post_data)
        //     .then(res => {
        //         this.booking   = res.data.booking;
        //         this.VerifyTicket();
        //     })
        //     .catch(error => {
        //         let serrors = Vue.helpers.axiosErrors(error);
        //         if (serrors.length) {
        //             this.serverValidate(serrors);
        //         }
        //     });
        // },
    },

    mounted() {
        document.getElementById("dropdown").style.display = "none";
          // 2-un-comment-when-testing
        // this.getBookingTesting();
        
    }

    
}
</script>
