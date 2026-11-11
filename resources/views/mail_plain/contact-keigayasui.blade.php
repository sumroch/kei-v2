---
{{ $data->name ?? '-' }} 様
この度は {{ $data->store->name }} にお問い合わせ頂きありがとうございます。
@if (date('Y-m-d') == date('Y-m-d', strtotime('2026-08-16')))
2026年08月09日～2026年08月15日の期間中にお問い合わせいただきました内容については、
08月16日にこちらの電話番号より、確認のご連絡を差し上げます。予めご了承ください。
お手数をお掛けいたしますが、よろしくお願いいたします。
@else
お問い合わせいただきました内容について営業時間内に30分以内に確認のご連絡をこちら電話番号から差し上げます。今しばらくお待ちくださいませ。
@endif
※営業時間： 09:00〜18:00　定休日：水曜日 TEL : {{ $data->store->phone_number }}
@if ($data->store->slug == 'keigayasui_kagohara')
営業TEL : 048-584-7589
@endif
@if ($data->store->slug == 'keigayasui_fujiagu')
営業TEL : 070-○○○○-5945
@elseif ($data->store->slug == 'keigayasui_kagohara')
営業TEL : 070-○○○○-1615
@elseif ($data->store->slug == 'keigayasui_kasahata')
営業TEL : 070-○○○○-4217
@endif
---

日時 : {{ $data->created_at }}
お名前 : {{ $data->name ?? '-' }}
フリガナ : {{ $data->how_to_read ?? '-' }}
メールアドレス : {{ $data->email ?? '-' }}
お電話番号 : {{ $data->phone_number ?? '-' }}
郵便番号 : {{ $data->address ?? '-' }}
連絡可能な時間帯 :
{{ $data->can_contacted_day ? implode(', ', json_decode($data->can_contacted_day)) : '-' }}
{{ $data->can_contacted ? implode(', ', json_decode($data->can_contacted)) : '-' }}
お問合わせ車両 :
{{ $data->car ? implode(', ', json_decode($data->car)) : '-' }}
{{ $data->other_car ?? '-' }}
項目 :{{ $data->type_question ? implode(', ', json_decode($data->type_question)) : '-' }}
ご希望の来店日時 : {{ $data->date1 ?? '-' }} {{ $data->time1 ?? '-' }}
お問い合わせ内容 :
{{ $data->message ?? '-' }}

---
{{ $data->store->name }}
{{ $data->store->postal_code }} {{ $data->store->address }}
Tel. {{ $data->store->phone_number }}
{{ $data->store->operation }}
---