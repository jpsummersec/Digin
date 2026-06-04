<?php 
include __DIR__ . '/include-loginrequired.php';
include __DIR__ . '/include-dbhandler.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
	session_unset();
	session_destroy();

	header('Location: signin.php');
	exit();
}

// Load the Spotify client ID from config.php.
$config = require __DIR__ . '/config.php';
$client_id = $config['SPOTIFY_CLIENT_ID'] ?? null;

if (!$client_id) {
    die('Error: SPOTIFY_CLIENT_ID not found in config.php');
}

$redirect_uri = $config['SPOTIFY_REDIRECT_URI'];

// Ensure the page is loaded from the same host as the Spotify redirect URI.
$expected_host = parse_url($redirect_uri, PHP_URL_HOST);
$expected_port = parse_url($redirect_uri, PHP_URL_PORT);
$expected_host_with_port = $expected_host . ($expected_port ? ':' . $expected_port : '');
$current_host = $_SERVER['HTTP_HOST'] ?? '';
if ($current_host !== $expected_host_with_port) {
    $scheme = parse_url($redirect_uri, PHP_URL_SCHEME) ?: 'http';
    $redirect_url = $scheme . '://' . $expected_host_with_port . $_SERVER['REQUEST_URI'];
    header('Location: ' . $redirect_url);
    exit();
}

// Generate a random state token and keep it in the session.
// Spotify will send it back so we can verify the callback.
$state = bin2hex(random_bytes(16));
$_SESSION['spotify_state'] = $state;

// These scopes let us read playback state and start playback.
$scope = "user-read-playback-state user-modify-playback-state";

$params = http_build_query([
    "response_type" => "code",
    "client_id" => $client_id,
    "scope" => $scope,
    "redirect_uri" => $redirect_uri,
    "state" => $state,
    "show_dialog" => "true" // Keep the login prompt visible for fresh auth.
]);

$authorize_url = "https://accounts.spotify.com/authorize?$params";

if (isset($_GET['action']) && $_GET['action'] === 'login') {
    header("Location: $authorize_url");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$user = null;
$achievements = [];

if (isset($dbHandler)) {
	// get user info based on user ID
	try { 
		$statement = $dbHandler->prepare('SELECT * FROM `user` WHERE `user_id` = :userId');
		$statement->bindValue(':userId', $userId, PDO::PARAM_INT); 
		$statement->execute();
		$user = $statement->fetch(PDO::FETCH_ASSOC);  
		$statement->closeCursor(); 
	}
	catch(PDOException $exception) {
		die('Select error: ' . $exception->getMessage());
	}

	// get user achievements based on user ID
	try { 
		$statement = $dbHandler->prepare('
			SELECT a.`achievement_id`, a.`achievement_name`, a.`path_to_icon`
			FROM `user_achievement` ua
			INNER JOIN `achievement` a
				ON a.`achievement_id` = ua.`achievement_id`
			WHERE ua.`user_id` = :userId
			ORDER BY a.`achievement_id`
		');
		$statement->bindValue(':userId', $userId, PDO::PARAM_INT); 
		$statement->execute();
		$achievements = $statement->fetchAll(PDO::FETCH_ASSOC);  
		$statement->closeCursor(); 
	}
	catch(PDOException $exception) {
		die('Select error: ' . $exception->getMessage());
	}
}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Document</title>
		<link rel="stylesheet" href="../css/root.css">
		<link rel="stylesheet" href="../css/profile-page.css">
	</head>
	<body>
		<h1 class="page-title">Your Profile</h1>
		<div class="profile-banner">
			<div class="profile-picture">
				<img src="<?= '../' . htmlspecialchars($user['path_to_icon']) ?>" alt="Profile Picture">
			</div>
			<h1><?= htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']) ?></h1>
			<h2><?= htmlspecialchars($user['email_address']) ?></h2>

			<?php
			$currentXp = $user['xp'];
			$currentLevel = $user['level'];
			$xpToNextLevel = 100 * $currentLevel * 2;

			$progressPercent = ($currentXp / $xpToNextLevel) * 100;
			$progressPercent = max(0, min(100, $progressPercent));
			?>

			<div class="xp-bar">
				<div class="xp-bar-fill" style="width: <?= $progressPercent ?>%;"></div>
			</div>

			<h3 class="xp-progress">
				<?= $currentXp ?> / <?= $xpToNextLevel ?> XP
			</h3>

			<h3 class="level-counter">Level <?= $currentLevel ?></h3>
		</div>

		<div class="achievements">
			<h2>Achievements</h2>

			<?php if (empty($achievements)): ?>
				<p class="empty-achievements">No achievements yet.</p>
			<?php else: ?>
				<div class="achievement-list">
					<?php foreach ($achievements as $achievement): ?>
						<div class="achievement-item">
							<img src="<?= htmlspecialchars($achievement['path_to_icon']) ?>" alt="<?= htmlspecialchars($achievement['achievement_name']) ?>">
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<hr>
		</div>

		

		<div class="favourite-dishes">
			<h2>Favorite Dishes</h2>
			<hr>
		</div>

		<div class="spotify">
			<hr>
			<img src="../images/profile-page/spotify-logo.svg" alt="spotify-logo">
			<a href="?action=login" class="button">Connect to Spotify</a>
			<p>Cook with Spotify. Taste the vibe.</p>
		</div>

		<div class="logout">
			<form action="profile-page.php?action=logout" method="post">
				<button type="submit">Logout</button>
			</form>
		</div>

	</body>

	
</html>
