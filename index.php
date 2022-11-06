<?php
/**
 * H3K | Tiny File Manager V2.4.8
 * CCP Programmers | ccpprogrammers@gmail.com
 * https://tinyfilemanager.github.io
 */

/**
 *Alexa Générateur de liste MP3  Version 0.1.9
 *https://github.com/christophe94700/AlexaPlayList
 *Christophe Caron
 *https://domotronic.fr
 *christophe@caron.tv
 */
//TFM version
define('VERSION', '0.1.9');

//Application Title
define('APP_TITLE', 'Alexa List MP3');

// Configuration dans config.php
// if User has the customized config file, try to use it to override the default config above
$config_file = __DIR__.'/config.php';
if (is_readable($config_file)) {
    @include($config_file);
}

// --- EDIT BELOW CAREFULLY OR DO NOT EDIT AT ALL ---

// max upload file size
define('MAX_UPLOAD_SIZE', $max_upload_size_bytes);

// private key and session name to store to the session
if ( !defined( 'FM_SESSION_ID')) {
    define('FM_SESSION_ID', 'filemanager');
}

// Configuration
$cfg = new FM_Config();

// Default language
$lang = isset($cfg->data['lang']) ? $cfg->data['lang'] : 'en';

// Show or hide files and folders that starts with a dot
$show_hidden_files = isset($cfg->data['show_hidden']) ? $cfg->data['show_hidden'] : true;

// PHP error reporting - false = Turns off Errors, true = Turns on Errors
$report_errors = isset($cfg->data['error_reporting']) ? $cfg->data['error_reporting'] : true;

// Hide Permissions and Owner cols in file-listing
$hide_Cols = isset($cfg->data['hide_Cols']) ? $cfg->data['hide_Cols'] : true;

// Show directory size: true or speedup output: false
$calc_folder = isset($cfg->data['calc_folder']) ? $cfg->data['calc_folder'] : true;

// Theme
$theme = isset($cfg->data['theme']) ? $cfg->data['theme'] : 'light';

define('FM_THEME', $theme);

//available languages
$lang_list = array(
    'en' => 'English'
);

if ($report_errors == true) {
    @ini_set('error_reporting', E_ALL);
    @ini_set('display_errors', 1);
} else {
    @ini_set('error_reporting', E_ALL);
    @ini_set('display_errors', 0);
}

// if fm included
if (defined('FM_EMBED')) {
    $use_auth = false;
    $sticky_navbar = false;
} else {
    @set_time_limit(600);

    date_default_timezone_set($default_timezone);

    ini_set('default_charset', 'UTF-8');
    if (version_compare(PHP_VERSION, '5.6.0', '<') && function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }
    if (function_exists('mb_regex_encoding')) {
        mb_regex_encoding('UTF-8');
    }

    session_cache_limiter('');
    session_name(FM_SESSION_ID );
    function session_error_handling_function($code, $msg, $file, $line) {
        // Permission denied for default session, try to create a new one
        if ($code == 2) {
            session_abort();
            session_id(session_create_id());
            @session_start();
        }
    }
    set_error_handler('session_error_handling_function');
    session_start();
    restore_error_handler();
}

if (empty($auth_users)) {
    $use_auth = false;
}

$is_https = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
    || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';

// update $root_url based on user specific directories
if (isset($_SESSION[FM_SESSION_ID]['logged']) && !empty($directories_users[$_SESSION[FM_SESSION_ID]['logged']])) {
    $wd = fm_clean_path(dirname($_SERVER['PHP_SELF']));
    $root_url =  $root_url.$wd.DIRECTORY_SEPARATOR.$directories_users[$_SESSION[FM_SESSION_ID]['logged']];
}
// clean $root_url
$root_url = fm_clean_path($root_url);

// abs path for site
defined('FM_ROOT_URL') || define('FM_ROOT_URL', ($is_https ? 'https' : 'http') . '://' . $http_host . (!empty($root_url) ? '/' . $root_url : ''));
defined('FM_SELF_URL') || define('FM_SELF_URL', ($is_https ? 'https' : 'http') . '://' . $http_host . $_SERVER['PHP_SELF']);

// logout
if (isset($_GET['logout'])) {
    unset($_SESSION[FM_SESSION_ID]['logged']);
    fm_redirect(FM_SELF_URL);
}

// Validate connection IP
if ($ip_ruleset != 'OFF') {
    function getClientIP() {
        if (array_key_exists('HTTP_CF_CONNECTING_IP', $_SERVER)) {
            return  $_SERVER["HTTP_CF_CONNECTING_IP"];
        }else if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            return  $_SERVER["HTTP_X_FORWARDED_FOR"];
        }else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            return $_SERVER['REMOTE_ADDR'];
        }else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        return '';
    }

    $clientIp = getClientIP();

    $proceed = false;

    $whitelisted = in_array($clientIp, $ip_whitelist);
    $blacklisted = in_array($clientIp, $ip_blacklist);
// ajout du mode auto
    if ($ip_ruleset == 'AUTO'){
        $proceed = true;
        foreach ($ip_whitelist as $ip){
            if (stristr($clientIp, $ip)){
                $_SESSION[FM_SESSION_ID]['logged'] = "user";
                break;
            }
        }
    } else 
    if($ip_ruleset == 'AND'){
        if($whitelisted == true && $blacklisted == false){
            $proceed = true;
        }
    } else
    if($ip_ruleset == 'OR'){
         if($whitelisted == true || $blacklisted == false){
            $proceed = true;
        }
    }

    if($proceed == false){
        trigger_error(lng('User connection denied from: ') . $clientIp, E_USER_WARNING); // Traduction connection

        if($ip_silent == false){
            fm_set_msg(lng('Access denied. IP restriction applicable'), 'error');
            fm_show_header_login();
            fm_show_message();
        }

        exit();
    }
}

// Auth
if ($use_auth) {
    if (isset($_SESSION[FM_SESSION_ID]['logged'], $auth_users[$_SESSION[FM_SESSION_ID]['logged']])) {
        // Logged
    } elseif (isset($_POST['fm_usr'], $_POST['fm_pwd'])) {
        // Logging In
        sleep(1);
        if(function_exists('password_verify')) {
            if (isset($auth_users[$_POST['fm_usr']]) && isset($_POST['fm_pwd']) && password_verify($_POST['fm_pwd'], $auth_users[$_POST['fm_usr']])) {
                $_SESSION[FM_SESSION_ID]['logged'] = $_POST['fm_usr'];
                fm_set_msg(lng('You are logged in'));
                // Automatique Add ip to acces files MP3
                $lines = file($root_path . '/.htaccess'); //Open file htaccess
                $newip = 0;
                foreach ($lines as $line_num => $line){
                    $pos = false;
                    $pos = stripos($line, $_SERVER['REMOTE_ADDR']);
                    if ($pos !== false) $newip += 1;
                }
                if ($newip == 0){
                    $adrip = "Allow from " . $_SERVER['REMOTE_ADDR'] . "\n";
                    $str = $root_path . '/.htaccess';
                    file_put_contents($str, $adrip, FILE_APPEND) or die("Can't create htaccess file. Please check permissions.");
                }
                // Automatique Add ip to acces files MP3
                fm_redirect(FM_SELF_URL . '?p=');
            } else {
                unset($_SESSION[FM_SESSION_ID]['logged']);
                fm_set_msg(lng('Login failed. Invalid username or password'), 'error');
                sleep(1); // Pause pour ralentissement atack force brut
                fm_redirect(FM_SELF_URL);
            }
        } else {
            fm_set_msg(lng('password_hash not supported, Upgrade PHP version'), 'error');;
        }
    } else {
        // Form
        unset($_SESSION[FM_SESSION_ID]['logged']);
        fm_show_header_login();
        ?>
        <section class="h-100">
            <div class="container h-100">
                <div class="row justify-content-md-center h-100">
                    <div class="card-wrapper">
                        <div class="card fat <?php echo fm_get_theme(); ?>">
                            <div class="card-body">
                                <form class="form-signin" action="" method="post" autocomplete="off">
                                    <div class="form-group">
                                       <div class="brand">
                                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 52" height="100%" width="200">
                                                <g id="g72" transform="matrix(0.09375845,0,0,0.09241896,28.141679,5.4105941)">
                                                    <path style="fill:#fdfdfd" d="m 11.76953,489.3664 c 0.29015,-0.46948 4.02921,-1.14692 8.30902,-1.50542 19.90933,-1.66771 54.14413,-11.14524 76.28146,-21.11769 60.29245,-27.16062 109.47946,-77.8252 134.93484,-138.98825 8.28633,-19.91004 15.89065,-48.00454 17.71328,-65.44256 0.3538,-3.385 1.02771,-6.39213 1.49758,-6.68253 0.47055,-0.29081 0.8543,52.27935 0.8543,117.03103 V 490.22 H 131.30099 c -66.05724,0 -119.82172,-0.38394 -119.53146,-0.8536 z m -223.7138,-61.78402 c -0.39997,-0.39996 -0.68409,-25.95762 -0.63139,-56.79479 l 0.0958,-56.06759 -31.0931,-27.33853 -31.0931,-27.33852 0.26303,-34.0528 0.26302,-34.0528 31.25,-18.01946 31.25,-18.01946 V 91.01415 26.129873 l 109.75,0.347579 c 98.78811,0.312862 110.79875,0.522246 120.25,2.09634 63.04717,10.500427 115.39203,46.941317 145.28966,101.146208 12.30607,22.31112 21.35982,50.47452 23.32029,72.54217 0.3644,4.1018 1.10633,8.02033 1.64874,8.70783 0.75087,0.95172 8.35435,1.25 31.86375,1.25 h 30.87756 v 15.5 15.5 h -31.35326 -31.35327 l -0.6184,7.25 c -0.97322,11.40974 -4.50005,26.98365 -9.32675,41.18541 -19.16281,56.38335 -63.0169,101.86154 -118.53496,122.9248 -13.66587,5.18476 -27.33883,8.87528 -41.81336,11.286 -9.45026,1.57393 -21.45116,1.78348 -120.03854,2.09607 -60.24619,0.19102 -109.86578,0.0201 -110.26574,-0.3799 z M -79.63999,351.72 v -22.5 h -22 -22 v 22.5 22.5 h 22 22 z m 58,0 v -22.5 h -22.5 -22.5 v 22.5 22.5 h 22.5 22.5 z m -58,-61.5 c 0,-20.83218 -0.26164,-26 -1.31634,-26 -4.06927,0 -13.8815,2.53028 -18.41961,4.74987 -6.98445,3.41609 -17.54053,14.44063 -20.99257,21.9242 -2.17903,4.72382 -2.76466,7.67037 -3.09575,15.57593 l -0.40834,9.75 h 22.1163 22.11631 z m 57.99245,22.25 c -0.0275,-13.64259 -4.76858,-25.85277 -13.36664,-34.42434 -7.30946,-7.28693 -13.02889,-10.59155 -21.40255,-12.36612 -11.35906,-2.40724 -10.22326,-5.13367 -10.22326,24.54046 v 26 h 22.5 22.5 l -0.008,-3.75 z m 166.01217,-13 c 7.59611,-16.20795 13.71668,-37.29598 14.28323,-49.21197 l 0.21215,-4.46197 -37.5,-21.78467 -37.5,-21.78468 -0.5,-48.75336 -0.5,-48.75335 h -31.5 -31.5 l -0.26504,29.85122 c -0.2059,23.19122 -0.54056,29.74501 -1.5,29.37517 -0.67923,-0.26183 -21.48873,-12.12565 -46.24334,-26.36405 l -45.00838,-25.88799 -39.99162,23.08711 c -21.99539,12.69791 -63.61662,36.71152 -92.49162,53.36358 -28.875,16.65206 -53.0451,30.79364 -53.71134,31.42572 -0.93066,0.88295 4.47066,6.13107 23.31041,22.64924 13.48696,11.825 24.89515,21.63353 25.35154,21.79673 0.45638,0.16321 4.5917,-1.90912 9.18959,-4.60516 C -154.1755,231.66224 -74.45717,186.22 -73.10142,186.22 c 0.9466,0 7.85017,3.53729 15.34126,7.86065 7.49109,4.32335 26.89517,15.51861 43.12017,24.87835 34.64009,19.98287 136.16648,78.64073 146.97909,84.9186 4.1135,2.38832 7.57936,4.3424 7.7019,4.3424 0.12254,0 2.06818,-3.9375 4.32363,-8.75 z M 250.36001,193.54243 c 0,-2.02267 -1.36288,-10.33062 -3.02862,-18.46212 C 231.76099,99.07157 185.69403,35.763352 117.36001,-3.534798 c -27.86981,-16.027625 -66.08422,-28.561944 -98,-32.143978 -4.43756,-0.498044 41.39703,-0.894729 112.25,-0.97149 l 119.75,-0.129735 V 80.22 c 0,64.35 -0.225,117 -0.5,117 -0.275,0 -0.5,-1.65491 -0.5,-3.67757 z" id="path54" />
                                                    <path style="fill:#f5c591" d="m 188.6187,210.97 c -0.54241,-0.6875 -1.28434,-4.60603 -1.64874,-8.70783 C 185.00949,180.19452 175.95574,152.03112 163.64967,129.72 137.24958,81.85621 92.00482,46.786947 38.73276,32.896778 15.04243,26.719756 18.05596,26.857321 -101.88999,26.477452 l -109.75,-0.347579 v 64.889097 64.8891 l -29.25,16.82594 c -16.0875,9.25427 -30.3576,17.53696 -31.71132,18.40597 -1.35373,0.86901 -2.18245,1.13002 -1.84159,0.58002 0.34085,-0.55 14.36499,-8.875 31.16475,-18.5 l 30.54502,-17.5 0.29657,-65 0.29657,-65.000001 h 109 c 99.95953,0 109.95381,0.149033 120.5,1.796879 56.46103,8.822058 104.10132,38.579331 137.2075,85.703122 17.35553,24.7041 30.71517,60.52413 33.56689,90 l 0.72561,7.5 31.5,0.52146 31.5,0.52146 -31.12756,0.22854 c -23.93307,0.17572 -31.35549,-0.0604 -32.11375,-1.02146 z m 61.34962,-12 C 248.5149,179.16 240.56413,147.95678 231.29485,125.68496 205.83954,64.522069 156.70285,13.907236 96.36001,-13.309019 68.52979,-25.8612 29.96082,-35.15182 2.36001,-35.952044 c -6.09902,-0.176828 -6.19156,-0.212903 -1.5,-0.584786 7.3655,-0.583839 25.84144,1.533622 41.3649,4.740682 47.92141,9.900293 93.54977,34.119225 129.00056,68.471793 39.51866,38.294414 65.22915,85.048125 76.07075,138.332235 2.75353,13.53301 5.03521,33.21212 3.85075,33.21212 -0.275,0 -0.80539,-4.1625 -1.17865,-9.25 z" id="path52" />
                                                    <path style="fill:#b58899" d="m -0.88999,489.48779 c 2.3375,-0.21053 6.1625,-0.21053 8.5,0 2.3375,0.21054 0.425,0.3828 -4.25,0.3828 -4.675,0 -6.5875,-0.17226 -4.25,-0.3828 z M -218.235,309.97 l -1.90499,-2.25 2.25,1.90499 c 2.11445,1.79022 2.70524,2.59501 1.90499,2.59501 -0.18976,0 -1.20226,-1.0125 -2.25,-2.25 z m 350.5741,-6.0924 c -13.67839,-7.94176 -115.98,-67.04083 -149.97909,-86.6421 -18.425,-10.62244 -38.44816,-22.17147 -44.49591,-25.66451 -6.04775,-3.49305 -10.60183,-6.35099 -10.12017,-6.35099 0.48165,0 7.00482,3.53729 14.49591,7.86065 7.49109,4.32335 26.89517,15.51861 43.12017,24.87835 34.47223,19.88604 136.11543,78.61118 146.93952,84.89543 l 7.43951,4.31924 3.85467,-8.20066 c 7.27723,-15.48203 13.47461,-36.87476 14.05415,-48.51346 l 0.21215,-4.26045 -37.5,-21.77889 -37.5,-21.77888 L 82.6005,153.93067 82.34098,105.22 H 51.3505 20.36001 v 30.11663 c 0,28.19634 -0.11158,30.08642 -1.75,29.64282 -0.9625,-0.26059 -22.02896,-12.14583 -46.81435,-26.41164 l -45.06434,-25.93783 -4.43566,2.66456 c -2.43961,1.4655 -39.76065,23.03583 -82.93565,47.93406 -43.175,24.89823 -83.12648,47.95648 -88.78107,51.24055 -9.06282,5.26351 -10.12927,6.16537 -9,7.61095 1.15031,1.47251 1.09732,1.49556 -0.51912,0.2258 -1.46982,-1.15458 -1.56158,-1.64077 -0.5,-2.64924 0.71511,-0.67933 24.92519,-14.85956 53.80019,-31.51162 28.875,-16.65206 70.49623,-40.66567 92.49162,-53.36358 l 39.99162,-23.08711 45.00838,25.88799 c 24.75461,14.2384 45.56411,26.10222 46.24334,26.36405 0.95944,0.36984 1.2941,-6.18395 1.5,-29.37517 L 19.86001,104.72 h 31.5 31.5 l 0.5,48.75335 0.5,48.75336 37.5,21.78468 37.5,21.78467 -0.21215,4.46197 c -0.56655,11.91599 -6.68712,33.00402 -14.28323,49.21197 -2.25545,4.8125 -4.20109,8.75 -4.32363,8.75 -0.12254,0 -3.5884,-1.95408 -7.7019,-4.3424 z m -358.5741,-0.9076 -1.90499,-2.25 2.25,1.90499 c 2.11445,1.79022 2.70524,2.59501 1.90499,2.59501 -0.18976,0 -1.20226,-1.0125 -2.25,-2.25 z m -8,-7 -1.90499,-2.25 2.25,1.90499 c 2.11445,1.79022 2.70524,2.59501 1.90499,2.59501 -0.18976,0 -1.20226,-1.0125 -2.25,-2.25 z m -8,-7 -1.90499,-2.25 2.25,1.90499 c 2.11445,1.79022 2.70524,2.59501 1.90499,2.59501 -0.18976,0 -1.20226,-1.0125 -2.25,-2.25 z m -8,-7 -1.90499,-2.25 2.25,1.90499 c 2.11445,1.79022 2.70524,2.59501 1.90499,2.59501 -0.18976,0 -1.20226,-1.0125 -2.25,-2.25 z m -8,-7 -1.90499,-2.25 2.25,1.90499 c 2.11445,1.79022 2.70524,2.59501 1.90499,2.59501 -0.18976,0 -1.20226,-1.0125 -2.25,-2.25 z m -8,-7 -1.90499,-2.25 2.25,1.90499 c 2.11445,1.79022 2.70524,2.59501 1.90499,2.59501 -0.18976,0 -1.20226,-1.0125 -2.25,-2.25 z m 54.10209,-4.73855 c -0.33849,-0.54768 0.0239,-0.74492 0.81258,-0.44228 0.78295,0.30045 5.19382,-1.66453 9.80194,-4.36662 32.07238,-18.80644 126.8531,-73.2008 127.53714,-73.19317 0.46269,0.005 -1.40874,1.30786 -4.15874,2.89489 -2.75,1.58703 -33.51289,19.3578 -68.36199,39.49062 -34.84909,20.13281 -63.73503,36.60511 -64.19098,36.60511 -0.45595,0 -1.10393,-0.44485 -1.43995,-0.98855 z M -273.76755,261.47 c -1.24387,-1.58606 -1.2085,-1.62143 0.37756,-0.37756 0.9625,0.75485 1.75,1.54235 1.75,1.75 0,0.82304 -0.82119,0.29331 -2.12756,-1.37244 z m 54.53255,-4.5 -1.90499,-2.25 2.25,1.90499 c 2.11445,1.79022 2.70524,2.59501 1.90499,2.59501 -0.18976,0 -1.20226,-1.0125 -2.25,-2.25 z m 469.90488,-7.25 c -0.006,-3.3 0.17648,-4.77056 0.40466,-3.26791 0.22817,1.50265 0.23276,4.20265 0.0102,6 -0.22256,1.79735 -0.40925,0.56791 -0.41486,-2.73209 z m -477.90488,0.25 -1.90499,-2.25 2.25,1.90499 c 2.11445,1.79022 2.70524,2.59501 1.90499,2.59501 -0.18976,0 -1.20226,-1.0125 -2.25,-2.25 z m -8,-7 -1.90499,-2.25 2.25,1.90499 c 2.11445,1.79022 2.70524,2.59501 1.90499,2.59501 -0.18976,0 -1.20226,-1.0125 -2.25,-2.25 z m -8,-7 -1.90499,-2.25 2.25,1.90499 c 2.11445,1.79022 2.70524,2.59501 1.90499,2.59501 -0.18976,0 -1.20226,-1.0125 -2.25,-2.25 z m -9,-8 -1.90499,-2.25 2.25,1.90499 c 2.11445,1.79022 2.70524,2.59501 1.90499,2.59501 -0.18976,0 -1.20226,-1.0125 -2.25,-2.25 z" id="path50" />
                                                    <path style="fill:#fd9d28" d="m 188.91049,209.97 c -0.21271,-0.6875 -0.66858,-4.4 -1.01305,-8.25 -1.93953,-21.67783 -11.04349,-49.87343 -23.24777,-72 C 141.74886,88.20046 104.23678,55.577218 60.17337,38.859788 45.9009,33.444883 32.38995,29.865312 17.36001,27.516878 6.81382,25.869032 -3.18046,25.719999 -103.13999,25.719999 h -109 l -0.29218,65.000001 -0.29219,65 -26.17516,15 c -14.39635,8.25 -28.32736,16.2466 -30.95782,17.77023 l -4.78265,2.77023 V 77.19271 -36.875039 l 142.25,0.387063 c 99.59681,0.271003 144.7987,0.731978 150.75,1.537371 26.55894,3.594231 56.25262,11.832927 78,21.641586 60.34284,27.216255 109.47953,77.831088 134.93484,138.993979 9.00109,21.62741 17.08003,52.9794 18.56238,72.03504 0.34228,4.4 0.85618,9.2375 1.14201,10.75 l 0.51968,2.75 h -31.11084 c -24.01574,0 -31.19904,-0.28507 -31.49759,-1.25 z" id="path48" />
                                                    <path style="fill:#dc041c" d="m -123.63999,351.72 v -22.5 h 22 22 v 22.5 22.5 h -22 -22 z m 57,0 v -22.5 h 22.5 22.5 v 22.5 22.5 h -22.5 -22.5 z m -56.83243,-44.75 c 0.34167,-7.78192 0.90839,-10.32266 3.57073,-16.00853 6.75892,-14.43478 19.5627,-23.87317 35.5117,-26.17768 l 4.75,-0.68634 V 290.15873 316.22 h -22.11928 -22.11928 z m 56.83243,-16.75 c 0,-20.14259 0.28161,-25.99609 1.25,-25.98264 4.39917,0.0611 15.67636,3.36696 20.05831,5.87999 13.93029,7.98897 21.67108,20.76022 23.26867,38.39016 l 0.69888,7.71249 h -22.63793 -22.63793 z" id="path46" />
                                                    <path style="fill:#762852" d="M 132.29953,302.85443 C 121.47544,296.57018 19.83224,237.84504 -14.63999,217.959 c -16.225,-9.35974 -35.62908,-20.555 -43.12017,-24.87835 -7.49109,-4.32336 -14.39466,-7.86065 -15.34126,-7.86065 -1.35603,0 -81.10154,45.45797 -128.41697,73.20255 -4.60812,2.70209 -9.00423,4.67273 -9.76914,4.37921 -2.76613,-1.06147 -48.42282,-41.90995 -47.64783,-42.62992 0.43746,-0.40639 24.42037,-14.36588 53.29537,-31.02108 28.875,-16.65521 70.49623,-40.67139 92.49162,-53.3693 L -73.15675,112.69435 -28.14837,138.6 c 24.75461,14.2481 45.79588,26.11886 46.75838,26.37945 1.63842,0.4436 1.75,-1.44648 1.75,-29.64282 V 105.22 H 51.3505 82.34098 l 0.25952,48.71067 0.25951,48.71066 37.5,21.77888 37.5,21.77889 -0.21215,4.26045 c -0.57954,11.6387 -6.77692,33.03143 -14.05415,48.51346 l -3.85467,8.20066 z" id="path44" />
                                                    <path style="fill:#1b8847" d="m 140.36001,440.72 c 0.68469,-0.825 1.46989,-1.5 1.74489,-1.5 0.275,0 -0.0602,0.675 -0.74489,1.5 -0.68469,0.825 -1.46989,1.5 -1.74489,1.5 -0.275,0 0.0602,-0.675 0.74489,-1.5 z m 4,-3 c 0.68469,-0.825 1.46989,-1.5 1.74489,-1.5 0.275,0 -0.0602,0.675 -0.74489,1.5 -0.68469,0.825 -1.46989,1.5 -1.74489,1.5 -0.275,0 0.0602,-0.675 0.74489,-1.5 z m 4,-3 c 0.68469,-0.825 1.46989,-1.5 1.74489,-1.5 0.275,0 -0.0602,0.675 -0.74489,1.5 -0.68469,0.825 -1.46989,1.5 -1.74489,1.5 -0.275,0 0.0602,-0.675 0.74489,-1.5 z m 4.5,-3.5 c 0.99549,-1.1 2.03498,-2 2.30998,-2 0.275,0 -0.31449,0.9 -1.30998,2 -0.99549,1.1 -2.03498,2 -2.30998,2 -0.275,0 0.31449,-0.9 1.30998,-2 z m 5.5,-4.5 c 1.29175,-1.375 2.57363,-2.5 2.84863,-2.5 0.275,0 -0.55688,1.125 -1.84863,2.5 -1.29175,1.375 -2.57363,2.5 -2.84863,2.5 -0.275,0 0.55688,-1.125 1.84863,-2.5 z m 14.5,-13.5 c 6.03124,-6.05 11.19089,-11 11.46589,-11 0.275,0 -4.43465,4.95 -10.46589,11 -6.03124,6.05 -11.19089,11 -11.46589,11 -0.275,0 4.43465,-4.95 10.46589,-11 z m 11.5,-12.12582 c 0,-0.275 1.35,-1.79338 3,-3.37418 1.65,-1.5808 3,-2.64918 3,-2.37418 0,0.275 -1.35,1.79338 -3,3.37418 -1.65,1.5808 -3,2.64918 -3,2.37418 z m 7.5,-8.87418 c 0.99549,-1.1 2.03498,-2 2.30998,-2 0.275,0 -0.31449,0.9 -1.30998,2 -0.99549,1.1 -2.03498,2 -2.30998,2 -0.275,0 0.31449,-0.9 1.30998,-2 z m -85.5,-2.5 c 0.68469,-0.825 1.46989,-1.5 1.74489,-1.5 0.275,0 -0.0602,0.675 -0.74489,1.5 -0.68469,0.825 -1.46989,1.5 -1.74489,1.5 -0.275,0 0.0602,-0.675 0.74489,-1.5 z m 89.5,-2.5 c 0.99549,-1.1 2.03498,-2 2.30998,-2 0.275,0 -0.31449,0.9 -1.30998,2 -0.99549,1.1 -2.03498,2 -2.30998,2 -0.275,0 0.31449,-0.9 1.30998,-2 z m -85,-1 c 0.99549,-1.1 2.03498,-2 2.30998,-2 0.275,0 -0.31449,0.9 -1.30998,2 -0.99549,1.1 -2.03498,2 -2.30998,2 -0.275,0 0.31449,-0.9 1.30998,-2 z m 5,-4 c 0.99549,-1.1 2.03498,-2 2.30998,-2 0.275,0 -0.31449,0.9 -1.30998,2 -0.99549,1.1 -2.03498,2 -2.30998,2 -0.275,0 0.31449,-0.9 1.30998,-2 z m 83.5,0.5 c 0.68469,-0.825 1.46989,-1.5 1.74489,-1.5 0.275,0 -0.0602,0.675 -0.74489,1.5 -0.68469,0.825 -1.46989,1.5 -1.74489,1.5 -0.275,0 0.0602,-0.675 0.74489,-1.5 z m -80,-2.5941 c 0,-0.275 1.8,-2.25766 4,-4.4059 2.2,-2.14824 4,-3.6809 4,-3.4059 0,0.275 -1.8,2.25766 -4,4.4059 -2.2,2.14824 -4,3.6809 -4,3.4059 z m 85,-4.4059 c 0.68469,-0.825 1.46989,-1.5 1.74489,-1.5 0.275,0 -0.0602,0.675 -0.74489,1.5 -0.68469,0.825 -1.46989,1.5 -1.74489,1.5 -0.275,0 0.0602,-0.675 0.74489,-1.5 z m -76,-3.5941 c 0,-0.275 1.8,-2.25766 4,-4.4059 2.2,-2.14824 4,-3.6809 4,-3.4059 0,0.275 -1.8,2.25766 -4,4.4059 -2.2,2.14824 -4,3.6809 -4,3.4059 z m 10,-11.4059 c 1.29175,-1.375 2.57363,-2.5 2.84863,-2.5 0.275,0 -0.55688,1.125 -1.84863,2.5 -1.29175,1.375 -2.57363,2.5 -2.84863,2.5 -0.275,0 0.55688,-1.125 1.84863,-2.5 z m 5,-6 c 1.29175,-1.375 2.57363,-2.5 2.84863,-2.5 0.275,0 -0.55688,1.125 -1.84863,2.5 -1.29175,1.375 -2.57363,2.5 -2.84863,2.5 -0.275,0 0.55688,-1.125 1.84863,-2.5 z m 4.5,-5.5 c 0.99549,-1.1 2.03498,-2 2.30998,-2 0.275,0 -0.31449,0.9 -1.30998,2 -0.99549,1.1 -2.03498,2 -2.30998,2 -0.275,0 0.31449,-0.9 1.30998,-2 z m 5.5,-7.5 c 0.68469,-0.825 1.46989,-1.5 1.74489,-1.5 0.275,0 -0.0602,0.675 -0.74489,1.5 -0.68469,0.825 -1.46989,1.5 -1.74489,1.5 -0.275,0 0.0602,-0.675 0.74489,-1.5 z m -368.12755,-28.25 c -1.24388,-1.58606 -1.20851,-1.62143 0.37755,-0.37756 1.66575,1.30637 2.19548,2.12756 1.37245,2.12756 -0.20766,0 -0.99516,-0.7875 -1.75,-1.75 z m -5.92628,-5 -3.44617,-3.75 3.75,3.44617 c 3.49042,3.20761 4.20979,4.05383 3.44617,4.05383 -0.16711,0 -1.85461,-1.6875 -3.75,-3.75 z m -8,-7 -3.44617,-3.75 3.75,3.44617 c 3.49042,3.20761 4.20979,4.05383 3.44617,4.05383 -0.16711,0 -1.85461,-1.6875 -3.75,-3.75 z m -8,-7 -3.44617,-3.75 3.75,3.44617 c 3.49042,3.20761 4.20979,4.05383 3.44617,4.05383 -0.16711,0 -1.85461,-1.6875 -3.75,-3.75 z m -8,-7 -3.44617,-3.75 3.75,3.44617 c 3.49042,3.20761 4.20979,4.05383 3.44617,4.05383 -0.16711,0 -1.85461,-1.6875 -3.75,-3.75 z m -8,-7 -3.44617,-3.75 3.75,3.44617 c 3.49042,3.20761 4.20979,4.05383 3.44617,4.05383 -0.16711,0 -1.85461,-1.6875 -3.75,-3.75 z m -8,-7 -3.44617,-3.75 3.75,3.44617 c 3.49042,3.20761 4.20979,4.05383 3.44617,4.05383 -0.16711,0 -1.85461,-1.6875 -3.75,-3.75 z m -8,-7 -3.44617,-3.75 3.75,3.44617 c 3.49042,3.20761 4.20979,4.05383 3.44617,4.05383 -0.16711,0 -1.85461,-1.6875 -3.75,-3.75 z" id="path42" />
                                                    <path style="fill:#007a31" d="m -274.63999,376.21798 c 0,-94.87188 0.2249,-113.96261 1.33469,-113.29767 0.73408,0.43983 14.68408,12.49401 31,26.78707 l 29.66531,25.98738 v 56.30732 56.30732 l 110.75,-0.34682 c 99.75456,-0.31238 111.79246,-0.52047 121.25,-2.09597 57.50113,-9.57894 105.8386,-40.4845 137.27132,-87.76727 16.25496,-24.45157 28.70515,-58.8056 31.48582,-86.87934 l 0.74286,-7.5 31.05083,-0.26397 31.05083,-0.26397 -0.34001,3.76397 c -8.03297,88.92573 -56.41811,164.25179 -134.26165,209.01877 -28.09319,16.15608 -62.78554,27.51543 -99,32.41564 -5.94878,0.80493 -51.04538,1.26641 -150.25,1.53752 l -141.75,0.38738 z" id="path40" /></g>
                                            </svg>
                                        </div>
                                        <div class="text-center">
                                            <h1 class="card-title"><?php echo APP_TITLE; ?></h1>
                                        </div>
                                    </div>
                                    <hr />
                                    <div class="form-group">
                                        <label for="fm_usr"><?php echo lng('Username'); ?></label>
                                        <input type="text" class="form-control" id="fm_usr" name="fm_usr" required autofocus>
                                    </div>

                                    <div class="form-group">
                                        <label for="fm_pwd"><?php echo lng('Password'); ?></label>
                                        <input type="password" class="form-control" id="fm_pwd" name="fm_pwd" required>
                                    </div>

                                    <div class="form-group">
                                        <?php fm_show_message(); ?>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-success btn-block mt-4" role="button">
                                            <?php echo lng('Login'); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="footer text-center">
                            &mdash;&mdash; &copy;
                            <a href="https://domotronic.fr/" target="_blank" class="text-muted" data-version="<?php echo VERSION; ?>">Domotronic</a> &mdash;&mdash;
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php
        fm_show_footer_login();
        exit;
    }
}

