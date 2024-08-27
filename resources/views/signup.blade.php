<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Signup</title>
</head>
<body>
    <form method="POST" enctype="multipart/form-data" action="/register">
        <div>
            <h1>Sign up Form:
                <br>
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" placeholder="Name">
                <br>                
                <label for="phone_number">Phone Number:</label>
                <input type="tel" name="phone_number" id="phone_number" placeholder="phone number">
                <br>                
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" placeholder="username">
                <br>                
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" placeholder="email@email.email">
                <br>                
                <label for="password">Password:</label>
                <input type="password" name="password" id="password">
                <br>                
                <label for="password_confirmation">Password Confiramtion:</label>
                <input type="password" name="password_confirmation" id="password_confirmation">
                <br>                
                <label for="profile_picture">Upload Your Profile Picture:</label>
                <input type="file" name="profile_picture" id="profile_picture">
                <br>                
                <label for="certificate">Upload Your certificate:</label>
                <input type="file" name="certificate" id="certificate">
                <br>                
                <input type="submit" name="Signup" id="Signup" placeholder="Signup">
            </h1>
        </div>
    </form>
    <a href="/">Home</a>

</body>
</html>