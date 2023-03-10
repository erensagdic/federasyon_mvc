<?php

namespace App\Http\Controllers\Kurum;

use App\Http\Controllers\Controller;
use App\Models\dersProgramiModel;
use App\Models\gunlerModel;
use App\Models\kurumDersModel;
use App\Models\kurumLogModel;
use App\Models\kurumModel;
use App\Models\kurumOkulModel;
use App\Models\kurumUserModel;
use App\Models\LogModel;
use App\Models\ogrenciSinifModel;
use App\Models\ogretmenDersModel;
use App\Models\ogretmenKurumModel;
use App\Models\OkulModel;
use App\Models\sinifModel;
use App\Models\User;
use App\Models\yoklamaModel;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use stdClass;

class kurumSinifController extends Controller
{
    public function index(Request $r)
    {
        $okulExist = false;
        $okul = null;
        if ($r->okul) {
            $okul = OkulModel::find($r->okul);
            if ($okul) {
                $kurumOkulExist = kurumOkulModel::where([
                    'kurum_id' => get_current_kurum()->id,
                    'okul_id' => $okul->id
                ])->first();
                if ($kurumOkulExist) {
                    $okulExist = true;
                    $siniflar = sinifModel::where([
                        'kurum_id' => get_current_kurum()->id,
                        'okul_id' => $okul->id
                    ])
                        ->with('ogrenciler')
                        ->get();
                }
            }
        }
        $kurum = get_current_kurum();
        $kurumOkullar = kurumOkulModel::where('kurum_id', $kurum->id)->join('okul', 'kurum_okul.okul_id', '=', 'okul.id')->orderBy('okul.ad')->with('okul')->get();
        if ($kurumOkullar->count() <= 0) {
            return redirect()->route('kurum_okul_index')->withErrors("Kurumunuza ait okul bulunmuyor.");
        }
        if (!$okulExist) {
            $kurumOkul = kurumOkulModel::where('kurum_id', get_current_kurum()->id)->first();
            $okul = OkulModel::find($kurumOkul->okul_id);
            if (!$okul)
                return redirect()->route('kurum_okul_index')->withErrors("Okul Bulunamad??.");
            $siniflar = sinifModel::where([
                'kurum_id' => get_current_kurum()->id,
                'okul_id' => $okul->id
            ])
                ->with('ogrenciler')
                ->get();
        }
        return view('kurum.siniflar.index')->with([
            'kurum' => $kurum,
            'kurumOkullar' => $kurumOkullar,
            'okulExist' => $okulExist,
            'okul' => $okul,
            'siniflar' => $siniflar,
        ]);
    }
    public function add(Request $request)
    {
        try {
            $rules = array(
                'okul_id' => array('required'),
                'yeniSinifAd' => array('required', 'string', 'max:30'),
            );
            $attributeNames = array(
                'Okul' => "Okul",
                'yeniSinifAd' => "S??n??f Ad??",
            );
            $messages = array(
                'required' => ':attribute alan?? zorunlu.',
                'max' => ':attribute alan?? maksimum :max karakter olmal??d??r.',
            );
            $validator = Validator::make($request->all(), $rules, $messages, $attributeNames);
            if ($validator->fails())
                throw new Exception($validator->errors()->first());
            $kurum = get_current_kurum();
            $okulExist = kurumOkulModel::where('kurum_id', $kurum->id)->where('okul_id', $request->okul_id)->first();
            if (!$okulExist)
                throw new Exception("Bir Hata Olu??tu. Sayfay?? yenileyin");
            $sinifExist = sinifModel::where('kurum_id', $kurum->id)->where('okul_id', $request->okul_id)->where('ad', $request->yeniSinifAd)->first();
            if ($sinifExist)
                throw new Exception("Bu s??n??f okulunuzda mevcut");
            $okul = OkulModel::find($request->okul_id);
            sinifModel::create([
                'kurum_id' => $kurum->id,
                'okul_id' => $request->okul_id,
                'ad' => $request->yeniSinifAd,
            ]);


            $logUser = auth()->user();
            $logText = "Kurum Yetkilisi $logUser->ad $logUser->soyad ($logUser->ozel_id), '$okul->ad' okuluna '$request->yeniSinifAd' adl?? s??n??f?? a??t??";
            LogModel::create(['kategori_id' => 13, 'logText' => $logText]);

            $kurumlogText = "$logUser->ad $logUser->soyad ($logUser->ozel_id), '$okul->ad' adl?? okula '$request->yeniSinifAd' adl?? s??n??f?? a??t??";
            kurumLogModel::create([
                'kategori_id' => 8,
                'logText' => $kurumlogText,
                'kurum_id' => get_current_kurum()->id,
            ]);



            return response()->json(['message' => "$request->yeniSinifAd s??n??f?? olu??turuldu."]);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 404);
        }
    }
    public function get(Request $request)
    {
        try {
            if (!$request->okul_id)
                throw new Exception("Bir hata olu??tu");
            $kurum = get_current_kurum();
            if (!$kurum)
                throw new Exception("Bir hata olu??tu");
            $okulKurum = kurumOkulModel::where('kurum_id', $kurum->id)->where('okul_id', $request->okul_id)->first();
            if (!$okulKurum)
                throw new Exception("Okul bilgisi al??namad??");
            $siniflar = sinifModel::where('kurum_id', $kurum->id)->where('okul_id', $request->okul_id)->orderBy('ad')->with('ogrenciler')->get();
            return response()->json(['data' => $siniflar]);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 404);
        }
    }
    public function show(Request $request)
    {
        try {
            if (!$request->id)
                throw new Exception("S??n??f Bulunamad??");
            $sinif = sinifModel::find($request->id);
            if (!$sinif)
                throw new Exception("S??n??f Bulunamad??");
            $kurum = get_current_kurum();
            if ($sinif->kurum_id != $kurum->id)
                throw new Exception("S??n??f Bulunamad??");
            $yoklamaShow = false;
            if ($request->yoklama_tarih) {
                $time_input = strtotime($request->yoklama_tarih);
                $today = date("Y-m-d", $time_input);
                $yoklamaShow = true;
            } else
                $today = date("Y-m-d");
            $mon = new DateTime($today);
            $sun = new DateTime($today);
            $mon->modify('this week');
            $sun->modify('this week +6 day');
            $first_day_of_week = $mon->format("Y-m-d");
            $last_day_of_week = $sun->format("Y-m-d");
            $dersler = kurumDersModel::where('kurum_id', get_current_kurum()->id)->get();
            $gunler = gunlerModel::all();
            $dersProgrami = dersProgramiModel::where('sinif_id', $sinif->id)
                ->with('ders')
                ->with('ogretmen')
                ->orderBy('baslangic')
                ->get();
            $saatler = [];
            $dersGunleri = [];
            foreach ($dersProgrami as $key) {
                $string = $key->baslangic . "-" . $key->bitis;
                if (!in_array($string, $saatler))
                    array_push($saatler, $string);
            }
            $defaultDersID = 0;
            if ($dersler->count() > 0) {
                $forYoklamaDersProgrami = dersProgramiModel::where('sinif_id', $sinif->id)
                    ->where('ders_id', $request->yoklama_ders ? $request->yoklama_ders : $dersler->first()->id)
                    ->get();
                foreach ($forYoklamaDersProgrami as $key) {
                    if (!in_array($key->gun_id, $dersGunleri))
                        array_push($dersGunleri, $key->gun_id);
                }
                sort($dersGunleri);
                $defaultDersID = $dersler->first()->id;
            }
            if ($request->yoklama_ders) {
                $yoklama = yoklamaModel::with('ders_programi')
                    ->whereHas('ders_programi', function ($q) use ($request) {
                        return $q->where([
                            'kurum_id' => get_current_kurum()->id,
                            'ders_id' => $request->yoklama_ders
                        ]);
                    })
                    ->get();
            } else {
                $yoklama = yoklamaModel::with('ders_programi')
                    ->whereHas('ders_programi', function ($q) {
                        return $q->where('kurum_id', get_current_kurum()->id);
                    })->get();
            }


            $ogrenciler = ogrenciSinifModel::where('sinif_id', $sinif->id)->with('ogrenci')->with('okul')->get();
            $ogretmenler = ogretmenKurumModel::where('kurum_id', get_current_kurum()->id)->with('ogretmen')->get()->sortBy('ogretmen.ad');
            $kurumOkullar = kurumOkulModel::where('kurum_id', get_current_kurum()->id)->with('okul')->join('okul', 'kurum_okul.okul_id', '=', 'okul.id')->orderBy('okul.ad')->get();
            return view('kurum.siniflar.show')->with([
                'sinif' => $sinif,
                'ogrenciler' => $ogrenciler,
                'kurumOkullar' => $kurumOkullar,
                'dersler' => $dersler,
                'gunler' => $gunler,
                'ogretmenler' => $ogretmenler,
                'dersProgrami' => $dersProgrami,
                'dersGunleri' => $dersGunleri,
                'saatler' => $saatler,
                'yoklama' => $yoklama,
                'first_day_of_week' => $first_day_of_week,
                'last_day_of_week' => $last_day_of_week,
                'yoklamaShow' => $yoklamaShow,
                'defaultDersID' => $defaultDersID,

            ]);
        } catch (Exception $ex) {
            return redirect()->route('kurum_sinif_index')->withErrors($ex->getMessage());
        }
    }
    public function ogrenciEkleTc(Request $request)
    {
        try {
            if (!$request->tc)
                throw new Exception("????renci Bulunamad??");
            // $ogrenci = User::where('tc_kimlik', $request->tc)->first();
            $ogrenci = User::where(function ($query) use ($request) {
                $query->where('tc_kimlik', '=',  $request->tc)
                    ->orWhere('ozel_id', '=', $request->tc);
            })->first();
            if (!$ogrenci)
                throw new Exception("????renci Bulunamad??");
            if (!$ogrenci->hasRole("????renci"))
                throw new Exception("????renci Bulunamad??");
            if (!$request->sinif_id)
                throw new Exception("S??n??f bilgisi al??namad??");
            $sinif = sinifModel::find($request->sinif_id);
            if (!$sinif)
                throw new Exception("S??n??f bilgisi al??namad??");
            $kurum = get_current_kurum();
            if ($sinif->kurum_id != $kurum->id)
                throw new Exception("S??n??f bilgisi al??namad??");
            $ogrenciSinifExist = ogrenciSinifModel::where('sinif_id', $request->sinif_id)->where('ogrenci_id', $ogrenci->id)->first();
            if ($ogrenciSinifExist)
                throw new Exception("????renci Zaten Bu S??n??fta");
            ogrenciSinifModel::create([
                'sinif_id' => $request->sinif_id,
                'ogrenci_id' => $ogrenci->id,
            ]);
            $logUser = auth()->user();
            $logText = "Kurum Yetkilisi $logUser->ad $logUser->soyad ($logUser->ozel_id), ????renci $ogrenci->ad $ogrenci->soyad ($ogrenci->ozel_id) s??n??fa ekledi; '$sinif->ad'";
            LogModel::create(['kategori_id' => 14, 'logText' => $logText]);


            $kurumlogText = "$logUser->ad $logUser->soyad ($logUser->ozel_id), ????renci $ogrenci->ad $ogrenci->soyad ($ogrenci->ozel_id) s??n??fa ekledi; '$sinif->ad'";
            kurumLogModel::create(['kategori_id' => 9, 'logText' => $kurumlogText, 'kurum_id' => get_current_kurum()->id]);
            return response()->json(['message' => "????renci $ogrenci->ad $ogrenci->soyad, s??n??fa eklendi"]);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 404);
        }
    }
    public function getSiniflar(Request $request)
    {
        try {
            if (!$request->okul_id)
                throw new Exception("S??n??f bilgisi al??namad??");
            $okul = OkulModel::find($request->okul_id);
            if (!$okul)
                throw new Exception("Okul bilgisi al??namad??");
            $kurum = get_current_kurum();
            if (!$kurum)
                throw new Exception("Kurum bilgisi al??namad??");
            $kurumOkulExist = kurumOkulModel::where([
                'okul_id' => $okul->id,
                'kurum_id' => $kurum->id,
            ]);
            if (!$kurumOkulExist)
                throw new Exception("Okul bilgisi al??namad??");
            $sinif = sinifModel::where([
                'kurum_id' => $kurum->id,
                'okul_id' => $okul->id,
            ])->get();
            return response()->json(['data' => $sinif]);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 404);
        }
    }
    public function ogrenciEkleToplu(Request $request)
    {
        try {
            if (!$request->values)
                throw new Exception("Veri Al??namad??");
            $data = json_decode($request->values);
            $sinif = sinifModel::find($request->sinif);
            if (!$sinif)
                throw new Exception("S??n??f verisi al??namad??");
            if ($sinif->kurum_id != get_current_kurum()->id)
                throw new Exception("S??n??f verisi al??namad??");
            $logArray = array();
            foreach ($data as $key) {
                if ($key->durum) {
                    $exist = ogrenciSinifModel::where('sinif_id', $sinif->id)->where('ogrenci_id', $key->id)->first();
                    if (!$exist) {
                        $anlikOgrenci = User::find($key->id);
                        if ($anlikOgrenci->hasRole('????renci')) {
                            ogrenciSinifModel::create([
                                'sinif_id' => $sinif->id,
                                'ogrenci_id' => $key->id
                            ]);
                            $stringHere = $anlikOgrenci->ad . " " . $anlikOgrenci->soyad . "(" . $anlikOgrenci->ozel_id . ")";
                            array_push($logArray, $stringHere);
                        }
                    }
                }
            }

            $logArray = implode(", ", $logArray);


            $logUser = auth()->user();
            $logText = "Kurum Yetkilisi $logUser->ad $logUser->soyad ($logUser->ozel_id), '$sinif->ad' s??n??f??na ????renciler ekledi : $logArray";
            LogModel::create(['kategori_id' => 14, 'logText' => $logText]);

            $kurumLogText = "$logUser->ad $logUser->soyad ($logUser->ozel_id), '$sinif->ad' s??n??f??na ????renciler ekledi : $logArray";
            kurumLogModel::create(['kategori_id' => 9, 'logText' => $kurumLogText, 'kurum_id' => get_current_kurum()->id]);

            return response()->json(['message' => "????renciler s??n??fa eklendi"]);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 404);
        }
    }
    public function ogrenciCikar(Request $request)
    {
        try {
            if (!$request->id)
                throw new Exception("????renci bilgisi al??namad??");
            if (!$request->sinif)
                throw new Exception("S??n??f bilgisi al??namad??");
            $exist = ogrenciSinifModel::where('ogrenci_id', $request->id)->where('sinif_id', $request->sinif)->first();
            if (!$exist)
                throw new Exception("????renci s??n??f??n??zda de??il");
            $sinif = sinifModel::find($request->sinif);
            if (!$sinif)
                throw new Exception("S??n??f bilgisi al??namad??");
            if ($sinif->kurum_id != get_current_kurum()->id)
                throw new Exception("S??n??f bilgisi al??namad??");
            $user = User::find($request->id);
            if (!$user)
                throw new Exception("????renci bilgisi al??namad??");
            $exist->delete();
            $logUser = auth()->user();
            $logText = "Kurum Yetkilisi $logUser->ad $logUser->soyad ($logUser->ozel_id), '$sinif->ad' adl?? s??n??ftan '$user->ad $user->soyad' ????renciyi ????kard??";
            LogModel::create(['kategori_id' => 19, 'logText' => $logText]);
            $kurumLogText = "$logUser->ad $logUser->soyad ($logUser->ozel_id), '$sinif->ad' adl?? s??n??ftan '$user->ad $user->soyad' ????renciyi ????kard??";
            kurumLogModel::create(['kategori_id' => 14, 'logText' => $kurumLogText, 'kurum_id' => get_current_kurum()->id]);
            return response()->json(['message' => "????renci '$user->ad $user->soyad' s??n??ftan kald??r??ld??"]);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 404);
        }
    }
    public function dersProgramiCreate(Request $r)
    {
        try {
            $rules = array(
                'ders_id' => array('required'),
                'gun_id' => array('required', 'integer', 'between:1,7'),
                'baslangic' => array('required'),
                'bitis' => array('required'),
                'ogretmen_id' => array('required'),
                'sinif_id' => array('required', 'integer'),
            );
            $attributeNames = array(
                'ders_id' => "Ders",
                'gun_id' => "G??n",
                'baslangic' => "Ba??lang????",
                'bitis' => "Biti??",
                'ogretmen_id' => "????retmen",
                'sinif_id' => "S??n??f",
            );
            $messages = array(
                'required' => ':attribute alan?? zorunlu.',
                'integer' => ':attribute alan?? numerik olmal??d??r.',
                'between' => ':attribute alan?? 1 ile 7 aras??nda olmal??d??r',
            );
            $validator = Validator::make($r->all(), $rules, $messages, $attributeNames);
            if ($validator->fails())
                throw new Exception($validator->errors()->first());
            $sinif = sinifModel::find($r->sinif_id);
            if (!$sinif)
                throw new Exception("S??n??f bilgisi al??namad??, L??tfen sayfay?? yenileyin");
            if ($sinif->kurum_id != get_current_kurum()->id)
                throw new Exception("S??n??f bilgisi al??namad??, L??tfen sayfay?? yenileyin");
            $ders = kurumDersModel::find($r->ders_id);
            if (!$ders)
                throw new Exception("Ders bilgisi al??namad??, L??tfen sayfay?? yenileyin veya ders ekleyin");
            if ($ders->kurum_id != get_current_kurum()->id)
                throw new Exception("Ders bilgisi al??namad??, L??tfen sayfay?? yenileyin veya ders ekleyin");
            $ogretmen = User::find($r->ogretmen_id);
            if (!$ogretmen)
                throw new Exception("????retmen bilgisi al??namad??, L??tfen sayfay?? yenileyin veya dersinize ????retmen atay??n");
            if (!$ogretmen->hasRole('????retmen'))
                throw new Exception("????retmen bilgisi al??namad??, L??tfen sayfay?? yenileyin veya dersinize ????retmen atay??n");
            $ogretmen_kurum_exist = ogretmenKurumModel::where('ogretmen_id', $ogretmen->id)->where('kurum_id', get_current_kurum()->id)->first();
            if (!$ogretmen_kurum_exist)
                throw new Exception("????retmen bilgisi al??namad??, L??tfen sayfay?? yenileyin");

            $doluSaatler = [];
            $gununProgrami = dersProgramiModel::where([
                'sinif_id' => $sinif->id,
                'gun_id' => $r->gun_id
            ])->get();
            foreach ($gununProgrami as $key) {
                array_push($doluSaatler, $key->baslangic . "-" . $key->bitis);
            }
            foreach ($doluSaatler as $key) {
                $baslangic = explode("-", $key)[0];
                $bitis = explode("-", $key)[1];
                $baslangicFormat = DateTime::createFromFormat('H:i', $baslangic);
                $bitisFormat = DateTime::createFromFormat('H:i', $bitis);
                $currentBaslangicFormat = DateTime::createFromFormat('H:i', $r->baslangic);
                $currentBitisFormat = DateTime::createFromFormat('H:i', $r->bitis);
                if ($currentBaslangicFormat >= $currentBitisFormat)
                    throw new Exception("Ba??lang???? saati biti?? saatinden b??y??k veya e??it olamaz");
                if ($currentBaslangicFormat >= $baslangicFormat && $currentBaslangicFormat < $bitisFormat)
                    throw new Exception("Ba??lang???? Saati ??ak??????yor");
                if ($currentBitisFormat > $baslangicFormat && $currentBitisFormat <= $bitisFormat)
                    throw new Exception("Biti?? Saati ??ak??????yor");
            }

            $ogretmenDersProgrami = dersProgramiModel::where([
                'ogretmen_id' => $r->ogretmen_id,
                'gun_id' => $r->gun_id,
            ])->get();

            foreach ($ogretmenDersProgrami as $key) {
                $input_baslangic = DateTime::createFromFormat("H:i", $r->baslangic);
                $input_bitis = DateTime::createFromFormat("H:i", $r->bitis);
                $db_baslangic = DateTime::createFromFormat("H:i", $key->baslangic);
                $db_bitis = DateTime::createFromFormat("H:i", $key->bitis);
                if ($input_baslangic >= $db_baslangic && $input_baslangic < $db_bitis) {
                    $programliSinif = $key->sinif->ad;
                    $error_text = "Bu ????retmenin bu saatler aras??nda ba??ka bir program?? var. Program?? oldu??u s??n??f : $programliSinif";
                    throw new Exception($error_text);
                }
            }

            $dersprogrami = dersProgramiModel::create(array_merge($r->input(), [
                'kurum_id' => get_current_kurum()->id,
                'sinif_id' => $sinif->id
            ]));
            $logUser = auth()->user();
            $logText = "Kurum Yetkilisi $logUser->ad $logUser->soyad ($logUser->ozel_id), '$sinif->ad ($sinif->id)' s??n??f??nda ders program?? olu??turdu. Ders Program?? ID: $dersprogrami->id";
            LogModel::create(['kategori_id' => 24, 'logText' => $logText]);
            $kurumLogText = "$logUser->ad $logUser->soyad ($logUser->ozel_id), '$sinif->ad ($sinif->id)' s??n??f??nda ders program?? olu??turdu. Ders Program?? ID: $dersprogrami->id";
            kurumLogModel::create(['kategori_id' => 19, 'logText' => $kurumLogText, 'kurum_id' => get_current_kurum()->id]);

            return response()->json(['message' => "Ders Program?? Olu??turuldu"]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
    public function yoklamaAl(Request $r)
    {
        try {
            $rules = array(
                'ders_programi_id' => array('required'),
                'ogrenci_id' => array('required'),
                'durum' => array('required'),
                'tarih' => array('required'),
                'first_day_of_week' => array('required'),
                'last_day_of_week' => array('required'),
            );
            $messages = array(
                'required' => 'Baz?? bilgiler al??namad?? l??tfen sayfay?? yenileyin',
            );
            $validator = Validator::make($r->all(), $rules, $messages);
            if ($validator->fails())
                throw new Exception($validator->errors()->first());
            $dersProgrami = dersProgramiModel::where('id', $r->ders_programi_id)
                ->with('sinif')
                ->with('ders')
                ->first();
            if (!$dersProgrami)
                throw new Exception("Ders Program?? Bulunamad??");
            if ($dersProgrami->kurum_id != get_current_kurum()->id)
                throw new Exception("Kurum ile ders program?? uyu??muyor");
            $ogrenci = User::find($r->ogrenci_id);
            if (!$ogrenci)
                throw new Exception("????renci Bulunamad??");
            if (!ogrenciSinifModel::where('ogrenci_id', $ogrenci->id)->where('sinif_id', $dersProgrami->sinif_id)->first())
                throw new Exception("????renci kurumunuza ait g??r??nm??yor");
            $yoklamaExist = yoklamaModel::where([
                'ders_programi_id' => $dersProgrami->id,
                'ogrenci_id' => $ogrenci->id,
            ])->first();

            $tarihNumber = $r->tarih - 1;
            $tarihString = "+" . $tarihNumber . " day";
            $first_day_of_week = strtotime($r->first_day_of_week);
            $first_day_of_week = date("Y-m-d", $first_day_of_week);
            $tarih = new DateTime($first_day_of_week);
            $tarih->modify($tarihString);
            $tarih = $tarih->format("Y-m-d");

            if ($yoklamaExist) {
                if ($r->durum == -1) {
                    $yoklamaExist->delete();


                    $sinif_ad_log = $dersProgrami->sinif->ad;
                    $ders_ad_log = $dersProgrami->ders->ad;
                    $ogrenci_ad_soyad_log = $ogrenci->ad . " " . $ogrenci->soyad;
                    $logUser = auth()->user();
                    $logText = "Kurum Yetkilisi $logUser->ad $logUser->soyad ($logUser->ozel_id), yoklama g??ncelledi. S??n??f : $sinif_ad_log | Ders : $ders_ad_log | ????renci : $ogrenci_ad_soyad_log | Durum : Bilinmiyor";
                    LogModel::create(['kategori_id' => 25, 'logText' => $logText]);

                    $kurumLogText = "$logUser->ad $logUser->soyad ($logUser->ozel_id), yoklama g??ncelledi. S??n??f : $sinif_ad_log | Ders : $ders_ad_log | ????renci : $ogrenci_ad_soyad_log | Durum : Bilinmiyor";
                    kurumLogModel::create(['kategori_id' => 20, 'logText' => $kurumLogText, 'kurum_id' => get_current_kurum()->id]);
                } else if ($r->durum == 0) {
                    $yoklamaExist->geldi = false;
                    $yoklamaExist->save();

                    $sinif_ad_log = $dersProgrami->sinif->ad;
                    $ders_ad_log = $dersProgrami->ders->ad;
                    $ogrenci_ad_soyad_log = $ogrenci->ad . " " . $ogrenci->soyad;
                    $logUser = auth()->user();
                    $logText = "Kurum Yetkilisi $logUser->ad $logUser->soyad ($logUser->ozel_id), yoklama g??ncelledi. S??n??f : $sinif_ad_log | Ders : $ders_ad_log | ????renci : $ogrenci_ad_soyad_log | Durum : Gelmedi";
                    LogModel::create(['kategori_id' => 25, 'logText' => $logText]);

                    $kurumLogText = "$logUser->ad $logUser->soyad ($logUser->ozel_id), yoklama g??ncelledi. S??n??f : $sinif_ad_log | Ders : $ders_ad_log | ????renci : $ogrenci_ad_soyad_log | Durum : Gelmedi";
                    kurumLogModel::create(['kategori_id' => 20, 'logText' => $kurumLogText, 'kurum_id' => get_current_kurum()->id]);
                } else {
                    $yoklamaExist->geldi = true;
                    $yoklamaExist->save();

                    $sinif_ad_log = $dersProgrami->sinif->ad;
                    $ders_ad_log = $dersProgrami->ders->ad;
                    $ogrenci_ad_soyad_log = $ogrenci->ad . " " . $ogrenci->soyad;
                    $logUser = auth()->user();
                    $logText = "Kurum Yetkilisi $logUser->ad $logUser->soyad ($logUser->ozel_id), yoklama g??ncelledi. S??n??f : $sinif_ad_log | Ders : $ders_ad_log | ????renci : $ogrenci_ad_soyad_log | Durum : Geldi";
                    LogModel::create(['kategori_id' => 25, 'logText' => $logText]);

                    $kurumLogText = "$logUser->ad $logUser->soyad ($logUser->ozel_id), yoklama g??ncelledi. S??n??f : $sinif_ad_log | Ders : $ders_ad_log | ????renci : $ogrenci_ad_soyad_log | Durum : Geldi";
                    kurumLogModel::create(['kategori_id' => 20, 'logText' => $kurumLogText, 'kurum_id' => get_current_kurum()->id]);
                }
            } else {
                if ($r->durum == 0) {
                    yoklamaModel::create([
                        'ders_programi_id' => $dersProgrami->id,
                        'ogrenci_id' => $ogrenci->id,
                        'geldi' => false,
                        'tarih' => "$tarih"
                    ]);

                    $sinif_ad_log = $dersProgrami->sinif->ad;
                    $ders_ad_log = $dersProgrami->ders->ad;
                    $ogrenci_ad_soyad_log = $ogrenci->ad . " " . $ogrenci->soyad;
                    $logUser = auth()->user();
                    $logText = "Kurum Yetkilisi $logUser->ad $logUser->soyad ($logUser->ozel_id), yoklama ald??. S??n??f : $sinif_ad_log | Ders : $ders_ad_log | ????renci : $ogrenci_ad_soyad_log | Durum : Gelmedi";
                    LogModel::create(['kategori_id' => 25, 'logText' => $logText]);

                    $kurumLogText = "$logUser->ad $logUser->soyad ($logUser->ozel_id), yoklama ald??. S??n??f : $sinif_ad_log | Ders : $ders_ad_log | ????renci : $ogrenci_ad_soyad_log | Durum : Gelmedi";
                    kurumLogModel::create(['kategori_id' => 20, 'logText' => $kurumLogText, 'kurum_id' => get_current_kurum()->id]);
                } else if ($r->durum == 1) {
                    yoklamaModel::create([
                        'ders_programi_id' => $dersProgrami->id,
                        'ogrenci_id' => $ogrenci->id,
                        'geldi' => true,
                        'tarih' => "$tarih"
                    ]);

                    $sinif_ad_log = $dersProgrami->sinif->ad;
                    $ders_ad_log = $dersProgrami->ders->ad;
                    $ogrenci_ad_soyad_log = $ogrenci->ad . " " . $ogrenci->soyad;
                    $logUser = auth()->user();
                    $logText = "Kurum Yetkilisi $logUser->ad $logUser->soyad ($logUser->ozel_id), yoklama ald??. S??n??f : $sinif_ad_log | Ders : $ders_ad_log | ????renci : $ogrenci_ad_soyad_log | Durum : Geldi";
                    LogModel::create(['kategori_id' => 25, 'logText' => $logText]);

                    $kurumLogText = "$logUser->ad $logUser->soyad ($logUser->ozel_id), yoklama ald??. S??n??f : $sinif_ad_log | Ders : $ders_ad_log | ????renci : $ogrenci_ad_soyad_log | Durum : Geldi";
                    kurumLogModel::create(['kategori_id' => 20, 'logText' => $kurumLogText, 'kurum_id' => get_current_kurum()->id]);
                }
            }
            return response()->json(['message' => "Yoklama Al??nd??"]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
    public function getDersOgretmenleri(Request $r)
    {
        try {
            if (!$r->dersid)
                throw new Exception("Ders Bilgisi Al??namad??");
            $dersExist = kurumDersModel::find($r->dersid);
            if (!$dersExist)
                throw new Exception("Ders Bilgisi Al??namad??");
            if ($dersExist->kurum_id != get_current_kurum()->id)
                throw new Exception("Ders Bilgisi Al??namad??");
            $ogretmenDers = ogretmenDersModel::where('ders_id', $dersExist->id)->with('ogretmen')->get();
            return response()->json(['data' => $ogretmenDers]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
