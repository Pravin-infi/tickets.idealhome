<template>
    <div class="custom_model">
        <div class="modal show" ref="modal_custom" :class="{ 'overflow-hidden': overflowHidden }">
            <div class="modal-dialog modal-xl model-extra-large">
                <div class="modal-content">
                    <div class="modal-header text-center border-0">
                        <div class="modal-title w-100">
                            <div>
                                <h5 class="mb-0 h3">{{ event.title }}</h5>
								<!-- vimar changed
                                <ul class="list-group list-group-horizontal list-group-flush justify-content-center">
                                    <li class="list-group-item text-sm border-0 px-0 pe-sm-3">
                                        <small v-if="event.online_location"><i class="fas fa-signal text-primary"></i>
                                            {{ trans("em.online") }}</small>
                                        <small v-else><i class="fas fa-location-dot text-primary"></i>
                                            {{ event.venue }}</small>
                                        &nbsp;
                                    </li>
                                    <li class="list-group-item text-sm border-0 px-0 pe-sm-3">
                                        <small><i class="fas fa-calendar-day text-primary"></i>
                                            {{
                                                serverTimezone(
                                                    moment(booking_date).format(
                                                        "dddd LL"
                                                    ) +
                                                    " " +
                                                    start_time,
                                                    "dddd LL HH:mm a"
                                                )
                                                    .locale("en")
                                                    .format("DD-MM-Y")
                                            }}</small>
                                        &nbsp;
                                    </li>
                                    <li class="list-group-item text-sm border-0 px-0 pe-sm-3">
                                        <small v-if="event.repetitive > 0"><i class="fas fa-clock text-primary"></i>
                                            {{ start_time }}
                                            {{ showTimezone() }}</small>
                                        <small v-else><i class="fas fa-clock text-primary"></i>
                                            {{ changeTimeFormat(start_time) }}
                                            {{ showTimezone() }}</small>
                                    </li>
                                </ul>
								-->
                            </div>
                        </div>

                        <button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close"
                            @click="close()"></button>
                    </div>
					
                    <div class="modal-body">
						<!-- vimar change
                        <div class="alert alert-primary mx-lg-3" v-if="show_checkout_timer">
                            <div class="text-sm d-flex flex-wrap justify-content-between">
                                <div>
                                    <p class="fw-bold text-primary m-0">
                                        {{ trans("em.checkout_timer") }}
                                    </p>
                                </div>

                                <div>
                                    <i class="fas fa-clock fa-spin text-danger"></i>
                                    <span class="fw-bold text-primary">
                                        <vue-countdown :time="480000" v-slot="{ minutes, seconds }">
                                            {{ minutes }} : {{ seconds }}
                                            {{ trans("em.minutes") }}
                                        </vue-countdown>
                                    </span>
                                </div>
                            </div>
                        </div>
						-->
                        <form ref="form" @submit.prevent="validateForm" method="POST">
                            <!-- CUSTOM -->
                            <div class="px-lg-3" style="display: none;">
                                <div class="form-group" v-if="is_admin > 0">
                                    <input type="checkbox" class="custom-control-input" :value="1" name="is_bulk"
                                        v-model="is_bulk" />
                                    <label class="form-label h6">{{
                                        trans("em.complimentary_bookings")
                                        }}</label>
                                </div>
                            </div>
                            <!-- CUSTOM -->

                            <input type="hidden" class="form-control" name="event_id" :value="tickets[0].event_id" />
                            <input type="hidden" class="form-control" name="booking_date" :value="serverTimezone(
                                moment(booking_date).format('dddd LL') +
                                ' ' +
                                start_time,
                                'dddd LL HH:mm a'
                            )
                                .locale('en')
                                .format('YYYY-MM-DD')
                                " />

                            <input type="hidden" class="form-control" name="booking_end_date" :value="booking_end_date != null &&
                                typeof booking_end_date != 'undefined' &&
                                booking_end_date != false
                                ? serverTimezone(
                                    moment(booking_date).format(
                                        'dddd LL'
                                    ) +
                                    ' ' +
                                    end_time,
                                    'dddd LL HH:mm a'
                                )
                                    .locale('en')
                                    .format('YYYY-MM-DD')
                                : null
                                " v-if="!event.merge_schedule" />
                            <input type="hidden" class="form-control" name="booking_end_date" :value="booking_end_date != null &&
                                typeof booking_end_date != 'undefined' &&
                                booking_end_date != false
                                ? serverTimezone(
                                    moment(booking_end_date).format(
                                        'dddd LL'
                                    ) +
                                    ' ' +
                                    end_time,
                                    'dddd LL HH:mm a'
                                )
                                    .locale('en')
                                    .format('YYYY-MM-DD')
                                : null
                                " v-else />

                            <input type="hidden" class="form-control" name="start_time" :value="serverTimezone(
                                moment(booking_date).format('dddd LL') +
                                ' ' +
                                start_time,
                                'dddd LL HH:mm a'
                            )
                                .locale('en')
                                .format('HH:mm:ss')
                                " />

                            <input type="hidden" class="form-control" name="end_time" :value="serverTimezone(
                                (booking_end_date == null ||
                                    booking_end_date == false
                                    ? moment(booking_date).format(
                                        'dddd LL'
                                    )
                                    : moment(booking_end_date).format(
                                        'dddd LL'
                                    )) +
                                ' ' +
                                end_time,
                                'dddd LL HH:mm a'
                            )
                                .locale('en')
                                .format('HH:mm:ss')
                                " />

                            <input type="hidden" class="form-control" name="merge_schedule"
                                :value="event.merge_schedule" />
                            <input type="hidden" name="customer_id" v-model="customer_id" v-validate="'required'" />

                            <div class="px-lg-3">
                                <div class="row" v-if=" is_customer <= 0 && is_bulk <= 0 && is_pos <= 0 " style="display:none">
                                    <!-- CUSTOM -->
                                    <div class="col-md-4 mb-3">
                                        <label for="customer_id" class="form-label h6">{{ trans("em.add") }}
                                            {{ trans("em.new") }}
                                            {{ trans("em.attendee") }}</label><br />
                                        <button class="btn  btn-dark btn-sm" type="button" @click="add_attendee = 1">
                                            <i class="fas fa-user-plus"></i>
                                            {{
                                                trans("em.create") +
                                                " " +
                                                trans("em.attendee")
                                            }}
                                        </button>
                                        <!-- add attendee -->
                                        <add-attendee v-if="add_attendee > 0"
                                            :add_attendee="add_attendee"></add-attendee>
                                    </div>
                                    <!-- CUSTOM -->
                                </div>

                                <!-- CUSTOM -->
                                <div class="row" v-if="is_twilio > 0">
                                    <input type="hidden" class="form-control" name="phone_t" v-model="phone_t" />
                                    <div class="col-4 mb-3" v-if="
                                        customer != null &&
                                        customer.phone == null
                                    ">
                                        <label>{{
                                            trans("em.phone") +
                                            "(" +
                                            trans("em.required") +
                                            ")"
                                        }}</label>
                                        <input type="text" class="form-control" name="phone_t" v-model="phone_t"
                                            v-validate="'required'" :placeholder="trans('em.phone_info')
                                                " />
                                        <span v-show="errors.has('phone_t')" class="help text-danger">{{
                                            errors.first("phone_t") }}</span>
                                    </div>
                                </div>
                                <!-- CUSTOM -->

                                <div class="row">
                                    <!-- only for admin & organizer -->
                                    <!-- <div class="col-md-12 mb-5" v-if="is_customer <= 0"> -->

                                    <!-- CUSTOM -->
                                    <div class="col-md-4 mb-3" style="display: none;" v-if="is_customer <= 0 && is_bulk <= 0">
                                        <!-- CUSTOM -->

                                        <label for="customer_id" class="form-label h6">{{
                                            trans("em.select_customer")
                                        }}</label>

                                        <v-select label="name" class="style-chooser" :placeholder="trans(
                                            'em.search_customer_email'
                                        )
                                            " v-model="customer" :required="!customer" :filterable="false"
                                            :options="options" @search="onSearch">
                                            <div slot="no-options">
                                                {{
                                                    trans(
                                                        "em.customer_not_found"
                                                    )
                                                }}
                                            </div>
                                        </v-select>

                                        <div class="invalid-feedback danger" v-show="errors.has('customer_id')">
                                            {{ errors.first("customer_id") }}
                                        </div>
                                    </div>

                                    <!-- Tickets -->
                                    <div class="col-12">
                                        <p class="mb-2 h6">
                                            {{ trans("em.tickets") }}
                                        </p>

                                        <ul class="list-group">
                                            <!-- <li class="list-group-item d-flex  justify-content-between lh-condensed d-flex-wrap"
                                                v-for="(item, index) in tickets"
                                                :key = "index"
                                            > -->
                                            <!-- CUSTOM -->

                                            <li class="list-group-item mb-3 rounded border-2"
                                                v-for="(item, index) in tickets" :key="index">



                                                <div class="badge bg-gradient col-md-8 col-lg-4 mb-2" v-if="
                                                    item.sale_start_date !=
                                                    null
                                                ">
                                                    <div class="text-sm d-flex flex-wrap justify-content-between">
                                                        <div v-if="
                                                            userTimezone(
                                                                item.sale_start_date,
                                                                'YYYY-MM-DD HH:mm:ss'
                                                            ).format(
                                                                'YYYY-MM-DD HH:mm:ss'
                                                            ) <=
                                                            moment()
                                                                .tz(
                                                                    Intl.DateTimeFormat().resolvedOptions()
                                                                        .timeZone
                                                                )
                                                                .format(
                                                                    'YYYY-MM-DD HH:mm:ss'
                                                                ) &&
                                                            userTimezone(
                                                                item.sale_end_date,
                                                                'YYYY-MM-DD HH:mm:ss'
                                                            ).format(
                                                                'YYYY-MM-DD HH:mm:ss'
                                                            ) >
                                                            moment()
                                                                .tz(
                                                                    Intl.DateTimeFormat().resolvedOptions()
                                                                        .timeZone
                                                                )
                                                                .format(
                                                                    'YYYY-MM-DD HH:mm:ss'
                                                                )
                                                        ">
                                                            <span class="font-weight-semi-bold text-white">
                                                                {{
                                                                    trans(
                                                                        "em.on_sale"
                                                                    )
                                                                }}
                                                            </span>
                                                        </div>

                                                        <div v-if="
                                                            userTimezone(
                                                                item.sale_start_date,
                                                                'YYYY-MM-DD HH:mm:ss'
                                                            ).format(
                                                                'YYYY-MM-DD HH:mm:ss'
                                                            ) <=
                                                            moment()
                                                                .tz(
                                                                    Intl.DateTimeFormat().resolvedOptions()
                                                                        .timeZone
                                                                )
                                                                .format(
                                                                    'YYYY-MM-DD HH:mm:ss'
                                                                ) &&
                                                            userTimezone(
                                                                item.sale_end_date,
                                                                'YYYY-MM-DD HH:mm:ss'
                                                            ).format(
                                                                'YYYY-MM-DD HH:mm:ss'
                                                            ) >
                                                            moment()
                                                                .tz(
                                                                    Intl.DateTimeFormat().resolvedOptions()
                                                                        .timeZone
                                                                )
                                                                .format(
                                                                    'YYYY-MM-DD HH:mm:ss'
                                                                )
                                                        ">
                                                            <i class="fas fa-clock fa-spin text-danger"></i>
                                                            <span class="font-weight-semi-bold fw-light text-white">
                                                                <vue-countdown :time="timerOnSale(
                                                                    item.sale_start_date,
                                                                    item.sale_end_date
                                                                )
                                                                    " v-slot="{
                                                                        days,
                                                                        hours,
                                                                        minutes,
                                                                        seconds
                                                                    }">
                                                                    {{ days }}
                                                                    {{
                                                                        trans(
                                                                            "em.days"
                                                                        )
                                                                    }},
                                                                    {{
                                                                        hours
                                                                    }}
                                                                    :
                                                                    {{
                                                                        minutes
                                                                    }}
                                                                    :
                                                                    {{
                                                                        seconds
                                                                    }}
                                                                    {{
                                                                        trans(
                                                                            "em.left"
                                                                        )
                                                                    }}
                                                                </vue-countdown>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- CUSTOM -->
                                                <input type="hidden" class="form-control" name="ticket_id[]"
                                                    :value="item.id" />
                                                <input type="hidden" class="form-control" name="ticket_title[]"
                                                    :value="item.title" />

                                                <div class="d-flex justify-content-between lh-condensed d-flex-wrap">
                                                    <div class="w-50">
                                                        <h6 class="my-0 ticket-title">
                                                            <strong>{{ item.title }}</strong>
                                                        </h6>
                                                        <p class="my-0 h6 ticket-price">
                                                            <!-- CUSTOM -->
                                                            <del v-if="
                                                                item.sale_price !=
                                                                null &&
                                                                userTimezone(
                                                                    item.sale_start_date,
                                                                    'YYYY-MM-DD HH:mm:ss'
                                                                ).format(
                                                                    'YYYY-MM-DD HH:mm:ss'
                                                                ) <=
                                                                moment()
                                                                    .tz(
                                                                        Intl.DateTimeFormat().resolvedOptions()
                                                                            .timeZone
                                                                    )
                                                                    .format(
                                                                        'YYYY-MM-DD HH:mm:ss'
                                                                    ) &&
                                                                userTimezone(
                                                                    item.sale_end_date,
                                                                    'YYYY-MM-DD HH:mm:ss'
                                                                ).format(
                                                                    'YYYY-MM-DD HH:mm:ss'
                                                                ) >
                                                                moment()
                                                                    .tz(
                                                                        Intl.DateTimeFormat().resolvedOptions()
                                                                            .timeZone
                                                                    )
                                                                    .format(
                                                                        'YYYY-MM-DD HH:mm:ss'
                                                                    )
                                                            ">
                                                                <span v-if="item.old_price">
                                                                    {{
                                                                        item.old_price >
                                                                            0
                                                                            ? item.old_price
                                                                            : "0.00"
                                                                    }}
                                                                </span>
                                                                <span v-else>
                                                                    {{
                                                                        item.price > 0
                                                                            ? item.price
                                                                            : "0.00"
                                                                    }}
                                                                </span>
                                                                <small>{{
                                                                    event.currency !=
                                                                        null
                                                                        ? event.currency
                                                                        : currency
                                                                }}</small>
                                                            </del>
                                                            <!-- CUSTOM -->
                                                            <span v-if="item.old_price">
                                                                {{
                                                                    item.price > 0
                                                                        ? item.price
                                                                        : "0.00"
                                                                }}
                                                            </span>
                                                            <span v-else>
                                                                {{
                                                                    item.sale_price ? (item.sale_price > 0
                                                                        ? item.sale_price
                                                                        : "0.00") : item.price
                                                                }}
                                                            </span>


                                                            <!-- <small>{{currency}}</small> -->
                                                            <!-- CUSTOM -->
                                                            <small>{{
                                                                event.currency !=
                                                                    null
                                                                    ? event.currency
                                                                    : currency
                                                            }}</small>
                                                            <!-- CUSTOM -->
                                                        </p>

                                                        <!-- show tax only if quantity is set -->
                                                        <!-- <div class="event-tax" v-if="quantity[index] > 0 && item.price > 0 && item.taxes.length > 0"> -->
                                                        <!-- CUSTOM -->
                                                    </div>

                                                    <div v-if="
                                                        item.t_soldout > 0
                                                    " class="w-25">
                                                        <p class="text-danger">
                                                            <small><i class="fas fa-times-circle"></i>
                                                                {{
                                                                    trans(
                                                                        "em.t_soldout"
                                                                    )
                                                                }}</small>
                                                        </p>
                                                    </div>

                                                    <div v-show="item.t_soldout <= 0
                                                        " class="w-25">
                                                        <!-- CUSTOM -->
                                                        <div v-if="
                                                            item.seatchart !=
                                                            null &&
                                                            item
                                                                .seatchart
                                                                .status >
                                                            0 &&
                                                            is_bulk <= 0
                                                        ">
                                                            <p>
                                                                {{
                                                                    trans(
                                                                        "em.quantity"
                                                                    )
                                                                }}:
                                                                {{
                                                                    quantity[
                                                                    index
                                                                    ]
                                                                }}
                                                            </p>
                                                        </div>

                                                        <!-- <div class="w-10 w-20-mobile"> -->
                                                        <!-- Hide quantity dropdown in case of reserved seating -->
                                                        <div v-if="
                                                            (item.seatchart ==
                                                                null ||
                                                                (item.seatchart !=
                                                                    null &&
                                                                    item
                                                                        .seatchart
                                                                        .status <=
                                                                    0)) &&
                                                            is_bulk <= 0
                                                        ">
                                                            <!-- CUSTOM -->

                                                            <!-- Live stock alert  -->
                                                            <!-- if any booked tickets -->
                                                            <div v-if="
                                                                typeof booked_tickets[
                                                                item.id +
                                                                '-' +
                                                                booked_date_server
                                                                ] !=
                                                                'undefined'
                                                            ">
                                                                <select class="form-select border-2 form-select-lg"
                                                                    name="quantity[]" v-model="quantity[
                                                                        index
                                                                    ]
                                                                        " v-if="
                                                                            (item.customer_limit !=
                                                                                null
                                                                                ? item.customer_limit
                                                                                : max_ticket_qty) <=
                                                                            100
                                                                        ">
                                                                    <option value="0" selected>0</option>
                                                                    <option :key="ind
                                                                        " v-if="
                                                                            booked_tickets[
                                                                                item.id +
                                                                                '-' +
                                                                                booked_date_server
                                                                            ]
                                                                                .total_vacant <=
                                                                            (item.customer_limit !=
                                                                                null
                                                                                ? item.customer_limit
                                                                                : max_ticket_qty)
                                                                        " :value="itm
                                                                            " v-for="(itm,
                                                                                ind) in booked_tickets[
                                                                                    item.id +
                                                                                    '-' +
                                                                                    booked_date_server
                                                                                ]
                                                                                    .total_vacant">{{
                                                                                        itm
                                                                                    }}</option>
                                                                    <option v-else :value="itm
                                                                        " v-for="(itm,
                                                                            ind) in item.quantity >
                                                                                (item.customer_limit !=
                                                                                    null
                                                                                    ? item.customer_limit
                                                                                    : max_ticket_qty)
                                                                                ? parseInt(
                                                                                    item.customer_limit !=
                                                                                        null
                                                                                        ? item.customer_limit
                                                                                        : max_ticket_qty
                                                                                )
                                                                                : parseInt(
                                                                                    item.quantity
                                                                                )" :key="ind
                                                                                    ">{{
                                                                                        itm
                                                                                    }}</option>
                                                                </select>
                                                                <input v-else type="number" name="quantity[]" v-model="quantity[
                                                                    index
                                                                ]
                                                                    " value="0" class="form-control form-input-sm"
                                                                    min="0" :max="booked_tickets[
                                                                        item.id +
                                                                        '-' +
                                                                        booked_date_server
                                                                    ]
                                                                        .total_vacant <
                                                                        (item.customer_limit !=
                                                                            null
                                                                            ? item.customer_limit
                                                                            : max_ticket_qty)
                                                                        ? booked_tickets[
                                                                            item.id +
                                                                            '-' +
                                                                            booked_date_server
                                                                        ]
                                                                            .total_vacant
                                                                        : item.customer_limit !=
                                                                            null
                                                                            ? item.customer_limit
                                                                            : max_ticket_qty
                                                                        " />
                                                                <!-- Show if vacant less than max_ticket_qty -->
                                                                <p class="text-dark" v-if="
                                                                    booked_tickets[
                                                                        item.id +
                                                                        '-' +
                                                                        booked_date_server
                                                                    ]
                                                                        .total_vacant <
                                                                    (item.customer_limit !=
                                                                        null
                                                                        ? item.customer_limit
                                                                        : max_ticket_qty) &&
                                                                    booked_tickets[
                                                                        item.id +
                                                                        '-' +
                                                                        booked_date_server
                                                                    ]
                                                                        .total_vacant >
                                                                    0 &&
                                                                    booked_tickets[
                                                                        item.id +
                                                                        '-' +
                                                                        booked_date_server
                                                                    ]
                                                                        .total_vacant <
                                                                    5
                                                                ">
                                                                    <small><i class="fas fa-exclamation"></i>
                                                                        {{
                                                                            trans(
                                                                                "em.vacant"
                                                                            )
                                                                        }}
                                                                        {{
                                                                            booked_tickets[
                                                                                item.id +
                                                                                "-" +
                                                                                booked_date_server
                                                                            ]
                                                                                .total_vacant
                                                                        }}</small>
                                                                </p>
                                                                <p class="text-danger" v-if="
                                                                    booked_tickets[
                                                                        item.id +
                                                                        '-' +
                                                                        booked_date_server
                                                                    ]
                                                                        .total_vacant <
                                                                    (item.customer_limit !=
                                                                        null
                                                                        ? item.customer_limit
                                                                        : max_ticket_qty) &&
                                                                    booked_tickets[
                                                                        item.id +
                                                                        '-' +
                                                                        booked_date_server
                                                                    ]
                                                                        .total_vacant <=
                                                                    0
                                                                ">
                                                                    <small><i class="fas fa-times-circle"></i>
                                                                        {{
                                                                            trans(
                                                                                "em.vacant"
                                                                            )
                                                                        }}
                                                                        0</small>
                                                                </p>
                                                            </div>
                                                            <div v-else>
                                                                <select class="form-select border-2 form-select-lg"
                                                                    name="quantity[]" v-model="quantity[
                                                                        index
                                                                    ]
                                                                        " v-if="
                                                                            (item.customer_limit !=
                                                                                null
                                                                                ? item.customer_limit
                                                                                : max_ticket_qty) <=
                                                                            100
                                                                        ">
                                                                    <option value="0" selected>0</option>
                                                                    <option :value="itm
                                                                        " v-for="(itm,
                                                                            ind) in item.quantity >
                                                                                (item.customer_limit !=
                                                                                    null
                                                                                    ? item.customer_limit
                                                                                    : max_ticket_qty)
                                                                                ? parseInt(
                                                                                    item.customer_limit !=
                                                                                        null
                                                                                        ? item.customer_limit
                                                                                        : max_ticket_qty
                                                                                )
                                                                                : parseInt(
                                                                                    item.quantity
                                                                                )" :key="ind
                                                                                    ">{{
                                                                                        itm
                                                                                    }}</option>
                                                                </select>
                                                                <input v-else type="number" name="quantity[]" v-model="quantity[
                                                                    index
                                                                ]
                                                                    " value="0" class="form-control form-input-sm"
                                                                    min="0" :max="item.quantity >
                                                                        (item.customer_limit !=
                                                                            null
                                                                            ? item.customer_limit
                                                                            : max_ticket_qty)
                                                                        ? parseInt(
                                                                            item.customer_limit !=
                                                                                null
                                                                                ? item.customer_limit
                                                                                : max_ticket_qty
                                                                        )
                                                                        : parseInt(
                                                                            item.quantity
                                                                        )
                                                                        " />
                                                                <!-- Show if vacant less than max_ticket_qty -->
                                                                <p class="text-dark" v-if="
                                                                    item.quantity <
                                                                    (item.customer_limit !=
                                                                        null
                                                                        ? item.customer_limit
                                                                        : max_ticket_qty) &&
                                                                    item.quantity >
                                                                    0 &&
                                                                    item.quantity <
                                                                    5
                                                                ">
                                                                    <small><i class="fas fa-exclamation"></i>
                                                                        {{
                                                                            trans(
                                                                                "em.vacant"
                                                                            )
                                                                        }}
                                                                        {{
                                                                            item.quantity
                                                                        }}</small>
                                                                </p>
                                                                <p class="text-danger" v-if="
                                                                    item.quantity <=
                                                                    0
                                                                ">
                                                                    <small><i class="fas fa-times-circle"></i>
                                                                        {{
                                                                            trans(
                                                                                "em.vacant"
                                                                            )
                                                                        }}
                                                                        0</small>
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <!-- CUSTOM -->
                                                        <!-- FOR BULK BOOKING -->
                                                        <div v-else>
                                                            <div v-if="
                                                                is_bulk > 0
                                                            ">
                                                                <!-- Live stock alert  -->
                                                                <!-- if any booked tickets -->
                                                                <div v-if="
                                                                    typeof booked_tickets[
                                                                    item.id +
                                                                    '-' +
                                                                    booked_date_server
                                                                    ] !=
                                                                    'undefined'
                                                                ">
                                                                    <input type="number" name="quantity[]" v-model="quantity[
                                                                        index
                                                                    ]
                                                                        " value="0" class="form-control form-input-sm"
                                                                        min="0" />
                                                                    <!-- Show if vacant less than max_ticket_qty -->
                                                                    <p class="text-dark" v-if="
                                                                        booked_tickets[
                                                                            item.id +
                                                                            '-' +
                                                                            booked_date_server
                                                                        ]
                                                                            .total_vacant <
                                                                        (item.customer_limit !=
                                                                            null
                                                                            ? item.customer_limit
                                                                            : max_ticket_qty) &&
                                                                        booked_tickets[
                                                                            item.id +
                                                                            '-' +
                                                                            booked_date_server
                                                                        ]
                                                                            .total_vacant >
                                                                        0 &&
                                                                        booked_tickets[
                                                                            item.id +
                                                                            '-' +
                                                                            booked_date_server
                                                                        ]
                                                                            .total_vacant <
                                                                        5
                                                                    ">
                                                                        <small><i class="fas fa-exclamation"></i>
                                                                            {{
                                                                                trans(
                                                                                    "em.vacant"
                                                                                )
                                                                            }}
                                                                            {{
                                                                                booked_tickets[
                                                                                    item.id +
                                                                                    "-" +
                                                                                    booked_date_server
                                                                                ]
                                                                                    .total_vacant
                                                                            }}</small>
                                                                    </p>
                                                                    <p class="text-danger" v-if="
                                                                        booked_tickets[
                                                                            item.id +
                                                                            '-' +
                                                                            booked_date_server
                                                                        ]
                                                                            .total_vacant <
                                                                        (item.customer_limit !=
                                                                            null
                                                                            ? item.customer_limit
                                                                            : max_ticket_qty) &&
                                                                        booked_tickets[
                                                                            item.id +
                                                                            '-' +
                                                                            booked_date_server
                                                                        ]
                                                                            .total_vacant <=
                                                                        0
                                                                    ">
                                                                        <small><i class="fas fa-times-circle"></i>
                                                                            {{
                                                                                trans(
                                                                                    "em.vacant"
                                                                                )
                                                                            }}
                                                                            0</small>
                                                                    </p>
                                                                </div>
                                                                <div v-else>
                                                                    <input type="number" name="quantity[]" v-model="quantity[
                                                                        index
                                                                    ]
                                                                        " value="0" class="form-control form-input-sm"
                                                                        min="0" />
                                                                    <!-- Show if vacant less than max_ticket_qty -->
                                                                    <p class="text-dark" v-if="
                                                                        item.quantity <
                                                                        (item.customer_limit !=
                                                                            null
                                                                            ? item.customer_limit
                                                                            : max_ticket_qty) &&
                                                                        item.quantity >
                                                                        0 &&
                                                                        item.quantity <
                                                                        5
                                                                    ">
                                                                        <small><i class="fas fa-exclamation"></i>
                                                                            {{
                                                                                trans(
                                                                                    "em.vacant"
                                                                                )
                                                                            }}
                                                                            {{
                                                                                item.quantity
                                                                            }}</small>
                                                                    </p>
                                                                    <p class="text-danger" v-if="
                                                                        item.quantity <=
                                                                        0
                                                                    ">
                                                                        <small><i class="fas fa-times-circle"></i>
                                                                            {{
                                                                                trans(
                                                                                    "em.vacant"
                                                                                )
                                                                            }}
                                                                            0</small>
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <div v-else class="w-10 w-20-mobile" :class="{
                                                                'no-display':
                                                                    item.seatchart !=
                                                                    null &&
                                                                    item
                                                                        .seatchart
                                                                        .status >
                                                                    0
                                                            }">
                                                                <!-- Live stock alert  -->
                                                                <!-- if any booked tickets -->
                                                                <div v-if="
                                                                    typeof booked_tickets[
                                                                    item.id +
                                                                    '-' +
                                                                    booked_date_server
                                                                    ] !=
                                                                    'undefined'
                                                                ">
                                                                    <select class="form-select border-2 form-select-lg"
                                                                        name="quantity[]" v-model="quantity[
                                                                            index
                                                                        ]
                                                                            " v-if="
                                                                                (item.customer_limit !=
                                                                                    null
                                                                                    ? item.customer_limit
                                                                                    : max_ticket_qty) <=
                                                                                100
                                                                            ">
                                                                        <option value="0" selected>0</option>
                                                                        <option :key="ind
                                                                            " v-if="
                                                                                booked_tickets[
                                                                                    item.id +
                                                                                    '-' +
                                                                                    booked_date_server
                                                                                ]
                                                                                    .total_vacant <=
                                                                                (item.customer_limit !=
                                                                                    null
                                                                                    ? item.customer_limit
                                                                                    : max_ticket_qty)
                                                                            " :value="itm
                                                                                " v-for="(itm,
                                                                                    ind) in booked_tickets[
                                                                                        item.id +
                                                                                        '-' +
                                                                                        booked_date_server
                                                                                    ]
                                                                                        .total_vacant">{{
                                                                                            itm
                                                                                        }}</option>
                                                                        <option v-else :value="itm
                                                                            " v-for="(itm,
                                                                                ind) in item.quantity >
                                                                                    (item.customer_limit !=
                                                                                        null
                                                                                        ? item.customer_limit
                                                                                        : max_ticket_qty)
                                                                                    ? parseInt(
                                                                                        item.customer_limit !=
                                                                                            null
                                                                                            ? item.customer_limit
                                                                                            : max_ticket_qty
                                                                                    )
                                                                                    : parseInt(
                                                                                        item.quantity
                                                                                    )" :key="ind
                                                                                        ">{{
                                                                                            itm
                                                                                        }}</option>
                                                                    </select>
                                                                    <input v-else type="number" name="quantity[]"
                                                                        v-model="quantity[
                                                                            index
                                                                        ]
                                                                            " value="0"
                                                                        class="form-control form-input-sm" min="0" :max="booked_tickets[
                                                                            item.id +
                                                                            '-' +
                                                                            booked_date_server
                                                                        ]
                                                                            .total_vacant <
                                                                            (item.customer_limit !=
                                                                                null
                                                                                ? item.customer_limit
                                                                                : max_ticket_qty)
                                                                            ? booked_tickets[
                                                                                item.id +
                                                                                '-' +
                                                                                booked_date_server
                                                                            ]
                                                                                .total_vacant
                                                                            : item.customer_limit !=
                                                                                null
                                                                                ? item.customer_limit
                                                                                : max_ticket_qty
                                                                            " />
                                                                    <!-- Show if vacant less than max_ticket_qty -->
                                                                    <p class="text-dark" v-if="
                                                                        booked_tickets[
                                                                            item.id +
                                                                            '-' +
                                                                            booked_date_server
                                                                        ]
                                                                            .total_vacant <
                                                                        (item.customer_limit !=
                                                                            null
                                                                            ? item.customer_limit
                                                                            : max_ticket_qty) &&
                                                                        booked_tickets[
                                                                            item.id +
                                                                            '-' +
                                                                            booked_date_server
                                                                        ]
                                                                            .total_vacant >
                                                                        0 &&
                                                                        booked_tickets[
                                                                            item.id +
                                                                            '-' +
                                                                            booked_date_server
                                                                        ]
                                                                            .total_vacant <
                                                                        5
                                                                    ">
                                                                        <small><i class="fas fa-exclamation"></i>
                                                                            {{
                                                                                trans(
                                                                                    "em.vacant"
                                                                                )
                                                                            }}
                                                                            {{
                                                                                booked_tickets[
                                                                                    item.id +
                                                                                    "-" +
                                                                                    booked_date_server
                                                                                ]
                                                                                    .total_vacant
                                                                            }}</small>
                                                                    </p>
                                                                    <p class="text-danger" v-if="
                                                                        booked_tickets[
                                                                            item.id +
                                                                            '-' +
                                                                            booked_date_server
                                                                        ]
                                                                            .total_vacant <
                                                                        (item.customer_limit !=
                                                                            null
                                                                            ? item.customer_limit
                                                                            : max_ticket_qty) &&
                                                                        booked_tickets[
                                                                            item.id +
                                                                            '-' +
                                                                            booked_date_server
                                                                        ]
                                                                            .total_vacant <=
                                                                        0
                                                                    ">
                                                                        <small><i class="fas fa-times-circle"></i>
                                                                            {{
                                                                                trans(
                                                                                    "em.vacant"
                                                                                )
                                                                            }}
                                                                            0</small>
                                                                    </p>
                                                                </div>
                                                                <div v-else>
                                                                    <select class="form-select border-2 form-select-lg"
                                                                        name="quantity[]" v-model="quantity[
                                                                            index
                                                                        ]
                                                                            " v-if="
                                                                                (item.customer_limit !=
                                                                                    null
                                                                                    ? item.customer_limit
                                                                                    : max_ticket_qty) <=
                                                                                100
                                                                            ">
                                                                        <option value="0" selected>0</option>
                                                                        <option :value="itm
                                                                            " v-for="(itm,
                                                                                ind) in item.quantity >
                                                                                    (item.customer_limit !=
                                                                                        null
                                                                                        ? item.customer_limit
                                                                                        : max_ticket_qty)
                                                                                    ? parseInt(
                                                                                        item.customer_limit !=
                                                                                            null
                                                                                            ? item.customer_limit
                                                                                            : max_ticket_qty
                                                                                    )
                                                                                    : parseInt(
                                                                                        item.quantity
                                                                                    )" :key="ind
                                                                                        ">{{
                                                                                            itm
                                                                                        }}</option>
                                                                    </select>
                                                                    <input v-else type="number" name="quantity[]"
                                                                        v-model="quantity[
                                                                            index
                                                                        ]
                                                                            " value="0"
                                                                        class="form-control form-input-sm" min="0" :max="item.quantity >
                                                                            (item.customer_limit !=
                                                                                null
                                                                                ? item.customer_limit
                                                                                : max_ticket_qty)
                                                                            ? parseInt(
                                                                                item.customer_limit !=
                                                                                    null
                                                                                    ? item.customer_limit
                                                                                    : max_ticket_qty
                                                                            )
                                                                            : parseInt(
                                                                                item.quantity
                                                                            )
                                                                            " />
                                                                    <!-- Show if vacant less than max_ticket_qty -->
                                                                    <p class="text-dark" v-if="
                                                                        item.quantity <
                                                                        (item.customer_limit !=
                                                                            null
                                                                            ? item.customer_limit
                                                                            : max_ticket_qty) &&
                                                                        item.quantity >
                                                                        0 &&
                                                                        item.quantity <
                                                                        5
                                                                    ">
                                                                        <small><i class="fas fa-exclamation"></i>
                                                                            {{
                                                                                trans(
                                                                                    "em.vacant"
                                                                                )
                                                                            }}
                                                                            {{
                                                                                item.quantity
                                                                            }}</small>
                                                                    </p>
                                                                    <p class="text-danger" v-if="
                                                                        item.quantity <=
                                                                        0
                                                                    ">
                                                                        <small><i class="fas fa-times-circle"></i>
                                                                            {{
                                                                                trans(
                                                                                    "em.vacant"
                                                                                )
                                                                            }}
                                                                            0</small>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- CUSTOM -->

                                                    <!-- <div class="w-30 text-right">
                                                                <strong>
                                                                    {{ total_price[index] ? total_price[index] : '0.00' }}
                                                                    <small>{{currency}}</small>
                                                                </strong>
                                                                <p v-if="quantity[index] > 0"><i class="fas fa-check-circle ticket-selected-text"></i></p>
                                                            </div> -->

                                                    <!-- CUSTOM -->
                                                    <div v-if="
                                                        item.is_donation <=
                                                        0 &&
                                                        item.t_soldout <=
                                                        0
                                                    " class="ticket-price w-25 text-end">
                                                        <strong>
                                                            {{
                                                                total_price[
                                                                    index
                                                                ]
                                                                    ? total_price[
                                                                    index
                                                                    ]
                                                                    : "0.00"
                                                            }}
                                                            <small>{{
                                                                currency
                                                                }}</small>
                                                        </strong>
                                                        <span v-if="
                                                            quantity[
                                                            index
                                                            ] > 0
                                                        "><i
                                                                class="fas fa-check-circle ticket-selected-text"></i></span>
                                                    </div>

                                                    <div v-show="item.is_donation >
                                                        0 &&
                                                        quantity[
                                                        index
                                                        ] > 0 &&
                                                        item.t_soldout <=
                                                        0
                                                        " class="w-25 text-end">
                                                        <!-- <label class="custom-control-label" ><i class="fab fa-paypal"></i> {{ trans('em.is_do') }}</label> -->
                                                        <input type="text" name="is_donation[]" v-model="is_donation[
                                                            index
                                                        ]
                                                            " class="form-control form-input-sm" @keypress="NumbersOnly
                                                                " @change="isDonation" :id="'donation_' +
                                                                    index
                                                                    " />

                                                        <div class="btn-group btn-group-sm mt-1" role="group"
                                                            aria-label="Second group" v-if="
                                                                ticket_price_unchanged[
                                                                index
                                                                ] != undefined
                                                            ">
                                                            <button type="button" class="btn btn-secondary btn-sm"
                                                                @click="
                                                                    setDonationValue(
                                                                        index,
                                                                        donationPriceSuggestion(
                                                                            ticket_price_unchanged[
                                                                                index
                                                                            ]
                                                                                .price,
                                                                            1.5
                                                                        )
                                                                    )
                                                                    ">
                                                                {{
                                                                    donationPriceSuggestion(
                                                                        ticket_price_unchanged[
                                                                            index
                                                                        ].price,
                                                                        1.5
                                                                    )
                                                                }}
                                                            </button>
                                                            <button type="button" class="btn btn-secondary btn-sm"
                                                                @click="
                                                                    setDonationValue(
                                                                        index,
                                                                        donationPriceSuggestion(
                                                                            ticket_price_unchanged[
                                                                                index
                                                                            ]
                                                                                .price,
                                                                            2
                                                                        )
                                                                    )
                                                                    ">
                                                                {{
                                                                    donationPriceSuggestion(
                                                                        ticket_price_unchanged[
                                                                            index
                                                                        ].price,
                                                                        2
                                                                    )
                                                                }}
                                                            </button>
                                                            <button type="button" class="btn btn-secondary btn-sm"
                                                                @click="
                                                                    setDonationValue(
                                                                        index,
                                                                        donationPriceSuggestion(
                                                                            ticket_price_unchanged[
                                                                                index
                                                                            ]
                                                                                .price,
                                                                            4
                                                                        )
                                                                    )
                                                                    ">
                                                                {{
                                                                    donationPriceSuggestion(
                                                                        ticket_price_unchanged[
                                                                            index
                                                                        ].price,
                                                                        4
                                                                    )
                                                                }}
                                                            </button>
                                                            <button type="button" class="btn btn-secondary btn-sm"
                                                                @click="
                                                                    setDonationValue(
                                                                        index,
                                                                        donationPriceSuggestion(
                                                                            ticket_price_unchanged[
                                                                                index
                                                                            ]
                                                                                .price,
                                                                            8
                                                                        )
                                                                    )
                                                                    ">
                                                                {{
                                                                    donationPriceSuggestion(
                                                                        ticket_price_unchanged[
                                                                            index
                                                                        ].price,
                                                                        8
                                                                    )
                                                                }}
                                                            </button>
                                                        </div>
                                                        <div class="btn-group mt-1 ticket-price" role="group"
                                                            aria-label="Third group">
                                                            <strong class="mt-2">
                                                                {{
                                                                    total_price[
                                                                        index
                                                                    ]
                                                                        ? total_price[
                                                                        index
                                                                        ]
                                                                        : "0.00"
                                                                }}
                                                                <small>{{
                                                                    currency
                                                                    }}</small>
                                                                <span v-if="
                                                                    quantity[
                                                                    index
                                                                    ] > 0
                                                                "><i
                                                                        class="fas fa-check-circle ticket-selected-text"></i></span>
                                                            </strong>
                                                        </div>
                                                    </div>

                                                    <div v-show="item.is_donation >
                                                        0 &&
                                                        quantity[
                                                        index
                                                        ] <= 0 &&
                                                        item.t_soldout <=
                                                        0
                                                        " class="w-25 text-end">
                                                        <p>
                                                            <i class="fas fa-check-circle ticket-selected-text"></i>{{
                                                                trans(
                                                                    "em.is_donation"
                                                                )
                                                            }}
                                                        </p>
                                                    </div>
                                                    <!-- CUSTOM -->
                                                    <!-- *** CUSTOM *** end-->
                                                    <!-- CUSTOM -->
                                                </div>

                                                <div class="break-flex mt-2 text-center">
                                                    <button v-if="
                                                        item.seatchart !=
                                                        null &&
                                                        item.seatchart
                                                            .status >
                                                        0 &&
                                                        is_bulk <= 0 &&
                                                        item.t_soldout <=
                                                        0
                                                    " type="button" @click.prevent="
                                                        zoomIn(item.id)
                                                        " class="btn btn-sm text-primary">
                                                        <i class="fa fa-search-plus" aria-hidden="true"></i>
                                                    </button>
                                                    <button v-if="
                                                        item.seatchart !=
                                                        null &&
                                                        item.seatchart
                                                            .status >
                                                        0 &&
                                                        is_bulk <= 0 &&
                                                        item.t_soldout <=
                                                        0
                                                    " type="button" @click.prevent="
                                                        zoomOut(item.id)
                                                        " class="btn btn-sm text-primary">
                                                        <i class="fa fa-search-minus" aria-hidden="true"></i>
                                                    </button>
                                                    <button v-if="
                                                        item.seatchart !=
                                                        null &&
                                                        item.seatchart
                                                            .status >
                                                        0 &&
                                                        is_bulk <= 0 &&
                                                        item.t_soldout <=
                                                        0
                                                    " type="button" @click.prevent="
                                                        zoomReset(item.id)
                                                        " class="btn btn-sm text-primary">
                                                        <i class="fas fa-sync" aria-hidden="true"></i>
                                                    </button>
                                                </div>

                                                <!-- *** CUSTOM *** start-->

                                                <div style="overflow: auto;" class="break-flex" v-if="
                                                    item.seatchart !=
                                                    null &&
                                                    item.seatchart
                                                        .status > 0 &&
                                                    is_bulk <= 0 &&
                                                    item.t_soldout <= 0
                                                ">
                                                    <div class="break-flex my-2 text-center " v-if="!register_user_id">
                                                        <button type="button" @click="
                                                            register_modal = 1;
                                                        is_seatchart = true;
                                                        " class="btn btn-primary">
                                                            {{
                                                                trans(
                                                                    "em.enter_guest_details"
                                                                )
                                                            }}
                                                        </button>
                                                    </div>
                                                    <seat-component :ticket="item" :ticket_index="index"
                                                        :max_ticket_qty="item.customer_limit !=
                                                            null
                                                            ? item.customer_limit
                                                            : max_ticket_qty
                                                            " :quantity="quantity[index]
                                                                " :event="event" :class="{
                                                                    unclickable: !register_user_id
                                                                }"></seat-component>
                                                </div>
                                                <!-- ********** create confliction *****************-->

                                                <div class="break-flex" v-if="
                                                    parseInt(
                                                        quantity[index]
                                                    ) > 0 && is_bulk <= 0
                                                " v-for="(q_num,
                                                    q_index) in parseInt(
                                                        quantity[index]
                                                    )" :key="q_index">
                                                    <attendee-component ref="attendee" v-if="
                                                        parseInt(q_num) > 0
                                                    " :ticket_index="index" :quantity_index="q_index
                                                        " @update-name="handleUpdateName"></attendee-component>
                                                </div>
                                                <!-- ********** create confliction *****************-->
                                                <div class="break-flex w-50 w-m-100 my-2"
                                                    v-show="item.promocodes.length > 0">
                                                    <div class="input-group my-3 col-md-5" v-show="item.t_soldout <= 0 && is_bulk <= 0 ">
                                                        <!-- <div class="input-group my-3 col-md-5" v-show="item.price > 0 && item.t_soldout <= 0 && is_bulk <= 0 "> -->
                                                        <input type="text" name="promocode[]"
                                                            class="form-control form-control-sm"
                                                            :placeholder="trans('em.enter_promocode')"
                                                            v-model="promocode[index]" :id="'pc_' + index"
                                                            aria-describedby="button-addon2" />
                                                        <button class="btn btn-sm btn-success" type="button" @click="
                                                            pc_readonly[index] > 0
                                                                ? ''
                                                                : applyPromocode(item.id, index)
                                                            " :id="'pcb_' + index">
                                                            {{ trans("em.apply") }}
                                                        </button>
                                                    </div>
                                                    <span class="text-success" :id="'pc_success_' + index"></span>
                                                    <span class="text-danger" :id="'pc_error_' + index"></span>
                                                </div>
                                               <!-- CUSTOM -->
                                                <div class="break-flex mt-2 w-30 w-m-100" v-if="
                                                    quantity[index] > 0 &&
                                                    item.price > 0 &&
                                                    item.taxes.length > 0 &&
                                                    item.is_donation <= 0
                                                ">
                                                    <a class="pointer small" @click="tax_info = !tax_info">
                                                        <small v-if="tax_info">{{
                                                            trans("em.hide_tax_info")
                                                            }}</small>
                                                        <small v-else>{{
                                                            trans("em.show_tax_info")
                                                            }}</small>
                                                    </a>
                                                    <ul class="list-group list-group-flush my-2" v-if="tax_info">
                                                        <li class="list-group-item small p-0 text-muted"
                                                            v-for="(tax, index1) in item.taxes" :key="index1">
                                                            <span>{{ tax.title }}
                                                                <small>{{
                                                                    total_price[index] > 0
                                                                        ? countTax(
                                                                            item.price,
                                                                            tax.rate,
                                                                            tax.rate_type,
                                                                            tax.net_price,
                                                                            quantity[index]
                                                                        )
                                                                        : 0
                                                                }}</small>
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                                <!-- CUSTOM -->
                                                <div class="break-flex mt-2 w-30 w-m-100" v-if="
                                                    quantity[index] > 0 &&
                                                    item.price > 0 &&
                                                    item.taxes.length > 0 &&
                                                    item.is_donation > 0
                                                ">
                                                    <a class="pointer small" @click="tax_info = !tax_info">
                                                        <small v-if="tax_info">{{
                                                            trans("em.hide_tax_info")
                                                            }}</small>
                                                        <small v-else>{{
                                                            trans("em.show_tax_info")
                                                            }}</small>
                                                    </a>
                                                    <ul class="list-group list-group-flush my-2" v-if="tax_info">
                                                        <li class="list-group-item small p-0 text-muted"
                                                            v-for="(tax, index1) in item.taxes" :key="index1">
                                                            <span>{{ tax.title }}
                                                                <small>{{
                                                                    total_price[index] > 0
                                                                        ? countTax(
                                                                            item.price,
                                                                            tax.rate,
                                                                            tax.rate_type,
                                                                            tax.net_price,
                                                                            1
                                                                        )
                                                                        : 0
                                                                }}</small>
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <!-- CUSTOM -->

                                                <div class="break-flex mt-2 w-30 w-m-100" style="display: none;">
                                                    <!-- hide/show below ticket description -->
                                                    <a class="pointer ticket-info-toggle small"
                                                        @click="ticket_info = !ticket_info">
                                                        <small v-if="ticket_info">{{
                                                            trans("em.hide_info")
                                                            }}</small>
                                                        <small v-else>{{ trans("em.show_info") }}</small>
                                                    </a>
                                                    <p class="ticket-info small text-muted" v-if="ticket_info">
                                                        <span v-html="item.description"></span>

                                                    </p>
                                                </div>
                                                <!-- CUSTOM -->
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- question feedback users -->
                                    <ul class="list-group">
                                        <!-- <ul class="list-group" v-if="!questionsAnswered"> -->
                                        <li class="list-group-item mb-3 rounded border-2">
                                            <div class="col-12 mb-3">
                                                <label for="" class="form-label">1.&nbsp;On which day do you
                                                    intend to visit the
                                                    Show?*</label>
                                                    <div class="d-flex flex-wrap gap-5">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="visit_show_day" v-model="visit_show_day" value="Friday October 18th 11:00 am to 06:00 pm" required/>
                                                            <label class="form-check-label">Friday October 18th</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="visit_show_day" v-model="visit_show_day" value="Saturday October 19th 10:00 am to 06:00 pm" required/>
                                                            <label class="form-check-label">Saturday October 19th</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"  name="visit_show_day" v-model="visit_show_day" value="Sunday October 20th 10:00 am to 06:00 pm" required/>
                                                            <label class="form-check-label">Sunday October 20th</label>
                                                        </div>
                                                    </div>
                                                    
                                                        
                                                        

                                                <!-- <select class="form-select" name="visit_show_day"
                                                    v-model="visit_show_day">
                                                    <option value="">Select</option>
                                                    <option value="Friday October 18th">Friday October
                                                        18th</option>
                                                    <option value="Saturday October 19th">Saturday October
                                                        19th</option>
                                                    <option value="Sunday October 20th">Sunday October
                                                        20th</option>
                                                </select> -->
                                            </div>
											
                                            <div class="col-12">
                                                <label for="" class="form-label mb-2">2.&nbsp;Would you like to
                                                    receive information directly
                                                    from exhibitors attending
                                                    the event?*</label>
                                                <div class="d-flex flex-wrap gap-5">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio"
                                                            name="direct_connect_attendee" v-model="direct_connect_attendee
                                                                " id="directYes" value="1" required />
                                                        <label class="form-check-label" for="directYes">Yes</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio"
                                                            name="direct_connect_attendee" v-model="direct_connect_attendee
                                                                " id="directNo" value="0" required/>
                                                        <label class="form-check-label" for="directNo">No</label>
                                                    </div>
                                                </div>
                                            </div>
											
                                            <div class="col-12 mb-3">
                                                <label for="" class="form-label mb-2"></label>
                                                <div class="d-flex flex-wrap gap-5">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="ptsb_notification" v-model="ptsb_notification
                                                                " id="ptsbYes" value="1" required/>
                                                        <label class="form-check-label" for="ptsbYes">I accept the <a href="https://idealhome.ie/terms-and-conditions/">registration terms</a> and <a href="https://idealhome.ie/privacy-policy/">privacy policy</a>.</label>
                                                    </div>
													<!--
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio"
                                                            name="ptsb_notification" v-model="ptsb_notification
                                                                " id="ptsbNo" value="0" required/>
                                                        <label class="form-check-label" for="ptsbNo">No</label>
                                                    </div>
													-->
                                                </div>
                                            </div>
											
                                        </li>
                                    </ul>

                                    <!-- Cart Totals -->
                                    <div class="col-12">
                                        <p class="mb-2 h6">
                                            {{ trans("em.cart") }}
                                        </p>
                                        <ul class="list-group">
                                            <li class="list-group-item mb-3 rounded border-2">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="my-0">
                                                        <strong>{{
                                                            trans(
                                                                "em.total_tickets"
                                                            )
                                                        }}</strong>
                                                    </h6>
                                                    <strong :class="{
                                                        'ticket-selected-text':
                                                            bookedTicketsTotal() >
                                                            0
                                                    }">{{
                                                        bookedTicketsTotal()
                                                        }}</strong>
                                                </div>

                                                <div class="d-flex justify-content-between">
                                                    <h6 class="my-0">
                                                        <strong>{{
                                                            trans(
                                                                "em.total_order"
                                                            )
                                                        }}</strong>
                                                    </h6>
                                                    <strong :class="{
                                                        'ticket-selected-text':
                                                            bookedTicketsTotal() >
                                                            0
                                                    }">{{ total }}
                                                        <small>{{
                                                            currency
                                                            }}</small></strong>
                                                </div>
                                            </li>

                                            <!-- *** CUSTOM *** -->
                                            <li class="list-group-item mb-3 rounded border-2" v-if="
                                                total > 0 &&
                                                promocode_reward > 0
                                            ">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="my-0">
                                                        <strong>{{
                                                            trans("em.rewards")
                                                            }}</strong>
                                                    </h6>
                                                    <strong class="ticket-selected-text">{{
                                                        "-" +
                                                        promocode_reward.toFixed(
                                                            2
                                                        )
                                                    }}
                                                        {{ currency }}</strong>
                                                </div>
                                            </li>
                                            <li class="list-group-item mb-3 rounded border-2" v-if="
                                                total > 0 &&
                                                promocode_reward > 0
                                            ">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="my-0">
                                                        <strong>{{
                                                            trans(
                                                                "em.net_order_total"
                                                            )
                                                        }}</strong>
                                                    </h6>
                                                    <strong class="ticket-selected-text">{{
                                                        (
                                                            parseFloat(
                                                                total
                                                            ) -
                                                            parseFloat(
                                                                promocode_reward
                                                            )
                                                        ).toFixed(2)
                                                    }}
                                                        {{ currency }}</strong>
                                                </div>
                                            </li>
                                            <!-- *** CUSTOM *** -->
                                        </ul>
                                    </div>

                                    <!-- If not logged in -->
                                    <!-- <div class="col-md-12" v-if="!login_user_id"> -->
                                    <!-- CUSTOM -->
                                    <!-- <div class="col-md-12" v-if="!register_user_id">
                                        <div class="alert alert-danger">
                                            {{ trans('em.please_login_signup') }}
                                        </div>
                                    </div> -->

                                    <!-- Payments -->
                                    <!-- <div class="col-md-12" v-if="bookedTicketsTotal() > 0 && login_user_id"> -->
                                    <!-- CUSTOM -->
                                    <div class="col-12" v-if="bookedTicketsTotal() > 0">
                                        <!-- CUSTOM -->
                                        <p class="mb-2 h6">
                                            {{ trans("em.payment") }}
                                        </p>
                                        <div class="border-1 list-group-flush px-2">
                                            <!-- Free -->
                                            <!-- <div class="d-block my-3 pl-3" v-if="total <= 0"> -->
                                            <div class="d-block my-3 pl-3" v-if="
                                                (
                                                    parseFloat(total) -
                                                    parseFloat(
                                                        promocode_reward
                                                    )
                                                ).toFixed(2) <= 0
                                            ">
                                                <div class="radio-inline">
                                                    <input id="free_order" name="payment_method" type="radio"
                                                        class="custom-control-input" checked v-model="payment_method"
                                                        value="free" />
                                                    <label class="custom-control-label" for="free_order">
                                                        &nbsp;<i class="fas fa-glass-cheers"></i>
                                                        {{ trans("em.free") }}
                                                        <small>({{
                                                            trans("em.rsvp")
                                                        }}
                                                            )</small></label>
                                                </div>
                                            </div>

                                            <!-- Paid -->
                                            <div class="d-block my-3 pl-3" v-else-if="
                                                (
                                                    parseFloat(total) -
                                                    parseFloat(
                                                        promocode_reward
                                                    )
                                                ).toFixed(2) > 0
                                            ">
                                                <!-- For Organizer & Customer -->
                                                <div class="radio-inline" v-if="
                                                    is_admin <= 0 &&
                                                    is_paypal > 0 &&
                                                    is_bulk <= 0
                                                ">
                                                    <input type="radio" class="custom-control-input"
                                                        id="payment_method_paypal" name="payment_method"
                                                        v-model="payment_method" value="1" />
                                                    <label class="custom-control-label" for="payment_method_paypal">
                                                        &nbsp;<i class="fab fa-paypal"></i>
                                                        {{
                                                            trans("em.paypal")
                                                        }}</label>
                                                </div>

                                                <!--  CUSTOM -->
                                                <!-- For Organizer & Customer stripe-->
                                                <div class="radio-inline" v-if="
                                                    is_admin <= 0 &&
                                                    isStripe > 0 &&
                                                    is_bulk <= 0 &&
                                                    is_stripe_direct <=
                                                    0
                                                ">
                                                    <input type="radio" class="custom-control-input"
                                                        id="payment_method_stripe" name="payment_method"
                                                        v-model="payment_method" value="2" />
                                                    <label class="custom-control-label" for="payment_method_stripe">
                                                        &nbsp;<i class="fab fa-stripe"></i>
                                                        {{
                                                            trans(
                                                                "em.card_payment"
                                                            )
                                                        }}</label>
                                                </div>

                                                <div class="radio-inline" v-if="
                                                    is_admin <= 0 &&
                                                    isAuthorizeNet >
                                                    0 &&
                                                    is_bulk <= 0
                                                ">
                                                    <input type="radio" class="custom-control-input"
                                                        id="payment_method_authorize" name="payment_method"
                                                        v-model="payment_method" value="3" />
                                                    <label class="custom-control-label" for="payment_method_authorize">
                                                        {{
                                                            trans(
                                                                "em.authorizenet"
                                                            )
                                                        }}</label>
                                                </div>

                                                <div class="radio-inline" v-if="
                                                    is_admin <= 0 &&
                                                    is_bitpay > 0 &&
                                                    is_bulk <= 0
                                                ">
                                                    <input type="radio" class="custom-control-input"
                                                        id="payment_method_bitpay" name="payment_method"
                                                        v-model="payment_method" value="4" />
                                                    <label class="custom-control-label" for="payment_method_bitpay">
                                                        {{
                                                            trans("em.bitpay")
                                                        }}</label>
                                                </div>

                                                <div class="radio-inline" v-if="
                                                    is_admin <= 0 &&
                                                    is_stripe_direct >
                                                    0 &&
                                                    is_bulk <= 0
                                                ">
                                                    <input type="radio" class="custom-control-input"
                                                        id="payment_method_stripe_direct" name="payment_method"
                                                        v-model="payment_method" value="5" />
                                                    <label class="custom-control-label"
                                                        for="payment_method_stripe_direct">
                                                        &nbsp;<i class="fab fa-stripe"></i>
                                                        {{
                                                            trans(
                                                                "em.card_payment"
                                                            )
                                                        }}</label>
                                                </div>

                                                <div class="radio-inline" v-if="
                                                    is_admin <= 0 &&
                                                    is_bulk <= 0 &&
                                                    is_pay_stack > 0
                                                ">
                                                    <input type="radio" class="custom-control-input"
                                                        id="payment_method_paystack" name="payment_method"
                                                        v-model="payment_method" value="6" />
                                                    <label class="custom-control-label"
                                                        for="payment_method_paystack">PayStack</label>
                                                </div>

                                                <div class="radio-inline" v-if="
                                                    is_admin <= 0 &&
                                                    is_bulk <= 0 &&
                                                    is_razorpay > 0
                                                ">
                                                    <input type="radio" class="custom-control-input"
                                                        id="payment_method_razorpay" name="payment_method"
                                                        v-model="payment_method" value="7" />
                                                    <label class="custom-control-label"
                                                        for="payment_method_razorpay">Razorpay</label>
                                                </div>

                                                <div class="radio-inline" v-if="
                                                    is_admin <= 0 &&
                                                    is_bulk <= 0 &&
                                                    is_paytm > 0
                                                ">
                                                    <input type="radio" class="custom-control-input"
                                                        id="payment_method_paytm" name="payment_method"
                                                        v-model="payment_method" value="8" />
                                                    <label class="custom-control-label"
                                                        for="payment_method_paytm">Paytm</label>
                                                </div>
                                                <div class="radio-inline" v-if="
                                                    is_admin <= 0 &&
                                                    is_usaepay > 0
                                                ">
                                                    <input type="radio" class="custom-control-input"
                                                        id="payment_method_usaepay" name="payment_method"
                                                        v-model="payment_method" value="9" />
                                                    <label class="custom-control-label" for="payment_method_usaepay">
                                                        &nbsp;<i class="fa-regular fa-credit-card"></i>
                                                        USAePay</label>
                                                </div>

                                                <!-- CUSTOM -->

                                                <!-- For Admin & Organizer & Customer -->
                                                <div class="radio-inline" v-if="
                                                    (is_organiser > 0 &&
                                                        is_offline_payment_organizer >
                                                        0) ||
                                                    (is_customer > 0 &&
                                                        is_offline_payment_customer >
                                                        0) ||
                                                    is_admin > 0 ||
                                                    is_bulk > 0
                                                ">
                                                    <input type="radio" class="custom-control-input"
                                                        id="payment_method_offline" name="payment_method"
                                                        v-model="payment_method" value="offline" />

                                                    <label class="custom-control-label" for="payment_method_offline">
                                                        &nbsp;<i class="fas fa-suitcase-rolling"></i>
                                                        {{
                                                            trans("em.offline")
                                                        }}
                                                        <small>({{
                                                            trans(
                                                                "em.cash_on_arrival"
                                                            )
                                                        }})</small>
                                                    </label>
                                                </div>

                                                <p v-if="
                                                    payment_method ==
                                                    'offline'
                                                " class="text-dark">
                                                    <strong>{{
                                                        trans(
                                                            "em.offline_payment_info"
                                                        )
                                                    }}: </strong><small v-html="event.offline_payment_info
                                                            "></small>
                                                </p>

                                                <p class="text-mute h6 mt-2" v-html="trans('em.order_terms')
                                                    "></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!--  CUSTOM -->

                                <authorize-net v-if="
                                    payment_method == 3 &&
                                    total > 0 &&
                                    register_user_id &&
                                    is_admin <= 0 &&
                                    isAuthorizeNet > 0 &&
                                    (
                                        parseFloat(total) -
                                        parseFloat(promocode_reward)
                                    ).toFixed(2) > 0
                                "></authorize-net>

                                <pay-stack ref="pay_stack" v-if="
                                    payment_method == 6 &&
                                    total > 0 &&
                                    register_user_id &&
                                    is_admin <= 0 &&
                                    is_pay_stack > 0 &&
                                    (
                                        parseFloat(total) -
                                        parseFloat(promocode_reward)
                                    ).toFixed(2) > 0
                                "></pay-stack>
                                <USAePay v-if="payment_method == 9" />
                                <!--  CUSTOM -->

                                <div class="row mt-1">
                                    <!-- <div class="col-xs-12" v-if="login_user_id"> -->
                                    <!-- CUSTOM -->
                                    <div class="col-xs-12  d-grid pb-3" v-if="
                                        register_user_id 
                                    ">
                                        <!-- CUSTOM -->
                                        <button :class="{ disabled: disable }" :disabled="disable" type="submit"
                                            class="btn btn-success btn-lg btn-block">
                                            <i class="fas fa-cash-register"></i>
                                            {{
                                                total <= 0 ? trans("em.rsvp") : trans("em.checkout") }} </button>
                                    </div>

                                    <!-- <div class="col-xs-12" v-else> -->
                                    <!-- CUSTOM -->
                                    <div class="col-xs-12 d-grid" v-if="
                                        !register_user_id
                                    ">
                                        <!-- CUSTOM -->
                                        <div class="btn-group pb-3">
                                            <button type="button" class="btn btn-success btn-lg w-50 click-guest"
                                                @click="register_modal = 1">
                                                <i class="fas fa-fingerprint"></i>
                                                {{ trans("em.checkout") }}
                                            </button>
                                        </div>
                                    </div>
                                    <register-user v-if="register_modal > 0" :register_user_id="register_user_id"
                                        :register_modal="register_modal" :attendees="attendees"></register-user>
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
import { mapState, mapMutations } from "vuex";
import mixinsFilters from "../../../../mixins.js";
import _ from "lodash";
//   CUSTOM
import Stripe from "./custom/Stripe.vue";
import RegisterUser from "./custom/RegisterUser.vue";
import AuthorizeNet from "./custom/AuthorizeNet.vue";
import AddAttendee from "./custom/AddAttendee";
import VueCountdown from "@chenfengyuan/vue-countdown";
import AttendeeComponent from "./custom/Attendee.vue";
import SeatComponent from "./custom/Seat";
import PayStack from "./custom/PayStack";
import USAePay from "../../../../../../eventmie-pro/resources/js/events_show/components/USAePay.vue";

