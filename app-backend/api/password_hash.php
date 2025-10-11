<?php
$newPassword = '2025:IwM,iwFid5!'; // Replace with your desired password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
echo $hashedPassword;