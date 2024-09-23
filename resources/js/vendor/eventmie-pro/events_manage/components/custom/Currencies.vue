<template>

    <div>
        <label class="form-label">{{ trans('em.event_specific_currency') +' ('+trans('em.optional') }})

        </label>
        <multiselect
        v-model="tmp_currencies_ids"
        :options="currencies_options" 
        id="ajax"
        label="text"
        track-by="value"
        :placeholder="'e.g USD'" 
        open-direction="bottom"
        :multiple="false"
        :searchable="true"
        :loading="isLoading"
        :internal-search="false"
        :clear-on-select="true"
        :close-on-select="true"
        :options-limit="300"
        :limit="20"
        :limit-text="limitText"
        :max-height="300"
        :show-no-results="false"
        :hide-selected="false"
        @search-change="searchCurrencies"
        :allow-empty="true"
        :class="'form-control px-0 py-0 mb-3'"
        :preserve-search="true" 
        :preselect-first="false"
        @select="$parent.isDirty()"
        >
        <template slot="tag" slot-scope="{ option, remove }">
            <span class="multiselect__tag" @click="remove(option)">
            <span >{{ option.text }}</span>
                <i aria-hidden="true" tabindex="1" class="multiselect__tag-icon">
                </i>
            </span>
        </template>
        <template slot="clear" slot-scope="props">
            <div
            class="multiselect__clear"
            v-if="tmp_currencies_ids != null"
            @mousedown.prevent.stop="clearAll(props.search)"
            ></div>
        </template>
        <span slot="noResult"> {{ trans('em.not_found') }}</span>
        </multiselect>
        
    </div>

</template>

<script>
import _ from 'lodash';
import { mapState, mapMutations} from 'vuex';
import mixinsFilters from '../../../../../../../eventmie-pro/resources/js/mixins.js';

export default {

    

    mixins:[
        mixinsFilters
    ],

    components: {
        
    },

    data() {
        return {
            currencies_ids         : [],
            currencies_options     : [],
            tmp_currencies_ids     : [],
            
            is_publishable   : [],
            currencies       : [],

            isLoading: false


        }
    },

    computed: {
        ...mapState( ['event_id', 'organiser_id']),
    },

    methods: {
        ...mapMutations(['add', 'update']),

           // validate data on form submit
        validateForm(event) {
            this.$validator.validateAll().then((result) => {
                if (result) {
                    this.formSubmit(event);            
                }
            });
        },

        // get selected currencies in case of editing
        getSelectedCurrencies(selected_currency = null) {
            console.log(selected_currency);
            // set mutiple currencies for multiselect list
            if(selected_currency != null)
            {   
                
                this.tmp_currencies_ids = {value : selected_currency.id, text : selected_currency.iso_code };
                
                
            }    
            
        },


        // update currencies for submit
        updateCurrencies(){
            
            //currencies
            if(this.tmp_currencies_ids != null) {
                this.$parent.currency = this.tmp_currencies_ids.text;
                this.$parent.currency_id = this.tmp_currencies_ids.value;
            } else {
                this.$parent.currency = '';
                this.$parent.currency_id = null;
            }
        },


        limitText (count) {
            return trans('em.event')+count +trans('em.currencies');
        },

        // get currencies of organiser and tag searching
        searchCurrencies: _.debounce(function(search) {  

            this.isLoading = true

            let post_url    = route('currencies');
            
            let post_data   = {
                'search'     : search,
                organiser_id : this.organiser_id, 
            };
            
            // axios post request
            axios.post(post_url, post_data)
            .then(res => {
                    console.log(res.data.currencies);
                this.currencies_options = [];
                // fill data to global currencies array
                
                this.currencies        = res.data.currencies;
                
                // set mutiple currencies for multiselect list
                if(this.currencies.length > 0)
                {
                    this.currencies.forEach(function(v, key) {
                        this.currencies_options.push(v);
                    }.bind(this));
                    
                }
                
                this.isLoading = false;
            })
            .catch(error => {
                let serrors = Vue.helpers.axiosErrors(error);
                if (serrors.length) {
                    this.serverValidate(serrors);
                }
            });
        }, 1000),

        clearAll () {
            this.tmp_currencies_ids = []
        },

    },
    
    watch: {
        
        tmp_currencies_ids : function() {
            this.updateCurrencies();
        },
    },

    mounted(){
        
        
    }

}
</script>