// update root path
if ($use_auth && isset($_SESSION[FM_SESSION_ID]['logged'])) {
    $root_path = isset($directories_users[$_SESSION[FM_SESSION_ID]['logged']]) ? $directories_users[$_SESSION[FM_SESSION_ID]['logged']] : $root_path;
}

// clean and check $root_path
$root_path = rtrim($root_path, '\\/');
$root_path = str_replace('\\', '/', $root_path);
if (!@is_dir($root_path)) {
    echo "<h1>".lng('Root path')." \"{$root_path}\" ".lng('not found!')." </h1>";
    exit;
}

defined('FM_SHOW_HIDDEN') || define('FM_SHOW_HIDDEN', $show_hidden_files);
defined('FM_ROOT_PATH') || define('FM_ROOT_PATH', $root_path);
defined('FM_LANG') || define('FM_LANG', $lang);
defined('FM_FILE_EXTENSION') || define('FM_FILE_EXTENSION', $allowed_file_extensions);
defined('FM_UPLOAD_EXTENSION') || define('FM_UPLOAD_EXTENSION', $allowed_upload_extensions);
defined('FM_EXCLUDE_ITEMS') || define('FM_EXCLUDE_ITEMS', (version_compare(PHP_VERSION, '7.0.0', '<') ? serialize($exclude_items) : $exclude_items));
defined('FM_DOC_VIEWER') || define('FM_DOC_VIEWER', $online_viewer);
define('FM_READONLY', $use_auth && !empty($readonly_users) && isset($_SESSION[FM_SESSION_ID]['logged']) && in_array($_SESSION[FM_SESSION_ID]['logged'], $readonly_users));
define('FM_IS_WIN', DIRECTORY_SEPARATOR == '\\');

// always use ?p=
if (!isset($_GET['p']) && empty($_FILES)) {
    fm_redirect(FM_SELF_URL . '?p=');
}

// get path
$p = isset($_GET['p']) ? $_GET['p'] : (isset($_POST['p']) ? $_POST['p'] : '');

// clean path
$p = fm_clean_path($p);

// for ajax request - save
$input = file_get_contents('php://input');
$_POST = (strpos($input, 'ajax') != FALSE && strpos($input, 'save') != FALSE) ? json_decode($input, true) : $_POST;

// instead globals vars
define('FM_PATH', $p);
define('FM_USE_AUTH', $use_auth);
define('FM_EDIT_FILE', $edit_files);
defined('FM_ICONV_INPUT_ENC') || define('FM_ICONV_INPUT_ENC', $iconv_input_encoding);
defined('FM_USE_HIGHLIGHTJS') || define('FM_USE_HIGHLIGHTJS', $use_highlightjs);
defined('FM_HIGHLIGHTJS_STYLE') || define('FM_HIGHLIGHTJS_STYLE', $highlightjs_style);
defined('FM_DATETIME_FORMAT') || define('FM_DATETIME_FORMAT', $datetime_format);

unset($p, $use_auth, $iconv_input_encoding, $use_highlightjs, $highlightjs_style);

/*************************** ACTIONS ***************************/

