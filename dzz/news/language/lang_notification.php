<?php
if(!defined('IN_DZZ')) {
	exit('Access Denied');
}
$lang = array
(

	//发表评论，通知信息发布者
	'news_moderator_2_title'	=>'信息被审核退回了',
	'news_moderator_2'			=>'{author}审核意见:<span class="danger">{modreason}</span>  <a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">现在去看看</a>',
	'news_moderator_2_wx'	=>'{author}审核意见:<span class="danger">{modreason}</span>',
	'news_moderator_2_redirecturl'	=>'{url}',
	
	//发表评论，通知信息发布者
	'news_moderator_1_title'	=>'信息审核通过了',
	'news_moderator_1'			=>'{author}审核意见：<span class="success">{modreason}</span>  <a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">现在去看看</a>',
	'news_moderator_1_wx'	=>'{author}审核意见:<span class="success">{modreason}</span>',
	'news_moderator_1_redirecturl'	=>'{url}',
	
	//发表评论，通知信息发布者
	'news_moderate_title'	=>'有需要审核的信息',
	'news_moderate'			=>'{author}提醒您有新信息需要审核 <a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">现在去看看</a>',
	'news_moderate_wx'	=>'{author}提醒您有新信息需要审核',
	'news_moderate_redirecturl'	=>'{url}',
	
	//通知用户查看信息
	'news_publish_title'	=>'有新发布的信息',
	'news_publish'			=>'{author}您查看新信息 <a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">{subject}</a>',
	'news_publish_wx'			=>'{author}您查看新信息', 
	'news_publish_redirecturl'=> '{url}',
);
?>