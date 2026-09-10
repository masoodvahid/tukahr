<?php
namespace App\Support;
use Carbon\CarbonImmutable;
final class JalaliDate {
 public static function toGregorian(int $jy,int $jm,int $jd): array {
  $jy+=1595;$days=-355668+(365*$jy)+intdiv($jy,33)*8+intdiv(($jy%33)+3,4)+$jd+(($jm<7)?($jm-1)*31:(($jm-7)*30)+186);
  $gy=400*intdiv($days,146097);$days%=146097;if($days>36524){$gy+=100*intdiv(--$days,36524);$days%=36524;if($days>=365)$days++;}$gy+=4*intdiv($days,1461);$days%=1461;if($days>365){$gy+=intdiv($days-1,365);$days=($days-1)%365;}
  $gd=$days+1;$leap=($gy%4===0&&$gy%100!==0)||($gy%400===0);$months=[0,31,$leap?29:28,31,30,31,30,31,31,30,31,30,31];for($gm=1;$gm<=12&&$gd>$months[$gm];$gm++)$gd-=$months[$gm];return [$gy,$gm,$gd];
 }
 public static function editDeadline(int $jy,int $jm,string $tz='Asia/Tehran'): CarbonImmutable { $ny=$jy;$nm=$jm+1;if($nm===13){$nm=1;$ny++;}[$gy,$gm,$gd]=self::toGregorian($ny,$nm,15);return CarbonImmutable::create($gy,$gm,$gd,0,0,0,$tz)->utc(); }
}
