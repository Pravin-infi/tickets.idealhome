@extends('eventmie::o_dashboard.index')

@section('title')
    @lang('eventmie-pro::em.dashboard')
@endsection

@section('o_dashboard')
    <div class="container-fluid my-2" onclick="navToggle()">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-12">
                <div class="mb-4 d-md-flex justify-content-between align-items-center">
                    <div class="mb-3 mb-md-0">
                        <h1 class="mb-0 h2 fw-bold">@lang('eventmie-pro::em.hello') {{ Auth::user()->name }}</h1>
                        <p class="mb-0">@lang('eventmie-pro::em.organizer_dashboard_activity')</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4 col-md-12 col-12">
                <!-- Card -->
                <div class="card mb-4 border-0 shadow-sm">
                    <!-- Card body -->
                    <div class="card-body p-4">

                        <div>
                            <h5 class="mb-0">@lang('eventmie-pro::em.total') @lang('eventmie-pro::em.events')</h5>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-4">
                            <h2 class="fw-bold mb-0 fs-1">
                                {{ $total_events }}
                            </h2>

                            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round"
                                stroke-linejoin="round" class="feather feather-activity text-primary">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 col-12">
                <!-- Card -->
                <div class="card mb-4 border-0 shadow-sm">
                    <!-- Card body -->
                    <div class="card-body p-4">

                        <div>
                            <h5 class="mb-0">@lang('eventmie-pro::em.total') @lang('eventmie-pro::em.earning')</h5>
                        </div>

                        @foreach ($total_earning as $earning)
                            <div class="d-flex align-items-center justify-content-between">
                                <h2 class="fw-bold mb-0 fs-3">
                                    {{ $earning->organiser_earning_total }}
                                </h2>
                                <span class="fw-bold mb-0 fs-5 text-primary">{{ $earning->currency }}</span>

                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 col-12">
                <div class="card mb-4 border-0 shadow-sm">
                    <!-- Card body -->
                    <div class="card-body p-4">

                        <div>
                            <h5 class="mb-0">@lang('eventmie-pro::em.total') @lang('eventmie-pro::em.bookings')</h5>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-4">
                            <h2 class="fw-bold mb-0 fs-1">
                                {{ $total_bookings }}
                            </h2>



                            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round"
                                stroke-linejoin="round" class="feather feather-credit-card text-primary">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>

            @if (!empty($total_scan_tickets))
                <div class="col-lg-4 col-md-12 col-12">
                    <div class="card mb-4 border-0 shadow-sm">
                        <!-- Card body -->
                        <div class="card-body p-4">

                            <div>
                                <h5 class="mb-0">@lang('eventmie-pro::em.total') @lang('eventmie-pro::em.scan') @lang('eventmie-pro::em.bookings')</h5>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-4">
                                <h2 class="fw-bold mb-0 fs-1">
                                    {{ $total_scan_tickets }}
                                </h2>



                                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round"
                                    stroke-linejoin="round" class="feather feather-credit-card text-primary">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                            </div>
                        </div>
                    </div>

                </div>
            @endif
        </div>
        <div class="row">
            <div class="col-lg-12 col-md-12 col-12">
                <!-- Card -->
                <div class="card mb-4 border-0 shadow-sm">

                    <!-- Card body -->
                    <div class="card-body p-4">
                        <h4 class="card-title">{{ __('eventmie-pro::em.top_selling_events') }}</h4>
                        <!-- Earning chart -->
                        <div id="earning" class="apex-charts">
                            {!! $eventsChart->container() !!}
                        </div>

                    </div>

                </div>
            </div>

        </div>

        <div class="row">
            <div class="col-lg-12 col-md-12 col-12">
                <!-- Card -->
                <div class="card mb-4 border-0 shadow-sm">

                    <!-- Card body -->
                    <div class="card-body p-4">
                        <h4 class="card-title">{{ __('eventmie-pro::em.event_tickets_statistics') }}</h4>
                        <!-- Earning chart -->
                        <div id="earning" class="apex-charts">
                            @if (Auth::user()->hasRole('organiser'))
                                <event-total :event_total_route="{{ json_encode(route('event_total')) }}"></event-total>
                            @elseif (Auth::user()->hasRole('pos'))
                                <event-total :event_total_route="{{ json_encode(route('pos.event_total')) }}"></event-total>
                            @elseif (Auth::user()->hasRole('scanner'))
                                <event-total
                                    :event_total_route="{{ json_encode(route('scanner.event_total')) }}"></event-total>
                            @endif
                        </div>

                    </div>

                </div>
            </div>

        </div>


    </div>
@endsection

@section('javascript')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>
    {!! $eventsChart->script() !!}

    <script type="text/javascript" src="{{ mix('js/event_dashboard.js') }}"></script>
@stop
