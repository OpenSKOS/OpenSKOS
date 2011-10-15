<?php
//commits the solr index
require_once 'Zend/Console/Getopt.php';
$opts = array(
	'help|?' => 'Print this usage message',
	'env|e=s' => 'The environment to use (defaults to "production")',
	'code=s' => 'Tenant code (required)',
	'name=s' => 'Tenant name (required when creating a tenant)',
	'email=s' => 'Admin email (required when creating a tenant)',
	'password=s' => 'Password for the Admin account'
);
$OPTS = new Zend_Console_Getopt($opts);

if ($OPTS->help) {
	echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
	exit(0);
}

$args = $OPTS->getRemainingArgs();
if (!$args || count($args)!=1) {
	echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
	fwrite(STDERR, "Expected an actions (create|delete)\n");
	exit(1);
}
$action = $args[0];

$query = $OPTS->query;

if (null === $OPTS->code) {
	fwrite(STDERR, "missing required `code` argument\n");
	exit(1);
}

include 'bootstrap.inc.php';

$model = new OpenSKOS_Db_Table_Tenants();

switch ($action) {
	case 'create':
		if (null === $OPTS->name) {
			fwrite(STDERR, "missing required `name` argument\n");
			exit(1);
		}
		if (null === $OPTS->email) {
			fwrite(STDERR, "missing required `email` argument\n");
			exit(1);
		}
		if (null === $OPTS->password) {
			$password = OpenSKOS_Db_Table_Users::pwgen(8);
		} else {
			$password = $OPTS->password;
		}
		try {
			$model->createRow(array(
				'code' => $OPTS->code,
				'name' => $OPTS->name
			))->save();
		} catch (Zend_Db_Exception $e) {
			fwrite(STDERR, $e->getMessage()."\n");
			exit(2);
		}
		$model = new OpenSKOS_Db_Table_Users();
		$model->createRow(array(
			'email' => $OPTS->email,
			'name' => $OPTS->name,
			'password' => new Zend_Db_Expr('MD5('.$model->getAdapter()->quote($password).')'),
			'tenant' => $OPTS->code,
			'type' => OpenSKOS_Db_Table_Users::USER_TYPE_DASHBOARD
		))->save();
		fwrite(STDOUT, 'A tenant has been created with this user account:'."\n");
		fwrite(STDOUT, "  - code: {$OPTS->code}\n");
		fwrite(STDOUT, "  - login: {$OPTS->email}\n");
		fwrite(STDOUT, "  - password: {$password}\n");
		break;
	case 'delete':
		$tenant = $model->find($OPTS->code)->current();
		if (null === $tenant) {
			fwrite(STDERR, "Tenant `{$OPTS->code} does not exists\n");
			exit(2);
		}
		$tenant->delete();
		break;
	default:
		fwrite(STDERR, "unkown action `{$action}`\n");
		exit(1);
}


exit(0);
