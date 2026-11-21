<?php
/* Private visit logger. Called silently by a tiny script on each page. */
header("Content-Type: text/plain");
if ($_SERVER["REQUEST_METHOD"] !== "POST") { http_response_code(204); exit; }
if (!empty($_COOKIE["ds_owner"])) { http_response_code(204); exit; } // your own visits are not counted

$ua = $_SERVER["HTTP_USER_AGENT"] ?? "";
if ($ua === "" || preg_match("/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget/i", $ua)) { http_response_code(204); exit; }

$ip = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["REMOTE_ADDR"] ?? "";
// store a masked IP only (last part hidden)
if (strpos($ip, ":") !== false) { $parts = explode(":", $ip); $ip = implode(":", array_slice($parts, 0, 3)) . ":xxxx"; }
else { $parts = explode(".", $ip); if (count($parts) === 4) { $parts[3] = "xxx"; } $ip = implode(".", $parts); }

$device = preg_match("/Mobile|Android|iPhone|iPad/i", $ua) ? "Mobile" : "Desktop";
$browser = "Other";
foreach (["Edg" => "Edge", "OPR" => "Opera", "Chrome" => "Chrome", "Firefox" => "Firefox", "Safari" => "Safari"] as $k => $v) { if (stripos($ua, $k) !== false) { $browser = $v; break; } }

$row = [
  "t"  => time(),
  "ip" => $ip,
  "c"  => substr(preg_replace("/[^A-Za-z]/", "", $_SERVER["HTTP_CF_IPCOUNTRY"] ?? ""), 0, 2),
  "p"  => substr(strip_tags((string)($_POST["p"] ?? "")), 0, 120),
  "r"  => substr(strip_tags((string)($_POST["r"] ?? "")), 0, 160),
  "s"  => substr(preg_replace("/[^a-z0-9]/i", "", (string)($_POST["s"] ?? "")), 0, 40),
  "d"  => $device,
  "b"  => $browser,
];
@file_put_contents(__DIR__ . "/_data/visits.log", json_encode($row, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
http_response_code(204);
