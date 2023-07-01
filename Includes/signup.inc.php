<!DOCTYPE html>
<html>
<head>
    <title>EDL-SignUp</title>
    <style>
        body {
            background-image: url("images/back.png");
            background-size: cover;
        }

        .container {
            display: flex;
            top:90px; left:0;
            right:0; bottom:0;
            flex-direction: column;
            justify-content: center;
            align-items: center; /* Updated to center the signup tables */
            height: 100vh;
            margin-left: 10px;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px;
        }

        label {
            margin-bottom: 10px;
            color: #2271b3; /* Updated to a lighter blue color */
        }

        input[type="email"],
        input[type="text"],
        input[type="password"],
        input[type="password"] {
            background-color: #444; /* Updated to make the entry fields dark gray */
            color:#339cf1; /* Updated to make the text in entry fields white */
            border: 1px solid #000; /* Updated to add a black outline */
            padding: 5px;
            margin-bottom: 20px; /* Updated to move the input fields down by 10 pixels */
            width: 200px;
        }

        input[type="submit"] {
            background-color: #111;
            color: #fff;
            border: none;
            padding: 10px;
            width: 100px;
            cursor: pointer;
        }

        .error-message {
            color: red;
            margin-top: 10px;
        }
      

   