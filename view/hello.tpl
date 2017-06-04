<!DOCTYPE html>
<html>
<head lang="en">
    <title>Parichya</title>
    <link rel="stylesheet" href="/src/style/main.css">
    <script src="https://code.jquery.com/jquery-1.11.3.js"></script>
</head>
<body>

<script type="application/javascript" src="/lib/parichya/sso/src/hello.all.js"></script>
<script type="application/javascript">
    {literal}
    window.hello.init({
        google: "34631265537-8qtlulvnrt6htck5go1kvk7irnmt0r8v.apps.googleusercontent.com"
    }, {redirect_uri: 'hello.html'});


    console.error("_READy");
    $(document).ready(function () {
        console.error("READy");
        var h1 = window.hello('google').login({
            scope: "email, offline_access"
        }).then(function (resp) {
            var access_token1 = window.hello("google").getAuthResponse().access_token;
            $.get("/api/parichya/auth/" + resp.network, {
                access_token: resp.authResponse.access_token
            }).done(function (resp) {
                console.error("done===", resp);
            })
        }, function (e) {
            console.error('You are nOT signed in to google', resp);
        });
    });

    {/literal}
</script>

</body>
</html>
