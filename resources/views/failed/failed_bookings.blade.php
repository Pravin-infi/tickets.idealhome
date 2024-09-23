@extends('eventmie::o_dashboard.index')

{{-- Page title --}}
@section('title')
    @lang('eventmie-pro::em.failed') @lang('eventmie-pro::em.bookings')
@endsection

@section('o_dashboard')

    <div class="container-fluid my-2">
        <div class="row">
            <div class="col-xl-12 col-md-12 col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header d-flex justify-content-between p-4 pb-1 bg-white border-bottom-0">
                        <h3 class="mb-0 fw-bold h2"> @lang('eventmie-pro::em.booking_info')</h3>
                    </div>
                    <div class="card-body">




                        <div class="col-md-4">
                            <input type="text" id="search" class=" form-control"
                                placeholder="@lang('eventmie-pro::em.search') @lang('eventmie-pro::em.by') @lang('eventmie-pro::em.phone') @lang('eventmie-pro::em.or') @lang('eventmie-pro::em.order_number')">
                            {{-- @if ($failed_bookings->isNotEmpty())
                                <a href="{{ route('check_all_bookings') }}" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-user"></i>
                                    <span class="hidden-xs hidden-sm">@lang('eventmie-pro::em.check_booking') @lang('eventmie-pro::em.all')</span>
                                </a>
                            @endif --}}
                            @if ($failed_bookings->isNotEmpty())
                                <a href="{{ route('failed-bookings.index') }}" class="btn btn-sm btn-dark mt-2">
                                    <i class="fas fa-undo"></i>
                                    <span class="hidden-xs hidden-sm">@lang('eventmie-pro::em.reset')</span>
                                </a>
                            @endif
                        </div>
                    </div>


                    {{-- booking details --}}
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table text-wrap table-hover" id="filter_data">
                                @include('failed.filter_failed_bookings')
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    </section>


@endsection


@section('javascript')
    <script type="text/javascript" src="{{ asset('js/manage_reviews.js') }}"></script>

    <script type="text/javascript">
        window.addEventListener('DOMContentLoaded', (event) => {
            document.getElementById("search").addEventListener("change", function(e) {
                search(e);
            });
        });

        var gateway = {!! json_encode(request()->gateway) !!};


        async function search(e) {

            let res = await axios.get(route('failed-bookings.index'), {
                params: {
                    search: e.target.value,
                    gateway: gateway,
                    page: 1
                }
            });

            document.getElementById("filter_data").innerHTML = '';
            document.getElementById("filter_data").innerHTML = res.data.view;

        }
    </script>
@stop
