<?php
session_start();
require "includes/pdo_connect.php";

define('SECRET_KEY', 'HWPROJECT2025');
$goal = 3000;

/* ---------- HANDLE APPLY / CLEAR ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply'])) {
        $_SESSION['start_date'] = $_POST['start_date'] ?: null;
        $_SESSION['end_date']   = $_POST['end_date'] ?: null;
    }

    if (isset($_POST['clear'])) {
        unset($_SESSION['start_date'], $_SESSION['end_date']);
    }
}

$startDate = $_SESSION['start_date'] ?? null;
$endDate   = $_SESSION['end_date'] ?? null;

/* Filter is active ONLY if both dates exist */
$hasFilter = (!empty($startDate) && !empty($endDate));

/* Shared helpers to build WHERE clauses safely */
function whereDateClause($hasFilter, $dateColumn = 'date') {
    return $hasFilter ? " AND $dateColumn BETWEEN :start AND :end " : "";
}

function bindDateParams(&$params, $hasFilter, $startDate, $endDate) {
    if ($hasFilter) {
        $params[':start'] = $startDate;
        $params[':end']   = $endDate;
    }
}

/* ---------- TABLE DATA ---------- */
$params = [];
bindDateParams($params, $hasFilter, $startDate, $endDate);

$sql = "
    SELECT e.*, GROUP_CONCAT(m.name SEPARATOR ', ') AS maneuvers
    FROM experiences e
    LEFT JOIN experience_maneuver em ON e.id = em.experience_id
    LEFT JOIN maneuvers m ON em.maneuver_id = m.id
    WHERE 1=1
";
if ($hasFilter) {
    $sql .= " AND e.date BETWEEN :start AND :end ";
}
$sql .= " GROUP BY e.id ORDER BY e.date DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- TOTAL KM ---------- */
$paramsKm = [];
bindDateParams($paramsKm, $hasFilter, $startDate, $endDate);

$kmSql = "SELECT SUM(kilometers) FROM experiences WHERE 1=1";
if ($hasFilter) {
    $kmSql .= " AND date BETWEEN :start AND :end ";
}
$stmt = $pdo->prepare($kmSql);
$stmt->execute($paramsKm);
$totalKm = (float)($stmt->fetchColumn() ?? 0);

$percentage = ($goal > 0) ? min(($totalKm / $goal) * 100, 100) : 0;

/* ---------- CHART QUERIES (FILTERED WHEN RANGE IS ACTIVE) ---------- */
function chartData($pdo, $sql, $params) {
    $s = $pdo->prepare($sql);
    $s->execute($params);
    return $s->fetchAll(PDO::FETCH_ASSOC);
}

/* For charts we also exclude NULL/empty labels so Chart.js never disappears */
$paramsCharts = [];
bindDateParams($paramsCharts, $hasFilter, $startDate, $endDate);

$weatherData = chartData($pdo,
    "SELECT weather AS label, COUNT(*) AS val
     FROM experiences
     WHERE weather IS NOT NULL AND weather != ''"
     . whereDateClause($hasFilter, 'date') .
    " GROUP BY weather",
    $paramsCharts
);

$roadData = chartData($pdo,
    "SELECT roadCondition AS label, COUNT(*) AS val
     FROM experiences
     WHERE roadCondition IS NOT NULL AND roadCondition != ''"
     . whereDateClause($hasFilter, 'date') .
    " GROUP BY roadCondition",
    $paramsCharts
);

$visibilityData = chartData($pdo,
    "SELECT visibility AS label, COUNT(*) AS val
     FROM experiences
     WHERE visibility IS NOT NULL AND visibility != ''"
     . whereDateClause($hasFilter, 'date') .
    " GROUP BY visibility",
    $paramsCharts
);

$trafficData = chartData($pdo,
    "SELECT traffic AS label, COUNT(*) AS val
     FROM experiences
     WHERE traffic IS NOT NULL AND traffic != ''"
     . whereDateClause($hasFilter, 'date') .
    " GROUP BY traffic",
    $paramsCharts
);

/* Maneuvers chart must filter by e.date when filter is active */
$paramsMan = [];
bindDateParams($paramsMan, $hasFilter, $startDate, $endDate);

$maneuverSql = "
    SELECT m.name AS label, COUNT(*) AS val
    FROM experience_maneuver em
    JOIN maneuvers m ON em.maneuver_id = m.id
    JOIN experiences e ON e.id = em.experience_id
    WHERE 1=1
";
if ($hasFilter) {
    $maneuverSql .= " AND e.date BETWEEN :start AND :end ";
}
$maneuverSql .= " GROUP BY m.name";

