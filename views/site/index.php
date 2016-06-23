<?php

use app\components\ActiveEventField;

/* @var $this yii\web\View */

$this->title = 'My Yii Application';
?>
<div class="site-index">

    <div class="col-md-6">
        <div class="well">
            <h1 class="text-center">
            <?= $running_exams ?>
            <small>running exams</small></h1>
        </div>
    </div>

    <div class="col-md-6">
        <div class="well">
            <h1 class="text-center">
            <?= ActiveEventField::widget([
                'event' => 'newActivities',
                'content' => $new_activities,
                'jsonSelector' => 'newActivities',
                'jsHandler' => 'function(d, s){s.innerHTML = eval(s.innerHTML + d)}',
                'options' => [ 'tag' => 'span' ],
            ]); ?>
            <small>new activities</small></h1>
        </div>
    </div>

    <div class="col-md-6">
        <div class="well">
            <h1 class="text-center">
            <?= $total_exams ?>
            <small>exams created</small></h1>
        </div>
    </div>

    <div class="col-md-6">
        <div class="well">
            <h1 class="text-center">
            <?= $completed_exams ?>
            <small>exams completed</small></h1>
        </div>
    </div>


    <div class="jumbotron">
        <p><a class="btn btn-lg btn-success" href="index.php?r=exam/index">Show my Exams</a></p>
    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-lg-4">
                <h2>Heading</h2>

                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                    dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                    ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                    fugiat nulla pariatur.</p>

                <p><a class="btn btn-default" href="http://www.yiiframework.com/doc/">Yii Documentation &raquo;</a></p>
            </div>
            <div class="col-lg-4">
                <h2>Heading</h2>

                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                    dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                    ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                    fugiat nulla pariatur.</p>

                <p><a class="btn btn-default" href="http://www.yiiframework.com/forum/">Yii Forum &raquo;</a></p>
            </div>
            <div class="col-lg-4">
                <h2>Heading</h2>

                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                    dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                    ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                    fugiat nulla pariatur.</p>

                <p><a class="btn btn-default" href="http://www.yiiframework.com/extensions/">Yii Extensions &raquo;</a></p>
            </div>
        </div>

    </div>
</div>
