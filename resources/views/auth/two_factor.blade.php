<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <form action="{{ route('two_factor_verify') }}" method="POST">
        @csrf
        <label for="code">Two Factor Code</label>
        <input type="text" name="code" id="code" required>
        <button type="submit">Verify</button>
    </form>
    @if ($errors->any())
        <div>
            @foreach ($errors->all() as $error)
                <p>(( $error ))</p>
            @endforeach
        </div>
    @endif
</body>
</html>