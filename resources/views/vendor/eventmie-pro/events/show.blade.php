@extends('eventmie::layouts.app')

@section('title', $event->title)
@section('meta_title', $event->meta_title ? $event->meta_title : $event->title)
@section('meta_keywords', $event->meta_keywords)
@section('meta_description', $event->meta_description ? $event->meta_description : $event->excerpt)
@section('meta_image', url('/storage/' . $event['thumbnail']))
@section('meta_url', url()->current())


@section('content')

    <!--Cover-->
    <section>
        <div class="container-fluid p-0 ">
            <div class="cover-img-bg" style="background-image: url({{ '/storage/' . $event['poster'] }});">
                <img class="cover-img" src="{{ '/storage/' . $event['poster'] }}" alt="{{ $event['title'] }}" />
            </div>
        </div>
    </section>

    <!--ABOUT-->
    <section>
        <div class="pt-lg-4 pb-lg-11 py-4">
            <div class="container">
                <div class="row">
                    <div class="col-xl-8 col-lg-8 col-md-12 col-12">
                        <div class="card mb-4">
                            <!-- listing detail head -->
                            <div class="card-body p-4 py-3">
                                <h2 class="mb-2">{{ $event['title'] }}</h2>
                                <p class="mb-3 fs-8">{{ $event['excerpt'] }}</p>
								<!--
                                <p class="fs-6 mb-2">

                                    {{-- CUSTOM --}}
                                    @php $organiser_slug = $extra['organiser']->organisation; @endphp
                                    @if (!empty($organiser_slug))
                                        <a class="text-dark text-sm"
                                            href="{{ route('organiser_show', [$event->slug, $organiser_slug]) }}">@lang('eventmie-pro::em.by')
                                            {{ $extra['organiser']->organisation }}</a>
                                    @endif
                                    {{-- CUSTOM --}}
                                    @if (!empty($event['online_location']))
                                        <span class="badge bg-primary text-white"><i class="fas fa-signal"></i>&nbsp;
                                            @lang('eventmie-pro::em.online_event')</span>
                                    @endif

                                    @if ($ended)
                                        <span class="badge bg-danger text-white">@lang('eventmie-pro::em.event_ended')</span>
                                    @endif
                                    <span class="badge bg-primary text-white">{{ $category['name'] }}</span>

                                    @if (!empty($free_tickets))
                                        <span class="badge bg-primary text-white">@lang('eventmie-pro::em.free_tickets')</span>
                                    @endif

                                    @if ($event->repetitive)
                                        @if ($event->repetitive_type == 1)
                                            <span class="badge bg-primary text-white">@lang('eventmie-pro::em.repetitive_daily_event')</span>
                                        @elseif($event->repetitive_type == 2)
                                            <span class="badge bg-primary text-white">@lang('eventmie-pro::em.repetitive_weekly_event')</span>
                                        @elseif($event->repetitive_type == 3)
                                            <span class="badge bg-primary text-white">@lang('eventmie-pro::em.repetitive_monthly_event')</span>
                                        @endif
                                    @endif

                                    @if ($ended)
                                        <span class="badge bg-danger text-white">@lang('eventmie-pro::em.event_ended')</span>
                                    @endif
                                </p>
								-->
                            </div>
							<!-- social links vimar changed
                            <div class="card-footer bg-gradient">
                                <div class="text-white">
                                    <span><strong>@lang('eventmie-pro::em.share_event') &nbsp;</strong></span>
                                    <a class="me-1 text-white  badge text-bg-primary" target="_blank"
                                        href="https://www.facebook.com/sharer/sharer.php?u={{ url()->current() }}">
                                        <i class="fab fa-facebook"></i>
                                    </a>
                                    <a class="me-1 text-white  badge text-bg-primary" target="_blank"
                                        href="https://twitter.com/intent/tweet?text={{ urlencode($event->title) }}&url={{ url()->current() }}">

                                        <i class="fab fa-twitter"></i>
                                    </a>
                                    <a class="me-1 text-white  badge text-bg-primary" target="_blank"
                                        href="http://www.linkedin.com/shareArticle?mini=true&url={{ url()->current() }}&title={{ urlencode($event->title) }}">
                                        <i class="fab fa-linkedin"></i>
                                    </a>
                                    <a class="me-1 text-white  badge text-bg-primary" target="_blank"
                                        href="https://wa.me/?text={{ url()->current() }}">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                    <a class="me-1 text-white  badge text-bg-primary" target="_blank"
                                        href="https://www.reddit.com/submit?title={{ urlencode($event->title) }}&url={{ url()->current() }}">
                                        <i class="fab fa-reddit"></i>
                                    </a>

                                    <a class="me-1 text-white  badge text-bg-primary" href="javascript:void(0)"
                                        onclick="copyToClipboard()"><i class="fas fa-link"></i></a>

                                </div>

                            </div>
							-->
                            <!-- listing detail head -->
                        </div>
                        <!--SCHEDULE-->
						<!-- vimar change click buy ticket -->
                        <div class="card mb-4 bg-light" id="buy-tickets" style="position:absolute;left:-9000000px;">
                            <div class="card-body p-4">
								
                                <div class="mb-4 text-left">
                                    @if ($event->merge_schedule)
										
                                        <h4 class="mb-0 fw-bold h4">
                                            @lang('eventmie-pro::em.get_tickets') &nbsp;
                                            <div class="badge bg-primary position-relative">
                                                @lang('eventmie-pro::em.seasonal_tickets')
                                                <span
                                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                                    <i class="fas fa-medal"></i>
                                                    <span class="visually-hidden">&nbsp;</span>
                                                </span>
                                            </div>
                                        </h4>
                                        <p class="text-primary"> @lang('eventmie-pro::em.seasonal_tickets_ie')</p>
                                    @else
                                        <h4 class="mb-0 fw-bold h4 test">@lang('eventmie-pro::em.get_tickets')</h4>
                                    @endif
                                </div>
								
                                <div class="row">
                                    <div class="col-xl-12 col-md-12 col-12">
                                        <div class="row">
                                            <div class="col-12">
                                                <select-dates :event="{{ json_encode($event, JSON_HEX_APOS) }}"
                                                    :max_ticket_qty="{{ json_encode($max_ticket_qty, JSON_HEX_APOS) }}"
                                                    :login_user_id="{{ json_encode(Auth::check() ? Auth::id() : null, JSON_HEX_APOS) }}"
                                                    :is_customer="{{ Auth::check() ? (Auth::user()->hasRole('customer') ? 1 : 0) : 1 }}"
                                                    :is_organiser="{{ Auth::check() ? (Auth::user()->hasRole('organiser') || Auth::user()->hasRole('pos') ? 1 : 0) : 0 }}"
                                                    :is_pos="{{ Auth::check() ? (Auth::user()->hasRole('pos') ? 1 : 0) : 0 }}"
                                                    :is_admin="{{ Auth::check() ? (Auth::user()->hasRole('admin') ? 1 : 0) : 0 }}"
                                                    :is_paypal="{{ $is_paypal }}"
                                                    :is_offline_payment_organizer="{{ setting('booking.offline_payment_organizer') ? 1 : 0 }}"
                                                    :is_offline_payment_customer="{{ setting('booking.offline_payment_customer') ? 1 : 0 }}"
                                                    :tickets="{{ json_encode($tickets, JSON_HEX_APOS) }}"
                                                    :booked_tickets="{{ json_encode($booked_tickets, JSON_HEX_APOS) }}"
                                                    :currency="{{ json_encode($currency, JSON_HEX_APOS) }}"
                                                    :total_capacity="{{ $total_capacity }}"
                                                    :date_format="{{ json_encode(
                                                        [
                                                            'vue_date_format' => format_js_date(),
                                                            'vue_time_format' => format_js_time(),
                                                        ],
                                                        JSON_HEX_APOS,
                                                    ) }}">
                                                </select-dates>
                                            </div>

                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
						<!-- -->
                        <!--SCHEDULE END-->

                        <!-- Seating Chart Image -->
                        @if ($event->seatingchart_image)
                            <div class="card mb-4">
                                <div class="card-body p-4">
                                    <h4 class="mb-3">@lang('eventmie-pro::em.seating_chart')</h4>
                                    <div class="row">
                                        <div class="col-12">
                                            <img src="/storage/{{ $event->seatingchart_image }}" alt="{{ $event->title }}"
                                                class="rounded mx-auto d-block img-fluid" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <!-- Seating Chart END -->

                        <!-- post single -->
                        <div class="card mb-4">
                            <div class="card-body p-4">
                                <!-- <h4>@lang('eventmie-pro::em.event_info')</h4> -->
                                <p>{!! $event['description'] !!}</p>
                            </div>
                        </div>
                        <!-- post single -->

                        <!--Event FAQ-->
                        @if ($event['faq'])
                            <div class="card mb-4" style="display: none;">
                                <div class="card-body p-4">
                                    <h4 class=" text-left">@lang('eventmie-pro::em.event_info')</h4>
                                    <p>{!! $event['faq'] !!}</p>
                                </div>
                            </div>
                        @endif
                        <!--Event FAQ END-->

                        <!--TAGS-->
                        @php $i = 0; @endphp
                        @foreach ($tag_groups as $key => $group)
                            @php $i++; @endphp
                            <div class="card mb-4  {{ $i % 2 ? 'bg-light' : '' }}">
                                <div class="card-body p-4">
                                    <!-- section heading  -->
                                    <h4 class="mb-3">{{ ucfirst($key) }}</h4>
                                    <div class="row">
                                        @foreach ($group as $key1 => $value)
                                            <div class="col-lg-4 col-md-6 col-12 text-center">
                                                <!-- member -->
                                                @if ($value['is_page'] > 0)
                                                    <a
                                                        href="{{ route('eventmie.events_tags', [$event->slug, str_replace(' ', '-', $value['title'])]) }}">
                                                    @elseif($value['website'])
                                                        <a href="{{ $value['website'] }}" target="_blank">
                                                @endif
                                                <div class="mb-3">
                                                    @if ($value['image'])
                                                        <img src="/storage/{{ $value['image'] }}"
                                                            alt="{{ $value['title'] }}" class="rounded-3 w-100 mb-4 " />
                                                    @else
                                                        <img src="{{ asset('ep_img/512x512.webp') }}"
                                                            alt="{{ $value['title'] }}" class="rounded-3 w-100 mb-4 " />
                                                    @endif
                                                    <h5 class="mb-0">
                                                        @if ($value['is_page'] > 0)
                                                            <a
                                                                href="{{ route('eventmie.events_tags', [$event->slug, str_replace(' ', '-', $value['title'])]) }}">{{ $value['title'] }}</a>
                                                        @elseif($value['website'])
                                                            <a href="{{ $value['website'] }}"
                                                                target="_blank">{{ $value['title'] }}</a>
                                                        @else
                                                            {{ $value['title'] }}
                                                        @endif
                                                    </h5>
                                                    <p class="small font-weight-semibold mb-1">
                                                        @if ($value['sub_title'])
                                                            {{ $value['sub_title'] }}
                                                        @endif
                                                    </p>

                                                    @if ($value['is_page'] > 0)
                                                        <div class="text-center text-white">
                                                            <a class="me-1 badge text-dark"
                                                                href="{{ $value['twitter'] }}" target="_blank"><i
                                                                    class="fab fa-twitter"></i></a>
                                                            <a class="me-1 badge text-dark"
                                                                href="{{ $value['facebook'] }}" target="_blank"><i
                                                                    class="fab fa-facebook"></i></a>
                                                            <a class="me-1 badge text-dark"
                                                                href="{{ $value['instagram'] }}" target="_blank"><i
                                                                    class="fab fa-instagram"></i></a>
                                                            <a class="me-1 badge text-dark"
                                                                href="{{ $value['linkedin'] }}" target="_blank"><i
                                                                    class="fab fa-linkedin"></i></a>
                                                            <a class="me-1 badge text-dark"
                                                                href="{{ $value['website'] }}" target="_blank"><i
                                                                    class="fas fa-globe"></i></a>
                                                        </div>
                                                    @endif

                                                </div>
                                                @if ($value['is_page'] > 0 || $value['website'])
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!--Tags END-->

                        <!--PHOTO GALLERY-->
                        @if (!empty($event->images))
                            <div class="card mb-4" id="lgx-photo-gallery">
                                <div class="card-body p-4 pb-0">
                                    <h4 class="mb-3">@lang('eventmie-pro::em.event_gallery')</h4>
                                    <gallery-images :gimages="{{ $event->images }}" :style=''>
                                    </gallery-images>
                                </div>
                            </div>
                        @endif
                        <!--PHOTO GALLERY END-->

                        <!--Event Video-->
                        @if (!empty($event->video_link))
                            <div class="card mb-4">
                                <div class="card-body p-4">
                                    <h4 class="mb-3">@lang('eventmie-pro::em.watch_trailer')</h4>
                                    {{-- CUSTOM --}}
                                    @foreach (json_decode($event->video_link) as $item)
                                        @if (count(json_decode($event->video_link)) == 1)
                                            <div class="ratio ratio-16x9">
                                                <iframe src="https://www.youtube.com/embed/{{ $item }}"
                                                    allowfullscreen class="rounded-4 img-hover"></iframe>
                                            </div>
                                        @else
                                            <div class="ratio ratio-16x9">
                                                <iframe src="https://www.youtube.com/embed/{{ $item }}"
                                                    allowfullscreen class="rounded-4 img-hover"></iframe>
                                            </div>
                                        @endif
                                    @endforeach
                                    {{-- CUSTOM --}}
                                </div>
                            </div>
                        @endif
                        <!--Event Video END-->

                        <!--GOOGLE MAP-->
                        <!-- @if ($event->latitude && $event->longitude)
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <h4 class="mb-3">@lang('eventmie-pro::em.location')</h4>
                                        </div>
                                        <div class="col-6">
                                            <a href="{{ 'https://www.google.com/maps/search/' . $event->address . '/' . $event->latitude . ',' . $event->longitude }}"
                                                class="btn btn-primary bg-gradient float-end btn-sm mt-1" id="get_directions">
                                                <i class="fas fa-location-arrow"></i> @lang('eventmie-pro::em.get_directions')
                                            </a>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="innerpage-section g-map-wrapper">
                                            <div class="lgxmapcanvas map-canvas-default">
                                                <g-component :lat="{{ json_encode($event->latitude, JSON_HEX_APOS) }}"
                                                    :lng="{{ json_encode($event->longitude, JSON_HEX_APOS) }}">
                                                </g-component>
                                                {{--  CUSTOM --}}
                                                <div id="warnings-panel"></div>
                                                <div id="map" style="height: 100%"></div>
                                                {{--  CUSTOM --}}

                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        @endif -->
                        <!--GOOGLE MAP END-->

                        <!-- Reviews -->
                        @if ($event->show_reviews)
                            <div class="card mb-4">
                                <div class="card-body p-4 pb-0">
                                    <h4 class="mb-3">@lang('eventmie-pro::em.rating_review')</h4>
                                    <div class="row">
                                        <div class="col-md-12">
                                            @include('vendor.eventmie-pro.events.custom.average_rating')
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <!--Reviews END-->

                    </div>

                    {{-- Event Start Date Start --}}
                    <div class="col-xl-4 col-lg-4 col-md-12 col-12">

                        <!-- widget -->
						
                        <div class="card mb-4">
                            <div class="card-body p-4">
                                <div class="d-grid">
                                    <a class="btn btn-primary btn-lg" style="background:#FC4C02!important;"
                                        href={{ $event->repetitive > 0 ? '#buy-tickets' : 'javascript:void(0)' }}
                                        onclick={{ $event->repetitive > 0 ? 'javascript:void(0)' : 'triggerSignleDay()' }}>
                                        <i class="fas fa-ticket-alt"></i>
                                        @lang('eventmie-pro::em.get_tickets')

                                    </a>
                                </div>
                            </div>
                        </div>
						
                        <div class="card mb-4">
                            <div class="card-body">
                                <!-- card-->
                                <!--<h4 class="mb-2 fw-bold">@lang('eventmie-pro::em.where')</h4>-->
                                <p style="padding-top:1rem;">
                                    @if (!empty($event['online_location']))
                                        <strong>@lang('eventmie-pro::em.online_event')</strong> <br>
                                    @endif

                                    @if ($event->venues->isNotEmpty())
                                        <!-- vimar change<a class="col-white" href="{{ route('eventmie.venues.show', [$event->venues[0]->slug]) }}"><strong>{{ $event->venue }} -->
                                        <a class="col-white" href="https://maps.app.goo.gl/VFzXjWHsVauTaEkKA"><strong>{{ $event->venue }}
                                            <i class="fas fa-external-link-alt"></i></strong> </a>
                                    @else
                                        <strong>{{ $event->venue }}</strong>
                                    @endif

                                    <br>
                                    @if ($event->address)
                                        {{ $event->address }} {{ $event->zipcode }} <br>
                                    @endif

                                    @if ($event->city)
                                        {{ $event->city }},
                                    @endif

                                    @if ($event->state)
                                        {{ $event->state }},
                                    @endif

                                    @if ($country)
                                        {{ $country->get('country_name') }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- card-->
                        <div class="card  mb-4">
                            <div class="card-body">
                                <!--<h4 class="mb-2 fw-bold">@lang('eventmie-pro::em.when')</h4>-->
                                <p>{!! $event['faq'] !!}</p>
                                <p style="display: none;">
                                    @if (!$event->repetitive)
                                    
                                        {{ userTimezone($event->start_date . ' ' . $event->start_time, 'Y-m-d H:i:s', format_carbon_date(false)) }}

                                        {{ showTimezone() }}

                                        -

                                        {{ userTimezone($event->end_date . ' ' . $event->end_time, 'Y-m-d H:i:s', format_carbon_date(false)) }}

                                        {{ showTimezone() }}
                                    
                                     @else
                                        {{ userTimezone($event->start_date . ' ' . $event->start_time, 'Y-m-d H:i:s', format_carbon_date(true)) }}

                                        -

                                        {{ userTimezone($event->start_date . ' ' . $event->start_time, 'Y-m-d H:i:s', 'Y-m-d') <= userTimezone($event->end_date . ' ' . $event->end_time, 'Y-m-d H:i:s', 'Y-m-d') ? userTimezone($event->end_date . ' ' . $event->end_time, 'Y-m-d H:i:s', format_carbon_date(true)) : userTimezone($event->start_date . ' ' . $event->start_time, 'Y-m-d H:i:s', format_carbon_date(true)) }}
                                    @endif
                                </p>
                                
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body p-4">
                                <h5 class="mb-0">
                                    @lang('eventmie-pro::em.organiser')&nbsp;<i class="fa-solid fa-medal text-primary text-sm"></i>
                                </h5>
                                <div class="text-center mt-4">
                                    @if (!empty($organiser_slug))
                                    <a class="text-dark text-sm"
                                        href="http://www.idealhome.ie/" target="_blank">
                                        @if ($extra['organiser']->avatar)
                                            <img src="/storage/{{ $extra['organiser']->avatar }}"
                                                alt="{{ $organiser_slug }}"
                                                class="rounded-circle avatar avatar-lg mb-2" />
                                        @else
                                            <img src="{{ asset('ep_img/512x512.webp') }}"
                                                alt="{{ $organiser_slug }}"
                                                class="rounded-circle avatar avatar-lg mb-2" />
                                        @endif
                                    </a>
                                    @endif

                                    <h5 class="mb-0">
                                        @if (!empty($organiser_slug))
                                            <a class="text-dark"
                                                href="{{ route('organiser_show', [$event->slug, $organiser_slug]) }}">
                                                {{ $extra['organiser']->organisation }}
                                            </a>
                                        @endif
                                    </h5>
                                </div>
                            </div>
                        </div>

                    </div>
                    {{-- Event Start Date End --}}
                </div>
            </div>
        </div>

    </section>
    <!--ABOUT END-->

@endsection


@section('javascript')
    <script type="text/javascript">
        var google_map_key = {!! json_encode($google_map_key) !!};

        var stripe_publishable_key = {!! json_encode(setting('apps.stripe_public_key')) !!};

        var stripe_secret_key = {!! json_encode($extra['stripe_secret_key']) !!};

        var is_stripe = {!! json_encode($extra['is_stripe']) !!};

        var is_authorize_net = {!! json_encode($extra['is_authorize_net']) !!};

        var is_bitpay = {!! json_encode($extra['is_bitpay']) !!};

        var is_stripe_direct = {!! json_encode($extra['is_stripe_direct']) !!};

        var is_twilio = {!! json_encode($extra['is_twilio']) !!};

        var default_payment_method = {!! json_encode($extra['default_payment_method']) !!};

        var sale_tickets = {!! json_encode($extra['sale_tickets']) !!};

        var is_pay_stack = {!! json_encode($extra['is_pay_stack']) !!};

        var is_razorpay = {!! json_encode($extra['is_razorpay']) !!};

        var is_paytm = {!! json_encode($extra['is_paytm']) !!};

        var is_usaepay = {!! json_encode($is_usaepay) !!};

        var login_user_id = {!! json_encode(Auth::check() ? Auth::id() : 0, JSON_HEX_APOS) !!};

        var is_guest_checkout = {!! json_encode(setting('multi-vendor.guest_checkout'), JSON_HEX_APOS)  !!};

        var is_otp_verify = {!! json_encode(setting('multi-vendor.is_otp_verify'), JSON_HEX_APOS)  !!};

        
    </script>



    <script type="text/javascript" src="https://js.stripe.com/v3/"></script>
    <script src="https://cdn.jsdelivr.net/npm/v-mask/dist/v-mask.min.js"></script>
    <script type="text/javascript" src="{{ mix('js/events_show.js') }}"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
    <script type="text/javascript">
        var latitude = {!! json_encode($event->latitude) !!};
        var longitude = {!! json_encode($event->longitude) !!};
        var venue = {!! json_encode($event->venue) !!};


        function initMap() {

            var markerArray = [];

            // Instantiate a directions service.
            var directionsService = new google.maps.DirectionsService;

            // Create a map and center it on Manhattan.
            var map = new google.maps.Map(document.getElementById('map'), {
                zoom: 13,
                center: {
                    lat: parseFloat(latitude),
                    lng: parseFloat(longitude)
                }
            });

            var marker = new google.maps.Marker({
                position: {
                    lat: parseFloat(latitude),
                    lng: parseFloat(longitude)
                },
                title: venue,
            });

            // To add the marker to the map, call setMap();
            marker.setMap(map);

            // Create a renderer for directions and bind it to the map.
            var directionsDisplay = new google.maps.DirectionsRenderer({
                map: map
            });

            // Instantiate an info window to hold step text.
            var stepDisplay = new google.maps.InfoWindow;

            // Listen to change events from the start and end lists.
            var onChangeHandler = function() {
                // get current location latlngs
                getUserLocationLatLong(directionsDisplay, directionsService, markerArray, stepDisplay, map);
            };
            document.getElementById('get_directions').addEventListener('click', onChangeHandler);

        }

        let infoWindow;

        function getUserLocationLatLong(directionsDisplay, directionsService, markerArray, stepDisplay, map) {

            console.log('heyy');
            // map = new google.maps.Map(document.getElementById("map"), {
            //     zoom: 13,
            //     center: {lat:  parseFloat(latitude), lng:  parseFloat(longitude)}
            // });
            infoWindow = new google.maps.InfoWindow();

            infoWindow = new google.maps.InfoWindow();

            infoWindow = new google.maps.InfoWindow();

            // Try HTML5 geolocation.
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => {
                        const pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        calculateAndDisplayRoute(directionsDisplay, directionsService, markerArray, stepDisplay, map,
                            position.coords.latitude, position.coords.longitude);
                    },
                    () => {
                        alert('Error-1');
                    }
                );
            } else {
                // Browser doesn't support Geolocation
                alert('Browser doesnt support Geolocation');
            }

            console.log('heyy');
            console.log('heyy');
        }



        function calculateAndDisplayRoute(directionsDisplay, directionsService, markerArray, stepDisplay, map, cur_lat,
            cur_lng) {
            // First, remove any existing markers from the map.
            for (var i = 0; i < markerArray.length; i++) {
                markerArray[i].setMap(null);
            }

            directionsService.route({
                origin: new google.maps.LatLng(parseFloat(cur_lat), parseFloat(cur_lng)),
                destination: new google.maps.LatLng(parseFloat(latitude), parseFloat(longitude)),
                travelMode: 'DRIVING',
                drivingOptions: {
                    departureTime: new Date( /* now, or future date */ ),
                    trafficModel: google.maps.TrafficModel.BEST_GUESS
                },
            }, function(response, status) {
                console.log(response);
                if (status === 'OK') {
                    document.getElementById('warnings-panel').innerHTML =
                        '<b>' + response.routes[0].warnings + '</b>';
                    directionsDisplay.setDirections(response);
                    showSteps(response, markerArray, stepDisplay, map);
                } else {
                    window.alert(trans('em.fail_directions'));
                }
            });
        }

        function showSteps(directionResult, markerArray, stepDisplay, map) {
            var myRoute = directionResult.routes[0].legs[0];
            for (var i = 0; i < myRoute.steps.length; i++) {
                var marker = markerArray[i] = markerArray[i] || new google.maps.Marker;
                marker.setMap(map);
                marker.setPosition(myRoute.steps[i].start_location);
                attachInstructionText(
                    stepDisplay, marker, myRoute.steps[i].instructions, map);
            }
        }

        function attachInstructionText(stepDisplay, marker, text, map) {
            google.maps.event.addListener(marker, 'click', function() {
                stepDisplay.setContent(text);
                stepDisplay.open(map, marker);
            });
        }



        function open() {
            document.getElementById("review_modal").style.display = "block";
        }

        //  Single day non-repetitive event
        function triggerSignleDay() {
            const hash = location.hash;
            if (hash != '#/checkout') {
                parent.location.hash = "/checkout";
                // parent.location.hash = "/checkout?&ticket={{$tickets[0]->title}}&promocode={{$tickets[0]->promocodes[0]->code}}";
            }
            document.getElementById('buy_ticket_btn').click();
        }
        // Function to get the value of a query parameter by its name
        function getQueryParam(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:window.location.href;e=e.replace(/[\[\]]/g,"\\$&");var n=new RegExp("[?&]"+e+"(=([^&#]*)|&|#|$)"),o=n.exec(t);return o?o[2]?decodeURIComponent(o[2].replace(/\+/g," ")):"":null}

        // Run the check when the page loads
        window.onload = checkParamsOnLoad;

        // Function to check if 'ticket' and 'promocode' are not null
        function checkParamsOnLoad() {
            const ticket = getQueryParam('ticket');
            const promocode = getQueryParam('promocode');

            // Ensure both ticket and promocode are not null
            if (ticket !== null && promocode !== null) {
                const buyTicketButton = document.getElementById('buy_ticket_btn');

                // Check if the button element exists before attempting to click
                if (buyTicketButton) {
                    buyTicketButton.click();
                } else {
                    console.error("Button with id 'buy_ticket_btn' not found.");
                }
            }
        }


		
        //vimar change		
        // simulate click on small window
        var myElementToCheckIfClicksAreInsideOf = document.querySelector('#buy-tickets');

        document.body.addEventListener('click', function (event) {
            
            if (myElementToCheckIfClicksAreInsideOf.contains(event.target)) {
                console.log('clicked inside');
        
                jQuery('.click-guest').on('click', function(event) {
                    setTimeout(function() {
                        console.log('click-guest');
                        jQuery('.vimar-footer-small-window-click').click();
                    }, 1);
                });
            }
        });

    </script>
@stop
<style>
    .badge.bg-gradient{display:none;}
    .list-group-item .badge.bg-gradient{background:transparent!important;margin-bottom:0!important;}
    .small-window-to-hidden{display:none;}
</style>