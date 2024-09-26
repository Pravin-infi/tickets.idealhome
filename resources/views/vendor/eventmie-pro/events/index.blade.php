@extends('eventmie::layouts.app')

{{-- Page title --}}
@section('title')
    @lang('eventmie-pro::em.events')
@endsection

@section('content')
    <main>
        <div class="lgx-page-wrapper">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        @component('eventmie::skeleton.event') @endcomponent
                    </div>
                </div>
            </div>
            <router-view
                :date_format="{{ json_encode(
                    [
                        'vue_date_format' => format_js_date(),
                        'vue_time_format' => format_js_time(),
                    ],
                    JSON_HEX_APOS,
                ) }}">
            </router-view>
        </div>
    </main>
@endsection

@section('javascript')

    <script>
        var path = {!! json_encode($path, JSON_HEX_TAG) !!};
        var events_slider = false;
    </script>
    <script type="text/javascript" src="{{ mix('js/events_listing.js') }}"></script>
@stop
