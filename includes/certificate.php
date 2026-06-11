<?php declare(strict_types=1);

/**
 * HTML-шаблон и генерация PDF-сертификата (ФинКид, альбомный A4).
 */
function certificateImageTag(string $relativePath, string $class, string $fallbackHtml = ''): string
{
    $path = dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
    if (!is_file($path)) {
        return $fallbackHtml;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg'], true)) {
        $mime = 'image/jpeg';
    } elseif ($ext === 'webp') {
        $mime = 'image/webp';
    } else {
        $mime = 'image/png';
    }
    $data = base64_encode((string) file_get_contents($path));
    $classE = htmlspecialchars($class, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return '<img src="data:' . $mime . ';base64,' . $data . '" alt="" class="' . $classE . '">';
}

function buildCertificateHtml(string $userName, string $courseTitle, string $date, string $code): string
{
  $name = htmlspecialchars($userName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $course = htmlspecialchars($courseTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $dateE = htmlspecialchars($date, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $codeE = htmlspecialchars($code, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $langAttr = currentLang() === 'kz' ? 'kk' : currentLang();
  $t = static fn(string $key): string => htmlspecialchars(__($key), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $mascotImg = certificateImageTag(
      'img/certificate/finya.png',
      'mascot',
      '<div class="mascot-ph">🦊</div>'
  );
  $signImg = certificateImageTag('img/certificate/signature.png', 'sign-img', '');

  return <<<HTML
<!DOCTYPE html>
<html lang="{$langAttr}">
<head>
<meta charset="UTF-8">
<style>
  @page {
    margin: 0;
    size: 842pt 595pt;
  }
  * {
    margin: 0;
    padding: 0;
  }
  html, body {
    width: 842pt;
    height: 595pt;
    margin: 0;
    padding: 0;
    background: #2d7dd2;
    font-family: "DejaVu Sans", sans-serif;
    color: #1a2a3a;
  }
  table {
    border-collapse: collapse;
    border-spacing: 0;
  }
  .cert {
    width: 842pt;
    height: 595pt;
    background: #2d7dd2;
  }
  .cert-pad {
    padding: 3pt;
    vertical-align: top;
  }
  .cert-sheet {
    width: 836pt;
    height: 589pt;
    background: #ffffff;
  }
  .cert-accent {
    height: 12pt;
    background: #0ab5a6;
  }
  .inner {
    padding: 14pt 22pt 16pt;
  }
  .mascot-ph {
    font-size: 48pt;
    text-align: center;
    line-height: 1;
  }
  .head {
    text-align: center;
    margin-bottom: 8pt;
  }
  .logo {
    font-size: 22pt;
    font-weight: bold;
    line-height: 1.1;
  }
  .logo-fin { color: #16a34a; }
  .logo-kid { color: #ea580c; }
  .logo-sub {
    font-size: 8pt;
    font-weight: bold;
    color: #2d7dd2;
    letter-spacing: 1.5pt;
    margin-top: 3pt;
  }
  .title {
    font-size: 24pt;
    font-weight: bold;
    color: #2d7dd2;
    letter-spacing: 1.5pt;
    margin-top: 5pt;
  }
  .ribbon {
    display: inline-block;
    margin-top: 5pt;
    padding: 4pt 14pt;
    background: #22c55e;
    color: #ffffff;
    font-size: 9pt;
    font-weight: bold;
    text-align: center;
  }
  .main {
    width: 100%;
    table-layout: fixed;
    margin-top: 6pt;
  }
  .main td {
    vertical-align: top;
    padding: 0;
  }
  .col-img {
    width: 230pt;
    text-align: center;
    padding-right: 8pt;
  }
  .mascot {
    width: 215pt;
    height: auto;
  }
  .col-text {
    width: auto;
    text-align: left;
    vertical-align: top;
    padding-top: 4pt;
  }
  .intro {
    font-size: 10pt;
    color: #475569;
    margin-bottom: 6pt;
  }
  .name {
    display: inline-block;
    font-size: 21pt;
    font-weight: bold;
    color: #2d7dd2;
    line-height: 1.2;
    border-bottom: 2pt solid #0ab5a6;
    padding-bottom: 3pt;
    margin-bottom: 6pt;
  }
  .done {
    font-size: 10pt;
    color: #475569;
    margin-bottom: 5pt;
  }
  .course {
    font-size: 13pt;
    font-weight: bold;
    color: #15803d;
    line-height: 1.3;
    margin-bottom: 5pt;
  }
  .desc {
    font-size: 9pt;
    color: #64748b;
    line-height: 1.45;
  }
  .foot {
    width: 100%;
    table-layout: fixed;
    margin-top: 10pt;
  }
  .foot td {
    vertical-align: bottom;
    font-size: 8pt;
    color: #475569;
    line-height: 1.4;
    padding-top: 4pt;
  }
  .foot-left {
    width: 33%;
    text-align: left;
  }
  .foot-center {
    width: 34%;
    text-align: center;
  }
  .foot-right {
    width: 33%;
    text-align: right;
  }
  .meta strong { color: #1a2a3a; }
  .sign-img {
    max-height: 42pt;
    width: auto;
    margin: 0 auto 4pt;
  }
  .sign-line {
    width: 120pt;
    border-bottom: 2pt solid #2d7dd2;
    margin: 0 auto 3pt;
    height: 0;
  }
  .sign-label {
    font-size: 8pt;
    color: #64748b;
    line-height: 1.3;
    text-align: center;
  }
  .well {
    display: inline-block;
    background: #fef9c3;
    border: 2pt solid #fde047;
    padding: 5pt 8pt;
    text-align: left;
    max-width: 200pt;
  }
  .well b {
    display: block;
    font-size: 9pt;
    color: #b45309;
    margin-bottom: 3pt;
  }
  .well span {
    font-size: 7pt;
    color: #92400e;
    line-height: 1.35;
  }
</style>
</head>
<body>
  <table class="cert" width="842" height="595" cellpadding="0" cellspacing="0">
    <tr>
      <td class="cert-pad">
        <table class="cert-sheet" width="836" height="589" cellpadding="0" cellspacing="0">
          <tr><td class="cert-accent"></td></tr>
          <tr>
            <td class="inner">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td class="head">
                    <div class="logo"><span class="logo-fin">{$t('cert.brand_fin')}</span><span class="logo-kid">{$t('cert.brand_kid')}</span></div>
                    <div class="logo-sub">{$t('cert.brand_sub')}</div>
                    <div class="title">{$t('cert.title')}</div>
                    <div class="ribbon">{$t('cert.ribbon')}</div>
                  </td>
                </tr>
              </table>
              <table class="main" cellpadding="0" cellspacing="0">
                <tr>
                  <td class="col-img" width="230">
                    {$mascotImg}
                  </td>
                  <td class="col-text">
                    <div class="intro">{$t('cert.intro')}</div>
                    <div class="name">{$name}</div>
                    <div class="done">{$t('cert.completed')}</div>
                    <div class="course">«{$course}»</div>
                    <div class="desc">{$t('cert.desc')}</div>
                  </td>
                </tr>
              </table>
              <table class="foot" cellpadding="0" cellspacing="0">
                <tr>
                  <td class="foot-left" width="33%">
                    <div class="meta">{$t('cert.date_label')}<br><strong>{$dateE}</strong></div>
                    <div class="meta" style="margin-top:6pt;">{$t('cert.id_label')}<br><strong>{$codeE}</strong></div>
                  </td>
                  <td class="foot-center" width="34%">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td align="center">
                          {$signImg}
                          <div class="sign-line"></div>
                          <div class="sign-label">{$t('cert.sign_label')}</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                  <td class="foot-right" width="33%" align="right">
                    <div class="well">
                      <b>{$t('cert.well_done_title')}</b>
                      <span>{$t('cert.well_done_text')}</span>
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

function generateCertificatePdf(string $html, string $outputPath): bool
{
  $autoload = dirname(__DIR__) . '/vendor/autoload.php';
  if (!is_file($autoload)) {
    return false;
  }
  require_once $autoload;

  try {
    $html = preg_replace('/>\s+</', '><', $html);

    $options = new Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('chroot', dirname(__DIR__));
    $options->set('defaultPaperSize', 'A4');
    $options->set('defaultPaperOrientation', 'landscape');

    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->setPaper([0, 0, 842, 595]);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->render();

    $pdf = $dompdf->output();
    if ($pdf === '' || $pdf === null) {
      return false;
    }

    $dir = dirname($outputPath);
    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }

    return file_put_contents($outputPath, $pdf) !== false;
  } catch (Throwable $e) {
    if (function_exists('mailLog')) {
      mailLog('certificate PDF error: ' . $e->getMessage());
    }
    error_log('certificate PDF error: ' . $e->getMessage());

    return false;
  }
}