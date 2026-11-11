<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style type="text/css">
		body{
			font-style: normal;
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
			font-variation-settings: normal;
		}
	</style>
</head>
<body>
	<div style="max-width: 600px; margin: 0px auto; padding-top: 10px;">
		<table  width="100%" role="presentation" cellspacing="0" border="0">
			<tbody>
				<tr>
					<td style="padding: 0px 0px 10px 0px; border-top: 2px solid black;">
					</td>
				</tr>
				<tr>
					<td style="padding: 0px 0px">
						この度は {{ $data->store->name }} にお問い合わせ頂きありがとうございます。
					</td>
				</tr>

				<tr>
					<td style="padding: 0px 0px">
						お問い合わせいただきました内容について営業時間内に30分以内に確認のご連絡をこちらの電話番号から差し上げます。今しばらくお待ちくださいませ。
					</td>
				</tr>
				

				{{-- <tr>
					<td style="padding: 0px 0px">
						2025年08月10日～2025年08月16日の期間中にお問い合わせいただきました内容については、
					</td>
				</tr>
				<tr>
					<td style="padding: 0px 0px">
						08月17日にこちらの電話番号より、確認のご連絡を差し上げます。予めご了承ください。
					</td>
				</tr>
				<tr>
					<td style="padding: 0px 0px">
						お手数をお掛けいたしますが、よろしくお願いいたします。
					</td>
				</tr> --}}

				<tr>
					<td style="padding: 5px 0px 5px 0px;">
						TEL : {{ $data->store->phone_number }}
					</td>
				</tr>
				<tr>
					<td style="padding: 10px 0px 0px 0px; border-bottom: 2px solid black;">
					</td>
				</tr>
				<tr>
					<td style="padding-top: 10px; padding-bottom: 10px">
						<table width="100%" role="presentation" cellspacing="0" border="0">
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
										住所 : {{ $data->address ?? '-' }}
									</td>
								</tr>
								<tr>
									<td style="padding: 6px 10px 6px 0px;">
										連絡可能な時間帯 : 
										<br>
										{{ $data->can_contacted_day ? implode(', ' , json_decode($data->can_contacted_day)) : '-' }}
										<br>
										{{ $data->can_contacted ? implode(', ' , json_decode($data->can_contacted)) : '-' }}
									</td>
								</tr>
								<tr>
									<td style="padding: 6px 10px 6px 0px;">
										お問合わせ車両 : 
										<br>
										{{ $data->car ? implode(', ' , json_decode($data->car)) : '-' }} 
										<br> 
										{{ $data->other_car ?? '-' }}
									</td>
								</tr>
								<tr>
									<td style="padding: 6px 10px 6px 0px;">
										項目 : {{ $data->type_question ? implode(', ' , json_decode($data->type_question)) : '-' }} 
									</td>
								</tr>
								<tr>
									<td style="padding: 6px 10px 6px 0px;">
										ご希望の来店日時 : {{ $data->date1 ?? '-' }}
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