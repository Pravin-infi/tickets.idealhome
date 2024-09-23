<template>
    <div>
        <update-seat v-if="update > 0 " :data="data"></update-seat> 

        
        <div class="row">
            <div class="col-md-4 mt-3 text-wrap">
                <p class="mb-0 form-label">{{ trans('em.upload_seatchart_info') }}</p>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ trans('em.seat_max') }}
                        <span class="badge bg-primary rounded-pill">{{ ticket.quantity }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ trans('em.seat_added') }}
                        <span class="badge bg-success rounded-pill">{{ seats.length }}</span>
                    </li>
                </ul>
               
            </div>
        </div>
        <br><br>

        <!-- Seating chart image -->
        <div class="row">
            <div class="col-md-12">
                <div class="seat-container" @contextmenu.prevent id="seat_container" @dragover="dragOver"  @drop="drop">
                    <img @click="seatsSeletion" :src="'/storage/'+local_ticket.seatchart.chart_image" class="seat-img" id="seat-img"   >
                    <span class="seat-mark" 
                        :class="{'seat-disabled': (seat.status <= 0 && seat.status != null)}"
                        :style="{ 'left': seat.x, 'top' :seat.y, 'width' : seat.width+'px' , 'height' : seat.height+'px', 'border-radius' : seat.border+'px', 'font-size' : seat.font_size+'px', 'resize' : 'both' }" 
                        @click.right="updateSeatName(seat)"
                        :data-id="seat.id"
                        draggable="true"
                        :id="`seat_${seat.temp_id}`"
                        v-for="(seat, index) in seats"
                        v-bind:key="index"
                   
                        @dragstart="dragStart($event, seat.temp_id)"
                        
                        @dragend="dragEnd($event, seat.temp_id)"

                        
                    >{{seat.name}}</span>
                </div>
            </div>
        </div>  

        <br><br>
        <div class="row">
            <div class="col-md-12">
                <form id="form" ref="form" method="POST" @submit.prevent="validateForm">
                    <input  type="hidden" class="form-control"  name="seatchart_id" 
                    :value="local_ticket.seatchart.id">
                    <input  type="hidden" class="form-control"  name="ticket_id" :value="local_ticket.id">
                    <input type="hidden" class="form-control"  name="event_id" :value="local_ticket.event_id">
                    
                    <input type="hidden" name="coordinates[]"
                            v-for="(seat, index) in seats"
                            :key="index+10000"
                            :value="seat.x+','+seat.y"
                    >

                    <input type="hidden" name="ids[]"
                            v-for="(seat, index) in seats"
                            :key="index+20000"
                            :value="seat.id"
                            
                    >

                    <input type="hidden" name="seat_names[]"
                            v-for="(seat, index) in seats"
                            :key="index+30000"
                            :value="seat.name"
                            
                    >

                    <input type="hidden" name="seat_width[]"
                            v-for="(seat, index) in seats"
                            :key="index+60000"
                            :value="seat.width"
                            
                    >

                    <input type="hidden" name="seat_height[]"
                            v-for="(seat, index) in seats"
                            :key="index+90000"
                            :value="seat.height"
                            
                    >

                    <input type="hidden" name="seat_border[]"
                            v-for="(seat, index) in seats"
                            :key="index+120000"
                            :value="seat.border"
                            
                    >

                        <input type="hidden" name="font_size[]"
                            v-for="(seat, index) in seats"
                            :key="index+150000"
                            :value="seat.font_size"
                            
                    >


                    <div class="d-flex justify-content-center">
                        <div>
                            <button type="button" class="btn btn-sm text-white btn-danger"  @click="deleteAllSeats()">{{trans('em.delete')+' '+trans('em.all')+' '+trans('em.seat') }}</button>    
                            <button v-if="ticket.seatchart != null" type="button" class="btn btn-sm text-white btn-primary" :class="ticket.seatchart.status > 0 ? 'btn-danger' : 'btn-info'" @click="seatchartDisableEnable(ticket.id, ticket.seatchart.status)">{{ (ticket.seatchart.status > 0 ? trans('em.disable') : trans('em.enable')) }} {{ trans('em.seatchart') }}</button>
                        </div> 
                        <div class="ms-1">
                            <button type="submit" class="btn btn-sm text-white btn-success">{{trans('em.save_seat')}}</button>
                        </div> 
                    </div>
                        
                    

                    
                </form>
            </div>
        </div>  

    </div>

