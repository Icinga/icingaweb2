<?php

namespace Icinga\File;

use TCPDF;
use Icinga\Web\Url;
use Icinga\Application\Icinga;

// $_SERVER['DOCUMENT_ROOT'] = '/';
$_SERVER['DOCUMENT_ROOT'] = Icinga::app()->getApplicationDir() . '/../public';
define('K_TCPDF_EXTERNAL_CONFIG', true);

//define('K_PATH_URL', 'http://net-test-icinga-vm1.adm.netways.de/develop/'); // ???
// define('K_PATH_URL', '/var/www/net-test-icinga-vm1.adm.netways.de/develop/public'); // ???
define('K_PATH_URL', (string) Url::fromPath('/') === '/' ? '' : (string) Url::fromPath('/')); // ???'/'));
define('K_PATH_MAIN', dirname(ICINGA_LIBDIR) . '/public');
define('K_PATH_FONTS', ICINGA_LIBDIR . '/vendor/tcpdf/fonts/');
define('K_PATH_CACHE', ICINGA_LIBDIR . '/vendor/tcpdf/cache/');
define('K_PATH_URL_CACHE', ICINGA_LIBDIR . '/vendor/tcpdf/cache/');
//define('K_PATH_IMAGES', K_PATH_MAIN . 'images/'); // ???
define('K_PATH_IMAGES', dirname(ICINGA_LIBDIR) . '/public'); // ???
define('K_BLANK_IMAGE', K_PATH_IMAGES.'_blank.png');  // COULD be anything?

// define('K_CELL_HEIGHT_RATIO', 1.25);
define('K_SMALL_RATIO', 2/3);
define('K_TCPDF_CALLS_IN_HTML', false); // SECURITY: is false better?
define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
define('K_THAI_TOPCHARS', false);

require_once 'vendor/tcpdf/tcpdf.php';

class Pdf extends TCPDF
{
    protected $cell_height_ratio = 1.25;
    public function __construct(
        $orientation = 'P',
        $unit = 'mm',
        $format = 'A4',
        $unicode = true,
        $encoding = 'UTF-8',
        $diskcache = false,
        $pdfa = false
    ) {
        parent::__construct(
            $orientation,
            $unit,
            $format,
            $unicode,
            $encoding,
            $diskcache,
            $pdfa
        );

        $this->SetCreator('IcingaWeb');
        $this->SetAuthor('IcingaWeb Team');
        $this->SetTitle('IcingaWeb Sample PDF - Title');
        $this->SetSubject('IcingaWeb Sample PDF - Subject');
        $this->SetKeywords('IcingaWeb, Monitoring');

        // set default header data
        // $pdf->SetHeaderData('tcpdf_logo.jpg', 30, 'Header title',
        // 'Header string', array(0,64,255), array(0,64,128));
        // $pdf->setFooterData($tc=array(0,64,0), $lc=array(0,64,128));

        $this->setHeaderFont(array('helvetica', '', 10));
        $this->setFooterFont(array('helvetica', '', 8));
        $this->SetDefaultMonospacedFont('courier');

        $this->SetMargins(15, 27, 15); // left, top, right
        $this->SetHeaderMargin(5);
        $this->SetFooterMargin(10);

        $this->SetAutoPageBreak(true, 25); // margin bottom
        $this->setImageScale(1.75);

        $lang = array(
            'a_meta_charset'  => 'UTF-8',
            'a_meta_dir'      => 'ltr',
            'a_meta_language' => 'de',
            'w_page'          => 'Seite',
        );
        $this->setLanguageArray($lang);

        $this->setFontSubsetting(true);
        $this->SetFont('dejavusans', '', 16, '', true);
    }
}
