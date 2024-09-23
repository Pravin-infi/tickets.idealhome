
require('../../../../eventmie-pro/resources/js/vue_common');


require('../../bootstrap.js');

Vue.component('seats-component', require('./Seats.vue').default);

window.seat = new Vue({
  el: '#eventmie_app',
  
});