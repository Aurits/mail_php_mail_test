<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your App Title</title>
    <!-- Include any additional stylesheets or scripts -->
    @livewireStyles
</head>

<body>
    @yield('content')
    <!-- This is where Livewire components will be rendered -->

    <!-- Include Livewire scripts at the end of the body -->
    @livewireScripts
</body>

</html>