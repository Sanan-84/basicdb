BasicDB - PHP Verilənlər Bazası Sinfi
Versiya: 1.3.0

HAQQINDA
BasicDB, MySQL verilənlər bazaları ilə qarşılıqlı əlaqəni asanlaşdıran, PHP-də yazılmış sadə və təhlükəsiz bir PDO sinfidir. Bu sinif SQL Injection-a qarşı tam qorunma (prepared statements) və rahat metod zəncirləməsi (method chaining) imkanları təqdim edir.

ƏSAS ÖZƏLLİKLƏR
- Təhlükəsizlik: Bütün sorğular "Prepared Statements" ilə icra olunur.
- Rahatlıq: insert(), update(), delete() və select() sorğularını zəncirvari şəkildə yazmaq mümkündür.
- Səhifələmə: Daxili pagination dəstəyi.
- Azərbaycan dili: az_like() metodu ilə Azərbaycan hərflərinə uyğun təkmilləşdirilmiş axtarış.
- Uyğunluq: Standart SQL operatorları (AND/OR) sayəsində müxtəlif bazalarla işləmə imkanı.

QURAŞDIRMA
1. Layihəni klonlayın: git clone https://github.com/Sanan-84/basicdb.git
2. Kitabxanaları yükləyin: composer install

İSTİFADƏ QAYDASI
require_once 'vendor/autoload.php';
use Webservis\Database;

$db = new Database('localhost', 'database_adı', 'istifadəçi_adı', 'şifrə');

// Məlumatın oxunması
$users = $db->from('users')
            ->where('status', 1)
            ->all();

// Yeni məlumat əlavə edilməsi
$db->insert('users')
   ->set([
       'username' => 'test_user',
       'email' => 'test@example.com'
   ])
   ->done();

// Məlumatın yenilənməsi
$db->update('users')
   ->set('status', 0)
   ->where('id', 1)
   ->done();

// Say artımı (increment)
$db->update('users')
   ->incrementDecrement('points', '+1')
   ->where('id', 1)
   ->done();

MÜƏLLİF
Sənan Məmmədov (sanan@webservis.az)
Vebsayt: http://www.webservis.az
