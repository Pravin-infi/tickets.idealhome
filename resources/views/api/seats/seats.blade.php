
<!doctype html>
<html class="no-js" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>

    @include('eventmie::layouts.meta')

    @include('eventmie::layouts.favicon')

    @include('eventmie::layouts.include_css')

    @yield('stylesheet')
</head>

<body class="home" {!! is_rtl() ? 'dir="rtl"' : '' !!}>

    <!--[if lt IE 8]>
    <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade
        your browser</a> to improve your experience.</p>
    <![endif]-->

    {{-- Ziggy directive --}}
    @routes

    {{-- Main wrapper --}}
    <div class="lgx-container" id="eventmie_app">
    
        {{-- @include('eventmie::layouts.header') --}}

        @php 
            $no_breadcrumb = [
                'eventmie.welcome', 
                'eventmie.events_show',
                'eventmie.login', 
                'eventmie.register', 
                'eventmie.register_show', 
                'eventmie.password.request', 
                'eventmie.password.reset',
                'seats.index'
            ]    
        @endphp
        @if (!in_array(Route::currentRouteName(), $no_breadcrumb))
            @include('eventmie::layouts.breadcrumb')
        @endif

        <section class="main-wrapper">
        
            {{-- page content --}}
                <seats-component ref="seat" :ticket="{{json_encode($ticket)}}" :event="{{json_encode($event)}}"  :max_ticket_qty="{{json_encode($max_ticket_qty)}}" :ticket_index="{{json_encode(1)}}" :booked_date_server="{{json_encode($booked_date_server)}}"></seats-component>


            {{-- set progress bar --}}
            <vue-progress-bar></vue-progress-bar>
        </section>

        

    </div>
    <!--Main wrapper end-->

    @include('eventmie::layouts.include_js')
    <script>
        var login_user_id = {!! json_encode(Auth::check() ? Auth::id() : 0, JSON_HEX_APOS) !!};
        
    </script>
    {{-- Page specific javascript --}}
    <script type="text/javascript" src="{{ mix('js/seats.js') }}"></script>

</body>
</html>
