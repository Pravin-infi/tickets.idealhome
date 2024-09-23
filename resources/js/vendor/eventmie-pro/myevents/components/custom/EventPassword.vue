<template>
    <div class="custom_model">

        <div class="modal show" v-if="event_id > 0">
            <div class="modal-dialog modal-lg w-100">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5"> {{ trans('em.private_event') }}</h1>
                        <button type="button" class="btn btn-sm bg-danger text-white close" @click="close()"><span aria-hidden="true">&times;</span></button>
                    </div>
                    
                    <div class="modal-body">
                        <form ref="form" @submit.prevent="validateForm" method="POST" enctype="multipart/form-data">
                            <input  type="hidden" class="form-control"  name="event_id" :value="event_id">
                            
                            <div class="modal-body">
                                <div class="mb-3">
                                    <input type="checkbox" class="form-check-input" :value=1 name="is_private" v-model="is_private">
                                    <label class="form-label" > &nbsp;&nbsp;{{ trans('em.is_private') }}</label>
                                </div>

                                    <div class="mb-3">
                                    <label class="form-label" for="event_password">{{ trans('em.event')+' '+trans('em.password') }}</label>
                                    <input type="text" class="form-control" v-model="event_password" name="event_password" >
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-sd-card"></i> {{ trans('em.save') }}</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>

<script>



import mixinsFilters from '../../../../../../../eventmie-pro/resources/js/mixins.js';

export default {

    

    props: ["event_id", 'event'],

    mixins:[
        mixinsFilters
    ],

    data() {
        return {
           event_password : null,
           is_private     : false
           
        }
    },


    methods: {

        // reset form and close modal
        close: function () {
            this.$refs.form.reset();
            this.$parent.event_id = 0;
            
            
        },

        edit(){
            console.log('hello');
            this.event_password   = this.event.event_password;
            this.is_private       = this.event.is_private;
            
        },

        
        // validate data on form submit
        validateForm(event) {
            this.$validator.validateAll().then((result) => {
                if (result) {
                    this.formSubmit(event);            
                }
            });
        },

        // show server validation errors
        serverValidate(serrors) {
            this.$validator.validateAll().then((result) => {
                this.$validator.errors.add(serrors);
            });
        },

        // submit form
        formSubmit(event) {

            // prepare form data for post request
            let post_url = route('private_event');
            let post_data = new FormData(this.$refs.form);
            
            // axios post request
            axios.post(post_url, post_data)
            .then(res => {
                
                this.close();
                this.showNotification('success',  trans('em.event')+'  '+trans('em.password')+' '+trans('em.saved')+' '+trans('em.successfully'));
                // reload page   
                setTimeout(function() {
                    location.reload(true);
                }, 1000);
            
            })
            .catch(error => {
                let serrors = Vue.helpers.axiosErrors(error);
                if (serrors.length) {
                    this.serverValidate(serrors);
                }
            });
        },

        
    },

    mounted(){
       if(this.event_id > 0) {
            this.edit();
            
        }
    }
}
</script>