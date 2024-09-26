<thead class="table-light text-nowrap">
    <tr>
        <th scope="col"> @lang('eventmie-pro::em.order_id')</th>

        <th scope="col">@lang('eventmie-pro::em.event')</th>

        <th scope="col">@lang('eventmie-pro::em.event') @lang('eventmie-pro::em.start_date')</th>

        <th scope="col">@lang('eventmie-pro::em.amount')</th>

        {{-- <th scope="col">@lang('eventmie-pro::em.booking')</th> --}}

        <th scope="col">@lang('eventmie-pro::em.customer')</th>

        {{-- <th scope="col">@lang('eventmie-pro::em.email')</th> --}}

        
        <th scope="col">@lang('eventmie-pro::em.payment_gateway')</th>

        <th scope="col">@lang('eventmie-pro::em.booking_date')</th>

        <th scope="col">@lang('eventmie-pro::em.update') @lang('eventmie-pro::em.date')</th>

        <th scope="col">@lang('eventmie-pro::em.actions')</th>

    </tr>
</thead>
<tbody>

    @foreach ($failed_bookings as $booking)
        @php
            $payment_method = json_decode($booking->payment_method, true);
            $bookings = json_decode($booking->booking, true);
        @endphp
        <tr>
            <td class="align-middle" scope="row">{{ $booking->orderId }}</td>

            <td class="align-middle">{{ $payment_method['event_title'] }}</td>

            <td class="align-middle">
                {{ userTimezone($bookings[0]['event_start_date'] . ' ' . $bookings[0]['event_start_time'], 'Y-m-d H:i:s', format_carbon_date(true)) }}
                {{ showTimezone() }}
            </td>

            <td class="align-middle">{{ collect($bookings)->sum('net_price') }}</td>
            {{-- <td class="align-middle">{{$booking->booking }}</td> --}}
            <td class="align-middle">{{ $payment_method['customer_name'] }}</td>

            {{-- <td class="align-middle">{{ $payment_method['customer_email'] }}</td> --}}


            <td class="align-middle">
                @if ($payment_method['payment_method'] == '7')
                    RazorPay
                @endif

                @if ($payment_method['payment_method'] == '8')
                    Paytm
                @endif

                @if ($payment_method['payment_method'] == '10')
                    PayU
                @endif

                @if ($payment_method['payment_method'] == '11')
                    CCAvenue
                @endif

                @if ($payment_method['payment_method'] == '12')
                    InstaMozo
                @endif

                @if($payment_method['payment_method'] == '5' || $payment_method['payment_method'] == '2')
                    Stripe
                @endif
            </td>


            <td class="align-middle">{{ userTimezone($booking['created_at'], 'Y-m-d H:i:s', format_carbon_date(true)) }}
                {{ showTimezone() }}
            </td>

            <td class="align-middle">{{ userTimezone($booking['updated_at'], 'Y-m-d H:i:s', format_carbon_date(true)) }}
                {{ showTimezone() }}
            </td>
            
            
            @if(empty($booking->success) && empty($booking->payment_failed))
                <td class="align-middle">
                    <a href="{{ route('failed-bookings.show', [$booking->orderId]) }}"
                        class="btn btn-sm btn-dark mt-2">
                        <i class="fas fa-user"></i>
                        <span class="hidden-xs hidden-sm">@lang('eventmie-pro::em.check_booking')</span>
                    </a>
                </td>
            @elseif(!empty($booking->payment_failed))
                <td class="align-middle">
                    <a href="javascript:void(0)"
                        class="btn btn-sm btn-danger mt-2 ">
                        <i class="far fa-times-circle"></i>
                        <span class="hidden-xs hidden-sm ">@lang('eventmie-pro::em.failed')</span>
                    </a>
                    <a href="{{ route('make_booking', [$booking->orderId]) }}"
                        class="btn btn-sm btn-warning mt-2 confirmation">
                        <i class="fas fa-user"></i>
                        <span class="hidden-xs hidden-sm">@lang('eventmie-pro::em.force_booking')</span>
                    </a>
                </td>
            @else
                <td class="align-middle">
                    <a href="javascript:void(0)"
                        class="btn btn-sm btn-success mt-2 ">
                        <i class="fas fa-check-circle"></i>
                        <span class="hidden-xs hidden-sm ">@lang('eventmie-pro::em.success')</span>
                    </a>
                    
                </td>
            @endif
        

        </tr>
    @endforeach
    <tr class="colspan-10">
        <td class="align-middle text-center" colspan=10>
            {{ $failed_bookings->links() }}
        </td>

    </tr>
</tbody>

@section('javascript')
<script type="text/javascript">
    var elems = document.getElementsByClassName('confirmation');
    var confirmIt = function (e) {
        if (!confirm('Are you sure? This will create a Booking no matter if you have received the payment or not.')) e.preventDefault();
    };
    for (var i = 0, l = elems.length; i < l; i++) {
        elems[i].addEventListener('click', confirmIt, false);
    }
</script>
@endsection