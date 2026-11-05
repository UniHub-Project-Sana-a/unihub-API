<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ar" dir="rtl">
<head>
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">

    <style>
        /* RESPONSIVE */
        @media only screen and (max-width: 600px) {
            .inner-body { width: 100% !important; }
            .footer { width: 100% !important; }
        }
        @media only screen and (max-width: 500px) {
            .button { width: 100% !important; }
        }

        /* إجبار الاتجاه والنص للعناصر الشائعة */
        body, table, td, p, a, li, blockquote {
            direction: rtl !important;
            text-align: right !important;
            font-family: "Tahoma", "Arial", sans-serif !important;
        }

        /* عناصر القوالب الداخلية */
        .wrapper { width:100% !important; direction: rtl !important; text-align: right !important; }
        .content { direction: rtl !important; text-align: right !important; }
        .inner-body { direction: rtl !important; text-align: right !important; margin: 0 auto; }
        .content-cell { direction: rtl !important; text-align: right !important; padding: 35px; }
        .header { direction: rtl !important; text-align: center !important; } /* header غالبًا تريد في الوسط */
        .footer { direction: rtl !important; text-align: center !important; padding: 20px; color: #6b7280; font-size: 12px; }

        /* زر */
        .button td { direction:  !important; }
        .button a { direction: rtl !important; text-align: center !important; }

        /* تقليل احتمالية أنماط العميل */
        [dir="ltr"] .content-cell, [dir="ltr"] .inner-body { direction: rtl !important; text-align: right !important; }

    </style>

    {!! $head ?? '' !!}
</head>
<body style="margin:0; padding:0; width:100%; direction:rtl; text-align:right;" dir="rtl">
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="direction:rtl; text-align:right;">
        <tr>
            <td align="center">
                <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="direction:rtl; text-align:right;">
                    {!! $header ?? '' !!}

                    <!-- Email Body -->
                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
                            <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="direction:rtl; text-align:right; margin:0 auto;">
                                <!-- Body content -->
                                <tr>
                                    <td class="content-cell" style="direction:rtl; text-align:right; padding: 35px;">
                                        {{-- إضافة غلاف إضافي لضمان الإرث --}}
                                        <div dir="rtl" style="direction:rtl; text-align:right;">
                                            {!! Illuminate\Mail\Markdown::parse($slot) !!}
                                        </div>

                                        {!! $subcopy ?? '' !!}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {!! $footer ?? '' !!}
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
