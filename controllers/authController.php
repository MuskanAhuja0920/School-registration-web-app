<?php
session_start();
require 'config/db.php';
require_once 'emailController.php';

$errors=array();
$username="";
$email="";
if(isset($_POST['Submitbutton']))
{
$username=$_POST['username'];
$email=$_POST['email'];
$password=$_POST['password'];
$confirmPassword=$_POST['confirmPassword'];

//validation
if(empty($username))
{
    $errors['username']="Username required";

}
if(!filter_var($email,FILTER_VALIDATE_EMAIL))
{
    $errors['email']="Email address is invalid";
}
if(empty($email))
{
    $errors['email']="Email required";

}
if(empty($password))
{
    $errors['password']="Password required";

}
if($password!==$confirmPassword)
{
    $errors['password']="The two password's do not match";

}
$emailQuerry="SELECT * FROM users WHERE email=? LIMIT 1";
$stmt=$conn->prepare($emailQuerry);
$stmt->bind_param('s',$email);        // S IS for string
$stmt->execute();
$result= $stmt->get_result();
$userCount=$result->num_rows;
$stmt->close();

if($userCount>0)
{
    $errors['email']="Email already in use";
}

if(count($errors)===0)
{
    $password=password_hash($password, PASSWORD_DEFAULT);
    $token=bin2hex(random_bytes(50));
    $verified=false;
    $sql="INSERT INTO users (username,email,verified,token,password) VALUES (?,?,?,?,?)";
    $stmt=$conn->prepare($sql);
    $stmt->bind_param('ssbss',$username,$email,$verified,$token,$password);
    
    if($stmt->execute())
    {
        $user_id=$conn->insert_id;
        $_SESSION['id']=$user_id;
        $_SESSION['username']=$username;
        $_SESSION['email']=$email;
        $_SESSION['verified']=$verified;
        sendVerificationEmail($email,$token);
        $_SESSION['message']="You are now logged in!";
        $_SESSION['alert-class']="alert-success";
        header('location:Home.php');
        exit();

    }
    else
    {
        $errors['db_error']="Database error:failed to register";  
    }
}
}

//login

if(isset($_POST['login-btn']))
{
$username=$_POST['username'];

$password=$_POST['password'];


//validation
if(empty($username))
{
    $errors['username']="Username required";

}
if(empty($password))
{
    $errors['password']="Password required";
}

if(count($errors)===0)
{
$sql=" SELECT * FROM users WHERE email=? OR username=? LIMIT 1";
$stmt=$conn->prepare($sql);
$stmt->bind_param('ss',$username,$username);
$stmt->execute();
$result=$stmt->get_result();
$user=$result->fetch_assoc();

if(password_verify($password,$user['password']))
{
    $_SESSION['id']=$user['id'];
    $_SESSION['username']=$user['username'];
    $_SESSION['email']=$user['email'];
    $_SESSION['verified']=$user['verified'];
    $_SESSION['message']="You are now logged in!";
    $_SESSION['alert-class']="alert-success";
    if(!$_SESSION['verified'])
    {
        header('location:home.php');
    } 
    else
    {
        header('location:index.php');
    
    }

    
    exit();
}
else{
    $errors['login fail']="Wrong Credentials";
}
}
}

//logout
if(isset($_GET['logout']))
{
    session_destroy();
    unset($_SESSION['id']);
    unset($_SESSION['username']);
    unset($_SESSION['email']);
    unset($_SESSION['verified']);
    header('location:login.php');
    echo "SESSION DESTROYED";
    exit();
}

//verify iser ny token
function verifyUser($token)
{
    global $conn;
    $sql="SELECT * FROM users WHERE token='$token' LIMIT 1";
    $result=mysqli_query($conn,$sql);
    if(mysqli_num_rows($result)>0)
    {
        $user=mysqli_fetch_assoc($result);
        $update_query="UPDATE users SET verified=1 WHERE token='$token'";
        
        if(mysqli_query($conn,$update_query))
        {
            $_SESSION['id']=$user['id'];
            $_SESSION['username']=$user['username'];
            $_SESSION['email']=$user['email'];
            $_SESSION['verified']=1;
            $_SESSION['message']="Your email was succesfully verified.";
            $_SESSION['alert-class']="alert-success";
            header('location:home.php');
            exit();
        }
        else
        {
            echo "User not found";
        }
    }
}



?>