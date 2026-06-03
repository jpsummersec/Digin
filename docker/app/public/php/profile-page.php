<?php 
include __DIR__ . '/include.php';

$userId = 1;
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

		


	</body>

	
</html>
