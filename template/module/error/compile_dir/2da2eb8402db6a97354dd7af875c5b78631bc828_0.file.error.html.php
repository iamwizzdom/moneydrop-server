<?php
/* Smarty version 3.1.33, created on 2019-07-02 17:21:51
  from 'C:\xampp\htdocs\personal\que\app\template\module\error\error.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d1b849f06be43_74589968',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '2da2eb8402db6a97354dd7af875c5b78631bc828' => 
    array (
      0 => 'C:\\xampp\\htdocs\\personal\\que\\app\\template\\module\\error\\error.html',
      1 => 1562084201,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d1b849f06be43_74589968 (Smarty_Internal_Template $_smarty_tpl) {
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project error page</title>
    <style>
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.42857143;
            color: #333;
            background-color: #fff;
        }

        h3 {
            font-size: 24px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-family: inherit;
            font-weight: 500;
            line-height: 1.1;
            color: inherit;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }

        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }

        .alert-info hr {
            border-top-color: #a6e1ec;
        }

        hr {
            height: 0;
            -webkit-box-sizing: content-box;
            -moz-box-sizing: content-box;
            box-sizing: content-box;
            margin-top: 20px;
            margin-bottom: 20px;
            border: 0;
            border-top: 1px solid #eee;
        }

        b, strong {
            font-weight: 700;
        }

        @media (min-width: 992px) {
            .col-md-offset-4 {
                margin-left: 33.33333333%;
            }

            .col-md-4 {
                float: left;
                width: 33.33333333%;
                position: relative;
                min-height: 1px;
                padding-right: 15px;
                padding-left: 15px;
            }
        }

        pre {
            display: block;
            padding: 9.5px;
            margin: 0 0 10px;
            font-size: 13px;
            line-height: 1.42857143;
            color: #333;
            word-break: break-all;
            word-wrap: break-word;
            background-color: #f5f5f5;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
            overflow: auto;
        }
    </style>
</head>
<body>

<div style="margin-top: 50px;" class="col-md-4 col-md-offset-4">

    <div class="alert alert-danger" role="alert">

        <div class="m-alert__text">

            <h3><?php echo $_smarty_tpl->tpl_vars['data']->value['title'];?>
</h3>

            <span><?php echo $_smarty_tpl->tpl_vars['data']->value['message'];?>
</span>

        </div>

    </div>

</div>

</body>
</html><?php }
}
