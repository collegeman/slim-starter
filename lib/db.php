<?php
require(GA_LIB_DIR.'/lib/idiorm.php');

ORM::configure(GA_LIB_DB);
ORM::configure('username', GA_LIB_DB_USER);
ORM::configure('password', GA_LIB_DB_PASS);