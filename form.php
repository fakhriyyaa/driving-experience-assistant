<?php
require "includes/pdo_connect.local.php";

define('SECRET_KEY', 'HWPROJECT2025');

$errors = [];

/* ---------- DEFAULT VALUES ---------- */
$experience = [
    'date' => date('Y-m-d'),
    'startingTime' => '12:00',
    'endingTime' => '15:00',
    'kilometers' => '',
    'weather' => '',
    'visibility' => '',
    'traffic' => '',
    'roadCondition' => ''
];

/* ---------- FORM SUBMISSION ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $requiredFields = [
        'date','startingTime','endingTime','kilometers',
        'weather','visibility','traffic','roadCondition'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "All fields except maneuvers must be filled.";
            break;
        }
    }

    if (empty($errors)) {
        if (strtotime($_POST['endingTime']) <= strtotime($_POST['startingTime'])) {
            $errors[] = "Ending time must be later than starting time.";
        }
    }

    if (empty($errors) && ($_POST['kilometers'] <= 0)) {
        $errors[] = "Distance must be positive.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO experiences
            (date, startingTime, endingTime, kilometers, weather, visibility, traffic, roadCondition)
            VALUES (?,?,?,?,?,?,?,?)
        ");

        $stmt->execute([
            $_POST['date'], $_POST['startingTime'], $_POST['endingTime'],
            $_POST['kilometers'], $_POST['weather'], $_POST['visibility'],
            $_POST['traffic'], $_POST['roadCondition']
        ]);

        $id = $pdo->lastInsertId();

        if (!empty($_POST['maneuvers'])) {
            $stmt = $pdo->prepare(
                "INSERT INTO experience_maneuver (experience_id, maneuver_id) VALUES (?,?)"
            );
            foreach ($_POST['maneuvers'] as $m) {
                $stmt->execute([$id, $m]);
            }
        }

        header("Location: dashboard.php");
        exit;
    }

    foreach ($experience as $k => $v) {
        if (isset($_POST[$k])) $experience[$k] = $_POST[$k];
    }
}

$maneuvers = $pdo->query("SELECT id,name FROM maneuvers")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Driving Experience</title>
<link rel="stylesheet" href="css/formstyle.css">
</head>
<body>

<header>
    <h1>Add Driving Experience</h1>
</header>

<main>
<form method="post">

<?php if ($errors): ?>
<p class="error"><?= htmlspecialchars($errors[0]) ?></p>
<?php endif; ?>

<div class="form-grid">

<div class="form-group">
<label>Date *</label>
<input type="date" name="date" required value="<?= $experience['date'] ?>">
</div>

<div class="form-group">
<label>Starting Time *</label>
<input type="time" name="startingTime" required value="<?= $experience['startingTime'] ?>">
</div>

<div class="form-group">
<label>Ending Time *</label>
<input type="time" name="endingTime" required value="<?= $experience['endingTime'] ?>">
</div>

<div class="form-group">
<label>Distance (km) *</label>
<input type="number" name="kilometers" min="1" required value="<?= $experience['kilometers'] ?>">
</div>

<div class="form-group">
<label>Weather *</label>
<select name="weather" required>
<option value="">Select</option>
<option <?= $experience['weather']=='Sunny'?'selected':'' ?>>Sunny</option>
<option <?= $experience['weather']=='Rainy'?'selected':'' ?>>Rainy</option>
<option <?= $experience['weather']=='Foggy'?'selected':'' ?>>Foggy</option>
<option <?= $experience['weather']=='Snowy'?'selected':'' ?>>Snowy</option>
</select>
</div>

<div class="form-group">
<label>Visibility *</label>
<select name="visibility" required>
<option value="">Select</option>
<option <?= $experience['visibility']=='Daytime'?'selected':'' ?>>Daytime</option>
<option <?= $experience['visibility']=='Nighttime'?'selected':'' ?>>Nighttime</option>
</select>
</div>

<div class="form-group">
<label>Traffic *</label>
<select name="traffic" required>
<option value="">Select</option>
<option <?= $experience['traffic']=='Low'?'selected':'' ?>>Low</option>
<option <?= $experience['traffic']=='Medium'?'selected':'' ?>>Medium</option>
<option <?= $experience['traffic']=='Heavy'?'selected':'' ?>>Heavy</option>
</select>
</div>

<div class="form-group">
<label>Road Condition *</label>
<select name="roadCondition" required>
<option value="">Select</option>
<option <?= $experience['roadCondition']=='Dry'?'selected':'' ?>>Dry</option>
<option <?= $experience['roadCondition']=='Wet'?'selected':'' ?>>Wet</option>
<option <?= $experience['roadCondition']=='Slippery'?'selected':'' ?>>Slippery</option>
</select>
</div>

<div class="form-group maneuvers">
<label>Maneuvers (optional)</label>
<?php foreach ($maneuvers as $m): ?>
<div>
<input type="checkbox" name="maneuvers[]" value="<?= $m['id'] ?>">
<?= htmlspecialchars($m['name']) ?>
</div>
<?php endforeach; ?>
</div>

<div class="form-actions">
<button type="submit">Save Experience</button>

    <button onclick="location.href='dashboard.php'">Dashboard</button>
</div>

</div>
</form>
</main>

<footer>Driving Experience Assistant</footer>

</body>
</html>
