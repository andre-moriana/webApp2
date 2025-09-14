$content = Get-Content "app/Views/auth/login.php" -Raw
$content = $content -replace "    <style>.*?</style>", "    <!-- Login CSS -->`n    <link href=`"/public/assets/css/login.css`" rel=`"stylesheet`">"
$content = $content -replace "    <script>.*?</script>", "    <!-- Login JS -->`n    <script src=`"/public/assets/js/login.js`"></script>"
$content | Set-Content "app/Views/auth/login.php" -Encoding UTF8
