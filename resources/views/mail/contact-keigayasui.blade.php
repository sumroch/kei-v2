<!DOCTYPE html>
<html>

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>

    <body style='font-style: normal;
			font-variant-caps: normal;
			font-weight: normal;
			font-stretch: normal;
			font-size: normal;
			line-height: 1.5;
			font-family: "Hiragino Maru Gothic W4 JIS2004", "游ゴシック", YuGothic, "ヒラギノ角ゴ ProN W3", "Hiragino Kaku Gothic ProN", "メイリオ", Meiryo, "Helvetica Neue", Helvetica, Arial, sans-serif;
			font-size-adjust: none;
			font-kerning: auto;
			font-optical-sizing: auto;
			font-variant-alternates: normal;
			font-variant-east-asian: normal;
			font-variant-ligatures: normal;
			font-variant-numeric: normal;
			font-variant-position: normal;
			font-language-override: normal;
			font-feature-settings: normal;
			font-variation-settings: normal;'>
        <div style="max-width: 600px; margin: 0px auto; padding-top: 10px;">
            <table width="100%" cellspacing="0" border="0">
                <tbody>
                    <tr>
                        <td style="padding: 0px 0px 10px 0px; border-top: 2px solid black;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0px 0px">
                            {{ $data->name ?? '-' }} 様 <br>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0px 0px">
                            この度は {{ $data->store->name }} にお問い合わせ頂きありがとうございます。
                        </td>
                    </tr>

                    @if (date('Y-m-d') < date('Y-m-d', strtotime('2026-08-16')))
                        <tr>
                            <td style="padding: 0px 0px">
                                2026年08月09日～2026年08月15日の期間中にお問い合わせいただきました内容については、
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 0px 0px">
                                08月16日にこちらの電話番号より、確認のご連絡を差し上げます。予めご了承ください。
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 0px 0px">
                                お手数をお掛けいたしますが、よろしくお願いいたします。
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td style="padding: 0px 0px">
                                お問い合わせいただきました内容について営業時間内に30分以内に確認のご連絡をこちら電話番号から差し上げます。今しばらくお待ちくださいませ。
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding: 5px 0px 5px 0px;">
                            ※営業時間： 09:00〜18:00　定休日：水曜日
                            <br>
                            TEL : {{ $data->store->phone_number }}
                            <br>
                            @if ($data->store->slug == 'keigayasui_kagohara')
                                営業TEL : 048-584-7589
                                <br>
                            @endif
                            @if ($data->store->slug == 'keigayasui_fujiagu')
                                営業TEL : 070-○○○○-5945
                            @elseif ($data->store->slug == 'keigayasui_kagohara')
                                営業TEL : 070-○○○○-1615
                            @elseif ($data->store->slug == 'keigayasui_kasahata')
                                営業TEL : 070-○○○○-4217
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0px 0px 0px; border-bottom: 2px solid black;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top: 10px; padding-bottom: 10px">
                            <table width="100%" cellspacing="0" border="0">

                                <body>
                                    <tr>
                                        <td style="padding: 6px 10px 6px 0px;">
                                            日時 : {{ $data->created_at }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 10px 6px 0px;">
                                            お名前 : {{ $data->name ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 10px 6px 0px;">
                                            フリガナ : {{ $data->how_to_read ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 10px 6px 0px;">
                                            メールアドレス : {{ $data->email ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 10px 6px 0px;">
                                            お電話番号 : {{ $data->phone_number ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 10px 6px 0px;">
                                            郵便番号 : {{ $data->address ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 10px 6px 0px;">
                                            連絡可能な時間帯 :
                                            <br>
                                            {{ $data->can_contacted_day ? implode(', ', json_decode($data->can_contacted_day)) : '-' }}
                                            <br>
                                            {{ $data->can_contacted ? implode(', ', json_decode($data->can_contacted)) : '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 10px 6px 0px;">
                                            お問合わせ車両 : 
                                            <br>
                                            {{ $data->car ? implode(', ', json_decode($data->car)) : '-' }}
                                            <br>
                                            {{ $data->other_car ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 10px 6px 0px;">
                                            項目 :{{ $data->type_question ? implode(', ', json_decode($data->type_question)) : '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 10px 6px 0px;">
                                            ご希望の来店日時 : {{ $data->date1 ?? '-' }}&nbsp;{{ $data->time1 ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 10px 6px 0px;">
                                            お問い合わせ内容 : 
                                            <br>
                                            {{ $data->message ?? '-' }}
                                        </td>
                                    </tr>
                                </body>

                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0px 0px 10px 0px; border-top: 2px solid black;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0px 0px">
                            {{ $data->store->name }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0px 0px">
                            {{ $data->store->postal_code }}&nbsp;&nbsp;&nbsp;{{ $data->store->address }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0px 0px">
                            Tel. {{ $data->store->phone_number }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0px 0px">
                            {{ $data->store->operation }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0px 0px 0px; border-bottom: 2px solid black;">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </body>

</html>
