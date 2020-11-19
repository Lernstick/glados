<?php

use app\components\ActiveEventField;

?>

<aside class="main-sidebar">

    <section class="sidebar">

        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="pull-left image">
                <img src="<?= $directoryAsset ?>/img/user2-160x160.jpg" class="img-circle" alt="User Image"/>
            </div>
            <div class="pull-left info">
                <p><?= Yii::$app->user->isGuest ? '' : Yii::$app->user->identity->username; ?></p>

                <i class="fa fa-circle text-success"></i> Online
            </div>
        </div>

        <!-- search form -->
        <form action="#" method="get" class="sidebar-form">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Search..."/>
              <span class="input-group-btn">
                <button type='submit' name='search' id='search-btn' class="btn btn-flat"><i class="fa fa-search"></i>
                </button>
              </span>
            </div>
        </form>
        <!-- /.search form -->

        <?= dmstr\widgets\Menu::widget([
            'options' => ['class' => 'sidebar-menu tree', 'data-widget'=> 'tree'],
            'items' => [
                [
                    'label' => \Yii::t('main', 'Home'),
                    'icon' => 'dashboard',
                    'url' => Yii::$app->homeUrl
                ],
                [
                    'label' => \Yii::t('main', 'Actions'),
                    'icon' => 'share',
                    'visible' => !Yii::$app->user->isGuest,
                    'items' => [
                        [
                            'label' => \Yii::t('main', 'Create User'),
                            'url' => ['/user/create'],
                            'icon' => 'user',
                            'visible' => Yii::$app->user->can('user/create'),
                            'encode' => false,
                        ],
                        [
                            'label' => \Yii::t('main', 'Create Exam'),
                            'icon' => 'graduation-cap',
                            'url' => ['/exam/create'],
                            'visible' => Yii::$app->user->can('exam/create'),
                            'encode' => false,
                        ],
                        [
                            'label' => '<i class="glyphicon glyphicon-file"></i> ' . \Yii::t('main', 'Create single Ticket'),
                            'url' => ['/ticket/create', 'mode' => 'single'],
                            'visible' => Yii::$app->user->can('ticket/create'),
                            'encode' => false,
                        ],
                        [
                            'label' => '<i class="glyphicon glyphicon-duplicate"></i> ' . \Yii::t('main', 'Create multiple Tickets'),
                            'url' => ['/ticket/create', 'mode' => 'many', 'type' => 'assigned'],
                            'visible' => Yii::$app->user->can('ticket/create'),
                            'encode' => false,
                        ],
                        [
                            'label' => '<i class="glyphicon glyphicon-barcode"></i> ' . \Yii::t('main', 'Submit Ticket'),
                            'url' => ['/ticket/update', 'mode' => 'submit'],
                            'visible' => Yii::$app->user->can('ticket/update'),
                            'encode' => false,
                        ],
                        [
                            'label' => \Yii::t('main', 'Monitor Exams'),
                            'icon' => 'desktop',
                            'url' => ['/monitor'],
                            'visible' => Yii::$app->user->can('ticket/view'),
                            'encode' => false,
                        ],
                        [
                            'label' => '<i class="glyphicon glyphicon-cloud-download"></i> ' . \Yii::t('main', 'Generate results'),
                            'url' => ['/result/generate'],
                            'visible' => Yii::$app->user->can('exam/view'),
                            'encode' => false,
                        ],
                        [
                            'label' => '<i class="glyphicon glyphicon-cloud-upload"></i> ' . \Yii::t('main', 'Submit results'),
                            'url' => ['/result/submit'],
                            'visible' => Yii::$app->user->can('result/submit'),
                            'encode' => false,
                        ],
                    ],
                ],
                [
                    'label' => \Yii::t('main', 'Activities ') . 
                        ActiveEventField::widget([
                            'content' => $newActivities,
                            'options' => [
                                'class' => 'badge pull-right',
                            ],
                            'event' => 'newActivities',
                            'jsonSelector' => 'newActivities',
                            'jsHandler' => 'function(d, s){
                                s.innerHTML = eval(s.innerHTML + d);
                                s.style.animation = "";
                                setTimeout(function (){s.style.animation = "bounce 1000ms linear both";},10);
                            }',
                        ]),
                    'encode' => false,
                    'url' => ['/activity/index'],
                    'visible' => Yii::$app->user->can('activity/index'),
                ], 
                [
                    'label' => \Yii::t('main', 'Exams'),
                    'url' => ['/exam/index'],
                    'visible' => Yii::$app->user->can('exam/index'),
                ],
                [
                    'label' => \Yii::t('main', 'Tickets'),
                    'url' => ['/ticket/index'],
                    'visible' => Yii::$app->user->can('ticket/index'),
                ],
                [
                    'label' => \Yii::t('main', 'System'),
                    'visible' => !Yii::$app->user->isGuest,
                    'items' => [

                        [
                            'label' => \Yii::t('main', 'Daemons ') . 
                                ActiveEventField::widget([
                                    'content' => $runningDaemons,
                                    'options' => [
                                        'class' => 'badge pull-right',
                                        'change-animation' => 'bounce 1000ms linear both',
                                    ],
                                    'event' => 'runningDaemons',
                                    'jsonSelector' => 'runningDaemons',
                                ]),
                            'encode' => false,
                            'url' => ['/daemon/index'],
                            'visible' => Yii::$app->user->can('daemon/index'),
                        ],
                        [
                            'label' => \Yii::t('main', 'Users'),
                            'url' => ['/user/index'],
                            'visible' => Yii::$app->user->can('user/index'),
                        ],
                        [
                            'label' => \Yii::t('main', 'Profile'),
                            'url' => ['/user/view', 'id' => Yii::$app->user->id],
                            'visible' => !Yii::$app->user->isGuest && Yii::$app->user->can('user/view'),
                        ],
                        [
                            'label' => \Yii::t('main', 'Config'),
                            'url' => ['/config/system'],
                            'visible' => Yii::$app->user->can('config/system'),
                        ],
                        [
                            'label' => \Yii::t('main', 'Settings'),
                            'url' => ['/setting/index'],
                            'visible' => Yii::$app->user->can('setting/index'),
                        ], 
                        [
                            'label' => \Yii::t('main', 'Authentication Methods'),
                            'url' => ['/auth/index'],
                            'visible' => Yii::$app->user->can('auth/index'),
                        ],
                    ],
                ],

                [
                    'label' => 'DEV',
                    'visible' => YII_ENV_DEV,
                    'options' => ['class' => 'dev_item'],
                    'items' => [
                        [
                            'label' => 'Send Events',
                            'url' => ['/test/send'],
                            'options' => ['class' => 'dev_item'],
                            'visible' => YII_ENV_DEV,
                        ],
                        [
                            'label' => 'Listen to Events',
                            'url' => ['/test/listen'],
                            'options' => ['class' => 'dev_item'],
                            'visible' => YII_ENV_DEV,
                        ],
                    ]
                ],

                [
                    'label' => \Yii::t('main', 'Help'),
                    'url' => ['/howto/index.md'],
                    'visible' => !Yii::$app->user->isGuest,
                ],

                Yii::$app->user->isGuest ?
                    ['label' => \Yii::t('main', 'Login'), 'url' => ['/site/login']] :
                    [
                        'label' => \Yii::t('main', 'Logout') . ' (' . Yii::$app->user->identity->username . ')',
                        'url' => ['/site/logout'],
                        'linkOptions' => ['data-method' => 'post']
                    ],
            ],
        ]); ?>

    </section>

</aside>
