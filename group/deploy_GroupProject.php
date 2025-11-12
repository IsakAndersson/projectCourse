<?php
// ---- config ----
$secret = 'ThereOnceWasAShipThatPutToSeaAndTheNameOfTheShipWasTheBulldogOfBristolASailorWentToSeaHisNameWasFletcherAndHeToldTheCaptainHeCouldFettleTheYardarm'; // your webhook secret
$repoRoot = '/home/silversu/groupProject';
$branch = 'main'; // change if your default branch differs

// ---- verify GitHub signature ----
$payload = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$calc = 'sha256='.hash_hmac('sha256', $payload, $secret);
if (!hash_equals($calc, $sig)) {
  http_response_code(403);
  echo "Invalid signature";
  exit;
}

// ---- pull latest + deploy via cPanel UAPI ----
// NOTE: On some hosts you may need /usr/local/cpanel/bin/uapi instead of /usr/bin/uapi
$uapi = '/usr/bin/uapi';

// 1) Pull from GitHub into the cPanel-managed repo
$cmd1 = escapeshellcmd("$uapi VersionControl update repository_root=$repoRoot branch=$branch");
$out1 = shell_exec("$cmd1 2>&1");

// 2) Run the .cpanel.yml deployment tasks
$cmd2 = escapeshellcmd("$uapi VersionControlDeployment create repository_root=$repoRoot");
$out2 = shell_exec("$cmd2 2>&1");

// optional: log somewhere private
file_put_contents($repoRoot.'/.webhook.log',
  date('c')."\nCMD1:$cmd1\n$out1\nCMD2:$cmd2\n$out2\n\n", FILE_APPEND);

echo "OK";
