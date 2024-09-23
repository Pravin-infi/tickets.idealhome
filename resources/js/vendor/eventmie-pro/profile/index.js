/**
 * This is a page specific seperate vue instance initializer
 */

// include vue common libraries, plugins and components
require("../../../vue_common");

/**
 * Local Third-party Lib Imports
 */
/* Instances */
import Vuex from "vuex";
window.Vuex = Vuex;
Vue.use(Vuex);

import PersonalDetails from "../../../../../eventmie-pro/resources/js/profile/components/PersonalDetails";
import Stripe from "./components/Stripe";
import Security from "../../../../../eventmie-pro/resources/js/profile/components/Security";
import BankDetails from "../../../../../eventmie-pro/resources/js/profile/components/BankDetails";
import OrganiserInfo from './components/OrganiserInfo';
import SellerInfo from "./components/SellerInfo";
import Mailchimp from "./components/Mailchimp";
import BecomeOrganiser from "../../../../../eventmie-pro/resources/js/profile/components/BecomeOrganiser";
import { mapState, mapMutations } from "vuex";

import Croppa from "vue-croppa";
Vue.use(Croppa);

/**
 * Local Vuex Store
 */

const store = new Vuex.Store({
    state: {
        personal_details: [],
        stripe: [],
        update_bank_details: [],
        organiser_info: [],
        seller_info: [],
        mailChimp_integration: []
    },
    mutations: {
        add(
            state,
            {
                personal_details,
                stripe,
                update_bank_details,
                organiser_info,
                seller_info,
                mailChimp_integration
            }
        ) {
            if (typeof personal_details !== "undefined") {
                state.personal_details = personal_details;
            }

            if (typeof stripe !== "undefined") {
                state.stripe = stripe;
            }

            if (typeof update_bank_details !== "undefined") {
                state.update_bank_details = update_bank_details;
            }

            if (typeof organiser_info !== "undefined") {
                state.organiser_info = organiser_info;
            }

            if (typeof seller_info !== "undefined") {
                state.seller_info = seller_info;
            }

            if (typeof mailChimp_integration !== "undefined") {
                state.mailChimp_integration = mailChimp_integration;
            }
        }
    }
});

const routes = new VueRouter({
    linkExactActiveClass: "active",

    routes: [
        {
            path: "/",

            name: "personal-details",
            props: {
                user: user,
                csrf_token: csrf_token
            },
            component: PersonalDetails
        },
        {
            path: "/userSecurity",

            name: "security",
            props: {
                user: user,
                csrf_token: csrf_token
            },
            component: Security
        },
        {
            path: "/userBankDetails",

            name: "bank-details",
            props: {
                user: user,
                csrf_token: csrf_token
            },
            component: BankDetails
        },
        {
            path: "/userOrganiserInfo",

            name: "organiser-info",
            props: {
                user: user,
                csrf_token: csrf_token
            },
            component: OrganiserInfo
        },
        {
            path: "/becomeOrganiser",

            props: {
                user: user,
                multi_vendor: multi_vendor,
                csrf_token: csrf_token
            },
            name: "become-organiser",
            component: BecomeOrganiser
        },
        {
            path: "/userStripe",

            name: "stripe",
            props: {
                user: user,
                csrf_token: csrf_token
            },
            component: Stripe
        },
        {
            path: "/userSellerInfo",

            name: "seller-info",
            props: {
                user: user,
                csrf_token: csrf_token
            },
            component: SellerInfo
        },
        {
            path: "/userMailchimp",

            props: {
                user: user,
                csrf_token: csrf_token
            },
            name: "mail-chimp",
            component: Mailchimp
        }
    ]
});

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
    el: "#eventmie_app",
    router: routes,
    store: store,
    data() {
        return {
            store: store
        };
    },
    computed: {
        currentRouteName() {
            return this.$route.name;
        },
        ...mapState([
            "personal_details",
            "stripe",
            "update_bank_details",
            "organiser_info",
            "seller_info",
            "mailChimp_integration"
        ])
    },
    methods: {
        ...mapMutations(["add"]),
        checkEmptyProfile() {
            if (user.name == "" || user.email == "") {
                return false;
            }
            return true;
        },
        checkEmptyBank() {
            return true;
        },
        checkEmptyOrganisation() {
            if (user.organisation == null) {
                return false;
            }
            return true;
        },
        checkEmptySeller() {
            // if (user.seller_name == null || user.seller_info == null || user.seller_tax_info == null || user.seller_note);
            //  {
            //     return false;
            // }
            return true;
        },
        checkEmptyMailChimp() {
            // if (user.mailchimp_apikey == null || user.mailchimp_list_id== null ) {

            //     return false;
            // }
            return true;
        },
        checkEmptyStripe() {
            if (user.stripe_account_id == "") {
                return false;
            }
            return true;
        }
    },
    mounted() {
        this.add({
            personal_details: this.checkEmptyProfile(),
            update_bank_details: this.checkEmptyBank(),
            organiser_info: this.checkEmptyOrganisation(),
            seller_info: this.checkEmptySeller(),
            mailChimp_integration: this.checkEmptyMailChimp(),
            stripe: this.checkEmptyStripe()
        });
    }
});
