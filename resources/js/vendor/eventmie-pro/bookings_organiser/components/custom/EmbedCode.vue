<template>
    <div class="custom_model">
        <div class="modal show" v-if="embed_code_modal > 0">
            <div class="modal-dialog modal-lg w-100">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">{{ trans('em.online_event') }} </h1>
                        <button type="button" class="btn btn-sm bg-danger text-white close" @click="close()"><span aria-hidden="true">&times;</span></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="iframe-full" v-html="embed_code"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div v-html="online_location"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
     props: ["embed_code_modal","embed_code", "online_location"],

    data() {
        return {
            
        }
    },

    methods: {
        
        // reset form and close modal
        close: function () {
            this.$parent.embed_code_modal = 0;
            this.$parent.embed_code       = null;
            
        },

        
    },

    mounted(){
       
    }
}
</script>