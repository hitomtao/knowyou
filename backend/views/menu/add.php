<?php
use yii\helpers\Url;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>添加菜单</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    <link rel="icon" href="<?=Url::to('@web/img/favicon.ico') ?>">
    <link rel="stylesheet" href="<?=Url::to('@web/layui/css/layui.css') ?>" media="all" />
</head>
<body class="childrenBody">
<form class="layui-form" method="post" action="<?=Url::to(['menu/insert']) ?>">
    <input type="hidden" name="type" value="<?=$type ?>">
    <div class="layui-form-item">
        <label class="layui-form-label">菜单名称</label>
        <div class="layui-input-block">
            <input type="text" name="name" class="layui-input" lay-verify="required" placeholder="请输入菜单名称" value="">
        </div>
    </div>
    <div class="layui-form-item">
            <label class="layui-form-label">菜单级别</label>
            <div class="layui-input-block">
                <select name="level" class="menuLevel" lay-filter="level">
                    <?php
                    if (!empty($level_map)) {
                        foreach ($level_map as $k => $v) {
                            echo "<option value=\"{$k}\">{$v}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
    </div>
    <div class="layui-form-item">
            <label class="layui-form-label">父级菜单</label>
            <div class="layui-input-block">
                <select name="parent_id" lay-verify="parent_id" class="parentMenu">
                    <option value="0">无</option>
                    <?php
                    if (!empty($parent_menu)) {
                        foreach ($parent_menu as $k => $v) {
                            echo "<option value=\"{$v['id']}\">{$v['name']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
    </div>
    <div class="layui-form-item">
            <label class="layui-form-label">访问链接</label>
            <div class="layui-input-block">
                <input type="text" name="url" class="layui-input" lay-verify="required" placeholder="请输入完整链接" value="">
            </div>
    </div>
    <div class="layui-form-item">
            <label class="layui-form-label">菜单权重</label>
            <div class="layui-input-block">
                <input type="text" name="weight" class="layui-input newsName" lay-verify="required" value="" placeholder="请输入权重，越小越靠前,不能为负值">
            </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-input-block">
            <p style="color:red"><?=Yii::$app->session->getFlash('message') ?></p>
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-input-block">
            <button class="layui-btn close-layer" lay-submit="" lay-filter="addNews">立即提交</button>
            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
        </div>
    </div>
</form>
<script type="text/javascript" src="<?=Url::to('@web/layui/layui.js') ?>"></script>
<script type="text/javascript" src="<?=Url::to('@web/js/backend.js') ?>"></script>
</body>
</html>