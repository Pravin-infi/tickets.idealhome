<template>
    <div class="custom_model small-window-to-hidden">
        <div class="modal show" v-if="register_modal > 0">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" v-if="guest_checkout">{{ trans('em.checkout_as_guest') }}</h1>
                        <h1 class="modal-title fs-5" v-else>{{ !is_login ? trans('em.register') : trans('em.login') }}
                        </h1>
                        <button type="button" class="btn btn-sm bg-danger text-white close" @click="close()"><span
                                aria-hidden="true">&times;</span></button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-danger" v-if="is_customer_email != null">
                            <i class="fas fa-ban"></i> {{ trans('em.wrong_organiser_email_guest_checkout') }}
                        </div>
                        <form ref="form" @submit.prevent="validateForm" method="POST" enctype="multipart/form-data">
                            <div class="modal-body pb-0" v-show="guest_checkout ? !is_send_otp : true">

                                <input type="hidden" name="is_send_otp" v-model="is_send_otp">
                                <input type="hidden" :value="is_login ? 1 : 0" name="is_login">
                                <input type="hidden" :value="guest_checkout ? 1 : 0" name="guest_checkout">

                                <div class="mb-3" v-show="!is_login">
                                    <label class="form-label">{{ trans('em.name') }}</label>
                                    <input type="text" class="form-control" name="name" v-model="name"
                                        v-validate="!is_login ? 'required' : ''">
                                    <span v-show="errors.has('name')" class="help text-danger">{{ errors.first('name')
                                        }}</span>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"> {{ trans('em.email') }}</label>
                                    <input type="text" class="form-control" name="email" v-model="email"
                                        v-validate="'required|email'">
                                    <span v-show="errors.has('email')" class="help text-danger">{{ errors.first('email')
                                        }}</span>
                                </div>

                                <!-- <div class="mb-3" v-show="!is_login">
                                    <label class="form-label"> {{ trans('em.phone') }}</label>
                                    <input type="text" class="form-control"  name="phone" v-model="phone" v-validate="!is_login ? 'required' : '' " v-if="is_twilio > 0">
                                    <input type="text" class="form-control"  name="phone" v-model="phone" v-else>
                                    <span v-show="errors.has('phone')" class="help text-danger">{{ errors.first('phone') }}</span>
                                </div> -->

                                <div class="mb-2" v-show="!guest_checkout">
                                    <label class="form-label">{{ trans('em.password') }}</label>
                                    <input type="password" class="form-control" name="password" v-model="password"
                                        v-validate="!guest_checkout ? 'required' : ''">
                                    <span v-show="errors.has('password')" class="help text-danger">{{
                                        errors.first('password') }}</span>
                                    <a v-show="is_login" :href="goto_route()" class="p-0">{{ trans('em.forgot_password')
                                        }}</a>
                                </div>

                            </div>

                            <div class="modal-body pb-0" v-if="is_send_otp && guest_checkout && is_otp_verify != null">
                                <div class="mb-2">
                                    <label class="form-label"> {{ trans('em.otp') }} </label>
                                    <input type="text" class="form-control" name="otp"
                                        v-validate="is_send_otp && guest_checkout ? 'required' : ''">
                                    <span v-show="errors.has('otp')" class="help text-danger">{{ errors.first('otp')
                                        }}</span>
                                    <p class="help-block "><a class="btn btn-link pull-left text-primary"
                                            href="javascript:void(0)" v-if="resendTimeout > 0">Resend OTP in {{
                                                resendTimeout }} seconds</a></p>
                                    <p class="help-block "><a class="btn btn-link pull-left text-primary"
                                            href="javascript:void(0)" @click="sendOtp" v-if="resendTimeout <= 0">{{
                                                trans('em.dont_receive_otp') }}</a></p>
                                </div>
                            </div>

                            <div class="modal-footer border-0 pt-0">

                                <button v-if="is_otp_verify == null && guest_checkout" type="submit"
                                    :class="{ 'disabled': disable }" :disabled="disable"
                                    class="btn btn-primary w-100 vimar-footer-small-window-click"><i class="fas fa-check"></i> {{ trans('em.continue')
                                    }}</button>

                                <button v-if="!is_send_otp && guest_checkout && is_otp_verify != null" type="button"
                                    @click="sendOtp" :class="{ 'disabled': disable }" :disabled="disable"
                                    class="btn btn-primary w-100 vimar-footer-small-window-click"><i class="fas fa-check"></i> {{ trans('em.continue')
                                    }}</button>

                                <button v-if="is_send_otp || !guest_checkout" type="submit"
                                    :class="{ 'disabled': disable }" :disabled="disable"
                                    class="btn btn-primary w-100 vimar-footer-small-window-click"><i class="fas fa-check"></i> {{ trans('em.continue')
                                    }}</button>

                                <div class="w-100">
                                    <div class="d-flex justify-content-between">
                                        <a class="small text-primary" type="button"
                                            @click.prevent="is_login = is_login ? false : true; guest_checkout = false">{{
                                                !is_login ? trans('em.already_have_account') + trans('em.login') :
                                                    trans('em.dont_have_account') + trans('em.register') }}</a>

                                        <a v-if="!guest_checkout && is_guest_checkout != null"
                                            class="small text-primary" type="button"
                                            @click.prevent="guest_checkout = true; is_login = false">{{
                                                trans('em.checkout_as_guest') }}</a>
                                    </div>
                                </div>

                            </div>

                            <div class="alert alert-info" role="alert" v-if="guest_checkout && is_send_otp">
                                {{ trans('em.guset_info') }}
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

