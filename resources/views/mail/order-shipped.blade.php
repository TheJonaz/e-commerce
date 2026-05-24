<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Din beställning är skickad</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;line-height:1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="padding:24px 32px;background:#15803d;color:#ffffff;">
                            <div style="font-size:13px;text-transform:uppercase;letter-spacing:0.08em;opacity:0.85;">{{ $shopName }}</div>
                            <h1 style="margin:4px 0 0;font-size:22px;font-weight:700;">📦 Din beställning är skickad</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px;">
                            <p style="margin:0 0 14px;">Hej {{ $order->shipping_address['name'] ?? '' }},</p>
                            <p style="margin:0;">Vi har skickat din beställning <strong>{{ $order->order_number }}</strong> med {{ $order->shipping_method }}.</p>
                        </td>
                    </tr>

                    @if ($order->tracking_number || $order->tracking_url)
                        <tr>
                            <td style="padding:0 32px 16px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:8px;">
                                    <tr>
                                        <td style="padding:14px 18px;">
                                            <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Spårning</div>
                                            @if ($order->tracking_number)
                                                <div style="font-size:16px;font-weight:600;font-family:ui-monospace,monospace;margin-top:4px;">{{ $order->tracking_number }}</div>
                                            @endif
                                            @if ($order->tracking_url)
                                                <a href="{{ $order->tracking_url }}" style="display:inline-block;margin-top:8px;padding:0.55rem 1rem;background:#4f46e5;color:#fff;border-radius:6px;text-decoration:none;font-size:14px;font-weight:500;">Spåra paket →</a>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if ($order->shipping_address)
                        <tr>
                            <td style="padding:8px 32px 24px;">
                                <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;margin-bottom:6px;">Levereras till</div>
                                <div style="font-size:14px;color:#334155;line-height:1.55;">
                                    {{ $order->shipping_address['name'] ?? '' }}<br>
                                    {{ $order->shipping_address['street'] ?? '' }}<br>
                                    {{ $order->shipping_address['zip'] ?? '' }} {{ $order->shipping_address['city'] ?? '' }}<br>
                                    {{ $order->shipping_address['country'] ?? '' }}
                                </div>
                            </td>
                        </tr>
                    @endif

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
