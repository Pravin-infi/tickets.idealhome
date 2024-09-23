<template>
    <div class="organizer-events">
        <div class="row" v-if="Object.keys(active_events).length > 0">
            <div class="col-12">
                <h2 class="h3">{{ trans('em.active_events') }}</h2>
                <event-listing :events ="active_events" :currency="currency" :date_format="date_format"></event-listing>
            </div>    
        </div>
        <div class="row" v-if="Object.keys(expired_events).length > 0">
            <div class="col-12">
                <h2 class="h3">{{ trans('em.ended_events') }}</h2>
                <event-listing :events ="expired_events" :currency="currency" :date_format="date_format"></event-listing>
            </div>    
        </div>
    </div>
</template>

<script>
import EventListing from '../../vendor/eventmie-pro/common_components/EventListing';
import mixinsFilters from '../../mixins.js';

export default {

    mixins:[
        mixinsFilters
    ],

    props: [
        // pagination query string
        
        'active_events',
        'expired_events',
        'currency',
        'date_format'
        
    ],

    components: {
        EventListing, 
    },

    mounted() {
      console.log('organiser');   
    }
}
</script>
