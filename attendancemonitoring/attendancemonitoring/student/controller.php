<?php
require_once ("../include/initialize.php");
if (!isset($_SESSION['ACCOUNT_ID'])){
    redirect(web_root."index.php");
}

$action = (isset($_GET['action']) && $_GET['action'] != '') ? $_GET['action'] : '';

switch ($action) {
    case 'add' :
        doInsert();
        break;
    
    case 'edit' :
        doEdit();
        break; 
    
    case 'delete' :
        doDelete();
        break;

    case 'photos' :
        doupdateimage();
        break;

    case 'checkid' :
        Check_StudentID();
        break;
}

function doInsert(){
    if(isset($_POST['save'])) {
        if ($_POST['StudentID'] == "" OR $_POST['Firstname'] == "" OR $_POST['Lastname'] == "" 
            OR $_POST['Middlename'] == "" OR $_POST['CourseID'] == "none" OR $_POST['Address'] == "" 
            OR $_POST['ContactNo'] == "") {
            message("All fields are required!", "error");
            redirect('index.php?view=add');
        } else {  
            $birthdate =  $_POST['year'].'-'.$_POST['month'].'-'.$_POST['day'];
            $age = date_diff(date_create($birthdate), date_create('today'))->y;

            if ($age < 15) {
                message("Invalid age. 15 years old and above is allowed.", "error");
                redirect("index.php?view=add");
            } else {
                // Establish database connection using MySQLi
                $connection = mysqli_connect('db', 'root', 'root', 'dbattendance');

                if (!$connection) {
                    die("Connection failed: " . mysqli_connect_error());
                }

                $sql = "SELECT * FROM tblstudent WHERE StudentID='" . $_POST['StudentID'] . "'";
                $res = mysqli_query($connection, $sql);

                if (!$res) {
                    die("Query failed: " . mysqli_error($connection));
                }

                $maxrow = mysqli_num_rows($res);
                if ($maxrow > 0) {
                    message("Student ID already in use!", "error");
                    redirect("index.php?view=add");
                } else {
                    $stud = New Student(); 
                    $stud->StudentID = $_POST['StudentID'];
                    $stud->Firstname = $_POST['Firstname']; 
                    $stud->Lastname = $_POST['Lastname'];
                    $stud->Middlename = $_POST['Middlename'];
                    $stud->CourseID = $_POST['CourseID']; 
                    $stud->Address = $_POST['Address']; 
                    $stud->BirthDate = $birthdate;
                    $stud->Age = $age;
                    $stud->Gender = $_POST['optionsRadios']; 
                    $stud->ContactNo = $_POST['ContactNo'];
                    $stud->YearLevel = $_POST['YearLevel'];
                    $stud->create();

                    message("New student created successfully!", "success");
                    redirect("index.php");
                }

                // Close the connection
                mysqli_close($connection);
            }
        }
    }
}

function doEdit(){
    if(isset($_POST['save'])) {
        if ($_POST['StudentID'] == "" OR $_POST['Firstname'] == "" OR $_POST['Lastname'] == "" 
            OR $_POST['Middlename'] == "" OR $_POST['CourseID'] == "none" OR $_POST['Address'] == "" 
            OR $_POST['ContactNo'] == "") {
            message("All fields are required!", "error");
            redirect('index.php?view=add');
        } else {  
            $birthdate =  $_POST['year'].'-'.$_POST['month'].'-'.$_POST['day'];
            $age = date_diff(date_create($birthdate), date_create('today'))->y;

            if ($age < 15) {
                message("Invalid age. 15 years old and above is allowed.", "error");
                redirect("index.php?view=view&id=".$_POST['StudentID']);
            } else {
                $stud = New Student(); 
                $stud->StudentID = $_POST['IDNO'];
                $stud->Firstname = $_POST['Firstname']; 
                $stud->Lastname = $_POST['Lastname'];
                $stud->Middlename = $_POST['Middlename'];
                $stud->CourseID = $_POST['CourseID']; 
                $stud->Address = $_POST['Address']; 
                $stud->BirthDate = $birthdate;
                $stud->Age = $age;
                $stud->Gender = $_POST['optionsRadios']; 
                $stud->ContactNo = $_POST['ContactNo'];
                $stud->YearLevel = $_POST['YearLevel'];
                $stud->studupdate($_POST['StudentID']);

                message("Student has been updated!", "success");
                redirect("index.php?view=view&id=".$_POST['StudentID']);
            }
        }
    }
}

function doDelete(){
    if (empty($_POST['selector'])) {
        message("Select the records first before you delete!", "error");
        redirect('index.php');
    } else {
        $id = $_POST['selector'];
        $key = count($id);

        for ($i = 0; $i < $key; $i++) {
            $subj = New Student();
            $subj->delete($id[$i]);
        }
        message("Student(s) already Deleted!", "success");
        redirect('index.php');
    }
}

function doupdateimage() {
    $errofile = $_FILES['photo']['error'];
    $type = $_FILES['photo']['type'];
    $temp = $_FILES['photo']['tmp_name'];
    $myfile = $_FILES['photo']['name'];
    $location = "photo/" . $myfile;

    if ($errofile > 0) {
        message("No Image Selected!", "error");
        redirect("index.php?view=view&id=". $_GET['id']);
    } else {
        @$file = $_FILES['photo']['tmp_name'];
        @$image = addslashes(file_get_contents($_FILES['photo']['tmp_name']));
        @$image_name = addslashes($_FILES['photo']['name']); 
        @$image_size = getimagesize($_FILES['photo']['tmp_name']);

        if ($image_size == FALSE) {
            message("Uploaded file is not an image!", "error");
            redirect("index.php?view=view&id=". $_GET['id']);
        } else {
            move_uploaded_file($temp, "photo/" . $myfile);

            $stud = New Student();
            $stud->StudPhoto = $location;
            $stud->studupdate($_POST['StudentID']);
            redirect("index.php?view=view&id=". $_POST['StudentID']);
        }
    }
}

function Check_StudentID() {
    // Establish a database connection using MySQLi
    $connection = mysqli_connect('localhost', 'username', 'password', 'database_name');

    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $sql = "SELECT * FROM tblstudent WHERE StudentID='" . $_POST['IDNO'] . "'";
    $res = mysqli_query($connection, $sql);

    if (!$res) {
        die("Query failed: " . mysqli_error($connection));
    }

    $maxrow = mysqli_num_rows($res);
    if ($maxrow > 0) {
        echo "Student ID already in use!";
    }

    // Close the MySQLi connection
    mysqli_close($connection);
}
?>
