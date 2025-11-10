<?php
// Simple dynamic link target for Sign in (no database, no session).
// If you already have a real login route, replace $loginUrl accordingly.
$loginUrl = '/auth/login'; // change to your real route if needed
// If you want this file to render a minimal login placeholder instead of redirecting,
// comment the header() line and keep the HTML below.
header('Location: ' . $loginUrl);
exit;
?>


<!-- Minimal fallback if redirect is disabled
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Task‑o‑Mania — Sign in</title>
<link rel="stylesheet" href="./style.css" />
</head>
<body>
<main class="landing">
<section class="hero">
<h1 class="hero__title">Sign in</h1>
<p class="hero__subtitle">This is a placeholder. Wire your auth route and form here.</p>
<a class="btn btn--primary" href="/">Back to Landing</a>
</section>
</main>
</body>
</html>
-->