<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $ItemsDataProvider yii\data\ArrayDataProvider */
/* @var $VersionsDataProvider yii\data\ArrayDataProvider */
/* @var $ticket app\models\Ticket the ticket data model */
/* @var $fs app\models\RdiffFileSystem the Rdiff data model */
/* @var $date string the date */

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

$('#confirmRestore').on('show.bs.modal', function(e) {
    $(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
    $('#confirmRestoreItemPath').html($(e.relatedTarget).data('path'));
    $('#confirmRestoreItemDate').html($(e.relatedTarget).data('version'));
});

$(document).on('click', '#confirmRestore a#restore-now', function (e) {
  $('#confirmRestore').modal('hide');
  $('.nav-tabs a[href*="restores"]').tab('show');
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
      <div class="col-sm-8 backup-browse-item">

<?php

        echo "<span>Path</span>: " . Html::a(
            '<span class="glyphicon glyphicon-folder-open"></span> ' . $fs->root,
            Url::to([
                false,
                'id' => $ticket->id,
                'path' => '/',
                'date' => $date,
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
                    'date' => $date,
                    '#' => 'browse',
                ])
            );
        }

        if ($date != "all") {
          echo "&nbsp;&nbsp;&nbsp;";
          echo "<span class='backup-browse-options'>";
          echo Html::a(
              '<span class="glyphicon glyphicon-tasks"></span> Restore this state of the directory',
              Url::to([
                  'ticket/restore',
                  'id' => $ticket->id,
                  'file' => $fs->path,
                  'date' => $fs->version
              ]),
              [
                  'data-href' => Url::to([
                      'ticket/restore',
                      'id' => $ticket->id,
                      'file' => $fs->path,
                      'date' => $fs->version,
                      '#' => 'tab_restores',
                  ]),
                  'data-toggle' => 'modal',
                  'data-target' => '#confirmRestore',
                  'data-path' => $fs->path,
                  'data-version' => yii::$app->formatter->format($fs->version, 'backupVersion'),
              ]
          );    
          echo ")</span>";
        }

?>

      </div>
      <div class="col-sm-4">
        <div class="pull-right special-dropdown">
          Version: 
          <button class="btn toggle-dropdown" data-toggle="dropdown" aria-expanded="false" aria-haspopup="true" aria-describedby="btnGroupAddon"> <span class="glyphicon glyphicon-cog"></span> <?= $date == 'all' ? 'All versions overlapping' : (yii::$app->formatter->format($fs->version, 'datetime') . ($fs->version == $fs->newestBackupVersion ? ' (current)' : null)); ?></button>

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
        'viewParams' => ['ticket' => $ticket, 'date' => $date],
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

<?php Modal::begin([
    'id' => 'confirmRestore',
    'header' => '<h4>Confirm Restore</h4>',
    'footer' => Html::Button('Cancel', ['data-dismiss' => 'modal', 'class' => 'btn btn-default']) . '<a id="restore-now" class="btn btn-danger btn-ok">Restore</a>',
    //'size' => \yii\bootstrap\Modal::SIZE_SMALL
]); ?>

<p>You're about to restore:</p>
<div class="list-group">
  <li class="list-group-item">
    <h4 id='confirmRestoreItemPath' class="list-group-item-heading">/path/to/file</h4>
    <p class="list-group-item-text">to the state as it was at <b id='confirmRestoreItemDate'>date</b></p>
  </li>
</div>

<div class="alert alert-danger" role="alert">
  <h4>Important!</h4>

  <p>Please notice, that if the <b>file</b> exists on the target machine, it will be permanently <b>OVERWRITTEN</b> by this version!</p>
  <p>If you restore a <b>directory</b>, notice that the target directory will be restored to the exact same state of this version. Newer files will be <b>REMOVED</b>!</p>
</div>




<?php Modal::end(); ?>