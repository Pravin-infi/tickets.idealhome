<template>
    <div >
        <div class="text-center">

            <h3 class="title"> {{ trans('em.seats') }}</h3>
           
            <table class="m-auto">
                <tr>
                    <td><i class="fas fa-square seat-disabled-col"></i> {{ trans('em.disabled') }}&nbsp;&nbsp; </td>
                    <td><i class="fas fa-square seat-reserved-col"></i> {{ trans('em.reserved') }}&nbsp;&nbsp; </td>
                    <td> <i class="fas fa-square seat-available-col"></i> {{ trans('em.available') }}&nbsp; &nbsp;</td>
                    <td> <i class="fas fa-square seat-select-col"></i> {{ trans('em.selected') }}</td>
                </tr>
            </table>

            <div class="seat-container" :id="`seat-container_${ticket.id}`">

                <img :src="'/storage/'+local_ticket.seatchart.chart_image" class="seat-img">

                <span class="seat-mark" 
                    :class="{'seat-disabled': (seat.status <= 0 && seat.status != null), 'seat-reserved': seat.reserved}"
                    :style="{ 'left': seat.x, 'top' :seat.y, 'width' : seat.width+'px' , 'height' : seat.height+'px', 'border-radius' : seat.border+'px', 'font-size' : seat.font_size+'px' }" 

                    :id="'seatmark_id_'+ticket.id+'_'+index"
                    v-for="(seat, index) in seats"
                    v-bind:key="index"
                    @click="increaseTicketQuantity(index)"
                >{{seat.name}}</span>

                <input class="seat-checkbox" type="checkbox" 
                    v-for="(seat, index) in seats"
                    :style="{ 'left': seat.x, 'top' :seat.y }" 
                    :key="index+300" 
                    :name="'seat_id_'+ticket.id+'[]'"
                    :id="'seat_id_'+ticket.id+'_'+index"
                    :value="seat.id"
                >
            </div>
            
            
        </div>
         
            <div class="d-flex flex-wrap justify-content-center " style="width: 100%; position: absolute; z-index: 999;">
                <button  type="button" @click.prevent="zoomIn(ticket.id)" class="btn btn-sm btn-primary m-3"><i class="fa fa-search-plus" aria-hidden="true"></i> </button>
                <button  type="button" @click.prevent="zoomOut(ticket.id)" class="btn btn-sm btn-primary m-3"><i class="fa fa-search-minus" aria-hidden="true"></i> </button>
                <button  type="button" @click.prevent="zoomReset(ticket.id)" class="btn btn-sm btn-primary m-3"><i class="fas fa-sync" aria-hidden="true"></i> </button>

                <button type="button" @click="sentDataToReact" class="btn btn-sm btn-primary m-3"><i class="fas fa-sd-card"></i> {{ trans('em.submit') }}</button>
            </div>

    </div>

</template>
<script>
import { mapState, mapMutations} from 'vuex';
import mixinsFilters from '../../mixins.js';

