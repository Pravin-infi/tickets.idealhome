<template>
    <div>
        <div id="organiser" class="tab-pane">
            <div class="panel-group">
                <div class="panel panel-default lgx-panel">
                    <div class="panel-heading px-5">
                        
                        <div class="alert alert-info" role="alert" v-if="!is_organiser && user.organisation != null && user.organisation != undefined && manually_approve_organizer">
                            <strong> {{ trans('em.become_organiser_notification') }} </strong> 
                        </div>

                        <div class="card border-0 shadow-sm bg-light my-3">
                            <div class="card-body p-4 fs-5">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="card-title mb-0 text-primary">{{ trans('em.how_it_works') }}</h4>
                                </div>
                                <div class="d-flex">
                                    <div>
                                        <span><i class="fas fa-arrow-right text-primary"></i></span>
                                    </div>
                                    <div class="ms-3 lh-1">
                                        <h5 class="mb-1">{{ trans('em.organiser_note_1') }}</h5>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <div>
                                        <span><i class="fas fa-arrow-right text-primary"></i></span>
                                    </div>
                                    <div class="ms-3 lh-1">
                                        <h5 class="mb-1">{{ trans('em.organiser_note_2') }}</h5>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <div>
                                        <span><i class="fas fa-arrow-right text-primary"></i></span>
                                    </div>
                                    <div class="ms-3 lh-1">
                                        <h5 class="mb-1">{{ trans('em.organiser_note_3') }}</h5>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <div>
                                        <span><i class="fas fa-arrow-right text-primary"></i></span>
                                    </div>
                                    <div class="ms-3 lh-1">
                                        <h5 class="mb-1">{{ trans('em.organiser_note_4') }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <form class="form-horizontal" ref="form" :action="submitUrl()" @submit.prevent="validateForm"
                            method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="_token" id="csrf-token" :value="csrf_token" />

                            <div class="form-group row mb-3">
                                <label class="col-md-3 form-label">{{ trans("em.organization") }}
                                    {{ trans("em.name") }}*</label>
                                <div class="col-md-9">
                                    <input class="form-control" name="organisation" type="text" v-validate="'required'"
                                        v-model="organisation"  :disabled="!is_organiser && user.organisation != null && user.organisation != undefined"/>
                                    <span v-show="errors.has('organisation')" class="help text-danger">{{
                                    errors.first("organisation")
                                    }}</span>
                                </div>
                            </div>

                            <div class="row mb-3" v-if="is_organiser">
                                <label class="col-md-3">{{ trans('em.facebook') }}</label>
                                <div class="col-md-9">
                                    <input class="form-control" name="org_facebook" type="text"
                                        placeholder="e.g. www.facebook.com/YourPage" v-model="org_facebook" />
                                    <span v-show="errors.has('org_facebook')" class="help text-danger">{{
                                    errors.first("org_facebook")
                                    }}</span>
                                </div>
                            </div>

                            <div class="row mb-3" v-if="is_organiser">
                                <label class="col-md-3">{{ trans('em.instagram') }}</label>
                                <div class="col-md-9">
                                    <input class="form-control" name="org_instagram" type="text" v-model="org_instagram"
                                        placeholder="e.g. www.instagram.com/YourPage" />
                                    <span v-show="errors.has('org_instagram')" class="help text-danger">{{
                                    errors.first("org_instagram")
                                    }}</span>
                                </div>
                            </div>

                            <div class="row mb-3" v-if="is_organiser">
                                <label class="col-md-3">{{ trans('em.youtube') }}</label>
                                <div class="col-md-9">
                                    <input class="form-control" name="org_youtube" type="text" v-model="org_youtube"
                                        placeholder="e.g. www.youtube.com/channel/YourChannel" />
                                    <span v-show="errors.has('org_youtube')" class="help text-danger">{{
                                    errors.first("org_youtube") }}</span>
                                </div>
                            </div>

                            <div class="row mb-3" v-if="is_organiser">
                                <label class="col-md-3">{{ trans('em.twitter') }}</label>
                                <div class="col-md-9">
                                    <input class="form-control" name="org_twitter" type="text" v-model="org_twitter" placeholder="e.g. www.twitter.com/yourhandle" />
                                    <span v-show="errors.has('org_twitter')" class="help text-danger">{{ errors.first("org_twitter") }}</span>
                                </div>
                            </div>
                            
                            <div class="row mb-3" v-if="is_organiser">
                                <label class="col-md-3">{{ trans('em.website') }}</label>
                                <div class="col-md-9">
                                    <input class="form-control" name="org_website" type="text" v-model="org_website" placeholder="e.g. www.example.com" />
                                    <span v-show="errors.has('org_website')" class="help text-danger">{{ errors.first("org_website") }}</span>
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
            organisation: null,
            org_description: null,
            org_facebook: null,
            org_instagram: null,
            org_youtube: null,
            org_twitter: null,
            org_website: null,
            manually_approve_organizer: manually_approve_organizer,
            is_organiser : is_organiser,
        };
    },
    computed: {
        // get global variables
    },
    methods: {
        editProfile() {
            
            (this.organisation = this.user.organisation),
            (this.org_description = this.user.org_description),
            (this.org_facebook = this.user.org_facebook),
            (this.org_instagram = this.user.org_instagram),
            (this.org_youtube = this.user.org_youtube),
            (this.org_twitter = this.user.org_twitter);
            (this.org_website = this.user.org_website);
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
            return route("eventmie.updateOrganiserUser");
        },
    },
    mounted() {
        this.editProfile();
    }
};
</script>
