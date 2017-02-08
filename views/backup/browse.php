<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $ItemsDataProvider yii\data\ArrayDataProvider */
/* @var $VersionsDataProvider yii\data\ArrayDataProvider */
/* @var $ticket app\models\Ticket the ticket data model */
/* @var $fs app\models\RdiffFileSystem the Rdiff data model */

$js = <<< 'SCRIPT'
/* To initialize BS3 tooltips set this below */
$(function () { 
    $("[data-toggle='tooltip']").tooltip(); 
});;
/* To initialize BS3 popovers set this below */
$(function () { 
    $("[data-toggle='popover']").popover(); 
});

$(document).on('click', '.special-dropdown .dropdown-menu .pagination a', function (e) {
  e.stopPropagation();
});

//$(document).on('click', '.special-dropdown .dropdown-menu #versionLinks', function (e) {
//  $.pjax({url: this.href, container: '#browse', push: false});
//});

//$('.special-dropdown a[id="versionLinks"]').on('click', function (e) {
//    $.pjax({url: this.href, container: '#browse', push: false});
//    e.stopPropagation();
//});
SCRIPT;
// Register tooltip/popover initialization javascript
$this->registerJs($js);

?>

<div class="panel panel-default">
  <div class="panel-heading">
    <div class="row">
      <div class="col-sm-6">

<?php
        echo "<span>Path</span>: " . Html::a(
            '<span class="glyphicon glyphicon-folder-open"></span> ' . $fs->root,
            Url::to([
                false,
                'id' => $ticket->id,
                'path' => '/',
                'date' => $fs->version,
                '#' => 'browse',
            ])
        );

        $i = '';
        foreach (preg_split("/\//", $fs->path, 0, PREG_SPLIT_NO_EMPTY) as $el) {
            $i .= '/' . $el;
            echo "/" . Html::a(
                $el,
                Url::to([
                    false,
                    'id' => $ticket->id,
                    'path' => $i,
                    'date' => $fs->version,
                    '#' => 'browse',
                ])
            );
        }

?>

      </div>
      <div class="col-sm-6">
        <div class="pull-right special-dropdown">
          Version: 
          <button class="btn toggle-dropdown" data-toggle="dropdown" aria-expanded="false" aria-haspopup="true" aria-describedby="btnGroupAddon"> <span class="glyphicon glyphicon-cog"></span> <?= $fs->version == 'all' ? 'All versions overlapping' : ($fs->version == 'now' ? 'Current version' : yii::$app->formatter->format($fs->version, 'datetime')); ?></button>

          <?php Pjax::begin([
              'id' => 'w101',
              'options' => ['tag' => 'ul', 'class' => 'dropdown-menu dropdown-menu-left'],
          ]); ?>
          <?= ListView::widget([
              'dataProvider' => $VersionsDataProvider,
              'options' => [
                  'tag' => false,
                  //'class' => 'dropdown-menu dropdown-menu-right'
              ],
              /*'itemOptions' => function($model) {
                  return [
                      'tag'=> 'li',
                      'class' => 'list-group-item-success'
                  ];
              },*/
              'itemOptions' => [
                  'tag' => 'li',
              ],
              'viewParams' => ['ticket' => $ticket, 'fs' => $fs, 'date' => $date],
              'itemView' => '_browse_version',
              'emptyText' => 'No versions.',
              'emptyTextOptions' => [
                  'tag' => 'li',
                  'class' => 'empty dropdown-header',
              ],
              'layout' => '{items} {summary} {pager}',
              'summaryOptions' => [
                  'tag' => 'li',
                  'class' => 'summary dropdown-header',
              ],
              'pager' => [
                  'maxButtonCount' => 3,
                  'options' => [
                      'class' => 'dropdown-header pagination pagination-sm',
                      'style' => 'padding: 3px 20px;'
                  ]
              ],        
          ]); ?>
          <?php Pjax::end() ?>

          
        </div>
      </div>
    </div>
    <p></p>
    <?= ListView::widget([
        'dataProvider' => $ItemsDataProvider,
        'options' => [
            'id' => 'w102',
            'tag' => 'div',
            'class' => 'list-group'
        ],
        'itemOptions' => [
            'tag' => 'div',
            'class' => 'list-group-item backup-browse-item',
        ],
        'itemView' => '_browse_item',
        'viewParams' => ['ticket' => $ticket],
        'emptyText' => 'No files or directories.',
        'emptyTextOptions' => [
            'tag' => 'div',
            'class' => 'empty list-group-item backup-browse-item',
        ],        
        'layout' => '{items} <div class="panel-footer">{summary}{pager}</div>',
        'summaryOptions' => [
            'tag' => 'div',
            'class' => 'summary',
        ],
        'pager' => [
            'options' => [
                'class' => 'pagination pagination-sm',
            ]
        ],
    ]); ?>
  </div>
</div>