<?php

session_start();


require_once "classroom-data.php";
require_once "index.php";
//include "style.css"; // CSS file (if needed)

function showNav(){
    echo '
    <nav>
        <form name="nav" method="post" action="">
            <button type="submit" name="btn-11a" value="1">11a</button>
            <button type="submit" name="btn-11b" value="1">11b</button>
            <button type="submit" name="btn-11c" value="1">11c</button>
            <button type="submit" name="btn-12a" value="1">12a</button>
            <button type="submit" name="btn-12b" value="1">12b</button>
            <button type="submit" name="btn-12c" value="1">12c</button>
            <button type="submit" name="btn-all" value="1">*</button>
        </form>
    </nav>
    ';
}

function generateRandomData($data, $class = null) {
    $result = [];
    $count = rand(10, 15);
    $subjects = $data['subjects'];
    $classes = $class ? [$class] : $data['classes'];

    foreach ($classes as $selectedClass) {
        for ($i = 0; $i < $count; $i++) {
            $lastname = $data['lastnames'][array_rand($data['lastnames'])];
            $gender = rand(0, 1) == 0 ? 'men' : 'women';
            $firstname = $data['firstnames'][$gender][array_rand($data['firstnames'][$gender])];
            $fullname = $lastname . ' ' . $firstname;
            $genderText = ($gender == 'men') ? 'Férfi' : 'Nő';


            $result[] = [
                'name' => $fullname, 
                'grades' => createGrades(), //'grades' => createGrades(),
                'class' => $selectedClass,
                'gender' => $genderText
            ];
        }
    }

    return $result;
}

function createGrades() {
    $grades = [];
            
    foreach (DATA['subjects'] as $subject) {
        $grades[$subject] = [];
        $g_num = rand(1,5);
        for ($i = 0; $i < $g_num; $i++){
            $grades[$subject][] = rand(1,5) . " ";
        }

    }
    
    return $grades;
}

function handleClassSelection() {
    $class = null;
    if (isset($_POST['btn-11a'])) $class = '11a';
    elseif (isset($_POST['btn-11b'])) $class = '11b';
    elseif (isset($_POST['btn-11c'])) $class = '11c';
    elseif (isset($_POST['btn-12a'])) $class = '12a';
    elseif (isset($_POST['btn-12b'])) $class = '12b';
    elseif (isset($_POST['btn-12c'])) $class = '12c';
    elseif (isset($_POST['btn-all'])) $class = 'all';

    return $class;
}

function generateClassData($class) {
    if (!isset($_SESSION['generatedData'])) {
        $_SESSION['generatedData'] = [];
    }

    if ($class && $class !== 'all' && !isset($_SESSION['generatedData'][$class])) {
        $_SESSION['generatedData'][$class] = generateRandomData(DATA, $class);
    }
}

function combineClassData($class) {
    $dataToDisplay = [];
    $uniqueStudents = []; 

    if ($class === 'all') {
        foreach ($_SESSION['generatedData'] as $classKey => $classData) {
            if (is_array($classData)) {
                foreach ($classData as $student) {
                    if (!is_array($student) || !isset($student['name'], $student['class'])) {
                        continue; 
                    }

                    $uniqueKey = $student['name'] . '_' . $student['class'];

                    if (!isset($uniqueStudents[$uniqueKey])) {
                        $dataToDisplay[] = $student;
                        $uniqueStudents[$uniqueKey] = true;
                    }
                }
            }
        }
    } else {
        $dataToDisplay = $_SESSION['generatedData'][$class] ?? [];
    }

    return $dataToDisplay;
}

function exportClassDataToCSV($data) {
    $filename = "class_data_" . date('Y-m-d_H-i-s') . ".csv";
    $file = fopen($filename, 'w');

    $header = ['Diák neve', 'Neme', 'Tantárgyak és osztályzatok'];
    fputcsv($file, $header);

    foreach ($data as $student) {
        if (!isset($student['name'], $student['gender'], $student['grades'])) {
            continue;
        }

        $gradesFormatted = "";
        foreach ($student['grades'] as $subject => $grade) {
            $gradesFormatted .= ucfirst($subject) . ": " . $grade . "\n";
        }

        fputcsv($file, [$student['name'], $student['gender'], $gradesFormatted]);
    }

    fclose($file);
    

}

function main() {
    showNav();

    $class = handleClassSelection();

    generateClassData($class);

    $dataToDisplay = combineClassData($class);

    if (isset($_POST['export_csv'])) {
        exportClassDataToCSV($dataToDisplay); 
    }

    displayStudentData($dataToDisplay);
}


function displayStudentData($dataToDisplay) {
    echo "<h2>Diákok adatai</h2>";
    $groupedData = [];

    foreach ($dataToDisplay as $student) {
        if (!is_array($student) || !isset($student['name'], $student['class'], $student['grades'])) {
            continue;
        }

        $class = $student['class'];
        if (!isset($groupedData[$class])) {
            $groupedData[$class] = [];
        }

        $groupedData[$class][] = $student;
    }

    foreach ($groupedData as $class => $students) {
        echo "<h3>Osztály: " . htmlspecialchars($class) . "</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Diák neve</th><th>Diák neme</th><th>Értékelések</th></tr>";
        
        foreach ($students as $student) {
            $genderClass = (htmlspecialchars($student['gender']) == 'Férfi') ? 'male' : 'female';
            echo "<tr>";
            echo "<td class = 'names'>" . htmlspecialchars($student['name']) . "</td>";  
            echo "<td class=" . $genderClass . ">" . htmlspecialchars($student['gender']) . "</td>";  
            echo "<td><ul>";
            foreach (DATA['subjects'] as $subject){
                echo "<li>$subject: ";
                foreach($student['grades'][$subject] as $grade){
                    echo "$grade";
                }
                echo "</li>";

            }
            echo "</ul></td>";
            echo "</tr>";
        }

        echo "</table><br>";
    }
}

main();





?>

<!-- HTML form for CSV export -->
<form method="post">
    <button type="submit" name="export_csv" class="export" value="1">Exportálás CSV-be</button>
</form>
