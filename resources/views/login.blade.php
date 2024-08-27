<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login</title>
</head>
<body>
    <form method="POST" enctype="multipart/form-data" action="/login">
        <div>
            <h1>Login Form:
                <br>
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" placeholder="email@email.email">
                <br>
                <label for="password">Password:</label>
                <input type="password" name="password" id="password">
                <br>
                <input type="submit" name="Login" id="Login" placeholder="Login">
            </h1>
        </div>
    </form>
    <a href="/">Home</a>

</body>
</html>