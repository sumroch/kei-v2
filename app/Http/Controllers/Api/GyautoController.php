<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\GyautoQuestionMail;
use App\Models\GyautoQuestion;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Gyauto (car lease) forms: POST /api/gyauto/{type}.
 * The site is WordPress + Contact Form 7, so responses mimic the wpcf7 format, as before.
 */
class GyautoController extends Controller
{
    private const DEFAULT_STORE = 'joycal_hanazono';

    private const THANKS_URL = 'https://kei-ichiman.com/carlease/thanks/';

    private const MESSAGES = [
        'required' => '必須項目に入力してください。',
        'same' => 'メールアドレスが一致しません。同じであることを確認してください。',
        'email' => 'メールアドレスを入力してください。 ',
    ];

    public function store(string $type, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules($type), self::MESSAGES);

        if ($validator->fails()) {
            return response()->json([
                'is_error' => false,
                'status' => 'validation_field',
                'message' => '入力内容に問題があります。確認して再度お試しください。',
                'invalid_fields' => $this->invalidFields($validator->errors()->messages()),
            ]);
        }

        $store = Store::where('slug', $request->input('store', self::DEFAULT_STORE))->first()
            ?? Store::where('slug', self::DEFAULT_STORE)->firstOrFail();

        $data = $request->only((new GyautoQuestion)->getFillable());
        $data['store_id'] = $store->id;
        $data['type_question'] = $type;

        $question = GyautoQuestion::create($data);

        try {
            Mail::to($question->email)->send(new GyautoQuestionMail($question, $type));
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'redirect' => [
                ['type' => 'redirect', 'redirect_url' => self::THANKS_URL, 'delay' => 0],
            ],
            'contact_form_id' => $type,
            'status' => 'mail_sent',
            'message' => 'ありがとうございます。メッセージは送信されました。',
            'posted_data_hash' => '4405df9fdded0dd363fe8748bb57eeab',
            'into' => "#wpcf7-f{$type}-o1",
            'invalid_fields' => [],
        ]);
    }

    private function rules(string $type): array
    {
        $email = [
            'email' => 'required|email|same:email_confirm',
            'email_confirm' => 'required|email',
        ];

        return match ($type) {
            'raiten' => [
                'name' => 'required',
                'phone_number' => 'required',
                ...$email,
                'store' => 'required',
                'date_1' => 'required',
            ],
            'loan' => [
                'name' => 'required',
                'how_to_read' => 'required',
                'phone_number' => 'required',
                'gender' => 'required',
                'date_birth' => 'required',
                ...$email,
                'address' => 'required',
                'married_status' => 'required',
                'living_status' => 'required',
                'living_year' => 'required',
                'licence_number' => 'required',
                'car_name' => 'required',
                'company' => 'required',
                'company_telephone' => 'required',
                'company_address' => 'required',
                'salary' => 'required',
                'work_year' => 'required',
                'company_type' => 'required',
                'job' => 'required',
                'store' => 'required',
            ],
            default => [
                'name' => 'required',
                'phone_number' => 'required',
                'address' => 'required',
                ...$email,
            ],
        };
    }

    /** Contact Form 7 error format. */
    private function invalidFields(array $errors): array
    {
        $fields = [];

        foreach ($errors as $key => $messages) {
            $fields[] = [
                'into' => "span.wpcf7-form-control-wrap.your-{$key}",
                'message' => $messages[0],
                'idref' => null,
                'error_id' => "wpcf7-f143-o2-ve-your-{$key}",
            ];
        }

        return $fields;
    }
}