import VeeValidate from "vee-validate";
Vue.use(VeeValidate);


export default {
    props: ["register_modal", "attendees"],

    mixins: [
        mixinsFilters
    ],

    data() {
        return {
            name: this.$parent.attendees.name,
            email: this.$parent.attendees.email,
            is_twilio: is_twilio,
            //phone     : '',
            password: '',
            disable: false,
            is_login: false,
            guest_checkout: true,
            is_send_otp: false,
            resendTimeout: 0,
            is_customer_email: null,
            is_otp_verify: is_otp_verify,
            is_guest_checkout: is_guest_checkout



        }
    },

    methods: {
        // reset form and close modal
        close: function () {
            this.$parent.register_modal = 0;
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
            let _this = this;
            let post_url = route('guest.register');
            let post_data = new FormData(this.$refs.form);

            // axios post request
            axios.post(post_url, post_data)
                .then(res => {
                    // on success
                    // use vuex to update global sponsors array
                    if (res.data.status) {
                        this.is_customer_email = null;
                        Swal.hideLoading();
                        Swal.close();
                        this.disable = false;

                        var promise = new Promise(function (resolve, reject) {
                            _this.$parent.register_user_id = res.data.user.id;
                            _this.$parent.stripe_secret_key = res.data.stripe_secret_key;
                            _this.$parent.$parent.is_customer = res.data.is_customer

                            resolve();

                        });

                        promise.
                            then(function () {

                                // _this.showNotification('success', trans('em.user')+' '+( _this.is_login ? trans('em.login') : trans('em.register'))+' '+trans('em.successfully'));
                                if (res.data.is_verify_email && !res.data.verify_email) {
                                    // _this.showNotification('success', trans('em.email_info'));

                                } else {
                                    // _this.showNotification('success', trans('em.user')+' '+( _this.is_login ? trans('em.login') : trans('em.register'))+' '+trans('em.successfully'));

                                    _this.is_customer_email = null;

                                    setTimeout(function () {

                                        if (_this.$parent.is_seatchart)
                                            window.location.reload();
                                        else
                                            _this.$parent.validateForm();
                                    }, 1000);



                                }
                                _this.close();
                            }).
                            catch(function (error) {
                                console.log(error);
                                console.log('Some error has occured');
                            });


                    }

                })
                .catch(error => {

                    Swal.hideLoading();
                    this.disable = false;

                    let serrors = Vue.helpers.axiosErrors(error);

                    if (serrors.length) {
                        this.serverValidate(serrors);
                        this.is_customer_email = serrors.find(obj => obj.field == 'password').msg == 'Allow only customer' ? 'Only Customer Can purchase Membership' : null;
                    }


                });
        },

        goto_route() {
            return route('eventmie.password.request');
        },

        sendOtp(event) {
            // show loader
            this.showLoaderNotification(trans('em.processing'));

            // prepare form data for post request


            // prepare form data for post request
            let _this = this;
            let post_url = route('guest.send_otp');



            let post_data = new FormData(this.$refs.form);

            // axios post request
            axios.post(post_url, post_data)
                .then(res => {

                    this.is_customer_email = null;
                    console.log(res.data);
                    this.errors.clear();
                    this.password = 'password';

                    // on success
                    // use vuex to update global sponsors array

                    Swal.close();
                    Swal.close();

                    _this.is_send_otp = true;
                    _this.startResendCountdown();


                    _this.showNotification('success', trans('em.send_otp'));

                })
                .catch(error => {

                    Swal.close();

                    let serrors = error;

                    if (serrors.length) {
                        this.serverValidate(serrors);

                        this.is_customer_email = serrors.find(obj => obj.field == 'password').msg == 'Allow only customer' ? 'Only Customer Can purchase Membership' : null;
                    }


                });
        },

        startResendCountdown() {
            this.resendTimeout = 30;
            const countdown = setInterval(() => {
                if (this.resendTimeout > 0) {
                    this.resendTimeout--;
                } else {
                    clearInterval(countdown);
                }
            }, 1000);
        }

    },

    mounted() {

        this.$parent.$refs.modal_custom.scrollTo(0, 0);
        this.$parent.overflowHidden = true;
    },

    watch: {
        guest_checkout: function () {
            // this.this.$parent.name[0][0]
        },

        is_login: function () {

        },
    }



}
</script>