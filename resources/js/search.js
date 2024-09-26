import _ from 'lodash';

window.app = new Vue({
    el: "#searchEvent",
    
    methods: {
         // serch event with 5 delay
        GetEvents: _.debounce(function(input_id, dropdown_id) {
            
            searchOptions(input_id,dropdown_id);    
           
        }, 1000),
    },
});