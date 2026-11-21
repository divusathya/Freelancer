<?php
/* Owner-only visitor stats. Open yourdomain/stats.php and log in. */
session_set_cookie_params(["httponly" => true, "samesite" => "Strict", "secure" => !empty($_SERVER["HTTPS"])]);
session_start();
$HASH = "f1207a5d951f155b5f679245ee8862a9005e52c335a178172cc5ba67f2ec3d0f"; // sha256 of your password. To change it: replace with sha256 of a new password.
header("X-Robots-Tag: noindex, nofollow");

if (isset($_GET["logout"])) { session_destroy(); setcookie("ds_owner", "", time() - 3600, "/"); header("Location: stats.php"); exit; }

$err = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["pw"])) {
  if (hash_equals($HASH, hash("sha256", (string)$_POST["pw"]))) {
    session_regenerate_id(true); $_SESSION["ok"] = 1;
    setcookie("ds_owner", "1", time() + 86400 * 365, "/", "", !empty($_SERVER["HTTPS"]), true); // stops counting your own visits
    header("Location: stats.php"); exit;
  }
  sleep(2); $err = "Wrong password.";
}
function esc($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Visitor stats</title>
<style>
body{margin:0;background:#0b0b1c;color:#e8e6ff;font:14px/1.5 system-ui,Segoe UI,Arial,sans-serif;padding:24px}
.w{max-width:1000px;margin:0 auto}h1{font-size:20px;margin:0 0 18px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:22px}
.card{background:#15152e;border:1px solid #2a2a55;border-radius:14px;padding:14px 16px}.card b{display:block;font-size:26px;color:#a99bff}.card span{font-size:11px;letter-spacing:.12em;color:#9a98c8}
h2{font-size:13px;letter-spacing:.14em;color:#9a98c8;margin:24px 0 8px}
table{width:100%;border-collapse:collapse;background:#15152e;border-radius:12px;overflow:hidden}
th,td{padding:8px 12px;text-align:left;border-bottom:1px solid #23234a;font-size:13px}th{color:#9a98c8;font-size:11px;letter-spacing:.1em}
.sc{overflow-x:auto}a{color:#8fb2ff}
form{max-width:320px;margin:15vh auto;background:#15152e;border:1px solid #2a2a55;border-radius:16px;padding:24px}
input,button{width:100%;box-sizing:border-box;padding:11px 12px;border-radius:10px;border:1px solid #2a2a55;background:#0b0b1c;color:#fff;font-size:14px}
button{margin-top:10px;background:linear-gradient(115deg,#8b7cff,#5b8def);border:0;font-weight:700;cursor:pointer}.e{color:#ff8a98;margin-top:8px}
</style></head><body>
<?php if (empty($_SESSION["ok"])): ?>
<form method="post"><h1>Owner login</h1><input type="password" name="pw" placeholder="Password" autofocus required><button>Log in</button><?php if ($err) echo "<div class=\"e\">".esc($err)."</div>"; ?></form>
<?php exit; endif;

$rows = [];
$f = __DIR__ . "/_data/visits.log";
if (is_file($f)) { foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) { $r = json_decode($l, true); if ($r) $rows[] = $r; } }
date_default_timezone_set("Asia/Dubai");
$today = strtotime("today"); $week = strtotime("-6 days", $today);
$total = count($rows); $uniq = []; $uToday = []; $vToday = 0; $vWeek = 0; $pages = []; $countries = [];
foreach ($rows as $r) {
  $k = $r["s"] ?: $r["ip"]; $uniq[$k] = 1;
  if ($r["t"] >= $today) { $vToday++; $uToday[$k] = 1; }
  if ($r["t"] >= $week) $vWeek++;
  $pages[$r["p"]] = ($pages[$r["p"]] ?? 0) + 1;
  if ($r["c"]) $countries[strtoupper($r["c"])] = ($countries[strtoupper($r["c"])] ?? 0) + 1;
}
arsort($pages); arsort($countries);
$recent = array_slice(array_reverse($rows), 0, 150);
?>
<div class="w">
<h1>Visitor stats <a href="?logout=1" style="float:right;font-size:13px">Log out</a></h1>
<div class="cards">
<div class="card"><b><?= $total ?></b><span>TOTAL VIEWS</span></div>
<div class="card"><b><?= count($uniq) ?></b><span>UNIQUE VISITORS</span></div>
<div class="card"><b><?= $vToday ?></b><span>VIEWS TODAY</span></div>
<div class="card"><b><?= count($uToday) ?></b><span>VISITORS TODAY</span></div>
<div class="card"><b><?= $vWeek ?></b><span>LAST 7 DAYS</span></div>
</div>
<h2>TOP PAGES</h2><div class="sc"><table><tr><th>PAGE</th><th>VIEWS</th></tr><?php foreach (array_slice($pages, 0, 10, true) as $p => $n) echo "<tr><td>".esc($p)."</td><td>".$n."</td></tr>"; ?></table></div>
<?php if ($countries): ?><h2>COUNTRIES</h2><div class="sc"><table><tr><th>COUNTRY</th><th>VIEWS</th></tr><?php foreach (array_slice($countries, 0, 10, true) as $c => $n) echo "<tr><td>".esc($c)."</td><td>".$n."</td></tr>"; ?></table></div><?php endif; ?>
<h2>RECENT VISITORS (LAST 150, DUBAI TIME)</h2>
<div class="sc"><table><tr><th>TIME</th><th>PAGE</th><th>IP</th><th>COUNTRY</th><th>DEVICE</th><th>BROWSER</th><th>CAME FROM</th></tr>
<?php foreach ($recent as $r) echo "<tr><td>".date("d M Y, H:i", $r["t"])."</td><td>".esc($r["p"])."</td><td>".esc($r["ip"])."</td><td>".esc($r["c"])."</td><td>".esc($r["d"])."</td><td>".esc($r["b"])."</td><td>".esc($r["r"] ?: "Direct")."</td></tr>"; ?>
</table></div>
<p style="color:#6f6da0;font-size:12px;margin-top:18px">Bots are ignored, and your own visits are not counted while you are logged in on this browser.</p>
</div></body></html>
