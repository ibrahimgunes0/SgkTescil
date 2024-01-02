# SgkTescil
Sgk Personel İşten Giriş Çıkış Yapma. TC No ile personel sorgulama.

## SgkTescil ile neler yapılabilir?
### 1-) Personelin TC kimlik numarası ile ilgili personelin daha önceki işe giriş ve çıkışları sorgulanabilir.
### 2-) Personelin istenilen bilgileri ile birlikte "İşe Giriş" ve "İşten Çıkış" yapılabilir.
### 3-) Personelin refarans kodu ile birlikte daha önce yaptığı giriş ve çıkışların PDF dökümanına ulaşılabilir.

# Kullanım:
 İlk önce personel bilgilerini almanız ve sgkIslemler.php dosyasına "POST" metodu ile göndermeniz gerekmektedir. Neleri göndereceğinizi dosyanın içinde görebilirsiniz.
 ## ÖRNEK
  Örneğin İşten Çıkış yapacka iseniz:
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
  Yukarıdaki workerInformation Arrayindeki değerleri ilgili dosyaya "POST" metodu ile gönderirseniz kodunuz çalışacaktır.
# Dikkat Edilmesi Gerekenler  
### baglani.php dosyası yerine kendi bağlantı dosyanızı yazmalısınız.
### Bu bir test webservisi değildir denemek için işlem yaptığınızda SGK'nın kendi sitesinden srgulayıp işlemi iptal etmeyi unutmayınız.
### İşten Çıkış veya İşe Giriş yaparken tarihlere dikkat ediniz. Hatalı tarih girildiği taktirde cezai işlem uygulanabilir.

        
