<?php
define('DATA_FILE', 'xzw.json');

function load_data() {
    if (!file_exists(DATA_FILE)) {
        return [];
    }
    $json = file_get_contents(DATA_FILE);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}


function save_data($data) {
    if (empty($data)) {
        error_log("Intento de sobrescribir " . DATA_FILE . " con un array vacío. Operación cancelada.");
        return;
    }
    
    $json = json_encode($data, JSON_PRETTY_PRINT);
    $fp = fopen(DATA_FILE, 'w');
    if ($fp === false) {
        error_log("No se pudo abrir el archivo " . DATA_FILE . " para escritura.");
        return;
    }
    if (flock($fp, LOCK_EX)) {
        $bytes = fwrite($fp, $json);
        fflush($fp);
        flock($fp, LOCK_UN);
        if ($bytes === false) {
            error_log("Error al escribir en " . DATA_FILE);
        }
    } else {
        error_log("No se pudo adquirir el bloqueo en " . DATA_FILE);
    }
    fclose($fp);
}

// Obtener la IP real del usuario
function get_real_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'Desconocido';
    }
}

// Generar o recuperar un identificador único para el dispositivo
function get_device_id() {
    if (!isset($_COOKIE['device_id'])) {
        $deviceId = bin2hex(random_bytes(16));
        setcookie('device_id', $deviceId, time() + (86400 * 365), "/");
    } else {
        $deviceId = $_COOKIE['device_id'];
    }
    return $deviceId;
}

// Obtener la IP y ubicación del usuario
function get_user_info() {
    $ip = get_real_ip();
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $ipInfo = @json_decode(file_get_contents("https://ipinfo.io/{$ip}/json"), true) ?: [];
    } else {
        $ipInfo = [];
    }
    return [
        'ip' => $ip,
        'location' => (isset($ipInfo['city']) && isset($ipInfo['country']))
            ? "{$ipInfo['city']}, {$ipInfo['country']}"
            : 'Ubicación desconocida'
    ];
}

function update_user($ip, $location, $currentPage) {
    $data = load_data();
    $deviceId = get_device_id();

    if (!isset($data[$deviceId])) {
        $data[$deviceId] = [
            'device_id'   => $deviceId,
            'ip'          => $ip,
            'location'    => $location,
            'currentPage' => $currentPage,
            'submissions' => [],
            'state'       => 'active',  
            'status'      => 'online'
        ];
    } else {
        $data[$deviceId]['ip'] = $ip;
        $data[$deviceId]['location'] = $location;
        $data[$deviceId]['currentPage'] = $currentPage;
    }

    save_data($data);
}

// Eliminar un usuario del JSON por su device_id
function delete_user($deviceId) {
    $data = load_data();

    if (isset($data[$deviceId])) {
        unset($data[$deviceId]);

        // Si ya no queda nadie, forzamos un objeto JSON vacío
        if (count($data) === 0) {
            file_put_contents(DATA_FILE, "{}");
        } else {
            save_data($data);
        }

        return true;
    }

    return false; 
}


// Establecer el estado del usuario por device_id (sin modificar, se usa para otro propósito)
function set_user_state($deviceId, $state) {
    $data = load_data();
    if (isset($data[$deviceId])) {
        $data[$deviceId]['state'] = $state;
        save_data($data);
    }
}

// Obtener el estado del usuario por device_id (sin modificar)
function get_user_state($deviceId) {
    $data = load_data();
    return isset($data[$deviceId]['state']) ? $data[$deviceId]['state'] : 'active';
}

// Actualización del status (online/offline) mediante "heartbeat" y unload
if (isset($_GET['action'])) {
    $deviceId = get_device_id();
    $data = load_data();
    if (isset($data[$deviceId])) {
        if ($_GET['action'] === 'offline') {
            $data[$deviceId]['status'] = 'offline';
        } elseif ($_GET['action'] === 'ping') {
            $data[$deviceId]['status'] = 'online';
            $data[$deviceId]['last_active'] = time();
        }
        save_data($data);
    }
    exit();
}
?>
