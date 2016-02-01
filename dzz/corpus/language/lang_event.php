<?php
if(!defined('IN_DZZ')) {
	exit('Access Denied');
}
$lang = array (
	'corpus_archive'=>'归档了文集：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}" >{corpusname}</a>',
	'corpus_delete'=>'删除了文集：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}" >{corpusname}</a>',
	'corpus_restore'=>'恢复了文集：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}" >{corpusname}</a>',
	'corpus_create'=>'创建了文集：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}" >{corpusname}</a>',
	'corpus_create_dir'=>'创建了目录：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}&fid={fid}">{fname}</a>',
	'corpus_create_doc'=>'创建了文档：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}&fid={fid}">{fname}</a>',
	'corpus_edit_doc'=>'编辑了文档：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}&fid={fid}">{fname}</a>',
	'corpus_reversion_doc'=>'添加了文档：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}&fid={fid}">{fname}</a>&nbsp;的新版本',
	
	'corpus_delete_doc'=>'删除了文档：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}&fid={fid}">{fname}</a>',
	'corpus_delete_dir'=>'删除了目录：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}&fid={fid}">{fname}</a>',
	'corpus_rename_doc'=>'将文档<a href="{dzzscript}?mod=corpus&op=list&cid={cid}&fid={fid}">{ofname}</a>的名称改为： {fname}',
	'corpus_rename_dir'=>'将目录<a href="{dzzscript}?mod=corpus&op=list&cid={cid}&fid={fid}">{ofname}</a>的名称改为： {fname}',
	
	'corpus_commit_doc_add'		=>'在文档：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}&fid={fid}">{fname}</a>上发表了评论：<b>{comment}</b>',
	'corpus_commit_doc_add_reply'=>'在文档：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}&fid={fid}">{fname}</a>上回复了评论：<b>RE:{author}：{comment}</b>',
	'corpus_commit_doc_delete'	=>'在文档：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}&fid={fid}">{fname}</a>上删除了评论：<b>{comment}</b>',
	'corpus_commit_doc_delete_reply'=>'在文档：<a href="{dzzscript}?mod=corpus&op=list&cid={cid}&fid={fid}">{fname}</a>上删除了回复：<b>{comment}</b>',
);
?>