<template>
    <div>
        <div id="stripe" class="tab-content">

            <div class="tab-pane fade show active px-5" v-if="!user.stripe_account_id">
                <label class="col-md-3">{{
                trans("em.stripe_account")
                }}</label>
                <div class="col-md-9">
                    <a :href="createStripeAccount()" class="lgx-btn lgx-btn-black btn-lg"><i
                            class="fab fa-stripe"></i>
                        {{ trans("em.connect_stripe") }}</a>
                </div>
            </div>

            <div class="form-group row p-5" v-else>
                <label class="col-md-3">{{
                trans("em.stripe_account")
                }}</label>
                <div class="col-md-9">
                    <button type="button" class="btn btn-success btn-lg disabled mb-3" disabled>
                        <i class="fab fa-stripe"></i> Stripe
                        Connected
                        <i class="fas fa-check-circle"></i>
                    </button>
                    <p class="help-block">
                        NOTE: Your Stripe account must have at least
                        one of the following capabilities enabled:
                        <strong>transfers</strong> or
                        <strong>legacy_payments</strong> to start
                        receiving payouts.
                        <a target="_blank"
                            href="https://stripe.com/docs/connect/required-verification-information#US-full-individual--card_payments|transfers">Visit
                            here for more info</a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
import mixinsFilters from '../../../../mixins.js';
export default {
    props: ["user", "csrf_token"],

    mixins: [mixinsFilters],
    data() {
        return {
            is_organiser : is_organiser,
        };
    },
    methods: {

        // Create Stripe Account
        createStripeAccount() {
            return route("connect_stripe");
        },
    },
    mounted() {
    }
};
</script>
