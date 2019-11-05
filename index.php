<?php
include 'fenye.php';
/*

//分页类所有可用方法
$fenye=new fneye(200);

$fenye->config();//修改配置，并重新初始化数据
$fenye->setHtmlModel();//选择html模板
$fenye->result();//获取结果

*/

echo "<style>span{color:#FF5722}</style>";
//////最简单的使用:获取结果的方法 [result()]
	$fenye=new fenye(100,20,7);//数据总数,每页数据量,页码数量
	$fenye_result=$fenye->result();//获取html结果
	
	echo '<span>最简单的使用:result()方法</span><br><br>'.$fenye_result['css'],$fenye_result['html'],'<br><br><br>';//输出结果


/////一、一般配置：config()方法
echo "<span>一、一般配置：config()方法</span><br><br>";
	$fenye1=new fenye(100);

	//[loopmodel参数]
	//页码循环部分的逻辑模式：'default','default1','pagenumber'
	//额外的逻辑可以在 getLoop()方法中添加
		//pagenumber
		$fenye1->config(['loopmodel'=>'pagenumber']);
		$fenye1_result=$fenye1->result();
		echo "loopmodel参数:3种 [可以在GetLoop()方法中自定义]<br><br>";
		echo $fenye1_result['css'],$fenye1_result['html'];
		
		$fenye1->config(['loopmodel'=>'default']);
		$fenye1_result=$fenye1->result();
		echo "<br><br>",$fenye1_result['css'],$fenye1_result['html'];

		$fenye1->config(['loopmodel'=>'default1']);
		$fenye1_result=$fenye1->result();
		echo "<br><br>",$fenye1_result['css'],$fenye1_result['html'];

	//[urlmodel参数] 配置页码识别值'p' 和 识别方式'p_url_type'
		$fenye1->config(['urlmodel'=>['p'=>'page'],'loopmodel'=>'default']);
		$fenye1_result=$fenye1->result();
		echo "<br><br>urlmodel参数:<br><br>";
		echo $fenye1_result['css'],$fenye1_result['html'];
	// url和p参数：自定义路径(配置该参数后自动获取路径方式将失效)
		$p=1;//当前页
		$url='/news/list/#num#';//当前页
		$fenye1->config(['p'=>$p,'url'=>$url]);
		$fenye1_result=$fenye1->result();
		echo "<br><br>url和p参数:<br><br>";
		echo $fenye1_result['css'],$fenye1_result['html'];

//////二、html模板：sethtmlmodel()方法
	// 额外的html模板可以再htmlModel()方法中添加
	$fenye2=new fenye(200);
	echo '<br><br><br><span>二、html模板：sethtmlmodel()方法</span><br>';

	$default_model=$fenye2->result();//默认模板

	$fenye2->sethtmlmodel('bilibili');//选择模板
	$bili_model=$fenye2->result();//bili模板

	$fenye2->sethtmlmodel('select');//选择模板
	$select_model=$fenye2->result();//select模板
	
	echo '默认模板<br>'.$default_model['css'],$default_model['html'],$default_model['script'];
	echo '<br><br>bili模板<br>'.$bili_model['css'],$bili_model['html'],$bili_model['script'];
	echo '<br><br>select模板<br>'.$select_model['css'],$select_model['html'],$select_model['script'];
	

///////三、动态配置html模板（或自定义模板）：sethtmlmodel()方法
	echo '<br><br><br><span>三、动态配置html模板（或自定义模板）：sethtmlmodel()方法</span><br>';
	$model=[
				'html'=>[
					'first_text'=>'第一页',//4个基础按钮中·首页·的文字
					'last_btn'=>[//4个基础按钮中·尾页·的样式
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
						'<div class="config_fanye_count">#p#/#countpaye#</div>',
					],
				]
			];

	$fenye2->sethtmlmodel('default',$model);

	$fenye2_result=$fenye2->result();

	echo '改变元素的显示顺序或内容：<br>'.$fenye2_result['css'],$fenye2_result['html'];

//////四.根据业务逻辑自定义构建更复杂的html模板
	echo '<br><br><br><span>四.根据业务逻辑构建html模板</span><br>';

	//相同的bilibili模板
		$or1=new fenye(200);
		$or1->config(['loopmodel'=>'default1']);//循环体逻辑更换成default1（新增的逻辑可以在getLoop()方法中添加）
		$or1->setHtmlModel('bilibili');
		$or1_result=$or1->result();
		echo '<br>和bilibili网站一样效果的分页逻辑：<br>'.$or1_result['css'],$or1_result['html'],$or1_result['script'];

	//动态的配置默认模板达到bilibili模板的效果
		$de=new fenye(200);
		$de->config(['loopmodel'=>'default1']);
		$model=[
			'html'=>[
				'btn'=>[
					'disable'=>''//把4个基础按钮disable状态改成空值
				],
				'first_text'=>'#self_p#',//把首页的说明改成数字
				'last_text'=>'#self_p#',//把尾页的说明改成数字
			],
			'html_btn_order'=>[
				'order'=>[//需要更改显示排序，添加默认模板没有的按钮元素
					'prev_btn',
					'first_btn',
					'<a href="#href#" class="config_fanye_a">...</a>',
					'loop_btn',
					'<a href="#href#" class="config_fanye_a">...</a>',
					'last_btn',
					'next_btn',

				],
				'show_rule'=>[//order的显示逻辑
					1=>$de->loopstart>=2,
					2=>$de->loopstart>=2+1,
					4=>$de->looplast<$de->countpaye-1,
					5=>$de->looplast<$de->countpaye,
				]
			]
		];
		$de->setHtmlModel('default',$model);//配置模板
		$de_result=$de->result();//获取结果
		echo '<br>动态配置默认模板达到bilibili的效果：<br>'.$de_result['css'],$de_result['html'],$de_result['script']; 

///五.其他不常用的
	echo "<br><br><br><span>五.其他不常用的</span>";

	$other=new fenye(600);
	$zn=['零','一','二','三','四','五','六','七','八','九'];
	foreach ($other->data['loop'] as $k => $v) {
		$newname='';
		foreach ($zn as $key => $value) {
			$v['name']=str_replace($key,$value,$v['name']);
			$newname=$v['name'];
		}
		$other->data['loop'][$k]['name']=$newname;
	}
	$other_result=$other->result();
	echo '<br>$data参数：<br>'.$other_result['css'],$other_result['html'];

	//默认html模板的其他css参数
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
			'color'=>'#4476A7',
			'size'=>1.5,
			'class_pre'=>'pre_'//类前缀,需要在上面的html.btn中同时修改类名才能生效
		];

	$fenyecss->sethtmlmodel('default',$model,$css);

	$fenyecss_result=$fenyecss->result();

	echo '<br><br>默认html模板的其他css参数：<br>'.$fenyecss_result['css'],$fenyecss_result['html'];

echo "<br><br><br><span>六.使用举例：Example/index.php</span>";
echo '<div style="height:200px;"> </div>';