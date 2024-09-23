@extends('eventmie::o_dashboard.index')

@section('title')
    @lang('eventmie-pro::em.manage') @lang('eventmie-pro::em.review')
@endsection

@section('o_dashboard')

    <div class="container-fluid my-2">
        <div class="row">
            <div class="col-md-12">
                <router-view :events="{{ json_encode($events, JSON_HEX_APOS) }}"
                    :is_admin="{{ json_encode($is_admin, JSON_HEX_APOS) }}"></router-view>
            </div>
        </div>
    </div>

@endsection


@section('javascript')
    <script type="text/javascript" src="{{ mix('js/manage_reviews.js') }}"></script>
@stop
