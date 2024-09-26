<html>

<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <style>
        * {
            padding: 0;
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
        }

        body {
            height: 100% !important;
            width: 100% !important;
            font-size: 14px;
            font-family: 'DejaVu Sans', sans-serif;
        }

        #bottom_table {
            max-width: auto;
        }

        #bottom_table tr {
            text-align: center;
        }

        #bottom_table td {
            padding: 0;
        }

        table {
            width: 100%;
            padding: 1px;
            margin: 0 auto !important;
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
        }

        table table table {
            table-layout: auto;
        }

        table td {
            padding: 5px;
            font-size: 14px;
            word-wrap: break-word;
        }

        .center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        [dir=rtl] .text-right {
            text-align: left;
        }

        [dir=rtl] .text-left {
            text-align: right;
        }

        p {
            font-size: 18px;
            display: block;
        }
		.text-bottom{
			font-size: 16px;
		}
        .title-bar {
            padding: 0 !important;
        }

        .title-bar .s-heading {
            color: #797979;
            font-size: 14px;
            margin: 0;
        }

        .title-bar .m-heading {
            color: #3c3c3c;
            font-size: 20px;
            margin: 0;
            font-weight: 600;
        }

        .label {
            color: #797979;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            margin:0;
			margin-top:5px;
        }

        .value-data {
            color: #000;
            font-size: 20px;
        }

        .question-value-data {
            color: #000;
            font-size: 16px;
        }

        .divider {
            color: #797979;
            border: 1px dashed #797979;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .text-capitalize {
            text-transform: capitalize;
        }

        .text-large {
            font-size: 20px;
            font-weight: 600;
			padding:0;margin:0;
        }

        .text-small {
            font-size: 16px;
			padding:0;margin:0;
        }

        .row-divide {
            padding-bottom: 15px;
        }

        .mt-0 {
            margin-top: 0px !important;
        }
    </style>
</head>

<body {!! is_rtl() ? 'dir="rtl"' : '' !!}>
    <!-- when testing  -->
    @if (!$img_path)
        <div style="max-width: 600px;margin: 0 auto;">
        @else
            <!-- when generating  -->
            <div>
    @endif
    <!-- 1. Branding -->
