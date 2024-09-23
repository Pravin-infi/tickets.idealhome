<div id="db-wrapper-two">
    <nav class="navbar-vertical-compact shadow-sm mt-9">
        <div data-simplebar style="" class="vh-100 mt-5">
            <ul class="navbar-nav flex-column" id="sideNavbar">

                @if (Auth::user()->hasRole('admin'))
                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'eventmie.myevents_index' || Route::currentRouteName() == 'eventmie.myevents_form' ? 'active' : '' }}"
                            href="{{ route('eventmie.myevents_index') }}" title="@lang('eventmie-pro::em.events')">
                            <span class="nav-icon"><i class="far fa-calendar-alt"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.events')</span>
                        </a>
                    </li>

                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'eventmie.ticket_scan' ? 'active' : '' }}"
                            href="{{ route('eventmie.ticket_scan') }}" title="@lang('eventmie-pro::em.scan_ticket')">
                            <span class="nav-icon"><i class="fas fa-qrcode"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.scan_ticket')</span>
                        </a>
                    </li>

                       {{-- CUSTOM CUSTOM --}}
                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'failed-bookings.index' ? 'active' : '' }}"
                            href="{{ route('failed-bookings.index') }}" title="@lang('eventmie-pro::em.failed') @lang('eventmie-pro::em.bookings')">
                            <span class="nav-icon"><i class="fa fa-exclamation-triangle"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.failed') @lang('eventmie-pro::em.bookings')</span>
                        </a>
                    </li>
                    {{-- CUSTOM CUSTOM --}}
                @endif

                @if (Auth::user()->hasRole('organiser'))
                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'eventmie.o_dashboard' ? 'active' : '' }}"
                            href="{{ route('eventmie.o_dashboard') }}" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="@lang('eventmie-pro::em.dashboard')">
                            <span class="nav-icon"><i class="fas fa-gauge"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.dashboard')</span>
                        </a>
                    </li>

                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'eventmie.myevents_index' || Route::currentRouteName() == 'eventmie.myevents_form' ? 'active' : '' }}"
                            href="{{ route('eventmie.myevents_index') }}" title="@lang('eventmie-pro::em.myevents')">
                            <span class="nav-icon"><i class="far fa-calendar-alt"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.myevents')</span>
                        </a>
                    </li>

                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'eventmie.ticket_scan' ? 'active' : '' }}"
                            href="{{ route('eventmie.ticket_scan') }}" title="@lang('eventmie-pro::em.scan_ticket')">
                            <span class="nav-icon"><i class="fas fa-qrcode"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.scan_ticket')</span>
                        </a>
                    </li>

                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'eventmie.obookings_index' ? 'active' : '' }}"
                            href="{{ route('eventmie.obookings_index') }}" title="@lang('eventmie-pro::em.mybookings')">
                            <span class="nav-icon"><i class="fas fa-money-check-alt"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.mybookings')</span>
                        </a>
                    </li>


                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'eventmie.event_earning_index' ? 'active' : '' }}"
                            href="{{ route('eventmie.event_earning_index') }}" title="@lang('eventmie-pro::em.myearning')">
                            <span class="nav-icon"><i class="fas fa-wallet"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.myearning')</span>
                        </a>
                    </li>

                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'eventmie.tags_form' ? 'active' : '' }}"
                            href="{{ route('eventmie.tags_form') }}" title="@lang('eventmie-pro::em.mytags')">
                            <span class="nav-icon"><i class="fas fa-user-tag"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.mytags')</span>
                        </a>
                    </li>

                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'eventmie.myvenues.index' ? 'active' : '' }}"
                            href="{{ route('eventmie.myvenues.index') }}" title="@lang('eventmie-pro::em.myvenues')">
                            <span class="nav-icon"><i class="fas fa-map-location"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.myvenues')</span>
                        </a>
                    </li>

                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'myglists_index' ? 'active' : '' }}"
                            href="{{ route('myglists_index') }}" title="@lang('eventmie-pro::em.manage') @lang('eventmie-pro::em.guests')">
                            <span class="nav-icon"><i class="fas fa-people-group"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.guests')</span>
                        </a>
                    </li>

                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'sub_organizer.index' ? 'active' : '' }}"
                            href="{{ route('sub_organizer.index') }}" title="@lang('eventmie-pro::em.manage') @lang('eventmie-pro::em.sub_organizers')">
                            <span class="nav-icon"><i class="fas fa-people-arrows"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.sub_organizers')</span>
                        </a>
                    </li>

                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'manage_reviews.index' ? 'active' : '' }}"
                            href="{{ route('manage_reviews.index') }}" title="@lang('eventmie-pro::em.manage') @lang('eventmie-pro::em.review')">
                            <span class="nav-icon"><i class="fas fa-star-half-stroke"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.reviews')</span>
                        </a>
                    </li>

                 
                @endif

                @if (Auth::user()->hasRole('pos'))
                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'pos.o_dashboard' ? 'active' : '' }}"
                            href="{{ route('pos.o_dashboard') }}" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="@lang('eventmie-pro::em.pos') @lang('eventmie-pro::em.dashboard')">
                            <span class="nav-icon"><i class="fas fa-gauge"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.pos') @lang('eventmie-pro::em.dashboard')</span>
                        </a>
                    </li>

                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'pos.index' ? 'active' : '' }}"
                            href="{{ route('pos.index') }}" title="@lang('eventmie-pro::em.pos') @lang('eventmie-pro::em.bookings')">
                            <span class="nav-icon"><i class="fas fa-money-check-alt"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.pos') @lang('eventmie-pro::em.bookings')</span>
                        </a>
                    </li>
                @endif

                @if (Auth::user()->hasRole('scanner'))

                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'scanner.o_dashboard' ? 'active' : '' }}"
                            href="{{ route('scanner.o_dashboard') }}" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="@lang('eventmie-pro::em.scanner') @lang('eventmie-pro::em.dashboard')">
                            <span class="nav-icon"><i class="fas fa-gauge"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.scanner') @lang('eventmie-pro::em.dashboard')</span>
                        </a>
                    </li>

                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'eventmie.ticket_scan' ? 'active' : '' }}"
                            href="{{ route('eventmie.ticket_scan') }}" title="@lang('eventmie-pro::em.scan') @lang('eventmie-pro::em.ticket')">
                            <span class="nav-icon"><i class="fas fa-qrcode"></i></span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.scan') @lang('eventmie-pro::em.ticket')</span>
                        </a>
                    </li>
                    <li class="nav-item tooltip-custom">
                        <a class="nav-link {{ Route::currentRouteName() == 'scanner.index' ? 'active' : '' }}"
                            href="{{ route('scanner.index') }}" title="@lang('eventmie-pro::em.scanner') @lang('eventmie-pro::em.bookings')">
                            <span class="nav-icon"><i class="fas fa-money-check-alt"></i> </span>
                            <span class="tooltiptext">@lang('eventmie-pro::em.scanner') @lang('eventmie-pro::em.bookings')</span>
                        </a>
                    </li>
                @endif
                
            </ul>
        </div>
    </nav>
</div>
