<?php
// validation.php
// Common validation rules for TutorPk project

//--------------------------------------------------
// 1. Clean user input
//--------------------------------------------------
function clean_input($data){
    $data = trim($data);            // remove extra spaces
    $data = stripslashes($data);    // remove slashes
    $data = htmlspecialchars($data); // prevent XSS
    return $data;
}

//--------------------------------------------------
// 2. Check empty field
//--------------------------------------------------
function is_empty($field){
    return empty($field);
}

//--------------------------------------------------
// 3. Validate Name (letters and spaces)
//--------------------------------------------------
function validate_name($name){
    if(preg_match("/^[a-zA-Z ]+$/",$name)){
        return true;
    }
    return false;
}

//--------------------------------------------------
// 4. Validate Email
//--------------------------------------------------
function validate_email($email){
    if(filter_var($email,FILTER_VALIDATE_EMAIL)){
        return true;
    }
    return false;
}

//--------------------------------------------------
// 5. Validate Password Strength
//--------------------------------------------------
function validate_password($password){

    // Minimum 6 characters
    if(strlen($password) < 6){
        return false;
    }

    return true;
}

//--------------------------------------------------
// 6. Confirm Password Match
//--------------------------------------------------
function password_match($pass1,$pass2){
    return $pass1 === $pass2;
}

//--------------------------------------------------
// 7. Validate Phone Number
//--------------------------------------------------
function validate_phone($phone){

    // Only numbers allowed
    if(preg_match("/^[0-9]{10,15}$/",$phone)){
        return true;
    }

    return false;
}

//--------------------------------------------------
// 8. Validate Age
//--------------------------------------------------
function validate_age($age){

    if(is_numeric($age) && $age > 0 && $age < 100){
        return true;
    }

    return false;
}

//--------------------------------------------------
// 9. Validate URL
//--------------------------------------------------
function validate_url($url){

    if(filter_var($url,FILTER_VALIDATE_URL)){
        return true;
    }

    return false;
}

//--------------------------------------------------
// 10. Validate Numbers
//--------------------------------------------------
function validate_number($num){

    if(is_numeric($num)){
        return true;
    }

    return false;
}

//--------------------------------------------------
// 11. Validate Text Length
//--------------------------------------------------
function validate_length($text,$min,$max){

    $len = strlen($text);

    if($len >= $min && $len <= $max){
        return true;
    }

    return false;
}

//--------------------------------------------------
// 12. Validate Date
//--------------------------------------------------
function validate_date($date){

    $d = DateTime::createFromFormat('Y-m-d',$date);

    return $d && $d->format('Y-m-d') === $date;
}

//--------------------------------------------------
// 13. Validate File Upload Type
//--------------------------------------------------
function validate_file_type($file,$allowed_types){

    $file_type = strtolower(pathinfo($file,PATHINFO_EXTENSION));

    if(in_array($file_type,$allowed_types)){
        return true;
    }

    return false;
}

//--------------------------------------------------
// 14. Validate File Size
//--------------------------------------------------
function validate_file_size($size,$max_size){

    if($size <= $max_size){
        return true;
    }

    return false;
}

//--------------------------------------------------
// 15. Sanitize Output
//--------------------------------------------------
function escape_output($data){
    return htmlspecialchars($data,ENT_QUOTES,'UTF-8');
}

/**
 * Save uploaded file to target directory with checks
 */
function save_uploaded_file($file, $target_dir, $allowed_extensions, $max_size, &$error_message)
{
    if (!isset($file) || !isset($file['error'])) {
        $error_message = "Missing upload data.";
        return "";
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = "File upload failed.";
        return "";
    }

    if ($file['size'] > $max_size) {
        $error_message = "Uploaded file is too large.";
        return "";
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions, true)) {
        $error_message = "Invalid file type.";
        return "";
    }

    // Verify MIME type using finfo to reduce risk of disguised executables
    $mime_map = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
    ];

    $allowed_mimes = [];
    foreach ($allowed_extensions as $ext) {
        if (isset($mime_map[$ext])) {
            $allowed_mimes = array_merge($allowed_mimes, $mime_map[$ext]);
        }
    }

    if (!empty($allowed_mimes)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($detected_mime, $allowed_mimes, true)) {
            $error_message = "Uploaded file MIME type is not allowed.";
            return "";
        }
    }

    if (!is_dir($target_dir) && !mkdir($target_dir, 0755, true)) {
        $error_message = "Could not create upload directory.";
        return "";
    }

    $safe_name = uniqid("file_", true) . "." . $extension;
    $destination = rtrim($target_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safe_name;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $error_message = "Could not save uploaded file.";
        return "";
    }

    // Ensure uploaded file is not executable
    @chmod($destination, 0644);

    return $safe_name;
}

?>