 <? session_start();
include_once($_SESSION['se_path']."personelyonetim/SgkTescil.php");
$sgkTescil = new SgkTescil($_POST['tckimlikNo']);
switch ($_POST['process']){
    case 'İşe Giriş Sorgula':

        //Birden fazla giriş gelme olasılığı olduğu için responsu 2 değişkene atıyoruz.
        //Aynı zamanda tek foreach döngüsünde halletmek de 2 değişkene atamam içib bir başka neden.
        $responseQuery = $sgkTescil->loginQuery();
        if ($responseQuery->tckimlikNoileiseGirisSorgulaReturn->hatakodu < 0 || $responseQuery->tckimlikNoileiseGirisSorgulaReturn->hatakodu == 1){?>
            <p>Hata Mesajı: <?=$responseQuery->tckimlikNoileiseGirisSorgulaReturn->hataAciklama?></p>
        <?php die();}
        $response = $response2 = $responseQuery->tckimlikNoileiseGirisSorgulaReturn->iseGirisKayitlari;

        if (count($response) > 1){?>
        <p style="text-align: center; color: green">1'den fazla işe giriş kaydı bulundu.</p>
        <?php }else{
            unset($response);
            $response[0] = $response2;
        }
        foreach ($response as $responseGiris){
            $responseGiris->girisTarihi = str_replace('/','.',$responseGiris->girisTarihi);
            ?>
            <table width="100%" cellpadding="5" cellspacing="1">
                <tbody>
                <tr class="tr1">
                    <th>TC. No:</th>
                    <td><?=$responseGiris->tckimlikNo?></td>
                </tr>
                <tr class="tr1">
                    <th>Sicil No:</th>
                    <td><?=$responseGiris->sicilno?></td>
                </tr>
                <tr class="tr1">
                    <th>Giriş Tarihi:</th>
                    <td><?=$responseGiris->girisTarihi?></td>
                </tr>
                <tr class="tr1">
                    <th>Sigorta Türü:</th>
                    <td><?=$responseGiris->sigortaTuru?></td>
                </tr>
                <tr class="tr1">
                    <th>İstisna Kodu:</th>
                    <td><?=$responseGiris->istisnaKodu?></td>
                </tr>
                <tr class="tr1">
                    <th>İşlem Tarihi:</th>
                    <td><?=$responseGiris->islemTarihi?></td>
                </tr>
                <tr class="tr1">
                    <th>İdari Para Cezası:</th>
                    <td><?=$responseGiris->idariParaCezasi?></td>
                </tr>
                <tr class="tr2">
                    <td colspan="2" align="center">
                        <button type="button"
                                class="buton yenile"
                                onclick="
                                        $('#per_ssksicilno').val('<?=$response->sicilno?>');
                                        $('#per_isebaslama').val('<?=$response->girisTarihi?>');
                                        pencereKapat('sgkTescil');
                                        ">Güncelle
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        }

        break;

    case 'İşe Giriş':
        //Personelin daha önce webticari giriş yapıp yapmadığını kontrol ediyoruz
//        $jobEntryControl = db::query_value("SELECT per_var1 FROM personelyonetim WHERE per_id=".$_POST['per_id']);
//        if ($jobEntryControl){
//            echo "<p style='color: red'>Personel işe giriş bildirimi daha önce yapılmış.</p>";
//            die();
//        }
        //Webservisin istediği zorunlu alanları dolduruyoruz
        $workerInformation = [
            'tckimlikNo' => $_POST['tckimlikNo'],
            'ad' => $_POST['ad'],
            'soyad' => $_POST['soyad'],
            'giristarihi' => $_POST['giristarihi'],
            'sigortaliTuru' => $_POST['sigortaliTuru'],
            'gorevkodu' => $_POST['gorevkodu'],
            'meslekkodu' => $_POST['meslekkodu'],
            'csgbiskolu' => $_POST['csgbiskolu'],
            'eskihukumlu' => $_POST['eskihukumlu'],
            'ozurlu' => $_POST['ozurlu'],
            'ogrenimkodu' => $_POST['ogrenimkodu'],
            'mezuniyetbolumu' => $_POST['mezuniyetbolumu'],
            'mezuniyetyili' => $_POST['mezuniyetyili'],
            'kismiSureliCalisiyormu' => $_POST['kismiSureliCalisiyormu'],
            'kismiSureliCalismaGunSayisi' => $_POST['kismiSureliCalismaGunSayisi']
        ];
        $res = $sgkTescil->jobEntry($workerInformation)->iseGirisKaydetReturn;
//        var_dump($res);
        $response = $res->sigortaliIseGirisSonuc;
        $islemSonucu = $response->islemSonucu ?: $res->hataKodu;

        //Referans kodunu daha sonra işe giriş bildiriminin pdf'ini alabilmek için kaydediyoruz
        $referansKodu = $response->referansKodu;
        switch ($islemSonucu){
            case 0:
            //İşlem başarılı olmuş oluyor tabloda success kaydediyoruz
            db::query("UPDATE personelyonetim SET per_var1 = 1 WHERE per_id = ".$_POST['per_id']);
            db::query("UPDATE personelyonetim SET per_var2 = '".$referansKodu."' WHERE per_id = ".$_POST['per_id']);
                ?>
            <p>Sigortalıya ait işe giriş işleminin başarılı olarak yapıldı.</p>
            <p>İşlem için üretilen referans kodu üretici tarafından saklanmalıdır.</p>
            <p>Referans Kodu:
                <span style="color: blue"><?=$referansKodu?></span>
                <button style="background-size: 14px !important;" class="simgeButon kopyala" onclick="copyTextToClipboard('<?=$referansKodu?>');bildirim('Referans Numarası Kopyalandı',1)"></button>
            </p>

            <? $pdfAdress = $sgkTescil->jobEntryPdfWithReferenceCode($referansKodu) ?>

            <a  href="<?=$pdfAdress?>" download>
                <button style="background-size: 14px !important;" class="simgeButon pdf"></button>
                <span>Pdf Kaydet&nbsp;&nbsp;</span>
            </a>
        <?php  break;
            case -1:?>
            <p style="color: red">Hata Açıklaması:</p>
            <p style="color: red"><?=$res->hataAciklamasi?></p>
            <p style="color: red"><?=$res->sigortaliIseGirisSonuc->islemAciklamasi?></p>
        <?php break;
            case -101: ?>
            <p style="color: red">Sistem hatası oluşmuştur. Kurumla iletişime geçmeniz gerekir.</p>
        <?php break;
        }
        break;

    case 'İşe Giriş Pdf':

        $response = $sgkTescil->jobEntryPdfWithReferenceCode($_POST['referenceCode']);

        //Hata Mesajı Veriyoruz
        //Hem referans kodu hem response'da varsa sgk hata döndürmüş oluyor
        if ($_POST['referenceCode'] && $response->hatakodu < 0){?>
            <p style="color: red">SGK Tarafından dönen hata mesajı:</p>
            <p style="color: red"><?=$response->hataAciklama?></p>
        <?php }else if(!$_POST['referenceCode']){ ?>
            <p style="color:red;">&nbsp;Personele ait referans kodu bulunamadı! </p>
        <?php }else{
            db::query("UPDATE personelyonetim SET per_var2 = '".$_POST['referenceCode']."' WHERE per_id = ".$_POST['per_id']);
        }

        if(!$_POST['referenceCode'] || $response->hatakodu < 0){?>
            <p>&nbsp;Devam etmek için referans kodu giriniz</p>
            <span>&nbsp;Referans Kodu</span>
            <input type="text" id="referenceCode">
            <button type="button" style="background: green" onclick="sgkTescil('İşe Giriş Pdf',{'referenceCode':$('#referenceCode').val(),'per_id':'<?=$_POST['per_id']?>'})">Devam</button>
            <br>
            <br>
        <?php }else{
            ?>
            <p style="text-align: center">Pdf Oluşturuldu</p>
            <p style="text-align: center">
                <a  href="<?=$response?>" download>
                    <button style="background-size: 14px !important;" class="simgeButon pdf"></button>
                    <span>Pdf Kaydet&nbsp;&nbsp;</span>
                </a>
            </p>

            <?php
        }
        break;

    case 'İşten Çıkış':
        $workerInformation = [
            'tckimlikNo' => $_POST['tckimlikNo'],
            'ad' => $_POST['ad'],
            'soyad' => $_POST['soyad'],
            'istenCikisTarihi' => $_POST['istenCikisTarihi'],
            'istenCikisNedeni' => $_POST['istenCikisNedeni'],
            'meslekkodu' => $_POST['meslekkodu'],
            'csgbiskolu' => $_POST['csgbiskolu'],
            'bulundugumuzDonem' => [
                'belgeturu' => $_POST['belgeturu'],
                'hakedilenucret' => $_POST['hakedilenucret'],
                'primikramiye' => $_POST['primikramiye'],
                'eksikgunsayisi' => $_POST['eksikgunsayisi'],
                'eksikgunnedeni' => $_POST['eksikgunnedeni']
            ],
            'oncekiDonem' => [
                'belgeturu' => $_POST['belgeturu2'],
                'hakedilenucret' => $_POST['hakedilenucret2'] ?: 0,
                'primikramiye' => $_POST['primikramiye2'] ?: 0,
                'eksikgunsayisi' => $_POST['eksikgunsayisi2'] ?: 0,
                'eksikgunnedeni' => $_POST['eksikgunnedeni2'] ?: 0
            ]
        ];

        function arrayDump($message):void{
            print("<pre>".print_r($message,true)."</pre>");
        }

        $response = $sgkTescil->jobOut($workerInformation,$_POST['nakilGidecegiIsyeriSicil']);
        $referansKodu = $response->istenCikisKaydetReturn->sigortaliIstenCikisSonuc->referansKodu;

        switch ($response->istenCikisKaydetReturn->sigortaliIstenCikisSonuc->islemSonucu){
            case 0:
                //İşlem başarılı olmuş oluyor tabloda success kaydediyoruz
                db::query("UPDATE personelyonetim SET per_var1 = 1 WHERE per_id = ".$_POST['per_id']);
                db::query("UPDATE personelyonetim SET per_var2 = '".$referansKodu."' WHERE per_id = ".$_POST['per_id']);
                ?>
                <p>Sigortalıya ait işten ayrilis işlemi başarılı olarak yapıldı.</p>
                <p>Referans Kodu:
                    <span style="color: blue"><?=$referansKodu?></span>
                    <button style="background-size: 14px !important;" class="simgeButon kopyala" onclick="copyTextToClipboard('<?=$referansKodu?>');bildirim('Referans Numarası Kopyalandı',1)"></button>
                </p>

                <? $pdfAdress = $sgkTescil->jobOutPdfWithReferenceCode($referansKodu) ?>

                <a  href="<?=$pdfAdress?>" download>
                    <button style="background-size: 14px !important;" class="simgeButon pdf"></button>
                    <span>Pdf Kaydet&nbsp;&nbsp;</span>
                </a>
                <?php  break;
            case -1:
                ?>
                <p style="color: red">Hata Açıklaması:</p>
                <p style="color: red"><?=$response->istenCikisKaydetReturn->sigortaliIstenCikisSonuc->islemAciklamasi?></p>
                <?php break;
            case -101: ?>
                <p style="color: red">Sistem hatası oluşmuştur. Kurumla iletişime geçmeniz gerekir.</p>
                <?php break;
        }
        break;

    case 'İşten Çıkış Pdf':

        $response = $sgkTescil->jobOutPdfWithReferenceCode($_POST['referenceCode']);
        //Hata Mesajı Veriyoruz
        //Hem referans kodu hem response'da varsa sgk hata döndürmüş oluyor
        if ($_POST['referenceCode'] && $response->hatakodu < 0){?>
            <p style="color: red">SGK Tarafından dönen hata mesajı:</p>
            <p style="color: red"><?=$response->hataAciklama?></p>
        <?php }else if(!$_POST['referenceCode']){ ?>
            <p style="color:red;">&nbsp;Personele ait referans kodu bulunamadı! </p>
        <?php }else{
            db::query("UPDATE personelyonetim SET per_var2 = '".$_POST['referenceCode']."' WHERE per_id = ".$_POST['per_id']);
        }

        if(!$_POST['referenceCode'] || $response->hatakodu < 0){?>
            <p>&nbsp;Devam etmek için referans kodu giriniz</p>
            <span>&nbsp;Referans Kodu</span>
            <input type="text" id="referenceCode">
            <button type="button" style="background: green" onclick="sgkTescil('İşten Çıkış Pdf',{'referenceCode':$('#referenceCode').val(),'per_id':'<?=$_POST['per_id']?>'})">Devam</button>
            <br>
            <br>
        <?php }else{
            ?>
            <p style="text-align: center">Pdf Oluşturuldu</p>
            <p style="text-align: center">
                <a  href="<?=$response?>" download>
                    <button style="background-size: 14px !important;" class="simgeButon pdf"></button>
                    <span>Pdf Kaydet&nbsp;&nbsp;</span>
                </a>
            </p>

            <?php
        }
        break;
}?>
<div style="text-align: center">
    <button class="buton duzenle" onclick="pencereKapat('sgkTescil');PersonelYonetimAyrinti('<?=$_POST['per_id']?>')">Tamam</button>
</div>