export default {
    props:['ticket', 'ticket_index','max_ticket_qty', 'event', 'booked_date_server'],
    mixins:[
        mixinsFilters
    ],
    data()  {
        return {
            local_ticket : this.ticket,
            seats   : [],
            count   : 0,
            zoom : 1,
            
        }
    },
    computed: {
       
    },
    methods: {
        // Show seats on image according to coordinates
        showSelectedSeats() {
            this.seats = [];
            this.count = 0;
            let _this  = this;

            // check seats
            if(this.local_ticket.seatchart != null && this.local_ticket.seatchart.seats.length > 0) {

                this.local_ticket.seatchart.seats.forEach((value, index) => {
                    
                    //increment count variable and check is number or not
                    if(!isNaN(value.name))
                        _this.count            = parseInt(value.name);

                    // comma saprated value convert into array    
                    let coordinates        = value.coordinates.split(',');
                    
                    // show reserved seats
                    let reserved           = false;
                    _this.local_ticket.attendees.forEach(function(attendee, key){
                        // check for specific date
                        // non-repetitive event
                        if(_this.event.repetitive <= 0) {
                            if(attendee.seat_id == value.id && attendee.booking.status > 0) {
                                reserved = true;
                            }
                        } else {
                            if(attendee.seat_id == value.id && _this.userTimezone(attendee.booking.event_start_date+' '+attendee.booking.event_start_time, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD') == _this.booked_date_server && attendee.booking.status > 0) {
                                reserved = true;
                            }
                        }
                    });
                        
                    // create seat
                    let seat = {
                        'x'           : coordinates[0],
                        'y'           : coordinates[1],
                        'name'        : value.name,
                        'id'          : value.id,
                        'ticket_id'   : _this.local_ticket.id,
                        'reserved'    : reserved,
                        'status'      : value.status,
                        'checked'     : false,
                        'width'       : value.width, 
                        'height'      : value.height, 
                        'border'      : value.border,
                        'font_size'   : value.font_size
                    };
                    
                    if(value.seat_status != null && !reserved) {
                        
                        if(login_user_id > 0 && this.booked_date_server == value.seat_status.seats.booked_date_server && login_user_id != value.seat_status.seats.customer_id) {
                            console.log('loing user');
                            seat.reserved = value.seat_status.is_checked > 0 ? true : false;
                        }
                        
                    }
                    // created seat push into seats array
                    _this.seats.push(seat);
                    
                });

            }
        },

        //increase or decrease ticket quantity through checkbox
        increaseTicketQuantity(index = null) {
            
            // seatcheckbox
            let seat_id      = 'seat_id_'+this.ticket.id+'_'+index;
            let seat         = document.getElementById(seat_id);

            // seatmark
            let seatmark_id  = 'seatmark_id_'+this.ticket.id+'_'+index;
            let seatmark     = document.getElementById(seatmark_id);

            // select seat only if, it's !reserved !disabled
            if(seatmark.classList.contains('seat-disabled') || seatmark.classList.contains('seat-reserved')) {
                return false;
            }
            
            //increase or decrease ticket quantity
            if (seat.checked == true ) {
                // uncheck if checked & decrease quantity
                seat.checked = false;
                seatmark.classList.remove('seat-select');

                this.seats[index].checked = false;  
            } else   {
                // check if unchecked & increase quantity
                seat.checked = true;
                seatmark.classList.add('seat-select');
                this.seats[index].checked = true;   
         
            }
            this.seatStatus(document.getElementById(seat_id).value, seat.checked);
        },

        zoomIn(id){
            
            this.zoom += 0.1;
            document.getElementById(`seat-container_${id}`).style.transform = `scale(${this.zoom})`  ;
        
        },

        zoomOut(id){
            this.zoom -= 0.1;
			document.getElementById(`seat-container_${id}`).style.transform = `scale(${this.zoom})`;
        },

        zoomReset(id){
            this.zoom = 1;
            document.getElementById(`seat-container_${id}`).style.transform = `scale(${this.zoom})`;
        
        },

        sentDataToReact(){
            console.log(this.seats);
            if(window.ReactNativeWebView) { // ensure window.ReactNativeWebView is there, otherwise, web app might crash if is not there
                window.ReactNativeWebView.postMessage(JSON.stringify(this.seats))
            }
        },

        seatStatus(seat_id, checked) {
            
            let seat = this.seats.find(seat => seat.id == seat_id);

            if(seat != undefined) {
                seat.is_checked = checked ? 1 : 0;
                seat.ipAddress = this.ipAddress;
                seat.customer_id = login_user_id > 0 ? login_user_id : 0;
                seat.event_start_date = this.serverTimezone(moment(this.booking_date).format('dddd LL')+' '+this.start_time, 'dddd LL HH:mm a').locale('en').format('YYYY-MM-DD') 
                seat.booked_date_server = this.booked_date_server;
            }
        },

    },

    mounted() {
        this.showSelectedSeats();
    }   

}
</script>