</template>
<script>
import UpdateSeat from './UpdateSeat';
import mixinsFilters from '../../../../../../../eventmie-pro/resources/js/mixins.js';


export default {
    props:['ticket'],
    mixins:[
        mixinsFilters
    ],

    data()  {
         
        return {
            local_ticket : this.ticket,
            seats   : [],
            count   : 0,
            update  : 0,
            data    : {},
            zoom : 1,
            last_width :  '20',
            last_height : '20',
            last_border : '50',
            last_font_size : '10',
            
        }
        
    },

    components:{
        UpdateSeat,
    },

    methods: {

        //select seats
        seatsSeletion(e) {
            console.log('sel', e);

            console.log('taget', e.taget);

            var rect = e.target.getBoundingClientRect();
            
            var x = e.clientX - rect.left; //x position within the element.
            var y = e.clientY - rect.top;  //y position within the element.
            
            // add seats upto ticket.quantity
            if(this.seats.length < this.ticket.quantity) {
                // increment count
                this.count = this.count + 1;
                
                //create seat
                let seat = {
                    'x'           : (x-10)+'px',
                    'y'           : (y-10)+'px',
                    'name'        : this.count,
                    'id'          : null,
                    'ticket_id'   : this.local_ticket.id,
                    'status'      : null,
                    'temp_id'     : Date.now(),
                    'width'       : this.last_width, 
                    'height'      : this.last_height, 
                    'border'      : this.last_border,
                    'font_size' : this.last_font_size
                };

                // created seat push into seats array
                this.seats.push(seat);

            } else {
                this.showNotification('error', trans('em.seat_max_error'));
            }
            
        },

        //save seat 
        saveSeat(){
            let formData = new FormData(this.$refs.form);

            axios.post(route('save_seats'),
                formData
            ).then(res => {

                if(res.data.status){
                    //ticket update
                    this.local_ticket = res.data.ticket
                    this.showSelectedSeats();
                    this.showNotification('success', trans('em.seat_saved'));
                 
                }

            })
            .catch(error => {
                let serrors = Vue.helpers.axiosErrors(error);
                if (serrors.length) {
                    this.serverValidate(serrors);
                }
            });
        },

        //show selected seats

        showSelectedSeats(){
            this.seats = [];
            this.count = 0;
            let _this  = this;

            // check seats
            if(this.local_ticket.seatchart != null && this.local_ticket.seatchart.seats.length > 0){
                try {
                    this.local_ticket.seatchart.seats.forEach((value, index) => {
                        
                        //increment count variable and check is number or not
                        if(!isNaN(value.name))
                            _this.count            = parseInt(value.name);

                        // comma saprated value convert into array    
                        let coordinates        = value.coordinates.split(',');    

                        // create seat
                        let seat = {
                            'x'           : coordinates[0],
                            'y'           : coordinates[1],
                            'name'        : value.name,
                            'id'          : value.id,
                            'ticket_id'   : _this.local_ticket.id,
                            'status'      : value.status,
                            'temp_id'     : value.id,
                            'width'       : value.width,
                            'height'      : value.height,
                            'border'      : value.border,
                            'font_size'   : value.font_size
                        };

                        // created seat push into seats array
                        _this.seats.push(seat);
                        
                    });

                    this.last_width  = this.seats[this.seats.length - 1].width;
                    this.last_height = this.seats[this.seats.length - 1].height;
                    this.last_border = this.seats[this.seats.length - 1].border;
                    this.last_font_size = this.seats[this.seats.length - 1].font_size;
                

                } catch(e){
                    console.log(e, 'Error');
                }

            }
        },

        // open UpdateSeat modal
        updateSeatName(seat = null){
            this.data   = seat;
            this.update = 1;
        },

        // validate data on form submit
        validateForm(event) {
            this.$validator.validateAll().then((result) => {
                if (result) {
                    this.saveSeat(event);            
                }
            });
        },

        // show server validation errors
        serverValidate(serrors) {
            this.$validator.validateAll().then((result) => {
                this.$validator.errors.add(serrors);
            });
        },
        
        
        //CUSTOM
        // seatchart disable or enable
        seatchartDisableEnable(ticket_id = null, status){
            this.showConfirm().then((res) => {
                if(res) {
                    axios.post(route('disable_enable_seatchart'), {
                        ticket_id       : ticket_id,
                    })
                    .then(res => {
                    
                        if(res.data.status) {
                            this.showNotification('success', (status > 0 ? trans('em.seatchart_disabled') : trans('em.seatchart_enabled')));
                            // reload page   
                            setTimeout(function() {
                                location.reload(true);
                            }, 1000);
                        }else{
                            this.showNotification('error', res.data.error);
                        }
                    })
                    .catch(error => {
                        Vue.helpers.axiosErrors(error);
                    });
                }
            })
        },

          // delete all seats
        deleteAllSeats(){
            this.showConfirm().then((res) => {
                if(res) {
                    
                        
                        // detele form database    
                        axios.post(route('delete_all_seats'),{
                        
                            'ticket_id' : this.ticket.id,

                        }).then(res => {
                            
                            //update ticket
                            this.local_ticket = res.data.ticket;
                            this.seats = [];

                            //update seats
                            this.showSelectedSeats();
                            
                            this.showNotification('success', trans('em.seat_deleted'));
                            this.close(); 
                        })
                        .catch(error => {
                            let serrors = Vue.helpers.axiosErrors(error);
                            if (serrors.length) {
                                this.serverValidate(serrors);
                            }
                        });

                        

                    
                }
            });

        },


        // drag start 
        dragStart(event, id) {
             
            console.log('start', event);
            event.dataTransfer.dropEffect = 'move';
            event.dataTransfer.effectAllowed = 'move';  
            event.dataTransfer.setData('seat_id', event.target.id);
            event.dataTransfer.setData('id', id);

            document.getElementById(event.target.id).style.opacity = "0.001";
            document.getElementById(event.target.id).style.background = "green";
           
        },

        // drag end
        dragEnd(event, id){
            if(id == null || id == undefined)
                return true;
            console.log('id',  id);
            console.log('drag-end', event);
                        
                        
                        var _this = this;

                        var parentPos = document.getElementById('seat_container').getBoundingClientRect();
                        var childPos = document.getElementById('seat_'+id).getBoundingClientRect();
                        var  relativePos = {};
                        console.log('taget', event.taget);
                        var rect = parentPos;
                        var x = event.clientX - rect.left; //x position within the element.
                        var y = event.clientY - rect.top;  //y position within the element.
                        
                        
                        this.seats.forEach(function(value, index){
                            if(value.temp_id == id) {
                                console.log('off', event.offsetX, event.offsetY)
                                _this.seats[index].x = (x-10)+'px';
                                _this.seats[index].y = (y-10)+'px';


                                console.log('match', id, _this.seats[index]);
                     
                            }
                            
                        });

                        document.getElementById('seat_'+id).style.opacity = "1";
                        document.getElementById(event.target.id).style.background = "#681F84";
                        


        },

        // drag drop
        drop(a) {

        },

        // drag over 
        dragOver(event) {
            event.preventDefault();
        },

        zoomIn(id = null){
            this.zoom += 0.1;
            document.getElementById('seat_container').style.transform = `scale(${this.zoom})`  ;
        
        },

        zoomOut(id = null){
            this.zoom -= 0.1;
			document.getElementById('seat_container').style.transform = `scale(${this.zoom})`;
        },

        zoomReset(id = null){
            this.zoom = 1;
            document.getElementById('seat_container').style.transform = `scale(${this.zoom})`;
        
        }
        //CUSTOM
    },

  

    mounted() {
        
        this.showSelectedSeats();
    }   

}
</script>
