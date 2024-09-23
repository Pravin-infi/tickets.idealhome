<template>
    <div>
        <div class="row" v-if="events_slider == true">
            <VueSlickCarousel
                :autoplay="true"
                :autoplaySpeed="3000"
                :arrows="false" 
                :dots="false"
                :infinite="false"
                :paginationEnabled="false"
                :slidesToShow="local_item_count"
                :rtl="dir"
                :class="'custom-carousel px-1'"
            >
                <div
                    v-match-heights="{
                        el: ['h5.sub-title'],  // Array of selectors to fix
                    }"
                    v-for="(event, index) in events" 
                    :key="index"
                    :class="'col-md-6 col-lg-4 col-12'"
                >
                    <Event :event="event" :currency='currency' :date_format='date_format'/>

                </div>
            </VueSlickCarousel>
        </div>
         
        <div class="row" v-else>
            <div 
                class="col-md-4 col-12 mb-4 px-0"
                    v-match-heights="{
                        el: ['h5.sub-title'],  // Array of selectors to fix
                    }"
                    v-for="(event, index) in events" 
                    :key="index"
            >
                <Event :event="event" :currency='currency' :date_format='date_format'/>
            </div>    
        </div>

        <div class="row" v-if="not_found">
            <div class="col-12">
                <h4 class="heading text-center mt-30"><i class="fas fa-exclamation-triangle"></i> {{ trans('em.events_not_found') }}</h4>
            </div>
        </div>

    </div>
   
</template>

<script>

import mixinsFilters from '../../../mixins.js';

import VueSlickCarousel from 'vue-slick-carousel'
import 'vue-slick-carousel/dist/vue-slick-carousel.css'
// optional style for arrows & dots
import 'vue-slick-carousel/dist/vue-slick-carousel-theme.css'

import Event from './Event.vue';

//  CUSTOM
import VueCountdown from '@chenfengyuan/vue-countdown';
//  CUSTOM


export default {
    
    props: ['events', 'currency', 'date_format', 'item_count'],

    components: {
        VueSlickCarousel,
        VueCountdown,
        Event
    },

    mixins:[
        mixinsFilters
    ],

    data() {
        return {
            not_found   : false,
            events_slider   : events_slider,
            dir         : false,
            local_item_count  : this.item_count,
        }
    },

    methods:{
        
        // check free tickets of events
        checkFreeTickets(event_tickets = []){
            let free = false;
            event_tickets.forEach(function(value, key) {
                if(parseFloat(value.price) <= parseFloat(0))
                {
                    free = true;
                }
            });    
            return free;
        },

        
        // return route with event slug
        eventSlug: function eventSlug(slug) {
            return route('eventmie.events_show', [slug]);
        },

         //CUSTOM
        timerOnSale(sale_start_date = null, sale_end_date = null){

            if(sale_start_date == null || sale_end_date == null)
                return true;
            
            var local_tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            
            var a    = this.userTimezone(sale_end_date, 'YYYY-MM-DD HH:mm:ss').format('DD/MM/YYYY HH:mm:ss');
            var b    = moment().tz(local_tz).format('DD/MM/YYYY HH:mm:ss');
            var ms   = 0; // milliseconds
            
            if(moment(a,"DD/MM/YYYY HH:mm:ss") > moment(b,"DD/MM/YYYY HH:mm:ss")){
                ms = moment(a,"DD/MM/YYYY HH:mm:ss").tz(local_tz).diff(moment(b,"DD/MM/YYYY HH:mm:ss").tz(local_tz)); //milliseconds
                
            }
           
            return ms;
        },
        getDirection(){
            document.documentElement.dir == 'rtl' ? this.dir = true : this.dir = false;
        },

        mobileView(){
            var androidMobile = window.matchMedia("(max-width: 768px)");
            if (androidMobile.matches)
                this.local_item_count = 1;
        }
  
    },


    watch: {
        events: function () {
            this.not_found = false;
            if(this.events.length <= 0)
                this.not_found = true;
        
        },
        
    },
    mounted(){    
        this.getDirection();
        this.mobileView();
    }

}
</script>