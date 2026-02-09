<?php
declare(strict_types=1);

// /www/apis/app/bootstrap.php
$APP_DIR = __DIR__;
// ---- Load .env (dev local) -------------------------------------------------
$envFile = dirname($APP_DIR) . '/.env'; // racine du projet (../.env)
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;

        // support "KEY=VALUE"
        $pos = strpos($line, '=');
        if ($pos === false) continue;

        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));

        // enlève quotes simples/doubles si présentes
        if (
            (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))
        ) {
            $val = substr($val, 1, -1);
        }

        putenv("$key=$val");
        $_ENV[$key] = $val;
    }
}
// ----------------------------------------------------------------------------

// Sécurité : si besoin dans routes.php
$GLOBALS['APP_DIR'] = $APP_DIR;

// Core
require_once $APP_DIR . '/Core/Constantes.php';
require_once $APP_DIR . '/Core/Debug.php';
require_once $APP_DIR . '/Core/Http.php';
require_once $APP_DIR . '/Core/Db.php';
require_once $APP_DIR . '/Core/DbTable.php';
require_once $APP_DIR . '/Core/clFichier.php';
require_once $APP_DIR . '/Core/Mailer.php';

// Digital
require_once $APP_DIR . '/Digital/APAVTPRD.php';
require_once $APP_DIR . '/Digital/ATAVTTXT.php';
require_once $APP_DIR . '/Digital/EVENSVAL.php';
require_once $APP_DIR . '/Digital/NANOMART.php';
require_once $APP_DIR . '/Digital/LVLIBVCART.php';
require_once $APP_DIR . '/Digital/MAMATART.php';
require_once $APP_DIR . '/Digital/NCNOMCAT.php';
require_once $APP_DIR . '/Digital/NDNOMDRO.php';
require_once $APP_DIR . '/Digital/NFNOMFIC.php';
require_once $APP_DIR . '/Digital/NNNIVNOMCA.php';
require_once $APP_DIR . '/Digital/STSERTET.php';
require_once $APP_DIR . '/Digital/TAFAM.php';
require_once $APP_DIR . '/Digital/TATABATT.php';
require_once $APP_DIR . '/Digital/TATXTATT.php';
require_once $APP_DIR . '/Digital/TFTABFIC.php';
require_once $APP_DIR . '/Digital/TIVARLOG.php';
require_once $APP_DIR . '/Digital/TPTITPRT.php';
require_once $APP_DIR . '/Digital/VUE_TATABATT.php';
// Domain
require_once $APP_DIR . '/Domain/Company.php';
require_once $APP_DIR . '/Digital/Digital.php';
// A*
require_once $APP_DIR . '/Domain/A1ARTICL.php';
require_once $APP_DIR . '/Domain/A3GESPVP.php';
require_once $APP_DIR . '/Domain/ACARTCAT.php';
require_once $APP_DIR . '/Domain/ADARTDEP.php';
require_once $APP_DIR . '/Domain/ARTNOWEB.php';
require_once $APP_DIR . '/Domain/ASARTSOC.php';
// B*
require_once $APP_DIR . '/Domain/B6DEVISE.php';
require_once $APP_DIR . '/Domain/B8ACTFOU.php';
// C*
require_once $APP_DIR . '/Domain/C0LIBART.php';
require_once $APP_DIR . '/Domain/C2LANGUE.php';
require_once $APP_DIR . '/Domain/C3LIBTAR.php';
require_once $APP_DIR . '/Domain/C7REGLEM.php';
require_once $APP_DIR . '/Domain/CATALOGUE.php';
// D*
require_once $APP_DIR . '/Domain/D4NOMENC.php';
require_once $APP_DIR . '/Domain/D6CONTRN.php';
require_once $APP_DIR . '/Domain/D7RFARFO.php';
require_once $APP_DIR . '/Domain/DFDEPFOUR.php';
// E 
require_once $APP_DIR . '/Domain/EAECOART.php';
// F 
require_once $APP_DIR . '/Domain/FIFOUINT.php';
// G
require_once $APP_DIR . '/Domain/G0ISO.php';
// H
require_once $APP_DIR . '/Domain/H6TRANSP.php';
require_once $APP_DIR . '/Domain/H7ZONECO.php';
// I 
require_once $APP_DIR . '/Domain/IAFAPPFOUR.php';
// J 
// K 
require_once $APP_DIR . '/Domain/K1ARTCP.php';
// L
// M 
// N
// O 
// P 
require_once $APP_DIR . '/Domain/PXROUGE.php';
require_once $APP_DIR . '/Domain/PXNROUGE.php';
// Q 
// R 
require_once $APP_DIR . '/Domain/R5GESPRA.php';
// S 
// T
require_once $APP_DIR . '/Domain/TTTXT.php';
// U
require_once $APP_DIR . '/Domain/U3FOURN.php';
// V
// W
require_once $APP_DIR . '/Domain/W1REFBAN.php';

// Enums
require_once $APP_DIR . '/Enums/ContactType.php';
require_once $APP_DIR . '/Enums/CoordonneesType.php';

// Utils
require_once $APP_DIR . '/Utils/Sql.php';

// Modules
require_once $APP_DIR . '/Help/Help.php';
require_once $APP_DIR . '/Customers/Customers.php';
require_once $APP_DIR . '/Products/Products.php';
require_once $APP_DIR . '/Prices/Prices.php';
require_once $APP_DIR . '/Phone/Phone.php';
require_once $APP_DIR . '/Suppliers/Suppliers.php';

