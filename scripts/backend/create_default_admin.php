<?php
/** Safe convenience wrapper for local/deployment administrator provisioning. */
$username=(string)(getenv('SGW_ADMIN_USERNAME')?:'admin');
$email=(string)(getenv('SGW_ADMIN_EMAIL')?:'admin@example.local');
$password=(string)(getenv('SGW_ADMIN_PASSWORD')?:'');
$role=(string)(getenv('SGW_ADMIN_ROLE')?:'superadmin');
if($password===''){fwrite(STDERR,"Set SGW_ADMIN_PASSWORD to a unique password of at least 12 characters.\n");exit(1);}
if(strlen($password)<12){fwrite(STDERR,"Administrator password must contain at least 12 characters.\n");exit(1);}
putenv('SGW_ADMIN_USERNAME='.$username);putenv('SGW_ADMIN_EMAIL='.$email);putenv('SGW_ADMIN_PASSWORD='.$password);putenv('SGW_ADMIN_ROLE='.$role);
require __DIR__.'/create_admin.php';