// AJAX Request
if (isset($_POST['ajax']) && !FM_READONLY) {

    // save
    if (isset($_POST['type']) && $_POST['type'] == "save") {
        // get current path
        $path = FM_ROOT_PATH;
        if (FM_PATH != '') {
            $path .= '/' . FM_PATH;
        }
        // check path
        if (!is_dir($path)) {
            fm_redirect(FM_SELF_URL . '?p=');
        }
        $file = $_GET['edit'];
        $file = fm_clean_path($file);
        $file = str_replace('/', '', $file);
        if ($file == '' || !is_file($path . '/' . $file)) {
            fm_set_msg(lng('File not found'), 'error');
            fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
        }
        header('X-XSS-Protection:0');
        $file_path = $path . '/' . $file;

        $writedata = $_POST['content'];
        $fd = fopen($file_path, "w");
        $write_results = @fwrite($fd, $writedata);
        fclose($fd);
        if ($write_results === false){
            header("HTTP/1.1 500 Internal Server Error");
            die("Could Not Write File! - Check Permissions / Ownership");
        }
        die(true);
    }

    // backup files
    if (isset($_POST['type']) && $_POST['type'] == "backup" && !empty($_POST['file'])) {
        $fileName = $_POST['file'];
        $fullPath = FM_ROOT_PATH . '/';
        if (!empty($_POST['path'])) {
            $relativeDirPath = fm_clean_path($_POST['path']);
            $fullPath .= "{$relativeDirPath}/";
        }
        $date = date("dMy-His");
        $newFileName = "{$fileName}-{$date}.bak";
        $fullyQualifiedFileName = $fullPath . $fileName;
        try {
            if (!file_exists($fullyQualifiedFileName)) {
                throw new Exception("File {$fileName} not found");
            }
            if (copy($fullyQualifiedFileName, $fullPath . $newFileName)) {
                echo "Backup {$newFileName} created";
            } else {
                throw new Exception("Could not copy file {$fileName}");
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    // Save Config
    if (isset($_POST['type']) && $_POST['type'] == "settings") {
        global $cfg, $lang, $report_errors, $show_hidden_files, $lang_list, $hide_Cols, $calc_folder, $theme;
        $newLng = $_POST['js-language'];
        fm_get_translations([]);
        if (!array_key_exists($newLng, $lang_list)) {
            $newLng = 'en';
        }

        $erp = isset($_POST['js-error-report']) && $_POST['js-error-report'] == "true" ? true : false;
        $shf = isset($_POST['js-show-hidden']) && $_POST['js-show-hidden'] == "true" ? true : false;
        $hco = isset($_POST['js-hide-cols']) && $_POST['js-hide-cols'] == "true" ? true : false;
        $caf = isset($_POST['js-calc-folder']) && $_POST['js-calc-folder'] == "true" ? true : false;
        $te3 = $_POST['js-theme-3'];

        if ($cfg->data['lang'] != $newLng) {
            $cfg->data['lang'] = $newLng;
            $lang = $newLng;
        }
        if ($cfg->data['error_reporting'] != $erp) {
            $cfg->data['error_reporting'] = $erp;
            $report_errors = $erp;
        }
        if ($cfg->data['show_hidden'] != $shf) {
            $cfg->data['show_hidden'] = $shf;
            $show_hidden_files = $shf;
        }
        if ($cfg->data['show_hidden'] != $shf) {
            $cfg->data['show_hidden'] = $shf;
            $show_hidden_files = $shf;
        }
        if ($cfg->data['hide_Cols'] != $hco) {
            $cfg->data['hide_Cols'] = $hco;
            $hide_Cols = $hco;
        }
        if ($cfg->data['calc_folder'] != $caf) {
            $cfg->data['calc_folder'] = $caf;
            $calc_folder = $caf;
        }
        if ($cfg->data['theme'] != $te3) {
            $cfg->data['theme'] = $te3;
            $theme = $te3;
        }
        $cfg->save();
        echo true;
    }

    // new password hash
    if (isset($_POST['type']) && $_POST['type'] == "pwdhash") {
        $res = isset($_POST['inputPassword2']) && !empty($_POST['inputPassword2']) ? password_hash($_POST['inputPassword2'], PASSWORD_DEFAULT) : '';
        echo $res;
    }

    //upload using url
    if(isset($_POST['type']) && $_POST['type'] == "upload" && !empty($_REQUEST["uploadurl"])) {
        $path = FM_ROOT_PATH;
        if (FM_PATH != '') {
            $path .= '/' . FM_PATH;
        }

         function event_callback ($message) {
            global $callback;
            echo json_encode($message);
        }

        function get_file_path () {
            global $path, $fileinfo, $temp_file;
            return $path."/".basename($fileinfo->name);
        }

        $url = !empty($_REQUEST["uploadurl"]) && preg_match("|^http(s)?://.+$|", stripslashes($_REQUEST["uploadurl"])) ? stripslashes($_REQUEST["uploadurl"]) : null;

        //prevent 127.* domain and known ports
        $domain = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $knownPorts = [22, 23, 25, 3306];

        if (preg_match("/^localhost$|^127(?:\.[0-9]+){0,2}\.[0-9]+$|^(?:0*\:)*?:?0*1$/i", $domain) || in_array($port, $knownPorts)) {
            $err = array("message" => "URL is not allowed");
            event_callback(array("fail" => $err));
            exit();
        }

        $use_curl = false;
        $temp_file = tempnam(sys_get_temp_dir(), "upload-");
        $fileinfo = new stdClass();
        $fileinfo->name = trim(basename($url), ".\x00..\x20");

        $allowed = (FM_UPLOAD_EXTENSION) ? explode(',', FM_UPLOAD_EXTENSION) : false;
        $ext = strtolower(pathinfo($fileinfo->name, PATHINFO_EXTENSION));
        $isFileAllowed = ($allowed) ? in_array($ext, $allowed) : true;

        $err = false;

        if(!$isFileAllowed) {
            $err = array("message" => "File extension is not allowed");
            event_callback(array("fail" => $err));
            exit();
        }

        if (!$url) {
            $success = false;
        } else if ($use_curl) {
            @$fp = fopen($temp_file, "w");
            @$ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOPROGRESS, false );
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            @$success = curl_exec($ch);
            $curl_info = curl_getinfo($ch);
            if (!$success) {
                $err = array("message" => curl_error($ch));
            }
            @curl_close($ch);
            fclose($fp);
            $fileinfo->size = $curl_info["size_download"];
            $fileinfo->type = $curl_info["content_type"];
        } else {
            $ctx = stream_context_create();
            @$success = copy($url, $temp_file, $ctx);
            if (!$success) {
                $err = error_get_last();
            }
        }

        if ($success) {
            $success = rename($temp_file, get_file_path());
        }

        if ($success) {
            event_callback(array("done" => $fileinfo));
        } else {
            unlink($temp_file);
            if (!$err) {
                $err = array("message" => "Invalid url parameter");
            }
            event_callback(array("fail" => $err));
        }
    }

    exit();
}

if (isset($_POST['ajax'])) {
    //search : get list of files from the current folder
    if(isset($_POST['type']) && $_POST['type']=="search") {
        $dir = FM_ROOT_PATH;
        $response = scan(fm_clean_path($_POST['path']), $_POST['content']);
        echo json_encode($response);
        exit();
    }
}

// Delete file / folder
if (isset($_GET['del']) && !FM_READONLY) {
    $del = str_replace( '/', '', fm_clean_path( $_GET['del'] ) );
    if ($del != '' && $del != '..' && $del != '.') {
        $path = FM_ROOT_PATH;
        if (FM_PATH != '') {
            $path .= '/' . FM_PATH;
        }
        $is_dir = is_dir($path . '/' . $del);
        if (fm_rdelete($path . '/' . $del)) {
            $msg = $is_dir ? lng('Folder').' <b>%s</b> '.lng('Deleted') : lng('File').' <b>%s</b> '.lng('Deleted');
            fm_set_msg(sprintf($msg, fm_enc($del)));
        } else {
            $msg = $is_dir ? lng('Folder').' <b>%s</b> '.lng('not deleted') : lng('File').' <b>%s</b> '.lng('not deleted');
            fm_set_msg(sprintf($msg, fm_enc($del)), 'error');
        }
    } else {
        fm_set_msg(lng('Invalid file or folder name'), 'error');
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Create folder
if (isset($_GET['new']) && isset($_GET['type']) && !FM_READONLY) {
    $type = $_GET['type'];
    $new = str_replace( '/', '', fm_clean_path( strip_tags( $_GET['new'] ) ) );
    if (fm_isvalid_filename($new) && $new != '' && $new != '..' && $new != '.') {
        $path = FM_ROOT_PATH;
        if (FM_PATH != '') {
            $path .= '/' . FM_PATH;
        }
        if ($_GET['type'] == "file") {
            if (!file_exists($path . '/' . $new)) {
                if(fm_is_valid_ext($new)) {
                    @fopen($path . '/' . $new, 'w') or die('Cannot open file:  ' . $new);
                    fm_set_msg(sprintf(lng('File').' <b>%s</b> '.lng('Created'), fm_enc($new)));
                } else {
                    fm_set_msg(lng('File extension is not allowed'), 'error');
                }
            } else {
                fm_set_msg(sprintf(lng('File').' <b>%s</b> '.lng('already exists'), fm_enc($new)), 'alert');
            }
        } else {
            if (fm_mkdir($path . '/' . $new, false) === true) {
                fm_set_msg(sprintf(lng('Folder').' <b>%s</b> '.lng('Created'), $new));
            } elseif (fm_mkdir($path . '/' . $new, false) === $path . '/' . $new) {
                fm_set_msg(sprintf(lng('Folder').' <b>%s</b> '.lng('already exists'), fm_enc($new)), 'alert');
            } else {
                fm_set_msg(sprintf(lng('Folder').' <b>%s</b> '.lng('not created'), fm_enc($new)), 'error');
            }
        }
    } else {
        fm_set_msg(lng('Invalid characters in file or folder name'), 'error');
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Copy folder / file
if (isset($_GET['copy'], $_GET['finish']) && !FM_READONLY) {
    // from
    $copy = $_GET['copy'];
    $copy = fm_clean_path($copy);
    // empty path
    if ($copy == '') {
        fm_set_msg(lng('Source path not defined'), 'error');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    // abs path from
    $from = FM_ROOT_PATH . '/' . $copy;
    // abs path to
    $dest = FM_ROOT_PATH;
    if (FM_PATH != '') {
        $dest .= '/' . FM_PATH;
    }
    $dest .= '/' . basename($from);
    // move?
    $move = isset($_GET['move']);
    // copy/move/duplicate
    if ($from != $dest) {
        $msg_from = trim(FM_PATH . '/' . basename($from), '/');
        if ($move) { // Move and to != from so just perform move
            $rename = fm_rename($from, $dest);
            if ($rename) {
                fm_set_msg(sprintf(lng('Moved from').' <b>%s</b> '.lng('to').' <b>%s</b>', fm_enc($copy), fm_enc($msg_from)));
            } elseif ($rename === null) {
                fm_set_msg(lng('File or folder with this path already exists'), 'alert');
            } else {
                fm_set_msg(sprintf(lng('Error while moving from').' <b>%s</b> '.lng('to').' <b>%s</b>', fm_enc($copy), fm_enc($msg_from)), 'error');
            }
        } else { // Not move and to != from so copy with original name
            if (fm_rcopy($from, $dest)) {
                fm_set_msg(sprintf(lng('Copied from').' <b>%s</b> '.lng('to').' <b>%s</b>', fm_enc($copy), fm_enc($msg_from)));
            } else {
                fm_set_msg(sprintf(lng('Error while copying from').' <b>%s</b> '.lng('to').' <b>%s</b>', fm_enc($copy), fm_enc($msg_from)), 'error');
            }
        }
    } else {
       if (!$move){ //Not move and to = from so duplicate
            $msg_from = trim(FM_PATH . '/' . basename($from), '/');
            $fn_parts = pathinfo($from);
            $extension_suffix = '';
            if(!is_dir($from)){
               $extension_suffix = '.'.$fn_parts['extension'];
            }
            //Create new name for duplicate
            $fn_duplicate = $fn_parts['dirname'].'/'.$fn_parts['filename'].'-'.date('YmdHis').$extension_suffix;
            $loop_count = 0;
            $max_loop = 1000;
            // Check if a file with the duplicate name already exists, if so, make new name (edge case...)
            while(file_exists($fn_duplicate) & $loop_count < $max_loop){
               $fn_parts = pathinfo($fn_duplicate);
               $fn_duplicate = $fn_parts['dirname'].'/'.$fn_parts['filename'].'-copy'.$extension_suffix;
               $loop_count++;
            }
            if (fm_rcopy($from, $fn_duplicate, False)) {
                fm_set_msg(sprintf('Copyied from <b>%s</b> to <b>%s</b>', fm_enc($copy), fm_enc($fn_duplicate)));
            } else {
                fm_set_msg(sprintf('Error while copying from <b>%s</b> to <b>%s</b>', fm_enc($copy), fm_enc($fn_duplicate)), 'error');
            }
       }
       else{
           fm_set_msg(lng('Paths must be not equal'), 'alert');
       }
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Mass copy files/ folders
if (isset($_POST['file'], $_POST['copy_to'], $_POST['finish']) && !FM_READONLY) {
    // from
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
        $path .= '/' . FM_PATH;
    }
    // to
    $copy_to_path = FM_ROOT_PATH;
    $copy_to = fm_clean_path($_POST['copy_to']);
    if ($copy_to != '') {
        $copy_to_path .= '/' . $copy_to;
    }
    if ($path == $copy_to_path) {
        fm_set_msg(lng('Paths must be not equal'), 'alert');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    if (!is_dir($copy_to_path)) {
        if (!fm_mkdir($copy_to_path, true)) {
            fm_set_msg('Unable to create destination folder', 'error');
            fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
        }
    }
    // move?
    $move = isset($_POST['move']);
    // copy/move
    $errors = 0;
    $files = $_POST['file'];
    if (is_array($files) && count($files)) {
        foreach ($files as $f) {
            if ($f != '') {
                // abs path from
                $from = $path . '/' . $f;
                // abs path to
                $dest = $copy_to_path . '/' . $f;
                // do
                if ($move) {
                    $rename = fm_rename($from, $dest);
                    if ($rename === false) {
                        $errors++;
                    }
                } else {
                    if (!fm_rcopy($from, $dest)) {
                        $errors++;
                    }
                }
            }
        }
        if ($errors == 0) {
            $msg = $move ? 'Selected files and folders moved' : 'Selected files and folders copied';
            fm_set_msg($msg);
        } else {
            $msg = $move ? 'Error while moving items' : 'Error while copying items';
            fm_set_msg($msg, 'error');
        }
    } else {
        fm_set_msg(lng('Nothing selected'), 'alert');
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Rename
if (isset($_GET['ren'], $_GET['to']) && !FM_READONLY) {
    // old name
    $old = $_GET['ren'];
    $old = fm_clean_path($old);
    $old = str_replace('/', '', $old);
    // new name
    $new = $_GET['to'];
    $new = fm_clean_path(strip_tags($new));
    $new = str_replace('/', '', $new);
    // path
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
        $path .= '/' . FM_PATH;
    }
    // rename
    if (fm_isvalid_filename($new) && $old != '' && $new != '') {
        if (fm_rename($path . '/' . $old, $path . '/' . $new)) {
            fm_set_msg(sprintf(lng('Renamed from').' <b>%s</b> '. lng('to').' <b>%s</b>', fm_enc($old), fm_enc($new)));
        } else {
            fm_set_msg(sprintf(lng('Error while renaming from').' <b>%s</b> '. lng('to').' <b>%s</b>', fm_enc($old), fm_enc($new)), 'error');
        }
    } else {
        fm_set_msg(lng('Invalid characters in file name'), 'error');
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Download
if (isset($_GET['dl'])) {
    $dl = $_GET['dl'];
    $dl = fm_clean_path($dl);
    $dl = str_replace('/', '', $dl);
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
        $path .= '/' . FM_PATH;
    }
    if ($dl != '' && is_file($path . '/' . $dl)) {
        fm_download_file($path . '/' . $dl, $dl, 1024);
        exit;
    } else {
        fm_set_msg(lng('File not found'), 'error');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
}

// Upload
if (!empty($_FILES) && !FM_READONLY) {
    $override_file_name = false;
    $chunkIndex = $_POST['dzchunkindex'];
    $chunkTotal = $_POST['dztotalchunkcount'];

    $f = $_FILES;
    $path = FM_ROOT_PATH;
    $ds = DIRECTORY_SEPARATOR;
    if (FM_PATH != '') {
        $path .= '/' . FM_PATH;
    }

    $errors = 0;
    $uploads = 0;
    $allowed = (FM_UPLOAD_EXTENSION) ? explode(',', FM_UPLOAD_EXTENSION) : false;
    $response = array (
        'status' => 'error',
        'info'   => 'Oops! Try again'
    );

    $filename = $f['file']['name'];
    $tmp_name = $f['file']['tmp_name'];
    $ext = pathinfo($filename, PATHINFO_FILENAME) != '' ? strtolower(pathinfo($filename, PATHINFO_EXTENSION)) : '';
    $isFileAllowed = ($allowed) ? in_array($ext, $allowed) : true;

    if(!fm_isvalid_filename($filename) && !fm_isvalid_filename($_REQUEST['fullpath'])) {
        $response = array (
            'status'    => 'error',
            'info'      => "Invalid File name!",
        );
        echo json_encode($response); exit();
    }

    $targetPath = $path . $ds;
    if ( is_writable($targetPath) ) {
    	// Copie fichiers et répertoire
        $fullPath = $path . '/' .($_REQUEST['fullpath']);
        $folder = substr($fullPath, 0, strrpos($fullPath, "/"));

        if(file_exists ($fullPath) && !$override_file_name && !$chunks) {
            $ext_1 = $ext ? '.'.$ext : '';
            $fullPath = $path . '/' . basename($_REQUEST['fullpath'], $ext_1) .'_'. date('ymdHis'). $ext_1;
        }

        if (!is_dir($folder)) {
            $old = umask(0);
            mkdir($folder, 0777, true);
            umask($old);
        }



        if (empty($f['file']['error']) && !empty($tmp_name) && $tmp_name != 'none' && $isFileAllowed) {
            if ($chunkTotal){
                $out = @fopen("{$fullPath}.part", $chunkIndex == 0 ? "wb" : "ab");
                if ($out) {
                    $in = @fopen($tmp_name, "rb");
                    if ($in) {
                        while ($buff = fread($in, 4096)) { fwrite($out, $buff); }
                    } else {
                        $response = array (
                        'status'    => 'error',
                        'info' => "failed to open output stream"
                        );
                    }
                    @fclose($in);
                    @fclose($out);
                    @unlink($tmp_name);

                    $response = array (
                        'status'    => 'success',
                        'info' => "file upload successful",
                        'fullPath' => $fullPath
                    );
                } else {
                    $response = array (
                        'status'    => 'error',
                        'info' => "failed to open output stream"
                        );
                }



                if ($chunkIndex == $chunkTotal - 1) {
                    rename("{$fullPath}.part", $fullPath);
                }

            } else if (move_uploaded_file($tmp_name, $fullPath)) {
                // Be sure that the file has been uploaded
                if ( file_exists($fullPath) ) {
                    $response = array (
                        'status'    => 'success',
                        'info' => "file upload successful"
                    );
                } else {
                    $response = array (
                        'status' => 'error',
                        'info'   => 'Couldn\'t upload the requested file.'
                    );
                }
            } else {
                $response = array (
                    'status'    => 'error',
                    'info'      => "Error while uploading files. Uploaded files $uploads",
                );
            }
        }
    } else {
        $response = array (
            'status' => 'error',
            'info'   => 'The specified folder for upload isn\'t writeable.'
        );
    }
    // Return the response
    echo json_encode($response);
    exit();
}

// Mass deleting
if (isset($_POST['group'], $_POST['delete']) && !FM_READONLY) {
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
        $path .= '/' . FM_PATH;
    }

    $errors = 0;
    $files = $_POST['file'];
    if (is_array($files) && count($files)) {
        foreach ($files as $f) {
            if ($f != '') {
                $new_path = $path . '/' . $f;
                if (!fm_rdelete($new_path)) {
                    $errors++;
                }
            }
        }
        if ($errors == 0) {
            fm_set_msg(lng('Selected files and folder deleted'));
        } else {
            fm_set_msg(lng('Error while deleting items'), 'error');
        }
    } else {
        fm_set_msg(lng('Nothing selected'), 'alert');
    }

    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Pack files
if (isset($_POST['group']) && (isset($_POST['zip']) || isset($_POST['tar'])) && !FM_READONLY) {
    $path = FM_ROOT_PATH;
    $ext = 'zip';
    if (FM_PATH != '') {
        $path .= '/' . FM_PATH;
    }

    //set pack type
    $ext = isset($_POST['tar']) ? 'tar' : 'zip';


    if (($ext == "zip" && !class_exists('ZipArchive')) || ($ext == "tar" && !class_exists('PharData'))) {
        fm_set_msg(lng('Operations with archives are not available'), 'error');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }

    $files = $_POST['file'];
    if (!empty($files)) {
        chdir($path);

        if (count($files) == 1) {
            $one_file = reset($files);
            $one_file = basename($one_file);
            $zipname = $one_file . '_' . date('ymd_His') . '.'.$ext;
        } else {
            $zipname = 'archive_' . date('ymd_His') . '.'.$ext;
        }

        if($ext == 'zip') {
            $zipper = new FM_Zipper();
            $res = $zipper->create($zipname, $files);
        } elseif ($ext == 'tar') {
            $tar = new FM_Zipper_Tar();
            $res = $tar->create($zipname, $files);
        }

        if ($res) {
            fm_set_msg(sprintf(lng('Archive').' <b>%s</b> '.lng('Created'), fm_enc($zipname)));
        } else {
            fm_set_msg(lng('Archive not created'), 'error');
        }
    } else {
        fm_set_msg(lng('Nothing selected'), 'alert');
    }

    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Unpack
if (isset($_GET['unzip']) && !FM_READONLY) {
    $unzip = $_GET['unzip'];
    $unzip = fm_clean_path($unzip);
    $unzip = str_replace('/', '', $unzip);
    $isValid = false;

    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
        $path .= '/' . FM_PATH;
    }

    if ($unzip != '' && is_file($path . '/' . $unzip)) {
        $zip_path = $path . '/' . $unzip;
        $ext = pathinfo($zip_path, PATHINFO_EXTENSION);
        $isValid = true;
    } else {
        fm_set_msg(lng('File not found'), 'error');
    }


    if (($ext == "zip" && !class_exists('ZipArchive')) || ($ext == "tar" && !class_exists('PharData'))) {
        fm_set_msg(lng('Operations with archives are not available'), 'error');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }

    if ($isValid) {
        //to folder
        $tofolder = '';
        if (isset($_GET['tofolder'])) {
            $tofolder = pathinfo($zip_path, PATHINFO_FILENAME);
            if (fm_mkdir($path . '/' . $tofolder, true)) {
                $path .= '/' . $tofolder;
            }
        }

        if($ext == "zip") {
            $zipper = new FM_Zipper();
            $res = $zipper->unzip($zip_path, $path);
        } elseif ($ext == "tar") {
            try {
                $gzipper = new PharData($zip_path);
                if (@$gzipper->extractTo($path,null, true)) {
                    $res = true;
                } else {
                    $res = false;
                }
            } catch (Exception $e) {
                //TODO:: need to handle the error
                $res = true;
            }
        }

        if ($res) {
            fm_set_msg(lng('Archive unpacked'));
        } else {
            fm_set_msg(lng('Archive not unpacked'), 'error');
        }

    } else {
        fm_set_msg(lng('File not found'), 'error');
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Change Perms (not for Windows)
if (isset($_POST['chmod']) && !FM_READONLY && !FM_IS_WIN) {
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
        $path .= '/' . FM_PATH;
    }

    $file = $_POST['chmod'];
    $file = fm_clean_path($file);
    $file = str_replace('/', '', $file);
    if ($file == '' || (!is_file($path . '/' . $file) && !is_dir($path . '/' . $file))) {
        fm_set_msg(lng('File not found'), 'error');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }

    $mode = 0;
    if (!empty($_POST['ur'])) {
        $mode |= 0400;
    }
    if (!empty($_POST['uw'])) {
        $mode |= 0200;
    }
    if (!empty($_POST['ux'])) {
        $mode |= 0100;
    }
    if (!empty($_POST['gr'])) {
        $mode |= 0040;
    }
    if (!empty($_POST['gw'])) {
        $mode |= 0020;
    }
    if (!empty($_POST['gx'])) {
        $mode |= 0010;
    }
    if (!empty($_POST['or'])) {
        $mode |= 0004;
    }
    if (!empty($_POST['ow'])) {
        $mode |= 0002;
    }
    if (!empty($_POST['ox'])) {
        $mode |= 0001;
    }

    if (@chmod($path . '/' . $file, $mode)) {
        fm_set_msg(lng('Permissions changed'));
    } else {
        fm_set_msg(lng('Permissions not changed'), 'error');
    }

    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

/*************************** /ACTIONS ***************************/

// get current path
$path = FM_ROOT_PATH;
if (FM_PATH != '') {
    $path .= '/' . FM_PATH;
}

// check path
if (!is_dir($path)) {
    fm_redirect(FM_SELF_URL . '?p=');
}

// get parent folder
$parent = fm_get_parent_path(FM_PATH);

$objects = is_readable($path) ? scandir($path) : array();
$folders = array();
$files = array();
$current_path = array_slice(explode("/",$path), -1)[0];
if (is_array($objects) && fm_is_exclude_items($current_path)) {
    foreach ($objects as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        if (!FM_SHOW_HIDDEN && substr($file, 0, 1) === '.') {
            continue;
        }
        $new_path = $path . '/' . $file;
        if (@is_file($new_path) && fm_is_exclude_items($file)) {
            $files[] = $file;
        } elseif (@is_dir($new_path) && $file != '.' && $file != '..' && fm_is_exclude_items($file)) {
            $folders[] = $file;
        }
    }
}

if (!empty($files)) {
    natcasesort($files);
}
if (!empty($folders)) {
    natcasesort($folders);
}

// upload form
if (isset($_GET['upload']) && !FM_READONLY) {
    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
    //get the allowed file extensions
    function getUploadExt() {
        $extArr = explode(',', FM_UPLOAD_EXTENSION);
        if(FM_UPLOAD_EXTENSION && $extArr) {
            array_walk($extArr, function(&$x) {$x = ".$x";});
            return implode(',', $extArr);
        }
        return '';
    }
    ?>
	<link href="css/dropzone.min.css" rel="stylesheet">
    <div class="path">
       <div class="card mb-2 fm-upload-wrapper <?php echo fm_get_theme(); ?>">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="#fileUploader" data-target="#fileUploader"><i class="fa fa-arrow-circle-o-up"></i> <?php echo lng('UploadingFiles') ?></a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <p class="card-text">
                    <a href="?p=<?php echo FM_PATH ?>" class="float-right"><i class="fa fa-chevron-circle-left go-back"></i> <?php echo lng('Back')?></a>
                    <?php echo lng('DestinationFolder') ?>: <?php echo fm_enc(fm_convert_win(FM_PATH)) ?>
                </p>

                <form action="<?php echo htmlspecialchars(FM_SELF_URL) . '?p=' . fm_enc(FM_PATH) ?>" class="dropzone card-tabs-container" id="fileUploader" enctype="multipart/form-data">
                    <input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
                    <input type="hidden" name="fullpath" id="fullpath" value="<?php echo fm_enc(FM_PATH) ?>">
                    <div class="fallback">
                        <input name="file" type="file" multiple/>
                    </div>
                </form>

                <div class="upload-url-wrapper card-tabs-container hidden" id="urlUploader">
                    <form id="js-form-url-upload" class="form-inline" onsubmit="return upload_from_url(this);" method="POST" action="">
                        <input type="hidden" name="type" value="upload" aria-label="hidden" aria-hidden="true">
                        <input type="url" placeholder="URL" name="uploadurl" required class="form-control" style="width: 80%">
                        <button type="submit" class="btn btn-primary ml-3"><?php echo lng('Upload') ?></button>
                        <div class="lds-facebook"><div></div><div></div><div></div></div>
                    </form>
                    <div id="js-url-upload__list" class="col-9 mt-3"></div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/dropzone.min.js"></script>
    <script>
        Dropzone.options.fileUploader = {
            chunking: true,
            chunkSize: 2000000, // Christophe nochange value 10000000 is bad for php 8
            forceChunking: true,
            retryChunks: true,
            retryChunksLimit: 3,
            parallelUploads: 1, // does not support more than 1!
            timeout: 120000,
            maxFilesize: 10000000000,
            acceptedFiles : "<?php echo getUploadExt() ?>",
            init: function () {
                this.on("sending", function (file, xhr, formData) {
                    let _path = (file.fullPath) ? file.fullPath : file.name;
                    document.getElementById("fullpath").value = _path;
                    xhr.ontimeout = (function() {
                        toast('Error: Server Timeout');
                    });
                }).on("success", function (res) {
                    let _response = JSON.parse(res.xhr.response);

                    if(_response.status == "error") {
                        toast(_response.info);
                    }

                }).on("error", function(file, response) {
                    toast(response);
                });
            }
        }
    </script>
    <?php
    fm_show_footer();
    exit;
}

// play form christophe 
if (isset($_GET['player'])){
    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
    //get the allowed file extensions
    
?>

<div class="card <?php echo fm_get_theme(); ?>">
    <link href="css/player.css" rel="stylesheet">
    <div class="brd">
        
        <!-- (A) AUDIO TAG -->
        <center>
            <h2><?php echo lng('Player') ?></h2>
            <audio id="PlayerAudio" controls style=" width:100%;"></audio><br>
            <button onclick="prev()" class="btn"><i class="fa fa-backward"></i></button>
            <button onclick="next()"class="btn" ><i class="fa fa-forward"></i></button>
        </center>    
        <!-- (B) PLAYLIST -->
        <div id="ListPlayer">
            <div class="brdList">
                <?php
                    // (B1) GET ALL SONGS
                    $str = FM_ROOT_PATH . '/playlist.m3u';
                    $lines = file($str);
                    $num_id = 0;
                    foreach ($lines as $line_num => $line){
                        if ($line_num & 1){
                            ++$num_id;
                            $line = str_replace('#EXTINF:-1,', '', $line);
                            $line = urldecode($line);
                            $listmp3[] = "N&deg; " . $num_id . " - " . htmlspecialchars($line);
                            } else {
                            if ($line_num > 1) {
                                $songs[] = $line;
                            }
                        }
                    }
                    // (B2) OUTPUT SONGS IN
                    if (is_array($songs)) {
                        foreach ($songs as $k => $s){
                            printf("<div data-id='%u' data-src='%s' class='song'>%s</div>", $k, $s, $listmp3[$k]);
                        }
                        } else {
                        echo "No songs found!";
                    }
                ?>
            </div>
        </div>
    </div>
</div>
<script src="js/audio.js"></script>
<?php
    fm_show_footer();
    exit;
}

//Christophe Alexa List mp3
// list mp3 form POST
if (isset($_POST['list']))
{
    $copy_files = isset($_POST['file']) ? $_POST['file'] : null;
    if (!is_array($copy_files) || empty($copy_files)) {
        fm_set_msg(lng('No selection') , 'alert');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    if (stripos($copy_files[0], '.mp3') === false) {
        fm_set_msg(lng('Directory selection : ') . $copy_files[0], 'alert');
        fm_redirect(FM_SELF_URL . '?p=' . $copy_files[0] . urlencode(FM_PATH));
    }
    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
    
?>
<div class="path">
    <div class="card <?php echo fm_get_theme(); ?>">
        <div class="card-header">
            <h6><?php echo lng('List MP3') ?></h6>
        </div>
        <div class="card-body">               
            <?php
                $str = str_replace($_SERVER['DOCUMENT_ROOT'], "", FM_ROOT_PATH, $chemin);
                $str = FM_ROOT_URL . $str;
                if (FM_PATH !== '') $str .= '/' . FM_PATH;
                
            ?>
            <p class="break-word"><?php "\n";
            echo lng('Files') . ': <br> <b>' . $str . '/' . implode('</b>,<br><b>' . $str . '/', $copy_files) ?></b></p>
            
            <form action="" method="post">
                <input type="hidden" name="listemp3" value="<?php echo fm_enc(FM_PATH) ?>">
                <input type="hidden" name="finish" value="1">
                <?php
                    foreach ($copy_files as $cf) {
                        echo '<input type="hidden" name="file[]" value="' . $str . '/' . fm_enc($cf) . '">' . PHP_EOL;
                    }
                ?>
                <p>
                    <button type="submit"  name="create" class="btn btn-success"><i class="fa fa-check-circle"></i> <?php echo lng('CreateNow') ?></button> &nbsp;
                    <button type="submit"  name="add" class="btn btn-primary"><i class="fa fa-check-circle"></i> <?php echo lng('Add') ?></button> &nbsp;
                    <b><a href="?p=<?php echo urlencode(FM_PATH) ?>" class="btn btn-outline-primary"><i class="fa fa-times-circle"></i> <?php echo lng('Cancel') ?></a></b>
                    
                    <i class="fa fa-cog fa-spin fa-fw" aria-hidden="true"></i><?php echo lng('Shuffle: ') ?><input type="checkbox" name="shuffle" value="1">
                </p>
            </form>                 
    </div>
</div>    

<?php
    fm_show_footer();
    
    exit;
}

//Christophe Alexa List mp3
// list mp3 form Création
if (isset($_POST['listemp3']) && isset($_POST['create'])) {
    $copy_files = array();
    $playlist = "#EXTM3U\n";
    $copy_files = isset($_POST['file']) ? $_POST['file'] : null;
    $str = FM_ROOT_PATH . '/';
    $status = 0;
    if (isset($_POST['shuffle'])) {
        $status = $_POST['shuffle'];
    }
    if ($status == 1) {
        shuffle($copy_files);
    }
    if (!empty($copy_files)) {
        foreach ($copy_files as $file){
            $pathInfo = pathinfo($file);
            $playlist .= "#EXTINF:-1," . $pathInfo['basename'] . "\n";
            $playlist .= str_replace(array(
            "%2F",
            "%3A"
            ) , array(
            "/",
            ":"
            ) , rawurlencode($file)) . "\n";
        }
    }
    @file_put_contents($str . "playlist.m3u", $playlist) or die("Can't create playlist.m3u file. Please check permissions.");
    // Rechargement de l'adress racine
    echo '<meta http-equiv="refresh" content="0" />';
    fm_show_footer();
    exit;
}

//Christophe Alexa List mp3
// list mp3 form add
if (isset($_POST['listemp3']) && isset($_POST['add'])){
    $copy_files = array();
    $playlist = "";
    $copy_files = isset($_POST['file']) ? $_POST['file'] : null;
    $str = FM_ROOT_PATH . '/';
    $status = 0;
    if (isset($_POST['shuffle'])) {
        $status = $_POST['shuffle'];
    }
    if ($status == 1) {
        shuffle($copy_files);
    }
    if (!empty($copy_files)) {
        foreach ($copy_files as $file){
            $pathInfo = pathinfo($file);
            $playlist .= "#EXTINF:-1," . $pathInfo['basename'] . "\n";
            $playlist .= str_replace(array(
            "%2F",
            "%3A"
            ) , array(
            "/",
            ":"
            ) , rawurlencode($file)) . "\n";
        }
    }
    @file_put_contents($str . "playlist.m3u", $playlist, FILE_APPEND) or die("Can't create playlist.m3u file. Please check permissions.");
    // Rechargement de l'adress racine
    echo '<meta http-equiv="refresh" content="0" />';
    fm_show_footer();
    exit;
}            

//Christophe Alexa List mp3
// list mp3 form Création
if (isset($_POST['listemp3']) && isset($_POST['create'])) {
    $copy_files = array();
    $playlist = "#EXTM3U\n";
    $copy_files = isset($_POST['file']) ? $_POST['file'] : null;
    $str = FM_ROOT_PATH . '/';
    $status = 0;
    if (isset($_POST['shuffle'])) {
        $status = $_POST['shuffle'];
    }
    if ($status == 1) {
        shuffle($copy_files);
    }
    if (!empty($copy_files)){
        foreach ($copy_files as $file){
            $pathInfo = pathinfo($file);
            $playlist .= "#EXTINF:-1," . $pathInfo['basename'] . "\n";
            $playlist .= str_replace(array(
                "%2F",
                "%3A"
            ) , array(
                "/",
                ":"
            ) , rawurlencode($file)) . "\n";
        }
    }
    @file_put_contents($str . "playlist.m3u", $playlist) or die("Can't create playlist.m3u file. Please check permissions.");
    // Rechargement de l'adress racine
    echo '<meta http-equiv="refresh" content="0" />';
    fm_show_footer();
    exit;
}

//Christophe Alexa List mp3
// list mp3 form add
if (isset($_POST['listemp3']) && isset($_POST['add'])){
    $copy_files = array();
    $playlist = "";
    $copy_files = isset($_POST['file']) ? $_POST['file'] : null;
    $str = FM_ROOT_PATH . '/';
    $status = 0;
    if (isset($_POST['shuffle'])) {
        $status = $_POST['shuffle'];
    }
    if ($status == 1) {
        shuffle($copy_files);
    }
    if (!empty($copy_files)) {
        foreach ($copy_files as $file){
            $pathInfo = pathinfo($file);
            $playlist .= "#EXTINF:-1," . $pathInfo['basename'] . "\n";
            $playlist .= str_replace(array(
                "%2F",
                "%3A"
            ) , array(
                "/",
                ":"
            ) , rawurlencode($file)) . "\n";
        }
    }
    @file_put_contents($str . "playlist.m3u", $playlist, FILE_APPEND) or die("Can't create playlist.m3u file. Please check permissions.");
    // Rechargement de l'adress racine
    echo '<meta http-equiv="refresh" content="0" />';
    fm_show_footer();
    exit;
}

// copy form POST
if (isset($_POST['copy']) && !FM_READONLY) {
    $copy_files = isset($_POST['file']) ? $_POST['file'] : null;
    if (!is_array($copy_files) || empty($copy_files)) {
        fm_set_msg(lng('Nothing selected'), 'alert');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }

    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
    ?>
    <div class="path">
        <div class="card <?php echo fm_get_theme(); ?>">
            <div class="card-header">
                <h6><?php echo lng('Copying') ?></h6>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
                    <input type="hidden" name="finish" value="1">
                    <?php
                    foreach ($copy_files as $cf) {
                        echo '<input type="hidden" name="file[]" value="' . fm_enc($cf) . '">' . PHP_EOL;
                    }
                    ?>
                    <p class="break-word"><?php echo lng('Files') ?>: <b><?php echo implode('</b>, <b>', $copy_files) ?></b></p>
                    <p class="break-word"><?php echo lng('SourceFolder') ?>: <?php echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . FM_PATH)) ?><br>
                        <label for="inp_copy_to"><?php echo lng('DestinationFolder') ?>:</label>
                        <?php echo FM_ROOT_PATH ?>/<input type="text" name="copy_to" id="inp_copy_to" value="<?php echo fm_enc(FM_PATH) ?>">
                    </p>
                    <p class="custom-checkbox custom-control"><input type="checkbox" name="move" value="1" id="js-move-files" class="custom-control-input"><label for="js-move-files" class="custom-control-label" style="vertical-align: sub"> <?php echo lng('Move') ?></label></p>
                    <p>
                        <button type="submit" class="btn btn-success"><i class="fa fa-check-circle"></i> <?php echo lng('Copy') ?></button> &nbsp;
                        <b><a href="?p=<?php echo urlencode(FM_PATH) ?>" class="btn btn-outline-primary"><i class="fa fa-times-circle"></i> <?php echo lng('Cancel') ?></a></b>
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php
    fm_show_footer();
    exit;
}

// copy form
if (isset($_GET['copy']) && !isset($_GET['finish']) && !FM_READONLY) {
    $copy = $_GET['copy'];
    $copy = fm_clean_path($copy);
    if ($copy == '' || !file_exists(FM_ROOT_PATH . '/' . $copy)) {
        fm_set_msg(lng('File not found'), 'error');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }

    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
    ?>
    <div class="path">
        <p><b>Copying</b></p>
        <p class="break-word">
            Source path: <?php echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . $copy)) ?><br>
            Destination folder: <?php echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . FM_PATH)) ?>
        </p>
        <p>
            <b><a href="?p=<?php echo urlencode(FM_PATH) ?>&amp;copy=<?php echo urlencode($copy) ?>&amp;finish=1"><i class="fa fa-check-circle"></i> Copy</a></b> &nbsp;
            <b><a href="?p=<?php echo urlencode(FM_PATH) ?>&amp;copy=<?php echo urlencode($copy) ?>&amp;finish=1&amp;move=1"><i class="fa fa-check-circle"></i> Move</a></b> &nbsp;
            <b><a href="?p=<?php echo urlencode(FM_PATH) ?>"><i class="fa fa-times-circle"></i> Cancel</a></b>
        </p>
        <p><i><?php echo lng('Select folder') ?></i></p>
        <ul class="folders break-word">
            <?php
            if ($parent !== false) {
                ?>
                <li><a href="?p=<?php echo urlencode($parent) ?>&amp;copy=<?php echo urlencode($copy) ?>"><i class="fa fa-chevron-circle-left"></i> ..</a></li>
                <?php
            }
            foreach ($folders as $f) {
                ?>
                <li>
                    <a href="?p=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?>&amp;copy=<?php echo urlencode($copy) ?>"><i class="fa fa-folder-o"></i> <?php echo fm_convert_win($f) ?></a></li>
                <?php
            }
            ?>
        </ul>
    </div>
    <?php
    fm_show_footer();
    exit;
}

if (isset($_GET['settings']) && !FM_READONLY) {
    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
    global $cfg, $lang, $lang_list;
    ?>

    <div class="col-md-8 offset-md-2 pt-3">
        <div class="card mb-2 <?php echo fm_get_theme(); ?>">
            <h6 class="card-header">
                <i class="fa fa-cog"></i>  <?php echo lng('Settings') ?>
                <a href="?p=<?php echo FM_PATH ?>" class="float-right"><i class="fa fa-window-close"></i> <?php echo lng('Cancel')?></a>
            </h6>
            <div class="card-body">
                <form id="js-settings-form" action="" method="post" data-type="ajax" onsubmit="return save_settings(this)">
                    <input type="hidden" name="type" value="settings" aria-label="hidden" aria-hidden="true">
                    <div class="form-group row">
                        <label for="js-language" class="col-sm-3 col-form-label"><?php echo lng('Language') ?></label>
                        <div class="col-sm-5">
                            <select class="form-control" id="js-language" name="js-language">
                                <?php
                                function getSelected($l) {
                                    global $lang;
                                    return ($lang == $l) ? 'selected' : '';
                                }
                                foreach ($lang_list as $k => $v) {
                                    echo "<option value='$k' ".getSelected($k).">$v</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <?php
                    //get ON/OFF and active class
                    function getChecked($conf, $val, $txt) {
                        if($conf== 1 && $val ==1) {
                            return $txt;
                        } else if($conf == '' && $val == '') {
                            return $txt;
                        } else {
                            return '';
                        }
                    }
                    ?>
                    <div class="form-group row">
                        <label for="js-err-rpt-1" class="col-sm-3 col-form-label"><?php echo lng('ErrorReporting') ?></label>
                        <div class="col-sm-9">
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-secondary <?php echo getChecked($report_errors, 1, 'active') ?>">
                                    <input type="radio" name="js-error-report" id="js-err-rpt-1" autocomplete="off" value="true" <?php echo getChecked($report_errors, 1, 'checked') ?> > <?php echo lng('ON') ?>
                                </label>
                                <label class="btn btn-secondary <?php echo getChecked($report_errors, '', 'active') ?>">
                                    <input type="radio" name="js-error-report" id="js-err-rpt-0" autocomplete="off" value="false" <?php echo getChecked($report_errors, '', 'checked') ?> > <?php echo lng('OFF') ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="js-hdn-1" class="col-sm-3 col-form-label"><?php echo lng('ShowHiddenFiles') ?></label>
                        <div class="col-sm-9">
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-secondary <?php echo getChecked($show_hidden_files, 1, 'active') ?>">
                                    <input type="radio" name="js-show-hidden" id="js-hdn-1" autocomplete="off" value="true" <?php echo getChecked($show_hidden_files, 1, 'checked') ?> > <?php echo lng('ON') ?>
                                </label>
                                <label class="btn btn-secondary <?php echo getChecked($show_hidden_files, '', 'active') ?>">
                                    <input type="radio" name="js-show-hidden" id="js-hdn-0" autocomplete="off" value="false" <?php echo getChecked($show_hidden_files, '', 'checked') ?> > <?php echo lng('OFF') ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="js-hid-1" class="col-sm-3 col-form-label"><?php echo lng('HideColumns') ?></label>
                        <div class="col-sm-9">
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-secondary <?php echo getChecked($hide_Cols, 1, 'active') ?>">
                                    <input type="radio" name="js-hide-cols" id="js-hid-1" autocomplete="off" value="true" <?php echo getChecked($hide_Cols, 1, 'checked') ?> > <?php echo lng('ON') ?>
                                </label>
                                <label class="btn btn-secondary <?php echo getChecked($hide_Cols, '', 'active') ?>">
                                    <input type="radio" name="js-hide-cols" id="js-hid-0" autocomplete="off" value="false" <?php echo getChecked($hide_Cols, '', 'checked') ?> > <?php echo lng('OFF') ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="js-dir-1" class="col-sm-3 col-form-label"><?php echo lng('CalculateFolderSize') ?></label>
                        <div class="col-sm-9">
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-secondary <?php echo getChecked($calc_folder, 1, 'active') ?>">
                                    <input type="radio" name="js-calc-folder" id="js-dir-1" autocomplete="off" value="true" <?php echo getChecked($calc_folder, 1, 'checked') ?> > <?php echo lng('ON') ?>
                                </label>
                                <label class="btn btn-secondary <?php echo getChecked($calc_folder, '', 'active') ?>">
                                    <input type="radio" name="js-calc-folder" id="js-dir-0" autocomplete="off" value="false" <?php echo getChecked($calc_folder, '', 'checked') ?> > <?php echo lng('OFF') ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="js-3-1" class="col-sm-3 col-form-label"><?php echo lng('Theme') ?></label>
                        <div class="col-sm-5">
                            <select class="form-control" id="js-3-0" name="js-theme-3" style="width:200px;">
                         <option value='light' <?php if($theme == "light"){echo "selected";} ?>><?php echo lng('light') ?></option>
                         <option value='dark' <?php if($theme == "dark"){echo "selected";} ?>><?php echo lng('dark') ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-sm-10">
                            <button type="submit" class="btn btn-success"> <i class="fa fa-check-circle"></i> <?php echo lng('Save'); ?></button>
                        </div>
                    </div>

                </form>
            </div>


	<!-- Gestion des adresses IP -->

	<h6 class="card-header"><?php echo lng('Managing IP addresses with access authorization') ?></h6>
	    <div class="col-sm-9">
	    <form method="post">
	        <div class="form-group row">
	            <label for="js-dir-1" class="col-sm-3 col-form-label"><?php echo lng('Add IP address'); ?></label>
	            <div class="col-sm-9">
	                <input type="text" name="htaccess" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>" />
	                <input type="submit" class="btn btn-secondary" value="<?php echo lng('Add'); ?>"/>		
	            </div>
	        </div>
	    </form>
	<form method="post">
	    <div class="form-group row">
	        </br></br>
	        <label for="js-dir-1" class="col-sm-3 col-form-label"><?php echo lng('Delete IP address. Line number?'); ?></label>
	        <div class="col-sm-9">
	           <input type="number" name="delhtaccess" value="" />
	           <input type="submit" class="btn btn-secondary" value="<?php echo lng('Delete'); ?>"/>		
	        </div>
	     </div>
	</form>	
	<?php $lines = file(FM_ROOT_PATH . '/' . '.htaccess');
    // display file line by line
    foreach ($lines as $line_num => $line) {
        $line = str_replace("Allow from", "IP: ", $line);
        $my_line = $line_num - $hide_Ligne;
        if ($line_num > $hide_Ligne) echo "  " . lng('Line number') . " {$my_line} : " . htmlspecialchars($line) . "<br />\n";
    } ?>
	</div>
	
	</div>
	</div>
	</div>
	<?php
    //Christophe Alexa List mp3
    // htaccess gestion des IP efface ligne
    if ((isset($_POST['delhtaccess'])) && ($_POST["delhtaccess"] > 0)) {
        $deladrip = $_POST["delhtaccess"];
        $str = FM_ROOT_PATH . '/' . ".htaccess";
        $ptr = fopen($str, "r");
        $contenu = fread($ptr, filesize($str));
        fclose($ptr);
        $contenu = explode(PHP_EOL, $contenu); /* PHP_EOL contient le saut à la ligne utilisé sur le serveur (\n linux, \r\n windows ou \r Macintosh */
        unset($contenu[$deladrip + $hide_Ligne]); /* On supprime la ligne */
        $contenu = array_values($contenu); /* Ré-indexe l'array */
        $contenu = implode(PHP_EOL, $contenu);
        file_put_contents($str, $contenu) or die("Can't create htaccess file. Please check permissions.");
        echo '<meta http-equiv="refresh" content="0" />'; // rafraichissement de la page
        
    }

    //Christophe Alexa List mp3
    // htaccess gestion des IP ajoute IP
    if (isset($_POST['htaccess'])) {
        $adrip = "Allow from " . $_POST["htaccess"] . "\n";
        $str = FM_ROOT_PATH . '/' . ".htaccess";
        file_put_contents($str, $adrip, FILE_APPEND) or die("Can't create htaccess file. Please check permissions.");
        echo '<meta http-equiv="refresh" content="0" />'; // rafraichissement de la page
        
    }
    fm_show_footer();
    exit;
}

if (isset($_GET['help'])) {
    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
    global $cfg, $lang;
    ?>

    <div class="col-md-8 offset-md-2 pt-3">
        <div class="card mb-2 <?php echo fm_get_theme(); ?>">
            <h6 class="card-header">
                <i class="fa fa-exclamation-circle"></i> <?php echo lng('Help') ?>
                <a href="?p=<?php echo FM_PATH ?>" class="float-right"><i class="fa fa-window-close"></i> <?php echo lng('Cancel')?></a>
            </h6>
            <div class="card-body">
                <div class="row">
                    <div class="col-xs-12 col-sm-6">
                        <p><h3><a href="https://github.com/christophe94700/AlexaPlayList" target="_blank" class="app-v-title"> Alexa List MP3 <?php echo VERSION; ?></a></h3></p>
                        <p><?php echo lng('Author') ?>: <a href="https://domotronic.fr">Christophe Caron</a></p>
                        <p><?php echo lng('Mail Us') ?>: <a href="mailto:christophe@caron.tv">christophe@caron.tv</a> </p>
                    </div>
                </div>
                <div class="row js-new-pwd hidden mt-2">
                    <div class="col-12">
                        <form class="form-inline" onsubmit="return new_password_hash(this)" method="POST" action="">
                            <input type="hidden" name="type" value="pwdhash" aria-label="hidden" aria-hidden="true">
                            <div class="form-group mb-2">
                                <label for="staticEmail2"><?php echo lng('Generate new password hash') ?></label>
                            </div>
                            <div class="form-group mx-sm-3 mb-2">
                                <label for="inputPassword2" class="sr-only"><?php echo lng('Password') ?></label>
                                <input type="text" class="form-control btn-sm" id="inputPassword2" name="inputPassword2" placeholder="Password" required>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm mb-2"><?php echo lng('Generate') ?></button>
                        </form>
                        <textarea class="form-control" rows="2" readonly id="js-pwd-result"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    fm_show_footer();
    exit;
}

// file viewer
if (isset($_GET['view'])) {
    $file = $_GET['view'];
    $quickView = (isset($_GET['quickView']) && $_GET['quickView'] == 1) ? true : false;
    $file = fm_clean_path($file, false);
    $file = str_replace('/', '', $file);
    if ($file == '' || !is_file($path . '/' . $file) || in_array($file, $GLOBALS['exclude_items'])) {
        fm_set_msg(lng('File not found'), 'error');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }

    if(!$quickView) {
        fm_show_header(); // HEADER
        fm_show_nav_path(FM_PATH); // current path
    }
// chemin des fichier .$dir_php.$dir_media 
    $file_url = FM_ROOT_URL. $dir_php . $dir_media . fm_convert_win((FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $file);
    $file_path = $path . '/' . $file;

    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $mime_type = fm_get_mime_type($file_path);
    $filesize_raw = fm_get_size($file_path);
    $filesize = fm_get_filesize($filesize_raw);

    $is_zip = false;
    $is_gzip = false;
    $is_image = false;
    $is_audio = false;
    $is_video = false;
    $is_text = false;
    $is_onlineViewer = false;

    $view_title = 'File';
    $filenames = false; // for zip
    $content = ''; // for text
    $online_viewer = strtolower(FM_DOC_VIEWER);

    if($online_viewer && $online_viewer !== 'false' && in_array($ext, fm_get_onlineViewer_exts())){
        $is_onlineViewer = true;
    }
    elseif ($ext == 'zip' || $ext == 'tar') {
        $is_zip = true;
        $view_title = 'Archive';
        $filenames = fm_get_zif_info($file_path, $ext);
    } elseif (in_array($ext, fm_get_image_exts())) {
        $is_image = true;
        $view_title = 'Image';
    } elseif (in_array($ext, fm_get_audio_exts())) {
        $is_audio = true;
        $view_title = 'Audio';
    } elseif (in_array($ext, fm_get_video_exts())) {
        $is_video = true;
        $view_title = 'Video';
    } elseif (in_array($ext, fm_get_text_exts()) || substr($mime_type, 0, 4) == 'text' || in_array($mime_type, fm_get_text_mimes())) {
        $is_text = true;
        $content = file_get_contents($file_path);
    }

    ?>
    <div class="row">
        <div class="col-12">
            <?php if(!$quickView) { ?>
                <p class="break-word"><b><?php echo $view_title ?> "<?php echo fm_enc(fm_convert_win($file)) ?>"</b></p>
                <p class="break-word">
                    Full path: <?php echo fm_enc(fm_convert_win($file_path)) ?><br>
                    File size: <?php echo ($filesize_raw <= 1000) ? "$filesize_raw bytes" : $filesize; ?><br>
                    MIME-type: <?php echo $mime_type ?><br>
                    <?php
                    // ZIP info
                    if (($is_zip || $is_gzip) && $filenames !== false) {
                        $total_files = 0;
                        $total_comp = 0;
                        $total_uncomp = 0;
                        foreach ($filenames as $fn) {
                            if (!$fn['folder']) {
                                $total_files++;
                            }
                            $total_comp += $fn['compressed_size'];
                            $total_uncomp += $fn['filesize'];
                        }
                        ?>
                        Files in archive: <?php echo $total_files ?><br>
                        Total size: <?php echo fm_get_filesize($total_uncomp) ?><br>
                        Size in archive: <?php echo fm_get_filesize($total_comp) ?><br>
                        Compression: <?php echo round(($total_comp / max($total_uncomp, 1)) * 100) ?>%<br>
                        <?php
                    }
                    // Image info
                    if ($is_image) {
                        $image_size = getimagesize($file_path);
                        echo 'Image sizes: ' . (isset($image_size[0]) ? $image_size[0] : '0') . ' x ' . (isset($image_size[1]) ? $image_size[1] : '0') . '<br>';
                    }
                    // Text info
                    if ($is_text) {
                        $is_utf8 = fm_is_utf8($content);
                        if (function_exists('iconv')) {
                            if (!$is_utf8) {
                                $content = iconv(FM_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $content);
                            }
                        }
                        echo 'Charset: ' . ($is_utf8 ? 'utf-8' : '8 bit') . '<br>';
                    }
                    ?>
                </p>
                <p>
                    <b><a href="?p=<?php echo urlencode(FM_PATH) ?>&amp;dl=<?php echo urlencode($file) ?>"><i class="fa fa-cloud-download"></i> <?php echo lng('Download') ?></a></b> &nbsp;
                    <b><a href="<?php echo fm_enc($file_url) ?>" target="_blank"><i class="fa fa-external-link-square"></i> <?php echo lng('Open') ?></a></b>
                    &nbsp;
                    <?php
                    // ZIP actions
                    if (!FM_READONLY && ($is_zip || $is_gzip) && $filenames !== false) {
                        $zip_name = pathinfo($file_path, PATHINFO_FILENAME);
                        ?>
                        <b><a href="?p=<?php echo urlencode(FM_PATH) ?>&amp;unzip=<?php echo urlencode($file) ?>"><i class="fa fa-check-circle"></i> <?php echo lng('UnZip') ?></a></b> &nbsp;
                        <b><a href="?p=<?php echo urlencode(FM_PATH) ?>&amp;unzip=<?php echo urlencode($file) ?>&amp;tofolder=1" title="UnZip to <?php echo fm_enc($zip_name) ?>"><i class="fa fa-check-circle"></i>
                                <?php echo lng('UnZipToFolder') ?></a></b> &nbsp;
                        <?php
                    }
                    if ($is_text && !FM_READONLY) {
                        ?>
                        <b><a href="?p=<?php echo urlencode(trim(FM_PATH)) ?>&amp;edit=<?php echo urlencode($file) ?>" class="edit-file"><i class="fa fa-pencil-square"></i> <?php echo lng('Edit') ?>
                            </a></b> &nbsp;
                        <b><a href="?p=<?php echo urlencode(trim(FM_PATH)) ?>&amp;edit=<?php echo urlencode($file) ?>&env=ace"
                              class="edit-file"><i class="fa fa-pencil-square-o"></i> <?php echo lng('AdvancedEditor') ?>
                            </a></b> &nbsp;
                    <?php } ?>
                    <b><a href="?p=<?php echo urlencode(FM_PATH) ?>"><i class="fa fa-chevron-circle-left go-back"></i> <?php echo lng('Back') ?></a></b>
                </p>
                <?php
            }
            if($is_onlineViewer) {
                if($online_viewer == 'google') {
                    echo '<iframe src="https://docs.google.com/viewer?embedded=true&hl=en&url=' . fm_enc($file_url) . '" frameborder="no" style="width:100%;min-height:460px"></iframe>';
                } else if($online_viewer == 'microsoft') {
                    echo '<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=' . fm_enc($file_url) . '" frameborder="no" style="width:100%;min-height:460px"></iframe>';
                }
            } elseif ($is_zip) {
                // ZIP content
                if ($filenames !== false) {
                    echo '<code class="maxheight">';
                    foreach ($filenames as $fn) {
                        if ($fn['folder']) {
                            echo '<b>' . fm_enc($fn['name']) . '</b><br>';
                        } else {
                            echo $fn['name'] . ' (' . fm_get_filesize($fn['filesize']) . ')<br>';
                        }
                    }
                    echo '</code>';
                } else {
                    echo '<p>'.lng('Error while fetching archive info').'</p>';
                }
            } elseif ($is_image) {
                // Image content
                if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png', 'bmp', 'ico', 'svg', 'webp', 'avif'))) {
                    echo '<p><img src="' . fm_enc($file_url) . '" alt="" class="preview-img"></p>';
                }
            } elseif ($is_audio) {
                // Audio content
                echo '<p><audio src="' . fm_enc($file_url) . '" controls preload="metadata"></audio></p>';
            } elseif ($is_video) {
                // Video content
                echo '<div class="preview-video"><video src="' . fm_enc($file_url) . '" width="640" height="360" controls preload="metadata"></video></div>';
            } elseif ($is_text) {
                if (FM_USE_HIGHLIGHTJS) {
                    // highlight
                    $hljs_classes = array(
                        'shtml' => 'xml',
                        'htaccess' => 'apache',
                        'phtml' => 'php',
                        'lock' => 'json',
                        'svg' => 'xml',
                    );
                    $hljs_class = isset($hljs_classes[$ext]) ? 'lang-' . $hljs_classes[$ext] : 'lang-' . $ext;
                    if (empty($ext) || in_array(strtolower($file), fm_get_text_names()) || preg_match('#\.min\.(css|js)$#i', $file)) {
                        $hljs_class = 'nohighlight';
                    }
                    $content = '<pre class="with-hljs"><code class="' . $hljs_class . '">' . fm_enc($content) . '</code></pre>';
                } elseif (in_array($ext, array('php', 'php4', 'php5', 'phtml', 'phps'))) {
                    // php highlight
                    $content = highlight_string($content, true);
                } else {
                    $content = '<pre>' . fm_enc($content) . '</pre>';
                }
                echo $content;
            }
            ?>
        </div>
    </div>
    <?php
    if(!$quickView) {
        fm_show_footer();
    }
    exit;
}

// file editor
if (isset($_GET['edit'])) {
    $file = $_GET['edit'];
    $file = fm_clean_path($file, false);
    $file = str_replace('/', '', $file);
    if ($file == '' || !is_file($path . '/' . $file)) {
        fm_set_msg(lng('File not found'), 'error');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    $editFile = ' : <i><b>'. $file. '</b></i>';
    header('X-XSS-Protection:0');
    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
    // Ajout des répertoire php et media 
    $file_url = FM_ROOT_URL. $dir_php . $dir_media . fm_convert_win((FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $file);
    $file_path = $path . '/' . $file;

    // normal editer
    $isNormalEditor = true;
    if (isset($_GET['env'])) {
        if ($_GET['env'] == "ace") {
            $isNormalEditor = false;
        }
    }

    // Save File
    if (isset($_POST['savedata'])) {
        $writedata = $_POST['savedata'];
        $fd = fopen($file_path, "w");
        @fwrite($fd, $writedata);
        fclose($fd);
        fm_set_msg(lng('File Saved Successfully'));
    }

    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $mime_type = fm_get_mime_type($file_path);
    $filesize = filesize($file_path);
    $is_text = false;
    $content = ''; // for text

    if (in_array($ext, fm_get_text_exts()) || substr($mime_type, 0, 4) == 'text' || in_array($mime_type, fm_get_text_mimes())) {
        $is_text = true;
        $content = file_get_contents($file_path);
    }

    ?>
    <div class="path">
        <div class="row">
            <div class="col-xs-12 col-sm-5 col-lg-6 pt-1">
                <div class="btn-toolbar" role="toolbar">
                    <?php if (!$isNormalEditor) { ?>
                        <div class="btn-group js-ace-toolbar">
                            <button data-cmd="none" data-option="fullscreen" class="btn btn-sm btn-outline-secondary" id="js-ace-fullscreen" title="Fullscreen"><i class="fa fa-expand" title="Fullscreen"></i></button>
                            <button data-cmd="find" class="btn btn-sm btn-outline-secondary" id="js-ace-search" title="Search"><i class="fa fa-search" title="Search"></i></button>
                            <button data-cmd="undo" class="btn btn-sm btn-outline-secondary" id="js-ace-undo" title="Undo"><i class="fa fa-undo" title="Undo"></i></button>
                            <button data-cmd="redo" class="btn btn-sm btn-outline-secondary" id="js-ace-redo" title="Redo"><i class="fa fa-repeat" title="Redo"></i></button>
                            <button data-cmd="none" data-option="wrap" class="btn btn-sm btn-outline-secondary" id="js-ace-wordWrap" title="Word Wrap"><i class="fa fa-text-width" title="Word Wrap"></i></button>
                            <button data-cmd="none" data-option="help" class="btn btn-sm btn-outline-secondary" id="js-ace-goLine" title="Help"><i class="fa fa-question" title="Help"></i></button>
                            <select id="js-ace-mode" data-type="mode" title="Select Document Type" class="btn-outline-secondary border-left-0 d-none d-md-block"><option>-- Select Mode --</option></select>
                            <select id="js-ace-theme" data-type="theme" title="Select Theme" class="btn-outline-secondary border-left-0 d-none d-lg-block"><option>-- Select Theme --</option></select>
                            <select id="js-ace-fontSize" data-type="fontSize" title="Select Font Size" class="btn-outline-secondary border-left-0 d-none d-lg-block"><option>-- Select Font Size --</option></select>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="edit-file-actions col-xs-12 col-sm-7 col-lg-6 text-right pt-1">
                <a title="<?php echo lng('Back') ?>" class="btn btn-sm btn-outline-primary" href="?p=<?php echo urlencode(trim(FM_PATH)) ?>&amp;view=<?php echo urlencode($file) ?>"><i class="fa fa-reply-all"></i> <?php echo lng('Back') ?></a>
                <a title="<?php echo lng('BackUp') ?>" class="btn btn-sm btn-outline-primary" href="javascript:void(0);" onclick="backup('<?php echo urlencode(trim(FM_PATH)) ?>','<?php echo urlencode($file) ?>')"><i class="fa fa-database"></i> <?php echo lng('BackUp') ?></a>
                <?php if ($is_text) { ?>
                    <?php if ($isNormalEditor) { ?>
                        <a title="Advanced" class="btn btn-sm btn-outline-primary" href="?p=<?php echo urlencode(trim(FM_PATH)) ?>&amp;edit=<?php echo urlencode($file) ?>&amp;env=ace"><i class="fa fa-pencil-square-o"></i> <?php echo lng('AdvancedEditor') ?></a>
                        <button type="button" class="btn btn-sm btn-outline-primary" name="Save" data-url="<?php echo fm_enc($file_url) ?>" onclick="edit_save(this,'nrl')"><i class="fa fa-floppy-o"></i> Save
                        </button>
                    <?php } else { ?>
                        <a title="Plain Editor" class="btn btn-sm btn-outline-primary" href="?p=<?php echo urlencode(trim(FM_PATH)) ?>&amp;edit=<?php echo urlencode($file) ?>"><i class="fa fa-text-height"></i> <?php echo lng('NormalEditor') ?></a>
                        <button type="button" class="btn btn-sm btn-outline-primary" name="Save" data-url="<?php echo fm_enc($file_url) ?>" onclick="edit_save(this,'ace')"><i class="fa fa-floppy-o"></i> <?php echo lng('Save') ?>
                        </button>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
        <?php
        if ($is_text && $isNormalEditor) {
            echo '<textarea class="mt-2" id="normal-editor" rows="33" cols="120" style="width: 99.5%;">' . htmlspecialchars($content) . '</textarea>';
        } elseif ($is_text) {
            echo '<div id="editor" contenteditable="true">' . htmlspecialchars($content) . '</div>';
        } else {
            fm_set_msg(lng('FILE EXTENSION HAS NOT SUPPORTED'), 'error');
        }
        ?>
    </div>
    <?php
    fm_show_footer();
    exit;
}

// chmod (not for Windows)
if (isset($_GET['chmod']) && !FM_READONLY && !FM_IS_WIN) {
    $file = $_GET['chmod'];
    $file = fm_clean_path($file);
    $file = str_replace('/', '', $file);
    if ($file == '' || (!is_file($path . '/' . $file) && !is_dir($path . '/' . $file))) {
        fm_set_msg(lng('File not found'), 'error');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }

    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path

    $file_url = FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $file;
    $file_path = $path . '/' . $file;

    $mode = fileperms($path . '/' . $file);

    ?>
    <div class="path">
        <div class="card mb-2 <?php echo fm_get_theme(); ?>">
            <h6 class="card-header">
                <?php echo lng('ChangePermissions') ?>
            </h6>
            <div class="card-body">
                <p class="card-text">
                    Full path: <?php echo $file_path ?><br>
                </p>
                <form action="" method="post">
                    <input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
                    <input type="hidden" name="chmod" value="<?php echo fm_enc($file) ?>">

                    <table class="table compact-table <?php echo fm_get_theme(); ?>">
                        <tr>
                            <td></td>
                            <td><b><?php echo lng('Owner') ?></b></td>
                            <td><b><?php echo lng('Group') ?></b></td>
                            <td><b><?php echo lng('Other') ?></b></td>
                        </tr>
                        <tr>
                            <td style="text-align: right"><b><?php echo lng('Read') ?></b></td>
                            <td><label><input type="checkbox" name="ur" value="1"<?php echo ($mode & 00400) ? ' checked' : '' ?>></label></td>
                            <td><label><input type="checkbox" name="gr" value="1"<?php echo ($mode & 00040) ? ' checked' : '' ?>></label></td>
                            <td><label><input type="checkbox" name="or" value="1"<?php echo ($mode & 00004) ? ' checked' : '' ?>></label></td>
                        </tr>
                        <tr>
                            <td style="text-align: right"><b><?php echo lng('Write') ?></b></td>
                            <td><label><input type="checkbox" name="uw" value="1"<?php echo ($mode & 00200) ? ' checked' : '' ?>></label></td>
                            <td><label><input type="checkbox" name="gw" value="1"<?php echo ($mode & 00020) ? ' checked' : '' ?>></label></td>
                            <td><label><input type="checkbox" name="ow" value="1"<?php echo ($mode & 00002) ? ' checked' : '' ?>></label></td>
                        </tr>
                        <tr>
                            <td style="text-align: right"><b><?php echo lng('Execute') ?></b></td>
                            <td><label><input type="checkbox" name="ux" value="1"<?php echo ($mode & 00100) ? ' checked' : '' ?>></label></td>
                            <td><label><input type="checkbox" name="gx" value="1"<?php echo ($mode & 00010) ? ' checked' : '' ?>></label></td>
                            <td><label><input type="checkbox" name="ox" value="1"<?php echo ($mode & 00001) ? ' checked' : '' ?>></label></td>
                        </tr>
                    </table>

                    <p>
                        <button type="submit" class="btn btn-success"><i class="fa fa-check-circle"></i> <?php echo lng('Change') ?></button> &nbsp;
                        <b><a href="?p=<?php echo urlencode(FM_PATH) ?>" class="btn btn-outline-primary"><i class="fa fa-times-circle"></i> <?php echo lng('Cancel') ?></a></b>
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php
    fm_show_footer();
    exit;
}

//--- FILEMANAGER MAIN
fm_show_header(); // HEADER
fm_show_nav_path(FM_PATH); // current path

// messages
fm_show_message();

$num_files = count($files);
$num_folders = count($folders);
$all_files_size = 0;
$tableTheme = (FM_THEME == "dark") ? "text-white bg-dark table-dark" : "bg-white";
?>
<form action="" method="post" class="pt-3">
    <input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
    <input type="hidden" name="group" value="1">
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm <?php echo $tableTheme; ?>" id="main-table">
            <thead class="thead-white">
            <tr>
                <?php if (!FM_READONLY): ?>
                    <th style="width:3%" class="custom-checkbox-header">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="js-select-all-items" onclick="checkbox_toggle()">
                            <label class="custom-control-label" for="js-select-all-items"></label>
                        </div>
                    </th><?php endif; ?>
                <th><?php echo lng('Name') ?></th>
                <th><?php echo lng('Size') ?></th>
                <th><?php echo lng('Modified') ?></th>
                <?php if (!FM_IS_WIN && !$hide_Cols): ?>
                    <th><?php echo lng('Perms') ?></th>
                    <th><?php echo lng('Owner') ?></th><?php endif; ?>
                <th><?php echo lng('Actions') ?></th>
            </tr>
            </thead>
            <?php
            // link to parent folder
            if ($parent !== false) {
                ?>
                <tr><?php if (!FM_READONLY): ?>
                    <td class="nosort"></td><?php endif; ?>
                    <td class="border-0" data-sort><a href="?p=<?php echo urlencode($parent) ?>"><i class="fa fa-chevron-circle-left go-back"></i> ..</a></td>
                    <td class="border-0" data-order></td>
                    <td class="border-0" data-order></td>
                    <td class="border-0"></td>
                    <?php if (!FM_IS_WIN && !$hide_Cols) { ?>
                        <td class="border-0"></td>
                        <td class="border-0"></td>
                    <?php } ?>
                </tr>
                <?php
            }
            $ii = 3399;
            foreach ($folders as $f) {
                $is_link = is_link($path . '/' . $f);
                $img = $is_link ? 'icon-link_folder' : 'fa fa-folder-o';
                $modif_raw = filemtime($path . '/' . $f);
                $modif = date(FM_DATETIME_FORMAT, $modif_raw);
                if ($calc_folder) {
                    try {
                        $filesize_raw = fm_get_directorysize($path . '/' . $f);
                        $filesize = fm_get_filesize($filesize_raw);
                    } catch(Exception $e) {
                        $filesize_raw = "";
                        $filesize = lng('Folder');
                    }
                }
                else {
                    $filesize_raw = "";
                    $filesize = lng('Folder');
                }
                $perms = substr(decoct(fileperms($path . '/' . $f)), -4);
                if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
                    $owner = posix_getpwuid(fileowner($path . '/' . $f));
                    $group = posix_getgrgid(filegroup($path . '/' . $f));
                } else {
                    $owner = array('name' => '?');
                    $group = array('name' => '?');
                }
                ?>
                <tr>
                    <?php if (!FM_READONLY): ?>
                        <td class="custom-checkbox-td">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="<?php echo $ii ?>" name="file[]" value="<?php echo fm_enc($f) ?>">
                            <label class="custom-control-label" for="<?php echo $ii ?>"></label>
                        </div>
                        </td><?php endif; ?>
                    <td data-sort=<?php echo fm_convert_win(fm_enc($f)) ?>>
                        <div class="filename"><a href="?p=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?>"><i class="<?php echo $img ?>"></i> <?php echo fm_convert_win(fm_enc($f)) ?>
                            </a><?php echo($is_link ? ' &rarr; <i>' . readlink($path . '/' . $f) . '</i>' : '') ?></div>
                    </td>
                    <td data-order="a-<?php echo str_pad($filesize_raw, 18, "0", STR_PAD_LEFT);?>">
                        <?php echo $filesize; ?>
                    </td>
                    <td data-order="a-<?php echo $modif_raw;?>"><?php echo $modif ?></td>
                    <?php if (!FM_IS_WIN && !$hide_Cols): ?>
                        <td><?php if (!FM_READONLY): ?><a title="Change Permissions" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;chmod=<?php echo urlencode($f) ?>"><?php echo $perms ?></a><?php else: ?><?php echo $perms ?><?php endif; ?>
                        </td>
                        <td><?php echo $owner['name'] . ':' . $group['name'] ?></td>
                    <?php endif; ?>
                    <td class="inline-actions"><?php if (!FM_READONLY): ?>
                            <a title="<?php echo lng('Delete')?>" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;del=<?php echo urlencode($f) ?>" onclick="return confirm('<?php echo lng('Delete').' '.lng('Folder').'?'; ?>\n \n ( <?php echo urlencode($f) ?> )');"> <i class="fa fa-trash-o" aria-hidden="true"></i></a>
                            <a title="<?php echo lng('Rename')?>" href="#" onclick="rename('<?php echo fm_enc(addslashes(FM_PATH)) ?>', '<?php echo fm_enc(addslashes($f)) ?>');return false;"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
                            <a title="<?php echo lng('CopyTo')?>..." href="?p=&amp;copy=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?>"><i class="fa fa-files-o" aria-hidden="true"></i></a>
                        <?php endif; ?>
                        <!-- suppresion du direct link <a title="<?php echo lng('DirectLink')?>" href="<?php echo fm_enc(FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $f . '/') ?>" target="_blank"><i class="fa fa-link" aria-hidden="true"></i></a>-->
                    </td>
                </tr>
                <?php
                flush();
                $ii++;
            }
            $ik = 6070;
            foreach ($files as $f) {
                $is_link = is_link($path . '/' . $f);
                $img = $is_link ? 'fa fa-file-text-o' : fm_get_file_icon_class($path . '/' . $f);
                $modif_raw = filemtime($path . '/' . $f);
                $modif = date(FM_DATETIME_FORMAT, $modif_raw);
                $filesize_raw = fm_get_size($path . '/' . $f);
                $filesize = fm_get_filesize($filesize_raw);
                $filelink = '?p=' . urlencode(FM_PATH) . '&amp;view=' . urlencode($f);
                $all_files_size += $filesize_raw;
                $perms = substr(decoct(fileperms($path . '/' . $f)), -4);
                if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
                    $owner = posix_getpwuid(fileowner($path . '/' . $f));
                    $group = posix_getgrgid(filegroup($path . '/' . $f));
                } else {
                    $owner = array('name' => '?');
                    $group = array('name' => '?');
                }
                ?>
                <tr>
                    <?php if (!FM_READONLY): ?>
                        <td class="custom-checkbox-td">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="<?php echo $ik ?>" name="file[]" value="<?php echo fm_enc($f) ?>">
                            <label class="custom-control-label" for="<?php echo $ik ?>"></label>
                        </div>
                        </td><?php endif; ?>
                    <td data-sort=<?php echo fm_enc($f) ?>>
                        <div class="filename">
                        <?php
                           if (in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), array('gif', 'jpg', 'jpeg', 'png', 'bmp', 'ico', 'svg', 'webp', 'avif'))): ?>
                                <?php $imagePreview = fm_enc(FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $f); ?>
                                <a href="<?php echo $filelink ?>" data-preview-image="<?php echo $imagePreview ?>" title="<?php echo fm_enc($f) ?>">
                           <?php else: ?>
                                <a href="<?php echo $filelink ?>" title="<?php echo $f ?>">
                            <?php endif; ?>
                                    <i class="<?php echo $img ?>"></i> <?php echo fm_convert_win(fm_enc($f)) ?>
                                </a>
                                <?php echo($is_link ? ' &rarr; <i>' . readlink($path . '/' . $f) . '</i>' : '') ?>
                        </div>
                    </td>
                    <td data-order="b-<?php echo str_pad($filesize_raw, 18, "0", STR_PAD_LEFT); ?>"><span title="<?php printf('%s bytes', $filesize_raw) ?>">
                        <?php echo $filesize; ?>
                        </span></td>
                    <td data-order="b-<?php echo $modif_raw;?>"><?php echo $modif ?></td>
                    <?php if (!FM_IS_WIN && !$hide_Cols): ?>
                        <td><?php if (!FM_READONLY): ?><a title="<?php echo 'Change Permissions' ?>" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;chmod=<?php echo urlencode($f) ?>"><?php echo $perms ?></a><?php else: ?><?php echo $perms ?><?php endif; ?>
                        </td>
                        <td><?php echo fm_enc($owner['name'] . ':' . $group['name']) ?></td>
                    <?php endif; ?>
                    <td class="inline-actions">
                        <a title="<?php echo lng('Preview') ?>" href="<?php echo $filelink.'&quickView=1'; ?>" data-toggle="lightbox" data-gallery="tiny-gallery" data-title="<?php echo fm_convert_win(fm_enc($f)) ?>" data-max-width="100%" data-width="100%"><i class="fa fa-eye"></i></a>
                        <?php if (!FM_READONLY): ?>
                            <a title="<?php echo lng('Delete') ?>" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;del=<?php echo urlencode($f) ?>" onclick="return confirm('<?php echo lng('Delete').' '.lng('File').'?'; ?>\n \n ( <?php echo urlencode($f) ?> )');"> <i class="fa fa-trash-o"></i></a>
                            <a title="<?php echo lng('Rename') ?>" href="#" onclick="rename('<?php echo fm_enc(addslashes(FM_PATH)) ?>', '<?php echo fm_enc(addslashes($f)) ?>');return false;"><i class="fa fa-pencil-square-o"></i></a>
                            <a title="<?php echo lng('CopyTo') ?>..."
                               href="?p=<?php echo urlencode(FM_PATH) ?>&amp;copy=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?>"><i class="fa fa-files-o"></i></a>
                        <?php endif; ?>
                        <!--<a title="<?php echo lng('DirectLink') ?>" href="<?php echo fm_enc(FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $f) ?>" target="_blank"><i class="fa fa-link"></i></a> -->
                        <a title="<?php echo lng('Download') ?>" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;dl=<?php echo urlencode($f) ?>"><i class="fa fa-download"></i></a>
                    </td>
                </tr>
                <?php
                flush();
                $ik++;
            }

            if (empty($folders) && empty($files)) {
                ?>
                <tfoot>
                    <tr><?php if (!FM_READONLY): ?>
                            <td></td><?php endif; ?>
                        <td colspan="<?php echo (!FM_IS_WIN && !$hide_Cols) ? '6' : '4' ?>"><em><?php echo lng('Folder is empty') ?></em></td>
                    </tr>
                </tfoot>
                <?php
            } else {
                ?>
                <tfoot>
                    <tr><?php if (!FM_READONLY): ?>
                            <td class="gray"></td><?php endif; ?>
                        <td class="gray" colspan="<?php echo (!FM_IS_WIN && !$hide_Cols) ? '6' : '4' ?>">
                            <?php echo lng('FullSize').': <span class="badge badge-light">'.fm_get_filesize($all_files_size).'</span>' ?>
                            <?php echo lng('File').': <span class="badge badge-light">'.$num_files.'</span>' ?>
                            <?php echo lng('Folder').': <span class="badge badge-light">'.$num_folders.'</span>' ?>
                            <?php if (function_exists('disk_free_space') && function_exists('disk_total_space')) { ?>
                            <?php echo lng('PartitionSize').': <span class="badge badge-light">'.fm_get_filesize(@disk_free_space($path)) .'</span> '.lng('FreeOf').' <span class="badge badge-light">'.fm_get_filesize(@disk_total_space($path)).'</span>'; ?>
                            <?php } ?>
                        </td>
                    </tr>
                </tfoot>
                <?php
            }
            ?>
        </table>
    </div>

    <div class="row">
        <?php if (!FM_READONLY): ?>
        <div class="col-xs-12 col-sm-9">
            <ul class="list-inline footer-action">
                <li class="list-inline-item"> <a href="#/select-all" class="btn btn-small btn-outline-primary btn-2" onclick="select_all();return false;"><i class="fa fa-check-square"></i> <?php echo lng('SelectAll') ?> </a></li>
                <li class="list-inline-item"><a href="#/unselect-all" class="btn btn-small btn-outline-primary btn-2" onclick="unselect_all();return false;"><i class="fa fa-window-close"></i> <?php echo lng('UnSelectAll') ?> </a></li>
                <li class="list-inline-item"><a href="#/invert-all" class="btn btn-small btn-outline-primary btn-2" onclick="invert_all();return false;"><i class="fa fa-th-list"></i> <?php echo lng('InvertSelection') ?> </a></li>
                <li class="list-inline-item"><input type="submit" class="hidden" name="delete" id="a-delete" value="Delete" onclick="return confirm('<?php echo lng('Delete selected files and folders?'); ?>')">
                    <a href="javascript:document.getElementById('a-delete').click();" class="btn btn-small btn-outline-primary btn-2"><i class="fa fa-trash"></i> <?php echo lng('Delete') ?> </a></li>
                <!-- Suppression compression ZIP et TAR <li class="list-inline-item"><input type="submit" class="hidden" name="zip" id="a-zip" value="zip" onclick="return confirm('<?php echo lng('Create archive?'); ?>')">
                    <a href="javascript:document.getElementById('a-zip').click();" class="btn btn-small btn-outline-primary btn-2"><i class="fa fa-file-archive-o"></i> <?php echo lng('Zip') ?> </a></li>
                <li class="list-inline-item"><input type="submit" class="hidden" name="tar" id="a-tar" value="tar" onclick="return confirm('<?php echo lng('Create archive?'); ?>')">
                    <a href="javascript:document.getElementById('a-tar').click();" class="btn btn-small btn-outline-primary btn-2"><i class="fa fa-file-archive-o"></i> <?php echo lng('Tar') ?> </a></li>
                -->
                <li class="list-inline-item"><input type="submit" class="hidden" name="copy" id="a-copy" value="Copy">
                    <a href="javascript:document.getElementById('a-copy').click();" class="btn btn-small btn-outline-primary btn-2"><i class="fa fa-files-o"></i> <?php echo lng('Copy') ?> </a></li>
                <!-- liste MP3 -->	
                <li class="list-inline-item"><input type="submit" class="hidden" name="list" id="a-list" value="List">
	            <a href="javascript:document.getElementById('a-list').click();" class="btn btn-small btn-outline-primary btn-2"><i class="fa fa-th-list"></i> Liste MP3 </a></li>
            </ul>
        </div>
        <div class="col-12"><a href="https://github.com/christophe94700/AlexaPlayList" target="_blank" class="float-right text-muted">Alexa List MP3 <?php echo VERSION; ?></a></div>
        <?php endif; ?>
    </div>

</form>

<?php
fm_show_footer();

//--- END

// Functions

/**
 * Delete  file or folder (recursively)
 * @param string $path
 * @return bool
 */
function fm_rdelete($path)
{
    if (is_link($path)) {
        return unlink($path);
    } elseif (is_dir($path)) {
        $objects = scandir($path);
        $ok = true;
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (!fm_rdelete($path . '/' . $file)) {
                        $ok = false;
                    }
                }
            }
        }
        return ($ok) ? rmdir($path) : false;
    } elseif (is_file($path)) {
        return unlink($path);
    }
    return false;
}

/**
 * Recursive chmod
 * @param string $path
 * @param int $filemode
 * @param int $dirmode
 * @return bool
 * @todo Will use in mass chmod
 */
function fm_rchmod($path, $filemode, $dirmode)
{
    if (is_dir($path)) {
        if (!chmod($path, $dirmode)) {
            return false;
        }
        $objects = scandir($path);
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (!fm_rchmod($path . '/' . $file, $filemode, $dirmode)) {
                        return false;
                    }
                }
            }
        }
        return true;
    } elseif (is_link($path)) {
        return true;
    } elseif (is_file($path)) {
        return chmod($path, $filemode);
    }
    return false;
}

/**
 * Check the file extension which is allowed or not
 * @param string $filename
 * @return bool
 */
function fm_is_valid_ext($filename)
{
    $allowed = (FM_FILE_EXTENSION) ? explode(',', FM_FILE_EXTENSION) : false;

    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $isFileAllowed = ($allowed) ? in_array($ext, $allowed) : true;

    return ($isFileAllowed) ? true : false;
}

/**
 * Safely rename
 * @param string $old
 * @param string $new
 * @return bool|null
 */
function fm_rename($old, $new)
{
    // $isFileAllowed = fm_is_valid_ext($new); Bug to rename folder Christophe Caron

    // if(!$isFileAllowed) return false;


    return (!file_exists($new) && file_exists($old)) ? rename($old, $new) : null;
}

/**
 * Copy file or folder (recursively).
 * @param string $path
 * @param string $dest
 * @param bool $upd Update files
 * @param bool $force Create folder with same names instead file
 * @return bool
 */
function fm_rcopy($path, $dest, $upd = true, $force = true)
{
    if (is_dir($path)) {
        if (!fm_mkdir($dest, $force)) {
            return false;
        }
        $objects = scandir($path);
        $ok = true;
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (!fm_rcopy($path . '/' . $file, $dest . '/' . $file)) {
                        $ok = false;
                    }
                }
            }
        }
        return $ok;
    } elseif (is_file($path)) {
        return fm_copy($path, $dest, $upd);
    }
    return false;
}

/**
 * Safely create folder
 * @param string $dir
 * @param bool $force
 * @return bool
 */
function fm_mkdir($dir, $force)
{
    if (file_exists($dir)) {
        if (is_dir($dir)) {
            return $dir;
        } elseif (!$force) {
            return false;
        }
        unlink($dir);
    }
    return mkdir($dir, 0777, true);
}

/**
 * Safely copy file
 * @param string $f1
 * @param string $f2
 * @param bool $upd Indicates if file should be updated with new content
 * @return bool
 */
function fm_copy($f1, $f2, $upd)
{
    $time1 = filemtime($f1);
    if (file_exists($f2)) {
        $time2 = filemtime($f2);
        if ($time2 >= $time1 && $upd) {
            return false;
        }
    }
    $ok = copy($f1, $f2);
    if ($ok) {
        touch($f2, $time1);
    }
    return $ok;
}

/**
 * Get mime type
 * @param string $file_path
 * @return mixed|string
 */
function fm_get_mime_type($file_path)
{
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        return $mime;
    } elseif (function_exists('mime_content_type')) {
        return mime_content_type($file_path);
    } elseif (!stristr(ini_get('disable_functions'), 'shell_exec')) {
        $file = escapeshellarg($file_path);
        $mime = shell_exec('file -bi ' . $file);
        return $mime;
    } else {
        return '--';
    }
}

/**
 * HTTP Redirect
 * @param string $url
 * @param int $code
 */
function fm_redirect($url, $code = 302)
{
    header('Location: ' . $url, true, $code);
    exit;
}

/**
 * Path traversal prevention and clean the url
 * It replaces (consecutive) occurrences of / and \\ with whatever is in DIRECTORY_SEPARATOR, and processes /. and /.. fine.
 * @param $path
 * @return string
 */
function get_absolute_path($path) {
    $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
    $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
    $absolutes = array();
    foreach ($parts as $part) {
        if ('.' == $part) continue;
        if ('..' == $part) {
            array_pop($absolutes);
        } else {
            $absolutes[] = $part;
        }
    }
    return implode(DIRECTORY_SEPARATOR, $absolutes);
}

/**
 * Clean path
 * @param string $path
 * @return string
 */
function fm_clean_path($path, $trim = true)
{
    $path = $trim ? trim($path) : $path;
    $path = trim($path, '\\/');
    $path = str_replace(array('../', '..\\'), '', $path);
    $path =  get_absolute_path($path);
    if ($path == '..') {
        $path = '';
    }
    return str_replace('\\', '/', $path);
}

/**
 * Get parent path
 * @param string $path
 * @return bool|string
 */
function fm_get_parent_path($path)
{
    $path = fm_clean_path($path);
    if ($path != '') {
        $array = explode('/', $path);
        if (count($array) > 1) {
            $array = array_slice($array, 0, -1);
            return implode('/', $array);
        }
        return '';
    }
    return false;
}

/**
 * Check file is in exclude list
 * @param string $file
 * @return bool
 */
function fm_is_exclude_items($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (isset($exclude_items) and sizeof($exclude_items)) {
        unset($exclude_items);
    }

    $exclude_items = FM_EXCLUDE_ITEMS;
    if (version_compare(PHP_VERSION, '7.0.0', '<')) {
        $exclude_items = unserialize($exclude_items);
    }
    if (!in_array($file, $exclude_items) && !in_array("*.$ext", $exclude_items)) {
        return true;
    }
    return false;
}

/**
 * get language translations from json file
 * @param int $tr
 * @return array
 */
function fm_get_translations($tr) {
    try {
        $content = @file_get_contents('translation.json');
        if($content !== FALSE) {
            $lng = json_decode($content, TRUE);
            global $lang_list;
            foreach ($lng["language"] as $key => $value)
            {
                $code = $value["code"];
                $lang_list[$code] = $value["name"];
                if ($tr)
                    $tr[$code] = $value["translation"];
            }
            return $tr;
        }

    }
    catch (Exception $e) {
        echo $e;
    }
}

/**
 * @param $file
 * Recover all file sizes larger than > 2GB.
 * Works on php 32bits and 64bits and supports linux
 * @return int|string
 */
function fm_get_size($file)
{
    static $iswin;
    static $isdarwin;
    if (!isset($iswin)) {
        $iswin = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
    }
    if (!isset($isdarwin)) {
        $isdarwin = (strtoupper(substr(PHP_OS, 0)) == "DARWIN");
    }

    static $exec_works;
    if (!isset($exec_works)) {
        $exec_works = (function_exists('exec') && !ini_get('safe_mode') && @exec('echo EXEC') == 'EXEC');
    }

    // try a shell command
    if ($exec_works) {
        $arg = escapeshellarg($file);
        $cmd = ($iswin) ? "for %F in (\"$file\") do @echo %~zF" : ($isdarwin ? "stat -f%z $arg" : "stat -c%s $arg");
        @exec($cmd, $output);
        if (is_array($output) && ctype_digit($size = trim(implode("\n", $output)))) {
            return $size;
        }
    }

    // try the Windows COM interface
    if ($iswin && class_exists("COM")) {
        try {
            $fsobj = new COM('Scripting.FileSystemObject');
            $f = $fsobj->GetFile( realpath($file) );
            $size = $f->Size;
        } catch (Exception $e) {
            $size = null;
        }
        if (ctype_digit($size)) {
            return $size;
        }
    }

    // if all else fails
    return filesize($file);
}

/**
 * Get nice filesize
 * @param int $size
 * @return string
 */
function fm_get_filesize($size)
{
    $size = (float) $size;
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $power = ($size > 0) ? floor(log($size, 1024)) : 0;
    $power = ($power > (count($units) - 1)) ? (count($units) - 1) : $power;
    return sprintf('%s %s', round($size / pow(1024, $power), 2), $units[$power]);
}

/**
 * Get total size of directory tree.
 *
 * @param  string $directory Relative or absolute directory name.
 * @return int Total number of bytes.
 */
function fm_get_directorysize($directory) {
    $bytes = 0;
    $directory = realpath($directory);
    if ($directory !== false && $directory != '' && file_exists($directory)){
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file){
            $bytes += $file->getSize();
        }
    }
    return $bytes;
}

