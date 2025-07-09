<?php
if (php_sapi_name() !== 'cli') {
    echo "本脚本只允许在命令行（CLI）模式下运行！\n";
    exit(1);
}

if ($argc < 3) {
    echo "Usage: php CF_BanCheck.php <Email> <KEY>\n";
    exit(1);
}

$email = $argv[1];
$key = $argv[2];

$headers = [
    "X-Auth-Email: $email",
    "X-Auth-Key: $key",
    "Content-Type: application/json"
];

// 检查dig命令是否存在
function check_dig_exists() {
    $which = (stripos(PHP_OS, 'WIN') === 0) ? 'where' : 'which';
    $result = shell_exec("$which dig");
    return !empty($result);
}

if (!check_dig_exists()) {
    echo "未检测到 dig 命令，请先安装。
";
    echo "常见系统安装命令：\n";
    echo "  CentOS/REHL/AlmaLinux: sudo yum install bind-utils\n";
    echo "  Debian/Ubuntu:         sudo apt-get install dnsutils\n";
    echo "  MacOS (Homebrew):      brew install bind\n";
    exit(1);
}

function dig_a_records($domain, $dns_server = '8.8.8.8') {
    $cmd = "dig +short @$dns_server $domain A";
    $output = shell_exec($cmd);
    $ips = array_filter(array_map('trim', explode("\n", $output)), function($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    });
    return array_values($ips);
}

// 获取账户ID
$ch = curl_init('https://api.cloudflare.com/client/v4/accounts');
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$accounts = json_decode($response, true);
if (!isset($accounts['success']) || !$accounts['success'] || empty($accounts['result'])) {
    echo "无法获取账户信息\n";
    exit(1);
}
$account_id = $accounts['result'][0]['id'];
echo "Process Account ID: $account_id\n";

// 获取Zone列表
$ch = curl_init("https://api.cloudflare.com/client/v4/zones?account.id=$account_id");
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$zones = json_decode($response, true);

printf("%-36s %-30s %-16s %-8s\n", 'ZoneID', 'Domain', 'Target IP', 'IP Status');

if (isset($zones['success']) && $zones['success'] === true) {
    foreach ($zones['result'] as $zone) {
        $zone_id = $zone['id'];
        $zone_name = $zone['name'];
        $domain_to_check = $zone_name;
        $ips = dig_a_records($domain_to_check);
        if (empty($ips)) {
            $domain_to_check = 'www.' . $zone_name;
            $ips = dig_a_records($domain_to_check);
        }
        if (empty($ips)) {
            $dns_url = "https://api.cloudflare.com/client/v4/zones/$zone_id/dns_records?type=A";
            $ch = curl_init($dns_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $dns_result = json_decode($response, true);
            if (isset($dns_result['success']) && $dns_result['success'] === true && count($dns_result['result']) > 0) {
                $first_sub = $dns_result['result'][0]['name'];
                $domain_to_check = $first_sub;
                $ips = dig_a_records($domain_to_check);
            }
        }
        if (!empty($ips)) {
            foreach ($ips as $ip) {
                $ip_status = (preg_match('/\\.1$/', $ip)) ? '已屏蔽' : '正常';
                if ($ip_status === '正常') {
                    $ip_status_colored = "\033[32m$ip_status\033[0m";
                } else if ($ip_status === '已屏蔽') {
                    $ip_status_colored = "\033[31m$ip_status\033[0m";
                } else {
                    $ip_status_colored = $ip_status;
                }
                printf("%-36s %-30s %-16s %-8s\n", $zone_id, $domain_to_check, $ip, $ip_status_colored);
            }
        } else {
            printf("%-36s %-30s %-16s %-8s\n", $zone_id, $domain_to_check, '-', '无A记录');
        }
    }
} else {
    echo "无法获取域名信息\n";
} 