<div style="margin:30px;border:2px solid #f4f4f4;">
    <div>
        <table>
            <tr>
                <td class="title-bar center">
                    <table>
                        <tr>
                            <td style="padding-top:5px;padding-bottom:10px;text-align:left;" >
                                @php
                                    $logoPath = public_path('/img/ihs-logo2.jpg');
                                @endphp
                                @if (file_exists($logoPath))
                                    <img src="{{ 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) }}"
                                        style="width:64px;float:left;margin-right:20px;">
                                @else
                                    
                                @endif
							
                                <p class="m-heading" style="padding-top:10px;">
                                    {{ setting('site.site_name') ? setting('site.site_name') : config('app.name') }}
                                <br/>
                                <span class="s-heading" style="font-weight:400;"><span style="font-size:16px;font-weight:600;">18 Oct 2024</span> 11:00 AM - 20 <span style="font-size:16px;font-weight:600;">Oct 2024</span> 05:00 PM</span>
								</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- 2. QrCode -->
	<!--
    <div>
        <table>
            <tr>
                <td style="text-align: center;padding-top: 5px;">
                    @php
                        $qrcode =
                            $booking['customer_id'] . '/' . $booking['id'] . '-' . $booking['order_number'] . '.png';
                        $qrcodePath = public_path('/storage/qrcodes/' . $qrcode);
                    @endphp
                    @if (file_exists($qrcodePath))
                        <img src="{{ 'data:image/png;base64,' . base64_encode(file_get_contents($qrcodePath)) }}"
                            style="width: 50%;">
                    @else
                        <p>QR Code not found</p>
                    @endif
                </td>
            </tr>
        </table>
    </div>
	-->
    

    <!-- 1. Event details -->
    <div style="background:#f4f4f4;">
        <table>
            <tr>
                <td style="padding: 15px 20px 15px 20px;">
                    <div>
                        <table>
                            <tr>
                                <td class="row-divide">
								<table>
								<tr>
									<td style="width:30%;vertical-align: top;">
                                    <p class="label">@lang('eventmie-pro::em.customer')</p>
									</td>
									<td style="width:70%;vertical-align: top;">
                                    <p class="value-data text-capitalize text-large">
                                        {{ ucfirst($booking['customer_name']) }}</p>
                                    <p class="value-data">#{{ $booking['order_number'] }}</p>
									</td>
								</tr>
								

                                    @if (collect($booking['attendees'])->isNotEmpty())
									<tr>
										<td style="vertical-align: top;">
											<p class="label">@lang('eventmie-pro::em.attendee')</p>
										</td>
										<td style="vertical-align: top;">
											<p class="value-data text-capitalize text-small">
												{{ $booking['attendees'][0]['name'] }} <br>
												<!--{{ $booking['attendees'][0]['phone'] }} <br>-->
												{{ $booking['attendees'][0]['address'] }} <br>
											</p>
										</td>
									</tr>
                                    @endif
								<tr>
									<td style="vertical-align: top;">
										<p class="label">@lang('eventmie-pro::em.ticket')</p>
									</td>
									<td style="vertical-align: top;">
										<p class="value-data text-capitalize text-small">
                                        {{ $booking['ticket_title'] }} <strong> x {{ $booking['quantity'] }}</strong>
                                        @if (!empty($booking['attendees'][0]['seat']))
                                            ({{ __('eventmie-pro::em.seat') . ' ' . $booking['attendees'][0]['seat']['name'] }})
                                        @endif
										</p>
									</td>
								</tr>
								<tr>
									<td style="vertical-align: top;">
										<p class="label">DAY</p>
									</td>
									<td style="vertical-align: top;">
										<p class="value-data text-capitalize text-small">
                                        {{ userTimezone($booking['event_start_date'] . ' ' . $booking['event_start_time'], 'Y-m-d H:i:s', format_carbon_date(false)) }}
										-<br>
                                        {{ userTimezone($booking['event_end_date'] . ' ' . $booking['event_end_time'], 'Y-m-d H:i:s', format_carbon_date(false)) }}

                                       
										</p>
									</td>
								</tr>
								</table>
                             </td>
                             <td class="row-divide">
                                                                       
                                        <p class="label" style="margin-top: 0px;width:100%;text-align:center;display: inline-block; font-size: 10px;text-transform: capitalize;padding-bottom: 5px;">
                                            {{ $event->title }}</p><br>
											<div style="width:100%;text-align:center;">
												@php
													$qrcode =
														$booking['customer_id'] . '/' . $booking['id'] . '-' . $booking['order_number'] . '.png';
													$qrcodePath = public_path('/storage/qrcodes/' . $qrcode);
												@endphp
												@if (file_exists($qrcodePath))
													<img src="{{ 'data:image/png;base64,' . base64_encode(file_get_contents($qrcodePath)) }}"
														style="width: 50%;">
												@else
													<p>QR Code not found</p>
												@endif
											</div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>
	<div>
        <table>
            <tr>
                <td style="padding: 15px 20px 15px 20px;">
                    <div>
                        <table>
						
                            <tr>
                                <td style="width:80%;">
									<p class="label">On which day do you intend to visit the Show?</p>
								</td>
								<td style="width:20%;">
                                    <p class="question-value-data">{{ $booking['visit_show_day'] }}</p>
								</td>
							</tr>
						
							<tr>
								<td style="width:90%;">
                                    <p class="label">Would you like to receive similar offers to future PTSB Ideal Home Shows?</p>
								</td>
								<td style="width:10%;">
                                    <p class="question-value-data">{{ $booking['ptsb_notification'] ? 'Yes' : 'No' }}</p>
								</td>
							</tr>
							<tr>
								<td style="width:90%;">
                                    <p class="label">Would you like to receive information directly from exhibitors attending the event?</p>
								</td>
								<td style="width:10%;">
                                    <p class="question-value-data">{{ $booking['direct_connect_attendee'] ? 'Yes' : 'No' }}</p>
								</td>
							</tr>
						 </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>
    <!-- Divider -->
    <div>
        <table>
            <tr>
                <td>
                    <hr class="divider">
                </td>
            </tr>
        </table>
    </div>
<div>
	<table>
		<tr>
			<td style="padding: 15px 25px 15px 25px; font-size:15px;">
				<table>
					<tr>
						<td>
							<p class="text-bottom">
							<strong>Venue</strong><br/>
							Simmonscourt Road D04 E6N6, Dublin 4
							</p>
							<p style="padding-top:7px;" class="text-bottom">
							<strong>Travel and Parking</strong><br/>
							RDS Simmonscourt is situated in the Heart of Dublin in Ballsbridge, Dublin 4.<br/>
							The PTSB Ideal Home Show is in the Simmonscourt Pavilion off Simmonscourt Road, D04 E6N6.
							</p>
							<p style="padding-top:7px;" class="text-bottom">
							<strong>By Bus</strong><br/>
							The RDS is serviced by Bus Route 4, 7, 8, 18, 46A, 145.
							</p>
							<p style="padding-top:7px;" class="text-bottom">
							<strong>By Train</strong><br/>
							The Sandymount DART station is only a few minutes walk from the RDS Simmonscourt.
							</p>
							<p style="padding-top:7px;" class="text-bottom">
							<strong>Parking</strong><br/>
							Limited parking is available on-site at a fee of €10. More details on the RDS website.
							</p>
							<p style="padding-top:7px;" class="text-bottom">
							<strong>Show Times</strong><br/>
							Fri, 18 October 2024, 11am – 6pm<br/>
							Sat, 19 October 2024, 10am – 6pm<br/>
							Sun, 20 October 2024, 10am – 6pm
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</div>

</body>

</html>
