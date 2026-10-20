<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $headline }} | Inventory Management System</title>
</head>

<body style="margin: 0; padding: 0; background-color: #eef2f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" style="background-color: #eef2f0;">
        <tr>
            <td align="center" style="padding: 28px 16px;">

                <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"
                    style="max-width: 640px; background-color: #ffffff; border-radius: 14px; overflow: hidden;">

                    <!-- HEADER -->
                    <tr>
                        <td align="center" bgcolor="#005740" style="background-color: #005740; padding: 26px 32px;">
                            <img src="https://ims.upcebu.edu.ph/images/UPC-LOGO.png"
                                alt="University of the Philippines Cebu" width="120"
                                style="display: block; width: 120px; max-width: 120px; height: auto; margin: 0 auto 10px auto;">

                            <div style="color: #ffffff; font-size: 13px; font-weight: 600; letter-spacing: 0.3px;">
                                University of the Philippines Cebu
                            </div>

                            <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; line-height: 1.25; margin: 12px 0 4px 0;">
                                {{ $headline }}
                            </h1>

                            <div style="color: #cfe6de; font-size: 12px;">
                                Inventory Management System · Borrowings
                            </div>
                        </td>
                    </tr>

                    <!-- BODY -->
                    <tr>
                        <td style="padding: 30px 32px 8px 32px; color: #1f2d27; font-size: 15px; line-height: 1.6;">
                            <p style="margin: 0 0 12px 0;">
                                Dear
                                <strong>{{ $user->userProfiles?->full_name ?? $user->email }}</strong>,
                            </p>

                            <p style="margin: 0 0 22px 0; color: #44534d;">
                                {{ $intro }}
                            </p>

                            <!-- SUMMARY -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"
                                style="border: 1px solid #dfe8e4; border-radius: 10px; border-collapse: separate; margin-bottom: 22px;">
                                <tr>
                                    <td style="padding: 14px 18px; width: 50%; border-bottom: 1px solid #eef2f0;">
                                        <div style="color: #7a8a83; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">
                                            Reference
                                        </div>
                                        <div style="color: #0E6021; font-size: 15px; font-weight: 700; margin-top: 3px;">
                                            {{ $borrowing->reference_code }}
                                        </div>
                                    </td>
                                    <td style="padding: 14px 18px; width: 50%; border-bottom: 1px solid #eef2f0; border-left: 1px solid #eef2f0;">
                                        <div style="color: #7a8a83; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">
                                            Borrowed on
                                        </div>
                                        <div style="color: #1f2d27; font-size: 14px; font-weight: 600; margin-top: 3px;">
                                            {{ $borrowing->borrowed_at?->format('M j, Y') ?? '—' }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 18px; width: 50%; border-bottom: 1px solid #eef2f0;">
                                        <div style="color: #7a8a83; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">
                                            Borrower
                                        </div>
                                        <div style="color: #1f2d27; font-size: 14px; font-weight: 600; margin-top: 3px;">
                                            {{ $borrowing->borrower_name ?: ($borrowing->borrower?->userProfiles?->full_name ?? '—') }}
                                        </div>
                                    </td>
                                    <td style="padding: 14px 18px; width: 50%; border-bottom: 1px solid #eef2f0; border-left: 1px solid #eef2f0;">
                                        <div style="color: #7a8a83; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">
                                            Borrowed from
                                        </div>
                                        <div style="color: #1f2d27; font-size: 14px; font-weight: 600; margin-top: 3px;">
                                            {{ $borrowing->borrowed_from_name ?: ($borrowing->releasedBy?->userProfiles?->full_name ?? '—') }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding: 14px 18px;">
                                        <div style="color: #7a8a83; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">
                                            Due back
                                        </div>
                                        <div style="color: #1f2d27; font-size: 14px; font-weight: 600; margin-top: 3px;">
                                            {{ $borrowing->due_at?->format('M j, Y') ?? 'No due date — return when finished' }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- ITEMS -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%"
                                style="border-collapse: collapse; margin-bottom: 24px;">
                                <thead>
                                    <tr>
                                        <th align="left" style="padding: 8px 10px; background: #f4f8f6; border-bottom: 1px solid #dfe8e4; color: #5d6d66; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px;">
                                            Item
                                        </th>
                                        <th align="left" style="padding: 8px 10px; background: #f4f8f6; border-bottom: 1px solid #dfe8e4; color: #5d6d66; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px;">
                                            Property number
                                        </th>
                                        <th align="center" style="padding: 8px 10px; background: #f4f8f6; border-bottom: 1px solid #dfe8e4; color: #5d6d66; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px;">
                                            Qty
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $line)
                                        <tr>
                                            <td style="padding: 9px 10px; border-bottom: 1px solid #eef2f0; font-size: 13px; color: #1f2d27;">
                                                {{ $line->inventoryItem?->item_name ?? 'Item' }}
                                            </td>
                                            <td style="padding: 9px 10px; border-bottom: 1px solid #eef2f0; font-size: 12px; color: #5d6d66;">
                                                {{ $line->inventoryItem?->property_number ?? '—' }}
                                            </td>
                                            <td align="center" style="padding: 9px 10px; border-bottom: 1px solid #eef2f0; font-size: 13px; color: #1f2d27;">
                                                {{ $line->quantity }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 26px 0;">
                                <tr>
                                    <td bgcolor="#0E6021" style="background-color: #0E6021; border-radius: 8px;">
                                        <a href="{{ route('borrowings.index') }}"
                                            style="display: inline-block; padding: 12px 22px; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none;">
                                            Open Borrowings
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 24px 0; color: #5d6d66; font-size: 13px;">
                                This is an automated message from the UP Cebu Inventory Management
                                System. If something here looks wrong, reply to the Supply &amp;
                                Property Management Office.
                            </p>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td align="center" style="padding: 18px 32px 26px 32px; border-top: 1px solid #eef2f0;">
                            <p style="margin: 0; color: #97a5a0; font-size: 11px;">
                                University of the Philippines Cebu · Supply &amp; Property Management Office
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>

</html>

