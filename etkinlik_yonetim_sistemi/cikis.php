<?php
session_start();
session_destroy();
header("Location: giris.html");
exit;
