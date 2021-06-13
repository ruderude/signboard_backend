<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
use App\Models\User;
use JWTAuth;
use Carbon\Carbon;
use Log;

class JWTAuthController extends Controller
{
    /**
     * コンストラクター
     */
    public function __construct()
    {
        $this->middleware('api', ['except' => ['regist','login','reminder']]);
    }


    /**
     * レスポンス作成
     *
     * @param string $status httpStatus Number
     * @param string $statusText
     * @param array $data
     * @param string $request
     * @return array
     */
    protected function buildResponse($status,$statusText,$data,$request){
        $response = [
            'status' => $status,
            'statusText' => $statusText,
            'data' => $data,
            'request' => $request
        ];

        return $response;
    }


    /**
     * ユーザー登録（レジスト）
     * name,email,passwordをリクエストパラメータで受け取る必要がある。
     *
     * @param Request $request
     * @return json
     */
    public function register(Request $request)
    {
        // 入力情報のバリデーション
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:64|confirmed',
        ]);

        // バリデーションエラーの場合レスポンス
        if($validator->fails()){
            $response = $this->buildResponse(400,$validator->errors(),'','register');
            return response()->json($response, $response['status']);
        }

        $user = new User;
        $user->fill($request->all());

        // 仮登録用Emailへ移動
        $user->verify_email_address = $request->email;
        // 仮のEmail。EmailはUniqのためダブらないようにランダムにする。
        $user->email = Str::random(32)."@temp.tmp";
        // パスワード暗号化
        $user->password = bcrypt($request->password);

        // 仮登録確認用設定
        $user->verify_email = false;
        $user->verify_token = Str::random(32);
        $user->verify_date = Carbon::now()->toDateTimeString();

        // ユーザー情報保存
        $user->save();
        // TODO:ここにメール送信処理を追加
        $this->sendVerifyMail("regist",$user->verify_email_address,$user->verify_token);
        // レスポンス作成
        $response = $this->buildResponse(200,"OK",'',"register");

        Log::info('Verify Regist User:'.$user);

        return response()->json($response, $response['status']);
    }

    /**
     * WEBアクセス Email認証用メソッド
     *
     * Emailで届いたトークンを承認する
     *
     * @param string $token
     * @return view
     */
    public function verify($token){

        $params['result'] = "error";

        // トークンの有効期限を30分とするため有効な時間を算出
        // 現在時間 -30分
        $verify_limit = Carbon::now()->subMinute(30)->toDateTimeString();

        $user = User::where('verify_token', $token)->where('verify_date', '>', $verify_limit)->first();

        if($user){
            // 本登録されていない
            if(User::where("email", $user->verify_email_address)->first()){
                $params['result'] = "exist";
                Log::info('Verify Exist: ' .$user->verify_email_address);
            }else{
                // 仮メールアドレスを本メールに移動
                $user->email = $user->verify_email_address;
                // 仮メールアドレスを削除
                $user->verify_email_address = null;
                // 有効なユーザーにする
                $user->verify_email = true;
                // その他クリーニング
                $user->verify_token = null;
                $user->verify_date = null;
                // 承認日登録
                $user->email_verified_at = Carbon::now()->toDateTimeString();

                // テーブル保存
                $user->save();
                $params['result'] = "success";
                Log::info('Verify Success: '.$user);
            }
        }else{
            Log::info('Verify Not Found: token='.$token);
        }

        return view('jwt.verify', $params);
    }


    /**
     * 認証メールを送信する
     *
     * @param [type] $type
     * @param [type] $email
     * @param [type] $token
     * @return void
     */
    public function sendVerifyMail($type, $email, $token)
    {
        $data = ['token' => $token];


        if($type == 'regist'){
            Mail::send('jwt.emails.user_register', $data, function($message) use ($email){
                $message
                    ->to($email)
                    ->from(config('mail.from.address'))
                    ->subject('ユーザー登録の確認メール');
            });
        }
        if($type == 'reminder'){
            Mail::send('jwt.emails.user_reminder', $data, function($message) use ($email){
                $message
                    ->to($email)
                    ->from(config('mail.from.address'))
                    ->subject('パスワード変更確認メール');
            });
        }
    }

    /**
     * ログイン
     * email,passwordをリクエストパラメータで受け取る必要がある。
     *
     * @param Request $request
     * @return json
     */
    public function login(Request $request)
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if($validator->fails()){
            $response = $this->buildResponse(400,$validator->errors(),'','login');
            return response()->json($response, $response['status']);
        }

        // ユーザーの存在確認
        $user = User::where('email',$request->email)->where('verify_email', true)->first();
        if(!$user){
            $response = $this->buildResponse(400,'User not found or not authenticated','','login');
            return response()->json($response, $response['status']);
        }

        //トークン作成
        $token = auth('api')->attempt(['email' => $request->email, 'password' => $request->password]);
        if($token){
            $data = [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ];
            $response = $this->buildResponse(200,'OK',$data,'login');
        }else{
            $response = $this->buildResponse(400,'Password not found or no user','' ,'login');
            Log::info('Login Auth Token Genarete Error'.$request->email."----".$request->password);
        }
        return response()->json($response, $response['status']);

    }

    /**
     * ログアウト
     *
     * @return json
     */
    public function logout()
    {
        auth()->logout();

        $response = $this->buildResponse(200,'OK','','logout');
        return response()->json($response, $response['status']);

    }

    /**
     * リフレッシュ・トークン.
     *
     * 有効期限超えて使う場合は新しいトークンを取得。
     * 再度認証をおこなわずに新しいトークンに更新するには、
     * リフレッシュAPIを使用する。
     *
     * @return jsonRsponse
     */
    public function refresh()
    {
        // リフレッシュトークン取得
        $token = auth()->refresh();
        if($token){
            $data = [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ];
            $response = $this->buildResponse(200,'OK',$data,'refresh');
        }else{
            $response = $this->buildResponse(500,'Login Auth Error','' ,'refresh');
            Log::info('Login Auth Token Genarete Error'.$request->email."----".$request->password);
        }
        return response()->json($response, $response['status']);
    }

    /**
     * ユーザー情報の取得
     *
     * @return json
     */
    public function me()
    {
        $user = auth('api')->user();

        if(!$user){
            $response = $this->buildResponse(401,'Not Found or Unauthorized','','me');
            return response()->json($response, $response['status']);
        }

        $response = $this->buildResponse(200,'OK',$user,'me');
        return response()->json($response, $response['status']);
    }


    /**
     * ユーザー情報の更新
     *
     * @param Request $request
     * @return json
     */
    public function update(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name_kana' => 'regex:/^[ア-ン゛゜ァ-ォャ-ョー 　]+$/u',
            'birthday' => 'date',
            'gender' => 'in:male,femail',
            'zip_cd' => 'digits:7',
            'pref_id' => 'min:1|max:47',
            'address1' => 'string|max:255',
            'address2' => 'string|max:255',
            'address3' => 'string|max:255',
            'phone_number' => 'regex:/^[0-9]{10,11}$/i'
        ]);

        if($validator->fails()){
            $response = $this->buildResponse(400, $validator->errors(), '', 'update');
            return response()->json($response, $response['status']);
        }

        $user = auth('api')->user();

        $form = $request->all();
        // 更新されると困るものを除外,Usersのfillable宣言以外の項目
        unset($form['name']);
        unset($form['email']);
        unset($form['password']);

        $user->fill($form)->save();

        $response = $this->buildResponse(200, 'OK', $user, 'update');
        return response()->json($response, $response['status']);
    }

    /**
     * パスワードリマインダー
     *
     * @param Request $request
     * @return json
     */
    public function reminder(Request $request){

        // ユーザーが存在するか
        if($request->email){
            $user = User::where('email',$request->email)->first();
        }

        if(!$user){
            $response = $this->buildResponse(400, 'User Not Found', '', 'reminder');
            Log::info('Reminder User:'.'User Not Found');
            return response()->json($response, $response['status']);
        }

        // verify_token作成し保存
        $user->verify_token = Str::random(32);
        $user->verify_date = Carbon::now()->toDateTimeString();
        $user->save();

        // メール送信
        $this->sendVerifyMail("reminder",$user->email,$user->verify_token);

        // レスポンス作成
        $response = $this->buildResponse(200, 'OK', '', 'reminder');
        Log::info('Reminder User:'.$user);
        return response()->json($response, $response['status']);

    }

    /**
      * WEBリクエスト パスワード設定画面
      *
      * @param Request $request
      * @return view
      */
    public function input_password(Request $request){

        $token = $request->id;

        $verify_limit = Carbon::now()->subMinute(30)->toDateTimeString();
        // tokenが一致するか
        $user = User::where('verify_token', $token)->where('verify_date', '>', $verify_limit)->first();

        if($user){
            return view('jwt.input_password')->with('token',$token);
        }
        return view('jwt.reminder')->with('result','error');
    }


    /**
      * WEBリクエスト パスワードリマインダー
      *
      * @param Request $request
      * @return View
      */
    public function password_change(Request $request){

        $params['result'] = "error";

        // 入力情報のバリデーション
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
            'token' => 'required',
        ]);

        $token = $request->token;

        // バリデーションエラーの場合レスポンス
        if($validator->fails()){
            $params['message'] = $validator->errors();
            Log::info('Reminder Error: '.$validator->errors());
            return redirect('/reminder/'.$token)
                        ->withErrors($validator)
                        ->withInput();
        }else{

            // トークンの有効期限を30分とするため有効な時間を算出
            // 現在時間 -30分
            $verify_limit = Carbon::now()->subMinute(30)->toDateTimeString();
            // tokenが一致するか
            $user = User::where('verify_token', $token)->where('verify_date', '>', $verify_limit)->first();

            if($user){

                // パスワードを変更する
                $user->password = bcrypt($request->password);
                // その他クリーニング
                $user->verify_token = null;
                $user->verify_date = null;
                // 承認日登録
                $user->email_verified_at = Carbon::now()->toDateTimeString();

                // テーブル保存
                $user->save();
                Log::info('Reminder Success: '.$user);
                $params = ['result' => 'success'];
            }else{
                Log::info('Reminder Error: Notfound User');
                $params = ['result' => 'error'];
            }
        }
        return view('jwt.reminder', $params);
    }
}