//  CUSTOM
// moment.locale('en');
export default {
    components: {
        //  CUSTOM
        "stripe-component": Stripe,
        RegisterUser,
        AuthorizeNet,
        AddAttendee,
        VueCountdown,
        AttendeeComponent,
        SeatComponent,
        "pay-stack": PayStack,
        USAePay
        // CUSTOM
    },

    mixins: [mixinsFilters],

    props: [
        "tickets",
        "max_ticket_qty",
        "event",
        "currency",
        "login_user_id",
        "is_admin",
        "is_organiser",
        "is_pos",
        "is_customer",
        "is_paypal",
        "is_offline_payment_organizer",
        "is_offline_payment_customer",
        "booked_tickets"
    ],
    data() {
        return {
            openModal: false,
            attendees: [],
            ticket_info: false,
            tax_info: false,
            moment: moment,
            quantity: [1],
            price: null,
            total_price: [],
            customer_id: 0,
            total: 0,
            disable: false,
            payment_method: "offline",

            // customers options
            options: [],
            //selected customer
            customer: null,

            /* CUSTOM */
            //promocode
            bookings_data: null,
            stripePublishableKey: stripe_publishable_key,
            stripe_secret_key: stripe_secret_key,
            isStripe: is_stripe,

            name: "",
            fname: "",
            lname: "",
            email: "",
            register_user_id: this.login_user_id,
            register_modal: 0,

            //promocode
            promocode: [],
            ticket_promocodes: [],
            pc_readonly: [],
            promocode_reward: 0,

            cardName: "",
            cardNumber: "",
            cardMonth: "",
            cardYear: "",
            cardCvv: "",
            isAuthorizeNet: is_authorize_net,
            is_bitpay: is_bitpay,
            is_stripe_direct: is_stripe_direct,
            add_attendee: 0,
            is_twilio: is_twilio,
            phone_t: "",

            fname: [[], []],
            lname: [[], []],
            phone: [[], []],
            address: [[], []],
            is_bulk: 0,

            is_donation: [],
            pay_stack: 0,
            is_pay_stack: is_pay_stack,
            is_razorpay: is_razorpay,
            is_paytm: is_paytm,
            show_checkout_button: false,
            show_checkout_timer: false,
            zoom: 1,
            is_usaepay: is_usaepay,
            route: route,
            is_seatchart: false,
            overflowHidden: false,

            ticket_price_unchanged: [],

            //visit_show_day: "Friday October 18th",
            ptsb_notification: false,
            direct_connect_attendee: false,
            questionsAnswered: false

            /* CUSTOM */
        };
    },

    computed: {
        // get global variables
        ...mapState([
            "booking_date",
            "start_time",
            "end_time",
            "booking_end_date",
            "booked_date_server",
            "disableSeats"
        ])
    },

    methods: {
        // update global variables
        ...mapMutations(["add", "update"]),

        // reset form and close modal
        close: function () {
            this.price = null;
            this.quantity = [];
            this.total_price = [];

            this.add({
                booking_date: null,
                booked_date_server: null,
                booking_end_date: null,
                start_time: null,
                end_time: null
            });

            this.openModal = false;
            this.overflowHidden = false;
        },
        //custom
        handleUpdateName({ name, email }) {

            this.attendees['name'] = name;
            this.attendees['email'] = email;

        },
        bookTickets() {
            // show loader
            this.showLoaderNotification(trans("em.processing"));

            // prepare form data for post request
            this.disable = true;

            // Set questionsAnswered to true to indicate the questions have been answered
            this.questionsAnswered = true;

            // Optional: You can save the responses to localStorage or Vuex to persist the state
            localStorage.setItem("questionsAnswered", true);

            let post_url = route("eventmie.bookings_book_tickets");
            let post_data = new FormData(this.$refs.form);
            console.log(post_data,"post_datapost_datapost_data");

            //CUSTOM
            post_data.append("name", JSON.stringify(this.name));
            post_data.append("fname", JSON.stringify(this.fname));
            post_data.append("lname", JSON.stringify(this.lname));
            post_data.append("phone", JSON.stringify(this.phone));
            post_data.append("address", JSON.stringify(this.address));
            post_data.append("disableSeats", JSON.stringify(this.disableSeats));

            //CUSTOM

            /* CUSTOM */
            // in case of Stripe
            if (this.payment_method == 6 && this.total > 0) {
                this.PayStack(post_data);
            } else {
                /* CUSTOM */
                // axios post request
                axios
                    .post(post_url, post_data)
                    .then(res => {
                        if (
                            res.data.status &&
                            res.data.message != "" &&
                            typeof res.data.message != "undefined"
                        ) {
                            // hide loader
                            Swal.hideLoading();

                            // close popup
                            this.close();
                            this.showNotification("success", res.data.message);
                        } else if (
                            !res.data.status &&
                            res.data.message != "" &&
                            res.data.url != "" &&
                            typeof res.data.url != "undefined"
                        ) {
                            // hide loader
                            Swal.hideLoading();

                            // close popup
                            this.close();
                            this.showNotification("error", res.data.message);

                            setTimeout(() => {
                                window.location.href = res.data.url;
                            }, 1000);
                        }

                        if (
                            res.data.url != "" &&
                            res.data.status &&
                            typeof res.data.url != "undefined"
                        ) {
                            // hide loader
                            Swal.hideLoading();

                            setTimeout(() => {
                                window.location.href = res.data.url;
                            }, 1000);
                        }

                        if (
                            !res.data.status &&
                            res.data.message != "" &&
                            typeof res.data.message != "undefined"
                        ) {
                            // hide loader
                            Swal.hideLoading();

                            // close popup
                            this.close();
                            this.showNotification("error", res.data.message);
                        }
                    })
                    .catch(error => {
                        this.disable = false;
                        this.questionsAnswered = false;
                        localStorage.setItem("questionsAnswered", false);
                        let serrors = Vue.helpers.axiosErrors(error);
                        if (serrors.length) {
                            this.serverValidate(serrors);
                        }
                    });
            }
        },

        // validate data on form submit
        validateForm(e) {
            this.$validator.validateAll().then(result => {
                if (result) {
                    this.disable = true;
                    // this.formSubmit(e);
                    //CUSTOM
                    this.bookTickets();
                    //CUSTOM
                } else {
                    this.disable = false;
                }
            });
        },

        // show server validation errors
        serverValidate(serrors) {
            this.disable = false;
            this.$validator.validateAll().then(result => {
                this.$validator.errors.add(serrors);
            });
        },

        // count total tax
        countTax(price, tax, rate_type, net_price, quantity) {
            price = parseFloat(price).toFixed(2);
            tax = parseFloat(tax).toFixed(2);
            var total_tax = parseFloat(quantity * tax).toFixed(2);

            // in case of percentage
            if (rate_type == "percent") {
                if (isNaN((price * total_tax) / 100)) return 0;

                total_tax = parseFloat((price * total_tax) / 100).toFixed(2);

                if (net_price == "excluding")
                    return (
                        total_tax +
                        " " +
                        this.currency +
                        " (" +
                        tax +
                        "%" +
                        " " +
                        trans("em.exclusive") +
                        ")"
                    );
                else
                    return (
                        total_tax +
                        " " +
                        this.currency +
                        " (" +
                        tax +
                        "%" +
                        " " +
                        trans("em.inclusive") +
                        ")"
                    );
            }

            // for fixed tax
            if (rate_type == "fixed") {
                if (net_price == "excluding")
                    return (
                        total_tax +
                        " " +
                        this.currency +
                        " (" +
                        tax +
                        " " +
                        this.currency +
                        " " +
                        trans("em.exclusive") +
                        ")"
                    );
                else
                    return (
                        total_tax +
                        " " +
                        this.currency +
                        " (" +
                        tax +
                        " " +
                        this.currency +
                        " " +
                        trans("em.inclusive") +
                        ")"
                    );
            }

            return 0;
        },

        // count total price
        totalPrice() {
            if (this.quantity != null || this.quantity.length > 0) {
                let amount;
                let tax;
                let total_tax;
                this.quantity.forEach(
                    function (value, key) {
                        total_tax = 0;
                        this.total_price[key] = [];

                        //CUSTOM
                        if (this.tickets[key].is_donation <= 0) {
                            amount = this.tickets[key].sale_price ? (parseFloat(
                                value * this.tickets[key].sale_price
                            ).toFixed(2)) : parseFloat(
                                value * this.tickets[key].price
                            ).toFixed(2);
                        } else if (
                            value > 0 &&
                            this.tickets[key].is_donation > 0
                        ) {
                            amount = parseFloat(
                                1 * this.tickets[key].price
                            ).toFixed(2);
                        } else if (
                            value <= 0 &&
                            this.tickets[key].is_donation > 0
                        ) {
                            // don't make donation Zero by default
                            // this.$parent.tickets[key].price = 0;
                            this.$parent.tickets[key].price = this.tickets[
                                key
                            ].donation_price;
                            this.is_donation[key] = 0;
                            amount = 0;
                        }
                        //CUSTOM

                        // amount                  = (parseFloat(value * this.tickets[key].price)).toFixed(2);

                        // when have no taxes set set total_price with actual ammount without taxes
                        if (Object.keys(this.total_price).length > 0) {
                            this.total_price.forEach(
                                function (v, k) {
                                    // if(Object.keys(v).length <= 0);
                                    //CUSTOM
                                    if (
                                        Object.keys(v).length <= 0 &&
                                        !isNaN(v)
                                    );
                                    //CUSTOM
                                    this.total_price[key] = amount;
                                }.bind(this)
                            );
                        }
                        if (this.tickets[key].taxes.length > 0 && amount > 0) {
                            this.tickets[key].taxes.forEach(
                                function (tax_v, tax_k) {
                                    // in case of percentage
                                    if (tax_v.rate_type == "percent") {
                                        // in case of excluding
                                        if (tax_v.net_price == "excluding") {
                                            tax = isNaN(
                                                (amount * tax_v.rate) / 100
                                            )
                                                ? 0
                                                : parseFloat(
                                                    (amount * tax_v.rate) /
                                                    100
                                                ).toFixed(2);

                                            total_tax =
                                                parseFloat(total_tax) +
                                                parseFloat(tax);
                                        }
                                    }

                                    // // in case of percentage
                                    if (tax_v.rate_type == "fixed") {
                                        tax = parseFloat(value * tax_v.rate);

                                        // // in case of excluding
                                        if (tax_v.net_price == "excluding")
                                            total_tax =
                                                parseFloat(total_tax) +
                                                parseFloat(tax);
                                    }
                                }.bind(this)
                            );
                        }

                        this.total_price[key] = (
                            parseFloat(amount) + parseFloat(total_tax)
                        ).toFixed(2);
                    }.bind(this)
                );
            }
        },

        updateItem() {
            this.$emit("changeItem");
        },

        setDefaultQuantity() {
            // only set default value once
            var _this = this;
            var promise = new Promise(function (resolve, reject) {
                // only set default value once
                if (_this.quantity.length == 1) {
                    _this.tickets.forEach(
                        function (value, key) {
                            if (key == 0) _this.quantity[key] = 1;
                            else _this.quantity[key] = 1;
                        }.bind()
                    );
                }
                resolve(true);
            });

            promise.then(
                function (successMessage) {
                    _this.totalPrice();
                    _this.orderTotal();
                    /* CUSTOM start*/
                    _this.resetPromocode();
                    /* CUSTOM end*/
                    
                    _this.defaultPaymentMethod();
                },
                function (errorMessage) { }
            );
        },

        // count prise all booked tickets
        orderTotal() {
            this.total = 0;
            if (Object.keys(this.total_price).length > 0) {
                this.total_price.forEach(
                    function (value, key) {
                        //CUSTOM
                        if (isNaN(value)) value = 0;
                        //CUSTOM

                        this.total = (
                            parseFloat(this.total) + parseFloat(value)
                        ).toFixed(2);
                    }.bind(this)
                );

                return this.total;
            }
            return 0;
        },

        // total booked tickets
        bookedTicketsTotal() {
            let total = 0;
            if (this.quantity.length > 0) {
                this.quantity.forEach(
                    function (value, key) {
                        total = parseInt(total) + parseInt(value);
                    }.bind(this)
                );

                return total;
            }
            return 0;
        },

        defaultPaymentMethod() {
            // if not admin
            // total > 0
            // if(this.is_admin <= 0 && this.bookedTicketsTotal() > 0)
            //     this.payment_method = 1;

            //CUSTOM
            // if premium order & not-admin
            if (
                this.is_admin <= 0 &&
                this.orderTotal() > 0 &&
                (
                    parseFloat(this.total) - parseFloat(this.promocode_reward)
                ).toFixed(2) > 0
            )
                this.payment_method = default_payment_method;
            // if premium order & admin
            else if (
                this.is_admin >= 1 &&
                this.orderTotal() > 0 &&
                (
                    parseFloat(this.total) - parseFloat(this.promocode_reward)
                ).toFixed(2) > 0
            )
                this.payment_method = "offline";
            else this.payment_method = default_payment_method;

            if (this.is_bulk > 0) {
                this.payment_method = "offline";
            }

            // for free
            if (
                (
                    parseFloat(this.total) - parseFloat(this.promocode_reward)
                ).toFixed(2) <= 0
            )
                this.payment_method = "free";

            //CUSTOM
        },

        loginFirst() {
            window.location.href = route("eventmie.login_first");
        },
        signupFirst() {
            window.location.href = route("eventmie.signup_first");
        },

        // get customers

        getCustomers(loading, search = null) {
            var postUrl = route("eventmie.get_customers");
            var _this = this;
            axios
                .post(postUrl, {
                    search: search
                })
                .then(res => {
                    var promise = new Promise(function (resolve, reject) {
                        _this.options = res.data.customers;

                        resolve(true);
                    });

                    promise.then(
                        function (successMessage) {
                            loading(false);
                        },
                        function (errorMessage) {
                            //error handler function is invoked
                            console.log(errorMessage);
                        }
                    );
                })
                .catch(error => {
                    let serrors = Vue.helpers.axiosErrors(error);
                    if (serrors.length) {
                        this.serverValidate(serrors);
                    }
                });
        },

        // v-select methods
        onSearch(search, loading) {
            loading(true);
            this.search(loading, search, this);
        },

        // v-select methods
        search: _.debounce((loading, search, vm) => {
            if (vm.validateEmail(search)) vm.getCustomers(loading, search);
            else loading(false);
        }, 350),

        validateEmail(email) {
            const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(email);
        },

        //CUSTOM
        // stipe open modal
        stripePayment(bookings_data = null) {
            this.bookings_data = bookings_data;
            this.$refs.stripe.stripeCheckout();
        },

        //apply promocode
        applyPromocode(ticket_id = null, index = null) {
            if (
                ticket_id != null &&
                this.promocode[index] != null &&
                this.promocode[index] != "" &&
                this.promocode[index] != "undefined"
            ) {
                axios
                    .post(route("apply_promocodes"), {
                        ticket_id: ticket_id,
                        promocode: this.promocode[index],
                        customer_id:
                            this.is_customer > 0
                                ? this.register_user_id
                                : this.customer_id
                    })
                    .then(res => {
                        if (res.data.status > 0) {
                            console.log("success");

                            var _this = this;
                            var promise = new Promise(function (
                                resolve,
                                reject
                            ) {
                                _this.ticket_promocodes[index] =
                                    res.data.promocode;
                                resolve(true);
                            });

                            promise.then(
                                function (successMessage) {
                                    _this.promocodeReward();
                                },
                                function (errorMessage) {
                                    console.log(errorMessage);
                                }
                            );

                            // success
                            this.pc_readonly[index] = 1;

                            // promocode apply button text change
                            document.getElementById(
                                "pcb_" + index
                            ).innerHTML = trans("em.applied");

                            // error field set null
                            document.getElementById(
                                "pc_error_" + index
                            ).innerHTML = "";

                            // show promocode's reward
                            document.getElementById(
                                "pc_success_" + index
                            ).innerHTML =
                                res.data.promocode.reward +
                                (res.data.promocode.p_type == "fixed"
                                    ? this.currency
                                    : "%") +
                                " OFF!";

                            // promocode input field readonly
                            document.getElementById(
                                "pc_" + index
                            ).readOnly = true;

                            // promocode apply button disable
                            document.getElementById(
                                "pcb_" + index
                            ).disabled = true;
                        } else {
                            console.log("error");
                            // error
                            this.pc_readonly[index] = 0;

                            // promocode input field readonly
                            document.getElementById(
                                "pc_" + index
                            ).readOnly = false;

                            // error field set
                            document.getElementById(
                                "pc_error_" + index
                            ).innerHTML = res.data.message;

                            //success message set null
                            document.getElementById(
                                "pc_success_" + index
                            ).innerHTML = "";

                            // promocode input field clear
                            document.getElementById("pc_" + index).value = "";

                            // promocode v-model value set null
                            this.promocode[index] = "";
                        }
                    })
                    .catch(error => {
                        Vue.helpers.axiosErrors(error);
                    });
            }
        },

        // promocode' reward
        promocodeReward() {
            this.promocode_reward = 0;

            if (Object.keys(this.ticket_promocodes).length > 0) {
                this.ticket_promocodes.forEach(
                    function (value, key) {
                        if (
                            value != "undefined" &&
                            value != "" &&
                            value != null
                        ) {
                            if (value.p_type == "fixed") {
                                this.promocode_reward = isNaN(
                                    parseFloat(this.promocode_reward) +
                                    parseFloat(value.reward)
                                )
                                    ? this.promocode_reward
                                    : parseFloat(this.promocode_reward) +
                                    parseFloat(value.reward);
                            } else {
                                if (this.total_price[key] > 0) {
                                    this.promocode_reward =
                                        this.promocode_reward +
                                        (isNaN(
                                            (this.total_price[key] *
                                                value.reward) /
                                            100
                                        )
                                            ? this.promocode_reward
                                            : parseFloat(
                                                (this.total_price[key] *
                                                    value.reward) /
                                                100
                                            ));
                                }
                            }
                        }
                    }.bind(this)
                );
            }
            this.defaultPaymentMethod();
            return this.promocode_reward.toFixed(2);
        },

        // reset promocode fields
        resetPromocode() {
            this.quantity.forEach(
                function (value, key) {
                    if (
                        Number(value) <= 0 ||
                        value == "" ||
                        value == "undefined" ||
                        value == null
                    ) {
                        // promocode apply button disable
                        document.getElementById("pcb_" + key).disabled = false;

                        // promocode input field readonly
                        document.getElementById("pc_" + key).readOnly = false;

                        // success
                        this.pc_readonly[key] = 1;

                        //clear field
                        this.promocode[key] = "";
                        document.getElementById("pc_" + key).value = "";

                        // promocode apply button text change
                        document.getElementById("pcb_" + key).innerHTML = trans(
                            "em.apply"
                        );

                        // error field set null
                        document.getElementById("pc_error_" + key).innerHTML =
                            "";

                        // show promocode's reward
                        document.getElementById("pc_success_" + key).innerHTML =
                            "";

                        // promocode input field readonly
                        document.getElementById("pc_" + key).readOnly = true;

                        // promocode apply button disable
                        document.getElementById("pcb_" + key).disabled = true;

                        this.ticket_promocodes[key] = "";
                    } else {
                        if (
                            this.promocode[key] == "undefined" ||
                            this.promocode[key] == ""
                        ) {
                            // promocode apply button disable
                            document.getElementById(
                                "pcb_" + key
                            ).disabled = false;

                            // promocode input field readonly
                            document.getElementById(
                                "pc_" + key
                            ).readOnly = false;

                            this.pc_readonly[key] = 0;

                            document.getElementById("pc_" + key).value = "";

                            // promocode apply button text change
                            document.getElementById(
                                "pcb_" + key
                            ).innerHTML = trans("em.apply");

                            // error field set null
                            document.getElementById(
                                "pc_error_" + key
                            ).innerHTML = "";

                            // show promocode's reward
                            document.getElementById(
                                "pc_success_" + key
                            ).innerHTML = "";
                        }
                    }
                }.bind(this)
            );
        },

        timerOnSale(sale_start_date = null, sale_end_date = null) {
            if (sale_start_date == null || sale_end_date == null) return true;

            var local_tz = Intl.DateTimeFormat().resolvedOptions().timeZone;

            var a = this.userTimezone(
                sale_end_date,
                "YYYY-MM-DD HH:mm:ss"
            ).format("DD/MM/YYYY HH:mm:ss");
            var b = moment()
                .tz(local_tz)
                .format("DD/MM/YYYY HH:mm:ss");
            var ms = 0; // milliseconds

            if (
                moment(a, "DD/MM/YYYY HH:mm:ss") >
                moment(b, "DD/MM/YYYY HH:mm:ss")
            ) {
                ms = moment(a, "DD/MM/YYYY HH:mm:ss")
                    .tz(local_tz)
                    .diff(moment(b, "DD/MM/YYYY HH:mm:ss").tz(local_tz)); //milliseconds
            }

            return ms;
        },

        // create attenddes fields
        createFields() {
            this.tickets.forEach(
                function (v, k) {
                    this.name[k] = [];
                    this.phone[k] = [];
                    this.address[k] = [];
                }.bind(this)
            );
        },

        // for input box
        isDonation() {
            // if(this.tickets.length > 0)
            // {
            //     this.tickets.forEach(function(value, key){
            //         if(value.is_donation > 0 ){
            //             if(this.is_donation[key] == undefined || this.is_donation[key] == ''){
            //                 this.is_donation[key] = parseFloat(0);
            //                 this.total_price[key] = parseFloat(0);
            //             }
            //             this.$parent.tickets[key].price =  parseFloat(this.is_donation[key]).toFixed(2);
            //             this.is_donation[key] = this.is_donation[key];
            //         }
            //     }.bind(this))
            // }
        },

        // for group button
        setDonationValue(index = null, price = 0) {
            if (index == null) return true;

            if (this.tickets[index].is_donation > 0) {
                var id = "donation_" + index;
                document.getElementById(id).value = parseFloat(price).toFixed(
                    2
                );
                this.is_donation[index] = parseFloat(price).toFixed(2);
                this.tickets[index].price = parseFloat(price).toFixed(2);
                this.quantity[index] = this.quantity[index];

                this.isDonation();
                this.totalPrice();
                this.orderTotal();
                this.$forceUpdate();
            }
        },

        // accept only numeric value
        NumbersOnly(evt) {
            var evt = evt ? evt : window.event;
            var charCode = evt.which ? evt.which : evt.keyCode;
            if (
                charCode > 31 &&
                (charCode < 48 || charCode > 57) &&
                charCode !== 46
            ) {
                evt.preventDefault();
            } else {
                return true;
            }
        },

        //paystack payment
        PayStack(booking_data) {
            this.$refs.pay_stack.PayStack(booking_data);
        },

        checkTicketQuantity() {
            let _this = this;
            console.log("q", this.quantity);
            this.quantity.every(function (value, key) {
                if (
                    Number(value) <= 0 ||
                    value == "" ||
                    value == "undefined" ||
                    value == null
                ) {
                    _this.show_checkout_button = false;

                    return true;
                } else {
                    _this.show_checkout_button = true;
                    _this.show_checkout_timer = true;
                    return false;
                }
            });
        },

        zoomIn(id) {
            this.zoom += 0.1;
            document.getElementById(
                `seat-container_${id}`
            ).style.transform = `scale(${this.zoom})`;
        },

        zoomOut(id) {
            this.zoom -= 0.1;
            document.getElementById(
                `seat-container_${id}`
            ).style.transform = `scale(${this.zoom})`;
        },

        zoomReset(id) {
            this.zoom = 1;
            document.getElementById(
                `seat-container_${id}`
            ).style.transform = `scale(${this.zoom})`;
        },

        reloadPage() {
            // Set a timeout to reload the page after a specified delay
            setTimeout(() => {
                location.reload();
            }, 479999);
        },

        setTicketPriceUnchanged: function () {
            this.ticket_price_unchanged = JSON.parse(
                JSON.stringify(this.tickets)
            );
        },

        donationPriceSuggestion: function (price, multiply) {
            return price * multiply;
        }

        //CUSTOM
    },

    watch: {
        quantity: function () {
            //CUSTOM
            this.checkTicketQuantity();
            this.isDonation();
            //CUSTOM

            this.totalPrice();
            this.orderTotal();
            this.defaultPaymentMethod();

            /* CUSTOM */
            this.resetPromocode();
            this.promocodeReward();

            /* CUSTOM */
        },
        tickets: function () {
            //CUSTOM
            this.checkTicketQuantity();
            this.isDonation();
            //CUSTOM

            this.totalPrice();
            this.orderTotal();
        },

        // active when customer search
        customer: function () {
            this.customer_id = this.customer != null ? this.customer.id : null;
        },

        // CUSTOM
        is_donation: function () {
            this.isDonation();
            this.totalPrice();
            this.orderTotal();
        },

        show_checkout_timer: function () {
            if (this.show_checkout_timer) {
                this.reloadPage();
            }
        }

        // CUSTOM
    },

    mounted() {
        this.openModal = true;
        this.setDefaultQuantity();
        this.defaultPaymentMethod();
        //  CUSTOM
        this.createFields();

        //  CUSTOM
        this.setTicketPriceUnchanged();

        // Optional: Check if the responses have been saved in localStorage or Vuex
        this.questionsAnswered =
            localStorage.getItem("questionsAnswered") === "true";
    }
};
</script>

<style scoped>
.unclickable {
    pointer-events: none;
    opacity: 0.5;
}
</style>
