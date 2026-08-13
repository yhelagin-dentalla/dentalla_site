<?php
echo "PHP работает, версия: " . phpversion();
echo "<br>password_verify доступен: " . (function_exists('password_verify') ? 'да' : 'нет');