/**
 * Get info about zip archive
 * @param string $path
 * @return array|bool
 */
function fm_get_zif_info($path, $ext) {
    if ($ext == 'zip' && function_exists('zip_open')) {
        $arch = zip_open($path);
        if ($arch) {
            $filenames = array();
            while ($zip_entry = zip_read($arch)) {
                $zip_name = zip_entry_name($zip_entry);
                $zip_folder = substr($zip_name, -1) == '/';
                $filenames[] = array(
                    'name' => $zip_name,
                    'filesize' => zip_entry_filesize($zip_entry),
                    'compressed_size' => zip_entry_compressedsize($zip_entry),
                    'folder' => $zip_folder
                    //'compression_method' => zip_entry_compressionmethod($zip_entry),
                );
            }
            zip_close($arch);
            return $filenames;
        }
    } elseif($ext == 'tar' && class_exists('PharData')) {
        $archive = new PharData($path);
        $filenames = array();
        foreach(new RecursiveIteratorIterator($archive) as $file) {
            $parent_info = $file->getPathInfo();
            $zip_name = str_replace("phar://".$path, '', $file->getPathName());
            $zip_name = substr($zip_name, ($pos = strpos($zip_name, '/')) !== false ? $pos + 1 : 0);
            $zip_folder = $parent_info->getFileName();
            $zip_info = new SplFileInfo($file);
            $filenames[] = array(
                'name' => $zip_name,
                'filesize' => $zip_info->getSize(),
                'compressed_size' => $file->getCompressedSize(),
                'folder' => $zip_folder
            );
        }
        return $filenames;
    }
    return false;
}

