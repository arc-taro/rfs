<?php  defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'rfs';
$active_record = TRUE;

$db['rfs']['hostname'] = 'rfs_postgres_server';
$db['rfs']['username'] = 'postgres';
$db['rfs']['password'] = 'postgres';
$db['rfs']['database'] = 'rfs';
$db['rfs']['dbdriver'] = 'postgre';
$db['rfs']['dbprefix'] = '';
$db['rfs']['pconnect'] = FALSE;
$db['rfs']['db_debug'] = TRUE;
$db['rfs']['cache_on'] = FALSE;
$db['rfs']['cachedir'] = '';
$db['rfs']['char_set'] = 'utf8';
$db['rfs']['dbcollat'] = 'utf8_general_ci';
$db['rfs']['swap_pre'] = '';
$db['rfs']['autoinit'] = TRUE;
$db['rfs']['stricton'] = FALSE;
$db['rfs']['port'] = 5432;

$db['imm']['hostname'] = 'rfs_postgres_server';
$db['imm']['username'] = 'postgres';
$db['imm']['password'] = 'postgres';
$db['imm']['database'] = 'imm';
$db['imm']['dbdriver'] = 'postgre';
$db['imm']['dbprefix'] = '';
$db['imm']['pconnect'] = FALSE;
$db['imm']['db_debug'] = TRUE;
$db['imm']['cache_on'] = FALSE;
$db['imm']['cachedir'] = '';
$db['imm']['char_set'] = 'utf8';
$db['imm']['dbcollat'] = 'utf8_general_ci';
$db['imm']['swap_pre'] = '';
$db['imm']['autoinit'] = TRUE;
$db['imm']['stricton'] = FALSE;
$db['imm']['port'] = 5432;
