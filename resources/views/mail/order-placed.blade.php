<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $forAdmin ? 'Ny order' : 'Orderbekräftelse' }} – {{ $order->order_number }}</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;line-height:1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
                    {{-- Header --}}
                    <tr>
                        <td style="padding:24px 32px;background:#4f46e5;color:#ffffff;">
                            <div style="font-size:13px;text-transform:uppercase;letter-spacing:0.08em;opacity:0.85;">{{ $shopName }}</div>
                            <h1 style="margin:4px 0 0;font-size:22px;font-weight:700;">
                                @if ($forAdmin)
                                    Ny order: {{ $order->order_number }}
                                @else
                                    Tack för din beställning!
                                @endif
                            </h1>
                        </td>
                    </tr>

                    {{-- Intro --}}
                    <tr>
                        <td style="padding:24px 32px 8px;">
                            @if ($forAdmin)
                                <p style="margin:0 0 6px;">En ny order har just lagts i {{ $shopName }}.</p>
                                <p style="margin:0;color:#64748b;font-size:14px;">Kund: <strong>{{ $order->email }}</strong></p>
                            @else
                                <p style="margin:0 0 6px;">Hej {{ $order->shipping_address['name'] ?? '' }},</p>
                                <p style="margin:0;color:#475569;">Vi har tagit emot din beställning hos {{ $shopName }}. Här är en sammanställning.</p>
                            @endif
                        </td>
                    </tr>

                    {{-- Order meta --}}
                    <tr>
                        <td style="padding:16px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:8px;">
                                <tr>
                                    <td style="padding:14px 18px;">
                                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Ordernummer</div>
                                        <div style="font-size:16px;font-weight:600;font-variant-numeric:tabular-nums;">{{ $order->order_number }}</div>
                                    </td>
                                    <td style="padding:14px 18px;text-align:right;">
                                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Datum</div>
                                        <div style="font-size:16px;">{{ $order->placed_at?->format('Y-m-d H:i') ?? $order->created_at->format('Y-m-d H:i') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Items --}}
                    <tr>
                        <td style="padding:8px 32px;">
                            <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;margin-bottom:8px;">Beställning</div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                @foreach ($order->items as $item)
                                    <tr style="border-bottom:1px solid #e2e8f0;">
                                        <td style="padding:10px 0;">
                                            <div style="font-weight:500;">{{ $item->name_snapshot }}</div>
                                            @if ($item->sku_snapshot)
                                                <div style="font-size:12px;color:#94a3b8;">{{ $item->sku_snapshot }}</div>
                                            @endif
                                        </td>
                                        <td style="padding:10px 0;text-align:right;color:#64748b;font-variant-numeric:tabular-nums;width:80px;">
                                            {{ $item->qty }} × {{ App\Support\Money::format($item->unit_price_incl_vat, $currency) }}
                                        </td>
                                        <td style="padding:10px 0;text-align:right;font-weight:600;font-variant-numeric:tabular-nums;width:110px;">
                                            {{ App\Support\Money::format($item->line_total_incl_vat, $currency) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- Totals --}}
                    <tr>
                        <td style="padding:16px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr><td style="padding:4px 0;color:#64748b;">Delsumma (exkl. moms)</td><td style="padding:4px 0;text-align:right;font-variant-numeric:tabular-nums;">{{ App\Support\Money::format($order->subtotal_excl_vat, $currency) }}</td></tr>
                                <tr><td style="padding:4px 0;color:#64748b;">Moms</td><td style="padding:4px 0;text-align:right;font-variant-numeric:tabular-nums;">{{ App\Support\Money::format($order->vat_total, $currency) }}</td></tr>
                                @if ((float) $order->shipping_total > 0)
                                    <tr><td style="padding:4px 0;color:#64748b;">Frakt{{ $shipping ? ' (' . $shipping->label() . ')' : '' }}</td><td style="padding:4px 0;text-align:right;font-variant-numeric:tabular-nums;">{{ App\Support\Money::format($order->shipping_total, $currency) }}</td></tr>
                                @endif
                                <tr><td colspan="2" style="padding:6px 0;"><hr style="border:0;border-top:1px solid #e2e8f0;margin:0;"></td></tr>
                                <tr>
                                    <td style="padding:8px 0;font-weight:700;font-size:17px;">Att betala</td>
                                    <td style="padding:8px 0;text-align:right;font-weight:700;font-size:17px;color:#15803d;font-variant-numeric:tabular-nums;">{{ App\Support\Money::format($order->grand_total, $currency) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Payment --}}
                    @if ($payment)
                        <tr>
                            <td style="padding:8px 32px 16px;">
                                <div style="background:#eef2ff;border-radius:8px;padding:14px 16px;font-size:14px;color:#3730a3;">
                                    <div style="font-weight:600;margin-bottom:2px;">{{ $payment->label() }}</div>
                                    <div style="color:#4338ca;">{{ $payment->description() }}</div>
                                </div>
                            </td>
                        </tr>
                    @endif

                    {{-- Shipping address --}}
                    @if ($order->shipping_address)
                        <tr>
                            <td style="padding:8px 32px 24px;">
                                <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;margin-bottom:6px;">Leveransadress</div>
                                <div style="font-size:14px;color:#334155;line-height:1.55;">
                                    {{ $order->shipping_address['name'] ?? '' }}<br>
                                    {{ $order->shipping_address['street'] ?? '' }}<br>
                                    {{ $order->shipping_address['zip'] ?? '' }} {{ $order->shipping_address['city'] ?? '' }}<br>
                                    {{ $order->shipping_address['country'] ?? '' }}
                                </div>
                            </td>
                        </tr>
                    @endif

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:18px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;color:#94a3b8;font-size:12px;">
                            {{ $shopName }} · skickat automatiskt av Open E-commerce
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
