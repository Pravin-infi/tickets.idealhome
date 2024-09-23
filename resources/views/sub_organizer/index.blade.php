@extends('eventmie::o_dashboard.index')

{{-- Page title --}}
@section('title')
    @lang('eventmie-pro::em.manage') @lang('eventmie-pro::em.sub_organizers')
@endsection


@section('o_dashboard')

    <div class="container-fluid my-2">
        <div class="row">
            <div class="col-md-12">
                <router-view></router-view>
            </div>
        </div>
    </div>


@endsection

@section('javascript')
    <script>
        var path = {!! json_encode($path, JSON_HEX_TAG) !!};
    </script>

    <script type="text/javascript" src="{{ mix('js/sub_organizers.js') }}"></script>
@stop
