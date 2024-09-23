<template>
    <div>
        <div id="seller" class="tab-content">

            <div class="tab-pane fade show active px-5">
                <form class="form-horizontal" ref="form" :action="submitUrl()" @submit.prevent="validateForm"
                    method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="_token" id="csrf-token" :value="csrf_token" />

                    <div class="col-md-12 mb-5 text-center">
                        <img id="preview-image-signature" alt="profile-pic"
                            :src="'/storage/' + user.seller_signature"
                            style="max-height: 128px;border-radius: 50%;" />
                    </div>
                    <div class="form-group row mt-5">
                        <label class="col-md-3 form-label">{{ trans("em.seller_signature") }}*</label>
                        <div class="col-md-9">
                            <input @change="imagePreview" class="form-control" id="seller_signature" name="seller_signature"
                                type="file" />
                            <span v-show="errors.has('seller_signature')" class="help text-danger">{{
                            errors.first("seller_signature")
                            }}</span>
                        </div>
                    </div>
                    
                    <div class="form-group row mt-3">
                        <label class="col-md-3 form-label">{{
                        trans("em.seller_name")
                        }}</label>
                        <div class="col-md-9">
                            <input class="form-control" name="seller_name" type="text" v-model="seller_name" />
                            <span v-show="errors.has('seller_name')" class="help text-danger">{{
                            errors.first("seller_name") }}</span>
                        </div>
                    </div>

                    <div class="form-group row mt-3">
                        <label class="col-md-3 form-label">{{
                        trans("em.seller_info")
                        }}</label>
                        <div class="col-md-9">
                            <textarea class="form-control" name="seller_info" v-model="seller_info"></textarea>
                            <span v-show="errors.has('seller_info')" class="help text-danger">{{
                            errors.first("seller_info") }}</span>
                        </div>
                    </div>

                    <div class="form-group row mt-3">
                        <label class="col-md-3 form-label">{{
                        trans("em.seller_tax_info")
                        }}</label>
                        <div class="col-md-9">
                            <textarea class="form-control" name="seller_tax_info" type="text" placeholder=""
                                v-model="seller_tax_info"></textarea>
                            <span v-show="errors.has('seller_tax_info')" class="help text-danger">{{
                            errors.first("seller_tax_info")
                            }}</span>
                        </div>
                    </div>

                    <div class="form-group row mt-3">
                        <label class="col-md-3 form-label">{{
                        trans("em.seller_note")
                        }}</label>
                        <div class="col-md-9">
                            <textarea class="form-control" name="seller_note" type="text" placeholder=""
                                v-model="seller_note"></textarea>
                            <span v-show="errors.has('seller_note')" class="help text-danger">{{
                            errors.first("seller_note") }}</span>
                        </div>
                    </div>

                    <div class="form-group row mt-3">
                        <div class="col-md-9 offset-md-3">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-sd-card"></i>
                                {{ trans("em.save") }}
                            </button>
                        </div>
                    </div>
                </form>
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
            seller_name: null,
            seller_info: null,
            seller_tax_info: null,
            seller_note: null
        };
    },
    methods: {
        editProfile() {
            (this.seller_name = this.user.seller_name),
                (this.seller_info = this.user.seller_info),
                (this.seller_tax_info = this.user.seller_tax_info),
                (this.seller_note = this.user.seller_note);
        },

        // validate data on form submit
        validateForm(event) {
            this.$validator.validateAll().then(result => {
                if (result) {
                    this.formSubmit(event);
                }
            });
        },

        // show server validation errors
        serverValidate(serrors) {
            this.$validator.validateAll().then(result => {
                this.$validator.errors.add(serrors);
            });
        },

        // submit form
        async formSubmit(event) {
            this.$refs.form.submit();
        },

        submitUrl() {
            return route("eventmie.updateSellerUser");
        },

        imagePreview(e) {

            const file = e.target.files[0];
            let url = URL.createObjectURL(file);

            $('#preview-image-signature').attr('src', url); 
           
        }
    },
    mounted() {
        this.editProfile();
    }
};
</script>
