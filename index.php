<?php
include 'fenye.php';


$fenye =new fenye(50);
$result=$fenye->result();
// echo $result['css'],$result['html'];die;

//////最简单的使用
	$fenye=new fenye(100,20,7);//数据总数,每页数据量,页码数量
	$fenye_html=$fenye->mainHtml();//获取html结果
	echo '简单使用：<br>'.$fenye_html['css'],$fenye_html['html'];//输出结果

	// $fenye_data=$fenye->mainData();//获取数据结果
	// $fenye_result=$fenye->result();//获取全部结果(包括数据结果和html结果)
	// print_r($fenye_html['limit']);die;//sql语句的limit信息

	

//////一选择模板:
	$fenye1=new fenye(500,12,7);

	$default_model=$fenye1->result();//默认html的model

	$fenye1->sethtmlmodel('select');//选择模板

	$select_model=$fenye1->result();//select的model 

	echo '<br><br><br>一、选择模板：<br>';

	echo '默认模板<br>'.$default_model['css'],$default_model['html'],$default_model['script'];

	echo '<br><br>select模板<br>'.$select_model['css'],$select_model['html'],$select_model['script'];

	///////二动态配置模板（或自定义模板）
	echo '<br><br><br>二、动态配置模板：<br>';

	$model=[
				'html'=>[
					'first_text'=>'第一页',//4个基础按钮中·首页·的文字
					'last_btn'=>[//4个基础按钮中·尾页·的的样式
						'normal'=>'<a href="#href#" class="config_fanye_a">最后一页</a>',//也可以在这里设置文字
						'disable'=>''//为空表示不显示
					],
				],
				'html_btn_order'=>[
					'order'=>[ //改变改参数显示顺序
						'prev_btn',
						'first_btn',
						'last_btn',
						'next_btn',
						'loop_btn',
						'prev_btn',
						'next_btn',
						'<div class="config_fanye_count">#p#/#count_paye#</div>',
					],
				]
			];

	$fenye1->sethtmlmodel('default',$model);

	$fenye1_result=$fenye1->result();

	echo '改变元素的显示顺序或内容：<br>'.$fenye1_result['css'],$fenye1_result['html'];


//////三.其他
	//由上两个例子可见,虽然第二个例子配置了css.color的参数,但是按钮颜色是相同的，因为在同一个html页面中class相同所以，css样式被覆盖了(默认颜色是绿色)，所以默认模板增加了一个类前缀参数
	echo '<br><br><br>三.其他(不常用的用法)<br>';

	$fenyecss=new fenye(100);

		$model=[
					'html'=>[
						'btn'=>[
							'normal'=>'<a href="#href#" class="pre_config_fanye_a">#text#</a>',
							'selection'=>'<a href="#href#" class="pre_config_fanye_a pre_config_fanye_a_">#text#</a>',
							'disable'=>'<a href="#href#" class="pre_config_fanye_a pre_config_fanye_d">#text#</a>',
						]
					]
				];
		$css=[
			'color'=>'#1E9FFF',
			'btn_class_pre'=>'pre_'//类前缀,需要在上面的html.btn中同时修改类名才能生效
		];

	$fenyecss->sethtmlmodel('default',$model,$css);

	$fenyecss_result=$fenyecss->result();

	echo '默认html模板中的css.btn_class_pre参数：<br>'.$fenyecss_result['css'],$fenyecss_result['html'];

	$fenyep=new fenye(100);

	$fenyep->setUrlModel(['p'=>'paye']);

	$fenyep_result=$fenyep->result();//获取结果 方法四

	echo '<br><br><br>setUrlModel方法：<br>'.$fenyep_result['css'],$fenyep_result['html'];

//////四.根据业务逻辑自定义构建更复杂的html model
	//类似bilibili模板
	echo '<br><br><br>四.根据业务逻辑构建html model<br>';
	$or=new fenye(200);
	$or->setHtmlModel('bilibili');
	// 显示逻辑定义在bilibili模板html_btn_order.order_rule
	$or_result=$or->result();
	echo '<br>类似bilibili分页逻辑：<br>'.$or_result['css'],$or_result['html'],$or_result['script'];

	//相同的bilibili模板
	$or1=new fenye(200);
	$or1->loopmodel='default1';//循环体逻辑更换成default1（新增的逻辑可以在getLoop()方法中添加）
	$or1->setHtmlModel('bilibili');
	$or1_result=$or1->result();
	echo '<br>和bilibili一样效果的分页逻辑：<br>'.$or1_result['css'],$or1_result['html'],$or1_result['script'];

	//动态的配置默认模板达到bilibili模板的效果
	$de=new fenye(200);
	$or1->loopmodel='default1';//因为这是数据逻辑，所以需要在 '初始化数据' 之前调用
	$de->mainData();//必须先初始化数据
	$model=[
		'html'=>[
			'btn'=>[
				'disable'=>''//把4个基础按钮disable状态改成空值
			],
			'first_text'=>'#self_p#',//把首页的说明改成数字
			'last_text'=>'#self_p#',//把尾页的说明改成数字
		],
		'html_btn_order'=>[
			'order'=>[//需要更更改显示排序，添加默认模板没有的按钮元素
				'prev_btn',
				'first_btn',
				'<a href="#href#" class="config_fanye_a">...</a>',
				'loop_btn',
				'<a href="#href#" class="config_fanye_a">...</a>',
				'last_btn',
				'next_btn',

			],
			'order_rule'=>[//显示逻辑
				1=>$de->loopstart>=2,
				2=>$de->loopstart>=2+1,
				4=>$de->looplast<$de->count_paye-1,
				5=>$de->looplast<$de->count_paye,
			]
		]
	];
	$de->setHtmlModel('default',$model);//配置模板
	$de_result=$de->mainHtml();//获取结果
	echo '<br>动态配置默认模板达到bilibili的效果：<br>'.$de_result['css'],$de_result['html'],$de_result['script']; 