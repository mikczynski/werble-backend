<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title')</title>
    <!-- Styles -->
    <link href="{{ asset('css/welcome.css') }}" rel="stylesheet" type="text/css">

</head>
<body>
<div id="app">

    @include('includes.title')

    <form method="POST" action="{{route('login.api')}}">
        @csrf
        <label for="login" class="logins">{{ __('LOGIN') }}</label>
        <input id="login" type="text" class="form-control @error('login') is-invalid @enderror" name="login" value="{{ old('login') }}" required autocomplete="login" autofocus>

        <label for="password" class="passwordowa">{{ __('PASSWORD') }}</label>
        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" value="{{ old('password') }}" autofocus>

        <button type="submit" class="btn btn-primary"> {{ __('Login') }}</button>

    </form>


</div>
<script src="./js/app.js"></script>
</body>
</html>
