<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Nahid\Talk\Facades\Talk;
use Auth;
use View;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Encryption\Encrypter;

class MessageController extends Controller
{
    protected $authUser;
    public function __construct()
    {
        $this->middleware('auth');
        Talk::setAuthUserId(Auth::user()->id);

        View::composer('partials.peoplelist', function($view) {
            $threads = Talk::threads();
            $view->with(compact('threads'));
        });
    }

    public function chatHistory($id)
    {
        $conversations = Talk::getMessagesByUserId($id, 0, 50);
        $user = '';
        $messages = [];
        $decrypted_messages = [];
        if(!$conversations) {
            $user = User::find($id);
        } else {
            $user = $conversations->withUser;
            $messages = $conversations->messages;
        }

        if (count($messages) > 0) {
            $messages = $messages->sortBy('id');
        } 

        // foreach ($messages as $encryptedValue) {
        //     try {
        //         $encryptedValue = Crypt::decryptString($encryptedValue -> message);
        //         array_push($decrypted_messages,$encryptedValue);
        //     } catch (DecryptException $e) {
        //         //
        //     }
        // }

        return view('messages.conversations', compact('messages', 'user'));
    }

    public function ajaxSendMessage(Request $request)
    {
        if ($request->ajax()) {
            $rules = [
                'message-data'=>'required',
                '_id'=>'required'
            ];


            $this->validate($request, $rules);

            $body = $request->input('message-data');
            $userId = $request->input('_id');

            if ($message = Talk::sendMessageByUserId($userId, Crypt::encryptString($body))) {
                $html = view('ajax.newMessageHtml', compact('message'))->render();
                return response()->json(['status'=>'success', 'html'=>$html], 200);
            }
        }
    }

    public function ajaxDeleteMessage(Request $request, $id)
    {
        if ($request->ajax()) {
            if(Talk::deleteMessage($id)) {
                return response()->json(['status'=>'success'], 200);
            }

            return response()->json(['status'=>'errors', 'msg'=>'something went wrong'], 401);
        }
    }

    public function tests()
    {
        dd(Talk::channel());
    }
}
