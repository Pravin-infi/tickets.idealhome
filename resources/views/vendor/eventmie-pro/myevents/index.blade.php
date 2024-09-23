@extends('eventmie::o_dashboard.index')

@section('title')
    @lang('eventmie-pro::em.myevents')
@endsection

@section('o_dashboard')
    <div class="container-fluid my-2">
        <div class="row">
            <div class="col-md-12">
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
        </div>
    </div>
@endsection

@section('javascript')
    <script>
        var path = {!! json_encode($path, JSON_HEX_TAG) !!};
        //CUSTOM
        var organizer_id = {!! json_encode($organizer_id, JSON_HEX_TAG) !!};
        var is_admin = {!! json_encode($is_admin, JSON_HEX_TAG) !!};
        //CUSTOM
    </script>

    <script type="text/javascript" src="{{ mix('js/myevents.js') }}"></script>
@stop