$maneuverData = chartData($pdo, $maneuverSql, $paramsMan);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Driving Experience Dashboard</title>
<link rel="stylesheet" href="css/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header>
    <h1>Driving Experience Dashboard</h1>
</header>

<div class="top-actions">
    <button onclick="location.href='form.php'">+ Add</button>
    <button onclick="location.href='index.php'">Homepage</button>
</div>

<div class="goal-box">
    <strong>Total:</strong> <?= $totalKm ?> km / <?= $goal ?> km
    (<?= number_format($percentage, 1) ?>%)
    <div class="progress-bar">
        <div class="progress" style="width:<?= $percentage ?>%"></div>
    </div>
</div>

<form method="post" class="filter">
    <label>From:</label>
    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate ?? '') ?>">
    <label>To:</label>
    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate ?? '') ?>">

    <button type="submit" name="apply">Apply</button>
    <button type="submit" name="clear">Clear</button>
</form>

<div class="table-wrapper">
<table>
<thead>
<tr>
    <th>Date</th>
    <th>Start</th>
    <th>End</th>
    <th>KM</th>
    <th>Weather</th>
    <th>Visibility</th>
    <th>Traffic</th>
    <th>Road</th>
    <th>Maneuvers</th>
    <th>Edit</th>
    <th>Delete</th>
</tr>
</thead>
<tbody>
<?php foreach ($experiences as $e):
    $code = base64_encode($e['id'].'|'.SECRET_KEY); ?>
<tr>
    <td><?= htmlspecialchars($e['date']) ?></td>
    <td><?= htmlspecialchars($e['startingTime']) ?></td>
    <td><?= htmlspecialchars($e['endingTime']) ?></td>
    <td><?= htmlspecialchars($e['kilometers']) ?></td>
    <td><?= htmlspecialchars($e['weather']) ?></td>
    <td><?= htmlspecialchars($e['visibility']) ?></td>
    <td><?= htmlspecialchars($e['traffic']) ?></td>
    <td><?= htmlspecialchars($e['roadCondition']) ?></td>
    <td><?= htmlspecialchars($e['maneuvers'] ?? '-') ?></td>
    <td class="actions"><a href="form.php?code=<?= urlencode($code) ?>">Edit</a></td>
    <td class="actions">
        <a href="delete.php?code=<?= urlencode($code) ?>"
           onclick="return confirm('Delete this experience?')">Del</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="charts">
    <div class="chart-box">
        <h3>Weather</h3>
        <canvas id="weatherChart"></canvas>
    </div>

    <div class="chart-box">
        <h3>Visibility</h3>
        <canvas id="visibilityChart"></canvas>
    </div>

    <div class="chart-box">
        <h3>Traffic</h3>
        <canvas id="trafficChart"></canvas>
    </div>

    <div class="chart-box">
        <h3>Road Condition</h3>
        <canvas id="roadChart"></canvas>
    </div>

    <div class="chart-box">
        <h3>Maneuvers</h3>
        <canvas id="maneuverChart"></canvas>
    </div>
</div>

<script>
const options = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } }
};

function safeLabels(arr) {
  // Ensure no null labels reach Chart.js
  return arr.map(x => (x === null || x === undefined || x === "") ? "Unknown" : x);
}

function pie(id, labels, data) {
  new Chart(document.getElementById(id), {
    type: 'pie',
    data: { labels: safeLabels(labels), datasets: [{ data }] },
    options
  });
}

pie('weatherChart',
  <?= json_encode(array_column($weatherData,'label')) ?>,
  <?= json_encode(array_column($weatherData,'val')) ?>
);

pie('visibilityChart',
  <?= json_encode(array_column($visibilityData,'label')) ?>,
  <?= json_encode(array_column($visibilityData,'val')) ?>
);

pie('trafficChart',
  <?= json_encode(array_column($trafficData,'label')) ?>,
  <?= json_encode(array_column($trafficData,'val')) ?>
);

pie('roadChart',
  <?= json_encode(array_column($roadData,'label')) ?>,
  <?= json_encode(array_column($roadData,'val')) ?>
);

new Chart(document.getElementById('maneuverChart'), {
  type: 'bar',
  data: {
    labels: safeLabels(<?= json_encode(array_column($maneuverData,'label')) ?>),
    datasets: [{ data: <?= json_encode(array_column($maneuverData,'val')) ?> }]
  },
  options
});
</script>

<footer>
    <p>Driving experience from Fakhriyya Huseynova</p>
</footer>
</body>
</html>
