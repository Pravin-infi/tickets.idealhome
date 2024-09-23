<div class="py-5">
    {{-- booking details --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <h3>@lang('eventmie-pro::em.promocode_info')</h3>

                <table class="table table-striped table-hover">
                    <tr>
                        <th>{{ __('eventmie-pro::em.promocode') }}</th>
                        <td>{{ !empty($booking['promocode']) ? $booking['promocode'] : '' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('eventmie-pro::em.reward') }}</th>
                        <td>{{ (!empty($booking['promocode_reward']) ? $booking['promocode_reward'] : 0) . ' ' . $booking['currency'] }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
