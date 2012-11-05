<?php
require(GA_LIB_DIR.'/lib/idiorm.php');

ORM::configure(config('ga.lib.db'));
ORM::configure('username', config('ga.lib.db.user'));
ORM::configure('password', config('ga.lib.db.pass'));