/**
 * Encode html entities
 * @param string $text
 * @return string
 */
function fm_enc($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Prevent XSS attacks
 * @param string $text
 * @return string
 */
function fm_isvalid_filename($text) {
    return (strpbrk($text, '/?%*:|"<>') === FALSE) ? true : false;
}

/**
 * Save message in session
 * @param string $msg
 * @param string $status
 */
function fm_set_msg($msg, $status = 'ok')
{
    $_SESSION[FM_SESSION_ID]['message'] = $msg;
    $_SESSION[FM_SESSION_ID]['status'] = $status;
}

/**
 * Check if string is in UTF-8
 * @param string $string
 * @return int
 */
function fm_is_utf8($string)
{
    return preg_match('//u', $string);
}

/**
 * Convert file name to UTF-8 in Windows
 * @param string $filename
 * @return string
 */
function fm_convert_win($filename)
{
    if (FM_IS_WIN && function_exists('iconv')) {
        $filename = iconv(FM_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $filename);
    }
    return $filename;
}

/**
 * @param $obj
 * @return array
 */
function fm_object_to_array($obj)
{
    if (!is_object($obj) && !is_array($obj)) {
        return $obj;
    }
    if (is_object($obj)) {
        $obj = get_object_vars($obj);
    }
    return array_map('fm_object_to_array', $obj);
}

/**
 * Get CSS classname for file
 * @param string $path
 * @return string
 */
function fm_get_file_icon_class($path)
{
    // get extension
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    switch ($ext) {
        case 'ico':
        case 'gif':
        case 'jpg':
        case 'jpeg':
        case 'jpc':
        case 'jp2':
        case 'jpx':
        case 'xbm':
        case 'wbmp':
        case 'png':
        case 'bmp':
        case 'tif':
        case 'tiff':
        case 'webp':
        case 'avif':
        case 'svg':
            $img = 'fa fa-picture-o';
            break;
        case 'passwd':
        case 'ftpquota':
        case 'sql':
        case 'js':
        case 'json':
        case 'sh':
        case 'config':
        case 'twig':
        case 'tpl':
        case 'md':
        case 'gitignore':
        case 'c':
        case 'cpp':
        case 'cs':
        case 'py':
        case 'rs':
        case 'map':
        case 'lock':
        case 'dtd':
            $img = 'fa fa-file-code-o';
            break;
        case 'txt':
        case 'ini':
        case 'conf':
        case 'log':
        case 'htaccess':
        case 'yaml':
        case 'yml':
        case 'toml':
            $img = 'fa fa-file-text-o';
            break;
        case 'css':
        case 'less':
        case 'sass':
        case 'scss':
            $img = 'fa fa-css3';
            break;
        case 'bz2':
        case 'zip':
        case 'rar':
        case 'gz':
        case 'tar':
        case '7z':
        case 'xz':
            $img = 'fa fa-file-archive-o';
            break;
        case 'php':
        case 'php4':
        case 'php5':
        case 'phps':
        case 'phtml':
            $img = 'fa fa-code';
            break;
        case 'htm':
        case 'html':
        case 'shtml':
        case 'xhtml':
            $img = 'fa fa-html5';
            break;
        case 'xml':
        case 'xsl':
            $img = 'fa fa-file-excel-o';
            break;
        case 'wav':
        case 'mp3':
        case 'mp2':
        case 'm4a':
        case 'aac':
        case 'ogg':
        case 'oga':
        case 'wma':
        case 'mka':
        case 'flac':
        case 'ac3':
        case 'tds':
            $img = 'fa fa-music';
            break;
        case 'm3u':
        case 'm3u8':
        case 'pls':
        case 'cue':
        case 'xspf':
            $img = 'fa fa-headphones';
            break;
        case 'avi':
        case 'mpg':
        case 'mpeg':
        case 'mp4':
        case 'm4v':
        case 'flv':
        case 'f4v':
        case 'ogm':
        case 'ogv':
        case 'mov':
        case 'mkv':
        case '3gp':
        case 'asf':
        case 'wmv':
        case 'webm':
            $img = 'fa fa-file-video-o';
            break;
        case 'eml':
        case 'msg':
            $img = 'fa fa-envelope-o';
            break;
        case 'xls':
        case 'xlsx':
        case 'ods':
            $img = 'fa fa-file-excel-o';
            break;
        case 'csv':
            $img = 'fa fa-file-text-o';
            break;
        case 'bak':
        case 'swp':
            $img = 'fa fa-clipboard';
            break;
        case 'doc':
        case 'docx':
        case 'odt':
            $img = 'fa fa-file-word-o';
            break;
        case 'ppt':
        case 'pptx':
            $img = 'fa fa-file-powerpoint-o';
            break;
        case 'ttf':
        case 'ttc':
        case 'otf':
        case 'woff':
        case 'woff2':
        case 'eot':
        case 'fon':
            $img = 'fa fa-font';
            break;
        case 'pdf':
            $img = 'fa fa-file-pdf-o';
            break;
        case 'psd':
        case 'ai':
        case 'eps':
        case 'fla':
        case 'swf':
            $img = 'fa fa-file-image-o';
            break;
        case 'exe':
        case 'msi':
            $img = 'fa fa-file-o';
            break;
        case 'bat':
            $img = 'fa fa-terminal';
            break;
        default:
            $img = 'fa fa-info-circle';
    }

    return $img;
}

/**
 * Get image files extensions
 * @return array
 */
function fm_get_image_exts()
{
    return array('ico', 'gif', 'jpg', 'jpeg', 'jpc', 'jp2', 'jpx', 'xbm', 'wbmp', 'png', 'bmp', 'tif', 'tiff', 'psd', 'svg', 'webp', 'avif');
}

/**
 * Get video files extensions
 * @return array
 */
function fm_get_video_exts()
{
    return array('avi', 'webm', 'wmv', 'mp4', 'm4v', 'ogm', 'ogv', 'mov', 'mkv');
}

/**
 * Get audio files extensions
 * @return array
 */
function fm_get_audio_exts()
{
    return array('wav', 'mp3', 'ogg', 'm4a');
}

/**
 * Get text file extensions
 * @return array
 */
function fm_get_text_exts()
{
    return array(
        'txt', 'css', 'ini', 'conf', 'log', 'htaccess', 'passwd', 'ftpquota', 'sql', 'js', 'json', 'sh', 'config',
        'php', 'php4', 'php5', 'phps', 'phtml', 'htm', 'html', 'shtml', 'xhtml', 'xml', 'xsl', 'm3u', 'm3u8', 'pls', 'cue',
        'eml', 'msg', 'csv', 'bat', 'twig', 'tpl', 'md', 'gitignore', 'less', 'sass', 'scss', 'c', 'cpp', 'cs', 'py',
        'map', 'lock', 'dtd', 'svg', 'scss', 'asp', 'aspx', 'asx', 'asmx', 'ashx', 'jsx', 'jsp', 'jspx', 'cfm', 'cgi',
        'yml', 'yaml', 'toml'
    );
}

/**
 * Get mime types of text files
 * @return array
 */
function fm_get_text_mimes()
{
    return array(
        'application/xml',
        'application/javascript',
        'application/x-javascript',
        'image/svg+xml',
        'message/rfc822',
        'application/json',
    );
}

/**
 * Get file names of text files w/o extensions
 * @return array
 */
function fm_get_text_names()
{
    return array(
        'license',
        'readme',
        'authors',
        'contributors',
        'changelog',
    );
}

/**
 * Get online docs viewer supported files extensions
 * @return array
 */
function fm_get_onlineViewer_exts()
{
    return array('doc', 'docx', 'xls', 'xlsx', 'pdf', 'ppt', 'pptx', 'ai', 'psd', 'dxf', 'xps', 'rar', 'odt', 'ods');
}

function fm_get_file_mimes($extension)
{
    $fileTypes['swf'] = 'application/x-shockwave-flash';
    $fileTypes['pdf'] = 'application/pdf';
    $fileTypes['exe'] = 'application/octet-stream';
    $fileTypes['zip'] = 'application/zip';
    $fileTypes['doc'] = 'application/msword';
    $fileTypes['xls'] = 'application/vnd.ms-excel';
    $fileTypes['ppt'] = 'application/vnd.ms-powerpoint';
    $fileTypes['gif'] = 'image/gif';
    $fileTypes['png'] = 'image/png';
    $fileTypes['jpeg'] = 'image/jpg';
    $fileTypes['jpg'] = 'image/jpg';
    $fileTypes['webp'] = 'image/webp';
    $fileTypes['avif'] = 'image/avif';
    $fileTypes['rar'] = 'application/rar';

    $fileTypes['ra'] = 'audio/x-pn-realaudio';
    $fileTypes['ram'] = 'audio/x-pn-realaudio';
    $fileTypes['ogg'] = 'audio/x-pn-realaudio';

    $fileTypes['wav'] = 'video/x-msvideo';
    $fileTypes['wmv'] = 'video/x-msvideo';
    $fileTypes['avi'] = 'video/x-msvideo';
    $fileTypes['asf'] = 'video/x-msvideo';
    $fileTypes['divx'] = 'video/x-msvideo';

    $fileTypes['mp3'] = 'audio/mpeg';
    $fileTypes['mp4'] = 'audio/mpeg';
    $fileTypes['mpeg'] = 'video/mpeg';
    $fileTypes['mpg'] = 'video/mpeg';
    $fileTypes['mpe'] = 'video/mpeg';
    $fileTypes['mov'] = 'video/quicktime';
    $fileTypes['swf'] = 'video/quicktime';
    $fileTypes['3gp'] = 'video/quicktime';
    $fileTypes['m4a'] = 'video/quicktime';
    $fileTypes['aac'] = 'video/quicktime';
    $fileTypes['m3u'] = 'video/quicktime';

    $fileTypes['php'] = ['application/x-php'];
    $fileTypes['html'] = ['text/html'];
    $fileTypes['txt'] = ['text/plain'];
    //Unknown mime-types should be 'application/octet-stream'
    if(empty($fileTypes[$extension])) {
      $fileTypes[$extension] = ['application/octet-stream'];
    }
    return $fileTypes[$extension];
}

/**
 * This function scans the files and folder recursively, and return matching files
 * @param string $dir
 * @param string $filter
 * @return json
 */
 function scan($dir, $filter = '') {
    $path = FM_ROOT_PATH.'/'.$dir;
     if($dir) {
         $ite = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
         $rii = new RegexIterator($ite, "/(" . $filter . ")/i");

         $files = array();
         foreach ($rii as $file) {
             if (!$file->isDir()) {
                 $fileName = $file->getFilename();
                 $location = str_replace(FM_ROOT_PATH, '', $file->getPath());
                 $files[] = array(
                     "name" => $fileName,
                     "type" => "file",
                     "path" => $location,
                 );
             }
         }
         return $files;
     }
}

/*
Parameters: downloadFile(File Location, File Name,
max speed, is streaming
If streaming - videos will show as videos, images as images
instead of download prompt
https://stackoverflow.com/a/13821992/1164642
*/

function fm_download_file($fileLocation, $fileName, $chunkSize  = 1024)
{
    if (connection_status() != 0)
        return (false);
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);

    $contentType = fm_get_file_mimes($extension);

    if(is_array($contentType)) {
        $contentType = implode(' ', $contentType);
    }

    header("Cache-Control: public");
    header("Content-Transfer-Encoding: binary\n");
    header("Content-Type: $contentType");

    $contentDisposition = 'attachment';


    if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
        $fileName = preg_replace('/\./', '%2e', $fileName, substr_count($fileName, '.') - 1);
        header("Content-Disposition: $contentDisposition;filename=\"$fileName\"");
    } else {
        header("Content-Disposition: $contentDisposition;filename=\"$fileName\"");
    }

    header("Accept-Ranges: bytes");
    $range = 0;
    $size = filesize($fileLocation);

    if (isset($_SERVER['HTTP_RANGE'])) {
        list($a, $range) = explode("=", $_SERVER['HTTP_RANGE']);
        str_replace($range, "-", $range);
        $size2 = $size - 1;
        $new_length = $size - $range;
        header("HTTP/1.1 206 Partial Content");
        header("Content-Length: $new_length");
        header("Content-Range: bytes $range$size2/$size");
    } else {
        $size2 = $size - 1;
        header("Content-Range: bytes 0-$size2/$size");
        header("Content-Length: " . $size);
    }

    if ($size == 0) {
        die('Zero byte file! Aborting download');
    }
    @ini_set('magic_quotes_runtime', 0);
    $fp = fopen("$fileLocation", "rb");

    fseek($fp, $range);

    while (!feof($fp) and (connection_status() == 0)) {
        set_time_limit(0);
        print(@fread($fp, 1024*$chunkSize));
        flush();
        ob_flush();
        // sleep(1);
    }
    fclose($fp);

    return ((connection_status() == 0) and !connection_aborted());
}

