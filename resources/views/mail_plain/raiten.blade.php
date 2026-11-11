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
※営業時間： 09:00〜18:00　定休日：水曜日
TEL : {{ $data->store->phone_number }}
@if ($data->store->slug == 'flat7_sayama')
営業TEL : 070-○○○○-0853
@elseif ($data->store->slug == 'flat7_ashikaga')
営業TEL : 070-○○○○-0853
@elseif ($data->store->slug == 'flat7_chichibu')
営業TEL : 070-○○○○-7203
@elseif ($data->store->slug == 'keigayasui_fujiagu')
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
ご希望の来店日時① : {{ $data->date1 ?? '-' }} {{ $data->time1 ?? '-' }}
ご希望の来店日時② : {{ $data->date2 ?? '-' }} {{ $data->time2 ?? '-' }}
ご希望の来店日時③ : {{ $data->date3 ?? '-' }} {{ $data->time3 ?? '-' }}
自由記入欄 :
{{ $data->message ?? '-' }}

---
{{ $data->store->name }}
{{ $data->store->postal_code }} {{ $data->store->address }}
Tel. {{ $data->store->phone_number }}
{{ $data->store->operation }}
---