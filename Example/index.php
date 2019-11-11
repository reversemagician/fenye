<?php

include '../fenye.php';


$fenye=new fenye(500,10,7);
$fenye->config(['urlmodel'=>['p'=>'page']]);//改变页码的识别key
$model=[
	'html_set'=>[
		'outer_begin'=>'<div class="page">',
		'outer_end'=>'</div>',
		'btn'=>[ //按钮内容的公共配置
			'normal'=>'<a href="#href#" >#text#</a>',
			'selection'=>'<a href="#href#" class="text-page-tag active">#text#</a>',
			'disable'=>'<span class="disabled_page">#text#</span>',
		],
		'loop_btn'=>[//循环体按钮内容
			'normal'=>'<a href="#href#" class="text-page-tag">#text#</a>',
		],
	],
];
$fenye->setHtmlModel('base',$model);//以base模板为基础，动态的修改html内容
$fenye_result=$fenye->result();//结果

print_r($fenye_result['limit']);//sql的limit

//额外扩展 
//以上方式已经可以达到业务需求
//但由于慕课网的a标签路径是 href ='/course/list?page=2';
//解决方案配置 'p'、'url'或 配置'pageurl'
	
	$fenye1=new fenye(500,10,7);
	$fenye1->config(['urlmodel'=>['p'=>'page']]);//同步上面例子的key，确保演示效果
	$fenye1->config([
		'url'=>'/course/list?page=#num#',//已经被过滤的路径
		'p'=>$fenye1->p,//当前页码,动态值:第一页时是1,第二页时是2...
	]);
	$fenye1->setHtmlModel('base',$model);//模板
	$fenye1_result=$fenye1->result();//结果



	


	//////或者配置 'pageurl' 。举例如下
	// $fenye->config([
	// 	'pageurl'=>'/course/list?page=1',//当前路径这里是一个动态值(当前页面的带参数路径)，第n页时即'/course/list?page=第n页'
	// 	'urlmodel'=>['p'=>'page']
	// ]);


include 'index.html';