<?php
if(!defined('IN_DZZ')) {
	exit('Access Denied');
}
$lang = array
(
	//评论@
	'corpus_comment_at_title'	=>'提到(@)我的评论',
	'corpus_comment_at'	=>'{author}在文集文档:<a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">{fname}</a>的评论中提到我<b>{comment}</b>',
	'corpus_comment_at_wx'	=>'{author}在文集文档:{fname}的评论中提到我<b>{comment}</b>',
	'corpus_comment_at_redirecturl'	=>'{url}',
	
	//发表评论，通知文章作者
	'corpus_comment_mydoc_title'	=>'评论了我的文档',
	'corpus_comment_mydoc'			=>'{author}在文集文档:<a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">{fname}</a>中发表了评论<b>{comment}</b>',
	'corpus_comment_mydoc_wx'	=>'{author}在文集文档:{fname}中发表了评论<b>{comment}</b>',
	'corpus_comment_mydoc_redirecturl'	=>'{url}',
	
	//回复评论，通知被回复者
	'corpus_comment_reply_title'	=>'回复了我的评论',
	'corpus_comment_reply'			=>'{author}在文集文档:<a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">{fname}</a>中回复了我的评论<b>{comment}</b>',
	'corpus_comment_reply_wx'	=>'{author}在文集文档:{fname}中回复了我的评论<b>{comment}</b>',
	'corpus_comment_reply_redirecturl'	=>'{url}',
	
	//编辑文档和保存新版本
	'corpus_doc_reversion_title'=>'添加文档的新版本',
	'corpus_doc_reversion'		=>'{author}添加了文集文档的新版本:<a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">{fname}</a>',
	'corpus_doc_reversion_wx'	=>'{author}添加了文集文档的新版本:{fname}',
	'corpus_doc_reversion_redirecturl'	=>'{url}',
	
	'corpus_doc_edit_title'=>'编辑了文档',
	'corpus_doc_edit'		=>'{author}编辑了文档:<a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">{fname}</a>',
	'corpus_doc_edit_wx'	=>'{author}编辑了文档:{fname}',
	'corpus_doc_edit_redirecturl'	=>'{url}',
	
 //删除文档
	'corpus_doc_delete_title'=>'删除了文档',
	'corpus_doc_delete'		=>'{author}删除了文档:<a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">{fname}</a>',
	'corpus_doc_delete_wx'	=>'{author}删除了文档:{fname}',
	'corpus_doc_delete_redirecturl'	=>'{url}',
	
	
	//文集归档
	'corpus_archived_title'=>'归档了文集',
	'corpus_archived'=>'{author}归档了文集::<a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">{corpusname}</a>',
	'corpus_archived_wx'	=>'{author}归档了文集:{corpusname}',
	'corpus_archived_redirecturl'	=>'{url}',
	
	//文集恢复
	'corpus_restore_title'=>'恢复了文集',
	'corpus_restore'=>'{author}恢复了文集::<a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">{corpusname}</a>',
	'corpus_restore_wx'	=>'{author}归档了文集:{corpusname}',
	'corpus_restore_redirecturl'	=>'{url}',
	
	
	//文集成员变化
	'corpus_user_change_title'=>'文集成员改变提醒',
	'corpus_user_change'=>'{author}设置您为文集:<a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">{corpusname}</a> &nbsp;的{permtitle}',
	'corpus_user_change_wx'	=>'{author}设置您为文集:{corpusname}&nbsp;的{permtitle}',
	'corpus_user_change_redirecturl'	=>'{url}',
	
	
	
	//文集成员移除
	'corpus_user_remove_title'=>'文集成员移除提醒',
	'corpus_user_remove'=>'{author}从文集:<a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">{corpusname}</a> &nbsp;中移除了您的{permtitle}权限',
	'corpus_user_remove_wx'	=>'{author}从文集:{corpusname}&nbsp;中移除了您的{permtitle}权限',
	'corpus_user_remove_redirecturl'	=>'{url}',
	
	
	//文集成员添加
	'corpus_user_add_title'=>'文集成员添加提醒',
	'corpus_user_add'=>'{author}设置您为文集:<a href="javascript:;" onclick="OpenApp(\'{from_id}\',\'{url}\');_notice.setIsread(jQuery(this).parent().parent().parent().attr(\'nid\'));">{corpusname}</a> &nbsp;的{permtitle}',
	'corpus_user_add_wx'	=>'{author}设置您为文集:{corpusname}&nbsp;您的{permtitle}',
	'corpus_user_add_redirecturl'	=>'{url}'
);

?>