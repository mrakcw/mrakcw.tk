<?php
/**
 * Archivarix CMS
 *
 * PHP version 5.6 or newer
 * Required extensions: pdo_sqlite, json, pcre
 * Recommended extensions: curl, dom, fileinfo, iconv, intl, libxml, zip, openssl
 *
 * LICENSE:
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package    Archivarix.CMS
 * @version    Release: 0.1.220929
 * @author     Archivarix Team <hello@archivarix.com>
 * @telegram   https://t.me/ArchivarixSupport
 * @messenger  https://m.me/ArchivarixSupport
 * @copyright  2017-2022 Archivarix LLC
 * @license    https://www.gnu.org/licenses/gpl.html GNU GPLv3
 * @link       https://archivarix.com/en/cms/
 */

@ini_set( 'display_errors', 0 );
@ini_set( 'max_execution_time', 600 );
@ini_set( 'memory_limit', '4G' );
@ini_set( 'session.cookie_httponly', 1 );
if ( version_compare( PHP_VERSION, '7.3.0' ) >= 0 ) @ini_set( 'session.cookie_samesite', 'Lax' );

/**
 * Set your password to access.
 * Please, do not use simple or short passwords!
 */
const ACMS_PASSWORD        = '';

/*
 * Separate password for a safe mode where you cannot create or
 * edit custom files with php code or change password. You can set
 * this password only to always work in a safe mode.
 */
const ACMS_SAFE_PASSWORD   = '';

/**
 * Restrict access by setting IPs separated by commas
 * CIDR masks are also allowed.
 * Example: 1.2.3.4, 5.6.7.8/24
 */
const ACMS_ALLOWED_IPS     = '';

/*
* This option disables left tree menu to save memory if
* a total number of URLs for a domain is larger than a
* number set. By default, 10 000 files.
*/
const ACMS_URLS_LIMIT      = 10000;

/*
* This option limits results output for Search and Replace so
* your browser will not hang on a huge html page. It will not
* limit actual replace process.
*/
const ACMS_MATCHES_LIMIT   = 5000;

/*
 * Set to 1 to purge all existing history and disable
 * history/backups to save space.
 */
const ACMS_DISABLE_HISTORY = 0;

/*
 * Tasks that can be performed for a long time will be performed
 * in parts with the intervals specified below.
 */
const ACMS_TIMEOUT         = 30;

/*
 * Set a domain if you run a website on a subdomain
 * of the original domain.
 */
const ACMS_CUSTOM_DOMAIN   = '';

/*
 * Set only if you renamed your .content.xxxxxxxx to different
 * name or if you have multiple content directories.
 * Will be deprecated in future versions.
 */
const ACMS_CONTENT_PATH    = '';

/*
 * Disable features that can potentially be harmful to the website
 * like uploading custom files with php or changing password. Editing
 * the website content is still fully available.
 */
const ACMS_SAFE_MODE       = 0;

/*
 * Default editor for HTML pages. Allowed values: 'visual' and 'code'
 */
const ACMS_EDITOR_DEFAULT  = 'visual';

/*
 * Enable comparing differences between changes in text/code files
 */
const ACMS_EDITOR_HISTORY  = 0;


/**
 * DO NOT EDIT UNDER THIS LINE UNLESS YOU KNOW WHAT YOU ARE DOING
 */
const ACMS_VERSION         = '0.1.220929';
define( 'ACMS_START_TIME', microtime( true ) );
session_start();

$ACMS = [
  'ACMS_ALLOWED_IPS'     => ACMS_ALLOWED_IPS,
  'ACMS_CUSTOM_DOMAIN'   => ACMS_CUSTOM_DOMAIN,
  'ACMS_URLS_LIMIT'      => ACMS_URLS_LIMIT,
  'ACMS_MATCHES_LIMIT'   => ACMS_MATCHES_LIMIT,
  'ACMS_DISABLE_HISTORY' => ACMS_DISABLE_HISTORY,
  'ACMS_TIMEOUT'         => ACMS_TIMEOUT,
  'ACMS_SAFE_MODE'       => ACMS_SAFE_MODE,
  'ACMS_EDITOR_DEFAULT'  => ACMS_EDITOR_DEFAULT,
  'ACMS_EDITOR_HISTORY'  => ACMS_EDITOR_HISTORY,
  'ACMS_PASSWORD'        => '',
  'ACMS_SAFE_PASSWORD'   => '',
];

$fBucket    = [];
$cmsLocales = [
  'be' => ['name' => 'Беларуская', 'code' => 'be-BY'],
  'de' => ['name' => 'Deutsch', 'code' => 'de-DE'],
  'en' => ['name' => 'English', 'code' => 'en-US'],
  'es' => ['name' => 'Español', 'code' => 'es-ES'],
  'fr' => ['name' => 'Français', 'code' => 'fr-FR'],
  'it' => ['name' => 'Italiano', 'code' => 'it-IT'],
  'pl' => ['name' => 'Polski', 'code' => 'pl-PL'],
  'pt' => ['name' => 'Português', 'code' => 'pt-BR'],
  'ru' => ['name' => 'Русский', 'code' => 'ru-RU'],
  'tr' => ['name' => 'Türkçe', 'code' => 'tr-TR'],
  'uk' => ['name' => 'Українська', 'code' => 'uk-UA'],
  'ja' => ['name' => '日本語', 'code' => 'ja-JP'],
  'zh' => ['name' => '中文', 'code' => 'zh-CN'],
];
$sourcePath = getSourceRoot();
loadAcmsSettings();
checkAllowedIp();
blockCrawlers();

if ( isset( $_GET['lang'] ) ) {
  $setLang = strtolower( trim( $_GET['lang'] ) );
  setLanguage( $setLang );
  header( 'Location: ' . parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
  http_response_code( 302 );
  exit( 0 );
}

if ( empty( $_SESSION['archivarix.lang'] ) ) {
  setLanguage( detectLanguage() );
}

$GLOBALS['L'] = loadLocalization( getLang() );

$accessAllowed     = checkAccess();
$extensionsAllowed = empty( getMissingExtensions( ['json', 'pcre', 'pdo_sqlite'] ) );

// CLI
if ( php_sapi_name() === 'cli' ) {
  @ini_set( 'max_execution_time', 0 );
  session_destroy();
  $taskStats         = !empty( $_POST['taskStats'] ) ? trim( $_POST['taskStats'] ) : serialize( ['time' => 0] );
  $cli               = getopt( null, ["action:", "data:", "settings:"] );
  $missingExtensions = getMissingExtensions( ['curl', 'json', 'pcre', 'pdo_sqlite', 'zip'] );

  if ( empty( $cli['action'] ) ) {
    showWarningJson( ['status' => 0, 'message' => 'Action is not set'], 1 );
  }
  if ( !checkPhpVersion() ) {
    showWarningJson( ['action' => $cli['action'], 'status' => 0, 'message' => sprintf( L( 'PHP 5.6 or newer is required. The version of PHP you\'re running is %s.' ), PHP_VERSION )], 1 );
  }
  if ( !empty( $missingExtensions ) ) {
    showWarningJson( ['action' => $cli['action'], 'status' => 0, 'message' => 'Following extensions are missing: ' . implode( ', ', $missingExtensions )], 1 );
  }
  if ( version_compare( getSqliteVersion(), '3.7.0', '<' ) ) {
    showWarningJson( ['action' => $cli['action'], 'status' => 0, 'message' => 'Your SQLite version is lower than 3.7.0'], 1 );
  }

  $data         = !empty( $cli['data'] ) ? json_decode( $cli['data'], true ) : [];
  $settings     = !empty( $cli['settings'] ) ? json_decode( $cli['settings'], true ) : [];
  $data['uuid'] = ( !empty( $data['uuid'] ) && preg_match( '~^([a-z0-9]{4}[-]?){3}[a-z0-9]{4}$~i', $data['uuid'] ) ) ? $data['uuid'] : '';

  switch ( $cli['action'] ) {
    case 'check.prereqs' :
    case 'test':
      $recommendedExtensions = ['curl', 'dom', 'fileinfo', 'iconv', 'imagick', 'intl', 'json', 'mbstring', 'libxml', 'pcre', 'pdo_sqlite', 'zip',];
      $missingExtensions     = getMissingExtensions( $recommendedExtensions );
      if ( count( $missingExtensions ) ) {
        showWarningJson( ['action' => $cli['action'], 'status' => 0, 'message' => 'Missing extensions: ' . implode( ', ', $missingExtensions )], 1 );
      } else {
        showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => 'All recommended extensions are enabled.'], 1 );
      }
      break;
    case 'perform.install' :
      // data {"uuid":"ABCD-EFGH-..."}, settings {"ACMS_PASSWORD":"password or hash", ...}
      cliPerformInstall( $data, $settings );
      break;
  }

  if ( $sourcePath === false ) showWarningJson( ['action' => $cli['action'], 'status' => 0, 'message' => 'No website detected'], 1 );

  $dsn          = getDSN();
  $uuidSettings = getSettings();
  upgradeSchema();
  switch ( $cli['action'] ) {
    case 'update.acms.settings' :
      // settings {"ACMS_PASSWORD":"password or hash", "ACMS_TIMEOUT":60, ...}
      cliUpdateAcmsSettings( $settings );
      break;
    case 'update.loader.settings':
      cliUpdateLoaderSettings( $settings );
      break;
    case 'perform.import' :
      // data {"uuid":"ABCD-EFGH-...", "hostnames":[for selective import], "overwrite":"all|none|newer", "submerge": 1|0, "subdomain": "value is needed"}
      cliPerformImport( $data );
      break;
    case 'perform.update' :
      updateSystem();
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => 'Update performed'], 1 );
      break;
    case 'get.info' :
      $cliGetInfo['settings']      = getSettings();
      $cliGetInfo['freespace']     = function_exists( 'disk_free_space' ) ? disk_free_space( $sourcePath ) : null;
      $cliGetInfo['websitespace']  = getDirectorySize( $sourcePath );
      $cliGetInfo['backupspace']   = getDirectorySize( $sourcePath . DIRECTORY_SEPARATOR . 'backup' );
      $cliGetInfo['cmsversion']    = ACMS_VERSION;
      $cliGetInfo['loaderversion'] = getLoaderInfo()['version'];
      $cliWebsite                  = getInfoFromDatabase( $dsn );
      $cliGetInfo['hostnames']     = $cliWebsite['hostnames'];
      $cliGetInfo['mimestats']     = $cliWebsite['mimestats'];
      $cliGetInfo['custom_rules']  = getCustomRules();
      $cliGetInfo['templates']     = [];
      if ( $templates = getTemplates() ) {
        foreach ( $templates as $template ) {
          $cliGetInfo['templates'][$template['name']]           = $template;
          $cliGetInfo['templates'][$template['name']]['params'] = getTemplateInfo( $template['name'] )['params'];
        }
      }
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'info' => $cliGetInfo], 1 );
      break;
    case 'delete.backups' :
      deleteBackups( ['all' => 1] );
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => 'Backups deleted'], 1 );
      break;
    case 'delete.urls' :
      cliDeleteUrls( $data );
      break;
    case 'import.url' :
      createUrl( $data );
      break;
    case 'robots.allow' :
      // --data='{"sitemap_include":1}'
      createRobotsTxt( $data );
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => 'Robots allowed'], 1 );
      break;
    case 'broken.encoded.urls' :
      $ACMS['ACMS_TIMEOUT'] = 0;
      $updatedUrls          = updateUrlEncoded();
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => sprintf( '%d encoded URLs in %d pages are updated.', $updatedUrls['links'], $updatedUrls['pages'] )], 1 );
      break;
    case 'convert.utf8' :
      $ACMS['ACMS_TIMEOUT'] = 0;
      $converted            = convertUTF8();
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => sprintf( 'The conversion of %d files into %s is complete.', $converted, 'UTF-8' )], 1 );
      break;
    case 'convert.webp' :
      $ACMS['ACMS_TIMEOUT'] = 0;
      $converted            = convertWebp();
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => sprintf( 'The conversion of %d files into %s is complete.', $converted, 'WebP' )], 1 );
      break;
    case 'broken.images.remove' :
      $ACMS['ACMS_TIMEOUT'] = 0;
      $brokenImages         = removeBrokenImages();
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => sprintf( '%d broken internal images in %d pages are removed.', $brokenImages['images'], $brokenImages['pages'] )], 1 );
      break;
    case 'broken.links.remove' :
      $ACMS['ACMS_TIMEOUT'] = 0;
      $brokenLinks          = removeBrokenLinks();
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => sprintf( '%d broken internal links in %d pages are removed.', $brokenLinks['links'], $brokenLinks['pages'] )], 1 );
      break;
    case 'version.control.remove':
      $ACMS['ACMS_TIMEOUT']  = 0;
      $removedVersionControl = removeVersionControl();
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => sprintf( '%d URLs are fixed, %d replaces in %d different pages are performed.', $removedVersionControl['urls'], $removedVersionControl['replaces'], $removedVersionControl['pages'] )], 1 );
      break;
    case 'external.links.remove' :
      $ACMS['ACMS_TIMEOUT'] = 0;
      $removedLinks         = removeExternalLinks();
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => sprintf( '%d external links in %d pages are removed.', $removedLinks['links'], $removedLinks['pages'] )], 1 );
      break;
    case 'api.cloudflare.create':
      // --data='{"zone_id":"example.com","token":"APItoken"}'
      if ( setCloudflareToken( $data ) ) showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => 'CloudFlare API key set.'], 1 );
      else showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => 'CloudFlare API key is not working'], 1 );
      break;
    case 'api.cloudflare.remove' :
      removeMetaTableData( 'acms_cloudflare' );
      break;
    case 'api.cloudflare.purge.cache' :
      if ( purgeCacheCloudflare() ) showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => 'Cache purged.'], 1 );
      else showWarningJson( ['action' => $cli['action'], 'status' => 0, 'message' => 'Could not purge cache'], 1 );
      break;
    case 'trackers.code' :
      if ( updateTrackersCode( $data['code'] ) ) showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => 'Trackers code updated.'], 1 );
      else showWarningJson( ['action' => $cli['action'], 'status' => 0, 'message' => 'Could not update trackers code'], 1 );
      break;
    case 'convert.www':
      $updatedUrls = updateWebsiteWww( $data['www'] );
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => sprintf( '%d urls were converted to %s.', $updatedUrls, ( $data['www'] ? 'www' : 'non-www' ) )], 1 );
      break;
    case 'update.viewport' :
      $ACMS['ACMS_TIMEOUT'] = 0;
      $updatedUrls          = updateViewport( $data );
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => sprintf( 'The viewport set in %d pages.', $updatedUrls['links'] )], 1 );
      break;
    case 'update.urls.meta' :
      $ACMS['ACMS_TIMEOUT'] = 0;
      $updatedUrls          = updateUrlsMeta();
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => sprintf( 'Metadata was updated in %d URLs.', $updatedUrls['processed'] )], 1 );
      break;
    case 'update.pages.depth' :
      $ACMS['ACMS_TIMEOUT'] = 0;
      $updatedDepth         = detectPagesDepth();
      showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => sprintf( 'Performed %d iterations. Depth detected for %d pages. Orphan pages: %d.', $updatedDepth['iterations'], $updatedDepth['linked_pages'], $updatedDepth['orphan_pages'] )], 1 );
      break;
    case 'create.template.page' :
      if ( $pageId = createTemplatePage( $data ) ) {
        $pageUrl = getRealUrl( $pageId );
        showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => "New page created from the template.", 'page_id' => $pageId, 'url' => $pageUrl], 1 );
      } else {
        showWarningJson( ['action' => $cli['action'], 'status' => 0, 'message' => "Could not create a page from the template."], 1 );
      }
      break;
    case 'import.loader.json' :
      if ( cliImportLoaderJson( $data ) ) showWarningJson( ['action' => $cli['action'], 'status' => 1, 'message' => "Loader settings imported."], 1 );
      else showWarningJson( ['action' => $cli['action'], 'status' => 0, 'message' => "Could not import loader settings."], 1 );
      break;
    default :
      showWarningJson( ['action' => $cli['action'], 'status' => 0, 'message' => 'No such command'], 1 );
  }
  exit( 0 );
}

if ( isset( $_GET['expert'] ) ) {
  $_SESSION['archivarix.expert'] = $_GET['expert'];
  header( 'Location: ' . parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
  http_response_code( 302 );
  exit( 0 );
}

getXSRF();

if (
  $accessAllowed
  && !empty( $_POST['action'] )
  && $_POST['action'] == 'set.acms.settings'
) {
  setAcmsSettings( $_POST['settings'] );
  addWarning( L( 'Settings were updated.' ), 1, L( 'Settings' ) );
  $section = 'settings';
  $LOADER  = loadLoaderSettings();
  loadAcmsSettings();
  $accessAllowed = checkAccess();
  checkAllowedIp();
}

header( 'X-Robots-Tag: noindex, nofollow' );

if ( isset( $_GET['logout'] ) ) {
  unset( $_SESSION['archivarix.logged'] );
  unset( $_SESSION['archivarix.expert'] );
  unset( $_SESSION['archivarix.safe_mode'] );
  //session_destroy();
  header( 'Location: ' . parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
  http_response_code( 302 );
  exit( 0 );
}

function addWarning( $message, $level = 1, $title = '', $monospace = false )
{
  global $warnings;
  switch ( $level ) {
    case 1 :
      $color = "success";
      break;
    case 2 :
      $color = "primary";
      break;
    case 3 :
      $color = "warning";
      break;
    case 4 :
      $color = "danger";
      break;
    default :
      $color = "success";
      break;
  }
  if ( is_array( $message ) ) {
    $message = '<pre>' . print_r( $message, 1 ) . '</pre>';
  } elseif ( $monospace ) {
    $message = '<pre>' . $message . '</pre>';
  }
  $warnings[] = ['message' => $message, 'level' => $color, 'title' => $title];
}

function backupFile( $rowid, $action )
{
  global $ACMS;
  if ( $ACMS['ACMS_DISABLE_HISTORY'] ) {
    return;
  }

  global $sourcePath;
  $pdo = newPDO();

  if ( !file_exists( $sourcePath . DIRECTORY_SEPARATOR . 'backup' ) ) {
    mkdir( $sourcePath . DIRECTORY_SEPARATOR . 'backup', 0777, true );
  }

  $metaData = getMetaData( $rowid );

  createTable( 'backup' );

  $filename = sprintf( '%08d.%s.file', $metaData['rowid'], microtime( true ) );
  if ( !empty( $metaData['filename'] ) ) {
    copy( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'], $sourcePath . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $filename );
  } else {
    touch( $sourcePath . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $filename );
  }

  $stmt = $pdo->prepare( "INSERT INTO backup (id, action, settings, filename, created) VALUES (:id, :action, :settings, :filename, :created)" );
  $stmt->execute( [
    'id'       => $metaData['rowid'],
    'action'   => $action,
    'settings' => json_encode( $metaData ),
    'filename' => $filename,
    'created'  => time(),
  ] );
}

function blockCrawlers()
{
  if ( !isset( $_SERVER['HTTP_USER_AGENT'] ) ) return;
  if ( !function_exists( 'preg_match' ) ) return;
  $spiders = [
    'aboundexbot',
    'ahrefsbot',
    'backlinkcrawler',
    'baiduspider',
    'bingbot',
    'blekkobo',
    'blexbot',
    'dotbot',
    'dsearch',
    'duckduckbot',
    'exabot',
    'ezooms',
    'facebookexternalhit',
    'facebot',
    'gigabot',
    'googlebot',
    'ia_archiver',
    'linkdexbot',
    'lipperhey spider',
    'majestic-12',
    'majestic-seo',
    'meanpathbot',
    'megaindex',
    'mj12bot',
    'msnbot',
    'ncbot',
    'nutch',
    'pagesinventory',
    'rogerbot',
    'scoutjet',
    'searchmetricsbot',
    'semrushbot',
    'seokicks-robot',
    'sistrix',
    'sitebot',
    'slurp',
    'sogou',
    'spbot',
    'twitter',
    'yandexbot',
  ];
  if ( preg_match( '~(' . preg_quote( implode( '|', $spiders ), '~' ) . ')~i', $_SERVER['HTTP_USER_AGENT'] ) ) {
    http_response_code( 404 );
    echo base64_decode( 'PCFET0NUWVBFIEhUTUwgUFVCTElDICItLy9JRVRGLy9EVEQgSFRNTCAyLjAvL0VOIj4KPGh0bWw+PGhlYWQ+Cjx0aXRsZT40MDQgTm90IEZvdW5kPC90aXRsZT4KPC9oZWFkPjxib2R5Pgo8aDE+Tm90IEZvdW5kPC9oMT4KPHA+VGhlIHJlcXVlc3RlZCBVUkwgd2FzIG5vdCBmb3VuZCBvbiB0aGlzIHNlcnZlci48L3A+CjwvYm9keT48L2h0bWw+' );
    exit( 1 );
  }
}

function checkAccess()
{
  global $ACMS;

  if ( ACMS_SAFE_MODE || $ACMS['ACMS_SAFE_MODE'] ) {
    $_SESSION['archivarix.safe_mode'] = 1;
  } else {
    $_SESSION['archivarix.safe_mode'] = 0;
  }

  if ( !empty( $_SESSION['archivarix.logged'] ) ) {
    if ( strlen( $ACMS['ACMS_PASSWORD'] ) && password_verify( $ACMS['ACMS_PASSWORD'], $_SESSION['archivarix.logged'] ) ) return true;
    if ( strlen( ACMS_PASSWORD ) && password_verify( ACMS_PASSWORD, $_SESSION['archivarix.logged'] ) ) return true;
    if ( strlen( $ACMS['ACMS_SAFE_PASSWORD'] ) && password_verify( $ACMS['ACMS_SAFE_PASSWORD'], $_SESSION['archivarix.logged'] ) ) {
      $_SESSION['archivarix.safe_mode'] = 1;
      return true;
    }
    if ( strlen( ACMS_SAFE_PASSWORD ) && password_verify( ACMS_SAFE_PASSWORD, $_SESSION['archivarix.logged'] ) ) {
      $_SESSION['archivarix.safe_mode'] = 1;
      return true;
    }

    if ( !strlen( ACMS_PASSWORD ) && !strlen( $ACMS['ACMS_PASSWORD'] ) && !strlen( ACMS_SAFE_PASSWORD ) && !strlen( $ACMS['ACMS_SAFE_PASSWORD'] ) ) {
      unset( $_SESSION['archivarix.logged'] );
      return true;
    }
    unset( $_SESSION['archivarix.logged'] );
    return false;
  }

  if ( isset( $_POST['password'] ) && strlen( $_POST['password'] ) ) {
    if ( strlen( $ACMS['ACMS_SAFE_PASSWORD'] ) && password_verify( $_POST['password'], $ACMS['ACMS_SAFE_PASSWORD'] ) ) {
      $_SESSION['archivarix.logged']    = password_hash( $ACMS['ACMS_SAFE_PASSWORD'], PASSWORD_DEFAULT );
      $_SESSION['archivarix.safe_mode'] = 1;
      return true;
    }
    if ( strlen( ACMS_SAFE_PASSWORD ) && $_POST['password'] == ACMS_SAFE_PASSWORD ) {
      $_SESSION['archivarix.logged']    = password_hash( ACMS_SAFE_PASSWORD, PASSWORD_DEFAULT );
      $_SESSION['archivarix.safe_mode'] = 1;
      return true;
    }
    if ( strlen( $ACMS['ACMS_PASSWORD'] ) && password_verify( $_POST['password'], $ACMS['ACMS_PASSWORD'] ) ) {
      $_SESSION['archivarix.logged'] = password_hash( $ACMS['ACMS_PASSWORD'], PASSWORD_DEFAULT );
      return true;
    }
    if ( strlen( ACMS_PASSWORD ) && $_POST['password'] == ACMS_PASSWORD ) {
      $_SESSION['archivarix.logged'] = password_hash( ACMS_PASSWORD, PASSWORD_DEFAULT );
      return true;
    }
    error_log( "Archivarix CMS login failed; TIME: " . date( 'c' ) . "; IP: " . $_SERVER['REMOTE_ADDR'] );
    return false;
  }

  if (
    strlen( ACMS_PASSWORD ) ||
    strlen( $ACMS['ACMS_PASSWORD'] ) ||
    strlen( ACMS_SAFE_PASSWORD ) ||
    strlen( $ACMS['ACMS_SAFE_PASSWORD'] )
  ) return false;

  return true;
}

function checkAllowedIp()
{
  global $ACMS;

  if ( empty( $ACMS['ACMS_ALLOWED_IPS'] ) ) return true;
  $ipsCleaned = preg_replace( '~[^\d./,:]~', '', $ACMS['ACMS_ALLOWED_IPS'] );
  $ipsArray   = explode( ',', $ipsCleaned );

  foreach ( $ipsArray as $cidr ) {
    if ( matchCidr( $_SERVER['REMOTE_ADDR'], $cidr ) ) {
      return true;
    }
  }

  http_response_code( 404 );
  exit( 1 );
}

function checkIntegrationPrerequisite()
{
  $file = __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
  if ( !file_exists( $file ) ) return;
  if ( preg_match( '~@package[\s]+WordPress~', file_get_contents( $file ) ) ) return 'WordPress';
  if ( preg_match( '~@package[\s]+Joomla.Site~', file_get_contents( $file ) ) ) return 'Joomla';
  if ( preg_match( '~Copyright \(c\) MODX, LLC~', file_get_contents( $file ) ) ) return 'MODX';
}

function isInstalled()
{
  if ( getSourceRoot() !== false ) return true;
}

function inExpertMode()
{
  if ( !empty( $_SESSION['archivarix.expert'] ) ) return true;
}

function inSafeMode()
{
  if ( !empty( $_SESSION['archivarix.safe_mode'] ) ) return true;
}

function isSecureConnection()
{
  return (
    (
      !empty( $_SERVER['HTTPS'] )
      && $_SERVER['HTTPS'] !== 'off'
    )
    || (
      !empty( $_SERVER['SERVER_PORT'] )
      && $_SERVER['SERVER_PORT'] == 443
    )
    || (
      !empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] )
      && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
    )
  );
}

function checkPhpVersion()
{
  if ( version_compare( PHP_VERSION, '5.6.0', '>=' ) ) return true;
}


function checkSourceStructure()
{
  global $sourcePath;
  if ( !strlen( $sourcePath ) || $sourcePath == __DIR__ . DIRECTORY_SEPARATOR . '.content.tmp' ) return false;
  $ignoreFiles   = ['.acms.settings.json', '.loader.settings.json', '1px.png', 'empty.css', 'empty.ico', 'empty.js', 'robots.txt', 'structure.db', 'structure.db-shm', 'structure.db-wal', 'structure.json', 'structure.legacy.db',];
  $ignoreFolders = ['binary', 'html', 'backup', 'imports', 'exports', 'includes', 'templates'];
  $allowed       = array_merge( $ignoreFiles, $ignoreFolders, ['.', '..'] );
  $filesList     = scandir( $sourcePath );
  $extraFiles    = [];

  foreach ( $filesList as $filename ) {
    if ( in_array( $filename, $allowed ) ) continue;
    $extraFiles[] = $filename;
  }

  if ( empty( $extraFiles ) ) return false;

  addWarning( L( 'Attention! Your .content.xxxxxx directory contains extra files that do not belong there!' ) . '<br>' . sprintf( L( 'Extra files or directories found: %s' ), implode( ', ', $extraFiles ) ), 3, L( 'System check' ) );

  return true;
}

function checkRequiredStructure( $path = null )
{

  $subDirs = array_merge( range( '0', '9' ), range( 'a', 'f' ) );
  createDirectory( 'html', $path );
  foreach ( $subDirs as $subDir ) {
    createDirectory( 'html' . DIRECTORY_SEPARATOR . $subDir, $path );
  }
  createDirectory( 'binary', $path );
  foreach ( $subDirs as $subDir ) {
    createDirectory( 'binary' . DIRECTORY_SEPARATOR . $subDir, $path );
  }
  createDirectory( 'backup', $path );
  createDirectory( 'imports', $path );
  createDirectory( 'templates', $path );
  createDirectory( 'includes', $path );

}

function checkXsrf()
{
  if (
    !empty( $_POST )
    && (
      empty( $_POST['xsrf'] )
      || $_POST['xsrf'] !== $_SESSION['acms_xsrf']
    )
  ) {
    addWarning( L( 'Security token mismatch. The action was not performed. Your session is probably expired.' ), 4, L( 'Request check' ) );
    return;
  }
  return true;
}

function cleanTemplate( $content )
{
  $content = preg_replace( "~<[^>]*\{\{@(STRING|HTML|URL|FILE)\('[-a-z0-9]+'\)\}\}[^>]*>~is", "", $content ); // remove tags with empty params
  $content = preg_replace( "~\{\{@(STRING|HTML|URL|FILE)\('([-a-z0-9]+)'\)\}\}~is", "", $content );
  return $content;
}

function clearMissingUrls()
{
  $pdo = newPDO();
  if ( $pdo->exec( "DROP TABLE IF EXISTS missing" ) ) return true;
}

function cliDeleteUrls( $data )
{
  global $dsn;
  $deletedUrls = 0;

  $urlTemplate = [
    'rowid'       => 0,
    'url'         => '',
    'protocol'    => '',
    'hostname'    => '',
    'request_uri' => '',
    'mimetype'    => '',
    'charset'     => '',
    'filesize'    => '',
  ];

  $data = array_intersect_key( $data, $urlTemplate );
  if ( empty( $data ) ) return showWarningJson( ['action' => 'delete.urls', 'status' => 0, 'message' => ''], 1 );

  $sqlWhere = implode( ' AND ', array_map( function ( $v ) {
    return "{$v} = :{$v}";
  }, array_keys( $data ) ) );
  if ( !strlen( $sqlWhere ) ) return showWarningJson( ['action' => 'delete.urls', 'status' => 0, 'message' => ''], 1 );

  $dsn  = getDSN();
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "SELECT rowid FROM structure WHERE {$sqlWhere}" );
  $stmt->execute( $data );

  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $deletedUrls += removeUrl( $url['rowid'] );
  }
  showWarningJson( ['action' => 'deletle.urls', 'status' => 1, 'message' => 'Delete action performed', 'urls_deleted' => $deletedUrls], 0 );
}

function cliImportLoaderJson( $data )
{
  global $sourcePath;
  if ( !empty( $data['tmp_file'] ) && is_file( $data['tmp_file'] ) ) {
    $settings = json_decode( file_get_contents( $data['tmp_file'] ), true );
    unlink( $data['tmp_file'] );
  } else {
    $settings = $data;
  }
  if ( empty( $settings ) ) return;

  $settings = array_filter( $settings, function ( $k ) {
    return preg_match( '~^ARCHIVARIX_~i', $k );
  }, ARRAY_FILTER_USE_KEY );
  if ( !empty( $settings['ARCHIVARIX_CUSTOM_FILES'] ) ) {
    foreach ( $settings['ARCHIVARIX_CUSTOM_FILES'] as $customFile ) {
      createCustomFile( ['filename' => $customFile['filename'], 'content' => base64_decode( $customFile['content'] )] );
    }
  }
  unset( $settings['ARCHIVARIX_CUSTOM_FILES'] );
  $LOADER         = loadLoaderSettings();
  $LOADER         = array_merge( $LOADER, $settings );
  $loaderFilename = $sourcePath . DIRECTORY_SEPARATOR . '.loader.settings.json';
  file_put_contents( $loaderFilename, json_encode( $LOADER, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
  return true;
}

function cliPerformImport( $data )
{
  global $sourcePath;
  global $dsn;
  global $uuidSettings;
  global $ACMS;

  if ( $sourcePath === false ) {
    showWarningJson( ['action' => 'perform.import', 'status' => 0, 'message' => 'No installed website found'], 1 );
  }
  $ACMS['ACMS_TIMEOUT'] = 0;
  $importFileName       = $sourcePath . DIRECTORY_SEPARATOR . 'imports' . DIRECTORY_SEPARATOR . $data['uuid'] . '.zip';
  if ( !file_exists( $importFileName ) ) downloadFromSerial( $data['uuid'] ); // data['skip_download']
  $import = getImportInfo( $importFileName, true );
  if ( empty( $import ) ) {
    showWarningJson( ['action' => 'perform.import', 'status' => 0, 'message' => "Cannot import {$data['uuid']}.zip file"], 1 );
  }

  $dsn          = getDSN();
  $uuidSettings = getSettings();

  $data['hostnames'] = !empty( $data['hostnames'] ) ? $data['hostnames'] : array_keys( $import['info']['hostnames'] );
  $data['overwrite'] = !empty( $data['overwrite'] ) ? $data['overwrite'] : 'all|none|newer';
  importPerform( $import['filename'], $data );

  showWarningJson( ['action' => 'perform.install', 'status' => 1, 'message' => 'Import installed', 'uuid' => $data['uuid']], 1 );
}

function cliPerformInstall( $data, $settings )
{
  global $sourcePath;
  global $dsn;
  global $uuidSettings;
  global $ACMS;

  $cleanInstall = false;

  if ( $sourcePath === false ) {
    $cleanInstall = true;
    $sourcePath   = __DIR__ . DIRECTORY_SEPARATOR . '.content.tmp';
    if ( !file_exists( $sourcePath ) ) {
      mkdir( $sourcePath, 0777, true );
    }
  }

  $ACMS['ACMS_TIMEOUT'] = 0;
  $uuid                 = downloadFromSerial( $data['uuid'] );
  $importFileName       = $sourcePath . DIRECTORY_SEPARATOR . 'imports' . DIRECTORY_SEPARATOR . $uuid . '.zip';
  $import               = getImportInfo( $importFileName, true );


  if ( empty( $import ) ) {
    showWarningJson( ['action' => 'perform.install', 'status' => 0, 'message' => 'Cannot import .ZIP file'], 1 );
  }

  if ( $cleanInstall ) {
    $sourcePath = createStructure( $import );
  }
  $dsn = getDSN();
  deleteBackups( ['all' => 1] );
  deleteDirectory( $sourcePath . DIRECTORY_SEPARATOR . 'html' );
  deleteDirectory( $sourcePath . DIRECTORY_SEPARATOR . 'binary' );
  checkRequiredStructure( $sourcePath );
  dropTable( 'structure' );
  createTable( 'structure' );
  dropTable( 'settings' );
  createTable( 'settings' );

  $pdo                                  = newPDO();
  $import['info']['settings']['schema'] = getSchemaLatest();
  foreach ( $import['info']['settings'] as $param => $value ) {
    $stmt = $pdo->prepare( "INSERT INTO settings VALUES(:param, :value)" );
    $stmt->execute( ['param' => $param, 'value' => $value] );
  }

  $uuidSettings = getSettings();

  unset( $ACMS['ACMS_TIMEOUT'] );
  loadAcmsSettings();
  setAcmsSettings( $settings );

  $ACMS['ACMS_DISABLE_HISTORY'] = 1;
  $ACMS['ACMS_TIMEOUT']         = 0;

  rename( $import['zip_path'], $sourcePath . DIRECTORY_SEPARATOR . 'imports' . DIRECTORY_SEPARATOR . $import['filename'] );
  $importSettings['overwrite'] = 'all';
  $importSettings['hostnames'] = array_keys( $import['info']['hostnames'] );
  importPerform( $import['filename'], $importSettings );


  $installMode = checkIntegrationPrerequisite() ? 'integration' : 'clean';
  putLoader( __DIR__, $installMode );

  if ( !empty( $cleanInstall ) ) {
    deleteDirectory( __DIR__ . DIRECTORY_SEPARATOR . '.content.tmp' . DIRECTORY_SEPARATOR );
  }

  unset( $ACMS['ACMS_DISABLE_HISTORY'] );
  unset( $ACMS['ACMS_TIMEOUT'] );

  showWarningJson( ['action' => 'perform.install', 'status' => 1, 'message' => 'Website installed'], 1 );
}

function cliUpdateAcmsSettings( $settings )
{
  global $sourcePath;
  if ( $sourcePath === false ) {
    showWarningJson( ['action' => 'update.acms.settings', 'status' => 0, 'message' => 'Website is not found.'], 1 );
  }
  setAcmsSettings( $settings );
  showWarningJson( ['action' => 'update.acms.settings', 'status' => 1, 'message' => 'CMS settings updated.'], 1 );
}

function cliUpdateLoaderSettings( $settings )
{
  global $sourcePath;
  if ( $sourcePath === false ) {
    showWarningJson( ['action' => 'update.loader.settings', 'status' => 0, 'message' => 'Website is not found.'], 1 );
  }
  setLoaderSettings( $settings );
  showWarningJson( ['action' => 'update.loader.settings', 'status' => 1, 'message' => 'Loader settings updated.'], 1 );
}

function cloneUrl( $input )
{
  global $sourcePath;

  $metaData = getMetaData( $input['urlID'] );

  if ( $metaData['request_uri'] == encodePath( $input['request_uri'] )
    && $metaData['hostname'] == convertIdnToAscii( $input['hostname'] )
  ) {
    addWarning( L( 'You cannot create a URL with a path that already exists.' ), 4, L( 'Clone URL' ) );
    return false;
  }

  if ( strlen( $input['hostname'] ) ) {
    $metaData['hostname'] = convertIdnToAscii( $input['hostname'] );
  }

  $pdo  = newPDO();
  $stmt = $pdo->prepare( 'INSERT INTO structure (url,protocol,hostname,request_uri,folder,filename,mimetype,charset,filesize,filetime,url_original,enabled,redirect) VALUES (:url,:protocol,:hostname,:request_uri,:folder,:filename,:mimetype,:charset,:filesize,:filetime,:url_original,:enabled,:redirect)' );
  $stmt->execute( [
    'url'          => $metaData['protocol'] . '://' . $metaData['hostname'] . encodePath( $input['request_uri'] ),
    'protocol'     => $metaData['protocol'],
    'hostname'     => $metaData['hostname'],
    'request_uri'  => encodePath( $input['request_uri'] ),
    'folder'       => $metaData['folder'],
    'filename'     => '',
    'mimetype'     => $metaData['mimetype'],
    'charset'      => $metaData['charset'],
    'filesize'     => filesize( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'] ),
    'filetime'     => date( 'YmdHis' ),
    'url_original' => '',
    'enabled'      => $metaData['enabled'],
    'redirect'     => $metaData['redirect'],
  ] );

  $cloneID = $pdo->lastInsertId();
  if ( $cloneID ) {
    $cloneFileExtension = pathinfo( $metaData['filename'], PATHINFO_EXTENSION );
    copy( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'], $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . sprintf( '%s/%s.%08d.%s', substr( convertPathToFilename( $input['request_uri'] ), 0, 1 ), convertPathToFilename( $input['request_uri'] ), $cloneID, $cloneFileExtension ) );
    $stmt = $pdo->prepare( 'UPDATE structure SET filename = :filename WHERE rowid = :rowid' );
    $stmt->execute( [
      'filename' => sprintf( '%s/%s.%08d.%s', substr( convertPathToFilename( $input['request_uri'] ), 0, 1 ), convertPathToFilename( $input['request_uri'] ), $cloneID, $cloneFileExtension ),
      'rowid'    => $cloneID,
    ] );
    backupFile( $cloneID, 'create' );
    return $cloneID;
  }
}

function convertDomain( $domain )
{
  global $ACMS;
  global $uuidSettings;
  global $LOADER;
  if ( !$LOADER ) $LOADER = loadLoaderSettings();

  if ( $LOADER['ARCHIVARIX_CUSTOM_DOMAIN'] ) {
    return preg_replace( '~' . preg_quote( $uuidSettings['domain'], '$~' ) . '~', $LOADER['ARCHIVARIX_CUSTOM_DOMAIN'], $domain, 1 );
  }
  if ( $ACMS['ACMS_CUSTOM_DOMAIN'] ) {
    return preg_replace( '~' . preg_quote( $uuidSettings['domain'], '$~' ) . '~', $ACMS['ACMS_CUSTOM_DOMAIN'], $domain, 1 );
  }
  if ( !$ACMS['ACMS_CUSTOM_DOMAIN'] && isset( $_SERVER['HTTP_HOST'] ) && substr( $_SERVER['HTTP_HOST'], -strlen( $uuidSettings['domain'] ) ) !== $uuidSettings['domain'] ) {
    return $_SERVER['HTTP_HOST'];
  }

  return $domain;
}

function convertEncoding( $content, $to, $from )
{
  if ( strtolower( $to ) == strtolower( $from ) ) {
    return $content;
  }

  $supported_charsets = ['437', '500', '500V1', '850', '851', '852', '855', '856', '857', '860', '861', '862', '863', '864', '865', '866', '866NAV', '869', '874', '904', '1026', '1046', '1047', '8859_1', '8859_2', '8859_3', '8859_4', '8859_5', '8859_6', '8859_7', '8859_8', '8859_9', '10646-1:1993', '10646-1:1993/UCS4', 'ANSI_X3.4-1968', 'ANSI_X3.4-1986', 'ANSI_X3.4', 'ANSI_X3.110-1983', 'ANSI_X3.110', 'ARABIC', 'ARABIC7', 'ARMSCII-8', 'ASCII', 'ASMO-708', 'ASMO_449', 'BALTIC', 'BIG-5', 'BIG-FIVE', 'BIG5-HKSCS', 'BIG5', 'BIG5HKSCS', 'BIGFIVE', 'BRF', 'BS_4730', 'CA', 'CN-BIG5', 'CN-GB', 'CN', 'CP-AR', 'CP-GR', 'CP-HU', 'CP037', 'CP038', 'CP273', 'CP274', 'CP275', 'CP278', 'CP280', 'CP281', 'CP282', 'CP284', 'CP285', 'CP290', 'CP297', 'CP367', 'CP420', 'CP423', 'CP424', 'CP437', 'CP500', 'CP737', 'CP770', 'CP771', 'CP772', 'CP773', 'CP774', 'CP775', 'CP803', 'CP813', 'CP819', 'CP850', 'CP851', 'CP852', 'CP855', 'CP856', 'CP857', 'CP860', 'CP861', 'CP862', 'CP863', 'CP864', 'CP865', 'CP866', 'CP866NAV', 'CP868', 'CP869', 'CP870', 'CP871', 'CP874', 'CP875', 'CP880', 'CP891', 'CP901', 'CP902', 'CP903', 'CP904', 'CP905', 'CP912', 'CP915', 'CP916', 'CP918', 'CP920', 'CP921', 'CP922', 'CP930', 'CP932', 'CP933', 'CP935', 'CP936', 'CP937', 'CP939', 'CP949', 'CP950', 'CP1004', 'CP1008', 'CP1025', 'CP1026', 'CP1046', 'CP1047', 'CP1070', 'CP1079', 'CP1081', 'CP1084', 'CP1089', 'CP1097', 'CP1112', 'CP1122', 'CP1123', 'CP1124', 'CP1125', 'CP1129', 'CP1130', 'CP1132', 'CP1133', 'CP1137', 'CP1140', 'CP1141', 'CP1142', 'CP1143', 'CP1144', 'CP1145', 'CP1146', 'CP1147', 'CP1148', 'CP1149', 'CP1153', 'CP1154', 'CP1155', 'CP1156', 'CP1157', 'CP1158', 'CP1160', 'CP1161', 'CP1162', 'CP1163', 'CP1164', 'CP1166', 'CP1167', 'CP1250', 'CP1251', 'CP1252', 'CP1253', 'CP1254', 'CP1255', 'CP1256', 'CP1257', 'CP1258', 'CP1282', 'CP1361', 'CP1364', 'CP1371', 'CP1388', 'CP1390', 'CP1399', 'CP4517', 'CP4899', 'CP4909', 'CP4971', 'CP5347', 'CP9030', 'CP9066', 'CP9448', 'CP10007', 'CP12712', 'CP16804', 'CPIBM861', 'CSA7-1', 'CSA7-2', 'CSASCII', 'CSA_T500-1983', 'CSA_T500', 'CSA_Z243.4-1985-1', 'CSA_Z243.4-1985-2', 'CSA_Z243.419851', 'CSA_Z243.419852', 'CSDECMCS', 'CSEBCDICATDE', 'CSEBCDICATDEA', 'CSEBCDICCAFR', 'CSEBCDICDKNO', 'CSEBCDICDKNOA', 'CSEBCDICES', 'CSEBCDICESA', 'CSEBCDICESS', 'CSEBCDICFISE', 'CSEBCDICFISEA', 'CSEBCDICFR', 'CSEBCDICIT', 'CSEBCDICPT', 'CSEBCDICUK', 'CSEBCDICUS', 'CSEUCKR', 'CSEUCPKDFMTJAPANESE', 'CSGB2312', 'CSHPROMAN8', 'CSIBM037', 'CSIBM038', 'CSIBM273', 'CSIBM274', 'CSIBM275', 'CSIBM277', 'CSIBM278', 'CSIBM280', 'CSIBM281', 'CSIBM284', 'CSIBM285', 'CSIBM290', 'CSIBM297', 'CSIBM420', 'CSIBM423', 'CSIBM424', 'CSIBM500', 'CSIBM803', 'CSIBM851', 'CSIBM855', 'CSIBM856', 'CSIBM857', 'CSIBM860', 'CSIBM863', 'CSIBM864', 'CSIBM865', 'CSIBM866', 'CSIBM868', 'CSIBM869', 'CSIBM870', 'CSIBM871', 'CSIBM880', 'CSIBM891', 'CSIBM901', 'CSIBM902', 'CSIBM903', 'CSIBM904', 'CSIBM905', 'CSIBM918', 'CSIBM921', 'CSIBM922', 'CSIBM930', 'CSIBM932', 'CSIBM933', 'CSIBM935', 'CSIBM937', 'CSIBM939', 'CSIBM943', 'CSIBM1008', 'CSIBM1025', 'CSIBM1026', 'CSIBM1097', 'CSIBM1112', 'CSIBM1122', 'CSIBM1123', 'CSIBM1124', 'CSIBM1129', 'CSIBM1130', 'CSIBM1132', 'CSIBM1133', 'CSIBM1137', 'CSIBM1140', 'CSIBM1141', 'CSIBM1142', 'CSIBM1143', 'CSIBM1144', 'CSIBM1145', 'CSIBM1146', 'CSIBM1147', 'CSIBM1148', 'CSIBM1149', 'CSIBM1153', 'CSIBM1154', 'CSIBM1155', 'CSIBM1156', 'CSIBM1157', 'CSIBM1158', 'CSIBM1160', 'CSIBM1161', 'CSIBM1163', 'CSIBM1164', 'CSIBM1166', 'CSIBM1167', 'CSIBM1364', 'CSIBM1371', 'CSIBM1388', 'CSIBM1390', 'CSIBM1399', 'CSIBM4517', 'CSIBM4899', 'CSIBM4909', 'CSIBM4971', 'CSIBM5347', 'CSIBM9030', 'CSIBM9066', 'CSIBM9448', 'CSIBM12712', 'CSIBM16804', 'CSIBM11621162', 'CSISO4UNITEDKINGDOM', 'CSISO10SWEDISH', 'CSISO11SWEDISHFORNAMES', 'CSISO14JISC6220RO', 'CSISO15ITALIAN', 'CSISO16PORTUGESE', 'CSISO17SPANISH', 'CSISO18GREEK7OLD', 'CSISO19LATINGREEK', 'CSISO21GERMAN', 'CSISO25FRENCH', 'CSISO27LATINGREEK1', 'CSISO49INIS', 'CSISO50INIS8', 'CSISO51INISCYRILLIC', 'CSISO58GB1988', 'CSISO60DANISHNORWEGIAN', 'CSISO60NORWEGIAN1', 'CSISO61NORWEGIAN2', 'CSISO69FRENCH', 'CSISO84PORTUGUESE2', 'CSISO85SPANISH2', 'CSISO86HUNGARIAN', 'CSISO88GREEK7', 'CSISO89ASMO449', 'CSISO90', 'CSISO92JISC62991984B', 'CSISO99NAPLPS', 'CSISO103T618BIT', 'CSISO111ECMACYRILLIC', 'CSISO121CANADIAN1', 'CSISO122CANADIAN2', 'CSISO139CSN369103', 'CSISO141JUSIB1002', 'CSISO143IECP271', 'CSISO150', 'CSISO150GREEKCCITT', 'CSISO151CUBA', 'CSISO153GOST1976874', 'CSISO646DANISH', 'CSISO2022CN', 'CSISO2022JP', 'CSISO2022JP2', 'CSISO2022KR', 'CSISO2033', 'CSISO5427CYRILLIC', 'CSISO5427CYRILLIC1981', 'CSISO5428GREEK', 'CSISO10367BOX', 'CSISOLATIN1', 'CSISOLATIN2', 'CSISOLATIN3', 'CSISOLATIN4', 'CSISOLATIN5', 'CSISOLATIN6', 'CSISOLATINARABIC', 'CSISOLATINCYRILLIC', 'CSISOLATINGREEK', 'CSISOLATINHEBREW', 'CSKOI8R', 'CSKSC5636', 'CSMACINTOSH', 'CSNATSDANO', 'CSNATSSEFI', 'CSN_369103', 'CSPC8CODEPAGE437', 'CSPC775BALTIC', 'CSPC850MULTILINGUAL', 'CSPC862LATINHEBREW', 'CSPCP852', 'CSSHIFTJIS', 'CSUCS4', 'CSUNICODE', 'CSWINDOWS31J', 'CUBA', 'CWI-2', 'CWI', 'CYRILLIC', 'DE', 'DEC-MCS', 'DEC', 'DECMCS', 'DIN_66003', 'DK', 'DS2089', 'DS_2089', 'E13B', 'EBCDIC-AT-DE-A', 'EBCDIC-AT-DE', 'EBCDIC-BE', 'EBCDIC-BR', 'EBCDIC-CA-FR', 'EBCDIC-CP-AR1', 'EBCDIC-CP-AR2', 'EBCDIC-CP-BE', 'EBCDIC-CP-CA', 'EBCDIC-CP-CH', 'EBCDIC-CP-DK', 'EBCDIC-CP-ES', 'EBCDIC-CP-FI', 'EBCDIC-CP-FR', 'EBCDIC-CP-GB', 'EBCDIC-CP-GR', 'EBCDIC-CP-HE', 'EBCDIC-CP-IS', 'EBCDIC-CP-IT', 'EBCDIC-CP-NL', 'EBCDIC-CP-NO', 'EBCDIC-CP-ROECE', 'EBCDIC-CP-SE', 'EBCDIC-CP-TR', 'EBCDIC-CP-US', 'EBCDIC-CP-WT', 'EBCDIC-CP-YU', 'EBCDIC-CYRILLIC', 'EBCDIC-DK-NO-A', 'EBCDIC-DK-NO', 'EBCDIC-ES-A', 'EBCDIC-ES-S', 'EBCDIC-ES', 'EBCDIC-FI-SE-A', 'EBCDIC-FI-SE', 'EBCDIC-FR', 'EBCDIC-GREEK', 'EBCDIC-INT', 'EBCDIC-INT1', 'EBCDIC-IS-FRISS', 'EBCDIC-IT', 'EBCDIC-JP-E', 'EBCDIC-JP-KANA', 'EBCDIC-PT', 'EBCDIC-UK', 'EBCDIC-US', 'EBCDICATDE', 'EBCDICATDEA', 'EBCDICCAFR', 'EBCDICDKNO', 'EBCDICDKNOA', 'EBCDICES', 'EBCDICESA', 'EBCDICESS', 'EBCDICFISE', 'EBCDICFISEA', 'EBCDICFR', 'EBCDICISFRISS', 'EBCDICIT', 'EBCDICPT', 'EBCDICUK', 'EBCDICUS', 'ECMA-114', 'ECMA-118', 'ECMA-128', 'ECMA-CYRILLIC', 'ECMACYRILLIC', 'ELOT_928', 'ES', 'ES2', 'EUC-CN', 'EUC-JISX0213', 'EUC-JP-MS', 'EUC-JP', 'EUC-KR', 'EUC-TW', 'EUCCN', 'EUCJP-MS', 'EUCJP-OPEN', 'EUCJP-WIN', 'EUCJP', 'EUCKR', 'EUCTW', 'FI', 'FR', 'GB', 'GB2312', 'GB13000', 'GB18030', 'GBK', 'GB_1988-80', 'GB_198880', 'GEORGIAN-ACADEMY', 'GEORGIAN-PS', 'GOST_19768-74', 'GOST_19768', 'GOST_1976874', 'GREEK-CCITT', 'GREEK', 'GREEK7-OLD', 'GREEK7', 'GREEK7OLD', 'GREEK8', 'GREEKCCITT', 'HEBREW', 'HP-GREEK8', 'HP-ROMAN8', 'HP-ROMAN9', 'HP-THAI8', 'HP-TURKISH8', 'HPGREEK8', 'HPROMAN8', 'HPROMAN9', 'HPTHAI8', 'HPTURKISH8', 'HU', 'IBM-803', 'IBM-856', 'IBM-901', 'IBM-902', 'IBM-921', 'IBM-922', 'IBM-930', 'IBM-932', 'IBM-933', 'IBM-935', 'IBM-937', 'IBM-939', 'IBM-943', 'IBM-1008', 'IBM-1025', 'IBM-1046', 'IBM-1047', 'IBM-1097', 'IBM-1112', 'IBM-1122', 'IBM-1123', 'IBM-1124', 'IBM-1129', 'IBM-1130', 'IBM-1132', 'IBM-1133', 'IBM-1137', 'IBM-1140', 'IBM-1141', 'IBM-1142', 'IBM-1143', 'IBM-1144', 'IBM-1145', 'IBM-1146', 'IBM-1147', 'IBM-1148', 'IBM-1149', 'IBM-1153', 'IBM-1154', 'IBM-1155', 'IBM-1156', 'IBM-1157', 'IBM-1158', 'IBM-1160', 'IBM-1161', 'IBM-1162', 'IBM-1163', 'IBM-1164', 'IBM-1166', 'IBM-1167', 'IBM-1364', 'IBM-1371', 'IBM-1388', 'IBM-1390', 'IBM-1399', 'IBM-4517', 'IBM-4899', 'IBM-4909', 'IBM-4971', 'IBM-5347', 'IBM-9030', 'IBM-9066', 'IBM-9448', 'IBM-12712', 'IBM-16804', 'IBM037', 'IBM038', 'IBM256', 'IBM273', 'IBM274', 'IBM275', 'IBM277', 'IBM278', 'IBM280', 'IBM281', 'IBM284', 'IBM285', 'IBM290', 'IBM297', 'IBM367', 'IBM420', 'IBM423', 'IBM424', 'IBM437', 'IBM500', 'IBM775', 'IBM803', 'IBM813', 'IBM819', 'IBM848', 'IBM850', 'IBM851', 'IBM852', 'IBM855', 'IBM856', 'IBM857', 'IBM860', 'IBM861', 'IBM862', 'IBM863', 'IBM864', 'IBM865', 'IBM866', 'IBM866NAV', 'IBM868', 'IBM869', 'IBM870', 'IBM871', 'IBM874', 'IBM875', 'IBM880', 'IBM891', 'IBM901', 'IBM902', 'IBM903', 'IBM904', 'IBM905', 'IBM912', 'IBM915', 'IBM916', 'IBM918', 'IBM920', 'IBM921', 'IBM922', 'IBM930', 'IBM932', 'IBM933', 'IBM935', 'IBM937', 'IBM939', 'IBM943', 'IBM1004', 'IBM1008', 'IBM1025', 'IBM1026', 'IBM1046', 'IBM1047', 'IBM1089', 'IBM1097', 'IBM1112', 'IBM1122', 'IBM1123', 'IBM1124', 'IBM1129', 'IBM1130', 'IBM1132', 'IBM1133', 'IBM1137', 'IBM1140', 'IBM1141', 'IBM1142', 'IBM1143', 'IBM1144', 'IBM1145', 'IBM1146', 'IBM1147', 'IBM1148', 'IBM1149', 'IBM1153', 'IBM1154', 'IBM1155', 'IBM1156', 'IBM1157', 'IBM1158', 'IBM1160', 'IBM1161', 'IBM1162', 'IBM1163', 'IBM1164', 'IBM1166', 'IBM1167', 'IBM1364', 'IBM1371', 'IBM1388', 'IBM1390', 'IBM1399', 'IBM4517', 'IBM4899', 'IBM4909', 'IBM4971', 'IBM5347', 'IBM9030', 'IBM9066', 'IBM9448', 'IBM12712', 'IBM16804', 'IEC_P27-1', 'IEC_P271', 'INIS-8', 'INIS-CYRILLIC', 'INIS', 'INIS8', 'INISCYRILLIC', 'ISIRI-3342', 'ISIRI3342', 'ISO-2022-CN-EXT', 'ISO-2022-CN', 'ISO-2022-JP-2', 'ISO-2022-JP-3', 'ISO-2022-JP', 'ISO-2022-KR', 'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5', 'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-9E', 'ISO-8859-10', 'ISO-8859-11', 'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'ISO-8859-16', 'ISO-10646', 'ISO-10646/UCS2', 'ISO-10646/UCS4', 'ISO-10646/UTF-8', 'ISO-10646/UTF8', 'ISO-CELTIC', 'ISO-IR-4', 'ISO-IR-6', 'ISO-IR-8-1', 'ISO-IR-9-1', 'ISO-IR-10', 'ISO-IR-11', 'ISO-IR-14', 'ISO-IR-15', 'ISO-IR-16', 'ISO-IR-17', 'ISO-IR-18', 'ISO-IR-19', 'ISO-IR-21', 'ISO-IR-25', 'ISO-IR-27', 'ISO-IR-37', 'ISO-IR-49', 'ISO-IR-50', 'ISO-IR-51', 'ISO-IR-54', 'ISO-IR-55', 'ISO-IR-57', 'ISO-IR-60', 'ISO-IR-61', 'ISO-IR-69', 'ISO-IR-84', 'ISO-IR-85', 'ISO-IR-86', 'ISO-IR-88', 'ISO-IR-89', 'ISO-IR-90', 'ISO-IR-92', 'ISO-IR-98', 'ISO-IR-99', 'ISO-IR-100', 'ISO-IR-101', 'ISO-IR-103', 'ISO-IR-109', 'ISO-IR-110', 'ISO-IR-111', 'ISO-IR-121', 'ISO-IR-122', 'ISO-IR-126', 'ISO-IR-127', 'ISO-IR-138', 'ISO-IR-139', 'ISO-IR-141', 'ISO-IR-143', 'ISO-IR-144', 'ISO-IR-148', 'ISO-IR-150', 'ISO-IR-151', 'ISO-IR-153', 'ISO-IR-155', 'ISO-IR-156', 'ISO-IR-157', 'ISO-IR-166', 'ISO-IR-179', 'ISO-IR-193', 'ISO-IR-197', 'ISO-IR-199', 'ISO-IR-203', 'ISO-IR-209', 'ISO-IR-226', 'ISO/TR_11548-1', 'ISO646-CA', 'ISO646-CA2', 'ISO646-CN', 'ISO646-CU', 'ISO646-DE', 'ISO646-DK', 'ISO646-ES', 'ISO646-ES2', 'ISO646-FI', 'ISO646-FR', 'ISO646-FR1', 'ISO646-GB', 'ISO646-HU', 'ISO646-IT', 'ISO646-JP-OCR-B', 'ISO646-JP', 'ISO646-KR', 'ISO646-NO', 'ISO646-NO2', 'ISO646-PT', 'ISO646-PT2', 'ISO646-SE', 'ISO646-SE2', 'ISO646-US', 'ISO646-YU', 'ISO2022CN', 'ISO2022CNEXT', 'ISO2022JP', 'ISO2022JP2', 'ISO2022KR', 'ISO6937', 'ISO8859-1', 'ISO8859-2', 'ISO8859-3', 'ISO8859-4', 'ISO8859-5', 'ISO8859-6', 'ISO8859-7', 'ISO8859-8', 'ISO8859-9', 'ISO8859-9E', 'ISO8859-10', 'ISO8859-11', 'ISO8859-13', 'ISO8859-14', 'ISO8859-15', 'ISO8859-16', 'ISO11548-1', 'ISO88591', 'ISO88592', 'ISO88593', 'ISO88594', 'ISO88595', 'ISO88596', 'ISO88597', 'ISO88598', 'ISO88599', 'ISO88599E', 'ISO885910', 'ISO885911', 'ISO885913', 'ISO885914', 'ISO885915', 'ISO885916', 'ISO_646.IRV:1991', 'ISO_2033-1983', 'ISO_2033', 'ISO_5427-EXT', 'ISO_5427', 'ISO_5427:1981', 'ISO_5427EXT', 'ISO_5428', 'ISO_5428:1980', 'ISO_6937-2', 'ISO_6937-2:1983', 'ISO_6937', 'ISO_6937:1992', 'ISO_8859-1', 'ISO_8859-1:1987', 'ISO_8859-2', 'ISO_8859-2:1987', 'ISO_8859-3', 'ISO_8859-3:1988', 'ISO_8859-4', 'ISO_8859-4:1988', 'ISO_8859-5', 'ISO_8859-5:1988', 'ISO_8859-6', 'ISO_8859-6:1987', 'ISO_8859-7', 'ISO_8859-7:1987', 'ISO_8859-7:2003', 'ISO_8859-8', 'ISO_8859-8:1988', 'ISO_8859-9', 'ISO_8859-9:1989', 'ISO_8859-9E', 'ISO_8859-10', 'ISO_8859-10:1992', 'ISO_8859-14', 'ISO_8859-14:1998', 'ISO_8859-15', 'ISO_8859-15:1998', 'ISO_8859-16', 'ISO_8859-16:2001', 'ISO_9036', 'ISO_10367-BOX', 'ISO_10367BOX', 'ISO_11548-1', 'ISO_69372', 'IT', 'JIS_C6220-1969-RO', 'JIS_C6229-1984-B', 'JIS_C62201969RO', 'JIS_C62291984B', 'JOHAB', 'JP-OCR-B', 'JP', 'JS', 'JUS_I.B1.002', 'KOI-7', 'KOI-8', 'KOI8-R', 'KOI8-RU', 'KOI8-T', 'KOI8-U', 'KOI8', 'KOI8R', 'KOI8U', 'KSC5636', 'L1', 'L2', 'L3', 'L4', 'L5', 'L6', 'L7', 'L8', 'L10', 'LATIN-9', 'LATIN-GREEK-1', 'LATIN-GREEK', 'LATIN1', 'LATIN2', 'LATIN3', 'LATIN4', 'LATIN5', 'LATIN6', 'LATIN7', 'LATIN8', 'LATIN9', 'LATIN10', 'LATINGREEK', 'LATINGREEK1', 'MAC-CENTRALEUROPE', 'MAC-CYRILLIC', 'MAC-IS', 'MAC-SAMI', 'MAC-UK', 'MAC', 'MACCYRILLIC', 'MACINTOSH', 'MACIS', 'MACUK', 'MACUKRAINIAN', 'MIK', 'MS-ANSI', 'MS-ARAB', 'MS-CYRL', 'MS-EE', 'MS-GREEK', 'MS-HEBR', 'MS-MAC-CYRILLIC', 'MS-TURK', 'MS932', 'MS936', 'MSCP949', 'MSCP1361', 'MSMACCYRILLIC', 'MSZ_7795.3', 'MS_KANJI', 'NAPLPS', 'NATS-DANO', 'NATS-SEFI', 'NATSDANO', 'NATSSEFI', 'NC_NC0010', 'NC_NC00-10', 'NC_NC00-10:81', 'NF_Z_62-010', 'NF_Z_62-010_(1973)', 'NF_Z_62-010_1973', 'NF_Z_62010', 'NF_Z_62010_1973', 'NO', 'NO2', 'NS_4551-1', 'NS_4551-2', 'NS_45511', 'NS_45512', 'OS2LATIN1', 'OSF00010001', 'OSF00010002', 'OSF00010003', 'OSF00010004', 'OSF00010005', 'OSF00010006', 'OSF00010007', 'OSF00010008', 'OSF00010009', 'OSF0001000A', 'OSF00010020', 'OSF00010100', 'OSF00010101', 'OSF00010102', 'OSF00010104', 'OSF00010105', 'OSF00010106', 'OSF00030010', 'OSF0004000A', 'OSF0005000A', 'OSF05010001', 'OSF100201A4', 'OSF100201A8', 'OSF100201B5', 'OSF100201F4', 'OSF100203B5', 'OSF1002011C', 'OSF1002011D', 'OSF1002035D', 'OSF1002035E', 'OSF1002035F', 'OSF1002036B', 'OSF1002037B', 'OSF10010001', 'OSF10010004', 'OSF10010006', 'OSF10020025', 'OSF10020111', 'OSF10020115', 'OSF10020116', 'OSF10020118', 'OSF10020122', 'OSF10020129', 'OSF10020352', 'OSF10020354', 'OSF10020357', 'OSF10020359', 'OSF10020360', 'OSF10020364', 'OSF10020365', 'OSF10020366', 'OSF10020367', 'OSF10020370', 'OSF10020387', 'OSF10020388', 'OSF10020396', 'OSF10020402', 'OSF10020417', 'PT', 'PT2', 'PT154', 'R8', 'R9', 'RK1048', 'ROMAN8', 'ROMAN9', 'RUSCII', 'SE', 'SE2', 'SEN_850200_B', 'SEN_850200_C', 'SHIFT-JIS', 'SHIFT_JIS', 'SHIFT_JISX0213', 'SJIS-OPEN', 'SJIS-WIN', 'SJIS', 'SS636127', 'STRK1048-2002', 'ST_SEV_358-88', 'T.61-8BIT', 'T.61', 'T.618BIT', 'TCVN-5712', 'TCVN', 'TCVN5712-1', 'TCVN5712-1:1993', 'THAI8', 'TIS-620', 'TIS620-0', 'TIS620.2529-1', 'TIS620.2533-0', 'TIS620', 'TS-5881', 'TSCII', 'TURKISH8', 'UCS-2', 'UCS-2BE', 'UCS-2LE', 'UCS-4', 'UCS-4BE', 'UCS-4LE', 'UCS2', 'UCS4', 'UHC', 'UJIS', 'UK', 'UNICODE', 'UNICODEBIG', 'UNICODELITTLE', 'US-ASCII', 'US', 'UTF-7', 'UTF-8', 'UTF-16', 'UTF-16BE', 'UTF-16LE', 'UTF-32', 'UTF-32BE', 'UTF-32LE', 'UTF7', 'UTF8', 'UTF16', 'UTF16BE', 'UTF16LE', 'UTF32', 'UTF32BE', 'UTF32LE', 'VISCII', 'WCHAR_T', 'WIN-SAMI-2', 'WINBALTRIM', 'WINDOWS-31J', 'WINDOWS-874', 'WINDOWS-936', 'WINDOWS-1250', 'WINDOWS-1251', 'WINDOWS-1252', 'WINDOWS-1253', 'WINDOWS-1254', 'WINDOWS-1255', 'WINDOWS-1256', 'WINDOWS-1257', 'WINDOWS-1258', 'WINSAMI2', 'WS2', 'YU'];

  if ( empty( $to ) ) {
    $to = 'utf-8';
  }

  if ( empty( $from ) ) {
    $from = 'utf-8';
  }

  if (
    function_exists( 'mb_convert_encoding' )
    && count( preg_grep( '~^' . preg_quote( $to, '~' ) . '$~i', mb_list_encodings() ) )
    && count( preg_grep( '~^' . preg_quote( $from, '~' ) . '$~i', mb_list_encodings() ) )
  ) {
    return mb_convert_encoding( $content, $to, $from );
  }

  if (
    function_exists( 'iconv' )
    && count( preg_grep( '~^' . preg_quote( $to, '~' ) . '$~i', $supported_charsets ) )
    && count( preg_grep( '~^' . preg_quote( $from, '~' ) . '$~i', $supported_charsets ) )
  ) {
    return iconv( $from . '//IGNORE', $to . '//IGNORE', $content );
  }

  return $content;
}

function convertFiletimeToHuman( $filetime )
{
  return preg_replace( '~([\d]{4})([\d]{2})([\d]{2})([\d]{2})([\d]{2})([\d]{2})~', '$1-$2-$3 $4:$5:$6', $filetime );
}

function convertHtmlEncoding( $html, $to, $from )
{
  $html = convertEncoding( $html, $to, $from );
  $html = preg_replace( '~<meta[\s]+charset=[^>]+>~is', '<meta charset="' . $to . '">', $html );
  $html = preg_replace( '~<meta[\s]+[^>]*\bhttp-equiv\b[^>]+content-type[^>]+>~is', '<meta http-equiv="content-type" content="text/html; charset=' . $to . '">', $html );
  return $html;
}

function convertHumanTimeToUnix( $humantime )
{
  $humantime = str_pad( $humantime, 14, 0 );
  $humantime = convertFiletimeToHuman( $humantime );
  return strtotime( $humantime );
}

function convertIdnToAscii( $string )
{
  if ( function_exists( 'idn_to_ascii' ) ) {
    if ( defined( 'INTL_IDNA_VARIANT_UTS46' ) ) {
      return idn_to_ascii( $string, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46 );
    } elseif ( defined( 'INTL_IDNA_VARIANT_2003' ) ) {
      return idn_to_ascii( $string, IDNA_DEFAULT, INTL_IDNA_VARIANT_2003 );
    } else {
      return idn_to_ascii( $string, IDNA_DEFAULT );
    }
  }
  return $string;
}

function convertIdnToUtf8( $string )
{
  if ( function_exists( 'idn_to_utf8' ) ) {
    if ( defined( 'INTL_IDNA_VARIANT_UTS46' ) ) {
      return idn_to_utf8( $string, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46 );
    } elseif ( defined( 'INTL_IDNA_VARIANT_2003' ) ) {
      return idn_to_utf8( $string, IDNA_DEFAULT, INTL_IDNA_VARIANT_2003 );
    } else {
      return idn_to_utf8( $string, IDNA_DEFAULT );
    }
  }
  return $string;
}

function convertPathToFilename( $path, $limit = 130 )
{
  return sha1( $path );
  // [TODO] old approach
  $search  = ['?', '/', ' ', '\'', '\\', ':', '/', '*', '"', '<', '>', '|'];
  $replace = [';', '!', '+', '', '', '', '', '', '', '', '', ''];
  if ( $limit ) {
    if ( function_exists( 'mb_substr' ) ) {
      return mb_substr( str_replace( $search, $replace, urldecode( $path ) ), 0, 130 );
    }
    return substr( str_replace( $search, $replace, urldecode( $path ) ), 0, 130 );
  }
  return str_replace( $search, $replace, urldecode( $path ) );
}

function convertUTF8( $taskOffset = 0 )
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $sourcePath;
  global $ACMS;

  $stats = array_merge( ['pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  $mimeTypeSql = "'text/html', 'text/css', 'application/javascript', 'application/x-javascript', 'text/javascript', 'text/plain', 'application/json', 'application/xml', 'text/xml'";

  $pdo  = newPDO();
  $pdo2 = newPDO();
  if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdo->query( "SELECT COUNT(1) FROM structure WHERE mimetype IN ({$mimeTypeSql}) AND charset != '' AND charset != 'utf-8'" )->fetchColumn() );
  $stmt = $pdo->prepare( "SELECT rowid, * FROM structure WHERE mimetype IN ({$mimeTypeSql}) AND charset != '' AND charset != 'utf-8' AND rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );

  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    backupFile( $url['rowid'], 'convert' );
    $file = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
    $html = convertEncoding( file_get_contents( $file ), 'utf-8', $url['charset'] );
    $html = preg_replace( '~<meta[\s]+charset=[^>]+>~is', '<meta charset="utf-8">', $html );
    $html = preg_replace( '~<meta[\s]+[^>]*\bhttp-equiv\b[^>]+content-type[^>]+>~is', '<meta http-equiv="content-type" content="text/html; charset=utf-8">', $html );
    file_put_contents( $file, $html );
    updateFilesize( $url['rowid'], filesize( $file ) );
    $pdo2->exec( "UPDATE structure SET charset = 'utf-8' WHERE rowid = {$url['rowid']}" );
    $stats['pages']++;
    $stats['processed']++;

    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $url['rowid'];
      return $stats;
    }
  }

  if ( $stats['processed'] ) createBackupBreakpoint( L( 'Website conversion to UTF-8' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['processed'], 0 ) ) );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return $stats['pages'];
}

function convertWebp()
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $sourcePath;
  global $ACMS;

  $stats = array_merge( ['pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  $imagickFormats = \Imagick::queryformats();
  if ( !in_array( 'WEBP', $imagickFormats ) ) return $stats['pages'];

  if ( empty( $stats['total'] ) ) $stats['total'] = sqlGetValue( "SELECT COUNT(1) FROM structure WHERE mimetype IN ('image/jpeg','image/gif','image/png', 'image/apng')" );
  $pdo  = newPDO();
  $stmt = $pdo->query( "SELECT rowid as urlID, * FROM structure WHERE mimetype IN ('image/jpeg','image/gif','image/png', 'image/apng') ORDER BY rowid" );
  while ( $metaData = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $imagePath = $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'];
    try {
      $imagick = new Imagick( $imagePath );
    } catch ( ImagickException $e ) {
      $imagick = null;
      continue;
    }
    $imagick->writeImage( "webp:{$imagePath}" );
    $metaData['mimetype'] = 'image/webp';
    updateUrlSettings( $metaData );
    updateFilesize( $metaData['urlID'], filesize( $imagePath ) );
    $stats['pages']++;
    $stats['processed']++;

    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $metaData['urlID'];
      return $stats;
    }
  }

  if ( $stats['processed'] ) createBackupBreakpoint( L( 'Images conversion to WebP' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['processed'], 0 ) ) );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return $stats['pages'];
}

function copyRecursive( $source, $destination )
{
  $directory = opendir( $source );
  if ( !file_exists( $destination ) ) mkdir( $destination, 0777, true );
  while ( false !== ( $file = readdir( $directory ) ) ) {
    if ( !in_array( $file, ['.', '..'] ) ) {
      if ( is_dir( $source . DIRECTORY_SEPARATOR . $file ) ) {
        copyRecursive( $source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file );
      } else {
        copy( $source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file );
      }
    }
  }
  closedir( $directory );
}

function copyUrl( $metaDataNew )
{
  global $sourcePath;
  global $uuidSettings;

  $mimeNew                 = getMimeInfo( $metaDataNew['mimetype'] );
  $metaDataNew['protocol'] = !empty( $uuidSettings['https'] ) ? 'https' : 'http';
  $metaDataNew['folder']   = $mimeNew['folder'];

  $pdo  = newPDO();
  $stmt = $pdo->prepare( 'INSERT INTO structure (url,protocol,hostname,request_uri,folder,filename,mimetype,charset,filesize,filetime,url_original,enabled,redirect) VALUES (:url,:protocol,:hostname,:request_uri,:folder,:filename,:mimetype,:charset,:filesize,:filetime,:url_original,:enabled,:redirect)' );
  $stmt->execute( [
    'url'          => $metaDataNew['protocol'] . '://' . $metaDataNew['hostname'] . $metaDataNew['request_uri'],
    'protocol'     => $metaDataNew['protocol'],
    'hostname'     => $metaDataNew['hostname'],
    'request_uri'  => $metaDataNew['request_uri'],
    'folder'       => $metaDataNew['folder'],
    'filename'     => '',
    'mimetype'     => $metaDataNew['mimetype'],
    'charset'      => $metaDataNew['charset'],
    'filesize'     => $metaDataNew['filesize'],
    'filetime'     => $metaDataNew['filetime'],
    'url_original' => $metaDataNew['url_original'],
    'enabled'      => $metaDataNew['enabled'],
    'redirect'     => $metaDataNew['redirect'],
  ] );

  $newId                   = $pdo->lastInsertId();
  $metaDataNew['filename'] = sprintf( '%s/%s.%08d.%s', substr( convertPathToFilename( $metaDataNew['request_uri'] ), 0, 1 ), convertPathToFilename( $metaDataNew['request_uri'] ), $newId, $mimeNew['extension'] );
  $stmt                    = $pdo->prepare( "UPDATE structure SET filename = :filename WHERE rowid = :rowid" );
  $stmt->execute( ['filename' => $metaDataNew['filename'], 'rowid' => $newId] );

  copy( $metaDataNew['tmp_file_path'], $sourcePath . DIRECTORY_SEPARATOR . $metaDataNew['folder'] . DIRECTORY_SEPARATOR . $metaDataNew['filename'] );

  backupFile( $newId, 'create' );
}

function createBackupBreakpoint( $name )
{
  createTable( 'backup' );
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "INSERT INTO backup (id, action, settings, created) VALUES (0, 'breakpoint', :settings, :created)" );
  $stmt->execute( [
    'settings' => json_encode( ['name' => $name], JSON_UNESCAPED_UNICODE ),
    'created'  => time(),
  ] );
}

function createCustomFile( $input )
{
  if ( inSafeMode() && preg_match( '~[<]([?%]|[^>]*script\b[^>]*\blanguage\b[^>]*\bphp\b)~is', $input['content'] ) ) {
    addWarning( L( 'You cannot create or edit custom files with a PHP code in a safe mode.' ), 4, L( 'Custom Files' ) );
    return false;
  }
  $includesPath = createDirectory( 'includes' );
  $filename     = basename( $input['filename'] );
  if ( !preg_match( '~^[-.\w]+$~i', $filename ) || in_array( $filename, ['.', '..'] ) ) $filename = date( 'Ymd_His' ) . '.txt';
  $file = $includesPath . DIRECTORY_SEPARATOR . $filename;
  file_put_contents( $file, $input['content'] );
  return true;
}

function createCustomRule( $input )
{
  global $sourcePath;
  $LOADER                                = loadLoaderSettings();
  $input['FILE']                         = basename( $input['FILE'] );
  $LOADER['ARCHIVARIX_INCLUDE_CUSTOM'][] = $input;
  $loaderFile                            = $sourcePath . DIRECTORY_SEPARATOR . '.loader.settings.json';
  file_put_contents( $loaderFile, json_encode( $LOADER, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
}

function createDirectory( $directoryName, $path = null )
{
  global $sourcePath;
  if ( $path === null ) $path = $sourcePath;
  $directoryPath = $path . DIRECTORY_SEPARATOR . $directoryName;
  if ( !file_exists( $directoryPath ) ) {
    mkdir( $directoryPath, 0777, true );
  }
  return $directoryPath;
}

function createPage404( $path = '/_404.html' )
{
  global $sourcePath;
  $stats   = ['processed' => 0, 'time' => 0];
  $page404 = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
</body></html>';
  $pdo     = newPDO();
  $stmt    = $pdo->prepare( 'SELECT DISTINCT hostname FROM structure' );
  $stmt->execute();
  while ( $hostname = $stmt->fetchColumn() ) {
    $rowid    = createUrl( [
      'hostname' => $hostname,
      'path'     => $path,
      'folder'   => 'html',
      'mime'     => 'text/html',
      'charset'  => 'utf-8',
    ] );
    $metaData = getMetaData( $rowid );
    file_put_contents( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'], $page404 );
    updateFilesize( $rowid, strlen( $page404 ) );
    $stats['processed']++;
  }
  createBackupBreakpoint( L( 'Custom 404 pages' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['processed'], 0 ) ) );
}

function createRobotsTxt( $params = [] )
{
  global $uuidSettings;
  global $sourcePath;
  $stats = ['processed' => 0, 'time' => 0];
  if ( !empty( $params['sitemap_include'] ) ) {
    if ( empty( $params['sitemap'] ) ) $params['sitemap'] = detectSitemapUrl();
    $sitemap = parse_url( $params['sitemap'] );
    if ( !empty( $sitemap['path'] ) ) {
      $LOADER                            = loadLoaderSettings();
      $LOADER['ARCHIVARIX_SITEMAP_PATH'] = $sitemap['path'];
      setLoaderSettings( $LOADER );
    }
  }
  $pdo  = newPDO();
  $stmt = $pdo->query( 'SELECT DISTINCT hostname FROM structure' );

  while ( $hostname = $stmt->fetchColumn() ) {
    $robotsTxt = "User-agent: *\nDisallow:";
    if ( !empty( $sitemap['path'] ) ) {
      $subdomain = preg_replace( '~' . preg_quote( $uuidSettings['domain'] ) . '$~', '', $hostname );
      if ( $subdomain == 'www.' && !empty( $uuidSettings['www'] ) ) $subdomain = '';
      $robotsTxt .= "\n\nSitemap: " . $sitemap['scheme'] . '://' . $subdomain . $sitemap['host'] . $sitemap['path'];
    }

    $rowid = urlExists( ( empty( $uuidSettings['https'] ) ? 'http' : 'https' ) . '://' . $hostname . '/robots.txt' );
    if ( $rowid ) {
      $metaData = getMetaData( $rowid );
      backupFile( $rowid, 'edit' );
      file_put_contents( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'], $robotsTxt );
      $metaData['urlID']    = $rowid;
      $metaData['mimetype'] = 'text/plain';
      $metaData['charset']  = 'utf-8';
      $metaData['redirect'] = '';
      updateUrlSettings( $metaData );
      updateFilesize( $rowid, strlen( $robotsTxt ) );
    } else {
      $rowid    = createUrl( [
        'hostname' => $hostname,
        'path'     => '/robots.txt',
        'folder'   => 'html',
        'mime'     => 'text/plain',
        'charset'  => 'utf-8',
      ] );
      $metaData = getMetaData( $rowid );
      file_put_contents( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'], $robotsTxt );
      updateFilesize( $rowid, strlen( $robotsTxt ) );
    }
    $stats['processed']++;
  }
  createBackupBreakpoint( L( 'Allow website in robots.txt' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['processed'], 0 ) ) );
  return;
}

function createStructure( $info )
{
  $contentFolder = __DIR__ . DIRECTORY_SEPARATOR . '.content.' . getRandomString( 8 );
  $newDbFile     = $contentFolder . DIRECTORY_SEPARATOR . 'structure.db';
  checkRequiredStructure( $contentFolder );
  touch( $contentFolder . DIRECTORY_SEPARATOR . 'empty.css' );
  touch( $contentFolder . DIRECTORY_SEPARATOR . 'empty.js' );
  touch( $contentFolder . DIRECTORY_SEPARATOR . 'empty.js' );
  file_put_contents( $contentFolder . DIRECTORY_SEPARATOR . '1px.png', base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQAAAAA3bvkkAAAAAnRSTlMAAHaTzTgAAAAKSURBVAjXY2AAAAACAAHiIbwzAAAAAElFTkSuQmCC' ) );
  file_put_contents( $contentFolder . DIRECTORY_SEPARATOR . 'empty.ico', base64_decode( 'AAABAAEAEBACAAEAAQCwAAAAFgAAACgAAAAQAAAAIAAAAAEAAQAAAAAAQAAAAAAAAAAAAAAAAgAAAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA' ) );
  $newDb = new PDO( "sqlite:{$newDbFile}" );
  $newDb->exec( "PRAGMA journal_mode=WAL" );
  createTable( 'structure', $newDb );
  createTable( 'settings', $newDb );
  createTable( 'meta', $newDb );
  createTable( 'templates', $newDb );
  createTable( 'missing', $newDb );
  $info['info']['settings']['schema'] = getSchemaLatest();
  foreach ( $info['info']['settings'] as $param => $value ) {
    $stmt = $newDb->prepare( "INSERT INTO settings VALUES(:param, :value)" );
    $stmt->execute( ['param' => $param, 'value' => $value] );
  }
  return $contentFolder;
}

function createTable( $tableName, $pdo = null )
{
  if ( $pdo == null ) $pdo = newPDO();

  switch ( $tableName ) :
    case 'backup' :
      $pdo->exec( "CREATE TABLE IF NOT EXISTS backup (id INTEGER, action TEXT, settings TEXT, filename TEXT, created INTEGER DEFAULT (STRFTIME('%s','now')))" );
      break;
    case 'templates' :
      $pdo->exec( "CREATE TABLE IF NOT EXISTS templates (name TEXT PRIMARY KEY, hostname TEXT, mimetype TEXT, charset TEXT, uploads TEXT, path TEXT)" );
      break;
    case 'meta' :
      $pdo->exec( "CREATE TABLE IF NOT EXISTS meta (name TEXT PRIMARY KEY, data TEXT)" );
      break;
    case 'structure' :
      $pdo->exec( "CREATE TABLE IF NOT EXISTS structure (url TEXT, protocol TEXT, hostname TEXT, request_uri TEXT, folder TEXT, filename TEXT, mimetype TEXT, charset TEXT, filesize INTEGER, filetime INTEGER, url_original TEXT, enabled INTEGER DEFAULT 1, redirect TEXT, depth INTEGER DEFAULT 0, metrics TEXT DEFAULT '')" );
      $pdo->exec( "CREATE UNIQUE INDEX IF NOT EXISTS url_index ON structure (url)" );
      $pdo->exec( "CREATE INDEX IF NOT EXISTS hostname_index ON structure (hostname)" );
      $pdo->exec( "CREATE INDEX IF NOT EXISTS mimetype_index ON structure (mimetype)" );
      $pdo->exec( "CREATE INDEX IF NOT EXISTS request_uri_index ON structure (request_uri);" );
      break;
    case 'settings' :
      $pdo->exec( "CREATE TABLE IF NOT EXISTS settings (param TEXT PRIMARY KEY, value TEXT)" );
      break;
    case 'missing' :
      $pdo->exec( 'CREATE TABLE IF NOT EXISTS missing (url TEXT PRIMARY KEY, status INTEGER DEFAULT 0, ignore INTEGER DEFAULT 0)' );
      break;
  endswitch;
}

function createTemplateRecord( $template )
{
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "INSERT INTO templates (name, hostname, mimetype, charset, uploads, path) VALUES (:name, :hostname, :mimetype, :charset, :uploads, :path)" );
  $stmt->bindParam( 'name', $template['name'], PDO::PARAM_STR );
  $stmt->bindParam( 'hostname', $template['hostname'], PDO::PARAM_STR );
  $stmt->bindParam( 'mimetype', $template['mimetype'], PDO::PARAM_STR );
  $stmt->bindParam( 'charset', $template['charset'], PDO::PARAM_STR );
  $stmt->bindParam( 'uploads', $template['uploads'], PDO::PARAM_STR );
  $stmt->bindParam( 'path', $template['path'], PDO::PARAM_STR );
  $stmt->execute();
}

function createTemplateFromPage( $name, $rowid )
{
  if ( !preg_match( '~^[-a-z0-9_]+$~i', $name ) ) return false;
  global $sourcePath;
  $name          = getTemplateNameAvailable( $name );
  $url           = getMetaData( $rowid );
  $templatesPath = createDirectory( 'templates' );
  $pdo           = newPDO();
  $stmt          = $pdo->prepare( "INSERT INTO templates (name, hostname, mimetype, charset, uploads, path) VALUES (:name, :hostname, :mimetype, :charset, '/uploads/%md5%', '/page-path')" );
  $stmt->bindParam( 'name', $name, PDO::PARAM_STR );
  $stmt->bindParam( 'hostname', $url['hostname'], PDO::PARAM_STR );
  $stmt->bindParam( 'mimetype', $url['mimetype'], PDO::PARAM_STR );
  $stmt->bindParam( 'charset', $url['charset'], PDO::PARAM_STR );
  $stmt->execute();
  copy( $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'], $templatesPath . DIRECTORY_SEPARATOR . $name . '.html' );
  return $name;
}

function createTemplatePage( $input )
{
  global $sourcePath;
  $files = [];
  if ( !$template = getTemplate( $input['template'] ) ) return;
  $templateInfo        = getTemplateInfo( $input['template'] );
  $input['path']       = !empty( $input['path'] ) ? $input['path'] : $template['path'];
  $input['path_latin'] = !empty( $input['path_latin'] ) ? 1 : 0;


  // download all FILES params with URLs

  foreach ( $input['params'] as $paramName => $paramValue ) {
    if ( !isset( $templateInfo['params'][$paramName] ) ) continue;
    if ( !$paramValue ) continue;
    // check files passed as string (url | path)
    if ( $templateInfo['params'][$paramName][0]['type'] == 'FILE' ) {
      if ( filter_var( $paramValue, FILTER_SANITIZE_URL ) ) {
        $tmpFile  = tempnam( getTempDirectory(), 'archivarix.' );
        $response = downloadFile( $paramValue, $tmpFile );
        if ( empty( $response['http_code'] ) || $response['http_code'] != 200 ) continue;
        $fileMime = preg_replace( '~(^[^;]*)(.*)~is', '$1', $response['content_type'] );
      } elseif ( is_file( $paramValue ) ) {
        $tmpFile  = realpath( $paramValue );
        $fileMime = mime_content_type( $tmpFile );
      } else continue;
      $_FILES['params']['name'][$paramName]     = $paramValue;
      $_FILES['params']['type'][$paramName]     = $fileMime;
      $_FILES['params']['tmp_name'][$paramName] = $tmpFile;
      $_FILES['params']['error'][$paramName]    = 0;
      $_FILES['params']['size'][$paramName]     = filesize( $tmpFile );
      continue; // avoid content replace
    }
    // replace text values
    foreach ( $templateInfo['params'][$paramName] as $paramInfo ) {
      $paramContent = $paramValue;
      if ( $templateInfo['params'][$paramName]['0']['type'] == 'STRING' ) $paramContent = htmlspecialchars( $paramValue ); // html escape STRING value
      $template['content'] = preg_replace( '~' . preg_quote( $paramInfo['string'], '~i' ) . '~', convertEncoding( $paramContent, $template['charset'], 'utf-8' ), $template['content'] );
    }
  }

  // uploaded files to $files array
  if ( !empty( $_FILES['params']['tmp_name'] ) ) {
    foreach ( $_FILES['params']['tmp_name'] as $key => $fileTmpName ) {
      if ( empty( $fileTmpName ) ) continue;
      $fileMime      = getMimeInfo( $_FILES['params']['type'][$key] );
      $fileExtension = !empty( pathinfo( $_FILES['params']['name'][$key], PATHINFO_EXTENSION ) ) ? strtolower( pathinfo( $_FILES['params']['name'][$key], PATHINFO_EXTENSION ) ) : $fileMime['extension'];
      $filePath      = $template['uploads'];
      $filePath      = preg_replace( '~%md5%~', md5_file( $fileTmpName ), $filePath );
      $filePath      = preg_replace( '~%filename%~', basename( $_FILES['params']['name'][$key] ), $filePath );
      $filePath      = preg_replace( '~%ext%~', $fileExtension, $filePath );
      $filePath      = preg_replace( '~%year%~', date( 'Y' ), $filePath );
      $filePath      = preg_replace( '~%month%~', date( 'm' ), $filePath );
      $filePath      = preg_replace( '~%day%~', date( 'd' ), $filePath );
      $filePath      = preg_replace( '~%hour%~', date( 'H' ), $filePath );
      $filePath      = preg_replace( '~%minute%~', date( 'i' ), $filePath );
      $filePath      = preg_replace( '~%second%~', date( 's' ), $filePath );
      foreach ( $input['params'] as $paramName => $paramValue ) {
        $filePath = preg_replace( '~%' . preg_quote( $paramName, '~' ) . '%~', sanitizeString( $paramValue, 200, 1, '-' ), $filePath );
      }
      $filePath        = preg_replace( '~%[\w]+%~', '', $filePath );
      $filePath        = preg_replace( '~[/]{2,}~', '/', $filePath );
      $filePath        = getPathAvailable( $filePath );
      $url['hostname'] = $template['hostname'];
      $url['path']     = $filePath;
      $url['mime']     = $_FILES['params']['type'][$key];
      $url['charset']  = ( $fileMime['folder'] == 'html' ? 'utf-8' : '' );
      $rowid           = createUrl( $url );
      $url             = getMetaData( $rowid );
      $fileName        = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
      is_uploaded_file( $fileTmpName ) ? move_uploaded_file( $fileTmpName, $fileName ) : copy( $fileTmpName, $fileName );
      updateFilesize( $rowid, filesize( $fileName ) );
      $files[$key] = $url['request_uri'];
    }
  }

  // files
  foreach ( $files as $fileKey => $filePath ) {
    $template['content'] = preg_replace( "~\{\{@FILE\('" . $fileKey . "'\)\}\}~is", $filePath, $template['content'] );
  }

  $url['hostname'] = $template['hostname'];
  $url['mime']     = $template['mimetype'];
  $url['charset']  = $template['charset'];

  // path
  $url['path'] = $input['path'];
  $url['path'] = preg_replace( '~%year%~', date( 'Y' ), $url['path'] );
  $url['path'] = preg_replace( '~%month%~', date( 'm' ), $url['path'] );
  $url['path'] = preg_replace( '~%day%~', date( 'd' ), $url['path'] );
  $url['path'] = preg_replace( '~%hour%~', date( 'H' ), $url['path'] );
  $url['path'] = preg_replace( '~%minute%~', date( 'i' ), $url['path'] );
  $url['path'] = preg_replace( '~%second%~', date( 's' ), $url['path'] );
  $url['path'] = preg_replace( '~[/]{2,}~', '/', $url['path'] );
  foreach ( $input['params'] as $paramName => $paramValue ) {
    $url['path'] = preg_replace( '~%' . preg_quote( $paramName, '~' ) . '%~', sanitizeString( $paramValue, 200, $input['path_latin'], '-' ), $url['path'] );
  }
  $url['path'] = preg_replace( '~%[\w]+%~', '', $url['path'] );
  $url['path'] = preg_replace( '~[/]{2,}~', '/', $url['path'] );
  $url['path'] = getPathAvailable( $url['path'] );

  // additional built-in replaces
  $template['content'] = preg_replace( "~\{\{@URL\('path'\)\}\}~is", $url['path'], $template['content'] );
  $template['content'] = preg_replace( "~\{\{@URL\('hostname'\)\}\}~is", convertDomain( $template['hostname'] ), $template['content'] ); // [TODO] CUSTOM_DOMAIN

  // clean left unused params
  $template['content'] = cleanTemplate( $template['content'] );

  $rowid = createUrl( $url );
  if ( $rowid ) {
    $url  = getMetaData( $rowid );
    $file = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
    file_put_contents( $file, $template['content'] ); // [TODO] convertEncoding() back
    updateFilesize( $rowid, filesize( $file ) );
    return $rowid;
  }
}

function createUrl( $input )
{
  if ( pathExists( $input['hostname'], $input['path'] ) ) {
    addWarning( L( 'You cannot create a URL with a path that already exists.' ), 4, L( 'Create a new URL' ) );
    return;
  }

  global $uuidSettings;
  global $sourcePath;

  $protocol         = ( !empty( $uuidSettings['https'] ) ? 'https' : 'http' );
  $input['charset'] = !empty( $input['charset'] ) ? $input['charset'] : '';
  $mime             = getMimeInfo( $input['mime'] );
  $pdo              = newPDO();
  $stmt             = $pdo->prepare( "INSERT INTO structure (url,protocol,hostname,request_uri,folder,filename,mimetype,charset,filesize,filetime,url_original,enabled,redirect) VALUES (:url,:protocol,:hostname,:request_uri,:folder,:filename,:mimetype,:charset,:filesize,:filetime,:url_original,:enabled,:redirect)" );
  $stmt->execute( [
    'url'          => $protocol . '://' . $input['hostname'] . encodePath( $input['path'] ),
    'protocol'     => $protocol,
    'hostname'     => convertIdnToAscii( $input['hostname'] ),
    'request_uri'  => encodePath( $input['path'] ),
    'folder'       => $mime['folder'],
    'filename'     => '',
    'mimetype'     => $input['mime'],
    'charset'      => $input['charset'],
    'filesize'     => 0,
    'filetime'     => date( 'YmdHis' ),
    'url_original' => '',
    'enabled'      => 1,
    'redirect'     => '',
  ] );

  $createID = $pdo->lastInsertId();
  if ( $createID ) {
    $file = $sourcePath . DIRECTORY_SEPARATOR . $mime['folder'] . DIRECTORY_SEPARATOR . sprintf( '%s/%s.%08d.%s', substr( convertPathToFilename( $input['path'] ), 0, 1 ), convertPathToFilename( $input['path'] ), $createID, $mime['extension'] );
    if ( !empty( $_FILES['create_file']['tmp_name'] ) ) {
      move_uploaded_file( $_FILES['create_file']['tmp_name'], $file );
    } elseif ( !empty( $input['tmp_file'] ) && file_exists( $input['tmp_file'] ) ) {
      copy( $input['tmp_file'], $file );
    } elseif ( !empty( $input['url_file'] ) ) {
      downloadFile( $input['url_file'], $file );
    } else {
      touch( $file );
    }
    $stmt = $pdo->prepare( 'UPDATE structure SET filename = :filename, filesize = :filesize WHERE rowid = :rowid' );
    $stmt->execute( [
      'filename' => sprintf( '%s/%s.%08d.%s', substr( convertPathToFilename( $input['path'] ), 0, 1 ), convertPathToFilename( $input['path'] ), $createID, $mime['extension'] ),
      'filesize' => filesize( $file ),
      'rowid'    => $createID,
    ] );
    backupFile( $createID, 'create' );
    return $createID;
  }
}

function curlContent( $url, $timeout = 5, $agent = null )
{
  if ( empty( $agent ) ) $agent = "Archivarix-CMS/" . ACMS_VERSION . " (+https://archivarix.com/en/cms/)";
  $options = [
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_URL            => $url,
    CURLOPT_FAILONERROR    => true,
    CURLOPT_TIMEOUT        => $timeout,
    CURLOPT_USERAGENT      => $agent,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false,
  ];
  $ch      = curl_init();
  curl_setopt_array( $ch, $options );
  return curl_exec( $ch );
}

function customRuleExists( $filename )
{
  if ( !strlen( basename( $filename ) ) ) return false;
  $LOADER = loadLoaderSettings();
  if ( empty( $LOADER['ARCHIVARIX_INCLUDE_CUSTOM'] ) ) return false;
  foreach ( $LOADER['ARCHIVARIX_INCLUDE_CUSTOM'] as $customRule ) {
    if ( $customRule['FILE'] == $filename ) return true;
  }
}

function dataLogo()
{
  return "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0MDMgNzYiPjxzdHlsZT4uc3Qwe2VuYWJsZS1iYWNrZ3JvdW5kOm5ld30uc3Qxe2ZpbGwtcnVsZTpldmVub2RkO2NsaXAtcnVsZTpldmVub2RkO2ZpbGw6I2ZmYTcwMH0uc3Qye2ZpbGw6I2ZmZn08L3N0eWxlPjxnIGlkPSJFbGxpcHNlXzFfMV8iIGNsYXNzPSJzdDAiPjxjaXJjbGUgY2xhc3M9InN0MSIgY3g9IjM4LjgiIGN5PSIzOCIgcj0iMzcuNiIgaWQ9IkVsbGlwc2VfMV8zXyIvPjwvZz48cGF0aCBjbGFzcz0ic3QyIiBkPSJNMjMuNCAxOS4xYzEuOS0uOCAzLjctMS4yIDUuNC0xLjIgMS40IDAgMi45LjUgNC41IDEuNi44LjYgMS44IDEuNyAyLjggMy40LjcgMS4yIDEuNiAzLjMgMi42IDYuM2w1LjMgMTVjMS4yIDMuNCAyLjUgNiAzLjcgOCAxLjMgMiAyLjQgMy41IDMuNCA0LjVzMi4xIDEuNyAzLjMgMi4xYzEuMS40IDIuMS42IDIuOC42czEuNC0uMSAyLS4ydi40Yy0xLjQuNS0yLjcuNy00LjEuNy0xLjMgMC0yLjctLjMtNC0xLTEuMy0uNy0yLjYtMS42LTMuNy0yLjhDNDUgNTQgNDIuOSA1MC4xIDQxLjIgNDVsLTEuNy00LjlIMjcuNmwtMyA3LjdjLS4xLjMtLjIuNy0uMiAxIDAgLjMuMi43LjUgMS4xcy44LjYgMS40LjZoLjN2LjRoLTguN3YtLjRoLjRjLjcgMCAxLjQtLjIgMi0uNi43LS40IDEuMi0xIDEuNi0xLjlsMTAuOC0yNS44Yy0xLjYtMi4yLTMuNS0zLjMtNS44LTMuMy0xIDAtMi4yLjItMy4zLjdsLS4yLS41em00LjcgMTkuNmgxMWwtMy40LTEwLjFjLS43LTEuOS0xLjMtMy41LTEuOC00LjZsLTUuOCAxNC43eiIgaWQ9IkEiLz48ZyBpZD0iQXJjaGl2YXJpeCI+PHBhdGggY2xhc3M9InN0MiIgZD0iTTk1LjIgMTQuMmMyLjItLjkgNC4zLTEuMyA2LjMtMS4zIDEuNyAwIDMuNC42IDUuMSAxLjggMSAuNyAyIDIgMy4zIDMuOS44IDEuMyAxLjggMy44IDMgNy4zbDYuMSAxNy4zYzEuNCAzLjkgMi44IDYuOSA0LjMgOS4yIDEuNSAyLjMgMi44IDQgNCA1LjJzMi41IDIgMy44IDIuNCAyLjQuNyAzLjIuNyAxLjYtLjEgMi4zLS4ydi41Yy0xLjYuNS0zLjIuOC00LjcuOHMtMy4xLS40LTQuNi0xLjJjLTEuNS0uOC0zLTEuOS00LjMtMy4zLTIuOS0yLjktNS4zLTcuMy03LjMtMTMuM2wtMS45LTUuN0gxMDBsLTMuNSA4LjljLS4yLjQtLjMuOC0uMyAxLjIgMCAuNC4yLjguNSAxLjNzLjkuNyAxLjYuN2guNHYuNWgtOS45di0uNWguNWMuOCAwIDEuNi0uMiAyLjQtLjcuOC0uNSAxLjQtMS4yIDEuOS0yLjJMMTA2IDE3LjhjLTEuOC0yLjYtNC0zLjktNi43LTMuOS0xLjIgMC0yLjUuMy0zLjkuOGwtLjItLjV6bTUuNCAyMi43aDEyLjdsLTQtMTEuN2MtLjgtMi4yLTEuNS00LTIuMS01LjNsLTYuNiAxN3oiLz48cGF0aCBjbGFzcz0ic3QyIiBkPSJNMTM4LjIgMTcuNUgxMzV2MjkuOGMwIC45LjMgMS42LjkgMi4yLjYuNiAxLjQuOSAyLjMuOWguNmwuMS41aC0xMXYtLjVoLjZjLjkgMCAxLjYtLjMgMi4yLS45LjYtLjYuOS0xLjMgMS0yLjJWMTkuNmMwLS45LS40LTEuNi0xLTIuMi0uNi0uNi0xLjQtLjktMi4yLS45aC0uNlYxNmgxMy41YzMgMCA1LjQuOCA3LjEgMi41IDEuNyAxLjcgMi42IDMuOSAyLjYgNi42IDAgMi43LS44IDUtMi41IDYuOS0xLjYgMi0zLjcgMi45LTYgMi45LjUuMiAxLjEuNyAxLjggMS40czEuMyAxLjQgMS44IDIuMWMyLjkgNC4xIDQuNyA2LjYgNS42IDcuNi45LjkgMS41IDEuNiAxLjggMS45LjQuNC44LjcgMS4yIDEgLjQuMy45LjYgMS4zLjggMSAuNSAyIC43IDMuMS43di41aC0yLjhjLTEuNCAwLTIuOC0uMy00LS44LTEuMi0uNS0yLjItMS0yLjgtMS42LS42LS41LTEuMS0xLjEtMS42LTEuNi0uNC0uNS0xLjctMi4yLTMuNy01LjItMi0yLjktMy4yLTQuNi0zLjUtNS0uMy0uNC0uNy0uOC0xLTEuMi0xLjEtMS4xLTIuMS0xLjctMy4yLTEuN3YtLjVjLjMgMCAuNi4xIDEgLjFzMSAwIDEuNi0uMWM0LjEtLjEgNi43LTEuOCA3LjgtNS4yLjItLjcuMy0xLjMuMy0xLjl2LTEuMWMtLjEtMi4yLS42LTQtMS44LTUuNC0xLjEtMS40LTIuNi0yLjEtNC41LTIuMmgtMi44ek0xNjQuNSA0Ni42Yy0zLjMtMy4zLTUtNy43LTUtMTMuMiAwLTUuNSAxLjctOS44IDUtMTMuMiAzLjMtMy4zIDcuNy01IDEzLjItNSA0LjUgMCA4LjQgMS4xIDExLjkgMy40bDEgN2gtLjZjLS43LTIuOS0yLjItNS4xLTQuNC02LjZzLTQuOS0yLjMtOC0yLjNjLTQuNCAwLTcuOSAxLjUtMTAuNSA0LjYtMi42IDMtMy45IDctMy45IDEyczEuMyA5IDMuOSAxMi4xYzIuNiAzLjEgNiA0LjYgMTAuMiA0LjcgMy43IDAgNi45LTEgOS40LTMgMi43LTIuMiA0LjMtNS44IDQuOS0xMC44aC40bC0uNiA3LjhjLTMgNS03LjcgNy41LTE0IDcuNS01LjMgMC05LjYtMS43LTEyLjktNXpNMjIxLjggNTAuNGMuOSAwIDEuNi0uMyAyLjItLjkuNi0uNi45LTEuMyAxLTIuMlYzNC40aC0yMC41djEyLjljMCAuOS4zIDEuNiAxIDIuMi42LjYgMS40LjkgMi4zLjloLjd2LjVoLTExdi0uNWguNmMuOSAwIDEuNi0uMyAyLjItLjkuNi0uNi45LTEuMyAxLTIuMlYxOS41YzAtLjktLjQtMS42LTEtMi4yLS42LS42LTEuNC0uOS0yLjItLjloLS42di0uNWgxMXYuNWgtLjdjLS45IDAtMS42LjMtMi4yLjktLjYuNi0uOSAxLjMtMSAyLjJ2MTMuNEgyMjVWMTkuNWMwLTEuMi0uNi0yLjEtMS42LTIuNy0uNS0uMy0xLS40LTEuNi0uNGgtLjZ2LS41aDEwLjl2LjVoLS43Yy0uOSAwLTEuNi4zLTIuMi45LS42LjYtLjkgMS40LTEgMi4ydjI3LjhjMCAuOS40IDEuNiAxIDIuMi42LjYgMS40LjkgMi4yLjloLjd2LjVoLTEwLjl2LS41aC42ek0yMzguOCA1MC40Yy45IDAgMS42LS4zIDIuMi0uOS42LS42LjktMS40IDEtMi4yVjE5LjVjMC0uOS0uNC0xLjYtMS0yLjItLjYtLjYtMS40LS45LTIuMi0uOWgtLjd2LS41aDExdi41aC0uN2MtLjkgMC0xLjYuMy0yLjIuOS0uNi42LS45IDEuMy0xIDIuMnYyNy43YzAgLjkuMyAxLjYgMSAyLjIuNi42IDEuNC45IDIuMy45aC43di41aC0xMXYtLjVoLjZ6TTI3OS41IDE1LjloMTB2LjVoLS41Yy0uOCAwLTEuNi4zLTIuNC44LS44LjUtMS40IDEuMy0xLjkgMi4zbC0xMiAyNi45Yy0xLjIgMi43LTEuOSA0LjQtMS45IDUuMmgtLjRsLTE0LjEtMzJjLS41LTEuMS0xLjEtMS45LTEuOS0yLjQtLjgtLjUtMS42LS44LTIuNS0uOGgtLjR2LS41aDExLjN2LjVoLS41Yy0uNyAwLTEuMi4yLTEuNi43LS40LjUtLjUuOS0uNSAxLjNzLjEuOC4zIDEuMkwyNzEgNDUuN2wxMC45LTI2LjJjLjEtLjQuMi0uOC4yLTEuMiAwLS40LS4yLS44LS41LTEuMy0uNC0uNC0uOS0uNy0xLjYtLjdoLS41di0uNHpNMzE5LjYgNTAuNGguNHYuNWgtMTEuM3YtLjVoLjVjLjcgMCAxLjItLjIgMS42LS43LjQtLjUuNS0uOS41LTEuM3MtLjEtLjgtLjItMS4ybC0zLjItOC41aC0xMy41bC0zLjMgOC42Yy0uMS40LS4yLjgtLjIgMS4yIDAgLjQuMi44LjUgMS4zcy45LjcgMS42LjdoLjR2LjVoLTEwdi0uNWguNWMuOCAwIDEuNy0uMyAyLjUtLjhzMS41LTEuMyAyLTIuNGwxMS4zLTI2LjljMS4yLTIuNyAxLjgtNC40IDEuOC01LjJoLjVsMTMuNCAzMmMuNSAxIDEuMSAxLjggMS45IDIuNHMxLjQuOCAyLjMuOHpNMjk1IDM3LjNoMTIuM2wtNi0xNi4xLTYuMyAxNi4xek0zMzIuNyAxNy41aC0zLjJ2MjkuOGMwIC45LjMgMS42LjkgMi4yLjYuNiAxLjQuOSAyLjMuOWguNnYuNWgtMTF2LS41aC43Yy45IDAgMS42LS4zIDIuMi0uOS42LS42LjktMS4zIDEtMi4yVjE5LjZjMC0uOS0uNC0xLjYtMS0yLjItLjYtLjYtMS40LS45LTIuMi0uOWgtLjdWMTZoMTMuNWMzIDAgNS40LjggNy4yIDIuNSAxLjcgMS43IDIuNiAzLjkgMi42IDYuNiAwIDIuNy0uOCA1LTIuNSA2LjktMS43IDItMy43IDIuOS02IDIuOS41LjIgMS4xLjcgMS44IDEuNHMxLjMgMS40IDEuOCAyLjFjMi45IDQuMSA0LjcgNi42IDUuNiA3LjYuOS45IDEuNSAxLjYgMS44IDEuOS40LjQuOC43IDEuMiAxIC40LjMuOS42IDEuMy44IDEgLjUgMiAuNyAzLjEuN3YuNUgzNTFjLTEuNCAwLTIuOC0uMy00LS44LTEuMi0uNS0yLjItMS0yLjgtMS42LS42LS41LTEuMi0xLjEtMS42LTEuNi0uNS0uNS0xLjctMi4yLTMuNy01LjItMi0yLjktMy4yLTQuNi0zLjUtNS0uMy0uNC0uNy0uOC0xLTEuMi0xLjEtMS4xLTIuMS0xLjctMy4yLTEuN3YtLjVjLjMgMCAuNi4xIDEgLjFzMSAwIDEuNi0uMWM0LjEtLjEgNi43LTEuOCA3LjgtNS4yLjItLjcuMy0xLjMuMy0xLjl2LTEuMWMtLjEtMi4yLS43LTQtMS44LTUuNC0xLjEtMS40LTIuNi0yLjEtNC41LTIuMmgtMi45ek0zNTUuNCA1MC40Yy45IDAgMS42LS4zIDIuMi0uOS42LS42LjktMS40IDEtMi4yVjE5LjVjMC0uOS0uNC0xLjYtMS0yLjItLjYtLjYtMS40LS45LTIuMi0uOWgtLjd2LS41aDExdi41aC0uN2MtLjkgMC0xLjYuMy0yLjIuOS0uNi42LS45IDEuMy0xIDIuMnYyNy43YzAgLjkuMyAxLjYgMSAyLjIuNi42IDEuNC45IDIuMy45aC43di41aC0xMXYtLjVoLjZ6TTQwMi4zIDUwLjloLTEyLjJ2LS41aC42Yy43IDAgMS4zLS4zIDEuNy0xIC4yLS40LjQtLjcuNC0xcy0uMS0uNy0uMy0xbC03LjctMTIuMS03LjcgMTIuMWMtLjIuMy0uMy43LS4zIDEgMCAuNC4xLjcuMyAxIC40LjcgMSAxIDEuNyAxaC43di41aC0xMS40di0uNWguN2MxIDAgMi0uMyAyLjgtLjguOS0uNiAxLjctMS4zIDIuMy0yLjFsOS42LTE0LjItOC44LTEzLjhjLS42LS44LTEuMy0xLjUtMi4yLTIuMS0uOS0uNi0xLjgtLjktMi44LS45aC0uN1YxNmgxMi4xdi41aC0uNmMtLjcgMC0xLjMuMy0xLjcgMS0uMi40LS40LjctLjQgMSAwIC4zLjEuNy4zIDFsNi45IDEwLjggNi44LTEwLjhjLjItLjMuMy0uNy4zLTEgMC0uNC0uMS0uNy0uMy0xLS40LS43LTEtMS0xLjctMWgtLjZWMTZoMTEuM3YuNWgtLjdjLTEgMC0xLjkuMy0yLjguOS0uOS42LTEuNiAxLjMtMi4yIDIuMWwtOC44IDEyLjkgOS43IDE1LjJjLjggMS4xIDEuOCAyIDMgMi41LjcuMyAxLjMuNCAyIC40aC43di40eiIvPjwvZz48L3N2Zz4=";
}

function deleteBackups( $params )
{
  $pdo        = newPDO();
  $backupPath = createDirectory( 'backup' );

  if ( isset( $params['all'] ) ) {
    createTable( 'backup' );
    $pdo->exec( "DROP TABLE IF EXISTS backup" );
    deleteDirectory( $backupPath );
    createDirectory( 'backup' );
    createTable( 'backup' );
    return true;
  }

  if ( isset( $params['breakpoint'] ) ) {
    $pdo_remove = newPDO();
    $stmt       = $pdo->prepare( "SELECT rowid, * FROM backup WHERE rowid <= :breakpoint ORDER BY rowid DESC" );
    $stmt->bindParam( 'breakpoint', $params['breakpoint'] );
    $stmt->execute();

    while ( $backup = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
      if ( strlen( $backup['filename'] ) ) unlink( $backupPath . DIRECTORY_SEPARATOR . $backup['filename'] );
      $stmt_remove = $pdo_remove->prepare( "DELETE FROM backup WHERE rowid = :rowid" );
      $stmt_remove->bindParam( 'rowid', $backup['rowid'] );
      $stmt_remove->execute();
    }

    return;
  }

  $backups = explode( ',', $params['backups'] );
  foreach ( $backups as $backupId ) {
    $stmt = $pdo->prepare( "SELECT rowid, * FROM backup WHERE rowid = :rowid" );
    $stmt->execute( ['rowid' => $backupId] );

    while ( $backup = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
      if ( strlen( $backup['filename'] ) ) unlink( $backupPath . DIRECTORY_SEPARATOR . $backup['filename'] );
    }

    $stmt = $pdo->prepare( "DELETE FROM backup WHERE rowid = :rowid" );
    $stmt->execute( ['rowid' => $backupId] );
  }

  responseAjax();
}

function deleteCustomFile( $filename )
{
  global $sourcePath;
  $filename     = basename( $filename );
  $includesPath = $sourcePath . DIRECTORY_SEPARATOR . 'includes';
  $file         = $includesPath . DIRECTORY_SEPARATOR . $filename;
  if ( !file_exists( $file ) ) return false;
  unlink( $file );
  if ( !file_exists( $file ) ) return true;
}

function deleteDirectory( $target )
{
  if ( !file_exists( $target ) ) return false;
  $files = array_diff( scandir( $target ), ['.', '..'] );
  foreach ( $files as $file ) {
    ( is_dir( $target . DIRECTORY_SEPARATOR . $file ) ) ? deleteDirectory( $target . DIRECTORY_SEPARATOR . $file ) : unlink( $target . DIRECTORY_SEPARATOR . $file );
  }
  return rmdir( $target );
}

function detectInterface()
{
  /*
  apache
  apache2filter
  apache2handler
  cgi-fcgi
  cli
  cli-server
  embed
  isapi
  fpm-fcgi
  litespeed
  nsapi
  phpdbg
  phttpd
  thttpd
  tux
  webjames
  */
}

function detectLanguage()
{
  if ( !empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
    $browserLanguages = explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
    $browserLanguages = array_map( function ( $a ) {
      return substr( trim( $a ), 0, 2 );
    }, $browserLanguages );
    global $cmsLocales;
    $locales = $cmsLocales;
    unset( $locales['en'] );
    $supportedLanguages = array_intersect( $browserLanguages, array_keys( $locales ) );
    if ( count( $supportedLanguages ) ) return reset( $supportedLanguages );
    if ( !empty( array_intersect( $browserLanguages, ['hy', 'kk', 'lv', 'lt', 'ky', 'ab', 'uz', 'ro'] ) ) ) return 'ru';
  }
  return 'en';
}

function detectPagesDepth()
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $ACMS;

  $stats = array_merge( [
    'iterations'        => 0,
    'linked_pages'      => 0,
    'orphan_pages'      => 0,
    'checked_hostnames' => [],
    'page_start'        => '',
    'pages_original'    => [],
    'pages_update'      => [],
    'pages_checked'     => [],
    'pages_pool'        => [],
    'links'             => 0,
    'pages'             => 0,
    'processed'         => 0,
    'total'             => 0,
    'time'              => 0,
  ], unserialize( $taskStats ) );

  if ( empty( $stats['total'] ) ) {
    sqlExec( "UPDATE structure SET depth = 0" ); // reset depth
    $stats['total'] = sqlGetValue( "SELECT COUNT(1) FROM structure WHERE mimetype='text/html' AND enabled = 1 AND redirect = ''" );
  }
  //$iterations  = 0;
  //$linkedPages = 0;
  //$orphanPages = 0;
  // reset to test again
  //sqlExec( "UPDATE structure SET depth = 0" );
  // get hostnames
  $hostnames = array_filter( array_keys( sqlReindex( sqlGetLines( "SELECT DISTINCT(hostname) as hostname FROM structure " ), 'hostname' ) ) );
  //print_r( $hostnames );
  foreach ( $hostnames as $hostname ) {
    if ( in_array( $hostname, $stats['checked_hostnames'] ) ) continue;
    // get pages
    //unset( $pageStart );
    //$pagesOriginal = [];
    if ( !$stats['pages_original'] ) {
      $pagesSql = sqlReindex( sqlGetLines( "SELECT request_uri as path, depth FROM structure WHERE hostname = :hostname AND mimetype = 'text/html' AND enabled = 1 AND redirect = ''", ['hostname' => $hostname] ), 'path' );
      foreach ( $pagesSql as $key => $page ) $stats['pages_original'][rawurldecode( $key )] = $page['depth'];
    }

    //addWarning( $pagesOriginal ); return;
    // make a working copy
    if ( !$stats['pages_update'] ) $stats['pages_update'] = array_fill_keys( array_keys( $stats['pages_original'] ), 0 );
    if ( !$stats['pages_checked'] ) $stats['pages_checked'] = array_fill_keys( array_keys( $stats['pages_original'] ), null );
    // get the main url /, if not exists = all 0, if redirects, then redirect gets 1 and is a starting point
    // [TODO] improve for longer cycle, separate detectStartPage()
    if ( !$stats['page_start'] ) {
      if ( key_exists( '/', $stats['pages_update'] ) ) {
        $stats['page_start']        = '/';
        $stats['pages_update']['/'] = 1;
      } else {
        $mainPage = getUrlByPath( $hostname, '/' );
        if ( isset( $mainPage['redirect'] ) && key_exists( $mainPage['redirect'], $stats['pages_update'] ) ) $stats['page_start'] = $mainPage['redirect'];
      }
      // no start page detected
      if ( !isset( $stats['page_start'] ) ) {
        sqlExec( "UPDATE structure SET depth = 0 WHERE hostname = :hostname", ['hostname' => $hostname] );
        $stats['checked_hostnames'][] = $hostname;
        continue;
      }
    }
    // start calculation from the startPage
    if ( !$stats['pages_pool'] ) {
      $stats['pages_pool']                       = [];
      $stats['pages_pool'][$stats['page_start']] = 1;
    }

    while ( !empty( $stats['pages_pool'] ) ) {
      asort( $stats['pages_pool'] );
      $currLevel = reset( $stats['pages_pool'] );
      $currPath  = key( $stats['pages_pool'] );
      if ( $stats['pages_update'][$currPath] > $currLevel ) $stats['pages_update'][$currPath] = $currLevel;
      $stats['pages_checked'][$currPath] = 1;
      unset( $stats['pages_pool'][$currPath] );
      $newPages = getPageLinks( $hostname, $currPath );
      //addWarning( $newPages );
      foreach ( $newPages as $newPage ) {
        if ( !key_exists( $newPage, $stats['pages_original'] ) ) continue;
        if ( $stats['pages_checked'][$newPage] ) continue;
        $stats['pages_pool'][$newPage] = $currLevel + 1;
        if ( !$stats['pages_update'][$newPage] || $stats['pages_update'][$newPage] > $stats['pages_pool'][$newPage] ) $stats['pages_update'][$newPage] = $stats['pages_pool'][$newPage];
      }
      $stats['iterations']++;

      if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
        $stats['time']        += microtime( true ) - ACMS_START_TIME;
        $stats['processed']   = $stats['iterations'];
        $stats['pages']       = $stats['iterations'];
        $taskStats            = serialize( $stats );
        $taskIncomplete       = true;
        $taskIncompleteOffset = 0;
        return $stats;
      }
    }
    $stats['linked_pages'] += count( array_filter( $stats['pages_update'] ) );
    $stats['orphan_pages'] += count( $stats['pages_original'] ) - count( array_filter( $stats['pages_update'] ) );
    //addWarning( array_filter( $pagesUpdate ), 2, "Iterations: {$i}, Linked pages: {$linkedPages}, Orphan pages: {$orphanPages}" );
    foreach ( $stats['pages_update'] as $path => $depth ) sqlExec( "UPDATE structure SET depth=:depth WHERE hostname=:hostname AND request_uri=:path", ['depth' => $depth, 'hostname' => $hostname, 'path' => encodePath( $path )] );
    $stats['page_start']     = '';
    $stats['pages_original'] = [];
    $stats['pages_update']   = [];
    $stats['pages_checked']  = [];
    $stats['pages_pool']     = [];
  }
  return ['iterations' => $stats['iterations'], 'linked_pages' => $stats['linked_pages'], 'orphan_pages' => $stats['orphan_pages']];
}

function detectSitemapUrl()
{
  global $LOADER;
  global $uuidSettings;
  return
    ( !empty( $LOADER['ARCHIVARIX_PROTOCOL'] ) &&
    !in_array( $LOADER['ARCHIVARIX_PROTOCOL'], ['any', ''] ) ? $LOADER['ARCHIVARIX_PROTOCOL'] :
      ( !empty( $uuidSettings['https'] ) ? 'https' :
        ( isSecureConnection() ? 'https' : 'http' )
      )
    ) .
    '://' .
    ( !empty( $LOADER['ARCHIVARIX_CUSTOM_DOMAIN'] ) ? $LOADER['ARCHIVARIX_CUSTOM_DOMAIN'] :
      ( $uuidSettings['domain'] ?: $_SERVER['HTTP_HOST'] )
    ) .
    ( !empty( $LOADER['ARCHIVARIX_SITEMAP_PATH'] ) ? $LOADER['ARCHIVARIX_SITEMAP_PATH'] : '/sitemap.xml' );
}

function doSearchReplaceCode( $params, $taskOffset = 0 )
{
  if ( $params['type'] == 'new' ) {
    return [];
  }

  if ( $params['type'] == 'replace_now' ) {
    $params['type']    = 'replace';
    $params['perform'] = 'replace';
  }

  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $sourcePath;
  global $ACMS;
  //$memorySize = getBytesFromHumanSize( ini_get( 'memory_limit' ) );

  //addWarning($taskStats);
  $stats = array_merge( ['pages' => 0, 'result' => ['total_urls' => 0, 'total_matches' => 0, 'limit_reached' => 0], 'total_matches' => 0, 'processed' => 0, 'replaced' => 0, 'removed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  $result = !empty( $stats['result'] ) ? $stats['result'] : ['total_urls' => 0, 'total_matches' => 0, 'limit_reached' => 0, 'strings' => []];

  $mimeTypeSql = "'text/html'";
  if ( !empty( $params['text_files_search'] ) ) {
    $mimeTypeSql = "'text/html', 'text/css', 'application/javascript', 'application/x-javascript', 'text/javascript', 'text/plain', 'application/json', 'application/xml', 'text/xml'";
  }

  $csgFlag = empty( $params['case_sensitive'] ) ? 'i' : '';

  $pdo = newPDO();
  if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdo->query( "SELECT COUNT(1) FROM structure WHERE mimetype IN ({$mimeTypeSql})" )->fetchColumn() );
  $stmt = $pdo->prepare( "SELECT rowid, * FROM structure WHERE mimetype IN ({$mimeTypeSql}) AND rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );

  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['result']      = $result;
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $url['rowid'] - 1;
      return $stats;
    }

    if ( $url['filename'] == '' ) continue;
    $file = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];

    $params['search_conv']  = convertEncoding( $params['search'], $url['charset'], 'utf-8' );
    $params['replace_conv'] = convertEncoding( $params['replace'], $url['charset'], 'utf-8' );

    $params['search_conv']  = preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", $params['search_conv'] );
    $params['replace_conv'] = preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", $params['replace_conv'] );

    if ( $params['regex'] == 0 ) {
      $params['search_conv']  = preg_quote( $params['search_conv'], '~' );
      $params['replace_conv'] = preg_replace( '/\$(\d)/', '\\\$$1', $params['replace_conv'] );
    }

    if ( $params['type'] == 'search' ) {
      preg_match_all( "~{$params['search_conv']}~{$csgFlag}s", preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", file_get_contents( $file ) ), $matches, PREG_OFFSET_CAPTURE );

      if ( is_array( $matches[0] ) && count( $matches[0] ) ) {
        if ( !empty( $params['filter']['text'] ) ) {
          foreach ( $params['filter']['text'] as $filterNum => $filterVal ) {
            $filterCur = [
              'param'          => $params['filter']['param'][$filterNum],
              'operator'       => $params['filter']['operator'][$filterNum],
              'text'           => $params['filter']['text'][$filterNum],
              'regex'          => $params['filter']['regex'][$filterNum],
              'case_sensitive' => $params['filter']['case_sensitive'][$filterNum],
            ];
            if ( !strlen( $filterCur['text'] ) ) continue;
            if ( $filterCur['param'] == 'datetime' && strlen( $filterCur['text'] ) < 4 ) continue;
            if ( $filterCur['param'] == 'filesize' ) $filterCur['text'] = getBytesFromHumanSize( $filterCur['text'] );
            if ( $filterCur['param'] == 'depth' && $filterCur['text'] < 0 ) continue;
            if ( $filterCur['param'] == 'text' ) $filterCur['text'] = convertEncoding( $filterCur['text'], $url['charset'], 'utf-8' );
            if ( !$filterCur['regex'] ) $filterCur['text'] = preg_quote( $filterCur['text'], '~' );
            $csfFlag = $filterCur['case_sensitive'] ? '' : 'i';
            switch ( $filterCur['param'] ) {
              case 'code' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", file_get_contents( $file ) ), $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'url' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", rawurldecode( $url['request_uri'] ), $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'mime' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['mimetype'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'charset' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['charset'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'redirect' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['redirect'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'datetime' :
                if ( $filterCur['operator'] == 'from' && $url['filetime'] < str_pad( $filterCur['text'], 14, 0 ) ) continue 3;
                if ( $filterCur['operator'] == 'to' && $url['filetime'] > str_pad( $filterCur['text'], 14, 9 ) ) continue 3;
                break;
              case 'filesize' :
                if ( $filterCur['operator'] == 'from' && $url['filesize'] < $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'to' && $url['filesize'] > $filterCur['text'] ) continue 3;
                break;
              case 'hostname' :
              case 'hostname' :
                $filterCur['text'] = convertIdnToAscii( $filterCur['text'] );
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['hostname'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'depth' :
                if ( $url['mimetype'] != 'text/html' ) continue 3;
                if ( $filterCur['operator'] == 'gt' && $url['depth'] <= $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'gte' && $url['depth'] < $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'lt' && $url['depth'] >= $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'lte' && $url['depth'] > $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'eq' && $url['depth'] != $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'neq' && $url['depth'] == $filterCur['text'] ) continue 3;
                break;
            }
          }
        }

        if ( isset( $params['perform'] ) && $params['perform'] == 'remove' ) {
          removeUrl( $url['rowid'] );
          $stats['removed']++;
          if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
            $stats['time']        += microtime( true ) - ACMS_START_TIME;
            $taskStats            = serialize( $stats );
            $taskIncomplete       = true;
            $taskIncompleteOffset = $url['rowid'];
            return $stats;
          }
        }
      }

      $pageCounted = false;
      foreach ( $matches as $n => $match ) {
        if ( !count( $match ) ) {
          continue;
        }

        unset( $results );
        $results = [];
        for ( $n = 0; $n < count( $match ); $n++ ) {
          $stats['total_matches']++;
          if ( $stats['total_matches'] > $ACMS['ACMS_MATCHES_LIMIT'] ) {
            if ( !$result['limit_reached'] ) $result['limit_reached'] = 1;
            continue;
          }
          $results[] = [
            'result'   => convertEncoding( $match[$n][0], 'utf-8', $url['charset'] ),
            'position' => $match[$n][1],
          ];
        }

        // && ( memory_get_usage() / $memorySize ) < 0.9
        if ( $stats['total_matches'] <= $ACMS['ACMS_MATCHES_LIMIT'] ) $result[] = [
          'type'        => 'search',
          'rowid'       => $url['rowid'],
          'hostname'    => $url['hostname'],
          'request_uri' => $url['request_uri'],
          'filetime'    => $url['filetime'],
          'results'     => !empty( $results ) ? $results : [],
        ];

        if ( !$pageCounted ) $stats['pages']++;
        $pageCounted = true;

        $result['total_matches'] = $stats['total_matches'];
        $result['total_urls']    = $stats['pages'];

        if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
          $stats['time']        += microtime( true ) - ACMS_START_TIME;
          $stats['result']      = $result;
          $taskStats            = serialize( $stats );
          $taskIncomplete       = true;
          $taskIncompleteOffset = $url['rowid'];
          return $stats;
        }
      }
    }

    if ( $params['type'] == 'replace' || $params['type'] == 'list' ) {
      preg_match_all( "~{$params['search_conv']}~{$csgFlag}s", preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", file_get_contents( $file ) ), $found, PREG_OFFSET_CAPTURE );
      $matches = preg_filter( "~{$params['search_conv']}~{$csgFlag}s", "{$params['replace_conv']}", preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", file_get_contents( $file ) ), -1, $count );

      if ( !$count ) {
        continue;
      }

      if ( count( $found[0] ) ) {
        if ( !empty( $params['filter']['text'] ) ) {
          foreach ( $params['filter']['text'] as $filterNum => $filterVal ) {
            $filterCur = [
              'param'          => $params['filter']['param'][$filterNum],
              'operator'       => $params['filter']['operator'][$filterNum],
              'text'           => $params['filter']['text'][$filterNum],
              'regex'          => $params['filter']['regex'][$filterNum],
              'case_sensitive' => $params['filter']['case_sensitive'][$filterNum],
            ];
            if ( !strlen( $filterCur['text'] ) ) continue;
            if ( $filterCur['param'] == 'datetime' && strlen( $filterCur['text'] ) < 4 ) continue;
            if ( $filterCur['param'] == 'filesize' ) $filterCur['text'] = getBytesFromHumanSize( $filterCur['text'] );
            if ( $filterCur['param'] == 'depth' && $filterCur['text'] < 0 ) continue;
            if ( $filterCur['param'] == 'text' ) $filterCur['text'] = convertEncoding( $filterCur['text'], $url['charset'], 'utf-8' );
            if ( !$filterCur['regex'] ) $filterCur['text'] = preg_quote( $filterCur['text'], '~' );
            $csfFlag = $filterCur['case_sensitive'] ? '' : 'i';
            switch ( $filterCur['param'] ) {
              case 'code' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", file_get_contents( $file ) ), $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'url' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", rawurldecode( $url['request_uri'] ), $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'mime' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['mimetype'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'charset' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['charset'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'redirect' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['redirect'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'datetime' :
                if ( $filterCur['operator'] == 'from' && $url['filetime'] < str_pad( $filterCur['text'], 14, 0 ) ) continue 3;
                if ( $filterCur['operator'] == 'to' && $url['filetime'] > str_pad( $filterCur['text'], 14, 9 ) ) continue 3;
                break;
              case 'filesize' :
                if ( $filterCur['operator'] == 'from' && $url['filesize'] < $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'to' && $url['filesize'] > $filterCur['text'] ) continue 3;
                break;
              case 'hostname' :
                $filterCur['text'] = convertIdnToAscii( $filterCur['text'] );
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['hostname'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'depth' :
                if ( $url['mimetype'] != 'text/html' ) continue 3;
                if ( $filterCur['operator'] == 'gt' && $url['depth'] <= $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'gte' && $url['depth'] < $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'lt' && $url['depth'] >= $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'lte' && $url['depth'] > $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'eq' && $url['depth'] != $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'neq' && $url['depth'] == $filterCur['text'] ) continue 3;
                break;
            }
          }
        }
      }

      unset( $results );
      $results = [];
      for ( $n = 0; $n < $count; $n++ ) {
        $stats['total_matches']++;
        if ( $stats['total_matches'] > $ACMS['ACMS_MATCHES_LIMIT'] && $params['type'] != 'list' ) {
          if ( !$result['limit_reached'] ) $result['limit_reached'] = 1;
          continue;
        }
        if ( $params['type'] == 'list' ) {
          $tmp_result = convertEncoding( preg_replace( "~{$params['search_conv']}~{$csgFlag}s", "{$params['replace_conv']}", $found[0][$n][0] ), 'utf-8', $url['charset'] );
          if ( !isset( $result['strings'] ) ) $result['strings'] = [];
          if ( !in_array( $tmp_result, $result['strings'] ) ) $result['strings'][] = $tmp_result;
        } else {
          $results[] = [
            'original' => convertEncoding( $found[0][$n][0], 'utf-8', $url['charset'] ),
            'position' => $found[0][$n][1],
            'result'   => convertEncoding( preg_replace( "~{$params['search_conv']}~{$csgFlag}s", "{$params['replace_conv']}", $found[0][$n][0] ), 'utf-8', $url['charset'] ),
          ];
        }
      }

      if ( $stats['total_matches'] <= $ACMS['ACMS_MATCHES_LIMIT'] && $params['type'] != 'list' ) $result[] = [
        'type'        => 'replace',
        'rowid'       => $url['rowid'],
        'hostname'    => $url['hostname'],
        'request_uri' => $url['request_uri'],
        'filetime'    => $url['filetime'],
        'count'       => $count,
        'results'     => $results,
      ];


      if ( isset( $params['perform'] ) && $params['perform'] == 'replace' ) {
        backupFile( $url['rowid'], 'replace' );
        file_put_contents( $file, $matches );
        updateFilesize( $url['rowid'], filesize( $file ) );
        $stats['replaced']++;
      }

      $stats['pages']++;
      $stats['processed']++;

      $result['total_matches'] = $stats['total_matches'];
      $result['total_urls']    = $stats['pages'];

      if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
        $stats['time']        += microtime( true ) - ACMS_START_TIME;
        $stats['result']      = $result;
        $taskStats            = serialize( $stats );
        $taskIncomplete       = true;
        $taskIncompleteOffset = $url['rowid'];
        return $stats;
      }
    }
  }

  if ( $stats['replaced'] && isset( $params['perform'] ) && $params['perform'] == 'replace' ) createBackupBreakpoint( L( 'CODE Replacements' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['replaced'], 0 ) ) );
  if ( $stats['removed'] && isset( $params['perform'] ) && $params['perform'] == 'remove' ) createBackupBreakpoint( L( 'Remove all found URLs' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['removed'], 0 ) ) );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return $result;
}

function doSearchReplaceUrls( $params, $taskOffset = 0 )
{
  if ( $params['type'] == 'new' ) {
    return [];
  }

  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $sourcePath;
  global $ACMS;

  $stats = array_merge( ['pages' => 0, 'result' => ['total_urls' => 0, 'total_matches' => 0, 'limit_reached' => 0], 'total_matches' => 0, 'processed' => 0, 'replaced' => 0, 'removed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  $result = !empty( $stats['result'] ) ? $stats['result'] : ['total_urls' => 0, 'total_matches' => 0, 'limit_reached' => 0];

  if ( $params['regex'] == 0 ) {
    $params['search']  = preg_quote( $params['search'], '~' );
    $params['replace'] = preg_replace( '/\$(\d)/', '\\\$$1', $params['replace'] );
  }

  $csgFlag = empty( $params['case_sensitive'] ) ? 'i' : '';

  $pdo = newPDO();
  if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdo->query( "SELECT COUNT(1) FROM structure" )->fetchColumn() );
  $stmt = $pdo->prepare( "SELECT rowid, * FROM structure WHERE rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );

  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['result']      = $result;
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $url['rowid'] - 1;
      return $stats;
    }
    $taskIncompleteOffset = $url['rowid'];

    if ( $params['type'] == 'search' ) {
      preg_match_all( "~{$params['search']}~{$csgFlag}s", rawurldecode( $url['request_uri'] ), $matches, PREG_OFFSET_CAPTURE );

      if ( is_array( $matches[0] ) && count( $matches[0] ) ) {
        if ( !empty( $params['filter']['text'] ) ) {
          foreach ( $params['filter']['text'] as $filterNum => $filterVal ) {
            $filterCur = [
              'param'          => $params['filter']['param'][$filterNum],
              'operator'       => $params['filter']['operator'][$filterNum],
              'text'           => $params['filter']['text'][$filterNum],
              'regex'          => $params['filter']['regex'][$filterNum],
              'case_sensitive' => $params['filter']['case_sensitive'][$filterNum],
            ];
            if ( !strlen( $filterCur['text'] ) ) continue;
            if ( $filterCur['param'] == 'datetime' && strlen( $filterCur['text'] ) < 4 ) continue;
            if ( $filterCur['param'] == 'filesize' ) $filterCur['text'] = getBytesFromHumanSize( $filterCur['text'] );
            if ( $filterCur['param'] == 'depth' && $filterCur['text'] < 0 ) continue;
            if ( $filterCur['param'] == 'text' ) $filterCur['text'] = convertEncoding( $filterCur['text'], $url['charset'], 'utf-8' );
            if ( !$filterCur['regex'] ) $filterCur['text'] = preg_quote( $filterCur['text'], '~' );
            $csfFlag = $filterCur['case_sensitive'] ? '' : 'i';
            switch ( $filterCur['param'] ) {
              case 'code' :
                if ( ( $url['filename'] == '' || $url['redirect'] != '' ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( ( $url['filename'] == '' || $url['redirect'] != '' ) && $filterCur['operator'] == 'contains-not' ) break;
                $file = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", file_get_contents( $file ) ), $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'url' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", rawurldecode( $url['request_uri'] ), $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'mime' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['mimetype'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'charset' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['charset'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'redirect' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['redirect'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'datetime' :
                if ( $filterCur['operator'] == 'from' && $url['filetime'] < str_pad( $filterCur['text'], 14, 0 ) ) continue 3;
                if ( $filterCur['operator'] == 'to' && $url['filetime'] > str_pad( $filterCur['text'], 14, 0 ) ) continue 3;
                break;
              case 'filesize' :
                if ( $filterCur['operator'] == 'from' && $url['filesize'] < $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'to' && $url['filesize'] > $filterCur['text'] ) continue 3;
                break;
              case 'hostname' :
                $filterCur['text'] = convertIdnToAscii( $filterCur['text'] );
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['hostname'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'depth' :
                if ( $url['mimetype'] != 'text/html' ) continue 3;
                if ( $filterCur['operator'] == 'gt' && $url['depth'] <= $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'gte' && $url['depth'] < $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'lt' && $url['depth'] >= $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'lte' && $url['depth'] > $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'eq' && $url['depth'] != $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'neq' && $url['depth'] == $filterCur['text'] ) continue 3;
                break;
            }
          }
        }

        if ( isset( $params['perform'] ) && $params['perform'] == 'remove' ) {
          removeUrl( $url['rowid'] );
          $stats['removed']++;
          if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
            $stats['time']        += microtime( true ) - ACMS_START_TIME;
            $taskStats            = serialize( $stats );
            $taskIncomplete       = true;
            $taskIncompleteOffset = $url['rowid'];
            return $stats;
          }
        }
      }

      foreach ( $matches as $n => $match ) {
        if ( !count( $match ) ) {
          continue;
        }

        unset( $results );
        $results = [];
        for ( $n = 0; $n < count( $match ); $n++ ) {
          $stats['total_matches']++;
          if ( $stats['total_matches'] > $ACMS['ACMS_MATCHES_LIMIT'] ) {
            if ( !$result['limit_reached'] ) $result['limit_reached'] = 1;
            continue;
          }
          $results[] = [
            'result'   => $match[$n][0],
            'position' => $match[$n][1],
          ];
        }

        if ( $stats['total_matches'] <= $ACMS['ACMS_MATCHES_LIMIT'] ) $result[] = [
          'type'        => 'search',
          'rowid'       => $url['rowid'],
          'hostname'    => $url['hostname'],
          'request_uri' => $url['request_uri'],
          'filetime'    => $url['filetime'],
          'results'     => !empty( $results ) ? $results : [],
        ];

        $stats['pages']++;

        $result['total_matches'] = $stats['total_matches'];
        $result['total_urls']    = $stats['pages'];

        if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
          $stats['result']      = $result;
          $stats['time']        += microtime( true ) - ACMS_START_TIME;
          $taskStats            = serialize( $stats );
          $taskIncomplete       = true;
          $taskIncompleteOffset = $url['rowid'];
          return $stats;
        }
      }
    }

    if ( $params['type'] == 'replace' ) {
      preg_match_all( "~{$params['search']}~{$csgFlag}s", rawurldecode( $url['request_uri'] ), $found, PREG_OFFSET_CAPTURE );
      $matches = preg_filter( "~{$params['search']}~{$csgFlag}s", "{$params['replace']}", rawurldecode( $url['request_uri'] ), -1, $count );

      if ( count( $found[0] ) ) {
        if ( !empty( $params['filter']['text'] ) ) {
          foreach ( $params['filter']['text'] as $filterNum => $filterVal ) {
            $filterCur = [
              'param'          => $params['filter']['param'][$filterNum],
              'operator'       => $params['filter']['operator'][$filterNum],
              'text'           => $params['filter']['text'][$filterNum],
              'regex'          => $params['filter']['regex'][$filterNum],
              'case_sensitive' => $params['filter']['case_sensitive'][$filterNum],
            ];
            if ( !strlen( $filterCur['text'] ) ) continue;
            if ( $filterCur['param'] == 'datetime' && strlen( $filterCur['text'] ) < 4 ) continue;
            if ( $filterCur['param'] == 'filesize' ) $filterCur['text'] = getBytesFromHumanSize( $filterCur['text'] );
            if ( $filterCur['param'] == 'depth' && $filterCur['text'] < 0 ) continue;
            if ( $filterCur['param'] == 'text' ) $filterCur['text'] = convertEncoding( $filterCur['text'], $url['charset'], 'utf-8' );
            if ( !$filterCur['regex'] ) $filterCur['text'] = preg_quote( $filterCur['text'], '~' );
            $csfFlag = $filterCur['case_sensitive'] ? '' : 'i';
            switch ( $filterCur['param'] ) {
              case 'code' :
                if ( ( $url['filename'] == '' || $url['redirect'] != '' ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( ( $url['filename'] == '' || $url['redirect'] != '' ) && $filterCur['operator'] == 'contains-not' ) break;
                $file = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", file_get_contents( $file ) ), $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'url' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", rawurldecode( $url['request_uri'] ), $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'mime' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['mimetype'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'charset' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['charset'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'redirect' :
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['redirect'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'datetime' :
                if ( $filterCur['operator'] == 'from' && $url['filetime'] < str_pad( $filterCur['text'], 14, 0 ) ) continue 3;
                if ( $filterCur['operator'] == 'to' && $url['filetime'] > str_pad( $filterCur['text'], 14, 0 ) ) continue 3;
                break;
              case 'filesize' :
                if ( $filterCur['operator'] == 'from' && $url['filesize'] < $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'to' && $url['filesize'] > $filterCur['text'] ) continue 3;
                break;
              case 'hostname' :
                $filterCur['text'] = convertIdnToAscii( $filterCur['text'] );
                preg_match_all( "~{$filterCur['text']}~{$csfFlag}s", $url['hostname'], $advmatches, PREG_OFFSET_CAPTURE );
                if ( !count( $advmatches[0] ) && $filterCur['operator'] == 'contains' ) continue 3;
                if ( count( $advmatches[0] ) && $filterCur['operator'] == 'contains-not' ) continue 3;
                break;
              case 'depth' :
                if ( $url['mimetype'] != 'text/html' ) continue 3;
                if ( $filterCur['operator'] == 'gt' && $url['depth'] <= $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'gte' && $url['depth'] < $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'lt' && $url['depth'] >= $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'lte' && $url['depth'] > $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'eq' && $url['depth'] != $filterCur['text'] ) continue 3;
                if ( $filterCur['operator'] == 'neq' && $url['depth'] == $filterCur['text'] ) continue 3;
                break;
            }
          }
        }
      }

      if ( !$count ) {
        continue;
      }

      unset( $results );
      $results = [];
      for ( $n = 0; $n < $count; $n++ ) {
        $stats['total_matches']++;
        if ( $stats['total_matches'] > $ACMS['ACMS_MATCHES_LIMIT'] ) {
          if ( !$result['limit_reached'] ) $result['limit_reached'] = 1;
          continue;
        }
        $results[] = [
          'original' => $found[0][$n][0],
          'position' => $found[0][$n][1],
          'result'   => preg_replace( "~{$params['search']}~{$csgFlag}s", "{$params['replace']}", $found[0][$n][0] ),
        ];
      }

      $request_uri_new         = encodePath( preg_replace( "~{$params['search']}~{$csgFlag}s", "{$params['replace']}", rawurldecode( $url['request_uri'] ) ) );
      $request_uri_new_decoded = rawurldecode( $request_uri_new );
      $request_uri_new_valid   = substr( $request_uri_new, 0, 1 ) === '/' && filter_var( 'http://domain' . $request_uri_new, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED );

      if ( $stats['total_matches'] <= $ACMS['ACMS_MATCHES_LIMIT'] ) $result[] = [
        'type'        => 'replace',
        'rowid'       => $url['rowid'],
        'hostname'    => $url['hostname'],
        'request_uri' => $url['request_uri'],
        'filetime'    => $url['filetime'],
        'replace_uri' => encodePath( preg_replace( "~{$params['search']}~{$csgFlag}s", "{$params['replace']}", rawurldecode( $url['request_uri'] ) ) ),
        'valid_uri'   => $request_uri_new_valid,
        'count'       => $count,
        'results'     => $results,
      ];

      if ( isset( $params['perform'] ) && $params['perform'] == 'replace' && $request_uri_new_valid ) {
        $url_existing = getUrlByPath( $url['hostname'], $request_uri_new );

        // replace metadata
        $metaDataReplace = 0;
        if ( !empty( $params['metadata'] ) ) {
          foreach ( $params['metadata'] as $metaDataKey => $metaDataVal ) {
            if ( !strlen( $metaDataVal ) ) continue;
            $metaDataReplace = 1;
            switch ( $metaDataKey ) :
              case 'mimetype' :
                $url['mimetype'] = $metaDataVal;
                break;
              case 'charset' :
                $url['charset'] = $metaDataVal;
                break;
              case 'redirect' :
                $url['redirect'] = $metaDataVal;
                break;
              case 'filetime':
                $url['filetime'] = $metaDataVal;
                break;
              case 'hostname':
                $metaDataVal = convertIdnToAscii( $metaDataVal );
                if ( filter_var( "http://{$metaDataVal}/", FILTER_VALIDATE_URL ) !== false ) $url['hostname'] = $metaDataVal;
                break;
            endswitch;
          }
        }
        // simple rename
        if ( !$url_existing && $url ) {
          $url['original_filename'] = $url['filename'];
          $url['urlID']             = $url['rowid'];
          $url['url']               = $url['protocol'] . '://' . $url['hostname'] . $request_uri_new;
          $url['request_uri']       = $request_uri_new_decoded;
          updateUrlSettings( $url );
        } else {
          $url_existing = getUrl( $url_existing['rowid'] );
          if ( $url_existing && $url && !empty( $params['replaceUrl'] ) && $url_existing['rowid'] != $url['rowid'] ) {
            if ( $url_existing['filetime'] < $url['filetime'] ) {
              removeUrl( $url_existing['rowid'] );
              $url['original_filename'] = $url['filename'];
              $url['urlID']             = $url['rowid'];
              $url['url']               = $url['protocol'] . '://' . $url['hostname'] . $request_uri_new;
              $url['request_uri']       = $request_uri_new_decoded;
              updateUrlSettings( $url );
            } else {
              removeUrl( $url['rowid'] );
            }
          } elseif ( $metaDataReplace ) {
            //addWarning( "$metaDataKey => $metaDataVal" );
            $url['urlID'] = $url['rowid'];
            updateUrlSettings( $url );
          }
        }
      }
      $stats['replaced']++;

      $result['total_matches'] = $stats['total_matches'];
      $result['total_urls']    = $stats['replaced'];
    }
    if ( isset( $params['perform'] ) && $params['perform'] == 'replace' ) {
      $stats['pages']++;
      if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
        $stats['result']      = $result;
        $stats['time']        += microtime( true ) - ACMS_START_TIME;
        $taskStats            = serialize( $stats );
        $taskIncomplete       = true;
        $taskIncompleteOffset = $url['rowid'];
        return $stats;
      }
    }
    $stats['processed']++;
  }

  if ( $stats['replaced'] && isset( $params['perform'] ) && $params['perform'] == 'replace' ) createBackupBreakpoint( L( 'Replaces in URLs' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['replaced'], 0 ) ) );
  if ( $stats['pages'] && isset( $params['perform'] ) && $params['perform'] == 'remove' ) createBackupBreakpoint( L( 'Remove all found URLs' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['pages'], 0 ) ) );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return $result;
}

function downloadFile( $url, $dest, $taskOffset = 0, $userAgent = null )
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $ACMS;

  $stats = array_merge( ['size' => 0, 'pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  $options = [
    CURLOPT_FILE           => is_resource( $dest ) ? $dest : ( $taskOffset ? fopen( $dest, 'a+' ) : fopen( $dest, 'w+' ) ),
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_URL            => $url,
    CURLOPT_FAILONERROR    => true,
    CURLOPT_RESUME_FROM    => $taskOffset ?: 0,
    CURLOPT_TIMEOUT        => $ACMS['ACMS_TIMEOUT'] ? ( $ACMS['ACMS_TIMEOUT'] - 1 ) : 0,
    CURLOPT_USERAGENT      => $userAgent ?: "Archivarix-CMS/" . ACMS_VERSION . " (+https://archivarix.com/en/cms/)",
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false,
  ];

  $ch = curl_init();
  curl_setopt_array( $ch, $options );
  $return = curl_exec( $ch );

  if ( $return === false ) {
    // [TODO] catch other frequent errors
    if ( curl_errno( $ch ) == 28 && $ACMS['ACMS_TIMEOUT'] ) {
      $stats['size'] += curl_getinfo( $ch )['size_download'];
      $stats['pages']++;
      $stats['processed']   = $stats['size'];
      $stats['total']       = $stats['size'] + curl_getinfo( $ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $stats['size'];
      $taskStats            = serialize( $stats );
      return false;
    }
    addWarning( sprintf( L( "Could not download %s. Error code is %d." ), basename( parse_url( $url, PHP_URL_PATH ) ), curl_errno( $ch ) ), 4, L( 'An import tool' ) );
    return false;
  } else {
    //return true;
    return curl_getinfo( $ch );
  }
}

function downloadFromSerial( $uuid, $taskOffset = 0 )
{
  $uuid = strtoupper( trim( preg_replace( '~[^0-9a-z]~i', '', $uuid ) ) );
  if ( !preg_match( '~[0-9A-Z]{16}~', $uuid ) ) return false;
  $importsPath = createDirectory( 'imports' );
  $filename    = "{$uuid}.zip";
  downloadFile(
    'https://download.archivarix.cloud/restores/' . $uuid[0] . '/' . $uuid[1] . '/' . $uuid . '.zip' . '?uid=' . sha1( __DIR__ ),
    $importsPath . DIRECTORY_SEPARATOR . $filename,
    $taskOffset
  );
  return $uuid;
}

function dropTable( $tableName, $pdo = null )
{
  if ( $pdo == null ) $pdo = newPDO();

  switch ( $tableName ) :
    case 'backup' :
      $pdo->exec( "DROP TABLE IF EXISTS backup" );
      break;
    case 'templates' :
      $pdo->exec( "DROP TABLE IF EXISTS templates" );
      break;
    case 'meta' :
      $pdo->exec( "DROP TABLE IF EXISTS meta" );
      break;
    case 'structure' :
      $pdo->exec( "DROP TABLE IF EXISTS structure" );
      break;
    case 'settings' :
      $pdo->exec( "DROP TABLE IF EXISTS settings" );
      break;
    case 'missing' :
      $pdo->exec( "DROP TABLE IF EXISTS missing" );
      break;
  endswitch;
}

function encodePath( $pathDecoded )
{
  $safeFrom = ['%21', '%23', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%2F', '%3A', '%3B', '%3D', '%3F', '%40', '%5B', '%5D', '%7E'];
  $safeTo   = ['!', '#', '$', '&', '\'', '(', ')', '*', '+', ',', '/', ':', ';', '=', '?', '@', '[', ']', '~',];
  return str_ireplace( $safeFrom, $safeTo, rawurlencode( rawurldecode( $pathDecoded ) ) );
}

function encodeUrl( $url )
{
  $parts = parse_url( $url );
  return
    ( !empty( $parts['scheme'] ) ? $parts['scheme'] . '://' : '' ) .
    ( !empty( $parts['host'] ) ? $parts['host'] : '' ) .
    encodePath( ( !empty( $parts['path'] ) ? $parts['path'] : '' ) . ( !empty( $parts['query'] ) ? '?' . $parts['query'] : '' ) );
}

function escapeArrayValues( &$value )
{
  $value = htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

function escapeFilename( $string )
{
  $from = ['\\', '/', '*', '?', '"', '<', '>', '|'];
  $to   = ['-', '-', '-', '-', '-', '-', '-', '-'];

  return str_ireplace( $from, $to, $string );
}

function fixHtmlScriptTags( $html )
{
  global $fBucket;
  $fBucket['fixHtmlScriptTags'] = ['simple' => [], 'complete' => []];
  $pattern                      = "/<script([^']*?)<\/script>/";
  preg_match_all( $pattern, $html, $matches );
  $matches = array_unique( $matches[0] );

  foreach ( $matches as $match ) {
    $id           = uniqid( 'script_' );
    $uniqueScript = "<script id=\"$id\"></script>";
    $simple[]     = $uniqueScript;
    $complete[]   = $match;
  }

  $html = str_replace( $complete, $simple, $html );

  $fBucket['fixHtmlScriptTags'] = [
    'simple'   => $simple,
    'complete' => $complete,
  ];

  return $html;
}

function fixHtmlScriptTagsBack( $html )
{
  global $fBucket;
  if ( empty( $fBucket['fixHtmlScriptTags'] ) ) return $html;
  return str_replace( $fBucket['fixHtmlScriptTags']['simple'], $fBucket['fixHtmlScriptTags']['complete'], $html );
}

function flattenArray( $array, $prefix = '' )
{
  $result = [];
  foreach ( $array as $key => $value ) {
    if ( is_array( $value ) ) {
      $result = $result + flattenArray( $value, $prefix . $key . ',' );
    } else {
      $result[$prefix . $key] = $value;
    }
  }
  return $result;
}

function exportPerform( $exportZIP, $exportSettings, $taskOffset = 0 )
{
  global $sourcePath;
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $ACMS;

  $stats = array_merge( ['pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  !empty( $stats['secret'] ) || $stats['secret'] = getRandomString( 8 );

  if ( !preg_match( '~^[-.\w\d]+.zip$~', $exportZIP ) ) {
    $exportZIP = 'export.zip';
  }

  if ( empty( $exportSettings['hostnames'] ) ) $exportSettings['hostnames'] = [];

  if ( !$taskIncomplete || empty( $stats['tmpDB'] ) ) {
    $exportTmpDB    = tempnam( getTempDirectory(), 'archvarix.' );
    $stats['tmpDB'] = $exportTmpDB;
    copy( $sourcePath . DIRECTORY_SEPARATOR . 'structure.db', $exportTmpDB );
    $pdoTmp = new PDO( "sqlite:{$exportTmpDB}" );
    $pdoTmp->exec( "DROP TABLE IF EXISTS backup" );
    $pdoTmp->exec( "DROP TABLE IF EXISTS meta" );
    $pdoTmp->exec( "DROP TABLE IF EXISTS missing" );
    if ( empty( $exportSettings['templates'] ) ) $pdoTmp->exec( "DROP TABLE IF EXISTS templates" );

    foreach ( $exportSettings['hostnames'] as $exportHostname ) {
      $sqlHostnamesArr[] = $pdoTmp->quote( $exportHostname, PDO::PARAM_STR );
    }
    $sqlHostnames = implode( ', ', $sqlHostnamesArr );
    $pdoTmp->exec( "DELETE FROM structure WHERE hostname NOT IN ({$sqlHostnames})" );

    $pdoTmp->exec( "VACUUM" );
    $pdoTmp = null;
    $pdoTmp = new PDO( "sqlite:{$exportTmpDB}" );
  } else {
    $pdoTmp      = new PDO( "sqlite:{$stats['tmpDB']}" );
    $exportTmpDB = $stats['tmpDB'];
  }

  if ( empty( $stats['tmpZIP'] ) ) {
    $exportTmpZIP    = tempnam( getTempDirectory(), 'archvarix.' );
    $stats['tmpZIP'] = $exportTmpZIP;
  } else {
    $exportTmpZIP = $stats['tmpZIP'];
  }

  $zip = new ZipArchive;

  if ( filesize( $exportTmpZIP ) == 0 ) {
    $zip->open( $exportTmpZIP, ZipArchive::CREATE );
    $zip->addEmptyDir( ".content.{$stats['secret']}" );
    $zip->addFile( $exportTmpDB, ".content.{$stats['secret']}/structure.db" );
    if ( !empty( $exportSettings['acms_settings'] ) && file_exists( $sourcePath . DIRECTORY_SEPARATOR . '.acms.settings.json' ) ) {
      $zip->addFile( $sourcePath . DIRECTORY_SEPARATOR . '.acms.settings.json', ".content.{$stats['secret']}/.acms.settings.json" );
    }
    if ( !empty( $exportSettings['loader_settings'] ) && file_exists( $sourcePath . DIRECTORY_SEPARATOR . '.loader.settings.json' ) ) {
      $zip->addFile( $sourcePath . DIRECTORY_SEPARATOR . '.loader.settings.json', ".content.{$stats['secret']}/.loader.settings.json" );
    }
    if ( !empty( $exportSettings['custom_files'] ) && file_exists( $sourcePath . DIRECTORY_SEPARATOR . 'includes' ) ) {
      $zip->addEmptyDir( ".content.{$stats['secret']}/includes/" );
      foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $sourcePath . DIRECTORY_SEPARATOR . 'includes', FilesystemIterator::SKIP_DOTS ) ) as $obj ) {
        $includesPathLen     = mb_strlen( $sourcePath . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR );
        $customFilePathRel   = mb_substr( $obj->getPathname(), $includesPathLen );
        $customFileSubdirRel = pathinfo( $customFilePathRel, PATHINFO_DIRNAME );
        if ( $customFileSubdirRel != '.' ) $zip->addEmptyDir( ".content.{$stats['secret']}/includes/{$customFileSubdirRel}" );
        $zip->addFile( $obj->getPathname(), ".content.{$stats['secret']}/includes/{$customFilePathRel}" );
      }
    }
    if ( !empty( $exportSettings['templates'] ) && file_exists( $sourcePath . DIRECTORY_SEPARATOR . 'templates' ) ) {
      $zip->addEmptyDir( ".content.{$stats['secret']}/templates/" );
      $stmt = $pdoTmp->query( "SELECT * FROM templates" );
      $stmt->execute();
      while ( $template = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
        if ( is_file( $sourcePath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template['name'] . '.html' ) ) {
          $zip->addFile(
            $sourcePath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template['name'] . '.html',
            ".content.{$stats['secret']}/templates/{$template['name']}.html"
          );
        }
      }
    }
  } else {
    $zip->open( $exportTmpZIP, ZipArchive::OVERWRITE );
  }

  if ( empty( $stats['total'] ) ) {
    $stats['total'] = intval( $pdoTmp->query( "SELECT COUNT(1) FROM structure" )->fetchColumn() );
  }

  $stmt = $pdoTmp->prepare( "SELECT rowid, * FROM structure WHERE rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );

  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $stats['processed']++;
    $stats['pages']++;
    $localFile = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
    if ( !is_file( $localFile ) ) continue;
    $exportFile = ".content.{$stats['secret']}/" . $url['folder'] . '/' . $url['filename'];
    $zip->addFile( $localFile, $exportFile );
    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $zip->close();
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $url['rowid'];
      return $stats;
    }
  }

  $pdoTmp->exec( "PRAGMA journal_mode=OFF" );
  $pdoTmp = null;
  $zip->close();
  unlink( $exportTmpDB );
  $importsPath = createDirectory( 'imports' );
  rename( $exportTmpZIP, $importsPath . DIRECTORY_SEPARATOR . $exportZIP );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return true;
}

function funConvertWebsite( $options )
{
  @ini_set( 'max_execution_time', 0 );
  // [TODO] just for fun, use at your own risk, not ready at all.
  global $uuidSettings;
  global $sourcePath;
  $exports = $sourcePath . DIRECTORY_SEPARATOR . 'exports';
  $output  = $exports . DIRECTORY_SEPARATOR . $uuidSettings['domain'];
  $pdo     = newPDO();
  $stmt    = $pdo->query( "SELECT rowid, * FROM structure ORDER BY filetime" );

  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $parsed   = parse_url( $url['url'] );
    $pathinfo = pathinfo( rawurldecode( $parsed['path'] ) );
    if ( !file_exists( $output . DIRECTORY_SEPARATOR . $url['hostname'] . DIRECTORY_SEPARATOR . $pathinfo['dirname'] ) ) {
      mkdir( $output . DIRECTORY_SEPARATOR . $url['hostname'] . DIRECTORY_SEPARATOR . $pathinfo['dirname'], 0777, true );
    }
    if ( isset( $pathinfo['extension'] ) ) $mimetype = getMimeByExtension( $pathinfo['extension'] );
    else $mimetype = [];
    if (
      $url['mimetype'] == 'text/html'
      && ( strlen( $pathinfo['basename'] ) == 0
        || !isset( $pathinfo['extension'] ) || empty( $mimetype['status'] ) )
    ) {
      $filename = $pathinfo['basename'] . DIRECTORY_SEPARATOR . 'index.html';
    } else {
      $filename = rawurldecode( $pathinfo['basename'] );
    }
    if ( empty( $options['strip_queries'] ) && !empty( $parsed['query'] ) ) {
      $filename = pathinfo( $filename, PATHINFO_FILENAME ) . '.' . escapeFilename( $parsed['query'] ) . '.' . pathinfo( $filename, PATHINFO_EXTENSION );
    }
    $source      = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
    $destination = str_replace( DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $output . DIRECTORY_SEPARATOR . $url['hostname'] . DIRECTORY_SEPARATOR . $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $filename );
    if ( !file_exists( pathinfo( $destination, PATHINFO_DIRNAME ) ) ) {
      mkdir( pathinfo( $destination, PATHINFO_DIRNAME ), 0777, true );
    }
    if (
      file_exists( $destination ) &&
      filemtime( $destination ) > convertHumantimeToUnix( $url['filetime'] )
    ) continue;

    copy( $source, $destination );
    touch( $destination, convertHumantimeToUnix( $url['filetime'] ) );

  }
  zipDirectory( $output, $exports . DIRECTORY_SEPARATOR . $uuidSettings['domain'] . '.zip' );
  deleteDirectory( $output );
}

function funMD( $params, $taskOffset = 0 )
{
  // nothing here
}

function getAbsolutePath( $pageUrl, $href )
{
  $pageScheme = parse_url( $pageUrl, PHP_URL_SCHEME );

  if ( mb_strpos( $href, '#' ) !== false ) {
    $href = mb_substr( $href, 0, mb_strpos( $href, '#' ) );
  }
  if ( !mb_strlen( $href ) ) {
    return $pageUrl;
  }
  if ( !parse_url( $pageUrl, PHP_URL_PATH ) ) {
    $pageUrl = $pageUrl . '/';
  }
  if ( parse_url( $href, PHP_URL_SCHEME ) ) {
    return preg_replace( '~^[^:]+://~', $pageScheme . '://', $href );
  }
  if ( parse_url( $href, PHP_URL_HOST ) && mb_substr( $href, 0, 2 ) == '//' ) {
    return parse_url( $pageUrl, PHP_URL_SCHEME ) . ':' . $href;
  }
  if ( mb_substr( $href, 0, 1 ) == '/' ) {
    return parse_url( $pageUrl, PHP_URL_SCHEME ) . '://' . parse_url( $pageUrl, PHP_URL_HOST ) . $href;
  }
  if ( mb_substr( $href, 0, 2 ) == './' ) {
    $href = preg_replace( '~^(\./)+~', '', $href );
  }
  if ( mb_substr( $href, 0, 3 ) == '../' ) {
    preg_match( '~^(\.\./)+~', $href, $matches );
    $levelsUp = mb_substr_count( $matches[0], '../' );
    $basePath = parse_url( $pageUrl, PHP_URL_PATH );
    for ( $i = 0; $i <= $levelsUp; $i++ ) {
      $basePath = mb_substr( $basePath, 0, strrpos( $basePath, '/' ) );
    }
    return parse_url( $pageUrl, PHP_URL_SCHEME ) . '://' . parse_url( $pageUrl, PHP_URL_HOST ) . $basePath . '/' . preg_replace( '~^(\.\./)+~', '', $href );
  }
  return parse_url( $pageUrl, PHP_URL_SCHEME ) . '://' . parse_url( $pageUrl, PHP_URL_HOST ) . mb_substr( parse_url( $pageUrl, PHP_URL_PATH ), 0, strrpos( parse_url( $pageUrl, PHP_URL_PATH ), '/' ) ) . '/' . $href;
}

function getAllDomains()
{
  global $uuidSettings;
  $pdo  = newPDO();
  $stmt = $pdo->prepare( 'SELECT DISTINCT hostname FROM structure ORDER BY (hostname = :hostname) DESC, (hostname = :wwwhostname) DESC, hostname' ); // FIX
  $stmt->execute( ['hostname' => $uuidSettings['domain'], 'wwwhostname' => 'www.' . $uuidSettings['domain']] );

  $domains = [];

  while ( $domain = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $domains[$domain['hostname']] = [];
  }

  foreach ( $domains as $domain => $val ) {
    $pathUrls = [];
    $paths    = [];

    $domains[$domain]['urls'] = getAllUrls( $domain );

    foreach ( $domains[$domain]['urls'] as $url ) {
      $pathUrls[$url['request_uri']] = $url;
      // OLD WAY COMMENTED BELOW
      //$pathString                    = ltrim( rtrim( $url['request_uri'], '/' ), '/' );
      //$pathParts  = explode( '/', $pathString );

      $pathString    = ltrim( rtrim( $url['request_uri'], '/' ), '/' );
      $pathPathQuery = explode( '?', $pathString );
      $pathParts     = explode( '/', $pathPathQuery[0] );
      unset( $pathPathQuery[0] );
      if ( !empty( $pathPathQuery ) ) $pathParts[count( $pathParts ) - 1] .= '?' . implode( '?', $pathPathQuery );

      if ( substr( $url['request_uri'], -1 ) == '/' ) {
        $pathParts[count( $pathParts ) - 1] .= '/';
      }
      $path = [array_pop( $pathParts )];
      foreach ( array_reverse( $pathParts ) as $pathPart ) {
        $path = ['_' . $pathPart => $path];
      }
      $paths[] = $path;
    }
    $domains[$domain]['tree'] = count( $paths ) ? call_user_func_array( 'array_merge_recursive', $paths ) : [];
    unset( $paths );
    $domains[$domain]['pathUrls'] = $pathUrls;
    unset( $pathUrls );
    $domains[$domain]['safeName'] = preg_replace( '~[^a-z0-9]~', '_', $domain );
  }

  return $domains;
}

function getAllUrls( $domain )
{
  global $ACMS;
  $pdo  = newPDO();
  $urls = [];

  $stmt = $pdo->prepare( 'SELECT COUNT(1) FROM structure WHERE hostname = :domain' );
  $stmt->execute( ['domain' => $domain] );

  global $urlsTotal;
  $urlsTotal[$domain] = $stmt->fetchColumn();

  global $urlOffsets;
  if ( key_exists( $domain, $urlOffsets ) ) {
    $offset = ( $urlOffsets[$domain] - 1 ) * $ACMS['ACMS_URLS_LIMIT'];
  } else {
    $offset = 0;
  }

  $stmt = $pdo->prepare( 'SELECT rowid, * FROM structure WHERE hostname = :domain ORDER BY request_uri LIMIT :offset, :limit' );
  $stmt->execute( ['domain' => $domain, 'offset' => $offset, 'limit' => $ACMS['ACMS_URLS_LIMIT']] );

  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $documentName = pathinfo( $url['request_uri'], PATHINFO_BASENAME );
    $documentPath = pathinfo( parse_url( $url['request_uri'], PHP_URL_PATH ), PATHINFO_DIRNAME );
    $documentPath = $documentPath . ( $documentPath == '/' ? '' : '/' );

    $url['name']         = $documentName;
    $url['virtual_path'] = $documentPath;
    $urls[]              = $url;
  }

  return $urls;
}

function getArrayTemplate( $type )
{
  switch ( $type ) :
    case 'custom-rule' :
      return [
        'FILE'      => '',
        'KEYPHRASE' => '',
        'LIMIT'     => 1,
        'REGEX'     => 0,
        'POSITION'  => 0,
        'URL_MATCH' => '',
        'URL_DEPTH' => '',
      ];
    default :
      return [];
  endswitch;
}

function getBytesFromHumanSize( $humanSize )
{
  $humanSize = trim( $humanSize );
  return intval( preg_replace_callback( '~^([\d.]+)\s*(?:([ptgmk]?[i]?)b?)?$~i', function ( $m ) {
    switch ( strtolower( $m[2] ) ) {
      case 'p' :
        $m[1] *= 1024;
      case 't' :
        $m[1] *= 1024;
      case 'g' :
        $m[1] *= 1024;
      case 'm' :
        $m[1] *= 1024;
      case 'k' :
        $m[1] *= 1024;
        break;
      case 'pi' :
        $m[1] *= 1000;
      case 'ti' :
        $m[1] *= 1000;
      case 'gi' :
        $m[1] *= 1000;
      case 'mi' :
        $m[1] *= 1000;
      case 'ki' :
        $m[1] *= 1000;
        break;
    }
    return intval( $m[1] );
  }, $humanSize ) );

  // remove
  $last = strtolower( $humanSize[strlen( $humanSize ) - 1] );
  switch ( $last ) {
    case 'g':
      $humanSize = intval( $humanSize ) * 1024;
    case 'm':
      $humanSize = intval( $humanSize ) * 1024;
    case 'k':
      $humanSize = intval( $humanSize ) * 1024;
  }
  return $humanSize;
}

function getConvertedWebsites()
{
  $exportPath = createDirectory( 'exports' );
  $files      = glob( $exportPath . DIRECTORY_SEPARATOR . "*.zip" );
  usort( $files, function ( $a, $b ) {
    return filemtime( $b ) - filemtime( $a );
  } );
  return $files;
}

function getCustomFileMeta( $filename )
{
  global $sourcePath;
  global $documentMimeType;
  $filename = basename( $filename );
  if ( empty( $filename ) ) return false;
  $file = $includesPath = $sourcePath . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $filename;
  if ( !file_exists( $file ) ) return false;
  if ( is_dir( $file ) ) return false;
  $fileStats        = stat( $file );
  $meta             = [
    'mimetype'      => function_exists( 'mime_content_type' ) ? mime_content_type( $file ) : 'text/plain',
    'filename'      => $filename,
    'mtime'         => $fileStats['mtime'],
    'size'          => $fileStats['size'],
    'is_dir'        => is_dir( $file ),
    'is_readable'   => is_readable( $file ),
    'is_writable'   => is_writable( $file ),
    'is_executable' => is_executable( $file ),
    'data'          => file_get_contents( $file ),
  ];
  $documentMimeType = $meta['mimetype'];
  return $meta;
}

function getCustomFiles()
{
  global $sourcePath;
  $includesPath = $sourcePath . DIRECTORY_SEPARATOR . 'includes';
  $result       = [];
  if ( !file_exists( $includesPath ) || !is_dir( $includesPath ) ) return $result;
  $files = array_diff( scandir( $includesPath ), ['.', '..'] );
  foreach ( $files as $filename ) {
    $file      = $includesPath . DIRECTORY_SEPARATOR . $filename;
    $fileStats = stat( $file );

    if ( is_dir( $file ) ) $mime = ['extension' => '', 'icon' => 'fa-folder', 'type' => 'folder'];
    elseif ( filesize( $file ) == 0 ) $mime = getMimeInfo( 'text/plain' );
    elseif ( function_exists( 'mime_content_type' ) ) $mime = getMimeInfo( mime_content_type( $file ) );
    else $mime = getMimeInfo( 'text/plain' );

    $result[] = [
      'id'            => getRandomString( 8 ),
      'mime'          => $mime,
      'mimetype'      => function_exists( 'mime_content_type' ) ? mime_content_type( $file ) : 'text/plain',
      'filename'      => $filename,
      'mtime'         => $fileStats['mtime'],
      'size'          => $fileStats['size'],
      'is_dir'        => is_dir( $file ),
      'is_readable'   => is_readable( $file ),
      'is_writable'   => is_writable( $file ),
      'is_executable' => is_executable( $file ),
      'permissions'   => ( is_readable( $file ) ? 'r' : '-' ) . ( is_writable( $file ) ? 'w' : '-' ) . ( is_executable( $file ) ? 'x' : '-' ),
    ];
  }

  usort( $result, function ( $f1, $f2 ) {
    $f1_key = ( $f1['is_dir'] ?: 2 ) . $f1['filename'];
    $f2_key = ( $f2['is_dir'] ?: 2 ) . $f2['filename'];
    return $f1_key > $f2_key;
  } );

  return $result;
}

function getCustomRules()
{
  $LOADER = loadLoaderSettings();
  if ( empty( $LOADER['ARCHIVARIX_INCLUDE_CUSTOM'] ) ) return [];
  return $LOADER['ARCHIVARIX_INCLUDE_CUSTOM'];
}

function getDirectorySize( $path )
{
  $size = 0;
  $path = realpath( $path );
  if ( $path !== false && $path != '' && file_exists( $path ) ) {
    foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ) ) as $obj ) {
      $size += $obj->getSize();
    }
  }
  return $size;
}

function getDSN()
{
  global $sourcePath;
  $dbm           = new PDO( 'sqlite::memory:' );
  $sqliteVersion = $dbm->query( 'SELECT sqlite_version()' )->fetch()[0];
  $dbm           = null;
  if ( version_compare( $sqliteVersion, '3.7.0', '>=' ) ) {
    $dsn = sprintf( 'sqlite:%s%s%s', $sourcePath, DIRECTORY_SEPARATOR, 'structure.db' );
  } else {
    $dsn = sprintf( 'sqlite:%s%s%s', $sourcePath, DIRECTORY_SEPARATOR, 'structure.legacy.db' );
  }
  return $dsn;
}

function getBackups( $output = 'data' )
{
  global $ACMS;

  $pdo     = newPDO();
  $backups = [];

  createTable( 'backup' );

  switch ( $output ) :
    case 'data' :
      $stmt = $pdo->prepare( "SELECT rowid, * FROM backup ORDER BY rowid DESC LIMIT :limit" );
      $stmt->execute( ['limit' => $ACMS['ACMS_MATCHES_LIMIT']] );
      while ( $backup = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
        $backups[$backup['rowid']] = $backup;
      }
      break;
    case 'stats' :
      $stmt = $pdo->query( "SELECT COUNT(1) FROM backup" );
      return ['total' => intval( $stmt->fetchColumn() )];
      break;
  endswitch;

  return $backups;
}

function getBackupsBreakpoints()
{
  $result = [];
  $pdo    = newPDO();
  $stmt   = $pdo->query( "SELECT rowid, settings, created FROM backup WHERE action = 'breakpoint' ORDER by rowid DESC" );
  while ( $breakpoint = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $result[$breakpoint['rowid']] = [
      'rowid'   => $breakpoint['rowid'],
      'name'    => json_decode( $breakpoint['settings'], true )['name'],
      'created' => $breakpoint['created'],
    ];
  }
  return $result;
}

function getBackupsByDocumentId( $documentId )
{
  $result = [];
  $i      = 0;
  $pdo    = newPDO();
  $stmt   = $pdo->prepare( "SELECT rowid, * FROM backup WHERE id = :documentId ORDER BY rowid DESC" );
  $stmt->bindParam( 'documentId', $documentId, PDO::PARAM_INT );
  $stmt->execute();

  while ( $backup = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $result[$i]             = $backup;
    $result[$i]['settings'] = json_decode( $backup['settings'], true );
    $i++;
  }
  return $result;
}

function getBackupsCountByDocumentId( $documentId )
{
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "SELECT COUNT(1) as total FROM backup WHERE id = :documentId ORDER BY rowid DESC" );
  $stmt->bindParam( 'documentId', $documentId, PDO::PARAM_INT );
  $stmt->execute();
  return $stmt->fetchColumn();
}

function getHumanSize( $bytes, $decimals = 2 )
{
  $size   = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  $factor = floor( ( strlen( $bytes ) - 1 ) / 3 );

  return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . @$size[$factor];
}

function getImportInfo( $importFile, $cleanup = false )
{
  $import['id']       = getRandomString( 8 );
  $import['zip_path'] = $importFile;
  $import['filename'] = basename( $importFile );
  $import['filesize'] = filesize( $importFile );
  $zip                = new ZipArchive();
  //$res                = $zip->open( $import['zip_path'], ZipArchive::CHECKCONS );
  $res = $zip->open( $import['zip_path'] );
  if ( $res !== true ) return;
  for ( $i = 0; $i < $zip->numFiles; $i++ ) {
    if ( $i >= 10 ) break; // to avoid hanging on non valid zip with a lot of files
    if ( preg_match( '~^[.]content[.][0-9a-z]+$~i', basename( $zip->statIndex( $i )['name'] ) ) && $zip->statIndex( $i )['size'] == 0 ) {
      $tmpDatabase                 = tempnam( getTempDirectory(), 'archivarix.' );
      $import['content_directory'] = basename( $zip->statIndex( $i )['name'] );
      $import['tmp_database']      = $tmpDatabase;
      $import['zip_source_path']   = $zip->statIndex( $i )['name'];
      $import['loader_settings']   = $zip->locateName( $import['zip_source_path'] . '.loader.settings.json' ) ? 1 : 0;
      $import['acms_settings']     = $zip->locateName( $import['zip_source_path'] . '.acms.settings.json' ) ? 1 : 0;
      $import['custom_includes']   = $zip->locateName( $import['zip_source_path'] . 'includes/' ) ? 1 : 0;
      $fileDatabase                = fopen( $tmpDatabase, 'w' );
      fwrite( $fileDatabase, $zip->getFromName( $import['zip_source_path'] . 'structure.db' ) );
      fclose( $fileDatabase );
      //file_put_contents( $tmpDatabase, $zip->getFromName( $import['zip_source_path'] . 'structure.db' ) );
      $import['info'] = getInfoFromDatabase( "sqlite:{$tmpDatabase}" );
      if ( isset( $import['info']['settings']['uuidg'] ) ) {
        $import['screenshot'] = 'https://download.archivarix.cloud/screenshots/' . $import['info']['settings']['uuidg'][0] . '/' . $import['info']['settings']['uuidg'][1] . '/' . $import['info']['settings']['uuidg'] . '_THUMB.jpg';
        $import['url']        = 'https://archivarix.com/' . getLang() . '/status/' . $import['info']['settings']['uuidg'] . '/';
      } else {
        $import['url'] = 'https://archivarix.com/' . getLang() . '/status/' . $import['info']['settings']['uuid'] . '/';
      }
      if ( $cleanup ) unlink( $tmpDatabase );
      break;
    }
  }


  if ( !empty( $import['custom_includes'] ) && !inSafeMode() ) {
    $includesPath              = $import['zip_source_path'] . 'includes/';
    $includesLen               = strlen( $includesPath ) + 1;
    $import['custom_includes'] = [];
    for ( $i = 0; $i < $zip->numFiles; $i++ ) {
      if ( substr_compare( $zip->statIndex( $i )['name'], $includesPath, 0, $includesLen ) == 1 ) {
        $import['custom_includes'][$i]             = $zip->statIndex( $i );
        $import['custom_includes'][$i]['filename'] = substr( $import['custom_includes'][$i]['name'], $includesLen - 1 );
        $import['custom_includes'][$i]['is_dir']   = substr( $import['custom_includes'][$i]['name'], -1 ) == DIRECTORY_SEPARATOR ? 1 : 0;
        $import['custom_includes'][$i]['levels']   = substr_count( $import['custom_includes'][$i]['filename'], DIRECTORY_SEPARATOR );
      }
    }

    if ( !empty( $import['info']['templates'] ) ) {
      $import['templates'] = [];
      $templatesPath       = $import['zip_source_path'] . 'templates/';
      $templatesLen        = strlen( $templatesPath ) + 1;
      for ( $i = 0; $i < $zip->numFiles; $i++ ) {
        if (
          substr_compare( $zip->statIndex( $i )['name'], $templatesPath, 0, $templatesLen ) == 1 &&
          in_array( basename( $zip->statIndex( $i )['name'], ".html" ), $import['info']['templates'] )
        ) {
          $import['templates'][basename( $zip->statIndex( $i )['name'], ".html" )] = [
            'name'     => basename( $zip->statIndex( $i )['name'], ".html" ),
            'path'     => $zip->statIndex( $i )['name'],
            'filesize' => $zip->statIndex( $i )['size'],
          ];
        }
      }
    }

    usort( $import['custom_includes'], function ( $f1, $f2 ) {
      $f1_key = ( $f1['levels'] ?: 3 ) . ( $f1['is_dir'] ?: 2 ) . $f1['filename'];
      $f2_key = ( $f2['levels'] ?: 3 ) . ( $f2['is_dir'] ?: 2 ) . $f2['filename'];
      return $f1_key > $f2_key;
    } );
  }
  $zip->close();

  if ( !isset( $import['info']['settings'] ) ) return;
  return $import;
}

function getImportsList()
{
  global $fBucket;
  $imports = [];
  if ( !empty( getMissingExtensions( ['zip'] ) ) ) return $imports;
  $importsPath    = createDirectory( 'imports' );
  $importZipFiles = glob( $importsPath . DIRECTORY_SEPARATOR . "*.zip" );
  usort( $importZipFiles, function ( $a, $b ) {
    return filemtime( $b ) - filemtime( $a );
  } );
  foreach ( $importZipFiles as $fileName ) {
    $importInfo = getImportInfo( $fileName, true );
    if ( !empty( $importInfo ) ) {
      $imports[] = $importInfo;
    } else {
      $fBucket['getImportsList'][] = $fileName;
    }
  }

  return $imports;
}

function getImportsSize()
{
  $result         = ['size' => 0, 'files' => 0];
  $importsPath    = createDirectory( 'imports' );
  $importZipFiles = glob( $importsPath . DIRECTORY_SEPARATOR . "*.zip" );
  foreach ( $importZipFiles as $fileName ) {
    $result['files']++;
    $result['size'] += filesize( $fileName );
  }
  return $result;
}

function getInfoFromDatabase( $dsn )
{
  $info = [];
  $pdo  = new PDO( $dsn );

  $stmt = $pdo->query( "SELECT * FROM settings ORDER BY param" );
  while ( $setting = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $info['settings'][$setting['param']] = $setting['value'];
  }

  $info['hostnames'] = [];
  $stmt              = $pdo->query( 'SELECT hostname, COUNT(1) as count, SUM(filesize) as size, SUM(CASE WHEN redirect != "" THEN 1 ELSE 0 END) as redirects FROM structure GROUP BY hostname ORDER BY hostname' );
  while ( $hostname = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $info['hostnames'][$hostname['hostname']] = $hostname;
  }

  if ( !empty( $info['hostnames'] ) && isset( $info['settings']['domain'] ) && key_exists( $info['settings']['domain'], $info['hostnames'] ) ) {
    $info['hostnames'] = array_merge( [$info['settings']['domain'] => $info['hostnames'][$info['settings']['domain']]], $info['hostnames'] );
  }

  $stmt               = $pdo->query( 'SELECT mimetype, COUNT(1) as count, SUM(filesize) as size FROM structure WHERE redirect = "" GROUP BY mimetype ORDER BY mimetype' );
  $info['filescount'] = 0;
  $info['filessize']  = 0;
  $info['mimestats']  = [];
  while ( $mimetype = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $info['mimestats'][$mimetype['mimetype']] = $mimetype;
    $info['filescount']                       += $mimetype['count'];
    $info['filessize']                        += $mimetype['size'];
  }

  $stmt = $pdo->query( "SELECT 1 FROM sqlite_master WHERE type='table' AND name='templates'" );
  if ( $stmt->fetchColumn() ) {
    $stmt = $pdo->query( "SELECT * FROM templates ORDER BY name" );
    while ( $template = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
      $info['templates'][] = $template['name'];
    }
  }

  if ( !empty( $info ) ) {
    $info['id'] = getRandomString( 8 );
  }

  return $info;
}

function getLang()
{
  return $_SESSION['archivarix.lang'];
}

function getLoaderInfo()
{
  $return    = ['filename' => false, 'version' => false, 'integration' => false];
  $filenames = ['index.php', 'archivarix.php'];
  foreach ( $filenames as $filename ) {
    if ( file_exists( __DIR__ . DIRECTORY_SEPARATOR . $filename ) ) {
      $loaderContent = file_get_contents( __DIR__ . DIRECTORY_SEPARATOR . $filename );
      preg_match( '~const ARCHIVARIX_VERSION = \'([\d.]+)\'~', $loaderContent, $loaderMatches );
      if ( !empty( $loaderMatches[1] ) ) {
        $return['version']  = $loaderMatches[1];
        $return['filename'] = $filename;
      }
      if ( preg_match( '~@package[\s]+WordPress~', file_get_contents( __DIR__ . DIRECTORY_SEPARATOR . $filename ) ) ) $return['integration'] = 'wordpress';
      elseif ( preg_match( '~@package[\s]+Joomla.Site~', file_get_contents( __DIR__ . DIRECTORY_SEPARATOR . $filename ) ) ) $return['integration'] = 'joomla';
      elseif ( preg_match( '~Copyright \(c\) MODX, LLC~', file_get_contents( __DIR__ . DIRECTORY_SEPARATOR . $filename ) ) ) $return['integration'] = 'modx';
    }
  }

  return $return;
}

function getLocaleStrings()
{
  $result  = [];
  $cmsCode = file_get_contents( __FILE__ );
  preg_match_all( '~\bL\( \'(.*?)\' \)~', $cmsCode, $matches );
  if ( !empty( $matches[1] ) ) {
    $phrases = array_unique( $matches[1] );
    asort( $phrases );
    $phrases = array_values( $phrases );
    foreach ( $phrases as $key => $phrase ) {
      $phrase                  = str_replace( "\'", "'", $phrase );
      $result[sha1( $phrase )] = $phrase;
    }
    ksort( $result );
  }
  header( "Content-Type: application/json", true );
  echo json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
  exit( 0 );
}

function getMetaData( $rowid )
{
  $pdo  = newPDO();
  $stmt = $pdo->prepare( 'SELECT rowid, * FROM structure WHERE rowid = :id' );
  $stmt->execute( ['id' => $rowid] );
  $metaData = $stmt->fetch( PDO::FETCH_ASSOC );
  return $metaData;
}

function getMimeByExtension( $extension )
{
  $knownMime = [
    '3g2'    => ['video/3gpp2', 'binary'],
    '3gp'    => ['video/3gpp', 'binary'],
    '7z'     => ['application/x-7z-compressed', 'binary'],
    'aac'    => ['audio/aac', 'binary'],
    'apng'   => ['image/apng', 'binary'],
    'avi'    => ['video/x-msvideo', 'binary'],
    'bmp'    => ['image/x-bmp', 'binary'],
    'css'    => ['text/css', 'html'],
    'csv'    => ['text/csv', 'html'],
    'doc'    => ['application/msword', 'binary'],
    'docx'   => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'binary'],
    'eot'    => ['application/vnd.ms-fontobject', 'binary'],
    'epub'   => ['application/epub+zip', 'binary'],
    'flac'   => ['audio/flac', 'binary'],
    'gif'    => ['image/gif', 'binary'],
    'gz'     => ['application/gzip', 'binary'],
    'heic'   => ['image/heic', 'binary'],
    'heif'   => ['image/heif', 'binary'],
    'htc'    => ['text/x-component', 'html'],
    'htm'    => ['text/html', 'html'],
    'html'   => ['text/html', 'html'],
    'ico'    => ['image/x-icon', 'binary'],
    'ics'    => ['text/calendar', 'html'],
    'jar'    => ['application/java-archive', 'binary'],
    'jp2'    => ['image/jp2', 'binary'],
    'jpg'    => ['image/jpeg', 'binary'],
    'jpeg'   => ['image/jpeg', 'binary'],
    'jpm'    => ['image/jpm', 'binary'],
    'jpx'    => ['image/jpx', 'binary'],
    'js'     => ['application/javascript', 'html'],
    'json'   => ['application/json', 'html'],
    'jsonld' => ['application/ld+json', 'html'],
    'jxr'    => ['image/jxr', 'binary'],
    'mid'    => ['audio/midi', 'binary'],
    'mov'    => ['video/quicktime', 'binary'],
    'mp3'    => ['audio/mpeg', 'binary'],
    'mp4'    => ['video/mp4', 'binary'],
    'oga'    => ['audio/ogg', 'binary'],
    'ogv'    => ['video/ogg', 'binary'],
    'ogx'    => ['application/ogg', 'binary'],
    'opus'   => ['audio/opus', 'binary'],
    'otf'    => ['font/otf', 'binary'],
    'pdf'    => ['application/pdf', 'binary'],
    'png'    => ['image/png', 'binary'],
    'ppt'    => ['application/vnd.ms-powerpoint', 'binary'],
    'pptx'   => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'binary'],
    'rar'    => ['application/x-rar-compressed', 'binary'],
    'rtf'    => ['application/rtf', 'binary'],
    'svg'    => ['image/svg+xml', 'binary'],
    'swf'    => ['application/x-shockwave-flash', 'binary'],
    'tar'    => ['application/x-tar', 'binary'],
    'tif'    => ['image/tiff', 'binary'],
    'ts'     => ['video/mp2t', 'binary'],
    'ttf'    => ['font/ttf', 'binary'],
    'txt'    => ['text/plain', 'html'],
    'vcard'  => ['text/vcard', 'html'],
    'wav'    => ['audio/wav', 'binary'],
    'wave'   => ['audio/wave', 'binary'],
    'weba'   => ['audio/webm', 'binary'],
    'webm'   => ['video/webm', 'binary'],
    'webp'   => ['image/webp', 'binary'],
    'woff'   => ['font/woff', 'binary'],
    'woff2'  => ['font/woff2', 'binary'],
    'xbm'    => ['image/x-xbm', 'binary'],
    'xhtml'  => ['application/xhtml+xml', 'html'],
    'xls'    => ['application/vnd.ms-excel', 'binary'],
    'xlsx'   => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'binary'],
    'xml'    => ['application/xml', 'html'],
    'zip'    => ['application/zip', 'binary'],
  ];

  if ( key_exists( $extension, $knownMime ) ) return [
    'mimetype' => $knownMime[$extension][0],
    'folder'   => $knownMime[$extension][1],
    'status'   => 1,
  ];
  return ['mimetype' => 'application/octet-stream', 'folder' => 'binary', 'status' => 0];
}

function getMimeInfo( $mimeName )
{
  $knownMime = [
    'application/atom+xml'                                                      => ['html', 'xml', 'fa-file-code', 'code'],
    'application/ecmascript'                                                    => ['html', 'js', 'fa-file-code', 'code'],
    'application/epub+zip'                                                      => ['binary', 'epub', 'fa-file', ''],
    'application/gzip'                                                          => ['binary', 'gz', 'fa-file-archive', 'archive'],
    'application/java-archive'                                                  => ['binary', 'jar', 'fa-file-archive', 'archive'],
    'application/javascript'                                                    => ['html', 'js', 'fa-file-code', 'code'],
    'application/json'                                                          => ['html', 'json', 'fa-file-code', 'code'],
    'application/json+oembed'                                                   => ['html', 'json', 'fa-file-code', 'code'],
    'application/ld+json'                                                       => ['html', 'jsonld', 'fa-file-code', 'code'],
    'application/msword'                                                        => ['binary', 'doc', 'fa-file-word', 'word'],
    'application/ogg'                                                           => ['binary', 'ogx', 'fa-file-audio', 'audio'],
    'application/opensearchdescription+xml'                                     => ['html', 'xml', 'fa-file-code', 'code'],
    'application/pdf'                                                           => ['binary', 'pdf', 'fa-file-pdf', 'pdf'],
    'application/php'                                                           => ['html', 'txt', 'fa-file-code', 'code'],
    'application/rdf+xml'                                                       => ['html', 'xml', 'fa-file-code', 'code'],
    'application/rss+xml'                                                       => ['html', 'xml', 'fa-file-code', 'code'],
    'application/rtf'                                                           => ['binary', 'rtf', 'fa-file', ''],
    'application/vnd.ms-excel'                                                  => ['binary', 'xls', 'fa-file-excel', 'excel'],
    'application/vnd.ms-fontobject'                                             => ['binary', 'eot', 'fa-file', ''],
    'application/vnd.ms-powerpoint'                                             => ['binary', 'ppt', 'fa-file-powerpoint', 'powerpoint'],
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['binary', 'pptx', 'fa-file-powerpoint', 'powerpoint'],
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => ['binary', 'xlsx', 'fa-file-excel', 'excel'],
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => ['binary', 'docx', 'fa-file-word', 'word'],
    'application/x-7z-compressed'                                               => ['binary', '7z', 'fa-file-archive', 'archive'],
    'application/x-bzpdf'                                                       => ['binary', 'pdf', 'fa-file-pdf', 'pdf'],
    'application/x-csh'                                                         => ['html', 'txt', 'fa-file-code', 'code'],
    'application/x-gzpdf'                                                       => ['binary', 'pdf', 'fa-file-pdf', 'pdf'],
    'application/x-httpd-php'                                                   => ['html', 'html', 'fa-file-code', 'code'],
    'application/x-javascript'                                                  => ['html', 'js', 'fa-file-code', 'code'],
    'application/x-pdf'                                                         => ['binary', 'pdf', 'fa-file-pdf', 'pdf'],
    'application/x-rar-compressed'                                              => ['binary', 'rar', 'fa-file-archive', 'archive'],
    'application/x-sh'                                                          => ['html', 'txt', 'fa-file-code', 'code'],
    'application/x-shockwave-flash'                                             => ['binary', 'swf', 'fa-file', ''],
    'application/x-tar'                                                         => ['binary', 'tar', 'fa-file-archive', 'archive'],
    'application/x-zip-compressed'                                              => ['binary', 'zip', 'fa-file-archive', 'archive'],
    'application/xhtml+xml'                                                     => ['html', 'xhtml', 'fa-file-code', 'code'],
    'application/xml'                                                           => ['html', 'xml', 'fa-file-code', 'code'],
    'application/zip'                                                           => ['binary', 'zip', 'fa-file-archive', 'archive'],
    'audio/3gpp'                                                                => ['binary', '3gp', 'fa-file-audio', 'audio'],
    'audio/3gpp2'                                                               => ['binary', '3g2', 'fa-file-audio', 'audio'],
    'audio/aac'                                                                 => ['binary', 'aac', 'fa-file-audio', 'audio'],
    'audio/flac'                                                                => ['binary', 'flac', 'fa-file-audio', 'audio'],
    'audio/midi'                                                                => ['binary', 'mid', 'fa-file-audio', 'audio'],
    'audio/mpeg'                                                                => ['binary', 'mp3', 'fa-file-audio', 'audio'],
    'audio/ogg'                                                                 => ['binary', 'oga', 'fa-file-audio', 'audio'],
    'audio/opus'                                                                => ['binary', 'opus', 'fa-file-audio', 'audio'],
    'audio/wav'                                                                 => ['binary', 'wav', 'fa-file-audio', 'audio'],
    'audio/wave'                                                                => ['binary', 'wav', 'fa-file-audio', 'audio'],
    'audio/webm'                                                                => ['binary', 'weba', 'fa-file-audio', 'audio'],
    'audio/x-flac'                                                              => ['binary', 'flac', 'fa-file-audio', 'audio'],
    'audio/x-pn-wav'                                                            => ['binary', 'wav', 'fa-file-audio', 'audio'],
    'audio/x-wav'                                                               => ['binary', 'wav', 'fa-file-audio', 'audio'],
    'font/otf'                                                                  => ['binary', 'otf', 'fa-file', ''],
    'font/ttf'                                                                  => ['binary', 'ttf', 'fa-file', ''],
    'font/woff'                                                                 => ['binary', 'woff', 'fa-file', ''],
    'font/woff2'                                                                => ['binary', 'woff2', 'fa-file', ''],
    'inode/x-empty'                                                             => ['html', 'txt', 'fa-file-alt', 'text'],
    'image/apng'                                                                => ['binary', 'apng', 'fa-file-image', 'image'],
    'image/gif'                                                                 => ['binary', 'gif', 'fa-file-image', 'image'],
    'image/heic'                                                                => ['binary', 'heic', 'fa-file-image', 'image'],
    'image/heic-sequence'                                                       => ['binary', 'heic', 'fa-file-image', 'image'],
    'image/heif'                                                                => ['binary', 'heif', 'fa-file-image', 'image'],
    'image/heif-sequence'                                                       => ['binary', 'heif', 'fa-file-image', 'image'],
    'image/jp2'                                                                 => ['binary', 'jp2', 'fa-file-image', 'image'],
    'image/jpeg'                                                                => ['binary', 'jpg', 'fa-file-image', 'image'],
    'image/jpg'                                                                 => ['binary', 'jpg', 'fa-file-image', 'image'],
    'image/jpm'                                                                 => ['binary', 'jpm', 'fa-file-image', 'image'],
    'image/jpx'                                                                 => ['binary', 'jpx', 'fa-file-image', 'image'],
    'image/jxr'                                                                 => ['binary', 'jxr', 'fa-file-image', 'image'],
    'image/pjpeg'                                                               => ['binary', 'jpg', 'fa-file-image', 'image'],
    'image/png'                                                                 => ['binary', 'png', 'fa-file-image', 'image'],
    'image/svg'                                                                 => ['binary', 'svg', 'fa-file-image', 'image'],
    'image/svg+xml'                                                             => ['binary', 'svg', 'fa-file-image', 'image'],
    'image/tiff'                                                                => ['binary', 'tif', 'fa-file-image', 'image'],
    'image/tiff-fx'                                                             => ['binary', 'tif', 'fa-file-image', 'image'],
    'image/vnd.ms-photo'                                                        => ['binary', 'jxr', 'fa-file-image', 'image'],
    'image/webp'                                                                => ['binary', 'webp', 'fa-file-image', 'image'],
    'image/x-bmp'                                                               => ['binary', 'bmp', 'fa-file-image', 'image'],
    'image/x-icon'                                                              => ['binary', 'ico', 'fa-file-image', 'image'],
    'image/x-xbitmap'                                                           => ['binary', 'bmp', 'fa-file-image', 'image'],
    'image/x-xbm'                                                               => ['binary', 'xbm', 'fa-file-image', 'image'],
    'text/calendar'                                                             => ['html', 'ics', 'fa-file-alt', 'text'],
    'text/css'                                                                  => ['html', 'css', 'fa-file-code', 'code'],
    'text/csv'                                                                  => ['html', 'csv', 'fa-file-alt', 'text'],
    'text/ecmascript'                                                           => ['html', 'js', 'fa-file-code', 'code'],
    'text/event-stream'                                                         => ['html', 'txt', 'fa-file-alt', 'text'],
    'text/html'                                                                 => ['html', 'html', 'fa-file-code', 'html'],
    'text/javascript'                                                           => ['html', 'js', 'fa-file-code', 'code'],
    'text/json'                                                                 => ['html', 'json', 'fa-file-code', 'code'],
    'text/pl'                                                                   => ['html', 'txt', 'fa-file-code', 'code'],
    'text/plain'                                                                => ['html', 'txt', 'fa-file-alt', 'text'],
    'text/text'                                                                 => ['html', 'txt', 'fa-file-alt', 'text'],
    'text/vbscript'                                                             => ['html', 'txt', 'fa-file-code', 'code'],
    'text/vcard'                                                                => ['html', 'vcard', 'fa-file-code', 'code'],
    'text/vnd'                                                                  => ['html', 'txt', 'fa-file-alt', 'alt'],
    'text/vnd.wap.wml'                                                          => ['html', 'txt', 'fa-file-alt', 'alt'],
    'text/x-component'                                                          => ['html', 'htc', 'fa-file-code', 'code'],
    'text/x-js'                                                                 => ['html', 'js', 'fa-file-code', 'code'],
    'text/x-php'                                                                => ['html', 'html', 'fa-file-code', 'code'],
    'text/x-vcard'                                                              => ['html', 'vcard', 'fa-file-code', 'code'],
    'text/xml'                                                                  => ['html', 'xml', 'fa-file-code', 'code'],
    'video/3gpp'                                                                => ['binary', '3gp', 'fa-file-video', 'video'],
    'video/3gpp2'                                                               => ['binary', '3g2', 'fa-file-video', 'video'],
    'video/mp2t'                                                                => ['binary', 'ts', 'fa-file-video', 'video'],
    'video/mp4'                                                                 => ['binary', 'mp4', 'fa-file-video', 'video'],
    'video/ogg'                                                                 => ['binary', 'ogv', 'fa-file-video', 'video'],
    'video/quicktime'                                                           => ['binary', 'mov', 'fa-file-video', 'video'],
    'video/webm'                                                                => ['binary', 'webm', 'fa-file-video', 'video'],
    'video/x-msvideo'                                                           => ['binary', 'avi', 'fa-file-video', 'video'],
  ];

  if ( array_key_exists( $mimeName, $knownMime ) ) {
    return [
      'folder'    => $knownMime[$mimeName][0],
      'extension' => $knownMime[$mimeName][1],
      'icon'      => $knownMime[$mimeName][2],
      'type'      => $knownMime[$mimeName][3],
    ];
  }

  return ['folder' => 'binary', 'extension' => 'data', 'icon' => 'fa-file', 'type' => ''];
}

function getMimeStats()
{
  $pdo  = newPDO();
  $stmt = $pdo->query( 'SELECT mimetype, COUNT(1) as count, SUM(filesize) as size FROM structure GROUP BY mimetype ORDER BY mimetype' );

  return $stmt->fetchAll( PDO::FETCH_ASSOC );
}

function getMissingExtensions( $extensions )
{
  return array_diff( $extensions, get_loaded_extensions() );
}

function getMissingUrls( $output = 'data' )
{
  $pdo = newPDO();

  $exists = $pdo->query( "SELECT 1 FROM sqlite_master WHERE name='missing'" )->fetchColumn();

  if ( !$exists ) return false;

  switch ( $output ) :
    case 'data' :
      $stmt = $pdo->query( 'SELECT rowid, * FROM missing ORDER BY url' );
      return $stmt->fetchAll( PDO::FETCH_ASSOC );
      break;
    case 'stats' :
      $stmt = $pdo->query( 'SELECT COUNT(1) AS `total`, SUM(`status`) AS visits, SUM(`ignore`) AS `ignore` FROM missing ORDER BY url' );
      return $stmt->fetch( PDO::FETCH_ASSOC );
      break;
  endswitch;
}

function getOnlyCustomFiles( $files )
{
  $result = [];
  foreach ( $files as $file ) {
    if ( !$file['is_dir'] ) $result[] = $file;
  }
  return $result;
}

function getPageLinks( $hostname, $request_uri )
{
  global $sourcePath;

  if ( !$url = getUrlByPath( $hostname, $request_uri ) ) return [];
  if ( $url['mimetype'] != 'text/html' ) return [];
  $file = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
  if ( !is_file( $file ) ) return [];
  $html = file_get_contents( $file );
  if ( !strlen( $html ) ) return [];
  if ( function_exists( 'libxml_use_internal_errors' ) ) libxml_use_internal_errors( true );
  $html                     = convertEncoding( convertHtmlEncoding( $html, 'utf-8', $url['charset'] ), 'html-entities', 'utf-8' );
  $dom                      = new DOMDocument();
  $dom->formatOutput        = true;
  $dom->documentURI         = $url['url'];
  $dom->strictErrorChecking = false;
  $dom->encoding            = 'utf-8';
  if ( defined( 'LIBXML_HTML_NOIMPLIED' ) && defined( 'LIBXML_HTML_NODEFDTD' ) ) {
    $dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
  } else {
    $dom->loadHTML( $html );
  }
  $aTags  = $dom->getElementsByTagName( 'a' );
  $result = [];
  for ( $n = $aTags->length - 1; $n >= 0; --$n ) {
    $hrefAttribute = $aTags->item( $n )->getAttribute( 'href' );
    $hrefAbsolute  = rawurldecode( getAbsolutePath( $url['url'], $hrefAttribute ) );
    if ( parse_url( $hrefAbsolute, PHP_URL_HOST ) != $hostname ) continue;
    $result[] = preg_replace( '~^https?://' . preg_quote( $hostname, '~' ) . '~', '', $hrefAbsolute );
  }
  return array_unique( $result );
}

function getPathAvailable( $path )
{
  $pdo = newPDO();

  $pathEncoded = encodePath( $path );
  $stmt        = $pdo->prepare( "SELECT rowid FROM structure WHERE request_uri = :path" );
  $stmt->bindParam( 'path', $pathEncoded );
  $stmt->execute();
  if ( !$stmt->fetchColumn() ) return $path;

  preg_match( '~(.*?)(-[\d]+)?([/.])?(' . preg_quote( pathinfo( $path, PATHINFO_EXTENSION ), '~' ) . ')$~', $path, $matches );
  $pathPreExtension = $matches[1];
  $pathSeparator    = $matches[3];
  $pathExtension    = $matches[4];
  $counter          = 0;
  while ( true ) {
    $pathNew = $pathPreExtension . ( $counter ? '-' . $counter : '' ) . $pathSeparator . $pathExtension;
    if ( substr( $pathNew, 0, 1 ) != '/' ) $pathNew = '/' . $pathNew;
    $pathEncoded = encodePath( $pathNew );
    $stmt        = $pdo->prepare( "SELECT rowid FROM structure WHERE request_uri = :path" );
    $stmt->bindParam( 'path', $pathEncoded );
    $stmt->execute();
    if ( !$stmt->fetchColumn() ) break;
    $counter++;
  }
  return $pathNew;
}

function getPublicKeyInfo( $pkey )
{
  $info = openssl_pkey_get_details( openssl_get_publickey( $pkey ) );
  if ( !$info ) return false;
  // OPENSSL_KEYTYPE_RSA, OPENSSL_KEYTYPE_DSA, OPENSSL_KEYTYPE_DH, OPENSSL_KEYTYPE_EC
  switch ( $info['type'] ) :
    case 0 :
      $info['method'] = 'RSA';
      break;
    case 1 :
      $info['method'] = 'DSA';
      break;
    case 2 :
      $info['method'] = 'DH';
      break;
    case 3 :
      $info['method'] = 'EC';
      break;
    default:
      $info['method'] = 'UNKNOWN';
  endswitch;
  isset( $info['bits'] ) || $info['bits'] = '';

  return $info;
}

function getRandomString( $len = 32 )
{
  mt_srand();
  $getBytes = function_exists( 'random_bytes' ) ? 'random_bytes' : 'openssl_random_pseudo_bytes';
  $string   = substr( strtoupper( base_convert( bin2hex( $getBytes( $len * 4 ) ), 16, 35 ) ), 0, $len );
  for ( $i = 0, $c = strlen( $string ); $i < $c; $i++ )
    $string[$i] = ( mt_rand( 0, 1 )
      ? strtoupper( $string[$i] )
      : strtolower( $string[$i] ) );
  return $string;
}

function getRealUrl( $pageId )
{
  global $uuidSettings;
  global $LOADER;
  $metaData = getUrl( $pageId );
  $protocol = (
  ( !empty( $LOADER['ARCHIVARIX_PROTOCOL'] ) && in_array( $LOADER['ARCHIVARIX_PROTOCOL'], ['http', 'https'] ) )
    ? $LOADER['ARCHIVARIX_PROTOCOL']
    : ( !empty( $uuidSettings['https'] ) ? 'https' : 'http' )
  );
  return $protocol . "://" . convertDomain( $metaData['hostname'] ) . $metaData['request_uri'];
}

function getSafeFilename( $filename, $extension = '', $increment = false )
{
  $name = preg_replace( '~(^[.]+|[.]+$)~', '', preg_replace( '~[^-.\w\d]~', '', pathinfo( $filename, PATHINFO_FILENAME ) ) );
  $ext  = preg_replace( '~[^-.\w\d]~', '', pathinfo( $filename, PATHINFO_EXTENSION ) );
  if ( !strlen( $name ) && !$increment ) return false;
  if ( $increment ) {
    preg_match( '~(.*?)-([\d]+)?$~', $name, $matches );
    $namePre = $matches[1];
    $counter = $matches[2];
    if ( strlen( $counter ) ) $name = "{$namePre}-" . ( $counter + 1 );
    else $name = "{$name}-1";
  }
  if ( strlen( $extension ) ) return "{$name}.{$extension}";
  if ( strlen( $ext ) ) return "{$name}.{$ext}";
}

function getSchemaLatest()
{
  return '1.0.2';
}

function getSettings()
{
  $result   = [];
  $settings = sqlGetLines( "SELECT * FROM settings" );
  foreach ( $settings as $setting ) {
    $result[$setting['param']] = $setting['value'];
  }
  return $result;
}

function getSourceRoot()
{
  $path = '';

  if ( ACMS_CONTENT_PATH && file_exists( __DIR__ . DIRECTORY_SEPARATOR . ACMS_CONTENT_PATH ) ) {
    $absolutePath = __DIR__ . DIRECTORY_SEPARATOR . ACMS_CONTENT_PATH;
    if ( !file_exists( $absolutePath . DIRECTORY_SEPARATOR . 'structure.db' ) || filesize( $absolutePath . DIRECTORY_SEPARATOR . 'structure.db' ) == 0 ) {
      header( 'X-Error-Description: Custom content directory is missing or empty.' );
      return false;
    } else {
      return $absolutePath;
    }
  }

  $list = scandir( __DIR__ );
  foreach ( $list as $item ) {
    if ( preg_match( '~^\.content\.[0-9a-zA-Z]+$~', $item ) && is_dir( __DIR__ . DIRECTORY_SEPARATOR . $item ) ) {
      $path = $item;
      break;
    }
  }

  if ( !$path ) {
    header( 'X-Error-Description: Content directory is missing.' );
    return false;
  }

  $absolutePath = __DIR__ . DIRECTORY_SEPARATOR . $path;

  if ( !realpath( $absolutePath ) ) {
    return false;
    //throw new \Exception( sprintf( 'Directory %s does not exist', $absolutePath ) );
  }

  if ( !file_exists( $absolutePath . DIRECTORY_SEPARATOR . 'structure.db' ) || filesize( $absolutePath . DIRECTORY_SEPARATOR . 'structure.db' ) == 0 ) {
    return false;
  }

  return $absolutePath;
}

function getSqliteVersion()
{
  $dbm = new PDO( 'sqlite::memory:' );
  return $dbm->query( 'SELECT sqlite_version()' )->fetch()[0];
}

function getTempDirectory()
{
  return ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();
}

function getTemplate( $name )
{
  global $sourcePath;
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "SELECT rowid, * FROM templates WHERE name = :name" );
  $stmt->bindParam( 'name', $name, PDO::PARAM_STR );
  $stmt->execute();

  $template = $stmt->fetch( PDO::FETCH_ASSOC );
  if ( !empty( $template ) ) {
    $template['content'] = file_get_contents( $sourcePath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template['name'] . '.html' );
  }
  return $template;
}

function getTemplateInfo( $name )
{
  $result = ['name' => $name, 'params' => []];
  global $sourcePath;
  $file = $sourcePath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $name . '.html';
  // get params
  preg_match_all( "~\{\{@(STRING|HTML|FILE|URL)\('([-\w]+)'\)\}\}~is", file_get_contents( $file ), $matches, PREG_OFFSET_CAPTURE );
  if ( !empty( $matches[2] ) ) {
    foreach ( $matches[2] as $num => $data ) {
      $result['params'][$data[0]][] = [
        'name'     => $data[0],
        'type'     => strtoupper( $matches[1][$num][0] ),
        'string'   => $matches[0][$num][0],
        'position' => $matches[0][$num][1],
      ];
    }
  }
  return $result;
}

function getTemplateNameAvailable( $name )
{
  $name = strtolower( $name );
  createTable( 'templates' );
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "SELECT name FROM templates WHERE name = :name" );
  $stmt->bindParam( 'name', $name );
  $stmt->execute();
  if ( !$stmt->fetchColumn() ) return $name;

  preg_match( '~(.*?)(-[\d]+)?$~', $name, $matches );
  $namePre = $matches[1];
  $counter = 0;
  while ( true ) {
    $nameNew = $namePre . ( $counter ? '-' . $counter : '' );
    $stmt    = $pdo->prepare( "SELECT name FROM templates WHERE name = :name" );
    $stmt->bindParam( 'name', $nameNew );
    $stmt->execute();
    if ( !$stmt->fetchColumn() ) break;
    $counter++;
  }
  return $nameNew;
}

function getTemplates( $hostname = '' )
{
  $pdo = newPDO();

  createTable( 'templates' );

  if ( strlen( $hostname ) ) {
    $stmt = $pdo->prepare( "SELECT * FROM templates WHERE hostname = :hostname ORDER BY name" );
    $stmt->bindParam( "hostname", $hostname, PDO::PARAM_STR );
    $stmt->execute();
  } else {
    $stmt = $pdo->query( "SELECT * FROM templates ORDER BY name" );
  }
  return $stmt->fetchAll( PDO::FETCH_ASSOC );
}

function getHumanTime( $inputSeconds, $precision = 4 )
{
  $precisionN = 0;
  $days       = floor( $inputSeconds / ( 60 * 60 * 24 ) );
  $remainder  = $inputSeconds % ( 60 * 60 * 24 );
  $hours      = floor( $remainder / ( 60 * 60 ) );
  $remainder  = $remainder % ( 60 * 60 );
  $minutes    = floor( $remainder / 60 );
  $remainder  = $remainder % 60;
  $seconds    = ceil( $remainder );
  $timeParts  = [];
  $sections   = [
    'day'  => (int)$days,
    'hour' => (int)$hours,
    'min'  => (int)$minutes,
    'sec'  => (int)$seconds,
  ];
  foreach ( $sections as $name => $value ) {
    if ( $value > 0 ) {
      $timeParts[] = $value . ' ' . $name . ( $value == 1 ? '' : 's' );
      $precisionN++;
      if ( $precisionN == $precision ) break;
    }
  }
  return implode( ', ', $timeParts );
}

function getTreeLi( $url )
{
  global $documentID;

  $iconColor = "text-success";
  if ( $url['enabled'] == 0 ) {
    $iconColor = "text-danger";
  }
  if ( $url['redirect'] ) {
    $iconColor = "text-warning";
  }

  $selectedClass = null;
  if ( $url['rowid'] == $documentID ) {
    $selectedClass = " class='bg-primary'";
  }

  $url['mimeinfo'] = getMimeInfo( $url['mimetype'] );
  $url['icon']     = "far {$url['mimeinfo']['icon']} {$iconColor}";

  $data = [
    'id'    => $url['rowid'],
    'icon'  => $url['icon'],
    'order' => 2,
  ];
  return "<li data-jstree='" . json_encode( $data ) . "' {$selectedClass} id='url{$url['rowid']}'>" . htmlspecialchars( rawurldecode( $url['request_uri'] ), ENT_IGNORE ) . ( $url['redirect'] ? " -> " . htmlspecialchars( rawurldecode( $url['redirect'] ), ENT_IGNORE ) : '' );
}

function getUploadLimit()
{
  $max_upload   = getBytesFromHumanSize( ini_get( 'upload_max_filesize' ) );
  $max_post     = getBytesFromHumanSize( ini_get( 'post_max_size' ) );
  $memory_limit = getBytesFromHumanSize( ini_get( 'memory_limit' ) );
  return min( $max_upload, $max_post, $memory_limit );
}

function getUrl( $rowid )
{

  $pdo  = newPDO();
  $stmt = $pdo->prepare( "SELECT rowid, * FROM structure WHERE rowid = :rowid" );
  $stmt->execute( [
    'rowid' => $rowid,
  ] );
  $stmt->execute(); // ??? [TODO] FIX
  return $stmt->fetch( PDO::FETCH_ASSOC );
}

function getUrlByPath( $hostname, $path )
{
  $pdo  = newPDO();
  $stmt = $pdo->prepare( 'SELECT rowid, * FROM structure WHERE hostname = :hostname AND request_uri = :request_uri ORDER BY filetime DESC LIMIT 1' );
  $stmt->execute( [
    'hostname'    => $hostname,
    'request_uri' => $path,
  ] );
  return $stmt->fetch( PDO::FETCH_ASSOC );
}

function getXSRF()
{
  if ( empty ( $_SESSION['acms_xsrf'] ) ) {
    $_SESSION['acms_xsrf'] = getRandomString( 32 );
  }
  return $_SESSION['acms_xsrf'];
}

function importUrl( $input )
{
  global $ACMS;
  //addWarning( $input );
  $tmp_download         = tempnam( getTempDirectory(), 'archivarix.' );
  $acmsTimeout          = $ACMS['ACMS_TIMEOUT'];
  $ACMS['ACMS_TIMEOUT'] = 0;
  $response             = downloadFile( $input['external_url'], $tmp_download, 0, $input['user_agent'] );
  $ACMS['ACMS_TIMEOUT'] = $acmsTimeout;
  //addWarning( $response );
  if ( empty( $response['http_code'] ) || $response['http_code'] != 200 ) return;
  $mime    = preg_replace( '~(^[^;]*)(.*)~is', '$1', $response['content_type'] );
  $charset = ( preg_match( '~;[\s]*charset=?[\s]*([^\s]+)$~', $response['content_type'], $matches ) ? $matches[1] : '' );
  $rowid   = createUrl( [
    'hostname' => $input['hostname'],
    'path'     => $input['path'],
    'charset'  => $charset,
    'mime'     => $mime,
    'tmp_file' => $tmp_download,
  ] );
  unlink( $tmp_download );
  return $rowid;

}

function importUrls( $input, $taskOffset = 0 )
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $ACMS;

  $stats = array_merge( ['size' => 0, 'pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  global $uuidSettings;
  if ( empty( $input['externalUrlsList'] ) ) return;
  if ( !empty( $input['subdomain'] ) ) $input['subdomain'] = convertIdnToAscii( $input['subdomain'] );
  $input['hostname'] = ( !empty( $uuidSettings['www'] ) ? 'www.' : '' ) .
    ( !empty( $input['subdomain'] ) ? "{$input['subdomain']}." : '' ) .
    $uuidSettings['domain'];
  // support local list as a file
  $urls           = explode( "\n", $input['externalUrlsList'] );
  $urls           = array_map( 'trim', $urls );
  $urls           = array_filter( $urls );
  $stats['total'] = count( $urls );
  $urlCounter     = 0;
  foreach ( $urls as $url ) {
    $urlCounter++;
    if ( $urlCounter <= $stats['processed'] ) continue;
    $stats['pages']++;
    $stats['processed']++;
    $urlParsed = parse_url( $url );
    if ( empty( $urlParsed['host'] ) || empty( $urlParsed['path'] ) ) continue;
    //addWarning($urlParsed);
    $newUrl = ( ( !empty( $uuidSettings['https'] ) ? 'https' : 'http' ) ) .
      '://' . $input['hostname'] .
      $urlParsed['path'] .
      ( !empty( $urlParsed['query'] ) ? "?${urlParsed['query']}" : '' ) .
      ( !empty( $urlParsed['fragment'] ) ? "${urlParsed['fragment']}" : '' );
    if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) continue;
    if ( !urlExists( $newUrl ) ) {
      if ( $input['web_archive'] ) $url = "http://web.archive.org/web/0id_/${url}";
      $newUrlPath = $urlParsed['path'] . ( !empty( $urlParsed['query'] ) ? "?${urlParsed['query']}" : '' ) . ( !empty( $urlParsed['fragment'] ) ? "${urlParsed['fragment']}" : '' );
      $rowid      = importUrl( [
        'external_url' => $url,
        'hostname'     => $input['hostname'],
        'path'         => $newUrlPath,
        'user_agent'   => $input['user_agent'],
      ] );
      if ( $rowid && $input['make_local'] ) {
        $acmsTimeout          = $ACMS['ACMS_TIMEOUT'];
        $ACMS['ACMS_TIMEOUT'] = 0;
        doSearchReplaceCode( [
          'search'            => "(https?:)?//" . preg_quote( "{$urlParsed['host']}{$newUrlPath}", '~' ) . "([\s'\")])",
          'replace'           => "{$newUrlPath}$2",
          'regex'             => 1,
          'action'            => 'searchreplace.code',
          'type'              => 'replace',
          'text_files_search' => 1,
          'perform'           => 'replace',
        ] );
        $ACMS['ACMS_TIMEOUT'] = $acmsTimeout;
      }


      if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
        $taskIncomplete       = true;
        $taskIncompleteOffset = $stats['processed'];
        $taskStats            = serialize( $stats );
        return false;
      }
    }
  }
}

function importPerform( $importFileName, $importSettings, $taskOffset = 0 )
{
  global $sourcePath;
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $uuidSettings;
  global $ACMS;

  $stats = array_merge( ['pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  if ( !empty( $_POST['disable_history'] ) ) {
    $ACMS['ACMS_DISABLE_HISTORY'] = 1;
  }

  if ( empty( $importSettings['hostnames'] ) ) return;

  $importsPath = createDirectory( 'imports' );

  $import = getImportInfo( $importsPath . DIRECTORY_SEPARATOR . $importFileName );
  if ( empty( $import ) ) return;

  $zip = new ZipArchive();
  $res = $zip->open( $import['zip_path'], ZipArchive::CHECKCONS );
  if ( $res !== true ) {
    unlink( $import['tmp_database'] );
    return;
  }

  $pdoZip          = new PDO( "sqlite:{$import['tmp_database']}" );
  $sqlHostnamesArr = [];
  foreach ( $importSettings['hostnames'] as $importHostname ) {
    $sqlHostnamesArr[] = $pdoZip->quote( $importHostname, PDO::PARAM_STR );
  }
  $sqlHostnames = implode( ', ', $sqlHostnamesArr );
  if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdoZip->query( "SELECT COUNT(1) FROM structure" )->fetchColumn() );
  $stmt = $pdoZip->prepare( "SELECT rowid, * FROM structure WHERE hostname IN ({$sqlHostnames}) AND rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );
  if ( isset( $importSettings['directory'] ) && strlen( $importSettings['directory'] ) ) {
    $importSettings['directory'] = encodePath( preg_replace( '~[/]+~', '/', '/' . $importSettings['directory'] . '/' ) );
    $importSettings['directory'] = rtrim( $importSettings['directory'], '/' );
  }
  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $stats['processed']++;
    if ( !empty( $importSettings['subdomain'] ) ) {
      $importSettings['subdomain'] = convertIdnToAscii( strtolower( $importSettings['subdomain'] ) );
    }
    if ( !empty( $import['info']['settings']['www'] ) ) {
      $url['hostname'] = preg_replace( '~^www[.]~', '', $url['hostname'] );
    }
    if ( !empty( $importSettings['submerge'] ) ) {
      $url['new_hostname'] = ( !empty( $uuidSettings['www'] ) ? 'www.' : '' ) .
        ( !empty( $importSettings['subdomain'] ) ? "{$importSettings['subdomain']}." : '' ) .
        $uuidSettings['domain'];
    } elseif ( !preg_match( '~' . preg_quote( $import['info']['settings']['domain'] ) . '$~', $url['hostname'] ) ) {
      $url['new_hostname'] = $url['hostname'];
    } else {
      $url['new_hostname'] = preg_replace( '~' . preg_quote( $import['info']['settings']['domain'] ) .
          '$~', '', $url['hostname'] ) . ( !empty( $uuidSettings['www'] ) ? 'www.' : '' ) .
        ( !empty( $importSettings['subdomain'] ) ? "{$importSettings['subdomain']}." : '' ) .
        $uuidSettings['domain'];
    }
    if ( !empty( $importSettings['directory'] ) ) {
      $url['request_uri'] = $importSettings['directory'] . $url['request_uri'];
    }
    if ( !empty( $uuidSettings['www'] ) && $uuidSettings['domain'] == $url['new_hostname'] ) {
      $url['new_hostname'] = 'www.' . $url['new_hostname'];
    }
    $url['new_url'] = ( ( !empty( $uuidSettings['https'] ) ? 'https' : 'http' ) ) . '://' . $url['new_hostname'] . $url['request_uri'];
    $existingUrl    = getUrlByPath( $url['new_hostname'], $url['request_uri'] );
    switch ( $importSettings['overwrite'] ) :
      case 'none' :
        if ( $existingUrl ) continue 2;
        break;
      case 'newer' :
        if ( $existingUrl && $url['filetime'] < $existingUrl['filetime'] ) continue 2;
        break;
    endswitch;

    $url['tmp_file_path'] = tempnam( getTempDirectory(), 'archivarix.' );
    $url['zip_file_path'] = $import['zip_source_path'] . $url['folder'] . '/' . $url['filename'];
    $fp                   = fopen( $url['tmp_file_path'], 'w' );
    fwrite( $fp, $zip->getFromName( $url['zip_file_path'] ) );
    fclose( $fp );
    $url['tmp_file_size'] = filesize( $url['tmp_file_path'] );
    $url['hostname']      = strtolower( $url['new_hostname'] );
    $url['filepath']      = $url['tmp_file_path'];
    $url['filesize']      = $url['tmp_file_size'];
    $stats['pages']++;

    if ( $existingUrl ) {
      replaceUrl( $existingUrl['rowid'], $url );
    } else {
      copyUrl( $url );
    }

    unlink( $url['tmp_file_path'] );

    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $url['rowid'];
      return $stats;
    }
  }

  if ( !empty( $importSettings['acms_settings'] ) && !inSafeMode() ) file_put_contents( $sourcePath . DIRECTORY_SEPARATOR . '.acms.settings.json', $zip->getFromName( $import['zip_source_path'] . '.acms.settings.json' ) );
  if ( !empty( $importSettings['loader_settings'] ) && !inSafeMode() ) file_put_contents( $sourcePath . DIRECTORY_SEPARATOR . '.loader.settings.json', $zip->getFromName( $import['zip_source_path'] . '.loader.settings.json' ) );
  if ( !empty( $importSettings['custom_includes'] ) && !inSafeMode() ) {
    $includesPath = createDirectory( 'includes' );
    $zip->extractTo( $includesPath . DIRECTORY_SEPARATOR, array_column( $import['custom_includes'], 'name' ) );
    copyRecursive( $includesPath . DIRECTORY_SEPARATOR . $import['zip_source_path'] . 'includes', $includesPath );
    deleteDirectory( $includesPath . DIRECTORY_SEPARATOR . $import['zip_source_path'] );
  }
  if ( !empty( $importSettings['templates'] ) ) {
    $stmt = $pdoZip->query( "SELECT * FROM templates ORDER BY name" );
    $stmt->execute();
    $templatesPath = createDirectory( 'templates' );
    while ( $template = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
      if ( array_key_exists( $template['name'], $import['templates'] ) ) {
        $templateName     = $template['name'];
        $template['name'] = getTemplateNameAvailable( $template['name'] );
        createTemplateRecord( $template );
        file_put_contents( $templatesPath . DIRECTORY_SEPARATOR . $template['name'] . ".html", $zip->getFromName( $import['templates'][$templateName]['path'] ) );
      }
    }
  }

  $LOADER = loadLoaderSettings();
  $ACMS   = loadAcmsSettings();

  if ( !empty( $importSettings['integration'] ) ) {
    if ( $LOADER['ARCHIVARIX_LOADER_MODE'] == 0 ) {
      $LOADER['ARCHIVARIX_LOADER_MODE'] = 2;
      $LOADER['ARCHIVARIX_QUERYLESS']   = 0;
      setLoaderSettings( $LOADER );
    }
  }

  $sitemapInclude = 0;
  if ( !empty( $importSettings['sitemap_enable'] ) ) {
    $LOADER['ARCHIVARIX_SITEMAP_PATH'] = '/sitemap.xml';
    $sitemapInclude                    = 1;
    setLoaderSettings( $LOADER );
  }

  if ( !empty( $importSettings['crawlers_allow'] ) ) {
    createRobotsTxt( ['sitemap_include' => $sitemapInclude, 'sitemap' => detectSitemapUrl()] );
  }
  if ( !empty( $importSettings['404_enable'] ) ) {
    $path404 = '/_404.html';
    createPage404( $path404 );
    $LOADER['ARCHIVARIX_LOADER_MODE']           = -1;
    $LOADER['ARCHIVARIX_REDIRECT_MISSING_HTML'] = $path404;
    setLoaderSettings( $LOADER );
  }
  if ( !empty( $importSettings['custom_domain'] ) ) {
    $LOADER['ARCHIVARIX_CUSTOM_DOMAIN'] = $importSettings['custom_domain'];
    setLoaderSettings( $LOADER );
    $ACMS['ACMS_CUSTOM_DOMAIN'] = $importSettings['custom_domain'];
    setAcmsSettings( $ACMS );

  }

  createBackupBreakpoint( L( 'Websites import' ) . '. ' . $importFileName . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['processed'], 0 ) ) );
  $pdoZip->exec( "PRAGMA journal_mode=OFF" );
  $pdoZip = null;
  unlink( $import['tmp_database'] );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return true;
}

function L( $phrase )
{
  if ( isset( $GLOBALS['L'][sha1( $phrase )] ) ) {
    return $GLOBALS['L'][sha1( $phrase )];
  } else {
    return $phrase;
  }
}

function loadAcmsSettings( $filename = null )
{
  global $sourcePath;
  global $ACMS;
  if ( empty( $filename ) ) {
    $filename = $sourcePath . DIRECTORY_SEPARATOR . '.acms.settings.json';
  }
  if ( !file_exists( $filename ) ) {
    if ( !empty( $sourcePath ) && $sourcePath != __DIR__ . DIRECTORY_SEPARATOR . '.content.tmp' ) setAcmsSettings( $ACMS );
    return;
  }
  $data = json_decode( file_get_contents( $filename ), true );
  if ( json_last_error() !== JSON_ERROR_NONE ) return;
  if ( !is_array( $data ) ) return;
  $ACMS = array_merge( $ACMS, $data );
  $ACMS = array_filter( $ACMS, function ( $k ) {
    return preg_match( '~^ACMS_~i', $k );
  }, ARRAY_FILTER_USE_KEY );
  return $ACMS;
}

function loadLoaderSettings( $filename = null )
{
  global $sourcePath;
  $LOADER = [
    'ARCHIVARIX_LOADER_MODE'           => 0,
    'ARCHIVARIX_PROTOCOL'              => 'any',
    'ARCHIVARIX_INCLUDE_CUSTOM'        => [],
    'ARCHIVARIX_FIX_MISSING_IMAGES'    => 1,
    'ARCHIVARIX_FIX_MISSING_CSS'       => 1,
    'ARCHIVARIX_FIX_MISSING_JS'        => 1,
    'ARCHIVARIX_FIX_MISSING_ICO'       => 1,
    'ARCHIVARIX_QUERYLESS'             => 1,
    'ARCHIVARIX_REDIRECT_MISSING_HTML' => '/',
    'ARCHIVARIX_CACHE_CONTROL_MAX_AGE' => 2592000,
    'ARCHIVARIX_CONTENT_PATH'          => '',
    'ARCHIVARIX_CUSTOM_DOMAIN'         => '',
    'ARCHIVARIX_SITEMAP_PATH'          => '',
    'ARCHIVARIX_CATCH_MISSING'         => '',
    'ARCHIVARIX_BLOCK_BOTS'            => [],
  ];
  if ( empty( $filename ) ) {
    $filename = $sourcePath . DIRECTORY_SEPARATOR . '.loader.settings.json';
  }
  if ( !file_exists( $filename ) ) return $LOADER;
  $data = json_decode( file_get_contents( $filename ), true );
  if ( json_last_error() !== JSON_ERROR_NONE ) return $LOADER;
  if ( !is_array( $data ) ) return $LOADER;

  $LOADER = array_merge( $LOADER, $data );
  $LOADER = array_filter( $LOADER, function ( $k ) {
    return preg_match( '~^ARCHIVARIX_~i', $k );
  }, ARRAY_FILTER_USE_KEY );
  if ( !is_array( $LOADER['ARCHIVARIX_BLOCK_BOTS'] ) ) $LOADER['ARCHIVARIX_BLOCK_BOTS'] = [];
  return $LOADER;
}

function loadLocalization( $languageCode )
{
  $localization = [
    'ru' => [
      '0016c34617750f65f58ad8d5f010f43771261649' => 'Кастомные файлы',
      '00434aa23d611e21c165328d61990ba78eb5868a' => 'Удалить ссылки',
      '004809c67af15dec187be8a45087a84eb0381fa8' => 'Регулярные выражения онлайн',
      '00b65e51cd6aea3ff7b47f7bd64fe5f9163e28dc' => 'Некоторые URLencode ссылки, содержащие нелатинские буквы или знаки, могли быть закодированы неправильно. Этот инструмент исправит такие ссылки в HTML коде.',
      '01f03837323aab32409bb99c50d6c475179c49df' => 'URL',
      '025a6c8350f47fb82c1637e7b8f16d194a861cc0' => 'Отправить всё в инструмент импорта URL',
      '027c9f8d5ca29414145270f276f917ed3e4e33e1' => 'У рабочей папки нет прав на запись.',
      '0354c8896bf5039d44c8f1c1b81ea30b175dffc1' => 'Кастомный домен',
      '038406ca8707c8ffba2fde1b0dacb23a38e6e1af' => 'Обновлено %d неправильно закодированных URL в %d страницах.',
      '03a07bbc1fe1d5f774867a7924aa98d07e41d0d3' => 'Транслитерировать латиницей',
      '03a3e29c35a9ba8b2ff43baf4398414e485db220' => 'Метатег viewport',
      '040b5cd4f34af7ffded9bf68b293d44f9f0c70f1' => 'ноль или более',
      '04278167876dbcf68be445bf57db2f1935fcf28f' => 'Удалено %d внешних ссылок в %d страницах.',
      '0452ee2ede26fff173b067ec3cd62d05ef616db4' => 'Осталось %s свободного места',
      '04a212215ef9fbf686d280802eb81ee7a6e681cd' => 'Подтвердить',
      '04a37120960452602640003cc2c386c79c2dee9e' => 'Zip файл %s с сайтом удалён.',
      '0690f8062264ef2395eed857bbf1665fa2accf38' => 'Введите серийный номер',
      '07a1dee5489d2603c09dc76ad53273cfec2befdc' => 'Ограничить количество отображаемых результатов в Поиске & Замене. На процесс замены этот параметр не влияет.',
      '0806a964897f9494a434f22ac0eab0da8a0a6de2' => 'Свободное пространство',
      '081abd0b62589d6e2905a903895657151018f3eb' => 'Найдено страниц: %d; суммарно вхождений: %d',
      '0868b58f6c18562037ef26a7b6d7b879019d5234' => 'Внимание! Переустановка удалит и заменит все URL сайта и их содержимое.',
      '08ac72820a2d55f9e50ecbe836ae9fe7258e3464' => 'Внимание! По соображениям безопасности мы рекомендуем переименовать дефолтное имя файла "archivarix.cms.php".',
      '08cff553c23177dc8e0515f8b4c7f53b2f395bf5' => 'Публичный ключ для API установлен.',
      '095cb90b0441573119a6dd2b704c20b88c594a52' => 'Импортировать список',
      '0a93ee9d84ef1ef0b7cc95432b64a4b0cc84c9a5' => 'Хосты и кол-во/размер хостов',
      '0a9a8962489ffa106a19988ef9d115d39a433fbc' => 'Рекомендуем создавать копии в той же директории, что и оригинал.',
      '0c8c4092780cf7e53c44ace2e64d4c82ecf1f944' => 'ленивые один или более',
      '0cc91a24b2c017b3d67e2f4c63a912a22223ee0f' => 'между n и m повторений (включая)',
      '0d5fda95e3940bc09dc1ccc22666813c0b54f73d' => 'Диапазоны',
      '0d7fa41ab2433ae3c948ee3eaf06dd3e0ca2272b' => 'В большинстве случаев оставьте пустым.',
      '0d9b94e827df187658649fb3228a9cf66313dba1' => 'показать файлы',
      '0dda7b8f2a70fd2b8d1059b5ca84086fb67d000a' => 'Не удалось подключиться к серверу обновлений.',
      '0e2d9b0777a485c1276de0803c12a7d76fbc5c39' => 'URL',
      '0e2e073d9f688c10bd945421ced4fb3e8f103b69' => 'Viewport добавлен в %d страницах.',
      '0e5d9740f13882ec67776262fdc57d6f32b90d83' => 'Выберите точку отката',
      '0e6e6cdc83739ad2f2d30c9632fa7559ea1f768c' => 'Оставьте пустым, чтобы соответствовать всем URL-адресам',
      '0e7383487387213f8f33b310d457c956a84efa2d' => 'Скопировано в буфер обмена',
      '0e7d934820897f0ee0b3946f48a1d8846cad29ba' => 'Очистить кэш',
      '0ea7288fabe36d1374b87e85268bd81849d9bced' => 'Размер истории',
      '0eb5ed506e4923c28d7f4a8aa69efe99b3ad75d1' => 'Информация',
      '0f3e5fbe2c645f80489f8470cfee8c4353099661' => 'Добавьте публичный ключ для удаленного редактирования сайта приватным ключом.',
      '1094cda4b670cc70a685dd1ff506e41cb6f5d82e' => 'Нельзя создать URL с уже существующим путём.',
      '113046bfa5599c49f7825e8847203d3195ebdff3' => 'Перезаписать все URL',
      '1140a218c4eec8b5a943d256ad0af8f3008d4660' => 'Обновить внешние ссылки',
      '118a9989815489c24b81b160782015890ed2085e' => 'комментарий',
      '11e291b32a9409f55b579abde047f4236bb73b4f' => 'Включая Кастомные файлы',
      '12605ebbc10bd2c694d54cf0b4b633ca3fcbf6cb' => 'Удалить шаблон',
      '12b273ae26c59aa33cf5143c39dd318529f08395' => 'Будьте осторожны, так как неверный синтаксис может привести к внутренней ошибке сервера.',
      '131b17f775cfeef60be55a25f32a787d55ed3489' => 'позитивный просмотр назад',
      '13b9a04dcb3e0ce7661aca803fc0ceecf849e1d8' => 'Равно',
      '13c3217bcdf9900eb3ef7e7ed58a28728f763960' => 'Поднять время для кэширования статических файлов. Чтобы отключить кэширование, поставьте 0.',
      '1435c41ea93bdd09456bdc843a202600af4c6553' => 'Внимание! Ваша папка .content.xxxxxx содержит лишние файлы, которые там не должны находиться!',
      '144af9f71c7d0289f8bd407f819dce81bafdf5c6' => 'Выберите протокол, на котором должен работать сайт.',
      '14fc863955f6e55d95459548def3b9aa0a7aacb4' => 'Этот раздел доступен, только если доступ защищён паролём. Сначала установите пароль.',
      '15140298926807d08eebf3a2f094565218094c11' => 'Всего файлов',
      '1560468e619100d31e40a83617337648a585d72b' => '%s не удалось обнаружить. Пожалуйста, обновите вручную.',
      '161e30926424f076b7ddf8e628e89fa40fef3d56' => 'PHP расширения',
      '16384ffc96a04ff099a875878c8c470acd0e639d' => 'Импортировать настройки Archivarix Лоадера',
      '16b710a2172242b52e62ae494cbee52187d3dc46' => 'Пароль администратора',
      '16bc19359876c6364b6056a91b75bbec0160849c' => 'Позволить индексирование в robots.txt',
      '170c6d2e56e8b4e9ec094b58afe212ec6e8296a6' => 'User-Agent',
      '1758356db21759f7c5a0da9b4dd1db8fd6feab3f' => 'или',
      '17aa0dbebbfc3843fb952a4c00895bff380f2eea' => 'Название шаблона может состоять только из латинских букв, цифр, тире и нижнего подчёркивания.',
      '17d2ff26f933593bd02a39e4ef636c3d7d8ad7e3' => 'Дата/время',
      '19a532c8bc61c311f583455c80ffe37067bbc9bb' => 'Изменено',
      '1a74fad16e5f72571b762267568ce8b15aef1502' => 'MIME-тип',
      '1aea28cf1dff9ac46b77dfd153e2a817a6b1bbf3' => 'Шаблон %s обновлён.',
      '1b151166e8da3069fc444c0ecff15caef9fcbf0a' => 'Интеграция со сторонней CMS, на главной другая система',
      '1b3175c0e35c2a56be638f705ac2b30917729bd5' => 'Введите новый путь, начинающийся со слеша. Это поле не может быть пустым.',
      '1b4f6403ea7b35ba0ee20f4a34678cc048a6f917' => 'Прочтите инструкцию',
      '1c12e6a399cb7bb36f24ef9249a9192dc27db96a' => 'Битые URLencode ссылки',
      '1d0c2590514c36326ffcb6f6515373e11097b838' => 'Удалить токен',
      '1d3d412a0852cc56c28ad0c2a1153229aa365b43' => 'Меньше',
      '1e149429f291c414f42dcf40d8d538ad2370cf23' => 'Импорт сайтов',
      '1e326a663cddb4231701d2610415b0c6a04b9813' => 'конец группы',
      '1eb844b3ab178ed9c5f5ea292b0d2e24136f0371' => 'Отобразит однопиксельную прозрачную заглушку у всех отсутствующих изображений вместо 404 ошибки.',
      '1ed77c3f7ffc41a33eadccef5727dc7c97079235' => 'Протокол',
      '1eea3d5309d2a88c1e83cbfafba24489c41a09ad' => 'Очистить',
      '1f80e75eb278bb7e49cdfe23ce1489e728a52c93' => 'Обновлено %d внешних ссылок в %d страницах',
      '1f92a45b23e5ffed5dd135546ef469566fdecb76' => 'Открыть URL-адрес в веб-архиве',
      '1fcd73919d510b3ba1566e318c163f12f54eb8ee' => 'Похоже, что это параметр не включён в Настройках Лоадера.',
      '1fffdad2435a85a8238b78615b5c28e7247dd346' => 'Ограничить по IP',
      '21ea655b02370c9b9cbbd36a0499b95a47447427' => 'Директория для session.save_path, которая настроена как %s не имеет прав на запись. Это может создавать проблемы при работе с сессиями.',
      '21f82ddcb6ab0d7ee55e55d3b10e7f3f6e9b03d9' => 'Удалить все',
      '220a998f4a1ae6678153b474bea23e35810e687f' => 'Глубина страницы',
      '2222841b97443aeb96d12840b6513fc2e6395e1a' => 'не a, не b и не c',
      '223235a4cb340d8e8e88075a8a4dd961d882cf1e' => 'поддомен',
      '227cc640570a0d013ae5fa41683c192b2c42ec2c' => 'Загрузки',
      '22d5a9d32f32c4e34c2812f24d782829e1b1232a' => '%s обновлен с версии %s на %s. Нажмите на логотип в меню, чтобы перезагрузить страницу в новую версию.',
      '22ffbbe3c16b09c9f7bec001a9bd6f95598388a4' => 'Введите путь (напр., /sitemap.xml), по которому будет отдаваться актуальная xml-карта сайта.',
      '231e96fbe484f951336a019a09b477fd74a5efbc' => 'Введите кодировку, если необходимо',
      '2416e2f347f3d4b43aff61d29375fc6feb111890' => 'не граница слова',
      '24f794abbcf7720c9e4edeeb81513acec436ecbd' => 'Не содержит',
      '251842f1da9f7b26a44ab4715147e362e3c5ad7f' => 'Важно! Пароль администратора и пароль для режима безопасности не должны совпадать. В противном случае вы не сможете войти в систему как администратор. Если вы не предоставляете доступ кому-то ещё, не нужно устанавливать пароль безопасного режима.',
      '26e711542d9d04f947792d2eb63b819407e4a88a' => 'Текущий пароль зашит в исходный код. Настройки пароля ниже не повлияют на зашитый пароль.',
      '278e52cd8252a75ead03a42eeb6e99f4ce8e934d' => 'Она принадлежит пользователю уровня администратора (root). Пожалуйста, исправьте владельца/группу папки, а не права на запись.',
      '282c2a0ff1fcbdfb7a518ad04eb08f1cd5276a35' => 'Сохранить только параметры',
      '286f4cbc9de27b82a093c001dd72d0110d527840' => 'Визуальный редактор',
      '2946c64caa280fb816172a3d19e4004a4c1ce12e' => 'Введите название домена',
      '2956ed532549547c22f57147e35241fbd8127192' => 'Перенаправить отсутствующие страницы',
      '29893f85bf54111733363bb148a0b4c565a606de' => 'не символ "слова"',
      '2b1e7902b7adc9bd5c4d383a605658813d1c2c92' => 'Больше или равно ',
      '2b4237441eade0c7641f2b9c979d6c60367d46f1' => 'Клонировать URL',
      '2c4c7d6baee492ac02950bf19cd8fd4b3301769a' => 'непробельный символ',
      '2c537e67d72036e236dfc9e0ad5dcf30cfdb2e8d' => 'Введите пароль или оставьте пустым, если менять не нужно.',
      '2ce923f51d90c20afa317e11b59dc341fcf8022c' => 'прибл.',
      '2cf84b60e4efea5e1f2b190b1c6067bdb2cac980' => 'Список вхождений User-Agent ботов, которые получат 404 при посещении любой страницы.',
      '2da600bf9404843107a9531694f654e5662959e0' => 'Версия',
      '2dc4a79c7d132df069e86fc0932f2539512455a0' => 'Версии в CSS и JS',
      '2e0d448e7fad001238f3bb01d9a56493b1e7d763' => 'Сайт переконвертирован.',
      '2e62bc4446bc06dc659d1cb35dde6cd39e30436a' => 'Макс. память PHP',
      '2e781882a962fca33b99103b0f9f399eda6ddc73' => 'начало символьного класса',
      '2e8a57cc5c472f4ac3b071979a38e80db7e59e87' => 'Сайт',
      '2f2c3146537261d33a8dc88688fdef6d36385fe5' => 'URI адрес',
      '2f4f6d15f4f7b07956ce81af267ab076231d7a9b' => 'Оставьте пустым, чтобы для любой глубины',
      '2fbb1e3551274ede6619f653e201e64059627f4f' => 'Подтвердить все замены',
      '30932547b5cf988ff732b6af121df95b9a682c9d' => 'Только поиск',
      '3095a2c04e5192c3c5386f7758ff5beb2f4f5b80' => 'Включая настройки Archiavrix Лоадера',
      '3159fe421b3221381b3c778dc1c3c26e4540be37' => 'Пусто',
      '3190c18baaea838d090d51299fc461d1795c8967' => 'Список внешних URL-адресов',
      '31b331f509c5c9e1a6b4ecbcf0cf03e209f246b1' => 'Загруженный .ZIP файл имеет неправильную структуру',
      '31fc2bb85f2cf7d32d512c8365d124f9123e3c7d' => 'Индексация сайта в robots.txt разрешена.',
      '321fa217abe93b1bedf827e7201468c030bcb713' => 'Подтвердите действие',
      '322444d3bb52c341f429ca0454f292dc242f315b' => 'Любое',
      '32269120283c856e9e53eff3c0c45fb17aee1294' => 'Исправить отсутствующие .css',
      '322d870f9cd63399c6393ec9c46df4e3fb5da704' => '%s обновлен с версии %s на %s',
      '32f6e7307249206b290cccdda3c8eb5d49be3632' => 'Серийный номер должен быть в формате 16 символов XXXXXXXXXXXXXXXX или XXXX-XXXX-XXXX-XXXX',
      '33e15d008d511f3101566a2e25203ef2a3f605a0' => 'Содержит',
      '342b7df0b976acd0d93a527d2106493afcb9d5e4' => 'Не забудьте добавить адрес этой страницы в закладки!',
      '346ba28004974f306f601a3962c753fcf10c270e' => 'конец объекта',
      '34b53bd007d3cd2f5a6b74eca9808f5d16f2512a' => 'Не удалось удалить шаблон %s.',
      '34eb4c4ef005207e8b8f916b9f1fffacccd6945e' => 'действие',
      '353754401e3efbacd3e7b342242ec96ae1ebf9d2' => 'Другое значение viewport',
      '36538fd6aad67c458a5c90c5c038bd054998490e' => 'начало объекта',
      '366246b92e0bc8e95904a6a6e60dacb0cbaa2589' => 'Запретить историю',
      '368e1a389e2b5207342730b00fe80b52ecad9a10' => 'Размер импортов',
      '36c2b435c298b7229394851497ce5f8fc6d086d6' => 'Файл %s успешно создан.',
      '377de6802ac2edbb5808d9373a5d803da96c3d5b' => 'Обнаружена глубина для %d страниц. Найдено %d орфан-страниц.',
      '38307aeb76a19b8e20044338135d8b84446fea35' => '404-пользовательская страница, если включен режим 404.',
      '38334226ad27c481bda86ec3df598cbdfd34fa59' => 'Конвертация %d файлов в %s завершена.',
      '38f35f7a01c656f6499be6c5b1354de21203897a' => 'Заменить сразу',
      '38fbc930ab475e9972c216c62bcccf4ff6b40108' => 'Перезаписать существующие URL только если версия импорта новее',
      '3a0ccf14deb4777d45cb00e5fdb891a89ec42690' => ' Не равно',
      '3a7e064f3f6eddf09484782c450c97a9373d7e5b' => 'Открыть URL в новом окне',
      '3b00154cb6481b657be2acc974cc3e13690b69d5' => 'Отправка с AJAX не удалась. Убедитесь, что у вас не включен mod_security.',
      '3b1264a5f7bd8251700f4f73a952db4192840842' => 'Удаление битых ссылок',
      '3b9fb53a47b33d506e9265583a2d8af40c9c4fa1' => 'Замена невозможна. Неправильный новый URL.',
      '3e19e674f6d4dc860b237f986640966fe78b58dd' => 'Настройки Development Mode обновлены.',
      '3e577093a1076fcf00a3b11c60cf57a4128778f0' => 'Выберите JSON файл с настройками',
      '3e682693d76c77ad239049b5b2f7eff67983c3ce' => 'Имя файла скрипта Archivarix CMS было переименовано в новое значение.',
      '3f15ce79532d7a88716daffe2b5fa5245abe9c20' => 'Конвертировать',
      '3f50d89e8015b6ce71d7d54366d0052db8927c58' => 'Редактор HTML по умолчанию',
      '3f53f3ccc561e0934b9e32f41eb0b54fe9327ef5' => 'Совпадения не найдены.',
      '3f588b0e07a9a1df4b268313e504496f33422556' => 'Искать в коде/тексте',
      '3f66052a107eaf9bae7cad0f61fb462f47ec2c47' => 'От',
      '3ff340ad82c674b8d49dbe00c9aa35f38523371d' => 'Заменить, если уже существует',
      '409ad8e9d994c2ccffece8ce2f34332d28a96f90' => 'Если вы используете NGINX + PHP-FPM, то необходимо добавить доп. правило для направления ВСЕХ запросов к несуществующим файлам и папкам на /index.php',
      '424103adcd33bfed9307c303ee577f9b9ee59b7c' => 'пробельный символ',
      '42712a35a86f584b6c8d2a3c90bf4293ee9fbbbc' => 'Сканировать и определить',
      '43dd32f1bff1c8b3c42807d0cdecf19aa67bcab0' => 'Полный путь URL',
      '447b5f76cd2b47272ff6d03c44476cb16e9979dd' => 'условие (если, то)',
      '44ff84543c6b90d8142878b1ebb1d32740666804' => 'Создать/перезаписать robots.txt для индексирования всего сайта.',
      '457fa7edbc4a898d4ff18e59243659a9849109d7' => 'Обновить код, чтобы сделать локальными',
      '45d0df35c727b943c962e23586be0cafbd8a3340' => 'Размер файла',
      '461f80e54bf29cd3a8f1ac461b2834e6ee5984dc' => 'Просмотр для этого типа файла может быть недоступен в браузере.',
      '46278bf8a65a51bdd701ffc9f3f8e196b77e6a88' => 'граница слова',
      '465975447fe1271f466f1ddd1944a6f9b3f8d118' => 'Модификаторы подгрупп и Утверждения',
      '4818e41f4a70b2c94970a0d8ec3565765662b60c' => '301-перенаправление для всех отсутствующих страниц, чтобы не терять вес с бэклинков.',
      '489a033301b14cf219d3ad3a789c803b71ca25fa' => 'цифровой символ (0-9)',
      '48a88ed4cbe840e00eda1134b35be23584224c23' => 'Информация о системе и сайте',
      '4928e255382281a99e15e3e1b12c1c6d2c8b5a2f' => 'Макс. размер загрузки',
      '4a0a1b4d693e68eb4f31753364b892ff2e1588ea' => 'Archivarix CMS использует библиотеку %s для регулярных выражений',
      '4ab424a7706524fb0823834d376ce4f6d573ec3f' => 'Точка отката',
      '4b32622d74eafe0f5acab8247e7dbf7bfed29983' => 'Новые настройки применены.',
      '4b5bd4416a23e8496e2a967ed16237c4cc9facb2' => 'Шаблоны для User-Agent ботов',
      '4bc60bdce0d4a5f6258f22d3921bed867456aa97' => 'Название папки с контентом',
      '4c1d87627bdc72f8ee95b797870de5cd936316e1' => 'Поиск и Замена',
      '4dbcd60ec442a8199dd9b177f7c5e1831d33732d' => 'Хосты',
      '4e75a7b73a16aded51a03a68b79fc7d5a52cbfbe' => 'ноль или одно повторение',
      '4ec1c4dba5d21f89fceea7b83b9037caa7dbe504' => 'У вас уже установлена последняя версия %s %s',
      '4f0c685cced76c52070d00490c49a5edd1501413' => 'Включая Шаблоны',
      '4f9be057f0ea5d2ba72fd2c810e8d7b9aa98b469' => 'Содержимое',
      '4f9c814110a998e7198dfe8b14c26cc745df99e2' => 'a или b или c',
      '4fa8cc860c52b268dc6a3adcde7305e9415db5bb' => 'Инструменты',
      '506918ecf9bf8a50297342f4643dcbcea257dd97' => 'Чтобы продолжить, необходимо включить следующие PHP расширения: %s',
      '50bee113c5c549dbc08ddf570b3b739fa3612f3c' => 'Редактор, который будет показан по умолчанию при редактировании HTML страниц.',
      '50d44e7c8dd2321ebd922e6485c3868a22f96e48' => 'Только этот сайт (по умолчанию)',
      '50f94286ba30706a19070d3ec0a0c8d34d6cf6eb' => 'Предыдущая',
      '51556ded8e2462ccefddede6d202f04e6f1d17be' => 'Этот инструмент просканирует все теги изображений на отсутствующие локальные и удалит их теги.',
      '519d99b816afb63b80c5332b6b2c2e73b959557b' => 'Определить глубину страниц',
      '519e39132bb98418cc0483cc446ccf5f3c8dcffe' => 'Путь',
      '5321be302d827f876fb976a3fc5a92837dfc7f76' => 'Исправить URL encoding',
      '53864b04923984987e1519bfb2ae1c42496a053d' => 'Игнорировать строку запроса, если совпадение не найдено',
      '5397e0583f14f6c88de06b1ef28f460a1fb5b0ae' => 'Да',
      '5409cae152154321d2beb5731f349ed8bbaae103' => 'Также удалит всю существующую историю.',
      '541c7a8ed70d5c526ea7bf873051b300c2099723' => 'Не забудьте добавить новый адрес в закладки после переименования!',
      '54a23faa8a621a5ff25553d308e1a30dfb62294d' => 'Пароль для безопасного режима',
      '550b1907d50fa2748da7f5144b857975d26a48f0' => 'Включить Dev Mode',
      '5522f56efd94226b11d17a8443e7cf06a5dc70ec' => 'Группы и классы',
      '552bf827bbe14163e67cadb53f372c51e52b76e2' => 'Файл поврежден или неполный. Вы можете удалить его и освободить %s места.',
      '55594433a9d43fa78c8260c5f42ebfa21e86501c' => 'начало текста',
      '55d90e5b31e48e0345036f91f593796d519daedc' => 'Версия PHP',
      '56a97a80f76c4ec4e1a5027159fad732338494d1' => 'MIME-типы файлов и размеры',
      '56e6deab95026cf303a9da0c514252461259265c' => 'Показать строки без изменений',
      '57980c45e4c9e695a2e67c43f9abf5e255e41da2' => 'Конвертация сайта в UTF-8',
      '57e33bc2cbc75c6e1ad96b26f8a861bef22a560c' => 'Общие действия',
      '58630bd27701e347fdf5016a49de3461e119d93f' => 'Если вы вручную вносили изменения в их исходный код, то эти изменения будут потеряны.',
      '589e54eb23b6b13bbdea6ef1063fb2307c73a029' => 'Удаление битых изображений',
      '58ae84100cc8f83a73372964a28ffc89ae8b765d' => 'Обработано: %s',
      '58cfcdca5ed542240131e60e7a5af5e83f61e786' => 'Перенаправление',
      '58ec75a772b67659063cd93055d62115d81e8429' => 'Интеграция со сторонней CMS, на главной этот сайт',
      '593258c53c8efe09ad8d84ce19844c0a9b9b510a' => 'Путь страницы',
      '5959d22b83eb4c757fa17bca648818f9e64cab6d' => 'любой символ',
      '599294f23ef04a992bc69ba465066344c3c7cf34' => 'Больше, чем',
      '59bb5522a0b364762c149de8f827884e94889338' => 'нежадные между n и m повторений (включая)',
      '5a7cd562f2a26aaa21892d885fd01458ca2c807f' => 'Не перезаписывать существующие URL',
      '5ac87b9295032e63ca963faf970bf2d2b90abdbd' => 'Вы также можете удалить все внешние ссылки, сохранив анкоры и содержимое.',
      '5b15a4609090308db6d89ea08f7ce834abe10052' => 'HTTP и HTTPS (по умолчанию)',
      '5be1461c380b453b1e2466a2881eeb6aa35a13cc' => 'Не удалось удалить файл %s.',
      '5bf79039f73389484c9a897c0648f61fa18ea644' => 'Редактировать код счётчиков',
      '5cc5a245f0b1afe6cae73bd9bdebd37e9c28475b' => 'Archivarix Лоадер',
      '5d384e5e1a9e42456e032d8ea856daea9f23921a' => 'Резервных копий нет.',
      '5da27f1313fd169f7595adc302d27fdfdec18436' => 'Вы можете сделать это в любое время вручную на сервере, или нажав кнопку, которая переименует файл в случайное имя.',
      '5de19119e110df28e9c32dabb3a96c9ef1440489' => 'Удалить текущий пароль',
      '5dfb1eb90efe4cce461805fed4003f11fdc5946b' => 'Проверка запроса',
      '5e1b57cbd24a8ca362bfd8f8ab58b6174162719c' => 'Внимание! Ограничение по IP или паролю не настроено. Кто угодно может видеть эту страницу.',
      '5e5a1b3166a20cdda1ba196ecd6063e257044a61' => 'Введите MIME-тип',
      '5ecdd2b79f33f6a9afffbc38a80ec4bda2b3c57c' => 'Преобразовать в www',
      '5f2f7b3df37bb1a2b79b8959e7c1b10e8826a674' => 'Откатить до точки',
      '5f56b9880010be2703f8fbd3a6064f67bee06d64' => 'Перекиньте файл, чтобы заменить.',
      '5fd4c03125350ba0ccaf87df0ec5052317da2a1c' => 'Откатить выбранное',
      '5fec120c644a1167616dcdb2e9d0d807e1dd5913' => 'Содержит: %d файлов, в объёме %s',
      '6078532ab203b30b0c8e32bb799fd87d65f55236' => 'Кванторы',
      '607b7ed1065e1fe5682dd7823997160903b798cd' => 'Замены в Коде',
      '612e12d29278b5519294bc25cdaddffec6d0f1c6' => 'Результаты',
      '61c1a9ffefa8bba2470d8ee68b6358cff60ecf81' => 'В безопасном режиме вы не сможете создавать/редактировать кастомные файлы с исполняемым PHP кодом или нанести какой-либо вред серверу.',
      '62a968483e50ceacae63f1b5fd47a9ce02519d88' => 'один или более',
      '62e14df03c8a7450d23378a59c8c668317c7eeea' => 'Показать публичный ключ',
      '630d06f3dac00d22fcd949d010e275ee2371fb7b' => 'Если настройки содержали пароль, то вы увидите форму входа на следующем клике.',
      '64be6f10e2ea1e50cf33934a03fbcc4629e30dcb' => 'Настройки CMS и Лоадера, внесенные через меню настроек, сохраняются.',
      '64dcf4de6bbc7f9a97b72b1fe587f00e30f87969' => 'конец текста',
      '64ec15a13b7e6d13d2779bbe2a896cd178c74a66' => 'символ "слова" (a-z 0-9 _)',
      '65982f389422e03d2165feb83c6a47398567517e' => 'нецифровой символ',
      '65da935aa66f63b037d1dc95c0c17651676d4374' => 'Не удалось обновить код счётчиков.',
      '66639f7d455dd71faa9147f5d69e5ce885d8bebd' => 'Бинарный',
      '669fb2347b8d6ed078ddd9449692832cd2f2e8c7' => 'Правила вставки/замены кастомных файлов и скриптов',
      '66e0d47e02470c2f5ce3f7a3bc93891cc04fef26' => 'Откатить до',
      '66e12969c225cc6d65e18210488acb826eba907e' => 'Количество',
      '6721e59e3ae25571dc7eab85278877e2703e3f9f' => 'Взять из ВебАрхива',
      '67f927c67acd3eea5b8cf5915a7dc7d66f336ebe' => 'позитивный просмотр вперёд',
      '685a93fb8d36acd2a9fa3f35b28dc3f012043938' => 'Конвертация изображений в WebP',
      '68d837cf59ff4440596d1bf5e0427bb64921e8f6' => 'Не закрывайте окно браузера!',
      '68e31410f6336605e1aca3bf212e339b84676948' => 'Текущие бэкапы занимают %s места. Не забывайте отключать или удалять их в случае ненадобности.',
      '68ed7ca44fcd6e7f01fba4a0c076575e29e6a0e5' => 'Применить к URL с глубиной',
      '690b9dbb0998b29c0e7f4b6047877e401fe6d19c' => 'условие (если, то, а иначе)',
      '693257149e548d525a8f32ef1e83d2c26cae6676' => 'Публичный ключ недействителен. Убедитесь, что он в PEM формате.',
      '6946580cadeec1d0593d05fb96489ceee7b94089' => 'Укажите название папки вместо .content.xxxxxxxx, если вы её переименовали иначе, или у вас несколько папок с контентом.',
      '69ce5bab3f97885c3d9211ade06ba4aded4f42c3' => 'Не удалось обновить файл %s.',
      '6a9171fa4dd064e0ca11b16b669436cf672c7b38' => 'Введите название или описание',
      '6bd44c6bb47a9b2d519d2d44b4f842fb47b985de' => 'Потяните для сортировки',
      '6c42d0c9b3b57dbaed999038e82a3b6f8276e66a' => 'Не удалось скачать zip файл с сайтом.',
      '6ce6c512ea433a7fc5c8841628e7696cd0ff7f2b' => 'Файлы',
      '6d15b2e73a69b50b5f9f30159efc5085f4b3c0f9' => 'Внимание! Показано только %d совпадений из %d из-за лимита вывода совпадений',
      '6da8d3ed49ec59385106720cc3427aed17c1e2fc' => 'пассивная группа',
      '6df5f48b66333bf17e195f87bb9f6b3578b06c75' => 'Импорт/переустановка веб-сайтов, созданных Archivarix.',
      '6dff41f945dd6692c47c72c96be91103f934652c' => 'Скачать файл',
      '6e157c5da4410b7e9de85f5c93026b9176e69064' => 'Создать',
      '6e78c91f5a05fc0d4f1a787d38e3d6fe2f856d46' => 'Выйти',
      '6eef6648406c333a4035cd5e60d0bf2ecf2606d7' => 'Нет',
      '6f035bee5518b7f36d45eb59db46186bcc6ecbd6' => 'Найденные параметры',
      '708c496e21fb2cdacfb2db53e083ac4477671bcf' => 'Отправка через AJAX не удалась. Ваш сервер блокирует XHR POST запросы.',
      '708c5661e7cd9e22032f1462da93b217486cd19a' => 'Не удалось создать шаблон %s.',
      '709a23220f2c3d64d1e1d6d18c4d5280f8d82fca' => 'Название',
      '715e03af319523fb222231bc21b3d6e1bad2332b' => 'API токен не работает.',
      '727eb6a7d89ef397165e0fdded916e27f14e2ab5' => 'Удалено %d битых внутренних изображений в %d страницах.',
      '72cc758d73fa3376dda1be91ffdcc2b7f0ad5dc0' => 'Удалить внешние ссылки',
      '72f9edd84ca59d60644c60e3558b9e75eef140a7' => 'Отсутствующих URL не было обнаружено во время просмотров веб-сайта посетителями.',
      '73313f271cdb5628e3bfd5fcec5c38afa12e1561' => 'Экспорт успешно завершён.',
      '733168e5964b9e36c6194938f3146c858a43db8d' => 'К сожалению, ваш %s не поддерживает %s',
      '736b9062b33638b3eccd593deae38fa35b66c94e' => 'Проверить обновления',
      '73ff76a5555b60349def095cd127976105cd5ede' => 'Несоответствие токена безопасности. Действие не было выполнено. Ваша сессия, вероятно, истекла.',
      '74379c78bc6b9a320a89ecf48430bff5f8d443e9' => 'Укажите домен, только если переключение между поддоменами не работает правильно.',
      '74b1d89d852512ff993721f74dca0a6d886372b8' => 'Выберите файл',
      '74f39697ac328c6325ce068b6aaca626abc0bf63' => 'Перед',
      '74f55cd9587fde96b568ac6ef484cc80dc4ea591' => 'Импортировать найденные шаблоны (%d шт.)',
      '755e2a2dc17e409385410bd2ec9c4b7e4af1708c' => 'Оставьте пустым, чтобы пропустить',
      '757f7dcf4adde137055179cd4076ebaebfba5035' => 'Этот инструмент просканирует все внутренние ссылки, которые ведут на отсутствующие страницы, и удалит ссылки, сохранив анкоры.',
      '766fa564d61cbc8c2421cf1d6faa2b5edf6b38c1' => 'Настройки были обновлены.',
      '76d2facfd0cf8e178a0831cc5a2112ff0e2f4ba9' => 'Импортируйте сайты, созданные Archivarix.',
      '775a0ca54142a052d21787d903648ce12843961d' => 'Импорт успешно выполнен.',
      '77a4a461098cfa9087c5fd57cd7b792c1e60f88f' => 'Рабочий сайт отсутствует или ещё не был установлен.',
      '77c3bde8b2aec5a6123a1ff87673056cef02cbb8' => 'Удалить URL',
      '77dfd2135f4db726c47299bb55be26f7f4525a46' => 'Отмена',
      '782f9c2f8b738d1fe452f88acca884b69bbb9201' => 'Zip файл %s с сайтом скачан.',
      '78fee1435d74666b84850cd5e82c18229351da5d' => 'Пропущен',
      '79b403f57bb09e035ae3515269845b15ce38bd27' => 'Создать страницу',
      '79ba5e1b3f99abfd54ef8d839ba12bd2ac4d79cb' => 'После',
      '7a649eadb66808986988c68b24cf2699921de3a9' => 'Собирать запросы на несуществующие URL',
      '7b66f127481b64c3edbd9d2dfc3e2b6234933964' => 'Управление API',
      '7c714e444bdc074b44f4d016b60dbb9771bcd0aa' => 'Не удалять, только показать список',
      '7e062bc2e0749538e0752d6d7285a547173f8c97' => 'Искать фразу',
      '7ec355bcf678d486100b78460d99addd1f02730c' => 'Этот инструмент корректно переконвертирует в UTF-8 все HTML страницы и другие виды текстовых файлов, которые имеют кодировку, отличную от UTF-8.',
      '7f1149ea3e57d7fd8873a625803539e924862aa7' => 'Перекиньте файл, чтобы загрузить.',
      '7f3ccc1febb7f7edea92457773447db7d54d749b' => 'a или b',
      '7f785c65f336ffceacdd859915a087ac5bef5898' => 'менее m повторений',
      '7fe693ee0012473f9eb011f1b7465ad39c748aa9' => 'Обновлено URL: %s',
      '7ff581f2f41a9d02ca891650b119baf8c5112982' => 'Не удалось обновить %s. Пожалуйста, обновите вручную.',
      '8018e8d266b0475e0d44c2babb0a11cab30a487d' => 'Путь к XML-карте сайта',
      '805b8364dd741b70184f3241017dba0068056c09' => 'Кодировка',
      '8075afb2feb002f2e262c112e1fabc80301a0fd7' => 'Введите имя файла',
      '81321417bb7823c4170f2751a3e500b30891f6ed' => 'Время кэширования статических файлов',
      '815e248364f54621794e8956162c54ef81e7dca0' => 'MIME-типы файлов и количество',
      '816c52fd2bdd94a63cd0944823a6c0aa9384c103' => 'Нет',
      '820fbd5069ca82f732479d138815ed0cb2f2b977' => 'Создание резервных копий отключено в параметрах CMS.',
      '8224205397363d21567025b1cef81761897bba51' => 'Показывает дополнительную вкладку с разницей между изменениями html и других текстовых видов файлов. Не рекомендуется на хостингах с низким ограничением памяти.',
      '8260994675cf2b17546e45f9b86a1030c9e7dec3' => 'Редактировать метаданные при замене',
      '82ce1843b9788eef179f9f7d10020b7b927c1cea' => 'Включая текстовые файлы (js, css, txt, json, xml)',
      '8372b1de3bdee9b33f38b2a6c8975226431f61da' => 'Обновить мета данные в БД',
      '877adb1347852c724a75c2e27144563408c88247' => 'Обновить CMS и Лоадер',
      '87d8295ba0e17029dab1b878bad076a2b66c9865' => 'конец символьного класса',
      '88c84f6272beb9b66d5cc2f48dba0cb9e44cce20' => 'Начальная установка',
      '894b6e68c137b076991f5daeacf293f8d817e819' => 'Список был очищен.',
      '89b86ab0e66f527166d98df92ddbcf5416ed58f6' => 'Язык',
      '8aa57de6dce6ee1c35c6aba7426503281876b258' => 'Изменения',
      '8ba2a5000a57a0b61d89576ae19b3afe8541d0fd' => 'Будет произведён поиск такого же URL, но без строки запроса, если точное совпадение не найдено.',
      '8bdf057f91e76ae328b2a21d35f682daa08a0ec0' => 'Загрузить',
      '8be3c943b1609fffbfc51aad666d0a04adf83c9d' => 'Пароль',
      '8c7e225b0029bd3fbaa615b3f7229f7852346e8b' => 'Редактор .htaccess',
      '8d392f56d616a516ceabb82ed8906418bce4647d' => 'папка',
      '8d79b3baadf67f510dae3a08a4ac7ace5ccbc107' => 'Не удалось поменять Development Mode.',
      '8dfda23b7d200a4970b4e1b5a9b830806ed5febd' => 'Исправить версии в URL',
      '8f045d87c56e98e60f33db5387b12dc84c52e752' => 'Откатить все',
      '8f5502d0b3c1d0293cf2fdd8a9d686a5c3956d32' => 'Внимание! Если вы не выберите флажок, то файл index.php от %s будет перезаписан Лоадером Archivarix.',
      '8fc50427e7c662308023f614aa49ef0404d178cc' => 'В случае если вы напрямую редактировали содержимое файлов на сервере, их размер мог измениться, и эти метаданные необходимо обновить в БД',
      '8fcf4dbe7dd1aa934ce316e2cfc87ed5c8fe366e' => 'Вставить счётчики и аналитику',
      '90ccd6497400b5576aeca1bd94af74aae1e0a250' => 'История',
      '911d1709c67d1903b0b005975b18a8cd52e22bbb' => 'Готово. Файлы сохранены в директорию "export" внутри .content.xxxxxxxx',
      '916ed631e343ddbf3a8451980156e75fdcb5a943' => 'Максимальное кол-во вставок/замен',
      '9270c45b02638b3ef1f7cb51da92019fece40ca2' => 'Внимание! Не закрывайте окно браузера и не останавливайте загрузку, страница сама будет перезагружаться.',
      '9365ba0dab8dc6ef4772cbe407d6fa876b5f1100' => 'Переустановка',
      '93ac3795ebcbffcaacab612d852b6f6c63d805ac' => 'Внимание! Любой файл внутри папки "includes" может содержать исполняемый PHP-код. Не импортируйте файлы из ненадёжных источников.',
      '93f2d9a75e8d5ca6a3634edfafc14cfe192cfc77' => 'ГГГГММДДЧЧММСС',
      '94408e41c12e924b82da7ea6e79e5cb69ac9e042' => 'Поставить пароль',
      '953d399887d06efef77969f0cfd777cf4bd8e2db' => 'Проверьте, что протокол и название домена указаны правильно.',
      '954ab955f2a4e981e2a42027ac3b702c33779a3e' => 'Название шаблона',
      '95802daab3a23990338179f72248350c1434cf39' => 'Вставить',
      '95927924973cc508fe9322672c45e9f0aed2749c' => 'Это действие установит Archivarix Лоадер и заменит содержимое .htaccess.',
      '95af64a15347f41ca2a71bd176cb1c1c1fb3ba76' => 'Символьные классы',
      '97182010da2fecbca4e77abbcc56d13f70db4b41' => 'n и более повторений',
      '977e9091eef6ab48aab0cd727c6559e4436fd7a0' => 'Кэш очищен',
      '97c89a4d6630adeb18fa12ba9976a31413fe293e' => 'Действие',
      '99ffdd6aa8c2fa3ec941793a8c0f69118e0ad591' => 'Не удалось применить файл.',
      '9a881910f6a04bec89e3a313c28db2db00570071' => 'Создать новый URL',
      '9b10914d8b0a097ace7176e8973f5c3dee92bb44' => 'Домен',
      '9df724dc87c580c1ca1fa9e9b3b646774ad561a3' => 'Шаблон %s создан.',
      '9e6674056200da05cbc589f5e08afa5a853c054d' => 'Удалить все найденные URL',
      '9f3f0ac4f066fe0efa014d31c40bf6596b770fec' => 'Код счетчиков успешно обновлен.',
      '9f7e78eecfec1a655cee942bd4232777f248a85c' => 'Не устанавливайте одинаковый пароль для администратора и безопасного режима.',
      '9f976cbbe7c723d6f0cb5a1ae19d29b09edcfa87' => 'Импортировать всё на поддомен',
      '9fb29051f2217270a7b253a39f820310d85a78f0' => 'показать',
      '9fd154488aa03dc59431a3ae03701b1199a3d4dc' => 'Будет включена пагинация для меню с URL, если количество URL для домена/поддомена больше.',
      'a01e64577ff0db2e53cb12d9eb9e5e02efe150cd' => 'Замены в URL',
      'a0566bad0aad8a86dd426aef59841ad0665a389e' => 'Этот функционал является экспериментальным. Вы сможете просматривать все собранные запросы от посетителей на несуществующие URL.',
      'a09d8c6b59d229322ffed097c72d3b60622b7899' => 'Все замены были сохранены в файлы!',
      'a158746242fca036057ad4e599959a0e08812b09' => 'Переконверовать все изображения в формат WebP и оставить их на тех же URL.',
      'a2c2d6377148584c08b4e8db54f7d3e51b170a70' => 'Меньше или равно',
      'a307a637b89fe16e9922778e99bea2ed2e8e496b' => 'Якоря',
      'a3202a5408bbaba26aefed03c861d0f78216098f' => 'Рекомендуемое время: 30 секунд.',
      'a33dd7a918878f5c854c2de3fb7f2f1864704b11' => 'Результаты Поиска & Замены',
      'a38f3421cae0567ad443242737fc16677eebeffe' => 'Больше или равно',
      'a3c3f1687befa727c33d2a038b3005812e935bba' => 'Пожалуйста, загрузите файлы на ваш хостинг или используйте форму ниже для импорта/загрузки существующего сайта.',
      'a3cbb98ddf5ee976bc1c3be5221d66ce3ca2e867' => 'Имя файла',
      'a479c9c34e878d07b4d67a73a48f432ad7dc53c8' => 'Скачать',
      'a47d1b856c4f7c69cd8923ab2d81813dd0780140' => 'Установите значение атрибута rel для всех внешних ссылок. К примеру, поставьте всем внешним ссылкам nofollow.',
      'a5349d62e6c174c229df311a7ebe9c1a78acdc94' => 'Серийный номер',
      'a56d806a8322b148d49928a4ca17ff2345661c5b' => 'Код / Текст',
      'a5cb42ba3a82341e7157037d60a2333bbd606a27' => 'Не обнаружено',
      'a63a7ded5dcc3bd5ef5a72a6a5bab3c608543393' => 'Введите название ZIP файла',
      'a679be854c7554ce548b45a4b1a4985faf3bd7c0' => 'Исправить отсутствующие .ico',
      'a6984d21de8a41c58c8ccfe0e1b24365b3ae49f4' => 'Отдаст прозрачный .ico у всех отсутствующих .ico (напр., favicon.ico) запросов вместо 404 ошибки.',
      'a74342a6db9c49346b878d930b478d1086ee6202' => 'Запустить импорт',
      'a7bdb7c38fb89eabafc46b02f12c3c69c7f355ae' => 'Отправка через AJAX не удалась. Отправляю данные обычным способом',
      'a8c19bc6ca66ba11769e55635168177801f3d71f' => 'Работа с внешними ссылками',
      'a9bbd7e4754b8d9c8fdcbb4c1e4b9d36d691f77e' => 'Файл %s удалён.',
      'a9fb14d1eee22ac545f10a6f6a2a1571b27e1fd0' => 'Добавить новое правило',
      'aa5fd3e1ef424a42c2ef18c838a96e9ce813cb03' => 'Показать страницу 404 для отсутствующих URL',
      'aaa8c9ffef4cfdf69cbf413ea97e31763d995cfb' => 'Настроить значение кастомного домена на %s',
      'accf40c89baa4fa88e6a7ff11e1f805beecafd3f' => 'Создано',
      'ad5a3c1343e1df4ac3797670a6e5f658782251c2' => 'Схема БД',
      'adac69379a626c2436948a4ef1792c7d719ef929' => 'Исходный код',
      'ae4a6182f15b95f6fa9eb5a92ef54ef051c870ba' => 'Исправлено %d URL, произведено %d замен в %d страницах.',
      'ae79ea1e9c6391a9ed83a2e18a031b835feec0c9' => 'До',
      'aea8cc2d34a5a6fa5588f40b54ce3227fed261af' => 'Удалить изображения',
      'aecf67b237dd7625b9066dd5d019cf8f799f6d98' => 'Искать в URL',
      'aede4f5e845a7532cf4c175450e271ff56653dc2' => 'Пользовательские страницы 404',
      'af11224b580a29182004115a1bdd37e0451c73dc' => 'Таймаут в секундах',
      'af8372a0d583f453162212974e4b3989d3117e5b' => 'Требуется версия PHP 5.6 или новее. У вас установлена версия %s.',
      'affb45c46864071c54cffd6729ff4d7828c320c3' => 'Публичный ключ',
      'b04061f569fb944ca26e0b7dec70f983fe725ec2' => 'Перед искомой фразой',
      'b0787109169e14e8ac12893ab3cac5f97f8534ae' => 'Преобразовать в без www',
      'b2370b9b22a9f7a9875296637630c3f1d89810aa' => 'После искомой фразы',
      'b25928c69902557b0ef0a628490a3a1768d7b82f' => 'Всего',
      'b28f718c4e4e154962b6436b1bd8e38865276d89' => 'Не удалось создать файл %s.',
      'b2d60472a24486431b36a967203dc9a6f3658e9b' => 'Развернуть/Свернуть все',
      'b2e5b6b651e61605454a49226a2601c31c6f7f6d' => 'Удалено %d битых внутренних ссылок в %d страницах.',
      'b46eb13a39e643c04d3f6cff0fc63e535100b395' => 'Проверка системы',
      'b4765c6305ebb14866604f33eb5766e5cd0ab29a' => 'Новая страница на основе шаблона %s',
      'b498326e15a92ae0d8b8c9b12f4c1b41ff6d4998' => 'Не равно',
      'b4befb40032f752245b16c2fff7ad96bd9bf4f0a' => 'Введите путь, например, /page.html',
      'b4e0addfb869cafefd393f22342ee5a2fc9363b3' => 'Если импорт содержал файл настроек с паролем, то вы увидите форму входа на следующем клике.',
      'b5083cce31947a6f0ba54d1ac862ffe6759ad751' => 'Выберите ZIP файл',
      'b57727b54f1ed1594681bc46393da68adcdf5aaa' => 'Безопасный режим',
      'b64a91ac509bdeb5d14280c34a680b2274584104' => 'Установка может работать только с SQLite версии 3.7.0 или выше. Ваш pdo_sqlite использует версию %s, которая очень сильно устарела.',
      'b6f62870c502fdff80e4c36b7a46cd4f28d02c6e' => 'Импорт внешних URL-адресов',
      'b7152342a267362add3c0d7f69f720f7a9c76c9e' => 'Размер',
      'b72dd46564a641feef9cd2bfbd294b40b75be9aa' => 'негативный просмотр вперёд',
      'b74182a43fc02b2cf1b932cc980906f2c7302c8f' => 'Исправить отсутствующие изображения',
      'b762993dcde7f686d82bb86cbcfc061cedbc302f' => 'Включая настройки Archivarix CMS',
      'b9738cfa683aaedee1bfba587675645efd20b836' => 'Открыть URL',
      'ba158f83bc4c5f091a43593d5a5605f378ac02ea' => 'Вместо искомой фразы',
      'ba60d4d6efb7e3c7fb76b2ebc547e727be6ad9fd' => 'Этот инструмент создает/редактирует пользовательское правило для вставки счётчиков или аналитики перед закрытым тегом &lt;/head&gt; на всех страницах сайта.',
      'bae7d5be70820ed56467bd9a63744e23b47bd711' => 'Состояние',
      'bb11402077b99ea44e090e51c463989d9f90da22' => 'Включить карту сайта по %s',
      'bb7b29a8438e4fd3efdde59a25ae872da1469564' => 'На вашем сервере не установлено расширение mbstring для PHP. Оно необходимо для правильной работы с кодировками.',
      'bbebc3c8bf955a6cc2768d914fd65e7280f2c00d' => 'Внимание! Рекомендуется использовать веб-сервер Apache в режиме FastCGI.',
      'bbfa773e5a63a5ea58c9b6207e608ca0120e592a' => 'Закрыть',
      'bc0792d8dc81e8aa30b987246a5ce97c40cd6833' => 'Система',
      'bc51cbfb7c0ec3c607b4c0ba44d73e9a9c4988e4' => 'Запустить импорт',
      'bd604d99e75e45d38bc7ac8fc714cde0097d901f' => 'Отладка',
      'bd9f6b614417eefea1df17af14201bd62928ea1b' => 'Проверит и обновит Archivarix CMS и Archivarix Лоадер на последние версии.',
      'bda446ac15be5dc3667ae9a0333954ba0cf96089' => 'Просмотр не доступен, потому что URL отключен или установлено перенаправление.',
      'bdfdb710e7ec26a3434f83e41fbf298e415e19ad' => 'Импортировать все в папку',
      'be763e9aba2af6b8baabada85d5e26c043c93fed' => 'Статистика',
      'bf9b1f60ed85828de6bc1b83f62234bca1d1caa8' => 'Преобразовать в www/без www',
      'c043160a89ba0acd714977735d49455f662b1c79' => 'Очистить всё',
      'c0ae8f6ea84111498894729659051ce9713aab42' => 'Сохранено',
      'c382b252a66527e71e92503d147fd4910f43a65c' => 'Мы вынуждены использовать старый тип файла .db, потому что у вас устаревшая версия SQLite. Минимально рекомендуемая версия – 3.7.0',
      'c3cd636a585b20c40ac2df5ffb403e83cb2eef51' => 'Действия',
      'c3de90e0ef6f6bc2dd953b732963c2a81a4704de' => 'цифры между 0 и 7',
      'c44f9f7542d1b44a66b32bd469e2fce35181b3f4' => 'Нельзя создавать или редактировать кастомные файлы с php кодом в безопасном режиме.',
      'c5c3e292466e0973825671bff724302a7d38f31b' => 'Преобразовать сайт в www/без www версию.',
      'c622526dea600dbcd0334b6b9cb31b717df8db36' => 'Склеить URL со всех поддоменов в основной домен',
      'c6c2d84131087f8fca784705f99eb1618d92fc7b' => 'Показать настройки',
      'c73af7bd31f0caab06d3e13c32bdf1f64103d49d' => 'Учитывать регистр',
      'c7f73bb54d928922c3838bb789ee9fb8a5b1eb37' => 'Настройки',
      'c8068d5c84370ad867875d1976715919d79af308' => 'Удалить выбранное',
      'c983a1551dcbdb6f0ce43637e89a8726577d4f4b' => 'Хост',
      'c9b72b2b62fdd73b14e70bd84bd10d2eb0ac32e8' => 'Включить sitemap.xml',
      'c9cb9f7d8ff5e45278e00cb19e01ea5714bedd91' => 'Доступ успешно сохранён.',
      'caf1bcd8455591a46e4e531cc899345638b90ed3' => 'Введите публичный ключ в PEM формате',
      'cb52e64b52c3fbbed45d0c47d5a0b1bd3a6aeedb' => 'После установки',
      'cd15098ba1fe5a88a676b2d74f78b384e345ee00' => 'Приоритет имеет более новый URL',
      'cd31054ee03fd750a7a33afc4ce3158813c3c6d3' => 'Инструмент импорта',
      'cd42ec84472c6d4dd7ffa6aaea45919db54f373a' => 'Ограничить меню списка URL',
      'cd78c6b6e1d98ad97b8969d4f75174b14a7e0a7e' => 'Интегрировать с найденной установкой %s',
      'cda863cb576cec0c4f2dd1c285e7f2ae7b499716' => 'Редактировать страницу в новом окне',
      'cdfc76a8a310b7e8a9f6d61d4031ccd974b4fe97' => 'Регулярное выражение',
      'cf1c85adba548e8d681255278976584a7e4a44de' => 'Позиция',
      'cf678cab87dc1f7d1b95b964f15375e088461679' => 'Ключ API',
      'cf94db5484d48935accd24096009ccfce284d9d8' => 'Заменить, если такой URL уже существует и версия замены новее',
      'cfb8ca84ce537392a42996cb1b9ccf008365bb6e' => 'Соответствовать URL путям (рег. выражение)',
      'cfbfe6b85601b668e99f90112aea92e1b6dc414c' => 'Исправить robots.txt',
      'cfdaca6c033469e1cb133c7fbdec7cea75135b0a' => 'Очистить кэш Cloudflare через API доступ.',
      'cffa7448fe9ec2af2c333334fe5741ac765fae94' => 'Список замен',
      'd06d55570938d12f87db3bf2b48caa9de22d9c67' => 'Права',
      'd0b7bad4e32e9972f062a33db3117257ffd39d2c' => 'Отдаст пустой .js у всех отсутствующих .js запросов вместо 404 ошибки.',
      'd17d0e4da471521c339ceae10e06c8f55de334f5' => 'Удалить текущий пароль для безопасного режима',
      'd1d98f0c7c37f0240459c80663172a9f733a29d8' => 'буквы между a и h (включая)',
      'd2002e42ef596fcbe9eea872f6ee911e82709e04' => 'Введите название или понятное короткое описание.',
      'd2025930e705a2c2bfdebd748328c3728405ab46' => 'Установить отсутствующий Лоадер',
      'd2e093f54b92d4c5044cc48c0f19c348c9e55bd8' => 'Статическая версия',
      'd3f4cb898fbe0c7a7ac4f721438c4c26ad1a2513' => 'Переименовать',
      'd42ec8c41ede991363e3c5f0bdd1b5f73c156509' => 'Версия SQLite',
      'd5d00ba35ed04b1ce3a8d1a627d22873d8cf2076' => 'негативный просмотр назад',
      'd5e5d1442e2386868faaf2293b19d0e686b71457' => 'Сканировать все внутренние ссылки и для определения уровня вложенности и орфан-страниц',
      'd6fbc9d2bdd580e18ed0bc5805dc26db323d6f5f' => 'Импорт',
      'd74197fddea10bc84eb4fb5dbfaa231e7c278a3f' => 'Откатить',
      'd745dd42cc0d7cdb7ad6eb4e853e60f0e3892463' => 'Режим эксперта',
      'd78c8e1dded0f8389dd7d171fd57dacc4c87583e' => 'Лишние найденные файлы или папки: %s',
      'd793b42c593a94dc19ca8aac2fc9a07d8fbf74d9' => 'Импортировать настройки Archivarix CMS',
      'd7de09cf5a239572791200ccb26dd10549180854' => 'API токен удалён.',
      'd7decf1aa22b02ae8abf9a96849ee423eee838e4' => 'Фильтрация',
      'd7e6ddafb44cee1f1850ccb1ab7e9b1d34e48dd9' => 'Готово. Метаданные были обновлены у %d URL',
      'd8cdb573350de78596e4852bc9cacfc94e8d17ed' => 'Клонировать',
      'd9178847aacd69ad14c6ebeba61a39c9f5d53193' => 'Выключить Dev Mode',
      'd9ca1471a1c5c73c1eaf333a7d893c103077a28a' => 'Дополнительные параметры',
      'da7a68734367828e30b94927f4c2b43ed2c0f652' => 'выключено',
      'dab4221e47bcc5a632ea681f8d16634c94712cdf' => 'Добавить метатег viewport во все страницы сайта для адаптации сайта к показу на мобильных устройствах.',
      'db4ce42c408ac30885e7dec1de1e7b80633e294e' => 'Включена пагинация. Вы можете повысить лимит в Настройках, но есть риск, что может не хватить оперативной памяти. Текущее значение лимита на страницу: %s.',
      'db5114ca2d9c4c0898d0f1a5b8993c50f8425cf1' => 'Импортировать файлы из кастомной папки \'includes\'',
      'dca4d2faa4a83c84a6fe8f64741c5762c82a2c9f' => 'Оставьте пустым, чтобы создать пустой файл.',
      'dd9ab0c8893faa461e9b42d6ad01a26437994056' => 'Вырезать параметры из URL для сохранения оригинальных имен файлов',
      'dde4069f73a2562217989480ad01f4397bf6ea8b' => 'Заменить на',
      'de1e4faaa9758db564ba458c3956824881efcd9c' => 'Конвертировать в',
      'de5edb39c5eecd335d15d47e0f0283d6cb3556ed' => 'Добавить viewport',
      'df0e298e356261439795d0d435ce0ce576604c65' => 'Глубина',
      'df174a3f2faa31814e06540acda7af8825403fac' => 'Включен',
      'dfc6f11bdbb4141b40ffaa80f861721129f00b21' => 'Очистить до точки отката',
      'e0070730b76d54b82816c0f794cd4ed5482e5548' => 'Файл  %s успешно обновлён.',
      'e0c06bb1850c31f5300b62cece0773e5703ae2f6' => 'Подсказки по Регулярным выражениям',
      'e185048ce3c07f61069845d16a69507a66ba5ea9' => 'Проверить снова',
      'e22b9edc9075c8988905cb2d0695597393888614' => 'Исправить отсутствующие .js',
      'e2b29eeb4779e3bfe1ca2050f71d19e50e412106' => 'Не заполняйте для обычного импорта.',
      'e2bcda84facb3954e32539ae66300758b6d834b2' => 'Готово.',
      'e33031092cd799b9deadc1b19d161903eaabf37f' => 'Создать шаблон',
      'e331b73b78ea3c429b4bab4d543159b28c478048' => 'Файлы необходимо поместить в папку .content.xxxxxx/includes/',
      'e40277013674af698314c40699779675a533398a' => 'Шаблон %s уже существует.',
      'e4b977fa00e49c5f9013250f6c3263ae7dbc1041' => 'Включить этот URL',
      'e62277c8efc2ec32f5c1d1118a2dc167b3d2c230' => 'Импортировать внешние URL, чтобы сделать их локальными по тем же путям.',
      'e63451d3cf90040c075dc1fe95aa1f7d7528c37f' => 'Идёт обработка',
      'e65b54cefcf5dabe105e76b511de7c73934c71bc' => 'Запустить установку',
      'e6aa128db823dc4e72fde6cbdbd2a5c5a80666d0' => 'Экспорт сайта в статическую структуру',
      'e7527d2bece512abacd053787f9c9687aad11ed9' => 'Предыдущее выполнение',
      'e75c66b5260cd7f2f46d73f4b21bc3e8979bee3f' => 'Показывать разницу между изменениями',
      'e7d3c0f60549bbfefa4ea32f77362cbaaf440ff8' => 'Редактировать .htaccess',
      'e7fad6859f4a7fd14527a07ec897c96529a127a5' => 'Открыть сайт в robots.txt',
      'e905a8a9e9efa4f4cfe4638b1181dc0095510fcf' => 'начало группы',
      'e94c616c841cb7a1ecf12fe766063b26522cc351' => 'Правило фильтрации',
      'e963907dac5cd5c017869b4c96c18021c9bd058b' => 'Удалить',
      'ea53c1abf00601076bc58d4cb445663477bfa061' => 'Откатить к точке отката',
      'ea6a33a4825bd2e4faba25885544b6e03750a75e' => 'Режим Лоадера',
      'ebd40bcf70b3b2b2a06d99dd6d0ec0fadff22a36' => 'Обновление системы',
      'ec7b4ab40a2eee0121756336677e4d6228c4666d' => 'посещений',
      'ecd961a12da0644bca14ded0d6cb75300d7c7704' => 'Дополнительно проверять файлы на этом сервере',
      'ed9a28ee0c0658dac15cebac8d3140827c9d8b35' => 'Будьте осторожны, т.к. ваш IP может меняться, и вы запретите сами себя. Вводите IP адреса или CIDR через запятую.',
      'ee603353f47236cce238c7d033d74ac026b128dc' => 'Переключите режим, если вам необходимо запустить сайт в режиме совместимости со сторонними системами (напр., Wordpress)',
      'ee7681a584fd9fc02158d142378f5f0d1adad607' => 'откатить',
      'ef02cf68168a5bfe04b81b8c5203cc06a794d1c2' => 'размер в байтах',
      'efc007a393f66cdb14d57d385822a3d9e36ef873' => 'Сохранить',
      'efebe4f4e0e2007b5ed535ce98da6cffd900b62d' => 'Искать во всех',
      'f03c0a59c59454eea25ccc9b83f19b79a7c85f45' => 'назад',
      'f0bc10c54ea88ef3413aac7e88082f0b110d415e' => 'Удалить историю до',
      'f17d19a0182517621f0fbd1b92c5cc1da2cb8333' => 'Отсутствующие URL',
      'f180908bf73f46b6505e0291f1c5a4a15b72baa5' => 'ленивые ноль или одно повторение',
      'f25779b94ebcabf67ae06ddd19a4b31d580b4d5b' => 'Укажите, если вы запускаете сайт на поддомене его оригинального домена или для работы поддоменов у другого домена.',
      'f25b700ed9f092123a43acb205a6869342cf9dd6' => 'Шаблоны',
      'f32e94e2ccd7503165b196b4ab04645349aea520' => 'ровно n повторений',
      'f347090e19994c56262b0a99086cab6f05ffdb6d' => 'Некоторые сайты используют контроль версий, добавляя ?ver=x.x. в конец URL. Этот инструмент исправит такие URL. Также он исправит вхождения PHPSESSID.',
      'f39b1460d567e7e207b75c584cb4286dfa093cee' => 'однократное подвыражение',
      'f3e4fadb9e370a1e2c0c622c01fc8c77daf93a2c' => 'Экспортировать',
      'f45f48a2a782f26a0072b3890ac6e79644d0f9f9' => 'Отдаст пустой .css у всех отсутствующих .css запросов вместо 404 ошибки.',
      'f699f295e5ae4ac633cfa18437fed38d028b3fdb' => 'Параметр',
      'f6fdbe48dc54dd86f63097a03bd24094dedd713a' => 'Удалить',
      'f7f3d06d1eba5a68cb274c1bdaeb611637af0d0b' => 'ленивые ноль или более',
      'f9738f9bf1c55917a34ba51f1c45000f3f1b2d29' => 'Публичный ключ удалён.',
      'f9d640a0935ae2aa530c8e9ed1b8daca3bc0caba' => 'Для работы этого инструмента необходимо установить следующие PHP расширения: %s.',
      'fa2e65fb1e64e8a5767ae81349f0d51a58f1279d' => 'Шаблон %s удалён.',
      'faa12cf44058c7e178e96c4c2259a5504baa97aa' => 'Не удалось очистить кэш.',
      'fab25430bc96f3a77a506a39f261656a627fbeca' => 'Отметить все',
      'fb135163991373afca2a8c72d43bfd2b6c0cf45a' => 'Введите значения в поля для обновления метаданных при действии замены. Оставьте пустым, чтобы оставить без изменений.',
      'fb91e24fa52d8d2b32937bf04d843f730319a902' => 'Обновить',
      'fcf937319f333e7df4ec12406025be63bde6549b' => 'Перенаправления',
      'fd1ef0e7555eda7d3ab305e11dd29d2a572a1878' => 'Создана новая точка отката.',
      'fd7042e0b9088ec45a8291df2fd9e293f4c9ec94' => 'Текущий пароль для безопасного режима зашит в исходный код. Настройки пароля ниже не повлияют на зашитый пароль.',
      'fde483e8914237363b867a03b514b0eeb00ae629' => 'Время файла',
      'ff3de3f6ed46c1d2fb533d3cc096c6c922b7dd18' => 'Только этот сайт, для отсутствующих URL код 404',
      'ffcb02a71bd51aedd1dd3699a690a11cce14e19d' => 'или выбрать',
    ],
  ];

  if ( isset( $_SESSION['localization'][$languageCode] ) ) {
    $localization[$languageCode] = $_SESSION['localization'][$languageCode];
  }

  if ( isset( $localization[$languageCode] ) ) {
    return $localization[$languageCode];
  }
}

function matchCidr( $ip, $cidr )
{
  if ( strpos( $cidr, '/' ) == false ) {
    $cidr .= '/32';
  }
  list( $cidr, $netmask ) = explode( '/', $cidr, 2 );
  $range_decimal    = ip2long( $cidr );
  $ip_decimal       = ip2long( $ip );
  $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
  $netmask_decimal  = ~$wildcard_decimal;
  return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}

function newPDO()
{
  global $dsn;
  return new PDO( $dsn );
}

function packLoaderSettings()
{
  global $sourcePath;
  $customFilesSettings = [];
  $customFiles         = getCustomFiles();
  if ( !empty( $customFiles ) ) foreach ( $customFiles as $customFile ) {
    if ( !$customFile['is_dir'] ) {
      $customFilesSettings['ARCHIVARIX_CUSTOM_FILES'][] = [
        'filename' => $customFile['filename'],
        'content'  => base64_encode( file_get_contents( $sourcePath . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $customFile['filename'] ) ),
      ];
    }
  }
  return json_encode( array_merge( loadLoaderSettings(), $customFilesSettings ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
}

function parseComparisonString( $string )
{
  preg_match( '~^(>|<|=|>=|<=|==|!=)([\d]+)$~', $string, $match );
  if ( !$match ) return ['', ''];
  return [$match[1], $match[2]];
}

function pathExists( $hostname, $path )
{
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "SELECT rowid FROM structure WHERE hostname = :hostname AND request_uri = :path LIMIT 1" );
  $stmt->execute( [
    'hostname' => $hostname,
    'path'     => encodePath( $path ),
  ] );
  $stmt->execute();
  $id = $stmt->fetchColumn();
  if ( $id ) {
    return $id;
  }
}

function printArrayHuman( $array, $return = false )
{
  array_walk_recursive( $array, "escapeArrayValues" );
  if ( $return ) {
    return $array;
  } else {
    print_r( $array );
  }
}

function printArrayList( $tree, $pathUrls, $dir = '' )
{
  global $urlsDisplayed;
  echo '<ul class="d-none">';

  foreach ( $tree as $k => $v ) {
    if ( is_array( $v ) ) {
      echo '<li data-jstree=\'{"icon":"far fa-folder","disabled":true,"order":1}\'>/' . htmlspecialchars( rawurldecode( substr( $k, 1 ) ), ENT_IGNORE );
      printArrayList( $v, $pathUrls, $dir . '/' . substr( $k, 1 ) );
      echo '</li>';
      continue;
    } else {
      if ( $v == '' || $v == '/' ) {
        $path = $dir . $v;
      } else {
        $path = $dir . '/' . $v;
      }

      if ( isset( $pathUrls[$path] ) ) {
        echo getTreeLi( $pathUrls[$path] ) . '</li>';
      } else {
        echo '<li data-jstree=\'{"icon":"far fa-folder","disabled":true,"order":1}\'>/' . htmlspecialchars( rawurldecode( $v ), ENT_IGNORE ) . '/</li>';
      }
    }
    $urlsDisplayed++;
  }

  echo '</ul>';
}

function printFormFields( $array )
{
  if ( empty( $array ) ) return;
  $flattenArray = flattenArray( $array );
  foreach ( $flattenArray as $key => $val ) {
    $keys = explode( ',', $key );
    if ( count( $keys ) > 1 ) {
      $name = $keys[0] . '[';
      unset( $keys[0] );
      $name .= implode( '][', $keys ) . ']';

    } else {
      $name = $keys[0];
    }
    echo '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars( $val ) . '">';
  }
}

function printStats( $stats )
{
// [TODO] graphs could be called separately
// graph mimetype and count
// graph mimetype and size
// graph hostnames and count
// graph local redirects

  //if ( empty( $stats['mimestats'] ) ) return;
  ?>
  <div class="row justify-content-center">

    <?php
    // mime chart count
    unset( $mime );
    $mime[]         = ['mimetype', 'count'];
    $mimeTotalCount = $stats['filescount'];
    foreach ( $stats['mimestats'] as $mimeItem ) {
      $mime[] = [$mimeItem['mimetype'], (int)$mimeItem['count']];
    }
    ?>
    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow mb-3">
        <div class="card-header bg-dark text-light">
          <i class="fas fa-chart-pie fa-fw"></i> <?=L( 'MIME-types of files and quantity' )?>
        </div>
        <div class="card-body p-1 justify-content-center">
          <div id="div_mimestats_count_<?=$stats['id']?>" class="p-0  m-0" style="min-height:380px;"></div>
          <script type="text/javascript">
            google.charts.load("current", {
              packages: ["corechart"],
              language: '<?=getLang()?>'
            });
            google.charts.setOnLoadCallback(drawStatsMimeCount_<?=$stats['id']?>);

            function drawStatsMimeCount_<?=$stats['id']?>() {
              var mimew = document.getElementById('div_mimestats_count_<?=$stats['id']?>').offsetWidth;
              var data = google.visualization.arrayToDataTable(<?=json_encode( $mime )?>);
              var options = {
                pieHole: 0.4,
                chartArea: {
                  left: 10,
                  right: 10,
                  top: 10,
                  bottom: 10,
                  width: '100%',
                  height: '350'
                },
                legend: {position: 'labeled'},
                pieSliceText: 'value'
              };
              if (mimew < 500) {
                options.legend.position = 'none';
              }
              var chart_<?=$stats['id']?> = new google.visualization.PieChart(document.getElementById('div_mimestats_count_<?=$stats['id']?>'));
              chart_<?=$stats['id']?>.draw(data, options);
            }
          </script>

        </div>
        <div class="card-footer bg-white">
          <?=L( 'Total files' )?>: <?=number_format( $mimeTotalCount )?>
        </div>
      </div>
    </div>

    <?php
    // mime chart size
    unset( $mime );
    $mime[]        = ['mimetype', 'size'];
    $mimeTotalSize = $stats['filessize'];
    foreach ( $stats['mimestats'] as $mimeItem ) {
      $mime[] = [$mimeItem['mimetype'], ['v' => (int)$mimeItem['size'], 'f' => getHumanSize( $mimeItem['size'] )]];
    }
    ?>
    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow mb-3">
        <div class="card-header bg-dark text-light">
          <i class="fas fa-chart-pie fa-fw"></i> <?=L( 'MIME-types of files and sizes' )?>
        </div>
        <div class="card-body p-1">
          <div id="div_mimestats_size_<?=$stats['id']?>" class="p-0 m-0" style="min-height:380px;"></div>
          <script type="text/javascript">
            google.charts.load("current", {
              packages: ["corechart"],
              language: '<?=getLang()?>'
            });
            google.charts.setOnLoadCallback(drawStatsMimeSize_<?=$stats['id']?>);

            function drawStatsMimeSize_<?=$stats['id']?>() {
              var mimew = document.getElementById('div_mimestats_size_<?=$stats['id']?>').offsetWidth;
              var data = google.visualization.arrayToDataTable(<?=json_encode( $mime )?>);
              var options = {
                pieHole: 0.4,
                chartArea: {
                  left: 10,
                  right: 10,
                  top: 10,
                  bottom: 10,
                  width: '100%',
                  height: '350'
                },
                legend: {position: 'labeled'},
                pieSliceText: 'value'
              };
              if (mimew < 500) {
                options.legend.position = 'none';
              }
              var chart = new google.visualization.PieChart(document.getElementById('div_mimestats_size_<?=$stats['id']?>'));
              chart.draw(data, options);
            }
          </script>
        </div>
        <div class="card-footer bg-white">
          <?=L( 'Total' )?>: <?=L( 'approx.' )?> <?=getHumanSize( $mimeTotalSize )?>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card border-0 shadow mb-3">
        <div class="card-header bg-dark text-light">
          <i class="fas fa-chart-pie fa-fw"></i> <?=L( 'Hostnames and URLs count/size' )?>
        </div>
        <div class="card-body p-0 justify-content-center small">
          <table class="table table-responsive table-hover m-0">
            <thead>
            <tr class="table-secondary">
              <th scope="col" class="w-100 text-nowrap"><?=L( 'Hostnames' )?></th>
              <th scope="col" class="text-center text-nowrap"><?=L( 'Files' )?></th>
              <th scope="col" class="text-center text-nowrap"><?=L( 'Size' )?></th>
              <th scope="col" class="text-center text-nowrap"><?=L( 'Redirects' )?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            $hostnamesTotalCount = 0;
            foreach ( $stats['hostnames'] as $hostname ) {
              $hostnamesTotalCount++; ?>
              <tr>
                <th scope="row"><?=convertIdnToUtf8( $hostname['hostname'] )?></th>
                <td class="text-center"><?=number_format( $hostname['count'] - $hostname['redirects'], 0 )?></td>
                <td class="text-center"><?=getHumanSize( $hostname['size'] )?></td>
                <td class="text-center"><?=number_format( $hostname['redirects'], 0 )?></td>
              </tr>
              <?php
              if ( $hostnamesTotalCount == 100 ) break;
            } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  <?php
}

function purgeCacheCloudflare( $urls = false )
{
  $acmsCloudflare = readMetaData( 'acms_cloudflare' );
  $path           = '/client/v4/zones/' . $acmsCloudflare['data']['zone_id'] . '/purge_cache';
  $data           = '{"purge_everything":true}';
  $response       = sendRequestCloudflare( 'POST', $path, $data );
  if ( !empty( $response['success'] ) ) {
    return true;
  }
}

function putLoader( $path, $mode )
{
  $loaderFile = tempnam( getTempDirectory(), 'archivarix.' );
  if ( $mode == 'integration' ) {
    downloadFile( 'https://archivarix.com/download/archivarix.loader.integration.zip' . '?uid=' . sha1( __DIR__ ), $loaderFile );
    $zip = new ZipArchive();
    $zip->open( $loaderFile );
    if ( file_exists( __DIR__ . DIRECTORY_SEPARATOR . '.htaccess' ) ) {
      $pathHtaccess        = __DIR__ . DIRECTORY_SEPARATOR . '.htaccess';
      $currentHtaccessData = file_get_contents( $pathHtaccess );
      file_put_contents( $pathHtaccess, $zip->getFromName( '.htaccess' ) . "\n\n# BACKUP BELOW\n" . $currentHtaccessData );
    } else {
      $zip->extractTo( $path, '.htaccess' );
    }
    $zip->extractTo( $path, 'archivarix.php' );
    $zip->close();
  } else {
    downloadFile( 'https://archivarix.com/download/archivarix.loader.install.zip' . '?uid=' . sha1( __DIR__ ), $loaderFile );
    $zip = new ZipArchive();
    $zip->open( $loaderFile );
    $zip->extractTo( $path );
    $zip->close();
  }
  unlink( $loaderFile );
}

function recoverBackup( $params, $taskOffset = 0 )
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $ACMS;
  $pdo = newPDO();

  $stats = array_merge( ['pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  if ( $taskOffset == 0 ) $taskOffset = 100000000;

  if ( isset( $params['all'] ) ) {
    if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdo->query( "SELECT COUNT(1) FROM backup" )->fetchColumn() );
    $stmt = $pdo->prepare( "SELECT rowid, * FROM backup WHERE rowid <= :taskOffset ORDER BY rowid DESC" );
    $stmt->execute( ['taskOffset' => $taskOffset] );
    while ( $backup = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
      recoverFile( $backup );
      $stats['pages']++;
      $stats['processed']++;

      if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
        $stats['time']        += microtime( true ) - ACMS_START_TIME;
        $taskStats            = serialize( $stats );
        $taskIncomplete       = true;
        $taskIncompleteOffset = $backup['rowid'];
        return $stats;
      }
    }
    $stmt_remove_all = $pdo->prepare( "DELETE FROM backup" );
    $stmt_remove_all->execute();

    $stats['time'] += microtime( true ) - ACMS_START_TIME;
    $taskStats     = serialize( $stats );
    return true;
  }

  if ( !empty( $params['breakpoint'] ) ) {
    $pdo2 = newPDO();
    if ( empty( $stats['total'] ) ) {
      $stmt = $pdo->prepare( "SELECT COUNT(1) FROM backup WHERE rowid > :breakpoint" );
      $stmt->execute( ['breakpoint' => $params['breakpoint']] );
      $stats['total'] = $stmt->fetchColumn();
    }
    $stmt = $pdo->prepare( "SELECT rowid, * FROM backup WHERE rowid > :breakpoint AND rowid <= :taskOffset ORDER BY rowid DESC" );
    $stmt->execute( ['breakpoint' => $params['breakpoint'], 'taskOffset' => $taskOffset] );
    while ( $backup = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
      recoverFile( $backup );
      $stmt_delete_backup = $pdo2->prepare( "DELETE FROM backup WHERE rowid = :backupid" );
      $stmt_delete_backup->execute( ['backupid' => $backup['rowid']] );
      $stats['pages']++;
      $stats['processed']++;

      if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
        $stats['time']        += microtime( true ) - ACMS_START_TIME;
        $taskStats            = serialize( $stats );
        $taskIncomplete       = true;
        $taskIncompleteOffset = $backup['rowid'];
        return $stats;
      }
    }

    $stats['time'] += microtime( true ) - ACMS_START_TIME;
    $taskStats     = serialize( $stats );
    return true;
  }

  if ( !empty( $params['id'] ) && !empty( $params['rowid'] ) ) {
    // roll back action for a single url
    $pdo2 = newPDO();
    if ( empty( $stats['total'] ) ) {
      $stmt = $pdo->prepare( "SELECT COUNT(1) FROM backup WHERE id = :id AND rowid > :rowid" );
      $stmt->execute( [
        'rowid' => $params['rowid'],
        'id'    => $params['id'],
      ] );
      $stats['total'] = $stmt->fetchColumn();
    }
    $stmt = $pdo->prepare( "SELECT rowid, * FROM backup WHERE id = :id AND rowid >= :rowid AND rowid <= :taskOffset ORDER BY rowid DESC" );
    $stmt->execute( [
      'rowid'      => $params['rowid'],
      'id'         => $params['id'],
      'taskOffset' => $taskOffset,
    ] );
    while ( $backup = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
      recoverFile( $backup );
      $stmt_delete_backup = $pdo2->prepare( "DELETE FROM backup WHERE rowid = :backupid" );
      $stmt_delete_backup->execute( ['backupid' => $backup['rowid']] );
      $stats['pages']++;
      $stats['processed']++;

      if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
        $stats['time']        += microtime( true ) - ACMS_START_TIME;
        $taskStats            = serialize( $stats );
        $taskIncomplete       = true;
        $taskIncompleteOffset = $backup['rowid'];
        return $stats;
      }
    }

    $stats['time'] += microtime( true ) - ACMS_START_TIME;
    $taskStats     = serialize( $stats );
    return true;
  }

  $backups = explode( ',', $params['backups'] );
  rsort( $backups );
  foreach ( $backups as $backupId ) {
    $stmt = $pdo->prepare( "SELECT rowid, * FROM backup WHERE rowid = :rowid" );
    $stmt->execute( ['rowid' => $backupId] );
    while ( $backup = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
      recoverFile( $backup );
    }
    $stmt = $pdo->prepare( "DELETE FROM backup WHERE rowid = :rowid" );
    $stmt->execute( ['rowid' => $backupId] );
  }

  responseAjax();
}

function readMetaData( $name )
{
  createTable( 'meta' );
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "SELECT * FROM meta WHERE name = :name" );
  $stmt->bindParam( 'name', $name, PDO::PARAM_STR );
  $stmt->execute();

  $result = $stmt->fetch( PDO::FETCH_ASSOC );
  if ( isset( $result['data'] ) ) {
    $result['data'] = json_decode( $result['data'], true );
  }
  return $result;
}

function recoverFile( $backup )
{
  $metaData = json_decode( $backup['settings'], true );

  global $sourcePath;
  $pdo = newPDO();

  switch ( $backup['action'] ) :
    case 'breakpoint' :
      break;
    case 'remove' :
      $stmt_backup = $pdo->prepare( "INSERT INTO structure (rowid, url, protocol, hostname, request_uri, folder, filename, mimetype, charset, filesize, filetime, url_original, enabled, redirect) VALUES (:rowid, :url, :protocol, :hostname, :request_uri, :folder, :filename, :mimetype, :charset, :filesize, :filetime, :url_original, :enabled, :redirect)" );
      $stmt_backup->execute( [
        'url'          => $metaData['url'],
        'protocol'     => $metaData['protocol'],
        'hostname'     => $metaData['hostname'],
        'request_uri'  => $metaData['request_uri'],
        'folder'       => $metaData['folder'],
        'filename'     => $metaData['filename'],
        'mimetype'     => $metaData['mimetype'],
        'charset'      => $metaData['charset'],
        'filesize'     => $metaData['filesize'],
        'filetime'     => $metaData['filetime'],
        'url_original' => $metaData['url_original'],
        'enabled'      => $metaData['enabled'],
        'redirect'     => $metaData['redirect'],
        'rowid'        => $metaData['rowid'],
      ] );
      rename( $sourcePath . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $backup['filename'], $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'] );
      break;
    case 'create' :
      $metaDataCurrent = getMetaData( $metaData['rowid'] );
      unlink( $sourcePath . DIRECTORY_SEPARATOR . $metaDataCurrent['folder'] . DIRECTORY_SEPARATOR . $metaDataCurrent['filename'] );
      $stmt_backup = $pdo->prepare( "DELETE FROM structure WHERE rowid = :rowid" );
      $stmt_backup->execute( [
        'rowid' => $metaData['rowid'],
      ] );
      break;
    default :
      $metaDataCurrent = getMetaData( $metaData['rowid'] );
      unlink( $sourcePath . DIRECTORY_SEPARATOR . $metaDataCurrent['folder'] . DIRECTORY_SEPARATOR . $metaDataCurrent['filename'] );
      $stmt_backup = $pdo->prepare( "UPDATE structure SET url = :url, protocol = :protocol, hostname = :hostname, request_uri = :request_uri, folder = :folder, filename = :filename, mimetype = :mimetype, charset = :charset, filesize = :filesize, filetime = :filetime, url_original = :url_original, enabled = :enabled, redirect = :redirect WHERE rowid = :rowid" );
      $stmt_backup->execute( [
        'url'          => $metaData['url'],
        'protocol'     => $metaData['protocol'],
        'hostname'     => $metaData['hostname'],
        'request_uri'  => $metaData['request_uri'],
        'folder'       => $metaData['folder'],
        'filename'     => $metaData['filename'],
        'mimetype'     => $metaData['mimetype'],
        'charset'      => $metaData['charset'],
        'filesize'     => $metaData['filesize'],
        'filetime'     => $metaData['filetime'],
        'url_original' => $metaData['url_original'],
        'enabled'      => $metaData['enabled'],
        'redirect'     => $metaData['redirect'],
        'rowid'        => $metaData['rowid'],
      ] );
      rename( $sourcePath . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $backup['filename'], $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'] );
  endswitch;
}

function removeApiKey()
{
  global $ACMS;
  unset( $ACMS['ACMS_PUBLIC_KEY'] );
  setAcmsSettings( [] );
  loadAcmsSettings();
}

function removeBrokenImages( $taskOffset = 0 )
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $sourcePath;
  global $uuidSettings;
  global $ACMS;

  $stats = array_merge( ['images' => 0, 'pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  if ( function_exists( 'libxml_use_internal_errors' ) ) libxml_use_internal_errors( true );
  $pdo = newPDO();
  if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdo->query( "SELECT COUNT(1) FROM structure WHERE mimetype = 'text/html'" )->fetchColumn() );
  $stmt = $pdo->prepare( "SELECT rowid, * FROM structure WHERE mimetype = 'text/html' AND rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );

  $urlsArray = array_fill_keys( array_keys( sqlReindex( sqlGetLines( "SELECT url FROM structure" ), 'url' ) ), 0 );

  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $updatedImages = 0;
    $file          = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
    if ( !is_file( $file ) ) continue;
    $html = file_get_contents( $file );
    if ( !strlen( $html ) ) continue;
    $html = convertEncoding( convertHtmlEncoding( $html, 'utf-8', $url['charset'] ), 'html-entities', 'utf-8' );
    unset( $dom );
    $dom                      = new DOMDocument();
    $dom->formatOutput        = true;
    $dom->documentURI         = $url['url'];
    $dom->strictErrorChecking = false;
    $dom->encoding            = 'utf-8';
    if ( defined( 'LIBXML_HTML_NOIMPLIED' ) && defined( 'LIBXML_HTML_NODEFDTD' ) ) {
      $dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    } else {
      $dom->loadHTML( $html );
    }
    $imgTags = $dom->getElementsByTagName( 'img' );
    for ( $n = $imgTags->length - 1; $n >= 0; --$n ) {
      $hrefAttribute = $imgTags->item( $n )->getAttribute( 'src' );
      $hrefAbsolute  = rawurldecode( getAbsolutePath( $url['url'], $hrefAttribute ) );
      if ( !empty( $_POST['check_files'] ) ) {
        $hrefFilePath = parse_url( $hrefAbsolute, PHP_URL_PATH );
        if ( file_exists( $sourcePath . DIRECTORY_SEPARATOR . '..' . $hrefFilePath ) ) continue;
      }
      $hrefHostname = strtolower( convertIdnToAscii( parse_url( $hrefAbsolute, PHP_URL_HOST ) ) );
      $hrefAbsolute = encodeUrl( $hrefAbsolute );
      $hrefVariants = [$hrefAbsolute];
      if ( preg_match( '~[/]+$~', $hrefAbsolute ) ) {
        $hrefVariants[] = preg_replace( '~[/]+$~', '', $hrefAbsolute );
      } elseif ( !parse_url( $hrefAbsolute, PHP_URL_QUERY ) && !parse_url( $hrefAbsolute, PHP_URL_FRAGMENT ) ) {
        $hrefVariants[] = $hrefAbsolute . '/';
      }
      if ( !preg_match( '~^([-a-z0-9.]+\.)?' . preg_quote( $uuidSettings['domain'], '~' ) . '$~i', $hrefHostname ) ) continue;
      if ( !empty( $_POST['stats_only'] ) ) {
        $urlExists = 0;
        foreach ( $hrefVariants as $hrefVariant ) if ( key_exists( $hrefVariant, $urlsArray ) ) $urlExists = 1;
        if ( !$urlExists ) $stats['stats'][$hrefAbsolute][0] = isset( $stats['stats'][$hrefAbsolute][0] ) ? $stats['stats'][$hrefAbsolute][0] + 1 : 1;
      }
      //if ( !urlExists( $hrefVariants ) && empty( $_POST['stats_only'] ) ) {
      if ( empty( $_POST['stats_only'] ) ) {
        $urlExists = 0;
        foreach ( $hrefVariants as $hrefVariant ) if ( key_exists( $hrefVariant, $urlsArray ) ) $urlExists = 1;
        if ( !$urlExists ) {
          $updatedImages++;
          $imgTags->item( $n )->parentNode->removeChild( $imgTags->item( $n ) );
        }
      }
    }
    if ( $updatedImages ) {
      backupFile( $url['rowid'], 'edit' );
      file_put_contents( $file, convertHtmlEncoding( html_entity_decode( $dom->saveHTML() ), $url['charset'], 'utf-8' ) );
      updateFilesize( $url['rowid'], filesize( $file ) );
      $stats['images'] += $updatedImages;
    }
    $stats['pages']++;
    $stats['processed']++;

    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $url['rowid'];
      return $stats;
    }
  }
  if ( $stats['images'] ) createBackupBreakpoint( L( 'Remove broken images' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['images'], 0 ) ) );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return $stats;
}

function removeBrokenLinks( $taskOffset = 0 )
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $sourcePath;
  global $uuidSettings;
  global $ACMS;

  $stats = array_merge( ['links' => 0, 'pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  if ( function_exists( 'libxml_use_internal_errors' ) ) libxml_use_internal_errors( true );
  $pdo = newPDO();
  if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdo->query( "SELECT COUNT(1) FROM structure WHERE mimetype = 'text/html'" )->fetchColumn() );
  $stmt = $pdo->prepare( "SELECT rowid, * FROM structure WHERE mimetype = 'text/html' AND rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );

  $urlsArray = array_fill_keys( array_keys( sqlReindex( sqlGetLines( "SELECT url FROM structure" ), 'url' ) ), 0 );

  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $updatedLinks = 0;
    $file         = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
    if ( !is_file( $file ) ) continue;
    $html = file_get_contents( $file );
    if ( !strlen( $html ) ) continue;
    $html = convertEncoding( convertHtmlEncoding( $html, 'utf-8', $url['charset'] ), 'html-entities', 'utf-8' );
    unset( $dom );
    $dom                      = new DOMDocument();
    $dom->formatOutput        = true;
    $dom->documentURI         = $url['url'];
    $dom->strictErrorChecking = false;
    $dom->encoding            = 'utf-8';
    if ( defined( 'LIBXML_HTML_NOIMPLIED' ) && defined( 'LIBXML_HTML_NODEFDTD' ) ) {
      $dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    } else {
      $dom->loadHTML( $html );
    }
    $linkTags = $dom->getElementsByTagName( 'a' );
    for ( $n = $linkTags->length - 1; $n >= 0; --$n ) {
      $hrefAttribute = $linkTags->item( $n )->getAttribute( 'href' );
      $hrefAbsolute  = rawurldecode( getAbsolutePath( $url['url'], $hrefAttribute ) );
      if ( !empty( $_POST['check_files'] ) ) {
        $hrefFilePath = parse_url( $hrefAbsolute, PHP_URL_PATH );
        if ( file_exists( $sourcePath . DIRECTORY_SEPARATOR . '..' . $hrefFilePath ) ) continue;
      }
      $hrefHostname = strtolower( convertIdnToAscii( parse_url( $hrefAbsolute, PHP_URL_HOST ) ) );
      $hrefAbsolute = encodeUrl( $hrefAbsolute );
      $hrefVariants = [$hrefAbsolute];
      if ( preg_match( '~[/]+$~', $hrefAbsolute ) ) {
        $hrefVariants[] = preg_replace( '~[/]+$~', '', $hrefAbsolute );
      } elseif ( !parse_url( $hrefAbsolute, PHP_URL_QUERY ) && !parse_url( $hrefAbsolute, PHP_URL_FRAGMENT ) ) {
        $hrefVariants[] = $hrefAbsolute . '/';
      }
      if ( !preg_match( '~^([-a-z0-9.]+\.)?' . preg_quote( $uuidSettings['domain'], '~' ) . '$~i', $hrefHostname ) ) continue;
      if ( !empty( $_POST['stats_only'] ) ) {
        $urlExists = 0;
        foreach ( $hrefVariants as $hrefVariant ) if ( key_exists( $hrefVariant, $urlsArray ) ) $urlExists = 1;
        if ( !$urlExists ) $stats['stats'][$hrefAbsolute][0] = isset( $stats['stats'][$hrefAbsolute][0] ) ? $stats['stats'][$hrefAbsolute][0] + 1 : 1;
      }
      if ( empty( $_POST['stats_only'] ) ) {
        $urlExists = 0;
        foreach ( $hrefVariants as $hrefVariant ) if ( key_exists( $hrefVariant, $urlsArray ) ) $urlExists = 1;
        if ( !$urlExists ) {
          $updatedLinks++;
          while ( $linkTags->item( $n )->hasChildNodes() ) {
            $linkTagChild = $linkTags->item( $n )->removeChild( $linkTags->item( $n )->firstChild );
            $linkTags->item( $n )->parentNode->insertBefore( $linkTagChild, $linkTags->item( $n ) );
          }
          $linkTags->item( $n )->parentNode->removeChild( $linkTags->item( $n ) );
        }
      }
    }
    if ( $updatedLinks ) {
      backupFile( $url['rowid'], 'edit' );
      file_put_contents( $file, convertHtmlEncoding( html_entity_decode( $dom->saveHTML() ), $url['charset'], 'utf-8' ) );
      updateFilesize( $url['rowid'], filesize( $file ) );
      $stats['links'] += $updatedLinks;
    }
    $stats['pages']++;
    $stats['processed']++;

    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $url['rowid'];
      return $stats;
    }
  }
  if ( $stats['links'] ) createBackupBreakpoint( L( 'Remove broken links' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['links'], 0 ) ) );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return $stats;
}

function removeMetaTableData( $name )
{
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "DELETE FROM meta WHERE name = :name" );
  $stmt->bindParam( 'name', $name, PDO::PARAM_STR );
  $stmt->execute();
}

function removeExternalLinks( $taskOffset = 0 )
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $sourcePath;
  global $uuidSettings;
  global $ACMS;

  $stats = array_merge( ['links' => 0, 'pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  if ( function_exists( 'libxml_use_internal_errors' ) ) libxml_use_internal_errors( true );
  $pdo = newPDO();
  if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdo->query( "SELECT COUNT(1) FROM structure WHERE mimetype = 'text/html'" )->fetchColumn() );
  $stmt = $pdo->prepare( "SELECT rowid, * FROM structure WHERE mimetype = 'text/html' AND rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );
  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $updatedLinks = 0;
    $file         = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
    if ( !is_file( $file ) ) continue;
    $html = file_get_contents( $file );
    if ( !strlen( $html ) ) continue;
    $html = convertEncoding( convertHtmlEncoding( $html, 'utf-8', $url['charset'] ), 'html-entities', 'utf-8' );
    unset( $dom );
    $dom                      = new DOMDocument();
    $dom->formatOutput        = true;
    $dom->documentURI         = $url['url'];
    $dom->strictErrorChecking = false;
    $dom->encoding            = 'utf-8';
    if ( defined( 'LIBXML_HTML_NOIMPLIED' ) && defined( 'LIBXML_HTML_NODEFDTD' ) ) {
      $dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    } else {
      $dom->loadHTML( $html );
    }
    $linkTags = $dom->getElementsByTagName( 'a' );
    for ( $n = $linkTags->length - 1; $n >= 0; --$n ) {
      $hrefAttribute = $linkTags->item( $n )->getAttribute( 'href' );
      $hrefAbsolute  = rawurldecode( getAbsolutePath( $url['url'], $hrefAttribute ) );
      $hrefHostname  = strtolower( convertIdnToAscii( parse_url( $hrefAbsolute, PHP_URL_HOST ) ) );
      if ( preg_match( '~^([-a-z0-9.]+\.)?' . preg_quote( $uuidSettings['domain'], '~' ) . '$~i', $hrefHostname ) ) continue;
      $updatedLinks++;
      while ( $linkTags->item( $n )->hasChildNodes() ) {
        $linkTagChild = $linkTags->item( $n )->removeChild( $linkTags->item( $n )->firstChild );
        $linkTags->item( $n )->parentNode->insertBefore( $linkTagChild, $linkTags->item( $n ) );
      }
      $linkTags->item( $n )->parentNode->removeChild( $linkTags->item( $n ) );
    }
    if ( $updatedLinks ) {
      backupFile( $url['rowid'], 'edit' );
      file_put_contents( $file, convertHtmlEncoding( html_entity_decode( $dom->saveHTML() ), $url['charset'], 'utf-8' ) );
      updateFilesize( $url['rowid'], filesize( $file ) );
      $stats['links'] += $updatedLinks;
    }
    $stats['pages']++;
    $stats['processed']++;

    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $url['rowid'];
      return $stats;
    }
  }
  if ( $stats['links'] ) createBackupBreakpoint( L( 'Remove external links' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['links'], 0 ) ) );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return $stats;
}

function removeImport( $filename )
{
  global $sourcePath;
  $filename = basename( $filename );
  $filename = $sourcePath . DIRECTORY_SEPARATOR . 'imports' . DIRECTORY_SEPARATOR . $filename;
  if ( !file_exists( $filename ) ) {
    return;
  }
  if ( unlink( $filename ) ) {
    return true;
  }
}

function removeTemplate( $name )
{
  global $sourcePath;
  $metaData = getTemplate( $name );
  if ( empty( $metaData ) ) return false;
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "DELETE FROM templates WHERE name = :name" );
  $stmt->execute( ['name' => $metaData['name']] );
  unlink( $sourcePath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $metaData['name'] . '.html' );
  return true;
}

function removeUrl( $id )
{
  global $sourcePath;

  backupFile( $id, 'remove' );
  $metaData = getMetaData( $id );
  if ( !empty( $metaData['folder'] ) && !empty( $metaData['filename'] ) ) {
    unlink( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'] );
  }

  $pdo  = newPDO();
  $stmt = $pdo->prepare( 'DELETE FROM structure WHERE rowid = :rowid' );
  $stmt->execute( ['rowid' => $id] );

  responseAjax();

  return $stmt->rowCount();
}

function removeVersionControl( $taskOffset = 0 )
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $sourcePath;
  global $ACMS;

  $stats = array_merge( ['urls' => 0, 'pages' => 0, 'replaces' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  $skipUrls = [];

  $replaceUrl = [
    '\?(?:v|ver)=[-a-f.\d]+$'                  => '',
    '\?[\d\w]+$'                               => '',
    '\?(?:PHPSESSID|sid|s)=[a-z\d]+$'          => '',
    '\?(?:PHPSESSID|sid|s)=[a-z\d]+&(?:amp;)?' => '?',
    '&(?:amp;)?(?:PHPSESSID|sid|s)=[a-z\d]+'   => '',
  ];


  $replaceCode = [
    '\?(?:v|ver)=[-a-f.\d]+([\'"\)])'          => '$1',
    '\?[\d\w]+([\'"\)])'                       => '$1',
    '\?(?:PHPSESSID|sid|s)=[a-z\d]+([\'"])'    => '$1',
    '\?(?:PHPSESSID|sid|s)=[a-z\d]+&(?:amp;)?' => '?',
    '&(?:amp;)?(?:PHPSESSID|sid|s)=[a-z\d]+'   => '',
  ];

  $pdo = newPDO();
  if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdo->query( "SELECT COUNT(1) FROM structure" )->fetchColumn() );
  $stmt = $pdo->prepare( "SELECT rowid, * FROM structure WHERE rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );

  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $stats['processed']++;
    if ( in_array( $url['rowid'], $skipUrls ) ) continue;

    // fix url
    $urlRemoved = false;
    foreach ( $replaceUrl as $search => $replace ) {
      preg_match_all( "~{$search}~i", rawurldecode( $url['request_uri'] ), $found );
      $matches = preg_filter( "~{$search}~i", "{$replace}", rawurldecode( $url['request_uri'] ), -1, $count );
      if ( !$count ) continue;
      $request_uri_new         = encodePath( preg_replace( "~{$search}~is", "{$replace}", rawurldecode( $url['request_uri'] ) ) );
      $request_uri_new_decoded = rawurldecode( $request_uri_new );
      $request_uri_new_valid   = substr( $request_uri_new, 0, 1 ) === '/' && filter_var( 'http://domain' . $request_uri_new, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED );

      if ( !$request_uri_new_valid ) continue;

      $url_existing = getUrlByPath( $url['hostname'], $request_uri_new );

      if ( !$url_existing ) {
        $url['original_filename'] = $url['filename'];
        $url['urlID']             = $url['rowid'];
        $url['url']               = $url['protocol'] . '://' . $url['hostname'] . $request_uri_new;
        $url['request_uri']       = $request_uri_new_decoded;
        updateUrlSettings( $url );
        $url = getMetaData( $url['rowid'] );
      } else {
        $url_existing = getUrl( $url_existing['rowid'] );
        if ( $url_existing && $url_existing['rowid'] != $url['rowid'] ) {
          if ( $url_existing['filetime'] < $url['filetime'] ) {
            removeUrl( $url_existing['rowid'] );
            $skipUrls[]               = $url_existing['rowid'];
            $url['original_filename'] = $url['filename'];
            $url['urlID']             = $url['rowid'];
            $url['url']               = $url['protocol'] . '://' . $url['hostname'] . $request_uri_new;
            $url['request_uri']       = $request_uri_new_decoded;
            $url['mimetype']          = $url_existing['mimetype'];
            $url['charset']           = $url_existing['charset'];
            $url['filetime']          = $url_existing['filetime'];
            updateUrlSettings( $url );
            $url = getMetaData( $url['rowid'] );
          } else {
            removeUrl( $url['rowid'] );
            $urlRemoved = true;
          }
        }
      }
      $stats['urls']++;
    }
    // end fix url

    if ( $urlRemoved ) continue;
    if ( !in_array( $url['mimetype'], ['text/html', 'text/css', 'application/javascript', 'application/x-javascript', 'text/javascript', 'text/plain', 'application/json', 'application/xml', 'text/xml'] ) ) continue;

    // fix code
    if ( $url['filename'] == '' ) continue;
    $file          = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
    $backupCreated = false;

    foreach ( $replaceCode as $search => $replace ) {
      $search  = convertEncoding( $search, $url['charset'], 'utf-8' );
      $replace = convertEncoding( $replace, $url['charset'], 'utf-8' );
      $search  = preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", $search );
      $replace = preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", $replace );

      preg_match_all( "~{$search}~i", file_get_contents( $file ), $found );
      $matches = preg_filter( "~{$search}~i", "{$replace}", file_get_contents( $file ), -1, $count );

      if ( !$count ) continue;
      $stats['replaces']++;
      if ( !$backupCreated ) backupFile( $url['rowid'], 'replace' );
      $backupCreated = true;
      file_put_contents( $file, $matches );
    }

    if ( $backupCreated ) updateFilesize( $url['rowid'], filesize( $file ) );
    // end fix code

    $stats['pages']++;
    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $url['rowid'];
      return $stats;
    }
  }

  if ( $stats['replaces'] ) createBackupBreakpoint( L( 'Versions in CSS and JS' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['replaces'], 0 ) ) );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return $stats;
}

function replaceUrl( $existingId, $metaDataNew )
{
  global $sourcePath;
  global $uuidSettings;

  backupFile( $existingId, 'replace' );
  $metaDataExisting        = getMetaData( $existingId );
  $mimeNew                 = getMimeInfo( $metaDataNew['mimetype'] );
  $metaDataNew['protocol'] = !empty( $uuidSettings['https'] ) ? 'https' : 'http';
  $metaDataNew['folder']   = $mimeNew['folder'];
  $metaDataNew['filename'] = sprintf( '%s/%s.%08d.%s', substr( convertPathToFilename( $metaDataNew['request_uri'] ), 0, 1 ), convertPathToFilename( $metaDataNew['request_uri'] ), $existingId, $mimeNew['extension'] );

  $pdo  = newPDO();
  $stmt = $pdo->prepare( 'UPDATE structure SET url = :url, protocol = :protocol, hostname = :hostname, request_uri = :request_uri, folder = :folder, filename = :filename, mimetype = :mimetype, charset = :charset, filesize = :filesize, filetime = :filetime, url_original = :url_original, enabled = :enabled, redirect = :redirect WHERE rowid = :rowid' );
  $stmt->execute( [
    'url'          => $metaDataNew['protocol'] . '://' . $metaDataNew['hostname'] . $metaDataNew['request_uri'],
    'protocol'     => $metaDataNew['protocol'],
    'hostname'     => $metaDataNew['hostname'],
    'request_uri'  => $metaDataNew['request_uri'],
    'folder'       => $metaDataNew['folder'],
    'filename'     => $metaDataNew['filename'],
    'mimetype'     => $metaDataNew['mimetype'],
    'charset'      => $metaDataNew['charset'],
    'filesize'     => $metaDataNew['filesize'],
    'filetime'     => $metaDataNew['filetime'],
    'url_original' => $metaDataNew['url_original'],
    'enabled'      => $metaDataNew['enabled'],
    'redirect'     => $metaDataNew['redirect'],
    'rowid'        => $existingId,
  ] );

  if ( !empty( $metaDataExisting['filename'] ) ) {
    unlink( $sourcePath . DIRECTORY_SEPARATOR . $metaDataExisting['folder'] . DIRECTORY_SEPARATOR . $metaDataExisting['filename'] );
  }
  copy( $metaDataNew['tmp_file_path'], $sourcePath . DIRECTORY_SEPARATOR . $metaDataNew['folder'] . DIRECTORY_SEPARATOR . $metaDataNew['filename'] );
}

function responseAjax( $status = true )
{
  if ( !empty( $_POST['ajax'] ) ) {
    header( 'Content-Type: application/json' );
    echo json_encode( ['status' => 'ok'] );
    exit( 0 );
  }
}

function sanitizeString( $string, $length = 200, $latin = 1, $delimiter = '-' )
{
  $string = strip_tags( $string );

  if ( $latin ) {
    if ( function_exists( 'transliterator_transliterate' ) ) {
      $string = iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', transliterator_transliterate( 'Any-Latin; Latin-ASCII; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();', $string ) );
    } else {
      $char_map = [
        // Russian
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
        'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
        'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
        'Я' => 'Ya',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
        'я' => 'ya',
        // Ukrainian
        'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
        'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
        // Latin
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
        'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
        'ß' => 'ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
        'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
        'ÿ' => 'y',
        // Greek
        'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
        'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
        'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
        'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
        'Ϋ' => 'Y',
        'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
        'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
        'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
        'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
        'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
        // Turkish
        'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
        'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g',

        // Czech
        'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
        'Ž' => 'Z',
        'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
        'ž' => 'z',
        // Polish
        'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
        'Ż' => 'Z',
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
        'ż' => 'z',
        // Latvian
        'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
        'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
        'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
        'š' => 's', 'ū' => 'u', 'ž' => 'z',
      ];
      $string   = str_replace( array_keys( $char_map ), $char_map, $string );
      $string   = iconv( 'UTF-8', 'US-ASCII//TRANSLIT//IGNORE', $string );
    }
  }

  $string = preg_replace( '~[^\p{L}\p{Nd}]+~u', $delimiter, $string );
  $string = preg_replace( '~[' . preg_quote( $delimiter, '~' ) . ']{2,}~', $delimiter, $string );
  $string = trim( $string, $delimiter );

  if ( function_exists( 'mb_strtolower' ) ) {
    $string = mb_strtolower( $string );
  }
  $string = strtolower( $string );

  if ( $length && function_exists( 'mb_substr' ) ) {
    return mb_substr( $string, 0, $length );
  }

  return $string;
}

function saveFile( $rowid )
{
  global $sourcePath;
  backupFile( $rowid, 'edit' );
  $metaData = getMetaData( $rowid );
  if ( isset( $metaData['charset'] ) ) {
    $content = convertEncoding( $_POST['content'], $metaData['charset'], 'UTF-8' );
  } else {
    $content = $_POST['content'];
  }
  file_put_contents( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'], $content );
  updateFilesize( $rowid, strlen( $content ) );

  responseAjax();
}

function saveMetaData( $name, $data )
{
  createTable( 'meta' );
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "INSERT OR IGNORE INTO meta (name, data) VALUES (:name, :data)" );
  $stmt->bindParam( 'name', $name, PDO::PARAM_STR );
  $stmt->bindParam( 'data', $data, PDO::PARAM_STR );
  $stmt->execute();

  $stmt = $pdo->prepare( "UPDATE meta SET data = :data WHERE name = :name" );
  $stmt->bindParam( 'name', $name, PDO::PARAM_STR );
  $stmt->bindParam( 'data', $data, PDO::PARAM_STR );
  $stmt->execute();
}

function saveTemplateFile( $name )
{
  $templatesPath = createDirectory( 'templates' );
  $metaData      = getTemplate( $name );
  if ( empty( $metaData ) ) return false;
  if ( isset( $metaData['charset'] ) ) {
    $content = convertEncoding( $_POST['content'], $metaData['charset'], 'UTF-8' );
  } else {
    $content = $_POST['content'];
  }
  file_put_contents( $templatesPath . DIRECTORY_SEPARATOR . $metaData['name'] . '.html', $content );

  responseAjax();
}

function sendRequestCloudflare( $type, $path, $data = '' )
{
  $acmsCloudflare = readMetaData( 'acms_cloudflare' );
  $options        = [
    CURLOPT_URL            => 'https://api.cloudflare.com' . $path,
    CURLOPT_CUSTOMREQUEST  => $type,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_FAILONERROR    => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER     => [
      'Authorization: Bearer ' . $acmsCloudflare['data']['token'],
      'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS     => $data,
  ];
  $ch             = curl_init();
  curl_setopt_array( $ch, $options );
  $response = curl_exec( $ch );
  curl_close( $ch );
  $response = json_decode( $response, true );
  return $response;
}

function setAcmsSettings( $settings, $filename = null )
{
  global $sourcePath;
  global $ACMS;
  if ( empty( $sourcePath ) ) return;
  if ( empty( $filename ) ) {
    $filename = $sourcePath . DIRECTORY_SEPARATOR . '.acms.settings.json';
  }

  if ( !inSafeMode() ) {
    if ( isset( $settings['ACMS_PASSWORD'] ) && strlen( $settings['ACMS_PASSWORD'] ) ) {
      if ( empty( password_get_info( $settings['ACMS_PASSWORD'] )['algo'] ) )
        $settings['ACMS_PASSWORD'] = password_hash( $settings['ACMS_PASSWORD'], PASSWORD_DEFAULT );
      unset( $_SESSION['archivarix.logged'] );
    } else {
      if ( !empty( $_POST['remove_password'] ) ) {
        $settings['ACMS_PASSWORD'] = '';
      } else {
        unset( $settings['ACMS_PASSWORD'] );
      }
    }

    if ( isset( $settings['ACMS_SAFE_PASSWORD'] ) && strlen( $settings['ACMS_SAFE_PASSWORD'] ) ) {
      if ( empty( password_get_info( $settings['ACMS_SAFE_PASSWORD'] )['algo'] ) )
        $settings['ACMS_SAFE_PASSWORD'] = password_hash( $settings['ACMS_SAFE_PASSWORD'], PASSWORD_DEFAULT );
    } else {
      if ( !empty( $_POST['remove_safe_password'] ) ) {
        $settings['ACMS_SAFE_PASSWORD'] = '';
      } else {
        unset( $settings['ACMS_SAFE_PASSWORD'] );
      }
    }

    if ( isset( $settings['ACMS_ALLOWED_IPS'] ) && strlen( $settings['ACMS_ALLOWED_IPS'] ) ) {
      $settings['ACMS_ALLOWED_IPS'] = preg_replace( '~[^\d./,:]~', '', $settings['ACMS_ALLOWED_IPS'] );
    }
  }

  if ( inSafeMode() ) {
    unset( $settings['ACMS_PASSWORD'] );
    unset( $settings['ACMS_SAFE_PASSWORD'] );
    unset( $settings['ACMS_ALLOWED_IPS'] );
  }

  if ( !isset( $ACMS['ACMS_PASSWORD'] ) || !strlen( $ACMS['ACMS_PASSWORD'] ) && strlen( ACMS_PASSWORD ) ) $settings['ACMS_PASSWORD'] = password_hash( ACMS_PASSWORD, PASSWORD_DEFAULT );
  if ( !isset( $ACMS['ACMS_SAFE_PASSWORD'] ) || !strlen( $ACMS['ACMS_SAFE_PASSWORD'] ) && strlen( ACMS_SAFE_PASSWORD ) ) $settings['ACMS_SAFE_PASSWORD'] = password_hash( ACMS_SAFE_PASSWORD, PASSWORD_DEFAULT );
  $ACMS = array_merge( $ACMS, $settings );
  $ACMS = array_filter( $ACMS, function ( $k ) {
    return preg_match( '~^ACMS_~i', $k );
  }, ARRAY_FILTER_USE_KEY );
  file_put_contents( $filename, json_encode( $ACMS, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
}

function setApiKey( $apiKey )
{
  $publicKey = getPublicKeyInfo( trim( $apiKey['api_public_key'] ) );
  if ( empty( $publicKey['key'] ) ) return false;
  $acmsSettings['ACMS_PUBLIC_KEY'] = $publicKey['key'];
  setAcmsSettings( $acmsSettings );
  return true;
}

function setCloudflareToken( $input )
{
  $zone_id = $input['zone_id'];
  $token   = $input['token'];
  $options = [
    CURLOPT_URL            => 'https://api.cloudflare.com/client/v4/zones/' . $zone_id,
    CURLOPT_CUSTOMREQUEST  => 'GET',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_FAILONERROR    => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER     => [
      'Authorization: Bearer ' . $token,
      'Content-Type:application/json',
    ],
  ];
  $ch      = curl_init();
  curl_setopt_array( $ch, $options );
  $response = curl_exec( $ch );
  curl_close( $ch );
  $response = json_decode( $response, true );
  if ( !empty( $response['success'] ) ) {
    saveMetaData( 'acms_cloudflare', json_encode( ['zone_id' => $response['result']['id'], 'name' => $response['result']['name'], 'token' => $token] ) );
    return true;
  }
}

function setDevelopmentModeCloudflare( $enable = 1 )
{
  $acmsCloudflare = readMetaData( 'acms_cloudflare' );
  $path           = '/client/v4/zones/' . $acmsCloudflare['data']['zone_id'] . '/settings/development_mode';
  $data           = $enable ? '{"value":"on"}' : '{"value":"off"}';
  $response       = sendRequestCloudflare( 'PATCH', $path, $data );
  if ( !empty( $response['success'] ) ) {
    $acmsCloudflare = readMetaData( 'acms_cloudflare' );
    if ( $enable ) {
      $acmsCloudflare['data']['dev_mode_time_remaining'] = time() + $response['result']['time_remaining'];
    } else {
      unset( $acmsCloudflare['data']['dev_mode_time_remaining'] );
    }
    saveMetaData( 'acms_cloudflare', json_encode( $acmsCloudflare['data'] ) );
    return true;
  }
}

function setLanguage( $lang )
{
  global $cmsLocales;
  if ( in_array( $lang, ['en', 'ru'] ) ) {
    $_SESSION['archivarix.lang'] = $lang;
  } elseif ( !empty( $_SESSION['localization'][$lang] ) ) {
    $_SESSION['archivarix.lang'] = $lang;
  } elseif ( key_exists( $lang, $cmsLocales ) && !getMissingExtensions( ['curl'] ) ) {
    $externalLocalization = json_decode( curlContent( "https://archivarix.com/download/cms/locales/archivarix.cms.{$lang}.json" . '?uid=' . sha1( __DIR__ ) ), true );
    if ( !empty( $externalLocalization ) ) {
      unset( $_SESSION['localization'] );
      $_SESSION['localization'][$lang] = $externalLocalization;
      $_SESSION['archivarix.lang']     = $lang;
    }
  } else {
    $_SESSION['archivarix.lang'] = 'en';
  }
}

function setLoaderSettings( $settings, $filename = null )
{
  global $sourcePath;
  $LOADER = loadLoaderSettings();
  if ( empty( $filename ) ) {
    $filename = $sourcePath . DIRECTORY_SEPARATOR . '.loader.settings.json';
  }
  $includeCustom = [];
  if ( !empty( $settings['ARCHIVARIX_INCLUDE_CUSTOM']['FILE'] ) ) {
    foreach ( $settings['ARCHIVARIX_INCLUDE_CUSTOM']['FILE'] as $index => $value ) {
      if ( !strlen( basename( $settings['ARCHIVARIX_INCLUDE_CUSTOM']['FILE'][$index] ) ) ) continue;
      if ( !strlen( $settings['ARCHIVARIX_INCLUDE_CUSTOM']['KEYPHRASE'][$index] ) ) continue;
      $includeCustom[] = [
        'FILE'      => basename( $settings['ARCHIVARIX_INCLUDE_CUSTOM']['FILE'][$index] ),
        'KEYPHRASE' => $settings['ARCHIVARIX_INCLUDE_CUSTOM']['KEYPHRASE'][$index],
        'LIMIT'     => $settings['ARCHIVARIX_INCLUDE_CUSTOM']['LIMIT'][$index],
        'REGEX'     => $settings['ARCHIVARIX_INCLUDE_CUSTOM']['REGEX'][$index],
        'POSITION'  => $settings['ARCHIVARIX_INCLUDE_CUSTOM']['POSITION'][$index],
        'URL_MATCH' => $settings['ARCHIVARIX_INCLUDE_CUSTOM']['URL_MATCH'][$index],
        'URL_DEPTH' => $settings['ARCHIVARIX_INCLUDE_CUSTOM']['URL_DEPTH_OPERATOR'][$index] . $settings['ARCHIVARIX_INCLUDE_CUSTOM']['URL_DEPTH_VALUE'][$index],
      ];
    }
    $settings['ARCHIVARIX_INCLUDE_CUSTOM'] = $includeCustom;
  }
  if ( is_string( $settings['ARCHIVARIX_BLOCK_BOTS'] ) ) {
    $settings['ARCHIVARIX_BLOCK_BOTS'] = array_filter( array_map( 'trim', preg_split( "~[,\n]+~", $settings['ARCHIVARIX_BLOCK_BOTS'] ) ) );
  }
  if ( !isset( $settings['ARCHIVARIX_BLOCK_BOTS'] ) ) $settings['ARCHIVARIX_BLOCK_BOTS'] = [];

  $LOADER = array_merge( $LOADER, $settings );
  file_put_contents( $filename, json_encode( $LOADER, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
}

function showWarning()
{
  global $warnings;
  if ( !isset( $warnings ) ) {
    $warnings = [];
  }
  foreach ( $warnings as $warning ) {
    echo <<< EOT
<div class="toast mw-100" role="alert" aria-live="assertive" aria-atomic="true" data-autohide="false" data-delay="5000" data-show="true">
  <div class="toast-header text-light bg-{$warning['level']}">
    <strong class="mr-auto">{$warning['title']}</strong>
    <small class="text-light"></small>
    <button type="button" class="ml-2 mb-1 close text-light" data-dismiss="toast" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
  <div class="toast-body">
    {$warning['message']}
  </div>
</div>
EOT;
  }
}

function showWarningJson( $message, $exit = 0 )
{
  $message['execution_time'] = microtime( true ) - ACMS_START_TIME;
  $message['execution_time'] = round( $message['execution_time'], 3 );
  $message['memory_peak']    = getHumanSize( memory_get_peak_usage() );
  echo json_encode( $message, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n";
  if ( $exit && empty( $message['status'] ) ) exit( 1 );
  if ( $exit ) exit( 0 );
}

function sqlExec( $sql, $params = [] )
{
  $pdo  = newPDO();
  $stmt = $pdo->prepare( $sql );
  $stmt->execute( $params );
  return $pdo->lastInsertId();
}

function sqlGetValue( $sql, $params = [] )
{
  if ( $params ) {
    $pdo  = newPDO();
    $stmt = $pdo->prepare( $sql );
    $stmt->execute( $params );
    return $stmt->fetchColumn();
  }
  return newPDO()->query( $sql )->fetchColumn();
}

function sqlGetLine( $sql, $params = [] )
{
  if ( $params ) {
    $pdo  = newPDO();
    $stmt = $pdo->prepare( $sql );
    $stmt->execute( $params );
    return $stmt->fetch( PDO::FETCH_ASSOC );
  }
  return newPDO()->query( $sql )->fetch( PDO::FETCH_ASSOC );
}

function sqlGetLines( $sql, $params = [] )
{
  if ( $params ) {
    $pdo  = newPDO();
    $stmt = $pdo->prepare( $sql );
    $stmt->execute( $params );
    return $stmt->fetchAll( PDO::FETCH_ASSOC );
  }
  return newPDO()->query( $sql )->fetchAll( PDO::FETCH_ASSOC );
}

function sqlReindex( $rows, $index = 'id' )
{
  if ( !$rows ) return [];
  $result = [];
  foreach ( $rows as $row ) $result[$row[$index]] = $row;
  return $result;
}

function updateCustomFile( $input )
{
  global $sourcePath;
  if ( inSafeMode() && preg_match( '~[<]([?%]|[^>]*script\b[^>]*\blanguage\b[^>]*\bphp\b)~is', $input['content'] ) ) {
    addWarning( L( 'You cannot create or edit custom files with a PHP code in a safe mode.' ), 4, L( 'Custom Files' ) );
    return false;
  }
  $includesPath = $sourcePath . DIRECTORY_SEPARATOR . 'includes';
  $filename     = basename( $input['filename'] );
  if ( !file_exists( $includesPath . DIRECTORY_SEPARATOR . $filename ) ) return;
  $newFilename = basename( $input['new_filename'] );
  if ( !preg_match( '~^[-.\w]+$~i', $newFilename ) || in_array( $newFilename, ['.', '..'] ) ) $newFilename = $filename;
  unlink( $includesPath . DIRECTORY_SEPARATOR . $filename );
  $file = $includesPath . DIRECTORY_SEPARATOR . $newFilename;
  file_put_contents( $file, $input['content'] );
  return true;
}

function updateExternalLinks( $setAttributes = [], $taskOffset = 0 )
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $sourcePath;
  global $uuidSettings;
  global $ACMS;

  $stats = array_merge( ['links' => 0, 'pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  if ( function_exists( 'libxml_use_internal_errors' ) ) libxml_use_internal_errors( true );
  $pdo = newPDO();
  if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdo->query( "SELECT COUNT(1) FROM structure WHERE mimetype = 'text/html'" )->fetchColumn() );
  $stmt = $pdo->prepare( "SELECT rowid, * FROM structure WHERE mimetype = 'text/html' AND rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );
  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $updatedLinks = 0;
    $file         = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
    if ( !is_file( $file ) ) continue;
    $html = file_get_contents( $file );
    if ( !strlen( $html ) ) continue;
    $html = convertEncoding( convertHtmlEncoding( $html, 'utf-8', $url['charset'] ), 'html-entities', 'utf-8' );
    unset( $dom );
    $dom                      = new DOMDocument();
    $dom->formatOutput        = true;
    $dom->documentURI         = $url['url'];
    $dom->strictErrorChecking = false;
    $dom->encoding            = 'utf-8';
    if ( defined( 'LIBXML_HTML_NOIMPLIED' ) && defined( 'LIBXML_HTML_NODEFDTD' ) ) {
      $dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    } else {
      $dom->loadHTML( $html );
    }
    $linkTags = $dom->getElementsByTagName( 'a' );
    for ( $n = $linkTags->length - 1; $n >= 0; --$n ) {
      $hrefAttribute     = $linkTags->item( $n )->getAttribute( 'href' );
      $hrefAbsolute      = rawurldecode( getAbsolutePath( $url['url'], $hrefAttribute ) );
      $hrefHostname      = strtolower( convertIdnToAscii( parse_url( $hrefAbsolute, PHP_URL_HOST ) ) );
      $attributesUpdated = 0;
      if ( !preg_match( '~^([-a-z0-9.]+\.)?' . preg_quote( $uuidSettings['domain'], '~' ) . '$~i', $hrefHostname ) ) {
        foreach ( $setAttributes as $attributeName => $attributeValue ) {
          if ( empty( $attributeValue ) ) continue;
          $linkTags->item( $n )->setAttribute( $attributeName, $attributeValue );
          $attributesUpdated++;
        }
        if ( $attributesUpdated ) $updatedLinks++;
      }
    }
    if ( $updatedLinks ) {
      backupFile( $url['rowid'], 'edit' );
      file_put_contents( $file, convertHtmlEncoding( html_entity_decode( $dom->saveHTML() ), $url['charset'], 'utf-8' ) );
      updateFilesize( $url['rowid'], filesize( $file ) );
      $stats['links'] += $updatedLinks;
    }
    $stats['pages']++;
    $stats['processed']++;

    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $url['rowid'];
      return $stats;
    }
  }
  if ( $stats['links'] ) createBackupBreakpoint( L( 'Update external links' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['links'], 0 ) ) );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return $stats;
}

function updateFilesize( $rowid, $filesize )
{
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "UPDATE structure SET filesize = :filesize WHERE rowid = :rowid" );
  $stmt->execute( ['filesize' => $filesize, 'rowid' => $rowid] );
}

function updateHtaccessFile( $content )
{
  global $sourcePath;
  // [TODO] validator: create tmpdir, save index.html, .htaccess, do curl, response code != 500
  $htaccessPath = __DIR__ . DIRECTORY_SEPARATOR . '.htaccess';
  file_put_contents( $htaccessPath, $content );
}

function updateUrlsMeta( $taskOffset = 0 )
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $sourcePath;
  global $ACMS;

  $stats = array_merge( ['links' => 0, 'pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  $pdo = newPDO();
  if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdo->query( "SELECT COUNT(1) FROM structure" )->fetchColumn() );
  $stmt = $pdo->prepare( "SELECT rowid, * FROM structure WHERE rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );
  while ( $metaData = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $stats['pages']++;
    if ( empty( $metaData['filename'] ) ) continue;
    $filename = $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'];
    if ( file_exists( $filename ) && is_file( $filename ) && filesize( $filename ) != $metaData['filesize'] ) {
      $stats['processed']++;
      updateFilesize( $metaData['rowid'], filesize( $filename ) );
    }

    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $metaData['rowid'];
      return $stats;
    }
  }

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return $stats;
}

function updateSettings( $param, $value )
{
  $settings = getSettings();
  $pdo      = newPDO();
  if ( array_key_exists( $param, $settings ) ) {
    $stmt = $pdo->prepare( "UPDATE settings SET value = :value WHERE param = :param" );
  } else {
    $stmt = $pdo->prepare( "INSERT INTO settings (param, value) VALUES (:param, :value)" );
  }
  $stmt->execute( ['param' => $param, 'value' => $value] );
}

function updateSystem()
{
  $cmsVersion = ACMS_VERSION;
  $updateInfo = json_decode( curlContent( 'https://archivarix.com/' . getLang() . '/cms/?ver=' . $cmsVersion . '&uid=' . sha1( __DIR__ ) ), true );
  if ( empty( $updateInfo['cms_version'] ) || empty( $updateInfo['loader_version'] ) ) {
    addWarning( L( 'Could not connect to the update server.' ), 4, L( 'System update' ) );
    return;
  }
  $loaderInfo = getLoaderInfo();

  if ( version_compare( $updateInfo['cms_version'], $cmsVersion, '>' ) ) {
    $cmsFileZip   = tempnam( getTempDirectory(), 'archivarix.' );
    $cmsLocalFile = $_SERVER['SCRIPT_FILENAME'];
    downloadFile( $updateInfo['cms_download_link'], $cmsFileZip );
    $zip = new ZipArchive();
    $zip->open( $cmsFileZip );
    $cmsData = $zip->getFromName( 'archivarix.cms.php' );
    if ( !empty( $cmsData ) && file_put_contents( $cmsLocalFile, $cmsData ) ) {
      addWarning( sprintf( L( '%s updated from version %s to %s. Click on the menu logo to reload the page into the new version.' ), 'Archivarix CMS', $cmsVersion, $updateInfo['cms_version'] ), 1, L( 'System update' ) );
    } else {
      addWarning( sprintf( L( 'Could not update %s. Please, update manually.' ), 'Archivarix CMS' ), 4, L( 'System update' ) );
    }
    $zip->close();
    unlink( $cmsFileZip );
  } else {
    addWarning( sprintf( L( 'You already have the latest version %s of %s.' ), $cmsVersion, 'Archivarix CMS' ), 2, L( 'System update' ) );
  }

  if ( empty( $loaderInfo['filename'] ) ) {
    addWarning( sprintf( L( '%s could not be detected. Please, update manually.' ), L( 'Archivarix Loader' ) ), 3, L( 'System update' ) );
    return;
  }

  if ( version_compare( $updateInfo['loader_version'], $loaderInfo['version'], '>' ) ) {
    $loaderFileZip   = tempnam( getTempDirectory(), 'archivarix.' );
    $loaderLocalFile = __DIR__ . DIRECTORY_SEPARATOR . $loaderInfo['filename'];
    downloadFile( $updateInfo['loader_download_link'], $loaderFileZip );
    $zip = new ZipArchive();
    $zip->open( $loaderFileZip );
    $loaderData = $zip->getFromName( 'index.php' );
    if ( !empty( $loaderData ) && file_put_contents( $loaderLocalFile, $loaderData ) ) {
      addWarning( sprintf( L( '%s is updated from version %s to %s.' ), L( 'Archivarix Loader' ), $loaderInfo['version'], $updateInfo['loader_version'] ), 1, L( 'System update' ) );
    } else {
      addWarning( sprintf( L( 'Could not update %s. Please, update manually.' ), L( 'Archivarix Loader' ) ), 4, L( 'System update' ) );
    }
    $zip->close();
    unlink( $loaderFileZip );
  } else {
    addWarning( sprintf( L( 'You already have the latest version %s of %s.' ), $loaderInfo['version'], L( 'Archivarix Loader' ) ), 2, L( 'System update' ) );
  }

}

function updateTemplateSettings( $input )
{
  global $sourcePath;
  $input['name'] = strtolower( $input['name'] );
  if ( $input['name'] != $input['name_orig'] ) {
    if ( !empty( getTemplate( $input['name'] ) ) ) {
      addWarning( sprintf( L( 'Template %s already exists.' ), $input['name'] ), 3, L( 'Templates' ) );
      return false;
    }
    rename( $sourcePath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $input['name_orig'] . '.html', $sourcePath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $input['name'] . '.html' );
  }
  $pdo  = newPDO();
  $stmt = $pdo->prepare( "UPDATE templates SET name = :name, hostname = :hostname, mimetype = :mimetype, charset = :charset, uploads = :uploads, path = :path WHERE name = :name_orig" );
  $stmt->execute( [
    'name'      => $input['name'],
    'hostname'  => $input['hostname'],
    'mimetype'  => $input['mimetype'],
    'charset'   => $input['charset'],
    'uploads'   => $input['uploads'],
    'path'      => $input['path'],
    'name_orig' => $input['name_orig'],
  ] );
}

function updateTrackersCode( $content )
{
  if ( !customRuleExists( 'trackers.txt' ) ) createCustomRule(
    [
      'FILE'      => 'trackers.txt',
      'KEYPHRASE' => '</head>',
      'REGEX'     => 0,
      'LIMIT'     => 1,
      'POSITION'  => -1,
      'URL_MATCH' => '',
    ]
  );
  return createCustomFile( ['filename' => 'trackers.txt', 'content' => $content] );
}

function updateUrlEncoded( $taskOffset = 0 )
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $sourcePath;
  global $ACMS;

  $stats = array_merge( ['links' => 0, 'pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  if ( function_exists( 'libxml_use_internal_errors' ) ) libxml_use_internal_errors( true );
  $pdo = newPDO();
  if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdo->query( "SELECT COUNT(1) FROM structure WHERE mimetype = 'text/html'" )->fetchColumn() );
  $stmt = $pdo->prepare( "SELECT rowid, * FROM structure WHERE mimetype = 'text/html' AND rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );
  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $updatedUrls = 0;
    $file        = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
    if ( !is_file( $file ) ) continue;
    $html = file_get_contents( $file );
    if ( !strlen( $html ) ) continue;
    $html = convertEncoding( convertHtmlEncoding( $html, 'utf-8', $url['charset'] ), 'html-entities', 'utf-8' );
    unset( $dom );
    $dom                      = new DOMDocument();
    $dom->formatOutput        = true;
    $dom->documentURI         = $url['url'];
    $dom->strictErrorChecking = false;
    $dom->encoding            = 'utf-8';
    if ( defined( 'LIBXML_HTML_NOIMPLIED' ) && defined( 'LIBXML_HTML_NODEFDTD' ) ) {
      $dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    } else {
      $dom->loadHTML( $html );
    }
    $attributesUpdated = 0;

    $linkTags = $dom->getElementsByTagName( 'a' );
    for ( $n = $linkTags->length - 1; $n >= 0; --$n ) {
      $hrefAttribute = $linkTags->item( $n )->getAttribute( 'href' );
      if ( preg_match( '~%([0-9A-F][a-f]|[a-f][0-9A-Z]|[a-f]{2})~', $hrefAttribute ) ) {
        $hrefAttribute = preg_replace_callback( '~(%[0-9a-f]{2})~', function ( $matches ) {
          return strtoupper( $matches[0] );
        }, $hrefAttribute );
        $attributesUpdated++;
        $updatedUrls++;
        $linkTags->item( $n )->setAttribute( 'href', $hrefAttribute );
      }
    }

    $linkTags = $dom->getElementsByTagName( 'img' );
    for ( $n = $linkTags->length - 1; $n >= 0; --$n ) {
      $srcAttribute = $linkTags->item( $n )->getAttribute( 'src' );
      if ( preg_match( '~%([0-9A-F][a-f]|[a-f][0-9A-Z]|[a-f]{2})~', $srcAttribute ) ) {
        $srcAttribute = preg_replace_callback( '~(%[0-9a-f]{2})~', function ( $matches ) {
          return strtoupper( $matches[0] );
        }, $srcAttribute );
        $attributesUpdated++;
        $updatedUrls++;
        $linkTags->item( $n )->setAttribute( 'src', $srcAttribute );
      }
    }

    if ( $updatedUrls ) {
      backupFile( $url['rowid'], 'edit' );
      file_put_contents( $file, convertHtmlEncoding( html_entity_decode( $dom->saveHTML() ), $url['charset'], 'utf-8' ) );
      updateFilesize( $url['rowid'], filesize( $file ) );
      $stats['links'] += $updatedUrls;
    }
    $stats['pages']++;
    $stats['processed']++;

    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $url['rowid'];
      return $stats;
    }
  }
  if ( $stats['links'] ) createBackupBreakpoint( L( 'Broken URLencoded links' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['links'], 0 ) ) );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return $stats;
}

function updateUrlFromUpload( $params, $file )
{
  if ( !$file['tmp_name'] ) exit( 1 );

  global $sourcePath;
  backupFile( $params['urlID'], 'upload' );
  $pdo      = newPDO();
  $metaData = getMetaData( $params['urlID'] );

  $mime             = getMimeInfo( $file['type'] );
  $uplMimeType      = $file['type'];
  $uplFileSize      = filesize( $file['tmp_name'] );
  $uplFileExtension = $mime['extension'];
  $uplFileName      = sprintf( '%s/%s.%08d.%s', substr( convertPathToFilename( $metaData['request_uri'] ), 0, 1 ), convertPathToFilename( $metaData['request_uri'] ), $metaData['rowid'], $uplFileExtension );

  unlink( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'] );
  move_uploaded_file( $file['tmp_name'], $sourcePath . DIRECTORY_SEPARATOR . $mime['folder'] . DIRECTORY_SEPARATOR . $uplFileName );

  $stmt = $pdo->prepare( "UPDATE structure SET folder = :folder, filename = :filename, mimetype = :mimetype, filesize = :filesize WHERE rowid = :rowid" );
  $stmt->execute( [
    'folder'   => $mime['folder'],
    'filename' => $uplFileName,
    'mimetype' => $uplMimeType,
    'filesize' => $uplFileSize,
    'rowid'    => $metaData['rowid'],
  ] );
  exit( 0 );
}

function updateUrlSettings( $settings )
{
  global $sourcePath;
  backupFile( $settings['urlID'], 'settings' );
  $metaData = getMetaData( $settings['urlID'] );

  if ( encodePath( $settings['request_uri'] ) == $metaData['request_uri'] ) {
    $settings['filename'] = $metaData['filename'];
  } else {
    $mime = getMimeInfo( $settings['mimetype'] );
    rename( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'], $sourcePath . DIRECTORY_SEPARATOR . $mime['folder'] . DIRECTORY_SEPARATOR . sprintf( '%s/%s.%08d.%s', substr( convertPathToFilename( $settings['request_uri'] ), 0, 1 ), convertPathToFilename( $settings['request_uri'] ), $metaData['rowid'], $mime['extension'] ) );
    $settings['filename'] = sprintf( '%s/%s.%08d.%s', substr( convertPathToFilename( $settings['request_uri'] ), 0, 1 ), convertPathToFilename( $settings['request_uri'] ), $metaData['rowid'], $mime['extension'] );
  }
  $settings = array_merge( $metaData, $settings );

  $pdo  = newPDO();
  $stmt = $pdo->prepare( 'UPDATE structure SET url = :protocol || "://" || :hostname || :request_uri, hostname = :hostname, protocol = :protocol, request_uri = :request_uri, filename = :filename, mimetype = :mimetype, charset = :charset, enabled = :enabled, redirect = :redirect, filetime = :filetime WHERE rowid = :rowid' );

  $stmt->execute( [
    'rowid'       => $settings['urlID'],
    'protocol'    => $settings['protocol'],
    'hostname'    => $settings['hostname'],
    'request_uri' => encodePath( $settings['request_uri'] ),
    'filename'    => $settings['filename'],
    'mimetype'    => $settings['mimetype'],
    'charset'     => $settings['charset'],
    'enabled'     => $settings['enabled'],
    'redirect'    => encodeUrl( $settings['redirect'] ),
    'filetime'    => $settings['filetime'],
  ] );
}

function updateViewport( $params, $taskOffset = 0 )
{
  global $taskIncomplete;
  global $taskIncompleteOffset;
  global $taskStats;
  global $sourcePath;
  global $ACMS;

  $customViewport = !empty( $params['viewport_value'] ) ? trim( $params['viewport_value'] ) : 'width=device-width, initial-scale=1';
  $overwrite      = !empty( $params['overwrite'] ) ? 1 : 0;

  $stats = array_merge( ['links' => 0, 'pages' => 0, 'processed' => 0, 'total' => 0, 'time' => 0], unserialize( $taskStats ) );

  if ( function_exists( 'libxml_use_internal_errors' ) ) libxml_use_internal_errors( true );
  $pdo = newPDO();
  if ( empty( $stats['total'] ) ) $stats['total'] = intval( $pdo->query( "SELECT COUNT(1) FROM structure WHERE mimetype = 'text/html'" )->fetchColumn() );
  $stmt = $pdo->prepare( "SELECT rowid, * FROM structure WHERE mimetype = 'text/html' AND rowid > :taskOffset ORDER BY rowid" );
  $stmt->execute( ['taskOffset' => $taskOffset] );
  while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
    $updatedUrls = 0;
    $createTag   = 0;
    $file        = $sourcePath . DIRECTORY_SEPARATOR . $url['folder'] . DIRECTORY_SEPARATOR . $url['filename'];
    if ( !is_file( $file ) ) continue;
    $html = file_get_contents( $file );
    if ( !strlen( $html ) ) continue;
    $html = convertEncoding( convertHtmlEncoding( $html, 'utf-8', $url['charset'] ), 'html-entities', 'utf-8' );
    unset( $dom );
    $dom                      = new DOMDocument();
    $dom->formatOutput        = true;
    $dom->documentURI         = $url['url'];
    $dom->strictErrorChecking = false;
    $dom->encoding            = 'utf-8';
    if ( defined( 'LIBXML_HTML_NOIMPLIED' ) && defined( 'LIBXML_HTML_NODEFDTD' ) ) {
      $dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    } else {
      $dom->loadHTML( $html );
    }

    $metaTags = $dom->getElementsByTagName( 'meta' );
    for ( $n = $metaTags->length - 1; $n >= 0; --$n ) {
      $nameAttribute    = $metaTags->item( $n )->getAttribute( 'name' );
      $contentAttribute = $metaTags->item( $n )->getAttribute( 'content' );
      if ( strtolower( $nameAttribute ) == 'viewport' ) {
        if ( $overwrite && strtolower( $contentAttribute ) != strtolower( $customViewport ) ) {
          $metaTags->item( $n )->setAttribute( 'content', $customViewport );
          $updatedUrls++;
        } else {
          $createTag = 1;
        }
      }
    }


    if ( !$createTag && !$updatedUrls ) {
      $metaTag = $dom->createElement( 'meta' );
      $metaTag->setAttribute( 'name', 'viewport' );
      $metaTag->setAttribute( 'content', $customViewport );
      if ( $metaTags->length ) {
        $metaTags->item( 0 )->parentNode->insertBefore( $metaTag, $metaTags->item( 0 ) );
      } else {
        $headTag = $dom->getElementsByTagName( 'head' );
        if ( $headTag->length ) {
          if ( $headTag->item( 0 )->hasChildNodes() ) {
            $headTag->item( 0 )->insertBefore( $metaTag, $headTag->item( 0 )->firstChild );
          } else {
            $headTag->item( 0 )->appendChild( $metaTag );
          }
        }
      }
      $updatedUrls++;
    }


    if ( $updatedUrls ) {
      backupFile( $url['rowid'], 'edit' );
      file_put_contents( $file, convertHtmlEncoding( html_entity_decode( $dom->saveHTML() ), $url['charset'], 'utf-8' ) );
      updateFilesize( $url['rowid'], filesize( $file ) );
      $stats['links']++;
    }
    $stats['pages']++;
    $stats['processed']++;

    if ( $ACMS['ACMS_TIMEOUT'] && ( microtime( true ) - ACMS_START_TIME ) > ( $ACMS['ACMS_TIMEOUT'] - 1 ) ) {
      $stats['time']        += microtime( true ) - ACMS_START_TIME;
      $taskStats            = serialize( $stats );
      $taskIncomplete       = true;
      $taskIncompleteOffset = $url['rowid'];
      return $stats;
    }
  }
  if ( $stats['links'] ) createBackupBreakpoint( L( 'Viewport meta tag' ) . '. ' . sprintf( L( 'Processed: %s' ), number_format( $stats['links'], 0 ) ) );

  $stats['time'] += microtime( true ) - ACMS_START_TIME;
  $taskStats     = serialize( $stats );
  return $stats;
}

function updateWebsiteWww( $www = 0 )
{
  global $uuidSettings;
  $updatedUrls = 0;
  $pdo         = newPDO();
  $pdo2        = newPDO();
  if ( !empty( $uuidSettings['non-www'] ) ) {
    if ( !$www ) return $updatedUrls;
    $stmt = $pdo->prepare( "UPDATE structure SET url = replace(url,'://' || :domain, '://www.' || :domain)" );
    $stmt->execute( ['domain' => $uuidSettings['domain']] );
    $stmt = $pdo->prepare( "UPDATE structure SET hostname = 'www.' || :domain WHERE hostname = :domain" );
    $stmt->execute( ['domain' => $uuidSettings['domain']] );
    $updatedUrls += $stmt->rowCount();
    $pdo->exec( "DELETE FROM settings WHERE param = 'non-www'" );
    $pdo->exec( "INSERT INTO settings (param,value) VALUES ('www',1)" );
  } elseif ( !empty( $uuidSettings['www'] ) ) {
    if ( $www ) return $updatedUrls;
    $stmt = $pdo->prepare( "UPDATE structure SET url = replace(url,'://www.' || :domain, '://' || :domain)" );
    $stmt->execute( ['domain' => $uuidSettings['domain']] );
    $stmt = $pdo->prepare( "UPDATE structure SET hostname = :domain WHERE hostname = 'www.' || :domain" );
    $stmt->execute( ['domain' => $uuidSettings['domain']] );
    $updatedUrls += $stmt->rowCount();
    $pdo->exec( "DELETE FROM settings WHERE param = 'www'" );
    $pdo->exec( "INSERT INTO settings (param,value) VALUES ('non-www',1)" );
  } else {
    if ( $www ) {
      $stmt = $pdo->prepare( "SELECT rowid, url, hostname, filetime FROM structure WHERE hostname = :domain" );
      $stmt->execute( ['domain' => $uuidSettings['domain']] );
      while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
        $updatedUrls++;
        if ( $urlExistId = urlExists( preg_replace( '~://~', '://www.', $url['url'], 1 ) ) ) {
          $urlExist = getMetaData( $urlExistId );
          if ( $url['filetime'] < $urlExist['filetime'] ) {
            removeUrl( $url['rowid'] );
          } else {
            removeUrl( $urlExistId );
            $url             = getMetaData( $url['rowid'] );
            $url['urlID']    = $url['rowid'];
            $url['url']      = preg_replace( '~://~', '://www.', $urlExist['url'], 1 );
            $url['hostname'] = "www." . $urlExist['hostname'];
            updateUrlSettings( $url );
          }
        } else {
          $stmt2 = $pdo2->prepare( "UPDATE structure SET url = replace(url,'://www.' || :domain, '://' || :domain) WHERE rowid = :rowid" );
          $stmt2->execute( ['domain' => $uuidSettings['domain'], 'rowid' => $url['rowid']] );
          $stmt2 = $pdo2->prepare( "UPDATE structure SET hostname = :domain WHERE rowid = :rowid" );
          $stmt2->execute( ['domain' => $uuidSettings['domain'], 'rowid' => $url['rowid']] );
        }
      }
      $pdo->exec( "DELETE FROM settings WHERE param = 'www'" );
      $pdo->exec( "INSERT INTO settings (param,value) VALUES ('non-www',1)" );
    } else {
      $stmt = $pdo->prepare( "SELECT rowid, url, hostname, filetime FROM structure WHERE hostname = 'www.' || :domain" );
      $stmt->execute( ['domain' => $uuidSettings['domain']] );
      while ( $url = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
        $updatedUrls++;
        if ( $urlExistId = urlExists( preg_replace( '~://www\.~', '://', $url['url'], 1 ) ) ) {
          $urlExist = getMetaData( $urlExistId );
          if ( $url['filetime'] < $urlExist['filetime'] ) {
            removeUrl( $url['rowid'] );
          } else {
            removeUrl( $urlExistId );
            $url             = getMetaData( $url['rowid'] );
            $url['urlID']    = $url['rowid'];
            $url['url']      = $urlExist['url'];
            $url['hostname'] = $urlExist['hostname'];
            updateUrlSettings( $url );
          }
        } else {
          $stmt2 = $pdo2->prepare( "UPDATE structure SET url = replace(url,'://www.' || :domain, '://' || :domain) WHERE rowid = :rowid" );
          $stmt2->execute( ['domain' => $uuidSettings['domain'], 'rowid' => $url['rowid']] );
          $stmt2 = $pdo2->prepare( "UPDATE structure SET hostname = :domain WHERE rowid = :rowid" );
          $stmt2->execute( ['domain' => $uuidSettings['domain'], 'rowid' => $url['rowid']] );
        }
      }
      $pdo->exec( "DELETE FROM settings WHERE param = 'www'" );
      $pdo->exec( "INSERT INTO settings (param,value) VALUES ('non-www',1)" );
    }
  }
  return $updatedUrls;
}

function upgradeSchema()
{
  $settings = getSettings();
  if ( empty( $settings['schema'] ) || version_compare( $settings['schema'], getSchemaLatest(), '<' ) ) {
    // UPGRADE TO 1.0.2: structure <+depth +metrics>
    sqlExec( "ALTER TABLE structure ADD depth INTEGER DEFAULT 0" );
    sqlExec( "ALTER TABLE structure ADD metrics TEXT DEFAULT ''" );
    updateSettings( 'schema', '1.0.2' );
  }
}

function uploadAcmsJson( $file )
{
  global $ACMS;
  global $sourcePath;
  if ( !isset( $file['error'] ) || $file['error'] > 0 ) return;
  $settings = json_decode( file_get_contents( $file['tmp_name'] ), true );
  if ( json_last_error() !== JSON_ERROR_NONE ) return;
  if ( !is_array( $settings ) && !count( $settings ) ) return;
  $settings = array_filter( $settings, function ( $k ) {
    return preg_match( '~^ACMS_~i', $k );
  }, ARRAY_FILTER_USE_KEY );
  $ACMS     = array_merge( $ACMS, $settings );
  $filename = $sourcePath . DIRECTORY_SEPARATOR . '.acms.settings.json';
  file_put_contents( $filename, json_encode( $ACMS, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
  return true;
}

function uploadCustomFile( $file )
{
  if ( inSafeMode() && preg_match( '~[<]([?%]|[^>]*script\b[^>]*\blanguage\b[^>]*\bphp\b)~is', file_get_contents( $file['tmp_name'] ) ) ) return false;
  $includesPath = createDirectory( 'includes' );
  if ( empty( $file['name'] ) || empty( basename( $file['name'] ) ) ) {
    $mimeInfo     = getMimeInfo( $file['type'] );
    $file['name'] = date( 'Y-m-d_H-m-s' ) . '.' . $mimeInfo['extension'];
  }
  move_uploaded_file( $file['tmp_name'], $includesPath . DIRECTORY_SEPARATOR . basename( $file['name'] ) );
}

function uploadImport( $file )
{
  $importsPath = createDirectory( 'imports' );
  $importInfo  = getImportInfo( $file['tmp_name'], true );
  if ( !$importInfo ) return;
  if ( !empty( $file['name'] ) ) $safeFilename = getSafeFilename( $file['name'], 'zip', false );
  if ( strlen( $safeFilename ) && file_exists( $importsPath . DIRECTORY_SEPARATOR . $safeFilename ) ) {
    while ( true ) {
      $safeFilename = getSafeFilename( $safeFilename, 'zip', true );
      if ( !file_exists( $importsPath . DIRECTORY_SEPARATOR . $safeFilename ) ) break;
    }
  }
  if ( !strlen( $safeFilename ) && file_exists( $importsPath . DIRECTORY_SEPARATOR . $importInfo['info']['settings']['uuid'] . ".zip" ) ) {
    while ( true ) {
      $safeFilename = getSafeFilename( $importInfo['info']['settings']['uuid'] . ".zip" );
      if ( !file_exists( $importsPath . DIRECTORY_SEPARATOR . $safeFilename ) ) break;
    }
  }
  move_uploaded_file( $file['tmp_name'], $importsPath . DIRECTORY_SEPARATOR . $safeFilename );
  return $importInfo['info']['settings']['uuid'];
}

function uploadLoaderJson( $file )
{
  global $sourcePath;
  if ( !isset( $file['error'] ) || $file['error'] > 0 ) return;
  $settings = json_decode( file_get_contents( $file['tmp_name'] ), true );
  if ( json_last_error() !== JSON_ERROR_NONE ) return;
  if ( !is_array( $settings ) && !count( $settings ) ) return;
  $settings = array_filter( $settings, function ( $k ) {
    return preg_match( '~^ARCHIVARIX_~i', $k );
  }, ARRAY_FILTER_USE_KEY );
  if ( !empty( $settings['ARCHIVARIX_CUSTOM_FILES'] ) ) {
    foreach ( $settings['ARCHIVARIX_CUSTOM_FILES'] as $customFile ) {
      createCustomFile( ['filename' => $customFile['filename'], 'content' => base64_decode( $customFile['content'] )] );
    }
  }
  unset( $settings['ARCHIVARIX_CUSTOM_FILES'] );
  $LOADER   = loadLoaderSettings();
  $LOADER   = array_merge( $LOADER, $settings );
  $filename = $sourcePath . DIRECTORY_SEPARATOR . '.loader.settings.json';
  file_put_contents( $filename, json_encode( $LOADER, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
  return true;
}

function urlExists( $urls )
{
  $pdo = newPDO();
  if ( is_array( $urls ) ) {
    $sqlVariants    = '';
    $sqlVariantsArr = [];
    foreach ( $urls as $key => $url ) {
      $sqlVariants                  .= " OR url = :url_{$key} ";
      $sqlVariantsArr["url_{$key}"] = $url;
    }
    $stmt = $pdo->prepare( "SELECT rowid FROM structure WHERE 0 {$sqlVariants} LIMIT 1" );
    $stmt->execute(
      $sqlVariantsArr
    );
  } else {
    $stmt = $pdo->prepare( "SELECT rowid FROM structure WHERE url = :url LIMIT 1" );
    $stmt->execute( [
      'url' => $urls,
    ] );
  }
  $id = $stmt->fetchColumn();
  if ( $id ) {
    return $id;
  }
}

function zipDirectory( $source, $destination )
{
  if ( !extension_loaded( 'zip' ) || !file_exists( $source ) ) {
    return false;
  }
  $zip = new ZipArchive();
  if ( !$zip->open( $destination, ZIPARCHIVE::CREATE ) ) {
    return false;
  }
  $source = str_replace( '\\', '/', realpath( $source ) );
  if ( is_dir( $source ) === true ) {
    $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );
    foreach ( $files as $file ) {
      $file = str_replace( '\\', '/', $file );
      if ( in_array( substr( $file, strrpos( $file, '/' ) + 1 ), array('.', '..') ) )
        continue;
      $file = realpath( $file );

      if ( is_dir( $file ) === true ) {
        $zip->addEmptyDir( str_replace( $source . '/', '', $file . '/' ) );
      } else if ( is_file( $file ) === true ) {
        //$zip->addFromString( str_replace( $source . '/', '', $file ), file_get_contents( $file ) );
        $zip->addFile( $file, str_replace( $source . '/', '', $file ) );
      }
    }
  } else if ( is_file( $source ) === true ) {
    //$zip->addFromString( basename( $source ), file_get_contents( $source ) );
    $zip->addFile( $source, basename( $source ) );
  }
  return $zip->close();
}

function compare( $string1, $string2, $compareCharacters = false )
{
  $start = 0;
  if ( $compareCharacters ) {
    $end1      = strlen( $string1 ) - 1;
    $end2      = strlen( $string2 ) - 1;
    $sequence1 = $string1;
    unset( $string1 );
    $sequence2 = $string2;
    unset( $string2 );
  } else {
    $sequence1 = preg_split( '~(*BSR_ANYCRLF)\R~', $string1 );
    unset( $string1 );
    $sequence2 = preg_split( '~(*BSR_ANYCRLF)\R~', $string2 );
    unset( $string2 );
    $end1 = count( $sequence1 ) - 1;
    $end2 = count( $sequence2 ) - 1;
  }

  while ( $start <= $end1 && $start <= $end2
    && $sequence1[$start] == $sequence2[$start] ) {
    $start++;
  }

  while ( $end1 >= $start && $end2 >= $start
    && $sequence1[$end1] == $sequence2[$end2] ) {
    $end1--;
    $end2--;
  }

  $table = computeTable( $sequence1, $sequence2, $start, $end1, $end2 );

  $partialDiff = generatePartialDiff( $table, $sequence1, $sequence2, $start );

  $diff = [];
  for ( $index = 0; $index < $start; $index++ ) {
    $diff[] = [$sequence1[$index], 0];
  }
  while ( count( $partialDiff ) > 0 ) $diff[] = array_pop( $partialDiff );
  for ( $index = $end1 + 1;
        $index < ( $compareCharacters ? strlen( $sequence1 ) : count( $sequence1 ) );
        $index++ ) {
    $diff[] = [$sequence1[$index], 0];
  }

  return $diff;
}


function compareFiles( $file1, $file2, $compareCharacters = false )
{
  return compare(
    file_get_contents( $file1 ),
    file_get_contents( $file2 ),
    $compareCharacters );
}

function computeTable( $sequence1, $sequence2, $start, $end1, $end2 )
{
  $length1 = $end1 - $start + 1;
  $length2 = $end2 - $start + 1;
  $table   = [array_fill( 0, $length2 + 1, 0 )];
  for ( $index1 = 1; $index1 <= $length1; $index1++ ) {
    $table[$index1] = [0];
    for ( $index2 = 1; $index2 <= $length2; $index2++ ) {
      if ( $sequence1[$index1 + $start - 1]
        == $sequence2[$index2 + $start - 1] ) {
        $table[$index1][$index2] = $table[$index1 - 1][$index2 - 1] + 1;
      } else {
        $table[$index1][$index2] =
          max( $table[$index1 - 1][$index2], $table[$index1][$index2 - 1] );
      }
    }
  }
  return $table;
}

function generatePartialDiff( $table, $sequence1, $sequence2, $start )
{
  $diff   = [];
  $index1 = count( $table ) - 1;
  $index2 = count( $table[0] ) - 1;
  while ( $index1 > 0 || $index2 > 0 ) {
    if ( $index1 > 0 && $index2 > 0
      && $sequence1[$index1 + $start - 1]
      == $sequence2[$index2 + $start - 1] ) {
      $diff[] = [$sequence1[$index1 + $start - 1], 0];
      $index1--;
      $index2--;
    } elseif ( $index2 > 0
      && $table[$index1][$index2] == $table[$index1][$index2 - 1] ) {
      $diff[] = [$sequence2[$index2 + $start - 1], 2];
      $index2--;
    } else {
      $diff[] = [$sequence1[$index1 + $start - 1], 1];
      $index1--;
    }
  }
  return $diff;
}

function compareToHTML( $diff, $separator = '<br>' )
{
  $html = '';
  foreach ( $diff as $line ) {
    switch ( $line[1] ) {
      case 0 :
        //continue 2; // no unchanged
        $element = 'span';
        break;
      case 1    :
        $element = 'del';
        break;
      case 2   :
        $element = 'ins';
        break;
    }
    $html .=
      '<' . $element . '>'
      . htmlspecialchars( $line[0] )
      . '</' . $element . '>';
    $html .= $separator;
  }
  return $html;
}

// START
isset( $section ) || $section = null;

if ( $sourcePath === false ) {
  $dsn                     = 'sqlite::memory:';
  $uuidSettings            = ['uuid' => '', 'domain' => ''];
  $section                 = 'install';
  $integrationPrerequisite = checkIntegrationPrerequisite();
} else {
  $dsn          = getDSN();
  $uuidSettings = getSettings();
}

if ( $ACMS['ACMS_DISABLE_HISTORY'] ) {
  if ( !empty( getBackups( 'stats' )['total'] ) ) {
    deleteBackups( ['all' => 1] );
  }
}

if ( !$extensionsAllowed ) $section = '';

$urlOffsets           = !empty( $_POST['urlOffsets'] ) ? unserialize( $_POST['urlOffsets'] ) : [];
$urlsTotal            = [];
$taskIncompleteOffset = !empty( $_POST['taskOffset'] ) ? $_POST['taskOffset'] : 0;
$taskStats            = !empty( $_POST['taskStats'] ) ? trim( $_POST['taskStats'] ) : serialize( ['time' => 0] );

if ( !checkPhpVersion() ) {
  $noProceed = true;
  addWarning( sprintf( L( 'PHP 5.6 or newer is required. The version of PHP you\'re running is %s.' ), PHP_VERSION ), 4, L( 'System check' ) );
}

if ( $section == 'install' && !isset( $_POST['action'] ) ) {
  clearstatcache();
  $missingExtensions = getMissingExtensions( ['curl', 'json', 'pcre', 'pdo_sqlite', 'zip'] );

  if ( !is_writable( __DIR__ ) ) {
    $noProceed = true;
    addWarning( L( 'The working directory does not have write permissions.' ) . ' ' . ( fileowner( __DIR__ ) == 0 || filegroup( __DIR__ ) == 0 ? L( 'It belongs to the "root" (administrator) level user. Please, fix the directory/files owner and a group, not the write permissions.' ) : '' ), 4, L( 'System check' ) );
  }
  if ( !is_writable( session_save_path() ) ) {
    addWarning( sprintf( L( 'The directory of session.save_path that points to %s does not have write permissions. It may create problems for keeping sessions.' ), session_save_path() ), 3, L( 'System check' ) );
  }
  if ( !in_array( 'pdo_sqlite', $missingExtensions ) && version_compare( getSqliteVersion(), '3.7.0', '<' ) ) {
    $noProceed = true;
    addWarning( sprintf( L( 'Installation can only work with SQLite version 3.7.0 or newer. Your pdo_sqlite uses version %s that is very outdated.' ), getSqliteVersion() ), 4, L( 'Initial installation' ) );
  }
  if ( !empty( $missingExtensions ) ) {
    $noProceed = true;
    addWarning( sprintf( L( 'This tool requires the following PHP extensions to be installed: %s.' ), implode( ', ', $missingExtensions ) ), 4, L( 'Initial installation' ) );
  }

  if ( PHP_SAPI === 'fpm-fcgi' ) {
    addWarning( L( 'Attention! Apache web-server in FastCGI mode is recommended.' ) . ' ' . L( 'If you use NGINX + PHP-FPM, make sure to add an additional rule to pass ALL requests for non-existing files and directories to /index.php.' ), 3, L( 'System check' ) );
  }

  addWarning( L( 'The working website is missing or not installed yet.' ) . ' ' . L( 'Please upload files to your hosting or use the form below to import/upload an existing website.' ), 2, L( 'Initial installation' ) );
}

if ( $section == 'install' && isset( $_POST['action'] ) && checkXsrf() ) {
  switch ( $_POST['action'] ) {
    case 'download.serial.install' :
      $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . '.content.tmp';
      if ( !file_exists( $sourcePath ) ) {
        mkdir( $sourcePath, 0777, true );
      }
      $uuid = downloadFromSerial( $_POST['uuid'], $taskIncompleteOffset );
      if ( $uuid ) {
        addWarning( sprintf( L( 'The website zip file %s is downloaded.' ), $uuid ), 1, L( 'An import tool' ) );
      } else {
        addWarning( L( 'Could not download the website zip.' ), 4, L( 'An import tool' ) );
      }
      $sourcePath = false;
      break;
    case 'import.upload.install' :
      $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . '.content.tmp';
      if ( !file_exists( $sourcePath ) ) {
        mkdir( $sourcePath, 0777, true );
      }
      if ( !empty( $_FILES['import_file'] ) && !$_FILES['import_file']['error'] ) {
        $uuid = uploadImport( $_FILES['import_file'] );
        if ( $uuid ) {
          addWarning( sprintf( L( 'The website zip file %s is downloaded.' ), $uuid ), 1, L( 'An import tool' ) );
        } else {
          addWarning( L( 'The uploaded .ZIP file has an incorrect structure' ), 4, L( 'An import tool' ) );
        }
      }
      $sourcePath = false;
      break;
    case 'import.remove.install' :
      $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . '.content.tmp';
      if ( !empty( $_POST['filename'] ) && removeImport( $_POST['filename'] ) ) {
        addWarning( sprintf( L( 'The website zip file %s is removed.' ), $_POST['filename'] ), 1, L( 'An import tool' ) );
      }
      $sourcePath = false;
      break;
    case 'import.perform.install' :
      if ( $taskIncompleteOffset == 0 ) {
        $sourcePath     = __DIR__ . DIRECTORY_SEPARATOR . '.content.tmp';
        $importFileName = $sourcePath . DIRECTORY_SEPARATOR . 'imports' . DIRECTORY_SEPARATOR . $_POST['filename'];
        $import         = getImportInfo( $sourcePath . DIRECTORY_SEPARATOR . 'imports' . DIRECTORY_SEPARATOR . $_POST['filename'], true );
        $sourcePath     = createStructure( $import );
        rename( $import['zip_path'], $sourcePath . DIRECTORY_SEPARATOR . 'imports' . DIRECTORY_SEPARATOR . $import['filename'] );
        $importSettings              = !empty( $_POST['settings'] ) ? $_POST['settings'] : [];
        $importSettings['overwrite'] = 'all';
        $dsn                         = getDSN();
        $uuidSettings                = getSettings();
        deleteDirectory( __DIR__ . DIRECTORY_SEPARATOR . '.content.tmp' . DIRECTORY_SEPARATOR );
        $_POST['action']   = 'import.perform';
        $_POST['settings'] = $importSettings;
        $installMode       = !empty( $_POST['settings']['integration'] ) ? 'integration' : 'clean';
        putLoader( __DIR__, $installMode );
        loadAcmsSettings();
      }
      break;
    case 'rename.cms' :
      if ( !$accessAllowed ) break;
      $newAcmsFilename = getRandomString( 8 ) . ".php";
      rename( __FILE__, __DIR__ . DIRECTORY_SEPARATOR . $newAcmsFilename );
      header( "Location: ./{$newAcmsFilename}" );
      http_response_code( 302 );
      exit( 0 );
      break;
  }
}

if ( isset( $_POST['action'] ) &&
  $accessAllowed &&
  $extensionsAllowed &&
  $sourcePath &&
  checkXsrf() ) {
  if ( !inSafeMode() ) {
    switch ( $_POST['action'] ) {
      case 'api.key.set' :
        if ( setApiKey( $_POST ) ) addWarning( L( 'Public key for API is set.' ), 1, L( 'API key' ) );
        else addWarning( L( 'Public key is invalid. Make sure it is in PEM format.' ), 4, L( 'API key' ) );
        $section = 'tools';
        break;
      case 'api.key.remove' :
        removeApiKey();
        addWarning( L( 'Public key removed.' ), 1, L( 'API key' ) );
        $section = 'tools';
        break;
      case 'download.acms.json' :
        header( "Content-Type: application/json" );
        header( "Content-disposition: attachment; filename=\"acms.settings.json\"" );
        echo json_encode( loadAcmsSettings(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        exit( 0 );
        break;
      case 'upload.acms.json' :
        if ( uploadAcmsJson( $_FILES['file'] ) ) {
          addWarning( L( 'New settings are applied.' ) . '<br>' . L( 'If settings had a password, you will see a login form on the next click.' ), 1, L( 'Settings' ) );
        } else {
          addWarning( L( 'Could not apply the file.' ), 4, L( 'Settings' ) );
        }
        $section = 'settings';
        break;
      case 'download.loader.json' :
        header( "Content-Type: application/json" );
        header( "Content-disposition: attachment; filename=\"loader.settings.json\"" );
        echo packLoaderSettings();
        exit( 0 );
        break;
      case 'upload.loader.json' :
        if ( uploadLoaderJson( $_FILES['file'] ) ) {
          addWarning( L( 'New settings are applied.' ), 1, L( 'Settings' ) );
        } else {
          addWarning( L( 'Could not apply the file.' ), 4, L( 'Settings' ) );
        }
        $section    = 'settings';
        $subSection = 'loader';
        break;
      case 'fun.convert.website' :
        funConvertWebsite( $_POST['options'] );
        addWarning( L( 'Done. Files are saved to the "export" directory inside .content.xxxxxxxx' ), 1, L( 'Flat-File version' ) );
        $section = 'tools';
        break;
      case 'fun.convert.website.delete' :
        deleteDirectory( $sourcePath . DIRECTORY_SEPARATOR . 'exports' );
        $section = 'tools';
        break;
      case 'update.urls.meta' :
        $updatedUrls = updateUrlsMeta( $taskIncompleteOffset );
        addWarning( sprintf( L( 'Done. Metadata was updated for %d URLs.' ), $updatedUrls['processed'] ), 1, L( 'Update metadata in DB' ) );
        $section = 'tools';
        break;
      case 'fun.import.md' :
        funMD( $_POST['params'], $taskIncompleteOffset );
        addWarning( L( 'Done.' ), 1 );
        $section = 'tools';
        break;
      case 'edit.htaccess' :
        if ( !empty( $_POST['htaccess'] ) ) {
          updateHtaccessFile( $_POST['htaccess'] );
          addWarning( sprintf( L( 'File %s is updated successfully.' ), '.htaccess' ), 1, L( '.htaccess editor' ) );
        }
        $section = 'htaccess';
        break;
    }
  }
  switch ( $_POST['action'] ) {
    case 'settings.view' :
      $section = 'settings';
      break;
    case 'set.loader.settings' :
      setLoaderSettings( $_POST['settings'] );
      addWarning( L( 'Settings were updated.' ), 1, L( 'Settings' ) );
      $section    = 'settings';
      $subSection = 'loader';
      break;
    case 'delete.custom.file' :
      if ( deleteCustomFile( $_POST['filename'] ) ) {
        addWarning( sprintf( L( 'File %s is deleted.' ), $_POST['filename'] ), 1, L( 'Custom Files' ) );
      } else {
        addWarning( sprintf( L( 'Could not delete %s file.' ), $_POST['filename'] ), 4, L( 'Custom Files' ) );
      }
      $section    = 'settings';
      $subSection = 'custom';
      break;
    case 'edit.custom.file' :
      $customFileMeta = getCustomFileMeta( $_POST['filename'] );
      $section        = 'settings';
      $subSection     = 'custom';
      break;
    case 'create.custom.file' :
      if ( createCustomFile( $_POST ) ) {
        addWarning( sprintf( L( 'File %s is created successfully.' ), $_POST['filename'] ), 1, L( 'Custom Files' ) );
      } else {
        addWarning( sprintf( L( 'Could not create %s file.' ), $_POST['filename'] ), 4, L( 'Custom Files' ) );
      }
      $section    = 'settings';
      $subSection = 'custom';
      break;
    case 'update.custom.file' :
      if ( updateCustomFile( $_POST ) ) {
        addWarning( sprintf( L( 'File %s is updated successfully.' ), $_POST['filename'] ), 1, L( 'Custom Files' ) );
      } else {
        addWarning( sprintf( L( 'Could not update %s file.' ), $_POST['filename'] ), 4, L( 'Custom Files' ) );
      }
      $section    = 'settings';
      $subSection = 'custom';
      break;
    case 'upload.custom.file' :
      if ( !empty( $_FILES['file'] ) && !$_FILES['file']['error'] ) {
        uploadCustomFile( $_FILES['file'] );
        exit( 0 );
      }
      $section    = 'settings';
      $subSection = 'custom';
      break;
    case 'update.system' :
      updateSystem();
      break;
    case 'install.loader' :
      $loaderInfo = getLoaderInfo();
      if ( empty( $loaderInfo['filename'] ) ) {
        $installMode = checkIntegrationPrerequisite() ? 'integration' : 'clean';
        putLoader( __DIR__, $installMode );
      }
      break;
    case 'update.url.settings' :
      updateUrlSettings( $_POST );
      break;
    case 'update.url.content' :
      saveFile( $_POST['urlID'] );
      break;
    case 'update.url.upload' :
      updateUrlFromUpload( $_POST, $_FILES['file'] );
      exit( 0 );
      break;
    case 'download.url' :
      $metaData = getMetaData( $_POST['urlID'] );
      header( 'Content-Type: application/octet-stream' );
      header( 'Content-Disposition: attachment; filename=' . pathinfo( $metaData['request_uri'], PATHINFO_BASENAME ) );
      header( 'Expires: 0' );
      header( 'Cache-Control: must-revalidate' );
      header( 'Pragma: public' );
      header( 'Content-Length: ' . filesize( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'] ) );
      readfile( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'] );
      exit( 0 );
      break;
    case 'remove.url' :
      removeUrl( $_POST['urlID'] );
      break;
    case 'clone.url' :
      $cloneID = cloneUrl( $_POST );
      if ( $cloneID ) {
        $_POST['urlID'] = $cloneID;
        $_POST['show']  = 'edit.url';
      }
      break;
    case 'create.url' :
      $createID = createUrl( $_POST );
      if ( $createID ) {
        $_POST['urlID'] = $createID;
        $_POST['show']  = 'edit.url';
      }
      break;
    case 'searchreplace.code' :
      $searchResults = doSearchReplaceCode( $_POST, $taskIncompleteOffset );
      if ( empty( $searchResults ) && $_POST['type'] != 'new' && !isset( $_POST['perform'] ) ) {
        addWarning( L( 'No matches are found' ), 4, L( 'Search & Replace' ) );
      }
      if ( $_POST['type'] == 'replace' && isset( $_POST['perform'] ) && $_POST['perform'] == 'replace' ) {
        addWarning( L( 'All replacements have been written to files!' ), 1, L( 'Search & Replace' ) );
      }
      $section = 'search';
      break;
    case 'searchreplace.url' :
      $searchResults = doSearchReplaceUrls( $_POST, $taskIncompleteOffset );
      if ( empty( $searchResults ) && $_POST['type'] != 'new' && !isset( $_POST['perform'] ) ) {
        addWarning( L( 'No matches are found' ), 4, L( 'Search & Replace' ) );
      }
      if ( $_POST['type'] == 'replace' && isset( $_POST['perform'] ) && $_POST['perform'] == 'replace' ) {
        addWarning( L( 'All replacements have been written to files!' ), 1, L( 'Search & Replace' ) );
      }
      $section = 'search';
      break;
    case 'history.edit' :
      $backups = getBackups();
      if ( empty( $backups ) ) addWarning( L( 'No backups are found.' ), 2, L( 'History' ) );
      if ( !empty( $backups ) ) addWarning( sprintf( L( 'Current backups take %s of space. Don\'t forget to disable or delete all backups if you don\'t need them.' ), getHumanSize( getDirectorySize( $sourcePath . DIRECTORY_SEPARATOR . 'backup' ) ) ), 2, L( 'History' ) );
      if ( $ACMS['ACMS_DISABLE_HISTORY'] ) addWarning( L( 'Backups are disabled in CMS settings.' ), 3, L( 'History' ) );
      $section = 'backups';
      break;
    case 'history.recover' :
      recoverBackup( $_POST, $taskIncompleteOffset );
      $backups = getBackups();
      if ( empty( $backups ) ) addWarning( L( 'No backups are found.' ), 2, L( 'History' ) );
      if ( !empty( $backups ) ) addWarning( sprintf( L( 'Current backups take %s of space. Don\'t forget to disable or delete all backups if you don\'t need them.' ), getHumanSize( getDirectorySize( $sourcePath . DIRECTORY_SEPARATOR . 'backup' ) ) ), 2, L( 'History' ) );
      if ( $ACMS['ACMS_DISABLE_HISTORY'] ) addWarning( L( 'Backups are disabled in CMS settings.' ), 3, L( 'History' ) );
      $section = 'backups';
      break;
    case 'history.recover.url' :
      recoverBackup( $_POST, $taskIncompleteOffset );
      break;
    case 'history.purge' :
      deleteBackups( $_POST );
      $backups = getBackups();
      if ( empty( $backups ) ) addWarning( L( 'No backups are found.' ), 2, L( 'History' ) );
      if ( !empty( $backups ) ) addWarning( sprintf( L( 'Current backups take %s of space. Don\'t forget to disable or delete all backups if you don\'t need them.' ), getHumanSize( getDirectorySize( $sourcePath . DIRECTORY_SEPARATOR . 'backup' ) ) ), 2, L( 'History' ) );
      if ( $ACMS['ACMS_DISABLE_HISTORY'] ) addWarning( L( 'Backups are disabled in CMS settings.' ), 3, L( 'History' ) );
      $section = 'backups';
      break;
    case 'history.breakpoint.create' :
      if ( createBackupBreakpoint( $_POST['name'] ) ) addWarning( L( 'A new breakpoint is created.' ), 1, L( 'History' ) );
      $backups = getBackups();
      $section = 'backups';
      break;
    case 'missing.clear' :
      clearMissingUrls();
      addWarning( L( 'The list is cleared.' ), 1, L( 'Missing URLs' ) );
      $section = 'missing.urls';
      break;
    case 'missing.show' :
      $missingUrls = getMissingUrls();
      if ( empty( $missingUrls ) ) addWarning( L( 'No missing URLs were detected during views of the website by visitors.' ), 2, L( 'Missing URLs' ) );
      $LOADER = loadLoaderSettings();
      if ( !$LOADER['ARCHIVARIX_CATCH_MISSING'] ) addWarning( L( 'Looks like this option is not enabled in Loader\'s Settings.' ), 3, L( 'Missing URLs' ) );
      $section = 'missing.urls';
      break;
    case 'change.page' :
      $urlOffsets[$_POST['domain']] = $_POST['page'];
      break;
    case 'tools.view' :
      $section = 'tools';
      break;
    case 'convert.utf8' :
      $converted = convertUTF8( $taskIncompleteOffset );
      addWarning( sprintf( L( 'The conversion of %d files into %s is complete.' ), $converted, 'UTF-8' ), 1, L( 'Website conversion to UTF-8' ) );
      $section = 'tools';
      break;
    case 'convert.webp' :
      $converted = convertWebp();
      addWarning( sprintf( L( 'The conversion of %d files into %s is complete.' ), $converted, 'WebP' ), 1, L( 'Images conversion to WebP' ) );
      $section = 'tools';
      break;
    case 'update.pages.depth' :
      $updatedPages = detectPagesDepth();
      addWarning( sprintf( L( 'Detected depth for %d pages. Found %d orphan pages.' ), $updatedPages['linked_pages'], $updatedPages['orphan_pages'] ), 1, L( 'Detect pages depth' ) );
      $section = 'tools';
      break;
    case 'broken.encoded.urls' :
      $updatedUrls = updateUrlEncoded( $taskIncompleteOffset );
      addWarning( sprintf( L( '%d encoded URLs in %d pages are updated.' ), $updatedUrls['links'], $updatedUrls['pages'] ), 1, L( 'Broken URLencoded links' ) );
      $section = 'tools';
      break;
    case 'update.viewport' :
      $updatedUrls = updateViewport( $_POST, $taskIncompleteOffset );
      addWarning( sprintf( L( 'The viewport set in %d pages.' ), $updatedUrls['links'] ), 1, L( 'Viewport meta tag' ) );
      $section = 'tools';
      break;
    case 'broken.links.remove' :
      $brokenLinks = removeBrokenLinks( $taskIncompleteOffset );
      if ( !empty( $brokenLinks['stats'] ) ) {
        $section      = 'table';
        $tableHeaders = ['url' => ['title' => 'URL'], 'count' => ['title' => 'Count']];
        $tableData    = &$brokenLinks['stats'];
      } else {
        addWarning( sprintf( L( '%d broken internal links in %d pages are removed.' ), $brokenLinks['links'], $brokenLinks['pages'] ), 1, L( 'Remove broken links' ) );
        $section = 'tools';
      }
      break;
    case 'broken.images.remove' :
      $brokenImages = removeBrokenImages( $taskIncompleteOffset );
      if ( !empty( $brokenImages['stats'] ) ) {
        $section      = 'table';
        $tableHeaders = ['url' => ['title' => 'URL'], 'count' => ['title' => 'Count']];
        $tableData    = &$brokenImages['stats'];
      } else {
        addWarning( sprintf( L( '%d broken internal images in %d pages are removed.' ), $brokenImages['images'], $brokenImages['pages'] ), 1, L( 'Remove broken images' ) );
        $section = 'tools';
      }
      break;
    case 'external.links.update' :
      $updatedLinks = updateExternalLinks( $_POST['attributes'], $taskIncompleteOffset );
      addWarning( sprintf( L( '%d external links in %d pages are updated.' ), $updatedLinks['links'], $updatedLinks['pages'] ), 1, L( 'Work with external links' ) );
      $section = 'tools';
      break;
    case 'external.links.remove' :
      $removedLinks = removeExternalLinks( $taskIncompleteOffset );
      addWarning( sprintf( L( '%d external links in %d pages are removed.' ), $removedLinks['links'], $removedLinks['pages'] ), 1, L( 'Work with external links' ) );
      $section = 'tools';
      break;
    case 'version.control.remove' :
      $removedVersionControl = removeVersionControl( $taskIncompleteOffset );
      addWarning( sprintf( L( '%d URLs are fixed, %d replaces in %d different pages are performed.' ), $removedVersionControl['urls'], $removedVersionControl['replaces'], $removedVersionControl['pages'] ), 1, L( 'Versions in CSS and JS' ) );
      $section = 'tools';
      break;
    case 'import.view' :
      $section = 'import';
      break;
    case 'import.remove' :
      if ( !empty( $_POST['filename'] ) && removeImport( $_POST['filename'] ) ) {
        addWarning( sprintf( L( 'The website zip file %s is removed.' ), $_POST['filename'] ), 1, L( 'An import tool' ) );
      }
      $section = 'import';
      break;
    case 'import.perform' :
      if ( importPerform( $_POST['filename'], $_POST['settings'], $taskIncompleteOffset ) ) {
        addWarning( L( 'The import is completed successfully.' ) . '<br>' . L( 'If the import contained a configuration file with a password, you will see a login form on the next click.' ), 1, L( 'An import tool' ) );
      }
      $section = 'import';
      break;
    case 'export.perform' :
      if ( exportPerform( $_POST['filename'], $_POST['settings'], $taskIncompleteOffset ) ) {
        addWarning( L( 'The export is completed successfully.' ), 1, L( 'An import tool' ) );
      }
      $section = 'import';
      break;
    case 'import.perform.reinstall' :
      $importFileName = $sourcePath . DIRECTORY_SEPARATOR . 'imports' . DIRECTORY_SEPARATOR . $_POST['filename'];
      $import         = getImportInfo( $importFileName, true );
      if ( empty( $import ) ) break;
      deleteBackups( ['all' => 1] );
      deleteDirectory( $sourcePath . DIRECTORY_SEPARATOR . 'html' );
      deleteDirectory( $sourcePath . DIRECTORY_SEPARATOR . 'binary' );
      checkRequiredStructure( $sourcePath );
      dropTable( 'structure' );
      createTable( 'structure' );
      dropTable( 'settings' );
      createTable( 'settings' );
      $pdo                                  = newPDO();
      $import['info']['settings']['schema'] = getSchemaLatest();
      foreach ( $import['info']['settings'] as $param => $value ) {
        $stmt = $pdo->prepare( "INSERT INTO settings VALUES(:param, :value)" );
        $stmt->execute( ['param' => $param, 'value' => $value] );
      }
      $importSettings['overwrite'] = 'all';
      $importSettings['hostnames'] = array_keys( $import['info']['hostnames'] );
      $uuidSettings                = getSettings();
      loadAcmsSettings();
      $_POST['action']          = 'import.perform';
      $_POST['settings']        = $importSettings;
      $_POST['disable_history'] = 1;
      importPerform( $_POST['filename'], $importSettings, $taskIncompleteOffset );
      $section = 'main';
      break;
    case 'download.serial' :
      $uuid = downloadFromSerial( $_POST['uuid'], $taskIncompleteOffset );
      if ( $uuid ) {
        addWarning( sprintf( L( 'The website zip file %s is downloaded.' ), $uuid ), 1, L( 'An import tool' ) );
      } else {
        addWarning( L( 'Could not download the website zip.' ), 4, L( 'An import tool' ) );
      }
      $section = 'import';
      break;
    case 'import.upload' :
      if ( !empty( $_FILES['import_file'] ) && !$_FILES['import_file']['error'] ) {
        uploadImport( $_FILES['import_file'] );
      }
      $section = 'import';
      break;
    case 'create.template' :
      $templateId = createTemplateFromPage( $_POST['name'], $_POST['urlID'] );
      if ( $templateId ) {
        addWarning( sprintf( L( 'Template %s is created.' ), $templateId ), 1, L( 'Templates' ) );
        $_POST['show'] = 'edit.template';
        $_POST['name'] = $templateId;
      } else addWarning( sprintf( L( 'Could not create %s template.' ), htmlspecialchars( $_POST['name'] ) ), 4, L( 'Templates' ) );
      $section = 'template';
      break;
    case 'robots.allow' :
      createRobotsTxt( $_POST );
      addWarning( L( 'Website crawling has been allowed in robots.txt.' ), 1, L( 'Allow website in robots.txt' ) );
      $section = 'tools';
      break;
    case 'template.remove' :
      if ( removeTemplate( $_POST['name'] ) ) {
        addWarning( sprintf( L( 'Template %s is removed.' ), $_POST['name'] ), 1, L( 'Templates' ) );
      } else {
        addWarning( sprintf( L( 'Could not remove the %s template.' ), $_POST['name'] ), 4, L( 'Templates' ) );
      }
      $section = 'template';
      break;
    case 'update.template.content' :
      saveTemplateFile( $_POST['name'] );
      addWarning( sprintf( L( 'Template %s is updated.' ), $_POST['name'] ), 1, L( 'Templates' ) );
      $section = 'template';
      break;
    case 'update.template.settings' :
      updateTemplateSettings( $_POST );
      addWarning( sprintf( L( 'Template %s is updated.' ), $_POST['name'] ), 1, L( 'Templates' ) );
      $_POST['show'] = 'edit.template';
      $section       = 'template';
      break;
    case 'show.create.page' :
      $template     = getTemplate( $_POST['name'] );
      $templateInfo = getTemplateInfo( $_POST['name'] );
      $section      = 'page';
      break;
    case 'create.template.page' :
      $_POST['urlID'] = createTemplatePage( $_POST );
      $_POST['show']  = 'edit.url';
      //$section = 'page';
      break;
    case 'api.cloudflare.create' :
      if ( setCloudflareToken( $_POST ) ) addWarning( L( 'Access is successfully saved.' ), 1, 'Cloudflare' );
      else addWarning( L( 'API token is not working.' ), 4, 'Cloudflare' );
      $section = 'tools';
      break;
    case 'api.cloudflare.remove' :
      removeMetaTableData( 'acms_cloudflare' );
      addWarning( L( 'API token removed.' ), 1, 'Cloudflare' );
      $section = 'tools';
      break;
    case 'api.cloudflare.development.mode' :
      if ( setDevelopmentModeCloudflare( $_POST['params']['enable'] ) ) addWarning( L( 'Development Mode setting is updated.' ), 1, 'Cloudflare' );
      else addWarning( L( 'Could not set Development Mode.' ), 4, 'Cloudflare' );
      $section = 'tools';
      break;
    case 'api.cloudflare.purge.cache' :
      if ( purgeCacheCloudflare() ) addWarning( L( 'Cache purged.' ), 1, 'Cloudflare' );
      else addWarning( L( 'Could not purge the cache.' ), 4, 'Cloudflare' );
      $section = 'tools';
      break;
    case 'fun.md' :
      funMD( $_POST['params'], $taskIncompleteOffset );
      $section = 'import';
      break;
    case 'convert.www' :
      $updatedUrls = updateWebsiteWww( $_POST['www'] );
      addWarning( L( 'Website converted.' ) . ' ' . sprintf( L( 'Updated URLs: %s' ), $updatedUrls ), 1, L( 'Convert to www/non-www' ) );
      $section = 'tools';
      break;
    case 'edit.trackers' :
      if ( !empty( $_POST['trackers'] ) ) {
        updateTrackersCode( $_POST['trackers'] ) ?
          addWarning( L( 'Trackers code is updated successfully.' ), 1, L( 'Insert trackers/analytics' ) )
          : addWarning( L( 'Could not update trackers code.' ), 4, L( 'Insert trackers/analytics' ) );
      }
      $section = 'trackers';
      break;
    case 'import.urls.list' :
      $section = 'import.urls';
      break;
    case 'import.urls.perform' :
      $section = 'import.urls';
      importUrls( $_POST, $taskIncompleteOffset );
      break;
  }
}

$domains = $sourcePath === false ? [] : getAllDomains();

define( 'ACMS_ORIGINAL_DOMAIN', $uuidSettings['domain'] );

$filterValue               = '';
$metaData                  = null;
$content                   = null;
$documentBaseUrl           = null;
$documentCharset           = null;
$documentID                = null;
isset( $documentMimeType ) || $documentMimeType = null;

if ( $section == 'settings' ) {
  $LOADER = loadLoaderSettings();
}

if ( isset( $_POST['filterValue'] ) ) {
  $filterValue = $_POST['filterValue'];
}

if ( isset( $_POST['show'], $_POST['urlID'] ) && $_POST['show'] == 'edit.url' ) {
  $metaData = getMetaData( $_POST['urlID'] );
  // [TODO] ignore protocol in the future release
  $realUrl      = ( isSecureConnection() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $metaData['request_uri'];
  $content      = file_get_contents( $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'] );
  $documentPath = pathinfo( parse_url( $metaData['request_uri'], PHP_URL_PATH ), PATHINFO_DIRNAME );
  // [TODO] ignore protocol in the future release
  $documentBaseUrl  = ( isSecureConnection() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $documentPath . ( $documentPath == '/' ? '' : '/' );
  $documentMimeType = $metaData['mimetype'];
  $documentCharset  = $metaData['charset'];
  $documentID       = $metaData['rowid'];
}

if ( isset( $_POST['show'], $_POST['name'] ) && $_POST['show'] == 'edit.template' ) {
  $section          = 'template';
  $template         = getTemplate( strtolower( $_POST['name'] ) );
  $template         = array_merge( $template, getTemplateInfo( $template['name'] ) );
  $documentBaseUrl  = ( isSecureConnection() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . '/';
  $documentMimeType = $template['mimetype'];
}

if ( !empty( $taskIncomplete ) ) {
  $taskIncompleteParams               = $_POST;
  $taskIncompleteParams['taskOffset'] = $taskIncompleteOffset;
  $taskIncompleteParams['taskStats']  = $taskStats;
} else {
  $taskIncomplete       = false;
  $taskIncompleteOffset = 0;
  $taskIncompleteParams = [];
  $taskStats            = !empty( $_POST['taskStats'] ) ? trim( $_POST['taskStats'] ) : serialize( ['time' => microtime( true ) - ACMS_START_TIME] );
}
if ( empty( $section ) && empty( $documentID ) && $accessAllowed && $extensionsAllowed ) {
  $section = 'main';
  checkSourceStructure();
  checkRequiredStructure();
  upgradeSchema();
}
?>
  <!doctype html>
  <html lang="<?=getLang()?>">
  <head>
    <title>Archivarix CMS</title>
    <meta name="robots" content="noindex,nofollow">
    <meta name="referrer" content="never">
    <meta name="referrer" content="no-referrer">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" href="data:image/svg+xml;base64,PHN2ZyBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMTYgMTYiPjxzdHlsZT4uc3Qwe2ZpbGw6I2ZmYTcwMH0uc3Qxe2ZpbGw6I2ZmZn08L3N0eWxlPjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0wIDBoMTZ2MTZIMHoiIGlkPSJCRyIvPjxwYXRoIGNsYXNzPSJzdDEiIGQ9Ik00LjkgMy45Yy40LS4xLjgtLjIgMS4xLS4yLjMgMCAuNi4xLjkuMy4yLjEuNC4zLjYuNy4xLjIuMy43LjUgMS4zbDEuMSAzYy4yLjcuNSAxLjIuOCAxLjYuMy40LjUuNy43LjkuMi4yLjQuMy43LjQuMi4xLjQuMS42LjFoLjR2LjFjLS4zLjEtLjUuMS0uOC4xLS4zIDAtLjUtLjEtLjgtLjItLjMtLjEtLjUtLjMtLjgtLjYtLjYtLjQtMS0xLjItMS40LTIuMmwtLjMtMUg1LjhsLS42IDEuNnYuMmMwIC4xIDAgLjEuMS4ycy4yLjEuMy4xaC4xdi4xSDMuOHYtLjFoLjFjLjEgMCAuMyAwIC40LS4xLjEtLjEuMi0uMi4zLS40bDIuMi01LjJjLS4zLS41LS43LS43LTEuMi0uN2gtLjd6bTEgNGgyLjJsLS43LTJjLS4xLS40LS4zLS43LS40LTFsLTEuMSAzeiIgaWQ9IkxvZ28iLz48L3N2Zz4=">
    <style>
      body {
        font-family: 'Open Sans', sans-serif;
        padding-top: 50px;
      }

      @media (min-width: 992px) {
        #sidebar {
          overflow-y: scroll;
          padding: 0;
          position: fixed;
          top: 60px;
          left: 0;
          bottom: 0;
        }
      }

      .bg-image {
        background: repeating-linear-gradient(45deg, #bcbcbc, #bcbcbc 10px, #f0f0f0 10px, #f0f0f0 20px);
      }

      .cursor-link {
        cursor: pointer;
      }

      .mce-fullscreen {
        padding-top: 55px !important;
      }

      #textarea_html {
        min-height: 400px;
        display: none;
        white-space: pre-wrap;
        word-wrap: break-word;
      }

      .CodeMirror {
        box-sizing: border-box;
        margin: 0;
        display: block;
        width: 100%;
        height: auto !important;
        padding: 0;
        font-size: 12px;
        line-height: 1.42857143;
        color: #555;
        background-color: #fff;
        background-image: none;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
        transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
        font-family: monospace, monospace;
      }

      .CodeMirror-focused {
        border-color: #66afe9;
        outline: 0;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(102, 175, 233, .6);
        transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
      }

      .jstree-default a {
        white-space: normal !important;
        height: auto;
      }

      .jstree-anchor {
        height: auto !important;
        font-size: 0.8em;
        word-wrap: anywhere;
        word-break: break-all;
        padding: 0 !important;
      }

      .jstree-leaf a {
        height: auto !important;
      }

      .jstree-default a.jstree-search {
        color: inherit;
      }

      .jstree-wholerow-clicked {
        background: inherit !important;
      }

      pre {
        white-space: pre-wrap;
      }

      .domain-name-toggle[aria-expanded=false] {
        text-decoration-line: underline;
        text-decoration-style: dotted;
      }

      .expand-label[data-toggle="collapse"][aria-expanded="true"] .fas:before {
        content: "\f0d7";
      }

      .expand-label[data-toggle="collapse"][aria-expanded="false"].collapsed .fas:before {
        content: "\f0da";
      }

      .bg-search-url {
        background-color: #e3f1ff !important;
      }

      .dropzone {
        border: dashed 1px !important;
        text-align: center;
      }

      .dz-drag-hover {
        background-color: #28a745 !important;
      }

      .logo-cloudflare {
        height: 1em;
        background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDg2LjMgMTU0LjMiPjxwYXRoIGQ9Ik0xMDgwLjIgMTA5LjdhNi4wNiA2LjA2IDAgMCAxLTYuMS02LjEgNi4xIDYuMSAwIDEgMSA2LjEgNi4xbTAtMTFhNC45IDQuOSAwIDEgMCA0LjkgNC45IDQuOTEgNC45MSAwIDAgMC00LjktNC45bTMuMSA4LjFoLTEuNGwtMS4yLTIuM2gtMS42djIuM2gtMS4zdi02LjdoMy4yYTIuMTMgMi4xMyAwIDAgMSAyLjMgMi4yIDIuMDggMi4wOCAwIDAgMS0xLjQgMnptLTIuNC0zLjVhLjk0Ljk0IDAgMCAwIDEtMWMwLS44LS40LTEtMS0xaC0ydjJ6bS02MzYuMi02LjRoMTUuNnY0Mi41aDI3LjFWMTUzaC00Mi43em01OC44IDI4LjJ2LS4xYzAtMTYuMSAxMy0yOS4yIDMwLjMtMjkuMnMzMC4xIDEyLjkgMzAuMSAyOXYuMmMwIDE2LjEtMTMgMjkuMi0zMC4zIDI5LjJzLTMwLjEtMTMtMzAuMS0yOS4xbTQ0LjYgMHYtLjFjMC04LjEtNS44LTE1LjEtMTQuNC0xNS4xcy0xNC4yIDYuOS0xNC4yIDE1di4yYzAgOC4xIDUuOCAxNS4xIDE0LjMgMTUuMXMxNC4zLTcgMTQuMy0xNS4xbTM0LjkgMy4zVjk2LjloMTUuOHYzMS4yYzAgOC4xIDQuMSAxMS45IDEwLjMgMTEuOXMxMC4zLTMuNyAxMC4zLTExLjVWOTYuOWgxNS44VjEyOGMwIDE4LjEtMTAuMyAyNi0yNi4zIDI2cy0yNS45LTgtMjUuOS0yNS42bTc2LTMxLjVoMjEuN2MyMCAwIDMxLjcgMTEuNSAzMS43IDI3Ljd2LjJjMCAxNi4yLTExLjggMjguMi0zMiAyOC4yaC0yMS4zbC0uMS01Ni4xem0yMS45IDQyLjNjOS4zIDAgMTUuNS01LjEgMTUuNS0xNC4ydi0uMmMwLTktNi4yLTE0LjItMTUuNS0xNC4yaC02LjN2MjguNWw2LjMuMXptNTQtNDIuM2g0NC45djEzLjdoLTI5LjR2OS41SDc3N1YxMzNoLTI2LjZ2MjBoLTE1LjV6bTY2LjUgMGgxNS41djQyLjVoMjcuMlYxNTNoLTQyLjd6bTgzLjMtLjRoMTVsMjMuOSA1Ni41aC0xNi43bC00LjEtMTBoLTIxLjZsLTQgMTBoLTE2LjNsMjMuOC01Ni41em0xMy43IDM0LjRsLTYuMi0xNS45LTYuMyAxNS45em00NS4yLTM0aDI2LjVjOC42IDAgMTQuNSAyLjIgMTguMyA2LjEgMy4zIDMuMiA1IDcuNSA1IDEzLjF2LjJjMCA4LjYtNC42IDE0LjMtMTEuNSAxNy4ybDEzLjQgMTkuNmgtMThsLTExLjMtMTdoLTYuOHYxN2gtMTUuNWwtLjEtNTYuMnptMjUuOCAyN2M1LjMgMCA4LjMtMi42IDguMy02LjZ2LS4zYzAtNC40LTMuMi02LjYtOC40LTYuNmgtMTAuMnYxMy41em00Ni40LTI3aDQ1LjF2MTMuM2gtMjkuN3Y4LjRoMjYuOXYxMi4zaC0yNi45djguOWgzMC4xVjE1M2gtNDUuNXptLTYwNS4xIDM0LjhhMTMuNzkgMTMuNzkgMCAwIDEtMTIuOCA4LjRjLTguNSAwLTE0LjMtNy4xLTE0LjMtMTUuMXYtLjJjMC04LjEgNS43LTE1IDE0LjItMTVhMTMuOTMgMTMuOTMgMCAwIDEgMTMuMyA5LjNoMTYuNGMtMi42LTEzLjQtMTQuNC0yMy4zLTI5LjYtMjMuMy0xNy4zIDAtMzAuMyAxMy4xLTMwLjMgMjkuMnYuMmMwIDE2LjEgMTIuOCAyOSAzMC4xIDI5IDE0LjggMCAyNi40LTkuNiAyOS40LTIyLjRsLTE2LjQtLjF6IiBmaWxsPSIjNDA0MDQxIi8+PHBhdGggZD0iTTMxMi44IDkxLjZMMjY5LjkgNjdsLTcuNC0zLjJMODcgNjUuMXY4OWwyMjUuOC4xeiIgZmlsbD0iI2ZmZiIvPjxwYXRoIGQ9Ik0yMzQuNyAxNDZjMi4xLTcuMiAxLjMtMTMuOC0yLjItMTguNy0zLjItNC41LTguNi03LjEtMTUuMS03LjRsLTEyMy4xLTEuNmEyLjI4IDIuMjggMCAwIDEtMS45LTEgMi43NiAyLjc2IDAgMCAxLS4zLTIuMiAzLjM4IDMuMzggMCAwIDEgMi45LTIuMmwxMjQuMi0xLjZjMTQuNy0uNyAzMC43LTEyLjYgMzYuMy0yNy4ybDcuMS0xOC41YTQgNCAwIDAgMCAuMi0yLjQgODAuODcgODAuODcgMCAwIDAtMTU1LjUtOC4zIDM2LjM1IDM2LjM1IDAgMCAwLTU4IDI1LjQgMzguMTggMzguMTggMCAwIDAgLjkgMTIuN0E1MS42NiA1MS42NiAwIDAgMCAwIDE0NC43YTYzIDYzIDAgMCAwIC41IDcuNSAyLjQ2IDIuNDYgMCAwIDAgMi40IDIuMWgyMjcuMmEzLjA5IDMuMDkgMCAwIDAgMi45LTIuMmwxLjctNi4xeiIgZmlsbD0iI2YzODAyMCIvPjxwYXRoIGQ9Ik0yNzMuOSA2Ni45Yy0xLjEgMC0yLjMgMC0zLjQuMWEyIDIgMCAwIDAtMS44IDEuNGwtNC44IDE2LjdjLTIuMSA3LjItMS4zIDEzLjggMi4yIDE4LjcgMy4yIDQuNSA4LjYgNy4xIDE1LjEgNy40bDI2LjIgMS42YTIuMjggMi4yOCAwIDAgMSAxLjkgMSAyLjgyIDIuODIgMCAwIDEgLjMgMi4yIDMuMzggMy4zOCAwIDAgMS0yLjkgMi4ybC0yNy4zIDEuNmMtMTQuOC43LTMwLjcgMTIuNi0zNi4zIDI3LjJsLTIgNS4xYTEuNDQgMS40NCAwIDAgMCAxLjQgMmg5My44YTIuNDYgMi40NiAwIDAgMCAyLjQtMS44IDY4LjM1IDY4LjM1IDAgMCAwIDIuNS0xOC4yIDY3LjI0IDY3LjI0IDAgMCAwLTY3LjMtNjcuMiIgZmlsbD0iI2ZhYWU0MCIvPjwvc3ZnPg==');
        background-repeat: no-repeat;
        background-position: center;
      }

      .revisions ins {
        border-top: 1px solid #b8ecc6;
        border-left: 1px solid #b8ecc6;
        border-right: 1px solid #b8ecc6;
        background: #e6ffed;
        padding: 0 5px 0 20px;
        display: block;
        text-decoration: none;
        min-height: 1.5em;
        white-space: pre-wrap;
        position: relative;
      }

      .revisions ins:hover {
        background: #cdffd8;
      }

      .revisions ins::before {
        content: "+";
        color: #6c757d;
        background: #cdffd8;
        padding: 0 5px 0 5px;
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
      }

      .revisions ins:last-child {
        border-bottom: 1px solid #b8ecc6;
      }

      .revisions del {
        border-top: 1px solid #f5d5d9;
        border-left: 1px solid #f5d5d9;
        border-right: 1px solid #f5d5d9;
        background: #ffeef0;
        padding: 0 5px 0 20px;
        display: block;
        text-decoration: none;
        min-height: 1.5em;
        white-space: pre-wrap;
        position: relative;
      }

      .revisions del:hover {
        background: #ffdce0;
      }

      .revisions del::before {
        content: "-";
        color: #6c757d;
        background: #ffdce0;
        padding: 0 5px 0 5px;
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
      }

      .revisions del:last-child {
        border-bottom: 1px solid #f5d5d9;
      }

      .revisions span {
        border-top: 1px solid #f0f0f0;
        border-left: 1px solid #e2e2e2;
        border-right: 1px solid #e2e2e2;
        background: #ffffff;
        padding: 0 5px 0 20px;
        display: none;
        text-decoration: none;
        min-height: 1.5em;
        white-space: pre-wrap;
        position: relative;
      }

      .revisions span:hover {
        background: #eeeeee;
      }

      .revisions span::before {
        content: " ";
        color: #6c757d;
        background: #f3f3f3;
        padding: 0 5px 0 5px;
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
      }

      .revisions span:last-child {
        border-bottom: 1px solid #e2e2e2;
      }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css" integrity="sha512-P5MgMn1jBN01asBgU0z60Qk4QxiXo86+wlFahKrsQf37c9cro517WzVSPPV1tDKzhku2iJ2FVgL67wG03SGnNA==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.11/themes/default/style.min.css" integrity="sha512-P8BwDSUInKMA7I116Z1RFg/Dfk85uFdliEUYO7vQlwtxLVMNvZimfMAQsaf++9EhlAGOVX6yhDQAIY3/70jDUg==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" integrity="sha512-iBBXm8fW90+nuLcSKlbmrPcLa0OT92xO1BIsZ+ywDWZCvqsWgccV3gFoRBv0z+8dLJgyAHIhR35VZc2oM/gI1w==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <?php if ( in_array( $section, ['table', 'missing.urls', 'backups', 'search'] ) ) { ?>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.18.3/bootstrap-table.min.css" integrity="sha512-5RNDl2gYvm6wpoVAU4J2+cMGZQeE2o4/AksK/bi355p/C31aRibC93EYxXczXq3ja2PJj60uifzcocu2Ca2FBg==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <?php } ?>
    <?php if ( in_array( $section, ['settings', 'template', 'htaccess', 'trackers', 'import.urls'] ) || isset( $metaData['rowid'] ) ) { ?>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/codemirror.min.css" integrity="sha512-xIf9AdJauwKIVtrVRZ0i4nHP61Ogx9fSRAkCLecmE2dL/U8ioWpDvFCAy4dcfecN72HHB9+7FfQj3aiO68aaaw==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/dialog/dialog.min.css" integrity="sha512-Vogm+Cii1SXP5oxWQyPdkA91rHB776209ZVvX4C/i4ypcfBlWVRXZGodoTDAyyZvO36JlTqDqkMhVKAYc7CMjQ==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.2/dropzone.min.css" integrity="sha512-jU/7UFiaW5UBGODEopEqnbIAHOI8fO6T99m7Tsmqs2gkdujByJfkCbbfPSN4Wlqlb9TGnsuC0YgUgWkRBK7B9A==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <?php } ?>
    <?php if ( in_array( $section, ['import', 'install', 'main'] ) ) { ?>
      <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <?php } ?>
  </head>
  <body>
  <?php
  if ( !$accessAllowed && $extensionsAllowed ) {
    $section = ''; ?>
    <div class="container">
      <?php showWarning(); ?>
      <div class="modal fade show d-block p-3" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content border-0 bg-dark">
            <div class="modal-header d-block border-0 pt-3 pb-0">
              <div class="row">
                <div class="col mb-3 text-center text-sm-left">
                  <a href=""><img src="<?=dataLogo()?>" height="30" class="border-0" alt=""></a>
                </div>
                <div class="col-12 mb-3 col-sm-auto text-center">
                  <div class="btn-group btn-group-sm" role="group">
                    <?php
                    $locales = $cmsLocales;
                    unset( $locales['en'] );
                    ?>
                    <a href="<?=$_SERVER['REQUEST_URI']?>?lang=en" class="cursor-link btn <?=( getLang() == 'en' ? 'btn-success' : 'btn-light' )?>">English</a>
                    <a href="<?=$_SERVER['REQUEST_URI']?>?lang=<?=( key_exists( getLang(), $locales ) ? getLang() : 'ru' )?>" class="cursor-link btn <?=( key_exists( getLang(), $locales ) ? 'btn-success' : 'btn-light' )?>"><?=( key_exists( getLang(), $locales ) ? $locales[getLang()]['name'] : 'Русский' )?></a>
                    <?php if ( !getMissingExtensions( ['curl'] ) ) {
                      ?>
                      <button type="button" class="btn dropdown-toggle dropdown-toggle-split <?=( key_exists( getLang(), $locales ) ? 'btn-success' : 'btn-light' )?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-language fa-fw"></i>
                      </button>
                      <div class="dropdown-menu">
                        <?php
                        unset( $locales[getLang()] );
                        foreach ( $locales as $localeLang => $localeData ) { ?>
                          <a class="dropdown-item" href="<?=$_SERVER['REQUEST_URI']?>?lang=<?=$localeLang?>"><?=$localeData['name']?></a>
                        <?php } ?>
                      </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-body p-3 bg-secondary rounded-bottom">
              <form method="post" action="" class="needs-validation" novalidate>
                <div class="d-flex">
                  <div class="w-100 input-group pr-2">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-fw fa-eye-slash password-eye"></i></span></div>
                    <input type="password" name="password" class="form-control" placeholder="<?=L( 'Password' )?>" value="" autofocus required>
                  </div>
                  <div class="ml-auto">
                    <button type="submit" class="btn btn-success"><i class="fas fa-unlock-alt fa-fw"></i>
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
  }

  if ( !$extensionsAllowed ) {
    $section = '';
    addWarning( sprintf( L( 'In order to continue, you must have the following PHP extensions enabled: %s' ), implode( ', ', getMissingExtensions( ['json', 'pcre', 'pdo_sqlite', 'zip'] ) ) ), 4, L( 'System check' ) );
    ?>
    <div class="container">
      <?php showWarning(); ?>
      <div class="modal fade show d-block p-3" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content border-0 bg-dark">
            <div class="modal-header justify-content-center border-0 py-3">
              <a href=""><img src="<?=dataLogo()?>" height="35" class="d-inline-block align-top border-0" alt=""></a>
            </div>
            <div class="modal-body p-3 bg-light rounded-bottom text-center border border-top-0">
              <a href="" class="btn btn-success"><?=L( 'Check again' )?></a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
  }

  if ( $section == 'install' && !$taskIncomplete ) { ?>
    <div class="container">
      <?php showWarning(); ?>
      <div class="card mb-3 border-0">

        <div class="card-header pt-3 pb-0 bg-dark">
          <div class="row">
            <div class="col mb-3 text-center text-sm-left">
              <a href=""><img src="<?=dataLogo()?>" height="30" class="border-0" alt=""></a>
            </div>
            <div class="col-12 col-sm-auto mb-3 text-center">
              <div class="btn-group btn-group-sm" role="group">
                <?php
                $locales = $cmsLocales;
                unset( $locales['en'] );
                ?>
                <a href="<?=$_SERVER['REQUEST_URI']?>?lang=en" class="cursor-link btn <?=( getLang() == 'en' ? 'btn-success' : 'btn-light' )?>">English</a>
                <a href="<?=$_SERVER['REQUEST_URI']?>?lang=<?=( key_exists( getLang(), $locales ) ? getLang() : 'ru' )?>" class="cursor-link btn <?=( key_exists( getLang(), $locales ) ? 'btn-success' : 'btn-light' )?>"><?=( key_exists( getLang(), $locales ) ? $locales[getLang()]['name'] : 'Русский' )?></a>
                <?php if ( !getMissingExtensions( ['curl'] ) ) { ?>
                  <button type="button" class="btn dropdown-toggle dropdown-toggle-split <?=( key_exists( getLang(), $locales ) ? 'btn-success' : 'btn-light' )?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-language fa-fw"></i>
                  </button>
                  <div class="dropdown-menu">
                    <?php
                    unset( $locales[getLang()] );
                    foreach ( $locales as $localeLang => $localeData ) { ?>
                      <a class="dropdown-item" href="<?=$_SERVER['REQUEST_URI']?>?lang=<?=$localeLang?>"><?=$localeData['name']?></a>
                    <?php } ?>
                  </div>
                <?php } ?>
              </div>
            </div>
            <?php if ( !empty( $_SESSION['archivarix.logged'] ) ) { ?>
              <div class="col-12 col-sm-auto mb-3 text-center pl-sm-0 d-block d-sm-flex align-items-center">
                <a class="text-light" href="<?=$_SERVER['REQUEST_URI']?>?logout">
                  <i class="fas fa-sign-out-alt fa-fw"></i> <span class="d-sm-none"><?=L( 'Log out' )?></span>
                </a>
              </div>
            <?php } ?>
          </div>
        </div>
        <div class="card-body border border-top-0">
          <div class="">
            <?php if ( basename( __FILE__ ) == 'archivarix.cms.php' ) { ?>
              <div class="alert alert-warning d-md-flex align-items-center">
                <div class="mr-auto pr-md-2">
                  <?=L( 'Attention! For security reasons, we recommend to rename the default name of the "archivarix.cms.php" file.' )?> <?=L( 'You can do this at any time manually on the server, or by pressing a button that will rename the file to a random name.' )?> <?=L( 'Do not forget to bookmark the new address after renaming!' )?>
                </div>
                <div class="mt-2 mt-md-0">
                  <form action="" method="post">
                    <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                    <input type="hidden" name="action" value="rename.cms">
                    <button class="btn btn-warning"><?=L( 'Rename' )?></button>
                  </form>
                </div>
              </div>
            <?php } else { ?>
              <div class="alert alert-success">
                &#x1f44d <?=L( 'The filename of Archivarix CMS script has been renamed to a non-default value.' )?> <?=L( 'Do not forget to bookmark the address of this page!' )?>
              </div>
            <?php } ?>
            <form action="" method="post" id="form_import_uuid" class="needs-validation form-wait" novalidate>
              <div class="input-group">
                <input type="text" class="form-control" name="uuid" pattern="[0-9a-zA-Z]{16}|[-0-9a-zA-Z]{19}" placeholder="<?=L( 'Enter serial number' )?>" autocorrect="off" autocapitalize="none" required>
                <div class="input-group-append">
                  <button type="submit" class="btn btn-primary rounded-right" <?=!empty( $noProceed ) ? ' disabled' : ''?>><?=L( 'Download' )?></button>
                </div>
                <div class="invalid-feedback"><?=L( 'The serial number has to be in a format of 16 characters XXXXXXXXXXXXXXXX or XXXX-XXXX-XXXX-XXXX' )?></div>
              </div>
              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
              <input type="hidden" name="action" value="download.serial.install">
            </form>

            <div class="d-flex align-items-center text-secondary small text-uppercase">
              <div class="w-100">
                <hr class="my-0">
              </div>
              <div class="mx-2 text-nowrap"><?=L( 'or' )?></div>
              <div class="w-100">
                <hr class="my-0">
              </div>
            </div>

            <form action="" method="post" id="form_import_upload" enctype="multipart/form-data" class="needs-validation form-wait" novalidate>
              <div class="input-group">
                <div class="custom-file">
                  <input type="file" class="custom-file-input input-upload-file" name="import_file" accept=".zip,application/zip,application/octet-stream,application/x-zip-compressed,multipart/x-zip" id="input_import_file" aria-describedby="button_import_file" required>
                  <label class="custom-file-label text-truncate" for="input_import_file"><?=L( 'Choose a ZIP file' )?></label>
                </div>
                <div class="input-group-append">
                  <button class="btn btn-primary" type="submit" id="button_import_file" <?=!empty( $noProceed ) ? ' disabled' : ''?>><?=L( 'Upload' )?></button>
                </div>
              </div>
              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
              <input type="hidden" name="action" value="import.upload.install">
            </form>
          </div>

          <?php
          $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . '.content.tmp';
          if ( file_exists( $sourcePath ) ) {
            $imports = getImportsList();
            foreach ( $imports as $import ) { ?>
              <div class="bg-light rounded border shadow p-3 mt-3">
                <div class="row">
                  <div class="col-12 col-md-3 mb-3">
                    <img src="<?=$import['screenshot']?>" class="img-fluid w-100 border rounded" onerror="this.src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAABLCAIAAAA3cxjrAAAABnRSTlMAAAAAAABupgeRAAAAMklEQVR42u3BAQEAAACCIP+vbkhAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEcGcMsAAQVgyaQAAAAASUVORK5CYII=';">
                  </div>
                  <div class="col-12 col-md-6 mb-3">
                    <div class="text-uppercase h5"><?=convertIdnToUtf8( $import['info']['settings']['domain'] )?>
                      <a href="<?=$import['url']?>" target="_blank"><i class="fas fa-external-link-alt fa-fw small"></i></a>
                    </div>
                    <div class="small"><?=$import['filename']?>
                      (<?=getHumanSize( $import['filesize'] );?>)
                    </div>
                    <div class="small text-muted">
                      <?=sprintf( L( 'Contains: %d files, %s of data' ), $import['info']['filescount'], getHumanSize( $import['info']['filessize'] ) )?>
                    </div>
                  </div>
                  <div class="col-12 col-md-3 text-center text-md-right">
                    <button class="btn btn-primary" data-toggle="collapse" data-target="#div_import_stats_<?=$import['id']?>" aria-expanded="false" aria-controls="div_import_stats_<?=$import['id']?>" title="<?=L( 'Stats' )?>">
                      <i class="fas fa-chart-pie fa-fw"></i>
                    </button>
                    <form action="" method="post" class="d-inline form-wait" id="form_remove_import_<?=$import['id']?>">
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="import.remove.install">
                      <input type="hidden" name="filename" value="<?=htmlspecialchars( $import['filename'] )?>">
                      <button class="btn btn-danger btn-action" type="button" data-toggle="modal" data-target="#confirm-action" data-source="form_remove_import_<?=$import['id']?>" title="<?=L( 'Delete' )?>">
                        <i class="fas fa-trash-alt fa-fw"></i></button>
                    </form>
                  </div>


                  <div class="col-12 py-3" id="div_form_import_<?=$import['id']?>">
                    <form action="" method="post" id="form_import_<?=$import['id']?>" class="form-wait">
                      <div class="mb-3">
                        <table class="table table-striped table-sm table-responsive">
                          <thead>
                          <tr>
                            <th class="w-100">
                              <div class="form-check font-weight-bold">
                                <input class="form-check-input check-all" data-group="check-<?=$import['id']?>" type="checkbox" checked id="check_all_<?=$import['id']?>"><label class="form-check-label" for="check_all_<?=$import['id']?>"><?=L( 'Hostnames' )?></label>
                              </div>
                            </th>
                            <th><?=L( 'Files' )?></th>
                          </tr>
                          </thead>
                          <?php foreach ( $import['info']['hostnames'] as $hostname ) { ?>
                            <tr>
                              <td>
                                <div class="form-check">
                                  <input class="form-check-input check-<?=$import['id']?>" type="checkbox" name="settings[hostnames][]" value="<?=$hostname['hostname']?>" checked id="check_<?=$hostname['hostname']?>_<?=$import['id']?>"><label class="form-check-label" for="check_<?=$hostname['hostname']?>_<?=$import['id']?>"><?=convertIdnToUtf8( $hostname['hostname'] )?></label>
                                </div>
                              </td>
                              <td><?=number_format( $hostname['count'], 0 )?></td>
                            </tr>
                          <?php } ?>
                        </table>
                      </div>

                      <?php if ( !empty( $integrationPrerequisite ) ) { ?>
                        <div class="alert alert-warning">
                          <div class="custom-control custom-checkbox mb-0">
                            <input type="hidden" name="settings[integration]" value="0">
                            <input class="custom-control-input" type="checkbox" name="settings[integration]" value="1" id="check_integration_settings_<?=$import['id']?>" checked>
                            <label class="custom-control-label" for="check_integration_settings_<?=$import['id']?>"><?=sprintf( L( 'Integrate with the found installation of %s' ), $integrationPrerequisite )?></label>
                            <small class="form-text"><i class="fas fa-exclamation-triangle fa-fw"></i> <?=sprintf( L( 'Attention! If you do not select the checkbox, then the index.php file of %s will be overwritten with Archivarix Loader.' ), $integrationPrerequisite )?>
                            </small>
                          </div>
                        </div>
                      <?php } ?>

                      <?php if ( $import['acms_settings'] ) { ?>
                        <div class="custom-control custom-checkbox mb-0">
                          <input type="hidden" name="settings[acms_settings]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[acms_settings]" value="1" id="check_acms_settings_<?=$import['id']?>" checked>
                          <label class="custom-control-label" for="check_acms_settings_<?=$import['id']?>"><?=L( 'Import Archivarix CMS settings' )?></label>
                        </div>
                      <?php } ?>

                      <?php if ( $import['loader_settings'] ) { ?>
                        <div class="custom-control custom-checkbox mb-0">
                          <input type="hidden" name="settings[loader_settings]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[loader_settings]" value="1" id="check_loader_settings_<?=$import['id']?>" checked>
                          <label class="custom-control-label" for="check_loader_settings_<?=$import['id']?>"><?=L( 'Import Archivarix Loader settings' )?></label>
                        </div>
                      <?php } ?>

                      <?php if ( !empty( $import['templates'] ) ) { ?>
                        <div class="custom-control custom-checkbox mb-0">
                          <input type="hidden" name="settings[templates]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[templates]" value="1" id="check_templates_<?=$import['id']?>" checked>
                          <label class="custom-control-label" for="check_templates_<?=$import['id']?>"><?=sprintf( L( 'The import detected templates (%d pieces)' ), count( $import['templates'] ) )?>
                            (<a href="" data-toggle="collapse" data-target="#div_templates_<?=$import['id']?>" aria-expanded="false" aria-controls="div_templates_<?=$import['id']?>"><?=L( 'show' )?></a>)</label>
                          <div class="collapse list-group mt-2" id="div_templates_<?=$import['id']?>">
                            <li class="list-group-item list-group-item-action list-group-item-secondary h6 m-0 rounded-0"><?=L( 'Templates' )?></li>
                            <?php foreach ( $import['templates'] as $importTemplate ) { ?>
                              <li class="list-group-item list-group-item-action py-1 text-nowrap text-truncate text-monospace rounded-0">
                                <i class="far fa-file-code fa-fw"></i> <?=$importTemplate['name']?>
                              </li>
                            <?php } ?>
                          </div>
                        </div>
                      <?php } ?>

                      <?php if ( !empty( $import['custom_includes'] ) && !inSafeMode() ) { ?>
                      <div class="custom-control custom-checkbox mb-0">
                        <input type="hidden" name="settings[custom_includes]" value="0">
                        <input class="custom-control-input" type="checkbox" name="settings[custom_includes]" value="1" id="check_custom_includes_settings_<?=$import['id']?>">
                        <label class="custom-control-label" for="check_custom_includes_settings_<?=$import['id']?>"><?=L( 'Import files from custom \'includes\' directory' )?>
                          (<a href="" data-toggle="collapse" data-target="#div_custom_includes_<?=$import['id']?>" aria-expanded="false" aria-controls="div_custom_includes_<?=$import['id']?>"><?=L( 'show files' )?></a>)</label>
                        <div class="collapse list-group mt-2" id="div_custom_includes_<?=$import['id']?>">
                          <li class="list-group-item list-group-item-action list-group-item-secondary h6 m-0 rounded-0"><?=L( 'Custom Files' )?></li>
                          <?php foreach ( $import['custom_includes'] as $customInclude ) { ?>
                            <li class="list-group-item list-group-item-action px-0 py-1 text-nowrap text-truncate text-monospace rounded-0">
                              <?=str_repeat( '<i class="fas fa-fw"></i>', $customInclude['levels'] )?>
                              <?=( $customInclude['is_dir'] ? '<i class="far fa-fw fa-folder"></i>' : '<i class="fas fa-fw"></i><i class="far fa-fw fa-file-alt"></i>' )?>
                              <?=basename( $customInclude['filename'] )?>
                              <span class="text-muted"><?=( $customInclude['is_dir'] ? '' : '(' . getHumanSize( $customInclude['size'], 0 ) . ')' )?></span>
                            </li>
                          <?php } ?>
                        </div>
                        <div class="small text-danger mt-1">
                          <i class="fas fa-exclamation-triangle fa-fw"></i> <?=L( 'Attention! Any file inside "includes" directory can have executable PHP source code. Do not import files from untrustworthy sources.' )?>
                        </div>
                        <?php } ?>
                        <div>
                          <div><strong><?=L( 'After install' )?></strong></div>
                          <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input" name="settings[crawlers_allow]" value="1" id="check_crawlers_allow"">
                            <label class="custom-control-label" for="check_crawlers_allow"><?=L( 'Allow crawlers in robots.txt' )?></label>
                          </div>
                          <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input" name="settings[sitemap_enable]" value="1" id="check_sitemap_enable"">
                            <label class="custom-control-label" for="check_sitemap_enable"><?=sprintf( L( 'Enable Sitemap at %s' ), htmlspecialchars( detectSitemapUrl() ) )?></label>
                          </div>
                          <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input" name="settings[404_enable]" value="1" id="check_404_enable"">
                            <label class="custom-control-label" for="check_404_enable"><?=L( 'Show 404 page for missing URLs' )?></label>
                          </div>
                          <?php if ( count( $import['info']['hostnames'] ) > 1 && $import['info']['settings']['domain'] != $_SERVER['HTTP_HOST'] ) { ?>
                            <div class="custom-control custom-checkbox mb-1">
                              <input type="checkbox" class="custom-control-input" name="settings[custom_domain]" value="<?=htmlspecialchars( $_SERVER['HTTP_HOST'] )?>" id="check_custom_domain"">
                              <label class="custom-control-label" for="check_custom_domain"><?=sprintf( L( 'Set custom domain as %s' ), htmlspecialchars( $_SERVER['HTTP_HOST'] ) )?></label>
                            </div>
                          <?php } ?>
                        </div>
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="import.perform.install">
                        <input type="hidden" name="filename" value="<?=htmlspecialchars( $import['filename'] )?>">
                        <input type="hidden" name="disable_history" value="1">
                        <div class="text-center">
                          <button class="btn btn-success mt-3" type="submit">
                            <i class="fas fa-rocket fa-fw"></i> <?=L( 'Run install' )?></button>
                        </div>
                    </form>
                  </div>
                </div>

                <div class="py-3 collapse stats-collapse" id="div_import_stats_<?=$import['id']?>" data-id="<?=$import['info']['id']?>">
                  <?php printStats( $import['info'] ) ?>
                </div>

              </div>
              <?php
            }
          }
          ?>
        </div>

        <div class="border border-top-0 rounded-bottom bg-light small">
          <div class="expand-label px-3 py-2 text-right" data-toggle="collapse" href="#collapseSystemInfo" role="button" aria-expanded="false" aria-controls="collapseSystemInfo">
            <i class="fab fa-php"></i></div>
          <div class="collapse px-3 pb-3 pt-0" id="collapseSystemInfo">
            <table class="table table-sm table-hover mb-0">
              <thead class="thead-light">
              <tr>
                <th colspan="2"><?=L( 'System' )?></th>
              </tr>
              </thead>
              <tr>
                <td><?=L( 'SQLite version' )?></td>
                <td>
                  <?php if ( !getMissingExtensions( ['pdo_sqlite'] ) ) { ?>
                    <?=getSqliteVersion()?> <?=( version_compare( getSqliteVersion(), '3.7.0', '>=' ) ? '' : '<i class="fas fa-exclamation-circle text-danger" data-toggle="tooltip" title="' . L( 'We have to use a legacy .db file because you have outdated SQLite version. The minimum recommended version is 3.7.0' ) . '"></i>' )?>
                  <?php } else { ?>
                    <span class="badge badge-pill badge-danger"><?=L( 'None' )?></span>
                  <?php } ?>
                </td>
              </tr>
              <tr>
                <td><?=L( 'PHP version' )?></td>
                <td><?=PHP_VERSION?> (<?=PHP_SAPI?>) <?=PHP_OS?></td>
              </tr>
              <tr>
                <td><?=L( 'PHP extensions' )?></td>
                <td>
                  <?php
                  $recommendedExtensions = ['curl', 'dom', 'fileinfo', 'iconv', 'imagick', 'intl', 'json', 'libxml', 'pcre', 'pdo_sqlite', 'zip',];
                  $missingExtensions     = getMissingExtensions( $recommendedExtensions );
                  foreach ( $recommendedExtensions as $recommendedExtension ) { ?>
                    <span class="badge badge-pill <?=in_array( $recommendedExtension, $missingExtensions ) ? 'badge-danger' : 'badge-success'?>"><?=$recommendedExtension?></span>
                  <?php } ?>
                </td>
              </tr>
              <tr>
                <td><?=L( 'Max upload size' )?></td>
                <td><?=getHumanSize( getUploadLimit(), 0 )?></td>
              </tr>
              <tr>
                <td><?=L( 'Max PHP memory' )?></td>
                <td><?=getHumanSize( getBytesFromHumanSize( ini_get( 'memory_limit' ) ), 0 )?></td>
              </tr>
              <tr>
                <td><?=L( 'Free disk space' )?></td>
                <td><?=getHumanSize( function_exists( 'disk_free_space' ) ? disk_free_space( __DIR__ ) : null )?></td>
              </tr>
            </table>
          </div>
        </div>


      </div>
    </div>
    <?php
  }


  if ( $section == 'install' && $taskIncomplete ) { ?>
    <div class="pt-3 container">
      <div class="p-4 rounded-top bg-dark text-light shadow">
        <div class="d-flex align-items-center">
          <div class=""><i class="fas fa-info-circle fa-3x mr-3"></i></div>
          <div class="h4"><?=L( 'Attention! Do not close the browser window and do not stop loading, the page will reload itself.' )?></div>
        </div>
        <form action="" method="post" id="form_task_part" class="form-wait">
          <?=printFormFields( $taskIncompleteParams )?>
        </form>
        <div class="small text-right">
          <?=L( 'Previous execution' )?>: <?=round( ( microtime( true ) - ACMS_START_TIME ), 3 )?>s
        </div>
      </div>
      <?php
      $stats = unserialize( $taskStats );
      if ( !empty( $stats['processed'] ) && !empty( $stats['total'] ) ) {
        ?>
        <div class="progress rounded-0">
          <div role="progressbar" aria-valuenow="<?=round( ( $stats['processed'] / $stats['total'] ) * 100 )?>" aria-valuemin="0" aria-valuemax="100" style="width: <?=round( ( $stats['processed'] / $stats['total'] ) * 100 )?>%" class="progress-bar progress-bar-striped progress-bar-animated bg-success rounded-0"></div>


        </div>
      <?php } ?>
    </div>
  <?php }

  if ( $section != 'install' && $accessAllowed && $extensionsAllowed ) { ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top" style="">
      <a class="navbar-brand" href=""><img src="<?=dataLogo()?>" height="30" class="d-inline-block align-top" alt="Logo Archivarix"></a>
      <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
        <ul class="navbar-nav">
          <li class="nav-item">
            <form action="" method="post" id="formSearchNew">
              <input type="hidden" name="search" value=""/>
              <input type="hidden" name="replace" value=""/>
              <input type="hidden" name="regex" value=""/>
              <input type="hidden" name="text_files_search" value=""/>
              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
              <input type="hidden" name="action" value="searchreplace.code"/>
              <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
              <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
              <input type="hidden" name="type" value="new"/>
              <a class="nav-link cursor-link" id="clickSearchNew"><i class="fas fa-search fa-fw"></i> <?=L( 'Search & Replace' )?>
              </a>
            </form>
          </li>
          <li class="nav-item">
            <form action="" method="post" id="formToolsView">
              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
              <input type="hidden" name="action" value="tools.view"/>
              <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
              <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
              <a class="nav-link cursor-link" id="clickToolsView"><i class="fas fa-tools fa-fw"></i> <?=L( 'Tools' )?>
              </a>
            </form>
          </li>
          <li class="nav-item">
            <form action="" method="post" id="formHistoryNew">
              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
              <input type="hidden" name="action" value="history.edit"/>
              <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
              <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
              <a class="nav-link cursor-link" id="clickHistory"><i class="fas fa-history fa-fw"></i> <?=L( 'History' )?>
              </a>
            </form>
          </li>
          <li class="nav-item">
            <form action="" method="post" id="formSettingsView">
              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
              <input type="hidden" name="action" value="settings.view"/>
              <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
              <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
              <a class="nav-link cursor-link" id="clickSettings"><i class="fas fa-cog fa-fw"></i>
                <?=L( 'Settings' )?>
              </a>
            </form>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?=$_SERVER['REQUEST_URI']?>"><i class="fas fa-info-circle fa-fw"></i>
              <span class="d-lg-none"><?=L( 'Information' )?></span></a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-language fa-fw"></i>
              <span class="d-lg-none"><?=L( 'Language' )?></span></a>
            <div class="dropdown-menu dropdown-menu-right pre-scrollable" aria-labelledby="navbarDropdownMenuLink">
              <a class="dropdown-item" href="<?=$_SERVER['REQUEST_URI']?>?lang=en">English</a>
              <a class="dropdown-item" href="<?=$_SERVER['REQUEST_URI']?>?lang=ru">Русский</a>
              <?php if ( !getMissingExtensions( ['curl'] ) ) {
                $locales = $cmsLocales;
                unset( $locales['en'], $locales['ru'] );
                foreach ( $locales as $localeLang => $localeData ) { ?>
                  <a class="dropdown-item" href="<?=$_SERVER['REQUEST_URI']?>?lang=<?=$localeLang?>"><?=$localeData['name']?></a>
                <?php }
              } ?>
            </div>
          </li>
          <?php if ( !empty( $_SESSION['archivarix.logged'] ) ) { ?>
            <li class="nav-item">
              <a class="nav-link" href="<?=$_SERVER['REQUEST_URI']?>?logout"><i class="fas fa-sign-out-alt fa-fw"></i>
                <span class="d-lg-none"><?=L( 'Log out' )?></span></a>
            </li>
          <?php } ?>
        </ul>
      </div>
    </nav>

    <?php if ( $taskIncomplete ) { ?>
      <div class="pt-4 container">
        <div class="px-4 py-3 rounded-top bg-dark text-light shadow">
          <div class="d-flex align-items-center">
            <div class=""><i class="fas fa-info-circle fa-3x mr-3"></i></div>
            <div class="h4"><?=L( 'Attention! Do not close the browser window and do not stop loading, the page will reload itself.' )?></div>
          </div>
          <form action="" method="post" id="form_task_part" class="form-wait">
            <?=printFormFields( $taskIncompleteParams )?>
          </form>
          <div class="small text-right">
            <?=L( 'Previous execution' )?>: <?=round( ( microtime( true ) - ACMS_START_TIME ), 3 )?>s
          </div>
        </div>
        <?php
        $stats = unserialize( $taskStats );
        if ( !empty( $stats['processed'] ) && !empty( $stats['total'] ) ) {
          ?>
          <div class="progress rounded-0">
            <div role="progressbar" aria-valuenow="<?=round( ( $stats['processed'] / $stats['total'] ) * 100 )?>" aria-valuemin="0" aria-valuemax="100" style="width: <?=round( ( $stats['processed'] / $stats['total'] ) * 100 )?>%" class="progress-bar progress-bar-striped progress-bar-animated bg-success rounded-0"></div>
          </div>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if ( empty( $taskIncomplete ) ) { ?>
      <div class="pt-3 container-fluid">
        <div class="row h-100">
          <div class="col-12 col-lg-3 border-0 mb-2 order-2 order-md-1" id="sidebar">
            <!-- SEARCH -->
            <div class="bg-white sticky-top px-1">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-white px-2"><i class="fas fa-filter fa-fw"></i></span>
                </div>
                <input type="search" value="<?=htmlspecialchars( $filterValue )?>" class="form-control border-left-0 pl-1" id="treeSearch" placeholder="<?=L( 'Filter' )?>"/>
              </div>
              <hr class="mt-1 mb-0 d-none d-lg-block">
            </div>

            <div class="p-1">
              <?php if ( inSafeMode() ) { ?>
                <div class="alert-info rounded p-2 mb-1 small text-center text-uppercase">
                  <i class="fas fa-shield-alt fa-fw"></i> <?=L( 'Safe mode is On' )?></div>
              <?php } ?>

              <?php if ( empty( $ACMS['ACMS_ALLOWED_IPS'] ) && !strlen( ACMS_PASSWORD ) && !strlen( $ACMS['ACMS_PASSWORD'] ) && !strlen( ACMS_SAFE_PASSWORD ) && !strlen( $ACMS['ACMS_SAFE_PASSWORD'] ) ) { ?>
                <div class="bg-danger text-light rounded p-2 mb-1 small">
                  <div class="">
                    <i class="fas fa-exclamation-triangle pr-2"></i><?=L( 'Warning! IP or password restriction is not configured. Anybody can access this page.' )?>
                  </div>
                  <hr class="bg-light my-2">
                  <div class="text-center">
                    <form action="" method="post">
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="settings.view"/>
                      <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                      <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                      <button type="submit" class="btn btn-light btn-sm">
                        <i class="fas fa-lock fa-fw"></i> <?=L( 'Set password' )?>
                      </button>
                    </form>
                    </a>
                  </div>
                </div>
              <?php } ?>

              <?php if ( inExpertMode() && count( $_POST ) ) {
                $stats = unserialize( $taskStats );
                ?>
                <!-- DEBUG MODE -->
                <div class="pt-3 text-white bg-dark rounded-top"></div>
                <div class="rounded-bottom bg-dark mb-1">
                  <div class="d-flex text-white text-monospace px-3 pb-3">
                    <div class="expand-label text-uppercase " data-toggle="collapse" href="#collapseDebug" role="button" aria-expanded="false" aria-controls="collapseDebug">
                      <i class="fas fa-caret-right fa-fw mr-1"></i><?=L( 'Debug' )?>
                    </div>
                    <div class="ml-auto"><i class="fas fa-memory"></i> <?=getHumanSize( memory_get_peak_usage() );?>
                    </div>
                    <div class="ml-2"><i class="fas fa-clock"></i> <?=number_format( $stats['time'], 2 )?>s</div>
                  </div>
                  <div class="bg-info text-white text-monospace p-3 rounded-bottom collapse" id="collapseDebug">
                    <pre class="text-white small"><?php printArrayHuman( $_POST ); ?><?php if ( !empty( $_FILES ) ) printArrayHuman( $_FILES ); ?></pre>
                  </div>
                </div>
              <?php } ?>

              <?php if ( empty( $domains ) ) { ?>
                <div class="text-center mb-2">
                  <button class="btn btn-success btn-block" type="button" id="createNewUrl_<?=$uuidSettings['domain']?>" data-toggle="modal" data-target="#createNewUrl" data-hostname="<?=$uuidSettings['domain']?>">
                    <i class="fas fa-file fa-fw"></i> <?=L( 'Create a new URL' )?>
                  </button>
                </div>
                <div class="text-center mb-2">
                  <form action="" method="post">
                    <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                    <input type="hidden" name="action" value="import.view">
                    <button class="btn btn-primary btn-block" type="submit">
                      <i class="fas fa-file-import fa-fw"></i> <?=L( 'Websites import' )?></button>
                  </form>
                </div>
              <?php } ?>

              <!-- TEMPLATES -->
              <?php $listTemplates = getTemplates();
              if ( $listTemplates ) { ?>
                <div id="accordion_templates">
                  <div class="card border-0 bg-light mb-1">
                    <div class="card-header border-0 rounded h5 px-2 py-3 alert-success">
                      <i class="fas fa-file-code fa-fw"></i> <?=L( 'Templates' )?>
                    </div>
                  </div>
                </div>
                <div class="collapse multi-collapse show urls-collapse" id="collapse_templates">
                  <div class="card-body p-0">
                    <div class="mb-2 small list-group">
                      <?php foreach ( $listTemplates as $currTemplate ) { ?>
                        <div class="d-flex">
                          <a href="#" class="template-link list-group-item list-group-item-action border-0 p-0 m-0 rounded-0 <?=( ( isset( $template ) && $template['name'] == $currTemplate['name'] ) ? 'bg-success text-white' : '' )?>" data-name="<?=$currTemplate['name']?>"><i class="fas fa-fw"></i><i class="fas fa-file-code fa-fw"></i><?=$currTemplate['name']?>
                          </a>
                          <a href="#" class="template-create-page-link px-2 <?=( ( isset( $template ) && $template['name'] == $currTemplate['name'] ) ? 'btn-success text-white' : 'text-success' )?>" data-name="<?=$currTemplate['name']?>"><i class="far fa-plus-square fa-fw"></i></a>
                        </div>
                      <?php } ?>
                    </div>
                  </div>
                </div>
                <hr class="mx-0 my-1">
              <?php } ?>

              <?php
              $urlsDisplayed    = 0;
              $domainsDisplayed = 0;
              foreach ( $domains as $domainName => $domainData ) { ?>
                <!-- DOMAINS TREE -->
                <div id="accordion_<?=$domainData['safeName']?>">
                  <div class="card border-0 rounded-top bg-light mb-1">
                    <div class="card-header border-0 p-0 rounded">
                      <div class="d-flex align-items-stretch">
                        <div class="h5 pl-2 pr-1 py-3 mb-0 rounded-left d-flex align-items-center">
                          <a class="text-dark" href="//<?=convertDomain( $domainName )?>" target="_blank"><i class="fas fa-external-link-alt fa-fw small"></i></a>
                        </div>
                        <div class="h5 px-1 py-3 mb-0">
                      <span class="text-break domain-name-toggle" data-toggle="collapse" data-target="#collapse_<?=$domainData['safeName']?>" aria-expanded="true" aria-controls="collapse_<?=$domainData['safeName']?>" role="button">
                      <?=convertIdnToUtf8( $domainName )?></span>
                        </div>
                        <div class="ml-auto text-nowrap align-items-center pr-2 d-flex align-items-center">
                          <button class="btn btn-sm btn-primary mr-1 urls-tree-expand" type="button" data-jstree="jstree_<?=$domainData['safeName']?>" title="<?=L( 'Expand/Collapse all' )?>">
                            <i class="fas fa-expand-alt fa-fw fa-rotate-90"></i>
                          </button>
                          <button class="btn btn-sm btn-primary" type="button" id="createNewUrl_<?=$domainData['safeName']?>" data-toggle="modal" data-target="#createNewUrl" data-hostname="<?=$domainName?>" title="<?=L( 'Create a new URL' )?>">
                            <i class="fas fa-file fa-fw"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="collapse multi-collapse show urls-collapse" id="collapse_<?=$domainData['safeName']?>">
                  <div class="card-body p-0">
                    <?php if ( $urlsTotal[$domainName] > $ACMS['ACMS_URLS_LIMIT'] ) {
                      $pageCurrent = 1;
                      $pagesTotal  = $ACMS['ACMS_URLS_LIMIT'] ? ceil( $urlsTotal[$domainName] / $ACMS['ACMS_URLS_LIMIT'] ) : 0;
                      if ( key_exists( $domainName, $urlOffsets ) ) {
                        $pageCurrent = $urlOffsets[$domainName];
                      }
                      ?>
                      <div class="alert alert-warning small mb-2"><?=sprintf( L( 'Pagination is on. You may increase the limit in Settings at the risk of running out of RAM. The current limit per page is %s.' ), $ACMS['ACMS_URLS_LIMIT'] )?></div>
                      <nav>
                        <ul class="pagination pagination-sm justify-content-center mb-2 flex-wrap">
                          <?php
                          if ( $pagesTotal ) for ( $i = 1; $i <= $pagesTotal; $i++ ) {
                            if ( $pageCurrent > 1 && $i == 1 ) { ?>
                              <li class="page-item">
                                <a class="page-link changePage cursor-link" data-page="<?=( $pageCurrent - 1 )?>" data-domain="<?=htmlspecialchars( $domainName )?>"><?=L( 'Previous' )?></a>
                              </li>
                            <?php }
                            if ( $i > 2 && $i < $pageCurrent ) { ?>
                              <li class="page-item disabled">
                                <a class="page-link">&hellip;</a>
                              </li>
                              <?php while ( $i < $pageCurrent ) $i++ ?>
                            <?php }
                            if ( $i > 2 && $i > $pageCurrent + 4 ) { ?>
                              <li class="page-item disabled">
                                <a class="page-link">&hellip;</a>
                              </li>
                              <?php while ( $i < $pagesTotal ) $i++ ?>
                            <?php }
                            if ( $i != $pageCurrent && ( $i > $pageCurrent - 5 || $i < $pageCurrent + 5 ) ) { ?>
                              <li class="page-item">
                                <a class="page-link changePage cursor-link" data-page="<?=$i?>" data-domain="<?=htmlspecialchars( $domainName )?>"><?=$i?></a>
                              </li>
                            <?php }
                            if ( $i == $pageCurrent ) { ?>
                              <li class="page-item active" aria-current="page">
                                <span class="page-link"><?=$i?></span>
                              </li>
                            <?php }
                          }
                          ?>
                        </ul>
                      </nav>
                    <?php } ?>
                    <div id="jstree_<?=$domainData['safeName']?>" data-domain-converted="<?=convertdomain( $domainName )?>" class="mb-2">
                      <?php if ( $urlsDisplayed < $ACMS['ACMS_URLS_LIMIT'] ) printArrayList( $domainData['tree'], $domainData['pathUrls'] ); ?>
                    </div>
                  </div>
                </div>
                <?php
                $domainsDisplayed++;
                if ( $domainsDisplayed == 100 ) break;
              } ?>
              <hr class="d-lg-none">
            </div>
          </div>

          <div class="col-12 col-lg-9 ml-auto pt-0 order-1 order-md-2 pb-3" id="main">
            <?php
            showWarning();
            if ( $section == 'settings' ) {
              $customFilesOnly = getOnlyCustomFiles( getCustomFiles() );
              if ( empty( $subSection ) ) $subSection = 'acms';
              ?>
              <div class="card mb-3">
                <div class="card-header">
                  <ul class="nav nav-tabs card-header-tabs" role="tablist" style="flex-wrap: inherit;">
                    <li class="nav-item">
                      <a class="nav-link <?=( $subSection == 'acms' ) ? 'active' : ''?>" id="settings_cms_tab" data-toggle="tab" href="#settings_cms" role="tab" aria-controls="settings_cms" aria-selected="<?=( $subSection == 'acms' ) ? 'true' : 'false'?>">Archivarix
                        CMS</a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link <?=( $subSection == 'loader' ) ? 'active' : ''?>" id="settings_loader_tab" data-toggle="tab" href="#settings_loader" role="tab" aria-controls="settings_loader" aria-selected="<?=( $subSection == 'loader' ) ? 'true' : 'false'?>"><?=L( 'Archivarix Loader' )?></a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link <?=( $subSection == 'custom' ) ? 'active' : ''?>" id="settings_includes_tab" data-toggle="tab" href="#settings_includes" role="tab" aria-controls="settings_includes" aria-selected="<?=( $subSection == 'custom' ) ? 'true' : 'false'?>"><?=L( 'Custom Files' )?></a>
                    </li>
                  </ul>
                </div>


                <div class="card-body">
                  <div class="tab-content">
                    <div class="tab-pane fade <?=( $subSection == 'acms' ) ? 'show active' : ''?>" id="settings_cms" role="tabpanel" aria-labelledby="settings_cms_tab">
                      <form action="" method="post" id="form_acms_settings" class="needs-validation" novalidate>
                        <?php if ( !inSafeMode() ) { ?>
                          <div class="form-group">
                            <label class="mb-1" for="acms_settings_password"><?=L( 'Administrative password' )?></label>
                            <?php if ( !empty( $ACMS['ACMS_PASSWORD'] ) ) { ?>
                              <?php if ( !empty( ACMS_PASSWORD ) ) { ?>
                                <div class="alert-danger rounded p-2 mb-1 small"><?=L( 'The current password is hardcoded into the sourcecode. The password settings below will not affect the hardcoded password.' )?></div>
                              <?php } ?>
                              <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input" name="remove_password" value="1" id="acms_settings_remove_password"">
                                <label class="custom-control-label" for="acms_settings_remove_password"><?=L( 'Remove current password' )?></label>
                              </div>
                            <?php } ?>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-fw fa-eye-slash password-eye"></i></span>
                              </div>
                              <input class="form-control" type="password" name="settings[ACMS_PASSWORD]" placeholder="" pattern=".{1,}" id="acms_settings_password" autocomplete="new-password">
                            </div>
                            <small class="form-text text-muted"><?=L( 'Enter a password to set or leave empty to keep an existing password.' )?></small>
                          </div>
                          <div class="form-group">
                            <label class="mb-1" for="acms_settings_password"><?=L( 'Safe mode password' )?></label>
                            <?php if ( !empty( $ACMS['ACMS_SAFE_PASSWORD'] ) ) { ?>
                              <?php if ( !empty( ACMS_SAFE_PASSWORD ) ) { ?>
                                <div class="alert-danger rounded p-2 mb-1 small"><?=L( 'The current safe mode password is hardcoded into the sourcecode. The password settings below will not affect the hardcoded password.' )?></div>
                              <?php } ?>
                              <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input" name="remove_safe_password" value="1" id="acms_settings_remove_safe_password">
                                <label class="custom-control-label" for="acms_settings_remove_safe_password"><?=L( 'Remove current safe mode password' )?></label>
                              </div>
                            <?php } ?>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-fw fa-eye-slash password-eye"></i></span>
                              </div>
                              <input class="form-control" type="password" name="settings[ACMS_SAFE_PASSWORD]" placeholder="" pattern=".{1,}" id="acms_settings_safe_password" autocomplete="new-password">
                            </div>
                            <div class="alert-danger rounded p-2 mt-2 small d-none" id="div_acms_settings_passwords_match"><?=L( 'Important! The administrative password and the safe mode password should not be the same. Otherwise, you will not be able to log in as an administrator. There is no need to set a safe mode password if you\'re not providing the access to someone else.' )?></div>
                            <small class="form-text text-muted"><?=L( 'Enter a password to set or leave empty to keep an existing password.' )?> <?=L( 'In a safe mode you cannot create/edit custom files with a PHP code or do any harm to the server.' )?> <?=L( 'Don\'t set the same password for admin and the safe mode.' )?></small>
                          </div>
                        <?php } ?>
                        <div class="form-group">
                          <label for="acms_settings_urls"><?=L( 'Limit URLs menu' )?></label>
                          <input class="form-control" type="number" name="settings[ACMS_URLS_LIMIT]" placeholder="" pattern="[\d]{1,}" value="<?=htmlspecialchars( $ACMS['ACMS_URLS_LIMIT'] )?>" id="acms_settings_urls" required>
                          <small class="form-text text-muted"><?=L( 'URLs menu will have pagination for domains/subdomains with a higher number of URLs.' )?></small>
                        </div>
                        <div class="form-group">
                          <label for="acms_settings_matches"><?=L( 'Results in Search & Replace' )?></label>
                          <input class="form-control" type="number" name="settings[ACMS_MATCHES_LIMIT]" placeholder="" pattern="[\d]{1,}" value="<?=htmlspecialchars( $ACMS['ACMS_MATCHES_LIMIT'] )?>" id="acms_settings_matches" required>
                          <small class="form-text text-muted"><?=L( 'Limit the number of results in Search & Replace. It does not affect the replacement process.' )?></small>
                        </div>
                        <div class="form-group">
                          <label for="acms_settings_timeout"><?=L( 'Timeout in seconds' )?></label>
                          <input class="form-control" type="number" name="settings[ACMS_TIMEOUT]" placeholder="" pattern="[\d]{1,}" min="5" value="<?=htmlspecialchars( $ACMS['ACMS_TIMEOUT'] )?>" id="acms_settings_timeout" required>
                          <small class="form-text text-muted"><?=L( 'The recommended time is 30 seconds.' )?></small>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="hidden" name="settings[ACMS_DISABLE_HISTORY]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[ACMS_DISABLE_HISTORY]" value="1" id="acms_settings_history" <?=$ACMS['ACMS_DISABLE_HISTORY'] ? 'checked' : ''?>>
                          <label class="custom-control-label" for="acms_settings_history"><?=L( 'Disable history' )?></label>
                          <small class="form-text text-muted"><?=L( 'This will also clear all existing history.' )?></small>
                        </div>
                        <div class="form-group">
                          <label for="acms_settings_editor_default"><?=L( 'Default HTML editor' )?></label>
                          <select class="form-control" name="settings[ACMS_EDITOR_DEFAULT]" id="acms_settings_default_editor" required>
                            <?php
                            $acmsHtmlEditors = [
                              ['value' => 'visual', 'label' => L( 'Visual editor' )],
                              ['value' => 'code', 'label' => L( 'Code' )],
                            ];
                            foreach ( $acmsHtmlEditors as $acmsHtmlEditor ) { ?>
                              <option value="<?=$acmsHtmlEditor['value']?>"<?=( $ACMS['ACMS_EDITOR_DEFAULT'] == $acmsHtmlEditor['value'] ) ? ' selected' : ''?>><?=$acmsHtmlEditor['label']?></option>
                            <?php }
                            ?>
                          </select>
                          <small class="form-text text-muted"><?=L( 'The editor that will be shown by default for editing HTML pages.' )?></small>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="hidden" name="settings[ACMS_EDITOR_HISTORY]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[ACMS_EDITOR_HISTORY]" value="1" id="acms_settings_editor_diff" <?=$ACMS['ACMS_EDITOR_HISTORY'] ? 'checked' : ''?>>
                          <label class="custom-control-label" for="acms_settings_editor_diff"><?=L( 'Show differences between changes' )?></label>
                          <small class="form-text text-muted"><i class="fas fa-exclamation-triangle fa-fw"></i> <?=L( 'Shows an additional tab with differences on edited HTML and other text files. Not recommended for low-memory hosting servers.' )?>
                          </small>
                        </div>
                        <div class="form-group">
                          <label for="acms_settings_domain"><?=L( 'Custom domain' )?></label>
                          <input class="form-control" type="text" name="settings[ACMS_CUSTOM_DOMAIN]" pattern="[-a-z\d.]*" placeholder="" value="<?=htmlspecialchars( $ACMS['ACMS_CUSTOM_DOMAIN'] )?>" id="acms_settings_domain" autocorrect="off" autocapitalize="none">
                          <small class="form-text text-muted"><i class="fas fa-exclamation-triangle fa-fw"></i> <?=L( 'Leave empty in most cases.' )?> <?=L( 'Set only if switching amongst subdomains does not work correctly.' )?>
                          </small>
                        </div>
                        <?php if ( !inSafeMode() ) { ?>
                          <div class="form-group">
                            <label for="acms_settings_ips"><?=L( 'Restrict by IP' )?></label>
                            <input class="form-control" type="text" name="settings[ACMS_ALLOWED_IPS]" pattern="[\d./, :]*" placeholder="" value="<?=htmlspecialchars( str_replace( ',', ', ', $ACMS['ACMS_ALLOWED_IPS'] ) )?>" id="acms_settings_ips">
                            <small class="form-text text-muted"><i class="fas fa-exclamation-triangle fa-fw"></i> <?=L( 'Be careful, as your IP may change and you will restrict yourself out. Enter IP addresses or CIDR separated by commas.' )?>
                            </small>
                          </div>
                        <?php } ?>
                        <div class="text-right">
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="set.acms.settings">
                          <button type="submit" class="btn btn-primary"><?=L( 'Save' )?></button>
                        </div>
                      </form>

                      <?php if ( !inSafeMode() ) { ?>
                        <div class="d-flex align-items-center text-secondary small text-uppercase my-3">
                          <div class="w-100">
                            <hr class="my-0">
                          </div>
                          <div class="mx-2 text-nowrap"><?=L( 'or' )?></div>
                          <div class="w-100">
                            <hr class="my-0">
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-12 col-sm-auto mb-3">
                            <form class="d-inline" action="" method="post">
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="download.acms.json">
                              <button class="btn btn-primary btn-block" type="submit">
                                <i class="fas fa-file-download fw-fw"></i> <?=L( 'Download' )?></button>
                            </form>
                          </div>
                          <div class="col mb-3">
                            <form action="" method="post" id="form_import_acms_json" enctype="multipart/form-data" class="d-inline needs-validation form-wait" novalidate>
                              <div class="input-group">
                                <div class="custom-file">
                                  <input type="file" class="custom-file-input input-upload-file" name="file" accept=".json,application/json" id="input_acms_file" aria-describedby="button_import_file_acms" required>
                                  <label class="custom-file-label text-truncate" for="input_acms_file"><?=L( 'Choose JSON file with settings' )?></label>
                                </div>
                                <div class="input-group-append">
                                  <button class="btn btn-primary" type="submit" id="button_import_file_acms">
                                    <i class="fas fa-file-upload fw-fw"></i> <?=L( 'Upload' )?></button>
                                </div>
                              </div>
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="upload.acms.json">
                            </form>
                          </div>
                        </div>
                      <?php } ?>

                    </div>

                    <div class="tab-pane fade pb-0  <?=( $subSection == 'loader' ) ? 'show active' : ''?>" id="settings_loader" role="tabpanel" aria-labelledby="settings_loader_tab">

                      <form action="" method="post" id="form_loader_settings" class="needs-validation" novalidate>
                        <div class="form-group">
                          <label for="loader_settings_mode"><?=L( 'Loader mode' )?></label>
                          <select class="form-control" name="settings[ARCHIVARIX_LOADER_MODE]" id="loader_settings_mode" required>
                            <?php
                            $loaderModes = [
                              ['value' => 0, 'label' => L( 'This website only (by default)' )],
                              ['value' => -1, 'label' => L( 'This website only, 404 code is for missing URLs' )],
                              ['value' => 1, 'label' => L( 'Integration with a third-party CMS, main page has this website' )],
                              ['value' => 2, 'label' => L( 'Integration with a third-party CMS, main page has other system' )],
                            ];
                            foreach ( $loaderModes as $loaderMode ) { ?>
                              <option value="<?=$loaderMode['value']?>"<?=( $LOADER['ARCHIVARIX_LOADER_MODE'] == $loaderMode['value'] ) ? ' selected' : ''?>><?=$loaderMode['label']?></option>
                            <?php }
                            ?>
                          </select>
                          <small class="form-text text-muted"><?=L( 'Switch the mode if you need to make integration with a third-party system (i.e. Wordpress).' )?></small>
                        </div>
                        <div class="form-group">
                          <label for="loader_settings_protocol"><?=L( 'Protocol' )?></label>
                          <select class="form-control" name="settings[ARCHIVARIX_PROTOCOL]" id="loader_settings_protocol" required>
                            <?php
                            $loaderProtocols = [
                              ['value' => 'any', 'label' => L( 'HTTP and HTTPS (default)' )],
                              ['value' => 'https', 'label' => 'HTTPS'],
                              ['value' => 'http', 'label' => 'HTTP'],
                            ];
                            foreach ( $loaderProtocols as $loaderProtocol ) { ?>
                              <option value="<?=$loaderProtocol['value']?>"<?=( $LOADER['ARCHIVARIX_PROTOCOL'] == $loaderProtocol['value'] ) ? ' selected' : ''?>><?=$loaderProtocol['label']?></option>
                            <?php }
                            ?>
                          </select>
                          <small class="form-text text-muted"><?=L( 'Select a protocol the website should work on.' )?></small>
                        </div>
                        <div class="form-group">
                          <label for="loader_settings_protocol"><?=L( 'Rules for inserting/replacement of custom files and scripts' )?></label>
                          <div class="loader-settings-rules-wrapper" id="sortwrapper">
                            <?php foreach ( $LOADER['ARCHIVARIX_INCLUDE_CUSTOM'] as $customRule ) {
                              $customRule = array_merge( getArrayTemplate( 'custom-rule' ), $customRule );
                              ?>
                              <div class="loader-custom-rule-block border rounded-bottom bg-light p-0 small mb-3 position-relative ">
                                <div class="position-absolute" style="top:-10px; left:-10px;">
                                <span class="fa-stack sort-handle bg-transparent cursor-link" title="<?=L( 'Drag to sort' )?>">
                                  <i class="fas fa-circle fa-stack-2x fa-fw text-dark"></i>
                                  <i class="fas fa-sort fa-stack-1x fa-fw fa-inverse"></i>
                                </span>
                                </div>
                                <div class="position-absolute" style="top:-10px; right:-10px;">
                                <span class="fa-stack remove-loader-custom-rule bg-transparent cursor-link">
                                  <i class="fas fa-circle fa-stack-2x fa-fw text-danger"></i>
                                  <i class="fas fa-trash-alt fa-stack-1x fa-fw fa-inverse"></i>
                                </span>
                                </div>
                                <hr class="bg-success p-1 m-0">
                                <div class="p-3">
                                  <div class="form-group">
                                    <label class="mb-1"><?=L( 'Filename' )?></label>
                                    <div class="input-group">
                                      <input type="text" class="form-control form-control-sm" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][FILE][]" value="<?=htmlspecialchars( $customRule['FILE'] )?>" autocorrect="off" autocapitalize="none">
                                      <div class="input-group-append">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?=L( 'or select' )?></button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                          <?php foreach ( $customFilesOnly as $customFile ) { ?>
                                            <a class="dropdown-item put-custom-file cursor-link" data-filename="<?=htmlspecialchars( $customFile['filename'] )?>">
                                              <i class="far <?=$customFile['mime']['icon']?> fa-fw"></i>
                                              <?=htmlspecialchars( $customFile['filename'] )?>
                                            </a>
                                          <?php } ?>
                                          <?php if ( !count( $customFilesOnly ) ) { ?>
                                            <a class="dropdown-item"><?=L( 'Empty' )?></a>
                                          <?php } ?>
                                        </div>
                                      </div>
                                    </div>
                                    <small class="form-text text-muted"><?=L( 'Files have to be placed into .content.xxxxxx/includes/ directory.' )?></small>
                                  </div>
                                  <div class="form-group">
                                    <label class="mb-1"><?=L( 'Search for a keyphrase' )?></label>
                                    <div class="input-group">
                                      <input class="form-control form-control-sm text-monospace input-custom-keyphrase" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][KEYPHRASE][]" value="<?=htmlspecialchars( $customRule['KEYPHRASE'] )?>">
                                      <div class="input-group-append">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?=L( 'or select' )?></button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                          <a class="dropdown-item put-custom-rule cursor-link" data-keyphrase="&lt;/head&gt;" data-regex="0" data-limit="1" data-position="-1" data-urlmatch=""><?=L( 'Before' )?>
                                            <span class="text-monospace">&lt;/head&gt;</span></a>
                                          <a class="dropdown-item put-custom-rule cursor-link" data-keyphrase="&lt;/body&gt;" data-regex="0" data-limit="1" data-position="-1" data-urlmatch=""><?=L( 'Before' )?>
                                            <span class="text-monospace">&lt;/body&gt;</span></a>
                                          <a class="dropdown-item put-custom-rule cursor-link" data-keyphrase="&lt;body[^&gt;]*&gt;" data-regex="1" data-limit="1" data-position="1" data-urlmatch=""><?=L( 'After' )?>
                                            <span class="text-monospace">&lt;body&gt;</span></a>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                  <div class="form-group">
                                    <label class="mb-1"><?=L( 'Regular expression' )?></label>
                                    <select class="form-control form-control-sm select-custom-regex" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][REGEX][]">
                                      <option value="0" <?=$customRule['REGEX'] == 0 ? 'selected' : ''?>><?=L( 'No' )?></option>
                                      <option value="1" <?=$customRule['REGEX'] == 1 ? 'selected' : ''?>><?=L( 'Yes' )?></option>
                                    </select>
                                  </div>
                                  <div class="form-group">
                                    <label class="mb-1"><?=L( 'Maximum number of inserts/replacements' )?></label>
                                    <input type="number" min="-1" step="1" class="form-control form-control-sm input-custom-limit" value="<?=htmlspecialchars( $customRule['LIMIT'] )?>" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][LIMIT][]">
                                  </div>
                                  <div class="form-group">
                                    <label class="mb-1"><?=L( 'Insert' )?></label>
                                    <select class="form-control form-control-sm select-custom-position" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][POSITION][]">
                                      <option value="-1" <?=$customRule['POSITION'] == -1 ? 'selected' : ''?>><?=L( 'Before the keyphrase' )?></option>
                                      <option value="0" <?=$customRule['POSITION'] == 0 ? 'selected' : ''?>><?=L( 'Replace the keyphrase' )?></option>
                                      <option value="1" <?=$customRule['POSITION'] == 1 ? 'selected' : ''?>><?=L( 'After the keyphrase' )?></option>
                                    </select>
                                  </div>
                                  <div class="form-group">
                                    <label class="mb-1"><?=L( 'Regex match URL paths' )?></label>
                                    <div class="input-group">
                                      <input class="form-control form-control-sm text-monospace input-custom-urlmatch" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][URL_MATCH][]" value="<?=htmlspecialchars( $customRule['URL_MATCH'] )?>">
                                    </div>
                                    <small class="form-text text-muted"><?=L( 'Leave empty to match all URLs' )?></small>
                                  </div>
                                  <div class="form-group">
                                    <label class="mb-1"><?=L( 'Apply to URLs with depth' )?></label>
                                    <div class="input-group">
                                      <div class="input-group-prepend">
                                        <select class="custom-select custom-select-sm input-custom-urldepthoperator" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][URL_DEPTH_OPERATOR][]">
                                          <?php $operator = parseComparisonString( $customRule['URL_DEPTH'] )[0]; ?>
                                          <option value="" <?=$operator == '' ? 'selected' : ''?>><?=L( 'Any' )?></option>
                                          <option value="&gt;" <?=$operator == '>' ? 'selected' : ''?>><?=L( 'Greater than' )?></option>
                                          <option value="&gt;=" <?=$operator == '>=' ? 'selected' : ''?>><?=L( 'Greater or equal to ' )?></option>
                                          <option value="&lt;" <?=$operator == '<' ? 'selected' : ''?>><?=L( 'Less than' )?></option>
                                          <option value="&lt;=" <?=$operator == '<=' ? 'selected' : ''?>><?=L( 'Less or equal to' )?></option>
                                          <option value="==" <?=in_array( $operator, ['=', '=='] ) ? 'selected' : ''?>><?=L( 'Equal to' )?></option>
                                          <option value="!=" <?=$operator == '!=' ? 'selected' : ''?>><?=L( 'Not equal to' )?></option>
                                        </select>
                                      </div>
                                      <input type="number" min="0" step="1" class="form-control form-control-sm text-monospace input-custom-urldepthvalue" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][URL_DEPTH_VALUE][]" value="<?=parseComparisonString( $customRule['URL_DEPTH'] )[1]?>">
                                    </div>
                                    <small class="form-text text-muted"><?=L( 'Leave empty to match any depth' )?></small>
                                  </div>
                                </div>
                              </div>
                            <?php } ?>

                            <div class="d-none loader-custom-rule-block border rounded-bottom bg-light small mb-3 position-relative">
                              <div class="position-absolute" style="top:-10px; left:-10px;">
                                <span class="fa-stack sort-handle bg-transparent cursor-link" title="<?=L( 'Drag to sort' )?>">
                                  <i class="fas fa-circle fa-stack-2x fa-fw text-dark"></i>
                                  <i class="fas fa-sort fa-stack-1x fa-fw fa-inverse"></i>
                                </span>
                              </div>
                              <div class="position-absolute" style="top:-10px; right:-10px;">
                                <span class="fa-stack remove-loader-custom-rule bg-transparent cursor-link">
                                  <i class="fas fa-circle fa-stack-2x text-danger"></i>
                                  <i class="fas fa-trash-alt fa-stack-1x fa-inverse"></i>
                                </span>
                              </div>
                              <hr class="bg-secondary p-1 m-0">
                              <div class="p-3">
                                <div class="form-group">
                                  <label class="mb-1"><?=L( 'Filename' )?></label>
                                  <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][FILE][]" autocorrect="off" autocapitalize="none">
                                    <div class="input-group-append">
                                      <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?=L( 'or select' )?></button>
                                      <div class="dropdown-menu dropdown-menu-right">
                                        <?php foreach ( $customFilesOnly as $customFile ) { ?>
                                          <a class="dropdown-item put-custom-file cursor-link" data-filename="<?=htmlspecialchars( $customFile['filename'] )?>">
                                            <i class="far <?=$customFile['mime']['icon']?> fa-fw"></i>
                                            <?=htmlspecialchars( $customFile['filename'] )?>
                                          </a>
                                        <?php } ?>
                                        <?php if ( !count( $customFilesOnly ) ) { ?>
                                          <a class="dropdown-item"><?=L( 'Empty' )?></a>
                                        <?php } ?>
                                      </div>
                                    </div>
                                  </div>
                                  <small class="form-text text-muted"><?=L( 'Files have to be placed into .content.xxxxxx/includes/ directory.' )?></small>
                                </div>
                                <div class="form-group">
                                  <label class="mb-1"><?=L( 'Search for a keyphrase' )?></label>
                                  <div class="input-group">
                                    <input class="form-control form-control-sm text-monospace input-custom-keyphrase" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][KEYPHRASE][]">
                                    <div class="input-group-append">
                                      <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?=L( 'or select' )?></button>
                                      <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item put-custom-rule cursor-link" data-keyphrase="&lt;/head&gt;" data-regex="0" data-limit="1" data-position="-1" data-urlmatch=""><?=L( 'Before' )?>
                                          <span class="text-monospace">&lt;/head&gt;</span></a>
                                        <a class="dropdown-item put-custom-rule cursor-link" data-keyphrase="&lt;/body&gt;" data-regex="0" data-limit="1" data-position="-1" data-urlmatch=""><?=L( 'Before' )?>
                                          <span class="text-monospace">&lt;/body&gt;</span></a>
                                        <a class="dropdown-item put-custom-rule cursor-link" data-keyphrase="&lt;body[^&gt;]*&gt;" data-regex="1" data-limit="1" data-position="1" data-urlmatch=""><?=L( 'After' )?>
                                          <span class="text-monospace">&lt;body&gt;</span></a>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                                <div class="form-group">
                                  <label class="mb-1"><?=L( 'Regular expression' )?></label>
                                  <select class="form-control form-control-sm select-custom-regex" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][REGEX][]">
                                    <option value="0"><?=L( 'No' )?></option>
                                    <option value="1"><?=L( 'Yes' )?></option>
                                  </select>
                                </div>
                                <div class="form-group">
                                  <label class="mb-1"><?=L( 'Maximum number of inserts/replacements' )?></label>
                                  <input type="number" min="-1" step="1" class="form-control form-control-sm input-custom-limit" value="1" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][LIMIT][]">
                                </div>
                                <div class="form-group">
                                  <label class="mb-1"><?=L( 'Insert' )?></label>
                                  <select class="form-control form-control-sm select-custom-position" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][POSITION][]">
                                    <option value="-1"><?=L( 'Before the keyphrase' )?></option>
                                    <option value="0"><?=L( 'Replace the keyphrase' )?></option>
                                    <option value="1"><?=L( 'After the keyphrase' )?></option>
                                  </select>
                                </div>
                                <div class="form-group">
                                  <label class="mb-1"><?=L( 'Regex match URL paths' )?></label>
                                  <div class="input-group">
                                    <input class="form-control form-control-sm text-monospace input-custom-urlmatch" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][URL_MATCH][]">
                                  </div>
                                  <small class="form-text text-muted"><?=L( 'Leave empty to match all URLs' )?></small>
                                </div>
                                <div class="form-group">
                                  <label class="mb-1"><?=L( 'Apply to URLs with depth' )?></label>
                                  <div class="input-group">
                                    <div class="input-group-prepend">
                                      <select class="custom-select custom-select-sm input-custom-urldepthoperator" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][URL_DEPTH_OPERATOR][]">
                                        <option value="" selected><?=L( 'Any' )?></option>
                                        <option value="&gt;"><?=L( 'Greater than' )?></option>
                                        <option value="&gt;="><?=L( 'Greater or equal to ' )?></option>
                                        <option value="&lt;"><?=L( 'Less than' )?></option>
                                        <option value="&lt;="><?=L( 'Less or equal to' )?></option>
                                        <option value="=="><?=L( 'Equal to' )?></option>
                                        <option value="!="><?=L( 'Not equal to' )?></option>
                                      </select>
                                    </div>
                                    <input type="number" min="0" step="1" class="form-control form-control-sm text-monospace input-custom-urldepthvalue" name="settings[ARCHIVARIX_INCLUDE_CUSTOM][URL_DEPTH_VALUE][]">
                                  </div>
                                  <small class="form-text text-muted"><?=L( 'Leave empty to match any depth' )?></small>
                                </div>
                              </div>
                            </div>
                          </div>

                          <div class="">
                            <button type="button" class="btn btn-sm btn-success" id="create-loader-custom-rule">
                              <i class="fas fa-plus-square fa-fw"></i> <?=L( 'Add a new rule' )?>
                            </button>
                          </div>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="hidden" name="settings[ARCHIVARIX_FIX_MISSING_IMAGES]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[ARCHIVARIX_FIX_MISSING_IMAGES]" value="1" id="loader_settings_images" <?=$LOADER['ARCHIVARIX_FIX_MISSING_IMAGES'] ? 'checked' : ''?>>
                          <label class="custom-control-label" for="loader_settings_images"><?=L( 'Fix missing images' )?></label>
                          <small class="form-text text-muted"><?=L( 'This will show 1x1 pixel transparent png for all missing images instead of a 404 error.' )?></small>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="hidden" name="settings[ARCHIVARIX_FIX_MISSING_CSS]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[ARCHIVARIX_FIX_MISSING_CSS]" value="1" id="loader_settings_css" <?=$LOADER['ARCHIVARIX_FIX_MISSING_CSS'] ? 'checked' : ''?>>
                          <label class="custom-control-label" for="loader_settings_css"><?=L( 'Fix missing .css' )?></label>
                          <small class="form-text text-muted"><?=L( 'This will generate an empty .css response for all missing .css styles instead of a 404 error.' )?></small>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="hidden" name="settings[ARCHIVARIX_FIX_MISSING_JS]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[ARCHIVARIX_FIX_MISSING_JS]" value="1" id="loader_settings_js" <?=$LOADER['ARCHIVARIX_FIX_MISSING_JS'] ? 'checked' : ''?>>
                          <label class="custom-control-label" for="loader_settings_js"><?=L( 'Fix missing .js' )?></label>
                          <small class="form-text text-muted"><?=L( 'This will show generate an empty .js response for all missing .js instead of a 404 error.' )?></small>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="hidden" name="settings[ARCHIVARIX_FIX_MISSING_ICO]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[ARCHIVARIX_FIX_MISSING_ICO]" value="1" id="loader_settings_ico" <?=$LOADER['ARCHIVARIX_FIX_MISSING_ICO'] ? 'checked' : ''?>>
                          <label class="custom-control-label" for="loader_settings_ico"><?=L( 'Fix missing .ico' )?></label>
                          <small class="form-text text-muted"><?=L( 'This will generate a transparent .ico for all missing .ico (i.e., favicon.ico) instead of a 404 error.' )?></small>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="hidden" name="settings[ARCHIVARIX_QUERYLESS]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[ARCHIVARIX_QUERYLESS]" value="1" id="loader_settings_queryless" <?=$LOADER['ARCHIVARIX_QUERYLESS'] ? 'checked' : ''?>>
                          <label class="custom-control-label" for="loader_settings_queryless"><?=L( 'Ignore the query string if no match found' )?></label>
                          <small class="form-text text-muted"><?=L( 'A search will be made for the same URL, but without the query string if no exact match is found.' )?></small>
                        </div>
                        <div class="form-group">
                          <label for="loader_settings_redirect"><?=L( 'Redirect missing pages' )?></label>
                          <input class="form-control" type="text" name="settings[ARCHIVARIX_REDIRECT_MISSING_HTML]" placeholder="" value="<?=htmlspecialchars( $LOADER['ARCHIVARIX_REDIRECT_MISSING_HTML'] )?>" id="loader_settings_redirect" autocorrect="off" autocapitalize="none">
                          <small class="form-text text-muted"><?=L( '301-redirect for all missing pages to save backlink juice.' )?> <?=L( '404-custom page if 404-mode is enabled.' )?>
                          </small>
                        </div>
                        <div class="form-group">
                          <label for="loader_settings_maxage"><?=L( 'Max-age for static files' )?></label>
                          <input class="form-control" type="number" name="settings[ARCHIVARIX_CACHE_CONTROL_MAX_AGE]" placeholder="" pattern="[\d]{1,}" value="<?=htmlspecialchars( $LOADER['ARCHIVARIX_CACHE_CONTROL_MAX_AGE'] )?>" id="loader_settings_maxage" required>
                          <small class="form-text text-muted"><?=L( 'Increase the time for caching static file types. To disable caching, set 0.' )?></small>
                        </div>
                        <div class="form-group">
                          <label for="loader_settings_sitemap"><?=L( 'XML sitemap path' )?></label>
                          <input class="form-control" type="text" name="settings[ARCHIVARIX_SITEMAP_PATH]" placeholder="" value="<?=htmlspecialchars( $LOADER['ARCHIVARIX_SITEMAP_PATH'] )?>" id="loader_settings_sitemap" autocorrect="off" autocapitalize="none">
                          <small class="form-text text-muted"><?=L( 'Enter a path (i.e. /sitemap.xml) to generate an up-to-date sitemap.' )?>
                          </small>
                        </div>
                        <div class="form-group">
                          <label for="loader_settings_content"><?=L( 'Content directory name' )?></label>
                          <input class="form-control" type="text" name="settings[ARCHIVARIX_CONTENT_PATH]" placeholder="" value="<?=htmlspecialchars( $LOADER['ARCHIVARIX_CONTENT_PATH'] )?>" id="loader_settings_content" autocorrect="off" autocapitalize="none">
                          <small class="form-text text-muted"><i class="fas fa-exclamation-triangle fa-fw"></i> <?=L( 'Leave empty in most cases.' )?>  <?=L( 'Set a custom directory name instead of .content.xxxxxxxx if you named it differently or you have multiple content directories.' )?>
                          </small>
                        </div>
                        <div class="form-group">
                          <label for="loader_settings_domain"><?=L( 'Custom domain' )?></label>
                          <input class="form-control" type="text" name="settings[ARCHIVARIX_CUSTOM_DOMAIN]" pattern="[-a-z\d.]*" placeholder="" value="<?=htmlspecialchars( $LOADER['ARCHIVARIX_CUSTOM_DOMAIN'] )?>" id="loader_settings_domain" autocorrect="off" autocapitalize="none">
                          <small class="form-text text-muted"><i class="fas fa-exclamation-triangle fa-fw"></i> <?=L( 'Leave empty in most cases.' )?> <?=L( 'Set to run the original website on its subdomain or to enable subdomains on another domain.' )?>
                          </small>
                        </div>
                        <div class="form-group">
                          <label for="loader_settings_bots"><?=L( 'Patterns for Bots User-Agents' )?></label>
                          <textarea class="form-control form-control-sm" name="settings[ARCHIVARIX_BLOCK_BOTS]" placeholder="" id="loader_settings_bots"><?=htmlspecialchars( implode( "\n", $LOADER['ARCHIVARIX_BLOCK_BOTS'] ) )?></textarea>
                          <small class="form-text text-muted"><?=L( 'A list of User-Agent patterns for bots that will get 404 on visiting any page.' )?>
                          </small>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="hidden" name="settings[ARCHIVARIX_CATCH_MISSING]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[ARCHIVARIX_CATCH_MISSING]" value="1" id="loader_settings_catch_missing" <?=$LOADER['ARCHIVARIX_CATCH_MISSING'] ? 'checked' : ''?>>
                          <label class="custom-control-label" for="loader_settings_catch_missing"><?=L( 'Gather missing URL requests' )?></label>
                          <small class="form-text text-muted"><i class="fas fa-exclamation-triangle fa-fw"></i> <?=L( 'This feature is experimental. You can view all gathered requests from visitors for missing URLs.' )?>
                          </small>
                        </div>
                        <div class="text-right">
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="set.loader.settings">
                          <button type="submit" class="btn btn-primary"><?=L( 'Save' )?></button>
                        </div>
                      </form>

                      <?php if ( !inSafeMode() ) { ?>
                        <div class="d-flex align-items-center text-secondary small text-uppercase my-3">
                          <div class="w-100">
                            <hr class="my-0">
                          </div>
                          <div class="mx-2 text-nowrap"><?=L( 'or' )?></div>
                          <div class="w-100">
                            <hr class="my-0">
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-12 col-sm-auto mb-3">
                            <form class="d-inline" action="" method="post">
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="download.loader.json">
                              <button class="btn btn-primary btn-block" type="submit">
                                <i class="fas fa-file-download fw-fw"></i> <?=L( 'Download' )?></button>
                            </form>
                          </div>
                          <div class="col mb-3">
                            <form action="" method="post" id="form_import_loader_json" enctype="multipart/form-data" class="d-inline needs-validation form-wait" novalidate>
                              <div class="input-group">
                                <div class="custom-file">
                                  <input type="file" class="custom-file-input input-upload-file" name="file" accept=".json,application/json" id="input_loader_file" aria-describedby="button_import_file_loader" required>
                                  <label class="custom-file-label text-truncate" for="input_loader_file"><?=L( 'Choose JSON file with settings' )?></label>
                                </div>
                                <div class="input-group-append">
                                  <button class="btn btn-primary" type="submit" id="button_import_file_loader">
                                    <i class="fas fa-file-upload fw-fw"></i> <?=L( 'Upload' )?></button>
                                </div>
                              </div>
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="upload.loader.json">
                            </form>
                          </div>
                        </div>
                      <?php } ?>

                    </div>


                    <div class="tab-pane fade pb-0 <?=( $subSection == 'custom' ) ? 'show active' : ''?>" id="settings_includes" role="tabpanel" aria-labelledby="settings_includes_tab">
                      <?php if ( !strlen( ACMS_PASSWORD ) && !strlen( $ACMS['ACMS_PASSWORD'] ) && !strlen( ACMS_SAFE_PASSWORD ) && !strlen( $ACMS['ACMS_SAFE_PASSWORD'] ) ) { ?>
                        <div class="alert alert-warning">
                          <i class="fas fa-exclamation-triangle fa-fw"></i> <?=L( 'This section is available only when access is restricted by a password Please, set your password first.' )?>
                        </div>
                      <?php } else { ?>
                        <div class="mb-3">
                          <form action="<?=htmlspecialchars( $_SERVER['REQUEST_URI'] )?>" method="post" enctype="multipart/form-data" class="dropzone w-100" style="min-height: inherit;" id="customFileUpload">
                            <div class="dz-message m-0">
                              <i class="fas fa-file-upload fa-fw fa-2x"></i><br> <?=L( 'Drop a file here to upload.' )?>
                            </div>
                            <div class="fallback">
                              <input name="file" type="file" multiple/>
                            </div>
                            <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                            <input type="hidden" name="action" value="upload.custom.file"/>
                            <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                            <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                            <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                          </form>
                        </div>

                        <?php if ( empty( $customFileMeta ) ) { ?>
                          <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-success" data-toggle="collapse" data-target="#div_create_custom_file" aria-expanded="true" aria-controls="div_create_custom_file" role="button">
                              <i class="fas fa-plus-square fa-fw"></i> <?=L( 'Create' )?></button>
                            <div class="mb-3 collapse" id="div_create_custom_file">
                              <hr>
                              <form action="" method="post" id="formCreateCustomFile" class="needs-validation" novalidate>
                                <div class="form-group">
                                  <label><?=L( 'Filename' )?></label>
                                  <div class="input-group">
                                    <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="far fa-file fa-fw"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="filename" value="" placeholder="<?=L( 'Enter filename' )?>" autocorrect="off" autocapitalize="none" required>
                                  </div>
                                </div>
                                <div class="form-group">
                                  <label><?=L( 'Content' )?></label>
                                  <textarea id="textarea_text" class="d-none" name="content"></textarea>
                                </div>
                                <div class="text-right">
                                  <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                                  <input type="hidden" name="action" value="create.custom.file"/>
                                  <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save fa-fw"></i> <?=L( 'Save' )?></button>
                                </div>
                              </form>
                            </div>
                          </div>
                        <?php } ?>

                        <?php
                        $customFiles = getCustomFiles();
                        if ( !empty( $customFiles ) ) { ?>
                          <table class="table table-hover table-sm table-responsive">
                            <thead>
                            <tr class="bg-dark text-light">
                              <th class="text-center text-nowrap"><?=L( 'Actions' )?></th>
                              <th class="text-nowrap"><?=L( 'Name' )?></th>
                              <th class="text-center text-nowrap"><?=L( 'Size' )?></th>
                              <th class="d-none d-md-table-cell align-middle text-center text-nowrap"><?=L( 'Modified' )?></th>
                              <th class="d-none d-md-table-cell align-middle text-center text-nowrap"><?=L( 'Permissions' )?></Th>
                            </tr>
                            </thead>
                            <?php foreach ( $customFiles as $customFile ) { ?>
                              <tr class="text-monospace">
                                <td class="text-nowrap">
                                  <?php if ( !$customFile['is_dir'] ) { ?>
                                    <form class="d-inline form-wait" action="" method="post" id="form_remove_custom_file_<?=$customFile['id']?>">
                                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                                      <input type="hidden" name="action" value="delete.custom.file">
                                      <input type="hidden" name="filename" value="<?=htmlspecialchars( $customFile['filename'] )?>">
                                      <button type="button" class="btn btn-danger btn-sm btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_remove_custom_file_<?=$customFile['id']?>">
                                        <i class="fas fa-trash-alt fa-fw"></i></button>
                                    </form>
                                  <?php } ?>
                                  <?php if ( !$customFile['is_dir'] && $customFile['mime']['folder'] == 'html' ) { ?>
                                    <form class="d-inline" action="" method="post" id="form_custom_file_<?=$customFile['id']?>">
                                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                                      <input type="hidden" name="action" value="edit.custom.file">
                                      <input type="hidden" name="filename" value="<?=htmlspecialchars( $customFile['filename'] )?>">
                                      <button class="btn btn-primary btn-sm"><i class="fas fa-edit fa-fw"></i></button>
                                    </form>
                                  <?php } ?>
                                </td>
                                <td class="w-100 text-nowrap align-middle">
                                  <i class="far <?=$customFile['mime']['icon']?> fa-fw"></i> <?=htmlspecialchars( $customFile['filename'] )?>
                                </td>
                                <td class="align-middle text-center"><?=$customFile['is_dir'] ? '' : getHumanSize( $customFile['size'], 0 )?></td>
                                <td class="d-none d-md-table-cell align-middle text-nowrap text-center" title="<?=date( 'Y-m-d H:i:s', $customFile['mtime'] )?>"><?=date( 'Y-m-d', $customFile['mtime'] )?></td>
                                <td class="d-none d-md-table-cell align-middle text-center"><?=$customFile['permissions']?></td>
                              </tr>
                            <?php } ?>
                          </table>

                          <?php if ( isset( $customFileMeta ) ) { ?>
                            <div class="mb-3">
                              <hr>
                              <form action="" method="post" id="formCustomFile" class="needs-validation" novalidate>
                                <div class="form-group">
                                  <label><?=L( 'Filename' )?></label>
                                  <div class="input-group">
                                    <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="far <?=$customFile['mime']['icon']?> fa-fw"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="new_filename" value="<?=htmlspecialchars( $customFileMeta['filename'] )?>" placeholder="<?=L( 'Enter filename' )?>" autocorrect="off" autocapitalize="none" required>
                                  </div>
                                </div>
                                <div class="form-group">
                                  <label><?=L( 'Content' )?></label>
                                  <textarea id="textarea_text" class="d-none" name="content"><?=htmlspecialchars( $customFileMeta['data'] );?></textarea>
                                </div>
                                <div class="text-right">
                                  <input type="hidden" name="filename" value="<?=htmlspecialchars( $customFileMeta['filename'] )?>"/>
                                  <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                                  <input type="hidden" name="action" value="update.custom.file"/>
                                  <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save fa-fw"></i> <?=L( 'Save' )?></button>
                                </div>
                              </form>
                            </div>
                          <?php }
                        }
                      } ?>
                    </div>


                  </div>
                </div>
              </div>
            <?php }

            if ( $section == 'template' && !empty( $template ) ) {
              $templateIcons = ['STRING' => 'fa-align-left', 'HTML' => 'fa-code', 'FILE' => 'fa-file', 'URL' => 'fa-link']; ?>
              <div class="row">
                <div class="col-12 col-md-9 mb-3">
                  <div class="border rounded p-3 h-100">
                    <form action="" method="post" class="needs-validation" novalidate>
                      <div class="row">
                        <div class="col-12 col-md-6">
                          <div class="form-group row">
                            <label class="col-sm-2 col-form-label col-form-label-sm text-nowrap"><?=L( 'Name' )?></label>
                            <div class="col-sm-10">
                              <input type="text" class="form-control form-control-sm" name="name" value="<?=htmlspecialchars( $template['name'], ENT_IGNORE )?>" pattern="[A-Za-z0-9-_]{1,64}" autocorrect="off" autocapitalize="none" required>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="form-group row">
                            <label class="col-sm-2 col-form-label col-form-label-sm text-nowrap"><?=L( 'Hostname' )?></label>
                            <div class="col-sm-10">
                              <input type="text" class="form-control form-control-sm" name="hostname" value="<?=htmlspecialchars( $template['hostname'], ENT_IGNORE )?>" autocorrect="off" autocapitalize="none" required>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="form-group row">
                            <label class="col-sm-2 col-form-label col-form-label-sm text-nowrap"><?=L( 'MIME-type' )?></label>
                            <div class="col-sm-10">
                              <input type="text" class="form-control form-control-sm" name="mimetype" value="<?=htmlspecialchars( $template['mimetype'], ENT_IGNORE )?>" autocorrect="off" autocapitalize="none" required>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="form-group row">
                            <label class="col-sm-2 col-form-label col-form-label-sm text-nowrap"><?=L( 'Charset' )?></label>
                            <div class="col-sm-10">
                              <input type="text" class="form-control form-control-sm" name="charset" value="<?=htmlspecialchars( $template['charset'], ENT_IGNORE )?>" autocorrect="off" autocapitalize="none">
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="form-group row">
                            <label class="col-sm-2 col-form-label col-form-label-sm text-nowrap"><?=L( 'Page path' )?></label>
                            <div class="col-sm-10 constructor-wrapper">
                              <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm input-constructor text-monospace border-right-0" name="path" value="<?=htmlspecialchars( $template['path'], ENT_IGNORE )?>" pattern="[/].*" autocorrect="off" autocapitalize="none" required>
                                <div class="input-group-append">
                                  <span class="input-group-text bg-white input-clear cursor-link"><i class="fas fa-times"></i></span>
                                </div>
                              </div>
                              <span class="form-text">
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%year%" data-slash="true">%year%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%month%" data-slash="true">%month%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%day%" data-slash="true">%day%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%hour%" data-slash="true">%hour%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%minute%" data-slash="true">%minute%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%second%" data-slash="true">%second%</span>
                              <?php foreach ( $template['params'] as $param ) {
                                if ( $param[0]['type'] != 'STRING' ) continue; ?>
                                <span class="badge badge-pill alert-success constructor-param cursor-link" data-value="%<?=$param[0]['name']?>%" data-slash="true">%<?=$param[0]['name']?>%</span>
                              <?php } ?>
                            </span>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="form-group row">
                            <label class="col-sm-2 col-form-label col-form-label-sm text-nowrap"><?=L( 'Uploads' )?></label>
                            <div class="col-sm-10 constructor-wrapper">
                              <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm input-constructor text-monospace border-right-0" name="uploads" value="<?=htmlspecialchars( $template['uploads'], ENT_IGNORE )?>" pattern="[/].*" autocorrect="off" autocapitalize="none" required>
                                <div class="input-group-append">
                                  <span class="input-group-text bg-white input-clear cursor-link"><i class="fas fa-times"></i></span>
                                </div>
                              </div>
                              <span class="form-text">
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%md5%" data-slash="true">%md5%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%filename%" data-slash="true">%filename%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%ext%" data-slash="false">%ext%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%year%" data-slash="true">%year%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%month%" data-slash="true">%month%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%day%" data-slash="true">%day%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%hour%" data-slash="true">%hour%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%minute%" data-slash="true">%minute%</span>
                              <span class="badge badge-pill badge-dark constructor-param cursor-link" data-value="%second%" data-slash="true">%second%</span>
                              <?php foreach ( $template['params'] as $param ) {
                                if ( $param[0]['type'] != 'STRING' ) continue; ?>
                                <span class="badge badge-pill alert-success constructor-param cursor-link" data-value="%<?=$param[0]['name']?>%" data-slash="true">%<?=$param[0]['name']?>%</span>
                              <?php } ?>
                            </span>
                            </div>
                          </div>
                        </div>
                        <div class="col-12">
                          <div class="d-md-flex h-100 align-items-end justify-content-end">
                            <div class="text-center text-md-right">
                              <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-save fa-fw"></i> <?=L( 'Save settings only' )?></button>
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="update.template.settings"/>
                              <input type="hidden" name="name_orig" value="<?=$template['name']?>"/>
                              <input type="hidden" name="show" value="edit.template"/>
                              <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                              <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                              <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                            </div>
                          </div>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>


                <div class="col-12 col-md-3 mb-3">
                  <div class="border p-3 rounded h-100">
                    <div class="row">
                      <div class="col pr-0">
                        <form class="d-inline" id="" action="" method="post">
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="show.create.page">
                          <input type="hidden" name="name" value="<?=$template['name']?>">
                          <button class="btn btn-primary btn-block">
                            <i class="far fa-plus-square fa-fw"></i> <?=L( 'Create a page' )?></button>
                        </form>
                      </div>
                      <div class="col-auto">
                        <form class="d-inline" id="form_template_remove" action="" method="post">
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="template.remove">
                          <input type="hidden" name="name" value="<?=$template['name']?>">
                          <button type="button" class="btn btn-danger btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_template_remove" title="<?=L( 'Remove template' )?>">
                            <i class="fas fa-trash-alt fa-fw"></i></button>
                        </form>
                      </div>
                    </div>
                    <?php if ( !empty( $template['params'] ) ) { ?>
                      <div class="mt-2">
                        <div class="d-flex align-items-center">
                          <div class="text-uppercase text-nowrap small muted mr-2"><?=L( 'Found parameters' )?></div>
                          <div class="w-100 ml-auto">
                            <hr>
                          </div>
                        </div>
                        <?php foreach ( $template['params'] as $paramName => $param ) { ?>
                          <button type="button" class="btn alert-success btn-sm badge-pill text-monospace mb-1">
                            <i class="fas fa-fw <?=$templateIcons[$param[0]['type']]?>"></i>
                            <?=htmlspecialchars( $paramName )?>
                            <?php if ( count( $param ) > 1 ) { ?>
                              <span class="badge badge-light badge-pill"><?=count( $param )?></span><?php } ?>
                          </button>
                        <?php } ?>
                      </div>
                    <?php } ?>
                  </div>
                </div>

              </div>

              <div>
                <ul class="nav nav-tabs mb-3" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link" id="wysiwyg-tab" data-toggle="tab" href="#wysiwyg" role="tab" aria-controls="wysiwyg" aria-selected="true"><?=L( 'Visual editor' )?></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link active" id="code-tab" data-toggle="tab" href="#code" role="tab" aria-controls="code" aria-selected="false"><?=L( 'Code' )?></a>
                  </li>
                </ul>
                <div class="tab-content">
                  <div class="tab-pane fade" id="wysiwyg" role="tabpanel" aria-labelledby="wysiwyg-tab">
                    <form action="" method="post" onsubmit="ajaxSaveFile('formHtml'); return false;" id="formHtml">
                      <textarea id="textarea_html" name="content" class="w-100 h-100"><?=htmlspecialchars( convertEncoding( $template['content'], 'UTF-8', $template['charset'] ), ENT_SUBSTITUTE );?></textarea>
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="update.template.content"/>
                      <input type="hidden" name="show" value="edit.template"/>
                      <input type="hidden" name="name" value="<?=htmlspecialchars( $template['name'] )?>"/>
                      <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                      <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                      <input type="hidden" name="ajax" value="1"/>
                      <button type="submit" class="btn btn-primary float-right my-3">
                        <i class="fas fa-save fa-fw"></i> <?=L( 'Save' )?></button>
                    </form>
                  </div>
                  <div class="tab-pane fade show active" id="code" role="tabpanel" aria-labelledby="code-tab">
                    <form action="" method="post" onsubmit="ajaxSaveFile('formCode'); return false;" id="formCode">
                      <textarea id="textarea_text" name="content"><?=htmlspecialchars( convertEncoding( $template['content'], 'UTF-8', $template['charset'] ), ENT_SUBSTITUTE );?></textarea>
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="update.template.content"/>
                      <input type="hidden" name="show" value="edit.template"/>
                      <input type="hidden" name="name" value="<?=htmlspecialchars( $template['name'] )?>"/>
                      <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                      <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                      <input type="hidden" name="ajax" value="1"/>
                      <div class="text-right" style="display: block; position: fixed; bottom: 30px; right: 30px; z-index: 99;">
                        <div class="row" id="template-param-panel">
                          <div class="col-auto alert-info p-2 mr-3 w-auto rounded">
                            <div class="d-flex">
                              <div class="text-monospace">
                                {{@<span id="template-param-type">STRING</span>('<input type="text" id="template-param-name" class="border-0 bg-transparent text-success text-center text-monospace d-inline input-resize-auto" value="name" size="4" pattern="[-A-Za-z0-9]+" onkeypress="return /[-a-z0-9]+/i.test(event.key)" onkeydown="return event.key != 'Enter';" onClick="this.select();">')}}
                              </div>
                              <div class="ml-auto">
                                <button type="button" class="btn btn-success btn-sm ml-2" id="template-param-insert" title="<?=L( 'Insert' )?>">
                                  <i class="fas fa-level-down-alt fa-fw fa-rotate-90"></i></button>
                              </div>
                            </div>
                            <hr class="my-1">
                            <div class="text-center">
                              <button type="button" class="btn btn-success btn-sm badge-pill" data-type="STRING">
                                <i class="fas fa-fw fa-align-left"></i> STRING
                              </button>
                              <button type="button" class="btn btn-success btn-sm badge-pill" data-type="HTML">
                                <i class="fas fa-fw fa-code"></i> HTML
                              </button>
                              <button type="button" class="btn btn-success btn-sm badge-pill" data-type="FILE">
                                <i class="fas fa-fw fa-file"></i> FILE
                              </button>
                              <button type="button" class="btn btn-success btn-sm badge-pill" data-type="URL" data-name="path">
                                <i class="fas fa-fw fa-link"></i> URL
                              </button>
                            </div>
                          </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3" id="cmSave">
                          <i class="fas fa-save fa-fw"></i> <?=L( 'Save' )?></button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php }

            if ( $section == 'page' && !empty( $templateInfo ) ) { ?>
              <h2><?=sprintf( L( 'A new page based on %s template' ), htmlspecialchars( $templateInfo['name'] ) )?></h2>
              <div class="mt-3">
                <form action="" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                  <div class="form-group">
                    <label for="input_uri_address"><?=L( 'URI address' )?></label>
                    <input class="form-control" type="text" name="path" value="<?=$template['path']?>" placeholder="<?=L( 'Enter a path, e.g., /page.html' )?>" pattern="[/].*" id="input_uri_address" required>
                    <div class="custom-control custom-checkbox mt-2">
                      <input type="hidden" name="path_latin" value="0">
                      <input class="custom-control-input" type="checkbox" name="path_latin" id="page_path_latin" checked value="1">
                      <label class="custom-control-label" for="page_path_latin"><?=L( 'Transliterate to latin characters' )?></label>
                    </div>
                  </div>
                  <?php foreach ( $templateInfo['params'] as $name => $data ) {
                    if ( in_array( $data[0]['type'], ['URL'] ) ) continue;
                    ?>
                    <div class="form-group">
                      <label for="input_<?=htmlspecialchars( $name )?>"><?=L( 'Parameter' )?>
                        <i><?=htmlspecialchars( $name )?></i></label>
                      <?php switch ( strtoupper( $data[0]['type'] ) ) {
                        case 'STRING' :
                          echo '<input type="text" class="form-control" name="params[' . htmlspecialchars( $name ) . ']" id="input_' . htmlspecialchars( $name ) . '">';
                          break;
                        case 'HTML' :
                          echo '<textarea class="form-control tinymce-control" name="params[' . htmlspecialchars( $name ) . ']" id="input_' . htmlspecialchars( $name ) . '"></textarea>';
                          break;
                        case 'FILE' :
                          ?>
                          <div class="input-group">
                            <div class="custom-file">
                              <input type="file" class="custom-file-input input-upload-file" name="params[<?=htmlspecialchars( $name )?>]" id="input_<?=htmlspecialchars( $name )?>" aria-describedby="button_import_file">
                              <label class="custom-file-label text-truncate" for="input_import_file"><?=L( 'Choose a file' )?></label>
                            </div>
                            <div class="input-group-append">
                              <button class="btn btn-primary" type="submit" id="button_import_file"><?=L( 'Upload' )?></button>
                            </div>
                          </div>
                        <?php
                        default :
                      } ?>
                    </div>
                  <?php } ?>
                  <input type="hidden" name="template" value="<?=htmlspecialchars( $templateInfo['name'] )?>">
                  <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                  <input type="hidden" name="action" value="create.template.page"/>
                  <div class="text-right">
                    <button type="submit" class="btn btn-primary"><?=L( 'Create' )?></button>
                  </div>
                </form>
              </div>
            <?php }

            if ( $section == 'backups' ) {
              $breakpoints = getBackupsBreakpoints();
              ?>
              <div class="border p-0 mb-3 rounded">
                <div class="row m-3">
                  <div class="col-12 col-sm-auto p-1">
                    <button type="button" class="btn btn-primary m-1 btn-block" data-toggle="modal" data-target="#breakpointModal">
                      <i class="fas fa-plus fa-fw"></i> <?=L( 'New breakpoint' )?></button>
                  </div>
                  <div class="col-12 col-sm-auto p-1">
                    <div class="btn-group m-1 btn-block" role="group">
                      <button id="rollbackGroup" type="button" class="btn btn-primary btn-block dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-undo fa-fw"></i> <?=L( 'Roll back' )?>
                      </button>
                      <div class="dropdown-menu" style="max-width:400px;" aria-labelledby="rollbackGroup">
                        <?php if ( !empty( $breakpoints ) ) { ?>
                          <h6 class="dropdown-header"><?=L( 'Roll back to the breakpoint' )?></h6>
                          <?php foreach ( $breakpoints as $breakpoint ) { ?>
                            <div class="dropdown-divider"></div>
                            <div class="d-flex align-items-center">
                              <div class="text-truncate px-0">
                                <form action="" method="post" class="d-inline form-wait" id="form_history_recover_breakpoint_<?=$breakpoint['rowid']?>">
                                  <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                                  <input type="hidden" name="action" value="history.recover">
                                  <input type="hidden" name="breakpoint" value="<?=$breakpoint['rowid']?>">
                                  <button type="submit" class="dropdown-item text-truncate px-2" href="#" title="<?=L( 'Rollback to' )?>: <?=htmlspecialchars( $breakpoint['name'] )?> (<?=getHumanTime( time() - $breakpoint['created'], 1 ) . ' ' . L( 'ago' )?>)">
                                    <i class="fas fa-arrow-right fa-fw"></i><i class="fas fa-circle text-danger fa-fw"></i> <?=htmlspecialchars( $breakpoint['name'] )?>
                                    <small>
                                      (<?=getHumanTime( time() - $breakpoint['created'], 1 ) . ' ' . L( 'ago' )?>)
                                    </small>
                                  </button>
                                </form>
                              </div>
                            </div>
                          <?php } ?>
                          <div class="dropdown-divider"></div>
                        <?php } ?>
                        <h6 class="dropdown-header"><?=L( 'General actions' )?></h6>
                        <div class="dropdown-divider"></div>
                        <div>
                          <div class="text-truncate px-0">
                            <form class="d-inline form-wait" id="form_history_recover_all" action="" method="post">
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="history.recover">
                              <input type="hidden" name="all" value="1">
                              <button type="submit" class="dropdown-item text-truncate px-2" href="#">
                                <i class="fa fa-fw"></i><i class="fas fa-fast-backward fa-fw"></i> <?=L( 'Roll back all' )?>
                              </button>
                            </form>
                          </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div>
                          <button type="button" class="dropdown-item text-truncate px-2" id="historyRecoverSelected">
                            <i class="fa fa-fw"></i><i class="fas fa-undo fa-fw"></i> <?=L( 'Roll back selected' )?>
                          </button>
                        </div>

                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-sm-auto p-1">
                    <div class="btn-group m-1 btn-block" role="group">
                      <button id="rollbackGroup" type="button" class="btn btn-danger btn-block dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-trash-alt fa-fw"></i> <?=L( 'Purge' )?>
                      </button>
                      <div class="dropdown-menu" style="max-width:400px;" aria-labelledby="rollbackGroup">
                        <?php if ( !empty( $breakpoints ) ) { ?>
                          <h6 class="dropdown-header"><?=L( 'Purge before the breakpoint' )?></h6>
                          <?php foreach ( $breakpoints as $breakpoint ) { ?>
                            <div class="dropdown-divider"></div>
                            <div class="d-flex align-items-center">
                              <div class="text-truncate px-0">
                                <form action="" method="post" class="d-inline form-wait" id="form_history_recover_breakpoint_<?=$breakpoint['rowid']?>">
                                  <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                                  <input type="hidden" name="action" value="history.purge">
                                  <input type="hidden" name="breakpoint" value="<?=$breakpoint['rowid']?>">
                                  <button type="submit" class="dropdown-item text-truncate px-2" href="#" title="<?=L( 'Purge history up to' )?>: <?=htmlspecialchars( $breakpoint['name'] )?> (<?=getHumanTime( time() - $breakpoint['created'], 1 ) . ' ' . L( 'ago' )?>)">
                                    <i class="fas fa-arrow-right fa-fw"></i><i class="far fa-trash-alt fa-fw text-danger"></i> <?=htmlspecialchars( $breakpoint['name'] )?>
                                    <small>
                                      (<?=getHumanTime( time() - $breakpoint['created'], 1 ) . ' ' . L( 'ago' )?>)
                                    </small>
                                  </button>
                                </form>
                              </div>
                            </div>
                          <?php } ?>
                          <div class="dropdown-divider"></div>
                        <?php } ?>
                        <h6 class="dropdown-header"><?=L( 'General actions' )?></h6>
                        <div class="dropdown-divider"></div>
                        <div>
                          <div class="text-truncate px-0">
                            <form class="d-inline form-wait" id="form_history_purge_all" action="" method="post">
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="history.purge">
                              <input type="hidden" name="all" value="1">
                              <button type="button" class="dropdown-item text-truncate px-2 btn-action" href="#" data-toggle="modal" data-target="#confirm-action" data-source="form_history_purge_all">
                                <i class="fa fa-fw"></i><i class="fas fa-trash-alt fa-fw"></i> <?=L( 'Purge all' )?>
                              </button>
                            </form>
                          </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div>
                          <button type="button" class="dropdown-item text-truncate px-2" id="historyPurgeSelected">
                            <i class="fa fa-fw"></i><i class="fas fa-trash-alt fa-fw"></i> <?=L( 'Purge selected' )?>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>


                  <?php $imports = getImportsList();
                  if ( !empty( $imports ) ) { ?>
                    <div class="col-12 col-sm-auto p-1">
                      <div class="btn-group m-1 btn-block dropdown" role="group">
                        <button id="reinstallGroup" type="button" class="btn btn-danger btn-block dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <i class="fas fa-sync fa-fw"></i> <?=L( 'Reinstallation' )?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right pt-5 pt-sm-0 pb-0" style="max-width:available; position: relative;" aria-labelledby="rollbackGroup">
                          <div class="dropdown-header alert-warning" style="white-space: inherit;"><?=L( 'Attention! Reinstallation will delete and replace all your website data URLs and their content.' )?></div>
                          <?php foreach ( $imports as $import ) { ?>
                            <form action="" method="post" class="d-inline form-wait" id="form_import_<?=$import['id']?>">
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="import.perform.reinstall">
                              <input type="hidden" name="filename" value="<?=$import['filename']?>">
                              <div class="dropdown-divider m-0"></div>
                              <div class="dropdown-item p-2">
                                <div class="position-relative">
                                  <img src="<?=$import['screenshot']?>" class="img-fluid w-100 border border-bottom-0" onerror="this.src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAABLCAIAAAA3cxjrAAAABnRSTlMAAAAAAABupgeRAAAAMklEQVR42u3BAQEAAACCIP+vbkhAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEcGcMsAAQVgyaQAAAAASUVORK5CYII=';">
                                  <div class="text-center position-absolute" style="bottom:10%; left:50%; transform: translate(-50%); ">
                                    <span class="badge badge-pill badge-warning shadow py-2"><?=number_format( $import['info']['filescount'], 0 ) . ' URLs, ' . getHumanSize( $import['info']['filessize'] )?></span>
                                  </div>
                                </div>
                                <button type="button" class="btn btn-danger btn-block text-left text-truncate btn-action rounded-0 py-2" title="<?=convertIdnToUtf8( $import['info']['settings']['domain'] )?>" data-toggle="modal" data-target="#confirm-action" data-source="form_import_<?=$import['id']?>" data-message="<?=L( 'Attention! Reinstallation will delete and replace all your website data URLs and their content.' )?>">
                                  <div class="d-flex align-items-center">
                                    <div>
                                      <div class="h5"><?=convertIdnToUtf8( $import['info']['settings']['domain'] )?></div>
                                      <div class="text-monospace small"><?=implode( '-', str_split( $import['info']['settings']['uuid'], 4 ) )?></div>
                                    </div>
                                    <div class="ml-auto pl-3">
                                      <i class="fas fa-arrow-right fa-lg fa-fw"></i>
                                    </div>
                                  </div>
                                </button>
                              </div>
                            </form>
                          <?php } ?>
                        </div>
                      </div>
                    </div>
                  <?php } ?>
                </div>
              </div>

              <table id="table_history" data-toggle="table" data-pagination="true" data-search="true" data-show-export="false" data-page-size="50" data-page-list="[10, 25, 50, 100, 200, All]" data-click-to-select="true" data-maintain-meta-data="true" data-locale="<?=$cmsLocales[getLang()]['code']?>" data-sort-class="bg-light" data-trim-on-search="false" data-unique-id="id" class="table table-bordered table-sm">
                <thead class="thead-light">
                <tr>
                  <th data-field="state" data-checkbox="true" class="px-1"></th>
                  <th data-field="id" data-visible="false"></th>
                  <th data-field="action" data-sortable="true" data-align="center"><?=L( 'Action' )?></th>
                  <th data-field="url" data-sortable="true" data-width="100" data-width-unit="%"><?=L( 'URL' )?></th>
                  <th data-field="created" data-sortable="true" data-align="center"><?=L( 'Created' )?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ( $backups as $historyItem ) {
                  $historySettings = json_decode( $historyItem['settings'], true );
                  ?>
                  <tr>
                    <td></td>
                    <td><?=$historyItem['rowid']?></td>
                    <td class="small"><?=$historyItem['action']?></td>
                    <td>
                      <?php if ( $historyItem['action'] == 'breakpoint' ) { ?>
                        <i class="fas fa-circle fa-fw text-danger"></i>
                        <mark><?=htmlspecialchars( $historySettings['name'] )?></mark>
                      <?php } else { ?>
                        <?=htmlspecialchars( rawurldecode( $historySettings['request_uri'] ), ENT_IGNORE )?>
                        <br/>
                        <small class="text-secondary"><?=convertIdnToUtf8( $historySettings['hostname'] )?></small>
                      <?php } ?>
                    </td>
                    <td class="small"><?=date( 'Y-m-d H:i:s', $historyItem['created'] )?></td>
                  </tr>
                <?php } ?>
                </tr>
                </tbody>
              </table>
              <?php
            }

            if ( $section == 'htaccess' && !inSafeMode() ) { ?>
              <form action="" method="post">
                <h3><?=L( '.htaccess editor' )?></h3>
                <div class="form-group">
                  <label><?=L( 'Content' )?></label>
                  <textarea class="form-control codemirror-editor" data-mime="text/plain" data-readonly="false" name="htaccess"><?=htmlspecialchars( file_get_contents( __DIR__ . DIRECTORY_SEPARATOR . '.htaccess' ) )?></textarea>
                </div>
                <div class="text-right">
                  <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                  <input type="hidden" name="action" value="edit.htaccess">
                  <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                  <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                  <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                  <button type="submit" class="btn btn-primary"><?=L( 'Save' )?></button>
                </div>
              </form>
            <?php }

            if ( inExpertMode() && $section == 'import.urls' ) { ?>
              <form action="" method="post" class="import-external-urls-wrapper">
                <h3><?=L( 'Import External URLs' )?></h3>
                <div class="form-group mt-3">
                  <label for="input_subdomain"><?=L( 'Import everything to a subdomain' )?></label>
                  <div class="input-group">
                    <input type="text" class="form-control text-right" name="subdomain" placeholder="<?=L( 'subdomain' )?>" id="input_subdomain" autocorrect="off" autocapitalize="none">
                    <div class="input-group-append">
                      <span class="input-group-text">.<?=convertIdnToUtf8( $uuidSettings['domain'] )?></span>
                    </div>
                  </div>
                  <small class="form-text text-muted"><?=L( 'Leave empty for a normal import.' )?></small>
                </div>
                <label for="external_urls_user_agent"><?=L( 'User-Agent' )?></label>
                <div class="input-group mb-3">
                  <input class="form-control text-monospace input-user-agent" id="external_urls_user_agent" name="user_agent" value="Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)">
                  <div class="input-group-append">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?=L( 'or select' )?></button>
                    <div class="dropdown-menu dropdown-menu-right">
                      <a class="dropdown-item put-custom-rule cursor-link" data-useragent="Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)">GoogleBot</a>
                      <a class="dropdown-item put-custom-rule cursor-link" data-useragent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36">Chrome</a>
                      <a class="dropdown-item put-custom-rule cursor-link" data-useragent="Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0">Firefox</a>
                      <a class="dropdown-item put-custom-rule cursor-link" data-useragent="Archivarix-CMS/<?=ACMS_VERSION?> (+https://archivarix.com/en/cms/)">Archivarix
                        CMS</a>
                    </div>
                  </div>
                </div>
                <div class="custom-control custom-checkbox">
                  <input type="hidden" name="web_archive" value="0">
                  <input id="external_urls_web_arvchive" class="custom-control-input" type="checkbox" name="web_archive" value="1">
                  <label class="custom-control-label" for="external_urls_web_arvchive"><?=L( 'Grab from WebArchive' )?></label>
                </div>
                <div class="custom-control custom-checkbox">
                  <input type="hidden" name="make_local" value="0">
                  <input id="external_urls_make_local" class="custom-control-input" type="checkbox" name="make_local" value="1">
                  <label class="custom-control-label" for="external_urls_make_local"><?=L( 'Update code to make local' )?></label>
                </div>
                <div class="text-right">
                  <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                  <input type="hidden" name="action" value="import.urls.perform">
                  <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                  <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                  <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                  <button type="submit" class="btn btn-primary"><?=L( 'Import' )?></button>
                </div>

                <div class="form-group">
                  <label><?=L( 'External URLs list' )?></label>
                  <textarea class="form-control codemirror-editor" data-mime="text/plain" data-readonly="false" name="externalUrlsList"><?=( !empty( $_POST['externalUrlsList'] ) ? htmlspecialchars( $_POST['externalUrlsList'] ) : '' )?></textarea>
                </div>
              </form>
            <?php }

            if ( $section == 'trackers' ) {
              $includesPath  = createDirectory( 'includes' );
              $trackersFile  = $includesPath . DIRECTORY_SEPARATOR . 'trackers.txt';
              $trackersExist = file_exists( $trackersFile );
              ?>
              <form action="" method="post">
                <h3><?=L( 'Insert trackers/analytics' )?></h3>
                <div class="form-group">
                  <label><?=L( 'Code' )?></label>
                  <textarea class="form-control codemirror-editor" data-mime="application/javascript" data-readonly="false" name="trackers"><?=$trackersExist ? htmlspecialchars( file_get_contents( $trackersFile ) ) : ''?></textarea>
                </div>
                <div class="text-right">
                  <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                  <input type="hidden" name="action" value="edit.trackers">
                  <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                  <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                  <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                  <button type="submit" class="btn btn-primary"><?=L( 'Save' )?></button>
                </div>
              </form>
            <?php }

            if ( $section == 'table' ) {
              ?>
              <table data-toggle="table" data-pagination="true" data-pagination-v-align="both" data-search="true" data-show-columns="true" data-show-export="true" data-page-size="100" data-page-list="[10, 25, 50, 100, 200, All]" data-maintain-meta-data="true" data-locale="<?=$cmsLocales[getLang()]['code']?>" data-sort-class="bg-light" data-sort-name="status" data-sort-order="desc" data-trim-on-search="false" class="table table-bordered table-sm">
                <thead class="thead-light">
                <?php foreach ( $tableHeaders as $tableHeaderId => $tableHeader ) { ?>
                  <th data-field="<?=$tableHeaderId?>" data-sortable="true" data-visible="true" data-align="left"><?=L( $tableHeader['title'] )?></th>
                <?php } ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ( $tableData as $tableRowId => $tableRow ) { ?>
                  <tr data-unique-id="<?=htmlspecialchars( $tableRowId )?>">
                    <td data-search="true" class="small"><?=htmlspecialchars( $tableRowId )?></td>
                    <?php for ( $i = 0; $i < count( $tableRow ); $i++ ) { ?>
                      <td data-search="true" class="small"><?=htmlspecialchars( $tableRow[$i] )?></td>
                    <?php } ?>
                  </tr>
                <?php } ?>
                </tbody>
              </table>
              <?php
            }

            if ( $section == 'tools' ) {
              $missingExtensions = getMissingExtensions( ['zip', 'curl'] );
              ?>
              <div class="row row-cols-1 row-cols-md-3">
                <div class="col mb-4">
                  <div class="card h-100 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Websites import' )?>
                    </div>
                    <div class="card-body small">
                      <?=L( 'Import/reinstall websites created by Archivarix.' )?>
                      <?php if ( !empty( $missingExtensions ) ) { ?>
                        <div class="p-3 rounded bg-danger text-light mt-3 font-weight-bold"><?=sprintf( L( 'This tool requires the following PHP extensions to be installed: %s.' ), implode( ', ', $missingExtensions ) )?></div>
                      <?php } ?>
                    </div>
                    <div class="card-footer text-right">
                      <form action="" method="post" id="">
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="import.view">
                        <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                        <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                        <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                        <button type="submit" class="btn btn-sm btn-primary" <?=!empty( $missingExtensions ) ? ' disabled' : ''?>><?=L( 'An import tool' )?></button>
                      </form>
                    </div>
                  </div>
                </div>

                <div class="col mb-4">
                  <div class="card h-100 mb-4 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Website conversion to UTF-8' )?>
                    </div>
                    <div class="card-body small">
                      <?=L( 'This tool correctly converts to UTF-8 all HTML pages and other types of text files with a non-UTF-8 encoding.' )?>
                    </div>
                    <div class="card-footer text-right">
                      <form action="" method="post" id="form_convert_utf8" class="form-wait">
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="convert.utf8">
                        <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                        <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                        <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                        <button type="button" class="btn btn-sm btn-primary btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_convert_utf8">
                          <?=L( 'Convert to' )?> UTF-8
                        </button>
                      </form>
                    </div>
                  </div>
                </div>

                <div class="col mb-4">
                  <div class="card h-100 mb-4 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Allow website in robots.txt' )?>
                    </div>
                    <div class="card-body small">
                      <form action="" method="post" id="form_robots_allow">
                        <?=L( 'Create/overwrite robots.txt and allow crawling the entire website.' )?>
                        <div class="custom-control custom-checkbox mt-2">
                          <input id="tool_include_sitemap" type="checkbox" class="custom-control-input" name="sitemap_include" value="1" data-toggle="collapse" data-target="#tool_robots_sitemap">
                          <label class="custom-control-label" for="tool_include_sitemap"><?=L( 'Include sitemap.xml' )?></label>
                        </div>
                        <div class="collapse mt-1" id="tool_robots_sitemap">
                          <?php
                          $LOADER               = loadLoaderSettings();
                          $toolRobotsSitemapUrl = detectSitemapUrl();
                          ?>
                          <input type="input" class="form-control form-control-sm text-monospace" name="sitemap" value="<?=htmlspecialchars( $toolRobotsSitemapUrl )?>">
                          <span class="form-text text-muted"><?=L( 'Make sure the protocol and the domain name are correct.' )?></span>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="robots.allow">
                      <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                      <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                      <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                      <button type="submit" class="btn btn-sm btn-primary"><?=L( 'Fix robots.txt' )?></button>
                      </form>
                    </div>
                  </div>
                </div>

                <?php
                $missingExtensions = getMissingExtensions( ['dom', 'libxml', 'mbstring'] );
                ?>
                <div class="col mb-4">
                  <div class="card h-100 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Versions in CSS and JS' )?>
                    </div>
                    <div class="card-body small">
                      <?=L( 'Some websites use version control by adding ?ver=x.x to the end of URLs. This tool will fix such URLs. It will also fix URLs with PHPSESSID.' )?>
                      <?php if ( !empty( $missingExtensions ) ) { ?>
                        <div class="p-3 rounded bg-danger text-light mt-3 font-weight-bold"><?=sprintf( L( 'This tool requires the following PHP extensions to be installed: %s.' ), implode( ', ', $missingExtensions ) )?></div>
                      <?php } ?>
                    </div>
                    <div class="card-footer text-right">
                      <form action="" method="post" id="form_remove_version_control" class="form-wait">
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="version.control.remove">
                        <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                        <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                        <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                        <button type="button" class="btn btn-sm btn-primary btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_remove_version_control" <?=!empty( $missingExtensions ) ? ' disabled' : ''?>><?=L( 'Fix versions in URLs' )?></button>
                      </form>
                    </div>
                  </div>
                </div>

                <div class="col mb-4">
                  <div class="card h-100 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Remove broken links' )?>
                    </div>
                    <div class="card-body small">
                      <form action="" method="post" id="form_remove_broken_links" class="form-wait">
                        <?=L( 'This tool will scan all internal links that lead to missing pages and remove those links while keeping anchors.' )?>
                        <div class="custom-control custom-checkbox mt-2">
                          <input id="tool_broken_links_files" type="checkbox" class="custom-control-input" name="check_files" value="1">
                          <label class="custom-control-label" for="tool_broken_links_files"><?=L( 'Additionally check files on this server' )?>
                          </label>
                        </div>
                        <div class="custom-control custom-checkbox mt-2">
                          <input id="tool_broken_links_stats" type="checkbox" class="custom-control-input" name="stats_only" value="1">
                          <label class="custom-control-label" for="tool_broken_links_stats"><?=L( 'Do not remove, show a list only' )?>
                          </label>
                        </div>
                        <?php if ( !empty( $missingExtensions ) ) { ?>
                          <div class="p-3 rounded bg-danger text-light mt-3 font-weight-bold"><?=sprintf( L( 'This tool requires the following PHP extensions to be installed: %s.' ), implode( ', ', $missingExtensions ) )?></div>
                        <?php } ?>
                    </div>
                    <div class="card-footer text-right">
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="broken.links.remove">
                      <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                      <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                      <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                      <button type="button" class="btn btn-sm btn-primary btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_remove_broken_links" <?=!empty( $missingExtensions ) ? ' disabled' : ''?>><?=L( 'Remove links' )?></button>
                      </form>
                    </div>
                  </div>
                </div>

                <div class="col mb-4">
                  <div class="card h-100 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Remove broken images' )?>
                    </div>
                    <div class="card-body small">
                      <form action="" method="post" id="form_remove_broken_images" class="form-wait">
                        <?=L( 'This tool will scan all image tags for missing internal URLs and remove those image tags.' )?>
                        <div class="custom-control custom-checkbox mt-2">
                          <input id="tool_broken_images_files" type="checkbox" class="custom-control-input" name="check_files" value="1">
                          <label class="custom-control-label" for="tool_broken_images_files"><?=L( 'Additionally check files on this server' )?>
                          </label>
                        </div>
                        <div class="custom-control custom-checkbox mt-2">
                          <input id="tool_broken_images_stats" type="checkbox" class="custom-control-input" name="stats_only" value="1">
                          <label class="custom-control-label" for="tool_broken_images_stats"><?=L( 'Do not remove, show a list only' )?>
                          </label>
                        </div>
                        <?php if ( !empty( $missingExtensions ) ) { ?>
                          <div class="p-3 rounded bg-danger text-light mt-3 font-weight-bold"><?=sprintf( L( 'This tool requires the following PHP extensions to be installed: %s.' ), implode( ', ', $missingExtensions ) )?></div>
                        <?php } ?>
                    </div>
                    <div class="card-footer text-right">
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="broken.images.remove">
                      <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                      <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                      <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                      <button type="button" class="btn btn-sm btn-primary btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_remove_broken_images" <?=!empty( $missingExtensions ) ? ' disabled' : ''?>><?=L( 'Remove images' )?></button>
                      </form>
                    </div>
                  </div>
                </div>

                <div class="col mb-4">
                  <div class="card h-100 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Insert trackers/analytics' )?>
                    </div>
                    <div class="card-body small">
                      <?=L( 'This tool creates/edits a custom rule to insert trackers or analytics before closing &lt;/head&gt; tag into all website pages.' )?>
                    </div>
                    <div class="card-footer text-right">
                      <form action="" method="post">
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="edit.trackers">
                        <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                        <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                        <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                        <button type="submit" class="btn btn-sm btn-primary"><?=L( 'Edit trackers code' )?></button>
                      </form>
                    </div>
                  </div>
                </div>

                <div class="col mb-4">
                  <div class="card h-100 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Viewport meta tag' )?>
                    </div>
                    <div class="card-body small">
                      <form action="" method="post" id="form_update_viewport">
                        <?=L( 'Add the viewport meta tag to all pages of the site to adapt the site to display on mobile devices.' )?>
                        <div class="custom-control custom-checkbox mt-2">
                          <input id="checkbox_viewport_value" type="checkbox" class="custom-control-input" data-toggle="collapse" data-target="#tool_viewport_value">
                          <label class="custom-control-label" for="checkbox_viewport_value"><?=L( 'Custom viewport value' )?></label>
                        </div>
                        <div class="collapse mt-1" id="tool_viewport_value">
                          <input type="input" class="form-control form-control-sm text-monospace" name="viewport_value" value="width=device-width, initial-scale=1">
                        </div>
                        <div class="custom-control custom-checkbox mt-2">
                          <input id="tool_viewport_overwrite" type="checkbox" class="custom-control-input" name="overwrite" value="1">
                          <label class="custom-control-label" for="tool_viewport_overwrite"><?=L( 'Overwrite if already presents' )?>
                          </label>
                        </div>
                        <?php if ( !empty( $missingExtensions ) ) { ?>
                          <div class="p-3 rounded bg-danger text-light mt-3 font-weight-bold"><?=sprintf( L( 'This tool requires the following PHP extensions to be installed: %s.' ), implode( ', ', $missingExtensions ) )?></div>
                        <?php } ?>
                    </div>
                    <div class="card-footer text-right">
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="update.viewport">
                      <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                      <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                      <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                      <button type="button" class="btn btn-sm btn-primary btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_update_viewport" <?=!empty( $missingExtensions ) ? ' disabled' : ''?>><?=L( 'Add viewport' )?></button>
                      </form>
                    </div>
                  </div>
                </div>

                <div class="col mb-4">
                  <div class="card h-100 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Work with external links' )?>
                    </div>
                    <div class="card-body small">
                      <?=L( 'Set rel attribute value for all internal links. E.g. make all external links nofollow.' )?>
                      <br>
                      <?=L( 'You can also remove all external links keeping the anchors and content.' )?>
                      <?php if ( !empty( $missingExtensions ) ) { ?>
                        <div class="p-3 rounded bg-danger text-light mt-3 font-weight-bold"><?=sprintf( L( 'This tool requires the following PHP extensions to be installed: %s.' ), implode( ', ', $missingExtensions ) )?></div>
                      <?php } ?>
                    </div>
                    <div class="card-footer small">
                      <div class="">
                        <a href="#div_update_external_links" class="expand-label text-dark" data-toggle="collapse" data-target="#div_update_external_links" aria-expanded="false" aria-controls="div_update_external_links">
                          <i class="fas fa-caret-right fa-fw mr-1"></i><?=L( 'Show settings' )?>
                        </a>
                      </div>
                      <div class="collapse mt-2" id="div_update_external_links">
                        <form action="" method="post" id="form_update_external_links" class="form-wait">
                          <div class="row">
                            <div class="col-12">
                              <div class="form-group">
                                <label>rel</label>
                                <input class="form-control form-control-sm w-100" type="text" name="attributes[rel]" value="" placeholder="<?=L( 'Leave empty to skip' )?>" autocorrect="off" autocapitalize="none">
                              </div>
                              <div class="form-group">
                                <label>target</label>
                                <input class="form-control form-control-sm  w-100" type="text" name="attributes[target]" value="" placeholder="<?=L( 'Leave empty to skip' )?>" autocorrect="off" autocapitalize="none">
                              </div>
                            </div>
                            <div class="col-12 text-center align-self-center">
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="external.links.update">
                              <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                              <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                              <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                              <button type="button" class="btn btn-sm btn-primary btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_update_external_links" <?=!empty( $missingExtensions ) ? ' disabled' : ''?>><?=L( 'Update external links' )?></button>

                              <div class="d-flex align-items-center text-secondary small text-uppercase my-2">
                                <div class="w-100">
                                  <hr class="my-0">
                                </div>
                                <div class="mx-2 text-nowrap"><?=L( 'or' )?></div>
                                <div class="w-100">
                                  <hr class="my-0">
                                </div>
                              </div>

                              <button type="button" class="btn btn-sm btn-danger btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_remove_external_links"><?=L( 'Remove external links' )?></button>
                            </div>
                          </div>
                        </form>
                        <form action="" method="post" id="form_remove_external_links" class="form-wait">
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="external.links.remove">
                          <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                          <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                          <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col mb-4">
                  <div class="card h-100 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Broken URLencoded links' )?>
                    </div>
                    <div class="card-body small">
                      <?=L( 'Some URLencode links containing non-Latin letters or characters might be encoded incorrectly. This tool will fix such links in HTML code.' )?>
                      <?php if ( !empty( $missingExtensions ) ) { ?>
                        <div class="p-3 rounded bg-danger text-light mt-3 font-weight-bold"><?=sprintf( L( 'This tool requires the following PHP extensions to be installed: %s.' ), implode( ', ', $missingExtensions ) )?></div>
                      <?php } ?>
                    </div>
                    <div class="card-footer text-right">
                      <form action="" method="post" id="form_update_encoded_urls" class="form-wait">
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="broken.encoded.urls">
                        <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                        <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                        <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                        <button type="button" class="btn btn-sm btn-primary btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_update_encoded_urls" <?=!empty( $missingExtensions ) ? ' disabled' : ''?>><?=L( 'Fix URL encoding' )?></button>
                      </form>
                    </div>
                  </div>
                </div>

                <?php $missingExtensions = getMissingExtensions( ['imagick'] ); ?>
                <div class="col mb-4">
                  <div class="card h-100 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Images conversion to WebP' )?>
                    </div>
                    <div class="card-body small">
                      <?=L( 'Convert all images to WebP format and keep the same URLs.' )?>
                      <?php if ( !empty( $missingExtensions ) ) { ?>
                        <div class="p-3 rounded bg-danger text-light mt-3 font-weight-bold"><?=sprintf( L( 'This tool requires the following PHP extensions to be installed: %s.' ), implode( ', ', $missingExtensions ) )?></div>
                      <?php } else {
                        $imagickFormats = \Imagick::queryformats();
                        if ( !in_array( 'WEBP', $imagickFormats ) ) { ?>
                          <div class="p-3 rounded bg-danger text-light mt-3 font-weight-bold"><?=sprintf( L( 'Unfortunately your %s does not support %s' ), 'php-imagick', 'WebP' )?></div>
                        <?php } ?>
                      <?php } ?>
                    </div>
                    <div class="card-footer text-right">
                      <form action="" method="post">
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="convert.webp">
                        <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                        <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                        <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                        <button type="submit" class="btn btn-sm btn-primary"><?=L( 'Convert' )?></button>
                      </form>
                    </div>
                  </div>
                </div>

                <?php
                $missingExtensions = getMissingExtensions( ['dom', 'libxml', 'mbstring'] );
                $uuidSettings      = getSettings(); ?>
                <div class="col mb-4">
                  <div class="card h-100 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Detect pages depth' )?>
                    </div>
                    <div class="card-body small">
                      <?=L( 'Scan all internal links and detect pages with depth and orphan pages' )?>
                      <?php if ( !empty( $missingExtensions ) ) { ?>
                        <div class="p-3 rounded bg-danger text-light mt-3 font-weight-bold"><?=sprintf( L( 'This tool requires the following PHP extensions to be installed: %s.' ), implode( ', ', $missingExtensions ) )?></div>
                      <?php } ?>
                    </div>
                    <div class="card-footer text-right">
                      <form action="" method="post">
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="update.pages.depth">
                        <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                        <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                        <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                        <button type="submit" class="btn btn-sm btn-primary" <?=!empty( $missingExtensions ) ? ' disabled' : ''?>>
                          <?=L( 'Scan & Detect' )?>
                        </button>
                      </form>
                    </div>
                  </div>
                </div>

                <?php if ( !inSafeMode() ) { ?>
                  <div class="col mb-4">
                    <div class="card h-100 shadow">
                      <div class="card-header text-white bg-dark">
                        <?=L( '.htaccess editor' )?>
                      </div>
                      <div class="card-body small">
                        <?=L( 'Be careful, as invalid syntax would result in an internal server error.' )?>
                      </div>
                      <div class="card-footer text-right">
                        <form action="" method="post">
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="edit.htaccess">
                          <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                          <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                          <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                          <button type="submit" class="btn btn-sm btn-primary"><?=L( 'Edit .htaccess' )?></button>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php } ?>

                <?php $missingExtensions = getMissingExtensions( ['curl', 'zip'] ); ?>

                <?php if ( !inSafeMode() ) { ?>
                  <div class="col mb-4">
                    <div class="card h-100 shadow">
                      <div class="card-header text-white bg-dark">
                        <?=L( 'Flat-File version' )?>
                      </div>
                      <div class="card-body small">
                        <p><?=L( 'Export website to a flat-file structure' )?></p>
                        <?php
                        $convertedWebsites = getConvertedWebsites();
                        foreach ( $convertedWebsites as $convertedWebsite ) {
                          ?>
                          <div>
                            <i class="far fa-file-archive fa-fw"></i>
                            <a href="<?=basename( $sourcePath ) . "/exports/" . basename( $convertedWebsite )?>" class="" text-info>
                              <?=htmlspecialchars( basename( $convertedWebsite ) )?>
                              (<?=getHumanSize( filesize( $convertedWebsite ) )?>)
                            </a>
                          </div>
                          <?php
                        }
                        ?>
                      </div>

                      <div class="card-footer small">
                        <div class="d-flex align-items-center">
                          <div class="mr-auto">
                            <a href="#div_export_flat_file" class="expand-label text-dark" data-toggle="collapse" data-target="#div_export_flat_file" aria-expanded="false" aria-controls="div_export_flat_file">
                              <i class="fas fa-caret-right fa-fw mr-1"></i><?=L( 'Show settings' )?>
                            </a>
                          </div>
                          <?php if ( !empty( $convertedWebsites ) ) { ?>
                            <div class="mr-2">
                              <form action="" method="post">
                                <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                                <input type="hidden" name="action" value="fun.convert.website.delete">
                                <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                                <button type="submit" class="btn btn-sm btn-danger">
                                  <?=L( 'Remove' )?></button>
                              </form>
                            </div>
                          <?php } ?>
                          <div class="">
                            <form action="" method="post" class="form-wait" id="form_convert_website">
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="fun.convert.website">
                              <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                              <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                              <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                              <button type="submit" class="btn btn-sm btn-primary">
                                <?=L( 'Export' )?></button>
                            </form>
                          </div>
                        </div>
                        <div class="collapse mt-2" id="div_export_flat_file">
                          <div class="custom-control custom-checkbox mt-2">
                            <input id="tool_convert_website_strip_queries" form="form_convert_website" type="checkbox" class="custom-control-input" name="options[strip_queries]" value="1" checked>
                            <label class="custom-control-label" for="tool_convert_website_strip_queries"><?=L( 'Strip queries to keep original file names' )?>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php } ?>

                <?php $uuidSettings = getSettings(); ?>
                <div class="col mb-4">
                  <div class="card h-100 shadow">
                    <div class="card-header text-white bg-dark">
                      <?=L( 'Convert to www/non-www' )?>
                    </div>
                    <div class="card-body small">
                      <?=L( 'Convert the website to www/non-www version.' )?>
                    </div>
                    <div class="card-footer text-right">
                      <form action="" method="post">
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="convert.www">
                        <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                        <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                        <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                        <?php if ( !empty( $uuidSettings['non-www'] ) ) { ?>
                          <input type="hidden" name="www" value="1">
                        <?php } else { ?>
                          <input type="hidden" name="www" value="0">
                        <?php } ?>
                        <button type="submit" class="btn btn-sm btn-primary">
                          <?php if ( !empty( $uuidSettings['non-www'] ) ) { ?>
                            <?=L( 'Convert to www' )?>
                          <?php } else { ?>
                            <?=L( 'Convert to non-www' )?>
                          <?php } ?>
                        </button>
                      </form>
                    </div>
                  </div>
                </div>

                <div class="col mb-4">
                  <div class="card h-100 shadow">
                    <div class="card-header text-white bg-dark">Cloudflare</div>
                    <div class="card-body small">
                      <?=L( 'Purge Cloudflare cache using API access.' )?>
                      <?php $acmsCloudflare = readMetaData( 'acms_cloudflare' );
                      if ( !empty( $acmsCloudflare ) ) {
                        $devModeTimeRemaining = !empty( $acmsCloudflare['data']['dev_mode_time_remaining'] ) ? $acmsCloudflare['data']['dev_mode_time_remaining'] - time() : 0;
                        ?>
                        <div class="alert-info p-3 my-3 rounded">
                          <?=L( 'Hostname' )?>: <?=$acmsCloudflare['data']['name']?><br/>
                          Dev
                          Mode: <?=( $devModeTimeRemaining > 0 ? getHumanTime( $devModeTimeRemaining ) : L( 'off' ) )?>
                          <br>
                          Zone ID: <?=$acmsCloudflare['data']['zone_id']?>
                        </div>
                        <div class="row">
                          <div class="col-xs-auto col-xl-6">
                            <form action="" method="post" class="mb-3">
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="api.cloudflare.purge.cache">
                              <button type="submit" class="btn btn-success btn-sm btn-block"><?=L( 'Purge cache' )?></button>
                            </form>
                          </div>
                          <div class="col-xs-auto col-xl-6">
                            <form action="" method="post" class="">
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="api.cloudflare.development.mode">
                              <input type="hidden" name="params[enable]" value="<?=( $devModeTimeRemaining > 0 ? 0 : 1 )?>">
                              <button type="submit" class="btn <?=( $devModeTimeRemaining > 0 ? 'btn-danger' : 'btn-success' )?> btn-sm btn-block text-truncate"><?=( $devModeTimeRemaining > 0 ? L( 'Turn OFF the Dev Mode' ) : L( 'Turn ON the Dev Mode' ) )?></button>
                            </form>
                          </div>
                        </div>
                      <?php } else { ?>
                        <form action="" method="post" class="needs-validation mt-3" novalidate>
                          <div class="form-group">
                            <label for="input_cf_zone_id">Zone ID</label>
                            <input type="text" class="form-control form-control-sm text-monospace" name="zone_id" id="input_cf_zone_id" pattern="[\w]{32}" autocorrect="off" autocapitalize="none" required>
                          </div>
                          <div class="form-group">
                            <label for="input_cf_token">API token</label>
                            <div class="input-group mb-3">
                              <input type="text" class="form-control form-control-sm text-monospace" name="token" id="input_cf_token" pattern="[-\w]{40}" autocorrect="off" autocapitalize="none" required>
                              <div class="input-group-append">
                                <button class="btn btn-primary btn-sm"><?=L( 'Save' )?></button>
                              </div>
                            </div>
                          </div>
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="api.cloudflare.create">
                        </form>
                      <?php } ?>
                    </div>
                    <div class="card-footer text-right">
                      <div class="float-left w-25 text-left">
                        <div class="logo-cloudflare" style="height:2em;"></div>
                      </div>
                      <?php if ( !empty( $acmsCloudflare ) ) { ?>
                        <form action="" method="post">
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="api.cloudflare.remove">
                          <button type="submit" class="btn btn-danger btn-sm"><?=L( 'Remove token' )?></button>
                        </form>
                      <?php } ?>
                    </div>
                  </div>
                </div>

                <?php if ( inExpertMode() ) { ?>
                  <div class="col mb-4">
                    <div class="card h-100 shadow">
                      <div class="card-header text-white bg-info">
                        <i class="fas fa-flask fa-fw"></i> <?=L( 'Import External URLs' )?>
                      </div>
                      <div class="card-body small">
                        <?=L( 'Import external URLs to make them local on the same paths.' )?>
                      </div>
                      <div class="card-footer text-right">
                        <form action="" method="post">
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="import.urls.list">
                          <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                          <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                          <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                          <button type="submit" class="btn btn-sm btn-primary"><?=L( 'Import list' )?></button>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php } ?>

                <?php if ( !inSafeMode() && inExpertMode() ) {
                  $missingExtensions = getMissingExtensions( ['openssl'] );
                  ?>
                  <div class="col mb-4">
                    <div class="card h-100 shadow">
                      <div class="card-header text-white bg-info">
                        <i class="fas fa-flask fa-fw"></i> <?=L( 'API management' )?>
                      </div>
                      <div class="card-body small">
                        <?=L( 'Add a public key for editing website remotely with a private key.' )?>
                        <?php if ( !empty( $missingExtensions ) ) { ?>
                          <div class="p-3 rounded bg-danger text-light mt-3 font-weight-bold"><?=sprintf( L( 'This tool requires the following PHP extensions to be installed: %s.' ), implode( ', ', $missingExtensions ) )?></div>
                        <?php } ?>
                        <?php if ( !empty( $ACMS['ACMS_PUBLIC_KEY'] ) && $publicKey = getPublicKeyInfo( $ACMS['ACMS_PUBLIC_KEY'] ) ) {
                          ?>
                          <div class="text-center mt-3">
                            <button type="button" class="btn btn-sm btn-primary div_api_public_key collapse show" data-toggle="collapse" data-target=".div_api_public_key" aria-expanded="false" aria-controls="div_api_public_key"><?=L( 'Show public key' )?> <?=$publicKey['method'] . ' ' . $publicKey['bits']?></button>
                            <div class="collapse div_api_public_key text-monospace text-left small" style="white-space: pre-wrap;"><?=htmlspecialchars( $publicKey['key'] )?></div>
                          </div>
                        <?php } else { ?>
                          <form id="form_tool_api_key" action="" method="post" class="d-inline needs-validation" novalidate>
                            <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                            <input type="hidden" name="action" value="api.key.set">
                            <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                            <div class="form-group mt-3">
                              <label for="api_management_pub_key"><?=L( 'Public key' )?></label>
                              <textarea name="api_public_key" class="form-control form-control-sm text-monospace" placeholder="<?=L( 'Enter a public key in PEM format here' )?>" required></textarea>
                            </div>
                          </form>
                        <?php } ?>
                      </div>
                      <div class="card-footer text-right">
                        <?php if ( !empty( $ACMS['ACMS_PUBLIC_KEY'] ) ) { ?>
                          <form class="d-inline" action="" method="post">
                            <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                            <input type="hidden" name="action" value="api.key.remove">
                            <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                            <button type="submit" class="btn btn-sm btn-danger"><?=L( 'Remove' )?></button>
                          </form>
                        <?php } else { ?>
                          <button type="submit" class="btn btn-sm btn-primary" form="form_tool_api_key"><?=L( 'Save' )?></button>
                        <?php } ?>
                      </div>
                    </div>
                  </div>
                <?php } ?>

                <?php if ( !inSafeMode() && inExpertMode() ) { ?>
                  <div class="col mb-4">
                    <div class="card h-100 shadow">
                      <div class="card-header text-white bg-info">
                        <i class="fas fa-flask fa-fw"></i> <?=L( 'Update metadata in DB' )?>
                      </div>
                      <div class="card-body small">
                        <?=L( 'In case you had edited files directly on the server, their size may have changed and this meta information needs to be updated in the DB.' )?>
                      </div>
                      <div class="card-footer text-right">
                        <form action="" method="post">
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="update.urls.meta">
                          <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                          <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                          <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                          <button type="submit" class="btn btn-sm btn-primary">
                            <?=L( 'Update' )?></button>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php } ?>

              </div>

            <?php }

            if ( $section == 'import' ) { ?>
              <div>
                <form action="" method="post" id="form_import_uuid" class="needs-validation form-wait" novalidate>
                  <div class="input-group">
                    <input type="text" class="form-control" name="uuid" pattern="[0-9a-zA-Z]{16}|[-0-9a-zA-Z]{19}" placeholder="<?=L( 'Enter serial number' )?>" autocorrect="off" autocapitalize="none" required>
                    <div class="input-group-append">
                      <button type="submit" class="btn btn-primary rounded-right"><?=L( 'Download' )?></button>
                    </div>
                    <div class="invalid-feedback"><?=L( 'The serial number has to be in a format of 16 characters XXXXXXXXXXXXXXXX or XXXX-XXXX-XXXX-XXXX' )?></div>
                  </div>
                  <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                  <input type="hidden" name="action" value="download.serial">
                </form>

                <div class="d-flex align-items-center text-secondary small text-uppercase">
                  <div class="w-100">
                    <hr class="my-0">
                  </div>
                  <div class="mx-2 text-nowrap"><?=L( 'or' )?></div>
                  <div class="w-100">
                    <hr class="my-0">
                  </div>
                </div>

                <form action="" method="post" id="form_import_upload" enctype="multipart/form-data" class="needs-validation form-wait" novalidate>
                  <div class="input-group">
                    <div class="custom-file">
                      <input type="file" class="custom-file-input input-upload-file" name="import_file" accept=".zip,application/zip,application/octet-stream,application/x-zip-compressed,multipart/x-zip" id="input_import_file" aria-describedby="button_import_file" required>
                      <label class="custom-file-label text-truncate" for="input_import_file"><?=L( 'Choose a ZIP file' )?></label>
                    </div>
                    <div class="input-group-append">
                      <button class="btn btn-primary" type="submit" id="button_import_file"><?=L( 'Upload' )?></button>
                    </div>
                  </div>
                  <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                  <input type="hidden" name="action" value="import.upload">
                </form>
              </div>

              <?php
              $imports = getImportsList();
              foreach ( $imports as $import ) { ?>
                <div class="bg-light rounded border shadow-sm p-3 mt-2 mb-1">
                  <div class="row">
                    <div class="col-12 col-md-3 mb-3">
                      <img src="<?=$import['screenshot']?>" class="img-fluid w-100 border rounded" onerror="this.src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAABLCAIAAAA3cxjrAAAABnRSTlMAAAAAAABupgeRAAAAMklEQVR42u3BAQEAAACCIP+vbkhAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEcGcMsAAQVgyaQAAAAASUVORK5CYII=';">
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                      <div class="text-uppercase h5 text-truncate">
                        <a href="<?=$import['url']?>" target="_blank"><i class="fas fa-external-link-alt fa-fw small"></i></a>
                        <?=convertIdnToUtf8( $import['info']['settings']['domain'] )?>
                      </div>
                      <div class="small"><i class="far fa-file-archive fa-fw"></i>
                        <span class="text-monospace">
                      <?php if ( !inSafeMode() && inExpertMode() ) { ?>
                        <a href="<?=basename( $sourcePath ) . "/imports/{$import['filename']}"?>" class="text-info"><?=$import['filename']?></a>
                      <?php } else { ?>
                        <?=$import['filename']?>
                      <?php } ?>
                    </span>
                        (<?=getHumanSize( $import['filesize'] );?>)
                      </div>
                      <div class="small text-muted">
                        <?=sprintf( L( 'Contains: %d files, %s of data' ), $import['info']['filescount'], getHumanSize( $import['info']['filessize'] ) )?>
                      </div>
                    </div>
                    <div class="col-12 col-md-3">
                      <button class="btn btn-block btn-success mb-3" data-toggle="collapse" data-target="#div_form_import_<?=$import['id']?>" aria-expanded="false" aria-controls="div_form_import_<?=$import['id']?>">
                        <i class="fas fa-cog fa-fw"></i> <?=L( 'Import' )?></button>
                      <button class="btn btn-block btn-primary mb-3" data-toggle="collapse" data-target="#div_import_stats_<?=$import['id']?>" aria-expanded="false" aria-controls="div_import_stats_<?=$import['id']?>">
                        <i class="fas fa-chart-pie fa-fw"></i> <?=L( 'Stats' )?>
                      </button>
                      <form action="" method="post" class="d-inline form-wait" id="form_import_<?=$import['id']?>">
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="import.perform.reinstall">
                        <input type="hidden" name="filename" value="<?=$import['filename']?>">
                        <button class="btn btn-block btn-warning btn-action mb-3" type="button" data-toggle="modal" data-target="#confirm-action" data-source="form_import_<?=$import['id']?>" data-message="<?=L( 'Attention! Reinstallation will delete and replace all your website data URLs and their content.' )?>">
                          <i class="fas fa-sync fa-fw"></i> <?=L( 'Reinstallation' )?></button>
                      </form>
                      <form action="" method="post">
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="import.remove">
                        <input type="hidden" name="filename" value="<?=htmlspecialchars( $import['filename'] )?>">
                        <button class="btn btn-block btn-danger" type="submit">
                          <i class="fas fa-trash-alt fa-fw"></i> <?=L( 'Delete' )?></button>
                      </form>

                    </div>
                  </div>

                  <div class="py-3 collapse" id="div_form_import_<?=$import['id']?>">
                    <form action="" method="post" id="form_import_<?=$import['id']?>" class="form-wait">

                      <?php if ( !empty( $import['info']['hostnames'] ) ) { ?>
                        <div class="mb-3">
                          <table class="table table-striped table-sm table-responsive">
                            <thead>
                            <tr>
                              <td class="w-100">
                                <div class="form-check font-weight-bold">
                                  <input class="form-check-input check-all" data-group="check-<?=$import['id']?>" type="checkbox" checked id="check_all_<?=$import['id']?>"><label class="form-check-label" for="check_all_<?=$import['id']?>"><?=L( 'Hostnames' )?></label>
                                </div>
                              </td>
                              <th><?=L( 'Files' )?></th>
                            </tr>
                            </thead>
                            <?php foreach ( $import['info']['hostnames'] as $hostname ) { ?>
                              <tr>
                                <td>
                                  <div class="form-check">
                                    <input class="form-check-input check-<?=$import['id']?>" type="checkbox" name="settings[hostnames][]" value="<?=$hostname['hostname']?>" checked id="check_<?=$hostname['hostname']?>_<?=$import['id']?>"><label class="form-check-label" for="check_<?=$hostname['hostname']?>_<?=$import['id']?>"><?=convertIdnToUtf8( $hostname['hostname'] )?></label>
                                  </div>
                                </td>
                                <td><?=$hostname['count']?></td>
                              </tr>
                            <?php } ?>
                          </table>
                        </div>
                      <?php } ?>

                      <?php if ( $import['acms_settings'] && !inSafeMode() ) { ?>
                        <div class="custom-control custom-checkbox mb-0">
                          <input type="hidden" name="settings[acms_settings]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[acms_settings]" value="1" id="check_acms_settings_<?=$import['id']?>" checked>
                          <label class="custom-control-label" for="check_acms_settings_<?=$import['id']?>"><?=L( 'Import Archivarix CMS settings' )?></label>
                        </div>
                      <?php } ?>

                      <?php if ( $import['loader_settings'] && !inSafeMode() ) { ?>
                        <div class="custom-control custom-checkbox mb-0">
                          <input type="hidden" name="settings[loader_settings]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[loader_settings]" value="1" id="check_loader_settings_<?=$import['id']?>" checked>
                          <label class="custom-control-label" for="check_loader_settings_<?=$import['id']?>"><?=L( 'Import Archivarix Loader settings' )?></label>
                        </div>
                      <?php } ?>

                      <?php if ( !empty( $import['templates'] ) ) { ?>
                        <div class="custom-control custom-checkbox mb-0">
                          <input type="hidden" name="settings[templates]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[templates]" value="1" id="check_templates_<?=$import['id']?>" checked>
                          <label class="custom-control-label" for="check_templates_<?=$import['id']?>"><?=sprintf( L( 'The import detected templates (%d pieces)' ), count( $import['templates'] ) )?>
                            (<a href="" data-toggle="collapse" data-target="#div_templates_<?=$import['id']?>" aria-expanded="false" aria-controls="div_templates_<?=$import['id']?>"><?=L( 'show' )?></a>)</label>
                          <div class="collapse list-group mt-2" id="div_templates_<?=$import['id']?>">
                            <li class="list-group-item list-group-item-action list-group-item-secondary h6 m-0 rounded-0"><?=L( 'Templates' )?></li>
                            <?php foreach ( $import['templates'] as $importTemplate ) { ?>
                              <li class="list-group-item list-group-item-action py-1 text-nowrap text-truncate text-monospace rounded-0">
                                <i class="far fa-file-code fa-fw"></i> <?=$importTemplate['name']?>
                              </li>
                            <?php } ?>
                          </div>
                        </div>
                      <?php } ?>

                      <?php if ( !empty( $import['custom_includes'] ) && !inSafeMode() ) { ?>
                        <div class="custom-control custom-checkbox mb-0">
                          <input type="hidden" name="settings[custom_includes]" value="0">
                          <input class="custom-control-input" type="checkbox" name="settings[custom_includes]" value="1" id="check_custom_includes_settings_<?=$import['id']?>">
                          <label class="custom-control-label" for="check_custom_includes_settings_<?=$import['id']?>"><?=L( 'Import files from custom \'includes\' directory' )?>
                            (<a href="" data-toggle="collapse" data-target="#div_custom_includes_<?=$import['id']?>" aria-expanded="false" aria-controls="div_custom_includes_<?=$import['id']?>"><?=L( 'show files' )?></a>)</label>
                          <div class="collapse list-group mt-2" id="div_custom_includes_<?=$import['id']?>">
                            <li class="list-group-item list-group-item-action list-group-item-secondary h6 m-0 rounded-0"><?=L( 'Custom Files' )?></li>
                            <?php foreach ( $import['custom_includes'] as $customInclude ) { ?>
                              <li class="list-group-item list-group-item-action px-0 py-1 text-nowrap text-truncate text-monospace rounded-0">
                                <?=str_repeat( '<i class="fas fa-fw"></i>', $customInclude['levels'] )?>
                                <?=( $customInclude['is_dir'] ? '<i class="far fa-fw fa-folder"></i>' : '<i class="fas fa-fw"></i><i class="far fa-fw fa-file-alt"></i>' )?>
                                <?=basename( $customInclude['filename'] )?>
                                <span class="text-muted"><?=( $customInclude['is_dir'] ? '' : '(' . getHumanSize( $customInclude['size'], 0 ) . ')' )?></span>
                              </li>
                            <?php } ?>
                          </div>
                          <div class="small text-danger mt-1">
                            <i class="fas fa-exclamation-triangle fa-fw"></i> <?=L( 'Attention! Any file inside "includes" directory can have executable PHP source code. Do not import files from untrustworthy sources.' )?>
                          </div>
                        </div>
                      <?php } ?>

                      <div class="custom-control custom-radio mt-3">
                        <input class="custom-control-input" type="radio" name="settings[overwrite]" value="newer" id="option_overwrite_newer_<?=$import['id']?>" checked>
                        <label class="custom-control-label" for="option_overwrite_newer_<?=$import['id']?>"><?=L( 'Overwrite existing URLs only if the imported version is newer' )?></label>
                      </div>
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input" type="radio" name="settings[overwrite]" value="all" id="option_overwrite_all_<?=$import['id']?>">
                        <label class="custom-control-label" for="option_overwrite_all_<?=$import['id']?>"><?=L( 'Overwrite all URLs' )?></label>
                      </div>
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input" type="radio" name="settings[overwrite]" value="none" id="option_overwrite_none_<?=$import['id']?>">
                        <label class="custom-control-label" for="option_overwrite_none_<?=$import['id']?>"><?=L( 'Do not overwrite existing URLs' )?></label>
                      </div>

                      <div class="custom-control custom-checkbox mt-3">
                        <input type="hidden" name="settings[submerge]" value="0">
                        <input class="custom-control-input" type="checkbox" name="settings[submerge]" value="1" id="check_submerge_<?=$import['id']?>">
                        <label class="custom-control-label" for="check_submerge_<?=$import['id']?>"><?=L( 'Merge URLs from all subdomains to the main domain' )?></label>
                        <small class="form-text text-muted"><?=L( 'A newer version of the URL has priority' )?></small>
                      </div>

                      <div class="form-group mt-3">
                        <label for="input_subdomain_<?=$import['id']?>"><?=L( 'Import everything to a subdomain' )?></label>
                        <div class="input-group">
                          <input type="text" class="form-control text-right" name="settings[subdomain]" placeholder="<?=L( 'subdomain' )?>" id="input_subdomain_<?=$import['id']?>" autocorrect="off" autocapitalize="none">
                          <div class="input-group-append">
                            <span class="input-group-text">.<?=convertIdnToUtf8( $uuidSettings['domain'] )?></span>
                          </div>
                        </div>
                        <small class="form-text text-muted"><?=L( 'Leave empty for a normal import.' )?></small>
                      </div>

                      <div class="form-group">
                        <label for="input_directory_<?=$import['id']?>"><?=L( 'Import everything to a directory' )?></label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><?=convertIdnToUtf8( $uuidSettings['domain'] )?>/</span>
                          </div>
                          <input type="text" class="form-control text-left" name="settings[directory]" placeholder="<?=L( 'directory' )?>" id="input_directory_<?=$import['id']?>" autocorrect="off" autocapitalize="none">
                        </div>
                        <small class="form-text text-muted"><?=L( 'Leave empty for a normal import.' )?></small>
                      </div>

                      <div class="text-right">
                        <button type="submit" class="btn btn-primary"><?=L( 'Start import' )?></button>
                      </div>
                      <input type="hidden" name="filename" value="<?=$import['filename']?>">
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="import.perform">
                    </form>
                  </div>

                  <div class="py-3 collapse stats-collapse" id="div_import_stats_<?=$import['id']?>" data-id="<?=$import['info']['id']?>">
                    <?php printStats( $import['info'] ) ?>
                  </div>

                </div>
                <?php
              }
              if ( !empty( $fBucket['getImportsList'] ) ) foreach ( $fBucket['getImportsList'] as $brokenImport ) { ?>
                <div class="bg-light rounded border shadow p-3 mt-2 mb-1">
                  <div class="row">
                    <div class="col-12 col-md-3 mb-3">
                      <div class="border border-danger alert-danger rounded w-100 text-center p-3 embed-responsive embed-responsive-16by9 d-flex">
                        <span class="h5 text-monospace align-self-center w-100 text-danger"><i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br><?=htmlspecialchars( basename( $brokenImport ) )?></span>
                      </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                      <?=sprintf( L( 'The file is damaged or incomplete. You can delete it and free up %s of space.' ), getHumanSize( filesize( $brokenImport ) ) )?>
                    </div>
                    <div class="col-12 col-md-3">
                      <?php if ( true && inExpertMode() ) { ?>
                        <form action="" method="post" class="needs-validation" novalidate>
                          <div class="form-group">
                            <label for="import_funmd_hostname"><?=L( 'Domain' )?></label>
                            <input class="form-control" type="text" name="params[hostname]" placeholder="<?=L( 'Domain' )?>" id="import_funmd_hostname" autocorrect="off" autocapitalize="none" required>
                          </div>
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="fun.md">
                          <input type="hidden" name="params[filename]" value="<?=htmlspecialchars( basename( $brokenImport ) )?>">
                          <button class="btn btn-block btn-primary mb-3" type="submit">
                            <i class="fas fa-file-import fa-fw"></i> <?=L( 'Import' )?></button>
                        </form>
                      <?php } ?>
                      <form action="" method="post">
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="import.remove">
                        <input type="hidden" name="filename" value="<?=htmlspecialchars( basename( $brokenImport ) )?>">
                        <button class="btn btn-block btn-danger" type="submit">
                          <i class="fas fa-trash-alt fa-fw"></i> <?=L( 'Delete' )?></button>
                      </form>
                    </div>
                  </div>
                </div>
              <?php } ?>

              <div class="d-flex align-items-center text-secondary small text-uppercase">
                <div class="w-100">
                  <hr class="my-0">
                </div>
                <div class="mx-2 text-nowrap"><?=L( 'or' )?></div>
                <div class="w-100">
                  <hr class="my-0">
                </div>
              </div>

              <div class="bg-light rounded border shadow-sm p-3">
                <form action="" method="post" class="needs-validation form-wait" novalidate>
                  <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                  <input type="hidden" name="action" value="export.perform">
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#exportSettings" aria-expanded="false" aria-controls="exportSettings">
                        <i class="fas fa-caret-down fa-fw"></i></button>
                    </div>
                    <input type="text" class="form-control" name="filename" placeholder="<?=L( 'Enter a .ZIP file name' )?>" value="<?=$uuidSettings['uuid'] . '-' . date( 'YmdHis' ) . '.zip'?>" autocorrect="off" autocapitalize="none" required>
                    <div class="input-group-append">
                      <button class="btn btn-primary" type="submit"><?=L( 'Export' )?></button>
                    </div>
                  </div>
                  <div class="collapse mt-2 bg-light" id="exportSettings">
                    <?php
                    unset( $import );
                    $import['info'] = getInfoFromDatabase( $dsn );
                    if ( !empty( $import['info']['hostnames'] ) ) { ?>
                      <div class="mb-3">
                        <table class="table table-striped table-sm table-responsive">
                          <thead>
                          <tr>
                            <td class="w-100">
                              <div class="form-check font-weight-bold">
                                <input class="form-check-input check-all" data-group="check-export" type="checkbox" checked id="check_all_export"><label class="form-check-label" for="check_all_export"><?=L( 'Hostnames' )?></label>
                              </div>
                            </td>
                            <th><?=L( 'Files' )?></th>
                          </tr>
                          </thead>
                          <?php foreach ( $import['info']['hostnames'] as $hostname ) { ?>
                            <tr>
                              <td>
                                <div class="form-check">
                                  <input class="form-check-input check-export" type="checkbox" name="settings[hostnames][]" value="<?=$hostname['hostname']?>" checked id="check_<?=$hostname['hostname']?>_export"><label class="form-check-label" for="check_<?=$hostname['hostname']?>_export"><?=convertIdnToUtf8( $hostname['hostname'] )?></label>
                                </div>
                              </td>
                              <td><?=$hostname['count']?></td>
                            </tr>
                          <?php } ?>
                        </table>
                      </div>
                    <?php } ?>

                    <div class="custom-control custom-checkbox">
                      <input id="export_check_all" class="custom-control-input check-all-switch" data-target="check-all-export" type="checkbox">
                      <label class="custom-control-label" for="export_check_all"><?=L( 'Check All' )?></label>
                    </div>
                    <hr class="my-1">
                    <div class="custom-control custom-checkbox">
                      <input type="hidden" name="settings[acms_settings]" value="0">
                      <input id="export_settings_cms" class="custom-control-input check-all-export" type="checkbox" name="settings[acms_settings]" value="1">
                      <label class="custom-control-label" for="export_settings_cms"><?=L( 'Include Archivarix CMS settings' )?></label>
                    </div>
                    <div class="custom-control custom-checkbox">
                      <input type="hidden" name="settings[loader_settings]" value="0">
                      <input id="export_settings_loader" class="custom-control-input check-all-export" type="checkbox" name="settings[loader_settings]" value="1">
                      <label class="custom-control-label" for="export_settings_loader"><?=L( 'Include Archivarix Loader settings' )?></label>
                    </div>
                    <div class="custom-control custom-checkbox">
                      <input type="hidden" name="settings[custom_files]" value="0">
                      <input id="export_settings_custom_files" class="custom-control-input check-all-export" type="checkbox" name="settings[custom_files]" value="1">
                      <label class="custom-control-label" for="export_settings_custom_files"><?=L( 'Include Custom files' )?></label>
                    </div>
                    <div class="custom-control custom-checkbox">
                      <input type="hidden" name="settings[templates]" value="0">
                      <input id="export_settings_template" class="custom-control-input check-all-export" type="checkbox" name="settings[templates]" value="1">
                      <label class="custom-control-label" for="export_settings_template"><?=L( 'Include Templates' )?></label>
                    </div>
                  </div>
                </form>
              </div>

            <?php }

            if ( $section == 'search' ) {
              if ( !extension_loaded( 'mbstring' ) ) { ?>
                <div class="rounded bg-danger text-light p-3 mb-3">
                  <i class="fas fa-exclamation-triangle mr-2"></i><?=L( 'PHP extension "mbstring" is missing on your server. It is required for the correct work with different charsets.' )?>
                </div>
              <?php } ?>

              <ul class="nav nav-pills nav-justified mb-2" id="searchTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link text-uppercase <?=$_POST['action'] == 'searchreplace.code' ? 'active' : ''?>" id="searchCodeTab" data-toggle="tab" href="#searchCode" role="tab" aria-controls="searchCode" aria-selected="<?=$_POST['action'] == 'searchreplace.code' ? 'true' : 'false'?>"><?=L( 'Code / Text' )?></a>
                </li>
                <li class="nav-item">
                  <a class="nav-link <?=$_POST['action'] == 'searchreplace.url' ? 'active' : ''?>" id="searchUrlsTab" data-toggle="tab" href="#searchUrls" role="tab" aria-controls="searchUrls" aria-selected="<?=$_POST['action'] == 'searchreplace.url' ? 'true' : 'false'?>"><?=L( 'URLs' )?></a>
                </li>
              </ul>

              <div class="tab-content" id="searchTab">
                <div class="tab-pane fade <?=$_POST['action'] == 'searchreplace.code' ? 'show active' : ''?>" id="searchCode" role="tabpanel" aria-labelledby="searchCodeTab">
                  <div class="mb-3 rounded p-3 bg-dark text-light border">
                    <form action="" method="post" class="needs-validation" novalidate>
                      <div class="form-group">
                        <label><?=L( 'Search in a code/text' )?></label>
                        <textarea class="form-control text-monospace" name="search" required><?=htmlspecialchars( $_POST['search'] )?></textarea>
                      </div>
                      <div class="form-group">
                        <label><?=L( 'Replace with' )?></label>
                        <textarea class="form-control text-monospace" name="replace"><?=htmlspecialchars( $_POST['replace'] )?></textarea>
                      </div>


                      <div class="custom-control custom-checkbox">
                        <input type="hidden" name="regex" value="0"/>
                        <input type="checkbox" class="custom-control-input" id="perlRegex" name="regex" value="1" <?=( !empty( $_POST['regex'] ) ? 'checked' : '' )?>>
                        <label class="custom-control-label" for="perlRegex"><?=L( 'Regular expression' )?></label>
                        <a href="#" class="text-primary small" data-toggle="modal" data-target="#regexModal"><i class="fas fa-info-circle"></i></a>
                      </div>
                      <div class="custom-control custom-checkbox">
                        <input type="hidden" name="case_sensitive" value="0"/>
                        <input type="checkbox" class="custom-control-input" id="textCaseSensitiveSearch" name="case_sensitive" value="1" <?=( !empty( $_POST['case_sensitive'] ) ? 'checked' : '' )?>>
                        <label class="custom-control-label" for="textCaseSensitiveSearch"><?=L( 'Case sensitive' )?></label>
                      </div>
                      <div class="custom-control custom-checkbox">
                        <input type="hidden" name="text_files_search" value="0"/>
                        <input type="checkbox" class="custom-control-input" id="textFilesSearch" name="text_files_search" value="1" <?=( !empty( $_POST['text_files_search'] ) ? 'checked' : '' )?>>
                        <label class="custom-control-label" for="textFilesSearch"><?=L( 'Including text files (js, css, txt, json, xml)' )?></label>
                      </div>
                      <div class="mt-3">
                        <div class="search-filter-wrapper search-code-filter-wrapper">
                          <?php if ( !empty( $_POST['filter']['text'] ) ) {
                            foreach ( $_POST['filter']['param'] as $filterNum => $filterVal ) {
                              if ( !strlen( $_POST['filter']['text'][$filterNum] ) ) continue;
                              $filterCur = [
                                'param'          => $_POST['filter']['param'][$filterNum],
                                'operator'       => $_POST['filter']['operator'][$filterNum],
                                'text'           => $_POST['filter']['text'][$filterNum],
                                'regex'          => $_POST['filter']['regex'][$filterNum],
                                'case_sensitive' => $_POST['filter']['case_sensitive'][$filterNum],
                              ];
                              ?>
                              <div class="search-filter-block rounded-bottom bg-light text-dark p-0 small mb-3 position-relative ">
                                <div class="position-absolute" style="top:-10px; right:-10px;">
                              <span class="fa-stack remove-search-filter bg-transparent cursor-link">
                                <i class="fas fa-circle fa-stack-2x fa-fw text-danger"></i>
                                <i class="fas fa-trash-alt fa-stack-1x fa-fw fa-inverse"></i>
                              </span>
                                </div>
                                <hr class="bg-success p-1 m-0">
                                <div class="p-3">
                                  <div class="form-row">
                                    <div class="col-12 col-sm-6">
                                      <select name="filter[param][]" class="form-control mb-2 search-filter-param">
                                        <option data-tag="textarea" data-type="" data-regex="1" data-operators="contains,contains-not" data-placeholder="" value="code" <?=( $filterCur['param'] == 'code' ? 'selected' : '' )?>><?=L( 'Code / Text' )?></option>
                                        <option data-tag="input" data-type="text" data-regex="1" data-operators="contains,contains-not" data-placeholder="" value="url" <?=( $filterCur['param'] == 'url' ? 'selected' : '' )?>><?=L( 'URL' )?></option>
                                        <option data-tag="input" data-type="text" data-regex="1" data-operators="contains,contains-not" data-placeholder="" value="hostname" <?=( $filterCur['param'] == 'hostname' ? 'selected' : '' )?>><?=L( 'Hostname' )?></option>
                                        <option data-tag="input" data-type="text" data-regex="1" data-operators="contains,contains-not" data-placeholder="" value="mime" <?=( $filterCur['param'] == 'mime' ? 'selected' : '' )?>><?=L( 'MIME-type' )?></option>
                                        <option data-tag="input" data-type="text" data-regex="1" data-operators="contains,contains-not" data-placeholder="" value="charset" <?=( $filterCur['param'] == 'charset' ? 'selected' : '' )?>><?=L( 'Charset' )?></option>
                                        <option data-tag="input" data-type="text" data-regex="1" data-operators="contains,contains-not" data-placeholder="" value="redirect" <?=( $filterCur['param'] == 'redirect' ? 'selected' : '' )?>><?=L( 'Redirect' )?></option>
                                        <option data-tag="input" data-type="number" data-regex="0" data-operators="from,to" data-placeholder="<?=L( 'YYYYMMDDHHMMSS' )?>" value="datetime" <?=( $filterCur['param'] == 'datetime' ? 'selected' : '' )?>><?=L( 'Date/time' )?></option>
                                        <option data-tag="input" data-type="text" data-regex="0" data-operators="from,to" data-placeholder="<?=L( 'size in bytes' )?>" value="filesize" <?=( $filterCur['param'] == 'filesize' ? 'selected' : '' )?>><?=L( 'File size' )?></option>
                                        <option data-tag="input" data-type="number" data-regex="0" data-operators="gt,gte,lt,lte,eq,neq" data-placeholder="" value="depth" <?=( $filterCur['param'] == 'depth' ? 'selected' : '' )?>><?=L( 'Page depth' )?></option>
                                      </select>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                      <select name="filter[operator][]" class="form-control mb-2 search-filter-operator">
                                        <option value="contains" <?=( $filterCur['operator'] == 'contains' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['code', 'url', 'mime', 'hostname', 'charset', 'redirect'] ) ? '' : 'disabled hidden' )?>><?=L( 'Contains' )?></option>
                                        <option value="contains-not" <?=( $filterCur['operator'] == 'contains-not' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['code', 'url', 'mime', 'hostname', 'charset', 'redirect'] ) ? '' : 'disabled hidden' )?>><?=L( 'Does not contain' )?></option>
                                        <option value="from" <?=( $filterCur['operator'] == 'from' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['datetime', 'filesize'] ) ? '' : 'disabled hidden' )?>><?=L( 'From' )?></option>
                                        <option value="to" <?=( $filterCur['operator'] == 'to' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['datetime', 'filesize'] ) ? '' : 'disabled hidden' )?>><?=L( 'To' )?></option>
                                        <option value="gt" <?=( $filterCur['operator'] == 'gt' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['depth'] ) ? '' : 'disabled hidden' )?>><?=L( 'Greater than' )?></option>
                                        <option value="gte" <?=( $filterCur['operator'] == 'gte' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['depth'] ) ? '' : 'disabled hidden' )?>><?=L( 'Greater or equal to' )?></option>
                                        <option value="lt" <?=( $filterCur['operator'] == 'lt' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['depth'] ) ? '' : 'disabled hidden' )?>><?=L( 'Less than' )?></option>
                                        <option value="lte" <?=( $filterCur['operator'] == 'lte' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['depth'] ) ? '' : 'disabled hidden' )?>><?=L( 'Less or equal to' )?></option>
                                        <option value="eq" <?=( $filterCur['operator'] == 'eq' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['depth'] ) ? '' : 'disabled hidden' )?>><?=L( 'Equal to' )?></option>
                                        <option value="neq" <?=( $filterCur['operator'] == 'neq' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['depth'] ) ? '' : 'disabled hidden' )?>><?=L( 'Not equal to' )?></option>
                                      </select>
                                    </div>
                                    <div class="col-12">
                                      <div class="form-group mb-2">
                                        <?php if ( in_array( $filterCur['param'], ['code'] ) ) { ?>
                                          <textarea name="filter[text][]" class="form-control search-filter-text text-monospace" placeholder=""><?=htmlspecialchars( $filterCur['text'] )?></textarea>
                                        <?php } elseif ( in_array( $filterCur['param'], ['url', 'mime', 'hostname', 'charset', 'redirect'] ) ) { ?>
                                          <input type="text" name="filter[text][]" class="form-control search-filter-text text-monospace" placeholder="" value="<?=htmlspecialchars( $filterCur['text'] )?>" autocorrect="off" autocapitalize="none"/>
                                        <?php } elseif ( in_array( $filterCur['param'], ['datetime'] ) ) { ?>
                                          <input type="number" name="filter[text][]" class="form-control search-filter-text text-monospace" placeholder="<?=L( 'YYYYMMDDHHMMSS' )?>" value="<?=htmlspecialchars( $filterCur['text'] )?>"/>
                                        <?php } elseif ( in_array( $filterCur['param'], ['filesize'] ) ) { ?>
                                          <input type="text" name="filter[text][]" class="form-control search-filter-text text-monospace" placeholder="<?=L( 'size in bytes' )?>" value="<?=htmlspecialchars( $filterCur['text'] )?>" autocorrect="off" autocapitalize="none"/>
                                        <?php } elseif ( in_array( $filterCur['param'], ['depth'] ) ) { ?>
                                          <input type="number" name="filter[text][]" class="form-control search-filter-text text-monospace" placeholder="" value="<?=htmlspecialchars( $filterCur['text'] )?>"/>
                                        <?php } ?>
                                      </div>
                                    </div>
                                    <div class="col-12 search-filter-regex-div <?=( in_array( $filterCur['param'], ['datetime', 'filesize', 'depth'] ) ? 'd-none' : '' )?> mb-1">
                                      <div class="form-check" style="line-height:1 !important;">
                                        <label class="form-check-label">
                                          <input type="hidden" class="search-filter-regex" name="filter[regex][]" value="<?=$filterCur['regex']?>"/>
                                          <input type="checkbox" class="form-check-input search-filter-regex-checkbox" style="margin-top: 0 !important;" <?=( $filterCur['regex'] ? 'checked' : '' )?>>
                                          <?=L( 'Regular expression' )?></label>
                                      </div>
                                    </div>
                                    <div class="col-12 search-filter-case-sensitive-div <?=( in_array( $filterCur['param'], ['datetime', 'filesize', 'depth'] ) ? 'd-none' : '' )?> mb-1">
                                      <div class="form-check" style="line-height:1 !important;">
                                        <label class="form-check-label">
                                          <input type="hidden" class="search-filter-case-sensitive" name="filter[case_sensitive][]" value="<?=$filterCur['case_sensitive']?>"/>
                                          <input type="checkbox" class="form-check-input search-filter-case-sensitive-checkbox" style="margin-top: 0 !important;" <?=( $filterCur['case_sensitive'] ? 'checked' : '' )?>>
                                          <?=L( 'Case sensitive' )?></label>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            <?php }
                          } ?>
                        </div>

                        <button type="button" class="btn btn-success btn-sm mb-3 mr-2 search-code-add-filter">
                          <i class="fas fa-plus-square fa-fw"></i> <?=L( 'Filter rule' )?></button>

                      </div>

                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="searchreplace.code"/>
                      <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                      <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>

                      <div class="text-center">
                        <button type="submit" class="btn btn-primary m-1" name="type" value="search"><?=L( 'Search only' )?></button>
                        <button type="submit" class="btn btn-warning m-1" name="type" value="replace"><?=L( 'Search & Replace' )?></button>
                        <?php if ( inExpertMode() ) { ?>
                          <button type="submit" class="btn btn-info m-1" name="type" value="replace_now"><?=L( 'Replace immediately' )?></button>
                          <button type="submit" class="btn btn-info m-1" name="type" value="list"><?=L( 'List replacements' )?></button>
                        <?php } ?>
                      </div>
                    </form>
                  </div>
                </div>

                <div class="tab-pane fade <?=$_POST['action'] == 'searchreplace.url' ? 'show active' : ''?>" id="searchUrls" role="tabpanel" aria-labelledby="searchUrlsTab">
                  <div class="mb-3 rounded p-3 bg-search-url text-dark border">
                    <form action="" method="post" class="needs-validation" novalidate>
                      <div class="form-group">
                        <label class="d-flex"><?=L( 'Search in URL' )?>
                          <div class="ml-auto text-primary cursor-link search-url-all"><?=L( 'Search in all' )?></div>
                        </label>
                        <textarea class="form-control text-monospace search-url-search" name="search" required><?=htmlspecialchars( $_POST['search'] )?></textarea>
                      </div>
                      <div class="form-group">
                        <label><?=L( 'Replace with' )?></label>
                        <textarea class="form-control text-monospace search-url-replace" name="replace"><?=htmlspecialchars( $_POST['replace'] )?></textarea>
                      </div>

                      <div class="custom-control custom-checkbox">
                        <input type="hidden" name="regex" value="0"/>
                        <input type="checkbox" class="custom-control-input" id="perlRegexUrl" name="regex" value="1" <?=( !empty( $_POST['regex'] ) ? 'checked' : '' )?>>
                        <label class="custom-control-label" for="perlRegexUrl"><?=L( 'Regular expression' )?></label>
                        <a href="#" class="text-primary small" data-toggle="modal" data-target="#regexModal"><i class="fas fa-info-circle"></i></a>
                      </div>
                      <div class="custom-control custom-checkbox">
                        <input type="hidden" name="case_sensitive" value="0"/>
                        <input type="checkbox" class="custom-control-input" id="textCaseSensitiveSearch" name="case_sensitive" value="1" <?=( !empty( $_POST['case_sensitive'] ) ? 'checked' : '' )?>>
                        <label class="custom-control-label" for="textCaseSensitiveSearch"><?=L( 'Case sensitive' )?></label>
                      </div>
                      <div class="custom-control custom-checkbox">
                        <input type="hidden" name="text_files_search" value="0"/>
                        <input type="checkbox" class="custom-control-input" id="replaceUrl" name="replaceUrl" value="1" <?=( !empty( $_POST['replaceUrl'] ) ? 'checked' : '' )?>>
                        <label class="custom-control-label" for="replaceUrl"><?=L( 'Replace if the same URL already exists and the replacing version is newer' )?></label>
                      </div>
                      <div class="mt-3">
                        <div class="search-filter-wrapper search-url-filter-wrapper">
                          <?php if ( !empty( $_POST['filter']['text'] ) ) {
                            foreach ( $_POST['filter']['param'] as $filterNum => $filterVal ) {
                              if ( !strlen( $_POST['filter']['text'][$filterNum] ) ) continue;
                              $filterCur = [
                                'param'          => $_POST['filter']['param'][$filterNum],
                                'operator'       => $_POST['filter']['operator'][$filterNum],
                                'text'           => $_POST['filter']['text'][$filterNum],
                                'regex'          => $_POST['filter']['regex'][$filterNum],
                                'case_sensitive' => $_POST['filter']['case_sensitive'][$filterNum],
                              ];
                              ?>
                              <div class="search-filter-block rounded-bottom bg-light text-dark p-0 small mb-3 position-relative ">
                                <div class="position-absolute" style="top:-10px; right:-10px;">
                              <span class="fa-stack remove-search-filter bg-transparent cursor-link">
                                <i class="fas fa-circle fa-stack-2x fa-fw text-danger"></i>
                                <i class="fas fa-trash-alt fa-stack-1x fa-fw fa-inverse"></i>
                              </span>
                                </div>
                                <hr class="bg-success p-1 m-0">
                                <div class="p-3">
                                  <div class="form-row">
                                    <div class="col-12 col-sm-6">
                                      <select name="filter[param][]" class="form-control mb-2 search-filter-param">
                                        <option data-tag="textarea" data-type="" data-regex="1" data-operators="contains,contains-not" data-placeholder="" value="code" <?=( $filterCur['param'] == 'code' ? 'selected' : '' )?>><?=L( 'Code / Text' )?></option>
                                        <option data-tag="input" data-type="text" data-regex="1" data-operators="contains,contains-not" data-placeholder="" value="url" <?=( $filterCur['param'] == 'url' ? 'selected' : '' )?>><?=L( 'URL' )?></option>
                                        <option data-tag="input" data-type="text" data-regex="1" data-operators="contains,contains-not" data-placeholder="" value="hostname" <?=( $filterCur['param'] == 'hostname' ? 'selected' : '' )?>><?=L( 'Hostname' )?></option>
                                        <option data-tag="input" data-type="text" data-regex="1" data-operators="contains,contains-not" data-placeholder="" value="mime" <?=( $filterCur['param'] == 'mime' ? 'selected' : '' )?>><?=L( 'MIME-type' )?></option>
                                        <option data-tag="input" data-type="text" data-regex="1" data-operators="contains,contains-not" data-placeholder="" value="charset" <?=( $filterCur['param'] == 'charset' ? 'selected' : '' )?>><?=L( 'Charset' )?></option>
                                        <option data-tag="input" data-type="text" data-regex="1" data-operators="contains,contains-not" data-placeholder="" value="redirect" <?=( $filterCur['param'] == 'redirect' ? 'selected' : '' )?>><?=L( 'Redirect' )?></option>
                                        <option data-tag="input" data-type="number" data-regex="0" data-operators="from,to" data-placeholder="<?=L( 'YYYYMMDDHHMMSS' )?>" value="datetime" <?=( $filterCur['param'] == 'datetime' ? 'selected' : '' )?>><?=L( 'Date/time' )?></option>
                                        <option data-tag="input" data-type="text" data-regex="0" data-operators="from,to" data-placeholder="<?=L( 'size in bytes' )?>" value="filesize" <?=( $filterCur['param'] == 'filesize' ? 'selected' : '' )?>><?=L( 'File size' )?></option>
                                        <option data-tag="input" data-type="number" data-regex="0" data-operators="gt,gte,lt,lte,eq,neq" data-placeholder="" value="depth" <?=( $filterCur['param'] == 'depth' ? 'selected' : '' )?>><?=L( 'Page depth' )?></option>
                                      </select>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                      <select name="filter[operator][]" class="form-control mb-2 search-filter-operator">
                                        <option value="contains" <?=( $filterCur['operator'] == 'contains' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['code', 'url', 'mime', 'hostname', 'charset', 'redirect'] ) ? '' : 'disabled hidden' )?>><?=L( 'Contains' )?></option>
                                        <option value="contains-not" <?=( $filterCur['operator'] == 'contains-not' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['code', 'url', 'mime', 'hostname', 'charset', 'redirect'] ) ? '' : 'disabled hidden' )?>><?=L( 'Does not contain' )?></option>
                                        <option value="from" <?=( $filterCur['operator'] == 'from' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['datetime', 'filesize'] ) ? '' : 'disabled hidden' )?>><?=L( 'From' )?></option>
                                        <option value="to" <?=( $filterCur['operator'] == 'to' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['datetime', 'filesize'] ) ? '' : 'disabled hidden' )?>><?=L( 'To' )?></option>
                                        <option value="gt" <?=( $filterCur['operator'] == 'gt' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['depth'] ) ? '' : 'disabled hidden' )?>><?=L( 'Greater than' )?></option>
                                        <option value="gte" <?=( $filterCur['operator'] == 'gte' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['depth'] ) ? '' : 'disabled hidden' )?>><?=L( 'Greater or equal to' )?></option>
                                        <option value="lt" <?=( $filterCur['operator'] == 'lt' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['depth'] ) ? '' : 'disabled hidden' )?>><?=L( 'Less than' )?></option>
                                        <option value="lte" <?=( $filterCur['operator'] == 'lte' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['depth'] ) ? '' : 'disabled hidden' )?>><?=L( 'Less or equal to' )?></option>
                                        <option value="eq" <?=( $filterCur['operator'] == 'eq' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['depth'] ) ? '' : 'disabled hidden' )?>><?=L( 'Equal to' )?></option>
                                        <option value="neq" <?=( $filterCur['operator'] == 'neq' ? 'selected' : '' )?> <?=( in_array( $filterCur['param'], ['depth'] ) ? '' : 'disabled hidden' )?>><?=L( 'Not equal to' )?></option>
                                      </select>
                                    </div>
                                    <div class="col-12">
                                      <div class="form-group mb-2">
                                        <?php if ( in_array( $filterCur['param'], ['code'] ) ) { ?>
                                          <textarea name="filter[text][]" class="form-control search-filter-text text-monospace" placeholder=""><?=htmlspecialchars( $filterCur['text'] )?></textarea>
                                        <?php } elseif ( in_array( $filterCur['param'], ['url', 'mime', 'hostname', 'charset', 'redirect'] ) ) { ?>
                                          <input type="text" name="filter[text][]" class="form-control search-filter-text text-monospace" placeholder="" value="<?=htmlspecialchars( $filterCur['text'] )?>" autocorrect="off" autocapitalize="none"/>
                                        <?php } elseif ( in_array( $filterCur['param'], ['datetime'] ) ) { ?>
                                          <input type="number" name="filter[text][]" class="form-control search-filter-text text-monospace" placeholder="<?=L( 'YYYYMMDDHHMMSS' )?>" value="<?=htmlspecialchars( $filterCur['text'] )?>"/>
                                        <?php } elseif ( in_array( $filterCur['param'], ['filesize'] ) ) { ?>
                                          <input type="text" name="filter[text][]" class="form-control search-filter-text text-monospace" placeholder="<?=L( 'size in bytes' )?>" value="<?=htmlspecialchars( $filterCur['text'] )?>" autocorrect="off" autocapitalize="none"/>
                                        <?php } elseif ( in_array( $filterCur['param'], ['depth'] ) ) { ?>
                                          <input type="number" name="filter[text][]" class="form-control search-filter-text text-monospace" placeholder="" value="<?=htmlspecialchars( $filterCur['text'] )?>"/>
                                        <?php } ?>
                                      </div>
                                    </div>
                                    <div class="col-12 search-filter-regex-div <?=( in_array( $filterCur['param'], ['datetime', 'filesize', 'depth'] ) ? 'd-none' : '' )?> mb-1">
                                      <div class="form-check" style="line-height:1 !important;">
                                        <label class="form-check-label">
                                          <input type="hidden" class="search-filter-regex" name="filter[regex][]" value="<?=$filterCur['regex']?>"/>
                                          <input type="checkbox" class="form-check-input search-filter-regex-checkbox" style="margin-top: 0 !important;" <?=( $filterCur['regex'] ? 'checked' : '' )?>>
                                          <?=L( 'Regular expression' )?></label>
                                      </div>
                                    </div>
                                    <div class="col-12 search-filter-case-sensitive-div <?=( in_array( $filterCur['param'], ['datetime', 'filesize', 'depth'] ) ? 'd-none' : '' )?>">
                                      <div class="form-check" style="line-height:1 !important;">
                                        <label class="form-check-label">
                                          <input type="hidden" class="search-filter-case-sensitive" name="filter[case_sensitive][]" value="<?=$filterCur['case_sensitive']?>"/>
                                          <input type="checkbox" class="form-check-input search-filter-case-sensitive-checkbox" style="margin-top: 0 !important;" <?=( $filterCur['case_sensitive'] ? 'checked' : '' )?>>
                                          <?=L( 'Case sensitive' )?></label>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            <?php }
                          } ?>
                        </div>

                        <button type="button" class="btn btn-success btn-sm mb-3 mr-2 search-url-add-filter">
                          <i class="fas fa-plus-square fa-fw"></i> <?=L( 'Filter rule' )?></button>

                        <button type="button" class="btn btn-primary btn-sm mb-3" data-toggle="collapse" data-target="#searchUrlEditMetadata" aria-expanded="false" aria-controls="searchUrlEditMetadata">
                          <i class="fas fa-edit fa-fw"></i> <?=L( 'Edit metadata on Replace' )?></button>

                        <div class="collapse <?=( ( isset( $_POST['metadata'] ) && array_filter( $_POST['metadata'] ) ) ? 'show' : '' )?> mb-3 text-dark" id="searchUrlEditMetadata">
                          <hr class="<?=( ( isset( $_POST['metadata'] ) && array_filter( $_POST['metadata'] ) ) ? 'bg-primary' : 'bg-secondary' )?> p-1 m-0">
                          <div class="bg-light rounded-bottom px-3 pt-3">
                            <div class="alert alert-secondary"><?=L( 'Enter values into fields to update metadata on Replace action. Leave empty to keep intact.' )?></div>
                            <div class="row">
                              <div class="col-12 col-md-6">
                                <div class="form-group">
                                  <label for="searchUrlMetaMime"><?=L( 'MIME-type' )?></label>
                                  <input type="text" name="metadata[mimetype]" class="form-control text-monospace" pattern="[-a-z0-9+./]*" value="<?=( empty( $_POST['metadata']['mimetype'] ) ? '' : htmlspecialchars( $_POST['metadata']['mimetype'] ) )?>" autocorrect="off" autocapitalize="none">
                                </div>
                              </div>
                              <div class="col-12 col-md-6">
                                <div class="form-group">
                                  <label for="searchUrlMetaCharset"><?=L( 'Charset' )?></label>
                                  <input type="text" name="metadata[charset]" class="form-control text-monospace" pattern="[-a-z0-9_]*" value="<?=( empty( $_POST['metadata']['charset'] ) ? '' : htmlspecialchars( $_POST['metadata']['charset'] ) )?>" autocorrect="off" autocapitalize="none">
                                </div>
                              </div>
                              <div class="col-12 col-md-6">
                                <div class="form-group">
                                  <label for="searchUrlMetaRedirect"><?=L( 'Redirect' )?></label>
                                  <input type="text" name="metadata[redirect]" class="form-control text-monospace" value="<?=( empty( $_POST['metadata']['redirect'] ) ? '' : htmlspecialchars( $_POST['metadata']['redirect'] ) )?>" autocorrect="off" autocapitalize="none">
                                </div>
                              </div>
                              <div class="col-12 col-md-6">
                                <div class="form-group">
                                  <label for="searchUrlMetaFiletime"><?=L( 'File time' )?></label>
                                  <input type="text" name="metadata[filetime]" class="form-control text-monospace" pattern="[\d]*" value="<?=( empty( $_POST['metadata']['filetime'] ) ? '' : htmlspecialchars( $_POST['metadata']['filetime'] ) )?>" autocorrect="off" autocapitalize="none">
                                </div>
                              </div>
                              <div class="col-12 col-md-6">
                                <div class="form-group">
                                  <label for="searchUrlMetaHostname"><?=L( 'Hostname' )?></label>
                                  <input type="text" name="metadata[hostname]" class="form-control text-monospace" value="<?=( empty( $_POST['metadata']['hostname'] ) ? '' : htmlspecialchars( $_POST['metadata']['hostname'] ) )?>" autocorrect="off" autocapitalize="none">
                                </div>
                              </div>
                            </div>
                          </div>
                        </div

                      </div>
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="searchreplace.url"/>
                      <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                      <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>

                      <div class="text-center">
                        <button type="submit" class="btn btn-primary m-1" name="type" value="search"><?=L( 'Search only' )?></button>
                        <button type="submit" class="btn btn-warning m-1" name="type" value="replace"><?=L( 'Search & Replace' )?></button>
                      </div>
                    </form>
                  </div>
                </div>


                <div class="search-filter-block d-none rounded-bottom bg-light text-dark p-0 small mb-3 position-relative ">
                  <div class="position-absolute" style="top:-10px; right:-10px;">
                  <span class="fa-stack remove-search-filter bg-transparent cursor-link">
                    <i class="fas fa-circle fa-stack-2x fa-fw text-danger"></i>
                    <i class="fas fa-trash-alt fa-stack-1x fa-fw fa-inverse"></i>
                  </span>
                  </div>
                  <hr class="bg-secondary p-1 m-0">
                  <div class="p-3">
                    <div class="form-row">
                      <div class="col-12 col-sm-6">
                        <select name="filter[param][]" class="form-control mb-2 search-filter-param">
                          <option data-tag="textarea" data-type="" data-regex="1" data-case-sensitive="1" data-operators="contains,contains-not" data-placeholder="" value="code"><?=L( 'Code / Text' )?></option>
                          <option data-tag="input" data-type="text" data-regex="1" data-case-sensitive="1" data-operators="contains,contains-not" data-placeholder="" value="url"><?=L( 'URL' )?></option>
                          <option data-tag="input" data-type="text" data-regex="1" data-case-sensitive="1" data-operators="contains,contains-not" data-placeholder="" value="hostname"><?=L( 'Hostname' )?></option>
                          <option data-tag="input" data-type="text" data-regex="1" data-case-sensitive="1" data-operators="contains,contains-not" data-placeholder="" value="mime"><?=L( 'MIME-type' )?></option>
                          <option data-tag="input" data-type="text" data-regex="1" data-case-sensitive="1" data-operators="contains,contains-not" data-placeholder="" value="charset"><?=L( 'Charset' )?></option>
                          <option data-tag="input" data-type="text" data-regex="1" data-case-sensitive="1" data-operators="contains,contains-not" data-placeholder="" value="redirect"><?=L( 'Redirect' )?></option>
                          <option data-tag="input" data-type="number" data-regex="0" data-case-sensitive="0" data-operators="from,to" data-placeholder="<?=L( 'YYYYMMDDHHMMSS' )?>" value="datetime"><?=L( 'Date/time' )?></option>
                          <option data-tag="input" data-type="text" data-regex="0" data-case-sensitive="0" data-operators="from,to" data-placeholder="<?=L( 'size in bytes' )?>" value="filesize"><?=L( 'File size' )?></option>
                          <option data-tag="input" data-type="number" data-regex="0" data-case-sensitive="0" data-operators="gt,gte,lt,lte,eq,neq" data-placeholder="" value="depth"><?=L( 'Page depth' )?></option>
                        </select>
                      </div>
                      <div class="col-12 col-sm-6">
                        <select name="filter[operator][]" class="form-control mb-2 search-filter-operator">
                          <option value="contains"><?=L( 'Contains' )?></option>
                          <option value="contains-not"><?=L( 'Does not contain' )?></option>
                          <option value="from" disabled hidden><?=L( 'From' )?></option>
                          <option value="to" disabled hidden><?=L( 'To' )?></option>
                          <option value="gt" disabled hidden><?=L( 'Greater than' )?></option>
                          <option value="gte" disabled hidden><?=L( 'Greater or equal to' )?></option>
                          <option value="lt" disabled hidden><?=L( 'Less than' )?></option>
                          <option value="lte" disabled hidden><?=L( 'Less or equal to' )?></option>
                          <option value="eq" disabled hidden><?=L( 'Equal to' )?></option>
                          <option value="neq" disabled hidden><?=L( ' Not equal to' )?></option>
                        </select>
                      </div>
                      <div class="col-12">
                        <div class="form-group mb-2">
                          <textarea name="filter[text][]" class="form-control search-filter-text text-monospace" placeholder=""></textarea>
                        </div>
                      </div>
                      <div class="col-12 search-filter-regex-div mb-1">
                        <div class="form-check" style="line-height:1 !important;">
                          <label class="form-check-label">
                            <input type="hidden" class="search-filter-regex" name="filter[regex][]" value="0"/>
                            <input type="checkbox" class="form-check-input search-filter-regex-checkbox" style="margin-top: 0 !important;">
                            <?=L( 'Regular expression' )?></label>
                        </div>
                      </div>
                      <div class="col-12 search-filter-case-sensitive-div">
                        <div class="form-check" style="line-height:1 !important;">
                          <label class="form-check-label">
                            <input type="hidden" class="search-filter-case-sensitive" name="filter[case_sensitive][]" value="0"/>
                            <input type="checkbox" class="form-check-input search-filter-case-sensitive-checkbox" style="margin-top: 0 !important;">
                            <?=L( 'Case sensitive' )?></label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <?php if ( count( $searchResults ) && $_POST['type'] == 'search' && !isset( $_POST['perform'] ) ) { ?>
                <div class="text-center mb-3">
                  <form action="" method="post" id="form_confirm_remove" class="form-wait">
                    <input type="hidden" name="search" value="<?=htmlspecialchars( $_POST['search'] )?>"/>
                    <input type="hidden" name="replace" value="<?=htmlspecialchars( $_POST['replace'] )?>"/>
                    <input type="hidden" name="regex" value="<?=$_POST['regex']?>"/>
                    <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                    <input type="hidden" name="action" value="<?=$_POST['action']?>"/>
                    <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                    <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                    <input type="hidden" name="type" value="<?=$_POST['type']?>"/>
                    <input type="hidden" name="text_files_search" value="<?=$_POST['text_files_search']?>"/>
                    <input type="hidden" name="perform" value="remove"/>
                    <?php if ( !empty( $_POST['filter'] ) ) printFormFields( ['filter' => $_POST['filter']] ); ?>
                    <?php if ( !empty( $_POST['replaceUrl'] ) ) { ?>
                      <input type="hidden" name="replaceUrl" value="<?=$_POST['replaceUrl']?>"/>
                    <?php } ?>
                    <button type="button" class="btn btn-danger btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_confirm_remove">
                      <i class="fas fa-trash-alt fa-fw"></i> <?=L( 'Remove all found URLs' )?>
                    </button>
                  </form>
                </div>
              <?php } ?>

              <?php if ( count( $searchResults ) && $_POST['type'] == 'replace' && !isset( $_POST['perform'] ) ) { ?>
                <div class="text-center mb-3">
                  <form action="" method="post" id="form_confirm_replace" class="form-wait">
                    <input type="hidden" name="search" value="<?=htmlspecialchars( $_POST['search'] )?>"/>
                    <input type="hidden" name="replace" value="<?=htmlspecialchars( $_POST['replace'] )?>"/>
                    <input type="hidden" name="regex" value="<?=$_POST['regex']?>"/>
                    <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                    <input type="hidden" name="action" value="<?=$_POST['action']?>"/>
                    <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                    <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                    <input type="hidden" name="type" value="<?=$_POST['type']?>"/>
                    <input type="hidden" name="text_files_search" value="<?=$_POST['text_files_search']?>"/>
                    <input type="hidden" name="perform" value="replace"/>
                    <?php if ( !empty( $_POST['filter'] ) ) printFormFields( ['filter' => $_POST['filter']] ); ?>
                    <?php if ( !empty( $_POST['metadata'] ) ) printFormFields( ['metadata' => $_POST['metadata']] ); ?>
                    <?php if ( !empty( $_POST['replaceUrl'] ) ) { ?>
                      <input type="hidden" name="replaceUrl" value="<?=$_POST['replaceUrl']?>"/>
                    <?php } ?>
                    <button type="button" class="btn btn-success btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_confirm_replace">
                      <i class="fas fa-check fa-fw"></i> <?=L( 'Confirm all replacements' )?>
                    </button>
                  </form>
                </div>
              <?php } ?>
              <?php
              if ( !isset( $_POST['perform'] ) && count( $searchResults ) > 2 ) {
                $searchCount  = $searchResults['total_urls'];
                $totalMatches = $searchResults['total_matches'];
                $limitReached = $searchResults['limit_reached'];
                unset( $searchResults['total_urls'] );
                unset( $searchResults['total_matches'] );
                unset( $searchResults['limit_reached'] );
                ?>

                <div class="text-right small pb-3">
                  <em>
                    <?=sprintf( L( 'Pages found: %d; total matches: %d' ), $searchCount, $totalMatches )?>
                  </em>
                </div>
                <?php if ( $limitReached ) { ?>
                  <div class="bg-warning p-3 rounded border mb-3">
                    <?=sprintf( L( 'Attention! Only %d matches are shown from %d due to matches display limit' ), $ACMS['ACMS_MATCHES_LIMIT'], $totalMatches )?>
                  </div>
                <?php } ?>
              <?php } ?>

              <?php if ( !isset( $_POST['perform'] ) && in_array( $_POST['type'], ['search', 'replace'] ) ) foreach ( $searchResults as $searchResult ) {
                if ( empty( $searchResult['results'] ) ) {
                  continue;
                } ?>
                <div class="bg-light border rounded p-3 mb-2 search-result">
                  <div class="row">
                    <div class="col order-sm-1 order-2">
                      <div class="h5 text-monospace text-break">
                        <?=htmlspecialchars( rawurldecode( $searchResult['request_uri'] ), ENT_IGNORE )?>
                        <?=( !empty( $searchResult['replace_uri'] ) ? ' -> ' . htmlspecialchars( rawurldecode( $searchResult['replace_uri'] ), ENT_IGNORE ) : '' )?>
                        <?php if ( isset( $searchResult['valid_uri'] ) && !$searchResult['valid_uri'] ) { ?>
                          <div class="small text-danger"><?=L( 'Replace is not possible. Invalid new URL.' )?></div>
                        <?php } ?>
                        <div class="small"><?=convertIdnToUtf8( $searchResult['hostname'] )?></div>
                      </div>
                    </div>

                    <div class="col-sm-auto col-12 text-center text-sm-right text-nowrap order-1 order-sm-2 mb-3 mb-sm-0">
                      <?php if ( $_POST['type'] == 'search' ) { ?>
                        <form action="" method="post" class="form-inline d-inline" id="url_remove_<?=$searchResult['rowid']?>" onsubmit="ajaxRemoveURL('url_remove_<?=$searchResult['rowid']?>'); return false;">
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="remove.url"/>
                          <input type="hidden" name="urlID" value="<?=$searchResult['rowid']?>"/>
                          <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                          <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                          <input type="hidden" name="ajax" value="1"/>
                          <button type="submit" class="btn btn-sm btn-danger" title="<?=L( 'Remove URL' )?>">
                            <i class="fas fa-trash-alt fa-fw"></i></button>
                        </form>
                      <?php } ?>
                      <form target="_blank" action="" method="post" class="form-inline d-inline">
                        <input type="hidden" name="show" value="edit.url"/>
                        <input type="hidden" name="urlID" value="<?=$searchResult['rowid']?>"/>
                        <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                        <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                        <button class="btn btn-sm btn-primary" title="<?=L( 'Edit page in an external window' )?>">
                          <i class="fas fa-edit fa-fw"></i></button>
                      </form>
                      <a href="<?='//' . convertDomain( $searchResult['hostname'] ) . $searchResult['request_uri']?>" target="_blank" class="btn btn-sm btn-primary" title="<?=L( 'Open URL in a new window' )?>"><i class="fas fa-external-link-alt fa-fw"></i></a>
                      <a class="btn btn-sm btn-primary" target="_blank" rel="noreferrer noopener" href="https://web.archive.org/web/<?=$searchResult['filetime']?>/http://<?=$searchResult['hostname']?><?=$searchResult['request_uri']?>" title="<?=L( 'Open URL in Web Archive' )?>">
                        <i class="fas fa-landmark fa-fw"></i>
                      </a>
                      <div class="small text-monospace text-center">
                        <span class="small badge badge-dark"><?=convertFiletimeToHuman( $searchResult['filetime'] )?></span>
                      </div>
                    </div>
                  </div>
                  <?php $n = 0;
                  foreach ( $searchResult['results'] as $searchCode ) {
                    $n++;
                    if ( $searchResult['type'] == 'search' ) { ?>
                      <div class="mt-2 mb-0">
                        <span class="ml-2 px-2 py-0 text-light bg-success rounded-top text-monospace"><?=$n?></span>
                      </div>
                      <div class="bg-success text-light rounded p-2">
                        <div class="bg-success text-light rounded p-2">
                          <small>
                            <small class="float-right"><abbr title="<?=L( 'Position' )?>">@<?=$searchCode['position']?></abbr>
                            </small>
                          </small>
                          <pre class="m-0"><code class="text-white"><?=htmlspecialchars( ( $searchCode['result'] ) )?></code></pre>
                        </div>
                      </div>
                      <?php
                    }
                    if ( $searchResult['type'] == 'replace' ) {
                      ?>
                      <div class="mt-2 mb-0">
                        <span class="ml-2 px-2 py-0 text-light bg-warning rounded-top text-monospace"><?=$n?></span>
                      </div>
                      <div class="bg-success text-light rounded-top p-2">
                        <small>
                          <small class="float-right"><abbr title="<?=L( 'Position' )?>">@<?=$searchCode['position']?></abbr>
                          </small>
                        </small>
                        <pre class="m-0"><code class="text-white"><?=htmlspecialchars( ( $searchCode['original'] ) )?></code></pre>
                      </div>
                      <div class="bg-warning rounded-bottom mt-0 p-2">
                        <pre class="m-0"><code><?=htmlspecialchars( ( $searchCode['result'] ) )?></code></pre>
                      </div>

                      <?php
                    }
                  } ?>
                </div>
                <?php
              }
              if ( $_POST['type'] == 'list' && count( $searchResults ) ) { ?>
                <div class="text-center mb-3">
                  <form action="" method="post" id="form_send_import_urls">
                    <input type="hidden" name="externalUrlsList" value="<?=htmlspecialchars( implode( "\n", $searchResults['strings'] ) )?>">
                    <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                    <input type="hidden" name="action" value="import.urls.list"/>
                    <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                    <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                    <button type="submit" class="btn btn-success">
                      <i class="fas fa-file-export fa-fw"></i> <?=L( 'Send all to Import URLs Tool' )?>
                    </button>
                  </form>
                </div>

                <table data-toggle="table" data-search="true" data-show-header="false" data-show-export="true" data-pagination="false" data-maintain-meta-data="true" data-locale="<?=$cmsLocales[getLang()]['code']?>" data-sort-class="bg-light" data-trim-on-search="false" class="table table-bordered table-sm">
                  <thead class="thead-light">
                  <th data-field="string" data-sortable="true" data-width="100" data-width-unit="%"><?=L( 'Results' )?></th>
                  </tr>
                  </thead>
                  <tbody>
                  <pre>
                  <?php foreach ( $searchResults['strings'] as $replaceString ) { ?>
                    <tr>
                        <td class="small"><?=htmlspecialchars( rawurldecode( $replaceString ), ENT_IGNORE )?></td>
                    </tr>
                  <?php } ?>
                  </tbody>
                </table>
              <?php }
            }

            if ( $section == 'missing.urls' ) { ?>
              <div class="border p-0 mb-3 rounded">
                <div class="row m-3">
                  <div class="col-12 col-sm-auto p-1">
                    <form class="d-inline" id="form_missing_urls_clear" action="" method="post">
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="missing.clear">
                      <input type="hidden" name="all" value="1">
                      <button type="submit" class="btn btn-danger m-1 btn-block">
                        <i class="fas fa-trash-alt fa-fw"></i> <?=L( 'Clear all' )?></button>
                    </form>
                  </div>
                  <div class="col-12 col-sm-auto p-1">
                    <form class="d-inline" id="form_missing_urls_refresh" action="" method="post">
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="missing.show">
                      <button type="submit" class="btn btn-primary m-1 btn-block">
                        <i class="fas fa-sync fa-fw"></i> <?=L( 'Update' )?></button>
                    </form>
                  </div>
                </div>
              </div>
              <?php if ( !empty( $missingUrls ) ) { ?>
                <table data-toggle="table" data-pagination="true" data-search="true" data-show-columns="true" data-show-export="true" data-page-size="100" data-page-list="[10, 25, 50, 100, 200, All]" data-maintain-meta-data="true" data-locale="<?=$cmsLocales[getLang()]['code']?>" data-sort-class="bg-light" data-sort-name="status" data-sort-order="desc" data-trim-on-search="false" class="table table-bordered table-sm">
                  <thead class="thead-light">
                  <th data-field="wbm" data-sortable="false" data-visible="true" data-align="center">WBM</th>
                  <th data-field="google" data-sortable="false" data-visible="true" data-align="center">Google</th>
                  <th data-field="create" data-sortable="false" data-visible="true" data-align="center"><?=L( 'Create' )?></th>
                  <th data-field="url" data-sortable="true" data-width="100" data-width-unit="%"><?=L( 'URL' )?></th>
                  <th data-field="status" data-sortable="true"><?=L( 'Status' )?></th>
                  <th data-field="ignored" data-sortable="true" data-visible="false"><?=L( 'Ignored' )?></th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ( $missingUrls as $missingUrl ) { ?>
                    <tr data-unique-id="<?=$missingUrl['rowid']?>">
                      <td data-search="false">
                        <a class="small" target="_blank" rel="noreferrer noopener" href="https://web.archive.org/web/*/<?=$missingUrl['url']?>"><i class="fas fa-external-link-alt fa-fw"></i></a>
                      </td>
                      <td data-search="false">
                        <a class="small" target="_blank" rel="noreferrer noopener" href="https://www.google.com/search?q=inurl:<?=urlencode( '"' . parse_url( $missingUrl['url'], PHP_URL_PATH ) . '"' )?>"><i class="fas fa-external-link-alt fa-fw"></i></a>
                      </td>
                      <td data-search="false">
                        <?php
                        $missingUrl['mime'] = getMimeByExtension( pathinfo( parse_url( $missingUrl['url'], PHP_URL_PATH ), PATHINFO_EXTENSION ) );
                        ?>
                        <button class="btn btn-link btn-sm" type="button" data-toggle="modal" data-target="#createNewUrl" data-hostname="<?=htmlspecialchars( parse_url( $missingUrl['url'], PHP_URL_HOST ) )?>" data-path="<?=parse_url( $missingUrl['url'], PHP_URL_PATH )?>" data-mime="<?=$missingUrl['mime']['status'] ? htmlspecialchars( $missingUrl['mime']['mimetype'] ) : ''?>" data-charset="<?=$missingUrl['mime']['folder'] == 'html' ? 'utf-8' : ''?>" data-window="_blank" title="Create a new URL">
                          <i class="fas fa-file fa-fw"></i>
                        </button>
                      </td>
                      <td class="small"><?=htmlspecialchars( rawurldecode( $missingUrl['url'] ), ENT_IGNORE )?></td>
                      <td class="text-center small"><?=$missingUrl['status']?></td>
                      <td class="text-center small"><?=$missingUrl['ignore']?></td>
                    </tr>
                  <?php } ?>
                  </tbody>
                </table>
                <?php
              }
            }

            // EDIT URL
            if ( isset( $_POST['urlID'], $_POST['show'] ) && $_POST['show'] == 'edit.url' ) {
              $docBkpsCount = getBackupsCountByDocumentId( $metaData['rowid'] );
              ?>
              <div class="border p-0 mb-3 rounded">
                <div class="row m-3">
                  <div class="col-12 col-sm-auto p-1">
                    <a href="<?=$metaData['protocol'] . '://' . convertDomain( $metaData['hostname'] ) . $metaData['request_uri']?>" class="btn btn-primary my-1 btn-block" target="_blank">
                      <i class="fas fa-external-link-alt fa-fw"></i> <?=L( 'Open URL' )?></a>
                  </div>
                  <div class="col-12 col-sm-auto p-1">
                    <a href="#" class="btn btn-primary my-1 btn-block" data-toggle="modal" data-target="#cloneModal">
                      <i class="far fa-clone fa-fw"></i> <?=L( 'Clone URL' )?></a>
                  </div>
                  <div class="col-12 col-sm-auto p-1">
                    <form action="" method="post" class="d-inline">
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="download.url"/>
                      <input type="hidden" name="show" value="edit.url"/>
                      <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                      <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                      <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                      <button type="submit" class="btn btn-primary my-1 btn-block">
                        <i class="fas fa-download fa-fw"></i> <?=L( 'Download File' )?></button>
                    </form>
                  </div>
                  <?php if ( true && $metaData['mimetype'] == 'text/html' ) { ?>
                    <div class="col-12 col-sm-auto p-1">
                      <a href="#" class="btn btn-primary my-1 btn-block" data-toggle="modal" data-target="#templateModal">
                        <i class="fas fa-file-code fa-fw"></i> <?=L( 'Create a template' )?></a>
                    </div>
                  <?php } ?>
                  <div class="col-12 col-sm-auto p-1">
                    <form action="" method="post" class="d-inline" id="url_remove_<?=$metaData['rowid']?>">
                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                      <input type="hidden" name="action" value="remove.url"/>
                      <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                      <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                      <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                      <button type="button" class="btn btn-danger btn-action my-1 btn-block" data-toggle="modal" data-target="#confirm-action" data-source="url_remove_<?=$metaData['rowid']?>">
                        <i class="fas fa-trash-alt fa-fw"></i> <?=L( 'Remove URL' )?></button>
                    </form>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-12 col-xl-10 mb-3">
                  <div class="border rounded p-3">
                    <form action="" method="post" class="needs-validation" novalidate>
                      <div class="row">
                        <div class="col-12 col-md-6">

                          <div class="form-group row">
                            <label class="col-sm-2 col-form-label col-form-label-sm text-nowrap"><?=L( 'URI address' )?></label>
                            <div class="col-sm-10">
                              <input type="text" class="form-control form-control-sm" name="request_uri" value="<?=htmlspecialchars( rawurldecode( $metaData['request_uri'] ), ENT_IGNORE )?>" autocorrect="off" autocapitalize="none" required>
                            </div>
                          </div>

                          <div class="form-group row">
                            <label class="col-sm-2 col-form-label col-form-label-sm text-nowrap"><?=L( 'MIME-type' )?></label>
                            <div class="col-sm-10">
                              <input type="text" class="form-control form-control-sm" name="mimetype" pattern="[-a-z0-9+./]*" value="<?=htmlspecialchars( $metaData['mimetype'] )?>" autocorrect="off" autocapitalize="none" required>
                            </div>
                          </div>

                          <div class="form-group row">
                            <label class="col-sm-2 col-form-label col-form-label-sm"><?=L( 'Charset' )?></label>
                            <div class="col-sm-10">
                              <input type="text" class="form-control form-control-sm" name="charset" pattern="[-a-z0-9_]*" value="<?=htmlspecialchars( $metaData['charset'] )?>" autocorrect="off" autocapitalize="none">
                            </div>
                          </div>

                          <?php if ( inExpertMode() ) { ?>
                            <div class="form-group row">
                              <label class="col-sm-2 col-form-label col-form-label-sm"><?=L( 'File size' )?></label>
                              <div class="col-sm-10 small text-info align-self-center text-monospace">
                                <?=getHumanSize( $metaData['filesize'] )?> (<?=$metaData['filesize']?>b)
                              </div>
                            </div>
                          <?php } ?>

                        </div>

                        <div class="col-12 col-md-6">

                          <div class="form-group row">
                            <label class="col-sm-2 col-form-label col-form-label-sm"><?=L( 'Redirect' )?></label>
                            <div class="col-sm-10">
                              <input type="text" class="form-control form-control-sm" name="redirect" value="<?=htmlspecialchars( rawurldecode( $metaData['redirect'] ), ENT_IGNORE )?>" autocorrect="off" autocapitalize="none">
                            </div>
                          </div>

                          <div class="form-group row">
                            <label class="col-sm-2 col-form-label col-form-label-sm"><?=L( 'File time' )?></label>
                            <div class="col-sm-10">
                              <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm" name="filetime" pattern="[0-9]+" value="<?=htmlspecialchars( $metaData['filetime'] )?>" autocorrect="off" autocapitalize="none">
                                <div class="input-group-append">
                                <span class="input-group-text">
                                  <a class="text-dark" href="https://web.archive.org/web/<?=htmlspecialchars( $metaData['filetime'] ) . '/' . htmlspecialchars( $metaData['url'] )?>" target="_blank" rel="noopener noreferrer" title="<?=L( 'Open URL in Web Archive' )?>">
                                  <i class="fas fa-landmark"></i>
                                  </a>
                                </span>
                                </div>
                              </div>
                            </div>
                          </div>

                          <div class="form-group row">
                            <label class="col-sm-2 col-form-label col-form-label-sm"><?=L( 'Enabled' )?></label>
                            <div class="col-sm-10">
                              <div class="form-group form-check mb-2">
                                <input type="hidden" name="enabled" value="0">
                                <input class="form-check-input form-control-sm" style="margin-top: inherit;" type="checkbox" name="enabled" value="1" <?=( $metaData['enabled'] ? 'checked' : '' )?> id="enabledCheck">
                                <label class="form-check-label col-form-label-sm" for="enabledCheck">
                                  <?=L( 'Enable this URL' )?>
                                </label>
                              </div>
                            </div>
                          </div>

                          <?php if ( inExpertMode() ) { ?>
                            <div class="form-group row">
                              <label class="col-sm-2 col-form-label col-form-label-sm"><?=L( 'Depth' )?></label>
                              <div class="col-sm-10 small text-info align-self-center text-monospace">
                                <?=$metaData['depth']?>
                              </div>
                            </div>
                          <?php } ?>

                        </div>

                      </div>

                      <div class="text-center text-lg-right">
                        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                        <input type="hidden" name="action" value="update.url.settings"/>
                        <input type="hidden" name="show" value="edit.url"/>
                        <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                        <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                        <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                        <button type="submit" class="btn btn-sm btn-primary">
                          <i class="fas fa-save fa-fw"></i> <?=L( 'Save settings only' )?></button>
                      </div>
                    </form>
                  </div>
                </div>
                <div class="col-12 col-xl-2 mb-3">
                  <form action="<?=htmlspecialchars( $_SERVER['REQUEST_URI'] )?>" method="post" enctype="multipart/form-data" class="dropzone w-100 h-100" id="urlUpload">
                    <div class="dz-message">
                      <i class="fas fa-file-upload fa-fw fa-2x"></i><br> <?=L( 'Drop a file here to replace.' )?></div>
                    <div class="fallback">
                      <input name="file" type="file" multiple/>
                    </div>
                    <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                    <input type="hidden" name="action" value="update.url.upload"/>
                    <input type="hidden" name="show" value="edit.url"/>
                    <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                    <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                    <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                  </form>
                </div>
              </div>
              <!-- PREVIEW AREA -->
              <?php if ( $metaData['redirect'] || !$metaData['enabled'] ) { ?>
                <div class="bg-warning p-3 rounded text-center">
                  <?=L( 'Preview is not available because the URL is disabled or redirect is set.' )?>
                </div>
              <?php } ?>
              <?php
              if ( !$metaData['redirect'] && $metaData['enabled'] ) {
                $metaData['mime'] = getMimeInfo( $metaData['mimetype'] );
                switch ( $metaData['mime']['type'] ) {
                  case 'html' :
                  case 'text' :
                  case 'code' :
                    $isHtml = $metaData['mime']['type'] == 'html' ? true : false;
                    ?>
                    <ul class="nav nav-tabs mb-3" role="tablist" style="flex-wrap: inherit;">
                      <?php if ( $isHtml ) { ?>
                        <li class="nav-item">
                          <a class="nav-link <?=( $ACMS['ACMS_EDITOR_DEFAULT'] == 'visual' ) ? 'active' : ''?>" id="wysiwyg-tab" data-toggle="tab" href="#wysiwyg" role="tab" aria-controls="wysiwyg" aria-selected="<?=( $ACMS['ACMS_EDITOR_DEFAULT'] == 'visual' ) ? 'true' : ''?>"><?=L( 'Visual editor' )?></a>
                        </li>
                      <?php } ?>
                      <li class="nav-item">
                        <a class="nav-link <?=( ( !$isHtml || $ACMS['ACMS_EDITOR_DEFAULT'] == 'code' ) ? 'active' : '' )?>" id="code-tab" data-toggle="tab" href="#code" role="tab" aria-controls="code" aria-selected="<?=( ( !$isHtml || $ACMS['ACMS_EDITOR_DEFAULT'] == 'code' ) ? 'true' : '' )?>"><?=L( 'Code' )?></a>
                      </li>
                      <?php if ( $ACMS['ACMS_EDITOR_HISTORY'] && $docBkpsCount ) { ?>
                        <li class="nav-item">
                          <a class="nav-link" id="backups-tab" data-toggle="tab" href="#backups" role="tab" aria-controls="backups" aria-selected="false"><?=L( 'Changes' )?></a>
                        </li>
                      <?php } ?>
                    </ul>
                    <div class="tab-content">
                      <?php if ( $isHtml ) { ?>
                        <div class="tab-pane fade <?=( $ACMS['ACMS_EDITOR_DEFAULT'] == 'visual' ) ? 'show active' : ''?>" id="wysiwyg" role="tabpanel" aria-labelledby="wysiwyg-tab">
                          <form action="" method="post" onsubmit="ajaxSaveFile('formHtml'); return false;" id="formHtml">
                            <textarea id="textarea_html" name="content" class="w-100 h-100"><?=htmlspecialchars( convertEncoding( $content, 'UTF-8', $metaData['charset'] ), ENT_SUBSTITUTE );?></textarea>
                            <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                            <input type="hidden" name="action" value="update.url.content"/>
                            <input type="hidden" name="show" value="edit.url"/>
                            <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                            <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                            <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                            <input type="hidden" name="ajax" value="1"/>
                            <button type="submit" class="btn btn-primary float-right my-3">
                              <i class="fas fa-save fa-fw"></i> <?=L( 'Save' )?></button>
                          </form>
                        </div>
                      <?php } ?>
                      <div class="tab-pane fade <?=( ( !$isHtml || $ACMS['ACMS_EDITOR_DEFAULT'] == 'code' ) ? 'show active' : '' )?>" id="code" role="tabpanel" aria-labelledby="code-tab">
                        <form action="" method="post" onsubmit="ajaxSaveFile('<?=( $isHtml ? 'formCode' : 'formText' )?>'); return false;" id="<?=( $isHtml ? 'formCode' : 'formText' )?>">
                          <textarea id="textarea_text" name="content"><?=htmlspecialchars( convertEncoding( $content, 'UTF-8', $metaData['charset'] ), ENT_SUBSTITUTE );?></textarea>
                          <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                          <input type="hidden" name="action" value="update.url.content"/>
                          <input type="hidden" name="show" value="edit.url"/>
                          <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                          <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                          <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                          <input type="hidden" name="ajax" value="1"/>
                          <button type="submit" class="btn btn-primary float-right mt-3" id="cmSave" style="display: block; position: fixed; bottom: 30px; right: 30px; z-index: 99;">
                            <i class="fas fa-save fa-fw"></i> <?=L( 'Save' )?></button>
                        </form>
                      </div>
                      <?php if ( $ACMS['ACMS_EDITOR_HISTORY'] && $docBkpsCount ) { ?>
                        <div class="tab-pane fade" id="backups" role="tabpanel" aria-labelledby="backups-tab">
                          <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="showUnmodified">
                            <label class="custom-control-label" for="showUnmodified"><?=L( 'Show unmodified lines' )?></label>
                          </div>
                          <?php
                          if ( $docBkpsCount ) {
                            $pdo  = newPDO();
                            $pdo2 = newPDO();
                            $stmt = $pdo->prepare( "SELECT rowid, * FROM backup WHERE id = :documentId ORDER BY rowid DESC" );
                            $stmt->bindParam( 'documentId', $metaData['rowid'], PDO::PARAM_INT );
                            $stmt->execute();
                            $docBkpNum = 0;
                            // loop here to save memory vs storing add changes in array
                            while ( $docBkp = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
                              $docBkp['settings'] = json_decode( $docBkp['settings'], true );
                              ?>
                              <?php
                              if ( $docBkpNum == 0 ) {
                                $docBkpCurrentPath    = $sourcePath . DIRECTORY_SEPARATOR . $metaData['folder'] . DIRECTORY_SEPARATOR . $metaData['filename'];
                                $docBkpCurrentCharset = $metaData['charset'];
                                $docBkpCurrentMime    = $metaData['mimetype'];
                                $docBkpNextPath       = $sourcePath . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $docBkp['filename'];
                                $docBkpNextCharset    = $docBkp['settings']['charset'];
                                $docBkpNextMime       = $docBkp['settings']['mimetype'];
                                if (
                                  getMimeInfo( $docBkpCurrentMime )['folder'] != 'html' ||
                                  getMimeInfo( $docBkpNextMime )['folder'] != 'html'
                                ) $docBkpChanges = '';
                                else {
                                  $docBkpChanges = compareToHTML(
                                    compare(
                                      convertEncoding( file_get_contents( $docBkpNextPath ), 'UTF-8', $docBkpNextCharset ),
                                      convertEncoding( file_get_contents( $docBkpCurrentPath ), 'UTF-8', $docBkpCurrentCharset ),
                                      false
                                    ), '' );
                                }
                                ?>
                                <div class="d-flex align-items-center mt-3 mb-1">
                                  <div class="mr-2 text-nowrap">
                                    <span class="badge alert-success"><?=date( 'Y-m-d H:i:s', $docBkp['created'] )?></span>
                                    <span class="badge alert-warning"><?=L( 'action' )?>: <?=$docBkp['action']?></span>
                                  </div>
                                  <div class="w-100">
                                    <hr class="my-0">
                                  </div>
                                  <div class="ml-2 text-nowrap">
                                    <form action="" method="post" id="form_history_recover_url_<?=$docBkp['rowid']?>" class="form-wait">
                                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                                      <input type="hidden" name="action" value="history.recover.url">
                                      <input type="hidden" name="rowid" value="<?=$docBkp['rowid']?>">
                                      <input type="hidden" name="id" value="<?=$metaData['rowid']?>">
                                      <input type="hidden" name="urlID" value="<?=$metaData['rowid']?>">
                                      <input type="hidden" name="show" value="edit.url">
                                      <button class="btn badge btn-danger border-0">
                                        <i class="fas fa-undo fa-fw"></i> <?=L( 'roll back' )?></button>
                                    </form>
                                  </div>
                                </div>
                                <div class="revisions text-monospace small">
                                  <?=$docBkpChanges?>
                                </div>
                                <?php
                              }
                              $stmt2 = $pdo->prepare( "SELECT rowid, * FROM backup WHERE id = :documentId AND rowid < :rowid ORDER BY rowid DESC LIMIT 1" );
                              $stmt2->bindParam( 'documentId', $metaData['rowid'], PDO::PARAM_INT );
                              $stmt2->bindParam( 'rowid', $docBkp['rowid'], PDO::PARAM_INT );
                              $stmt2->execute();
                              $docBkpNext = $stmt2->fetch( PDO::FETCH_ASSOC );
                              if ( !empty( $docBkpNext ) ) {
                                $docBkpNext['settings'] = json_decode( $docBkpNext['settings'], true );
                                $docBkpCurrentPath      = $sourcePath . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $docBkp['filename'];
                                $docBkpCurrentCharset   = $docBkp['settings']['charset'];
                                $docBkpCurrentMime      = $docBkp['settings']['mimetype'];
                                $docBkpNextPath         = $sourcePath . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $docBkpNext['filename'];
                                $docBkpNextCharset      = $docBkpNext['settings']['charset'];
                                $docBkpNextMime         = $docBkpNext['settings']['mimetype'];
                                if (
                                  getMimeInfo( $docBkpCurrentMime )['folder'] != 'html' ||
                                  getMimeInfo( $docBkpNextMime )['folder'] != 'html'
                                ) $docBkpChanges = '';
                                else {
                                  $docBkpChanges = compareToHTML( compare(
                                    convertEncoding( file_get_contents( $docBkpNextPath ), 'UTF-8', $docBkpNextCharset ),
                                    convertEncoding( file_get_contents( $docBkpCurrentPath ), 'UTF-8', $docBkpCurrentCharset ),
                                    false
                                  ), '' );
                                }
                                ?>
                                <div class="d-flex align-items-center mt-3 mb-1">
                                  <div class="mr-2 text-nowrap">
                                    <span class="badge alert-success"><?=date( 'Y-m-d H:i:s', $docBkpNext['created'] )?></span>
                                    <span class="badge alert-warning"><?=L( 'action' )?>: <?=$docBkpNext['action']?></span>
                                  </div>
                                  <div class="w-100">
                                    <hr class="my-0">
                                  </div>
                                  <div class="ml-2 text-nowrap">
                                    <form action="" method="post" id="form_history_recover_url_<?=$docBkpNext['rowid']?>" class="form-wait">
                                      <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                                      <input type="hidden" name="action" value="history.recover.url">
                                      <input type="hidden" name="rowid" value="<?=$docBkpNext['rowid']?>">
                                      <input type="hidden" name="id" value="<?=$metaData['rowid']?>">
                                      <input type="hidden" name="urlID" value="<?=$metaData['rowid']?>">
                                      <input type="hidden" name="show" value="edit.url">
                                      <button class="btn badge btn-danger border-0">
                                        <i class="fas fa-undo fa-fw"></i> <?=L( 'roll back' )?></button>
                                    </form>
                                  </div>
                                </div>
                                <div class="revisions text-monospace small">
                                  <?=$docBkpChanges?>
                                </div>
                                <?php
                              }
                              $docBkpNum++;
                              ?>
                            <?php }
                          }
                          ?>
                        </div>
                      <?php } ?>
                    </div>
                    <?php
                    break;

                  case 'video' :
                    ?>
                    <div class="text-center p-3 mb-3">
                      <video src="<?=$metaData['request_uri']?>" type="<?=$metaData['mimetype']?>" controls>
                    </div>
                    <?php
                    break;

                  case 'audio' :
                    ?>
                    <div class="text-center p-3 mb-3">
                      <audio class="w-100" src="<?=$metaData['request_uri']?>" type="<?=$metaData['mimetype']?>" controls>
                    </div>
                    <?php
                    break;

                  case 'image' :
                    ?>
                    <div class="text-center p-3 mb-3 bg-image rounded">
                      <img class="img-fluid" src="<?=$metaData['request_uri']?>"/></div>
                    <?php
                    break;
                  case 'pdf' :
                    ?>
                    <object data="<?=$metaData['request_uri']?>" class="w-100" style="min-height:600px;" type="<?=$metaData['mimetype']?>"></object>
                    <?php
                    break;
                  default :
                    ?>
                    <div class="bg-secondary text-light p-3 rounded text-center p-3 mb-3">
                      <?=L( 'A preview for this file type may not be available in a browser.' )?>
                      <div>
                        <embed src="<?=$metaData['request_uri']?>" type="<?=$metaData['mimetype']?>">
                      </div>
                    </div>
                    <?php
                    break;
                }
              }
            }
            ?>


            <?php if ( $section == 'main' && $accessAllowed ) {
              $backupsStats     = getBackups( 'stats' );
              $backupsSize      = getDirectorySize( $sourcePath . DIRECTORY_SEPARATOR . 'backup' );
              $freeDiskSpace    = function_exists( 'disk_free_space' ) ? disk_free_space( $sourcePath ) : null;
              $importsStats     = getImportsSize();
              $missingUrlsStats = getMissingUrls( 'stats' );
              ?>
              <div class="card border-0 shadow mb-3">
                <div class="card-header bg-dark text-light">
                  <i class="fas fa-server fa-fw"></i> <?=L( 'System and Website Info' )?>
                </div>
                <div class="card-body mb-0">
                  <div class="row">
                    <div class="col-12 col-md-6 small">
                      <table class="table table-sm table-hover mb-3">
                        <thead class="thead-light">
                        <tr>
                          <th colspan="2"><?=L( 'Website' )?></th>
                        </tr>
                        </thead>
                        <tr>
                          <td><?=L( 'Serial number' )?></td>
                          <td class="text-monospace">
                            <a href="https://archivarix.com/<?=getLang()?>/status/<?=( $uuidSettings['uuidg'] ? $uuidSettings['uuidg'] : $uuidSettings['uuid'] )?>/" target="_blank" rel="nofollow noopener">
                              <?=implode( '-', str_split( $uuidSettings['uuid'], 4 ) )?>
                            </a>
                          </td>
                        </tr>
                        <tr>
                          <td><?=L( 'Created' )?></td>
                          <td><?=gmdate( "D, d M Y H:i:s", $uuidSettings['created'] )?> GMT</td>
                        </tr>
                        <tr>
                          <td><?=L( 'Version' )?></td>
                          <td><?=$uuidSettings['version']?></td>
                        </tr>
                        <tr>
                          <td><?=L( 'DB schema' )?></td>
                          <td><?=( empty( $uuidSettings['schema'] ) ? '1.0.1' : $uuidSettings['schema'] )?></td>
                        </tr>
                        <tr>
                          <td><?=L( 'History size' )?></td>
                          <td>
                            <form method="post" action="" class="d-inline">
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="history.edit">
                              <button type="submit" class="btn btn-link btn-sm text-left p-0">
                                <?=number_format( $backupsStats['total'] )?>
                                (<?=getHumanSize( $backupsSize )?>)
                              </button>
                            </form>
                            <?php
                            if ( $freeDiskSpace !== null && $freeDiskSpace < 1e+8 ) { ?>
                              <div class="text-danger text-uppercase">
                                <i class="fas fa-exclamation-triangle"></i> <?=sprintf( L( '%s free disk space left' ), getHumanSize( $freeDiskSpace ) )?>
                              </div>
                            <?php } ?>
                          </td>
                        </tr>
                        <tr>
                          <td><?=L( 'Imports size' )?></td>
                          <td>
                            <form method="post" action="" class="d-inline">
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="import.view">
                              <button type="submit" class="btn btn-link btn-sm text-left p-0">
                                <?=number_format( $importsStats['files'] )?>
                                (<?=getHumanSize( $importsStats['size'] )?>)
                              </button>
                            </form>
                          </td>
                        </tr>
                        <tr>
                          <td><?=L( 'Missing URLs' )?></td>
                          <td>
                            <?php
                            if ( empty( $missingUrlsStats['total'] ) ) {
                              echo L( 'None' );
                            } else {
                              ?>
                              <form method="post" action="" class="d-inline">
                                <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                                <input type="hidden" name="action" value="missing.show">
                                <button type="submit" class="btn btn-link btn-sm text-left p-0">
                                  <?=L( 'URLs' ) . ': ' . $missingUrlsStats['total'] . ', ' . L( 'visits' ) . ': ' . $missingUrlsStats['visits']?>
                                </button>
                              </form>
                              <?php
                            }
                            ?>
                          </td>
                        </tr>
                      </table>
                    </div>

                    <div class="col-12 col-md-6 small">
                      <table class="table table-sm table-hover mb-3">
                        <thead class="thead-light">
                        <tr>
                          <th colspan="2"><?=L( 'System' )?></th>
                        </tr>
                        </thead>
                        <tr>
                          <td><?=L( 'SQLite version' )?></td>
                          <td>
                            <?php if ( !getMissingExtensions( ['pdo_sqlite'] ) ) { ?>
                              <?=getSqliteVersion()?> <?=( version_compare( getSqliteVersion(), '3.7.0', '>=' ) ? '' : '<i class="fas fa-exclamation-circle text-danger" data-toggle="tooltip" title="' . L( 'We have to use a legacy .db file because you have outdated SQLite version. The minimum recommended version is 3.7.0' ) . '"></i>' )?>
                            <?php } else { ?>
                              <span class="badge badge-pill badge-danger"><?=L( 'None' )?></span>
                            <?php } ?>
                          </td>
                        </tr>
                        <tr>
                          <td><?=L( 'PHP version' )?></td>
                          <td><?=PHP_VERSION?> (<?=PHP_SAPI?>) <?=PHP_OS?></td>
                        </tr>
                        <tr>
                          <td><?=L( 'PHP extensions' )?></td>
                          <td>
                            <?php
                            $recommendedExtensions = ['curl', 'dom', 'fileinfo', 'iconv', 'imagick', 'intl', 'json', 'libxml', 'pcre', 'pdo_sqlite', 'zip',];
                            $missingExtensions     = getMissingExtensions( $recommendedExtensions );
                            foreach ( $recommendedExtensions as $recommendedExtension ) { ?>
                              <span class="badge badge-pill <?=in_array( $recommendedExtension, $missingExtensions ) ? 'badge-danger' : 'badge-success'?>"><?=$recommendedExtension?></span>
                            <?php } ?>
                          </td>
                        </tr>
                        <tr>
                          <td><?=L( 'Max upload size' )?></td>
                          <td><?=getHumanSize( getUploadLimit(), 0 )?></td>
                        </tr>
                        <tr>
                          <td><?=L( 'Max PHP memory' )?></td>
                          <td><?=getHumanSize( getBytesFromHumanSize( ini_get( 'memory_limit' ) ), 0 )?></td>
                        </tr>
                        <tr>
                          <td><?=L( 'Free disk space' )?></td>
                          <td><?=getHumanSize( $freeDiskSpace )?></td>
                        </tr>
                      </table>
                    </div>
                  </div>
                  <div class="">
                    <div class="text-right">
                      <a href="<?=$_SERVER['PHP_SELF'] . '?expert=' . ( inExpertMode() ? 0 : 1 )?>" class="text-dark text-decoration-none small">
                        <i class="fas fa-fw <?=( inExpertMode() ? 'fa-toggle-on text-info' : 'fa-toggle-off' )?>"></i>
                        <?=L( 'Expert mode' )?>
                      </a>
                    </div>
                  </div>
                </div>
              </div>

              <?php printStats( getInfoFromDatabase( $dsn ) ) ?>

              <?php
              $missingExtensions = getMissingExtensions( ['curl', 'zip'] );
              $loaderInfo        = getLoaderInfo();
              ?>
              <div class="card border-0 shadow mb-3">
                <div class="card-header bg-dark text-light">
                  <i class="fas fa-sync-alt fa-fw"></i> <?=L( 'System update' )?>
                </div>
                <div class="card-body small">
                  <div class="row">
                    <div class="col-12 col-md-6">
                      <?=L( 'This tool checks and updates Archivarix CMS, Archivarix Loader to the latest version.' )?> <?=L( 'If you have manually edited the source code, all changes will be lost.' )?> <?=L( 'CMS and Loader settings that were set using the Settings menu will not be affected.' )?></p>
                      <table class="table table-hover table-sm table-responsive mb-0">
                        <thead class="thead-light">
                        <tr>
                          <th class="w-100"><?=L( 'Name' )?></th>
                          <th><?=L( 'Version' )?></th>
                          <th><?=L( 'Filename' )?></th>
                        </tr>
                        </thead>
                        <tr>
                          <td class="text-nowrap">Archivarix CMS</td>
                          <td class="text-nowrap text-monospace"><?=ACMS_VERSION?></td>
                          <td class="text-nowrap text-monospace"><?=basename( $_SERVER['SCRIPT_FILENAME'] )?></td>
                        </tr>
                        <tr>
                          <td class="text-nowrap"><?=L( 'Archivarix Loader' )?><?=$loaderInfo['integration'] ? ' + <i class="fab fa-' . $loaderInfo['integration'] . ' fa-fw"></i>' : ''?></td>
                          <td class="text-nowrap text-monospace"><?=$loaderInfo['version'] ?: L( 'Not detected' )?></td>
                          <td class="text-nowrap text-monospace"><?=$loaderInfo['filename'] ?: L( 'Not detected' )?></td>
                        </tr>
                      </table>

                    </div>
                    <div class="col-12 col-md-6">

                      <?php if ( !empty( $missingExtensions ) ) { ?>
                        <div class="p-3 rounded bg-danger text-light mb-3 font-weight-bold"><?=sprintf( L( 'This tool requires the following PHP extensions to be installed: %s.' ), implode( ', ', $missingExtensions ) )?></div>
                      <?php } ?>
                      <div class="row">
                        <?php if ( empty( $loaderInfo['filename'] ) ) { ?>
                          <div class="col">
                            <form action="" method="post" id="form_install_loader" class="form-wait">
                              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                              <input type="hidden" name="action" value="install.loader">
                              <button type="button" class="btn btn-sm btn-block btn-primary btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_install_loader" data-message="<?=L( 'This action will install Archivarix Loader and replace .htaccess content.' )?>" <?=!empty( $missingExtensions ) ? ' disabled' : ''?>><?=L( 'Install missing Loader' )?></button>
                            </form>
                          </div>
                        <?php } ?>
                        <div class="col">
                          <div class="text-center">
                            <button type="button" class="btn btn-primary btn-sm btn-block" id="checkUpdate"><?=L( 'Check for updates' )?></button>
                          </div>
                        </div>
                        <div class="col">
                          <form action="" method="post" id="form_system_update" class="form-wait">
                            <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                            <input type="hidden" name="action" value="update.system">
                            <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                            <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                            <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
                            <button type="button" class="btn btn-sm btn-block btn-primary btn-action" data-toggle="modal" data-target="#confirm-action" data-source="form_system_update" <?=!empty( $missingExtensions ) ? ' disabled' : ''?>><?=L( 'Update CMS & Loader' )?></button>
                          </form>
                        </div>
                      </div>

                      <div class="mx-auto align-self-center">
                        <div id="messageUpdate" class="p-3 text-left"></div>
                      </div>


                    </div>

                  </div>

                </div>

              </div>

            <?php } ?>
          </div>
        </div>
      </div>
    <?php } ?>


    <!-- MODALS -->
    <div class="modal fade" id="createNewUrl" tabindex="-1" role="dialog" aria-hidden="false">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="" method="post" id="createNewUrlForm" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="modal-header bg-primary text-light">
              <h5 class="modal-title"><?=L( 'Create a new URL' )?></h5>
              <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <div class="input-group">
                  <div class="custom-file">
                    <input type="file" class="custom-file-input input-upload-file" name="create_file" id="input_create_url_file" aria-describedby="button_import_file">
                    <label class="custom-file-label text-truncate" for="input_create_url_file"><?=L( 'Choose a file' )?></label>
                  </div>
                </div>
                <small class="form-text text-muted"><?=L( 'Leave empty to create an empty file.' )?></small>
              </div>
              <div class="form-group">
                <label for="newUrlPath"><?=L( 'Path' )?></label>
                <input type="text" name="path" class="form-control" id="newUrlPath" placeholder="<?=L( 'Enter a path, e.g., /page.html' )?>" pattern="[/].*" autocorrect="off" autocapitalize="none" required>
                <div class="invalid-feedback">
                  <?=L( 'Enter a new path starting with a slash. This field cannot be empty.' )?>
                </div>
              </div>
              <div class="form-group">
                <label for="newUrlMime"><?=L( 'MIME-type' )?></label>
                <div class="input-group">
                  <input type="text" name="mime" class="form-control" id="newUrlMime" placeholder="<?=L( 'Enter a MIME-type' )?>" pattern="[-.+a-zA-Z0-9/]*" autocorrect="off" autocapitalize="none" required>
                  <div class="input-group-append">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?=L( 'or select' )?></button>
                    <div class="dropdown-menu dropdown-menu-right">
                      <a class="dropdown-item put-new-url-mime cursor-link" data-mime="text/html" data-charset="utf-8">HTML</a>
                      <a class="dropdown-item put-new-url-mime cursor-link" data-mime="text/css" data-charset="utf-8">CSS</a>
                      <a class="dropdown-item put-new-url-mime cursor-link" data-mime="application/javascript" data-charset="utf-8">JAVASCRIPT</a>
                      <a class="dropdown-item put-new-url-mime cursor-link" data-mime="text/plain" data-charset="utf-8">TEXT</a>
                      <a class="dropdown-item put-new-url-mime cursor-link" data-mime="application/json" data-charset="utf-8">JSON</a>
                      <a class="dropdown-item put-new-url-mime cursor-link" data-mime="image/jpeg" data-charset="">JPEG</a>
                      <a class="dropdown-item put-new-url-mime cursor-link" data-mime="image/png" data-charset="">PNG</a>
                      <a class="dropdown-item put-new-url-mime cursor-link" data-mime="image/gif" data-charset="">GIF</a>
                      <a class="dropdown-item put-new-url-mime cursor-link text-uppercase" data-mime="application/octet-stream" data-charset=""><?=L( 'Binary' )?></a>
                    </div>
                  </div>
                </div>
              </div>
              <a href="#createNewUrlAdditional" class="text-dark expand-label" data-toggle="collapse" aria-expanded="false" aria-controls="createNewUrlAdditional"><i class="fas fa-caret-right fa-fw mr-2"></i><?=L( 'Additional parameters' )?>
              </a>
              <div class="collapse hide mt-2" id="createNewUrlAdditional">
                <div class="form-group">
                  <label for="newUrlCharset"><?=L( 'Charset' )?></label>
                  <input type="text" name="charset" class="form-control" id="newUrlCharset" placeholder="<?=L( 'Enter a charset if required' )?>" pattern="[-a-zA-Z0-9/_]*" autocorrect="off" autocapitalize="none">
                </div>
                <div class="form-group">
                  <label for="newUrlHostname"><?=L( 'Domain' )?></label>
                  <input type="text" name="hostname" class="form-control" id="newUrlHostname" placeholder="<?=L( 'Enter a domain name' )?>" autocorrect="off" autocapitalize="none" required>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal"><?=L( 'Cancel' )?></button>
              <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
              <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
              <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
              <input type="hidden" name="action" value="create.url">
              <button type="submit" class="btn btn-primary"><?=L( 'Create' )?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php if ( $section == 'search' ) { ?>
      <div class="modal fade" id="regexModal" tabindex="-1" role="dialog" aria-labelledby="templateLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
          <div class="modal-content">
            <div class="modal-header bg-primary text-light">
              <h5 class="modal-title"><?=L( 'Regex Cheat Sheet' )?></h5>
              <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="alert alert-primary px-3 py-2 d-flex align-items-center">
                <?=sprintf( L( 'Archivarix CMS uses %s library for Regex' ), version_compare( PHP_VERSION, '7.3', '>=' ) ? 'PCRE2' : 'PCRE' )?>
                <a href="https://regex101.com/" target="_blank" rel="noreferrer noopener" class="btn btn-primary btn-sm ml-auto">
                  <i class="fas fa-external-link-alt"></i>
                  <?=L( 'Online Regex' )?>
                </a>
              </div>
              <div class="row">
                <div class="col-12 col-xl-6 mb-3">
                  <h5><?=L( 'Patterns and classes' )?></h5>
                  <kbd>[</kbd> <?=L( 'start character class definition' )?><br>
                  <kbd>]</kbd> <?=L( 'end character class definition' )?><br>
                  <kbd>(</kbd> <?=L( 'start subpattern' )?><br>
                  <kbd>)</kbd> <?=L( 'end subpattern' )?><br>
                </div>
                <div class="col-12 col-xl-6 mb-3">
                  <h5><?=L( 'Ranges' )?></h5>
                  <kbd>(a|b)</kbd> <?=L( 'a or b' )?><br>
                  <kbd>[abc]</kbd> <?=L( 'a or b or c' )?><br>
                  <kbd>[^abc]</kbd> <?=L( 'not a and not b and not c' )?><br>
                  <kbd>[a-h]</kbd> <?=L( 'letters between a and h (inclusive)' )?><br>
                  <kbd>[0-7]</kbd> <?=L( 'digits between 0 and 7 (inclusive)' )?><br>
                </div>
                <div class="col-12 col-xl-6 mb-3">
                  <h5><?=L( 'Anchors' )?></h5>
                  <kbd>^</kbd> <?=L( 'start of subject' )?><br>
                  <kbd>\A</kbd> <?=L( 'start of text' )?><br>
                  <kbd>$</kbd> <?=L( 'end of subject' )?><br>
                  <kbd>\Z</kbd> <?=L( 'end of text' )?><br>
                  <kbd>\b</kbd> <?=L( 'word boundary' )?><br>
                  <kbd>\B</kbd> <?=L( 'not a word boundary' )?><br>
                </div>
                <div class="col-12 col-xl-6 mb-3">
                  <h5><?=L( 'Base Character Classes' )?></h5>
                  <kbd>\s</kbd> <?=L( 'whitespace (space, tab, CRLF)' )?><br>
                  <kbd>\S</kbd> <?=L( 'any non-whitespace character' )?><br>
                  <kbd>\d</kbd> <?=L( 'digits (0-9)' )?><br>
                  <kbd>\D</kbd> <?=L( 'any non-digit character' )?><br>
                  <kbd>\w</kbd> <?=L( '"word" character (a-z 0-9 _)' )?><br>
                  <kbd>\W</kbd> <?=L( 'any non-"word" characters' )?><br>
                  <kbd>.</kbd> <?=L( 'any character' )?><br>
                </div>
                <div class="col-12 col-xl-6 mb-3">
                  <h5><?=L( 'Quantifiers' )?></h5>
                  <kbd>n*</kbd> <?=L( 'zero or more' )?><br>
                  <kbd>n*?</kbd> <?=L( 'non-greedy zero or more' )?><br>
                  <kbd>n+</kbd> <?=L( 'one or more' )?><br>
                  <kbd>n+?</kbd> <?=L( 'non-greedy one or more' )?><br>
                  <kbd>n?</kbd> <?=L( 'zero or one occurrence' )?><br>
                  <kbd>n??</kbd> <?=L( 'non-greedy zero or one occurrence' )?><br>
                  <kbd>{n}</kbd> <?=L( 'n occurrences exactly' )?><br>
                  <kbd>{n,}</kbd> <?=L( 'at least n occurrences' )?><br>
                  <kbd>{,m}</kbd> <?=L( 'at most m occurrences' )?><br>
                  <kbd>{n,m}</kbd> <?=L( 'between n and m occurrences (inclusive)' )?><br>
                  <kbd>{n,m}?</kbd> <?=L( 'non-greedy between n and m occurrences (inclusive)' )?><br>
                </div>
                <div class="col-12 col-xl-6 mb-3">
                  <h5><?=L( 'Subpattern Modifiers & Assertions' )?></h5>
                  <kbd>(?=)</kbd> <?=L( 'positive look-ahead assertion' )?><br>
                  <kbd>(?!)</kbd> <?=L( 'negative look-ahead assertion' )?><br>
                  <kbd>(?&lt;=)</kbd> <?=L( 'positive look-behind assertion' )?><br>
                  <kbd>(?&lt;!)</kbd> <?=L( 'negative look-behind assertion' )?><br>
                  <kbd>(?&gt;)</kbd> <?=L( 'once-only subpattern' )?><br>
                  <kbd>(?(x))</kbd> <?=L( 'conditional subpattern (if then)' )?><br>
                  <kbd>(?(x)|)</kbd> <?=L( 'conditional subpattern (if then else)' )?><br>
                  <kbd>(?#)</kbd> <?=L( 'comment' )?><br>
                  <kbd>(?:)</kbd> <?=L( 'a non-capturing subpattern' )?><br>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0">
              <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=L( 'Close' )?></button>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>
    <?php if ( $section == 'backups' ) { ?>
      <div class="modal fade" id="breakpointModal" tabindex="-1" role="dialog" aria-hidden="false">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <form action="" method="post" class="needs-validation" novalidate>
              <div class="modal-header bg-primary text-light">
                <h5 class="modal-title"><?=L( 'New breakpoint' )?></h5>
                <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="form-group">
                  <label for="breakpointName"><?=L( 'Name' )?></label>
                  <input type="text" name="name" class="form-control" id="breakpointName" placeholder="<?=L( 'Enter a name or description' )?>" pattern=".+" required>
                  <div class="invalid-feedback">
                    <?=L( 'Enter a name or a comprehensible short description.' )?>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=L( 'Cancel' )?></button>
                <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                <input type="hidden" name="action" value="history.breakpoint.create">
                <button type="submit" class="btn btn-primary"><?=L( 'Create' )?></button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php } ?>

    <div class="alert alert-success text-center position-fixed" id="savedAlert" role="alert" style="display: none; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 99;"></div>

    <div class="position-fixed d-none">
      <form id="formUrlsPage" action="" method="post">
        <input type="text" class="pageNumber" name="page">
        <input type="text" class="domainName" name="domain">
        <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
        <input type="hidden" name="action" value="change.page">
      </form>
    </div>

    <?php
    if ( isset( $metaData['rowid'] ) ) {
      ?>
      <div class="modal fade" id="cloneModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <form action="" method="post" class="needs-validation" novalidate>
              <div class="modal-header bg-primary text-light">
                <h5 class="modal-title"><?=L( 'Clone URL' )?></h5>
                <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="form-group">
                  <label for="cloneUrlPath"><?=L( 'A complete URL path' )?></label>
                  <input type="text" class="form-control mb-2 px-3 clear" name="request_uri" id="cloneUrlPath" value="<?=htmlspecialchars( rawurldecode( $metaData['request_uri'] ), ENT_IGNORE )?>" autocorrect="off" autocapitalize="none" required/>
                  <small class="form-text text-muted"><?=L( 'We recommend creating copies in the same directory as originals.' )?></small>
                </div>
                <a href="#cloneNewUrlAdditional" class="text-dark expand-label" data-toggle="collapse" aria-expanded="false" aria-controls="cloneNewUrlAdditional"><i class="fas fa-caret-right fa-fw mr-2"></i><?=L( 'Additional parameters' )?>
                </a>
                <div class="collapse hide mt-2" id="cloneNewUrlAdditional">
                  <div class="form-group">
                    <label for="cloneUrlHostname"><?=L( 'Domain' )?></label>
                    <input type="text" name="hostname" class="form-control" id="cloneUrlHostname" placeholder="<?=L( 'Enter a domain name' )?>" value="<?=htmlspecialchars( convertIdnToUtf8( $metaData['hostname'] ) )?>" autocorrect="off" autocapitalize="none" required>
                  </div>
                </div>
                <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                <input type="hidden" name="action" value="clone.url"/>
                <input type="hidden" name="show" value="edit.url"/>
                <input type="hidden" name="urlID" id="cloneUrlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
              </div>
              <div class="modal-footer border-0">
                <button type="submit" class="btn btn-primary"><?=L( 'Clone' )?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=L( 'Close' )?></button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="modal fade" id="templateModal" tabindex="-1" role="dialog" aria-labelledby="templateLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <form action="" method="post" class="needs-validation" novalidate>
              <div class="modal-header bg-primary text-light">
                <h5 class="modal-title"><?=L( 'Create a template' )?></h5>
                <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="form-group">
                  <label for="templateName"><?=L( 'Template name' )?></label>
                  <input type="text" class="form-control mb-2 px-3 clear" name="name" id="templateName" value="" pattern="[-\w]{1,64}" autocorrect="off" autocapitalize="none" required/>
                  <small class="form-text text-muted"><?=L( 'A template name can only contain Latin letters, numbers, dashes, and underscores.' )?></small>
                </div>
                <input type="hidden" name="xsrf" value="<?=getXSRF()?>">
                <input type="hidden" name="action" value="create.template"/>
                <input type="hidden" name="urlID" value="<?=!empty( $metaData['rowid'] ) ? $metaData['rowid'] : ''?>"/>
                <input type="hidden" name="filterValue" class="filterValue" value="<?=htmlspecialchars( $filterValue )?>"/>
                <input type="hidden" name="urlOffsets" class="urlOffsets" value="<?=htmlspecialchars( serialize( $urlOffsets ) )?>"/>
              </div>
              <div class="modal-footer border-0">
                <button type="submit" class="btn btn-primary"><?=L( 'Create' )?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=L( 'Close' )?></button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php
    }
  } // if logged
  ?>

  <div class="modal hide" id="pleaseWaitDialog" data-backdrop="static" data-keyboard="false">
    <div class="container d-flex align-items-center h-100">
      <div class="w-100 text-center">
        <div class="spinner-grow text-warning mb-3" style="width: 10rem; height: 10rem;" role="status">
        </div>
        <div class="text-light h3"><?=L( 'Processing' )?>&hellip;</div>
        <div class="text-light h5 message"><?=L( 'Do not close the browser window!' )?></div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="confirm-action" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <h5><?=L( 'Confirm the action' )?></h5>
          <div class="confirm-message"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default btn-danger" data-dismiss="modal">
            <i class="fas fa-times fa-fw"></i> <?=L( 'Cancel' )?></button>
          <a class="btn btn-success btn-ok text-white" data-source=""><i class="fas fa-check fa-fw"></i> <?=L( 'Confirm' )?>
          </a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.1/umd/popper.min.js" integrity="sha512-ubuT8Z88WxezgSqf3RLuNi5lmjstiJcyezx34yIU2gAHonIi27Na7atqzUZCOoY4CExaoFumzOsFQ2Ch+I/HCw==" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.min.js" integrity="sha512-XKa9Hemdy1Ui3KSGgJdgMyYlUg1gM+QhL6cnlyTe2qzMCYm4nAZ1PsVerQzTTXzonUR+dmswHqgJPuwCq1MaAg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <?php if ( in_array( $section, ['table', 'missing.urls', 'backups', 'search'] ) ) { ?>
    <script src="https://unpkg.com/tableexport.jquery.plugin/tableExport.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.min.js" integrity="sha512-ToRWKKOvhBSS8EtqSflysM/S7v9bB9V0X3B1+E7xo7XZBEZCPL3VX5SFIp8zxY19r7Sz0svqQVbAOx+QcLQSAQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.14/jspdf.plugin.autotable.min.js" integrity="sha512-m4dz/cB+GDbGitRXh/Ppt8vsDpD2nqhviX1eoD7wgu3xSO2B9EUKjZKsZLbmyUS0d43vLzakJcPFzxi7eHlmBw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.18.3/bootstrap-table.min.js" integrity="sha512-Wm00XTqNHcGqQgiDlZVpK4QIhO2MmMJfzNJfh8wwbBC9BR0FtdJwPqDhEYy8jCfKEhWWZe/LDB6FwY7YE9QhMg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.18.3/extensions/export/bootstrap-table-export.min.js" integrity="sha512-cAMZL39BuY4jWHUkLWRS+TlHzd/riowdz6RNNVI6CdKRQw1p1rDn8n34lu6pricfL0i8YXeWQIDF5Xa/HBVLRg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.18.3/locale/bootstrap-table-<?=$cmsLocales[getLang()]['code']?>.min.js" crossorigin="anonymous"></script>
  <?php } ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.11/jstree.min.js" integrity="sha512-bU6dl4fd2XN3Do3aWypPP2DcKywDyR3YlyszV+rOw9OpglrGyBs6TyTsbglf9umgE+sy+dKm1UHhi07Lv+Vtfg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <?php if ( in_array( $section, ['settings', 'template', 'htaccess', 'trackers', 'import.urls'] ) || isset( $metaData['rowid'] ) ) { ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/codemirror.min.js" integrity="sha512-ZTpbCvmiv7Zt4rK0ltotRJVRaSBKFQHQTrwfs6DoYlBYzO1MA6Oz2WguC+LkV8pGiHraYLEpo7Paa+hoVbCfKw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/dialog/dialog.min.js" integrity="sha512-we+ZB6C1/Gi+WtMSWNefd59oPqrfzsm8StIbEtIwaSP1CfPdV/ScqEknSw3nQS4tocb9885wdu/TgPfCDePx7Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/display/autorefresh.min.js" integrity="sha512-vAsKB7xXQAWMn5kcwda0HkFVKUxSYwrmrGprVhmbGFNAG1Ij+2epT3zzdwjHTJyDsKXsiEdrUdhIxh7loHyX+A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/edit/matchbrackets.min.js" integrity="sha512-03Ei8A+mDhwF6O/CmXM47U4A9L7TobAxMbPV2Wn5cEbY76lngHQRyvvmnqhJ8IthfoxrRqmtoBxQCxOC7AOeKw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/edit/matchtags.min.js" integrity="sha512-RhFcU90dzfpVCAfiAAFCqH/UESr9/ZzrwX9gW1ZjRh9kPu2CTqvWuk85U6ECWis/M9/yZemU+sheJspFobQOag==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/search/jump-to-line.min.js" integrity="sha512-82DIItx2hxdDqa893OQJ86MzyRj2Zrf8SvXPu/XlHSV6WhFMLx5ZeRFmnrHQPi1zVRXlqrlpliidEXFTj+Zuag==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/search/search.min.js" integrity="sha512-yKYvYs+KegwaGyc9AXAl3VAWEaNzHEV0tL5HuH6Y9Tasq5Q0fqivzctlQA2UuyEOTa5J+hMZAuMifUJUdA8NGw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/search/searchcursor.min.js" integrity="sha512-ebe4PgbfWfZ4YEJJC2gPiIqZ/jdf/eDBRmk4mc31LkEDu60STy0Mtr3D5WbVN24yDYNFwIGQecxp9iSotrn4XQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/selection/active-line.min.js" integrity="sha512-UNVAZmixdjeBtJVQcH5eSKXuVdzbSV6rzfTfNVyYWUIIDCdI9/G8/Z/nWplnSHXXxz9U8TA1BiJ1trK7abL/dg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/mode/css/css.min.js" integrity="sha512-YeNG6eTv+q+p/vvx+E5u05IBRurTlv0jfQuvitZMD+oNe9TfrGw+z4MHHxhPlE3X3csemC5YXlzDLMSZrgb34A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/mode/htmlmixed/htmlmixed.min.js" integrity="sha512-IC+qg9ITjo2CLFOTQcO6fBbvisTeJmiT5D5FnXsCptqY8t7/UxWhOorn2X+GHkoD1FNkyfnMJujt5PcB7qutyA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/mode/javascript/javascript.min.js" integrity="sha512-cMW1RqDC6+KwVloyQoUjqgmM5B0QGZcpZEAHJsab2WrCBmuyqoojv6cQ8O7KtAYtPym2vCooftLPeGzf0klXyA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/mode/php/php.min.js" integrity="sha512-VYPUSn3XJTii+yEASLCfsbt/mM4I1dHCDdCUKlzXE2uXccT9c8MvqtUvvV3+HnJgVtkKGH6qS63FscTYbXmcmw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/mode/xml/xml.min.js" integrity="sha512-XPih7uxiYsO+igRn/NA2A56REKF3igCp5t0W1yYhddwHsk70rN1bbbMzYkxrvjQ6uk+W3m+qExHIJlFzE6m5eg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.2/dropzone.min.js" integrity="sha512-llCHNP2CQS+o3EUK2QFehPlOngm8Oa7vkvdUpEFN71dVOf3yAj9yMoPdS5aYRTy8AEdVtqUBIsVThzUSggT0LQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <?php } ?>
  <?php if ( in_array( $section, ['settings'] ) ) { ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.13.0/Sortable.min.js" integrity="sha512-5x7t0fTAVo9dpfbp3WtE2N6bfipUwk7siViWncdDoSz2KwOqVC1N9fDxEOzk0vTThOua/mglfF8NO7uVDLRC8Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <?php } ?>
  <?php if ( isset( $metaData['rowid'] ) || in_array( $section, ['template', 'page'] ) ) { ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.9.11/tinymce.min.js" integrity="sha512-3tlegnpoIDTv9JHc9yJO8wnkrIkq7WO7QJLi5YfaeTmZHvfrb1twMwqT4C0K8BLBbaiR6MOo77pLXO1/PztcLg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <?php } ?>

  <script language="JavaScript">
    (function () {
      'use strict';
      window.addEventListener('load', function () {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function (form) {
          form.addEventListener('submit', function (event) {
            if (form.checkValidity() === false) {
              event.preventDefault();
              event.stopPropagation();
            }
            form.classList.add('was-validated');
          }, false);
        });
      }, false);
    })();

    var submitNonAjax = function formSubmitNonAjax(form_id) {
      $('#savedAlert').html('<?=htmlspecialchars( L( 'Sending with AJAX failed. Make sure mod_security is not enabled.' ), ENT_QUOTES )?>');
      $('#savedAlert').show('fast').delay(5000).hide('fast');
      //$('#' + form_id + ' input[name=ajax]').remove();
      //setTimeout(function () {
      //  $('#' + form_id).removeAttr('onsubmit').submit();
      //}, 4000);
    };

    $(function () {
      $('[data-toggle="tooltip"]').tooltip();
      $('.toast').toast('show');
    });

    $('.form-wait').submit(function (e) {
      if (this.checkValidity() === false) {
        e.preventDefault();
        e.stopPropagation();
        return;
      }
      e.preventDefault();
      this.submit();
      $('#pleaseWaitDialog').modal();
    });

    $(".check-all-switch").on('click', function () {
      $('.' + $(this).data('target')).prop('checked', $(this).prop('checked'));
    });

    <?php if($taskIncomplete) { ?>
    $('#pleaseWaitDialog .message').html('<?=htmlspecialchars( sprintf( L( 'Processed: %s' ), number_format( unserialize( $taskStats )['pages'] ), 2 ), ENT_QUOTES )?>');
    $('#form_task_part').submit();
    <?php } ?>

    $('.btn-action').on('click touch', function () {
      $('#confirm-action .confirm-message').html($(this).data('message'));
      $('#confirm-action .btn-ok').attr('data-source', $(this).attr('data-source'));
    });

    $('.btn-ok').on('click touch', function () {
      $('#confirm-action').modal('hide');
      var datasource = $(this).attr('data-source');
      $('#' + datasource).submit();
    });

    $('.password-eye').on('click touch', function () {
      $(this).toggleClass('fa-eye').toggleClass('fa-eye-slash');
      var type = $(this).closest('.input-group').children('input').attr("type");
      if (type == "text") $(this).closest('.input-group').children('input').attr("type", "password"); else $(this).closest('.input-group').children('input').attr("type", "text");
    });

    $('.clipboard-copy').on('click touch', function () {
      var $temp = $("<input>");
      $("body").append($temp);
      $temp.val($(this).attr('data-clipboard-text')).select();
      document.execCommand("copy");
      $temp.remove();
      $('#savedAlert').html('<?=htmlspecialchars( L( 'Copied to clipboard' ), ENT_QUOTES )?>');
      $('#savedAlert').show('fast').delay(4000).hide('fast');
    });

    $('#acms_settings_password, #acms_settings_safe_password').on('input change', function () {
      if (
        $('#acms_settings_password').val().length &&
        $('#acms_settings_password').val() == $('#acms_settings_safe_password').val()
      ) {
        $('#div_acms_settings_passwords_match').removeClass('d-none');
      } else {
        $('#div_acms_settings_passwords_match').addClass('d-none');
      }
    });

    // LOADER CUSTOM RULES
    $('.loader-settings-rules-wrapper').on('click touch', '.remove-loader-custom-rule', function () {
      $(this).closest('.loader-custom-rule-block').fadeOut(500, function () {
        $(this).remove();
      });
    });

    $('#create-loader-custom-rule').on('click touch', function () {
      newRule = $('.loader-custom-rule-block.d-none').clone();
      newRule.removeClass('d-none');
      newRule.appendTo('.loader-settings-rules-wrapper');
    });

    $('.loader-settings-rules-wrapper').on('click touch', '.put-custom-file', function () {
      $(this).closest('.input-group').children('input').val($(this).data('filename'));
    });

    $('.loader-settings-rules-wrapper').on('click touch', '.put-custom-rule', function () {
      $(this).closest('.loader-custom-rule-block').find('.input-custom-keyphrase').val($(this).data('keyphrase'));
      $(this).closest('.loader-custom-rule-block').find('.input-custom-urlmatch').val($(this).data('urlmatch'));
      $(this).closest('.loader-custom-rule-block').find('.select-custom-regex').val($(this).data('regex')).change();
      $(this).closest('.loader-custom-rule-block').find('.input-custom-limit').val($(this).data('limit'));
      $(this).closest('.loader-custom-rule-block').find('.select-custom-position').val($(this).data('position')).change();
    });

    // filter rules
    $('.search-code-add-filter').on('click touch', function () {
      newRule = $('.search-filter-block.d-none').clone();
      newRule.removeClass('d-none');
      newRule.appendTo('.search-code-filter-wrapper');
    });

    $('.search-url-all').on('click touch', function () {
      $('.search-url-search').val('^');
      $('#perlRegexUrl').prop('checked', true);
    });

    $('.search-url-add-filter').on('click touch', function () {
      newRule = $('.search-filter-block.d-none').clone();
      newRule.removeClass('d-none');
      newRule.appendTo('.search-url-filter-wrapper');
    });

    $('.search-filter-wrapper').on('click touch', '.remove-search-filter', function () {
      $(this).closest('.search-filter-block').fadeOut(500, function () {
        $(this).remove();
      });
    });

    $('.search-filter-wrapper').on('change', '.search-filter-param', function () {
      searchFilterTag = $(this).find('option:selected').data('tag');
      searchFilterType = $(this).find('option:selected').data('type');
      searchFilterRegex = $(this).find('option:selected').data('regex');
      searchFilterCaseSensitive = $(this).find('option:selected').data('case-sensitive');
      searchFilterOperators = $(this).find('option:selected').data('operators').split(',');
      searchFilterPlaceholder = $(this).find('option:selected').data('placeholder');
      searchFilterTextCurrent = $(this).closest('.search-filter-block').find('.search-filter-text');
      searchFilterSelectOperator = $(this).closest('.search-filter-block').find('.search-filter-operator');
      searchFilterValue = searchFilterTextCurrent.val();
      searchFilterText = $(document.createElement(searchFilterTag)).attr('type', searchFilterType);
      searchFilterText.attr({
        name: "filter[text][]",
        class: "form-control search-filter-text text-monospace",
        placeholder: searchFilterPlaceholder
      });
      searchFilterText.val(searchFilterValue);
      searchFilterTextCurrent.replaceWith(searchFilterText);
      if (searchFilterRegex) {
        $(this).closest('.search-filter-block').find('.search-filter-regex-div').removeClass('d-none');
      } else {
        $(this).closest('.search-filter-block').find('.search-filter-regex-div').addClass('d-none');
      }
      if (searchFilterCaseSensitive) {
        $(this).closest('.search-filter-block').find('.search-filter-case-sensitive-div').removeClass('d-none');
      } else {
        $(this).closest('.search-filter-block').find('.search-filter-case-sensitive-div').addClass('d-none');
      }
      searchFilterSelectOperator.find('option').each(function () {
        if (searchFilterOperators.includes($(this).val())) {
          $(this).removeAttr('disabled hidden');
        } else {
          $(this).removeAttr('selected').attr({disabled: true, hidden: true});
        }
      });
      searchFilterSelectOperator.find('option:not([disabled]):first').prop('selected', true);
    });

    $('.search-filter-wrapper').on('change', '.search-filter-regex-checkbox', function () {
      if (this.checked) {
        $(this).prev().val(1);
      } else {
        $(this).prev().val(0);
      }
    });

    $('.search-filter-wrapper').on('change', '.search-filter-case-sensitive-checkbox', function () {
      if (this.checked) {
        $(this).prev().val(1);
      } else {
        $(this).prev().val(0);
      }
    });

    $('.import-external-urls-wrapper').on('click touch', '.put-custom-rule', function () {
      $(this).closest('.import-external-urls-wrapper').find('.input-user-agent').val($(this).data('useragent'));
    });

    $('.constructor-param').on('click touch', function () {
      constructorInput = $(this).closest('.constructor-wrapper').find('.input-constructor');
      if (constructorInput.val().slice(constructorInput.val().length - 1) == '/') {
        constructorInput.val(constructorInput.val().slice(0, -1));
      }
      if ($(this).data('slash')) {
        constructorInput.val(constructorInput.val() + '/' + $(this).data('value') + '/');
      } else {
        constructorInput.val(constructorInput.val() + $(this).data('value'));
      }
    });

    $('.play-youtube').on('click touch', function () {
      $('.videos-welcome-list').addClass('justify-content-center');
      $('.play-youtube').removeClass('text-primary').addClass('text-dark');
      $(this).removeClass('text-dark').addClass('text-primary');
      $('.player-youtube').show(1000);
      $('.player-youtube iframe').attr('src', 'https://www.youtube.com/embed/' + $(this).data('videoid') + '?autoplay=1&rel=0&modestbranding=1&hl=<?=getLang()?>');
    });

    $('.input-resize-auto').keydown(resizeInput).keyup(resizeInput).each(resizeInput);

    $('.input-clear').on('click touch', function () {
      $(this).closest('.input-group').find('input').val('');
    });

    $('#createNewUrl').on('shown.bs.modal', function (e) {
      var trigger = $(e.relatedTarget);
      $('#newUrlHostname').val(trigger.data('hostname'));
      $('#newUrlPath').val(trigger.data('path'));
      $('#newUrlMime').val(trigger.data('mime'));
      $('#newUrlCharset').val(trigger.data('charset'));
      console.log(trigger.data('window'));
      if (trigger.data('window') == '_blank') {
        $('#createNewUrlForm').attr('target', '_blank');
      }
    });

    $('#templateModal').on('shown.bs.modal', function (e) {
      $('#templateName').focus();
    });

    $('#breakpointModal').on('shown.bs.modal', function (e) {
      $('#breakpointName').focus();
    });

    $('#clickSearchNew').on('click touch', function () {
      $('#formSearchNew').submit();
    });

    $('#clickToolsView').on('click touch', function () {
      $('#formToolsView').submit();
    });

    $('#clickHistory').on('click touch', function () {
      $('#formHistoryNew').submit();
    });

    $('#clickSettings').on('click touch', function () {
      $('#formSettingsView').submit();
    });

    $('.changePage').on('click touch', function () {
      $('#formUrlsPage .pageNumber').val($(this).data('page'));
      $('#formUrlsPage .domainName').val($(this).data('domain'));
      $('#formUrlsPage').submit();
    });

    $(".check-all").click(function () {
      $("." + $(this).data("group")).prop('checked', $(this).prop('checked'));
    });

    $(".stats-collapse").on('shown.bs.collapse', function () {
      window["drawStatsMimeCount_" + $(this).data('id')]();
      window["drawStatsMimeSize_" + $(this).data('id')]();
    });

    $('.input-upload-file').on('change', function () {
      var importFileName = $(this)[0].files[0].name;
      $(this).next('.custom-file-label').text(importFileName);
    });

    $('#input_create_url_file').on('change', function () {
      $('#newUrlPath').val('/' + $(this)[0].files[0].name);
      $('#newUrlMime').val($(this)[0].files[0].type);
    });

    $(".put-new-url-mime").on('click touch', function () {
      $('#newUrlMime').val($(this).data('mime'));
      $('#newUrlCharset').val($(this).data('charset'));
    });

    <?php if ( isset( $missingUrls ) || isset ( $backups ) ) { ?>
    $('#historyRecoverSelected').on('click touch', function () {
      rows = [];
      var $table = $('#table_history');
      $table.bootstrapTable('getSelections').forEach(function (row) {
        rows.push(row.id);
      });
      var url = "<?=$_SERVER['REQUEST_URI']?>";
      $.post({
        url: url,
        data: {
          action: "history.recover",
          ajax: 1,
          xsrf: "<?=getXSRF()?>",
          backups: rows.join(),
        },
        success: function (data) {
          rows.forEach(function (row) {
            $table.bootstrapTable('removeByUniqueId', row);
          });
        },
        error: function (data) {
          submitNonAjax('historyRecoverSelected');
        }
      });
    });

    $('#historyPurgeSelected').on('click touch', function () {
      rows = [];
      var $table = $('#table_history');
      $table.bootstrapTable('getSelections').forEach(function (row) {
        rows.push(row.id);
      });
      var url = "<?=$_SERVER['REQUEST_URI']?>";
      $.post({
        url: url,
        data: {
          action: "history.purge",
          ajax: 1,
          xsrf: "<?=getXSRF()?>",
          backups: rows.join(),
        },
        success: function (data) {
          rows.forEach(function (row) {
            $table.bootstrapTable('removeByUniqueId', row);
          });
        },
        error: function (data) {
          submitNonAjax('historyPurgeSelected');
        }
      });
    });
    <?php } ?>

    $('#checkUpdate').on('click touch', function () {
      $.getJSON("https://archivarix.com/<?=getLang()?>/cms/?ver=<?=ACMS_VERSION?><?=( !empty( $loaderInfo['version'] ) ? '&loader=' . $loaderInfo['version'] : '' )?>&uid=<?=sha1( __DIR__ )?>", function (json) {
        $('#messageUpdate').html(json.message);
      });
    });

    $('#code-tab').on('shown.bs.tab', function () {
      editor.refresh();
    });

    $('#div_create_custom_file').on('shown.bs.collapse', function () {
      editor.refresh();
    });

    $('#showUnmodified').change(function () {
      if (this.checked) $('.revisions span').css('display', 'block');
      else $('.revisions span').css('display', 'none');
    });

    function resizeInput() {
      $(this).attr('size', ($(this).val().length ? $(this).val().length : 1));
    }

    function ajaxRemoveURL(form) {
      var url = "<?=$_SERVER['REQUEST_URI']?>";
      $.post({
        url: url,
        data: $('#' + form).serialize(),
        dataType: 'json',
        success: function (data) {
          $('#' + form).closest('.search-result').hide('fast', function () {
            $(this).remove();
          });
        },
        error: function (data) {
          $('#savedAlert').html('<?=htmlspecialchars( L( 'Sending with AJAX failed. Your server blocks XHR POST requests.' ), ENT_QUOTES )?>');
          $('#savedAlert').show('fast').delay(5000).hide('fast');
        }
      });
      return false;
    }

    function ajaxSaveFile(form) {
      if (form == 'formHtml') {
        tinymce.triggerSave();
      }
      if (form == 'formCode') {
        editor.save();
      }
      if (form == 'formText') {
        editor.save();
      }
      if (form == 'formCustomFile') {
        return;
      }
      if (form == 'formCreateCustomFile') {
        return;
      }
      if (form == 'formSimpleHtml') {
        return;
      }

      var url = "<?=$_SERVER['REQUEST_URI']?>";
      $.post({
        url: url,
        data: $('#' + form).serialize(),
        dataType: 'json',
        //contentType: 'application/json',
        success: function (data) {
          $('#savedAlert').html('<?=htmlspecialchars( L( 'Saved' ), ENT_QUOTES )?>');
          if (form == 'formHtml') {
            editor.setValue($('#textarea_html').val());
            $('#savedAlert').show('fast').delay(2000).hide('fast');
          }
          if (form == 'formCode') {
            tinymce.editors[0].setContent($('#textarea_text').val());
            tinymce.editors[0].theme.resizeTo('100%', 500);
            tinymce.editors[0].focus();
            $('#savedAlert').show('fast').delay(2000).hide('fast');
          }
          if (form == 'formText') {
            $('#savedAlert').show('fast').delay(2000).hide('fast');
          }
          if (form == 'formCustomFile') {
            return false;
          }
          if (form == 'formCreateCustomFile') {
            return false;
          }
          if (form == 'formSimpleHtml') {
            return false;
          }
        },
        error: function (data) {
          submitNonAjax(form);
        }
      });
      return false;
    }


    function post(path, parameters) {
      var form = $('<form></form>');

      form.attr("method", "post");
      form.attr("action", path);

      $.each(parameters, function (key, value) {
        var field = $('<input></input>');

        field.attr("type", "hidden");
        field.attr("name", key);
        field.attr("value", value);

        form.append(field);
      });

      $(document.body).append(form);
      form.submit();
    }


    // JSTree
    $('[id^=jstree_]').each(function () {
      $(this).jstree({
        "plugins": [
          "wholerow",
          "search",
          "sort",
          "state",
        ],
        "search": {
          "case_sensitive": false,
          "show_only_matches": true
        },
        "core": {
          "themes": {
            "variant": "small",
          },
          "multiple": false,
        },
        "state": {
          "key": "domainname",
        },
        "sort": function (a, b) {
          a1 = this.get_node(a);
          b1 = this.get_node(b);
          if (a1.data.jstree.order == b1.data.jstree.order) {
            return (a1.text > b1.text) ? 1 : -1;
          } else {
            return (a1.data.jstree.order > b1.data.jstree.order) ? 1 : -1;
          }
        },
      })
    });

    $('[id^=jstree_]')
      .on('activate_node.jstree', function (e, data) {
        post('//' + $(this).data("domain-converted") + '<?=htmlspecialchars( $_SERVER['REQUEST_URI'] )?>', {
          urlID: data.instance.get_node(data.node.id).data.jstree.id,
          show: "edit.url",
          filterValue: $('#treeSearch').val(),
          sender: "jstree",
        });
      });

    $('.template-link').on('click touch', function () {
      var url = "<?=$_SERVER['REQUEST_URI']?>";
      post(url,
        {
          show: "edit.template",
          xsrf: "<?=getXSRF()?>",
          name: $(this).data('name'),
        });
    });

    $('.template-create-page-link').on('click touch', function () {
      var url = "<?=$_SERVER['REQUEST_URI']?>";
      post(url,
        {
          action: "show.create.page",
          xsrf: "<?=getXSRF()?>",
          name: $(this).data('name'),
        });
    });

    $('#template-param-panel button').on('click touch', function () {
      $('#template-param-type').html($(this).data('type'));
      if ($(this).data('name')) {
        $('#template-param-name').val($(this).data('name'));
        $('#template-param-name').each(resizeInput);
      }
    });

    $('#template-param-insert').on('click touch', function () {
      editor.replaceSelection("{{@" + $('#template-param-type').html() + "('" + $('#template-param-name').val() + "')}}");
    });


    $(".urls-tree-expand").on('click touch', function () {
      if ($("#" + $(this).data("jstree")).find(".jstree-open").length !== 0) {
        $("#" + $(this).data("jstree")).jstree('close_all');
      } else {
        $("#" + $(this).data("jstree")).jstree('open_all');
      }
    });

    urlshidden = localStorage.urlshidden === undefined ? [] : JSON.parse(localStorage.urlshidden);
    for (var i in urlshidden) {
      if ($("#" + urlshidden[i]).hasClass('urls-collapse')) {
        $("#" + urlshidden[i]).collapse("hide");
      }
    }

    $(".urls-collapse").on('hidden.bs.collapse', function () {
      var active = $(this).attr('id');
      var urlshidden = localStorage.urlshidden === undefined ? [] : JSON.parse(localStorage.urlshidden);
      if ($.inArray(active, urlshidden) == -1) urlshidden.push(active);
      localStorage.urlshidden = JSON.stringify(urlshidden);
    });

    $(".urls-collapse").on('shown.bs.collapse', function () {
      var active = $(this).attr('id');
      var urlshidden = localStorage.urlshidden === undefined ? [] : JSON.parse(localStorage.urlshidden);
      var elementIndex = $.inArray(active, urlshidden);
      if (elementIndex !== -1) urlshidden.splice(elementIndex, 1);
      localStorage.urlshidden = JSON.stringify(urlshidden);
    });

    // JSTree search action
    var to = false;
    $('#treeSearch').keyup(function () {
      if (to) {
        clearTimeout(to);
      }
      to = setTimeout(function () {
        var v = $('#treeSearch').val();
        $('.filterValue').val(v);
        $('[id^=jstree_]').each(function () {
          $(this).jstree(true).search(v)
        });
      }, 250);
    });

    // JSTree search if any
    var v = $('#treeSearch').val();
    if (v) {
      $('[id^=jstree_]').each(function () {
        $(this).jstree(true).search(v)
      });
    }

    <?php if (in_array( $section, ['settings'] )) { ?>
    new Sortable(sortwrapper, {
      handle: '.sort-handle',
      swapThreshold: 0.40,
      animation: 500,
      ghostClass: 'border-dark',
    });
    <?php } ?>

    <?php if (in_array( $section, ['settings', 'template'] ) || isset( $metaData['rowid'] )) { ?>
    // Dropzone
    Dropzone.options.urlUpload = {
      uploadMultiple: false,
      maxFiles: 1,
      rowID: "<?=$documentID?>",
      "filterValue": $('#treeSearch').val(),
      maxFilesize: <?=floor( getUploadLimit() / 1024 / 1024 )?>,
      addRemoveLinks: true,
      init: function () {
        this.on('success', function () {
          if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
            post('', {
              "urlID": Dropzone.options.urlUpload.rowID,
              "show": "edit.url",
              "filterValue": $('#treeSearch').val(),
              "sender": "dropzone",
            });
          }
        });
      }
    };

    // Dropzone custom file
    Dropzone.options.customFileUpload = {
      uploadMultiple: false,
      maxFiles: 1,
      maxFilesize: <?=floor( getUploadLimit() / 1024 / 1024 )?>,
      addRemoveLinks: true,
      init: function () {
        this.on('success', function () {
          if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
            post('', {
              "filterValue": $('#treeSearch').val(),
              "sender": "dropzone",
              "xsrf": "<?=getXSRF()?>",
              "action": "upload.custom.file",
            });
          }
        });
      }
    };

    // CodeMirror
    if (document.getElementById('textarea_text')) {
      var editor = CodeMirror.fromTextArea(document.getElementById('textarea_text'), {
        mode: "<?=$documentMimeType?>",
        viewportMargin: Infinity,
        lineNumbers: true,
        lineWrapping: true,
        smartIndent: true,
        matchBrackets: true,
        styleActiveLine: true,
        //matchTags: {bothTags: true},
        extraKeys: {
          "Ctrl-S": function (instance) {
            ajaxSaveFile($('#textarea_text').closest('form').attr('id'));
          },
        },
      });
      editor.setSize("100%", "100%");
    }
    <?php } ?>

    $('.codemirror-editor').each(function () {
      CodeMirror.fromTextArea(this, {
        mode: $(this).data('mime'),
        viewportMargin: Infinity,
        lineNumbers: true,
        lineWrapping: true,
        smartIndent: true,
        matchBrackets: true,
        styleActiveLine: true,
        readOnly: $(this).data('readonly'),
      });
    });

    <?php if (isset( $metaData['rowid'] ) || in_array( $section, ['template'] )) { ?>
    // TinyMCE
    tinyEditor = tinymce.init({
      selector: 'textarea#textarea_html',
      //language: 'en_US',
      plugins: "advlist autoresize lists charmap print anchor textcolor visualblocks colorpicker  fullpage fullscreen code image link imagetools media searchreplace save",
      toolbar: "fullscreen fullpage | insert | undo redo |  formatselect | bold italic forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | code image link imagetools media searchreplace save",
      removed_menuitems: 'newdocument',
      save_onsavecallback: function () {
        ajaxSaveFile('formHtml');
      },
      valid_elements: "*[*]",
      valid_children: "*[*]",
      theme: "modern",
      cleanup_on_startup: false,
      trim_span_elements: false,
      verify_html: false,
      cleanup: false,
      extended_valid_elements: "*[*]",
      custom_elements: "*[*]",
      allow_conditional_comments: true,
      allow_html_in_named_anchor: true,
      allow_unsafe_link_target: true,
      convert_fonts_to_spans: false,
      branding: false,
      height: 900,
      autoresize_on_init: true,
      relative_urls: true,
      allow_script_urls: true,
      convert_urls: false,
      remove_script_host: true,
      anchor_bottom: false,
      anchor_top: false,
      forced_root_block: false,
      keep_styles: true,
      remove_trailing_brs: false,
      document_base_url: "<?=htmlspecialchars( $documentBaseUrl )?>",
      entity_encoding: "named",
    });
    <?php } ?>

    <?php if (in_array( $section, ['page'] )) { ?>
    tinymce.init({
      selector: 'textarea.tinymce-control',
      menubar: false,
      valid_elements: "*[*]",
      valid_children: "*[*]",
      theme: "modern",
      cleanup_on_startup: false,
      trim_span_elements: false,
      verify_html: false,
      cleanup: false,
      extended_valid_elements: "*[*]",
      custom_elements: "*[*]",
      allow_conditional_comments: true,
      allow_html_in_named_anchor: true,
      allow_unsafe_link_target: true,
      convert_fonts_to_spans: false,
      branding: false,
      autoresize_on_init: true,
      relative_urls: true,
      allow_script_urls: true,
      convert_urls: false,
      remove_script_host: true,
      anchor_bottom: false,
      anchor_top: false,
      forced_root_block: false,
      keep_styles: true,
      remove_trailing_brs: false,
      entity_encoding: "named",
      autoresize_bottom_margin: 30,
      plugins: [
        'advlist autolink lists link image charmap print preview anchor textcolor',
        'autoresize searchreplace visualblocks code fullscreen',
        'insertdatetime media image table contextmenu paste code wordcount'
      ],
      toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | image media | removeformat | code',
    });
    <?php  } ?>
  </script>
  </body>
  </html>
<?php
// Kept for translation backward compatibility
/* Additional strings included for translations
L( 'Count' );
L( 'Could not connect to the update server.' );
L( 'Read instructions' );
L( 'Import websites created by Archivarix.' );
L( 'Roll back to a breakpoint' );
L( 'Choose the breakpoint' );
L( 'Purge history up to' );
L( 'Purge selected' );
L( 'Run import' );
L( 'Sending with AJAX failed. Sending data the regular way' );
*/
?>