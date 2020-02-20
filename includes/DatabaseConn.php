<?php
//데이터베이스 연결
$pdo = new PDO('mysql:host=localhost; dbname=php_project; charset=UTF8', 
'root', 'autoset');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //객체 오류 처리방식을 ERRMODE_EXCEPTION으로 처리한다.
