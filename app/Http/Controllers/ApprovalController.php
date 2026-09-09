<?php
namespace App\Http\Controllers;
use App\Enums\ApprovalStage;use App\Models\OtpChallenge;use App\Models\PayrollSheet;use App\Services\Payroll\ApprovalService;use Illuminate\Http\Request;use Illuminate\Validation\Rule;
class ApprovalController extends Controller {
 public function requestOtp(Request $r,PayrollSheet $sheet,ApprovalService $svc){$d=$r->validate(['stage'=>['required',Rule::enum(ApprovalStage::class)],'comment'=>'nullable|string|max:4000','override'=>'nullable|boolean']);$stage=ApprovalStage::from($d['stage']);$c=$svc->request($r->user(),$sheet,$stage,$d['comment']??null,(bool)($d['override']??false));session(['approval_context'=>['challenge_id'=>$c->id,'stage'=>$stage->value,'comment'=>$d['comment']??null,'override'=>(bool)($d['override']??false)]]);return back()->with('status','کد تأیید ارسال شد.')->with('otp_pending',true);}
 public function confirm(Request $r,PayrollSheet $sheet,ApprovalService $svc){$d=$r->validate(['code'=>'required|digits:6']);$ctx=session('approval_context');abort_unless($ctx&&$ctx['challenge_id'],422);$c=OtpChallenge::findOrFail($ctx['challenge_id']);abort_unless((int)$c->sheet_id===(int)$sheet->id,422);$svc->confirm($r->user(),$c,$d['code'],ApprovalStage::from($ctx['stage']),$ctx['comment'],$ctx['override']);session()->forget('approval_context');return back()->with('status','تأیید با موفقیت ثبت شد.');}
}
