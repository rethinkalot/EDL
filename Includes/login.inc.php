

    <div class="container"> 

        <?php 
        // Check if signup form submitted
        if (isset($_POST["submit"])) {
            // Check if all fields are filled
            if  !empty($_POST["name"]) && (!empty($_POST["email"]) && !empty($_POST["uid"]) && !empty($_POST["pwd"]) && !empty($_POST["pwdrepeat"])) {
                $name = $post["username"]   
                $email = $_POST["email"];
                $username = $_POST["uid"];
                $pwd = $_POST["pwd"];
                $pwdrepeat = $_POST["pwdrepeat"];

                // Perform your signup logic here
                // For demonstration purposes, let's assume the signup is unsuccessful
                $error_message = "SignUp failed!";
            } else {
                $error_message = "Please fill in all fields!";
            }
        }
        ?>
      

        <form method="POST" action="signup.php">

            <label for="name">name:</label>
            <input type="name" name="name" id="name" placeholder="Email">
                   
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" placeholder="Email">

            <label for="uid">Username:</label>
            <input type="text" name="uid" id="uid" placeholder="Username">

            <label for="pwd">Password:</label>
            <input type="password" name="pwd" id="pwd" placeholder="Password">

            <label for="pwdrepeat">Repeat Password:</label>
            <input type="password" name="pwdrepeat" id="pwdrepeat" placeholder="Repeat Password">

            <input type="submit" name="submit" value="SignUp">
        </form>

        <?php
        // Display error message if set
        if (isset($error_message)) {
            echo '<p class="error-message">' . $error_message . '</p>';
        }
        ?>
    </div>
</body>
</html>
