<?php

function getHttpStatus($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $http_status;
}

function checkSSL($url) {
    $url_parts = parse_url($url);
    $host = $url_parts['host'] ?? $url;

    $ctx = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
    $client = @stream_socket_client("ssl://$host:443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
    
    if ($client) {
        $context = stream_context_get_params($client);
        $cert = openssl_x509_parse($context["options"]["ssl"]["peer_certificate"]);
        return ($cert) ? "Valid SSL" : "Invalid SSL";
    } else {
        return "No SSL or Expired";
    }
}

function getHostProvider($url) {
    $host = parse_url($url, PHP_URL_HOST) ?? $url;
    $ip = gethostbyname($host);
    
    if ($ip == $host) return "Unknown Hosting";
    
    $details = @file_get_contents("https://ipinfo.io/$ip/org");
    return $details ? trim($details) : "Unknown Hosting";
}

function measureLoadTime($url) {
    $start = microtime(true);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_exec($ch);

    $end = microtime(true);
    curl_close($ch);

    return round(($end - $start) * 1000, 2) . " ms";
}

$urllist = explode("\n", trim($_POST["urls"] ?? ''));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Status Results</title>

    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        /* Reset styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        /* Full-screen dark background */
        body {
            background-color: #121212;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            padding: 20px;
        }

        /* Table container */
        .table-container {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0px 4px 15px rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 900px;
            text-align: center;
            overflow-x: auto;
        }

        /* Heading */
        h2 {
            margin-bottom: 15px;
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #252525;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #444;
        }

        th {
            background: #333;
            font-weight: 600;
            color: #4caf50;
        }

        td {
            color: #ffffff;
        }

        tr:hover {
            background: #2d2d2d;
        }

        /* Back Button */
        .back-button {
            margin-top: 20px;
            display: inline-block;
            padding: 12px 20px;
            background: #4caf50;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }

        .back-button:hover {
            background: #45a049;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .table-container {
                padding: 15px;
            }
            h2 {
                font-size: 20px;
            }
            th, td {
                padding: 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

    <div class="table-container">
        <h2>Website Status Results</h2>
        <table>
            <tr>
                <th>Website</th>
                <th>Status</th>
                <th>HTTP Code</th>
                <th>SSL Status</th>
                <th>Hosting Provider</th>
                <th>Load Time</th>
            </tr>

            <?php foreach ($urllist as $url): ?>
                <?php 
                    $url = trim($url);
                    if (!empty($url)): 
                        $status = getHttpStatus($url) ? "🟢 Online" : "🔴 Offline";
                        $http_code = getHttpStatus($url);
                        $ssl_status = checkSSL($url);
                        $hosting_provider = getHostProvider($url);
                        $load_time = measureLoadTime($url);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($url); ?></td>
                    <td><?php echo $status; ?></td>
                    <td><?php echo $http_code; ?></td>
                    <td><?php echo $ssl_status; ?></td>
                    <td><?php echo $hosting_provider; ?></td>
                    <td><?php echo $load_time; ?></td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>

        <a href="index.html" class="back-button">Check Another</a>
    </div>

</body>
</html>
