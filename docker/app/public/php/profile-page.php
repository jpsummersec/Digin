<?php 
include __DIR__ . '/include.php';

$userId = 1;
$user = null;

if (isset($dbHandler)) {
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
	
	var_dump($user);

	// echo '<div class="content">';
	// foreach ($results as $result) {
	// 	echo '<div class="racer">';
	// 	echo '<div class="racerImg">
	// 		<img src="img/racerImg/' . $result['racerImage'] . '">
	// 		</div>';
	// 	echo '<div class="racerDetails">
	// 			<p><strong>Racer: </strong>' . $result['racerName'] . '</p>
	// 			<p><strong>Car: </strong>' . $result['carName'] . '</p>
	// 			</div>';
	// 	echo '</div>';
	// }
	// echo '</div>';
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
			<img class="profile-picture" src="<?= htmlspecialchars($user['path_to_icon']) ?>" alt="Profile Picture">
			<h1>Name</h1>
			<h2>@username</h2>

			<?php
			// THESE VALUES NEED TO BE PULLED FROM THE DATABASE LATER
			$currentXp = 75;
			$currentLevel = 0;
			if ($currentLevel !== 0) {
				$xpToNextLevel = 100 * $currentLevel * 2;
			} else {
				$xpToNextLevel = 100;
			}
			

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

		<h2>Achievements</h2>

		<hr>
		<hr>

		<h2>Favorite Dishes</h2>

	</body>

	
</html>