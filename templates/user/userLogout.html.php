<h1>로그아웃</h1>
<p2>로그아웃 되었습니다.</p2>
<?php
session_destroy();
header('location: index.php');