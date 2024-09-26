<template>

    <div class="custom_model">

        <div class="modal show" v-if="add_attendee > 0" >
            <div class="modal-dialog modal-lg w-95 modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5"> {{ trans('em.create')+' '+trans('em.user') }}</h1>
                        <button type="button" class="btn btn-sm bg-danger text-white close" @click="close()"><span aria-hidden="true">&times;</span></button>

                    </div>
                    <div class="modal-body">
                        <form ref="form" @submit.prevent="validateForm" method="POST" enctype="multipart/form-data">

                            <div class="modal-body">

                                    <div class="mb-3">
                                    <label class="form-label" for="name">{{ trans('em.name') }}<sup>*</sup></label>
                                    <input type="text" class="form-control " v-model="name" name="name" v-validate="'required'">
                                    <span v-show="errors.has('name')" class="help text-danger">{{ errors.first('name') }}</span>
                                </div>

                                    <div class="mb-3">
                                    <label class="form-label" for="name">{{ trans('em.email') }}<sup>*</sup></label>
                                    <input type="text" class="form-control " v-model="email" name="email" v-validate="'required'">
                                    <span v-show="errors.has('email')" class="help text-danger">{{ errors.first('email') }}</span>
                                </div>

                                <div class="mb-3" >
                                    <label class="form-label"> {{ trans('em.phone') }}</label>
                                    <input type="text" class="form-control"  name="phone" v-model="phone" v-validate="'required'" v-if="is_twilio > 0">
                                    <input type="text" class="form-control"  name="phone" v-model="phone" v-else>
                                    <span v-show="errors.has('phone')" class="help text-danger">{{ errors.first('phone') }}</span>
                                </div>

                            </div>

                            <div class="modal-footer">
                                <button type="submit" :class="{ 'disabled' : disable }"  :disabled="disable" class="btn btn-primary"><i class="fas fa-sd-card"></i> {{ trans('em.save') }}</button>
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


    props: ["add_attendee"],

    mixins:[
        mixinsFilters
    ],

    data() {
        return {
           name     : null,
           email    : null,
           phone    : null,
           is_twilio : is_twilio,
           disable   : false
        }
    },


    methods: {

        // reset form and close modal
        close: function () {
            this.$refs.form.reset();
            this.$parent.add_attendee = 0;
            this.$parent.overflowHidden = false;
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

            // show loader
            this.showLoaderNotification(trans('em.processing'));

            // prepare form data for post request

            this.disable = true;
            // prepare form data for post request
            let post_url = route('add_attendee');
            let post_data = new FormData(this.$refs.form);

            // axios post request
            axios.post(post_url, post_data)
            .then(res => {

                this.close();
                this.showNotification('success',  trans('em.user')+' '+trans('em.saved')+' '+trans('em.successfully'));

                // CUSTOM
                // reload page
                // setTimeout(function() {
                //     location.reload(true);
                // }, 1000);
                if(res.data.status){

                    Swal.hideLoading();
                    this.disable = false;

                    this.$parent.customer = res.data.attendee;

                    console.log(this.$parent.options.length);
                    if(this.$parent.options.length > 0)
                        this.$parent.options.push(res.data.attendee);
                    else
                        this.$parent.options = res.data.customer_options;
                }else{
                    this.showNotification('error', res.data.message);

                    Swal.hideLoading();
                    this.disable = false;
                }

                //CUSTOM

            })
            .catch(error => {
                this.disable = false;
                let serrors = Vue.helpers.axiosErrors(error);
                if (serrors.length) {
                    this.serverValidate(serrors);
                }
            });
        },


    },

    mounted(){
        this.$parent.$refs.modal_custom.scrollTo(0, 0);
        this.$parent.overflowHidden      = true;
    }
}
</script>

<style>
    .modal-content{
        min-height: fit-content !important;
    }
</style>