<? session_start();
include_once($_SESSION['se_path']."baglanti.php");

class SgkTescil{

    //Dinamik olacak şekilde değiştirilecek
    public $serviceUrl = "https://uyg.sgk.gov.tr/WS_SgkTescil4a/WS_SgkIseGirisService?wsdl";
    public $kullaniciAdi = "";
    public $isyeriKodu = "";
    public $sistemSifre = "";
    public $isyeriSifre = "";
    public $isyeriSicil = "";
    public $tcNo;

    public function __construct($tcNo){
        $sirketInfo = db::query_row("SELECT si_bil_kuladi,si_sgk_mukellef_kodu,si_bil_parola1,si_bil_parola2,si_sgksicilno FROM tbsirket WHERE si_sube=".$_SESSION['se_sube']);
        $this->kullaniciAdi = $sirketInfo['si_bil_kuladi'];
        $this->isyeriKodu = $sirketInfo['si_sgk_mukellef_kodu'];
        $this->sistemSifre = $sirketInfo['si_bil_parola1'];
        $this->isyeriSifre = $sirketInfo['si_bil_parola2'];
        $this->isyeriSicil = $sirketInfo['si_sgksicilno'];
        $this->tcNo = $tcNo;
    }

    public function setServiceUrl($function) : void{
        //Giriş ve çıkış için ayrı endpointler mevcut
        if (strpos($function,'Giris') !== false){
            $this->serviceUrl = "https://uyg.sgk.gov.tr/WS_SgkTescil4a/WS_SgkIseGirisService?wsdl";
        }else{
            $this->serviceUrl = "https://uyg.sgk.gov.tr/WS_SgkTescil4a/WS_SgkIstenCikisService?wsdl";
        }
    }

    public function getUserInformation($title = 'kullaniciBilgileri'){
        //Her function'ın <kullaniciBilgileri> alanında olması gereken bilgiler.
        $userInformation = [
            $title =>[
                'kullaniciAdi' => $this->kullaniciAdi,
                'isyeriKodu' => $this->isyeriKodu,
                'sistemSifre' => $this->sistemSifre,
                'isyeriSifre' => $this->isyeriSifre,
                'isyeriSicil' => $this->isyeriSicil
            ]
        ];
        return $userInformation;
    }

    public function loginQuery(){
        $params = ['tcKimliktenIseGirisSorParametre' => $this->getUserInformation()];
        $params['tcKimliktenIseGirisSorParametre']['tcKimlikNo'] = $this->tcNo;

        return $this->sendRequest('tckimlikNoileiseGirisSorgula',$params);
    }

    public function logoutQuery(){
        $params = ['tcKimliktenIstenCikisSorParametre' => $this->getUserInformation()];
        $params['tcKimliktenIstenCikisSorParametre']['tcKimlikNo'] = $this->tcNo;

        return $this->sendRequest('tckimlikNoileistenCikisSorgula',$params);
    }

    public function jobEntryControl(){
        $response = $this->loginQuery();
        $responseGiris = $response->tckimlikNoileiseGirisSorgulaReturn->iseGirisKayitlari;


        // 0 hata yok eğer sıfır değilse giriş yapmamış demektir bu da giriş yapabilir anlamına gelmektedir
        //1'den fazla giriş varsa son giriş tarihini alıyoruz
        if ($response->tckimlikNoileiseGirisSorgulaReturn->hatakodu !== 0){
            return true;
        }else if (count($responseGiris) > 1){
            $iseGirisTarihi = $responseGiris[0]->girisTarihi;
            $iseGirisTarihi = str_replace("/",".",$iseGirisTarihi);
        }else{
            $iseGirisTarihi = $responseGiris->girisTarihi;
            $iseGirisTarihi = str_replace("/",".",$iseGirisTarihi);
        }

        $responseCikis = $this->logoutQuery()->tckimlikNoileistenCikisSorgulaReturn->istenCikisKayitlari;
        //1'den fazla çıkış varsa son çıkış tarihini alıyoruz
        if (!$responseCikis){
            return false;
        } else if (count($responseCikis) > 1){
            $istenCikisTarihi = $responseCikis[count($responseCikis)-1]->cikisTarihi;
        }else{
            $istenCikisTarihi = $responseCikis->cikisTarihi;
        }

        $iseGirisTarihi = date_format(date_create($iseGirisTarihi),'Y-m-d');
        $istenCikisTarihi = date_format(date_create($istenCikisTarihi),'Y-m-d');

        //Eğer giriş tarihi çıkıştan küçük ise bu durum en son yaptığı çıkış olduğunu gösterir böylece giriş butonu görünür
        return $iseGirisTarihi < $istenCikisTarihi;
    }

    public function jobEntry($workerInformation,$transfer = null, $transferNo = null){
        $params = ['sgk4aIseGirisParametre' => $this->getUserInformation()];

        foreach ($workerInformation as $key=>$value){
            $params['sgk4aIseGirisParametre']['sigortaliIseGirisListesi'][$key] = $value;
        }
        if ($transfer){
            $params['sgk4aIseGirisParametre']['ayniIsverenFarkliIsyeriNakil'] = $transfer;
        }
        if ($transferNo){
            $params['sgk4aIseGirisParametre']['nakilGeldigiIsyeriSicil'] = $transferNo;
        }

        return $this->sendRequest('iseGirisKaydet',$params);
    }

    public function jobEntryPdfWithReferenceCode($referenceCode){
        $params = $this->getUserInformation('kullanici');
        $params['referansKodu'] = $referenceCode;

        $response = $this->sendRequest('iseGirisPdfDokum',$params);

        if ($response->iseGirisPdfDokumReturn->hataAciklama){
            return $response->iseGirisPdfDokumReturn;
        }

        $pdf = base64_encode($response->iseGirisPdfDokumReturn->pdfByteArray);
        $pdf_decoded = base64_decode($pdf);
        $pdf = fopen ('../temp/'.$referenceCode.'.pdf','w');
        fwrite ($pdf,$pdf_decoded);
        fclose ($pdf);
        return 'temp/'.$referenceCode.'.pdf';
    }

    public function jobOutPdfWithReferenceCode($referenceCode){
        $params = $this->getUserInformation('kullanici');
        $params['referansKodu'] = $referenceCode;

        $response = $this->sendRequest('istenCikisPdfDokum',$params);
        if ($response->istenCikisPdfDokumReturn->hatakodu != 0){
            return $response->istenCikisPdfDokumReturn;
        }

        $pdf = base64_encode($response->istenCikisPdfDokumReturn->pdfByteArray);
        $pdf_decoded = base64_decode($pdf);
        $pdf = fopen ('../temp/'.$referenceCode.'.pdf','w');
        fwrite ($pdf,$pdf_decoded);
        fclose ($pdf);
        return 'temp/'.$referenceCode.'.pdf';
    }

    public function jobOut($workerInformation,$transferNo = null){
        $params = ['sgk4aIstenCikisParametre' => $this->getUserInformation()];
        foreach ($workerInformation as $key=>$value){
            $params['sgk4aIstenCikisParametre']['sigortaliIstenCikisListesi'][$key] = $value;
        }
        if ($transferNo){
            $params['sgk4aIstenCikisParametre']['nakilGidecegiIsyeriSicil'] = $transferNo;
        }

        return $this->sendRequest('istenCikisKaydet',$params);
    }

    public function sendRequest($function, $params)
    {
        $this->setServiceUrl($function);

        $client = new \SoapClient($this->serviceUrl, array(
            'trace'              => 0,
            'connection_timeout' => 600
        ));
        return $client->__soapCall($function, [$params]);
    }
}