function fm_get_theme() {
    $result = '';
    if(FM_THEME == "dark") {
        $result = "text-white bg-dark";
    }
    return $result;
}

/**
 * Class to work with zip files (using ZipArchive)
 */
class FM_Zipper
{
    private $zip;

    public function __construct()
    {
        $this->zip = new ZipArchive();
    }

    /**
     * Create archive with name $filename and files $files (RELATIVE PATHS!)
     * @param string $filename
     * @param array|string $files
     * @return bool
     */
    public function create($filename, $files)
    {
        $res = $this->zip->open($filename, ZipArchive::CREATE);
        if ($res !== true) {
            return false;
        }
        if (is_array($files)) {
            foreach ($files as $f) {
                if (!$this->addFileOrDir($f)) {
                    $this->zip->close();
                    return false;
                }
            }
            $this->zip->close();
            return true;
        } else {
            if ($this->addFileOrDir($files)) {
                $this->zip->close();
                return true;
            }
            return false;
        }
    }

    /**
     * Extract archive $filename to folder $path (RELATIVE OR ABSOLUTE PATHS)
     * @param string $filename
     * @param string $path
     * @return bool
     */
    public function unzip($filename, $path)
    {
        $res = $this->zip->open($filename);
        if ($res !== true) {
            return false;
        }
        if ($this->zip->extractTo($path)) {
            $this->zip->close();
            return true;
        }
        return false;
    }

    /**
     * Add file/folder to archive
     * @param string $filename
     * @return bool
     */
    private function addFileOrDir($filename)
    {
        if (is_file($filename)) {
            return $this->zip->addFile($filename);
        } elseif (is_dir($filename)) {
            return $this->addDir($filename);
        }
        return false;
    }

    /**
     * Add folder recursively
     * @param string $path
     * @return bool
     */
    private function addDir($path)
    {
        if (!$this->zip->addEmptyDir($path)) {
            return false;
        }
        $objects = scandir($path);
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($path . '/' . $file)) {
                        if (!$this->addDir($path . '/' . $file)) {
                            return false;
                        }
                    } elseif (is_file($path . '/' . $file)) {
                        if (!$this->zip->addFile($path . '/' . $file)) {
                            return false;
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }
}

/**
 * Class to work with Tar files (using PharData)
 */
class FM_Zipper_Tar
{
    private $tar;

    public function __construct()
    {
        $this->tar = null;
    }

    /**
     * Create archive with name $filename and files $files (RELATIVE PATHS!)
     * @param string $filename
     * @param array|string $files
     * @return bool
     */
    public function create($filename, $files)
    {
        $this->tar = new PharData($filename);
        if (is_array($files)) {
            foreach ($files as $f) {
                if (!$this->addFileOrDir($f)) {
                    return false;
                }
            }
            return true;
        } else {
            if ($this->addFileOrDir($files)) {
                return true;
            }
            return false;
        }
    }

    /**
     * Extract archive $filename to folder $path (RELATIVE OR ABSOLUTE PATHS)
     * @param string $filename
     * @param string $path
     * @return bool
     */
    public function unzip($filename, $path)
    {
        $res = $this->tar->open($filename);
        if ($res !== true) {
            return false;
        }
        if ($this->tar->extractTo($path)) {
            return true;
        }
        return false;
    }

    /**
     * Add file/folder to archive
     * @param string $filename
     * @return bool
     */
    private function addFileOrDir($filename)
    {
        if (is_file($filename)) {
            try {
                $this->tar->addFile($filename);
                return true;
            } catch (Exception $e) {
                return false;
            }
        } elseif (is_dir($filename)) {
            return $this->addDir($filename);
        }
        return false;
    }

    /**
     * Add folder recursively
     * @param string $path
     * @return bool
     */
    private function addDir($path)
    {
        $objects = scandir($path);
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($path . '/' . $file)) {
                        if (!$this->addDir($path . '/' . $file)) {
                            return false;
                        }
                    } elseif (is_file($path . '/' . $file)) {
                        try {
                            $this->tar->addFile($path . '/' . $file);
                        } catch (Exception $e) {
                            return false;
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }
}



/**
 * Save Configuration
 */
 class FM_Config
{
     var $data;

    function __construct()
    {
        global $root_path, $root_url, $CONFIG;
        $fm_url = $root_url.$_SERVER["PHP_SELF"];
        $this->data = array(
            'lang' => 'en',
            'error_reporting' => true,
            'show_hidden' => true
        );
        $data = false;
        if (strlen($CONFIG)) {
            $data = fm_object_to_array(json_decode($CONFIG));
        } else {
            $msg = 'Alexa List MP3<br>Error: Cannot load configuration';
            if (substr($fm_url, -1) == '/') {
                $fm_url = rtrim($fm_url, '/');
                $msg .= '<br>';
                $msg .= '<br>Seems like you have a trailing slash on the URL.';
                $msg .= '<br>Try this link: <a href="' . $fm_url . '">' . $fm_url . '</a>';
            }
            die($msg);
        }
        if (is_array($data) && count($data)) $this->data = $data;
        else $this->save();
    }

