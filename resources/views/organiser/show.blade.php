@extends('eventmie::layouts.app')

{{-- Page title --}}
@section('title')
    @lang('eventmie-pro::em.organiser'): {{ $organiser_d->name }}
@endsection

@section('content')

    <div class="container-fluid">
        <div class="row align-items-center mb-5">
            <div class="col-lg-12 col-md-12 px-0">
                <div class="py-10 bg-gradient"></div>
                <div class="bg-white pt-2 pb-0 rounded-bottom shadow-sm row g-0">
                    <div class="offset-xl-3 col-xl-6 col-md-12 col-12 mb-4">
                        <div class="text-center position-relative mt-n10">
                            @if ($organiser_d->avatar)
                                <img src="/storage/{{ $organiser_d->avatar }}" alt="{{ $organiser_d->name }}"
                                    class="avatar-lg rounded-circle border-4 border border-white mb-3" />
                            @else
                                <img src="{{ asset('ep_img/512x512.webp') }}" alt="{{ $organiser_d->name }}"
                                    class="avatar-lg rounded-circle border-4 border border-white mb-3" />
                            @endif
                            <a href="#!" class="position-absolute  top-0 right-0 ms-n4 mt-3" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="" data-bs-original-title="Verified">
                                <img src="{{ asset('img/checked-mark.svg') }}" alt="" height="30" width="30">
                            </a>

                            <h2 class="mb-1">
                                {{ $organiser_d->name }}
                            </h2>
                            <p class="mb-3 px-4 ">
                                {!! nl2br($organiser_d->org_description) !!}
                            </p>

                            <ul class="list-inline lgx-social">
                                @if ($organiser_d->org_facebook)
                                    <li class="list-inline-item">
                                        <a href="{{ Str::contains($organiser_d->org_facebook, ['http://', 'https://']) == false ? '//' : '' }}{{ $organiser_d->org_facebook }}"
                                            target="_blank" class="badge text-dark">
                                            <i class="fab fa-facebook-f text-primary fa-2x" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                @endif

                                @if ($organiser_d->org_instagram)
                                    <li class="list-inline-item">
                                        <a href="{{ Str::contains($organiser_d->org_instagram, ['http://', 'https://']) == false ? '//' : '' }}{{ $organiser_d->org_instagram }}"
                                            class="badge text-dark" target="_blank" rel="nofollow">
                                            <i class="fab fa-instagram text-danger fa-2x" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                @endif

                                @if ($organiser_d->org_youtube)
                                    <li class="list-inline-item">
                                        <a href="{{ Str::contains($organiser_d->org_youtube, ['http://', 'https://']) == false ? '//' : '' }}{{ $organiser_d->org_youtube }}"
                                            class="badge text-dark" target="_blank" rel="nofollow">
                                            <i class="fab fa-youtube text-danger fa-2x" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                @endif

                                @if ($organiser_d->org_twitter)
                                    <li class="list-inline-item">
                                        <a href="{{ Str::contains($organiser_d->org_twitter, ['http://', 'https://']) == false ? '//' : '' }}{{ $organiser_d->org_twitter }}"
                                            class="badge text-primary" target="_blank" rel="nofollow">
                                            <i class="fa-brands fa-twitter fa-2x" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                @endif

                                @if ($organiser_d->org_website)
                                    <li class="list-inline-item">
                                        <a href="{{ Str::contains($organiser_d->org_website, ['http://', 'https://']) == false ? '//' : '' }}{{ $organiser_d->org_website }}"
                                            class="badge text-primary" target="_blank" rel="nofollow">
                                            <i class="fas fa-globe text-primary" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                @endif
                            </ul>

                        </div>
                    </div>
                    {{-- <ul class="nav nav-lt-tab mt-2" id="pills-tab" role="tablist">
                        <li class="nav-item ml-0">
                            <a class="nav-link active" href="/">Listings </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/">Reviews</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/">Favourite</a>
                        </li>
                    </ul> --}}
                </div>

            </div>
        </div>
    </div>

    <div class="container">
        <div class="row align-items-center">
            <div class="col-xl-12 col-lg-12 col-md-12 col-12">
                <organiser-event :active_events="{{ json_encode($activeEvents, JSON_HEX_APOS) }}"
                    :expired_events="{{ json_encode($expiredEvents, JSON_HEX_APOS) }}"
                    :currency="{{ json_encode($currency, JSON_HEX_APOS) }}"
                    :date_format="{{ json_encode(
                        [
                            'vue_date_format' => format_js_date(),
                            'vue_time_format' => format_js_time(),
                        ],
                        JSON_HEX_APOS,
                    ) }}">
                </organiser-event>
            </div>
        </div>
    </div>


@endsection

@section('javascript')
    <script type="text/javascript">
        var events_slider = false;
    </script>
    <script type="text/javascript" src="{{ mix('js/organiser.js') }}"></script>
@stop
