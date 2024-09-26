<template>
    <div class="custom_model">
        <div class="modal show" v-if="is_qrcode > 0 && qrcode_booking_id > 0">
            <div class="modal-dialog modal-lg w-100">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">{{ booking.ticket_title }} {{ ' x '+booking.quantity }}</h1>
                        <button type="button" class="btn btn-sm bg-danger text-white close" @click="close()"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">                       
                        <p class="subtitle text-center"><strong>#{{ order_number }}</strong></p>
                        <br>
                        <img :src="qrcode_file" class="mx-auto d-block img-fluid">
                    </div>
                    
                </div>
            </div>
        </div>
        
    </div>
</template>

<script>

import mixinsFilters from '../../../../../../../eventmie-pro/resources/js/mixins.js';


export default {
    props: ["organizer_id", "is_qrcode", "qrcode_booking_id", "order_number", "booking"],

    mixins:[
        mixinsFilters
    ],

    data() {
        return {
           qrcode_file : null
             
        }
    },

    methods: {
        // reset form and close modal
        close: function () {    
            this.$parent.is_qrcode            = 0;
            this.$parent.qrcode_booking_id    = 0;
        },


        // get qrcode
        getQrcode(event) {
            // prepare form data for post request
            let post_url = route('get_qrcode');
           
            // axios post request
            axios.post(post_url,{
                booking_id : this.qrcode_booking_id, 
            })
            .then(res => {
                // on success
                // use vuex to update global sponsors array
                if(res.data.status)
                {
                    this.qrcode_file = res.data.qrcode_file;
                }    

            })
            .catch(error => {
                let serrors = Vue.helpers.axiosErrors(error);
                if (serrors.length) {
                    this.serverValidate(serrors);
                }
            });
        },
    },
    
    mounted() {
        this.getQrcode();   
    }


}
</script>