    function save()
    {
        $fm_file = __DIR__.'/config.php'; // Sauvegarde dan le fichier config.php
        $var_name = '$CONFIG';
        $var_value = var_export(json_encode($this->data), true);
        $config_string = "<?php" . chr(13) . chr(10) . "//Default Configuration".chr(13) . chr(10)."$var_name = $var_value;" . chr(13) . chr(10);
        if (is_writable($fm_file)) {
            $lines = file($fm_file);
            if ($fh = @fopen($fm_file, "w")) {
                @fputs($fh, $config_string, strlen($config_string));
                for ($x = 3; $x < count($lines); $x++) {
                    @fputs($fh, $lines[$x], strlen($lines[$x]));
                }
                @fclose($fh);
            }
        }
    }
}



//--- templates functions

/**
 * Show nav block
 * @param string $path
 */
function fm_show_nav_path($path)
{
    global $lang, $sticky_navbar, $editFile;
    $isStickyNavBar = $sticky_navbar ? 'fixed-top' : '';
    $getTheme = fm_get_theme();
    $getTheme .= " navbar-light";
    if(FM_THEME == "dark") {
        $getTheme .= " navbar-dark";
    } else {
        $getTheme .= " bg-white";
    }
    ?>
    <nav class="navbar navbar-expand-lg <?php echo $getTheme; ?> mb-4 main-nav <?php echo $isStickyNavBar ?>">
        <a class="navbar-brand" href=""> <?php echo lng('AppTitle') ?> </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">

            <?php
            $path = fm_clean_path($path);
            $root_url = "<a href='?p='><i class='fa fa-home' aria-hidden='true' title='" . FM_ROOT_PATH . "'></i></a>";
            $sep = '<i class="bread-crumb"> / </i>';
            if ($path != '') {
                $exploded = explode('/', $path);
                $count = count($exploded);
                $array = array();
                $parent = '';
                for ($i = 0; $i < $count; $i++) {
                    $parent = trim($parent . '/' . $exploded[$i], '/');
                    $parent_enc = urlencode($parent);
                    $array[] = "<a href='?p={$parent_enc}'>" . fm_enc(fm_convert_win($exploded[$i])) . "</a>";
                }
                $root_url .= $sep . implode($sep, $array);
            }
            echo '<div class="col-xs-6 col-sm-5">' . $root_url . $editFile . '</div>';
            ?>

            <div class="col-xs-6 col-sm-7 text-right">
                <ul class="navbar-nav mr-auto float-right <?php echo fm_get_theme();  ?>">
                    <li class="nav-item mr-2">
                        <div class="input-group input-group-sm mr-1" style="margin-top:4px;">
                            <input type="text" class="form-control" placeholder="<?php echo lng('Search') ?>" aria-label="<?php echo lng('Search') ?>" aria-describedby="search-addon2" id="search-addon">
                            <div class="input-group-append">
                                <span class="input-group-text" id="search-addon2"><i class="fa fa-search"></i></span>
                            </div>
                            <div class="input-group-append btn-group">
                                <span class="input-group-text dropdown-toggle" id="search-addon2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></span>
                                  <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="<?php echo $path2 = $path ? $path : '.'; ?>" id="js-search-modal" data-toggle="modal" data-target="#searchModal"><?php echo lng('Advanced Search') ?></a>
                                  </div>
                            </div>
                        </div>
                    </li>
                    <?php if (!FM_READONLY): ?>
                    <li class="nav-item">
                        <a title="<?php echo lng('Upload') ?>" class="nav-link" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;upload"><i class="fa fa-cloud-upload" aria-hidden="true"></i> <?php echo lng('Upload') ?></a>
                    </li>
                    <!-- Lecteur MP3 -->
                    <li class="nav-item">
	                    <a title="<?php echo lng('Player') ?>" class="nav-link" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;player"><i class="fa fa-play-circle" aria-hidden="true"></i> <?php echo lng('Player') ?></a>
	                </li>
                    <li class="nav-item">
                        <a title="<?php echo lng('NewItem') ?>" class="nav-link" href="#createNewItem" data-toggle="modal" data-target="#createNewItem"><i class="fa fa-plus-square"></i> <?php echo lng('NewItem') ?></a>
                    </li>
                    <?php endif; ?>
                    <?php if (FM_USE_AUTH): ?>
                    <li class="nav-item avatar dropdown">
                        <a class="nav-link dropdown-toggle" id="navbarDropdownMenuLink-5" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-user-circle"></i> <?php if(isset($_SESSION[FM_SESSION_ID]['logged'])) { echo $_SESSION[FM_SESSION_ID]['logged']; } ?></a>
                        <div class="dropdown-menu dropdown-menu-right <?php echo fm_get_theme(); ?>" aria-labelledby="navbarDropdownMenuLink-5">
                            <?php if (!FM_READONLY): ?>
                            <a title="<?php echo lng('Settings') ?>" class="dropdown-item nav-link" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;settings=1"><i class="fa fa-cog" aria-hidden="true"></i> <?php echo lng('Settings') ?></a>
                            <?php endif ?>
                            <a title="<?php echo lng('Help') ?>" class="dropdown-item nav-link" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;help=2"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> <?php echo lng('Help') ?></a>
                            <a title="<?php echo lng('Logout') ?>" class="dropdown-item nav-link" href="?logout=1"><i class="fa fa-sign-out" aria-hidden="true"></i> <?php echo lng('Logout') ?></a>
                        </div>
                    </li>
                    <?php else: ?>
                        <?php if (!FM_READONLY): ?>
                            <li class="nav-item">
                                <a title="<?php echo lng('Settings') ?>" class="dropdown-item nav-link" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;settings=1"><i class="fa fa-cog" aria-hidden="true"></i> <?php echo lng('Settings') ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <?php
}

/**
 * Show message from session
 */
function fm_show_message()
{
    if (isset($_SESSION[FM_SESSION_ID]['message'])) {
        $class = isset($_SESSION[FM_SESSION_ID]['status']) ? $_SESSION[FM_SESSION_ID]['status'] : 'ok';
        echo '<p class="message ' . $class . '">' . $_SESSION[FM_SESSION_ID]['message'] . '</p>';
        unset($_SESSION[FM_SESSION_ID]['message']);
        unset($_SESSION[FM_SESSION_ID]['status']);
    }
}

/**
 * Show page header in Login Form
 */
function fm_show_header_login()
{
$sprites_ver = '20160315';
header("Content-Type: text/html; charset=utf-8");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache");

global $lang, $root_url, $favicon_path;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Web based File Manager in PHP, Manage your files and generate list MU3 for Alexa">
    <meta name="author" content="Christophe Caron">
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex">
    <?php if($favicon_path) { echo '<link rel="icon" href="'.fm_enc($favicon_path).'" type="image/png">'; } ?>
    <title><?php echo fm_enc(APP_TITLE) ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body.fm-login-page{ background-color:#f7f9fb;font-size:14px;background-color:#f7f9fb;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400' viewBox='0 0 200 200'%3E%3Cg fill='none' stroke='%237F3' stroke-width='1' stroke-opacity='0.2'%3E%3Crect x='-40' y='40' width='75' height='75'/%3E%3Crect x='-35' y='45' width='65' height='65'/%3E%3Crect x='-30' y='50' width='55' height='55'/%3E%3Crect x='-25' y='55' width='45' height='45'/%3E%3Crect x='-20' y='60' width='35' height='35'/%3E%3Crect x='-15' y='65' width='25' height='25'/%3E%3Crect x='-10' y='70' width='15' height='15'/%3E%3Crect x='-5' y='75' width='5' height='5'/%3E%3Crect width='35' height='35'/%3E%3Crect x='5' y='5' width='25' height='25'/%3E%3Crect x='10' y='10' width='15' height='15'/%3E%3Crect x='15' y='15' width='5' height='5'/%3E%3Crect x='40' width='75' height='75'/%3E%3Crect x='45' y='5' width='65' height='65'/%3E%3Crect x='50' y='10' width='55' height='55'/%3E%3Crect x='55' y='15' width='45' height='45'/%3E%3Crect x='60' y='20' width='35' height='35'/%3E%3Crect x='65' y='25' width='25' height='25'/%3E%3Crect x='70' y='30' width='15' height='15'/%3E%3Crect x='75' y='35' width='5' height='5'/%3E%3Crect x='40' y='80' width='35' height='35'/%3E%3Crect x='45' y='85' width='25' height='25'/%3E%3Crect x='50' y='90' width='15' height='15'/%3E%3Crect x='55' y='95' width='5' height='5'/%3E%3Crect x='120' y='-40' width='75' height='75'/%3E%3Crect x='125' y='-35' width='65' height='65'/%3E%3Crect x='130' y='-30' width='55' height='55'/%3E%3Crect x='135' y='-25' width='45' height='45'/%3E%3Crect x='140' y='-20' width='35' height='35'/%3E%3Crect x='145' y='-15' width='25' height='25'/%3E%3Crect x='150' y='-10' width='15' height='15'/%3E%3Crect x='155' y='-5' width='5' height='5'/%3E%3Crect x='120' y='40' width='35' height='35'/%3E%3Crect x='125' y='45' width='25' height='25'/%3E%3Crect x='130' y='50' width='15' height='15'/%3E%3Crect x='135' y='55' width='5' height='5'/%3E%3Crect y='120' width='75' height='75'/%3E%3Crect x='5' y='125' width='65' height='65'/%3E%3Crect x='10' y='130' width='55' height='55'/%3E%3Crect x='15' y='135' width='45' height='45'/%3E%3Crect x='20' y='140' width='35' height='35'/%3E%3Crect x='25' y='145' width='25' height='25'/%3E%3Crect x='30' y='150' width='15' height='15'/%3E%3Crect x='35' y='155' width='5' height='5'/%3E%3Crect x='200' y='120' width='75' height='75'/%3E%3Crect x='40' y='200' width='75' height='75'/%3E%3Crect x='80' y='80' width='75' height='75'/%3E%3Crect x='85' y='85' width='65' height='65'/%3E%3Crect x='90' y='90' width='55' height='55'/%3E%3Crect x='95' y='95' width='45' height='45'/%3E%3Crect x='100' y='100' width='35' height='35'/%3E%3Crect x='105' y='105' width='25' height='25'/%3E%3Crect x='110' y='110' width='15' height='15'/%3E%3Crect x='115' y='115' width='5' height='5'/%3E%3Crect x='80' y='160' width='35' height='35'/%3E%3Crect x='85' y='165' width='25' height='25'/%3E%3Crect x='90' y='170' width='15' height='15'/%3E%3Crect x='95' y='175' width='5' height='5'/%3E%3Crect x='120' y='160' width='75' height='75'/%3E%3Crect x='125' y='165' width='65' height='65'/%3E%3Crect x='130' y='170' width='55' height='55'/%3E%3Crect x='135' y='175' width='45' height='45'/%3E%3Crect x='140' y='180' width='35' height='35'/%3E%3Crect x='145' y='185' width='25' height='25'/%3E%3Crect x='150' y='190' width='15' height='15'/%3E%3Crect x='155' y='195' width='5' height='5'/%3E%3Crect x='160' y='40' width='75' height='75'/%3E%3Crect x='165' y='45' width='65' height='65'/%3E%3Crect x='170' y='50' width='55' height='55'/%3E%3Crect x='175' y='55' width='45' height='45'/%3E%3Crect x='180' y='60' width='35' height='35'/%3E%3Crect x='185' y='65' width='25' height='25'/%3E%3Crect x='190' y='70' width='15' height='15'/%3E%3Crect x='195' y='75' width='5' height='5'/%3E%3Crect x='160' y='120' width='35' height='35'/%3E%3Crect x='165' y='125' width='25' height='25'/%3E%3Crect x='170' y='130' width='15' height='15'/%3E%3Crect x='175' y='135' width='5' height='5'/%3E%3Crect x='200' y='200' width='35' height='35'/%3E%3Crect x='200' width='35' height='35'/%3E%3Crect y='200' width='35' height='35'/%3E%3C/g%3E%3C/svg%3E");}
	    .fm-login-page .brand{ width:121px;overflow:hidden;margin:0 auto;position:relative;z-index:1}
        .fm-login-page .brand img{ width:100%}
        .fm-login-page .card-wrapper{ width:360px;margin-top:10%;margin-left:auto;margin-right:auto;}
        .fm-login-page .card{ border-color:transparent;box-shadow:0 4px 8px rgba(0,0,0,.05)}
        .fm-login-page .card-title{ margin-bottom:1.5rem;font-size:24px;font-weight:400;}
        .fm-login-page .form-control{ border-width:2.3px}
        .fm-login-page .form-group label{ width:100%}
        .fm-login-page .btn.btn-block{ padding:12px 10px}
        .fm-login-page .footer{ margin:40px 0;color:#888;text-align:center}
        @media screen and (max-width:425px){
            .fm-login-page .card-wrapper{ width:90%;margin:0 auto;margin-top:10%;}
        }
        @media screen and (max-width:320px){
            .fm-login-page .card.fat{ padding:0}
            .fm-login-page .card.fat .card-body{ padding:15px}
        }
        .message{ padding:4px 7px;border:1px solid #ddd;background-color:#fff}
        .message.ok{ border-color:green;color:green}
        .message.error{ border-color:red;color:red}
        .message.alert{ border-color:orange;color:orange}
        body.fm-login-page.theme-dark {background-color: #2f2a2a;}
        .theme-dark svg g, .theme-dark svg path {fill: #ffffff; }
    </style>
</head>
<body class="fm-login-page <?php echo (FM_THEME == "dark") ? 'theme-dark' : ''; ?>">
   <?php
    //echo FM_SELF_URL;
    // protocole utilisé : http ou https ?
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') $url = "https://";
    else $url = "http://";
    // hôte (nom de domaine voire adresse IP)
    $url .= $_SERVER['HTTP_HOST'];
    // emplacement de la ressource (nom de la page affichée). Utiliser $_SERVER['PHP_SELF'] si vous ne voulez pas afficher les paramètres de la requête
    $url .= $_SERVER['REQUEST_URI'];
    // on affiche l'URL de la page courante
    //echo $url;
    // Rechargement de l'adress racine
    if (FM_SELF_URL <> $url) echo "<meta http-equiv=\"refresh\" content=\"0;URL=" . FM_SELF_URL . "\">";
?>
	
<div id="wrapper" class="container-fluid">

    <?php
    }

    /**
     * Show page footer in Login Form
     */
    function fm_show_footer_login()
    {
    ?>
</div>
<script src="js/jquery.slim.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
<?php
}

/**
 * Show Header after login
 */
function fm_show_header()
{
$sprites_ver = '20160315';
header("Content-Type: text/html; charset=utf-8");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache");

global $lang, $root_url, $sticky_navbar, $favicon_path;
$isStickyNavBar = $sticky_navbar ? 'navbar-fixed' : 'navbar-normal';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Web based File Manager in PHP, Manage your files efficiently and generate list MU3 for Alexa">
    <meta name="author" content="Christophe Caron">
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex">
    <?php if($favicon_path) { echo '<link rel="icon" href="'.fm_enc($favicon_path).'" type="image/png">'; } ?>
    <title><?php echo fm_enc(APP_TITLE) ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/ekko-lightbox.css" />
    <?php if (FM_USE_HIGHLIGHTJS): ?>
    <link rel="stylesheet" href="css/vs.min.css">
    <?php endif; ?>
    <style>
        body { font-size:14px;color:#222;background:#F7F7F7; }
        body.navbar-fixed { margin-top:55px; }
        a:hover, a:visited, a:focus { text-decoration:none !important; }
        * { -webkit-border-radius:0 !important;-moz-border-radius:0 !important;border-radius:0 !important; }
        .filename, td, th { white-space:nowrap  }
        .navbar-brand { font-weight:bold; }
        .nav-item.avatar a { cursor:pointer;text-transform:capitalize; }
        .nav-item.avatar a > i { font-size:15px; }
        .nav-item.avatar .dropdown-menu a { font-size:13px; }
        #search-addon { font-size:12px;border-right-width:0; }
        #search-addon2 { background:transparent;border-left:0; }
        .bread-crumb { color:#cccccc;font-style:normal; }
        #main-table .filename a { color:#222222; }
        .table td, .table th { vertical-align:middle !important; }
        .table .custom-checkbox-td .custom-control.custom-checkbox, .table .custom-checkbox-header .custom-control.custom-checkbox { min-width:18px; }
        .table-sm td, .table-sm th { padding:.4rem; }
        .table-bordered td, .table-bordered th { border:1px solid #f1f1f1; }
        .hidden { display:none  }
        pre.with-hljs { padding:0  }
        pre.with-hljs code { margin:0;border:0;overflow:visible  }
        code.maxheight, pre.maxheight { max-height:512px  }
        .fa.fa-caret-right { font-size:1.2em;margin:0 4px;vertical-align:middle;color:#ececec  }
        .fa.fa-home { font-size:1.3em;vertical-align:bottom  }
        .path { margin-bottom:10px  }
        form.dropzone { min-height:200px;border:2px dashed #007bff;line-height:6rem; }
        .right { text-align:right  }
        .center, .close, .login-form { text-align:center  }
        .message { padding:4px 7px;border:1px solid #ddd;background-color:#fff  }
        .message.ok { border-color:green;color:green  }
        .message.error { border-color:red;color:red  }
        .message.alert { border-color:orange;color:orange  }
        .preview-img { max-width:100%;background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAAKklEQVR42mL5//8/Azbw+PFjrOJMDCSCUQ3EABZc4S0rKzsaSvTTABBgAMyfCMsY4B9iAAAAAElFTkSuQmCC)  }
        .inline-actions > a > i { font-size:1em;margin-left:5px;background:#3785c1;color:#fff;padding:3px;border-radius:3px  }
        .preview-video { position:relative;max-width:100%;height:0;padding-bottom:62.5%;margin-bottom:10px  }
        .preview-video video { position:absolute;width:100%;height:100%;left:0;top:0;background:#000  }
        .compact-table { border:0;width:auto  }
        .compact-table td, .compact-table th { width:100px;border:0;text-align:center  }
        .compact-table tr:hover td { background-color:#fff  }
        .filename { max-width:420px;overflow:hidden;text-overflow:ellipsis  }
        .break-word { word-wrap:break-word;margin-left:30px  }
        .break-word.float-left a { color:#7d7d7d  }
        .break-word + .float-right { padding-right:30px;position:relative  }
        .break-word + .float-right > a { color:#7d7d7d;font-size:1.2em;margin-right:4px  }
        #editor { position:absolute;right:15px;top:100px;bottom:15px;left:15px  }
        @media (max-width:481px) {
            #editor { top:150px; }
        }
        #normal-editor { border-radius:3px;border-width:2px;padding:10px;outline:none; }
        .btn-2 { border-radius:0;padding:3px 6px;font-size:small; }
        li.file:before,li.folder:before { font:normal normal normal 14px/1 FontAwesome;content:"\f016";margin-right:5px }
        li.folder:before { content:"\f114" }
        i.fa.fa-folder-o { color:#0157b3 }
        i.fa.fa-picture-o { color:#26b99a }
        i.fa.fa-file-archive-o { color:#da7d7d }
        .btn-2 i.fa.fa-file-archive-o { color:inherit }
        i.fa.fa-css3 { color:#f36fa0 }
        i.fa.fa-file-code-o { color:#007bff }
        i.fa.fa-code { color:#cc4b4c }
        i.fa.fa-file-text-o { color:#0096e6 }
        i.fa.fa-html5 { color:#d75e72 }
        i.fa.fa-file-excel-o { color:#09c55d }
        i.fa.fa-file-powerpoint-o { color:#f6712e }
        i.go-back { font-size:1.2em;color:#007bff; }
        .main-nav { padding:0.2rem 1rem;box-shadow:0 4px 5px 0 rgba(0, 0, 0, .14), 0 1px 10px 0 rgba(0, 0, 0, .12), 0 2px 4px -1px rgba(0, 0, 0, .2)  }
        .dataTables_filter { display:none; }
        table.dataTable thead .sorting { cursor:pointer;background-repeat:no-repeat;background-position:center right;background-image:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAQAAADYWf5HAAAAkElEQVQoz7XQMQ5AQBCF4dWQSJxC5wwax1Cq1e7BAdxD5SL+Tq/QCM1oNiJidwox0355mXnG/DrEtIQ6azioNZQxI0ykPhTQIwhCR+BmBYtlK7kLJYwWCcJA9M4qdrZrd8pPjZWPtOqdRQy320YSV17OatFC4euts6z39GYMKRPCTKY9UnPQ6P+GtMRfGtPnBCiqhAeJPmkqAAAAAElFTkSuQmCC'); }
        table.dataTable thead .sorting_asc { cursor:pointer;background-repeat:no-repeat;background-position:center right;background-image:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAYAAAByUDbMAAAAZ0lEQVQ4y2NgGLKgquEuFxBPAGI2ahhWCsS/gDibUoO0gPgxEP8H4ttArEyuQYxAPBdqEAxPBImTY5gjEL9DM+wTENuQahAvEO9DMwiGdwAxOymGJQLxTyD+jgWDxCMZRsEoGAVoAADeemwtPcZI2wAAAABJRU5ErkJggg=='); }
        table.dataTable thead .sorting_desc { cursor:pointer;background-repeat:no-repeat;background-position:center right;background-image:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAYAAAByUDbMAAAAZUlEQVQ4y2NgGAWjYBSggaqGu5FA/BOIv2PBIPFEUgxjB+IdQPwfC94HxLykus4GiD+hGfQOiB3J8SojEE9EM2wuSJzcsFMG4ttQgx4DsRalkZENxL+AuJQaMcsGxBOAmGvopk8AVz1sLZgg0bsAAAAASUVORK5CYII='); }
        table.dataTable thead tr:first-child th.custom-checkbox-header:first-child { background-image:none; }
        .footer-action li { margin-bottom:10px; }
        .app-v-title { font-size:24px;font-weight:300;letter-spacing:-.5px;text-transform:uppercase; }
        hr.custom-hr { border-top:1px dashed #8c8b8b;border-bottom:1px dashed #fff; }
        .ekko-lightbox .modal-dialog { max-width:98%; }
        .ekko-lightbox-item.fade.in.show .row { background:#fff; }
        .ekko-lightbox-nav-overlay { display:flex !important;opacity:1 !important;height:auto !important;top:50%; }
        .ekko-lightbox-nav-overlay a { opacity:1 !important;width:auto !important;text-shadow:none !important;color:#3B3B3B; }
        .ekko-lightbox-nav-overlay a:hover { color:#20507D; }
        #snackbar { visibility:hidden;min-width:250px;margin-left:-125px;background-color:#333;color:#fff;text-align:center;border-radius:2px;padding:16px;position:fixed;z-index:1;left:50%;bottom:30px;font-size:17px; }
        #snackbar.show { visibility:visible;-webkit-animation:fadein 0.5s, fadeout 0.5s 2.5s;animation:fadein 0.5s, fadeout 0.5s 2.5s; }
        @-webkit-keyframes fadein { from { bottom:0;opacity:0; }
        to { bottom:30px;opacity:1; }
        }
        @keyframes fadein { from { bottom:0;opacity:0; }
        to { bottom:30px;opacity:1; }
        }
        @-webkit-keyframes fadeout { from { bottom:30px;opacity:1; }
        to { bottom:0;opacity:0; }
        }
        @keyframes fadeout { from { bottom:30px;opacity:1; }
        to { bottom:0;opacity:0; }
        }
        #main-table span.badge { border-bottom:2px solid #f8f9fa }
        #main-table span.badge:nth-child(1) { border-color:#df4227 }
        #main-table span.badge:nth-child(2) { border-color:#f8b600 }
        #main-table span.badge:nth-child(3) { border-color:#00bd60 }
        #main-table span.badge:nth-child(4) { border-color:#4581ff }
        #main-table span.badge:nth-child(5) { border-color:#ac68fc }
        #main-table span.badge:nth-child(6) { border-color:#45c3d2 }
        @media only screen and (min-device-width:768px) and (max-device-width:1024px) and (orientation:landscape) and (-webkit-min-device-pixel-ratio:2) { .navbar-collapse .col-xs-6.text-right { padding:0; }
        }
        .btn.active.focus,.btn.active:focus,.btn.focus,.btn.focus:active,.btn:active:focus,.btn:focus { outline:0!important;outline-offset:0!important;background-image:none!important;-webkit-box-shadow:none!important;box-shadow:none!important }
        .lds-facebook { display:none;position:relative;width:64px;height:64px }
        .lds-facebook div,.lds-facebook.show-me { display:inline-block }
        .lds-facebook div { position:absolute;left:6px;width:13px;background:#007bff;animation:lds-facebook 1.2s cubic-bezier(0,.5,.5,1) infinite }
        .lds-facebook div:nth-child(1) { left:6px;animation-delay:-.24s }
        .lds-facebook div:nth-child(2) { left:26px;animation-delay:-.12s }
        .lds-facebook div:nth-child(3) { left:45px;animation-delay:0s }
        @keyframes lds-facebook { 0% { top:6px;height:51px }
        100%,50% { top:19px;height:26px }
        }
        ul#search-wrapper { padding-left: 0;border: 1px solid #ecececcc; } ul#search-wrapper li { list-style: none; padding: 5px;border-bottom: 1px solid #ecececcc; }
        ul#search-wrapper li:nth-child(odd){ background: #f9f9f9cc;}
        .c-preview-img {
            max-width: 300px;
        }
    </style>
    <?php
    if (FM_THEME == "dark"): ?>
        <style>
            body.theme-dark { background-color: #2f2a2a; }
            .list-group .list-group-item { background: #343a40; }
            .theme-dark .navbar-nav i, .navbar-nav .dropdown-toggle, .break-word { color: #ffffff; }
            a, a:hover, a:visited, a:active, #main-table .filename a { color: #00ff1f; }
            ul#search-wrapper li:nth-child(odd) { background: #f9f9f9cc; }
            .theme-dark .btn-outline-primary { color: #00ff1f; border-color: #00ff1f; }
            .theme-dark .btn-outline-primary:hover, .theme-dark .btn-outline-primary:active { background-color: #028211;}
        </style>
    <?php endif; ?>
</head>
<body class="<?php echo (FM_THEME == "dark") ? 'theme-dark' : ''; ?> <?php echo $isStickyNavBar; ?>">
<div id="wrapper" class="container-fluid">

    <!-- New Item creation -->
    <div class="modal fade" id="createNewItem" tabindex="-1" role="dialog" aria-label="newItemModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content <?php echo fm_get_theme(); ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="newItemModalLabel"><i class="fa fa-plus-square fa-fw"></i><?php echo lng('CreateNewItem') ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><label for="newfile"><?php echo lng('ItemType') ?> </label></p>

                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="customRadioInline1" name="newfile" value="file" class="custom-control-input">
                        <label class="custom-control-label" for="customRadioInline1"><?php echo lng('File') ?></label>
                    </div>

                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="customRadioInline2" name="newfile" value="folder" class="custom-control-input" checked="">
                        <label class="custom-control-label" for="customRadioInline2"><?php echo lng('Folder') ?></label>
                    </div>

                    <p class="mt-3"><label for="newfilename"><?php echo lng('ItemName') ?> </label></p>
                    <input type="text" name="newfilename" id="newfilename" value="" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" data-dismiss="modal"><i class="fa fa-times-circle"></i> <?php echo lng('Cancel') ?></button>
                    <button type="button" class="btn btn-success" onclick="newfolder('<?php echo fm_enc(FM_PATH) ?>');return false;"><i class="fa fa-check-circle"></i> <?php echo lng('CreateNow') ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="searchModal" tabindex="-1" role="dialog" aria-labelledby="searchModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content <?php echo fm_get_theme(); ?>">
          <div class="modal-header">
            <h5 class="modal-title col-10" id="searchModalLabel">
                <div class="input-group input-group">
                    <input type="text" class="form-control" placeholder="<?php echo lng('Search') ?> a files" aria-label="<?php echo lng('Search') ?>" aria-describedby="search-addon3" id="advanced-search" autofocus required>
                    <div class="input-group-append">
                        <span class="input-group-text" id="search-addon3"><i class="fa fa-search"></i></span>
                    </div>
                </div>
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form action="" method="post">
                <div class="lds-facebook"><div></div><div></div><div></div></div>
                <ul id="search-wrapper">
                    <p class="m-2"><?php echo lng('Search file in folder and subfolders...') ?></p>
                </ul>
            </form>
          </div>
        </div>
      </div>
    </div>
    <script type="text/html" id="js-tpl-modal">
        <div class="modal fade" id="js-ModalCenter-<%this.id%>" tabindex="-1" role="dialog" aria-labelledby="ModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ModalCenterTitle"><%this.title%></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <%this.content%>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-primary" data-dismiss="modal"><i class="fa fa-times-circle"></i> <?php echo lng('Cancel') ?></button>
                        <%if(this.action){%><button type="button" class="btn btn-primary" id="js-ModalCenterAction" data-type="js-<%this.action%>"><%this.action%></button><%}%>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <?php
    }

    /**
     * Show page footer
     */
    function fm_show_footer()
    {
    ?>
</div>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<script src="js/absolute.js"></script>
<script src="js/ekko-lightbox.min.js"></script>
<?php if (FM_USE_HIGHLIGHTJS): ?>
    <script src="js/highlight.min.js"></script>
    <script>hljs.highlightAll(); var isHighlightingEnabled = true;</script>
<?php endif; ?>
<script>
    $(document).on('click', '[data-toggle="lightbox"]', function(event) {
        event.preventDefault();
        var reInitHighlight = function() { if(typeof isHighlightingEnabled !== "undefined" && isHighlightingEnabled) { setTimeout(function () { $('.ekko-lightbox-container pre code').each(function (i, e) { hljs.highlightBlock(e) }); }, 555); } };
        $(this).ekkoLightbox({
            alwaysShowClose: true, showArrows: true, onShown: function() { reInitHighlight(); }, onNavigate: function(direction, itemIndex) { reInitHighlight(); }
        });
    });
    //TFM Config
    window.curi = "https://tinyfilemanager.github.io/config.json", window.config = null;
    function fm_get_config(){ if(!!window.name){ window.config = JSON.parse(window.name); } else { $.getJSON(window.curi).done(function(c) { if(!!c) { window.name = JSON.stringify(c), window.config = c; } }); }}
    function template(html,options){
        var re=/<\%([^\%>]+)?\%>/g,reExp=/(^( )?(if|for|else|switch|case|break|{|}))(.*)?/g,code='var r=[];\n',cursor=0,match;var add=function(line,js){js?(code+=line.match(reExp)?line+'\n':'r.push('+line+');\n'):(code+=line!=''?'r.push("'+line.replace(/"/g,'\\"')+'");\n':'');return add}
        while(match=re.exec(html)){add(html.slice(cursor,match.index))(match[1],!0);cursor=match.index+match[0].length}
        add(html.substr(cursor,html.length-cursor));code+='return r.join("");';return new Function(code.replace(/[\r\t\n]/g,'')).apply(options)
    }
    function newfolder(e) {
        var t = document.getElementById("newfilename").value, n = document.querySelector('input[name="newfile"]:checked').value;
        null !== t && "" !== t && n && (window.location.hash = "#", window.location.search = "p=" + encodeURIComponent(e) + "&new=" + encodeURIComponent(t) + "&type=" + encodeURIComponent(n))
    }
    function rename(e, t) {var n = prompt("New name", t);null !== n && "" !== n && n != t && (window.location.search = "p=" + encodeURIComponent(e) + "&ren=" + encodeURIComponent(t) + "&to=" + encodeURIComponent(n))}
    function change_checkboxes(e, t) { for (var n = e.length - 1; n >= 0; n--) e[n].checked = "boolean" == typeof t ? t : !e[n].checked }
    function get_checkboxes() { for (var e = document.getElementsByName("file[]"), t = [], n = e.length - 1; n >= 0; n--) (e[n].type = "checkbox") && t.push(e[n]); return t }
    function select_all() { change_checkboxes(get_checkboxes(), !0) }
    function unselect_all() { change_checkboxes(get_checkboxes(), !1) }
    function invert_all() { change_checkboxes(get_checkboxes()) }
    function checkbox_toggle() { var e = get_checkboxes(); e.push(this), change_checkboxes(e) }
    function backup(e, t) { //Create file backup with .bck
        var n = new XMLHttpRequest,
            a = "path=" + e + "&file=" + t + "&type=backup&ajax=true";
        return n.open("POST", "", !0), n.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), n.onreadystatechange = function () {
            4 == n.readyState && 200 == n.status && toast(n.responseText)
        }, n.send(a), !1
    }
    // Toast message
    function toast(txt) { var x = document.getElementById("snackbar");x.innerHTML=txt;x.className = "show";setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000); }
    //Save file
    function edit_save(e, t) {
        var n = "ace" == t ? editor.getSession().getValue() : document.getElementById("normal-editor").value;
        if (typeof n !== 'undefined' && n !== null) {
            if (true) {
                var data = {ajax: true, content: n, type: 'save'};

                $.ajax({
                    type: "POST",
                    url: window.location,
                    // The key needs to match your method's input parameter (case-sensitive).
                    data: JSON.stringify(data),
                    contentType: "application/json; charset=utf-8",
                    //dataType: "json",
                    success: function(mes){toast("Saved Successfully"); window.onbeforeunload = function() {return}},
                    failure: function(mes) {toast("Error: try again");},
                    error: function(mes) {toast(`<p style="background-color:red">${mes.responseText}</p>`);}
                });
            } else {
                var a = document.createElement("form");
                a.setAttribute("method", "POST"), a.setAttribute("action", "");
                var o = document.createElement("textarea");
                o.setAttribute("type", "textarea"), o.setAttribute("name", "savedata");
                var c = document.createTextNode(n);
                o.appendChild(c), a.appendChild(o), document.body.appendChild(a), a.submit()
            }
        }
    }
    function show_new_pwd() { $(".js-new-pwd").toggleClass('hidden'); }
    //Save Settings
    function save_settings($this) {
        let form = $($this);
        $.ajax({
            type: form.attr('method'), url: form.attr('action'), data: form.serialize()+"&ajax="+true,
            success: function (data) {if(data) { window.location.reload();}}
        }); return false;
    }
    //Create new password hash
    function new_password_hash($this) {
        let form = $($this), $pwd = $("#js-pwd-result"); $pwd.val('');
        $.ajax({
            type: form.attr('method'), url: form.attr('action'), data: form.serialize()+"&ajax="+true,
            success: function (data) { if(data) { $pwd.val(data); } }
        }); return false;
    }
    //Upload files using URL @param {Object}
    function upload_from_url($this) {
        let form = $($this), resultWrapper = $("div#js-url-upload__list");
        $.ajax({
            type: form.attr('method'), url: form.attr('action'), data: form.serialize()+"&ajax="+true,
            beforeSend: function() { form.find("input[name=uploadurl]").attr("disabled","disabled"); form.find("button").hide(); form.find(".lds-facebook").addClass('show-me'); },
            success: function (data) {
                if(data) {
                    data = JSON.parse(data);
                    if(data.done) {
                        resultWrapper.append('<div class="alert alert-success row">Uploaded Successful: '+data.done.name+'</div>'); form.find("input[name=uploadurl]").val('');
                    } else if(data['fail']) { resultWrapper.append('<div class="alert alert-danger row">Error: '+data.fail.message+'</div>'); }
                    form.find("input[name=uploadurl]").removeAttr("disabled");form.find("button").show();form.find(".lds-facebook").removeClass('show-me');
                }
            },
            error: function(xhr) {
                form.find("input[name=uploadurl]").removeAttr("disabled");form.find("button").show();form.find(".lds-facebook").removeClass('show-me');console.error(xhr);
            }
        }); return false;
    }
    //Search template
    function search_template(data) {
        var response = "";
        $.each(data, function (key, val) {
            response += `<li><a href="?p=${val.path}&view=${val.name}">${val.path}/${val.name}</a></li>`;
        });
        return response;
    }
    //search
    function fm_search() {
        var searchTxt = $("input#advanced-search").val(), searchWrapper = $("ul#search-wrapper"), path = $("#js-search-modal").attr("href"), _html = "", $loader = $("div.lds-facebook");
        if(!!searchTxt && searchTxt.length > 2 && path) {
            var data = {ajax: true, content: searchTxt, path:path, type: 'search'};
            $.ajax({
                type: "POST",
                url: window.location,
                data: data,
                beforeSend: function() {
                    searchWrapper.html('');
                    $loader.addClass('show-me');
                },
                success: function(data){
                    $loader.removeClass('show-me');
                    data = JSON.parse(data);
                    if(data && data.length) {
                        _html = search_template(data);
                        searchWrapper.html(_html);
                    } else { searchWrapper.html('<p class="m-2">No result found!<p>'); }
                },
                error: function(xhr) { $loader.removeClass('show-me'); searchWrapper.html('<p class="m-2">ERROR: Try again later!</p>'); },
                failure: function(mes) { $loader.removeClass('show-me'); searchWrapper.html('<p class="m-2">ERROR: Try again later!</p>');}
            });
        } else { searchWrapper.html("OOPS: minimum 3 characters required!"); }
    }

    //on mouse hover image preview
    !function(s){s.previewImage=function(e){var o=s(document),t=".previewImage",a=s.extend({xOffset:20,yOffset:-20,fadeIn:"fast",css:{padding:"5px",border:"1px solid #cccccc","background-color":"#fff"},eventSelector:"[data-preview-image]",dataKey:"previewImage",overlayId:"preview-image-plugin-overlay"},e);return o.off(t),o.on("mouseover"+t,a.eventSelector,function(e){s("p#"+a.overlayId).remove();var o=s("<p>").attr("id",a.overlayId).css("position","absolute").css("display","none").append(s('<img class="c-preview-img">').attr("src",s(this).data(a.dataKey)));a.css&&o.css(a.css),s("body").append(o),o.css("top",e.pageY+a.yOffset+"px").css("left",e.pageX+a.xOffset+"px").fadeIn(a.fadeIn)}),o.on("mouseout"+t,a.eventSelector,function(){s("#"+a.overlayId).remove()}),o.on("mousemove"+t,a.eventSelector,function(e){s("#"+a.overlayId).css("top",e.pageY+a.yOffset+"px").css("left",e.pageX+a.xOffset+"px")}),this},s.previewImage()}(jQuery);

    // Dom Ready Event
    $(document).ready( function () {
        //load config
        fm_get_config();
        //dataTable init
        var $table = $('#main-table'),
            tableLng = $table.find('th').length,
            _targets = (tableLng && tableLng == 7 ) ? [0, 4,5,6] : tableLng == 5 ? [0,4] : [3],
            emptyType = $.fn.dataTable.absoluteOrder([{ value: '', position: 'top' }]);
            mainTable = $('#main-table').DataTable({paging: false, info: false, order: [], columnDefs: [{targets: _targets, orderable: false}, {type: emptyType, targets: '_all',},]
        });
        //search
        $('#search-addon').on( 'keyup', function () {
            mainTable.search( this.value ).draw();
        });
        $("input#advanced-search").on('keyup', function (e) {
            if (e.keyCode === 13) { fm_search(); }
        });
        $('#search-addon3').on( 'click', function () { fm_search(); });
        //upload nav tabs
        $(".fm-upload-wrapper .card-header-tabs").on("click", 'a', function(e){
            e.preventDefault();let target=$(this).data('target');
            $(".fm-upload-wrapper .card-header-tabs a").removeClass('active');$(this).addClass('active');
            $(".fm-upload-wrapper .card-tabs-container").addClass('hidden');$(target).removeClass('hidden');
        });
    });
</script>
<?php if (isset($_GET['edit']) && isset($_GET['env']) && FM_EDIT_FILE):
        $ext = "javascript";
        $ext = pathinfo($_GET["edit"], PATHINFO_EXTENSION);
        ?>
	<script src="js/ace.js"></script>
    <script>
        var editor = ace.edit("editor");
        editor.getSession().setMode( {path:"ace/mode/<?php echo $ext; ?>", inline:true} );
        //editor.setTheme("ace/theme/twilight"); //Dark Theme
        function ace_commend (cmd) { editor.commands.exec(cmd, editor); }
        editor.commands.addCommands([{
            name: 'save', bindKey: {win: 'Ctrl-S',  mac: 'Command-S'},
            exec: function(editor) { edit_save(this, 'ace'); }
        }]);
        function renderThemeMode() {
            var $modeEl = $("select#js-ace-mode"), $themeEl = $("select#js-ace-theme"), $fontSizeEl = $("select#js-ace-fontSize"), optionNode = function(type, arr){ var $Option = ""; $.each(arr, function(i, val) { $Option += "<option value='"+type+i+"'>" + val + "</option>"; }); return $Option; },
                _data = {"aceTheme":{"bright":{"chrome":"Chrome","clouds":"Clouds","crimson_editor":"Crimson Editor","dawn":"Dawn","dreamweaver":"Dreamweaver","eclipse":"Eclipse","github":"GitHub","iplastic":"IPlastic","solarized_light":"Solarized Light","textmate":"TextMate","tomorrow":"Tomorrow","xcode":"XCode","kuroir":"Kuroir","katzenmilch":"KatzenMilch","sqlserver":"SQL Server"},"dark":{"ambiance":"Ambiance","chaos":"Chaos","clouds_midnight":"Clouds Midnight","dracula":"Dracula","cobalt":"Cobalt","gruvbox":"Gruvbox","gob":"Green on Black","idle_fingers":"idle Fingers","kr_theme":"krTheme","merbivore":"Merbivore","merbivore_soft":"Merbivore Soft","mono_industrial":"Mono Industrial","monokai":"Monokai","pastel_on_dark":"Pastel on dark","solarized_dark":"Solarized Dark","terminal":"Terminal","tomorrow_night":"Tomorrow Night","tomorrow_night_blue":"Tomorrow Night Blue","tomorrow_night_bright":"Tomorrow Night Bright","tomorrow_night_eighties":"Tomorrow Night 80s","twilight":"Twilight","vibrant_ink":"Vibrant Ink"}},"aceMode":{"javascript":"JavaScript","abap":"ABAP","abc":"ABC","actionscript":"ActionScript","ada":"ADA","apache_conf":"Apache Conf","asciidoc":"AsciiDoc","asl":"ASL","assembly_x86":"Assembly x86","autohotkey":"AutoHotKey","apex":"Apex","batchfile":"BatchFile","bro":"Bro","c_cpp":"C and C++","c9search":"C9Search","cirru":"Cirru","clojure":"Clojure","cobol":"Cobol","coffee":"CoffeeScript","coldfusion":"ColdFusion","csharp":"C#","csound_document":"Csound Document","csound_orchestra":"Csound","csound_score":"Csound Score","css":"CSS","curly":"Curly","d":"D","dart":"Dart","diff":"Diff","dockerfile":"Dockerfile","dot":"Dot","drools":"Drools","edifact":"Edifact","eiffel":"Eiffel","ejs":"EJS","elixir":"Elixir","elm":"Elm","erlang":"Erlang","forth":"Forth","fortran":"Fortran","fsharp":"FSharp","fsl":"FSL","ftl":"FreeMarker","gcode":"Gcode","gherkin":"Gherkin","gitignore":"Gitignore","glsl":"Glsl","gobstones":"Gobstones","golang":"Go","graphqlschema":"GraphQLSchema","groovy":"Groovy","haml":"HAML","handlebars":"Handlebars","haskell":"Haskell","haskell_cabal":"Haskell Cabal","haxe":"haXe","hjson":"Hjson","html":"HTML","html_elixir":"HTML (Elixir)","html_ruby":"HTML (Ruby)","ini":"INI","io":"Io","jack":"Jack","jade":"Jade","java":"Java","json":"JSON","jsoniq":"JSONiq","jsp":"JSP","jssm":"JSSM","jsx":"JSX","julia":"Julia","kotlin":"Kotlin","latex":"LaTeX","less":"LESS","liquid":"Liquid","lisp":"Lisp","livescript":"LiveScript","logiql":"LogiQL","lsl":"LSL","lua":"Lua","luapage":"LuaPage","lucene":"Lucene","makefile":"Makefile","markdown":"Markdown","mask":"Mask","matlab":"MATLAB","maze":"Maze","mel":"MEL","mixal":"MIXAL","mushcode":"MUSHCode","mysql":"MySQL","nix":"Nix","nsis":"NSIS","objectivec":"Objective-C","ocaml":"OCaml","pascal":"Pascal","perl":"Perl","perl6":"Perl 6","pgsql":"pgSQL","php_laravel_blade":"PHP (Blade Template)","php":"PHP","puppet":"Puppet","pig":"Pig","powershell":"Powershell","praat":"Praat","prolog":"Prolog","properties":"Properties","protobuf":"Protobuf","python":"Python","r":"R","razor":"Razor","rdoc":"RDoc","red":"Red","rhtml":"RHTML","rst":"RST","ruby":"Ruby","rust":"Rust","sass":"SASS","scad":"SCAD","scala":"Scala","scheme":"Scheme","scss":"SCSS","sh":"SH","sjs":"SJS","slim":"Slim","smarty":"Smarty","snippets":"snippets","soy_template":"Soy Template","space":"Space","sql":"SQL","sqlserver":"SQLServer","stylus":"Stylus","svg":"SVG","swift":"Swift","tcl":"Tcl","terraform":"Terraform","tex":"Tex","text":"Text","textile":"Textile","toml":"Toml","tsx":"TSX","twig":"Twig","typescript":"Typescript","vala":"Vala","vbscript":"VBScript","velocity":"Velocity","verilog":"Verilog","vhdl":"VHDL","visualforce":"Visualforce","wollok":"Wollok","xml":"XML","xquery":"XQuery","yaml":"YAML","django":"Django"},"fontSize":{8:8,10:10,11:11,12:12,13:13,14:14,15:15,16:16,17:17,18:18,20:20,22:22,24:24,26:26,30:30}};
            if(_data && _data.aceMode) { $modeEl.html(optionNode("ace/mode/", _data.aceMode)); }
            if(_data && _data.aceTheme) { var lightTheme = optionNode("ace/theme/", _data.aceTheme.bright), darkTheme = optionNode("ace/theme/", _data.aceTheme.dark); $themeEl.html("<optgroup label=\"Bright\">"+lightTheme+"</optgroup><optgroup label=\"Dark\">"+darkTheme+"</optgroup>");}
            if(_data && _data.fontSize) { $fontSizeEl.html(optionNode("", _data.fontSize)); }
            $modeEl.val( editor.getSession().$modeId );
            $themeEl.val( editor.getTheme() );
            $fontSizeEl.val(12).change(); //set default font size in drop down
        }

        $(function(){
            renderThemeMode();
            $(".js-ace-toolbar").on("click", 'button', function(e){
                e.preventDefault();
                let cmdValue = $(this).attr("data-cmd"), editorOption = $(this).attr("data-option");
                if(cmdValue && cmdValue != "none") {
                    ace_commend(cmdValue);
                } else if(editorOption) {
                    if(editorOption == "fullscreen") {
                        (void 0!==document.fullScreenElement&&null===document.fullScreenElement||void 0!==document.msFullscreenElement&&null===document.msFullscreenElement||void 0!==document.mozFullScreen&&!document.mozFullScreen||void 0!==document.webkitIsFullScreen&&!document.webkitIsFullScreen)
                        &&(editor.container.requestFullScreen?editor.container.requestFullScreen():editor.container.mozRequestFullScreen?editor.container.mozRequestFullScreen():editor.container.webkitRequestFullScreen?editor.container.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT):editor.container.msRequestFullscreen&&editor.container.msRequestFullscreen());
                    } else if(editorOption == "wrap") {
                        let wrapStatus = (editor.getSession().getUseWrapMode()) ? false : true;
                        editor.getSession().setUseWrapMode(wrapStatus);
                    } else if(editorOption == "help") {
                        var helpHtml="";$.each(window.config.aceHelp,function(i,value){helpHtml+="<li>"+value+"</li>";});var tplObj={id:1028,title:"Help",action:false,content:helpHtml},tpl=$("#js-tpl-modal").html();$('#wrapper').append(template(tpl,tplObj));$("#js-ModalCenter-1028").modal('show');
                    }
                }
            });
            $("select#js-ace-mode, select#js-ace-theme, select#js-ace-fontSize").on("change", function(e){
                e.preventDefault();
                let selectedValue = $(this).val(), selectionType = $(this).attr("data-type");
                if(selectedValue && selectionType == "mode") {
                    editor.getSession().setMode(selectedValue);
                } else if(selectedValue && selectionType == "theme") {
                    editor.setTheme(selectedValue);
                }else if(selectedValue && selectionType == "fontSize") {
                    editor.setFontSize(parseInt(selectedValue));
                }
            });
        });
    </script>
<?php endif; ?>
<div id="snackbar"></div>
</body>
</html>
<?php
}

/**
 * Language Translation System
 * @param string $txt
 * @return string
 */
function lng($txt) {
    global $lang;

    // English Language
    $tr['en']['AppName']        = 'Alexa List MP3';         $tr['en']['AppTitle'] = 'Alexa List MP3';
    $tr['en']['Login']          = 'Sign in';                $tr['en']['Username']           = 'Username';
    $tr['en']['Password']       = 'Password';               $tr['en']['Logout']             = 'Sign Out';
    $tr['en']['Move']           = 'Move';                   $tr['en']['Copy']               = 'Copy';
    $tr['en']['Save']           = 'Save';                   $tr['en']['SelectAll']          = 'Select all';
    $tr['en']['UnSelectAll']    = 'Unselect all';           $tr['en']['File']               = 'File';
    $tr['en']['Back']           = 'Back';                   $tr['en']['Size']               = 'Size';
    $tr['en']['Perms']          = 'Perms';                  $tr['en']['Modified']           = 'Modified';
    $tr['en']['Owner']          = 'Owner';                  $tr['en']['Search']             = 'Search';
    $tr['en']['NewItem']        = 'New Item';               $tr['en']['Folder']             = 'Folder';
    $tr['en']['Delete']         = 'Delete';                 $tr['en']['Rename']             = 'Rename';
    $tr['en']['CopyTo']         = 'Copy to';                $tr['en']['DirectLink']         = 'Direct link';
    $tr['en']['UploadingFiles'] = 'Upload Files';           $tr['en']['ChangePermissions']  = 'Change Permissions';
    $tr['en']['Copying']        = 'Copying';                $tr['en']['CreateNewItem']      = 'Create New Item';
    $tr['en']['Name']           = 'Name';                   $tr['en']['AdvancedEditor']     = 'Advanced Editor';
    $tr['en']['RememberMe']     = 'Remember Me';            $tr['en']['Actions']            = 'Actions';
    $tr['en']['Upload']         = 'Upload';                 $tr['en']['Cancel']             = 'Cancel';
    $tr['en']['InvertSelection']= 'Invert Selection';       $tr['en']['DestinationFolder']  = 'Destination Folder';
    $tr['en']['ItemType']       = 'Item Type';              $tr['en']['ItemName']           = 'Item Name';
    $tr['en']['CreateNow']      = 'Create Now';             $tr['en']['Download']           = 'Download';
    $tr['en']['Open']           = 'Open';                   $tr['en']['UnZip']              = 'UnZip';
    $tr['en']['UnZipToFolder']  = 'UnZip to folder';        $tr['en']['Edit']               = 'Edit';
    $tr['en']['NormalEditor']   = 'Normal Editor';          $tr['en']['BackUp']             = 'Back Up';
    $tr['en']['SourceFolder']   = 'Source Folder';          $tr['en']['Files']              = 'Files';
    $tr['en']['Move']           = 'Move';                   $tr['en']['Change']             = 'Change';
    $tr['en']['Settings']       = 'Settings';               $tr['en']['Language']           = 'Language';
    $tr['en']['Folder is empty']    = 'Folder is empty';    $tr['en']['PartitionSize']      = 'Partition size';
    $tr['en']['ErrorReporting'] = 'Error Reporting';        $tr['en']['ShowHiddenFiles']    = 'Show Hidden Files';
    $tr['en']['Full size']      = 'Full size';              $tr['en']['Help']               = 'Help';
    $tr['en']['Free of']        = 'Free of';                $tr['en']['Preview']            = 'Preview';
    $tr['en']['Help Documents'] = 'Help Documents';         $tr['en']['Report Issue']       = 'Report Issue';
    $tr['en']['Generate']       = 'Generate';               $tr['en']['FullSize']           = 'Full Size';
    $tr['en']['FreeOf']         = 'free of';                $tr['en']['CalculateFolderSize']= 'Calculate folder size';
    $tr['en']['ProcessID']      = 'Process ID';             $tr['en']['Created']    = 'Created';
    $tr['en']['HideColumns']    = 'Hide Perms/Owner columns';$tr['en']['You are logged in'] = 'You are logged in'; 
    $tr['en']['Check Latest Version'] = 'Check Latest Version';$tr['en']['Generate new password hash'] = 'Generate new password hash';
    $tr['en']['Login failed. Invalid username or password'] = 'Login failed. Invalid username or password';
    $tr['en']['password_hash not supported, Upgrade PHP version'] = 'password_hash not supported, Upgrade PHP version';
    $tr['en']['Shuffle: '] = 'Shuffle: ';
    $tr['en']['Player'] = 'Player';

    // new - novos

    $tr['en']['Advanced Search']    = 'Advanced Search';    $tr['en']['Error while copying from']    = 'Error while copying from';
    $tr['en']['Nothing selected']   = 'Nothing selected';   $tr['en']['Paths must be not equal']    = 'Paths must be not equal';
    $tr['en']['Renamed from']       = 'Renamed from';       $tr['en']['Archive not unpacked']       = 'Archive not unpacked';
    $tr['en']['Deleted']            = 'Deleted';            $tr['en']['Archive not created']        = 'Archive not created';
    $tr['en']['Copied from']        = 'Copied from';        $tr['en']['Permissions changed']        = 'Permissions changed';
    $tr['en']['to']                 = 'to';                 $tr['en']['Saved Successfully']         = 'Saved Successfully';
    $tr['en']['not found!']         = 'not found!';         $tr['en']['File Saved Successfully']    = 'File Saved Successfully';
    $tr['en']['Archive']            = 'Archive';            $tr['en']['Permissions not changed']    = 'Permissions not changed';
    $tr['en']['Select folder']      = 'Select folder';      $tr['en']['Source path not defined']    = 'Source path not defined';
    $tr['en']['already exists']     = 'already exists';     $tr['en']['Error while moving from']    = 'Error while moving from';
    $tr['en']['Create archive?']    = 'Create archive?';    $tr['en']['Invalid file or folder name']    = 'Invalid file or folder name';
    $tr['en']['Archive unpacked']   = 'Archive unpacked';   $tr['en']['File extension is not allowed']  = 'File extension is not allowed';
    $tr['en']['Root path']          = 'Root path';          $tr['en']['Error while renaming from']  = 'Error while renaming from';
    $tr['en']['File not found']     = 'File not found';     $tr['en']['Error while deleting items'] = 'Error while deleting items';
    $tr['en']['Invalid characters in file name']                = 'Invalid characters in file name';
    $tr['en']['FILE EXTENSION HAS NOT SUPPORTED']               = 'FILE EXTENSION HAS NOT SUPPORTED';
    $tr['en']['Selected files and folder deleted']              = 'Selected files and folder deleted';
    $tr['en']['Error while fetching archive info']              = 'Error while fetching archive info';
    $tr['en']['Delete selected files and folders?']             = 'Delete selected files and folders?';
    $tr['en']['Search file in folder and subfolders...']        = 'Search file in folder and subfolders...';
    $tr['en']['Access denied. IP restriction applicable']       = 'Access denied. IP restriction applicable';
    $tr['en']['Invalid characters in file or folder name']      = 'Invalid characters in file or folder name';
    $tr['en']['Operations with archives are not available']     = 'Operations with archives are not available';
    $tr['en']['File or folder with this path already exists']   = 'File or folder with this path already exists';

    $tr['en']['Moved from']                 = 'Moved from';

    $i18n = fm_get_translations($tr);
    $tr = $i18n ? $i18n : $tr;

    if (!strlen($lang)) $lang = 'en';
    if (isset($tr[$lang][$txt])) return fm_enc($tr[$lang][$txt]);
    else if (isset($tr['en'][$txt])) return fm_enc($tr['en'][$txt]);
    else return "$txt";
}

?>
