<?php
require "includes/pdo_connect.php";
define('SECRET_KEY', 'HWPROJECT2025');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $date = $_POST['date'];
    $startingTime = $_POST['startingTime'];
    $endingTime = $_POST['endingTime'];
    $kilometers = $_POST['kilometers'];
    $weather = $_POST['weather'];
    $visibility = $_POST['visibility'];
    $traffic = $_POST['traffic'];
    $roadCondition = $_POST['roadCondition'];

    // IMPORTANT: maneuvers arrives as an array from form
    $maneuvers = isset($_POST['maneuvers']) ? $_POST['maneuvers'] : [];

    try {

        // 1️⃣ Insert into driving_experiences
        $sql = "INSERT INTO experiences 
                (date, startingTime, endingTime, kilometers, weather, visibility, traffic, roadCondition)
                VALUES 
                (:date, :startingTime, :endingTime, :kilometers, :weather, :visibility, :traffic, :roadCondition)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':date' => $date,
            ':startingTime' => $startingTime,
            ':endingTime' => $endingTime,
            ':kilometers' => $kilometers,
            ':weather'   => $weather,
            ':visibility'=> $visibility,
            ':traffic'   => $traffic,
            ':roadCondition' => $roadCondition
        ]);

        // 2️⃣ Get new experience ID
        $experience_id = $pdo->lastInsertId();

        // 3️⃣ Insert maneuvers into join table
        if (!empty($maneuvers)) {
            $link_sql = "INSERT INTO experience_maneuver (experience_id, maneuver_id)
                         VALUES (:experience_id, :maneuver_id)";
            $link_stmt = $pdo->prepare($link_sql);

            foreach ($maneuvers as $mid) {
                $link_stmt->execute([
                    ':experience_id' => $experience_id,
                    ':maneuver_id' => $mid
                ]);
            }
        }

        echo "success";

    } catch (PDOException $e) {
        echo "error: " . $e->getMessage();
    }
}
?>
