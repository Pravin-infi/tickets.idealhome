<template>
    <div>
        <label class="form-label h6 mt-1 mb-0">#{{ quantity_index + 1 }} {{ trans('em.attendee') }}</label>
        <div class="row g-2">
            <div class="col-md-3">
                <input type="text" class="form-control form-control-sm border-2" style="height: auto !important;"
                    :name="'fname_' + ticket_index + '_' + quantity_index"
                    v-model="$parent.fname[ticket_index][quantity_index]" v-validate="'required'"
                    @input="updateName('fname', $parent.fname[ticket_index][quantity_index], ticket_index, quantity_index)" placeholder="First Name*">
            </div>

            <div class="col-md-3">
                <input type="text" class="form-control form-control-sm border-2" style="height: auto !important;"
                    :name="'lname_' + ticket_index + '_' + quantity_index"
                    v-model="$parent.lname[ticket_index][quantity_index]" v-validate="'required'"
                    @input="updateName('lname', $parent.lname[ticket_index][quantity_index], ticket_index, quantity_index)" placeholder="Last Name*">
            </div>



            <div class="col-md-3" v-if = "quantity_index < 1">
                <textarea class="form-control form-control-sm border-2" rows="1" style="height: auto !important;"
                    :name="'address_' + ticket_index + '_' + quantity_index"
                    v-model="$parent.address[ticket_index][quantity_index]" v-validate="'required'"
                    @input="updateName('email', $parent.address[ticket_index][quantity_index], ticket_index, quantity_index)"
                    :placeholder="trans('em.email') + '*'"></textarea>
            </div>
            <div class="col-md-3" v-if = "quantity_index < 1">
                <input type="text" class="form-control form-control-sm border-2" style="height: auto !important;"
                    :name="'phone_' + ticket_index + '_' + quantity_index"
                    v-model="$parent.phone[ticket_index][quantity_index]"
                    :placeholder="trans('em.phone') + '(Optional)'">
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span
                    v-show="errors.has('name_' + ticket_index + '_' + quantity_index) || errors.has('name_' + ticket_index + '_' + quantity_index) || errors.has('address_' + ticket_index + '_' + quantity_index)"
                    class="help text-danger">{{ trans('em.attendee') }} {{ trans('em.required') }}</span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    inject: ['$validator'],

    props: ['quantity_index', 'ticket_index'],
    data() {
        return {
            attendeeName: ''
        }
    },

    methods: {
        updateName(attr, value, ticket_index, quantity_index) {
            if (quantity_index === 0) {
                let name = this.$parent.fname[ticket_index][quantity_index] + ' ' +this.$parent.lname[ticket_index][quantity_index];
                let email = this.$parent.address[ticket_index][quantity_index];

                this.$emit('update-name', {
                    name: attr === 'name' ? value : name,
                    email: attr === 'email' ? value : email
                });
            }
        }

    },
    beforeDestroy() {
        // remove value from array on addendees dropdown decrement
        this.$parent.fname[this.ticket_index].splice(-1, 1);
        this.$parent.lname[this.ticket_index].splice(-1, 1);
        this.$parent.phone[this.ticket_index].splice(-1, 1);
        this.$parent.address[this.ticket_index].splice(-1, 1);
    }

}
</script>