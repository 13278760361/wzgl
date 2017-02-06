<?php
$config = array(
	//'配置项'=>'配置值'
	'URL_CASE_INSENSITIVE'=>false,
	'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR',
	'URL_MODEL'=>2,
	'DATA_AUTH_KEY'=>'qy@admin.999',
	'ADMIN_LIST'=>array(
		array(
			'id'=>-1,
			'name'=>'超级管理员1',
			'username'=>'admins',
			'password'=>'c2e6cb77d35f80407aec1776daf917c6',
			'is_auditer'=>0,
			'is_supper'=>1,
			'org_id'=>0
		),
	),
	'mobile'=>array(
		'DEFAULT_THEME'=>'Mobile',
		'TMPL_PARSE_STRING'=>array(
			'__PUBLIC__'=>'/Public/Mobile',
			'__JS__'=>'/Public/Mobile/js',
			'__CSS__'=>'/Public/Mobile/css',
			'__IMG__'=>'/Public/Mobile/images'
		)
	),
	'default'=>array(
		'DEFAULT_THEME'=>'Default',
		'TMPL_PARSE_STRING'=>array(
			'__PUBLIC__'=>'/Public/Default',
			'__JS__'=>'/Public/Default/js',
			'__CSS__'=>'/Public/Default/css',
			'__IMG__'=>'/Public/Default/images'
		)
	),
	'pagesize'=>20, //列表分页大小
	'seachsize'=>10, //搜索分页大小
	'wapsize'=>20   //手机端分页大小
	
);

$db_config = dirname(__FILE__).'/db_config.php';
$db_config = file_exists($db_config) ? include "$db_config" : array();

$sn_config = dirname(__FILE__).'/sn_config.php';
$sn_config = file_exists($sn_config) ? include "$sn_config" : array();

return array_merge($db_config,$config,$sn_config);