/**
 * This is a page specific seperate vue instance initializer
 */

// include vue common libraries, plugins and components
require("../vue_common.js");

/**
 * Local Third-party Lib Imports
 */
/* Instances */
import Vuex from "vuex";
window.Vuex = Vuex;
Vue.use(Vuex);

/**
 * Below are the page specific plugins and components
 */

// for using time
window.moment = require("moment-timezone");
import VueGoodTablePlugin from "vue-good-table";
// import the styles
import "vue-good-table/dist/vue-good-table.css";
Vue.use(VueGoodTablePlugin);

Vue.component("event-total", require("./components/EventTotal.vue").default);

/**
 * This is where we finally create a page specific
 * vue instance with required configs
 * element=app will remain common for all vue instances
 *
 * make sure to use window.app to make new Vue instance
 * so that we can access vue instance from anywhere
 * e.g interceptors
 */
window.app = new Vue({
    el: "#eventmie_app"
});
