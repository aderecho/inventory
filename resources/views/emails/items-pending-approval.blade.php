<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Pending Item Acknowledgement | Inventory Management System</title>

    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->

    <style>
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        @media screen and (max-width: 600px) {
            .hero-padding {
                padding: 20px 22px 24px 22px !important;
            }

            .content-padding {
                padding: 28px 22px 32px 22px !important;
            }

            .footer-padding {
                padding: 22px !important;
            }

            .stack {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box;
            }

            .item-label {
                border-right: 0 !important;
                border-bottom: 1px solid #cfe3dd !important;
            }

            .mobile-button {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box;
                text-align: center !important;
            }
        }
    </style>
</head>

<body
    style="margin: 0; padding: 0; background-color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">

    <!-- The card fills the entire email -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"
        style="width: 100%; background-color: #ffffff;">

        <!-- Hero: background image lives inside the card -->
        <tr>
            <td align="center" bgcolor="#005740" style="background-color: #005740;">

                <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"
                    style="max-width: 640px;">
                    <tr>
                        <td class="hero-padding" align="center" style="padding: 22px 40px 26px 40px;">

                            <img src="https://ims.upcebu.edu.ph/images/UPC-LOGO.png"
                                alt="University of Cebu Philippines" width="150"
                                style="display: block; width: 150px; max-width: 150px; height: auto; margin: 0 auto 8px auto;">

                            <div
                                style="color: #ffffff; font-size: 14px; font-weight: 600; letter-spacing: 0.3px; line-height: 1.3; opacity: 0.92;">
                                University of Cebu Philippines
                            </div>

                            <h1
                                style="color: #ffffff; font-size: 22px; font-weight: 800; line-height: 1.2; margin: 12px 0 4px 0; letter-spacing: -0.2px;">
                                Items waiting for your acknowledgement
                            </h1>

                            <div style="color: #d4e7e1; font-size: 13px; line-height: 1.4;">
                                Inventory Management System
                            </div>

                        </td>
                    </tr>
                </table>

            </td>
        </tr>

        <!-- Body -->
        <tr>
            <td align="center" background="https://ims.upcebu.edu.ph/images/background-up.jpg" bgcolor="#005740"
                style="background-color: #005740; background-image: linear-gradient(to right, rgba(3, 125, 92, 0.8), rgba(18, 61, 49, 0.7), rgba(31, 103, 84, 0.2)), url('https://ims.upcebu.edu.ph/images/background-up.jpg'); background-size: cover; background-position: center;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"
                    style="max-width: 640px;">
                    <tr>
                        <td class="content-padding"
                            style="padding: 40px 40px 36px 40px; color: #ffffff; font-size: 15px; line-height: 1.65;">

                            <p style="margin: 0 0 14px 0; font-size: 17px; color: #ffffff;">
                                Dear <strong>{{ $user->userProfiles?->full_name ?? $user->name }}</strong>,
                            </p>

                            <p style="margin: 0 0 28px 0; color: #f7fffd;">
                                This is an automated notice about institutional property under your custody.
                                You have <strong style="color: #ffffff;">{{ $totalItems }} pending item(s)</strong>
                                that need your official acknowledgement.
                            </p>

                            <!-- Summary strip -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"
                                style="margin-bottom: 36px; background-color: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.28); border-radius: 10px; border-collapse: separate;">
                                <tr>
                                    <td class="stack" style="padding: 18px 22px; width: 50%;">
                                        <div
                                            style="color: #a9d1c5; font-size: 12px; font-weight: 600; letter-spacing: 0.3px;">
                                            Document type
                                        </div>
                                        <div
                                            style="color: #ffffff; font-size: 16px; font-weight: 700; margin-top: 3px;">
                                            {{ $documentType }}
                                        </div>
                                    </td>
                                    <td class="stack"
                                        style="padding: 18px 22px; width: 50%; border-left: 1px solid rgba(255, 255, 255, 0.22);">
                                        <div
                                            style="color: #a9d1c5; font-size: 12px; font-weight: 600; letter-spacing: 0.3px;">
                                            Category
                                        </div>
                                        <div
                                            style="color: #ffffff; font-size: 16px; font-weight: 700; margin-top: 3px;">
                                            {{ $category }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Items heading -->
                            <div style="font-size: 18px; font-weight: 800; color: #ffffff; margin-bottom: 4px;">
                                Items to acknowledge
                            </div>
                            <div style="font-size: 13px; color: #c5ded6; margin-bottom: 16px;">
                                Match each property number with the item in your custody.
                            </div>

                            <!-- Item rows -->
                            @foreach ($items as $item)

                                <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"
                                    style="margin-bottom: 10px; background-color: #f2f8f6; border: 1px solid #cfe3dd; border-radius: 10px; border-collapse: separate; overflow: hidden;">
                                    <tr>
                                        <td class="stack item-label" valign="middle"
                                            style="width: 38%; padding: 14px 18px; background-color: #e3f0ec; border-right: 1px solid #cfe3dd;">
                                            <div
                                                style="color: #4d8374; font-size: 11px; font-weight: 600; line-height: 1.3;">
                                                Property number
                                            </div>
                                            <div
                                                style="font-family: 'Courier New', Courier, monospace; color: #005740; font-size: 15px; font-weight: 700; margin-top: 2px; word-break: break-all;">
                                                {{ $item['property_number'] }}
                                            </div>
                                        </td>
                                        <td class="stack" valign="middle"
                                            style="padding: 14px 18px; color: #0f172a; font-size: 15px; font-weight: 600; line-height: 1.4;">
                                            {{ $item['item_name'] }}
                                        </td>
                                    </tr>
                                </table>

                            @endforeach

                            <!-- More items notice -->
                            @if ($totalItems > 10)

                                <p style="margin: 14px 0 30px 0; color: #e3f0ec; font-size: 13px; text-align: center;">
                                    Showing the first 10 items. {{ $totalItems - 10 }} more item(s) are waiting in your
                                    dashboard.
                                </p>

                            @else

                                <div style="margin-bottom: 30px;"></div>

                            @endif

                            <p style="margin: 0 0 26px 0; color: #e3f0ec; font-size: 14px;">
                                Open the system portal to review the equipment details and record your digital
                                acknowledgement.
                            </p>

                            <!-- Call to action -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
                                <tr>
                                    <td align="center">
                                        <!--[if mso]>
                                            <v:roundrect
                                                xmlns:v="urn:schemas-microsoft-com:vml"
                                                xmlns:w="urn:schemas-microsoft-com:office:word"
                                                href="https://ims.upcebu.edu.ph/user/dashboard"
                                                style="height:50px;v-text-anchor:middle;width:240px;"
                                                arcsize="50%"
                                                stroke="f"
                                                fillcolor="#ffffff">

                                                <w:anchorlock/>

                                                <center style="color:#005740;font-family:sans-serif;font-size:15px;font-weight:bold;">
                                                    Review and acknowledge
                                                </center>

                                            </v:roundrect>
                                            <![endif]-->
                                        <!--[if !mso]><!-->

                                        <a href="https://ims.upcebu.edu.ph/user/dashboard" class="mobile-button"
                                            style="background-color: #ffffff; color: #005740; display: inline-block; font-size: 15px; font-weight: 700; line-height: 50px; text-align: center; text-decoration: none; padding: 0 40px; border-radius: 999px; -webkit-text-size-adjust: none;">

                                            Review and acknowledge

                                        </a>

                                        <!--<![endif]-->

                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td align="center" bgcolor="#005740" style="background-color: #005740;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"
                    style="max-width: 640px;">
                    <tr>
                        <td class="footer-padding" align="center"
                            style="padding: 26px 40px; color: #c5ded6; font-size: 12px; line-height: 1.6;">

                            <p style="margin: 0 0 4px 0; font-weight: 700; color: #ffffff; font-size: 13px;">
                                Inventory Management System
                            </p>

                            <p style="margin: 0 0 8px 0;">
                                Official school administrative notification
                            </p>

                            <p style="margin: 0; color: #9cc4b8; font-size: 11px;">
                                This is an automated email. Please do not reply to this message.
                            </p>

                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>

</body>

</html>